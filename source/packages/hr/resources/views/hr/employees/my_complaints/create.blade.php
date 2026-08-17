<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}"
      enctype="multipart/form-data">
    <input type="hidden" name="employee_id" value="{{ $employee_id }}">
    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.directed_to") }}  </label>
        <select class="form-control show-tick ms select2" name="directed_to_id" data-validation="required">
            <option value=""> {{ __("hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.date") }}  </label>
        <input type="date" name="date" value="{{date('Y-m-d')}}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-12">
        <label for="TextInput" class="form-label"> {{ __("hr.complaint_text") }}  </label>
        <textarea name="description" class="form-control" cols="15" rows="4" data-validation="required"></textarea>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>


