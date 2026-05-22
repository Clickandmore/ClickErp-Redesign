@if (@AdminGuardHasPerm(\App\PermKeys::PACKAGES_CONTROL))
<form class="row g-3 from-submit-global" method="post" action="{{route("packages.update",$obj->id)}}">
    @csrf
    @method('Put')
    @foreach (config('translatable.locales') as $locale)
        <div class="col-6">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.title")}} ({{helperTrans('ingaz.'.$locale)}})</label>
            <input type="text" name="title:{{ $locale }}" class="form-control" data-validation="required" value="{{$obj->translate($locale)->title}}">
        </div>
    @endforeach
    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.nationality")}}</label>
        <select name="nationality_id" class="form-control">
            <option value="" {{ empty($obj->nationality_id) ? 'selected' : '' }}>جميع الجنسيات</option>
            @foreach ($nationalities as $nationality)
                <option value="{{ $nationality->id }}" {{ $obj->nationality_id == $nationality->id ? 'selected' : '' }}>{{ $nationality->display_name }}</option>
            @endforeach
        </select>
        <small class="text-muted">اتركها "جميع الجنسيات" إذا كانت الباقة مناسبة لأي عاملة.</small>
    </div>

    <div class="col-6">
        <label class="form-label">نوع الإيجار</label>
        <select name="rental_type" class="form-control" data-validation="required">
            @foreach (['monthly' => 'شهري', 'hourly' => 'بالساعات', 'daily' => 'يومي', 'weekly' => 'أسبوعي', 'yearly' => 'سنوي'] as $value => $label)
                <option value="{{ $value }}" {{ ($obj->rental_type ?? 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-6">
        <label class="form-label">مدة الباقة</label>
        <div class="input-group">
            <input type="number" min="1" name="duration_count" class="form-control" value="{{ $obj->duration_count ?: max(1, (int) ceil(($obj->days_count ?: 30) / 30)) }}" data-validation="required">
            <select name="duration_unit" class="form-control" data-validation="required">
                @foreach (['month' => 'شهر', 'hour' => 'ساعة', 'day' => 'يوم', 'week' => 'أسبوع', 'year' => 'سنة'] as $value => $label)
                    <option value="{{ $value }}" {{ ($obj->duration_unit ?? 'month') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-6">
        <label class="form-label">ساعات العمل اليومية</label>
        <input type="number" min="1" max="24" name="working_hours_per_day" class="form-control" value="{{ $obj->working_hours_per_day ?: 8 }}">
    </div>

    <div class="col-6">
        <label class="form-label">ترتيب الباقة</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="{{ $obj->sort_order ?? 0 }}">
        <small class="text-muted">الأقل يظهر أولاً في الموقع وعقود الإيجار.</small>
    </div>

    <div class="col-6">
        <label class="form-label">السعر بدون الضريبة</label>
        <input type="number" min="0" step="0.01" name="original_price" class="form-control js-package-original" data-validation="required" value="{{$obj->original_price}}">
        <small class="text-muted">تكلفة الباقة الأساسية قبل الضريبة.</small>
    </div>

    <div class="col-6">
        <label class="form-label">الإجمالي بعد الضريبة</label>
        <input type="number" min="0" step="0.01" name="price_with_tax" class="form-control js-package-total" value="{{$obj->price_with_tax}}">
        <small class="text-muted">تكلفة الباقة الأساسية بعد الضريبة ويمكن تعديلها.</small>
    </div>

    <div class="col-6">
        <label class="form-label">ضريبة القيمة المضافة 15%</label>
        <input type="number" min="0" step="0.01" class="form-control js-package-vat" readonly value="{{ max(0, (float) $obj->price_with_tax - (float) $obj->original_price) }}">
    </div>

    <div class="col-6 d-flex align-items-center">
        <input type="hidden" name="is_show" value="0">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="is_show" value="1" id="packageIsShowEdit{{ $obj->id }}" {{ $obj->is_show ? 'checked' : '' }}>
            <label class="form-check-label" for="packageIsShowEdit{{ $obj->id }}">إظهار الباقة للعميل وفي عقود الإيجار</label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">وصف مختصر</label>
        <textarea name="description" class="form-control" rows="2">{{ $obj->description }}</textarea>
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
</script>
@else
    @include('components.no_permissions')
@endif
