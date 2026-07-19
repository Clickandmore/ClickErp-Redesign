@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        .bill-sales-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .bill-sales-heading {
            min-width: 220px;
        }

        .bill-sales-title {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
        }

        .bill-sales-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .bill-sales-actions .btn {
            margin: 0 !important;
            white-space: nowrap;
        }

        .bill-sales-filters {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 16px;
        }

        .bill-sales-filters label {
            font-size: 12px;
            font-weight: 700;
            color: #4b5563;
        }

        .bill-sales-table {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fff;
            overflow-x: auto;
        }

        #dataTableObject th {
            white-space: nowrap;
            font-size: 12px;
        }

        #dataTableObject td {
            vertical-align: middle;
        }

        @media (max-width: 767.98px) {
            .bill-sales-toolbar {
                align-items: stretch;
            }

            .bill-sales-actions {
                width: 100%;
            }

            .bill-sales-actions .btn {
                flex: 1 1 120px;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="bill-sales-toolbar">
        <div class="bill-sales-heading">
            <h4 class="bill-sales-title">{{ $bladeTitle }}</h4>
            <small class="text-muted">متابعة فواتير المبيعات وحالة مشاركتها مع هيئة الزكاة.</small>
        </div>
        <div class="bill-sales-actions">
            @if (@AdminGuardHasPerm('finance_bill_sales_export') || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
            <a href="{{ route('finance.bill-sales.export.excel') }}" id="exportExcelLink" class="btn btn-outline-success">
                <i class="fa fa-file-excel me-1"></i> Excel
            </a>
            <a href="{{ route('finance.bill-sales.export.pdf') }}" id="exportPdfLink" class="btn btn-outline-danger">
                <i class="fa fa-file-pdf me-1"></i> PDF
            </a>
            @endif
            <a href="{{ $createRoute }}" class="btn btn-success">
                <i class="fa fa-plus-circle me-1"></i>
                {{ __('buttons.add button') }} {{ $addButtonText }}
            </a>
        </div>
    </div>

    <div class="row bill-sales-filters">
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.code') }}</label>
            <input type="text" name="code" id="code" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.clients') }}</label>
            <select name="client_id" id="client_id" class="form-control">
                {!! showSelectElement($clients, 'name') !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.items') }}</label>
            <select name="item_id" id="item_id" class="form-control">
                {!! showSelectElement($items, 'name') !!}
            </select>
        </div>
    </div>
    <div class="bill-sales-table">
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
                <tr>
                    <th>{{ __('finance::finance.SL') }}</th>
                    <th>{{ __('finance::finance.Invoice Number') }}</th>
                    <th>{{ __('finance::finance.date') }}</th>
                    <th>{{ __('finance::finance.the_stock') }}</th>
                    <th>{{ __('finance::finance.client') }}</th>
                    <th>{{ __('finance::finance.Total before discount') }}</th>
                    <th>{{ __('finance::finance.Total discount') }}</th>
                    <th>{{ __('finance::finance.the_tax_value') }}</th>
                    <th>{{ __('finance::finance.total_after_all') }}</th>
                    <th>{{ __('finance::finance.profit_margin') }}</th>
                    <th>{{ __('finance::finance.share') }}</th>
                    <th>{{ __('finance::finance.actions') }}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            function currentQueryString() {
                let params = {
                    shared: "{{ request('shared') }}",
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    code: $('#code').val(),
                    client_id: $('#client_id').val(),
                    item_id: $('#item_id').val()
                };

                Object.keys(params).forEach(function(key) {
                    if (params[key] === '' || params[key] === null || typeof params[key] === 'undefined') {
                        delete params[key];
                    }
                });

                return $.param(params);
            }

            function updateExportLinks() {
                let query = currentQueryString();
                $('#exportExcelLink').attr('href', "{{ route('finance.bill-sales.export.excel') }}" + (query ? '?' + query : ''));
                $('#exportPdfLink').attr('href', "{{ route('finance.bill-sales.export.pdf') }}" + (query ? '?' + query : ''));
            }

            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "الكل"]
                ],
                "processing": true,
                "responsive": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.code = $('#code').val();
                        d.client_id = $('#client_id').val();
                        d.item_id = $('#item_id').val();
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
                        "data": "date",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "the_stock",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "client_id",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "total_item",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "discount",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "total_tax_value",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "final_total",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "profit_margin",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "zatca_status",
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                ],
            });

            $('#from_date, #to_date, #client_id, #item_id').change(function() {
                updateExportLinks();
                table.draw();
            });
            $('#code').keyup(function() {
                updateExportLinks();
                table.draw();
            });
            updateExportLinks();
        });

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

                var totalSalesPrice = salesPrice * salesAmount;
                var discountValue = totalSalesPrice * (discountPer / 100);
                var finalTotal = totalSalesPrice - discountValue;

                currentRow.find('.total-sales-price-field').val(totalSalesPrice.toFixed(2));
                currentRow.find('.discount-value-field').val(discountValue.toFixed(2));
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

        $(document).on('click', '.shareZatcaBtn', function(e) {
            e.preventDefault(); // Prevent the default action
            {{-- // Get the invoice ID from the data-id attribute --}}
            var id = $(this).data('id');
            var url = "{{ route('zatca.share-invoice.store') }}";

            // Create a FormData object and append the id
            var formData = new FormData();
            formData.append('id', id);

            // Perform the AJAX request
            $.ajax({
                url: url,
                type: 'POST',
                data: formData, // Pass the FormData object
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    // Actions before the AJAX call is made
                    $(".preloaderImg::before").css("background-image", "url('" +
                        "{{ asset('admin/assets/zatca.png') }}" + "')");
                    $(".preloaderImg").css("mask-image", "url('" +
                        "{{ asset('admin/assets/zatca.png') }}" + "')");
                    $(".preloader").fadeIn(300).css("opacity", "0.5");
                },
                complete: function() {
                    $(".preloader").fadeOut(300);
                },
                success: function(response) {
                    $.toast({
                        heading: 'تم الارسال',
                        text: 'تم مشاركة الفاتورة مع الهيئة بنجاح',
                        showHideTransition: 'slide',
                        icon: 'success',
                        hideAfter: 2000,
                        position: 'top-right',
                        stack: 1
                    });
                    $('#dataTableObject').DataTable().ajax.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error
                    console.error('Error sharing invoice:', error);
                    errorToster('Error sharing invoice');
                }
            });
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
