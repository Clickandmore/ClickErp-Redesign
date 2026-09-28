<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyTeam, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * لوحة تطبيق السائق: تحوّل المهام المشتقة من الزيارات (توصيل عند البداية، استلام عند النهاية)
 * إلى «مشاوير ومحطات» بالشكل الذي يقرأه التطبيق.
 *
 * - المشوار = صف في hourly_driver_trips لكل (سائق + يوم + دور)، يُنشأ عند أول قراءة.
 * - المحطة = صف hourly_route_stops لكل (زيارة + دور) وهو نفس الصف الذي تستخدمه «إدارة المسار»
 *   للترتيب، فلا يوجد جدول موازٍ ولا معرّف موازٍ. تحويل المهمة لسائق آخر يحذف الصف فيبدأ الجديد نظيفًا.
 * - حالة التنفيذ الحقيقية تبقى في hourly_visits.status (نفس انتقالات بوابة السائق على الويب).
 */
class DriverAppService
{
    public const ROLES = ['delivery', 'pickup'];
    /** حالات لا تُعرض للسائق إطلاقًا. */
    public const VOID = ['cancelled', 'cancelled_by_client', 'expired', 'no_show'];
    /** بعدها تكون العاملة عند العميل: مهمة التوصيل منتهية (نفس قائمة RouteBoard). */
    public const DELIVERED = ['delivered', 'in_progress', 'worker_finished', 'picked_up', 'completed'];
    public const RETURNED = ['completed'];

    public function today(HourlyDriver $driver, ?string $date = null): array
    {
        return array_values(array_filter($this->sync($driver, $date), fn ($trip) => $trip['status'] !== 'offered'));
    }

    /** العروض المفتوحة: لا تظهر إلا إذا فُعّل staff_app_driver_acceptance. */
    public function offers(HourlyDriver $driver): array
    {
        return array_map(fn ($trip) => [
            'trip_id' => $trip['id'], 'id' => $trip['id'],
            'title' => ($trip['type'] === 'delivery' ? 'مشوار توصيل' : 'مشوار استلام').($trip['area'] ? ' — '.$trip['area'] : ''),
            'type' => $trip['type'], 'stops_count' => $trip['stops_count'], 'capacity_needed' => $trip['stops_count'],
            'distance_km' => null, 'planned_start_at' => $trip['planned_start_at'], 'expires_at' => $trip['expires_at'],
        ], array_values(array_filter($this->sync($driver, null), fn ($trip) => $trip['status'] === 'offered')));
    }

    public function accept(HourlyDriver $driver, int $tripId): array
    {
        return $this->decide($driver, $tripId, function ($trip) use ($driver) {
            if ($trip->status !== 'offered') HourlyOrderService::reject('العرض لم يعد متاحًا أو سبق الرد عليه.');
            if ($trip->expires_at && now()->gt($trip->expires_at)) HourlyOrderService::reject('انتهت مدة العرض.');
            $stops = DB::table('hourly_route_stops')->where('hourly_driver_trip_id', $trip->id)->count();
            $seats = (int) $driver->vehicle_capacity;
            if ($seats > 0 && $stops > $seats) HourlyOrderService::reject('عدد محطات المشوار يتجاوز سعة المركبة ('.$seats.').');
            return ['status' => 'accepted', 'accepted_at' => now()];
        });
    }

    public function reject(HourlyDriver $driver, int $tripId, string $reason): array
    {
        return $this->decide($driver, $tripId, function ($trip) use ($reason) {
            if ($trip->status !== 'offered') HourlyOrderService::reject('العرض لم يعد متاحًا أو سبق الرد عليه.');
            return ['status' => 'rejected', 'reject_reason' => mb_substr($reason, 0, 200)];
        });
    }

    public function start(HourlyDriver $driver, int $tripId): array
    {
        return $this->decide($driver, $tripId, function ($trip) {
            if ($trip->status === 'started') return [];
            if ($trip->status !== 'accepted') HourlyOrderService::reject('المشوار غير مقبول أو منتهٍ؛ لا يمكن بدؤه.');
            return ['status' => 'started', 'started_at' => now()];
        });
    }

