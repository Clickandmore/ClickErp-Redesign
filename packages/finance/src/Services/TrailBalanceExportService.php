<?php

namespace Nami\Finance\Services;

use Illuminate\Http\Request;

class TrailBalanceExportService
{
    public function download(
        Request $request,
        string $format,
        TrailBalanceService $service,
        YearService $yearService,
        ComprehensiveTableExportService $exporter
    ) {
        $period = $yearService->getReportPeriod();
        abort_unless($period, 422, 'لا توجد سنة مالية معرفة.');

        $levels = $service->showLevels();
        $level = $request->query('level', $levels[0] ?? 1);
        $level = $level === 'all' ? 'all' : (int) $level;
        $fromDate = (string) $request->query('from_date', $period->from_date);
        $toDate = (string) $request->query('to_date', $period->to_date);
        $includeOpening = $request->boolean('show_first_balance');
        $accounts = $service->getAccountSummaryForTrailBalance($level, $fromDate, $toDate, $includeOpening);

        $headers = ['رمز الحساب', 'اسم الحساب'];
        if ($level === 'all') {
            $headers[] = 'المستوى';
        }
        if ($includeOpening) {
            array_push($headers, 'أول المدة دائن', 'أول المدة مدين');
        }
        array_push($headers, 'حركة الفترة دائن', 'حركة الفترة مدين', 'الرصيد الختامي دائن', 'الرصيد الختامي مدين');

        $rows = $accounts->map(function ($account) use ($level, $includeOpening) {
            $firstDebtor = (float) $account->first_debtor;
            $firstCreditor = (float) $account->first_creditor;
            $periodDebtor = (float) $account->total_debtor;
            $periodCreditor = (float) $account->total_creditor;
            $finalDebtor = $firstDebtor + $periodDebtor;
            $finalCreditor = $firstCreditor + $periodCreditor;
            $creditorBalance = $account->status === 'creditor' ? $finalCreditor - $finalDebtor : 0;
            $debtorBalance = $account->status === 'debtor' ? $finalDebtor - $finalCreditor : 0;

            if (abs($firstDebtor) + abs($firstCreditor) + abs($periodDebtor) + abs($periodCreditor) < 0.00001) {
                return null;
            }

            $row = [(string) $account->code, (string) $account->name];
            if ($level === 'all') {
                $row[] = (int) $account->level;
            }
            if ($includeOpening) {
                array_push($row, $firstCreditor, $firstDebtor);
            }
            array_push($row, $periodCreditor, $periodDebtor, $creditorBalance, $debtorBalance);

            return $row;
        })->filter()->values()->all();

        $numericStart = 2 + ($level === 'all' ? 1 : 0);
        $numericColumns = range($numericStart, count($headers) - 1);

        return $exporter->download(
            $format,
            'ميزان المراجعة الشامل من ' . $fromDate . ' إلى ' . $toDate,
            'trial-balance',
            $headers,
            $rows,
            $numericColumns
        );
    }
}
