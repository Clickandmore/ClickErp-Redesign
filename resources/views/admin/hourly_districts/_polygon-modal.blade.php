{{--
    مودال رسم حدود منطقة من قائمة المناطق.
    يُفتح بزر يحمل data-polygon (JSON: id, name, city, city_id, polygon, latitude, longitude, save_url, osm_url, boundary_url).
    الحفظ عبر admin.hourly-districts.polygon، والجلب من OSM يملأ الخريطة للمراجعة فقط دون حفظ.
--}}
<style>
    #polygonModal .pm-meta { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; justify-content: flex-end; margin-bottom: .9rem; }
    #polygonModal .pm-meta strong { font-size: 1.05rem; color: var(--ht-ink); }
    #polygonModal .pm-search { position: relative; margin-bottom: .9rem; }
    #polygonModal .pm-search > button { position: absolute; top: 23px; inset-inline-start: .4rem; transform: translateY(-50%); color: #64748b; border: 0; background: transparent; padding: .5rem; }
    #polygonModal .pm-search .form-control { height: 46px; padding-inline-start: 2.4rem; border-radius: 10px; }
    #polygonModal .pm-results { position: absolute; top: calc(100% + 4px); inset-inline: 0; z-index: 1100; background: #fff; border: 1px solid var(--ht-line); border-radius: 10px; box-shadow: 0 12px 30px rgba(15, 23, 42, .12); max-height: 240px; overflow: auto; }
    #polygonModal .pm-results button { display: block; width: 100%; text-align: right; border: 0; background: none; padding: .55rem .9rem; font-size: .85rem; }
    #polygonModal .pm-results button:hover { background: var(--ht-soft); }
    #polygonModal .pm-map { height: min(58vh, 540px); border-radius: 12px; border: 1px solid var(--ht-line); }
    #polygonModal .pm-actions { display: flex; gap: .6rem; flex-wrap: wrap; margin-top: 1rem; }
    #polygonModal .pm-actions .btn { padding: .6rem 1.2rem; border-radius: 8px; }
    #polygonModal .leaflet-control-layers-base label { direction: rtl; text-align: right; margin: 0; }
</style>

<div class="modal fade ht-page" id="polygonModal" tabindex="-1" aria-labelledby="polygonModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="polygonModalLabel">رسم الحدود</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="pm-meta">
                    <span class="ht-chip" id="pm-status"></span>
                    <strong id="pm-name"></strong>
                    <span class="ht-chip ht-chip-off" id="pm-city"></span>
                </div>
                <div id="pm-feedback" class="alert border-0 rounded-3 d-none" role="status"></div>
                <div class="pm-search">
                    <button type="button" id="pm-search-button" aria-label="بحث عن موقع" title="بحث عن موقع"><i class="fa fa-magnifying-glass"></i></button>
                    <input type="search" class="form-control" id="pm-search" placeholder="ابحث عن مكان للانتقال إليه على الخريطة ثم اضغط Enter" autocomplete="off" aria-label="البحث عن مكان">
                    <div class="pm-results d-none" id="pm-results"></div>
                </div>
                <div id="pm-map" class="pm-map" dir="ltr"></div>
                <div class="form-text mt-2"><i class="fa fa-circle-info ms-1"></i> استخدم أداة المضلع في الخريطة لرسم الحدود، أو أداة التعديل لتحريك النقاط. المناطق الأخرى في المدينة تظهر باللون الرمادي.</div>
                <div class="pm-actions">
                    <button type="button" class="btn btn-success" id="pm-save"><i class="fa fa-floppy-disk ms-1"></i> حفظ الحدود</button>
                    <button type="button" class="btn btn-outline-secondary" id="pm-clear"><i class="fa fa-eraser ms-1"></i> مسح</button>
                    <button type="button" class="btn btn-info text-white" id="pm-osm"><i class="fa fa-earth-asia ms-1"></i> جلب من OSM</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
