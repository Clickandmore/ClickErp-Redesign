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
    <div class="row mb-3">
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
            <label for="">{{ __("finance::finance.stocks") }}</label>
            <select name="stock_id" id="stock_id" class="form-control select2">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($stocks as $stock)
                    <option value="{{ $stock->id }}">
                        {{ $stock->name }} - {{ $stock->code }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __("finance::finance.items") }}</label>
            <select name="item_id" id="item_id" class="form-control select2">
                <option value="">{{ __("finance::finance.choose") }}</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }} - {{ $item->code_string }}{{ $item->code }}
                    </option>
                @endforeach
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
            <th>{{__("finance::finance.stock")}}</th>
            <th>{{__("finance::finance.responsible")}}</th>
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
            "serverSide": true,
            "sort": false,
            "ajax": {
                "url": "{{ $dataTableRoute }}",
                "type": "GET",
                "data": function (d) {
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.code = $('#code').val();
                    d.stock_id = $('#stock_id').val();
                    d.item_id = $('#item_id').val();
                }
            },
            "columns": [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "code", orderable: false, searchable: false},
                {"data": "date", orderable: false, searchable: false},
                {"data": "stock_id", orderable: false, searchable: false},
                {"data": "responsible", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ],
        });

        $('#from_date, #to_date, #stock_id, #item_id').change(function () {
            table.draw();
        });
        $('#code').keyup(function () {
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

            var currentAmount = parseFloat(currentRow.find('.current-amount-field').val()) || 0;
            var realAmount = parseFloat(currentRow.find('.real-amount-field').val()) || 0;
            var purchasePrice = parseFloat(currentRow.find('.purchase-price-field').val()) || 0;

            var differenceAmount = realAmount - currentAmount;

            var differencePurchasePrice = differenceAmount * purchasePrice;

            currentRow.find('.difference-amount-field').val(differenceAmount.toFixed(2));
            currentRow.find('.difference-purchase-price-field').val(differencePurchasePrice.toFixed(2));
        }

        $(document).ajaxComplete(function() {
            $(document).on('keyup', '.real-amount-field', function() {
                calculateDifference(this);
            });
            $(document).on('change', '.item-select', function() {
                calculateDifference(this);
            });
        });
    </script>
@endsection
