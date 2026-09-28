@extends('admin.layout.indexs.index')
@section('page-title','باقات الخدمات')
@section('style')
@include('admin.hourly_teams._style')
@include('admin.hourly._item-form-style')
<style>
    .si-tabs { display: flex; gap: .4rem; flex-wrap: wrap; padding: .35rem; background: #fff; border-radius: 14px; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); margin-bottom: 1rem; }
    .si-tabs a { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem 1rem; border-radius: 10px; color: var(--ht-muted); font-weight: 600; text-decoration: none; }
    .si-tabs a.active { background: var(--si-color, #6861ee); color: #fff; }
    .si-tabs a .badge { background: rgba(15, 23, 42, .08); color: inherit; }
    .si-tabs a.active .badge { background: rgba(255, 255, 255, .25); }
    .si-card { border: 1px solid var(--ht-line); border-radius: 16px; padding: 1rem; height: 100%; background: #fff; display: flex; flex-direction: column; gap: .5rem; }
    .si-card.is-off { background: #fcfcfd; }
    .si-card.is-off .si-title { color: var(--ht-muted); }
    .si-code { font-family: ui-monospace, Menlo, monospace; font-size: .75rem; background: var(--ht-soft); border-radius: 6px; padding: .1rem .45rem; color: var(--ht-muted); }
    .si-price { font-size: 1.35rem; font-weight: 800; color: var(--ht-ink); }
    .si-foot { margin-top: auto; display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding-top: .6rem; border-top: 1px dashed var(--ht-line); }
</style>
@endsection
@section('content')
@php
    $canEdit = AdminGuardHasPerm('hourly_service_items_edit');
    $activeFilters = count(array_filter(\Illuminate\Support\Arr::only($filters, ['duration', 'visits'])));
    $durationLabel = fn ($m) => intdiv($m, 60).' ساعة'.($m % 60 ? ' و '.($m % 60).' دقيقة' : '');
@endphp
<div class="container-fluid ht-page" dir="rtl">
@include('admin.hourly.messages')
<div class="ht-card"><div class="ht-head">
    <div class="ht-head-icon"><i class="fa fa-box-open"></i></div>
    <div><h5>باقات الخدمات</h5><p>الباقات مجمعة حسب الفئة؛ الأزرار الجماعية تُطبَّق على الفئة المختارة فقط.</p></div>
    <div class="ht-actions">
        @if($category && AdminGuardHasPerm('hourly_packages_create'))<button class="btn btn-primary rounded-3" data-item-form="{{ route('admin.hourly-packages.create',['service_category_id'=>$category->id]) }}"><i class="fa fa-plus ms-1"></i> إضافة باقة جديدة</button>@endif
        @if($category && $canEdit)<button class="btn btn-info rounded-3" data-bs-toggle="modal" data-bs-target="#price-modal"><i class="fa fa-tags ms-1"></i> تحديث الأسعار جماعيًا</button><button class="btn btn-warning rounded-3" data-bs-toggle="modal" data-bs-target="#shift-modal"><i class="fa fa-clock ms-1"></i> تحديث الشفتات جماعيًا</button>@endif
        <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-service-items.logs') }}"><i class="fa fa-clock-rotate-left ms-1"></i> سجل التعديلات</a>
    </div>
</div></div>

@if($categories->isEmpty())
    <div class="ht-note ht-note-info"><i class="fa fa-circle-info mt-1"></i><div>لا توجد فئات خدمة بعد. أنشئ فئة من <a href="{{ route('admin.hourly-service-categories.index') }}">فئات الخدمة</a> ثم أضف باقاتها.</div></div>
@else
<nav class="si-tabs" aria-label="فئات الخدمة">
    @foreach($categories as $tab)
        <a href="{{ route('admin.hourly-service-items.index',['category'=>$tab->id]) }}" class="{{ $category && $category->id===$tab->id ? 'active' : '' }}" style="--si-color: {{ $tab->color ?: '#6861ee' }}">
            <i class="fa {{ $tab->icon_type==='library' && $tab->icon ? $tab->icon : 'fa-layer-group' }}"></i> {{ $tab->name_ar }}
            <span class="badge rounded-pill">{{ $tab->packages_count }}</span>
            @include('admin.hourly._publication-badge',['report'=>$categoryPublication[$tab->id],'badgeId'=>'website-category-'.$tab->id])
            @unless($tab->is_active)<i class="fa fa-circle-pause small" title="الفئة متوقفة"></i>@endunless
        </a>
    @endforeach
    @if(AdminGuardHasPerm('hourly_service_categories_view') && $category)
        <a href="{{ route('admin.hourly-service-categories.show',$category) }}#attributes" class="ms-auto" data-manage-attributes><i class="fa fa-tags"></i> {{ __('hourly.manage_attributes') }}</a>
        <a href="{{ route('admin.hourly-service-categories.show',$category) }}#settings"><i class="fa fa-sliders"></i> {{ __('hourly.tab_settings') }}</a>
    @endif
</nav>

<form method="GET" class="hd-filter mb-3">
    <input type="hidden" name="category" value="{{ $category?->id }}">
    <div class="hd-filter-head">
        <div class="hd-filter-title"><i class="fa fa-sliders"></i> فلترة الباقات @if($activeFilters)<span class="hd-filter-count">{{ $activeFilters }} فلتر مفعّل</span>@endif</div>
    </div>
    <div class="hd-filter-body">
        <div class="hd-filter-field"><label for="f_duration"><i class="fa fa-hourglass-half"></i> مدة الخدمة</label>
            <select id="f_duration" name="duration" class="form-select {{ !empty($filters['duration']) ? 'is-set' : '' }}" onchange="this.form.submit()"><option value="">الكل</option>@foreach($durations as $minutes)<option value="{{ $minutes }}" {{ (int)($filters['duration'] ?? 0)===$minutes ? 'selected' : '' }}>{{ $durationLabel($minutes) }}</option>@endforeach</select></div>
        <div class="hd-filter-field"><label for="f_visits"><i class="fa fa-repeat"></i> عدد الزيارات</label>
            <select id="f_visits" name="visits" class="form-select {{ !empty($filters['visits']) ? 'is-set' : '' }}" onchange="this.form.submit()"><option value="">الكل</option>@foreach($visitCounts as $count)<option value="{{ $count }}" {{ (int)($filters['visits'] ?? 0)===(int)$count ? 'selected' : '' }}>{{ $count==1 ? 'زيارة واحدة' : $count.' زيارات' }}</option>@endforeach</select></div>
    </div>
    <div class="hd-filter-foot"><span class="small text-muted">النتائج: <strong class="ht-num">{{ $items->count() }}</strong> باقة</span>@if($activeFilters)<a class="btn btn-light rounded-3" href="{{ route('admin.hourly-service-items.index',['category'=>$category?->id]) }}"><i class="fa fa-rotate-left ms-1"></i> مسح الفلاتر</a>@endif</div>
</form>
@endif

<div id="item-feedback" class="alert d-none" role="status"></div>
@forelse($items->groupBy(fn($p)=>$p->duration_minutes ?? round($p->hours_per_visit*60)) as $duration=>$group)
<div class="ht-card has-publication"><div class="ht-head"><div class="ht-head-icon"><i class="fa fa-hourglass-half"></i></div><div><h5>{{ $category->name_ar }} — {{ $durationLabel((int)$duration) }}</h5><p>{{ $group->count() }} باقة</p></div></div><div class="ht-body"><div class="row g-3">
@foreach($group as $item)
@php $total = $item->total_price ?? $item->price_per_visit*$item->visits_count; @endphp
<div class="col-md-6 col-xl-4"><div class="si-card {{ $item->is_active ? '' : 'is-off' }}">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div><h6 class="si-title mb-1">{{ $item->title }}</h6><span class="si-code">{{ $item->code }}</span></div>
        <span class="ht-chip {{ $item->visits_count==1 ? 'ht-chip-off' : 'hd-chip-primary' }}">{{ $item->visits_count==1 ? __('hourly.tier_once') : 'باقة '.$item->visits_count.' زيارات' }}</span>
    </div>
    <div class="small text-muted">النمط: {{ $item->package_tier_id ? $item->packageTier?->name : __('hourly.tier_once') }}@if($item->workers_per_visit>1) · {{ $item->workers_per_visit }} عاملات@endif</div>
    <div>@include('admin.hourly._publication-badge',['report'=>$publication[$item->id],'badgeId'=>'website-package-'.$item->id])</div>
    @if($item->attribute_id && $item->productAttribute)
        <div class="small text-muted"><i class="fa fa-tags"></i> {{ $item->productAttribute->parent?->name }}: {{ $item->productAttribute->name }}</div>
    @endif
    <div><span class="si-price ht-num">{{ number_format($total,2) }}</span> <small class="text-muted">ر.س للباقة</small><div class="small text-muted">{{ number_format($item->price_per_visit,2) }} ر.س للزيارة شامل الضريبة</div></div>
    @if($canEdit)<form class="item-quick-price d-flex gap-2" action="{{ route('admin.hourly-service-items.price',$item) }}"><input aria-label="إجمالي سعر الباقة" class="form-control form-control-sm" type="number" name="total_price" min="0.01" max="100000" step="0.01" value="{{ $total }}" required><button class="btn btn-sm btn-success text-nowrap">حفظ السعر</button></form>@endif
    <div class="small text-muted"><i class="fa fa-clock ms-1"></i>{{ $item->start_times===null ? 'مواعيد التشغيل العامة' : count($item->start_times).' موعد بدء' }}</div>
    <div class="si-foot">
        <label class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" data-item-status="{{ route('admin.hourly-service-items.status',$item) }}" {{ $item->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }} aria-label="حالة {{ $item->title }}"><span>{{ $item->is_active ? 'نشط' : 'متوقف' }}</span></label>
        @if($canEdit)<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.hourly-service-items.settings.edit',$item) }}">تخصيص إعدادات الفئة</a><button class="btn btn-sm btn-outline-primary rounded-3" data-item-form="{{ route('admin.hourly-packages.edit',$item) }}"><i class="fa fa-gear ms-1"></i> الإعدادات</button>@endif
    </div>
</div></div>
@endforeach
</div></div></div>
@empty
@if($category)<div class="ht-empty"><i class="fa fa-box-open"></i> {{ $activeFilters ? 'لا توجد باقات مطابقة للفلاتر.' : 'لا توجد باقات في هذه الفئة بعد.' }}</div>@endif
@endforelse
</div>
<div class="modal fade ht-page" id="item-modal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5>بيانات الباقة</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="item-modal-body"></div></div></div></div>
@if($category)@include('admin.hourly.service-item-bulk')@endif
@endsection
@push('js')
@include('admin.hourly.service-item-script')
@endpush
