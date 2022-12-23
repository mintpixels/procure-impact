<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Content;
use \Auth;

class SettingsController extends Controller
{
    /**
     * Show the view for a specific brand.
     */
    public function showSettings()
    {
        $settings = Setting::first();
        $content = Content::orderBy('position')->orderBy('name')->get();

        return view('admin.settings')->with([
            'settings' => $settings,
            'content' => $content
        ]);
    }

    public function saveSettings(Request $r)
    {
        $settings = Setting::first();
        $settings->buyer_fee = $r->buyer_fee;
        $settings->brand_fee = $r->brand_fee;
        $settings->order_email = $r->order_email;
        $settings->save();

        return redirect()->back()->with([
            'status' => 'The settings have been saved'
        ]);
    }

    public function saveContent(Request $r)
    {
        foreach($r->all() as $handle => $field)
        {
            Content::where('handle', $handle)->update([
                'content' => $field
            ]);
        }
        
        return redirect()->back()->with([
            'status' => 'The content updates have been saved'
        ]);
    }

    public function deleteBrand($id)
    {
        Brand::where('id', $id)->delete();
        
        return response()->json([]);
    }

}