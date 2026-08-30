@extends('admin.layout.indexs.index')

@section('page-title', 'عمولات المسوقين')

@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('commissions.marketers.index') }}">نظام العمولات</a></li>
    <li class="breadcrumb-item active">المسوقون</li>
@endsection

@section('css')
<style>
    .commissions-hero {
        overflow: hidden;
        position: relative;
        border-radius: 18px;
        color: #fff;
        background: linear-gradient(135deg, #0f4c81 0%, #087f72 100%);
    }
    .commissions-hero::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        left: -90px;
        bottom: -170px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .09);
    }
    .overview-card,
    .marketer-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .07);
    }
    .overview-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        flex: 0 0 46px;
        border-radius: 13px;
    }
    .employee-avatar {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        flex: 0 0 48px;
        border-radius: 14px;
        color: #075985;
        background: #e0f2fe;
        font-size: 1.1rem;
        font-weight: 800;
    }
    .services-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr));
        gap: 10px;
    }
    .service-box {
        height: 100%;
        padding: 12px;
        border: 1px solid #e8edf3;
        border-radius: 13px;
        background: #fbfcfe;
    }
    .service-box__title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
        color: #334155;
        font-size: .82rem;
        font-weight: 700;
    }
    .service-box__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--service-color);
    }
    .service-counts {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
    }
    .report-count {
        display: block;
        padding: 7px 4px;
        border-radius: 9px;
        color: #334155;
        background: #fff;
        text-align: center;
        text-decoration: none !important;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .report-count:hover {
        color: #0f4c81;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 76, 129, .13);
    }
    .report-count strong {
        display: block;
        font-size: 1.05rem;
        line-height: 1.1;
    }
    .report-count small {
        display: block;
        margin-top: 4px;
        color: #94a3b8;
        font-size: .65rem;
        white-space: nowrap;
    }
    .marketer-finance {
        min-width: 190px;
        padding-right: 18px;
        border-right: 1px solid #edf0f4;
    }
    .pending-link {
        color: inherit;
        text-decoration: none !important;
    }
    .pending-link:hover .badge {
        box-shadow: 0 3px 10px rgba(245, 158, 11, .24);
    }
    .marketers-filter {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
    }
    .filter-input-group .input-group-text {
        color: #64748b;
        background: #fff;
        border-left: 0;
    }
    .filter-input-group .form-control {
        border-right: 0;
    }
    .filter-input-group .form-control:focus {
        box-shadow: none;
        border-color: #ced4da;
    }
    .empty-filter-results {
        display: none;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #fff;
    }
    @media (max-width: 1199.98px) {
        .services-grid { grid-template-columns: repeat(2, minmax(150px, 1fr)); }
        .marketer-finance { min-width: 100%; padding: 16px 0 0; border-top: 1px solid #edf0f4; border-right: 0; }
    }
    @media (max-width: 575.98px) {
        .services-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@php
    $services = [
        'recruitment' => ['label' => 'عقود الاستقدام', 'color' => '#2563eb'],
        'rental' => ['label' => 'عقود التأجير', 'color' => '#0891b2'],
        'transfer' => ['label' => 'نقل الخدمات', 'color' => '#d97706'],
        'delegation' => ['label' => 'التفاويض', 'color' => '#64748b'],
    ];
    $overview = [
        'marketers' => $marketers->count(),
        'operations' => $marketers->sum(fn ($marketer) => $marketer->commission_summary['total']),
        'pending' => $marketers->sum(fn ($marketer) => $marketer->commission_summary['pending']),
        'balances' => $marketers->where('commission_balance', '>', 0)->sum('commission_balance'),
        'receivables' => abs($marketers->where('commission_balance', '<', 0)->sum('commission_balance')),
    ];
@endphp

<div class="commissions-hero p-4 mb-4">
    <div class="position-relative d-flex flex-wrap justify-content-between align-items-center gap-3" style="z-index:1">
        <div>
            <div class="small opacity-75 mb-1">التشغيل ← نظام العمولات</div>
            <h3 class="mb-2 fw-bold">عمولات المسوقين</h3>
            <div class="opacity-75">مراجعة العقود والخدمات وحالة احتساب العمولة لكل مسوق من مكان واحد.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_SETTINGS))
                <a href="{{ route('commissions.settings.index') }}" class="btn btn-light">
                    <i class="fa fa-sliders-h me-1"></i> إعدادات العمولات
                </a>
            @endif
            @if(@AdminGuardHasPerm(\App\PermKeys::COMMISSIONS_PAY))
                <a href="{{ route('commissions.finance.index') }}" class="btn btn-outline-light">
                    <i class="fa fa-wallet me-1"></i> مركز الصرف المالي
                </a>
            @endif
        </div>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <div class="fw-bold mb-1"><i class="fa fa-info-circle me-1"></i> الفرق بين سداد العقد وسداد العمولة</div>
    <div class="small">
        سداد العميل لقيمة العقد لا يجعل العمولة محتسبة تلقائيًا.
        يظهر العقد «غير محتسب» حتى يُعتمد ضمن دفعة عمولة، بينما العمليات المستوردة من ملف العمولات المسددة تظهر كمحتسبة ومسـددة سابقًا.
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['المسوقون', $overview['marketers'], 'fa-users', 'primary'],
        ['إجمالي العقود والخدمات', $overview['operations'], 'fa-file-contract', 'info'],
        ['بانتظار الاحتساب', $overview['pending'], 'fa-hourglass-half', 'warning'],
        ['إجمالي الأرصدة المستحقة', number_format($overview['balances'], 2) . ' ر.س', 'fa-wallet', 'success'],
        ['مستحق للشركة من المسوقين', number_format($overview['receivables'], 2) . ' ر.س', 'fa-undo', 'danger'],
    ] as [$label, $value, $icon, $color])
        <div class="col-xl-3 col-md-6">
            <div class="card overview-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="overview-icon bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
                        <i class="fa {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">{{ $label }}</div>
                        <div class="fs-5 fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card marketers-filter mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-lg-5 col-md-6">
                <label for="marketerSearch" class="form-label small fw-bold">بحث سريع</label>
                <div class="input-group filter-input-group">
                    <input type="search" id="marketerSearch" class="form-control"
                           placeholder="اسم المسوق أو رقمه..." autocomplete="off">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                </div>
            </div>
            <div class="col-lg-3 col-md-3">
                <label for="serviceFilter" class="form-label small fw-bold">نوع الخدمة</label>
                <select id="serviceFilter" class="form-select">
                    <option value="">كل الخدمات</option>
                    @foreach($services as $key => $service)
                        <option value="{{ $key }}">{{ $service['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-3">
                <label for="profileStatusFilter" class="form-label small fw-bold">حالة الملف</label>
                <select id="profileStatusFilter" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="pending">لديه عقود غير محتسبة</option>
                    <option value="commissioned">لديه عقود محتسبة</option>
                    <option value="balance">لديه رصيد مستحق</option>
                    <option value="no_pending">لا توجد عقود معلقة</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-12">
                <button type="button" id="resetMarketerFilters" class="btn btn-outline-secondary w-100" title="إلغاء الفلاتر">
                    <i class="fa fa-undo"></i>
                    <span class="d-lg-none ms-1">إلغاء الفلاتر</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-1 fw-bold"><i class="fa fa-address-card text-primary me-2"></i>ملفات المسوقين</h5>
        <div class="text-muted small">اضغط على أي رقم لفتح تقرير العقود مفلترًا حسب النوع وحالة العمولة.</div>
    </div>
    <span class="badge bg-light text-dark border px-3 py-2">
        <span id="visibleMarketersCount">{{ number_format($marketers->count()) }}</span> مسوق
    </span>
</div>

<div id="marketersList" class="row g-3">
    @forelse($marketers as $employee)
        @php $summary = $employee->commission_summary; @endphp
        <div class="col-12 marketer-item"
             data-search="{{ mb_strtolower($employee->commission_display_name . ' ' . $employee->name . ' ' . $employee->id) }}"
             data-pending="{{ $summary['pending'] }}"
             data-commissioned="{{ $summary['commissioned'] }}"
             data-balance="{{ $employee->commission_balance }}"
             data-recruitment="{{ $summary['recruitment'] }}"
             data-rental="{{ $summary['rental'] }}"
             data-transfer="{{ $summary['transfer'] }}"
             data-delegation="{{ $summary['delegation'] }}">
            <div class="card marketer-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-center">
                        <div class="d-flex align-items-center gap-3" style="min-width:235px">
                            <div class="employee-avatar">{{ mb_substr($employee->commission_display_name, 0, 1) }}</div>
                            <div class="min-w-0">
                                <a href="{{ route('commissions.marketers.show', $employee->id) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ $employee->commission_display_name }}
                                </a>
                                <div class="text-muted small mt-1">
                                    #{{ $employee->id }} · {{ optional($employee->role)->name ?? 'خدمة العملاء' }}
                                </div>
                                <a href="{{ route('commissions.marketers.show', $employee->id) }}" class="small text-primary text-decoration-none">
                                    عرض التقرير الكامل <i class="fa fa-arrow-left ms-1"></i>
                                </a>
                            </div>
                        </div>

                        <div class="services-grid flex-grow-1">
                            @foreach($services as $key => $service)
                                @php
                                    $serviceType = [
                                        'recruitment' => 'recruitment',
                                        'rental' => 'rental',
                                        'transfer' => 'service_transfer',
                                        'delegation' => 'electronic_ingaz_delegation',
                                    ][$key];
                                @endphp
                                <div class="service-box" style="--service-color:{{ $service['color'] }}">
                                    <div class="service-box__title">
                                        <span class="service-box__dot"></span>
                                        {{ $service['label'] }}
                                    </div>
                                    <div class="service-counts">
                                        <a class="report-count"
                                           href="{{ route('commissions.marketers.show', ['admin' => $employee->id, 'service_type' => $serviceType]) }}"
                                           title="عرض كل {{ $service['label'] }}">
                                            <strong>{{ number_format($summary[$key]) }}</strong>
                                            <small>الإجمالي</small>
                                        </a>
                                        <a class="report-count"
                                           href="{{ route('commissions.marketers.show', ['admin' => $employee->id, 'service_type' => $serviceType, 'commission_status' => 'pending']) }}"
                                           title="عرض العقود غير المحتسبة">
                                            <strong class="text-warning">{{ number_format($summary[$key . '_pending']) }}</strong>
                                            <small>غير محتسبة</small>
                                        </a>
                                        <a class="report-count"
                                           href="{{ route('commissions.marketers.show', ['admin' => $employee->id, 'service_type' => $serviceType, 'commission_status' => 'commissioned']) }}"
                                           title="عرض العقود المحتسبة">
                                            <strong class="text-success">{{ number_format($summary[$key . '_commissioned']) }}</strong>
                                            <small>محتسبة</small>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="marketer-finance">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">غير محتسبة</span>
                                <a class="pending-link"
                                   href="{{ route('commissions.marketers.show', ['admin' => $employee->id, 'commission_status' => 'pending']) }}">
                                    <span class="badge {{ $summary['pending'] ? 'bg-warning text-dark' : 'bg-light text-muted border' }}">
                                        {{ number_format($summary['pending']) }}
                                    </span>
                                </a>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">عمولة محتسبة</span>
                                <strong class="text-success">{{ number_format($summary['commissioned_amount'], 2) }} ر.س</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">{{ $employee->commission_balance < 0 ? 'مستحق للشركة' : 'الرصيد المستحق' }}</span>
                                <strong class="{{ $employee->commission_balance > 0 ? 'text-success' : ($employee->commission_balance < 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ number_format(abs($employee->commission_balance), 2) }} ر.س
                                </strong>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('commissions.marketers.show', $employee->id) }}" class="btn btn-sm btn-primary flex-grow-1">
                                    <i class="fa fa-chart-bar me-1"></i> التقرير
                                </a>
                                @if($employee->commission_balance > 0)
                                    <a href="{{ route('commissions.marketers.payments.index', $employee->id) }}"
                                       class="btn btn-sm btn-outline-success" title="سندات الصرف">
                                        <i class="fa fa-receipt"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="fa fa-users fa-2x mb-3 opacity-50"></i>
                    <div>لا يوجد مسوقون مرتبطون بعمليات.</div>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div id="emptyFilterResults" class="empty-filter-results text-center text-muted py-5">
    <i class="fa fa-search fa-2x mb-3 opacity-50"></i>
    <div class="fw-bold mb-1">لا توجد نتائج مطابقة</div>
    <div class="small mb-3">جرّب تغيير البحث أو اختيار حالة أخرى.</div>
    <button type="button" class="btn btn-sm btn-outline-primary reset-marketer-filters">عرض كل المسوقين</button>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('marketerSearch');
    const serviceFilter = document.getElementById('serviceFilter');
    const statusFilter = document.getElementById('profileStatusFilter');
    const items = Array.from(document.querySelectorAll('.marketer-item'));
    const resultCount = document.getElementById('visibleMarketersCount');
    const emptyState = document.getElementById('emptyFilterResults');

    function normalized(value) {
        return String(value || '').trim().toLocaleLowerCase('ar');
    }

    function applyFilters() {
        const search = normalized(searchInput.value);
        const service = serviceFilter.value;
        const status = statusFilter.value;
        let visible = 0;

        items.forEach(function (item) {
            const matchesSearch = !search || normalized(item.dataset.search).includes(search);
            const matchesService = !service || Number(item.dataset[service] || 0) > 0;
            let matchesStatus = true;

            if (status === 'pending') matchesStatus = Number(item.dataset.pending) > 0;
            if (status === 'commissioned') matchesStatus = Number(item.dataset.commissioned) > 0;
            if (status === 'balance') matchesStatus = Number(item.dataset.balance) > 0;
            if (status === 'no_pending') matchesStatus = Number(item.dataset.pending) === 0;

            const matches = matchesSearch && matchesService && matchesStatus;
            item.style.display = matches ? '' : 'none';
            if (matches) visible++;
        });

        resultCount.textContent = visible.toLocaleString('en-US');
        emptyState.style.display = visible === 0 && items.length ? 'block' : 'none';
    }

    function resetFilters() {
        searchInput.value = '';
        serviceFilter.value = '';
        statusFilter.value = '';
        applyFilters();
        searchInput.focus();
    }

    searchInput.addEventListener('input', applyFilters);
    serviceFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    document.getElementById('resetMarketerFilters').addEventListener('click', resetFilters);
    document.querySelectorAll('.reset-marketer-filters').forEach(function (button) {
        button.addEventListener('click', resetFilters);
    });
});
</script>
@endsection
