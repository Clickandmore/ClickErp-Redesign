@extends('click.layout.indexs.index')
@section("style")

@endsection
@section('page-title')
    {{__('hr::hr.employeeAttendances')}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('style')

@endsection
@section('content')
{{--    <div class="row">--}}
{{--        <div class="col-12">--}}
{{--            <form class="row g-3 from-submit-global" method="post" action="{{route("mluti-employee.store")}}" enctype="multipart/form-data">--}}
{{--                <table id="myDataTable_no_filter" class="table align-middle card-table">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th></th>--}}
{{--                        <th>{{ __("hr.Employee") }}</th>--}}
{{--                        <th>  {{ __("hr.date") }}</th>--}}
{{--                        <th> {{ __("hr.time attendance") }}</th>--}}
{{--                        <th> {{ __("hr.Check-out time") }}</th>--}}
{{--                        <th>{{__("hr.actions")}}</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody id="json_array1" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>--}}
{{--                        <tr class="y-group">--}}
{{--                            <td> </td>--}}
{{--                            <td>--}}
{{--                                <select class="form-control show-tick ms" name="employee_id[]" data-validation="required">--}}
{{--                                    <option value=""> {{ __("hr.Choose") }} {{ __("hr.Employee") }} </option>--}}
{{--                                    @foreach ($employees as $employee)--}}
{{--                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>--}}
{{--                                    @endforeach--}}
{{--                                </select>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <input type="date" name="date[]" data-validation="required" class="form-control">--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <input type="time" name="attendance_time[]" data-validation="required" class="form-control">--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <input type="time" name="leave_time[]" data-validation="required" class="form-control">--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <i class="fa fa-plus-circle" id="btnAdd-2"></i>--}}
{{--                                <i class="fa fa-trash-o btnRemove"></i>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                    </tbody>--}}
{{--                </table>--}}

{{--                <div class="col-12 modal-footer">--}}
{{--                     {!! submitButton( route("mluti-employee.store") ) !!}--}}
{{--                    {!! closeButton() !!}--}}
{{--                </div>--}}

{{--            </form>--}}
{{--        </div>--}}
{{--    </div>--}}
<div class="row">
    <div class="col-3 my-3">
        <label>{{ __("hr::hr.department") }}</label>
        <select name="department_id" id="department_id" class="form-control select2">
            <option value="">{{ __("hr::hr.Select department") }}</option>
            @foreach ($departments as $item)
                <option value="{{ $item->id }}">{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-2 my-3">
        <label>{{ __("hr::hr.date") }}</label>
        <input type="date" id="date" data-validation="required" class="form-control">
    </div>
    <div class="col-2 my-3">
        <label>{{ __("hr::hr.time attendance") }}</label>
        <input type="time" id="attendance_time" data-validation="required"
               class="form-control">
    </div>
    <div class="col-2 my-3">
        <label>{{ __("hr::hr.Check-out time") }}</label>
        <input type="time" id="leave_time" data-validation="required"
               class="form-control">
    </div>
</div>


{{-- </form> --}}
<div class="row">
    <div class="col-12">
        <form class="row g-3 from-submit-global" method="post" action="{{route("mluti-employee.store")}}"
              enctype="multipart/form-data">
            <table id="myDataTable_no_filter" class="table align-middle card-table">
                <thead>
                <tr>
                    <th>{{ __("hr::hr.Employee") }}</th>
{{--                    <th>  {{ __("hr::hr.shift") }}</th>--}}
                    <th>  {{ __("hr::hr.date") }}</th>
                    <th>  {{ __("hr::hr.time attendance") }}</th>
                    <th>  {{ __("hr::hr.Check-out time") }}</th>
                    <th>{{ __("hr::hr.actions") }}</th>
                </tr>
                </thead>
                <tbody id="json_array1" class="content"
                       data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
                @if ($employees->count() > 0)
                    @foreach ($employees as $index => $item)
                        <tr class="y-group">
                            <td>
                                <select class="form-control show-tick ms" name="employee_id[]"
                                        data-validation="required">
                                    <option value=""> {{ __("hr::hr.Choose") }} </option>
                                    @foreach ($allEmployees as $allEmployee)
                                        <option value="{{ $allEmployee->id }}" {{ $allEmployee->id == $item->id ? "selected" : "" }}>{{ $allEmployee->name }}</option>
                                    @endforeach
                                </select>
                            </td>
{{--                            <td>--}}
{{--                                <input type="text" readonly disabled--}}
{{--                                       value="{{$item->shift->shift->name ?? 'لم يحدد !'}}" class="form-control">--}}
{{--                            </td>--}}
                            <td>
                                <input type="date" name="date[]" data-validation="required" class="form-control">
                            </td>
                            <td>
                                <input type="time" name="attendance_time[]" data-validation="required"
                                       class="form-control">
                            </td>
                            <td>
                                <input type="time" name="leave_time[]" data-validation="required"
                                       class="form-control">
                            </td>
                            <td>
                                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
{{--                                <i class="fa fa-trash btnRemove"></i>--}}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" style="text-align: center">
                            لا يوجد بيانات
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>

            <div class="col-12 modal-footer">
                {!! submitButton( route("mluti-employee.store") ) !!}
{{--                <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>--}}
            </div>

        </form>
    </div>
</div>
@endsection
@section('js')
<script>
  $('#json_array1').multifield();


  $(document).ready(function () {
      $('#department_id').on('change', function (event) {
          event.preventDefault();
          var department_id = $('#department_id').val();
          $.ajax({
              url: '{{ route("mluti-employee.index") }}',
              type: 'GET',
              data: {department_id: department_id},
              beforeSend: function () {
                  $('#json_array1').html('');
              },
              success: function (response) {
                  $('#json_array1').html(response.table);
              },
              error: function (xhr, status, error) {
                  alert('حدث خطأ: ' + error);
              }
          });
      });
  });

  $(document).ready(function () {
      $('#date').on('change', function () {
          var newDate = $(this).val();
          $('input[name="date[]"]').val(newDate);
      });

      $('#attendance_time').on('change', function () {
          var newAttendanceTime = $(this).val();
          $('input[name="attendance_time[]"]').val(newAttendanceTime);
      });

      $('#leave_time').on('change', function () {
          var newLeaveTime = $(this).val();
          $('input[name="leave_time[]"]').val(newLeaveTime);
      });
  });
  $.validate({
            ignore: 'input[type=hidden]',
            modules: 'date, security',
            lang: '{{\App::currentLocale()}}',
            validateOnEvent: true
        });
</script>
@endsection
