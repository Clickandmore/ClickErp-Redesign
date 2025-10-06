@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.why_us")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    {{--@if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))--}}

    {{--    @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_CONTROL))--}}
    {!! addButton($createRoute,helperTrans("ingaz.why_us")) !!}
    {{--    @endif--}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.icon")}}</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.description")}}</th>
            <th>{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

    {{--@else--}}
    {{--    @include('components.no_permissions')--}}
    {{--@endif--}}

@endsection
@section('js')
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "icon", orderable: true, searchable: true},
                {"data": "title", orderable: true, searchable: true},
                {"data": "description", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{$dataTableRoute}}", columns);
        });

        // ِChange Status Using Ajax
    </script>
@endsection
