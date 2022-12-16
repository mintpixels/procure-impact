<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
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
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderBilling;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderPayment;
use App\Models\BrandPayment;
use App\Models\Product;
use App\Models\Carrier;
use App\Models\OrderDraft;
use \Auth;
use \DB;

class TransactionController extends Controller
{
    /**
     * Show the main transactions screen.
     */
    public function transactionsView()
    {
        return view('admin.transactions')->with([
            'counts' => (object) [
                'submitted' => Order::submitted()->count(),
                'approved' => Order::approved()->count(),
                'completed' => Order::completed()->count(),
                'waiting' => Order::waiting()->count()
            ]
        ]);
    }

    /**
     * Show the screen for a single order.
     */
    public function orderView(Order $order)
    {
        return view('admin.transaction')->with([
            'page' => 'orders',
            'type' => 'order',
            'order' => $order
        ]);
    }

    /**
     * Get the matching list of orders.
     */
    public function transactions(Request $r)
    {
        // See if there is a direct match based on order number.
        $match = $r->search ? Order::find($r->search) : false;
        if($match) 
        {
            $match->load('customer.buyer');
            $match->load('payments');
            $match->load('billing');
            $match->load('draft');
            $match->loadCount('items');
            return response()->json([
                'orders' => [$match]
            ]);
        }

        $page = $r->page ?? 0;

        $orders = Order::orderBy('id', 'DESC')
            ->with('customer.buyer')
            ->withCount('items')
            ->with('payments')
            ->with('brandPayments')
            ->with('billing')
            ->with('draft')
            ->take(250)
            ->offset(250*$page);

        if($r->search)
            $orders->where('search', 'like', '%'.$r->search.'%');


        $filter = $r->filter ?? 'All';
        if($filter) {
            $this->filterOrders($orders, $filter);
        }

        $orders = $orders->get();
        if(!Auth::user()->isAdmin())
        {
            foreach($orders as $order)
            {
                $items = [];
                $itemCount = 0;
                $itemTotal = 0;
                foreach($order->items as $item)
                {
                    if($item->brand_id == Auth::user()->brand_id)
                    {
                        $items[] = $item;
                        $itemCount++;
                        $itemTotal += $item->line_price;
                    }
                }

                $order->items = $items;
                $order->total = $itemTotal;
                $order->items_count = $itemCount;
            }
        }

        return response()->json([
            'orders' => $orders
        ]);
    }

    /**
     * Filter orders based on a condition.
     */
    private function filterOrders($orders, $filter)
    {
        if($filter == 'Submitted') {
            Order::submitted($orders);
        }
        else if($filter == 'Approved') {
            Order::approved($orders);
        }
        else if($filter == 'Completed') {
            Order::completed($orders);
        }
        else if($filter == 'Awaiting Fulfillment') {
            Order::waiting($orders);
        }
    }

    /**
     * Get information on a specific order.
     */
    public function order($id)
    {
        $order = Order::where('id', $id)
            ->with('customer.addresses')
            ->with('items.product.brand')
            ->with('items.variant')
            ->with('billing')
            ->with('buyer')
            ->with('payments')
            ->with('brandPayments.brand')
            ->with('brands')
            ->first();

        return response()->json([
            'order' => $order
        ]);
    }

    public function makePayment(Request $r, $id)
    {
        $payment = BrandPayment::where('order_id', $id)
            ->where('brand_id', $r->brand_id)
            ->first();

        $payment->paid = $r->net;
        $payment->fee = $r->fee;
        $payment->paid_at = date('Y-m-d H:i:s');
        $payment->save();
    }
}