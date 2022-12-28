<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Services\SearchService;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Property;
use App\Models\Brand;
use App\Models\SearchFilter;
use App\Models\SearchFacet;
use App\Models\SearchFacetProduct;
use App\Models\SearchFeatured;
use \DB;

class SearchController extends Controller
{
    public function home()
    {
        return view('store.search');
    }

    /**
     * Return product and category suggestions based on the query term.
     */
    public function suggestions(Request $request)
    {
        $key = $request->q;
        if(Cache::has($key)) {
        //    return Cache::get($key);
        }

        $products = Product::where(function($q) use ($request) {
            $q->where('name', 'like', '%' . $request->q . "%")
              ->orWhere('sku', 'like', '%' . $request->q . '%');
            })
            ->whereNotNull('published_at')
            ->whereNotNull('thumbnail')
            ->select('id', 'name', 'description', 'thumbnail', 'price', 'prices', 'additional', 'handle', 'available')
            ->orderBy('available', 'DESC')
            ->orderBy('total_sold')
            ->take(10)
            ->get();

        foreach($products as $product)
        {
            $product->available = $product->available > 0;
            $product->price_in_cart = $product->additional->price_in_cart ?? false;
        }    

        $categories = Category::where('name', 'like', '%'. $request->q . "%")
            ->where('is_visible', 1)
            ->with('parent')
            ->select('name', 'handle', 'parent_id', 'path')
            ->orderBy('name')
            ->take(3)
            ->get();

        $cached = json_encode([
            'products' => $products,
            'categories' => $categories
        ]);

        Cache::put($key, $cached, now()->addMinutes(5));

        return $cached;
    }

    public function search(Request $request)
    {
        if($request->category == 'null')
            $request->category = '';

        if($request->mode == 'category')
        {
            return $this->categoryResults($request->category, $request->g, $request->filter, $request->sort_by, $request->page, $request->pagesize);
        }

        if($request->mode == 'brand')
        {
            return $this->brandResults($request->brand, $request->g, $request->filter, $request->sort_by, $request->page, $request->pagesize);
        }

        // Check cache.
        $key = "$request->qs.$request->category.$request->g.$request->filter.$request->sort_by.$request->page,$request->pagesize";
        if(Cache::has($key)) {
           return Cache::get($key);
        }

        $products = [];
        $matches = [];
        $facetFilters = [];
        $featured = false;
        $categories = [];

        if($request->qs)
        {
            $results = SearchService::find($request->qs);
            $matches = $results->matches;

            $ids = [];
            foreach($matches as $id => $m)
                $ids[] = $id;

            $products = Product::whereIn('id', $ids)
                ->whereNotNull('published_at');

            // Get sub categories.
            $categoryId = 0;
            if($request->category)
            {
                $categoryId = $request->category;
                
                $descendents = Category::where('parent_id', $categoryId)
                    ->orwhere('ancestor_id', $categoryId)
                    ->pluck('id')
                    ->toArray();

                $descendents[] = $categoryId;

                $productIds = ProductCategory::whereIn('category_id', $descendents)
                    ->pluck('product_id')
                    ->toArray();

                $products->whereIn('id', $productIds);
            }

            $products = $products->get();
        }

        $results =  $this->buildResults($matches, $products, $categoryId, $request->g, $request->filter, $request->sort_by, $request->page, $request->pagesize);
        
        $cached = json_encode($results);
        Cache::put($key, $cached, now()->addMinutes(5));

        return $cached;
    }

