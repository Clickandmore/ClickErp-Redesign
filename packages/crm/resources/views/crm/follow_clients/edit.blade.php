<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.follow-clients.update', $obj->id) }}"
      enctype="multipart/form-data">
    @method('PUT')
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.clients") }}
        </label>
        <select class="form-control show-tick select2" name="client_id"  data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($clients as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->client_id) ? 'selected' : '')}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("hr.Employee") }}</label>
        <select class="form-control show-tick select2" name="admin_id"  data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->admin_id) ? 'selected' : '')}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.follow_clients_category") }}</label>
        <select class="form-control show-tick select2" name="follow_clients_category_id"  data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($categoriess as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->follow_clients_category_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.date") }}</label>
        <input type="date" value="{{ $obj->date }}" name="date" class="form-control"  data-validation="required">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.content") }}</label>
        <textarea name="content" id="content" cols="20" class="form-control"  data-validation="required"
                  rows="6">{{ $obj->content }}</textarea>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.notes") }}</label>
        <textarea name="notes" id="notes" cols="20" class="form-control"  data-validation="required" rows="6">{{ $obj->notes }}</textarea>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.result") }}</label>
        <textarea name="result" id="result" cols="20" class="form-control" rows="6">{{ $obj->result }}</textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('crm.follow-clients.update', $obj->id)) !!}
        {!! closeButton() !!}
    </div>

</form>
