<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchFacetProduct extends Model
{
    public $timestamps = false;
    
    protected $table = 'search_facet_product';

    protected $fillable = [
        'name',
        'value',
        'product_id'
    ];
}
