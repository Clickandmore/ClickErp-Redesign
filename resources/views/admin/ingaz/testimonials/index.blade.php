@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{helperTrans("ingaz.testimonials")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
    {{--@if (@AdminGuardHasPerm(\App\PermKeys::FAQ_VIEW))--}}

    {{--    @if (@AdminGuardHasPerm(\App\PermKeys::FAQ_CONTROL))--}}
    {!! addButton($createRoute,helperTrans("ingaz.testimonials")) !!}
    {{--    @endif--}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.name")}}</th>
            <th>{{helperTrans("ingaz.image")}}</th>
            <th>{{helperTrans("ingaz.job")}}</th>
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
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "name", orderable: true, searchable: true},
                {"data": "image", orderable: true, searchable: true},
                {"data": "job", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{$dataTableRoute}}", columns);
        });

        // ِChange Status Using Ajax
    </script>
@endsection
