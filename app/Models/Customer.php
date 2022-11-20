<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'customer';

    protected $hidden = ['password'];
    
    protected $fillable = [
        'first_name', 'last_name', 'company', 'email', 'phone', 'group_id',
        'notes', 'taxable', 'pay_later', 'accepts_emails', 'disabled', 'password'
    ];

    public $syncFields = [
        'first_name', 'last_name', 'company', 'email', 'phone', 'notes', 'group_id',
        'taxable', 'accepts_emails', 'pay_later', 'disabled'
    ];

    protected $withCount = ['orders'];

    /**
     * Check if a customer exists with the given email and is not
     * the customer with the given id.
     */
    public static function existsWithEmail($email, $id)
    {
        $customer = Customer::where('email', $email)->first();
        return $customer && $customer->id != $id;
    }

    /**
     * Auto save search text whenever the entity is saved.
     */
    public function save($options = [])
    {
        $this->search = $this->getSearch();
        parent::save();
    }
    
    /**
     * Build search text for the customer.
     */
    public function getSearch()
    {
        return $this->first_name . ' ' . 
            $this->last_name . '.' . 
            $this->email . '.' . 
            $this->company . '.' . 
            $this->phone . '.' . 
            preg_replace("/[^0-9]/", "", $this->phone);
    }

    /**
     * Get the total amouht spent the customer.
     */
    public function spent()
    {
        // Order::where('customer_id', $this->id)
    }

    /**
     * Add the address to the customer address list if it doesn't 
     * exists in the list.
     */
    public function addUniqueAddress($address)
    {
        $addressFormatted = $address->formatted();
        
        $addresses = CustomerAddress::where('customer_id', $this->id)->get();

        $exists = false;
        foreach($addresses as $addy)
        {
            if(strtolower($addy->formatted()) == strtolower($addressFormatted))
                $exists = true;
        }
        
        if(!$exists)
        {
            CustomerAddress::create([
                'customer_id' => $this->id,
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'company' => $address->company,
                'address1' => $address->address1,
                'address2' => $address->address2,
                'city' => $address->city,
                'state' => $address->state,
                'zip' => $address->zip,
                'phone' => $address->phone,
            ]);
        }
    }

    public function metrics()
    {
        return (object) [

            // All orders, including cancelled orders.
            'orders' => Order::where('customer_id', $this->id)->count(),

            // Number of cancelled orders.
            'cancelled' => Order::where('customer_id', $this->id)->where('status', 'Cancelled')->count(),

            // Total spend on non-cancelled orders.
            'spend' => Order::where('customer_id', $this->id)->where('status', '!=', 'Cancelled')->sum('total'),

            // Total spend on non-cancelled orders in the last year
            'spend_year' => Order::where('customer_id', $this->id)->where('status', '!=', 'Cancelled')->whereRaw('created_at > DATE_SUB(NOW(),INTERVAL 1 YEAR)')->sum('total')
        ];
    }

    //--------------------------------------------------------------------------

    public function loadDealers()
    {
        return [];
        $dealerIds = Order::where('customer_id', $this->id)
            ->whereNotNull('dealer_id')
            ->pluck('dealer_id')->toArray();

        $this->dealers = Dealer::whereIn('id', $dealerIds)->get();
        return $this->dealers;
    }


    public function orderCount()
    {
        return Order::where('customer_id', $this->id)
            ->whereNotIn('status', ['Cancelled', 'Incomplete'])
            ->count();
    }

    public function openOrders()
    {
        return Order::where('customer_id', $this->id)
            ->whereNotIn('status', ['Completed', 'Cancelled', 'Incomplete'])
            ->with('items')
            ->orderBy('id', 'DESC')
            ->get();
    }

    //--------------------------------------------------------------------------

    public function buyer() 
    {
        return $this->belongsTo(Buyer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'DESC');
    }

    public function recentOrders()
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'DESC')->take(10);
    }

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'group_id', 'id');
    }
    
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class)
            ->orderBy('is_default', 'DESC')
            ->orderBy('created_at', 'ASC');
    }
}
