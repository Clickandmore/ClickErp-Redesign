<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use App\Models\ingaz\Maid;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * الإنهاء المبكر: العاملة أعلنت «انتهيت» قبل اكتمال مدة الخدمة محسوبة من وقت وصولها.
 *
 * التسلسل: يُسجَّل الإنهاء دون منعه (قد يكون بطلب العميل) ← يُسأل العميل خلال مهلة هل كان بطلبه
 * ← التأكيد يغلق السجل، والاعتراض أو انقضاء المهلة بلا رد يحيله لمراجعة الإدارة
 * ← الإدارة تسجل قرارها يدويًا وتنفذ أي تعويض بأدوات الطلب القائمة. لا حركة مالية تلقائية هنا.
 * لا يوجد هامش سماح: أي إنهاء قبل اكتمال المدة يُسجَّل.
 */
class EarlyFinish
{
    public const REASONS = [
        'customer_request' => 'العميل طلب إنهاء الخدمة',
        'work_done' => 'انتهى العمل المطلوب قبل الوقت',
        'emergency' => 'ظرف طارئ',
        'other' => 'سبب آخر',
    ];
    public const DECISIONS = [
        'no_action' => 'لا إجراء — الإنهاء مقبول',
        'wallet_credit' => 'تعويض العميل برصيد في المحفظة',
        'free_visit' => 'زيارة أو ساعات مجانية للعميل',
        'worker_action' => 'إجراء على العاملة',
        'other' => 'قرار آخر',
    ];
    /** مهلة رد العميل بالساعات بعد الإنهاء. */
    public const RESPONSE_HOURS = 2;

    public static function ready(): bool
    {
        return Schema::hasTable('hourly_early_finishes');
    }

    /** بداية الخدمة الفعلية للعاملة: وقت وصولها المسجّل، وإلا موعد بدء الزيارة. */
    public function startedAt(?string $arrivedAt, HourlyVisit $visit): array
    {
        if ($arrivedAt) return [Carbon::parse($arrivedAt), false];
        return [$visit->scheduled_start_at?->copy(), true];
    }

    /** نهاية الخدمة المتوقعة للعاملة؛ قبلها يكون «انتهيت» إنهاءً مبكرًا. */
    public function plannedEnd(?string $arrivedAt, HourlyVisit $visit): ?Carbon
    {
        [$start] = $this->startedAt($arrivedAt, $visit);
        return $start?->copy()->addSeconds((int) round((float) $visit->planned_hours * 3600));
    }

