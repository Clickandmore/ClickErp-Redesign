<script>
(() => {
    const feedback = document.getElementById('lookup-feedback');
    const fields = {ar: document.getElementById('name_ar'), en: document.getElementById('name_en')};
    function message(text, error = false) { feedback.className = 'alert border-0 rounded-3 ' + (error ? 'alert-danger' : 'alert-info'); feedback.textContent = text; }
    async function post(url, payload) {
        const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify(payload)});
        const data = await response.json();
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'تعذر إكمال الطلب.');
        return data;
    }
    async function translate(target, button) {
        const source = target === 'ar' ? 'en' : 'ar';
        if (!fields[source].value.trim()) { message('اكتب اسم المدينة باللغة الأخرى أولًا.', true); return; }
        if (fields[target].value.trim()) { message('الحقل يحتوي اسمًا بالفعل؛ امسحه أولًا إذا أردت ترجمته.'); return; }
        const original = fields[source].value;
        button.disabled = true;
        try {
            const data = await post(@json(route('admin.hourly-cities.translate')), {text: original, source, target});
            if (!fields[target].value.trim() && fields[source].value === original) fields[target].value = data.text;
            message('تم جلب الترجمة. راجع الاسم قبل الحفظ.');
        } catch (e) { message(e.message, true); } finally { button.disabled = false; }
    }
    document.querySelectorAll('[data-translate]').forEach(button => button.addEventListener('click', () => translate(button.dataset.translate, button)));
    document.getElementById('translate-all').addEventListener('click', function () {
        if (!fields.ar.value.trim() && fields.en.value.trim()) translate('ar', this);
        else if (!fields.en.value.trim() && fields.ar.value.trim()) translate('en', this);
        else message('املأ أحد الاسمين واترك الآخر فارغًا للترجمة.');
    });
    document.getElementById('locate').addEventListener('click', async function () {
        const name = fields.en.value.trim() || fields.ar.value.trim();
        if (!name) { message('اكتب اسم المدينة أولًا.', true); return; }
        this.disabled = true;
        const results = document.getElementById('location-results');
        results.replaceChildren();
        try {
            const data = await post(@json(route('admin.hourly-cities.locate')), {name});
            if (!Array.isArray(data.results) || !data.results.length) throw new Error('لم يُعثر على موقع للمدينة. عدّل الاسم وأعد المحاولة.');
            data.results.forEach(result => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary text-end rounded-3';
                button.textContent = result.label;
                button.addEventListener('click', () => {
                    if ((fields.en.value.trim() || fields.ar.value.trim()) !== name) { message('تغير اسم المدينة؛ أعد جلب الموقع.', true); return; }
                    document.getElementById('latitude').value = result.latitude;
                    document.getElementById('longitude').value = result.longitude;
                    results.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    message('تم اختيار مركز المدينة.');
                });
                results.append(button);
            });
            if (data.results.length === 1) results.firstElementChild.click();
            else message('وجدنا أكثر من موقع؛ اضغط على النتيجة الصحيحة لتعبئة خط العرض وخط الطول.');
        } catch (e) { message(e.message, true); } finally { this.disabled = false; }
    });
})();
</script>
