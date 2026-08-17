<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.confirm-clients.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
        <input type="text" name="name" value="{{ $obj->name }}"  class="form-control"  data-validation="required">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.email") }}</label>
        <input type="text" name="email" value="{{ $obj->email }}"  data-validation="required" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.password") }}</label>
        <input type="password" name="password"  data-validation="required" class="form-control">
    </div>


    <div class="col-12 modal-footer">
        {!! submitButton( route('crm::crm.confirm-clients.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
