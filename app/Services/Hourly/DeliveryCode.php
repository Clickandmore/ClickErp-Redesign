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
        if($visit->id && ServiceIncident::pendingReport((int)$visit->id))HourlyOrderService::reject(ServiceIncident::PENDING_MESSAGE);
        // Legacy visits without a customer-visible code keep their existing delivery flow.
        if (!self::required($visit)) return;
        self::match($visit, $code);
    }

    /**
     * A self-travelling worker proves she reached the customer the way the driver does at hand-over.
     * Only the first arrival needs it: the customer sees the code until the visit is delivered.
     */
    public static function requiredForSelfArrival(object $visit): bool
    {
        return (bool)HourlyRentalSettings::get('worker_require_delivery_code',true)
            && (string)($visit->delivery_code??'')!==''
            && in_array($visit->status??null,['scheduled','en_route'],true);
    }

    public static function assertSelfArrival(HourlyVisit $visit, ?string $code): void
    {
        if (self::requiredForSelfArrival($visit)) self::match($visit, $code);
    }

    private static function match(HourlyVisit $visit, ?string $code): void
    {
        if (trim((string)$code)==='') HourlyOrderService::reject('أدخل رمز التسليم الذي يظهر للعميل في تطبيقه.');
        if (!hash_equals((string)$visit->delivery_code,trim((string)$code))) HourlyOrderService::reject('رمز التسليم غير صحيح.');
    }
}
