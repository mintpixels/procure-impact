<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Search;
use App\Models\SearchFacet;
use App\Models\SearchFacetProduct;
use Nadar\Stemming\Stemm;

class SearchService
{
    /**
     * Add a product to the search index. If the product already exists then it
     * will update the information for the existing product.
     */
    public static function addProduct($id)
    {
        $product = Product::where('id', $id)->first();

        SearchService::add($product->sku, $product->id, NULL, 50, 'SKU', true);
        SearchService::add($product->upc, $product->id, NULL, 50, 'UPC', false);
        SearchService::add($product->name, $product->id, NULL, 30, 'Name', false);
        SearchService::add(strip_tags($product->description), $product->id, NULL, 10, 'Description' ,false);

        SearchFacetProduct::where('product_id', $product->id)->delete();
        if($product->published_at)
        {
            foreach($product->properties as $p)
            {
                // Include product properties in search.
                SearchService::add($p->value, $product->id, NULL, 10, 'Property', false);            

                // Also include each property as a facet.
                SearchService::addFacet($product->id, $p->property->name, $p->value);
            }
        }

        // if($product->brand)
        //     SearchService::addFacet($product->id, 'Brand', $product->brand);
    }

    /**
     * 
     */
    public static function add($text, $productId, $categoryId = NULL, $priority = 0, $field = NULL, $replace = true)
    {
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = preg_replace("/(?![.=$'€%-])/u", "", $text);
        $text = str_replace('&nbsp;', '', $text);
        $text = str_replace('&amp;', '', $text);
        $text = str_replace('&rsquo;', '', $text);
        $text = str_replace('&rdquo;', '', $text);
        $text = str_replace('&trade;', '', $text);
        $text = str_replace('&mdash;', '', $text);
        $stems = [];
        $terms = array_map('trim', explode(' ', $text));
        $terms = array_map('strtolower', $terms);
        // $terms = array_unique(array_merge($terms, $termsLC));

        foreach ($terms as $term) {
            $term = trim($term, " \n");
            if (strlen($term) >= 2 && strlen($term) < 30) {
                $stems[] = trim(Stemm::stem($term, 'en'), '.');
                $stems[] = trim($term, '.');
                $stems[] = trim($term, ',');

                $number = preg_replace('/[^0-9]/', '', $term);
                if(strlen($number) > 0)
                    $stems[] = $number;
            }
        }

        $stems = array_unique($stems);

        if($productId && $replace)
            Search::where('product_id', $productId)->delete();

        if($categoryId && $replace)
            Search::where('category_id', $categoryId)->delete();

        foreach ($stems as $stem) 
        {
            try 
            {
                $exists = Search::where('index', $stem)
                    ->where('product_id', $productId)
                    ->where('priority', $priority)
                    ->where('field', $field)
                    ->exists();

                if(!$exists)
                {

                    Search::create([
                        'index' => $stem,
                        'product_id' => $productId,
                        'category' => $categoryId,
                        'priority' => $priority,
                        'field' => $field
                    ]);
                }
            } 
            catch (\Exception $e) 
            {
                echo $e->getMessage() . "\n";
            }
        }
    }

    /**
     * 
     */
    public static function find($search)
    {
        $productIds = [];
        $stems = [];
        $terms = array_map('trim', explode(' ', $search));

        // Priorities
        // SKU
        // Exact match
        // Sales
        // Matched terms

        $indexes = Search::select('index')->distinct()->pluck('index')->toArray();

        foreach ($terms as $term) 
        {
            $stem = Stemm::stem($term, 'en');
            $smallest = 3;
            $t = $stem;
            
            // if (!in_array($stem, $indexes)) {
            //     foreach ($indexes as $index) {
            //         $d = levenshtein($stem, $index);
            //         if ($d < $smallest) {
            //             $smallest = $d;
            //             $t = $index;
            //         }
            //     }
            // }

            $stems[] = $t;
            $matches = Search::where('index', $t)
                ->select('product_id', 'priority', 'field')
                ->get();

            foreach($matches as $m)
            {
                if(!array_key_exists($m->product_id, $productIds)) {
                    $productIds[$m->product_id] = (object) [
                        'id' => $m->product_id,
                        'score' => 0,
                        'matches' => []
                    ];
                }

                $productIds[$m->product_id]->score += $m->priority;
                $productIds[$m->product_id]->matches[] = (object) [
                    'field' => $m->field,
                    'priority' => $m->priority,
                ];
            }
        }

        // Also include partial sku matches;
        // $products = Product::where('sku', 'like', $search . '%')->get();
        // foreach($products as $p) 
        // {
        //     if(!array_key_exists($p->id, $productIds)) {
        //         $productIds[$p->id] = (object) [
        //             'id' => $p->id,
        //             'score' => 50,
        //             'matches' => []
        //         ];
        //     }
        // }

        $ordered = [];
        foreach($productIds as $id => $m)
            $ordered[] = $m;

        usort($ordered, function($a, $b) {
            if($a->score > $b->score) return 1;
            if($a->score < $b->score) return -1;
            return 0;
        });

        return (object) [
            'matches' => $productIds,
            'results' => $ordered
        ];
    }

    /**
     * Add a new search facet for a product.
     */
    private static function addFacet($productId, $name, $value)
    {
        $facet = SearchFacet::where('name', $name)->first();
        if(!$facet)
        {
            SearchFacet::create([
                'name' => $name,
                'display_name' => $name
            ]);
        }

        SearchFacetProduct::create([
            'name' => $name,
            'value' => $value,
            'product_id' => $productId
        ]);
    }
}
