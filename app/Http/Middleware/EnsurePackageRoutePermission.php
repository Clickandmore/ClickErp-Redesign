<?php

namespace App\Http\Middleware;

use App\Enums\AdminTypeisEnum;
use App\Support\BranchScopedPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnsurePackageRoutePermission
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            abort(403, __('ingaz.permission_denied'));
        }

        if ((int) ($admin->admin_type ?? 0) === AdminTypeisEnum::Developer->value) {
            return $next($request);
        }

        $permissions = $this->resolvePermissions($request->route()?->getName(), $module);

        if ($permissions === [] || $this->adminHasAnyPermission($admin, $permissions)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('ingaz.permission_denied'),
                'required_permissions' => $permissions,
            ], 403);
        }

        abort(403, __('ingaz.permission_denied'));
    }

    private function resolvePermissions(?string $routeName, string $module): array
    {
        if (!$routeName) {
            return [];
        }

        return match ($module) {
            'finance' => $this->financePermissions($routeName),
            'hr' => $this->hrPermissions($routeName),
            'zatca' => $this->zatcaPermissions($routeName),
            default => [],
        };
    }

    private function financePermissions(string $routeName): array
    {
        if ($routeName === 'finance.constraints.approve') {
            return ['finance.constraints-approve', 'finance_module'];
        }

        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'finance.');
        $resource = $resource === 'home' ? 'dashboard' : $resource;

        return $this->resourcePermissions('finance', $resource, $action, 'finance_module');
    }

    private function hrPermissions(string $routeName): array
    {
        if (Str::startsWith($routeName, 'hr.maids.')) {
            return match (Str::after($routeName, 'hr.maids.')) {
                'index' => ['hr_employees_view', 'hr_module'],
                'employ' => ['hr_employees_edit', 'hr_module'],
                default => ['hr_module'],
            };
        }

        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'hr.');
        $resource = $resource === 'home' ? 'dashboard' : $resource;

        return $this->resourcePermissions('hr', $resource, $action, 'hr_module');
    }

    private function zatcaPermissions(string $routeName): array
    {
        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'zatca.');

        return $this->resourcePermissions('zatca', $resource, $action, 'finance_module');
    }

    private function splitPrefixedRoute(string $routeName, string $prefix): array
    {
        $routeName = Str::startsWith($routeName, $prefix) ? Str::after($routeName, $prefix) : $routeName;
        $parts = explode('.', $routeName);
        $action = array_pop($parts) ?: 'index';

        return [implode('.', $parts), $action];
    }

    private function resourcePermissions(string $module, string $resource, string $action, string $modulePermission): array
    {
        $ability = match ($action) {
            'index', 'show', 'pending', 'export' => 'view',
            'create', 'store' => 'create',
            'edit', 'update', 'toggleStatus', 'toggle-status', 'year-status' => 'edit',
            'destroy', 'delete' => 'delete',
            default => 'view',
        };

        $normalizedResource = trim(Str::snake(str_replace(['.', '-'], '_', $resource)), '_');

        if ($normalizedResource === '') {
            return [$modulePermission];
        }

        return array_values(array_unique([
            "{$module}_{$normalizedResource}_{$ability}",
            $modulePermission,
        ]));
    }

    private function adminHasAnyPermission($admin, array $permissions): bool
    {
        $branchPermission = BranchScopedPermissions::adminHasAnyPermission($admin, $permissions);
        if ($branchPermission !== null) {
            return $branchPermission;
        }

        if (method_exists($admin, 'hasAnyPermission') && $admin->hasAnyPermission($permissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
