<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-12">
        <label for="title" class="form-label">{{ __("finance::finance.name") }}</label>
        <input type="text" name="title" id="title" value="{{ $obj->title }}" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
