<?php

namespace App\Http\Middleware;

use Closure;

/** Session authentication for website endpoints that share the customer API actions. */
class HourlyWebsiteCustomer
{
    public function handle($request, Closure $next)
    {
        $user = auth('web')->user();
        if (!$user) {
            if ($request->expectsJson()) return response()->json(['message'=>'سجّل الدخول لإكمال الحجز.','login_url'=>route('site.login.index')],401);
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('site.login.index');
        }
        abort_if($user->is_blocked,403,'الحساب غير متاح للحجز.');
        // Only the authenticated session supplies the identity; no client-supplied user ID or API token.
        auth('api')->setUser($user);
        return $next($request);
    }
}
