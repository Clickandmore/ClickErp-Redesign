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
                <option value="{{ $nationality->id }}">{{ $nationality->display_name }}</option>
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
        <label class="form-label">ترتيب الباقة</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="0">
        <small class="text-muted">الأقل يظهر أولاً في الموقع وعقود الإيجار.</small>
    </div>

    <div class="col-12">
        <label class="form-label d-block">احتساب الضريبة</label>
        <div class="form-check form-check-inline">
            <input class="form-check-input js-package-taxable" type="radio" name="is_taxable"
                   id="packageTaxableCreate" value="1" checked>
            <label class="form-check-label" for="packageTaxableCreate">بضريبة 15%</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input js-package-taxable" type="radio" name="is_taxable"
                   id="packageNotTaxableCreate" value="0">
            <label class="form-check-label" for="packageNotTaxableCreate">بدون ضريبة</label>
        </div>
    </div>

    <div class="col-6">
        <label class="form-label">السعر بدون الضريبة</label>
        <input type="number" min="0" step="0.01" name="original_price" class="form-control js-package-original" data-validation="required">
        <small class="text-muted">تكلفة الباقة الأساسية قبل الضريبة.</small>
    </div>

    <div class="col-6">
        <label class="form-label">الإجمالي بعد الضريبة</label>
        <input type="number" min="0" step="0.01" name="price_with_tax" class="form-control js-package-total">
        <small class="text-muted js-package-total-help">تكلفة الباقة الأساسية بعد الضريبة ويمكن تعديلها.</small>
    </div>

    <div class="col-6">
        <label class="form-label">ضريبة القيمة المضافة 15%</label>
        <input type="number" min="0" step="0.01" class="form-control js-package-vat" readonly>
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
    function calculatePackagePrices(changedField) {
        let original = parseFloat($('.js-package-original').val()) || 0;
        let total = parseFloat($('.js-package-total').val()) || 0;
        const isTaxable = $('input[name="is_taxable"]:checked').val() !== '0';

        if (!isTaxable) {
            if (changedField === 'total') {
                original = total;
                $('.js-package-original').val(original.toFixed(2));
            } else {
                total = original;
                $('.js-package-total').val(total.toFixed(2));
            }

            $('.js-package-vat').val('0.00');
            $('.js-package-total-help').text('لا توجد ضريبة؛ الإجمالي يساوي السعر الأساسي.');
            return;
        }

        $('.js-package-total-help').text('تكلفة الباقة الأساسية بعد الضريبة ويمكن تعديلها.');

        if (changedField === 'total') {
            original = total > 0 ? total / 1.15 : 0;
            $('.js-package-original').val(original.toFixed(2));
        } else {
            total = original * 1.15;
            $('.js-package-total').val(total.toFixed(2));
        }

        const vat = Math.max(total - original, 0);
        $('.js-package-vat').val(vat.toFixed(2));
    }

    $('.js-package-original').on('input', function () {
        calculatePackagePrices('original');
    });

    $('.js-package-total').on('input', function () {
        calculatePackagePrices('total');
    });

    $('.js-package-taxable').on('change', function () {
        calculatePackagePrices('original');
    });
</script>
@else
    @include('components.no_permissions')
@endif
