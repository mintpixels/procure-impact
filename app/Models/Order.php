<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends WithHistory
{
    use SoftDeletes;

    protected $table = 'order';

    protected $fillable = [
        'checkout_id', 'buyer_id', 'customer_id', 'email', 'phone', 'first_name', 'last_name', 'status',
        'subtotal', 'tax', 'shipping', 'total', 'discount', 'customer_notes', 'staff_notes', 'ip_address'
    ];

    public $syncFields = [
        'customer_id', 'email', 'phone', 'subtotal',
        'tax', 'total', 'shipping',  'discount', 'paid', 'customer_notes',
        'staff_notes', 'buyer_fee', 'brand_fee'
    ];

    public $adminFields = [
        'customer_id', 'email', 'phone', 'subtotal',
        'tax', 'total', 'shipping',  'discount', 'paid', 'buyer_fee', 'brand_fee'
    ];

    protected $casts = [
        'is_priority' => 'boolean',
    ];

    protected $createdMessage = 'Order was created';
    protected $updatedMessage = 'Order was updated';
    protected $historyFields = [
        'email', 'status', 'customer_id', 'staff_notes', 'customer_notes', 'subtotal', 'tax',
        'shipping', 'total', 'discount', 'email', 'phone', 'source', 'created_at'
    ];

    /**
     * Auto save search text whenever the entity is saved.
     */
    public function save($options = [])
    {
        $this->search = $this->getSearch();
        parent::save();
    }

    /**
     * Build search text for the order.
     */
    public function getSearch()
    {
        return $this->id.
            $this->first_name . ' ' . 
            $this->last_name . '.' . 
            $this->email . '.' . 
            $this->phone . '.' . 
            preg_replace("/[^0-9]/", "", $this->phone);

        // $items = OrderShipmentItem::where('order_id', $this->id)->get();
        // foreach($items as $item)
        // {
        //     foreach($item->serial_numbers as $serial)
        //         $search .= '.' . $serial;
        // }
    }


    public static function submitted($query = false)
    {
        if(!$query) $query = Order::query();

        return $query->whereIn('status', ['Submitted']);
    }

    public static function approved($query = false)
    {
        if(!$query) $query = Order::query();

        return $query->whereIn('status', ['Approved']);
    }

    public static function completed($query = false)
    {
        if(!$query) $query = Order::query();

        return $query->whereIn('status', ['Completed']);
    }

    public static function waiting($query = false)
    {
        if(!$query) $query = Order::query();

        return $query->whereIn('status', ['Awaiting Fulfillment']);
    }


    /**
     * Get a list of all order tags.
     */
    public static function allTags()
    {
        return EntityTag::where('entity_type', 'order')
            ->orderBy('name')
            ->distinct()
            ->pluck('name')->toArray();
    }

    public function tags()
    {
        return $this->hasMany(EntityTag::class, 'entity_id', 'id')->where('entity_type', 'order')->orderBy('name');
    }

    public function tagArray()
    {
        return EntityTag::where('entity_type', 'order')
            ->where('entity_id', $this->id)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function checkout()
    {
        return $this->hasOne(Checkout::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)->where('product_id', '>', 0);
    }

    public function brands()
    {
        return $this->hasMany(OrderBrand::class);
    }

    public function billing()
    {
        return $this->hasOne(OrderBilling::class);
    }
    
    public function shipments()
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function draft()
    {
        return $this->hasOne(OrderDraft::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function fulfillment()
    {
        return $this->hasOne(OrderFulfillment::class);
    }

    public function returns()
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo('\App\Models\User', 'verified_by');
    }
}