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
        // السجل التابع يرث فرع أبيه قبل الاعتماد على مستخدم الشاشة. هذا مهم
        // للإدارة العامة ومديري المناطق الذين قد ينشئون سجلاً لفرع آخر.
        $attributes = $this->getAttributes();

        if (! empty($attributes['payroll_id'])) {
            $branchId = $this->resolveBranchIdFromTable('hr_payrolls', (int) $attributes['payroll_id']);
            if ($branchId) {
                return $branchId;
            }
        }

        if (! empty($attributes['task_id'])) {
            $branchId = $this->resolveBranchIdFromTable('hr_tasks', (int) $attributes['task_id']);
            if ($branchId) {
                return $branchId;
            }
        }

        if (! empty($attributes['employee_id'])) {
            $branchId = $this->resolveBranchIdFromTable('hr_employee', (int) $attributes['employee_id']);
            if ($branchId) {
                return $branchId;
            }
        }

        if (! empty($attributes['admin_id'])) {
            $branchId = $this->resolveBranchIdFromAdmin((int) $attributes['admin_id']);
            if ($branchId) {
                return $branchId;
            }
        }

        if (! empty($attributes['maid_id'])) {
            $branchId = $this->resolveBranchIdFromMaid((int) $attributes['maid_id']);
            if ($branchId) {
                return $branchId;
            }
        }

        return $this->resolveBranchIdFromCurrentAdmin();
    }

    protected function resolveBranchIdFromTable(string $table, ?int $id): ?int
    {
        if (! $id || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return null;
        }

        $branchId = DB::table($table)->where('id', $id)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromCurrentAdmin(): ?int
    {
        if (\App\Support\DataImportBranchContext::$active) return \App\Support\DataImportBranchContext::$branchId;
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

    protected function resolveBranchIdFromOrderResponsibleAdmin(?int $orderId): ?int
    {
        if (!$orderId || !Schema::hasTable('orders')) {
            return null;
        }

        $adminId = null;
        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
            $adminId = DB::table('order_details')->where('order_id', $orderId)->value('admin_id');
        }

        if (!$adminId && Schema::hasColumn('orders', 'customer_id')) {
            $adminId = DB::table('orders')->where('id', $orderId)->value('customer_id');
        }

        return $this->resolveBranchIdFromAdmin($adminId ? (int) $adminId : null);
    }

    protected function resolveMainBranchId(): ?int
    {
        if (!Schema::hasTable('branches')) {
            return null;
        }

        $branchId = Branch::query()->where('is_main', true)->value('id')
            ?: Branch::query()->active()->ordered()->value('id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromUser(?int $userId): ?int
    {
        if (!$userId || !Schema::hasTable('users') || !Schema::hasColumn('users', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('users')->where('id', $userId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromComplaint(?int $complaintId): ?int
    {
        if (!$complaintId || !Schema::hasTable('complaints') || !Schema::hasColumn('complaints', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('complaints')->where('id', $complaintId)->value('branch_id');

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

    protected function resolveBranchIdFromRentalContract(?int $rentalContractId): ?int
    {
        if (!$rentalContractId || !Schema::hasTable('rental_contracts') || !Schema::hasColumn('rental_contracts', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('rental_contracts')->where('id', $rentalContractId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromInvoice(?int $invoiceId): ?int
    {
        if (!$invoiceId || !Schema::hasTable('invoices') || !Schema::hasColumn('invoices', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('invoices')->where('id', $invoiceId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromFinancialRequest(?int $financialRequestId): ?int
    {
        if (!$financialRequestId || !Schema::hasTable('financial_requests') || !Schema::hasColumn('financial_requests', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('financial_requests')->where('id', $financialRequestId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    protected function resolveBranchIdFromMaid(?int $maidId): ?int
    {
        if (!$maidId) {
            return null;
        }

        if (Schema::hasTable('maids') && Schema::hasColumn('maids', 'branch_id')) {
            $branchId = DB::table('maids')->where('id', $maidId)->value('branch_id');
            if ($branchId) {
                return (int) $branchId;
            }
        }

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'branch_id') && Schema::hasColumn('contracts', 'maid_id')) {
            $branchId = DB::table('contracts')
                ->where('maid_id', $maidId)
                ->whereNotNull('branch_id')
                ->latest('id')
                ->value('branch_id');

            if ($branchId) {
                return (int) $branchId;
            }
        }

        if (Schema::hasTable('rental_contracts') && Schema::hasColumn('rental_contracts', 'branch_id') && Schema::hasColumn('rental_contracts', 'maid_id')) {
            $branchId = DB::table('rental_contracts')
                ->where('maid_id', $maidId)
                ->whereNotNull('branch_id')
                ->latest('id')
                ->value('branch_id');

            if ($branchId) {
                return (int) $branchId;
            }
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
