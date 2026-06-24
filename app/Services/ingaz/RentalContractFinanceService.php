<?php

namespace App\Services\ingaz;

use App\Models\RentalContract;
use App\Models\RentalContractInvoice;
use App\Models\ingaz\Package;
use App\Models\ingaz\User as ClientUser;
use Illuminate\Support\Facades\Auth;
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
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected BondService $bondService
    ) {
    }

    public function createForContract(RentalContract $contract, ?Package $package = null): array
    {
        $contract->loadMissing(['user', 'maid.nationality', 'info', 'financeInvoice']);

        if ($contract->financeInvoice) {
            return [
                'invoice' => $contract->financeInvoice,
                'invoice_constraint' => $contract->financeInvoice->invoiceConstraint,
                'receipt_bond' => $contract->financeInvoice->receiptBond,
                'receipt_constraint' => $contract->financeInvoice->receiptConstraint,
            ];
        }

        $info = $contract->info;

        if (!$info) {
            throw new \RuntimeException('لا توجد بيانات مالية مرتبطة بعقد الإيجار.');
        }

        $package = $package
            ?: ($info->package_id ? Package::query()->find($info->package_id) : null);

        $amounts = $this->buildInvoiceAmounts($contract, $package);
        $clientAccountId = $this->ensureClientAccountId($contract);

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
        if ($amounts['total_due'] > 0) {
            $invoiceConstraint = $this->storeInvoiceConstraint($invoice, $contract, $clientAccountId, $amounts);
        }

        $receiptBond = null;
        $receiptConstraint = null;
        if ($amounts['paid_amount'] > 0) {
            $receipt = $this->storeReceiptBond($invoice, $contract, $clientAccountId, $amounts['paid_amount']);
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

    public function syncPaymentForContract(RentalContract $contract, ?Package $package = null): array
    {
        $contract->loadMissing(['user', 'maid.nationality', 'info', 'financeInvoice']);

        if (!$contract->financeInvoice) {
            return $this->createForContract($contract, $package);
        }

        $invoice = $contract->financeInvoice;
        $info = $contract->info;

        if (!$info) {
            throw new \RuntimeException('لا توجد بيانات مالية مرتبطة بعقد الإيجار.');
        }

        $package = $package
            ?: ($info->package_id ? Package::query()->find($info->package_id) : null);

        $amounts = $this->buildInvoiceAmounts($contract, $package);

        $invoice->update([
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

        $receiptBond = $invoice->receiptBond;
        $receiptConstraint = $invoice->receiptConstraint;
        $receiptCreated = false;

        if ($amounts['paid_amount'] > 0 && !$invoice->receipt_bond_id) {
            $clientAccountId = $this->ensureClientAccountId($contract);
            $receipt = $this->storeReceiptBond($invoice->fresh(), $contract, $clientAccountId, $amounts['paid_amount']);
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
            'invoice_constraint' => $invoice->invoiceConstraint,
            'receipt_bond' => $receiptBond,
            'receipt_constraint' => $receiptConstraint,
            'receipt_created' => $receiptCreated,
        ];
    }

    private function buildInvoiceAmounts(RentalContract $contract, ?Package $package): array
    {
        $grossTotal = $this->roundMoney((float) ($contract->info?->contract_value ?? 0));
        $paidAmount = min(
            $grossTotal,
            $this->roundMoney((float) ($contract->info?->paid_amount ?? 0))
        );
        $remainingAmount = $this->roundMoney(max(0, $grossTotal - $paidAmount));
        $salaryExemptAmount = $this->resolveSalaryExemptAmount($contract, $grossTotal);

        if ($salaryExemptAmount > 0) {
            $taxableGross = $this->roundMoney(max(0, $grossTotal - $salaryExemptAmount));
            $taxableSubtotal = $taxableGross > 0
                ? $this->roundMoney($taxableGross / (1 + InvoiceCalculationService::VAT_RATE))
                : 0.0;
            $vatAmount = $this->roundMoney(max(0, $taxableGross - $taxableSubtotal));
            $netSubtotal = $this->roundMoney($salaryExemptAmount + $taxableSubtotal);

            return [
                'subtotal' => $netSubtotal,
                'discount' => 0.0,
                'net_subtotal' => $netSubtotal,
                'taxable_subtotal' => $taxableSubtotal,
                'salary_exempt_amount' => $salaryExemptAmount,
                'vat_amount' => $vatAmount,
                'total_due' => $grossTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
            ];
        }

        if ($contract->info?->package_price_after_discount !== null && $contract->info?->package_total_with_tax !== null) {
            $subtotal = $this->roundMoney((float) ($contract->info->package_original_price ?? $contract->info->package_price_after_discount));
            $netSubtotal = $this->roundMoney((float) $contract->info->package_price_after_discount);
            $discount = $this->roundMoney((float) ($contract->info->package_discount_amount ?? max(0, $subtotal - $netSubtotal)));
            $vatAmount = $this->roundMoney((float) ($contract->info->package_vat_amount ?? max(0, $grossTotal - $netSubtotal)));

            return [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'net_subtotal' => $netSubtotal,
                'taxable_subtotal' => $netSubtotal,
                'salary_exempt_amount' => 0.0,
                'vat_amount' => $vatAmount,
                'total_due' => $grossTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
            ];
        }

        $canUsePackagePricing = $package
            && (int) ($contract->info?->package_id ?? 0) === (int) $package->id
            && abs($grossTotal - $this->roundMoney((float) $package->price_with_tax)) < 0.05;

        if ($canUsePackagePricing) {
            $subtotal = $this->roundMoney((float) $package->original_price);
            $netSubtotal = $this->roundMoney((float) $package->price_after_discount);
            $discount = $this->roundMoney(max(0, $subtotal - $netSubtotal));
            $vatAmount = $this->roundMoney(max(0, $grossTotal - $netSubtotal));
        } else {
            $netSubtotal = $grossTotal > 0
                ? $this->roundMoney($grossTotal / (1 + InvoiceCalculationService::VAT_RATE))
                : 0.0;
            $subtotal = $netSubtotal;
            $discount = 0.0;
            $vatAmount = $this->roundMoney(max(0, $grossTotal - $netSubtotal));
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'net_subtotal' => $netSubtotal,
            'taxable_subtotal' => $netSubtotal,
            'salary_exempt_amount' => 0.0,
            'vat_amount' => $vatAmount,
            'total_due' => $grossTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
        ];
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

        if ($taxableSubtotal > 0) {
            $this->appendConstraintPair(
                $lines,
                $deferredRevenueAccountId,
                $clientAccountId,
                $taxableSubtotal,
                $salaryExemptAmount > 0 ? $statement . ' / خدمات التأجير الخاضعة للضريبة' : $statement,
                $costCenterId
            );
        }

        if ($salaryExemptAmount > 0) {
            $salaryStatement = $this->buildWorkerSalaryStatement($statement, $contract, $salaryExemptAmount);

            $this->appendConstraintPair(
                $lines,
                $deferredRevenueAccountId,
                $clientAccountId,
                $salaryExemptAmount,
                $salaryStatement,
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
        float $paidAmount
    ): array {
        $date = optional($contract->contract_date)->format('Y-m-d') ?: now()->toDateString();

        if (!$this->yearService->checkDateRange($date)) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ سند القبض المحدد.');
        }

        $receiptAccount = $this->resolveReceiptAccount((string) $invoice->payment_type);
        $paymentType = $this->mapBondPaymentType((string) $invoice->payment_type);

        $bond = $this->bondService->storeQabdBond([
            'payment_type' => $paymentType->value,
            'bond_book_num' => ' ',
            'debit_account_id' => $receiptAccount->id,
            'credit_account_id' => $clientAccountId,
            'value' => $this->roundMoney($paidAmount),
            'date' => $date,
            'statement' => $this->buildReceiptStatement($invoice, $contract, $receiptAccount->name),
            'code' => $this->bondService->lastCode(BondTypes::Qabd->value),
            'is_tax' => 0,
            'general_type' => 0,
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

    private function resolveSalaryExemptAmount(RentalContract $contract, float $grossTotal): float
    {
        $monthlySalary = (float) ($contract->info?->monthly_salary ?? 0);

        if ($monthlySalary <= 0) {
            $monthlySalary = (float) ($contract->maid?->resolved_salary ?? $contract->maid?->salary ?? 0);
        }

        if ($monthlySalary <= 0 || $grossTotal <= 0) {
            return 0.0;
        }

        return $this->roundMoney(min($grossTotal, $monthlySalary));
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

        $parentAccount = Account::query()
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

        throw new \RuntimeException(
            'لا يوجد حساب مناسب لإثبات إيراد/مقدم خدمات التأجير. الرجاء تعريف حساب "إيراد مقدم خدمات تأجير" أو ما يعادله في الدليل المحاسبي.'
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
            ->whereIn('parent_id', [122, 156])
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
}
