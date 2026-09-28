<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHourlyDriverAccess
{
    public function handle(Request $request, Closure $next, string $channel)
    {
        $guard = auth($channel === 'web' ? 'hourly_driver' : 'hourly_driver_api');
        $driver = $guard->user();
        $version = $channel === 'web'
            ? $request->session()->get('hourly_driver_version')
            : ($driver ? $guard->payload()->get('driver_version') : null);
        if (!$driver || !$driver->canLoginVia($channel) || (int) $version !== $driver->auth_version) {
            if ($channel === 'mobile') return response()->json(['message' => 'يرجى تسجيل الدخول بحساب سائق متاح على الجوال.'], 401);
            $guard->logout();
            $request->session()->forget(['hourly_driver_version', 'hourly_driver_impersonator']);
            return redirect()->route('hourly-driver.login');
        }
        return $next($request);
    }
}
