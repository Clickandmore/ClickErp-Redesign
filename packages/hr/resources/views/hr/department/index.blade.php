@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.departments')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')
    @include('hr::hr._nav_tabs')
    {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_department-create')) --}}
    {!! addButton(route("departments.create") , $addButtonText) !!}
    {{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.name") }} </th>
            <th>{{ __("hr::hr.number_employees") }} </th>
            <th>{{__("hr::hr.actions")}}</th>
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
                {"data": "title", orderable: false, searchable: true},
                {"data": "number_employees", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("departments.index")}}", columns);
        });


    </script>
@endsection
