<?php

namespace Nami\Finance\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AccountStatementExportService
{
    public function download(
        Request $request,
        string $format,
        ?int $accountType = null,
        string $title = 'التصدير الشامل لكشوف الحسابات',
        string $filenamePrefix = 'account-statements'
    )
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'excel', 'xlsx'], true), 404);

        $filters = $this->filters($request);
        $movements = $this->query($filters, $accountType)->get();
        $balances = [];

        $rows = $movements->map(function ($movement) use (&$balances) {
            $accountId = (int) $movement->account_id;
            $balances[$accountId] = ($balances[$accountId] ?? 0.0)
                + (float) $movement->debtor_value
                - (float) $movement->creditor_value;

            return [
                'account_code' => (string) ($movement->account_code ?? ''),
                'account_name' => (string) ($movement->account_name ?? ''),
                'date' => (string) $movement->date,
                'constraint_code' => (string) ($movement->constraint_code ?? $movement->constraint_id),
                'statement' => (string) ($movement->statement ?? ''),
                'debtor' => (float) $movement->debtor_value,
                'creditor' => (float) $movement->creditor_value,
                'balance' => (float) $balances[$accountId],
                'cost_center' => (string) ($movement->cost_center_name ?? ''),
                'created_by' => (string) ($movement->created_by_name ?? ''),
            ];
        })->values()->all();

        return in_array($format, ['excel', 'xlsx'], true)
            ? $this->excel($rows, $filenamePrefix)
            : $this->printablePdf($rows, $filters, $title);
    }

    private function query(array $filters, ?int $accountType)
    {
        return DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.account_id',
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_constraint_details.constraint_id',
                'fn_accounts.code as account_code',
                'fn_accounts.name as account_name',
                'fn_constraints.code as constraint_code',
                'fn_cost_centers.title as cost_center_name',
                'admins.name as created_by_name',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->leftJoin('fn_cost_centers', 'fn_cost_centers.id', '=', 'fn_constraint_details.cost_center_id')
            ->leftJoin('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->when($accountType !== null, fn ($query) => $query->where('fn_accounts.type', $accountType))
            ->when($filters['account_id'] !== null, fn ($query) => $query->where('fn_constraint_details.account_id', $filters['account_id']))
            ->when($filters['from_date'] !== null, fn ($query) => $query->whereDate('fn_constraint_details.date', '>=', $filters['from_date']))
            ->when($filters['to_date'] !== null, fn ($query) => $query->whereDate('fn_constraint_details.date', '<=', $filters['to_date']))
            ->when($filters['type'] !== 'both', fn ($query) => $query->where('fn_constraint_details.type', $filters['type']))
            ->orderBy('fn_accounts.code')
            ->orderBy('fn_accounts.id')
            ->orderBy('fn_constraint_details.date')
            ->orderByRaw('COALESCE(fn_constraints.code, fn_constraints.id)')
            ->orderBy('fn_constraint_details.id');
    }

    private function filters(Request $request): array
    {
        $type = (string) $request->query('type', 'both');

        return [
            'account_id' => $request->filled('account_id') ? (int) $request->query('account_id') : null,
            'from_date' => $request->filled('from_date') ? (string) $request->query('from_date') : null,
            'to_date' => $request->filled('to_date') ? (string) $request->query('to_date') : now()->format('Y-m-d'),
            'type' => in_array($type, ['both', 'debtor', 'creditor'], true) ? $type : 'both',
        ];
    }

    private function excel(array $rows, string $filenamePrefix)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('account-statements');
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
        $sheet->setCellValue('E' . $totalRow, 'الإجمالي');
        $sheet->setCellValue('F' . $totalRow, array_sum(array_column($rows, 'debtor')));
        $sheet->setCellValue('G' . $totalRow, array_sum(array_column($rows, 'creditor')));
        $sheet->getStyle('E' . $totalRow . ':G' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('F2:H' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'account_statements_');
        $writer->save($tempFile);

        return response()->download(
            $tempFile,
            $this->filename($filenamePrefix, 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function printablePdf(array $rows, array $filters, string $title)
    {
        return response()->view('finance::finance.account_statement.export_pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'title' => $title,
            'totalDebtor' => array_sum(array_column($rows, 'debtor')),
            'totalCreditor' => array_sum(array_column($rows, 'creditor')),
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function headers(): array
    {
        return [
            'رمز الحساب', 'اسم الحساب', 'التاريخ', 'رقم القيد', 'البيان',
            'مدين', 'دائن', 'الرصيد خلال الفترة', 'مركز التكلفة', 'أنشأ بواسطة',
        ];
    }

    private function filename(string $prefix, string $extension): string
    {
        return $prefix . '-' . now()->format('Ymd_His') . '.' . $extension;
    }
}
