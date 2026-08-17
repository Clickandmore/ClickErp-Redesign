<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __('finance::finance.cancel_refuse') }} </label>
        <input type="date" name="date" class="form-control" value="{{date('Y-m-d')}}" data-validation="required">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __('finance::finance.cancel_refuse') }} </label>
        <textarea rows="3" type="text" name="refuse_reason" class="form-control"  data-validation="required">

        </textarea>
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>
</form>

