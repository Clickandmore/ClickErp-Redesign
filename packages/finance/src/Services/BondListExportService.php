<?php

namespace Nami\Finance\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Nami\Finance\Enums\BondTypes;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BondListExportService
{
    public function download(Request $request, BondService $service, BondTypes $type, string $format)
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'excel', 'xlsx'], true), 404);

        $query = $service->filterBonds(
            $request,
            $service->getDataTableData($type->value)
        );

        $bonds = $query->reorder()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $rows = $bonds->map(fn ($bond) => [
            'code' => (string) $bond->code,
            'book_number' => (string) ($bond->bond_book_num ?? ''),
            'date' => (string) $bond->date,
            'debit_account' => (string) ($bond->debitAccount?->name ?? ''),
            'credit_account' => (string) ($bond->creditAccount?->name ?? ''),
            'payment_type' => (string) getEnumValue('bond_payment_type', $bond->payment_type),
            'value' => (float) $bond->value,
            'status' => $bond->is_confirm ? 'معتمد' : 'بانتظار الاعتماد',
            'constraint' => $bond->constraint_id ? (string) $bond->constraint_id : '',
            'statement' => (string) ($bond->statement ?? ''),
            'cost_center' => (string) ($bond->costCenter?->title ?? ''),
            'created_by' => (string) ($bond->createdBy?->name ?? ''),
        ])->values()->all();

        return in_array($format, ['excel', 'xlsx'], true)
            ? $this->excel($rows, $type)
            : $this->pdf($rows, $type, $request);
    }

    private function excel(array $rows, BondTypes $type)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($type === BondTypes::Qabd ? 'receipt-bonds' : 'payment-bonds');
        $sheet->setRightToLeft(true);
        $headers = $this->headers();
        $sheet->fromArray($headers, null, 'A1');

        $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F3F1'],
            ],
        ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray(array_values($row), null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $totalRow = $rowNumber;
        $sheet->setCellValue('F' . $totalRow, 'الإجمالي');
        $sheet->setCellValue('G' . $totalRow, array_sum(array_column($rows, 'value')));
        $sheet->getStyle('F' . $totalRow . ':G' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('G2:G' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'finance_bonds_');
        $writer->save($tempFile);

        return response()->download(
            $tempFile,
            $this->filename($type, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function pdf(array $rows, BondTypes $type, Request $request)
    {
        $pdf = Pdf::loadView('finance::finance.bond.export_pdf', [
            'rows' => $rows,
            'title' => $type === BondTypes::Qabd ? 'سندات القبض' : 'سندات الصرف',
            'total' => array_sum(array_column($rows, 'value')),
            'filters' => $request->only([
                'bond_search', 'from_date', 'to_date', 'cost_center_id',
                'debit_account_id', 'credit_account_id', 'payment_type', 'is_confirm',
            ]),
        ])->setPaper('a4', 'landscape');

        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download($this->filename($type, 'pdf'));
    }

    private function headers(): array
    {
        return [
            'رقم السند',
            'رقم الدفتر',
            'التاريخ',
            'الحساب المدين',
            'الحساب الدائن',
            'طريقة الدفع',
            'القيمة',
            'الحالة',
            'رقم القيد',
            'البيان',
            'مركز التكلفة',
            'أنشأ بواسطة',
        ];
    }

    private function filename(BondTypes $type, string $extension): string
    {
        $name = $type === BondTypes::Qabd ? 'receipt-bonds' : 'payment-bonds';

        return $name . '-' . now()->format('Ymd_His') . '.' . $extension;
    }
}
