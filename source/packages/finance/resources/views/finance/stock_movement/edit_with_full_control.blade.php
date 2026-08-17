<form class="row g-3 from-submit-update-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="col-md-4 col-12">
        <label>{{ __('finance::finance.code') }}</label>
        <input type="text" class="form-control" name="code" value="{{ $movement->code }}" disabled>
        <input type="hidden" name="code" value="{{ $movement->code }}">
    </div>

    <div class="col-md-4 col-12">
        <label>
            {{ __('finance::finance.the_stock') }}
            {!! addUpButton(route('finance.stocks.create'),'stockk_id','','name',false) !!}
        </label>
        <select class="form-control" name="stock_id" id="stockk_id" data-validation="required">
            {!! showSelectElement($stocks,"name", $movement->fn_stock_id) !!}
        </select>
    </div>

    <div class="col-md-4 col-12">
        <label>{{ __('finance::finance.date') }}</label>
        <input type="date" name="date" data-validation="required" class="form-control" value="{{ $movement->date }}">
    </div>

    <div class="col-md-12 col-12">
        <label>{{ __('finance::finance.notes') }}</label>
        <textarea name="note" class="form-control form-control-lg mb-1" placeholder="{{ __('finance::finance.notes') }}">{{ $movement->note }}</textarea>
    </div>

    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
        <thead>
        <tr>
            <th></th>
{{--            <th>{{ __("finance::finance.code") }}</th>--}}
            <th>{{ __("finance::finance.items") }}</th>
            <th>{{ __("finance::finance.unit") }}</th>

            @if($stock_setting->show_price_in_additional_permit)
                <th>{{ __("finance::finance.price") }}</th>
            @endif

            <th>{{ __("finance::finance.amount") }}</th>

            @if($stock_setting->show_price_in_additional_permit)
                <th>{{ __("finance::finance.total") }}</th>
            @endif

            <th>{{ __("finance::finance.actions") }}</th>
        </tr>
        </thead>
        <tbody id="json_array" class="content"
               data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
        @foreach ($movement->details as $item)
            <tr class="y-group">
                <td></td>
{{--                <td>--}}
{{--                    <input type="number" class="form-control codeSearch" value="{{ $item->item->code }}" readonly>--}}
{{--                </td>--}}
                <td>
                    <select name="item_id[]" id="item_id" data-validation="required" class="form-control">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($items as $availableItem)
                            <option value="{{ $availableItem->id }}"
                                    data-code="{{ $availableItem->code }}"
                                    data-purchase-price="{{ $availableItem->purchase_price }}"
                                    data-unit="{{ @$availableItem->purchaseUnit->title }}"
                                {{ $availableItem->id == $item->item_id ? 'selected' : '' }}>
                                {{ $availableItem->code_string .$availableItem->code.' | '. $availableItem->name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control itemUnit" value="{{ $item->item->purchaseUnit->title }}" readonly>
                </td>

                @if($stock_setting->show_price_in_additional_permit)
                    <td>
                        <input type="text" class="form-control itemPurchasePrice" name="one_price[]" value="{{ $item->price }}" readonly>
                    </td>
                @else
                    <input type="hidden" class="form-control itemPurchasePrice" name="one_price[]" readonly>
                @endif

                <td>
                    <input type="number" name="amount[]" value="{{ $item->amount }}" data-validation="required" class="form-control plus-index total-price-input">
                </td>

                @if($stock_setting->show_price_in_additional_permit)
                    <td>
                        <input type="number" name="price[]" class="form-control totalItemPrice" value="{{ $item->total_price }}" readonly>
                    </td>
                @else
                    <input type="hidden" name="price[]" class="totalItemPrice">
                @endif

                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                    <i class="fa fa-trash-o btnRemove"></i>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>

<script>
    $(document).ready(function () {
        // Function to calculate and update the total price
        function updateTotalPrice(row) {
            let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
            let price = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
            let totalPrice = amount * price;

            // Update totalItemPrice whether it's hidden or visible
            row.find('.totalItemPrice').val(totalPrice.toFixed(2)); // Set the total price with 2 decimal places
        }

        // When an item is selected, update the code input and price fields
        $('#json_array').on('change', 'select[name="item_id[]"]', function () {
            let selectedOption = $(this).find('option:selected');
            let itemCode = selectedOption.data('code');
            let itemUnit = selectedOption.data('unit');
            let itemPurchasePrice = selectedOption.data('purchase-price');

            // Find the corresponding row
            let row = $(this).closest('tr');

            // Update the code, unit, and price fields
            row.find('.codeSearch').val(itemCode);
            row.find('.itemUnit').val(itemUnit);
            row.find('.itemPurchasePrice').val(itemPurchasePrice);

            // Calculate the total price
            updateTotalPrice(row);
        });

        // When the amount is changed, calculate the total price
        $('#json_array').on('input', 'input[name="amount[]"]', function () {
            let row = $(this).closest('tr');
            // Calculate the total price
            updateTotalPrice(row);
        });
    });
</script>
