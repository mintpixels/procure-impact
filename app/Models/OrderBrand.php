<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBrand extends Model
{
    protected $table = 'order_brand';
    protected $fillable = ['order_id', 'brand_id'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
