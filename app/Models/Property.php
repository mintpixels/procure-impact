<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ProductPropery;

class Property extends Model
{
    protected $table = 'property';
    protected $fillable = ['name'];

    /**
     * Get values by property;
     */
    public static function values()
    {
        $values = [];
        $pps = ProductProperty::select('property_id', 'value')->groupBy('property_id', 'value')->get();
        foreach($pps as $pp)
        {
            if(!array_key_exists($pp->property_id, $values))
                $values[$pp->property_id] = [];

            $values[$pp->property_id][] = $pp->value;
        }

        foreach($values as $id => $value)
        {
            $values[$id] = array_unique($values[$id]);
            sort($values[$id]);
        }
        
        return $values;
    }

    public function products()
    {
        return $this->hasMany(ProductProperty::class);
    }
}
