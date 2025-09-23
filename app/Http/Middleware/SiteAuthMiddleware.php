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
        if ($request->has('redirect_url')) {
            session(['url.intended' => $request->input('redirect_url')]);
        } else {
            session(['url.intended' => $request->fullUrl()]);
        }
        toastr()->addError(helperTrans('ingaz.please_login_first'));

        return redirect()->route("site.login.index");
    }
}
