@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.employeeSanctions')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_employee_sanctions-create')) --}}
{!! addButton(route("employeeSanctions.create") , __('hr::hr.employeeSanction')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }} </th>
            <th>{{ __("hr::hr.sanctions") }} </th>
            <th>{{ __("hr::hr.value") }} </th>
            <th>{{ __("hr::hr.date") }} </th>
            <th>{{ __("hr::hr.Is the salary deducted?") }}</th>
            <th>{{ __("hr::hr.reason") }} </th>
            <th>{{ __("hr::hr.Status") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    @include('hr::hr.employeeSanctions._js')
@endsection
