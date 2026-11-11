<script>
// يربط سلوك نموذج مشاركة السير (بحث العميل + الإرسال مع عرض الأخطاء) بأي نسخة من النموذج.
window.initMaidCvShareForm = window.initMaidCvShareForm || function (form) {
    const $ = selector => form.querySelector(selector);
    const client = $('.cv-client'), phone = $('.cv-phone'), search = $('.cv-client-search');
    const status = $('.cv-search-status'), button = $('.cv-client-search-button');
    const errors = $('.cv-share-errors'), submit = $('.cv-share-submit');
    const submitLabel = submit.innerHTML;
    let results = [];

    const showErrors = messages => {
        errors.replaceChildren(...messages.map(text => Object.assign(document.createElement('div'), {textContent: text})));
        errors.classList.toggle('d-none', messages.length === 0);
        if (messages.length) errors.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    };

    button.addEventListener('click', async () => {
        if (search.value.trim().length < 2) { status.textContent = 'اكتب حرفين على الأقل.'; return; }
        button.disabled = true;
        status.textContent = 'جارٍ البحث…';
        client.replaceChildren(new Option('اختر العميل', ''));
        phone.replaceChildren();
        try {
            const response = await fetch(@json(route('maid-cv-shares.clients')) + '?q=' + encodeURIComponent(search.value.trim()),
                {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error();
            results = await response.json();
            results.forEach(row => client.add(new Option(row.name + ' — ' + row.phones.join(' / '), row.id)));
            status.textContent = results.length ? 'اختر العميل ثم رقم واتساب.' : 'لا توجد نتائج.';
            if (results.length === 1) { client.value = String(results[0].id); client.dispatchEvent(new Event('change')); }
        } catch (_) {
            status.textContent = 'تعذر البحث عن العملاء.';
        } finally {
            button.disabled = false;
        }
    });

    search.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); button.click(); }
    });

    // بحث تلقائي أثناء الكتابة
    let timer;
    search.addEventListener('input', () => {
        clearTimeout(timer);
        if (search.value.trim().length >= 2) timer = setTimeout(() => button.click(), 450);
    });

    client.addEventListener('change', () => {
        phone.replaceChildren();
        (results.find(row => String(row.id) === client.value)?.phones || []).forEach(value => phone.add(new Option(value, value)));
    });

    form.querySelectorAll('.cv-share-maid').forEach(box => box.addEventListener('change', () => {
        $('.cv-share-count').textContent = form.querySelectorAll('.cv-share-maid:checked').length;
    }));

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const problems = [];
        if (!client.value) problems.push('اختر العميل.');
        if (!phone.value) problems.push('اختر رقم واتساب.');
        if (!form.querySelector('.cv-share-maid:checked')) problems.push('حدد سيرة واحدة على الأقل.');
        showErrors(problems);
        if (problems.length) return;

        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جارٍ تجهيز الملفات…';
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form),
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const data = await response.json().catch(() => ({}));
            if (response.ok && data.redirect) { window.location.href = data.redirect; return; }
            showErrors(data.errors ? Object.values(data.errors).flat() : [data.message || 'تعذر تجهيز المشاركة.']);
        } catch (_) {
            showErrors(['تعذر الاتصال بالخادم.']);
        }
        submit.disabled = false;
        submit.innerHTML = submitLabel;
    });
};
</script>
