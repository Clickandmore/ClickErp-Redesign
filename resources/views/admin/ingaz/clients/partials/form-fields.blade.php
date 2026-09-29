{{--
    حقول نموذج العميل المشتركة بين شاشتي الإضافة والتعديل.
    $client = null في الإضافة، وسجل العميل في التعديل.
--}}
@php
    $client = $client ?? null;

    // القيمة المعروضة: old() بعد خطأ تحقق، ثم قيمة العميل المحفوظة (في التعديل)
    $fv = fn (string $field, $default = null) => old($field, $client ? ($client->{$field} ?? $default) : $default);
    $fdate = function (string $field) use ($client) {
        $value = $client?->{$field};

        return old($field, $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : null);
    };

    $clientType = $fv('client_type', 'person') === 'company' ? 'company' : 'person';

    // الجوال: الرقم السعودي الموحّد، وإلا يُعرض الرقم القديم كما هو مع تنبيه لتصحيحه
    $canonicalPhone = $client?->canonicalPhoneValue();
    $storedPhone = $client ? trim((string) ($client->source_phone ?: $client->phone)) : '';
    $legacyPhone = $client && !$canonicalPhone && $storedPhone !== '' ? $storedPhone : null;
    $phoneValue = old('phone', $canonicalPhone ?: $storedPhone);

    // تاريخ الهوية كان نصاً حراً (هجري أو ميلادي): يُعرض في التقويم فقط إن كان ميلادياً صحيحاً
    $storedIdentityDate = trim((string) ($client?->identity_date ?? ''));
    $identityDateIsValid = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $storedIdentityDate);
    $identityDateValue = old('identity_date', $identityDateIsValid ? $storedIdentityDate : null);
    $legacyIdentityDate = $storedIdentityDate !== '' && !$identityDateIsValid ? $storedIdentityDate : null;
