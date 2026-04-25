<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="country_id">الدولة <span class="text-danger">*</span></label>
            <select class="form-control @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                <option value="">اختر الدولة</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ (string) old('country_id', isset($city) ? $city->country_id : ($selectedCountryId ?? '')) === (string) $country->id ? 'selected' : '' }}>
                        {{ $country->name_ar }} @if($country->name_en) ({{ $country->name_en }}) @endif
                    </option>
                @endforeach
            </select>
            @error('country_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="code">رمز المدينة</label>
            <input type="text" class="form-control @error('code') is-invalid @enderror"
                   id="code" name="code" value="{{ old('code', isset($city) ? $city->code : '') }}"
                   placeholder="مثل: RUH">
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="name_ar">اسم المدينة بالعربية <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name_ar') is-invalid @enderror"
                   id="name_ar" name="name_ar" value="{{ old('name_ar', isset($city) ? $city->name_ar : '') }}" required>
            @error('name_ar')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="name_en">اسم المدينة بالإنجليزية <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                   id="name_en" name="name_en" value="{{ old('name_en', isset($city) ? $city->name_en : '') }}" required>
            @error('name_en')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="latitude">خط العرض</label>
            <input type="number" step="0.0000001" class="form-control @error('latitude') is-invalid @enderror"
                   id="latitude" name="latitude" value="{{ old('latitude', isset($city) ? $city->latitude : '') }}"
                   placeholder="24.7135517">
            @error('latitude')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="longitude">خط الطول</label>
            <input type="number" step="0.0000001" class="form-control @error('longitude') is-invalid @enderror"
                   id="longitude" name="longitude" value="{{ old('longitude', isset($city) ? $city->longitude : '') }}"
                   placeholder="46.6752957">
            @error('longitude')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="sort_order">ترتيب العرض</label>
            <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                   id="sort_order" name="sort_order" value="{{ old('sort_order', isset($city) ? $city->sort_order : 0) }}">
            @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <input type="hidden" name="is_capital" value="0">
            <div class="custom-control custom-switch mt-4">
                <input type="checkbox" class="custom-control-input" id="is_capital"
                       name="is_capital" value="1" {{ old('is_capital', isset($city) ? $city->is_capital : false) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_capital">عاصمة الدولة</label>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <input type="hidden" name="is_active" value="0">
            <div class="custom-control custom-switch mt-4">
                <input type="checkbox" class="custom-control-input" id="is_active"
                       name="is_active" value="1" {{ old('is_active', isset($city) ? $city->is_active : true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active">نشطة</label>
            </div>
        </div>
    </div>
</div>

<div class="form-group mt-4">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ route('cities.index') }}" class="btn btn-secondary">إلغاء</a>
</div>
