<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'order_item';
    protected $fillable = [
        'order_id', 'brand_id', 'product_id', 'variant_id', 'sku', 'name', 'quantity', 
        'price', 'line_price', 'discount', 'properties', 'approved_at', 'approved_by'
    ];

    protected $casts = ['properties' => 'array'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
