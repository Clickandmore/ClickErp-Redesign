@extends('click.layout.indexs.index')

@php
    $money = fn($value) => number_format((float) ($value ?? 0), 2) . ' ' . __('finance::finance.sar');
    $counts = $finance_counts ?? [];
    $months = [
        __('finance::finance.january'), __('finance::finance.february'), __('finance::finance.march'),
        __('finance::finance.april'), __('finance::finance.may'), __('finance::finance.june'),
        __('finance::finance.july'), __('finance::finance.august'), __('finance::finance.september'),
        __('finance::finance.october'), __('finance::finance.november'), __('finance::finance.december'),
    ];
    $changeBadge = function ($value, $inverse = false) {
        if ($value === null) {
            return ['class' => 'neutral', 'icon' => 'fa-minus', 'text' => __('finance::finance.no_comparison_data')];
        }
        $positive = $inverse ? $value <= 0 : $value >= 0;
        return [
            'class' => $positive ? 'positive' : 'negative',
            'icon' => $value >= 0 ? 'fa-arrow-up' : 'fa-arrow-down',
            'text' => number_format(abs($value), 1) . '% ' . __('finance::finance.vs_previous_month'),
        ];
    };
    $ops = $operational ?? [];
    $incomeChange = $changeBadge($income_change ?? null);
    $expenseChange = $changeBadge($expense_change ?? null, true);
@endphp

@section('page-title')
    {{ __('finance::finance.finance_dashboard') }}
@endsection

