<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\SearchService;
use \DB;

class Product extends WithHistory
{
    use SoftDeletes;

    protected $table = 'product';
    protected $casts = [
        'inventory' => 'object',
        // 'dimensions' => 'object',
        'prices' => 'object',
        'images' => 'array',
        // 'shipping' => 'object',
        'additional' => 'object',
        'options' => 'object'
    ];

    protected $createdMessage = 'Product was created';
    protected $updatedMessage = 'Product was updated';

    protected $historyFields = ['name', 'sku', 'price', 'upc', 'cost', 'location'];

    public $syncFields = [
        'name', 'description', 'sku', 'upc', 'price', 'cost',  'images', 'handle', 'additional'
    ];

    protected $cloneableRelations = ['tags', 'properties', 'type', 'categoryMap'];

    /**
     * Perform a deep clone of the entity.
     */
    public function clone() 
    {
        $clone = $this->replicate();
        if($clone->name)
            $clone->name .= ' - Copy';

        $clone->handle = NULL;
        $clone->published_at = NULL;
        $clone->save();

        foreach($this->tags as $field)
        {
            $dup = $field->replicate();
            $dup->entity_id = $clone->id;
            $dup->save();
        }

        foreach($this->properties as $field)
        {
            $dup = $field->replicate();
            $dup->product_id = $clone->id;
            $dup->save();
        }

        foreach($this->categoryMap as $field)
        {
            $dup = $field->replicate();
            $dup->product_id = $clone->id;
            $dup->save();
        }
        
        return $clone;
    }

    public static function getBySkus($skus) 
    {
        if(!is_iterable($skus)) 
        {
            if(Cache::has($skus)) {
                return Cache::get($skus);
            }

            $product = Product::where('sku', $skus)
                ->get();

            Cache::put($skus, $product, now()->addMinutes(30));
            return $product;
        }

        $products = [];
        foreach($skus as $sku)
        {
            $matches = Product::where('sku', $sku)
                ->get();

            foreach($matches as $product)
                $products[] = $product;
        }
        
        return $products;
    }

    public static function getBySku($sku) 
    {
        // if(Cache::has($sku)) {
        //     return Cache::get($sku);
        // }
        $product = Product::where('sku', $sku)->first();

        Cache::put($sku, $product, now()->addMinutes(30));

        return $product;
    }

    /**
     * Save tags for the product by removing existing tags
     * and replacing them with new ones.
     */
    public function replaceTags($tags)
    {
        EntityTag::where('entity_type', 'product')
            ->where('entity_id', $this->id)
            ->delete();

        $tags = array_unique($tags);
        foreach($tags as $tag) 
        {
            EntityTag::create([
                'entity_type' => 'product',
                'entity_id' => $this->id,
                'name' => $tag
            ]);
        }
    }

    /**
     * Save related products for the product by removing existing
     * and replacing them with new ones.
     */
    public function replaceRelated($related)
    {
        ProductRelated::where('product_id', $this->id)->delete();

        foreach($related as $r) 
        {
            ProductRelated::create([
                'product_id' => $this->id,
                'related_id' => $r['related_id']
            ]);
        }
    }

    /**
     * Save addon products for the product by removing existing
     * and replacing them with new ones.
     */
    public function replaceAddons($related)
    {
        // ProductAddon::where('product_id', $this->id)->delete();

        // foreach($related as $r) 
        // {
        //     ProductAddon::create([
        //         'product_id' => $this->id,
        //         'addon_id' => $r['addon_id']
        //     ]);
        // }
    }

    /**
     * Save categories for the product by removing existing categories
     * and replacing them with new ones.
     */
    public function replaceCategories($categories)
    {
        ProductCategory::where('product_id', $this->id)->delete();

        foreach($categories as $c) 
        {
            ProductCategory::create([
                'product_id' => $this->id,
                'category_id' => $c['id']
            ]);
        }
    }

