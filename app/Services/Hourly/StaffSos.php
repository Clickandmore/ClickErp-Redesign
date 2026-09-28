<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyWorkerSosAlert};
use App\Models\ingaz\Maid;
use App\Support\HourlyStaffOtp;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * نداء النجدة لتطبيق الفريق الميداني: العاملة والسائق على نفس الجدول والشاشة.
 *
 * قاعدتان تحكمان المسار:
 * - **بلاغ واحد مفتوح لكل فرد**: الضغط المتكرر يقوّي البلاغ القائم (repeat_count) ويحدّث موقعه ونصه
 *   بدل إنشاء بلاغات متوالية تُغرق الإدارة.
 * - **لا شيء إلزامي**: زر واحد يكفي؛ الموقع والنص والزيارة كلها اختيارية، والزيارة تُستنتج من عمل اليوم.
 */
class StaffSos
{
    public const TABLE = 'hourly_worker_sos_alerts';
    /** الحالات التي يبقى البلاغ فيها مفتوحًا. */
    public const OPEN = ['triggered', 'acknowledged'];

    /** @return array<int,array> آخر 20 بلاغًا لهذا الفرد. */
    public function alerts(string $guard, int $staffId): array
    {
        $this->assertReady();
        return $this->scope($guard, $staffId)->latest('id')->limit(20)->get()
            ->map(fn (HourlyWorkerSosAlert $alert) => $this->payload($alert))->all();
    }

    public function trigger(string $guard, int $staffId, array $data): array
    {
        $this->assertReady();
        $guard = $this->guard($guard);

        return DB::transaction(function () use ($guard, $staffId, $data) {
            $visitId = $this->visitId($guard, $staffId, isset($data['visit_id']) ? (int) $data['visit_id'] : null);
            $alert = $this->scope($guard, $staffId)->whereIn('status', self::OPEN)->lockForUpdate()->latest('id')->first();
            $created = !$alert;
            $values = [
                'hourly_visit_id' => $visitId ?: $alert?->hourly_visit_id,
                'latitude' => $data['latitude'] ?? $alert?->latitude,
                'longitude' => $data['longitude'] ?? $alert?->longitude,
                'accuracy' => $data['accuracy'] ?? $alert?->accuracy,
                'message' => $data['message'] ?? $alert?->message,
            ];
            if ($alert) {
                $alert->update($values + ['repeat_count' => $alert->repeat_count + 1]);
            } else {
                $alert = HourlyWorkerSosAlert::create($values + $this->owner($guard, $staffId) + ['status' => 'triggered']);
            }

            // مفتاح مستقل للتأكيد: payload يحمل message (نص صاحب البلاغ نفسه).
            return $this->payload($alert->fresh()) + [
                'created' => $created,
                'confirmation' => $created ? 'تم إرسال نداء النجدة إلى الإدارة.' : 'تم تحديث نداء النجدة المفتوح وإعادة تنبيه الإدارة.',
            ];
        });
    }

    public function cancel(string $guard, int $staffId, int $alertId, ?string $reason): array
    {
        $this->assertReady();
        $alert = $this->scope($guard, $staffId)->findOrFail($alertId);
        if (!in_array($alert->status, self::OPEN, true)) HourlyOrderService::reject('لا يمكن إلغاء هذا النداء بعد إغلاقه.');
        $alert->update(['status' => 'cancelled', 'cancelled_at' => now(), 'resolution_note' => $reason]);
        return $this->payload($alert->fresh());
    }

    public function payload(HourlyWorkerSosAlert $alert): array
    {
        return [
            'id' => $alert->id, 'guard' => $alert->guard ?: HourlyStaffOtp::WORKER, 'status' => $alert->status,
            'visit_id' => $alert->hourly_visit_id,
            'latitude' => $alert->latitude, 'longitude' => $alert->longitude, 'accuracy' => $alert->accuracy,
            'message' => $alert->message, 'repeat_count' => $alert->repeat_count,
            'created_at' => $alert->created_at?->toIso8601String(),
            'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
            'resolved_at' => $alert->resolved_at?->toIso8601String(),
            'cancelled_at' => $alert->cancelled_at?->toIso8601String(),
        ];
    }

