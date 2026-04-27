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
     {!! addButton($createRoute , $addButtonText) !!}
{{--    <div class="d-flex mb-3 justify-content-end">--}}
{{--        <a class="btn btn-success float-start mx-1" href="{{ $createRoute }}" title="" >--}}
{{--            {{ __("buttons.add button") }} {{ $addButtonText }}--}}
{{--        </a>--}}
{{--    </div>--}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th> {{__('finance::finance.code')}} </th>
            <th> {{__('finance::finance.name')}} </th>
            <th>{{__('finance::finance.unit')}} </th>
            <th>{{__('finance::finance.current_balance')}} </th>
            <th>{{__('finance::finance.purchase price')}} </th>
            <th>{{__('finance::finance.supplier')}} </th>
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
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "code", orderable: false, searchable: false},
                {"data": "name", orderable: false, searchable: false},
                {"data": "unit", orderable: false, searchable: false},
                {"data": "current_balance", orderable: false, searchable: false},
                {"data": "purchase_price", orderable: false, searchable: false},
                {"data": "supplier_id", orderable: false, searchable: false},
            ];
            showDataTable("{{ $dataTableRoute }}", columns);


        });
        $(document).on('change', '#supplier_id', function(e) {
            const supplier_id = this.value;
            var from = 'missing';
            var url = "{{ route('finance.filter-items.index') }}";
            $.ajax({
                type: 'get',
                data: {
                    supplier_id : supplier_id,
                    from : from
                },
                url: url,
                success: function(data) {
                    console.log(data)
                    $("#items_list").html('');
                    $("#items_list").html(data.data.html);
                },
                error: function() {
                    alert('حدث خطأ أثناء التحديث.');
                }
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
