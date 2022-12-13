<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ProductPropery;

class PropertyCategoryValue extends Model
{
    protected $table = 'property_category_value';
    protected $fillable = ['property_id', 'category_id', 'value_id'];
}
