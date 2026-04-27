<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <!-- Registration Type -->
<div class="col-md-4">
    <label for="registration_type" class="form-label">{{ __("finance::finance.registration_type") }}</label>
    <select name="registration_type" id="registration_type" class="form-select select2" data-validation="required">
        {!! getEnumData($registration_types,$obj->registration_type) !!}
    </select>
</div>

<!-- Main Account -->
<div class="col-md-4">
    <label for="parent_account_id" class="form-label">{{ __("finance::finance.main_account") }}</label>
    <select name="parent_account_id" disabled id="parent_account_id" class="form-select select2" data-validation="required">
       {!! showSelectElement($types,'name', $obj->parent_account_id) !!}
    </select>
</div>

<!-- Code -->
<div class="col-md-4">
    <label for="code_prin" class="form-label">{{ __("finance::finance.code") }}</label>
    <input type="text" id="code_prin" name="code" disabled class="form-control" value="{{ $obj->code }}">
</div>

<!-- Name -->
<div class="col-md-4">
    <label for="name" class="form-label">{{ __("finance::finance.name") }}</label>
    <input type="text" id="name" name="name" class="form-control" data-validation="required" value="{{ $obj->name }}">
</div>

<!-- Purchase Date -->
<div class="col-md-4">
    <label for="purchase_date" class="form-label">{{ __("finance::finance.purchase_date") }}</label>
    <input type="date" id="purchase_date" name="purchase_date" class="form-control" data-validation="required" value="{{ $obj->purchase_date }}">
</div>

<!-- Service Start Date -->
<div class="col-md-4">
    <label for="service_start_date" class="form-label">{{ __("finance::finance.service_start_date") }}</label>
    <input type="date" id="service_start_date" name="service_start_date" class="form-control" data-validation="required" value="{{ $obj->service_start_date }}">
</div>

<!-- Principle Websites -->
<div class="col-md-4">
    <label for="principle_website_id" class="form-label">{{ __("finance::finance.principle-websites") }}</label>
    {!! addUpButton(route('finance.principle-websites.create'),'principle_website_id','' ,'title',false) !!}
    <select name="principle_website_id" id="principle_website_id" class="form-control select2">
        {!! showSelectElement($principle_websites,'title', $obj->principle_website_id) !!}
    </select>
</div>

<!-- Employee -->
{{--<div class="col-md-4">--}}
{{--    <label for="employee_id" class="form-label">{{ __("finance::finance.responsible_employee") }}</label>--}}
{{--    <select name="employee_id" id="employee_id" class="form-control select2">--}}
{{--        {!! showSelectElement($employees,'name', $obj->employee_id) !!}--}}
{{--    </select>--}}
{{--</div>--}}

<!-- Purchase Value -->
<div class="col-md-4" id="purchase_value_container">
    <label for="purchase_value" class="form-label">{{ __("finance::finance.purchase_value") }}</label>
    <input type="number" id="purchase_value" value="{{ $obj->purchase_value }}" name="purchase_value" class="form-control" data-validation="required">
</div>

<!-- Principle Value -->
<div class="col-md-4" id="principle_value_container">
    <label for="principle_value" class="form-label">{{ __("finance::finance.principle_value") }}</label>
    <input type="number" id="principle_value" value="{{ $obj->principle_value }}" name="principle_value" class="form-control" data-validation="required">
</div>

<!-- Creditor Account -->
<div class="col-md-4">
    <label for="creditor_account_id" class="form-label">{{ __("finance::finance.creditor_account") }}</label>
    {!! addUpButton(route('finance.accounts.create'),'creditor_account_id','' ,'name',false) !!}
    <select name="creditor_account_id" id="creditor_account_id" class="form-control select2" data-validation="required">
        {!! showSelectElement($creditor_accounts,'name', $obj->creditor_account_id) !!}
    </select>
</div>

<!-- First Tax -->
<div class="col-md-4">
    <label for="first_tax" class="form-label">{{ __("finance::finance.first_tax") }}</label>
    {!! addUpButton(route('finance.tax-setting.create'),'first_tax','' ,'name',false) !!}
    <select name="first_tax" id="first_tax" class="form-select select2">
       {!! showSelectElement($taxs,'name', $obj->first_tax) !!}
    </select>
</div>

<!-- Second Tax -->
<div class="col-md-4">
    <label for="second_tax" class="form-label">{{ __("finance::finance.second_tax") }}</label>
    {!! addUpButton(route('finance.tax-setting.create'),'second_tax','' ,'name',false) !!}
    <select name="second_tax" id="second_tax" class="form-select select2">
       {!! showSelectElement($taxs,'name', $obj->second_tax) !!}
    </select>
