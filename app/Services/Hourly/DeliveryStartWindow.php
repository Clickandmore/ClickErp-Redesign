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
        if ($reason=self::blockedReason($visit)) HourlyOrderService::reject($reason);
    }

    public static function blockedReason(HourlyVisit $visit): ?string
    {
        if($visit->order && ServiceIncident::waiting($visit->order,$visit->id))
            return 'للزيارة بلاغ تعذر خدمة معتمد؛ لا يمكن بدء التوصيل قبل اعتماد البديلة أو الموعد الجديد.';
        if($visit->id && ServiceIncident::pendingReport((int)$visit->id))return ServiceIncident::PENDING_MESSAGE;
        if ($visit->scheduled_end_at && now()->gte($visit->scheduled_end_at))
            return 'انتهى موعد الزيارة؛ لا يمكن بدء التوصيل. راجع الإدارة لإعادة الجدولة.';
        if (!self::allows($visit)) return self::MESSAGE;
        // Re-read workers: leave or weekly schedules may have changed after assignment.
        $start=now()->max($visit->scheduled_start_at)->toDateTimeString();
        foreach ($visit->workers()->get() as $worker) {
            $reason=app(HourlyWorkerAssignment::class)->availabilityReason($worker,$start,
                $visit->scheduled_end_at->toDateTimeString(),(array)$visit->order->package_snapshot,(int)$visit->id);
            if ($reason) return 'لا يمكن بدء مشوار التوصيل: '.$worker->name.' — '.$reason.' راجع الإدارة لتعديل التعيين أو إعادة الجدولة.';
        }
        return null;
    }
}
