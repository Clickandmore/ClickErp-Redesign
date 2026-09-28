<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\HourlyRentalSettings;

/**
 * قراءة موقع القادم إلى العميل لزيارة واحدة، مقابل الكتابة في LocationPingController.
 *
 * ثلاث قواعد تحكم ما يُكشف:
 * - نافذة زمنية بالحالة لا بالوقت: الموقع يظهر أثناء رحلة التوصيل (en_route) ورحلة
 *   الاستلام (worker_finished) فقط. قبلها لم يتحرك أحد، وبعدها لا معنى للتتبع.
 * - جهة واحدة فقط: سائق الرحلة الجارية، أو العاملة نفسها إن لم يُسند سائق للتوصيل.
 *   لا يُكشف موقع العاملة داخل منزل العميل ولا موقع أي طرف آخر في الطلب.
 * - نقطة قديمة تُعاد موسومة is_stale ليخفت المؤشر في الخريطة، والأقدم من
 *   MAX_AGE_MINUTES لا تُعاد أصلًا حتى لا يتابع العميل موقعًا ميتًا.
 */
class VisitTracking
{
    /** رحلة التوصيل: السائق في طريقه بالعاملة إلى العميل. */
    private const DELIVERY_STATUSES = ['en_route'];
    /** رحلة الاستلام: العاملة أنهت، والسائق في طريقه لاستلامها. */
    private const PICKUP_STATUSES = ['worker_finished'];
    /** أقدم نقطة تُعرض للعميل؛ ما قبلها لا يصف موقعًا حاليًا. */
    private const MAX_AGE_MINUTES = 30;
    /** بعدها تُوسم النقطة قديمة، بحد أدنى دقيقتان مهما قصر فاصل الإرسال. */
    private const STALE_INTERVALS = 6;
    private const MIN_STALE_SECONDS = 120;

    /**
     * @return array{enabled:bool,state:string,message:?string,poll_after_seconds:?int,visit:array,destination:?array,subject:?array}
     */
    public function forVisit(HourlyVisit $visit, HourlyRentalOrder $order): array
    {
        $tracking = HourlyRentalSettings::trackingConfig();
        abort_unless($this->categoryEnabled($order), 404);
        $result = [
            'enabled' => (bool) $tracking['enabled'],
            'state' => 'off',
            'message' => null,
            'poll_after_seconds' => null,
            'visit' => [
                'id' => $visit->id,
                'status' => $visit->status,
                'display_status' => CustomerHourlyData::VISIT_DISPLAY[$visit->status] ?? $visit->status,
                'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
            ],
            'destination' => $this->destination($visit),
            'subject' => null,
        ];

        if (!$tracking['enabled']) return ['message' => __('hourly_api.tracking.off'), 'is_live' => false] + $result;

        $leg = $this->leg($visit->status);
        if (!$leg) {
            return ['state' => 'closed', 'message' => __('hourly_api.tracking.not_now'), 'is_live' => false,
                'stage' => $this->stage($visit->status)] + $result;
        }

        // فاصل الاستطلاع يتبع فاصل الإرسال، فلا يسأل التطبيق أسرع مما يكتب الطرف الآخر.
        $interval = max(5, (int) $tracking['interval_seconds']);
        $result['poll_after_seconds'] = $interval;

        [$type, $id, $name, $phone] = $this->who($visit, $leg);
        if (!$id) {
            return ['state' => 'waiting', 'message' => __('hourly_api.tracking.no_subject'), 'is_live' => true,
                'stage' => $this->stage($visit->status), 'poll_seconds' => $interval] + $result;
        }

        $ping = $this->lastPing($type, $id, (int) $visit->id);
        if (!$ping) {
            $person = ['name' => $name, 'phone' => $phone];
            return ['state' => 'waiting', 'message' => __('hourly_api.tracking.no_signal'), 'is_live' => true,
                'stage' => $this->stage($visit->status), 'poll_seconds' => $interval,
                ($type === 'maid' ? 'worker' : 'driver') => $person,
                'subject' => ['role' => $leg, 'type' => $type, 'name' => $name, 'phone' => $phone, 'point' => null]] + $result;
        }

        $at = Carbon::parse($ping->recorded_at);
        $age = max(0, now()->diffInSeconds($at));

        $person = ['name' => $name, 'phone' => $phone];
        $point = [
            'lat' => (float) $ping->lat, 'lng' => (float) $ping->lng,
            'accuracy' => $ping->accuracy !== null ? (float) $ping->accuracy : null,
            'updated_at' => $at->toDateTimeString(),
        ];

        return ['state' => 'live', 'stage' => $this->stage($visit->status), 'is_live' => true,
            'poll_seconds' => $interval, ($type === 'maid' ? 'worker' : 'driver') => $person,
            'location' => $point, 'subject' => [
            'role' => $leg, 'type' => $type, 'name' => $name, 'phone' => $phone,
            'point' => [
                'lat' => (float) $ping->lat, 'lng' => (float) $ping->lng,
                'accuracy' => $ping->accuracy !== null ? (float) $ping->accuracy : null,
                'speed' => $ping->speed !== null ? (float) $ping->speed : null,
                'heading' => $ping->heading !== null ? (int) $ping->heading : null,
                'recorded_at' => $at->toIso8601String(),
                'age_seconds' => $age,
                'is_stale' => $age > max(self::MIN_STALE_SECONDS, $interval * self::STALE_INTERVALS),
            ],
        ]] + $result;
    }

