<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use App\Support\ConstraintReferenceGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;

class ResetAlrafedainDelegationCosts extends Command
{
    protected $signature = 'finance:reset-alrafedain-delegation-costs
        {--expected-database= : Exact database name}
        {--local-copy : Allow rafdain_test only on a loopback database host}
        {--apply : Soft-delete verified legacy costs and clear their invoice links}';

    protected $description = 'معاينة أو حذف تكاليف التفويض القديمة للرافدين مع تقرير CSV ونسخة JSON قبل الحذف';

    public function handle(ConstraintService $service): int
    {
        $database = DB::connection()->getDatabaseName();
        if (!self::targetAllowed($database, (string) DB::connection()->getConfig('host'),
            (string) $this->option('expected-database'), (bool) $this->option('local-copy'))) {
            $this->error('حدد قاعدة الرافدين في --expected-database؛ النسخة rafdain_test تتطلب --local-copy واتصالًا محليًا.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $directory = storage_path('app/delegation-cost-reset');
        $base = $directory.'/'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4));
        $report = ['database' => $database, 'mode' => $apply ? 'apply' : 'preview',
            'status' => 'planned', 'created_at' => now()->toIso8601String(), 'rows' => []];
        try {
            if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
                throw new \RuntimeException('تعذر إنشاء مجلد التقرير.');
            }
            DB::transaction(function () use ($service, $apply, &$report, $base) {
                // Lock invoice owners first, matching the new posting workflow.
                $invoices = Invoice::withoutGlobalScopes()->whereNotNull('ingaz_delegation_id')
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $linkedIds = $invoices->pluck('ingaz_delegation_cost_constraint_id')->filter()->unique();
                $journals = Constraint::withoutGlobalScopes()->without('admin')
                    ->whereNull('deleted_at')->where(function ($q) use ($linkedIds) {
                        $q->whereIn('id', $linkedIds)->orWhere('statement', 'like', 'قيد تكلفة تفويض إنجاز #%');
                    })->orderBy('id')->lockForUpdate()->get();
                ConstraintReferenceGuard::flushSchemaCache();
                $references = ConstraintReferenceGuard::referencesForMany($journals->modelKeys());
                foreach ($journals as $journal) {
                    $lines = DB::table('fn_constraint_details')->where('constraint_id', $journal->id)
                        ->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get();
                    preg_match('/^قيد تكلفة تفويض إنجاز #(\d+) \/ الفاتورة #(\d+) \/ إنجاز .+$/u', (string) $journal->statement, $matches);
                    $invoice = isset($matches[2]) ? $invoices->get((int) $matches[2]) : null;
                    $reason = self::skipReason($journal->getAttributes(), $invoice?->getAttributes(),
                        $lines->map(fn ($line) => (array) $line)->all(), $references[$journal->id] ?? []);
                    if ($reason === null && !DB::table('ingaz_delegations')->where('id', $invoice->ingaz_delegation_id)->exists()) {
                        $reason = 'التفويض الأصلي غير موجود';
                    }
                    $report['rows'][] = [
                        'constraint_id' => $journal->id, 'constraint_code' => $journal->code,
                        'date' => $journal->getRawOriginal('date'), 'amount' => $journal->value,
                        'delegation_id' => $invoice?->ingaz_delegation_id,
                        'invoice_id' => $invoice?->id, 'branch_id' => $journal->branch_id,
                        'statement' => $journal->statement,
                        'action' => $reason === null ? 'delete' : 'skip', 'reason' => $reason,
                        'journal_before' => $journal->getAttributes(),
                        'details_before' => $lines->all(),
                        'invoice_link_before' => $invoice?->ingaz_delegation_cost_constraint_id,
                    ];
                }
                // The immutable backup is durable before the first database mutation.
                $this->writeJson($base.'-before.json', $report);
                $this->writeCsv($base.'-preview.csv', $report['rows'], 'معاينة');
                if (!$apply) {
                    return;
                }
                foreach ($report['rows'] as $row) {
                    if ($row['action'] !== 'delete') {
                        continue;
                    }
                    $service->deleteConstraint((int) $row['constraint_id'], true);
                    Invoice::withoutGlobalScopes()->whereKey($row['invoice_id'])
                        ->where('ingaz_delegation_cost_constraint_id', $row['constraint_id'])
                        ->update(['ingaz_delegation_cost_constraint_id' => null]);
                }
            });
        } catch (\Throwable $exception) {
            $report['status'] = 'failed_no_changes';
            $report['error'] = $exception->getMessage();
            if (is_dir($directory)) {
                try { $this->writeJson($base.'-failed.json', $report); } catch (\Throwable $ignored) {}
            }
            $this->error('لم يُعتمد أي حذف؛ تراجعت العملية: '.$exception->getMessage());
            $this->line('مسار التقرير: '.$base);
            return self::FAILURE;
        }

