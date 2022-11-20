<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use SoftDeletes;
    
    protected $table = 'product_category';
    protected $fillable = ['product_id', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
