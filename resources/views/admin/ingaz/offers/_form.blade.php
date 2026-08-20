@php
    $selectedNationalities = isset($obj) && $obj ? $obj->nationalities->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
    $selectedMaids = isset($obj) && $obj ? $obj->maids->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
    $offerType = old('type', $obj->type ?? \App\Models\ingaz\Offer::TYPE_GENERAL);
@endphp

<div class="col-md-6">
    <label class="form-label">نوع العرض <span class="text-danger">*</span></label>
    <select name="type" class="form-control offer-type" required>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" {{ $offerType === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="col-md-6">
    <label class="form-label">حالة العرض</label>
    <select name="is_active" class="form-control">
        <option value="1" {{ (bool) ($obj->is_active ?? true) ? 'selected' : '' }}>نشط</option>
        <option value="0" {{ isset($obj) && ! $obj->is_active ? 'selected' : '' }}>غير نشط</option>
    </select>
</div>

<div class="col-md-6">
    <label class="form-label">عنوان العرض <span class="text-danger">*</span></label>
    <input type="text" name="title" class="form-control" value="{{ $obj->title ?? '' }}" required>
</div>

<div class="col-md-6">
    <label class="form-label">ترتيب العرض</label>
    <input type="number" name="sort_order" class="form-control" min="0" value="{{ $obj->sort_order ?? 0 }}">
</div>

<div class="col-12">
    <label class="form-label">وصف العرض <span class="text-danger">*</span></label>
    <textarea name="description" class="form-control" rows="4" required>{{ $obj->description ?? '' }}</textarea>
</div>

<div class="col-12">
    <label class="form-label">صورة العرض @if(empty($obj)) <span class="text-danger">*</span> @endif</label>
    <input type="file" name="image" class="dropify" data-default-file="{{ isset($obj) && $obj->image ? getFile($obj->image) : '' }}" {{ empty($obj) ? 'required' : '' }}>
    <small class="text-muted d-block mt-1">
        يُقبل الملف بأي امتداد أو حجم من جهة النظام. إذا تجاوز حجم الملف حد الخادم ({{ ini_get('upload_max_filesize') }}) ستظهر رسالة توضح ذلك.
    </small>
</div>

<div class="col-md-4">
    <label class="form-label">السعر قبل الخصم</label>
    <input type="number" step="0.01" min="0" name="price_before_discount" class="form-control offer-price-before" value="{{ $obj->price_before_discount ?? '' }}">
</div>

<div class="col-md-4">
    <label class="form-label">السعر بعد الخصم</label>
    <input type="number" step="0.01" min="0" name="price_after_discount" class="form-control offer-price-after" value="{{ $obj->price_after_discount ?? '' }}">
</div>

<div class="col-md-4">
    <label class="form-label">نسبة الخصم</label>
    <input type="number" step="0.01" min="0" max="100" name="discount_percentage" class="form-control offer-discount" value="{{ $obj->discount_percentage ?? '' }}">
</div>

<div class="col-md-6">
    <label class="form-label">رابط العرض</label>
    <input type="text" name="url" class="form-control" value="{{ $obj->url ?? '' }}"
           placeholder="https://example.com/offer  أو  /professional-workers">
    <small class="text-muted">رابط كامل يبدأ بـ https:// أو مسار داخلي في الموقع يبدأ بـ /</small>
</div>

<div class="col-md-3">
    <label class="form-label">تاريخ بداية العرض</label>
    <input type="date" name="starts_at" class="form-control" value="{{ isset($obj) && $obj->starts_at ? $obj->starts_at->format('Y-m-d') : '' }}">
</div>

<div class="col-md-3">
    <label class="form-label">تاريخ نهاية العرض</label>
    <input type="date" name="ends_at" class="form-control" value="{{ isset($obj) && $obj->ends_at ? $obj->ends_at->format('Y-m-d') : '' }}">
</div>

<div class="col-12 offer-nationalities-field">
    <label class="form-label">الجنسيات</label>
    <select name="nationality_ids[]" class="form-control" multiple>
        @foreach ($nationalities as $nationality)
            <option value="{{ $nationality->id }}" {{ in_array((string) $nationality->id, $selectedNationalities, true) ? 'selected' : '' }}>
                {{ $nationality->sub_title ?: $nationality->title ?: $nationality->id }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-12 offer-maids-field">
    <label class="form-label">السير الذاتية</label>
    <select name="maid_ids[]" class="form-control" multiple>
        @foreach ($maids as $maid)
            <option value="{{ $maid->id }}" {{ in_array((string) $maid->id, $selectedMaids, true) ? 'selected' : '' }}>
                {{ $maid->name ?: 'سيرة #' . $maid->id }}
                @if($maid->cv_code)
                    - {{ $maid->cv_code }}
                @endif
                @if($maid->nationality)
                    - {{ $maid->nationality->sub_title ?: $maid->nationality->title }}
                @endif
            </option>
        @endforeach
    </select>
</div>

<div class="col-12 modal-footer">
    <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">{{ __('buttons.close') }}</button>
</div>

<script>
    (function () {
        function toggleOfferRelationFields() {
            const type = $('.offer-type').val();
            const showNationalities = type === 'nationality' || type === 'nationality_maid';
            const showMaids = type === 'maid' || type === 'nationality_maid';

            $('.offer-nationalities-field').toggle(showNationalities);
            $('.offer-nationalities-field select').prop('required', showNationalities);
            $('.offer-maids-field').toggle(showMaids);
            $('.offer-maids-field select').prop('required', showMaids);
        }

        function calculateDiscount() {
            const before = parseFloat($('.offer-price-before').val());
            const after = parseFloat($('.offer-price-after').val());
            const $discount = $('.offer-discount');

            if (before > 0 && after >= 0 && after < before && !$discount.data('manual')) {
                $discount.val((((before - after) / before) * 100).toFixed(2));
            }
        }

        $('.offer-type').on('change', toggleOfferRelationFields);
        $('.offer-price-before, .offer-price-after').on('input', calculateDiscount);
        $('.offer-discount').on('input', function () {
            $(this).data('manual', true);
        });

        toggleOfferRelationFields();
    })();
</script>
