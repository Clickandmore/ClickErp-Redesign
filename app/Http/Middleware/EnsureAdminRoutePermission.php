<?php

namespace App\Http\Middleware;

use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminRoutePermission
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName();
        $disabledModule = SystemModuleManager::disabledModuleForRoute($routeName, $request->path());

        if ($disabledModule) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'هذا الموديل مغلق حالياً من لوحة Super Admin.',
                    'module' => $disabledModule['key'],
                ], 403);
            }

            return redirect()
                ->route('admin.no-permissions')
                ->with('warning', 'هذا الموديل مغلق حالياً من لوحة Super Admin.');
        }

        $requiredPermissions = AdminRoutePermissionMatrix::resolve($routeName, $request);

        if ($requiredPermissions === []) {
            return $next($request);
        }

        $admin = auth('admin')->user();

        if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, $requiredPermissions)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('ingaz.permission_denied'),
                'required_permissions' => $requiredPermissions,
            ], 403);
        }

        if ($routeName && str_starts_with($routeName, 'dashboard.')) {
            $fallbackUrl = AdminRoutePermissionMatrix::firstAccessibleUrlForAdmin($admin, [$routeName]);

            if ($fallbackUrl) {
                return redirect($fallbackUrl)->with('warning', __('ingaz.permission_denied'));
            }

            return redirect()->route('admin.no-permissions')->with('warning', __('ingaz.permission_denied'));
        }

        abort(403, __('ingaz.permission_denied'));
    }
}
