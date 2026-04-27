@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="form-check form-switch">
        <label class="form-check-label" id="switchFilterLabel"
               for="switchFilter">{{__('finance::finance.advanced_search')}}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.from_date") }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.to_date") }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __("finance::finance.code") }}</label>
            <input type="text" name="code" id="code" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="">{{ __("finance::finance.clients") }}</label>
            <select name="client_id" id="client_id" class="form-control">
                {!! showSelectElement($clients,"name") !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __("finance::finance.items") }}</label>
            <select name="item_id" id="item_id" class="form-control">
                {!! showSelectElement($items,"name") !!}
            </select>
        </div>
    </div>
    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{__("finance::finance.code")}}</th>
            <th>{{__("finance::finance.date")}}</th>
            <th>{{__("finance::finance.client")}}</th>
            <th>{{__("finance::finance.final_total")}}</th>
            <th>{{__("finance::finance.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "الكل"] ],
                "processing": true,
                "responsive": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function (d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.code = $('#code').val();
                        d.client_id = $('#client_id').val();
                        d.item_id = $('#item_id').val();
                    }
                },
                "columns": [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {"data": "code", orderable: false, searchable: false},
                    {"data": "date", orderable: false, searchable: false},
                    {"data": "client_id", orderable: false, searchable: false},
                    {"data": "final_total", orderable: false, searchable: false},
                    {"data": "actions", orderable: false, searchable: false}
                ],
            });

            $('#from_date, #to_date, #client_id, #item_id').change(function () {
                table.draw();
            });
            $('#code').keyup(function () {
                table.draw();
            });
        });
    </script>
    <script>
        function calculateRow(inputElement) {
            // Get the parent row of the current input
            let row = inputElement.closest('tr');

            // Get the values from the inputs in the current row
            let amount = parseFloat(row.querySelector(`input[name*="[amount]"]`).value) || 0;
            let price = parseFloat(row.querySelector(`input[name*="[price]"]`).value) || 0;
            let discountPer = parseFloat(row.querySelector(`input[name*="[discount_per]"]`).value) || 0;
            let taxPer = parseFloat(row.querySelector(`input[name*="[tax_per]"]`).value) || 0;

            // Calculate total price
            let total = amount * price;

            // Calculate discount value
            let discountValue = (total * discountPer) / 100;

            // Calculate final price after discount
            let finalPriceBeforeTax = total - discountValue;

            // Calculate tax value
            let taxValue = (finalPriceBeforeTax * taxPer) / 100;

            // Calculate final price after tax
            let finalPrice = finalPriceBeforeTax + taxValue;

            // Set the calculated values in their respective inputs
            row.querySelector(`input[name*="[discount_value]"]`).value = discountValue.toFixed(2);
            row.querySelector(`input[name*="[tax_value]"]`).value = taxValue.toFixed(2);
            row.querySelector(`input[name*="[final_price]"]`).value = finalPrice.toFixed(2);
        }
    </script>

    <script>
        // دالة لحساب الإجماليات لكل صف
        function calculateRowAdd(inputElement) {
            // Get the parent row of the current input
            let row = inputElement.closest('tr');

            // Get the values from the inputs in the current row
            let amount = parseFloat(row.querySelector(`input[name*="[amount]"]`).value) || 0;
            let price = parseFloat(row.querySelector(`input[name*="[price]"]`).value) || 0;
            let discountPer = parseFloat(row.querySelector(`input[name*="[discount_per]"]`).value) || 0;
            let taxPer = parseFloat(row.querySelector(`input[name*="[tax_per]"]`).value) || 0;

            // Calculate total price
            let total = amount * price;

            // Calculate discount value
            let discountValue = (total * discountPer) / 100;

            // Calculate final price before tax
            let finalPriceBeforeTax = total - discountValue;

            // Calculate tax value
            let taxValue = (finalPriceBeforeTax * taxPer) / 100;

            // Calculate final price after tax
            let finalPrice = finalPriceBeforeTax + taxValue;

            // Set the calculated values in their respective inputs
            row.querySelector(`input[name*="[discount_value]"]`).value = discountValue.toFixed(2);
            row.querySelector(`input[name*="[tax_value]"]`).value = taxValue.toFixed(2);
            row.querySelector(`input[name*="[final_price]"]`).value = finalPrice.toFixed(2);
        }
    </script>

    <script>
        $(document).ajaxComplete(function() {
            // عند اختيار صنف من القائمة
            $(document).on('change', '.item-select', function () {
                var selectedOption = $(this).find('option:selected');
                var currentRow = $(this).closest('tr');

                // تحديث القيم الافتراضية عند اختيار العنصر
                updateRowValues(selectedOption, currentRow);

                // تعيين القيم الافتراضية للضريبة والكمية والخصم
                currentRow.find('.sale-amount-field').val(1); // الكمية الافتراضية
                currentRow.find('.tax-per-field').val(15);   // الضريبة الافتراضية
                currentRow.find('.discount-per-field').val(0); // الخصم الافتراضي

                // إعادة حساب الإجماليات بناءً على القيم الافتراضية
                // calculateRowAdd(currentRow.find('.sale-amount-field').get(0));
            });

            // تحديث قيم الصفوف عند اختيار صنف
            function updateRowValues(selectedOption, currentRow) {
                var saleUnit = selectedOption.data('saleunit');
                var availableAmount = selectedOption.data('availableamount');
                var salePrice = selectedOption.data('saleprice');

                // تحديث حقل الوحدة والكمية المتاحة وسعر البيع
                // currentRow.find('.unit-field').val(saleUnit);
                currentRow.find('.unit-field').val(saleUnit ? saleUnit : 'لا يوجد');
                currentRow.find('.available-amount-field').val(availableAmount);
                currentRow.find('.sale-price-field').val(salePrice);

                // تحديث الإجماليات لكل صف
                calculateTotal(currentRow.find('.sale-amount-field'));
            }

            // حساب الإجماليات
            window.calculateTotal = function(element) {
                var currentRow = $(element).closest('tr');
                var salesPrice = parseFloat(currentRow.find('.sale-price-field').val()) || 0;
                var salesAmount = parseFloat(currentRow.find('.sale-amount-field').val()) || 0;
                var discountPer = parseFloat(currentRow.find('.discount-per-field').val()) || 0;
                var taxPer = parseFloat(currentRow.find('.tax-per-field').val()) || 0;

                var totalSalesPrice = salesPrice * salesAmount;
                var discountValue = (totalSalesPrice * discountPer) / 100;
                var finalTotalBeforeTax = totalSalesPrice - discountValue;
                let taxValue = (finalTotalBeforeTax * taxPer) / 100;
                let finalTotal = finalTotalBeforeTax + taxValue;

                currentRow.find('.total-sales-price-field').val(totalSalesPrice.toFixed(2));
                currentRow.find('.discount-value-field').val(discountValue.toFixed(2));
                currentRow.find('.tax-value-field').val(taxValue.toFixed(2));
                currentRow.find('.final-total-field').val(finalTotal.toFixed(2));

                // تحديث الإجماليات الكلية لكل الطلب
                updateGlobalTotals();
            }



            // Handle input changes for recalculating totals
            $('#json_array1').on('input', '.index-plus', function () {
                calculateTotals();
            });

            // Handle row removal
            $('#json_array1').on('click', '.btnRemove', function () {
                $(this).closest('.y-group').remove();  // Remove the row
                calculateTotals();  // Recalculate totals after row removal
            });

            // Function to calculate totals
            function calculateTotals() {
                let totalPrice = 0;
                let totalDisc = 0;
                let totalTax = 0;

                $('#json_array1 .y-group').each(function () {
                    let priceField = parseFloat($(this).find('.sale-price-field-additional').val()) || 0;
                    let amountField = parseFloat($(this).find('.sale-amount-field-additional').val()) || 1;
                    let discountPercent = parseFloat($(this).find('.discount-per-field-additional').val()) || 0;
                    let taxPercent = parseFloat($(this).find('.tax-per-field-additional').val()) || 0;

                    let discountValue = (priceField * discountPercent) / 100;
                    let finalPrice = (priceField - discountValue) * amountField;
                    let taxValue = (finalPrice * taxPercent) / 100;
                    finalPrice += taxValue;

                    $(this).closest('.discount-value-field-additional').val(discountValue.toFixed(2));
                    $(this).closest('.tax-value-field-additional').val(taxValue.toFixed(2));
                    $(this).closest('.total-sales-price-field-additional').val(finalPrice.toFixed(2));

                    totalPrice += finalPrice;
                    totalDisc += discountValue * amountField;
                    totalTax  += taxValue * amountField;
                });

                $('#totalPriceSumAdditional').val(totalPrice.toFixed(2));
                $('input[name="total_discount_value_additional"]').val(totalDisc.toFixed(2));
            }

            // حساب الإجماليات
            window.calculateTotalAdditional = function(element) {
                var currentRow = $(element).closest('tr');
                var salesPrice = parseFloat(currentRow.find('.sale-price-field-additional').val()) || 0;
                var salesAmount = parseFloat(currentRow.find('.sale-amount-field-additional').val()) || 0;
                var discountPer = parseFloat(currentRow.find('.discount-per-field-additional').val()) || 0;
                var taxPer = parseFloat(currentRow.find('.tax-per-field-additional').val()) || 0;

                var totalSalesPrice = salesPrice * salesAmount;
                var discountValue = (totalSalesPrice * discountPer) / 100;
                var finalTotalBeforeTax = totalSalesPrice - discountValue;
                let taxValue = (finalTotalBeforeTax * taxPer) / 100;
                let finalTotal = finalTotalBeforeTax + taxValue;

                currentRow.find('.total-sales-price-field-additional').val(totalSalesPrice.toFixed(2));
                currentRow.find('.discount-value-field-additional').val(discountValue.toFixed(2));
                currentRow.find('.tax-value-field-additional').val(taxValue.toFixed(2));
                currentRow.find('.final-total-field-additional').val(finalTotal.toFixed(2));

                // تحديث الإجماليات الكلية لكل الطلبات
                updateGlobalTotalsAdditional();
            }

            // حساب الإجماليات الكلية لكل الطلب
            function updateGlobalTotalsAdditional() {
                var totalPrice = 0, totalDiscountValue = 0, totalTaxValue = 0, finalTotal = 0;

                // لكل صف، اجمع القيم
                $('.y-group').each(function() {
                    totalPrice += parseFloat($(this).find('.total-sales-price-field-additional').val()) || 0;
                    totalDiscountValue += parseFloat($(this).find('.discount-value-field-additional').val()) || 0;
                    totalTaxValue += parseFloat($(this).find('.tax-value-field-additional').val()) || 0;
                    finalTotal += parseFloat($(this).find('.final-total-field-additional').val()) || 0;
                });

                // تعيين القيم الإجمالية
                $('input[name="total_price"]').val(totalPrice.toFixed(2));
                $('input[name="total_discount_value_additional"]').val(totalDiscountValue.toFixed(2));
                $('input[name="total_tax_value_additional"]').val(totalTaxValue.toFixed(2));
                $('input[name="final_total"]').val(finalTotal.toFixed(2));
            }
        });
        // عند حذف صف احسب الاجمالي من جديد
        $(document).on('click', '.btnRemove', function () {
            var subValue = $(this).closest('tr').find('.total-sales-price-field').val();
            var currentValue = $('#totalPriceSum').val();
            $('#totalPriceSum').val((currentValue - subValue).toFixed(2));
        });
    </script>

    <script>
        $(document).ajaxComplete(function() {
            // Handle transfer button click
            $('.transferBtn').on('click', function () {
                let recordId = $(this).data('id');
                let url = "{{ route('finance.transfer-sales-order.store') }}"; // Route for handling the transfer action
                Swal.fire({
                    title: '{{ helperTrans("finance::finance.Are you sure?") }}',
                    text: '{{ helperTrans("finance::finance.This action will transfer the record to a sales invoice.") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ helperTrans("finance::finance.Yes, transfer it!") }}',
                    cancelButtonText: '{{ helperTrans("finance::finance.Cancel") }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Perform an AJAX POST request
                        $.ajax({
                            url: url,
                            method: 'POST',
                            data: {
                                id: recordId,  // Pass the ID to the server
                                _token: '{{ csrf_token() }}' // Include CSRF token
                            },
                            success: function(response) {
                                Swal.fire('{{ helperTrans("finance::finance.Success") }}', '{{ helperTrans("finance::finance.Transfer completed successfully") }}', 'success');
                                $('#dataTableObject').DataTable().ajax.reload();
                            },
                            error: function() {
                                Swal.fire('{{ helperTrans("finance::finance.Error") }}', '{{ helperTrans("finance::finance.Transfer failed") }}', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script>
        // حساب الإجماليات الكلية لكل الطلب
        function updateGlobalTotals() {
            let totalPrice = 0, totalDiscountValue = 0, totalTaxValue = 0, finalTotal = 0;

            // لكل صف، اجمع القيم
            $('.y-group').each(function () {
                totalPrice += parseFloat($(this).find('.total-sales-price-field').val()) || 0;
                totalDiscountValue += parseFloat($(this).find('.discount-value-field').val()) || 0;
                totalTaxValue += parseFloat($(this).find('.tax-value-field').val()) || 0;
                finalTotal += parseFloat($(this).find('.final-total-field').val()) || 0;
            });

            // تعيين القيم الإجمالية
            $('input[name="total_price"]').val(totalPrice.toFixed(2));
            $('input[name="total_discount_value"]').val(totalDiscountValue.toFixed(2));
            $('input[name="total_tax_value"]').val(totalTaxValue.toFixed(2));
            $('input[name="final_total"]').val(finalTotal.toFixed(2));
        }
    </script>


@endsection
