/* إدارة المسار: خريطة مسار السائق خطوة بخطوة، تحديد المهام وتحويلها (routing/transfer مع فحص المقاعد)،
 * ترتيب المهام بالسحب (routing/reorder)، مقاعد المركبة، موقع الزيارة، والنوافذ المخصصة.
 * النصوص من data.i18n بلغة الواجهة. */
(() => {
    'use strict';
    const root = document.getElementById('routing');
    if (!root) return;
    const data = JSON.parse(document.getElementById('rt-data').textContent);
    const COLORS = { base: '#2563eb', delivery: '#f0a020', pickup: '#17996f', transit: '#0891b2' };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const t = (key, params = {}) => Object.entries(params).reduce((text, [k, v]) => text.split(':' + k).join(v), data.i18n[key] ?? key);
    const $ = id => document.getElementById(id);
    const tasks = {};
    data.drivers.forEach(d => d.tasks.forEach(task => { tasks[task.key] = task; }));
    data.unassigned.forEach(task => { tasks[task.key] = task; });

    function notify(options) {
        if (window.Swal) return Swal.fire(Object.assign({ confirmButtonText: t('ok') }, options, { customClass: { popup: 'om-swal' } }));
        if (options.showCancelButton) return Promise.resolve({ isConfirmed: confirm((options.title ? options.title + '\n' : '') + (options.text || '')) });
        alert(options.text || options.title);
        return Promise.resolve({ isConfirmed: true });
    }

    async function send(url, method, body) {
        const response = await fetch(url, { method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf }, body: JSON.stringify(body) });
        const json = await response.json().catch(() => ({}));
        if (!response.ok) {
            const first = json.errors ? Object.values(json.errors).flat()[0] : null;
            const error = new Error(first || json.message || t('error'));
            error.status = response.status; error.data = json;
            throw error;
        }
        return json;
    }

    function go(params) {
        const query = new URLSearchParams(Object.entries(Object.assign({}, data.query, params)).filter(([, v]) => v !== null && v !== undefined && v !== ''));
        location.href = data.baseUrl + '?' + query.toString();
    }

    // ——— التاريخ والفلاتر والأقسام ———
    $('rt-date')?.addEventListener('change', e => { if (e.target.value) go({ date: e.target.value }); });
    document.querySelectorAll('#rt-filters select').forEach(select => select.addEventListener('change', () => select.form.submit()));
    document.querySelectorAll('[data-toggle-section]').forEach(button => button.addEventListener('click', () => {
        const section = $(button.dataset.toggleSection);
        section.hidden = !section.hidden;
        button.classList.toggle('is-open', !section.hidden);
        button.setAttribute('aria-expanded', String(!section.hidden));
        if (!section.hidden) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    // ——— خريطة المسار ———
    const mapModal = $('rt-map-modal');
    let map, layer, route = null, step = 0;

    function pin(color, label) {
        return L.divIcon({ className: '', iconSize: [30, 30], iconAnchor: [15, 30], html: '<div class="rt-marker" style="background:' + color + '"><span>' + esc(label) + '</span></div>' });
    }

    function draw() {
        layer.clearLayers();
        const stops = route.stops;
        const base = route.base;
        $('rt-step').textContent = t('step', { step, total: stops.length });
        $('rt-prev').disabled = step <= 1;
        $('rt-next').disabled = step >= stops.length;
        const points = [];
        if (base) { L.marker([base.lat, base.lng], { icon: pin(COLORS.base, 'T') }).bindTooltip(esc(t('base_tooltip', { name: base.name }))).addTo(layer); points.push([base.lat, base.lng]); }
        stops.slice(0, step).forEach((stop, i) => {
            const current = i === step - 1;
            const color = current ? COLORS[stop.role] : COLORS.transit;
            L.marker([stop.lat, stop.lng], { icon: pin(color, i + 1), zIndexOffset: current ? 1000 : 0 })
                .bindTooltip(esc(stop.clock + ' ' + stop.role_label + ' — ' + (stop.customer || ''))).addTo(layer);
            points.push([stop.lat, stop.lng]);
            // من عميل إلى عميل: خط متصل؛ وإلا فكل مهمة من المقر.
            const from = data.clientToClient ? (i === 0 ? base : stops[i - 1]) : base;
            if (from) L.polyline([[from.lat, from.lng], [stop.lat, stop.lng]], { color: COLORS.base, weight: current ? 4 : 3, opacity: current ? .95 : .45, dashArray: current ? null : '6 6' }).addTo(layer);
        });
        const stop = stops[step - 1];
        $('rt-step-info').innerHTML = stop
            ? '<b>' + esc(stop.clock) + ' · ' + esc(stop.role_label) + '</b> — ' + esc(stop.workers || t('no_worker')) + ' — ' + esc(stop.customer || '') + ' #' + esc(stop.order_number)
                + (stop.km !== null ? ' — <b>' + stop.km.toFixed(2) + ' ' + esc(t('km')) + '</b>' : '') + '<div class="rt-sub">' + esc(stop.address || '') + '</div>'
            : esc(t('no_stops'));
        if (points.length) map.fitBounds(L.latLngBounds(points).pad(0.25), { maxZoom: 15 });
    }

    document.querySelectorAll('[data-show-route]').forEach(button => button.addEventListener('click', () => {
        const driver = data.drivers.find(d => String(d.id) === button.dataset.showRoute);
        // نفس ترتيب الكرت (المحفوظ بالسحب أولًا).
        const stops = driver.tasks.filter(task => task.lat !== null && task.lng !== null);
        route = { base: driver.base, stops };
        step = stops.length ? 1 : 0;
        $('rt-map-title').textContent = t('route_title', { name: driver.name });
        $('rt-map-sub').textContent = t('stops', { count: stops.length }) + ' · ' + t(data.clientToClient ? 'mode_c2c' : 'mode_base') + (driver.base ? '' : ' · ' + t('no_base'));
        bootstrap.Modal.getOrCreateInstance(mapModal).show();
    }));

    mapModal?.addEventListener('shown.bs.modal', () => {
        if (!map) {
            map = L.map('rt-map').setView([24.7136, 46.6753], 11);
            HourlyMaps.attach(map);
            layer = L.layerGroup().addTo(map);
        }
        map.invalidateSize();
        draw();
    });
    $('rt-prev')?.addEventListener('click', () => { if (step > 1) { step--; draw(); } });
    $('rt-next')?.addEventListener('click', () => { if (step < route.stops.length) { step++; draw(); } });
    $('rt-full')?.addEventListener('click', () => {
        const wrap = $('rt-map-wrap');
        (document.fullscreenElement ? document.exitFullscreen() : wrap.requestFullscreen?.())?.then?.(() => setTimeout(() => map.invalidateSize(), 150));
    });

    // ——— موقع زيارة واحدة داخل الصفحة ———
    const locationModal = $('rt-location-modal');
    let locationMap, locationMarker, locationTask = null;
    document.querySelectorAll('[data-location]').forEach(button => button.addEventListener('click', () => {
        locationTask = tasks[button.dataset.location];
        $('rt-location-sub').textContent = (locationTask.customer || '') + ' · #' + locationTask.order_number + ' · ' + (locationTask.address || '');
        $('rt-location-google').href = locationTask.map_url;
        bootstrap.Modal.getOrCreateInstance(locationModal).show();
    }));
    locationModal?.addEventListener('shown.bs.modal', () => {
        if (!locationMap) { locationMap = L.map('rt-location-map'); HourlyMaps.attach(locationMap); }
        const point = [locationTask.lat, locationTask.lng];
        if (locationMarker) locationMarker.remove();
        locationMarker = L.marker(point, { icon: pin(COLORS[locationTask.role], locationTask.role === 'delivery' ? '↓' : '↑') }).addTo(locationMap)
            .bindTooltip(esc(locationTask.customer || ''), { permanent: true, direction: 'top', offset: [0, -30] });
        locationMap.invalidateSize();
        locationMap.setView(point, 16);
    });

    // ——— التحديد ———
    const selected = new Set();
    const selbar = $('rt-selbar');
    function refreshSelection() {
        document.querySelectorAll('[data-select-task]').forEach(box => {
            box.checked = selected.has(box.dataset.selectTask);
            box.closest('tr').classList.toggle('is-selected', box.checked);
        });
        document.querySelectorAll('[data-select-all]').forEach(all => {
            const boxes = [...document.querySelectorAll('[data-select-task][data-group="' + all.dataset.selectAll + '"]:not(:disabled)')];
            const count = boxes.filter(b => b.checked).length;
            all.checked = boxes.length > 0 && count === boxes.length;
            all.indeterminate = count > 0 && count < boxes.length;
        });
        if (selbar) { selbar.hidden = selected.size === 0; $('rt-selcount').textContent = t('selected', { count: selected.size }); }
    }
    root.addEventListener('change', e => {
        const box = e.target;
        if (box.matches('[data-select-task]')) { box.checked ? selected.add(box.dataset.selectTask) : selected.delete(box.dataset.selectTask); refreshSelection(); }
        if (box.matches('[data-select-all]')) {
            document.querySelectorAll('[data-select-task][data-group="' + box.dataset.selectAll + '"]:not(:disabled)')
                .forEach(b => { box.checked ? selected.add(b.dataset.selectTask) : selected.delete(b.dataset.selectTask); });
            refreshSelection();
        }
    });
    $('rt-clear-selected')?.addEventListener('click', () => { selected.clear(); refreshSelection(); });
    $('rt-transfer-selected')?.addEventListener('click', () => {
        const keys = [...selected];
        openTransfer(keys, t('transfer_selected_title'), t('transfer_selected_sub', { count: keys.length }));
    });

    // ——— التحويل ———
    const transferModal = $('rt-transfer-modal');
    const driverSelect = $('rt-transfer-driver');
    const teamOnly = $('rt-team-only');
    let transfer = null;

    function renderChoices() {
        const list = transfer.keys.map(k => tasks[k]);
        const teams = [...new Set(list.map(task => task.team_id).filter(Boolean))];
        const owners = [...new Set(list.map(task => task.driver_id))];
        // سائق المهام الحالي يُستبعد فقط إن كانت كلها له؛ الفريق يطابق كل فرق المهام المحددة.
        const choices = data.choices.filter(d => !(owners.length === 1 && owners[0] === d.id) && (!teamOnly.checked || teams.every(team => d.teams.includes(team))));
        const current = driverSelect.value;
        driverSelect.innerHTML = '<option value="">' + esc(t('choose_driver')) + '</option>' + choices.map(d => '<option value="' + d.id + '">' + esc(d.name) + '</option>').join('');
        if (choices.some(d => String(d.id) === current)) driverSelect.value = current;
        $('rt-transfer-hint').textContent = t(teamOnly.checked ? 'hint_team' : 'hint_all');
        $('rt-transfer-empty').hidden = choices.length > 0;
    }

    function openTransfer(keys, title, sub) {
        transfer = { keys };
        $('rt-transfer-title').textContent = title;
        $('rt-transfer-sub').textContent = sub;
        $('rt-transfer-reason').value = '';
        teamOnly.checked = true;
        $('rt-same-driver').checked = false;
        driverSelect.value = '';
        renderChoices();
        bootstrap.Modal.getOrCreateInstance(transferModal).show();
    }
    teamOnly?.addEventListener('change', renderChoices);

    document.querySelectorAll('[data-transfer-task]').forEach(button => button.addEventListener('click', () => {
        const task = tasks[button.dataset.transferTask];
        openTransfer([task.key], t(task.driver_id ? 'transfer_title' : 'assign_title'),
            task.clock + ' · ' + task.role_label + ' · ' + (task.customer || '') + ' #' + task.order_number + ' ' + t('visit') + ' ' + task.visit_number);
    }));

    document.querySelectorAll('[data-transfer-all]').forEach(button => button.addEventListener('click', () => {
        const driver = data.drivers.find(d => String(d.id) === button.dataset.transferAll);
        const keys = driver.tasks.filter(task => task.movable).map(task => task.key);
        openTransfer(keys, t('transfer_all_title'), t('transfer_all_sub', { count: keys.length, name: driver.name }));
    }));

    async function submitTransfer(confirmCapacity) {
        const payload = {
            to_driver_id: Number(driverSelect.value), tasks: transfer.keys, reason: $('rt-transfer-reason').value.trim(),
            expected: Object.fromEntries(transfer.keys.map(k => [k, tasks[k].driver_id])),
            same_driver: $('rt-same-driver').checked, confirm_capacity: confirmCapacity,
        };
        try {
            return await send(root.dataset.transfer, 'POST', payload);
        } catch (error) {
            // تجاوز مقاعد المركبة: لا يُنفَّذ شيء حتى يؤكد المستخدم.
            if (error.status === 409 && error.data?.needs_confirmation && !confirmCapacity) {
                const answer = await notify({ icon: 'warning', title: t('capacity_title'), text: error.message, showCancelButton: true, confirmButtonText: t('capacity_confirm'), cancelButtonText: t('cancel') });
                if (answer.isConfirmed) return submitTransfer(true);
                return null;
            }
            throw error;
        }
    }

    $('rt-transfer-form')?.addEventListener('submit', async e => {
        e.preventDefault();
        if (!driverSelect.value || !$('rt-transfer-reason').value.trim()) return notify({ icon: 'warning', title: t('complete_title'), text: t('complete_text') });
        const submit = $('rt-transfer-submit');
        submit.disabled = true;
        try {
            const result = await submitTransfer(false);
            if (!result) return;
            bootstrap.Modal.getOrCreateInstance(transferModal).hide();
            await notify({ icon: 'success', title: t('done'), text: result.message });
            location.reload();
        } catch (error) {
            notify({ icon: 'error', title: t('transfer_failed'), text: error.message });
        } finally {
            submit.disabled = false;
        }
    });

    // ——— الترتيب بالسحب ———
    if (window.Sortable) {
        document.querySelectorAll('tbody[data-sortable]').forEach(body => Sortable.create(body, {
            handle: '.rt-handle', draggable: 'tr[data-key]', animation: 150, ghostClass: 'rt-ghost',
            onEnd: async event => {
                if (event.oldIndex === event.newIndex) return;
                const keys = [...body.querySelectorAll('tr[data-key]')].map(row => row.dataset.key);
                try {
                    await send(root.dataset.reorder, 'POST', { driver_id: Number(body.dataset.sortable), date: data.date, tasks: keys });
                    // المسافات (من عميل إلى عميل) والخريطة تتبع الترتيب الجديد.
                    location.reload();
                } catch (error) {
                    await notify({ icon: 'error', title: t('reorder_failed'), text: error.message });
                    location.reload();
                }
            },
        }));
    }

    // ——— مقاعد المركبة ———
    const seatsModal = $('rt-seats-modal');
    let seatsDriver = null;
    document.querySelectorAll('[data-edit-seats]').forEach(button => button.addEventListener('click', () => {
        seatsDriver = Number(button.dataset.editSeats);
        $('rt-seats-sub').textContent = data.drivers.find(d => d.id === seatsDriver)?.name || '';
        $('rt-seats-input').value = button.dataset.seats || '';
        bootstrap.Modal.getOrCreateInstance(seatsModal).show();
    }));
    $('rt-seats-form')?.addEventListener('submit', async e => {
        e.preventDefault();
        try {
            await send(root.dataset.capacity, 'PUT', { driver_id: seatsDriver, seats: Number($('rt-seats-input').value) });
            location.reload();
        } catch (error) {
            notify({ icon: 'error', title: t('save_failed'), text: error.message });
        }
    });

    // ——— النوافذ المخصصة ———
    const windowsModal = $('rt-windows-modal');
    const list = $('rt-windows-list');
    let windows = data.windows.map(w => ({ ...w }));
    let removedActiveWindow = false;
    const isActiveWindow = w => data.query.period === 'custom' && w.from === data.query.from && w.to === data.query.to;

    function renderWindows() {
        if (!windows.length) { list.innerHTML = '<div class="rt-sub">' + esc(t('windows_empty')) + '</div>'; return; }
        list.innerHTML = windows.map((w, i) => '<div class="rt-window" data-i="' + i + '">'
            + '<input class="form-control" data-k="name" maxlength="40" placeholder="' + esc(t('window_name')) + '" value="' + esc(w.name) + '">'
            + '<input class="form-control" type="time" data-k="from" value="' + esc(w.from) + '">'
            + '<input class="form-control" type="time" data-k="to" value="' + esc(w.to) + '">'
            + '<button type="button" class="btn btn-primary btn-sm" data-apply="' + i + '"><i class="fa fa-eye"></i> ' + esc(t('show')) + '</button>'
            + '<button type="button" class="btn btn-light btn-sm" data-remove="' + i + '" aria-label="' + esc(t('delete')) + '"><i class="fa fa-trash"></i></button>'
            + '</div>').join('');
    }

    list?.addEventListener('input', e => { const row = e.target.closest('[data-i]'); if (row) windows[row.dataset.i][e.target.dataset.k] = e.target.value; });
    list?.addEventListener('click', e => {
        const apply = e.target.closest('[data-apply]');
        const remove = e.target.closest('[data-remove]');
        if (apply) {
            const w = windows[apply.dataset.apply];
            if (!w.from || !w.to || w.to <= w.from) return notify({ icon: 'warning', title: t('invalid_time_title'), text: t('invalid_time_text') });
            go({ period: 'custom', from: w.from, to: w.to, window: w.name || null });
        }
        if (remove) {
            const index = Number(remove.dataset.remove);
            removedActiveWindow = removedActiveWindow || isActiveWindow(windows[index]);
            windows.splice(index, 1);
            renderWindows();
        }
    });
    $('rt-window-add')?.addEventListener('click', () => { windows.push({ name: '', from: '08:00', to: '12:00' }); renderWindows(); });
    $('rt-windows-save')?.addEventListener('click', async () => {
        try {
            const result = await send(root.dataset.windows, 'PUT', { windows });
            windows = result.windows; data.windows = windows.map(w => ({ ...w })); renderWindows();
            await notify({ icon: 'success', title: t('done'), text: result.message });
            if (removedActiveWindow && !windows.some(isActiveWindow)) {
                go({ period: null, from: null, to: null, window: null });
            }
            removedActiveWindow = false;
        } catch (error) {
            notify({ icon: 'error', title: t('save_failed'), text: error.message });
        }
    });
    $('rt-open-windows')?.addEventListener('click', () => {
        windows = data.windows.map(w => ({ ...w }));
        removedActiveWindow = false;
        renderWindows();
        bootstrap.Modal.getOrCreateInstance(windowsModal).show();
    });

    refreshSelection();
})();
