@php
    $accountsCount = $accounts->count();
    $fromDate = \Carbon\Carbon::parse($filters['from_date']);
    $toDate = \Carbon\Carbon::parse($filters['to_date']);
    $monthsCount = $fromDate->diffInMonths($toDate) + 1;
    $accountNameHeader = ($reportType ?? 'accounts') === 'external_agents'
        ? 'الوكيل الخارجي'
        : __('finance::finance.account_name');
    $summaryRows = ($reportType ?? 'accounts') === 'accounts'
        ? $accounts->filter(fn ($account) => empty($account->parent_id))
        : $accounts;
    if ($summaryRows->isEmpty()) {
        $summaryRows = $accounts;
    }
    $periodDebtorTotal = (float) $summaryRows->sum('period_debtor');
    $periodCreditorTotal = (float) $summaryRows->sum('period_creditor');
    $periodMovementTotal = $periodDebtorTotal + $periodCreditorTotal;
    $formatMoney = fn ($value) => number_format((float) $value, 2);
    $periods = [
        'opening' => ['title' => 'أول الفترة', 'subtitle' => 'قبل: ' . $filters['from_date'], 'class' => 'opening'],
        'period' => ['title' => 'مجموع الفترة', 'subtitle' => $filters['from_date'] . ' ← ' . $filters['to_date'], 'class' => 'current'],
        'after' => ['title' => 'بعد الفترة', 'subtitle' => 'بعد: ' . $filters['to_date'], 'class' => 'after'],
        'closing' => ['title' => 'الرصيد الختامي', 'subtitle' => 'جميع الحركات', 'class' => 'closing'],
    ];
@endphp

<div class="balance-report-heading mb-3">
    <h4>حركات الأرصدة</h4>
    <p>
        تقرير حركة أرصدة يشمل {{ number_format($monthsCount) }} شهرًا،
        يبدأ من {{ $filters['from_date'] }} إلى {{ $filters['to_date'] }}
    </p>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#6d28d9">
            <div class="balance-summary-label">الحسابات ذات الحركة</div>
            <div class="balance-summary-value">{{ number_format($accountsCount) }}</div>
            <span class="balance-summary-note">تشمل الحسابات التجميعية والفرعية</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#2563eb">
            <div class="balance-summary-label">مدين خلال الفترة</div>
            <div class="balance-summary-value">{{ $formatMoney($periodDebtorTotal) }}</div>
            <span class="balance-summary-note">من {{ $filters['from_date'] }} إلى {{ $filters['to_date'] }}</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#f59e0b">
            <div class="balance-summary-label">دائن خلال الفترة</div>
            <div class="balance-summary-value">{{ $formatMoney($periodCreditorTotal) }}</div>
            <span class="balance-summary-note">من {{ $filters['from_date'] }} إلى {{ $filters['to_date'] }}</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#0f766e">
            <div class="balance-summary-label">حجم حركة الفترة</div>
            <div class="balance-summary-value">{{ $formatMoney($periodMovementTotal) }}</div>
            <span class="balance-summary-note">مجموع المدين والدائن</span>
        </div>
    </div>
</div>

<div class="balance-table-card">
    @if ($accountsCount > 0)
        <div class="table-responsive balance-table-scroll">
            <table class="table table-hover" id="balancesTable" style="width:100%">
                <thead>
                    <tr>
                        <th rowspan="2">#</th>
                        <th rowspan="2">{{ __('finance::finance.account_code') }}</th>
                        <th rowspan="2">{{ $accountNameHeader }}</th>
                        <th rowspan="2">الحساب الأب</th>
                        @foreach ($periods as $period)
                            <th colspan="4" class="balance-period-heading balance-period-heading--{{ $period['class'] }}">
                                <strong>{{ $period['title'] }}</strong><br>
                                <small>{{ $period['subtitle'] }}</small>
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($periods as $periodKey => $period)
                            <th class="balance-period-subhead balance-period-border">مدين</th>
                            <th class="balance-period-subhead">دائن</th>
                            <th class="balance-period-subhead">رصيد</th>
                            <th class="balance-period-subhead">حركة الرصيد</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $row)
                        @php
                            $statementUrl = route('finance.account-statement.index', [
                                'account_id' => $row->id,
                                'from_date' => $filters['from_date'],
                                'to_date' => $filters['to_date'],
                                'type' => 'both',
                                'other_account' => 'no',
                            ]);
                        @endphp
                        <tr class="{{ $row->has_children ? 'balance-parent-row' : '' }}">
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                @if ($row->has_children)
                                    <span class="balance-account-link">{{ $row->code }}</span>
                                @else
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener"
                                       class="balance-account-link" title="فتح كشف الحساب">
                                        {{ $row->code }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if ($row->has_children)<span class="balance-level-mark"></span>@endif
                                @if ($row->has_children)
                                    <span>{{ $row->name }}</span>
                                @else
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener" style="color:inherit">
                                        {{ $row->name }}
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if ($row->parent_name)
                                    <span class="text-muted">{{ $row->parent_code }}</span>
                                    <span>{{ $row->parent_name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @foreach ($periods as $periodKey => $period)
                                @php($movementStatus = $row->{$periodKey . '_status'})
                                <td class="text-left balance-period-border">{{ $formatMoney($row->{$periodKey . '_debtor'}) }}</td>
                                <td class="text-left">{{ $formatMoney($row->{$periodKey . '_creditor'}) }}</td>
                                <td class="text-left font-weight-bold">{{ $formatMoney($row->{$periodKey . '_balance'}) }}</td>
                                <td class="text-center">
                                    @if ($movementStatus)
                                        <span class="balance-direction balance-direction--{{ $movementStatus }}">
                                            {{ $movementStatus === 'debtor' ? 'مدين' : 'دائن' }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-5 px-3">
            <div class="mb-3" style="color:#9ca3af"><i class="fa fa-exchange fa-3x"></i></div>
            <h5>لا توجد حركات أرصدة</h5>
            <p class="text-muted mb-0">لا توجد حركات مرتبطة بالحسابات ضمن الفترة أو قبلها أو بعدها.</p>
        </div>
    @endif
</div>
