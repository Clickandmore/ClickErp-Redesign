<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;

class CommissionProfileService
{
    public function __construct(
        private readonly CommissionOperationService $operationService
    ) {}

    public function getMarketersWithBalance()
    {
        return Admin::query()
            ->where('admin_type', 'customer_service')
            ->activeForOperations()
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(function (Admin $admin) {
                $admin->commission_balance = $this->getMarketerBalance($admin->id);
                $admin->commission_summary = $this->operationService->summary($admin->id);
                return $admin;
            });
    }

    public function getCustomerServiceEmployeesWithBalance()
    {
        return Admin::query()
            ->where('admin_type', 'customer_service')
            ->activeForOperations()
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(function (Admin $admin) {
                $admin->commission_balance = $this->getMarketerBalance($admin->id);

                return $admin;
            });
    }

    public function getMarketerBalance(int $marketerId): float
    {
        $credit = CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');

        $debit = CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');

        return round((float) $credit - (float) $debit, 2);
    }

    public function getOperations(int $marketerId, array $filters = [])
    {
        return $this->operationService->getOperations($marketerId, $filters);
    }

    public function getOperationSummary(int $marketerId): array
    {
        return $this->operationService->summary($marketerId);
    }
}
