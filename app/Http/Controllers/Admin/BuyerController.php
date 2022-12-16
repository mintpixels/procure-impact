<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use \Auth;

class BuyerController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'users');
        View::share('page', 'users');
    }

    public function buyers(Request $r)
    {
        $buyers = Buyer::orderBy('name');

        if($r->search)
        {
            $buyers->where('name', 'like', '%'.$r->search.'%');
        }

        return view('admin.buyers')->with([
            'buyers' => $buyers->get()
        ]);
    }

    public function showBuyer($id = false)
    {
        $buyer = Buyer::find($id);
        if(!$buyer) $buyer = new Buyer;

        return view('admin.buyer')->with([
            'buyer' => $buyer
        ]);
    }

    public function saveBuyer(Request $r, $id)
    {
        $buyer = Buyer::find($id);
        if(!$buyer) $buyer = new Buyer;

        $buyer->name = $r->name;
        $buyer->description = $r->description;
        $buyer->type = $r->type;
        $buyer->email = $r->email;

        $document = $r->file('document');
        if($document)
        {
            $filename = $document->getClientOriginalName();

            Storage::disk('public')->putFileAs(
                'uploads',
                $document,
                $filename
            );

            $buyer->document = $filename;
        }

        $buyer->save();

        return redirect("admin/buyers/$buyer->id")->with([
            'status' => 'The buyer has been saved'
        ]);
    }

    public function deleteBuyer($id)
    {
        Buyer::where('id', $id)->delete();
        
        return response()->json([]);
    }

}