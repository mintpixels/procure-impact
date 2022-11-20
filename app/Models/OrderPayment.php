<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPayment extends Model
{
    use SoftDeletes;

    protected $table = 'order_payment';
    protected $fillable = [
        'order_id', 'method', 'amount', 'note', 'last_4', 'avs',
        'transaction_id', 'pos_number'

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
