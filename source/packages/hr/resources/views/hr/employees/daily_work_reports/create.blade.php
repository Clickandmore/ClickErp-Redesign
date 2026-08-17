<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}"
      enctype="multipart/form-data">
    <input type="hidden" name="employee_id" value="{{ $employee_id }}">

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._title"). ' ' .__("hr::hr.report") }} </label>
        <input type="text" name="title" class="form-control">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._date"). ' ' .__("hr::hr.report") }}</label>
        <input type="date" name="date" class="form-control">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._description"). ' ' .__("hr::hr.report") }}</label>
        <textarea name="description" id="description" class="form-control" cols="15" rows="7"
                  data-validation="required"></textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.attachments") }}</label>
        <input type="file"
               name="file" class="dropify" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
