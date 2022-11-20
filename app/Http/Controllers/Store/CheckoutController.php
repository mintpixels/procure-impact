<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OrderConfirmation;
use App\Http\Controllers\Controller;
use App\Services\EasyPostApi;
use App\Services\AuthorizeApi;
use App\Services\OrderService;
use App\Services\InventoryService;
use App\Jobs\VerifyOrder;
use App\Models\Product;
use App\Models\Carrier;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderBilling;
use App\Models\OrderPayment;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use \Exception;
use \Auth;
use \DB;

class CheckoutController extends Controller
{
    /**
     * View the checkout page on the store.
     */
    public function view()
    {
        $cart = Cart::instance();
        if(count($cart->items) == 0)
            return redirect('/');

        DB::beginTransaction();

        $checkout = Checkout::where('guid', $cart->guid)
            ->whereNull('completed_at')
            ->first();

        if(!$checkout) 
        {
            $checkout = new Checkout();
            $checkout->guid = $cart->guid;
            $checkout->billing = (object) ['id' => ''];
            $checkout->payment = new \stdClass;
            $checkout->shipments = [];
            $checkout->save();
        }

        $checkout->customer_id = NULL;
        // if(Auth::guard('customer')->check())
        // {
        //     $customer = Auth::guard('customer')->user();
        //     $checkout->customer_id = $customer->id;
        //     $checkout->email = $customer->email;
        // }

        CheckoutItem::where('checkout_id', $checkout->id)->delete();

        $items = [];
        foreach($cart->items as $item)
        {
            CheckoutItem::create([
                'checkout_id' => $checkout->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'price' => $item->price,
                'base_price' => $item->base_price,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
                'tax' => 0,
                'properties' => $item->properties
            ]);
        }
        
        $checkout->load('items');

        // $failed = InventoryService::checkHoldAvailability($checkout->items);
        // if(count($failed) > 0) 
        // {
        //     return redirect('/#cart')->with([
        //         'failed' => $failed
        //     ]);
        // }

        $checkout->getTax();
        $checkout->getShipments();
        $checkout->save();

        DB::commit();
        
        return redirect("checkout/$checkout->guid");
    }

    /**
     * Clear the state of the checkout. 
     */
    public function clear(Checkout $checkout)
    {
        $checkout->delete();

        return redirect('checkout');
    }

    /**
     * Sign out the user in checkout.
     */
    public function signout(Checkout $checkout)
    {
        Auth::guard('customer')->logout();
        $checkout->customer_id = NULL;
        $checkout->save();

        return $this->checkout($checkout);
    }

    public function signin(Request $r, Checkout $checkout)
    {
        $credentials = $r->only('email', 'password');
        if(Auth::guard('customer')->attempt($credentials)) {
            $customer = Auth::guard('customer')->user();
            $checkout->customer_id = $customer->id;
            $checkout->email = $customer->email;
            $checkout->save();

            return $this->checkout($checkout);
        }

        return response()->json([
            'error' => 'Login Failed'
        ]);
    }

    /**
     * View a specific checkout.
     */
    public function viewById(Checkout $checkout)
    {
        if($checkout->completed_at)
        {
            return redirect('thankyou/' . $checkout->guid);
        }

        return view('store.checkout')->with([
            'checkout' => $checkout
        ]);
    }

    public function thankYou(Checkout $checkout)
    {
        return view('store.thankyou')->with([
            'order' => $checkout->order
        ]);
    }

    /**
     * Get the checkout for the session.
     */
    public function checkout(Checkout $checkout)
    {
        // $checkout->load('dealer');
        $checkout->load('items.product', 'items.variant');
        $checkout->load('customer');

        $checkout->addresses = [];
        if($checkout->customer)
        {
            $checkout->addresses = $checkout->customer->addresses;
        }
        
        return response()->json([
            'checkout' => $checkout
        ]);
    }

