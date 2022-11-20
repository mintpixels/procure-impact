<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'history';

    protected $fillable = ['id', 'user_id', 'source', 'entity_type', 'entity_id', 'summary', 'note', 'parent_id', 'updated_at', 'created_at'];

    public function user()
    {
        return $this->belongsTo('\App\Models\User');
    }

    public function updates()
    {
        return $this->hasMany('\App\Models\HistoryChange', 'history_id', 'id');
    }
}
