@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr.advances')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.complaints")}}</li>
@endsection
@section('content')

    {!! addButton($createRoute , __('hr.reply')) !!}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead class="thead-inverse">
        <tr>
            <th>{{ __('finance.SL') }}</th>
            <th>{{ __("hr.added_by") }}</th>
            <th>{{ __("hr.notes") }} </th>
            <th> {{ __("hr.date") }}</th>
            <th>{{__("finance.actions")}}</th>
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
                {"data": "admin_id", orderable: false, searchable: true},
                {"data": "text", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{$dataTableRoute}}", columns);
        });
    </script>
@endsection
