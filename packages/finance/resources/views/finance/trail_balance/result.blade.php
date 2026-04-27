@if(isset($accountsWithChildren))
    <table class="table table-hover table-bordered table-striped" style="width:100%;font-size: 17px">
        <thead>
        <tr class="header">
            <th rowspan="2">{{ __("finance::finance.Account code") }}</th>
            <th  rowspan="2">{{ __("finance::finance.Account Name") }}</th>
            @if(isset($includeFirstBalance))
                <th colspan="2">{{ __("finance::finance.first-stocks") }} +</th>
            @endif
            <th colspan="2">{{ __('finance::finance.operations_period') }}</th>
            <th colspan="2">{{ __('finance::finance.last-stocks') }}</th>
        </tr>
        <tr class="header">

            <th>{{ __('finance::finance.creditor') }}</th>
            <th>{{ __('finance::finance.debtor') }}</th>
            @if(isset($includeFirstBalance))
                <th>{{ __('finance::finance.creditor') }}</th>
                <th>{{ __('finance::finance.debtor') }}</th>
            @endif
            <th>{{ __('finance::finance.creditor') }}</th>
            <th>{{ __('finance::finance.debtor') }}</th>
        </tr>

        </thead>
        <tbody style="font-size: 19px">
            <?php
            $totalDebtorBalance = $totalCreditorBalance = $sumOfDebtorBalance = $sumOfCreditorBalance = 0;
            $totalFirstDebtorValue = $totalFirstCreditorValue = $totalFinalDebitValue = $totalFinalCreditValue = 0;
            ?>

        @foreach ($accountsWithChildren as $account)
                <?php
                $sumPeriodDebtorValue = $sumPeriodCreditorValue = $firstDebtorValue = $firstCreditorValue = $finalSumDebtorValue = $finalSumCreditorValue = 0;

                $sumPeriodDebtorValue += $account->total_debtor;
                $sumPeriodCreditorValue += $account->total_creditor;

                $finalSumDebtorValue = $sumPeriodDebtorValue + $firstDebtorValue;
                $finalSumCreditorValue = $sumPeriodCreditorValue + $firstCreditorValue;

                // حساب اجمالي دائن ومدين في فترة
                $totalDebtorBalance += $sumPeriodDebtorValue;
                $totalCreditorBalance += $sumPeriodCreditorValue;

                // حساب اجمالي رصيد اول دائن ورصيد اول مدين
                $totalFirstDebtorValue += $firstDebtorValue;
                $totalFirstCreditorValue += $firstCreditorValue;

                // Calculate total credit and debit balance
                $totalFinalDebitValue += $finalSumDebtorValue;
                $totalFinalCreditValue += $finalSumCreditorValue;

                // حساب رصيد دائن ومدين بناء علي نوع الحساب
                if($account->status == 'debtor'){
                    $debtorBalance = $finalSumDebtorValue - $finalSumCreditorValue ;
                }else{
                    $debtorBalance =  0;
                }

                if($account->status == 'creditor'){
                    $creditorBalance =  $finalSumCreditorValue - $finalSumDebtorValue ;
                }else{
                    $creditorBalance = 0;
                }

                // حساب اجمالي رصيد دائن والمدين
                $sumOfDebtorBalance += $debtorBalance;
                $sumOfCreditorBalance += $creditorBalance;

                if ( $firstCreditorValue == 0 &&
                     $firstDebtorValue == 0  &&
                     $sumPeriodCreditorValue == 0 &&
                     $sumPeriodDebtorValue == 0 &&
                     $creditorBalance == 0  &&
                     $debtorBalance == 0 ){
                continue;
                }
                ?>
            <tr>
                <td>{{--{{ $account->code }}--}} {{ $account->id }}</td>
                <td>(  {{ $account->name }} ) -  {{$account->status  }}</td>

                @if(isset($includeFirstBalance))
                    <td>{{ $firstCreditorValue }}</td>
                    <td>{{ $firstDebtorValue }}</td>
                @endif

                <td>{{ $sumPeriodCreditorValue }}</td>
                <td>{{ $sumPeriodDebtorValue }}</td>

                <td>{{ $creditorBalance }}</td>
                <td>{{ $debtorBalance }}</td>
            </tr>
        @endforeach

        <tr class="header">
            <td class="text-center" colspan="2"><strong>{{ __('finance::finance.Total') }}</strong></td>

            @if(isset($includeFirstBalance))
                <td><strong>{{ $totalFirstCreditorValue }}</strong></td>
                <td><strong>{{ $totalFirstDebtorValue }}</strong></td>
            @endif

            <td><strong>{{ $totalCreditorBalance }}</strong></td>
            <td><strong>{{ $totalDebtorBalance }}</strong></td>

            <td><strong>{{ $sumOfCreditorBalance }}</strong></td>
            <td><strong>{{ $sumOfDebtorBalance }}</strong></td>
        </tr>
        </tbody>
    </table>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
@endif
