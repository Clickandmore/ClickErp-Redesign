<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}"
      enctype="multipart/form-data">

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.title") }}</label>
        <input type="text" value="" name="title" class="form-control"   data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.details") }}</label>
        <textarea type="text" name="description" class="form-control form-control-lg mb-1" id="" data-validation="required"
                  rows="3"></textarea>
    </div>

    <div class="col-8">
        <label for="TextInput" class="form-label">{{ __("crm.end_date") }}</label>
        <input type="date" name="end_date" class="form-control form-control-lg mb-1" id="">
    </div>

    <div class="col-4">
        <label for="TextInput" class="form-label">{{ __("crm.time") }} </label>
        <input type="time" name="end_time" class="form-control form-control-lg mb-1">
    </div>


    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr.employees") }}</label>
        <select class="form-control show-tick ms select2" multiple="multiple" name="employee_ids[]" data-validation="required">
            {!! showSelectElement($employees,"name" ) !!}
        </select>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.attach") }}</label>
        <input type="file" class="dropify" name="file">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
