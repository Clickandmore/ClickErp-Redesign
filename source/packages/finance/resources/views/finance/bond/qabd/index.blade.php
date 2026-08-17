@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <style>
        .bond-page-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
        .bond-page-toolbar h5 { margin:0; font-weight:700; }
        .bond-page-toolbar p { margin:3px 0 0; color:#6c757d; font-size:13px; }
        .bond-filter-card, .bond-table-card { border:0; border-radius:14px; box-shadow:0 4px 18px rgba(28,39,60,.06); }
        .bond-filter-card .card-header, .bond-table-card .card-header { background:#fff; border-bottom:1px solid #edf0f4; padding:15px 18px; }
        .bond-filter-card .form-label { color:#4b5563; font-size:12px; font-weight:700; margin-bottom:6px; }
        .bond-filter-card .form-control, .bond-filter-card .select2-container .select2-selection { min-height:40px; border-color:#e1e6ed; }
        .bond-filter-actions { display:flex; align-items:end; gap:8px; height:100%; }
        .bond-summary-card { border:1px solid #edf0f4; border-radius:12px; background:#fff; padding:14px 16px; height:100%; }
        .bond-summary-card .summary-label { color:#6c757d; font-size:12px; }
        .bond-summary-card .summary-value { color:#172033; font-size:20px; font-weight:800; margin-top:5px; }
        .bond-summary-card .summary-compare { color:#8a94a3; font-size:11px; margin-top:3px; }
        .bond-summary-card .summary-icon { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; color:#16875d; background:rgba(22,135,93,.09); }
        #dataTableObject th, #dataTableObject td { vertical-align:middle; }
        #dataTableObject thead th { background:#f8fafc; color:#4b5563; font-size:12px; white-space:nowrap; }
        .bond-voucher-number, .bond-account { display:flex; flex-direction:column; gap:3px; }
        .bond-voucher-number a { color:#16875d; font-size:15px; font-weight:800; text-decoration:none; }
        .bond-voucher-number small, .bond-account small { color:#8a94a3; font-size:11px; }
        .bond-account { min-width:210px; }
        .bond-account strong { color:#273142; white-space:normal; line-height:1.5; }
        .bond-account small { max-width:270px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .bond-date { display:flex; flex-direction:column; gap:3px; white-space:nowrap; }
        .bond-date small { color:#8a94a3; font-size:11px; }
        .bond-amount, .bond-constraint-link { white-space:nowrap; }
        .bond-amount { color:#111827; font-size:14px; }
        .bond-status { display:inline-flex; align-items:center; gap:5px; padding:7px 9px; white-space:nowrap; }
        .bond-actions { display:flex; align-items:center; gap:4px; white-space:nowrap; }
        .bond-actions .btn { margin:0 !important; }
        .bond-table-card .dataTables_wrapper { padding:0 16px 16px; }
        .bond-table-card .dataTables_filter { margin-top:14px; }
        @media (max-width:767px) { .bond-page-toolbar { align-items:flex-start; flex-direction:column; } }
    </style>
@endsection

@section('page-title', $bladeTitle)

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    <div class="bond-page-toolbar">
        <div>
            <h5>إدارة سندات القبض</h5>
            <p>ابحث وراجع سند القبض والقيد المحاسبي المرتبط به من مكان واحد.</p>
        </div>
        {!! addButton($createRoute, $addButtonText) !!}
    </div>

    <div class="row g-3 mb-3">
        @php
            $summaries = [
                ['label' => __('finance::finance.yearly'), 'value' => $results['year']->total_value, 'compare' => __('finance::finance.last_year').' '.number_format($results['last_year']->total_value, 2), 'icon' => 'fa-calendar'],
                ['label' => __('finance::finance.quarterly'), 'value' => $results['quarterYearData']->total_value, 'compare' => __('finance::finance.last_quarterly').' '.number_format($results['last_quarterYearData']->total_value, 2), 'icon' => 'fa-calendar-check'],
                ['label' => __('finance::finance.monthly'), 'value' => $results['month']->total_value, 'compare' => __('finance::finance.last_month').' '.number_format($results['last_month']->total_value, 2), 'icon' => 'fa-calendar-alt'],
                ['label' => __('finance::finance.daily'), 'value' => $results['day']->total_value, 'compare' => __('finance::finance.last_day').' '.number_format($results['last_day']->total_value, 2), 'icon' => 'fa-calendar-day'],
            ];
        @endphp
        @foreach($summaries as $summary)
            <div class="col-xl-3 col-md-6">
                <div class="bond-summary-card">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="summary-label">{{ $summary['label'] }}</div>
                            <div class="summary-value" dir="ltr">{{ number_format($summary['value'], 2) }}</div>
                        </div>
                        <span class="summary-icon"><i class="fa {{ $summary['icon'] }}"></i></span>
                    </div>
                    <div class="summary-compare">{{ $summary['compare'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card bond-filter-card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong><i class="fa fa-filter text-success me-1"></i> تصفية سندات القبض</strong>
            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="collapse" data-bs-target="#qabdFilters" aria-expanded="true"><i class="fa fa-chevron-up"></i></button>
        </div>
        <div class="collapse show" id="qabdFilters">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="bond_search">بحث سريع</label>
                        <input class="form-control" id="bond_search" type="search" placeholder="رقم السند، القيد، الدفتر، البيان أو التحويل">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="from_date">{{ __('finance::finance.from_date') }}</label>
                        <input class="form-control" id="from_date" type="date">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="to_date">{{ __('finance::finance.to_date') }}</label>
                        <input class="form-control" id="to_date" type="date">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label" for="is_confirm_filter">حالة السند</label>
                        <select id="is_confirm_filter" class="form-control select2">
                            <option value="">كل الحالات</option>
                            <option value="1">معتمد</option>
                            <option value="0">بانتظار الاعتماد</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="payment_type_filter">{{ __('finance::finance.payment_type') }}</label>
                        <select id="payment_type_filter" class="form-control select2">
                            <option value="">كل طرق الدفع</option>
                            @foreach($payment_types as $payment_type)
                                <option value="{{ $payment_type->value }}">{{ $payment_type->lang() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="credit_account_id">الدافع / الحساب الدائن</label>
                        <select id="credit_account_id" class="form-control bond-remote-select" data-option-type="account" data-placeholder="ابحث باسم الدافع أو رقم الحساب">
                            <option value="">كل الدافعين</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="debit_account_id">{{ __('finance::finance.Cash/Drawer/Bank') }}</label>
                        <select id="debit_account_id" class="form-control select2">
                            <option value="">كل الصناديق والبنوك</option>
                            @foreach($banks_boxes as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label" for="cost_center_id">{{ __('finance::finance.cost_center') }}</label>
                        <select id="cost_center_id" class="form-control bond-remote-select" data-option-type="cost_center" data-placeholder="ابحث باسم أو رقم مركز التكلفة">
                            <option value="">كل مراكز التكلفة</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="bond-filter-actions">
                            <button type="button" id="applyBondFilters" class="btn btn-success flex-grow-1"><i class="fa fa-search"></i> تطبيق</button>
                            <button type="button" id="resetBondFilters" class="btn btn-outline-secondary" title="مسح الفلاتر"><i class="fa fa-undo"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bond-table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <strong><i class="fa fa-list text-success me-1"></i> سجل سندات القبض</strong>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if (@AdminGuardHasPerm('finance_bonds_export') || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
                    <a href="#" class="btn btn-sm btn-outline-success bond-list-export" data-export-url="{{ route('finance.bonds-qabd.export', 'excel') }}">
                        <i class="fa fa-file-excel-o"></i> Excel
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-danger bond-list-export" data-export-url="{{ route('finance.bonds-qabd.export', 'pdf') }}">
                        <i class="fa fa-file-pdf-o"></i> PDF
                    </a>
                @endif
                <span class="badge bg-light text-dark border">عدد السندات: {{ number_format($archiveSummary->total_count ?? 0) }}</span>
                <span class="badge bg-light text-dark border">إجمالي الحركة: {{ number_format($archiveSummary->total_movement ?? 0, 2) }} ريال</span>
                <small class="text-muted">اضغط رقم السند للطباعة أو رقم القيد لعرض تفاصيله</small>
            </div>
        </div>
        <div class="table-responsive">
            <table id="dataTableObject" class="table table-hover align-middle mb-0" style="width:100%">
                <thead><tr>
                    <th>#</th>
                    <th>{{ __('finance::finance.Voucher Number') }}</th>
                    <th>{{ __('finance::finance.Voucher Date') }}</th>
                    <th>الدافع / البيان</th>
                    <th>{{ __('finance::finance.Cash/Drawer/Bank') }}</th>
                    <th>{{ __('finance::finance.Amount') }}</th>
                    <th>الحالة</th>
                    <th>القيد المرتبط</th>
                    <th>{{ __('finance::finance.actions') }}</th>
                </tr></thead>
            </table>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script>
        $(function () {
            $('.bond-remote-select').each(function () {
                const select = $(this);
                select.select2({
                    width:'100%', allowClear:true, placeholder:select.data('placeholder'), minimumInputLength:0,
                    ajax:{
                        url:@json($filterOptionsRoute), dataType:'json', delay:300, cache:true,
                        data:function(params){ return {type:select.data('option-type'), q:params.term || '', page:params.page || 1}; },
                        processResults:function(data){ return data; }
                    }
                });
            });
        });
    </script>
    @php($bondTableType = 'qabd')
    @include('finance::finance.bond._js_bond')
@endsection
