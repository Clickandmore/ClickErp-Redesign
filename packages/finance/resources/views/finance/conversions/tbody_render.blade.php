
<table id="myDataTable_no_filter" class="table align-middle card-table my-2">
    <thead>
    <tr>
        <th>{{ __("finance::finance.product_code") }}</th>
        @if ($show_price_in_conversions == 1)
            <th>{{ __("finance::finance.purchase_price") }} </th>
        @endif
        <th>{{ __("finance::finance.available_amount") }} </th>
        @if ($show_price_in_conversions == 1)
            <th>{{ __("finance::finance.price") }} </th>
        @endif
        <th>{{ __("finance::finance.transfer_amount") }} </th>
        <th>{{ __("finance::finance.notes") }} </th>
        <th>{{ __("finance::finance.actions") }}</th>
    </tr>
    </thead>
    <tbody id="json_array" class="content"
           data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
           <tr class="y-group">
            <td>
                <select name="items[0][item_id]" id="item_id" class="form-control index-plus item-select"
                    data-validation="required">
                    <option value="">{{ __("finance::finance.choose") }}</option>
                    @if ($items->count() > 0)
                        @foreach ($items as $item)
                            @php
                                $total_current_amount = $item->itemStockStatus->sum('amount');
                                $total_price = $item->itemStockStatus->sum('price');
                            @endphp
                            <option value="{{ $item->id }}"
                                    data-purchasePrice="{{ $item->purchase_price }}"
                                    data-price="{{ $total_price }}"
                                    data-availableAmount="{{ $total_current_amount }}"
                                    {{ $item->id == @$conversionItem->item_id ? 'selected' : '' }}>
                                {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                            </option>
                        @endforeach
                    @else
                        <option value="">{{ __("finance::finance.No Data Available") }}</option>
                    @endif
                </select>
            </td>
            @if ($show_price_in_conversions == 1)
                <td>
                    <input type="number" step="0.01" readonly name="items[0][purchase_price]" class="form-control index-plus purchase-price-field" data-validation="required">
                </td>
            @endif
            <td>
                <input type="number" step="0.01" readonly name="items[0][available_amount]" class="form-control index-plus available-amount-field" data-validation="required">
            </td>
            @if ($show_price_in_conversions == 1)
                <td>
                    <input type="number" step="0.01" readonly name="items[0][price]" class="form-control index-plus price-field" data-validation="required">
                </td>
            @endif
            <td>
                <input type="number" step="0.01" name="items[0][transfer_amount]" class="form-control index-plus transfer-amount-field"
                    data-validation="required custom">
            </td>
            <td>
                <textarea name="items[0][notes]" id="notes" class="form-control index-plus" cols="15" rows="4"></textarea>
            </td>
            <td>
                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                <i class="fa fa-trash-o btnRemove"></i>
            </td>
        </tr>
    </tbody>
</table>


{{-- <script>
    $(document).ready(function () {
        $('#json_array').multifield();
    })
</script> --}}
