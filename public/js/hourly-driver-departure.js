(() => {
    const openedAt = performance.now();
    const message = 'انتهى موعد الزيارة؛ لا يمكن بدء التوصيل. راجع الإدارة لإعادة الجدولة.';
    document.querySelectorAll('form[data-delivery-start]').forEach(form => {
        const expiresAfter = Number(form.dataset.remainingMs);
        if (!Number.isFinite(expiresAfter)) return;
        const expired = () => performance.now() - openedAt >= expiresAfter;
        const update = () => {
            if (!expired()) return;
            form.querySelectorAll('button').forEach(button => { button.disabled = true; });
            if (!form.querySelector('[data-departure-expired]')) {
                const alert = document.createElement('p');
                alert.className = 'error';
                alert.dataset.departureExpired = '';
                alert.setAttribute('role', 'alert');
                alert.textContent = message;
                form.prepend(alert);
            }
        };
        form.addEventListener('submit', event => {
            if (expired()) { event.preventDefault(); update(); }
        });
        document.addEventListener('visibilitychange', update);
        window.setTimeout(update, Math.min(Math.max(0, expiresAfter), 2147483647));
        update();
    });
})();