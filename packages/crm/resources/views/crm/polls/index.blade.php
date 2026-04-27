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
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
            <tr>
                <th>{{ __('crm::crm.SL') }}</th>
                <th>{{ __('crm::crm.title') }} </th>
                <th class="notPrintable">{{ __('crm::crm.questions') }} </th>
                <th class="notPrintable">{{ __('crm::crm.showAll') }} </th>
                <th class="notPrintable">{{ __('crm::crm.clients') }} </th>
                <th class="notPrintable">{{ __('crm::crm.result') }} </th>
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
        $(document).ready(function() {
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
                    "data": "questions",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "show",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "send_poll_clients",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "clients",
                    orderable: false,
                    searchable: true
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
