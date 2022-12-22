<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AvalaraApi;
use App\Services\RulesService;
use \Auth;

class Checkout extends Model
{
    use SoftDeletes;

    protected $table = 'checkout';
    protected $casts = [
        'shipments' => 'object',
        'billing' => 'object',
        'payment' => 'object'
    ];

    public function getShippingPrice()
    {
        // $price = 0;
        // if(!$this->is_pickup)
        // {
        //     foreach($this->shipments as $shipment)
        //     {
        //         if(isset($shipment->method) && $shipment->method)
        //             $price += $shipment->method->price;
        //     }
        // }
        // $this->shipping = $price;
    }

    /**
     * Get the tax amount for the order.
     */
    public function getTax()
    {
        // $taxCode = '';
        // if($this->customer && !$this->customer->taxable)
        //     $taxCode =  'G';

        // $api = new AvalaraApi;

        // $address = false;
        // if(isset($this->billing->address1))
        //     $address = $this->billing;

        // if(isset($this->shipments[0]->address->address1))
        //     $address = $this->shipments[0]->address;
            
        // $tax = $api->getTaxEstimate($this->items, $taxCode, $this->shipping, $address);

        // Assign the tax values.
        $this->tax = 0;
        $this->save();
    }

    /**
     * Get the shipments for the order.
     */
    public function getShipments()
    {
        // Get the subtotal for only items that are free shipping eligible.
        // $subtotal = 0;
        // foreach($this->items as $item) {
        //     if($this->freeShippingEligible([$item])) 
        //         $subtotal += $item->price * $item->quantity;
        // }

        // Should we try to split shipments based on items that
        // can ship for free.
        // $freeEligible = $this->freeShippingEligible($this->items, $subtotal);

        $shipments = [];
        $splitReasons = [];

        $shipment = (object) [
            'items' => [],
            'carriers' => [], //$carriers->available,
            'failed' => [], //$carriers->failed,
            'free' => [], //$itemFreeShipping,
            'address' => (object) [
                'id' => ''
            ],
            'methods' => []
        ];
        $shipments[] = $shipment;
        
        foreach($this->items as $item) 
        {
            // Check if this item should be shipped for free.
            // $itemFreeShipping = $freeEligible && $this->freeShippingEligible([$item]);
            $itemFreeShipping = $this->itemCanShipFree($item);

            $item->allow_pickup = false;

            for($i = 0; $i < count($shipments); $i++)
            {
                $shipment = $shipments[$i];

                // // If this item is eligible for free shipping but the shipment isn't
                // // free then don't include it.
                // if($itemFreeShipping != $shipment->free) {
                //     $splitReasons[] = "Certain items qualify for free shipping";
                //     continue;
                // }

                // // Check if the item FFL requirement matches the current shipment requirement.
                // $fflRequired = $shipment->ffl_required ?? false;
                // $itemFflRequired = RulesService::requireFFL([$item->product->sku]);
                // if($fflRequired != $itemFflRequired)
                //     continue;

                // $shipmentItems = array_merge($shipment->items, [$item]);
                // $carriers = $this->findCarriers($shipmentItems, $this->subtotal);

                // if(count($carriers->available) > 0)
                // {
                    $shipment->items[] = $item;
                //     $shipment->carriers = $carriers->available;
                //     $shipment->failed = $carriers->failed;

                //     // We found a spot for the current item so
                //     // skip to the next.
                //     continue 2;
                // }
                // else 
                // {
                //     // Get the reason why this shipment must be split.
                //     foreach($carriers->failed as $failed) 
                //     {
                //         if($failed->matched > 1) 
                //             $splitReasons[] = $failed->rule;
                //     }
                // }
            }

            // Couldn't fit into any existing shipment so
            // create a new one.
            // $carriers = $this->findCarriers([$item], $this->subtotal);
            
            // $this->checkFFLNeeded($shipment);
        }

        $this->shipments = $shipments;
    }

