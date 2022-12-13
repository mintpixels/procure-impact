<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ProductPropery;

class PropertyValue extends Model
{
    protected $table = 'property_value';
    protected $fillable = ['property_id', 'value', 'position'];
}
