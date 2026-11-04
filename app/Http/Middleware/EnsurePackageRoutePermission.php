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

    /**
     * الصلاحيات المطلوبة لمسار حزمة معينة. عامة ليستخدمها كتالوج الصلاحيات
     * (App\Support\PermissionCatalog) في التدقيق والمزامنة بدون Reflection.
     */
    public function resolvePermissions(?string $routeName, string $module): array
    {
        if (!$routeName) {
            return [];
        }

        return match ($module) {
            'finance' => $this->financePermissions($routeName),
            'hr' => $this->hrPermissions($routeName),
            'zatca' => $this->zatcaPermissions($routeName),
            'crm' => $this->crmPermissions($routeName),
            'employee' => $this->employeePermissions($routeName),
            default => [],
        };
    }

    /** هل يملك المشرف صلاحية مسار حزمة معين (لإظهار الأزرار بنفس قاعدة الحماية). */
    public function allows($admin, string $routeName, string $module): bool
    {
        if (!$admin) {
            return false;
        }

        if ((int) ($admin->admin_type ?? 0) === AdminTypeisEnum::Developer->value) {
            return true;
        }

        $permissions = $this->resolvePermissions($routeName, $module);

        return $permissions === [] || $this->adminHasAnyPermission($admin, $permissions);
    }

    private function crmPermissions(string $routeName): array
    {
        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'crm.');
        $resource = $resource === 'home' ? 'dashboard' : $resource;

        return $this->resourcePermissions('crm', $resource, $action, 'crm_module');
    }

    private function employeePermissions(string $routeName): array
    {
        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'employee.');
        $resource = $resource === 'home' ? 'dashboard' : $resource;

        return $this->resourcePermissions('employee', $resource, $action, 'employee_module');
    }

    private function financePermissions(string $routeName): array
    {
        if (Str::startsWith($routeName, 'finance.procurement.')) {
            $action = Str::after($routeName, 'finance.procurement.');
            $ability = match ($action) {
                'approve' => 'approve', 'pay' => 'pay',
                'convert', 'create', 'store' => 'create',
                'edit', 'update', 'cancel' => 'edit', default => 'view',
            };
            return ['finance_procurement_'.$ability, 'finance_module'];
        }

        // إعادة هيكلة الدليل تغيّر قيوداً تاريخية؛ صلاحيتها حصرية بلا بديل finance_module.
        foreach ([
            'finance.accounting-guide-management.destroy' => 'finance_accounting_guide_management_delete',
            'finance.accounts.destroy' => 'finance_accounting_guide_management_delete',
            'finance.accounting-guide-management.move*' => 'finance_accounting_guide_management_move',
            'finance.accounting-guide-management.merge*' => 'finance_accounting_guide_management_merge',
            'finance.chart-settings.*' => 'finance_chart_settings_manage',
        ] as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return [$permission];
            }
        }

        $exportPermission = $this->financeExportPermission($routeName);

        if ($exportPermission !== null) {
            return [$exportPermission];
        }

        if ($routeName === 'finance.constraints.approve') {
            return ['finance.constraints-approve', 'finance_module'];
        }

        [$resource, $action] = $this->splitPrefixedRoute($routeName, 'finance.');
        $resource = $resource === 'home' ? 'dashboard' : $resource;

        return $this->resourcePermissions('finance', $resource, $action, 'finance_module');
    }

    private function financeExportPermission(string $routeName): ?string
    {
        foreach ([
            'finance.constraints.export' => 'finance_constraints_export',
            'finance.accounting-guide-management.export' => 'finance_accounting_guide_management_export',
            'finance.bill-sales.export.*' => 'finance_bill_sales_export',
            'finance.account-statement.export' => 'finance_account_statement_export',
            'finance.account-statement-print.*' => 'finance_account_statement_export',
            'finance.account-statement-clients-print.*' => 'finance_account_statement_clients_export',
            'finance.account-statement-clients.export' => 'finance_account_statement_clients_export',
            'finance.account-statement-supplier-print.*' => 'finance_account_statement_suppliers_export',
            'finance.bank-account-statement-print.*' => 'finance_bank_account_statement_export',
            'finance.bank-account-statement.export' => 'finance_bank_account_statement_export',
            'finance.double-account-statement-print.*' => 'finance_double_account_statement_export',
            'finance.double-account-statement.export' => 'finance_double_account_statement_export',
            'finance.trail-balance.export' => 'finance_trail_balance_export',
            'finance.client-trial-balance.export' => 'finance_client_trial_balance_export',
            'finance.bonds-qabd.export' => 'finance_bonds_export',
            'finance.bonds-sarf.export' => 'finance_bonds_export',
            'finance.bonds-print.*' => 'finance_bonds_export',
        ] as $pattern => $permission) {
            if (Str::is($pattern, $routeName)) {
                return $permission;
            }
        }

        return null;
    }

    private function hrPermissions(string $routeName): array
    {
        if (Str::startsWith($routeName, 'hr.maids.')) {
            return match (Str::after($routeName, 'hr.maids.')) {
                'index', 'employees' => ['hr_employees_view', 'hr_module'],
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
