<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Services\ingaz\RentalContractFinanceService;
use Illuminate\Console\Command;

class AuditRentalContractFinance extends Command
{
    protected $signature = 'rental-contracts:audit-finance
        {--contract=* : Rental contract IDs to audit}
        {--export= : Optional CSV output path}
        {--summary : Show issue counts instead of the full contract table}
        {--apply-unposted : Correct unposted invoices only}
        {--include-legacy : Allow explicitly selected imported reference contracts to be changed}
        {--repair-extensions : Recalculate extension values and create missing extension constraints}
        {--repair-closures : Create missing constraints for already recorded closure adjustments}';

    protected $description = 'Audit rental VAT, exempt salary allocation, extension values, and missing financial postings without altering posted invoices';

    public function handle(RentalContractFinanceService $financeService): int
    {
        $revenueAccountIds = $financeService->rentalRevenueAccountIds();
        $contractIds = collect($this->option('contract'))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $willWrite = $this->option('apply-unposted')
            || $this->option('repair-extensions')
            || $this->option('repair-closures');

        if ($willWrite && $contractIds->isEmpty()) {
            $this->error('خيارات الإصلاح تتطلب تحديد العقود صراحةً عبر --contract=ID، ولا يسمح بتطبيقها على جميع العقود.');

            return self::FAILURE;
        }

        $query = RentalContract::query()
            ->with([
                'info.package',
                'maid',
                'financeInvoice.invoiceConstraint.constraintDetails.account',
                'extensions',
                'financialAdjustments',
            ])
            ->orderBy('id');

        if ($contractIds->isNotEmpty()) {
            $query->whereIn('id', $contractIds);
        }

        $rows = [];
        $query->chunkById(200, function ($contracts) use ($financeService, $revenueAccountIds, &$rows): void {
            foreach ($contracts as $contract) {
                if (!$contract->info || !$contract->financeInvoice) {
                    $rows[] = $this->row($contract, 'missing_invoice', 0, 0, 0, 0, 'لا توجد فاتورة مالية مكتملة');
                    continue;
                }

                $expected = $financeService->expectedInvoiceAmounts($contract);
                $invoice = $contract->financeInvoice;
                $totalDelta = round((float) $invoice->total_due - (float) $expected['total_due'], 2);
                $vatDelta = round((float) $invoice->vat_amount - (float) $expected['vat_amount'], 2);
                $posted = (bool) $invoice->invoice_constraint_id;
                $isLegacy = !empty($contract->legacy_source);
                $status = abs($totalDelta) < 0.01 && abs($vatDelta) < 0.01
                    ? 'invoice_ok'
                    : ($posted
                        ? 'posted_credit_or_debit_note_required'
                        : ($isLegacy
                            ? 'legacy_reference_reconciliation_required'
                            : 'unposted_invoice_can_be_corrected'));

                $rows[] = $this->row(
                    $contract,
                    $status,
                    (float) $invoice->total_due,
                    (float) $expected['total_due'],
                    (float) $invoice->vat_amount,
                    (float) $expected['vat_amount'],
                    $posted
                        ? 'لا تعدّل الفاتورة/القيد الأصلي؛ أنشئ إشعار تصحيح مستقل'
                        : ($isLegacy ? 'مرجع مستورد: طابقه مع مستند المصدر قبل أي تعديل' : '')
                );

                if ($posted && $invoice->invoiceConstraint) {
                    $details = $invoice->invoiceConstraint->constraintDetails;
                    $salaryDetails = $details->filter(fn ($detail) =>
                        (float) $detail->creditor_value > 0
                        && (str_contains((string) $detail->statement, 'مرتب العامل')
                            || str_contains((string) $detail->statement, 'راتب العاملة'))
                    );
                    $mispostedSalary = $salaryDetails
                        ->where('account_id', '!=', $revenueAccountIds['salary_exempt']);

                    if ($mispostedSalary->isNotEmpty()) {
                        $accounts = $mispostedSalary
                            ->map(fn ($detail) => ($detail->account?->code ?: '-') . ' / ' . ($detail->account?->name ?: 'حساب محذوف'))
                            ->unique()
                            ->implode('، ');
                        $rows[] = $this->row(
                            $contract,
                            'salary_account_reclassification_required',
                            (float) $mispostedSalary->sum('creditor_value'),
                            (float) ($expected['salary_exempt_amount'] ?? 0),
                            0,
                            0,
                            'راتب العاملة مسجل على: ' . $accounts
                                . '؛ المطلوب حساب #' . $revenueAccountIds['salary_exempt']
                                . '. لا تعدّل القيد الأصلي؛ استخدم قيد إعادة تصنيف بعد اعتماد المحاسب.'
                        );
                    }

                    $serviceDetails = $details->filter(fn ($detail) =>
                        (float) $detail->creditor_value > 0
                        && str_contains((string) $detail->statement, 'خدمات التأجير الخاضعة للضريبة')
                    );
                    $mispostedService = $serviceDetails
                        ->where('account_id', '!=', $revenueAccountIds['service']);

                    if ($mispostedService->isNotEmpty()) {
                        $accounts = $mispostedService
                            ->map(fn ($detail) => ($detail->account?->code ?: '-') . ' / ' . ($detail->account?->name ?: 'حساب محذوف'))
                            ->unique()
                            ->implode('، ');
                        $rows[] = $this->row(
                            $contract,
                            'service_account_reclassification_required',
                            (float) $mispostedService->sum('creditor_value'),
                            (float) ($expected['taxable_subtotal'] ?? 0),
                            0,
                            0,
                            'خدمة التأجير مسجلة على: ' . $accounts
                                . '؛ المطلوب حساب #' . $revenueAccountIds['service']
                                . '. لا تعدّل القيد الأصلي؛ استخدم قيد إعادة تصنيف بعد اعتماد المحاسب.'
                        );
                    }
                }

                if (
                    $this->option('apply-unposted')
                    && !$posted
                    && (!$isLegacy || $this->option('include-legacy'))
                ) {
                    $paid = min((float) $expected['total_due'], (float) $contract->info->paid_amount);
                    $remaining = round(max(0, (float) $expected['total_due'] - $paid), 2);
                    $invoice->update([
                        'subtotal' => $expected['subtotal'],
                        'discount' => $expected['discount'],
                        'vat_amount' => $expected['vat_amount'],
                        'total_due' => $expected['total_due'],
                        'paid_amount' => $paid,
                        'remaining_amount' => $remaining,
                        'is_paid' => $remaining <= 0,
                    ]);
                    $contract->info->update([
                        'package_vat_amount' => $expected['vat_amount'],
                        'package_total_with_tax' => $expected['total_due'],
                        'contract_value' => $expected['total_due'],
                        'paid_amount' => $paid,
                        'remaining_amount' => $remaining,
                    ]);
                }

                foreach ($contract->extensions as $extension) {
                    $extensionExpected = $financeService->extensionAmountsFor($extension);
                    $extensionDelta = round(
                        (float) $extension->extension_value - (float) $extensionExpected['total_due'],
                        2
                    );
                    $missingConstraint = !$extension->invoice_constraint_id;

                    if (abs($extensionDelta) >= 0.01 || $missingConstraint) {
                        $rows[] = $this->row(
                            $contract,
                            $missingConstraint ? 'extension_constraint_missing' : 'extension_value_mismatch',
                            (float) $extension->extension_value,
                            (float) $extensionExpected['total_due'],
                            0,
                            (float) $extensionExpected['vat_amount'],
                            'تمديد #' . $extension->id
                        );
                    }

                    if ($this->option('repair-extensions')) {
                        if (!$extension->invoice_constraint_id) {
                            $extension->update(['extension_value' => $extensionExpected['total_due']]);
                            $financeService->recordExtensionForContract(
                                $contract,
                                $extension->fresh(),
                                $extension->created_at ?: now()
                            );
                        }
                    }
                }

                foreach ($contract->financialAdjustments->whereIn('context', ['closure', 'closure_correction']) as $adjustment) {
                    if ((float) $adjustment->amount <= 0 || $adjustment->constraint_id) {
                        continue;
                    }

                    $rows[] = $this->row(
                        $contract,
                        'closure_constraint_missing',
                        (float) $adjustment->amount,
                        (float) $adjustment->amount,
                        0,
                        0,
                        'تسوية إنهاء #' . $adjustment->id
                    );

                    if ($this->option('repair-closures')) {
                        $financeService->recordClosureAdjustment(
                            $contract,
                            $adjustment,
                            $contract->closed_at ?: $adjustment->created_at ?: now()
                        );
                    }
                }
            }
        });

        if ($this->option('summary')) {
            $summaryRows = collect($rows)
                ->countBy('status')
                ->map(fn (int $count, string $status) => [$status, $count])
                ->values()
                ->all();
            $this->table(['الحالة', 'العدد'], $summaryRows);
        } else {
            $this->table(
                ['العقد', 'الرقم', 'الحالة', 'المسجل', 'الصحيح', 'ضريبة مسجلة', 'ضريبة صحيحة', 'الفرق', 'ملاحظة'],
                array_map(fn (array $row) => array_values($row), $rows)
            );
        }

        if ($path = $this->option('export')) {
            $this->exportCsv($path, $rows);
            $this->info('تم حفظ تقرير التدقيق: ' . $path);
        }

        $this->info('التدقيق لا يعدّل الفواتير المنشورة أو قيودها. فروقات المبالغ تتطلب إشعارات تصحيح، وفروق حساب الراتب تتطلب قيد إعادة تصنيف مستقل بعد اعتماد المحاسب.');

        return self::SUCCESS;
    }

    private function row(
        RentalContract $contract,
        string $status,
        float $storedTotal,
        float $expectedTotal,
        float $storedVat,
        float $expectedVat,
        string $note
    ): array {
        return [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number ?: '-',
            'status' => $status,
            'stored_total' => number_format($storedTotal, 2, '.', ''),
            'expected_total' => number_format($expectedTotal, 2, '.', ''),
            'stored_vat' => number_format($storedVat, 2, '.', ''),
            'expected_vat' => number_format($expectedVat, 2, '.', ''),
            'delta' => number_format($storedTotal - $expectedTotal, 2, '.', ''),
            'note' => $note,
        ];
    }

    private function exportCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('تعذر فتح مسار تقرير CSV للكتابة.');
        }

        fputcsv($handle, [
            'contract_id',
            'contract_number',
            'status',
            'stored_total',
            'expected_total',
            'stored_vat',
            'expected_vat',
            'delta',
            'note',
        ]);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}
