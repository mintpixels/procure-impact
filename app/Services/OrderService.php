<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Services\InventoryService;
use App\Models\Carrier;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\DropshipItem;
use App\Models\DropshipOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderBilling;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Rule;
use App\Models\FraudCheck;
use Carbon\Carbon;
use \DB;

class OrderService
{
    /** 
     * Complete a checkout.
     */
    public static function completeCheckout($checkout, $r = false)
    {
        return OrderService::completeOrder($checkout, false, $r);
    }

    /** 
     * Complete an order draft.
     */
    public static function completeDraft($draft, $r = false)
    {
        return OrderService::completeOrder(false, $draft, $r);
    }

    /**
     * Complete an order.
     */
    private static function completeOrder($checkout, $draft = false, $r = false)
    {
        if($checkout)
        {
            $data = $checkout;
            $insurance = $data->accepts_insurance ? $data->insurance : 0;
            $failed = InventoryService::checkoutHold($data->items, $checkout->id);
        }
        else
        {
            $data = $draft->data;
            $data->email = $data->customer->email;

            if(!isset($data->shipments))
                $data->shipments = [];
                
            $insurance = 0;
            if(isset($data->acceptInsurance) && $data->acceptInsurance)
                $insurance = $data->insurance;
                
            $failed = InventoryService::draftHold($data->items, $draft->id);
        }

        // If we were unable to claim the inventory then we can't complete the order.
        if(count($failed) > 0)
        {
            return (object)[
                'success' => false,
                'error' => 'There was not enough inventory to complete the order',
                'products' => $failed
            ];
        }

        DB::beginTransaction();

        try {
            $pickup = (isset($data->is_pickup) && $data->is_pickup) || count($data->shipments) == 0;
            
            // If the customer doesn't exist then create it before continuing.
            if(isset($data->customer->id))
            {
                $customer = Customer::find($data->customer->id);
            }
            else
            {
                $customer = Customer::where('email', $data->email)->first();
                if(!$customer)
                {
                    if(isset($data->customer))
                    {
                        $phone = $data->customer->phone ?? '';
                        if(!$phone)
                            $phone = $data->billing->phone ?? '';

                        $customer = Customer::create([
                            'first_name' => $data->customer->first_name ?? '', 
                            'last_name' => $data->customer->last_name ?? '', 
                            'company' => $data->customer->company ?? '', 
                            'email' => $data->customer->email, 
                            'phone' => $phone,
                            'group_id' => $data->customer->group_id ?? NULL,
                            'notes' => $data->customer->notes ?? NULL
                        ]);
                    }
                    else
                    {
                        $customer = Customer::create([
                            'first_name' => $data->billing->first_name ?? '', 
                            'last_name' => $data->billing->last_name ?? '', 
                            'company' => $data->billing->company ?? '', 
                            'email' => $data->email, 
                            'phone' => $data->billing->phone ?? '', 
                        ]);
                    }
                }
            }

            $order = Order::create([
                'customer_id' => $customer->id, 
                'dealer_id' => $data->dealer_id ?? NULL, 
                'email' => $data->email, 
                'phone' => $data->billing->phone ?? NULL, 
                'first_name' => $data->billing->first_name, 
                'last_name' => $data->billing->last_name,
                'status' => $pickup ? 'Awaiting Pickup' : 'New',
                'subtotal' => $data->subtotal,
                'tax' => $data->tax, 
                'shipping' => $data->shipping, 
                'insurance' => $insurance,
                'total' => $data->total, 
                'discount' => $data->discount ?? 0, 
                'customer_notes' => $data->customer_notes,
                'staff_notes' => $data->staff_notes ?? '',
                'ip_address' => $_SERVER["HTTP_CF_CONNECTING_IP"] ?? ($r ? $r->ip() : NULL)
            ]);

            $order->name = $order->id;
            $order->saveWithHistory('Order was created', '', '', false, true);

            $billing = OrderBilling::create([
                'order_id' => $order->id,
                'first_name' => $data->billing->first_name,
                'last_name' => $data->billing->last_name,
                'company' => $data->billing->company,
                'address1' => $data->billing->address1,
                'address2' => $data->billing->address2,
                'city' => $data->billing->city,
                'state' => $data->billing->state,
                'zip' => $data->billing->zip,
                'phone' => $data->billing->phone,
            ]);

            // Add the address to the customer if it is not already in 
            // the address list.
            $customer->addUniqueAddress($billing);

            foreach($data->items as $item)
            {
                if(!isset($item->product_id))
                    $item->product_id = $item->product->id;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id, 
                    'product_id' => $item->product_id, 
                    'sku' => $item->product->sku, 
                    'name' => $item->product->name, 
                    'quantity' => $item->quantity, 
                    'price' => $item->price, 
                    'line_price' => $item->price * $item->quantity, 
                    'discount' => $item->discount ?? 0,
                    'properties' => $item->properties ?? NULL
                ]);
            }

