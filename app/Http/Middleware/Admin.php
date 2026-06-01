<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class Admin
{

    public function handle($request, Closure $next = null, $guard = null)
    {
        Config::set('auth.defaults.guard', 'admin');
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
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
