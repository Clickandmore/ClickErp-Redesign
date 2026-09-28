<?php

namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * «تتبع GPS»: مواقع العاملات والسائقين من location_pings، والعملاء من عنوان الطلب.
 * user_type يميّز الجهة: maid (مزود الخدمة) أو driver أو customer، لأن أرقامها من جداول مختلفة.
 * «نشط» = آخر موقع خلال tracking_active_minutes دقيقة (الافتراضي 60)؛ والعميل نشط إن كانت له زيارة في اليوم.
 */
class LocationPings
{
    /** المفاتيح فقط؛ النصوص من hourly_routing.gps.types بحسب لغة الواجهة. */
    public const TYPES = ['maid', 'driver', 'customer'];
    /** الجهات التي تُتتبَّع من التطبيق؛ العميل يُعرض من عنوان طلبه. */
    public const TRACKED = ['maid', 'driver'];
    /** أبعد مسافة لنسب الموقع لأقرب مدينة؛ أبعد من ذلك يبقى بلا مدينة. */
    private const CITY_RADIUS_KM = 100;
    /** التطبيق قد يرسل مواقع مخزنة أثناء انقطاع الشبكة، لكن ليس أقدم من يوم ولا من المستقبل. */
    private const MAX_AGE_HOURS = 24;
    /** سقف علامات العملاء في الخريطة حتى لا تثقل الصفحة؛ التجميع يتكفل بالكثافة. */
    private const MAX_CUSTOMERS = 3000;
    private const TRAIL_LIMIT = 5000;

    /**
     * @param array<int, array{lat:float,lng:float,accuracy?:float|null,speed?:float|null,heading?:float|null,recorded_at?:string|null,app_version?:string|null,device_type?:string|null}> $points
     * @param array{app_version?:string|null,device_type?:string|null} $device بيانات الجهاز العامة للطلب، والنقطة تتقدم عليها
     */
    public function record(string $type, int $userId, array $points, array $device = []): int
    {
        $now = now();
        $hasVisitId = Schema::hasColumn('location_pings', 'hourly_visit_id');
        $cities = null;
        $rows = [];
        foreach ($points as $point) {
            $at = !empty($point['recorded_at']) ? Carbon::parse($point['recorded_at'])->setTimezone(config('app.timezone')) : $now->copy();
            if ($at->lt($now->copy()->subHours(self::MAX_AGE_HOURS)) || $at->gt($now->copy()->addMinutes(5))) continue;
            $cities ??= \App\Models\City::where('is_active', true)->whereNotNull('latitude')->whereNotNull('longitude')->get(['id', 'latitude', 'longitude']);
            $rows[] = [
                'user_type' => $type, 'user_id' => $userId, 'lat' => $point['lat'], 'lng' => $point['lng'],
                'accuracy' => $point['accuracy'] ?? null,
                'speed' => isset($point['speed']) ? round((float) $point['speed'], 2) : null,
                'heading' => isset($point['heading']) ? ((int) round((float) $point['heading'])) % 360 : null,
                'app_version' => $point['app_version'] ?? $device['app_version'] ?? null,
                'device_type' => $point['device_type'] ?? $device['device_type'] ?? null,
                'city_id' => $this->nearestCity($cities, (float) $point['lat'], (float) $point['lng']),
                'recorded_at' => $at->min($now)->toDateTimeString(), 'created_at' => $now,
            ];
            if ($hasVisitId) {
                $rows[array_key_last($rows)]['hourly_visit_id'] = isset($point['visit_id']) ? (int) $point['visit_id'] : null;
            }
        }
        // بالترتيب الزمني حتى يكون أكبر id لكل مستخدم هو آخر موقع له (تعتمد عليه latest()).
        usort($rows, fn ($a, $b) => $a['recorded_at'] <=> $b['recorded_at']);
        foreach (array_chunk($rows, 100) as $chunk) DB::table('location_pings')->insert($chunk);
        if ($rows && in_array($type, self::TRACKED, true) && app(TrackingSocket::class)->enabled()) {
            DB::afterCommit(fn () => app(TrackingSocket::class)->changed());
        }

        return count($rows);
    }

    /** نشط = آخر تحديث خلال الدقائق المحددة في الإعدادات. */
    public function isActive(Carbon $recordedAt): bool
    {
        return $recordedAt->gte(now()->subMinutes((int) HourlyRentalSettings::get('tracking_active_minutes', 60)));
    }

    /**
     * كل ما يُرسم على الخريطة: آخر موقع لكل عاملة/سائق في اليوم، والعملاء من عناوين طلباتهم.
     *
     * @param array{city_ids?:int[],types?:string[],status?:string} $filters
     */
    public function latest(Carbon $date, array $filters): array
    {
        $types = array_values(array_intersect($filters['types'] ?? self::TYPES, self::TYPES));
        $points = collect($this->tracked($date, array_values(array_intersect($types, self::TRACKED)), $filters['city_ids'] ?? []));
        if (in_array('customer', $types, true)) $points = $points->concat($this->customers($date, $filters['city_ids'] ?? []));
        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') $points = $points->where('active', $status === 'active');

        return $points->values()->all();
    }