    /**
     * Save the properties for product
     */
    public function saveProperties($properties)
    {
        $properties = json_decode(json_encode($properties));

        // Remove any properties that have been deleted.
        $ids = array_column($properties, 'id');
        foreach($this->properties as $p)
        {
            if(!in_array($p->id, $ids))
                $p->delete();
        }

        // Update or add any remaining properties.
        foreach($properties as $p)
        {   
            if(isset($p->id))
            {
                ProductProperty::where('id', $p->id)->update([
                    'value' => $p->value,
                    'pdp' => $p->pdp
                ]);
            }
            else
            {
                $property = Property::firstOrCreate(['name' => $p->property->name]);
                ProductProperty::create([
                    'product_id' => $this->id,
                    'property_id' => $property->id,
                    'value' => $p->value,
                    'pdp' => $p->pdp ?? false
                ]);
            }
        }
    }

    /**
     * Auto save search text whenever the entity is saved.
     */
    public function save($options = [])
    {
        $this->search = $this->getSearch();

        if(isset($this->images[0]))
        {
            // $this->thumbnail = ((object) $this->images[0])->x200;
        }

        // Make sure the product has a handle.
        if(!$this->handle)
        {
            $this->handle = Str::slug($this->name);
            $postfix = 2;
            while(Product::where('handle', $this->handle)->exists())
            {
                $this->handle = Str::slug($this->name) . "_$postfix";
                $postfix++;
            }
        }

        parent::save();

        // Update the search index for the product.
        // SearchService::addProduct($this->id);
    }
    
    /**
     * Build search text for the customer.
     */
    public function getSearch()
    {
        return $this->name . '.' .
            $this->sku . '.' . 
            $this->upc . '.' . 
            $this->phone . '.' .
            '(' . $this->location . ')';
    }

    /**
     * Set the product type.
     */
    public function setType($id, $name)
    {
        $this->type_id = $id ?? NULL;

        // If there is no id but there is a name, then
        // we are adding a new type.
        if(!$this->type_id && $name)
        {
            $type = ProductType::create(['name' => $name]);
            $this->type_id = $type->id;
        }
    }

    /**
     * Get the amount of inventory that is currently being held.
     */
    public function heldQuantity()
    {
        return InventoryHold::where('product_id', $this->id)->sum('quantity');
    }

    /**
     * Get the amount of inventory is part of an order that has not shipped.
     */
    public function commitedQuantity()
    {
    }

    public function clean()
    {
        $clean = new \stdClass;
        $clean->brand = $this->brand;
        $clean->description = $this->description;
        $clean->handle = $this->handle;
        $clean->id = $this->id;
        $clean->images = $this->images;
        $clean->name = $this->name;
        $clean->price = $this->price;
        $clean->lowest_price = $this->lowest_price;
        $clean->prices = $this->prices;
        unset($clean->prices->additional);
        $clean->qty_prices = $this->qty_prices;
        $clean->properties = $this->properties;
        $clean->review_count = $this->review_count;
        $clean->review_score = $this->review_score;
        $clean->sku = $this->sku;
        $clean->thumbnail = $this->thumbnail;
        $clean->tags = []; //$this->tagArray();
        $clean->options = $this->options;
        $clean->price_in_cart = $this->additional->price_in_cart ?? false;
        $clean->variants = $this->variants;

        $properties = [];
        foreach($this->properties as $property)
        {
            $properties[] = (object) [
                'name' => $property->property->name,
                'value' => $property->value,
                'pdp' => $property->pdp
            ];
        }
        $clean->properties = $properties;

        return $clean;
    }

    //------------------------------------------------------------

    /**
     * Get a list of all product tags.
     */
    public static function allTags()
    {
        return EntityTag::where('entity_type', 'product')
            ->orderBy('name')
            ->distinct()
            ->pluck('name')->toArray();
    }

    //------------------------------------------------------------

    public function categoryMap()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function categories()
    {
        $categories = [];
        foreach($this->categoryMap as $map) 
        {
            $category = $map->category;
        
            while($category) 
            {
                $categories[] = $category;
                $category = $category->parent;
            }
        }

        return $categories;
    }

    public function inventoryAdjustments()
    {
        return $this->hasMany(InventoryAdjustment::class)
            ->orderBy('id', 'DESC')
            ->with('user');
    }

