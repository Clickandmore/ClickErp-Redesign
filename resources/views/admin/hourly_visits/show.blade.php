@extends('admin.layout.indexs.index')
@section('page-title','تفاصيل الزيارة #'.$visit->id)
@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ——— صفحة تفاصيل الزيارة بهوية قائمة الزيارات: الأزرق #2794eb مع الفيروزي #00ac9a ——— */
    .vd { --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d; --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc; color: var(--ink); }
    .vd a:focus-visible, .vd button:focus-visible, .vd select:focus-visible, .vd input:focus-visible { outline: 3px solid #9fd0f4; outline-offset: 2px; }
    .vd-num { font-variant-numeric: tabular-nums; }
    .vd-muted { color: var(--muted); }

    /* الترويسة */
    .vd-hero { position: relative; border-radius: 18px; overflow: hidden; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.2rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 10px 30px rgba(20, 80, 127, .12); }
    .vd-crumbs { display: flex; align-items: center; gap: .45rem; font-size: .8rem; flex-wrap: wrap; }
    .vd-crumbs a { color: rgba(255, 255, 255, .85); text-decoration: none; }
    .vd-crumbs a:hover { color: #fff; text-decoration: underline; }
    .vd-crumbs i { font-size: .6rem; opacity: .6; }
    .vd-hero-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-top: .7rem; }
    .vd-hero h3 { margin: 0; font-size: 1.45rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .vd-hero h3 small { font-size: .8rem; font-weight: 600; opacity: .75; }
    .vd-hero-meta { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-top: .55rem; font-size: .85rem; }
    .vd-hero-meta > span:not(.badge) { display: inline-flex; align-items: center; gap: .35rem; opacity: .9; }
    .vd-pill { display: inline-flex; align-items: center; gap: .35rem; border-radius: 99px; padding: .2rem .7rem; font-size: .78rem; font-weight: 700; background: rgba(255, 255, 255, .95); color: var(--tone, var(--brand-deep)); }
    .vd-pill .fa-circle { font-size: .45rem; }
    .vd-hero-acts { display: flex; gap: .5rem; flex-wrap: wrap; }
    .vd-hero-btn { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border-radius: 10px; padding: .5rem .95rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .vd-hero-btn:hover { background: #fff; color: var(--brand-ink); }
    .vd-hero-btn.is-ghost { background: rgba(255, 255, 255, .14); color: #fff; border: 1px solid rgba(255, 255, 255, .35); }
    .vd-hero-btn.is-ghost:hover { background: rgba(255, 255, 255, .24); color: #fff; }

    .vd-notice { display: flex; gap: .7rem; align-items: center; justify-content: space-between; flex-wrap: wrap; background: #fef8ec; border: 1px solid #f7ddb2; color: #8a5806; padding: .85rem 1rem; border-radius: 14px; font-size: .88rem; margin-bottom: 1.25rem; }
    .vd-notice > span { display: inline-flex; gap: .55rem; align-items: center; font-weight: 600; }
    .vd-notice a { background: #e08a17; color: #fff; border-radius: 9px; padding: .4rem .85rem; font-weight: 700; font-size: .82rem; text-decoration: none; }
    .vd-notice a:hover { background: #b96f0f; color: #fff; }

    /* بلاطات الأرقام */
    .vd-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin-bottom: 1.25rem; }
    .vd-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .95rem 1.1rem; display: flex; align-items: center; gap: .85rem; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); min-width: 0; }
    .vd-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone); }
    .vd-stat > i { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: grid; place-items: center; color: var(--tone); background: var(--tint); }
    .vd-stat > div { min-width: 0; }
    .vd-stat span { display: block; font-size: .78rem; color: var(--muted); font-weight: 600; }
    .vd-stat strong { display: block; font-size: 1.25rem; line-height: 1.3; font-variant-numeric: tabular-nums; }
    .vd-stat strong small { font-size: .75rem; color: var(--muted); font-weight: 600; }
    .vd-stat em { display: block; font-style: normal; font-size: .76rem; color: var(--muted); font-variant-numeric: tabular-nums; }
    .vd-t-blue { --tone: #2794eb; --tint: #eaf5fe; } .vd-t-green { --tone: #17996f; --tint: #e8f7f1; } .vd-t-amber { --tone: #e08a17; --tint: #fef5e7; } .vd-t-violet { --tone: #6b63d6; --tint: #f0effc; } .vd-t-red { --tone: #e5484d; --tint: #fdeeee; }

    /* التخطيط: عمود رئيسي وعمود جانبي */
    .vd-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 1.25rem; align-items: start; }
    .vd-main, .vd-side { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
    .vd-box { background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 10px 30px rgba(20, 80, 127, .05); min-width: 0; }
    .vd-box-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .85rem 1.15rem; border-bottom: 1px solid var(--line); }
    .vd-box-head h5 { margin: 0; font-size: .95rem; font-weight: 700; color: var(--brand-ink); display: flex; align-items: center; gap: .5rem; }
    .vd-box-head h5 i { width: 28px; height: 28px; border-radius: 8px; display: inline-grid; place-items: center; background: #eaf5fe; color: var(--brand-ink); font-size: .78rem; }
    .vd-box-body { padding: 1rem 1.15rem; }

    .vd-info { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    .vd-field { background: var(--soft); border: 1px solid #edf1f6; border-radius: 12px; padding: .75rem .9rem; min-width: 0; overflow-wrap: anywhere; }
    .vd-field.is-wide { grid-column: 1 / -1; }
    .vd-field > span { display: block; font-size: .74rem; color: var(--muted); font-weight: 700; margin-bottom: .25rem; }
    .vd-field b { font-weight: 700; }
    .vd-field a { color: var(--brand-ink); font-weight: 700; text-decoration: none; }
    .vd-field a:hover { text-decoration: underline; }
    .vd-field p { margin: 0; line-height: 1.6; }
    .vd-links { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .vd-link { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid #dbe6f1; background: #fff; color: var(--brand-ink); border-radius: 8px; padding: .25rem .6rem; font-size: .76rem; font-weight: 700; text-decoration: none !important; }
    .vd-link:hover { background: #eff8ff; border-color: #9fd0f4; }

    /* الفريق */
    .vd-team-row { display: flex; align-items: center; gap: .8rem; padding: .7rem 0; border-bottom: 1px dashed var(--line); }
    .vd-team-row:first-child { padding-top: 0; }
    .vd-team-row:last-child { border-bottom: 0; padding-bottom: 0; }
    .vd-team-role { flex: 0 0 140px; font-size: .8rem; color: var(--muted); font-weight: 700; display: flex; align-items: center; gap: .4rem; }
    .vd-team-role i { width: 26px; height: 26px; border-radius: 8px; display: inline-grid; place-items: center; background: var(--soft); color: var(--brand-ink); font-size: .72rem; }
    .vd-team-role small { font-weight: 600; }
    .vd-team-val { flex: 1; min-width: 0; display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .vd-person { display: inline-flex; align-items: center; gap: .45rem; background: #fff; border: 1px solid #dbe6f1; border-radius: 99px; padding: .2rem .7rem .2rem .25rem; font-weight: 700; font-size: .84rem; color: var(--ink); text-decoration: none; }
    a.vd-person:hover { border-color: #9fd0f4; background: #eff8ff; color: var(--brand-deep); }
    .vd-avatar { width: 26px; height: 26px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, var(--brand), var(--teal)); color: #fff; font-size: .72rem; font-weight: 800; }
    .vd-empty-val { color: #b45309; font-weight: 700; font-size: .84rem; display: inline-flex; align-items: center; gap: .35rem; }
    .vd-progress { height: 6px; border-radius: 99px; background: #e3eaf2; overflow: hidden; width: 90px; }
    .vd-progress span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--teal), #17996f); }

    /* السجل كخط زمني */
    .vd-timeline { list-style: none; margin: 0; padding: 0; }
    .vd-timeline li { position: relative; padding-inline-start: 1.8rem; padding-bottom: 1rem; }
    .vd-timeline li::before { content: ''; position: absolute; inset-inline-start: .45rem; top: .4rem; bottom: -.4rem; width: 2px; background: var(--line); }
    .vd-timeline li:last-child { padding-bottom: 0; }
    .vd-timeline li:last-child::before { display: none; }
    .vd-dot { position: absolute; inset-inline-start: 0; top: .2rem; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid var(--brand); }
    .vd-log-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; }
    .vd-log-head b { font-size: .88rem; }
    .vd-log-head time { font-size: .76rem; color: var(--muted); font-variant-numeric: tabular-nums; }
    .vd-log-by { font-size: .78rem; color: var(--muted); display: flex; align-items: center; gap: .35rem; margin-top: .15rem; }
    .vd-log-reason { margin: .4rem 0 0; font-size: .82rem; background: var(--soft); border-radius: 9px; padding: .45rem .7rem; }
    .vd-changes { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .vd-change { font-size: .76rem; border: 1px solid var(--line); border-radius: 8px; padding: .2rem .55rem; background: #fff; }
    .vd-change span { color: var(--muted); }
    .vd-change s { color: #b42a2e; text-decoration-color: rgba(180, 42, 46, .5); }
    .vd-change b { color: #0f7a5a; }

    /* الدفعات: تنسيق جدول الجزئية المشتركة دون تعديلها */
    .vd-payments > p:first-child { font-size: .86rem; margin-bottom: .3rem; }
    .vd-payments .table { margin: 0; font-size: .83rem; }
    .vd-payments .table thead th { background: var(--soft); color: var(--muted); font-size: .74rem; font-weight: 700; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .vd-payments .table td { vertical-align: middle; font-variant-numeric: tabular-nums; border-color: #eef2f6; }
    .vd-payments .table-striped > tbody > tr:nth-of-type(odd) > * { --bs-table-accent-bg: #fbfcfe; }
    .vd-payments .table-responsive { border: 1px solid var(--line); border-radius: 12px; }

    /* العمود الجانبي */
    .vd-money { display: grid; gap: .5rem; }
    .vd-money div { display: flex; justify-content: space-between; gap: .5rem; font-size: .85rem; padding: .5rem .7rem; border-radius: 10px; background: var(--soft); }
    .vd-money div span { color: var(--muted); font-weight: 600; }
    .vd-money div b { font-variant-numeric: tabular-nums; }
    .vd-money .is-due { background: #fff7e8; }
    .vd-money .is-due b { color: #b45309; }
    .vd-money .is-ok b { color: #0f7a5a; }
    .vd-collect { margin-top: .9rem; padding-top: .9rem; border-top: 1px dashed var(--line); font-size: .84rem; }
    .vd-collect > .small:first-child { margin-top: 0 !important; }
    .vd-collect .alert { border-radius: 10px; border: 1px solid #c4e2f8; background: #eff8ff; color: var(--brand-deep); }
    .vd-collect details summary { cursor: pointer; font-weight: 700; color: var(--brand-ink); }
    .vd-collect form label { display: block; margin: .5rem 0 .25rem; font-weight: 600; }
    .vd-notes { list-style: none; margin: 0; padding: 0; display: grid; gap: .6rem; }
    .vd-notes li { background: #fffbea; border: 1px solid #f5e6a8; border-radius: 12px; padding: .65rem .8rem; font-size: .85rem; }
    .vd-notes li p { margin: 0 0 .3rem; line-height: 1.6; white-space: pre-line; }
    .vd-notes li small { color: var(--muted); font-size: .74rem; }
    .vd-empty { text-align: center; padding: 1.5rem .5rem; color: var(--muted); font-size: .85rem; }
    .vd-empty i { display: block; font-size: 1.6rem; color: #c3d2e0; margin-bottom: .45rem; }

    @media (max-width: 1199px) { .vd-layout { grid-template-columns: 1fr; } .vd-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 650px) { .vd-stats, .vd-info { grid-template-columns: 1fr; } .vd-team-row { flex-direction: column; align-items: flex-start; gap: .45rem; } .vd-team-role { flex: none; } .vd-hero { padding: 1rem; } }
</style>
@endsection
@section('content')
@php
    $statusLabel = \App\Services\Hourly\VisitSearch::STATUSES[$visit->display_status] ?? $visit->status;
    $tone = match ($visit->status) {
        'completed' => '#17996f',
        'cancelled', 'cancelled_by_client', 'expired', 'no_show' => '#e5484d',
        'scheduled' => '#1b6fb8',
        'en_route', 'delivered', 'picked_up' => '#6b63d6',
        'in_progress', 'worker_finished', 'unscheduled' => '#c27410',
        default => '#5a6b7d',
    };
    $free = $visit->is_free || (float) $visit->visit_price === 0.0;
    $visitPaid = (float) $allocation['paid'];
    $visitRemaining = max(0, (float) $visit->visit_price - $visitPaid);
    $orderRemaining = max(0, $order->total_amount - $order->paid_amount);
    $assigned = $visit->workers->count();
    $hours = rtrim(rtrim(number_format((float) $visit->planned_hours, 2), '0'), '.');
    $initial = fn ($name) => mb_substr(trim((string) $name), 0, 1) ?: '؟';
    $actionLabels = \App\Services\Hourly\VisitBatchService::ACTIONS + ['bulk_note' => 'مذكرة', 'bulk_collect' => 'تحصيل', 'refund' => 'استرداد'];
    $changeLabels = ['status' => 'الحالة', 'worker_ids' => 'معرّفات العاملات', 'delivery_driver_id' => 'سائق التوصيل', 'pickup_driver_id' => 'سائق الاستلام', 'scheduled_start_at' => 'الموعد', 'visit_price' => 'السعر', 'amount' => 'المبلغ', 'payment_id' => 'رقم الدفعة'];
    $show = fn ($value) => is_array($value) ? implode('، ', $value) : ($value ?? '—');
@endphp
<div class="container-fluid ht-page vd" dir="rtl">

    <header class="vd-hero">
        <nav class="vd-crumbs" aria-label="مسار الصفحة">
            <a href="{{ route('admin.hourly-visits.index') }}">قائمة الزيارات</a><i class="fa fa-chevron-left"></i>
            <a href="{{ route('admin.hourly-orders.show', $order) }}" dir="ltr">{{ $order->order_number }}</a><i class="fa fa-chevron-left"></i>
            <span>زيارة {{ $visit->visit_number }}</span>
        </nav>
        <div class="vd-hero-top">
            <div>
                <h3><i class="fa fa-calendar-check"></i> زيارة {{ $visit->visit_number }} <small>مرجع #{{ $visit->id }}</small></h3>
                <div class="vd-hero-meta">
                    <span class="vd-pill" style="--tone: {{ $tone }}"><i class="fa fa-circle"></i> {{ $statusLabel }}</span>
                    @include('admin.hourly_visits._delay')
                    <span><i class="fa fa-broom"></i> {{ $order->serviceCategory?->name ?? 'خدمة ساعات' }}</span>
                </div>
            </div>
            <div class="vd-hero-acts">
                <a class="vd-hero-btn is-ghost" href="{{ route('admin.hourly-orders.show', $order) }}"><i class="fa fa-receipt"></i> الطلب</a>
                <a class="vd-hero-btn is-ghost" href="{{ route('admin.hourly-orders.invoice', $order) }}" target="_blank" rel="noopener"><i class="fa fa-file-invoice-dollar"></i> فاتورة الطلب</a>
                <a class="vd-hero-btn" href="{{ route('admin.hourly-visits.invoice', $visit) }}" target="_blank" rel="noopener"><i class="fa fa-file-invoice"></i> فاتورة الزيارة</a>
            </div>
        </div>
    </header>

    @if($visit->is_missed)
        <div class="vd-notice" role="alert">
            <span><i class="fa fa-triangle-exclamation"></i> لم يبدأ تنفيذ هذه الزيارة. يجب إعادة جدولتها أو إلغاؤها مع تسجيل السبب.</span>
            @if(AdminGuardHasPerm('hourly_orders_create'))<a href="{{ route('admin.hourly-visits.index', ['search' => $visit->id, 'from' => '', 'to' => '']) }}">معالجة الزيارة</a>@endif
        </div>
    @endif

    <div class="vd-stats">
        <div class="vd-stat vd-t-blue"><i class="fa fa-calendar"></i><div>
            <span>الموعد</span>
            @if($visit->scheduled_start_at)
                <strong dir="ltr">{{ $visit->scheduled_start_at->format('H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') ?? '—' }}</strong>
                <em>{{ $visit->scheduled_start_at->translatedFormat('l') }} · <span dir="ltr">{{ $visit->scheduled_start_at->format('Y-m-d') }}</span></em>
            @else
                <strong>لم يحدد</strong>
            @endif
        </div></div>
        <div class="vd-stat vd-t-violet"><i class="fa fa-hourglass-half"></i><div>
            <span>المدة</span>
            <strong>{{ $hours }} <small>ساعات</small></strong>
            <em>{{ $visit->workers_count }} عاملات مطلوبة</em>
        </div></div>
        <div class="vd-stat vd-t-green"><i class="fa fa-tag"></i><div>
            <span>السعر شامل الضريبة</span>
            <strong>{{ number_format($visit->visit_price, 2) }} <small>ر.س</small></strong>
            <em>{{ $free ? 'زيارة مجانية' : 'مدفوع '.number_format($visitPaid, 2).' ر.س' }}</em>
        </div></div>
        <div class="vd-stat {{ $visitRemaining > 0 ? 'vd-t-amber' : 'vd-t-green' }}"><i class="fa fa-wallet"></i><div>
            <span>المتبقي على الزيارة</span>
            <strong>{{ number_format($visitRemaining, 2) }} <small>ر.س</small></strong>
            <em>{{ $free || $visitRemaining <= 0 ? __('hourly.paid') : ($visitPaid > 0 ? __('hourly.partial') : __('hourly.unpaid')) }}</em>
        </div></div>
    </div>

    <div class="vd-layout">
        <div class="vd-main">
            <section class="vd-box">
                <div class="vd-box-head"><h5><i class="fa fa-user"></i> العميل والعنوان</h5></div>
                <div class="vd-box-body vd-info">
                    <div class="vd-field">
                        <span>العميل</span>
                        <a href="{{ route('clients.show', $order->user_id) }}">{{ $order->user?->name }}</a>
                    </div>
                    <div class="vd-field">
                        <span>هاتف التواصل</span>
                        <a dir="ltr" class="vd-num" href="tel:{{ $visit->contact_phone }}"><i class="fa fa-phone"></i> {{ $visit->contact_phone }}</a>
                    </div>
                    <div class="vd-field is-wide">
                        <span>العنوان</span>
                        <p>{{ $visit->address_text }}</p>
                        <div class="vd-links"><a class="vd-link" href="{{ $visit->map_url }}" target="_blank" rel="noopener"><i class="fa fa-map-location-dot"></i> فتح في الخريطة</a></div>
                    </div>
                </div>
            </section>

            <section class="vd-box">
                <div class="vd-box-head">
                    <h5><i class="fa fa-people-group"></i> التعيينات</h5>
                    <span class="vd-muted vd-num d-inline-flex align-items-center gap-2" style="font-size:.8rem">
                        <span class="vd-progress"><span style="width: {{ $visit->workers_count ? min(100, round($assigned / $visit->workers_count * 100)) : 100 }}%"></span></span>
                        {{ $assigned }} / {{ $visit->workers_count }}
                    </span>
                </div>
                <div class="vd-box-body">
                    <div class="vd-team-row">
                        <div class="vd-team-role"><i class="fa fa-user-nurse"></i> العاملات</div>
                        <div class="vd-team-val">
                            @forelse($visit->workers as $worker)
                                <a class="vd-person" href="{{ route('admin.hourly-maids.show', $worker) }}"><span class="vd-avatar">{{ $initial($worker->name) }}</span>{{ $worker->name }}</a>
                            @empty
                                <span class="vd-empty-val"><i class="fa fa-triangle-exclamation"></i> غير مخصص</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="vd-team-row">
                        <div class="vd-team-role"><i class="fa fa-user-tie"></i> قائد الفريق</div>
                        <div class="vd-team-val">
                            @if($visit->teamLeader)<span class="vd-person"><span class="vd-avatar">{{ $initial($visit->teamLeader->name) }}</span>{{ $visit->teamLeader->name }}</span>@else<span class="vd-muted">غير مخصص</span>@endif
                        </div>
                    </div>
                    @foreach(['delivery' => ['سائق التوصيل', 'fa-arrow-right-to-bracket', $visit->deliveryDriver], 'pickup' => ['سائق الاستلام', 'fa-arrow-right-from-bracket', $visit->pickupDriver]] as $role => [$label, $icon, $driver])
                        @php $time = $visit->{$role.'_at'} ? \Carbon\Carbon::parse($visit->{$role.'_at'})->format('H:i') : ($role === 'delivery' ? $visit->scheduled_start_at?->format('H:i') : $visit->scheduled_end_at?->format('H:i')); @endphp
                        <div class="vd-team-row">
                            <div class="vd-team-role"><i class="fa {{ $icon }}"></i> {{ $label }} @if($time)<small class="vd-num" dir="ltr">{{ $time }}</small>@endif</div>
                            <div class="vd-team-val">
                                @if($driver)<a class="vd-person" href="{{ route('admin.hourly-drivers.edit', $driver) }}"><span class="vd-avatar">{{ $initial($driver->name) }}</span>{{ $driver->name }}</a>@else<span class="vd-muted">غير مخصص</span>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="vd-box">
                <div class="vd-box-head"><h5><i class="fa fa-money-bill-wave"></i> الدفعات</h5></div>
                <div class="vd-box-body vd-payments">@include('admin.hourly_visits._payments')</div>
            </section>

            <section class="vd-box">
                <div class="vd-box-head">
                    <h5><i class="fa fa-clock-rotate-left"></i> سجل الحالات والإجراءات والتعيينات</h5>
                    @if($logs->total())<span class="vd-muted vd-num" style="font-size:.8rem">{{ $logs->total() }} إجراء</span>@endif
                </div>
                <div class="vd-box-body">
                    @if($logs->isEmpty())
                        <div class="vd-empty"><i class="fa fa-list-check"></i>لا توجد إجراءات مسجلة لهذه الزيارة.</div>
                    @else
                        <ol class="vd-timeline">
                            @foreach($logs as $log)
                                @php $before = json_decode($log->before, true) ?? []; $after = json_decode($log->after, true) ?? []; @endphp
                                <li>
                                    <span class="vd-dot"></span>
                                    <div class="vd-log-head"><b>{{ $actionLabels[$log->action] ?? $log->action }}</b><time dir="ltr">{{ $log->created_at }}</time></div>
                                    <div class="vd-log-by"><i class="fa fa-user-pen"></i> {{ $log->admin_name ?? 'النظام / التطبيق' }}</div>
                                    @if($log->reason)<p class="vd-log-reason">{{ $log->reason }}</p>@endif
                                    @php $changes = collect($changeLabels)->filter(fn ($label, $key) => array_key_exists($key, $after) && ($before[$key] ?? null) !== $after[$key]); @endphp
                                    @if($changes->isNotEmpty())
                                        <div class="vd-changes">
                                            @foreach($changes as $key => $label)
                                                <span class="vd-change"><span>{{ $label }}:</span> <s>{{ $show($before[$key] ?? null) }}</s> ← <b>{{ $show($after[$key]) }}</b></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                        <div class="mt-3">{{ $logs->links() }}</div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="vd-side">
            <section class="vd-box">
                <div class="vd-box-head"><h5><i class="fa fa-wallet"></i> الملخص المالي</h5></div>
                <div class="vd-box-body">
                    <div class="vd-money">
                        <div><span>قيمة الزيارة</span><b>{{ number_format($visit->visit_price, 2) }} ر.س</b></div>
                        <div class="is-ok"><span>المدفوع للزيارة</span><b>{{ number_format($visitPaid, 2) }} ر.س</b></div>
                        <div class="{{ $visitRemaining > 0 ? 'is-due' : 'is-ok' }}"><span>المتبقي للزيارة</span><b>{{ number_format($visitRemaining, 2) }} ر.س</b></div>
                        <div><span>حالة سداد الطلب</span><b>{{ __('hourly.'.$order->payment_status) }}</b></div>
                        <div class="{{ $orderRemaining > 0 ? 'is-due' : 'is-ok' }}"><span>المتبقي على الطلب</span><b>{{ number_format($orderRemaining, 2) }} ر.س</b></div>
                        <div><span>طريقة الدفع</span><b>{{ __('hourly.'.$order->payment_method) }}</b></div>
                    </div>
                    <div class="vd-collect">@include('admin.hourly._cash-collector')</div>
                </div>
            </section>

            <section class="vd-box">
                <div class="vd-box-head"><h5><i class="fa fa-note-sticky"></i> المذكرات</h5>@if($notes->count())<span class="vd-muted vd-num" style="font-size:.8rem">{{ $notes->count() }}</span>@endif</div>
                <div class="vd-box-body">
                    @if($notes->isEmpty())
                        <div class="vd-empty"><i class="fa fa-note-sticky"></i>لا توجد مذكرات لهذه الزيارة.</div>
                    @else
                        <ul class="vd-notes">
                            @foreach($notes as $note)
                                <li><p>{{ $note['text'] }}</p><small>{{ $note['author'] ?? '' }} · <span dir="ltr">{{ $note['created_at'] ?? '' }}</span></small></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
