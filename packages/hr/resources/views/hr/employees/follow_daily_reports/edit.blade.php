<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')


    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.Employee") }}</label>
        <select name="employee_id" class="form-control" >
            {!! showSelectElement($employees, 'name', $obj->employee_id) !!}
        </select>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.title") }}</label>
        <input type="text" value="{{$obj->title}}" name="title" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.date") }}</label>
        <input type="date" name="date" value="{{$obj->date}}" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm.description") }}</label>
        <textarea name="description" id="description" class="form-control" cols="15" rows="5"
                  data-validation="required">{{$obj->description}}</textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm.attachments") }}</label>
        <input type="file"
               name="file" class="dropify" data-default-file="{{showFile($obj->file)}}">
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