@endphp
<div class="client-form-card">
    <div class="card-body">
        <div class="section-heading">
            <i class="fas {{ $client ? 'fa-user-edit' : 'fa-user-plus' }}"></i>
            <span>{{ helperTrans('ingaz.basic_information') }}</span>
            <span class="required-label">{{ helperTrans('ingaz.required') }}</span>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label" for="name">{{ helperTrans('ingaz.name') }}</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="{{ $fv('name') }}" maxlength="150" required autofocus
                       title="{{ helperTrans('ingaz.client_name_invalid_chars') }}">
                <small class="text-muted d-block mt-1">حروف عربية أو إنجليزية ومسافات فقط، بدون رموز (للشركة يُسمح بالأرقام) — حتى 150 حرفاً.</small>
            </div>

            <div class="col-lg-6">
                <label class="form-label" for="phone">{{ helperTrans('ingaz.phone') }}</label>
                <div class="input-group">
                    <span class="input-group-text" dir="ltr">+966</span>
                    <input type="text" id="phone" name="phone" class="form-control" dir="ltr"
                           value="{{ $phoneValue }}" inputmode="numeric" maxlength="9" pattern="5[0-9]{8}"
                           placeholder="5XXXXXXXX" autocomplete="off" required
                           title="{{ helperTrans('ingaz.client_phone_saudi_mobile_format') }}">
                </div>
                <small class="text-muted d-block mt-1" dir="rtl">9 أرقام تبدأ بالرقم 5 — الصيغة الكاملة: <span dir="ltr">+9665XXXXXXXX</span></small>
                @if($legacyPhone)
                    <small class="text-danger d-block mt-1">الرقم المحفوظ حالياً (<span dir="ltr">{{ $legacyPhone }}</span>) ليس جوالاً سعودياً صالحاً — أدخل رقماً بالصيغة الصحيحة.</small>
                @endif
            </div>

            <div class="col-lg-6">
                <label class="form-label d-block">{{ helperTrans('ingaz.client_type') }}</label>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input client-type-radio" type="radio" name="client_type"
                               id="client_type_person" value="person"
                               {{ $clientType === 'person' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="client_type_person">{{ helperTrans('ingaz.person_client') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input client-type-radio" type="radio" name="client_type"
                               id="client_type_company" value="company"
                               {{ $clientType === 'company' ? 'checked' : '' }}>
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
                       value="{{ $fv('email') }}" maxlength="100" placeholder="example@domain.com"
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
                                {{ (string) $fv('nationality_id') === (string) $country->id ? 'selected' : '' }}>
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
                       class="form-control" value="{{ $fdate('residence_expiry_date') }}">
                <small class="text-muted d-block mt-1">تاريخ ميلادي — اختره من التقويم.</small>
            </div>

            <div class="col-lg-6 person-client-field">
                <label class="form-label" for="national_num">
                    {{ helperTrans('ingaz.national_num') }}
                    <span class="text-danger">({{ helperTrans('ingaz.required') }})</span>
                </label>
                <input type="text" id="national_num" name="national_num" class="form-control" dir="ltr"
                       value="{{ $fv('national_num') }}" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                       placeholder="1XXXXXXXXX" autocomplete="off" required
                       title="{{ helperTrans('ingaz.client_national_num_digits') }}">
                <small class="text-muted d-block mt-1">10 أرقام فقط، بدون حروف أو مسافات أو رموز.</small>
            </div>

            <div class="col-lg-6 company-client-field d-none">
                <label class="form-label" for="tax_number">{{ helperTrans('ingaz.tax_number') }}</label>
                <input type="text" id="tax_number" name="tax_number" class="form-control" dir="ltr"
                       value="{{ $fv('tax_number') }}" inputmode="numeric" maxlength="15" pattern="3[0-9]{14}"
                       placeholder="3XXXXXXXXXXXXXX" autocomplete="off"
                       title="{{ helperTrans('ingaz.client_tax_number_format') }}">
                <small class="text-muted d-block mt-1">15 رقماً بالضبط تبدأ بالرقم 3، بدون حروف أو مسافات أو رموز — ولا يتكرر لعميل آخر.</small>
            </div>

            <div class="col-lg-6 company-client-field d-none">
                <label class="form-label" for="license_number">{{ helperTrans('ingaz.license_number') }}</label>
                <input type="text" id="license_number" name="license_number" class="form-control" dir="ltr"
                       value="{{ $fv('license_number') }}" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"
                       placeholder="XXXXXXXXXX" autocomplete="off"
                       title="{{ helperTrans('ingaz.client_license_number_format') }}">
                <small class="text-muted d-block mt-1">10 أرقام بالضبط، بدون حروف أو مسافات أو رموز.</small>
            </div>

            <div class="col-lg-6 company-client-field d-none">
                <label class="form-label" for="license_expiry_date">{{ helperTrans('ingaz.license_expiry_date') }}</label>
                <input type="date" id="license_expiry_date" name="license_expiry_date" class="form-control"
                       value="{{ $fdate('license_expiry_date') }}" min="1900-01-01" max="2100-12-31">
                <small id="license_expiry_hint" class="text-muted d-block mt-1">تاريخ ميلادي — إذا كان التاريخ قد مضى يظهر للعميل بادج "ترخيص منتهي".</small>
            </div>

            <div class="col-lg-6 person-client-field">
                <label class="form-label" for="birthdate">تاريخ الميلاد (ميلادي)</label>
                <input type="date" id="birthdate" name="birthdate" class="form-control"
                       value="{{ $fdate('birthdate') }}" min="{{ $birthdateMin }}" max="{{ $birthdateMax }}">
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
                                   {{ $fv('marital_status') === $value ? 'checked' : '' }}>
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
                            <option value="{{ $code }}" {{ old('phone2_code', $phone2Code ?? '+966') === $code ? 'selected' : '' }}>
                                {{ $code }} {{ $countryName }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" id="phone2" name="phone2" class="form-control" dir="ltr"
                           value="{{ old('phone2', $phone2Number ?? '') }}" inputmode="numeric" maxlength="9"
                           placeholder="5XXXXXXXX" autocomplete="off">
                </div>
                <small id="phone2_hint" class="text-muted d-block mt-1" dir="rtl"></small>
            </div>

            <div class="col-lg-6">
                <label class="form-label" for="client_category_id">{{ __('finance::finance.client_category') }}</label>
                <select name="client_category_id" id="client_category_id" class="form-select">
                    {!! showSelectElement($client_categories, 'name', $fv('client_category_id')) !!}
                </select>
                <small class="text-muted d-block mt-1">اختر تصنيف العميل من القائمة (اختياري).</small>
            </div>

            @if($client && ($branchesEnabled ?? false) && ($canViewAllBranches ?? false))
                <div class="col-lg-6">
                    <label class="form-label" for="branch_id">{{ __('admin.branches') }}</label>
                    <select name="branch_id" id="branch_id" class="form-select">
                        <option value="">{{ helperTrans('ingaz.choose') }}</option>
                        @foreach(($branches ?? collect()) as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $client->branch_id) === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted d-block mt-1">اختر فرع العميل من القائمة.</small>
                </div>
            @elseif($client && ($branchesEnabled ?? false))
                <input type="hidden" name="branch_id" value="{{ old('branch_id', $client->branch_id) }}">
            @elseif($branchesEnabled ?? false)
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
                       value="{{ $fv('address') }}" maxlength="255">
                <small class="text-muted d-block mt-1">الحي والشارع ورقم المبنى — حتى 255 حرفاً.</small>
            </div>

            <div class="col-lg-6">
                <label class="form-label" for="citySelect">{{ helperTrans('ingaz.city') }}</label>
                <select name="city" id="citySelect" class="form-control select2">
                    <option value="">{{ helperTrans('ingaz.choose') }}</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->name }}" data-country-id="{{ $city->country_id }}"
                                {{ (string) $fv('city') === (string) $city->name ? 'selected' : '' }}>
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
                       value="{{ $fv('rooms_num') }}">
                <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_ROOMS_NUM }}.</small>
            </div>

            <div class="col-lg-3 col-md-6 person-client-field family-field">
                <label class="form-label" for="family_num">{{ helperTrans('ingaz.family_num') }}</label>
                <input type="number" id="family_num" name="family_num" class="form-control" dir="ltr"
                       min="0" max="{{ \App\Http\Controllers\ingaz\UserController::MAX_FAMILY_NUM }}" step="1"
                       value="{{ $fv('family_num') }}">
                <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_FAMILY_NUM }}.</small>
            </div>

            <div class="col-lg-3 col-md-6 person-client-field family-field">
                <label class="form-label" for="children">{{ helperTrans('ingaz.children') }}</label>
                <input type="number" id="children" name="children" class="form-control" dir="ltr"
                       min="0" max="{{ \App\Http\Controllers\ingaz\UserController::MAX_CHILDREN }}" step="1"
                       value="{{ $fv('children') }}">
                <small class="text-muted d-block mt-1">رقم من 0 إلى {{ \App\Http\Controllers\ingaz\UserController::MAX_CHILDREN }}.</small>
            </div>

            <div class="col-lg-3 col-md-6 person-client-field">
                <label class="form-label" for="job">{{ helperTrans('ingaz.job') }}</label>
                <input type="text" id="job" name="job" class="form-control" value="{{ $fv('job') }}" maxlength="100">
                <small class="text-muted d-block mt-1">حروف ومسافات فقط، بدون أرقام أو رموز — حتى 100 حرف.</small>
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="full_name_en">الاسم الكامل بالإنجليزية</label>
                <input type="text" id="full_name_en" name="full_name_en" class="form-control" dir="ltr"
                       value="{{ $fv('full_name_en') }}" maxlength="150" placeholder="Mahmoud Amer Ali">
                <small class="text-muted d-block mt-1">حروف إنجليزية (A-Z) ومسافات فقط — حتى 150 حرفاً.</small>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="identity_type">نوع الهوية</label>
                <input type="text" id="identity_type" name="identity_type" class="form-control"
                       value="{{ $fv('identity_type') }}" maxlength="50">
                <small class="text-muted d-block mt-1">مثال: هوية وطنية، إقامة — حتى 50 حرفاً.</small>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="identity_date">تاريخ الهوية (ميلادي)</label>
                <input type="date" id="identity_date" name="identity_date" class="form-control"
                       value="{{ $identityDateValue }}" min="1900-01-01" max="2100-12-31">
                <small class="text-muted d-block mt-1">تاريخ ميلادي — اختره من التقويم.</small>
                @if($legacyIdentityDate)
                    <small class="text-danger d-block mt-1">القيمة المحفوظة حالياً (<span dir="ltr">{{ $legacyIdentityDate }}</span>) ليست تاريخاً ميلادياً — اختر التاريخ من التقويم وإلا ستُحذف عند الحفظ.</small>
                @endif
            </div>
            <div class="col-lg-6">
                <label class="form-label" for="workplace">جهة العمل</label>
                <input type="text" id="workplace" name="workplace" class="form-control"
                       value="{{ $fv('workplace') }}" maxlength="150">
                <small class="text-muted d-block mt-1">اسم جهة العمل — حتى 150 حرفاً.</small>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="po_box">صندوق البريد</label>
                <input type="text" id="po_box" name="po_box" class="form-control" dir="ltr"
                       value="{{ $fv('po_box') }}" maxlength="20">
                <small class="text-muted d-block mt-1">حتى 20 خانة.</small>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="postal_code">الرقم البريدي</label>
                <input type="text" id="postal_code" name="postal_code" class="form-control" dir="ltr"
                       value="{{ $fv('postal_code') }}" maxlength="10">
                <small class="text-muted d-block mt-1">حتى 10 خانات (مثال: 12345).</small>
            </div>
            <div class="col-12">
                <label class="form-label" for="notes">ملاحظات العميل</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" maxlength="5000">{{ $fv('notes') }}</textarea>
                <small class="text-muted d-block mt-1">حتى 5000 حرف.</small>
            </div>

            @if($client)
                <div class="col-lg-6">
                    <label for="is_blocked" class="form-label">{{ helperTrans('ingaz.is_blocked') }}</label>
                    <select id="is_blocked" name="is_blocked" class="form-select">
                        <option value="0" {{ (string) old('is_blocked', (int) $client->is_blocked) === '0' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.active') }}
                        </option>
                        <option value="1" {{ (string) old('is_blocked', (int) $client->is_blocked) === '1' ? 'selected' : '' }}>
                            {{ helperTrans('ingaz.blocked') }}
                        </option>
                    </select>
                    <small class="text-muted d-block mt-1">العميل المحظور لا يمكنه استخدام الموقع أو التطبيق.</small>
                </div>
            @endif
        </div>
    </div>
</div>
