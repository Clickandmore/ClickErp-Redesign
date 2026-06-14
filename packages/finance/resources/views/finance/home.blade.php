@extends('click.layout.indexs.index')

@php
    $money = fn($value) => number_format((float) ($value ?? 0), 2) . ' ' . __('finance::finance.sar');
    $counts = $finance_counts ?? [];
    $monthLabels = [
        __('finance::finance.january'),
        __('finance::finance.february'),
        __('finance::finance.march'),
        __('finance::finance.april'),
        __('finance::finance.may'),
        __('finance::finance.june'),
        __('finance::finance.july'),
        __('finance::finance.august'),
        __('finance::finance.september'),
        __('finance::finance.october'),
        __('finance::finance.november'),
        __('finance::finance.december'),
    ];
@endphp

@section('page-title')
    {{ __('finance::finance.finance_dashboard') }}
@endsection

@section('content')
    <style>
        .finance-dashboard {
            --finance-border: #e8edf3;
            --finance-muted: #667085;
            --finance-text: #1f2937;
            --finance-soft: #f7f9fc;
            --finance-blue: #2563eb;
            --finance-green: #059669;
            --finance-red: #dc2626;
            --finance-amber: #d97706;
            color: var(--finance-text);
        }

        .finance-dashboard .finance-hero,
        .finance-dashboard .finance-panel,
        .finance-dashboard .finance-stat {
            background: #fff;
            border: 1px solid var(--finance-border);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
        }

        .finance-dashboard .finance-hero {
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .finance-dashboard .finance-hero h3,
        .finance-dashboard .finance-panel h5 {
            margin: 0;
            font-weight: 700;
        }

        .finance-dashboard .finance-muted {
            color: var(--finance-muted);
            font-size: .88rem;
        }

        .finance-dashboard .finance-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .finance-dashboard .finance-actions .btn,
        .finance-dashboard .quick-link {
            border-radius: 6px;
        }

        .finance-dashboard .finance-stat {
            padding: 18px;
            height: 100%;
        }

        .finance-dashboard .finance-stat .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-bottom: 14px;
        }

        .finance-dashboard .finance-stat h4 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .finance-dashboard .finance-stat small {
            color: var(--finance-muted);
        }

        .finance-dashboard .finance-panel {
            padding: 18px;
            height: 100%;
        }

        .finance-dashboard .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--finance-border);
            margin-bottom: 14px;
        }

        .finance-dashboard .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .finance-dashboard .quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            color: var(--finance-text);
            background: var(--finance-soft);
            border: 1px solid var(--finance-border);
        }

        .finance-dashboard .quick-link i {
            color: var(--finance-blue);
        }

        .finance-dashboard .quick-link:hover {
            background: #eef4ff;
            color: var(--finance-blue);
        }

        .finance-dashboard .mini-metric {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--finance-border);
        }

        .finance-dashboard .mini-metric:last-child {
            border-bottom: 0;
        }

        .finance-dashboard .badge-soft {
            background: var(--finance-soft);
            border: 1px solid var(--finance-border);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: .78rem;
            color: var(--finance-muted);
        }

        @media (max-width: 767px) {
            .finance-dashboard .finance-hero {
                align-items: flex-start;
                flex-direction: column;
            }

            .finance-dashboard .finance-actions {
                width: 100%;
            }

            .finance-dashboard .finance-actions .btn {
                flex: 1 1 140px;
            }
        }
    </style>

    <div class="container-fluid py-3 finance-dashboard" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <div class="finance-hero">
            <div>
                <span class="badge-soft">{{ __('finance::finance.finance_dashboard') }}</span>
                <h3 class="mt-2">{{ __('finance::finance.finance_dashboard_heading') }}</h3>
                <div class="finance-muted mt-1">{{ __('finance::finance.finance_dashboard_hint') }}</div>
            </div>
            <div class="finance-actions">
                <a href="{{ route('finance.constraints.pending') }}" class="btn btn-warning">
                    <i class="fa-solid fa-check-double me-1"></i> {{ __('finance::finance.pending_constraints') }}
                </a>
                <a href="{{ route('finance.bonds-qabd.create') }}" class="btn btn-success">
                    <i class="fa-solid fa-circle-plus me-1"></i> {{ __('finance::finance.receipt_voucher') }}
                </a>
                <a href="{{ route('finance.bonds-sarf.create') }}" class="btn btn-outline-danger">
                    <i class="fa-solid fa-circle-minus me-1"></i> {{ __('finance::finance.payment_voucher') }}
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-3 col-md-6">
                <div class="finance-stat">
                    <span class="stat-icon" style="background: var(--finance-green);"><i
                            class="fa-solid fa-arrow-trend-up"></i></span>
                    <small>{{ __('finance::finance.total_revenues') }}</small>
                    <h4>{{ $money($revenues ?? 0) }}</h4>
                    <div class="finance-muted mt-2">{{ __('finance::finance.this_month') }}:
                        {{ $money($month_revenues ?? 0) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="finance-stat">
                    <span class="stat-icon" style="background: var(--finance-red);"><i
                            class="fa-solid fa-arrow-trend-down"></i></span>
                    <small>{{ __('finance::finance.total_expenses') }}</small>
                    <h4>{{ $money($expenses ?? 0) }}</h4>
                    <div class="finance-muted mt-2">{{ __('finance::finance.this_month') }}:
                        {{ $money($month_expenses ?? 0) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="finance-stat">
                    <span class="stat-icon" style="background: var(--finance-blue);"><i
                            class="fa-solid fa-scale-balanced"></i></span>
                    <small>{{ __('finance::finance.net_movement') }}</small>
                    <h4 class="{{ ($net_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $money($net_profit ?? 0) }}</h4>
                    <div class="finance-muted mt-2">{{ __('finance::finance.month_net') }}: {{ $money($month_net ?? 0) }}
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="finance-stat">
                    <span class="stat-icon" style="background: var(--finance-amber);"><i
                            class="fa-solid fa-file-signature"></i></span>
                    <small>{{ __('finance::finance.accounting_entries') }}</small>
                    <h4>{{ number_format($counts['constraints'] ?? 0) }}</h4>
                    <div class="finance-muted mt-2">{{ __('finance::finance.awaiting_approval') }}:
                        {{ number_format($counts['pending_constraints'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-xl-3 col-md-6">
                <div class="finance-stat">
                    <span class="stat-icon" style="background: var(--finance-blue);"><i
                            class="fa-solid fa-file-contract"></i></span>
                    <small>{{ __('finance::finance.rental_contracts_total_value') }}</small>
                    <h4>{{ $money($rental_contracts_total_value ?? 0) }}</h4>
                    <div class="finance-muted mt-2">{{ __('finance::finance.rental_contracts_value_hint') }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-8">
                <div class="finance-panel">
                    <div class="panel-head">
                        <div>
                            <h5>{{ __('finance::finance.revenue_expense_movement') }}</h5>
                            <div class="finance-muted">{{ __('finance::finance.current_year_voucher_data') }}</div>
                        </div>
                        <a href="{{ route('finance.account-statement.index') }}"
                            class="btn btn-sm btn-outline-primary">{{ __('finance::finance.account_statement') }}</a>
                    </div>
                    <div id="financeMonthlyChart"></div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="finance-panel">
                    <div class="panel-head">
                        <h5>{{ __('finance::finance.files_summary') }}</h5>
                        <a href="{{ route('finance.accounts.index') }}"
                            class="btn btn-sm btn-outline-secondary">{{ __('finance::finance.directory') }}</a>
                    </div>
                    <div class="mini-metric">
                        <span>{{ __('finance::finance.accounts') }}</span><strong>{{ number_format($counts['accounts'] ?? 0) }}</strong>
                    </div>
                    <div class="mini-metric">
                        <span>{{ __('finance::finance.leaf_accounts') }}</span><strong>{{ number_format($counts['leaf_accounts'] ?? 0) }}</strong>
                    </div>
                    <div class="mini-metric">
                        <span>{{ __('finance::finance.clients') }}</span><strong>{{ number_format($counts['clients'] ?? 0) }}</strong>
                    </div>
                    <div class="mini-metric">
                        <span>{{ __('finance::finance.suppliers') }}</span><strong>{{ number_format($counts['suppliers'] ?? 0) }}</strong>
                    </div>
                    <div class="mini-metric">
                        <span>{{ __('finance::finance.cost_centers') }}</span><strong>{{ number_format($counts['cost_centers'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-xl-4">
                <div class="finance-panel">
                    <div class="panel-head">
                        <h5>{{ __('finance::finance.quick_links') }}</h5>
                    </div>
                    <div class="quick-grid">
                        <a class="quick-link" href="{{ route('finance.clients.index') }}"><i
                                class="fa-solid fa-users"></i><span>{{ __('finance::finance.clients') }}</span></a>
                        <a class="quick-link" href="{{ route('finance.suppliers.index') }}"><i
                                class="fa-solid fa-user-tie"></i><span>{{ __('finance::finance.suppliers') }}</span></a>
                        <a class="quick-link" href="{{ route('finance.constraints.index') }}"><i
                                class="fa-solid fa-file-invoice"></i><span>{{ __('finance::finance.constraints') }}</span></a>
                        <a class="quick-link" href="{{ route('finance.bonds-qabd.index') }}"><i
                                class="fa-solid fa-receipt"></i><span>{{ __('finance::finance.receipt_vouchers') }}</span></a>
                        <a class="quick-link" href="{{ route('finance.bonds-sarf.index') }}"><i
                                class="fa-solid fa-money-bill-transfer"></i><span>{{ __('finance::finance.payment_vouchers') }}</span></a>
                        <a class="quick-link" href="{{ route('finance.trail-balance.index') }}"><i
                                class="fa-solid fa-chart-column"></i><span>{{ __('finance::finance.trial_balance') }}</span></a>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="finance-panel">
                    <div class="panel-head">
                        <h5>{{ __('finance::finance.recent_vouchers') }}</h5>
                    </div>
                    @forelse ($recent_bonds ?? [] as $bond)
                        <div class="mini-metric">
                            <span>
                                {{ $bond->type === \Nami\Finance\Enums\BondTypes::Qabd->value ? __('finance::finance.receipt') : __('finance::finance.payment') }}
                                #{{ $bond->code ?? $bond->id }}
                                <div class="finance-muted">
                                    {{ optional($bond->debitAccount)->name ?? (optional($bond->creditAccount)->name ?? __('finance::finance.not_specified')) }}
                                </div>
                            </span>
                            <strong>{{ $money($bond->value ?? 0) }}</strong>
                        </div>
                    @empty
                        <div class="finance-muted">{{ __('finance::finance.no_vouchers_recorded') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="col-xl-4">
                <div class="finance-panel">
                    <div class="panel-head">
                        <h5>{{ __('finance::finance.recent_entries') }}</h5>
                        <a href="{{ route('finance.constraints.pending') }}"
                            class="badge-soft">{{ __('finance::finance.pending') }}:
                            {{ number_format($counts['pending_constraints'] ?? 0) }}</a>
                    </div>
                    @forelse ($recent_constraints ?? [] as $constraint)
                        <div class="mini-metric">
                            <span>
                                {{ __('finance::finance.entry') }} #{{ $constraint->code ?? $constraint->id }}
                                <div class="finance-muted">
                                    {{ \Illuminate\Support\Str::limit($constraint->statement ?? __('finance::finance.no_statement'), 44) }}
                                </div>
                            </span>
                            <strong>{{ $money($constraint->value ?? 0) }}</strong>
                        </div>
                    @empty
                        <div class="finance-muted">{{ __('finance::finance.no_entries_recorded') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartElement = document.querySelector('#financeMonthlyChart');
            if (!chartElement || typeof ApexCharts === 'undefined') {
                return;
            }

            const chart = new ApexCharts(chartElement, {
                series: [{
                        name: @json(__('finance::finance.revenues')),
                        data: @json($revenues_value_month ?? [])
                    },
                    {
                        name: @json(__('finance::finance.expenses')),
                        data: @json($expenses_value_month ?? [])
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 330,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '42%'
                    }
                },
                colors: ['#059669', '#dc2626'],
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: @json($monthLabels)
                },
                yaxis: {
                    labels: {
                        formatter: value => Number(value || 0).toLocaleString()
                    }
                },
                tooltip: {
                    y: {
                        formatter: value =>
                            `${Number(value || 0).toLocaleString()} {{ __('finance::finance.sar') }}`
                    }
                },
                legend: {
                    position: 'bottom'
                },
                grid: {
                    borderColor: '#edf2f7'
                }
            });
            chart.render();
        });
    </script>
@endsection
