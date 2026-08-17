@extends('click.layout.indexs.index')
@section('style')
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addHyperButton($createRoute, $addButtonText) !!}
    <div class="row my-2">

        <div class="col-md-4 float-start">
            <label for="TextInput" class="form-label">{{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control" required>
        </div>

        <div class="col-md-4 float-start">
            <label for="TextInput" class="form-label">{{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control" required>
        </div>

        <div class="col-md-4 float-start">
            <label for="TextInput" class="form-label">
                {{ __('finance::finance.the_stock') }}
                {!! addUpButton(route('finance.stocks.create'), 'stock_id', '', 'name', true) !!}
            </label>
            <select class="form-control" name="stock_id" id="stock_id" data-validation="required">
                {!! showSelectElement($stocks, 'name') !!}
            </select>
        </div>

    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                {{-- <th>{{ __('finance::finance.type') }}</th> --}}
                <th>{{ __('finance::finance.code') }}</th>
                <th>{{ __('finance::finance.date') }}</th>
                <th>{{ __('finance::finance.the_stock') }}</th>
                <th>{{ __('finance::finance.type') }}</th>
                <th>{{ __('finance::finance.notes') }}</th>
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
                        d.from_date = $('#from_date').val()
                        d.to_date = $('#to_date').val()
                        d.stock_id = $('#stock_id').val()
                    }
                },
                "columns": [{
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    // {"data": "type", orderable: false, searchable: true},
                    {
                        "data": "code",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "date",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "the_stock",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "type",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "note",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                ],
            });

            $('#filter').click(function() {
                table.draw();
            });

            $('#from_date').change(function() {
                table.draw();
            });
            $('#stock_id').change(function() {
                table.draw();
            });
            $('#to_date').change(function() {
                table.draw();
            });
        });
    </script>
@endsection
