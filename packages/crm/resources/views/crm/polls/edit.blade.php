<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.polls.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" value="{{$obj->title}}" name="title" class="form-control"  data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('crm.polls.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
