<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.clients.update', $obj->id) }}"
      enctype="multipart/form-data">
    @method('PUT')
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
        <input type="text" name="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.phone") }}</label>
        <input type="text" name="phone" value="{{ $obj->phone }}" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">الرقم الضريبي</label>
        <input type="text" name="vat_number" value="{{ $obj->vat_number }}" class="form-control"
               data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.email") }}</label>
        <input type="text" name="email" value="{{ $obj->email }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.client_whatsapp") }}</label>
        <input type="number" name="whatsapp" value="{{ $obj->whatsapp }}" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.Notes") }}</label>
        <input type="text" name="notes" value="{{ $obj->notes }}" class="form-control">
    </div>

    {{--    <div class="col-md-4">--}}
    {{--        <label for="TextInput" class="form-label">{{ __("crm::crm.client_code") }}</label>--}}
    {{--        <input type="text" name="code" value="{{ $obj->code }}" class="form-control">--}}
    {{--    </div>--}}

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.address") }}</label>
        <input type="text" name="address" value="{{ $obj->address }}" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">
            {{ __("crm::crm.social_contacts") }}
            {!! addUpButton(route('crm.social_contacts.create'),'social_contact_id' ,false, 'title') !!}
        </label>
        <select class="form-control show-tick select2" name="social_contact_id" id="social_contact_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($social_contacts as $item)
                <option
                    value="{{ $item->id }}" {{(($item->id==$obj->social_contact_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.cities") }}
            {!! addUpButton(route('crm.areas.create'),'city_id' ,false, 'title') !!}
        </label>
        <select class="form-control show-tick select2" name="city_id" id="city_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($cities as $item)
                <option
                    value="{{ $item->id }}" {{(($item->id==$obj->city_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.areas") }}
            {!! addUpButton(route('crm.areas.create'),'area_id' ,false, 'title') !!}
        </label>
        <select class="form-control show-tick select2" name="area_id" id="area_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($areas as $item)
                <option
                    value="{{ $item->id }}" {{(($item->id==$obj->area_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.employee_in_charge") }}
            {!! addUpButton(route('admins.create'),'admin_id' ,false, 'name') !!}
        </label>
        <select class="form-control show-tick select2" name="admin_id" id="admin_id">
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($users as $item)
                <option
                    value="{{ $item->id }}" {{(($item->id==$obj->admin_id) ? 'selected' : '')}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.categories") }}
            {!! addUpButton(route('crm.categories.create'),'category_id' ,false, 'name') !!}
        </label>
        <select class="form-control show-tick ms select2" name="category_id[]" multiple id="category_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($categories as $item)
                <option
                    value="{{ $item->id }}" {{$obj->hasCategory($item->id) ? 'selected' : ''}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( route('crm.clients.update', $obj->id) ) !!}
        {!! closeButton() !!}
    </div>

</form>