    /** متاح / غير متاح. لا يُسمح بالإغلاق ومعه مشوار جارٍ أو عاملة في كشف العودة. */
    public function shift(HourlyDriver $driver, bool $online): array
    {
        if (!$online) {
            $this->sync($driver, null);
            if (DB::table('hourly_driver_trips')->where('hourly_driver_id', $driver->id)->where('status', 'started')->exists()) {
                HourlyOrderService::reject('لديك مشوار جارٍ؛ أكمل محطاته قبل التحول إلى «غير متاح».');
            }
            if ($this->onboard($driver)->isNotEmpty()) HourlyOrderService::reject('لديك عاملات في كشف العودة؛ أعدهن للإيواء أولًا.');
        }
        $driver->forceFill(['is_online' => $online, 'last_shift_at' => now()])->save();
        return ['is_online' => $online, 'changed_at' => now()->toIso8601String()];
    }

    public function pickupRequests(HourlyDriver $driver): array
    {
        $this->sync($driver, null);
        $requests = $this->tasks($driver, null)
            ->filter(fn ($task) => $task['role'] === 'pickup' && $task['visit']->status === 'worker_finished')
            ->map(fn ($task) => [
                'stop_id' => $task['stop_id'], 'visit_id' => $task['visit']->id,
                'worker' => $this->worker($task['visit']), 'address' => $task['visit']->address_text,
                'finished_at' => $task['visit']->updated_at?->toIso8601String(),
                'latitude' => (float) $task['visit']->latitude, 'longitude' => (float) $task['visit']->longitude,
            ])->values()->all();

        return ['requests' => $requests, 'onboard' => $this->onboard($driver)->values()->all()];
    }

    /** مستحقات مؤقتة بسعر المحطة من الإعدادات حتى تُبنى المرحلة ٤ (hourly_visit_earnings). */
    public function earnings(HourlyDriver $driver): array
    {
        $rate = (float) HourlyRentalSettings::get('staff_app_driver_stop_rate', 0);
        $rows = DB::table('hourly_route_stops')->where('hourly_driver_id', $driver->id)->where('status', 'completed')
            ->selectRaw('route_date, count(*) as stops')->groupBy('route_date')->orderByDesc('route_date')->limit(60)->get();
        $items = $rows->map(fn ($row) => [
            'trip_number' => (string) $row->route_date, 'date' => (string) $row->route_date,
            'stops_count' => (int) $row->stops, 'amount' => round($rate * (int) $row->stops, 2), 'status' => 'accrued',
        ])->all();
        $today = $rows->firstWhere('route_date', now()->toDateString());

        return [
            'currency' => 'SAR',
            'today' => ['trips_count' => $today ? 1 : 0, 'stops_count' => (int) ($today->stops ?? 0), 'amount' => round($rate * (int) ($today->stops ?? 0), 2)],
            'total_due' => round(array_sum(array_column($items, 'amount')), 2),
            'items' => $items,
            'note' => $rate > 0 ? null : 'لم تُضبط قيمة المحطة في إعدادات الساعات بعد.',
        ];
    }

    /** العاملات مع السائق الآن (سُلّمن من العميل ولم يُعَدن للإيواء). */
    public function onboard(HourlyDriver $driver): Collection
    {
        return $this->tasks($driver, null)
            ->filter(fn ($task) => $task['role'] === 'pickup' && $task['visit']->status === 'picked_up')
            ->map(fn ($task) => ['stop_id' => $task['stop_id'], 'visit_id' => $task['visit']->id,
                'worker' => $this->worker($task['visit']),
                'shelter' => $task['team'] ? ['id' => $task['team']->id, 'name' => $task['team']->name] : null]);
    }

