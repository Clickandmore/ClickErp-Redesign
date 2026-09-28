{{-- حقل إعداد واحد مع خيار «يرث». يتطلب: $key، واختياريًا $options (للقوائم) و$step. يعتمد على $categorySettings و$generalSettings. --}}
@php
    $type = \App\Services\Hourly\HourlyCategorySettings::GENERAL[$key][0];
    $label = __('hourly.settings_fields.'.$key);
    $own = $categorySettings[$key] ?? null;
    $general = $generalSettings[$key] ?? null;
    $show = fn ($v) => is_bool($v) ? __('hourly.'.($v ? 'yes' : 'no')) : ($v === null || $v === '' ? '—' : (string) $v);
    $disabled = $canEdit ? '' : 'disabled';
@endphp
<div class="cs-field">
    @if ($type === 'channels')
        <span class="form-label d-block">{{ $label }}</span>
        <div class="row g-2">
            @foreach (\App\Services\Hourly\HourlyCategorySettings::CHANNELS as $channel)
                @php $value = isset($own[$channel]) ? ((int) $own[$channel]).'' : 'inherit'; @endphp
                <div class="col-6">
                    <label class="small text-muted" for="cs_{{ $key }}_{{ $channel }}">{{ __('hourly.channel_'.$channel) }}</label>
                    <select class="form-select form-select-sm {{ $value !== 'inherit' ? 'is-set' : '' }}" id="cs_{{ $key }}_{{ $channel }}" data-inherited-on="{{ ($general[$channel] ?? false) ? 1 : 0 }}" name="{{ $key }}[{{ $channel }}]" {{ $disabled }}>
                        <option value="inherit">{{ __('hourly.inherit_value', ['value' => $show($general[$channel] ?? false)]) }}</option>
                        <option value="1" {{ $value === '1' ? 'selected' : '' }}>{{ __('hourly.yes') }}</option>
                        <option value="0" {{ $value === '0' ? 'selected' : '' }}>{{ __('hourly.no') }}</option>
                    </select>
                </div>
            @endforeach
        </div>
    @elseif ($type === 'bool')
        @php $value = $own === null ? 'inherit' : ((int) $own).''; @endphp
        <label class="form-label" for="cs_{{ $key }}">{{ $label }}</label>
        <select class="form-select {{ $value !== 'inherit' ? 'is-set' : '' }}" id="cs_{{ $key }}" data-inherited-on="{{ $general ? 1 : 0 }}" name="{{ $key }}" {{ $disabled }}>
            <option value="inherit">{{ __('hourly.inherit_value', ['value' => $show((bool) $general)]) }}</option>
            <option value="1" {{ $value === '1' ? 'selected' : '' }}>{{ __('hourly.yes') }}</option>
            <option value="0" {{ $value === '0' ? 'selected' : '' }}>{{ __('hourly.no') }}</option>
        </select>
    @elseif (str_starts_with($type, 'enum'))
        <label class="form-label" for="cs_{{ $key }}">{{ $label }}</label>
        <select class="form-select {{ $own !== null ? 'is-set' : '' }}" id="cs_{{ $key }}" name="{{ $key }}" {{ $disabled }}>
            <option value="inherit">{{ __('hourly.inherit_value', ['value' => $options[$general] ?? $general]) }}</option>
            @foreach ($options as $value => $text)<option value="{{ $value }}" {{ $own === $value ? 'selected' : '' }}>{{ $text }}</option>@endforeach
        </select>
    @else
        <label class="form-label" for="cs_{{ $key }}">{{ $label }}</label>
        <input type="number" class="form-control {{ $own !== null ? 'is-set' : '' }}" id="cs_{{ $key }}" name="{{ $key }}" value="{{ $own }}" step="{{ $type === 'decimal' ? '0.01' : '1' }}" min="0" placeholder="{{ __('hourly.inherit_value', ['value' => $show($general)]) }}" {{ $disabled }}>
    @endif
@php($help = ['show_tax_separately'=>'tax_hint','show_custom_fee_separately'=>'fee_hint','allow_reschedule'=>'reschedule_hint','show_packages'=>'packages_hint','show_one_off'=>'packages_hint','package_price_display'=>'price_hint','assign_by_product_attribute'=>'assignment_hint','customer_worker_selection'=>'worker_hint','handpick_session_dates'=>'dates_hint','allow_multiple_sessions_per_day'=>'multiple_hint'][$key] ?? null)
@if($help)<p class="cs-help">{{ __('hourly_category_ui.'.$help) }}</p>@endif
@if($key==='auto_assign_driver')<p class="cs-help">{{ __('hourly.settings_fields.auto_assign_driver_hint') }}</p>@endif
</div>
