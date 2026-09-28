<?php

namespace App\Services\Hourly;

use App\Models\CommunicationLog;
use App\Models\auth\Admin;
use App\Models\Hourly\{HourlyDriver, HourlyPackage, HourlyVisit};
use App\Models\ingaz\Maid;
use App\Services\Firebase\FirebasePushResult;
use App\Services\Firebase\FirebasePushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * إرسال إشعارات Firebase لتطبيق الطاقم (السائق والعاملة).
 *
 * أجهزة الطاقم تُسجَّل في جدول مستقل هو hourly_staff_devices، لا في
 * user_firebase_tokens الخاص بالعملاء — فالسائق ليس مستخدماً في جدول users.
 * لذلك لا تصلح FirebasePushService::sendToUser هنا، ونستخدم sendToTokens
 * بعد جلب توكنات الجهاز بأنفسنا.
 */
class StaffPushService
{
    public const GUARD_DRIVER = 'driver';
    public const GUARD_WORKER = 'worker';
    public const GUARDS = [self::GUARD_DRIVER, self::GUARD_WORKER];

    public const TABLE = 'hourly_staff_devices';

    /**
     * سقف العاملات المُشعَرات بعرض واحد. فحص الإتاحة يكلّف عدة استعلامات لكل
     * عاملة، والمرشّحات مقيّدات أصلاً بالفريق وفئة الخدمة — فالسقف حماية من
     * تركيبة تُنتج قائمة ضخمة لا حاجة إليها.
     */
    private const OFFER_NOTIFY_LIMIT = 60;

    public function __construct(private FirebasePushService $push) {}

    /**
     * إرسال إلى كل أجهزة فرد واحد من الطاقم، مع تسجيل سطر في سجل الرسائل.
     */
    public function sendToStaff(string $guard, int $staffId, string $title, string $body, array $data = [], ?Admin $admin = null): FirebasePushResult
    {
        $guard = $this->normalizeGuard($guard);
        $tokens = $this->tokens($guard, [$staffId]);

        $result = $tokens === []
            ? FirebasePushResult::skipped('لا توجد أجهزة مسجّلة لهذا الحساب في تطبيق الطاقم.')
            : $this->push->sendToTokens($tokens, $title, $body, $data + ['guard' => $guard, 'staff_id' => (string) $staffId]);

        $this->pruneInvalidTokens($guard, $result);
        $this->log($guard, $staffId, $title, $body, $result, $admin, $data);

        return $result;
    }

