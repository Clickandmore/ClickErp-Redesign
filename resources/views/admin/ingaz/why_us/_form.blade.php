@php
    $editing = isset($obj) && $obj;
@endphp

<div class="col-md-5">
    <label class="form-label">{{ __('why_us.icon') }}</label>
    @include('admin.partials.icon_picker._picker', [
        'name' => 'icon',
        'value' => $editing ? $obj->icon : '',
    ])
</div>

<div class="col-md-3">
    <label class="form-label">{{ __('why_us.sort_order') }}</label>
    <input type="number" name="sort_order" class="form-control" min="1" max="9999" data-validation="required"
        value="{{ $editing ? $obj->sort_order : $nextSortOrder }}">
</div>

<div class="col-md-4 d-flex align-items-end">
    <div class="form-check form-switch mb-2">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="why-us-active"
            {{ $editing ? ($obj->is_active ? 'checked' : '') : 'checked' }}>
        <label class="form-check-label" for="why-us-active">{{ __('why_us.active') }}</label>
    </div>
</div>

@foreach (config('translatable.locales') as $locale)
    <div class="col-md-6">
        <label class="form-label">{{ __('why_us.title_' . $locale) }}</label>
        <input type="text" name="title:{{ $locale }}" class="form-control" maxlength="255" data-validation="required"
            value="{{ $editing ? optional($obj->translate($locale))->title : '' }}">
    </div>
@endforeach

@foreach (config('translatable.locales') as $locale)
    <div class="col-md-6">
        <label class="form-label">{{ __('why_us.description_' . $locale) }}</label>
        <textarea name="description:{{ $locale }}" class="form-control" rows="3" maxlength="1000"
            data-validation="required">{{ $editing ? optional($obj->translate($locale))->description : '' }}</textarea>
    </div>
@endforeach

@include('admin.partials.icon_picker._panel')
