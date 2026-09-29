@extends('admin.layout.indexs.index')

@section('title')
    {{ helperTrans('ingaz.add_client') }}
@endsection

@section('links')
    <li class="breadcrumb-item active"><a href="javascript:void(0)">{{ helperTrans('ingaz.add_client') }}</a></li>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.add_client') }}
@endsection

@section('style')
    <style>
        .client-create-page {
            max-width: 1180px;
            margin: 0 auto;
        }

        .client-create-header,
        .client-form-card,
        .client-form-actions {
            border: 1px solid #e8edf3;
            border-radius: 8px;
            background: #fff;
        }

        .client-create-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px;
            margin-bottom: 16px;
        }

        .client-create-header h4 {
            margin: 0;
            color: #202734;
            font-size: 22px;
            font-weight: 700;
        }

        .client-create-header p {
            margin: 8px 0 0;
            color: #667085;
            font-size: 14px;
        }

        .client-form-card {
            margin-bottom: 16px;
            box-shadow: 0 10px 26px rgba(16, 24, 40, .06);
            overflow: hidden;
        }

        .client-form-card .card-body,
        .client-form-card .card-header {
            padding: 22px 24px;
        }

        .client-form-card .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #eef2f6;
            background: #fbfcfe;
        }

        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: #202734;
            font-size: 16px;
            font-weight: 700;
        }

        .section-heading i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #0f766e;
            background: #e6f6f3;
        }

        .optional-label,
        .required-label {
            display: inline-flex;
            align-items: center;
            height: 24px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .required-label {
            color: #b42318;
            background: #fff1f0;
            border: 1px solid #ffd6d1;
        }

        .optional-label {
            color: #475467;
            background: #f2f4f7;
            border: 1px solid #e4e7ec;
        }

        .form-label {
            color: #344054;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 44px;
            border-color: #d9e0ea;
            border-radius: 8px;
        }

        .input-group .form-control {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .input-group-text {
            color: #344054;
            background: #f8fafc;
            font-weight: 700;
        }

        /* ===== القوائم المنسدلة: شكل موحد مع باقي الحقول ===== */
        .form-control:focus,
        .form-select:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }

        /* القوائم العادية (التصنيف، مفتاح الدولة، شهر/سنة التقويم الهجري) */
        .client-create-page .form-select {
            color: #344054;
            background-color: #fff;
            cursor: pointer;
        }

        html[dir="rtl"] .client-create-page .form-select:not([dir="ltr"] *):not([dir="ltr"]) {
            padding-right: .75rem;
            padding-left: 2.25rem;
            background-position: left .75rem center;
        }

        .client-create-page .form-select:disabled {
            color: #667085;
            background-color: #f2f4f7;
            cursor: not-allowed;
        }

        .client-create-page .form-select-sm {
            min-height: 36px;
            border-radius: 6px;
            font-size: 13px;
        }

        /* مفتاح الدولة بجوار رقم الجوال الآخر */
        .phone-code-select {
            flex: 0 0 auto;
            width: 170px;
            max-width: 45%;
            background-color: #f8fafc !important;
            font-weight: 700;
        }

        /* Select2: الحقل المغلق */
        .client-create-page .select2-container {
            width: 100% !important;
        }

        .client-create-page .select2-container--default .select2-selection--single {
            display: flex;
            align-items: center;
            height: 44px;
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            flex: 1;
            padding: 0 14px;
            color: #344054;
            line-height: 42px;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #98a2b3;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 0;
            width: 36px;
            height: 42px;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__arrow b {
            width: 8px;
            height: 8px;
            margin-top: -6px;
            margin-left: -4px;
            border: solid #667085;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
            transition: transform .15s ease;
        }

        .client-create-page .select2-container--open .select2-selection--single .select2-selection__arrow b {
            margin-top: -2px;
            border-color: #0f766e;
            transform: rotate(-135deg);
        }

        /* في الواجهة العربية: السهم على اليسار ومساحة له حتى لا يغطي النص */
        html[dir="rtl"] .client-create-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            right: auto;
            left: 4px;
        }

        html[dir="rtl"] .client-create-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 14px;
            padding-left: 40px;
            text-align: right;
        }

        html[dir="ltr"] .client-create-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 40px;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__clear {
            margin: 0 4px;
            color: #98a2b3;
            font-size: 18px;
            font-weight: 400;
        }

        .client-create-page .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #b42318;
        }

        .client-create-page .select2-container--default.select2-container--focus .select2-selection--single,
        .client-create-page .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }

        .client-create-page .select2-container--default.select2-container--disabled .select2-selection--single {
            background: #f2f4f7;
            cursor: not-allowed;
        }

        /* حقل به خطأ من التحقق */
        .client-create-page select.is-invalid + .select2-container .select2-selection--single {
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, .12);
        }

        /* Select2: القائمة المفتوحة (تُضاف في نهاية الصفحة، لذلك بدون .client-create-page) */
        .select2-container--default .select2-dropdown {
            overflow: hidden;
            border: 1px solid #d9e0ea;
            border-radius: 8px;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .14);
        }

        .select2-container--default .select2-dropdown--below {
            margin-top: 4px;
        }

        .select2-container--default .select2-dropdown--above {
            margin-top: -4px;
        }

        .select2-container--default .select2-search--dropdown {
            padding: 10px;
            background: #fbfcfe;
            border-bottom: 1px solid #eef2f6;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            height: 38px;
            padding: 0 12px;
            border: 1px solid #d9e0ea;
            border-radius: 6px;
            outline: 0;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, .15);
        }

        .select2-container--default .select2-results > .select2-results__options {
            max-height: 260px;
            padding: 4px;
        }

        .select2-container--default .select2-results__option {
            padding: 9px 12px;
            border-radius: 6px;
            color: #344054;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            color: #0f766e;
            background: #e6f6f3;
        }

        .select2-container--default .select2-results__option[aria-selected="true"],
        .select2-container--default .select2-results__option--selected {
            color: #fff;
            background: #0f766e;
            font-weight: 700;
        }

        .select2-container--default .select2-results__message {
            color: #667085;
            text-align: center;
        }

        .client-form-actions {
            position: sticky;
            bottom: 12px;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 16px;
            box-shadow: 0 10px 26px rgba(16, 24, 40, .08);
        }

        .hijri-calendar-popover {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 1060;
            width: min(350px, 92vw);
            padding: 14px;
            border: 1px solid #d9e0ea;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 14px 34px rgba(16, 24, 40, .18);
        }

        .hijri-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            direction: rtl;
        }

        .hijri-calendar-weekday {
            padding: 7px 2px;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .hijri-calendar-day {
            width: 100%;
            aspect-ratio: 1;
            padding: 0;
            border: 0;
            border-radius: 7px;
            color: #344054;
            background: transparent;
        }

        .hijri-calendar-day:hover {
            color: #0f766e;
            background: #e6f6f3;
        }

        .hijri-calendar-day.is-selected {
            color: #fff;
            background: #0f766e;
            font-weight: 700;
        }

        /* أيام خارج نطاق العمر المسموح */
        .hijri-calendar-day:disabled {
            color: #adb5bd;
            background: transparent;
            cursor: not-allowed;
        }

        @media (max-width: 767.98px) {
            .client-create-header,
            .client-form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .client-create-header .btn,
            .client-form-actions .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <div class="client-create-page">
        <div class="client-create-header">
            <div>
                <h4>{{ helperTrans('ingaz.add_client') }}</h4>
                <p>{{ helperTrans('ingaz.client_required_fields_hint') }}</p>
            </div>

            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>
                {{ helperTrans('ingaz.back') }}
            </a>
        </div>

        <form id="addForm" method="POST" action="{{ route('clients.store') }}" novalidate>
            @csrf
            <input type="hidden" name="phone_code" value="+966">

            <div class="client-form-card">
                <div class="card-body">
                    <div class="section-heading">
                        <i class="fas fa-user-plus"></i>
                        <span>{{ helperTrans('ingaz.basic_information') }}</span>
                        <span class="required-label">{{ helperTrans('ingaz.required') }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label" for="name">{{ helperTrans('ingaz.name') }}</label>
                            <input type="text" id="name" name="name" class="form-control"
                                   value="{{ old('name') }}" maxlength="150" required autofocus
                                   title="{{ helperTrans('ingaz.client_name_invalid_chars') }}">
                            <small class="text-muted d-block mt-1">حروف عربية أو إنجليزية ومسافات فقط، بدون رموز (للشركة يُسمح بالأرقام) — حتى 150 حرفاً.</small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="phone">{{ helperTrans('ingaz.phone') }}</label>
                            <div class="input-group">
                                <span class="input-group-text" dir="ltr">+966</span>
                                <input type="text" id="phone" name="phone" class="form-control" dir="ltr"
                                       value="{{ old('phone') }}" inputmode="numeric" maxlength="9" pattern="5[0-9]{8}"
                                       placeholder="5XXXXXXXX" autocomplete="off" required
                                       title="{{ helperTrans('ingaz.client_phone_saudi_mobile_format') }}">
                            </div>
                            <small class="text-muted d-block mt-1" dir="rtl">9 أرقام تبدأ بالرقم 5 — الصيغة الكاملة: <span dir="ltr">+9665XXXXXXXX</span></small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label d-block">{{ helperTrans('ingaz.client_type') }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input client-type-radio" type="radio" name="client_type"
                                           id="client_type_person" value="person"
                                           {{ old('client_type', 'person') === 'person' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="client_type_person">{{ helperTrans('ingaz.person_client') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input client-type-radio" type="radio" name="client_type"
                                           id="client_type_company" value="company"
                                           {{ old('client_type') === 'company' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="client_type_company">{{ helperTrans('ingaz.company') }}</label>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">اختر "شركة" لإظهار الرقم الضريبي ورقم السجل بدلاً من بيانات الفرد.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="client-form-card">
                <div class="card-header">
                    <div class="section-heading mb-0">
                        <i class="fas fa-id-card"></i>
                        <span>{{ helperTrans('ingaz.additional_information') }}</span>
                    </div>
                    <span class="required-label">{{ helperTrans('ingaz.national_id_required') }}</span>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label" for="email">{{ helperTrans('ingaz.email') }}</label>
                            <input type="email" id="email" name="email" class="form-control" dir="ltr"
                                   value="{{ old('email') }}" maxlength="100" placeholder="example@domain.com"
                                   title="{{ helperTrans('ingaz.client_email_english_only') }}">
                            <small class="text-muted d-block mt-1">بأحرف إنجليزية فقط، بالصيغة <span dir="ltr">name@domain.com</span> — حتى 100 حرف.</small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="nationalitySelect">{{ helperTrans('ingaz.nationality') }}</label>
                            <select name="nationality_id" id="nationalitySelect" class="form-control select2">
                                <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}"
                                            data-is-saudi="{{ $country->is_saudi ? '1' : '0' }}"
                                            {{ old('nationality_id') == $country->id ? 'selected' : '' }}>
                                        {{ $country->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">اكتب أول حروف اسم الدولة للبحث (مثال: "س" ← السعودية، السودان).</small>
                        </div>

                        {{-- يظهر فقط عند اختيار جنسية غير سعودية --}}
                        <div class="col-lg-6 residence-expiry-field d-none">
                            <label class="form-label" for="residence_expiry_date">
                                تاريخ انتهاء الإقامة
                                <span class="text-muted">({{ helperTrans('ingaz.optional') }})</span>
                            </label>
                            <input type="date" id="residence_expiry_date" name="residence_expiry_date"
                                   class="form-control" value="{{ old('residence_expiry_date') }}">
                            <small class="text-muted d-block mt-1">تاريخ ميلادي — اختره من التقويم.</small>
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label" for="national_num">
                                {{ helperTrans('ingaz.national_num') }}
                                <span class="text-danger">({{ helperTrans('ingaz.required') }})</span>
                            </label>
                            <input type="text" id="national_num" name="national_num" class="form-control" dir="ltr"
                                   value="{{ old('national_num') }}" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                                   placeholder="1XXXXXXXXX" autocomplete="off" required
                                   title="{{ helperTrans('ingaz.client_national_num_digits') }}">
                            <small class="text-muted d-block mt-1">10 أرقام فقط، بدون حروف أو مسافات أو رموز.</small>
                        </div>

                        <div class="col-lg-6 company-client-field d-none">
                            <label class="form-label" for="tax_number">{{ helperTrans('ingaz.tax_number') }}</label>
                            <input type="text" id="tax_number" name="tax_number" class="form-control" dir="ltr"
                                   value="{{ old('tax_number') }}" inputmode="numeric" maxlength="15" pattern="3[0-9]{14}"
                                   placeholder="3XXXXXXXXXXXXXX" autocomplete="off"
                                   title="{{ helperTrans('ingaz.client_tax_number_format') }}">
                            <small class="text-muted d-block mt-1">15 رقماً بالضبط تبدأ بالرقم 3، بدون حروف أو مسافات أو رموز — ولا يتكرر لعميل آخر.</small>
                        </div>

                        <div class="col-lg-6 company-client-field d-none">
                            <label class="form-label" for="license_number">{{ helperTrans('ingaz.license_number') }}</label>
                            <input type="text" id="license_number" name="license_number" class="form-control" dir="ltr"
                                   value="{{ old('license_number') }}" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                                   placeholder="XXXXXXXXXX" autocomplete="off"
                                   title="{{ helperTrans('ingaz.client_license_number_format') }}">
                            <small class="text-muted d-block mt-1">10 أرقام بالضبط، بدون حروف أو مسافات أو رموز.</small>
                        </div>

                        <div class="col-lg-6 company-client-field d-none">
                            <label class="form-label" for="license_expiry_date">{{ helperTrans('ingaz.license_expiry_date') }}</label>
                            <input type="date" id="license_expiry_date" name="license_expiry_date" class="form-control"
                                   value="{{ old('license_expiry_date') }}" min="1900-01-01" max="2100-12-31">
                            <small id="license_expiry_hint" class="text-muted d-block mt-1">تاريخ ميلادي — إذا كان التاريخ قد مضى يظهر للعميل بادج "ترخيص منتهي".</small>
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label" for="birthdate">تاريخ الميلاد (ميلادي)</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control"
                                   value="{{ old('birthdate') }}" min="{{ $birthdateMin }}" max="{{ $birthdateMax }}">
                            <small class="text-muted d-block mt-1">
                                يمكن إدخال التاريخ الميلادي أو الهجري. عمر العميل بين
                                {{ \App\Http\Controllers\ingaz\UserController::CLIENT_MIN_AGE }} و
                                {{ \App\Http\Controllers\ingaz\UserController::CLIENT_MAX_AGE }} سنة
                                (من <span dir="ltr">{{ $birthdateMin }}</span> إلى <span dir="ltr">{{ $birthdateMax }}</span>).
                            </small>
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label" for="birthdate_hijri">تاريخ الميلاد (هجري)</label>
                            <div id="birthdate_hijri_picker" class="position-relative">
                                <div class="input-group">
                                    <input type="text" id="birthdate_hijri" class="form-control" dir="ltr"
                                           placeholder="اختر التاريخ الهجري" readonly>
                                    <button type="button" id="birthdate_hijri_toggle"
                                            class="btn btn-outline-secondary" aria-label="فتح التقويم الهجري">
                                        <i class="fas fa-calendar-alt"></i>
                                    </button>
                                </div>

                                <div id="birthdate_hijri_calendar" class="hijri-calendar-popover d-none" dir="rtl">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <button type="button" id="hijri_calendar_previous"
                                                class="btn btn-light btn-sm" aria-label="الشهر السابق">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                        <select id="birthdate_hijri_month" class="form-select form-select-sm"
                                                aria-label="الشهر الهجري"></select>
                                        <select id="birthdate_hijri_year" class="form-select form-select-sm"
                                                aria-label="السنة الهجرية"></select>
                                        <button type="button" id="hijri_calendar_next"
                                                class="btn btn-light btn-sm" aria-label="الشهر التالي">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                    </div>
                                    <div id="birthdate_hijri_weekdays" class="hijri-calendar-grid"></div>
                                    <div id="birthdate_hijri_days" class="hijri-calendar-grid"></div>
                                    <div class="d-flex justify-content-end mt-2 pt-2 border-top">
                                        <button type="button" id="birthdate_hijri_clear"
                                                class="btn btn-link btn-sm text-danger text-decoration-none">
                                            مسح التاريخ
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="birthdate_hijri_feedback" class="text-danger small mt-1 d-none">
                                يرجى اختيار تاريخ هجري صحيح وكامل.
                            </div>
                            <small class="text-muted d-block mt-1">اختر من التقويم بصيغة <span dir="ltr">YYYY-MM-DD</span>، ويُحوَّل تلقائياً إلى الميلادي.</small>
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label d-block">
                                {{ helperTrans('ingaz.marital_status') ?: 'الحالة الاجتماعية' }}
                            </label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach(['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'] as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="marital_status"
                                               id="marital_status_{{ $value }}" value="{{ $value }}"
                                               {{ old('marital_status') === $value ? 'checked' : '' }}>
                                        <label class="form-check-label" for="marital_status_{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted d-block mt-1">تظهر حقول الغرف وأفراد العائلة والأطفال لغير الأعزب فقط.</small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="phone2">{{ helperTrans('ingaz.phone2') }}</label>
                            <div class="input-group" dir="ltr">
                                <select id="phone2_code" name="phone2_code" class="form-select phone-code-select"
                                        aria-label="مفتاح الدولة">
                                    @foreach($phone2CountryCodes as $code => $countryName)
                                        <option value="{{ $code }}" {{ old('phone2_code', '+966') === $code ? 'selected' : '' }}>
                                            {{ $code }} {{ $countryName }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="text" id="phone2" name="phone2" class="form-control" dir="ltr"
                                       value="{{ old('phone2') }}" inputmode="numeric" maxlength="9"
                                       placeholder="5XXXXXXXX" autocomplete="off">
                            </div>
                            <small id="phone2_hint" class="text-muted d-block mt-1" dir="rtl"></small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="client_category_id">{{ __('finance::finance.client_category') }}</label>
                            <select name="client_category_id" id="client_category_id" class="form-select">
                                {!! showSelectElement($client_categories, 'name', old('client_category_id')) !!}
                            </select>
                            <small class="text-muted d-block mt-1">اختر تصنيف العميل من القائمة (اختياري).</small>
                        </div>

                        @if($branchesEnabled ?? false)
                            <div class="col-lg-6">
                                <label class="form-label" for="branch_id">{{ __('admin.branches') }}</label>
                                <input type="hidden" name="branch_id" value="{{ old('branch_id', $defaultBranch?->id) }}">
                                <select id="branch_id" class="form-select" disabled>
                                    @if($defaultBranch)
                                        <option value="{{ $defaultBranch->id }}" selected>{{ $defaultBranch->name }}</option>
                                    @else
                                        <option value="">{{ helperTrans('ingaz.not_specified') }}</option>
                                    @endif
                                </select>
                                <small class="text-muted d-block mt-1">يتم تسجيل العميل على الفرع الرئيسي افتراضياً.</small>
                            </div>
                        @endif

                        <div class="col-lg-6">
                            <label class="form-label" for="address">{{ helperTrans('ingaz.address') }}</label>
                            <input type="text" id="address" name="address" class="form-control"
                                   value="{{ old('address') }}" maxlength="255">
                            <small class="text-muted d-block mt-1">الحي والشارع ورقم المبنى — حتى 255 حرفاً.</small>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="citySelect">{{ helperTrans('ingaz.city') }}</label>
                            <select name="city" id="citySelect" class="form-control select2">
                                <option value="">{{ helperTrans('ingaz.choose') }}</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->name }}" data-country-id="{{ $city->country_id }}"
                                            {{ old('city') == $city->name ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">اختر المدينة من القائمة (تتغير حسب الجنسية).</small>
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field family-field">
                            <label class="form-label" for="rooms_num">{{ helperTrans('ingaz.rooms_num') }}</label>
                            <input type="number" id="rooms_num" name="rooms_num" class="form-control" dir="ltr"
                                   min="0" max="{{ \App\Http\Controllers\ingaz\UserController::MAX_ROOMS_NUM }}" step="1"
                                   value="{{ old('rooms_num') }}">
                            <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_ROOMS_NUM }}.</small>
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field family-field">
                            <label class="form-label" for="family_num">{{ helperTrans('ingaz.family_num') }}</label>
                            <input type="number" id="family_num" name="family_num" class="form-control" dir="ltr"
                                   min="0" max="{{ \App\Http\Controllers\ingaz\UserController::MAX_FAMILY_NUM }}" step="1"
                                   value="{{ old('family_num') }}">
                            <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_FAMILY_NUM }}.</small>
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field family-field">
                            <label class="form-label" for="children">{{ helperTrans('ingaz.children') }}</label>
                            <input type="number" id="children" name="children" class="form-control" dir="ltr"
                                   min="0" max="{{ \App\Http\Controllers\ingaz\UserController::MAX_CHILDREN }}" step="1"
                                   value="{{ old('children') }}">
                            <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_CHILDREN }}.</small>
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field">
                            <label class="form-label" for="job">{{ helperTrans('ingaz.job') }}</label>
                            <input type="text" id="job" name="job" class="form-control" value="{{ old('job') }}" maxlength="100">
                            <small class="text-muted d-block mt-1">حروف ومسافات فقط، بدون أرقام أو رموز — حتى 100 حرف.</small>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="full_name_en">الاسم الكامل بالإنجليزية</label>
                            <input type="text" id="full_name_en" name="full_name_en" class="form-control" dir="ltr"
                                   value="{{ old('full_name_en') }}" maxlength="150" placeholder="Mahmoud Amer Ali">
                            <small class="text-muted d-block mt-1">حروف إنجليزية (A-Z) ومسافات فقط — حتى 150 حرفاً.</small>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="identity_type">نوع الهوية</label>
                            <input type="text" id="identity_type" name="identity_type" class="form-control"
                                   value="{{ old('identity_type') }}" maxlength="50">
                            <small class="text-muted d-block mt-1">مثال: هوية وطنية، إقامة — حتى 50 حرفاً.</small>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="identity_date">تاريخ الهوية (ميلادي)</label>
                            <input type="date" id="identity_date" name="identity_date" class="form-control"
                                   value="{{ old('identity_date') }}" min="1900-01-01" max="2100-12-31">
                            <small class="text-muted d-block mt-1">تاريخ ميلادي — اختره من التقويم.</small>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="workplace">جهة العمل</label>
                            <input type="text" id="workplace" name="workplace" class="form-control"
                                   value="{{ old('workplace') }}" maxlength="150">
                            <small class="text-muted d-block mt-1">اسم جهة العمل — حتى 150 حرفاً.</small>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="po_box">صندوق البريد</label>
                            <input type="text" id="po_box" name="po_box" class="form-control" dir="ltr"
                                   value="{{ old('po_box') }}" maxlength="20">
                            <small class="text-muted d-block mt-1">حتى 20 خانة.</small>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="postal_code">الرقم البريدي</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" dir="ltr"
                                   value="{{ old('postal_code') }}" maxlength="10">
                            <small class="text-muted d-block mt-1">حتى 10 خانات (مثال: 12345).</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">ملاحظات العميل</label>
                            <textarea id="notes" name="notes" class="form-control" rows="4" maxlength="5000">{{ old('notes') }}</textarea>
                            <small class="text-muted d-block mt-1">حتى 5000 حرف.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="client-form-actions">
                <a href="{{ route('clients.index') }}" class="btn btn-light">
                    {{ helperTrans('ingaz.cancel') }}
                </a>
                <button type="submit" id="submitBtn" class="btn btn-success">
                    <i class="fas fa-save me-1"></i>
                    {{ __('admin.confirm') }}
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const chooseText = "{{ helperTrans('ingaz.choose') }}";
            const $nationalitySelect = $('#nationalitySelect');
            const $citySelect = $('#citySelect');
            const oldCity = @json(old('city'));
            const birthdateMin = @json($birthdateMin);
            const birthdateMax = @json($birthdateMax);

            // التواريخ بصيغة YYYY-MM-DD فتصلح المقارنة النصية
            function isBirthdateInRange(value) {
                return !value || (value >= birthdateMin && value <= birthdateMax);
            }

            // توحيد أشكال الحروف العربية (أ/إ/آ ← ا، ى ← ي، ة ← ه) وإزالة التشكيل للبحث
            function normalizeSearchText(value) {
                return String(value || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[ً-ٰٟـ]/g, '')
                    .replace(/[أإآٱ]/g, 'ا')
                    .replace(/ى/g, 'ي')
                    .replace(/ة/g, 'ه');
            }

            // البحث في الجنسية بأول حروف اسم الدولة، مع تجاهل "ال" التعريف (س ← السعودية)
            function countryStartsWithMatcher(params, data) {
                const term = normalizeSearchText(params.term);

                if (!term) {
                    return data;
                }

                if (!data.id) {
                    return null;
                }

                const text = normalizeSearchText(data.text);
                const matches = text.startsWith(term) || text.split(/\s+/).some(function (word) {
                    return word.startsWith(term) || (word.startsWith('ال') && word.slice(2).startsWith(term));
                });

                return matches ? data : null;
            }

            if ($nationalitySelect.length) {
                $nationalitySelect.select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0,
                    matcher: countryStartsWithMatcher,
                    language: {
                        noResults: function () {
                            return @json(helperTrans('ingaz.country_search_no_results'));
                        }
                    }
                });

                // وضع المؤشر في خانة البحث مباشرة عند فتح القائمة
                $nationalitySelect.on('select2:open', function () {
                    setTimeout(function () {
                        const searchField = document.querySelector('.select2-container--open .select2-search__field');
                        if (searchField) {
                            searchField.focus();
                        }
                    }, 0);
                });

                // الكتابة على الحقل وهو مغلق تفتح القائمة وتبدأ البحث بالحرف المكتوب
                $nationalitySelect.next('.select2-container').on('keydown', '.select2-selection', function (e) {
                    if (e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey || e.key === ' ') {
                        return;
                    }

                    e.preventDefault();
                    $nationalitySelect.select2('open');

                    const $search = $('.select2-container--open .select2-search__field');
                    $search.val(e.key).trigger('input');
                });
            }

            // ===== تنقية المدخلات أثناء الكتابة =====
            function toLatinDigits(value) {
                return String(value || '')
                    .replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); })
                    .replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); });
            }

            function setIfChanged(input, value) {
                if (input.value !== value) {
                    input.value = value;
                }
            }

            // الاسم: حروف ومسافات فقط (والشركة يُسمح لها بالأرقام)
            function sanitizeName(value) {
                const isCompany = $('input[name="client_type"]:checked').val() === 'company';
                const disallowed = isCompany ? /[^\p{L}\p{M}\p{N} ]/gu : /[^\p{L}\p{M} ]/gu;

                return String(value || '').replace(disallowed, '').replace(/ {2,}/g, ' ');
            }

            // الجوال: 9 أرقام تبدأ بـ 5 (يقبل لصق 05... أو +966... ويحوّلها)
            function sanitizePhone(value) {
                let digits = toLatinDigits(value).replace(/\D+/g, '');

                if (digits.startsWith('00966')) {
                    digits = digits.slice(5);
                } else if (digits.startsWith('966')) {
                    digits = digits.slice(3);
                }

                digits = digits.replace(/^0+/, '');

                if (digits && digits[0] !== '5') {
                    return '';
                }

                return digits.slice(0, 9);
            }

            // الهوية الوطنية / الإقامة: 10 أرقام فقط
            function sanitizeNationalNum(value) {
                return toLatinDigits(value).replace(/\D+/g, '').slice(0, 10);
            }

            // البريد الإلكتروني: أحرف إنجليزية فقط، بدون مسافات
            function sanitizeEmail(value) {
                return String(value || '').replace(/[^\x21-\x7E]/g, '');
            }

            // الجوال الآخر: نفس قاعدة الجوال الأساسي للسعودية، وأرقام فقط (حتى 12) لبقية الدول
            function isSaudiPhone2() {
                return ($('#phone2_code').val() || '+966') === '+966';
            }

            function sanitizePhone2(value) {
                if (isSaudiPhone2()) {
                    return sanitizePhone(value);
                }

                return toLatinDigits(value).replace(/\D+/g, '').replace(/^0+/, '').slice(0, 12);
            }

            const phone2Hints = {
                saudi: 'للسعودية: 9 أرقام تبدأ بالرقم 5 — الصيغة الكاملة: <span dir="ltr">+9665XXXXXXXX</span>',
                other: 'اختر مفتاح الدولة ثم اكتب الرقم بدون المفتاح وبدون صفر في البداية (6 إلى 12 رقماً) — مثال: <span dir="ltr">CODE + 1XXXXXXXXX</span>'
            };

            function updatePhone2Mode() {
                const saudi = isSaudiPhone2();
                const $phone2 = $('#phone2');

                $phone2.attr({
                    maxlength: saudi ? 9 : 12,
                    placeholder: saudi ? '5XXXXXXXX' : 'XXXXXXXXXX'
                });
                $('#phone2_hint').html(saudi ? phone2Hints.saudi : phone2Hints.other);
                setIfChanged($phone2[0], sanitizePhone2($phone2.val()));
            }

            // الوظيفة: حروف ومسافات فقط
            function sanitizeJob(value) {
                return String(value || '').replace(/[^\p{L}\p{M} ]/gu, '').replace(/ {2,}/g, ' ');
            }

            // الاسم بالإنجليزية: حروف إنجليزية ومسافات فقط
            function sanitizeEnglishName(value) {
                return String(value || '').replace(/[^A-Za-z ]/g, '').replace(/ {2,}/g, ' ');
            }

            // حقول الأعداد: رقم صحيح بين 0 والحد الأعلى للحقل
            function sanitizeCount(input) {
                const digits = toLatinDigits(input.value).replace(/\D+/g, '');

                if (digits === '') {
                    return '';
                }

                return String(Math.min(Number(digits), Number(input.max)));
            }

            // اللصق في حقول الأرقام يُنظَّف قبل تطبيق maxlength (مثل لصق +966 55 123 4567)
            function bindDigitsPaste(selector, sanitize) {
                $(selector).on('paste', function (e) {
                    const clipboard = (e.originalEvent.clipboardData || window.clipboardData).getData('text');

                    e.preventDefault();
                    setIfChanged(this, sanitize(clipboard));
                    $(this).trigger('change');
                });
            }

            $('#name').on('input', function () { setIfChanged(this, sanitizeName(this.value)); });
            $('#phone').on('input', function () { setIfChanged(this, sanitizePhone(this.value)); });
            $('#phone2').on('input', function () { setIfChanged(this, sanitizePhone2(this.value)); });
            $('#national_num').on('input', function () { setIfChanged(this, sanitizeNationalNum(this.value)); });
            $('#email').on('input', function () { setIfChanged(this, sanitizeEmail(this.value)); });
            $('#job').on('input', function () { setIfChanged(this, sanitizeJob(this.value)); });
            $('#full_name_en').on('input', function () { setIfChanged(this, sanitizeEnglishName(this.value)); });
            $('#rooms_num, #family_num, #children').on('input', function () { setIfChanged(this, sanitizeCount(this)); });
            $('#rooms_num, #family_num, #children').on('keydown', function (e) {
                if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
                    e.preventDefault();
                }
            });
            bindDigitsPaste('#phone', sanitizePhone);
            bindDigitsPaste('#phone2', sanitizePhone2);
            bindDigitsPaste('#national_num', sanitizeNationalNum);

            // الرقم الضريبي: 15 رقماً تبدأ بـ 3 — الترخيص: 10 أرقام
            function sanitizeTaxNumber(value) {
                const digits = toLatinDigits(value).replace(/\D+/g, '');

                return digits && digits[0] !== '3' ? '' : digits.slice(0, 15);
            }

            function sanitizeLicenseNumber(value) {
                return toLatinDigits(value).replace(/\D+/g, '').slice(0, 10);
            }

            $('#tax_number').on('input', function () { setIfChanged(this, sanitizeTaxNumber(this.value)); });
            $('#license_number').on('input', function () { setIfChanged(this, sanitizeLicenseNumber(this.value)); });
            bindDigitsPaste('#tax_number', sanitizeTaxNumber);
            bindDigitsPaste('#license_number', sanitizeLicenseNumber);

            // تنبيه فوري إذا كان تاريخ انتهاء الترخيص قد مضى (يُحفظ ويظهر بادج "ترخيص منتهي")
            const licenseExpiryDefaultHint = $('#license_expiry_hint').html();
            $('#license_expiry_date').on('change input', function () {
                const today = new Date().toISOString().slice(0, 10);
                const isExpired = this.value && this.value < today;

                $('#license_expiry_hint')
                    .toggleClass('text-muted', !isExpired)
                    .toggleClass('text-danger fw-bold', !!isExpired)
                    .html(isExpired ? @json(helperTrans('ingaz.license_expired_warning')) : licenseExpiryDefaultHint);
            }).trigger('change');
            $('#phone2_code').on('change', updatePhone2Mode);
            updatePhone2Mode();
            $('.client-type-radio').on('change', function () { $('#name').trigger('input'); });

            // تحقق قبل الإرسال (النموذج novalidate، فالمتصفح لا يتحقق تلقائياً)
            function validateClientFields() {
                const errors = {};
                const isCompany = $('input[name="client_type"]:checked').val() === 'company';
                const name = $('#name').val().trim();
                const phone = $('#phone').val().trim();
                const email = $('#email').val().trim();
                const nationalNum = $('#national_num').val().trim();

                if (name && name !== sanitizeName(name)) {
                    errors.name = [@json(helperTrans('ingaz.client_name_invalid_chars'))];
                }

                if (!/^5\d{8}$/.test(phone)) {
                    errors.phone = [@json(helperTrans('ingaz.client_phone_saudi_mobile_format'))];
                }

                if (email && !/^[\x21-\x7E]+$/.test(email)) {
                    errors.email = [@json(helperTrans('ingaz.client_email_english_only'))];
                }

                if (!isCompany && !/^\d{10}$/.test(nationalNum)) {
                    errors.national_num = [@json(helperTrans('ingaz.client_national_num_digits'))];
                }

                if (isCompany && !/^3\d{14}$/.test($('#tax_number').val().trim())) {
                    errors.tax_number = [@json(helperTrans('ingaz.client_tax_number_format'))];
                }

                if (isCompany && !/^\d{10}$/.test($('#license_number').val().trim())) {
                    errors.license_number = [@json(helperTrans('ingaz.client_license_number_format'))];
                }

                if (isCompany && !$('#license_expiry_date').val()) {
                    errors.license_expiry_date = [@json(helperTrans('ingaz.client_license_expiry_date_required'))];
                }

                const phone2 = $('#phone2').val().trim();
                if (phone2 && !(isSaudiPhone2() ? /^5\d{8}$/ : /^[1-9]\d{5,11}$/).test(phone2)) {
                    errors.phone2 = [isSaudiPhone2()
                        ? @json(helperTrans('ingaz.client_phone2_saudi_format'))
                        : @json(helperTrans('ingaz.client_phone2_international_format'))];
                }

                const birthdate = $('#birthdate').val();
                if (!isCompany && birthdate && !isBirthdateInRange(birthdate)) {
                    errors.birthdate = [@json(__('ingaz.client_birthdate_range', ['min' => \App\Http\Controllers\ingaz\UserController::CLIENT_MIN_AGE, 'max' => \App\Http\Controllers\ingaz\UserController::CLIENT_MAX_AGE]))];
                }

                const job = $('#job').val().trim();
                if (!isCompany && job && job !== sanitizeJob(job)) {
                    errors.job = [@json(helperTrans('ingaz.client_job_letters_only'))];
                }

                const fullNameEn = $('#full_name_en').val().trim();
                if (fullNameEn && fullNameEn !== sanitizeEnglishName(fullNameEn)) {
                    errors.full_name_en = [@json(helperTrans('ingaz.client_full_name_en_english_only'))];
                }

                $('.family-field input:not(:disabled)').each(function () {
                    if (this.value !== '' && (!/^\d+$/.test(this.value) || Number(this.value) > Number(this.max))) {
                        errors[this.name] = [@json(__('ingaz.client_number_max', ['max' => ':max'])).replace(':max', this.max)];
                    }
                });

                return errors;
            }

            if ($('#client_category_id').length) {
                $('#client_category_id').select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });
            }

            function toggleClientTypeFields() {
                if (!$('input[name="client_type"]:checked').length) {
                    $('#client_type_person').prop('checked', true);
                }

                const type = $('input[name="client_type"]:checked').val() || 'person';
                const isCompany = type === 'company';

                $('.person-client-field').toggleClass('d-none', isCompany)
                    .find('input, select, textarea').prop('disabled', isCompany);

                $('.company-client-field').toggleClass('d-none', !isCompany)
                    .find('input, select, textarea').prop('disabled', !isCompany);

                $('#national_num').prop('required', !isCompany);
                $('input[name="marital_status"]').prop('required', false);
                $('#tax_number, #license_number, #license_expiry_date').prop('required', isCompany);

                toggleFamilyFields();
            }

            // حقول الأسرة (الغرف/أفراد العائلة/الأطفال) تظهر فقط للمتزوج أو المطلق أو الأرمل
            const familyMaritalStatuses = @json(\App\Http\Controllers\ingaz\UserController::FAMILY_MARITAL_STATUSES);

            function toggleFamilyFields() {
                const isCompany = ($('input[name="client_type"]:checked').val() || 'person') === 'company';
                const maritalStatus = $('input[name="marital_status"]:checked').val();
                const showFamily = !isCompany && familyMaritalStatuses.includes(maritalStatus);
                const $familyFields = $('.family-field');

                $familyFields.toggleClass('d-none', !showFamily)
                    .find('input').prop('disabled', !showFamily);

                if (!showFamily) {
                    $familyFields.find('input').val('');
                }
            }

            $('.client-type-radio').on('change', toggleClientTypeFields);
            $('input[name="marital_status"]').on('change', toggleFamilyFields);
            toggleClientTypeFields();

            const $birthdate = $('#birthdate');
            const $birthdateHijriPicker = $('#birthdate_hijri_picker');
            const $birthdateHijri = $('#birthdate_hijri');
            const $birthdateHijriCalendar = $('#birthdate_hijri_calendar');
            const $birthdateHijriYear = $('#birthdate_hijri_year');
            const $birthdateHijriMonth = $('#birthdate_hijri_month');
            const $birthdateHijriDays = $('#birthdate_hijri_days');
            const $birthdateHijriFeedback = $('#birthdate_hijri_feedback');
            let syncingBirthdate = false;

            const hijriFormatter = (() => {
                try {
                    const formatter = new Intl.DateTimeFormat('en-US-u-ca-islamic-umalqura-nu-latn', {
                        year: 'numeric',
                        month: 'numeric',
                        day: 'numeric',
                        timeZone: 'UTC'
                    });

                    return formatter.resolvedOptions().calendar === 'islamic-umalqura'
                        ? formatter
                        : null;
                } catch (error) {
                    return null;
                }
            })();

            function normalizeDateDigits(value) {
                const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
                const persianDigits = '۰۱۲۳۴۵۶۷۸۹';

                return String(value || '')
                    .trim()
                    .replace(/[٠-٩]/g, digit => arabicDigits.indexOf(digit))
                    .replace(/[۰-۹]/g, digit => persianDigits.indexOf(digit))
                    .replace(/[/.]/g, '-')
                    .replace(/\s+/g, '');
            }

            function parseDateParts(value) {
                const match = normalizeDateDigits(value).match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);

                if (!match) {
                    return null;
                }

                return {
                    year: Number(match[1]),
                    month: Number(match[2]),
                    day: Number(match[3])
                };
            }

            function formatDateParts(parts) {
                return [
                    String(parts.year).padStart(4, '0'),
                    String(parts.month).padStart(2, '0'),
                    String(parts.day).padStart(2, '0')
                ].join('-');
            }

            function hijriPartsFromTimestamp(timestamp) {
                if (!hijriFormatter) {
                    return null;
                }

                const values = {};
                hijriFormatter.formatToParts(new Date(timestamp)).forEach(part => {
                    if (part.type === 'year' || part.type === 'month' || part.type === 'day') {
                        values[part.type] = Number(part.value);
                    }
                });

                return values.year && values.month && values.day ? values : null;
            }

            function compareDateParts(left, right) {
                if (left.year !== right.year) {
                    return left.year - right.year;
                }
                if (left.month !== right.month) {
                    return left.month - right.month;
                }
                return left.day - right.day;
            }

            function gregorianToHijri(value) {
                const parts = parseDateParts(value);

                if (!parts) {
                    return null;
                }

                const timestamp = Date.UTC(parts.year, parts.month - 1, parts.day, 12);
                const date = new Date(timestamp);

                if (
                    date.getUTCFullYear() !== parts.year ||
                    date.getUTCMonth() !== parts.month - 1 ||
                    date.getUTCDate() !== parts.day
                ) {
                    return null;
                }

                return hijriPartsFromTimestamp(timestamp);
            }

            function hijriToGregorian(value) {
                const target = parseDateParts(value);

                if (
                    !target ||
                    target.year < 1200 ||
                    target.year > 1600 ||
                    target.month < 1 ||
                    target.month > 12 ||
                    target.day < 1 ||
                    target.day > 30 ||
                    !hijriFormatter
                ) {
                    return null;
                }

                const dayMilliseconds = 86400000;
                let low = Math.floor(Date.UTC(target.year + 577, 0, 1, 12) / dayMilliseconds);
                let high = Math.floor(Date.UTC(target.year + 581, 11, 31, 12) / dayMilliseconds);

                while (low <= high) {
                    const middle = Math.floor((low + high) / 2);
                    const current = hijriPartsFromTimestamp(middle * dayMilliseconds);

                    if (!current) {
                        return null;
                    }

                    const comparison = compareDateParts(current, target);

                    if (comparison === 0) {
                        const date = new Date(middle * dayMilliseconds);
                        return {
                            year: date.getUTCFullYear(),
                            month: date.getUTCMonth() + 1,
                            day: date.getUTCDate()
                        };
                    }

                    if (comparison < 0) {
                        low = middle + 1;
                    } else {
                        high = middle - 1;
                    }
                }

                return null;
            }

            function setHijriPickerInvalid(isInvalid) {
                $birthdateHijri.toggleClass('is-invalid', isInvalid);
                $birthdateHijriFeedback.toggleClass('d-none', !isInvalid);
            }

            function selectedHijriDate() {
                return parseDateParts($birthdateHijri.val());
            }

            function setSelectValue($select, value) {
                if (value && !$select.find('option[value="' + value + '"]').length) {
                    $select.append(new Option(value, value));
                }

                $select.val(value ? String(value) : '');
            }

            function populateHijriPicker() {
                if (!hijriFormatter) {
                    $('#birthdate_hijri_toggle').prop('disabled', true);
                    $birthdateHijriFeedback
                        .removeClass('d-none')
                        .text('المتصفح الحالي لا يدعم تقويم أم القرى.');
                    return;
                }

                const currentHijriDate = hijriPartsFromTimestamp(Date.now());
                const hijriMonths = [
                    'محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر',
                    'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان',
                    'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة'
                ];
                const weekdays = ['أحد', 'اثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'];

                const maxHijriDate = gregorianToHijri(birthdateMax) || currentHijriDate;
                const minHijriDate = gregorianToHijri(birthdateMin);

                for (let year = maxHijriDate.year; year >= (minHijriDate ? minHijriDate.year : 1200); year--) {
                    $birthdateHijriYear.append(new Option(year, year));
                }

                hijriMonths.forEach((month, index) => {
                    $birthdateHijriMonth.append(new Option(month, index + 1));
                });

                weekdays.forEach(day => {
                    $('#birthdate_hijri_weekdays').append(
                        $('<div>', {class: 'hijri-calendar-weekday', text: day})
                    );
                });

                setSelectValue($birthdateHijriYear, maxHijriDate.year);
                setSelectValue($birthdateHijriMonth, maxHijriDate.month);
                renderHijriCalendar();
            }

            function renderHijriCalendar() {
                const year = Number($birthdateHijriYear.val());
                const month = Number($birthdateHijriMonth.val());
                const selectedDate = selectedHijriDate();
                const firstGregorianDate = hijriToGregorian(formatDateParts({year, month, day: 1}));

                $birthdateHijriDays.empty();

                if (!firstGregorianDate) {
                    return;
                }

                const firstWeekday = new Date(Date.UTC(
                    firstGregorianDate.year,
                    firstGregorianDate.month - 1,
                    firstGregorianDate.day
                )).getUTCDay();

                for (let blank = 0; blank < firstWeekday; blank++) {
                    $birthdateHijriDays.append('<span></span>');
                }

                for (let day = 1; day <= 30; day++) {
                    const hijriDate = {year, month, day};

                    const gregorianDate = hijriToGregorian(formatDateParts(hijriDate));

                    if (!gregorianDate) {
                        continue;
                    }

                    const isSelected = selectedDate
                        && selectedDate.year === year
                        && selectedDate.month === month
                        && selectedDate.day === day;

                    $birthdateHijriDays.append(
                        $('<button>', {
                            type: 'button',
                            class: 'hijri-calendar-day' + (isSelected ? ' is-selected' : ''),
                            text: day,
                            'data-day': day,
                            'aria-label': formatDateParts(hijriDate),
                            disabled: !isBirthdateInRange(formatDateParts(gregorianDate))
                        })
                    );
                }
            }

            function syncHijriFromGregorian() {
                if (syncingBirthdate) {
                    return;
                }

                if (!hijriFormatter) {
                    return;
                }

                syncingBirthdate = true;
                const hijriDate = $birthdate.val() ? gregorianToHijri($birthdate.val()) : null;

                $birthdateHijri.val(hijriDate ? formatDateParts(hijriDate) : '');
                if (hijriDate) {
                    setSelectValue($birthdateHijriYear, hijriDate.year);
                    setSelectValue($birthdateHijriMonth, hijriDate.month);
                }
                renderHijriCalendar();
                setHijriPickerInvalid(false);
                syncingBirthdate = false;
            }

            function syncGregorianFromHijri() {
                if (syncingBirthdate) {
                    return;
                }

                if (!hijriFormatter) {
                    return true;
                }

                const hijriDate = selectedHijriDate();

                if (!hijriDate) {
                    setHijriPickerInvalid(false);
                    return true;
                }

                const gregorianDate = hijriToGregorian(formatDateParts(hijriDate));

                if (!gregorianDate || !isBirthdateInRange(formatDateParts(gregorianDate))) {
                    setHijriPickerInvalid(true);
                    return false;
                }

                syncingBirthdate = true;
                setHijriPickerInvalid(false);
                $birthdate.val(formatDateParts(gregorianDate));
                syncingBirthdate = false;
                return true;
            }

            populateHijriPicker();
            $birthdate.on('change', syncHijriFromGregorian);
            $('#birthdate_hijri_toggle, #birthdate_hijri').on('click', function () {
                $birthdateHijriCalendar.toggleClass('d-none');
            });
            $birthdateHijriYear.add($birthdateHijriMonth).on('change', renderHijriCalendar);
            $birthdateHijriDays.on('click', '.hijri-calendar-day', function () {
                const hijriDate = {
                    year: Number($birthdateHijriYear.val()),
                    month: Number($birthdateHijriMonth.val()),
                    day: Number($(this).data('day'))
                };

                $birthdateHijri.val(formatDateParts(hijriDate));
                syncGregorianFromHijri();
                renderHijriCalendar();
                $birthdateHijriCalendar.addClass('d-none');
            });
            $('#hijri_calendar_previous, #hijri_calendar_next').on('click', function () {
                let year = Number($birthdateHijriYear.val());
                let month = Number($birthdateHijriMonth.val());
                const direction = this.id === 'hijri_calendar_next' ? 1 : -1;

                month += direction;
                if (month < 1) {
                    month = 12;
                    year--;
                } else if (month > 12) {
                    month = 1;
                    year++;
                }

                setSelectValue($birthdateHijriYear, year);
                setSelectValue($birthdateHijriMonth, month);
                renderHijriCalendar();
            });
            $('#birthdate_hijri_clear').on('click', function () {
                $birthdateHijri.val('');
                $birthdate.val('');
                setHijriPickerInvalid(false);
                renderHijriCalendar();
                $birthdateHijriCalendar.addClass('d-none');
            });
            $(document).on('click', function (event) {
                if (!$(event.target).closest($birthdateHijriPicker).length) {
                    $birthdateHijriCalendar.addClass('d-none');
                }
            });

            if ($birthdate.val()) {
                syncHijriFromGregorian();
            }

            if ($citySelect.length) {
                const citiesData = $citySelect.find('option').not('[value=""]').map(function () {
                    const $option = $(this);
                    const countryId = $option.data('countryId');

                    return {
                        value: $option.val(),
                        text: $option.text(),
                        countryId: countryId !== undefined && countryId !== null ? String(countryId) : ''
                    };
                }).get();

                const filterCities = function (countryId) {
                    const targetCountry = countryId ? String(countryId) : '';

                    $citySelect.find('option').not('[value=""]').remove();

                    citiesData.forEach(function (city) {
                        if (!targetCountry || city.countryId === targetCountry) {
                            const option = new Option(city.text, city.value, false, city.value === oldCity);
                            option.setAttribute('data-country-id', city.countryId);
                            $citySelect.append(option);
                        }
                    });
                };

                filterCities($nationalitySelect.val());

                $citySelect.select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });

                if (oldCity) {
                    $citySelect.val(oldCity).trigger('change');
                } else {
                    $citySelect.val('').trigger('change');
                }

                $nationalitySelect.on('change', function () {
                    filterCities($(this).val());
                    $citySelect.val('').trigger('change');
                });
            }

            // تاريخ انتهاء الإقامة يظهر للجنسيات غير السعودية فقط
            (function () {
                const $nationality = $('#nationalitySelect');
                const $field = $('.residence-expiry-field');
                const $input = $('#residence_expiry_date');

                if (!$nationality.length || !$field.length) {
                    return;
                }

                // الإقامة تخص الأفراد فقط، فلا يظهر الحقل للشركات
                const toggleResidenceExpiry = function () {
                    const isSaudi = String($nationality.find('option:selected').data('is-saudi')) === '1';
                    const hasNationality = !!$nationality.val();
                    const isCompany = $('input[name="client_type"]:checked').val() === 'company';

                    if (hasNationality && !isSaudi && !isCompany) {
                        $field.removeClass('d-none');
                        $input.prop('disabled', false);
                        return;
                    }

                    $field.addClass('d-none');
                    $input.val('').prop('disabled', true);
                };

                $nationality.on('change', toggleResidenceExpiry);
                $('.client-type-radio').on('change', toggleResidenceExpiry);
                toggleResidenceExpiry();
            })();

            $('#addForm').on('submit', function (e) {
                e.preventDefault();

                const $form = $(this);
                const $btn = $('#submitBtn');
                const originalHtml = $btn.html();

                if (!syncGregorianFromHijri()) {
                    $birthdateHijri.trigger('focus');
                    return;
                }

                $form.find('.is-invalid').removeClass('is-invalid');

                const clientFieldErrors = validateClientFields();
                if (Object.keys(clientFieldErrors).length) {
                    showClientFormErrors({ errors: clientFieldErrors });
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: $form.attr('action'),
                    data: $form.serialize(),
                    beforeSend: function () {
                        $btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> {{ __("ingaz.sending") }}'
                        );
                    },
                    success: function (response) {
                        if (response.code === 200) {
                            successToster('{{ helperTrans("ingaz.done successfully") }}', response.message);
                            setTimeout(function () {
                                window.location.href = response.url;
                            }, 1000);
                            return;
                        }

                        if (response.code === 422) {
                            showClientFormErrors(response);
                        }
                    },
                    error: function (response) {
                        showClientFormErrors(response);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });

        function showClientFormErrors(response) {
            const payload = response.responseJSON || response;
            const errors = payload && payload.errors ? payload.errors : null;

            if (errors) {
                const messages = [];

                Object.keys(errors).forEach(function (key) {
                    $('[name="' + key + '"]').addClass('is-invalid');
                    errors[key].forEach(function(message) { messages.push($('<div>').text(message).html()); });
                });

                Swal.fire({
                    icon: 'error',
                    title: @json(helperTrans('ingaz.validation_error_title')),
                    html: messages.join('<br>'),
                    confirmButtonText: @json(helperTrans('ingaz.ok'))
                });
                return;
            }

            Swal.fire({
                icon: 'error',
                title: @json(helperTrans('ingaz.error')),
                text: payload && payload.message ? payload.message : @json(helperTrans('ingaz.unexpected_error_try_again')),
                confirmButtonText: @json(helperTrans('ingaz.ok'))
            });
        }
    </script>
@endsection
