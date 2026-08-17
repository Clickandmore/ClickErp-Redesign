@extends('click.layout.indexs.index')
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <form class="row g-2 from-submit-global" method="post" action="{{ $storeRoute }}">

        <div class="col-md-3 col-12">
            <label>{{ __('finance::finance.code') }}</label>
            <input type="text" class="form-control" name="code" value="{{ $lastCode }}" disabled>
            <input type="hidden" name="code" value="{{ $lastCode }}">
        </div>

        <div class="col-md-3 col-12">
            <label>{{ __('finance::finance.date') }}</label>
            <input type="date" name="date" data-validation="required" class="form-control" value="{{ date('Y-m-d') }}"
                placeholder="{{ __('finance::finance.date') }}">
        </div>

        @if ($stock_setting->multiple_stocks_in_bill_sales == 0)
            <div class="col-md-3 col-12">
                <label>
                    {{ __('finance::finance.the_stock') }}
                </label>
                <select class="form-control select2" name="main_stock_id" id="stockk_id" data-validation="required">
                    {!! showSelectElement($stocks, 'name') !!}
                </select>
            </div>
        @endif

        <div class="col-md-3 col-12">
            <label>
                {{ __('finance::finance.payment_type') }}
            </label>
            <select class="form-control select2" name="payment_type" data-validation="required">
                <option value=""> {{ __('finance::finance.choose') }} </option>
                <option value="postpaid">دفع آجل</option>
                <option value="cash">كاش</option>
            </select>
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>
                {{ __('finance::finance.client') }}
                {!! addUpButton(route('finance::finance.clients.create'), 'client_id', '', 'name', true) !!}
            </label>
            <input type="hidden" name="client_id" class="form-control" value="{{ $salesOrder->client->id }}">
            <input type="text" disabled class="form-control" value="{{ @$salesOrder->client->name }}">
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>{{ __('finance::finance.current_balance') }}</label>
            <input type="text" disabled class="form-control" value="{{ @$salesOrder->client->client_balance ?? 0 }}"
                name="client_balance">
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>{{ __('finance::finance.tax_number') }}</label>
            <input type="text" disabled class="form-control" value="{{ @$salesOrder->client->tax_number }}"
                id="client_tax_number">
        </div>

        {{--        <div class="col-md-3 col-12 mt-3 mb-3"> --}}
        {{--            <label>{{ __('zatca.invoice_type') }}</label> --}}
        {{--            <select name=""> --}}

        {{--            </select> --}}
        {{--        </div> --}}

        <div class="col-md-12 col-12 mb-3">
            <label>{{ __('finance::finance.notes') }}</label>
            <textarea rows="4" class="form-control" name="notes">{{ $salesOrder->notes }}</textarea>
        </div>

        <table id="myDataTable_no_filter mt-5" class="table align-middle card-table my-2">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('finance::finance.items') }}</th>
                    @if ($stock_setting->multiple_stocks_in_bill_sales != 0)
                        <th>{{ __('finance::finance.the_stock') }}</th>
                    @endif
                    <th>{{ __('finance::finance.unit') }}</th>
                    <th>{{ __('finance::finance.available_amount') }}</th>
                    <th>{{ __('finance::finance.price') }}</th>
                    <th>{{ __('finance::finance.amount') }}</th>
                    <th>{{ __('finance::finance.discount_per') }} </th>
                    <th>{{ __('finance::finance.Tax rate') }} </th>
                    <th>{{ __('finance::finance.total') }}</th>
                    <th>{{ __('finance::finance.actions') }}</th>
                </tr>
            </thead>
            <tbody id="json_array" class="content"
                data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                @foreach ($salesOrder->salesOrderItems as $itemRow)
                    <tr class="y-group">
                        <td></td>
                        <td style="min-width: 170px;">
                            <select name="item_id[]" id="item_id" data-validation="required" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                        {{ $itemRow->item->id == $item->id ? 'selected' : '' }}
                                        data-code="{{ $item->code }}" data-purchase-price="{{ $item->purchase_price }}"
                                        data-sale-price="{{ $item->sale_price }}"
                                        data-unit="{{ @$item->purchaseUnit->title }}" data-type="{{ $item->type }}">
                                        {{ $item->code_string . $item->code . ' | ' . $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        @if ($stock_setting->multiple_stocks_in_bill_sales)
                            @php
                                $maxStock = $itemRow->item->maxStockAmount();
                            @endphp
                            <td style="min-width: 170px;">
                                <select name="stock_id[]" id="stock_id" data-validation="required"
                                    class="form-control select2">
                                    <option value="">{{ __('finance::finance.choose') }}</option>
                                    @foreach ($stocks as $stock)
                                        <option value="{{ $stock->id }}"
                                            {{ $maxStock && $maxStock->fn_stock_id == $stock->id ? 'selected' : '' }}>
                                            {{ $stock->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        @endif

                        <td style="max-width: 100px">
                            <input type="text" class="form-control itemUnit"
                                value="{{ @$itemRow->item->purchaseUnit->title }}" readonly>
                        </td>

                        <td style="max-width: 100px">
                            @php
                                $maxStock = $itemRow->item->maxStockAmount();
                            @endphp
                            <input type="text" class="form-control availableAmount" readonly
                                value="{{ $maxStock->max_amount ?? 0 }}">
                        </td>

                        <td>
                            <input type="text" class="form-control itemSalePrice" name="sale_price[]"
                                value="{{ @$itemRow->sales_price }}">
                            <input type="hidden" class="form-control itemPurchasePrice" name="purchase_price[]"
                                value="{{ @$itemRow->item->purchase_price }}">
                            <input type="hidden" class="form-control itemPurchasePrice" name="one_price[]"
                                value="{{ @$itemRow->item->purchase_price }}">
                        </td>


                        <td>
                            @php
                                $maxStock = $itemRow->item->maxStockAmount(); // جلب بيانات المخزن الذي يحتوي على الكمية الأكبر
                                $maxAmount = $maxStock->max_amount ?? 0; // استخدم القيمة الافتراضية 0 إذا لم تكن هناك بيانات
                                $finalAmount = min($itemRow->sales_amount, $maxAmount);
                            @endphp
                            <input type="number" name="amount[]" data-validation="required" value="{{ $finalAmount }}"
                                class="form-control plus-index total-price-input">
                        </td>

                        <td>
                            @php
                                $salePrice = @$itemRow->sales_price;
                                $purchasePrice = @$itemRow->item->purchase_price;
                                $discountPercent = $itemRow->discount_per ?? 0;
                                $taxPercent = $itemRow->tax_per ?? 0;

                                // Calculate base price
                                $basePrice = $finalAmount * $salePrice;

                                // Calculate discount and price after discount
                                $discountValue = ($basePrice * $discountPercent) / 100;
                                $priceAfterDiscount = $basePrice - $discountValue;

                                // Calculate tax
                                $taxValue = ($priceAfterDiscount * $taxPercent) / 100;

                                // Calculate final price
                                $finalPrice = $priceAfterDiscount + $taxValue;

                                // Calculate total purchase price
                                $totalPurchasePrice = $finalAmount * $purchasePrice;
                            @endphp

                            <input type="number" step="0.01" name="discount_per[]"
                                class="form-control index-plus discount-per-field" onkeyup="calculateTotal(this)"
                                value="{{ $discountPercent }}">
                            <input type="hidden" step="0.01" readonly name="discount_value[]"
                                class="form-control index-plus discount-value-field" value="{{ $discountValue }}">
                        </td>

                        <td>
                            <input type="number" step="0.01" name="tax_per[]"
                                class="form-control index-plus tax-per-field" onkeyup="calculateTotal(this)"
                                value="{{ $taxPercent }}">
                            <input type="hidden" step="0.01" readonly name="tax_value[]"
                                class="form-control index-plus tax-value-field" value="{{ $taxValue }}">
                        </td>

                        <td>
                            <input type="number" name="price[]" class="form-control totalItemPrice" readonly
                                value="{{ $finalPrice }}">
                            <input type="hidden" name="total_purchase_price[]" class="form-control totalPurchasePrice"
                                readonly value="{{ $totalPurchasePrice }}">
                        </td>
                        <td>
                            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                            <i class="fa fa-trash-o btnRemove"></i>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <hr>


        <div class="col-md-2 col-12">
            <label>{{ __('hr.total_price') }}</label>
            <input type="number" class="form-control mt-2" id="totalPriceSum" name="total_item" value="0"
                readonly>
        </div>

        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
        </div>
        <input type="hidden" value="{{ $salesOrder->id }}" name="sales_order">
    </form>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#json_array').multifield();

            function calculateTotalPriceSum() {
                let totalSum = 0;
                $('.totalItemPrice').each(function() {
                    totalSum += parseFloat($(this).val()) || 0;
                });
                $('#totalPriceSum').val(totalSum.toFixed(2));
            }

            // Initial calculation on page load
            calculateTotalPriceSum();

            select2AfterPlus()

            $('#client_id').on('change', function() {
                // Get the selected option
                var selectedClient = $(this).find('option:selected');

                // Get the client balance and tax number from the selected option's data attributes
                var clientBalance = selectedClient.data('client_balance');
                var clientTaxNumber = selectedClient.data('tax_number') || 'اختر العميل اولا';

                // Update the input fields
                $('input[name="client_balance"]').val(clientBalance);
                $('#client_tax_number').val(clientTaxNumber);
            });

            // Function to calculate and update the total price
            function updateTotalPrice(row) {
                let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                let SalePrice = parseFloat(row.find('.itemSalePrice').val()) || 0;
                // السعر الأساسي قبل الخصم
                let basePrice = amount * SalePrice;

                // حساب الخصم
                let discountPercent = parseFloat(row.find('input[name="discount_per[]"]').val()) || 0;
                let discountValue = (basePrice * discountPercent) / 100;
                row.find('input.discount-value-field').val(discountValue.toFixed(2));

                // السعر بعد الخصم
                let discountedPrice = basePrice - discountValue;

                // حساب الضريبة
                let taxPercent = parseFloat(row.find('input[name="tax_per[]"]').val()) || 0;
                let taxValue = (discountedPrice * taxPercent) / 100;
                row.find('input.tax-value-field').val(taxValue.toFixed(2));

                // السعر النهائي بعد الخصم والضريبة
                let finalPrice = discountedPrice + taxValue;

                let purchase_price = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
                let totalPurchasePrice = amount * purchase_price;

                // Update totalItemPrice whether it's hidden or visible
                row.find('.totalItemPrice').val(finalPrice.toFixed(2));
                row.find('.totalPurchasePrice').val(totalPurchasePrice.toFixed(2));

                // Update the sum of all prices
                calculateTotalSum();
            }

            // Function to calculate the sum of all totalItemPrices
            function calculateTotalSum() {
                let totalSum = 0;

                // Loop through each row and sum up the totalItemPrices
                $('#json_array').find('.totalItemPrice').each(function() {
                    let price = parseFloat($(this).val()) || 0;
                    totalSum += price;
                });

                // Update the total price field
                $('#totalPriceSum').val(totalSum.toFixed(2));
            }

            // When an item is selected, update the code input and price fields
            $('#json_array').on('change', 'select[name="item_id[]"]', function() {
                let selectedOption = $(this).find('option:selected');
                let itemCode = selectedOption.data('code');
                let itemUnit = selectedOption.data('unit');
                let itemSalePrice = selectedOption.data('sale-price');
                let itemPurchasePrice = selectedOption.data('purchase-price');

                // Find the corresponding row
                let row = $(this).closest('tr');

                // Update the code, unit, and price fields
                row.find('.codeSearch').val(itemCode);
                row.find('.itemUnit').val(itemUnit ? itemUnit : 'لا يوجد');
                row.find('.itemSalePrice').val(itemSalePrice);
                row.find('.itemPurchasePrice').val(itemPurchasePrice);

                // Calculate the total price
                updateTotalPrice(row);
            });

            // لو عدل في المخزن او المنتج شوف الكمية المتوفرة من المخزون للمنتج ده
            $('#json_array').on('change', 'select[name="stock_id[]"], select[name="item_id[]"]', function() {
                let row = $(this).closest('tr');
                let stockId = row.find('select[name="stock_id[]"]').val() ?? $('#stockk_id').val();
                let itemSelect = row.find('select[name="item_id[]"]');
                let itemId = itemSelect.val();
                let itemType = itemSelect.find('option:selected').data('type');

                if (itemType === 'service') {
                    row.find('input[name="amount[]"]').removeAttr('max');
                    row.find('input.availableAmount').val('0');
                    row.find('input[name="amount[]"]').val('1');
                    updateTotalPrice(row);
                    return;
                }

                if (stockId === '') {
                    validToster('{{ __('auth.an error occurred') }}',
                        "{{ __('finance::finance.Please specify the exchange store') }}");
                }

                if (stockId && itemId) {
                    $.ajax({
                        url: '{{ route('finance::finance.get-item-stock-amount.index') }}',
                        method: 'GET',
                        data: {
                            stock_id: stockId,
                            item_id: itemId
                        },
                        success: function(response) {
                            if (response.amount !== undefined) {
                                row.find('input[name="amount[]"]').attr('max', response.amount);
                                row.find('input.availableAmount').val(response.amount);

                                let currentAmount = parseFloat(row.find(
                                    'input[name="amount[]"]').val()) || 0;
                                if (currentAmount > response.amount) {
                                    row.find('input[name="amount[]"]').val(response.amount);
                                    validToster('{{ __('auth.an error occurred') }}',
                                        "{{ __('finance::finance.The amount exceeds the maximum available stock. It has been reset to the maximum value of ') }}" +
                                        response.amount);
                                }
                                if (response.amount === 0) {
                                    validToster('{{ __('auth.an error occurred') }}',
                                        "{{ __('finance::finance.this item not exists in this stock') }}"
                                        )
                                }

                                updateTotalPrice(row);
                            }
                        }
                    });
                }
            });


            // لو غير المخزن الاساسي فضي كل الجدول علشان يتم فحص كميات المنتجات
            $('#stockk_id').on('change', function() {
                $('#json_array').find('tr:gt(0)').remove();
                let firstRow = $('#json_array').find('tr').first();
                firstRow.find('select[name="item_id[]"]').val('').trigger('change');
                firstRow.find('input').val('');
            });

            // لما اغير في الكمية شوف الكمية المدخلة اكبر من الحد الاقصي ولا لا ثم احسب السعر الاجمالي
            $('#json_array').on('input', 'input[name="amount[]"]', function() {
                let row = $(this).closest('tr');
                let itemType = row.find('select[name="item_id[]"] option:selected').data('type');

                // تجاهل التحقق لو النوع خدمة
                if (itemType !== 'service') {
                    let maxAmount = parseFloat($(this).attr('max')) || 0;
                    let enteredAmount = parseFloat($(this).val()) || 0;

                    if (enteredAmount > maxAmount) {
                        maxAmount === 0 ?
                            validToster('{{ __('auth.an error occurred') }}',
                                "{{ __('finance::finance.this item not exists in this stock') }}") :
                            validToster('{{ __('auth.an error occurred') }}',
                                "{{ __('finance::finance.The amount exceeds the maximum allowed value of ') }}" +
                                maxAmount);
                        $(this).val(maxAmount);
                    }
                }

                updateTotalPrice(row);
            });


            // لو عاوز يغير سعر البيع عدل السعر الاجمالي بناء علي السعر الجديد والكمية المدخلة
            $('#json_array').on('input', '.itemSalePrice', function() {
                let row = $(this).closest('tr');
                updateTotalPrice(row); // Update total price whenever the sale price is changed
            });

            // استدعاء الدالة عند تغيير الخصم أو الضريبة
            $('#json_array').on('input', 'input[name="discount_per[]"], input[name="tax_per[]"]', function() {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // عند حذف صف احسب الاجمالي من جديد
            $(document).on('click', '.btnRemove', function() {
                var subValue = $(this).closest('tr').find('.totalItemPrice').val();
                var currentValue = $('#totalPriceSum').val();
                $('#totalPriceSum').val((currentValue - subValue).toFixed(2));
            });
        });
    </script>
@endsection
