<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use App\Services\Migration\BigCommerceApi;
use App\Http\Controllers\Controller;
use App\Services\EasyPostApi;
use App\Services\SearchService;
use App\Services\InventoryService;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductProperty;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\ProductRelated;
use App\Models\EntityTag;
use App\Models\PriceRule;
use App\Models\PriceRuleApplication;
use App\Models\ProductType;
use App\Models\Property;
use App\Models\History;
use App\Models\Tag;
use App\Models\Brand;
use \Auth;
use \DB;

class ProductController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'products');
    }

    /**
     * Show the main products page.
     */
    public function productsView()
    {
        return view('admin.products')->with([
            'page' => 'products'
        ]);
    }

    /**
     * Show the page for a specific product.
     */
    public function productView(Product $product)
    {
        return view('admin.product')->with([
            'page' => 'products',
            'product' => $product
        ]);
    }

    /**
    * Show the page to create a new product.
    */
    public function createProductView()
    {
        $product = new Product;
        $product->name = "New Product";
        $product->save();

        return redirect('/admin/products/' . $product->id);
    }

    /**
     * Get the matching list of products.
     */
    public function products(Request $r)
    {
        // See if there is a direct match based on sku.
        // $matches = $r->search ? Product::where('sku', $r->search)->withCount('categoryMap')->get() : false;
        // if($matches && count($matches) > 0) 
        // {
        //     return response()->json([
        //         'products' => $matches
        //     ]);
        // }

        // See if there is a direct match based on upc.
        // $matches = $r->search ? Product::where('upc', 'like', '%'.$r->search.'%')->get() : false;
        // $matches = $r->search ? Product::where('upc', $r->search)->withCount('categoryMap')->get() : false;
        // if($matches && count($matches) == 1) 
        // {
        //     return response()->json([
        //         'products' => $matches
        //     ]);
        // }

        // Support sku prefix search.
        $search = str_replace('*', '', $r->search);

        $products = Product::whereNull('archived_at')
            ->withCount('categoryMap')
            ->with('tags')
            ->with('variants')
            ->with('brand')
            ->take(50);

        if(!Auth::user()->isAdmin())
        {
            $products->where('brand_id', Auth::user()->brand_id);
        }
    
        
        $filtered = false;

        if($search || $r->category || $r->pricerule || $r->tag || $r->property)
            $filtered = true;

        if($search)
        {
            $words = array_map('trim', explode(' ', $search));
            foreach($words as $word)
                $products->where('search', 'like', '%' . $word . '%')->take(500);
        }

        $category = false;
        if($r->category)
        {
            $category = Category::find($r->category);

            $ids = ProductCategory::where('category_id', $r->category)->pluck('product_id');
            $products->whereIn('id', $ids)->take(750);
        }

        // $priceRule = false;
        // if($r->pricerule)
        // {
        //     $priceRule = PriceRule::find($r->pricerule);

        //     $ids = PriceRuleApplication::where('price_rule_id', $r->pricerule)
        //         ->where('entity_type', 'product')
        //         ->pluck('entity_id');
                
        //     $products->whereIn('id', $ids)->take(750);
        // }

        if($r->status)
        {
            if($r->status == 'Draft')
                $products->whereNull('published_at')->take(750);
            else
                $products->whereNotNull('published_at');
        }

        $property = false;
        if($r->property)
        {
            $property = Property::find($r->property);

            $properties = ProductProperty::where('property_id', $r->property);
            if($r->values)
                $properties->whereIn('value', explode('__', $r->values));
       
            $ids = $properties->pluck('product_id');
            $products->whereIn('id', $ids)->take(750);
        }

        if(!($search || $r->category) && $r->sortBy)
        {
            $products->orderBy($r->sortBy, $r->sortDir == -1 ? 'DESC' : 'ASC')->take(500);
        }
        else {
            $products->orderBy('id', 'DESC');
        }

        if($r->tag)
        {
            if($r->exclude)
            {
                $products->whereDoesntHave('tags', function ($query) use ($r) {
                    $query->where('name', $r->tag);
                });
            }
            else
            {
                $products->whereHas('tags', function ($query) use ($r) {
                    $query->where('name', $r->tag);
                });
            }
        }

        if($r->brand)
        {
            $products->where('brand_id', $r->brand);
        }

        $products = $products->get();

        $allTags = EntityTag::where('entity_type', 'product')
            ->orderBy('name')
            ->distinct()
            ->pluck('name')->toArray();

        $tags = $allTags;
        if($filtered) 
        {
            $tags = [];
            foreach($products as $product) {
                foreach($product->tags as $tag) {
                    $tags[] = $tag->name;
                }
            }
            sort($tags);
        }

        $categoryList = Category::withCount('productMap')
            ->orderBy('path')
            ->orderBy('name')
            ->get();

        return response()->json([
            'products' => $products,
            'category' => $category,
            'categories' => Category::hierarchy(true),
            'categoryList' =>  $categoryList,
            // 'priceRule' => $priceRule,
            // 'rules' => PriceRule::orderBy('name')->get(),
            'properties' => Property::orderBy('name')->get(),
            'tags' => array_unique($tags),
            'brands' => Brand::orderBy('name')->get(),
            'allTags' => $allTags,
            'property' => $property
        ]);
    }

    /**
     * Get information on a specific product.
     */
    public function product(Product $product)
    {
        // Load additional fields for the product.
        $product->load('type');
        $product->load('related.product');
        $product->load('variants');
        // $product->load('addons.product');
        // $product->load('priceRules.priceRule');
        $product->load('properties.property');
        $product->loadCount('orderItems');
        // $product->loadCount('categoryMap');

        // Get customer group information.
        $mapped = [];
        $categories = [];
        foreach($product->categoryMap as $c)
        {
            if($c->category) {
                $c->category->getBreadcrumb();
                $categories[] = $c->category;
            }
        }

        usort($categories, function($a, $b) {
            return strcmp($a->name, $b->name);
        });

        $product->categories = $categories;
        $product->tags = $product->tagArray();

        // Get the existing tags.
        $tags = Product::allTags();

        $orderIds = OrderItem::where('product_id', $product->id)->pluck('order_id')->toArray();
        $count = Order::whereIn('id', $orderIds)->count();
        $product->orders_count = $count;
        // $product->rules = $product->allPriceRules();

        return response()->json([
            'product' => $product,
            'groups' => [],
            'groupsMap' => $mapped,
            'productTypes' => ProductType::orderBy('name')->get(),
            'tags' => $tags,
            'committed' => $product->committed(),
            'timeline' => $product->timeline(),
            'adjustments' => []
        ]);
    }

    /**
     * Make bulk updates to products.
     */
    public function updateProducts(Request $r)
    {  
        if($r->tags)
        {
            if($r->mode == 'remove') 
            {
                EntityTag::where('entity_type', 'product')
                    ->whereIn('entity_id', $r->products)
                    ->whereIn('name', $r->tags)
                    ->delete();
            }
            else 
            {
                foreach($r->products as $id)
                {
                    foreach($r->tags as $tag)
                    {
                        EntityTag::firstOrCreate([
                            'entity_type' => 'product',
                            'entity_id' => $id,
                            'name' => $tag
                        ]);
                    }
                }
            }
        }
        else if($r->property)
        {
            if($r->mode == 'remove') 
            {
                ProductProperty::whereIn('product_id', $r->products)
                    ->where('property_id', $r->property)
                    ->delete();
            }
            else 
            {
                foreach($r->products as $id)
                {
                    $property = Property::find($r->property);
                    
                    $pp = ProductProperty::firstOrCreate([
                        'product_id' => $id,
                        'property_id' => $property->id
                    ]);

                    $pp->value = $r->value;
                    $pp->pdp = $property->pdp;
                    $pp->save();
                }
            }
        }
        else if($r->rules)
        {
            foreach($r->products as $id)
            {
                foreach($r->rules as $rule)
                {
                    if($r->mode == 'remove') 
                    {
                        PriceRuleApplication::where('price_rule_id', $rule['id'])
                            ->where('entity_type', 'product')
                            ->where('entity_id', $id)
                            ->delete();
                    }
                    else {
                        PriceRuleApplication::firstOrCreate([
                            'price_rule_id' => $rule['id'],
                            'entity_type' => 'product',
                            'entity_id' => $id
                        ]);
                    }
                }
            }
        }
        else if($r->categories)
        {
            foreach($r->products as $id)
            {
                foreach($r->categories as $category)
                {
                    if($r->mode == 'remove') 
                    {
                        ProductCategory::where('product_id', $id)
                            ->where('category_id', $category['id'])
                            ->delete();
                    }
                    else
                    {
                        ProductCategory::firstOrCreate([
                            'product_id' => $id,
                            'category_id' => $category['id'],
                        ]);
                    }
                }
            }
        }
        else if($r->changes)
        {
            // foreach($r->changes as $inv)
            // {
            //     InventoryService::adjust($inv['id'], $inv['update']);
            // }
        }

        return response()->json([]);
    }

    /**
     * Get category information to be used on the product page.
     */
    public function categories()
    {
        // Get the ancestor path for the categories.
        $categories = Category::orderBy('name')->with('parent')->get();
        foreach($categories as $c)
            $c->getBreadcrumb();

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Add an image to the product
     */
    public function addImages(Request $r, Product $product)
    {
        $images = [];
        
        foreach($r->images as $file)
        {
            $fileContents = file_get_contents($file->getRealPath());
            $basename = $product->id . '-' . uniqid();
            $path = sys_get_temp_dir() . '/' . $basename;
            file_put_contents($path, $fileContents);
            list($width, $height) = getimagesize($path);

            $ext = '';
            $type = exif_imagetype($path);
            if($type == IMAGETYPE_JPEG) $ext = '.jpg';
            else if($type == IMAGETYPE_PNG) $ext = '.png';
            else if($type == IMAGETYPE_GIF) $ext = '.gif';

            $imageObj = [];

            // Save the master image.
            $filename = $basename . $ext;
            if(Storage::disk('s3')->putFileAs('products', new File($path), $filename)) {
                $imageObj['master'] = $filename;
            }

            // Save the resized images. 
            $sizes = [1200, 400, 200, 40];
            foreach($sizes as $size)
            {
                $sizePath = $path . '_' . $size;

                if($type == IMAGETYPE_JPEG)
                {
                    $image = imagecreatefromjpeg($path);  
                    $scaled = $width > $size ? imagescale($image, $size) : $image;
                    imagejpeg($scaled, $sizePath);
                }
                else if($type == IMAGETYPE_PNG)
                {
                    $image = imagecreatefrompng($path);  
                    $scaled = $width > $size ? imagescale($image, $size) : $image;
                    imagepng($scaled, $sizePath);
                }
                else if($type == IMAGETYPE_GIF)
                {
                    $image = imagecreatefromgif($path);  
                    $scaled = $width > $size ? imagescale($image, $size) : $image;
                    imagegif($scaled, $sizePath);
                }

                $filename = $basename . '_' . $size . $ext;
                if(Storage::disk('s3')->putFileAs('products', new File($sizePath), $filename)) {
                    $imageObj["x$size"] = $filename;
                }
                unlink($sizePath);
            }

            $images[] = $imageObj;
            unlink($path);
        }

        return response()->json([
            'images' => $images
        ]);
    }

    /**
     * Create a new product.
     */
    public function create(Request $r) 
    {
        $product = new Product;
        return $this->save($r, $product);
    }

    /**
     * Save changes made to a product.
     */
    public function save(Request $r, Product $product) 
    {
        // Make sure inventory was initialized.
        // if(!$product->inventory)
        // {
        //     $product->inventory = (object) [
        //         'warehouse' => 0, 
        //         'showroom' => 0, 
        //         'hold' => 0
        //     ];
        // }

        // if($r->inventoryAdjustment) {
        //     InventoryService::adjust($product->id, $r->inventoryAdjustment);
        // }
        
        $fields = (object) $r->fields;
        foreach($fields as $name => $value) {
            if(in_array($name, $product->syncFields))
                $product->$name = $value;
        }
        
        // Change status to timestamp for the product state.
        if($fields->status == 'draft')
            $product->published_at = NULL;
        else if(!$product->published_at)
            $product->published_at = date("Y-m-d H:i:s");

        // Save additional product fields.
        DB::beginTransaction();

        // Variants
        foreach($r->fields['variants'] as $v)
        {
            $variant = ProductVariant::find($v['id']);
            $variant->price = floatval($v['price']);
            $variant->wholesale_price = floatval($v['wholesale_price']);
            $variant->msrp = floatval($v['msrp']);
            $variant->case_quantity = intval($v['case_quantity']);
            $variant->save();
        }

        // Save the product type. Adding it if necessary.
        $product->setType(
            $fields->type['id'] ?? false, 
            $fields->type['name'] ?? false
        );
        
        $product->replaceTags($fields->tags);
        $product->replaceCategories($fields->categories);
        $product->replaceRelated($fields->related);
        // $product->replaceAddons($fields->addons);
        $product->saveProperties($fields->properties);
        $product->saveWithHistory();

        DB::commit();

        // Make sure all of the latest data is in the model.
        $product->refresh();

        return $this->product($product);
    }

    /**
     * Copy the current product.
     */
    public function copyProduct(Product $product)
    {
        $clone = $product->clone();

        return response()->json([
            'id' => $clone->id
        ]);
    }

    /**
     * Get a list of products that match the the filter.
     */
    public function lookup(Request $r)
    {
        $q = $r->q;
        $products = Product::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Get a list of existing properties.
     */
    public function properties(Request $r)
    {
        $q = $r->q;
        $properties = Property::with('values')->orderBy('name')->get();

        return response()->json([
            'properties' => $properties
        ]);
    }

    /**
     * Archive a product.
     */
    public function archive(Product $product)
    {
        $product->archived_at = date('Y-m-d H:i:s');
        $product->published_at = NULL;
        $product->save();
    }

    /**
     * Delete a product.
     */
    public function delete(Product $product)
    {
        DB::beginTransaction();

        // Delete relationships.
        ProductCategory::where('product_id', $product->id)->delete();
        ProductRelated::where('product_id', $product->id)->delete();
        ProductRelated::where('related_id', $product->id)->delete();
        ProductAddon::where('product_id', $product->id)->delete();
        ProductAddon::where('addon_id', $product->id)->delete();

        $product->delete();

        DB::commit();
    }

    public function purchaseOrderView()
    {
        return view('admin.purchase-orders')->with([
            'page' => 'purchaseorders'
        ]);
    }

    public function purchaseOrders()
    {
        
    }

    /**
     * Show the view for managing price rules.
     */
    public function priceRulesView()
    {
        return view('admin.pricerules')->with([
            'page' => 'pricerules'
        ]);
    }

    /**
     * Get the price rule data.
     */
    public function priceRules(Request $r)
    {
        $rules = PriceRule::orderBy('name')
            ->with('group')
            ->withCount('products')
            ->withCount('categories');

        if($r->search) 
            $rules->where('name', 'like', '%'.$r->search.'%');

        return response()->json([
            'rules' => $rules->get(),
            'groups' => []
        ]);
    }

    /**
     * Save changes to the price rules.
     */
    public function savePriceRules(Request $r)
    {
        DB::beginTransaction();

        foreach($r->changes as $change)
        {
            $change = (object) $change;
            
            $rule = isset($change->id) ? PriceRule::find($change->id) : new PriceRule;

            if($change->deleted) 
            {
                PriceRuleApplication::where('price_rule_id', $rule->id)->delete();
                $rule->delete();
                continue;
            }

             // Change status to timestamp for the product state.
             if($change->status == 'Draft')
                $rule->published_at = NULL;
              else if(!$rule->published_at)
                $rule->published_at = date("Y-m-d H:i:s");
                
            $rule->name = $change->name;
            $rule->group_id = $change->group_id ? $change->group_id : NULL;
            $rule->quantity = $change->quantity;
            $rule->percent_off = $change->percent_off;
            $rule->cost_plus = $change->cost_plus;
            $rule->save();
        }

        DB::commit();

        return response()->json();
    }
}