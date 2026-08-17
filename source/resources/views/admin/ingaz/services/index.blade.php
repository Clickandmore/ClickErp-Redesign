@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{helperTrans("ingaz.services")}}
@endsection
@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::SERVICES_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::SERVICES_CONTROL))
        {!! addButton(route("services.create") ,helperTrans("ingaz.service")) !!}
    @endif

    <p>{{helperTrans('ingaz.shown in the site home page and they are 4 rows')}}</p>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.image")}}</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.description")}}</th>
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

    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "image", orderable: false, searchable: false},
                {"data": "title",orderable: true,searchable: true},
                {"data": "description",orderable: true,searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("services.index")}}", columns);
        });
    </script>
@endsection
