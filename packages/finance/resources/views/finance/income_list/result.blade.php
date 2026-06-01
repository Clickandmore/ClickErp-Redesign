@php
    use Nami\Finance\Enums\AccountTypesEnum;

    $amount = function ($value) {
        $value = round((float) $value, 2);

        return number_format(abs($value), 2);
    };

    $natureLabel = function ($value, $normalStatus = 'creditor') {
        $normalStatus = $normalStatus instanceof BackedEnum ? $normalStatus->value : (string) $normalStatus;
        $status = (float) $value < 0
            ? ($normalStatus === 'creditor' ? 'debtor' : 'creditor')
            : $normalStatus;

        return $status === 'creditor' ? __('finance::finance.creditor') : __('finance::finance.debtor');
    };

    $findRoot = function ($type) use ($data) {
        return collect($data)->first(fn ($row) => $row['account']->type === $type);
    };

    $incomeRoot = $findRoot(AccountTypesEnum::GeneralIncome);
    $outcomeRoot = $findRoot(AccountTypesEnum::GeneralOutcome);
    $incomeRows = collect($incomeRoot['list'] ?? []);
    $outcomeRows = collect($outcomeRoot['list'] ?? []);

    $rowName = fn ($row) => $row['account']->name ?? '';
    $rowContains = fn ($row, array $needles) => collect($needles)->contains(
        fn ($needle) => mb_stripos($rowName($row), $needle) !== false
    );

    $recruitmentIncome = $incomeRows->filter(fn ($row) => $rowContains($row, ['استقدام']));
    $otherIncome = $incomeRows->reject(fn ($row) => $rowContains($row, ['استقدام']));

    $revenueReturns = $outcomeRows->filter(fn ($row) => $rowContains($row, [
        'الخدمات الإضافية',
        'التأجير',
    ]));

    $revenueCosts = $outcomeRows->filter(fn ($row) => $rowContains($row, [
        'تكاليف مبيعات الاستقدام',
    ]));

    $generalExpenses = $outcomeRows->filter(fn ($row) => $rowContains($row, [
        'عمومية',
        'ادارية',
        'إدارية',
        'تشغيلية مباشرة',
        'بيعية',
        'تسويقية',
        'تشغيلية اخرى',
        'تشغيلية أخرى',
        'الاهلاك',
        'الإهلاك',
    ]));

    $otherCosts = $outcomeRows
        ->reject(fn ($row) => $revenueReturns->containsStrict($row))
        ->reject(fn ($row) => $revenueCosts->containsStrict($row))
        ->reject(fn ($row) => $generalExpenses->containsStrict($row));

    $isZero = fn ($value) => abs((float) $value) < 0.005;
    $filterNonZeroRows = function ($rows) use (&$filterNonZeroRows, $isZero) {
        return collect($rows)
            ->map(function ($row) use (&$filterNonZeroRows) {
                $row['children'] = $filterNonZeroRows($row['children'] ?? [])->values()->all();

                return $row;
            })
            ->filter(fn ($row) => !$isZero($row['total_price']) || count($row['children'] ?? []) > 0)
            ->values();
    };

    $recruitmentIncome = $filterNonZeroRows($recruitmentIncome);
    $revenueReturns = $filterNonZeroRows($revenueReturns);
    $revenueCosts = $filterNonZeroRows($revenueCosts);
    $generalExpenses = $filterNonZeroRows($generalExpenses);
    $otherIncome = $filterNonZeroRows($otherIncome);
    $otherCosts = $filterNonZeroRows($otherCosts);

    $sumRows = fn ($rows) => collect($rows)->sum(fn ($row) => (float) $row['total_price']);

    $recruitmentIncomeTotal = $sumRows($recruitmentIncome);
    $revenueReturnsTotal = $sumRows($revenueReturns);
    $revenueCostsTotal = $sumRows($revenueCosts);
    $grossProfit = $recruitmentIncomeTotal - $revenueCostsTotal;
    $generalExpensesTotal = $sumRows($generalExpenses);
    $otherIncomeTotal = $sumRows($otherIncome);
    $otherCostsTotal = $sumRows($otherCosts);
    $netIncome = $grossProfit - $revenueReturnsTotal - $generalExpensesTotal + $otherIncomeTotal - $otherCostsTotal;

    $renderRows = function ($rows, $level = 0) use (&$renderRows, $amount, $natureLabel) {
        foreach ($rows as $row) {
            $account = $row['account'];
            $total = (float) $row['total_price'];
            $children = collect($row['children'] ?? []);
            $padding = 18 + ($level * 22);

            echo '<tr class="' . ($children->isNotEmpty() ? 'income-parent-row' : '') . '">';
            echo '<td class="income-code">' . e($account->code ?? $account->id) . '</td>';
            echo '<td style="padding-right: ' . $padding . 'px">' . e($account->name) . '</td>';
            echo '<td class="income-amount">' . $amount($total) . '</td>';
            echo '<td class="income-type">' . $natureLabel($total, $account->status ?? 'creditor') . '</td>';
            echo '</tr>';

            if ($children->isNotEmpty()) {
                $renderRows($children, $level + 1);
            }
        }
    };
@endphp

