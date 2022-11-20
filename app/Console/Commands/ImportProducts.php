<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchService;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductCategory;

function handleize($text)
{
    $text = str_replace(' ','-',$text);	
    $text = str_replace(',','-',$text);
    $text = str_replace("'",'',$text);
    $text = str_replace('+','-',$text);
    $text = str_replace('/','-',$text);
    $text = str_replace('.','-',$text);
    $text = str_replace('&','-',$text);
    $text = str_replace('--','-',$text);
    $text = str_replace('--','-',$text);
    $text = str_replace('--','-',$text);
    $text = str_replace('--','-',$text);
    $text = str_replace('--','-',$text);
    $text = str_replace('(','',$text);
    $text = str_replace('[','',$text);
    $text = str_replace(')','',$text);
    $text = str_replace(']','',$text);
    $text = str_replace('"','',$text);
    $text = strtolower($text);
    $text = trim($text);
    
    return $text;
}

class ImportProducts extends Command
{
    protected $signature = 'products:import';
    protected $description = '';

    public function handle()
    {
        $f = fopen('products_export_1.csv', 'r');
        $i = 0;
        $images = [];
        $products = [];
        $lastTitle = '';
        while($row = fgetcsv($f)) 
        {
            if($i++ == 0) continue;

            if($row[1] == '') continue;

            $type = $row[5];
            $title = $row[1];
            if(trim($title) == '')
            {
                $products[$type]->images[] =  $row[24];
                continue;
            }

            if($title != $lastTitle)
            {   
                if(!array_key_exists($type, $products))
                {
                    $products[$type] = (object)[
                        'brand_id' => 1,
                        'name' => $type,
                        'handle' => handleize($type),
                        'description' => $row[2],
                        'images' => [],
                        'thumbnail' => $row[24],
                        'type' => $type,
                        'variants' => []
                    ];
                }

                if(count($products[$type]->variants) < 5)
                {
                    $products[$type]->images[] = $row[24];

                    $products[$type]->variants[] = (object) [
                        'name' => $title,
                        'image' => count($products[$type]->images)-1,
                        'price' => floatval($row[19]),
                        'wholesale_price' => floatval($row[19]),
                        'retail_price' => floatval($row[19])
                    ];
                }

                $lastTitle = $title;
            }

    
        }


        foreach($products as $type => $p)
        {
            $product = new Product;
            $product->brand_id = 1;
            $product->name = $p->name;
            $product->handle = $p->handle;
            $product->description = $p->description;
            $product->thumbnail = $p->thumbnail;
            $product->images = $p->images;
            $product->type = $p->type;
            $product->published_at = date('Y-m-d H:i:s');
            $product->save();

            foreach($p->variants as $v)
            {
                $variant = new ProductVariant;
                $variant->name = $v->name;
                $variant->image = $v->image;
                $variant->product_id = $product->id;
                $variant->price = $v->price;
                $variant->wholesale_price = $v->price;
                $variant->retail_price = $v->price;
                $variant->save();
            }

            $pc = new ProductCategory;
            $pc->product_id = $product->id;
            $pc->category_id = 1;
            $pc->save();
        }
        // print_r($products);
    }
}
