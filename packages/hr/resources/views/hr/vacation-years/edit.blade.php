<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")
    <div class="col-12">
        <label for="title" class="form-label">{{ __('hr::hr.title') }} </label>
        <input type="text" name="title" id="title" class="form-control" data-validation="required" value="{{$obj->title}}">
    </div>

    <div class="col-12">
        <label for="from_date" class="form-label">{{ __('hr::hr.from_date') }}</label>
        <input type="date" name="from_date" id="from_date" class="form-control" data-validation="required" value="{{$obj->from_date}}">
    </div>

    <div class="col-12">
        <label for="to_date" class="form-label">{{ __('hr::hr.to_date') }}</label>
        <input type="date" name="to_date" id="to_date" class="form-control" data-validation="required" value="{{$obj->to_date}}">
    </div>
    <div class="col-12 modal-footer">
         {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
