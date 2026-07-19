<?php

namespace Nami\Finance\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ComprehensiveTableExportService
{
    public function download(
        string $format,
        string $title,
        string $filenamePrefix,
        array $headers,
        array $rows,
        array $numericColumns = []
    ) {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'excel', 'xlsx'], true), 404);

        if ($format === 'pdf') {
            return response()->view('finance::finance.exports.table_print', compact(
                'title', 'headers', 'rows', 'numericColumns'
            ), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('export');
        $sheet->setRightToLeft(true);
        $sheet->fromArray($headers, null, 'A1');

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F3F1'],
            ],
        ]);

        foreach ($numericColumns as $columnIndex) {
            $column = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->getStyle($column . '2:' . $column . max(2, count($rows) + 1))
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'finance_export_');
        (new Xlsx($spreadsheet))->save($tempFile);

        return response()->download(
            $tempFile,
            $filenamePrefix . '-' . now()->format('Ymd_His') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }
}
