@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/vendor/tree_collapse/tree-co.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addButton($createRoute, $addButtonText) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.code') }}</th>
                <th>{{ __('finance::finance.name') }}</th>
                <th>{{ __('finance::finance.phone') }}</th>
                <th>{{ __('finance::finance.address') }}</th>
                <th>{{ __('finance::finance.stockKeeper') }}</th>
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
                    searchable: true
                },
                {
                    "data": "name",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "phone",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "address",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "stockKeeper",
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
    </script>
@endsection
