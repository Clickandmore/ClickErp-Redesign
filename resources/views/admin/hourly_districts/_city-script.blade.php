<script>
(() => {
    const feedback = document.getElementById('lookup-feedback');
    const fields = {ar: document.getElementById('name_ar'), en: document.getElementById('name_en')};
    const lat = document.getElementById('latitude'), lng = document.getElementById('longitude'), sort = document.getElementById('sort_order');
    const results = document.getElementById('location-results');
    const form = fields.ar.form;
    // نفس القيم في HourlyCityController
    const NAME_MAX = 50, LAT = [16, 33], LNG = [34, 56], SORT_MAX = 9999;
    const LABELS = {ar: 'بالعربية', en: 'بالإنجليزية'};
    // pair: آخر اسمين متطابقين (بعد ترجمة أو عند فتح سجل محفوظ). locatedFor: الاسم الذي جُلبت له الإحداثيات.
    const state = {pair: null, locatedFor: null};

    function message(text, type = 'info') {
        const icon = {info: 'fa-circle-info', success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation'}[type];
        feedback.className = 'alert border-0 rounded-3 alert-' + (type === 'error' ? 'danger' : type);
        feedback.replaceChildren(Object.assign(document.createElement('i'), {className: 'fa ' + icon + ' mt-1'}), document.createTextNode(text));
    }
    function hideMessage() { feedback.className = 'alert border-0 rounded-3 d-none'; }

    function setError(field, text) {
        const box = form.querySelector('[data-error-for="' + field.name + '"]');
        field.classList.toggle('is-invalid', Boolean(text));
        if (box) box.textContent = text || '';
    }
    function clearErrors() {
        [fields.ar, fields.en, lat, lng, sort].forEach(f => setError(f, ''));
        document.querySelectorAll('[data-modal-errors]').forEach(el => el.remove());
    }

    const clean = value => value.replace(/[’‘]/g, "'").replace(/\s+/g, ' ').trim();
    const validators = {
        name_ar(v) {
            v = clean(v);
            if (!v) return 'برجاء إدخال اسم المدينة بالعربية.';
            if (v.length > NAME_MAX) return 'اسم المدينة بالعربية يجب ألا يتجاوز ' + NAME_MAX + ' حرفًا.';
            if (!/^[ء-غف-ي]+( [ء-غف-ي]+)*$/.test(v)) return 'اسم المدينة بالعربية يقبل الحروف العربية والمسافات فقط.';
            return '';
        },
        name_en(v) {
            v = clean(v);
            if (!v) return 'برجاء إدخال اسم المدينة بالإنجليزية.';
            if (v.length > NAME_MAX) return 'اسم المدينة بالإنجليزية يجب ألا يتجاوز ' + NAME_MAX + ' حرفًا.';
            if (!/^[A-Za-z]+([ '-][A-Za-z]+)*$/.test(v)) return "اسم المدينة بالإنجليزية يقبل الحروف الإنجليزية والمسافات فقط (ويُسمح بـ - أو ' بين الكلمات).";
            return '';
        },
        latitude(v) { return coordinate(v, lng.value, 'خط العرض', 'خط الطول', LAT); },
        longitude(v) { return coordinate(v, lat.value, 'خط الطول', 'خط العرض', LNG); },
        sort_order(v) {
            v = v.trim();
            if (v === '') return 'برجاء إدخال ترتيب العرض.';
            if (!/^\d+$/.test(v) || Number(v) > SORT_MAX) return 'ترتيب العرض يجب أن يكون رقمًا صحيحًا بين 0 و ' + SORT_MAX + '.';
            return '';
        },
    };
    function coordinate(value, other, label, otherLabel, [min, max]) {
        value = value.trim();
        if (value === '') return other.trim() !== '' ? 'أدخل ' + label + ' مع ' + otherLabel + '، أو اتركهما فارغين.' : '';
        if (!/^\d{1,2}(\.\d+)?$/.test(value)) return label + ' يجب أن يكون رقمًا موجبًا مثل ' + (min + 8) + '.123456';
        const n = Number(value);
        if (n < min || n > max) return label + ' يجب أن يكون داخل المملكة (بين ' + min + ' و ' + max + ').';
        return '';
    }
    const validate = field => { const error = validators[field.name](field.value); setError(field, error); return error; };

    function updateCounter(field) {
        const counter = form.querySelector('[data-counter-for="' + field.name + '"]');
        if (!counter) return;
        counter.textContent = field.value.length + ' / ' + NAME_MAX;
        counter.classList.toggle('is-full', field.value.length >= NAME_MAX);
    }
    function snapshot() {
        const ar = clean(fields.ar.value), en = clean(fields.en.value);
        state.pair = ar && en ? {ar, en} : null;
        state.locatedFor = null;
        Object.values(fields).forEach(updateCounter);
    }

    // إدخال الأرقام: خط العرض/الطول أرقام موجبة وفاصلة عشرية واحدة، والترتيب أرقام صحيحة حتى 4 خانات
    function sanitizeDecimal(field) {
        let v = field.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[٫,]/g, '.').replace(/[^\d.]/g, '');
        const [whole, ...rest] = v.split('.');
        v = whole.slice(0, 2) + (rest.length ? '.' + rest.join('').slice(0, 8) : '');
        if (v !== field.value) field.value = v;
    }
    [lat, lng].forEach(field => field.addEventListener('input', () => { sanitizeDecimal(field); state.locatedFor = null; if (field.classList.contains('is-invalid')) { validate(lat); validate(lng); } }));
    sort.addEventListener('input', () => {
        const v = sort.value.replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g, '').slice(0, 4);
        if (v !== sort.value) sort.value = v;
        if (sort.classList.contains('is-invalid')) validate(sort);
    });
    [lat, lng, sort].forEach(field => field.addEventListener('blur', () => validate(field)));

    // كل حقل اسم لا يقبل إلا حروف لغته: تُحذف الحروف الأخرى فور كتابتها أو لصقها
    const ALLOWED = {name_ar: /[^ء-غف-ي ]/g, name_en: /[^A-Za-z '’‘-]/g};
    const REJECTED = {name_ar: 'اسم المدينة بالعربية يقبل الحروف العربية والمسافات فقط، ولا يقبل حروفًا إنجليزية أو أرقامًا أو رموزًا.', name_en: "اسم المدينة بالإنجليزية يقبل الحروف الإنجليزية والمسافات فقط (و - أو ')، ولا يقبل حروفًا عربية أو أرقامًا."};
    function sanitizeName(field) {
        const value = field.value.replace(ALLOWED[field.name], '');
        if (value === field.value) return false;
        const caret = field.selectionStart - (field.value.length - value.length);
        field.value = value;
        field.setSelectionRange(Math.max(caret, 0), Math.max(caret, 0));
        setError(field, REJECTED[field.name]);
        return true;
    }

    Object.values(fields).forEach(field => {
        field.addEventListener('input', event => {
            const stripped = !event.isComposing && sanitizeName(field);
            updateCounter(field);
            if (!stripped && field.classList.contains('is-invalid')) validate(field);
            // الإحداثيات المجلوبة تخص الاسم القديم؛ لا نتركها معلقة على اسم جديد
            if (state.locatedFor !== null && currentLocateName() !== state.locatedFor) {
                lat.value = ''; lng.value = ''; results.replaceChildren(); state.locatedFor = null;
                message('تغيّر اسم المدينة فتم مسح الإحداثيات السابقة. اضغط «جلب الموقع من الاسم» مرة أخرى.', 'warning');
            }
        });
        field.addEventListener('blur', () => { if (field.value.trim()) validate(field); });
    });

    async function post(url, payload) {
        const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify(payload)});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'تعذر إكمال الطلب.');
        return data;
    }

    // زر الترجمة بجانب الحقل يملؤه من الاسم المكتوب في الحقل الآخر (ويستبدل ما فيه)
    async function translate(target, button) {
        const source = target === 'ar' ? 'en' : 'ar';
        const original = clean(fields[source].value);
        if (!original) {
            message('برجاء إدخال اسم المدينة ' + LABELS[source] + ' أولًا، ثم اضغط «ترجمة».', 'error');
            setError(fields[source], 'برجاء إدخال اسم المدينة ' + LABELS[source] + '.');
            fields[source].focus();
            return;
        }
        const sourceError = validate(fields[source]);
        if (sourceError) { message(sourceError, 'error'); fields[source].focus(); return; }
        button.disabled = true;
        try {
            const data = await post(@json(route('admin.hourly-cities.translate')), {text: original, source, target});
            if (clean(fields[source].value) !== original) return;
            fields[target].value = clean(String(data.text || '')).slice(0, NAME_MAX);
            fields[target].dispatchEvent(new Event('input'));
            state.pair = {ar: clean(fields.ar.value), en: clean(fields.en.value)};
            const error = validate(fields[target]);
            if (error) message('تم جلب الترجمة لكنها تحتاج تعديلًا: ' + error, 'warning');
            else message('تم جلب الترجمة. راجع الاسم قبل الحفظ.', 'success');
        } catch (e) { message(e.message, 'error'); } finally { button.disabled = false; }
    }
    document.querySelectorAll('[data-translate]').forEach(button => button.addEventListener('click', () => translate(button.dataset.translate, button)));

    const currentLocateName = () => clean(fields.en.value) || clean(fields.ar.value);

    document.getElementById('locate').addEventListener('click', async function () {
        const ar = clean(fields.ar.value), en = clean(fields.en.value);
        if (!ar && !en) { message('برجاء إدخال اسم المدينة أولًا.', 'error'); setError(fields.ar, 'برجاء إدخال اسم المدينة بالعربية.'); fields.ar.focus(); return; }
        // تعديل أحد الاسمين بعد الترجمة مع ترك الآخر كما هو = الاسمان لم يعودا لنفس المدينة
        if (state.pair && (ar !== state.pair.ar) !== (en !== state.pair.en)) {
            const changed = ar !== state.pair.ar ? 'ar' : 'en', stale = changed === 'ar' ? 'en' : 'ar';
            const text = 'تم تعديل الاسم ' + LABELS[changed] + ' ولم يُعدَّل الاسم ' + LABELS[stale] + '. عدّله أو اضغط «ترجمة» بجانبه قبل جلب الموقع.';
            message(text, 'error'); setError(fields[stale], 'هذا الاسم لا يطابق الاسم ' + LABELS[changed] + ' بعد تعديله.'); fields[stale].focus();
            return;
        }
        const name = currentLocateName();
        this.disabled = true;
        results.replaceChildren();
        message('جارٍ البحث عن موقع «' + name + '»...');
        try {
            const data = await post(@json(route('admin.hourly-cities.locate')), {name});
            if (!Array.isArray(data.results) || !data.results.length) throw new Error('لم يُعثر على موقع للمدينة «' + name + '». تأكد من الاسم وأعد المحاولة، أو أدخل الإحداثيات يدويًا.');
            data.results.forEach(result => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary text-end rounded-3';
                button.textContent = result.label;
                button.addEventListener('click', () => {
                    if (currentLocateName() !== name) { message('تغير اسم المدينة؛ أعد جلب الموقع.', 'error'); return; }
                    lat.value = Number(result.latitude).toFixed(6);
                    lng.value = Number(result.longitude).toFixed(6);
                    state.locatedFor = name;
                    state.pair = ar && en ? {ar, en} : state.pair;
                    const error = validate(lat) || validate(lng);
                    results.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    message(error ? error : 'تم اختيار مركز المدينة.', error ? 'error' : 'success');
                });
                results.append(button);
            });
            if (data.results.length === 1) results.firstElementChild.click();
            else message('وجدنا أكثر من موقع؛ اضغط على النتيجة الصحيحة لتعبئة خط العرض وخط الطول.');
        } catch (e) {
            // لا نُبقي إحداثيات جُلبت لاسم سابق
            if (state.locatedFor !== null) { lat.value = ''; lng.value = ''; state.locatedFor = null; }
            message(e.message, 'error');
        } finally { this.disabled = false; }
    });

    function resetForm() {
        fields.ar.value = ''; fields.en.value = ''; lat.value = ''; lng.value = ''; sort.value = '1';
        const active = document.getElementById('is_active'); if (active) active.checked = true;
        results.replaceChildren(); clearErrors(); hideMessage(); snapshot();
        fields.ar.focus();
    }
    form.querySelectorAll('[data-city-reset]').forEach(button => button.addEventListener('click', resetForm));

    // الحفظ: تحقق فوري ثم إرسال عبر fetch وعرض أخطاء الخادم (مثل الاسم المكرر) دون إعادة تحميل الصفحة
    form.noValidate = true;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        fields.ar.value = clean(fields.ar.value); fields.en.value = clean(fields.en.value);
        clearErrors();
        const errors = [fields.ar, fields.en, lat, lng, sort].map(validate).filter(Boolean);
        if (errors.length) { message('تعذّر الحفظ: ' + errors[0], 'error'); form.querySelector('.is-invalid')?.focus(); return; }
        const submit = form.querySelector('[type="submit"]');
        if (submit) submit.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, body: new FormData(form)});
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.redirect) { window.location.href = data.redirect; return; }
            if (response.status === 422 && data.errors) {
                Object.entries(data.errors).forEach(([name, list]) => { const field = form.elements[name]; if (field && field.name) setError(field, list[0]); });
                message('تعذّر الحفظ: ' + Object.values(data.errors).flat()[0], 'error');
                form.querySelector('.is-invalid')?.focus();
            } else {
                message(data.message && response.status !== 500 ? data.message : 'تعذّر الحفظ. حاول مجددًا.', 'error');
            }
        } catch (e) { message('تعذّر الاتصال بالخادم. حاول مجددًا.', 'error'); } finally { if (submit) submit.disabled = false; }
    });

    // في المودال تُملأ القيم عند الفتح؛ نأخذ لقطة بعد ذلك
    const modal = form.closest('.modal');
    if (modal) modal.addEventListener('shown.bs.modal', () => { snapshot(); if (!modal.querySelector('.is-invalid')) hideMessage(); });
    snapshot();
})();
</script>