@section('content')
    <style>
        .finance-hub {
            --navy: #132238;
            --navy-2: #1d3553;
            --blue: #2878ff;
            --cyan: #12a7b7;
            --green: #119b70;
            --red: #df5a63;
            --amber: #e5a43b;
            --ink: #172033;
            --muted: #738095;
            --line: #e7ebf1;
            --soft: #f4f7fb;
            color: var(--ink);
        }

        .finance-hub a { text-decoration: none; }
        .finance-hub .hub-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            padding: 28px 30px;
            color: #fff;
            background: linear-gradient(125deg, var(--navy) 0%, var(--navy-2) 70%, #244c73 100%);
            border-radius: 18px;
            box-shadow: 0 18px 42px rgba(19, 34, 56, .16);
        }

        .finance-hub .hub-hero::after {
            content: '';
            position: absolute;
            inset-inline-end: -60px;
            top: -115px;
            width: 300px;
            height: 300px;
            border: 55px solid rgba(255, 255, 255, .045);
            border-radius: 50%;
        }

        .finance-hub .hero-copy, .finance-hub .hero-actions { position: relative; z-index: 1; }
        .finance-hub .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .03em;
            color: #a9d5ff;
            margin-bottom: 8px;
        }
        .finance-hub .hub-hero h2 { font-size: 1.65rem; font-weight: 800; margin: 0 0 7px; }
        .finance-hub .hub-hero p { margin: 0; color: #c9d6e5; max-width: 700px; }
        .finance-hub .as-of { display: inline-flex; align-items: center; gap: 7px; margin-top: 14px; color: #dce8f5; font-size: .82rem; }
        .finance-hub .hero-actions { display: flex; gap: 9px; flex-wrap: wrap; justify-content: flex-end; }
        .finance-hub .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 9px;
            color: #fff;
            background: rgba(255,255,255,.08);
            font-size: .84rem;
            font-weight: 700;
            backdrop-filter: blur(5px);
        }
        .finance-hub .hero-btn.primary { color: var(--navy); background: #fff; border-color: #fff; }
        .finance-hub .hero-btn:hover { transform: translateY(-1px); color: #fff; background: rgba(255,255,255,.16); }
        .finance-hub .hero-btn.primary:hover { color: var(--navy); background: #f1f7ff; }

        .finance-hub .metric-card, .finance-hub .hub-panel {
            height: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 9px 28px rgba(26, 39, 61, .045);
        }
        .finance-hub .metric-card { position: relative; overflow: hidden; padding: 19px 20px 17px; }
        .finance-hub .metric-card::before { content: ''; position: absolute; inset-inline-start: 0; top: 0; bottom: 0; width: 4px; background: var(--metric-color); }
        .finance-hub .metric-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .finance-hub .metric-label { color: var(--muted); font-size: .82rem; font-weight: 650; }
        .finance-hub .metric-value { margin-top: 7px; font-size: 1.42rem; line-height: 1.35; font-weight: 850; color: var(--ink); }
        .finance-hub .metric-icon { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 11px; display: grid; place-items: center; color: var(--metric-color); background: #f1f5f9; }
        .finance-hub .metric-foot { margin-top: 13px; padding-top: 12px; border-top: 1px dashed var(--line); display: flex; justify-content: space-between; gap: 8px; color: var(--muted); font-size: .75rem; }
        .finance-hub .metric-foot strong { color: var(--ink); }

        .finance-hub .hub-panel { padding: 20px; }
        .finance-hub .panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 17px; }
        .finance-hub .panel-title { margin: 0; font-size: 1rem; font-weight: 800; }
        .finance-hub .panel-subtitle { color: var(--muted); font-size: .77rem; margin-top: 4px; }
        .finance-hub .panel-link { display: inline-flex; align-items: center; gap: 6px; color: var(--blue); font-size: .77rem; font-weight: 700; white-space: nowrap; }
        .finance-hub .panel-link:hover { color: #155fcf; }

        .finance-hub .period-summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 9px; }
        .finance-hub .period-cell { padding: 11px 12px; background: var(--soft); border-radius: 10px; }
        .finance-hub .period-cell span { display: block; color: var(--muted); font-size: .72rem; margin-bottom: 4px; }
        .finance-hub .period-cell strong { font-size: .91rem; }
        .finance-hub .positive-text { color: var(--green) !important; }
        .finance-hub .negative-text { color: var(--red) !important; }

        .finance-hub .balance-list { display: flex; flex-direction: column; }
        .finance-hub .balance-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .finance-hub .balance-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .finance-hub .balance-row:first-child { padding-top: 1px; }
        .finance-hub .account-name { font-size: .83rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .finance-hub .account-code { color: var(--muted); font-size: .7rem; margin-top: 3px; }
        .finance-hub .balance-value { font-size: .83rem; font-weight: 800; white-space: nowrap; }
        .finance-hub .empty-state { display: grid; place-items: center; min-height: 150px; color: var(--muted); text-align: center; font-size: .82rem; }
        .finance-hub .empty-state i { display: block; font-size: 1.6rem; margin-bottom: 9px; opacity: .45; }

        .finance-hub .cash-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--line); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .finance-hub .cash-item { background: #fff; padding: 14px 15px; }
        .finance-hub .cash-item span { display: block; color: var(--muted); font-size: .72rem; margin-bottom: 5px; }
        .finance-hub .cash-item strong { font-size: .92rem; }

        .finance-hub .entry-row { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; gap: 11px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--line); }
        .finance-hub .entry-row:last-child { border-bottom: 0; }
        .finance-hub .entry-icon { width: 38px; height: 38px; display: grid; place-items: center; color: var(--blue); background: #edf4ff; border-radius: 10px; }
        .finance-hub .entry-title { font-size: .81rem; font-weight: 750; }
        .finance-hub .entry-meta { color: var(--muted); font-size: .69rem; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .finance-hub .entry-value { text-align: end; font-size: .8rem; font-weight: 800; white-space: nowrap; }

        .finance-hub .quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .finance-hub .quick-action { display: flex; align-items: center; gap: 10px; padding: 12px; color: var(--ink); border: 1px solid var(--line); border-radius: 11px; background: #fff; font-size: .78rem; font-weight: 700; }
        .finance-hub .quick-action i { width: 30px; height: 30px; display: grid; place-items: center; color: var(--blue); background: #edf4ff; border-radius: 8px; }
        .finance-hub .quick-action:hover { color: var(--blue); border-color: #b9d1fb; background: #f8fbff; transform: translateY(-1px); }

        .finance-hub .status-chip { display: inline-flex; align-items: center; gap: 5px; border-radius: 99px; padding: 4px 8px; font-size: .68rem; font-weight: 750; }
        .finance-hub .status-chip.positive { color: #087451; background: #e9f8f2; }
        .finance-hub .status-chip.negative { color: #b33b44; background: #fff0f1; }
        .finance-hub .status-chip.neutral { color: #697386; background: #f0f2f5; }

        @media (max-width: 1199px) {
            .finance-hub .quick-grid { grid-template-columns: repeat(2, 1fr); }
            .finance-hub .cash-strip { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 767px) {
            .finance-hub .hub-hero { align-items: flex-start; flex-direction: column; padding: 22px 20px; border-radius: 14px; }
            .finance-hub .hero-actions { width: 100%; justify-content: flex-start; }
            .finance-hub .hero-btn { flex: 1 1 125px; justify-content: center; }
            .finance-hub .metric-value { font-size: 1.2rem; }
            .finance-hub .period-summary { grid-template-columns: 1fr; }
            .finance-hub .cash-strip, .finance-hub .quick-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="container-fluid py-3 finance-hub" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <section class="hub-hero mb-3">
            <div class="hero-copy">
                <div class="eyebrow"><i class="fa-solid fa-chart-line"></i> {{ __('finance::finance.financial_command_center') }}</div>
                <h2>{{ __('finance::finance.finance_dashboard_heading_v2') }}</h2>
                <p>{{ __('finance::finance.finance_dashboard_hint_v2') }}</p>
                <div class="as-of"><i class="fa-regular fa-clock"></i> {{ __('finance::finance.balances_as_of') }} {{ $as_of_date ?? now()->toDateString() }}</div>
            </div>
            <div class="hero-actions">
                <a class="hero-btn" href="{{ route('finance.procurement.index') }}"><i class="fa-solid fa-cart-shopping"></i>المشتريات</a>
                <a class="hero-btn primary" href="{{ route('finance.add-constraint.create') }}"><i class="fa-solid fa-plus"></i> {{ __('finance::finance.new_journal_entry') }}</a>
                <a class="hero-btn" href="{{ route('finance.bonds-qabd.create') }}"><i class="fa-solid fa-arrow-down"></i> {{ __('finance::finance.receipt_voucher') }}</a>
                <a class="hero-btn" href="{{ route('finance.bonds-sarf.create') }}"><i class="fa-solid fa-arrow-up"></i> {{ __('finance::finance.payment_voucher') }}</a>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <article class="metric-card" style="--metric-color: var(--blue)">
                    <div class="metric-top"><div><div class="metric-label">{{ __('finance::finance.available_liquidity') }}</div><div class="metric-value">{{ $money($liquidity_total ?? 0) }}</div></div><div class="metric-icon"><i class="fa-solid fa-building-columns"></i></div></div>
                    <div class="metric-foot"><span>{{ __('finance::finance.bank_and_cash_accounts') }}</span><strong>{{ number_format($bank_accounts_count ?? 0) }}</strong></div>
                </article>
            </div>
            <div class="col-xl-3 col-md-6">
                <article class="metric-card" style="--metric-color: var(--cyan)">
                    <div class="metric-top"><div><div class="metric-label">{{ __('finance::finance.customer_receivables') }}</div><div class="metric-value">{{ $money($clients_receivable ?? 0) }}</div></div><div class="metric-icon"><i class="fa-solid fa-user-clock"></i></div></div>
                    <div class="metric-foot"><span>{{ __('finance::finance.customers_with_balance') }}</span><strong>{{ number_format($clients_with_balance ?? 0) }}</strong></div>
                </article>
            </div>
            <div class="col-xl-3 col-md-6">
                <article class="metric-card" style="--metric-color: var(--amber)">
                    <div class="metric-top"><div><div class="metric-label">{{ __('finance::finance.supplier_payables') }}</div><div class="metric-value">{{ $money($suppliers_payable ?? 0) }}</div></div><div class="metric-icon"><i class="fa-solid fa-truck-field"></i></div></div>
                    <div class="metric-foot"><span>{{ __('finance::finance.suppliers_with_balance') }}</span><strong>{{ number_format($suppliers_with_balance ?? 0) }}</strong></div>
                </article>
            </div>
            <div class="col-xl-3 col-md-6">
                <article class="metric-card" style="--metric-color: {{ ($month_net ?? 0) >= 0 ? 'var(--green)' : 'var(--red)' }}">
                    <div class="metric-top"><div><div class="metric-label">{{ __('finance::finance.month_operating_result') }}</div><div class="metric-value {{ ($month_net ?? 0) >= 0 ? 'positive-text' : 'negative-text' }}">{{ $money($month_net ?? 0) }}</div></div><div class="metric-icon"><i class="fa-solid fa-scale-balanced"></i></div></div>
                    <div class="metric-foot"><span>{{ __('finance::finance.approved_entries_this_month') }}</span><strong>{{ number_format($counts['month_constraints'] ?? 0) }}</strong></div>
                </article>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-8">
                <section class="hub-panel">
                    <div class="panel-head">
                        <div><h5 class="panel-title">{{ __('finance::finance.monthly_financial_performance') }}</h5><div class="panel-subtitle">{{ __('finance::finance.recognized_income_expense_hint') }}</div></div>
                        <a class="panel-link" href="{{ route('finance.profit-loss.index') }}">{{ __('finance::finance.view_profit_loss') }} <i class="fa-solid fa-arrow-left-long"></i></a>
                    </div>
                    <div class="period-summary">
                        <div class="period-cell"><span>{{ __('finance::finance.month_income') }}</span><strong class="positive-text">{{ $money($month_income ?? 0) }}</strong><div class="mt-1"><small class="status-chip {{ $incomeChange['class'] }}"><i class="fa-solid {{ $incomeChange['icon'] }}"></i>{{ $incomeChange['text'] }}</small></div></div>
                        <div class="period-cell"><span>{{ __('finance::finance.month_expenses') }}</span><strong class="negative-text">{{ $money($month_expenses ?? 0) }}</strong><div class="mt-1"><small class="status-chip {{ $expenseChange['class'] }}"><i class="fa-solid {{ $expenseChange['icon'] }}"></i>{{ $expenseChange['text'] }}</small></div></div>
                        <div class="period-cell"><span>{{ __('finance::finance.today_result') }}</span><strong class="{{ ($today_net ?? 0) >= 0 ? 'positive-text' : 'negative-text' }}">{{ $money($today_net ?? 0) }}</strong><div class="account-code">{{ __('finance::finance.income') }} {{ $money($today_income ?? 0) }} · {{ __('finance::finance.expenses') }} {{ $money($today_expenses ?? 0) }}</div></div>
                    </div>
                    <div id="financeMonthlyChart"></div>
                </section>
            </div>
            <div class="col-xl-4">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.daily_movement') }}</h5><div class="panel-subtitle">{{ __('finance::finance.last_14_days') }}</div></div></div>
                    <div id="financeDailyChart"></div>
                </section>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-12">
                <section class="hub-panel">
                    <div class="panel-head">
                        <div><h5 class="panel-title">{{ __('finance::finance.operational_summary') }}</h5><div class="panel-subtitle">{{ __('finance::finance.ops_summary_subtitle') }}</div></div>
                        <a class="panel-link" href="{{ route('finance.operational-summary.index') }}">{{ __('finance::finance.operational_summary') }} <i class="fa-solid fa-arrow-left-long"></i></a>
                    </div>
                    <div class="cash-strip">
                        <div class="cash-item"><span>{{ __('finance::finance.ops_kpi_total_sales') }}</span><strong>{{ $money($ops['sales_invoiced'] ?? 0) }}</strong><div class="account-code">{{ __('finance::finance.ops_collection_rate') }}: {{ ($ops['collection_rate'] ?? null) === null ? '—' : number_format($ops['collection_rate'], 1) . '%' }}</div></div>
                        <div class="cash-item"><span>{{ __('finance::finance.ops_kpi_receivables') }}</span><strong class="negative-text">{{ $money($ops['receivables_total'] ?? 0) }}</strong><div class="account-code">{{ number_format($ops['receivables_documents'] ?? 0) }} {{ __('finance::finance.ops_documents') }}</div></div>
                        <div class="cash-item"><span>{{ __('finance::finance.ops_kpi_musaned_net') }}</span><strong class="positive-text">{{ $money($ops['musaned_commission_net'] ?? 0) }}</strong><div class="account-code">{{ number_format($ops['musaned_transfers'] ?? 0) }} {{ __('finance::finance.ops_transfers') }}</div></div>
                        <div class="cash-item"><span>{{ __('finance::finance.ops_kpi_delegation_net') }}</span><strong class="positive-text">{{ $money($ops['delegation_net'] ?? 0) }}</strong><div class="account-code">{{ number_format($ops['delegation_count'] ?? 0) }} {{ __('finance::finance.ops_delegations_count') }} — {{ $money($ops['delegation_revenue'] ?? 0) }}</div></div>
                        <div class="cash-item"><span>{{ __('finance::finance.ops_kpi_offices_payable') }}</span><strong class="negative-text">{{ $money($ops['offices_payable'] ?? 0) }}</strong><div class="account-code">{{ __('finance::finance.ops_offices_hint') }}</div></div>
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-4 col-lg-6">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.bank_cash_balances') }}</h5><div class="panel-subtitle">{{ __('finance::finance.net_posted_balance') }}</div></div><a class="panel-link" href="{{ route('finance.treasury-and-banks-balances.index') }}">{{ __('finance::finance.all_balances') }}</a></div>
                    <div class="balance-list">
                        @forelse(($liquidity_balances ?? collect())->take(6) as $account)
                            <div class="balance-row"><div><div class="account-name">{{ $account->name }}</div><div class="account-code">#{{ $account->code }}</div></div><div class="balance-value {{ $account->signed_balance >= 0 ? 'positive-text' : 'negative-text' }}">{{ $money($account->signed_balance) }}</div></div>
                        @empty
                            <div class="empty-state"><div><i class="fa-solid fa-building-columns"></i>{{ __('finance::finance.no_posted_balances') }}</div></div>
                        @endforelse
                    </div>
                </section>
            </div>
            <div class="col-xl-4 col-lg-6">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.largest_customer_balances') }}</h5><div class="panel-subtitle">{{ __('finance::finance.amounts_due_from_customers') }}</div></div><a class="panel-link" href="{{ route('finance.clients-balances.index') }}">{{ __('finance::finance.all_customers') }}</a></div>
                    <div class="balance-list">
                        @forelse($top_clients ?? [] as $account)
                            <div class="balance-row"><div><div class="account-name">{{ $account->name }}</div><div class="account-code">#{{ $account->code }}</div></div><div class="balance-value">{{ $money($account->signed_balance) }}</div></div>
                        @empty
                            <div class="empty-state"><div><i class="fa-solid fa-user-check"></i>{{ __('finance::finance.no_customer_balances') }}</div></div>
                        @endforelse
                    </div>
                </section>
            </div>
            <div class="col-xl-4 col-lg-6">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.largest_supplier_balances') }}</h5><div class="panel-subtitle">{{ __('finance::finance.amounts_due_to_suppliers') }}</div></div><a class="panel-link" href="{{ route('finance.suppliers-balances.index') }}">{{ __('finance::finance.all_suppliers') }}</a></div>
                    <div class="balance-list">
                        @forelse($top_suppliers ?? [] as $account)
                            <div class="balance-row"><div><div class="account-name">{{ $account->name }}</div><div class="account-code">#{{ $account->code }}</div></div><div class="balance-value">{{ $money(abs($account->signed_balance)) }}</div></div>
                        @empty
                            <div class="empty-state"><div><i class="fa-solid fa-truck-ramp-box"></i>{{ __('finance::finance.no_supplier_balances') }}</div></div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-7">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.cash_collection_payment') }}</h5><div class="panel-subtitle">{{ __('finance::finance.voucher_movement_not_income_hint') }}</div></div><a class="panel-link" href="{{ route('finance.cash-movements.index') }}">{{ __('finance::finance.cash_movement_report') }}</a></div>
                    <div class="cash-strip">
                        <div class="cash-item"><span>{{ __('finance::finance.today_collections') }}</span><strong class="positive-text">{{ $money($today_collections ?? 0) }}</strong></div>
                        <div class="cash-item"><span>{{ __('finance::finance.today_payments') }}</span><strong class="negative-text">{{ $money($today_payments ?? 0) }}</strong></div>
                        <div class="cash-item"><span>{{ __('finance::finance.month_collections') }}</span><strong>{{ $money($month_collections ?? 0) }}</strong></div>
                        <div class="cash-item"><span>{{ __('finance::finance.month_payments') }}</span><strong>{{ $money($month_payments ?? 0) }}</strong></div>
                    </div>
                    <div class="quick-grid mt-3">
                        <a class="quick-action" href="{{ route('finance.account-statement.index') }}"><i class="fa-solid fa-book-open"></i>{{ __('finance::finance.account_statement') }}</a>
                        <a class="quick-action" href="{{ route('finance.trail-balance.index') }}"><i class="fa-solid fa-scale-unbalanced-flip"></i>{{ __('finance::finance.trial_balance') }}</a>
                        <a class="quick-action" href="{{ route('finance.clients.index') }}"><i class="fa-solid fa-users"></i>{{ __('finance::finance.clients') }}</a>
                        <a class="quick-action" href="{{ route('finance.procurement.index') }}"><i class="fa-solid fa-cart-shopping"></i>المشتريات</a>
                        <a class="quick-action" href="{{ route('finance.suppliers.index') }}"><i class="fa-solid fa-truck"></i>{{ __('finance::finance.suppliers') }}</a>
                        <a class="quick-action" href="{{ route('finance.operational-summary.index') }}"><i class="fa-solid fa-chart-pie"></i>{{ __('finance::finance.operational_summary') }}</a>
                    </div>
                </section>
            </div>
            <div class="col-xl-5">
                <section class="hub-panel">
                    <div class="panel-head"><div><h5 class="panel-title">{{ __('finance::finance.latest_approved_entries') }}</h5><div class="panel-subtitle">{{ __('finance::finance.today_entries') }}: {{ number_format($counts['today_constraints'] ?? 0) }}</div></div><a class="panel-link" href="{{ route('finance.constraints.pending') }}">{{ __('finance::finance.pending') }} ({{ number_format($counts['pending_constraints'] ?? 0) }})</a></div>
                    @forelse($recent_constraints ?? [] as $constraint)
                        <a class="entry-row" href="{{ route('finance.constraints.show', $constraint->id) }}">
                            <div class="entry-icon"><i class="fa-solid fa-file-lines"></i></div>
                            <div><div class="entry-title">{{ __('finance::finance.entry') }} #{{ $constraint->code ?? $constraint->id }}</div><div class="entry-meta">{{ \Illuminate\Support\Str::limit($constraint->statement ?: __('finance::finance.no_statement'), 58) }}</div></div>
                            <div class="entry-value">{{ $money($constraint->value ?? 0) }}<div class="account-code">{{ $constraint->date ? \Carbon\Carbon::parse($constraint->date)->format('Y-m-d') : '—' }}</div></div>
                        </a>
                    @empty
                        <div class="empty-state"><div><i class="fa-regular fa-folder-open"></i>{{ __('finance::finance.no_entries_recorded') }}</div></div>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;
            const currency = @json(__('finance::finance.sar'));
            const common = {
                chart: { toolbar: { show: false }, fontFamily: 'inherit' },
                dataLabels: { enabled: false },
                grid: { borderColor: '#edf0f5', strokeDashArray: 3 },
                tooltip: { y: { formatter: value => `${Number(value || 0).toLocaleString()} ${currency}` } },
                yaxis: { labels: { formatter: value => Number(value || 0).toLocaleString() } },
                legend: { position: 'top', horizontalAlign: @json(app()->getLocale() === 'ar' ? 'right' : 'left') }
            };

            const monthlyElement = document.querySelector('#financeMonthlyChart');
            if (monthlyElement) new ApexCharts(monthlyElement, {
                ...common,
                series: [
                    { name: @json(__('finance::finance.income')), data: @json($monthly_income ?? []) },
                    { name: @json(__('finance::finance.expenses')), data: @json($monthly_expenses ?? []) }
                ],
                chart: { ...common.chart, type: 'bar', height: 295 },
                colors: ['#119b70', '#df5a63'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                xaxis: { categories: @json($months), axisBorder: { show: false }, axisTicks: { show: false } }
            }).render();

            const dailyElement = document.querySelector('#financeDailyChart');
            if (dailyElement) new ApexCharts(dailyElement, {
                ...common,
                series: [
                    { name: @json(__('finance::finance.income')), data: @json($daily_income ?? []) },
                    { name: @json(__('finance::finance.expenses')), data: @json($daily_expenses ?? []) }
                ],
                chart: { ...common.chart, type: 'area', height: 348 },
                colors: ['#119b70', '#df5a63'],
                stroke: { curve: 'smooth', width: 2.5 },
                fill: { type: 'gradient', gradient: { opacityFrom: .24, opacityTo: .03 } },
                xaxis: { categories: @json($daily_labels ?? []), axisBorder: { show: false }, axisTicks: { show: false }, labels: { rotate: -45 } }
            }).render();
        });
    </script>
@endsection
