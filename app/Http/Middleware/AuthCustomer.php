<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class AuthCustomer extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if(!auth()->check() && !auth()->guard('customer')->check()) {
            return redirect(route('store.login'));
        }
        return $next($request);
    }

}
