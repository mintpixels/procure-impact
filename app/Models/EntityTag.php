<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntityTag extends Model
{
    protected $table = 'entity_tag';
    protected $fillable = ['entity_id', 'entity_type', 'name'];
}
