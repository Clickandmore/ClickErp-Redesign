@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')

    {!! addButton($createRoute , $addButtonText, 'btn-success',false) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }}</th>
            <th>{{ __("hr::hr.custody_name") }}</th>
            <th>{{ __("hr::hr.custody_code") }}</th>
            <th>{{ __("hr::hr.value") }}</th>
            <th>{{ __("hr::hr.location") }}</th>
            <th>{{ __("hr::hr.received_at") }}</th>
            <th>{{ __("hr::hr.returned_at") }}</th>
            <th>{{ __("hr::hr.account") }}</th>
            <th>{{ __("hr::hr.Status") }}</th>
            <th>{{ __("hr::hr.file") }}</th>
            <th class="notPrintable">{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>

@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "employee_id", orderable: false, searchable: false},
                {"data": "name", orderable: false, searchable: true},
                {"data": "code", orderable: false, searchable: true},
                {"data": "value", orderable: false, searchable: true},
                {"data": "location", orderable: false, searchable: true},
                {"data": "received_at", orderable: false, searchable: true},
                {"data": "returned_at", orderable: false, searchable: true},
                {"data": "account_id", orderable: false, searchable: false},
                {"data": "status", orderable: false, searchable: true},
                {"data": "file", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
@endsection
