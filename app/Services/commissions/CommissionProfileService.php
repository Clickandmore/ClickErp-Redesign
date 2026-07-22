<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommissionProfileService
{
    public function __construct(
        private readonly CommissionOperationService $operationService
    ) {}

    public function getMarketersWithBalance()
    {
        $marketerIds = $this->operationMarketerIds();
        $legacyVoucherTotals = $this->legacyVoucherTotals();

        return Admin::query()
            ->where(function ($query) use ($marketerIds) {
                $query->where('admin_type', 'customer_service')
                    ->orWhereIn('id', $marketerIds);
            })
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(function (Admin $admin) use ($legacyVoucherTotals) {
                $voucherTotals = $legacyVoucherTotals->get($this->normalizeMarketerName($admin->name));
                $admin->commission_balance = $this->getMarketerBalance($admin->id);
                $admin->commission_summary = $this->operationService->summary($admin->id);
                $admin->legacy_commission_vouchers_count = $voucherTotals
                    ? (int) $voucherTotals->vouchers_count
                    : null;
                $admin->legacy_commission_paid_total = $voucherTotals
                    ? (float) $voucherTotals->paid_total
                    : null;
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
            ->merge(DB::table('orders')->whereNotNull('customer_id')->pluck('customer_id'))
            ->merge(DB::table('order_details')->whereNotNull('admin_id')->pluck('admin_id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function legacyVoucherTotals()
    {
        if (! Schema::hasTable('fn_bonds') || ! Schema::hasTable('fn_accounts')) {
            return collect();
        }

        return DB::table('fn_bonds as bonds')
            ->join('fn_accounts as accounts', 'accounts.id', '=', 'bonds.debit_account_id')
            ->where('bonds.type', 'sarf')
            ->where('bonds.legacy_source', 'alrafedin_finance_bonds')
            ->where('bonds.legacy_source_file', 'like', '%سندات الصرف%')
            ->where('accounts.code', 'like', '144%')
            ->groupBy('accounts.name')
            ->selectRaw('accounts.name, COUNT(*) as vouchers_count, COALESCE(SUM(bonds.value), 0) as paid_total')
            ->get()
            ->keyBy(fn ($row) => $this->normalizeMarketerName($row->name));
    }

    private function normalizeMarketerName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه',
            'ؤ' => 'و', 'ئ' => 'ي', 'ـ' => '',
        ]);

        return preg_replace('/[\s\p{P}\p{S}_]+/u', '', $value) ?: $value;
    }
}
