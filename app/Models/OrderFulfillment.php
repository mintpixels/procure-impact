<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFulfillment extends WithHistory
{
    protected $table = 'order_fulfillment';
    protected $fillable = ['order_id', 'bin', 'picked_by', 'pick_started_at'];
    protected $historyFields = [
        'bin'
    ];

    protected $createdMessage = 'Fulfillment was created';
    protected $updatedMessage = 'Fulfillment was updated';
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function pickedBy()
    {
        return $this->belongsTo(User::class, 'picked_by', 'id');
    }

    public function packedBy()
    {
        return $this->belongsTo(User::class, 'packed_by', 'id');
    }
}