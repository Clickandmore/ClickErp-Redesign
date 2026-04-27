<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.clients.store') }}" enctype="multipart/form-data">
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.name") }}</label>
        <input type="text" name="name" class="form-control"  data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.phone") }}</label>
        <input type="text" name="phone" class="form-control"  data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">الرقم الضريبي</label>
        <input type="text" name="vat_number" class="form-control"  data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.email") }}</label>
        <input type="text" name="email" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.client_whatsapp") }}</label>
        <input type="number" name="whatsapp" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.Notes") }}</label>
        <input type="text" name="notes" class="form-control">
    </div>

{{--    <div class="col-md-4">--}}
{{--        <label for="TextInput" class="form-label">{{ __("crm::crm.client_code") }}</label>--}}
{{--        <input type="text" name="code" class="form-control">--}}
{{--    </div>--}}

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm::crm.address") }}</label>
        <input type="text" name="address" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">
            {{ __("crm::crm.social_contacts") }}
            {!! addUpButton(route('crm.social_contacts.create'),'social_contact_id' ,false, 'title') !!}
        </label>
        <select class="form-control show-tick select2" name="social_contact_id" id="social_contact_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($social_contacts as $item)
                <option value="{{ $item->id }}">{{ $item->title }}</option>
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
                <option value="{{ $item->id }}">{{ $item->title }}</option>
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
                <option value="{{ $item->id }}">{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm::crm.employee_in_charge") }}
            {!! addUpButton(route('admins.create'),'admin_id' ,false, 'name') !!}
        </label>
        <select class="form-control show-tick select2" name="admin_id" id="admin_id">
            <option value=""> {{ __("crm::crm.Choose") }} </option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
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
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12" style="padding-top: 20px !important;" id="dataaa">
        <h4>{{__('crm::crm.attachments')}}</h4>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th scope="row">{{ __("crm::crm.attach_type") }}</th>
                <th scope="row">{{ __("crm::crm.title") }}</th>
                <th scope="row">{{ __("crm::crm.attach") }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody id="json_array" class="content" data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-1","btnRemove":".btnRemove"}'>
            <tr class="y-group">
                <td>
                    <select class="form-control show-tick index-plus ms" name="attachments[0][attachment_id]">
                        {!! showSelectElement($attachments, "title") !!}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control index-plus" placeholder="السجل الخاص بمحمد" name="attachments[0][title]">
                </td>
                <td>
                    <input type="file" class="form-control index-plus" name="attachments[0][file]">
                </td>
                <td>
                    <i class="fa fa-plus-circle" id="btnAdd-1"></i>
                    <i class="fa fa-trash btnRemove"></i>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton(  route('crm.clients.store') ) !!}
        {!! closeButton() !!}
    </div>
</form>



