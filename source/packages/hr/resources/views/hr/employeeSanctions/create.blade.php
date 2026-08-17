<form class="row g-3 from-submit-global" method="post" action="{{route("employeeSanctions.store")}}" enctype="multipart/form-data">

    <div class="col-4" >
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Employee") }}</label>
        <select class="form-control show-tick ms select2" name="employee_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" data-shiftDays="{{isset($employee->shift->shift->month_days) ? $employee->shift->shift->month_days : '1'}}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.salary") }}  </label>
        <input type="text" readonly class="form-control salary">
    </div>

    <div class="col-4 d-none">
        <label for="TextInput" class="form-label">{{ __("hr::hr.salary") }} </label>
        <input type="text" readonly class="form-control month_days">
    </div>

    <div class="col-4">
        <label for="date" class="form-label">{{ __("hr::hr.date") }}  </label>
        <input type="date" name="date" class="form-control" data-validation="required" onchange="updateDays()">
    </div>


    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanctions") }} </label>
        {!! addUpButton(route('sanctions.create'),'sanction_id','' ,'title',false) !!}
        <select class="form-control show-tick ms" name="sanction_id" id="sanction_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            @foreach ($sanctions as $sanction)
                <option value="{{ $sanction->id }}">{{ $sanction->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.calculation_type") }}</label>
        <input type="hidden" readonly class="form-control type">
        <input type="hidden" readonly class="form-control valueSac">
        <input type="text" readonly class="form-control typeName">
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Penalty value") }}</label>
        <input type="text" name="value" readonly class="form-control valueEmp" value="0" data-validation="required">
    </div>
    <div class="col-12">
        <input type="checkbox" name="with_salary" value="1" checked>
        <label for="TextInput" class="form-label">{{ __("hr::hr.Is the salary deducted?") }}</label>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <textarea name="reason" class="form-control" data-validation="required" rows="4"></textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file"  name="file" class="dropify">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("employeeSanctions.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
@include('hr::hr.employeeSanctions.filter-js')
@include("hr::hr.sanctions._js_sanctions")
