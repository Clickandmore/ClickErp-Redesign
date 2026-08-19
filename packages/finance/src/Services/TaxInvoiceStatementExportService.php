<?php

namespace Nami\Finance\Services;

use PDF as Pdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TaxInvoiceStatementExportService
{
    private const HEADERS = [
        'م',
        'رقم الفاتورة',
        'التاريخ',
        'المصدر',
        'النوع',
        'البائع',
        'الرقم الضريبي للبائع',
        'المبلغ شامل الضريبة',
        'المبلغ دون ضريبة',
        'ضريبة القيمة المضافة',
        'اسم العميل',
        'جوال العميل',
        'رقم العميل / العقد',
        'نوع المستند',
        'حالة ZATCA',
    ];

    public function download(
        string $format,
        Collection $rows,
        array $filters,
        string $supplierName,
        string $supplierTaxNumber
    ) {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'excel', 'xlsx'], true), 404);

        return $format === 'pdf'
            ? $this->pdf($rows, $filters, $supplierName, $supplierTaxNumber)
            : $this->excel($rows, $filters, $supplierName, $supplierTaxNumber);
    }

    private function excel(
        Collection $rows,
        array $filters,
        string $supplierName,
        string $supplierTaxNumber
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('الفواتير الضريبية');
        $sheet->setRightToLeft(true);
        $sheet->getSheetView()->setZoomScale(80);

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->setCellValue('A1', 'كشف حساب ضريبة الفواتير');
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->setCellValue('A2', $supplierName . ' | الرقم الضريبي: ' . $supplierTaxNumber);
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A3', $this->periodLabel($filters) . ' | تاريخ الإصدار: ' . now()->format('Y-m-d H:i'));
        $sheet->fromArray(self::HEADERS, null, 'A5');

        foreach ($rows->values() as $index => $row) {
            $excelRow = $index + 6;
            $sheet->fromArray([
                $index + 1,
                (string) $row['invoice_number'],
                $row['date'] ?: '-',
                $row['source'],
                $row['type'],
                $row['seller'],
                (string) $row['seller_tax_number'],
                (float) $row['total_with_tax'],
                (float) $row['total_without_tax'],
                (float) $row['vat_amount'],
                $row['client_name'],
                (string) $row['client_phone'],
                (string) ($row['reference_number'] ?: '-'),
                $row['document_type'],
                $row['zatca_status'],
            ], null, "A{$excelRow}");

            foreach (['B', 'G', 'L', 'M'] as $textColumn) {
                $sheet->getCell("{$textColumn}{$excelRow}")
                    ->setValueExplicit((string) $sheet->getCell("{$textColumn}{$excelRow}")->getValue(), DataType::TYPE_STRING);
            }
        }

        $totalRow = $rows->count() + 6;
        $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'الإجمالي');
        $sheet->setCellValue("H{$totalRow}", $rows->sum('total_with_tax'));
        $sheet->setCellValue("I{$totalRow}", $rows->sum('total_without_tax'));
        $sheet->setCellValue("J{$totalRow}", $rows->sum('vat_amount'));

        $sheet->getStyle("A1:{$lastColumn}3")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18)->getColor()->setRGB('315A63');
        $sheet->getStyle('A2:A3')->getFont()->setSize(11)->getColor()->setRGB('4B5563');
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '315A63']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(34);

        $sheet->getStyle("A6:{$lastColumn}{$totalRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D7E3E7']],
            ],
        ]);
        $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF3F5']],
        ]);
        $sheet->getStyle("H6:J{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->freezePane('A6');
        $sheet->setAutoFilter("A5:{$lastColumn}5");

        $widths = [7, 16, 20, 13, 28, 24, 22, 20, 20, 20, 24, 18, 20, 17, 20];
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'tax_invoice_statement_');
        (new Xlsx($spreadsheet))->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $tempFile,
            $this->filename('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function pdf(
        Collection $rows,
        array $filters,
        string $supplierName,
        string $supplierTaxNumber
    ) {
        $pdf = Pdf::loadView('finance::finance.tax_invoice_statement.export_pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'supplierName' => $supplierName,
            'supplierTaxNumber' => $supplierTaxNumber,
            'periodLabel' => $this->periodLabel($filters),
            'generatedAt' => now(),
        ], [], [
            'format' => 'A4-L',
            'mode' => 'utf-8',
        ]);

        return $pdf->download($this->filename('pdf'));
    }

    private function periodLabel(array $filters): string
    {
        $from = $filters['from_date'] ?? '';
        $to = $filters['to_date'] ?? '';

        if ($from && $to) {
            return "الفترة من {$from} إلى {$to}";
        }

        if ($from) {
            return "الفترة من {$from}";
        }

        return $to ? "الفترة حتى {$to}" : 'كل الفترات';
    }

    private function filename(string $extension): string
    {
        return 'tax-invoice-statement-' . now()->format('Y-m-d-His') . ".{$extension}";
    }
}
