<?php

namespace App\Console\Commands;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class ExportRecruitmentContractInvoices extends Command
{
    protected $signature = 'recruitment-contracts:export-invoices
        {--export : إنشاء ملفات PDF وExcel وZIP؛ بدون هذا الخيار يعرض الأمر معاينة فقط}
        {--output= : اسم مجلد التصدير داخل storage/app/exports فقط}';

    protected $description = 'معاينة أو تصدير الفواتير الموجودة لعقود الاستقدام دون إنشاء فواتير أو تعديل البيانات المالية';

    private string $exportsRoot;

    public function handle(InvoiceController $invoiceController): int
    {
        $this->exportsRoot = storage_path('app/exports');

        try {
            $outputName = $this->resolveOutputName($this->option('output'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        }

        $willExport = (bool) $this->option('export');
        $finalDirectory = $this->exportsRoot . DIRECTORY_SEPARATOR . $outputName;
        $finalZip = $finalDirectory . '.zip';

        if ($willExport && (file_exists($finalDirectory) || file_exists($finalZip))) {
            $this->error('مسار التصدير موجود مسبقاً ولن يتم استبداله: ' . $outputName);

            return self::FAILURE;
        }

        $contracts = $this->contracts();
        $summary = $this->summary($contracts);
        $this->displaySummary($summary);

        if (!$willExport) {
            $this->newLine();
            $this->warn('وضع المعاينة: لم يتم إنشاء ملفات ولم تُعدّل قاعدة البيانات.');
            $this->line('للتصدير بعد مراجعة الأعداد:');
            $this->line('php artisan recruitment-contracts:export-invoices --export --output=' . $outputName);

            return self::SUCCESS;
        }

        if (!is_dir($this->exportsRoot) && !mkdir($this->exportsRoot, 0775, true) && !is_dir($this->exportsRoot)) {
            $this->error('تعذر إنشاء مجلد التصدير الآمن: ' . $this->exportsRoot);

            return self::FAILURE;
        }

        $lockHandle = fopen($this->exportsRoot . '/.recruitment-invoices-export.lock', 'c');
        if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
            if (is_resource($lockHandle)) {
                fclose($lockHandle);
            }
            $this->error('يوجد تصدير آخر قيد التنفيذ. حاول مرة أخرى بعد اكتماله.');

            return self::FAILURE;
        }

        $stagingName = '.tmp-' . $outputName . '-' . bin2hex(random_bytes(4));
        $stagingDirectory = $this->exportsRoot . DIRECTORY_SEPARATOR . $stagingName;
        $stagingZip = $this->exportsRoot . DIRECTORY_SEPARATOR . $stagingName . '.zip';

        $directoryPublished = false;
        $zipPublished = false;

        try {
            $result = $this->export(
                $contracts,
                $summary,
                $invoiceController,
                $stagingDirectory,
                $stagingZip,
                $outputName
            );

            if (!rename($stagingDirectory, $finalDirectory)) {
                throw new RuntimeException('تعذر تثبيت مجلد التصدير النهائي.');
            }
            $directoryPublished = true;
            if (!rename($stagingZip, $finalZip)) {
                throw new RuntimeException('تعذر تثبيت ملف ZIP النهائي.');
            }
            $zipPublished = true;

            $this->newLine();
            $this->info('اكتمل التصدير دون تعديل قاعدة البيانات.');
            $this->line('المجلد: ' . $finalDirectory);
            $this->line('الحزمة: ' . $finalZip);
            $this->line('التقرير: ' . $finalDirectory . '/' . $result['report_name']);
            $this->line('ملفات PDF: ' . $result['pdf_exported']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->removeDirectory($stagingDirectory);
            if (is_file($stagingZip)) {
                @unlink($stagingZip);
            }
            if ($directoryPublished) {
                $this->removeDirectory($finalDirectory);
            }
            if ($zipPublished && is_file($finalZip)) {
                @unlink($finalZip);
            }
            $this->error('فشل التصدير ولم يتم نشر ملفات جزئية: ' . $exception->getMessage());

            return self::FAILURE;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    private function contracts(): Collection
    {
        return Contract::query()
            ->whereIn('type', ['recruitment', 'maid'])
            ->with([
                'user',
                'maid.nationality',
                'maid.office',
                'requiredMaidNationality',
                'info',
                'invoice.financeSale.zatca',
                'branch',
            ])
            ->orderBy('id')
            ->get();
    }

    private function summary(Collection $contracts): array
    {
        $withInvoice = $contracts->filter(fn (Contract $contract) => (bool) $contract->invoice);

        return [
            'contracts' => $contracts->count(),
            'with_invoice' => $withInvoice->count(),
            'without_invoice' => $contracts->count() - $withInvoice->count(),
            'invoice_total' => round((float) $withInvoice->sum(fn (Contract $contract) => $contract->invoice->total_due), 2),
            'vat_total' => round((float) $withInvoice->sum(fn (Contract $contract) => $contract->invoice->vat_amount), 2),
        ];
    }

    private function displaySummary(array $summary): void
    {
        $this->table(
            ['البيان', 'القيمة'],
            [
                ['إجمالي عقود الاستقدام', $summary['contracts']],
                ['عقود لديها فاتورة', $summary['with_invoice']],
                ['عقود بلا فاتورة', $summary['without_invoice']],
                ['إجمالي مبالغ الفواتير', number_format($summary['invoice_total'], 2)],
                ['إجمالي الضريبة', number_format($summary['vat_total'], 2)],
            ]
        );
    }

    private function export(
        Collection $contracts,
        array $summary,
        InvoiceController $invoiceController,
        string $outputDirectory,
        string $zipPath,
        string $outputName
    ): array {
        $pdfDirectory = $outputDirectory . '/invoices';
        $pdfTempDirectory = sys_get_temp_dir() . '/clickerp-' . $outputName . '-' . bin2hex(random_bytes(4));

        foreach ([$pdfDirectory, $pdfTempDirectory] as $directory) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('تعذر إنشاء مجلد التصدير: ' . $directory);
            }
        }

        $spreadsheet = new Spreadsheet();
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('الملخص')->setRightToLeft(true);
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('تفاصيل العقود')->setRightToLeft(true);
        $this->prepareDetailsSheet($detailsSheet);

        $pdfExported = 0;
        $pdfErrors = [];
        $statusSummary = [];
        $rowNumber = 2;

        try {
            foreach ($contracts as $contract) {
                $invoice = $contract->invoice;
                $statusLabel = $this->statusLabel((string) ($contract->status ?: 'غير محدد'));
                $statusSummary[$statusLabel] ??= ['contracts' => 0, 'invoiced' => 0, 'total' => 0.0];
                $statusSummary[$statusLabel]['contracts']++;

                $invoiceFileName = '';
                $note = 'لا توجد فاتورة مرتبطة بالعقد.';

                if ($invoice) {
                    $statusSummary[$statusLabel]['invoiced']++;
                    $statusSummary[$statusLabel]['total'] += (float) $invoice->total_due;
                    $invoiceNumber = $invoice->legacy_invoice_number ?: $invoice->id;
                    $invoiceFileName = sprintf('contract-%d_invoice-%s.pdf', $contract->id, $invoiceNumber);

                    try {
                        $viewData = $invoiceController->prepareInvoiceViewData((int) $invoice->id);
                        $this->writeArabicPdf(
                            view('admin.ingaz.invoices.invoice', $viewData)->render(),
                            $pdfDirectory . '/' . $invoiceFileName,
                            $pdfTempDirectory
                        );
                        if (!is_file($pdfDirectory . '/' . $invoiceFileName)
                            || filesize($pdfDirectory . '/' . $invoiceFileName) < 1024) {
                            throw new RuntimeException('ملف PDF الناتج غير صالح أو فارغ.');
                        }
                        $pdfExported++;
                        $note = 'تم تصدير الفاتورة بنجاح.';
                    } catch (Throwable $exception) {
                        $pdfErrors[] = [
                            'contract_id' => (int) $contract->id,
                            'invoice_id' => (int) $invoice->id,
                            'message' => $exception->getMessage(),
                        ];
                        $invoiceFileName = '';
                        $note = 'تعذر إنشاء ملف PDF: ' . $exception->getMessage();
                    }
                }

                $this->appendContractRow(
                    $detailsSheet,
                    $rowNumber++,
                    $contract,
                    $statusLabel,
                    $invoiceFileName,
                    $note
                );
            }

            if ($pdfErrors !== []) {
                throw new RuntimeException(
                    'تعذر تصدير ' . count($pdfErrors) . ' فاتورة PDF؛ أُلغي نشر الحزمة الجزئية.'
                );
            }

            $this->finishDetailsSheet($detailsSheet, $rowNumber);
            $this->writeSummarySheet($summarySheet, $summary, $statusSummary, $pdfExported, count($pdfErrors));

            $reportName = 'recruitment-contract-invoices-report.xlsx';
            (new Xlsx($spreadsheet))->save($outputDirectory . '/' . $reportName);

            file_put_contents($outputDirectory . '/README.txt', implode(PHP_EOL, [
                'تصدير فواتير عقود الاستقدام',
                'تاريخ التصدير: ' . now()->format('Y-m-d H:i:s'),
                'إجمالي العقود: ' . $summary['contracts'],
                'العقود التي لديها فاتورة: ' . $summary['with_invoice'],
                'العقود بلا فاتورة: ' . $summary['without_invoice'],
                'ملفات PDF المصدرة: ' . $pdfExported,
                'أخطاء PDF: ' . count($pdfErrors),
                '',
                'هذا التصدير للقراءة فقط ولم ينشئ فواتير أو قيوداً مالية.',
            ]));

            $this->createZip($outputDirectory, $zipPath, $outputName);

            return [
                'report_name' => $reportName,
                'pdf_exported' => $pdfExported,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
            $this->removeDirectory($pdfTempDirectory);
        }
    }

    private function prepareDetailsSheet($sheet): void
    {
        $sheet->fromArray([
            'رقم العقد',
            'نوع العقد',
            'حالة العقد',
            'تاريخ العقد',
            'الفرع',
            'اسم العميل',
            'جوال العميل',
            'اسم العاملة',
            'رقم الجواز',
            'الجنسية',
            'المكتب الخارجي',
            'حالة الفاتورة',
            'رقم الفاتورة',
            'تاريخ الفاتورة',
            'المجموع قبل الضريبة',
            'الضريبة',
            'الخصم',
            'إجمالي الفاتورة',
            'حالة السداد',
            'حالة هيئة الزكاة',
            'اسم ملف الفاتورة',
            'ملاحظة',
        ], null, 'A1');
        $sheet->getStyle('A1:V1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:V1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $sheet->getStyle('A:V')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:V1');
    }

    private function appendContractRow(
        $sheet,
        int $rowNumber,
        Contract $contract,
        string $statusLabel,
        string $invoiceFileName,
        string $note
    ): void {
        $invoice = $contract->invoice;
        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
        $contractDate = $contract->info?->contract_date
            ?: ($contract->contract_date ?: optional($contract->created_at)->format('Y-m-d'));

        $zatcaStatus = '-';
        if ($invoice?->legacy_zatca_shared) {
            $zatcaStatus = 'مشارك سابقاً مع الهيئة';
        } elseif ($invoice?->send_to_zatca) {
            $zatcaStatus = $invoice->financeSale?->zatca?->zatca_status
                ?: ($invoice->finance_sale_id ? 'معلق في المبيعات' : 'لم يدرج في المبيعات');
        } elseif ($invoice) {
            $zatcaStatus = 'غير مطلوب';
        }

        $sheet->fromArray([
            (string) $contract->id,
            (string) ($contract->type ?: '-'),
            $statusLabel,
            (string) ($contractDate ?: '-'),
            (string) ($contract->branch?->name ?: '-'),
            (string) ($contract->user?->name ?: '-'),
            (string) ($contract->user?->phone ?: '-'),
            (string) ($contract->maid?->name ?: '-'),
            (string) ($contract->maid?->passport_num ?: '-'),
            (string) ($nationality?->sub_title ?: ($nationality?->title ?: '-')),
            (string) ($contract->maid?->office?->title ?: '-'),
            $invoice ? 'موجودة' : 'غير موجودة',
            $invoice ? (string) ($invoice->legacy_invoice_number ?: $invoice->id) : '-',
            $invoice?->created_at?->format('Y-m-d H:i') ?: '-',
            $invoice ? (float) $invoice->subtotal : 0.0,
            $invoice ? (float) $invoice->vat_amount : 0.0,
            $invoice ? (float) $invoice->discount : 0.0,
            $invoice ? (float) $invoice->total_due : 0.0,
            $invoice ? ($invoice->is_paid ? 'مسددة' : 'غير مسددة') : '-',
            $zatcaStatus,
            $invoiceFileName ?: '-',
            $note,
        ], null, 'A' . $rowNumber);

        foreach (['A', 'G', 'I', 'M'] as $column) {
            $sheet->setCellValueExplicit(
                $column . $rowNumber,
                (string) $sheet->getCell($column . $rowNumber)->getValue(),
                DataType::TYPE_STRING
            );
        }
    }

    private function finishDetailsSheet($sheet, int $nextRow): void
    {
        $lastRow = max(2, $nextRow - 1);
        $sheet->getStyle('O2:R' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'V') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function writeSummarySheet(
        $sheet,
        array $summary,
        array $statusSummary,
        int $pdfExported,
        int $pdfErrorCount
    ): void {
        $sheet->fromArray([
            ['تقرير تصدير فواتير عقود الاستقدام', ''],
            ['تاريخ التصدير', now()->format('Y-m-d H:i:s')],
            ['إجمالي العقود', $summary['contracts']],
            ['عقود لديها فاتورة', $summary['with_invoice']],
            ['عقود بلا فاتورة', $summary['without_invoice']],
            ['ملفات PDF تم تصديرها', $pdfExported],
            ['أخطاء تصدير PDF', $pdfErrorCount],
            ['إجمالي مبالغ الفواتير', $summary['invoice_total']],
            ['إجمالي ضريبة القيمة المضافة', $summary['vat_total']],
            [],
            ['الحالة', 'عدد العقود', 'بفاتورة', 'بلا فاتورة', 'إجمالي الفواتير'],
        ], null, 'A1');

        $row = 12;
        foreach ($statusSummary as $label => $values) {
            $sheet->fromArray([
                $label,
                $values['contracts'],
                $values['invoiced'],
                $values['contracts'] - $values['invoiced'],
                round($values['total'], 2),
            ], null, 'A' . $row++);
        }

        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $sheet->getStyle('A11:E11')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A11:E11')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
        $sheet->getStyle('B8:B9')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('E12:E' . max(12, $row - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function writeArabicPdf(string $html, string $path, string $tempDirectory): void
    {
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => $tempDirectory,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'default_font' => 'dejavusans',
            'directionality' => 'rtl',
        ]);
        $pdf->SetDirectionality('rtl');
        $pdf->WriteHTML($html);
        $pdf->Output($path, 'F');
    }

    private function createZip(string $directory, string $zipPath, string $rootName): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('تعذر إنشاء ملف ZIP.');
        }

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen($directory) + 1);
                if (!$zip->addFile($file->getPathname(), $rootName . '/' . $relative)) {
                    throw new RuntimeException('تعذر إضافة ملف إلى ZIP: ' . $relative);
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function resolveOutputName($requested): string
    {
        $name = trim((string) $requested);
        if ($name === '') {
            return 'recruitment-invoices-' . now()->format('Y-m-d_His');
        }

        if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,79}\z/', $name) || str_contains($name, '..')) {
            throw new RuntimeException(
                'اسم المخرج غير آمن. استخدم حروفاً إنجليزية وأرقاماً وشرطة فقط، دون مسارات أو "..".'
            );
        }

        return $name;
    }

    private function statusLabel(string $status): string
    {
        return function_exists('getContractStatus') ? getContractStatus($status) : $status;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($directory);
    }
}
