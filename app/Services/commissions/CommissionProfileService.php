<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use Illuminate\Support\Facades\DB;

class CommissionProfileService
{
    public function __construct(
        private readonly CommissionOperationService $operationService,
        private readonly LegacyCommissionMarketerResolver $marketerResolver
    ) {}

    public function getMarketersWithBalance()
    {
        $marketerIds = $this->operationMarketerIds();

        return Admin::query()
            ->where(function ($query) use ($marketerIds) {
                $query->where('admin_type', 'customer_service')
                    ->orWhereIn('id', $marketerIds);
            })
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(function (Admin $admin) {
                $admin->commission_display_name = $this->marketerResolver->displayName($admin);
                $admin->commission_balance = $this->getMarketerBalance($admin->id);
                $admin->commission_summary = $this->operationService->summary($admin->id);
                return $admin;
            })
            ->sortByDesc(fn (Admin $admin) => $admin->commission_summary['total'])
            ->values();
    }

    public function hasOperations(int $marketerId): bool
    {
        return in_array($marketerId, $this->operationMarketerIds(), true);
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

    private function operationMarketerIds(): array
    {
        return collect()
            ->merge(DB::table('contracts')->whereNotNull('marketer_id')->pluck('marketer_id'))
            ->merge(DB::table('rental_contracts')->whereNotNull('marketer_id')->pluck('marketer_id'))
            ->merge(DB::table('transfer_service_contracts')->whereNotNull('created_by')->pluck('created_by'))
            ->merge(DB::table('ingaz_delegations')->whereNotNull('marketer_id')->pluck('marketer_id'))
            ->merge(DB::table('commission_batches')->whereNotNull('marketer_id')->pluck('marketer_id'))
            ->merge(DB::table('orders')->whereNotNull('customer_id')->pluck('customer_id'))
            ->merge(DB::table('order_details')->whereNotNull('admin_id')->pluck('admin_id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

}
