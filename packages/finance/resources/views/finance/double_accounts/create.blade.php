<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <div class="col-6">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{ __('finance::finance.account') }}</label>
        <select name="account_id[]" class="form-control select2" data-validation="required" multiple>
            {!! showSelectElement($accounts ,'name') !!}
        </select>
    </div>

    <div class="col-12 modal-footer">
         {!! submitButton(  $storeRoute  ) !!}
        {!! closeButton() !!}
    </div>

</form>
