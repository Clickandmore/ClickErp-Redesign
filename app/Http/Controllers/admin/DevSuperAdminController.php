<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use App\Services\SchedulerMonitorService;
use App\Support\DatabaseBackupSettings;
use App\Support\DevSuperAdminSecuritySettings;
use App\Support\DashboardMaintenanceSettings;
use App\Support\ClientSubscriptionSettings;
use App\Support\R2StorageSettings;
use App\Support\ProductUpdatesSettings;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DevSuperAdminController extends Controller
{
    /**
     * Core tables whose creation migration must agree with the live schema.
     *
     * A recorded creation migration with a missing table means data/schema was
     * removed outside Laravel. Running later migrations cannot repair that and
     * may make the partial restore harder to diagnose.
     */
    private const CORE_TABLE_CREATION_MIGRATIONS = [
        'settings' => '2023_05_02_000115_create_settings_table',
        'new_settings' => '2024_03_21_000000_create_new_settings_table',
        'admins' => '2023_02_19_091453_create_admins_table',
        'users' => '2014_10_12_000000_create_users_table',
        'system_module_settings' => '2026_05_23_000001_create_system_module_settings_table',
        'dev_superadmin_settings' => '2026_05_24_000001_create_dev_superadmin_settings_table',
        'system_product_profile_changes' => '2026_08_25_000002_create_system_product_profile_changes_table',
    ];

    public function loginForm()
    {
        if ((bool) session('dev_superadmin_authenticated')) {
            return $this->redirectAfterLogin();
        }

        return view('admin.superadmin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (
            hash_equals((string) config('dev_superadmin.username'), (string) $data['username'])
            && DevSuperAdminSecuritySettings::matchesPassword((string) $data['password'])
        ) {
            $request->session()->regenerate();
            $request->session()->put('dev_superadmin_authenticated', true);

            return $this->redirectAfterLogin();
        }

        return back()
            ->withInput(['username' => $data['username']])
            ->withErrors(['username' => 'بيانات دخول لوحة التطوير غير صحيحة.']);
    }

    private function redirectAfterLogin()
    {
        try {
            DB::connection()->getPdo();

            foreach (array_merge(['migrations'], array_keys(self::CORE_TABLE_CREATION_MIGRATIONS)) as $table) {
                if (! Schema::hasTable($table)) {
                    return redirect()->route('dev-superadmin.recovery');
                }
            }
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('dev-superadmin.recovery');
        }

        return redirect()->route('dev-superadmin.modules');
    }

    public function recovery()
    {
        $database = [
            'connected' => false,
            'driver' => (string) config('database.default'),
            'name' => (string) config('database.connections.' . config('database.default') . '.database'),
            'error' => null,
        ];
        $tables = collect([
            'migrations' => 'سجل migrations',
            'settings' => 'إعدادات النظام',
            'new_settings' => 'الإعدادات الموسعة',
            'admins' => 'حسابات الإدارة',
            'users' => 'العملاء',
            'system_module_settings' => 'إعدادات الموديولات',
            'dev_superadmin_settings' => 'إعدادات Super Admin',
            'system_product_profile_changes' => 'سجل تغيير نوع البرنامج',
        ])->map(fn (string $label, string $table) => [
            'table' => $table,
            'label' => $label,
            'exists' => null,
        ]);

        try {
            DB::connection()->getPdo();
            $database['connected'] = true;
            $database['driver'] = DB::connection()->getDriverName();
            $database['name'] = DB::connection()->getDatabaseName();
            $tables = $tables->map(function (array $item) {
                $item['exists'] = Schema::hasTable($item['table']);

                return $item;
            });
        } catch (\Throwable $exception) {
            report($exception);
            $database['error'] = $exception->getMessage();
        }

        $migrationFiles = collect(File::allFiles(database_path('migrations')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getBasename('.php'))
            ->unique()
            ->sort()
            ->values();
        $appliedMigrations = collect();

        if ($database['connected'] && $tables->firstWhere('table', 'migrations')['exists'] === true) {
            try {
                $appliedMigrations = DB::table('migrations')->pluck('migration')->unique();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $pendingMigrations = $migrationFiles->diff($appliedMigrations)->values();
        $structuralDrift = $this->missingRecordedCoreTables($appliedMigrations);

        return view('admin.superadmin.recovery', [
            'database' => $database,
            'tables' => $tables,
            'missingTableCount' => $tables->where('exists', false)->count(),
            'migrationConfirmation' => 'MIGRATE ' . ($database['name'] ?: 'DATABASE'),
            'migrationStatus' => [
                'total' => $migrationFiles->count(),
                'applied' => $appliedMigrations->count(),
                'pending' => $pendingMigrations,
            ],
            'structuralDrift' => $structuralDrift,
        ]);
    }

    public function runRecoveryMigrations(Request $request)
    {
        $databaseName = (string) config('database.connections.' . config('database.default') . '.database');

        try {
            DB::connection()->getPdo();
            $databaseName = DB::connection()->getDatabaseName();
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'database' => 'لا يمكن تشغيل migrations قبل نجاح الاتصال بقاعدة البيانات.',
            ]);
        }

        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:255'],
            'current_password' => ['required', 'string'],
        ]);
        $expected = 'MIGRATE ' . $databaseName;

        if (! hash_equals($expected, trim($data['confirmation']))) {
            return back()->withErrors([
                'confirmation' => 'عبارة التأكيد غير مطابقة لقاعدة البيانات الحالية.',
            ])->withInput($request->except('current_password'));
        }

        $structuralDrift = $this->missingRecordedCoreTables();
        if ($structuralDrift->isNotEmpty()) {
            return back()->withErrors([
                'migration_integrity' => 'تم إيقاف التشغيل حمايةً للبيانات: سجل migrations يعتبر رقعة إنشاء الجداول التالية مطبقة، لكن الجداول غير موجودة فعليًا: '
                    . $structuralDrift->pluck('table')->implode(', ')
                    . '. استعد هذه الجداول من آخر نسخة احتياطية سليمة ثم أعد الفحص؛ migrations لا تستطيع استعادة الصفوف المحذوفة.',
            ])->withInput($request->except('current_password'));
        }

        if (! DevSuperAdminSecuritySettings::matchesPassword($data['current_password'])) {
            return back()->withErrors([
                'current_password' => 'كلمة مرور Super Admin غير صحيحة.',
            ])->withInput($request->except('current_password'));
        }

        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Str::limit(trim(Artisan::output()), 6000, "\n...");

            if ($exitCode !== 0) {
                return back()->withErrors([
                    'migration' => 'انتهى أمر migrations بكود ' . $exitCode . ".\n" . $output,
                ]);
            }

            return redirect()
                ->route('dev-superadmin.recovery')
                ->with('success', 'تم تشغيل migrations بنجاح.')
                ->with('command_output', $output !== '' ? $output : 'لا توجد migrations جديدة.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['migration' => $exception->getMessage()]);
        }
    }

    public function modules(
        Request $request,
        DatabaseBackupService $databaseBackups,
        DatabaseBackupVerificationService $backupVerification,
        SchedulerMonitorService $schedulerMonitor
    )
    {
        $activeSection = match ($request->route()?->getName()) {
            'dev-superadmin.subscription' => 'subscription',
            'dev-superadmin.product-updates' => 'product-updates',
            'dev-superadmin.employees' => 'employees',
            'dev-superadmin.database-backups' => 'backups',
            'dev-superadmin.scheduler' => 'scheduler',
            default => 'modules',
        };

        $admins = collect();
        if (in_array($activeSection, ['subscription', 'employees'], true)) {
            $admins = Admin::query()
                ->with('role')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'username', 'role_id', 'status', 'admin_type'])
                ->map(function (Admin $admin) {
                    $roleName = optional($admin->role)->name ?: 'بدون دور';

                    $admin->forceFill([
                        'dev_contact' => $admin->email ?: ($admin->phone ?: ($admin->username ?: 'لا توجد بيانات دخول ظاهرة')),
                        'dev_role_name' => $roleName,
                        'dev_is_suspended' => ($admin->status ?? 'active') === 'suspended',
                        'dev_search_text' => trim($admin->id . ' ' . $admin->name . ' ' . $admin->email . ' ' . $admin->phone . ' ' . $admin->username . ' ' . $roleName),
                    ]);

                    return $admin;
                });
        }

        $backups = [];
        $backupListError = null;
        $r2Ready = false;
        if ($activeSection === 'backups') {
            if (R2StorageSettings::isReady()) {
                try {
                    $backups = $databaseBackups->backups();
                    $r2Ready = true;
                } catch (\Throwable $exception) {
                    report($exception);
                    $backupListError = 'تعذر الاتصال بـ R2 أو التحقق من بياناته. راجع Access Key وSecret Key وEndpoint؛ ويمكنك استخدام الاستيراد المحلي دون R2.';
                }
            }
        }

        return view('admin.superadmin.modules', [
            'modules' => SystemModuleManager::modules(),
            'enabledMap' => SystemModuleManager::enabledMap(),
            'configuredMap' => SystemModuleManager::configuredMap(),
            'productProfiles' => SystemModuleManager::productProfiles(),
            'productProfileKey' => SystemModuleManager::productProfileKey(),
            'dashboards' => SystemModuleManager::dashboards(),
            'defaultDashboardKey' => SystemModuleManager::defaultDashboardKey(),
            'branchLimit' => SystemModuleManager::branchLimit(),
            'userLimit' => SystemModuleManager::userLimit(),
            'admins' => $admins,
            'backupSettings' => $activeSection === 'backups' ? DatabaseBackupSettings::all() : null,
            'databaseBackups' => $backups,
            'localDatabaseBackups' => $activeSection === 'backups' ? $databaseBackups->localBackups() : [],
            'currentDatabaseDetails' => $activeSection === 'backups' ? $databaseBackups->currentDatabaseDetails() : null,
            'databaseImportCommandPrefix' => $activeSection === 'backups' ? $databaseBackups->localImportCommandPrefix() : null,
            'databaseImportShell' => $activeSection === 'backups' ? $databaseBackups->terminalShell() : null,
            'databaseEmptyCommand' => $activeSection === 'backups' ? $databaseBackups->emptyDatabaseCommand() : null,
            'backupListError' => $backupListError,
            'backupVerificationChannels' => $activeSection === 'backups' ? $backupVerification->availableChannels() : [],
            'backupVerificationChallenge' => $activeSection === 'backups' ? $backupVerification->current($request) : null,
            'devSuperAdminSecurity' => $activeSection === 'backups' ? DevSuperAdminSecuritySettings::all() : null,
            'r2Ready' => $r2Ready,
            'schedulerStatus' => $activeSection === 'scheduler' ? $schedulerMonitor->status() : null,
            'subscription' => ClientSubscriptionSettings::all(),
            'productUpdates' => ProductUpdatesSettings::all(),
            'maintenanceSettings' => DashboardMaintenanceSettings::all(),
            'activeSection' => $activeSection,
        ]);
    }

    public function updateMaintenanceMode(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'message.required' => 'اكتب الرسالة التي ستظهر للموظفين أثناء التحديث.',
            'message.min' => 'رسالة التحديث يجب ألا تقل عن 10 أحرف.',
            'message.max' => 'رسالة التحديث يجب ألا تزيد على 500 حرف.',
        ]);

        DashboardMaintenanceSettings::persist($data);

        return redirect()
            ->route('dev-superadmin.modules')
            ->with('success', $data['enabled']
                ? 'تم تفعيل وضع التحديث وإغلاق لوحة التحكم أمام الموظفين.'
                : 'تم إلغاء وضع التحديث وإتاحة لوحة التحكم للموظفين.');
    }

    public function updateProductUpdates(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'updates' => ['required', 'array', 'min:1', 'max:20'],
            'updates.*.title' => ['required', 'string', 'max:160'],
            'updates.*.details' => ['nullable', 'string', 'max:1000'],
            'updates.*.url' => ['nullable', 'string', 'max:1000'],
            'updates.*.link_label' => ['nullable', 'string', 'max:80'],
        ]);

        foreach ($data['updates'] as $index => $update) {
            $url = trim((string) ($update['url'] ?? ''));
            $isInternalUrl = Str::startsWith($url, '/') && ! Str::startsWith($url, '//');
            $isExternalUrl = Str::startsWith($url, ['https://', 'http://']);
            $isAnchor = Str::startsWith($url, '#');

            if ($url !== '' && ! $isInternalUrl && ! $isExternalUrl && ! $isAnchor) {
                return back()
                    ->withErrors(["updates.$index.url" => 'أدخل رابطًا داخليًا يبدأ بـ / أو رابطًا كاملًا يبدأ بـ https://.'])
                    ->withInput();
            }
        }

        ProductUpdatesSettings::persist([
            ...$data,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return redirect()
            ->route('dev-superadmin.product-updates')
            ->with('success', 'تم حفظ محتوى «ما الجديد» وسيظهر في هيدر لوحة الإدارة.');
    }

    public function updateSubscription(Request $request)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:160'],
            'client_code' => ['nullable', 'string', 'max:80'],
            'plan_name' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:active,trial,paused,expired'],
            'work_started_at' => ['nullable', 'date'],
            'subscription_started_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date', 'after_or_equal:subscription_started_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ClientSubscriptionSettings::persist($data);

        return redirect()
            ->route('dev-superadmin.subscription')
            ->with('success', 'تم حفظ بيانات العميل والاشتراك بنجاح.');
    }

    public function updateModules(Request $request)
    {
        if ($request->boolean('modules.hourly_rental')
            && !\App\Support\HourlyRentalSettings::isEnabled()
            && collect(\App\Support\HourlyRentalSettings::installationStages())->contains(fn ($stage) => ($stage['required'] ?? true) && !$stage['done'])) {
            return back()->withErrors(['modules.hourly_rental' => 'أكمل تركيب جداول الساعات أولًا باستخدام الأمر hourly:install.']);
        }
        if (!array_key_exists('hourly_rental', (array)$request->input('modules', []))
            && \App\Support\HourlyRentalSettings::isEnabled()
            && ($blockers = \App\Support\HourlyRentalSettings::disableBlockers())) {
            return back()->withErrors(['modules.hourly_rental' => implode(' ', $blockers)]);
        }
        $data = $request->validate([
            'branches_limit' => ['nullable', 'integer', 'min:0'],
            'users_limit' => ['nullable', 'integer', 'min:0'],
            'product_profile' => ['required', 'string', 'in:' . implode(',', array_keys(SystemModuleManager::productProfiles()))],
            'current_product_profile' => ['required', 'string'],
        ]);

        $productProfile = $data['product_profile'];
        $currentProductProfile = SystemModuleManager::productProfileKey();

        if (! hash_equals($currentProductProfile, $data['current_product_profile'])) {
            return back()
                ->withErrors(['product_profile' => 'تغير نوع البرنامج من جلسة أخرى. حدّث الصفحة قبل المحاولة مرة أخرى.']);
        }

        if ($productProfile !== $currentProductProfile) {
            $sensitiveData = $request->validate([
                'product_profile_confirmation' => ['required', 'string', 'max:80'],
                'product_profile_change_reason' => ['required', 'string', 'min:10', 'max:1000'],
                'current_password' => ['required', 'string'],
            ], [
                'product_profile_confirmation.required' => 'اكتب عبارة التأكيد المطلوبة.',
                'product_profile_change_reason.required' => 'اكتب سبب تغيير نوع البرنامج.',
                'product_profile_change_reason.min' => 'سبب التغيير يجب ألا يقل عن 10 أحرف.',
                'current_password.required' => 'أدخل كلمة مرور Super Admin لتغيير نوع البرنامج.',
            ]);
            $expectedConfirmation = 'تغيير إلى ' . $productProfile;

            if (! hash_equals($expectedConfirmation, trim($sensitiveData['product_profile_confirmation']))) {
                return back()
                    ->withErrors(['product_profile_confirmation' => 'عبارة التأكيد غير مطابقة.'])
                    ->withInput($request->except('current_password'));
            }

            if (! DevSuperAdminSecuritySettings::matchesPassword($sensitiveData['current_password'])) {
                return back()
                    ->withErrors(['current_password' => 'كلمة مرور Super Admin غير صحيحة.'])
                    ->withInput($request->except('current_password'));
            }
        }

        DB::transaction(function () use ($request, $data, $productProfile, $currentProductProfile) {
            SystemModuleManager::updateProductProfile($productProfile);
            SystemModuleManager::update(
                array_values(array_unique(array_merge(
                    array_keys((array) $request->input('modules', [])),
                    SystemModuleManager::requiredModules($productProfile)
                ))),
                null
            );
            $dashboard = $productProfile === 'general'
                ? SystemModuleManager::defaultDashboardForProfile($productProfile)
                : (string) $request->input('default_dashboard', 'recruitment');
            SystemModuleManager::updateDefaultDashboard($dashboard);
            SystemModuleManager::updateBranchLimit($data['branches_limit'] ?? null);
            SystemModuleManager::updateUserLimit($data['users_limit'] ?? null);

            if ($productProfile !== $currentProductProfile && Schema::hasTable('system_product_profile_changes')) {
                DB::table('system_product_profile_changes')->insert([
                    'from_profile' => $currentProductProfile,
                    'to_profile' => $productProfile,
                    'changed_by' => (string) config('dev_superadmin.username'),
                    'reason' => trim((string) $request->input('product_profile_change_reason')),
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('dev-superadmin.modules')
            ->with('success', 'تم تحديث حالة الموديلات وإعداداتها بنجاح.');
    }

    public function enableScheduler(SchedulerMonitorService $schedulerMonitor)
    {
        try {
            $result = $schedulerMonitor->enableAndRun();
            $message = $result['installed']
                ? 'تمت إضافة ClickERP إلى Crontab وتشغيل Laravel Scheduler بنجاح.'
                : 'إعداد Crontab موجود مسبقًا، وتم تشغيل Laravel Scheduler الآن بنجاح.';

            return back()->with('success', $message);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['scheduler' => $exception->getMessage()]);
        }
    }

    protected function missingRecordedCoreTables(?\Illuminate\Support\Collection $appliedMigrations = null): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('migrations')) {
            return collect();
        }

        $appliedMigrations ??= DB::table('migrations')->pluck('migration')->unique();

        return collect(self::CORE_TABLE_CREATION_MIGRATIONS)
            ->filter(fn (string $migration, string $table) =>
                $appliedMigrations->contains($migration) && ! Schema::hasTable($table)
            )
            ->map(fn (string $migration, string $table) => [
                'table' => $table,
                'migration' => $migration,
            ])
            ->values();
    }

    public function updateSecurity(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'verification_email' => ['nullable', 'required_without:verification_phone', 'email:rfc', 'max:190'],
            'verification_phone' => ['nullable', 'required_without:verification_email', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'],
        ], [
            'verification_email.required_without' => 'أدخل بريد تحقق أو رقم جوال واحدًا على الأقل.',
            'verification_phone.required_without' => 'أدخل رقم جوال أو بريد تحقق واحدًا على الأقل.',
            'verification_phone.regex' => 'رقم الجوال غير صالح.',
            'password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 12 حرفًا.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير مطابق.',
            'password.regex' => 'كلمة المرور يجب أن تحتوي حرفًا كبيرًا وصغيرًا ورقمًا ورمزًا خاصًا.',
        ]);

        if (!DevSuperAdminSecuritySettings::matchesPassword($data['current_password'])) {
            return back()->withErrors(['current_password' => 'كلمة مرور Super Admin الحالية غير صحيحة.']);
        }

        $passwordChanged = filled($data['password'] ?? null);
        DevSuperAdminSecuritySettings::persist([
            'email' => $data['verification_email'] ?? '',
            'phone' => preg_replace('/\s+/', '', (string) ($data['verification_phone'] ?? '')),
            'password' => $data['password'] ?? '',
        ]);

        logger()->warning('Dev Super Admin security settings updated.', [
            'password_changed' => $passwordChanged,
            'email_configured' => filled($data['verification_email'] ?? null),
            'phone_configured' => filled($data['verification_phone'] ?? null),
            'ip' => $request->ip(),
        ]);

        if ($passwordChanged) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('superadmin.login')->with('success', 'تم تحديث إعدادات الأمان وكلمة المرور. سجل الدخول بكلمة المرور الجديدة.');
        }

        return back()->with('success', 'تم تحديث جهات اتصال التحقق وحفظها في ملف مشفر مستقل عن قاعدة البيانات.');
    }

    public function impersonate(Request $request, int $admin)
    {
        $admin = Admin::query()->findOrFail($admin);

        if (($admin->status ?? 'active') === 'suspended') {
            return back()->withErrors(['admin' => 'لا يمكن الدخول كموظف موقوف.']);
        }

        activity('authentication')->causedByAnonymous()->performedOn($admin)
            ->event('impersonated')->withProperties([
                'actor_name' => 'مدير النظام المطور',
                'dev_superadmin_impersonating' => true,
                'target_admin_id' => $admin->id,
            ])->log('الدخول بحساب مستخدم من لوحة المطور');
        $request->session()->put('dev_superadmin_impersonating', true);
        Auth::guard('admin')->login($admin);
        $this->startAdminSession($request, $admin);

        return redirect(SystemModuleManager::defaultDashboardUrl());
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'dev_superadmin_authenticated',
            'dev_superadmin_impersonating',
        ]);

        return redirect()->route('superadmin.login');
    }

    private function startAdminSession(Request $request, Admin $admin): void
    {
        $request->session()->put('admin_session_checked_at', time());

        if (! Schema::hasColumn('admins', 'current_session_id')) {
            return;
        }

        $admin->forceFill([
            'current_session_id' => $request->session()->getId(),
            'current_session_ip' => $request->ip(),
            'current_session_user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ])->save();
    }
}
