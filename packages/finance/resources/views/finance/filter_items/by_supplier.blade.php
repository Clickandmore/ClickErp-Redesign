<table id="myDataTable_no_filter" class="table align-middle card-table">
    <thead>
    <tr>
        <th></th>
        <th> {{__('finance::finance.product_code')}} </th>
        <th>{{__('finance::finance.units')}} </th>
        <th>{{__('finance::finance.current_balance')}} </th>
        <th>{{__('finance::finance.amount')}} </th>
        <th>{{__('finance::finance.price')}} </th>
        <th>{{__('finance::finance.total')}} </th>
        <th>{{__('finance::finance.actions')}} </th>
    </tr>
    </thead>
    <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
    <tr class="y-group">
        <td> </td>
        <td>
            <select class="form-control show-tick ms  index-plus item-select" name="items[0][item_id]" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($items as $item)
                    @php
                        $total_current_amount = $item->itemStockStatus->sum('amount');
                    @endphp
                    <option value="{{ $item->id }}"
                            data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"
                            data-purchasePrice="{{ $item->purchase_price }}"
                            data-currentAmount="{{ $total_current_amount }}">
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" readonly name="items[0][unit_id]" class="form-control  index-plus unit-field" data-validation="required">
        </td>
        <td>
            <input type="number" readonly name="items[0][current-amount]" class="form-control  index-plus current-amount-field" data-validation="required">
        </td>

        <td>
            <input type="number" name="items[0][amount]" class="form-control index-plus amount-field" data-validation="required" onkeyup="calculateDifference(this)">
        </td>
        <td>
            <input type="number" name="items[0][price]" class="form-control index-plus purchase-price-field" data-validation="required">
        </td>
        <td>
            <input type="number" name="items[0][total_price]" class="form-control  index-plus total-purchase-price-field" data-validation="required">
        </td>

        <td>
            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
            <i class="fa fa-trash-o btnRemove"></i>
        </td>
    </tr>
    </tbody>
</table>
<script>
    if ($('#json_array').length > 0) {
        $('#json_array').multifield();
    }
</script>
