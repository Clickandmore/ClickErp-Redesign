@extends('admin.layout.indexs.index')
@section('page-title',$heading.(isset($package) ? ' — '.$package->title : ''))
@section('style')
@include('admin.hourly_teams._style')
<style>
    .ss-page { --ss-accent: #0f6b6e; --ss-accent-soft: #e8f4f4; }
    .ss-topbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .ss-crumbs { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; margin: 0; padding: 0; list-style: none; font-size: .84rem; color: var(--ht-muted); }
    .ss-crumbs li { display: inline-flex; align-items: center; gap: .4rem; }
    .ss-crumbs li + li::before { content: '\f104'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: .7rem; color: #cbd5e1; }
    .ss-crumbs a { color: var(--ht-muted); text-decoration: none; font-weight: 600; }
    .ss-crumbs a:hover { color: var(--ss-accent); }
    .ss-crumbs [aria-current] { color: var(--ht-ink); font-weight: 700; }
    .ss-back { display: inline-flex; align-items: center; gap: .45rem; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; background: #fff; border: 1px solid var(--ht-line); color: var(--ht-ink); }
    .ss-back:hover { border-color: var(--ss-accent); color: var(--ss-accent); }

    .ss-package { position: relative; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1rem 1.25rem; margin-bottom: 1rem; background: #fff; border: 1px solid var(--ht-line); border-radius: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); overflow: hidden; }
    .ss-package::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--si-color); }
    .ss-package-icon { width: 48px; height: 48px; flex: 0 0 48px; border-radius: 14px; display: grid; place-items: center; font-size: 1.1rem; color: var(--si-color); background: color-mix(in srgb, var(--si-color) 12%, #fff); }
    .ss-package-copy { min-width: 0; flex: 1 1 260px; }
    .ss-package-copy h5 { margin: 0 0 .35rem; font-weight: 800; color: var(--ht-ink); font-size: 1.05rem; }
    .ss-package-meta { display: flex; flex-wrap: wrap; gap: .4rem; }
    .ss-meta { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 600; color: var(--ht-muted); background: var(--ht-soft); border: 1px solid var(--ht-line); }
    .ss-meta i { color: var(--si-color); font-size: .7rem; }
    .ss-meta.ss-code { font-family: ui-monospace, Menlo, monospace; }
    .ss-package-side { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin-inline-start: auto; }
    .ss-price { text-align: end; line-height: 1.2; }
    .ss-price strong { display: block; font-size: 1.3rem; font-weight: 800; color: var(--ht-ink); }
    .ss-price small { color: var(--ht-muted); font-size: .72rem; }
    .ss-package-side .btn { border-radius: 10px; font-weight: 600; font-size: .82rem; }
    .ss-package-side .btn:hover { border-color: var(--ss-accent); color: var(--ss-accent); background: #fff; }
    @media (max-width: 767px) { .ss-package-side { margin-inline-start: 0; width: 100%; justify-content: space-between; } .ss-price { text-align: start; } }
</style>
@endsection
@section('content')
<div class="container-fluid ht-page ss-page" dir="rtl">
<div class="ss-topbar">
    <ol class="ss-crumbs">
        <li><a href="{{ route('admin.hourly-service-items.index') }}"><i class="fa fa-box-open"></i> {{ __('hourly.item_settings.packages') }}</a></li>
        @isset($package)
            @if($package->serviceCategory)<li><a href="{{ route('admin.hourly-service-items.index',['category'=>$package->service_category_id]) }}">{{ $package->serviceCategory->name_ar }}</a></li>@endif
            <li><span>{{ $package->title }}</span></li>
        @endisset
        <li><span aria-current="page">{{ $heading }}</span></li>
    </ol>
    <a class="btn ss-back" href="{{ route('admin.hourly-service-items.index',isset($package) ? ['category'=>$package->service_category_id] : []) }}"><i class="fa fa-arrow-right"></i> {{ __('hourly.item_settings.back') }}</a>
</div>
@include('admin.hourly.messages')
@isset($package)
@php
    $packageCategory = $package->serviceCategory;
    $duration = (int) ($package->duration_minutes ?? round($package->hours_per_visit * 60));
@endphp
<div class="ss-package" style="--si-color: {{ $packageCategory?->color ?: '#0f6b6e' }}">
    <div class="ss-package-icon"><i class="fa {{ $packageCategory && $packageCategory->icon_type==='library' && $packageCategory->icon ? $packageCategory->icon : 'fa-box-open' }}"></i></div>
    <div class="ss-package-copy">
        <h5>{{ $package->title }}</h5>
        <div class="ss-package-meta">
            <span class="ss-meta ss-code">{{ $package->code }}</span>
            @if($packageCategory)<span class="ss-meta"><i class="fa fa-layer-group"></i> {{ $packageCategory->name_ar }}</span>@endif
            <span class="ss-meta"><i class="fa fa-hourglass-half"></i> {{ intdiv($duration,60) }}:{{ sprintf('%02d',$duration%60) }}</span>
            <span class="ss-meta"><i class="fa fa-calendar-check"></i> {{ $package->visits_count==1 ? __('hourly.tier_once') : __('hourly.item_settings.visits',['count'=>$package->visits_count]) }}</span>
            <span class="ht-chip {{ $package->is_active ? 'ht-chip-on' : 'ht-chip-off' }}">{{ __('hourly.item_settings.'.($package->is_active ? 'active' : 'inactive')) }}</span>
        </div>
    </div>
    <div class="ss-package-side">
        <div class="ss-price"><strong class="ht-num">{{ number_format($package->total_price ?? $package->price_per_visit*$package->visits_count,2) }}</strong><small>{{ __('hourly.item_settings.per_package') }}</small></div>
        @if($packageCategory && AdminGuardHasPerm('hourly_service_categories_view'))<a class="btn btn-light border" href="{{ route('admin.hourly-service-categories.show',$packageCategory) }}#settings"><i class="fa fa-sliders ms-1"></i> {{ __('hourly.item_settings.category_settings') }}</a>@endif
    </div>
</div>
@endisset
@include('admin.hourly_service_categories._tab-settings')
</div>
@endsection
@push('js')
@stack('category-js')
@endpush
