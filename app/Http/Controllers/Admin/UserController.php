<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Brand;
use \Auth;

class UserController extends Controller
{
    /**
     * Indicate the active section.
     */
    public function __construct()
    {
        View::share('section', 'users');
        View::share('page', 'users');
    }

    /**
     * Show the existing users.
     */
    public function users(Request $request)
    {
        $users = User::orderBy('email');

        if($request->search)
        {
            $users->where('email', 'like', '%'.$request->search.'%')
                ->orWhere('name', 'like', '%'.$request->search.'%');
        }

        return view('admin.users')->with([
            'users' => $users->get()
        ]);
    }

    /**
     * Show the existing users.
     */
    public function showUser($id = false)
    {
        $user = User::find($id);
        if(!$user) $user = new User;

        $roles = []; // Role::orderBy('position')->get();
        $brands = Brand::orderBy('name')->get();

        return view('admin.user')->with([
            'user' => $user,
            'roles' => $roles,
            'brands' => $brands
        ]);
    }

    /** 
     * Save changes to a user.
     */
    public function saveUser(Request $request, $id)
    {
        $user = User::find($id);
        if(!$user) $user = new User;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->type = $request->type;
        $user->brand_id = $request->brand_id ?: NULL;

        // Only update the password if one was set.
        if($request->password)
            $user->password = \Hash::make($request->password);

        // Get the assigned user roles.
        $roles = $request->roles ?? [];
        $user->roles = implode(',', $roles);
        $user->save();

        return redirect("admin/users/$user->id")->with([
            'status' => 'The user has been saved'
        ]);
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        User::where('id', $id)->delete();
        
        return response()->json([]);
    }
}