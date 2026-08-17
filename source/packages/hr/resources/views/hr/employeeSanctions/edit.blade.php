<form class="row g-3 from-submit-global" method="post" action="{{ route('employeeSanctions.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-4">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Employee") }}</label>
        <select class="form-control show-tick ms" name="employee_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ $employee->id == $obj->employee_id ? 'selected' : ''  }}>{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.salary") }} </label>
        <input type="text" readonly class="form-control salary" value="{{$obj->employee->net_salary}}">
    </div>

    <div class="col-4 d-none">
        <label for="TextInput" class="form-label">{{ __("hr::hr.salary") }} </label>
        <input type="text" readonly class="form-control month_days" value="{{$obj->employee->shift->shift->month_days}}">
    </div>

    <div class="col-4">
        <label for="from_date" class="form-label"> {{ __("hr::hr.date") }} </label>
        <input type="date" value="{{ $obj->date }}" name="date" class="form-control" data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label">{{ __("hr::hr.sanctions") }} </label>
        {!! addUpButton(route('sanctions.create'),'sanction_id','' ,'title',false) !!}
        <select class="form-control show-tick ms" name="sanction_id" id="sanction_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            @foreach ($sanctions as $sanction)
                <option value="{{ $sanction->id }}" {{ $sanction->id == $obj->sanction_id ? 'selected' : ''  }}>{{ $sanction->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.calculation_type") }} </label>
        <input type="hidden" readonly class="form-control type">
        <input type="hidden" readonly class="form-control valueSac" value="{{$obj->sanction->value}}">
        <input type="text" readonly class="form-control typeName" value="{{getEnumValue("allowance_typeis", $obj->sanction->type) . ' = ' . $obj->sanction->value }}">
    </div>

    <div class="col-3">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Penalty value") }} </label>
        <input type="text" name="value" readonly class="form-control valueEmp" value="{{ $obj->value }}" data-validation="required">
    </div>

    <div class="col-12">
        <input type="checkbox" value="1" {{ $obj->with_salary == 1 ? 'checked' : '' }} name="with_salary">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Is the salary deducted?") }}</label>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <textarea name="reason"  class="form-control" data-validation="required" rows="4">{{ $obj->reason }}</textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file"  name="file" data class="dropify"  data-default-file="{{showFile($obj->file)}}">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('employeeSanctions.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
@include('hr::hr.employeeSanctions.filter-js')
@include("hr::hr.sanctions._js_sanctions")
