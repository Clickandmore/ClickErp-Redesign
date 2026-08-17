<form class="row g-3 from-submit-global" method="post" action="{{route("employeePermissions.store")}}" enctype="multipart/form-data">

    <div class="col-6">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Employee") }}</label>
        <select class="form-control show-tick ms select2" name="employee_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.Permission") }} </label>
        {!! addUpButton(route('permissions.create'),'permission_id','' ,'title',false) !!}
        <select class="form-control show-tick ms" name="permission_id" id="permission_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($permissions as $permission)
                <option value="{{ $permission->id }}">{{ $permission->title }}</option>
            @endforeach
        </select>
    </div>


    <div class="col-6">
        <label for="date" class="form-label">{{ __("hr::hr.permission_date") }} </label>
        <input type="date" name="date" class="form-control" data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-6">
        <label for="from_time" class="form-label">{{ __("hr::hr.permission_from_time") }} </label>
        <input type="time" name="from_time" class="form-control" id="from_time" data-validation="required">
    </div>
    <div class="col-6">
        <label for="to_time" class="form-label">{{ __("hr::hr.permission_to_time") }} </label>
        <input type="time" name="to_time" class="form-control" id="to_time" data-validation="required">
    </div>

    <div class="col-6">
        <label for="time" class="form-label">{{ __("hr::hr.Duration of permission in minutes") }}</label>
        <input type="number" readonly id="time" name="time" class="form-control" data-validation="required">
    </div>


    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <textarea name="reason" class="form-control" data-validation="required" rows="4"></textarea>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.attached") }}</label>
        <input type="file"  name="file" class="dropify">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("employeePermissions.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>
