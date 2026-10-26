<?php

namespace App\Http\Middleware;

use App\Support\DashboardMaintenanceSettings;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Admin
{

    public function handle($request, Closure $next = null, $guard = null)
    {
        Auth::shouldUse('admin');
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            if (($admin->status ?? 'active') === 'suspended') {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('admin.form.login')
                    ->with('status', __('admin.account_suspended_now'));
            }

            if (DashboardMaintenanceSettings::enabled()) {
                $settings = DashboardMaintenanceSettings::all();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => $settings['message'],
                        'maintenance' => true,
                    ], 503)->header('Retry-After', '300');
                }

                return response()
                    ->view('admin.maintenance', ['maintenance' => $settings], 503)
                    ->header('Retry-After', '300')
                    ->header('Cache-Control', 'no-store, private')
                    // الصفحة عربية ونصها من Super Admin؛ الترجمة التلقائية كانت تحوّل «نعمل» إلى «Yesل».
                    ->header(TranslateHardcodedOutput::SKIP_HEADER, '1');
            }

            $lastCheck = (int) $request->session()->get('admin_session_checked_at', 0);

            if (time() - $lastCheck >= 60) {
                $request->session()->put('admin_session_checked_at', time());

                if (! empty($admin->current_session_id) && $admin->current_session_id !== $request->session()->getId()) {
                    Auth::guard('admin')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()
                        ->route('admin.form.login')
                        ->with('status', 'تم إنهاء الجلسة لأن الحساب تم استخدامه من جهاز آخر.');
                }

                if (Schema::hasColumn('admins', 'last_activity_at')) {
                    $admin->forceFill([
                        'last_activity_at' => now(),
                    ])->save();
                }
            }

            return $next($request);
        }
        return redirect()->route("admin.form.login");
    }
}
