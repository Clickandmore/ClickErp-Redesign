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
            <input type="date" name="date" data-validation="required" class="form-control"
                   value="{{ date('Y-m-d') }}"
                   placeholder="{{ __('finance::finance.date') }}">
        </div>

        @if($stock_setting->multiple_stocks_in_bill_sales == 0)
            <div class="col-md-3 col-12">
                <label>
                    {{ __('finance::finance.the_stock') }}
                </label>
                <select class="form-control select2" name="main_stock_id" id="stockk_id" data-validation="required">
                    {!! showSelectElement($stocks,"name") !!}
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
                {!! addUpButton(route('finance.clients.create'),'client_id','','name',true) !!}
            </label>
            <select class="form-control select2" name="client_id" id="client_id" data-validation="required">
                <option value=""> {{ __('finance::finance.choose') }} </option>
                @foreach($clients as $client)
                    <option value="{{$client->id}}" data-client_balance="0"
                            data-tax_number="{{$client->tax_number}}">{{$client->name}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>{{ __('finance::finance.current_balance') }}</label>
            <input type="text" disabled class="form-control" name="client_balance" value="اختر العميل اولا">
        </div>

        <div class="col-md-4 col-12 mt-3 mb-3">
            <label>{{ __('finance::finance.tax_number') }}</label>
            <input type="text" disabled class="form-control" id="client_tax_number" value="اختر العميل اولا">
        </div>

        <div class="col-md-12 col-12 mb-3">
            <label>{{ __('finance::finance.notes') }}</label>
            <textarea rows="4" class="form-control" name="notes">-</textarea>
        </div>

        <table id="myDataTable_no_filter mt-5" class="table align-middle card-table my-2">
            <thead>
            <tr>
                <th></th>
                <th>{{ __("finance::finance.items") }}</th>
                @if($stock_setting->multiple_stocks_in_bill_sales != 0)
                    <th>{{ __("finance::finance.the_stock") }}</th>
                @endif
                <th>{{ __("finance::finance.unit") }}</th>
                <th>{{ __("finance::finance.available_amount") }}</th>
                <th>{{ __("finance::finance.price") }}</th>
                <th>{{ __("finance::finance.amount") }}</th>
                <th>{{ __("finance::finance.discount_per") }} </th>
                <th>{{ __("finance::finance.Tax rate") }} </th>
                <th>{{ __("finance::finance.total") }}</th>
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
                            {{-- <option value="{{ $item->id }}"
                                    data-code="{{ $item->code }}"
                                    data-purchase-price="{{ $item->purchase_price }}"
                                    data-sale-price="{{ $item->sale_price }}"
                                    data-unit="{{ @$item->purchaseUnit->title }}">
                                {{ $item->code_string .$item->code.' | '. $item->name }}
                            </option> --}}
                            <option value="{{ $item->id }}"
                                data-code="{{ $item->code }}"
                                data-purchase-price="{{ $item->purchase_price }}"
                                data-sale-price="{{ $item->sale_price }}"
                                data-unit="{{ @$item->purchaseUnit->title }}"
                                data-type="{{ $item->type }}">
                                {{ $item->code_string .$item->code.' | '. $item->name }}
                            </option>
                        @endforeach
                    </select>
                </td>

                @if($stock_setting->multiple_stocks_in_bill_sales)
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

                <td style="max-width: 100px">
                    <input type="text" class="form-control itemUnit" readonly>
                </td>

                <td style="max-width: 100px">
                    <input type="text" class="form-control availableAmount" readonly>
                </td>

                <td>
                    <input type="number" min="0.01" step="any" class="form-control itemSalePrice" name="sale_price[]">
                    <input type="hidden" class="form-control itemPurchasePrice" name="purchase_price[]">
                    <input type="hidden" class="form-control itemPurchasePrice" name="one_price[]">
                </td>


                <td>
                    <input type="number" name="amount[]" data-validation="required"
                           class="form-control plus-index total-price-input">
                </td>

                <td>
                    <input type="number" step="0.01" name="discount_per[]" class="form-control index-plus discount-per-field" value="0">
                    <input type="hidden" step="0.01" readonly name="discount_value[]" class="form-control index-plus discount-value-field"  value="0">
                </td>
                <!-- إضافة حقل الضريبة لكل عنصر -->
                <td>
                    <input type="number" step="0.01" name="tax_per[]" class="form-control index-plus tax-per-field" value="0">
                    <input type="hidden" step="0.01" readonly name="tax_value[]" class="form-control index-plus tax-value-field" value="0">
                </td>

                <td>
                    <input type="number" name="price[]" class="form-control totalItemPrice" readonly>
                    <input type="hidden" name="total_purchase_price[]" class="form-control totalPurchasePrice" readonly>
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

        <div class="col-md-2 col-12">
            <label>{{ __('hr.total_price') }}</label>
            <input type="number" class="form-control mt-2" id="totalPriceSum" name="total_item" value="" readonly>
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

            $('#client_id').on('change', function () {
                // Get the selected option
                var selectedClient = $(this).find('option:selected');

                // Get the client balance and tax number from the selected option's data attributes
                var clientBalance = selectedClient.data('client_balance');
                var clientTaxNumber = selectedClient.data('tax_number') || 'اختر العميل اولا';

                // Update the input fields
                $('input[name="client_balance"]').val(clientBalance);
                $('#client_tax_number').val(clientTaxNumber);
            });

// Function to calculate and update the total price with discount and tax
            function updateTotalPrice(row) {
                // Parse input values
                let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                let salePrice = parseFloat(row.find('.itemSalePrice').val()) || 0;
                let discountPer = parseFloat(row.find('.discount-per-field').val()) || 0;
                let taxPer = parseFloat(row.find('.tax-per-field').val()) || 0;

                // Calculate discount value
                let discountValue = (salePrice * discountPer) / 100;
                row.find('.discount-value-field').val(discountValue.toFixed(2));

                // Apply discount to sale price
                let discountedPrice = salePrice - discountValue;

                // Calculate total price after discount
                let totalPriceAfterDiscount = amount * discountedPrice;

                // Calculate tax value
                let taxValue = (totalPriceAfterDiscount * taxPer) / 100;
                row.find('.tax-value-field').val(taxValue.toFixed(2));

                // Calculate total price including tax
                let totalPrice = totalPriceAfterDiscount + taxValue;

                // Parse purchase price and calculate total purchase price
                let purchasePrice = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
                let totalPurchasePrice = amount * purchasePrice;

                // Update totalItemPrice and totalPurchasePrice
                row.find('.totalItemPrice').val(totalPrice.toFixed(2));
                row.find('.totalPurchasePrice').val(totalPurchasePrice.toFixed(2));

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
            $('#json_array').on('change', 'select[name="stock_id[]"], select[name="item_id[]"]', function () {
                let row = $(this).closest('tr');
                let stockId = row.find('select[name="stock_id[]"]').val() ?? $('#stockk_id').val();
                let itemId = row.find('select[name="item_id[]"]').val();
                let itemSelect = row.find('select[name="item_id[]"]');
                let itemType = itemSelect.find('option:selected').data('type');

                // لو النوع خدمة، تجاهل التحقق من الكمية والمخزن
                if (itemType === 'service') {
                    row.find('input[name="amount[]"]').removeAttr('max');
                    row.find('input.availableAmount').val('0');
                    row.find('input[name="amount[]"]').val('1');
                    updateTotalPrice(row);
                    return;
                }
                if (stockId === '') {
                    validToster('{{__("auth.an error occurred")}}', "{{__('finance::finance.Please specify the exchange store')}}");
                }

                if (stockId && itemId) {
                    // Make an AJAX request to fetch the stock amount
                    $.ajax({
                        url: '{{ route("finance.get-item-stock-amount.index") }}',
                        method: 'GET',
                        data: {
                            stock_id: stockId,
                            item_id: itemId
                        },
                        success: function (response) {
                            if (response.amount !== undefined) {
                                // تحديد اقصي كمية للطلب للمنتج ده بناء علي المخزون
                                row.find('input[name="amount[]"]').attr('max', response.amount);

                                // عرض الكمية المتاحة من المخزون للمنتج ده
                                row.find('input.availableAmount').val(response.amount);

                                // لو الكمية المدخلة اكبر من الحد الاقصي للكمية الموجودة للمنتج داخل المخزن اعد تعيين اجمالي الكمية الي الحد الاقصي
                                let currentAmount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                                if (currentAmount > response.amount) {
                                    row.find('input[name="amount[]"]').val(response.amount);
                                    validToster('{{__("auth.an error occurred")}}', "{{__('finance::finance.The amount exceeds the maximum available stock. It has been reset to the maximum value of ')}}" + response.amount);
                                }
                                // لو مفيش كمية للمنتج ده في المخزن ده اظهرله ان مفيش كمية
                                if (response.amount === 0) {
                                    validToster('{{__("auth.an error occurred")}}', "{{__('finance::finance.this item not exists in this stock')}}")
                                }
                                // احسب السعر الاجمالي للمنتج * الكمية
                                updateTotalPrice(row);
                            }
                        }
                    });
                }
            });

            // لو غير المخزن الاساسي فضي كل الجدول علشان يتم فحص كميات المنتجات
            $('#stockk_id').on('change', function () {
                $('#json_array').find('tr:gt(0)').remove();
                let firstRow = $('#json_array').find('tr').first();
                firstRow.find('select[name="item_id[]"]').val('').trigger('change');
                firstRow.find('input').val('');
            });

            // لما اغير في الكمية شوف الكمية المدخلة اكبر من الحد الاقصي ولا لا ثم احسب السعر الاجمالي
            $('#json_array').on('input', 'input[name="amount[]"]', function () {
                let row = $(this).closest('tr');
                let itemType = row.find('select[name="item_id[]"] option:selected').data('type');

                if (itemType !== 'service') {
                    let maxAmount = parseFloat($(this).attr('max')) || 0;
                    console.log(maxAmount)

                    let enteredAmount = parseFloat($(this).val()) || 0;

                    // Check if the entered amount exceeds the max
                    if (enteredAmount > maxAmount) {
                        maxAmount === 0 ? validToster('{{__("auth.an error occurred")}}', "{{__('finance::finance.this item not exists in this stock')}}") : validToster('{{__("auth.an error occurred")}}', "{{__('finance::finance.The amount exceeds the maximum allowed value of ')}}" + maxAmount);
                        $(this).val(maxAmount);
                    }
                }

                // Update total price
                updateTotalPrice(row);
            });

            // لو عاوز يغير سعر البيع عدل السعر الاجمالي بناء علي السعر الجديد والكمية المدخلة
            $('#json_array').on('input', '.itemSalePrice', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row); // Update total price whenever the sale price is changed
            });
            // لو عاوز يغير سعر البيع عدل السعر الاجمالي بناء علي السعر الجديد والكمية المدخلة
            $('#json_array').on('input', '.itemSalePrice', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row); // Update total price whenever the sale price is changed
            });

            $('#json_array').on('input', 'input[name="discount_per[]"]', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            $('#json_array').on('input', 'input[name="tax_per[]"]', function () {
                let row = $(this).closest('tr');
                updateTotalPrice(row);
            });

            // عند حذف صف احسب الاجمالي من جديد
            $(document).on('click', '.btnRemove', function () {
                var subValue = $(this).closest('tr').find('.totalItemPrice').val();
                var currentValue = $('#totalPriceSum').val();
                $('#totalPriceSum').val((currentValue - subValue).toFixed(2));
            });
        });
    </script>
@endsection

