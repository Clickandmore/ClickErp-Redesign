<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SiteAuthMiddleware
{

    public function handle($request, Closure $next = null, $guard = null)
    {
        if (!Auth::guard('web')->check()) {
            $previousUrl = $request->headers->get('referer') ?? url()->previous();
            session(['url.intended' => $previousUrl]);

            toastr()->addError(helperTrans('ingaz.please_login_first'));

            if ($request->expectsJson()) {
                return response()->json([
                    'message'  => __('ingaz.please_login_first'),
                    'redirect' => route('site.login.index'),
                ], 401);
            }

            return redirect()->route('site.login.index');
        }

        return $next($request);

    }
}
