{{--@dd($firstBalance)--}}
@if($data->count() > 0)
    @php
        $months  = [];
        $totals['debtor']  = 0;
        $totals['creditor']  = 0;
        $totalDebtorValue = 0.0;
        $totalCreditorValue = 0.0;
        $totalSumDebtorValue = 0;
        $totalSumCreditorValue = 0;
    @endphp
    <div class="table-responsive">
        <table id="simpleTable4" class="table table-hover table-bordered table-striped dataTable no-footer align-middle audit-budget-table">
            <thead>
            <tr class="header text-center">
                <th style="width: 4%">#</th>
                <th style="width: 8%">{{ __("finance::finance.code") }}</th>
                <th style="min-width: 240px">{{__('finance::finance.account')}}</th>
                @if($firstBalance)
                    <th colspan="2">{{__('finance::finance.First Balance')}}</th>
                @endif
                @foreach($months_keys as $month)
                    <th colspan="2">{{ getMonthName($month) }}</th>
                @endforeach
                <th colspan="2">{{__('finance::finance.totals')}}</th>
                <th colspan="2">{{__('finance::finance.balances')}}</th>
            </tr>
            <tr class="header text-center">
                <th></th>
                <th></th>
                <th></th>
                @if($firstBalance)
                    <th>{{__('finance::finance.debtor')}}</th>
                    <th>{{__('finance::finance.creditor')}}</th>
                @endif
                @foreach($months_keys as $month)
                    @php
                        $months[$month]['sum_debtor_value'] = 0;
                        $months[$month]['sum_creditor_value'] = 0;
                    @endphp
                    <th>{{__('finance::finance.debtor')}}</th>
                    <th>{{__('finance::finance.creditor')}}</th>
                @endforeach
                <th>{{__('finance::finance.debtor')}}</th>
                <th>{{__('finance::finance.creditor')}}</th>
                <th>{{__('finance::finance.debtor')}}</th>
                <th>{{__('finance::finance.creditor')}}</th>
            </tr>
            </thead>
            <tbody id="option_table">

            @foreach ($data as $account_key => $row)
                @php
                    $rowDebtorTotal = 0.0;
                    $rowCreditorTotal = 0.0;
                    if($firstBalance){

                        $totalSumDebtorValue += $firstBalance[$account_key]->sum_debtor_value??0  ;
                        $totalSumCreditorValue += $firstBalance[$account_key]->sum_creditor_value??0  ;
                    }

                @endphp
                <tr>
                    <td>{{ ++$loop->index }}</td>
                    <td class="text-nowrap">{{ $row['account_code'] }}</td>
                    <td>{{ $row['account_name'] }}</td>
                    @if($firstBalance)
                        <td class="col-y text-end">{{ number_format($firstBalance[$account_key]->sum_debtor_value ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($firstBalance[$account_key]->sum_creditor_value ?? 0, 2) }}</td>
                    @endif
                    @foreach($months_keys as $month)
                        @php
                            $debtorValue = $row['months'][$month]['sum_debtor_value'] ?? 0.0;
                            $creditorValue = $row['months'][$month]['sum_creditor_value'] ?? 0.0;

                            $months[$month]['sum_debtor_value'] += $debtorValue;
                            $months[$month]['sum_creditor_value'] += $creditorValue;

                            $totalDebtorValue += $debtorValue;
                            $totalCreditorValue += $creditorValue;

                            $rowDebtorTotal += $debtorValue;
                            $rowCreditorTotal += $creditorValue;
                        @endphp
                        <td class="col-y text-end">{{ number_format($debtorValue, 2) }}</td>
                        <td class="text-end">{{ number_format($creditorValue, 2) }}</td>
                    @endforeach

                    <td class="col-y text-end">{{ number_format($rowDebtorTotal, 2) }}</td>
                    <td class="text-end">{{ number_format($rowCreditorTotal, 2) }}</td>

                    @php
                        $totals['debtor'] += ($row['type'] == 'debtor') ? $rowDebtorTotal - $rowCreditorTotal : 0;
                        $totals['creditor'] += ($row['type'] == 'creditor') ? $rowCreditorTotal - $rowDebtorTotal : 0;
                    @endphp
                    <td class="text-end">{{ number_format(($row['type'] == 'debtor') ? $rowDebtorTotal - $rowCreditorTotal : 0, 2) }}</td>
                    <td class="text-end">{{ number_format(($row['type'] == 'creditor') ? $rowCreditorTotal - $rowDebtorTotal : 0, 2) }}</td>
                </tr>
            @endforeach

            <tr class="fw-bold" style="background-color: #48b849">
                <td colspan="3" class="text-center">{{ __('finance::finance.total') }}</td>
                @if($firstBalance)
                    <td class="text-end">{{ number_format($totalSumDebtorValue, 2) }}</td>
                    <td class="text-end">{{ number_format($totalSumCreditorValue, 2) }}</td>
                @endif
                @foreach($months_keys as $key)
                    <th class="text-end">{{ number_format($months[$key]['sum_debtor_value'], 2) }}</th>
                    <th class="text-end">{{ number_format($months[$key]['sum_creditor_value'], 2) }}</th>
                @endforeach
                <td class="text-end">{{ number_format($totalDebtorValue, 2) }}</td>
                <td class="text-end">{{ number_format($totalCreditorValue, 2) }}</td>
                <td class="text-end">{{ number_format($totals['debtor'], 2) }}</td>
                <td class="text-end">{{ number_format($totals['creditor'], 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>

@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
@endif
