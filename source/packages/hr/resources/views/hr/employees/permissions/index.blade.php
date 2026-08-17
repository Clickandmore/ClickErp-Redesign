@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.employeePermissions')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_employee_permissions-create')) --}}
{!! addButton($createRoute , $addButtonText, 'btn-success',false) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Permission") }} </th>
            <th>{{ __("hr::hr.date") }} </th>
            <th>{{ __("hr::hr.Permission time") }} </th>
            <th>{{ __("hr::hr.reason") }} </th>
            <th>{{ __("hr::hr.file") }} </th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    @include('hr::hr.employees.permissions._js')
@endsection
