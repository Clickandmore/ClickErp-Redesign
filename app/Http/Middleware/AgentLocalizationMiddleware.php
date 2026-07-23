<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AgentLocalizationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = array_keys(config('laravellocalization.supportedLocales', [
            'ar' => [],
            'en' => [],
        ]));

        $requestedLocale = $request->query('agent_locale');
        $urlLocale = $request->segment(1);

        if (in_array($requestedLocale, $supportedLocales, true)) {
            $locale = $requestedLocale;
            $request->session()->put('agent_locale', $locale);
        } elseif (in_array($urlLocale, $supportedLocales, true)) {
            $locale = $urlLocale;
            $request->session()->put('agent_locale', $locale);
        } else {
            $locale = $request->session()->get('agent_locale', 'en');
        }

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = 'en';
        }

        LaravelLocalization::setLocale($locale);

        return $next($request);
    }
}
