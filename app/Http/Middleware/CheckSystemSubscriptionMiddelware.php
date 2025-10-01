<?php

namespace App\Http\Middleware;

use App\Enums\ingaz\ModulaNamesEnum;
use App\Http\Actions\ingaz\SystemSubscriptionAction;
use Closure;
use Illuminate\Http\Request;

class CheckSystemSubscriptionMiddelware
{
    public function __construct(protected SystemSubscriptionAction $systemSubscriptionAction)
    {

    }

    public function handle(Request $request, Closure $next)
    {
        $systemSubscription = $this->systemSubscriptionAction->first();
        if (!$systemSubscription) {
            return redirect()->route('admin.form.login');
        }

        $systemPlans = $systemSubscription->system_plans;
        $hasModuleWebsite = $systemPlans->contains('module_name', ModulaNamesEnum::Website->value);

        if (!$hasModuleWebsite) {
            return redirect()->route('admin.form.login');
        }

        return $next($request);
    }
}
