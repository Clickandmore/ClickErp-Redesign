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
            <th>{{ __("hr::hr.name") }}</th>
            <th>{{ __("hr::hr.code") }}</th>
            <th>{{ __("hr::hr.address") }}</th>
            <th>{{ __("hr::hr.custodies_count") }}</th>
            <th>{{ __("hr::hr.Status") }}</th>
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
                {"data": "name", orderable: false, searchable: true},
                {"data": "code", orderable: false, searchable: true},
                {"data": "address", orderable: false, searchable: true},
                {"data": "custodies_count", orderable: false, searchable: false},
                {"data": "is_active", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
@endsection
