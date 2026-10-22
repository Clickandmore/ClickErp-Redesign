@extends('admin.layout.indexs.index')
@section('page-title', 'طلبات التأجير بالساعة')

@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ——— لوحة طلبات الساعات ——— */
    .ho-hero { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 1.15rem 1.35rem; margin-bottom: 1rem; background: #fff; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, .08); }
    .ho-hero-main { display: flex; align-items: center; gap: .9rem; min-width: 0; }
    .ho-hero-icon { width: 50px; height: 50px; flex: 0 0 50px; border-radius: 15px; display: grid; place-items: center; background: linear-gradient(135deg, #6861ee, #8b85f5); color: #fff; font-size: 1.2rem; box-shadow: 0 8px 18px rgba(104, 97, 238, .3); }
    .ho-hero h4 { margin: 0; font-weight: 800; font-size: 1.2rem; color: var(--ht-ink); }
    .ho-hero p { margin: .2rem 0 0; font-size: .84rem; color: var(--ht-muted); }
    .ho-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .ho-hero-actions .btn { border-radius: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; }

    /* روابط المتابعة السريعة */
    .ho-quick { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem; }
    .ho-quick a { display: inline-flex; align-items: center; gap: .55rem; padding: .55rem .9rem; border-radius: 12px; background: #fff; border: 1px solid #e8ecf3; color: var(--ht-ink); font-size: .84rem; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(15, 23, 42, .04); transition: border-color .15s, transform .15s; }
    .ho-quick a:hover { border-color: #c7c4fa; transform: translateY(-1px); }
    .ho-quick a i { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; background: var(--q-tint, #f1f5f9); color: var(--q-tone, #475569); font-size: .8rem; }
    .ho-quick a b { min-width: 22px; padding: 0 .45rem; border-radius: 99px; background: var(--q-tone, #475569); color: #fff; font-size: .72rem; text-align: center; }
    .ho-q-amber { --q-tone: #b45309; --q-tint: #fef3c7; } .ho-q-red { --q-tone: #b91c1c; --q-tint: #fee2e2; } .ho-q-violet { --q-tone: #4f46e5; --q-tint: #eef2ff; }

    /* بطاقات الإحصاء القابلة للضغط */
    #hourly-order-summary .hd-stat { text-decoration: none; color: inherit; transition: transform .15s, box-shadow .15s; }
    #hourly-order-summary a.hd-stat:hover { transform: translateY(-2px); box-shadow: 0 14px 34px rgba(15, 23, 42, .1); }
    .ho-collect .ht-progress-bar { height: 6px; margin-top: .35rem; }

    .ho-section-title { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin: 1.6rem 0 .8rem; }
    .ho-section-title h4 { margin: 0; font-weight: 800; font-size: 1.08rem; color: var(--ht-ink); display: flex; align-items: center; gap: .5rem; }
    .ho-section-title h4 i { color: #6861ee; }
    .ho-section-title p { margin: .2rem 0 0; color: var(--ht-muted); font-size: .82rem; }

    /* آخر الطلبات */
    .ho-latest-card { margin-bottom: 1rem; overflow: hidden; }
    .ho-latest-tabs { display: flex; gap: .4rem; padding: .7rem 1rem 0; border-bottom: 1px solid #eef2f7; }
    .ho-latest-tabs .nav-link { border: 0; border-radius: 10px 10px 0 0; color: var(--ht-muted); font-weight: 700; padding: .7rem 1rem; display: inline-flex; align-items: center; gap: .4rem; }
    .ho-latest-tabs .nav-link.active { color: #4f46e5; background: #eef2ff; box-shadow: inset 0 -3px 0 #6861ee; }
    .ho-latest-table { min-width: 980px; }
    .ho-latest-table td { vertical-align: middle; }
    .ho-latest-summary { background: #fafbff; border-bottom: 1px solid #eef2f7; }

    /* الفلتر */
    .hd-filter-field .form-control { height: 46px; border-radius: 8px; border-color: #e5e7eb; }
    .hd-filter-field .form-control.is-set, .hd-filter-field .form-select.is-set { border-color: #c7c4fa; background-color: #f5f4ff; font-weight: 700; }
    .ho-advanced-toggle { border: 0; background: transparent; color: #6861ee; font-weight: 700; padding: 0; }

    /* شريط النتائج والتصدير */
    .ho-results-bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin: 0 0 .75rem; }
    .ho-results-bar > span { font-size: .86rem; color: var(--ht-muted); }
    .ho-results-bar > span b { color: var(--ht-ink); font-size: 1rem; }
    .ho-export { position: relative; }
    .ho-export > summary { list-style: none; display: inline-flex; align-items: center; gap: .45rem; padding: .5rem .9rem; border-radius: 10px; background: #fff; border: 1px solid #bbf7d0; color: #15803d; font-weight: 700; font-size: .84rem; cursor: pointer; }
    .ho-export > summary::-webkit-details-marker { display: none; }
    .ho-export[open] > summary { background: #f0fdf4; }
    .ho-export-panel { position: absolute; inset-inline-end: 0; top: calc(100% + 6px); z-index: 30; width: min(560px, 92vw); padding: 1rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 18px 40px rgba(15, 23, 42, .14); }
    .ho-export-panel h6 { margin: 0 0 .7rem; font-weight: 800; font-size: .9rem; }
    .ho-export-cols { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .35rem .75rem; margin-bottom: .9rem; }
    .ho-export-cols label { display: flex; align-items: center; gap: .4rem; font-size: .82rem; margin: 0; cursor: pointer; }
    .ho-export-cols input { accent-color: #16a34a; }

    /* جدول الطلبات */
    .ho-table td { vertical-align: top; padding-top: .85rem; padding-bottom: .85rem; }
    .ho-table tbody tr { box-shadow: inset -4px 0 0 var(--row-tone, transparent); }
    .ho-table tbody tr:hover > td { background: #fafbff; }
    .ho-tone-active { --row-tone: #22c55e; } .ho-tone-pending_confirmation { --row-tone: #f59e0b; } .ho-tone-expired, .ho-tone-cancelled { --row-tone: #ef4444; } .ho-tone-completed { --row-tone: #94a3b8; }
    .ho-order-no { font-family: ui-monospace, Menlo, monospace; font-weight: 800; font-size: .95rem; text-decoration: none; white-space: nowrap; color: #4f46e5; }
    .ho-order-no:hover { text-decoration: underline; }
    .ho-source { display: inline-flex; align-items: center; gap: .3rem; margin-top: .3rem; padding: .1rem .5rem; border-radius: 7px; background: #f1f5f9; color: #475569; font-size: .72rem; font-weight: 700; white-space: nowrap; width: fit-content; }
    .ho-sub { display: block; font-size: .76rem; color: var(--ht-muted); margin-top: .2rem; }
    .ho-sub.ho-tight { white-space: nowrap; }
    .ho-sub i { width: 13px; color: #a3aec0; }
    .ho-ltr { display: inline-block; direction: ltr; }
    .ho-customer strong, .ho-service strong { color: var(--ht-ink); }
    .ho-wallet { color: #15803d; font-weight: 700; }
    .ho-bal { min-width: 170px; }
    .ho-bal-bar { display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: #f1f5f9; }
    .ho-bal-bar i { display: block; height: 100%; }
    .ho-bal-available { background: #22c55e; } .ho-bal-reserved { background: #6861ee; } .ho-bal-consumed { background: #94a3b8; } .ho-bal-expired { background: #ef4444; }
    .ho-bal-keys { display: flex; flex-wrap: wrap; gap: .25rem .6rem; margin-top: .4rem; font-size: .74rem; color: var(--ht-muted); }
    .ho-bal-keys span { display: inline-flex; align-items: center; gap: .25rem; }
    .ho-bal-keys b { color: var(--ht-ink); font-variant-numeric: tabular-nums; }
    .ho-key { width: 8px; height: 8px; border-radius: 3px; display: inline-block; }
    .ho-state { display: grid; gap: .4rem; justify-items: start; min-width: 130px; }
    .ho-expiry { font-size: .76rem; color: var(--ht-muted); }
    .ho-pay { min-width: 170px; }
    .ho-pay strong { font-size: .95rem; color: var(--ht-ink); }
    .ho-pay .ht-progress-bar { height: 6px; }
    .ho-pay-row { display: flex; align-items: center; gap: .5rem; margin-top: .35rem; }
    .ho-actions { display: flex; flex-direction: column; align-items: stretch; gap: .35rem; }
    .ho-actions .ht-icon-btn { width: 34px; height: 34px; display: inline-grid; place-items: center; border-radius: 9px; }
    .ho-actions-row { display: flex; gap: .35rem; }
    .ho-actions .send-payment-link { border-radius: 9px; font-size: .74rem; white-space: nowrap; }
</style>
@endsection

@section('content')
@php
    $cityNames = $cities->pluck('name_ar', 'id');
    $activeFilters = count(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
    $advancedFilterKeys = ['customer_name', 'order_number', 'unique_id', 'short_id', 'coupon_code', 'customer_source', 'admin_id', 'service_id', 'hours', 'installment', 'user_id'];
    $advancedFilters = count(array_filter(collect($filters)->only($advancedFilterKeys)->all(), fn ($value) => $value !== null && $value !== ''));
    $shortOrderNumber = fn ($order) => str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
    // حالة الطلب: شراء الرصيد ← تأكيد الصرّاف ← نشط حتى تاريخ الصلاحية
    $statusChip = fn ($status) => match ($status) {
        'pending_confirmation' => ['hd-chip-warn', 'fa-hourglass-half'],
        'active' => ['ht-chip-on', 'fa-circle-check'],
        'awaiting_payment' => ['hd-chip-warn', 'fa-file-invoice-dollar'],
        'expired' => ['ht-chip-off', 'fa-circle-xmark'],
        default => ['ht-chip-off', 'fa-circle'],
    };
    $paymentChip = fn ($status) => match ($status) {
        'paid' => ['ht-chip-on', 'fa-circle-check'],
        'partial' => ['hd-chip-warn', 'fa-circle-half-stroke'],
        default => ['hd-chip-danger', 'fa-circle-exclamation'],
    };
    $money = fn ($amount) => number_format((float) $amount, 2).' '.__('hourly.sar');
    // Laravel 8 لا يدعم @selected؛ هذه الدالة تعيد selected حين تطابق قيمة الفلتر.
    $sel = fn ($key, $value) => (string) ($filters[$key] ?? '') === (string) $value ? 'selected' : '';
    $collectedPercent = $summary->total_amount > 0 ? min(100, round($summary->paid_amount / $summary->total_amount * 100)) : 0;
    $pendingIncidents = \Illuminate\Support\Facades\Schema::hasTable('hourly_service_incidents')
        ? \Illuminate\Support\Facades\DB::table('hourly_service_incidents')->where('status', 'pending')->count() : null;
@endphp

<div class="container-fluid ht-page" dir="rtl">
@include('admin.hourly.messages')

<header class="ho-hero">
    <div class="ho-hero-main">
        <span class="ho-hero-icon"><i class="fa fa-calendar-check"></i></span>
        <div>
            <h4>طلبات التأجير بالساعة</h4>
            <p>كل طلب يشتري رصيد زيارات: ينتظر تأكيد الصرّاف، ثم يصبح نشطًا وتُستهلك زياراته حتى تاريخ الصلاحية.</p>
        </div>
    </div>
    <div class="ho-hero-actions">
        <a class="btn btn-outline-warning" href="{{ route('admin.hourly-orders.index', ['status' => 'pending_confirmation']) }}#all-orders">
            <i class="fa fa-hourglass-half"></i> طابور الصرّاف
            @if($summary->pending_count)<span class="badge rounded-pill bg-warning text-dark ht-num">{{ $summary->pending_count }}</span>@endif
        </a>
        @if(AdminGuardHasPerm('hourly_orders_create'))
            <a class="btn btn-primary" href="{{ route('admin.hourly-orders.create') }}"><i class="fa fa-plus"></i> إنشاء طلب</a>
        @endif
    </div>
</header>

<div id="hourly-order-summary" class="hd-stats">
    <a class="hd-stat" href="{{ route('admin.hourly-orders.index') }}#all-orders"><div class="hd-stat-icon"><i class="fa fa-receipt"></i></div><div><span>إجمالي الطلبات</span><strong class="ht-num">{{ number_format($summary->orders_count) }}</strong></div></a>
    <a class="hd-stat hd-stat-warn" href="{{ route('admin.hourly-orders.index', ['status' => 'pending_confirmation']) }}#all-orders"><div class="hd-stat-icon"><i class="fa fa-hourglass-half"></i></div><div><span>{{ __('hourly.pending_confirmation') }}</span><strong class="ht-num">{{ number_format($summary->pending_count) }}</strong></div></a>
    <a class="hd-stat hd-stat-on" href="{{ route('admin.hourly-orders.index', ['status' => 'active']) }}#all-orders"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>{{ __('hourly.active') }}</span><strong class="ht-num">{{ number_format($summary->active_count) }}</strong></div></a>
    <a class="hd-stat hd-stat-off" href="{{ route('admin.hourly-orders.index', ['status' => 'expired']) }}#all-orders"><div class="hd-stat-icon"><i class="fa fa-circle-xmark"></i></div><div><span>{{ __('hourly.expired') }}</span><strong class="ht-num">{{ number_format($summary->expired_count) }}</strong></div></a>
    <div class="hd-stat hd-stat-info ho-collect"><div class="hd-stat-icon"><i class="fa fa-sack-dollar"></i></div>
        <div class="flex-grow-1"><span>المحصَّل من الإجمالي · {{ $collectedPercent }}%</span><strong class="ht-num">{{ number_format($summary->paid_amount, 2) }}</strong>
            <span class="ht-num">من {{ $money($summary->total_amount) }}</span>
            <div class="ht-progress-bar"><span style="width: {{ $collectedPercent }}%"></span></div>
        </div>
    </div>
</div>

{{-- روابط المتابعة اليومية خارج هذه الصفحة --}}
<nav class="ho-quick" aria-label="متابعة سريعة">
    @if($pendingIncidents !== null && \App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-incidents.index'))
        <a class="ho-q-red" href="{{ route('admin.hourly-incidents.index') }}"><i class="fa fa-notes-medical"></i> بلاغات تعذر التنفيذ @if($pendingIncidents)<b>{{ $pendingIncidents }}</b>@endif</a>
    @endif
    @if(\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-recovery.index') || AdminGuardHasPerm('hourly_orders_view'))
    <a class="ho-q-amber" href="{{ route('admin.hourly-recovery.index') }}"><i class="fa fa-calendar-xmark"></i> طلبات معالجة الزيارات غير المنفذة</a>
    @endif
    @if(\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-visits.unassigned'))
    <a class="ho-q-violet" href="{{ route('admin.hourly-visits.unassigned') }}"><i class="fa fa-user-clock"></i> الزيارات غير المخصصة</a>
    @endif
</nav>

<div class="ho-section-title">
    <div>
        <h4><i class="fa fa-bolt"></i> أحدث الطلبات</h4>
        <p>آخر طلبات العملاء من الموقع والتطبيق وطلبات موظفي الفروع في مكان واحد.</p>
    </div>
</div>
<div class="ht-card ho-latest-card">
    <ul class="nav nav-tabs ho-latest-tabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#latest-customer-orders" type="button" role="tab"><i class="fa fa-mobile-screen-button"></i> Web / Mobile Apps <span class="badge bg-light text-dark">{{ $latestCustomerOrders->count() }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#latest-employee-orders" type="button" role="tab"><i class="fa fa-user-tie"></i> موظف الفرع <span class="badge bg-light text-dark">{{ $latestEmployeeOrders->count() }}</span></button></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="latest-customer-orders" role="tabpanel">
            @include('admin.hourly._latest-orders-table', ['latestOrders' => $latestCustomerOrders, 'description' => 'أحدث الطلبات التي أنشأها العملاء من الموقع أو تطبيقات الجوال.', 'source' => 'customer'])
        </div>
        <div class="tab-pane fade" id="latest-employee-orders" role="tabpanel">
            @include('admin.hourly._latest-orders-table', ['latestOrders' => $latestEmployeeOrders, 'description' => 'أحدث الطلبات التي أنشأها موظفو الفروع من لوحة التحكم.', 'source' => 'admin'])
        </div>
    </div>
</div>

<div class="ho-section-title" id="all-orders">
    <div>
        <h4><i class="fa fa-list"></i> جميع الطلبات</h4>
        <p>ابحث وصفِّ الطلبات، ثم افتح الطلب لإدارة زياراته ودفعاته.</p>
    </div>
</div>

@include('admin.hourly._filter-loader', ['id' => 'hourly-order-filter-loader', 'title' => 'جارٍ تحديث الطلبات'])
<p id="hourly-order-filter-status" class="alert" role="status" aria-live="polite" hidden></p>
<section id="hourly-order-results" class="hx-results">
<form id="hourly-order-filter" method="GET" action="{{ route('admin.hourly-orders.index') }}#all-orders" class="hd-filter mb-3">
    <div class="hd-filter-head">
        <div class="hd-filter-title"><i class="fa fa-sliders"></i> فلترة الطلبات
            @if($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif
        </div>
        <div class="hd-filter-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="رقم الطلب أو الفاتورة أو اسم العميل أو جواله" aria-label="بحث في الطلبات">
        </div>
    </div>
    <div class="hd-filter-body">
        <div class="hd-filter-field"><label for="f_status"><i class="fa fa-flag"></i> {{ __('hourly.status') }}</label>
            <select id="f_status" name="status" class="form-select {{ !empty($filters['status']) ? 'is-set' : '' }}">
                <option value="">كل الحالات</option>
                @foreach(['pending_confirmation', 'active', 'awaiting_payment', 'expired'] as $status)
                    <option value="{{ $status }}" {{ $sel('status', $status) }}>{{ __('hourly.'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_payment"><i class="fa fa-money-bill"></i> حالة السداد</label>
            <select id="f_payment" name="payment_status" class="form-select {{ !empty($filters['payment_status']) ? 'is-set' : '' }}">
                <option value="">كل حالات السداد</option>
                <option value="pending_approval" {{ $sel('payment_status', 'pending_approval') }}>قيد الموافقة</option>
                <option value="cancelled" {{ $sel('payment_status', 'cancelled') }}>ملغى</option>
                @foreach(['unpaid', 'partial', 'paid'] as $status)
                    <option value="{{ $status }}" {{ $sel('payment_status', $status) }}>{{ __('hourly.'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_source"><i class="fa fa-code-branch"></i> مصدر الطلب</label>
            <select id="f_source" name="source" class="form-select {{ !empty($filters['source']) ? 'is-set' : '' }}">
                <option value="">كل المصادر</option>
                <option value="customer" {{ $sel('source', 'customer') }}>Web / Mobile Apps</option>
                <option value="admin" {{ $sel('source', 'admin') }}>موظف الفرع</option>
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_city"><i class="fa fa-city"></i> المدينة</label>
            <select id="f_city" name="city_id" class="form-select {{ !empty($filters['city_id']) ? 'is-set' : '' }}">
                <option value="">كل المدن</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ $sel('city_id', $city->id) }}>{{ $city->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_from"><i class="fa fa-calendar-day"></i> من تاريخ الإنشاء</label>
            <input id="f_from" name="from" type="date" class="form-control {{ !empty($filters['from']) ? 'is-set' : '' }}" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="hd-filter-field"><label for="f_to"><i class="fa fa-calendar-day"></i> إلى تاريخ الإنشاء</label>
            <input id="f_to" name="to" type="date" class="form-control {{ !empty($filters['to']) ? 'is-set' : '' }}" value="{{ $filters['to'] ?? '' }}">
        </div>
    </div>
    <div class="collapse {{ $advancedFilters ? 'show' : '' }}" id="hourlyAdvancedFilters">
        <div class="hd-filter-body border-top">
            @include('admin.hourly._order-filters-extra')
            <div class="hd-filter-field"><label for="f_user"><i class="fa fa-user"></i> رقم العميل</label>
                <input id="f_user" name="user_id" type="number" min="1" class="form-control {{ !empty($filters['user_id']) ? 'is-set' : '' }}" value="{{ $filters['user_id'] ?? '' }}" placeholder="مثال: 1024">
            </div>
        </div>
    </div>
    <div class="hd-filter-foot">
        <button class="ho-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#hourlyAdvancedFilters" aria-expanded="{{ $advancedFilters ? 'true' : 'false' }}"><i class="fa fa-sliders ms-1"></i> خيارات متقدمة @if($advancedFilters)<span class="badge bg-primary">{{ $advancedFilters }}</span>@endif</button>
        <div class="d-flex gap-2">
            @if($activeFilters)<a class="btn btn-light rounded-3" data-order-reset href="{{ route('admin.hourly-orders.index') }}#all-orders"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>@endif
            <button class="btn btn-primary rounded-3"><i class="fa fa-filter ms-1"></i> تصفية</button>
        </div>
    </div>
</form>

<div class="ho-results-bar">
    <span>النتائج: <b class="ht-num">{{ number_format($orders->total()) }}</b> طلب{{ $activeFilters ? ' مطابق للفلاتر' : '' }}</span>
    @include('admin.hourly._order-export')
</div>

<div class="ht-card"><div class="ht-body">
    <div class="table-responsive">
        <table class="ht-table ho-table">
            <thead><tr>
                <th>الطلب</th>
                <th>العميل</th>
                <th>الخدمة والباقة</th>
                <th>رصيد الزيارات</th>
                <th>الحالة والصلاحية</th>
                <th>السداد</th>
                <th><span class="visually-hidden">إجراءات</span></th>
            </tr></thead>
            <tbody>
            @forelse($orders as $order)
                @php
                    $reserved = max(0, $order->visits_count - $order->visits_available - $order->visits_consumed - $order->visits_expired - $order->visits_cancelled);
                    $visits = max(1, $order->visits_count);
                    [$statusClass, $statusIcon] = $statusChip($order->status);
                    [$payClass, $payIcon] = $paymentChip($order->payment_status);
                    $paidPercent = $order->total_amount > 0 ? min(100, round($order->paid_amount / $order->total_amount * 100)) : 0;
                    $daysLeft = $order->expires_at ? (int) now()->startOfDay()->diffInDays($order->expires_at->startOfDay(), false) : null;
                @endphp
                <tr class="ho-tone-{{ $order->status }}">
                    <td>
                        <a class="ho-order-no" href="{{ route('admin.hourly-orders.show', $order) }}" title="رقم الطلب الكامل: {{ $order->order_number }}">#{{ $shortOrderNumber($order) }}</a>
                        <span class="ho-source"><i class="fa {{ $order->source === 'admin' ? 'fa-user-tie' : 'fa-mobile-screen' }}"></i> {{ $order->source === 'admin' ? 'موظف الفرع' : 'Web / Mobile Apps' }}</span>
                        <span class="ho-sub ho-tight"><i class="fa fa-clock"></i> <span class="ho-ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</span></span>
                    </td>
                    <td class="ho-customer">
                        <strong>{{ optional($order->user)->name ?: 'عميل محذوف' }}</strong>
                        <span class="ho-sub ho-tight"><i class="fa fa-phone"></i> <span class="ho-ltr">{{ optional($order->user)->phone }}</span></span>
                        <span class="ho-sub"><i class="fa fa-location-dot"></i> {{ $cityNames[$order->city_id] ?? '—' }}{{ $order->district ? ' · '.$order->district : '' }}</span>
                        <span class="ho-sub ho-wallet"><i class="fa fa-wallet"></i> الرصيد {{ number_format($order->user?->hourly_wallet_balance ?? 0, 2) }} ر.س</span>
                    </td>
                    <td class="ho-service">
                        <strong>{{ optional($order->serviceCategory)->name ?: '—' }}</strong>
                        <span class="ho-sub">{{ $order->package_snapshot['title'] ?? '—' }}</span>
                        <span class="ho-sub ho-tight ht-num">{{ $order->visits_total }} زيارة · {{ rtrim(rtrim(number_format($order->hours_per_visit, 2), '0'), '.') }} ساعة · {{ $order->workers_per_visit }} عاملة</span>
                        <span class="ho-sub ho-tight"><i class="fa fa-play"></i> البدء <span class="ho-ltr">{{ $order->starts_at?->format('Y-m-d H:i') ?: '—' }}</span></span>
                    </td>
                    <td>
                        <div class="ho-bal">
                            <div class="ho-bal-bar" role="img" aria-label="رصيد الزيارات: {{ $order->visits_available }} متاحة، {{ $reserved }} محجوزة، {{ $order->visits_consumed }} مستهلكة، {{ $order->visits_expired }} منتهية">
                                <i class="ho-bal-available" style="width: {{ $order->visits_available / $visits * 100 }}%"></i>
                                <i class="ho-bal-reserved" style="width: {{ $reserved / $visits * 100 }}%"></i>
                                <i class="ho-bal-consumed" style="width: {{ $order->visits_consumed / $visits * 100 }}%"></i>
                                <i class="ho-bal-expired" style="width: {{ $order->visits_expired / $visits * 100 }}%"></i>
                            </div>
                            <div class="ho-bal-keys">
                                <span><i class="ho-key ho-bal-available"></i> متاحة <b>{{ $order->visits_available }}</b></span>
                                <span><i class="ho-key ho-bal-reserved"></i> محجوزة <b>{{ $reserved }}</b></span>
                                <span><i class="ho-key ho-bal-consumed"></i> مستهلكة <b>{{ $order->visits_consumed }}</b></span>
                                @if($order->visits_expired)<span><i class="ho-key ho-bal-expired"></i> منتهية <b>{{ $order->visits_expired }}</b></span>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="ho-state">
                            <span class="ht-chip {{ $statusClass }}"><i class="fa {{ $statusIcon }}"></i> {{ \App\Services\Hourly\HourlyOrderService::statusLabel($order) }}</span>
                            <span class="ho-expiry"><i class="fa fa-calendar-day"></i> حتى <span class="ht-num ho-ltr">{{ optional($order->expires_at)->format('Y-m-d') ?: '—' }}</span></span>
                            @if($daysLeft !== null)
                                @if($daysLeft < 0)
                                    <span class="ht-chip hd-chip-danger"><i class="fa fa-ban"></i> انتهت الصلاحية</span>
                                @elseif($daysLeft <= 7)
                                    <span class="ht-chip hd-chip-warn ht-num"><i class="fa fa-triangle-exclamation"></i> {{ $daysLeft }} يوم متبقٍ</span>
                                @else
                                    <span class="ht-chip ht-chip-off ht-num">{{ $daysLeft }} يوم متبقٍ</span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="ho-pay">
                            <strong class="ht-num">{{ $money($order->total_amount) }}</strong>
                            <span class="ho-sub ht-num">الضريبة {{ $money($order->tax_amount) }}</span>
                            <div class="ho-pay-row">
                                <span class="ht-chip {{ $payClass }}"><i class="fa {{ $payIcon }}"></i> {{ __('hourly.'.$order->payment_status) }}</span>
                                <small class="ht-num">{{ $paidPercent }}%</small>
                            </div>
                            <div class="ht-progress mt-1"><div class="ht-progress-bar {{ $paidPercent === 0 ? 'is-zero' : ($paidPercent < 100 ? 'is-low' : '') }}"><span style="width: {{ $paidPercent }}%"></span></div></div>
                            <span class="ho-sub ho-tight ht-num">{{ $money($order->paid_amount) }} من {{ $money($order->total_amount) }}</span>
                            <span class="ho-sub ho-tight"><i class="fa fa-credit-card"></i> {{ __('hourly.'.$order->payment_method) }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="ho-actions">
                            <div class="ho-actions-row">
                                <a class="btn btn-sm btn-primary ht-icon-btn" href="{{ route('admin.hourly-orders.show', $order) }}" title="إدارة الطلب" aria-label="إدارة الطلب {{ $order->order_number }}"><i class="fa fa-eye"></i></a>
                                <a class="btn btn-sm btn-outline-secondary ht-icon-btn" href="{{ route('admin.hourly-orders.invoice', $order) }}" target="_blank" title="الفاتورة PDF" aria-label="فاتورة الطلب {{ $order->order_number }}"><i class="fa fa-file-pdf"></i></a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <div class="ht-empty">
                        <i class="fa fa-calendar-xmark"></i>
                        {{ $activeFilters ? 'لا توجد طلبات مطابقة للفلاتر.' : 'لا توجد طلبات بعد.' }}
                        @if($activeFilters)<div class="mt-2"><a class="btn btn-sm btn-light rounded-3" data-order-reset href="{{ route('admin.hourly-orders.index') }}#all-orders"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a></div>@endif
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($orders->hasPages())<div class="ht-foot">{{ $orders->fragment('all-orders')->links() }}</div>@endif
</div>
</section>
</div>
@endsection

@push('js')
<script src="{{ asset('admin/assets/js/hourly-order-filters.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-order-filters.js')) }}" defer></script>
@endpush
