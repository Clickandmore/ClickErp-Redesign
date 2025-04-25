@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.statistics")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::STAT_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::STAT_CONTROL))
        {!! addButton(route("statistics.create") ,helperTrans("ingaz.statistics")) !!}
    @endif

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.image")}}</th>
            <th>{{helperTrans("ingaz.counter_num")}}</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "image", orderable: false, searchable: false},
                {"data": "counter_num",orderable: true,searchable: true},
                {"data": "title",orderable: true,searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("statistics.index")}}", columns);
        });
    </script>
@endsection
