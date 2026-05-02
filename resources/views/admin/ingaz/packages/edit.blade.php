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
                <option value="{{ $nationality->id }}" {{ $obj->nationality_id == $nationality->id ? 'selected' : '' }}>{{ $nationality->title }}</option>
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
        <label class="form-label">{{helperTrans("ingaz.original_price")}}</label>
        <input type="number" min="0" step="0.01" name="original_price" class="form-control js-package-original" data-validation="required" value="{{$obj->original_price}}">
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.price_after_discount")}}</label>
        <input type="number" min="0" step="0.01" name="price_after_discount" class="form-control js-package-net" data-validation="required" value="{{$obj->price_after_discount}}">
    </div>

    <div class="col-6">
        <label class="form-label">{{helperTrans("ingaz.price_with_tax")}}</label>
        <input type="number" min="0" step="0.01" name="price_with_tax" class="form-control js-package-total" data-validation="required" value="{{$obj->price_with_tax}}">
        <small class="text-muted">الشامل للضريبة 15% يحسب تلقائياً من السعر بعد الخصم ويمكن تعديله.</small>
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
