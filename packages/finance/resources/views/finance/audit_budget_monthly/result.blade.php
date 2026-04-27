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
        <table id="myTable" class="table table-bordered table-striped dataTable no-footer">
            <thead>
            <tr>
                <th>#</th>
                <th>{{__('finance.account')}}</th>
                @if($firstBalance)
                    <th colspan="2">{{__('finance::finance.First Balance')}}</th>
                @endif
                @foreach($months_keys as $month)
                    <th colspan="2">{{ getMonthName($month) }}</th>
                @endforeach
                <th colspan="2">{{__('finance::finance.totals')}}</th>
                <th colspan="2">{{__('finance::finance.balances')}}</th>
            </tr>
            <tr>
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
                    <td>{{ $row['account_name'] }}</td>
                    @if($firstBalance)
                        <td class="col-y">{{ $firstBalance[$account_key]->sum_debtor_value ?? 0 }}</td>
                        <td>{{ $firstBalance[$account_key]->sum_creditor_value ?? 0 }}</td>
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
                        <td class="col-y">{{ $debtorValue }}</td>
                        <td>{{ $creditorValue }}</td>
                    @endforeach

                    <td class="col-y">{{ $rowDebtorTotal }}</td>
                    <td>{{ $rowCreditorTotal }}</td>

                    @php
                        $totals['debtor'] += ($row['type'] == 'debtor') ? $rowDebtorTotal - $rowCreditorTotal : 0;
                        $totals['creditor'] += ($row['type'] == 'creditor') ? $rowCreditorTotal - $rowDebtorTotal : 0;
                    @endphp
                    <td>{{ ($row['type'] == 'debtor') ? $rowDebtorTotal - $rowCreditorTotal : 0 }}</td>
                    <td>{{ ($row['type'] == 'creditor') ? $rowCreditorTotal - $rowDebtorTotal : 0 }}</td>
                </tr>
            @endforeach

            <tr style="background-color: #48b849">
                <td colspan="2">{{ __('finance::finance.total') }}</td>
                @if($firstBalance)
                    <td>{{ $totalSumDebtorValue }}</td>
                    <td>{{ $totalSumCreditorValue }}</td>
                @endif
                @foreach($months_keys as $key)
                    <th>{{ $months[$key]['sum_debtor_value'] }}</th>
                    <th>{{ $months[$key]['sum_creditor_value'] }}</th>
                @endforeach
                <td>{{ $totalDebtorValue }}</td>
                <td>{{ $totalCreditorValue }}</td>
                <td>{{ $totals['debtor'] }}</td>
                <td>{{ $totals['creditor'] }}</td>
            </tr>
            </tbody>
        </table>
    </div>

@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
@endif
