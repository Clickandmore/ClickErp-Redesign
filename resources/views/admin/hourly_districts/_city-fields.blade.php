{{-- حقول المدينة: تُستخدم في صفحة الإضافة/التعديل وفي مودال الإضافة. تتطلب: $city --}}
@php
    $cityActive = (bool) old('is_active', $city->is_active ?? true);
@endphp
<style>
    .city-fields .hd-section-title { font-size: .92rem; color: #0f172a; }
    .city-fields .hd-section-title .num { width: 24px; height: 24px; border-radius: 50%; display: inline-grid; place-items: center; background: #0e7490; color: #fff; font-size: .75rem; }
    .city-fields .form-label { font-weight: 600; color: #1e293b; }
    .city-fields .form-text, .city-fields .city-hint { color: #475569; font-size: .8rem; }
    .city-fields .city-counter { font-size: .75rem; color: #475569; }
    .city-fields .city-counter.is-full { color: #b45309; font-weight: 700; }
    .city-fields .btn-translate { white-space: nowrap; }
    .city-fields #lookup-feedback { display: flex; gap: .5rem; align-items: flex-start; }
</style>
<div class="city-fields" data-city-fields>
<div id="lookup-feedback" class="alert border-0 rounded-3 d-none" role="status"></div>

<div class="d-flex align-items-center justify-content-between mb-2">
    <div class="hd-section-title flex-grow-1 mb-0"><span class="num">1</span> اسم المدينة</div>
    <button type="button" class="btn btn-sm btn-outline-danger rounded-3 ms-0 me-2" data-city-reset title="مسح كل القيم المكتوبة في النموذج"><i class="fa fa-eraser ms-1"></i> مسح الحقول</button>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="name_ar" class="form-label">اسم المدينة بالعربية <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input id="name_ar" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror" value="{{ old('name_ar', $city->name_ar) }}" maxlength="50" required placeholder="مثال: الرياض" autocomplete="off">
            <button type="button" class="btn btn-outline-primary btn-translate" data-translate="ar" title="ترجمة الاسم الإنجليزي إلى العربية"><i class="fa fa-language ms-1"></i> ترجمة</button>
            <div class="invalid-feedback" data-error-for="name_ar">@error('name_ar'){{ $message }}@enderror</div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="city-hint">حروف عربية ومسافات فقط.</small>
            <small class="city-counter" data-counter-for="name_ar"></small>
        </div>
    </div>
    <div class="col-md-6">
        <label for="name_en" class="form-label">اسم المدينة بالإنجليزية <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" dir="ltr" value="{{ old('name_en', $city->name_en) }}" maxlength="50" required placeholder="e.g. Riyadh" autocomplete="off">
            <button type="button" class="btn btn-outline-primary btn-translate" data-translate="en" title="ترجمة الاسم العربي إلى الإنجليزية"><i class="fa fa-language ms-1"></i> ترجمة</button>
            <div class="invalid-feedback" data-error-for="name_en">@error('name_en'){{ $message }}@enderror</div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="city-hint">حروف إنجليزية ومسافات فقط (يُسمح بـ - أو ' بين الكلمات).</small>
            <small class="city-counter" data-counter-for="name_en"></small>
        </div>
    </div>
    <div class="col-12">
        <small class="city-hint"><i class="fa fa-circle-info ms-1 text-primary"></i> زر «ترجمة» يملأ الحقل المجاور له من الاسم المكتوب في الحقل الآخر. عند تعديل أحد الاسمين عدّل الآخر أو اضغط «ترجمة» مرة أخرى.</small>
    </div>
</div>

<div class="hd-section-title"><span class="num">2</span> مركز المدينة</div>
<div class="row g-3 mb-2">
    <div class="col-md-4">
        <label for="latitude" class="form-label">خط العرض (Latitude)</label>
        <input type="text" inputmode="decimal" name="latitude" id="latitude" dir="ltr" maxlength="12" placeholder="16 – 33" class="form-control ht-num @error('latitude') is-invalid @enderror" value="{{ old('latitude', $city->latitude) }}" autocomplete="off">
        <div class="invalid-feedback" data-error-for="latitude">@error('latitude'){{ $message }}@enderror</div>
    </div>
    <div class="col-md-4">
        <label for="longitude" class="form-label">خط الطول (Longitude)</label>
        <input type="text" inputmode="decimal" name="longitude" id="longitude" dir="ltr" maxlength="12" placeholder="34 – 56" class="form-control ht-num @error('longitude') is-invalid @enderror" value="{{ old('longitude', $city->longitude) }}" autocomplete="off">
        <div class="invalid-feedback" data-error-for="longitude">@error('longitude'){{ $message }}@enderror</div>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="button" class="btn btn-primary rounded-3 w-100" id="locate"><i class="fa fa-magnifying-glass-location ms-1"></i> جلب الموقع من الاسم</button>
    </div>
</div>
<div id="location-results" class="d-grid gap-2 mb-2"></div>
<div class="form-text mb-4">الإحداثيات يجب أن تكون داخل المملكة: خط العرض بين 16 و 33، وخط الطول بين 34 و 56. اضغط «جلب الموقع من الاسم» واختر النتيجة، أو أدخلها يدويًا (© OpenStreetMap).</div>

<div class="hd-section-title"><span class="num">3</span> العرض والحالة</div>
<div class="row g-3">
    <div class="col-md-4">
        <label for="sort_order" class="form-label">ترتيب العرض <span class="text-danger">*</span></label>
        <input type="text" inputmode="numeric" id="sort_order" name="sort_order" dir="ltr" maxlength="4" class="form-control ht-num @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $city->sort_order) }}" required autocomplete="off">
        <div class="invalid-feedback" data-error-for="sort_order">@error('sort_order'){{ $message }}@enderror</div>
        <div class="form-text">رقم من 0 إلى 9999، الأصغر يظهر أولًا.</div>
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
</div>
