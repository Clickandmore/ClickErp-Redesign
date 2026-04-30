<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class lang
{
    public function handle($request, Closure $next)
    {
        Config::set('auth.defaults.guard', 'api');

        // Check if language is set in URL
        $locale = $request->segment(1);
        if (in_array($locale, ['ar', 'en'])) {
            app()->setLocale($locale);
            Session::put('locale', $locale);
            Cookie::queue('locale', $locale, 60 * 24 * 30); // 30 days
        } else {
            // If no language in URL, check session or cookie
            $locale = Session::get('locale', Cookie::get('locale', 'ar'));
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
