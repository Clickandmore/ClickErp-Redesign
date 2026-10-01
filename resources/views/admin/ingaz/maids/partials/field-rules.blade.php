{{--
    قواعد حقول نموذج السيرة (إضافة/تعديل): صيغ الإدخال والحد الأقصى للحروف، الحقول المطلوبة،
    الأرقام وحدودها، حدود التواريخ، صيغ وأحجام الملفات ومعاينة الصور، البحث في قائمة الجنسيات،
    ومكتب الاستقدام والراتب حسب الجنسية، وزر مسح الفيديو.
    الحدود نفسها يتحقق منها الخادم في MaidRequest عبر App\Support\MaidFieldRules.
    يُضمَّن قبل سكربت النموذج الرئيسي لأن ذلك السكربت يستخدم window.maidDropifyOptions و window.maidFieldRules.
--}}
{{-- حقول الجوال: اختيار الدولة وفحص الرقم حسب قواعد ترقيمها (libphonenumber) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.11.2/build/css/intlTelInput.min.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.11.2/build/js/intlTelInputWithUtils.min.js"></script>
<style>
    #maidForm .iti {
        width: 100%;
        direction: ltr;
    }

    #maidForm .iti__country-list {
        text-align: left;
    }

    #maidForm .maid-required-star {
        color: #dc3545;
        margin-inline-start: 2px;
    }

    #maidForm select.form-control:not([multiple]) {
        -webkit-appearance: none;
        appearance: none;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-size: 14px 10px;
        background-position: right .75rem center;
        padding-right: 2.25rem;
    }

    [dir="rtl"] #maidForm select.form-control:not([multiple]) {
        background-position: left .75rem center;
        padding-right: .75rem;
        padding-left: 2.25rem;
    }

    #maidForm .select2-container--default .select2-selection--multiple {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-size: 14px 10px;
        background-position: right .75rem center;
        padding-right: 2.25rem;
        cursor: pointer;
    }

    [dir="rtl"] #maidForm .select2-container--default .select2-selection--multiple {
        background-position: left .75rem center;
        padding-right: 0;
        padding-left: 2.25rem;
    }

    /* الحقل غير الصحيح: خلفية حمراء فاتحة مع الإبقاء على سهم القائمة */
    #maidForm .form-control.is-invalid {
        background-color: #fff5f5;
    }

    #maidForm select.form-control.is-invalid:not([multiple]) {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23dc3545' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    }

    #maidForm input[type="date"].is-invalid {
        color: #b42318;
    }

    #maidForm .select2-selection.border-danger {
        border-color: #dc3545 !important;
        background-color: #fff5f5;
    }

    #maidForm .maid-field-flash {
        display: block;
        font-size: 12px;
        color: #b42318;
        margin-top: 4px;
    }

    #maidForm .maid-salary-hint a {
        cursor: pointer;
    }
