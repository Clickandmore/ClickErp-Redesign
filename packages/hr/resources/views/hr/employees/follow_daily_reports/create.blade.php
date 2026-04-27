<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}"
      enctype="multipart/form-data">

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.Employee") }}</label>
        <select name="employee_id" class="form-control" >
            {!! showSelectElement($employees, 'name') !!}
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.title") }}</label>
        <input type="text" name="title" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.date") }}</label>
        <input type="date" name="date" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm.description") }}</label>
        <textarea name="description" id="description" class="form-control" cols="15" rows="7"
                  data-validation="required"></textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm.attachments") }}</label>
        <input type="file"
               name="file" class="dropify" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
