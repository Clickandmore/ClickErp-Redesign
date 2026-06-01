<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class ExternalOfficeAgent
{
    public function handle($request, Closure $next)
    {
        Config::set('auth.defaults.guard', 'agent');

        $guard = Auth::guard('agent');

        if (! $guard->check()) {
            return redirect($this->localizedRoute('agent.login.form'));
        }

        $agent = $guard->user();

        if (($agent->status ?? 'active') === 'suspended' || ! $agent->isExternalOfficeAgent()) {
            $guard->logout();

            return redirect($this->localizedRoute('agent.login.form'))
                ->withErrors(['login' => 'This account is not available for the external agent dashboard.']);
        }

        return $next($request);
    }

    private function localizedRoute(string $routeName): string
    {
        return LaravelLocalization::getLocalizedURL(app()->getLocale(), route($routeName), [], true);
    }
}
