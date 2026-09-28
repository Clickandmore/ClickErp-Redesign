<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class HourlyApiLang
{
    public function handle(Request $request, Closure $next)
    {
        Config::set('auth.defaults.guard', 'api');

        app()->setLocale(self::localeFor($request));
        $attributes = trans('hourly_validation');
        if (is_array($attributes)) {
            trans('validation');
            app('translator')->addLines(collect($attributes)->mapWithKeys(fn ($value, $key) => ['validation.attributes.'.$key => $value])->all(), app()->getLocale());
        }

        return $next($request);
    }

    public static function localeFor(Request $request): string
    {
        $locale = strtolower((string) ($request->header('lang') ?: $request->header('Accept-Language', 'ar')));
        $locale = substr($locale, 0, 2);

        return in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }
}
