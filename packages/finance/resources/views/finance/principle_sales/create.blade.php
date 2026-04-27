<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

    <div class="col-md-4">
        <label for="principle_id" class="form-label">{{ __("finance::finance.principles") }}</label>
        <select name="principle_id" id="principle_id" class="form-control select2" data-validation="required">
            <option value="">{{ __("finance::finance.choose") }}</option>
            @foreach ($principles as $principle)
                <option value="{{ $principle->id }}"
                    data-purchase_value="{{ $principle->purchase_value }}" data-financial_value="{{ $principle->total_constraint_value }}">
                    {{ $principle->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label for="principle_value" class="form-label">{{ __("finance::finance.purchase_value") }}</label>
        <input type="number" id="principle_value" readonly name="principle_value" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="financial_value" class="form-label">{{ __("finance::finance.financial_value") }}</label>
        <input type="number" id="financial_value" name="financial_value" class="form-control" data-validation="required" readonly>
    </div>

    <div class="col-md-4">
        <label for="debit_account_id" class="form-label">{{ __("finance::finance.debit_account") }}</label>
        <select name="debit_account_id" id="debit_account_id"  class="form-control select2" data-validation="required">
            {!! showSelectElement($debit_accounts,'name') !!}
        </select>
    </div>

    <div class="col-md-4">
        <label for="date" class="form-label">{{ __("finance::finance.date") }}</label>
        <input type="date" id="date" name="date" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="sales_value" class="form-label">{{ __("finance::finance.sales_value") }}</label>
        <input type="number" id="sales_value" name="sales_value" class="form-control" data-validation="required">
    </div>


    <div class="col-md-4">
        <label for="tax_setting_id" class="form-label">{{ __("finance::finance.tax_type") }}</label>
        <select id="tax_setting_id" name="tax_setting_id" class="form-control vehicles" >
            <option value="">@lang('finance::finance.choose')</option>
            @foreach($tax_settings as $tax_setting)
                <option data-value="{{ $tax_setting->value }}" data-account-id="{{ $tax_setting->account_id }}" value="{{ $tax_setting->id }}">{{ $tax_setting->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="tax_setting_value" id="tax_setting_value" value="0">
        <input type="hidden" name="tax_account_id" id="tax_account_id" value="">
    </div>

    <div class="col-md-4">
        <label class="form-label">@lang('finance::finance.tax_charge')</label>
        <input type="number" name="total_tax_value" id="total_tax_value" value="0" class="form-control sum" readonly min="0" step="0.01">
    </div>


    <div class="col-md-4">
        <label for="total_price" class="form-label">{{ __("finance::finance.total_sales_price") }}</label>
        <input type="number" id="total_price" readonly name="total_price" class="form-control" data-validation="required" readonly>
    </div>

    <div class="col-md-12">
        <label for="notes" class="form-label">{{ __("finance::finance.notes") }}</label>
        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
    </div>


    <div class="col-12 modal-footer">
         {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
