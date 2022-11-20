<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use \Auth;

class AuthController extends Controller
{
    /**
     * Show the view to log into the admin.
     */
    public function loginView()
    {
        return view('admin.login');
    }

    /**
     * Attempt to log into the admin.
     */
    public function attemptLogin(Request $r)
    {
        $credentials = $r->only('email', 'password');

        if(Auth::attempt($credentials)) {
            return redirect('admin');
        }

        return redirect('admin/login')->with([
            'error' => 'Invalid email or password'
        ]);
    }

    /**
     * Logout a user.
     */
    public function logout()
    {
        Auth::logout();
        return redirect('admin');
    }
}