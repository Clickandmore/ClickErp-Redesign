<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")

    <div class="col-md-2">
        <label for="code" class="form-label">{{ __("finance::finance.Voucher Number") }}</label>
        <input type="text" value="{{$obj->code}}" id="code" name="code" class="form-control" data-validation="required" readonly >
    </div>
    <div class="col-md-3">
        <label for="date" class="form-label">{{ __("finance::finance.Voucher Date") }}</label>
        <input type="date" name="date" id="date" value="{{$obj->date}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-2">
        <label for="bond_book_num" class="form-label">{{ __("finance::finance.Bookkeeping Number") }}</label>
        <input type="text" name="bond_book_num" id="bond_book_num"  value="{{$obj->bond_book_num}}" class="form-control">
    </div>

    <div class="col-md-2">
       <label for="value" class="form-label"> {{ __("finance::finance.Amount") }} </label>
        <input type="number"  min="0.01" step="any" name="value" id="value" class="form-control" value="{{$obj->value}}" data-validation="required" oninput="calculateTax()">
    </div>
    <!-- New Tax Fields -->
    <div class="col-md-3">
        <label for="is_tax" class="form-label">{{ __("finance::finance.Is there a tax?") }}:</label>
        <div>
            <input type="radio" id="is_tax_yes" value="1" name="is_tax" {{ $obj->is_tax == 1 ? "checked" : "" }} onchange="toggleTaxFields()"> {{ __("finance::finance.Yes") }}
            <input type="radio" id="is_tax_no" value="0" name="is_tax" {{ $obj->is_tax == 0 ? "checked" : "" }} onchange="toggleTaxFields()"> {{ __("finance::finance.No") }}
        </div>
    </div>
    <div id="tax-fields" @if($obj->is_tax == 0) style="display: none;" @endif  class="col-12">
        <div class="row">
            <div class="col-md-4">
                <label for="tax_setting_id" class="form-label">{{ __("finance::finance.Tax Account") }}:</label>
                <select name="tax_setting_id" id="tax_setting_id" class="form-control">
                    <option value="">{{ __("finance::finance.choose") }}</option>
                    @foreach ($tax_settings as $item)
                        <option value="{{ $item->id }}" data-value="{{ $item->value }}" data-account-id="{{ $item->account_id }}"
                            {{ ($obj->is_tax == 0 || !isset($obj->bondTax)) ? ($tax_rate_expenditure->value == $item->id ? "selected" : "") : ($obj->bondTax->tax_setting_id == $item->id ? "selected" : "") }}>
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
                <input type="number" name="tax_amount" value="{{ $obj->tax_amount }}" id="tax_amount" class="form-control" readonly>
            </div>
            <div class="col-md-4">
                 <label for="net" class="form-label"> {{ __("finance::finance.net") }} :</label>
                <input type="hidden" name="net" value="{{ $obj->net }}" id="net" class="form-control" readonly>
            </div>
        </div>
    </div>

    <div class="col-4">
        <label for="debit_account_id" class="form-label"> {{ __("finance::finance.Payee") }} </label>
        <input type="text" name="debit_account_id" value="{{ isset($bond_sarf_account) && $bond_sarf_account->value ? $bond_sarf_account->value : null }}">
        <input type="hidden" name="general_type" value="1">
        <textarea class="form-control" name="account_text" id="account_text" cols="15" rows="5" data-validation="required">
            {{ $obj->account_text }}
        </textarea>
    </div>


    <div class="col-4">
        <label for="cost_center_id" class="form-label"> {{ __("finance::finance.fn-cost-centers") }} </label>
        <select class="form-control show-tick ms select2" name="cost_center_id" id="cost_center_id"
        @if (isset($cost_center_required) && $cost_center_required->value == "on")
            data-validation="required"
        @endif>
            {!! showSelectElement($costCenters,"title",$obj->cost_center_id) !!}
        </select>
        <input type="hidden" name="cost_center_required" value="{{ (isset($cost_center_required) && $cost_center_required->value == "on") ? "on" : "off" }}">

    </div>

    <div class="col-4">
        <label for="payment_type" class="form-label">{{ __("finance::finance.Payment Method") }}</label>
        <input type="hidden" name="payment_type_old" value="{{$obj->payment_type}}">
        <select class="form-control" name="payment_type" data-validation="required" id="payment_type" onchange="getPaymentTypeHtml('sarf');"
                data-validation="required">
            {!! getEnumData($bond_payment_type ,$obj->payment_type) !!}
        </select>

    </div>

    <div class="col-12  row g-3" id="content-div">
        @include($edit_payment_type_view["view"],["accounts"=>$edit_payment_type_view["accounts"],"obj"=>$obj])
    </div>

    <div class="col-6">
        <label for="bs_maxlength_textarea" class="form-label">{{ __("finance::finance.statement") }} </label>
        <textarea type="text" data-validation="required" name="statement" class="form-control form-control-lg mb-1" id="bs_maxlength_textarea"
                  rows="5">{{$obj->statement}}</textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("finance::finance.Document Image") }}</label>
        <input type="file" name="file" class="dropify" data-default-file="{{showFile($obj->file)}}">
    </div>



    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
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
