{{-- تبويب الإعدادات. يتطلب: $categorySettings (المخصص فقط)، $generalSettings، $canEdit --}}
@php
    $f = fn ($key) => __('hourly.settings_fields.'.$key);
    $s = fn ($key) => __('hourly.settings_sections.'.$key);
    $autoMode = !array_key_exists('auto_complete_visits_at', $categorySettings) ? 'inherit' : ($categorySettings['auto_complete_visits_at'] === 'disabled' ? 'disabled' : 'time');
    $generalAuto = $generalSettings['auto_complete_visits_at'] ?: __('hourly.disabled');
    $steppersMode = array_key_exists('order_form_steppers', $categorySettings) ? 'custom' : 'inherit';
    $steppers = $categorySettings['order_form_steppers'] ?? $generalSettings['order_form_steppers'];
    $customSettingsCount = collect($categorySettings)->except(['working_days', 'order_form_steppers'])->count()
        + collect($categorySettings['working_days'] ?? [])->filter(fn ($day) => !empty($day))->count()
        + (array_key_exists('order_form_steppers', $categorySettings) ? 1 : 0);
    $sections = [
        'tax' => ['fa-percent', ['tax_rate', 'show_custom_fee_separately', 'show_tax_separately']],
        'reschedule' => ['fa-calendar-days', ['allow_reschedule', 'reschedule_hours_before', 'max_reschedules_per_visit']],
        'channels' => ['fa-store', ['show_packages', 'show_one_off', 'package_price_display']],
        'assignment' => ['fa-user-check', ['assign_by_product_attribute', 'customer_worker_selection', 'auto_assign', 'auto_assign_driver', 'auto_confirm_postpaid']],
        'calendar' => ['fa-calendar', ['dispatcher_past_days', 'dispatcher_future_days', 'customer_future_days']],
        'packages' => ['fa-box', ['handpick_session_dates', 'allow_multiple_sessions_per_day']],
        'interface' => ['fa-display', ['show_material_option', 'show_time_options_app', 'show_service_type_field']],
    ];
    if (!isset($settingsAction)) $sections['tracking'] = ['fa-location-dot', ['tracking_enabled']];
