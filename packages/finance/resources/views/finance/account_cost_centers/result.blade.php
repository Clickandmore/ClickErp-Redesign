@if($data->count() > 0)
    @php
        $colspan = 6;
    @endphp
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped statement-table" id="simpleTable4" style="width:100%">
            <thead>
            <tr class="header">
                <th style="width: 4%" scope="col">#</th>
                <th style="width: 13%" scope="col">{{ __("finance::finance.account") }}</th>
                <th style="width: 9%" scope="col">{{ __("finance::finance.Transaction Date") }}</th>
                <th style="width: 7%" scope="col">{{ __("finance::finance.Transaction Number") }}</th>
                <th style="width: 9%" scope="col">{{ __("finance::finance.Transaction Type") }}</th>
                <th style="width: 29%" scope="col">{{__('finance::finance.statement')}}</th>
                <th style="width: 7%" scope="col" class="currency-egp">{{ __("finance::finance.debtor") }}</th>
                <th style="width: 7%" scope="col" class="currency-egp">{{ __("finance::finance.creditor") }}</th>
                <th style="width: 7%" scope="col" class="currency-egp">{{__('finance::finance.Balance')}}</th>
                <th style="width: 8%" scope="col">{{__('finance::finance.user')}}</th>
            </tr>
            </thead>
            <tbody>
            @php
                $balance = 0;
                $sumCreditorValue = $sumDebtorValue = 0;
            @endphp
            @foreach ($data as $key => $row)
                @php
                    $balance += ($row->status == "debtor") ? ($row->debtor_value - $row->creditor_value) : ($row->creditor_value - $row->debtor_value);
                    $sumDebtorValue += $row->debtor_value;
                    $sumCreditorValue += $row->creditor_value;
                    $trClass = ($row->debtor_value == 0 && $row->creditor_value == 0) ? "currency-usd" : "currency-egp";
                @endphp
                <tr class="{{ $trClass }}">
                    <td class="text-center">{{ ++$key }}</td>
                    <td class="account-cell">{{ @$row->account_name }}</td>
                    <td class="text-center">{{ $row->date }}</td>
                    <td class="text-center">{{ $row->constraint_id }}</td>
                    <td class="text-center">{{ getEnumValue('constraint_types', $row->constraints_type) }}</td>
                    <td class="statement-cell">{{ $row->statement }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $row->debtor_value, 2) }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $row->creditor_value, 2) }}</td>
                    <td class="currency-egp number-cell">{{ number_format((float) $balance, 2) }}</td>
                    <td class="text-center user-cell">{{ $row->name }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="{{ $colspan }}" class="text-center">{{__('finance::finance.Balance')}}</td>
                <td class="currency-egp number-cell">{{ number_format((float) $sumDebtorValue, 2) }}</td>
                <td class="currency-egp number-cell">{{ number_format((float) $sumCreditorValue, 2) }}</td>
                <td class="currency-egp number-cell">{{ number_format((float) $balance, 2) }}</td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>
    <div class="report-scroll-hint">مرر الجدول يمينًا ويسارًا عند ضيق الشاشة لعرض جميع الأعمدة.</div>
@else
    <div role="alert" class="alert alert-danger text-center empty-state"> {{__('finance::finance.no_data_found')}}</div>
@endif
