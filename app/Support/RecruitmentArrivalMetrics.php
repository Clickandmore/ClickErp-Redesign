<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Pure calculations shared by the report, its cards and exports. */
class RecruitmentArrivalMetrics
{
    public static function date($value): ?Carbon
    {
        if (!$value || preg_match('/^(0000|0001)-/', (string) $value)) return null;
        try {
            $date = Carbon::parse($value);
            return $date->year >= 1900 ? $date : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function previousRange(Carbon $from, Carbon $to): array
    {
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        return [$from->copy()->subDays($days)->startOfDay(), $from->copy()->subDay()->endOfDay()];
    }

    public static function inRange(?Carbon $date, Carbon $from, Carbon $to): bool
    {
        return $date && $date->betweenIncluded($from, $to);
    }

    public static function waiting(array $record): bool
    {
        return !$record['has_arrived'] && !in_array($record['status'], [
            'back', 'refunded', 'cancelled', 'canceled', 'closed', 'sari', 'shelter', 'completed',
        ], true);
    }

    public static function delayed(array $record, Carbon $asOf): bool
    {
        return self::waiting($record) && $record['expected']
            && $record['expected']->copy()->startOfDay()->lt($asOf->copy()->startOfDay());
    }

    public static function select(Collection $records, string $bucket, string $dateType, Carbon $from, Carbon $to, Carbon $asOf): Collection
    {
        [$previousFrom, $previousTo] = self::previousRange($from, $to);
        return $records->filter(function ($r) use ($bucket, $dateType, $from, $to, $asOf, $previousFrom, $previousTo) {
            return match ($bucket) {
                'waiting' => self::waiting($r),
                'delayed' => self::delayed($r, $asOf),
                'missing' => $r['has_arrived'] && !$r['arrival'],
                'undelivered' => $r['arrival'] && $r['arrival']->lte($asOf) && !$r['delivery']
                    && !in_array($r['status'], ['back', 'refunded', 'cancelled', 'canceled'], true),
                'previous' => self::inRange($r['arrival'], $previousFrom, $previousTo),
                'arrived' => self::inRange($r['arrival'], $from, $to),
                'delivered' => self::inRange($r['delivery'], $from, $to),
                default => self::inRange($r[$dateType], $from, $to),
            };
        })->values();
    }

    public static function summary(Collection $records, Carbon $from, Carbon $to, Carbon $asOf): array
    {
        $counts = [];
        foreach (['arrived', 'previous', 'waiting', 'delayed', 'missing', 'undelivered', 'delivered'] as $bucket) {
            $counts[$bucket] = self::select($records, $bucket, 'arrival', $from, $to, $asOf)->count();
        }
        $counts['difference'] = $counts['arrived'] - $counts['previous'];
        $counts['percentage'] = $counts['previous'] > 0
            ? round($counts['difference'] * 100 / $counts['previous'], 1) : null;
        return $counts;
    }
}