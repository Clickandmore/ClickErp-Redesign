{{-- حقول الفئة (مودال الإضافة/التعديل). تتطلب: $category --}}
@php
    $iconType = old('icon_type', $category->icon_type ?: 'library');
    $categoryActive = (bool) old('is_active', $category->is_active ?? true);
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name_ar">{{ __('hourly.category_name_ar') }} <span class="text-danger">*</span></label>
        <input class="form-control @error('name_ar') is-invalid @enderror" id="name_ar" name="name_ar" value="{{ old('name_ar', $category->name_ar) }}" maxlength="150" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="name_en">{{ __('hourly.category_name_en') }}</label>
        <input class="form-control @error('name_en') is-invalid @enderror" id="name_en" name="name_en" value="{{ old('name_en', $category->name_en) }}" maxlength="150" dir="ltr">
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="slug">{{ __('hourly_category_ui.slug') }}</label>
        <input class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="160" dir="ltr" placeholder="{{ __('hourly_category_ui.auto') }}">
        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="color">{{ __('hourly.category_color') }}</label>
        <input type="color" class="form-control form-control-color w-100 @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $category->color ?: '#6861ee') }}">
        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="description_ar">{{ __('hourly.category_description_ar') }}</label>
        <textarea class="form-control" id="description_ar" name="description_ar" rows="2" maxlength="2000">{{ old('description_ar', $category->description_ar) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="description_en">{{ __('hourly.category_description_en') }}</label>
        <textarea class="form-control" id="description_en" name="description_en" rows="2" maxlength="2000" dir="ltr">{{ old('description_en', $category->description_en) }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="icon_type">{{ __('hourly.category_icon') }}</label>
        <select class="form-select" id="icon_type" name="icon_type" data-icon-type>
            <option value="library" {{ $iconType === 'library' ? 'selected' : '' }}>{{ __('hourly.category_icon_library') }}</option>
            <option value="upload" {{ $iconType === 'upload' ? 'selected' : '' }}>{{ __('hourly.category_icon_upload') }}</option>
        </select>
    </div>
    <div class="col-12" data-icon-pane="library">
        <span class="form-label d-block">{{ __('hourly_icons.title') }}</span>
        @include('admin.hourly_teams._icon-picker', ['name' => 'icon', 'id' => 'icon', 'value' => old('icon', $category->icon_type === 'upload' ? '' : $category->icon)])
    </div>
    <div class="col-md-8" data-icon-pane="upload">
        <label class="form-label" for="icon_file">{{ __('hourly.category_icon_upload') }}</label>
        <input type="file" class="form-control @error('icon_file') is-invalid @enderror" id="icon_file" name="icon_file" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        @error('icon_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-8">
        <label class="form-label" for="image">{{ __('hourly.category_image') }}</label>
        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/png,image/jpeg,image/webp">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <label class="small mt-1 d-none" data-remove-image><input type="checkbox" name="remove_image" value="1"> {{ __('hourly.category_remove_image') }}</label>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="ht-switch form-switch w-100">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $categoryActive ? 'checked' : '' }}>
            <div><label for="is_active">{{ __('hourly.category_active') }}</label></div>
        </div>
    </div>
</div>
