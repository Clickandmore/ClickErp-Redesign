@extends('click.layout.indexs.index')

@section('page-title', $bladeTitle)
@section('page-links')<li class="breadcrumb-item"><a href="{{ route('finance.home.index') }}">{{ __('finance::finance.finance_dashboard') }}</a></li><li class="breadcrumb-item active">{{ $bladeTitle }}</li>@endsection

@php
    $currency = __('finance::finance.sar');
    $money = fn($value) => number_format((float) ($value ?? 0), 2) . ' ' . $currency;
    $short = fn($value) => number_format((float) ($value ?? 0), 0);
    $printing = (bool) ($report['print'] ?? false);
    $sales = $report['sales'];
    $kpis = $report['kpis'];
    $delegations = $report['delegations'];
    $musaned = $report['musaned'];
    $offices = $report['external_offices'];
    $agingLabels = [
        'd30' => __('finance::finance.ops_aging_d30'),
        'd60' => __('finance::finance.ops_aging_d60'),
        'd90' => __('finance::finance.ops_aging_d90'),
        'over' => __('finance::finance.ops_aging_over'),
        'unknown' => __('finance::finance.ops_aging_unknown'),
    ];
@endphp

@section('style')
<style>
    .ops-summary { --ink:#172033; --muted:#738095; --line:#e7ebf1; --soft:#f4f7fb; --navy:#132238;
        --green:#119b70; --red:#df5a63; --amber:#e5a43b; --blue:#2878ff; color:var(--ink); }
    .ops-summary a { text-decoration:none; }

    /* ——— تحييد أنماط الجداول العامة في style.css / style-rtl.css ———
       هذان الملفان يُحمَّلان بعد هذا القسم ويفرضان ظلاً وهوامش على كل <table>،
       و padding:16px !important على رؤوس الأعمدة، وتحريك الصفوف عند المرور،
       فيتداخل ذلك مع تنسيق التقرير. نعيد ضبطه داخل .ops-summary وحدها. */
    .ops-summary table { width:100%; margin:0; background:transparent; box-shadow:none;
        border-collapse:collapse; border-spacing:0; font-size:.86rem; }
    .ops-summary thead { background:none; box-shadow:none; }
    .ops-summary tbody tr { transition:background-color .15s ease; }
    .ops-summary tbody tr:hover { background:#f7f9fc; transform:none; }
    .ops-summary th, .ops-summary td { padding:10px 12px !important; vertical-align:middle;
        border-bottom:1px solid var(--line); }
    .ops-summary thead th { background:var(--soft); font-weight:700; color:#3a4658;
        border-bottom:1px solid #dde3ec; white-space:nowrap; }
    .ops-summary tfoot td { background:var(--soft); font-weight:800; border-top:1px solid #dde3ec;
        border-bottom:0; }

    /* ——— الهيكل العام ——— */
    .ops-summary .ops-hero { padding:22px 26px; border-radius:16px; color:#fff;
        background:linear-gradient(125deg,#132238 0%,#1d3553 70%,#244c73 100%); margin-bottom:16px; }
    .ops-summary .ops-hero h3 { color:#fff; margin:0 0 6px; font-weight:800; font-size:1.3rem; }
    .ops-summary .ops-hero p { margin:0; opacity:.78; font-size:.9rem; }
    .ops-summary .ops-hero-branch { margin-top:8px !important; opacity:.92; font-weight:700; }
    .ops-summary .ops-panel { background:#fff; border:1px solid var(--line); border-radius:14px;
        padding:18px 20px; margin-bottom:16px; }
    .ops-summary .panel-head { display:flex; align-items:flex-start; justify-content:space-between;
        gap:10px 20px; flex-wrap:wrap; margin-bottom:14px; }
    .ops-summary .panel-head > * { min-width:0; }
    .ops-summary .panel-headline { flex:1 1 300px; }
    .ops-summary .panel-meta { flex:0 1 auto; max-width:100%; text-align:end; }
    .ops-summary .panel-meta .pill { margin-bottom:4px; }
    .ops-summary .panel-title { margin:0; font-size:1.02rem; font-weight:800; line-height:1.6; }
    .ops-summary .panel-subtitle { color:var(--muted); font-size:.8rem; margin-top:3px; line-height:1.8; }

    /* ——— شريط الفلاتر: كل الحقول على استقامة واحدة مهما طال عنوانها ——— */
    .ops-summary .ops-filters label { display:flex; align-items:flex-end; min-height:2.5em;
        margin:0 0 6px; font-size:.82rem; font-weight:700; color:#42506b; line-height:1.25; }
    .ops-summary .ops-filters .form-control { width:100%; }
    .ops-summary .ops-filters-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px;
        margin-top:4px; padding-top:14px; border-top:1px dashed var(--line); }
    .ops-summary .ops-filters-actions .panel-subtitle { margin:0; flex:1 1 220px; }
    .ops-summary .ops-field-hint { display:block; margin-top:6px; font-size:.74rem;
        color:var(--muted); line-height:1.6; }

    /* ——— بطاقات المؤشرات ——— */
    .ops-summary .kpi-grid { display:grid; gap:12px;
        grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); margin-bottom:16px; }
    .ops-summary .kpi { background:#fff; border:1px solid var(--line); border-radius:14px;
        padding:15px 17px; display:flex; flex-direction:column; }
    .ops-summary .kpi span { display:block; color:var(--muted); font-size:.78rem; font-weight:700; line-height:1.6; }
    .ops-summary .kpi strong { display:block; font-size:1.3rem; font-weight:800; margin-top:6px;
        direction:ltr; text-align:right; font-variant-numeric:tabular-nums; }
    .ops-summary .kpi small { display:block; color:var(--muted); font-size:.74rem; margin-top:5px; line-height:1.7; }
    .ops-summary .kpi.is-green strong { color:var(--green); }
    .ops-summary .kpi.is-red strong { color:var(--red); }
    .ops-summary .kpi.is-amber strong { color:var(--amber); }

    /* ——— الأرقام تُحاذى على حافة بداية الصفحة كي تتطابق مع عناوين الأعمدة ——— */
    .ops-summary .num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .ops-summary td.num { direction:ltr; }
    [dir="ltr"] .ops-summary .num { text-align:left; }
    [dir="ltr"] .ops-summary .kpi strong,
    [dir="ltr"] .ops-summary .aging-cell strong { text-align:left; }
    .ops-summary .positive { color:var(--green); }
    .ops-summary .negative { color:var(--red); }

    .ops-summary .table-scroll { overflow-x:auto; overflow-y:hidden; background:#fff;
        border:1px solid var(--line); border-radius:12px; }
    .ops-summary .table-scroll + .table-scroll { margin-top:12px; }

    .ops-summary .aging-strip { display:grid; gap:10px;
        grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); margin-bottom:14px; }
    .ops-summary .aging-cell { background:var(--soft); border:1px solid var(--line);
        border-radius:10px; padding:11px 13px; }
    .ops-summary .aging-cell span { display:block; color:var(--muted); font-size:.75rem; font-weight:700; }
    .ops-summary .aging-cell strong { display:block; font-size:1rem; margin-top:4px;
        direction:ltr; text-align:right; font-variant-numeric:tabular-nums; }
    .ops-summary .aging-cell.is-hot { background:#fdeceb; border-color:#f6d5d3; }

    .ops-summary .ops-chart { min-height:290px; max-width:100%; margin-bottom:14px; }
    .ops-summary .ops-chart .apexcharts-canvas { margin-inline:auto; }

    .ops-summary .print-only { display:none; }
    .ops-summary .empty-state { text-align:center; color:var(--muted); padding:26px 12px; font-size:.88rem; }
    .ops-summary .empty-state-detail { margin-top:8px; font-size:.82rem; line-height:1.9; color:#6b7280; }
    .ops-summary .ops-data-basis-hint { margin:0 0 16px; border:1px solid #bfdbfe; border-radius:12px;
        background:#eff6ff; color:#1e3a5f; padding:12px 15px; font-size:.84rem; line-height:1.85; }
    .ops-summary .ops-data-basis-hint strong { color:#1d4ed8; }
    .ops-summary .ops-data-basis-hint summary { cursor:pointer; font-weight:800; color:#1d4ed8; list-style:none; }
    .ops-summary .ops-data-basis-hint summary::-webkit-details-marker { display:none; }
    .ops-summary .ops-guide-intro { margin:8px 0 10px; }
    .ops-summary .ops-guide-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:9px; }
    .ops-summary .ops-guide-item { background:#fff; border:1px solid #dbeafe; border-radius:9px; padding:9px 11px; }
    .ops-summary .ops-guide-item b { display:block; color:#1e3a5f; margin-bottom:2px; }
    .ops-summary .ops-filter-reset { display:inline-block; margin-inline-start:6px; font-weight:700; color:#1d4ed8; }
    /* شريط يوضّح أن فلتر مساند استبعد صفوفاً من هذا القسم وحده */
    .ops-summary .ops-filter-note { display:flex; align-items:flex-start; gap:8px; margin:0 0 12px;
        padding:9px 12px; border:1px solid #fde68a; border-radius:10px; background:#fffbeb;
        color:#92400e; font-size:.82rem; font-weight:600; line-height:1.8; }
    .ops-summary .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:.72rem;
        font-weight:700; background:var(--soft); color:#3a4658; white-space:nowrap; }
    .ops-summary .pill.is-hot { background:#fdeceb; color:#b42318; }
    .ops-summary .ops-pager { display:flex; align-items:center; justify-content:space-between;
        flex-wrap:wrap; gap:10px; margin-top:14px; }
    .ops-summary .ops-pager .pagination { margin:0; }

    @media (max-width:575.98px) {
        .ops-summary .ops-hero { padding:18px; }
        .ops-summary .ops-panel { padding:14px; }
        .ops-summary .panel-meta { text-align:start; }
    }

    @media print {
        /* هيكل لوحة التحكم ليس جزءاً من التقرير */
        .sidebar, header.page-header, .page-footer, #scroll-controls,
        .page-loader, .modal, .offcanvas, .breadcrumb { display:none !important; }
        .wrapper { margin:0 !important; padding:0 !important; width:100% !important; }
        body { background:#fff !important; }

        .ops-summary { padding:0 !important; }
        .ops-summary .no-print { display:none !important; }
        .ops-summary .print-only { display:block !important; margin-bottom:12px; font-size:11px; }
        .ops-summary .ops-panel { border:0; padding:0; margin-bottom:14px; break-inside:avoid; }
        .ops-summary .ops-hero { background:none !important; color:#000 !important; padding:0 0 8px;
            border-bottom:2px solid #000; border-radius:0; box-shadow:none; }
        .ops-summary .ops-hero h3 { color:#000 !important; }
        .ops-summary .ops-hero p { opacity:1; }
        .ops-summary .kpi-grid { grid-template-columns:repeat(3,1fr); }
        .ops-summary .kpi { border:1px solid #999; break-inside:avoid; }
        .ops-summary .kpi strong { color:#000 !important; }
        .ops-summary .aging-strip { grid-template-columns:repeat(5,1fr); }

        /* الجداول: رأس متكرر على كل ورقة، صفوف لا تنكسر، ولا قص أفقي */
        .ops-summary .table-scroll { overflow:visible !important; border:0; border-radius:0; }
        .ops-summary table { font-size:10.5px; page-break-inside:auto; }
        .ops-summary thead { display:table-header-group; }
        .ops-summary tfoot { display:table-footer-group; }
        .ops-summary tr { break-inside:avoid; page-break-inside:avoid; }
        .ops-summary tbody tr:hover { background:none; }
        .ops-summary th, .ops-summary td { padding:4px 6px !important; white-space:normal; word-break:break-word; }
        .ops-summary .num { white-space:nowrap; }
        .ops-summary .pill { background:none !important; padding:0; }
        .ops-summary .positive, .ops-summary .negative { color:#000 !important; }
    }
    @page { margin: 12mm 10mm; }
</style>
@endsection

@section('content')
<div class="ops-summary">

    <div class="ops-hero">
        <h3>{{ __('finance::finance.operational_summary') }}</h3>
        <p>{{ __('finance::finance.ops_summary_subtitle') }}</p>
        @include('admin.partials.branch-report-scope', ['scopeTag' => 'p', 'scopeClass' => 'ops-hero-branch'])
    </div>

    <div class="print-only">
        <strong>{{ __('finance::finance.from_date') }}:</strong> {{ $report['from'] }} &nbsp;
        <strong>{{ __('finance::finance.to_date') }}:</strong> {{ $report['to'] }} &nbsp;
        <strong>{{ __('finance::finance.ops_receivables_scope') }}:</strong>
        {{ $report['scope'] === 'period' ? __('finance::finance.ops_scope_period') : __('finance::finance.ops_scope_outstanding') }} &nbsp;
        <strong>{{ __('finance::finance.ops_musaned_status') }}:</strong>
        {{ __('finance::finance.ops_musaned_status_' . $report['musaned_status']) }}
        @include('admin.partials.branch-report-scope')
    </div>

    <form class="ops-panel {{ $printing ? 'd-none' : '' }} no-print" method="GET" action="{{ route('finance.operational-summary.index') }}">
        <div class="row g-3 ops-filters">
            <div class="col-6 col-md-3 col-xl-2">
                <label for="ops-from">{{ __('finance::finance.from_date') }}</label>
                <input class="form-control" id="ops-from" type="date" name="from_date" value="{{ $report['from'] }}" required>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label for="ops-to">{{ __('finance::finance.to_date') }}</label>
                <input class="form-control" id="ops-to" type="date" name="to_date" value="{{ $report['to'] }}" required>
            </div>
            <div class="col-md-6 col-xl-3">
                <label for="ops-scope">{{ __('finance::finance.ops_receivables_scope') }}</label>
                <select class="form-control" id="ops-scope" name="scope">
                    <option value="period" {{ $report['scope'] === 'period' ? 'selected' : '' }}>{{ __('finance::finance.ops_scope_period') }}</option>
                    <option value="outstanding" {{ $report['scope'] === 'outstanding' ? 'selected' : '' }}>{{ __('finance::finance.ops_scope_outstanding') }}</option>
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label for="ops-musaned-status">{{ __('finance::finance.ops_musaned_status') }}</label>
                <select class="form-control" id="ops-musaned-status" name="musaned_status">
                    <option value="all" @selected($report['musaned_status'] === 'all')>{{ __('finance::finance.ops_musaned_status_all') }}</option>
                    <option value="transferred" @selected($report['musaned_status'] === 'transferred')>{{ __('finance::finance.ops_musaned_status_transferred') }}</option>
                    <option value="not_transferred" @selected($report['musaned_status'] === 'not_transferred')>{{ __('finance::finance.ops_musaned_status_not_transferred') }}</option>
                </select>
                <small class="ops-field-hint">{{ __('finance::finance.ops_musaned_status_hint') }}</small>
            </div>
            @include('admin.partials.branch-filter', [
                'name' => 'branch_id',
                'label' => __('financial_requests.branch'),
                'id' => 'ops-branch',
                'containerClass' => 'col-6 col-md-3 col-xl-2',
                'selectClass' => 'form-control',
                'labelClass' => '',
            ])
            <div class="col-6 col-md-3 col-xl-2">
                <label for="ops-per-page">{{ __('finance::finance.ops_per_page') }}</label>
                <select class="form-control" id="ops-per-page" name="per_page">
                    @foreach(\Nami\Finance\Services\OperationalSummaryService::PER_PAGE_OPTIONS as $size)
                        <option value="{{ $size }}" {{ (int) $report['per_page'] === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <div class="ops-filters-actions">
                    <button class="btn btn-success" type="submit">{{ __('finance::finance.filter') }}</button>
                    <a class="btn btn-outline-success" href="{{ route('finance.operational-summary.index', ['from_date' => $report['from'], 'to_date' => $report['to'], 'scope' => $report['scope'], 'musaned_status' => $report['musaned_status'], 'branch_id' => $branchId ?? null, 'export' => 'xlsx']) }}">{{ __('finance::finance.ops_export_excel') }}</a>
                    <a class="btn btn-outline-secondary" target="_blank" rel="noopener"
                       href="{{ route('finance.operational-summary.index', ['from_date' => $report['from'], 'to_date' => $report['to'], 'scope' => $report['scope'], 'musaned_status' => $report['musaned_status'], 'branch_id' => $branchId ?? null, 'print' => 1]) }}">{{ __('finance::finance.print') }}</a>
                    <span class="panel-subtitle">{{ $report['scope'] === 'period' ? __('finance::finance.ops_scope_period_hint') : __('finance::finance.ops_scope_outstanding_hint') }}</span>
                </div>
            </div>
        </div>
        @if($errors->any())<div class="alert alert-danger mt-3 mb-0">{{ $errors->first() }}</div>@endif
    </form>

    <details class="ops-data-basis-hint">
        <summary><i class="fas fa-info-circle me-1"></i>{{ __('finance::finance.ops_page_guide_title') }}</summary>
        <div class="ops-guide-intro">{{ __('finance::finance.ops_data_basis_hint') }}</div>
        <div class="ops-guide-grid">
            @foreach([
                'filters', 'sales', 'collection', 'receivables', 'aging', 'delegations', 'musaned', 'offices'
            ] as $guide)
                <div class="ops-guide-item">
                    <b>{{ __('finance::finance.ops_guide_' . $guide . '_title') }}</b>
                    <span>{{ __('finance::finance.ops_guide_' . $guide . '_hint') }}</span>
                </div>
            @endforeach
        </div>
    </details>

    <div class="kpi-grid">
        <div class="kpi">
            <span>{{ __('finance::finance.ops_kpi_total_sales') }}</span>
            <strong>{{ $money($kpis['total_sales']) }}</strong>
            <small>{{ __('finance::finance.ops_of_which_rental') }}: {{ $short($sales['totals']['rental']) }} — {{ __('finance::finance.ops_of_which_recruitment') }}: {{ $short($sales['totals']['recruitment']) }} — {{ __('finance::finance.ops_sponsorship_invoices') }}: {{ $short($sales['totals']['sponsorship']) }}</small>
        </div>
        <div class="kpi is-green">
            <span>{{ __('finance::finance.ops_kpi_collected') }}</span>
            <strong>{{ $money($kpis['collected']) }}</strong>
            <small>{{ __('finance::finance.ops_collection_rate') }}: {{ $kpis['collection_rate'] === null ? '—' : number_format($kpis['collection_rate'], 1) . '%' }}</small>
        </div>
        <div class="kpi is-amber">
            <span>{{ __('finance::finance.ops_kpi_outstanding') }}</span>
            <strong>{{ $money($kpis['outstanding_period']) }}</strong>
            <small>{{ __('finance::finance.ops_monthly_sales_hint') }}</small>
        </div>
        <div class="kpi is-red">
            <span>{{ __('finance::finance.ops_kpi_receivables') }}</span>
            <strong>{{ $money($kpis['receivables_total']) }}</strong>
            <small>{{ __('finance::finance.ops_of_which_rental') }}: {{ $short($kpis['rental_receivables']) }} — {{ __('finance::finance.ops_of_which_recruitment') }}: {{ $short($kpis['recruitment_receivables']) }}</small>
            @php $allOutstanding = ($report['rental_receivables']['outstanding_all'] ?? null) !== null ? $report['rental_receivables']['outstanding_all'] + $report['recruitment_receivables']['outstanding_all'] + $report['sponsorship_receivables']['outstanding_all'] : null; @endphp
            @if($allOutstanding !== null && abs($allOutstanding - $kpis['receivables_total']) > .005)
                <small>{{ __('finance::finance.ops_outstanding_all') }}: {{ $short($allOutstanding) }}</small>
            @endif
        </div>
        <div class="kpi is-green">
            <span>{{ __('finance::finance.ops_kpi_delegation_net') }}</span>
            <strong>{{ $money($kpis['delegation_net']) }}</strong>
            <small>{{ $short($kpis['delegation_count']) }} {{ __('finance::finance.ops_delegations_count') }} — {{ __('finance::finance.ops_delegation_revenue') }}: {{ $short($kpis['delegation_revenue']) }}</small>
        </div>
        <div class="kpi is-green">
            <span>{{ __('finance::finance.ops_kpi_musaned_net') }}</span>
            <strong>{{ $money($kpis['musaned_commission_net']) }}</strong>
            <small>{{ __('finance::finance.ops_gross_commission') }}: {{ $short($kpis['musaned_commission_gross']) }}</small>
        </div>
        <div class="kpi is-red">
            <span>{{ __('finance::finance.ops_kpi_offices_payable') }}</span>
            <strong>{{ $money($kpis['offices_payable']) }}</strong>
            <small>{{ __('finance::finance.ops_prepaid_to_offices') }}: {{ $short($kpis['offices_prepaid']) }}</small>
        </div>
    </div>

    {{-- إجمالي المبيعات الشهرية --}}
    <section class="ops-panel">
        <div class="panel-head">
            <div class="panel-headline">
                <h5 class="panel-title">{{ __('finance::finance.ops_monthly_sales') }}</h5>
                <div class="panel-subtitle">{{ __('finance::finance.ops_monthly_sales_hint') }}</div>
            </div>
        </div>
        <div id="opsSalesChart" class="ops-chart no-print"></div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('finance::finance.ops_month') }}</th>
                        <th class="num">{{ __('finance::finance.ops_rental_invoices') }}</th>
                        <th class="num">{{ __('finance::finance.ops_count') }}</th>
                        <th class="num">{{ __('finance::finance.ops_recruitment_invoices') }}</th>
                        <th class="num">{{ __('finance::finance.ops_count') }}</th>
                        <th class="num">{{ __('finance::finance.ops_delegation_invoices') }}</th>
                        <th class="num">{{ __('finance::finance.ops_count') }}</th>
                        <th class="num">{{ __('finance::finance.ops_sponsorship_invoices') }}</th>
                        <th class="num">{{ __('finance::finance.ops_count') }}</th>
                        <th class="num">{{ __('finance::finance.ops_rental_adjustments') }}</th>
                        <th class="num">{{ __('finance::finance.ops_invoiced') }}</th>
                        <th class="num">{{ __('finance::finance.ops_collected') }}</th>
                        <th class="num">{{ __('finance::finance.ops_outstanding') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales['months'] as $month)
                        <tr>
                            <td>{{ $month['label'] }}</td>
                            <td class="num">{{ number_format($month['rental'], 2) }}</td>
                            <td class="num">{{ $month['rental_count'] }}</td>
                            <td class="num">{{ number_format($month['recruitment'], 2) }}</td>
                            <td class="num">{{ $month['recruitment_count'] }}</td>
                            <td class="num">{{ number_format($month['delegations'], 2) }}</td>
                            <td class="num">{{ $month['delegations_count'] }}</td>
                            <td class="num">{{ number_format($month['sponsorship'], 2) }}</td>
                            <td class="num">{{ $month['sponsorship_count'] }}</td>
                            <td class="num">{{ number_format($month['adjustments'], 2) }}</td>
                            <td class="num"><strong>{{ number_format($month['invoiced'], 2) }}</strong></td>
                            <td class="num positive">{{ number_format($month['collected'], 2) }}</td>
                            <td class="num {{ $month['outstanding'] > 0 ? 'negative' : '' }}">{{ number_format($month['outstanding'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>{{ __('finance::finance.total') }}</td>
                        <td class="num">{{ number_format($sales['totals']['rental'], 2) }}</td>
                        <td class="num">{{ $sales['totals']['rental_count'] }}</td>
                        <td class="num">{{ number_format($sales['totals']['recruitment'], 2) }}</td>
                        <td class="num">{{ $sales['totals']['recruitment_count'] }}</td>
                        <td class="num">{{ number_format($sales['totals']['delegations'], 2) }}</td>
                        <td class="num">{{ $sales['totals']['delegations_count'] }}</td>
                        <td class="num">{{ number_format($sales['totals']['sponsorship'], 2) }}</td>
                        <td class="num">{{ $sales['totals']['sponsorship_count'] }}</td>
                        <td class="num">{{ number_format($sales['totals']['adjustments'], 2) }}</td>
                        <td class="num">{{ number_format($sales['totals']['invoiced'], 2) }}</td>
                        <td class="num positive">{{ number_format($sales['totals']['collected'], 2) }}</td>
                        <td class="num negative">{{ number_format($sales['totals']['outstanding'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- الذمم: التأجير ثم الاستقدام --}}
    @foreach([
        ['section' => $report['rental_receivables'], 'title' => __('finance::finance.ops_rental_receivables'), 'hint' => __('finance::finance.ops_rental_receivables_hint'), 'show_paid' => true],
        ['section' => $report['recruitment_receivables'], 'title' => __('finance::finance.ops_recruitment_receivables'), 'hint' => __('finance::finance.ops_recruitment_receivables_hint'), 'show_paid' => false],
        ['section' => $report['sponsorship_receivables'], 'title' => __('finance::finance.ops_sponsorship_receivables'), 'hint' => __('finance::finance.ops_sponsorship_receivables_hint'), 'show_paid' => true],
    ] as $block)
        @php $section = $block['section']; @endphp
        <section class="ops-panel">
            <div class="panel-head">
                <div class="panel-headline">
                    <h5 class="panel-title">{{ $block['title'] }}</h5>
                    <div class="panel-subtitle">{{ $block['hint'] }} — {{ __('finance::finance.ops_top_rows_note') }}</div>
                </div>
                <div class="panel-subtitle panel-meta">
                    {{ $section['totals']['documents'] }} {{ __('finance::finance.ops_documents') }} —
                    {{ __('finance::finance.ops_clients_count') }}: {{ $section['totals']['clients'] }} —
                    {{ __('finance::finance.ops_oldest_debt') }}: {{ $section['totals']['oldest_days'] }} {{ __('finance::finance.ops_days') }}
                    @isset($section['outstanding_all'])
                        <div>{{ __('finance::finance.ops_outstanding_all') }}: <strong>{{ $money($section['outstanding_all']) }}</strong></div>
                    @endisset
                </div>
            </div>

            <div class="aging-strip">
                @foreach($agingLabels as $bucket => $label)
                    @continue($bucket === 'unknown' && ($section['aging']['unknown'] ?? 0) <= 0)
                    <div class="aging-cell {{ $bucket === 'over' && ($section['aging'][$bucket] ?? 0) > 0 ? 'is-hot' : '' }}">
                        <span>{{ $label }}</span>
                        <strong>{{ number_format($section['aging'][$bucket] ?? 0, 2) }}</strong>
                    </div>
                @endforeach
            </div>

            @php
                // فلتر «تحويل مساند» يخص قسم ذمم الاستقدام وحده، فنوضّح أثره هنا
                // حتى لا يبدو الفلتر معطّلاً عند خلو القسم.
                $musanedFilter = $section['musaned_filter'] ?? null;
            @endphp

            @if($musanedFilter && $musanedFilter['active'] && $musanedFilter['excluded_documents'] > 0)
                <div class="ops-filter-note no-print">
                    <i class="fa fa-filter"></i>
                    <span>
                        {{ __('finance::finance.ops_musaned_filter_applied', [
                            'status' => __('finance::finance.ops_musaned_status_' . $musanedFilter['status']),
                            'excluded' => number_format($musanedFilter['excluded_documents']),
                            'amount' => $money($musanedFilter['excluded_remaining']),
                        ]) }}
                    </span>
                </div>
            @endif

            @if($section['rows']->isEmpty())
                <div class="empty-state">
                    {{ __('finance::finance.ops_no_receivables') }}

                    @if($musanedFilter && $musanedFilter['active'])
                        <div class="empty-state-detail">
                            @if($musanedFilter['unfiltered_documents'] === 0)
                                {{-- القسم فارغ بغض النظر عن الفلتر: لا ذمم استقدام في النطاق --}}
                                {{ __('finance::finance.ops_musaned_filter_no_base_rows') }}
                            @else
                                {{-- توجد ذمم لكن الفلتر استبعدها كلها --}}
                                {{ __('finance::finance.ops_musaned_filter_hid_all', [
                                    'status' => __('finance::finance.ops_musaned_status_' . $musanedFilter['status']),
                                    'total' => number_format($musanedFilter['unfiltered_documents']),
                                ]) }}
                            @endif
                            <a class="ops-filter-reset no-print"
                               href="{{ route('finance.operational-summary.index', array_merge(request()->query(), ['musaned_status' => 'all', 'recruitment_page' => null])) }}">
                                {{ __('finance::finance.ops_musaned_filter_clear') }}
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('finance::finance.contract_number') }}</th>
                                <th>{{ __('finance::finance.ops_client') }}</th>
                                <th>{{ __('finance::finance.phone') }}</th>
                                <th>{{ __('finance::finance.ops_identity') }}</th>
                                <th>{{ __('finance::finance.ops_invoice_date') }}</th>
                                <th class="num">{{ __('finance::finance.ops_age_days') }}</th>
                                <th class="num">{{ __('finance::finance.ops_invoice_value') }}</th>
                                @if($block['show_paid'])<th class="num">{{ __('finance::finance.ops_paid') }}</th>@endif
                                <th class="num">{{ __('finance::finance.ops_remaining') }}</th>
                                <th>{{ __('finance::finance.ops_payment_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section['rows'] as $row)
                                <tr>
                                    <td>
                                        {{ $row->contract_number ?: '—' }}
                                        @if(isset($row->document_type))
                                            <small class="d-block">{{ $row->document_type === 'extension' ? 'تمديد' : 'فاتورة أساسية' }} #{{ $row->id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($row->client_account_id) && \Illuminate\Support\Facades\Route::has('finance.account-statement-clients.index'))
                                            <a href="{{ route('finance.account-statement-clients.index', ['account_id' => $row->client_account_id]) }}">{{ $row->client_name ?: '—' }}</a>
                                        @else
                                            {{ $row->client_name ?: '—' }}
                                        @endif
                                    </td>
                                    <td class="num">{{ $row->client_phone ?: '—' }}</td>
                                    <td class="num">{{ $row->client_identity ?: '—' }}</td>
                                    <td class="num">{{ $row->invoiced_at ? \Carbon\Carbon::parse($row->invoiced_at)->format('Y-m-d') : '—' }}</td>
                                    <td class="num"><span class="pill {{ $row->aging_bucket === 'over' ? 'is-hot' : '' }}">{{ $row->age_days ?? '—' }}</span></td>
                                    <td class="num">{{ number_format($row->total_due, 2) }}</td>
                                    @if($block['show_paid'])<td class="num positive">{{ number_format($row->paid_amount, 2) }}</td>@endif
                                    <td class="num negative">
                                        <strong>{{ number_format($row->remaining_amount, 2) }}</strong>
                                        @if(abs($row->adjustment_amount ?? 0) > 0.005)
                                            <small class="d-block text-muted">{{ __('finance::finance.ops_adjustments') }}: {{ number_format($row->adjustment_amount, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $row->payment_status ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6">{{ __('finance::finance.total') }}</td>
                                <td class="num">{{ number_format($section['totals']['invoiced'], 2) }}</td>
                                @if($block['show_paid'])<td class="num positive">{{ number_format($section['totals']['paid'], 2) }}</td>@endif
                                <td class="num negative">{{ number_format($section['totals']['remaining'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($section['paginator'] && $section['paginator']->hasPages())
                    <div class="ops-pager no-print">
                        <div class="panel-subtitle">
                            {{ __('finance::finance.ops_showing') }}
                            {{ $section['paginator']->firstItem() }}–{{ $section['paginator']->lastItem() }}
                            {{ __('finance::finance.ops_of') }} {{ $section['paginator']->total() }}
                        </div>
                        {{ $section['paginator']->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            @endif
        </section>
    @endforeach

    {{-- إيرادات التفاويض الرجالية --}}
    <section class="ops-panel">
        <div class="panel-head">
            <div class="panel-headline">
                <h5 class="panel-title">{{ __('finance::finance.ops_delegations') }}</h5>
                <div class="panel-subtitle">{{ __('finance::finance.ops_delegations_hint') }}</div>
            </div>
            @if($delegations['pending_cost']['entries'] > 0)
                <div class="panel-subtitle panel-meta">
                    <span class="pill is-hot">{{ __('finance::finance.ops_delegation_pending_cost') }}: {{ $delegations['pending_cost']['entries'] }} — {{ $money($delegations['pending_cost']['amount']) }}</span>
                    <div>{{ __('finance::finance.ops_delegation_pending_cost_hint') }}</div>
                </div>
            @endif
        </div>
        @if($delegations['totals']['delegations'] === 0)
            <div class="empty-state">{{ __('finance::finance.ops_no_delegations') }}</div>
        @else
            <div class="aging-strip">
                <div class="aging-cell"><span>{{ __('finance::finance.ops_delegations_count') }}</span><strong>{{ number_format($delegations['totals']['delegations']) }}</strong></div>
                <div class="aging-cell"><span>{{ __('finance::finance.ops_delegation_revenue') }}</span><strong>{{ number_format($delegations['totals']['revenue'], 2) }}</strong></div>
                <div class="aging-cell"><span>{{ __('finance::finance.ops_delegation_cost') }}</span><strong>{{ number_format($delegations['totals']['cost'], 2) }}</strong></div>
                <div class="aging-cell"><span>{{ __('finance::finance.ops_delegation_net') }}</span><strong>{{ number_format($delegations['totals']['net'], 2) }}</strong></div>
                <div class="aging-cell"><span>{{ __('finance::finance.ops_delegation_average') }}</span><strong>{{ number_format($delegations['totals']['average_revenue'], 2) }}</strong></div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance::finance.ops_month') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegations_count') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_revenue') }}</th>
                            <th class="num">{{ __('finance::finance.ops_collected') }}</th>
                            <th class="num">{{ __('finance::finance.ops_outstanding') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_cost') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_net') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($delegations['months'] as $month)
                            <tr>
                                <td>{{ $month['label'] }}</td>
                                <td class="num">{{ $month['delegations'] }}</td>
                                <td class="num">{{ number_format($month['revenue'], 2) }}</td>
                                <td class="num positive">{{ number_format($month['collected'], 2) }}</td>
                                <td class="num {{ $month['outstanding'] > 0 ? 'negative' : '' }}">{{ number_format($month['outstanding'], 2) }}</td>
                                <td class="num">{{ number_format($month['cost'], 2) }}</td>
                                <td class="num positive"><strong>{{ number_format($month['net'], 2) }}</strong></td>
                                <td class="num">{{ number_format($month['average_revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>{{ __('finance::finance.total') }}</td>
                            <td class="num">{{ $delegations['totals']['delegations'] }}</td>
                            <td class="num">{{ number_format($delegations['totals']['revenue'], 2) }}</td>
                            <td class="num positive">{{ number_format($delegations['totals']['collected'], 2) }}</td>
                            <td class="num negative">{{ number_format($delegations['totals']['outstanding'], 2) }}</td>
                            <td class="num">{{ number_format($delegations['totals']['cost'], 2) }}</td>
                            <td class="num positive">{{ number_format($delegations['totals']['net'], 2) }}</td>
                            <td class="num">{{ number_format($delegations['totals']['average_revenue'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="panel-subtitle mt-3 mb-2"><strong>{{ __('finance::finance.ops_by_profession') }}</strong></div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance::finance.ops_profession') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegations_count') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_revenue') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_cost') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_net') }}</th>
                            <th class="num">{{ __('finance::finance.ops_delegation_average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($delegations['professions'] as $profession)
                            <tr>
                                <td>{{ $profession->profession }}</td>
                                <td class="num">{{ $profession->delegations }}</td>
                                <td class="num">{{ number_format($profession->revenue, 2) }}</td>
                                <td class="num">{{ number_format($profession->cost, 2) }}</td>
                                <td class="num positive">{{ number_format($profession->net, 2) }}</td>
                                <td class="num">{{ number_format($profession->average_revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- عمولات مساند --}}
    <section class="ops-panel">
        <div class="panel-head">
            <div class="panel-headline">
                <h5 class="panel-title">{{ __('finance::finance.ops_musaned') }}</h5>
                <div class="panel-subtitle">{{ __('finance::finance.ops_musaned_hint') }}</div>
            </div>
            @if($musaned['unposted']['transfers'] > 0)
                <div class="panel-subtitle panel-meta">
                    <span class="pill is-hot">{{ __('finance::finance.ops_musaned_unposted') }}: {{ $musaned['unposted']['transfers'] }} — {{ $money($musaned['unposted']['amount']) }}</span>
                </div>
            @endif
        </div>
        @if($musaned['totals']['transfers'] === 0)
            <div class="empty-state">{{ __('finance::finance.ops_no_musaned') }}</div>
        @else
            <div id="opsMusanedChart" class="ops-chart no-print"></div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance::finance.ops_month') }}</th>
                            <th class="num">{{ __('finance::finance.ops_transfers') }}</th>
                            <th class="num">{{ __('finance::finance.ops_contracts') }}</th>
                            <th class="num">{{ __('finance::finance.ops_customer_paid') }}</th>
                            <th class="num">{{ __('finance::finance.ops_transferred') }}</th>
                            <th class="num">{{ __('finance::finance.ops_commission') }}</th>
                            <th class="num">{{ __('finance::finance.ops_expense_tax') }}</th>
                            <th class="num">{{ __('finance::finance.ops_commission_net') }}</th>
                            <th class="num">{{ __('finance::finance.ops_commission_rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($musaned['months'] as $month)
                            <tr>
                                <td>{{ $month['label'] }}</td>
                                <td class="num">{{ $month['transfers'] }}</td>
                                <td class="num">{{ $month['contracts'] }}</td>
                                <td class="num">{{ number_format($month['customer_paid'], 2) }}</td>
                                <td class="num">{{ number_format($month['transferred'], 2) }}</td>
                                <td class="num">{{ number_format($month['commission'], 2) }}</td>
                                <td class="num">{{ number_format($month['expense_tax'], 2) }}</td>
                                <td class="num positive"><strong>{{ number_format($month['commission_net'], 2) }}</strong></td>
                                <td class="num">{{ $month['commission_rate'] === null ? '—' : number_format($month['commission_rate'], 2) . '%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>{{ __('finance::finance.total') }}</td>
                            <td class="num">{{ $musaned['totals']['transfers'] }}</td>
                            <td class="num">{{ $musaned['totals']['contracts'] }}</td>
                            <td class="num">{{ number_format($musaned['totals']['customer_paid'], 2) }}</td>
                            <td class="num">{{ number_format($musaned['totals']['transferred'], 2) }}</td>
                            <td class="num">{{ number_format($musaned['totals']['commission'], 2) }}</td>
                            <td class="num">{{ number_format($musaned['totals']['expense_tax'], 2) }}</td>
                            <td class="num positive">{{ number_format($musaned['totals']['commission_net'], 2) }}</td>
                            <td class="num">{{ $musaned['totals']['commission_rate'] === null ? '—' : number_format($musaned['totals']['commission_rate'], 2) . '%' }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>

    {{-- استحقاقات المكاتب الخارجية --}}
    <section class="ops-panel">
        <div class="panel-head">
            <div class="panel-headline">
                <h5 class="panel-title">{{ __('finance::finance.ops_offices') }}</h5>
                <div class="panel-subtitle">{{ __('finance::finance.ops_offices_hint') }}</div>
            </div>
            @if($offices['unlinked_offices'] > 0)
                <div class="panel-subtitle panel-meta"><span class="pill is-hot">{{ __('finance::finance.ops_offices_unlinked') }}: {{ $offices['unlinked_offices'] }}</span></div>
            @endif
        </div>
        @if($offices['rows']->isEmpty())
            <div class="empty-state">{{ __('finance::finance.ops_no_offices') }}</div>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('finance::finance.ops_account_code') }}</th>
                            <th>{{ __('finance::finance.ops_office') }}</th>
                            <th>{{ __('finance::finance.ops_country') }}</th>
                            <th class="num">{{ __('finance::finance.ops_office_payable') }}</th>
                            <th class="num">{{ __('finance::finance.ops_office_prepaid') }}</th>
                            <th class="num">{{ __('finance::finance.ops_period_contracts') }}</th>
                            <th class="num">{{ __('finance::finance.ops_period_cost_usd') }}</th>
                            <th class="num">{{ __('finance::finance.ops_last_dispatch') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($offices['rows'] as $row)
                            <tr>
                                <td class="num">{{ $row->account_code ?: '—' }}</td>
                                <td>
                                    @if($row->account_id)
                                        <a href="{{ route('finance.account-statement-suppliers.index', ['account_id' => $row->account_id]) }}">{{ $row->name }}</a>
                                    @else
                                        {{ $row->name }}
                                    @endif
                                </td>
                                <td>{{ $row->country ?: '—' }}</td>
                                <td class="num negative"><strong>{{ number_format($row->payable, 2) }}</strong></td>
                                <td class="num positive">{{ number_format($row->prepaid, 2) }}</td>
                                <td class="num">{{ $row->period_contracts }}</td>
                                <td class="num">{{ number_format($row->period_cost_usd, 2) }}</td>
                                <td class="num">{{ $row->last_dispatch_at ? \Carbon\Carbon::parse($row->last_dispatch_at)->format('Y-m-d') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">{{ __('finance::finance.total') }} ({{ $offices['totals']['offices'] }})</td>
                            <td class="num negative">{{ number_format($offices['totals']['payable'], 2) }}</td>
                            <td class="num positive">{{ number_format($offices['totals']['prepaid'], 2) }}</td>
                            <td class="num">{{ $offices['totals']['period_contracts'] }}</td>
                            <td class="num">{{ number_format($offices['totals']['period_cost_usd'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>

</div>
@endsection

@section('js')
<script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
<script>
    @if($printing)
        window.addEventListener('load', function () { window.setTimeout(function () { window.print(); }, 400); });
    @endif
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof ApexCharts === 'undefined') return;
        const currency = @json($currency);
        const labels = @json(array_column($sales['months'], 'label'));
        const common = {
            chart: { toolbar: { show: false }, fontFamily: 'inherit' },
            dataLabels: { enabled: false },
            grid: { borderColor: '#edf0f5', strokeDashArray: 3 },
            tooltip: { y: { formatter: value => `${Number(value || 0).toLocaleString()} ${currency}` } },
            yaxis: { labels: { formatter: value => Number(value || 0).toLocaleString() } },
            legend: { position: 'top', horizontalAlign: @json(app()->getLocale() === 'ar' ? 'right' : 'left') }
        };

        const salesElement = document.querySelector('#opsSalesChart');
        if (salesElement) new ApexCharts(salesElement, {
            ...common,
            series: [
                { name: @json(__('finance::finance.ops_rental_invoices')), type: 'column', data: @json(array_map(fn($m) => round($m['rental'], 2), $sales['months'])) },
                { name: @json(__('finance::finance.ops_recruitment_invoices')), type: 'column', data: @json(array_map(fn($m) => round($m['recruitment'], 2), $sales['months'])) },
                { name: @json(__('finance::finance.ops_sponsorship_invoices')), type: 'column', data: @json(array_map(fn($m) => round($m['sponsorship'], 2), $sales['months'])) },
                { name: @json(__('finance::finance.ops_delegation_invoices')), type: 'column', data: @json(array_map(fn($m) => round($m['delegations'], 2), $sales['months'])) },
                { name: @json(__('finance::finance.ops_collected')), type: 'line', data: @json(array_map(fn($m) => round($m['collected'], 2), $sales['months'])) }
            ],
            chart: { ...common.chart, type: 'line', height: 320, stacked: false },
            colors: ['#2878ff', '#12a7b7', '#e5a43b', '#119b70'],
            stroke: { width: [0, 0, 0, 3], curve: 'smooth' },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '52%' } },
            xaxis: { categories: labels, axisBorder: { show: false }, axisTicks: { show: false } }
        }).render();

        const musanedElement = document.querySelector('#opsMusanedChart');
        if (musanedElement) new ApexCharts(musanedElement, {
            ...common,
            series: [
                { name: @json(__('finance::finance.ops_customer_paid')), data: @json(array_map(fn($m) => round($m['customer_paid'], 2), $musaned['months'])) },
                { name: @json(__('finance::finance.ops_commission_net')), data: @json(array_map(fn($m) => round($m['commission_net'], 2), $musaned['months'])) }
            ],
            chart: { ...common.chart, type: 'bar', height: 280 },
            colors: ['#1d3553', '#119b70'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '48%' } },
            xaxis: { categories: @json(array_column($musaned['months'], 'label')), axisBorder: { show: false }, axisTicks: { show: false } }
        }).render();
    });
</script>
@endsection
