@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.form_settings') }}
@endsection

@section('style')
    <style>
        .form-settings-shell .hero-card {
            border: 0;
            border-radius: 20px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
        }

        .form-settings-shell .preview-card,
        .form-settings-shell .editor-card {
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .form-settings-shell .preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .form-settings-shell .section-anchor {
            scroll-margin-top: 100px;
        }

        .form-settings-shell .field-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.82rem;
            margin: 0 0.35rem 0.35rem 0;
        }

        .form-settings-shell .logo-preview {
            max-height: 120px;
            max-width: 100%;
            object-fit: contain;
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        <div class="form-settings-shell">
            <div class="card hero-card mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h3 class="mb-2">{{ __('admin.form_settings') }}</h3>
                            <p class="mb-0 text-white-50">
                                {{ __('admin.form_settings_hint') }}
                            </p>
                        </div>
                        <div class="text-white-50 small">
                            تم نقل إعدادات النماذج من
                            <code class="text-white">/admin/settings/section/content</code>
                            إلى هذه الشاشة.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                @foreach ($templates as $template)
                    <div class="col-xl-6">
                        <div class="card preview-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $template['title'] }}</h5>
                                        <p class="text-muted small mb-0">{{ $template['description'] }}</p>
                                    </div>
                                    <a href="#editor-{{ $template['key'] }}" class="btn btn-sm btn-light border">
                                        الذهاب للتحرير
                                    </a>
                                </div>

                                <div class="mb-3">
                                    @foreach ($template['fields'] as $field)
                                        <span class="field-chip">{{ $field }}</span>
                                    @endforeach
                                </div>

                                <div class="preview-actions">
                                    @foreach ($previewLocales as $previewLocale)
                                        <a class="btn btn-outline-primary btn-sm" target="_blank"
                                           href="{{ route('form-settings.preview', ['template' => $template['key'], 'locale' => $previewLocale]) }}">
                                            معاينة {{ strtoupper($previewLocale) }}
                                        </a>
                                        <a class="btn btn-primary btn-sm" target="_blank"
                                           href="{{ route('form-settings.preview.print', ['template' => $template['key'], 'locale' => $previewLocale]) }}">
                                            طباعة {{ strtoupper($previewLocale) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <form class="row g-4 from-submit-global" method="post"
                  action="{{ route('form-settings.update') }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="col-12 section-anchor" id="editor-shared">
                    <div class="card editor-card">
                        <div class="card-body">
                            <h5 class="mb-3">الهوية المشتركة للنماذج</h5>
                            <div class="alert alert-light border small">
                                يمكن استخدام المتغيرات
                                <code>[اسم_الشركة]</code>
                                و
                                <code>[اسم_المكتب]</code>
                                داخل النصوص الافتراضية ليتم استبدالها تلقائياً وقت الطباعة.
                            </div>
                            <div class="row g-3">
                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">اسم الشركة على النماذج {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="document_company_name:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->document_company_name ?? '' }}"
                                               placeholder="مثال: {{ documentSetting('document_company_name', documentSetting('website_name', config('app.name'))) }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">اسم المكتب على النماذج {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="document_office_name:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->document_office_name ?? '' }}"
                                               placeholder="مثال: {{ documentSetting('document_office_name', documentSetting('website_name', config('app.name'))) }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">تذييل عام للنماذج {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="3" name="document_footer_note:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->document_footer_note ?? '' }}</textarea>
                                    </div>
                                @endforeach

                                <div class="col-md-6">
                                    <label class="form-label">لوجو النماذج والعقود</label>
                                    <input type="file" name="document_logo" class="form-control" accept="image/*">
                                    <small class="text-muted d-block mt-1">
                                        إذا تُرك الحقل فارغاً فسيستمر استخدام اللوجو الحالي.
                                    </small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-block">المعاينة الحالية</label>
                                    @php
                                        $documentLogo = showFile($settings->document_logo ?: $settings->logo_header);
                                    @endphp
                                    @if ($documentLogo)
                                        <div class="border rounded p-3 bg-light text-center">
                                            <img src="{{ $documentLogo }}" alt="لوجو النماذج" class="logo-preview">
                                        </div>
                                    @else
                                        <div class="border rounded p-3 text-muted small">
                                            لا يوجد لوجو مخصص للنماذج حالياً.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 section-anchor" id="editor-delivery-form">
                    <div class="card editor-card">
                        <div class="card-body">
                            <h5 class="mb-3">تحرير نموذج تسليم العمالة</h5>
                            <div class="row g-3">
                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان النموذج {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="delivery_form_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->delivery_form_title ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">النص الافتراضي {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="4" name="delivery_form_default_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->delivery_form_default_text ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 section-anchor" id="editor-shelter-receipt">
                    <div class="card editor-card">
                        <div class="card-body">
                            <h5 class="mb-3">تحرير نموذج الإيواء</h5>
                            <div class="row g-3">
                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان النموذج {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="shelter_receipt_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->shelter_receipt_title ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">العنوان الفرعي {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="shelter_receipt_subtitle:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->shelter_receipt_subtitle ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">نص التعهد {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="4" name="shelter_receipt_default_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->shelter_receipt_default_text ?? '' }}</textarea>
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">نص المخالصة {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="4" name="shelter_receipt_default_settlement_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->shelter_receipt_default_settlement_text ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 section-anchor" id="editor-invoice">
                    <div class="card editor-card">
                        <div class="card-body">
                            <h5 class="mb-3">تحرير الفاتورة</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">الرقم الضريبي</label>
                                    <input type="text" name="tax_number" class="form-control"
                                           value="{{ $settings->tax_number ?? '' }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">هاتف مقدم الخدمة</label>
                                    <input type="text" name="phone" class="form-control"
                                           value="{{ $settings->phone ?? '' }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">هاتف إضافي</label>
                                    <input type="text" name="other_phone" class="form-control"
                                           value="{{ $settings->other_phone ?? '' }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ ($settings->email ?? '') === '#' ? '' : ($settings->email ?? '') }}">
                                </div>

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان مقدم الخدمة {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="location:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->location ?? '' }}">
                                    </div>
                                @endforeach

                                <div class="col-md-4">
                                    <label class="form-label">لوجو هيئة الزكاة والضريبة</label>
                                    <input type="file" name="zatch_logo" class="form-control" accept="image/*">
                                    @php $zatcaLogoPreview = showFile($settings->zatch_logo ?? null); @endphp
                                    @if ($zatcaLogoPreview)
                                        <div class="mt-2 border rounded p-2 bg-light text-center">
                                            <img src="{{ $zatcaLogoPreview }}" alt="لوجو الضريبة" class="logo-preview">
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">QR الفاتورة</label>
                                    <input type="file" name="qr_image" class="form-control" accept="image/*">
                                    @php $qrPreview = showFile($settings->qr_image ?? null); @endphp
                                    @if ($qrPreview)
                                        <div class="mt-2 border rounded p-2 bg-light text-center">
                                            <img src="{{ $qrPreview }}" alt="QR الفاتورة" class="logo-preview">
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">QR احتياطي للفاتورة</label>
                                    <input type="file" name="second_qr_image" class="form-control" accept="image/*">
                                    @php $secondQrPreview = showFile($settings->second_qr_image ?? null); @endphp
                                    @if ($secondQrPreview)
                                        <div class="mt-2 border rounded p-2 bg-light text-center">
                                            <img src="{{ $secondQrPreview }}" alt="QR احتياطي" class="logo-preview">
                                        </div>
                                    @endif
                                </div>

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان الفاتورة {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="invoice_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->invoice_title ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">العنوان الفرعي {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="invoice_subtitle:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->invoice_subtitle ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">تذييل الفاتورة {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="3" name="invoice_footer_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->invoice_footer_text ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 section-anchor" id="editor-rental-contract">
                    <div class="card editor-card">
                        <div class="card-body">
                            <h5 class="mb-3">تحرير عقد الإيجار</h5>
                            <div class="alert alert-light border small">
                                بنود العقد التفصيلية يتم التحكم بها من
                                <a href="{{ route('rent-contract-terms.index') }}" target="_blank">إدارة بنود عقد الإيجار</a>.
                                هذه الحقول تتحكم في رأس العقد، بيانات الأطراف، وعنوان معلومات الخدمة.
                                المتغيرات المتاحة:
                                <code>[اسم_الشركة]</code>
                                <code>[رقم_السجل]</code>
                                <code>[تاريخ_السجل]</code>
                                <code>[عنوان_الشركة]</code>
                                <code>[هاتف_الشركة]</code>
                                <code>[جوال_الشركة]</code>
                                <code>[بريد_الشركة]</code>
                                <code>[رقم_الترخيص]</code>
                            </div>
                            <div class="row g-3">
                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان العقد {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_title ?? '' }}">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">مقدمة العقد {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="5" name="rental_contract_intro_text:{{ $locale }}"
                                                  class="form-control">{{ $settings->translate($locale)->rental_contract_intro_text ?? '' }}</textarea>
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان بيانات الطرف الأول {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_party_one_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_party_one_title ?? '' }}"
                                               placeholder="بيانات الطرف الأول">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان بيانات الطرف الثاني {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_party_two_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_party_two_title ?? '' }}"
                                               placeholder="بيانات الطرف الثاني">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">عنوان معلومات الخدمة {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_service_title:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_service_title ?? '' }}"
                                               placeholder="1. معلومات الخدمة المطلوبة:">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">تسمية رقم سداد {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_sadad_label:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_sadad_label ?? '' }}"
                                               placeholder="رقم سداد">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-md-6">
                                        <label class="form-label">كود رقم سداد {{ getFieldLanguage($locale) }}</label>
                                        <input type="text" name="rental_contract_sadad_code:{{ $locale }}" class="form-control"
                                               value="{{ $settings->translate($locale)->rental_contract_sadad_code ?? '' }}"
                                               placeholder="AQR">
                                    </div>
                                @endforeach

                                @foreach (config('translatable.locales') as $locale)
                                    <div class="col-12">
                                        <label class="form-label">نص بيانات الطرف الأول {{ getFieldLanguage($locale) }}</label>
                                        <textarea rows="4" name="rental_contract_party_one_text:{{ $locale }}"
                                                  class="form-control"
                                                  placeholder="[اسم_الشركة]، سجل تجاري رقم: [رقم_السجل]، صادر بتاريخ: [تاريخ_السجل]، العنوان: [عنوان_الشركة]، هاتف رقم: [هاتف_الشركة]، بريد إلكتروني: [بريد_الشركة]، ترخيص استقدام رقم [رقم_الترخيص]، مركز الاتصال [جوال_الشركة].">{{ $settings->translate($locale)->rental_contract_party_one_text ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end">
                    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                        <button class="btn btn-primary px-4" type="submit">{{ __('buttons.save') }}</button>
                    @endif
                </div>
            </form>
            @include('admin.form-settings.shelter-templates')
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection
