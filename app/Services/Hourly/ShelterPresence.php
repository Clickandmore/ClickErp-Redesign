<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyVisit, WorkerDailyMovement};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * المصدر الوحيد للإجابة عن «أين العاملة الآن؟» بين موديول الساعات وشاشات الإيواء.
 *
 * الحضور اللحظي يُقرأ من حالة الزيارة لأنها تُحدَّث في كل الأحوال (تطبيق السائق،
 * بوابة الويب، إجراءات الإدارة)، بينما worker_daily_movements هو السجل الدائم
 * الذي يكتبه تطبيق السائق عند «استلام من الإيواء» و«عودة للإيواء» للجرد والتقارير.
 */
class ShelterPresence
{
    /** حالات الزيارة التي تكون فيها العاملة خارج الإيواء فعليًا. */
    public const OUT_STATUSES = ['en_route', 'delivered', 'in_progress', 'worker_finished', 'picked_up'];

    /** حالات الزيارة التي تُحتسب ارتباطًا قادمًا للعاملة. */
    public const UPCOMING_STATUSES = ['scheduled', 'en_route', 'delivered', 'in_progress', 'worker_finished', 'picked_up'];

    private ?array $outNow = null;
    private ?array $upcoming = null;

    /** الربط مُفعَّل: الموديول يعمل، ومفتاح الربط بالإيواء مرفوع، والجداول موجودة. */
    public static function linked(): bool
    {
        return HourlyRentalSettings::shelterLinked()
            && Schema::hasTable('hourly_visits')
            && Schema::hasTable('hourly_visit_workers');
    }

    /**
     * العاملات الخارجات الآن في زيارات ساعات.
     * يُحسب مرة واحدة لكل طلب لأن جداول الإيواء تستدعيه لكل صف.
     *
     * @return array<int, array{visit_id:int, visit_number:int, status:string, order_id:int, order_number:?string, since:?string, until:?string}>
     */
    public function outNow(): array
    {
        return self::linked() ? $this->inVisitNow() : [];
    }

    /** الزيارات تُقرأ متى عمل موديول الساعات، ولو لم يُفعَّل مفتاح الربط بشاشات الإيواء. */
    public static function tracked(): bool
    {
        return HourlyRentalSettings::isEnabled()
            && Schema::hasTable('hourly_visits')
            && Schema::hasTable('hourly_visit_workers');
    }

    /**
     * العاملات الموجودات الآن في زيارة ساعات، لصفحة خادمات الساعات نفسها.
     *
     * @return array<int, array{visit_id:int, visit_number:int, status:string, order_id:int, order_number:?string, since:?string, until:?string}>
     */
    public function inVisitNow(): array
    {
        if ($this->outNow !== null) return $this->outNow;
        if (!self::tracked()) return $this->outNow = [];

        $rows = DB::table('hourly_visit_workers as w')
            ->join('hourly_visits as v', 'v.id', '=', 'w.hourly_visit_id')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')
            ->where(fn ($q) => $q->whereIn('v.status', self::OUT_STATUSES)->orWhereIn('w.status', ['delivered', 'finished', 'picked_up']))
            ->whereNotIn('o.status', ['cancelled', 'expired'])
            ->where(fn ($q) => $q->whereNull('w.status')->orWhereNotIn('w.status', ['returned', 'completed']))
            ->orderBy('v.scheduled_start_at')
            ->get(['w.maid_id', 'v.id as visit_id', 'v.visit_number', 'v.status', 'v.scheduled_start_at', 'v.scheduled_end_at',
                'o.id as order_id', 'o.order_number']);

        $map = [];
        foreach ($rows as $row) {
            // عاملة في أكثر من زيارة مفتوحة: الأقدم هي التي خرجت من أجلها.
            $map[(int) $row->maid_id] ??= [
                'visit_id' => (int) $row->visit_id, 'visit_number' => (int) $row->visit_number, 'status' => $row->status,
                'order_id' => (int) $row->order_id, 'order_number' => $row->order_number,
                'since' => $row->scheduled_start_at, 'until' => $row->scheduled_end_at,
            ];
        }

        return $this->outNow = $map;
    }