    /** بناء (أو تحديث) مشاوير اليوم ومحطاتها ثم إرجاعها بشكل التطبيق. */
    public function sync(HourlyDriver $driver, ?string $date): array
    {
        $day = Carbon::parse($date ?: now()->toDateString())->toDateString();
        $tasks = $this->tasks($driver, $day);
        $trips = [];
        foreach (self::ROLES as $role) {
            $own = $tasks->where('role', $role)->sortBy('time')->values();
            if ($own->isEmpty()) continue;
            $trip = $this->trip($driver, $day, $role, $own);
            $stops = [];
            foreach ($own as $index => $task) {
                $this->stop($driver, $trip, $task, $index + 1);
                $stops[] = $this->stopPayload($task, $index + 1);
            }
            $this->closeTrip($trip, $stops);
            $team = $own->first()['team'] ?? null;
            $order = $own->first()['visit']->order;
            $trips[] = [
                'id' => (int) $trip->id, 'trip_number' => (string) $trip->id, 'type' => $role,
                'route_date' => $day,
                // الطلب غير النشط يُعرض للسائق للعلم، وإجراءات محطاته مرفوضة حتى يُفعَّل الطلب.
                'order_status' => $order?->status, 'actionable' => $order?->status === 'active',
                'status' => (string) (DB::table('hourly_driver_trips')->where('id', $trip->id)->value('status') ?? $trip->status), 'team' => $team ? ['id' => $team->id, 'name' => $team->name] : null,
                'area' => $own->first()['visit']->district ?: null,
                'planned_start_at' => $trip->planned_start_at ? Carbon::parse($trip->planned_start_at)->toIso8601String() : null,
                'expires_at' => $trip->expires_at ? Carbon::parse($trip->expires_at)->toIso8601String() : null,
                'distance_km' => null, 'stops_count' => count($stops), 'stops' => $stops,
            ];
        }
        return $trips;
    }

    /**
     * مشاوير السائق في مدى تواريخ (السابقة والحالية والقادمة) مع تشخيص يوضح سبب الفراغ إن حدث.
     * الشكل كائن لا قائمة، لأنه مسار جديد لا يعتمد عليه أي إصدار منشور من التطبيق.
     */
    public function history(HourlyDriver $driver, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        if ($end->lt($start)) [$start, $end] = [$end, $start];
        // سقف 62 يومًا حتى لا يبني الطلب مشاوير سنة كاملة.
        if ($start->diffInDays($end) > 62) $start = $end->copy()->subDays(62);

        $trips = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            foreach ($this->sync($driver, $day->toDateString()) as $trip) $trips[] = $trip;
        }
        $days = collect($trips)->groupBy('route_date')->map(fn ($group, $date) => [
            'date' => (string) $date, 'trips_count' => $group->count(), 'stops_count' => (int) $group->sum('stops_count'),
            'completed' => $group->every(fn ($trip) => $trip['status'] === 'completed'),
        ])->values()->all();

