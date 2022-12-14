<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Buyer extends Model
{
    use SoftDeletes;
    
    protected $table = 'buyer';
    protected $fillable = ['name'];

    public function documents()
    {
        return $this->hasMany(BuyerDocument::class);
    }
}
