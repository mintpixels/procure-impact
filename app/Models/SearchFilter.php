<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchFilter extends Model
{
    protected $table = 'search_filter';
    protected $fillable = ['category_id', 'property_id', 'position'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
