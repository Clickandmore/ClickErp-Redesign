@php
    $accountsCount = (int) ($movementSummary['accounts_count'] ?? $accounts->count());
    $fromDate = \Carbon\Carbon::parse($filters['from_date']);
    $toDate = \Carbon\Carbon::parse($filters['to_date']);
    $monthsCount = $fromDate->diffInMonths($toDate) + 1;
    $accountNameHeader = ($reportType ?? 'accounts') === 'external_agents'
        ? 'الوكيل الخارجي'
        : __('finance::finance.account_name');
    $periodDebtorTotal = (float) ($movementSummary['period_debtor'] ?? 0);
    $periodCreditorTotal = (float) ($movementSummary['period_creditor'] ?? 0);
    $periodMovementTotal = (float) ($movementSummary['period_movement'] ?? ($periodDebtorTotal + $periodCreditorTotal));
    $formatMoney = fn ($value) => number_format((float) $value, 2);
    $periods = [
        'opening' => ['title' => 'أول الفترة', 'subtitle' => 'قبل ' . $filters['from_date']],
        'period' => ['title' => 'حركة الفترة', 'subtitle' => $filters['from_date'] . ' ← ' . $filters['to_date']],
        'after' => ['title' => 'بعد الفترة', 'subtitle' => 'بعد ' . $filters['to_date']],
        'closing' => ['title' => 'الرصيد الختامي', 'subtitle' => 'جميع الحركات'],
    ];
@endphp

<div class="balance-report-heading mb-3">
    <h4>حركات الأرصدة</h4>
    <p>
        تقرير موحّد يعرض {{ number_format($accountsCount) }} حسابًا في صفحة واحدة،
        خلال {{ number_format($monthsCount) }} شهرًا من {{ $filters['from_date'] }} إلى {{ $filters['to_date'] }}
    </p>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#6d28d9">
            <div class="balance-summary-label">الحسابات ذات الحركة</div>
            <div class="balance-summary-value">{{ number_format($accountsCount) }}</div>
            <span class="balance-summary-note">جميعها معروضة في الجدول أدناه</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#2563eb">
            <div class="balance-summary-label">مدين خلال الفترة</div>
            <div class="balance-summary-value">{{ $formatMoney($periodDebtorTotal) }}</div>
            <span class="balance-summary-note">إجمالي التقرير دون تكرار الفروع</span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="balance-summary-card" style="color:#f59e0b">
            <div class="balance-summary-label">دائن خلال الفترة</div>
            <div class="balance-summary-value">{{ $formatMoney($periodCreditorTotal) }}</div>
            <span class="balance-summary-note">إجمالي التقرير دون تكرار الفروع</span>
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
        <div class="balance-period-switcher balances-no-print">
            @foreach ($periods as $periodKey => $period)
                <button type="button"
                        class="balance-period-button {{ $periodKey === 'period' ? 'is-active' : '' }}"
                        data-period-index="{{ $loop->index }}" data-subtitle="{{ $period['subtitle'] }}">
                    {{ $period['title'] }}
                </button>
            @endforeach
        </div>
        <div class="balance-active-period">
            المعروض الآن: <strong data-active-period-label>حركة الفترة — {{ $periods['period']['subtitle'] }}</strong>
        </div>
        <div class="table-responsive balance-table-scroll">
            <table class="table table-hover" id="balancesTable" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('finance::finance.account_code') }}</th>
                        <th>{{ $accountNameHeader }}</th>
                        <th>الحساب الأب</th>
                        <th class="text-left">مدين</th>
                        <th class="text-left">دائن</th>
                        <th class="text-left">الرصيد</th>
                        <th class="text-center">حركة الرصيد</th>
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
                            $movementData = [];
                            foreach (array_keys($periods) as $periodKey) {
                                $movementData[] = [
                                    round((float) $row->{$periodKey . '_debtor'}, 2),
                                    round((float) $row->{$periodKey . '_creditor'}, 2),
                                    round((float) $row->{$periodKey . '_balance'}, 2),
                                    $row->{$periodKey . '_status'} === 'debtor' ? 'd' : ($row->{$periodKey . '_status'} === 'creditor' ? 'c' : ''),
                                ];
                            }
                        @endphp
                        <tr class="balance-movement-row {{ $row->has_children ? 'balance-parent-row' : '' }}"
                            data-m='@json($movementData)'>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td>
                                @if ($row->has_children)
                                    <span class="balance-account-link">{{ $row->code }}</span>
                                @else
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener"
                                       class="balance-account-link" title="فتح كشف الحساب">{{ $row->code }}</a>
                                @endif
                            </td>
                            <td>
                                @if ($row->has_children)<span class="balance-level-mark"></span>@endif
                                @if ($row->has_children)
                                    <span>{{ $row->name }}</span>
                                @else
                                    <a href="{{ $statementUrl }}" target="_blank" rel="noopener" style="color:inherit">{{ $row->name }}</a>
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
                            <td class="text-left balance-cell-debtor">{{ $formatMoney($row->period_debtor) }}</td>
                            <td class="text-left balance-cell-creditor">{{ $formatMoney($row->period_creditor) }}</td>
                            <td class="text-left font-weight-bold balance-cell-total">{{ $formatMoney($row->period_balance) }}</td>
                            <td class="text-center balance-cell-status">
                                @if ($row->period_status)
                                    <span class="balance-direction balance-direction--{{ $row->period_status }}">
                                        {{ $row->period_status === 'debtor' ? 'مدين' : 'دائن' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
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
