<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;

final class RentalContractDays
{
    public static function calculate(int $duration, ?DateTimeInterface $start, ?DateTimeInterface $end, DateTimeInterface $at): array
    {
        $duration = max(0, $duration);
        $at = new DateTimeImmutable($at->format('Y-m-d'));
        $start = $start ? new DateTimeImmutable($start->format('Y-m-d')) : null;
        $end = $end ? new DateTimeImmutable($end->format('Y-m-d')) : null;
        $used = 0;
        if ($start && $at >= $start) {
            // The current service day is charged, including the first day.
            $used = $end && $at >= $end
                ? $duration
                : min($duration, (int) $start->diff($at)->days + 1);
        }

        return [
            'duration_days' => $duration,
            'used_days' => $used,
            'remaining_days' => $duration - $used,
            'overdue_days' => $end && $at > $end ? (int) $end->diff($at)->days : 0,
        ];
    }
}
