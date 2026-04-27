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
{!! addButton(route("employeePermissions.create") , __('hr::hr.Permission')) !!}
{{-- @endif --}}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }} </th>
            <th>{{ __("hr::hr.Permission") }} </th>
            <th>{{ __("hr::hr.date") }} </th>
            <th>{{ __("hr::hr.Permission time") }} </th>
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
    @include('hr::hr.employeePermissions._js')
    <script>
        $(document).ajaxComplete(function () {
            function calculateDuration() {
                const fromTime = $('#from_time').val();
                const toTime = $('#to_time').val();

                if (fromTime && toTime) {
                    const fromDate = new Date('1970-01-01T' + fromTime + 'Z');
                    const toDate = new Date('1970-01-01T' + toTime + 'Z');

                    let diffInMinutes = (toDate - fromDate) / 60000;

                    if (diffInMinutes < 0) {
                        diffInMinutes += 24 * 60;
                    }

                    $('#time').val(diffInMinutes);
                } else {
                    $('#time').val('');
                }
            }
            $('#from_time, #to_time').on('change', calculateDuration);
        });
    </script>
@endsection
