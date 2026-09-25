@if(isset($accountsWithChildren) && count($accountsWithChildren))
    <div class="trial-print-header" aria-hidden="true">
        <div class="trial-print-header__date">{{ now()->format('d/m/Y, H:i') }}</div>
        <h1 class="trial-print-header__title">{{ helperTrans('finance::finance.trail-balance') }}</h1>
        <div class="trial-print-header__period">
            {{ __('finance::finance.from_date') }}: {{ request('from_date') }}
            <br>
            {{ __('finance::finance.to_date') }}: {{ request('to_date') }}
            @include('admin.partials.branch-report-scope', ['scopeTag' => 'div'])
        </div>
    </div>

    <div class="trial-table-wrap">
    <table class="table table-hover table-bordered trial-table">
        <thead>
        <tr>
            <th rowspan="2">{{ __("finance::finance.Account code") }}</th>
            <th  rowspan="2">{{ __("finance::finance.Account Name") }}</th>
            @if($allLevels ?? false)
                <th rowspan="2">{{ __('finance::finance.level') }}</th>
            @endif
            @if($includeFirstBalance)
                <th colspan="2">{{ __("finance::finance.first-stocks") }} +</th>
            @endif
            <th colspan="2">{{ __('finance::finance.operations_period') }}</th>
            <th colspan="2">{{ __('finance::finance.last-stocks') }}</th>
        </tr>
        <tr>

            @if($includeFirstBalance)
                <th>{{ __('finance::finance.creditor') }}</th>
                <th>{{ __('finance::finance.debtor') }}</th>
            @endif
            <th>{{ __('finance::finance.creditor') }}</th>
            <th>{{ __('finance::finance.debtor') }}</th>
            <th>{{ __('finance::finance.creditor') }}</th>
            <th>{{ __('finance::finance.debtor') }}</th>
        </tr>

        </thead>
        <tbody style="font-size: 19px">
            <?php
            $totalDebtorBalance = $totalCreditorBalance = $sumOfDebtorBalance = $sumOfCreditorBalance = 0;
            $totalFirstDebtorValue = $totalFirstCreditorValue = $totalFinalDebitValue = $totalFinalCreditValue = 0;
            $summaryLevel = ($allLevels ?? false) ? $accountsWithChildren->min('level') : null;
            ?>

        @foreach ($accountsWithChildren as $account)
                <?php
                $sumPeriodDebtorValue = $sumPeriodCreditorValue = $firstDebtorValue = $firstCreditorValue = $finalSumDebtorValue = $finalSumCreditorValue = 0;

                $sumPeriodDebtorValue += (float) $account->total_debtor;
                $sumPeriodCreditorValue += (float) $account->total_creditor;
                $firstDebtorValue += (float) $account->first_debtor;
                $firstCreditorValue += (float) $account->first_creditor;

                $finalSumDebtorValue = $sumPeriodDebtorValue + $firstDebtorValue;
                $finalSumCreditorValue = $sumPeriodCreditorValue + $firstCreditorValue;

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

                // عند عرض كل المستويات نجمع المستوى الأعلى فقط حتى لا تتكرر
                // حركة الحساب داخل إجمالي الأب والابن في الوقت نفسه.
                $includeInTotals = !($allLevels ?? false) || (int) $account->level === (int) $summaryLevel;
                if ($includeInTotals) {
                    $totalDebtorBalance += $sumPeriodDebtorValue;
                    $totalCreditorBalance += $sumPeriodCreditorValue;
                    $totalFirstDebtorValue += $firstDebtorValue;
                    $totalFirstCreditorValue += $firstCreditorValue;
                    $totalFinalDebitValue += $finalSumDebtorValue;
                    $totalFinalCreditValue += $finalSumCreditorValue;
                    $sumOfDebtorBalance += $debtorBalance;
                    $sumOfCreditorBalance += $creditorBalance;
                }

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
                <td class="trial-code">{{ $account->code }}</td>
                <td class="trial-name" @if($allLevels ?? false) style="padding-right: {{ max(0, ((int) $account->level - (int) $summaryLevel) * 18) }}px" @endif>{{ $account->name }}</td>
                @if($allLevels ?? false)
                    <td class="text-center">{{ $account->level }}</td>
                @endif

                @if($includeFirstBalance)
                    <td class="trial-number">{{ number_format($firstCreditorValue, 2) }}</td>
                    <td class="trial-number">{{ number_format($firstDebtorValue, 2) }}</td>
                @endif

                <td class="trial-number">{{ number_format($sumPeriodCreditorValue, 2) }}</td>
                <td class="trial-number">{{ number_format($sumPeriodDebtorValue, 2) }}</td>

                <td class="trial-number">{{ number_format($creditorBalance, 2) }}</td>
                <td class="trial-number">{{ number_format($debtorBalance, 2) }}</td>
            </tr>
        @endforeach

        <tr class="trial-total-row">
            <td class="text-center" colspan="{{ ($allLevels ?? false) ? 3 : 2 }}"><strong>{{ __('finance::finance.Total') }}</strong></td>

            @if($includeFirstBalance)
                <td class="trial-number"><strong>{{ number_format($totalFirstCreditorValue, 2) }}</strong></td>
                <td class="trial-number"><strong>{{ number_format($totalFirstDebtorValue, 2) }}</strong></td>
            @endif

            <td class="trial-number"><strong>{{ number_format($totalCreditorBalance, 2) }}</strong></td>
            <td class="trial-number"><strong>{{ number_format($totalDebtorBalance, 2) }}</strong></td>

            <td class="trial-number"><strong>{{ number_format($sumOfCreditorBalance, 2) }}</strong></td>
            <td class="trial-number"><strong>{{ number_format($sumOfDebtorBalance, 2) }}</strong></td>
        </tr>
        </tbody>
    </table>
    </div>
@else
    <div role="alert" class="trial-result__empty"> {{__('finance::finance.no_data_found')}}</div>
@endif
