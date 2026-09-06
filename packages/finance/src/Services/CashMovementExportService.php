<?php

namespace Nami\Finance\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CashMovementExportService
{
    public function workbook(array $report): Spreadsheet
    {
        $book = new Spreadsheet;
        $summary = [['تقرير حركة الصندوق والبنوك', $report['from'], $report['to']],
            ['الكود', 'الحساب', 'الرصيد الافتتاحي', 'مدين', 'دائن', 'الرصيد الختامي']];
        foreach ($report['accounts'] as $row) {
            $summary[] = [(string) $row['account']->code, $row['account']->name, $row['opening']/100, $row['receipts']/100, $row['payments']/100, $row['closing']/100];
        }
        $summary[] = ['', 'الإجمالي', $report['opening']/100, $report['receipts']/100, $report['payments']/100, $report['closing']/100];
        $summary[] = ['قيود غير معتمدة مستبعدة حتى نهاية الفترة', $report['pending']];
        $summary[] = ['تشمل الحركة التحويلات والتسويات والقيود الافتتاحية؛ وليست قائمة إيرادات ومصروفات.'];
        $daily = [['الحركة اليومية', $report['from'], $report['to']], ['التاريخ', 'افتتاحي', 'مدين', 'دائن', 'صافي الحركة', 'ختامي']];
        foreach ($report['days'] as $row) $daily[] = [$row['date'], $row['opening']/100, $row['receipts']/100, $row['payments']/100, ($row['receipts']-$row['payments'])/100, $row['closing']/100];
        $details = [['تفاصيل الحركة كاملة', $report['from'], $report['to']], ['التاريخ', 'رقم القيد', 'معرف القيد', 'كود الحساب', 'الحساب', 'البيان', 'مدين', 'دائن', 'رصيد الحساب', 'رصيد النطاق']];
        foreach ($report['movements'] as $row) $details[] = [$row['date'], (string) ($row['code'] ?: $row['constraint_id']), (string) $row['constraint_id'], (string) $row['account']->code, $row['account']->name, $row['statement'] ?: $row['entry_statement'], $row['receipts']/100, $row['payments']/100, $row['account_balance']/100, $row['balance']/100];
        foreach (['ملخص الحسابات'=>$summary, 'الحركة اليومية'=>$daily, 'تفاصيل القيود'=>$details] as $title=>$rows) {
            $sheet = $title === 'ملخص الحسابات' ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle($title)->setRightToLeft(true)->freezePane('A3');
            foreach ($rows as $i=>$row) foreach ($row as $j=>$value) {
                $sheet->setCellValueExplicit([$j+1, $i+1], $value ?? '', is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
            $last = $sheet->getHighestColumn();
            $sheet->getStyle('A2:'.$last.'2')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle('A2:'.$last.'2')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF21685F');
            $sheet->getStyle('A3:'.$last.$sheet->getHighestRow())->getNumberFormat()->setFormatCode('#,##0.00;[Red](#,##0.00)');
            foreach ($sheet->getColumnIterator() as $column) $sheet->getColumnDimension($column->getColumnIndex())->setWidth(24);
            $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
        }
        $book->setActiveSheetIndex(0);
        return $book;
    }

    public function referenceWorkbook(array $report): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('المقبوضات والمصروفات')->setRightToLeft(true);
        $sheet->setCellValue('A1', 'تقرير حركة صندوق اليومية');
        $sheet->setCellValue('A2', ($report['from'] ?: 'جميع السجلات').' — '.$report['to']);
        $sheet->setCellValue('A3', $report['approval'] === 'approved' ? 'القيود المعتمدة فقط' : 'يشمل القيود غير المعتمدة — للمراجعة');
        $sheet->fromArray(['كود المقبوضات','الحساب','المبلغ','كود المصروفات','الحساب','المبلغ'], null, 'A5');
        $left = $report['groups']['receipts']; $right = $report['groups']['expenses'];
        for ($i=0; $i<max(count($left['rows']),count($right['rows'])); $i++) {
            foreach ([$left, $right] as $side=>$group) {
                $row = $group['rows'][$i] ?? null;
                if (!$row) continue;
                $col = $side*3+1;
                $sheet->setCellValueExplicit([$col,$i+6],$row['code'],DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([$col+1,$i+6],$row['name'],DataType::TYPE_STRING);
                $sheet->setCellValueExplicit([$col+2,$i+6],$row['missing'] ? 'غير مربوط' : $row['amount']/100,$row['missing'] ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
            }
        }
        $last = $sheet->getHighestRow()+1;
        $sheet->fromArray(['إجمالي المقبوضات','',$left['total']/100,'إجمالي المصروفات','',$right['total']/100],null,'A'.$last);
        $sheet->setCellValue('A'.($last+1),'عدد القيود غير المعتمدة ذات الصلة: '.$report['pending']);
        $sheet->setCellValue('A'.($last+2),'المبالغ صافي المدين ناقص الدائن لبنود النموذج؛ تشمل المصروفات السلف والعهد المحددة بالمرفق.');
        foreach ($report['issues'] as $i=>$issue) $sheet->setCellValueExplicit('A'.($last+3+$i),$issue,DataType::TYPE_STRING);
        $sheet->getStyle('A5:F5')->getFont()->setBold(true);
        $sheet->getStyle('A'.$last.':F'.$last)->getFont()->setBold(true);
        foreach (['C','F'] as $col) $sheet->getStyle($col.'6:'.$col.$last)->getNumberFormat()->setFormatCode('#,##0.00;[Red]-#,##0.00');
        foreach (['A','D','C','F'] as $col) $sheet->getColumnDimension($col)->setWidth(22);
        foreach (['B','E'] as $col) $sheet->getColumnDimension($col)->setWidth(48);
        $sheet->freezePane('A6');
        return $book;
    }

    public function downloadReference(array $report)
    {
        return response()->streamDownload(function () use ($report) {
            $book = $this->referenceWorkbook($report);
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'daily-cash-receipts-expenses-'.$report['to'].'.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function download(array $report)
    {
        return response()->streamDownload(function () use ($report) {
            $book = $this->workbook($report);
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, 'cash-movement-'.$report['from'].'-'.$report['to'].'.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
