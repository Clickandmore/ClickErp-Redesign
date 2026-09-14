<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkLegacyInvoiceConstraints extends Command
{
    protected $signature = 'invoices:link-legacy-constraints {--apply} {--expected-database=}';
    protected $description = 'مطابقة وربط الفواتير المنقولة بالقيود الأصلية المؤكدة، مع تقرير ونسخة القيم السابقة';

    public function handle(LegacyInvoiceConstraintLinker $linker): int
    {
        $database = DB::connection()->getDatabaseName();
        if ($this->option('apply') && $this->option('expected-database') !== $database) {
            $this->error('حدد قاعدة البيانات المقصودة باستخدام --expected-database.');
            return self::FAILURE;
        }
        $directory = storage_path('app/legacy-invoice-links');
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $path = $directory . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.jsonl';
        $file = fopen($path, 'x');
        $counts = [];
        $candidatesByNumber = [];
        foreach (DB::table('fn_constraints')->whereNull('deleted_at')->where(function ($query) {
            $query->whereNotNull('legacy_invoice_number')->orWhere('statement', 'like', 'فاتورة:%');
        })->get() as $source) {
            $numbers = [];
            if ($source->legacy_invoice_number) {
                $numbers[] = (int) $source->legacy_invoice_number;
            }
            if (preg_match('/^فاتورة: (\d+)$/u', $source->statement, $match)) {
                $numbers[] = (int) $match[1];
            }
            foreach (array_unique($numbers) as $number) {
                $candidatesByNumber[$number][] = $source;
            }
        }
        try {
            Invoice::withoutGlobalScopes()->whereNotNull('legacy_invoice_number')->orderBy('id')
                ->chunkById(200, function ($invoices) use ($linker, $file, &$counts, $database, $candidatesByNumber) {
                    foreach ($invoices as $invoice) {
                        $result = $linker->inspect($invoice, collect($candidatesByNumber[(int) $invoice->legacy_invoice_number] ?? []));
                        $record = ['database' => $database, 'invoice_id' => $invoice->id,
                            'legacy_invoice_number' => $invoice->legacy_invoice_number, 'result' => $result,
                            'before' => $invoice->only(['invoice_constraint_id', 'revenue_recognition_mode', 'updated_at'])];
                        if (fwrite($file, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n") === false || !fflush($file)) {
                            throw new \RuntimeException('تعذر حفظ نسخة ما قبل التعديل.');
                        }
                        if ($this->option('apply') && in_array($result['status'], ['ready', 'linked'], true)) {
                            $linker->link($invoice);
                        }
                        $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;
                    }
                });
        } finally {
            fclose($file);
        }
        $this->line(json_encode(['database' => $database, 'applied' => (bool) $this->option('apply'), 'counts' => $counts, 'report' => $path], JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
