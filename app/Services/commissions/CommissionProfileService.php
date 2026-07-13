<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\IngazDelegation;
use Illuminate\Database\Eloquent\Builder;

class CommissionProfileService
{
    public function getMarketersWithBalance()
    {
        return Admin::query()
            ->whereHas('ingazDelegations')
            ->withCount('ingazDelegations as total_delegations')
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

    public function getDelegationsQuery(int $marketerId, array $filters = []): Builder
    {
        $query = IngazDelegation::query()
            ->with(['nationality', 'country', 'profession', 'user', 'invoice'])
            ->where('marketer_id', $marketerId);

        if (! empty($filters['date_filter'])) {
            match ($filters['date_filter']) {
                'month_year' => $this->applyMonthYearFilter($query, $filters),
                'range'      => $this->applyRangeFilter($query, $filters),
                default      => null,
            };
        }

        return $query->latest();
    }

    public function getTotalDelegationsCount(int $marketerId): int
    {
        return IngazDelegation::where('marketer_id', $marketerId)->count();
    }

    private function applyMonthYearFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['month'])) {
            $query->whereMonth('delegation_date_gregorian', $filters['month']);
        }
        if (! empty($filters['year'])) {
            $query->whereYear('delegation_date_gregorian', $filters['year']);
        }
    }

    private function applyRangeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['from_date'])) {
            $query->where('delegation_date_gregorian', '>=', $filters['from_date']);
        }
        if (! empty($filters['to_date'])) {
            $query->where('delegation_date_gregorian', '<=', $filters['to_date']);
        }
    }
}