    /**
     * يُستدعى داخل معاملة «انتهيت» بعد تحديث صف العاملة. يعيد true إن سُجّل إنهاء مبكر.
     */
    public function detect(HourlyRentalOrder $order, HourlyVisit $visit, object $workerRow, Maid $maid, ?string $reason, ?string $note): bool
    {
        if (!self::ready() || DB::table('hourly_early_finishes')->where('worker_visit_id', $workerRow->id)->exists()) return false;
        [$start, $fromSchedule] = $this->startedAt($workerRow->arrived_at ?? null, $visit);
        if (!$start || $start->isFuture()) return false;
        $finished = now();
        $planned = (int) round((float) $visit->planned_hours * 60);
        $worked = (int) floor($start->diffInSeconds($finished) / 60);
        if ($planned <= 0 || $start->diffInSeconds($finished) >= (int) round((float) $visit->planned_hours * 3600)) return false;

        DB::table('hourly_early_finishes')->insert([
            'order_id' => $order->id, 'visit_id' => $visit->id, 'worker_visit_id' => $workerRow->id, 'maid_id' => $maid->id,
            'started_at' => $start, 'finished_at' => $finished, 'started_from_schedule' => $fromSchedule,
            'planned_minutes' => $planned, 'worked_minutes' => $worked,
            'reason' => isset(self::REASONS[$reason]) ? $reason : 'unspecified', 'worker_note' => $note,
            'status' => 'awaiting_customer', 'respond_until' => $finished->copy()->addHours(self::RESPONSE_HOURS),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (Schema::hasTable('hourly_order_audits')) {
            app(OrderManagementService::class)->audit($order, 'early_finish', [], ['worker_visit_id' => $workerRow->id, 'maid_id' => $maid->id,
                'worked_minutes' => $worked, 'planned_minutes' => $planned, 'reason' => $reason], null, $visit->id,
                'إنهاء مبكر من العاملة '.$maid->name.': '.$this->duration($worked).' من '.$this->duration($planned));
        }
        $visitId = (int) $visit->id;
        DB::afterCommit(fn () => app(CustomerTrackingPushService::class)->notify($visitId, 'early_finish'));
        return true;
    }

    /** الحالة المعروضة: «بانتظار العميل» تتحول إلى «بلا رد» بعد المهلة دون مهمة مجدولة. */
    public function effectiveStatus(object $row): string
    {
        return $row->status === 'awaiting_customer' && Carbon::parse($row->respond_until)->isPast() ? 'no_response' : $row->status;
    }

    public function needsReview(object $row): bool
    {
        return in_array($this->effectiveStatus($row), ['disputed', 'no_response'], true);
    }

    /** عدد السجلات التي تنتظر قرار الإدارة (اعتراض العميل أو انقضاء المهلة). */
    public function reviewCount(): int
    {
        if (!self::ready()) return 0;
        return DB::table('hourly_early_finishes')->where(fn ($q) => $q->where('status', 'disputed')
            ->orWhere(fn ($w) => $w->where('status', 'awaiting_customer')->where('respond_until', '<', now())))->count();
    }

    /** بيانات الزيارة للعميل: المدة وحالة السؤال ورابط الرد. */
    public function publicData(HourlyVisit $visit): array
    {
        if (!self::ready()) return [];
        $names = $visit->relationLoaded('workers') ? $visit->workers->pluck('name', 'id') : $visit->workers()->pluck('name', 'maids.id');
        return DB::table('hourly_early_finishes')->where('visit_id', $visit->id)->orderBy('id')->get()->map(fn ($row) => [
            'id' => (int) $row->id, 'worker_name' => $names[$row->maid_id] ?? null,
            'worked_minutes' => (int) $row->worked_minutes, 'planned_minutes' => (int) $row->planned_minutes,
            'worked_label' => $this->duration((int) $row->worked_minutes), 'planned_label' => $this->duration((int) $row->planned_minutes),
            'finished_at' => Carbon::parse($row->finished_at)->toIso8601String(),
            'status' => $this->effectiveStatus($row),
            'can_respond' => $this->effectiveStatus($row) === 'awaiting_customer',
            'respond_until' => Carbon::parse($row->respond_until)->toIso8601String(),
        ])->values()->all();
    }

    /** رد العميل: confirmed «كان بطلبي» يغلق السجل، disputed «لم يكن بطلبي» يحيله للإدارة. */
    public function respond(HourlyRentalOrder $order, int $visitId, int $id, string $response, ?string $note): array
    {
        abort_unless(self::ready(), 503, 'ميزة الإنهاء المبكر غير مهيأة.');
        return DB::transaction(function () use ($order, $visitId, $id, $response, $note) {
            $row = DB::table('hourly_early_finishes')->where('id', $id)->where('order_id', $order->id)->where('visit_id', $visitId)->lockForUpdate()->first();
            abort_unless($row, 404, 'السجل غير موجود.');
            if ($row->status !== 'awaiting_customer') HourlyOrderService::reject('تم تسجيل ردك على هذا الإنهاء من قبل.');
            if (Carbon::parse($row->respond_until)->isPast()) HourlyOrderService::reject('انتهت مهلة الرد؛ أُحيل الإنهاء لمراجعة الإدارة.');
            DB::table('hourly_early_finishes')->where('id', $id)->update([
                'status' => $response, 'customer_responded_at' => now(), 'customer_note' => $note, 'updated_at' => now(),
            ]);
            return ['id' => $id, 'status' => $response, 'message' => $response === 'confirmed'
                ? 'شكرًا لتأكيدك. أُغلق الإنهاء المبكر دون إجراء.'
                : 'وصلنا اعتراضك، وستراجع الإدارة الزيارة وتتواصل معك بالقرار.'];
        });
    }

    /** قرار الإدارة اليدوي؛ التعويض نفسه يُنفَّذ بأدوات الطلب (الاسترداد أو الزيارة المجانية). */
    public function review(int $id, array $data, int $adminId): void
    {
        abort_unless(self::ready(), 503, 'ميزة الإنهاء المبكر غير مهيأة.');
        DB::transaction(function () use ($id, $data, $adminId) {
            $row = DB::table('hourly_early_finishes')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ($row->status === 'resolved') HourlyOrderService::reject('تم تسجيل قرار لهذا الإنهاء من قبل.');
            if ($row->status === 'confirmed') HourlyOrderService::reject('أكد العميل أن الإنهاء كان بطلبه؛ لا يحتاج قرارًا.');
            DB::table('hourly_early_finishes')->where('id', $id)->update([
                'status' => 'resolved', 'decision' => $data['decision'], 'decision_note' => $data['decision_note'],
                'reviewed_by' => $adminId, 'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            $order = HourlyRentalOrder::find($row->order_id);
            if ($order && Schema::hasTable('hourly_order_audits')) {
                app(OrderManagementService::class)->audit($order, 'early_finish_review', ['status' => $row->status],
                    ['status' => 'resolved', 'decision' => $data['decision']], $adminId, (int) $row->visit_id,
                    'قرار الإنهاء المبكر: '.(self::DECISIONS[$data['decision']] ?? $data['decision']).' — '.$data['decision_note']);
            }
        });
    }

    public function duration(int $minutes): string
    {
        $h = intdiv($minutes, 60); $m = $minutes % 60;
        return trim(($h ? $h.' س ' : '').($m || !$h ? $m.' د' : ''));
    }
}
