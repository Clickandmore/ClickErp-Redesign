<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class ExternalOfficeAgent
{
    public function handle($request, Closure $next)
    {
        Config::set('auth.defaults.guard', 'agent');

        $guard = Auth::guard('agent');

        if (! $guard->check()) {
            return redirect()->route('agent.login.form');
        }

        if (session('_agent_impersonate_office_id') && Auth::guard('admin')->check()) {
            return $next($request);
        }

        $agent = $guard->user();

        if (($agent->status ?? 'active') === 'suspended' || ! $agent->isExternalOfficeAgent()) {
            $guard->logout();

            return redirect()
                ->route('agent.login.form')
                ->withErrors(['login' => 'هذا الحساب غير متاح للوحة الوكلاء الخارجيين.']);
        }

        return $next($request);
    }
}
