@extends('admin.layout.indexs.index')
@section('page-title', __('hourly_routing.title'))
@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@include('admin.hourly._modal-theme')
<style>
    /* ——— إدارة المسار: بطاقة لكل سائق ومهامه (توصيل عند بداية الزيارة، استلام عند نهايتها) ——— */
    .rt { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc;
          --delivery: #f0a020; --pickup: #17996f; --base: #2563eb; --transit: #0891b2; color: var(--ink); }
    .rt [hidden] { display: none !important; }
    .rt-box { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); margin-bottom: 1rem; }

    .rt-bar { padding: .9rem 1.1rem; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .rt-title { display: flex; align-items: center; gap: .5rem; font-size: 1.15rem; font-weight: 800; margin: 0; margin-inline-end: .6rem; color: var(--brand-deep); }
    .rt-date { display: flex; align-items: center; gap: .35rem; }
    .rt-date a, .rt-btn { height: 40px; min-width: 40px; padding: 0 .8rem; border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--ink); display: inline-flex; align-items: center; justify-content: center; gap: .4rem; font-weight: 700; font-size: .84rem; text-decoration: none; white-space: nowrap; }
    .rt-date a:hover, .rt-btn:hover { border-color: var(--brand); color: var(--brand-ink); }
    .rt-date label { position: relative; height: 40px; padding: 0 .8rem; border: 1px solid var(--line); border-radius: 10px; display: inline-flex; align-items: center; gap: .45rem; font-weight: 800; font-size: .88rem; cursor: pointer; margin: 0; }
    .rt-date label input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .rt-filters { display: flex; gap: .5rem; flex-wrap: wrap; margin-inline-start: auto; align-items: center; }
    .rt-filters .rt-select { position: relative; }
    .rt-filters .rt-select i { position: absolute; inset-inline-start: .7rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: .8rem; pointer-events: none; }
    .rt-filters .form-select { height: 40px; min-width: 160px; border-radius: 10px; border-color: #dfe7ef; font-size: .84rem; padding-inline-start: 2rem; }
    .rt-filters .form-select.is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .rt-export { background: #17996f; border-color: #17996f; color: #fff; }
    .rt-export:hover { background: #0f7a5a; color: #fff; border-color: #0f7a5a; }

    .rt-controls { padding: .9rem 1.1rem; display: grid; gap: .75rem; }
    .rt-row { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; }
    .rt-seg { display: inline-flex; gap: .35rem; flex-wrap: wrap; }
    .rt-seg a, .rt-seg button { height: 36px; padding: 0 .85rem; border: 1px solid var(--line); border-radius: 9px; background: #fff; color: var(--ink); display: inline-flex; align-items: center; gap: .4rem; font-weight: 700; font-size: .8rem; text-decoration: none; }
    .rt-seg .is-on { background: var(--brand-ink); border-color: var(--brand-ink); color: #fff; }
    .rt-seg.is-view .is-on { background: #6b63d6; border-color: #6b63d6; }
    .rt-pills { display: flex; gap: .45rem; flex-wrap: wrap; }
    .rt-pill { display: inline-flex; align-items: center; gap: .4rem; border-radius: 99px; padding: .35rem .85rem; font-weight: 700; font-size: .8rem; }
    .rt-pill b { font-variant-numeric: tabular-nums; }
    .rt-pill.is-routes { background: #e7f1fd; color: #1b5fa8; }
    .rt-pill.is-unassigned { background: #fdf0dc; color: #9a5b00; }
    .rt-pill.is-cancelled { background: #fde7e7; color: #b42318; }
    .rt-pill.is-nodriver { background: #efeafd; color: #5b3fc4; }
    .rt-note { font-size: .78rem; color: var(--muted); display: flex; align-items: center; gap: .35rem; }

    .rt-card { overflow: hidden; }
    .rt-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: .9rem 1.1rem; background: var(--soft); border-bottom: 1px solid var(--line); flex-wrap: wrap; }
    .rt-driver { display: grid; gap: .2rem; font-size: .84rem; }
    .rt-driver .rt-count { font-weight: 800; color: var(--muted); display: inline-flex; align-items: center; gap: .35rem; font-variant-numeric: tabular-nums; }
    .rt-driver span b { font-weight: 700; }
    .rt-driver .rt-name { color: #0f7a5a; font-weight: 800; }
    .rt-driver .rt-team { color: #0f7a5a; font-weight: 700; }
    .rt-card-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .rt-show { background: #4f46e5; border-color: #4f46e5; color: #fff; }
    .rt-show:hover { background: #4338ca; color: #fff; }
    .rt-move { background: #f26d7d; border-color: #f26d7d; color: #fff; }
    .rt-move:hover { background: #e5485d; color: #fff; }
    .rt-state { display: inline-flex; align-items: center; gap: .35rem; border-radius: 99px; padding: .4rem 1rem; font-weight: 800; font-size: .8rem; }
    .rt-state.is-pending { background: #fbbf24; color: #3b2a00; }
    .rt-state.is-moving { background: #dbeafe; color: #1e40af; }
    .rt-state.is-done { background: #dcfce7; color: #166534; }

    .rt-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .83rem; }
    .rt-table th { background: #fff; color: var(--muted); font-weight: 700; font-size: .76rem; padding: .6rem .8rem; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .rt-table td { padding: .65rem .8rem; border-bottom: 1px solid var(--line); vertical-align: middle; }
    .rt-table tr:last-child td { border-bottom: 0; }
    .rt-band td { background: #f7f9fc; color: var(--brand-deep); font-weight: 800; font-size: .78rem; padding: .4rem .8rem; }
    .rt-time { font-weight: 800; font-variant-numeric: tabular-nums; direction: ltr; white-space: nowrap; }
    .rt-kind { display: inline-flex; align-items: center; gap: .3rem; border-radius: 7px; padding: .15rem .55rem; font-weight: 800; font-size: .74rem; white-space: nowrap; }
    .rt-kind.is-delivery { background: #fde68a; color: #7a4a00; }
    .rt-kind.is-pickup { background: #16a34a; color: #fff; }
    .rt-task-main { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
    .rt-task-main b { font-weight: 800; }
    .rt-sub { color: var(--muted); font-size: .76rem; margin-top: .2rem; }
    .rt-sub .rt-customer { color: #0f7a5a; font-weight: 700; }
    .rt-status { display: inline-flex; align-items: center; gap: .3rem; border-radius: 99px; padding: .2rem .7rem; font-weight: 700; font-size: .74rem; white-space: nowrap; }
    .rt-status.is-pending { background: #fef3c7; color: #92400e; }
    .rt-status.is-moving, .rt-status.is-ready { background: #dbeafe; color: #1e40af; }
    .rt-status.is-done { background: #dcfce7; color: #166534; }
    .rt-status.is-failed { background: #fee2e2; color: #991b1b; }
    .rt-km { font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rt-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-grid; place-items: center; border: 0; color: #fff; text-decoration: none; }
    .rt-icon.is-map { background: #2794eb; }
    .rt-icon.is-transfer { background: #f26d7d; }
    .rt-icon:disabled { opacity: .35; cursor: not-allowed; }
    .rt-icons { display: inline-flex; gap: .35rem; }
    .rt-empty { padding: 3rem 1rem; text-align: center; color: var(--muted); }
    .rt-empty i { font-size: 1.8rem; color: #c3d2e0; display: block; margin-bottom: .5rem; }
    .rt-nodriver .rt-card-head { background: #faf7ff; }
    @media (max-width: 767px) { .rt-table thead { display: none; } .rt-table tr { display: grid; grid-template-columns: 1fr auto; } .rt-table td { border-bottom: 0; } .rt-table tr:not(.rt-band) { border-bottom: 1px solid var(--line); } }

    /* خريطة المسار */
    .rt-map-top { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; margin-bottom: .6rem; }
    .rt-legend { display: flex; gap: .8rem; flex-wrap: wrap; font-size: .78rem; font-weight: 700; }
    .rt-legend i { width: 11px; height: 11px; border-radius: 50%; display: inline-block; margin-inline-end: .3rem; vertical-align: -1px; }
    .rt-steps { display: inline-flex; gap: .35rem; align-items: center; }
    .rt-steps button, .rt-steps span { height: 34px; padding: 0 .75rem; border-radius: 8px; border: 1px solid var(--om-line, #e6ecf3); background: #fff; font-weight: 700; font-size: .8rem; display: inline-flex; align-items: center; gap: .35rem; }
    .rt-steps .is-next { background: #2563eb; border-color: #2563eb; color: #fff; }
    .rt-steps .is-full { background: #111827; border-color: #111827; color: #fff; }
    .rt-steps button:disabled { opacity: .45; }
    #rt-map { height: 60vh; min-height: 360px; border-radius: 12px; }
    #rt-map-wrap:fullscreen #rt-map { height: 100vh; border-radius: 0; }
    .rt-step-info { margin-top: .6rem; font-size: .82rem; color: #374151; min-height: 1.3em; }
    .rt-marker { width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); display: grid; place-items: center; border: 2px solid #fff; box-shadow: 0 3px 8px rgba(0,0,0,.3); }
    .rt-marker span { transform: rotate(45deg); color: #fff; font-weight: 800; font-size: .78rem; }
    .rt-windows { display: grid; gap: .5rem; }
    .rt-window { display: grid; grid-template-columns: 1fr 110px 110px auto auto; gap: .4rem; align-items: center; }
    @media (max-width: 575px) { .rt-window { grid-template-columns: 1fr 1fr; } }

    /* مقاييس الكرت: المقاعد منفصلة عن «منفذ / الكل» */
    .rt-metrics { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .35rem; }
    .rt-metric { display: inline-flex; align-items: center; gap: .35rem; border-radius: 99px; padding: .22rem .7rem; font-size: .76rem; font-weight: 700; background: #eef3f8; color: #445466; font-variant-numeric: tabular-nums; }
    .rt-metric b { font-weight: 800; }
    .rt-metric.is-seats { background: #e7f1fd; color: #1b5fa8; }
    .rt-metric.is-over { background: #fee2e2; color: #b42318; box-shadow: inset 0 0 0 1px #fca5a5; }
    .rt-metric button { border: 0; background: none; color: inherit; padding: 0 .15rem; line-height: 1; }
    .rt-check { width: 18px; height: 18px; accent-color: var(--brand-ink); cursor: pointer; vertical-align: middle; }
    .rt-check:disabled { cursor: not-allowed; opacity: .35; }
    .rt-select-all { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 700; color: var(--muted); margin: 0; cursor: pointer; }
    .rt-lead { width: 1%; white-space: nowrap; }
    .rt-handle { cursor: grab; color: #9fb0c2; padding: 0 .3rem; }
    .rt-handle:active { cursor: grabbing; }
    tr.rt-ghost { opacity: .45; background: #eff8ff; }
    tr.is-selected td { background: #f5f9ff; }
    .rt-km.is-far { color: #b42318; }
    .rt-km.is-far i { margin-inline-end: .2rem; }
    .rt-sub a, .rt-task-main a { color: inherit; text-decoration: none; border-bottom: 1px dashed currentColor; }
    .rt-sub a:hover, .rt-task-main a:hover { color: var(--brand-ink); }
    .rt-pill { border: 0; cursor: pointer; }
    .rt-pill.is-open { box-shadow: inset 0 0 0 2px currentColor; }
    .rt-section .rt-card-head { background: #fff8f6; }
    .rt-section.is-understaffed .rt-card-head { background: #fffbeb; }
    .rt-reason { color: #b42318; font-weight: 600; }

    /* شريط التحديد */
    .rt-selbar { position: fixed; bottom: 18px; inset-inline: 0; margin-inline: auto; width: max-content; max-width: calc(100vw - 32px); z-index: 1040; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
                 background: #14507f; color: #fff; border-radius: 16px; padding: .6rem .7rem .6rem 1rem; box-shadow: 0 16px 40px rgba(20, 80, 127, .35); }
    .rt-selbar b { font-variant-numeric: tabular-nums; }
    .rt-selbar button { border: 0; border-radius: 10px; height: 38px; padding: 0 .9rem; font-weight: 700; font-size: .84rem; display: inline-flex; align-items: center; gap: .4rem; }
    .rt-selbar .is-go { background: #f26d7d; color: #fff; }
    .rt-selbar .is-clear { background: rgba(255, 255, 255, .14); color: #fff; }
    .rt-options { display: grid; gap: .5rem; margin-top: 1rem; padding: .75rem .9rem; border: 1px solid var(--om-line, #e6ecf3); border-radius: 12px; background: var(--om-soft, #f6f9fc); }
    .rt-options label { display: flex !important; align-items: flex-start; gap: .55rem; margin: 0 !important; font-weight: 600 !important; }
    .rt-options small { display: block; color: var(--om-muted, #6b7a8c); font-weight: 500; }
    #rt-location-map { height: 380px; border-radius: 12px; }
</style>
@endsection
@section('content')
@php
    $keep = array_filter($filters, fn ($v) => $v !== null && $v !== '');
    $url = fn (array $override) => route('admin.hourly-routing.index', array_filter(array_merge($keep, ['date' => $date->toDateString()], $override), fn ($v) => $v !== null && $v !== ''));
    $period = $filters['period'] ?? 'all';
    $counters = $board['counters'];
    $rtl = app()->getLocale() === 'ar';
    // السهم «السابق» يشير لبداية السطر: يمين في العربية ويسار في الإنجليزية.
    [$back, $forward] = $rtl ? ['fa-chevron-right', 'fa-chevron-left'] : ['fa-chevron-left', 'fa-chevron-right'];
    $t = fn (string $key, array $replace = []) => __('hourly_routing.'.$key, $replace);
    // السحب في «قائمة واحدة» فقط؛ «حسب الوقت» يجمع بالساعة فلا معنى لترتيب يدوي داخله.
    $sortable = $canReorder && $view === 'flat';
    $rowOptions = ['bands' => $view === 'bands', 'selectable' => $canTransfer, 'sortable' => $sortable, 'warning' => $board['warning_km']];
@endphp
<div class="container-fluid rt" dir="{{ $rtl ? 'rtl' : 'ltr' }}" id="routing"
     data-transfer="{{ route('admin.hourly-routing.transfer') }}" data-reorder="{{ route('admin.hourly-routing.reorder') }}"
     data-capacity="{{ route('admin.hourly-routing.capacity') }}" data-windows="{{ route('admin.hourly-routing.windows') }}"
     data-csrf="{{ csrf_token() }}">

    <section class="rt-box rt-bar">
        <h3 class="rt-title"><i class="fa fa-route"></i> {{ $t('title') }}</h3>
        <div class="rt-date">
            <a href="{{ $url(['date' => $date->copy()->subDay()->toDateString()]) }}" aria-label="{{ $t('prev_day') }}"><i class="fa {{ $back }}"></i></a>
            <label title="{{ $t('pick_date') }}"><i class="fa fa-calendar-day"></i> {{ $date->translatedFormat('d M Y') }}<input type="date" id="rt-date" value="{{ $date->toDateString() }}" aria-label="{{ $t('pick_date') }}"></label>
            <a href="{{ $url(['date' => $date->copy()->addDay()->toDateString()]) }}" aria-label="{{ $t('next_day') }}"><i class="fa {{ $forward }}"></i></a>
            @unless($date->isToday())<a href="{{ $url(['date' => today()->toDateString()]) }}">{{ $t('today') }}</a>@endunless
        </div>
        <form method="GET" action="{{ route('admin.hourly-routing.index') }}" class="rt-filters" id="rt-filters">
            @foreach(['date' => $date->toDateString(), 'period' => $filters['period'] ?? null, 'from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null, 'window' => $filters['window'] ?? null, 'view' => $filters['view'] ?? null] as $name => $value)
                @if($value !== null && $value !== '')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
            @endforeach
            @foreach(['service_id' => [$t('all_services'), 'fa-briefcase', $services->mapWithKeys(fn ($s) => [$s->id => $s->name])], 'driver_id' => [$t('all_drivers'), 'fa-id-card', collect($drivers)->pluck('name', 'id')], 'team_id' => [$t('all_teams'), 'fa-people-group', $teams->pluck('name', 'id')]] as $key => [$placeholder, $icon, $items])
                @php $value = (string) ($filters[$key] ?? ''); @endphp
                <div class="rt-select"><i class="fa {{ $icon }}"></i>
                    <select class="form-select {{ $value !== '' ? 'is-set' : '' }}" name="{{ $key }}" aria-label="{{ $placeholder }}">
                        <option value="">{{ $placeholder }}</option>
                        @foreach($items as $id => $name)<option value="{{ $id }}" {{ $value === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                    </select>
                </div>
            @endforeach
            <a class="rt-btn" href="{{ route('admin.hourly-routing.index', ['date' => $date->toDateString()]) }}"><i class="fa fa-rotate"></i> {{ $t('clear') }}</a>
            <a class="rt-btn rt-export" href="{{ route('admin.hourly-routing.export', array_merge($keep, ['date' => $date->toDateString()])) }}"><i class="fa fa-file-excel"></i> {{ $t('export') }}</a>
        </form>
    </section>

    <section class="rt-box rt-controls">
        <div class="rt-row">
            <div class="rt-seg" role="group" aria-label="{{ $t('period_group') }}">
                @foreach(['all' => null, 'am' => 'fa-sun', 'pm' => 'fa-cloud-sun'] as $key => $icon)
                    <a class="{{ $period === $key ? 'is-on' : '' }}" href="{{ $url(['period' => $key === 'all' ? null : $key, 'from' => null, 'to' => null, 'window' => null]) }}">@if($icon)<i class="fa {{ $icon }}"></i>@endif {{ $t('periods.'.$key) }}</a>
                @endforeach
                <button type="button" class="{{ $period === 'custom' ? 'is-on' : '' }}" id="rt-open-windows"><i class="fa fa-plus"></i> {{ $period === 'custom' ? (($filters['window'] ?? null) ?: $t('periods.custom')).' · '.$filters['from'].'–'.$filters['to'] : $t('periods.custom') }}</button>
            </div>
            <div class="rt-seg is-view" role="group" aria-label="{{ $t('view_group') }}">
                <a class="{{ $view === 'bands' ? 'is-on' : '' }}" href="{{ $url(['view' => 'bands']) }}" @if($canReorder) title="{{ $t('drag_bands') }}" @endif><i class="fa fa-clock"></i> {{ $t('view_bands') }}</a>
                <a class="{{ $view === 'flat' ? 'is-on' : '' }}" href="{{ $url(['view' => null]) }}"><i class="fa fa-list"></i> {{ $t('view_flat') }}</a>
            </div>
        </div>
        <div class="rt-row">
            <div class="rt-pills">
                <span class="rt-pill is-routes"><i class="fa fa-route"></i> {{ $t('counters.routes') }} <b>{{ $counters['routes'] }}</b></span>
                <button type="button" class="rt-pill is-unassigned" data-toggle-section="rt-understaffed" aria-expanded="false" aria-controls="rt-understaffed" title="{{ $t('counters.unassigned_hint') }}"><i class="fa fa-user-clock"></i> {{ $t('counters.unassigned') }} <b>{{ $counters['unassigned'] }}</b></button>
                <button type="button" class="rt-pill is-cancelled" data-toggle-section="rt-cancelled" aria-expanded="false" aria-controls="rt-cancelled"><i class="fa fa-ban"></i> {{ $t('counters.cancelled') }} <b>{{ $counters['cancelled'] }}</b></button>
                <a class="rt-pill is-nodriver text-decoration-none" href="#rt-nodriver"><i class="fa fa-user-xmark"></i> {{ $t('counters.without_driver') }} <b>{{ $counters['without_driver'] }}</b></a>
            </div>
            <span class="rt-note"><i class="fa fa-circle-info"></i> {{ $board['client_to_client'] ? $t('distance_c2c') : $t('distance_base') }}</span>
        </div>
    </section>

    {{-- الزيارات التي تنقصها عاملات: مخفية حتى الضغط على «غير مخصص» --}}
    <section class="rt-box rt-card rt-section is-understaffed" id="rt-understaffed" hidden>
        <div class="rt-card-head">
            <div class="rt-driver"><span class="rt-count"><i class="fa fa-user-clock"></i> {{ $t('understaffed_section.title') }} — {{ count($board['understaffed']) }}</span></div>
            <a class="rt-btn" href="{{ route('admin.hourly-visits.unassigned', ['from' => $date->toDateString(), 'to' => $date->toDateString()]) }}"><i class="fa fa-arrow-up-right-from-square"></i> {{ $t('understaffed_section.full_page') }}</a>
        </div>
        @include('admin.hourly_routing._visits', ['rows' => $board['understaffed'], 'mode' => 'understaffed', 'empty' => $t('understaffed_section.empty')])
    </section>

    {{-- الزيارات الملغاة مع سبب الإلغاء: مخفية افتراضيًا --}}
    <section class="rt-box rt-card rt-section" id="rt-cancelled" hidden>
        <div class="rt-card-head">
            <div class="rt-driver"><span class="rt-count"><i class="fa fa-ban"></i> {{ $t('cancelled_section.title') }} — {{ count($board['cancelled']) }}</span></div>
        </div>
        @include('admin.hourly_routing._visits', ['rows' => $board['cancelled'], 'mode' => 'cancelled', 'empty' => $t('cancelled_section.empty')])
    </section>

    @forelse($board['drivers'] as $driver)
        @php $movable = collect($driver['tasks'])->contains('movable', true); @endphp
        <section class="rt-box rt-card" data-driver="{{ $driver['id'] }}">
            <div class="rt-card-head">
                <div class="rt-driver">
                    @if($canTransfer && $movable)
                        <label class="rt-select-all"><input type="checkbox" class="rt-check" data-select-all="{{ $driver['id'] }}"> {{ $t('select.all') }}</label>
                    @endif
                    <span><i class="fa fa-car-side"></i> {{ $t('driver') }}: <span class="rt-name">{{ $driver['name'] }}</span></span>
                    <span><i class="fa fa-clock"></i> {{ $t('work_hours') }}: <span class="rt-team">{{ $driver['hours'] }}</span></span>
                    <span><i class="fa fa-people-group"></i> <b>{{ $t('primary_team') }}:</b> <span class="rt-team">{{ $driver['team'] ?? '—' }}</span></span>
                    <div class="rt-metrics">
                        <span class="rt-metric is-seats {{ $driver['over'] ? 'is-over' : '' }}" title="{{ $driver['over'] ? $t('seats.over') : $t('seats.label') }}">
                            <i class="fa fa-truck"></i> {{ $t('seats.label') }}: <b>{{ $driver['assigned'] }}/{{ $driver['seats'] ?? '∞' }}</b>
                            @if($driver['over'])<i class="fa fa-triangle-exclamation"></i>@endif
                            @if($canCapacity)<button type="button" data-edit-seats="{{ $driver['id'] }}" data-seats="{{ $driver['seats'] }}" title="{{ $t('seats.edit') }}" aria-label="{{ $t('seats.edit') }}"><i class="fa fa-pen"></i></button>@endif
                        </span>
                        <span class="rt-metric" title="{{ $t('tasks_done_hint') }}"><i class="fa fa-list-check"></i> {{ $t('done_label') }}: <b>{{ $driver['done'] }}/{{ count($driver['tasks']) }}</b></span>
                        <span class="rt-metric"><i class="fa fa-road"></i> <b>{{ number_format($driver['km'], 2) }}</b> {{ $t('km') }}</span>
                    </div>
                </div>
                <div class="rt-card-actions">
                    <button type="button" class="rt-btn rt-show" data-show-route="{{ $driver['id'] }}"><i class="fa fa-route"></i> {{ $t('show_routes') }}</button>
                    @if($canTransfer && $movable)
                        <button type="button" class="rt-btn rt-move" data-transfer-all="{{ $driver['id'] }}"><i class="fa fa-share"></i> {{ $t('transfer_all') }}</button>
                    @endif
                    <span class="rt-state is-{{ $driver['state'][0] }}">{{ $driver['state'][1] }}</span>
                </div>
            </div>
            @include('admin.hourly_routing._tasks', $rowOptions + ['tasks' => $driver['tasks'], 'driverId' => $driver['id']])
        </section>
    @empty
        <div class="rt-box rt-empty"><i class="fa fa-route"></i> {{ $t('empty') }}</div>
    @endforelse

    @if($board['unassigned'])
        <section class="rt-box rt-card rt-nodriver" id="rt-nodriver">
            <div class="rt-card-head">
                <div class="rt-driver"><span class="rt-count"><i class="fa fa-user-xmark"></i> {{ $t('no_driver_title') }} — {{ count($board['unassigned']) }}</span><span class="rt-sub">{{ $t('no_driver_hint') }}</span></div>
                @if($canTransfer)<label class="rt-select-all"><input type="checkbox" class="rt-check" data-select-all="none"> {{ $t('select.all') }}</label>@endif
            </div>
            @include('admin.hourly_routing._tasks', ['sortable' => false] + $rowOptions + ['tasks' => $board['unassigned'], 'driverId' => 'none'])
        </section>
    @endif

    {{-- شريط التحديد: يظهر عند اختيار مهمة أو أكثر --}}
    @if($canTransfer)
        <div class="rt-selbar" id="rt-selbar" role="region" aria-live="polite" hidden>
            <span><i class="fa fa-square-check"></i> <b id="rt-selcount"></b></span>
            <button type="button" class="is-go" id="rt-transfer-selected"><i class="fa fa-share"></i> {{ $t('select.transfer') }}</button>
            <button type="button" class="is-clear" id="rt-clear-selected"><i class="fa fa-xmark"></i> {{ $t('select.clear') }}</button>
        </div>
    @endif

    {{-- خريطة المسار --}}
    <div class="modal fade om-modal" id="rt-map-modal" tabindex="-1" aria-labelledby="rt-map-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-route"></i></span><div><h5 id="rt-map-title">{{ $t('map.title') }}</h5><p id="rt-map-sub"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $t('map.close') }}"></button></div>
            <div class="modal-body" id="rt-map-wrap">
                <div class="rt-map-top">
                    <div class="rt-legend">
                        <span><i style="background:var(--base)"></i>{{ $t('map.base') }}</span>
                        <span><i style="background:var(--delivery)"></i>{{ $t('roles.delivery') }}</span>
                        <span><i style="background:var(--pickup)"></i>{{ $t('roles.pickup') }}</span>
                        <span><i style="background:var(--transit)"></i>{{ $t('map.transit') }}</span>
                    </div>
                    <div class="rt-steps">
                        <button type="button" id="rt-prev"><i class="fa {{ $back }}"></i> {{ $t('map.prev') }}</button>
                        <span id="rt-step"></span>
                        <button type="button" class="is-next" id="rt-next">{{ $t('map.next') }} <i class="fa {{ $forward }}"></i></button>
                        <button type="button" class="is-full" id="rt-full" aria-label="{{ $t('map.fullscreen') }}"><i class="fa fa-expand"></i></button>
                    </div>
                </div>
                <div id="rt-map"></div>
                <div class="rt-step-info" id="rt-step-info"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t('map.close') }}</button></div>
        </div></div>
    </div>

    {{-- موقع زيارة واحدة --}}
    <div class="modal fade om-modal" id="rt-location-modal" tabindex="-1" aria-labelledby="rt-location-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-location-dot"></i></span><div><h5 id="rt-location-title">{{ $t('location_modal.title') }}</h5><p id="rt-location-sub"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $t('map.close') }}"></button></div>
            <div class="modal-body"><div id="rt-location-map"></div></div>
            <div class="modal-footer">
                <a class="btn btn-primary" id="rt-location-google" href="#" target="_blank" rel="noopener"><i class="fa fa-diamond-turn-right"></i> {{ $t('location_modal.google') }}</a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t('map.close') }}</button>
            </div>
        </div></div>
    </div>

    {{-- تحويل مهمة أو مهام محددة أو كل مهام سائق --}}
    <div class="modal fade om-modal" id="rt-transfer-modal" tabindex="-1" aria-labelledby="rt-transfer-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="rt-transfer-form" novalidate>
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-share"></i></span><div><h5 id="rt-transfer-title"></h5><p id="rt-transfer-sub"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $t('map.close') }}"></button></div>
            <div class="modal-body">
                <label for="rt-transfer-driver"><i class="fa fa-car-side"></i> {{ $t('modal.new_driver') }} <small id="rt-transfer-hint"></small></label>
                <select class="form-select" id="rt-transfer-driver" required></select>
                <small class="form-text" id="rt-transfer-empty" hidden>{{ $t('js.no_team_drivers') }}</small>
                <small class="form-text">{{ $t('modal.rules') }}</small>
                <div class="rt-options">
                    <label><input type="checkbox" class="rt-check" id="rt-team-only" checked> <span>{{ $t('transfer_options.team_only') }}</span></label>
                    <label><input type="checkbox" class="rt-check" id="rt-same-driver"> <span>{{ $t('transfer_options.same_driver') }}<small>{{ $t('transfer_options.same_driver_hint') }}</small></span></label>
                </div>
                <label class="mt-3" for="rt-transfer-reason"><i class="fa fa-pen"></i> {{ $t('modal.reason') }} <small>{{ $t('modal.reason_hint') }}</small></label>
                <textarea class="form-control" id="rt-transfer-reason" rows="2" maxlength="2000" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t('modal.cancel') }}</button><button type="submit" class="btn btn-primary" id="rt-transfer-submit"><i class="fa fa-check"></i> {{ $t('modal.confirm') }}</button></div>
        </form></div>
    </div>

    {{-- مقاعد المركبة --}}
    @if($canCapacity)
    <div class="modal fade om-modal" id="rt-seats-modal" tabindex="-1" aria-labelledby="rt-seats-title" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered"><form class="modal-content" id="rt-seats-form" novalidate>
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-truck"></i></span><div><h5 id="rt-seats-title">{{ $t('seats.title') }}</h5><p id="rt-seats-sub"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $t('map.close') }}"></button></div>
            <div class="modal-body">
                <label for="rt-seats-input"><i class="fa fa-chair"></i> {{ $t('seats.field') }}</label>
                <input class="form-control" type="number" min="3" max="50" step="1" id="rt-seats-input" required>
                <small class="form-text">{{ $t('seats.hint') }}</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t('modal.cancel') }}</button><button type="submit" class="btn btn-primary"><i class="fa fa-floppy-disk"></i> {{ $t('seats.save') }}</button></div>
        </form></div>
    </div>
    @endif

    {{-- النوافذ المخصصة --}}
    <div class="modal fade om-modal" id="rt-windows-modal" tabindex="-1" aria-labelledby="rt-windows-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><span class="om-m-icon"><i class="fa fa-clock"></i></span><div><h5 id="rt-windows-title">{{ $t('modal.windows_title') }}</h5><p>{{ $t('modal.windows_sub') }}</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $t('map.close') }}"></button></div>
            <div class="modal-body">
                <div class="rt-windows" id="rt-windows-list"></div>
                <button type="button" class="btn btn-light mt-2" id="rt-window-add"><i class="fa fa-plus"></i> {{ $t('modal.add_window') }}</button>
                <small class="form-text">{{ $t('modal.windows_hint') }}</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t('map.close') }}</button>
                @if($canEdit)<button type="button" class="btn btn-primary" id="rt-windows-save"><i class="fa fa-floppy-disk"></i> {{ $t('modal.save_windows') }}</button>@endif
            </div>
        </div></div>
    </div>
</div>
@php $payload = [
    'date' => $date->toDateString(),
    'drivers' => collect($board['drivers'])->map(fn ($d) => ['id' => $d['id'], 'name' => $d['name'], 'base' => $d['base'], 'tasks' => $d['tasks']])->values(),
    'unassigned' => $board['unassigned'],
    'choices' => $drivers,
    'windows' => $windows,
    'clientToClient' => $board['client_to_client'],
    'baseUrl' => route('admin.hourly-routing.index'),
    'query' => array_merge($keep, ['date' => $date->toDateString()]),
    'i18n' => __('hourly_routing.js') + ['selected' => __('hourly_routing.select.count')],
]; @endphp
<script type="application/json" id="rt-data">@json($payload)</script>
@endsection
@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@if($sortable)<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>@endif
@include('admin.hourly._map-provider')
<script src="{{ asset('admin/assets/js/hourly-routing.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-routing.js')) }}"></script>
@endpush
