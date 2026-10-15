<script>
(() => {
    function initCitySearch() {
        if (!window.jQuery || !jQuery.fn.select2) return;
        document.querySelectorAll('select[data-city-search]').forEach(city => {
            const select = jQuery(city), modal = city.closest('.modal');
            if (select.hasClass('select2-hidden-accessible')) return;
            select.select2({
                width: '100%', dir: 'rtl', minimumResultsForSearch: 0,
                placeholder: 'اختر المدينة أو ابحث باسمها',
                dropdownParent: modal ? jQuery(modal) : jQuery(document.body),
                language: { noResults: () => 'لا توجد مدن مطابقة' }
            });
            // The shared modal fills native select values when switching between add and edit.
            if (modal) modal.addEventListener('shown.bs.modal', () => select.trigger('change.select2'));
            city.addEventListener('invalid', () => select.select2('open'));
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCitySearch);
    else initCitySearch();
    const fields = {en: document.getElementById('name_en'), ar: document.getElementById('name')};
    const feedback = document.getElementById('lookup-feedback');
    function message(text, error = false) { feedback.className = 'alert border-0 rounded-3 ' + (error ? 'alert-danger' : 'alert-info'); feedback.textContent = text; }
    async function post(url, data) {
        const r = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify(data)});
        const body = await r.json();
        if (!r.ok) throw new Error(body.errors ? Object.values(body.errors).flat()[0] : 'تعذر تنفيذ الطلب.');
        return body;
    }
    async function translate(target, button) {
        const source = target === 'ar' ? 'en' : 'ar';
        if (!fields[source].value.trim() || fields[target].value.trim()) { message('املأ الاسم باللغة الأخرى واترك حقل الترجمة فارغًا.'); return; }
        const original = fields[source].value;
        button.disabled = true;
        try {
            const data = await post(@json(route('admin.hourly-cities.translate')), {text: original, source, target});
            if (!fields[target].value.trim() && fields[source].value === original) fields[target].value = data.text;
            message('راجع الترجمة قبل حفظ المنطقة.');
        } catch (e) { message(e.message, true); } finally { button.disabled = false; }
    }
    document.querySelectorAll('[data-translate]').forEach(b => b.addEventListener('click', () => translate(b.dataset.translate, b)));
    document.getElementById('translate-all').addEventListener('click', function () { translate(fields.en.value.trim() ? 'ar' : 'en', this); });
    document.getElementById('locate').addEventListener('click', async function () {
        const city = document.getElementById('city_id').value, name = fields.ar.value.trim() || fields.en.value.trim();
        if (!city || !name) { message('اختر المدينة واكتب اسم المنطقة أولًا.', true); return; }
        this.disabled = true;
        const results = document.getElementById('location-results');
        results.replaceChildren();
        try {
            const data = await post(@json(route('admin.hourly-districts.locate')), {city_id: city, name});
            if (!Array.isArray(data.results) || !data.results.length) throw new Error('لم يُعثر على موقع للمنطقة. عدّل الاسم وأعد المحاولة.');
            data.results.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary text-end rounded-3';
                button.textContent = item.label;
                button.addEventListener('click', () => {
                    if (document.getElementById('city_id').value !== city || (fields.ar.value.trim() || fields.en.value.trim()) !== name) { message('تغيرت المدينة أو المنطقة؛ أعد البحث.', true); return; }
                    document.getElementById('latitude').value = item.latitude;
                    document.getElementById('longitude').value = item.longitude;
                    results.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                    button.classList.add('active');
                    message('تم اختيار الموقع.');
                });
                results.append(button);
            });
            if (data.results.length === 1) results.firstElementChild.click();
            else message('وجدنا أكثر من موقع؛ اضغط على النتيجة الصحيحة لتعبئة خط العرض وخط الطول.');
        } catch (e) { message(e.message, true); } finally { this.disabled = false; }
    });
})();
</script>
