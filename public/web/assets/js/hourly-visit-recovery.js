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