<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDevSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if ((bool) $request->session()->get('dev_superadmin_authenticated')) {
            return $next($request);
        }

        return redirect()->route('superadmin.login');
    }
}
