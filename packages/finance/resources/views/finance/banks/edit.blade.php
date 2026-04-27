<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
{{--    @foreach (config('translatable.locales') as $locale)--}}
{{--        <div class="col-md-6">--}}
{{--            <label for="name:{{ $locale }}" class="form-label">{{ __("charities.name") }} {{ getFieldLanguage($locale) }}</label>--}}
{{--            <input type="text" id="name:{{ $locale }}" name="name:{{ $locale }}" value="{{ $obj->translate($locale)->name }}"--}}
{{--                   class="form-control">--}}
{{--        </div>--}}
{{--    @endforeach--}}

    <div class="col-md-12">
        <label for="name:ar" class="form-label">{{ __("finance::finance.name") }} </label>
        <input type="text" id="name:ar" name="name:ar" value="{{$obj->translate("ar")->name}}" class="form-control" data-validation="required" placeholder="مثال : بنك الراجحي">
    </div>

    <input type="hidden" name="account_id" value="{{@$obj->account_id}}">
    <input type="hidden" name="bank_id" value="{{@$obj->bank_id}}">

{{--    <div class="col-12">--}}
{{--        <label for="TextInput" class="form-label">{{ __("finance::finance.image") }}</label>--}}
{{--        <input type="file" name="image" class="dropify" data-default-file="{{ showFile($obj->image) }}">--}}
{{--    </div>--}}

{{--    <table id="myDataTable_no_filter" class="table align-middle card-table my-2">--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th>{{ __("finance::finance.account_name") }}</th>--}}
{{--            <th>{{ __("finance::finance.bank_number") }} </th>--}}
{{--            <th>{{ __("finance::finance.iban") }} </th>--}}
{{--            <th>{{ __("finance::finance.actions") }}</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tbody id="json_array" class="content"--}}
{{--               data-mfield-options='{"section": ".y-group","btnAdd":"#btnAdd-2","btnRemove":".btnRemove"}'>--}}
{{--        @if ($obj->bank_accounts->count() > 0)--}}
{{--            @foreach ($obj->bank_accounts as $key =>$bank_accounts)--}}
{{--                <tr class="y-group">--}}
{{--                    <td>--}}
{{--                        <input type="text" name="accounts[{{ $key }}][name]"--}}
{{--                               value="{{ $bank_accounts->name ?? "" }}"--}}
{{--                               class="form-control index-plus" data-validation="required">--}}
{{--                        <input type="hidden" name="accounts[{{ $key }}][id]"--}}
{{--                               value="{{ $bank_accounts->id ?? "" }}"--}}
{{--                             >--}}
{{--                    </td>--}}
{{--                    <td>--}}
{{--                        <input type="number" value="{{ $bank_accounts->bank_number }}" step="0.01"--}}
{{--                               name="accounts[{{ $key }}][bank_number]" class="form-control index-plus"--}}
{{--                               data-validation="required">--}}
{{--                    </td>--}}
{{--                    <td>--}}
{{--                        <input type="number" value="{{ $bank_accounts->iban }}" step="0.01"--}}
{{--                               name="accounts[{{ $key }}][iban]" class="form-control index-plus"--}}
{{--                               data-validation="required">--}}
{{--                    </td>--}}
{{--                    <td>--}}
{{--                        <i class="fa fa-plus-circle" id="btnAdd-2"></i>--}}
{{--                        <i class="fa fa-trash-o btnRemove"></i>--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            @endforeach--}}
{{--        @else--}}
{{--            <tr class="y-group">--}}
{{--                <td>--}}
{{--                    <input type="text" name="accounts[0][name]" class="form-control index-plus"--}}
{{--                           data-validation="required">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="number" step="0.01" name="accounts[0][bank_number]" class="form-control index-plus"--}}
{{--                           data-validation="required">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <input type="number" step="0.01" name="accounts[0][iban]" class="form-control index-plus"--}}
{{--                           data-validation="required">--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <i class="fa fa-plus-circle" id="btnAdd-2"></i>--}}
{{--                    <i class="fa fa-trash-o btnRemove"></i>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        @endif--}}
{{--        </tbody>--}}
{{--    </table>--}}

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
