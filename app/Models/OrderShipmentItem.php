<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderShipmentItem extends Model
{
    use SoftDeletes;
    
    protected $table = 'order_shipment_item';
    protected $fillable = [
        'shipment_id', 'order_id', 'order_item_id', 'quantity'
    ];
    protected $casts = [
        'serial_numbers' => 'array'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shipment()
    {
        return $this->belongsTo(OrderShipment::class);
    }
    
    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }
}
