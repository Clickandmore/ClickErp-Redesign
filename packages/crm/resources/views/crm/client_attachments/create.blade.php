<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.client_attachments.store') }}"
      enctype="multipart/form-data">

    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.title") }}</label>
        <input type="text" value="" name="title" class="form-control"  data-validation="required">
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
        <select class="form-control show-tick ms select2" name="user_id" data-validation="required" id="user_id">
            {!! showSelectElement($clients,"name" ) !!}
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.attach") }}
            {!! addUpButton(route('crm.attachment_categories.create'),'attachment_id' ,false, 'title') !!}</label>
        <select class="form-control show-tick ms select2" name="attachment_id" id="attachment_id" data-validation="required">
            {!! showSelectElement($attachments,"title" ) !!}
        </select>
    </div>
    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm::crm.file") }}</label>
        <input type="file" name="file" class="dropify" data-default-file="">
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.client_attachments.store') ) !!}
        {!! closeButton() !!}

    </div>

</form>
