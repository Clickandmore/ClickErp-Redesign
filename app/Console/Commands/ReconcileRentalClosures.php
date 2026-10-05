<?php

namespace App\Console\Commands;

use App\Models\RentalContract;
use App\Services\ingaz\RentalContractLedgerService;
use App\Support\RentalClosureReconciler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * مطابقة العقود المغلقة بالثابتة: رصيد حساب العميل ≡ المستحَق − المدفوع.
 *
 * قراءة فقط. لا يكتب في قاعدة البيانات ولا يُنشئ أي قيد أو مستند ضريبي.
 */
class ReconcileRentalClosures extends Command
{
    protected $signature = 'rental-contracts:reconcile-closures
        {--contract=* : أرقام عقود محددة للفحص}
        {--all : يفحص كل العقود لا المغلقة فقط}
        {--only-issues : يطبع المخالِف فقط}
        {--vat : يضيف قسم فحص تقسيم الضريبة على التسويات المرحَّلة}
        {--export= : مسار ملف CSV لحفظ النتيجة}
        {--apply : يُصدر مستند التصحيح — يتطلب --contract و--reason}
        {--reason= : سبب التصحيح، يُحفظ في بيان القيد وفي سجل التسوية}
        {--dry-run : يعرض القيد المزمع ترحيله دون كتابته}
        {--allow-reviewed : يسمح بتصحيح العقود المصنَّفة قراراً تجارياً أو مراجعة يدوية}';

    protected $description = 'مطابقة رصيد عقود التأجير المغلقة مع دفتر الأستاذ، وإصدار مستند تصحيح لعقد واحد عند الطلب';

    public function handle(RentalContractLedgerService $ledger): int
    {
        if ($this->option('apply') || $this->option('dry-run')) {
            return $this->applyCorrection($ledger);
        }

        $contractIds = collect($this->option('contract'))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = RentalContract::query()
            ->with(['info', 'financeInvoice', 'financialAdjustments', 'extensions', 'financeBonds', 'user'])
            ->orderBy('id');

        if ($contractIds->isNotEmpty()) {
            $query->whereIn('id', $contractIds);
        } elseif (!$this->option('all')) {
            $query->whereIn('status', ['completed', 'cancelled'])->whereNotNull('closed_at');
        }

        $rows = [];
        $skipped = 0;
        $matched = 0;

        $query->chunkById(200, function ($contracts) use ($ledger, &$rows, &$skipped, &$matched): void {
            foreach ($contracts as $contract) {
                $picture = $this->picture($contract, $ledger);

                if ($picture === null) {
                    $skipped++;
                    continue;
                }

                if (abs($picture['difference']) < RentalClosureReconciler::TOLERANCE) {
                    $matched++;

                    if ($this->option('only-issues')) {
                        continue;
                    }
                }

                $rows[] = $picture;
            }
        });

        $issues = array_values(array_filter(
            $rows,
            fn (array $row) => abs($row['difference']) >= RentalClosureReconciler::TOLERANCE
        ));

        $this->renderTable($rows);

        $this->newLine();
        $this->line(sprintf(
            'مفحوصة: %d | مطابِقة للثابتة: %d | تحتاج مراجعة: %d | متخطاة (بلا قيد فاتورة أو حساب عميل): %d',
            $matched + count($issues),
            $matched,
            count($issues),
            $skipped
        ));

        if ($issues !== []) {
            $this->newLine();
            $this->table(['التصنيف', 'العدد', 'إجمالي الفرق'], $this->classificationSummary($issues));
        }

        if ($this->option('vat')) {
            $this->newLine();
            $this->vatSection();
        }

        if ($path = $this->option('export')) {
            $this->exportCsv($path, $rows);
            $this->info('تم حفظ التقرير: ' . $path);
        }

        $this->newLine();
        $this->info('هذا الأمر للقراءة فقط. لم تُعدَّل أي بيانات ولم يُنشأ أي قيد.');
        $this->line('الثابتة المطبَّقة: رصيد حساب العميل من قيود العقد ≡ المستحَق فعلياً − المدفوع.');

        return self::SUCCESS;
    }

