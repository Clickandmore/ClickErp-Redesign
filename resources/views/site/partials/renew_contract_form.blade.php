<form id="form-renew-contract" method="post" action="{{ route('site.renew-contract.store') }}"
    class="row needs-validation" novalidate>
    @csrf
    <div class="col-md-12">
        <h5 class="mt-4">{{ __("ingaz.customer_data") }}</h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.client_name") }} *</label>
        <input type="text" class="form-control" name="client_name" placeholder="" />
    </div>

    <div class="col-md-6 mb-3">
        <div class="mb-3">
            <label class="form-label">{{ __("ingaz.client_phone") }} *</label>
            <input type="tel" class="form-control" name="client_phone" />
            <div class="invalid-feedback">{{ __("ingaz.this_field_is_required") }}</div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.client_address") }} *</label>
        <input type="text" class="form-control" name="client_address" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.city") }} *</label>
        <input type="text" class="form-control" name="city" />
    </div>

    <div class="col-md-12">
        <h5 class="mt-4">{{ __("ingaz.worker_data") }}</h5>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_name") }}</label>
        <input type="text" class="form-control" name="worker_name" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_phone") }}</label>
        <input type="tel" class="form-control" name="worker_phone" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.passport_number") }}</label>
        <input type="text" class="form-control" name="passport_number" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_address") }}</label>
        <input type="text" class="form-control" name="worker_address" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.name_of_relative") }}</label>
        <input type="text" class="form-control" name="name_of_relative" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.address_of_relative") }}</label>
        <input type="text" class="form-control" name="address_of_relative" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.phone_of_relative") }}</label>
        <input type="text" class="form-control" name="phone_of_relative" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_nationality") }}</label>
        <select class="form-control" name="nationality_id">
            <option disabled selected>{{ __("ingaz.please_select_an_option") }}</option>
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}">{{ $nationality->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_new_salary") }}</label>
        <input type="number" class="form-control" name="worker_new_salary" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_old_salary") }}</label>
        <input type="number" class="form-control" name="worker_old_salary" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.the_worker_was_brought_in_from") }}</label>
        <select class="form-control" name="worker_brought_from">
            <option disabled selected>{{ __("ingaz.please_select_an_option") }}</option>
            <option value="company">{{ __("ingaz.company") }}</option>
            <option value="another_office">{{ __("ingaz.another_office") }}</option>
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.social_status_of_the_worker") }}</label>
        <select class="form-control" name="worker_marital_status">
            <option disabled selected>{{ __("ingaz.please_select_an_option") }}</option>
            @foreach ($maritalStatus as $maritalState)
                <option value="{{ $maritalState->value }}">{{ \App\Enums\Office\MaritalStatusEnum::tryFrom($maritalState->value)->lang() }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12">
        <h5 class="mt-4">{{ __("ingaz.required_files") }}</h5>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.Sponsor_Civil_Status_Card") }}</label>
        <input type="file" class="form-control" name="sponsor_card" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_residence") }}</label>
        <input type="file" class="form-control" name="worker_residence" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.the_first_page_of_the_worker_passport") }}</label>
        <input type="file" class="form-control" name="first_page_of_the_worker_passport" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.travel_tickets") }}</label>
        <input type="file" class="form-control" name="travel_tickets" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.the_visa_printed_in_the_worker_passport") }}</label>
        <input type="file" class="form-control" name="visa_printed_in_the_worker_passport" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.exit_and_re_entry_visa") }}</label>
        <input type="file" class="form-control" name="exit_and_re_entry_visa" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.customer_national_address") }}</label>
        <input type="file" class="form-control" name="customer_national_address" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.worker_information_written_on_an_external_sheet") }}</label>
        <input type="file" class="form-control" name="worker_information" />
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __("ingaz.image_of_bank_transfer") }}</label>
        <input type="file" class="form-control" name="image_of_bank_transfer" />
    </div>

    <div class="col-md-12 text-center mt-4">
        {!! NoCaptcha::renderJs(app()->getLocale()) !!}
        {!! NoCaptcha::display() !!}

        @error('g-recaptcha-response')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        <button type="submit" class="defaultBtn mt-4">
            {{ __("ingaz.send") }} <i class="fas fa-paper-plane ms-2"></i>
        </button>
    </div>
</form>
