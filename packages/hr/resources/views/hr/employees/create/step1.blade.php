<div class="row g-2">
    @if(!empty($sourceAdmin))
        <input type="hidden" name="admin_id" value="{{ $sourceAdmin->id }}">
    @endif

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $sourceAdmin->name ?? '') }}" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Employee Code") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="code" class="form-control" value="{{ old('code', !empty($sourceAdmin) ? 'USR-' . $sourceAdmin->id : '') }}" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.the address") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="address" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.phone") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="number" name="phone" class="form-control" value="{{ old('phone', $sourceAdmin->contact_phone ?? $sourceAdmin->phone ?? '') }}" data-validation="required,number" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.date of birth") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="date" name="birthday" class="form-control" data-validation="required" onchange="checkOther()">
    </div>


    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.ID Number") }}  <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="number" name="national_num" class="form-control" value="{{ old('national_num', $sourceAdmin->identity_number ?? '') }}" data-validation="required,number" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="identity_expiry_date" class="form-label"> {{ __("hr::hr.Identity expiry date") }}  <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="date" id="identity_expiry_date" name="identity_expiry_date" class="form-control" value="{{ old('identity_expiry_date', $sourceAdmin->identity_expiry_date ?? '') }}" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="country_id" class="form-label"> {{ __("hr::hr.nationality") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <select id="country_id" name="country_id" class="form-control employee-country-select" data-validation="required" data-placeholder="{{ __("hr::hr.Search by nationality or country") }}" style="width: 100%;">
            <option value="">{{ __("hr::hr.Search by nationality or country") }}</option>
            @foreach ($hrCountries as $country)
                @php
                    $countryLabel = trim((string) ($country->name ?? '')) ?: __("hr::hr.Countries") . ' #' . $country->id;
                @endphp
                <option value="{{ $country->id }}" @if ((string) old('country_id') === (string) $country->id) selected @endif>{{ $countryLabel }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-2">{{ __("hr::hr.Nationality search hint") }}</small>
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Email") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="email" class="form-control" value="{{ old('email', $sourceAdmin->email ?? '') }}" data-validation="required" onchange="checkOther()">
    </div>

    @if(!empty($sourceAdmin))
        <div class="col-md-6 col-12 mt-5">
            <span class="badge bg-success">
                <i class="fa fa-link"></i>
                مرتبط بحساب المستخدم: {{ $sourceAdmin->name }}
            </span>
            <input type="hidden" name="create_admin_account" id="createAdminAccountInput" value="0">
        </div>
    @else
        <div class="col-md-6 col-12 mt-5">
            <label class="form-check-label">
                <input type="checkbox" id="createAdminToggle" class="form-check-input">
                {{ __("hr::hr.Create admin account") }}
                <input type="hidden" name="create_admin_account" id="createAdminAccountInput" value="0">
            </label>
        </div>
    @endif

    <div class="col-md-6 col-12" id="passwordField" style="display: none;">
        <label for="TextInput" class="form-label"> {{ __("crm.password") }} </label>
        <input type="password" name="password" class="form-control">
    </div>


    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.image") }}</label>
        <input type="file"  name="image" class="dropify">
    </div>

</div>