    /**
     * إصدار مستند تصحيح لعقد واحد. لا يعمل جماعياً بأي حال.
     */
    private function applyCorrection(RentalContractLedgerService $ledger): int
    {
        $contractIds = collect($this->option('contract'))
            ->filter(fn ($id) => ctype_digit((string) $id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($contractIds->count() !== 1) {
            $this->error('التصحيح يعمل على عقد واحد فقط. حدّد --contract=ID مرة واحدة.');

            return self::FAILURE;
        }

        $reason = trim((string) $this->option('reason'));
        $isDryRun = $this->option('dry-run');

        if (!$isDryRun && $reason === '') {
            $this->error('--reason إلزامي عند --apply. يُحفظ في بيان القيد وفي سجل التسوية.');

            return self::FAILURE;
        }

        $contract = RentalContract::query()
            ->with(['info', 'financeInvoice', 'financialAdjustments', 'extensions', 'financeBonds', 'user', 'maid'])
            ->find($contractIds->first());

        if (!$contract) {
            $this->error('العقد غير موجود.');

            return self::FAILURE;
        }

        $picture = $this->picture($contract, $ledger);

        if ($picture === null) {
            $this->error('تعذّرت مطابقة هذا العقد من الأستاذ: لا قيد فاتورة مرحَّل أو لا حساب محاسبي للعميل.');

            return self::FAILURE;
        }

        $this->renderTable([$picture]);
        $this->newLine();

        if (abs($picture['difference']) < RentalClosureReconciler::TOLERANCE) {
            $this->info('العقد مطابق للثابتة بالفعل. لا حاجة لأي تصحيح.');

            return self::SUCCESS;
        }

        $autoCorrectable = ['structural_error', 'closure_document_missing'];

        if (!in_array($picture['classification'], $autoCorrectable, true) && !$this->option('allow-reviewed')) {
            $this->error(sprintf(
                'العقد مصنَّف "%s" ولا يُصحَّح آلياً.',
                $this->classificationLabel($picture['classification'])
            ));
            $this->line('الفروق من هذا النوع قد تكون قرارات تجارية سليمة أو بيانات مستوردة تحتاج مطابقة مع مستند المصدر.');
            $this->line('لو تأكدت أنه خطأ فعلاً، أعد الأمر مع --allow-reviewed.');

            return self::FAILURE;
        }

        $correction = round($picture['actual_balance'] - $picture['expected_balance'], 2);
        $service = app(\App\Services\ingaz\RentalClosureCorrectionService::class);

        try {
            $preview = $service->preview($contract, $correction);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line(sprintf(
            'المستند المزمع: <options=bold>%s بقيمة %s ريال</> بتاريخ %s',
            $preview['document'],
            number_format($preview['amount'], 2),
            now()->toDateString()
        ));
        $this->newLine();
        $this->table(
            ['الحساب', 'مدين', 'دائن', 'مركز التكلفة'],
            $this->previewRows($preview, $contract)
        );
        $this->line(sprintf(
            'التقسيم: خاضع %s | معفى %s | ض.ق.م %s (%s%%)',
            number_format($preview['taxable_amount'], 2),
            number_format($preview['salary_exempt_amount'], 2),
            number_format($preview['vat_amount'], 2),
            $preview['effective_vat_rate']
        ));
        $this->line(sprintf(
            'الرصيد: %s ← %s',
            number_format($picture['actual_balance'], 2),
            number_format($picture['expected_balance'], 2)
        ));

        if ($isDryRun) {
            $this->newLine();
            $this->info('معاينة فقط. لم يُكتب أي شيء. أعد الأمر مع --apply و--reason للترحيل.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('سيُرحَّل قيد محاسبي جديد ولن يُحذف أو يُعدَّل أي مستند قائم.');

        if (!$this->confirm('هل أخذت نسخة احتياطية من قاعدة البيانات وتريد المتابعة؟', false)) {
            $this->line('أُلغي التصحيح. لم يُكتب أي شيء.');

            return self::SUCCESS;
        }

        try {
            $result = $service->apply($contract, $correction, $reason);
        } catch (\Throwable $e) {
            $this->error('فشل الترحيل: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            'تم الترحيل. القيد #%s (كود %s) | التسوية #%s',
            $result['constraint']->id,
            $result['constraint']->code,
            $result['adjustment']->id
        ));
        $this->line('الرصيد بعد الترحيل: ' . number_format((float) $result['balance_after'], 2) . ' ريال');
        $this->newLine();
        $this->line('للتراجع عن هذا التصحيح تحديداً:');
        $this->line(sprintf(
            '  php artisan rental-contracts:reconcile-closures --contract=%d   # للتأكد من الرصيد',
            $contract->id
        ));
        $this->line(sprintf(
            '  ثم احذف القيد #%s والتسوية #%s من شاشة القيود، أو استرجع النسخة الاحتياطية.',
            $result['constraint']->id,
            $result['adjustment']->id
        ));
        $this->newLine();
        $this->warn('لم يُرسَل هذا المستند لهيئة الزكاة. الإرسال قرار منفصل بعد مراجعة المحاسب.');

        return self::SUCCESS;
    }

    private function previewRows(array $preview, RentalContract $contract): array
    {
        $rows = [];
        $clientName = $contract->user?->name ?: 'العميل';

        foreach ($preview['lines'] as $line) {
            $account = DB::table('fn_accounts')->where('id', $line['account_id'])->first();
            $rows[] = [
                sprintf('%s (%s)', $account->name ?? '?', $account->code ?? $line['account_id']),
                $preview['is_credit_note'] ? number_format($line['amount'], 2) : '—',
                $preview['is_credit_note'] ? '—' : number_format($line['amount'], 2),
                $line['cost_center_id'] ?: '—',
            ];
        }

        $rows[] = [
            mb_substr($clientName, 0, 40) . ' — ذمم عملاء',
            $preview['is_credit_note'] ? '—' : number_format($preview['amount'], 2),
            $preview['is_credit_note'] ? number_format($preview['amount'], 2) : '—',
            '—',
        ];

        return $rows;
    }

    /**
     * صورة العقد المالية الكاملة، أو null إذا تعذّرت المطابقة من الأستاذ.
     */
    private function picture(RentalContract $contract, RentalContractLedgerService $ledger): ?array
    {
        $invoice = $contract->financeInvoice;
        $info = $contract->info;

        if (!$invoice || !$invoice->invoice_constraint_id || !$info) {
            return null;
        }

        $actual = $ledger->balanceForContract($contract);

        if ($actual === null) {
            return null;
        }

        // القيمة المفوترة تؤخذ من القيد المرحَّل لا من سطر الفاتورة: العمودان
        // يختلفان في جزء كبير من الفواتير القديمة.
        $postedTotal = (float) DB::table('fn_constraint_details')
            ->where('constraint_id', $invoice->invoice_constraint_id)
            ->where('account_id', $contract->user?->account_id)
            ->whereNull('deleted_at')
            ->sum('debtor_value');
        $invoiceTotal = $postedTotal > 0 ? $postedTotal : (float) $invoice->total_due;

        $extensionDays = (int) $contract->extensions->sum('extension_days');
        $extensionValue = (float) $contract->extensions->sum('extension_value');
        $invoiced = round($invoiceTotal + $extensionValue, 2);

        $baseDays = (int) ($info->contract_duration_days ?: ((int) $info->contract_duration_months * 30));
        $totalDays = $baseDays + $extensionDays;

        $paid = $ledger->confirmedReceiptsTotal($contract);

        [$usedDays, $overdueDays] = $this->days($contract, $totalDays);

        $earned = RentalClosureReconciler::earned($invoiced, $totalDays, $usedDays, $overdueDays);
        // Approved closure pricing is a business decision, not a ledger error.
        // Keep the exact earned amount saved by the transactional close flow.
        $pricing = $info->closure_pricing;
        if (is_array($pricing) && $contract->closed_at
            && ($pricing['closure_date'] ?? null) === $contract->closed_at->toDateString()
            && isset($pricing['earned_amount']) && is_numeric($pricing['earned_amount'])) {
            $earned = round(max(0, (float) $pricing['earned_amount']), 2);
        }
        $settlement = RentalClosureReconciler::settle($invoiced, $paid, $earned);
        $difference = round($actual - $settlement['expected_balance'], 2);

        return [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number ?: (string) $contract->id,
            'status' => $contract->status,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'earned' => $earned,
            'used_days' => $usedDays,
            'overdue_days' => $overdueDays,
            'total_days' => $totalDays,
            'actual_balance' => $actual,
            'expected_balance' => $settlement['expected_balance'],
            'difference' => $difference,
            'classification' => $this->classify($contract, $settlement, $usedDays, $totalDays, $difference),
        ];
    }

    /**
     * الأيام المستهلكة تشمل يوم البداية ويوم الإغلاق، ولا تتجاوز مدة العقد.
     * أيام التأخير تُحسب بعد تاريخ الانتهاء.
     */
    private function days(RentalContract $contract, int $totalDays): array
    {
        $info = $contract->info;
        $start = $info?->ingaz_from
            ? Carbon::parse($info->ingaz_from)->startOfDay()
            : ($contract->contract_date ? Carbon::parse($contract->contract_date)->startOfDay() : null);
        $end = $info?->ingaz_to ? Carbon::parse($info->ingaz_to)->startOfDay() : null;
        $closure = $contract->closed_at
            ? Carbon::parse($contract->closed_at)->startOfDay()
            : Carbon::now()->startOfDay();

        $days = \App\Support\RentalContractDays::calculate($totalDays, $start, $end, $closure);

        return [$days['used_days'], $days['overdue_days']];
    }

    /**
     * ليس كل فرق خطأ. الإلغاء في اليوم الأول بإشعار دائن كامل قرار تجاري
     * محتمل ضمن فترة التجربة، والعقود المستوردة تحتاج مطابقة مع مستند المصدر.
     */
    private function classify(
        RentalContract $contract,
        array $settlement,
        int $usedDays,
        int $totalDays,
        float $difference
    ): string {
        if (abs($difference) < RentalClosureReconciler::TOLERANCE) {
            return 'ok';
        }

        if (!empty($contract->legacy_source)) {
            return 'legacy_manual_review';
        }

        $hasClosureDocument = $contract->financialAdjustments
            ->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction'])
            ->whereNotNull('constraint_id')
            ->isNotEmpty();

        if (!$hasClosureDocument) {
            return 'closure_document_missing';
        }

        // إعفاء الأيام الأولى: العقد أُلغي مبكراً جداً والفرق لا يتجاوز قيمتها
        if ($usedDays > 0 && $usedDays <= 2 && $totalDays > 0) {
            $earlyDaysValue = round($settlement['invoiced'] / $totalDays * $usedDays, 2);

            if (abs(abs($difference) - $earlyDaysValue) < RentalClosureReconciler::TOLERANCE) {
                return 'trial_period_waiver';
            }
        }

        return 'structural_error';
    }

    private function renderTable(array $rows): void
    {
        if ($rows === []) {
            $this->info('لا توجد عقود مطابِقة للمعايير المحددة.');

            return;
        }

        $this->table(
            ['العقد', 'الحالة', 'المفوتر', 'المدفوع', 'المستحَق', 'أيام', 'الرصيد الآن', 'الصحيح', 'الفرق', 'التصنيف'],
            array_map(fn (array $row) => [
                $row['contract_id'],
                $row['status'],
                number_format($row['invoiced'], 2),
                number_format($row['paid'], 2),
                number_format($row['earned'], 2),
                $row['used_days'] . '/' . $row['total_days'] . ($row['overdue_days'] > 0 ? '+' . $row['overdue_days'] : ''),
                number_format($row['actual_balance'], 2),
                number_format($row['expected_balance'], 2),
                number_format($row['difference'], 2),
                $this->classificationLabel($row['classification']),
            ], $rows)
        );
    }

    private function classificationSummary(array $issues): array
    {
        return collect($issues)
            ->groupBy('classification')
            ->map(fn ($group, $key) => [
                $this->classificationLabel($key),
                $group->count(),
                number_format($group->sum(fn (array $row) => abs($row['difference'])), 2),
            ])
            ->values()
            ->all();
    }

    private function classificationLabel(string $classification): string
    {
        return match ($classification) {
            'ok' => 'مطابق',
            'structural_error' => 'خطأ هيكلي',
            'closure_document_missing' => 'إشعار مفقود',
            'trial_period_waiver' => 'قرار تجاري؟',
            'legacy_manual_review' => 'مراجعة يدوية',
            default => $classification,
        };
    }

    /**
     * فحص تقسيم الضريبة على تسويات الإنهاء المرحَّلة مقابل تركيبة فاتورتها.
     */
    private function vatSection(): void
    {
        $rows = [];
        $understated = 0.0;

        $adjustments = \App\Models\RentalFinancialAdjustment::query()
            ->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction'])
            ->whereNotNull('constraint_id')
            ->orderBy('id')
            ->get();

        foreach ($adjustments as $adjustment) {
            $contract = RentalContract::query()
                ->with(['financeInvoice', 'user'])
                ->find($adjustment->rental_contract_id);
            $invoice = $contract?->financeInvoice;

            if (!$invoice || !$invoice->invoice_constraint_id) {
                continue;
            }

            $invoiceVat = (float) DB::table('fn_constraint_details')
                ->where('constraint_id', $invoice->invoice_constraint_id)
                ->whereIn('account_id', $this->revenueTaxAccountIds())
                ->whereNull('deleted_at')
                ->sum('creditor_value');
            $invoiceTotal = (float) DB::table('fn_constraint_details')
                ->where('constraint_id', $invoice->invoice_constraint_id)
                ->where('account_id', $contract->user?->account_id)
                ->whereNull('deleted_at')
                ->sum('debtor_value');

            if ($invoiceTotal <= 0 || $invoiceVat <= 0) {
                continue;
            }

            $composition = RentalClosureReconciler::compositionFromPostedVat($invoiceTotal, $invoiceVat);
            $expected = RentalClosureReconciler::splitByComposition(
                (float) $adjustment->amount,
                $composition['exempt_fraction']
            );

            $postedVat = (float) DB::table('fn_constraint_details')
                ->where('constraint_id', $adjustment->constraint_id)
                ->whereIn('account_id', $this->revenueTaxAccountIds())
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(ABS(debtor_value - creditor_value)), 0) AS vat')
                ->value('vat');
            $delta = round($expected['vat'] - (float) $postedVat, 2);

            if (abs($delta) < 0.02) {
                continue;
            }

            // الإشعار المدين يُقيّد الضريبة دائنة، والدائن يعكسها مدينة. النقص في
            // الالتزام في الحالتين هو الفارق عن التقسيم الصحيح.
            $understated += $adjustment->type === 'income' ? $delta : -$delta;

            $rows[] = [
                $adjustment->rental_contract_id,
                '#' . $adjustment->id,
                $adjustment->type === 'income' ? 'مدين' : 'دائن',
                number_format((float) $adjustment->amount, 2),
                number_format((float) $postedVat, 2),
                number_format($expected['vat'], 2),
                number_format($delta, 2),
            ];
        }

        if ($rows === []) {
            $this->info('تقسيم الضريبة على كل التسويات المرحَّلة مطابق لتركيبة فواتيرها.');

            return;
        }

        $this->line('فحص تقسيم الضريبة على تسويات الإنهاء المرحَّلة:');
        $this->table(
            ['العقد', 'التسوية', 'النوع', 'المبلغ', 'ضريبة مقيَّدة', 'ضريبة صحيحة', 'الفرق'],
            $rows
        );
        $this->warn(sprintf(
            'التزام ضريبة القيمة المضافة الناقص = %s ريال على %d تسوية.',
            number_format(abs($understated), 2),
            count($rows)
        ));
    }

    /**
     * فحص قيود تاريخية: كل حساب سبق ضبطه لضريبة الإيرادات، حتى لا تُقرأ ضريبة
     * مُرحَّلة على حساب قديم كأنها صفر بعد تغيير الإعداد.
     */
    private function revenueTaxAccountIds(): array
    {
        static $accountIds = null;

        return $accountIds ??= app(\Nami\Finance\Services\TaxAccountResolver::class)
            ->historicalAccountIds('revenue_tax_account');
    }

    private function exportCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error('تعذر فتح ملف التصدير: ' . $path);

            return;
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'contract_id', 'contract_number', 'status', 'invoiced', 'paid', 'earned',
            'used_days', 'overdue_days', 'total_days', 'actual_balance', 'expected_balance',
            'difference', 'classification',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }
}
