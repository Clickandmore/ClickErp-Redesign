<form class="row g-3 from-submit-global" method="post" action="{{ route('airports.update', $obj->id) }}">
    @csrf
    @method('PUT')

    <div class="col-12">
        <label for="name" class="form-label">اسم المطار</label>
        <input type="text" name="name" id="name" value="{{ $obj->name }}" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label for="country_id" class="form-label">الدولة</label>
        <select name="country_id" id="country_id" class="form-select form-control" data-validation="required">
            <option value="">{{ helperTrans('ingaz.choose') }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected($obj->country_id == $country->id)>{{ $country->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label for="city_id" class="form-label">المدينة</label>
        <select name="city_id" id="city_id" class="form-select form-control" data-validation="required">
            <option value="">{{ helperTrans('ingaz.choose') }}</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected($obj->city_id == $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
    </div>
</form>
