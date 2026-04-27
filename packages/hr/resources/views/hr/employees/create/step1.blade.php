<div class="row g-2">
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="name" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Employee Code") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="code" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.the address") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="address" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.phone") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="number" name="phone" class="form-control" data-validation="required,number" onchange="checkOther()">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.date of birth") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="date" name="birthday" class="form-control" data-validation="required" onchange="checkOther()">
    </div>


    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.ID Number") }}  <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="number" name="national_num" class="form-control" data-validation="required,number" onchange="checkOther()">
    </div>


    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.nationality") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="search" class="form-control employee-select-filter mb-2" data-target="#country_id" placeholder="{{ __("hr::hr.Search by nationality or country") }}">
        <select class="form-select employee-native-select" id="country_id" name="country_id" data-validation="required" onchange="checkOther()">
            <option value="">{{ __("hr::hr.Choose") }} {{ __("hr::hr.nationality") }}</option>
            @foreach ($countries as $country)
                @php
                    $countryLabel = trim(implode(' - ', array_filter([
                        $country->nationality ?? null,
                        $country->name ?? null,
                    ]))) ?: __("hr::hr.Countries") . ' #' . $country->id;
                @endphp
                <option value="{{ $country->id }}">{{ $countryLabel }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-2">{{ __("hr::hr.Nationality search hint") }}</small>
    </div>

    <div class="col-md-6 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Email") }}   <i class="fa fa-asterisk text-danger fs-10"></i> </label>
        <input type="text" name="email" class="form-control" data-validation="required" onchange="checkOther()">
    </div>

    <div class="col-md-6 col-12 mt-5">
        <label class="form-check-label">
            <input type="checkbox" id="createAdminToggle" class="form-check-input">
            {{ __("hr::hr.Create admin account") }}
            <input type="hidden" name="create_admin_account" id="createAdminAccountInput" value="0">
        </label>
    </div>

    <div class="col-md-6 col-12" id="passwordField" style="display: none;">
        <label for="TextInput" class="form-label"> {{ __("crm.password") }} </label>
        <input type="password" name="password" class="form-control">
    </div>


    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.image") }}</label>
        <input type="file"  name="image" class="dropify">
    </div>

</div>
