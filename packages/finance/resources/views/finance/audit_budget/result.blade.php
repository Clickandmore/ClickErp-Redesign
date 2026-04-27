{{--@dd($firstBalance)--}}
@if($data->count() > 0)

    <table class="table table-hover table-bordered table-striped" id="simpleTable4" style="width:100%">
        <thead>
        <tr class="header">
            <th style="width: 5%" scope="col">#</th>
            <th style="width: 10%" scope="col">{{ __("finance::finance.code") }}</th>
            <th style="width: 5%" scope="col">{{ __("finance::finance.account") }}</th>
            @if($firstBalance)
            <th style="width: 5%" scope="col">{{ __("finance::finance.first_balance")." ".__("finance::finance.debtor") }}</th>
            <th style="width: 5%" scope="col">{{ __("finance::finance.first_balance")." ".__("finance::finance.creditor") }}</th>
            @endif
            <th style="width: 10%" scope="col">{{ __("finance::finance.balance_from_to_debtor") }}</th>
            <th style="width: 10%" scope="col">{{ __("finance::finance.balance_from_to_creditor") }}</th>
            @if($firstBalance)
                <th style="width: 5%" scope="col">{{__("finance::finance.total")." ". __("finance::finance.debtor") }}</th>
                <th style="width: 5%" scope="col">{{__("finance::finance.total")." ". __("finance::finance.creditor") }}</th>
            @endif
            <th style="width: 5%" scope="col" class="currency-egp">{{__('finance::finance.Balance')}} {{ __("finance::finance.debtor") }}</th>
            <th style="width: 5%" scope="col" class="currency-egp"> {{__('finance::finance.Balance')}} {{ __("finance::finance.creditor") }}</th>
        </tr>
        </thead>
        <tbody>
        @php
            $balance_sum_debtor_value = 0;
            $balance_sum_creditor_value = 0;
            $balanceDebtorValue = 0;
            $balanceCreditorValue = 0;
             $firstSumDebtorValue = 0;
            $firstSumCreditorValue = 0;
             $totalSumDebtorValue = 0;
            $totalSumCreditorValue = 0;
            $sumCreditorValue = $sumDebtorValue = 0;
        @endphp
        @foreach ($data as $key => $row)
            @php
                $balance_sum_debtor_value += $row->sum_debtor_value;
                $balance_sum_creditor_value += $row->sum_creditor_value;
                $balanceDebtorValue += ($row->account_status == "debtor") ? ($row->sum_debtor_value - $row->sum_creditor_value) : 0;
                $balanceCreditorValue += ($row->account_status == "creditor") ? ($row->sum_creditor_value - $row->sum_debtor_value) : 0;
                if($firstBalance){
                    $firstSumDebtorValue += $firstBalance[$key]->sum_debtor_value ?? 0;
                    $firstSumCreditorValue += $firstBalance[$key]->sum_creditor_value??0 ;
                    $totalSumDebtorValue += $firstBalance[$key]->sum_debtor_value??0  ;
                    $totalSumCreditorValue += $firstBalance[$key]->sum_creditor_value??0  ;
                }
//                $trClass = ($row->debtor_value == 0 && $row->creditor_value == 0) ? "currency-usd" : "currency-egp";
            @endphp
            <tr class="">
                <td>{{ ++$loop->index }}</td>
                <td>{{ $row->account_code }}</td>
                <td>{{ $row->account_name }}</td>
                @if($firstBalance)
                <td>{{ $firstBalance[$key]->sum_debtor_value ?? 0 }}</td>
                <td>{{ $firstBalance[$key]->sum_creditor_value ?? 0 }}</td>
                @endif
                <td>{{ $row->sum_debtor_value }}</td>
                <td>{{ $row->sum_creditor_value }}</td>
                @if($firstBalance)
                 <td>{{ $firstBalance[$key]->sum_debtor_value ?? 0 }}</td>
                <td>{{ $firstBalance[$key]->sum_creditor_value ?? 0 }}</td>
                @endif
                <td>{{ ($row->account_status == "debtor") ? ($row->sum_debtor_value - $row->sum_creditor_value) : 0 }}</td>
                <td>{{  ($row->account_status == "creditor") ? ($row->sum_creditor_value - $row->sum_debtor_value) : 0  }}</td>

            </tr>
        @endforeach
        <tr>
            <td colspan="3" class="text-center">{{__('finance::finance.total')}}</td>
            @if($firstBalance)
            <td class="currency-egp">{{ $firstSumDebtorValue }}</td>
            <td class="currency-egp">{{ $firstSumCreditorValue }}</td>
            @endif
            <td class="currency-egp">{{ $balance_sum_debtor_value }}</td>
            <td class="currency-egp">{{ $balance_sum_creditor_value }}</td>
            @if($firstBalance)
            <td class="currency-egp">{{ $totalSumDebtorValue }}</td>
            <td class="currency-egp">{{ $totalSumCreditorValue }}</td>
            @endif
            <td class="currency-egp" >{{ $balanceDebtorValue }}</td>
            <td class="currency-egp" >{{ $balanceCreditorValue }}</td>
        </tr>
        </tbody>
    </table>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>

@endif
