<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\Buyer;

class CustomerController extends CrudController
{
    protected $model = '\App\Models\Customer';
    protected $fields = [
        'first_name', 'last_name'
    ];
    
    protected $listView = 'admin.customers';
    protected $singleView = 'admin.customer';
    protected $baseRoute = 'admin/customers';
    protected $entityName = 'customer';
    protected $searchFields = ['search'];
    protected $orderBy = 'id';
    protected $orderDirection = 'DESC';
    protected $pageSize = 50;

    /**
    * Indicate the active section.j
    */
    public function __construct()
    {
        View::share('section', 'customers');
        View::share('page', 'customers');
    }

    /**
    * Show the page to create a new customer.
    */
    public function createCustomerView()
    {
        return view('admin.customer')->with([
            'page' => 'customers',
            'customer' => false
        ]);
    }

    /**
    * Show the page for a specific customer.
    */
    public function customerView(Customer $customer)
    {
        return view('admin.customer')->with([
            'page' => 'customers',
            'customer' => $customer
        ]);
    }

    /**
    * Get the matching list of customers.
    */
    public function customers(Request $r)
    {
        $customers = Customer::orderBy('id', 'DESC')
            ->withCount('orders')
            ->take(50)->get();

        $buyers = Buyer::orderBy('name')->get();

        return response()->json([
            'customers' => $customers,
            'buyers' => $buyers
        ]);
    }

    /**
    * Get information on a specific customer.
    */
    public function customer(Customer $customer)
    {
        $groups = [];
        $buyers = Buyer::orderBy('name')->get();

        $customer->load('addresses');
        $customer->load('recentOrders');
        return response()->json([
            'customer' => $customer,
            'groups' => $groups,
            'buyers' => $buyers,
            'metrics' => $customer->metrics()
        ]);
    }

    /**
     * Save changes made to a customer.
     */
    public function save(Request $r, $id = false) 
    {
        $customer = $id ? Customer::find($id) : new Customer;

        if(Customer::existsWithEmail($r->email, $id)) {
            return response()->json([
                'error' => "The email $r->email already exists."
            ], 400);
        }

        foreach($r->all() as $name => $value) {
            if(in_array($name, $customer->syncFields))
                $customer->$name = $value;
        }

        if($r->password)
            $customer->password = \Hash::make($r->password);
        
        $customer->save();

        // Save updates to addresses.
        $keepIds = [];
        foreach($r->addresses as $addy)
        {
            $addy = (object) $addy;
            $address = isset($addy->id) ? CustomerAddress::find($addy->id) : new CustomerAddress;
            $address->customer_id = $customer->id;
            $address->save();

            // Track the ids of the addresses we want to keep so we can remove
            // any addresses that were deleted.
            $keepIds[] = $address->id;
            
            $address->update([
                'first_name' => $addy->first_name,
                'last_name' => $addy->last_name,
                'company' => $addy->company,
                'address1' => $addy->address1,
                'address2' => $addy->address2,
                'city' => $addy->city,
                'state' => $addy->state,
                'zip' => $addy->zip,
                'phone' => $addy->phone ?? NULL,
            ]);
        }

        // Remove an addresses that were deleted.
        CustomerAddress::where('customer_id', $customer->id)
            ->whereNotIn('id', $keepIds)
            ->delete();


        return $this->customer($customer);
    }

    /**
     * Look up a customer based on the filter.
     */
    public function lookup(Request $r)
    {
        $words = explode(' ', trim($r->q));

        $limit = $r->limit ?? 10;
        // $customers = Customer::where('search', 'LIKE', '%'.$words[0].'%');
        $customers = Customer::where('search', 'LIKE', '%'.$r->q.'%');
     
        // for($i = 1; $i < count($words); $i++)
        //     $customers->orWhere('search', 'LIKE', '%'.$words[$i].'%');
            
        return response()->json([
            'customers' => $customers->take($limit)->get()
        ]);
    }

    /**
     * Get addresses for a customer.
     */
    public function addresses(Customer $customer)
    {
        return response()->json([
            'addresses' => $customer->addresses
        ]);
    }

    /**
     * Get metrics for a customer.
     */
    public function metrics(Customer $customer)
    {
        // $spent = Order::where('customer_id', $id)
    }

    /**
     * Show the page for customer groups.
     */
    public function groupsView(Request $r)
    {
        return view('admin.groups');
    }

    /**
     * Get the data for customer groups.
     */
    public function groups()
    {
        $groups = [];
        // CustomerGroup::orderBy('name')
        //     ->withCount('customers')
        //     ->get();

        return response()->json([
            'groups' => $groups
        ]);
    }

    /**
     * Check if the email exists for a customer.
     */
    public function emailExists(Request $r)
    {
        return response()->json([
            'exists' => Customer::where('email', $r->email)->exists()
        ]);
    }
}