<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.attachment_categories.store') }}"
      enctype="multipart/form-data">

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" value="" name="title" class="form-control" required>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.attachment_categories.store') ) !!}
        {!! closeButton() !!}
    </div>

</form>
