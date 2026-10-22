// Each date loads only the dispatcher's available slots for this visit.
document.addEventListener('change', async event => {
    const date = event.target;
    if (!date.matches('[data-recovery-date]')) return;
    const form = date.closest('form');
    const times = form.querySelector('[data-recovery-times]');
    const feedback = form.querySelector('[data-recovery-slots-feedback]');
    const submit = form.querySelector('[type="submit"]');
    form.recoveryController?.abort();
    const controller = new AbortController();
    form.recoveryController = controller;
    times.replaceChildren(new Option('اختر الساعة', ''));
    times.disabled = true;
    submit.disabled = true;
    feedback.textContent = date.value ? 'جارٍ تحميل المواعيد المتاحة…' : 'اختر التاريخ أولًا';
    if (!date.value) return;
    try {
        const url = new URL(date.dataset.slotsUrl, location.href);
        url.searchParams.set('date', date.value);
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'تعذر تحميل المواعيد المتاحة. حاول مجددًا.');
        for (const slot of result.data) times.add(new Option(slot.label, slot.starts_at));
        times.disabled = !result.data.length;
        submit.disabled = !result.data.length;
        feedback.textContent = result.data.length ? 'اختر ساعة متاحة لإرسال طلبك إلى الإدارة.' : 'لا توجد مواعيد متاحة في هذا اليوم. اختر تاريخًا آخر.';
    } catch (error) {
        if (error.name !== 'AbortError') feedback.textContent = error.message;
    }
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!form.matches('[data-visit-recovery]')) return;
    event.preventDefault();
    if (form.dataset.busy === '1' || !form.reportValidity()) return;
    const button = event.submitter || form.querySelector('button[type="submit"],button:not([type]),input[type="submit"]');
    let feedback = form.querySelector('[data-recovery-feedback]');
    if (!feedback) {
        feedback = document.createElement('p');
        feedback.dataset.recoveryFeedback = '';
        feedback.setAttribute('role', 'status');
        form.append(feedback);
    }
    const originalLabel = button?.textContent;
    try {
        form.dataset.busy = '1';
        form.setAttribute('aria-busy', 'true');
        if (button) { button.disabled = true; button.textContent = 'جارٍ الإرسال…'; }
        feedback.textContent = 'جارٍ إرسال الطلب…';
        const response = await fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' }
        });
        const result = await response.json().catch(() => { throw new Error('تعذر قراءة رد الخادم. حدّث الصفحة ثم أعد المحاولة.'); });
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'تعذر إرسال الطلب.');
        feedback.textContent = result.message || 'تم إرسال الطلب بنجاح. جارٍ تحديث الزيارة…';
        window.location.reload();
    } catch (error) {
        feedback.textContent = error.message || 'تعذر الاتصال بالخادم. حاول مرة أخرى.';
    } finally {
        form.dataset.busy = '0';
        form.removeAttribute('aria-busy');
        if (button) { button.disabled = false; button.textContent = originalLabel; }
    }
});