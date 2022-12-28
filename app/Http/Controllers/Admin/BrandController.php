<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use \Auth;

class BrandController extends Controller
{
    /**
     * Get a list of brands.
     */
    public function brands(Request $r)
    {
        $brands = Brand::orderBy('name');
        if($r->search)
        {
            $brands->where('name', 'like', '%'.$r->search.'%');
        }

        return view('admin.brands')->with([
            'brands' => $brands->get()
        ]);
    }

    /**
     * Show the view for a specific brand.
     */
    public function showBrand($id = false)
    {
        $brand = Brand::find($id);
        if(!$brand) $brand = new Brand;

        return view('admin.brand')->with([
            'brand' => $brand
        ]);
    }

    /**
     * Save changes to a brand.
     */
    public function saveBrand(Request $r, $id)
    {
        $brand = Brand::find($id);
        if(!$brand) $brand = new Brand;

        $brand->name = $r->name;
        $brand->description = $r->description;
        $brand->order_min = floatval($r->order_min);
        $brand->contact_email = $r->contact_email;
        $brand->contact_name = $r->contact_name;
        $brand->bill_id = $r->bill_id;
        $brand->handle = $r->handle;
        $brand->is_active = $r->is_active ? 1 : 0;
        $brand->save();

        return redirect("admin/brands/$brand->id")->with([
            'status' => 'The vendor has been saved'
        ]);
    }

    public function deleteBrand($id)
    {
        Brand::where('id', $id)->delete();
        
        return response()->json([]);
    }

}