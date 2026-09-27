{{-- زر اختيار أيقونة: يخزّن الكلاس في حقل مخفي، والقائمة المشتركة (_icon_panel) تُفتح تحته --}}
@php
    $value = trim((string) ($value ?? ''));
@endphp
<div class="req-icon-picker">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" class="req-icon-picker__input">
    <button type="button" class="form-control req-icon-picker__toggle" aria-haspopup="listbox">
        <span class="req-icon-picker__preview">
            <i class="{{ $value !== '' ? $value : 'fa-regular fa-image' }}"></i>
        </span>
        <span class="req-icon-picker__label" data-empty="{{ __('icon_picker.choose_icon') }}">
            {{ $value !== '' ? $value : __('icon_picker.choose_icon') }}
        </span>
        <i class="fa-solid fa-chevron-down req-icon-picker__caret"></i>
    </button>
</div>
