<form class="row g-3 from-submit-update-global" method="post" action="{{$updateRoute}}" enctype="multipart/form-data">
    @method("PUT")
    <div class="col-6">
        <label for="name" class="form-label">{{ __('finance::finance.name') }} </label>
        <input type="text" name="name" id="name" class="form-control" data-validation="required,length"
               data-validation-length="max255" value="{{$obj->name}}">
    </div>
    <div class="col-md-6">
        <label for="code" class="form-label">{{ __("finance::finance.code") }}</label>
        <input type="text" name="code" value="{{ $obj->code }}" class="form-control" readonly
               data-validation="required">
    </div>

    <div class="col-6">
        <label for="stockKeeper" class="form-label">{{ __('finance::finance.stockKeeper') }} </label>
        <input type="text" name="stockKeeper" id="stockKeeper" class="form-control" data-validation="length"
               data-validation-length="max255" value="{{$obj->stockKeeper ?? ""}}">
    </div>

    <div class="col-6">
        <label for="phone" class="form-label">{{ __('finance::finance.phone') }} </label>
        <input type="number" name="phone" id="phone" class="form-control" data-validation="required,length"
               data-validation-length="max255" value="{{$obj->phone}}">
    </div>

    <div class="col-6">
        <label for="address" class="form-label">{{ __('finance::finance.address') }} </label>
        <input type="text" name="address" id="address" class="form-control" data-validation="length"
               data-validation-length="max500" value="{{$obj->address ?? ""}}">
    </div>

    <div class="col-6">
        <label for="status" class="form-label">{{ __('finance::finance.status') }} </label>
        <select name="status" id="status" class="form-control" data-validation="required">
            {!! getEnumData(Nami\Finance\Enums\StockStatusEnum::cases(), $obj->status->value) !!}
        </select>
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">{{ __('finance::finance.notes') }} </label>
        <textarea name="notes" id="notes" class="form-control" data-validation="length" data-validation-length="max100"
                  rows="4">{{$obj->notes ?? ""}}</textarea>
    </div>

    <div class="col-12 modal-footer">
        {!! submitButton( $updateRoute ) !!}
        {!! closeButton() !!}
    </div>

</form>
