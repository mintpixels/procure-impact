<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Mail\PasswordReset;
use App\Mail\ReturnCreated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use \Auth;
use \DB;
use \Hash;

class AccountController extends Controller
{
    /**
     * Show the view for login.
     */
    public function loginView()
    {
        return view('store.account.login')->with([
            'noheader' => true
        ]);
    }

    /**
     * Attempt a login.
     */
    public function login(Request $r)
    {
        $credentials = $r->only('email', 'password');

        if(Auth::guard('customer')->attempt($credentials)) {
            return redirect('/');
        }

        return redirect()->back()->with([
            'loginError' => 'Invalid email / password combination'
        ]);
    }

    /**
     * Log out a customer.
     */
    public function logout()
    {
        Auth::guard('customer')->logout();
        return redirect('/account/login');
    }

    /**
     * Show the view to request a password reset.
     */
    public function forgotView()
    {
        return view('store.account.forgot');
    }

    /**
     * Show the view to reset a password.
     */
    public function resetView($token)
    {
        $customer = Customer::where('reset_token', $token)->first();
        if($customer)
        {
            return view('store.account.reset')->with([
                'customer' => $customer
            ]);
        }
        
        return redirect('account/login');
    }

    /**
     * Send an email to let the user reset their password.
     */
    public function sendReset(Request $r)
    {
        $customer = Customer::where('email', $r->email)->first();
        if($customer)
        {
            $customer->reset_token = uniqid();
            $customer->save();

            if(env('RC_MODE') == 'live') {
                Mail::to($customer->email)->send(new PasswordReset($customer->reset_token));
            }
            else {
                Mail::to('pi@ryanas.com')->send(new PasswordReset($customer->reset_token));
            }

            return redirect()->back()->with([
                'status' => "An email has been sent to $r->email"
            ]);
        }

        return redirect()->back()->with([
            'error' => 'No account was found with that email address'
        ]);
    }

    /**
     * Update a customer password.
     */
    public function reset(Request $r)
    {
        $customer = Customer::where('reset_token', $r->token)->first();
        if($customer && trim($r->password))
        {
            $customer->password = \Hash::make($r->password);
            $customer->reset_token = NULL;
            $customer->save();

            Auth::guard('customer')->loginUsingId($customer->id);
            return redirect('account');
        }

        return redirect()->back()->with([
            'error' => 'Please enter a valid password'
        ]);
    }

    /**
     * Show the view to register a new account.
     */
    public function registerView()
    {
        return view('store.account.register');
    }

    /**
     * Register a new account.
     */
    public function register(Request $r)
    {
        if(Customer::where('email', $r->email)->exists()) 
        {
            return redirect()->back()->with([
                'error' => "The email address $r->email is already in use."
            ]);
        }

        if($r->password != $r->password2)
        {
            return redirect()->back()->with([
                'error' => "The password and password confirmation must match."
            ]);
        }

        $customer = Customer::create([
            'first_name' => $r->first_name,
            'last_name' => $r->last_name,
            'email' => $r->email,
            'password' => \Hash::make($r->password)
        ]);

        Auth::guard('customer')->loginUsingId($customer->id);

        return redirect('/');
    }

