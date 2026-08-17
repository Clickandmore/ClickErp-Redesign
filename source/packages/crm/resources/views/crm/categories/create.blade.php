<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.categories.store') }}"
      enctype="multipart/form-data">

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }} </label>
        <input type="text" name="name" class="form-control"
               data-validation="required" >
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.categories.store') ) !!}
        {!! closeButton() !!}
    </div>

</form>
