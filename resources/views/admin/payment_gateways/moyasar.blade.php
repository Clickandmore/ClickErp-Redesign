@extends('admin.layout.indexs.index')

@section('page-title', __('payment_gateways.moyasar.title'))

@section('style')
    @include('admin.payment_gateways._styles')
    <style>
        .pg-page { --pg-brand: #4338ca; --pg-brand-soft: #eef2ff; --pg-gradient: linear-gradient(135deg, #1e1b4b 0%, #4338ca 55%, #6d28d9 100%); }
    </style>
@endsection

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $route = 'moyasar-payments.index';
    $serviceIcons = ['rental' => 'fa-file-signature', 'renewal' => 'fa-calendar-plus', 'transfer' => 'fa-right-left', 'hourly' => 'fa-clock', 'app' => 'fa-mobile-alt'];
    $statusTone = ['paid' => 'ok', 'partially_refunded' => 'info', 'refunded' => 'info', 'pending' => 'warn', 'cancelled' => ''];
    $currency = __('payment_gateways.chart.currency');
@endphp
<div class="container-fluid pg-page">
    <div class="pg-hero">
        <div class="pg-hero-row">
            <span class="pg-hero-icon"><i class="fa fa-credit-card"></i></span>
            <div>
                <h1>{{ __('payment_gateways.moyasar.title') }}</h1>
                <p>{{ __('payment_gateways.moyasar.subtitle') }}</p>
            </div>
            <div class="pg-hero-side">
                <span class="pg-hero-period"><i class="fa fa-calendar"></i> <bdi dir="ltr">{{ $period->label() }}</bdi></span>
                @if (@AdminGuardHasPerm(\App\PermKeys::PAYMENTS_MOYASAR_EXPORT))
                    @include('admin.payment_gateways._export_buttons', ['exportRoute' => 'moyasar-payments.export'])
                @endif
            </div>
        </div>
    </div>

    <div class="pg-card pg-filters">
        <form method="GET" action="{{ route($route) }}">
            @include('admin.payment_gateways._period_fields')
            <div>
                <label for="pg-service">{{ __('payment_gateways.filters.service') }}</label>
                <select id="pg-service" name="service" class="form-select">
                    <option value="">{{ __('payment_gateways.filters.all') }}</option>
                    @foreach ($services as $service)
                        <option value="{{ $service }}" {{ ($filters['service'] ?? '') === $service ? 'selected' : '' }}>{{ __('payment_gateways.service.' . $service) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="pg-status">{{ __('payment_gateways.filters.status') }}</label>
                <select id="pg-status" name="status" class="form-select">
                    <option value="">{{ __('payment_gateways.filters.all') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ __('payment_gateways.status.' . $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pg-wide">
                <label for="pg-search">{{ __('payment_gateways.filters.search') }}</label>
                <input type="search" id="pg-search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('payment_gateways.filters.search_placeholder') }}">
            </div>
            <div class="pg-actions">
                <button class="pg-btn pg-btn-brand flex-fill"><i class="fa fa-filter"></i> {{ __('payment_gateways.filters.filter') }}</button>
                <a href="{{ route($route) }}" class="pg-btn pg-btn-ghost" title="{{ __('payment_gateways.period.reset') }}"><i class="fa fa-rotate-left"></i></a>
            </div>
        </form>
        @include('admin.payment_gateways._quick_periods')
    </div>

    <div class="pg-kpis">
        <div class="pg-card pg-kpi">
            <span class="pg-kpi-icon"><i class="fa fa-file-invoice-dollar"></i></span>
            <div><small>{{ __('payment_gateways.kpi.billed') }}</small><strong>{{ $money($summary['billed']) }} <span>{{ $currency }}</span></strong>
                <em>{{ __('payment_gateways.kpi.billed_hint', ['count' => $summary['count']]) }}</em></div>
        </div>
        <div class="pg-card pg-kpi pg-tone-ok">
            <span class="pg-kpi-icon"><i class="fa fa-circle-check"></i></span>
            <div><small>{{ __('payment_gateways.kpi.collected') }}</small><strong>{{ $money($summary['collected']) }} <span>{{ $currency }}</span></strong>
                <em>{{ __('payment_gateways.kpi.collected_hint', ['count' => $summary['paid_count']]) }}</em></div>
        </div>
        <div class="pg-card pg-kpi pg-tone-info">
            <span class="pg-kpi-icon"><i class="fa fa-scale-balanced"></i></span>
            <div><small>{{ __('payment_gateways.kpi.net') }}</small><strong>{{ $money($summary['net']) }} <span>{{ $currency }}</span></strong>
                <em>{{ __('payment_gateways.kpi.net_hint', ['amount' => $money($summary['refunded'])]) }}</em></div>
        </div>
        <div class="pg-card pg-kpi pg-tone-warn">
            <span class="pg-kpi-icon"><i class="fa fa-hourglass-half"></i></span>
            <div><small>{{ __('payment_gateways.kpi.pending') }}</small><strong>{{ $money($summary['pending']) }} <span>{{ $currency }}</span></strong>
                <em>{{ __('payment_gateways.kpi.pending_hint', ['count' => $summary['pending_count']]) }}</em></div>
        </div>
        <div class="pg-card pg-kpi">
            <span class="pg-kpi-icon"><i class="fa fa-percent"></i></span>
            <div><small>{{ __('payment_gateways.kpi.conversion') }}</small><strong>{{ $summary['conversion'] }}%</strong>
                <em>{{ __('payment_gateways.kpi.conversion_hint') }} · {{ __('payment_gateways.kpi.average') }}: {{ $money($summary['average']) }}</em></div>
        </div>
    </div>

    <div class="pg-grid pg-grid-2-1">
        <div class="pg-card">
            <div class="pg-card-head"><h3><i class="fa fa-chart-area"></i> {{ __('payment_gateways.chart.trend') }}</h3></div>
            <div class="pg-card-body"><div id="pg-trend" class="pg-chart"></div></div>
        </div>
        <div class="pg-card">
            <div class="pg-card-head"><h3><i class="fa fa-layer-group"></i> {{ __('payment_gateways.chart.by_service') }}</h3></div>
            <div class="pg-card-body"><div id="pg-services" class="pg-chart"></div></div>
        </div>
    </div>

    <div class="pg-grid pg-grid-2-1">
        <div class="pg-card">
            <div class="pg-card-head"><h3><i class="fa fa-chart-column"></i> {{ __('payment_gateways.chart.daily') }}</h3><small><bdi dir="ltr">{{ $period->label() }}</bdi></small></div>
            <div class="pg-card-body"><div id="pg-daily" class="pg-chart"></div></div>
        </div>
        <div class="pg-card">
            <div class="pg-card-head"><h3><i class="fa fa-chart-pie"></i> {{ __('payment_gateways.chart.by_status') }}</h3></div>
            <div class="pg-card-body"><div id="pg-statuses" class="pg-chart"></div></div>
        </div>
    </div>

    <div class="pg-card">
        <div class="pg-card-head">
            <h3><i class="fa fa-list"></i> {{ __('payment_gateways.moyasar.table') }} <span class="pg-pill">{{ $payments->total() }}</span></h3>
            <small>{{ __('payment_gateways.moyasar.table_hint') }}</small>
        </div>
        <div class="pg-card-body pg-table-wrap">
            @if ($payments->isEmpty())
                <div class="pg-empty"><i class="fa fa-inbox"></i>{{ __('payment_gateways.table.empty') }}</div>
            @else
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th>{{ __('payment_gateways.table.contract_number') }}</th>
                            <th>{{ __('payment_gateways.table.service') }}</th>
                            <th>{{ __('payment_gateways.table.customer') }}</th>
                            <th>{{ __('payment_gateways.table.amount') }}</th>
                            <th>{{ __('payment_gateways.table.refunded') }}</th>
                            <th>{{ __('payment_gateways.table.status') }}</th>
                            <th>{{ __('payment_gateways.table.created') }}</th>
                            <th>{{ __('payment_gateways.table.paid_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $row)
                            <tr>
                                <td>
                                    @if ($row['url'])
                                        <a class="pg-ref" href="{{ $row['url'] }}">{{ $row['reference'] }}</a>
                                    @else
                                        <span class="pg-ref">{{ $row['reference'] }}</span>
                                    @endif
                                    @if ($row['gateway_id'])<span class="pg-sub pg-ltr" title="{{ __('payment_gateways.moyasar.gateway_id') }}">{{ \Illuminate\Support\Str::limit($row['gateway_id'], 14) }}</span>@endif
                                </td>
                                <td><span class="pg-service"><i class="fa {{ $serviceIcons[$row['service']] ?? 'fa-circle' }}"></i>{{ __('payment_gateways.service.' . $row['service']) }}</span></td>
                                <td>{{ $row['customer'] ?: '—' }}@if ($row['phone'])<span class="pg-sub pg-ltr">{{ $row['phone'] }}</span>@endif</td>
                                <td class="pg-num">{{ $money($row['amount']) }}</td>
                                <td class="pg-num">{{ $row['refunded'] > 0 ? $money($row['refunded']) : '—' }}</td>
                                <td><span class="pg-pill {{ $statusTone[$row['status']] ?? '' }}">{{ __('payment_gateways.status.' . $row['status']) }}</span></td>
                                <td class="pg-num">{{ $row['created_at']?->format('Y-m-d') ?? '—' }}<span class="pg-sub">{{ $row['created_at']?->format('H:i') }}</span></td>
                                <td class="pg-num">{{ $row['paid_at']?->format('Y-m-d') ?? '—' }}<span class="pg-sub">{{ $row['paid_at']?->format('H:i') }}</span></td>
                                <td>
                                    @if ($row['url'])
                                        <a href="{{ $row['url'] }}" class="pg-btn pg-btn-ghost" style="min-height:32px;padding:0 10px" title="{{ __('payment_gateways.table.open') }}"><i class="fa fa-arrow-up-right-from-square"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if ($payments->hasPages())
            <div class="pg-foot">{{ $payments->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
</div>
@endsection

@push('js')
    @include('admin.payment_gateways._charts', ['palette' => ['#4338ca', '#16a34a', '#e11d48', '#0ea5e9', '#f59e0b']])
    <script>
        (function () {
            var charts = @json($charts);
            PgCharts.bar('pg-trend', charts.trend, 330, { width: '62%' });
            PgCharts.bar('pg-daily', charts.daily, 300, { width: '70%' });
            PgCharts.donut('pg-services', charts.services, { colors: ['#4338ca', '#0ea5e9', '#f59e0b', '#16a34a', '#e11d48'], totalLabel: @json(__('payment_gateways.chart.collected')) });
            PgCharts.donut('pg-statuses', charts.statuses, { count: true, colors: ['#16a34a', '#0ea5e9', '#6366f1', '#f59e0b', '#94a3b8'] });
        })();
    </script>
@endpush
