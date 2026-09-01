<?php

namespace App\Services\commissions;

use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionPayment;
use App\Models\commissions\CommissionBatch;
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
        private readonly YearService $yearService,
        private readonly CommissionAccrualService $accrualService
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

            if ($this->hasPendingAccrualConstraint($marketerId)) {
                throw ValidationException::withMessages([
                    'payment' => 'لا يمكن إصدار سند الصرف قبل اعتماد جميع قيود استحقاق العمولة ماليًا.',
                ]);
            }

            if ($this->hasAccrualBatches($marketerId)) {
                $payableAccount = $this->getDefaultPayableAccount($marketerId);
                if (! $payableAccount) {
                    throw ValidationException::withMessages([
                        'payment' => 'تعذر تحديد حساب استحقاق العمولة من قيد الاعتماد.',
                    ]);
                }
                $financialData['debit_account_id'] = $payableAccount->id;
            }

            if ((int) ($financialData['debit_account_id'] ?? 0) === (int) $financialData['credit_account_id']) {
                throw ValidationException::withMessages([
                    'credit_account_id' => 'حساب الصندوق أو البنك يجب أن يختلف عن حساب استحقاق العمولة.',
                ]);
            }

            $this->assertPayableAccount($marketerId, (int) $financialData['debit_account_id']);

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

    /** Keep the original approval immutable and record the correction in the employee ledger. */
    public function adjustApprovedBalance(
        int $marketerId,
        int $batchId,
        float $amount,
        string $reason,
        int $adminId
    ): CommissionBalanceTransaction {
        return DB::transaction(function () use ($marketerId, $batchId, $amount, $reason, $adminId) {
            Admin::query()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $batch = CommissionBatch::query()
                ->whereKey($batchId)
                ->where('marketer_id', $marketerId)
                ->where('status', CommissionBatch::STATUS_APPROVED)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = $this->profileService->getMarketerBalance($marketerId);
            if ($amount <= 0 || $amount > $currentBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'مبلغ التصحيح يجب أن يكون أكبر من صفر وألا يتجاوز الرصيد المتاح.',
                ]);
            }

            return CommissionBalanceTransaction::create([
                'marketer_id' => $marketerId,
                'type' => CommissionBalanceTransaction::TYPE_DEBIT,
                'amount' => $amount,
                'reference_type' => 'commission_adjustment',
                'reference_id' => $batch->id,
                'balance_after' => round($currentBalance - $amount, 2),
                'notes' => 'تصحيح اعتماد دفعة عمولة #' . $batch->id . ': ' . trim($reason),
                'created_by' => $adminId,
            ]);
        });
    }

    /**
     * Return an unpaid approval to the operations screen.
     * This is intentionally only allowed before any later ledger movement exists,
     * so deleting the approval cannot alter an already-issued payment history.
     */
    public function rollbackUnpaidBatch(int $marketerId, int $batchId): void
    {
        DB::transaction(function () use ($marketerId, $batchId) {
            Admin::query()->whereKey($marketerId)->lockForUpdate()->firstOrFail();
            $batch = CommissionBatch::query()
                ->with('items')
                ->whereKey($batchId)
                ->where('marketer_id', $marketerId)
                ->where('status', CommissionBatch::STATUS_APPROVED)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->items->contains(fn ($item) => ! empty($item->legacy_source))) {
                throw ValidationException::withMessages([
                    'batch' => 'لا يمكن إعادة فتح دفعة تاريخية أو مقفلة. استخدم قيد التصحيح عند الحاجة.',
                ]);
            }

            $credit = CommissionBalanceTransaction::query()
                ->where('marketer_id', $marketerId)
                ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
                ->where('reference_type', 'commission_batch')
                ->where('reference_id', $batch->id)
                ->lockForUpdate()
                ->first();

            if (! $credit) {
                throw ValidationException::withMessages([
                    'batch' => 'لا يمكن التراجع عن هذه الدفعة لأنها لا تملك قيد اعتماد قابلًا للإلغاء.',
                ]);
            }

            if (CommissionBalanceTransaction::query()->where('marketer_id', $marketerId)->where('id', '>', $credit->id)->exists()) {
                throw ValidationException::withMessages([
                    'batch' => 'لا يمكن التراجع بعد وجود حركة لاحقة على رصيد الموظف. استخدم قيد التصحيح للحفاظ على التسلسل المالي.',
                ]);
            }

            $delegationIds = $batch->items
                ->where('source_type', CommissionOperationService::SOURCE_DELEGATION)
                ->pluck('source_id')
                ->filter()
                ->all();
            if ($delegationIds) {
                \App\Models\IngazDelegation::query()
                    ->whereIn('id', $delegationIds)
                    ->where('commission_batch_id', $batch->id)
                    ->update(['commissioned_at' => null, 'commission_batch_id' => null]);
            }

            $this->accrualService->deleteForBatch($batch);
            $credit->delete();
            $batch->delete(); // its items are removed by the database cascade.
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

    public function getDefaultPayableAccount(int $marketerId): ?\Nami\Finance\Models\Account
    {
        $accountId = CommissionBatch::query()
            ->where('marketer_id', $marketerId)
            ->where('status', CommissionBatch::STATUS_APPROVED)
            ->whereNotNull('payable_account_id')
            ->whereHas('accrualConstraint', fn ($query) => $query->approved())
            ->latest('approved_at')
            ->value('payable_account_id');

        return $accountId
            ? \Nami\Finance\Models\Account::query()->find($accountId)
            : null;
    }

    public function hasAccrualBatches(int $marketerId): bool
    {
        return CommissionBatch::query()
            ->where('marketer_id', $marketerId)
            ->whereNotNull('accrual_constraint_id')
            ->exists();
    }

    public function hasPendingAccrualConstraint(int $marketerId): bool
    {
        return CommissionBatch::query()
            ->where('marketer_id', $marketerId)
            ->where('status', CommissionBatch::STATUS_APPROVED)
            ->whereNotNull('accrual_constraint_id')
            ->whereDoesntHave('accrualConstraint', fn ($query) => $query->approved())
            ->exists();
    }

    private function assertPayableAccount(int $marketerId, int $accountId): void
    {
        $accrualBatchesExist = $this->hasAccrualBatches($marketerId);

        if (! $accrualBatchesExist) {
            return;
        }

        $valid = CommissionBatch::query()
            ->where('marketer_id', $marketerId)
            ->where('status', CommissionBatch::STATUS_APPROVED)
            ->where('payable_account_id', $accountId)
            ->whereNotNull('accrual_constraint_id')
            ->whereHas('accrualConstraint', fn ($query) => $query->approved())
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'debit_account_id' => 'يجب اختيار حساب استحقاق العمولة المستخدم عند الاعتماد، ويجب أن يكون قيد الاستحقاق معتمدًا ماليًا.',
            ]);
        }
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

    public function getBalanceTransactionsForMarketer(int $marketerId)
    {
        return CommissionBalanceTransaction::query()
            ->with('createdBy')
            ->where('marketer_id', $marketerId)
            ->orderBy('id')
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
            ->where('reference_type', 'commission_payment')
            ->sum('amount');

        $reversed = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->where('reference_type', 'commission_reversal')
            ->sum('amount');
        $zeroAdjustments = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->where('reference_type', 'commission_zero_adjustment')
            ->sum('amount');
        $adjustments = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->where('reference_type', 'commission_adjustment')
            ->sum('amount');

        return [
            'approved' => round($approved, 2),
            'paid' => round($paid, 2),
            'reversed' => round($reversed, 2),
            'zero_adjustments' => round($zeroAdjustments, 2),
            'adjustments' => round($adjustments, 2),
            'balance' => round($approved - $paid - $reversed - $zeroAdjustments - $adjustments, 2),
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
            ->where('reference_type', 'commission_payment')
            ->sum('amount');
        $reversedTotal = (float) CommissionBalanceTransaction::query()
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->where('reference_type', 'commission_reversal')
            ->sum('amount');
        $marketerBalances = CommissionBalanceTransaction::query()
            ->selectRaw("marketer_id, SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as balance")
            ->groupBy('marketer_id')
            ->pluck('balance');
        $outstandingTotal = (float) $marketerBalances->filter(fn ($balance) => (float) $balance > 0)->sum();
        $companyReceivableTotal = abs((float) $marketerBalances->filter(fn ($balance) => (float) $balance < 0)->sum());

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
            'reversedTotal' => round($reversedTotal, 2),
            'outstandingTotal' => round($outstandingTotal, 2),
            'companyReceivableTotal' => round($companyReceivableTotal, 2),
            'employeesWithBalance' => $employees->where('commission_balance', '>', 0)->count(),
            'vouchersCount' => (int) CommissionPayment::query()->count(),
        ];
    }
}
