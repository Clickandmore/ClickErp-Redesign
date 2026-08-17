<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

    <div class="col-md-12">
        <label for="title" class="form-label">{{ __("finance::finance.title") }}</label>
        <input type="text" id="title" name="title" class="form-control" data-validation="required">
    </div>
    <div class="col-12 modal-footer">
         {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
