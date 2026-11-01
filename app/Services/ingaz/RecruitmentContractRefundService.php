<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\MusanedTransferItem;
use App\Models\RecruitmentContractRefund;
use App\Http\Controllers\ingaz\InvoiceController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Support\AutomaticJournalEntries;
use App\Support\RecruitmentRevenueRecognition;
use Nami\Finance\Enums\AccountStatusEnum;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RecruitmentContractRefundService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected RecruitmentTripAccountingService $tripAccountingService
    ) {
    }

    public const EXTERNAL_OFFICE_REVERSAL_SECTION = 'استرداد فاتورة المورد الخارجي';

    public static function externalOfficeReversalStatement(Contract $contract): string
    {
        return 'استرداد فاتورة المورد الخارجي - استرداد عقد استقدام #' . $contract->id;
    }

    /**
     * في وضع «إثبات الإيراد عند الفاتورة» تُعكس فاتورة المورد الخارجي مع استرداد العقد
     * (مدين المكتب الخارجي / دائن تكلفة المبيعات) فتعود فاتورة المكتب إلى صفر.
     */
    private function shouldReverseExternalOfficeCost(Contract $contract, Invoice $invoice): bool
    {
        if (!AutomaticJournalEntries::enabled()) {
            return false;
        }

        // روابط وضع on_contract تبقى على العقد حتى تُنسخ للفاتورة.
        $hasCost = (bool) ($invoice->external_office_cost_constraint_id
            ?: ($contract->getAttribute('external_office_cost_constraint_id') ?: null));
        if (!$hasCost) {
            return false;
        }

        if (RecruitmentRevenueRecognition::invoiceRecognizesOnInvoice($invoice)) {
            return true;
        }

        // تكلفة أُثبتت قبل الوصول بإعداد صريح (فاتورة مورد كاملة/جزئية من التسلسل الجديد، أو
        // عند الإرسالية، أو عند إنشاء العقد) تُعكس مع الاسترداد. الدورة الافتراضية لا تعكس فاتورة
        // المورد من نافذة الاسترداد، فتبقى كما هي.
        return !$this->contractHasArrived($contract, $invoice)
            && $this->supplierCostWasPostedEarlyByDesign($contract);
    }

    private function supplierCostWasPostedEarlyByDesign(Contract $contract): bool
    {
        $contract->loadMissing('externalOfficeDispatch');

        return $contract->externalOfficeDispatch?->supplier_recognition_mode !== null
            || $contract->getAttribute(\App\Support\RecruitmentSupplierCostRecognition::CONTRACT_COLUMN)
                === \App\Support\RecruitmentSupplierCostRecognition::MODE_ON_CONTRACT
            || \App\Support\RecruitmentSupplierCostRecognition::mode()
                === \App\Support\RecruitmentSupplierCostRecognition::MODE_ON_DISPATCH;
    }

    private function contractHasArrived(Contract $contract, Invoice $invoice): bool
    {
        return (bool) $invoice->arrival_revenue_constraint_id
            || filled($contract->info?->arrival_date)
            || in_array((string) $contract->status, ['arrival', 'sari', 'trial', 'completed', 'under_warranty', 'shelter', 'back'], true);
    }

    public const MUSANED_COMMISSION_REVERSAL_SECTION = 'عكس عمولة مساند';

    public static function musanedCommissionReversalStatement(Contract $contract): string
    {
        return 'عكس عمولة مساند المسجلة عند الإصدار - استرداد عقد استقدام #' . $contract->id . ' قبل التحويل';
    }

    /**
     * عمولة مساند المسجلة عند إصدار الفاتورة («التسلسل الجديد») لا تقع إن استُرد العقد قبل أي
     * تحويل من مساند، فتُعكس. بعد التحويل تبقى، لأن مساند حسمتها فعلاً.
     */
    private function shouldReverseMusanedCommission(Invoice $invoice, bool $hasMusanedTransfer): bool
    {
        return AutomaticJournalEntries::enabled()
            && !$hasMusanedTransfer
            && (bool) $invoice->musaned_commission_constraint_id
            && !($invoice->musaned_commission_reversal_constraint_id ?? null);
    }

    private function buildMusanedCommissionReversalLines(Invoice $invoice, string $statement): array
    {
        if (!\App\Support\MusanedCommissionPosting::isMergedIntoReceipt($invoice)) {
            return $this->buildReverseInvoiceLines((int) $invoice->musaned_commission_constraint_id, $statement);
        }

        // العمولة داخل قيد سند القبض: تُعكس سطورها وحدها، ويُعاد المبلغ إلى حساب مساند.
        // عكس القيد كاملاً كان سيعكس سداد العميل معه.
        $constraintId = (int) $invoice->musaned_commission_constraint_id;
        $musanedAccountId = (int) \Nami\Finance\Models\Bond::query()
            ->where('constraint_id', $constraintId)
            ->value('debit_account_id');
        $commissionLines = ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->where('statement', 'التكاليف البنكية لمساند عند إصدار الفاتورة #' . (int) $invoice->id)
            ->where('debtor_value', '>', 0)
            ->get();
        if (!$musanedAccountId || $commissionLines->isEmpty()) {
            return [];
        }

        $lines = [];
        $this->appendLine($lines, $musanedAccountId, 'debtor', (float) $commissionLines->sum('debtor_value'), $statement, null);
        foreach ($commissionLines as $detail) {
            $this->appendLine($lines, (int) $detail->account_id, 'creditor', (float) $detail->debtor_value, $statement, $detail->cost_center_id ? (int) $detail->cost_center_id : null);
        }

        return $lines;
    }

    private function reverseMusanedCommission(Contract $contract, Invoice $invoice, string $refundDate): ?Constraint
    {
        $statement = self::musanedCommissionReversalStatement($contract);
        $lines = $this->buildMusanedCommissionReversalLines($invoice, $statement);
        if (empty($lines)) {
            return null;
        }

        $activeYear = $this->yearService->checkDateRange($refundDate);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ الاسترداد المحدد.');
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $refundDate,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'musaned_commission_reversal_constraint_id')) {
            Invoice::query()->whereKey($invoice->id)
                ->update(['musaned_commission_reversal_constraint_id' => $constraint->id]);
        }

        return $constraint;
    }

    public function preview(Contract $contract, string $refundDate, ?string $cancelledBy = null, array $overrides = []): array
    {
        $contract->loadMissing(['invoice.musanedTransferItem.transfer', 'invoice.recruitmentCreditNotes', 'info']);
        $invoice = $contract->invoice;

        if (!$invoice) {
            throw new \RuntimeException('لا توجد فاتورة مرتبطة بالعقد.');
        }

        $transferItem = $invoice->musanedTransferItem;
        $hasMusanedTransfer = (bool) ($transferItem?->transfer);
        $contractAmount = $this->roundMoney((float) $invoice->total_due);
        $days = $this->daysFromContract($contract, $refundDate);
        $penaltyPercent = $hasMusanedTransfer ? $this->penaltyPercentForDays($days) : 0.0;
        $penaltyAmount = $this->roundMoney($contractAmount * ($penaltyPercent / 100));

        if ($hasMusanedTransfer) {
            if (isset($overrides['penalty_amount'])) {
                $penaltyAmount = $this->roundMoney((float) $overrides['penalty_amount']);
                if (!is_finite($penaltyAmount) || $penaltyAmount < 0 || $penaltyAmount > $contractAmount) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'penalty_amount' => 'مبلغ الخصم يجب أن يكون بين صفر وقيمة العقد.',
                    ]);
                }
                $penaltyPercent = $contractAmount > 0 ? round($penaltyAmount / $contractAmount * 100, 4) : 0.0;
            } elseif (isset($overrides['penalty_percent'])) {
                $penaltyPercent = (float) $overrides['penalty_percent'];
                if (!is_finite($penaltyPercent) || $penaltyPercent < 0 || $penaltyPercent > 100) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'penalty_percent' => 'نسبة الخصم يجب أن تكون بين صفر و100.',
                    ]);
                }
                $penaltyPercent = round($penaltyPercent, 4);
                $penaltyAmount = $this->roundMoney($contractAmount * $penaltyPercent / 100);
            }
        }

        if (!$hasMusanedTransfer) {
            $cancelledBy = 'none_before_transfer';
            $penaltyAmount = 0.0;
            $penaltyPercent = 0.0;
        } elseif (!in_array($cancelledBy, ['client', 'office', 'external_office'], true)) {
            $cancelledBy = null;
        }

        $grossRefundAmount = $hasMusanedTransfer && $cancelledBy === 'client'
            ? max(0, $this->roundMoney($contractAmount - $penaltyAmount))
            : $contractAmount;
        $previousCreditAmount = $this->roundMoney((float) $invoice->recruitmentCreditNotes->sum('total'));
        $remainingCreditNoteAmount = $this->roundMoney(max(0, $contractAmount - $previousCreditAmount));
        $clientRefundAmount = $this->roundMoney($grossRefundAmount);

        return [
            'has_musaned_transfer' => $hasMusanedTransfer,
            'cancelled_by' => $cancelledBy,
            'days_from_contract' => $days,
            'penalty_percent' => $penaltyPercent,
            'contract_amount' => $contractAmount,
            'penalty_amount' => $penaltyAmount,
            'refund_amount' => $clientRefundAmount,
            'previous_credit_amount' => $previousCreditAmount,
            'remaining_credit_note_amount' => $remainingCreditNoteAmount,
            'transfer_item' => $transferItem,
        ];
    }

    public function refund(Contract $contract, array $data): RecruitmentContractRefund
    {
        $contract->loadMissing(['invoice.musanedTransferItem.transfer', 'invoice.recruitmentCreditNotes', 'user', 'info']);
        $invoice = $contract->invoice;

        if (!$invoice) {
            throw new \RuntimeException('لا توجد فاتورة مرتبطة بالعقد.');
        }

        if (RecruitmentContractRefund::query()->where('contract_id', $contract->id)->exists()) {
            throw new \RuntimeException('تم تسجيل استرداد سابق لهذا العقد.');
        }

        $refundDate = $data['refund_date'] ?? now()->toDateString();
        $summary = $this->preview($contract, $refundDate, $data['cancelled_by'] ?? null, $data);

        if ($summary['has_musaned_transfer'] && !$summary['cancelled_by']) {
            throw new \RuntimeException('يجب تحديد الطرف المتسبب في الإلغاء بعد تحويل مبلغ العقد من مساند.');
        }

        $creditNote = app(InvoiceController::class)->createSystemCreditNoteForRecruitmentRefund(
            $invoice,
            $refundDate,
            'استرداد عقد الاستقدام #' . $contract->id . ': ' . ($data['reason'] ?? 'استرداد العقد'),
            $this->constraintService,
            $this->yearService
        );
        $constraint = null;
        /** @var MusanedTransferItem|null $transferItem */
        $transferItem = $summary['transfer_item'];

        if ($this->shouldReverseExternalOfficeCost($contract, $invoice)) {
            $this->tripAccountingService->reverseExternalOfficeCostEntries(
                $contract,
                $invoice,
                $refundDate,
                self::externalOfficeReversalStatement($contract)
            );
        }

        if ($this->shouldReverseMusanedCommission($invoice, (bool) $summary['has_musaned_transfer'])) {
            $this->reverseMusanedCommission($contract, $invoice, $refundDate);
        }

        if ($summary['has_musaned_transfer']
            && (float) $summary['penalty_amount'] > 0
            && AutomaticJournalEntries::enabled()) {
            $constraint = $this->createPenaltyConstraint(
                $contract,
                $transferItem,
                $refundDate,
                $summary
            );
        }

        return RecruitmentContractRefund::query()->create([
            'contract_id' => $contract->id,
            'invoice_id' => $invoice->id,
            'musaned_transfer_item_id' => $transferItem?->id,
            'refund_constraint_id' => $constraint?->id,
            'credit_note_id' => $creditNote?->id,
            'cancelled_by' => $summary['cancelled_by'],
            'refund_date' => $refundDate,
            'days_from_contract' => $summary['days_from_contract'],
            'penalty_percent' => $summary['penalty_percent'],
            'contract_amount' => $summary['contract_amount'],
            'penalty_amount' => $summary['penalty_amount'],
            'refund_amount' => $summary['refund_amount'],
            'reason' => $data['reason'] ?? null,
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
        ]);
    }

    public function previewDetails(Contract $contract, string $refundDate, ?string $cancelledBy = null, array $overrides = []): array
    {
        $contract->loadMissing(['invoice.musanedTransferItem.transfer', 'user', 'info']);
        $summary = $this->preview($contract, $refundDate, $cancelledBy, $overrides);
        $invoice = $contract->invoice;
        $statement = $this->buildStatement($contract, $summary);
        $warnings = [];
        $lines = [];

        if ((float) $summary['remaining_credit_note_amount'] > 0) {
            $sourceConstraintId = $invoice->arrival_revenue_constraint_id
                ?: $invoice->invoice_constraint_id
                ?: $invoice->constraint_id;
            $reverseLines = $this->buildReverseInvoiceLines(
                $sourceConstraintId,
                $statement,
                (float) $summary['remaining_credit_note_amount'],
                (float) $summary['contract_amount']
            );

            if (empty($reverseLines)) {
                $warnings[] = 'لا يوجد قيد فاتورة أو قيد إيراد وصول لعكسه ضمن قيد الاسترداد.';
            }

            $lines = array_merge($lines, $this->withSection($reverseLines, 'إشعار الدائن للرصيد غير المعكوس'));
        } else {
            $warnings[] = 'تم عكس كامل قيمة الفاتورة بإشعارات دائنة سابقة، ولن ينشأ إشعار إضافي.';
        }

        if ($this->shouldReverseMusanedCommission($invoice, (bool) $summary['has_musaned_transfer'])) {
            $commissionLines = $this->buildMusanedCommissionReversalLines(
                $invoice,
                self::musanedCommissionReversalStatement($contract)
            );
            $lines = array_merge($lines, $this->withSection($commissionLines, self::MUSANED_COMMISSION_REVERSAL_SECTION));
        }

        if ($this->shouldReverseExternalOfficeCost($contract, $invoice)) {
            if ($invoice->external_office_cost_reversal_constraint_id ?? null) {
                $warnings[] = 'تم عكس فاتورة المورد الخارجي مسبقاً، ولن يُنشأ قيد عكس إضافي.';
            } else {
                $reversalLines = $this->tripAccountingService->buildExternalOfficeCostReversalLines(
                    $invoice,
                    self::externalOfficeReversalStatement($contract)
                );
                $lines = array_merge($lines, $this->withSection($reversalLines, self::EXTERNAL_OFFICE_REVERSAL_SECTION));
            }
        }

        if ($summary['has_musaned_transfer'] && !AutomaticJournalEntries::enabled()
            && (float) $summary['penalty_amount'] > 0) {
            $warnings[] = 'لن يتم إنشاء قيد الغرامة لأن القيود التلقائية غير مفعلة.';
        }

        if ($summary['has_musaned_transfer'] && AutomaticJournalEntries::enabled()) {

            /** @var MusanedTransferItem|null $transferItem */
            $transferItem = $summary['transfer_item'];

            $clientAccountId = (int) ($contract->user?->account_id ?: 0);
            if (!$clientAccountId) {
                throw new \RuntimeException('لا يوجد حساب محاسبي للعميل المرتبط بالعقد.');
            }

            if (($summary['cancelled_by'] ?? null) === 'client' && (float) $summary['penalty_amount'] > 0) {
                $penaltyAccount = $this->findPenaltyIncomeAccount();
                if (!$penaltyAccount) {
                    $warnings[] = 'سيتم إنشاء حساب غرامات إلغاء عقود الاستقدام عند تسجيل الاسترداد.';
                }

                $penaltyLines = [];
                $this->appendLine($penaltyLines, $clientAccountId, 'debtor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
                $this->appendPreviewLine(
                    $penaltyLines,
                    $penaltyAccount?->id,
                    'creditor',
                    (float) $summary['penalty_amount'],
                    $statement,
                    $transferItem->cost_center_id,
                    $penaltyAccount?->name ?: 'غرامات إلغاء عقود الاستقدام'
                );
                $lines = array_merge($lines, $this->withSection($penaltyLines, 'غرامة الاسترداد'));
            }

            if (($summary['cancelled_by'] ?? null) === 'external_office' && (float) $summary['penalty_amount'] > 0) {
                $officeAccountId = $this->resolveExternalOfficeAccountId($contract);
                $penaltyAccount = $this->findPenaltyIncomeAccount();
                if (!$penaltyAccount) {
                    $warnings[] = 'سيتم إنشاء حساب غرامات إلغاء عقود الاستقدام عند تسجيل الاسترداد.';
                }

                $penaltyLines = [];
                $this->appendLine($penaltyLines, $officeAccountId, 'debtor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
                $this->appendPreviewLine(
                    $penaltyLines,
                    $penaltyAccount?->id,
                    'creditor',
                    (float) $summary['penalty_amount'],
                    $statement,
                    $transferItem->cost_center_id,
                    $penaltyAccount?->name ?: 'غرامات إلغاء عقود الاستقدام'
                );
                $lines = array_merge($lines, $this->withSection($penaltyLines, 'غرامة الاسترداد'));
            }

            if (($summary['cancelled_by'] ?? null) === 'office' && (float) $summary['penalty_amount'] > 0) {
                $warnings[] = 'تم تحديد المكتب كطرف متسبب، لذلك سيسترد العميل كامل قيمة العقد ولا يتم تحميل الغرامة على العميل أو الوكيل الخارجي.';
            }
        }

        $lines = $this->hydrateLineLabels($lines);

        return [
            'summary' => [
                'has_musaned_transfer' => $summary['has_musaned_transfer'],
                'cancelled_by' => $summary['cancelled_by'],
                'cancelled_by_label' => $this->cancelledByLabel($summary['cancelled_by']),
                'days_from_contract' => $summary['days_from_contract'],
                'penalty_percent' => $summary['penalty_percent'],
                'contract_amount' => $summary['contract_amount'],
                'penalty_amount' => $summary['penalty_amount'],
                'refund_amount' => $summary['refund_amount'],
                'previous_credit_amount' => $summary['previous_credit_amount'],
                'remaining_credit_note_amount' => $summary['remaining_credit_note_amount'],
                'statement' => $statement,
                'source_constraint_id' => $invoice->arrival_revenue_constraint_id
                    ?: $invoice->invoice_constraint_id
                    ?: $invoice->constraint_id,
            ],
            'lines' => $lines,
            'totals' => [
                'debtor' => $this->roundMoney(array_sum(array_column($lines, 'debtor_value'))),
                'creditor' => $this->roundMoney(array_sum(array_column($lines, 'creditor_value'))),
            ],
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function createPenaltyConstraint(
        Contract $contract,
        MusanedTransferItem $transferItem,
        string $refundDate,
        array $summary
    ): ?Constraint {
        $activeYear = $this->yearService->checkDateRange($refundDate);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ الاسترداد المحدد.');
        }

        $clientAccountId = (int) ($contract->user?->account_id ?: 0);
        if (!$clientAccountId) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للعميل المرتبط بالعقد.');
        }

        $statement = $this->buildStatement($contract, $summary);
        $lines = [];

        if (($summary['cancelled_by'] ?? null) === 'client' && (float) $summary['penalty_amount'] > 0) {
            $penaltyIncomeAccountId = $this->ensurePenaltyIncomeAccountId();
            $this->appendLine($lines, $clientAccountId, 'debtor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
            $this->appendLine($lines, $penaltyIncomeAccountId, 'creditor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
        }

        if (($summary['cancelled_by'] ?? null) === 'external_office' && (float) $summary['penalty_amount'] > 0) {
            $officeAccountId = $this->resolveExternalOfficeAccountId($contract);
            $penaltyIncomeAccountId = $this->ensurePenaltyIncomeAccountId();
            $this->appendLine($lines, $officeAccountId, 'debtor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
            $this->appendLine($lines, $penaltyIncomeAccountId, 'creditor', (float) $summary['penalty_amount'], $statement, $transferItem->cost_center_id);
        }

        if (empty($lines)) {
            return null;
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $refundDate,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function buildReverseInvoiceLines(
        ?int $constraintId,
        string $statement,
        ?float $remainingAmount = null,
        ?float $originalAmount = null
    ): array
    {
        if (!$constraintId) {
            return [];
        }

        $ratio = $remainingAmount !== null && $originalAmount && $originalAmount > 0
            ? min(1, max(0, $remainingAmount / $originalAmount))
            : 1;

        return ConstraintDetails::query()
            ->where('constraint_id', $constraintId)
            ->get()
            ->map(function (ConstraintDetails $detail) use ($statement, $ratio) {
                $debtor = $this->roundMoney((float) $detail->creditor_value * $ratio);
                $creditor = $this->roundMoney((float) $detail->debtor_value * $ratio);

                return [
                    'account_id' => (int) $detail->account_id,
                    'account_name' => $detail->account?->name,
                    'account_code' => $detail->account?->code,
                    'account_type' => $debtor > 0 ? 'debtor' : 'creditor',
                    'debtor_value' => $debtor,
                    'creditor_value' => $creditor,
                    'statement' => $statement,
                    'cost_center_id' => $detail->cost_center_id,
                    'cost_center_name' => $detail->costCenter?->name,
                ];
            })
            ->filter(fn (array $line) => $line['debtor_value'] > 0 || $line['creditor_value'] > 0)
            ->values()
            ->all();
    }

    private function appendLine(array &$lines, int $accountId, string $type, float $amount, string $statement, ?int $costCenterId): void
    {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'account_type' => $type,
            'debtor_value' => $type === 'debtor' ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function appendPreviewLine(
        array &$lines,
        ?int $accountId,
        string $type,
        float $amount,
        string $statement,
        ?int $costCenterId,
        ?string $accountName = null
    ): void {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'account_name' => $accountName,
            'account_type' => $type,
            'debtor_value' => $type === 'debtor' ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function withSection(array $lines, string $section): array
    {
        return array_map(function (array $line) use ($section) {
            $line['section'] = $section;
            return $line;
        }, $lines);
    }

    private function hydrateLineLabels(array $lines): array
    {
        $accountIds = collect($lines)->pluck('account_id')->filter()->unique()->values();
        $accounts = $accountIds->isEmpty()
            ? collect()
            : Account::query()->whereIn('id', $accountIds)->get()->keyBy('id');

        return collect($lines)->map(function (array $line) use ($accounts) {
            $account = $line['account_id'] ? $accounts->get($line['account_id']) : null;
            $line['account_name'] = $line['account_name'] ?? $account?->name ?? 'غير محدد';
            $line['account_code'] = $line['account_code'] ?? $account?->code;
            $line['cost_center_name'] = $line['cost_center_name'] ?? null;
            return $line;
        })->values()->all();
    }

    private function daysFromContract(Contract $contract, string $refundDate): int
    {
        $contractDate = $contract->info?->contract_date ?: $contract->contract_date ?: $contract->created_at?->toDateString();
        if (!$contractDate) {
            return 0;
        }

        return max(0, Carbon::parse($contractDate)->startOfDay()->diffInDays(Carbon::parse($refundDate)->startOfDay(), false) + 1);
    }

    private function penaltyPercentForDays(int $days): float
    {
        return match (true) {
            $days <= 5 => 0.0,
            $days <= 60 => 5.0,
            $days <= 90 => 10.0,
            $days <= 120 => 15.0,
            default => 20.0,
        };
    }

    private function resolveExternalOfficeAccountId(Contract $contract): int
    {
        $office = $contract->resolveExternalOfficeDispatchOffice();
        $accountId = (int) ($office?->account_id ?: 0);

        if ($accountId) {
            return $accountId;
        }

        throw new \RuntimeException('لا يوجد حساب محاسبي للمكتب الخارجي المحدد على العقد.');
    }

    private function ensurePenaltyIncomeAccountId(): int
    {
        // حساب الغرامة من إعدادات الحسابات أولاً (الدليل يستخدم 4217 «إيراد غرامة إلغاء / خصم الكفالة»).
        $configured = \App\Support\ShelterAccountingSettings::refundPenaltyAccountId();
        if ($configured) {
            return $configured;
        }

        $account = $this->findPenaltyIncomeAccount();

        if ($account) {
            return (int) $account->id;
        }

        $parent = Account::query()
            ->whereIn('type', [AccountTypesEnum::CompanyIncome->value, AccountTypesEnum::GeneralIncome->value])
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        // Imported charts may retain the normal-account type on the revenue root.
        if (!$parent) {
            $parent = Account::query()
                ->whereNull('parent_id')
                ->where('status', AccountStatusEnum::Creditor->value)
                ->whereIn('name', ['الإيرادات', 'الايرادات'])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if (!$parent) {
            throw new \RuntimeException('لا يوجد حساب إيرادات رئيسي لإنشاء حساب غرامات الاسترداد.');
        }

        // Re-check after acquiring lock to avoid duplicate creation under concurrency
        $account = $this->findPenaltyIncomeAccount();
        if ($account) {
            return (int) $account->id;
        }

        return (int) Account::query()->create([
            'name' => 'غرامات إلغاء عقود الاستقدام',
            'operation_type' => $parent->operation_type,
            'status' => AccountStatusEnum::Creditor->value,
            'statement_type' => $parent->statement_type,
            'level' => $parent->level + 1,
            'type' => AccountTypesEnum::GeneralIncome->value,
            'parent_id' => $parent->id,
            'code' => $this->nextAccountCode($parent),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ])->id;
    }

    private function findPenaltyIncomeAccount(): ?Account
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->where(function ($query) {
                $query->where('name', 'like', '%غرام%إلغاء%')
                    ->orWhere('name', 'like', '%غرامة%استرداد%');
            })
            ->orderBy('id')
            ->first();
    }

    private function nextAccountCode(Account $parent): int
    {
        $base = \Nami\Finance\Support\ChartSettings::childCodeStart($parent->code, (int) $parent->level) ?? ((int) $parent->code) * 100;

        $siblingCodes = Account::query()
            ->where('parent_id', $parent->id)
            ->orderBy('code')
            ->pluck('code')
            ->map(fn ($c) => (int) $c)
            ->all();

        $code = $base;
        foreach ($siblingCodes as $siblingCode) {
            if ($siblingCode < $code) {
                continue;
            }
            if ($siblingCode !== $code) {
                break;
            }
            $code++;
        }

        while (Account::query()->where('code', $code)->exists()) {
            $code++;
        }

        return $code;
    }

    private function buildStatement(Contract $contract, array $summary): string
    {
        $party = match ($summary['cancelled_by'] ?? null) {
            'client' => 'العميل',
            'office' => 'المكتب',
            'external_office' => 'الوكيل/المكتب الخارجي',
            default => 'بدون خصم قبل تحويل مساند',
        };

        return 'استرداد عقد استقدام #' . $contract->id . ' / الطرف المتسبب: ' . $party;
    }

    private function cancelledByLabel(?string $cancelledBy): string
    {
        return match ($cancelledBy) {
            'client' => 'العميل',
            'office' => 'المكتب',
            'external_office' => 'الوكيل/المكتب الخارجي',
            'none_before_transfer' => 'قبل تحويل مساند',
            default => 'غير محدد',
        };
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
