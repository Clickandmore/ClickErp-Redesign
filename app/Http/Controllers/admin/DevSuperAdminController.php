<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
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

    public function modules(Request $request)
    {
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

        return view('admin.superadmin.modules', [
            'modules' => SystemModuleManager::modules(),
            'enabledMap' => SystemModuleManager::enabledMap(),
            'dashboards' => SystemModuleManager::dashboards(),
            'defaultDashboardKey' => SystemModuleManager::defaultDashboardKey(),
            'branchLimit' => SystemModuleManager::branchLimit(),
            'admins' => $admins,
        ]);
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
