<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // التحقق من تسجيل الدخول
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        // التحقق من الصلاحية
        if (!AdminGuardHasPerm($permission)) {
            // إذا كان الطلب AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية للوصول لهذه الصفحة',
                    'code' => 403
                ], 403);
            }

            // إذا كان الطلب عادي
            return redirect()->route('admin.dashboard')->with('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
        }

        return $next($request);
    }
}
