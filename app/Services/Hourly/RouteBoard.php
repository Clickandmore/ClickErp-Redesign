<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyTeam, HourlyTeamMember, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * لوحة «إدارة المسار»: كل زيارة تولّد مهمتين للسائق، توصيل عند بدايتها واستلام عند نهايتها.
 * وقت المهمة هو delivery_at / pickup_at إن ضُبط، وإلا بداية الزيارة / نهايتها (نفس COALESCE في checkDrivers).
 * المهام تُشتق من الزيارات؛ المخزَّن فقط ترتيب السائق في hourly_route_stops، والتعيين يمر عبر assign_driver القائم.
 */
class RouteBoard
{
    /** المفاتيح فقط؛ النصوص من lang/{ar,en}/hourly_routing.php بحسب لغة الواجهة. */
    public const ROLES = ['delivery', 'pickup'];
    public const PERIODS = ['all', 'am', 'pm', 'custom'];
    /** بعد هذه الحالات تكون العاملة قد وصلت للعميل، فمهمة التوصيل منتهية. */
    private const DELIVERED = ['delivered', 'in_progress', 'worker_finished', 'picked_up', 'completed'];
    private const PICKED = ['picked_up', 'completed'];

    /**
     * @param array{service_id?:int,driver_id?:int,team_id?:int,period?:string,from?:string,to?:string} $filters
     * @return array{drivers:array,unassigned:array,cancelled:array,understaffed:array,counters:array,client_to_client:bool,warning_km:int}
     */
    public function build(Carbon $date, array $filters): array
    {
        $visits = HourlyVisit::query()
            ->whereBetween('scheduled_start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereHas('order', fn ($o) => $o->when($filters['service_id'] ?? null, fn ($q, $id) => $q->where('service_category_id', $id)))
            ->when($filters['team_id'] ?? null, fn ($q, $id) => $q->where('hourly_team_id', $id))
            ->with(['workers:id,name', 'order:id,order_number,user_id,status', 'order.user:id,name,phone'])
            ->orderBy('scheduled_start_at')->orderBy('id')->get();

        // الملغاة تُعرض مهما كانت حالة الطلب (إلغاء آخر زيارة يلغي الطلب نفسه)؛ والمهام من طلبات قائمة فقط.
        $cancelled = $visits->filter(fn ($v) => in_array($v->status, VisitSearch::CANCELLED, true))->values();
        $visits = $visits->diff($cancelled)->reject(fn ($v) => in_array($v->order?->status, ['cancelled', 'expired'], true))->values();
        $teams = HourlyTeam::whereIn('id', $visits->pluck('hourly_team_id')->filter()->unique())->get(['id', 'name', 'base_lat', 'base_lng'])->keyBy('id');
        $cities = \App\Models\City::whereIn('id', $visits->pluck('city_id')->filter()->unique())->get()->keyBy('id');
        $stops = $this->stops($visits->pluck('id')->all());
        $warning = (int) HourlyRentalSettings::get('route_distance_warning_km', 50);

        $tasks = $visits->flatMap(fn ($visit) => array_map(fn ($role) => $this->task($visit, $role, $cities, $stops), self::ROLES))
            ->filter(fn ($task) => $this->inWindow($task['time'], $filters))
            ->when($filters['driver_id'] ?? null, fn ($c, $id) => $c->where('driver_id', (int) $id))
            ->sortBy(fn ($task) => $this->sortKey($task, false))
            ->values();

        $clientToClient = (bool) HourlyRentalSettings::get('route_client_to_client');
        $drivers = HourlyDriver::withTrashed()->whereIn('id', $tasks->pluck('driver_id')->filter()->unique())->orderBy('name')->get();
        $primary = HourlyTeamMember::where('member_type', HourlyTeamMember::TYPE_DRIVER)->where('role', HourlyTeamMember::ROLE_PRIMARY)->whereNull('left_at')
            ->whereIn('member_id', $drivers->pluck('id'))->with('team:id,name,base_lat,base_lng')->get()->keyBy('member_id');
        $assigned = $this->assignedVisits($drivers->pluck('id')->all(), $date);

        $cards = $drivers->map(function (HourlyDriver $driver) use ($tasks, $teams, $primary, $clientToClient, $date, $assigned, $warning) {
            $team = $primary[$driver->id]->team ?? null;
            // الترتيب المحفوظ بالسحب أولًا، ثم المهام الجديدة بوقتها.
            $own = $tasks->where('driver_id', $driver->id)->sortBy(fn ($t) => $this->sortKey($t, true))->values();
            // مقر الفريق الأساسي للسائق، وإن لم يكن له إحداثيات فمقر فريق أول زيارة في مساره.
            $base = $this->base($team) ?? $this->base($teams[$own->first()['team_id'] ?? 0] ?? null);
            $own = $this->measure($own, $base, $clientToClient, $warning);
            $seats = (int) $driver->vehicle_capacity;

            return [
                'id' => $driver->id, 'name' => $driver->name, 'phone' => $driver->phone, 'hours' => $this->workHours($driver, $date),
                'team' => $team?->name, 'base' => $base, 'tasks' => $own->all(), 'done' => $own->where('done', true)->count(),
                'km' => round($own->sum('km'), 2), 'state' => $this->driverState($own),
                // «زيارات مخصصة / المقاعد» على مستوى اليوم كله، بلا فلاتر الفترة.
                'assigned' => count($assigned[$driver->id] ?? []), 'seats' => $seats ?: null,
                'over' => $seats > 0 && count($assigned[$driver->id] ?? []) > $seats,
            ];
        })->values()->all();

        $unassigned = $tasks->whereNull('driver_id')
            ->map(fn ($task) => $this->measure(collect([$task]), $this->base($teams[$task['team_id']] ?? null), false, $warning)->first())->values()->all();
        $understaffed = $visits->filter(fn ($v) => $v->workers->count() < (int) $v->workers_count && !in_array($v->status, VisitSearch::CLOSED, true))->values();

        return [
            'drivers' => $cards,
            'unassigned' => $unassigned,
            'cancelled' => $this->cancelledRows($cancelled),
            'understaffed' => $understaffed->map(fn ($v) => $this->visitRow($v) + ['filled' => $v->workers->count(), 'required' => (int) $v->workers_count])->all(),
            'client_to_client' => $clientToClient,
            'warning_km' => $warning,
            'counters' => [
                'routes' => count($cards),
                'unassigned' => $understaffed->count(),
                'cancelled' => $cancelled->count(),
                'without_driver' => count($unassigned),
            ],
        ];
    }

    /**
     * تحويل مهام (من سائق أو عدة سائقين أو بلا سائق) إلى سائق واحد دفعة واحدة، أو لا شيء.
     * كل مهمة تمر عبر assign_driver القائم (عضوية الفريق، جدول العمل، فاصل 30 دقيقة، والسجل)، بنفس وقتها.
     *
     * @param string[] $keys مفاتيح المهام "{visit}-{role}" كما عرضتها الصفحة
     * @param array<string, int|null> $expected السائق الحالي لكل مهمة كما رأته الصفحة (null = بلا سائق)
     * @param bool $bothSides تعيين نفس السائق للطرف الآخر من الزيارة أيضًا (إن لم يُنفَّذ بعد)
     * @param bool $confirmCapacity تم تأكيد تجاوز مقاعد المركبة
     * @throws RouteCapacityExceeded
     */
    public function transfer(array $keys, int $to, string $reason, int $admin, array $expected, bool $bothSides = false, bool $confirmCapacity = false): int
    {
        $pairs = $this->pairs($keys);

        return DB::transaction(function () use ($pairs, $to, $reason, $admin, $expected, $bothSides, $confirmCapacity) {
            $driver = HourlyDriver::whereKey($to)->lockForUpdate()->first();
            if (!$driver) HourlyOrderService::reject(__('hourly_routing.messages.bad_tasks'));
            $visits = HourlyVisit::whereIn('id', $pairs->pluck(0))->with('order')->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $moves = collect();
            foreach ($pairs as [$id, $role]) {
                $visit = $visits[$id] ?? null;
                $current = $visit && $visit->{$role.'_driver_id'} ? (int) $visit->{$role.'_driver_id'} : null;
                $seen = array_key_exists($id.'-'.$role, $expected) && $expected[$id.'-'.$role] !== null ? (int) $expected[$id.'-'.$role] : null;
                if (!$visit || $current !== $seen) HourlyOrderService::reject(__('hourly_routing.messages.changed'));
                if (!$this->openTask($visit->status, $role)) HourlyOrderService::reject(__('hourly_routing.messages.task_done', ['role' => __('hourly_routing.roles.'.$role), 'number' => $visit->visit_number]));
                if ($current !== $to) $moves->put($id.'-'.$role, [$visit, $role]);
            }
            // الطرف الآخر من الزيارة يُنقل معها إن كان مفتوحًا ولم يكن على السائق الجديد.
            if ($bothSides) {
                foreach ($pairs as [$id, $role]) {
                    $other = $role === 'delivery' ? 'pickup' : 'delivery';
                    $visit = $visits[$id];
                    if (!$moves->has($id.'-'.$other) && (int) $visit->{$other.'_driver_id'} !== $to && $this->openTask($visit->status, $other)) {
                        $moves->put($id.'-'.$other, [$visit, $other]);
                    }
                }
            }
            if ($moves->isEmpty()) HourlyOrderService::reject(__('hourly_routing.messages.same_driver'));

            if (!$confirmCapacity) $this->assertCapacity($driver, $moves->map(fn ($m) => $m[0])->unique('id'));

            $manager = app(OrderManagementService::class);
            foreach ($moves->sortBy(fn ($m) => sprintf('%010d|%010d|%s', $m[0]->hourly_rental_order_id, $m[0]->id, $m[1])) as [$visit, $role]) {
                $manager->apply($visit->order->fresh(), ['action' => 'assign_driver', 'visit_ids' => [$visit->id], 'driver_id' => $to, 'driver_role' => $role, 'driver_time' => $this->taskTime($visit, $role)->format('H:i'), 'reason' => $reason], $admin);
            }
            // ترتيب السائق السابق لا ينطبق على السائق الجديد.
            foreach ($moves as [$visit, $role]) DB::table('hourly_route_stops')->where('hourly_visit_id', $visit->id)->where('role', $role)->delete();

            return $moves->count();
        });
    }

    /**
     * حفظ ترتيب مهام سائق في يوم. تكفي المهام الظاهرة (مثل نافذة «قبل الظهر»):
     * تُعاد ترتيبًا داخل مواقعها في مسار اليوم الكامل، ثم يُرقَّم المسار كله 1..n.
     *
     * @param string[] $keys
     * @return string[] الترتيب الكامل بعد الحفظ
     */
    public function reorder(int $driverId, Carbon $date, array $keys): array
    {
        $keys = $this->pairs($keys)->map(fn ($p) => implode('-', $p))->all();

        return DB::transaction(function () use ($driverId, $date, $keys) {
            if (!HourlyDriver::whereKey($driverId)->lockForUpdate()->exists()) HourlyOrderService::reject(__('hourly_routing.messages.changed'));
            $card = collect($this->build($date, ['driver_id' => $driverId])['drivers'])->firstWhere('id', $driverId);
            $full = $card ? array_column($card['tasks'], 'key') : [];
            if (array_diff($keys, $full)) HourlyOrderService::reject(__('hourly_routing.messages.changed'));
            foreach (array_values(array_keys(array_intersect($full, $keys))) as $i => $position) $full[$position] = $keys[$i];

            foreach ($full as $order => $key) {
                [$visit, $role] = explode('-', $key, 2);
                DB::table('hourly_route_stops')->updateOrInsert(
                    ['hourly_visit_id' => (int) $visit, 'role' => $role],
                    ['hourly_driver_id' => $driverId, 'route_date' => $date->toDateString(), 'display_order' => $order + 1, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            return $full;
        });
    }

    /**
     * الزيارات المسندة لكل سائق في اليوم (توصيل أو استلام)، بلا الملغاة.
     *
     * @param int[] $driverIds
     * @return array<int, int[]> driver_id => visit ids
     */
    public function assignedVisits(array $driverIds, Carbon $date): array
    {
        if (!$driverIds) return [];
        $rows = HourlyVisit::whereBetween('scheduled_start_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->whereNotIn('status', VisitSearch::CANCELLED)
            ->whereHas('order', fn ($o) => $o->whereNotIn('status', ['cancelled', 'expired']))
            ->where(fn ($q) => $q->whereIn('delivery_driver_id', $driverIds)->orWhereIn('pickup_driver_id', $driverIds))
            ->get(['id', 'delivery_driver_id', 'pickup_driver_id']);
        $result = [];
        foreach ($rows as $row) {
            foreach (array_unique(array_filter([(int) $row->delivery_driver_id, (int) $row->pickup_driver_id])) as $id) {
                if (in_array($id, $driverIds, true)) $result[$id][] = $row->id;
            }
        }

        return $result;
    }

    /** مهمة يمكن تغيير سائقها: الزيارة غير مغلقة والمهمة نفسها لم تُنفَّذ بعد. */
    public function openTask(string $status, string $role): bool
    {
        return !in_array($status, VisitSearch::CLOSED, true) && in_array($this->state($status, $role)[0], ['pending', 'moving', 'ready'], true);
    }

    /** وقت المهمة: الوقت المضبوط للسائق، وإلا بداية الزيارة للتوصيل ونهايتها للاستلام. */
    public function taskTime(HourlyVisit $visit, string $role): Carbon
    {
        $anchor = $role === 'delivery' ? $visit->scheduled_start_at : ($visit->scheduled_end_at ?? $visit->scheduled_start_at);

        return $visit->{$role.'_at'} ? Carbon::parse($visit->{$role.'_at'}) : $anchor->copy();
    }

    /** @return array{0:string,1:string} */
    public function state(string $status, string $role): array
    {
        $label = fn (string $state, string $text) => [$state, __('hourly_routing.states.'.$text)];
        if ($status === 'no_show') return $label('failed', 'failed');
        if ($role === 'delivery') {
            if (in_array($status, self::DELIVERED, true)) return $label('done', 'delivered');
            return $status === 'en_route' ? $label('moving', 'moving') : $label('pending', 'pending');
        }
        if (in_array($status, self::PICKED, true)) return $label('done', 'picked');

        return $status === 'worker_finished' ? $label('ready', 'ready') : $label('pending', 'pending');
    }

    /** نافذة اليوم: قبل الظهر < 12:00، بعد الظهر ≥ 12:00، والمخصصة [من، إلى). */
    public function inWindow(string $time, array $filters): bool
    {
        $clock = substr($time, 11, 5);

        return match ($filters['period'] ?? 'all') {
            'am' => $clock < '12:00',
            'pm' => $clock >= '12:00',
            'custom' => (!isset($filters['from']) || $clock >= $filters['from']) && (!isset($filters['to']) || $clock < $filters['to']),
            default => true,
        };
    }

    /** @return Collection<int, array{0:string,1:string}> أزواج [visit, role] صالحة بلا تكرار */
    private function pairs(array $keys): Collection
    {
        $pairs = collect($keys)->map(fn ($key) => explode('-', (string) $key, 2))
            ->filter(fn ($p) => count($p) === 2 && ctype_digit($p[0]) && in_array($p[1], self::ROLES, true))->unique(fn ($p) => implode('-', $p))->values();
        if ($pairs->isEmpty() || $pairs->count() !== count(array_unique($keys))) HourlyOrderService::reject(__('hourly_routing.messages.bad_tasks'));

        return $pairs;
    }

    /** يرفض التحويل إن تجاوزت زيارات السائق الجديد في أي يوم عدد مقاعد مركبته. */
    private function assertCapacity(HourlyDriver $driver, Collection $visits): void
    {
        $seats = (int) $driver->vehicle_capacity;
        if ($seats <= 0) return;
        foreach ($visits->groupBy(fn ($v) => $v->scheduled_start_at->toDateString()) as $day => $moving) {
            $ids = array_unique(array_merge($this->assignedVisits([$driver->id], Carbon::parse($day))[$driver->id] ?? [], $moving->pluck('id')->all()));
            if (count($ids) > $seats) throw new RouteCapacityExceeded($driver->name, $day, count($ids), $seats);
        }
    }

    /** @return array<string, array{driver:int,order:int}> مفتاح المهمة => ترتيبها المحفوظ لسائقها */
    private function stops(array $visitIds): array
    {
        if (!$visitIds) return [];

        return DB::table('hourly_route_stops')->whereIn('hourly_visit_id', $visitIds)->get()
            ->mapWithKeys(fn ($s) => [$s->hourly_visit_id.'-'.$s->role => ['driver' => (int) $s->hourly_driver_id, 'order' => (int) $s->display_order]])->all();
    }

    private function sortKey(array $task, bool $ordered): string
    {
        $order = $ordered && $task['display_order'] !== null ? $task['display_order'] : 999999;

        return sprintf('%06d|%s|%d|%010d', $order, $task['time'], $task['role'] === 'delivery' ? 0 : 1, $task['visit_id']);
    }

    private function task(HourlyVisit $visit, string $role, Collection $cities, array $stops): array
    {
        $time = $this->taskTime($visit, $role);
        [$state, $label] = $this->state($visit->status, $role);
        $closed = in_array($visit->status, VisitSearch::CLOSED, true);
        $driver = $visit->{$role.'_driver_id'} ? (int) $visit->{$role.'_driver_id'} : null;
        $stop = $stops[$visit->id.'-'.$role] ?? null;

        return $this->visitRow($visit) + [
            'key' => $visit->id.'-'.$role, 'role' => $role, 'role_label' => __('hourly_routing.roles.'.$role),
            'time' => $time->format('Y-m-d H:i'), 'clock' => $time->format('H:i'),
            'driver_id' => $driver, 'team_id' => $visit->hourly_team_id,
            // الترتيب المحفوظ يُعتمد فقط ما دام السائق نفسه.
            'display_order' => $stop && $driver && $stop['driver'] === $driver ? $stop['order'] : null,
            'state' => $state, 'state_label' => $label, 'done' => in_array($state, ['done', 'failed'], true),
            // التحويل متاح لمهمة لم تنتهِ على زيارة غير مغلقة، لأن assign_driver يرفض الزيارات المغلقة.
            'movable' => !$closed && !in_array($state, ['done', 'failed'], true),
            'updated' => $state === 'pending' ? null : $visit->updated_at?->format('H:i'),
            'city' => $cities[$visit->city_id]->name ?? null, 'district' => $visit->district, 'address' => $visit->address_text,
            'lat' => $visit->latitude !== null ? (float) $visit->latitude : null, 'lng' => $visit->longitude !== null ? (float) $visit->longitude : null,
            'map_url' => $visit->map_url, 'km' => null, 'far' => false,
        ];
    }

    /** بيانات الزيارة المشتركة بين المهام والأقسام: العميل والطلب والعاملات بروابطها. */
    private function visitRow(HourlyVisit $visit): array
    {
        return [
            'visit_id' => $visit->id, 'visit_number' => $visit->visit_number, 'visit_url' => route('admin.hourly-visits.show', $visit->id),
            'order_id' => $visit->hourly_rental_order_id, 'order_number' => $visit->order?->order_number, 'order_url' => route('admin.hourly-orders.show', $visit->hourly_rental_order_id),
            'customer' => $visit->order?->user?->name, 'phone' => $visit->contact_phone ?: $visit->order?->user?->phone,
            'workers' => $visit->workers->pluck('name')->implode(app()->getLocale() === 'ar' ? '، ' : ', ') ?: null,
            'worker_list' => $visit->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'url' => route('admin.hourly-maids.show', $w->id)])->values()->all(),
            'window' => $visit->scheduled_start_at?->format('H:i').'–'.$visit->scheduled_end_at?->format('H:i'),
        ];
    }

    private function cancelledRows(Collection $cancelled): array
    {
        if ($cancelled->isEmpty()) return [];
        $reasons = DB::table('hourly_order_audits')->whereIn('visit_id', $cancelled->pluck('id'))->whereIn('action', ['cancel_visit', 'cancel_order'])
            ->orderByDesc('id')->get(['visit_id', 'reason'])->unique('visit_id')->pluck('reason', 'visit_id');

        return $cancelled->map(fn ($v) => $this->visitRow($v) + [
            'status' => $v->status, 'status_label' => __('hourly_routing.visit_statuses.'.$v->status), 'reason' => $reasons[$v->id] ?? null,
        ])->all();
    }

    /** المسافة المستقيمة بالكيلومتر: من المقر لكل مهمة، أو من موقع المهمة السابقة عند «من عميل إلى عميل». */
    private function measure(Collection $tasks, ?array $base, bool $clientToClient, int $warning): Collection
    {
        $from = $base;

        return $tasks->map(function ($task) use (&$from, $base, $clientToClient, $warning) {
            if ($task['lat'] !== null && $task['lng'] !== null) {
                $origin = $clientToClient ? $from : $base;
                $task['km'] = $origin ? round(TeamAreaResolver::haversineKm($origin['lat'], $origin['lng'], $task['lat'], $task['lng']), 2) : null;
                $task['far'] = $task['km'] !== null && $task['km'] > $warning;
                $from = ['lat' => $task['lat'], 'lng' => $task['lng']];
            }

            return $task;
        });
    }

    private function base(?HourlyTeam $team): ?array
    {
        return $team && $team->base_lat !== null && $team->base_lng !== null
            ? ['lat' => (float) $team->base_lat, 'lng' => (float) $team->base_lng, 'name' => $team->name] : null;
    }

    private function workHours(HourlyDriver $driver, Carbon $date): string
    {
        $day = (array) ($driver->work_schedule[strtolower($date->format('l'))] ?? []);

        return match ($day['mode'] ?? 'off') {
            'all_day' => __('hourly_routing.hours.all_day'),
            'hours' => ($day['start'] ?? '—').' - '.($day['end'] ?? '—'),
            default => __('hourly_routing.hours.off'),
        };
    }

    /** @return array{0:string,1:string} */
    private function driverState(Collection $tasks): array
    {
        $state = $tasks->isNotEmpty() && $tasks->every(fn ($t) => $t['done']) ? 'done'
            : ($tasks->contains(fn ($t) => $t['state'] !== 'pending') ? 'moving' : 'pending');

        return [$state, __('hourly_routing.driver_states.'.$state)];
    }
}