</style>
<script>
    (function($) {
        @php
            // الشاشة المضمِّنة يمكنها تخصيص صيغ/حجم ملف معيّن بتمرير المتغير fileRules.
            $maidFieldRulesConfig = \App\Support\MaidFieldRules::clientConfig();
            if (!empty($fileRules)) {
                $maidFieldRulesConfig['files'] = array_merge($maidFieldRulesConfig['files'], $fileRules);
            }
        @endphp
        var config = @json($maidFieldRulesConfig);

        var messages = {
            latinName: 'اسم العاملة بالإنجليزية يقبل الحروف الإنجليزية والمسافات فقط',
            passportChars: 'رقم الجواز يقبل الحروف الإنجليزية والأرقام فقط',
            passportLength: 'رقم الجواز يجب أن يكون من ' + config.passportMinLength + ' إلى ' +
                config.passportMaxLength + ' خانة',
            borderNumber: 'رقم الحدود يقبل الأرقام فقط',
            lettersOnly: 'هذا الحقل يقبل الحروف والمسافات فقط، بدون أرقام أو رموز',
            salaryChars: 'الراتب يقبل أرقامًا صحيحة أكبر من صفر فقط (بدون سالب أو كسور)',
            integerChars: 'هذا الحقل يقبل أرقامًا صحيحة فقط',
            phoneChars: 'رقم الجوال يقبل الأرقام فقط',
            idNumber: 'رقم الهوية يجب أن يكون من 5 إلى 30 خانة (أرقام أو حروف إنجليزية أو -)',
            badDate: 'التاريخ غير مكتمل أو غير صحيح، يرجى تصحيحه',
            birthTooOld: 'تاريخ الميلاد غير منطقي: عمر العاملة أكبر من ' + config.maxAge + ' سنة',
            birthTooYoung: 'تاريخ الميلاد غير منطقي: عمر العاملة أقل من ' + config.minAge + ' سنة',
            issueFuture: 'تاريخ إصدار جواز السفر لا يمكن أن يكون في المستقبل',
            issueTooOld: 'تاريخ إصدار جواز السفر لا يمكن أن يكون أقدم من ' + config.passportIssueMaxYears + ' سنة',
            expiryPast: 'جواز السفر منتهي: تاريخ الانتهاء يجب ألا يكون تاريخًا سابقًا',
            expiryBeforeIssue: 'تاريخ انتهاء جواز السفر يجب أن يكون بعد تاريخ الإصدار',
            required: 'هذا الحقل مطلوب',
            chooseNationalityFirst: 'اختر الجنسية أولاً',
            searchNationality: 'ابحث أو اختر الجنسية',
            packageSalary: 'راتب باقة الجنسية',
            applyPackageSalary: 'تطبيق',
            imageExtension: 'صيغة الملف غير مدعومة. الصيغ المسموحة: @{{ value }}',
            fileRejectedTitle: 'تعذر اختيار الملف',
            fileType: 'صيغة الملف غير مدعومة. الصيغ المسموحة: ',
            fileSize: 'حجم الملف أكبر من الحد المسموح (',
            durationChars: 'مدة العقد تقبل عددًا صحيحًا موجبًا من الشهور فقط (بدون سالب أو كسور)',
            idNumberSaudi: 'رقم الهوية يجب أن يتكون من 10 أرقام بالضبط ويبدأ بالرقم 1 (هوية وطنية) أو 2 (إقامة)، بدون حروف أو رموز أو مسافات',
            idNumberDigits: 'رقم الهوية يقبل الأرقام فقط',
            issueBeforeBirth: 'تاريخ إصدار جواز السفر يجب أن يكون بعد تاريخ الميلاد',
            experienceBeforeWorkAge: 'بداية الخبرة يجب ألا تسبق بلوغ العاملة ' + config.minWorkAge + ' سنة حسب تاريخ الميلاد (من ',
            arrivalBeforeWorkAge: 'تاريخ الوصول يجب ألا يسبق بلوغ العاملة ' + config.minWorkAge + ' سنة حسب تاريخ الميلاد (من ',
            hiddenInvalid: 'قيمة غير صحيحة في حقل '
        };

        // وصف كل حقل رقمي لرسائل الحدود.
        var numberLabels = {
            salary: {
                label: 'الراتب',
                unit: ''
            },
            height: {
                label: 'الطول',
                unit: ' سم'
            },
            weight: {
                label: 'الوزن',
                unit: ' كجم'
            },
            childrens_count: {
                label: 'عدد الأطفال',
                unit: ''
            },
            duration: {
                label: 'مدة العقد',
                unit: ' شهرًا'
            }
        };

        function toWesternDigits(value) {
            return String(value || '')
                .replace(/[٠-٩]/g, function(d) {
                    return String(d.charCodeAt(0) - 0x0660);
                })
                .replace(/[۰-۹]/g, function(d) {
                    return String(d.charCodeAt(0) - 0x06F0);
                });
        }

        function digitsOnly(value, maxLength, keepSingleZero) {
            // الكسر العشري يُحذف (1500.50 ← 1500) بدل دمج أرقامه في العدد.
            var digits = toWesternDigits(value).split(/[.,٫]/)[0].replace(/[^0-9]/g, '');
            digits = keepSingleZero ? digits.replace(/^0+(?=\d)/, '') : digits.replace(/^0+/, '');
            return digits.slice(0, maxLength);
        }

        function lettersOnly(value) {
            return String(value || '').replace(/[^\p{L}\p{M} ]/gu, '').replace(/^ +/, '');
        }

        // الجوال: أرقام ومسافات، و+ في البداية فقط.
        function phoneChars(value) {
            return toWesternDigits(value).replace(/[^0-9 +]/g, '').replace(/(?!^)\+/g, '');
        }

        // تنظيف الإدخال أثناء الكتابة: تُحذف الحروف غير المسموحة فورًا مع تنبيه قصير.
        var sanitizers = {
            'name:en': {
                clean: function(value) {
                    return String(value || '').replace(/[^A-Za-z .'\-]/g, '').replace(/^[ .'\-]+/, '');
                },
                message: messages.latinName
            },
            passport_num: {
                clean: function(value) {
                    return toWesternDigits(value).toUpperCase().replace(/[^A-Z0-9]/g, '')
                        .slice(0, config.passportMaxLength);
                },
                message: messages.passportChars
            },
            border_number: {
                clean: function(value) {
                    return digitsOnly(value, config.borderNumberMaxLength, true);
                },
                message: messages.borderNumber
            },
            passport_issue_place: {
                clean: lettersOnly,
                message: messages.lettersOnly
            },
            relative_name: {
                clean: lettersOnly,
                message: messages.lettersOnly
            },
            residence_city: {
                clean: lettersOnly,
                message: messages.lettersOnly
            },
            phone: {
                clean: phoneChars,
                message: messages.phoneChars
            },
            relative_phone: {
                clean: phoneChars,
                message: messages.phoneChars
            },
            salary: {
                clean: function(value) {
                    return digitsOnly(value, String(config.numbers.salary.max).length, false);
                },
                message: messages.salaryChars
            },
            height: {
                clean: function(value) {
                    return digitsOnly(value, 3, false);
                },
                message: messages.integerChars
            },
            weight: {
                clean: function(value) {
                    return digitsOnly(value, 3, false);
                },
                message: messages.integerChars
            },
            childrens_count: {
                clean: function(value) {
                    return digitsOnly(value, 2, true);
                },
                message: messages.integerChars
            },
            duration: {
                clean: function(value) {
                    return digitsOnly(value, 2, false);
                },
                message: messages.durationChars
            },
            id_number: {
                clean: function(value) {
                    return toWesternDigits(value).replace(/[^0-9]/g, '').slice(0, 10);
                },
                message: messages.idNumberDigits
            }
        };

        var dateRules = {
            birth_date: {
                min: config.birthDateMin,
                max: config.birthDateMax,
                minMessage: messages.birthTooOld,
                maxMessage: messages.birthTooYoung
            },
            passport_issue_date: {
                min: config.passportIssueDateMin,
                max: config.today,
                minMessage: messages.issueTooOld,
                maxMessage: messages.issueFuture
            },
            passport_expiry_date: {
                min: config.today,
                minMessage: messages.expiryPast
            }
        };

        var mimeToExtension = {
            'image/jpeg': 'jpg',
            'image/pjpeg': 'jpg',
            'image/png': 'png',
            'image/gif': 'gif',
            'image/bmp': 'bmp',
            'image/x-ms-bmp': 'bmp',
            'image/webp': 'webp',
            'application/pdf': 'pdf',
            'video/mp4': 'mp4',
            'video/quicktime': 'mov',
            'video/x-msvideo': 'avi',
            'video/x-ms-wmv': 'wmv',
            'video/webm': 'webm',
            'video/x-matroska': 'mkv',
            'video/x-m4v': 'm4v'
        };

        function getForm() {
            return document.getElementById('maidForm');
        }

        function isEditForm() {
            var form = getForm();
            var method = form ? form.querySelector('input[name="_method"]') : null;
            return !!(method && String(method.value).toUpperCase() === 'PUT');
        }

        // في التعديل لا تُفرض القواعد الجديدة على قيمة محفوظة لم يغيّرها المستخدم (مثل الخادم).
        function isUnchangedStoredValue(input) {
            return isEditForm() &&
                typeof input.dataset.initialValue !== 'undefined' &&
                input.value === input.dataset.initialValue;
        }

        // الحقل ظاهر ومفعّل؛ الحقول داخل قسم "بيانات إضافية" المطوي تُعد ظاهرة.
        function isActiveField(input) {
            if (!input || input.disabled || input.type === 'hidden') {
                return false;
            }
            var form = getForm();
            for (var node = input; node && node !== form; node = node.parentElement) {
                if (window.getComputedStyle(node).display === 'none' &&
                    !node.classList.contains('accordion-collapse')) {
                    return false;
                }
            }
            return true;
        }

        function select2Container(input) {
            var $next = $(input).next('.select2');
            return $next.length ? $next : null;
        }

        // العنصر الذي تُوضع بعده الرسائل: غلاف select2 أو غلاف حقل الجوال أو الحقل نفسه.
        function messageAnchor(input) {
            var $iti = $(input).closest('.iti');
            return select2Container(input) || ($iti.length ? $iti : $(input));
        }

        function flash(input, message) {
            var $anchor = messageAnchor(input);
            var $flash = $anchor.siblings('.maid-field-flash');

            if (!$flash.length) {
                $flash = $('<small class="maid-field-flash"></small>');
                var $after = $anchor.nextAll('.invalid-feedback, .valid-feedback').last();
                ($after.length ? $after : $anchor).after($flash);
            }

            $flash.text(message).stop(true, true).show();
            clearTimeout(input._maidFlashTimer);
            input._maidFlashTimer = setTimeout(function() {
                $flash.fadeOut(200);
            }, 2500);
        }

        function feedbackFor(input) {
            var $anchor = messageAnchor(input);
            var $feedback = $anchor.siblings('.maid-field-rule-feedback');
            if (!$feedback.length) {
                $feedback = $('<div class="invalid-feedback maid-field-rule-feedback"></div>');
                $anchor.after($feedback);
            }
            return $feedback;
        }

        // يعرض خطأ القاعدة أو يزيله؛ لا يزيل إلا ما وضعته هذه القواعد (لا يمس أخطاء الخادم).
        function showFieldState(input, error) {
            var $input = $(input);
            var $select2 = select2Container(input);

            if (error) {
                $input.addClass('is-invalid');
                if ($select2) {
                    $select2.find('.select2-selection').addClass('border-danger');
                }
                feedbackFor(input).text(error).addClass('d-block');
                return;
            }

            var $feedback = messageAnchor(input).siblings('.maid-field-rule-feedback.d-block');
            if (!$feedback.length) {
                return;
            }
            $feedback.text('').removeClass('d-block');
            $input.removeClass('is-invalid');
            if ($select2) {
                $select2.find('.select2-selection').removeClass('border-danger');
            }
        }

        function sanitizeInput(input) {
            var sanitizer = input && sanitizers[input.name];
            if (!sanitizer || input.readOnly) {
                return false;
            }

            var raw = input.value;
            var cleaned = sanitizer.clean(raw);
            if (cleaned === raw) {
                return false;
            }

            var caret = null;
            try {
                caret = input.selectionStart;
            } catch (error) {
                caret = null;
            }
            var caretAfter = caret === null ? null : sanitizer.clean(raw.slice(0, caret)).length;

            input.value = cleaned;
            if (caretAfter !== null && document.activeElement === input) {
                try {
                    input.setSelectionRange(caretAfter, caretAfter);
                } catch (error) {
                    // بعض أنواع الحقول لا تدعم تحديد المؤشر.
                }
            }
            flash(input, sanitizer.message);

            return true;
        }

        function passportError(value) {
            value = String(value || '').trim();
            if (!value) {
                return '';
            }
            if (!/^[A-Za-z0-9]+$/.test(value)) {
                return messages.passportChars;
            }
            if (value.length < config.passportMinLength || value.length > config.passportMaxLength) {
                return messages.passportLength;
            }
            return '';
        }

        function dateError(input) {
            var rule = dateRules[input.name];
            if (!rule) {
                return '';
            }
            if (input.validity && input.validity.badInput) {
                return messages.badDate;
            }

            var value = input.value;
            if (!value || isUnchangedStoredValue(input)) {
                return '';
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                return messages.badDate;
            }
            if (rule.min && value < rule.min) {
                return rule.minMessage;
            }
            if (rule.max && value > rule.max) {
                return rule.maxMessage;
            }
            if (input.name === 'passport_expiry_date') {
                var issueInput = getForm().querySelector('[name="passport_issue_date"]');
                if (issueInput && issueInput.value && value < issueInput.value) {
                    return messages.expiryBeforeIssue;
                }
            }

            return '';
        }

        function numberError(input) {
            var rule = config.numbers[input.name];
            var value = String(input.value || '').trim();
            if (!rule || !value || isUnchangedStoredValue(input)) {
                return '';
            }

            var meta = numberLabels[input.name];
            if (!/^\d+$/.test(value)) {
                return sanitizers[input.name].message;
            }

            var number = parseInt(value, 10);
            if (number < rule.min || number > rule.max) {
                return meta.label + ' يجب أن يكون بين ' + rule.min + ' و' + rule.max + meta.unit;
            }

            return '';
        }

        function sanitizerError(input) {
            var sanitizer = sanitizers[input.name];
            if (!sanitizer || input.name === 'passport_num') {
                return '';
            }
            // الاسم الإنجليزي يُفرض دائمًا (مثل الخادم)، وباقي الحقول عند تغييرها فقط.
            if (input.name !== 'name:en' && isUnchangedStoredValue(input)) {
                return '';
            }
            return input.value !== sanitizer.clean(input.value) ? sanitizer.message : '';
        }

        function isEmpty(input) {
            var value = $(input).val();
            if (Array.isArray(value)) {
                return value.length === 0;
            }
            return value === null || typeof value === 'undefined' || String(value).trim() === '';
        }

        function fieldLabel(input) {
            var $label = $(input).closest('[class*="col-"], td').find('label').first();
            if (!$label.length) {
                return '';
            }
            return $label.clone().children().remove().end().text().replace(/[*:]/g, '').replace(/\s+/g, ' ').trim();
        }

        function requiredMessage(input) {
            var label = fieldLabel(input);
            if (!label) {
                return messages.required;
            }
            return input.tagName === 'SELECT' ? 'يرجى اختيار ' + label : 'حقل ' + label + ' مطلوب';
        }

        function isRequired(input) {
            return input.required || input.name === 'salary';
        }

        function fieldError(input) {
            if (!isActiveField(input) || input.readOnly) {
                return '';
            }
            if (input.type === 'date' && input.validity && input.validity.badInput) {
                return messages.badDate;
            }
            if (isEmpty(input)) {
                return isRequired(input) ? requiredMessage(input) : '';
            }
            if (phoneWidgets[input.name]) {
                return phoneError(input);
            }
            if (input.name === 'id_number') {
                var idValue = String(input.value).trim();
                if (isUnchangedStoredValue(input)) {
                    return /^[0-9A-Za-z-]{5,30}$/.test(idValue) ? '' : messages.idNumber;
                }
                return /^[12]\d{9}$/.test(idValue) ? '' : messages.idNumberSaudi;
            }

            var ruleError = dateError(input) || birthRelatedError(input) || numberError(input) || sanitizerError(input);
            if (ruleError) {
                return ruleError;
            }

            // أي قيمة يرفضها المتصفح نفسه (بريد إلكتروني، حدود min/max، ...) تُعرض رسالتها تحت الحقل.
            if (input.validity && !input.validity.valid && input.validationMessage &&
                !(dateRules[input.name] && isUnchangedStoredValue(input))) {
                return input.validationMessage;
            }

            return '';
        }

        // تاريخ الميلاد هو المرجع لباقي التواريخ: إصدار الجواز بعده، وبداية الخبرة والوصول بعد بلوغ سن العمل.
        function birthValue() {
            var birth = getForm().querySelector('[name="birth_date"]');
            return birth && /^\d{4}-\d{2}-\d{2}$/.test(birth.value) ? birth : null;
        }

        function addYears(date, years) {
            return (parseInt(date.slice(0, 4), 10) + years) + date.slice(4);
        }

        function birthRelatedError(input) {
            var birth = birthValue();
            var value = String(input.value || '');
            if (!birth || !value) {
                return '';
            }
            if (isUnchangedStoredValue(input) && isUnchangedStoredValue(birth)) {
                return '';
            }
            var workStart = addYears(birth.value, config.minWorkAge);
            if (input.name === 'passport_issue_date' && value <= birth.value) {
                return messages.issueBeforeBirth;
            }
            if (input.name === 'arrival_date' && value < workStart) {
                return messages.arrivalBeforeWorkAge + workStart + ')';
            }
            if (input.classList.contains('exp-start-date') && /^\d{4}-\d{2}$/.test(value) && value < workStart.slice(0, 7)) {
                return messages.experienceBeforeWorkAge + workStart.slice(0, 7) + ')';
            }
            return '';
        }

        function birthDependentInputs() {
            return Array.prototype.slice.call(getForm().querySelectorAll('[name="passport_issue_date"], [name="arrival_date"], .exp-start-date'));
        }

        function validateField(input) {
            if (dateRules[input.name]) {
                syncDateBounds(input);
            }
            showFieldState(input, fieldError(input));
        }

        // حدود min/max تُقيّد منتقي التاريخ، ولا تُضاف إذا كانت القيمة الحالية خارجها
        // حتى لا يمنع المتصفح الحفظ بدون رسالة واضحة؛ رسالتنا الخاصة تتولى ذلك.
        function syncDateBounds(input) {
            var rule = dateRules[input.name];
            if (!rule) {
                return;
            }
            var value = input.value;
            var withinRange = !value || ((!rule.min || value >= rule.min) && (!rule.max || value <= rule.max));

            if (withinRange) {
                rule.min ? input.setAttribute('min', rule.min) : input.removeAttribute('min');
                rule.max ? input.setAttribute('max', rule.max) : input.removeAttribute('max');
            } else {
                input.removeAttribute('min');
                input.removeAttribute('max');
            }
        }

        function revealField(input) {
            var collapse = $(input).closest('.accordion-collapse:not(.show)').get(0);
            if (collapse && window.bootstrap && window.bootstrap.Collapse) {
                window.bootstrap.Collapse.getOrCreateInstance(collapse, {
                    toggle: false
                }).show();
            }
        }

        // يُستدعى من سكربت الحفظ في النموذج ويرجع قائمة رسائل الأخطاء.
        function validateAll() {
            var form = getForm();
            var errors = [];
            if (!form) {
                return errors;
            }

            var names = Object.keys(sanitizers).concat(Object.keys(dateRules), ['id_number']).filter(function(name, index, all) {
                return name !== 'passport_num' && all.indexOf(name) === index;
            });

            names.forEach(function(name) {
                var input = form.querySelector('[name="' + name + '"]');
                if (!input) {
                    return;
                }
                var error = fieldError(input);
                if (dateRules[name]) {
                    syncDateBounds(input);
                }
                showFieldState(input, error);
                if (error) {
                    revealField(input);
                    errors.push(error);
                }
            });

            birthDependentInputs().forEach(function(input) {
                if (input.name === 'passport_issue_date') {
                    return; // ضمن dateRules أعلاه
                }
                var error = fieldError(input);
                showFieldState(input, error);
                if (error) {
                    revealField(input);
                    errors.push(error);
                }
            });

            var passportInput = form.querySelector('[name="passport_num"]');
            if (isActiveField(passportInput) && !passportInput.readOnly && !isUnchangedStoredValue(passportInput)) {
                var passportMessage = passportError(passportInput.value);
                if (passportMessage) {
                    $(passportInput).addClass('is-invalid');
                    $('#passport_num_feedback').removeClass('valid-feedback').addClass('invalid-feedback')
                        .text(passportMessage);
                    errors.push(passportMessage);
                }
            }

            return errors;
        }

        // ===== جوال العاملة وجوال قريبها: رمز الدولة + فحص حسب ترقيم الدولة =====
        var phoneFieldNames = ['phone', 'relative_phone'];
        var phoneWidgets = {};

        // دولة الجوال الافتراضية من اسم الجنسية (رمز الهاتف المخزن في الجنسيات غير موثوق).
        var nationalityCountries = [
            [['الفلبين', 'فلبين', 'philippin', 'filipin'], 'ph'],
            [['كينيا', 'kenya'], 'ke'],
            [['اوغندا', 'أوغندا', 'uganda'], 'ug'],
            [['الهند', 'هندي', 'india'], 'in'],
            [['سيريلانكا', 'سريلانكا', 'sri lanka', 'srilanka'], 'lk'],
            [['بنجلاديش', 'بنغلاديش', 'bangladesh'], 'bd'],
            [['باكستان', 'pakistan'], 'pk'],
            [['اثيوبيا', 'إثيوبيا', 'ethiop', 'ethiob'], 'et'],
            [['مصر', 'egypt'], 'eg'],
            [['السودان', 'sudan'], 'sd'],
            [['المغرب', 'morocco'], 'ma'],
            [['اندونيسيا', 'إندونيسيا', 'indonesia'], 'id'],
            [['بروندي', 'بوروندي', 'burundi'], 'bi'],
            [['اليمن', 'yemen'], 'ye'],
            [['نيبال', 'nepal'], 'np'],
            [['تنزانيا', 'tanzania'], 'tz'],
            [['غانا', 'ghana'], 'gh'],
            [['نيجيريا', 'nigeria'], 'ng'],
            [['رواندا', 'rwanda'], 'rw'],
            [['تشاد', 'chad'], 'td'],
            [['مالي', 'mali'], 'ml'],
            [['سيراليون', 'sierra'], 'sl'],
            [['فيتنام', 'vietnam'], 'vn'],
            [['تايلاند', 'thailand'], 'th'],
            [['اريتريا', 'إريتريا', 'eritrea'], 'er'],
            [['الصومال', 'somalia'], 'so'],
            [['السعودية', 'saudi'], 'sa']
        ];

        function nationalityCountry() {
            var $option = $('#Pnationality_id option:selected');
            if (!$option.length || !$option.val()) {
                return '';
            }
            var text = normalizeSearch($option.text() + ' ' + ($option.data('search') || ''));
            for (var i = 0; i < nationalityCountries.length; i++) {
                var names = nationalityCountries[i][0];
                for (var j = 0; j < names.length; j++) {
                    if (text.indexOf(normalizeSearch(names[j])) !== -1) {
                        return nationalityCountries[i][1];
                    }
                }
            }
            return '';
        }

        function dialCodeFor(iso2) {
            var list = window.intlTelInput && window.intlTelInput.getCountryData ?
                window.intlTelInput.getCountryData() : [];
            for (var i = 0; i < list.length; i++) {
                if (list[i].iso2 === iso2) {
                    return String(list[i].dialCode);
                }
            }
            return '';
        }

        // الأرقام المحفوظة قديمًا بدون "+" (مثل 254712345678) تُقرأ كرقم دولي إن بدأت برمز دولة الجنسية.
        function toInitialPhone(value, country) {
            var digits = toWesternDigits(value).replace(/[^\d+]/g, '');
            if (digits.indexOf('00') === 0) {
                digits = '+' + digits.slice(2);
            }
            var dial = dialCodeFor(country);
            if (digits && digits.charAt(0) !== '+' && dial && digits.indexOf(dial) === 0 &&
                digits.length > dial.length + 6) {
                digits = '+' + digits;
            }
            return digits;
        }

        // في التعديل: الرقم المحفوظ الذي لم يلمسه المستخدم يُرسل كما هو ولا يُفحص (مثل الخادم).
        function phoneUnchanged(input) {
            return isEditForm() && input.dataset.phoneTouched !== '1';
        }

        function phoneError(input) {
            var iti = phoneWidgets[input.name];
            if (!iti || !String(input.value).trim() || phoneUnchanged(input)) {
                return '';
            }
            if (iti.isValidNumber()) {
                return '';
            }

            var country = iti.getSelectedCountryData() || {};
            var reasons = {
                1: 'رمز الدولة غير صحيح',
                2: 'الرقم أقصر من المطلوب',
                3: 'الرقم أطول من المطلوب',
                5: 'عدد أرقام الرقم غير صحيح'
            };
            var reason = reasons[iti.getValidationError()] || 'تحقق من بداية الرقم وعدد أرقامه';

            return 'رقم الجوال غير صحيح' + (country.name ? ' لدولة ' + country.name : '') +
                (country.dialCode ? ' (+' + country.dialCode + ')' : '') + ': ' + reason;
        }

        function initPhoneFields(form) {
            if (!window.intlTelInput) {
                return;
            }

            var defaultCountry = nationalityCountry() || 'sa';

            phoneFieldNames.forEach(function(name) {
                var input = form.querySelector('[name="' + name + '"]');
                if (!input || phoneWidgets[name]) {
                    return;
                }

                input.dataset.storedValue = input.value;
                input.value = toInitialPhone(input.value, defaultCountry);
                input.setAttribute('dir', 'ltr');
                input.setAttribute('inputmode', 'tel');

                phoneWidgets[name] = window.intlTelInput(input, {
                    initialCountry: defaultCountry,
                    separateDialCode: true,
                    strictMode: true,
                    formatAsYouType: false,
                    formatOnDisplay: false,
                    countryOrder: ['sa', 'ph', 'ke', 'ug', 'in', 'lk', 'bd', 'et', 'id', 'np', 'pk'],
                    countrySearch: true
                });

                input.addEventListener('input', function() {
                    input.dataset.phoneTouched = '1';
                });
                input.addEventListener('countrychange', function() {
                    if (input.value) {
                        input.dataset.phoneTouched = '1';
                    }
                    if ($(input).hasClass('is-invalid')) {
                        validateField(input);
                    }
                });
            });
        }

        // عند اختيار الجنسية: حقول الجوال الفارغة تنتقل إلى دولة الجنسية.
        function syncPhoneCountries() {
            var country = nationalityCountry();
            if (!country) {
                return;
            }
            phoneFieldNames.forEach(function(name) {
                var iti = phoneWidgets[name];
                var input = getForm().querySelector('[name="' + name + '"]');
                if (iti && input && !String(input.value).trim()) {
                    iti.setCountry(country);
                }
            });
        }

        // يُستدعى بعد new FormData في سكربت الحفظ: الجوال يُرسل بالصيغة الدولية +رمز الدولة.
        function prepareFormData(formData) {
            phoneFieldNames.forEach(function(name) {
                var iti = phoneWidgets[name];
                var input = getForm().querySelector('[name="' + name + '"]');
                if (!iti || !input) {
                    return;
                }
                if (phoneUnchanged(input)) {
                    formData.set(name, input.dataset.storedValue || '');
                } else if (!String(input.value).trim()) {
                    formData.set(name, '');
                } else {
                    formData.set(name, iti.getNumber());
                }
            });
            return formData;
        }

        // ===== علامة * بجانب اسم كل حقل إلزامي (تتبع تغيّر الإلزام حسب نوع السيرة) =====
        function fieldForLabel($label) {
            var id = $label.attr('for');
            var form = getForm();
            if (id && id !== 'TextInput') {
                var byId = document.getElementById(id);
                if (byId && form.contains(byId)) {
                    return byId;
                }
            }
            var selector = 'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea';
            var $field = $label.nextAll(selector).first();
            if (!$field.length) {
                $field = $label.parent().find(selector).first();
            }
            return $field.get(0) || null;
        }

        function syncRequiredMarkers() {
            var form = getForm();
            if (!form) {
                return;
            }
            $(form).find('label').not('.form-check-label').each(function() {
                var $label = $(this);
                var field = fieldForLabel($label);
                var required = !!field && (field.required || field.name === 'salary');
                var $star = $label.children('.maid-required-star');
                var hasOwnStar = $label.children('span.text-danger').not('.maid-required-star').filter(function() {
                    return $(this).text().trim() === '*';
                }).length > 0;

                if (required && !hasOwnStar && !$star.length) {
                    $label.append('<span class="maid-required-star" aria-hidden="true">*</span>');
                } else if (!required && $star.length) {
                    $star.remove();
                }
            });
        }

        // ===== الحد الأقصى لعدد الحروف =====
        function applyMaxLengths(form) {
            $(form).find('input[type="text"], input[type="tel"], input[type="email"], input[type="url"], input[type="search"], input:not([type]), textarea').each(function() {
                var limit = config.maxLengths[this.name] ||
                    (this.tagName === 'TEXTAREA' ? config.defaultTextareaMaxLength : config.defaultTextMaxLength);
                var current = parseInt(this.getAttribute('maxlength'), 10);

                if (!current || current > limit) {
                    this.setAttribute('maxlength', limit);
                }
            });
        }

        // المتصفح يتجاهل maxlength في حقول type="number"، فيُقص طولها هنا.
        function limitNumberLength(input) {
            if (!input || input.type !== 'number' || input.readOnly) {
                return;
            }
            var limit = parseInt(input.getAttribute('maxlength'), 10) || config.numberMaxLength;
            if (String(input.value).length > limit) {
                input.value = String(input.value).slice(0, limit);
                flash(input, 'الحد الأقصى ' + limit + ' خانات');
            }
        }

        // ===== صيغة وحجم الملفات (يشمل السحب والإفلات) =====
        function fileError(input) {
            var rule = config.files[input.name];
            var file = input.files && input.files[0];
            if (!rule || !file) {
                return '';
            }

            var name = String(file.name || '');
            var extension = name.lastIndexOf('.') !== -1 ? name.split('.').pop().toLowerCase() : '';
            var mimeExtension = mimeToExtension[String(file.type || '').toLowerCase()];
            var allowed = rule.extensions.indexOf(extension) !== -1 ||
                (!!mimeExtension && rule.extensions.indexOf(mimeExtension) !== -1);

            if (!allowed) {
                return messages.fileType + rule.extensions.map(function(ext) {
                    return ext.toUpperCase();
                }).join('، ');
            }

            if (file.size > rule.max_kb * 1024) {
                return messages.fileSize + Math.round(rule.max_kb / 1024) + ' ميجابايت). حجم الملف المختار ' +
                    (file.size / 1024 / 1024).toFixed(1) + ' ميجابايت';
            }

            return '';
        }

        // بعد رفض ملف: إفراغ الحقل وإرجاع معاينة Dropify لحالتها (الصورة المحفوظة إن وُجدت).
        function resetRejectedFile(input) {
            input.value = '';
            var dropify = $(input).data('dropify');
            if (!dropify) {
                return;
            }
            dropify.resetPreview();
            dropify.resetFile();
            if (dropify.settings.defaultFile) {
                dropify.file.name = dropify.cleanFilename(dropify.settings.defaultFile);
                dropify.setPreview(dropify.isImage(), dropify.settings.defaultFile);
            }
        }

        function rejectFile(input, message) {
            resetRejectedFile(input);
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: messages.fileRejectedTitle,
                    text: message,
                    confirmButtonText: 'حسناً'
                });
            } else {
                window.alert(message);
            }
        }

        // ===== زر مسح الفيديو: يظهر فقط عند وجود ملف فيديو مرفوع =====
        function syncVideoClearButton() {
            var input = document.getElementById('maid_video');
            if (!input) {
                return;
            }
            var hasSelectedFile = !!(input.files && input.files.length);
            var hasStoredVideo = input.dataset.existingVideo === '1' && $('#remove_maid_video').val() !== '1';
            $('.js-clear-maid-video').toggleClass('d-none', !(hasSelectedFile || hasStoredVideo));
        }

        // ===== الراتب حسب باقة الجنسية (قابل للتعديل) =====
        var lastAutoSalary = null;

        function packageSalary() {
            var $option = $('#Pnationality_id option:selected');
            if (!$option.length || !$option.val()) {
                return null;
            }

            var experienceStatus = $('#experience_status').val();
            var experienced = parseFloat($option.data('salary-experienced')) || 0;
            var inexperienced = parseFloat($option.data('salary-inexperienced')) || 0;
            var monthly = parseFloat($option.data('salary')) || 0;
            var salary = experienceStatus === 'has_experience' ? experienced :
                (experienceStatus === 'no_experience' ? inexperienced : 0);

            salary = Math.round(salary || monthly);
            return salary > 0 ? salary : null;
        }

        function salaryInput() {
            return $('#maidForm input[name="salary"]');
        }

        function renderSalaryHint(suggested) {
            var $salary = salaryInput();
            var $hint = $salary.siblings('.maid-salary-hint');

            if (!$hint.length) {
                $hint = $('<small class="text-muted d-block mt-1 maid-salary-hint"></small>');
                $salary.after($hint);
            }

            if (suggested === null) {
                $hint.addClass('d-none').empty();
                return;
            }

            var current = parseFloat($salary.val()) || 0;
            $hint.removeClass('d-none').empty()
                .append(document.createTextNode(messages.packageSalary + ': ' + suggested + ' '));

            if (current !== suggested && !$salary.prop('readonly')) {
                $('<a class="text-primary text-decoration-underline"></a>')
                    .text(messages.applyPackageSalary)
                    .on('click', function() {
                        $salary.val(suggested);
                        lastAutoSalary = suggested;
                        validateField($salary.get(0));
                        renderSalaryHint(suggested);
                    })
                    .appendTo($hint);
            }
        }

        // يُملأ الراتب تلقائيًا ما لم يكن المستخدم قد أدخل راتبًا مختلفًا بنفسه.
        function applyPackageSalary() {
            var $salary = salaryInput();
            if (!$salary.length) {
                return;
            }

            var suggested = packageSalary();
            var current = parseFloat($salary.val()) || 0;
            var untouched = current === 0 || (lastAutoSalary !== null && current === lastAutoSalary);

            if (suggested !== null && untouched && !$salary.prop('readonly')) {
                $salary.val(suggested);
                lastAutoSalary = suggested;
                validateField($salary.get(0));
            }

            renderSalaryHint(suggested);
        }

        // ===== مكتب الاستقدام: فارغ حتى تُختار الجنسية =====
        function resetOfficeOptions() {
            var $office = $('#maidForm select[name="office_id"]');
            if (!$office.length) {
                return;
            }
            $office.empty().append($('<option value=""></option>').text(messages.chooseNationalityFirst))
                .trigger('change.select2');
        }

        // ===== قائمة الجنسيات قابلة للبحث =====
        function normalizeSearch(text) {
            return String(text || '')
                .replace(/[إأآ]/g, 'ا')
                .replace(/ى/g, 'ي')
                .replace(/ة/g, 'ه')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();
        }

        function initNationalitySearch() {
            var $select = $('#Pnationality_id');
            if (!$select.length || !$.fn.select2) {
                return;
            }

            var lastTerm = '';
            var optionText = function(data) {
                var extra = data.element ? $(data.element).data('search') : '';
                return normalizeSearch((data.text || '') + ' ' + (extra || ''));
            };

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.select2({
                width: '100%',
                dir: document.documentElement.getAttribute('dir') || 'rtl',
                placeholder: messages.searchNationality,
                matcher: function(params, data) {
                    lastTerm = normalizeSearch(params.term);
                    if (!lastTerm || !data.id) {
                        return lastTerm ? null : data;
                    }
                    return optionText(data).indexOf(lastTerm) !== -1 ? data : null;
                },
                // الجنسيات التي تبدأ بالحروف المكتوبة تظهر أولاً.
                sorter: function(results) {
                    if (!lastTerm) {
                        return results;
                    }
                    var startsWith = function(item) {
                        return optionText(item).split(' ').some(function(word) {
                            return word.indexOf(lastTerm) === 0;
                        }) ? 0 : 1;
                    };
                    return results.slice().sort(function(a, b) {
                        return startsWith(a) - startsWith(b);
                    });
                }
            });

            // فتح القائمة بالكتابة مباشرة عند التركيز عليها بلوحة المفاتيح.
            $select.on('select2:open', function() {
                var searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            });
        }

        // ===== معاينة الصور =====
        // Dropify يحدد الصورة من امتداد الاسم فقط: يفشل مع روابط التخزين التي تحمل
        // ?query، ومع امتدادات مثل JFIF/WEBP. نزيل الاستعلام ونعتمد نوع الملف الفعلي.
        if (window.Dropify && !window.Dropify.__maidPreviewPatched) {
            window.Dropify.prototype.getFileType = function() {
                var name = String(this.file && this.file.name || '').split(/[?#]/)[0];
                var extension = name.lastIndexOf('.') !== -1 ? name.split('.').pop().toLowerCase() : '';
                var object = this.file && this.file.object;
                var mimeExtension = object && object.type ? mimeToExtension[String(object.type).toLowerCase()] : null;

                if (mimeExtension && this.settings.imgFileExtensions.indexOf(extension) === -1) {
                    return mimeExtension;
                }

                return extension;
            };
            window.Dropify.__maidPreviewPatched = true;
        }

        window.maidDropifyOptions = {
            imgFileExtensions: config.imageExtensions,
            maxFileSizePreview: '25M',
            error: {
                fileExtension: messages.imageExtension
            }
        };

        window.maidFieldRules = {
            config: config,
            sanitizeInput: sanitizeInput,
            passportError: passportError,
            validateAll: validateAll,
            applyPackageSalary: applyPackageSalary,
            resetOfficeOptions: resetOfficeOptions,
            prepareFormData: prepareFormData,
            syncRequiredMarkers: syncRequiredMarkers
        };

        $(function() {
            var form = getForm();
            if (!form) {
                return;
            }

            applyMaxLengths(form);
            initPhoneFields(form);

            Object.keys(sanitizers).concat(Object.keys(dateRules)).forEach(function(name) {
                var input = form.querySelector('[name="' + name + '"]');
                if (!input) {
                    return;
                }
                input.dataset.initialValue = input.value;
                syncDateBounds(input);
            });

            // مرحلة الالتقاط على النموذج تسبق معالجات الحقل نفسه:
            // التنظيف يسبق فحص تكرار الجواز، ورفض الملف يمنع Dropify من عرضه.
            form.addEventListener('input', function(event) {
                sanitizeInput(event.target);
                limitNumberLength(event.target);
            }, true);

            form.addEventListener('change', function(event) {
                var input = event.target;
                if (!input || input.type !== 'file') {
                    return;
                }
                var error = fileError(input);
                if (error) {
                    event.stopImmediatePropagation();
                    rejectFile(input, error);
                    syncVideoClearButton();
                }
            }, true);

            // القيم التي يملؤها السكربت (قراءة السيرة) تمر عبر jQuery trigger.
            $(form).on('input change', Object.keys(sanitizers).map(function(name) {
                return '[name="' + name + '"]';
            }).join(', '), function() {
                sanitizeInput(this);
            });

            // تنبيه فوري: عند مغادرة الحقل (مطلوب فارغ أو قيمة غير صحيحة)، ويزول بمجرد التصحيح.
            var fieldSelector = 'input:not([type="file"]):not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select, textarea';

            $(form).on('focusout', fieldSelector, function() {
                if (this.name === 'passport_num') {
                    if (isEmpty(this) && !this.readOnly) {
                        $(this).addClass('is-invalid');
                        $('#passport_num_feedback').removeClass('valid-feedback').addClass('invalid-feedback')
                            .text(requiredMessage(this));
                    }
                    return;
                }
                // select2 يُتحقق منه عند إغلاق قائمته.
                if (select2Container(this)) {
                    return;
                }
                validateField(this);
            });

            $(form).on('input change', fieldSelector, function() {
                if (this.name === 'passport_num') {
                    return;
                }
                if (this.tagName === 'SELECT' || $(this).hasClass('is-invalid') || dateRules[this.name]) {
                    validateField(this);
                }
                if (this.name === 'passport_issue_date') {
                    var expiry = form.querySelector('[name="passport_expiry_date"]');
                    if (expiry && expiry.value) {
                        validateField(expiry);
                    }
                }
                if (this.name === 'birth_date') {
                    birthDependentInputs().forEach(function(input) {
                        if (input.value) {
                            validateField(input);
                        }
                    });
                }
                if (this.classList.contains('exp-start-date') || this.name === 'arrival_date') {
                    validateField(this);
                }
            });

            $(form).on('select2:close', 'select', function() {
                validateField(this);
            });

            initNationalitySearch();

            var $nationality = $('#Pnationality_id');
            var $office = $('#maidForm select[name="office_id"]');
            if (!$nationality.val() && !$office.prop('disabled')) {
                resetOfficeOptions();
            }

            $nationality.on('change', function() {
                if (!$(this).val()) {
                    resetOfficeOptions();
                }
                applyPackageSalary();
                syncPhoneCountries();
            });
            $('#experience_status').on('change', applyPackageSalary);

            // في التعديل: إذا كان الراتب المحفوظ يساوي راتب الباقة فهو يتبعها عند تغيير الجنسية،
            // وإن كان مختلفًا فهو راتب مخصص يبقى كما هو.
            lastAutoSalary = packageSalary();
            renderSalaryHint(lastAutoSalary);

            // علامات الحقول الإلزامية: الآن، وكلما تغيّر الإلزام (تبديل نوع السيرة أو حالة الخبرة).
            syncRequiredMarkers();
            var markersTimer = null;
            if (window.MutationObserver) {
                new MutationObserver(function() {
                    clearTimeout(markersTimer);
                    markersTimer = setTimeout(syncRequiredMarkers, 50);
                }).observe(form, {
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['required']
                });
            }

            // المتصفح كان يمنع الحفظ بصمت إذا بقي حقل غير صالح داخل قسم مخفي (مثل مدة الخبرة بعد اختيار «بدون خبرة»).
            // نوقف الفحص التلقائي ونجريه بأنفسنا: الحقل الظاهر يُعرض خطؤه، والمخفي غير المستخدم لا يمنع الحفظ.
            form.noValidate = true;
            form.addEventListener('submit', function(event) {
                var problems = [];
                Array.prototype.forEach.call(form.elements, function(input) {
                    if (!input.willValidate || input.checkValidity()) {
                        return;
                    }
                    if (!isActiveField(input)) {
                        if (input.setCustomValidity) {
                            input.setCustomValidity('');
                        }
                        return;
                    }
                    var message = isEmpty(input) && input.required ? requiredMessage(input) :
                        (fieldLabel(input) ? messages.hiddenInvalid + fieldLabel(input) + ': ' : '') + input.validationMessage;
                    revealField(input);
                    showFieldState(input, message);
                    problems.push(message);
                });
                if (!problems.length) {
                    return;
                }
                event.preventDefault();
                event.stopImmediatePropagation();
                var first = form.querySelector('.is-invalid');
                if (first) {
                    setTimeout(function() {
                        first.scrollIntoView({behavior: 'smooth', block: 'center'});
                    }, 250);
                }
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'تعذّر الحفظ',
                        html: problems.map(function(p) { return '<div>' + $('<div>').text(p).html() + '</div>'; }).join(''),
                        confirmButtonText: 'حسنًا'
                    });
                }
            }, true);

            $('#maid_video').on('change', syncVideoClearButton);
            $(document).on('click', '.js-clear-maid-video', function() {
                setTimeout(syncVideoClearButton, 0);
            });
            syncVideoClearButton();
        });
    })(jQuery);
</script>
