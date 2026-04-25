<?php

namespace App\Http\Middleware;

use App\Support\AdminRoutePermissionMatrix;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminRoutePermission
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName();
        $requiredPermissions = AdminRoutePermissionMatrix::resolve($routeName, $request);

        if ($requiredPermissions === []) {
            return $next($request);
        }

        $admin = auth('admin')->user();

        if ($admin && method_exists($admin, 'hasAnyPermission') && $admin->hasAnyPermission($requiredPermissions)) {
            return $next($request);
        }

        if ($admin) {
            foreach ($requiredPermissions as $permission) {
                if ($admin->can($permission)) {
                    return $next($request);
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('ingaz.permission_denied'),
                'required_permissions' => $requiredPermissions,
            ], 403);
        }

        abort(403, __('ingaz.permission_denied'));
    }
}
