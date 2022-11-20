<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutItem extends Model
{
    protected $table = 'checkout_item';
    protected $fillable = ['checkout_id', 'product_id', 'variant_id', 'base_price', 'price', 'quantity', 'tax', 'properties'];
    protected $casts = ['properties' => 'array'];

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
