<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('put')
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __("finance::finance.banks") }} </label>
        <select name="bank_id" id="bank_id" class="form-select" data-validation="required">
            {!! showSelectElement($banks,'name',$obj->bank_id) !!}
        </select>
    </div>
    <div class="col-md-6">
        <label for="name" class="form-label">{{ __("finance::finance.account_name") }} </label>
        <input type="text" value="{{ $obj->name }}" id="name" name="name" class="form-control" data-validation="required">
    </div>

    <div class="col-md-6">
        <label for="bank_number" class="form-label">{{ __("finance::finance.bank_number") }} </label>
        <input type="text" value="{{ $obj->bank_number }}" id="bank_number" name="bank_number" class="form-control" data-validation="required">
    </div>
    <div class="col-md-6">
        <label for="iban" class="form-label">{{ __("finance::finance.iban") }} </label>
        <input type="text" value="{{ $obj->iban }}" id="iban" name="iban" class="form-control" data-validation="required">
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
