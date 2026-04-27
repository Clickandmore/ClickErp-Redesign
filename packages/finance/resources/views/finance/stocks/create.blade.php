<form class="row g-3 from-submit-global" method="post" action="{{$storeRoute}}" enctype="multipart/form-data">
    <div class="col-6">
        <label for="name" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" id="name" class="form-control" data-validation="required,length"
               data-validation-length="max255">
    </div>

    <div class="col-md-6">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" value="{{ $code }}" class="form-control" readonly data-validation="required">
    </div>

    <div class="col-6">
        <label for="stockKeeper" class="form-label">{{ __('finance::finance.stockKeeper') }} </label>
        <input type="text" name="stockKeeper" id="stockKeeper" class="form-control" data-validation="length"
               data-validation-length="max255">
    </div>

    <div class="col-6">
        <label for="phone" class="form-label">{{ __('finance::finance.phone') }} </label>
        <input type="tel" name="phone" id="phone" class="form-control" data-validation="required,length"
               data-validation-length="max255">
    </div>

    <div class="col-6">
        <label for="address" class="form-label">{{ __('finance::finance.address') }} </label>
        <input type="text" name="address" id="address" class="form-control" data-validation="length"
               data-validation-length="max500">
    </div>

    <div class="col-6">
        <label for="status" class="form-label">{{ __('finance::finance.status') }} </label>
        <select name="status" id="status" class="form-control" data-validation="required">
            {!! getEnumData(Nami\Finance\Enums\StockStatusEnum::cases()) !!}
        </select>
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">{{ __('finance::finance.notes') }} </label>
        <textarea name="notes" id="notes" class="form-control" data-validation="length" data-validation-length="max100"
                  rows="4"></textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( $storeRoute ) !!}
        {!! closeButton() !!}
    </div>
</form>
