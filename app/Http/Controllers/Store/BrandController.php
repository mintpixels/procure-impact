<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Services\TakeShapeApi;
use App\Models\Brand;
use App\Models\Product;
use \Auth;

class BrandController extends Controller
{
    /**
     * Show the PDP.
     */
    public function view(Request $r, $handle)
    {
        // if(!$product->published_at && !Auth::user())
        //     abort(404);

        $brand = Brand::where('handle', $handle)->first();
            
        return view('store.brand')->with([
            'handle' => $handle,
            'pageTitle' => $brand->name
        ]);
    }

    /**
     * Get information on a single brand.
     */
    public function brand($handle)
    {
        $ts = new TakeShapeApi;
        $response = $ts->getBrand($handle);

        $data = false;
        foreach($response->data->getMerchantBrandPagesList->items as $entry)
        {
            if($entry->slug == $handle)
                $data = $entry;

        }

        $brand = Brand::where('handle', $handle)->first();
        $brandProducts = Product::whereNotNull('published_at')->where('brand_id', $brand->id)->with('variants')->with('brand')->take(6)->get();

        return response()->json([
            'handle' => $handle,
            'logo' => $data->logo,
            'merchantName' => $data->merchantName,
            'missionStatement' => $data->missionStatement,
            'sections' => $data->sections,
            'brand_products' => $brandProducts
        ]);
    }
}