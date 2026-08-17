<div class="row">
    <!-- قسم اختيار العميل الموجود -->
    <div id="existing-client-selection" class="row g-3 mb-4" style="display: none;">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>اختيار العميل الموجود</strong>
            </div>
        </div>
        <div class="col-md-12">
            <label for="existing_client_id" class="form-label">اختيار العميل <span class="text-danger">*</span></label>
            <select name="existing_client_id" id="existing_client_id" class="form-select form-control select2 ajax-client-select">
                <option value="" disabled selected>{{helperTrans('ingaz.choose')}}</option>
            </select>
        </div>
    </div>

    <!-- قسم إدخال بيانات العميل الجديد -->
    <div id="new-client-section" class="row g-3">
        <div class="col-12">
            <label class="form-label d-block">{{helperTrans("ingaz.client_type")}} <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input js-account-client-type" type="radio"
                           name="account_client_type" id="account_client_type_person" value="person" checked>
                    <label class="form-check-label" for="account_client_type_person">شخص</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input js-account-client-type" type="radio"
                           name="account_client_type" id="account_client_type_company" value="company">
                    <label class="form-check-label" for="account_client_type_company">شركة</label>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label for="client_name" class="form-label">{{helperTrans("ingaz.client_name")}} <span class="text-danger">*</span></label>
            <input type="text" id="client_name" name="client_name" class="form-control"
                   data-validation="required" >
        </div>

        <div class="col-md-6">
            <label for="client_phone" class="form-label">{{helperTrans("ingaz.client_phone")}} <span class="text-danger">*</span></label>
            <input type="tel" id="client_phone" name="client_phone" class="form-control"
                   data-validation="required" dir="rtl">
        </div>

        <div class="col-md-6">
            <label for="client_nationality_id"
                   class="form-label">{{helperTrans("ingaz.nationality_id")}}</label>
            <select id="client_nationality_id" name="client_nationality_id"
                    class="form-select form-control select2" >
                <option value="">{{helperTrans('ingaz.choose')}}</option>
                @foreach($countries as $country)
                    <option value="{{$country->id}}" {{ $country->code === 'SA' ? 'selected' : '' }}>{{$country->name_ar}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="client_email" class="form-label">{{helperTrans("ingaz.email")}} <small class="text-muted">(اختياري)</small></label>
            <input type="email" id="client_email" name="client_email" class="form-control" >
        </div>

        <div class="col-md-6 js-client-document-field">
            <label for="client_national_num" class="form-label">{{helperTrans("ingaz.national_num")}} <span class="text-danger">*</span></label>
            <input type="text" id="client_national_num" name="client_national_num" class="form-control" data-validation="required" >
        </div>

        <div class="col-md-6 js-client-company-field d-none">
            <label for="client_license_number" class="form-label">{{helperTrans("ingaz.license_number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="client_license_number" name="client_license_number" class="form-control">
        </div>

        <div class="col-md-6 js-client-birthdate-field js-client-person-field">
            <label for="client_birthdate" class="form-label">{{helperTrans("ingaz.birthdate")}} <span class="text-danger">*</span></label>
            <input type="text" id="client_birthdate" name="client_birthdate" class="form-control" data-validation="required"
                   placeholder="مثال: 1990-05-20 أو 1410/10/20">
        </div>

        <div class="col-md-6">
            <label for="phone2" class="form-label">{{helperTrans("ingaz.phone2")}} <span class="text-danger">*</span></label>
            <input type="tel" id="phone2" name="phone2" class="form-control" data-validation="required" >
        </div>

        <div class="col-md-6">
            <label for="address" class="form-label">{{helperTrans("ingaz.address")}} <span class="text-danger">*</span></label>
            <input type="text" id="address" name="address" class="form-control" data-validation="required" >
        </div>

        <div class="col-md-6">
            <label for="city" class="form-label">{{helperTrans("ingaz.city of receive")}} <span class="text-danger">*</span></label>
            <select id="city" name="city" class="form-select form-control select2 select2-city"
                data-validation="required" data-placeholder="اختر مدينة تسليم العميل">
                <option value="">{{ helperTrans('ingaz.choose') }}</option>
                @foreach ($saudiCities ?? collect() as $city)
                    <option value="{{ $city->name_ar }}">{{ $city->name_ar }}</option>
                @endforeach
            </select>
            <div class="contract-city-actions">
                <a href="{{ route('cities.create') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-plus-circle ms-1"></i>إضافة مدينة سعودية
                </a>
                <small class="text-muted align-self-center">يمكنك البحث واختيار مدينة من مدن السعودية.</small>
            </div>
        </div>

        <div class="col-md-6 js-client-person-field">
            <label for="beds_num" class="form-label">{{helperTrans("ingaz.beds number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="beds_num" name="beds_num" class="form-control" >
        </div>

        <div class="col-md-6 js-client-person-field">
            <label for="family_num" class="form-label">{{helperTrans("ingaz.family_number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="family_num" name="family_num" class="form-control" >
        </div>

        <div class="col-md-6 js-client-person-field">
            <label for="children" class="form-label">{{helperTrans("ingaz.children")}} <small class="text-muted">(اختياري - تحت سن 12)</small></label>
            <input type="number" id="children" max="50" name="children" class="form-control" >
        </div>

        <div class="col-md-6 js-client-person-field">
            <label for="job" class="form-label">{{helperTrans("ingaz.job")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="job" name="job" class="form-control" >
        </div>
    </div>
</div>
