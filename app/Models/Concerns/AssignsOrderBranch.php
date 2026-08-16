<?php

namespace App\Models\Concerns;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait AssignsOrderBranch
{
    public static function bootAssignsOrderBranch(): void
    {
        static::creating(function ($order) {
            if (!$order->orderBranchesFeatureAvailable() || !empty($order->branch_id)) {
                return;
            }

            $branchId = $order->resolveOrderBranchIdForCreate();

            if ($branchId) {
                $order->branch_id = $branchId;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function syncBranchFromResponsibleAdmin(?int $adminId, bool $force = false): void
    {
        if (!$this->orderBranchesFeatureAvailable() || (!$force && !empty($this->branch_id))) {
            return;
        }

        $branchId = $adminId
            ? ($this->resolveOrderBranchIdFromAdmin($adminId) ?: $this->resolveOrderMainBranchId())
            : $this->resolveOrderMainBranchId();

        if ($branchId) {
            $this->forceFill(['branch_id' => $branchId])->saveQuietly();
        }
    }

    protected function resolveOrderBranchIdForCreate(): ?int
    {
        $maidId = (int) ($this->maid_id ?? 0);
        if ($maidId > 0 && Schema::hasTable('maids') && Schema::hasColumn('maids', 'branch_id')) {
            $maidBranchId = DB::table('maids')->where('id', $maidId)->value('branch_id');
            if ($maidBranchId) {
                return (int) $maidBranchId;
            }
        }

        $responsibleAdminId = (int) ($this->customer_id ?? 0);
        if ($responsibleAdminId > 0) {
            return $this->resolveOrderBranchIdFromAdmin($responsibleAdminId)
                ?: $this->resolveOrderMainBranchId();
        }

        return $this->resolveOrderBranchIdFromCurrentAdmin()
            ?: $this->resolveOrderMainBranchId();
    }

    protected function resolveOrderBranchIdFromCurrentAdmin(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return !empty($admin?->branch_id) ? (int) $admin->branch_id : null;
    }

    protected function resolveOrderBranchIdFromAdmin(?int $adminId): ?int
    {
        if (!$adminId || !Schema::hasColumn('admins', 'branch_id')) {
            return null;
        }

        $branchId = Admin::query()->whereKey($adminId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveOrderMainBranchId(): ?int
    {
        if (!Schema::hasTable('branches')) {
            return null;
        }

        $branchId = Branch::query()->where('is_main', true)->value('id')
            ?: Branch::query()->active()->ordered()->value('id');

        return $branchId ? (int) $branchId : null;
    }

    protected function orderBranchesFeatureAvailable(): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'branch_id');
    }
}
