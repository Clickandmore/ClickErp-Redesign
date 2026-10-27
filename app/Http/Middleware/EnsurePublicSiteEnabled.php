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

        if (!empty($settings->public_site_maintenance)) {
            return response()->view('maintenance', [
                'logo' => $settings->logo_header ? showFile($settings->logo_header) : null,
                'siteName' => $settings->website_name ?? config('app.name'),
            ], 503)->header('Retry-After', '3600');
        }

        return $next($request);
    }
}
