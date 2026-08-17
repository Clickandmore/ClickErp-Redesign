<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}">
    @method('put')

    <div class="col-md-12">
        <label for="name" class="form-label">{{ __('finance::finance.bank_name') }}</label>
        <input type="text" id="name" name="name" value="{{ $obj->name }}" class="form-control"
               data-validation="required">
    </div>

    @include('finance::finance.banks.partials.accounts', ['accounts' => $obj->children])

    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
