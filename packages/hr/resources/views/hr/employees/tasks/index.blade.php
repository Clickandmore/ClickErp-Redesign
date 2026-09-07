@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>

    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')

    {!! addButton($createRoute , $addButtonText) !!}

    <table class="table" id="dataTableObject">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('crm.SL') }}</th>
            <th>{{ __("crm.title") }} </th>
            <th>{{ __("crm.end_date") }} </th>
            <th>{{ __("crm.attach") }} </th>
            <th>{{ __('financial_requests.branch') }}</th>
            <th class="notPrintable">{{ __("hr.replies_actions") }} </th>
            <th class="notPrintable">{{ __('crm.actions') }}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>

    </table>

@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [{
                "data": 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
                {
                    "data": "title",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "end_date",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "file",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "branch",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "replies",
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
