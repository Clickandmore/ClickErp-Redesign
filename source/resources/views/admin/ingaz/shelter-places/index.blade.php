@extends('admin.layout.indexs.index')
@section("style")
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_VIEW))

    @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_PLACES_CONTROL))
        {!! addButton(route("shelter-places.create") ,helperTrans("ingaz.shelter_place")) !!}
    @endif
    <table id="dataTableObject" class="table display dataTable table-hover table-style" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
            <th>{{helperTrans("ingaz.title")}}</th>
            <th>{{helperTrans("ingaz.shelter_capacity")}}</th>
            <th>{{helperTrans("ingaz.current_occupancy")}}</th>
            <th>{{helperTrans("ingaz.shelter_location")}}</th>
            <th>{{helperTrans("ingaz.shelter_management")}}</th>
            <th>{{helperTrans("ingaz.created_at")}}</th>
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
                {"data": "title", orderable: true, searchable: true},
                {"data": "capacity", orderable: false, searchable: false},
                {"data": "current_occupancy", orderable: false, searchable: false},
                {"data": "location", orderable: false, searchable: true},
                {"data": "management", orderable: false, searchable: true},
                {"data": "created_at", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("shelter-places.index")}}", columns);
        });
    </script>
@endsection
