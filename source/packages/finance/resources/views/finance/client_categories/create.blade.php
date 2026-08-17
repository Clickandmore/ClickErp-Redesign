<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}" enctype="multipart/form-data">

        <div class="col-md-12">
            <label for="title" class="form-label">{{ __("finance::finance.name") }}<span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control" data-validation="required">
        </div>

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