@include('admin.hourly._map-provider')
<script>
(() => {
    const modal = document.getElementById('polygonModal');
    if (!modal) return;
    const $ = id => document.getElementById(id);
    const csrf = @json(csrf_token());
    const locateUrl = @json(route('admin.hourly-districts.locate'));
    let map = null, drawn = null, others = null, control = null, record = null, trigger = null, dirty = false, searchSeq = 0;

    function feedback(text, type = 'info') {
        const el = $('pm-feedback');
        if (!text) { el.className = 'alert border-0 rounded-3 d-none'; return; }
        el.className = 'alert border-0 rounded-3 alert-' + type;
        el.textContent = text;
    }
    function status() {
        const el = $('pm-status');
        if (dirty) { el.className = 'ht-chip hd-chip-primary'; el.textContent = 'تعديلات غير محفوظة'; }
        else if (record.polygon) { el.className = 'ht-chip ht-chip-on'; el.textContent = 'حدود محفوظة'; }
        else { el.className = 'ht-chip hd-chip-warn'; el.textContent = 'لا توجد حدود بعد'; }
    }
    async function request(url, method, body) {
        const response = await fetch(url, {method, credentials: 'same-origin', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}, body: body === undefined ? undefined : JSON.stringify(body)});
        let data = {};
        try { data = await response.json(); } catch (e) {}
        if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'تعذر تنفيذ الطلب.'));
        return data;
    }

    function initMap() {
        if (map) return;
        map = L.map('pm-map').setView([23.9, 45.1], 5);
        HourlyMaps.attach(map, {satellite:true});
        others = L.featureGroup().addTo(map);
        drawn = L.featureGroup().addTo(map);
        map.on(L.Draw.Event.CREATED, e => { drawn.clearLayers(); drawn.addLayer(e.layer); dirty = true; status(); controls(); });
        map.on(L.Draw.Event.EDITED, () => { dirty = true; status(); });
    }
    function controls() {
        if (control) map.removeControl(control);
        const empty = drawn.getLayers().length === 0;
        control = new L.Control.Draw({
            position: 'topright',
            draw: {polygon: empty ? {allowIntersection: false, showArea: true, shapeOptions: {color: '#16a34a'}} : false, polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false},
            edit: {featureGroup: drawn, remove: false},
        });
        map.addControl(control);
    }
    function showPolygon(points) {
        drawn.clearLayers();
        if (points && points.length) drawn.addLayer(L.polygon(points, {color: '#16a34a', weight: 3}));
        controls();
    }
    function contextPolygons() {
        others.clearLayers();
        document.querySelectorAll('[data-polygon]').forEach(button => {
            const other = JSON.parse(button.dataset.polygon);
            if (other.id === record.id || other.city_id !== record.city_id || !other.polygon) return;
            L.polygon(other.polygon, {color: '#94a3b8', weight: 1.5, fillOpacity: .08, interactive: false})
                .bindTooltip(document.createTextNode(other.name), {sticky: true}).addTo(others);
        });
    }
    function fitView() {
        if (drawn.getLayers().length) return map.fitBounds(drawn.getBounds(), {padding: [30, 30]});
        if (record.latitude !== null && record.longitude !== null) return map.setView([Number(record.latitude), Number(record.longitude)], 15);
        if (others.getLayers().length) return map.fitBounds(others.getBounds(), {padding: [30, 30]});
        map.setView([23.9, 45.1], 5);
        // لا شيء محلي: مركز المدينة أو نطاقها
        request(record.boundary_url, 'GET').then(data => {
            if (drawn.getLayers().length) return;
            if (data.boundary) map.fitBounds(L.geoJSON(data.boundary).getBounds());
            else if (data.bbox) map.fitBounds([[data.bbox[0], data.bbox[1]], [data.bbox[2], data.bbox[3]]]);
            else if (data.center) map.setView(data.center, 12);
        }).catch(() => {});
    }

    modal.addEventListener('show.bs.modal', event => {
        if (!event.relatedTarget || !event.relatedTarget.dataset.polygon) return;
        trigger = event.relatedTarget;
        record = JSON.parse(trigger.dataset.polygon);
        dirty = false;
        $('polygonModalLabel').textContent = (record.polygon ? 'تعديل الحدود — ' : 'رسم الحدود — ') + record.name;
        $('pm-name').textContent = record.name;
        $('pm-city').textContent = record.city;
        $('pm-search').value = '';
        $('pm-results').classList.add('d-none');
        feedback('');
        status();
    });
    modal.addEventListener('shown.bs.modal', () => {
        if (!record) return;
        if (typeof L === 'undefined' || !L.Control.Draw) { feedback('تعذر تحميل الخريطة. تحقق من الاتصال ثم أعد المحاولة.', 'danger'); return; }
        initMap();
        map.invalidateSize();
        contextPolygons();
        showPolygon(record.polygon);
        fitView();
    });
    modal.addEventListener('hide.bs.modal', event => {
        if (dirty && !confirm('توجد حدود غير محفوظة. إغلاق دون حفظ؟')) event.preventDefault();
    });

    // البحث عن مكان داخل مدينة المنطقة
    async function search() {
        const query = $('pm-search').value.trim(), results = $('pm-results'), seq = ++searchSeq;
        if (query.length < 2) { results.classList.add('d-none'); return; }
        results.replaceChildren(Object.assign(document.createElement('button'), {type: 'button', disabled: true, textContent: 'جارٍ البحث…'}));
        results.classList.remove('d-none');
        try {
            const data = await request(locateUrl, 'POST', {city_id: record.city_id, name: query});
            if (seq !== searchSeq) return;
            results.replaceChildren();
            (data.results || []).forEach(item => {
                const button = Object.assign(document.createElement('button'), {type: 'button', textContent: item.label});
                button.addEventListener('click', () => { map.setView([item.latitude, item.longitude], 16); results.classList.add('d-none'); });
                results.append(button);
            });
            if (!results.children.length) throw new Error('لا توجد نتائج.');
        } catch (e) {
            if (seq !== searchSeq) return;
            results.replaceChildren(Object.assign(document.createElement('button'), {type: 'button', disabled: true, textContent: e.message}));
        }
    }
    $('pm-search').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } if (e.key === 'Escape') $('pm-results').classList.add('d-none'); });
    $('pm-search-button').addEventListener('click', search);
    document.addEventListener('click', e => { if (!e.target.closest('.pm-search')) $('pm-results').classList.add('d-none'); });

    function setBusy(busy) { ['pm-save', 'pm-clear', 'pm-osm'].forEach(id => $(id).disabled = busy); }

    $('pm-save').addEventListener('click', async () => {
        const layer = drawn.getLayers()[0];
        if (!layer && !record.polygon) { feedback('ارسم حدود المنطقة أولًا باستخدام أداة المضلع.', 'warning'); return; }
        if (!layer && !(await hourlyConfirm({title: 'حذف الحدود', text: 'لا توجد حدود مرسومة. حذف الحدود المحفوظة لهذه المنطقة؟', button: 'نعم، احذف'}))) return;
        const polygon = layer ? layer.getLatLngs()[0].map(p => [p.lat, p.lng]) : null;
        setBusy(true);
        try {
            const data = await request(record.save_url, 'PUT', {polygon});
            if (!Object.prototype.hasOwnProperty.call(data, 'polygon')) throw new Error('يرجى تسجيل الدخول مجددًا.');
            record.polygon = data.polygon;
            trigger.dataset.polygon = JSON.stringify(record);
            dirty = false;
            showPolygon(record.polygon);
            status();
            feedback(data.message, 'success');
            const chip = document.querySelector('[data-polygon-chip="' + record.id + '"]');
            if (chip) {
                chip.className = 'ht-chip ' + (record.polygon ? 'ht-chip-on' : 'hd-chip-warn');
                chip.innerHTML = '<i class="fa ' + (record.polygon ? 'fa-draw-polygon' : 'fa-pen-ruler') + '"></i> ' + (record.polygon ? 'محفوظة' : 'ارسم الحدود');
            }
        } catch (e) {
            feedback(e.message, 'danger');
        } finally {
            setBusy(false);
        }
    });

    $('pm-clear').addEventListener('click', () => {
        if (!drawn.getLayers().length) return;
        drawn.clearLayers();
        controls();
        dirty = Boolean(record.polygon);
        status();
        feedback(record.polygon ? 'مُسحت الحدود من الخريطة. اضغط «حفظ الحدود» لحذفها نهائيًا، أو ارسم حدودًا جديدة.' : '', 'info');
    });

    $('pm-osm').addEventListener('click', async () => {
        if (drawn.getLayers().length && !(await hourlyConfirm({title: 'استبدال الحدود', text: 'استبدال الحدود الحالية على الخريطة بحدود OpenStreetMap؟', button: 'نعم، استبدل', danger: false}))) return;
        setBusy(true);
        feedback('جارٍ جلب الحدود من OpenStreetMap…', 'info');
        try {
            const data = await request(record.osm_url, 'GET');
            showPolygon(data.polygon);
            map.fitBounds(drawn.getBounds(), {padding: [30, 30]});
            dirty = true;
            status();
            feedback('تم جلب الحدود (' + data.label + '). راجعها وعدّلها إن لزم، ثم اضغط «حفظ الحدود».', 'success');
        } catch (e) {
            feedback(e.message, 'warning');
        } finally {
            setBusy(false);
        }
    });
})();
</script>
@endpush
