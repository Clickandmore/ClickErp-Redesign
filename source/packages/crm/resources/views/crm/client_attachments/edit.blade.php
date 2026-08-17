<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.client_attachments.update', $obj->id) }}"
    enctype="multipart/form-data">
    @method('PUT')

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" value="{{$obj->title}}" name="title" class="form-control"  data-validation="required">
    </div>

    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{ __("crm::crm.client") }}
            @if (Route::has('clients.create'))
                <a href="{{ route('clients.create') }}" target="_blank" title="{{ __('buttons.add button') }}" class="text-primary">
                    <i class="fa fa-plus-circle"></i>
                </a>
            @endif
        </label>
        <select class="form-control show-tick ms select2" name="user_id" data-validation="required">
            {!! showSelectElement($clients,"name",$selectedUserId ?? $obj->user_id ) !!}
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.attach") }}</label>
        <select class="form-control show-tick ms select2" name="attachment_id" data-validation="required">
            {!! showSelectElement($attachments,"title",$obj->attachment_id ) !!}
        </select>
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.file") }}</label>
        <input type="file" name="file" class="dropify" data-default-file="{{showFile($obj->file)}}">
        <a href="{{showFile($obj->file)}}" download="">
            <i class="fa fa-download" aria-hidden="true"></i>
        </a>
        <a href="{{showFile($obj->file)}}" target="_blank">
            <i class="fa fa-eye" aria-hidden="true"></i>
        </a>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('crm.client_attachments.update', $obj->id)) !!}
        {!! closeButton() !!}

    </div>

</form>
