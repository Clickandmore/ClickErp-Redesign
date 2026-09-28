<?php

namespace App\Http\Middleware;

use App\Support\HourlyRentalSettings;
use Closure;
use Illuminate\Http\Request;

/**
 * يخفي كل مسارات موديل التأجير بالساعة (إدارة، عميل، سائق) حين يكون الموديل مطفأً.
 * يُعيد 404 لا 403 حتى لا يكشف وجود الوحدة أصلًا.
 */
class EnsureHourlyRentalEnabled
{
    public function handle(Request $request, Closure $next)
    {
        HourlyRentalSettings::flush();
        abort_unless(HourlyRentalSettings::isEnabled(), 404);

        return $next($request);
    }
}
