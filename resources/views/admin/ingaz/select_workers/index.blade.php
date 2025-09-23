@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.select_workers")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')


{{--    @if (@AdminGuardHasPerm(\App\PermKeys::STAT_CONTROL))--}}
        {!! addButton(route("select-workers.create") ,helperTrans("ingaz.select_workers")) !!}
{{--    @endif--}}

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.icon")}}</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
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
                {"data": "icon", orderable: false, searchable: false},
                {"data": "title",orderable: true,searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("select-workers.index")}}", columns);
        });
    </script>
@endsection
