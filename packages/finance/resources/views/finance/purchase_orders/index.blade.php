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
    <div class="row mb-3">


        <div class="col-md-3">
            <label for="">{{ __('finance::finance.suppliers') }}</label>
            <select name="supplier_id" id="supplier_id" class="form-control select2">
                <option value=""> {{ __('finance::finance.Choose') }} </option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}"> {{ $supplier->code . '-' . $supplier->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.item') }}</label>
            <select name="item_id" id="item_id" class="form-control select2">
                <option value=""> {{ __('finance::finance.Choose') }} </option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}"> {{ $item->name . '-' . $item->code_string . $item->code }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control">
        </div>
    </div>

    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.order_number') }}</th>
                <th>{{ __('finance::finance.date') }}</th>
                <th>{{ __('finance::finance.supplier') }}</th>
                <th>{{ __('finance::finance.product_amount') }}</th>
                <th>{{ __('finance::finance.Total before discount') }}</th>
                <th>{{ __('finance::finance.total_discount_value') }}</th>
                <th>{{ __('finance::finance.Tax amount') }}</th>
                <th>{{ __('finance::finance.total') }}</th>
                <th>{{ __('finance::finance.status') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            let columns = [{
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
                    "data": "supplier_id",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "amount_items",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total_before_discount",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total_discount_value",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total_tax_value",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total_price",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "status",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
        // $(document).ready(function () {
        //     select2AfterPlus();
        //     let columns = [
        //         {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        //         {"data": "code", orderable: false, searchable: false},
        //         {"data": "date", orderable: false, searchable: false},
        //         {"data": "supplier_id", orderable: false, searchable: false},
        //         {"data": "amount_items", orderable: false, searchable: false},
        //         {"data": "total_price", orderable: false, searchable: false},
        //         {"data": "status", orderable: false, searchable: false},
        //         {"data": "actions", orderable: false, searchable: false}
        //     ];
        //     var table = $('#dataTableObject').DataTable({
        //         "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "الكل"] ],
        //         "processing": true,
        //         "serverSide": true,
        //         "sort": false,
        //         "ajax": {
        //             "url": "{{ $dataTableRoute }}",
        //             "type": "GET",
        //             "data": function (d) {
        //                 d.from_date = $('#from_date').val();
        //                 d.to_date = $('#to_date').val();
        //                 d.code = $('#code').val();
        //                 d.supplier_id = $('#supplier_id').val();
        //                 d.item_id = $('#item_id').val();
        //             }
        //         },
        //         "columns": columns
        //     });


        //     $('#from_date, #to_date, #supplier_id, #item_id').change(function () {
        //         table.draw();
        //     });
        //     // $('#code').keyup(function () {
        //     //     table.draw();
        //     // });

        // });

        // {{-- $(document).on('change', '#supplier_id', function(e) { --}}
        // {{--    const supplier_id = this.value; --}}
        // {{--    var url = "{{ route('finance.filter-items.index') }}"; --}}
        // {{--    $.ajax({ --}}
        // {{--        type: 'get', --}}
        // {{--        data: { --}}
        // {{--            supplier_id : supplier_id --}}
        // {{--        }, --}}
        // {{--        url: url, --}}
        // {{--        success: function(data) { --}}
        // {{--            console.log(data) --}}
        // {{--           $("#items_list").html(''); --}}
        // {{--            $("#items_list").html(data.data.html); --}}
        // {{--        }, --}}
        // {{--        error: function() { --}}
        // {{--            alert('حدث خطأ أثناء التحديث.'); --}}
        // {{--        } --}}
        // {{--    }); --}}
        // {{-- }); --}}
    </script>
    <script>
        $(document).ajaxComplete(function() {
            $('.item-select').each(function() {
                var selectedOption = $(this).find('option:selected');
                var currentRow = $(this).closest('tr');
                updateRowValues(selectedOption, currentRow);
            });

            $(document).on('change', '.item-select', function() {
                var selectedOption = $(this).find('option:selected');
                var currentRow = $(this).closest('tr');
                updateRowValues(selectedOption, currentRow);
            });

            function updateRowValues(selectedOption, currentRow) {
                var purchaseUnit = selectedOption.data('purchaseunit');
                var currentAmount = selectedOption.data('currentamount');
                var purchasePrice = selectedOption.data('purchaseprice');

                currentRow.find('.unit-field').val(purchaseUnit);
                currentRow.find('.current-amount-field').val(currentAmount);
                currentRow.find('.purchase-price-field').val(purchasePrice);

                calculateDifference(currentRow);
            }
        });

        function calculateDifference(element) {
            var currentRow = $(element).closest('tr');

            var currentAmount = parseFloat(currentRow.find('.amount-field').val()) || 0;
            var purchasePrice = parseFloat(currentRow.find('.purchase-price-field').val()) || 0;

            var discountPer = parseFloat(currentRow.find('.discount-per-field').val()) || 0;
            var taxPer = parseFloat(currentRow.find('.tax-per-field').val()) || 0;

            // Calculate total price
            let total = currentAmount * purchasePrice;
            // Calculate discount value
            let discountValue = (total * discountPer) / 100;
            // Calculate final price after discount
            let finalPriceBeforeTax = total - discountValue;
            // Calculate tax value
            let taxValue = (finalPriceBeforeTax * taxPer) / 100;

            // Calculate final price after tax
            let totalPurchasePrice = finalPriceBeforeTax + taxValue;

            currentRow.find('.discount-value-field').val(discountValue.toFixed(2));
            currentRow.find('.tax-value-field').val(taxValue.toFixed(2));
            currentRow.find('.total-purchase-price-field').val(totalPurchasePrice.toFixed(2));
        }

        $(document).ajaxComplete(function() {
            $(document).on('keyup', '.real-amount-field', function() {
                calculateDifference(this);
            });
        });
    </script>
@endsection
