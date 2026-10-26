<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyRentalOrder;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;

class RenewalEligibility
{
    public function reason(HourlyRentalOrder $order, ?bool $performed = null, ?Carbon $end = null): ?string
    {
        if ($order->status === HourlyOrderService::AWAITING_PAYMENT) return 'انتهت زيارات الطلب ولم يُسدَّد المتبقي؛ سدِّد الطلب أولًا ثم جدِّده.';
        if (!in_array($order->status, ['active','completed'], true)) return 'التجديد متاح للطلبات النشطة أو المكتملة فقط؛ لا يمكن تجديد طلب ملغى أو غير مؤكد.';
        $period=app(ContractPeriod::class);
        $performed ??= $period->scope($order->visits()->getQuery())->where('status','completed')->exists();
        if (!$performed) return 'لا يمكن التجديد قبل تنفيذ أول زيارة؛ يمكنك تعديل الحجز الحالي أو إلغاؤه.';
        $end ??= $period->endOf($order);
        if (!$end) return 'لا توجد زيارات مجدولة تحدد نهاية الباقة.';
        $days=(int)HourlyRentalSettings::get('renewal_reminder_days',7);
        $days=$days>0?$days:7;
        if ($end->gt(today()->addDays($days)->endOfDay())) return "يتاح التجديد خلال {$days} أيام من نهاية الباقة أو بعد انتهائها.";
        return null;
    }
}