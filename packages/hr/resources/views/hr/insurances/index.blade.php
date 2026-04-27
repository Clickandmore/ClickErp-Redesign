@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.insurances')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
{{--@section('up-card')--}}
{{--    @include('click.layout.inc._nav_tabs')--}}
{{--@endsection--}}
@section('content')
    @include('hr::hr._nav_tabs')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_insurances-create')) --}}
{!! addButton(route("insurances.create") , __('hr::hr.insurance')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.name_of_the_slice") }}</th>
            <th>{{ __("hr::hr.calculation method") }}</th>
            <th>{{ __("hr::hr.The value of the total insurance segment") }}</th>
            <th>{{ __("hr::hr.The value of the employee’s insurance segment") }}</th>
            <th>{{ __("hr::hr.The value of the company's insurance segment") }}</th>
            <th>{{ __("hr::hr.status") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    @include("hr::hr.insurances._js_insurances")
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "title", orderable: false, searchable: true},
                {"data": "type", orderable: false, searchable: true},
                {"data": "value", orderable: false, searchable: true},
                {"data": "emp_value", orderable: false, searchable: true},
                {"data": "company_value", orderable: false, searchable: true},
                {"data": "status", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{route("insurances.index")}}", columns);
        });
    </script>
@endsection
