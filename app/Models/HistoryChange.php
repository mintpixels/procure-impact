<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryChange extends Model
{
    public $timestamps = false;
    protected $table = 'history_change';

    protected $fillable = ['id', 'history_id', 'field', 'old_value', 'new_value'];

    public function history()
    {
        return $this->belongsTo('\App\Models\History');
    }
}
