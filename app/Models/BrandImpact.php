<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandImpact extends Model
{
    public $timestamps = false;
    
    protected $table = 'brand_impact';
    protected $fillable = ['brand_id', 'impact'];

}