    /**
     * إرسال لمجموعة من الطاقم. نمرّ عليهم فرداً فرداً لا دفعةً واحدة حتى يحمل
     * سجل الرسائل سطراً مستقلاً لكل مستلم، فيُعرف من وصله الإشعار ومن لم يصله.
     *
     * @param  array<int, int> $staffIds
     * @return array{total:int,sent:int,failed:int,skipped:int}
     */
    public function sendToMany(string $guard, array $staffIds, string $title, string $body, array $data = [], ?Admin $admin = null): array
    {
        $summary = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach (array_unique(array_filter(array_map('intval', $staffIds))) as $staffId) {
            $result = $this->sendToStaff($guard, $staffId, $title, $body, $data, $admin);
            $summary['total']++;

            if ($result->wasSkipped()) {
                $summary['skipped']++;
            } elseif ($result->successCount() > 0) {
                $summary['sent']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /** إشعار الأطراف التشغيلية عند تمديد الزيارة؛ فشل FCM لا يرجع مع طلب العميل. */
    public function extraHour(int $visitId, int $hours): void
    {
        $this->quietly(function () use ($visitId, $hours) {
            $visit = HourlyVisit::with('workers:id')->find($visitId);
            if (!$visit) return;
            $data = ['type' => 'hourly_extra_hour', 'hourly_visit_id' => (string) $visit->id,
                'hourly_order_id' => (string) $visit->hourly_rental_order_id];
            $this->sendToMany(self::GUARD_WORKER, $visit->workers->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'تم تمديد الزيارة', 'أضاف العميل '.$hours.' ساعة إلى الزيارة #'.$visit->visit_number.'.', $data);
            $this->sendToMany(self::GUARD_DRIVER, array_values(array_unique(array_filter([
                (int) $visit->delivery_driver_id, (int) $visit->pickup_driver_id,
            ]))), 'تغيّر موعد الزيارة', 'تم تمديد الزيارة #'.$visit->visit_number.'؛ راجع موعد الاستلام.', $data);
        });
    }

    /** إعلان انتهاء كل العاملات يجعل محطة الاستلام متاحة لسائق العودة. */
    public function pickupRequested(int $visitId): void
    {
        $this->quietly(function () use ($visitId) {
            $visit = HourlyVisit::find($visitId);
            if (!$visit || !$visit->pickup_driver_id) return;
            $this->sendToStaff(self::GUARD_DRIVER, (int) $visit->pickup_driver_id,
                'طلب استلام جاهز', 'أنهت العاملة الزيارة #'.$visit->visit_number.' وهي جاهزة للاستلام.',
                ['type' => 'hourly_pickup_request', 'hourly_visit_id' => (string) $visit->id,
                    'hourly_order_id' => (string) $visit->hourly_rental_order_id]);
        });
    }

    /** إشعار من أُضيف حديثًا إلى الزيارة، دون إزعاج من كان مسندًا من قبل. */
    public function assignmentChanged(int $visitId, array $before): void
    {
        $this->quietly(function () use ($visitId, $before) {
            $visit = HourlyVisit::with('workers:id')->find($visitId);
            if (!$visit) return;
            $data = ['type' => 'hourly_assignment', 'hourly_visit_id' => (string) $visit->id,
                'hourly_order_id' => (string) $visit->hourly_rental_order_id];
            $oldWorkers = array_map('intval', (array) ($before['worker_ids'] ?? []));
            $newWorkers = array_values(array_diff($visit->workers->pluck('id')->map(fn ($id) => (int) $id)->all(), $oldWorkers));
            $this->sendToMany(self::GUARD_WORKER, $newWorkers, 'زيارة جديدة',
                'أُسندت إليك الزيارة #'.$visit->visit_number.'؛ افتحي التطبيق لمراجعة الموعد.', $data);
            foreach (['delivery', 'pickup'] as $role) {
                $field = $role.'_driver_id';
                $driverId = (int) $visit->{$field};
                if (!$driverId || $driverId === (int) ($before[$field] ?? 0)) continue;
                $type = (bool) HourlyRentalSettings::get('staff_app_driver_acceptance', false) ? 'hourly_trip_offer' : 'hourly_trip_assigned';
                $this->sendToStaff(self::GUARD_DRIVER, $driverId,
                    $type === 'hourly_trip_offer' ? 'عرض مشوار جديد' : 'مشوار جديد',
                    'أُسندت إليك مهمة '.($role === 'delivery' ? 'توصيل' : 'استلام').' للزيارة #'.$visit->visit_number.'.',
                    $data + ['type' => $type, 'role' => $role]);
            }
        });
    }

    /**
     * عرض الزيارة للعاملات: يُشعر المرشّحات المتاحات أن هناك زيارة تُقبل من
     * التطبيق. بدون هذا الإشعار كانت الميزة معطّلة فعلياً — العرض يُفتح في
     * القاعدة ولا أحد يعلم به حتى تفتح العاملة التطبيق من تلقاء نفسها.
     */
    public function visitOffered(int $visitId): void
    {
        $this->quietly(function () use ($visitId) {
            $visit = HourlyVisit::with('order')->find($visitId);
            if (!$visit?->offered_to_workers || !$visit->scheduled_start_at || !$visit->scheduled_end_at
                || !$visit->scheduled_start_at->isFuture()) {
                return;
            }

            $order = $visit->order;
            $package = $order ? HourlyPackage::withTrashed()->find($order->hourly_package_id) : null;
            if (!$package) return;

            $snapshot = (array) $order->package_snapshot;
            $assignment = app(HourlyWorkerAssignment::class);

            // نفس ترشيح شاشة العروض في التطبيق، حتى لا تُشعَر عاملة بعرض لا يظهر لها.
            $candidates = $assignment->candidates(
                $package,
                $visit->hourly_team_id,
                (bool) ($snapshot['assign_by_product_attribute'] ?? false),
                (bool) (($order->booking_metadata['all_workers'] ?? false))
            )->limit(self::OFFER_NOTIFY_LIMIT)->get();

            $start = $visit->scheduled_start_at->toDateTimeString();
            $end = $visit->scheduled_end_at->toDateTimeString();
            $ids = [];

            foreach ($candidates as $maid) {
                if ($assignment->available($maid, $start, $end, $snapshot, $visit->id)) {
                    $ids[] = (int) $maid->id;
                }
            }

            $this->sendToMany(self::GUARD_WORKER, $ids, 'زيارة متاحة للقبول',
                'زيارة #'.$visit->visit_number.' يوم '.$visit->scheduled_start_at->format('Y-m-d H:i')
                .' ('.$this->hoursLabel((float) $visit->planned_hours).') متاحة للقبول من التطبيق.',
                ['type' => 'hourly_visit_offer', 'hourly_visit_id' => (string) $visit->id,
                    'hourly_order_id' => (string) $visit->hourly_rental_order_id]);
        });
    }

    /** نتيجة طلب الإجازة: العاملة طلبتها من التطبيق، فيجب أن تعرف قرارها. */
    public function leaveDecision(int $maidId, string $status, ?string $from = null, ?string $to = null): void
    {
        $this->quietly(function () use ($maidId, $status, $from, $to) {
            $approved = $status === 'approved';
            $period = $from && $to
                ? ' للفترة من '.$this->day($from).' إلى '.$this->day($to)
                : '';

            $this->sendToStaff(self::GUARD_WORKER, $maidId,
                $approved ? 'تم اعتماد إجازتك' : 'لم تُعتمد إجازتك',
                $approved
                    ? 'تم اعتماد طلب إجازتك'.$period.'.'
                    : 'لم يُعتمد طلب إجازتك'.$period.'. راجعي مسؤول التشغيل لمعرفة السبب.',
                ['type' => 'hourly_leave_decision', 'leave_status' => $status]);
        });
    }

    /** تعيين قائد الفريق: السائق المعيّن لا يعرف بمسؤوليته الجديدة إلا من هنا. */
    public function teamLeaderAssigned(int $visitId, array $before): void
    {
        $this->quietly(function () use ($visitId, $before) {
            $visit = HourlyVisit::find($visitId);
            $leaderId = (int) ($visit?->team_leader_id ?? 0);

            if (!$visit || !$leaderId || $leaderId === (int) ($before['team_leader_id'] ?? 0)) {
                return;
            }

            $this->sendToStaff(self::GUARD_DRIVER, $leaderId, 'أنت قائد الفريق',
                'عُيّنت قائداً لفريق الزيارة #'.$visit->visit_number.'.',
                ['type' => 'hourly_team_leader', 'hourly_visit_id' => (string) $visit->id,
                    'hourly_order_id' => (string) $visit->hourly_rental_order_id]);
        });
    }

    /**
     * إلغاء زيارة: يُشعر من كان مسنداً عليها قبل الإلغاء، لا من هو مسند بعده —
     * فالإلغاء يُفرّغ التعيينات. بدون هذا الإشعار كانت العاملة تنتقل إلى زيارة ملغاة.
     */
    public function visitCancelled(int $visitId, array $before, ?string $reason = null): void
    {
        $this->quietly(function () use ($visitId, $before, $reason) {
            $visit = HourlyVisit::find($visitId);
            if (!$visit) return;

            $number = $visit->visit_number;
            $tail = $reason ? ' السبب: '.mb_substr($reason, 0, 120) : '';
            $data = ['type' => 'hourly_visit_cancelled', 'hourly_visit_id' => (string) $visit->id,
                'hourly_order_id' => (string) $visit->hourly_rental_order_id];

            $this->sendToMany(self::GUARD_WORKER, $this->previousWorkers($before, $visit),
                'أُلغيت الزيارة', 'أُلغيت الزيارة #'.$number.' ولم تعد مطلوبة.'.$tail, $data);
            $this->sendToMany(self::GUARD_DRIVER, $this->previousDrivers($before),
                'أُلغي المشوار', 'أُلغيت الزيارة #'.$number.' ولم يعد مشوارها مطلوباً.'.$tail, $data);
        });
    }

    /**
     * تغيّر الموعد أو المدة: يُشعر المسندين حالياً بالموعد الجديد، وإلا بقوا على
     * الموعد القديم بلا أن يعرف أحد.
     */
    public function scheduleChanged(int $visitId, array $before): void
    {
        $this->quietly(function () use ($visitId, $before) {
            $visit = HourlyVisit::with('workers:id')->find($visitId);
            if (!$visit) return;

            $when = $visit->scheduled_start_at?->format('Y-m-d H:i');
            if (!$when) return;

            // لقطة «قبل» تحمل القيمة الخام من القاعدة، والزيارة تحمل Carbon —
            // فنوحّد الصيغة قبل المقارنة وإلا اعتُبر كل نداء تغييراً.
            $wasAt = $this->minute($before['scheduled_start_at'] ?? null);
            $sameTime = $wasAt !== null && $wasAt === $when;
            $sameHours = abs((float) ($before['planned_hours'] ?? 0) - (float) $visit->planned_hours) < 0.001;

            if ($sameTime && $sameHours) {
                return;
            }

            $body = 'تغيّر موعد الزيارة #'.$visit->visit_number.' إلى '.$when
                .' ('.$this->hoursLabel((float) $visit->planned_hours).').';
            $data = ['type' => 'hourly_schedule_changed', 'hourly_visit_id' => (string) $visit->id,
                'hourly_order_id' => (string) $visit->hourly_rental_order_id];

            $this->sendToMany(self::GUARD_WORKER, $visit->workers->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'تغيّر موعد الزيارة', $body, $data);
            $this->sendToMany(self::GUARD_DRIVER, array_values(array_unique(array_filter([
                (int) $visit->delivery_driver_id, (int) $visit->pickup_driver_id,
            ]))), 'تغيّر موعد المشوار', $body, $data);
        });
    }

    /** إزالة تعيين: يُشعر من أُزيل فقط، فلا يبقى في جدوله موعد ليس له. */
    public function unassigned(int $visitId, array $before): void
    {
        $this->quietly(function () use ($visitId, $before) {
            $visit = HourlyVisit::with('workers:id')->find($visitId);
            if (!$visit) return;

            $number = $visit->visit_number;
            $data = ['type' => 'hourly_unassigned', 'hourly_visit_id' => (string) $visit->id,
                'hourly_order_id' => (string) $visit->hourly_rental_order_id];

            $current = $visit->workers->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->sendToMany(self::GUARD_WORKER, array_values(array_diff($this->previousWorkers($before, $visit), $current)),
                'أُزيل تعيينك', 'لم تعد الزيارة #'.$number.' مُسندة إليك.', $data);

            $currentDrivers = array_filter([(int) $visit->delivery_driver_id, (int) $visit->pickup_driver_id]);
            $this->sendToMany(self::GUARD_DRIVER, array_values(array_diff($this->previousDrivers($before), $currentDrivers)),
                'أُزيلت مهمتك', 'لم يعد مشوار الزيارة #'.$number.' مُسنداً إليك.', $data);
        });
    }

    /** تصريف عربي صحيح لعدد الساعات: ساعة / ساعتان / 3-10 ساعات / 11+ ساعة. */
    private function hoursLabel(float $hours): string
    {
        $isWhole = abs($hours - round($hours)) < 0.001;
        $whole = (int) round($hours);

        if (!$isWhole) {
            return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.').' ساعة';
        }

        return match (true) {
            $whole === 1 => 'ساعة واحدة',
            $whole === 2 => 'ساعتان',
            $whole >= 3 && $whole <= 10 => $whole.' ساعات',
            default => $whole.' ساعة',
        };
    }

    /** تاريخ مقروء بلا وقت. */
    private function day($value): string
    {
        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    /** يوحّد أي تمثيل للوقت إلى دقيقة واحدة قابلة للمقارنة. */
    private function minute($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse(is_string($value) ? $value : (string) $value)->format('Y-m-d H:i');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * العاملات المسندات قبل الإجراء.
     *
     * لقطة لوحة التحكم تحمل worker_ids، أما مسار العميل فلقطته getAttributes
     * فقط. في الحالة الثانية نقرأ المسندات من الزيارة — والقراءة هنا آمنة لأن
     * كل النداءات تجري داخل quietly().
     *
     * @return array<int, int>
     */
    private function previousWorkers(array $before, ?HourlyVisit $visit = null): array
    {
        if (array_key_exists('worker_ids', $before)) {
            return array_values(array_filter(array_map('intval', (array) $before['worker_ids'])));
        }

        return $visit
            ? $visit->workers()->pluck('maids.id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    /** @return array<int, int> */
    private function previousDrivers(array $before): array
    {
        return array_values(array_unique(array_filter([
            (int) ($before['delivery_driver_id'] ?? 0),
            (int) ($before['pickup_driver_id'] ?? 0),
        ])));
    }

    private function quietly(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::warning('Unable to send automatic hourly staff notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * إحصاء أجهزة الطاقم لعرضه في صفحة إعدادات Firebase.
     *
     * @return array{total:int,driver:int,worker:int,android:int,ios:int,staff:int}
     */
    public function deviceStats(): array
    {
        $empty = ['total' => 0, 'driver' => 0, 'worker' => 0, 'android' => 0, 'ios' => 0, 'staff' => 0];

        if (!Schema::hasTable(self::TABLE)) {
            return $empty;
        }

        return [
            'total' => DB::table(self::TABLE)->count(),
            'driver' => DB::table(self::TABLE)->where('guard', self::GUARD_DRIVER)->count(),
            'worker' => DB::table(self::TABLE)->where('guard', self::GUARD_WORKER)->count(),
            'android' => DB::table(self::TABLE)->where('platform', 'android')->count(),
            'ios' => DB::table(self::TABLE)->where('platform', 'ios')->count(),
            'staff' => DB::table(self::TABLE)->distinct()->count(DB::raw('CONCAT(guard, "-", staff_id)')),
        ];
    }

    /**
     * الطاقم الذي سجّل أجهزة فعلاً، لعرضه في قائمة الاختيار.
     *
     * @return \Illuminate\Support\Collection<int, array{guard:string,id:int,name:string,phone:?string,devices:int}>
     */
    public function staffWithDevices(int $limit = 300)
    {
        if (!Schema::hasTable(self::TABLE)) {
            return collect();
        }

        return collect(DB::table(self::TABLE)
            ->selectRaw('guard, staff_id, COUNT(*) as devices, MAX(last_used_at) as last_seen')
            ->groupBy('guard', 'staff_id')
            ->orderByDesc('last_seen')
            ->limit($limit)
            ->get())
            ->map(function ($row) {
                $identity = $this->identity($row->guard, (int) $row->staff_id);

                return [
                    'guard' => $row->guard,
                    'id' => (int) $row->staff_id,
                    'name' => $identity['name'],
                    'phone' => $identity['phone'],
                    'devices' => (int) $row->devices,
                ];
            })
            ->values();
    }

    /**
     * @param  array<int, int> $staffIds
     * @return array<int, string>
     */
    private function tokens(string $guard, array $staffIds): array
    {
        if (!Schema::hasTable(self::TABLE)) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('guard', $guard)
            ->whereIn('staff_id', $staffIds)
            ->pluck('fcm_token')
            ->all();
    }

    /**
     * FirebasePushService ينظّف توكنات العملاء فقط، فأجهزة الطاقم الميتة كانت
     * ستتراكم إلى ما لا نهاية. ننظّفها هنا بنفس الشرط.
     */
    private function pruneInvalidTokens(string $guard, FirebasePushResult $result): void
    {
        $invalid = $result->invalidTokens();

        if ($invalid === [] || !config('firebase.prune_invalid_tokens', true) || !Schema::hasTable(self::TABLE)) {
            return;
        }

        $deleted = DB::table(self::TABLE)->where('guard', $guard)->whereIn('fcm_token', $invalid)->delete();

        Log::info('Pruned invalid staff device tokens', ['guard' => $guard, 'deleted' => $deleted]);
    }

    /**
     * سطر في نفس سجل الرسائل الذي يعرض إشعارات العملاء.
     *
     * user_id يبقى فارغاً: عموده مقيّد بمفتاح أجنبي على جدول users، والسائق ليس
     * فيه. هوية المستلم تُحفظ في recipient_name/phone وفي meta.
     */
    private function log(string $guard, int $staffId, string $title, string $body, FirebasePushResult $result, ?Admin $admin, array $payload = []): void
    {
        try {
            $identity = $this->identity($guard, $staffId);
            $payload = $result->toArray();

            CommunicationLog::create([
                'batch_id' => (string) Str::uuid(),
                'channel' => 'notification',
                'recipient_type' => 'staff',
                'user_id' => null,
                'admin_id' => $admin?->id,
                'notification_id' => null,
                'recipient_name' => $identity['name'],
                'recipient_phone' => $identity['phone'],
                'title' => $title,
                'message' => $body,
                'provider' => 'staff_app',
                'status' => $result->wasSkipped() ? 'skipped' : ($result->successCount() > 0 ? 'sent' : 'failed'),
                'response' => json_encode(['fcm' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'meta' => [
                    'guard' => $guard,
                    'staff_id' => $staffId,
                    // حمولة الإشعار تُحفظ ليقرأها صندوق الإشعارات في التطبيق (StaffNotifications) ويفتح الشاشة الصحيحة.
                    'payload' => $payload,
                    'devices_reached' => $result->successCount(),
                    'devices_failed' => $result->failureCount(),
                    'delivery_note' => $payload['failures'][0]['reason'] ?? null,
                ],
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            // التسجيل لا يجوز أن يُسقط الإرسال.
            Log::warning('Unable to log staff push notification', ['guard' => $guard, 'staff_id' => $staffId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{name:string,phone:?string}
     */
    private function identity(string $guard, int $staffId): array
    {
        if ($guard === self::GUARD_DRIVER) {
            $driver = HourlyDriver::withTrashed()->find($staffId);

            return [
                'name' => (string) ($driver?->name ?: 'سائق #' . $staffId),
                'phone' => $driver?->login_phone ?: $driver?->phone,
            ];
        }

        $maid = Maid::find($staffId);

        return [
            'name' => (string) ($maid?->name ?: 'عاملة #' . $staffId),
            'phone' => $maid?->hourly_login_phone ?: $maid?->phone_1,
        ];
    }

    private function normalizeGuard(string $guard): string
    {
        $guard = strtolower(trim($guard));

        if (!in_array($guard, self::GUARDS, true)) {
            throw new \InvalidArgumentException('نوع حساب الطاقم غير مدعوم: ' . $guard);
        }

        return $guard;
    }
}
