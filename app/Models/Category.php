<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'category';
    protected $fillable = ['id'];

    /**
     * Build the category hierarchy.
     */
    public static function hierarchy($includeAll = false)
    {
        $categories = Category::whereNull('parent_id')
            ->orderBy('sort_order')
            ->with('children.children.children')
            ->withCount('productMap')
            ->get(); 
            
        $hierarchy = [];
        foreach($categories as $category)
        {
            if($includeAll || $category->is_visible)
                $hierarchy[] = Category::build($category, $includeAll);
        }

        return $hierarchy;
    }

    /**
     * Recursively build a category and it's children into a hierarchy.
     */
    private static function build($category, $includeAll, $breadcrumb = [])
    {
        $c = (object) [
            'id' => $category->id,
            'name' => $category->name,
            'handle' => $category->handle,
            'path' => $category->path,
            'children' => [],
            'breadcrumb' => implode(' / ', $breadcrumb),
            'products' => $category->product_map_count,
            'nested' => 0,
            'visible' => $category->is_visible ? true : false
        ];

        $breadcrumb[] = $category->name;

        foreach($category->children as $child)
        {
            if($includeAll || $child->is_visible)
                $c->children[] = Category::build($child, $includeAll, $breadcrumb);
        }

        foreach($c->children as $child)
            $c->nested += $child->products;

        return $c;
    }

    /**
     * Get a breadcrumb string back to the original ancestor for
     * the category.
     */
    public function getBreadcrumb()
    {
        $ancestors = [];
        $category = $this;
        while($category->parent)
        {
            array_unshift($ancestors, $category->parent->name);
            $category = $category->parent;
        }

        $this->breadcrumb = implode(' > ', $ancestors);
    }

    /**
     * Add additional processing when saving.
     */
    public function save($options = [])
    {
        // Make sure the product has a handle.
        if(!$this->handle)
        {
            $this->handle = Str::slug($this->name);
            $postfix = 2;
            while(Category::where('handle', $this->handle)->exists())
            {
                $this->handle = Str::slug($this->name) . "_$postfix";
                $postfix++;
            }
        }
        
        // Update the path to this category.
        $this->load('parent');
        $parent = $this->parent;
        $paths = [];
        while($parent)
        {
            $paths[] = $parent->handle;
            $parent = $parent->parent;
        }
        $paths[] = $this->handle;
        $this->path = implode('/', $paths);

        parent::save();
    }

    //------------------------------------------------------------------------

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id')
            ->orderBy('sort_order')
            ->withCount('productMap');
    }

    public function productMap()
    {
        return $this->hasMany(ProductCategory::class);
    }
}
