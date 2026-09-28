{{--
    منتقي أيقونات مرسوم لموديول الساعات. يمكن تكراره في الصفحة نفسها (يعتمد data-* لا المعرّفات).
    المعاملات: $name (اسم الحقل، افتراضي icon)، $value (مثل fa-broom)، $id (معرّف الحقل النصي)، $default.
    الحقل النصي هو مصدر القيمة: يمكن ملؤه برمجيًا (مثل مودال التعديل) ثم تُحدَّث الشبكة عند فتح المودال أو إطلاق input.
--}}
@php
    $name ??= 'icon';
    $id ??= 'icon';
    $default ??= 'fa-broom';
    $value = (string) ($value ?? '') ?: $default;
    $groups = [
        'cleaning' => ['broom', 'soap', 'spray-can-sparkles', 'pump-soap', 'bucket', 'jug-detergent', 'shirt', 'bed', 'bath', 'sink', 'kitchen-set', 'utensils', 'wand-magic-sparkles'],
        'home' => ['house', 'house-chimney', 'building', 'city', 'hotel', 'warehouse', 'couch', 'door-open', 'key', 'leaf', 'seedling'],
        'care' => ['baby', 'child-reaching', 'person-cane', 'user-nurse', 'hand-holding-heart', 'dog', 'user', 'users', 'people-group', 'handshake'],
        'time' => ['clock', 'calendar', 'calendar-check', 'calendar-week', 'hourglass-half', 'stopwatch', 'business-time'],
        'transport' => ['car', 'van-shuttle', 'truck', 'route', 'location-dot', 'map'],
        'general' => ['star', 'heart', 'gem', 'crown', 'gift', 'tag', 'bell', 'circle-check', 'shield-heart', 'box', 'layer-group', 'briefcase', 'phone', 'headset'],
    ];
    $label = fn ($icon) => __('hourly_icons.names.'.$icon) !== 'hourly_icons.names.'.$icon ? __('hourly_icons.names.'.$icon) : $icon;