</div>

<!-- Hypothetical Years -->
<div class="col-md-4">
    <label for="hypothetical_years" class="form-label">{{ __("finance::finance.hypothetical_years") }}</label>
    <input type="number" name="hypothetical_years" id="hypothetical_years" class="form-control" data-validation="required" value="{{ $obj->hypothetical_years }}">
</div>

<!-- Perished Type -->
<div class="col-md-4">
    <label for="perished_type" class="form-label">{{ __("finance::finance.perished_type") }}</label>
    <select name="perished_type" id="perished_type" class="form-select select2" data-validation="required">
        {!! getEnumData($perished_types, $obj->perished_type) !!}
    </select>
</div>

<!-- Junk Value -->
<div class="col-md-4" id="junk_value_container">
    <label for="junk_value" class="form-label">{{ __("finance::finance.junk_value") }}</label>
    <div class="input-group">
        <input type="number" name="junk_value" id="junk_value" class="form-control" data-validation="required" value="{{ @$obj->principlePerished->junk_value }}">
    </div>
</div>

<!-- Perished Value -->
<div class="col-md-4" id="perished_value_container">
    <label for="perished_value" class="form-label" id="perished_value_label">{{ __("finance::finance.perished_value") }}</label>
    <div class="input-group">
        <input type="number" name="perished_value" id="perished_value" class="form-control" data-validation="required" value="{{ @$obj->principlePerished->perished_value }}">
        <span class="input-group-text" id="perished_value_unit">$</span>
    </div>
</div>
<div class="col-md-4" id="account_perished_type_container">
    <label for="account_type" class="form-label">{{ __("finance::finance.account_perished_type") }}</label>
    <select name="account_type" disabled id="account_type" class="form-select select2" data-validation="required">
        {!! getEnumData($account_perished_type, @$obj->principlePerished->account_type) !!}
    </select>
</div>

<!-- Perished Account ID -->
<div class="col-md-3" id="perished_account_id_container">
    <label for="perished_account_id" class="form-label">{{ __("finance::finance.perished_account") }}</label>
    <select name="perished_account_id" disabled id="perished_account_id" class="form-select show-tick ms select2" data-validation="required">
        {!! showSelectElement($perished_accounts,'name', @$obj->principlePerished->perished_account_id) !!}
    </select>
</div>

 <!-- Expense Account ID -->
 <div class="col-md-3" id="expense_account_id_container">
    <label for="expense_account_id" class="form-label">{{ __("finance::finance.expense_account") }}</label>
    <select name="expense_account_id" disabled id="expense_account_id" class="form-select show-tick ms select2" data-validation="required">
        {!! showSelectElement($perished_accounts,'name', @$obj->principlePerished->expense_account_id) !!}
    </select>
</div>

<!-- Perished End Date -->
<div class="col-md-3" id="perished_end_date_container">
    <label for="perished_end_date" class="form-label">{{ __("finance::finance.perished_end_date") }}</label>
    <input type="date" id="perished_end_date" name="perished_end_date" class="form-control" data-validation="required" value="{{ @$obj->principlePerished->perished_end_date }}">
</div>

<!-- Period in months -->
<div class="col-md-3" id="period_container">
    <label for="period" class="form-label">{{ __("finance::finance.Period in months") }}</label>
    <input type="number" id="period" name="period" class="form-control" data-validation="required" value="{{ @$obj->principlePerished->period }}">
</div>

    <div class="col-12">
        <label for="description" class="form-label">{{ __("finance::finance.description") }}</label>
        <textarea name="description" id="description" class="form-control" cols="30" rows="8">
            {{ $obj->description }}
        </textarea>
    </div>

    <div class="col-12">
        <div class="work_samples_grid">
            <label class="img_upload">
                <input type="file" accept="image/*" multiple name="file[]"
                    id="img-upload" onchange="handleFileSelect(event)">
                <div class="img justify-content-center">
                    <img loading="lazy" src="{{asset("admin")}}/assets/img/plus.svg" alt="plus">
                </div>
            </label>
            @if ($obj->files->count() > 0)
                @foreach ($obj->files as $image)
                    <div class="uploadedImage">
                        <img src="{{ showFile($image->file) }}" alt="">
                        <button type="button" style="border: none;background: none;color:red" class="delete deleteImagesButton"
                            delete-route="{{ route('finance.principle-files.destroy', $image->id) }}">
                            <img loading="lazy" src="{{ asset('admin') }}/assets/img/trash.svg" alt="trash">
                        </button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
