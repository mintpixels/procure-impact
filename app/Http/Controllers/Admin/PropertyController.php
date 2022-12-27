<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyValue;
use App\Models\ProductProperty;
use App\Models\SearchFilter;
use \DB;

class PropertyController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'products');
    }

    /**
     * Show the main properties page.
     */
    public function propertiesView()
    {
        return view('admin.properties')->with([
            'page' => 'properties'
        ]);
    }

    /**
     * Get the matching list of properties.
     */
    public function properties(Request $r)
    {
        $properties = Property::orderBy('name')
            ->with('values')
            ->withCount('products');
            
        if($r->search)
            $properties->where('name', 'like', '%'.$r->search.'%')->take(500);


        return response()->json([
            'properties' => $properties->get()
        ]);
    }

    /**
     * Save updates to the properties.
    */
    public function save(Request $r)
    {
        $properties = json_decode(json_encode($r->properties));
        DB::transaction(function() use ($properties) 
        {
            foreach($properties as $property)
            {
                $p = Property::find($property->id);
                if(!$p) 
                {
                    $p = new Property;
                    $p->save();
                }
    
                $p->filter = $property->filter;
                $p->pdp = $property->pdp;
                $p->name = $property->name;
                $p->save();
    
                if(isset($property->deleted) && $property->deleted)
                {
                    SearchFilter::where('property_id', $property->id)->delete();
                    ProductProperty::where('property_id', $property->id)->delete();
                    Property::where('id', $property->id)->delete();
                }
            }
        });

        return response()->json([]);
    }

    /**
     * 
     */
    public function saveValues(Request $r, Property $property)
    {
        DB::beginTransaction();

        $values = is_array($r->values) ? $r->values : [$r->values];
        
        // Find the values that have been deleted.
        PropertyValue::where('property_id', $property->id)->delete();

        // Add new values.
        $existing = PropertyValue::where('property_id', $property->id)->pluck('value')->toArray();
        $new = array_diff($values, $existing);
        foreach($new as $v)
        {
            PropertyValue::create([
                'property_id' => $property->id,
                'value' => $v
            ]);
        }

        DB::commit();

        $property->load('values');
        return response()->json([
            'property' => $property
        ]);
    }
}