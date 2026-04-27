@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}} &nbsp;
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}} &nbsp;</li>
@endsection
@section('content')

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('finance.SL') }}</th>
            <th>{{ __("hr.Employee") }}</th>
            <th>{{ __("hr.complaint_text") }} </th>
            <th> {{ __("hr.date") }}</th>
            <th class="notPrintable"> {{ __("hr.replies") }}</th>
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "employee_id", orderable: false, printable: false, searchable: true},
                {"data": "description", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "replies", orderable: false, searchable: false},
            ];
            showDataTable("{{$dataTableRoute}}", columns);
        });
    </script>
@endsection