    public function tags()
    {
        return $this->hasMany(EntityTag::class, 'entity_id', 'id')->where('entity_type', 'product')->orderBy('name');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function properties()
    {
        return $this->hasMany(ProductProperty::class, 'product_id', 'id')->orderBy('position');
    }

    public function type()
    {
        return $this->hasOne(ProductType::class, 'id', 'type_id');
    }

    public function tagArray()
    {
        return EntityTag::where('entity_type', 'product')
            ->where('entity_id', $this->id)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function related()
    {
        return $this->hasMany(ProductRelated::class);
    }

    public function addons()
    {
        // return $this->hasMany(ProductAddon::class);
    }

    public function priceRules()
    {
        return $this->hasMany(PriceRuleApplication::class, 'entity_id', 'id')
            ->where('entity_type', 'product');
    }

    public function allPriceRules()
    {
        $ids = [];
        foreach($this->categoryMap as $c)
            $ids[] = $c->category_id;

        $categoryRules = PriceRuleApplication::whereIn('entity_id', $ids)
            ->where('entity_type', 'category')
            ->get();

        $rules = [];
        
        foreach($this->priceRules as $rule)
            $rules[] = $rule->priceRule;

        foreach($categoryRules as $rule)
            $rules[] = $rule->priceRule;

        return $rules;
    }

    /**
     * Get the lowest price for the product for the given
     * customer group.
     */
    public function getLowestPrice($group = false, $quantity = 1)
    {
        return $this->variants[0]->price;
        
        // Default to the standard price as the lowest.
        $this->lowest_price = $this->price;

        // See if there is a sales price.
        if($this->prices->sale > 0 && $this->prices->sale < $this->lowest_price)
            $this->lowest_price = $this->prices->sale;
            
        $qtyPricing = [];
        foreach($this->prices->additional as $price)
        {
            if(!$price->group_id || $price->group_id == $group)
            {
                $qtyPrice = $qtyPricing[$price->quantity] ?? $this->lowest_price;
                
                if(isset($price->price) && $price->price < $qtyPrice)
                    $qtyPrice = $price->price;

                if(isset($price->percent))
                {
                    $percentPrice = $this->price * (100 - $price->percent) / 100;
                    if($percentPrice < $qtyPrice)
                        $qtyPrice = $percentPrice;
                }

                if(isset($price->cost_plus) && $price->cost_plus > 0 && $this->cost > 0)
                {
                    $costPlusPrice = $this->cost * (1 + $price->cost_plus/100);
                    if($costPlusPrice < $qtyPrice)
                        $qtyPrice = $costPlusPrice;
                }

                $qtyPricing[$price->quantity] = $qtyPrice;
            }
        };

        foreach($this->allPriceRules() as $rule)
        {
            if(!$rule->published_at)
                continue;

            if(!$rule->group_id || $rule->group_id == $group)
            {
                $qtyPrice = $qtyPricing[$rule->quantity] ?? $this->lowest_price;

                if(isset($rule->percent_off))
                {
                    $percentPrice = $this->price * (100 - $rule->percent_off) / 100;
                    if($percentPrice < $qtyPrice)
                        $qtyPrice = $percentPrice;
                }

                if(isset($rule->cost_plus) && $rule->cost_plus > 0 && $this->cost > 0)
                {
                    $costPlusPrice = $this->cost * (1 + $rule->cost_plus/100);
                    if($costPlusPrice < $qtyPrice)
                        $qtyPrice = $costPlusPrice;
                }

                $qtyPrice = round($qtyPrice * 100) / 100.0;
                $qtyPricing[$rule->quantity] = $qtyPrice;
            }
        }

        ksort($qtyPricing);
        $this->qty_prices = $qtyPricing;

        foreach($qtyPricing as $qty => $price)
        {
            if($qty <= $quantity && $price < $this->lowest_price)
                $this->lowest_price = $price;
        }

        return $this->lowest_price;
    }

    /**
     * Get the order items with this product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the holds currently active on the product.
     */
    public function holds()
    {
        return $this->hasMany(InventoryHold::class);
    }

    /**
     * Get information on the commited items for this order.
     */
    public function committed()
    {
        $orderIds = Order::whereNotIn('status', ['Completed', 'Cancelled'])
            ->where('id', '>', '1000000')
            ->pluck('id');

        $items = OrderItem::selectRaw('sum(quantity) as quantity, sum(line_price) as total, count(distinct order_id) as order_count, product_id')
            ->whereNull('deleted_at')
            ->where('product_id', $this->id)
            ->whereIn('order_id', $orderIds)
            ->groupBy('product_id')
            ->get();

        return $items[0] ?? false;
    }
}