    /**
     * إفراغ ذاكرة الطلب. الخدمة singleton حتى تُحسب الخريطة مرة واحدة لكل طلب،
     * فأي عملية طويلة (أمر مجدول، عامل طابور، اختبار) تستدعي هذا بعد تغيير الزيارات.
     */
    public function flush(): void
    {
        $this->outNow = null;
        $this->upcoming = null;
    }

    public function isOut(int $maidId): bool
    {
        return isset($this->outNow()[$maidId]);
    }

    public function visitOf(int $maidId): ?array
    {
        return $this->outNow()[$maidId] ?? null;
    }

    /**
     * عدد الزيارات القادمة لكل عاملة. تُحسب الخريطة كاملة مرة واحدة لأن جداول
     * الإيواء تستدعيها صفًّا صفًّا، فاستعلام لكل صف كان سيضرب الأداء.
     *
     * @return array<int,int>
     */
    public function upcomingAll(): array
    {
        if ($this->upcoming !== null) return $this->upcoming;
        if (!self::linked()) return $this->upcoming = [];

        return $this->upcoming = DB::table('hourly_visit_workers as w')
            ->join('hourly_visits as v', 'v.id', '=', 'w.hourly_visit_id')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')
            ->whereIn('v.status', self::UPCOMING_STATUSES)
            ->whereNotIn('o.status', ['cancelled', 'expired'])
            ->where(fn ($q) => $q->whereNull('w.status')->orWhereNotIn('w.status', ['returned', 'completed']))
            ->where(fn ($q) => $q->whereNull('v.scheduled_end_at')->orWhere('v.scheduled_end_at', '>=', now()))
            ->groupBy('w.maid_id')
            ->pluck(DB::raw('COUNT(*)'), 'w.maid_id')
            ->map(fn ($count) => (int) $count)->all();
    }

    public function upcomingFor(int $maidId): int
    {
        return $this->upcomingAll()[$maidId] ?? 0;
    }

    /**
     * الزيارة القادمة وعدد الزيارات القادمة لعاملات صفحة واحدة، باستعلام واحد.
     *
     * @param  array<int>  $maidIds
     * @return array<int, array{count:int, next_at:?string, visit_number:?int}>
     */
    public function upcomingSummary(array $maidIds): array
    {
        if (!$maidIds || !self::tracked()) return [];

        $rows = DB::table('hourly_visit_workers as w')
            ->join('hourly_visits as v', 'v.id', '=', 'w.hourly_visit_id')
            ->join('hourly_rental_orders as o', 'o.id', '=', 'v.hourly_rental_order_id')
            ->whereIn('w.maid_id', $maidIds)
            ->where('v.status', 'scheduled')
            ->whereNotIn('o.status', ['cancelled', 'expired'])
            ->where(fn ($q) => $q->whereNull('w.status')->orWhereNotIn('w.status', ['returned', 'completed']))
            ->where('v.scheduled_start_at', '>=', now())
            ->orderBy('v.scheduled_start_at')
            ->get(['w.maid_id', 'v.visit_number', 'v.scheduled_start_at']);

        $map = [];
        foreach ($rows as $row) {
            $id = (int) $row->maid_id;
            $map[$id] ??= ['count' => 0, 'next_at' => $row->scheduled_start_at, 'visit_number' => (int) $row->visit_number];
            $map[$id]['count']++;
        }

        return $map;
    }