        $report['status'] = $apply ? 'committed' : 'preview';
        $eligible = collect($report['rows'])->where('action', 'delete');
        $report['summary'] = ['eligible_count' => $eligible->count(),
            'eligible_amount' => round((float) $eligible->sum('amount'), 2),
            'deleted_count' => $apply ? $eligible->count() : 0,
            'skipped_count' => count($report['rows']) - $eligible->count()];
        try {
            $this->writeJson($base.'-result.json', $report);
            $this->writeCsv($base.'-result.csv', $report['rows'], $apply ? 'تم الحذف' : 'معاينة');
        } catch (\Throwable $exception) {
            $this->error(($apply ? 'تم اعتماد الحذف، لكن تعذر حفظ التقرير النهائي: ' : 'تعذر حفظ التقرير النهائي: ').$exception->getMessage());
            $this->line('نسخة ما قبل التنفيذ: '.$base.'-before.json');
            return self::FAILURE;
        }
        $this->table(['الوضع', 'عدد القيود المؤهلة', 'إجمالي التكلفة', 'المستبعد'], [[
            $apply ? 'تم الحذف المنطقي' : 'معاينة دون حذف', $eligible->count(),
            number_format($report['summary']['eligible_amount'], 2), $report['summary']['skipped_count'],
        ]]);
        $this->info('التقرير: '.$base.'-result.csv');
        $this->info('التفاصيل والنتيجة: '.$base.'-result.json');
        $this->info('النسخة السابقة للحذف: '.$base.'-before.json');
        return self::SUCCESS;
    }

    public static function targetAllowed(string $database, string $host, string $expected, bool $local): bool
    {
        if ($expected !== $database) {
            return false;
        }
        return $local
            ? $database === 'rafdain_test' && in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
            : $database === 'alrafedain_dashboard';
    }

    /** Only the exact old automatic-cost statement is eligible, never new confirmed costs. */
    public static function skipReason(array $journal, ?array $invoice, array $lines, array $references): ?string
    {
        if (!preg_match('/^قيد تكلفة تفويض إنجاز #(\d+) \/ الفاتورة #(\d+) \/ إنجاز .+$/u', (string) $journal['statement'], $matches)) {
            return 'ليس قيد تكلفة تلقائيًا قديمًا؛ قد يكون تكلفة مؤكدة بالطريقة الجديدة';
        }
        if (!$invoice || (int) $invoice['id'] !== (int) $matches[2]
            || (int) $invoice['ingaz_delegation_id'] !== (int) $matches[1]) {
            return 'الفاتورة أو التفويض لا يطابقان بيان القيد';
        }
        if (!empty($invoice['ingaz_delegation_cost_constraint_id'])
            && (int) $invoice['ingaz_delegation_cost_constraint_id'] !== (int) $journal['id']) {
            return 'الفاتورة مرتبطة بقيد تكلفة آخر؛ تحتاج مراجعة';
        }
        foreach ($references as $reference) {
            if ($reference['table'] !== 'invoices' || $reference['column'] !== 'ingaz_delegation_cost_constraint_id'
                || (int) $reference['id'] !== (int) $invoice['id']) {
                return 'القيد مرتبط بمستند أو حقل مالي آخر';
            }
        }
        $debit = round(array_sum(array_column($lines, 'debtor_value')), 2);
        $credit = round(array_sum(array_column($lines, 'creditor_value')), 2);
        if (count($lines) !== 2 || $debit <= 0 || abs($debit - $credit) > 0.001
            || abs($debit - (float) $journal['value']) > 0.001
            || count(array_unique(array_column($lines, 'account_id'))) !== 2) {
            return 'تفاصيل القيد لا تطابق قيد تكلفة قديم متوازن من طرفين';
        }
        foreach ($lines as $line) {
            if ((float) $line['debtor_value'] < 0 || (float) $line['creditor_value'] < 0
                || (((float) $line['debtor_value'] > 0) === ((float) $line['creditor_value'] > 0))) {
                return 'أطراف القيد غير مطابقة للتكلفة القديمة';
            }
        }
        return null;
    }

    private function writeJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path, $json, LOCK_EX) !== strlen($json)) {
            throw new \RuntimeException('تعذر حفظ التقرير: '.$path);
        }
        chmod($path, 0600);
    }

    private function writeCsv(string $path, array $rows, string $status): void
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['الحالة', 'رقم القيد', 'كود القيد', 'التاريخ', 'التفويض', 'الفاتورة', 'الفرع', 'المبلغ', 'البيان', 'سبب الاستبعاد']);
        foreach ($rows as $row) {
            $values = [$row['action'] === 'skip' ? 'مستبعد' : $status, $row['constraint_id'], $row['constraint_code'],
                $row['date'], $row['delegation_id'], $row['invoice_id'], $row['branch_id'], $row['amount'], $row['statement'], $row['reason']];
            $values = array_map(fn ($value) => preg_match('/^[=+@\-\t\r]/', (string) $value) ? "'".$value : $value, $values);
            fputcsv($stream, $values);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        if (file_put_contents($path, $csv, LOCK_EX) !== strlen($csv)) {
            throw new \RuntimeException('تعذر حفظ التقرير: '.$path);
        }
        chmod($path, 0600);
    }
}
