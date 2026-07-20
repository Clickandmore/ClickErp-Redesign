<?php

namespace App\Services;

use App\PermKeys;
use App\Models\Branch;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchAccessService
{
    public function enabled(): bool
    {
        return SystemModuleManager::isEnabled('branches');
    }

    public function tableHasBranchColumn(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id');
    }

    public function adminBranchId($admin = null): ?int
    {
        $admin ??= auth('admin')->user();
        $branchId = (int) ($admin?->branch_id ?? 0);

        return $branchId > 0 ? $branchId : null;
    }

    public function canViewAllBranches($admin = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $admin ??= auth('admin')->user();

        return (bool) $admin
            && AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::BRANCHES_VIEW_ALL]);
    }

    public function mainBranchId(): ?int
    {
        if (! $this->enabled() || ! Schema::hasTable('branches')) {
            return null;
        }

        $branch = Branch::query()->where('is_main', true)->first(['id'])
            ?: Branch::query()->active()->ordered()->first(['id']);

        return $branch ? (int) $branch->id : null;
    }

    public function isMainBranchId(?int $branchId): bool
    {
        return $branchId !== null && $branchId === $this->mainBranchId();
    }

    public function selectedBranchId(Request $request, $admin = null): ?int
    {
        if (! $this->enabled()) {
            return null;
        }

        $adminBranchId = $this->adminBranchId($admin);
        if ($adminBranchId && ! $this->canViewAllBranches($admin)) {
            return $adminBranchId;
        }

        $requestedBranchId = (int) $request->input('branch_id', 0);
        if ($requestedBranchId <= 0) {
            return null;
        }

        return Branch::query()->whereKey($requestedBranchId)->exists()
            ? $requestedBranchId
            : null;
    }

    public function selectorBranches($admin = null): Collection
    {
        if (! $this->enabled()) {
            return collect();
        }

        $adminBranchId = $this->adminBranchId($admin);
        $query = Branch::query()->active()->ordered();

        if ($adminBranchId && ! $this->canViewAllBranches($admin)) {
            $query->whereKey($adminBranchId);
        }

        return $query->get(['id', 'name_ar', 'name_en', 'code']);
    }

    public function selectorData(Request $request, $admin = null): array
    {
        $adminBranchId = $this->adminBranchId($admin);
        $canViewAllBranches = $this->canViewAllBranches($admin);

        return [
            'enabled' => $this->enabled(),
            'branches' => $this->selectorBranches($admin),
            'selected_branch_id' => $this->selectedBranchId($request, $admin),
            'forced_branch_id' => $this->enabled() && $adminBranchId && ! $canViewAllBranches ? $adminBranchId : null,
            'can_view_all_branches' => $this->enabled() && (! $adminBranchId || $canViewAllBranches),
        ];
    }

    public function applyToQuery($query, Request $request, string $table, ?string $qualifiedColumn = null, $admin = null)
    {
        if (! $this->enabled() || ! $this->tableHasBranchColumn($table)) {
            return $query;
        }

        $branchId = $this->selectedBranchId($request, $admin);

        if ($branchId) {
            $this->applyBranchIdToQuery($query, $branchId, $table, $qualifiedColumn);
        }

        return $query;
    }

    public function applyBranchIdToQuery($query, int $branchId, string $table, ?string $qualifiedColumn = null)
    {
        if (! $this->enabled() || ! $this->tableHasBranchColumn($table)) {
            return $query;
        }

        $column = $qualifiedColumn ?: "{$table}.branch_id";

        $tableReference = $qualifiedColumn && str_contains($qualifiedColumn, '.')
            ? substr($qualifiedColumn, 0, strrpos($qualifiedColumn, '.'))
            : $table;
        $unassignedBranch = $this->unassignedBranchExpression($table, $tableReference);

        if ($unassignedBranch) {
            [$expression, $bindings] = $unassignedBranch;

            $query->where(function ($branchQuery) use ($column, $branchId, $expression, $bindings) {
                $branchQuery->where($column, $branchId)
                    ->orWhere(function ($legacyQuery) use ($column, $branchId, $expression, $bindings) {
                        $legacyQuery->whereNull($column)
                            ->whereRaw("({$expression}) = ?", [...$bindings, $branchId]);
                    });
            });

            return $query;
        }

        if ($this->isMainBranchId($branchId)) {
            $query->where(function ($branchQuery) use ($column, $branchId) {
                $branchQuery->where($column, $branchId)->orWhereNull($column);
            });

            return $query;
        }

        return $query->where($column, $branchId);
    }

    public function canAccessModel(Model $model, $admin = null, ?string $table = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $table ??= $model->getTable();
        if (! $this->tableHasBranchColumn($table)) {
            return true;
        }

        $adminBranchId = $this->adminBranchId($admin);
        if (! $adminBranchId) {
            return true;
        }

        if ($this->canViewAllBranches($admin)) {
            return true;
        }

        $modelBranchId = (int) ($model->getAttribute('branch_id') ?? 0);

        if ($modelBranchId > 0) {
            return $modelBranchId === $adminBranchId;
        }

        $resolvedBranchId = $this->resolveUnassignedModelBranchId($model, $table);

        return $resolvedBranchId === $adminBranchId;
    }

    public function assertCanAccessModel(Model $model, $admin = null, ?string $table = null): void
    {
        abort_unless($this->canAccessModel($model, $admin, $table), 403);
    }

    private function unassignedBranchExpression(string $table, string $tableReference): ?array
    {
        $mainBranchId = $this->mainBranchId();
        if (! $mainBranchId) {
            return null;
        }

        if ($table === 'orders') {
            return [
                'COALESCE(
                        (SELECT COALESCE(responsible_admin.branch_id, ?) FROM order_details AS responsible_details INNER JOIN admins AS responsible_admin ON responsible_admin.id = responsible_details.admin_id WHERE responsible_details.order_id = '.$tableReference.'.id LIMIT 1),
                    (SELECT COALESCE(customer_admin.branch_id, ?) FROM admins AS customer_admin WHERE customer_admin.id = '.$tableReference.'.customer_id LIMIT 1),
                    ?
                )',
                [$mainBranchId, $mainBranchId, $mainBranchId],
            ];
        }

        if ($table === 'contracts') {
            return [
                'COALESCE(
                    (SELECT COALESCE(contract_marketer.branch_id, ?) FROM admins AS contract_marketer WHERE contract_marketer.id = '.$tableReference.'.marketer_id LIMIT 1),
                    (SELECT COALESCE(
                        (SELECT COALESCE(order_admin.branch_id, ?) FROM order_details AS contract_order_details INNER JOIN admins AS order_admin ON order_admin.id = contract_order_details.admin_id WHERE contract_order_details.order_id = contract_order.id LIMIT 1),
                        (SELECT COALESCE(order_customer.branch_id, ?) FROM admins AS order_customer WHERE order_customer.id = contract_order.customer_id LIMIT 1),
                        ?
                    ) FROM orders AS contract_order WHERE contract_order.id = '.$tableReference.'.order_id LIMIT 1),
                    ?
                )',
                [$mainBranchId, $mainBranchId, $mainBranchId, $mainBranchId, $mainBranchId],
            ];
        }

        return null;
    }

    private function resolveUnassignedModelBranchId(Model $model, string $table): ?int
    {
        if ($table === 'orders') {
            return $this->resolveOrderBranchId((int) $model->getKey());
        }

        if ($table === 'contracts') {
            $marketerId = (int) ($model->getAttribute('marketer_id') ?? 0);
            if ($marketerId > 0) {
                return $this->resolveAdminBranchId($marketerId);
            }

            $orderId = (int) ($model->getAttribute('order_id') ?? 0);

            return $orderId > 0 ? $this->resolveOrderBranchId($orderId) : $this->mainBranchId();
        }

        return $this->mainBranchId();
    }

    private function resolveOrderBranchId(int $orderId): ?int
    {
        if ($orderId <= 0 || ! Schema::hasTable('orders')) {
            return $this->mainBranchId();
        }

        $adminId = Schema::hasTable('order_details')
            ? (int) (DB::table('order_details')->where('order_id', $orderId)->value('admin_id') ?? 0)
            : 0;
        if ($adminId <= 0) {
            $adminId = (int) (DB::table('orders')->where('id', $orderId)->value('customer_id') ?? 0);
        }

        return $adminId > 0 ? $this->resolveAdminBranchId($adminId) : $this->mainBranchId();
    }

    private function resolveAdminBranchId(int $adminId): ?int
    {
        $branchId = (int) (DB::table('admins')->where('id', $adminId)->value('branch_id') ?? 0);

        return $branchId > 0 ? $branchId : $this->mainBranchId();
    }
}