    /** فتح حركة خروج لكل عاملة في الزيارة. إعادة التنفيذ لا تُكرّر الصف ولا تُعيد فتح حركة مغلقة. */
    public function open(HourlyVisit $visit, string $byType, ?int $byId): void
    {
        if (!Schema::hasTable('worker_daily_movements')) return;
        $maidIds = $this->workerIds($visit);
        if (!$maidIds) return;
        $places = $this->shelterPlaces($maidIds);
        $fallback = HourlyRentalSettings::get('default_shelter_place_id');
        foreach ($maidIds as $maidId) {
            WorkerDailyMovement::firstOrCreate(
                ['source' => WorkerDailyMovement::SOURCE_HOURLY_VISIT, 'reference_type' => 'hourly_visit', 'reference_id' => $visit->id, 'maid_id' => $maidId],
                ['shelter_place_id' => $places[$maidId] ?? ($fallback ?: null), 'out_at' => now(),
                    'status' => WorkerDailyMovement::STATUS_OUT, 'out_by_type' => $byType, 'out_by_id' => $byId,
                    'note' => 'زيارة #'.$visit->visit_number.' (مرجع #'.$visit->id.')'],
            );
        }
    }

    /** مكان الإيواء من عقد العاملة؛ الإعداد الافتراضي يُستخدم لمن لا مكان محدد لها. */
    private function shelterPlaces(array $maidIds): array
    {
        if (!Schema::hasTable('contract_infos')) return [];

        return DB::table('contracts')
            ->join('contract_infos as ci', 'ci.contract_id', '=', 'contracts.id')
            ->whereIn('contracts.maid_id', $maidIds)
            ->whereNotNull('ci.shelter_id')
            ->orderBy('contracts.id')
            ->pluck('ci.shelter_id', 'contracts.maid_id')
            ->map(fn ($id) => (int) $id)->all();
    }

    /** إغلاق حركة العودة. لا أثر إن لم تُفتح الحركة أصلًا أو كانت مغلقة. */
    public function openWorker(HourlyVisit $visit, int $maidId, string $byType, ?int $byId): void
    {
        if (!Schema::hasTable('worker_daily_movements')) return;
        if (!DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->where('maid_id', $maidId)->exists()) return;
        $place = $this->shelterPlaces([$maidId])[$maidId] ?? HourlyRentalSettings::get('default_shelter_place_id');
        WorkerDailyMovement::firstOrCreate(
            ['source' => WorkerDailyMovement::SOURCE_HOURLY_VISIT, 'reference_type' => 'hourly_visit', 'reference_id' => $visit->id, 'maid_id' => $maidId],
            ['shelter_place_id' => $place ?: null, 'out_at' => now(), 'status' => WorkerDailyMovement::STATUS_OUT, 'out_by_type' => $byType, 'out_by_id' => $byId, 'note' => 'Visit #'.$visit->visit_number.' (ref #'.$visit->id.')'],
        );
    }
    public function closeWorker(HourlyVisit $visit, int $maidId, string $byType, ?int $byId): void
    {
        if (!Schema::hasTable('worker_daily_movements')) return;
        WorkerDailyMovement::where('source', WorkerDailyMovement::SOURCE_HOURLY_VISIT)->where('reference_type', 'hourly_visit')
            ->where('reference_id', $visit->id)->where('maid_id', $maidId)->whereNull('in_at')
            ->update(['in_at' => now(), 'status' => WorkerDailyMovement::STATUS_RETURNED, 'in_by_type' => $byType, 'in_by_id' => $byId, 'updated_at' => now()]);
    }
    public function close(HourlyVisit $visit, string $byType, ?int $byId): void
    {
        if (!Schema::hasTable('worker_daily_movements')) return;
        WorkerDailyMovement::where('source', WorkerDailyMovement::SOURCE_HOURLY_VISIT)
            ->where('reference_type', 'hourly_visit')->where('reference_id', $visit->id)
            ->whereIn('maid_id', $this->workerIds($visit))
            ->whereNull('in_at')
            ->update(['out_at' => DB::raw('out_at'), 'in_at' => now(), 'status' => WorkerDailyMovement::STATUS_RETURNED,
                'in_by_type' => $byType, 'in_by_id' => $byId, 'updated_at' => now()]);
    }

    private function workerIds(HourlyVisit $visit): array
    {
        return DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->pluck('maid_id')->map('intval')->all();
    }
}
