<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\RulesService;
use \Auth;

class Cart extends Model
{
    use SoftDeletes;

    protected $table = 'cart';

    public function __construct()
    {
        parent::__construct();
        $this->guid = bin2hex(random_bytes(16));
    }

    /**
     * Get the instance of the cart for this session.
     */
    public static function instance()
    {
        $guid = session('cart');
        if($guid)
        {
            $cart = Cart::where('guid', $guid)
                ->whereNull('completed_at')
                ->with('items.product.brand')
                ->with('items.variant')
                ->first();

            if($cart) 
            {
                // $cart->calculate();
                return $cart;
            }
        }

        // There isn't an existing cart so create a
        // new empty one.
        $cart = new Cart;
        $cart->save();
        $cart->load('items.product');
        $cart->load('items.variant');

        session(['cart' => $cart->guid]);
        return $cart;
    }

    /**
     * Calculate total amounts for the cart.
     */
    public function calculate()
    {
        $this->load('items.product.brand');

        $this->subtotal = 0;
        foreach($this->items as $item)
        {
            // Make sure we have the latest low price for the product.
            // $item->price = $item->product->getLowestPrice($groupId, $item->quantity);
            
            $item->line_price = $item->price * $item->quantity;
            $this->subtotal += $item->line_price;
        }

        $this->total = $this->subtotal;
    }

    /**
     * Auto calculate totals whenever the checkout is saved.
     */
    public function save($options = [])
    {
        $this->calculate();

        // Make sure a customer is assigned if logged in.
        // $customer = Auth::guard('customer')->user();
        // $this->customer_id = $customer->id ?? NULL;

        parent::save();
    }

    /**
     * Get the SKUs that are in the cart.
     */
    public function getSkus()
    {
        $skus = [];
        foreach($this->items as $item) {
            $skus[] = $item->product->sku;
        }

        return array_unique($skus);
    }

    /**
     * Get suggested products based on the contents of the cart.
     */
    public function getSuggestedProducts()
    {
        $suggested = [];
        
        $inCart = [];
        foreach($this->items as $item)
            $inCart[] = $item->product->id;

        try
        {
            foreach($this->items as $item)
            {
                foreach($item->product->addons as $suggestion)
                {
                    $product = $suggestion->product;
                    if($product->available > 0 && !in_array($product->id, $inCart))
                    {
                        $suggested[] = (object) [
                            'id' => $product->id,
                            'name' => $product->name,
                            'thumbnail' => $product->thumbnail,
                            'price' => $product->price,
                            'sku' => $product->sku,
                            'brand' => $product->brand,
                            'handle' => $product->handle,
                            'options' => $product->options && count($product->options) > 0
                        ];
                    }
                }
            }
        }
        catch(\Exception $e) {}

        usort($suggested, function($a, $b) {
            return $a->price > $b->price ? -1 : 1;
        });

        return array_slice($suggested, 0, 2);
    }

    //-------------------------------------------------------------------

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function checkout()
    {
        return $this->hasOne(Checkout::class, 'guid', 'guid');
    }
}
