<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.categories.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')

        <div class="col-12">
            <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
            <input type="text" name="name" value="{{ $obj->name }}" class="form-control"  data-validation="required" >
        </div>
    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.categories.update', $obj->id) ) !!}
        {!! closeButton() !!}

    </div>

</form>
