@extends('click.layout.indexs.index')
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <form class="row g-2 from-submit-global" method="post" action="{{ $storeRoute }}">
        @if (isset($items) && count($items))
            <div class="col-md-4 col-12">
                <label>{{ __('finance::finance.code') }}</label>
                <input type="text" class="form-control" name="code" value="{{ $lastCode }}" disabled>
                <input type="hidden" name="code" value="{{ $lastCode }}">
            </div>

            <div class="col-md-4 col-12">
                <label>
                    {{ __('finance::finance.the_stock') }}
                    {!! addUpButton(route('finance.stocks.create'), 'stockk_id', '', 'name', true) !!}
                </label>
                <select class="form-control select2" name="stock_id" id="stockk_id" data-validation="required">
                    {!! showSelectElement($stocks, 'name') !!}
                </select>
            </div>

            <div class="col-md-4 col-12">
                <label>{{ __('finance::finance.date') }}</label>
                <input type="date" name="date" data-validation="required" class="form-control"
                    value="{{ date('Y-m-d') }}" placeholder="{{ __('finance::finance.date') }}">
            </div>

            <div class="col-md-12 col-12">
                <label>{{ __('finance::finance.notes') }}</label>
                <textarea name="note" class="form-control form-control-lg mb-1">لا يوجد</textarea>
            </div>
            <table id="myDataTable_no_filter" class="table align-middle card-table my-2">
                <thead>
                    <tr>
                        <th></th>
                        {{--                <th>{{ __("finance::finance.code") }}</th> --}}
                        <th>{{ __('finance::finance.items') }}</th>
                        <th>{{ __('finance::finance.unit') }}</th>
                        <th>{{ __('finance::finance.available_amount') }}</th>

                        {{--                    @if ($stock_setting->show_price_in_additional_permit) --}}
                        <th>{{ __('finance::finance.sale price') }}</th>
                        {{--                    @endif --}}

                        <th>{{ __('finance::finance.amount') }}</th>

                        {{--                    @if ($stock_setting->show_price_in_additional_permit) --}}
                        <th>{{ __('finance::finance.total') }}</th>
                        {{--                    @endif --}}

                        <th>{{ __('finance::finance.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="json_array" class="content"
                    data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                    <tr class="y-group">
                        <td></td>
                        {{--                <td> --}}
                        {{--                    <input type="number" class="form-control codeSearch" readonly> --}}
                        {{--                </td> --}}
                        <td>
                            <select name="item_id[]" id="item_id" data-validation="required" class="form-control select2">
                                <option value="">{{ __('finance::finance.choose') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                                        data-sale-price="{{ $item->sale_price }}"
                                        data-purchase-price="{{ $item->purchase_price }}"
                                        data-unit="{{ @$item->purchaseUnit->title }}">
                                        {{ $item->code_string . $item->code . ' | ' . $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <input type="text" class="form-control itemUnit" readonly>
                        </td>

                        <td style="max-width: 100px">
                            <input type="text" class="form-control availableAmount" readonly>
                        </td>

                        {{--                    @if ($stock_setting->show_price_in_additional_permit) --}}
                        <td>
                            <input type="text" name="one_price[]" class="form-control itemSalePrice" readonly>
                            <input type="hidden" name="purchase_price[]" class="form-control itemPurchasePrice" readonly>
                        </td>
                        {{--                    @else --}}
                        {{--                        <input type="hidden" name="one_price[]" class="form-control itemSalePrice" readonly> --}}
                        {{--                    @endif --}}

                        <td>
                            <input type="number" name="amount[]" data-validation="required" min="0"
                                class="form-control plus-index total-price-input">
                        </td>

                        {{--                    @if ($stock_setting->show_price_in_additional_permit) --}}
                        <td>
                            <input type="number" name="price[]" class="form-control totalItemPrice" readonly>
                            <input type="hidden" name="total_price[]" class="form-control totalItemPurchasePrice" readonly>
                        </td>
                        {{--                    @else --}}
                        {{--                        <!-- Hidden input for totalItemPrice when price is not shown --> --}}
                        {{--                        <input type="hidden" name="price[]" class="totalItemPrice"> --}}
                        {{--                    @endif --}}

                        <td>
                            <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                            <i class="fa fa-trash-o btnRemove"></i>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="col-md-2 col-12">
                <label>{{ __('hr.total_price') }}</label>
                <input type="number" class="form-control mt-2" id="totalPriceSum" name="total_item" value=""
                    readonly>
            </div>
        @else
            <div class="text-center">
                <p class="text-danger fs-5 d-inline-flex">{{ __('finance::finance.no items available') }}</p>
                <a class="d-inline-flex btn btn-primary" href="{{ route('finance.items.index') }}">اضف الان</a>
            </div>
        @endif

        <div class="col-12 modal-footer">
            {!! submitButton($storeRoute) !!}
        </div>
    </form>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#json_array').multifield();

            select2AfterPlus()

            $(document).on('click', '#json_array .btnRemove', function() {
                // Delay the calculation of the total sum to ensure the row is removed first
                setTimeout(function() {
                    calculateTotalSum();
                }, 200);
            });

            // Function to calculate and update the total price
            function updateTotalPrice(row) {
                let amount = parseFloat(row.find('input[name="amount[]"]').val()) || 0;
                let price = parseFloat(row.find('.itemSalePrice').val()) || 0;
                let totalPrice = amount * price;

                let purchase_price = parseFloat(row.find('.itemPurchasePrice').val()) || 0;
                let totalPurchasePrice = amount * purchase_price;

                // Update totalItemPrice whether it's hidden or visible
                row.find('.totalItemPrice').val(totalPrice.toFixed(2)); // Set the total price with 2 decimal places
                row.find('.totalItemPurchasePrice').val(totalPurchasePrice.toFixed(
                2)); // Set the total price with 2 decimal places

                // Update the sum of all prices
                calculateTotalSum();
            }

            // When an item is selected, update the code input and price fields
            $('#json_array').on('change', 'select[name="item_id[]"]', function() {
                let stockId = $('#stockk_id').val();
                console.log(stockId);

                if (stockId === '') {
                    validToster('{{ __('auth.an error occurred') }}',
                        "{{ __('finance::finance.Please specify the exchange store') }}");
                } else {
                    let selectedOption = $(this).find('option:selected');
                    let itemCode = selectedOption.data('code');
                    let itemUnit = selectedOption.data('unit');
                    let itemSalePrice = selectedOption.data('sale-price');
                    let itemPurchasePrice = selectedOption.data('purchase-price');

                    // Find the corresponding row
                    let row = $(this).closest('tr');

                    // Update the code, unit, and price fields
                    row.find('.codeSearch').val(itemCode);
                    row.find('.itemUnit').val(itemUnit);
                    row.find('.itemSalePrice').val(itemSalePrice);
                    row.find('.itemPurchasePrice').val(itemPurchasePrice);

                    // Calculate the total price
                    updateTotalPrice(row);

                    let itemId = row.find('select[name="item_id[]"]').val();

                    if (stockId && itemId) {
                        // Make an AJAX request to fetch the stock amount
                        $.ajax({
                            url: '{{ route('finance.get-item-stock-amount.index') }}',
                            method: 'GET',
                            data: {
                                stock_id: stockId,
                                item_id: itemId
                            },
                            success: function(response) {
                                if (response.amount !== undefined) {
                                    // تحديد اقصي كمية للطلب للمنتج ده بناء علي المخزون
                                    row.find('input[name="amount[]"]').attr('max', response
                                        .amount);

                                    // عرض الكمية المتاحة من المخزون للمنتج ده
                                    row.find('input.availableAmount').val(response.amount);

                                    // لو الكمية المدخلة اكبر من الحد الاقصي للكمية الموجودة للمنتج داخل المخزن اعد تعيين اجمالي الكمية الي الحد الاقصي
                                    let currentAmount = parseFloat(row.find(
                                        'input[name="amount[]"]').val()) || 0;
                                    if (currentAmount > response.amount) {
                                        row.find('input[name="amount[]"]').val(response.amount);
                                        validToster('{{ __('auth.an error occurred') }}',
                                            "{{ __('finance::finance.The amount exceeds the maximum available stock. It has been reset to the maximum value of ') }}" +
                                            response.amount);
                                    }
                                    // لو مفيش كمية للمنتج ده في المخزن ده اظهرله ان مفيش كمية
                                    if (response.amount === 0) {
                                        validToster('{{ __('auth.an error occurred') }}',
                                            "{{ __('finance::finance.this item not exists in this stock') }}"
                                            )
                                    }
                                    // احسب السعر الاجمالي للمنتج * الكمية
                                    updateTotalPrice(row);
                                }
                            }
                        });
                    }
                }

            });

            // لما اغير في الكمية شوف الكمية المدخلة اكبر من الحد الاقصي ولا لا ثم احسب السعر الاجمالي
            $('#json_array').on('input', 'input[name="amount[]"]', function() {
                let row = $(this).closest('tr');
                let maxAmount = parseFloat($(this).attr('max')) || 0;
                console.log(maxAmount)

                let enteredAmount = parseFloat($(this).val()) || 0;

                // Check if the entered amount exceeds the max
                if (enteredAmount > maxAmount) {
                    maxAmount === 0 ? validToster('{{ __('auth.an error occurred') }}',
                            "{{ __('finance::finance.this item not exists in this stock') }}") :
                        validToster('{{ __('auth.an error occurred') }}',
                            "{{ __('finance::finance.The amount exceeds the maximum allowed value of ') }}" +
                            maxAmount);
                    $(this).val(maxAmount);
                }
                // Calculate the total price
                updateTotalPrice(row);
            });

            // لو غير المخزن فضي كل الجدول علشان يتم فحص كميات المنتجات
            $('#stockk_id').on('change', function() {
                $('#json_array').find('tr:gt(0)').remove();
                let firstRow = $('#json_array').find('tr').first();
                firstRow.find('select[name="item_id[]"]').val('').trigger('change');
                firstRow.find('input').val('');
            });

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
        });
    </script>
@endsection
