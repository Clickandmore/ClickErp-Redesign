<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.follow-clients.store') }}"
    enctype="multipart/form-data">
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.clients") }}
            {!! addUpButton(route('crm.clients.create'),'client_id' ,false, 'name') !!}</label>
        <select class="form-control show-tick select2" name="client_id" id="client_id" data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($clients as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.employee") }}
            {!! addUpButton(route('admins.create'),'admin_id' ,false, 'name') !!}</label>
        <select class="form-control show-tick select2" name="admin_id"  id="admin_id" data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
     <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.follow_clients_category") }}
            {!! addUpButton(route('crm.follow_clients_categories.create'),'follow_clients_category_id' ,false, 'title') !!}</label>
        <select class="form-control show-tick select2" name="follow_clients_category_id" id="follow_clients_category_id"  data-validation="required">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($categoriess as $item)
                <option value="{{ $item->id }}">{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.date") }}</label>
        <input type="date" name="date" class="form-control"   data-validation="required">
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.content") }}</label>
        <textarea name="content" id="content" cols="20" class="form-control"  rows="6"   data-validation="required"></textarea>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.Notes") }}</label>
        <textarea name="notes" id="notes" cols="20" class="form-control"  rows="6"   data-validation="required"></textarea>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __("crm::crm.result") }}</label>
        <textarea name="result" id="result" cols="20" class="form-control"  rows="6"></textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(route('crm.follow-clients.store')) !!}
        {!! closeButton() !!}
    </div>

</form>
