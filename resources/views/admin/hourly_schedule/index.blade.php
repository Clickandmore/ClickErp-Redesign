@extends('admin.layout.indexs.index')
@section('page-title', 'جدول الموظفين')
@section('style')
@include('admin.hourly._modal-theme')
<style>
    /* ——— جدول الموظفين: هوية وحدة الساعات، والشبكة تُرسم بالدقائق (كل صف = 15 دقيقة بارتفاع --row) ——— */
    .hs { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; --col: 200px; --time: 64px; --head: 64px; color: var(--ink); }
    .hs [hidden] { display: none !important; }
    .hs-box { background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); margin-bottom: 1rem; }

    .hs-hero { border-radius: 18px; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.2rem 1.4rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .hs-hero h3 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: .55rem; }
    .hs-hero p { margin: .3rem 0 0; font-size: .84rem; opacity: .85; }
    .hs-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .hs-hero-btn { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border: 0; border-radius: 10px; padding: .5rem .95rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .hs-hero-btn:hover { background: #fff; color: var(--brand-ink); }
    .hs-hero-btn.is-ghost { background: rgba(255, 255, 255, .16); color: #fff; }
    .hs-hero-btn.is-ghost:hover { background: rgba(255, 255, 255, .28); color: #fff; }

    /* شريط الفلاتر والتاريخ */
    .hs-bar { padding: .9rem 1.1rem; display: grid; gap: .8rem; }
    .hs-filters { display: grid; grid-template-columns: repeat(3, minmax(160px, 1fr)) auto; gap: .6rem; align-items: end; }
    @media (max-width: 991px) { .hs-filters { grid-template-columns: 1fr 1fr; } }
    .hs-field label { display: flex; align-items: center; gap: .35rem; font-size: .76rem; font-weight: 600; color: var(--muted); margin-bottom: .3rem; }
    .hs-field .form-select, .hs-field .form-control { height: 40px; border-radius: 10px; border-color: #dfe7ef; font-size: .85rem; }
    .hs-field .is-set { border-color: #9fd0f4; background-color: #f2f9ff; font-weight: 700; color: var(--brand-deep); }
    .hs-toggle { display: inline-flex; align-items: center; gap: .45rem; height: 40px; padding: 0 .9rem; border: 1px solid var(--line); border-radius: 10px; font-size: .84rem; font-weight: 700; color: var(--ink); text-decoration: none; white-space: nowrap; background: #fff; }
    .hs-toggle.is-on { background: #eff8ff; border-color: #9fd0f4; color: var(--brand-ink); }
    .hs-date { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; padding-top: .8rem; border-top: 1px solid var(--line); }
    .hs-date-nav { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .hs-date-nav a, .hs-date-nav button { height: 38px; min-width: 38px; padding: 0 .75rem; border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--ink); display: inline-grid; place-items: center; font-weight: 700; font-size: .84rem; text-decoration: none; }
    .hs-date-nav a:hover { border-color: var(--brand); color: var(--brand-ink); }
    .hs-date-title { position: relative; font-size: 1.1rem; font-weight: 800; padding: 0 .5rem; cursor: pointer; border-bottom: 1px dashed #9fb0c2; }
    .hs-date-title input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .hs-jump { display: flex; align-items: center; gap: .45rem; font-size: .82rem; color: var(--muted); white-space: nowrap; }
    .hs-jump .form-select { height: 38px; min-width: 190px; border-radius: 10px; font-size: .84rem; }
    .hs-new { white-space: nowrap; background: #17996f; color: #fff; border-radius: 10px; padding: .5rem .95rem; font-weight: 700; font-size: .84rem; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
    .hs-new:hover { background: #0f7a5a; color: #fff; }

    .hs-legend { display: flex; align-items: center; gap: .5rem .9rem; flex-wrap: wrap; font-size: .8rem; color: var(--muted); }
    .hs-legend .sw { width: 4px; height: 18px; border-radius: 3px; display: inline-block; vertical-align: middle; margin-inline-end: .35rem; }
    .hs-pill { display: inline-flex; align-items: center; gap: .35rem; border-radius: 99px; padding: .25rem .75rem; font-weight: 700; font-size: .8rem; border: 1px solid transparent; }
    .hs-pill.is-assign { background: #fef6d8; color: #7a5a06; border-color: #f2d785; cursor: pointer; }
    .hs-pill.is-assign.is-full { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; cursor: default; }
    .hs-pill.is-total { background: #eef3f8; color: #445466; }

    /* لوحة الزيارات الناقصة */
    .hs-needing { border: 1px solid #f2d785; background: #fffbeb; border-radius: 14px; padding: .8rem 1rem; margin-bottom: 1rem; }
    .hs-needing h6 { margin: 0 0 .6rem; font-weight: 700; font-size: .9rem; color: #7a5a06; display: flex; align-items: center; gap: .45rem; }
    .hs-needing ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .4rem; }
    .hs-needing li { display: flex; align-items: center; gap: .7rem; flex-wrap: wrap; background: #fff; border: 1px solid #f7e6b3; border-radius: 10px; padding: .45rem .7rem; font-size: .82rem; }
    .hs-needing li b { font-weight: 700; }
    .hs-needing li .hs-num { font-variant-numeric: tabular-nums; direction: ltr; }
    .hs-needing li button { margin-inline-start: auto; border: 0; border-radius: 8px; background: var(--brand-ink); color: #fff; font-weight: 700; font-size: .76rem; padding: .3rem .7rem; }

    /* الشبكة: تمرير أفقي وعمودي مع رأس وعمود وقت ثابتين */
    .hs-grid-wrap { overflow: auto; max-height: calc(100vh - 170px); border-radius: 14px; border: 1px solid var(--line); background: #fff; }
    .hs-grid { display: grid; grid-template-columns: var(--time) repeat(var(--cols), minmax(var(--col), 1fr)); min-width: max-content; position: relative; }
    .hs-corner, .hs-col-head { position: sticky; top: 0; z-index: 4; height: var(--head); background: #eef3f8; border-bottom: 1px solid #d9e2ec; }
    .hs-corner { inset-inline-start: 0; z-index: 6; border-inline-end: 1px solid #d9e2ec; }
    .hs-col-head { display: flex; align-items: center; gap: .55rem; padding: 0 .75rem; border-inline-end: 1px solid #d9e2ec; min-width: 0; }
    .hs-avatar { width: 34px; height: 34px; flex: 0 0 34px; border-radius: 50%; display: grid; place-items: center; font-weight: 800; font-size: .78rem; color: #fff; background: var(--av); }
    .hs-col-head b { font-size: .86rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
    .hs-col-head small { color: var(--muted); font-size: .72rem; }
    .hs-col-head.is-target { box-shadow: inset 0 -3px 0 var(--brand); background: #e3f1fd; }
    .hs-times { position: sticky; inset-inline-start: 0; z-index: 3; background: #f7f9fc; border-inline-end: 1px solid #d9e2ec; }
    .hs-time { position: absolute; inset-inline: 0; font-size: .72rem; color: var(--muted); font-variant-numeric: tabular-nums; text-align: center; transform: translateY(-50%); background: #f7f9fc; line-height: 1.2; }
    .hs-col { position: relative; border-inline-end: 1px solid var(--line);
        background-color: #fffdf6;
        background-image: linear-gradient(to bottom, #e1e8f0 1px, transparent 1px), linear-gradient(to bottom, #eef2f6 1px, transparent 1px);
        background-size: 100% calc(var(--row) * 4), 100% calc(var(--row) * 2); }
    .hs-col.is-suspended { background-color: #f7f7f7; }
    .hs-now { position: absolute; inset-inline: 0; height: 2px; background: #e5484d; z-index: 2; pointer-events: none; }
    .hs-now::before { content: ''; position: absolute; inset-inline-start: -4px; top: -3px; width: 8px; height: 8px; border-radius: 50%; background: #e5484d; }

    /* كتلة الزيارة: خلفية بحالة التعيين، وشريط جانبي (برتقالي للمتكرر، بنفسجي لمرة واحدة) */
    .hs-block { position: absolute; z-index: 1; border-radius: 9px; padding: .35rem .5rem; overflow: hidden; cursor: pointer; font-size: .74rem; line-height: 1.45; text-align: start; border: 1px solid rgba(0, 0, 0, .06); border-inline-start: 4px solid var(--kind); background: var(--bg); color: #2b2f36; box-shadow: 0 3px 10px rgba(20, 80, 127, .1); transition: box-shadow .15s, transform .15s; }
    .hs-block:hover, .hs-block:focus-visible { box-shadow: 0 8px 20px rgba(20, 80, 127, .2); z-index: 3; outline: 0; transform: translateY(-1px); }
    .hs-block.is-recurring { --kind: #f08c1c; }
    .hs-block.is-once { --kind: #9b4dca; }
    .hs-block { --bg: #fff6cc; }
    .hs-block.is-partial { --bg: #ffe7c7; }
    .hs-block.is-progress { --bg: #dff0ff; }
    .hs-block.is-done { --bg: #dcf5e9; }
    .hs-block .hs-b-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .3rem; }
    .hs-block .hs-b-name { font-weight: 800; font-size: .8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hs-block .hs-b-menu { flex: 0 0 22px; height: 22px; border-radius: 6px; display: grid; place-items: center; background: rgba(255, 255, 255, .75); color: #445466; }
    .hs-block .hs-b-time { font-weight: 800; font-variant-numeric: tabular-nums; direction: ltr; text-align: end; }
    .hs-block .hs-b-tags { display: flex; gap: .25rem; flex-wrap: wrap; margin: .15rem 0; }
    .hs-block .hs-b-tags span { border-radius: 5px; padding: 0 .35rem; font-size: .66rem; font-weight: 700; background: rgba(255, 255, 255, .7); }
    .hs-block .hs-b-tags .is-creator { background: #a4161a; color: #fff; }
    .hs-block .hs-b-muted { color: #5b6573; }
    .hs-muted { color: var(--muted); }
    .hs-empty { padding: 3rem 1rem; text-align: center; color: var(--muted); }
    .hs-empty i { font-size: 1.8rem; color: #c3d2e0; display: block; margin-bottom: .5rem; }

    /* ملخص الزيارة داخل المودال */
    .hs-summary { border: 1px solid var(--om-line); border-inline-start: 4px solid var(--om-brand); border-radius: 12px; background: var(--om-soft); padding: .8rem 1rem; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem 1.2rem; margin-bottom: 1rem; font-size: .83rem; }
    .hs-summary div { display: flex; justify-content: space-between; gap: .6rem; border-bottom: 1px dashed #e3eaf2; padding: .2rem 0; }
    .hs-summary span { color: var(--om-muted); }
    .hs-summary b { font-weight: 700; text-align: end; }
    @media (max-width: 575px) { .hs-summary { grid-template-columns: 1fr; } }
    .hs-workers { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: .4rem; max-height: 260px; overflow: auto; }
    .hs-workers label { display: flex !important; align-items: center; gap: .45rem; border: 1px solid var(--om-line); border-radius: 10px; padding: .45rem .6rem; font-weight: 600 !important; margin: 0 !important; cursor: pointer; }
    .hs-workers label:has(input:checked) { border-color: var(--om-brand); background: #eff8ff; }
    .hs-price-row { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }

    /* إعدادات التقويم */
    .hs-tabs { display: flex; gap: 1.2rem; border-bottom: 1px solid var(--om-line); margin-bottom: 1rem; }
    .hs-tabs button { border: 0; background: none; padding: .6rem .1rem; font-weight: 700; color: var(--om-muted); position: relative; display: inline-flex; gap: .4rem; align-items: center; }
    .hs-tabs button.active { color: var(--om-brand-ink); }
    .hs-tabs button.active::after { content: ''; position: absolute; inset-inline: 0; bottom: -1px; height: 3px; border-radius: 99px; background: linear-gradient(90deg, var(--om-brand), var(--om-teal)); }
    .hs-set-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid var(--om-line); }
    .hs-set-row small { display: block; color: var(--om-muted); font-weight: 500; }
    .hs-set-row .form-select, .hs-set-row .form-control { width: 180px; }
    .hs-copy-list { list-style: none; margin: 0; padding: 0; display: grid; gap: .45rem; max-height: 55vh; overflow: auto; }
    .hs-copy-list li { display: flex; align-items: center; gap: .75rem; border: 1px solid var(--om-line); border-radius: 12px; padding: .55rem .75rem; background: #fff; }
    .hs-copy-list li.is-dragging { opacity: .5; }
    .hs-copy-list li.is-over { border-color: var(--om-brand); box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }
    .hs-copy-list li.is-off { background: var(--om-soft); }
    .hs-copy-list li.is-off .hs-copy-text { opacity: .55; }
    .hs-grip { cursor: grab; color: #9fb0c2; }
    .hs-copy-idx { width: 24px; height: 24px; border-radius: 50%; display: grid; place-items: center; background: #eaf5fe; color: var(--om-brand-ink); font-size: .72rem; font-weight: 800; flex: 0 0 24px; }
    .hs-copy-text { flex: 1; min-width: 0; }
    .hs-copy-text b { display: block; font-size: .86rem; }
    .hs-copy-text small { display: block; color: var(--om-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: .74rem; }
    .hs-copy-list label { display: inline-flex !important; align-items: center; gap: .3rem; margin: 0 !important; font-size: .76rem !important; white-space: nowrap; }
    .hs-copy-list .form-switch .form-check-input { width: 2.4em; height: 1.3em; cursor: pointer; margin: 0; }
</style>
@endsection
@section('content')
@php
    [$startHour, $endHour] = [(int) substr($settings['start_time'], 0, 2), (int) substr($settings['end_time'], 0, 2)];
    $totalMinutes = ($endHour - $startHour) * 60;
    $px = $settings['row_height'] / 15; // بكسل لكل دقيقة
    $palette = ['#f0a020', '#2794eb', '#17996f', '#6b63d6', '#e5484d', '#068a7d', '#c2410c', '#3f6ad8'];
    $initials = fn ($name) => mb_strtoupper(collect(preg_split('/\s+/u', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    $query = fn (array $override) => route('admin.hourly-schedule.index', array_filter(array_merge($filters, ['date' => $date->toDateString()], $override), fn ($v) => $v !== null && $v !== ''));
    $showNow = $date->isToday() && now()->hour >= $startHour && now()->hour < $endHour;
    $nowTop = $showNow ? (now()->hour - $startHour) * 60 + now()->minute : 0;
    $visitsById = $visits->keyBy('id');
@endphp
<div class="container-fluid hs" dir="rtl" id="staff-schedule" style="--cols: {{ max(1, $workers->count()) }}; --row: {{ $settings['row_height'] }}px"
     data-batch="{{ route('admin.hourly-visits.batch') }}" data-settings="{{ route('admin.hourly-schedule.settings') }}" data-csrf="{{ csrf_token() }}"
     data-can-edit="{{ $canEdit ? 1 : 0 }}" data-can-price="{{ $canPrice ? 1 : 0 }}" data-can-collect="{{ $canCollect ? 1 : 0 }}">

    <header class="hs-hero">
        <div>
            <h3><i class="fa fa-table-columns"></i> جدول الموظفين</h3>
            <p>العاملات كأعمدة والزيارات على خط الوقت · متابعة التوزيع وإدارة الزيارات من شاشة واحدة</p>
        </div>
        <div class="hs-hero-actions">
            @if($canSettings)<button type="button" class="hs-hero-btn is-ghost" id="hs-open-settings"><i class="fa fa-gear"></i> إعدادات التقويم</button>@endif
            <a class="hs-hero-btn" href="{{ route('admin.hourly-visits.index', ['from' => $date->toDateString(), 'to' => $date->toDateString()]) }}"><i class="fa fa-list"></i> قائمة الزيارات</a>
        </div>
    </header>

    <section class="hs-box hs-bar">
        <form method="GET" action="{{ route('admin.hourly-schedule.index') }}" class="hs-filters" id="hs-filters">
            <input type="hidden" name="date" value="{{ $date->toDateString() }}">
            @if(!empty($filters['hide_empty']))<input type="hidden" name="hide_empty" value="1">@endif
            @foreach(['service_id' => ['الخدمة', 'fa-briefcase', $services->pluck('name_ar', 'id')], 'team_id' => ['الفريق', 'fa-people-group', $teams->pluck('name', 'id')], 'city_id' => ['المدينة (حسب الطلب)', 'fa-location-dot', $cities->mapWithKeys(fn ($c) => [$c->id => $c->name])]] as $key => [$label, $icon, $items])
                @php $value = (string) ($filters[$key] ?? ''); @endphp
                <div class="hs-field">
                    <label for="hs-{{ $key }}"><i class="fa {{ $icon }}"></i> {{ $label }}</label>
                    <select class="form-select {{ $value !== '' ? 'is-set' : '' }}" id="hs-{{ $key }}" name="{{ $key }}">
                        <option value="">{{ $key === 'city_id' ? 'كل المدن' : 'الكل' }}</option>
                        @foreach($items as $id => $name)<option value="{{ $id }}" {{ $value === (string) $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                    </select>
                </div>
            @endforeach
            <a class="hs-toggle {{ !empty($filters['hide_empty']) ? 'is-on' : '' }}" href="{{ $query(['hide_empty' => empty($filters['hide_empty']) ? 1 : null]) }}">
                <i class="fa {{ !empty($filters['hide_empty']) ? 'fa-eye' : 'fa-eye-slash' }}"></i> {{ !empty($filters['hide_empty']) ? 'إظهار الفارغة' : 'إخفاء الفارغة' }}
            </a>
        </form>

        <div class="hs-date">
            <div class="hs-date-nav">
                <a href="{{ $query(['date' => $date->copy()->subDay()->toDateString()]) }}" aria-label="اليوم السابق"><i class="fa fa-chevron-right"></i></a>
                <label class="hs-date-title" title="اختر تاريخًا">{{ $date->translatedFormat('l، d F Y') }}<input type="date" id="hs-date" value="{{ $date->toDateString() }}" aria-label="اختر التاريخ"></label>
                <a href="{{ $query(['date' => $date->copy()->addDay()->toDateString()]) }}" aria-label="اليوم التالي"><i class="fa fa-chevron-left"></i></a>
                @unless($date->isToday())<a href="{{ $query(['date' => today()->toDateString()]) }}">اليوم</a>@endunless
            </div>
            <div class="hs-legend">
                <span><i class="sw" style="background:#f08c1c"></i>متكرر</span>
                <span><i class="sw" style="background:#9b4dca"></i>لمرة واحدة</span>
                <span class="hs-pill is-assign {{ $needing->isEmpty() ? 'is-full' : '' }}" id="hs-needing-toggle" role="button" tabindex="0" title="الزيارات التي تنقصها عاملات"><i class="fa fa-user-check"></i> تعيين: {{ $assignedSlots }}/{{ $slots }} ({{ $visits->count() - $needing->count() }}/{{ $visits->count() }} زيارات)</span>
                <span class="hs-pill is-total"><i class="fa fa-users"></i> المجموع: {{ $slots }} ({{ $visits->count() }} زيارات)</span>
            </div>
            <div class="hs-jump">
                <label for="hs-jump">الانتقال إلى:</label>
                <select class="form-select" id="hs-jump"><option value="">اختر العاملة</option>@foreach($workers as $worker)<option value="{{ $worker->id }}">{{ $worker->name }}</option>@endforeach</select>
                @if(AdminGuardHasPerm('hourly_orders_create'))<a class="hs-new" href="{{ route('admin.hourly-orders.create') }}"><i class="fa fa-plus"></i> طلب جديد</a>@endif
            </div>
        </div>
    </section>

    <section class="hs-needing" id="hs-needing" @if($needing->isEmpty()) hidden @endif>
        <h6><i class="fa fa-user-clock"></i> زيارات تنقصها عاملات — {{ $needing->sum(fn ($v) => (int) $v->workers_count - $v->workers->count()) }} مقاعد / {{ $needing->count() }} زيارات</h6>
        <ul>
            @foreach($needing as $visit)
                <li>
                    <span class="hs-num"><i class="fa fa-clock"></i> {{ $visit->scheduled_start_at?->format('H:i') }}–{{ $visit->scheduled_end_at?->format('H:i') }}</span>
                    <b>{{ $visit->order->user?->name }}</b>
                    <span>طلب #{{ $visit->order->order_number }} · زيارة {{ $visit->visit_number }}</span>
                    <span class="hs-muted">{{ $visit->workers->count() }}/{{ $visit->workers_count }} عاملات</span>
                    <button type="button" data-open-visit="{{ $visit->id }}" data-open-action="{{ $canEdit ? 'reassign' : '' }}"><i class="fa fa-user-plus"></i> تعيين</button>
                </li>
            @endforeach
        </ul>
    </section>

    @if($workers->isEmpty())
        <div class="hs-box hs-empty"><i class="fa fa-user-slash"></i> لا توجد عاملات مطابقة للفلاتر{{ !empty($filters['hide_empty']) ? ' أو لا توجد زيارات معيّنة في هذا اليوم' : '' }}.</div>
    @else
        <div class="hs-grid-wrap" id="hs-grid-wrap">
            <div class="hs-grid">
                <div class="hs-corner"></div>
                @foreach($workers as $worker)
                    <div class="hs-col-head" id="hs-head-{{ $worker->id }}" style="--av: {{ $palette[$worker->id % count($palette)] }}">
                        <span class="hs-avatar">{{ $initials($worker->name) }}</span>
                        <div style="min-width:0"><b title="{{ $worker->name }}">{{ $worker->name }}</b><small>{{ count($blocks[$worker->id] ?? []) }} زيارات{{ $worker->hourly_is_suspended ? ' · معلّقة' : '' }}</small></div>
                    </div>
                @endforeach

                <div class="hs-times" style="height: {{ $totalMinutes * $px }}px">
                    @for($h = $startHour + 1; $h < $endHour; $h++)<span class="hs-time" style="top: {{ ($h - $startHour) * 60 * $px }}px">{{ sprintf('%02d:00', $h) }}</span>@endfor
                </div>
                @foreach($workers as $worker)
                    <div class="hs-col {{ $worker->hourly_is_suspended ? 'is-suspended' : '' }}" style="height: {{ $totalMinutes * $px }}px" data-worker="{{ $worker->id }}">
                        @if($showNow)<span class="hs-now" style="top: {{ $nowTop * $px }}px"></span>@endif
                        @foreach($blocks[$worker->id] ?? [] as $block)
                            @php
                                $visit = $visitsById[$block['visit']];
                                $d = $details[$visit->id];
                                $state = in_array($visit->status, ['completed'], true) ? 'is-done' : (in_array($visit->status, ['en_route', 'delivered', 'in_progress', 'worker_finished', 'picked_up'], true) ? 'is-progress' : (count($d['workers']) < $d['required'] ? 'is-partial' : ''));
                                $width = 100 / $block['lanes'];
                            @endphp
                            <div class="hs-block {{ $d['recurring'] ? 'is-recurring' : 'is-once' }} {{ $state }}" role="button" tabindex="0"
                                 data-open-visit="{{ $visit->id }}" data-worker="{{ $worker->id }}"
                                 style="top: {{ $block['top'] * $px + 1 }}px; height: {{ max(22, $block['height'] * $px - 2) }}px; inset-inline-start: calc({{ $width * $block['lane'] }}% + 3px); width: calc({{ $width }}% - 6px)"
                                 title="{{ $d['customer'] }} — {{ $d['start'] }}–{{ $d['end'] }} — {{ $d['status'] }}">
                                <div class="hs-b-top"><span class="hs-b-name">{{ $d['customer'] }}</span><span class="hs-b-menu" aria-hidden="true"><i class="fa fa-ellipsis-vertical"></i></span></div>
                                <div class="hs-b-time">{{ $d['start'] }} – {{ $d['end'] }}</div>
                                <div>{{ $d['service'] }} · #{{ $d['order_number'] }}</div>
                                <div class="hs-b-muted">زيارة {{ $d['number'] }} · مرجع #{{ $visit->id }}</div>
                                <div class="hs-b-tags"><span>{{ $d['recurring'] ? 'متكرر' : 'لمرة واحدة' }}</span>@if($d['creator'])<span class="is-creator">موظف الفرع - {{ $d['creator'] }}</span>@endif<span>{{ $d['status'] }}</span></div>
                                <div class="hs-b-muted"><b>العنوان:</b> {{ $d['address'] }}</div>
                                <div class="hs-b-muted" dir="ltr" style="text-align:end">{{ $d['phone'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @include('admin.hourly_schedule._modals')
</div>
<script type="application/json" id="hs-data">@json(['visits' => $details, 'settings' => $settings, 'labels' => \App\Support\HourlyScheduleSettings::COPY_FIELDS])</script>
@endsection
@push('js')
<script src="{{ asset('admin/assets/js/hourly-staff-schedule.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-staff-schedule.js')) }}"></script>
@endpush
