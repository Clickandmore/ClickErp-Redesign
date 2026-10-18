<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder, HourlyVisit};
use App\Models\ingaz\Maid;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * إجراءات العميل على زيارة مشتراة من تطبيقه: الإلغاء، طلب ساعة إضافية، التقييم.
 *
 * ثلاث قواعد تحكم هذا الصنف:
 * - كل إجراء داخل معاملة واحدة تقفل الطلب والزيارة، ثم تعيد حساب إجماليات الطلب من الزيارات
 *   بنفس دالة لوحة التحكم (OrderManagementService::totals) فلا يوجد مصدران للحقيقة المالية.
 * - كل إجراء idempotent: إعادة إرسال نفس الطلب (التطبيق لا يعيد محاولة POST تلقائيًا، لكن
 *   العميل قد يكرر اللمس) لا تكرر رسوم إلغاء ولا ساعة إضافية ولا صف تقييم.
 * - سياسة الإلغاء المعروضة في الـAPI (policy) هي نفسها المفروضة في التنفيذ (cancel).
 */
class CustomerVisitActions
{
    /** حالات الزيارة التي لا يقبل بعدها أي إجراء عميل. */
    public const CLOSED = ['completed', 'cancelled', 'cancelled_by_client', 'expired', 'no_show'];

    /** الحد الأقصى لساعات الزيارة الواحدة بعد التمديد (نفس سقف مسار الحجز). */
    public const MAX_VISIT_HOURS = 12;
    /** الحالات التي تقبل ساعة إضافية: قبل الزيارة وأثناءها (في الطريق / جارية). */
    public const EXTENDABLE = ['scheduled', 'en_route', 'delivered', 'in_progress'];
    /** التقييم متاح بعد إعلان العاملة انتهاءها (حسم الفجوة ف٢)، ولو لم يكتمل الاستلام والعودة. */
    public const RATEABLE = ['worker_finished', 'picked_up', 'completed'];

    /** سياسة الإلغاء وحالة زر الإلغاء لزيارة واحدة، بلا أي استعلام. */
    public function policy(HourlyVisit $visit, HourlyRentalOrder $order): array
    {
        $snapshot = (array) $order->package_snapshot;
        $window = (int) ($snapshot['cancellation_window_hours'] ?? HourlyRentalSettings::get('cancellation_window_hours'));
        $percent = (float) ($snapshot['cancellation_fee_percent'] ?? HourlyRentalSettings::get('cancellation_fee_percent'));
        $upcoming = $visit->status === 'scheduled' && $visit->scheduled_start_at?->isFuture() && !ServiceIncident::waiting($order,$visit->id);
        // داخل نافذة الإلغاء = رسوم بنسبة من سعر الزيارة، وقبلها الإلغاء مجاني.
        $inside = $upcoming && now()->diffInMinutes($visit->scheduled_start_at, false) < $window * 60;
        return [
            'window_hours' => $window,
            'fee_percent' => $percent,
            'fee_if_cancelled_now' => $upcoming && $inside ? round((float) $visit->visit_price * $percent / 100, 2) : 0.0,
            'free_until' => $visit->scheduled_start_at?->copy()->subHours($window)->toIso8601String(),
            'can_cancel' => $this->open($order) && $upcoming,
        ];
    }

