<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * تصدير الملخص المالي التشغيلي إلى ملف واحد بخمس أوراق، بنفس أرقام الشاشة.
 */
class OperationalSummaryExportService
{
    private const HEADER_FILL = 'FF21685F';

    public function download(array $report)
    {
        return response()->streamDownload(function () use ($report) {
            $book = $this->workbook($report);
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'operational-financial-summary-' . $report['from'] . '-' . $report['to'] . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function workbook(array $report): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheets = [
            'المبيعات الشهرية' => $this->salesSheet($report),
            'ذمم التأجير' => $this->receivablesSheet($report['rental_receivables'], 'عملاء عقود التأجير غير المسددين', $report),
            'ذمم الاستقدام' => $this->receivablesSheet($report['recruitment_receivables'], 'فواتير الاستقدام غير المحصّلة', $report),
            'عمولات مساند' => $this->musanedSheet($report),
            'المكاتب الخارجية' => $this->officesSheet($report),
        ];

        $first = true;
        foreach ($sheets as $title => $rows) {
            $sheet = $first ? $book->getActiveSheet() : $book->createSheet();
            $first = false;
            $sheet->setTitle($title)->setRightToLeft(true);

            foreach ($rows as $i => $row) {
                foreach (array_values($row) as $j => $value) {
                    $sheet->setCellValueExplicit(
                        [$j + 1, $i + 1],
                        $value ?? '',
                        is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
                    );
                }
            }

            $last = $sheet->getHighestColumn();
            $sheet->getStyle('A2:' . $last . '2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A2:' . $last . '2')->getFill()->setFillType('solid')->getStartColor()->setARGB(self::HEADER_FILL);
            $sheet->freezePane('A3');

            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setWidth(22);
            }
        }

        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function salesSheet(array $report): array
    {
        $sales = $report['sales'];
        $rows = [
            ['الملخص المالي التشغيلي — المبيعات', $report['from'], $report['to']],
            ['الشهر', 'فواتير التأجير', 'عدد', 'فواتير الاستقدام', 'عدد', 'تسويات التأجير', 'عدد', 'إجمالي المبيعات', 'المحصّل', 'غير المحصّل'],
        ];

        foreach ($sales['months'] as $month) {
            $rows[] = [
                $month['key'], $month['rental'], $month['rental_count'],
                $month['recruitment'], $month['recruitment_count'],
                $month['adjustments'], $month['adjustments_count'],
                $month['invoiced'], $month['collected'], $month['outstanding'],
            ];
        }

        $totals = $sales['totals'];
        $rows[] = [
            'الإجمالي', $totals['rental'], $totals['rental_count'],
            $totals['recruitment'], $totals['recruitment_count'],
            $totals['adjustments'], $totals['adjustments_count'],
            $totals['invoiced'], $totals['collected'], $totals['outstanding'],
        ];

        return $rows;
    }

    private function receivablesSheet(array $section, string $title, array $report): array
    {
        $scope = ($report['scope'] ?? 'period') === 'period'
            ? 'فواتير الفترة ' . $report['from'] . ' — ' . $report['to']
            : 'كل رصيد قائم حتى ' . $report['to'];

        $rows = [
            [$title, $scope, 'المتبقي', $section['totals']['remaining'],
                ...(isset($section['outstanding_all']) ? ['إجمالي القائم بلا قيد فترة', $section['outstanding_all']] : [])],
            ['رقم العقد', 'العميل', 'الجوال', 'الهوية', 'تاريخ الفاتورة', 'عمر الدين (يوم)', 'قيمة الفاتورة', 'المدفوع', 'المتبقي', 'حالة السداد'],
        ];

        foreach ($section['rows'] as $row) {
            $rows[] = [
                (string) ($row->contract_number ?: '—'),
                (string) ($row->client_name ?: '—'),
                (string) ($row->client_phone ?: '—'),
                (string) ($row->client_identity ?: '—'),
                $row->invoiced_at ? Carbon::parse($row->invoiced_at)->toDateString() : '—',
                (int) ($row->age_days ?? 0),
                $row->total_due,
                $row->paid_amount,
                $row->remaining_amount,
                (string) ($row->payment_status ?? 'unpaid'),
            ];
        }

        $totals = $section['totals'];
        $rows[] = ['الإجمالي', (string) $totals['documents'] . ' فاتورة', '', '', '', '', $totals['invoiced'], $totals['paid'], $totals['remaining'], ''];

        return $rows;
    }

    private function musanedSheet(array $report): array
    {
        $musaned = $report['musaned'];
        $rows = [
            ['عمولات مساند', $report['from'], $report['to']],
            ['الشهر', 'عدد الحوالات', 'عدد العقود', 'دفع العميل', 'المحوّل إلينا', 'العمولة', 'ضريبة المصروف', 'صافي العمولة', 'نسبة العمولة %'],
        ];

        foreach ($musaned['months'] as $month) {
            $rows[] = [
                $month['key'], $month['transfers'], $month['contracts'], $month['customer_paid'],
                $month['transferred'], $month['commission'], $month['expense_tax'],
                $month['commission_net'], $month['commission_rate'] ?? 0,
            ];
        }

        $totals = $musaned['totals'];
        $rows[] = [
            'الإجمالي', $totals['transfers'], $totals['contracts'], $totals['customer_paid'],
            $totals['transferred'], $totals['commission'], $totals['expense_tax'],
            $totals['commission_net'], $totals['commission_rate'] ?? 0,
        ];
        $rows[] = ['حوالات بلا قيد بنكي مرحّل', $musaned['unposted']['transfers'], '', $musaned['unposted']['amount']];

        return $rows;
    }

    private function officesSheet(array $report): array
    {
        $offices = $report['external_offices'];
        $rows = [
            ['استحقاقات المكاتب الخارجية — رصيد الأستاذ حتى', $report['to']],
            ['كود الحساب', 'المكتب', 'الدولة', 'الحالة', 'مدين', 'دائن', 'المستحق للمكتب', 'مدفوع مقدماً', 'عقود الفترة', 'تكلفة الفترة (USD)', 'آخر إرسال'],
        ];

        foreach ($offices['rows'] as $row) {
            $rows[] = [
                (string) ($row->account_code ?: '—'),
                (string) $row->name,
                (string) ($row->country ?: '—'),
                (string) ($row->status ?: '—'),
                $row->debtor,
                $row->creditor,
                $row->payable,
                $row->prepaid,
                $row->period_contracts,
                $row->period_cost_usd,
                $row->last_dispatch_at ? Carbon::parse($row->last_dispatch_at)->toDateString() : '—',
            ];
        }

        $totals = $offices['totals'];
        $rows[] = [
            'الإجمالي', (string) $totals['offices'] . ' مكتب', '', '', '', '',
            $totals['payable'], $totals['prepaid'], $totals['period_contracts'], $totals['period_cost_usd'], '',
        ];

        return $rows;
    }
}
