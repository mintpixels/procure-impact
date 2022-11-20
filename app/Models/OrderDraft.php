<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDraft extends Model
{
    use SoftDeletes;

    protected $table = 'order_draft';
    protected $fillable = ['order_id', 'guid', 'user_id', 'cancelled_at'];
    protected $casts = [
        'data' => 'object'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
