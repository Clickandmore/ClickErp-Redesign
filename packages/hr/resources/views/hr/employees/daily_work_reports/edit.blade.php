<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')


   <input type="hidden" name="employee_id" value="{{$obj->employee_id}}">
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._title"). ' ' .__("hr::hr.report") }} </label>
        <input type="text" value="{{$obj->title}}" name="title" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._date"). ' ' .__("hr::hr.report") }}</label>
        <input type="date" name="date" value="{{$obj->date}}" class="form-control" >
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr._description"). ' ' .__("hr::hr.report") }}</label>
        <textarea name="description" id="description" class="form-control" cols="15" rows="5"
                  data-validation="required">{{$obj->description}}</textarea>
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr::hr.attachments") }}</label>
        <input type="file"
               name="file" class="dropify" data-default-file="{{showFile($obj->file)}}">
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
