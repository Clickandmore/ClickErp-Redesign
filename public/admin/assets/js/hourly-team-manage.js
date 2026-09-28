(() => {
    'use strict';
    let busy = false;
    function notice(message, ok) {
        let box = document.getElementById('tm-toast');
        if (!box) { box = document.createElement('div'); box.id = 'tm-toast'; document.body.append(box); }
        box.setAttribute('role', ok ? 'status' : 'alert');
        box.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger');
        box.textContent = message;
    }
    async function save(url, body, pane, source) {
        if (busy) return;
        busy = true;
        const buttons = [...document.querySelectorAll('.ht-page button')].filter(b => !b.disabled);
        buttons.forEach(b => b.disabled = true);
        source.setAttribute('aria-busy', 'true');
        let saved = false;
        try {
            const response = await fetch(url, {method:'POST', credentials:'same-origin', headers:{Accept:'application/json'}, body});
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.message) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message || 'تعذر الحفظ. تحقق من الاتصال وصلاحية الجلسة.');
            saved = true;
            const refreshed = await fetch(location.pathname, {credentials:'same-origin', cache:'no-store', headers:{Accept:'text/html'}});
            if (!refreshed.ok || refreshed.redirected) throw new Error('refresh');
            const doc = new DOMParser().parseFromString(await refreshed.text(), 'text/html');
            if (!doc.querySelector('.ht-page .hd-stats') || !doc.getElementById(pane)) throw new Error('refresh');
            // Keep the live map and other tabs' unsaved inputs intact.
            if (pane !== 'location') document.getElementById(pane).innerHTML = doc.getElementById(pane).innerHTML;
            document.querySelector('.ht-page .hd-stats').innerHTML = doc.querySelector('.ht-page .hd-stats').innerHTML;
            document.querySelectorAll('.ht-tabs [data-bs-target]').forEach(tab => {
                const fresh = doc.querySelector('.ht-tabs [data-bs-target="' + tab.dataset.bsTarget + '"] .badge');
                if (fresh) tab.querySelector('.badge').textContent = fresh.textContent;
            });
            document.getElementById('team-district-data').textContent = doc.getElementById('team-district-data').textContent;
            notice(result.message, true);
        } catch (error) {
            notice(saved ? 'تم الحفظ، لكن تعذر تحديث العرض. حدّث الصفحة لعرض النتيجة دون إعادة إرسال الحفظ.' : error.message, false);
        } finally {
            buttons.forEach(b => b.disabled = false);
            source.removeAttribute('aria-busy');
            busy = false;
        }
    }
    document.addEventListener('submit', event => {
        const form = event.target;
        if (event.defaultPrevented || !form.matches('.ht-page form[method="POST"]')) return;
        event.preventDefault();
        const body = new FormData(form);
        if (event.submitter?.name) body.set(event.submitter.name, event.submitter.value);
        save(form.action, body, form.closest('.tab-pane').id, form);
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('.ht-page .ht-toggle[data-status-url]');
        if (!button) return;
        event.preventDefault();
        const body = new FormData();
        body.set('_token', document.querySelector('.ht-page input[name="_token"]').value);
        body.set('_method', 'PUT');
        body.set('action', button.getAttribute('aria-checked') === 'true' ? 'deactivate' : 'activate');
        if (button.dataset.districtId) body.set('district_id', button.dataset.districtId);
        save(button.dataset.statusUrl, body, button.closest('.tab-pane').id, button);
    });
    document.addEventListener('change', event => {
        if (event.target.id !== 'district_city') return;
        const select = document.getElementById('district_ids');
        select.replaceChildren();
        JSON.parse(document.getElementById('team-district-data').textContent)
            .filter(d => String(d.city_id) === event.target.value)
            .forEach(d => select.add(new Option(d.name, d.id)));
    });
})();
