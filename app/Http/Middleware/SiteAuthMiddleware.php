<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SiteAuthMiddleware
{

    public function handle($request, Closure $next = null, $guard = null)
    {

        if (Auth::guard('web')->check()) {
            return $next($request);
        }
        toastr()->addError(helperTrans('ingaz.please_login_first'));

        return redirect()->route("site.home.index");
    }
}
