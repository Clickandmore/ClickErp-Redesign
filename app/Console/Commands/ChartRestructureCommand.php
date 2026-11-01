<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nami\Finance\Services\ChartRestructureService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * يطبّق خطة ترتيب دليل محفوظة في packages/finance/database/chart_plans.
 * بلا --execute: معاينة تنفّذ الخطة كاملة ثم تلغيها. خذ نسخة احتياطية وأوقف الإدخال قبل --execute.
 * يكتب ملفي report.xlsx وreport.html في storage/app/chart-restructure.
 */
class ChartRestructureCommand extends Command
{
    protected $signature = 'finance:chart-restructure
        {--plan= : اسم ملف الخطة بلا امتداد}
        {--execute : تنفيذ وحفظ؛ بدونه معاينة بلا حفظ}
        {--force : السماح بتنفيذ خطة نُفّذت من قبل}';

    protected $description = 'ترتيب شجرة الحسابات حسب خطة محفوظة (معاينة أو تنفيذ) للشركة الحالية';

    public function handle(ChartRestructureService $service): int
    {
        $name = (string) $this->option('plan');
        $path = base_path("packages/finance/database/chart_plans/{$name}.php");
        if ($name === '' || !is_file($path)) {
            $this->error('الخطة غير موجودة: ' . $path);
            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        try {
            $report = $service->run(require $path, $name, $execute, (bool) $this->option('force'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->error('لم يُحفظ أي تعديل.');
            return self::FAILURE;
        }

        $this->table(['البند', 'العدد'], collect($report['summary'])->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->table(['الحساب الرئيسي', 'قبل', 'بعد'], collect($report['before']['roots'])->map(fn ($r, $id) => [$report['after']['roots'][$id]['name'], $r['balance'], $report['after']['roots'][$id]['balance']])->values()->all());
        foreach ($report['warnings'] as $warning) {
            $this->warn($warning);
        }

        $directory = storage_path('app/chart-restructure/' . now()->format('Ymd-His') . ($execute ? '-execute' : '-dry-run'));
        @mkdir($directory, 0775, true);
        $this->writeWorkbook($report, "{$directory}/report.xlsx");
        file_put_contents("{$directory}/report.html", $this->html($report));
        $this->info('التقرير: ' . $directory);
        $execute ? $this->info('تم التنفيذ والحفظ. أعد تشغيل queue workers ونفّذ optimize:clear.') : $this->warn('معاينة فقط؛ لم يُحفظ شيء.');

        return self::SUCCESS;
    }

    private function writeWorkbook(array $report, string $path): void
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('الشجرة بعد الترتيب');
        $sheet->setRightToLeft(true);
        $sheet->fromArray(['الاسم', 'الكود الحالي', 'الكود الجديد', 'النوع المعروض', 'النوع في البرنامج', 'المستوى', 'الحساب الرئيسي', 'الحساب الرئيسي السابق', 'الاسم السابق', 'الإجراء', 'دُمج في'], null, 'A1');
        $rows = array_map(fn ($r) => [$r['name'], $r['old_code'], $r['new_code'], $r['display_type'], $r['type'], $r['level'], $r['new_parent'], $r['old_parent'], $r['old_name'] !== $r['name'] ? $r['old_name'] : '', $r['action'], $r['into']], $report['rows']);
        $sheet->fromArray($rows, null, 'A2', true);
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:K' . (count($rows) + 1));
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setWidth(in_array($column, ['A', 'G', 'H', 'I'], true) ? 36 : 16);
        }
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
    }

    private function html(array $report): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $labels = ['accounts_before' => 'الحسابات قبل', 'accounts_after' => 'الحسابات بعد', 'created' => 'إنشاء', 'renamed' => 'إعادة تسمية', 'moved' => 'نقل', 'recoded' => 'تغيّر كوده', 'retyped' => 'تغيّر نوعه',
            'merged' => 'دمج', 'deleted' => 'حذف', 'aliases' => 'أكواد قديمة مربوطة', 'lines' => 'سطور القيود', 'debit' => 'مجموع المدين', 'credit' => 'مجموع الدائن'];
        $kpis = '';
        foreach ($report['summary'] as $key => $value) {
            $kpis .= '<div class="kpi"><b>' . $e(is_numeric($value) ? number_format((float) $value, in_array($key, ['debit', 'credit'], true) ? 2 : 0) : $value) . '</b><span>' . $e($labels[$key] ?? $key) . '</span></div>';
        }
        $roots = '';
        foreach ($report['before']['roots'] as $id => $root) {
            $after = $report['after']['roots'][$id];
            $difference = bcsub($after['balance'], $root['balance'], 2);
            $roots .= '<tr><td>' . $e($after['name']) . '</td><td class="n">' . number_format((float) $root['balance'], 2) . '</td><td class="n">' . number_format((float) $after['balance'], 2) . '</td><td class="n">' . (bccomp($difference, '0', 2) ? number_format((float) $difference, 2) : '—') . '</td></tr>';
        }
        $merged = '';
        foreach ($report['changes']['merged'] as $m) {
            $moved = $m['moved'] ? implode('، ', array_map(fn ($k, $v) => "$k ($v)", array_keys($m['moved']), $m['moved'])) : 'لا قيود ولا ارتباطات';
            $merged .= '<tr><td>' . $e($m['code'] . ' ' . $m['name']) . '</td><td>' . $e($m['into_code'] . ' ' . $m['into_name']) . '</td><td>' . $e($moved) . '</td></tr>';
        }
        $deleted = implode('، ', array_map(fn ($d) => $e($d['code'] . ' ' . $d['name']), $report['changes']['deleted']));
        $tree = '';
        foreach ($report['rows'] as $r) {
            if ($r['new_code'] !== '' && (int) $r['level'] <= 3) {
                $tree .= '<tr><td>' . $e($r['old_code']) . '</td><td><b>' . $e($r['new_code']) . '</b></td><td style="padding-right:' . (((int) $r['level'] - 1) * 14 + 6) . 'px">' . $e($r['name']) . '</td><td>' . $e($r['display_type']) . '</td><td>' . $e($r['action']) . '</td></tr>';
            }
        }
        $warnings = implode('', array_map(fn ($w) => '<li>' . $e($w) . '</li>', $report['warnings']));
        $state = $report['executed'] ? 'تم التنفيذ' : 'معاينة: لم يُحفظ أي تعديل';

        return '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>ترتيب شجرة الحسابات</title><style>@page{size:A4;margin:14mm 12mm}body{font-family:"Segoe UI",Tahoma,Arial,sans-serif;font-size:10.3pt;line-height:1.6;color:#1c2321}h1{color:#1f4e46;font-size:20pt;margin:0}h2{color:#1f4e46;font-size:13pt;border-bottom:2px solid #1f4e46;padding-bottom:3px;margin-top:20px;page-break-after:avoid}.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:12px 0}.kpi{border:1px solid #d9e0dd;border-radius:6px;padding:7px 10px}.kpi b{display:block;font-size:14pt}.kpi span{color:#5f6b67;font-size:9pt}table{width:100%;border-collapse:collapse;font-size:9pt}th{background:#1f4e46;color:#fff;text-align:right;padding:4px 6px}thead{display:table-header-group}td{border-bottom:1px solid #d9e0dd;padding:4px 6px}tr{page-break-inside:avoid}.n{direction:ltr;text-align:left;white-space:nowrap}</style></head><body>'
            . '<h1>ترتيب شجرة الحسابات: ' . $e($report['plan']) . '</h1><p>' . $e($state) . ' · ' . $e(now()->format('Y-m-d H:i')) . '. نتيجة الفحص: مجموع القيود ورصيد كل حساب لم يتغيرا. الأرصدة أدناه للقيود المعتمدة وغير المحذوفة.</p>'
            . '<div class="kpis">' . $kpis . '</div><h2>ميزان الحسابات الرئيسية</h2><table><thead><tr><th>الحساب</th><th>قبل</th><th>بعد</th><th>الفرق</th></tr></thead><tbody>' . $roots . '</tbody></table>'
            . ($warnings ? '<h2>تنبيهات</h2><ul>' . $warnings . '</ul>' : '')
            . '<h2>الحسابات المدموجة</h2><table><thead><tr><th>الحساب</th><th>دُمج في</th><th>ما انتقل</th></tr></thead><tbody>' . $merged . '</tbody></table>'
            . '<h2>الحسابات المحذوفة</h2><p>' . $deleted . '</p>'
            . '<h2>الشجرة حتى المستوى الثالث</h2><p>القائمة الكاملة في report.xlsx.</p><table><thead><tr><th>الكود الحالي</th><th>الكود الجديد</th><th>الاسم</th><th>النوع المعروض</th><th>الإجراء</th></tr></thead><tbody>' . $tree . '</tbody></table></body></html>';
    }
}
