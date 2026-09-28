{{-- حقول المنطقة: تُستخدم في صفحة الإضافة/التعديل وفي مودال الإضافة. تتطلب: $district, $cities, $teams, $teamIds --}}
@php
    $districtActive = (bool) old('is_active', $district->is_active ?? true);
    $selectedTeams = old('team_ids', session()->hasOldInput() ? [] : $teamIds);
@endphp
<div id="lookup-feedback" class="alert border-0 rounded-3 d-none" role="status"></div>

<div class="hd-section-title">1. المدينة والاسم</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <label for="city_id" class="form-label">المدينة <span class="text-danger">*</span></label>
        <select id="city_id" name="city_id" class="form-select @error('city_id') is-invalid @enderror" required>
            <option value="">اختر المدينة</option>
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" {{ (string) old('city_id', $district->city_id) === (string) $city->id ? 'selected' : '' }}>{{ $city->name_ar ?: $city->name_en }}</option>
            @endforeach
        </select>
        @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label">اسم المنطقة بالعربية <span class="text-danger">*</span></label>
        <div class="input-group has-validation">
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" maxlength="150" required value="{{ old('name', $district->name) }}" placeholder="حي النرجس">
            <button type="button" class="btn btn-outline-secondary" data-translate="ar" title="ترجمة إلى العربية"><i class="fa fa-language"></i></button>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <label for="name_en" class="form-label">اسم المنطقة بالإنجليزية</label>
        <div class="input-group has-validation">
            <input id="name_en" name="name_en" class="form-control @error('name_en') is-invalid @enderror" maxlength="150" dir="ltr" value="{{ old('name_en', $district->name_en) }}" placeholder="Al Narjis">
            <button type="button" class="btn btn-outline-secondary" data-translate="en" title="ترجمة إلى الإنجليزية"><i class="fa fa-language"></i></button>
            @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="col-12 d-flex align-items-center gap-2 flex-wrap">
        <button type="button" id="translate-all" class="btn btn-sm btn-outline-primary rounded-3"><i class="fa fa-wand-magic-sparkles ms-1"></i> Translate All — ترجمة الحقول الفارغة</button>
        <small class="text-muted">من أول حقل ممتلئ، دون استبدال الأسماء المكتوبة.</small>
    </div>
</div>

<div class="hd-section-title">2. الموقع</div>
<div class="row g-3 mb-2">
    <div class="col-md-5">
        <label for="latitude" class="form-label">خط العرض</label>
        <input type="number" step="any" min="-90" max="90" id="latitude" name="latitude" dir="ltr" class="form-control ht-num @error('latitude') is-invalid @enderror" value="{{ old('latitude', $district->latitude) }}">
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label for="longitude" class="form-label">خط الطول</label>
        <input type="number" step="any" min="-180" max="180" id="longitude" name="longitude" dir="ltr" class="form-control ht-num @error('longitude') is-invalid @enderror" value="{{ old('longitude', $district->longitude) }}">
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="button" id="locate" class="btn btn-outline-primary rounded-3 w-100" title="البحث عن موقع المنطقة داخل المدينة المختارة"><i class="fa fa-magnifying-glass-location ms-1"></i> GET الموقع</button>
    </div>
</div>
<div id="location-results" class="d-grid gap-2 mb-2"></div>
<div class="form-text mb-4">اختر المدينة واكتب الاسم ثم اضغط «GET الموقع» واختر النتيجة الصحيحة (© OpenStreetMap)، أو أدخل الإحداثيات يدويًا.</div>

<div class="hd-section-title">3. الحالة وربط الفرق</div>
<div class="ht-switch form-switch mb-3">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ $districtActive ? 'checked' : '' }}>
    <div>
        <label for="is_active">المنطقة نشطة</label>
        <small>المنطقة غير النشطة لا تُعرض ضمن مناطق الخدمة.</small>
    </div>
</div>
<input type="hidden" name="teams_submitted" value="1">
<label for="team_ids" class="form-label">ربط الفرق — اختياري</label>
<select name="team_ids[]" id="team_ids" class="form-select @error('team_ids') is-invalid @enderror" multiple data-placeholder="ابحث عن فريق…" data-empty="لا توجد فرق متاحة.">
    @foreach ($teams as $team)
        <option value="{{ $team->id }}" {{ in_array($team->id, $selectedTeams) ? 'selected' : '' }}>{{ $team->name }}{{ $team->is_active ? '' : ' (متوقف)' }}</option>
    @endforeach
</select>
@error('team_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
<div class="form-text">الفرق التي تخدم هذه المنطقة.</div>
