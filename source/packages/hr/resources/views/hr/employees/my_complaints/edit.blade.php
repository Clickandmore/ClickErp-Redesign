<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('put')
    <input type="hidden" name="employee_id" value="{{$obj->employee_id}}">

    <div class="col-md-4">
        <label for="TextInput" class="form-label"> {{ __("hr.directed_to") }}  </label>
        <select class="form-control show-tick ms select2" name="directed_to_id" data-validation="required">
            <option value=""> {{ __("hr.Choose") }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" {{ $employee->id == $obj->directed_to_id  ? "selected" : "" }}>{{ $employee->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label"> {{ __("hr.date") }}  </label>
        <input type="date" name="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>

    <div class="col-md-12">
        <label for="TextInput" class="form-label"> {{ __("hr.complaint_text") }}  </label>
        <textarea name="description" class="form-control" cols="5"  rows="4" data-validation="required">{{ $obj->description }}</textarea>
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
