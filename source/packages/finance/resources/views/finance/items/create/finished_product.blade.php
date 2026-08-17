<div class="row">
    <div class="col-md-3">
        <label for="tax_rate" class="form-label">{{ __("finance::finance.min_order") }}</label>
        <input type="number" name="min_order" value="" id="min_order" class="form-control" data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="sale_unit_id" class="form-label">{{ __("finance::finance.sale_unit") }}:</label>
        {!! addUpButton(route('finance.units.create'),'sale_unit_id','' ,'title',true) !!}
        <select name="sale_unit_id" id="sale_unit_id" class="form-control" data-validation="required">
            <option value="">{{ __("finance::finance.choose") }}</option>
            @foreach ($units as $item)
                <option value="{{ $item->id }}">
                    {{ $item->title }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="stock_account_id" class="form-label">{{ __("finance::finance.stock_account") }}:</label>
        {!! addUpButton(route('finance.accounts.create'),'stock_account_id','' ,'name',true) !!}
        <select name="stock_account_id" id="stock_account_id" data-validation="required" class="form-control">
            <option value="">{{ __("finance::finance.choose") }}</option>
            @foreach ($stock_accounts as $item)
                <option value="{{ $item->id }}">
                    {{ $item->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="tax_rate" class="form-label">{{ __("finance::finance.stock_tracking") }}</label>
        <select name="stock_tracking" id="stock_tracking" class="form-control" data-validation="required">
            <option value="">{{ __("finance::finance.choose") }}</option>
            <option value="1">{{ __("finance::finance.Yes") }}</option>
            <option value="0">{{ __("finance::finance.No") }}</option>
        </select>
    </div>


    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
        <thead>
            <tr>
                <th></th>
                <th>{{ __("finance::finance.component_item") }}</th>
                <th>{{ __("finance::finance.amount") }} </th>
                <th>{{ __("finance::finance.one_purchase_price") }} </th>
                <th>{{ __("finance::finance.total_purchase_price") }} </th>
                <th>{{ __("finance::finance.actions") }}</th>
            </tr>
        </thead>
        <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
            <tr class="y-group">
                <td> </td>
                <td>
                    <select name="component_item_id[]" id="component_item_id" data-validation="required" class="form-control index-plus select2" onchange="setPurchasePrice.call(this)">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($items_finished_product as $item)
                            <option value="{{ $item->id }}" data-purchase_price="{{ $item->purchase_price }}">
                                {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="amount[]" data-validation="required" class="form-control index-plus amount-input" step="0.01" min="0" onkeyup="calculateTotal.call(this)">
                </td>
                <td>
                    <input readonly type="number" name="one_purchase_price[]" data-validation="required" class="form-control index-plus price-input" step="0.01" min="0" onkeyup="calculateTotal.call(this)">
                </td>
                <td>
                    <input type="number" name="total_purchase_price[]" data-validation="required" class="form-control index-plus total-price-input" readonly onkeyup="calculateTotal.call(this)">
                </td>
                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                    <i class="fa fa-trash-o btnRemove"></i>
                </td>
            </tr>
        </tbody>
    </table>

</div>
