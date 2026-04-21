@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_general'))

@section('settings-content')
    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="col-12">
            <h6 class="text-muted mb-2">بيانات الموقع</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">اسم الموقع {{ getFieldLanguage($locale) }}</label>
                        <fieldset class="form-icon-group right-icon position-relative">
                            <div class="form-icon position-absolute">
                                <i class="fa fa-globe"></i>
                            </div>
                            <input type="text" name="website_name:{{ $locale }}"
                                   value="{{ $settings->translate($locale)->website_name ?? '' }}" class="form-control">
                        </fieldset>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">العنوان {{ getFieldLanguage($locale) }}</label>
                        <fieldset class="form-icon-group right-icon position-relative">
                            <div class="form-icon position-absolute">
                                <i class="fa fa-map-marker"></i>
                            </div>
                            <input type="text" name="location:{{ $locale }}"
                                   value="{{ $settings->translate($locale)->location ?? '' }}" class="form-control">
                        </fieldset>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">هوية النماذج والعقود</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">اسم الشركة على النماذج {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="document_company_name:{{ $locale }}" class="form-control"
                               value="{{ $settings->translate($locale)->document_company_name ?? '' }}"
                               placeholder="مثال: شركة بلاتنيوم الشرقية للاستقدام">
                    </div>
                @endforeach

                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">اسم المكتب على النماذج {{ getFieldLanguage($locale) }}</label>
                        <input type="text" name="document_office_name:{{ $locale }}" class="form-control"
                               value="{{ $settings->translate($locale)->document_office_name ?? '' }}"
                               placeholder="مثال: مكتب بلاتنيوم الشرقية للاستقدام">
                    </div>
                @endforeach

                <div class="col-md-6">
                    <label class="form-label">لوجو النماذج والعقود</label>
                    <input type="file" name="document_logo" class="form-control" accept="image/*">
                    <small class="text-muted d-block mt-1">عند تركه فارغاً سيتم استخدام لوجو الهدر الحالي.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">المعاينة الحالية</label>
                    @php
                        $documentLogo = showFile($settings->document_logo ?: $settings->logo_header);
                    @endphp
                    @if ($documentLogo)
                        <img src="{{ $documentLogo }}" alt="لوجو النماذج" class="img-fluid rounded border p-2"
                             style="max-height: 120px;">
                    @else
                        <div class="text-muted small">لا يوجد لوجو مخصص للنماذج حالياً.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">بيانات التواصل</h6>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">البريد الالكتروني</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-envelope"></i>
                        </div>
                        <input type="email" name="email" class="form-control"
                               value="{{ $settings->email ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">رقم الهاتف</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-mobile"></i>
                        </div>
                        <input type="text" name="phone" class="form-control" value="{{ $settings->phone ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">رقم هاتف اخر</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-phone"></i>
                        </div>
                        <input type="text" name="other_phone" class="form-control"
                               value="{{ $settings->other_phone ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">الرقم الضريبي</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-hashtag"></i>
                        </div>
                        <input type="text" name="tax_number" class="form-control"
                               value="{{ $settings->tax_number ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-8">
                    <label class="form-label">رابط الخريطة</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-link"></i>
                        </div>
                        <input type="text" name="map_link" class="form-control"
                               value="{{ $settings->map_link ?? '' }}">
                    </fieldset>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">بيانات إضافية</h6>
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label">سنين الخبره</label>
                    <input type="number" name="years" class="form-control" value="{{ $settings->years ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">تفعيل خدمة العملاء أثناء الحجز</label>
                    <input type="hidden" name="show_customer_service_during_booking" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="show_customer_service_during_booking"
                            id="show_customer_service_during_booking"
                            class="form-check-input"
                            value="1"
                            {{ !empty($settings->show_customer_service_during_booking) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_customer_service_during_booking">
                            {{ !empty($settings->show_customer_service_during_booking) ? 'مفعل' : 'غير مفعل' }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">الموقع الخارجي</h6>
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label d-block">إظهار الموقع الخارجي للعميل</label>
                    <input type="hidden" name="show_public_site" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="show_public_site"
                            id="show_public_site"
                            class="form-check-input"
                            value="1"
                            {{ (int)($settings->show_public_site ?? 1) === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_public_site">
                            {{ (int)($settings->show_public_site ?? 1) === 1 ? 'مفعل' : 'غير مفعل' }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">عند تعطيله سيتم تحويل الزائر مباشرة إلى صفحة الدخول للوحة التحكم.</small>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
