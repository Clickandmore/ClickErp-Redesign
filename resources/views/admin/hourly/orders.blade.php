@extends('admin.layout.indexs.index')
@section('page-title', 'طلبات التأجير بالساعة')

@section('style')
@include('admin.hourly_teams._style')
<style>
    /* شريط رصيد الزيارات: متاحة / محجوزة / مستهلكة / منتهية */
    .ho-bal { min-width: 170px; }
    .ho-bal-bar { display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: #f1f5f9; }
    .ho-bal-bar i { display: block; height: 100%; }
    .ho-bal-available { background: #22c55e; }
    .ho-bal-reserved  { background: #6861ee; }
    .ho-bal-consumed  { background: #94a3b8; }
    .ho-bal-expired   { background: #ef4444; }
    .ho-bal-keys { display: flex; flex-wrap: wrap; gap: .25rem .6rem; margin-top: .4rem; font-size: .74rem; color: var(--ht-muted); }
    .ho-bal-keys span { display: inline-flex; align-items: center; gap: .25rem; }
    .ho-bal-keys b { color: var(--ht-ink); font-variant-numeric: tabular-nums; }
    .ho-key { width: 8px; height: 8px; border-radius: 3px; display: inline-block; }
    .ho-order-no { font-family: ui-monospace, Menlo, monospace; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .ho-sub { font-size: .75rem; color: var(--ht-muted); }
    .ho-sub.ho-tight { white-space: nowrap; }
    /* الأرقام والتواريخ تُقرأ من اليسار حتى داخل صفحة عربية */
    .ho-ltr { display: inline-block; direction: ltr; }
    .ho-pay { min-width: 160px; }
    .ho-pay .ht-progress-bar { height: 6px; }
    .hd-filter-field .form-control { height: 46px; border-radius: 8px; border-color: #e5e7eb; }
    .hd-filter-field .form-control.is-set { border-color: #bbf7d0; background-color: #f0fdf4; font-weight: 700; }
    .ho-dashboard-title { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: 1.5rem 0 .75rem; }
    .ho-dashboard-title h4 { margin: 0; font-weight: 800; color: var(--ht-ink); }
    .ho-dashboard-title p { margin: .25rem 0 0; color: var(--ht-muted); }
    .ho-latest-card { margin-bottom: 1rem; overflow: hidden; }
    .ho-latest-card .ht-head { border-bottom: 1px solid #eef2f7; }
    .ho-latest-tabs { display: flex; gap: .5rem; padding: .75rem 1rem 0; border-bottom: 1px solid #eef2f7; }
    .ho-latest-tabs .nav-link { border: 0; border-radius: 10px 10px 0 0; color: var(--ht-muted); font-weight: 700; padding: .75rem 1rem; }
    .ho-latest-tabs .nav-link.active { color: #fff; background: #6861ee; }
    .ho-latest-table { min-width: 980px; }
    .ho-latest-table td { vertical-align: middle; }
    .ho-latest-summary { background: #fafbff; border-bottom: 1px solid #eef2f7; }
    .ho-all-orders-title { display: flex; align-items: center; gap: .6rem; margin: 1.5rem 0 .75rem; font-weight: 800; }
    .ho-advanced-toggle { border: 0; background: transparent; color: #6861ee; font-weight: 700; }
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
        'expired' => ['ht-chip-off', 'fa-circle-xmark'],
        default => ['ht-chip-off', 'fa-circle'],
    };
    $paymentChip = fn ($status) => match ($status) {
        'paid' => ['ht-chip-on', 'fa-circle-check'],
        'partial' => ['hd-chip-warn', 'fa-circle-half-stroke'],
        default => ['hd-chip-danger', 'fa-circle-exclamation'],
    };
    $money = fn ($amount) => number_format((float) $amount, 2).' '.__('hourly.sar');
@endphp

<div class="container-fluid ht-page" dir="rtl">
@include('admin.hourly.messages')

<div class="ht-card"><div class="ht-head">
    <div class="ht-head-icon"><i class="fa fa-calendar-check"></i></div>
    <div>
        <h5>طلبات التأجير بالساعة</h5>
        <p>كل طلب يشتري رصيد زيارات: ينتظر تأكيد الصرّاف، ثم يصبح نشطًا وتُستهلك زياراته حتى تاريخ الصلاحية.</p>
    </div>
    <div class="ht-actions">
        <a class="btn btn-outline-warning rounded-3" href="{{ route('admin.hourly-orders.index', ['status' => 'pending_confirmation']) }}">
            <i class="fa fa-hourglass-half ms-1"></i> طابور الصرّاف
            @if($summary->pending_count)<span class="badge rounded-pill bg-warning text-dark ht-num">{{ $summary->pending_count }}</span>@endif
        </a>
        @if(AdminGuardHasPerm('hourly_orders_create'))
            <a class="btn btn-primary rounded-3" href="{{ route('admin.hourly-orders.create') }}"><i class="fa fa-plus ms-1"></i> إنشاء طلب</a>
        @endif
    </div>
</div></div>

<div class="hd-stats">
    <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-receipt"></i></div><div><span>إجمالي الطلبات</span><strong class="ht-num">{{ number_format($summary->orders_count) }}</strong></div></div>
    <div class="hd-stat hd-stat-warn"><div class="hd-stat-icon"><i class="fa fa-hourglass-half"></i></div><div><span>{{ __('hourly.pending_confirmation') }}</span><strong class="ht-num">{{ number_format($summary->pending_count) }}</strong></div></div>
    <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>{{ __('hourly.active') }}</span><strong class="ht-num">{{ number_format($summary->active_count) }}</strong></div></div>
    <div class="hd-stat hd-stat-off"><div class="hd-stat-icon"><i class="fa fa-circle-xmark"></i></div><div><span>{{ __('hourly.expired') }}</span><strong class="ht-num">{{ number_format($summary->expired_count) }}</strong></div></div>
    <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-sack-dollar"></i></div>
        <div><span>المحصَّل من الإجمالي</span><strong class="ht-num">{{ number_format($summary->paid_amount, 2) }}</strong>
        <span class="ht-num">من {{ $money($summary->total_amount) }}</span></div>
    </div>
</div>

<div class="ho-dashboard-title">
    <div>
        <h4><i class="fa fa-gauge-high ms-1"></i> لوحة تحكم الطلبات</h4>
        <p>متابعة آخر طلبات العملاء من الموقع والتطبيق وطلبات موظفي الفروع في مكان واحد.</p>
    </div>
</div>

<div class="ht-card ho-latest-card">
    <ul class="nav nav-tabs ho-latest-tabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#latest-customer-orders" type="button" role="tab"><i class="fa fa-mobile-screen-button ms-1"></i> Web / Mobile Apps <span class="badge bg-light text-dark ms-1">{{ $latestCustomerOrders->count() }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#latest-employee-orders" type="button" role="tab"><i class="fa fa-user-tie ms-1"></i> موظف الفرع <span class="badge bg-light text-dark ms-1">{{ $latestEmployeeOrders->count() }}</span></button></li>
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

<h4 id="all-orders" class="ho-all-orders-title"><i class="fa fa-list"></i> جميع الطلبات</h4>

<form method="GET" action="{{ route('admin.hourly-orders.index') }}#all-orders" class="hd-filter mb-3">
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
                @foreach(['pending_confirmation', 'active', 'expired'] as $status)
                    <option value="{{ $status }}" @if(($filters['status'] ?? '') === $status) selected @endif>{{ __('hourly.'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_payment"><i class="fa fa-money-bill"></i> حالة السداد</label>
            <select id="f_payment" name="payment_status" class="form-select {{ !empty($filters['payment_status']) ? 'is-set' : '' }}">
                <option value="">كل حالات السداد</option>
                <option value="pending_approval" @selected(($filters['payment_status'] ?? '') === 'pending_approval')>قيد الموافقة</option>
                <option value="cancelled" @selected(($filters['payment_status'] ?? '') === 'cancelled')>ملغى</option>
                @foreach(['unpaid', 'partial', 'paid'] as $status)
                    <option value="{{ $status }}" @if(($filters['payment_status'] ?? '') === $status) selected @endif>{{ __('hourly.'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_source"><i class="fa fa-code-branch"></i> مصدر الطلب</label>
            <select id="f_source" name="source" class="form-select {{ !empty($filters['source']) ? 'is-set' : '' }}">
                <option value="">كل المصادر</option>
                <option value="customer" @selected(($filters['source'] ?? '') === 'customer')>Web / Mobile Apps</option>
                <option value="admin" @selected(($filters['source'] ?? '') === 'admin')>موظف الفرع</option>
            </select>
        </div>
        <div class="hd-filter-field"><label for="f_city"><i class="fa fa-city"></i> المدينة</label>
            <select id="f_city" name="city_id" class="form-select {{ !empty($filters['city_id']) ? 'is-set' : '' }}">
                <option value="">كل المدن</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" @if((string) ($filters['city_id'] ?? '') === (string) $city->id) selected @endif>{{ $city->name_ar }}</option>
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
        <div><span class="small text-muted">النتائج: <strong class="ht-num">{{ number_format($orders->total()) }}</strong> طلب</span>
            <button class="ho-advanced-toggle me-2" type="button" data-bs-toggle="collapse" data-bs-target="#hourlyAdvancedFilters" aria-expanded="{{ $advancedFilters ? 'true' : 'false' }}"><i class="fa fa-sliders ms-1"></i> خيارات متقدمة @if($advancedFilters)<span class="badge bg-primary">{{ $advancedFilters }}</span>@endif</button>
        </div>
        <div class="d-flex gap-2">
            @if($activeFilters)<a class="btn btn-light rounded-3" href="{{ route('admin.hourly-orders.index') }}#all-orders"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>@endif
            <button class="btn btn-primary rounded-3"><i class="fa fa-filter ms-1"></i> تصفية</button>
        </div>
    </div>
</form>
@include('admin.hourly._order-export')

<div class="ht-card"><div class="ht-body">
    <div class="table-responsive">
        <table class="ht-table">
            <thead><tr>
                <th>الطلب</th>
                <th>العميل</th>
                <th>الخدمة</th>
                <th>المنتج / بدء الخدمة</th><th>رصيد الزيارات</th>
                <th>{{ __('hourly.status') }}</th>
                <th>السداد</th>
                <th>الصلاحية</th>
                <th></th>
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
                <tr>
                    <td>
                        <a class="ho-order-no" href="{{ route('admin.hourly-orders.show', $order) }}" title="رقم الطلب الكامل: {{ $order->order_number }}">#{{ $shortOrderNumber($order) }}</a>
                        <span class="badge bg-secondary">{{ $order->source === 'admin' ? 'موظف الفرع' : 'Web / Mobile Apps' }}</span>
                        <div class="ho-sub ho-tight"><i class="fa fa-clock"></i> <span class="ho-ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</span></div>
                    </td>
                    <td>
                        <strong>{{ optional($order->user)->name ?: 'عميل محذوف' }}</strong><div class="text-success">الرصيد: {{ number_format($order->user?->hourly_wallet_balance ?? 0,2) }} ر.س</div>
                        <div class="ho-sub ho-tight"><span class="ho-ltr">{{ optional($order->user)->phone }}</span></div>
                        <div class="ho-sub"><i class="fa fa-location-dot"></i> {{ $cityNames[$order->city_id] ?? '—' }}{{ $order->district ? ' · '.$order->district : '' }}</div>
                    </td>
                    <td>
                        <strong>{{ optional($order->serviceCategory)->name ?: '—' }}</strong>
                        <div class="ho-sub ho-tight ht-num">{{ $order->visits_total }} زيارة · {{ rtrim(rtrim(number_format($order->hours_per_visit, 2), '0'), '.') }} ساعة · {{ $order->workers_per_visit }} عاملة</div>
                        <div class="ho-sub ho-tight"><i class="fa {{ $order->source === 'admin' ? 'fa-user-tie' : 'fa-mobile-screen' }}"></i> {{ $order->source === 'admin' ? 'لوحة التحكم / موظف الفرع' : 'الموقع وتطبيقات الجوال' }}</div>
                    </td>
                    <td>
                        <div>{{ $order->package_snapshot['title'] ?? '—' }}</div>
                        <div class="ho-sub ho-ltr">{{ $order->starts_at?->format('Y-m-d H:i') ?: '—' }}</div>
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
                    <td><span class="ht-chip {{ $statusClass }}"><i class="fa {{ $statusIcon }}"></i> {{ __('hourly.'.$order->status) }}</span></td>
                    <td>
                        <div class="ho-pay"><strong>{{ $money($order->total_amount) }}</strong><small class="d-block">الضريبة: {{ $money($order->tax_amount) }}</small>
                            <span class="ht-chip {{ $payClass }}"><i class="fa {{ $payIcon }}"></i> {{ __('hourly.'.$order->payment_status) }}</span>
                            <div class="ht-progress mt-2">
                                <div class="ht-progress-bar {{ $paidPercent === 0 ? 'is-zero' : ($paidPercent < 100 ? 'is-low' : '') }}"><span style="width: {{ $paidPercent }}%"></span></div>
                                <small class="ht-num">{{ $paidPercent }}%</small>
                            </div>
                            <div class="ho-sub ho-tight ht-num">{{ $money($order->paid_amount) }} من {{ $money($order->total_amount) }}</div>
                            <div class="ho-sub ho-tight"><i class="fa fa-wallet"></i> {{ __('hourly.'.$order->payment_method) }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="ht-num ho-ltr">{{ optional($order->expires_at)->format('Y-m-d') ?: '—' }}</div>
                        @if($daysLeft !== null)
                            @if($daysLeft < 0)
                                <span class="ht-chip hd-chip-danger"><i class="fa fa-ban"></i> انتهت الصلاحية</span>
                            @elseif($daysLeft <= 7)
                                <span class="ht-chip hd-chip-warn ht-num"><i class="fa fa-triangle-exclamation"></i> {{ $daysLeft }} يوم متبقٍ</span>
                            @else
                                <span class="ht-chip ht-chip-off ht-num">{{ $daysLeft }} يوم متبقٍ</span>
                            @endif
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary ht-icon-btn" href="{{ route('admin.hourly-orders.show', $order) }}" title="تفاصيل الطلب"><i class="fa fa-eye"></i></a>
                        <a class="btn btn-sm btn-outline-secondary ht-icon-btn" href="{{ route('admin.hourly-orders.invoice', $order) }}" target="_blank" title="الفاتورة PDF"><i class="fa fa-file-pdf"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">
                    <div class="ht-empty">
                        <i class="fa fa-calendar-xmark"></i>
                        {{ $activeFilters ? 'لا توجد طلبات مطابقة للفلاتر.' : 'لا توجد طلبات بعد.' }}
                        @if($activeFilters)<div class="mt-2"><a class="btn btn-sm btn-light rounded-3" href="{{ route('admin.hourly-orders.index') }}#all-orders"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a></div>@endif
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($orders->hasPages())<div class="ht-foot">{{ $orders->fragment('all-orders')->links() }}</div>@endif
</div>
</div>
@endsection
