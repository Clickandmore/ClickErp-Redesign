<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Services\DatabaseBackupService;
use App\Services\SchedulerMonitorService;
use App\Support\DatabaseBackupSettings;
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
            && hash_equals((string) config('dev_superadmin.password'), (string) $data['password'])
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
        if ($activeSection === 'backups') {
            try {
                $backups = $databaseBackups->backups();
            } catch (\Throwable $exception) {
                report($exception);
                $backupListError = $exception->getMessage();
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
            'r2Ready' => $activeSection === 'backups' && R2StorageSettings::isReady(),
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
