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

                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                <thead class="thead-inverse">
                    <tr>
                        <th>{{ __('crm.SL') }}</th>
                        <th>{{ __("hr.Employee") }} </th>
                        <th>{{ __("crm.title") }} </th>
                        <th>{{ __("crm.description") }} </th>
                        <th>{{ __("crm.attach") }} </th>
                        <th>{{ __('crm.actions') }}</th>
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
                    "data": "employee_id",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "title",
                    orderable: false,
                    searchable: true
                },
                 {
                    "data": "description",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "file",
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
