<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRelated extends Model
{   
    protected $table = 'product_related';
    protected $fillable = ['product_id', 'related_id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'related_id', 'id');
    }
}
