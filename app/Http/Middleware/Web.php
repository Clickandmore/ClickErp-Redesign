<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Web
{

    public function handle($request, Closure $next = null, $guard = null)
    {

        if (Auth::guard('web')->check()) {
            return $next($request);
        }
        toastr()->addInfo(helperTrans('web.please_login_first'));

        return redirect()->route("web.login");
    }
}
