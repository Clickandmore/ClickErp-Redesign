{{-- سلوك نموذج العميل المشترك بين شاشتي الإضافة والتعديل؛ الإرسال نفسه تعرّفه كل شاشة --}}
<script>
    $(document).ready(function () {
        const chooseText = "{{ helperTrans('ingaz.choose') }}";
        const $nationalitySelect = $('#nationalitySelect');
        const $citySelect = $('#citySelect');
        const oldCity = @json(old('city', ($client ?? null)?->city));
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

        // تستخدمها شاشة الإضافة/التعديل للتحقق قبل الإرسال
        window.clientForm = {
            validate: validateClientFields,
            preSubmitErrors: function () {
                if (!syncGregorianFromHijri()) {
                    $birthdateHijri.trigger('focus');
                    return { birthdate: ['يرجى اختيار تاريخ هجري صحيح وكامل.'] };
                }

                return validateClientFields();
            }
        };
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
