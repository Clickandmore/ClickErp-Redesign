@extends('admin.layout.indexs.index')
@section('page-title','باقات الخدمات')
@section('style')
@include('admin.hourly_teams._style')
@include('admin.hourly._item-form-style')
<style>
    .sp-page { --sp-accent: #0f6b6e; --sp-accent-soft: #e8f4f4; }
    .sp-page .ht-head-icon { background: var(--sp-accent-soft); color: var(--sp-accent); }
    .sp-page .ht-actions .btn { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .55rem 1rem; font-weight: 600; font-size: .88rem; }
    .sp-page .ht-actions .btn-primary { background: var(--sp-accent); border-color: var(--sp-accent); box-shadow: 0 6px 16px rgba(15, 23, 42, .12); }
    .sp-page .ht-actions .sp-btn-soft { background: #fff; border: 1px solid var(--ht-line); color: var(--ht-ink); }
    .sp-page .ht-actions .sp-btn-soft:hover { border-color: var(--sp-accent); color: var(--sp-accent); }

    .si-tabs { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; padding: .45rem; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); margin-bottom: 1rem; }
    .si-tabs a { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .85rem; border-radius: 12px; color: var(--ht-muted); font-weight: 600; font-size: .9rem; text-decoration: none; transition: background .15s, color .15s; }
    .si-tabs a:hover { background: var(--ht-soft); color: var(--ht-ink); }
    .si-tabs .si-tab-icon { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; font-size: .8rem; color: var(--si-color, #0f6b6e); background: color-mix(in srgb, var(--si-color, #0f6b6e) 12%, #fff); }
    .si-tabs a.active { background: var(--si-color, #0f6b6e); color: #fff; box-shadow: 0 8px 18px color-mix(in srgb, var(--si-color, #0f6b6e) 30%, transparent); }
    .si-tabs a.active .si-tab-icon { background: rgba(255, 255, 255, .2); color: #fff; }
    .si-tabs a .badge { background: rgba(15, 23, 42, .07); color: inherit; font-weight: 700; }
    .si-tabs a.active .badge { background: rgba(255, 255, 255, .25); }
    .si-tabs .si-tabs-links { margin-inline-start: auto; display: flex; gap: .25rem; padding-inline-start: .5rem; border-inline-start: 1px solid var(--ht-line); }
    .si-tabs .si-tabs-links a { font-size: .82rem; padding: .45rem .7rem; }

    .sp-page .hd-stats { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    .sp-page .hd-stat strong { font-size: 1.35rem; }
    .sp-page .hd-stat small { color: var(--ht-muted); font-size: .75rem; }

    .si-toolbar { display: flex; align-items: flex-end; gap: .75rem 1rem; flex-wrap: wrap; padding: .9rem 1.1rem; margin-bottom: 1.25rem; background: #fff; border: 1px solid var(--ht-line); border-radius: 16px; }
    .si-toolbar-title { display: flex; align-items: center; gap: .5rem; font-weight: 700; color: var(--ht-ink); align-self: center; margin-inline-end: .5rem; }
    .si-toolbar-title > i { color: var(--sp-accent); }
    .si-toolbar .hd-filter-field { flex: 0 1 220px; min-width: 170px; }
    .si-toolbar .hd-filter-field label { margin-bottom: .3rem; }
    .si-toolbar .hd-filter-field .form-select { height: 42px; border-radius: 10px; }
    .si-toolbar-end { margin-inline-start: auto; display: flex; align-items: center; gap: .6rem; align-self: center; }
    .si-toolbar-end .btn { border-radius: 10px; }

    .sp-group > .ht-head .ht-head-icon { background: color-mix(in srgb, var(--si-color) 12%, #fff); color: var(--si-color); }
    .sp-group .ht-body { background: #fbfcfe; }

    .si-card { position: relative; border: 1px solid var(--ht-line); border-radius: 16px; height: 100%; background: #fff; display: flex; flex-direction: column; overflow: hidden; transition: box-shadow .2s, transform .2s, border-color .2s; }
    .si-card::before { content: ''; height: 4px; background: var(--si-color); }
    .si-card:hover { box-shadow: 0 14px 34px rgba(15, 23, 42, .09); transform: translateY(-2px); border-color: color-mix(in srgb, var(--si-color) 35%, var(--ht-line)); }
    .si-card.is-off::before { background: #cbd5e1; }
    .si-card.is-off .si-title, .si-card.is-off .si-price { color: var(--ht-muted); }
    .si-card-body { padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .8rem; flex: 1; }
    .si-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .6rem; }
    .si-title { margin: 0 0 .3rem; font-weight: 700; color: var(--ht-ink); line-height: 1.5; }
    .si-code { font-family: ui-monospace, Menlo, monospace; font-size: .72rem; background: var(--ht-soft); border: 1px solid var(--ht-line); border-radius: 6px; padding: .05rem .45rem; color: var(--ht-muted); }
    .si-tags { display: flex; flex-wrap: wrap; gap: .4rem; }
    .si-tag { display: inline-flex; align-items: center; gap: .35rem; padding: 5px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--ht-soft); color: var(--ht-muted); border: 1px solid var(--ht-line); }
    .si-meta { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: .45rem; }
    .si-meta li { display: flex; align-items: center; gap: .5rem; padding: .45rem .55rem; border-radius: 10px; background: var(--ht-soft); min-width: 0; }
    .si-meta i { width: 26px; height: 26px; flex: 0 0 26px; border-radius: 8px; display: grid; place-items: center; font-size: .72rem; background: #fff; color: var(--si-color); box-shadow: 0 0 0 1px var(--ht-line); }
    .si-meta span { display: block; font-size: .68rem; color: var(--ht-muted); line-height: 1.3; }
    .si-meta strong { display: block; font-size: .8rem; color: var(--ht-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .si-price-box { margin-top: auto; padding: .8rem .9rem; border-radius: 12px; border: 1px dashed var(--ht-line); display: flex; flex-direction: column; gap: .6rem; }
    .si-price { font-size: 1.5rem; font-weight: 800; color: var(--ht-ink); line-height: 1.1; }
    .si-price-unit { font-size: .78rem; color: var(--ht-muted); font-weight: 600; }
    .si-price-sub { font-size: .75rem; color: var(--ht-muted); margin-top: .2rem; }
    .item-quick-price .form-control { border-radius: 0 10px 10px 0; border-color: var(--ht-line); font-weight: 600; }
    .item-quick-price .input-group-text { background: var(--ht-soft); border-color: var(--ht-line); font-size: .75rem; color: var(--ht-muted); }
    .item-quick-price .btn { border-radius: 10px 0 0 10px; font-weight: 600; }
    .si-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .7rem 1.1rem; border-top: 1px solid var(--ht-line); background: var(--ht-soft); }
    .si-status { display: inline-flex; align-items: center; gap: .5rem; margin: 0; padding: 0; font-size: .82rem; font-weight: 600; color: var(--ht-ink); }
    .si-status.form-switch .form-check-input { float: none; margin: 0; width: 2.4em; height: 1.3em; cursor: pointer; }
    .si-status .form-check-input:checked { background-color: #16a34a; border-color: #16a34a; }
    .si-foot-actions { display: flex; gap: .35rem; }
    .si-foot-actions .btn { border-radius: 9px; font-weight: 600; }
    .sp-page .min-w-0 { min-width: 0; }
    .si-icon-btn { width: 34px; padding-inline: 0; display: inline-grid; place-items: center; }

    .sp-page .ht-empty { text-align: center; padding: 3rem 1rem; background: #fff; border: 1px dashed var(--ht-line); border-radius: 16px; color: var(--ht-muted); }
    .sp-page .ht-empty > i { display: block; font-size: 2rem; margin-bottom: .6rem; color: #cbd5e1; }

    #price-modal .modal-content, #shift-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; }
    #price-modal .modal-header, #shift-modal .modal-header { direction: rtl; padding: 1.1rem 1.5rem; background: var(--ht-soft); }
    #price-modal .modal-header h5, #shift-modal .modal-header h5 { font-weight: 700; font-size: 1.05rem; margin: 0; }
    #price-modal .modal-header .btn-close, #shift-modal .modal-header .btn-close { margin: 0 auto 0 0; }
    #price-modal .modal-body, #shift-modal .modal-body { padding: 1.25rem 1.5rem; }
    #price-modal label, #shift-modal label { font-size: .85rem; font-weight: 600; color: var(--ht-ink); margin-bottom: .3rem; }
    #price-modal .form-control, #price-modal .form-select, #shift-modal .form-control, #shift-modal .form-select { border-radius: 10px; }

    @media (max-width: 768px) { .si-tabs .si-tabs-links { margin-inline-start: 0; border: 0; padding: 0; } .si-toolbar-end { margin-inline-start: 0; width: 100%; justify-content: space-between; } .si-toolbar .hd-filter-field { flex: 1 1 100%; } }
</style>
@endsection
@section('content')
@php
    $canEdit = AdminGuardHasPerm('hourly_service_items_edit');
    $activeFilters = count(array_filter(\Illuminate\Support\Arr::only($filters, ['duration', 'visits'])));
    $durationLabel = fn ($m) => intdiv($m, 60).' ساعة'.($m % 60 ? ' و '.($m % 60).' دقيقة' : '');
    $totalOf = fn ($item) => $item->total_price ?? $item->price_per_visit * $item->visits_count;
    $categoryColor = $category?->color ?: '#0f6b6e';
@endphp
<div class="container-fluid ht-page sp-page" dir="rtl">
@include('admin.hourly.messages')
<div class="ht-card"><div class="ht-head">
    <div class="ht-head-icon"><i class="fa fa-box-open"></i></div>
    <div><h5>باقات الخدمات</h5><p>الباقات مجمعة حسب الفئة؛ الأزرار الجماعية تُطبَّق على الفئة المختارة فقط.</p></div>
    <div class="ht-actions">
        @if($category && AdminGuardHasPerm('hourly_packages_create'))<button class="btn btn-primary" data-item-form="{{ route('admin.hourly-packages.create',['service_category_id'=>$category->id]) }}"><i class="fa fa-plus"></i> إضافة باقة جديدة</button>@endif
        @if($category && $canEdit)<button class="btn sp-btn-soft" data-bs-toggle="modal" data-bs-target="#price-modal"><i class="fa fa-tags text-info"></i> تحديث الأسعار جماعيًا</button><button class="btn sp-btn-soft" data-bs-toggle="modal" data-bs-target="#shift-modal"><i class="fa fa-clock text-warning"></i> تحديث الشفتات جماعيًا</button>@endif
        <a class="btn sp-btn-soft" href="{{ route('admin.hourly-service-items.logs') }}"><i class="fa fa-clock-rotate-left text-muted"></i> سجل التعديلات</a>
    </div>
</div></div>

@if($categories->isEmpty())
    <div class="ht-note ht-note-info"><i class="fa fa-circle-info mt-1"></i><div>لا توجد فئات خدمة بعد. أنشئ فئة من <a href="{{ route('admin.hourly-service-categories.index') }}">فئات الخدمة</a> ثم أضف باقاتها.</div></div>
@else
<nav class="si-tabs" aria-label="فئات الخدمة">
    @foreach($categories as $tab)
        <a href="{{ route('admin.hourly-service-items.index',['category'=>$tab->id]) }}" class="{{ $category && $category->id===$tab->id ? 'active' : '' }}" style="--si-color: {{ $tab->color ?: '#0f6b6e' }}">
            <span class="si-tab-icon"><i class="fa {{ $tab->icon_type==='library' && $tab->icon ? $tab->icon : 'fa-layer-group' }}"></i></span> {{ $tab->name_ar }}
            <span class="badge rounded-pill">{{ $tab->packages_count }}</span>
            @include('admin.hourly._publication-badge',['report'=>$categoryPublication[$tab->id],'badgeId'=>'website-category-'.$tab->id])
            @unless($tab->is_active)<i class="fa fa-circle-pause small" title="الفئة متوقفة"></i>@endunless
        </a>
    @endforeach
    @if(AdminGuardHasPerm('hourly_service_categories_view') && $category)
        <div class="si-tabs-links">
            <a href="{{ route('admin.hourly-service-categories.show',$category) }}#attributes" data-manage-attributes><i class="fa fa-tags"></i> {{ __('hourly.manage_attributes') }}</a>
            <a href="{{ route('admin.hourly-service-categories.show',$category) }}#settings"><i class="fa fa-sliders"></i> {{ __('hourly.tab_settings') }}</a>
        </div>
    @endif
</nav>

@if($category)
@php
    $prices = $items->map($totalOf);
    $visibleCount = $items->filter(fn ($item) => $publication[$item->id]['visible'] ?? false)->count();
@endphp
<div class="hd-stats">
    <div class="hd-stat"><div class="hd-stat-icon"><i class="fa fa-boxes-stacked"></i></div><div><span>الباقات المعروضة</span><strong class="ht-num">{{ $items->count() }}</strong></div></div>
    <div class="hd-stat hd-stat-on"><div class="hd-stat-icon"><i class="fa fa-circle-check"></i></div><div><span>الباقات النشطة</span><strong class="ht-num">{{ $items->where('is_active',true)->count() }}</strong></div></div>
    <div class="hd-stat hd-stat-info"><div class="hd-stat-icon"><i class="fa fa-eye"></i></div><div><span>تظهر في الموقع</span><strong class="ht-num">{{ $visibleCount }}</strong></div></div>
    <div class="hd-stat hd-stat-warn"><div class="hd-stat-icon"><i class="fa fa-coins"></i></div><div><span>نطاق الأسعار</span>@if($prices->isNotEmpty())<strong class="ht-num">{{ number_format($prices->min()) }} – {{ number_format($prices->max()) }}</strong> <small>ر.س</small>@else<strong>—</strong>@endif</div></div>
</div>

<form method="GET" class="si-toolbar">
    <input type="hidden" name="category" value="{{ $category->id }}">
    <div class="si-toolbar-title"><i class="fa fa-sliders"></i> فلترة الباقات @if($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif</div>
    <div class="hd-filter-field"><label for="f_duration"><i class="fa fa-hourglass-half"></i> مدة الخدمة</label>
        <select id="f_duration" name="duration" class="form-select {{ !empty($filters['duration']) ? 'is-set' : '' }}" onchange="this.form.submit()"><option value="">الكل</option>@foreach($durations as $minutes)<option value="{{ $minutes }}" {{ (int)($filters['duration'] ?? 0)===$minutes ? 'selected' : '' }}>{{ $durationLabel($minutes) }}</option>@endforeach</select></div>
    <div class="hd-filter-field"><label for="f_visits"><i class="fa fa-repeat"></i> عدد الزيارات</label>
        <select id="f_visits" name="visits" class="form-select {{ !empty($filters['visits']) ? 'is-set' : '' }}" onchange="this.form.submit()"><option value="">الكل</option>@foreach($visitCounts as $count)<option value="{{ $count }}" {{ (int)($filters['visits'] ?? 0)===(int)$count ? 'selected' : '' }}>{{ $count==1 ? 'زيارة واحدة' : $count.' زيارات' }}</option>@endforeach</select></div>
    <div class="si-toolbar-end"><span class="small text-muted">النتائج: <strong class="ht-num">{{ $items->count() }}</strong> باقة</span>@if($activeFilters)<a class="btn btn-light border btn-sm" href="{{ route('admin.hourly-service-items.index',['category'=>$category->id]) }}"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>@endif</div>
</form>
@endif
@endif

<div id="item-feedback" class="alert d-none" role="status"></div>
@forelse($items->groupBy(fn($p)=>$p->duration_minutes ?? round($p->hours_per_visit*60)) as $duration=>$group)
<div class="ht-card has-publication sp-group" style="--si-color: {{ $categoryColor }}"><div class="ht-head"><div class="ht-head-icon"><i class="fa fa-hourglass-half"></i></div><div><h5>{{ $category->name_ar }} — {{ $durationLabel((int)$duration) }}</h5><p>{{ $group->count() }} باقة · {{ $group->where('is_active',true)->count() }} نشطة</p></div></div><div class="ht-body"><div class="row g-3">
@foreach($group as $item)
@php
    $total = $totalOf($item);
    $paymentPolicyLabels = ['prepaid' => 'مقدمًا بالكامل', 'per_visit' => 'لكل زيارة', 'installments' => 'أقساط شهرية'];
    $paymentPolicy = $item->payment_policy;
    $effectivePriceDisplay = app(\App\Services\Hourly\HourlyBookingRules::class)->settings()->value($item, 'package_price_display');
    $paymentPolicyHint = $paymentPolicy
        ? ($paymentPolicyLabels[$paymentPolicy] ?? $paymentPolicy)
        : 'تلقائي — شروط العميل، ثم إعداد الفئة: '.($effectivePriceDisplay === 'per_visit' ? 'لكل زيارة' : 'إجمالي الباقة');
@endphp
<div class="col-md-6 col-xl-4"><article class="si-card {{ $item->is_active ? '' : 'is-off' }}">
    <div class="si-card-body">
        <div class="si-card-head">
            <div class="min-w-0"><h6 class="si-title">{{ $item->title }}</h6><span class="si-code">{{ $item->code }}</span></div>
            <span class="ht-chip {{ $item->visits_count==1 ? 'ht-chip-off' : 'hd-chip-primary' }}">{{ $item->visits_count==1 ? __('hourly.tier_once') : 'باقة '.$item->visits_count.' زيارات' }}</span>
        </div>
        <div class="si-tags">
            @include('admin.hourly._publication-badge',['report'=>$publication[$item->id],'badgeId'=>'website-package-'.$item->id])
            @if($item->attribute_id && $item->productAttribute)<span class="si-tag"><i class="fa fa-tags"></i> {{ $item->productAttribute->parent?->name }}: {{ $item->productAttribute->name }}</span>@endif
        </div>
        <ul class="si-meta">
            <li><i class="fa fa-repeat"></i><div class="min-w-0"><span>النمط</span><strong title="{{ $item->package_tier_id ? $item->packageTier?->name : __('hourly.tier_once') }}">{{ $item->package_tier_id ? $item->packageTier?->name : __('hourly.tier_once') }}</strong></div></li>
            <li><i class="fa fa-calendar-check"></i><div class="min-w-0"><span>الزيارات</span><strong class="ht-num">{{ $item->visits_count==1 ? 'زيارة واحدة' : $item->visits_count.' زيارات' }}</strong></div></li>
            <li><i class="fa fa-wallet"></i><div class="min-w-0"><span>سياسة الدفع</span><strong title="{{ $paymentPolicyHint }}">{{ $paymentPolicyHint }}</strong></div></li>
            <li><i class="fa fa-users"></i><div class="min-w-0"><span>العاملات / زيارة</span><strong class="ht-num">{{ $item->workers_per_visit ?: 1 }}</strong></div></li>
            <li><i class="fa fa-clock"></i><div class="min-w-0"><span>مواعيد البدء</span><strong>{{ $item->start_times===null ? 'المواعيد العامة' : count($item->start_times).' موعد' }}</strong></div></li>
        </ul>
        <div class="si-price-box">
            <div><span class="si-price ht-num">{{ number_format($total,2) }}</span> <span class="si-price-unit">ر.س للباقة</span><div class="si-price-sub">{{ number_format($item->price_per_visit,2) }} ر.س للزيارة شامل الضريبة</div></div>
            @if($canEdit)<form class="item-quick-price input-group input-group-sm" action="{{ route('admin.hourly-service-items.price',$item) }}"><input aria-label="إجمالي سعر الباقة" class="form-control" type="number" name="total_price" min="0.01" max="100000" step="0.01" value="{{ $total }}" required><span class="input-group-text">ر.س</span><button class="btn btn-success text-nowrap"><i class="fa fa-check ms-1"></i> حفظ السعر</button></form>@endif
        </div>
    </div>
    <div class="si-foot">
        <label class="form-check form-switch si-status"><input class="form-check-input" type="checkbox" data-item-status="{{ route('admin.hourly-service-items.status',$item) }}" {{ $item->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }} aria-label="حالة {{ $item->title }}"><span>{{ $item->is_active ? 'نشط' : 'متوقف' }}</span></label>
        @if($canEdit)<div class="si-foot-actions"><a class="btn btn-sm btn-light border si-icon-btn" href="{{ route('admin.hourly-service-items.settings.edit',$item) }}" title="تخصيص إعدادات الفئة" aria-label="تخصيص إعدادات الفئة"><i class="fa fa-sliders"></i></a><button class="btn btn-sm btn-outline-primary" data-item-form="{{ route('admin.hourly-packages.edit',$item) }}"><i class="fa fa-gear ms-1"></i> الإعدادات</button></div>@endif
    </div>
</article></div>
@endforeach
</div></div></div>
@empty
@if($category)<div class="ht-empty"><i class="fa fa-box-open"></i> {{ $activeFilters ? 'لا توجد باقات مطابقة للفلاتر.' : 'لا توجد باقات في هذه الفئة بعد.' }}</div>@endif
@endforelse
</div>
<div class="modal fade ht-page" id="item-modal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><span class="si-modal-icon"><i class="fa fa-box-open"></i></span><div><h5>بيانات الباقة</h5><p class="si-modal-sub">البيانات الأساسية والتسعير والمواعيد وخيارات الظهور في مكان واحد.</p></div><button class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body" id="item-modal-body"></div></div></div></div>
@if($category)@include('admin.hourly.service-item-bulk')@endif
@endsection
@push('js')
@include('admin.hourly.service-item-script')
@endpush
