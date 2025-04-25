<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class Admin
{

    public function handle($request, Closure $next = null, $guard = null)
    {
        Config::set('auth.defaults.guard', 'admin');
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }
        return redirect()->route("admin.form.login");
    }
}
