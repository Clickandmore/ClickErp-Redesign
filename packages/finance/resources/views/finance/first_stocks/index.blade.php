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
            <label for="">{{ __('finance::finance.stocks') }}</label>
            <select name="stock_id" id="stock_id" class="form-control select2">
                {!! showSelectElement($stocks, 'name') !!}
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
        <div class="col-md-6 ">
            <label for=""> </label>
            {!! addButton($createRoute, $addButtonText) !!}
        </div>
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.code') }}</th>
                <th>{{ __('finance::finance.item') }}</th>
                <th>{{ __('finance::finance.unit') }}</th>
                <th>{{ __('finance::finance.stock') }}</th>
                @if ($stock_setting->show_purchase_price_in_first_stocks)
                    <th>{{ __('finance::finance.purchase price') }}</th>
                @endif
                <th>{{ __('finance::finance.sale price') }}</th>
                <th>{{ __('finance::finance.amount') }}</th>
                <th>{{ __('finance::finance.total') }}</th>
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
            select2AfterPlus();

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
                    "data": "item",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "unit",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "stock",
                    orderable: false,
                    searchable: false
                },
                @if ($stock_setting->show_purchase_price_in_first_stocks)
                    {
                        "data": "purchase_price",
                        orderable: false,
                        searchable: false
                    },
                @endif {
                    "data": "sale_price",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "amount",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "total_price",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "الكل"]
                ],
                "processing": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ $dataTableRoute }}",
                    "type": "GET",
                    "data": function(d) {
                        d.stock_id = $('#stock_id').val();
                        d.item_id = $('#item_id').val();
                    }
                },
                "columns": columns,
            });

            $('#stock_id, #item_id').change(function() {
                table.draw();
            });
        });
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
            }
        });

        function calculateDifference(element) {
            var currentRow = $(element).closest('tr');

            var currentAmount = parseFloat(currentRow.find('.amount-field').val()) || 0;
            var purchasePrice = parseFloat(currentRow.find('.purchase-price-field').val()) || 0;


            var totalPurchasePrice = currentAmount * purchasePrice;

            currentRow.find('.total-purchase-price-field').val(totalPurchasePrice.toFixed(2));
        }

        $(document).ajaxComplete(function() {
            $(document).on('keyup', '.real-amount-field', function() {
                calculateDifference(this);
            });
        });
    </script>
@endsection