    public function cancel(HourlyVisit $visit): HourlyVisit
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($visit) {
            [$order, $visit] = $this->lock($visit);
            if(ServiceIncident::waiting($order,$visit->id)) HourlyOrderService::reject('للزيارة بلاغ معتمد؛ استخدم طلب الإلغاء والاسترداد دون رسوم من خيارات المعالجة.');
            // إعادة الإرسال لا تُعيد فرض الرسوم ولا تُسجّل إلغاءً ثانيًا.
            if ($visit->status === 'cancelled_by_client') return $visit;
            if (in_array($visit->status, self::CLOSED, true)) HourlyOrderService::reject('الزيارة منتهية أو ملغاة ولا يمكن إلغاؤها.');
            if ($visit->status !== 'scheduled' || !$visit->scheduled_start_at) HourlyOrderService::reject('الإلغاء متاح للزيارات المجدولة فقط.');
            if (!$visit->scheduled_start_at->isFuture()) HourlyOrderService::reject('بدأ موعد الزيارة بالفعل؛ تواصل مع خدمة العملاء.');
            if (!$this->open($order)) HourlyOrderService::reject('الطلب مغلق أو انتهت صلاحيته ولا يقبل الإلغاء.');
            $policy = $this->policy($visit, $order);
            $before = $visit->getAttributes();
            $visit->update(['status' => 'cancelled_by_client', 'cancellation_fee' => $policy['fee_if_cancelled_now']]);
            // تحرير الحجز المبدئي للعاملات فورًا؛ سجل hourly_visit_workers يبقى كأثر تشغيلي
            // (استعلامات التوفر تستثني الزيارات الملغاة أصلًا).
            if (Schema::hasTable('hourly_visit_reservations')) DB::table('hourly_visit_reservations')->where('hourly_visit_id', $visit->id)->delete();
            // العاملة والسائق المسندان لا يعلمان بإلغاء العميل إلا من هنا.
            // لا نجلب قائمة العاملات هنا: أي استعلام في هذا المسار غير محمي
            // وسيُسقط الإلغاء نفسه إن فشل. الخدمة تستخرج المسندين بنفسها داخل
            // حارس الاستثناءات الخاص بها.
            $visitId = (int) $visit->id;
            DB::afterCommit(fn () => app(StaffPushService::class)->visitCancelled($visitId, $before, 'إلغاء بطلب العميل'));
            $this->commit($order, $visit, $before, 'customer_cancel_visit',
                'إلغاء الزيارة '.$visit->visit_number.' بطلب العميل — رسوم '.$policy['fee_if_cancelled_now'].' ر.س',
                ['cancellation_fee' => $policy['fee_if_cancelled_now']], true);
            return $visit->fresh();
        });
    }

    /** يعيد ['visit','hours','amount','replayed']. */
    public function extraHour(HourlyVisit $visit, int $hours, ?string $token = null): array
    {
        HourlyOrderService::enabled();
        return DB::transaction(function () use ($visit, $hours, $token) {
            [$order, $visit] = $this->lock($visit);
            if ($replay = $this->replay($visit, $hours, $token)) return $replay;
            ["end"=>$end, "planned"=>$planned, "amount"=>$amount] = $this->extension($order, $visit, $hours);
            $before = $visit->getAttributes();
            $price = round((float) $visit->visit_price + $amount, 2);
            if ($price > 9999999) HourlyOrderService::reject('السعر الناتج للزيارة خارج الحدود المسموحة.');
            $changes = ['planned_hours' => $planned, 'scheduled_end_at' => $end, 'visit_price' => $price];
            if (array_key_exists('is_free', $before)) $changes['is_free'] = false;
            // موعد استلام السائق يُعاد تدبيره من لوحة التحكم بعد التمديد.
            if (array_key_exists('pickup_at', $before)) $changes['pickup_at'] = null;
            $visit->update($changes);
            $this->commit($order, $visit, $before, 'customer_extra_hour',
                'ساعة إضافية (+'.$hours.') للزيارة '.$visit->visit_number.' بطلب العميل — '.$amount.' ر.س',
                ['hours' => $hours, 'amount' => $amount, 'request_token' => $token]);
            $visitId=(int)$visit->id;
            DB::afterCommit(fn()=>app(StaffPushService::class)->extraHour($visitId,$hours));
            return ['visit' => $visit->fresh(), 'hours' => $hours, 'amount' => $amount, 'replayed' => false];
        });
    }

    /** معاينة بلا تعديل؛ التأكيد يعيد فحص التوفر تحت قفل الزيارة. */
    public function extraHourQuote(HourlyVisit $visit, int $hours): array
    {
        HourlyOrderService::enabled();
        $order = HourlyRentalOrder::findOrFail($visit->hourly_rental_order_id);
        $quote = $this->extension($order, $visit, $hours);
        return ['hours'=>$hours, 'amount'=>$quote['amount'], 'currency'=>'SAR',
            'ends_at'=>$quote['end']->format('Y-m-d H:i'), 'planned_hours'=>$quote['planned']];
    }

    private function extension(HourlyRentalOrder $order, HourlyVisit $visit, int $hours): array
    {
        if(ServiceIncident::waiting($order,$visit->id)) HourlyOrderService::reject('للزيارة بلاغ معتمد؛ أكمل المعالجة قبل طلب خدمات إضافية.');
        if ($hours < 1 || $hours > 6) HourlyOrderService::reject('عدد الساعات الإضافية من 1 إلى 6.');
        if (in_array($visit->status, self::CLOSED, true)) HourlyOrderService::reject('الزيارة منتهية أو ملغاة ولا تقبل ساعة إضافية.');
        if (!in_array($visit->status, self::EXTENDABLE, true) || !$visit->scheduled_start_at || !$visit->scheduled_end_at) HourlyOrderService::reject('طلب الساعة الإضافية متاح للزيارات المجدولة أو الجارية فقط.');
        if (!$this->open($order)) HourlyOrderService::reject('الطلب مغلق أو انتهت صلاحيته ولا يقبل تعديلًا.');
        $serviceEnd=ServiceTime::deadline($visit);
        if (!$serviceEnd?->isFuture()) HourlyOrderService::reject('انتهى وقت الزيارة؛ لا يمكن إضافة ساعات إليها.');
        $end = $serviceEnd->copy()->addHours($hours);
        if (!$end->isSameDay($visit->scheduled_start_at)) HourlyOrderService::reject('الساعة الإضافية تتجاوز يوم الزيارة.');
        $planned = round((float) $visit->planned_hours + $hours, 2);
        if ($planned > self::MAX_VISIT_HOURS) HourlyOrderService::reject('إجمالي ساعات الزيارة لا يتجاوز '.self::MAX_VISIT_HOURS.' ساعة.');
        $amount = round($this->hourPrice($order, $visit) * $hours, 2);
        $snapshot = (array) $order->package_snapshot + ['service_category_id' => $order->service_category_id ?? null];
        $this->assertNoOverlap($order, $visit, $end, $snapshot);
        $this->assertWorkersStay($visit, $end, $snapshot);
        return compact('end', 'planned', 'amount');
    }

    /** يعيد ['visit','rating','comment','workers']. */
    public function rate(HourlyVisit $visit, int $rating, ?string $comment): array
    {
        HourlyOrderService::enabled();
        abort_unless(Schema::hasTable('hourly_worker_reviews'), 503, 'تقييم الزيارات غير مهيأ على الخادم.');
        return DB::transaction(function () use ($visit, $rating, $comment) {
            [$order, $visit] = $this->lock($visit);
            if (!in_array($visit->status, self::RATEABLE, true)) HourlyOrderService::reject('التقييم متاح بعد انتهاء الزيارة فقط.');
            $ids = Schema::hasTable('hourly_visit_workers')
                ? DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->pluck('maid_id')
                : collect();
            if ($ids->isEmpty()) HourlyOrderService::reject('لا توجد عاملة مسجلة على هذه الزيارة لتقييمها.');
            foreach ($ids as $maid) {
                $keys = ['visit_id' => $visit->id, 'maid_id' => $maid, 'user_id' => (int) $order->user_id];
                $row = ['rating' => $rating, 'comment' => $comment, 'updated_at' => now()];
                // تحديث التقييم لا يغيّر تاريخ إنشائه، وإعادة الإرسال لا تضيف صفًا ثانيًا.
                DB::table('hourly_worker_reviews')->where($keys)->exists()
                    ? DB::table('hourly_worker_reviews')->where($keys)->update($row)
                    : DB::table('hourly_worker_reviews')->insert($keys + $row + ['hourly_rental_order_id' => $order->id, 'created_at' => now()]);
            }
            if (Schema::hasTable('hourly_order_audits')) {
                app(OrderManagementService::class)->audit($order, 'customer_rate_visit', [], ['rating' => $rating, 'comment' => $comment, 'maid_ids' => $ids->all()], null, $visit->id, 'تقييم العميل للزيارة '.$visit->visit_number);
            }
            return ['visit' => $visit, 'rating' => $rating, 'comment' => $comment, 'workers' => $ids->count()];
        });
    }

    /** سعر الساعة الإضافية: سعر الباقة إن وُجد، وإلا سعر الزيارة موزعًا على ساعاتها. */
    private function hourPrice(HourlyRentalOrder $order, HourlyVisit $visit): float
    {
        if ((float) $order->extra_hour_price > 0) return (float) $order->extra_hour_price;
        return (float) $visit->planned_hours > 0 ? round((float) $visit->visit_price / (float) $visit->planned_hours, 2) : 0.0;
    }

    private function open(HourlyRentalOrder $order): bool
    {
        return in_array($order->status, ['active', 'pending_confirmation'], true) && $order->expires_at?->isFuture();
    }

    private function lock(HourlyVisit $visit): array
    {
        $order = HourlyRentalOrder::query()->lockForUpdate()->findOrFail($visit->hourly_rental_order_id);
        return [$order, $order->visits()->lockForUpdate()->findOrFail($visit->id)];
    }

    /** طلب ساعة إضافية مكرر: نفس request_token، أو نفس عدد الساعات خلال دقيقتين بلا رمز. */
    private function replay(HourlyVisit $visit, int $hours, ?string $token): ?array
    {
        if (!Schema::hasTable('hourly_order_audits')) return null;
        $row = DB::table('hourly_order_audits')->where('visit_id', $visit->id)->where('action', 'customer_extra_hour')->orderByDesc('id')->first();
        if (!$row) return null;
        $after = json_decode($row->after ?? '[]', true) ?: [];
        $same = $token !== null
            ? ($after['request_token'] ?? null) === $token
            : (int) ($after['hours'] ?? 0) === $hours && Carbon::parse($row->created_at)->gt(now()->subMinutes(2));
        return $same ? ['visit' => $visit, 'hours' => (int) ($after['hours'] ?? $hours), 'amount' => (float) ($after['amount'] ?? 0), 'replayed' => true] : null;
    }

    private function assertNoOverlap(HourlyRentalOrder $order, HourlyVisit $visit, Carbon $end, array $snapshot): void
    {
        $rest = (int) ($snapshot['break_minutes'] ?? 0) + (int) ($snapshot['buffer_minutes'] ?? 0);
        $clash = $order->visits()->where('id', '!=', $visit->id)->whereNotIn('status', ['cancelled', 'cancelled_by_client', 'expired'])
            ->where('scheduled_start_at', '<', $end->copy()->addMinutes($rest))
            ->where('scheduled_end_at', '>', $visit->scheduled_start_at->copy()->subMinutes($rest))->exists();
        if ($clash) HourlyOrderService::reject('الساعة الإضافية تتعارض مع زيارة أخرى في الطلب أو مع فترة الراحة بينهما.');
    }

    private function assertWorkersStay(HourlyVisit $visit, Carbon $end, array $snapshot): void
    {
        if (!Schema::hasTable('hourly_visit_workers')) return;
        $ids = DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->pluck('maid_id');
        if (Schema::hasTable('hourly_visit_reservations')) $ids = $ids->merge(DB::table('hourly_visit_reservations')->where('hourly_visit_id', $visit->id)->pluck('maid_id'))->unique();
        if ($ids->isEmpty()) return;
        $assignment = app(HourlyWorkerAssignment::class);
        foreach (Maid::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get() as $maid) {
            if (!$assignment->available($maid, $visit->scheduled_start_at->toDateTimeString(), $end->toDateTimeString(), $snapshot, $visit->id)) {
                HourlyOrderService::reject('العاملة '.$maid->name.' غير متاحة لتمديد الزيارة (جدول العمل أو زيارة أخرى أو سقف الساعات). تواصل مع خدمة العملاء.');
            }
        }
    }

    /** إعادة حساب إجماليات الطلب + سجل حالة + سجل تدقيق، ثم إغلاق الطلب إن لم يبقَ فيه زيارة مفتوحة. */
    private function commit(HourlyRentalOrder $order, HourlyVisit $visit, array $before, string $action, string $reason, array $extra = [], bool $closeOrder = false): void
    {
        app(OrderManagementService::class)->totals($order);
        if ($closeOrder) {
            app(OrderRefundService::class)->refund($order,'wallet',$reason,null,'client',(int)$order->user_id);
            $order->refresh();
        }
        $order->statusLogs()->create(['from_status' => $order->status, 'to_status' => $order->status, 'actor_type' => 'client', 'actor_id' => $order->user_id, 'reason' => $reason]);
        if (Schema::hasTable('hourly_order_audits')) {
            app(OrderManagementService::class)->audit($order, $action, $before, $visit->fresh()->getAttributes() + $extra, null, $visit->id, $reason);
        }
        if ($closeOrder && !$order->visits()->whereNotIn('status', self::CLOSED)->exists()) {
            app(HourlyOrderService::class)->transition($order, $order->visits()->where('status', 'completed')->exists() ? 'completed' : 'cancelled', 'client', (int) $order->user_id, $reason);
        }
    }
}
