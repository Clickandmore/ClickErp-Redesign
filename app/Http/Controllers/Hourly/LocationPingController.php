<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Services\Hourly\LocationPings;
use App\Support\HourlyRentalSettings;
use Illuminate\Http\Request;
use App\Models\Hourly\HourlyVisit;

/**
 * إرسال الموقع من تطبيقات الساعات: نقطة واحدة (lat, lng) أو دفعة points[] مخزنة أثناء انقطاع الشبكة.
 * الرد يحمل إعدادات التتبع الحالية حتى يلتزم التطبيق بالفاصل وبالعمل في الخلفية أو التوقف.
 */
class LocationPingController extends Controller
{
    public function maid(Request $request, LocationPings $pings) { return $this->store($request, $pings, 'maid', auth('hourly_maid_api')->id()); }
    public function driver(Request $request, LocationPings $pings) { return $this->store($request, $pings, 'driver', auth('hourly_driver_api')->id()); }
    public function driverWeb(Request $request, LocationPings $pings) { return $this->store($request, $pings, 'driver', auth('hourly_driver')->id()); }
    public function customer(Request $request, LocationPings $pings) { return $this->store($request, $pings, 'customer', auth('api')->id()); }

    private function store(Request $request, LocationPings $pings, string $type, int $userId)
    {
        // تطبيق الفريق الميداني يرسل latitude/longitude؛ مقبولان كاسمين بديلين لـlat/lng.
        foreach (['latitude' => 'lat', 'longitude' => 'lng'] as $alias => $key) {
            if ($request->filled($alias) && !$request->has($key)) $request->merge([$key => $request->input($alias)]);
        }
        $tracking = HourlyRentalSettings::trackingConfig();
        if (!$tracking['enabled']) {
            return response()->json(['message' => __('hourly_routing.gps.tracking_off'), 'saved' => 0, 'tracking' => $tracking], 403);
        }
        // speed بالكيلومتر في الساعة، heading بالدرجات من الشمال (0–360)؛ إصدار التطبيق ونوع الجهاز للطلب كله أو لكل نقطة.
        $data = $request->validate([
            'lat' => 'required_without:points|numeric|between:-90,90', 'lng' => 'required_without:points|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:100000', 'recorded_at' => 'nullable|date',
            'speed' => 'nullable|numeric|min:0|max:400', 'heading' => 'nullable|numeric|min:0|max:360',
            'app_version' => 'nullable|string|max:30', 'device_type' => 'nullable|string|max:40',
            'visit_id' => 'nullable|integer|min:1',
            'points' => 'sometimes|array|min:1|max:100',
            'points.*.lat' => 'required|numeric|between:-90,90', 'points.*.lng' => 'required|numeric|between:-180,180',
            'points.*.accuracy' => 'nullable|numeric|min:0|max:100000', 'points.*.recorded_at' => 'nullable|date',
            'points.*.speed' => 'nullable|numeric|min:0|max:400', 'points.*.heading' => 'nullable|numeric|min:0|max:360',
            'points.*.app_version' => 'nullable|string|max:30', 'points.*.device_type' => 'nullable|string|max:40',
        ]);
        $points = $data['points'] ?? [array_intersect_key($data, array_flip(['lat', 'lng', 'accuracy', 'recorded_at', 'speed', 'heading']))];
        if (!empty($data['visit_id'])) {
            $this->authorizeVisit($type, $userId, (int) $data['visit_id']);
            $points = array_map(fn ($point) => $point + ['visit_id' => (int) $data['visit_id']], $points);
        }
        $device = array_intersect_key($data, array_flip(['app_version', 'device_type']));

        return response()->json(['message' => __('hourly_routing.gps.received'), 'saved' => $pings->record($type, $userId, $points, $device), 'tracking' => $tracking]);
    }

    private function authorizeVisit(string $type, int $userId, int $visitId): void
    {
        $query = HourlyVisit::whereKey($visitId);
        if ($type === 'driver') {
            $query->where(fn ($q) => $q->where('delivery_driver_id', $userId)->orWhere('pickup_driver_id', $userId));
        } elseif ($type === 'maid') {
            $query->whereHas('workers', fn ($q) => $q->where('maids.id', $userId));
        } else {
            $query->whereHas('order', fn ($q) => $q->where('user_id', $userId));
        }
        abort_unless($query->exists(), 404, 'الزيارة غير موجودة أو ليست من مهامك.');
    }
}