    /**
     * Check if any of the specified shipments need to be
     * shipped to an FFL licensed recipient.
     */
    private function checkFFLNeeded(&$shipment) 
    {    
        $skus = [];
        foreach($shipment->items as $item) {
            $skus[] = $item->product->sku;
        }   

        $shipment->ffl_required = RulesService::requireFFL($skus);
    }

    /**
     * Find the carriers that can ship all the products.
     */
    private function findCarriers($items, $orderValue = 0, $ffl = false)
    {
        $skus = [];
        foreach($items as $item)
            $skus[] = $item->product->sku;

        $products = Product::getBySkus($skus);

        // Check each carrier to see all the specified items
        // can be shipped with that carrier.
        $available = $failed = [];
        $carriers = Carrier::all();
        foreach($carriers as $carrier)
        {
            $carrierFailed = [];
            foreach($carrier->rules as $ruleMap)
            {
                // If a rule was matched then this carrier can't be used.
                $productsMatched = RulesService::matchesRule($ruleMap->rule, $products, $ffl);
                if($productsMatched) 
                {
                    $carrierFailed[] = (object) [
                        'carrier' => $carrier->name,
                        'rule' => $ruleMap->rule->name,
                        'matched' => $productsMatched
                    ];
                }
                else if($carrier->minimum_order && $orderValue < $carrier->minimum_order)
                {
                    $carrierFailed[] = (object) [
                        'carrier' => $carrier->name,
                        'rule' => 'Minimum order value not reached',
                        'matched' => []
                    ];
                }
            }

            // No rules matched so we can use it.
            if(count($carrierFailed) == 0) 
            {
                // $mappings = array_map('trim', explode(',', $carrier->mapping));
                // foreach($mappings as $mapping)
                //     $available[] = $mapping;
                $available[] = $carrier->mapping_to . '-' . $carrier->account_id;
            }
            else {
                $failed = array_merge($failed, $carrierFailed);
            }
        }

        return (object) [
            'available' => $available,
            'failed' => $failed
        ];
    }

    /**
     * Check if an item is eligible for free shipping.
     */
    private function itemCanShipFree($item)
    {
        $product = Product::where('id', $item->product_id)->first();
        return $product && in_array('FREESHIP', $product->tagArray());
    }

    /**
     * Check if the order is free shipping eligible.
     */
    private function freeShippingEligible($items, $orderValue = false)
    {
        $carrier = Carrier::where('name', 'Free Shipping')->first();
        if(!$carrier || !$carrier->minimum_order)
            return false;

        if($orderValue && $orderValue < $carrier->minimum_order)
            return false;

        // See if any of the rules exclude 
        foreach($carrier->rules as $ruleMap)
        {
            // If any product is eligble for free shipping then the order is
            // considered eligible. The free shipping items will be broken into
            // a separate shipment.
            foreach($items as $item)
            {
                if(!RulesService::matchesRule($ruleMap->rule, $item->product))
                    return true;
            }
        }

        return false;
    }

    /**
     * Calculate totals for the checkout.
     */
    public function calculate()
    {
        $this->subtotal = 0;
        foreach($this->items as $item)
            $this->subtotal += $item->price * $item->quantity;

        // Insurance is 2% of order with a minimum of 99 cents.
        $this->insurance = .02 * $this->subtotal;
        if($this->insurance < .99)
            $this->insurance = .99;

        $this->fee = $this->subtotal * .05;
        $this->fee = round(100*$this->fee)/100;

        $this->total = $this->subtotal;
        $this->total = round(100*$this->total)/100;
        $this->total += $this->shipping;
        $this->total = round(100*$this->total)/100;
        $this->total += $this->accepts_insurance ? $this->insurance : 0;
        $this->total = round(100*$this->total)/100;
        $this->total += $this->tax;
        $this->total = round(100*$this->total)/100;
        $this->total += $this->fee;
        $this->total = round(100*$this->total)/100;
    }

    /**
     * Auto calculate totals whenever the checkout is saved.
     */
    public function save($options = [])
    {
        $this->getShippingPrice();
        $this->calculate();
        parent::save();
    }
    
    //-------------------------------------------------------------------

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'guid', 'guid');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CheckoutItem::class, 'checkout_id', 'id');
    }
}
