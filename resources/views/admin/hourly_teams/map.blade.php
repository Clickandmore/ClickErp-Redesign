@extends('admin.layout.indexs.index')

@section('page-title', __('خريطة الفرق'))

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    @include('admin.hourly_teams._style')
@endsection

@section('content')
    <div class="container-fluid ht-page">
        <div class="row g-3">
            <div class="col-xl-3">
                <div class="ht-card">
                    <div class="ht-head">
                        <div class="ht-head-icon"><i class="fa fa-map"></i></div>
                        <div>
                            <h5>{{ __('خريطة الفرق') }}</h5>
                            <p>{{ __('مناطق التغطية ونقاط الانطلاق لكل فريق') }}</p>
                        </div>
                    </div>
                    <div class="ht-body">
                        <form method="GET" action="{{ route('admin.hourly-teams.map') }}" class="d-grid gap-2">
                            <div>
                                <label class="form-label" for="m_city">{{ __('المدينة') }}</label>
                                <select class="form-select" id="m_city" name="city_id" data-placeholder="{{ __('ابحث عن مدينة…') }}" onchange="this.form.submit()">
                                    <option value="">{{ __('الكل') }}</option>
                                    @foreach ($cities as $c)
                                        <option value="{{ $c->id }}" data-lat="{{ $c->latitude }}" data-lng="{{ $c->longitude }}" {{ (string) request('city_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name_ar ?: $c->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="m_shelter">{{ __('مكان الإيواء') }}</label>
                                <select class="form-select" id="m_shelter" name="shelter_place_id" onchange="this.form.submit()">
                                    <option value="">{{ __('الكل') }}</option>
                                    @foreach ($shelters as $s)
                                        <option value="{{ $s->id }}" {{ (string) request('shelter_place_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->title ?? ('#' . $s->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="m_inactive" name="include_inactive" value="1" {{ request()->boolean('include_inactive') ? 'checked' : '' }} onchange="this.form.submit()">
                                <label class="form-check-label" for="m_inactive">{{ __('إظهار الفرق المتوقفة') }}</label>
                            </div>
                        </form>

                        <h6 class="fw-bold mt-4 mb-2">{{ __('الفرق') }} <span class="badge bg-secondary">{{ count($geo['teams']) }}</span></h6>
                        @if (empty($geo['teams']))
                            <div class="ht-note ht-note-info"><i class="fa fa-circle-info mt-1"></i><div>{{ __('لا فرق مطابقة للفلاتر.') }}</div></div>
                        @else
                            <ul class="ht-legend" id="legend">
                                @foreach ($geo['teams'] as $t)
                                    <li data-team="{{ $t['id'] }}">
                                        <span class="ht-dot" style="background: {{ $t['color'] }}"></span>
                                        <span>{{ $t['name'] }}</span>
                                        <span class="m">{{ count($t['areas']) }} {{ __('منطقة') }} · {{ $t['maids_count'] }} {{ __('عاملة') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="ht-foot">
                        <a href="{{ route('admin.hourly-teams.index') }}" class="btn btn-outline-secondary rounded-3"><i class="fa fa-list ms-1"></i> {{ __('قائمة الفرق') }}</a>
                        @if (@AdminGuardHasPerm(\App\PermKeys::HOURLY_TEAMS_CREATE))
                            <a href="{{ route('admin.hourly-teams.create') }}" class="btn btn-primary rounded-3"><i class="fa fa-plus ms-1"></i> {{ __('فريق جديد') }}</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-9">
                <div class="ht-card">
                    <div class="ht-body p-2">
                        <div id="teamsMap" class="ht-map ht-map-tall"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    @include('admin.hourly._map-provider')
    <script>
    (function () {
        var geo = @json($geo);
        var map = L.map('teamsMap').setView([24.7136, 46.6753], 11);
        HourlyMaps.attach(map);

        if (window.jQuery && jQuery.fn.select2) {
            jQuery('#m_city').select2({
                width: '100%',
                dir: 'rtl',
                minimumResultsForSearch: 0,
                language: { noResults: function () { return @json(__('لا توجد مدن مطابقة')); } }
            });
        }

        var layersByTeam = {};
        var all = L.featureGroup().addTo(map);

        geo.teams.forEach(function (t) {
            var group = L.featureGroup();
            var popupHtml = '<div style="min-width:180px"><strong>' + esc(t.name) + '</strong>' + (t.code ? ' <span dir="ltr" style="color:#64748b">(' + esc(t.code) + ')</span>' : '') +
                '<div style="font-size:12px;color:#64748b;margin-top:4px">' + esc(t.shelter || '') + (t.city ? ' · ' + esc(t.city) : '') + '</div>' +
                '<div style="font-size:12px;margin-top:4px">' + t.maids_count + ' {{ __('عاملة') }} · ' + t.areas.length + ' {{ __('منطقة') }}' + (t.is_active ? '' : ' · <span style="color:#b45309">{{ __('متوقف') }}</span>') + '</div>' +
                '<a href="' + t.edit_url + '" style="font-size:12px;display:inline-block;margin-top:6px">{{ __('تعديل الفريق') }}</a></div>';

            t.areas.forEach(function (a) {
                if (!a.polygon || a.polygon.length < 3) return;
                var poly = L.polygon(a.polygon, { color: t.color, weight: 2, fillOpacity: t.is_active ? 0.2 : 0.06, dashArray: t.is_active ? null : '6 4' });
                poly.bindTooltip(esc(t.name) + ' — ' + esc(a.name), { sticky: true });
                poly.bindPopup(popupHtml);
                group.addLayer(poly);
            });
            if (t.base) {
                var m = L.circleMarker(t.base, { radius: 9, color: '#0f172a', weight: 2, fillColor: t.color, fillOpacity: 0.95 });
                m.bindTooltip('{{ __('انطلاق') }}: ' + esc(t.name));
                m.bindPopup(popupHtml);
                group.addLayer(m);
            }
            layersByTeam[t.id] = group;
            all.addLayer(group);
        });

        function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }

        var legend = document.getElementById('legend');
        if (legend) {
            legend.querySelectorAll('li').forEach(function (li) {
                li.addEventListener('click', function () {
                    var g = layersByTeam[li.dataset.team];
                    if (g && g.getLayers().length) { map.fitBounds(g.getBounds(), { padding: [24, 24] }); g.eachLayer(function (l) { if (l.openPopup) l.openPopup(); return false; }); }
                });
                li.addEventListener('mouseenter', function () { var g = layersByTeam[li.dataset.team]; if (g) g.eachLayer(function (l) { if (l.setStyle && l.getBounds) l.setStyle({ weight: 4 }); }); });
                li.addEventListener('mouseleave', function () { var g = layersByTeam[li.dataset.team]; if (g) g.eachLayer(function (l) { if (l.setStyle && l.getBounds) l.setStyle({ weight: 2 }); }); });
            });
        }

        setTimeout(function () {
            map.invalidateSize();
            if (all.getLayers().length && all.getBounds().isValid()) { map.fitBounds(all.getBounds(), { padding: [24, 24] }); return; }
            var city = document.getElementById('m_city').selectedOptions[0];
            if (city && city.dataset.lat && city.dataset.lng) map.setView([parseFloat(city.dataset.lat), parseFloat(city.dataset.lng)], 11);
        }, 250);
    })();
    </script>
@endpush
