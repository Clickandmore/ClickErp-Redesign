@extends('admin.layout.indexs.index')

@section('page-title', $team->exists ? __('تعديل فريق') . ' — ' . $team->name : __('فريق جديد'))

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    <div class="container-fluid ht-page">
        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3"><i class="fa fa-circle-check ms-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger border-0 rounded-3"><i class="fa fa-circle-xmark ms-1"></i> {{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $team->exists ? route('admin.hourly-teams.update', $team) : route('admin.hourly-teams.store') }}" id="teamForm">
            @csrf
            @if ($team->exists) @method('PUT') @endif
            <input type="hidden" name="areas_json" id="areas_json" value="">

            <div class="row g-3">
                <div class="col-xl-4">
                    {{-- بيانات الفريق --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon"><i class="fa fa-people-group"></i></div>
                            <div>
                                <h5>{{ $team->exists ? $team->name : __('فريق جديد') }}</h5>
                                <p>{{ __('الفريق وحدة الحركة: ينطلق من إيواء واحد ويعود إليه') }}</p>
                            </div>
                        </div>
                        <div class="ht-body">
                            <div class="mb-3">
                                <label class="form-label" for="shelter_place_id">{{ __('ينبثق من مكان الإيواء') }} <span class="text-danger">*</span></label>
                                <select class="form-select @error('shelter_place_id') is-invalid @enderror" id="shelter_place_id" name="shelter_place_id" required>
                                    <option value="">{{ __('— اختر —') }}</option>
                                    @foreach ($shelters as $s)
                                        <option value="{{ $s->id }}" data-city="{{ $s->city }}" {{ (string) old('shelter_place_id', $team->shelter_place_id) === (string) $s->id ? 'selected' : '' }}>
                                            {{ $s->title ?? ('#' . $s->id) }}{{ $s->city ? ' — ' . $s->city : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shelter_place_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">{{ __('تغيير الإيواء يعيد تحميل قائمة العاملات المتاحة.') }}</div>
                            </div>

                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label" for="name">{{ __('اسم الفريق') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $team->name) }}" required maxlength="150">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="code">{{ __('الرمز') }}</label>
                                    <input type="text" dir="ltr" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $team->code) }}" maxlength="30" placeholder="RYD-1">
                                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-8">
                                    <label class="form-label" for="city_id">{{ __('المدينة') }}</label>
                                    <select class="form-select @error('city_id') is-invalid @enderror" id="city_id" name="city_id">
                                        <option value="">{{ __('— بلا —') }}</option>
                                        @foreach ($cities as $c)
                                            <option value="{{ $c->id }}" data-lat="{{ $c->latitude }}" data-lng="{{ $c->longitude }}" {{ (string) old('city_id', $team->city_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->name_ar ?: $c->name_en }}</option>
                                        @endforeach
                                    </select>
                                    @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="color">{{ __('اللون') }}</label>
                                    <input type="color" class="form-control form-control-color w-100 @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $team->color ?: '#0B6461') }}">
                                    @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-4">
                                    <label class="form-label" for="working_start">{{ __('بداية العمل') }}</label>
                                    <input type="time" class="form-control" id="working_start" name="working_start" value="{{ old('working_start', $team->working_start ? substr($team->working_start, 0, 5) : '') }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="working_end">{{ __('نهاية العمل') }}</label>
                                    <input type="time" class="form-control" id="working_end" name="working_end" value="{{ old('working_end', $team->working_end ? substr($team->working_end, 0, 5) : '') }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label" for="sort_order">{{ __('الترتيب') }}</label>
                                    <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $team->sort_order ?? 0) }}">
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label" for="description">{{ __('وصف') }}</label>
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="2000">{{ old('description', $team->description) }}</textarea>
                            </div>

                            <div class="ht-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $team->is_active ?? true) ? 'checked' : '' }}>
                                <div>
                                    <label for="is_active">{{ __('الفريق نشط') }}</label>
                                    <small>{{ __('الفريق المتوقف لا يُقترح للطلبات ولا يظهر على خريطة التشغيل.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- الأعضاء --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon" style="background:#fef3c7;color:#d97706"><i class="fa fa-person-dress"></i></div>
                            <div>
                                <h5>{{ __('عاملات الفريق') }}</h5>
                                <p>{{ __('من عاملات هذا الإيواء المؤهلات للتأجير بالساعة') }}</p>
                            </div>
                        </div>
                        <div class="ht-body">
                            <label class="form-label" for="maid_ids">{{ __('العاملات') }}</label>
                            <select class="form-select" id="maid_ids" name="maid_ids[]" multiple>
                                @foreach ($shelterMaids as $m)
                                    <option value="{{ $m->id }}" {{ in_array((int) $m->id, array_map('intval', old('maid_ids', $maidIds)), true) ? 'selected' : '' }}>
                                        {{ $m->name }} — {{ $m->passport_num ?: ('#' . $m->id) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('العاملة تنتمي لفريق أساسي واحد. اختيارها هنا ينقلها من فريقها السابق تلقائيًا.') }}</div>

                            <label class="form-label mt-3" for="leader_maid_id">{{ __('قائدة الفريق') }}</label>
                            <select class="form-select" id="leader_maid_id" name="leader_maid_id">
                                <option value="">{{ __('— بلا —') }}</option>
                                @foreach ($shelterMaids as $m)
                                    <option value="{{ $m->id }}" {{ (string) old('leader_maid_id', $leaderId) === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                @endforeach
                            </select>

                            <div class="ht-note ht-note-info mt-3">
                                <i class="fa fa-truck mt-1"></i>
                                <div>{{ __('السائقون يُربطون بالفريق من شاشة السائقين حين تُبنى في مرحلة السائق والمشوار.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    {{-- الخريطة والمناطق --}}
                    <div class="ht-card">
                        <div class="ht-head">
                            <div class="ht-head-icon" style="background:#dcfce7;color:#16a34a"><i class="fa fa-draw-polygon"></i></div>
                            <div>
                                <h5>{{ __('مناطق التغطية ونقطة الانطلاق') }}</h5>
                                <p>{{ __('ارسم مضلعًا لكل منطقة يغطيها الفريق. أداة الرسم في أعلى يسار الخريطة.') }}</p>
                            </div>
                            <div class="ht-actions">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" id="btnSetBase"><i class="fa fa-location-dot ms-1"></i> {{ __('تحديد نقطة الانطلاق') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" id="btnFit"><i class="fa fa-expand ms-1"></i> {{ __('ملاءمة العرض') }}</button>
                            </div>
                        </div>
                        <div class="ht-body">
                            <div id="teamMap" class="ht-map"></div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-4 col-6">
                                    <label class="form-label" for="base_lat">{{ __('نقطة الانطلاق — خط العرض') }}</label>
                                    <input type="text" dir="ltr" class="form-control form-control-sm" id="base_lat" name="base_lat" value="{{ old('base_lat', $team->base_lat) }}" placeholder="24.7136">
                                </div>
                                <div class="col-md-4 col-6">
                                    <label class="form-label" for="base_lng">{{ __('خط الطول') }}</label>
                                    <input type="text" dir="ltr" class="form-control form-control-sm" id="base_lng" name="base_lng" value="{{ old('base_lng', $team->base_lng) }}" placeholder="46.6753">
                                </div>
                                <div class="col-md-4 col-12 d-flex align-items-end">
                                    <div class="form-text">{{ __('افتراضيًا موقع الإيواء. تُستخدم لحساب مسافات المشاوير.') }}</div>
                                </div>
                            </div>

                            <h6 class="mt-4 mb-2 fw-bold">{{ __('المناطق المرسومة') }} <span class="badge bg-secondary" id="areaCount">0</span></h6>
                            <ul class="ht-areas" id="areaList"></ul>
                            <div class="ht-note ht-note-warn mt-3" id="noAreasNote">
                                <i class="fa fa-triangle-exclamation mt-1"></i>
                                <div>{{ __('فريق بلا مناطق لا يُقترح تلقائيًا لأي عنوان. ارسم منطقة واحدة على الأقل.') }}</div>
                            </div>
                        </div>
                        <div class="ht-foot">
                            <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="fa fa-floppy-disk ms-1"></i> {{ __('حفظ الفريق') }}</button>
                            <a href="{{ route('admin.hourly-teams.index') }}" class="btn btn-outline-secondary rounded-3">{{ __('رجوع') }}</a>
                            @if ($team->exists)
                                <a href="{{ route('admin.hourly-teams.map') }}?shelter_place_id={{ $team->shelter_place_id }}" class="btn btn-outline-primary rounded-3 ms-auto"><i class="fa fa-map ms-1"></i> {{ __('عرض على خريطة الفرق') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    @include('admin.hourly._map-provider')
    <script>
    (function () {
        var initialAreas = @json($areas);
        var shelterMaidsUrl = @json(route('admin.hourly-teams.shelter-maids'));
        var colorInput = document.getElementById('color');
        var DEFAULT_CENTER = [24.7136, 46.6753];

        var map = L.map('teamMap', { zoomControl: true }).setView(DEFAULT_CENTER, 11);
        HourlyMaps.attach(map);

        var drawn = new L.FeatureGroup().addTo(map);
        var areaMeta = {}; // leaflet layer id -> {id, name, is_active}

        function teamColor() { return colorInput.value || '#0B6461'; }

        var drawControl = new L.Control.Draw({
            position: 'topleft',
            draw: {
                polygon: { allowIntersection: false, showArea: false, shapeOptions: { color: teamColor(), weight: 2, fillOpacity: 0.18 } },
                polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false
            },
            edit: { featureGroup: drawn, remove: true }
        });
        map.addControl(drawControl);

        function addPolygon(points, meta) {
            var layer = L.polygon(points, { color: teamColor(), weight: 2, fillOpacity: 0.18 });
            drawn.addLayer(layer);
            areaMeta[L.stamp(layer)] = meta || { id: null, name: '', is_active: true };
            layer.bindTooltip(function () { return areaMeta[L.stamp(layer)].name || '{{ __('منطقة') }}'; }, { sticky: true });
            return layer;
        }

        initialAreas.forEach(function (a) {
            if (a.polygon && a.polygon.length >= 3) addPolygon(a.polygon, { id: a.id, name: a.name, is_active: a.is_active !== false });
        });

        map.on(L.Draw.Event.CREATED, function (e) {
            drawn.addLayer(e.layer);
            areaMeta[L.stamp(e.layer)] = { id: null, name: '', is_active: true };
            e.layer.setStyle({ color: teamColor() });
            renderList();
        });
        map.on(L.Draw.Event.EDITED, renderList);
        map.on(L.Draw.Event.DELETED, function (e) {
            e.layers.eachLayer(function (l) { delete areaMeta[L.stamp(l)]; });
            renderList();
        });
        colorInput.addEventListener('input', function () {
            drawn.eachLayer(function (l) { l.setStyle({ color: teamColor() }); });
            if (baseMarker) baseMarker.setStyle({ fillColor: teamColor() });
        });

        function layerPoints(layer) {
            var latlngs = layer.getLatLngs();
            if (latlngs.length && Array.isArray(latlngs[0])) latlngs = latlngs[0];
            return latlngs.map(function (p) { return [Number(p.lat.toFixed(7)), Number(p.lng.toFixed(7))]; });
        }

        function renderList() {
            var list = document.getElementById('areaList');
            list.innerHTML = '';
            var i = 0;
            drawn.eachLayer(function (layer) {
                i++;
                var id = L.stamp(layer);
                var meta = areaMeta[id] || (areaMeta[id] = { id: null, name: '', is_active: true });
                var li = document.createElement('li');
                li.innerHTML = '<span class="idx">' + i + '</span>' +
                    '<input type="text" class="form-control form-control-sm" maxlength="150" placeholder="{{ __('اسم المنطقة (الحي مثلاً)') }}">' +
                    '<span class="pts"></span>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary rounded-3" title="{{ __('تكبير') }}"><i class="fa fa-magnifying-glass"></i></button>';
                var input = li.querySelector('input');
                input.value = meta.name || '';
                input.addEventListener('input', function () { meta.name = input.value; });
                li.querySelector('.pts').textContent = layerPoints(layer).length + ' {{ __('نقطة') }}';
                li.querySelector('button').addEventListener('click', function () { map.fitBounds(layer.getBounds(), { padding: [20, 20] }); });
                li.addEventListener('mouseenter', function () { layer.setStyle({ weight: 4 }); });
                li.addEventListener('mouseleave', function () { layer.setStyle({ weight: 2 }); });
                list.appendChild(li);
            });
            document.getElementById('areaCount').textContent = i;
            document.getElementById('noAreasNote').hidden = i > 0;
        }

        // نقطة الانطلاق
        var baseMarker = null;
        var settingBase = false;
        var latEl = document.getElementById('base_lat'), lngEl = document.getElementById('base_lng');
        function placeBase(lat, lng) {
            if (baseMarker) { baseMarker.setLatLng([lat, lng]); }
            else {
                baseMarker = L.circleMarker([lat, lng], { radius: 9, color: '#0f172a', weight: 2, fillColor: teamColor(), fillOpacity: 0.95 }).addTo(map)
                    .bindTooltip('{{ __('نقطة الانطلاق') }}');
            }
            latEl.value = Number(lat).toFixed(7); lngEl.value = Number(lng).toFixed(7);
        }
        if (latEl.value && lngEl.value) placeBase(parseFloat(latEl.value), parseFloat(lngEl.value));
        document.getElementById('btnSetBase').addEventListener('click', function () {
            settingBase = true; map.getContainer().style.cursor = 'crosshair';
        });
        map.on('click', function (e) {
            if (!settingBase) return;
            settingBase = false; map.getContainer().style.cursor = '';
            placeBase(e.latlng.lat, e.latlng.lng);
        });
        [latEl, lngEl].forEach(function (el) {
            el.addEventListener('change', function () {
                var la = parseFloat(latEl.value), ln = parseFloat(lngEl.value);
                if (!isNaN(la) && !isNaN(ln)) placeBase(la, ln);
            });
        });

        function fitAll() {
            var b = null;
            if (drawn.getLayers().length) b = drawn.getBounds();
            if (baseMarker) b = b ? b.extend(baseMarker.getLatLng()) : L.latLngBounds([baseMarker.getLatLng()]);
            if (b && b.isValid()) { map.fitBounds(b, { padding: [24, 24] }); return; }
            var city = document.getElementById('city_id').selectedOptions[0];
            if (city && city.dataset.lat && city.dataset.lng) { map.setView([parseFloat(city.dataset.lat), parseFloat(city.dataset.lng)], 11); return; }
            map.setView(DEFAULT_CENTER, 11);
        }
        document.getElementById('btnFit').addEventListener('click', fitAll);
        document.getElementById('city_id').addEventListener('change', function () {
            if (!drawn.getLayers().length && !baseMarker) fitAll();
        });

        // العاملات حسب الإيواء
        var maidSel = document.getElementById('maid_ids'), leaderSel = document.getElementById('leader_maid_id');
        function initSelect2() {
            if (window.jQuery && jQuery.fn.select2) {
                jQuery(maidSel).select2({ width: '100%', placeholder: '{{ __('اختر العاملات') }}', dir: 'rtl' });
            }
        }
        initSelect2();
        document.getElementById('shelter_place_id').addEventListener('change', function () {
            var id = this.value;
            if (!id) return;
            fetch(shelterMaidsUrl + '?shelter_place_id=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    var keep = Array.from(maidSel.selectedOptions).map(function (o) { return o.value; });
                    maidSel.innerHTML = ''; leaderSel.innerHTML = '<option value="">{{ __('— بلا —') }}</option>';
                    rows.forEach(function (r) {
                        var o = new Option(r.text, r.id, false, keep.indexOf(String(r.id)) !== -1); maidSel.add(o);
                        leaderSel.add(new Option(r.text.split(' — ')[0], r.id));
                    });
                    if (window.jQuery && jQuery.fn.select2) jQuery(maidSel).trigger('change');
                })
                .catch(function () {});
        });

        document.getElementById('teamForm').addEventListener('submit', function () {
            var out = [];
            drawn.eachLayer(function (layer) {
                var meta = areaMeta[L.stamp(layer)] || {};
                out.push({ id: meta.id || null, name: meta.name || '', is_active: meta.is_active !== false, polygon: layerPoints(layer) });
            });
            document.getElementById('areas_json').value = JSON.stringify(out);
        });

        renderList();
        setTimeout(function () { map.invalidateSize(); fitAll(); }, 250);
    })();
    </script>
@endpush
