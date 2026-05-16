<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AdminLocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = array_keys(LaravelLocalization::getSupportedLocales());
        $firstSegment = strtolower((string) $request->segment(1));

        $locale = in_array($firstSegment, $supportedLocales, true)
            ? $firstSegment
            : config('app.locale', 'ar');

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = config('app.locale', 'ar');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
