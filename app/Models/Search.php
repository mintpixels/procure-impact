<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Search extends Model
{
    public $timestamps = false;
    
    protected $table = 'search';

    protected $fillable = [
        'index',
        'product_id',
        'category_id',
        'priority',
        'field'
    ];

    public function product()
    {
        return $this->hasOne('\App\Models\Product');
    }


    public function category()
    {
        return $this->hasOne('\App\Models\Category');
    }
}
