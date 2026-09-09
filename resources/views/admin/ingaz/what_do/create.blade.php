<form class="row g-3 from-submit-global" method="post" action="{{route("what-do.store")}}" enctype="multipart/form-data">
    @csrf
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("web.type")}}
        </label>
        <select name="type" class="form-control" data-validation="required">
            <option value="">{{__("ingaz.choose")}}</option>
            <option value="estqdam"> {{ __('ingaz.recruitment') }}</option>
            <option value="egar"> {{ __('ingaz.Labor_rental') }}</option>
            <option value="transfer"> {{ __('ingaz.transfer_services') }}</option>
        </select>
    </div>
    <div class="col-6">
        <label for="TextInput" class="form-label">
            {{helperTrans("ingaz.icon")}}
        </label>
        @include('admin.ingaz.what_do.icons')
    </div>
    <div class="col-12">
        <label class="form-label">
            {{ helperTrans('ingaz.image') }} ({{ helperTrans('ingaz.optional') }})
        </label>
        <input type="file" name="image" class="dropify" accept="image/jpeg,image/png,image/webp,image/svg+xml">
    </div>
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.title")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">
                {{helperTrans("ingaz.description")}}
                ({{helperTrans('ingaz.'.$locale)}})
            </label>
            <textarea type="text" name="description:{{ $locale }}" class="form-control"
                      data-validation="required"></textarea>
        </div>
    @endforeach

    <label for="TextInput" class="form-label">
        {{helperTrans("ingaz.items")}} ({{ helperTrans('ingaz.optional') }})
    </label>
    <table class="table align-middle card-table my-2">
        <thead>
        <tr>
            <th></th>
            @foreach (config('translatable.locales') as $locale)
                <th>{{ __("ingaz.title") }} ({{helperTrans('ingaz.'.$locale)}})
                </th>
            @endforeach
            <th>{{ __("ingaz.actions") }}</th>
        </tr>
        </thead>
        <tbody id="json_array" class="content"
               data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>
        <tr class="y-group">
            <td></td>
            @foreach (config('translatable.locales') as $locale)
                <td>
                    <input type="text" name="titles[0][{{ $locale }}][title]" class="form-control index-plus">
                </td>
            @endforeach
            <td>
                <i class="fa fa-plus-circle" id="btnAdd-2"></i>
                <i class="fa fa-trash-o btnRemove"></i>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>



