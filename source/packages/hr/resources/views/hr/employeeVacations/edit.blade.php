<form class="row g-3 from-submit-global" method="post" action="{{ route('employeeVacations.update', $obj->id) }}"
      enctype="multipart/form-data">
    @method('put')
    @if($obj->status->value == 0)
        <div class="col-4">
            <label for="TextInput" class="form-label">{{ __("hr::hr.Employee") }}</label>
            <select class="form-control show-tick ms select2" name="employee_id"
                    id="employee_id" data-validation="required">
                <option value="">{{ __("hr::hr.Choose") }}</option>
                @foreach ($employees as $employee)
                    <option
                        value="{{ $employee->id }}" {{ $employee->id == $obj->employee_id ? 'selected' : ''  }}>{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <div class="col-4">
            <label for="TextInput" class="form-label">{{ __("hr::hr.Employee") }}</label>
            <input type="hidden" value="{{$obj->employee_id}}" name="employee_id">
            <input type="text" class="form-control" disabled value="{{$obj->employee->name}}">
        </div>
    @endif

    @if($obj->status->value == 0)
    <div class="col-4">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.the kind of holiday") }} </label>
        {!! addUpButton(route('vacations.create'),'vacation_id','' ,'title',false) !!}
        <select {{$obj->status->value != 0 ? 'readonly' : ''}} class="form-control show-tick ms" name="vacation_id"
                id="vacation_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($vacations as $vacation)
                <option
                    value="{{ $vacation->id }}" {{ $vacation->id == $obj->vacation_id ? 'selected' : ''  }}>{{ $vacation->title }}</option>
            @endforeach
        </select>
    </div>
    @else
        <div class="col-4">
            <label for="TextInput" class="form-label">{{ __("hr::hr.the kind of holiday") }}</label>
            <input type="text" class="form-control"  disabled value="{{$obj->vacation->title}}">
            <input type="hidden" class="form-control" name="vacation_id" value="{{$obj->vacation_id}}" placeholder="{{$obj->vacation->title}}">
        </div>
    @endif

    <div class="col-4 form-group" id="balance_div">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Balance") }}  </label>
        <input {{$obj->status->value != 0 ? 'readonly' : ''}} type="text" id="value" name="value" value="{{ $obj->value }}"
               readonly class="form-control"
               data-validation="required">
    </div>

    <div class="col-4 form-group" id="from_date_div">
        <label for="from_date" class="form-label">{{ __("hr::hr.From the date of") }}</label>
        <input {{$obj->status->value != 0 ? 'readonly' : ''}} type="date" id="from_date" value="{{ $obj->from_date }}"
               name="from_date" class="form-control"
               data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-4 form-group" id="to_date_div">
        <label for="to_date" class="form-label">{{ __("hr::hr.To date") }}</label>
        <input {{$obj->status->value != 0 ? 'readonly' : ''}} type="date" id="to_date" value="{{ $obj->to_date }}"
               name="to_date" class="form-control"
               data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-4 form-group" id="days_div">
        <label for="days" class="form-label">{{ __("hr::hr.Date difference") }}</label>
        <input {{$obj->status->value != 0 ? 'readonly' : ''}} type="text" id="days" value="{{ $obj->days }}" name="days"
               readonly class="form-control"
               data-validation="required,lessThan">
    </div>

    <div class="col-12 form-group" id="reason_div">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <input type="text" name="reason" value="{{ $obj->reason }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12 form-group" id="file_div">
        <label for="vacation_file" class="form-label">{{ __("hr::hr.vacation_file") }}</label>
        <input type="file" name="vacation_file" class="dropify" data-default-file="{{showFile($obj->vacation_file)}}">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('employeeVacations.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
<script>
    $(document).ready(function () {

        $('#employee_id, #vacation_id').on('change', function () {
            var employee_id = $('#employee_id').val();
            var vacation_id = $('#vacation_id').val();

            if (employee_id && vacation_id) {
                $.ajax({
                    url: '{{ route("employeeVacationBalance.show", ["employeeVacationBalance" => 0]) }}' + '?employee_id=' + employee_id + '&vacation_id=' + vacation_id,
                    type: 'GET',
                    success: function (data) {
                        if (data.error) {
                            warningToster(data.error);
                            // Swal.fire({
                            //     title: 'تحذير',
                            //     text: data.error,
                            //     icon: 'warning',
                            //     showCancelButton: false,
                            //     confirmButtonColor: '#3085d6',
                            //     cancelButtonColor: '#d33',
                            //     confirmButtonText: 'فهمت',
                            // });
                            // Hide elements on error
                            $('#from_date_div, #to_date_div, #days_div, #reason_div,#file_div, button[type="submit"]').hide();
                            $('input[name="value"]').val(0);
                        } else {
                            var remain_days = data.remain_days;
                            $('input[name="value"]').val(remain_days);

                            // Show or hide elements based on remaining days
                            if (remain_days > 0) {
                                $('#from_date_div, #to_date_div, #days_div, #reason_div,#file_div, button[type="submit"]').show();
                            } else {
                                $('#from_date_div, #to_date_div, #days_div, #reason_div,#file_div, button[type="submit"]').hide();
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });
</script>
