<form class="row g-3 from-submit-global" method="post" action="" enctype="multipart/form-data">
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("hr.name") }} </label>
        <input type="text" name="title" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( ) !!}
        {!! closeButton() !!}
    </div>

</form>
