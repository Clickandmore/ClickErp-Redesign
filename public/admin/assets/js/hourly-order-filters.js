(function () {
    'use strict';
    const byId = id => document.getElementById(id);
    if (!byId('hourly-order-results')) return;
    const status = byId('hourly-order-filter-status');
    const loader = byId('hourly-order-filter-loader');
    const setLoading = loading => {
        // يتوسط اللودر منطقة النتائج أفقيًا، فلا يزيحه عرض القائمة الجانبية عن منتصف المحتوى.
        const area = loading && loader?.style && byId('hourly-order-results').getBoundingClientRect?.();
        if (area) loader.style.left = (area.left + area.width / 2) + 'px';
        if (loader) loader.hidden = !loading;
        byId('hourly-order-results').setAttribute('aria-busy', String(loading));
    };
    let version = 0, pending;
    const message = (text, failed = false) => {
        status.textContent = text; status.hidden = !text;
        status.className = 'alert ' + (failed ? 'alert-danger' : 'alert-info');
    };
    async function load(target, push = true) {
        const url = new URL(target, window.location.href);
        if (url.origin !== window.location.origin) return;
        const run = ++version, position = {top:window.scrollY, left:window.scrollX};
        pending?.abort(); pending = new AbortController();
        // اللودر نفسه يعلن التحميل؛ رسالة الحالة تُستخدم للأخطاء فقط.
        setLoading(true);
        message('');
        try {
            const response = await fetch(url.href, {credentials:'same-origin', signal:pending.signal, headers:{Accept:'text/html'}});
            if (run !== version) return;
            if (!response.ok) throw new Error('تعذر تطبيق الفلاتر. راجع القيم وحاول مجددًا.');
            const html = await response.text();
            if (run !== version) return;
            const page = new DOMParser().parseFromString(html, 'text/html');
            const results = page.getElementById('hourly-order-results'), summary = page.getElementById('hourly-order-summary');
            if (!results || !summary) throw new Error('تعذر تحديث النتائج. قد تكون الجلسة انتهت؛ أعد تحميل الصفحة وسجل الدخول.');
            byId('hourly-order-results').replaceWith(results);
            byId('hourly-order-summary').replaceWith(summary);
            if (push) window.history.pushState({hourlyOrders:true}, '', url.href);
            window.scrollTo(position);
        } catch (failure) {
            if (run === version && failure.name !== 'AbortError') message(failure.message || 'تعذر تحديث النتائج. حاول مجددًا.', true);
        } finally {
            if (run === version) setLoading(false);
        }
    }
    document.addEventListener('submit', event => {
        if (event.target.id !== 'hourly-order-filter') return;
        event.preventDefault();
        const url = new URL(event.target.action, window.location.href);
        url.search = new URLSearchParams(new FormData(event.target)).toString();
        url.searchParams.delete('page');
        load(url.href);
    });
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest('#hourly-order-results [data-order-reset], #hourly-order-results .ht-foot a[href]');
        if (!link) return;
        event.preventDefault(); load(link.href);
    });
    window.addEventListener('popstate', () => load(window.location.href, false));
})();
