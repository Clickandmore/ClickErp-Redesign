<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('put')

    <input type="hidden" name="employee_id" value="{{ $employee_id }}">

    <div class="col-4">
        <label for="TextInput" class="form-label"> {{ __("hr::hr.the kind of holiday") }} </label>
        <select class="form-control show-tick ms" name="vacation_id" id="vacation_id" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }}</option>
            @foreach ($vacations as $vacation)
                <option value="{{ $vacation->id }}" {{ $vacation->id == $obj->vacation_id ? 'selected' : ''  }}>{{ $vacation->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("hr::hr.Balance") }} </label>
        <input type="text" id="value" name="value" value="{{ $obj->value }}" readonly class="form-control" data-validation="required">
    </div>

    <div class="col-4">
        <label for="from_date" class="form-label"> {{ __("hr::hr.From the date of") }}</label>
        <input type="date" id="from_date" value="{{ $obj->from_date }}" name="from_date" class="form-control" data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-4">
        <label for="to_date" class="form-label">{{ __("hr::hr.To date") }}</label>
        <input type="date" id="to_date" value="{{ $obj->to_date }}" name="to_date" class="form-control" data-validation="required" onchange="updateDays()">
    </div>

    <div class="col-4">
        <label for="days" class="form-label">{{ __("hr::hr.Date difference") }}</label>
        <input type="text" id="days" value="{{ $obj->days }}" name="days" readonly class="form-control" data-validation="required,lessThan">
    </div>


    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.comments") }} </label>
        <textarea rows="3" name="reason" class="form-control" data-validation="required">{{ $obj->reason }}</textarea>
    </div>

    <div class="col-6 form-group" id="vacation_file">
        <label for="vacation_file" class="form-label">{{ __("hr::hr.file") }}</label>
        <input type="file"  name="vacation_file" id="" class="dropify" data-default-file="{{ showFile($obj->vacation_file) }}">

    </div>
    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
