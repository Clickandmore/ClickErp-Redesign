<?php

namespace App\Services\ingaz;

use App\Models\RentalContract;
use App\Models\RentalFinancialAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * يُصدر مستند تصحيح واحداً ينقل رصيد عقد مغلق إلى قيمته الصحيحة.
 *
 * القاعدة الحاكمة: لا يُحذف قيد ولا يُعدَّل مستند ضريبي صادر. التصحيح دائماً
 * مستند جديد مؤرخ بتاريخه يشير إلى الفاتورة الأصل.
 *
 * التقسيم الضريبي لا يُعاد حسابه، بل يُشتق من قيد الفاتورة الأصلي حساباً بحساب
 * وبالتناسب. هذا يحفظ نسبة 15% تلقائياً (لأن القيد الأصلي يحفظها)، ويضع كل جزء
 * على نفس الحساب الذي قُيِّد عليه أصلاً — بما في ذلك العقود القديمة التي رحّلت
 * راتب العاملة المعفى على حساب 4216 المستقل بدل 4214.
 */
class RentalClosureCorrectionService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected RentalContractLedgerService $ledgerService
    ) {
    }

    /**
     * يبني المستند دون ترحيله — لمعاينة ما سيحدث قبل الالتزام به.
     *
     * @param float $correction موجب: العميل مُثقل بأكثر مما يستحق -> إشعار دائن.
     *                         سالب: العميل مُثقل بأقل -> إشعار مدين.
     */
    public function preview(RentalContract $contract, float $correction): array
    {
        $contract->loadMissing(['user', 'financeInvoice', 'maid']);
        $invoice = $contract->financeInvoice;

        if (!$invoice?->invoice_constraint_id) {
            throw new \RuntimeException('لا يوجد قيد فاتورة مرحَّل لهذا العقد، فلا يمكن اشتقاق تقسيم التصحيح.');
        }

        $amount = round(abs($correction), 2);

        if ($amount < 0.01) {
            throw new \RuntimeException('قيمة التصحيح أقل من قرش واحد.');
        }

        $isCreditNote = $correction > 0;
        $allocation = $this->allocateFromInvoiceConstraint($contract, $invoice->invoice_constraint_id, $amount);

        return [
            'contract_id' => $contract->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'is_credit_note' => $isCreditNote,
            'document' => $isCreditNote ? 'إشعار دائن ضريبي' : 'إشعار مدين ضريبي',
            'lines' => $allocation['lines'],
            'taxable_amount' => $allocation['taxable'],
            'salary_exempt_amount' => $allocation['exempt'],
            'vat_amount' => $allocation['vat'],
            'effective_vat_rate' => $allocation['taxable'] > 0
                ? round($allocation['vat'] / $allocation['taxable'] * 100, 2)
                : 0.0,
        ];
    }

    /**
     * يُنشئ التسوية ويرحّل قيدها داخل معاملة واحدة.
     */
    public function apply(
        RentalContract $contract,
        float $correction,
        string $reason,
        Carbon|string|null $issueDate = null
    ): array {
        $preview = $this->preview($contract, $correction);
        $date = $issueDate instanceof Carbon
            ? $issueDate->copy()->startOfDay()
            : ($issueDate ? Carbon::parse($issueDate)->startOfDay() : Carbon::now()->startOfDay());

        $activeYear = $this->yearService->checkDateRange($date->toDateString());

        if (!$activeYear) {
            throw new \RuntimeException(
                'لا توجد سنة مالية نشطة لتاريخ ' . $date->toDateString() . '. لا يُرحَّل التصحيح في فترة مقفلة.'
            );
        }

        return DB::transaction(function () use ($contract, $preview, $reason, $date, $activeYear) {
            $invoice = $contract->financeInvoice;
            $statement = ($preview['is_credit_note'] ? 'إشعار دائن تصحيحي' : 'إشعار مدين تصحيحي')
                . ' لعقد تأجير رقم ' . ($contract->contract_number ?: $contract->id)
                . ' / الفاتورة #' . $invoice->id
                . ' / العميل ' . ($contract->user?->name ?: 'غير محدد');

            $adjustment = $contract->financialAdjustments()->create([
                'maid_id' => $contract->maid_id,
                'type' => $preview['is_credit_note'] ? 'expense' : 'income',
                'label' => ($preview['is_credit_note'] ? 'إشعار دائن تصحيحي' : 'إشعار مدين تصحيحي')
                    . ' لتسوية إنهاء العقد',
                'amount' => $preview['amount'],
                'taxable_amount' => $preview['taxable_amount'],
                'non_taxable_amount' => $preview['salary_exempt_amount'],
                'vat_amount' => $preview['vat_amount'],
                // الإرسال لهيئة الزكاة قرار منفصل يتخذه المحاسب بعد المراجعة
                'send_to_zatca' => false,
                'notes' => $reason,
                'context' => 'closure_correction',
                'issue_date' => $date->toDateString(),
                'created_by' => Auth::guard('admin')->id() ?: Auth::id(),
            ]);

            $constraint = $this->storeCorrectionConstraint(
                $contract,
                $preview,
                $statement . ' / ' . $reason,
                $date,
                $activeYear
            );

            $adjustment->update(['constraint_id' => $constraint->id]);

            return [
                'adjustment' => $adjustment->fresh(),
                'constraint' => $constraint,
                'preview' => $preview,
                'balance_after' => $this->ledgerService->balanceForContract($contract->fresh()),
            ];
        });
    }

    /**
     * يوزّع مبلغ التصحيح على نفس حسابات قيد الفاتورة الأصلي بالتناسب.
     *
     * الطرف المدين في قيد الفاتورة هو حساب العميل، والأطراف الدائنة هي الإيراد
     * بأنواعه وضريبة القيمة المضافة. التوزيع بالتناسب على هذه الأطراف يعيد
     * إنتاج تركيبة الفاتورة نفسها مهما كانت.
     */
    private function allocateFromInvoiceConstraint(
        RentalContract $contract,
        int $invoiceConstraintId,
        float $amount
    ): array {
        $clientAccountId = (int) ($contract->user?->account_id ?? 0);
        $taxAccountId = $this->revenueTaxAccountId();

        $creditLines = DB::table('fn_constraint_details')
            ->where('constraint_id', $invoiceConstraintId)
            ->whereNull('deleted_at')
            ->where('creditor_value', '>', 0)
            ->get(['account_id', 'creditor_value', 'cost_center_id', 'statement']);

        $invoiceTotal = round((float) $creditLines->sum('creditor_value'), 2);

        if ($creditLines->isEmpty() || $invoiceTotal <= 0) {
            throw new \RuntimeException('قيد الفاتورة الأصلي لا يحتوي أطرافاً دائنة صالحة للتوزيع.');
        }

        $ratio = $amount / $invoiceTotal;
        $lines = [];

        foreach ($creditLines as $line) {
            $share = round((float) $line->creditor_value * $ratio, 2);

            if ($share < 0.01) {
                continue;
            }

            $lines[] = [
                'account_id' => (int) $line->account_id,
                'amount' => $share,
                'cost_center_id' => $line->cost_center_id ? (int) $line->cost_center_id : null,
                'is_tax' => $taxAccountId && (int) $line->account_id === $taxAccountId,
                'statement' => (string) $line->statement,
            ];
        }

        if ($lines === []) {
            throw new \RuntimeException('تعذّر توزيع مبلغ التصحيح على أطراف الفاتورة.');
        }

        // فروق التقريب تُضبط على أكبر طرف غير ضريبي حتى يساوي المجموع المبلغ
        $allocated = round(array_sum(array_column($lines, 'amount')), 2);
        $drift = round($amount - $allocated, 2);

        if (abs($drift) >= 0.01) {
            $target = 0;
            foreach ($lines as $index => $line) {
                if (!$line['is_tax'] && $line['amount'] > $lines[$target]['amount']) {
                    $target = $index;
                }
            }
            $lines[$target]['amount'] = round($lines[$target]['amount'] + $drift, 2);
        }

        $vat = 0.0;
        $exempt = 0.0;
        $taxable = 0.0;

        foreach ($lines as $line) {
            if ($line['is_tax']) {
                $vat += $line['amount'];
            } elseif (str_contains($line['statement'], 'معفى') || str_contains($line['statement'], 'مرتب')) {
                $exempt += $line['amount'];
            } else {
                $taxable += $line['amount'];
            }
        }

        // العقود القديمة لا تحمل بيان "معفى" على سطر الراتب، فيُشتق المعفى من
        // الضريبة: الوعاء الخاضع شامل الضريبة = ض × 1.15 / 0.15، والباقي معفى.
        if ($exempt <= 0 && $vat > 0) {
            $derivedTaxable = round($vat / 0.15, 2);
            if ($derivedTaxable < $taxable - 0.01) {
                $exempt = round($taxable - $derivedTaxable, 2);
                $taxable = $derivedTaxable;
            }
        }

        return [
            'lines' => $lines,
            'taxable' => round($taxable, 2),
            'exempt' => round($exempt, 2),
            'vat' => round($vat, 2),
            'invoice_total' => $invoiceTotal,
        ];
    }

    private function storeCorrectionConstraint(
        RentalContract $contract,
        array $preview,
        string $statement,
        Carbon $date,
        $activeYear
    ): Constraint {
        $clientAccountId = (int) ($contract->user?->account_id ?? 0);

        if ($clientAccountId <= 0) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للعميل، فلا يمكن ترحيل التصحيح.');
        }

        $rows = [];

        foreach ($preview['lines'] as $line) {
            // الإشعار الدائن يعكس الفاتورة: يُمدَّن الإيراد والضريبة ويُرصَّد العميل.
            // الإشعار المدين يكررها في نفس اتجاه الفاتورة الأصلية.
            $rows[] = [
                'account_id' => $line['account_id'],
                'account_type' => $preview['is_credit_note'] ? 'debtor' : 'creditor',
                'debtor_value' => $preview['is_credit_note'] ? $line['amount'] : 0,
                'creditor_value' => $preview['is_credit_note'] ? 0 : $line['amount'],
                'statement' => $statement,
                'cost_center_id' => $line['cost_center_id'],
            ];
            $rows[] = [
                'account_id' => $clientAccountId,
                'account_type' => $preview['is_credit_note'] ? 'creditor' : 'debtor',
                'debtor_value' => $preview['is_credit_note'] ? 0 : $line['amount'],
                'creditor_value' => $preview['is_credit_note'] ? $line['amount'] : 0,
                'statement' => $statement,
                'cost_center_id' => null,
            ];
        }

        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date->toDateString(),
            'statement' => $statement,
            'account_id' => array_column($rows, 'account_id'),
            'account_type' => array_column($rows, 'account_type'),
            'debtor_value' => array_column($rows, 'debtor_value'),
            'creditor_value' => array_column($rows, 'creditor_value'),
            'statement_account' => array_column($rows, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($rows, 'cost_center_id'),
            'value' => $preview['amount'],
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function revenueTaxAccountId(): ?int
    {
        $accountId = (int) DB::table('fn_confirm_settings')
            ->where('title', 'revenue_tax_account')
            ->latest('updated_at')
            ->value('value');

        return $accountId ?: null;
    }
}
