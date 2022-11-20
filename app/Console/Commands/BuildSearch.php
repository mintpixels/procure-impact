<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SearchService;
use App\Models\Product;

class BuildSearch extends Command
{
    protected $signature = 'search:build';
    protected $description = 'Build the search index';

    public function handle()
    {
        $products = Product::orderBy('id', 'DESC')->get();
        foreach($products as $product)
        {
            echo "$product->id\n";
            SearchService::addProduct($product->id);
        }
    }
}
