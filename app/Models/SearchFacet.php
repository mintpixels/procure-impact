<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SearchFacet extends Model
{
    protected $table = 'search_facet';

    protected $fillable = [
        'name',
        'display_name',
        'handle',
        'is_enabled',
        'category_id',
        'position'
    ];
}
