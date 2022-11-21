<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Services\TakeShapeApi;
use App\Models\Brand;
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
            
        return view('store.brand')->with([
            'handle' => $handle
        ]);
    }

    /**
     * Get information on a single brand.
     */
    public function brand($handle)
    {
        exit;
        $ts = new TakeShapeApi;
        $response = $ts->getBrand($handle);

        $data = false;
        foreach($response->data->getMerchantBrandPagesList->items as $entry)
        {
            if($entry->slug == $handle)
                $data = $entry;

        }
        return response()->json([
            'handle' => $handle,
            'sections' => $data->sections
        ]);
    }
}