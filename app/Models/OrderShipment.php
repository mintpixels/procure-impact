<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderShipment extends Model
{
    use SoftDeletes;

    protected $table = 'order_shipment';
    protected $casts = [
        'photos' => 'array'
    ];
    protected $fillable = [
        'order_id', 'dealer_id', 'ffl_required', 'first_name', 'last_name', 'company',
        'address1', 'address2', 'city', 'state', 'zip', 'phone', 'method', 'amount'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderShipmentItem::class, 'shipment_id', 'id');
    }

    /**
     * Get the value of the items included in the shipment.
     */
    public function getValue()
    {
        $value = 0;
        foreach($this->items as $item)
            $value += $item->item->price * $item->quantity;

        return $value;
    }

    /**
     * Get the expected shipment weight based on the item in the shipment.
     */
    public function loadWeight()
    {
        $this->weight = 0;
        foreach($this->items as $item)
        {
            if($item->item->product) {
                $this->weight += $item->item->product->dimensions->weight * $item->quantity;
            }
        }
        $this->weight = round($this->weight, 1);
    }

    public function formatted()
    {
        $address = "$this->address1";
        if($this->address2)
            $address .= " $this->address2";
        
        $address .= ", $this->city, $this->state $this->zip";

        return $address;
    }
}
