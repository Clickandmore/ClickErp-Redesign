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

        .select2-container--default .select2-selection--single {
            min-height: 44px;
            border-color: #d9e0ea;
            border-radius: 8px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px;
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
                                   value="{{ old('name') }}" required autofocus>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="phone">{{ helperTrans('ingaz.phone') }}</label>
                            <div class="input-group">
                                <span class="input-group-text" dir="ltr">+966</span>
                                <input type="text" id="phone" name="phone" class="form-control" dir="ltr"
                                       value="{{ old('phone') }}" inputmode="tel" pattern="[0-9+() .-]*" required>
                            </div>
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
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}">
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
                        </div>

                        {{-- يظهر فقط عند اختيار جنسية غير سعودية --}}
                        <div class="col-lg-6 residence-expiry-field d-none">
                            <label class="form-label" for="residence_expiry_date">
                                تاريخ انتهاء الإقامة
                                <span class="text-muted">({{ helperTrans('ingaz.optional') }})</span>
                            </label>
                            <input type="date" id="residence_expiry_date" name="residence_expiry_date"
                                   class="form-control" value="{{ old('residence_expiry_date') }}">
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label" for="national_num">
                                {{ helperTrans('ingaz.national_num') }}
                                <span class="text-danger">({{ helperTrans('ingaz.required') }})</span>
                            </label>
                            <input type="text" id="national_num" name="national_num" class="form-control"
                                   value="{{ old('national_num') }}" required>
                        </div>

                        <div class="col-lg-6 company-client-field d-none">
                            <label class="form-label" for="tax_number">{{ helperTrans('ingaz.tax_number') }}</label>
                            <input type="text" id="tax_number" name="tax_number" class="form-control"
                                   value="{{ old('tax_number') }}">
                        </div>

                        <div class="col-lg-6 company-client-field d-none">
                            <label class="form-label" for="license_number">{{ helperTrans('ingaz.license_number') }}</label>
                            <input type="text" id="license_number" name="license_number" class="form-control"
                                   value="{{ old('license_number') }}">
                        </div>

                        <div class="col-lg-6 person-client-field">
                            <label class="form-label" for="birthdate">تاريخ الميلاد (ميلادي)</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control"
                                   value="{{ old('birthdate') }}">
                            <small class="text-muted d-block mt-1">يمكن إدخال التاريخ الميلادي أو الهجري.</small>
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
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="phone2">{{ helperTrans('ingaz.phone2') }}</label>
                            <input type="text" id="phone2" name="phone2" class="form-control" dir="ltr"
                                   value="{{ old('phone2') }}" inputmode="tel" pattern="[0-9+() .-]*">
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label" for="client_category_id">{{ __('finance::finance.client_category') }}</label>
                            <select name="client_category_id" id="client_category_id" class="form-select">
                                {!! showSelectElement($client_categories, 'name', old('client_category_id')) !!}
                            </select>
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
                                   value="{{ old('address') }}">
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
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field">
                            <label class="form-label" for="rooms_num">{{ helperTrans('ingaz.rooms_num') }}</label>
                            <input type="number" id="rooms_num" name="rooms_num" class="form-control" min="0"
                                   value="{{ old('rooms_num') }}">
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field">
                            <label class="form-label" for="family_num">{{ helperTrans('ingaz.family_num') }}</label>
                            <input type="number" id="family_num" name="family_num" class="form-control" min="0"
                                   value="{{ old('family_num') }}">
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field">
                            <label class="form-label" for="children">{{ helperTrans('ingaz.children') }}</label>
                            <input type="number" id="children" name="children" class="form-control" min="0"
                                   value="{{ old('children') }}">
                        </div>

                        <div class="col-lg-3 col-md-6 person-client-field">
                            <label class="form-label" for="job">{{ helperTrans('ingaz.job') }}</label>
                            <input type="text" id="job" name="job" class="form-control" value="{{ old('job') }}">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="full_name_en">الاسم الكامل بالإنجليزية</label>
                            <input type="text" id="full_name_en" name="full_name_en" class="form-control" dir="ltr" value="{{ old('full_name_en') }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="identity_type">نوع الهوية</label>
                            <input type="text" id="identity_type" name="identity_type" class="form-control" value="{{ old('identity_type') }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="identity_date">تاريخ الهوية</label>
                            <input type="text" id="identity_date" name="identity_date" class="form-control" value="{{ old('identity_date') }}" placeholder="هجري أو ميلادي">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="workplace">جهة العمل</label>
                            <input type="text" id="workplace" name="workplace" class="form-control" value="{{ old('workplace') }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="po_box">صندوق البريد</label>
                            <input type="text" id="po_box" name="po_box" class="form-control" value="{{ old('po_box') }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label" for="postal_code">الرقم البريدي</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" value="{{ old('postal_code') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="notes">ملاحظات العميل</label>
                            <textarea id="notes" name="notes" class="form-control" rows="4">{{ old('notes') }}</textarea>
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

            if ($nationalitySelect.length) {
                $nationalitySelect.select2({
                    placeholder: chooseText,
                    allowClear: true,
                    width: '100%'
                });
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
                $('#tax_number, #license_number').prop('required', isCompany);
            }

            $('.client-type-radio').on('change', toggleClientTypeFields);
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

                for (let year = currentHijriDate.year; year >= 1200; year--) {
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

                setSelectValue($birthdateHijriYear, currentHijriDate.year);
                setSelectValue($birthdateHijriMonth, currentHijriDate.month);
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

                    if (!hijriToGregorian(formatDateParts(hijriDate))) {
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
                            'aria-label': formatDateParts(hijriDate)
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

                if (!gregorianDate) {
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

                const toggleResidenceExpiry = function () {
                    const isSaudi = String($nationality.find('option:selected').data('is-saudi')) === '1';
                    const hasNationality = !!$nationality.val();

                    if (hasNationality && !isSaudi) {
                        $field.removeClass('d-none');
                        return;
                    }

                    $field.addClass('d-none');
                    $input.val('');
                };

                $nationality.on('change', toggleResidenceExpiry);
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
