<form class="row g-3 from-submit-global" method="post" action="{{ route('employeeAttendances.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Employee") }} </label>
        <select class="form-control show-tick ms" name="employee_id" data-validation="required">
            <option value=""> {{ __("hr::hr.Choose") }} {{ __("hr::hr.Employee") }} </option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ $employee->id == $obj->employee_id ? 'selected' : '' }}>{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4 d-none">
        <label for="date" class="form-label"> {{ __("hr::hr.date") }}   </label>
        <input type="date" name="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-4 d-none">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.type") }} </label>
        <select class="form-control show-tick ms" id="attendance-type" name="type" data-validation="required">
            {!! getEnumData($employee_attendances_type ,1) !!}
        </select>
    </div>

    <div class="col-4" style="display:none">
        <label for="attendance_time"  class="form-label"> {{ __("hr::hr.time attendance") }}:</label>
        <input type="time" value="{{ $obj->attendance_time }}" class="form-control" name="attendance_time">
    </div>

    <div class="col-4">
        <label for="departure_time"  class="form-label"> {{ __("hr::hr.Check-out time") }}:</label>
        <input type="time" class="form-control" name="leave_time">
    </div>

    <div class="col-12 d-none">
        <label for="date" class="form-label">  {{ __("hr::hr.Month and year") }}  </label>
        <input type="text" name="month_year" value="{{ $obj->month_year }}" readonly class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('employeeAttendances.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
