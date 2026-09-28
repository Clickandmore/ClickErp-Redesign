{{-- حقول المدينة: تُستخدم في صفحة الإضافة/التعديل وفي مودال الإضافة. تتطلب: $city --}}
@php
    $cityActive = (bool) old('is_active', $city->is_active ?? true);
@endphp
<div id="lookup-feedback" class="alert border-0 rounded-3 d-none" role="status"></div>

<div class="hd-section-title">1. اسم المدينة</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="name_ar" class="form-label">بالعربية <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input id="name_ar" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $city->name_ar) }}" maxlength="150" required placeholder="الرياض">
            <button type="button" class="btn btn-outline-secondary" data-translate="ar" title="ترجمة إلى العربية"><i class="fa fa-language"></i></button>
            @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <label for="name_en" class="form-label">بالإنجليزية <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" dir="ltr" value="{{ old('name_en', $city->name_en) }}" maxlength="150" required placeholder="Riyadh">
            <button type="button" class="btn btn-outline-secondary" data-translate="en" title="ترجمة إلى الإنجليزية"><i class="fa fa-language"></i></button>
            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="translate-all"><i class="fa fa-wand-magic-sparkles ms-1"></i> ترجمة الحقول الفارغة — Translate All</button>
        <small class="text-muted">من أول حقل ممتلئ، دون تغيير الأسماء المكتوبة.</small>
    </div>
</div>

<div class="hd-section-title">2. مركز المدينة</div>
<div class="row g-3 mb-2">
    <div class="col-md-5">
        <label for="latitude" class="form-label">خط العرض</label>
        <input type="number" step="any" min="-90" max="90" name="latitude" id="latitude" dir="ltr" class="form-control ht-num @error('latitude') is-invalid @enderror" value="{{ old('latitude', $city->latitude) }}">
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label for="longitude" class="form-label">خط الطول</label>
        <input type="number" step="any" min="-180" max="180" name="longitude" id="longitude" dir="ltr" class="form-control ht-num @error('longitude') is-invalid @enderror" value="{{ old('longitude', $city->longitude) }}">
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="button" class="btn btn-outline-primary rounded-3 w-100" id="locate"><i class="fa fa-magnifying-glass-location ms-1"></i> جلب الموقع GET</button>
    </div>
</div>
<div id="location-results" class="d-grid gap-2 mb-2"></div>
<div class="form-text mb-4">مركز المدينة تتمركز عليه الخرائط. اضغط «جلب الموقع» واختر النتيجة، أو أدخل الإحداثيات يدويًا (© OpenStreetMap).</div>

<div class="hd-section-title">3. العرض والحالة</div>
<div class="row g-3">
    <div class="col-md-4">
        <label for="sort_order" class="form-label">ترتيب العرض <span class="text-danger">*</span></label>
        <input type="number" id="sort_order" name="sort_order" class="form-control ht-num @error('sort_order') is-invalid @enderror" min="0" max="99999" value="{{ old('sort_order', $city->sort_order) }}" required>
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">الأصغر يظهر أولًا.</div>
    </div>
    <div class="col-md-8">
        <div class="ht-switch form-switch h-100">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $cityActive ? 'checked' : '' }}>
            <div>
                <label for="is_active">المدينة نشطة</label>
                <small>المدينة غير النشطة لا تظهر ضمن مدن الخدمة.</small>
            </div>
        </div>
    </div>
</div>
