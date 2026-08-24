<?php

namespace App\Services\ingaz;

use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInvoice;
use App\Models\RentalFinancialAdjustment;
use App\Models\ingaz\Package;
use App\Models\ingaz\User as ClientUser;
use App\Support\AutomaticJournalEntries;
use App\Support\RentalClosureReconciler;
use App\Support\RentalContractPricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RentalContractFinanceService
{
    public const RENTAL_REVENUE_ACCOUNT_SETTING = 'rental_contract_revenue_account';
    public const RENTAL_EXEMPT_SALARY_REVENUE_ACCOUNT_SETTING = 'rental_contract_exempt_salary_revenue_account';
    public const RENTAL_CLIENTS_PARENT_ACCOUNT_SETTING = 'rental_contract_clients_parent_account';
    public const RENTAL_CASH_ACCOUNT_SETTING = 'rental_contract_cash_account';
    public const RENTAL_BANK_ACCOUNT_SETTING = 'rental_contract_bank_account';
    public const RENTAL_ELECTRONIC_ACCOUNT_SETTING = 'rental_contract_electronic_account';

    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected BondService $bondService
    ) {
    }

    public function createForContract(RentalContract $contract, ?Package $package = null, ?int $receiptAccountId = null): array
    {
        $contract->loadMissing(['user', 'maid.nationality', 'info', 'financeInvoice']);

        if ($contract->financeInvoice) {
            $invoice = $this->linkExistingInvoiceConstraint($contract->financeInvoice);

            return [
                'invoice' => $invoice,
                'invoice_constraint' => $invoice->invoiceConstraint,
                'receipt_bond' => $invoice->receiptBond,
                'receipt_constraint' => $invoice->receiptConstraint,
            ];
        }

        $info = $contract->info;

        if (!$info) {
            throw new \RuntimeException('لا توجد بيانات مالية مرتبطة بعقد الإيجار.');
        }

        $package = $package
            ?: ($info->package_id ? Package::query()->find($info->package_id) : null);

        $amounts = $this->buildInvoiceAmounts($contract, $package);
        $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
        $clientAccountId = ($automaticEntriesEnabled || $amounts['paid_amount'] > 0)
            ? $this->ensureClientAccountId($contract)
            : null;

        $invoice = RentalContractInvoice::query()->create([
            'rental_contract_id' => $contract->id,
            'package_id' => $info->package_id,
            'subtotal' => $amounts['subtotal'],
            'discount' => $amounts['discount'],
            'vat_amount' => $amounts['vat_amount'],
            'total_due' => $amounts['total_due'],
            'paid_amount' => $amounts['paid_amount'],
            'remaining_amount' => $amounts['remaining_amount'],
            'payment_type' => $info->payment_type,
            'payment_status' => $info->payment_status,
            'is_paid' => $amounts['remaining_amount'] <= 0,
            'paid_at' => $amounts['paid_amount'] > 0
                ? ($info->payment_confirmed_at ?: now())
                : null,
            'service_description' => $this->buildServiceDescription($contract, $package),
        ]);

        $invoice->setRelation('rentalContract', $contract);

        $invoiceConstraint = null;
        if ($automaticEntriesEnabled && $amounts['total_due'] > 0) {
            $invoiceConstraint = $this->storeInvoiceConstraint($invoice, $contract, $clientAccountId, $amounts);
        }

        $receiptBond = null;
        $receiptConstraint = null;
        if ($amounts['paid_amount'] > 0) {
            $receipt = $this->storeReceiptBond(
                $invoice,
                $contract,
                $clientAccountId,
                $amounts['paid_amount'],
                null,
                null,
                $receiptAccountId
            );
            $receiptBond = $receipt['bond'];
            $receiptConstraint = $receipt['constraint'];
        }

        $invoice->update([
            'invoice_constraint_id' => $invoiceConstraint?->id,
            'receipt_bond_id' => $receiptBond?->id,
            'receipt_constraint_id' => $receiptConstraint?->id,
            'receipt_account_id' => $receiptBond?->debit_account_id,
        ]);

        return [
            'invoice' => $invoice->fresh(['invoiceConstraint', 'receiptBond', 'receiptConstraint', 'receiptAccount']),
            'invoice_constraint' => $invoiceConstraint,
            'receipt_bond' => $receiptBond,
            'receipt_constraint' => $receiptConstraint,
        ];
    }

    public function syncPaymentForContract(RentalContract $contract, ?Package $package = null, ?int $receiptAccountId = null): array
    {
        $contract->loadMissing(['user', 'maid.nationality', 'info', 'financeInvoice']);

        if (!$contract->financeInvoice) {
            return $this->createForContract($contract, $package);
        }

        $invoice = $contract->financeInvoice;
        $invoice = $this->linkExistingInvoiceConstraint($invoice);
        $info = $contract->info;

        if (!$info) {
            throw new \RuntimeException('لا توجد بيانات مالية مرتبطة بعقد الإيجار.');
        }

        $package = $package
            ?: ($info->package_id ? Package::query()->find($info->package_id) : null);

        $amounts = $this->buildInvoiceAmounts($contract, $package);

        if ((float) $info->contract_value <= 0 && (float) $amounts['total_due'] > 0) {
            $durationMonths = max(1, (int) ($info->contract_duration_months ?? 1));
            $info->update([
                'contract_value' => $amounts['total_due'],
                'monthly_cost' => (float) $info->monthly_cost > 0
                    ? $info->monthly_cost
                    : $this->roundMoney((float) $amounts['total_due'] / $durationMonths),
            ]);
        }

        $postedInvoice = (bool) $invoice->invoice_constraint_id;
        $invoiceTotal = $postedInvoice
            ? $this->roundMoney((float) $invoice->total_due)
            : $this->roundMoney((float) $amounts['total_due']);
        $invoicePaidSource = $postedInvoice
            ? (float) ($info->paid_amount ?? 0)
            : (float) $amounts['paid_amount'];
        $invoicePaid = $this->roundMoney(min($invoiceTotal, $invoicePaidSource));
        $invoiceRemaining = $this->roundMoney(max(0, $invoiceTotal - $invoicePaid));
        $invoiceUpdates = [
            'package_id' => $info->package_id,
            'paid_amount' => $invoicePaid,
            'remaining_amount' => $invoiceRemaining,
            'payment_type' => $info->payment_type,
            'payment_status' => $info->payment_status,
            'is_paid' => $invoiceRemaining <= 0,
            'paid_at' => $invoicePaid > 0
                ? ($info->payment_confirmed_at ?: now())
                : null,
            'service_description' => $this->buildServiceDescription($contract, $package),
        ];

        if (!$postedInvoice) {
            $invoiceUpdates = array_merge($invoiceUpdates, [
                'subtotal' => $amounts['subtotal'],
                'discount' => $amounts['discount'],
                'vat_amount' => $amounts['vat_amount'],
                'total_due' => $amounts['total_due'],
            ]);
        }

        $invoice->update($invoiceUpdates);

        $invoiceConstraint = $invoice->invoiceConstraint;
        if (
            AutomaticJournalEntries::enabled()
            && (float) $amounts['total_due'] > 0
            && !$invoice->invoice_constraint_id
        ) {
            $clientAccountId = $this->ensureClientAccountId($contract);
            $invoiceConstraint = $this->storeInvoiceConstraint(
                $invoice->fresh(),
                $contract,
                $clientAccountId,
                $amounts
            );
            $invoice->update(['invoice_constraint_id' => $invoiceConstraint?->id]);
        }

        $receiptBond = $invoice->receiptBond;
        $receiptConstraint = $invoice->receiptConstraint;
        $receiptCreated = false;

        if ($amounts['paid_amount'] > 0 && !$invoice->receipt_bond_id) {
            $clientAccountId = $this->ensureClientAccountId($contract);
            $receipt = $this->storeReceiptBond(
                $invoice->fresh(),
                $contract,
                $clientAccountId,
                $amounts['paid_amount'],
                null,
                null,
                $receiptAccountId
            );
            $receiptBond = $receipt['bond'];
            $receiptConstraint = $receipt['constraint'];
            $receiptCreated = true;

            $invoice->update([
                'receipt_bond_id' => $receiptBond?->id,
                'receipt_constraint_id' => $receiptConstraint?->id,
                'receipt_account_id' => $receiptBond?->debit_account_id,
            ]);
        }

        return [
            'invoice' => $invoice->fresh(['invoiceConstraint', 'receiptBond', 'receiptConstraint', 'receiptAccount']),
            'invoice_constraint' => $invoiceConstraint,
            'receipt_bond' => $receiptBond,
            'receipt_constraint' => $receiptConstraint,
            'receipt_created' => $receiptCreated,
        ];
    }

    public function recordPaymentForContract(
        RentalContract $contract,
        float $paymentAmount,
        string $paymentType,
        Carbon|string|null $paymentDate = null,
        ?int $receiptAccountId = null
    ): array {
        $contract->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);

        if (!$contract->info) {
            throw new \RuntimeException('لا توجد بيانات مالية مرتبطة بعقد الإيجار.');
        }

        if (!$contract->financeInvoice) {
            $this->createForContract($contract, $contract->info?->package);
            $contract->refresh()->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        }

        $paymentAmount = $this->roundMoney($paymentAmount);
        if ($paymentAmount <= 0) {
            throw new \InvalidArgumentException('قيمة الدفعة يجب أن تكون أكبر من صفر.');
        }

        $invoice = $this->linkExistingInvoiceConstraint($contract->financeInvoice);
        $clientAccountId = $this->ensureClientAccountId($contract);
        $receipt = $this->storeReceiptBond(
            $invoice,
            $contract,
            $clientAccountId,
            $paymentAmount,
            $paymentType,
            $paymentDate,
            $receiptAccountId
        );

        $invoiceTotal = $this->roundMoney((float) $invoice->total_due);
        $allocatedInvoicePaid = $this->roundMoney(min(
            $invoiceTotal,
            max(0, (float) $invoice->paid_amount) + $paymentAmount
        ));
        $invoiceRemaining = $this->roundMoney(max(0, $invoiceTotal - $allocatedInvoicePaid));
        $invoiceStatus = $allocatedInvoicePaid <= 0
            ? 'pending'
            : ($invoiceRemaining <= 0 ? 'paid' : 'partial');

        $invoiceUpdates = [
            'paid_amount' => $allocatedInvoicePaid,
            'remaining_amount' => $invoiceRemaining,
            'payment_type' => $paymentType,
            'payment_status' => $invoiceStatus,
            'is_paid' => $invoiceRemaining <= 0,
            'paid_at' => $allocatedInvoicePaid > 0 ? now() : null,
            'receipt_account_id' => $receipt['bond']?->debit_account_id,
        ];

        if (!$invoice->receipt_bond_id) {
            $invoiceUpdates['receipt_bond_id'] = $receipt['bond']?->id;
            $invoiceUpdates['receipt_constraint_id'] = $receipt['constraint']?->id;
        }

        $invoice->update($invoiceUpdates);
        $contract->info->update([
            'paid_amount' => $allocatedInvoicePaid,
            'remaining_amount' => $invoiceRemaining,
            'payment_type' => $paymentType,
            'payment_status' => $invoiceStatus,
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => Auth::guard('admin')->id() ?: Auth::id(),
        ]);

        return [
            'invoice' => $invoice->fresh(['invoiceConstraint', 'receiptBond', 'receiptConstraint', 'receiptAccount']),
            'receipt_bond' => $receipt['bond'],
            'receipt_constraint' => $receipt['constraint'],
            'payment_amount' => $paymentAmount,
        ];
    }

    public function recordExtensionPaymentForContract(
        RentalContract $contract,
        RentalContractExtension $extension,
        float $paymentAmount,
        string $paymentType,
        int $receiptAccountId,
        Carbon|string|null $paymentDate = null
    ): array {
        $contract->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);

        if (!$contract->financeInvoice) {
            $this->createForContract($contract, $contract->info?->package);
            $contract->refresh()->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        }

        $paymentAmount = $this->roundMoney($paymentAmount);
        if ($paymentAmount <= 0) {
            throw new \InvalidArgumentException('قيمة سند قبض التمديد يجب أن تكون أكبر من صفر.');
        }

        $clientAccountId = $this->ensureClientAccountId($contract);
        $invoice = $this->linkExistingInvoiceConstraint($contract->financeInvoice);
        $extensionConstraint = $this->recordExtensionForContract(
            $contract,
            $extension,
            $paymentDate
        );
        $statement = 'سند قبض تمديد عقد تأجير رقم '
            . ($contract->contract_number ?: $contract->id)
            . ' / التمديد #'
            . $extension->id
            . ' / العميل '
            . ($contract->user?->name ?: 'غير محدد');

        $receipt = $this->storeReceiptBond(
            $invoice,
            $contract,
            $clientAccountId,
            $paymentAmount,
            $paymentType,
            $paymentDate,
            $receiptAccountId,
            $statement
        );

        return [
            'extension_constraint' => $extensionConstraint,
            'receipt_bond' => $receipt['bond'],
            'receipt_constraint' => $receipt['constraint'],
            'payment_amount' => $paymentAmount,
        ];
    }

    public function recordAncillaryReceiptForContract(
        RentalContract $contract,
        float $paymentAmount,
        string $paymentType,
        int $receiptAccountId,
        Carbon|string|null $paymentDate = null,
        ?string $statement = null
    ): array {
        $contract->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        if (!$contract->financeInvoice) {
            $this->createForContract($contract, $contract->info?->package);
            $contract->refresh()->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        }

        if ($paymentAmount <= 0) {
            throw new \InvalidArgumentException('قيمة سند القبض يجب أن تكون أكبر من صفر.');
        }

        $clientAccountId = $this->ensureClientAccountId($contract);

        return $this->storeReceiptBond(
            $contract->financeInvoice,
            $contract,
            $clientAccountId,
            $this->roundMoney($paymentAmount),
            $paymentType,
            $paymentDate,
            $receiptAccountId,
            $statement
        );
    }

    public function recordExtensionForContract(
        RentalContract $contract,
        RentalContractExtension $extension,
        Carbon|string|null $date = null
    ): ?Constraint {
        if (
            !AutomaticJournalEntries::enabled()
            || (float) $extension->extension_value <= 0
            || $extension->invoice_constraint_id
        ) {
            return $extension->invoiceConstraint;
        }

        $contract->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        $clientAccountId = $this->ensureClientAccountId($contract);
        $constraint = $this->storeExtensionConstraint(
            $contract,
            $extension,
            $clientAccountId,
            $date
        );
        $extension->update(['invoice_constraint_id' => $constraint->id]);

        return $constraint;
    }

    private function storeExtensionConstraint(
        RentalContract $contract,
        RentalContractExtension $extension,
        int $clientAccountId,
        Carbon|string|null $date = null
    ): Constraint {
        $date = $date instanceof Carbon
            ? $date->toDateString()
            : ($date ? Carbon::parse($date)->toDateString() : now()->toDateString());
        $activeYear = $this->yearService->checkDateRange($date);

        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ تمديد عقد الإيجار المحدد.');
        }

        $amounts = $this->extensionAmounts($contract, (int) $extension->extension_days);
        $extensionValue = $this->roundMoney((float) $amounts['total_due']);
        $vatAmount = $this->roundMoney((float) $amounts['vat_amount']);
        $taxableSubtotal = $this->roundMoney((float) $amounts['taxable_subtotal']);
        $salaryExemptAmount = $this->roundMoney((float) $amounts['salary_exempt_amount']);
        $statement = 'قيد تمديد عقد تأجير رقم '
            . ($contract->contract_number ?: $contract->id)
            . ' / التمديد #'
            . $extension->id
            . ' / العميل '
            . ($contract->user?->name ?: 'غير محدد');
        $lines = [];
        $costCenterId = $this->ensureRentalContractCostCenter($contract)->id;

        if ($taxableSubtotal > 0) {
            $this->appendConstraintPair(
                $lines,
                $this->resolveDeferredRevenueAccountId(),
                $clientAccountId,
                $taxableSubtotal,
                $statement . ' / خدمات التأجير الخاضعة للضريبة',
                $costCenterId
            );
        }

        if ($salaryExemptAmount > 0) {
            $this->appendConstraintPair(
                $lines,
                $this->resolveExemptSalaryRevenueAccountId(),
                $clientAccountId,
                $salaryExemptAmount,
                $this->buildWorkerSalaryStatement($statement, $contract, $salaryExemptAmount),
                $costCenterId
            );
        }

        if ($vatAmount > 0) {
            $taxAccountId = $this->resolveRevenueTaxAccountId();
            if (!$taxAccountId) {
                throw new \RuntimeException('لا يوجد حساب ضريبة إيرادات محدد لإثبات قيمة تمديد عقد الإيجار.');
            }

            $this->appendConstraintPair(
                $lines,
                $taxAccountId,
                $clientAccountId,
                $vatAmount,
                $statement
            );
        }

        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $extensionValue,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    public static function confirmSettingTitles(): array
    {
        return [
            self::RENTAL_REVENUE_ACCOUNT_SETTING,
            self::RENTAL_EXEMPT_SALARY_REVENUE_ACCOUNT_SETTING,
            self::RENTAL_CLIENTS_PARENT_ACCOUNT_SETTING,
            self::RENTAL_CASH_ACCOUNT_SETTING,
            self::RENTAL_BANK_ACCOUNT_SETTING,
            self::RENTAL_ELECTRONIC_ACCOUNT_SETTING,
        ];
    }

    public function rentalRevenueAccountIds(): array
    {
        return [
            'service' => $this->resolveDeferredRevenueAccountId(),
            'salary_exempt' => $this->resolveExemptSalaryRevenueAccountId(),
        ];
    }

    public function recordClosureAdjustment(
        RentalContract $contract,
        RentalFinancialAdjustment $adjustment,
        Carbon|string|null $closureDate = null
    ): ?Constraint {
        if (
            !AutomaticJournalEntries::enabled()
            || (float) $adjustment->amount <= 0
            || $adjustment->constraint_id
        ) {
            return $adjustment->constraint;
        }

        $contract->loadMissing(['user', 'maid.nationality', 'info.package', 'financeInvoice']);
        $invoice = $contract->financeInvoice;

        if (!$invoice) {
            throw new \RuntimeException('لا توجد فاتورة مرتبطة بعقد الإيجار لإنشاء قيد تسوية الإنهاء.');
        }

        $date = $closureDate instanceof Carbon
            ? $closureDate->toDateString()
            : ($closureDate ? Carbon::parse($closureDate)->toDateString() : now()->toDateString());
        $activeYear = $this->yearService->checkDateRange($date);

        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ إنهاء عقد الإيجار المحدد.');
        }

        $clientAccountId = $this->ensureClientAccountId($contract);
        $costCenterId = $this->ensureRentalContractCostCenter($contract)->id;
        $amount = $this->roundMoney((float) $adjustment->amount);
        $breakdown = $this->closureAdjustmentAmounts($invoice, $contract, $amount);
        $isRefund = $adjustment->type === 'expense';
        $statement = ($isRefund ? 'إشعار دائن' : 'قيد مستحق للشركة')
            . ' لإنهاء عقد تأجير رقم '
            . ($contract->contract_number ?: $contract->id)
            . ' / الفاتورة #'
            . $invoice->id
            . ' / العميل '
            . ($contract->user?->name ?: 'غير محدد');
        $revenueAccountId = $this->resolveDeferredRevenueAccountId();
        $salaryRevenueAccountId = $this->resolveExemptSalaryRevenueAccountId();
        $lines = [];

        foreach ([
            'taxable_subtotal' => ['خدمات التأجير', $revenueAccountId],
            'salary_exempt_amount' => ['راتب العاملة المعفى من الضريبة', $salaryRevenueAccountId],
        ] as $key => [$label, $lineAccountId]) {
            $lineAmount = (float) ($breakdown[$key] ?? 0);
            if ($lineAmount <= 0) {
                continue;
            }

            if ($isRefund) {
                $this->appendReversalPair(
                    $lines,
                    $lineAccountId,
                    $clientAccountId,
                    $lineAmount,
                    $statement . ' / ' . $label,
                    $costCenterId
                );
            } else {
                $this->appendConstraintPair(
                    $lines,
                    $lineAccountId,
                    $clientAccountId,
                    $lineAmount,
                    $statement . ' / ' . $label,
                    $costCenterId
                );
            }
        }

        if ((float) $breakdown['vat_amount'] > 0) {
            $taxAccountId = $this->resolveRevenueTaxAccountId();
            if (!$taxAccountId) {
                throw new \RuntimeException(
                    'لا يوجد حساب ضريبة إيرادات محدد. الرجاء تعريفه من إعدادات النظام المحاسبي أولاً.'
                );
            }

            if ($isRefund) {
                $this->appendReversalPair(
                    $lines,
                    $taxAccountId,
                    $clientAccountId,
                    (float) $breakdown['vat_amount'],
                    $statement . ' / عكس ضريبة القيمة المضافة'
                );
            } else {
                $this->appendConstraintPair(
                    $lines,
                    $taxAccountId,
                    $clientAccountId,
                    (float) $breakdown['vat_amount'],
                    $statement . ' / ضريبة القيمة المضافة'
                );
            }
        }

        if (empty($lines)) {
            return null;
        }

        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $amount,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        $adjustment->update([
            'constraint_id' => $constraint->id,
            'account_id' => $revenueAccountId,
            'cost_center_id' => $costCenterId,
        ]);

        return $constraint;
    }

    private function buildInvoiceAmounts(RentalContract $contract, ?Package $package): array
    {
        $storedGrossTotal = $this->roundMoney((float) ($contract->info?->contract_value ?? 0));
        $storedPaidAmount = $this->roundMoney((float) ($contract->info?->paid_amount ?? 0));
        $storedRemainingAmount = $this->roundMoney((float) ($contract->info?->remaining_amount ?? 0));

        if ($storedGrossTotal <= 0 && ($storedPaidAmount > 0 || $storedRemainingAmount > 0)) {
            $storedGrossTotal = $this->roundMoney($storedPaidAmount + $storedRemainingAmount);
        }

        $monthlySalary = $this->resolveMonthlySalary($contract);
        $durationDays = RentalContractPricing::durationDays(
            (int) ($contract->info?->contract_duration_days ?? 0),
            (int) ($contract->info?->contract_duration_months ?? 0)
        );
        $amounts = $this->resolveContractPricing(
            $contract,
            $package,
            $storedGrossTotal,
            $monthlySalary,
            $durationDays,
            (int) ($contract->info?->contract_duration_months ?? 0)
        );
        $grossTotal = (float) $amounts['total_due'];
        $paidAmount = min(
            $grossTotal,
            $storedPaidAmount
        );
        $remainingAmount = $this->roundMoney(max(0, $grossTotal - $paidAmount));
        return array_merge($amounts, [
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ]);
    }

    public function expectedInvoiceAmounts(RentalContract $contract, ?Package $package = null): array
    {
        $contract->loadMissing(['info.package', 'maid']);

        return $this->buildInvoiceAmounts($contract, $package ?: $contract->info?->package);
    }

    private function storeInvoiceConstraint(
        RentalContractInvoice $invoice,
        RentalContract $contract,
        int $clientAccountId,
        array $amounts
    ): ?Constraint {
        $date = optional($contract->contract_date)->format('Y-m-d') ?: now()->toDateString();
        $activeYear = $this->yearService->checkDateRange($date);

        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ عقد الإيجار المحدد.');
        }

        $statement = $this->buildInvoiceStatement($invoice, $contract);
        $costCenterId = $this->ensureRentalContractCostCenter($contract)->id;
        $lines = [];
        $deferredRevenueAccountId = $this->resolveDeferredRevenueAccountId();
        $taxableSubtotal = $this->roundMoney((float) ($amounts['taxable_subtotal'] ?? $amounts['net_subtotal'] ?? 0));
        $salaryExemptAmount = $this->roundMoney((float) ($amounts['salary_exempt_amount'] ?? 0));
        $salaryRevenueAccountId = $salaryExemptAmount > 0
            ? $this->resolveExemptSalaryRevenueAccountId()
            : $deferredRevenueAccountId;

        foreach ($this->invoiceRevenueAllocations(
            $taxableSubtotal,
            $salaryExemptAmount,
            $deferredRevenueAccountId,
            $salaryRevenueAccountId
        ) as $allocation) {
            $this->appendConstraintPair(
                $lines,
                $allocation['account_id'],
                $clientAccountId,
                $allocation['amount'],
                $allocation['kind'] === 'salary'
                    ? $this->buildWorkerSalaryStatement($statement, $contract, $allocation['amount'])
                    : ($salaryExemptAmount > 0 ? $statement . ' / خدمات التأجير الخاضعة للضريبة' : $statement),
                $costCenterId
            );
        }

        if ((float) $invoice->vat_amount > 0) {
            $taxAccountId = $this->resolveRevenueTaxAccountId();

            if (!$taxAccountId) {
                throw new \RuntimeException(
                    'لا يوجد حساب ضريبة إيرادات محدد. الرجاء تعريفه من إعدادات النظام المحاسبي أولاً.'
                );
            }

            $this->appendConstraintPair(
                $lines,
                $taxAccountId,
                $clientAccountId,
                (float) $invoice->vat_amount,
                $statement
            );
        }

        if (empty($lines)) {
            return null;
        }

        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $this->roundMoney((float) $invoice->total_due),
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function storeReceiptBond(
        RentalContractInvoice $invoice,
        RentalContract $contract,
        int $clientAccountId,
        float $paidAmount,
        ?string $paymentType = null,
        Carbon|string|null $paymentDate = null,
        ?int $receiptAccountId = null,
        ?string $statement = null
    ): array {
        $date = $paymentDate instanceof Carbon
            ? $paymentDate->toDateString()
            : ($paymentDate
                ? Carbon::parse($paymentDate)->toDateString()
                : (optional($contract->contract_date)->format('Y-m-d') ?: now()->toDateString()));

        if (!$this->yearService->checkDateRange($date)) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ سند القبض المحدد.');
        }

        $paymentType = $paymentType ?: (string) $invoice->payment_type;
        $receiptAccount = $receiptAccountId
            ? $this->resolveSelectedReceiptAccount($receiptAccountId)
            : $this->resolveReceiptAccount($paymentType);
        $bondPaymentType = $this->mapBondPaymentType($paymentType);

        $bond = $this->bondService->storeQabdBond([
            'payment_type' => $bondPaymentType->value,
            'bond_book_num' => ' ',
            'debit_account_id' => $receiptAccount->id,
            'credit_account_id' => $clientAccountId,
            'value' => $this->roundMoney($paidAmount),
            'date' => $date,
            'statement' => $statement
                ? $statement . ' / إلى حساب ' . $receiptAccount->name
                : $this->buildReceiptStatement($invoice, $contract, $receiptAccount->name),
            'code' => $this->bondService->lastCode(BondTypes::Qabd->value),
            'is_tax' => 0,
            'general_type' => 0,
            'rental_contract_id' => $contract->id,
        ])->fresh();

        if ((int) $bond->is_confirm !== 1 || !$bond->constraint_id) {
            $this->bondService->confirmBond($bond->id);
        }

        $bond = $bond->fresh();

        return [
            'bond' => $bond,
            'constraint' => $bond?->constraint,
        ];
    }

    private function linkExistingInvoiceConstraint(RentalContractInvoice $invoice): RentalContractInvoice
    {
        if ($invoice->invoice_constraint_id) {
            return $invoice;
        }

        $invoiceNumbers = collect([
            $invoice->id,
            $invoice->legacy_invoice_id ?? null,
        ])->filter()->map(fn ($value) => (int) $value)->unique()->values();

        foreach ($invoiceNumbers as $invoiceNumber) {
            $matches = Constraint::query()
                ->where('statement', 'فاتورة: ' . $invoiceNumber)
                ->get();

            if ($matches->isEmpty()) {
                $matches = Constraint::query()
                    ->where(
                        'statement',
                        'like',
                        'قيد فاتورة عقد تأجير رقم % / الفاتورة #' . $invoiceNumber . ' / العميل %'
                    )
                    ->get();
            }

            if ($matches->count() === 1) {
                $invoice->update(['invoice_constraint_id' => $matches->first()->id]);

                return $invoice->fresh([
                    'invoiceConstraint',
                    'receiptBond',
                    'receiptConstraint',
                    'receiptAccount',
                ]);
            }
        }

        return $invoice;
    }

    private function appendConstraintPair(
        array &$lines,
        int $creditAccountId,
        int $debitAccountId,
        float $amount,
        string $statement,
        ?int $costCenterId = null
    ): void {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $creditAccountId,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => $amount,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];

        $lines[] = [
            'account_id' => $debitAccountId,
            'account_type' => 'debtor',
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => $statement,
            'cost_center_id' => null,
        ];
    }

    private function appendReversalPair(
        array &$lines,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $statement,
        ?int $costCenterId = null
    ): void {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $debitAccountId,
            'account_type' => 'debtor',
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];

        $lines[] = [
            'account_id' => $creditAccountId,
            'account_type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => $amount,
            'statement' => $statement,
            'cost_center_id' => null,
        ];
    }

    public function closureAdjustmentAmounts(
        RentalContractInvoice $invoice,
        RentalContract $contract,
        float $adjustmentAmount
    ): array {
        $adjustmentAmount = $this->roundMoney(max(0, $adjustmentAmount));

        if ($adjustmentAmount <= 0) {
            return [
                'taxable_subtotal' => 0.0,
                'salary_exempt_amount' => 0.0,
                'vat_amount' => 0.0,
                'total' => 0.0,
            ];
        }

        $invoiceTotal = 0.0;
        $postedVat = 0.0;
        $clientAccountId = $contract->relationLoaded('user')
            ? (int) ($contract->getRelation('user')?->account_id ?? 0)
            : 0;
        $taxAccountId = null;

        if ($invoice->invoice_constraint_id && $clientAccountId > 0) {
            $taxAccountId = $this->resolveRevenueTaxAccountId();
            $invoiceTotal = $this->roundMoney((float) DB::table('fn_constraint_details')
                ->where('constraint_id', $invoice->invoice_constraint_id)
                ->where('account_id', $clientAccountId)
                ->whereNull('deleted_at')
                ->sum('debtor_value'));

            if ($taxAccountId) {
                $postedVat = $this->roundMoney((float) DB::table('fn_constraint_details')
                    ->where('constraint_id', $invoice->invoice_constraint_id)
                    ->where('account_id', $taxAccountId)
                    ->whereNull('deleted_at')
                    ->sum('creditor_value'));
            }
        }

        if ($invoiceTotal > 0) {
            $composition = RentalClosureReconciler::compositionFromPostedVat($invoiceTotal, $postedVat);
            $split = RentalClosureReconciler::splitByComposition(
                $adjustmentAmount,
                (float) $composition['exempt_fraction']
            );
        } else {
            $invoiceAmounts = $this->buildInvoiceAmounts($contract, $contract->info?->package);
            $fallbackTotal = max(0.01, (float) $invoiceAmounts['total_due']);
            $split = RentalClosureReconciler::splitByComposition(
                $adjustmentAmount,
                (float) $invoiceAmounts['salary_exempt_amount'] / $fallbackTotal
            );
        }

        return [
            'taxable_subtotal' => $split['taxable'],
            'salary_exempt_amount' => $split['salary_exempt'],
            'vat_amount' => $split['vat'],
            'total' => $adjustmentAmount,
        ];
    }

    private function buildInvoiceStatement(RentalContractInvoice $invoice, RentalContract $contract): string
    {
        return 'قيد فاتورة عقد تأجير رقم '
            . ($contract->contract_number ?: $contract->id)
            . ' / الفاتورة #'
            . $invoice->id
            . ' / العميل '
            . ($contract->user?->name ?: 'غير محدد');
    }

    private function buildWorkerSalaryStatement(string $baseStatement, RentalContract $contract, float $salaryAmount): string
    {
        $maidName = $contract->maid?->name ?: 'غير محدد';

        return $baseStatement
            . ' / مرتب العامل (معفى ضريبياً)'
            . ' / العاملة: ' . $maidName
            . ' / المبلغ: ' . number_format($salaryAmount, 2, '.', '') . ' ريال';
    }

    public function extensionAmounts(RentalContract $contract, int $extensionDays): array
    {
        $contract->loadMissing(['info.package', 'maid']);
        $baseDays = RentalContractPricing::durationDays(
            (int) ($contract->info?->contract_duration_days ?? 0),
            (int) ($contract->info?->contract_duration_months ?? 0)
        );
        $baseAmounts = $this->resolveContractPricing(
            $contract,
            $contract->info?->package,
            (float) ($contract->info?->contract_value ?? 0),
            $this->resolveMonthlySalary($contract),
            $baseDays,
            (int) ($contract->info?->contract_duration_months ?? 0)
        );
        $baseGrossTotal = (float) ($contract->info?->contract_value ?? 0);
        if ($baseGrossTotal <= 0) {
            $baseGrossTotal = (float) $baseAmounts['total_due'];
        }

        $extensionGrossTotal = $baseDays > 0
            ? $this->roundMoney(($baseGrossTotal / $baseDays) * max(0, $extensionDays))
            : 0.0;

        return RentalContractPricing::fromGrossPrice(
            $extensionGrossTotal,
            $this->resolveMonthlySalary($contract),
            max(0, $extensionDays)
        );
    }

    private function resolveMonthlySalary(RentalContract $contract): float
    {
        $monthlySalary = (float) ($contract->info?->monthly_salary ?? 0);

        return $monthlySalary > 0
            ? $monthlySalary
            : (float) ($contract->maid?->resolved_salary ?? $contract->maid?->salary ?? 0);
    }

    private function invoiceRevenueAllocations(
        float $taxableSubtotal,
        float $salaryExemptAmount,
        int $serviceRevenueAccountId,
        int $salaryRevenueAccountId
    ): array {
        return collect([
            [
                'kind' => 'service',
                'account_id' => $serviceRevenueAccountId,
                'amount' => $this->roundMoney($taxableSubtotal),
            ],
            [
                'kind' => 'salary',
                'account_id' => $salaryRevenueAccountId,
                'amount' => $this->roundMoney($salaryExemptAmount),
            ],
        ])->filter(fn (array $allocation) => $allocation['amount'] > 0)->values()->all();
    }

    private function resolveContractPricing(
        RentalContract $contract,
        ?Package $package,
        float $storedGrossTotal,
        float $monthlySalary,
        int $durationDays,
        int $durationMonths
    ): array {
        $snapshotNet = (float) ($contract->info?->package_price_after_discount ?? 0);
        $snapshotGross = (float) ($contract->info?->package_total_with_tax ?? 0);
        $basis = (string) ($contract->info?->package_price_basis ?? '');

        if ($basis === '') {
            $basis = $this->inferPackagePriceBasis($package, $snapshotNet, $snapshotGross);
        }

        if ($basis === 'net') {
            $netPrice = $snapshotNet > 0
                ? $snapshotNet
                : (float) ($package?->price_after_discount ?? 0);

            return RentalContractPricing::fromNetPrice(
                $netPrice,
                $monthlySalary,
                $durationDays,
                $durationMonths,
                (float) ($contract->info?->package_original_price ?? $package?->original_price ?? $netPrice)
            );
        }

        $grossPrice = $storedGrossTotal > 0
            ? $storedGrossTotal
            : ($snapshotGross > 0 ? $snapshotGross : (float) ($package?->price_with_tax ?? 0));

        return RentalContractPricing::fromGrossPrice(
            $grossPrice,
            $monthlySalary,
            $durationDays,
            $durationMonths
        );
    }

    private function inferPackagePriceBasis(
        ?Package $package,
        float $snapshotNet,
        float $snapshotGross
    ): string {
        if (!$package) {
            return 'gross';
        }

        $net = $snapshotNet > 0 ? $snapshotNet : (float) $package->price_after_discount;
        $gross = $snapshotGross > 0 ? $snapshotGross : (float) $package->price_with_tax;
        $fullVatGross = $this->roundMoney($net * (1 + RentalContractPricing::VAT_RATE));

        return $net > 0 && abs($gross - $fullVatGross) < 0.05
            ? 'net'
            : 'gross';
    }

    private function buildReceiptStatement(
        RentalContractInvoice $invoice,
        RentalContract $contract,
        string $receiptAccountName
    ): string {
        $statement = 'سند قبض عقد تأجير رقم '
            . ($contract->contract_number ?: $contract->id)
            . ' / الفاتورة #'
            . $invoice->id
            . ' / العميل '
            . ($contract->user?->name ?: 'غير محدد')
            . ' / إلى حساب '
            . $receiptAccountName;

        if ((float) $invoice->remaining_amount > 0) {
            $statement .= ' / المتبقي ' . number_format((float) $invoice->remaining_amount, 2) . ' ريال';
        }

        return $statement;
    }

    private function buildServiceDescription(RentalContract $contract, ?Package $package): string
    {
        $maidName = $contract->maid?->name ?: 'غير محدد';
        $nationality = $contract->maid?->nationality?->title
            ?: $contract->maid?->nationality?->sub_title
            ?: 'غير محدد';
        $durationDays = (int) ($contract->info?->contract_duration_days ?? 0);
        $durationMonths = (int) ($contract->info?->contract_duration_months ?? 0);
        $packageTitle = $package?->title ?: 'باقة إيجار';
        $durationLabel = $package?->duration_label
            ?: ($durationMonths > 0 ? $durationMonths . ' شهر' : 'غير محدد');

        return 'عقد تأجير - رقم العقد: '
            . ($contract->contract_number ?: $contract->id)
            . ' - العميل: ' . ($contract->user?->name ?: 'غير محدد')
            . ' - العاملة: ' . $maidName
            . ' - الجنسية: ' . $nationality
            . ' - الباقة: ' . $packageTitle
            . ' - المدة: ' . $durationLabel
            . ($durationDays > 0 ? ' / ' . $durationDays . ' يوم' : '');
    }

    private function ensureClientAccountId(RentalContract $contract): int
    {
        if (!$contract->user_id) {
            throw new \RuntimeException('عقد الإيجار غير مرتبط بعميل، لا يمكن إنشاء الحساب المحاسبي.');
        }

        $user = ClientUser::query()
            ->lockForUpdate()
            ->find($contract->user_id);

        if (!$user) {
            throw new \RuntimeException('العميل المرتبط بعقد الإيجار غير موجود.');
        }

        if ($user->account_id) {
            $existingAccount = Account::query()
                ->lockForUpdate()
                ->find($user->account_id);

            if ($existingAccount) {
                return (int) $existingAccount->id;
            }

            $user->update(['account_id' => null]);
        }

        $configuredParentId = $this->configuredAccountId(
            self::RENTAL_CLIENTS_PARENT_ACCOUNT_SETTING,
            'debtor'
        );
        $parentAccount = $configuredParentId
            ? Account::query()->lockForUpdate()->find($configuredParentId)
            : Account::query()
                ->where('type', AccountTypesEnum::Clients->value)
                ->lockForUpdate()
                ->first();

        if (!$parentAccount) {
            throw new \RuntimeException(
                'لا يوجد حساب رئيسي للعملاء في جدول الحسابات. يرجى إنشاء حساب من نوع Clients أولاً.'
            );
        }

        $lastCode = Account::query()
            ->where('parent_id', $parentAccount->id)
            ->max('code');

        if (!$lastCode) {
            $lastCode = (int) ($parentAccount->code . '00000');
        }

        $account = Account::query()->create([
            'name' => $user->name ?: ('عميل #' . $user->id),
            'operation_type' => $parentAccount->operation_type,
            'status' => $parentAccount->status,
            'statement_type' => $parentAccount->statement_type,
            'level' => $parentAccount->level + 1,
            'type' => AccountTypesEnum::Client->value,
            'parent_id' => $parentAccount->id,
            'code' => ((int) $lastCode) + 1,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);

        $user->update([
            'account_id' => $account->id,
        ]);

        if ($contract->relationLoaded('user') && $contract->user) {
            $contract->user->account_id = $account->id;
        }

        return (int) $account->id;
    }

    private function ensureRentalContractCostCenter(RentalContract $contract): CostCenter
    {
        $rootCostCenter = $this->ensureRentalContractsRootCostCenter();

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $rootCostCenter->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contract->id)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عقد تأجير #' . ($contract->contract_number ?: $contract->id),
            'parent_id' => $rootCostCenter->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $contract->id,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureRentalContractsRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عقود التأجير')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عقود التأجير',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    private function resolveDeferredRevenueAccountId(): int
    {
        $configuredAccountId = $this->configuredAccountId(
            self::RENTAL_REVENUE_ACCOUNT_SETTING,
            'creditor',
            true
        );

        if ($configuredAccountId) {
            return $configuredAccountId;
        }

        $preferredNames = [
            'إيراد مقدم خدمات تأجير',
            'إيراد مقدم عمالة تأجير',
        ];

        foreach ($preferredNames as $name) {
            $accountId = Account::query()
                ->where('name', $name)
                ->where('status', 'creditor')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        $parentId = Account::query()
            ->where('name', 'دفعات مقدمة تأجير')
            ->value('id');

        if ($parentId) {
            $accountId = Account::query()
                ->where('parent_id', $parentId)
                ->where('status', 'creditor')
                ->orderBy('code')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        $fallbackNames = [
            'إيراد خدمات تأجير العمالة',
            'إيراد خدمات التأجير',
            'إيراد مبيعات التأجير',
        ];

        foreach ($fallbackNames as $name) {
            $accountId = Account::query()
                ->where('name', $name)
                ->where('status', 'creditor')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        $accountId = Account::query()
            ->where('status', 'creditor')
            ->where(function ($query) {
                $query->where('name', 'like', '%تأجير%')
                    ->orWhere('name', 'like', '%تاجير%')
                    ->orWhere('name', 'like', '%إيجار%')
                    ->orWhere('name', 'like', '%ايجار%');
            })
            ->orderBy('code')
            ->value('id');

        if ($accountId) {
            return (int) $accountId;
        }

        throw new \RuntimeException(
            'لا يوجد حساب مناسب لإثبات إيراد/مقدم خدمات التأجير. الرجاء تعريف حساب "إيراد مقدم خدمات تأجير" أو ما يعادله في الدليل المحاسبي.'
        );
    }

    private function resolveExemptSalaryRevenueAccountId(): int
    {
        $configuredAccountId = $this->configuredAccountId(
            self::RENTAL_EXEMPT_SALARY_REVENUE_ACCOUNT_SETTING,
            'creditor',
            true
        );

        if ($configuredAccountId) {
            return $configuredAccountId;
        }

        $accountId = Account::query()
            ->where('status', 'creditor')
            ->where(function ($query) {
                $query->where('code', '4216')
                    ->orWhere('name', 'like', '%مرتب%عامل%معف%')
                    ->orWhere('name', 'like', '%راتب%عامل%معف%');
            })
            ->orderByRaw("CASE WHEN code = '4216' THEN 0 ELSE 1 END")
            ->value('id');

        if ($accountId) {
            return (int) $accountId;
        }

        throw new \RuntimeException(
            'لا يوجد حساب إيراد مستقل لراتب العاملة المعفى. عرّف الإعداد '
            . self::RENTAL_EXEMPT_SALARY_REVENUE_ACCOUNT_SETTING
            . ' قبل إنشاء أو تمديد أو إنهاء عقد تأجير.'
        );
    }

    private function resolveRevenueTaxAccountId(): ?int
    {
        $directAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::RevenueTaxAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($directAccountId) {
            $account = Account::query()
                ->whereKey($directAccountId)
                ->where('status', 'creditor')
                ->first();

            if ($account) {
                return (int) $account->id;
            }
        }

        $taxSettingId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::TaxRateRevenue->value)
            ->latest('updated_at')
            ->value('value');

        if ($taxSettingId) {
            $taxAccountId = TaxSetting::query()
                ->whereKey($taxSettingId)
                ->value('account_id');

            if ($taxAccountId) {
                return (int) $taxAccountId;
            }
        }

        $taxAccountId = Account::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%ضريبة%إيراد%')
                    ->orWhere('name', 'like', '%ضريبة%إيرادات%')
                    ->orWhere('name', 'like', '%ضريبة%قيمة%مضافة%');
            })
            ->where('status', 'creditor')
            ->orderBy('code')
            ->value('id');

        return $taxAccountId ? (int) $taxAccountId : null;
    }

    private function resolveReceiptAccount(string $paymentType): Account
    {
        $settingTitle = match ($paymentType) {
            'cash' => self::RENTAL_CASH_ACCOUNT_SETTING,
            'tabby', 'tamara', 'moyasar' => self::RENTAL_ELECTRONIC_ACCOUNT_SETTING,
            default => self::RENTAL_BANK_ACCOUNT_SETTING,
        };
        $configuredAccountId = $this->configuredAccountId($settingTitle, 'debtor', true);

        if ($configuredAccountId) {
            return Account::query()->findOrFail($configuredAccountId);
        }

        $account = match ($paymentType) {
            'cash' => $this->findReceiptAccountByTypes([AccountTypesEnum::Box, AccountTypesEnum::Bank]),
            'mada' => $this->findReceiptAccountByTypes([AccountTypesEnum::Bank]),
            'tabby', 'tamara' => $this->findReceiptAccountByTypes([AccountTypesEnum::ElectronicWallet, AccountTypesEnum::Bank]),
            'bank_transfer', 'bank_pending', 'other' => $this->findReceiptAccountByTypes([AccountTypesEnum::Bank]),
            'moyasar' => $this->findReceiptAccountByTypes([AccountTypesEnum::ElectronicWallet, AccountTypesEnum::Bank]),
            default => $this->findReceiptAccountByTypes([AccountTypesEnum::Bank, AccountTypesEnum::Box]),
        };

        if ($account) {
            return $account;
        }

        return $this->resolveFallbackReceiptAccount();
    }

    private function resolveSelectedReceiptAccount(int $accountId): Account
    {
        $account = Account::query()->find($accountId);
        $allowedTypes = [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Box->value,
            AccountTypesEnum::ElectronicWallet->value,
        ];

        if (
            !$account
            || !in_array((int) $account->getRawOriginal('type'), $allowedTypes, true)
            || $account->status?->value !== 'debtor'
        ) {
            throw new \RuntimeException('حساب القبض المحدد يجب أن يكون حساب بنك أو صندوق أو محفظة إلكترونية صالحاً.');
        }

        return $account;
    }

    private function findReceiptAccountByTypes(array $types): ?Account
    {
        $typeValues = array_map(static fn (AccountTypesEnum $type) => $type->value, $types);

        return Account::query()
            ->whereIn('type', $typeValues)
            ->where('status', 'debtor')
            ->orderBy('code')
            ->first();
    }

    private function resolveFallbackReceiptAccount(): Account
    {
        $settingTitles = [
            ConfirmSettingTypes::MusanedAccount->value,
            ConfirmSettingTypes::MusanedIntermediateAccount->value,
        ];

        foreach ($settingTitles as $settingTitle) {
            $accountId = ConfirmSetting::query()
                ->where('title', $settingTitle)
                ->latest('updated_at')
                ->value('value');

            if ($accountId) {
                $account = Account::query()->find($accountId);
                if ($account && $account->status?->value === 'debtor') {
                    return $account;
                }
            }
        }

        $account = Account::query()
            ->underMusanedParent()
            ->where('status', 'debtor')
            ->orderBy('code')
            ->first();

        if ($account) {
            return $account;
        }

        throw new \RuntimeException(
            'لا يوجد حساب قبض مناسب لإثبات سند قبض عقد الإيجار. الرجاء تعريف حساب بنك/صندوق أو حساب قبض افتراضي.'
        );
    }

    private function mapBondPaymentType(string $paymentType): BondPaymentTypes
    {
        return match ($paymentType) {
            'cash' => BondPaymentTypes::Cash,
            'mada' => BondPaymentTypes::Network,
            'tabby', 'tamara' => BondPaymentTypes::ElectronicWallet,
            'bank_transfer', 'bank_pending', 'other' => BondPaymentTypes::BankTransfer,
            'moyasar' => BondPaymentTypes::Online,
            default => BondPaymentTypes::BankTransfer,
        };
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }

    private function configuredAccountId(
        string $settingTitle,
        string $expectedStatus,
        bool $mustBeLeaf = false
    ): ?int
    {
        $accountId = ConfirmSetting::query()
            ->where('title', $settingTitle)
            ->latest('updated_at')
            ->value('value');

        if (!$accountId) {
            return null;
        }

        $account = Account::query()->find($accountId);
        $status = $account?->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

        if (
            !$account
            || $statusValue !== $expectedStatus
            || ($mustBeLeaf && $account->children()->exists())
        ) {
            throw new \RuntimeException(
                'الحساب المحدد في إعدادات عقود التأجير غير موجود أو طبيعته المحاسبية غير صحيحة أو ليس حساباً نهائياً.'
            );
        }

        return (int) $account->id;
    }
}