            if($pickup)
            {
                // Check if any products require servicing.
                foreach($order->items as $item)
                {
                    if(in_array('Service', $item->product->tagArray()))
                    {
                        $order->status = 'Pickup Pending Service';
                        $order->saveWithHistory();
                        break;
                    }
                }
            }

            if(!$pickup)
            {
                foreach($data->shipments as $shipment)
                {
                    $useDealer = $data->dealer && $shipment->ffl_required;
                    $address = $useDealer ? $data->dealer : $shipment->address;

                    $ship = OrderShipment::create([
                        'order_id' => $order->id,
                        'dealer_id' => $data->dealer && $shipment->ffl_required ? $data->dealer_id : NULL,
                        'ffl_required' => $shipment->ffl_required,
                        'first_name' => $useDealer ? '' : $address->first_name,
                        'last_name' => $useDealer ? '' : $address->last_name,
                        'company' => $useDealer ? $data->dealer->name : $address->company ?? NULL,
                        'address1' => $address->address1,
                        'address2' => $address->address2 ?? NULL,
                        'city' => $address->city,
                        'state' => $address->state,
                        'zip' => $address->zip,
                        'phone' => $address->phone ?? NULL,
                        'method' => $shipment->method->carrier . '-' . $shipment->method->service,
                        'amount' => $shipment->method->price,
                    ]);

                    // See if the shipping address should be added to the customer
                    // as well.
                    if(!$useDealer)
                        $customer->addUniqueAddress($ship);

                    foreach($shipment->items as $item)
                    {
                        $productId = $item->product_id ?? $data->items[$item->idx]->product_id;
                        $orderItem = OrderItem::where('order_id', $order->id)
                            ->where('product_id', $productId)
                            ->first();

                        OrderShipmentItem::create([
                            'shipment_id' => $ship->id,
                            'order_id' => $order->id,
                            'order_item_id' => $orderItem->id,
                            'quantity' => $item->quantity
                        ]);
                    }
                }
            }

            if(!$pickup)
            {
                if($checkout)
                {
                    $api = new AuthorizeApi;
                    $response = $api->authorizeCreditCard($order, $order->total, $r->token);
                    if(isset($response->id))
                    {
                        OrderPayment::create([
                            'order_id' => $order->id, 
                            'method' => 'Authorize.net', 
                            'amount' => $order->total, 
                            'last_4' => $r->last4,
                            'transaction_id' => $response->id,
                            'avs' => $response->avs
                        ]);

                        $order->status = 'New';
                        $order->save();
                    }
                    else {
                        Log::info('Credit Card error', (array)$response);
                        throw new \Exception("There was an error processing the credit card");
                    }
                }
                else
                {
                    foreach($data->payments as $payment)
                    {
                        if(isset($payment->accept_token))
                        {
                            $order->load('billing');
                            
                            $api = new AuthorizeApi;
                            $response = $api->authorizeCreditCard($order, $payment->amount, $payment->accept_token);

                            if(isset($response->error)) 
                            {
                                throw new \Exception(
                                    "There was an error processing the credit card: " .
                                    $response->error
                                );
                            }
                            else
                            {
                                OrderPayment::create([
                                    'order_id' => $order->id, 
                                    'method' => 'Authorize.net', 
                                    'amount' => $payment->amount, 
                                    'last_4' => $payment->last_4,
                                    'transaction_id' => $response->id,
                                    'avs' => $response->avs,
                                    'note' => $payment->note
                                ]);
                            }
                        }
                        else 
                        {
                            OrderPayment::create([
                                'order_id' => $order->id, 
                                'method' => $payment->method,
                                'amount' => $payment->amount, 
                                'note' => $payment->note
                            ]);
                        }
                    }
                }
            }

            if($checkout)
            {
                $checkout->order_id = $order->id;
                $checkout->completed_at = date('Y-m-d H:i:s');
                $checkout->save();

                // Check if the customer completed the order after coming
                // from an abandoned cart.
                $cutoff = date("Y-m-d H:i:s", strtotime('-2 hours'));
                if($checkout->cart->recovered_at > $cutoff) 
                {
                    $order->source = 'Abandoned Cart';
                    $order->saveWithHistory();
                }

                $checkout->cart->delete();
            }
            else
            {
                $draft->order_id = $order->id;
                $draft->completed_at = date('Y-m-d H:i:s');
                $draft->save();
            }
            
