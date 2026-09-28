<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyMaidAccount};
use App\Models\ingaz\Maid;
use App\Support\{HourlyRentalSettings, HourlyStaffOtp};
use Illuminate\Support\Facades\{DB, Schema};

/**
 * دخول تطبيق الفريق الميداني بالهاتف ورمز التحقق، وتسجيل جهاز الإشعارات.
 *
 * الدخول القديم (اسم مستخدم + كلمة مرور على api/app/hourly/{driver,maid}/login) يبقى كما هو
 * لبوابة الويب وللتوافق؛ هذا المسار إضافة موازية يستخدمها التطبيق.
 */
class StaffAppAuth
{
    public function requestDriverCode(string $phone): array
    {
        $driver = $this->driver($phone);
        if (!$driver) HourlyOrderService::reject('لا يوجد حساب سائق بهذا الرقم. راجع الإدارة.');
        if (!$driver->canLoginVia('mobile')) HourlyOrderService::reject('حساب السائق غير متاح على الجوال. راجع الإدارة.');
        $this->assertDocuments($driver);
        return HourlyStaffOtp::issue(HourlyStaffOtp::DRIVER, $phone, (int) $driver->id) + ['phone' => HourlyStaffOtp::normalize($phone)];
    }

    public function verifyDriver(string $phone, string $code, bool $remember = true): array
    {
        $driver = $this->driver($phone);
        if (!$driver || !$driver->canLoginVia('mobile')) HourlyOrderService::reject('لا يوجد حساب سائق متاح بهذا الرقم.');
        $this->assertDocuments($driver);
        HourlyStaffOtp::consume(HourlyStaffOtp::DRIVER, $phone, $code);
        return $this->issue('hourly_driver_api', $driver, $remember) + ['driver' => $this->driverPayload($driver)];
    }

    /**
     * إصدار توكن الجلسة: مع «تذكّرني» تمتد المدة إلى staff_app_session_days (افتراضيًا 30 يومًا)
     * بدل jwt.ttl العامة (60 دقيقة)، فلا يُطالب السائق ولا العاملة بالدخول كل ساعة.
     *
     * المدة تُعاد للقيمة العامة بعد الإصدار حتى لا تتأثر بقية الحراس في نفس الطلب.
     * سحب الوصول لا يعتمد على قِصر المدة: تعديل حالة الحساب من لوحة التحكم يرفع auth_version
     * فيُرفض كل توكن قديم فورًا (EnsureHourlyDriverAccess / EnsureHourlyMaidAccess).
     */
    public function issue(string $guard, $user, bool $remember = true): array
    {
        $factory = auth($guard)->factory();
        $default = $factory->getTTL();
        $minutes = $remember ? (int) HourlyRentalSettings::get('staff_app_session_days', 30) * 24 * 60 : $default;
        $factory->setTTL($minutes);
        try {
            $token = auth($guard)->login($user);
        } finally {
            $factory->setTTL($default);
        }

        return ['token' => $token, 'access_token' => $token, 'token_type' => 'bearer',
            'expires_in' => $minutes * 60, 'expires_at' => now()->addMinutes($minutes)->toIso8601String(),
            'remember' => $remember, 'session_days' => (int) round($minutes / 1440, 0) ?: 1];
    }

    /** تجديد صامت بتوكن سارٍ: يعيد توكنًا جديدًا بنفس المدة بلا مطالبة المستخدم ببيانات الدخول. */
    public function refresh(string $guard, bool $remember = true): array
    {
        $user = auth($guard)->user();
        abort_unless($user, 401);
        return $this->issue($guard, $user, $remember);
    }

    public function requestWorkerCode(string $phone): array
    {
        $account = $this->worker($phone);
        if (!$account) HourlyOrderService::reject('لا يوجد حساب عاملة بهذا الرقم. راجع الإدارة.');
        if (!$account->canLoginVia('mobile')) HourlyOrderService::reject('حساب العاملة غير متاح على الجوال. راجع الإدارة.');
        if ($account->hourly_is_suspended) HourlyOrderService::reject('الحساب موقوف مؤقتًا. راجع الإدارة.');
        return HourlyStaffOtp::issue(HourlyStaffOtp::WORKER, $phone, (int) $account->id) + ['phone' => HourlyStaffOtp::normalize($phone)];
    }

