<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    protected $table = 'customer_address';
    protected $fillable = [
        'id', 'customer_id', 'first_name', 'last_name', 'address1',
        'address2', 'company', 'city', 'state', 'zip', 'phone'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
