<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Dealer;

class CartController extends Controller
{
    /**
     * Get the cart contents.
     */
    public function get()
    {
        $cart = Cart::instance();

        return response()->json([
            'cart' => $cart,
            'suggested' => [] //$cart->getSuggestedProducts()
        ]);
    }
    
    /**
     * Add a new item to the cart.
     */
    public function addItem(Request $r)
    {
        $cart = Cart::instance();
        // if($product->available < $r->quantity)
        // {
        //     return response()->json([
        //         'error' => "We don't have enough $product->name on hand for the quantity you selected."
        //     ], 400);
        // }

        // If there are properties on the item then add it
        // as a new item.
        // if($r->options && count($r->options) > 0)
        // {
        //     $item = CartItem::create([
        //         'cart_id' => $cart->id,
        //         'product_id' => $r->id,
        //         'variant_id' => $r->variantId
        //     ]);
        // }
        // else
        // {
        //     // If the item is already in the cart then we can re-use it.
        //     $item = CartItem::firstOrCreate([
        //         'cart_id' => $cart->id,
        //         'product_id' => $r->id,
        //         'variant_id' => $r->variantId
        //     ]);
        // }
        
        foreach($r->variants as $v)
        {
            $v = json_decode(json_encode($v));
            $product = Product::find($v->id);
            if(!$product)
            {
                return response()->json([
                    'error' => "Invalid product"
                ], 400);
            }

            
            $item = CartItem::firstOrCreate([
                'cart_id' => $cart->id,
                'product_id' => $v->id,
                'variant_id' => $v->variantId
            ]);

            $variant = ProductVariant::find($v->variantId);

            $item->guid = bin2hex(random_bytes(16));
            $item->base_price = $variant->price;
            $item->price = $variant->price;
            $item->quantity += intval($v->quantity);
            // $item->properties = $r->options;
            $item->save();
        }

        $cart->save();
        return $this->get();
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $r)
    {
        $cart = Cart::instance();
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $r->id)
            ->delete();
        
        $cart->save();
        return $this->get();
    }

    /**
     * Update an item in the cart.
     */
    public function updateItem(Request $r)
    {
        $cart = Cart::instance();
        $item = CartItem::where('guid', $r->id)->first();
        
        if($item)
        {
            // if($item->product->available < $r->quantity)
            // {
            //     return response()->json([
            //         'error' => "We don't have enough " . $item->product->name . " on hand for the quantity you selected."
            //     ], 400);
            // }

            $item->quantity = $r->quantity;
            $item->save();

            if($item->quantity <= 0)
                $item->delete();
        }
        
        $cart->save();
        return $this->get();
    }

    /**
     * Check if the cart can proceed to checkout.
     */
    public function checkout(Request $r)
    {
        $cart = Cart::instance();
        
        // foreach($cart->items as $item)
        // {
        //     if($item->product->available < $item->quantity)
        //     {
        //         return response()->json([
        //             'error' => "We don't have enough " . $item->product->name . " on hand for the quantity you selected."
        //         ], 400);
        //     }
        // }

        return $this->get();
    }

    /**
     * Clear the cart contents.
     */
    public function clear()
    {
        $cart = Cart::instance();
        CartItem::where('cart_id', $cart->id)->delete();

        $cart->save();
        return $this->get();
    }

    /**
     * Set a dealer for the order.
     */
    public function setDealer(Request $r)
    {
        $dealer = Dealer::where('id', $r->id)->first();
        if($dealer)
        {
            $cart = Cart::instance();
            $cart->dealer_id = $dealer->id;
            $cart->save();
        }

        return $this->get();
    }

    /**
     * Add a discount code to the cart.
     */
    public function addDiscount(Request $r)
    {
        $cart = Cart::instance();
        
        $discount = Discount::where('code', $r->code)->first();
        if($discount)
        {
            $cart->discount_id = $discount->id;
            $cart->save();
        }

        return $this->get();
    }

    /**
     * Add a discount code to the cart.
     */
    public function removeDiscount()
    {
        $cart = Cart::instance();
        $cart->discount_id = NULL;
        $cart->save();

        return $this->get();
    }

    /**
     * Recover an abandoned cart.
     */
    public function recoverCart($id)
    {
        $cart = Cart::where('guid', $id)->first();
        if($cart && !$cart->completed_at)
        {
            $cart->recovered_at = date('Y-m-d H:i:s');
            $cart->save();
        }

        return redirect('/#cart');
    }
}