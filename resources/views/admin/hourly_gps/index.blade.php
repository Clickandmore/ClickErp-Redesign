{{-- تتبع GPS: صفحة مستقلة بملء الشاشة بلا هيدر ولا قائمة جانبية؛ الخريطة خلفية والفلاتر والقائمة عائمة فوقها. --}}
@php
    $rtl = app()->getLocale() === 'ar';
    $g = fn (string $key, array $replace = []) => __('hourly_routing.gps.'.$key, $replace);
    $colors = ['maid' => '#8b5cf6', 'driver' => '#2563eb', 'customer' => '#10b981'];
    $i18n = __('hourly_routing.gps.js') + ['types' => $types];
    $payload = $i18n + ['colors' => $colors, 'fit_all' => $g('fit_all')];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $g('title') }}</title>
    <link rel="icon" href="{{ asset('admin/assets/img/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.min.css">
    <style>
        :root {
            --ink: #0f172a; --muted: #64748b; --line: rgba(15, 23, 42, .09); --glass: rgba(255, 255, 255, .9); --brand: #2563eb; --brand-soft: #eff4ff;
            --maid: {{ $colors['maid'] }}; --driver: {{ $colors['driver'] }}; --customer: {{ $colors['customer'] }};
            --shadow: 0 18px 50px rgba(15, 23, 42, .16), 0 2px 6px rgba(15, 23, 42, .06); --gap: 16px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; overflow: hidden; background: #e8edf3; color: var(--ink); font-family: 'IBMPlexSansArabic', system-ui, -apple-system, 'Segoe UI', sans-serif; font-size: 14px; }
        button, input, select { font: inherit; color: inherit; }
        [hidden] { display: none !important; }
        #map { position: fixed; inset: 0; z-index: 0; }
        .leaflet-container { font: inherit; background: #e8edf3; }

        .glass { background: var(--glass); backdrop-filter: blur(14px) saturate(1.4); -webkit-backdrop-filter: blur(14px) saturate(1.4); border: 1px solid rgba(255, 255, 255, .7); box-shadow: var(--shadow); }
        .icon-btn { width: 38px; height: 38px; flex: 0 0 38px; display: inline-grid; place-items: center; border-radius: 12px; border: 1px solid var(--line); background: #fff; cursor: pointer; color: var(--ink); text-decoration: none; transition: background .15s, border-color .15s, transform .15s; }
        .icon-btn:hover { border-color: #c7d2fe; background: var(--brand-soft); color: var(--brand); }
        .icon-btn.is-spin i { animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ——— لوحة الفلاتر ——— */
        .panel { position: fixed; top: var(--gap); inset-inline: var(--gap); margin-inline: auto; max-width: 1040px; z-index: 1000; border-radius: 22px; padding: 14px 16px; }
        .panel-head { display: flex; align-items: center; gap: 12px; }
        .brand { width: 44px; height: 44px; flex: 0 0 44px; border-radius: 14px; display: grid; place-items: center; color: #fff; font-size: 18px; background: linear-gradient(135deg, #1d4ed8, #0ea5e9); box-shadow: 0 8px 18px rgba(37, 99, 235, .35); }
        .title { min-width: 0; flex: 1; }
        .title h1 { margin: 0; font-size: 17px; font-weight: 800; letter-spacing: -.2px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .title p { margin: 2px 0 0; color: var(--muted); font-size: 12.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .live { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: #dcfce7; color: #166534; }
        .live::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 0 rgba(34, 197, 94, .6); animation: pulse 1.8s infinite; }
        .live.is-archive { background: #f1f5f9; color: var(--muted); }
        .live.is-archive::before { background: #94a3b8; animation: none; }
        @keyframes pulse { 70% { box-shadow: 0 0 0 7px rgba(34, 197, 94, 0); } 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); } }
        .head-actions { display: flex; gap: 8px; }
        .panel.is-min .filters { display: none; }
        .panel.is-min #toggle i { transform: rotate(180deg); }
        #toggle i { transition: transform .2s; }

        .filters { display: grid; grid-template-columns: minmax(180px, 1.1fr) minmax(150px, .8fr) minmax(260px, 1.6fr) minmax(220px, 1.2fr); gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--line); }
        .field > span { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .3px; }
        .control { height: 42px; width: 100%; border-radius: 12px; border: 1px solid var(--line); background: #fff; padding: 0 12px; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .control:hover, .control:focus-within, .control.is-open { border-color: #93c5fd; box-shadow: 0 0 0 4px rgba(59, 130, 246, .12); }
        .control input[type=date], .control .df-date-input { border: 0; outline: 0; background: none; width: 100%; height: 100%; cursor: pointer; }
        .control .caret { margin-inline-start: auto; color: var(--muted); font-size: 11px; }
        .control .value { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; }

        /* قائمة المدن المنسدلة */
        .drop { position: relative; }
        .drop-menu { position: absolute; top: calc(100% + 8px); inset-inline-start: 0; width: max(100%, 260px); z-index: 20; border-radius: 16px; padding: 10px; background: #fff; box-shadow: var(--shadow); border: 1px solid var(--line); }
        .drop-search { display: flex; align-items: center; gap: 8px; height: 38px; padding: 0 10px; border-radius: 10px; background: #f1f5f9; margin-bottom: 8px; }
        .drop-search input { border: 0; outline: 0; background: none; width: 100%; }
        .drop-list { max-height: 240px; overflow: auto; display: grid; gap: 2px; }
        .drop-list label { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; cursor: pointer; }
        .drop-list label:hover { background: #f8fafc; }
        .drop-list input { accent-color: var(--brand); width: 16px; height: 16px; }
        .drop-foot { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--line); font-size: 12.5px; color: var(--muted); }
        .link { border: 0; background: none; color: var(--brand); font-weight: 700; cursor: pointer; padding: 4px 6px; border-radius: 8px; }
        .link:hover { background: var(--brand-soft); }

        /* أزرار الاختيار: الأنواع والحالة */
        .chips { display: flex; gap: 6px; flex-wrap: wrap; }
        .chip { position: relative; }
        .chip input { position: absolute; opacity: 0; pointer-events: none; }
        .chip span { height: 42px; padding: 0 14px; border-radius: 12px; border: 1px solid var(--line); background: #fff; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all .15s; user-select: none; }
        .chip span i.dot { width: 10px; height: 10px; border-radius: 50%; background: var(--c); box-shadow: 0 0 0 3px color-mix(in srgb, var(--c) 18%, transparent); }
        .chip input:checked + span { border-color: var(--c, var(--brand)); background: color-mix(in srgb, var(--c, var(--brand)) 10%, #fff); color: color-mix(in srgb, var(--c, var(--brand)) 80%, #000); }
        .chip input:focus-visible + span { box-shadow: 0 0 0 4px rgba(59, 130, 246, .2); }
        .seg { display: flex; padding: 4px; gap: 4px; height: 42px; border-radius: 12px; background: #f1f5f9; border: 1px solid var(--line); }
        .seg label { flex: 1; position: relative; }
        .seg input { position: absolute; opacity: 0; }
        .seg span { height: 100%; display: grid; place-items: center; border-radius: 9px; font-weight: 700; font-size: 13px; color: var(--muted); cursor: pointer; transition: all .15s; white-space: nowrap; padding: 0 8px; }
        .seg input:checked + span { background: #fff; color: var(--ink); box-shadow: 0 2px 6px rgba(15, 23, 42, .1); }

        /* ——— شريط العدادات ——— */
        .stats { position: fixed; bottom: var(--gap); inset-inline-start: 50%; transform: translateX(-50%); z-index: 1000; border-radius: 99px; padding: 6px; display: flex; gap: 4px; max-width: calc(100vw - 2 * var(--gap)); overflow-x: auto; }
        [dir=rtl] .stats { transform: translateX(50%); }
        .stat { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 99px; font-weight: 700; white-space: nowrap; }
        .stat b { font-size: 16px; font-variant-numeric: tabular-nums; }
        .stat i.dot { width: 10px; height: 10px; border-radius: 50%; background: var(--c); }
        .stat.is-active { background: #dcfce7; color: #166534; }
        .stat small { color: var(--muted); font-weight: 600; }

        /* ——— قائمة الأشخاص ——— */
        .people-btn { position: fixed; bottom: var(--gap); inset-inline-end: var(--gap); z-index: 1000; height: 48px; padding: 0 18px; border-radius: 16px; display: inline-flex; align-items: center; gap: 10px; font-weight: 800; cursor: pointer; border: 0; }
        .people-btn .count { min-width: 26px; height: 26px; border-radius: 99px; background: var(--brand); color: #fff; display: grid; place-items: center; font-size: 12px; padding: 0 6px; }
        .people { position: fixed; z-index: 1001; inset-inline-end: var(--gap); bottom: calc(var(--gap) + 60px); width: 340px; max-height: min(60vh, 520px); border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; }
        .people-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 12px 8px 14px; }
        .people-head h2 { margin: 0; font-size: 15px; font-weight: 800; }
        .people .drop-search { margin: 0 12px 8px; background: #fff; border: 1px solid var(--line); }
        .people-list { overflow: auto; padding: 0 8px 8px; display: grid; gap: 4px; }
        .person { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 12px; cursor: pointer; border: 1px solid transparent; background: rgba(255, 255, 255, .6); text-align: start; width: 100%; }
        .person:hover { border-color: #bfdbfe; background: #fff; }
        .person .avatar { width: 36px; height: 36px; flex: 0 0 36px; border-radius: 12px; display: grid; place-items: center; color: #fff; font-weight: 800; font-size: 13px; background: var(--c); position: relative; }
        .person .avatar.is-on::after { content: ''; position: absolute; bottom: -2px; inset-inline-end: -2px; width: 11px; height: 11px; border-radius: 50%; background: #22c55e; border: 2px solid #fff; }
        .person .who { min-width: 0; flex: 1; }
        .person .who b { display: block; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .person .who small { color: var(--muted); font-size: 12px; }
        .person .when { font-size: 11.5px; color: var(--muted); white-space: nowrap; }
        .people-empty { padding: 24px 12px; text-align: center; color: var(--muted); }

        /* ——— الإشعارات والعلامات ——— */
        .toast { position: fixed; top: 50%; inset-inline-start: 50%; transform: translate(-50%, -50%); z-index: 999; border-radius: 16px; padding: 14px 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; pointer-events: none; }
        [dir=rtl] .toast { transform: translate(50%, -50%); }
        /* علامة العاملة/السائق: حلقة خضراء للمتصل ورمادية لغير المتصل، وسهم باتجاه الحركة */
        .pin { position: relative; width: 26px; height: 26px; }
        .pin .core { position: absolute; inset: 4px; border-radius: 50%; background: var(--c); box-shadow: 0 0 0 2px #fff, 0 3px 10px rgba(0, 0, 0, .35); }
        .pin .ring { position: absolute; inset: 0; border-radius: 50%; border: 3px solid #9ca3af; }
        .pin.is-on .ring { border-color: #22c55e; }
        .pin.is-on::before { content: ''; position: absolute; inset: -7px; border-radius: 50%; background: #22c55e; opacity: .3; animation: ring 2s ease-out infinite; }
        .pin.is-off .core { filter: grayscale(.6); opacity: .75; }
        .pin .arrow { position: absolute; inset: -13px; pointer-events: none; }
        .pin .arrow::before { content: ''; position: absolute; top: 0; left: 50%; margin-left: -6px; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 10px solid var(--c); filter: drop-shadow(0 1px 1px rgba(0,0,0,.35)); }
        @keyframes ring { 0% { transform: scale(.5); opacity: .45; } 100% { transform: scale(1.3); opacity: 0; } }
        /* العميل: بيت ملوّن إن كانت له زيارة في اليوم ورمادي إن لا */
        .house { width: 30px; height: 30px; border-radius: 10px; display: grid; place-items: center; color: #fff; font-size: 14px; background: var(--customer); box-shadow: 0 0 0 2px #fff, 0 3px 10px rgba(0, 0, 0, .3); }
        .house.is-off { background: #9ca3af; }
        .legend { position: fixed; top: calc(var(--gap) + 86px); inset-inline-end: var(--gap); z-index: 900; border-radius: 16px; padding: 10px 12px; display: grid; gap: 6px; font-size: 12px; font-weight: 600; }
        .legend span { display: flex; align-items: center; gap: 8px; }
        .legend .sw { width: 14px; height: 14px; border-radius: 50%; border: 3px solid; flex: 0 0 14px; }
        .legend .sw.is-house { border-radius: 4px; border: 0; }
        .panel:not(.is-min) ~ .legend { display: none; }
        .pop .now { margin: 8px 0; display: grid; gap: 6px; }
        .pop .now div { border: 1px solid var(--line); border-radius: 10px; padding: 6px 8px; font-size: 12px; }
        .pop .now b { display: block; font-size: 11px; color: var(--muted); }
        .pop .now a { color: var(--brand); font-weight: 700; text-decoration: none; }
        .pop .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; }
        .pop .actions button, .pop .actions a { height: 34px; border-radius: 10px; border: 1px solid var(--line); background: #fff; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; color: var(--ink); text-decoration: none; }
        .pop .actions a.go { background: var(--brand); color: #fff !important; border-color: var(--brand); }
        .pop .actions .full { grid-column: 1 / -1; }
        .marker-cluster-small, .marker-cluster-medium, .marker-cluster-large { background: rgba(37, 99, 235, .18) !important; }
        .marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div { background: #2563eb !important; color: #fff !important; font-weight: 800; font: inherit; }
        .leaflet-popup-content-wrapper { border-radius: 16px; box-shadow: var(--shadow); }
        .leaflet-popup-content { margin: 14px 16px; min-width: 220px; }
        .pop h3 { margin: 0 0 2px; font-size: 15px; }
        .pop .tag { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: var(--c); }
        .pop .tag::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
        .pop dl { margin: 10px 0; display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; font-size: 12.5px; }
        .pop dt { color: var(--muted); }
        .pop dd { margin: 0; font-weight: 600; }
        .pop .state { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 11.5px; font-weight: 800; }
        .pop .state.is-on { background: #dcfce7; color: #166534; }
        .pop .state.is-off { background: #fef3c7; color: #92400e; }
        .pop a.go { display: flex; align-items: center; justify-content: center; gap: 6px; height: 36px; border-radius: 10px; background: var(--brand); color: #fff !important; text-decoration: none; font-weight: 700; }
        .leaflet-control-zoom { border: 0 !important; box-shadow: var(--shadow) !important; border-radius: 14px !important; overflow: hidden; }
        .leaflet-control-zoom a { width: 38px !important; height: 38px !important; line-height: 38px !important; }
        .fit { margin-bottom: 8px !important; }

        @media (max-width: 1100px) { .filters { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 640px) {
            :root { --gap: 10px; }
            .panel { padding: 12px; border-radius: 18px; }
            .title p { display: none; }
            .filters { grid-template-columns: 1fr; max-height: 55vh; overflow: auto; }
            .people { inset-inline: var(--gap); width: auto; }
            .stats { bottom: calc(var(--gap) + 58px); }
            .stat { padding: 6px 10px; }
        }
    </style>
</head>
<body>
<div id="map" aria-label="{{ $g('title') }}"></div>

<form class="panel glass" id="gps" data-url="{{ route('admin.hourly-gps.data') }}" data-status="{{ route('admin.hourly-gps.status') }}" data-trail="{{ route('admin.hourly-gps.trail') }}" data-refresh="{{ $refreshSeconds }}" data-admin="{{ $adminId }}" onsubmit="return false">
    <div class="panel-head">
        <span class="brand"><i class="fa fa-location-crosshairs"></i></span>
        <div class="title">
            <h1>{{ $g('title') }} <span class="live" id="live">{{ $i18n['live'] }}</span></h1>
            <p id="subtitle">{{ $g('subtitle') }}</p>
        </div>
        <div class="head-actions">
            <button type="button" class="icon-btn" id="refresh" title="{{ $g('refresh') }}" aria-label="{{ $g('refresh') }}"><i class="fa fa-rotate"></i></button>
            <button type="button" class="icon-btn" id="toggle" title="{{ $g('collapse') }}" aria-label="{{ $g('collapse') }}" aria-expanded="true"><i class="fa fa-chevron-up"></i></button>
            <a class="icon-btn" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.hourly-routing.index') }}" title="{{ $g('back') }}" aria-label="{{ $g('back') }}"><i class="fa {{ $rtl ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i></a>
        </div>
    </div>

    <div class="filters">
        <div class="field">
            <span><i class="fa fa-city"></i> {{ $g('city') }}</span>
            <div class="drop" id="cities">
                <button type="button" class="control" id="cities-btn" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-location-dot" style="color:var(--muted)"></i><span class="value" id="cities-value">{{ $g('all_cities') }}</span><i class="fa fa-chevron-down caret"></i>
                </button>
                <div class="drop-menu" id="cities-menu" hidden>
                    <label class="drop-search"><i class="fa fa-magnifying-glass" style="color:var(--muted)"></i><input type="search" id="cities-search" placeholder="{{ $g('search_city') }}" autocomplete="off"></label>
                    <div class="drop-list">
                        @foreach($cities as $city)
                            <label data-name="{{ mb_strtolower($city->name_ar.' '.$city->name_en) }}"><input type="checkbox" name="city_ids[]" value="{{ $city->id }}" checked data-lat="{{ $city->latitude }}" data-lng="{{ $city->longitude }}"> {{ $city->name }}</label>
                        @endforeach
                    </div>
                    <div class="drop-foot"><span id="cities-count"></span><button type="button" class="link" id="cities-clear">{{ $g('select_all') }}</button></div>
                </div>
            </div>
        </div>
        <div class="field">
            <span><i class="fa fa-calendar-day"></i> {{ $g('date') }}</span>
            <label class="control"><input type="date" name="date" value="{{ today()->toDateString() }}" aria-label="{{ $g('date') }}"></label>
        </div>
        <div class="field">
            <span><i class="fa fa-users"></i> {{ $g('user_type') }}</span>
            <div class="chips">
                @foreach($types as $key => $label)
                    <label class="chip" style="--c: {{ $colors[$key] }}"><input type="checkbox" name="types[]" value="{{ $key }}" {{ $key !== 'customer' ? 'checked' : '' }}><span><i class="dot"></i>{{ $label }}</span></label>
                @endforeach
            </div>
        </div>
        <div class="field">
            <span><i class="fa fa-signal"></i> {{ $g('status') }}</span>
            <div class="seg">
                @foreach(['all', 'active', 'inactive'] as $status)
                    <label><input type="radio" name="status" value="{{ $status }}" {{ $status === 'all' ? 'checked' : '' }}><span>{{ $g($status) }}</span></label>
                @endforeach
            </div>
        </div>
    </div>
</form>

<div class="stats glass" id="stats" aria-live="polite"></div>

<button type="button" class="people-btn glass" id="people-btn" aria-expanded="false"><i class="fa fa-list-ul"></i> {{ $g('people') }} <span class="count" id="people-count">0</span></button>
<section class="people glass" id="people" hidden>
    <div class="people-head">
        <h2>{{ $g('people') }}</h2>
        <button type="button" class="icon-btn" id="people-close" aria-label="{{ $g('close') }}"><i class="fa fa-xmark"></i></button>
    </div>
    <label class="drop-search"><i class="fa fa-magnifying-glass" style="color:var(--muted)"></i><input type="search" id="people-search" placeholder="{{ $g('search_person') }}" autocomplete="off"></label>
    <div class="people-list" id="people-list"></div>
</section>

{{-- دليل الألوان؛ يظهر حين تُطوى الفلاتر حتى لا يزاحمها --}}
<aside class="legend glass" aria-label="{{ $g('title') }}">
    <span><i class="sw" style="border-color:#22c55e"></i>{{ $g('legend.online', ['m' => $activeMinutes]) }}</span>
    <span><i class="sw" style="border-color:#9ca3af"></i>{{ $g('legend.offline') }}</span>
    <span><i class="sw is-house" style="background:var(--customer)"></i>{{ $g('legend.house_on') }}</span>
    <span><i class="sw is-house" style="background:#9ca3af"></i>{{ $g('legend.house_off') }}</span>
</aside>

<div class="toast glass" id="toast" hidden></div>

<script type="application/json" id="gps-i18n">@json($payload)</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js"></script>
@include('admin.hourly._map-provider')
<script type="application/json" id="gps-socket-config">@json($socketConfig)</script>
@if($socketConfig)
<script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
@endif
<script src="{{ asset('admin/assets/js/hourly-gps-socket.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-gps-socket.js')) }}"></script>
<script src="{{ asset('admin/assets/js/hourly-gps.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-gps.js')) }}"></script>
</body>
</html>
