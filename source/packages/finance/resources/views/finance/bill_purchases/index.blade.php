@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
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
            for="switchFilter">{{ __('finance::finance.advanced_search') }}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.suppliers') }}</label>
            <select name="supplier_id" id="supplier_id" class="form-control select2">
                {!! showSelectElement($suppliers, 'name') !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.items') }}</label>
            <select name="item_id" id="item_id" class="form-control select2">
                {!! showSelectElement($items, 'name') !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.the_stock') }}</label>
            <select name="stock_id" id="stock_id" class="form-control select2">
                {!! showSelectElement($stocks, 'name') !!}
            </select>
        </div>
    </div>
    {!! addHyperButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.Invoice Number') }}</th>
                <th>{{ __('finance::finance.supplier_bill_number') }}</th>
                <th>{{ __('finance::finance.date') }}</th>
                <th>{{ __('finance::finance.the_supplier') }}</th>
                <th>{{ __('finance::finance.the_stock') }}</th>
                <th>{{ __('finance::finance.Total before discount') }}</th>
                <th>{{ __('finance::finance.Total discount') }}</th>
                <th>{{ __('finance::finance.the_tax_value') }}</th>
                <th>{{ __('finance::finance.net') }}</th>
                {{--            <th>{{__("portfolio.paid_amount")}}</th> --}}
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script type="text/javascript" src="{{ asset('admin') }}/assets/js/bundle/datatable.buttons.min.js"></script>
    <script type="text/javascript" src="{{ asset('admin') }}/assets/js/bundle/buttons.html5.min.js"></script>
    <script type="text/javascript" src="{{ asset('admin') }}/assets/js/bundle/buttons.print.min.js"></script>
    <script type="text/javascript" src="{{ asset('admin') }}/assets/js/bundle/jszip.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "الكل"]
                ],
                "processing": true,
                "serverSide": true,
                "responsive": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.code = $('#code').val();
                        d.supplier_id = $('#supplier_id').val();
                        d.item_id = $('#item_id').val();
                        d.stock_id = $('#stock_id').val();
                    }
                },
                "columns": [{
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "code",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "supplier_bill_number",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "date",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "supplier_id",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "the_stock",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "total_before_all",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "discount",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "tax_value",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "final_total",
                        orderable: false,
                        searchable: false
                    },
                    // {"data": "paid_amount", orderable: false, searchable: false},
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                ],
                // dom: 'Bflrtip', // Add the Buttons extension to the DataTable
                dom: '<"d-flex justify-content-between align-items-center mb-3"fB>rtlip',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-outline-success mb-2',
                    text: '<?php echo e(trans('dataTable.ex_excel')); ?>',
                }]
            });

            $('#from_date, #to_date, #supplier_id, #item_id,#stock_id').change(function() {
                table.draw();
            });
            $('#code').keyup(function() {
                table.draw();
            });
        });
    </script>
    <script>
        $(document).ajaxComplete(function() {
            // عند اختيار صنف من القائمة
            $(document).on('change', '.item-select', function() {
                var selectedOption = $(this).find('option:selected');
                var currentRow = $(this).closest('tr');
                updateRowValues(selectedOption, currentRow);
            });

            // تحديث قيم الصفوف عند اختيار صنف
            function updateRowValues(selectedOption, currentRow) {
                var saleUnit = selectedOption.data('saleunit');
                var availableAmount = selectedOption.data('availableamount');
                var salePrice = selectedOption.data('saleprice');

                // تحديث حقل الوحدة والكمية المتاحة وسعر البيع
                currentRow.find('.unit-field').val(saleUnit);
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
                var discountValue = totalSalesPrice * (discountPer / 100);
                var taxValue = (((salesPrice * salesAmount) - discountValue) * taxPer) / 100;
                var finalTotal = totalSalesPrice - discountValue;

                currentRow.find('.total-sales-price-field').val(totalSalesPrice.toFixed(2));
                currentRow.find('.discount-value-field').val(discountValue.toFixed(2));
                currentRow.find('.tax-value-field').val(taxValue.toFixed(2));
                currentRow.find('.final-total-field').val(finalTotal.toFixed(2));

                // تحديث الإجماليات الكلية لكل الطلبات
                updateGlobalTotals();
            }

            // حساب الإجماليات الكلية لكل الطلب
            function updateGlobalTotals() {
                var totalPrice = 0,
                    totalDiscountValue = 0,
                    finalTotal = 0;

                // لكل صف، اجمع القيم
                $('.y-group').each(function() {
                    totalPrice += parseFloat($(this).find('.total-sales-price-field').val()) || 0;
                    totalDiscountValue += parseFloat($(this).find('.discount-value-field').val()) || 0;
                    finalTotal += parseFloat($(this).find('.final-total-field').val()) || 0;
                });

                // تعيين القيم الإجمالية
                $('input[name="total_price"]').val(totalPrice.toFixed(2));
                $('input[name="total_discount_value"]').val(totalDiscountValue.toFixed(2));
                $('input[name="final_total"]').val(finalTotal.toFixed(2));
            }
        });

        $(document).on('click', '.confirmBtn', function(e) {
            e.preventDefault()
            let buttonObj = $(this);
            let confirmRoute = buttonObj.attr("confirm-route");
            swal.fire({
                title: "{{ __('buttons.Are You Sure') }}",
                text: "{{ __('buttons.You Can not to rollback') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "{{ __('buttons.accept') }}",
                cancelButtonText: "{{ __('buttons.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: confirmRoute,
                        type: 'PUT',
                        beforeSend: function() {},
                        complete: function() {},
                        success: function(data) {
                            Swal.fire({
                                icon: 'success',
                                title: "{{ __('buttons.Confirm successfully') }}",
                                confirmButtonText: "{{ __('buttons.close') }}",
                            });
                            reloadDataTable();
                            if (data.code == 201) {
                                location.reload();
                            }
                        },
                        error: function(errorObj, errorText, errorThrown) {
                            handleErrorResponce(errorObj);
                        }
                    });
                }
            });
        });
    </script>
@endsection
