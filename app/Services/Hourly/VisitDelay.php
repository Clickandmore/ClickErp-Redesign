<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;

class VisitDelay
{
    /**
     * Delay is measured from the same service end the worker and customer see (arrival + planned hours),
     * not the booked slot end, so a late start does not escalate before the service actually ends.
     */
    public static function state(HourlyVisit $visit, ?Carbon $end = null): ?array
    {
        if (!in_array($visit->status, ShelterPresence::OUT_STATUSES, true)) return null;
        $end ??= ServiceTime::deadline($visit);
        if (!$end || $end->isFuture()) return null;
        $minutes = (int) $end->diffInMinutes(now());
        $grace = (int) HourlyRentalSettings::get('pickup_grace_minutes', 30);
        $escalation = (int) HourlyRentalSettings::get('delay_escalation_minutes', 60);
        $stage = $minutes >= $grace + $escalation ? 'escalated' : ($minutes >= $grace ? 'overdue' : 'ended');
        $label = $visit->status === 'picked_up' ? 'في طريق العودة إلى الإيواء' : 'انتهى وقت الخدمة — بانتظار الاستلام';
        if ($stage !== 'ended') $label = ($stage === 'escalated' ? 'تصعيد التأخير: ' : 'تأخر: ').$label;
        $pickup_overdue = $stage !== 'ended' && in_array($visit->status, ['delivered', 'in_progress', 'worker_finished'], true);
        if ($pickup_overdue) $label = ($stage === 'escalated' ? 'تصعيد التأخير: ' : '').'العاملة لم يتم استلامها من العميل';
        $ends_at = $end->toDateTimeString();
        return compact('stage', 'minutes', 'label', 'pickup_overdue', 'ends_at');
    }
}
