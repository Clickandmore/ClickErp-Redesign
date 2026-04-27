<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.social_contacts.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" name="title" value="{{$obj->title}}" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.social_contacts.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
