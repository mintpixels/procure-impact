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
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'users');
        View::share('page', 'users');
    }

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

    public function showBrand($id = false)
    {
        $brand = Brand::find($id);
        if(!$brand) $brand = new Brand;

        return view('admin.brand')->with([
            'brand' => $brand
        ]);
    }

    public function saveBrand(Request $r, $id)
    {
        $brand = Brand::find($id);
        if(!$brand) $brand = new Brand;

        $brand->name = $r->name;
        $brand->description = $r->description;
        $brand->location = $r->location;
        $brand->email = $r->email;
        $brand->contact_name = $r->contact_name;
        $brand->save();

        return redirect("admin/brands/$brand->id")->with([
            'status' => 'The brand has been saved'
        ]);
    }

    public function deleteBrand($id)
    {
        Brand::where('id', $id)->delete();
        
        return response()->json([]);
    }

}