@if(count($data))
    <div class="col d-flex justify-content-end no-print" id="print_excel">
        <button onclick="handlePrint()" class="btn btn-success ml-2"><i class="fa fa-print"></i></button>
    </div>

    <div id="print_section">
        @include('finance::finance.print-header')
        <style>
            .income-report {
                direction: rtl;
                color: #111827;
            }

            .income-report table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }

            .income-report th,
            .income-report td {
                border: 1px solid #d1d5db;
                padding: 7px 9px;
                vertical-align: middle;
            }

            .income-report thead th {
                background: #f3f4f6;
                text-align: center;
                font-weight: 700;
            }

            .income-section-row td {
                background: #e5edf7;
                font-weight: 700;
                font-size: 16px;
                text-align: center;
            }

            .income-parent-row td {
                background: #fafafa;
                font-weight: 600;
            }

            .income-total-row td {
                background: #dbeafe;
                font-weight: 700;
            }

            .income-result-row td {
                background: #e0f2fe;
                font-weight: 800;
                font-size: 16px;
            }

            .income-net-row td {
                background: #dcfce7;
                font-weight: 800;
                font-size: 17px;
            }

            .income-code,
            .income-type {
                width: 90px;
                text-align: center;
                white-space: nowrap;
            }

            .income-amount {
                width: 170px;
                direction: ltr;
                text-align: left;
                white-space: nowrap;
            }
        </style>

        <div class="income-report">
            <div class="text-center" style="margin: 10px auto 16px">
                <h5>{{ __("finance::finance.Income statement") }} {{ request('from_date') ? __('finance::finance.from_date') : '' }} {{ request('from_date') }} {{ request('to_date') ? __('finance::finance.to_date') : '' }} {{ request('to_date') }}</h5>
            </div>

            <table>
                <thead>
                <tr>
                    <th>الكود</th>
                    <th>الحساب / البند</th>
                    <th>المبلغ</th>
                    <th>الطبيعة</th>
                </tr>
                </thead>
                <tbody>
                @if($recruitmentIncome->isNotEmpty() || $otherIncome->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">الإيرادات</td></tr>
                @endif

                @if($recruitmentIncome->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">إيرادات الاستقدام</td></tr>
                {!! $renderRows($recruitmentIncome) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي إيرادات الاستقدام</td>
                    <td class="income-amount">{{ $amount($recruitmentIncomeTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($recruitmentIncomeTotal, 'creditor') }}</td>
                </tr>
                @endif

                @if($revenueReturns->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">المردودات</td></tr>
                {!! $renderRows($revenueReturns) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي المردودات</td>
                    <td class="income-amount">{{ $amount($revenueReturnsTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($revenueReturnsTotal, 'debtor') }}</td>
                </tr>
                @endif

                @if($revenueCosts->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">تكلفة الإيرادات</td></tr>
                {!! $renderRows($revenueCosts) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي تكلفة الإيرادات</td>
                    <td class="income-amount">{{ $amount($revenueCostsTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($revenueCostsTotal, 'debtor') }}</td>
                </tr>
                @endif

                @if($recruitmentIncome->isNotEmpty() || $revenueCosts->isNotEmpty())
                <tr class="income-result-row">
                    <td colspan="2">{{ $grossProfit >= 0 ? 'مجمل الربح' : 'مجمل الخسارة' }}</td>
                    <td class="income-amount">{{ $amount($grossProfit) }}</td>
                    <td class="income-type">{{ $natureLabel($grossProfit, 'creditor') }}</td>
                </tr>
                @endif

                @if($generalExpenses->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">مصاريف عمومية وإدارية</td></tr>
                {!! $renderRows($generalExpenses) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي المصاريف العمومية والإدارية</td>
                    <td class="income-amount">{{ $amount($generalExpensesTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($generalExpensesTotal, 'debtor') }}</td>
                </tr>
                @endif

                @if($otherIncome->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">الإيرادات الأخرى</td></tr>
                {!! $renderRows($otherIncome) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي الإيرادات الأخرى</td>
                    <td class="income-amount">{{ $amount($otherIncomeTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($otherIncomeTotal, 'creditor') }}</td>
                </tr>
                @endif

                @if($otherCosts->isNotEmpty())
                <tr class="income-section-row"><td colspan="4">التكاليف الأخرى</td></tr>
                {!! $renderRows($otherCosts) !!}
                <tr class="income-total-row">
                    <td colspan="2">إجمالي التكاليف الأخرى</td>
                    <td class="income-amount">{{ $amount($otherCostsTotal) }}</td>
                    <td class="income-type">{{ $natureLabel($otherCostsTotal, 'debtor') }}</td>
                </tr>
                @endif

                <tr class="income-net-row">
                    <td colspan="2">{{ $netIncome >= 0 ? 'صافي الدخل' : 'صافي الخسارة' }}</td>
                    <td class="income-amount">{{ $amount($netIncome) }}</td>
                    <td class="income-type">{{ $natureLabel($netIncome, 'creditor') }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
@endif

<script>
    function handlePrint() {
        const printContent = document.getElementById('print_section').innerHTML;
        const styleSheet = `
            <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css">
            <style>
                body {
                    direction: rtl;
                    font-family: "Arial", sans-serif;
                    padding: 20px;
                }
                @media print {
                    html, body {
                        width: 95%;
                        height: 100%;
                        margin: auto;
                        padding: 0;
                        overflow: visible;
                    }
                }
            </style>
        `;

        const printWindow = window.open('', '_blank');

        printWindow.document.open();
        printWindow.document.write(`
            <html>
                <head>
                    <title>{{ __("finance::finance.Income statement") }}</title>
                    ${styleSheet}
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                        window.onafterprint = function() {
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
