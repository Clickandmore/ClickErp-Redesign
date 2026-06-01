<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            $supportedLocales = array_keys(config('laravellocalization.supportedLocales', []));
            $firstSegment = $request->segment(1);
            $adminSegment = in_array($firstSegment, $supportedLocales, true)
                ? $request->segment(2)
                : $firstSegment;

            if ($adminSegment === 'admin' || $adminSegment === 'dashboard') {
                return route('admin.form.login');
            }

            return Route::has('login') ? route('login') : route('admin.form.login');
        }
    }
}
