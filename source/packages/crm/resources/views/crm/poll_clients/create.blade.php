<form class="row g-3 from-submit-global" method="post" action="{{ route('crm.clients.store') }}"
      enctype="multipart/form-data">
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.name") }}</label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.phone") }}</label>
        <input type="text" name="phone" class="form-control" data-validation="required">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.email") }}</label>
        <input type="text" name="email" class="form-control">
    </div>
    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.client_whatsapp") }}</label>
        <input type="number" name="whatsapp" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.Notes") }}</label>
        <input type="text" name="notes" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.client_code") }}</label>
        <input type="text" name="code" class="form-control">
    </div>

    <div class="col-md-4">
        <label for="TextInput" class="form-label">{{ __("crm.address") }}</label>
        <input type="text" name="address" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">{{ __("crm.social_contacts") }}</label>
        <select class="form-control show-tick select2" name="social_contact_id">
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($social_contacts as $item)
                <option value="{{ $item->id }}">{{ getFinalTrans('title',$item) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm.cities") }}</label>
        <select class="form-control show-tick select2" name="city_id">
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($cities as $item)
                <option value="{{ $item->id }}">{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm.areas") }}</label>
        <select class="form-control show-tick select2" name="area_id">
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($areas as $item)
                <option value="{{ $item->id }}">{{ $item->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm.employee_in_charge") }}</label>
        <select class="form-control show-tick select2" name="user_id">
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($users as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __("crm.categories") }}</label>
        <select class="form-control show-tick ms select2" name="category_id[]" multiple>
            <option value=""> {{ __("crm.Choose") }} </option>
            @foreach ($categories as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12" style="padding-top: 20px !important;" id="dataaa">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th scope="row">{{__('crm.attachments')}}</th>
            </tr>
            </thead>
            <tbody id="json_array" class="content"
                   data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-1","btnRemove":".btnRemove"}'>
            <tr class="y-group">
                <td>
                    <label for="TextInput" class="form-label">{{ __("crm.attach_type") }}</label>
                    <select class="form-control show-tick index-plus ms" name="attachments[0][attachment_id]">
                        {{--                        {!! showSelectElement($attachments, "title") !!}--}}
                        @foreach($attachments as $attachment)
                            <option value="{{$attachment->id}}">{{getFinalTrans('title',$attachment)}}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <label for="TextInput" class="form-label">{{ __("crm.title") }}</label>
                    <input type="text" class="form-control index-plus" placeholder="السجل الخاص بمحمد"
                           name="attachments[0][title]">
                </td>
                <td>
                    <label for="TextInput" class="form-label">{{ __("crm.attach") }}</label>
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
        {!! submitButton(route('crm.clients.store')) !!}
        {!! closeButton() !!}
    </div>
</form>
