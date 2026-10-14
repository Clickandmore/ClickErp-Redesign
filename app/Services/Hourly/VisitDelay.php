<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use App\Support\HourlyRentalSettings;

class VisitDelay
{
    public static function state(HourlyVisit $visit): ?array
    {
        if (!in_array($visit->status, ShelterPresence::OUT_STATUSES, true)
            || !$visit->scheduled_end_at || $visit->scheduled_end_at->isFuture()) return null;
        $minutes = (int) $visit->scheduled_end_at->diffInMinutes(now());
        $grace = (int) HourlyRentalSettings::get('pickup_grace_minutes', 30);
        $escalation = (int) HourlyRentalSettings::get('delay_escalation_minutes', 60);
        $stage = $minutes >= $grace + $escalation ? 'escalated' : ($minutes >= $grace ? 'overdue' : 'ended');
        $label = $visit->status === 'picked_up' ? 'في طريق العودة إلى الإيواء' : 'انتهى وقت الخدمة — بانتظار الاستلام';
        if ($stage !== 'ended') $label = ($stage === 'escalated' ? 'تصعيد التأخير: ' : 'تأخر: ').$label;
        return compact('stage', 'minutes', 'label');
    }
}
