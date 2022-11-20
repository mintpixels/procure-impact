<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderBilling extends Model
{
    use SoftDeletes;

    protected $table = 'order_billing';
    protected $fillable = [
        'order_id', 'first_name', 'last_name', 'company', 'address1', 'address2',
        'city', 'state', 'zip', 'phone'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
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
