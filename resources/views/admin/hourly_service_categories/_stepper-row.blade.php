{{-- صف عدّاد في نموذج الطلب. يتطلب: $i، $stepper --}}
@php $f = fn ($key) => __('hourly.settings_fields.'.$key); $base = 'order_form_steppers['.$i.']'; @endphp
<div class="cs-stepper" data-stepper draggable="true">
    <span class="cs-handle" title="{{ $f('stepper_drag') }}"><i class="fa fa-grip-vertical"></i></span>
    <label class="cs-stepper-field"><span>{{ $f('order_form_steppers') }}</span><select class="form-select form-select-sm" name="{{ $base }}[key]" data-stepper-key aria-label="{{ $f('order_form_steppers') }}">
        @foreach (\App\Services\Hourly\HourlyCategorySettings::STEPPER_KEYS as $key)<option value="{{ $key }}" {{ ($stepper['key'] ?? '') === $key ? 'selected' : '' }}>{{ $f('stepper_'.$key) }}</option>@endforeach
    </select></label>
    <label class="cs-stepper-field"><span>{{ $f('stepper_label') }}</span><input class="form-control form-control-sm" name="{{ $base }}[label]" value="{{ $stepper['label'] ?? '' }}" maxlength="60" placeholder="{{ $f('stepper_label') }}" aria-label="{{ $f('stepper_label') }}"></label>
    <div class="cs-stepper-field"><span>{{ __('hourly_category_ui.display_channels') }}</span><div class="cs-stepper-channels small">
        @foreach (\App\Services\Hourly\HourlyCategorySettings::CHANNELS as $channel)
            <label class="d-flex align-items-center gap-1 m-0"><input type="checkbox" name="{{ $base }}[channels][]" value="{{ $channel }}" {{ in_array($channel, $stepper['channels'] ?? [], true) ? 'checked' : '' }}> {{ __('hourly.channel_'.$channel) }}</label>
        @endforeach
    </div></div>
    <label class="cs-stepper-field"><span>{{ $f('stepper_min') }}</span><input type="number" class="form-control form-control-sm" name="{{ $base }}[min]" value="{{ $stepper['min'] ?? 1 }}" min="1" max="50" aria-label="{{ $f('stepper_min') }}"></label>
    <label class="cs-stepper-field"><span>{{ $f('stepper_max') }}</span><input type="number" class="form-control form-control-sm" name="{{ $base }}[max]" value="{{ $stepper['max'] ?? 1 }}" min="1" max="50" aria-label="{{ $f('stepper_max') }}"></label>
    <label class="cs-stepper-field"><span>{{ $f('stepper_scale_price') }}</span><span class="cs-stepper-check"><input type="hidden" name="{{ $base }}[scale_price]" value="0"><input type="checkbox" name="{{ $base }}[scale_price]" value="1" {{ !empty($stepper['scale_price']) ? 'checked' : '' }}></span></label>
    <button type="button" class="btn btn-sm btn-outline-danger ht-icon-btn" data-stepper-remove title="{{ __('hourly.delete') }}"><i class="fa fa-xmark"></i></button>
</div>
