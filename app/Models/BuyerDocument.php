<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerDocument extends Model
{
    protected $table = 'buyer_document';
    protected $fillable = ['buyer_id', 'name', 'path', 'state'];
}