    private function buildResults($matches, $products, $categoryId = '', $group = '', $filter = '', $sortBy = '', $page = 1, $pageSize = 24, $brandId = '')
    {
        $facetFilters = [];
        $featured = false;
        $categories = [];

        if(!$page) $page = 1;

        if($filter) 
        {
            $filters = explode('@', $filter);
            foreach($filters as $f) 
            {
                $parts = explode(':', $f);
                $name = $parts[0];
                $values = array_map('strtolower', explode(',', $parts[1]));
                $facetFilters[$name] = $values;
            }
        }

        if($group)
        {
            foreach($products as $product)
            {
                $prices = ProductPrice::where('product_id', $product->id)
                    ->where('group_id', $group)
                    ->get();

                $lowest = $product->price;
                foreach($prices as $price)
                {
                    if($price->price < $lowest) {
                        $lowest = $price->price;
                    }
                }

                $product->price = $lowest;
            }
        }

        $results = [];
        $productIds = [];
        $productArray = [];
        foreach($products as $p)
        {
            if(count($facetFilters) > 0 && $p->properties)
            {
                foreach($p->properties as $prop) {
                    // continue;
                    if(array_key_exists($prop->property->name, $facetFilters))
                    {
                        // echo $prop->property->name ." : $prop->value\n";
                        $val = strtolower(trim($prop->value));
                        if(!in_array($val, $facetFilters[$prop->property->name]))
                            continue;
                    }
                }

                foreach($facetFilters as $key => $values) {
                    foreach($p->properties as $prop) {
                        if($key == trim($prop->property->name))
                            continue 2;
                    }

                    if($key == 'In Stock')
                    {
                        if($p->available > 0)
                            continue;
                    }
                    
                    continue 2;
                }
            }

            $productArray[] = $p;
        }
        
        foreach($products as $p) {
            $productIds[] = $p->id;
        }
        $facetedProductIds = [];
        foreach($productArray as $p)
        {  
            $facetedProductIds[] = $p->id;

            $results[] = (object) [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'thumbnail' => $p->thumbnail,
                'price' => $p->price,
                'prices' => $p->prices,
                'price_in_cart' => $p->additional->price_in_cart ?? false,
                'total_sold' => $p->total_sold,
                'review_score' => $p->review_score,
                'review_count' => $p->review_count,
                'url' => "/products/$p->handle",
                'created_at' => $p->created_at,
                'available' => $p->available,
                'score' => $matches ? $matches[$p->id]->score : 0,
                'variants' => $p->variants,
                'brand' => $p->brand->name
            ];
        }

        if($sortBy) 
        {
            usort($results, function($a, $b) use ($sortBy)
            {
                if($a->available <= 0 && $b->available > 0) {
                    return 1;
                }

                if($b->available <= 0 && $a->available > 0) {
                    return -1;
                }

                if($sortBy == 'best')
                {
                    $v2 = $a->score;
                    $v1 = $b->score;

                    if($v1 == $v2) 
                    {
                        $v2 = $a->total_sold;
                        $v1 = $b->total_sold; 
                    }
                }
                else if($sortBy == 'popular') 
                {
                    $v2 = $a->total_sold;
                    $v1 = $b->total_sold;    
                }
                else if($sortBy == 'price_low_to_high') 
                {
                    $v1 = $a->price;
                    $v2 = $b->price;
                }
                else if($sortBy == 'price_high_to_low') 
                {
                    $v1 = $b->price;
                    $v2 = $a->price;
                }
                else if($sortBy == 'age_low_to_high') 
                {
                    $v1 = $b->created_at;
                    $v2 = $a->created_at;
                }
                else if($sortBy == 'age_high_to_low') 
                {
                    $v1 = $a->created_at;
                    $v2 = $b->created_at;
                }
                else {
                    return 0;
                }

                if($v1 < $v2) return -1;
                if($v1 > $v2) return 1;
                return 0;
            });
        }
        
        $filters = [];
        
        if($categoryId) 
        {
            $searchFilters = SearchFilter::where('category_id', $categoryId)
                ->orderBy('position')
                ->with('property')
                ->get();

            if(count($searchFilters) == 0) 
            {
                $entries = Category::where('id', $categoryId)
                    ->orderBy('parent_id', 'DESC')
                    ->get();

                foreach($entries as $entry)
                {
                    if(!$entry->parent_id && !$entry->ancestor_id)
                        continue;

                    $ancestorId = $entry->parent_id ?? $entry->ancestor_id;
                    $searchFilters = SearchFilter::where('category_id', $ancestorId)
                        ->orderBy('position')
                        ->with('property')
                        ->get();

                    if(count($searchFilters) > 0)
                        break;
                }
            }

            foreach($searchFilters as $f)
            {
                $filters[] = (object) [
                    'name' => $f->property->name,
                    'display_name' => $f->property->name,
                    'sort_by' => 'position'
                ];
            }
        }

        if(count($filters) == 0) 
        {
            $properties = Property::where('filter', 1)
                ->orderBy('name')
                ->get();

            foreach($properties as $p)
            {
                $filters[] = (object) [
                    'name' => $p->name,
                    'display_name' => $p->name,
                    'sort_by' => 'name'
                ];
            }
        }

        $productFacets = SearchFacetProduct::whereIn('product_id', $productIds)->get();
        $facetTotals = [];

        foreach($productFacets as $pf)
        {
            if(!in_array($pf->product_id, $facetedProductIds) && !array_key_exists($pf->name, $facetFilters))
                continue;

                
            $name = trim($pf->name);
            if(!array_key_exists($name, $facetTotals)) {
                $facetTotals[$name] = (object) [
                    'products' => 0,
                    'options' => []
                ];
            }
            $facetTotals[$name]->products++;

            $val = strtolower(trim($pf->value));
            if(!array_key_exists($val, $facetTotals[$name]->options)) {
                $facetTotals[$name]->options[$val] = (object) [
                    'name' => trim($pf->value),
                    'products' => 0,
                    'chosen' => false
                ];
            }

            if(array_key_exists(trim($name), $facetFilters) && in_array(trim($val), $facetFilters[$name]))
                $facetTotals[$name]->options[$val]->chosen = true;

            $facetTotals[$name]->options[$val]->products++;


        }

        $finalFacets = [];
        foreach($filters as $f)
        {
            $f->products = 0;
            $options = [];
            if(array_key_exists($f->name, $facetTotals)){
                $f->products = $facetTotals[$f->name]->products;
                foreach($facetTotals[$f->name]->options as $key => $o)
                {
                    $options[] = $o;
                }

                usort($options, function($a, $b) use ($f) {
                    // if($f->sort_by == 'name') {
                    //     if($a->name < $b->name) return -1;
                    //     if($a->name > $b->name) return 1;    
                    // }
                    // if($a->products > $b->products) return -1;
                    // if($a->products < $b->products) return 1;
                    if($a->name < $b->name) return -1;
                    if($a->name > $b->name) return 1;
                    return 0;
                });

                $f->options = $options;

                if(count($f->options) > 0 || $f->name == 'In Stock') {
                    $finalFacets[] = $f;
                }
            }
        }

        $category = Category::where('id', $categoryId)->first();

        $parents = [];
        $children = [];
        if($category)
        {
            $children = Category::where('parent_id', $category->id)
                ->where('is_visible', 1)
                ->select('id', 'name', 'handle', 'path')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $parents = [$category->parent_id];

            $ancestors = Category::where('id', $categoryId)
                ->where('is_visible', 1)
                ->whereNotNull('ancestor_id')
                ->pluck('ancestor_id')
                ->toArray();

            $parents = array_merge($parents, $ancestors);
            $parents = Category::whereIn('id', $parents)
                ->where('is_visible', 1)
                ->select('id', 'handle', 'name', 'path')
                ->get()
                ->toArray();
        }
        else {
            $children = Category::whereNull('parent_id')
                ->where('is_visible', 1)
                ->select('id', 'name', 'handle')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

        }

        $brand = false;
        if($brandId)
        {
            $brand = Brand::find($brandId);
        }

        // Get results per category
        $totals = ProductCategory::whereIn('product_id', $productIds)
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->get();

        foreach($children as $child)
        {
            $ids = Category::where('parent_id', $child->id)
                ->orWhere('ancestor_id', $child->id)
                ->pluck('id')->toArray();

            $ids[] = $child->id;

            $child->total = 0;
            foreach($totals as $total) {
                if(in_array($total->category_id, $ids))
                    $child->total += $total->total;
            }
        }

        $filteredChildren = [];
        foreach($children as $child)
        {
            if($child->total > 0)
                $filteredChildren[] = $child;
        }

        usort($filteredChildren, function($a, $b) 
        {
            if($a->total > $b->total) return -1;
            if($a->total < $b->total) return 1;
            return 0;
        });

        $pages = floor(count($results) / $pageSize);
        if(count($results) / $pageSize != 0)
            $pages++;
            
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'pages' => $pages,
            'featured' => $featured,
            'suggestions' => [],
            'products' => array_slice($results, ($page - 1) * $pageSize, $pageSize),
            'facets' => $finalFacets,
            'total' => count($results),
            'category' => [
                'parents' => $parents,
                'name' =>  $category ? $category->name : '',
                'children' => $filteredChildren
            ],
            'brand' => $brand
        ];
    }

    private function categoryResults($categoryId, $group, $filter, $sortBy, $page, $pageSize)
    {
        $key = "$categoryId.$group.$filter.$sortBy.$page.$pageSize";
        if(Cache::has($key)) {
            // return Cache::get($key);
        }

        // Get sub categories.
        $descendents = Category::where('parent_id', $categoryId)
            ->orwhere('ancestor_id', $categoryId)
            ->pluck('id')
            ->toArray();

        $descendents[] = $categoryId;

        $productIds = ProductCategory::whereIn('category_id', $descendents)
            ->pluck('product_id')
            ->toArray();

        $products = Product::whereIn('id', $productIds)
            ->whereNotNull('published_at')
            ->with('properties')
            ->with('variants')
            ->get();

        $results = $this->buildResults(false, $products, $categoryId, $group, $filter, $sortBy, $page, $pageSize);
        
        $cached = json_encode($results);
        Cache::put($key, $cached, now()->addMinutes(5));

        return $cached;
    }

    private function brandResults($brandId, $group, $filter, $sortBy, $page, $pageSize)
    {
        $key = "$brandId.$group.$filter.$sortBy.$page.$pageSize";
        if(Cache::has($key)) {
            // return Cache::get($key);
        }

        $products = Product::where('brand_id', $brandId)
            ->whereNotNull('published_at')
            ->with('properties')
            ->with('variants')
            ->get();

        $results = $this->buildResults(false, $products, '', $group, $filter, $sortBy, $page, $pageSize, $brandId);
        
        $cached = json_encode($results);
        Cache::put($key, $cached, now()->addMinutes(5));

        return $cached;
    }
}
