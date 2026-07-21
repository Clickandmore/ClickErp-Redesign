<?php

namespace App\Http\Middleware;

use App\Enums\ingaz\ModulaNamesEnum;
use App\Http\Actions\ingaz\SystemSubscriptionAction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CheckSystemSubscriptionMiddelware
{
    public function __construct(protected SystemSubscriptionAction $systemSubscriptionAction)
    {

    }

    public function handle(Request $request, Closure $next)
    {
        if (!Schema::hasTable('system_subscriptions') || !Schema::hasTable('system_plans')) {
            return $next($request);
        }

        $systemSubscription = $this->systemSubscriptionAction->first();
        if (!$systemSubscription) {
            return $next($request);
        }

        $systemPlans = $systemSubscription->system_plans;
        $hasModuleWebsite = $systemPlans->contains('module_name', ModulaNamesEnum::Website->value);

        if (!$hasModuleWebsite) {
            return $next($request);
        }

        return $next($request);
    }
}