    /**
     * Save updates to a customer.
     */
    public function saveCustomer(Request $r, Checkout $checkout)
    {
        $checkout->email = $r->email;
        $checkout->customer_id = $r->customer_id;
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to pickup status.
     */
    public function savePickup(Request $r, Checkout $checkout)
    {
        $checkout->is_pickup = $r->is_pickup;
        if($checkout->is_pickup && $checkout->accepts_insurance) {
            $checkout->accepts_insurance = false;
        }

        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to use billing status.
     */
    public function saveUseBilling(Request $r, Checkout $checkout)
    {
        $checkout->use_billing = $r->use_billing;
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to insurance status.
     */
    public function saveInsurance(Request $r, Checkout $checkout)
    {
        $checkout->accepts_insurance = $r->accepts_insurance ? 1 : 0;
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to tax amount.
     */
    public function saveTax(Request $r, Checkout $checkout)
    {
        $checkout->getTax();
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to use billing status.
     */
    public function removeDealer(Request $r, Checkout $checkout)
    {
        $checkout->dealer_id = NULL;
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Save updates to the billing address
     */
    public function saveBilling(Request $r, Checkout $checkout)
    {
        $checkout->billing = (object) [
            'id' => $r->id ?? '',
            'first_name' => $r->first_name,
            'last_name' => $r->last_name,
            'company' => $r->company,
            'address1' => $r->address1,
            'address2' => $r->address2,
            'city' => $r->city,
            'state' => $r->state,
            'zip' => $r->zip,
            'phone' => $r->phone,
        ];
        
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Convert the checkout to an order.
     */
    public function completeCheckout(Request $r, Checkout $checkout)
    {
        if($checkout->order_id)
        {
            return response()->json([
                'error' => 'Duplicate checkout id'
            ], 400);
        }

        $result = OrderService::completeCheckout($checkout, $r);
        if(!$result->success) {
            return response()->json($result, 400);
        }

        try
        {
            if(env('RC_MODE') == 'live') {
                Mail::to($result->order->email)->send(new OrderConfirmation($result->order));
            }
            else {
                Mail::to('aimtest@ryanas.com')->send(new OrderConfirmation($result->order));
            }

            $result->order->saveWithHistory('Order confirmation sent to ' . $result->order->email, false, '', false, true);
        }
        catch(\Exception $e) {
            Log::info('Error sending email: ' . $e->getMessage());
        }

        return response()->json([
            'order_id' => $result->order->id
        ]);
    }

    public function saveMethod(Request $r, Checkout $checkout, $shipmentIndex)
    {
        $shipments = $checkout->shipments;
        $shipments[$shipmentIndex]->methodId = $r->methodId;
        foreach($shipments[$shipmentIndex]->methods as $m)
        {
            if($m->id == $r->methodId)
                $shipments[$shipmentIndex]->method = $m;
        }

        $checkout->shipments = $shipments;
        $checkout->getShippingPrice();
        $checkout->save();

        return $this->checkout($checkout);
    }

    /**
     * Get available shipping methods for all requested shipments.
     */
    public function getAllMethods(Request $r, Checkout $checkout)
    {
        foreach($r->shipments as $shipment)
        {
            $this->getMethods($r, $checkout, $shipment['index'], $shipment['address']);
        }
 
        return $this->checkout($checkout);
    }

    /**
     * Get available shipping methods for a shipment.
     */
    public function getMethods(Request $r, Checkout $checkout, $shipmentIndex, $address = false)
    {
        $shipments = $checkout->shipments;
        $shipments[$shipmentIndex]->address = $address;

        $total = 0;
        $weight = 0;
        foreach($shipments[$shipmentIndex]->items as $item)
        {
            $total += $item->price * $item->quantity;
            $weight += $item->product->dimensions->weight * $item->quantity;
        }
        
        $signature = ($total >= 1000 || $shipments[$shipmentIndex]->ffl_required);

        $address =  [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'company' => $address['company'] ?? '',
            'street1' => $address['address1'],
            'street2' => $address['address2'] ?? '',
            'city' => $address['city'],
            'state' => $address['state'],
            'zip' => $address['zip'],
        ];

        $api = new EasyPostApi;

        // If weight is over the max then break the weights to multiple shipments.
        if($weight >= 50)
        {
            $weights = [];
            while($weight >= 50)
            {
                $weights[] = 49;
                $weight -= 49;
            }
            $weights[] = $weight;

            $multi = $api->createMultiShipment('', $address, $weights, $signature);
            $allShipments = $multi->shipments;
        }
        else 
        {
            $shipment = $api->createShipment('', $address, $weight, $signature);
            $allShipments = [$shipment];
        }
        
        $methods = [];

        if($shipments[$shipmentIndex]->free)
        {
            $methods[] = (object) [
                'id' => 'freeshipping',
                'carrier' => '',
                'service' => 'Free Shipping',
                'price' => 0,
                'days' => 10
            ];
        }

        foreach($allShipments as $shipment)
        {
            $shipments[$shipmentIndex]->external_id = $shipment->id;

            foreach($shipment->rates as $rate)
            {
                $key = $rate->carrier . '-' . $rate->service . '-' . $rate->carrier_account_id;
                $serviceKey = $rate->carrier . '-' . $rate->service;
                
                if(!in_array($key, $shipments[$shipmentIndex]->carriers))
                    $key = $serviceKey;

                if(in_array($key, $shipments[$shipmentIndex]->carriers))
                {
                    if(!array_key_exists($key, $methods))
                    {
                        $name = $rate->carrier;
                        $method = $rate->service;
                        $accountId = false;

                        $carrier = Carrier::where('mapping_to', $serviceKey)
                            ->where('account_id',  $rate->carrier_account_id)
                            ->first();

                        if($carrier)
                        {
                            $name = $carrier->provider;
                            $method = $carrier->method;
                            $accountId = $carrier->account_id;
                        }

                        $delay = 0;
                        if($key == 'USPS-First')
                            $delay = 2;
                            
                        $methods[] = (object) [
                            'id' => $rate->id,
                            'carrier' => $name,
                            'service' => $method,
                            'price' => $rate->rate * 1.12,
                            'days' => $rate->est_delivery_days + $delay
                        ];
                    }
                    else 
                    {
                        $methods[$key]->price += $rate->rate * 1.12;
                    }
                }
            }
        }

        usort($methods, function($a, $b) { return $a->price > $b->price ? 1 : -1; });
        $shipments[$shipmentIndex]->methods = $methods;
        $shipments[$shipmentIndex]->method = count($methods) > 0 ? $methods[0] : false;
        $shipments[$shipmentIndex]->methodId = count($methods) > 0 ? $shipments[$shipmentIndex]->method->id : false;

        $checkout->shipments = $shipments;
        $checkout->getShippingPrice();
        $checkout->save();
    }
}