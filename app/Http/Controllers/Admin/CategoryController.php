<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Services\EasyPostApi;
use App\Models\InventoryLocation;
use App\Models\Category;
use App\Models\ProductCategory;
use App\Models\PriceRule;
use App\Models\PriceRuleApplication;
use App\Models\Property;
use App\Models\SearchFilter;
use DB;

class CategoryController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'products');
    }

    /**
     * Show the main categories page.
     */
    public function categoriesView()
    {
        return view('admin.categories')->with([
            'page' => 'categories'
        ]);
    }

    /**
     * Show the page for a specific category.
     */
    public function categoryView(Category $category)
    {
        return view('admin.category')->with([
            'page' => 'categories',
            'category' => $category
        ]);
    }

    /**
     * Get the matching list of categories.
     */
    public function categories()
    {
        $categories = Category::hierarchy(true);
        $properties = Property::orderBy('name')->get();

        return response()->json([
            'categories' => $categories,
            'properties' => $properties
        ]);
    }

    /**
     * Get the matching list of categories.
     */
    public function categoriesList(Request $r)
    {
        $categories = Category::withCount('productMap')
            ->orderBy('path')
            ->orderBy('name');

        if($r->search)
        {
            $categories->where('name', 'like', '%'.$r->search.'%')
             ->orWhere('path', 'like', '%'.$r->search.'%');
        }

        $priceRule = false;
        if($r->pricerule)
        {
            $priceRule = PriceRule::find($r->pricerule);

            $ids = PriceRuleApplication::where('price_rule_id', $r->pricerule)
                ->where('entity_type', 'category')
                ->pluck('entity_id');
                
            $categories->whereIn('id', $ids);
        }

        return response()->json([
            'categories' => $categories->get(),
            'priceRule' => $priceRule,
            'rules' => PriceRule::orderBy('name')->get(),
        ]);
    }

    /**
     * Get information on a specific category.
     */
    public function category($id)
    {
        $category = Category::find($id);

        return response()->json([
            'category' => $category
        ]);
    }

    /**
     * Get the search filters for the category
     */
    public function filters($id)
    {
        $filters = SearchFilter::where('category_id', $id)
            ->orderBy('position')
            ->get();

        return response()->json([
            'filters' => $filters
        ]);
    }

    /**
     * Get the search filters for the category
     */
    public function saveFilters(Request $r, $id)
    {
        $filters = $r->filters;
        DB::transaction(function() use ($id, $filters) 
        {
            SearchFilter::where('category_id', $id)->delete();
            foreach($filters as $filter)
            {
                SearchFilter::create([
                    'category_id' => $id,
                    'property_id' => $filter['id'],
                    'position' => $filter['position']
                ]);
            }
        });
        
        return response()->json([]);
    }

    /**
     * Save updates to the categories.
     */
    public function save(Request $r)
    {
        $categories = json_decode(json_encode($r->categories));
        DB::transaction(function() use ($categories) 
        {
            $this->updateHierarchy($categories);
        });

        return $this->categories();
    }

    /**
     * Update the category hierarchy.
     */
    private function updateHierarchy($categories, $parentId = NULL, $ancestorId = NULL)
    {
        foreach($categories as $i => $category)
        {
            $c = Category::find($category->id);
            if(!$c) 
            {
                $c = new Category;
                $c->save();
            }

            $c->name = $category->name;
            $c->handle = $category->handle ? $category->handle : $this->slugify($category->name);
            $c->parent_id = $parentId;
            $c->ancestor_id = $ancestorId;
            $c->sort_order = $i;
            $c->is_visible = $category->visible;
            $c->save();

            if(isset($category->deleted) && $category->deleted)
            {
                if($c->id)
                {
                    $deleteIds = Category::where('id', $c->id)
                        ->orWhere('parent_id', $c->id)
                        ->orWhere('ancestor_id', $c->id)
                        ->pluck('id');

                    ProductCategory::whereIn('category_id', $deleteIds)->delete();
                    Category::whereIn('id', $deleteIds)->delete();
                }
            }
            else {
                $c->save();
            }

            $this->updateHierarchy($category->children, $c->id, $parentId);
        }
    }

    /**
     * Make bulk updates to categories.
     */
    public function updateCategories(Request $r)
    {  
        if($r->rules)
        {
            foreach($r->categories as $id)
            {
                foreach($r->rules as $rule)
                {
                    if($r->mode == 'remove') 
                    {
                        PriceRuleApplication::where('price_rule_id', $rule['id'])
                            ->where('entity_type', 'category')
                            ->where('entity_id', $id)
                            ->delete();
                    }
                    else {
                        PriceRuleApplication::firstOrCreate([
                            'price_rule_id' => $rule['id'],
                            'entity_type' => 'category',
                            'entity_id' => $id
                        ]);
                    }
                }
            }
        }

        return response()->json([]);
    }

    private function slugify($string){
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }
}