        return ['from' => $start->toDateString(), 'to' => $end->toDateString(),
            'days' => $days, 'trips' => $trips, 'diagnostics' => $this->diagnostics($driver, $start, $end)];
    }

    /** لماذا القائمة فارغة؟ أرقام تُغني عن فتح قاعدة البيانات. */
    public function diagnostics(HourlyDriver $driver, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = fn () => HourlyVisit::query()->where(fn ($q) => $q->where('delivery_driver_id', $driver->id)->orWhere('pickup_driver_id', $driver->id));
        $all = $base()->count();
        $inRange = $from ? $base()->whereDate('scheduled_start_at', '>=', $from->toDateString())
            ->whereDate('scheduled_start_at', '<=', ($to ?: $from)->toDateString())->count() : $all;
        $void = $base()->whereIn('status', self::VOID)->count();
        $closedOrders = $base()->whereHas('order', fn ($q) => $q->whereIn('status', ['cancelled', 'expired']))->count();
        $unscheduled = $base()->whereNull('scheduled_start_at')->count();
        $leaderOnly = HourlyVisit::query()->where('team_leader_id', $driver->id)
            ->where(fn ($q) => $q->whereNull('delivery_driver_id')->orWhere('delivery_driver_id', '!=', $driver->id))
            ->where(fn ($q) => $q->whereNull('pickup_driver_id')->orWhere('pickup_driver_id', '!=', $driver->id))->count();
        $dates = $base()->whereNotNull('scheduled_start_at')->selectRaw('MIN(scheduled_start_at) as first_at, MAX(scheduled_start_at) as last_at')->first();

        return [
            'assigned_visits_total' => $all,
            'assigned_visits_in_range' => $inRange,
            'excluded_cancelled_visits' => $void,
            'excluded_cancelled_or_expired_orders' => $closedOrders,
            'excluded_without_schedule' => $unscheduled,
            'team_leader_only_visits' => $leaderOnly,
            'first_assigned_at' => $dates->first_at ?? null,
            'last_assigned_at' => $dates->last_at ?? null,
            'today' => now()->toDateString(),
            'hint' => $this->hint($all, $inRange, $void, $closedOrders, $unscheduled, $leaderOnly),
        ];
    }

    private function hint(int $all, int $inRange, int $void, int $closedOrders, int $unscheduled, int $leaderOnly): ?string
    {
        if ($all === 0) {
            return $leaderOnly > 0
                ? 'السائق قائد فريق على '.$leaderOnly.' زيارة لكنه ليس سائق توصيل ولا استلام فيها؛ استخدم «تعيين سائق» لدور التوصيل أو الاستلام.'
                : 'لا توجد زيارة مُسنَدة لهذا السائق إطلاقًا (delivery_driver_id / pickup_driver_id).';
        }
        if ($inRange === 0) return 'للسائق '.$all.' زيارة مُسنَدة لكن لا شيء منها في المدى المطلوب؛ وسّع from و to.';
        if ($void === $all) return 'كل زيارات السائق ملغاة أو منتهية.';
        if ($closedOrders === $all) return 'كل زياراته تتبع طلبات ملغاة أو منتهية الصلاحية.';
        if ($unscheduled === $all) return 'زياراته بلا موعد محدد بعد (scheduled_start_at فارغ).';
        return null;
    }

    /**
     * المهام المشتقة: زيارة + دور + وقت + محطة مضمونة.
     * حالة الطلب تُستثنى كما تستثنيها لوحة «إدارة المسار» (الملغى والمنتهي فقط)، فما يراه المرسِل يراه السائق؛
     * والطلب غير النشط يُعرض للعلم بلا إجراء (actionable = false).
     */
    private function tasks(HourlyDriver $driver, ?string $from, ?string $to = null): Collection
    {
        $query = HourlyVisit::query()
            ->where(fn ($q) => $q->where('delivery_driver_id', $driver->id)->orWhere('pickup_driver_id', $driver->id))
            ->whereNotIn('status', self::VOID)
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'expired']))
            ->with(['order:id,order_number,user_id,status,payment_method,payment_status,total_amount,paid_amount,package_snapshot', 'workers:id,name']);
        if ($from) $query->whereDate('scheduled_start_at', '>=', $from)->whereDate('scheduled_start_at', '<=', $to ?: $from);
        else $query->whereDate('scheduled_start_at', '>=', now()->subDay()->toDateString())->whereDate('scheduled_start_at', '<=', now()->addDay()->toDateString());
        $visits = $query->orderBy('scheduled_start_at')->orderBy('id')->get();
        $teams = HourlyTeam::whereIn('id', $visits->pluck('hourly_team_id')->filter()->unique())->get(['id', 'name'])->keyBy('id');
        $stops = DB::table('hourly_route_stops')->whereIn('hourly_visit_id', $visits->pluck('id'))->get()
            ->mapWithKeys(fn ($row) => [$row->hourly_visit_id.'-'.$row->role => $row]);

        return $visits->flatMap(function (HourlyVisit $visit) use ($driver, $teams, $stops) {
            $out = [];
            foreach (self::ROLES as $role) {
                if ((int) $visit->{$role.'_driver_id'} !== (int) $driver->id) continue;
                $row = $stops->get($visit->id.'-'.$role);
                $out[] = [
                    'role' => $role, 'visit' => $visit, 'team' => $teams[$visit->hourly_team_id] ?? null,
                    'time' => $role === 'delivery' ? ($visit->delivery_at ?? $visit->scheduled_start_at) : ($visit->pickup_at ?? $visit->scheduled_end_at),
                    'row' => $row, 'stop_id' => $row->id ?? null,
                ];
            }
            return $out;
        });
    }

    private function trip(HourlyDriver $driver, string $day, string $role, Collection $tasks): object
    {
        $planned = $tasks->first()['time'];
        $acceptance = (bool) HourlyRentalSettings::get('staff_app_driver_acceptance', false);
        $existing = DB::table('hourly_driver_trips')->where('hourly_driver_id', $driver->id)->where('route_date', $day)->where('role', $role)->first();
        if (!$existing) {
            $minutes = (int) HourlyRentalSettings::get('offer_timeout_minutes', 10);
            $id = DB::table('hourly_driver_trips')->insertGetId([
                'hourly_driver_id' => $driver->id, 'route_date' => $day, 'role' => $role,
                'status' => $acceptance ? 'offered' : 'accepted',
                'planned_start_at' => $planned, 'offered_at' => $acceptance ? now() : null,
                'expires_at' => $acceptance ? now()->addMinutes($minutes) : null,
                'accepted_at' => $acceptance ? null : now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return DB::table('hourly_driver_trips')->find($id);
        }
        $changes = [];
        if ((string) $existing->planned_start_at !== (string) $planned) $changes['planned_start_at'] = $planned;
        if ($existing->status === 'offered' && $existing->expires_at && now()->gt($existing->expires_at)) $changes['status'] = 'expired';
        // مشوار أُغلق ثم أُسندت له مهمة جديدة: يُعاد فتحه بنفس المعرّف.
        if (in_array($existing->status, ['completed', 'expired'], true) && $tasks->contains(fn ($task) => !$this->done($task))) {
            $changes['status'] = $acceptance && $existing->status === 'expired' ? 'offered' : 'accepted';
            $changes['completed_at'] = null;
        }
        if ($changes) {
            DB::table('hourly_driver_trips')->where('id', $existing->id)->update($changes + ['updated_at' => now()]);
            $existing = DB::table('hourly_driver_trips')->find($existing->id);
        }
        return $existing;
    }

    private function stop(HourlyDriver $driver, object $trip, array $task, int $sequence): void
    {
        $keys = ['hourly_visit_id' => $task['visit']->id, 'role' => $task['role']];
        $values = ['hourly_driver_id' => $driver->id, 'route_date' => Carbon::parse($task['time'])->toDateString(),
            'hourly_driver_trip_id' => $trip->id, 'updated_at' => now()];
        if (!$task['row']) {
            DB::table('hourly_route_stops')->insert($keys + $values + ['display_order' => $sequence, 'status' => 'pending', 'created_at' => now()]);
            return;
        }
        // الترتيب المحفوظ بالسحب من لوحة التحكم لا يُلمس؛ يُحدَّث الربط بالمشوار فقط.
        DB::table('hourly_route_stops')->where('id', $task['row']->id)->update($values);
    }

    private function closeTrip(object $trip, array $stops): void
    {
        $open = array_filter($stops, fn ($stop) => !in_array($stop['status'], ['completed', 'failed', 'skipped'], true));
        if (!$open && $stops && !in_array($trip->status, ['completed', 'rejected', 'offered', 'expired'], true)) {
            DB::table('hourly_driver_trips')->where('id', $trip->id)->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);
        }
    }

    private function decide(HourlyDriver $driver, int $tripId, callable $mutate): array
    {
        return DB::transaction(function () use ($driver, $tripId, $mutate) {
            $this->sync($driver, null);
            $trip = DB::table('hourly_driver_trips')->where('id', $tripId)->lockForUpdate()->first();
            abort_unless($trip && (int) $trip->hourly_driver_id === (int) $driver->id, 404, 'المشوار غير موجود أو لا يخصك.');
            $changes = $mutate($trip);
            if ($changes) DB::table('hourly_driver_trips')->where('id', $trip->id)->update($changes + ['updated_at' => now()]);
            $day = Carbon::parse($trip->route_date)->toDateString();
            $trips = $this->sync($driver, $day);
            $current = collect($trips)->firstWhere('id', (int) $trip->id);
            return ['trip' => $current, 'trips' => array_values(array_filter($trips, fn ($t) => $t['status'] !== 'offered'))];
        });
    }

    public function done(array $task): bool
    {
        $status = $task['visit']->status;
        return $task['role'] === 'delivery' ? in_array($status, self::DELIVERED, true) : in_array($status, self::RETURNED, true);
    }

    /** الإجراء القادم لهذه المحطة؛ التطبيق يعرض الزر المطابق له. */
    public function action(array $task): string
    {
        if ($task['role'] === 'delivery') return $task['visit']->status === 'scheduled' ? 'shelter_pickup' : 'drop_off';
        return in_array($task['visit']->status, ['picked_up', 'completed'], true) ? 'shelter_return' : 'pick_up';
    }

    public function stopStatus(array $task): string
    {
        $row = $task['row'];
        if ($row && in_array($row->status, ['failed', 'skipped'], true)) return $row->status;
        if ($this->done($task)) return 'completed';
        if ($row && $row->status === 'arrived') return 'arrived';
        return 'pending';
    }

    private function stopPayload(array $task, int $sequence): array
    {
        $visit = $task['visit'];
        $action = $this->action($task);
        $atShelter = in_array($action, ['shelter_pickup', 'shelter_return'], true);
        $row = $task['row'] ?: DB::table('hourly_route_stops')->where('hourly_visit_id', $visit->id)->where('role', $task['role'])->first();
        return [
            'id' => (int) ($row->id ?? 0), 'sequence' => (int) ($row->display_order ?? $sequence), 'visit_id' => $visit->id,
            'visit_number' => (int) $visit->visit_number, 'action' => $action, 'status' => $this->stopStatus($task),
            'booking_options' => $visit->order?->package_snapshot['booking_options'] ?? [],
            'worker' => $this->worker($visit), 'workers' => $visit->workers->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
            'place_name' => $atShelter ? ($task['team']->name ?? 'الإيواء') : 'العميل',
            'address' => $atShelter ? ($task['team']->name ?? null) : $visit->address_text,
            'contact_phone' => $atShelter ? null : $visit->contact_phone,
            'planned_at' => $task['time'] ? Carbon::parse($task['time'])->format('H:i') : null,
            'planned_at_iso' => $task['time'] ? Carbon::parse($task['time'])->toIso8601String() : null,
            'latitude' => $atShelter ? null : (float) $visit->latitude, 'longitude' => $atShelter ? null : (float) $visit->longitude,
            'cash_due' => $this->cashDue($task), 'requires_code' => $action === 'drop_off' && (bool) HourlyRentalSettings::get('staff_app_require_delivery_code', true),
        ];
    }

    private function worker(HourlyVisit $visit): ?array
    {
        $worker = $visit->workers->first();
        return $worker ? ['id' => $worker->id, 'name' => $worker->name] : null;
    }

    /** نقد مطلوب عند التسليم فقط، ولطلبات الدفع الآجل غير المسددة. */
    private function cashDue(array $task): float
    {
        $order = $task['visit']->order;
        if ($task['role'] !== 'delivery' || !$order || !in_array($order->payment_method, ['cash', 'card_on_delivery', 'cash_and_card'], true)) return 0.0;
        $due = round((float) $order->total_amount - (float) $order->paid_amount, 2);
        return $due <= 0 ? 0.0 : min($due, round((float) $task['visit']->visit_price, 2));
    }
}
