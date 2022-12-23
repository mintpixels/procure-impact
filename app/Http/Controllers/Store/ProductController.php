<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRelated;
use App\Models\Review;
use App\Models\ReviewFilter;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use \Auth;

class ProductController extends Controller
{
    /**
     * Show the PDP.
     */
    public function view(Product $product)
    {
        if(!$product->published_at && !Auth::user())
            abort(404);
            
        return view('store.pdp')->with([
            'product' => $product
        ]);
    }

    /**
     * Get information on a single product.
     */
    public function product(Product $product)
    {
        $related = [];

        $pc = ProductCategory::orderBy('category_id')->where('product_id', $product->id)->first();
        if($pc)
        {
            $products = ProductCategory::where('category_id', $pc->category_id)->pluck('product_id')->toArray();
            $secondary = Product::whereNotNull('published_at')
                ->where('available', '>', 0)
                ->whereNotNull('thumbnail')
                ->whereIn('id', $products)->take(6)->get();

            foreach($secondary as $p)
            {
                if(count($related) >= 6)
                    break;

                $related[] = $p->clean();
            }
        }
        
        // TODO: pull from max and min settings.
        $product->min_qty = 1;
        // $product->getLowestPrice($groupId);
        
        $clean = $product->clean();
        $clean->in_stock = $product->available ? true : false;

        $brandProducts = Product::where('id', '!=', $product->id)->where('brand_id', $product->brand_id)->with('variants')->with('brand')->take(6)->get();
        $related =  Product::where('id', '!=', $product->id)->where('brand_id', $product->brand_id)->with('variants')->with('brand')->take(4)->get();
        
        return response()->json([
            'product' => $clean,
            'related' => $related,
            'brand_products' => $brandProducts,
            'related' => $related,
            'brand' => $product->brand
        ]);
    }

    /** 
     * Get reviews for the product
     */
    public function reviews(Request $r, $id)
    {
        // Get the sum of all scores so we can calculate
        // the average.
        $sum = Review::where('product_id', $id)
            ->whereNotNull('published_at')
            ->sum('score');

        $page = $r->page ?? 0;
        
        $reviews = Review::where('product_id', $r->product_id)
            ->whereNotNull('published_at')
            ->whereNotNull('content');
            
        $total = $reviews->count();

        if($r->rating)
            $reviews->where('score', $r->rating);

        if($r->search)
            $reviews->where('search', 'like', '%' . $r->search . '%');

        if($r->images)
            $reviews->has('images', '>', 0);

        if($r->sort == 'popular')
            $reviews->orderByRaw('(upvotes - downvotes) DESC');

        $filteredTotal = $reviews->count();
        
        // Get the paginated results.
        $reviews = $reviews->offset($page * 5)
            ->whereNotNull('content')
            ->take(5)
            ->with('images')
            ->orderBy('published_at', 'DESC')
            ->select('name', 'title', 'content', 'score', 'published_at', 'id')
            ->get();

        return response()->json([
            'reviews' => $reviews->toArray(),
            'total' => $total,
            'filteredTotal' => $filteredTotal,
            'average' => $total > 0 ? $sum / $total : 0,
            'questions' => []
        ]);
    }

    /**
     * Add a new review for a product.
     */
    public function addReview(Request $r)
    {
        $review = Review::create([
            'product_id' => $r->product_id,
            'name' => $r->name,
            'email' => $r->email,
            'title' => $r->title,
            'content' => $r->content,
            'score' => $r->rating
        ]);

        $review->search = $review->getSearch();
        $review->save();

        // Check if the review has any flagged words.
        $filterMatch = $this->checkFilter($review);
        
        // Can we auto-publish?
        if(!$filterMatch && count($review->images) == 0 && $review->score >= 4)
        {
            $review->published_at = date('Y-m-d H:i:s');
            $review->save();
        }

        return response()->json([
            'review' => $review
        ]);
    }

      /**
     * Check if the review matches and filtered words.
     */
    private function checkFilter($review)
    {
        $filterText = $review->name . $review->title . $review->content;
        $filterWords = ReviewFilter::pluck('word')->toArray();

        foreach($filterWords as $w)
        {
            if(stripos($filterText, $w)) {
                return true;
            }
        }

        return false;
    }
}