<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}"
      enctype="multipart/form-data">

    <input type="hidden" value="{{$employee_id}}" name="employee_id" >
    <input type="hidden" value="{{request('task_id')}}" name="task_id" >

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.title") }}</label>
        <input type="text" value="" name="title" class="form-control" required>
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.details") }}</label>
        <textarea type="text" name="description" class="form-control form-control-lg mb-1" id=""
                  rows="3"></textarea>
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.file") }}</label>
        <input type="file" class="dropify" name="file">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
