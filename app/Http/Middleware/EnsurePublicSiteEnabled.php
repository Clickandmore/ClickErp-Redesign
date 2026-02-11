<?php

namespace App\Http\Middleware;

use App\Models\ingaz\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsurePublicSiteEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Schema::hasTable('settings')) {
            return $next($request);
        }

        $settings = Setting::first();
        if (!$settings) {
            return $next($request);
        }

        $enabled = $settings->show_public_site ?? 1;
        if ((int) $enabled === 0) {
            return redirect()->route('admin.form.login');
        }

        return $next($request);
    }
}