    public function verifyWorker(string $phone, string $code, bool $remember = true): array
    {
        $account = $this->worker($phone);
        if (!$account || !$account->canLoginVia('mobile') || $account->hourly_is_suspended) HourlyOrderService::reject('لا يوجد حساب عاملة متاح بهذا الرقم.');
        HourlyStaffOtp::consume(HourlyStaffOtp::WORKER, $phone, $code);
        $maid = Maid::findOrFail($account->id);
        return $this->issue('hourly_maid_api', $account, $remember) + ['worker' => app(WorkerAppService::class)->profile($maid)];
    }

    /** تسجيل رمز الإشعارات؛ فشله لا يمنع العمل، لذلك لا يرمي إلا على مدخلات خاطئة. */
    public function device(string $guard, int $staffId, array $data): array
    {
        abort_unless(Schema::hasTable('hourly_staff_devices'), 503, 'تسجيل الأجهزة غير مهيأ على الخادم.');
        DB::table('hourly_staff_devices')->where('guard', $guard)->where('fcm_token', $data['fcm_token'])->delete();
        DB::table('hourly_staff_devices')->insert([
            'guard' => $guard, 'staff_id' => $staffId, 'fcm_token' => $data['fcm_token'],
            'platform' => $data['platform'] ?? null, 'app_version' => $data['app_version'] ?? null,
            'last_used_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        return ['registered' => true, 'guard' => $guard];
    }

    public function driverPayload(HourlyDriver $driver): array
    {
        $team = Schema::hasTable('hourly_team_members') ? $driver->teamMemberships()->with('team:id,name')->first()?->team : null;
        return $driver->only(['id', 'name', 'username', 'employee_number', 'vehicle_capacity'])
            + ['phone' => $driver->login_phone ?: $driver->phone, 'is_online' => (bool) $driver->is_online,
                'team' => $team ? ['id' => $team->id, 'name' => $team->name] : null,
                'tracking' => HourlyRentalSettings::trackingConfig()];
    }

    private function driver(string $phone): ?HourlyDriver
    {
        $variants = HourlyStaffOtp::variants($phone);
        return HourlyDriver::where(fn ($q) => $q->whereIn('login_phone', $variants)->orWhereIn('phone', $variants))->orderBy('id')->first();
    }

    /** جدول العاملات لا يحمل عمود phone موحدًا (phone_1/phone_2 في هذا التثبيت)، فالمطابقة على الموجود فقط. */
    private function worker(string $phone): ?HourlyMaidAccount
    {
        $variants = HourlyStaffOtp::variants($phone);
        $columns = array_values(array_filter(['hourly_login_phone', 'phone', 'phone_1', 'phone_2'], fn ($column) => Schema::hasColumn('maids', $column)));
        return HourlyMaidAccount::where('rental_mode', 'hourly')
            ->where(function ($query) use ($columns, $variants) {
                foreach ($columns as $column) $query->orWhereIn($column, $variants);
            })->orderBy('id')->first();
    }

    /** هاتف العاملة كما يُعرض: هاتف الدخول، وإلا أول عمود هاتف موجود في هذا التثبيت. */
    public static function workerPhone($maid): ?string
    {
        foreach (['hourly_login_phone', 'phone', 'phone_1', 'phone_2'] as $column) {
            if (Schema::hasColumn('maids', $column) && !empty($maid->$column)) return (string) $maid->$column;
        }
        return null;
    }

    /** إقامة أو عقد أو تأمين منتهٍ يمنع الدخول، كما في عقد التطبيق. */
    private function assertDocuments(HourlyDriver $driver): void
    {
        foreach ($driver->expiringDocuments(0) as $field => $daysLeft) {
            if ($daysLeft < 0) HourlyOrderService::reject((HourlyDriver::DATES[$field] ?? $field).' منتهٍ. راجع الإدارة لتحديث بياناتك.');
        }
    }
}