            DB::commit();

            if($checkout)
            {
                InventoryService::clearCheckoutHold($checkout->id);
            }
            else
            {
                InventoryService::clearDraftHold($draft->id);
            }

            if(!$pickup)
            {
                OrderService::autoVerify($order);
            }

            return (object)[
                'success' => true,
                'order' => $order
            ];

        } catch (\Exception $e) {

            DB::rollback();

            return (object)[
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform operations need when an order is cancelled.
     */
    public static function cancelOrder($order, $restock = true)
    {
        $order->status = 'Cancelled';

        // Restock inventory on the order if requested.
        if($restock)
        {
            $inventory = [];
            foreach($order->items as $item)
                $inventory[$item->product_id] = $item->quantity;

            InventoryService::releaseInventory($inventory);
        }

        // Attempt to void the transactions on the order.
        foreach($order->payments as $payment)
        {
            if($payment->transaction_id)
            {
                $api = new AuthorizeApi;
                $result = $api->voidTransaction($payment->transaction_id);
                if($result->success)
                {
                    $payment->voided_at = date('Y-m-d H:i:s');
                    $payment->save();
                }
            }
        }
    }

    /**
     * Change the items on a currently existing order.
     */
    public static function updateOrderItems($order, $items, $data = false)
    {
        // Get the current number of each item.
        $quantities = [];
        foreach($order->items as $item)
            $quantities[$item->id] = $item->quantity;

        // Get the quantity updates that need to be applied to 
        // each item.
        foreach($items as $item)
        {
            $item->claimQty = $item->quantity;
            if(isset($item->id) && $item->id)
                $item->claimQty = $item->quantity - $quantities[$item->id];
        }

        DB::beginTransaction();

        try 
        {
            $failed = InventoryService::claimInventory($items);

            // If we were unable to claim the inventory then we can't complete the updated.
            if(count($failed) > 0)
            {
                DB::rollback();
                return (object)[
                    'success' => false,
                    'error' => 'There was not enough inventory to complete the order',
                    'products' => $failed
                ];
            }

            // Get the items that have been removed.
            $toDelete = [];
            foreach($order->items as $item)
            {
                $found = false;
                foreach($items as $newItem)
                {
                    if(isset($newItem->id) && $newItem->id == $item->id)
                        $found = true;
                }    

                if(!$found) 
                {
                    // Add inventory back for the product.
                    InventoryService::updateInventory($item->product_id, $item->quantity);
                    
                    // Track items to delete.
                    $toDelete[] = $item->id;
                }
            }

            // Remove deleted items from the order.
            OrderItem::where('order_id', $order->id)
                ->whereIn('id', $toDelete)
                ->delete();

            foreach($items as $item)
            {
                // Update existing items.
                if(isset($item->id) && $item->id)
                {
                    OrderItem::where('id', $item->id)->update([
                        'price' => $item->price,
                        'quantity' => $item->quantity, 
                        'price' => $item->price, 
                        'line_price' => $item->price * $item->quantity,
                    ]);
                }
                else
                {
                    // Add new items.
                    OrderItem::create([
                        'order_id' => $order->id, 
                        'product_id' => $item->product->id, 
                        'sku' => $item->sku, 
                        'name' => $item->name, 
                        'quantity' => $item->quantity, 
                        'price' => $item->price, 
                        'line_price' => $item->price * $item->quantity, 
                        'discount' => $item->discount ?? 0
                    ]);
                }
            }

              // Replace the order shipments.
              if($data && isset($data->shippingUpdated) && $data->shippingUpdated)
              {
                  OrderShipment::where('order_id', $order->id)->delete();
                  OrderShipmentItem::where('order_id', $order->id)->delete();
                  foreach($data->shipments as $shipment)
                  {
                      $useDealer = $data->dealer && $shipment->ffl_required;
                      $address = $useDealer ? $data->dealer : $shipment->address;
  
                      $ship = OrderShipment::create([
                          'order_id' => $order->id,
                          'dealer_id' => $data->dealer && $shipment->ffl_required ? $data->dealer_id : NULL,
                          'ffl_required' => $shipment->ffl_required,
                          'first_name' => $useDealer ? '' : $address->first_name,
                          'last_name' => $useDealer ? '' : $address->last_name,
                          'company' => $useDealer ? $data->dealer->name : $address->company ?? NULL,
                          'address1' => $address->address1,
                          'address2' => $address->address2 ?? NULL,
                          'city' => $address->city,
                          'state' => $address->state,
                          'zip' => $address->zip,
                          'phone' => $address->phone ?? NULL,
                          'method' => $shipment->method ? $shipment->method->carrier . '-' . $shipment->method->service : '',
                          'amount' => $shipment->method ? $shipment->method->price : ''
                      ]);
  
                      foreach($shipment->items as $item)
                      {
                          
                          $productId = $item->product_id ?? $data->items[$item->idx]->product->id;
                          $orderItem = OrderItem::where('order_id', $order->id)
                              ->where('product_id', $productId)
                              ->first();
  
                          OrderShipmentItem::create([
                              'shipment_id' => $ship->id,
                              'order_id' => $order->id,
                              'order_item_id' => $orderItem->id,
                              'quantity' => $item->quantity
                          ]);
                      }
                  }
              }

            DB::commit();

            return (object)[
                'success' => true,
                'order' => $order
            ];

        } catch (\Exception $e) {

            DB::rollback();

            return (object)[
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

    }

    /**
     * Check if the order should be auto verified.
     */
    public static function autoVerify($order)
    {
        if($order->verified_at)
        {
            $order->saveWithHistory('Order was not auto verified - Already Verified', '', 'System', false, false, true);
            return;
        }

        // Check if any products require servicing.
        foreach($order->items as $item)
        {
            if(in_array('Service', $item->product->tagArray()))
            {
                $order->status = 'Pending Service';
                $order->saveWithHistory('Order was not auto verified - Pending Service', '', 'System', false, true);
                return false;
            }
        }

        $rule = Rule::where('name', 'Auto Verify')->first();

        // Every product must be eligible for auto verification in
        // order to verify.
        foreach($order->items as $item) 
        {       
            if(!RulesService::matchesRule($rule, $item->product)) 
            {
                $order->saveWithHistory('Order was not auto verified - ' . $item->sku, '', 'System', false, true);
                return false;
            }

            // Don't auto verify orders with Zanders products.
            // if(strlen($item->sku) > 0 && $item->sku[0] == 'Z')
            //     return false;
        }

        // Perform fraud checks.
        $checks = FraudCheck::all();
        foreach($checks as $check)
        {
            $name = "$order->first_name $order->last_name";
            if($check->name == $name ||
               $check->email == $order->email ||
               ($check->ip_address && $check->ip_address == $order->ip_address) ||
               $check->address == $order->billing->address1)
               
            {
                // TODO: remove magic number.
                $order->failed_rule_id = 94;
                $order->saveWithHistory('Order was not auto verified - Fraud Check', '', 'System', false, true);
                return false;
            }
        }

        // Confirm all totals match.
        $subtotal = 0;
        foreach($order->items as $item)
            $subtotal += $item->line_price;

        $total = $subtotal + $order->shipping + $order->insurance + $order->tax;
        if(abs($total - $order->total) > 0.01)
        {
            $order->saveWithHistory('Order was not auto verified - Totals do not match', '', 'System', false, true);
            return false;
        }

        
        // If an FFL is required then check the address agains easy check.
        // foreach($order->address as $address)
        // {
        //     if($address->ffl_required)
        //     {
        //         $ezcheck = new EzCheckApi;
        //         $ezcheck->getAddress();
        //     }
        // }

        // This can be auto verified, now check if it's eligible.
        $status = (object) RulesService::shippable($order);
        if($status->shippable)
        {
            $order->verified_at = date('Y-m-d H:i:s');
            $order->verified_by = 0;
            $order->status = 'Awaiting Fulfillment';
            $order->saveWithHistory('Order was auto verified', '', 'System', false, true);
        }
        else 
        {
            foreach($status->rules as $rule)
            {
                $failed = $rule->name;

                // Prioritize fraud rules.
                if($rule->is_fraud_rule || !$order->failed_rule_id)
                {
                    $order->failed_rule_id = $rule->id;
                    break;
                }
            }

            $order->saveWithHistory('Order was not auto verified - ' . $failed, '', 'System', false, true);
        }
    }

    public static function splitShipments($items, $subtotal)
    {
        // Should we try to split shipments based on items that
        // can ship for free.
        $freeEligible = false;

        $merged = [];
        foreach($items as $item) {
            $merged[] = (object) $item;
        }

        $shipments = [];
        $splitReasons = [];
        foreach($merged as $item) 
        {
            // Check if this item should be shipped for free.
            $itemFreeShipping = $freeEligible;

            $product = Product::where('sku', $item->sku)->first();
            // if($product)
            //     $item->allow_pickup = stripos($product->attributes, 'NoPickup') === FALSE;

            for($i = 0; $i < count($shipments); $i++)
            {
                $shipment = $shipments[$i];

                // If this item is eligible for free shipping but the shipment isn't
                // free then don't include it.
                if($itemFreeShipping != $shipment->free) {
                    $splitReasons[] = "Certain items qualify for free shipping";
                    continue;
                }

                $shipmentItems = array_merge($shipment->items, [$item]);
                $carriers = OrderService::findCarriers($shipmentItems, $subtotal);

                if(count($carriers->available) > 0)
                {
                    $shipment->items[] = $item;
                    $shipment->carriers = $carriers->available;
                    $shipment->failed = $carriers->failed;

                    // We found a spot for the current item so
                    // skip to the next.
                    continue 2;
                }
                else 
                {
                    // Get the reason why this shipment must be split.
                    foreach($carriers->failed as $failed) 
                    {
                        if($failed->matched > 1) 
                            $splitReasons[] = $failed->rule;
                    }
                }
            }

            // Couldn't fit into any existing shipment so
            // create a new one.
            $carriers = OrderService::findCarriers([$item], $subtotal);
            $shipments[] = (object) [
                'items' => [$item],
                'carriers' => $carriers->available,
                'failed' => $carriers->failed,
                'free' => $itemFreeShipping
            ];
        }

        // Check if any shipments need to be split due to weight.
        $splitShipments = OrderService::splitByWeight($shipments);
        if(count($splitShipments) > count($shipments))
            $splitReasons[] = 'Package weight was heavier than maximum allowed';

        // Check if any of the shipments require an FFL.
        OrderService::checkFFLNeeded($splitShipments);

        return (object)[
            'shipments' => $splitShipments,
            'splitReasons' => array_values(array_unique($splitReasons))
        ];
    }

    /**
     * Find the carriers that can ship all the products.
     */
    private static function findCarriers($items, $orderValue = 0, $ffl = false)
    {
        $skus = [];
        foreach($items as $item)
            $skus[] = $item->sku;

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
                $mappings = array_map('trim', explode(',', $carrier->mapping));
                foreach($mappings as $mapping)
                    $available[] = $mapping;
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
     * Check if any of the specified shipments need to be
     * shipped to an FFL licensed recipient.
     */
    public static function checkFFLNeeded(&$shipments) {

        foreach($shipments as $shipment)
        {
            $skus = [];
            foreach($shipment->items as $item) {
                $skus[] = $item->sku;
            }   

            $shipment->ffl_required = RulesService::requireFFL($skus);
        }
    }

     /**
     * Check if the shipments need to be split up because
     * they weight more than allowed.
     */
    public static function splitByWeight(&$shipments)
    {
        $MAX_WEIGHT = 49.9;

        // print_r($shipments);exit;
        $newShipments = [];
        foreach($shipments as $shipment)
        {
            // Get the total weight of each shipment.
            $weight = 0;
            foreach($shipment->items as $item) 
            {   
                $product = Product::where('sku', $item->sku)
                    ->select('dimensions')
                    ->first();

                $item->weight = $product->dimensions->weight;
            }

            $cutoff = $MAX_WEIGHT;
            $newItems = [];
            foreach($shipment->items as $item) 
            {  
                // Just include all weightless items.
                $included = $item->quantity;
                if($item->weight > 0)
                    $included = min(floor($cutoff / $item->weight), $item->quantity);

                if($included > 0) 
                {
                    $newItems[] = (object) [
                        'sku' => $item->sku,
                        'quantity' => $included
                    ];
                }

                // Remove the items weight from the remaining weight
                // that is allowed for the shipment.
                $cutoff -= $included * $item->weight;

                // We've hit the weight limit for this shipment so start
                // a new one.
                while($included < $item->quantity)
                {
                    $newShipments[] = (object) [
                        'items' => $newItems,
                        'carriers' => $shipment->carriers,
                        'failed' => $shipment->failed
                    ];
                    
                    // Continue processing the shipment with the original
                    // max allowable weight.
                    $cutoff = $MAX_WEIGHT;
                    $item->quantity -= $included;
                    $included = min(floor($cutoff / $item->weight), $item->quantity);

                    $newItems = [];
                    if($included > 0) 
                    {
                        $newItems[] = (object) [
                            'sku' => $item->sku,
                            'quantity' => $included
                        ];
                    }
                }
            }

            // Include the remaining items in one last shipment.
            if(count($newItems) > 0) 
            {
                $newShipments[] = (object) [
                    'items' => $newItems,
                    'carriers' => $shipment->carriers,
                    'failed' => $shipment->failed
                ];
            }
        }

        return $newShipments;
    }

}