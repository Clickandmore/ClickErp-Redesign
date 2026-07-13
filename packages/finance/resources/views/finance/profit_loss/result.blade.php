@php
    use Nami\Finance\Enums\AccountTypesEnum;

    $amount = function ($value) {
        return number_format(abs(round((float) $value, 2)), 2);
    };

    $findRoot = function ($type) use ($data) {
        return collect($data)->first(fn ($row) => ($row['report_type'] ?? $row['account']->type) === $type);
    };

    $incomeRoot = $findRoot(AccountTypesEnum::GeneralIncome);
    $outcomeRoot = $findRoot(AccountTypesEnum::GeneralOutcome);

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

    $incomeRows = $filterNonZeroRows($incomeRoot['list'] ?? []);
    $outcomeRows = $filterNonZeroRows($outcomeRoot['list'] ?? []);

    $sumRows = fn ($rows) => collect($rows)->sum(fn ($row) => (float) $row['total_price']);
    $incomeTotal = $sumRows($incomeRows);
    $outcomeTotal = $sumRows($outcomeRows);
    $netProfit = $incomeTotal - $outcomeTotal;

    $renderRows = function ($rows, $level = 0) use (&$renderRows, $amount) {
        foreach ($rows as $row) {
            $account = $row['account'];
            $total = (float) $row['total_price'];
            $children = collect($row['children'] ?? []);
            $padding = 16 + ($level * 18);

            echo '<tr class="' . ($children->isNotEmpty() ? 'profit-parent-row' : '') . '">';
            echo '<td style="padding-right: ' . $padding . 'px">';
            echo '<span class="profit-code">' . e($account->code ?? $account->id) . '</span>';
            echo '<span>' . e($account->name) . '</span>';
            echo '</td>';
            echo '<td class="profit-amount">' . $amount($total) . '</td>';
            echo '</tr>';

            if ($children->isNotEmpty()) {
                $renderRows($children, $level + 1);
            }
        }
    };
@endphp

@if($incomeRows->isNotEmpty() || $outcomeRows->isNotEmpty())
    <div class="col d-flex justify-content-end no-print p-2" id="print_excel">
        <button onclick="handlePrint()" class="btn btn-success ml-2">
            <i class="fa fa-print"></i>
        </button>
    </div>

    <div id="print_section">
        @include('finance::finance.print-header')
        <style>
            .profit-report {
                direction: rtl;
                color: #111827;
                padding: 0.75rem;
            }

            .profit-report__title {
                margin: 10px auto 16px;
                text-align: center;
            }

            .profit-report__grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
                align-items: start;
            }

            .profit-report table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }

            .profit-report th,
            .profit-report td {
                border: 1px solid #d1d5db;
                padding: 7px 9px;
                vertical-align: middle;
            }

            .profit-report thead th {
                background: #315a63;
                color: #fff;
                text-align: center;
                font-weight: 700;
            }

            .profit-code {
                display: inline-block;
                min-width: 52px;
                color: #315a63;
                font-weight: 800;
                white-space: nowrap;
            }

            .profit-amount {
                width: 150px;
                direction: ltr;
                text-align: left;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .profit-parent-row td {
                background: #f7fafb;
                font-weight: 700;
            }

            .profit-total-row td {
                background: #fff4e6;
                color: #4f3420;
                font-weight: 800;
            }

            .profit-net-row td {
                background: {{ $netProfit >= 0 ? '#dcfce7' : '#fee2e2' }};
                font-weight: 800;
                font-size: 16px;
            }

            @media (max-width: 991.98px) {
                .profit-report__grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div class="profit-report">
            <div class="profit-report__title">
                <h5>
                    {{ __("finance::finance.profit_loss_statement") }}
                    {{ request('level') && request('level') !== 'all' ? __('finance::finance.level') . ' ' . request('level') : '' }}
                </h5>
                <div>
                    {{ __('finance::finance.values_from_date_to_date', [
                        'from' => request('from_date'),
                        'to' => request('to_date'),
                    ]) }}
                </div>
            </div>

            <div class="profit-report__grid">
                <table>
                    <thead>
                    <tr>
                        <th colspan="2">{{ __('finance::finance.revenues') }}</th>
                    </tr>
                    <tr>
                        <th>{{ __('finance::finance.account') }}</th>
                        <th>{{ __('finance::finance.Amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {!! $renderRows($incomeRows) !!}
                    <tr class="profit-total-row">
                        <td>{{ __('finance::finance.total_revenues') }}</td>
                        <td class="profit-amount">{{ $amount($incomeTotal) }}</td>
                    </tr>
                    </tbody>
                </table>

                <table>
                    <thead>
                    <tr>
                        <th colspan="2">{{ __('finance::finance.expenses') }}</th>
                    </tr>
                    <tr>
                        <th>{{ __('finance::finance.account') }}</th>
                        <th>{{ __('finance::finance.Amount') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {!! $renderRows($outcomeRows) !!}
                    <tr class="profit-total-row">
                        <td>{{ __('finance::finance.total_expenses') }}</td>
                        <td class="profit-amount">{{ $amount($outcomeTotal) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <table class="mt-3">
                <tbody>
                <tr class="profit-net-row">
                    <td>{{ $netProfit >= 0 ? __('finance::finance.net_profit') : __('finance::finance.net_loss') }}</td>
                    <td class="profit-amount">{{ $amount($netProfit) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@else
    <div role="alert" class="profit-result__empty"> {{__('finance::finance.no_data_found')}}</div>
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
                    <title>{{ __("finance::finance.profit_loss_statement") }}</title>
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
