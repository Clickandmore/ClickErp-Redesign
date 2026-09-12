<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class CommissionProfileService
{
    public function __construct(
        private readonly CommissionOperationService $operationService,
        private readonly LegacyCommissionMarketerResolver $marketerResolver
    ) {}

    public function getMarketersWithBalance()
    {
        return $this->marketersQuery()
            ->activeForOperations()
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
        foreach ($this->operationSources() as $table => $column) {
            if (DB::table($table)->where($column, $marketerId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function getPaymentMarketersWithBalance()
    {
        return $this->marketersQuery()
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

    public function marketersQuery(): Builder
    {
        return Admin::query()->where(function (Builder $query) {
            $query->where('admin_type', 'customer_service')
                ->orWhereNotNull('commission_payable_account_id');
            foreach ($this->operationSources() as $table => $column) {
                $query->orWhereIn('admins.id', DB::table($table)->select($column)->whereNotNull($column));
            }
        });
    }

    private function operationSources(): array
    {
        return [
            'contracts' => 'marketer_id',
            'rental_contracts' => 'marketer_id',
            'transfer_service_contracts' => 'created_by',
            'ingaz_delegations' => 'marketer_id',
            'commission_batches' => 'marketer_id',
            'orders' => 'customer_id',
            'order_details' => 'admin_id',
        ];
    }
}
