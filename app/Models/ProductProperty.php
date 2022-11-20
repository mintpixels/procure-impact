<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductProperty extends Model
{    
    protected $table = 'product_property';
    protected $fillable = ['product_id', 'property_id', 'value', 'pdp'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
