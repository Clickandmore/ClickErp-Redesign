@php
    $fieldValue = static fn (string $field, $default = null) => old($field, $obj?->{$field} ?? $default);
    $isEditing = (bool) $obj;
@endphp

<style>
    .nationality-form-section { grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; margin-top: 8px; color: #1e3a8a; font-size: 1rem; font-weight: 700; }
    .nationality-form-section::after { content: ''; height: 1px; background: #e5eaf2; flex: 1; }
    .nationality-price-group { grid-column: 1 / -1; border: 1px solid #dbe5f2; border-radius: 16px; padding: 18px; background: #f8fbff; }
    .nationality-price-group--invoice { border-color: #f4dfb8; background: #fffaf1; }
    .nationality-price-group__head { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 16px; }
    .nationality-price-group__icon { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 12px; color: #2563eb; background: #e8f0ff; }
    .nationality-price-group--invoice .nationality-price-group__icon { color: #a16207; background: #fff0c7; }
    .nationality-price-group__head h6 { margin: 0 0 3px; font-weight: 800; color: #172554; }
    .nationality-price-group__head p { margin: 0; color: #64748b; font-size: .85rem; }
    .nationality-price-field { height: 100%; padding: 14px; border: 1px solid #e4eaf2; border-radius: 12px; background: #fff; }
    .nationality-price-field .form-label { font-weight: 700; color: #334155; }
    .nationality-price-field .form-text { min-height: 38px; line-height: 1.45; }
    .nationality-price-field .input-group-text { color: #475569; background: #f8fafc; font-weight: 700; }
    .nationality-edit-form .dropify-wrapper { height: 145px; }
    @media (max-width: 767.98px) { .nationality-price-group { padding: 14px; } .nationality-price-field .form-text { min-height: auto; } }
</style>

<div class="nationality-form-section"><i class="fas fa-id-card"></i>البيانات الأساسية</div>
<div class="col-lg-4 col-12">
    <label class="form-label">{{ helperTrans('ingaz.image') }}</label>
    <input type="file" data-default-file="{{ $isEditing ? getFile($obj->logo) : '' }}" name="logo" class="dropify" @unless($isEditing) data-validation="required" @endunless>
</div>
<div class="col-lg-4 col-md-6 col-12">
    <label for="nationality_phone_code" class="form-label">{{ helperTrans('ingaz.country_code') }}</label>
    <input type="text" id="nationality_phone_code" name="phone_code" class="form-control" value="{{ $fieldValue('phone_code') }}" data-validation="required">
</div>
<div class="col-lg-4 col-md-6 col-12">
    <label for="nationality_sort_order" class="form-label">{{ __('countries.sort_order') }}</label>
    <input type="number" id="nationality_sort_order" name="sort_order" class="form-control" min="1" step="1" value="{{ $fieldValue('sort_order', $nextSortOrder ?? 1) }}" data-validation="required">
</div>

<div class="nationality-price-group">
    <div class="nationality-price-group__head">
        <span class="nationality-price-group__icon"><i class="fas fa-globe"></i></span>
        <div><h6>أسعار الموقع الخارجي والخدمات</h6><p>هذه القيم تظهر للعملاء أو تُستخدم كسعر افتراضي للعاملات والعقود، ولا تمثل بنود الفاتورة المحاسبية.</p></div>
    </div>
    <div class="row g-3">
        @php
            $sitePrices = [
                ['name' => 'price', 'label' => 'تكلفة الاستقدام بالموقع', 'help' => 'السعر الافتراضي الظاهر لخدمة الاستقدام والسير الذاتية.'],
                ['name' => 'service_transportation_cost', 'label' => 'تكلفة نقل الخدمات بالموقع', 'help' => 'السعر الافتراضي عند عرض خدمة نقل الخدمات.'],
                ['name' => 'rental_cost', 'label' => 'تكلفة التأجير بالموقع', 'help' => 'السعر الافتراضي عند عرض خدمة التأجير.'],
                ['name' => 'monthly_salary', 'label' => 'الراتب الشهري الافتراضي', 'help' => 'يُقترح تلقائياً عند إضافة عاملة أو إنشاء عقد تأجير.'],
                ['name' => 'salary_with_no_experience', 'label' => 'راتب بدون خبرة', 'help' => 'الراتب المعروض للعاملات اللاتي لم يسبق لهن العمل.'],
                ['name' => 'salary_with_experience', 'label' => 'راتب مع خبرة', 'help' => 'الراتب المعروض للعاملات اللاتي سبق لهن العمل.'],
            ];
        @endphp
        @foreach($sitePrices as $priceField)
            <div class="col-lg-4 col-md-6">
                <div class="nationality-price-field">
                    <label for="{{ $priceField['name'] }}" class="form-label">{{ $priceField['label'] }}</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" id="{{ $priceField['name'] }}" name="{{ $priceField['name'] }}" class="form-control" value="{{ $fieldValue($priceField['name']) }}">
                        <span class="input-group-text">ر.س</span>
                    </div>
                    <div class="form-text">{{ $priceField['help'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="nationality-price-group nationality-price-group--invoice">
    <div class="nationality-price-group__head">
        <span class="nationality-price-group__icon"><i class="fas fa-file-invoice-dollar"></i></span>
        <div><h6>بنود الفاتورة الافتراضية</h6><p>تُعبأ هذه القيم تلقائياً داخل بنود فاتورة عقد الاستقدام، ويمكن تعديلها لاحقاً من الفاتورة نفسها.</p></div>
    </div>
    <div class="row g-3">
        @php
            $invoicePrices = [
                ['name' => 'direct_recruitment_cost', 'label' => 'بند الاستقدام المباشر', 'help' => 'القيمة الافتراضية لبند الاستقدام المباشر في الفاتورة.'],
                ['name' => 'external_recruitment_cost', 'label' => 'بند الاستقدام الخارجي', 'help' => 'القيمة الافتراضية لبند الاستقدام الخارجي في الفاتورة.'],
                ['name' => 'government_cost', 'label' => 'بند التكاليف الحكومية', 'help' => 'القيمة الافتراضية لبند التكاليف الحكومية في الفاتورة.'],
                ['name' => 'musaned_commission_rate', 'label' => 'نسبة التكاليف البنكية (مساند)', 'help' => 'تُستخدم لحساب مبلغ التكاليف البنكية الظاهر أسفل فاتورة مساند.', 'suffix' => '%', 'default' => 2.4],
            ];
        @endphp
        @foreach($invoicePrices as $priceField)
            <div class="col-md-6 col-lg-3">
                <div class="nationality-price-field">
                    <label for="{{ $priceField['name'] }}" class="form-label">{{ $priceField['label'] }}</label>
                    <div class="input-group">
                        <input type="number" step="{{ $priceField['name'] === 'musaned_commission_rate' ? '0.0001' : '0.01' }}" min="0"
                            @if($priceField['name'] === 'musaned_commission_rate') max="100" required @endif
                            id="{{ $priceField['name'] }}" name="{{ $priceField['name'] }}" class="form-control"
                            value="{{ $fieldValue($priceField['name'], $priceField['default'] ?? null) }}">
                        <span class="input-group-text">{{ $priceField['suffix'] ?? 'ر.س' }}</span>
                    </div>
                    <div class="form-text">{{ $priceField['help'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="nationality-form-section"><i class="fas fa-clock"></i>المدة والمحتوى</div>
<div class="col-md-6 col-12">
    <label for="expected_duration" class="form-label">{{ helperTrans('ingaz.expected_duration') }}</label>
    <div class="input-group">
        <input type="number" id="expected_duration" name="expected_duration" class="form-control" min="45" value="{{ $fieldValue('expected_duration', 45) }}" data-validation="required">
        <span class="input-group-text">يوم</span>
    </div>
    <div class="form-text">تظهر كمدة متوقعة للاستقدام في الموقع الخارجي.</div>
</div>

@foreach (config('translatable.locales') as $locale)
    <div class="col-md-6 col-12">
        <label class="form-label">{{ helperTrans('ingaz.nationality_name') }} ({{ helperTrans('ingaz.'.$locale) }})</label>
        <input type="text" name="sub_title:{{ $locale }}" class="form-control" data-validation="required" value="{{ old('sub_title:'.$locale, $obj ? optional($obj->translate($locale))->sub_title : '') }}">
    </div>
@endforeach

@foreach (config('translatable.locales') as $locale)
    <div class="col-md-6 col-12">
        <label class="form-label">{{ helperTrans('ingaz.description') }} ({{ helperTrans('ingaz.'.$locale) }})</label>
        <textarea name="description:{{ $locale }}" class="form-control" rows="3" placeholder="{{ helperTrans('ingaz.description') }}">{{ old('description:'.$locale, $obj ? optional($obj->translate($locale))->description : '') }}</textarea>
    </div>
@endforeach

<div class="col-12">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" id="is_show" name="is_show" value="1" {{ $fieldValue('is_show', 1) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_show">{{ helperTrans('ingaz.show_on_home') }}</label>
    </div>
</div>
