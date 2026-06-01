@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{ route('packages.offer.update', $obj->id) }}">
    @csrf
    @method('Put')

    <div class="col-12">
        <div class="alert alert-info mb-0">
            العرض منفصل تماماً عن تكاليف الباقة. تعديل هذه البيانات لا يغير السعر بدون الضريبة أو الإجمالي بعد الضريبة.
        </div>
    </div>

    <div class="col-6">
        <label class="form-label">قيمة العرض</label>
        <input type="number" min="0" step="0.01" name="offer_amount" class="form-control" value="{{ $obj->offer_amount }}">
    </div>

    <div class="col-6 d-flex align-items-center">
        <input type="hidden" name="offer_is_active" value="0">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="offer_is_active" value="1" id="packageOfferIsActive{{ $obj->id }}" {{ $obj->offer_is_active ? 'checked' : '' }}>
            <label class="form-check-label" for="packageOfferIsActive{{ $obj->id }}">تفعيل العرض</label>
        </div>
    </div>

    <div class="col-6">
        <label class="form-label">بداية العرض</label>
        <input type="date" name="offer_starts_at" class="form-control" value="{{ $obj->offer_starts_at?->format('Y-m-d') }}">
    </div>

    <div class="col-6">
        <label class="form-label">انتهاء العرض</label>
        <input type="date" name="offer_ends_at" class="form-control" value="{{ $obj->offer_ends_at?->format('Y-m-d') }}">
    </div>

    <div class="col-12">
        <label class="form-label">وصف العرض</label>
        <textarea name="offer_description" class="form-control" rows="3">{{ $obj->offer_description }}</textarea>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
@else
    @include('components.no_permissions')
@endif
