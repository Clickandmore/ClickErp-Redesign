<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">
    <div class="col-md-4">
        <label for="registration_type" class="form-label">{{ __("finance::finance.registration_type") }}</label>
        <select name="registration_type" id="registration_type" class="form-select select2" data-validation="required">
            {!! getEnumData($registration_types) !!}
        </select>
    </div>
    <div class="col-md-4">
        <label for="parent_account_id" class="form-label">{{ __("finance::finance.main_account") }}</label>
        {!! addUpButton(route('finance.accounts.create'),'parent_account_id','' ,'name',false) !!}
        <select name="parent_account_id" id="parent_account_id" class="form-select select2" data-validation="required">
           {!! showSelectElement($types,'name') !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="code_prin" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" id="code_prin" name="code" readonly class="form-control">
    </div>

    <div class="col-md-4">
        <label for="name" class="form-label">{{ __("finance::finance.name") }}</label>
        <input type="text" id="name" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="purchase_date" class="form-label">{{ __("finance::finance.purchase_date") }}</label>
        <input type="date" id="purchase_date" name="purchase_date" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="service_start_date" class="form-label">{{ __("finance::finance.service_start_date") }}</label>
        <input type="date" id="service_start_date" name="service_start_date" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="principle_website_id" class="form-label">{{ __("finance::finance.principle-websites") }}</label>
        {!! addUpButton(route('finance.principle-websites.create'),'principle_website_id','' ,'title',false) !!}
        <select name="principle_website_id" id="principle_website_id" class="form-control select2">
            {!! showSelectElement($principle_websites,'title') !!}
        </select>
    </div>

{{--    <div class="col-md-4">--}}
{{--        <label for="employee_id" class="form-label">{{ __("finance::finance.responsible_employee") }}</label>--}}
{{--        <select name="employee_id" id="employee_id" class="form-control select2">--}}
{{--            {!! showSelectElement($employees,'name') !!}--}}
{{--        </select>--}}
{{--    </div>--}}

    <!-- Purchase Value -->
    <div class="col-md-4" id="purchase_value_container">
        <label for="purchase_value" class="form-label">{{ __("finance::finance.purchase_value") }}</label>
        <input type="number" id="purchase_value" name="purchase_value" class="form-control" data-validation="required">
    </div>

    <!-- Principle Value -->
    <div class="col-md-4" id="principle_value_container">
        <label for="principle_value" class="form-label">{{ __("finance::finance.principle_value") }}</label>
        <input type="number" id="principle_value" name="principle_value" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="creditor_account_id" class="form-label">{{ __("finance::finance.creditor_account") }}</label>
        {!! addUpButton(route('finance.accounts.create'),'creditor_account_id','' ,'name',false) !!}
        <select name="creditor_account_id" id="creditor_account_id" class="form-control select2" data-validation="required">
            {!! showSelectElement($creditor_accounts,'name') !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="first_tax" class="form-label">{{ __("finance::finance.first_tax") }}</label>
        {!! addUpButton(route('finance.tax-setting.create'),'first_tax','' ,'name',false) !!}
        <select name="first_tax" id="first_tax" class="form-select select2">
           {!! showSelectElement($taxs,'name') !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="second_tax" class="form-label">{{ __("finance::finance.second_tax") }}</label>
        {!! addUpButton(route('finance.tax-setting.create'),'second_tax','' ,'name',false) !!}
        <select name="second_tax" id="second_tax" class="form-select select2">
           {!! showSelectElement($taxs,'name') !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="hypothetical_years" class="form-label">{{ __("finance::finance.hypothetical_years") }}</label>
        <input type="number" name="hypothetical_years" id="hypothetical_years" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="perished_type" class="form-label">{{ __("finance::finance.perished_type") }}</label>
        <select name="perished_type" id="perished_type" class="form-select select2" data-validation="required">
            {!! getEnumData($perished_types) !!}
        </select>
    </div>
    <!-- Junk Value -->
    <div class="col-md-4" id="junk_value_container">
        <label for="junk_value" class="form-label">{{ __("finance::finance.junk_value") }}</label>
        <div class="input-group">
            <input type="number" name="junk_value" id="junk_value" class="form-control" data-validation="required">
        </div>
    </div>

    <!-- Perished Value -->
    <div class="col-md-4" id="perished_value_container">
        <label for="perished_value" class="form-label" id="perished_value_label">{{ __("finance::finance.perished_value") }}</label>
        <div class="input-group">
            <input type="number" name="perished_value" id="perished_value" class="form-control" data-validation="required">
            <span class="input-group-text" id="perished_value_unit">$</span>
        </div>
    </div>

    <div class="col-md-4" id="account_perished_type_container">
        <label for="account_type" class="form-label">{{ __("finance::finance.account_perished_type") }}</label>
        <select name="account_type" id="account_type" class="form-select select2" data-validation="required">
            {!! getEnumData($account_perished_type) !!}
        </select>
    </div>

    <!-- Perished Account ID -->
    <div class="col-md-3" id="perished_account_id_container">
        <label for="perished_account_id" class="form-label">{{ __("finance::finance.perished_account") }}</label>
        <select name="perished_account_id" id="perished_account_id" class="form-select show-tick ms select2" data-validation="required">
            {!! showSelectElement($perished_accounts,'name') !!}
        </select>
    </div>

    <!-- Expense Account ID -->
    <div class="col-md-3" id="expense_account_id_container">
        <label for="expense_account_id" class="form-label">{{ __("finance::finance.expense_account") }}</label>
        <select name="expense_account_id" id="expense_account_id" class="form-select show-tick ms select2" data-validation="required">
            {!! showSelectElement($perished_accounts,'name') !!}
        </select>
    </div>

    <!-- Service Start Date -->
    <div class="col-md-3" id="perished_end_date_container">
        <label for="perished_end_date" class="form-label">{{ __("finance::finance.perished_end_date") }}</label>
        <input type="date" id="perished_end_date" name="perished_end_date" class="form-control" data-validation="required">
    </div>

    <!-- Period in months -->
    <div class="col-md-3" id="period_container">
        <label for="period" class="form-label">{{ __("finance::finance.Period in months") }}</label>
        <input type="number" id="period" name="period" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="description" class="form-label">{{ __("finance::finance.description") }}</label>
        <textarea name="description" id="description" class="form-control" cols="30" rows="8"></textarea>
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
        </div>
    </div>
    <div class="col-12 modal-footer">
         {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