@endphp
@once
<style>
    .hip { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; overflow: hidden; }
    .hip-head { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; padding: .65rem .8rem; border-bottom: 1px solid #eef2f7; background: #f8fafc; }
    .hip-current { display: flex; align-items: center; gap: .6rem; min-width: 150px; }
    .hip-current .hip-preview { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; background: #6861ee; color: #fff; font-size: 1.05rem; flex: 0 0 40px; }
    .hip-current small { display: block; font-size: .72rem; color: #64748b; }
    .hip-current strong { font-size: .9rem; color: #0f172a; }
    .hip-search { position: relative; flex: 1 1 200px; }
    .hip-search i { position: absolute; top: 50%; inset-inline-start: .7rem; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .hip-search input { height: 36px; padding-inline-start: 2rem; border-radius: 9px; font-size: .85rem; }
    .hip-body { max-height: 250px; overflow-y: auto; padding: .4rem .8rem .8rem; }
    .hip-group-title { font-size: .72rem; font-weight: 700; color: #64748b; margin: .6rem 0 .4rem; }
    .hip-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(64px, 1fr)); gap: .4rem; }
    .hip-item { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .5rem .2rem; border: 1px solid #eef2f7; border-radius: 10px; background: #fff; color: #334155; cursor: pointer; transition: all .12s; min-width: 0; }
    .hip-item i { font-size: 1.1rem; }
    .hip-item span { font-size: .66rem; color: #64748b; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hip-item:hover { border-color: #c7d2fe; background: #f5f7ff; }
    .hip-item.active { border-color: #6861ee; background: #eef2ff; color: #4338ca; box-shadow: 0 0 0 2px rgba(104, 97, 238, .15); }
    .hip-item.active span { color: #4338ca; font-weight: 700; }
    .hip-empty { padding: 1rem; text-align: center; font-size: .82rem; color: #94a3b8; }
    .hip-custom { border-top: 1px solid #eef2f7; padding: .5rem .8rem; }
    .hip-custom summary { font-size: .78rem; color: #6861ee; cursor: pointer; }
    .hip-custom .form-control { height: 36px; margin-top: .4rem; font-size: .85rem; }
</style>
@endonce
<div class="hip" data-hourly-icon-picker data-default="{{ $default }}">
    <div class="hip-head">
        <div class="hip-current">
            <span class="hip-preview" data-hip-preview-box><i class="fa {{ $value }}" data-icon-preview></i></span>
            <div><small>{{ __('hourly_icons.selected') }}</small><strong data-hip-name>{{ $label(preg_replace('/^fa-/', '', $value)) }}</strong></div>
        </div>
        <div class="hip-search">
            <i class="fa fa-magnifying-glass"></i>
            <input type="search" class="form-control" data-hip-search autocomplete="off" placeholder="{{ __('hourly_icons.search') }}" aria-label="{{ __('hourly_icons.search') }}">
        </div>
    </div>
    <div class="hip-body" role="listbox" aria-label="{{ __('hourly_icons.title') }}">
        @foreach ($groups as $group => $icons)
            <div data-hip-group>
                <div class="hip-group-title">{{ __('hourly_icons.groups.'.$group) }}</div>
                <div class="hip-grid">
                    @foreach ($icons as $icon)
                        <button type="button" class="hip-item" role="option" data-hip-icon="fa-{{ $icon }}" data-hip-label="{{ $label($icon) }}"
                                data-search="{{ mb_strtolower($icon.' '.__('hourly_icons.names.'.$icon, [], 'ar').' '.__('hourly_icons.names.'.$icon, [], 'en')) }}" title="{{ $label($icon) }}">
                            <i class="fa fa-{{ $icon }}"></i><span>{{ $label($icon) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
        <div class="hip-empty d-none" data-hip-empty>{{ __('hourly_icons.none') }}</div>
    </div>
    <details class="hip-custom">
        <summary>{{ __('hourly_icons.custom') }}</summary>
        <input class="form-control @error($name) is-invalid @enderror" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" maxlength="60" dir="ltr" placeholder="fa-broom" data-hip-input>
        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('hourly_icons.custom_hint') }}</div>
    </details>
</div>
@once
<script>
(() => {
    const valid = v => /^fa-[a-z0-9-]+$/.test(v);
    function sync(picker) {
        const input = picker.querySelector('[data-hip-input]');
        const value = valid(input.value) ? input.value : picker.dataset.default;
        let match = null;
        picker.querySelectorAll('[data-hip-icon]').forEach(item => {
            const on = item.dataset.hipIcon === value;
            item.classList.toggle('active', on);
            item.setAttribute('aria-selected', on);
            if (on) match = item;
        });
        picker.querySelector('[data-icon-preview]').className = 'fa ' + value;
        picker.querySelector('[data-hip-name]').textContent = match ? match.dataset.hipLabel : value;
        // أيقونة خارج القائمة: افتح حقل الأيقونة المخصصة ليُرى ما سيُحفظ.
        if (!match && input.value) picker.querySelector('.hip-custom').open = true;
    }
    function filter(picker, query) {
        const q = query.trim().toLowerCase();
        let any = false;
        picker.querySelectorAll('[data-hip-group]').forEach(group => {
            let shown = 0;
            group.querySelectorAll('[data-hip-icon]').forEach(item => {
                const on = !q || item.dataset.search.includes(q);
                item.classList.toggle('d-none', !on);
                shown += on;
            });
            group.classList.toggle('d-none', !shown);
            any ||= shown > 0;
        });
        picker.querySelector('[data-hip-empty]').classList.toggle('d-none', any);
    }
    document.addEventListener('click', e => {
        const item = e.target.closest('[data-hip-icon]');
        if (!item) return;
        const picker = item.closest('[data-hourly-icon-picker]'), input = picker.querySelector('[data-hip-input]');
        input.value = item.dataset.hipIcon;
        input.dispatchEvent(new Event('input', {bubbles: true}));
    });
    document.addEventListener('input', e => {
        const picker = e.target.closest('[data-hourly-icon-picker]');
        if (!picker) return;
        if (e.target.matches('[data-hip-search]')) filter(picker, e.target.value);
        if (e.target.matches('[data-hip-input]')) sync(picker);
    });
    // عند فتح مودال (مثل التعديل الذي يملأ الحقل برمجيًا) تُعاد مزامنة الشبكة وتفريغ البحث.
    document.addEventListener('shown.bs.modal', e => e.target.querySelectorAll('[data-hourly-icon-picker]').forEach(picker => {
        const search = picker.querySelector('[data-hip-search]'); search.value = ''; filter(picker, ''); sync(picker);
    }));
    const init = () => document.querySelectorAll('[data-hourly-icon-picker]').forEach(sync);
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
</script>
@endonce
