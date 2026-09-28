<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyPackage;

/** Date-only bookings choose an actual available slot on the server. */
class HourlyDateBooking
{
    public function resolve(HourlyPackage $item, array $dates, array $snapshot, bool $panel, ?int $teamId, int $workers, ?int $preferred, ?int $exceptVisit = null, array $assigned = [], ?\Illuminate\Support\Collection $eligible = null, bool $requireFullCount = true): array
    {
        $settings = app(HourlyBookingRules::class)->settings();
        if ($panel || $settings->value($snapshot, 'show_time_options_app')) {
            HourlyOrderService::reject('اختر وقت الزيارة لهذه الخدمة.');
        }
        $assignment = app(HourlyWorkerAssignment::class);
        $checkWorkers = $eligible !== null || $preferred || $assigned || $settings->value($snapshot, 'auto_assign', 'web_app');
        $candidates = $eligible ?? ($checkWorkers ? $assignment->candidates($item, $teamId,
            (bool) $settings->value($item, 'assign_by_product_attribute'))->lockForUpdate()->get() : collect());
        return array_map(function ($date) use ($item, $snapshot, $workers, $preferred, $assignment, $checkWorkers, $candidates, $exceptVisit, $assigned, $requireFullCount) {
            $slots = app(HourlySlotService::class)->forDate($date, (float) ($snapshot['hours_per_visit'] ?? $item->hours_per_visit),
                $snapshot['min_notice_hours'] ?? null, $snapshot);
            foreach ($slots as $slot) {
                if ($checkWorkers) {
                    $available = $candidates->filter(fn ($maid) => $assignment->available($maid, $slot['starts_at'], $slot['ends_at'], $snapshot, $exceptVisit));
                    if (($requireFullCount && $available->count() < $workers) || ($preferred && !$available->contains('id', $preferred))) continue;
                    if (array_diff($assigned, $available->pluck('id')->all())) continue;
                }
                return substr($slot['starts_at'], 0, 16);
            }
            HourlyOrderService::reject('لا يوجد وقت متاح للخدمة في يوم '.$date.'.');
        }, $dates);
    }
}
