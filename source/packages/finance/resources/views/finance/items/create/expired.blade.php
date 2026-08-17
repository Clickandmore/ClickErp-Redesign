<div class="row">
    <div class="col-md-4">
        <label for="tax_rate" class="form-label">{{ __("finance::finance.min_order") }}</label>
        <input type="number" name="min_order" value="" id="min_order" data-validation="required" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="supplier_id" class="form-label">{{ __("finance::finance.suppliers") }}:</label>
        <select name="supplier_id" id="supplier_id" class="form-control" data-validation="required">
            <option value="">{{ __("finance::finance.choose") }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}">
                    {{ $supplier->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="sale_unit_id" class="form-label">{{ __("finance::finance.sale_unit") }}:</label>
        {!! addUpButton(route('finance::finance.units.create'),'sale_unit_id','' ,'title',true) !!}
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
        <label for="purchase_unit_id" class="form-label">{{ __("finance::finance.purchase_unit") }}:</label>
        {!! addUpButton(route('finance.units.create'),'purchase_unit_id','' ,'title',true) !!}
        <select name="purchase_unit_id" id="purchase_unit_id" class="form-control" data-validation="required">
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
    <div class="col-md-4">
        <label for="tax_rate" class="form-label">{{ __("finance::finance.stock_tracking") }}</label>
        <select name="stock_tracking" id="stock_tracking" class="form-control" data-validation="required">
            <option value="">{{ __("finance::finance.choose") }}</option>
            <option value="1">{{ __("finance::finance.Yes") }}</option>
            <option value="0">{{ __("finance::finance.No") }}</option>
        </select>
    </div>
</div>


