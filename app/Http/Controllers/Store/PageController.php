<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Services\TakeShapeApi;
use \Auth;

class PageController extends Controller
{
    /**
     * Show the home page.
     */
    public function homeView()
    {
        return view('store.home');
    }

    /**
     * Get home page data.
     */
    public function home()
    {
        $ts = new TakeShapeApi;
        $response = $ts->getHomePage();

        return response()->json([
            'sections' => $response->data->getHomePage->sections
        ]);
    }

    /**
     * View the content of a page.
     */
    public function viewPage($handle)
    {
        $ts = new TakeShapeApi;
        $response = $ts->getPage($handle);

        if(count($response->data->getPageList->items) != 1)
            abort(404);
            
        $page = $response->data->getPageList->items[0];
        return view('store.page')->with([
            'page' => $page,
            'pageTitle' => $page->title
        ]);
    }

    /**
     * Get common page data.
     */
    public function common()
    {
        $ts = new TakeShapeApi;
        $footer = $ts->getFooter();
        $header = $ts->getHeader();

        return response()->json([
            'footer' => $footer->data->getfooter,
            'header' => $header->data->getHeader
        ]);
    }
}