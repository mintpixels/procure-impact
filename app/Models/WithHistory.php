<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \DB;
use \Auth;

class WithHistory extends Model
{
    protected $historyFields = [];
    protected $historyFloatFields = [];
    protected $createdMessage = '';
    protected $updatedMessage = '';

    /**
     * Get the historical timeline for the entity.
     */
    public function timeline()
    {
        // Get all the updates to the entity.
        $history = History::where('entity_type', $this->getTable())
            ->where('entity_id', $this->id)
            ->with('user')
            ->with('updates')
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get();

        // Build the timeline from the history.
        $timeline = [];
        foreach($history as $h)
        {
            $time = strtotime($h->created_at);
            $entry = (object) [
                'summary' => $h->summary,
                'source' => $h->user ? $h->user->name : $h->source,
                'note' => $h->note,
                'sdate' => date('F jS', $time),
                'date' => date('Y-m-d', $time),
                'time' => date('h:ia', $time),
                'changes' => []
            ];

            // Set custom date strings for today and yesterday.
            if($entry->date == date('Y-m-d'))
                $entry->sdate = 'Today';
            else if($entry->date ==  date('Y-m-d', time() - 60*60*24))
                $entry->sdate = 'Yesterday';

            // Include the value changes.
            foreach($h->updates as $update)
            {
                $entry->changes[] = (object) [
                    'field' => $update->field,
                    'old_value' => $update->old_value,
                    'new_value' => $update->new_value
                ];
            }

            // Include the entry in the timeline.
            $timeline[] = $entry;
        }

        return $timeline;
    }

    /**
     * Save changes to the model after changing the history
     * of fields that were updated.
     */
    public function saveWithHistory($summary = false, $note = false, $source = '', $userId = false, $force = false, $parentId = NULL)
    {
        if(!$userId && Auth::user())
            $userId = Auth::user()->id;

        // Fill in a default summary if one isn't specified.
        if(!$summary) {
            $summary = $this->id ? $this->updatedMessage : $this->createdMessage;
        }
        
        // If the entity is new or has been updated then add to the history.
        $changes = $this->changes();
        if(count($changes) > 0 || !$this->id || $force)
        {
            // If it's new go ahead and save so we have an
            // id to put in the history.
            if(!$this->id) $this->save();

            $model = $this;
            DB::transaction(function() use ($model, $changes, $userId, $source, $summary, $note, $parentId)
            {
                $history = History::create([
                    'user_id' => $userId ? $userId : NULL,
                    'parent_id' => $parentId,
                    'source' => $userId ? NULL : $source,
                    'entity_type' => $model->getTable(),
                    'entity_id' => $model->id,
                    'summary' => $summary ? $summary : NULL,
                    'note' => $note ? $note : NULL
                ]);

                // Track each changed field as well.
                foreach($changes as $field => $values)
                {

                    $oldValue = $values->old;
                    if(strlen($oldValue) > 1000)
                        $oldValue = substr($oldValue, 0, 997) . '...';

                    $newValue = $values->new;
                    if(strlen($newValue) > 1000)
                        $newValue = substr($newValue, 0, 997) . '...';

                        
                    HistoryChange::create([
                        'history_id' => $history->id,
                        'field' => $field,
                        'old_value' => $oldValue,
                        'new_value' => $newValue
                    ]);
                }
            });
        }

        // Persist the updated model to the db.
        $this->save();
    }

    /**
     * Get the difference between two object of the same type.
     */
    public function changes() 
    {
        $dbVersion = $this->find($this->id);
        if(!$dbVersion) return [];

        $changes = [];
        foreach($this->historyFields as $field)
        {
            $v1 = $this->{$field};
            if(in_array($field, $this->historyFloatFields))
                $v1 = number_format($v1, 2);

            if($v1 != $dbVersion->{$field})
            {
                $changes[$field] = (object) [
                    'old' => $dbVersion->{$field},
                    'new' => $v1
                ];
            }
        }

        return $changes;
    }
}
