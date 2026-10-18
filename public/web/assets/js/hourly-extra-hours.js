(function () {
    'use strict';
    const byId = id => document.getElementById(id);
    async function request(url, csrf, body) {
        const options = {credentials: 'same-origin', headers: {Accept: 'application/json'}};
        if (body !== undefined) {
            options.method = 'POST';
            options.headers['X-CSRF-TOKEN'] = csrf;
            if (body instanceof FormData) options.body = body;
            else { options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(body); }
        }
        const response = await fetch(url, options);
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 401 || response.status === 419) {
                throw new Error('انتهت جلسة الدخول. أعيدي تحميل الصفحة وسجلي الدخول للمتابعة.');
            }
            const failure = new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'تعذر إتمام الطلب. حاولي مجددًا.');
            failure.status = response.status;
            failure.errors = result.errors || {};
            failure.duplicate = result.duplicate;
            throw failure;
        }
        return result;
    }
    function extraHours(config) {
        const dialog = byId('hr-extra-dialog');
        if (!dialog) return;
        const hours = byId('hr-extra-hours'), summary = byId('hr-extra-summary');
        const error = byId('hr-extra-error'), confirm = byId('hr-extra-confirm');
        let visit, token, quote, version = 0, busy = false;
        const url = action => config.urls[action].replace('__VISIT__', visit);
        const showError = message => { error.textContent = message; error.hidden = !message; };
        async function preview() {
            const run = ++version;
            quote = null; confirm.disabled = true; showError('');
            summary.textContent = 'جارٍ التحقق من التوفر والتكلفة…';
            try {
                const response = await request(url('extraHourQuote') + '?hours=' + hours.value);
                if (run !== version) return;
                quote = response.data;
                summary.textContent = 'التكلفة الإضافية شاملة الضريبة: ' + Number(quote.amount).toFixed(2)
                    + ' ر.س · موعد الانتهاء الجديد: ' + quote.ends_at + ' · إجمالي المدة: ' + quote.planned_hours + ' ساعة';
                confirm.disabled = false;
            } catch (failure) {
                if (run !== version) return;
                summary.textContent = ''; showError(failure.message);
            }
        }
        hours.addEventListener('change', () => { if (!busy) preview(); });
        document.querySelectorAll('[data-extra-hour]').forEach(button => button.addEventListener('click', () => {
            if (busy) return;
            visit = button.dataset.extraHour;
            token = window.crypto.randomUUID();
            hours.value = '1';
            byId('hr-extra-visit').textContent = 'الزيارة رقم ' + button.dataset.visitNumber;
            dialog.showModal(); preview();
        }));
        dialog.addEventListener('close', () => { ++version; quote = null; });
        confirm.addEventListener('click', async () => {
            if (!quote || busy) return;
            busy = true; confirm.disabled = true; hours.disabled = true; showError('');
            try {
                await request(url('extraHour'), config.csrf, {hours: quote.hours, request_token: token});
                window.location.reload();
            } catch (failure) { showError(failure.message); confirm.disabled = false; }
            finally { busy = false; hours.disabled = false; }
        });
    }
    const config = byId('hourly-extra-config');
    if (config) extraHours(JSON.parse(config.textContent));
})();
