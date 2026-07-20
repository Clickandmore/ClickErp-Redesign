<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $mainBranchId = $this->mainBranchId();
        if (!$mainBranchId) {
            return;
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'branch_id')) {
            DB::table('orders')
                ->whereNull('branch_id')
                ->orderBy('id')
                ->chunkById(200, function ($orders) use ($mainBranchId) {
                    foreach ($orders as $order) {
                        DB::table('orders')->where('id', $order->id)->update([
                            'branch_id' => $this->orderBranchId($order, $mainBranchId),
                        ]);
                    }
                });
        }

        if (Schema::hasTable('contracts') && Schema::hasColumn('contracts', 'branch_id')) {
            DB::table('contracts')
                ->whereNull('branch_id')
                ->orderBy('id')
                ->chunkById(200, function ($contracts) use ($mainBranchId) {
                    foreach ($contracts as $contract) {
                        $marketerId = $contract->marketer_id ?? null;
                        $branchId = $marketerId
                            ? ($this->adminBranchId($marketerId) ?: $mainBranchId)
                            : null;

                        if (!$marketerId && !empty($contract->order_id)) {
                            $order = DB::table('orders')->where('id', $contract->order_id)->first();
                            $branchId = $order
                                ? ((int) ($order->branch_id ?? 0) ?: $this->orderBranchId($order, $mainBranchId))
                                : null;
                        }

                        DB::table('contracts')->where('id', $contract->id)->update([
                            'branch_id' => $branchId ?: $mainBranchId,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // The previous NULL values cannot be identified safely after assignment.
    }

    private function orderBranchId(object $order, int $mainBranchId): int
    {
        $adminId = null;
        if (Schema::hasTable('order_details')) {
            $adminId = DB::table('order_details')->where('order_id', $order->id)->value('admin_id');
        }

        $adminId = $adminId ?: ($order->customer_id ?? null);

        return $this->adminBranchId($adminId) ?: $mainBranchId;
    }

    private function adminBranchId($adminId): ?int
    {
        if (!$adminId || !Schema::hasTable('admins') || !Schema::hasColumn('admins', 'branch_id')) {
            return null;
        }

        $branchId = DB::table('admins')->where('id', $adminId)->value('branch_id');

        return $branchId ? (int) $branchId : null;
    }

    private function mainBranchId(): ?int
    {
        if (!Schema::hasTable('branches')) {
            return null;
        }

        $branchId = DB::table('branches')->where('is_main', true)->value('id')
            ?: DB::table('branches')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->value('id');

        return $branchId ? (int) $branchId : null;
    }
};
