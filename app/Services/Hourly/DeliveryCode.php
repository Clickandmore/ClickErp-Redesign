<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use App\Support\HourlyRentalSettings;

class DeliveryCode
{
    public static function required(HourlyVisit $visit): bool
    {
        return (bool)HourlyRentalSettings::get('staff_app_require_delivery_code',true)
            && (string)($visit->delivery_code??'')!=='';
    }

    public static function assertValid(HourlyVisit $visit, ?string $code): void
    {
        if($visit->order && ServiceIncident::waiting($visit->order,$visit->id))
            HourlyOrderService::reject('للزيارة بلاغ معتمد يحتاج مراجعة الإدارة قبل التسليم؛ الاستلام والعودة متاحان حسب حالة التنفيذ.');
        // Legacy visits without a customer-visible code keep their existing delivery flow.
        if (!self::required($visit)) return;
        if (trim((string)$code)==='') HourlyOrderService::reject('أدخل رمز التسليم الذي يظهر للعميل في تطبيقه.');
        if (!hash_equals((string)$visit->delivery_code,trim((string)$code))) HourlyOrderService::reject('رمز التسليم غير صحيح.');
    }
}
