@php $pagination = $data instanceof \Nami\Finance\Services\StatementPaginator ? $data : null; @endphp
@if($data->count() > 0 )
    @php
        $colspan = ($show_other_account == "yes" )? 7:6;
        $width   = ($show_other_account == "yes" )? 27:37;
    @endphp
    <table class="table table-hover table-bordered table-striped" id="simpleTable4" style="width:100%">
        <thead>
        <tr class="header">
            <th style="width: 3%" scope="col">#</th>
            <th style="width: 10%" scope="col"> {{ __('finance::finance.Account') }}</th>
            <th style="width: 10%" scope="col"> {{ __('finance::finance.date') }}</th>
            <th style="width: 5%" scope="col">{{ __('finance::finance.Transaction Number') }}</th>
            <th style="width: 5%" scope="col">{{ __('finance::finance.Transaction Type') }}</th>
            @if($show_other_account == "yes" )
                <th style="width: 10%" scope="col">{{__('finance::finance.Counter Account')}}</th>
            @endif
            <th style="width: {{$width}}%" scope="col">{{ __('finance::finance.statement') }}</th>
            <th style="width: 5%" scope="col" class="currency-egp">{{ __("finance::finance.debtor") }}</th>
            <th style="width: 5%" scope="col" class="currency-egp">{{ __("finance::finance.creditor") }}</th>
            <th style="width: 5%" scope="col" class="currency-egp">{{__('finance::finance.Balance')}}</th>
            <th style="width: 5%" scope="col">{{__('finance::finance.user')}}</th>

        </tr>
        </thead>
        <tbody>
        @php
            $balance = $pagination->previous->balance ?? 0;
            $balanceOther = 0 ;
            $sumCreditorValue = $sumDebtorValue = 0  ;
        @endphp
        @foreach ($data as $key=>$row)
            @php
                 $balance += $row->debtor_value - $row->creditor_value;
                 $sumDebtorValue += $row->debtor_value ;
                 $sumCreditorValue += $row->creditor_value ;
                 $trClass = ( $row->debtor_value  == 0  &&  $row->creditor_value == 0 )? "currency-usd":"currency-egp";
            @endphp
            <tr class="{{$trClass}}">
                <td>{{ $key + ($pagination ? $pagination->firstItem() : 1) }}</td>
                <td>{{ $row->account_name }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ $row->constraint_id }}</td>
                <td>{{  getEnumValue('constraint_types',$row->constraints_type)  }}</td>
                @if($show_other_account == "yes" )
                    <td>
                        @foreach($otherAccount[$row->constraint_id]["accounts"] as $oneAccount)
                            <span> {{$oneAccount->name}} </span> <br>
                        @endforeach
                    </td>
                @endif
                <td>{{ $row->statement }}</td>
                <td class="currency-egp">{{ $row->debtor_value }}</td>
                <td class="currency-egp">{{ $row->creditor_value }}</td>
                <td class="currency-egp">{{$balance}}</td>
                <td>{{ $row->name }}</td>
            </tr>
        @endforeach
        @php
            if ($pagination) {
                $sumDebtorValue = $pagination->totals->debtor;
                $sumCreditorValue = $pagination->totals->creditor;
                $balance = $pagination->totals->balance;
            }
        @endphp
        <tr>
            <td colspan="{{$colspan}}" class="text-center"> {{__('finance::finance.Balance')}}</td>
            <td class="currency-egp">{{ $sumDebtorValue }}</td>
            <td class="currency-egp">{{ $sumCreditorValue }}</td>
            <td class="currency-egp" style="background-color: yellow">{{$balance}}</td>
            <td></td>
        </tr>
        </tbody>
    </table>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>

@endif
@include('finance::finance.account_statement.partials.pagination')


