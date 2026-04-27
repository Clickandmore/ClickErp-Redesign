@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr_permissions-create')) --}}
{!! addButton($createRoute , $addButtonText) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.number_of_hours") }}</th>
            <th>{{ __('hr::hr.per_month') }}</th>
            <th> {{ __("hr::hr.shifts_count") }} </th>
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "number_of_hours", orderable: false, searchable: false},
                {"data": "per_month", orderable: false, searchable: false},
                {"data": "shifts_count", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{$dataTableRoute}}", columns);
        });
    </script>
@endsection
