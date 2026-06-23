<?php

namespace App\Services;

use App\Models\Branch;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function selectedBranchId(Request $request, $admin = null): ?int
    {
        if (! $this->enabled()) {
            return null;
        }

        $adminBranchId = $this->adminBranchId($admin);
        if ($adminBranchId) {
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

        if ($adminBranchId) {
            $query->whereKey($adminBranchId);
        }

        return $query->get(['id', 'name_ar', 'name_en', 'code']);
    }

    public function selectorData(Request $request, $admin = null): array
    {
        $adminBranchId = $this->adminBranchId($admin);

        return [
            'enabled' => $this->enabled(),
            'branches' => $this->selectorBranches($admin),
            'selected_branch_id' => $this->selectedBranchId($request, $admin),
            'forced_branch_id' => $adminBranchId,
            'can_view_all_branches' => $this->enabled() && ! $adminBranchId,
        ];
    }

    public function applyToQuery($query, Request $request, string $table, ?string $qualifiedColumn = null, $admin = null)
    {
        if (! $this->enabled() || ! $this->tableHasBranchColumn($table)) {
            return $query;
        }

        $branchId = $this->selectedBranchId($request, $admin);

        if ($branchId) {
            $query->where($qualifiedColumn ?: "{$table}.branch_id", $branchId);
        }

        return $query;
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

        $modelBranchId = (int) ($model->getAttribute('branch_id') ?? 0);

        return $modelBranchId === 0 || $modelBranchId === $adminBranchId;
    }

    public function assertCanAccessModel(Model $model, $admin = null, ?string $table = null): void
    {
        abort_unless($this->canAccessModel($model, $admin, $table), 403);
    }
}
