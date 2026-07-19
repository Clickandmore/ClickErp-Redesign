<?php

namespace App\Services\commissions;

use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionPayment;
use App\Models\auth\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Models\Bond;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;

class CommissionPaymentService
{
    public function __construct(
        private readonly CommissionProfileService $profileService,
        private readonly BondService $bondService,
        private readonly YearService $yearService
    ) {}

    public function issuePayment(
        int $marketerId,
        float $amount,
        string $paymentDate,
        ?string $notes,
        int $adminId,
        array $financialData
    ): CommissionPayment
    {
        return DB::transaction(function () use ($marketerId, $amount, $paymentDate, $notes, $adminId, $financialData) {
            $marketer = Admin::query()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $currentBalance = $this->profileService->getMarketerBalance($marketerId);

            if ($amount <= 0 || $amount > $currentBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ سند الصرف يجب أن يكون أكبر من صفر وألا يتجاوز الرصيد المتاح.',
                ]);
            }

            $payment = CommissionPayment::create([
                'marketer_id'  => $marketerId,
                'amount'       => $amount,
                'payment_date' => $paymentDate,
                'notes'        => $notes,
                'status'       => CommissionPayment::STATUS_APPROVED,
                'approved_by'  => $adminId,
                'approved_at'  => now(),
                'created_by'   => $adminId,
            ]);

            $this->createFinanceBond($payment, $marketer, $financialData);

            $balanceAfter = round($currentBalance - $amount, 2);

            CommissionBalanceTransaction::create([
                'marketer_id'    => $marketerId,
                'type'           => CommissionBalanceTransaction::TYPE_DEBIT,
                'amount'         => $amount,
                'reference_type' => 'commission_payment',
                'reference_id'   => $payment->id,
                'balance_after'  => $balanceAfter,
                'notes'          => "سداد عمولة - سند رقم #{$payment->id}",
                'created_by'     => $adminId,
            ]);

            return $payment->fresh('bond');
        });
    }

    public function linkExistingPaymentToFinance(
        int $marketerId,
        int $paymentId,
        array $financialData
    ): CommissionPayment {
        return DB::transaction(function () use ($marketerId, $paymentId, $financialData) {
            $marketer = Admin::query()->whereKey($marketerId)->firstOrFail();
            $payment = CommissionPayment::query()
                ->where('marketer_id', $marketerId)
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->bond_id) {
                throw ValidationException::withMessages([
                    'payment' => 'سند العمولة مربوط بالفعل بسند صرف مالي.',
                ]);
            }

            $this->createFinanceBond($payment, $marketer, $financialData);

            return $payment->fresh('bond');
        });
    }

    private function createFinanceBond(
        CommissionPayment $payment,
        Admin $marketer,
        array $financialData
    ): Bond {
        if (! $this->yearService->checkDateRange($payment->payment_date)) {
            throw ValidationException::withMessages([
                'payment_date' => 'تاريخ سند الصرف لا يقع داخل سنة مالية نشطة.',
            ]);
        }

        $statement = trim((string) ($payment->notes ?: 'صرف عمولة موظف'));
        $statement = "صرف عمولة الموظف {$marketer->name} - سند عمولات #{$payment->id} - {$statement}";

        $bondData = [
            'payment_type' => (int) $financialData['payment_type'],
            'bond_book_num' => 'COM-' . $payment->id,
            'debit_account_id' => (int) $financialData['debit_account_id'],
            'credit_account_id' => (int) $financialData['credit_account_id'],
            'cost_center_id' => $financialData['cost_center_id'] ?? null,
            'value' => (float) $payment->amount,
            'date' => $payment->payment_date->format('Y-m-d'),
            'statement' => mb_substr($statement, 0, 500),
            'general_type' => 0,
            'is_tax' => 0,
            'net' => (float) $payment->amount,
            'code' => $this->bondService->lastCode(BondTypes::Sarf->value),
            // fn_bonds.transfer_number is non-nullable in the current finance schema.
            'transfer_number' => trim((string) ($financialData['transfer_number'] ?? '')),
            'check_num' => $financialData['check_num'] ?? null,
            'check_date' => $financialData['check_date'] ?? null,
            'status' => 0,
        ];

        $bond = $this->bondService->storeSarfBond($bondData);
        $this->bondService->checkIsConfirmSetting($bond, ConfirmSettingTypes::SarfBond->value);
        $bond->refresh();

        $payment->update(['bond_id' => $bond->id]);

        return $bond;
    }

    public function getFinancialFormData(): array
    {
        return [
            'paymentTypes' => $this->bondService->getBondPaymentTypies(),
            'costCenterRequired' => $this->isCostCenterRequired(),
        ];
    }

    public function isCostCenterRequired(): bool
    {
        $setting = $this->bondService->getTaxRate(ConfirmSettingTypes::CostCenterSarfBond->value);

        return ($setting?->value ?? 'off') === 'on';
    }

    public function getPaymentsForMarketer(int $marketerId)
    {
        return CommissionPayment::query()
            ->with(['approvedBy', 'createdBy', 'bond.constraint'])
            ->where('marketer_id', $marketerId)
            ->latest()
            ->get();
    }

    public function getPaymentSummary(int $marketerId): array
    {
        $approved = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');

        $paid = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');

        return [
            'approved' => round($approved, 2),
            'paid' => round($paid, 2),
            'balance' => round($approved - $paid, 2),
            'payments_count' => CommissionPayment::query()
                ->where('marketer_id', $marketerId)
                ->count(),
        ];
    }

    public function getFinancialOverview(): array
    {
        $employees = $this->profileService->getCustomerServiceEmployeesWithBalance();
        $paymentTotals = CommissionPayment::query()
            ->selectRaw('marketer_id, COUNT(*) as vouchers_count, COALESCE(SUM(amount), 0) as paid_total')
            ->groupBy('marketer_id')
            ->get()
            ->keyBy('marketer_id');

        $employees->each(function (Admin $employee) use ($paymentTotals) {
            $totals = $paymentTotals->get($employee->id);
            $employee->commission_paid_total = (float) ($totals?->paid_total ?? 0);
            $employee->commission_vouchers_count = (int) ($totals?->vouchers_count ?? 0);
        });

        $approvedTotal = (float) CommissionBalanceTransaction::query()
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');
        $paidTotal = (float) CommissionBalanceTransaction::query()
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');

        return [
            'employees' => $employees,
            'recentPayments' => CommissionPayment::query()
                ->with(['marketer', 'approvedBy'])
                ->latest('payment_date')
                ->latest('id')
                ->take(15)
                ->get(),
            'approvedTotal' => round($approvedTotal, 2),
            'paidTotal' => round($paidTotal, 2),
            'outstandingTotal' => round($approvedTotal - $paidTotal, 2),
            'employeesWithBalance' => $employees->where('commission_balance', '>', 0)->count(),
            'vouchersCount' => (int) CommissionPayment::query()->count(),
        ];
    }
}
