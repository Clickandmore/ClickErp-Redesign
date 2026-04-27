<form class="row g-3 from-submit-global"
    enctype="multipart/form-data">
    {{-- @method('PUT') --}}
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
        <input type="text" name="name" disabled value="{{ $obj->name }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.phone") }}</label>
        <input type="text" name="phone" value="{{ $obj->phone }}" class="form-control" disabled>
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.email") }}</label>
        <input type="text" name="email" value="{{ $obj->email }}" class="form-control" disabled>
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.client_whatsapp") }}</label>
        <input type="number" name="whatsapp" value="{{ $obj->whatsapp }}" class="form-control" disabled>
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.Notes") }}</label>
        <input type="text" name="notes" value="{{ $obj->notes }}" class="form-control" disabled>
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.client_code") }}</label>
        <input type="text" name="code" value="{{ $obj->code }}" class="form-control" disabled>
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.address") }}</label>
        <input type="text" name="address" value="{{ $obj->address }}" class="form-control" disabled>
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.social_contacts") }}</label>
        <select disabled class="form-control show-tick select2" name="social_contact_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($social_contacts as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->social_contact_id) ? 'selected' : '')}}>{{ getFinalTrans('title',$item) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.cities") }}</label>
        <select  disabled class="form-control show-tick select2" name="city_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($cities as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->city_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.areas") }}</label>
        <select disabled class="form-control show-tick select2" name="area_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($areas as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->area_id) ? 'selected' : '')}}>{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.employee_in_charge") }}</label>
        <select disabled class="form-control show-tick select2" name="user_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}" {{(($item->id==$obj->user_id) ? 'selected' : '')}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.categories") }}</label>
        <select disabled class="form-control show-tick ms select2" name="category_id[]" multiple>
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}" {{$obj->hasCategory($item->id) ? 'selected' : ''}}>{{ $item->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">
{{--        {!! submitButton( route('crm::crm.user-polls.store') ) !!}--}}
        {!! closeButton() !!}
    </div>

</form>
