@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("packages.store")}}">
    @csrf

    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}} ({{helperTrans('ingaz.'.$locale)}})</label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required">
        </div>
    @endforeach

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.nationality")}}</label>
        <select name="nationality_id" class="form-control">
            <option value="">جميع الجنسيات</option>
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}">{{ $nationality->title }}</option>
            @endforeach
        </select>
        <small class="text-muted">اتركها "جميع الجنسيات" إذا كانت الباقة مناسبة لأي عاملة.</small>
    </div>

    <div class="col-6">
        <label class="form-label">نوع الإيجار</label>
        <select name="rental_type" class="form-control" data-validation="required">
            <option value="monthly">شهري</option>
            <option value="hourly">بالساعات</option>
            <option value="daily">يومي</option>
            <option value="weekly">أسبوعي</option>
            <option value="yearly">سنوي</option>
        </select>
    </div>

    <div class="col-6">
        <label class="form-label">مدة الباقة</label>
        <div class="input-group">
            <input type="number" min="1" name="duration_count" class="form-control" value="1" data-validation="required">
            <select name="duration_unit" class="form-control" data-validation="required">
                <option value="month">شهر</option>
                <option value="hour">ساعة</option>
                <option value="day">يوم</option>
                <option value="week">أسبوع</option>
                <option value="year">سنة</option>
            </select>
        </div>
    </div>

    <div class="col-6">
        <label class="form-label">ساعات العمل اليومية</label>
        <input type="number" min="1" max="24" name="working_hours_per_day" class="form-control" value="8">
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.original_price")}}</label>
        <input type="number" min="0" step="0.01" name="original_price" class="form-control js-package-original" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.price_after_discount")}}</label>
        <input type="number" min="0" step="0.01" name="price_after_discount" class="form-control js-package-net" data-validation="required">
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.price_with_tax")}}</label>
        <input type="number" min="0" step="0.01" name="price_with_tax" class="form-control js-package-total" data-validation="required">
        <small class="text-muted">الشامل للضريبة 15% يحسب تلقائياً من السعر بعد الخصم ويمكن تعديله.</small>
    </div>

    <div class="col-6 d-flex align-items-center">
        <input type="hidden" name="is_show" value="0">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="is_show" value="1" id="packageIsShowCreate" checked>
            <label class="form-check-label" for="packageIsShowCreate">إظهار الباقة للعميل وفي عقود الإيجار</label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">وصف مختصر</label>
        <textarea name="description" class="form-control" rows="2" placeholder="مثال: مناسبة لعقود الإيجار الشهري داخل المدينة"></textarea>
    </div>

    <div class="col-12 modal-footer">
        <button class="btn btn-primary" type="submit">{{__("buttons.save")}}</button>
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
    </div>
</form>
<script>
    $('.js-package-original, .js-package-net').on('input', function () {
        const original = parseFloat($('.js-package-original').val()) || 0;
        const net = parseFloat($('.js-package-net').val()) || original;

        if (!$('.js-package-net').val() && original) {
            $('.js-package-net').val(original.toFixed(2));
        }

        if (net) {
            $('.js-package-total').val((net * 1.15).toFixed(2));
        }
    });
</script>
@else
    @include('components.no_permissions')
@endif
