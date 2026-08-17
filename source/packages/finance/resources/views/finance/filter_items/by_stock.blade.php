<table id="myDataTable_no_filter" class="table align-middle card-table">
    <thead>
    <tr>
        <th></th>
        <th> {{__('finance::finance.product_code')}} </th>
        <th>{{__('finance::finance.unit')}} </th>
        @if($stock_setting->show_purchase_price_in_first_stocks)
            <th>{{__('finance::finance.purchase price')}} </th>
        @endif
        <th>{{__('finance::finance.amount')}} </th>
        <th>{{__('finance::finance.total')}} </th>
        <th>{{__('finance::finance.actions')}} </th>
    </tr>
    </thead>
    <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
    <tr class="y-group">
        <td> </td>
        <td>
            <select class="form-control show-tick ms  index-plus item-select select2" name="items[0][item_id]" data-validation="required">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @forelse ($items as $item)
                    @php
                        $total_current_amount = $item->itemStockStatus->sum('amount');
                    @endphp
                    <option value="{{ $item->id }}"
                            data-purchaseUnit="{{ $item->purchaseUnit->title ?? '' }}"
                            data-purchasePrice="{{ $item->purchase_price }}"
                            data-currentAmount="{{ $total_current_amount }}">

                        {{ $item->code_string.$item->code . ' - ' . $item->name }}
                    </option>
                @empty
                    <option value="">{{ __("finance::finance.all_items_added") }}</option>
                @endforelse
            </select>
        </td>
        <td>
            <input type="text" readonly name="items[0][unit_id]" class="form-control  index-plus unit-field" >
        </td>
        @if($stock_setting->show_purchase_price_in_first_stocks)
            <td>
                <input type="number" name="items[0][price]" class="form-control index-plus purchase-price-field" >
            </td>
        @else
            <input type="hidden" name="items[0][price]" class="form-control index-plus purchase-price-field">
        @endif

        <td>
            <input type="number" name="items[0][amount]" class="form-control index-plus amount-field" data-validation="required" onkeyup="calculateDifference(this)">
        </td>

        <td>
            <input type="number" readonly name="items[0][total_price]" class="form-control  index-plus total-purchase-price-field" data-validation="required">
        </td>

        <td>
            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
            <i class="fa fa-trash-o btnRemove"></i>
        </td>
    </tr>
    </tbody>
</table>


<script>
    select2AfterPlus();
    $('.select2').select2();
    if ($('#json_array').length > 0) {
        $('#json_array').multifield();
    }
</script>
