<div class="row g-2">
    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name") }} </label>
        <input type="text" name="name" value="{{$obj->name}}" class="form-control" data-validation="required">
        <input type="hidden" name="employeee_id" value="{{$obj->id}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Employee Code") }} </label>
        <input type="text" name="code" value="{{$obj->code}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.the address") }} </label>
        <input type="text" name="address" value="{{$obj->address}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.phone") }} </label>
        <input type="text" name="phone" value="{{$obj->phone}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.date of birth") }} </label>
        <input type="date" name="birthday" value="{{$obj->birthday}}" class="form-control" data-validation="required">
    </div>


    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.ID Number") }} </label>
        <input type="text" name="national_num" value="{{$obj->national_num}}" class="form-control"
               data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        <label for="identity_expiry_date" class="form-label">{{ __("hr::hr.Identity expiry date") }} </label>
        <input type="date" id="identity_expiry_date" name="identity_expiry_date" value="{{ $obj->identity_expiry_date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4 col-12">
        @php
            $currentCountry = collect($countries)->firstWhere('id', $obj->country_id);
            $currentCountryLabel = $currentCountry
                ? (trim((string) ($currentCountry->name ?? '')) ?: __("hr::hr.Countries") . ' #' . $currentCountry->id)
                : '';
        @endphp
        <label for="country_name" class="form-label"> {{ __("hr::hr.nationality") }} </label>
        <input type="search" id="country_name" class="form-control employee-country-input" list="countries_list" data-target="#country_id" value="{{ $currentCountryLabel }}" placeholder="{{ __("hr::hr.Search by nationality or country") }}" data-validation="required" autocomplete="off">
        <input type="hidden" id="country_id" name="country_id" value="{{ $obj->country_id }}">
        <datalist id="countries_list">
            @foreach ($countries as $country)
                @php
                    $countryLabel = trim((string) ($country->name ?? '')) ?: __("hr::hr.Countries") . ' #' . $country->id;
                @endphp
                <option value="{{ $countryLabel }}" data-id="{{ $country->id }}"></option>
            @endforeach
        </datalist>
        @if($obj->maid_id && optional($obj->maid)->nationality)
            <small class="text-muted d-block mt-2">{{ __("hr::hr.CV nationality") }}: {{ $obj->maid->nationality->sub_title ?? $obj->maid->nationality->title }}</small>
        @else
            <small class="text-muted d-block mt-2">{{ __("hr::hr.Nationality search hint") }}</small>
        @endif
    </div>

    <div class="col-md-4 col-12">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Email") }} </label>
        <input type="text" name="email" value="{{$obj->email}}" class="form-control" data-validation="required">
    </div>

    @if($obj->admin_id)
        <div class="col-md-6 col-12">
            <label for="TextInput" class="form-label"> {{ __("hr::hr.change password") }} </label>
            <input type="password" name="password" class="form-control" placeholder="**********">
        </div>
    @else
        <div class="col-md-6 col-12 mt-5">
            <label class="form-check-label">
                <input type="checkbox" id="createAdminToggle" class="form-check-input">
                {{ __("hr::hr.Create admin account") }}
                <input type="hidden" name="create_admin_account" id="createAdminAccountInput" value="0">
            </label>
        </div>
        <div class="col-md-6 col-12" id="passwordField" style="display: none;">
            <label for="TextInput" class="form-label"> {{ __("crm.password") }} </label>
            <input type="password" name="password" class="form-control" data-validation="required">
        </div>
    @endif




    <div class="col-md-12 col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.image") }}</label>
        <input type="file" data-default-file="{{showFile($obj->image)}}" name="image" class="dropify">
    </div>

</div>
