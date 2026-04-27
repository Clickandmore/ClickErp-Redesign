@extends('click.layout.indexs.index')
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <form class="row g-2 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

        <div class="col-md-4 col-12">
            <label>{{ __('finance::finance.Invoice Number') }}</label>
            <input type="text" class="form-control" name="code" value="{{ $lastCode }}" disabled>
            <input type="hidden" name="code" value="{{ $lastCode }}">
        </div>

        <div class="col-md-4 col-12">
            <label>{{ __('finance::finance.supplier_bill_number') }}</label>
            <input type="text" class="form-control" name="supplier_bill_number" value="لا يوجد">
        </div>

        <div class="col-md-4 col-12">
            <label>{{ __('finance::finance.date') }}</label>
            <input type="date" name="date" data-validation="required" class="form-control"
                   value="{{ date('Y-m-d') }}"
                   placeholder="{{ __('finance::finance.date') }}">
        </div>


        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>
                {{ __('finance::finance.suppliers') }}
                {!! addUpButton(route('finance.suppliers.create'),'supplier_id','','name',true) !!}
            </label>
            <select class="form-control select2" name="supplier_id" id="supplier_id" data-validation="required">
                <option value=""> {{ __('finance::finance.choose') }} </option>
                @foreach($suppliers as $supplier)
                    <option value="{{$supplier->id}}" data-supplier_balance="0">{{$supplier->name}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>{{ __('finance::finance.current_balance') }}</label>
            <input type="text" disabled class="form-control" name="supplier_balance" value="اختر المورد اولا">
        </div>


        @if($stock_setting->multiple_stocks_in_bill_purchase == 0)
            <div class="col-md-4 col-12 mt-3 mb-3">
                <label>
                    {{ __('finance::finance.stocks') }}
                </label>
                <select class="form-control select2" name="main_stock_id" id="main_stock_id" data-validation="required">
                    <option value=""> {{ __('finance::finance.choose') }} </option>
                    @foreach($stocks as $stock)
                        <option value="{{$stock->id}}">{{$stock->name}}</option>
                    @endforeach
                </select>
            </div>
        @endif


        <table id="myDataTable_no_filter mt-5" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th></th>
                <th>{{ __("finance::finance.items") }}</th>
                @if($stock_setting->multiple_stocks_in_bill_purchase != 0)
                    <th>{{ __("finance::finance.the_stock") }}</th>
                @endif
                <th>{{ __("finance::finance.unit") }}</th>
                <th>{{ __("finance::finance.price") }}</th>
                <th>{{ __("finance::finance.amount") }}</th>
                <th>{{ __("finance::finance.discount_per") }}</th>
                <th>{{ __("finance::finance.Tax rate") }}</th>
                <th>{{ __("finance::finance.total") }}</th>
                <th>{{ __("finance::finance.notes") }}</th>
                <th>{{ __("finance::finance.actions") }}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content"
                   data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
            <tr class="y-group">
                <td></td>
                <td style="min-width: 170px;">
                    <select name="item_id[]" id="item_id" data-validation="required" class="form-control select2">
                        <option value="">{{ __("finance::finance.choose") }}</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}"
                                    data-code="{{ $item->code }}"
                                    data-purchase-price="{{ $item->purchase_price }}"
                                    data-sale-price="{{ $item->sale_price }}"
                                    data-unit="{{ @$item->purchaseUnit->title }}">
                                {{ $item->code_string .$item->code.' | '. $item->name }}
                            </option>
                        @endforeach
                    </select>
                </td>

                @if($stock_setting->multiple_stocks_in_bill_purchase)
                    <td style="min-width: 170px;">
                        <select name="stock_id[]" id="stock_id" data-validation="required" class="form-control select2">
                            <option value="">{{ __("finance::finance.choose") }}</option>
                            @foreach ($stocks as $stock)
                                <option value="{{ $stock->id }}">
                                    {{ $stock->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                @endif

                <td>
                    <input type="text" class="form-control itemUnit" readonly>
                </td>

                <td>
                    <input type="number" min="0.01" step="any" class="form-control itemPurchasePrice" name="purchase_price[]">
                    <input type="hidden" class="form-control itemPurchasePrice" name="one_price[]">
                    <input type="hidden" class="form-control itemSalePrice" name="sale_price[]">
                </td>


                <td>
                    <input type="number" name="amount[]" data-validation="required"
                           class="form-control plus-index total-price-input">
                </td>

                <td>
                    <input type="number" step="0.01" name="discount_per[]" value="0" data-validation="required"
                           class="form-control plus-index discount_per" max="100" min="0">
                    <input type="hidden" step="0.01" readonly name="discount_value[]" class="form-control index-plus discount-value-field" value="0">

                </td>

                <td>
                    <input type="number" step="0.01" name="tax_per[]" value="0" data-validation="required"
                           class="form-control plus-index tax_per" max="100" min="0">
                    <input type="hidden" step="0.01" readonly name="tax_value[]" class="form-control index-plus tax-value-field" value="0">
                </td>


                <td>
                    <input type="number" name="price[]" class="form-control totalItemPrice" readonly>
                </td>
                <td>
                    <input type="text" name="ItemNotes[]" class="form-control">
                </td>
                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                    <i class="fa fa-trash-o btnRemove"></i>
                </td>
            </tr>
            </tbody>
        </table>
        <hr>
        {{--        <div class="col-md-9 col-12">--}}

        {{--        </div>--}}
        <div class="row mb-3">
            <div class="col-md-2 col-12">
                <label>{{ __('finance::finance.total_price') }}</label>
                <input type="number" class="form-control mt-2" id="totalPriceSum" name="total_item" value="" readonly>
            </div>
        </div>


        <div class="col-md-6 col-12 mb-3">
            <label>{{ __('finance::finance.Notes') }}</label>
            <textarea rows="8" class="form-control" name="notes"></textarea>
        </div>

        <div class="col-md-6 col-12 mb-3">
            <label>{{ __('finance::finance.attachments') }}</label>
            <input type="file" name="file" class="dropify">
        </div>

        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
        </div>
    </form>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            $('#json_array').multifield();

            select2AfterPlus()

            // When main stock is selected, update all stock_id fields in the table
            $('#main_stock_id').on('change', function () {
                var selectedStockId = $(this).val();
                // Loop through all stock_id fields and set the value
                $('#json_array').find('select[name="stock_id[]"]').each(function () {
                    $(this).val(selectedStockId);
                });
            });

            $('#supplier_id').on('change', function () {
                // Get the selected option
                var selectedSupplier = $(this).find('option:selected');

                // Get the client balance and tax number from the selected option's data attributes
                var clientBalance = selectedSupplier.data('supplier_balance');
                var clientTaxNumber = selectedSupplier.data('tax_number') || 'اختر المورد اولا';

                // Update the input fields
                $('input[name="supplier_balance"]').val(clientBalance);
                $('#client_tax_number').val(clientTaxNumber);
            });

// Function to calculate and update the total price including discount and tax
            function updateTotalPrice(row) {
                // Get the values from the row
                let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                let purchasePrice = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
                let discount = parseFloat(row.find('.discount_per').val()) || 0;
                let tax = parseFloat(row.find('.tax_per').val()) || 0;

                // Ensure discount is between 0 and 100
                if (discount > 100) { discount = 100; row.find('.discount_per').val(discount); }
                if (discount < 0) { discount = 0; row.find('.discount_per').val(discount); }

                // Ensure tax is between 0 and 100
                if (tax > 100) { tax = 100; row.find('.tax_per').val(tax); }
                if (tax < 0) { tax = 0; row.find('.tax_per').val(tax); }

                // Calculate the total amount before discount
                let totalBeforeDiscount = purchasePrice * amount;

                // Calculate the discount value
                let discountValue = totalBeforeDiscount * (discount / 100);
                row.find('.discount-value-field').val(discountValue.toFixed(2)); // Add discount value to input

                // Calculate the total amount after discount
                let totalAfterDiscount = totalBeforeDiscount - discountValue;

                // Calculate the tax based on the total amount after discount
                let taxValue = totalAfterDiscount * (tax / 100);
                row.find('.tax-value-field').val(taxValue.toFixed(2)); // Add tax value to input

                // Calculate the total price
                let totalPrice = totalAfterDiscount + taxValue;
                row.find('.totalItemPrice').val(totalPrice.toFixed(2));

                // Update the sum of all prices
                calculateTotalSum();
            }

            // Function to calculate the sum of all totalItemPrices
            function calculateTotalSum() {
                let totalSum = 0;

                // Loop through each row and sum up the totalItemPrices
                $('#json_array').find('.totalItemPrice').each(function () {
                    let price = parseFloat($(this).val()) || 0;
                    totalSum += price;
                });

                // Update the total price field
                $('#totalPriceSum').val(totalSum.toFixed(2));
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

            // When changing the amount, recalculate the total price
            $('#json_array').on('input', 'input[name="amount[]"]', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // When changing the purchase price, recalculate the total price
            $('#json_array').on('input', '.itemPurchasePrice', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // When changing the discount percentage, recalculate the total price
            $('#json_array').on('input', '.discount_per', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // When changing the tax percentage, recalculate the total price
            $('#json_array').on('input', '.tax_per', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // Recalculate total price when a row is removed
            $(document).on('click', '.btnRemove', function () {
                var subValue = $(this).closest('tr').find('.totalItemPrice').val();
                var currentValue = $('#totalPriceSum').val();
                $('#totalPriceSum').val((currentValue - subValue).toFixed(2));
            });
        });
    </script>

@endsection

