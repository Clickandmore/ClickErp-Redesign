/* تتبع GPS بملء الشاشة: آخر موقع لكل عاملة/سائق والعملاء من عناوين طلباتهم، مجمّعة بـ markercluster،
 * ويتحدث تلقائيًا ما دام اليوم هو الحالي. «الحالة الآن» و«عرض المسار» تُجلب لكل شخص عند الطلب.
 * النصوص والألوان من #gps-i18n بلغة الواجهة. */
(() => {
    'use strict';
    const form = document.getElementById('gps');
    if (!form) return;
    const i18n = JSON.parse(document.getElementById('gps-i18n').textContent);
    const COLORS = i18n.colors;
    const t = (key, params = {}) => Object.entries(params).reduce((text, [k, v]) => text.split(':' + k).join(v), i18n[key] ?? key);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const $ = id => document.getElementById(id);
    const rtl = document.documentElement.dir === 'rtl';
    const corner = rtl ? 'bottomleft' : 'bottomright';
    const initials = name => String(name || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    const keyOf = p => p.type + ':' + p.id;
    const store = { get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } }, set(k, v) { try { localStorage.setItem(k, v); } catch (_) { /* تخزين غير متاح */ } } };

    // ——— الخريطة: الطبقة المختارة (خريطة/قمر صناعي) محفوظة لكل مستخدم ———
    const baseKey = 'hourly-gps-base:' + form.dataset.admin;
    const map = L.map('map', { zoomControl: false }).setView([24.7136, 46.6753], 11);
    L.control.zoom({ position: corner }).addTo(map);
    HourlyMaps.attach(map, { satellite: true, base: store.get(baseKey) === 'satellite' ? 'satellite' : 'streets', controlPosition: corner,
        labels: { streets: t('streets'), satellite: t('satellite') }, onBaseChange: name => store.set(baseKey, name) });
    const layer = L.markerClusterGroup ? L.markerClusterGroup({ showCoverageOnHover: false, maxClusterRadius: 45, spiderfyOnMaxZoom: true }) : L.featureGroup();
    layer.addTo(map);
    const trailLayer = L.featureGroup().addTo(map);
    const markers = new Map();
    let points = [], timer, request = 0, fitted = false, live = true, past = false, trailOf = null;
    let controller, failures = 0;
    let realtimeConnected = false, loading = false, realtimePending = false;
    $('live').title = t('socket_fallback');
    const pollDelay = () => realtimeConnected ? 60000 : Number(form.dataset.refresh) * 1000;
    function requestLiveRefresh() {
        if (!live || document.hidden) return;
        if (loading) { realtimePending = true; return; }
        load();
    }
    function transportState(connected) {
        realtimeConnected = connected;
        $('live').title = t(connected ? 'socket_connected' : 'socket_fallback');
        requestLiveRefresh();
    }

    function stopMovement(marker) {
        if (marker.gpsFrame) cancelAnimationFrame(marker.gpsFrame);
        marker.gpsFrame = null;
    }

    function moveMarker(marker, point) {
        stopMovement(marker);
        const from = marker.getLatLng(), to = L.latLng(point.lat, point.lng);
        if (from.equals(to)) return;
        // Animate visible, nearby movements only; interpolation is not a recorded route.
        if (!live || !marker.getElement() || from.distanceTo(to) > 5000 || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            marker.setLatLng(to); return;
        }
        const started = performance.now();
        const frame = now => {
            const progress = Math.min(1, (now - started) / 600);
            marker.setLatLng([from.lat + (to.lat - from.lat) * progress, from.lng + (to.lng - from.lng) * progress]);
            marker.gpsFrame = progress < 1 ? requestAnimationFrame(frame) : null;
        };
        marker.gpsFrame = requestAnimationFrame(frame);
    }

    function syncMarkers() {
        const retained = new Set(points.map(keyOf));
        markers.forEach((marker, key) => {
            if (!retained.has(key)) { stopMovement(marker); layer.removeLayer(marker); markers.delete(key); }
        });
        points.forEach(p => {
            const key = keyOf(p);
            let marker = markers.get(key);
            if (!marker) {
                marker = L.marker([p.lat, p.lng], { icon: icon(p), zIndexOffset: p.active ? 500 : 0, title: p.name });
                marker.gpsPoint = p;
                marker.bindPopup(() => popup(marker.gpsPoint), { minWidth: 240 });
                marker.bindTooltip(esc(p.name), { direction: 'top', offset: [0, -12] });
                marker.addTo(layer); markers.set(key, marker);
                return;
            }
            const previous = marker.gpsPoint;
            marker.gpsPoint = p;
            if (previous.active !== p.active || previous.heading !== p.heading) {
                marker.setIcon(icon(p)); marker.setZIndexOffset(p.active ? 500 : 0);
            }
            if (previous.name !== p.name) {
                marker.setTooltipContent(esc(p.name));
                if (marker.getElement()) marker.getElement().title = p.name;
            }
            moveMarker(marker, p);
            if (marker.isPopupOpen()) marker.setPopupContent(() => popup(marker.gpsPoint));
        });
    }

    const Fit = L.Control.extend({
        options: { position: corner },
        onAdd() {
            const bar = L.DomUtil.create('div', 'leaflet-bar leaflet-control fit');
            const link = L.DomUtil.create('a', '', bar);
            link.href = '#'; link.title = i18n.fit_all; link.setAttribute('role', 'button'); link.setAttribute('aria-label', i18n.fit_all);
            link.innerHTML = '<i class="fa fa-expand"></i>';
            L.DomEvent.on(link, 'click', e => { L.DomEvent.preventDefault(e); fitAll(); });
            return bar;
        },
    });
    new Fit().addTo(map);

    function compass(deg) { return i18n.compass[Math.round(deg / 45) % 8]; }

    function icon(p) {
        if (p.type === 'customer') {
            return L.divIcon({ className: '', iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -14], html: '<div class="house ' + (p.active ? '' : 'is-off') + '"><i class="fa fa-house"></i></div>' });
        }
        // السهم يدور باتجاه الحركة؛ يظهر فقط حين يرسل التطبيق الاتجاه.
        const arrow = p.heading !== null ? '<span class="arrow" style="transform:rotate(' + p.heading + 'deg)"></span>' : '';
        return L.divIcon({ className: '', iconSize: [26, 26], iconAnchor: [13, 13], popupAnchor: [0, -14],
            html: '<div class="pin ' + (p.active ? 'is-on' : 'is-off') + '" style="--c:' + COLORS[p.type] + '">' + arrow + '<span class="ring"></span><span class="core"></span></div>' });
    }

    function row(iconName, value, ltr) {
        return value === null || value === undefined || value === '' ? '' : '<dt><i class="fa ' + iconName + '"></i></dt><dd' + (ltr ? ' dir="ltr" style="text-align:start"' : '') + '>' + value + '</dd>';
    }

    function popup(p) {
        const state = '<span class="state ' + (p.active ? 'is-on' : 'is-off') + '">' + esc(p.type === 'customer' ? (p.active ? t('has_visit', { time: p.visit }) : t('no_visit')) : t(p.active ? 'online' : 'offline')) + '</span>';
        const details = p.type === 'customer'
            ? row('fa-phone', esc(p.phone), true) + row('fa-city', esc(p.city)) + row('fa-location-dot', esc(p.address)) + row('fa-receipt', p.order_number ? esc(t('order') + ' #' + p.order_number) : '')
              + row('fa-circle-info', esc(t('address_source'))) + row('fa-signal', state)
            : row('fa-phone', esc(p.phone), true) + row('fa-city', esc(p.city))
              + row('fa-clock', esc(t('last_seen')) + ': ' + esc(p.recorded_at) + ' · ' + esc(p.ago))
              + row('fa-gauge-high', p.speed !== null ? esc(t('speed')) + ': ' + Math.round(p.speed) + ' ' + esc(t('kmh')) : '')
              + row('fa-compass', p.heading !== null ? esc(t('heading')) + ': ' + p.heading + '° ' + esc(compass(p.heading)) : '')
              + row('fa-mobile-screen', p.app_version ? esc(t('app_version')) + ': ' + esc(p.app_version) : '')
              + row('fa-microchip', p.device_type ? esc(t('device')) + ': ' + esc(p.device_type) : '')
              + row('fa-bullseye', p.accuracy !== null ? esc(t('accuracy', { m: Math.round(p.accuracy) })) : '')
              + row('fa-signal', state);
        const actions = [];
        if (live) actions.push('<button type="button" data-now="' + esc(keyOf(p)) + '"><i class="fa fa-list-check"></i> ' + esc(t('status_now')) + '</button>');
        if (past && p.type !== 'customer') actions.push('<button type="button" data-trail="' + esc(keyOf(p)) + '"><i class="fa fa-route"></i> ' + esc(t(trailOf === keyOf(p) ? 'hide_trail' : 'show_trail')) + '</button>');
        actions.push('<a class="go ' + (actions.length % 2 ? '' : 'full') + '" href="https://www.google.com/maps/search/?api=1&query=' + p.lat + ',' + p.lng + '" target="_blank" rel="noopener"><i class="fa fa-diamond-turn-right"></i> ' + esc(t('open_maps')) + '</a>');
        return '<div class="pop" style="--c:' + COLORS[p.type] + '"><h3>' + esc(p.name) + '</h3><span class="tag">' + esc(p.type_label) + '</span><dl>' + details + '</dl>'
            + '<div class="now" data-now-box hidden></div><div class="actions">' + actions.join('') + '</div></div>';
    }

    function fitAll() {
        const bounds = layer.getBounds();
        if (bounds.isValid()) { map.fitBounds(bounds.pad(0.25), { maxZoom: 14, paddingTopLeft: [20, 200], paddingBottomRight: [20, 90] }); return true; }
        const city = form.querySelector('input[name="city_ids[]"]:checked');
        if (city && city.dataset.lat && city.dataset.lng) map.setView([Number(city.dataset.lat), Number(city.dataset.lng)], 11);
        return false;
    }

    function toast(text) {
        const box = $('toast');
        box.hidden = !text;
        box.innerHTML = text ? '<i class="fa fa-location-pin-lock" style="color:#94a3b8"></i> ' + esc(text) : '';
    }

    // كل المدن محددة = بلا فلتر مدينة، حتى لا تختفي مواقع خارج نطاق أي مدينة.
    function query() {
        const params = new URLSearchParams(new FormData(form));
        const boxes = form.querySelectorAll('input[name="city_ids[]"]');
        if ([...boxes].every(b => b.checked)) params.delete('city_ids[]');
        return params.toString();
    }

    // ——— التحميل ———
    async function load() {
        clearTimeout(timer);
        controller?.abort();
        const id = ++request;
        loading = true;
        realtimePending = false;
        controller = new AbortController();
        const currentController = controller;
        const timeout = setTimeout(() => currentController.abort(), 15000);
        const refresh = $('refresh');
        refresh.classList.add('is-spin');
        try {
            const response = await fetch(form.dataset.url + '?' + query(), { headers: { Accept: 'application/json' }, signal: currentController.signal, cache: 'no-store' });
            if (id !== request) return;
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (id !== request) return;
            failures = 0;
            live = data.live; past = data.past;
            points = data.points;
            syncMarkers();
            if (trailOf && !past) clearTrail();
            renderStats(data);
            renderPeople();
            toast(points.length ? '' : t('empty'));
            if (!fitted || !data.live) fitted = fitAll();
        } catch (_) {
            if (id === request && !document.hidden) { failures++; toast(t('failed')); }
        } finally {
            clearTimeout(timeout);
            if (id === request) {
                loading = false;
                refresh.classList.remove('is-spin');
                if (live && !document.hidden) timer = setTimeout(load, realtimePending && !failures ? 250 : Math.min(60000, pollDelay() * 2 ** Math.min(failures, 4)));
            }
        }
    }

    function renderStats(data) {
        const counts = { maid: 0, driver: 0, customer: 0 };
        points.forEach(p => { counts[p.type]++; });
        const active = points.filter(p => p.active).length;
        const selected = [...form.querySelectorAll('input[name="types[]"]:checked')].map(i => i.value);
        $('stats').innerHTML = Object.keys(counts).filter(k => selected.includes(k))
            .map(k => '<span class="stat" style="--c:' + COLORS[k] + '"><i class="dot"></i><b>' + counts[k] + '</b> ' + esc(i18n.types[k]) + '</span>').join('')
            + '<span class="stat is-active"><i class="fa fa-signal"></i> ' + esc(t('active_count', { count: active })) + '</span>';
        const badge = $('live');
        badge.textContent = data.live ? t('live') : t('archive');
        badge.classList.toggle('is-archive', !data.live);
        $('subtitle').textContent = data.live ? t('updated', { time: data.server_time }) : form.elements.date.value;
        $('people-count').textContent = points.length;
    }

    // ——— داخل النافذة: الحالة الآن والمسار ———
    map.on('popupopen', event => {
        const box = event.popup.getElement();
        box.querySelector('[data-now]')?.addEventListener('click', e => statusNow(e.currentTarget.dataset.now, box));
        box.querySelector('[data-trail]')?.addEventListener('click', e => toggleTrail(e.currentTarget.dataset.trail, e.currentTarget));
    });

    async function statusNow(key, box) {
        const [type, id] = key.split(':');
        const target = box.querySelector('[data-now-box]');
        target.hidden = false;
        target.innerHTML = '<div>' + esc(t('loading')) + '</div>';
        try {
            const response = await fetch(form.dataset.status + '?' + new URLSearchParams({ type, id }), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const data = await response.json();
            const item = (label, v) => '<div><b>' + esc(label) + '</b>' + (v
                ? (v.task ? esc(v.task) + ' · ' : '') + esc(v.time) + ' · ' + esc(v.customer || '') + '<br><a href="' + esc(v.url) + '" target="_blank" rel="noopener">'
                  + esc(t('order')) + ' #' + esc(v.order_number) + ' · ' + esc(t('visit')) + ' ' + esc(v.visit_number) + '</a> · ' + esc(v.status)
                : esc(t('none'))) + '</div>';
            target.innerHTML = item(t('current'), data.current) + item(t('next'), data.next);
        } catch (_) {
            target.innerHTML = '<div>' + esc(t('failed')) + '</div>';
        }
    }

    function clearTrail() { trailLayer.clearLayers(); trailOf = null; }

    async function toggleTrail(key, button) {
        if (trailOf === key) { clearTrail(); button.innerHTML = '<i class="fa fa-route"></i> ' + esc(t('show_trail')); return; }
        const [type, id] = key.split(':');
        const response = await fetch(form.dataset.trail + '?' + new URLSearchParams({ type, id, date: form.elements.date.value }), { headers: { Accept: 'application/json' } });
        const data = response.ok ? await response.json() : { points: [] };
        clearTrail();
        if (!data.points.length) { toast(t('trail_empty')); setTimeout(() => toast(points.length ? '' : t('empty')), 2500); return; }
        const color = COLORS[type];
        const line = data.points.map(p => [p.lat, p.lng]);
        L.polyline(line, { color, weight: 4, opacity: .85 }).addTo(trailLayer);
        const first = data.points[0], last = data.points[data.points.length - 1];
        L.circleMarker(line[0], { radius: 7, color: '#fff', weight: 2, fillColor: '#22c55e', fillOpacity: 1 }).bindTooltip(esc(t('trail_start', { time: first.at }))).addTo(trailLayer);
        L.circleMarker(line[line.length - 1], { radius: 7, color: '#fff', weight: 2, fillColor: '#ef4444', fillOpacity: 1 }).bindTooltip(esc(t('trail_end', { time: last.at }))).addTo(trailLayer);
        trailOf = key;
        button.innerHTML = '<i class="fa fa-route"></i> ' + esc(t('hide_trail'));
        map.fitBounds(trailLayer.getBounds().pad(0.2), { paddingTopLeft: [20, 200], paddingBottomRight: [20, 90] });
    }

    // ——— قائمة الأشخاص ———
    function renderPeople() {
        const q = $('people-search').value.trim().toLowerCase();
        const list = points.filter(p => !q || String(p.name).toLowerCase().includes(q) || String(p.phone || '').includes(q))
            .sort((a, b) => (b.active - a.active) || String(a.name).localeCompare(String(b.name)));
        $('people-list').innerHTML = list.length ? list.map(p => '<button type="button" class="person" data-key="' + esc(keyOf(p)) + '" style="--c:' + COLORS[p.type] + '">'
            + '<span class="avatar ' + (p.active ? 'is-on' : '') + '">' + (p.type === 'customer' ? '<i class="fa fa-house"></i>' : esc(initials(p.name))) + '</span>'
            + '<span class="who"><b>' + esc(p.name) + '</b><small>' + esc(p.type_label) + (p.city ? ' · ' + esc(p.city) : '') + '</small></span>'
            + '<span class="when">' + esc(p.recorded_at ? p.recorded_at.slice(11) : (p.visit || '')) + '</span></button>').join('')
            : '<div class="people-empty">' + esc(points.length ? t('no_match') : t('empty')) + '</div>';
    }
    $('people-list').addEventListener('click', e => {
        const item = e.target.closest('[data-key]');
        const marker = item && markers.get(item.dataset.key);
        if (!marker) return;
        const show = () => { map.flyTo(marker.getLatLng(), Math.max(map.getZoom(), 16), { duration: .6 }); map.once('moveend', () => marker.openPopup()); };
        // داخل مجموعة مجمّعة: فكّ التجميع أولًا ثم افتح النافذة.
        if (layer.zoomToShowLayer) layer.zoomToShowLayer(marker, () => marker.openPopup()); else show();
        if (window.innerWidth < 640) togglePeople(false);
    });
    $('people-search').addEventListener('input', renderPeople);
    function togglePeople(open) {
        $('people').hidden = !open;
        $('people-btn').setAttribute('aria-expanded', String(open));
        if (open) $('people-search').focus();
    }
    $('people-btn').addEventListener('click', () => togglePeople($('people').hidden));
    $('people-close').addEventListener('click', () => togglePeople(false));

    // ——— المدن: كلها محددة افتراضيًا ———
    const cityMenu = $('cities-menu'), cityBtn = $('cities-btn');
    function openCities(open) {
        cityMenu.hidden = !open;
        cityBtn.classList.toggle('is-open', open);
        cityBtn.setAttribute('aria-expanded', String(open));
        if (open) $('cities-search').focus();
    }
    function cityLabel() {
        const boxes = [...form.querySelectorAll('input[name="city_ids[]"]')];
        const checked = boxes.filter(b => b.checked);
        const all = checked.length === boxes.length || !checked.length;
        $('cities-value').textContent = all ? t('all_cities') : checked.length === 1 ? checked[0].parentElement.textContent.trim() : t('cities', { count: checked.length });
        $('cities-count').textContent = all ? t('all_cities') : t('cities', { count: checked.length });
    }
    cityBtn.addEventListener('click', () => openCities(cityMenu.hidden));
    $('cities-search').addEventListener('input', e => {
        const q = e.target.value.trim().toLowerCase();
        cityMenu.querySelectorAll('.drop-list label').forEach(label => { label.hidden = q && !label.dataset.name.includes(q) && !label.textContent.toLowerCase().includes(q); });
    });
    // «مسح» يعيد تحديد كل المدن (= بلا فلتر).
    $('cities-clear').addEventListener('click', () => { form.querySelectorAll('input[name="city_ids[]"]').forEach(i => { i.checked = true; }); cityLabel(); fitted = false; load(); });
    document.addEventListener('click', e => { if (!cityMenu.hidden && !$('cities').contains(e.target)) openCities(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { openCities(false); togglePeople(false); } });

    // ——— الفلاتر والأزرار ———
    form.addEventListener('change', e => {
        if (e.target.id === 'cities-search' || e.target.id === 'people-search') return;
        if (e.target.name === 'city_ids[]') cityLabel();
        if (e.target.name === 'date') clearTrail();
        fitted = false; load();
    });
    $('refresh').addEventListener('click', load);
    $('toggle').addEventListener('click', e => {
        const min = form.classList.toggle('is-min');
        e.currentTarget.setAttribute('aria-expanded', String(!min));
        openCities(false);
    });
    if (window.innerWidth < 640) { form.classList.add('is-min'); $('toggle').setAttribute('aria-expanded', 'false'); }
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearTimeout(timer); controller?.abort();
            markers.forEach(marker => { stopMovement(marker); marker.setLatLng([marker.gpsPoint.lat, marker.gpsPoint.lng]); });
        } else if (live) load();
    });
    window.addEventListener('online', () => { if (live && !document.hidden) load(); });

    cityLabel();
    toast(t('loading'));
    load();
    const socket = window.HourlyGpsSocket?.(JSON.parse($('gps-socket-config')?.textContent || 'null'), requestLiveRefresh, transportState);
    window.addEventListener('pagehide', () => { socket?.disconnect(); clearTimeout(timer); controller?.abort(); });
})();
