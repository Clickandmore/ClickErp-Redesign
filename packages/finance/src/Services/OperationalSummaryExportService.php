<?php

namespace Nami\Finance\Services;

use App\Services\BranchAccessService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * تصدير الملخص المالي التشغيلي إلى ملف واحد بخمس أوراق، بنفس أرقام الشاشة.
 *
 * كل ورقة تُبنى من وصف واحد (عنوان، سطر نطاق، رؤوس، صفوف، سطر إجمالي، أنواع
 * الأعمدة)، فالتنسيق — فواصل الآلاف وعرض العمود والمحاذاة — يُشتق من نوع العمود
 * بدل تكراره يدوياً في كل ورقة.
 */
class OperationalSummaryExportService
{
    private const HEADER_FILL = 'FF21685F';
    private const TOTAL_FILL = 'FFEDF4F7';

    private const MONEY_FORMAT = '#,##0.00;[Red]-#,##0.00';
    private const INT_FORMAT = '#,##0';
    private const PERCENT_FORMAT = '0.00"%"';

    /** عرض العمود ومحاذاته وتنسيق رقمه حسب نوعه. */
    private const COLUMN_TYPES = [
        'text' => ['width' => 30, 'format' => null, 'align' => null],
        'name' => ['width' => 34, 'format' => null, 'align' => null],
        'code' => ['width' => 15, 'format' => null, 'align' => Alignment::HORIZONTAL_CENTER],
        'date' => ['width' => 13, 'format' => null, 'align' => Alignment::HORIZONTAL_CENTER],
        'money' => ['width' => 16, 'format' => self::MONEY_FORMAT, 'align' => Alignment::HORIZONTAL_RIGHT],
        'int' => ['width' => 10, 'format' => self::INT_FORMAT, 'align' => Alignment::HORIZONTAL_CENTER],
        'percent' => ['width' => 12, 'format' => self::PERCENT_FORMAT, 'align' => Alignment::HORIZONTAL_CENTER],
    ];

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
            $this->salesSheet($report),
            $this->receivablesSheet($report['rental_receivables'], 'ذمم التأجير', 'عملاء عقود التأجير — مفوترون ولم يسددوا', $report, true),
            $this->receivablesSheet($report['recruitment_receivables'], 'ذمم الاستقدام', 'فواتير الاستقدام غير المحصّلة', $report, false),
            $this->receivablesSheet($report['sponsorship_receivables'], 'ذمم نقل الكفالة', 'فواتير عقود نقل الكفالة غير المحصّلة', $report, true),
            $this->delegationsSheet($report),
            $this->musanedSheet($report),
            $this->officesSheet($report),
        ];

        foreach ($sheets as $index => $spec) {
            $this->writeSheet($index === 0 ? $book->getActiveSheet() : $book->createSheet(), $spec);
        }

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /* ------------------------------------------------------------------ */

    /**
     * ورقة واحدة: سطر عنوان، سطر نطاق، رأس، صفوف، سطر إجمالي، ثم ملاحظات.
     *
     * @param  array{name: string, title: string, caption: string, headers: array<int, string>,
     *               types: array<int, string>, rows: array<int, array<int, mixed>>,
     *               total?: array<int, mixed>|null, notes?: array<int, string>}  $spec
     */
    private function writeSheet(Worksheet $sheet, array $spec): void
    {
        $sheet->setTitle($spec['name'])->setRightToLeft(true);

        $lastColumn = Coordinate::stringFromColumnIndex(count($spec['headers']));

        $sheet->setCellValue('A1', $spec['title']);
        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A2', $spec['caption'] . $this->branchCaption());
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF667585');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $headerRow = 3;
        foreach ($spec['headers'] as $i => $header) {
            $sheet->setCellValueExplicit([$i + 1, $headerRow], $header, DataType::TYPE_STRING);
        }

        $rowNumber = $headerRow;
        foreach ($spec['rows'] as $row) {
            $this->writeRow($sheet, ++$rowNumber, $row, $spec['types']);
        }

        $firstDataRow = $headerRow + 1;
        $hasData = $rowNumber >= $firstDataRow;

        if (!empty($spec['total'])) {
            $this->writeRow($sheet, ++$rowNumber, $spec['total'], $spec['types']);
            $totalRange = "A{$rowNumber}:{$lastColumn}{$rowNumber}";
            $sheet->getStyle($totalRange)->getFont()->setBold(true);
            $sheet->getStyle($totalRange)->getFill()->setFillType('solid')->getStartColor()->setARGB(self::TOTAL_FILL);
            $sheet->getStyle($totalRange)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        }

        $lastFormattedRow = $rowNumber;

        foreach ($spec['notes'] ?? [] as $note) {
            $rowNumber += 2;
            $sheet->setCellValueExplicit([1, $rowNumber], $note, DataType::TYPE_STRING);
            $sheet->getStyle("A{$rowNumber}")->getFont()->setItalic(true)->setSize(9);
        }

        $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType('solid')->getStartColor()->setARGB(self::HEADER_FILL);
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(30);
        $sheet->freezePane("A{$firstDataRow}");

        if ($hasData) {
            $sheet->setAutoFilter($headerRange);
        }

        foreach ($spec['types'] as $i => $type) {
            $meta = self::COLUMN_TYPES[$type] ?? self::COLUMN_TYPES['text'];
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($letter)->setWidth($meta['width']);

            if (!$hasData) {
                continue;
            }

            $range = "{$letter}{$firstDataRow}:{$letter}{$lastFormattedRow}";

            if ($meta['format']) {
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode($meta['format']);
            }

            if ($meta['align']) {
                $sheet->getStyle($range)->getAlignment()->setHorizontal($meta['align']);
            }
        }

        // ورقة عريضة تُطبع بعرض صفحة واحدة، ورأس الجدول يتكرر على كل ورقة.
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<int, string>  $types
     */
    /**
     * الفرع الذي تغطيه أرقام الملف، يُلحق بسطر الوصف في كل ورقة.
     *
     * ملف Excel يعيش بعد الشاشة التي خرج منها، فإن لم يحمل اسم الفرع صار
     * رقم فرع واحد غير مميَّز عن رقم الشركة كلها.
     */
    private function branchCaption(): string
    {
        $label = app(BranchAccessService::class)->reportBranchLabel();

        return $label ? ' — الفرع: ' . $label : '';
    }

    private function writeRow(Worksheet $sheet, int $rowNumber, array $row, array $types): void
    {
        foreach (array_values($row) as $i => $value) {
            $numeric = in_array($types[$i] ?? 'text', ['money', 'int', 'percent'], true) && is_numeric($value);

            $sheet->setCellValueExplicit(
                [$i + 1, $rowNumber],
                $numeric ? $value + 0 : (string) ($value ?? ''),
                $numeric ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
            );
        }
    }

    /* ------------------------------------------------------------------ */

    private function salesSheet(array $report): array
    {
        $sales = $report['sales'];
        $rows = [];

        foreach ($sales['months'] as $month) {
            $rows[] = [
                $month['label'], $month['rental'], $month['rental_count'],
                $month['recruitment'], $month['recruitment_count'],
                $month['delegations'], $month['delegations_count'],
                $month['sponsorship'], $month['sponsorship_count'],
                $month['adjustments'], $month['adjustments_count'],
                $month['invoiced'], $month['collected'], $month['outstanding'],
            ];
        }

        $t = $sales['totals'];

        return [
            'name' => 'المبيعات الشهرية',
            'title' => 'إجمالي المبيعات الشهرية',
            'caption' => 'قيمة الفواتير الصادرة من ' . $report['from'] . ' إلى ' . $report['to'] . ' — ليست قيود الإيراد المحاسبية.',
            'headers' => ['الشهر', 'فواتير التأجير', 'عدد', 'فواتير الاستقدام', 'عدد', 'فواتير التفاويض', 'عدد', 'فواتير نقل الكفالة', 'عدد', 'تسويات التأجير', 'عدد', 'إجمالي المبيعات', 'المحصّل', 'غير المحصّل'],
            'types' => ['code', 'money', 'int', 'money', 'int', 'money', 'int', 'money', 'int', 'money', 'int', 'money', 'money', 'money'],
            'rows' => $rows,
            'total' => [
                'الإجمالي', $t['rental'], $t['rental_count'], $t['recruitment'], $t['recruitment_count'],
                $t['delegations'], $t['delegations_count'], $t['sponsorship'], $t['sponsorship_count'],
                $t['adjustments'], $t['adjustments_count'],
                $t['invoiced'], $t['collected'], $t['outstanding'],
            ],
        ];
    }

    private function receivablesSheet(array $section, string $name, string $title, array $report, bool $showPaid): array
    {
        $t = $section['totals'];
        $rows = [];

        foreach ($section['rows'] as $row) {
            $line = [
                (string) ($row->contract_number ?: '—')
                    . (isset($row->document_type) ? ' / ' . ($row->document_type === 'extension' ? 'تمديد' : 'فاتورة أساسية') . ' #' . $row->id : ''),
                (string) ($row->client_name ?: '—'),
                (string) ($row->client_phone ?: '—'),
                (string) ($row->client_identity ?: '—'),
                $row->invoiced_at ? Carbon::parse($row->invoiced_at)->toDateString() : '—',
                (int) ($row->age_days ?? 0),
                $row->total_due,
            ];

            if ($showPaid) {
                $line[] = $row->paid_amount;
            }

            $line[] = $row->remaining_amount;
            $line[] = (string) ($row->payment_status ?? 'unpaid');
            $rows[] = $line;
        }

        $headers = ['رقم العقد', 'العميل', 'الجوال', 'الهوية', 'تاريخ الفاتورة', 'عمر الدين (يوم)', 'قيمة الفاتورة'];
        $types = ['code', 'name', 'code', 'code', 'date', 'int', 'money'];

        if ($showPaid) {
            $headers[] = 'المدفوع';
            $types[] = 'money';
        }

        $headers[] = 'المتبقي';
        $types[] = 'money';
        $headers[] = 'حالة السداد';
        $types[] = 'code';

        $total = ['الإجمالي', '', '', '', '', '', $t['invoiced']];

        if ($showPaid) {
            $total[] = $t['paid'];
        }

        $total[] = $t['remaining'];
        $total[] = '';

        $notes = [
            'عدد العملاء: ' . $t['clients'] . ' — أقدم دين: ' . $t['oldest_days'] . ' يوم.',
            'التقادم: ' . $this->agingSummary($section['aging']),
        ];

        if (isset($section['outstanding_all'])) {
            $notes[] = 'إجمالي الرصيد القائم حتى ' . $report['to'] . ' بلا قيد فترة: '
                . number_format($section['outstanding_all'], 2) . ' ر.س.';
        }

        return [
            'name' => $name,
            'title' => $title,
            'caption' => $this->scopeCaption($report) . ' — ' . $t['documents'] . ' فاتورة، المتبقي '
                . number_format($t['remaining'], 2) . ' ر.س.',
            'headers' => $headers,
            'types' => $types,
            'rows' => $rows,
            'total' => $rows === [] ? null : $total,
            'notes' => $notes,
        ];
    }

    /**
     * @param  array<string, float>  $aging
     */
    private function agingSummary(array $aging): string
    {
        $labels = ['d30' => 'حتى 30 يوم', 'd60' => '31–60 يوم', 'd90' => '61–90 يوم', 'over' => 'أكثر من 90 يوم', 'unknown' => 'بلا تاريخ'];
        $parts = [];

        foreach ($labels as $key => $label) {
            if (($aging[$key] ?? 0) > .005) {
                $parts[] = $label . ': ' . number_format($aging[$key], 2);
            }
        }

        return $parts === [] ? 'لا يوجد' : implode(' — ', $parts);
    }

    private function scopeCaption(array $report): string
    {
        return ($report['scope'] ?? 'period') === 'period'
            ? 'فواتير الفترة ' . $report['from'] . ' — ' . $report['to']
            : 'كل رصيد قائم حتى ' . $report['to'];
    }

    private function delegationsSheet(array $report): array
    {
        $delegations = $report['delegations'];
        $t = $delegations['totals'];
        $rows = [];

        foreach ($delegations['months'] as $month) {
            $rows[] = [
                $month['label'], $month['delegations'], $month['revenue'], $month['collected'],
                $month['outstanding'], $month['cost'], $month['net'], $month['average_revenue'],
            ];
        }

        $rows[] = ['', '', '', '', '', '', '', ''];
        $rows[] = ['التوزيع على المهن', 'عدد التفاويض', 'الإيراد', '', '', 'التكلفة المعتمدة', 'الصافي', 'متوسط الإيراد'];

        foreach ($delegations['professions'] as $profession) {
            $rows[] = [
                $profession->profession, $profession->delegations, $profession->revenue, '', '',
                $profession->cost, $profession->net, $profession->average_revenue,
            ];
        }

        $notes = ['هامش التفاويض: ' . ($t['margin_rate'] === null ? '—' : $t['margin_rate'] . '%')];

        if ($delegations['pending_cost']['entries'] > 0) {
            $notes[] = 'قيود تكلفة تفاويض غير معتمدة: ' . $delegations['pending_cost']['entries']
                . ' بقيمة ' . number_format($delegations['pending_cost']['amount'], 2)
                . ' ر.س — الصافي أعلاه يحتسب المعتمد وحده فيبدو أعلى من حقيقته.';
        }

        return [
            'name' => 'التفاويض الرجالية',
            'title' => 'إيرادات التفاويض الرجالية',
            'caption' => 'تفاويض الإنجاز الإلكترونية من ' . $report['from'] . ' إلى ' . $report['to'],
            'headers' => ['الشهر', 'عدد التفاويض', 'الإيراد', 'المحصّل', 'غير المحصّل', 'التكلفة المعتمدة', 'الصافي', 'متوسط الإيراد'],
            'types' => ['code', 'int', 'money', 'money', 'money', 'money', 'money', 'money'],
            'rows' => $rows,
            'total' => [
                'الإجمالي', $t['delegations'], $t['revenue'], $t['collected'],
                $t['outstanding'], $t['cost'], $t['net'], $t['average_revenue'],
            ],
            'notes' => $notes,
        ];
    }

    private function musanedSheet(array $report): array
    {
        $musaned = $report['musaned'];
        $rows = [];

        foreach ($musaned['months'] as $month) {
            $rows[] = [
                $month['label'], $month['transfers'], $month['contracts'], $month['customer_paid'],
                $month['transferred'], $month['commission'], $month['expense_tax'],
                $month['commission_net'], $month['commission_rate'] ?? 0,
            ];
        }

        $t = $musaned['totals'];

        return [
            'name' => 'عمولات مساند',
            'title' => 'عمولات مساند',
            'caption' => 'حوالات مساند من ' . $report['from'] . ' إلى ' . $report['to'],
            'headers' => ['الشهر', 'عدد الحوالات', 'عدد العقود', 'دفع العميل', 'المحوّل إلينا', 'العمولة', 'ضريبة المصروف', 'صافي العمولة', 'نسبة العمولة'],
            'types' => ['code', 'int', 'int', 'money', 'money', 'money', 'money', 'money', 'percent'],
            'rows' => $rows,
            'total' => [
                'الإجمالي', $t['transfers'], $t['contracts'], $t['customer_paid'], $t['transferred'],
                $t['commission'], $t['expense_tax'], $t['commission_net'], $t['commission_rate'] ?? 0,
            ],
            'notes' => [
                'حوالات لم يُرحَّل قيدها البنكي بعد: ' . $musaned['unposted']['transfers']
                    . ' بقيمة ' . number_format($musaned['unposted']['amount'], 2) . ' ر.س.',
            ],
        ];
    }

    private function officesSheet(array $report): array
    {
        $offices = $report['external_offices'];
        $rows = [];

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

        $t = $offices['totals'];

        return [
            'name' => 'المكاتب الخارجية',
            'title' => 'استحقاقات المكاتب الخارجية',
            'caption' => 'رصيد حساب كل مكتب في الأستاذ حتى ' . $report['to']
                . ' (دائن − مدين، القيود المعتمدة فقط) — ونشاط الفترة ' . $report['from'] . ' — ' . $report['to'],
            'headers' => ['كود الحساب', 'المكتب', 'الدولة', 'الحالة', 'مدين', 'دائن', 'المستحق للمكتب', 'مدفوع مقدماً', 'عقود الفترة', 'تكلفة الفترة (USD)', 'آخر إرسال'],
            'types' => ['code', 'name', 'code', 'code', 'money', 'money', 'money', 'money', 'int', 'money', 'date'],
            'rows' => $rows,
            'total' => $rows === [] ? null : [
                'الإجمالي', $t['offices'] . ' مكتب', '', '', '', '',
                $t['payable'], $t['prepaid'], $t['period_contracts'], $t['period_cost_usd'], '',
            ],
            'notes' => $offices['unlinked_offices'] > 0
                ? ['مكاتب بلا حساب مالي مرتبط: ' . $offices['unlinked_offices'] . ' — لا يظهر لها رصيد.']
                : [],
        ];
    }
}
