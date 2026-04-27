@extends('nami.layout.indexs.index')
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
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('crm::crm.SL') }}</th>
            <th>{{ __("crm::crm.title") }} </th>
            <th>{{ __("crm::crm.client") }} </th>
            <th>{{ __("crm::crm.attach") }} </th>
            <th>{{ __("crm::crm.file") }} </th>
            <th class="notPrintable">{{ __('crm::crm.actions') }}</th>
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
                    "data": "client",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "attachment",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "file",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            showDataTable("{{ route('crm.client_attachments.index') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}", columns);
        });


    </script>

@endsection