    /** بلاغات هذا الفرد وحده؛ الحارس يمنع أن يرى أحدهم بلاغات الآخر. */
    private function scope(string $guard, int $staffId)
    {
        $guard = $this->guard($guard);
        $query = HourlyWorkerSosAlert::query();
        if ($guard === HourlyStaffOtp::DRIVER) return $query->where('guard', $guard)->where('hourly_driver_id', $staffId);
        // بلاغات العاملة القديمة سُجّلت قبل عمود الحارس، فتُقبل بحارس فارغ أيضًا.
        return $query->where('maid_id', $staffId)->where(fn ($q) => $q->whereNull('guard')->orWhere('guard', HourlyStaffOtp::WORKER));
    }

    private function owner(string $guard, int $staffId): array
    {
        return $guard === HourlyStaffOtp::DRIVER
            ? ['guard' => $guard, 'hourly_driver_id' => $staffId, 'maid_id' => null]
            : ['guard' => $guard, 'maid_id' => $staffId];
    }

    /**
     * الزيارة المرتبطة بالبلاغ: زيارة الفرد إن أُرسل معرّفها (وتُتحقق ملكيتها)،
     * وإلا أول زيارة له اليوم — فيعرف المسؤول أين هو ومع من.
     */
    private function visitId(string $guard, int $staffId, ?int $visitId): ?int
    {
        $query = $guard === HourlyStaffOtp::DRIVER
            ? DB::table('hourly_visits as v')->where(fn ($q) => $q->where('v.delivery_driver_id', $staffId)->orWhere('v.pickup_driver_id', $staffId))
            : DB::table('hourly_visit_workers as w')->join('hourly_visits as v', 'v.id', '=', 'w.hourly_visit_id')->where('w.maid_id', $staffId);
        $query->whereNotIn('v.status', DriverAppService::VOID);
        if ($visitId) {
            abort_unless((clone $query)->where('v.id', $visitId)->exists(), 404,
                $guard === HourlyStaffOtp::DRIVER ? 'الزيارة غير موجودة أو ليست من مهامك.' : 'الزيارة غير موجودة أو ليست من زياراتك.');
            return $visitId;
        }
        return (int) ((clone $query)->whereDate('v.scheduled_start_at', today())->orderBy('v.scheduled_start_at')->value('v.id') ?: 0) ?: null;
    }

    private function guard(string $guard): string
    {
        return $guard === HourlyStaffOtp::DRIVER ? HourlyStaffOtp::DRIVER : HourlyStaffOtp::WORKER;
    }

    private function assertReady(): void
    {
        abort_unless(Schema::hasTable(self::TABLE), 503, 'نداء النجدة غير مهيأ على الخادم.');
    }

    /** اسم صاحب البلاغ وهاتفه لشاشة اللوحة، أيًّا كان حارسه. */
    public static function ownerLabel(HourlyWorkerSosAlert $alert): array
    {
        if (($alert->guard ?: HourlyStaffOtp::WORKER) === HourlyStaffOtp::DRIVER) {
            $driver = HourlyDriver::withTrashed()->find($alert->hourly_driver_id);
            return ['kind' => 'driver', 'label' => 'سائق', 'id' => $alert->hourly_driver_id,
                'name' => $driver?->name ?: '#'.$alert->hourly_driver_id, 'phone' => $driver?->login_phone ?: $driver?->phone];
        }
        $maid = Maid::find($alert->maid_id);
        return ['kind' => 'worker', 'label' => 'عاملة', 'id' => $alert->maid_id,
            'name' => $maid?->name ?: '#'.$alert->maid_id, 'phone' => $maid?->hourly_login_phone ?: $maid?->phone_1];
    }
}
