{{-- تحقق فوري لحقول السائق، وحفظ دون إعادة تحميل الصفحة. القواعد نفسها في DriverRequest وHourlyDriverFieldRules. --}}
<script>
(() => {
    const form = document.getElementById('driver-form');
    if (!form) return;
    const C = @json(\App\Support\HourlyDriverFieldRules::clientConfig());
    const HAS_PASSWORD = @json((bool) $driver->password);
    const feedback = document.getElementById('driver-feedback');
    const el = name => form.elements[name];
    const val = name => (el(name) && !el(name).disabled ? el(name).value.trim() : '');
    const touched = new Set();

    const AR = 'ء-غف-ي';
    const toLatinDigits = v => v.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
    const fmt = d => d.split('-').reverse().join('/');

    // ===== عرض الأخطاء =====
    function box(name) { return form.querySelector('[data-error-for="' + name + '"]'); }
    function setError(name, text) {
        const field = el(name), b = box(name);
        if (field && field.classList) { field.classList.toggle('is-invalid', Boolean(text)); }
        if (b) b.textContent = text || '';
    }
    function scheduleError(day, text) {
        const b = form.querySelector('[data-schedule-error="' + day + '"]');
        if (b) b.textContent = text || '';
        form.querySelectorAll('[name^="work_schedule[' + day + ']"][data-schedule-time]').forEach(i => i.classList.toggle('is-invalid', Boolean(text)));
    }
    function message(html, type) {
        feedback.className = 'alert border-0 rounded-3 alert-' + type;
        feedback.innerHTML = html;
    }
    const escape = t => String(t).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

    // ===== تنقية الإدخال أثناء الكتابة: يُحذف ما لا يقبله الحقل مع رسالة توضح السبب =====
    const sanitizers = {
        name: [new RegExp('[^' + AR + 'A-Za-z ]', 'g'), 'الاسم يقبل الحروف العربية أو الإنجليزية والمسافات فقط، دون أرقام أو رموز.'],
        employee_number: [/\D/g, 'رقم الموظف يقبل الأرقام فقط.'],
        login_phone: [/\D/g, 'هاتف تسجيل الدخول يقبل الأرقام فقط.'],
        residency_number: [/\D/g, 'رقم الإقامة يقبل الأرقام فقط.'],
        border_number: [/\D/g, 'رقم الحدود يقبل الأرقام فقط.'],
        passport_number: [/[^A-Z0-9]/g, 'رقم الجواز يقبل الحروف الإنجليزية والأرقام فقط.'],
        email: [/[^A-Za-z0-9._%+\-@]/g, 'البريد الإلكتروني يُكتب بحروف إنجليزية فقط، دون حروف عربية أو مسافات.'],
        phone: [/\D/g, 'رقم الجوال يقبل الأرقام فقط.'],
        phone2: [/\D/g, 'رقم الجوال يقبل الأرقام فقط.'],
    };
    function normalizePhone(v) {
        v = toLatinDigits(v).replace(/[\s()-]/g, '');
        const m = v.match(/^(?:\+|00)?966(5\d{8})$/);
        return m ? '0' + m[1] : v;
    }
    function sanitize(field) {
        const rule = sanitizers[field.name];
        if (!rule) return false;
        let v = toLatinDigits(field.value);
        if (field.name === 'phone' || field.name === 'phone2') v = normalizePhone(v);
        if (field.name === 'passport_number') v = v.toUpperCase();
        const cleaned = v.replace(rule[0], '');
        const limit = {employee_number: C.employeeNumberMax, residency_number: 10, border_number: 10, phone: 10, phone2: 10, passport_number: 12}[field.name];
        const next = limit ? cleaned.slice(0, limit) : cleaned;
        const stripped = cleaned !== v;
        if (next !== field.value) {
            const pos = Math.max(0, field.selectionStart - (field.value.length - next.length));
            field.value = next;
            try { field.setSelectionRange(pos, pos); } catch (e) { /* type=email لا يدعم التحديد */ }
        }
        if (stripped) setError(field.name, rule[1]);
        return stripped;
    }

    // ===== قواعد التحقق لكل حقل: تُرجع نص الخطأ أو '' =====
    const pairs = {residency_number: 'residency_expiry_date', residency_expiry_date: 'residency_number', passport_number: 'passport_expiry_date', passport_expiry_date: 'passport_number'};
    const rules = {
        name(v) {
            if (!v) return 'برجاء إدخال اسم السائق.';
            if (v.length < C.nameMin) return 'الاسم يجب ألا يقل عن ' + C.nameMin + ' أحرف.';
            if (v.length > C.nameMax) return 'الاسم يجب ألا يتجاوز ' + C.nameMax + ' حرفًا.';
            if (!new RegExp('^[' + AR + 'A-Za-z]+( [' + AR + 'A-Za-z]+)*$').test(v.replace(/\s+/g, ' '))) return 'الاسم يقبل الحروف العربية أو الإنجليزية والمسافات فقط، دون أرقام أو رموز.';
            return '';
        },
        employee_number(v) {
            if (!v) return 'برجاء إدخال رقم الموظف.';
            return /^\d+$/.test(v) ? '' : 'رقم الموظف يقبل الأرقام فقط.';
        },
        phone(v) {
            if (!v) return 'برجاء إدخال رقم الجوال.';
            return /^05\d{8}$/.test(v) ? '' : 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام (المُدخل ' + v.length + ').';
        },
        phone2(v) {
            if (!v) return '';
            if (!/^05\d{8}$/.test(v)) return 'رقم الجوال الثاني يجب أن يبدأ بـ 05 ويتكون من 10 أرقام (المُدخل ' + v.length + ').';
            return v === val('phone') ? 'رقم الجوال الثاني يجب أن يختلف عن رقم الجوال الأساسي.' : '';
        },
        address(v) { return v.length > C.addressMax ? 'العنوان يجب ألا يتجاوز ' + C.addressMax + ' حرفًا.' : ''; },
        email(v) {
            if (!v) return '';
            if (v.length > C.emailMax) return 'البريد الإلكتروني يجب ألا يتجاوز ' + C.emailMax + ' حرفًا.';
            return /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/.test(v) ? '' : 'البريد الإلكتروني غير صحيح؛ مثال: name@example.com';
        },
        login_phone(v) { return !v || /^\d{5,20}$/.test(v) ? '' : 'هاتف تسجيل الدخول من 5 إلى 20 رقمًا.'; },
        birth_date(v) {
            if (!v) return '';
            if (v > C.birthDateMax) return 'عمر السائق يجب ألا يقل عن ' + C.minAge + ' سنة (سن استخراج رخصة القيادة). أحدث تاريخ مقبول ' + fmt(C.birthDateMax) + '.';
            if (v < C.birthDateMin) return 'عمر السائق يجب ألا يتجاوز ' + C.maxAge + ' سنة.';
            return '';
        },
        residency_number(v) {
            if (!v) return val('residency_expiry_date') ? 'أدخل رقم الإقامة مع تاريخ انتهائها.' : '';
            return /^2\d{9}$/.test(v) ? '' : 'رقم الإقامة يتكون من 10 أرقام ويبدأ بالرقم 2 (المُدخل ' + v.length + ').';
        },
        residency_expiry_date(v) {
            if (!v) return val('residency_number') ? 'أدخل تاريخ انتهاء الإقامة مع رقمها.' : '';
            return v > C.expiryMax ? 'تاريخ انتهاء الإقامة بعيد جدًا؛ تأكد من السنة.' : '';
        },
        passport_number(v) {
            if (!v) return val('passport_expiry_date') ? 'أدخل رقم الجواز مع تاريخ انتهائه.' : '';
            return /^[A-Z0-9]{6,12}$/.test(v) ? '' : 'رقم الجواز من 6 إلى 12 حرفًا إنجليزيًا أو رقمًا.';
        },
        passport_expiry_date(v) {
            if (!v) return val('passport_number') ? 'أدخل تاريخ انتهاء الجواز مع رقمه.' : '';
            return v > C.expiryMax ? 'تاريخ انتهاء الجواز بعيد جدًا؛ تأكد من السنة.' : '';
        },
        border_number(v) { return !v || /^[34]\d{9}$/.test(v) ? '' : 'رقم الحدود يتكون من 10 أرقام ويبدأ بالرقم 3 أو 4 (المُدخل ' + v.length + ').'; },
        entry_date(v) {
            if (!v) return '';
            if (v > C.today) return 'تاريخ الدخول للمملكة لا يمكن أن يكون في المستقبل.';
            return val('birth_date') && v <= val('birth_date') ? 'تاريخ الدخول للمملكة يجب أن يكون بعد تاريخ الميلاد.' : '';
        },
        work_start_date(v) { return afterWorkAge(v, 'تاريخ بدء العمل'); },
        contract_start_date(v) { return afterWorkAge(v, 'تاريخ بداية العقد'); },
        contract_end_date(v) { return v && val('contract_start_date') && v < val('contract_start_date') ? 'تاريخ نهاية العقد لا يسبق تاريخ بدايته.' : ''; },
        vehicle_capacity(v) {
            if (!v) return 'برجاء إدخال سعة السيارة.';
            return /^\d+$/.test(v) && +v >= 3 && +v <= 50 ? '' : 'سعة السيارة رقم صحيح من 3 إلى 50.';
        },
        password(v) {
            const account = document.getElementById('account_enabled') /* الاسم مكرر مع الحقل المخفي */;
            if (!v) return account && account.checked && !HAS_PASSWORD ? 'برجاء إدخال كلمة المرور لتفعيل حساب السائق.' : '';
            if (v.length < C.passwordMin) return 'كلمة المرور يجب ألا تقل عن ' + C.passwordMin + ' أحرف (المُدخل ' + v.length + ').';
            return v.length > C.passwordMax ? 'كلمة المرور يجب ألا تتجاوز ' + C.passwordMax + ' حرفًا.' : '';
        },
        password_confirmation(v) {
            const p = el('password').value;
            if (!p && !v) return '';
            if (!v) return 'برجاء تأكيد كلمة المرور.';
            return v === p ? '' : 'تأكيد كلمة المرور غير مطابق لكلمة المرور.';
        },
    };
    // تاريخ الميلاد هو المرجع: لا يبدأ العمل أو العقد قبل بلوغ سن الرخصة
    function afterWorkAge(v, label) {
        const birth = val('birth_date');
        if (!v || !/^\d{4}-\d{2}-\d{2}$/.test(birth)) return '';
        const min = (parseInt(birth.slice(0, 4), 10) + C.minAge) + birth.slice(4);
        return v < min ? label + ' يجب ألا يسبق بلوغ السائق ' + C.minAge + ' سنة (من ' + fmt(min) + ').' : '';
    }
    // حقول يتغير صوابها بتغير حقل آخر
    const dependents = {phone: ['phone2'], birth_date: ['entry_date', 'work_start_date', 'contract_start_date'], contract_start_date: ['contract_end_date'], password: ['password_confirmation'], ...Object.fromEntries(Object.entries(pairs).map(([k, v]) => [k, [v]]))};

    function validate(name) {
        if (!rules[name] || !el(name)) return '';
        const error = rules[name](el(name).value.trim());
        setError(name, error);
        if (name === 'password_confirmation') {
            const confirm = el('password_confirmation');
            confirm.classList.toggle('is-valid', !error && confirm.value !== '');
        }
        return error;
    }
    function validateSchedule() {
        const errors = [];
        form.querySelectorAll('[data-schedule-row]').forEach(row => {
            const mode = (row.querySelector('[data-schedule-mode]:checked') || {}).value;
            const [start, end] = row.querySelectorAll('[data-schedule-time]');
            const day = start.name.match(/work_schedule\[(\w+)\]/)[1], label = row.querySelector('th').textContent.trim();
            let error = '';
            if (mode === 'hours') {
                if (!start.value || !end.value) error = 'حدد بداية ونهاية العمل يوم ' + label + '.';
                else if (end.value <= start.value) error = 'نهاية العمل يوم ' + label + ' يجب أن تكون بعد البداية.';
            }
            scheduleError(day, error);
            if (error) errors.push(error);
        });
        return errors;
    }

    // ===== ربط الأحداث =====
    Object.keys(rules).forEach(name => {
        const field = el(name);
        if (!field) return;
        field.addEventListener('input', () => {
            const stripped = sanitize(field);
            // تأكيد كلمة المرور يُفحص فور الكتابة؛ بقية الحقول بعد أول خروج منها أو إن كانت خاطئة
            if (!stripped && (name === 'password_confirmation' || touched.has(name) || field.classList.contains('is-invalid'))) validate(name);
            (dependents[name] || []).forEach(dep => { if (touched.has(dep) || el(dep).value) validate(dep); });
        });
        field.addEventListener('change', () => { if (field.type === 'date') { touched.add(name); validate(name); (dependents[name] || []).forEach(validate); } });
        field.addEventListener('blur', () => {
            if (field.value.trim() || touched.has(name)) { touched.add(name); validate(name); }
        });
    });
    form.querySelectorAll('[data-schedule-time], [data-schedule-mode]').forEach(i => i.addEventListener('change', validateSchedule));
    const account = document.getElementById('account_enabled') /* الاسم مكرر مع الحقل المخفي */;
    if (account) account.addEventListener('change', () => { if (touched.has('password')) validate('password'); });

    // معاينة كلمة المرور
    form.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.querySelector('i').className = 'fa ' + (show ? 'fa-eye-slash' : 'fa-eye');
        button.title = show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور';
    }));

    // ===== الحفظ =====
    const ALL = Object.keys(rules);
    function clearAll() {
        ALL.forEach(n => setError(n, ''));
        form.querySelectorAll('[data-schedule-error]').forEach(b => scheduleError(b.dataset.scheduleError, ''));
        form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
        document.querySelectorAll('[data-server-errors]').forEach(b => b.remove());
    }
    function showSummary(errors) {
        message('<div class="fw-bold mb-1"><i class="fa fa-circle-xmark ms-1"></i> تعذّر الحفظ، راجع الحقول التالية:</div><ul class="mb-0 ps-3">' + errors.map(e => '<li>' + escape(e) + '</li>').join('') + '</ul>', 'danger');
        const first = form.querySelector('.is-invalid');
        if (first) { first.scrollIntoView({behavior: 'smooth', block: 'center'}); first.focus({preventScroll: true}); }
        else feedback.scrollIntoView({behavior: 'smooth', block: 'start'});
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearAll();
        ALL.forEach(n => touched.add(n));
        const errors = ALL.map(validate).filter(Boolean).concat(validateSchedule());
        if (errors.length) { showSummary(errors); return; }

        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)});
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.redirect) { window.location.href = data.redirect; return; }
            if (response.status === 422 && data.errors) {
                Object.entries(data.errors).forEach(([key, list]) => {
                    const day = key.match(/^work_schedule\.(\w+)\./);
                    if (day) scheduleError(day[1], list[0]);
                    else setError(key, list[0]);
                });
                showSummary(Object.values(data.errors).map(list => list[0]));
            } else {
                message(escape(response.status === 419 ? 'انتهت الجلسة؛ أعد تحميل الصفحة ثم حاول مجددًا.' : (data.message && response.status < 500 ? data.message : 'تعذّر الحفظ. حاول مجددًا.')), 'danger');
                feedback.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        } catch (e) {
            message('تعذّر الاتصال بالخادم. حاول مجددًا.', 'danger');
        } finally { submit.disabled = false; }
    });
})();
</script>
