<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")


    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __('finance::finance.name') }}</label>
        <input type="text" name="name" class="form-control"data-validation="required"value="{{ $obj->name }}">
    </div>

    <div class="col-12">
        <label for="TextInput" class="form-label"> {{ __("finance::finance.Directory account") }}   {!! addUpButton(route('finance.accounts.create'),'account_id' ,false, 'name') !!}</label>
        <select class="form-control required show-tick ms select2" name="account_id" data-validation="required">
            {!! showSelectElement($accounts,"name",$obj->account_id ) !!}
        </select>
    </div>


    <div class="col-12">
        <label for="value" class="form-label">{{__('finance::finance.value')}}</label>
        <input type="text" name="value"  class="form-control" id="value" data-validation="required,number" value="{{ $obj->value }}">
    </div>


    <div class="col-12 modal-footer">
         {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
