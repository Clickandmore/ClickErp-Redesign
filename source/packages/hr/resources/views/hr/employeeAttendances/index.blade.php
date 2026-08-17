@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{__('hr::hr.employeeAttendances')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

<div class="row">
    <div class="col-3 my-2">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Employee") }} </label>
        <select class="form-control show-tick ms select2" name="employee_id" id="employeeId" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} {{ __("hr::hr.Employee") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-3 my-2">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.type") }} </label>
        <select class="form-control show-tick ms select2" name="type" id="type" data-validation="required">
            {!! getEnumData($employee_attendances_type_full) !!}
        </select>
    </div>
    <div class="col-2 my-2">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.date") }} </label>
        <input class="form-control" type="date" name="date" id="date">
    </div>
    <div class="col-2 my-2">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Choose the month") }} </label>
        <input class="form-control" type="month" name="month" id="month">
    </div>
</div>
{{-- @if (Auth::guard('admin')->user()->hasPermission('hr::hr_employee_attendances-create')) --}}
{!! addButton(route("employeeAttendances.create") , __('hr::hr.employeeAttendances')) !!}
{{-- @endif --}}

    <div class="col-12">
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('hr::hr.SL') }}</th>
            <th>{{ __("hr::hr.Employee") }}</th>
            <th>  {{ __("hr::hr.date") }}</th>
            <th> {{ __("hr::hr.time attendance") }}</th>
            <th> {{ __("hr::hr.Check-out time") }}</th>
            <th> {{ __("hr::hr.Month and year") }}</th>
            <th> {{ __("hr::hr.type") }}</th>
            <th> {{ __("hr::hr.created_by_id") }}</th>
            <th>{{__("hr::hr.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
    </div>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            var table = $('#dataTableObject').DataTable({
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "الكل"] ],
                "processing": true,
                "serverSide": true,
                "sort": false,
                "ajax": {
                    "url": "{{ route('employeeAttendances.index') }}",
                    "type": "GET",
                    "data": function (d) {
                        d.employee_id = $('#employeeId').val();
                        d.type = $('#type').val();
                        d.date = $('#date').val();
                        d.month_year = $('#month').val();
                    }
                },
                "columns": [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {"data": "employee_id", orderable: false, searchable: true},
                    {"data": "date", orderable: false, searchable: true},
                    {"data": "attendance_time", orderable: false, searchable: false},
                    {"data": "leave_time", orderable: false, searchable: false},
                    {"data": "month_year", orderable: false, searchable: false},
                    {"data": "type", orderable: false, searchable: true},
                    {"data": "created_by_id", orderable: false, searchable: true},
                    {"data": "actions", orderable: false, searchable: false}
                ],
            });

            $('#filter').click(function() {
                table.draw();
            });

            $('#employeeId, #type').on('keyup change', function () {
                table.column(1).search($('#employeeId').val()).draw();
                table.column(6).search($('#type').val()).draw();
            });
            $('#date').on('change', function () {
                var searchValue = $(this).val();
                table.column(2).search(searchValue).draw();
            });
            $('#month').on('change', function () {
                var searchValue = $(this).val();
                table.column(2).search(searchValue).draw();
            });
        });
    </script>
    <script>
        function showFields() {
            var attendanceType = document.getElementById("attendance-type").value;
            if (attendanceType == 1) {
                document.getElementById("attendance-time").style.display = "block";
                document.getElementById("departure-time").style.display = "block";
            } else if (attendanceType == 2) {
                document.getElementById("attendance-time").style.display = "block";
                document.getElementById("departure-time").style.display = "none";
            } else {
                document.getElementById("attendance-time").style.display = "none";
                document.getElementById("departure-time").style.display = "none";
            }
        }

        function convertToMonthYear(dateValue) {
            var date = new Date(dateValue);
            var monthYear = (date.getMonth() + 1).toString().padStart(2, '0') + "-" + date.getFullYear();
            document.getElementsByName("month_year")[0].value = monthYear;
        }
    </script>
@endsection