    /**
     * Show the view for the customer orders.
     */
    public function ordersView()
    {
        return view('store.account.orders')->with([
            'page' => 'orders',
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Show the view for a specific order.
     */
    public function orderView($id)
    {
        return view('store.account.order')->with([
            'page' => 'order',
            'id' => $id,
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Show the view for the customer returns.
     */
    public function returnView(Order $order)
    {
        return view('store.account.order-return')->with([
            'page' => 'returns',
            'order' => $order
        ]);
    }

    /**
     * Show the view for the customer returns.
     */
    public function returnsView()
    {
        return view('store.account.returns')->with([
            'page' => 'returns',
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Show the view for the customer addresses.
     */
    public function addressesView()
    {
        return view('store.account.addresses')->with([
            'page' => 'addresses',
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Show the view for the customer wishlists.
     */
    public function wishlistsView()
    {
        return view('store.account.wishlists')->with([
            'page' => 'wishlists',
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Get wishlists for a customer.
     */
    public function wishlists()
    {
        $lists = Wishlist::where('customer_id', Auth::guard('customer')->user()->id)
            ->with('items.product')
            ->get();
        
        foreach($lists as $list)
        {
            foreach($list->items as $item)
            {
                $item->product->getLowestPrice();
            }
        }

        return response()->json([
            'wishlists' => $lists
        ]);
    }

    /**
     * Save a wishlist for a customer.
     */
    public function saveWishlist(Request $r)
    {
        $customer =  Auth::guard('customer')->user();
        if(!$customer && $r->name) abort(404);

        $wishlist = Wishlist::where('id', $r->id)
            ->where('customer_id', $customer->id)
            ->first();

        if(!$wishlist)
        {
            $wishlist = new Wishlist;
            $wishlist->customer_id = $customer->id;
            $wishlist->save();
        }

        $wishlist->name = $r->name;
        $wishlist->is_public = $r->is_public ? 1 : 0;
        $wishlist->save();

        return $this->wishlists();
    }

    /**
     * Delete a wishlist.
     */
    public function deleteWishlist(Wishlist $wishlist)
    {
        $customer = Auth::guard('customer')->user();
        if($customer->id == $wishlist->customer_id)
            $wishlist->delete();

        return $this->wishlists();
    }

    /**
     * Delete a wishlist.
     */
    public function deleteWishlistItem(Wishlist $wishlist, WishlistItem $item)
    {
        $customer = Auth::guard('customer')->user();
        if($customer->id == $wishlist->customer_id)
            $item->delete();

        return $this->wishlists();
    }

    /**
     * Update a wishlist for a customer.
     */
    public function updateWishlist(Request $r, Wishlist $wishlist)
    {
        $customer = Auth::guard('customer')->user();
        if($customer && $customer->id == $wishlist->customer_id)
        {
            $wishlist->name = $r->name;
            $wishlist->is_public = $r->is_public;
            $wishlist->save();
            
            return response()->json([
                'wishlist' => $wishlist
            ]);
        }

        return abort(404);
    }

    /**
     * Show the view for the customer settings.
     */
    public function settingsView()
    {
        return view('store.account.settings')->with([
            'page' => 'settings',
            'customer' => Auth::guard('customer')->user()
        ]);
    }

    /**
     * Update customer settings.
     */
    public function updateSettings(Request $r)
    {
        $validated = $r->validate([
            'email' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required'
        ]);

        $customer = Auth::guard('customer')->user();

        if($r->password)
        {
            if(!$r->current_password)
            {
                return redirect()->back()->with([
                    'error' => 'You must specify your current password to update your password.'
                ]); 
            }
            
            if(!Hash::check($r->current_password, $customer->password)) 
            {
                return redirect()->back()->with([
                    'error' => 'Your current password did not match.'
                ]);
            }
        }

        if($r->email != $customer->email && Customer::where('email', $r->email)->exists()) 
        {
            return redirect()->back()->with([
                'error' => "The email address $r->email is already in use."
            ]);
        }

        $customer->email = $r->email;
        $customer->first_name = $r->first_name;
        $customer->last_name = $r->last_name;
        $customer->company = $r->company;
        $customer->phone = $r->phone;

        if($r->password)
            $customer->password = Hash::make($r->password);

        $document = $r->file('document');
        if($document)
        {
            $filename = $document->getClientOriginalName();

            Storage::disk('public')->putFileAs(
                'uploads',
                $document,
                $filename
            );

            $customer->buyer->document = $filename;
            $customer->buyer->save();
        }
    

        $customer->save();

   
        return redirect()->back()->with([
            'status' => 'Your settings have been updated'
        ]);
    }

    /**
     * Return the orders information.
     */
    public function orders(Request $r)
    {
        $page = $r->page ?? 1;

        $customer = Auth::guard('customer')->user();

        $orders = Order::where('customer_id', $customer->id)
            ->with('items.product')
            ->with('shipments')
            ->with('billing')
            ->orderBy('id', 'desc');

        $total = $orders->count();

        $pageSize = 5;
        $pages =  ceil($total / $pageSize);
        $orders = $orders->take($pageSize)->offset($pageSize*($page-1))->get();
        
        return response()->json([
            'total' => $total,
            'orders' => $orders,
            'page' => $page,
            'pages' => $pages
        ]);
    }

     /**
     * Return the orders information.
     */
    public function order(Order $order)
    {
        $customer = Auth::guard('customer')->user();
        if($order->customer_id != $customer->id)
            return abort(404);

        $order->load('items.product');
        $order->load('shipments');
        $order->load('billing');
        
        return response()->json([
            'order' => $order
        ]);
    }

    /**
     * Return the items available for a return on an order
     */
    public function returnItems(Order $order)
    {
        $customer = Auth::guard('customer')->user();
        if($order->customer_id != $customer->id)
        {
            return response()->json([
                'items' => []
            ]);
        }

        $cutoff = date('Y-m-d', strtotime('- 45 days'));
    
        $items = [];
        foreach($order->items as $item)
        {
            // Check for products that can't be returned.
            $tags = $item->product->tagArray();
            if(in_array('NORETURN', $tags))
                continue;

            // Remove any items that have already had a return
            // create for them.
            $quantity = $item->quantity;
            $returnItem = OrderReturnItem::where('order_id', $order->id)
                ->where('item_id', $item->id)
                ->first();

            if($returnItem) 
                $quantity -= $returnItem->quantity;

            if($quantity > 0)
            {
                $items[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'price' => $item->price
                ];
            }
        }

        return response()->json([
            'items' => $items
        ]);
    }

    /**
     * Sumbit a return request.
     */
    public function submitReturn(Request $r, Order $order)
    {
        $customer = Auth::guard('customer')->user();
        if($order->customer_id != $customer->id)
        {
            return response()->json([]);
        }

        DB::beginTransaction();

        $return = new OrderReturn;
        $return->order_id = $order->id;
        $return->customer_id = $customer->id;
        $return->reason = $r->reason;
        $return->notes = $r->comments;
        $return->status = 'Requested';
        $return->saveWithHistory('Return was requested');

        foreach($r->items as $item)
        {
            $item = (object) $item;

            OrderReturnItem::create([
                'order_id' => $order->id,
                'return_id' => $return->id,
                'item_id' => $item->id,
                'status' => 'Requested',
                'product_id' => $item->product_id,
                'quantity' => $item->return_quantity
            ]);
        }

        DB::commit();

        $return->load('items');

        try
        {
            if(env('RC_MODE') == 'live') {
                Mail::to($customer->email)->send(new ReturnCreated($return));
            }
            else {
                Mail::to('aimtest@ryanas.com')->send(new ReturnCreated($return));
            }
        }
        catch(\Exception $e) {}

        return response()->json([
            'id' => $return->id
        ]);
    }
}