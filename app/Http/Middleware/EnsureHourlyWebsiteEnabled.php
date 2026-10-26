<?php

namespace App\Http\Middleware;

use App\Support\HourlyRentalSettings;
use Closure;
use Illuminate\Http\Request;

/**
 * يخفي حجز الساعات عن المواقع (الموقع المدمج وواجهات الموقع الخارجي) حين يُطفأ «العرض في الموقع الخارجي»
 * أو الموديل نفسه. التطبيق ولوحة التحكم لا يمران من هنا. يُعيد 404 حتى لا يكشف وجود الخدمة.
 */
class EnsureHourlyWebsiteEnabled
{
    public function handle(Request $request, Closure $next)
    {
        HourlyRentalSettings::flush();
        abort_unless(HourlyRentalSettings::websiteEnabled(), 404);

        return $next($request);
    }
}
