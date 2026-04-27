<form class="row g-3 from-submit-global" method="post" action="{{route("insurances.store")}}" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.name_of_the_slice") }}</label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr::hr.calculation method") }}</label>
        <select class="form-control show-tick ms" name="type" data-validation="required">
            <option value="">{{ __("hr::hr.Choose") }} </option>
            <option value="0">{{ __("hr::hr.percentage_of_salary") }}</option>
            <option value="1">{{ __("hr::hr.fixed_amount") }}</option>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">{{ __("hr::hr.The value of the total insurance segment") }}</label>
        <input type="text" class="form-control" name="value" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("hr::hr.The value of the employee’s insurance segment") }}</label>
        <input type="text" class="form-control" name="emp_value" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __("hr::hr.The value of the company's insurance segment") }}</label>
        <input type="text" class="form-control" name="company_value" data-validation="required" readonly>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route("insurances.store") ) !!}
        {!! closeButton() !!}
    </div>

</form>


