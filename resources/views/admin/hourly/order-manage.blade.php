@extends('admin.layout.indexs.index')
@section('page-title', 'إدارة طلب الساعات')
@push('css')
<style>

    /* ——— هوية أسس: الأزرق السماوي #2794eb مع الفيروزي #00AC9A المأخوذان من ثيم لوحة التحكم ——— */
    .om {
        --brand: #2794eb; --brand-ink: #1b6fb8; --brand-deep: #14507f; --teal: #00ac9a; --teal-deep: #068a7d;
        --ink: #16212e; --muted: #6b7a8c; --line: #e6ecf3; --soft: #f6f9fc;
        --amber: #f0a020; --coral: #e5484d; --violet: #6b63d6;
        direction: rtl;
    }
    .om-layout { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 1.25rem; align-items: start; }
    @media (max-width: 1199px) { .om-layout { grid-template-columns: 1fr; } .om-side { order: 2; } }

    /* البطاقة: حافة علوية بتدرّج الهوية تميّز كل لوحة */
    .om-card { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 18px; overflow: hidden; margin-bottom: 1.25rem; box-shadow: 0 10px 30px rgba(20, 80, 127, .06); }
    .om-head { position: relative; background: linear-gradient(105deg, var(--brand-deep) 0%, var(--brand-ink) 42%, var(--brand) 78%, var(--teal) 140%); color: #fff; padding: 1.05rem 1.3rem; display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .om-head::after { content: ''; position: absolute; inset-inline: 0; bottom: 0; height: 3px; background: linear-gradient(90deg, var(--teal), rgba(255, 255, 255, .35)); }
    .om-head h5, .om-head strong { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
    .om-head-actions { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .om-head .om-btn-light { background: rgba(255, 255, 255, .95); color: var(--brand-deep); border: 0; border-radius: 10px; padding: .45rem .9rem; font-size: .84rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; text-decoration: none; }
    .om-head .om-btn-light:hover { background: #fff; color: var(--brand-ink); }
    .om-head .om-icon { background: rgba(255, 255, 255, .18); color: #fff; border: 0; border-radius: 10px; width: 34px; height: 34px; display: grid; place-items: center; }
    .om-head .om-icon:hover { background: rgba(255, 255, 255, .34); }
    .om-body { padding: 1.35rem; }

    /* بطاقة الطلب الجانبية */
    .om-side section { padding: 1.1rem 1.15rem; border-bottom: 1px solid var(--line); text-align: center; overflow-wrap: anywhere; }
    .om-side section:last-child { border-bottom: 0; }
    .om-side .om-name { font-size: 1.3rem; font-weight: 700; color: var(--ink); margin: 0; line-height: 1.3; }
    .om-side .om-label { font-size: .82rem; color: var(--muted); margin: 0 0 .2rem; }
    .om-side .om-value { font-size: 1.4rem; font-weight: 700; color: var(--teal-deep); margin: 0; }
    .om-icons { display: flex; justify-content: center; gap: .5rem; margin-bottom: .65rem; }
    .om-icons button, .om-icons a { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line); background: var(--soft); color: var(--muted); display: grid; place-items: center; text-decoration: none; font-weight: 700; }
    .om-icons button:hover, .om-icons a:hover { color: var(--brand); border-color: #bfe0f8; background: #eff8ff; }
    .om-tile { display: flex; align-items: center; justify-content: center; gap: .6rem; padding: .95rem; border-radius: 14px; background: linear-gradient(105deg, var(--teal-deep), var(--teal)); color: #fff; font-size: 1.15rem; font-weight: 700; box-shadow: 0 8px 20px rgba(6, 138, 125, .25); }
    .om-tile small { display: block; font-size: .78rem; font-weight: 500; opacity: .92; }
    .om-sec-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; text-align: start; }
    .om-sec-head h6 { margin: 0 0 .5rem; font-size: .9rem; font-weight: 700; color: var(--ink); }
    .om-lines { text-align: start; font-size: .84rem; color: var(--muted); display: grid; gap: .2rem; }
    .om-lines b { color: var(--ink); font-weight: 600; }
    .om-meta { text-align: start; font-size: .84rem; display: grid; gap: .4rem; }
    .om-meta span { color: var(--ink); }
    .om-meta i { width: 16px; color: var(--brand); }
    .om-ltr { direction: ltr; display: inline-block; }
    .om-num { font-variant-numeric: tabular-nums; }

    /* تبويبات بمؤشّر متدرّج مستدير */
    .om-tabs { display: flex; gap: 1.4rem; flex-wrap: wrap; border-bottom: 1px solid var(--line); margin-bottom: 1.35rem; }
    .om-tabs .nav-link { position: relative; border: 0; background: none; padding: .6rem .1rem .75rem; color: var(--muted); font-weight: 600; font-size: .92rem; display: inline-flex; align-items: center; gap: .4rem; }
    .om-tabs .nav-link::after { content: ''; position: absolute; inset-inline: 0; bottom: -1px; height: 3px; border-radius: 99px; background: transparent; }
    .om-tabs .nav-link:hover { color: var(--ink); }
    .om-tabs .nav-link.active { color: var(--brand-ink); }
    .om-tabs .nav-link.active::after { background: linear-gradient(90deg, var(--brand), var(--teal)); }
    .om-tabs .nav-link .badge { background: #eef3f8; color: var(--muted); font-weight: 600; }
    .om-tabs .nav-link.active .badge { background: #e7f3fd; color: var(--brand-ink); }

    /* بطاقات الإحصاء: بيضاء بشريط جانبي وأيقونة مظلّلة بدل الكتل المصمتة */
    .om-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .9rem; margin-bottom: 1.5rem; }
    .om-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .95rem 1.1rem; display: flex; align-items: center; gap: .8rem; overflow: hidden; box-shadow: 0 6px 18px rgba(20, 80, 127, .05); }
    .om-stat::before { content: ''; position: absolute; inset-block: 0; inset-inline-start: 0; width: 4px; background: var(--tone, var(--brand)); }
    .om-stat > i { width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px; display: grid; place-items: center; font-size: 1.05rem; color: var(--tone, var(--brand)); background: var(--tint, #eff8ff); }
    .om-stat strong { display: block; font-size: 1.35rem; line-height: 1.2; color: var(--ink); font-variant-numeric: tabular-nums; }
    .om-stat span { font-size: .8rem; color: var(--muted); }
    .om-t-orange { --tone: #e08a17; --tint: #fef5e7; }
    .om-t-violet { --tone: #6b63d6; --tint: #f0effc; }
    .om-t-teal   { --tone: #068a7d; --tint: #e6f7f5; }
    .om-t-indigo { --tone: #3f6ad8; --tint: #ecf1fd; }
    .om-t-blue   { --tone: #2794eb; --tint: #eaf5fe; }
    .om-t-red    { --tone: #e5484d; --tint: #fdeeee; }
    .om-t-green  { --tone: #17996f; --tint: #e8f7f1; }

    /* صندوق الملاحظات التشغيلية */
    .om-notes { border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.15rem; margin-bottom: 1.5rem; background: linear-gradient(180deg, var(--soft), #fff); }
    .om-notes-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .om-notes-head h6 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--brand-ink); display: flex; align-items: center; gap: .45rem; }
    .om-note { border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: .7rem .85rem; margin-top: .75rem; }
    .om-note p { white-space: pre-wrap; margin: 0 0 .3rem; font-size: .86rem; color: var(--ink); }
    .om-note small { color: var(--muted); font-size: .74rem; }

    /* عنوان القسم وشريط الإجراءات الجماعية */
    .om-section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: .85rem; }
    .om-section-head h6 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--brand-ink); }
    .om-ops .d-flex { margin: 0 !important; gap: .6rem !important; }
    .om-ops label { font-size: .84rem; color: var(--muted); display: inline-flex; align-items: center; gap: .4rem; margin: 0; }
    .om-ops .btn-primary { background: var(--brand-ink); border-color: var(--brand-ink); border-radius: 10px; font-weight: 600; }
    .om-ops .btn-primary:hover { background: var(--brand-deep); border-color: var(--brand-deep); }
    .om-ops #selected-visits-count { font-size: .8rem; }

    /* جدول الزيارات: كل صف بطاقة بشريط حالة على الحافة الخارجية */
    .om-table { width: 100%; font-size: .86rem; border-collapse: separate; border-spacing: 0 .55rem; }
    .om-table th { font-size: .82rem; color: var(--muted); font-weight: 700; padding: .5rem .9rem; white-space: nowrap; text-align: start; }
    .om-table td { padding: 1rem .9rem; vertical-align: top; min-width: 140px; background: #fff; border-block: 1px solid var(--line); }
    .om-table td:first-child { border-inline-start: 4px solid var(--row-tone, var(--brand)); border-start-start-radius: 14px; border-end-start-radius: 14px; }
    .om-table td:last-child { border-inline-end: 1px solid var(--line); border-start-end-radius: 14px; border-end-end-radius: 14px; }
    .om-kv { display: flex; gap: .4rem; flex-wrap: wrap; align-items: baseline; margin-bottom: .25rem; }
    .om-kv > span { color: var(--muted); font-size: .8rem; }
    .om-kv > b { color: var(--ink); font-weight: 600; }
    .om-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 8px; padding: .18rem .55rem; font-size: .76rem; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .om-b-green { background: #e8f7f1; color: #0f7a5a; border-color: #bfe9d9; }
    .om-b-grey  { background: #eef3f8; color: #5a6b7d; border-color: #dbe4ee; }
    .om-b-red   { background: #fdeeee; color: #b42a2e; border-color: #f7cdcf; }
    .om-b-amber { background: #fef5e7; color: #9a6206; border-color: #f7ddb2; }
    .om-b-blue  { background: #eaf5fe; color: #1b6fb8; border-color: #c4e2f8; }
    .om-b-violet{ background: #f0effc; color: #4f47b8; border-color: #d6d2f5; }
    .om-when { text-align: center; }
    .om-when .om-day { color: var(--brand-ink); font-weight: 700; display: inline-flex; align-items: center; gap: .35rem; }
    .om-when .om-time { color: var(--brand); font-weight: 700; font-variant-numeric: tabular-nums; }
    .om-when .om-dur { color: var(--ink); font-weight: 700; margin-top: .2rem; }
    .om-slot { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; padding: .5rem 0; border-bottom: 1px dashed var(--line); }
    .om-slot:last-of-type { border-bottom: 0; }
    .om-slot .om-slot-no { color: var(--muted); font-variant-numeric: tabular-nums; }
    .om-slot .om-slot-name { color: var(--ink); font-weight: 600; }
    .om-slot .om-slot-empty { color: var(--muted); }
    .om-drivers { font-size: .78rem; color: var(--muted); display: grid; gap: .2rem; margin-top: .6rem; padding-top: .6rem; border-top: 1px solid var(--line); }
    .om-drivers i { color: #9fb0c2; width: 14px; }

    /* أزرار الإجراءات: ملء بلون الهوية للأساسي وحواف خفيفة للثانوي */
    .om-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; width: 100%; border: 0; border-radius: 10px; padding: .55rem .7rem; font-size: .82rem; font-weight: 700; color: #fff; background: var(--brand); margin-top: .45rem; text-decoration: none; transition: filter .15s, box-shadow .15s; }
    .om-btn:hover { color: #fff; filter: brightness(.95); box-shadow: 0 4px 12px rgba(20, 80, 127, .16); }
    .om-btn-warn { background: var(--amber); }
    .om-btn-info { background: var(--brand); }
    .om-btn-violet { background: var(--violet); }
    .om-btn-danger { background: var(--coral); }
    .om-btn-green { background: var(--teal-deep); }
    .om-btn-soft { background: #fff; color: #4a5a6b; border: 1px solid var(--line); }
    .om-btn-soft:hover { border-color: var(--brand); color: var(--brand); background: #f7fbff; }
    .om-order-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
    .om-order-actions .om-btn { margin: 0; min-height: 42px; }
    .om-link-danger { background: none; border: 0; color: var(--coral); font-size: .78rem; display: inline-flex; align-items: center; gap: .25rem; padding: 0; }
    .om-link-edit { background: none; border: 0; color: var(--brand); font-size: .78rem; display: inline-flex; align-items: center; gap: .25rem; padding: 0; }

    /* زر فتح مودال الملاحظة التشغيلية: مفرَّغ في ترويسة الصندوق، وممتلئ داخل الجدول */
    .om-note-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: 10px; padding: .55rem .9rem; font-size: .82rem; font-weight: 700; background: #fff; color: var(--brand-ink); border: 1px solid var(--brand); }
    .om-note-btn:hover { background: #f7fbff; color: var(--brand-deep); }
    .om-table .om-note-btn { width: 100%; margin-top: .45rem; background: var(--violet); border-color: var(--violet); color: #fff; }
    .om-table .om-note-btn:hover { color: #fff; filter: brightness(.95); }

    .om-note-form summary { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: 10px; padding: .55rem .9rem; font-size: .82rem; font-weight: 700; color: #fff; background: var(--violet); cursor: pointer; list-style: none; }
    .om-note-form summary::-webkit-details-marker { display: none; }
    .om-table .om-note-form { margin-top: .45rem; }
    .om-table .om-note-form summary { width: 100%; }
    .om-notes-head .om-note-form { margin: 0; }
    .om-notes-head .om-note-form summary { background: #fff; color: var(--brand-ink); border: 1px solid var(--brand); }
    .om-notes-head .om-note-form summary:hover { background: #f7fbff; }

    .om-timeline { list-style: none; margin: 0; padding-inline-start: 1.6rem; position: relative; }
    .om-timeline::before { content: ''; position: absolute; inset-block: .5rem; inset-inline-start: .3rem; width: 2px; background: linear-gradient(180deg, var(--brand), var(--teal)); opacity: .35; }
    .om-timeline li { position: relative; padding-bottom: 1.15rem; }
    .om-timeline li:last-child { padding-bottom: 0; }
    .om-timeline li::before { content: ''; position: absolute; inset-inline-start: -1.6rem; top: .3rem; width: 12px; height: 12px; border-radius: 50%; background: #fff; border: 2px solid var(--brand); }
    .om-tl-time { font-size: .76rem; color: var(--muted); }
    .om-tl-flow { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin: .3rem 0; }
    .om-timeline p { margin: .2rem 0 0; font-size: .85rem; color: var(--ink); }

    .om-collect { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .85rem; align-items: end; padding: 1rem 1.1rem; border: 1px solid var(--line); border-radius: 16px; background: linear-gradient(180deg, var(--soft), #fff); margin-bottom: 1.5rem; }
    .om-collect label { display: block; font-size: .8rem; color: var(--muted); margin-bottom: .35rem; }
    .om-collect .form-control, .om-collect .form-select { border-radius: 10px; border-color: #dfe7ef; }
    .om-collect .form-control:focus, .om-collect .form-select:focus { border-color: #9fd0f4; box-shadow: 0 0 0 3px rgba(39, 148, 235, .15); }

    .om-alert { display: flex; gap: .7rem; align-items: flex-start; border: 1px solid #f7ddb2; background: #fef8ec; color: #8a5806; border-radius: 14px; padding: .85rem 1rem; font-size: .86rem; margin-bottom: 1.25rem; }

    .om-empty { text-align: center; padding: 2.2rem 1rem; color: var(--muted); }
    .om-empty i { font-size: 1.7rem; color: #c3d2e0; display: block; margin-bottom: .5rem; }

    @media print {
        body * { visibility: hidden; }
        .om, .om * { visibility: visible; }
        .om { position: absolute; inset: 0; }
        .om-layout { grid-template-columns: 1fr; }
        .om-card { break-inside: avoid; box-shadow: none; }
        .om-head-actions, .om-note-form, .om-tabs, .om-ops, .om-btn, .om-icons, form, details { display: none !important; }
        .tab-pane { display: block !important; opacity: 1 !important; }
    }
</style>
@endpush
@section('content')
@include('admin.hourly._booking-options')
@php
    $snapshot = $order->package_snapshot ?? [];
    $itemTitle = collect($snapshot['translations'] ?? [])->firstWhere('locale', app()->getLocale())['title'] ?? ($snapshot['title'] ?? $snapshot['code'] ?? '—');
    $notes = app(\App\Services\Hourly\OperationalNotes::class)->forOrder($order);
    $visitCount = $order->visits->count();
    $completed = $order->visits->where('status', 'completed')->count();
    $cancelled = $order->visits->whereIn('status', ['cancelled', 'cancelled_by_client'])->count();
    $lastVisit = $order->visits->sortByDesc('scheduled_end_at')->first();
    $remaining = max(0, (float) $order->total_amount - (float) $order->paid_amount);
    $paidPercent = $order->total_amount > 0 ? min(100, round($order->paid_amount / $order->total_amount * 100)) : 0;
    $daysLeft = $order->expires_at ? (int) now()->startOfDay()->diffInDays($order->expires_at->startOfDay(), false) : null;
    $totalWorkHours = $order->visits->sum(fn ($visit) => (float) $visit->planned_hours * $visit->workers_count);

    $money = fn ($amount) => __('hourly.sar').' '.number_format((float) $amount, 2);
    $hours = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    /* لون وأيقونة موحّدان لكل حالة: الطلب والزيارة والدفعة. */
    $statusTone = fn ($status) => match ($status) {
        'pending_confirmation' => ['om-b-amber', 'fa-hourglass-half'],
        'active', 'completed', 'paid' => ['om-b-green', 'fa-circle-check'],
        'expired', 'no_show', 'cancelled', 'cancelled_by_client', 'failed', 'unpaid', 'rejected' => ['om-b-red', 'fa-circle-xmark'],
        'scheduled' => ['om-b-blue', 'fa-calendar-check'],
        'en_route', 'delivered', 'picked_up' => ['om-b-violet', 'fa-van-shuttle'],
        'in_progress', 'worker_finished', 'partial', 'pending', 'pending_review' => ['om-b-amber', 'fa-clock'],
        default => ['om-b-grey', 'fa-circle'],
    };
    /* لون شريط الصف: يطابق فئة الشارة نفسها. */
    $toneHex = ['om-b-green' => '#17996f', 'om-b-red' => '#e5484d', 'om-b-amber' => '#e08a17', 'om-b-blue' => '#2794eb', 'om-b-violet' => '#6b63d6', 'om-b-grey' => '#9fb0c2'];
    [$statusClass, $statusIcon] = $statusTone($order->status);
    [$payClass, $payIcon] = $statusTone($order->payment_status);
@endphp

<div class="container-fluid om">
@include('admin.hourly.messages')
@include('admin.hourly._order-refund')
@include('admin.hourly._cash-link')

<div class="om-layout">
<aside class="om-side">
    <div class="om-card">
        <header class="om-head"><strong><i class="fa fa-cart-shopping"></i> بطاقة الطلب #{{ $order->order_number }}</strong></header>
        <section>
            <div class="om-icons">
                <button type="button" id="om-copy-card" title="نسخ بيانات الاتصال" aria-label="نسخ بيانات الاتصال"><i class="fa fa-copy"></i></button>
                <a href="#om-notes-box" title="عدد الملاحظات" aria-label="عدد الملاحظات"><span class="om-num">{{ $notes->count() }}</span></a>
                @if($order->user?->email)<a href="mailto:{{ $order->user->email }}" title="مراسلة العميل" aria-label="مراسلة العميل"><i class="fa fa-envelope"></i></a>@endif
            </div>
            <p class="om-name" id="om-customer-name">{{ $order->user?->name ?: 'عميل محذوف' }}</p>
            <p class="om-label mt-2"><i class="fa fa-circle-info"></i> بيانات الاتصال وقت الطلب</p>
            <p class="om-ltr om-num" id="om-customer-phone"><i class="fa fa-phone"></i> {{ $order->contact_phone ?: $order->user?->phone }}</p>
            <div class="om-tile mt-2"><i class="fa fa-wallet"></i> <span>{{ $money($order->user?->hourly_wallet_balance ?? 0) }}<small>رصيد العميل</small></span></div>
        </section>
        <section>
            <div class="om-sec-head">
                <div>
                    <h6>معلومات العنوان</h6>
                    <div class="om-lines">
                        <div>اسم المبنى / الفيلا: <b>{{ $order->building ?: '—' }}</b></div>
                        <div>رقم الوحدة: <b>{{ $order->unit ?: '—' }}</b></div>
                        <div>العنوان: <b>{{ $order->address_text }}</b></div>
                        <div>الحي / المنطقة: <b>{{ $order->district ?: '—' }}</b></div>
                    </div>
                </div>
                @if($order->latitude !== null && $order->longitude !== null)
                    <a class="om-btn om-btn-warn" style="width:auto;margin:0" target="_blank" rel="noopener" title="عرض على الخريطة" aria-label="عرض على الخريطة" href="https://www.google.com/maps?q={{ urlencode($order->latitude.','.$order->longitude) }}"><i class="fa fa-map-location-dot"></i></a>
                @endif
            </div>
        </section>
        <section><p class="om-label">مبلغ الطلب الأولي</p><p class="om-value om-num">{{ $money($order->initial_amount ?? $snapshot['original_total'] ?? $order->total_amount) }}</p></section>
        <section><p class="om-label">مبلغ الطلب الحالي</p><p class="om-value om-num">{{ $money($order->total_amount) }}</p></section>
        <section><p class="om-label">مبلغ الطلب المحصَّل</p><p class="om-value om-num">{{ $money($order->paid_amount) }}</p>
            @if($remaining > 0)<p class="om-label mt-1" style="color:#c2410c">المتبقي {{ $money($remaining) }} — سُدِّد {{ $paidPercent }}%</p>@endif
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-folder"></i> <b>فئة الخدمة:</b> <span>{{ $snapshot['category'] ?? $order->serviceCategory?->name ?? '—' }}</span></div>
                <div><i class="fa fa-box"></i> <b>الباقة:</b> <span>{{ $itemTitle }}</span></div>
                <div><i class="fa fa-tag"></i> <b>سعر الباقة:</b> <span class="om-num">{{ $money($snapshot['price_per_visit'] ?? $order->price_per_visit) }}</span></div>
                <div><i class="fa fa-droplet"></i> <b>مواد التنظيف:</b> <span>{{ !empty($snapshot['materials_included']) ? 'مشمولة' : 'غير مشمولة' }}</span></div>
            </div>
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-user-tie"></i> <b>مصدر الطلب:</b> <span>{{ $order->source === 'admin' ? 'موظف الفرع' : 'العميل' }} — {{ $creator?->name ?: '—' }}</span></div>
                <div><i class="fa fa-clock"></i> <b>إنشاء الطلب:</b> <span class="om-ltr om-num">{{ $order->created_at?->format('Y-m-d H:i') }}</span></div>
                <div><i class="fa fa-play"></i> <b>بداية الطلب:</b> <span class="om-ltr om-num">{{ $order->starts_at?->format('Y-m-d H:i') }}</span></div>
                <div><i class="fa fa-flag-checkered"></i> <b>نهاية آخر زيارة:</b> <span class="om-ltr om-num">{{ $lastVisit?->scheduled_end_at?->format('Y-m-d H:i') ?: 'لم يحدد' }}</span></div>
                <div><i class="fa fa-calendar-day"></i> <b>الصلاحية:</b> <span class="om-ltr om-num">{{ $order->expires_at?->format('Y-m-d') }}</span>
                    @if($daysLeft !== null)<span class="om-badge {{ $daysLeft < 0 ? 'om-b-red' : ($daysLeft <= 7 ? 'om-b-amber' : 'om-b-grey') }}">{{ $daysLeft < 0 ? 'منتهية' : $daysLeft.' يوم' }}</span>@endif
                </div>
            </div>
        </section>
        <section>
            <div class="om-meta">
                <div><i class="fa fa-wallet"></i> <b>نوع الدفع:</b> <span>{{ __('hourly.'.$order->payment_method) }}</span></div>
                <div><i class="fa fa-receipt"></i> <b>حالة السداد:</b> <span class="om-badge {{ $payClass }}"><i class="fa {{ $payIcon }}"></i> {{ __('hourly.'.$order->payment_status) }}</span></div>
                <div><i class="fa fa-flag"></i> <b>حالة الطلب:</b> <span class="om-badge {{ $statusClass }}"><i class="fa {{ $statusIcon }}"></i> {{ __('hourly.'.$order->status) }}</span></div>
            </div>
        </section>
        @include('admin.hourly._order-card-actions')
    </div>
</aside>

<main>
    <div class="om-card">
        <header class="om-head">
            <h5><i class="fa fa-cart-shopping"></i> #{{ $order->order_number }} — تفاصيل الطلب</h5>
            <div class="om-head-actions">
                <button class="om-icon" onclick="window.print()" type="button" title="طباعة" aria-label="طباعة"><i class="fa fa-print"></i></button>
                <a class="om-btn-light" target="_blank" href="{{ route('admin.hourly-orders.invoice', $order) }}">الفاتورة <i class="fa fa-file-pdf"></i></a>
                @if(AdminGuardHasPerm('hourly_orders_create'))
                    @php $renewalReason=app(\App\Services\Hourly\RenewalEligibility::class)->reason($order); @endphp
                    @if($renewalReason)<span class="om-btn-light" aria-disabled="true" title="{{ $renewalReason }}">التجديد غير متاح <i class="fa fa-lock"></i><small class="d-block">{{ $renewalReason }}</small></span>@else
                    <a class="om-btn-light" href="{{ route('admin.hourly-orders.create', ['renew' => $order->id]) }}">تجديد <i class="fa fa-rotate"></i></a>
                    @endif
                @endif
                <a class="om-btn-light" href="{{ route('admin.hourly-orders.summary', $order) }}">الملخص <i class="fa fa-file-lines"></i></a>
                <a class="om-btn-light" href="{{ route('admin.hourly-orders.index') }}">الطلبات <i class="fa fa-list"></i></a>
            </div>
        </header>
        <div class="om-body">
            @php
                $tabs = ['details' => ['تفاصيل الطلب', $visitCount], 'status' => ['سجل الحالة', $order->statusLogs->count()], 'reviews' => ['تقييمات العاملات', $reviews->count()], 'audit' => ['السجلات', $audits->count()], 'notes' => ['الملاحظات', $notes->count()], 'payments' => ['سجل الدفعات', $order->payments->count()]];
            @endphp
            <nav class="nav om-tabs" role="tablist">
                @foreach($tabs as $key => $tab)
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="om-tab-{{ $key }}" data-bs-toggle="tab" data-bs-target="#om-{{ $key }}" type="button" role="tab" aria-controls="om-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $tab[0] }}@if($tab[1])<span class="badge rounded-pill om-num">{{ $tab[1] }}</span>@endif
                    </button>
                @endforeach
            </nav>

            <div class="tab-content">
            <section id="om-details" class="tab-pane show active" role="tabpanel" aria-labelledby="om-tab-details">
                <div class="om-stats">
                    <div class="om-stat om-t-orange"><i class="fa fa-calendar-days"></i><div><strong>{{ $visitCount }}</strong><span>إجمالي الزيارات</span></div></div>
                    <div class="om-stat om-t-violet"><i class="fa fa-user-group"></i><div><strong>{{ $order->workers_per_visit }}</strong><span>الموظفون لكل زيارة</span></div></div>
                    <div class="om-stat om-t-teal"><i class="fa fa-hourglass-half"></i><div><strong>{{ $hours($totalWorkHours) }} ساعة</strong><span>إجمالي ساعات العمل</span></div></div>
                    <div class="om-stat om-t-indigo"><i class="fa fa-clock"></i><div><strong>{{ $hours($order->hours_per_visit) }} ساعة</strong><span>وقت الزيارة</span></div></div>
                    <div class="om-stat om-t-blue"><i class="fa fa-dollar-sign"></i><div><strong>{{ $money($averageVisitPrice) }}</strong><span>متوسط السعر (دون المجانية والملغاة)</span></div></div>
                    <div class="om-stat om-t-red"><i class="fa fa-circle-xmark"></i><div><strong>{{ $cancelled }} ({{ $visitCount ? round($cancelled / $visitCount * 100) : 0 }}%)</strong><span>ملغاة</span></div></div>
                    <div class="om-stat om-t-green"><i class="fa fa-circle-check"></i><div><strong>{{ $completed }} ({{ $visitCount ? round($completed / $visitCount * 100) : 0 }}%)</strong><span>مكتملة</span></div></div>
                </div>

                <div class="om-notes" id="om-notes-box">
                    <div class="om-notes-head">
                        <h6>الملاحظات التشغيلية</h6>
                        @include('admin.hourly._note-form', ['visitId' => null])
                    </div>
                    @if($order->notes)<div class="om-note"><p>{{ $order->notes }}</p><small>ملاحظة مرفقة بالطلب عند الإنشاء</small></div>@endif
                    @foreach($notes->whereNull('visit_id') as $note)
                        <div class="om-note"><p>{{ $note['text'] }}</p><small><i class="fa fa-user-pen"></i> {{ $note['author'] }} — <span class="om-ltr">{{ $note['created_at'] }}</span></small></div>
                    @endforeach
                </div>

                <div class="om-section-head">
                    <h6>تفاصيل الزيارة ({{ $visitCount }} زيارة)</h6>
                    <div class="om-ops">@include('admin.hourly._order-operations')</div>
                </div>

                <div class="table-responsive">
                    <table class="om-table">
                        <thead><tr><th>رقم الزيارة</th><th>تاريخ الزيارة</th><th>شؤون الموظفين</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                        <tbody>
                        @forelse($order->visits as $visit)
                            @php
                                [$visitClass, $visitIcon] = $statusTone($visit->status);
                                $assigned = $visit->workers;
                                $visitPaid=$visitAllocations[$visit->id]['paid']??0;
                                $free=$visit->is_free || (float)$visit->visit_price===0.0;
                                $visitPaymentStatus=$visitPaid>=(float)$visit->visit_price?'paid':($visitPaid>0?'partial':'unpaid');
                                $emptySlots = max(0, (int) $visit->workers_count - $assigned->count());
                                $editable = AdminGuardHasPerm('hourly_orders_edit') && in_array($order->status, ['active', 'pending_confirmation']) && !in_array($visit->status, ['completed', 'cancelled', 'expired', 'no_show', 'cancelled_by_client']);
                            @endphp
                            <tr style="--row-tone: {{ $toneHex[$visitClass] ?? '#9fb0c2' }}">
                                <td>
                                    @if($editable)
                                        <label class="om-kv"><input type="checkbox" data-visit-select value="{{ $visit->id }}" data-number="{{ $visit->visit_number }}" data-start="{{ $visit->scheduled_start_at?->format('Y-m-d H:i') }}" data-end="{{ $visit->scheduled_end_at?->format('Y-m-d H:i') }}" data-delivery="{{ $visit->delivery_at }}" data-pickup="{{ $visit->pickup_at }}" aria-label="تحديد الزيارة {{ $visit->visit_number }}"> <span>تحديد للإجراءات الجماعية</span></label>
                                    @endif
                                    <div class="om-kv"><span>رقم الزيارة:</span><b class="om-ltr om-num">{{ $visit->visit_number }}</b></div>
                                    <small class="text-muted">المرجع: #{{ $visit->id }}</small>
                                    @include('admin.hourly._cash-collector')
                                    @if($free)<span class="om-badge om-b-green">زيارة مجانية</span>@endif
                                    <div class="om-kv"><span>معرف الطلب:</span><b class="om-ltr om-num">#{{ $order->order_number }}</b></div>
                                    <div class="om-kv"><span>سعر الزيارة:</span><span class="om-badge om-b-green om-num">{{ $money($visit->visit_price) }}</span></div>
                                    <div class="om-kv"><span>حالة سداد الزيارة:</span><span class="om-badge {{ $free?'om-b-green':$statusTone($visitPaymentStatus)[0] }}">{{ $free?'مجانية':__('hourly.'.$visitPaymentStatus) }}</span></div>
                                    <div class="om-kv"><span>المبلغ المدفوع للزيارة:</span><span class="om-badge om-b-green om-num">{{ $money($visitPaid) }}</span></div>
                                    @include('admin.hourly._note-form', ['visitId' => $visit->id])
                                    @include('admin.hourly._visit-actions', ['group' => 'money'])
                                </td>
                                <td class="om-when">
                                    @if($visit->scheduled_start_at)
                                        <div class="om-day">
                                            {{ $visit->scheduled_start_at->translatedFormat('l') }}
                                            @if($editable && $visit->scheduled_start_at->isFuture())
                                                <button type="button" class="om-link-edit" data-order-action="start_time" data-visit="{{ $visit->id }}" title="تعديل الموعد" aria-label="تعديل موعد الزيارة {{ $visit->id }}"><i class="fa fa-pen-to-square"></i></button>
                                            @endif
                                        </div>
                                        <div class="om-time om-ltr">{{ $visit->scheduled_start_at->format('d/m/Y H:i') }} – {{ $visit->scheduled_end_at?->format('H:i') }}</div>
                                    @else
                                        <div class="om-day">لم يُحدَّد الموعد</div>
                                    @endif
                                    <div class="om-dur om-num">{{ $hours($visit->planned_hours) }} ساعات</div>
                                </td>
                                <td>
                                    @foreach($assigned as $index => $worker)
                                        <div class="om-slot">
                                            <span class="om-slot-no">{{ $index + 1 }} –</span>
                                            <span class="om-slot-name">{{ $worker->name }}</span>
                                            @if($editable)
                                                <button type="button" class="om-link-danger" data-order-action="unassign_worker" data-worker="{{ $worker->id }}" data-visit="{{ $visit->id }}"><i class="fa fa-circle-xmark"></i> إلغاء التعيين</button>
                                            @endif
                                        </div>
                                    @endforeach
                                    @for($slot = 1; $slot <= $emptySlots; $slot++)
                                        <div class="om-slot">
                                            <span class="om-slot-no">{{ $assigned->count() + $slot }} –</span>
                                            <span class="om-slot-empty">لم يتم الاختيار بعد</span>
                                            @if($editable)
                                                <button type="button" class="om-link-edit" data-order-action="assign_workers" data-visit="{{ $visit->id }}">اختر العاملة <i class="fa fa-pen-to-square"></i></button>
                                                @if(AdminGuardHasPerm('hourly_orders_price_override'))
                                                    <button type="button" class="om-link-danger" data-order-action="remove_worker" data-visit="{{ $visit->id }}"><i class="fa fa-circle-xmark"></i> إزالة المقعد</button>
                                                @endif
                                            @endif
                                        </div>
                                    @endfor
                                    @if($assigned->isEmpty() && !$emptySlots)<div class="om-slot"><span class="om-slot-empty">لا توجد مقاعد على هذه الزيارة</span></div>@endif
                                    <div class="om-drivers">
                                        <div><i class="fa fa-van-shuttle"></i> التوصيل: {{ $visit->deliveryDriver?->name ?: 'غير مخصص' }} <span class="om-ltr om-num">{{ $visit->delivery_at ?: $visit->scheduled_start_at?->format('H:i') }}</span></div>
                                        <div><i class="fa fa-car-side"></i> الاستلام: {{ $visit->pickupDriver?->name ?: 'غير مخصص' }} <span class="om-ltr om-num">{{ $visit->pickup_at ?: $visit->scheduled_end_at?->format('H:i') }}</span></div>
                                        <div><i class="fa fa-user-tie"></i> قائد الفريق: {{ $visit->teamLeader?->name ?: 'غير مخصص' }}</div>
                                    </div>
                                    @include('admin.hourly._visit-actions', ['group' => 'staff'])
                                </td>
                                <td>
                                    <span class="om-badge {{ $visitClass }}"><i class="fa {{ $visitIcon }}"></i> {{ __('hourly.'.$visit->status) }}</span>
                                    @if($assigned->isEmpty())<div class="om-kv mt-2"><span style="color:#df2b2b;font-weight:700">غير مخصص</span></div>@endif
                                </td>
                                <td>@include('admin.hourly._visit-actions', ['group' => 'row'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="om-empty"><i class="fa fa-calendar-xmark"></i> لا توجد زيارات على هذا الطلب.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="om-status" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-status">
                @if($order->statusLogs->isEmpty())
                    <div class="om-empty"><i class="fa fa-timeline"></i> لا توجد سجلات حالة بعد.</div>
                @else
                    <ul class="om-timeline">
                        @foreach($order->statusLogs->sortByDesc('id') as $log)
                            @php [$toClass, $toIcon] = $statusTone($log->to_status); @endphp
                            <li>
                                <div class="om-tl-time om-ltr om-num">{{ $log->created_at?->format('Y-m-d H:i') }}</div>
                                <div class="om-tl-flow">
                                    @if($log->from_status)<span class="om-badge om-b-grey">{{ __('hourly.'.$log->from_status) }}</span><i class="fa fa-arrow-left" style="color:#94a3b8"></i>@endif
                                    <span class="om-badge {{ $toClass }}"><i class="fa {{ $toIcon }}"></i> {{ __('hourly.'.$log->to_status) }}</span>
                                </div>
                                @if($log->reason)<p>{{ $log->reason }}</p>@endif
                                <div class="om-tl-time mt-1"><i class="fa fa-user"></i> {{ $log->actor_type === 'admin' ? 'موظف' : ($log->actor_type === 'client' ? 'العميل' : $log->actor_type) }}@if($log->actor_id) <span class="om-ltr">#{{ $log->actor_id }}</span>@endif</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @include('admin.hourly._order-audit-reviews')

            <section id="om-notes" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-notes">
                @if($order->notes)<div class="om-note"><p>{{ $order->notes }}</p><small>ملاحظة مرفقة بالطلب عند الإنشاء</small></div>@endif
                @forelse($notes->reverse() as $note)
                    <div class="om-note">
                        <span class="om-badge {{ $note['visit_id'] ? 'om-b-blue' : 'om-b-grey' }}">{{ $note['visit_id'] ? 'زيارة #'.$note['visit_id'] : 'الطلب بالكامل' }}</span>
                        <p class="mt-2">{{ $note['text'] }}</p>
                        <span class="badge bg-light text-dark">{{ __('operational_notes.'.$note['audience']) }}</span>
                        <small><i class="fa fa-user-pen"></i> {{ $note['author'] }} — <span class="om-ltr">{{ $note['created_at'] }}</span></small>
                    </div>
                @empty
                    @unless($order->notes)<div class="om-empty"><i class="fa fa-note-sticky"></i> لا توجد ملاحظات إضافية.</div>@endunless
                @endforelse
                @include('admin.hourly._note-form', ['visitId' => null])
            </section>

            <section id="om-payments" class="tab-pane" role="tabpanel" aria-labelledby="om-tab-payments">
                @include('admin.hourly._order-collection')
                <div class="table-responsive">
                    <table class="om-table">
                        <thead><tr><th>المبلغ</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th><th>المستندات</th></tr></thead>
                        <tbody>
                        @forelse($order->payments as $payment)
                            @php [$rowClass, $rowIcon] = $statusTone($payment->settlement_status); @endphp
                            <tr>
                                <td><b class="om-num">{{ $money($payment->amount) }}</b></td>
                                <td>{{ __('hourly.'.$payment->method) }}</td>
                                <td><span class="om-badge {{ $rowClass }}"><i class="fa {{ $rowIcon }}"></i> {{ __('hourly.'.$payment->settlement_status) }}</span></td>
                                <td class="om-ltr om-num">{{ optional($payment->paid_at)->format('Y-m-d H:i') ?: '—' }}</td>
                                <td>
                                    @if($payment->method === 'bank_transfer')
                                        @if(!empty($payment->provider_payload['reference_number']))
                                            <div class="om-kv"><span>رقم المرجع:</span><b class="om-ltr om-num">{{ $payment->provider_payload['reference_number'] }}</b></div>
                                        @endif
                                        @if(!empty($payment->provider_payload['rejection_reason']))
                                            <div class="alert alert-danger py-2 px-3 mb-2">سبب الرفض: {{ $payment->provider_payload['rejection_reason'] }}</div>
                                        @endif
                                    @endif
                                    @if($payment->status === 'paid' && (float) $payment->amount > 0)
                                        <a class="om-btn om-btn-soft" style="width:auto;margin:0 .15rem" target="_blank" href="{{ route('admin.hourly-orders.receipt', [$order, $payment]) }}"><i class="fa fa-receipt"></i> سند قبض</a>
                                    @endif
                                    @if($payment->attachment_path)
                                        <a class="om-btn om-btn-soft" style="width:auto;margin:0 .15rem" target="_blank" href="{{ route('admin.hourly-orders.attachment', [$order, $payment]) }}"><i class="fa fa-paperclip"></i> عرض الإيصال</a>
                                    @elseif($payment->status !== 'paid' || (float) $payment->amount <= 0)
                                        —
                                    @endif
                                    @if($payment->method === 'bank_transfer' && $payment->status === 'pending_review' && AdminGuardHasPerm('hourly_orders_collect'))
                                        <div class="d-flex gap-2 flex-wrap mt-2">
                                            <form method="post" action="{{ route('admin.hourly-orders.bank-transfer-review',[$order,$payment]) }}" data-confirm-title="قبول التحويل" data-confirm="اعتماد التحويل وتسجيل المبلغ مدفوعًا؟" data-confirm-button="نعم، اعتماد" data-confirm-danger="0">
                                                @csrf
                                                <input type="hidden" name="decision" value="accept">
                                                <button class="btn btn-sm btn-success" type="submit"><i class="fa fa-check"></i> قبول التحويل</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.hourly-orders.bank-transfer-review',[$order,$payment]) }}" class="d-flex gap-1">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <input class="form-control form-control-sm" name="rejection_reason" required maxlength="1000" placeholder="سبب الرفض">
                                                <button class="btn btn-sm btn-danger" type="submit"><i class="fa fa-xmark"></i> رفض</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="om-empty"><i class="fa fa-receipt"></i> لا توجد دفعات مسجلة.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </div>
    </div>
</main>
</div>
</div>
@endsection
@push('js')
@include('admin.hourly._confirm-script')
<script src="{{ asset('admin/assets/js/hourly-order-operations.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-order-operations.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = [...document.querySelectorAll('[id^="om-tab-"]')];
    const active = tabs.find(tab => tab.dataset.bsTarget === location.hash);
    if (active && window.bootstrap) bootstrap.Tab.getOrCreateInstance(active).show();
    tabs.forEach(tab => tab.addEventListener('shown.bs.tab', () => history.replaceState(null, '', tab.dataset.bsTarget)));

    const copy = document.getElementById('om-copy-card');
    if (copy) copy.addEventListener('click', async function () {
        const text = ['om-customer-name', 'om-customer-phone']
            .map(id => document.getElementById(id)).filter(Boolean)
            .map(node => node.innerText.trim()).join('\n');
        try { await navigator.clipboard.writeText(text); this.innerHTML = '<i class="fa fa-check"></i>'; }
        catch (_) { this.innerHTML = '<i class="fa fa-xmark"></i>'; }
        setTimeout(() => { this.innerHTML = '<i class="fa fa-copy"></i>'; }, 1500);
    });
});
</script>
@endpush
