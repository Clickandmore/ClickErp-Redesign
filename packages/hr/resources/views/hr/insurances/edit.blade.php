<form class="row g-3 from-submit-global" method="post" action="{{ route('insurances.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('put')
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name_of_the_slice") }}</label>
        <input type="text" name="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
        <input type="hidden" name="insurances_id" value="{{ $obj->id }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.calculation method") }}</label>
        <select class="form-control show-tick ms" name="type" data-validation="required">
            <option>{{ __("hr::hr.Choose") }}</option>
            <option value="0" {{$obj->type == 0 ? 'selected' : ''}} >{{ __("hr::hr.percentage_of_salary") }}</option>
            <option value="1" {{$obj->type == 1 ? 'selected' : ''}} >{{ __("hr::hr.fixed_amount") }}</option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">{{ __("hr::hr.The value of the total insurance segment") }}</label>
        <input type="text" class="form-control" value="{{ $obj->value }}" name="value" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("hr::hr.The value of the employee’s insurance segment") }}</label>
        <input type="text" class="form-control" value="{{ $obj->emp_value }}" name="emp_value" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("hr::hr.The value of the company's insurance segment") }}</label>
        <input type="text" class="form-control" value="{{ $obj->company_value }}" name="company_value" readonly data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('insurances.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
