<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class AppApiLang
{
    public function handle(Request $request, Closure $next)
    {
        Config::set('auth.defaults.guard', 'api');

        $locale = strtolower((string) ($request->header('lang') ?: $request->header('Accept-Language', 'ar')));
        $locale = substr($locale, 0, 2);

        if (!in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
