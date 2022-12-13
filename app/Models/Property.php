<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ProductPropery;

class Property extends Model
{
    protected $table = 'property';
    protected $fillable = ['name'];

    public function values()
    {
        return $this->hasMany(PropertyValue::class)->orderBy('value');
    }

    public function products()
    {
        return $this->hasMany(ProductProperty::class);
    }
}
