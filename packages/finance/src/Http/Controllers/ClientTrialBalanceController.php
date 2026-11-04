<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Nami\Finance\Services\ClientTrialBalanceService;
use Nami\Finance\Services\YearService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** ميزان مراجعة العملاء. التصدير يستخدم فلاتر الشاشة نفسها. */
class ClientTrialBalanceController extends Controller
{
    private const PER_PAGE = [50, 100, 250, 500];

    public function index(Request $request, ClientTrialBalanceService $service, YearService $years)
    {
        $filters = $service->filters($request->all(), $years->getReportPeriod());
        $report = $service->report($filters);
        $perPage = in_array((int) $request->input('per_page'), self::PER_PAGE, true) ? (int) $request->input('per_page') : 100;
        $page = max(1, (int) $request->input('page', 1));
        $rows = new LengthAwarePaginator($report['rows']->forPage($page, $perPage)->values(), $report['count'], $perPage, $page, [
            'path' => $request->url(), 'query' => $request->except('page'),
        ]);

        return view('finance::finance.client_trial_balance.index', [
            'bladeTitle' => 'ميزان مراجعة العملاء',
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $report['totals'],
            'count' => $report['count'],
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE,
            'description' => $service->describe($filters),
        ]);
    }

    public function export(Request $request, ClientTrialBalanceService $service, YearService $years)
    {
        $filters = $service->filters($request->all(), $years->getReportPeriod());
        $report = $service->report($filters);

        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('ميزان مراجعة العملاء');
        $sheet->setRightToLeft(true);
        $sheet->setCellValue('A1', 'ميزان مراجعة العملاء');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $row = 2;
        foreach ($service->describe($filters) as $line) {
            $sheet->setCellValue('A' . $row++, $line);
        }
        $sheet->setCellValue('A' . $row++, 'عدد العملاء: ' . number_format($report['count']));
        $row++;

        $headers = ['#', 'الكود', 'العميل', 'أول المدة مدين', 'أول المدة دائن', 'حركة الفترة مدين', 'حركة الفترة دائن', 'الرصيد مدين', 'الرصيد دائن'];
        $headerRow = $row;
        $sheet->fromArray($headers, null, 'A' . $row++);
        $first = $row;
        foreach ($report['rows'] as $index => $item) {
            $sheet->fromArray([$index + 1, (string) $item->code, $item->name, $item->opening_debit, $item->opening_credit, $item->period_debit, $item->period_credit, $item->closing_debit, $item->closing_credit], null, 'A' . $row);
            $sheet->setCellValueExplicit('B' . $row, (string) $item->code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, (string) $item->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }
        $t = $report['totals'];
        $sheet->fromArray(['', '', 'الإجمالي', $t['opening_debit'], $t['opening_credit'], $t['period_debit'], $t['period_credit'], $t['closing_debit'], $t['closing_credit']], null, 'A' . $row);

        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F0EA');
        $sheet->getStyle("A{$row}:I{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$first}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A1:I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setAutoFilter("A{$headerRow}:I" . max($headerRow, $row - 1));
        $sheet->freezePane('A' . ($headerRow + 1));
        foreach (['A' => 6, 'B' => 14, 'C' => 42, 'D' => 16, 'E' => 16, 'F' => 16, 'G' => 16, 'H' => 16, 'I' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $name = 'client-trial-balance-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
