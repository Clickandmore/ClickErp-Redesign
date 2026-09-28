@extends('admin.layout.indexs.index')

@section('page-title', __('hourly.category_manage').' — '.$category->name)

@section('style')
    @include('admin.hourly_teams._style')
    <style>
        .sc-navigation { position: relative; z-index: 21; background: #fff; margin-bottom: 1.25rem; border-radius: 14px; }
        .sc-navigation .sc-header { position: static; margin-bottom: 0; }
        #category-tabs { display: flex; flex-wrap: wrap; gap: .25rem; position: relative; padding: .75rem 1rem 0; margin: 0; list-style: none; border-bottom: 1px solid var(--ht-line); background: #fff; }
        #category-tabs .nav-link { display: inline-flex; align-items: center; gap: .45rem; padding: .85rem 1rem; background: transparent; color: var(--ht-muted); border: 0; border-bottom: 3px solid transparent; border-radius: 0; font-weight: 600; }
        #category-tabs .nav-link.active { color: #6861ee; border-bottom-color: #6861ee; }
        #category-tabs .nav-link:focus-visible { outline: 2px solid #6861ee; outline-offset: -2px; }
        @media (max-width: 767px) { #category-tabs { flex-wrap: nowrap; overflow-x: auto; } #category-tabs .nav-link { white-space: nowrap; } }
        .sc-header .ht-head { background: #fff; }
        .sc-icon { width: 44px; height: 44px; border-radius: 13px; display: grid; place-items: center; color: #fff; font-size: 1.1rem; flex: 0 0 44px; overflow: hidden; }
        .sc-icon img { width: 100%; height: 100%; object-fit: cover; }
        .sc-metric { display: flex; flex-direction: column; align-items: flex-start; padding: .2rem .9rem; border-inline-start: 1px solid var(--ht-line); }
        .sc-metric span { font-size: .75rem; color: var(--ht-muted); }
        .sc-metric strong { font-size: 1.15rem; color: var(--ht-ink); }
        .sc-toast { position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%); z-index: 1080; min-width: 260px; text-align: center; box-shadow: 0 12px 30px rgba(15, 23, 42, .18); }
    </style>
    {{-- تنسيقات التبويبات تُكتب داخل ملف كل تبويب: قسم style يُنفَّذ قبل المحتوى فلا يلتقط push منها. --}}
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_service_categories_edit');
        $tabs = [
            'districts' => [__('hourly_category_ui.manage_services'), 'fa-map-location-dot'],
            'operation' => [__('hourly_category_ui.service_operation'), 'fa-power-off'],
            'settings' => [__('hourly.tab_settings'), 'fa-sliders'],
            'attributes'=>[__('hourly.product_attributes'),'fa-tags'],
            'payments' => [__('hourly.tab_payments'), 'fa-credit-card'],
            'notes' => [__('hourly.tab_notes'), 'fa-note-sticky'],
        ];
    @endphp
    <div class="container-fluid ht-page" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ $errors->first() }}</div>
        @endif

        <div class="sc-navigation">
        <div class="ht-card sc-header">
            <div class="ht-head">
                <span class="sc-icon" style="background: {{ $category->color ?: '#6861ee' }}">
                    @if ($category->icon_url)<img src="{{ $category->icon_url }}" alt="">@else<i class="fa {{ $category->icon ?: 'fa-layer-group' }}"></i>@endif
                </span>
                <div>
                    <h5>{{ $category->name }} @unless ($category->is_active)<span class="ht-chip ht-chip-off">{{ __('hourly.category_disabled') }}</span>@endunless</h5>
                    
                </div>
                <div class="ht-actions align-items-center">
                    <div class="sc-metric"><span>{{ __('hourly.total_orders') }}</span><strong class="ht-num" data-metric="orders">{{ number_format($stats['orders']) }}</strong></div>
                    <div class="sc-metric"><span>{{ __('hourly.total_amount') }}</span><strong class="ht-num" data-metric="amount">{{ number_format($stats['amount'], 2) }} <small class="fs-6 text-muted">{{ __('hourly.sar') }}</small></strong></div>
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-service-categories.index') }}"><i class="fa fa-arrow-right ms-1"></i> {{ __('hourly.back') }}</a>
                </div>
            </div>
        </div>

        <div class="nav" id="category-tabs" role="tablist" aria-label="{{ __('hourly.category_manage') }}">
            @foreach ($tabs as $key => [$label, $icon])
                    <button id="category-tab-{{ $key }}" type="button" class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}" role="tab" aria-controls="tab-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"><i class="fa {{ $icon }}"></i> {{ $label }}</button>
            @endforeach
        </div>
        </div>

        <div class="tab-content">
            @foreach ($tabs as $key => $tab)
                <div class="tab-pane {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $key }}" role="tabpanel" aria-labelledby="category-tab-{{ $key }}">
                    @includeIf('admin.hourly_service_categories._tab-'.$key)
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
<script>
// أدوات مشتركة لتبويبات الفئة: طلب JSON ورسالة عائمة، وفتح التبويب من الرابط (#districts).
window.categoryUi = {
    csrf: @json(csrf_token()),
    async request(url, method, body) {
        const response = await fetch(url, {method, credentials: 'same-origin', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf}, body: body === undefined ? undefined : JSON.stringify(body)});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || @json(__('hourly_category_ui.request_failed'))));
        return data;
    },
    toast(text, ok = true) {
        let el = document.getElementById('sc-toast');
        if (!el) { el = document.createElement('div'); el.id = 'sc-toast'; el.setAttribute('role', 'status'); document.body.append(el); }
        el.className = 'alert border-0 rounded-3 sc-toast ' + (ok ? 'alert-success' : 'alert-danger');
        el.textContent = text;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => el.remove(), 2800);
    },
};
document.addEventListener('DOMContentLoaded', () => {
    const open = () => {
        const tab = [...document.querySelectorAll('#category-tabs [role="tab"]')].find(button => button.dataset.bsTarget === '#tab-' + location.hash.slice(1));
        if (tab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(tab).show();
    };
    document.querySelectorAll('[data-bs-target^="#tab-"]').forEach(button => button.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + button.dataset.bsTarget.replace('#tab-', ''))));
    open();
    window.addEventListener('hashchange', open);
});
</script>
@stack('category-js')
@endpush
