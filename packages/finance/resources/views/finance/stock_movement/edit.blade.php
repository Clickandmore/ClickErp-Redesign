{{--<form class="row g-3 from-submit-update-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">--}}
{{--    @csrf--}}
{{--    @method('PUT')--}}

{{--    <div class="col-md-4 col-12">--}}
{{--        <label>{{ __('finance.code') }}</label>--}}
{{--        <input type="text" class="form-control read-only-field" value="{{ $movement->code }}" readonly>--}}
{{--        <input type="hidden" name="code" value="{{ $movement->code }}">--}}
{{--    </div>--}}

{{--    <div class="col-md-4 col-12">--}}
{{--        <label>{{ __('finance.the_stock') }} {!! addUpButton(route('finance.stocks.create'),'stockk_id','','name',false) !!}</label>--}}
{{--        <input type="text" class="form-control read-only-field" value="{{ $movement->stock->name }}" readonly>--}}
{{--        <input type="hidden" name="stock_id" value="{{$movement->fn_stock_id}}">--}}
{{--    </div>--}}

{{--    <div class="col-md-4 col-12">--}}
{{--        <label>{{ __('finance.date') }}</label>--}}
{{--        <input type="date" name="date" class="form-control" value="{{ $movement->date }}">--}}
{{--    </div>--}}

{{--    <div class="col-md-12 col-12">--}}
{{--        <label>{{ __('finance.notes') }}</label>--}}
{{--        <textarea class="form-control" name="note">{{ $movement->note }}</textarea>--}}
{{--    </div>--}}

{{--    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th></th>--}}
{{--            <th>{{ __("finance.items") }}</th>--}}
{{--            <th>{{ __("finance.unit") }}</th>--}}
{{--            @if($stock_setting->show_price_in_additional_permit)--}}
{{--                <th>{{ __("finance.price") }}</th>--}}
{{--            @endif--}}
{{--            <th>{{ __("finance.amount") }}</th>--}}
{{--            @if($stock_setting->show_price_in_additional_permit)--}}
{{--                <th>{{ __("finance.total") }}</th>--}}
{{--            @endif--}}
{{--            <th>{{ __("finance.actions") }}</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tbody>--}}
{{--        @foreach ($movement->details as $item)--}}
{{--            <tr>--}}
{{--                <td></td>--}}
{{--                <td>--}}
{{--                    <input type="text" class="form-control read-only-field" value="{{ $item->item->name }}" readonly>--}}
{{--                    <input type="hidden" name="item_id[]" value="{{ $item->item_id }}">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="text" class="form-control read-only-field" value="{{ $item->item->purchaseUnit->title }}" readonly>--}}
{{--                </td>--}}
{{--                @if($stock_setting->show_price_in_additional_permit)--}}
{{--                    <td>--}}
{{--                        <input type="text" class="form-control read-only-field" value="{{ $item->price }}" readonly>--}}
{{--                    </td>--}}
{{--                @endif--}}
{{--                <input type="hidden" class="form-control read-only-field" name="one_price[]" value="{{ $item->price }}">--}}
{{--                <td>--}}
{{--                    <input type="number" class="form-control read-only-field" value="{{ $item->amount }}" readonly>--}}
{{--                    <input type="hidden" name="amount[]" value="{{ $item->amount }}">--}}
{{--                </td>--}}
{{--                @if($stock_setting->show_price_in_additional_permit)--}}
{{--                    <td>--}}
{{--                        <input type="text" class="form-control read-only-field" value="{{ $item->total_price }}" readonly>--}}
{{--                    </td>--}}
{{--                @endif--}}
{{--                <input type="hidden" class="form-control read-only-field" name="price[]" value="{{ $item->total_price }}">--}}
{{--                <td>--}}
{{--                    <i class="fa fa-lock"></i>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        @endforeach--}}
{{--        </tbody>--}}
{{--    </table>--}}

{{--    <div class="col-12 modal-footer">--}}
{{--        {!! submitButton($updateRoute) !!}--}}
{{--        {!! closeButton() !!}--}}
{{--    </div>--}}
{{--</form>--}}

{{--<style>--}}
{{--    .read-only-field {--}}
{{--        background-color: #f8f9fa;--}}
{{--        border: none;--}}
{{--        pointer-events: none;--}}
{{--    }--}}
{{--</style>--}}

{{--<script>--}}
{{--    $(document).ready(function () {--}}
{{--        // Function to calculate and update the total price--}}
{{--        function updateTotalPrice(row) {--}}
{{--            let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;--}}
{{--            let price = parseFloat(row.find('.itemPurchasePrice').val()) || 0;--}}
{{--            let totalPrice = amount * price;--}}

{{--            // Update totalItemPrice whether it's hidden or visible--}}
{{--            row.find('.totalItemPrice').val(totalPrice.toFixed(2)); // Set the total price with 2 decimal places--}}
{{--        }--}}

{{--        // When an item is selected, update the code input and price fields--}}
{{--        $('#json_array').on('change', 'select[name="item_id[]"]', function () {--}}
{{--            let selectedOption = $(this).find('option:selected');--}}
{{--            let itemCode = selectedOption.data('code');--}}
{{--            let itemUnit = selectedOption.data('unit');--}}
{{--            let itemPurchasePrice = selectedOption.data('purchase-price');--}}

{{--            // Find the corresponding row--}}
{{--            let row = $(this).closest('tr');--}}

{{--            // Update the code, unit, and price fields--}}
{{--            row.find('.codeSearch').val(itemCode);--}}
{{--            row.find('.itemUnit').val(itemUnit);--}}
{{--            row.find('.itemPurchasePrice').val(itemPurchasePrice);--}}

{{--            // Calculate the total price--}}
{{--            updateTotalPrice(row);--}}
{{--        });--}}

{{--        // When the amount is changed, calculate the total price--}}
{{--        $('#json_array').on('input', 'input[name="amount[]"]', function () {--}}
{{--            let row = $(this).closest('tr');--}}
{{--            // Calculate the total price--}}
{{--            updateTotalPrice(row);--}}
{{--        });--}}
{{--    });--}}
{{--</script>--}}


<form class="row g-3 from-submit-update-global" method="post" action="{{ $updateRoute }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="alert alert-info text-center">
        {{__("finance::finance.to control of amounts go to sarf or add")}}
    </div>
    <div class="d-flex mb-3 justify-content-center">
        {!! addHyperButton(route('finance.stock-movement.create') , __("finance::finance.Additional Permit")) !!}
        {!! addHyperButton(route('finance.sarf-movement.create') , __("finance::finance.sarf Permit")) !!}
    </div>


    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</form>
