@extends('admin.layout.indexs.index')
@section('page-title', 'إنشاء طلب ساعات')

@section('style')
@include('admin.hourly_teams._style')
<style>
    /* ===== معالج الحجز: كل خطوة لوحة مستقلة، والملخص يرافق الخطوات ===== */
    .bk-shell { max-width: 1500px; }
    .bk-shell [hidden] { display: none !important; }
    .bk-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.25rem; align-items: start; }

    /* شريط الخطوات */
    .bk-steps { display: flex; flex-wrap: wrap; background: #fff; border-radius: 18px; box-shadow: 0 14px 40px rgba(15, 23, 42, .08); padding: .85rem 1rem; margin-bottom: 1.25rem; }
    .bk-step { position: relative; flex: 1 1 160px; display: flex; align-items: center; gap: .6rem; padding: .4rem .75rem; font-size: .84rem; font-weight: 600; color: var(--ht-muted); }
    .bk-step + .bk-step::before { content: ''; position: absolute; inset-inline-start: 0; top: 50%; width: 1px; height: 26px; transform: translateY(-50%); background: var(--ht-line); }
    .bk-step-dot { width: 30px; height: 30px; flex: 0 0 30px; border-radius: 50%; display: grid; place-items: center; background: #eef2f7; color: #64748b; font-size: .8rem; font-style: normal; transition: background .2s, color .2s, box-shadow .2s; }
    .bk-step-dot em { display: none; font-size: .72rem; }
    .bk-step.current { color: var(--ht-ink); }
    .bk-step.current .bk-step-dot { background: #6861ee; color: #fff; box-shadow: 0 0 0 4px rgba(104, 97, 238, .15); }
    .bk-step.done { color: #166534; }
    .bk-step.done .bk-step-dot { background: #dcfce7; color: #166534; }
    .bk-step.done .bk-step-dot b { display: none; }
    .bk-step.done .bk-step-dot em { display: block; }

    /* لوحة الخطوة */
    .bk-main { min-height: 520px; display: flex; flex-direction: column; }
    .bk-panel { padding: 1.35rem 1.35rem 0; flex: 1 1 auto; }
    .bk-panel-head { display: flex; align-items: flex-start; gap: .75rem; padding-bottom: .9rem; margin-bottom: 1.1rem; border-bottom: 1px solid #eef2f7; }
    .bk-panel-no { width: 34px; height: 34px; flex: 0 0 34px; border-radius: 11px; display: grid; place-items: center; background: #eef2ff; color: #4338ca; font-weight: 800; }
    .bk-panel-head h6 { margin: 0; font-weight: 700; color: var(--ht-ink); font-size: 1rem; }
    .bk-panel-head p { margin: .2rem 0 0; font-size: .8rem; color: var(--ht-muted); }
    .bk-actions { display: flex; gap: .75rem; align-items: center; padding: 1rem 1.35rem; margin-top: 1.25rem; background: var(--ht-soft); border-top: 1px solid #eef2f7; }
    .bk-actions .btn { min-width: 130px; }

    /* اختيار بين وضعين (عميل حالي / جديد) */
    .bk-segment { display: inline-flex; gap: .25rem; padding: .25rem; background: var(--ht-soft); border: 1px solid var(--ht-line); border-radius: 12px; }
    .bk-segment label { display: inline-flex; align-items: center; gap: .4rem; margin: 0; padding: .45rem .9rem; border-radius: 9px; font-size: .85rem; font-weight: 600; color: var(--ht-muted); cursor: pointer; }
    .bk-segment input { position: absolute; opacity: 0; width: 0; height: 0; }
    .bk-segment label:focus-within { outline: 2px solid #a5b4fc; outline-offset: 1px; }
    .bk-segment input:checked + span { color: #4338ca; }
    .bk-segment label:has(input:checked) { background: #fff; color: #4338ca; box-shadow: 0 4px 14px rgba(15, 23, 42, .08); }

    /* بطاقات الاختيار التي يبنيها السكربت (فئات، عناصر، طرق دفع) */
    .booking-options { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .75rem; }
    .booking-option { display: block; width: 100%; text-align: start; background: #fff; border: 1px solid var(--ht-line); border-radius: 14px; padding: .9rem 1rem; cursor: pointer; transition: border-color .15s, box-shadow .15s, background .15s; color: var(--ht-ink); }
    .booking-option:hover:not(:disabled) { border-color: #c7d2fe; box-shadow: 0 8px 22px rgba(15, 23, 42, .07); }
    .booking-option.selected { border-color: #6861ee; background: #f5f4ff; box-shadow: 0 8px 22px rgba(104, 97, 238, .14); }
    .booking-option:disabled { opacity: .55; cursor: not-allowed; background: var(--ht-soft); }
    .booking-option strong { display: block; font-size: .95rem; }
    .booking-option .mt-2 { font-size: .8rem; color: var(--ht-muted); }
    #payment-methods { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
    #payment-methods .booking-option { display: flex; align-items: center; gap: .55rem; font-weight: 600; }
    #payment-methods .booking-option:has(input:checked) { border-color: #6861ee; background: #f5f4ff; }

    /* حقول */
    .booking-fields { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .85rem; }
    .booking-fields .wide { grid-column: 1 / -1; }
    #new-customer { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem 2rem; }
    .bk-customer-tabs { display: flex; border-bottom: 1px solid var(--ht-line); }
    .bk-customer-tabs button { background: transparent; border: 1px solid transparent; padding: .75rem 1rem; color: #64748b; border-radius: 5px 5px 0 0; }
    .bk-customer-tabs button[aria-selected="true"] { border-color: var(--ht-line); border-bottom-color: white; margin-bottom: -1px; color: #4338ca; background: white; }
    .bk-customer-tabs i { color: #edbf16; margin-inline-end: .5rem; }
    #new-customer .form-control, #new-customer .form-select { min-height: 48px; background-color: #f7f8fc; }
    .bk-customer-phone { display: flex; direction: rtl; }
    .bk-customer-phone select { width: 115px; flex-shrink: 0; }
    .bk-customer-phone input { min-width: 0; direction: ltr; }
    @media(max-width:600px) { #new-customer { grid-template-columns: 1fr; } }
    .bk-shell label { display: block; font-size: .82rem; font-weight: 600; color: #475569; margin-bottom: .3rem; }
    .bk-shell .bk-check { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .85rem; border: 1px solid var(--ht-line); border-radius: 11px; background: var(--ht-soft); margin: 0 0 .5rem; font-weight: 600; color: #475569; cursor: pointer; }
    .bk-shell .bk-segment label, .bk-shell .bk-inline > label { display: inline-flex; align-items: center; margin-bottom: 0; }
    .bk-check input { margin: 0; }
    .bk-inline { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }

    /* عدّاد مقدمي الخدمة */
    .bk-counter { display: inline-flex; align-items: center; gap: .85rem; padding: .4rem .5rem; border: 1px solid var(--ht-line); border-radius: 12px; background: #fff; }
    .bk-counter button { width: 34px; height: 34px; border-radius: 10px; padding: 0; line-height: 1; }
    .bk-counter strong { min-width: 2ch; text-align: center; font-size: 1.15rem; font-variant-numeric: tabular-nums; }

    /* التقويم والفترات */
    #booking-calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: .3rem; }
    #booking-calendar small { text-align: center; padding: .4rem 0; font-size: .72rem; color: var(--ht-muted); font-weight: 700; }
    #booking-calendar button { border: 1px solid transparent; border-radius: 10px; padding: .55rem 0; background: var(--ht-soft); color: var(--ht-ink); font-size: .85rem; font-variant-numeric: tabular-nums; }
    #booking-calendar button:hover { border-color: #c7d2fe; }
    #booking-calendar button.selected { background: #6861ee; color: #fff; font-weight: 700; }
    .booking-slots { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; direction: rtl; }
    .booking-slots button { font-variant-numeric: tabular-nums; direction: ltr; }
    .bk-schedule { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; direction: rtl; }
    .bk-schedule h6 { font-size: .95rem; margin-bottom: 1rem; }
    .bk-calendar-box { border: 1px solid var(--ht-line); border-radius: 5px; padding: 1rem; }
    .bk-calendar-box #booking-calendar { direction: ltr; gap: .15rem; }
    .bk-calendar-box #booking-calendar button { background: transparent; border-radius: 5px; }
    .bk-calendar-box #booking-calendar button.selected { background: #6861ee; color: white; }
    .bk-calendar-box #booking-calendar button.outside-month { color: #9ca3af; }
    .bk-calendar-box #booking-calendar button:disabled { opacity: .45; cursor: not-allowed; }
    .bk-calendar-box .bk-cal-head { direction: ltr; }
    .booking-slots button { border: 1px solid #e5e7eb; border-radius: 0; min-height: 46px; color: #425777; background: white; }
    .booking-slots button.btn-primary { background: #0075a9; color: white; border-color: #0075a9; }
    .booking-slots button.slot-available { background: #ecfdf5; color: #166534; border-color: #bbf7d0; }
    .booking-slots button:disabled { background: #f3f4f6; color: #9ca3af; }
    .booking-slots button small { display: block; font-size: .7rem; margin-top: .2rem; }
    .bk-schedule-price { display: flex; align-items: center; gap: .75rem; border-top: 1px solid var(--ht-line); margin-top: 1.5rem; padding-top: 1rem; }
    .bk-schedule-price output { background: #ff9800; color: white; font-weight: 700; font-size: 1.2rem; border-radius: 6px; padding: .25rem .65rem; }
    @media (max-width: 767px) { .bk-schedule { grid-template-columns: 1fr; } }
    .bk-cal-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
    .bk-cal-head strong { font-size: .95rem; }
    #visit-dates { display: grid; gap: .35rem; }
    #visit-dates p { margin: 0; padding: .5rem .75rem; border: 1px solid var(--ht-line); border-radius: 10px; background: var(--ht-soft); font-size: .82rem; }

    /* بطاقة الزيارة في خطوة الإسناد */
    .booking-visit { border: 1px solid var(--ht-line); border-radius: 14px; padding: 1rem; margin-bottom: .85rem; background: #fff; }
    .booking-visit > strong { display: block; margin-bottom: .6rem; color: var(--ht-ink); }

    /* الخريطة */
    #booking-map { height: 450px; background: var(--ht-soft); border: 1px solid var(--ht-line); border-radius: 14px; margin: 1rem 0; }
    .bk-address-search { display: flex; border: 1px solid #cdd8e7; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px #162d4510; }
    .bk-address-search .form-control { border: 0; min-height: 54px; box-shadow: none; }
    .bk-address-search button { border: 0; background: white; color: #e9b916; padding: 0 18px; }
    .bk-map-link { background: #f7f9fc; border: 1px solid #dfe6f1; border-radius: 12px; margin-top: 14px; padding: 14px 16px; }
    .bk-map-link summary { cursor: pointer; color: #526781; }
    .bk-map-link summary i { color: #e9b916; margin-inline-end: 8px; }
    .bk-map-hint { background: #eaf2ff; border: 1px solid #c6d9ff; color: #205599; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
    .bk-address-location, .bk-address-contacts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 28px; }
    .bk-address-details { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; border-top: 1px solid #e5e9ef; margin-top: 28px; padding-top: 24px; }
    .bk-address-details textarea { min-height: 235px; resize: vertical; }
    #new-address .form-control, #new-address .form-select { min-height: 48px; }
    #saved-addresses tr.selected { background: #e6f6ef; }
    .bk-address-coordinates { margin-top: 18px; color: #64748b; }
    .bk-address-coordinates summary { cursor: pointer; }
    @media(max-width:700px) { .bk-address-details, .bk-address-location, .bk-address-contacts { grid-template-columns: 1fr; gap: 16px; } #booking-map { height: 330px; } }

    /* الملخص الجانبي */
    .bk-summary { position: sticky; top: 90px; }
    .bk-summary-body { padding: 1.1rem 1.2rem; }
    #booking-summary { display: grid; gap: .6rem; margin: 0; }
    #booking-summary div { display: grid; gap: .1rem; }
    #booking-summary dt { font-size: .74rem; color: var(--ht-muted); font-weight: 600; }
    #booking-summary dd { margin: 0; font-size: .87rem; font-weight: 600; color: var(--ht-ink); overflow-wrap: anywhere; }
    #price-summary p { margin: 0 0 .3rem; font-size: .85rem; display: flex; justify-content: space-between; gap: .5rem; }
    #price-summary .total { font-size: 1.6rem; font-weight: 800; color: #166534; font-variant-numeric: tabular-nums; }
    .booking-muted { color: var(--ht-muted); font-size: .8rem; }
    .booking-error { white-space: pre-line; }
    /* شريط التجديد أعلى المعالج */
    .bk-renewal { display: flex; gap: .85rem; align-items: flex-start; background: linear-gradient(180deg, #eff8ff, #fff); border: 1px solid #c4e2f8; border-inline-start: 4px solid #2794eb; border-radius: 14px; padding: .9rem 1.1rem; margin-bottom: 1rem; }
    .bk-renewal-icon { width: 38px; height: 38px; flex: 0 0 38px; border-radius: 11px; display: grid; place-items: center; background: #2794eb; color: #fff; }
    .bk-renewal-body { flex: 1; min-width: 0; }
    .bk-renewal-body b { font-size: 1rem; color: #14507f; }
    .bk-renewal-body b a { color: #1b6fb8; text-decoration: none; }
    .bk-renewal-body b a:hover { text-decoration: underline; }
    .bk-renewal-body small { display: block; color: #5b6b7d; margin-top: .2rem; }
    .bk-renewal-warn { margin: .6rem 0 0; background: #fef8ec; border: 1px solid #f7ddb2; color: #8a5806; border-radius: 10px; padding: .5rem .75rem; font-size: .85rem; }
    .bk-renewal-notices { list-style: none; margin: .6rem 0 0; padding: 0; display: grid; gap: .35rem; }
    .bk-renewal-notices li { display: flex; gap: .45rem; align-items: flex-start; background: #fef8ec; border: 1px solid #f7ddb2; color: #8a5806; border-radius: 10px; padding: .45rem .7rem; font-size: .84rem; }
    .bk-renewal-notices li.is-info { background: #eff8ff; border-color: #c4e2f8; color: #14507f; }
    .bk-empty-summary { color: var(--ht-muted); font-size: .82rem; }

    @media (max-width: 1100px) {
        .bk-grid { grid-template-columns: 1fr; }
        .bk-summary { position: static; }
    }
</style>
@endsection

@section('content')
@php
    // ترتيب الخطوات يعكس منطق الحجز: العميل ← عنوانه ← ما يغطيه هذا العنوان ← تفاصيله ← مواعيده ← دفعه
    $steps = [
        ['العميل', 'fa-user'],
        ['العنوان', 'fa-location-dot'],
        ['الخدمة', 'fa-layer-group'],
        ['تفاصيل الخدمة', 'fa-sliders'],
        ['التاريخ والوقت', 'fa-calendar-days'],
        ['الدفع والتأكيد', 'fa-credit-card'],
    ];
@endphp

<div class="container-fluid ht-page bk-shell" id="booking-app" dir="rtl">

    <div class="ht-card"><div class="ht-head">
        <div class="ht-head-icon"><i class="fa fa-calendar-plus"></i></div>
        <div>
            <h5>إنشاء طلب ساعات</h5>
            <p>معالج من ست خطوات؛ البيانات محفوظة في هذا التبويب فقط ولا يُنشأ الطلب إلا عند الضغط على «تأكيد الطلب».</p>
        </div>
        <div class="ht-actions">
            @if(AdminGuardHasPerm('hourly_orders_view'))
                <a class="btn btn-outline-secondary rounded-3" href="{{ route('admin.hourly-orders.index') }}"><i class="fa fa-list ms-1"></i> كل الطلبات</a>
            @endif
        </div>
    </div></div>

    @if($renewal || $renewalBlocked)
        @php $renewalInfo = $renewal ?? $renewalBlocked; @endphp
        <section class="bk-renewal" aria-label="بيانات التجديد">
            <span class="bk-renewal-icon"><i class="fa fa-rotate"></i></span>
            <div class="bk-renewal-body">
                <b>تجديد للطلب <a href="{{ $renewalInfo['order_url'] }}" target="_blank" rel="noopener" dir="ltr">#{{ $renewalInfo['order_number'] }}</a></b>
                <small>
                    انتهاء العقد السابق: <span dir="ltr">{{ $renewalInfo['contract_end'] ?? '—' }}</span>
                    @if($renewalInfo['pattern']) · النمط المعتاد: {{ $renewalInfo['pattern'] }} @endif
                    @if($renewal && !empty($renewal['visits'])) · أول موعد مقترح: <span dir="ltr">{{ $renewal['visits'][0] }}</span> @endif
                </small>
                @if($renewalInfo['renewed_by'])
                    <p class="bk-renewal-warn" role="alert"><i class="fa fa-triangle-exclamation"></i>
                        هذا الطلب جُدِّد سابقًا بالطلب <a href="{{ $renewalInfo['renewed_by']['url'] }}" dir="ltr">#{{ $renewalInfo['renewed_by']['order_number'] }}</a>.
                        {{ $renewalBlocked ? 'تجديده مرة أخرى يتطلب صلاحية «تجديد طلب سبق تجديده»، لذلك لم تُعبّأ بيانات التجديد.' : 'لديك صلاحية تجديده مرة أخرى، وسيُسجَّل ذلك في سجل الطلبين.' }}
                    </p>
                @endif
                <ul class="bk-renewal-notices" id="renewal-notices" aria-live="polite" hidden></ul>
            </div>
        </section>
    @endif

    <nav class="bk-steps" aria-label="مراحل الحجز">
        @foreach($steps as [$label, $icon])
            <span class="bk-step" data-step-label="{{ $loop->iteration }}">
                <i class="bk-step-dot"><b>{{ $loop->iteration }}</b><em class="fa fa-check"></em></i>
                <span><i class="fa {{ $icon }} ms-1"></i> {{ $label }}</span>
            </span>
        @endforeach
    </nav>

    <div id="booking-error" class="alert alert-danger booking-error" role="alert" hidden></div>

    <div class="bk-grid">
        <main class="ht-card bk-main">

            <section data-step="1" class="bk-panel">
                <header class="bk-panel-head">
                    <span class="bk-panel-no">1</span>
                    <div><h6>البحث عن العميل</h6><p>اختر عميلًا مسجَّلًا لتظهر عناوينه ورصيد محفظته، أو أنشئ حسابًا جديدًا يُحفظ مع الطلب.</p></div>
                </header>
                <div class="bk-segment mb-3">
                    <label><input type="radio" name="customer_mode" value="existing" checked><span><i class="fa fa-user-check ms-1"></i> عميل حالي</span></label>
                    <label><input type="radio" name="customer_mode" value="new"><span><i class="fa fa-user-plus ms-1"></i> عميل جديد</span></label>
                </div>
                <div id="existing-customer">
                    <div class="d-flex gap-2 mb-3">
                        <input id="customer-search" class="form-control" placeholder="ابحث بالاسم أو الإيميل أو الجوال" aria-label="البحث عن العميل">
                        <button type="button" id="search-customers" class="btn btn-outline-primary rounded-3 text-nowrap"><i class="fa fa-magnifying-glass ms-1"></i> بحث</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>#</th><th>الاسم</th><th>الإيميل</th><th>الجوال</th><th>الرصيد</th><th></th></tr></thead>
                            <tbody id="customer-results"></tbody>
                        </table>
                    </div>
                    <div class="bk-inline" id="customer-pages"></div>
                </div>
                <form id="new-customer" class="booking-fields" hidden autocomplete="off">
                    <input type="hidden" id="customer-type" name="type" value="individual">
                    <div class="bk-customer-tabs wide" role="tablist" aria-label="نوع العميل">
                        <button type="button" role="tab" data-customer-type="individual" aria-selected="true"><i class="fa fa-user"></i>فرد</button>
                        <button type="button" role="tab" data-customer-type="company" aria-selected="false"><i class="fa fa-building"></i>شركة</button>
                    </div>
                    <div data-customer-field="individual"><label for="customer-first_name">الاسم الأول <span class="text-danger">*</span></label><input id="customer-first_name" name="first_name" class="form-control" maxlength="100" required data-required></div>
                    <div data-customer-field="individual"><label for="customer-last_name">اسم العائلة <span class="text-danger">*</span></label><input id="customer-last_name" name="last_name" class="form-control" maxlength="100" required data-required></div>
                    <div data-customer-field="company" hidden><label for="customer-company_name">اسم الشركة <span class="text-danger">*</span></label><input id="customer-company_name" name="company_name" class="form-control" maxlength="200" disabled data-required></div>
                    <div data-customer-field="company" hidden><label for="customer-company_id">رقم هوية الشركة <small class="text-muted">(اختياري)</small></label><input id="customer-company_id" name="company_id" class="form-control" maxlength="100" disabled></div>
                    <div><label for="customer-email">عنوان البريد الإلكتروني <span class="text-danger">*</span></label><input id="customer-email" name="email" type="email" class="form-control" placeholder="email@email.com" maxlength="255" required></div>
                    <div><label for="customer-phone">رقم الجوال <span class="text-danger">*</span></label><div class="bk-customer-phone"><select name="phone_code" class="form-select" aria-label="مفتاح الدولة"><option value="966">🇸🇦 +966</option></select><input id="customer-phone" name="phone" type="tel" inputmode="numeric" pattern="5[0-9]{8}" maxlength="9" placeholder="501234567" class="form-control" aria-describedby="customer-phone-hint" required></div><small id="customer-phone-hint" class="text-muted">9 أرقام تبدأ بالرقم 5</small></div>
                    <div><label for="customer-password">كلمة المرور <span class="text-danger">*</span></label><input id="customer-password" name="password" type="password" class="form-control" minlength="8" maxlength="100" autocomplete="new-password" required></div>
                    <div data-customer-field="individual"><label for="customer-birthdate">تاريخ الميلاد <small class="text-muted">(اختياري)</small></label><input id="customer-birthdate" name="birthdate" type="date" max="{{ now()->toDateString() }}" class="form-control"></div>
                    <div data-customer-field="individual"><label for="customer-identity">رقم الهوية / الجواز <small class="text-muted">(اختياري)</small></label><input id="customer-identity" name="identity" class="form-control" maxlength="100"></div>
                    <div><label for="customer-language">لغة التواصل المفضلة <small class="text-muted">(اختياري)</small></label><select id="customer-language" name="language" class="form-select"><option value="">اختر اللغة...</option><option value="ar">العربية</option><option value="en">English</option></select></div>
                    <div class="wide" data-customer-field="company" hidden><label for="customer-note">ملاحظة</label><textarea id="customer-note" name="note" class="form-control" rows="4" maxlength="2000" placeholder="مسؤول التواصل 1 (الاسم ورقم الهاتف)&#10;مسؤول التواصل 2 (الاسم ورقم الهاتف)&#10;مسؤول التواصل 3 (الاسم ورقم الهاتف)" disabled></textarea></div>
                </form>
            </section>

            <section data-step="2" class="bk-panel" hidden>
                <header class="bk-panel-head">
                    <span class="bk-panel-no">2</span>
                    <div><h6>الخطوة 2: حدد العنوان</h6><p>اختر عنوان العميل المحفوظ أو أضف عنوانًا جديدًا.</p></div>
                    <button type="button" id="toggle-address" class="btn btn-outline-primary rounded-3 ms-auto text-nowrap">إضافة عنوان جديد</button>
                </header>
                <div id="saved-addresses"></div>
                <form id="new-address" hidden>
                    <div class="bk-address-search">
                        <input id="map-search" class="form-control" placeholder="ابحث عن العنوان بالاسم أو المنطقة" aria-label="البحث عن العنوان بالاسم أو المنطقة">
                        <button type="button" id="locate-address" aria-label="البحث عن الموقع"><i class="fa fa-search"></i></button>
                    </div>
                    <details class="bk-map-link"><summary><i class="fa fa-link"></i> أو الصق رابط Google Maps بدلًا من البحث</summary><div class="d-flex gap-2 mt-3"><input id="map-url" type="url" class="form-control" placeholder="https://maps.app.goo.gl/..." aria-label="رابط Google Maps" dir="ltr"><button type="button" id="locate-map-url" class="btn btn-outline-primary text-nowrap">تحديد الموقع</button></div></details>
                    <div id="booking-map"></div>
                    <p id="map-hint" class="bk-map-hint" role="status">📍 اضغط على الخريطة أو استخدم البحث لتحديد الموقع بدقة.</p>
                    <div class="bk-address-location">
                        <div><label for="address-city">المدينة <span class="text-danger">*</span></label>
                            <select id="address-city" name="city_id" class="form-select" required><option value="">اختر المدينة</option>@foreach($cities as $city)<option value="{{ $city->id }}">{{ $city->name_ar }}</option>@endforeach</select>
                        </div>
                        <div><label for="address-district">المنطقة / الحي <span class="text-danger">*</span></label><select id="address-district" name="hourly_district_id" class="form-select" required></select></div>
                    </div>
                    <div class="bk-address-details">
                        <div class="bk-address-contacts">
                        @foreach(['building'=>'اسم المبنى / الفيلا','unit'=>'رقم الوحدة','contact_first_name'=>'الاسم الأول','contact_last_name'=>'اسم العائلة','contact_phone'=>'رقم الجوال','contact_phone2'=>'الهاتف 2'] as $name => $label)
                            @php $optional = $name === 'contact_phone2'; @endphp
                            <div>
                                <label for="address-{{ $name }}">{{ $label }} @unless($optional)<span class="text-danger">*</span>@endunless</label>
                                <input id="address-{{ $name }}" name="{{ $name }}" class="form-control" maxlength="{{ str_starts_with($name,'contact_phone') ? 30 : (str_starts_with($name,'contact_') ? 100 : 255) }}" @unless($optional) required @endunless @if(str_starts_with($name,'contact_phone')) type="tel" dir="ltr" @endif>
                            </div>
                        @endforeach
                        </div>
                        <div><label for="address-text">تفاصيل العنوان <span class="text-danger">*</span></label><textarea id="address-text" name="address" rows="7" maxlength="500" class="form-control" placeholder="حدد الموقع على الخريطة لتعبئة العنوان تلقائيًا. يمكنك أيضًا كتابة تفاصيل العنوان هنا." required></textarea><small class="text-muted">تلميح: الضغط على علامة الموقع يعبئ العنوان تلقائيًا.</small></div>
                    </div>
                    <details id="address-coordinates" class="bk-address-coordinates"><summary>إحداثيات الموقع / إدخال يدوي</summary><div class="bk-address-location mt-3"><div><label for="address-latitude">خط العرض</label><input id="address-latitude" name="latitude" type="number" step="any" min="-90" max="90" class="form-control"></div><div><label for="address-longitude">خط الطول</label><input id="address-longitude" name="longitude" type="number" step="any" min="-180" max="180" class="form-control"></div></div></details>
                </form>
            </section>

            <section data-step="3" class="bk-panel" hidden>
                <header class="bk-panel-head">
                    <span class="bk-panel-no">3</span>
                    <div><h6>اختيار الخدمة</h6><p>تُعرض الفئات كلها، والمعطَّل منها لا يغطي حي العنوان المختار.</p></div>
                </header>
                <div id="service-categories" class="booking-options"></div>
            </section>

            <section data-step="4" class="bk-panel" hidden>
                <header class="bk-panel-head">
                    <span class="bk-panel-no">4</span>
                    <div><h6>تفاصيل الخدمة</h6><p>الباقة تحدد عدد الزيارات وساعاتها، وعدد مقدمي الخدمة يضاعف الساعات والسعر.</p></div>
                </header>
                <div class="bk-inline mb-3">
                    <button type="button" class="btn btn-outline-primary rounded-3" data-item-tab="general">General Service Item</button>
                    <button type="button" class="btn btn-outline-primary rounded-3" data-item-tab="special">Special Service Item</button>
                </div>
                <div id="service-items" class="booking-options"></div>
                <div id="booking-count-fields">
                <div class="bk-inline my-4" id="booking-workers-field">
                    <label class="mb-0" id="booking-workers-label">عدد مقدمي الخدمة</label>
                    <span class="bk-counter">
                        <button type="button" id="workers-minus" class="btn btn-outline-secondary" aria-label="تقليل عدد مقدمي الخدمة">−</button>
                        <strong id="workers-number">1</strong>
                        <button type="button" id="workers-plus" class="btn btn-outline-secondary" aria-label="زيادة عدد مقدمي الخدمة">+</button>
                    </span>
                    <span id="total-hours" class="booking-muted"></span>
                </div>
                <div class="my-3" id="booking-quantity-field" hidden>
                    <label for="booking-quantity" id="booking-quantity-label">الكمية</label>
                    <input id="booking-quantity" type="number" min="1" max="50" value="1" class="form-control">
                </div>
                </div>
                <div id="booking-service-options" class="my-3"></div>
                <div id="booking-service-notes" class="booking-muted my-3"></div>
                @if($canPrice)
                    <label class="bk-check" for="override-price"><input type="checkbox" id="override-price"> <i class="fa fa-pen-to-square"></i> تعديل السعر الإجمالي يدويًا</label>
                    <input id="manual-total" type="number" min="0.01" step="0.01" class="form-control" aria-label="السعر الإجمالي اليدوي" hidden>
                @endif
            </section>

            <section data-step="5" class="bk-panel" hidden>
                <header class="bk-panel-head">
                    <span class="bk-panel-no">5</span>
                    <div><h6>التاريخ والوقت</h6><p>لكل زيارة موعد مستقل؛ الفترات المعروضة متاحة فعلًا بعد التحقق من شواغر الفريق ومهلة الحجز.</p></div>
                </header>
                <div id="visit-selector">
                <label for="visit-index">الزيارة التي تحدد موعدها</label>
                <select id="visit-index" class="form-select mb-3"></select>
                </div>
                <div class="bk-schedule">
                <div>
                <h6>حدد وقت الخدمة</h6>
                <div class="bk-calendar-box">
                <div class="bk-cal-head">
                    <button type="button" id="previous-month" class="btn btn-light rounded-3" aria-label="الشهر السابق">‹</button>
                    <strong id="calendar-title"></strong>
                    <button type="button" id="next-month" class="btn btn-light rounded-3" aria-label="الشهر التالي">›</button>
                </div>
                <div id="booking-calendar" class="booking-calendar"></div>
                </div>
                </div>
                <div>
                <h6>تحديد الفترة</h6>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <button type="button" id="first-available-slot" class="btn btn-outline-success btn-sm" disabled>اختيار أول فترة متاحة في اليوم</button>
                    <label class="mb-0"><input type="checkbox" id="available-slots-only" checked> المتاح فقط</label>
                </div>
                <div id="booking-slots" class="booking-slots"></div>
                <p id="availability-status" class="booking-muted my-3" role="status">اختر يومًا من التقويم لعرض الفترات المتاحة.</p>
                </div>
                </div>
                <div id="visit-dates" class="mt-4"></div>
                <div class="bk-schedule-price">
                    <strong>السعر الكلي:</strong>
                    <output id="schedule-total" aria-live="polite"></output>
                    @if($canPrice)
                    <button type="button" id="schedule-edit-price" class="btn btn-outline-warning" aria-label="تعديل السعر الإجمالي" title="تعديل السعر الإجمالي"><i class="fa fa-pen"></i></button>
                    @endif
                </div>
            </section>

            <section data-step="6" class="bk-panel" hidden>
                <header class="bk-panel-head">
                    <span class="bk-panel-no">6</span>
                    <div><h6>الدفع والتأكيد</h6><p>عيّن مقدمي الخدمة والسائقين لكل زيارة، ثم اختر طريقة الدفع. يُنشأ الطلب عند التأكيد فقط.</p></div>
                </header>
                <p id="visits-total" class="booking-muted"></p>
                <label class="bk-check" for="all-workers"><input type="checkbox" id="all-workers"> Show All Workers (No Team Filter)</label>
                <div id="worker-assignments"></div>
                <p id="booking-auto-assign" class="booking-muted" hidden>سيعيّن النظام عاملة متاحة تلقائيًا عند ترك الاختيار فارغًا.</p>

                <p class="hd-section-title mt-4">نوع الدفع</p>
                <div id="payment-methods" class="booking-options"></div>
                @if($canCollect)
                    <label class="bk-check mt-3" id="collect-label" for="collect-now"><input type="checkbox" id="collect-now"> <i class="fa fa-hand-holding-dollar"></i> تحصيل الدفعة الآن</label>
                @endif
                <div id="cash-part" class="mb-3" hidden>
                    <label for="cash-amount">المبلغ النقدي (الباقي بطاقة)</label>
                    <input id="cash-amount" type="number" min="0.01" step="0.01" class="form-control">
                </div>

                <p class="hd-section-title mt-4">الإشعارات والملاحظات</p>
                <div class="bk-inline">
                    <label class="bk-check" for="notify-email"><input type="checkbox" id="notify-email" checked> <i class="fa fa-envelope"></i> إرسال تأكيد الحجز بالإيميل</label>
                    <label class="bk-check" for="notify-app"><input type="checkbox" id="notify-app" checked> <i class="fa fa-bell"></i> إرسال إشعار التطبيق للعميل</label>
                </div>
                <label for="order-notes">ملاحظات الطلب</label>
                <textarea id="order-notes" class="form-control" rows="3" maxlength="5000"></textarea>
            </section>

            <div class="bk-actions">
                <button type="button" id="booking-back" class="btn btn-outline-secondary rounded-3" hidden><i class="fa fa-arrow-right ms-1"></i> العودة</button>
                <button type="button" id="booking-next" class="btn btn-primary rounded-3 ms-auto">استمرار <i class="fa fa-arrow-left me-1"></i></button>
            </div>
        </main>

        <aside class="ht-card bk-summary">
            <div class="ht-head">
                <div class="ht-head-icon"><i class="fa fa-receipt"></i></div>
                <div><h5>ملخص الحجز</h5><p>يُحدَّث مع كل خطوة</p></div>
            </div>
            <div class="bk-summary-body">
                <dl id="booking-summary"></dl>
                <div id="coupon-box" class="mt-3" hidden>
                    <label for="coupon-code">رمز الكوبون</label>
                    <div class="d-flex gap-2">
                        <input id="coupon-code" class="form-control" maxlength="100">
                        <button type="button" id="apply-coupon" class="btn btn-outline-primary rounded-3 text-nowrap">تطبيق</button>
                    </div>
                </div>
                <hr>
                <div id="price-summary" aria-live="polite"></div>
                <button type="button" class="btn btn-success w-100 rounded-3 mt-3" id="place-order" hidden><i class="fa fa-circle-check ms-1"></i> تأكيد الطلب</button>
            </div>
        </aside>
    </div>
</div>
@endsection

@section('js')
@php($bookingConfig = ['base' => url('/panel/add-order'), 'csrf' => csrf_token(), 'districts' => $districts, 'drivers' => $drivers, 'canCollect' => $canCollect, 'mapProvider' => $mapProvider, 'mapsKey' => $mapsKey, 'messages' => ['noServiceArea' => __('لا توجد منطقة خدمة مفعّلة لهذه الخدمة - راجع مناطق الخدمة')]])
<script>
window.hourlyBookingConfig = @json($bookingConfig);
window.hourlyBookingConfig.renewal = @json($renewal ?? null);
</script>
<script src="{{ asset('admin/assets/js/hourly-booking.js') }}?v={{ filemtime(public_path('admin/assets/js/hourly-booking.js')) }}"></script>
@endsection
