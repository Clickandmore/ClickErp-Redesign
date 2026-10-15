<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;

class DeliveryStartWindow
{
    public const MESSAGE = 'بدء التوصيل متاح من موعد التوصيل المحدد وحتى نهاية موعد الزيارة فقط. راجع الإدارة لإعادة الجدولة.';

    public static function allows(HourlyVisit $visit): bool
    {
        $start = $visit->delivery_at ?? $visit->scheduled_start_at;
        $end = $visit->scheduled_end_at;
        return $start && $end && $start->lt($end) && now()->gte($start) && now()->lt($end);
    }

    public static function assertAllowed(HourlyVisit $visit): void
    {
        if (!self::allows($visit)) HourlyOrderService::reject(self::MESSAGE);
    }
}
