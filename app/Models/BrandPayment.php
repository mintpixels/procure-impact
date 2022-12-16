<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandPayment extends Model
{
    use SoftDeletes;

    protected $table = 'brand_payment';
    protected $fillable = [
        'order_id', 'brand_id', 'subtotal', 'fee', 'shipping', 'note'

    ];

    public function brand() 
    {
        return $this->belongsTo(Brand::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
