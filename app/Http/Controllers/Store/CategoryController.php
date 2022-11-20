<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class CategoryController extends Controller
{
    /**
     * Show the category results.
     */
    public function view($handle, $part2 = '', $part3 = '', $part4 = '')
    {
        $parts = [$handle];
        if($part2) $parts[] = $part2;
        if($part3) $parts[] = $part3;
        if($part4) $parts[] = $part4;

        $path = implode('/', $parts);
        $category = Category::where('path', $path)->first();
        if($category && $category->is_visible) 
        {
            return view('store.category')->with([
                'category' => $category
            ]);
        }

        abort(404);
    }

    public function catchAll($handle, $part2 = '', $part3 = '')
    {
        $product = Product::where('handle', $handle)->first();
        if($product)
        {
            return redirect('products/' . $product->handle);
        }

        return $this->view($handle, $part2, $part3);
    }
}