@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="title_ar">عنوان اللغة بالعربية</label>
        <input type="text" id="title_ar" name="title_ar"
            class="form-control @error('title_ar') is-invalid @enderror"
            value="{{ old('title_ar', $language->title_ar ?? '') }}" maxlength="255" required dir="rtl">
        @error('title_ar')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="title_en">عنوان اللغة بالإنجليزية</label>
        <input type="text" id="title_en" name="title_en"
            class="form-control @error('title_en') is-invalid @enderror"
            value="{{ old('title_en', $language->title_en ?? '') }}" maxlength="255" required dir="ltr">
        @error('title_en')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @isset($language)
        <div class="col-md-6">
            <label class="form-label">رمز التوافق مع الـAPI</label>
            <input type="text" class="form-control bg-light" value="{{ $language->code }}" readonly dir="ltr">
            <small class="text-muted">يبقى هذا الرمز ثابتًا حتى عند تعديل عنوان اللغة.</small>
        </div>
    @endisset

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                {{ old('is_active', isset($language) ? $language->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">إظهار اللغة في نموذج السيرة الذاتية</label>
        </div>
    </div>

    <div class="col-12 d-flex gap-2 justify-content-end">
        <a href="{{ route('languages.index') }}" class="btn btn-outline-secondary">رجوع</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>حفظ
        </button>
    </div>
</div>