    /** هل زر التتبع متاح لهذه الزيارة وفق إعداد النظام والفئة ومرحلة التنفيذ؟ */
    public function canTrack(HourlyVisit $visit, HourlyRentalOrder $order): bool
    {
        return (bool) HourlyRentalSettings::trackingConfig()['enabled']
            && $this->categoryEnabled($order)
            && $this->leg($visit->status) !== null;
    }

    private function categoryEnabled(HourlyRentalOrder $order): bool
    {
        $snapshot = (array) $order->package_snapshot;
        if (empty($snapshot['service_category_id'])) return true;

        return (bool) app(HourlyCategorySettings::class)->value($snapshot, 'tracking_enabled');
    }

    private function stage(string $status): string
    {
        return match ($status) {
            'en_route' => 'on_the_way',
            'delivered' => 'arrived',
            'in_progress' => 'working',
            'worker_finished', 'picked_up', 'completed' => 'completed',
            default => 'assigned',
        };
    }

    /** أي رحلة تجري الآن، أو null إن لم يكن هناك ما يُتتبَّع. */
    private function leg(string $status): ?string
    {
        if (in_array($status, self::DELIVERY_STATUSES, true)) return 'delivery';
        if (in_array($status, self::PICKUP_STATUSES, true)) return 'pickup';

        return null;
    }

    /**
     * صاحب الرحلة: سائقها، وإلا العاملة نفسها في رحلة التوصيل حين لا سائق.
     *
     * @return array{0:string,1:?int,2:?string,3:?string}
     */
    private function who(HourlyVisit $visit, string $leg): array
    {
        if ($driverId = $visit->{$leg.'_driver_id'}) {
            $driver = DB::table('hourly_drivers')->where('id', $driverId)->first(['name', 'phone']);

            return ['driver', (int) $driverId, $driver->name ?? null, $driver->phone ?? null];
        }
        // بلا سائق توصيل تنتقل العاملة بنفسها؛ الاستلام بلا سائق لا يُتتبَّع.
        if ($leg !== 'delivery') return ['driver', null, null, null];
        $maid = $visit->workers()->first(['maids.id', 'maids.name']);

        // اسم العاملة يكفي العميل؛ جوالها لا يُكشف من هنا.
        return ['maid', $maid?->id, $maid?->name, null];
    }

    private function lastPing(string $type, int $userId, int $visitId): ?object
    {
        return DB::table('location_pings')->where('user_type', $type)->where('user_id', $userId)
            ->when(Schema::hasColumn('location_pings', 'hourly_visit_id'), fn ($q) => $q->where('hourly_visit_id', $visitId))
            ->where('recorded_at', '>=', now()->subMinutes(self::MAX_AGE_MINUTES))
            ->orderByDesc('recorded_at')->orderByDesc('id')
            ->first(['lat', 'lng', 'accuracy', 'speed', 'heading', 'recorded_at']);
    }

    private function destination(HourlyVisit $visit): ?array
    {
        if ($visit->latitude === null || $visit->longitude === null) return null;

        return ['lat' => (float) $visit->latitude, 'lng' => (float) $visit->longitude, 'address' => $visit->address_text];
    }
}
