<?php

namespace App\Models\Hourly;

use Illuminate\Database\Eloquent\Model;

/**
 * منطقة تغطية لفريق: مضلع من نقاط [lat, lng]. الحدود والمركز محسوبة عند الحفظ
 * حتى يكون فحص «أي فريق يغطي هذه النقطة» رخيصًا.
 */
class HourlyTeamArea extends Model
{
    protected $table = 'hourly_team_areas';

    protected $fillable = [
        'hourly_team_id', 'name', 'polygon', 'centroid_lat', 'centroid_lng',
        'min_lat', 'max_lat', 'min_lng', 'max_lng', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'polygon' => 'array',
        'is_active' => 'boolean',
        'centroid_lat' => 'float',
        'centroid_lng' => 'float',
        'min_lat' => 'float',
        'max_lat' => 'float',
        'min_lng' => 'float',
        'max_lng' => 'float',
    ];

    public function team()
    {
        return $this->belongsTo(HourlyTeam::class, 'hourly_team_id');
    }

    /** يُنظّف المضلع ويحسب الحدود والمركز. يعيد null إن كان المضلع غير صالح. */
    public static function normalizePolygon($raw): ?array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return null;
        }

        $points = [];
        foreach ($raw as $point) {
            $lat = is_array($point) ? ($point[0] ?? $point['lat'] ?? null) : null;
            $lng = is_array($point) ? ($point[1] ?? $point['lng'] ?? null) : null;
            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $lat = (float) $lat;
            $lng = (float) $lng;
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }
            $points[] = [round($lat, 7), round($lng, 7)];
        }

        // إزالة نقطة الإغلاق المكررة إن أرسلها المحرر
        if (count($points) > 1 && $points[0] === end($points)) {
            array_pop($points);
        }

        if (count($points) < 3) {
            return null;
        }

        return $points;
    }

    public function fillGeometry(array $points): self
    {
        $lats = array_column($points, 0);
        $lngs = array_column($points, 1);

        $this->polygon = $points;
        $this->min_lat = min($lats);
        $this->max_lat = max($lats);
        $this->min_lng = min($lngs);
        $this->max_lng = max($lngs);
        $this->centroid_lat = array_sum($lats) / count($lats);
        $this->centroid_lng = array_sum($lngs) / count($lngs);

        return $this;
    }

    public function contains(float $lat, float $lng): bool
    {
        if ($lat < $this->min_lat || $lat > $this->max_lat || $lng < $this->min_lng || $lng > $this->max_lng) {
            return false;
        }

        return self::pointInPolygon($lat, $lng, $this->polygon ?? []);
    }

    /** خوارزمية الشعاع: عدد تقاطعات خط أفقي من النقطة مع أضلاع المضلع. */
    public static function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$latI, $lngI] = $polygon[$i];
            [$latJ, $lngJ] = $polygon[$j];

            $intersects = (($lngI > $lng) !== ($lngJ > $lng))
                && ($lat < ($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 1e-12) + $latI);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
