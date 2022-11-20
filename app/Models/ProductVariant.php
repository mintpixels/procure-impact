<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \DB;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $table = 'product_variant';
    
    /**
     * Get the amount of inventory that is currently being held.
     */
    public function heldQuantity()
    {
        return InventoryHold::where('variant_id', $this->id)->sum('quantity');
    }
}