@endphp
<style>
    .cs-field .is-set, .cs-days .is-set, .cs-steppers .is-set { border-color: #a5b4fc; background-color: #f5f7ff; font-weight: 600; }
</style>
@include('admin.hourly_service_categories._settings-ui')

<div @isset($settingsAction) id="tab-settings" @endisset>
<div class="cs-overview">
    <div class="cs-overview-icon"><i class="fa fa-sliders"></i></div>
    <div class="cs-overview-copy">
        <h4>{{ $heading ?? __('hourly_category_ui.settings_title') }}</h4>
        <p>{{ isset($settingsAction) ? __('hourly.settings_hint') : __('hourly_category_ui.settings_subtitle') }}</p>
    </div>
    <div class="cs-overview-stats">
        <span class="cs-status-pill is-custom"><i class="fa fa-pen"></i> {{ trans_choice('hourly_category_ui.custom_settings_count', $customSettingsCount, ['count' => $customSettingsCount]) }}</span>
        <span class="cs-status-pill"><i class="fa fa-link"></i> {{ __('hourly_category_ui.inheritance_active') }}</span>
    </div>
</div>

<div class="cs-layout">
    <aside class="cs-sidebar" aria-label="{{ __('hourly_category_ui.settings_navigation') }}">
        <p>{{ __('hourly_category_ui.settings_navigation') }}</p>
        <nav>
            @if(!isset($settingsAction))
            <a href="#cs-presentation"><i class="fa fa-palette"></i><span>{{ __('hourly_category_ui.presentation_short') }}</span></a>
            @endif
            @foreach ($sections as $section => [$icon])
                <a href="#cs-section-{{ $section }}"><i class="fa {{ $icon }}"></i><span>{{ $s($section) }}</span></a>
            @endforeach
            <a href="#cs-steppers"><i class="fa fa-arrow-down-1-9"></i><span>{{ $f('order_form_steppers') }}</span></a>
            <a href="#cs-working-days"><i class="fa fa-business-time"></i><span>{{ $s('working_days') }}</span></a>
        </nav>
    </aside>

    <div class="cs-content">
@if(!isset($settingsAction))
    @include('admin.hourly_service_categories._presentation')
@endif
<form method="POST" action="{{ $settingsAction ?? route('admin.hourly-category-settings.update', $category) }}" data-settings-form>
    @csrf @method('PUT')
    <div class="cs-inheritance-note"><i class="fa fa-circle-info"></i><div><strong>{{ __('hourly_category_ui.inheritance_title') }}</strong><span>{{ __('hourly.settings_hint') }}</span></div></div>

    <div class="cs-sections-grid">
      @foreach ($sections as $section => [$icon, $keys])
        <section class="ht-card cs-section-card" id="cs-section-{{ $section }}">
            <div class="ht-head"><div class="ht-head-icon"><i class="fa {{ $icon }}"></i></div><div><h5>{{ $s($section) }}</h5></div></div>
            <div class="ht-body cs-grid">
                @foreach ($keys as $key)
                    @include('admin.hourly_service_categories._setting-field', ['key' => $key, 'options' => $key === 'package_price_display' ? ['total' => $f('price_total'), 'per_visit' => $f('price_per_visit')] : []])
                @endforeach
                @if ($section === 'assignment')
                    <div class="cs-field">
                        <label class="form-label" for="cs_auto_mode">{{ $f('auto_complete_visits_at') }}</label>
                        <div class="d-flex gap-2">
                            <select class="form-select {{ $autoMode !== 'inherit' ? 'is-set' : '' }}" id="cs_auto_mode" name="auto_complete_mode" data-auto-mode {{ $canEdit ? '' : 'disabled' }}>
                                <option value="inherit">{{ __('hourly.inherit_value', ['value' => $generalAuto]) }}</option>
                                <option value="disabled" {{ $autoMode === 'disabled' ? 'selected' : '' }}>{{ __('hourly.disabled') }}</option>
                                <option value="time" {{ $autoMode === 'time' ? 'selected' : '' }}>{{ $f('auto_complete_time') }}</option>
                            </select>
                            <input type="time" class="form-control" name="auto_complete_time" aria-label="{{ $f('auto_complete_time') }}" value="{{ $autoMode === 'time' ? $categorySettings['auto_complete_visits_at'] : '' }}" data-auto-time {{ $canEdit ? '' : 'disabled' }}>
                        </div>
                    </div>
                @endif
                @if ($section === 'calendar')
                    <div class="cs-field">
                        <label class="form-label" for="cs_same_day_cutoff">{{ $f('same_day_cutoff') }}</label>
                        <div class="input-group">
                            <input type="time" class="form-control {{ isset($categorySettings['same_day_cutoff']) ? 'is-set' : '' }}" id="cs_same_day_cutoff" name="same_day_cutoff" value="{{ $categorySettings['same_day_cutoff'] ?? '' }}" {{ $canEdit ? '' : 'disabled' }}>
                            <span class="input-group-text small">{{ __('hourly.inherit_value', ['value' => $generalSettings['same_day_cutoff'] ?: '—']) }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>
      @endforeach
    </div>

    {{-- عدّادات نموذج الطلب: قابلة للترتيب بالسحب --}}
    <section class="ht-card" id="cs-steppers">
        <div class="ht-head">
            <div class="ht-head-icon"><i class="fa fa-arrow-down-1-9"></i></div>
            <div><h5>{{ $f('order_form_steppers') }}</h5><p>{{ $f('stepper_drag') }}</p></div>
            <div class="ht-actions">
                <select class="form-select form-select-sm" name="order_form_steppers_mode" data-steppers-mode aria-label="{{ $f('order_form_steppers') }}" {{ $canEdit ? '' : 'disabled' }}>
                    <option value="inherit">{{ __('hourly.inherit') }}</option>
                    <option value="custom" {{ $steppersMode === 'custom' ? 'selected' : '' }}>{{ $f('steppers_custom') }}</option>
                </select>
            </div>
        </div>
        <div class="ht-body cs-steppers">
            <fieldset data-steppers {{ $steppersMode === 'custom' && $canEdit ? '' : 'disabled' }}>
                <div data-stepper-list>
                    @foreach ($steppers as $i => $stepper)
                        @include('admin.hourly_service_categories._stepper-row', ['i' => $i, 'stepper' => $stepper])
                    @endforeach
                </div>
                <template data-stepper-template>@include('admin.hourly_service_categories._stepper-row', ['i' => '__i__', 'stepper' => ['key' => 'workers', 'label' => '', 'channels' => \App\Services\Hourly\HourlyCategorySettings::CHANNELS, 'min' => 1, 'max' => 5, 'scale_price' => true]])</template>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-3" data-stepper-add><i class="fa fa-plus ms-1"></i> {{ $f('stepper_add') }}</button>
            </fieldset>
        </div>
    </section>

    {{-- أيام العمل --}}
    <section class="ht-card" id="cs-working-days">
        <div class="ht-head"><div class="ht-head-icon"><i class="fa fa-business-time"></i></div><div><h5>{{ $s('working_days') }}</h5></div></div>
        <div class="ht-body">
            <div class="cs-days">
                        @foreach (\App\Services\Hourly\HourlyCategorySettings::WEEKDAYS as $day => $dayName)
                            @php
                                $dayName = __('hourly_category_ui.'.['sunday','monday','tuesday','wednesday','thursday','friday','saturday'][$day]);
                                $own = $categorySettings['working_days'][$day] ?? [];
                                $general = $generalSettings['working_days'][$day];
                                $isOn = isset($own['is_on']) ? ((int) $own['is_on']).'' : 'inherit';
                            @endphp
                            <div class="cs-day-card">
                                <div class="cs-day-head"><strong>{{ $dayName }}</strong><span>{{ $f('is_on') }}</span></div>
                                <div class="cs-day-toggle">
                                    <select class="form-select form-select-sm {{ $isOn !== 'inherit' ? 'is-set' : '' }}" data-inherited-on="{{ $general['is_on'] ? 1 : 0 }}" name="working_days[{{ $day }}][is_on]" aria-label="{{ $f('is_on') }} {{ $dayName }}" {{ $canEdit ? '' : 'disabled' }}>
                                        <option value="inherit">{{ __('hourly.inherit_value', ['value' => __('hourly.'.($general['is_on'] ? 'yes' : 'no'))]) }}</option>
                                        <option value="1" {{ $isOn === '1' ? 'selected' : '' }}>{{ __('hourly.yes') }}</option>
                                        <option value="0" {{ $isOn === '0' ? 'selected' : '' }}>{{ __('hourly.no') }}</option>
                                    </select>
                                </div>
                                <label>{{ $f('order_buffer_hours') }}
                                    <input type="number" min="0" max="168" class="form-control form-control-sm {{ isset($own['order_buffer_hours']) ? 'is-set' : '' }}" name="working_days[{{ $day }}][order_buffer_hours]" value="{{ $own['order_buffer_hours'] ?? '' }}" placeholder="{{ __('hourly.inherit_value', ['value' => $general['order_buffer_hours']]) }}" aria-label="{{ $f('order_buffer_hours') }} {{ $dayName }}" {{ $canEdit ? '' : 'disabled' }}>
                                </label>
                            </div>
                        @endforeach
            </div>
        </div>
    </section>

    @if ($canEdit)
        <div class="ht-card hd-save cs-save-bar"><div class="ht-foot"><div><strong>{{ __('hourly_category_ui.save_settings_title') }}</strong><small>{{ __('hourly_category_ui.save_settings_hint') }}</small></div><button class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> {{ __('hourly.save') }}</button></div></div>
    @endif
</form>
    </div>
</div>
</div>

@push('category-js')
<script>
(() => {
    const mode = document.querySelector('[data-auto-mode]'), time = document.querySelector('[data-auto-time]');
    if (mode) { const sync = () => { time.disabled = mode.value !== 'time' || mode.disabled; time.required = !time.disabled; }; mode.addEventListener('change', sync); sync(); }

    const stepperMode = document.querySelector('[data-steppers-mode]'), fieldset = document.querySelector('[data-steppers]');
    if (!fieldset) return;
    const list = fieldset.querySelector('[data-stepper-list]'), template = fieldset.querySelector('[data-stepper-template]'), add = fieldset.querySelector('[data-stepper-add]');
    const renumber = () => {
        list.querySelectorAll('[data-stepper]').forEach((row, i) => row.querySelectorAll('[name]').forEach(el => el.name = el.name.replace(/order_form_steppers\[[^\]]+\]/, 'order_form_steppers[' + i + ']')));
        add.disabled = list.children.length >= 2;
    };
    stepperMode.addEventListener('change', () => { fieldset.disabled = stepperMode.value !== 'custom'; });
    add.addEventListener('click', () => {
        list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__i__', list.children.length));
        const used = [...list.querySelectorAll('[data-stepper-key]')].map(s => s.value);
        const last = list.lastElementChild.querySelector('[data-stepper-key]');
        if (used.filter(v => v === 'workers').length > 1) last.value = 'quantity';
        renumber();
    });
    list.addEventListener('click', e => { if (e.target.closest('[data-stepper-remove]')) { e.target.closest('[data-stepper]').remove(); renumber(); } });
    // ترتيب بالسحب (HTML5)
    let dragged = null;
    list.addEventListener('dragstart', e => { dragged = e.target.closest('[data-stepper]'); dragged?.classList.add('is-dragging'); });
    list.addEventListener('dragend', () => { dragged?.classList.remove('is-dragging'); dragged = null; renumber(); });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const over = e.target.closest('[data-stepper]');
        if (!dragged || !over || over === dragged) return;
        const after = e.clientY > over.getBoundingClientRect().top + over.offsetHeight / 2;
        list.insertBefore(dragged, after ? over.nextSibling : over);
    });
    renumber();
})();
</script>
@endpush