    /** مسار الشخص خلال اليوم بالترتيب الزمني. */
    public function trail(string $type, int $userId, Carbon $date): array
    {
        return DB::table('location_pings')->where('user_type', $type)->where('user_id', $userId)
            ->whereBetween('recorded_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->orderBy('recorded_at')->orderBy('id')->limit(self::TRAIL_LIMIT)->get(['lat', 'lng', 'speed', 'recorded_at'])
            ->map(fn ($p) => ['lat' => (float) $p->lat, 'lng' => (float) $p->lng, 'speed' => $p->speed !== null ? (float) $p->speed : null, 'at' => substr((string) $p->recorded_at, 11, 5)])->all();
    }

    /**
     * «الحالة الآن»: المهمة الحالية والتالية للشخص اليوم.
     * السائق: مهام التوصيل/الاستلام. العاملة: زياراتها. العميل: زيارات طلباته.
     *
     * @return array{current:?array,next:?array}
     */
    public function now(string $type, int $userId): array
    {
        $now = now();
        $query = HourlyVisit::query()->whereBetween('scheduled_start_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->whereNotIn('status', VisitSearch::CANCELLED)->whereHas('order', fn ($o) => $o->whereNotIn('status', ['cancelled', 'expired']))
            ->with(['order:id,order_number,user_id', 'order.user:id,name'])->orderBy('scheduled_start_at');

        if ($type === 'driver') {
            $board = app(RouteBoard::class);
            $items = $query->where(fn ($q) => $q->where('delivery_driver_id', $userId)->orWhere('pickup_driver_id', $userId))->get()
                ->flatMap(fn ($visit) => collect(RouteBoard::ROLES)->filter(fn ($role) => (int) $visit->{$role.'_driver_id'} === $userId)
                    ->map(fn ($role) => ['visit' => $visit, 'role' => $role, 'at' => $board->taskTime($visit, $role), 'open' => $board->openTask($visit->status, $role)]))
                ->sortBy(fn ($i) => $i['at']->timestamp)->values();
            // الحالية: أقدم مهمة مفتوحة حان وقتها؛ التالية: أول مهمة مفتوحة لم يحن وقتها.
            $current = $items->first(fn ($i) => $i['open'] && $i['at']->lte($now));
            $next = $items->first(fn ($i) => $i['open'] && $i['at']->gt($now));

            return ['current' => $current ? $this->describe($current['visit'], $current['role'], $current['at']) : null,
                'next' => $next ? $this->describe($next['visit'], $next['role'], $next['at']) : null];
        }

        $visits = ($type === 'maid'
            ? $query->whereHas('workers', fn ($w) => $w->where('maids.id', $userId))
            : $query->whereHas('order', fn ($o) => $o->where('user_id', $userId)))->get();
        $running = ['en_route', 'delivered', 'in_progress', 'worker_finished'];
        $current = $visits->first(fn ($v) => !in_array($v->status, VisitSearch::CLOSED, true)
            && (in_array($v->status, $running, true) || ($v->scheduled_start_at->lte($now) && ($v->scheduled_end_at ?? $v->scheduled_start_at)->gte($now))));
        $next = $visits->first(fn ($v) => !in_array($v->status, VisitSearch::CLOSED, true) && $v->scheduled_start_at->gt($now) && $v->id !== $current?->id);

        return ['current' => $current ? $this->describe($current) : null, 'next' => $next ? $this->describe($next) : null];
    }

    private function describe(HourlyVisit $visit, ?string $role = null, ?Carbon $at = null): array
    {
        return [
            'task' => $role ? __('hourly_routing.roles.'.$role) : null,
            'time' => $at ? $at->format('H:i') : $visit->scheduled_start_at->format('H:i').'–'.$visit->scheduled_end_at?->format('H:i'),
            'visit_number' => $visit->visit_number, 'order_number' => $visit->order?->order_number, 'customer' => $visit->order?->user?->name,
            'status' => __('hourly_routing.visit_statuses.'.$visit->status), 'url' => route('admin.hourly-visits.show', $visit->id),
        ];
    }

    /** آخر موقع لكل عاملة/سائق أرسل موقعه في اليوم. */
    private function tracked(Carbon $date, array $types, array $cityIds): array
    {
        if (!$types) return [];
        $ids = DB::table('location_pings')->whereBetween('recorded_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereIn('user_type', $types)->groupBy('user_type', 'user_id')->selectRaw('MAX(id) as id')->pluck('id');
        $rows = DB::table('location_pings')->whereIn('id', $ids)
            ->when($cityIds, fn ($q) => $q->whereIn('city_id', $cityIds))
            ->orderByDesc('recorded_at')->get();

        $names = $this->people($rows);
        $cities = \App\Models\City::whereIn('id', $rows->pluck('city_id')->filter()->unique())->get()->keyBy('id');

        return $rows->map(function ($row) use ($names, $cities) {
            $person = $names[$row->user_type][$row->user_id] ?? null;
            $at = Carbon::parse($row->recorded_at);

            return [
                'type' => $row->user_type, 'type_label' => __('hourly_routing.gps.types.'.$row->user_type), 'id' => (int) $row->user_id, 'source' => 'ping',
                'name' => $person->name ?? ('#'.$row->user_id), 'phone' => $person->phone ?? null,
                'lat' => (float) $row->lat, 'lng' => (float) $row->lng, 'accuracy' => $row->accuracy !== null ? (float) $row->accuracy : null,
                'speed' => $row->speed !== null ? (float) $row->speed : null, 'heading' => $row->heading !== null ? (int) $row->heading : null,
                'app_version' => $row->app_version, 'device_type' => $row->device_type,
                'city' => $cities[$row->city_id]->name ?? null, 'recorded_at' => $at->format('Y-m-d H:i'), 'ago' => $at->diffForHumans(),
                'active' => $this->isActive($at),
            ];
        })->all();
    }

    /**
     * العملاء من عنوان الطلب: من له زيارة في اليوم يظهر مفعّلًا بعنوان تلك الزيارة،
     * ومن له طلب قائم بلا زيارة في اليوم يظهر رماديًا بعنوان أحدث طلب.
     */
    private function customers(Carbon $date, array $cityIds): array
    {
        $visits = HourlyVisit::query()->whereBetween('scheduled_start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereNotIn('status', VisitSearch::CANCELLED)->whereNotNull('latitude')
            ->whereHas('order', fn ($o) => $o->whereNotIn('status', ['cancelled', 'expired']))
            ->when($cityIds, fn ($q) => $q->whereIn('city_id', $cityIds))
            ->with('order:id,order_number,user_id')->orderBy('scheduled_start_at')->get()
            ->unique(fn ($v) => $v->order?->user_id)->keyBy(fn ($v) => $v->order?->user_id);
        $orders = DB::table('hourly_rental_orders')->whereIn('status', ['active', 'pending_confirmation'])->whereNotNull('latitude')
            ->when($cityIds, fn ($q) => $q->whereIn('city_id', $cityIds))
            ->whereNotIn('user_id', $visits->keys()->filter()->all() ?: [0])
            ->orderByDesc('id')->limit(self::MAX_CUSTOMERS * 3)->get(['id', 'order_number', 'user_id', 'latitude', 'longitude', 'city_id', 'address_text'])
            ->unique('user_id')->take(max(0, self::MAX_CUSTOMERS - $visits->count()));

        $users = DB::table('users')->whereIn('id', $visits->keys()->merge($orders->pluck('user_id'))->filter()->unique()->all() ?: [0])->get(['id', 'name', 'phone'])->keyBy('id');
        $cities = \App\Models\City::whereIn('id', $visits->pluck('city_id')->merge($orders->pluck('city_id'))->filter()->unique())->get()->keyBy('id');
        $label = __('hourly_routing.gps.types.customer');
        $point = fn ($userId, $lat, $lng, $cityId, $address, $order, $active, $visit = null) => [
            'type' => 'customer', 'type_label' => $label, 'id' => (int) $userId, 'source' => 'address',
            'name' => $users[$userId]->name ?? ('#'.$userId), 'phone' => $users[$userId]->phone ?? null,
            'lat' => (float) $lat, 'lng' => (float) $lng, 'accuracy' => null, 'speed' => null, 'heading' => null, 'app_version' => null, 'device_type' => null,
            'city' => $cities[$cityId]->name ?? null, 'address' => $address, 'order_number' => $order, 'visit' => $visit,
            'recorded_at' => null, 'ago' => null, 'active' => $active,
        ];

        return $visits->map(fn ($v) => $point($v->order->user_id, $v->latitude, $v->longitude, $v->city_id, $v->address_text, $v->order->order_number, true,
                $v->scheduled_start_at->format('H:i').'–'.$v->scheduled_end_at?->format('H:i')))->values()
            ->concat($orders->map(fn ($o) => $point($o->user_id, $o->latitude, $o->longitude, $o->city_id, $o->address_text, $o->order_number, false)))
            ->all();
    }

    private function nearestCity($cities, float $lat, float $lng): ?int
    {
        $best = null;
        $bestKm = self::CITY_RADIUS_KM;
        foreach ($cities as $city) {
            $km = TeamAreaResolver::haversineKm($lat, $lng, (float) $city->latitude, (float) $city->longitude);
            if ($km <= $bestKm) [$best, $bestKm] = [$city->id, $km];
        }

        return $best;
    }

    /** الأسماء والجوالات من جدول كل جهة. */
    private function people($rows): array
    {
        $ids = fn (string $type) => $rows->where('user_type', $type)->pluck('user_id')->unique()->all() ?: [0];

        return [
            'maid' => DB::table('maids')->whereIn('id', $ids('maid'))->get(['id', 'name'])->keyBy('id')->all(),
            'driver' => DB::table('hourly_drivers')->whereIn('id', $ids('driver'))->get(['id', 'name', 'phone'])->keyBy('id')->all(),
        ];
    }
}
