@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.employeeVacations')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')
{{--    <div class="row">--}}
{{--        <div class="col-3 my-2">--}}
{{--            <select class="form-control show-tick ms" name="employee_id" id="employeeId" data-validation="required">--}}
{{--                <option value="">{{ __("hr.Choose") }} {{ __("hr.Employee") }}</option>--}}
{{--                @foreach ($employees as $employee)--}}
{{--                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>--}}
{{--                @endforeach--}}
{{--            </select>--}}
{{--        </div>--}}
{{--        <div class="col-2 my-2">--}}
{{--            <input class="form-control" type="date" name="date" id="date">--}}
{{--        </div>--}}
{{--        <div class="col-2 my-2">--}}
{{--            <input class="form-control" type="month" name="month" id="month">--}}
{{--        </div>--}}
{{--    </div>--}}

{{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_vacations-create')) --}}
{!! addButton(route("employeeVacations.create") , __('hr::hr.employeeVacation_order')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }}  </th>
            <th>{{ __("hr::hr.the kind of holiday") }}</th>
            <th>{{ __("hr::hr.Balance") }} </th>
            <th>{{ __("hr::hr.From the date of") }}</th>
            <th>{{ __("hr::hr.To date") }}</th>
            <th>{{ __("hr::hr.Date difference") }}</th>
            <th>{{ __("hr::hr.comments") }} </th>
            <th>{{ __("hr::hr.status") }}</th>
            <th>{{ __("hr::hr.View Attached File") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    @include('hr::hr.employeeVacations._js')
@endsection
