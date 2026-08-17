<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
    enctype="multipart/form-data">
    @method('put')

    <input type="hidden" name="complaint_id" value="{{$obj->complaint_id}}" id="">
    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.notes") }}  </label>
        <textarea name="text" class="form-control" cols="5" rows="4">{{ $obj->text }}</textarea>
    </div>


    <div class="col-md-6">
        <label for="TextInput" class="form-label"> {{ __("hr.date") }}  </label>
        <input type="date" name="date" value="{{ $obj->date }}" class="form-control" data-validation="required">
    </div>




    <div class="col-12 modal-footer">
        {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
