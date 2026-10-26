/*
 * تصفية بدون إعادة تحميل الصفحة لأي قسم يحمل data-ajax-filter (النموذج والنتائج معًا).
 * - data-ajax-loader / data-ajax-status: معرّفا اللودر ورسالة الخطأ خارج القسم.
 * - النموذج [data-ajax-form]: لا تُرسل الحقول الفارغة ولا الحقول داخل حاوية مخفية.
 * - [data-auto-apply] على حقل: يطبّق التصفية فور تغييره (والاختيار المتعدد عند إغلاق لوحته).
 * - الروابط [data-ajax-link] وروابط .pagination و.hc-pager داخل القسم: تُجلب بالطريقة نفسها.
 * بعد كل تحديث يُطلق الحدث hx-filter:updated على document مع القسم الجديد في detail.root.
 */
(function () {
    'use strict';
    const roots = document.querySelectorAll('[data-ajax-filter]');
    roots.forEach(initial => {
        const id = initial.id;
        const byId = key => document.getElementById(key);
        const current = () => byId(id);
        const loader = byId(initial.dataset.ajaxLoader), status = byId(initial.dataset.ajaxStatus);
        let version = 0, pending;

        const setLoading = loading => {
            // يتوسط اللودر منطقة النتائج أفقيًا، فلا يزيحه عرض القائمة الجانبية عن منتصف المحتوى.
            const area = loading && loader?.style && current().getBoundingClientRect?.();
            if (area) loader.style.left = (area.left + area.width / 2) + 'px';
            if (loader) loader.hidden = !loading;
            current().setAttribute('aria-busy', String(loading));
        };
        const fail = text => {
            if (!status) return;
            status.textContent = text; status.hidden = !text;
            status.className = 'alert ' + (text ? 'alert-danger' : '');
        };
        const query = form => {
            const params = new URLSearchParams();
            [...form.elements].forEach(el => {
                if (!el.name || el.disabled || el.closest('[hidden]')) return;
                if ((el.type === 'radio' || el.type === 'checkbox') && !el.checked) return;
                const values = el.multiple ? [...el.selectedOptions].map(o => o.value) : [el.value];
                values.filter(value => value !== '').forEach(value => params.append(el.name, value));
            });
            return params;
        };

        async function load(target, push = true) {
            const url = new URL(target, window.location.href);
            if (url.origin !== window.location.origin) return;
            const run = ++version, position = {top: window.scrollY, left: window.scrollX};
            pending?.abort(); pending = new AbortController();
            setLoading(true); fail('');
            try {
                const response = await fetch(url.href, {credentials: 'same-origin', signal: pending.signal, headers: {Accept: 'text/html'}});
                if (run !== version) return;
                if (!response.ok) throw new Error('تعذر تطبيق الفلاتر. راجع القيم وحاول مجددًا.');
                const html = await response.text();
                if (run !== version) return;
                const next = new DOMParser().parseFromString(html, 'text/html').getElementById(id);
                if (!next) throw new Error('تعذر تحديث النتائج. قد تكون الجلسة انتهت؛ أعد تحميل الصفحة وسجل الدخول.');
                current().replaceWith(next);
                if (push) window.history.pushState({hxFilter: id}, '', url.href);
                window.scrollTo(position);
                document.dispatchEvent(new CustomEvent('hx-filter:updated', {detail: {root: next}}));
            } catch (failure) {
                if (run === version && failure.name !== 'AbortError') fail(failure.message || 'تعذر تحديث النتائج. حاول مجددًا.');
            } finally {
                if (run === version) setLoading(false);
            }
        }

        const submit = form => {
            const url = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            url.search = query(form).toString();
            load(url.href);
        };
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!form.matches?.('[data-ajax-form]') || !current()?.contains(form)) return;
            event.preventDefault(); submit(form);
        });
        // الاختيار المتعدد (HourlyMultiSelect المضغوط) يُطبَّق عند إغلاق لوحته عبر hms:commit لا مع كل مربع اختيار.
        const autoApply = event => {
            const field = event.target;
            if (!field.matches?.('[data-auto-apply]') || !current()?.contains(field)) return;
            if (event.type === 'change' && field.multiple) return;
            submit(field.form);
        };
        document.addEventListener('change', autoApply);
        document.addEventListener('hms:commit', autoApply);
        document.addEventListener('click', event => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest?.('a[data-ajax-link], .pagination a[href], .hc-pager a[href]');
            if (!link || !current()?.contains(link)) return;
            event.preventDefault(); load(link.href);
        });
        window.addEventListener('popstate', () => load(window.location.href, false));
    });
})();
