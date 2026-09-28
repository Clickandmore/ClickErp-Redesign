@php
    $filters = $filters ?? [];
    $isPrint = $isPrint ?? false;
    $pagination = $data instanceof \Nami\Finance\Services\StatementPaginator ? $data : null;
    $rows = $pagination ? $pagination->getCollection() : collect($data ?? []);
    $showOtherAccount = ($show_other_account ?? data_get($filters, 'other_account', 'no')) === 'yes';
    $colspan = $showOtherAccount ? 6 : 5;
    $showDollarEquivalent = $showDollarEquivalent ?? false;
    $usdExchangeRate = 3.75;
    $generatedAt = now(config('app.timezone', 'Asia/Riyadh'));
    $generatedAtDate = $generatedAt->format('d/m/Y');
    $generatedAtTime = $generatedAt->format('h:i A');
    $statementPermissions = [
        'finance.account-statement-read',
        'finance.account-statement.index-read',
        'finance.account-statement-print-read',
        'finance::finance.account-statement-print-read',
    ];
    $canOpenAccountStatement = auth('admin')->check()
        && (
            auth('admin')->user()->admin_type == \App\Enums\AdminTypeisEnum::Developer->value
            || count(array_intersect($statementPermissions, getUserPermissions())) > 0
        );
    $buildStatementUrl = function ($accountId) use ($filters, $generatedAt) {
        return route('finance.account-statement.index', [
            'account_id' => $accountId,
            'from_date' => data_get($filters, 'from_date'),
            'to_date' => data_get($filters, 'to_date') ?: $generatedAt->format('Y-m-d'),
            'type' => 'both',
            'other_account' => 'yes',
        ]);
    };
    $resolveBalanceLabel = function ($balance, $accountStatus = 'debtor') {
        if ($balance > 0) {
            return $accountStatus === 'creditor'
                ? __('finance::finance.creditor')
                : __('finance::finance.debtor');
        }

        if ($balance < 0) {
            return $accountStatus === 'creditor'
                ? __('finance::finance.debtor')
                : __('finance::finance.creditor');
        }

        return 'متوازن';
    };
    $resolveBalanceClass = function ($balance, $accountStatus = 'debtor') use ($resolveBalanceLabel) {
        if (abs((float) $balance) < 0.01) {
            return 'is-neutral';
        }

        return $resolveBalanceLabel($balance, $accountStatus) === __('finance::finance.creditor')
            ? 'is-creditor'
            : 'is-debtor';
    };
    $formatMoney = fn ($value) => number_format((float) $value, 2);
    $formatDollarValue = fn ($value) => $usdExchangeRate > 0 ? number_format(abs((float) $value) / $usdExchangeRate, 2) : '0.00';
    $formatStatementMoney = function ($value) use ($showDollarEquivalent, $formatMoney, $formatDollarValue) {
        if (! $showDollarEquivalent) {
            return $formatMoney($value);
        }

        return '<span class="statement-currency-stack">'
            . '<span class="statement-currency-row statement-currency-row--sar">'
            . '<b>' . $formatMoney($value) . '</b><em>ريال</em>'
            . '</span>'
            . '<span class="statement-currency-row statement-currency-row--usd">'
            . '<b>' . $formatDollarValue($value) . '</b><em>دولار</em>'
            . '</span>'
            . '</span>';
    };

    $accountStatus = $firstBalance->status ?? optional($rows->first())->status ?? 'debtor';
    $openingDebtor = round((float) ($firstBalance->sum_debtor_value ?? 0), 2);
    $openingCreditor = round((float) ($firstBalance->sum_creditor_value ?? 0), 2);
    $hasOpeningBalance = abs($openingDebtor) > 0.01 || abs($openingCreditor) > 0.01;
    $openingBalance = 0.0;

    if ($hasOpeningBalance) {
        $openingBalance = ($accountStatus === 'debtor')
            ? $openingDebtor - $openingCreditor
            : $openingCreditor - $openingDebtor;
        $openingBalance = round($openingBalance, 2);

        if (abs($openingBalance) < 0.01) {
            $openingBalance = 0.0;
        }
    }

    $runningBalance = $openingBalance + ($pagination->previous->balance ?? 0);
    $totalDebtor = $openingDebtor;
    $totalCreditor = $openingCreditor;
    $preparedRows = [];

    foreach ($rows as $index => $row) {
        $rowDebtorValue = round((float) $row->debtor_value, 2);
        $rowCreditorValue = round((float) $row->creditor_value, 2);
        $rowAccountStatus = $row->status ?? $accountStatus;
        $rowBalance = ($rowAccountStatus === 'debtor')
            ? $rowDebtorValue - $rowCreditorValue
            : $rowCreditorValue - $rowDebtorValue;

        $rowBalance = round($rowBalance, 2);
        if (abs($rowBalance) < 0.01) {
            $rowBalance = 0.0;
        }

        $runningBalance = round($runningBalance + $rowBalance, 2);
        $totalDebtor = round($totalDebtor + $rowDebtorValue, 2);
        $totalCreditor = round($totalCreditor + $rowCreditorValue, 2);

        if (abs($runningBalance) < 0.01) {
            $runningBalance = 0.0;
        }

        $detailCounterAccounts = data_get($otherAccount ?? [], 'details.' . $row->constraint_detail_id . '.accounts');
        // Client/supplier statements still provide the legacy constraint-level
        // shape, so retain that as a compatibility fallback for this shared view.
        $detailCounterAccounts ??= data_get($otherAccount ?? [], $row->constraint_id . '.accounts', []);

        $preparedRows[] = [
            'index' => $index + ($pagination ? $pagination->firstItem() : 1),
            'date' => $row->date,
            'formatted_date' => filled($row->date) ? \Carbon\Carbon::parse($row->date)->format('d/m/Y') : '-',
            'constraint_id' => $row->constraint_id,
            'constraint_number' => filled($row->constraint_code ?? null) ? $row->constraint_code : $row->constraint_id,
            'constraint_url' => route('finance.add-constraint.show', ['add_constraint' => $row->constraint_id]),
            'type' => getEnumValue('constraint_types', $row->constraints_type),
            'statement' => $row->statement,
            'debtor_value' => $rowDebtorValue,
            'creditor_value' => $rowCreditorValue,
            'running_balance' => $runningBalance,
            'running_balance_abs' => abs($runningBalance),
            'running_balance_label' => $resolveBalanceLabel($runningBalance, $rowAccountStatus),
            'running_balance_class' => $resolveBalanceClass($runningBalance, $rowAccountStatus),
            'user_name' => $row->name,
            'other_accounts' => collect($detailCounterAccounts),
            'is_zero' => $rowDebtorValue === 0.0 && $rowCreditorValue === 0.0,
        ];
    }

    if ($pagination) {
        $totalDebtor = $openingDebtor + $pagination->totals->debtor;
        $totalCreditor = $openingCreditor + $pagination->totals->creditor;
        $runningBalance = round($openingBalance + $pagination->totals->balance, 2);
    }

    $periodDebtor = round($totalDebtor - $openingDebtor, 2);
    $periodCreditor = round($totalCreditor - $openingCreditor, 2);
    $periodBalance = round($runningBalance - $openingBalance, 2);

    $hasData = $hasOpeningBalance || count($preparedRows) > 0;
    $periodFrom = data_get($filters, 'from_date');
    $periodTo = data_get($filters, 'to_date');
    $periodLabel = 'كل الفترات';

    if ($periodFrom && $periodTo) {
        $periodLabel = 'من ' . \Carbon\Carbon::parse($periodFrom)->format('d/m/Y') . ' إلى ' . \Carbon\Carbon::parse($periodTo)->format('d/m/Y');
    } elseif ($periodFrom) {
        $periodLabel = 'من ' . \Carbon\Carbon::parse($periodFrom)->format('d/m/Y');
    } elseif ($periodTo) {
        $periodLabel = 'حتى ' . \Carbon\Carbon::parse($periodTo)->format('d/m/Y');
    }

    $typeLabel = match (data_get($filters, 'type', 'both')) {
        'debtor' => __('finance::finance.debtor'),
        'creditor' => __('finance::finance.creditor'),
        default => __('finance::finance.All'),
    };

    $closingBalanceDirection = $resolveBalanceLabel($runningBalance, $accountStatus);
    $closingBalanceClass = $resolveBalanceClass($runningBalance, $accountStatus);
    $openingDirection = $resolveBalanceLabel($openingBalance, $accountStatus);
    $openingBalanceClass = $resolveBalanceClass($openingBalance, $accountStatus);
