<form class="row g-3 from-submit-global" method="post" action="{{ $storeRoute }}">
    <div class="col-md-12">
        <label for="name" class="form-label">{{ __('finance::finance.bank_name') }}</label>
        <input type="text" id="name" name="name" class="form-control" data-validation="required"
               placeholder="مثال: البنك الأهلي">
    </div>

    @include('finance::finance.banks.partials.accounts', ['accounts' => collect()])

    <div class="col-12 modal-footer">
        {!! submitButton($storeRoute) !!}
        {!! closeButton() !!}
    </div>
</form>
