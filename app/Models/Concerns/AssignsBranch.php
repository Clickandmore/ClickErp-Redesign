<?php

namespace App\Models\Concerns;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait AssignsBranch
{
    public static function bootAssignsBranch(): void
    {
        static::saving(function ($model) {
            if (!$model->branchesFeatureAvailable() || !empty($model->branch_id)) {
                return;
            }

            $branchId = $model->resolveBranchIdForCreate();

            if ($branchId) {
                $model->branch_id = $branchId;
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromCurrentAdmin();
    }

    protected function resolveBranchIdFromCurrentAdmin(): ?int
    {
        $admin = Auth::guard('admin')->user();

        return !empty($admin?->branch_id) ? (int) $admin->branch_id : null;
    }

    protected function resolveBranchIdFromAdmin(?int $adminId): ?int
    {
        if (!$adminId || !Schema::hasTable('admins') || !Schema::hasColumn('admins', 'branch_id')) {
            return null;
        }

        $branchId = Admin::query()->whereKey($adminId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromOrder(?int $orderId): ?int
    {
        if (!$orderId || !Schema::hasTable('orders') || !Schema::hasColumn('orders', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('orders')->where('id', $orderId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromContract(?int $contractId): ?int
    {
        if (!$contractId || !Schema::hasTable('contracts')) {
            return null;
        }

        if (Schema::hasColumn('contracts', 'branch_id')) {
            $branchId = DB::table('contracts')->where('id', $contractId)->value('branch_id');

            if ($branchId) {
                return (int) $branchId;
            }
        }

        if (Schema::hasColumn('contracts', 'order_id')) {
            $orderId = DB::table('contracts')->where('id', $contractId)->value('order_id');
            $branchId = $this->resolveBranchIdFromOrder($orderId ? (int) $orderId : null);

            if ($branchId) {
                return $branchId;
            }
        }

        if (Schema::hasColumn('contracts', 'marketer_id')) {
            $adminId = DB::table('contracts')->where('id', $contractId)->value('marketer_id');

            return $this->resolveBranchIdFromAdmin($adminId ? (int) $adminId : null);
        }

        return null;
    }

    protected function branchesFeatureAvailable(): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($this->getTable())
            && Schema::hasColumn($this->getTable(), 'branch_id');
    }
}
