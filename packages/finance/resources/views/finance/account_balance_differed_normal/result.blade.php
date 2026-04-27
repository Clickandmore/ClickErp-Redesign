


    @if($accounts->count() > 0)

        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
            <tr class="header">
                <th style="width: 10%" scope="col">{{ __("finance::finance.account_code") }}</th>
                <th style="width: 10%" scope="col">{{ __("finance::finance.account_name") }}</th>
                <th style="width: 10%" scope="col">{{ __("finance::finance.notes") }}</th>
                <th style="width: 5%" scope="col">{{__('finance::finance.Balance') }}</th>
            </tr>
            </thead>
            <tbody>
            @php( $balance = 0 )
            @foreach ($accounts as $row)
                @php( $balance += $row->balance )

                <tr >
                    <td>{{ $row->code }}</td>
                    <td>{{ @$row->name }}</td>
                    <td>{{ $row->status == 'debtor' ? __('finance::finance.creditor_from_debtor_natural') : __('finance::finance.debtor_from_creditor_natural') }}</td>
                    <td>{{ -$row->balance }}</td>

                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-center">{{__('finance::finance.total')}}</td>

                <td class="currency-egp" >{{ -$balance }}</td>
            </tr>
            </tbody>
        </table>
    @else
        <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
    @endif
