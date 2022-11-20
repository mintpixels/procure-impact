<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use App\Mail\OrderProblem;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Jobs\VerifyOrder;
use App\Services\AvalaraApi;
use App\Services\AuthorizeApi;
use App\Services\EasyPostApi;
use App\Services\InventoryService;
use App\Services\RulesService;
use App\Services\OrderService;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderBilling;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\Problem;
use App\Models\Carrier;
use App\Models\OrderDraft;
use \Auth;
use \DB;

class OrderController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'orders');
    }

    /**
     * Show the main orders screen.
     */
    public function ordersView()
    {
        return view('admin.orders')->with([
            'page' => 'orders',
            'counts' => (object) [
                'unverified' => Order::unverified()->count(),
                'problem' => Order::problemOrders()->count(),
                'pickup' => Order::pickup()->count(),
                'shipping_problem' => Order::whereStatus('Shipping Problem')->count(),
                'unpaid' => Order::unpaid()->count(),
                'unshipped' => Order::unshipped()->count(),
                'held' => Order::held()->count(),
                'service' => Order::whereIn('status', ['Pending Service', 'Pickup Pending Service'])->count(),
            ]
        ]);
    }

    /**
     * Show the screen for a single order.
     */
    public function orderView(Order $order)
    {
        return view('admin.order')->with([
            'page' => 'orders',
            'type' => 'order',
            'order' => $order
        ]);
    }

    /**
     * Get the matching list of orders.
     */
    public function orders(Request $r)
    {
        // See if there is a direct match based on order number.
        $match = $r->search ? Order::find($r->search) : false;
        if($match) 
        {
            $match->load('customer');
            $match->load('payments');
            $match->load('billing');
            $match->load('draft');
            $match->loadCount('items');
            return response()->json([
                'orders' => [$match]
            ]);
        }

        $shipment = $r->search ?  OrderShipment::where('tracking_number', $r->search)->first() : false;
        if($shipment)
        {
            $order = Order::find($shipment->order_id);
            $order->load('customer');
            $order->load('payments');
            $order->load('billing');
            $order->load('draft');
            $order->loadCount('items');
            return response()->json([
                'orders' => [$order]
            ]);
        }

        if($r->search)
        {
            $product = Product::where('sku', $r->search)->first();
            if($product)
            {
                $orders = Order::orderBy('id', 'DESC')
                ->whereHas('items', function($q) use($product) {
                    $q->where('sku', $product->sku);
                })->with('customer')
                ->withCount('items')
                ->with('payments')
                ->with('billing')
                ->with('draft')->get();

                return response()->json([
                    'orders' => $orders
                ]);

            }
        }

        $page = $r->page ?? 0;

        $orders = Order::orderBy('id', 'DESC')
            ->with('customer')
            ->withCount('items')
            ->with('payments')
            ->with('billing')
            ->with('draft')
            ->take(250)
            ->offset(250*$page);
        
        if($r->search)
            $orders->where('search', 'like', '%'.$r->search.'%');

        $product = false;
        if($r->product_id)
        {
            $filter = 'All';
            $orderIds = OrderItem::where('product_id', $r->product_id)->pluck('order_id')->toArray();
            $orders->whereIn('id', $orderIds);
            $orders->whereNotIn('status', ['Completed', 'Cancelled', 'Incomplete']);
            $orders->take(2000);
            $product = Product::find($r->product_id);
        }

        $customer = false;
        if($r->customer_id)
        {
            $filter = 'All';
            $orders->where('customer_id', $r->customer_id);
            $orders->take(2000);
            $customer = Customer::find($r->customer_id);
        }

        $filter = $r->filter ?? 'Unverified';
        if($filter) {
            $this->filterOrders($orders, $filter);
        }

        return response()->json([
            'orders' => $orders->get(),
            'product' => $product,
            'customer' => $customer
        ]);
    }

    /**
     * Filter orders based on a condition.
     */
    private function filterOrders($orders, $filter)
    {
        if($filter == 'Unverified') {
            Order::unverified($orders);
        }
        else if($filter == 'Pickup') {
            Order::pickup($orders);
        }
        else if($filter == 'Unshipped') {
            Order::unshipped($orders);
        }
        else if($filter == 'Problem') {
            $orders->whereStatus('Problem');
        }
        else if($filter == 'Shipping Problem') {
            $orders->whereStatus('Shipping Problem');
        }
        else if($filter == 'Unpaid') {
            Order::unpaid($orders);
        }
        else if($filter == 'Held') {
            Order::held($orders);
        }
        else if($filter == 'Service') {
            $orders->whereIn('status', ['Pending Service', 'Pickup Pending Service']);
        }
    }

    /**
     * Get information on a specific order.
     */
    public function order($id)
    {
        $order = Order::where('id', $id)
            ->with('customer.addresses')
            ->with('items.product')
            ->with('billing')
            ->with('shipments.items.item')
            ->with('payments')
            ->with('verifiedBy')
            ->with('dealer')
            ->with('problem')
            ->first();

        $groups = CustomerGroup::orderBy('name')->get();

        $order->tags = $order->tagArray();
        $tags = Order::allTags();

        $shippable = RulesService::shippable($order);
            
        $noverify = [];
        if(!$shippable['shippable'])
        {
            foreach($shippable['rules'] as $rule)
            {
                if($rule->no_verify)
                    $noverify[] = $rule;
            }
        }

        foreach($order->items as $item)
            $item->product->getLowestPrice($order->customer->group_id ?? false);

        return response()->json([
            'order' => $order,
            'groups' => $groups,
            'timeline' => $order->timeline(),
            'shippable' => RulesService::shippable($order),
            'tags' => [],
            'problems' => Problem::orderBy('name')->get(),
            'noverify' => $noverify
        ]);
    }

    /**
     * Verify that an order is allowed to be shipped.
     */
    public function verifyOrder(Order $order)
    {
        $order->verified_at = date('Y-m-d H:i:s');
        $order->verified_by = Auth::user()->id;
        $order->status = 'Awaiting Fulfillment';
        $order->saveWithHistory('Order was verified'); 

        $order->load('verifiedBy');
        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Remove verification from an order.
     */
    public function unverifyOrder(Order $order)
    {
        $order->verified_at = NULL;
        $order->verified_by = NULL;
        $order->status = 'New';
        $order->saveWithHistory('Verification was removed'); 

        $order->load('verifiedBy');
        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Save changes made to an order.
     */
    public function saveOrder(Request $r, Order $order) 
    {
        $fields = json_decode(json_encode($r->fields));
        foreach($fields as $name => $value) {
            if(in_array($name, $order->syncFields))
                $order->$name = $value;
        }
        
        // Make sure all of the latest data is in the model.
        $order->saveWithHistory();
        $order->refresh();

        $order->billing->update([
            'order_id' => $order->id,
            'first_name' => $fields->billing->first_name,
            'last_name' => $fields->billing->last_name,
            'company' => $fields->billing->company,
            'address1' => $fields->billing->address1,
            'address2' => $fields->billing->address2,
            'city' => $fields->billing->city,
            'state' => $fields->billing->state,
            'zip' => $fields->billing->zip,
            'phone' => $fields->billing->phone,
        ]);

        $updatedItems = json_decode(json_encode($fields->items));
        foreach($updatedItems as $item)
            $item->price = $item->customPrice;

        $data = json_decode(json_encode($fields));
        $result = OrderService::updateOrderItems($order, $updatedItems, $data);
        if(!$result->success)
        {
            return response()->json([
                'error' => $result->error
            ], 400);
        }

        // Add any additional payments.
        foreach($fields->payments as $payment)
        {
            // if(!$payment->id
        }
        
        return $this->order($order->id);
    }

    /** 
     * Save the problem with an order.
     * */
    public function saveProblem(Request $request, Order $order)
    {
        $order->problem_id = $request->problem_id;
        $order->status = 'Problem';
        $order->saveWithHistory('Order has a problem'); 

        // Send an email to the customer if requested.
        if($request->has('send_problem_email'))
        {
            Mail::to($order->email)->send(
                new OrderProblem($order, $order->problem)
            );
        }

        return $this->order($order->id);
    }

    /**
     * Save changes made to an order status.
     */
    public function updateOrderStatus(Request $r, Order $order) 
    {
        $prevStatus = $order->status;
        $order->status = $r->status;

        DB::beginTransaction();
        
        if($prevStatus == 'Problem' && in_array($r->status, ['Awaiting Fulfillment', 'In Shipping']))
        {
            $order->problem_id = NULL;
            $order->verified_at = date('Y-m-d H:i:s');
            $order->verified_by = Auth::user()->id;
            $order->saveWithHistory('Problem was resolved'); 

        }
        if($order->status == 'Cancelled' && $prevStatus != 'Cancelled')
        {
            OrderService::cancelOrder($order, $r->restock);
            
            $order->saveWithHistory('Order was cancelled'); 
        }
        else if($order->status == 'Completed' && !$order->completed_at)
        {
            $order->completed_at = date('Y-m-d H:i:s');
            $order->saveWithHistory('Order was completed'); 
        }
        else
        {
            $order->saveWithHistory();
        }

        DB::commit();
        
        $order->refresh();

        return $this->order($order->id);
    }

    /**
     * Show the main order drafts screen.
     */
    public function draftsView()
    {
        return view('admin.drafts')->with([
            'page' => 'drafts'
        ]);
    }

    /**
     * Show the screen for a new order draft.
     */
    public function newDraftView()
    {
        return view('admin.order')->with([
            'page' => 'drafts',
            'type' => 'draft'
        ]);
    }

    /**
     * Show the screen for a single order draft.
     */
    public function draftView(OrderDraft $draft)
    {
        if($draft->completed_at)
        {
            return redirect("admin/orders/$draft->order_id");
        }

        return view('admin.order')->with([
            'page' => 'drafts',
            'type' => 'draft',
            'draft' => $draft
        ]);
    }

    /**
     * Get a list of in progress draft orders.
     */
    public function drafts(OrderDraft $draft)
    {
        $drafts = OrderDraft::whereNull('completed_at')
            ->where('source', '=', 'Admin')
            ->with('user')
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            'drafts' => $drafts
        ]);
    }

    /**
     * Get customer groups.
     */
    public function customerGroups(OrderDraft $draft)
    {
        $groups = CustomerGroup::orderBy('name')->get();
        
        return response()->json([
            'groups' => $groups
        ]);
    }

    /**
     * Get details on a specific order draft.
     */
    public function draft(OrderDraft $draft)
    {
        $groups = CustomerGroup::orderBy('name')->get();

        $data = $draft->data;

        // Reload customer data in case it's changed.
        $customer = false;
        if(isset($data->customer->id))
        {
            $customer = Customer::where('id', $draft->data->customer->id)
                ->with('addresses')
                ->first();
            
            $customer->loadDealers();
        }

        // Get latest product information.
        foreach($data->items as $i => $item)
        {
            $p = Product::find($item->product->id);
            $item->product->available = $p->available;
            $item->product->name = $p->name;
        }

        $order = $data;
        $order->last_name = '';
        foreach($order->shipments as $shipment)
        {
            $shipment->address1 = $shipment->address->address1 ?? '';
            $shipment->last_name = $shipment->address->last_name ?? '';
            $shipment->city = $shipment->address->city ?? '';
            $shipment->state = $shipment->address->state ?? '';
            $shipment->zip = $shipment->address->zip ?? '';

            foreach($shipment->items as $item)
            {
                $item->sku = $order->items[$item->idx]->sku;
                $item->product = $order->items[$item->idx]->product;
            }
        }
        $verification = (object) RulesService::shippable($order);
        
        return response()->json([
            'draft' => $draft,
            'groups' => $groups,
            'customer' => $customer,
            'verification' => $verification
        ]);
    }

    /**
     * Get details on a specific order draft.
     */
    public function completeDraft(OrderDraft $draft)
    {
        $data = $draft->data;
        for($i = 0; $i < count($data->items); $i++)
        {
            $item = $data->items[$i];
            $item->price = $item->customPrice;
            $data->items[$i] = $item;    
        }
        $draft->data = $data;

        $result = OrderService::completeDraft($draft);

        if($result->success)
        {
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
        else 
        {
            return response()->json([
                'error' => $result->error,
                'products' => $result->products ?? []
            ], 400);
        }
    }

    /**
     * Check if an ffl is required for a set of skus.
     */
    public function fflRequired(Request $r)
    {
        return response()->json([
            'ffl_required' => RulesService::requireFFL($r->skus)
        ]);
    }

    public function shipments(Request $r)
    {
        $shipments = [];
        // $shipments = OrderService::splitShipments($r->items, $r->subtotal);
        
        return response()->json([
            'shipments' => $shipments
        ]);
    }

    /**
     * Save changes made to a draft order.
     */
    public function saveDraft(Request $r, $id = false) 
    {
        $draft = $id ? OrderDraft::find($id) : new OrderDraft;
        $draft->source = 'Admin';
        $draft->user_id = Auth::user()->id;
        $draft->data = $r->all();
        $draft->save();

        if($r->holdInventory)
        {
            $failed = InventoryService::draftHold($draft->data->items, $draft->id);
            if(count($failed) > 0) {
                return response()->json([
                    'error' => 'Unable to hold inventory',
                    'details' => $failed
                ], 400);
            }
        }
        else {
            InventoryService::removeDraftHold($draft->id);
        }

        return $this->draft($draft);
    }

    /**
     * Capture a payment for an order.
     */
    public function capturePayment(Order $order, $paymentId)
    {
        $payment = OrderPayment::where('id', $paymentId)
            ->where('order_id', $order->id)
            ->first();

        $amount = $payment->amount < $order->total ? $payment->amount : $order->total;

        $api = new AuthorizeApi;
        $response = $api->capturePayment($payment->transaction_id, $amount);
        if($response->success) 
        {
            $payment->captured_amount = $amount;
            $payment->captured_at = date('Y-m-d H:i:s');
            $payment->save();
            $order->saveWithHistory("Payment was captured ($payment->transaction_id) for $$payment->captured_amount", '', 'System', false, true);

            return response([
                'payment' => $payment,
                'timeline' => $order->timeline()
            ]);
        } 
        else 
        {
            $payment->capture_error = $response->error;
            $payment->save();
            $order->saveWithHistory("Error capturing payment ($payment->transaction_id)", $payment->capture_error, 'System', false, true);

            return response()->json([
                'error' => $payment->capture_error,
                'timeline' => $order->timeline()
            ], 400);
        }
    }

    /**
     * Delete a draft order.
     */
    public function deleteDraft($id) 
    {
        InventoryService::removeDraftHold($id);
        OrderDraft::find($id)->delete();
    }

    /**
     * Get the available rates for a shipment.
     */
    public function getShippingRates(Request $r)
    {
        $api = new EasyPostApi;
        $address = (object) $r->address;
        $name = $address->name ?? $address->first_name . ' ' . $address->last_name;
        $company = $address->company ?? '';

        $response = $api->createShipment('', [
            'name' => $name,
            'company' =>  $company,
            'street1' =>  $address->address1,
            'street2' =>  $address->address2 ?? '',
            'city' =>  $address->city,
            'state' =>  $address->state,
            'zip' =>  $address->zip
        ], $r->weight);

        // Get carriers that are supported.
        $carriers = Carrier::whereNotNull('mapping_to')
            ->pluck('mapping_to')->toArray();

        // Get the rate information to return to the
        // front end.
        $rates = [];
        foreach($response->rates as $rate)
        {
            $key = "$rate->carrier-$rate->service";
            if(in_array($key, $carriers))
            {
                $carrier = $rate->carrier;
                if($rate->carrier_account_id == 'ca_065407af167041a48d5121cfd361c3c5')
                    $carrier = $carrier . '(GUN)';

                $rates[] = (object) [
                    'id' => $rate->id,
                    'carrier' => $carrier,
                    'service' => $rate->service,
                    'price' => $rate->rate * 1.12,
                    'days' => $rate->est_delivery_days,
                ];
            }
        }

        // Order rates by price.
        usort($rates, function($a, $b)
        {
            return $a->price > $b->price ? 1 : -1;
        });

        return response()->json([
            'rates' => $rates
        ]);
    }

    /**
     * Get the tax amount for the order.
     */
    public function getTax(Request $r)
    {
        $taxCode = $r->taxable ? '' : 'G';
        $address = $r->address ? (object) $r->address : false;

        // Convert to an object to pass to the api.
        $items = json_decode(json_encode($r->items));
        foreach($items as $item)
            $item->price = $item->customPrice ?? $item->price;
            
        $api = new AvalaraApi;
        $tax = $api->getTaxEstimate($items, $taxCode, $r->shipping, $address);

        // Assign the tax values.
        return response()->json([
            'tax' => $tax
        ]);
    }

    /**
     * Get a list of products that match the the filter.
     */
    public function productLookup(Request $r)
    {
        $q = $r->q;
        $products = Product::where('search', 'like', "%$q%")
            ->take(25)
            ->select('id', 'name', 'sku',  'thumbnail', 'available')
            ->with('variants')
            ->orderBy('available', 'desc')
            ->get();

        // See if there are any discounted prices for the products.
        foreach($products as $product) 
        {
            $product->price = $product->getLowestPrice($r->group);
        }

        return response()->json([
            'products' => $products
        ]);
    }
}