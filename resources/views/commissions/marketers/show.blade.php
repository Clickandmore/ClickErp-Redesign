@extends('admin.layout.indexs.index')

@section('page-title', 'مراجعة عمولات ' . $admin->name)

@section('css')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .employee-head { background: linear-gradient(135deg, #075985, #0f766e); color: #fff; border-radius: 16px; }
        .summary-tile { border: 0; border-radius: 13px; box-shadow: 0 3px 14px rgba(15, 23, 42, .07); }
        .summary-tile-link { display: block; height: 100%; color: inherit; text-decoration: none !important; }
        .summary-tile-link .summary-tile { transition: transform .15s ease, box-shadow .15s ease; }
        .summary-tile-link:hover .summary-tile { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15, 23, 42, .12); }
        .summary-tile-link.active .summary-tile { outline: 2px solid #0d6efd; outline-offset: -2px; }
        .active-report-filter { border: 1px solid #bfdbfe; border-radius: 13px; background: #eff6ff; }
        .quick-filters { border: 0; border-radius: 14px; box-shadow: 0 3px 14px rgba(15, 23, 42, .06); }
        .quick-filter-group { display: flex; flex-wrap: wrap; gap: 7px; }
        .quick-filter {
            padding: 6px 11px;
            border: 1px solid #dbe3ec;
            border-radius: 20px;
            color: #475569;
            background: #fff;
            font-size: .78rem;
            text-decoration: none !important;
            transition: all .15s ease;
        }
        .quick-filter:hover,
        .quick-filter.active { border-color: #0d6efd; color: #fff; background: #0d6efd; }
        #operationsTable thead th { white-space: nowrap; }
        .sticky-selection { position: sticky; bottom: 0; z-index: 5; background: #fff8db; box-shadow: 0 -5px 18px rgba(15, 23, 42, .09); }
    </style>
@endsection

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">{{ $admin->name }}</li>
@endsection

@section('content')
@if(isset($errors) && $errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="alert alert-info"><i class="fa fa-info-circle me-1"></i>{{ session('info') }}</div>
@endif
<div class="alert alert-info border-0 shadow-sm">
    <div class="fw-bold mb-1"><i class="fa fa-info-circle me-1"></i> تنبيه مهم عن حالات السداد</div>
    <div class="small">
        «سداد العميل» يعني أن العميل سدد قيمة العقد أو الفاتورة، وهو مستقل عن العمولة.
        أما «العمولة غير محتسبة» فتعني أن هذا العقد لم يدخل في دفعة احتساب عمولة، ولا يوجد تعارض بين الحالتين.
    </div>
</div>
@php
    $activeServiceLabel = !empty($filters['service_type'])
        ? ($visaTypeOptions[$filters['service_type']] ?? $filters['service_type'])
        : null;
    $activeCommissionLabel = match($filters['commission_status'] ?? '') {
        'pending' => 'غير محتسبة',
        'commissioned' => 'محتسبة',
        default => null,
    };
    $quickReportUrl = function (array $changes = [], array $remove = []) use ($admin, $filters) {
        $query = collect($filters)
            ->reject(fn ($value) => $value === null || $value === '')
            ->except($remove)
            ->merge($changes)
            ->all();

        return route('commissions.marketers.show', array_merge(['admin' => $admin->id], $query));
    };
@endphp

<div class="employee-head p-4 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white text-primary rounded-circle d-grid place-items-center fw-bold text-center pt-3" style="width:58px;height:58px;font-size:1.4rem;">{{ mb_substr($admin->name, 0, 1) }}</div>
        <div><h4 class="mb-1 fw-bold">{{ $admin->name }}</h4><div class="opacity-75">موظف خدمة عملاء · رقم #{{ $admin->id }}</div></div>
    </div>
    <div class="text-end">
        <div class="small opacity-75">{{ $balance < 0 ? 'مستحق للشركة على المسوق' : 'الرصيد المستحق للمسوق' }}</div>
        <div class="fs-3 fw-bold">{{ number_format(abs($balance), 2) }} <small class="fs-6">ر.س</small></div>
        @if($balance > 0)
            <a href="{{ route('commissions.marketers.payments.index', $admin->id) }}" class="btn btn-light btn-sm"><i class="fa fa-money-bill-wave me-1"></i> إصدار سند صرف</a>
        @endif
    </div>
</div>

@if($activeServiceLabel || $activeCommissionLabel)
    <div class="active-report-filter p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold text-primary mb-1"><i class="fa fa-filter me-1"></i> التقرير مفلتر حاليًا</div>
            <div class="d-flex flex-wrap gap-2">
                @if($activeServiceLabel)
                    <span class="badge bg-primary">{{ $activeServiceLabel }}</span>
                @endif
                @if($activeCommissionLabel)
                    <span class="badge {{ ($filters['commission_status'] ?? '') === 'pending' ? 'bg-warning text-dark' : 'bg-success' }}">
                        {{ $activeCommissionLabel }}
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-times me-1"></i> عرض كل العقود
        </a>
    </div>
@endif

<div class="card quick-filters mb-3">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-xl-8">
                <div class="small fw-bold text-muted mb-2">فلترة سريعة حسب نوع العقد</div>
                <div class="quick-filter-group">
                    <a href="{{ $quickReportUrl([], ['service_type']) }}"
                       class="quick-filter {{ empty($filters['service_type']) ? 'active' : '' }}">الكل</a>
                    @foreach([
                        'recruitment' => 'الاستقدام',
                        'rental' => 'عقود التأجير',
                        'service_transfer' => 'نقل الخدمات',
                        'electronic_ingaz_delegation' => 'التفاويض',
                    ] as $type => $label)
                        <a href="{{ $quickReportUrl(['service_type' => $type]) }}"
                           class="quick-filter {{ ($filters['service_type'] ?? '') === $type ? 'active' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-xl-4">
                <div class="small fw-bold text-muted mb-2">حالة العمولة</div>
                <div class="quick-filter-group">
                    <a href="{{ $quickReportUrl([], ['commission_status']) }}"
                       class="quick-filter {{ empty($filters['commission_status']) ? 'active' : '' }}">الكل</a>
                    <a href="{{ $quickReportUrl(['commission_status' => 'pending']) }}"
                       class="quick-filter {{ ($filters['commission_status'] ?? '') === 'pending' ? 'active' : '' }}">غير محتسبة</a>
                    <a href="{{ $quickReportUrl(['commission_status' => 'commissioned']) }}"
                       class="quick-filter {{ ($filters['commission_status'] ?? '') === 'commissioned' ? 'active' : '' }}">محتسبة</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['إجمالي العمليات', $summary['total'], 'primary', [], empty($filters['service_type']) && empty($filters['commission_status'])],
        ['غير محتسبة', $summary['pending'], 'warning', ['commission_status' => 'pending'], ($filters['commission_status'] ?? '') === 'pending' && empty($filters['service_type'])],
        ['محتسبة', $summary['commissioned'], 'success', ['commission_status' => 'commissioned'], ($filters['commission_status'] ?? '') === 'commissioned' && empty($filters['service_type'])],
        ['عقود الاستقدام', $summary['recruitment'], 'info', ['service_type' => 'recruitment'], ($filters['service_type'] ?? '') === 'recruitment' && empty($filters['commission_status'])],
    ] as [$label, $value, $color, $query, $active])
        <div class="col-lg-3 col-6">
            <a class="summary-tile-link {{ $active ? 'active' : '' }}"
               href="{{ route('commissions.marketers.show', array_merge(['admin' => $admin->id], $query)) }}">
                <div class="card summary-tile h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-bold text-{{ $color }}">{{ number_format($value) }}</div>
                        <div class="small text-muted mt-1">اضغط لعرض التقرير</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('commissions.marketers.show', $admin->id) }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">نوع العملية</label>
                    <select class="form-select" name="service_type">
                        <option value="">كل الأنواع</option>
                        @foreach($visaTypeOptions as $type => $label)
                            <option value="{{ $type }}" @selected(($filters['service_type'] ?? '') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">حالة العمولة</label>
                    <select class="form-select" name="commission_status">
                        <option value="">الكل</option>
                        <option value="pending" @selected(($filters['commission_status'] ?? '') === 'pending')>غير محتسبة</option>
                        <option value="commissioned" @selected(($filters['commission_status'] ?? '') === 'commissioned')>محتسبة</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">فلتر التاريخ</label>
                    <select class="form-select" id="dateFilterType" name="date_filter">
                        <option value="">كل التواريخ</option>
                        <option value="month_year" @selected(($filters['date_filter'] ?? '') === 'month_year')>شهر / سنة</option>
                        <option value="range" @selected(($filters['date_filter'] ?? '') === 'range')>من / إلى</option>
                    </select>
                </div>
                <div class="col-lg-1 col-md-3 filter-month-year">
                    <label class="form-label">الشهر</label>
                    <select class="form-select" name="month"><option value="">الكل</option>@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected(($filters['month'] ?? '') == $m)>{{ $m }}</option>@endfor</select>
                </div>
                <div class="col-lg-1 col-md-3 filter-month-year">
                    <label class="form-label">السنة</label>
                    <select class="form-select" name="year">@for($y=date('Y');$y>=2020;$y--)<option value="{{ $y }}" @selected(($filters['year'] ?? date('Y')) == $y)>{{ $y }}</option>@endfor</select>
                </div>
                <div class="col-lg-2 col-md-3 filter-range">
                    <label class="form-label">من</label><input type="date" class="form-control" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
                </div>
                <div class="col-lg-2 col-md-3 filter-range">
                    <label class="form-label">إلى</label><input type="date" class="form-control" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                </div>
                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="fa fa-filter me-1"></i> عرض</button>
                    <a href="{{ route('commissions.marketers.show', $admin->id) }}" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1 fw-bold">
                تقرير الطلبات والعقود
                @if($activeServiceLabel)<span class="text-primary">— {{ $activeServiceLabel }}</span>@endif
            </h5>
            <div class="small text-muted">في عقود التأجير يُحتسب صافي الفوترة: قيمة الفاتورة + التمديدات − الإشعارات الدائنة. لا تظهر العمولة للاحتساب قبل إصدار فاتورة للعميل.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllBtn">تحديد غير المحتسب في الصفحة</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">إلغاء التحديد</button>
        </div>
    </div>
    <form id="commissionForm" method="POST" action="{{ route('commissions.marketers.preview', $admin->id) }}">
        @csrf
        <div class="table-responsive">
            <table id="operationsTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead class="table-light"><tr>
                    <th></th><th>#</th><th>المصدر</th><th>رقم العملية</th><th>رقم الطلب</th><th>الخدمة</th><th>العميل</th><th>الحالة</th><th>التاريخ</th><th>بيانات الفوترة</th><th>قيمة الأساس</th><th>سداد العميل</th><th>حالة العمولة</th>
                </tr></thead>
            </table>
        </div>
        <div id="selectionSummary" class="sticky-selection border-top p-3" style="display:none;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><strong>المحدد:</strong> <span id="selectedCount" class="badge bg-primary">0</span> عملية · <strong>إجمالي الأساس:</strong> <span id="selectedTotal" class="text-primary fw-bold">0.00</span> ر.س</div>
                <button class="btn btn-warning fw-bold"><i class="fa fa-calculator me-1"></i> مراجعة وتحديد العمولة</button>
            </div>
        </div>
    </form>
</div>

@if($balanceTransactions->count())
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fa fa-history me-2"></i>آخر حركات الرصيد</h6></div>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead class="table-light"><tr><th>الحركة</th><th>المبلغ</th><th>الرصيد بعد</th><th>البيان</th><th>التاريخ</th></tr></thead>
        <tbody>@foreach($balanceTransactions as $tx)<tr>
            <td><span class="badge {{ $tx->type === 'credit' ? 'bg-success' : 'bg-danger' }}">{{ $tx->type === 'credit' ? 'إضافة' : ($tx->reference_type === 'commission_reversal' ? 'عكس عمولة' : ($tx->reference_type === 'commission_zero_adjustment' ? 'تصفير عمولة' : 'صرف')) }}</span></td>
            <td class="fw-bold">{{ $tx->type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }}</td>
            <td>{{ number_format($tx->balance_after, 2) }}</td><td>{{ $tx->notes }}</td><td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
        </tr>@endforeach</tbody>
    </table></div>
</div>
@endif
@endsection

@section('js')
<script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
<script>
$(function () {
    function toggleDateFilters() {
        const type = $('#dateFilterType').val();
        $('.filter-month-year')
            .toggle(type === 'month_year')
            .find(':input').prop('disabled', type !== 'month_year');
        $('.filter-range')
            .toggle(type === 'range')
            .find(':input').prop('disabled', type !== 'range');
    }
    toggleDateFilters();
    $('#dateFilterType').on('change', toggleDateFilters);

    const table = $('#operationsTable').DataTable({
        responsive: true, processing: true, serverSide: true, ordering: false, searching: true,
        stateSave: true, stateDuration: -1,
        language: { url: '{{ asset("admin/assets/js/bundle/ar.json") }}' },
        ajax: { url: '{{ route("commissions.marketers.show", $admin->id) }}', data: {
            service_type: @json($filters['service_type'] ?? ''),
            commission_status: @json($filters['commission_status'] ?? ''),
            date_filter: @json($filters['date_filter'] ?? ''),
            month: @json($filters['month'] ?? ''),
            year: @json($filters['year'] ?? ''),
            from_date: @json($filters['from_date'] ?? ''),
            to_date: @json($filters['to_date'] ?? '')
        }},
        columns: [
            {data:'checkbox', searchable:false}, {data:'DT_RowIndex', searchable:false},
            {data:'source_label'}, {data:'reference'}, {data:'order_reference'},
            {data:'service_type_label'}, {data:'client_name'}, {data:'status'},
            {data:'operation_date'}, {data:'billing_label', searchable:false}, {data:'base_amount', className:'text-end'},
            {data:'is_paid_label', searchable:false}, {data:'commissioned_label', searchable:false}
        ],
        drawCallback: updateSummary
    });

    function updateSummary() {
        const checked = $('.operation-check:checked');
        let total = 0;
        checked.each(function () { total += Number($(this).data('amount') || 0); });
        $('#selectedCount').text(checked.length);
        $('#selectedTotal').text(total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));
        $('#selectionSummary').toggle(checked.length > 0);
    }
    $(document).on('change', '.operation-check', updateSummary);
    $('#selectAllBtn').on('click', function () { $('.operation-check').prop('checked', true); updateSummary(); });
    $('#deselectAllBtn').on('click', function () { $('.operation-check').prop('checked', false); updateSummary(); });

    $(document).on('click', '.returned-commission-decision', async function () {
        const button = $(this);
        const decision = button.data('decision');
        const message = decision === 'deducted'
            ? 'سيتم خصم العمولة السابقة من رصيد المسوق وقد يصبح الرصيد مستحقًا للشركة. هل تريد الاعتماد؟'
            : 'سيتم إقفال التسوية بصفر دون أي خصم من رصيد المسوق. هل تريد الاعتماد؟';
        if (!window.confirm(message)) return;

        button.closest('div').find('button').prop('disabled', true);
        try {
            const response = await fetch(button.data('url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({decision})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'تعذر اعتماد التسوية.');
            table.ajax.reload(null, false);
            window.location.reload();
        } catch (error) {
            alert(error.message);
            button.closest('div').find('button').prop('disabled', false);
        }
    });
});
</script>
@endsection
