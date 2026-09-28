<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyTeam;
use App\Models\Hourly\HourlyTeamArea;
use Illuminate\Support\Collection;

/**
 * أي فريق يغطي هذه النقطة؟ يُستخدم عند إنشاء الطلب لاقتراح الفريق،
 * وعند بناء المشاوير لتجميع الزيارات بالفريق.
 */
class TeamAreaResolver
{
    /** الفرق النشطة التي يقع العنوان داخل إحدى مناطقها، مرتبة بترتيب الفريق. */
    public function teamsCovering(float $lat, float $lng): Collection
    {
        $areas = HourlyTeamArea::query()
            ->where('is_active', true)
            ->where('min_lat', '<=', $lat)->where('max_lat', '>=', $lat)
            ->where('min_lng', '<=', $lng)->where('max_lng', '>=', $lng)
            ->with('team')
            ->get()
            ->filter(fn (HourlyTeamArea $area) => $area->team
                && $area->team->is_active
                && $area->contains($lat, $lng));

        return $areas->map->team->unique('id')->sortBy('sort_order')->values();
    }

    public function firstTeamCovering(float $lat, float $lng): ?HourlyTeam
    {
        return $this->teamsCovering($lat, $lng)->first();
    }

    /** أقرب فريق نشط بمركز منطقته حين لا يغطي أحد النقطة — للاقتراح فقط، لا للإسناد الآلي. */
    public function nearestTeam(float $lat, float $lng): ?HourlyTeam
    {
        $best = null;
        $bestDistance = PHP_FLOAT_MAX;

        HourlyTeamArea::query()->where('is_active', true)->with('team')->get()
            ->each(function (HourlyTeamArea $area) use ($lat, $lng, &$best, &$bestDistance) {
                if (!$area->team || !$area->team->is_active || $area->centroid_lat === null) {
                    return;
                }
                $d = self::haversineKm($lat, $lng, (float) $area->centroid_lat, (float) $area->centroid_lng);
                if ($d < $bestDistance) {
                    $bestDistance = $d;
                    $best = $area->team;
                }
            });

        return $best;
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
