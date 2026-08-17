<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <div class="col-md-2">
        <label for="code" class="form-label">{{ __("finance::finance.Voucher Number") }}</label>
        <input type="text" value="{{$lastId}}" name="code" class="form-control" data-validation="required" readonly>
    </div>
    <div class="col-md-3">
        <label for="date" class="form-label">{{ __("finance::finance.Voucher Date") }}</label>
        <input type="date" name="date" id="date" class="form-control" value="{{date('Y-m-d')}}" data-validation="required">
    </div>
    <div class="col-md-2">
        <label for="bond_book_num" class="form-label">{{ __("finance::finance.Bookkeeping Number") }}</label>
        <input type="text" name="bond_book_num" id="bond_book_num" class="form-control" >
    </div>
    <div class="col-md-2">
        <label for="value" class="form-label">{{ __("finance::finance.Amount") }}</label>
        <input type="number" min="0.01" step="any" name="value" id="value" value="1" class="form-control" data-validation="required" oninput="calculateTax()">
    </div>
    <!-- New Tax Fields -->
    <div class="col-md-3">
        <label for="is_tax" class="form-label">{{ __("finance::finance.Is there a tax?") }}:</label>
        <div>
            <input type="radio" id="is_tax_yes" value="1" name="is_tax" onchange="toggleTaxFields()"> {{ __("finance::finance.Yes") }}
            <input type="radio" id="is_tax_no" value="0" name="is_tax" onchange="toggleTaxFields()" checked> {{ __("finance::finance.No") }}
        </div>
    </div>
    <div id="tax-fields" style="display: none;" class="col-12">
        <div class="row">
            <div class="col-md-4">
                <label for="tax_setting_id" class="form-label">{{ __("finance::finance.Tax Account") }}:</label>
                {!! addUpButton(route('finance.tax-setting.create'),'tax_rate_revenue' ,false, 'name') !!}
                <select name="tax_setting_id" id="tax_setting_id" class="form-control">
                    <option value="">{{ __("finance::finance.choose") }}</option>
                    @foreach ($tax_settings as $item)
                        <option value="{{ $item->id }}" data-value="{{ $item->value }}" data-account-id="{{ $item->account_id }}" {{ @$tax_rate_expenditure->value == $item->id ? "selected" : "" }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" id="tax_account_id" name="tax_account_id" value="">
            </div>
            <div class="col-md-4">
                <label for="tax_rate" class="form-label">{{ __("finance::finance.Tax rate") }}:</label>
                <input type="number" name="tax_rate" value="" readonly id="tax_rate" class="form-control" oninput="calculateTax()">
            </div>

            <div class="col-md-4">
                <label for="tax_amount" class="form-label">{{ __("finance::finance.Tax amount") }}:</label>
                <input type="number" name="tax_amount" id="tax_amount" class="form-control" readonly>
            </div>
            <div class="col-md-4">
                {{-- <label for="net" class="form-label"> {{ __("finance::finance.net") }}:</label> --}}
                <input type="hidden" name="net" id="net" class="form-control" readonly>
            </div>
        </div>
    </div>
    <div class="col-4">
        <label for="debit_account_id" class="form-label"> {{ __("finance::finance.Payee") }} </label>
        <input type="hidden" name="debit_account_id" value="{{ isset($bond_sarf_account) && $bond_sarf_account->value ? $bond_sarf_account->value : null }}">
        <input type="hidden" name="general_type" value="1">
        <textarea class="form-control" name="account_text" id="account_text" cols="15" rows="5" data-validation="required"></textarea>
    </div>
    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("finance::finance.fn-cost-centers") }} : </label>
        <label for="TextInput" class="form-label">
            {!! addUpButton(route('finance.cost-centers.create'),'cost_center_id' ,false, 'title') !!}</label>
        <select class="form-control show-tick ms select2" name="cost_center_id" id="cost_center_id"
        @if (isset($cost_center_required) && $cost_center_required->value == "on")
            data-validation="required"
        @endif>
            {!! showSelectElement($costCenters, "title") !!}
        </select>
        <input type="hidden" name="cost_center_required" value="{{ (isset($cost_center_required) && $cost_center_required->value == "on") ? "on" : "off" }}">
    </div>
    <div class="col-4">
        <label for="payment_type" class="form-label">{{ __("finance::finance.Payment Method") }}</label>
        <select class="form-control show-tick ms" name="payment_type" id="payment_type" onchange="getPaymentTypeHtml('sarf');" data-validation="required">
            {!! getEnumData($bond_payment_type, "bond_payment_type") !!}
        </select>
    </div>



    <div class="col-12 row g-3" id="content-div"></div>
    <div class="col-6">
        <label for="bs_maxlength_textarea" class="form-label">{{ __("finance::finance.statement") }}</label>
        <textarea name="statement" data-validation="required" class="form-control form-control-lg mb-1" id="bs_maxlength_textarea" rows="5"></textarea>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("finance::finance.Document Image") }}</label>
        <input type="file" name="file" class="dropify">
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>
</form>


<script>
    $(document).ready(function() {
        function setTaxRate() {
            var selectedOption = $('#tax_setting_id option:selected');
            var dataValue = selectedOption.data('value');
            var accountId = selectedOption.data('account-id');
            $('#tax_account_id').val(accountId);
            $('#tax_rate').val(dataValue);
            calculateTax();
        }

        $('#tax_setting_id').on('change', function() {
            setTaxRate();
        });
        setTaxRate();
    });
</script>