@endphp

<div class="statement-report {{ $isPrint ? 'statement-report--print' : '' }}">
    <div class="statement-report-top">
        <div class="statement-report-heading">
            <span class="statement-report-tag">{{ $reportTitle ?? __('finance::finance.account-statement') }}</span>
            <h2>{{ $accountName ?: 'اختر حسابًا لعرض كشفه' }}</h2>
            <p>
                @if ($hasData)
                    نسخة أوضح للمعاينة والطباعة، مع رصيد أول المدة وإجماليات الفترة وربط مباشر إلى القيد وكشف الحسابات المقابلة.
                @else
                    لم يتم العثور على حركات مطابقة ضمن الفلاتر الحالية.
                @endif
            </p>
        </div>

        <div class="statement-report-meta">
            <div class="statement-meta-pill">
                <span>الفترة</span>
                <strong>{{ $periodLabel }}</strong>
            </div>
            <div class="statement-meta-pill">
                <span>{{ __('finance::finance.Transaction Type') }}</span>
                <strong>{{ $typeLabel }}</strong>
            </div>
            <div class="statement-meta-pill">
                <span>{{ __('finance::finance.Show Counter Account') }}</span>
                <strong>{{ $showOtherAccount ? 'مفعّل' : 'غير مفعّل' }}</strong>
            </div>
            <div class="statement-meta-pill">
                <span>تاريخ الإصدار</span>
                <strong>{{ $generatedAtDate }}</strong>
            </div>
        </div>
    </div>

    <div class="statement-summary-grid">
        <article class="statement-summary-card">
            <span>عدد القيود</span>
            <strong>{{ $pagination ? $pagination->total() : count($preparedRows) }}</strong>
            <small>خلال الفترة المحددة فقط</small>
        </article>

        <article class="statement-summary-card is-accent">
            <span>{{ __('finance::finance.Opening Balance') }}</span>
            <strong>{!! $formatStatementMoney(abs($openingBalance)) !!}</strong>
            <small>{{ $hasOpeningBalance ? $openingDirection : 'لا يوجد' }}</small>
        </article>

        <article class="statement-summary-card is-debtor">
            <span>{{ __('finance::finance.debtor') }}</span>
            <strong>{!! $formatStatementMoney($totalDebtor) !!}</strong>
            <small>إجمالي الحركات المدينة</small>
        </article>

        <article class="statement-summary-card is-creditor">
            <span>{{ __('finance::finance.creditor') }}</span>
            <strong>{!! $formatStatementMoney($totalCreditor) !!}</strong>
            <small>إجمالي الحركات الدائنة</small>
        </article>

        <article class="statement-summary-card {{ $closingBalanceClass }}">
            <span>{{ __('finance::finance.Balance') }}</span>
            <strong>{!! $formatStatementMoney(abs($runningBalance)) !!}</strong>
            <small>{{ $closingBalanceDirection }}</small>
        </article>

        <article class="statement-summary-card">
            <span>وقت الإصدار</span>
            <strong>{{ $generatedAtDate }}</strong>
            <small>{{ $generatedAtTime }}</small>
        </article>
    </div>

    @if ($hasData)
        <div class="statement-table-card {{ $showDollarEquivalent ? 'statement-table-card--currency' : '' }}">
            <div class="statement-table-head">
                <div>
                    <h3>تفاصيل الحركات</h3>
                    <p>يمكنك الضغط على رقم الحركة لعرض القيد، وعلى الحساب المقابل لفتح كشف حسابه مباشرة بنفس الفترة.</p>
                </div>
                @if (! $isPrint)
                    <span class="statement-table-note">يمكنك طباعة النتيجة الحالية أو تصديرها مباشرة إلى Excel.</span>
                @endif
            </div>

            <div class="table-responsive statement-table-scroll">
                <table class="table statement-table {{ $showOtherAccount ? 'statement-table--with-counter' : '' }} {{ $showDollarEquivalent ? 'statement-table--currency' : '' }}" id="simpleTable4">
                    <thead>
                        <tr>
                            <th scope="col" class="statement-col-index">#</th>
                            <th scope="col" class="statement-col-date">{{ __('finance::finance.Transaction Date') }}</th>
                            <th scope="col" class="statement-col-number">{{ __('finance::finance.Transaction Number') }}</th>
                            <th scope="col" class="statement-col-type">{{ __('finance::finance.Transaction Type') }}</th>
                            @if ($showOtherAccount)
                                <th scope="col" class="statement-col-account">{{ __('finance::finance.Counter Account') }}</th>
                            @endif
                            <th scope="col" class="statement-col-statement">{{ __('finance::finance.statement') }}</th>
                            <th scope="col" class="statement-col-money">
                                {{ __('finance::finance.debtor') }}
                                @if ($showDollarEquivalent)
                                    <small class="statement-th-note">دولار / ريال</small>
                                @endif
                            </th>
                            <th scope="col" class="statement-col-money">
                                {{ __('finance::finance.creditor') }}
                                @if ($showDollarEquivalent)
                                    <small class="statement-th-note">دولار / ريال</small>
                                @endif
                            </th>
                            <th scope="col" class="statement-col-balance">
                                {{ __('finance::finance.Balance') }}
                                @if ($showDollarEquivalent)
                                    <small class="statement-th-note">دولار / ريال</small>
                                @endif
                            </th>
                            <th scope="col" class="statement-col-user">{{ __('finance::finance.user') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($hasOpeningBalance)
                            <tr class="statement-row statement-row--opening">
                                <td colspan="{{ $colspan }}" class="text-center">{{ __('finance::finance.Opening Balance') }}</td>
                                <td class="statement-money-cell">{!! $formatStatementMoney($openingDebtor) !!}</td>
                                <td class="statement-money-cell">{!! $formatStatementMoney($openingCreditor) !!}</td>
                                <td class="statement-balance-cell">
                                    <span class="statement-balance-pill {{ $openingBalanceClass }}">
                                        <strong>{!! $formatStatementMoney(abs($openingBalance)) !!}</strong>
                                        <small>{{ $hasOpeningBalance ? $openingDirection : 'متوازن' }}</small>
                                    </span>
                                </td>
                                <td>-</td>
                            </tr>
                        @endif

                        @if ($pagination && $pagination->currentPage() > 1)
                            <tr class="statement-row statement-row--opening">
                                <td colspan="{{ $colspan + 2 }}" class="text-center">الرصيد المرحّل من الصفحة السابقة</td>
                                <td class="statement-balance-cell">{!! $formatStatementMoney($openingBalance + $pagination->previous->balance) !!}</td>
                                <td>-</td>
                            </tr>
                        @endif

                        @foreach ($preparedRows as $item)
                            <tr class="statement-row {{ $item['is_zero'] ? 'statement-row--muted' : '' }}">
                                <td class="statement-cell--index">{{ $item['index'] }}</td>
                                <td class="text-nowrap">{{ $item['formatted_date'] }}</td>
                                <td class="text-nowrap statement-cell--number">
                                    <a href="{{ $item['constraint_url'] }}" target="_blank" rel="noopener"
                                        class="statement-entry-link">
                                        #{{ $item['constraint_number'] }}
                                    </a>
                                </td>
                                <td class="statement-cell--type">{{ $item['type'] }}</td>
                                @if ($showOtherAccount)
                                    <td>
                                        <div class="statement-other-accounts">
                                            @forelse ($item['other_accounts'] as $account)
                                                @if ($canOpenAccountStatement && filled($account->id))
                                                    <a href="{{ $buildStatementUrl($account->id) }}" target="_blank"
                                                        rel="noopener" class="statement-other-account"
                                                        title="فتح كشف حساب {{ $account->name }}">
                                                        <span>{{ $account->name }}</span>
                                                        <i class="fa fa-external-link"></i>
                                                    </a>
                                                @else
                                                    <span class="statement-other-account">{{ $account->name }}</span>
                                                @endif
                                            @empty
                                                <span class="statement-other-account statement-other-account--empty">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                @endif
                                <td class="statement-cell--wide">{{ $item['statement'] }}</td>
                                <td class="statement-money-cell">{!! $formatStatementMoney($item['debtor_value']) !!}</td>
                                <td class="statement-money-cell">{!! $formatStatementMoney($item['creditor_value']) !!}</td>
                                <td class="statement-balance-cell">
                                    <span class="statement-balance-pill {{ $item['running_balance_class'] }}">
                                        <strong>{!! $formatStatementMoney($item['running_balance_abs']) !!}</strong>
                                        <small>{{ $item['running_balance_label'] }}</small>
                                    </span>
                                </td>
                                <td class="statement-cell--user">{{ $item['user_name'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="statement-row statement-row--period-total">
                            <td colspan="{{ $colspan }}" class="text-center">إجمالي الفترة</td>
                            <td class="statement-money-cell">{!! $formatStatementMoney($periodDebtor) !!}</td>
                            <td class="statement-money-cell">{!! $formatStatementMoney($periodCreditor) !!}</td>
                            <td class="statement-balance-cell">
                                <span class="statement-balance-pill {{ $resolveBalanceClass($periodBalance, $accountStatus) }}">
                                    <strong>{!! $formatStatementMoney(abs($periodBalance)) !!}</strong>
                                    <small>{{ $resolveBalanceLabel($periodBalance, $accountStatus) }}</small>
                                </span>
                            </td>
                            <td>-</td>
                        </tr>
                        <tr class="statement-row statement-row--total">
                            <td colspan="{{ $colspan }}" class="text-center">{{ __('finance::finance.Balance') }}</td>
                            <td class="statement-money-cell">{!! $formatStatementMoney($totalDebtor) !!}</td>
                            <td class="statement-money-cell">{!! $formatStatementMoney($totalCreditor) !!}</td>
                            <td class="statement-balance-cell">
                                <span class="statement-balance-pill {{ $closingBalanceClass }}">
                                    <strong>{!! $formatStatementMoney(abs($runningBalance)) !!}</strong>
                                    <small>{{ $closingBalanceDirection }}</small>
                                </span>
                            </td>
                            <td>-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="statement-empty-state">
            <div class="statement-empty-icon">
                <i class="fa fa-file-text-o" aria-hidden="true"></i>
            </div>
            <h3>لا توجد بيانات للعرض</h3>
            <p>
                @if (! data_get($filters, 'account_id'))
                    اختر الحساب المطلوب أولًا ثم نفّذ البحث.
                @else
                    جرّب تغيير الفترة الزمنية أو نوع الحركة لعرض بيانات أخرى.
                @endif
            </p>
        </div>
    @endif
</div>
@if (! $isPrint)
    @include('finance::finance.account_statement.partials.pagination')
@endif
