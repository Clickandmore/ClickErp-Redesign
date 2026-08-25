<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use App\Services\SchedulerMonitorService;
use App\Support\DatabaseBackupSettings;
use App\Support\DevSuperAdminSecuritySettings;
use App\Support\ClientSubscriptionSettings;
use App\Support\R2StorageSettings;
use App\Support\ProductUpdatesSettings;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DevSuperAdminController extends Controller
{
    public function loginForm()
    {
        if ((bool) session('dev_superadmin_authenticated')) {
            return redirect()->route('dev-superadmin.modules');
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

            return redirect()->route('dev-superadmin.modules');
        }

        return back()
            ->withInput(['username' => $data['username']])
            ->withErrors(['username' => 'بيانات دخول لوحة التطوير غير صحيحة.']);
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
            'dashboards' => SystemModuleManager::dashboards(),
            'defaultDashboardKey' => SystemModuleManager::defaultDashboardKey(),
            'branchLimit' => SystemModuleManager::branchLimit(),
            'admins' => $admins,
            'backupSettings' => $activeSection === 'backups' ? DatabaseBackupSettings::all() : null,
            'databaseBackups' => $backups,
            'localDatabaseBackups' => $activeSection === 'backups' ? $databaseBackups->localBackups() : [],
            'backupListError' => $backupListError,
            'backupVerificationChannels' => $activeSection === 'backups' ? $backupVerification->availableChannels() : [],
            'backupVerificationChallenge' => $activeSection === 'backups' ? $backupVerification->current($request) : null,
            'devSuperAdminSecurity' => $activeSection === 'backups' ? DevSuperAdminSecuritySettings::all() : null,
            'r2Ready' => $r2Ready,
            'schedulerStatus' => $activeSection === 'scheduler' ? $schedulerMonitor->status() : null,
            'subscription' => ClientSubscriptionSettings::all(),
            'productUpdates' => ProductUpdatesSettings::all(),
            'activeSection' => $activeSection,
        ]);
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
        $data = $request->validate([
            'branches_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        SystemModuleManager::update(
            array_keys((array) $request->input('modules', [])),
            null
        );
        SystemModuleManager::updateDefaultDashboard((string) $request->input('default_dashboard', 'recruitment'));
        SystemModuleManager::updateBranchLimit($data['branches_limit'] ?? null);

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

        Auth::guard('admin')->login($admin);
        $this->startAdminSession($request, $admin);
        $request->session()->put('dev_superadmin_impersonating', true);

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
