<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" class="form-control" data-validation="required" value="{{ $obj->name }}">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __('finance::finance.account') }}</label>
        <select name="account_id[]" class="form-control select2" data-validation="required" multiple>
            <option value="">{{ trans('finance::finance.Select') }}</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" {{ in_array($account->id, $accountIds) ? 'selected' : '' }}>{{ $account->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton(  $updateRoute  ) !!}
        {!! closeButton() !!}
    </div>

</form>
