<?php

namespace App\Services\PaymentGateways;

use App\Services\Hourly\HourlyPdf;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;

/**
 * تصدير تقرير بوابة دفع كإكسل (ورقة للملخص ثم ورقة لكل جدول) أو PDF (الملخص والجدول الأول).
 *
 * @phpstan-type Sheet array{name: string, columns: array<int, string>, rows: array<int, array<int, mixed>>, money?: array<int, int>}
 * @phpstan-type Document array{title: string, period: string, filters: array<string, string>, summary: array<int, array{0: string, 1: string}>, sheets: array<int, Sheet>}
 */
class ReportExporter
{
    private const BRAND = ['moyasar' => '4338CA'];

    /** @param Document $document */
    public function xlsx(array $document, string $gateway, string $filename): Response
    {
        $rtl = app()->getLocale() === 'ar';
        $brand = self::BRAND[$gateway] ?? '334155';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($document['title'])->setCreator(config('app.name'));

        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle($this->sheetTitle(__('payment_gateways.export.summary_sheet')));
        $summary->setRightToLeft($rtl);
        $summary->setCellValue('A1', $document['title']);
        $summary->mergeCells('A1:B1');
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB($brand);
        $line = 2;
        foreach ([__('payment_gateways.period.title') => $document['period']] + $document['filters'] + [__('payment_gateways.export.generated_at') => now()->format('Y-m-d H:i')] as $label => $value) {
            $summary->setCellValue('A' . $line, $label);
            $summary->setCellValueExplicit('B' . $line, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $summary->getStyle('A' . $line)->getFont()->setBold(true)->getColor()->setRGB('64748B');
            $line++;
        }
        $line++;
        $this->header($summary, ['A', 'B'], $line, [__('payment_gateways.export.indicator'), __('payment_gateways.export.value')], $brand);
        foreach ($document['summary'] as [$label, $value]) {
            $line++;
            $summary->setCellValue('A' . $line, $label);
            $summary->setCellValueExplicit('B' . $line, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $summary->getColumnDimension('A')->setWidth(38);
        $summary->getColumnDimension('B')->setWidth(28);

        foreach ($document['sheets'] as $sheetData) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($this->sheetTitle($sheetData['name']));
            $sheet->setRightToLeft($rtl);
            $columns = array_map(fn ($i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1), array_keys($sheetData['columns']));
            $this->header($sheet, $columns, 1, $sheetData['columns'], $brand);
            $money = $sheetData['money'] ?? [];
            foreach ($sheetData['rows'] as $r => $row) {
                foreach (array_values($row) as $c => $value) {
                    $cell = $columns[$c] . ($r + 2);
                    if (in_array($c, $money, true) && is_numeric($value)) {
                        $sheet->setCellValue($cell, (float) $value);
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        // نص صريح: أرقام الفواتير والهويات والجوالات لا تتحول إلى أرقام علمية.
                        $sheet->setCellValueExplicit($cell, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
            }
            foreach ($columns as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheet->freezePane('A2');
            if ($sheetData['rows']) {
                $sheet->setAutoFilter('A1:' . end($columns) . (count($sheetData['rows']) + 1));
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'pgx');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** @param Document $document */
    public function pdf(array $document, string $gateway, string $filename): Response
    {
        $html = view('admin.payment_gateways.export_pdf', [
            'document' => $document,
            'brand' => '#' . (self::BRAND[$gateway] ?? '334155'),
            'rtl' => app()->getLocale() === 'ar',
        ])->render();

        $config = HourlyPdf::mpdfConfig();
        $config['format'] = 'A4-L';
        $config['orientation'] = 'L';
        $config['directionality'] = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
        foreach (['margin_left', 'margin_right', 'margin_top', 'margin_bottom'] as $margin) {
            $config[$margin] = 10;
        }
        $config['margin_footer'] = 5;

        $mpdf = new Mpdf($config);
        $mpdf->SetTitle($document['title']);
        // تذييل بخط عادي: SetFooter يستخدم المائل افتراضياً فيولّد ملفات خط إضافية.
        $mpdf->SetHTMLFooter('<div style="text-align:center;font-size:8pt;color:#64748b">{PAGENO} / {nbpg}</div>');
        // الجداول الطويلة: تكرار رأس الجدول في كل صفحة.
        $mpdf->simpleTables = false;
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
        ]);
    }

    private function header(Worksheet $sheet, array $columns, int $row, array $labels, string $brand): void
    {
        foreach (array_values($labels) as $i => $label) {
            $sheet->setCellValue($columns[$i] . $row, $label);
        }
        $range = $columns[0] . $row . ':' . end($columns) . $row;
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($brand);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    /** أسماء أوراق إكسل: 31 حرفاً بلا الرموز الممنوعة. */
    private function sheetTitle(string $name): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name), 0, 31);
    }
}
