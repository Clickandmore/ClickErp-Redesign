@extends('admin.layout.indexs.index')

@section('page-title', 'حدود مناطق '.$city->name_ar)

@section('style')
    @include('admin.hourly_teams._style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
    <style>
        .pg-map { height: calc(100vh - 300px); min-height: 520px; border-radius: 14px; border: 1px solid var(--ht-line); }
        .pg-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; }
        .pg-toolbar .form-select { flex: 1 1 260px; max-width: 420px; }
        .pg-legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .78rem; color: var(--ht-muted); margin-top: .6rem; }
        .pg-legend i { display: inline-block; width: 18px; height: 0; vertical-align: middle; margin-inline-end: .35rem; }
    </style>
@endsection

@section('content')
    @php
        $canEdit = AdminGuardHasPerm('hourly_teams_edit');
        $geometry = $districts->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'polygon' => $d->polygon, 'url' => route('admin.hourly-districts.polygon', [$city, $d])])->values();
        $mappedCount = $districts->whereNotNull('polygon')->count();
    @endphp
    <div class="container-fluid ht-page" dir="rtl">
        <div class="ht-card">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-draw-polygon"></i></div>
                <div>
                    <h5>حدود مناطق {{ $city->name_ar }}</h5>
                    <p>
                        <a href="{{ route('admin.hourly-cities.index') }}" class="text-decoration-none">المدن</a>
                        <i class="fa fa-angle-left mx-1 small"></i> {{ $city->name_ar }}
                        · {{ $mappedCount }} من {{ $districts->count() }} مناطق لها حدود
                    </p>
                </div>
                <div class="ht-actions">
                    <span class="ht-chip ht-chip-off" id="city-boundary-status"><i class="fa fa-spinner fa-spin"></i> جارٍ تحميل حدود المدينة</span>
                    <a class="btn btn-outline-primary rounded-3" href="{{ route('admin.hourly-cities.districts', $city) }}"><i class="fa fa-map-pin ms-1"></i> إدارة المناطق</a>
                    <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-cities.index') }}"><i class="fa fa-arrow-right ms-1"></i> المدن</a>
                </div>
            </div>
            <div class="ht-body">
                <div id="polygon-feedback" class="alert alert-info border-0 rounded-3" role="status">اختر المنطقة لعرض حدودها. {{ $canEdit ? 'استخدم أدوات الخريطة لرسم المضلع أو تعديل نقاطه ثم احفظ.' : '' }}</div>

                <div class="pg-toolbar">
                    <select class="form-select" id="district" aria-label="المنطقة">
                        <option value="">اختر المنطقة</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}">{{ $district->name }} — {{ $district->polygon ? 'لها حدود' : 'بدون حدود' }}</option>
                        @endforeach
                    </select>
                    @if ($canEdit)
                        <button class="btn btn-primary rounded-3 text-nowrap" id="save-polygon" disabled><i class="fa fa-floppy-disk ms-1"></i> حفظ الحدود</button>
                        <button class="btn btn-outline-danger rounded-3 text-nowrap" id="clear-polygon" disabled><i class="fa fa-eraser ms-1"></i> مسح الحدود</button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary rounded-3 text-nowrap ms-auto" id="fit-city"><i class="fa fa-expand ms-1"></i> عرض المدينة كاملة</button>
                </div>

                @if ($districts->isEmpty())
                    <div class="ht-note ht-note-warn mb-3"><i class="fa fa-triangle-exclamation mt-1"></i><div>لا توجد مناطق لهذه المدينة بعد. أضف المناطق من «إدارة المناطق» ثم ارسم حدود كل منطقة هنا.</div></div>
                @endif

                <div id="polygon-map" class="pg-map" dir="ltr"></div>
                <div class="pg-legend">
                    <span><i style="border-top: 2px dashed #0e7490"></i> حدود المدينة</span>
                    <span><i style="border-top: 3px solid #625eee"></i> المنطقة المختارة</span>
                    <span><i style="border-top: 3px solid #9ba8b7"></i> مناطق أخرى</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@include('admin.hourly._confirm-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
@include('admin.hourly._map-provider')
<script>
(() => {
    const feedback = document.getElementById('polygon-feedback');
    const status = document.getElementById('city-boundary-status');
    if (typeof L === 'undefined') { feedback.textContent = 'تعذر تحميل الخريطة. تحقق من الاتصال ثم أعد المحاولة.'; return; }
    const districts = @json($geometry), canEdit = @json($canEdit);
    const cityPoint = @json($city->latitude !== null && $city->longitude !== null ? [(float) $city->latitude, (float) $city->longitude] : null);
    const select = document.getElementById('district'), save = document.getElementById('save-polygon'), clear = document.getElementById('clear-polygon');

    const map = L.map('polygon-map').setView(cityPoint || [23.9, 45.1], cityPoint ? 11 : 5);
    HourlyMaps.attach(map);
    // حدود المدينة: مرجع بصري فقط، أسفل المضلعات ولا يستقبل النقر حتى لا يعيق الرسم.
    const cityLayer = L.featureGroup().addTo(map);
    const layers = L.featureGroup().addTo(map), others = L.featureGroup().addTo(map);
    let current = null, dirty = false, control = null;

    // عرض المدينة نفسها: مضلعها إن وُجد، ثم نطاقها، ثم مركزها بتكبير مستوى المدينة، ثم المناطق المرسومة، ثم المملكة.
    let cityView = null;
    function fitCity() {
        if (cityLayer.getLayers().length) return map.fitBounds(cityLayer.getBounds(), {padding: [20, 20]});
        if (cityView && cityView.bbox) return map.fitBounds([[cityView.bbox[0], cityView.bbox[1]], [cityView.bbox[2], cityView.bbox[3]]]);
        const center = (cityView && cityView.center) || cityPoint;
        if (center) return map.setView(center, 12);
        const drawn = L.featureGroup([...layers.getLayers(), ...others.getLayers()]);
        if (drawn.getLayers().length) return map.fitBounds(drawn.getBounds(), {padding: [25, 25]});
        map.setView([23.9, 45.1], 5);
    }
    document.getElementById('fit-city').addEventListener('click', fitCity);

    function controls() {
        if (control) map.removeControl(control);
        if (canEdit && current) {
            control = new L.Control.Draw({draw: {polygon: layers.getLayers().length ? false : {allowIntersection: false, showArea: true}, polyline: false, rectangle: false, circle: false, circlemarker: false, marker: false}, edit: {featureGroup: layers, remove: false}});
            map.addControl(control);
        }
    }
    function choose(id) {
        current = districts.find(d => String(d.id) === id) || null;
        layers.clearLayers(); others.clearLayers();
        districts.forEach(d => {
            if (!d.polygon) return;
            const layer = L.polygon(d.polygon, {color: d === current ? '#625eee' : '#9ba8b7'});
            layer.bindTooltip(document.createTextNode(d.name));
            (d === current ? layers : others).addLayer(layer);
        });
        if (current && current.polygon) map.fitBounds(layers.getBounds(), {padding: [25, 25]});
        if (save) { save.disabled = !current; clear.disabled = !current; }
        dirty = false;
        controls();
    }
    select.addEventListener('change', () => {
        if (dirty && !confirm('تجاهل الحدود غير المحفوظة؟')) { select.value = current ? String(current.id) : ''; return; }
        choose(select.value);
        if (!current || !current.polygon) fitCity();
    });
    map.on('draw:created', event => { layers.clearLayers(); layers.addLayer(event.layer); dirty = true; controls(); });
    map.on('draw:edited', () => { dirty = true; });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });

    async function persist(polygon) {
        if (!current) return;
        save.disabled = true; clear.disabled = true; select.disabled = true;
        try {
            const response = await fetch(current.url, {method: 'PUT', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({polygon})});
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : 'تعذر حفظ الحدود.');
            if (!Object.prototype.hasOwnProperty.call(data, 'polygon')) throw new Error('يرجى تسجيل الدخول مجددًا.');
            current.polygon = data.polygon;
            select.selectedOptions[0].textContent = current.name + ' — ' + (data.polygon ? 'لها حدود' : 'بدون حدود');
            choose(select.value);
            feedback.className = 'alert alert-success border-0 rounded-3';
            feedback.textContent = data.message;
        } catch (e) {
            feedback.className = 'alert alert-danger border-0 rounded-3';
            feedback.textContent = e.message;
        } finally {
            save.disabled = false; clear.disabled = false; select.disabled = false;
        }
    }
    if (save) save.addEventListener('click', () => { const layer = layers.getLayers()[0]; if (!layer) { feedback.textContent = 'ارسم حدود المنطقة أولًا.'; return; } persist(layer.getLatLngs()[0].map(p => [p.lat, p.lng])); });
    if (clear) clear.addEventListener('click', () => { hourlyConfirm({title: 'مسح الحدود', text: 'مسح الحدود المحفوظة لهذه المنطقة؟', button: 'نعم، امسح'}).then(ok => { if (ok) persist(null); }); });

    const requested = new URLSearchParams(window.location.search).get('district');
    select.value = districts.some(d => String(d.id) === requested) ? requested : '';
    choose(select.value);
    const focusedDistrict = current && current.polygon;
    if (!focusedDistrict) fitCity();

    function setStatus(cls, icon, text) { status.className = 'ht-chip ' + cls; status.innerHTML = '<i class="fa ' + icon + '"></i> '; status.append(document.createTextNode(text)); }
    fetch(@json(route('admin.hourly-cities.boundary', $city)), {headers: {'Accept': 'application/json'}})
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            cityView = data;
            if (data.boundary) {
                L.geoJSON(data.boundary, {interactive: false, style: {color: '#0e7490', weight: 2, dashArray: '6 6', fillColor: '#06b6d4', fillOpacity: .06}}).addTo(cityLayer);
                cityLayer.bringToBack();
                setStatus('ht-chip-on', 'fa-check', 'حدود المدينة محددة');
            } else if (data.center) {
                L.circleMarker(data.center, {radius: 7, color: '#0e7490', fillColor: '#06b6d4', fillOpacity: .9, interactive: false}).addTo(map)
                    .bindTooltip(document.createTextNode('مركز المدينة'), {permanent: true, direction: 'top', offset: [0, -6]});
                setStatus('hd-chip-info', 'fa-location-dot', 'مركز المدينة — لا تتوفر حدود مرسومة لها في الخريطة');
            } else {
                setStatus('hd-chip-warn', 'fa-triangle-exclamation', 'لم يُعثر على المدينة؛ تحقق من اسمها');
            }
            if (!focusedDistrict && !dirty) fitCity();
        })
        .catch(() => setStatus('hd-chip-warn', 'fa-triangle-exclamation', 'تعذر تحميل حدود المدينة'));
})();
</script>
@endpush
