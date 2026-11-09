@extends('admin.layout.indexs.index')
@section('page-title', 'سجل الرسائل والإرسال')

@section('style')
<style>
    .msg-page {
        --mp-primary: #4f46e5; --mp-primary-dark: #3730a3; --mp-primary-soft: #eef2ff;
        --mp-border: #e5e7eb; --mp-muted: #6b7280; --mp-text: #111827; --mp-soft: #f9fafb;
        color: var(--mp-text);
    }

    /* ---------- الرأس ---------- */
    .msg-hero {
        position: relative; overflow: hidden; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap;
        padding: 24px 26px; margin-bottom: 18px; border-radius: 16px; color: #fff;
        background: linear-gradient(135deg, #4338ca 0%, #6d28d9 55%, #7c3aed 100%);
        box-shadow: 0 18px 40px -22px rgba(79, 70, 229, .7);
    }
    .msg-hero::after { content: ""; position: absolute; width: 260px; height: 260px; left: -60px; top: -120px; border-radius: 50%; background: rgba(255,255,255,.08); }
    .msg-hero-main { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }
    .msg-hero-icon { width: 56px; height: 56px; display: grid; place-items: center; border-radius: 16px; background: rgba(255,255,255,.16); font-size: 1.5rem; }
    .msg-hero h1 { margin: 0; color: #fff; font-size: 1.35rem; font-weight: 800; }
    .msg-hero p { margin: 4px 0 0; color: rgba(255,255,255,.85); font-size: .86rem; }
    .msg-hero-actions { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }
    .msg-hero-actions .btn { border-radius: 10px; font-weight: 700; font-size: .84rem; }
    .msg-hero-actions .btn-glass { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.3); color: #fff; }
    .msg-hero-actions .btn-glass:hover { background: rgba(255,255,255,.24); color: #fff; }

    /* ---------- المؤشرات ---------- */
    .msg-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .msg-kpi { display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--mp-border); border-radius: 14px; background: #fff; }
    .msg-kpi-icon { width: 44px; height: 44px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 12px; font-size: 1.1rem; }
    .msg-kpi span { display: block; color: var(--mp-muted); font-size: .78rem; }
    .msg-kpi strong { display: block; font-size: 1.35rem; font-weight: 800; line-height: 1.3; }
    .msg-kpi small { color: var(--mp-muted); font-size: .72rem; }
    .kpi-indigo .msg-kpi-icon { background: var(--mp-primary-soft); color: var(--mp-primary); }
    .kpi-green .msg-kpi-icon { background: #ecfdf3; color: #067647; }
    .kpi-blue .msg-kpi-icon { background: #eff8ff; color: #175cd3; }
    .kpi-red .msg-kpi-icon { background: #fef3f2; color: #b42318; }
    .kpi-red strong { color: #b42318; }

    /* ---------- القنوات ---------- */
    .msg-channels { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .msg-channel {
        display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 12px;
        border: 1px solid var(--mp-border); background: #fff; color: #374151; font-weight: 600; font-size: .84rem; text-decoration: none;
        transition: border-color .15s ease, background .15s ease;
    }
    .msg-channel:hover { border-color: #c7d2fe; color: var(--mp-text); }
    .msg-channel.active { background: var(--mp-primary); border-color: var(--mp-primary); color: #fff; }
    .msg-channel b { min-width: 22px; padding: 0 7px; border-radius: 999px; background: var(--mp-soft); color: var(--mp-muted); font-size: .72rem; line-height: 20px; text-align: center; }
    .msg-channel.active b { background: rgba(255,255,255,.22); color: #fff; }

    /* ---------- الفلاتر ---------- */
    .msg-card { border: 1px solid var(--mp-border); border-radius: 16px; background: #fff; margin-bottom: 16px; }
    .msg-card-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; padding: 14px 18px; border-bottom: 1px solid var(--mp-border); }
    .msg-card-head h2 { margin: 0; font-size: 1rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .msg-card-head h2 i { color: var(--mp-primary); }
    .msg-card-body { padding: 16px 18px; }
    .msg-filters label { display: block; margin-bottom: 5px; font-size: .78rem; font-weight: 700; color: #374151; }
    .msg-filters .form-control, .msg-filters .form-select { min-height: 42px; border-radius: 10px; border-color: #d1d5db; font-size: .86rem; }
    .msg-filters .form-control:focus, .msg-filters .form-select:focus { border-color: var(--mp-primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, .12); }
    .msg-search { position: relative; }
    .msg-dates { display: flex; gap: 8px; }
    .msg-dates .form-control { flex: 1 1 0; min-width: 0; padding-inline: 8px; }
    .msg-search i { position: absolute; top: 50%; right: 14px; transform: translateY(-50%); color: #9ca3af; }
    .msg-search .form-control { padding-right: 38px; }
    .msg-toggle-advanced { border: 0; background: none; padding: 0; color: var(--mp-primary); font-weight: 700; font-size: .82rem; }
    .msg-advanced { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--mp-border); }
    .msg-btn-primary { background: var(--mp-primary); border-color: var(--mp-primary); color: #fff; border-radius: 10px; font-weight: 700; min-height: 42px; }
    .msg-btn-primary:hover { background: var(--mp-primary-dark); border-color: var(--mp-primary-dark); color: #fff; }
    .msg-active { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-top: 12px; font-size: .78rem; color: var(--mp-muted); }
    .msg-active a { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: var(--mp-primary-soft); color: var(--mp-primary-dark); font-weight: 600; text-decoration: none; }
    .msg-active a:hover { background: #e0e7ff; }

    /* ---------- الحالات ---------- */
    .msg-statuses { display: flex; gap: 6px; flex-wrap: wrap; }
    .msg-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; border: 1px solid transparent; font-size: .76rem; font-weight: 700; text-decoration: none; white-space: nowrap; }
    .msg-status-pill.is-selected { box-shadow: 0 0 0 2px #fff, 0 0 0 4px currentColor; }
    .st-pending, .st-unknown { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }
    .st-accepted, .st-sent { background: #eff8ff; color: #175cd3; border-color: #b2ddff; }
    .st-delivered { background: #ecfdf3; color: #067647; border-color: #abefc6; }
    .st-read { background: #e6f5f2; color: #0b5f58; border-color: #9fd3cb; }
    .st-failed { background: #fef3f2; color: #b42318; border-color: #fbd5d1; }
    .st-skipped { background: #fffaeb; color: #b54708; border-color: #fedf89; }

    /* ---------- الجدول ---------- */
    .msg-table { margin: 0; }
    .msg-table thead th { background: var(--mp-soft); color: var(--mp-muted); font-size: .76rem; font-weight: 700; border-bottom: 1px solid var(--mp-border); white-space: nowrap; padding: 11px 14px; }
    .msg-table tbody td { padding: 13px 14px; vertical-align: top; border-color: #f1f2f4; font-size: .84rem; }
    .msg-table tbody tr.msg-row:hover { background: #fafaff; }
    .msg-table tbody tr.msg-row.is-failed { background: #fffbfa; }
    .msg-recipient { display: flex; gap: 10px; align-items: flex-start; min-width: 210px; }
    .ch-icon { width: 36px; height: 36px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 10px; font-size: .95rem; }
    .ch-sms { background: #eff8ff; color: #175cd3; }
    .ch-whatsapp { background: #ecfdf3; color: #128c4a; }
    .ch-email { background: #fff4ed; color: #c4320a; }
    .ch-notification { background: var(--mp-primary-soft); color: var(--mp-primary); }
    .ch-push { background: #f4f3ff; color: #6d28d9; }
    .msg-recipient b { display: block; font-size: .86rem; }
    .msg-recipient .phone { display: block; direction: ltr; text-align: right; color: var(--mp-muted); font-size: .78rem; }
    .msg-tag { display: inline-block; margin-top: 3px; padding: 1px 7px; border-radius: 6px; background: #f3f4f6; color: #4b5563; font-size: .68rem; font-weight: 700; }
    .msg-preview { min-width: 240px; max-width: 380px; color: #374151; line-height: 1.7; overflow-wrap: anywhere; white-space: pre-wrap; }
    .msg-error { display: flex; gap: 6px; margin-top: 6px; padding: 6px 9px; border-radius: 8px; background: #fef3f2; color: #b42318; font-size: .76rem; overflow-wrap: anywhere; white-space: normal; }
    .msg-source { max-width: 220px; overflow-wrap: anywhere; }
    .msg-source small, .msg-date small { display: block; color: var(--mp-muted); font-size: .74rem; }
    .msg-date { white-space: nowrap; direction: ltr; text-align: right; }
    .msg-details-btn { border: 1px solid var(--mp-border); background: #fff; border-radius: 9px; padding: 5px 10px; font-size: .78rem; font-weight: 600; color: #374151; white-space: nowrap; }
    .msg-details-btn:hover, .msg-details-btn[aria-expanded="true"] { border-color: #c7d2fe; background: var(--mp-primary-soft); color: var(--mp-primary-dark); }

    /* ---------- التفاصيل ---------- */
    /* صف التفاصيل مخفي كليًا حتى يُفتح، فلا يترك فراغًا تحت كل رسالة */
    .msg-table tbody tr.msg-detail-row { display: none; }
    .msg-table tbody tr.msg-detail-row.is-open { display: table-row; }
    .msg-table tbody tr.msg-detail-row > td { background: #fbfbfe; padding: 0; border: 0; }
    .msg-detail { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 18px; padding: 18px; border-top: 1px dashed #c7d2fe; border-bottom: 1px solid var(--mp-border); }
    .msg-detail h3 { margin: 0 0 8px; font-size: .82rem; font-weight: 800; color: #374151; }
    .msg-full { padding: 12px 14px; border: 1px solid var(--mp-border); border-radius: 12px; background: #fff; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.8; font-size: .84rem; }
    .msg-timeline { list-style: none; margin: 0 0 14px; padding: 0; }
    .msg-timeline li { position: relative; display: flex; gap: 10px; padding: 0 0 12px; }
    .msg-timeline li:not(:last-child)::before { content: ""; position: absolute; top: 22px; bottom: 0; right: 10px; width: 2px; background: #e5e7eb; }
    .msg-timeline .dot { width: 22px; height: 22px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 50%; background: #e5e7eb; color: #fff; font-size: .6rem; position: relative; z-index: 1; }
    .msg-timeline li.done .dot { background: #12b76a; }
    .msg-timeline li.fail .dot { background: #d92d20; }
    .msg-timeline b { display: block; font-size: .8rem; }
    .msg-timeline small { color: var(--mp-muted); font-size: .74rem; direction: ltr; unicode-bidi: plaintext; }
    .msg-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
    .msg-meta div { padding: 8px 10px; border: 1px solid var(--mp-border); border-radius: 10px; background: #fff; }
    .msg-meta span { display: block; color: var(--mp-muted); font-size: .7rem; }
    .msg-meta strong { font-size: .78rem; overflow-wrap: anywhere; }
    .msg-code summary { cursor: pointer; font-size: .78rem; font-weight: 700; color: var(--mp-primary-dark); margin-top: 8px; }
    .msg-code pre { margin: 6px 0 0; padding: 10px 12px; max-height: 240px; overflow: auto; border-radius: 10px; background: #111827; color: #e5e7eb; font-size: .74rem; white-space: pre-wrap; direction: ltr; text-align: left; }

    .msg-empty { padding: 50px 16px; text-align: center; color: var(--mp-muted); }
    .msg-empty i { display: block; margin-bottom: 10px; font-size: 2.2rem; color: #c7d2fe; }
    .msg-legend { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 18px; margin-top: 10px; font-size: .78rem; color: #374151; }
    .msg-legend div { display: flex; gap: 8px; align-items: baseline; }

    @media (max-width: 991.98px) {
        .msg-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .msg-detail, .msg-legend { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 575.98px) {
        .msg-hero { padding: 18px; }
        .msg-kpis { grid-template-columns: minmax(0, 1fr); }
    }
</style>
@endsection

@section('content')
@php
    $channels = \App\Models\OutboundMessageLog::CHANNELS;
    $statuses = \App\Models\OutboundMessageLog::STATUSES;
    $channelIcons = ['sms' => 'fa fa-sms', 'whatsapp' => 'fab fa-whatsapp', 'email' => 'fa fa-envelope', 'notification' => 'fa fa-bell', 'push' => 'fa fa-mobile-alt'];
    $statusIcons = ['pending' => 'fa fa-clock', 'accepted' => 'fa fa-check', 'sent' => 'fa fa-paper-plane', 'delivered' => 'fa fa-check-double', 'read' => 'fa fa-eye', 'failed' => 'fa fa-times-circle', 'skipped' => 'fa fa-ban', 'unknown' => 'fa fa-question-circle'];

    $statusTotal = $counts->sum();
    $deliveredCount = (int) ($counts['delivered'] ?? 0) + (int) ($counts['read'] ?? 0);
    $waitingCount = (int) ($counts['pending'] ?? 0) + (int) ($counts['accepted'] ?? 0) + (int) ($counts['sent'] ?? 0);
    $failedCount = (int) ($counts['failed'] ?? 0);
    $percent = fn ($value) => $statusTotal > 0 ? round($value / $statusTotal * 100) : 0;

    $currentType = request('type') ?: 'all';
    $currentStatus = (string) request('status');
    $advancedKeys = ['provider', 'source', 'branch_id', 'admin_id', 'contract_id', 'order_id'];
    $hasAdvanced = collect($advancedKeys)->contains(fn ($key) => filled(request($key)));
    $urlWith = fn (array $params) => route('messages.index', array_filter(array_merge(request()->except('page'), $params), fn ($value) => $value !== null && $value !== ''));

    $selectOptions = [
        'provider' => ['المزوّد', $providers->mapWithKeys(fn ($v) => [$v => $v])],
        'source' => ['مصدر الإرسال / المهمة', $sources->mapWithKeys(fn ($v) => [$v => \App\Models\OutboundMessageLog::sourceLabel($v)])],
        'branch_id' => ['الفرع', $branches->pluck('name', 'id')],
        'admin_id' => ['الموظف الذي أرسل', $actors->pluck('name', 'id')],
    ];
    $filterLabels = [
        'q' => 'بحث', 'type' => 'القناة', 'status' => 'الحالة', 'purpose' => 'النوع', 'provider' => 'المزوّد', 'source' => 'المصدر',
        'branch_id' => 'الفرع', 'admin_id' => 'الموظف', 'from' => 'من', 'to' => 'إلى', 'contract_id' => 'عقد', 'order_id' => 'طلب',
    ];
    $filterValue = function (string $key, $value) use ($channels, $statuses, $branches, $actors) {
        return match ($key) {
            'type' => $channels[$value] ?? $value,
            'status' => $statuses[$value] ?? $value,
            'purpose' => $value === 'verification' ? 'تحقق / OTP' : 'رسالة عامة',
            'branch_id' => $branches->firstWhere('id', (int) $value)?->name ?? $value,
            'admin_id' => $actors->firstWhere('id', (int) $value)?->name ?? $value,
            'contract_id', 'order_id' => '#' . $value,
            default => $value,
        };
    };
    $activeFilters = collect($filterLabels)->keys()->filter(fn ($key) => filled(request($key)) && ! ($key === 'type' && request($key) === 'all'));
@endphp

<div class="msg-page" dir="rtl">
    {{-- الرأس --}}
    <div class="msg-hero">
        <div class="msg-hero-main">
            <span class="msg-hero-icon"><i class="fa fa-paper-plane"></i></span>
            <div>
                <h1>سجل الرسائل والإرسال</h1>
                <p>كل رسالة خرجت من النظام: SMS وواتساب والبريد والإشعارات ورموز التحقق، ومصيرها عند المزوّد.</p>
            </div>
        </div>
        <div class="msg-hero-actions">
            <a href="{{ request()->fullUrl() }}" class="btn btn-glass"><i class="fa fa-sync-alt"></i> تحديث</a>
            <a href="{{ route('notifications.settings') }}" class="btn btn-light"><i class="fa fa-plug"></i> إعدادات الربط</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- المؤشرات --}}
    <div class="msg-kpis">
        <div class="msg-kpi kpi-indigo">
            <span class="msg-kpi-icon"><i class="fa fa-layer-group"></i></span>
            <div><span>إجمالي الرسائل</span><strong>{{ number_format($statusTotal) }}</strong><small>حسب الفلاتر الحالية</small></div>
        </div>
        <div class="msg-kpi kpi-green">
            <span class="msg-kpi-icon"><i class="fa fa-check-double"></i></span>
            <div><span>تم التسليم أو الإتاحة</span><strong>{{ number_format($deliveredCount) }}</strong><small>{{ $percent($deliveredCount) }}% · تشمل الإشعارات المتاحة داخل النظام</small></div>
        </div>
        <div class="msg-kpi kpi-blue">
            <span class="msg-kpi-icon"><i class="fa fa-hourglass-half"></i></span>
            <div><span>بانتظار التأكيد</span><strong>{{ number_format($waitingCount) }}</strong><small>{{ $percent($waitingCount) }}% · عند المزوّد</small></div>
        </div>
        <div class="msg-kpi kpi-red">
            <span class="msg-kpi-icon"><i class="fa fa-exclamation-triangle"></i></span>
            <div><span>فشلت</span><strong>{{ number_format($failedCount) }}</strong><small>{{ $percent($failedCount) }}%@if ($failedCount > 0) · <a href="{{ $urlWith(['status' => 'failed']) }}">عرضها</a>@endif</small></div>
        </div>
    </div>

    {{-- القنوات --}}
    <div class="msg-channels" aria-label="القنوات">
        <a class="msg-channel {{ $currentType === 'all' ? 'active' : '' }}" href="{{ $urlWith(['type' => null]) }}">
            <i class="fa fa-inbox"></i> كل القنوات <b>{{ number_format($channelCounts->sum()) }}</b>
        </a>
        @foreach ($channels as $key => $label)
            <a class="msg-channel {{ $currentType === $key ? 'active' : '' }}" href="{{ $urlWith(['type' => $key]) }}">
                <i class="{{ $channelIcons[$key] ?? 'fa fa-comment' }}"></i> {{ $label }} <b>{{ number_format($channelCounts[$key] ?? 0) }}</b>
            </a>
        @endforeach
    </div>

    {{-- الفلاتر --}}
    <div class="msg-card">
        <div class="msg-card-head">
            <h2><i class="fa fa-filter"></i> البحث والفلترة</h2>
            <button class="msg-toggle-advanced" type="button" data-bs-toggle="collapse" data-bs-target="#msgAdvanced" aria-expanded="{{ $hasAdvanced ? 'true' : 'false' }}">
                <i class="fa fa-sliders-h"></i> فلاتر متقدمة
            </button>
        </div>
        <div class="msg-card-body">
            <form method="get" action="{{ route('messages.index') }}" class="msg-filters">
                @if ($currentType !== 'all')<input type="hidden" name="type" value="{{ $currentType }}">@endif
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <label for="msgQ">بحث</label>
                        <div class="msg-search">
                            <i class="fa fa-search"></i>
                            <input id="msgQ" name="q" class="form-control" value="{{ request('q') }}" maxlength="150" placeholder="اسم المستلم، الجوال، نص الرسالة، الخطأ أو معرّف المزوّد">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label for="msgStatus">الحالة</label>
                        <select id="msgStatus" name="status" class="form-select">
                            <option value="">كل الحالات</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label for="msgPurpose">نوع الرسالة</label>
                        <select id="msgPurpose" name="purpose" class="form-select">
                            <option value="">الكل</option>
                            <option value="message" @selected(request('purpose') === 'message')>رسالة عامة</option>
                            <option value="verification" @selected(request('purpose') === 'verification')>تحقق / OTP / استعادة حساب</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label>الفترة</label>
                        <div class="msg-dates">
                            <input type="date" name="from" class="form-control" value="{{ request('from') }}" aria-label="من تاريخ" title="من تاريخ">
                            <input type="date" name="to" class="form-control" value="{{ request('to') }}" aria-label="إلى تاريخ" title="إلى تاريخ">
                        </div>
                    </div>
                </div>

                <div class="collapse {{ $hasAdvanced ? 'show' : '' }}" id="msgAdvanced">
                    <div class="msg-advanced row g-3">
                        @foreach ($selectOptions as $field => [$label, $options])
                            <div class="{{ $field === 'source' ? 'col-lg-4' : 'col-lg-2' }} col-md-6">
                                <label for="msg-{{ $field }}">{{ $label }}</label>
                                <select id="msg-{{ $field }}" name="{{ $field }}" class="form-select">
                                    <option value="">الكل</option>
                                    @foreach ($options as $key => $text)
                                        <option value="{{ $key }}" @selected((string) request($field) === (string) $key)>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                        <div class="col-lg-1 col-md-3">
                            <label for="msg-contract">رقم العقد</label>
                            <input id="msg-contract" type="number" name="contract_id" class="form-control" value="{{ request('contract_id') }}" min="1">
                        </div>
                        <div class="col-lg-1 col-md-3">
                            <label for="msg-order">رقم الطلب</label>
                            <input id="msg-order" type="number" name="order_id" class="form-control" value="{{ request('order_id') }}" min="1">
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn msg-btn-primary px-4"><i class="fa fa-search"></i> عرض النتائج</button>
                    @if ($activeFilters->isNotEmpty())
                        <a class="btn btn-outline-secondary d-inline-flex align-items-center" style="border-radius:10px; min-height:42px" href="{{ route('messages.index') }}">مسح كل الفلاتر</a>
                    @endif
                </div>

                @if ($activeFilters->isNotEmpty())
                    <div class="msg-active">
                        <span>الفلاتر المطبقة:</span>
                        @foreach ($activeFilters as $key)
                            <a href="{{ $urlWith([$key => null]) }}" title="إزالة الفلتر">
                                {{ $filterLabels[$key] }}: {{ $filterValue($key, request($key)) }} <i class="fa fa-times"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- النتائج --}}
    <div class="msg-card">
        <div class="msg-card-head">
            <h2><i class="fa fa-list"></i> الرسائل <span class="text-muted fw-normal" style="font-size:.85rem">({{ number_format($logs->total()) }})</span></h2>
            <div class="msg-statuses">
                <a class="msg-status-pill st-unknown {{ $currentStatus === '' ? 'is-selected' : '' }}" href="{{ $urlWith(['status' => null]) }}">كل الحالات</a>
                @foreach ($statuses as $key => $label)
                    @if (($counts[$key] ?? 0) > 0)
                        <a class="msg-status-pill st-{{ $key }} {{ $currentStatus === $key ? 'is-selected' : '' }}" href="{{ $urlWith(['status' => $key]) }}">
                            <i class="{{ $statusIcons[$key] }}"></i> {{ $label }} · {{ number_format($counts[$key]) }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        @if ($logs->isEmpty())
            <div class="msg-empty">
                <i class="fa fa-inbox"></i>
                لا توجد رسائل تطابق الفلاتر.
                @if ($activeFilters->isNotEmpty())<div class="mt-2"><a href="{{ route('messages.index') }}">عرض كل الرسائل</a></div>@endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table msg-table">
                    <thead>
                        <tr>
                            <th>المستلم والقناة</th>
                            <th>الرسالة</th>
                            <th>الحالة</th>
                            <th>المصدر</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $channelKey = $log->channel;
                                $isInternal = $channelKey === 'notification';
                                $isFailed = $log->status === 'failed';
                                $steps = [
                                    ['label' => 'أُنشئت الرسالة', 'at' => $log->created_at, 'state' => 'done'],
                                    ['label' => $isInternal ? 'سُجّل الإشعار في النظام' : 'قبلها المزوّد', 'at' => $log->accepted_at, 'state' => $log->accepted_at ? 'done' : ''],
                                    ['label' => $isInternal ? 'أُتيح الإشعار لحساب المستلم' : 'وصلت للمستلم', 'at' => $log->delivered_at, 'state' => $log->delivered_at ? 'done' : ''],
                                    ['label' => 'قرأها المستلم', 'at' => $log->read_at, 'state' => $log->read_at ? 'done' : ''],
                                ];
                                if ($log->failed_at || $isFailed) {
                                    $steps[] = ['label' => 'فشل الإرسال', 'at' => $log->failed_at, 'state' => 'fail'];
                                }
                            @endphp
                            <tr class="msg-row {{ $isFailed ? 'is-failed' : '' }}">
                                <td>
                                    <div class="msg-recipient">
                                        <span class="ch-icon ch-{{ $channelKey }}" title="{{ $channels[$channelKey] ?? $channelKey }}"><i class="{{ $channelIcons[$channelKey] ?? 'fa fa-comment' }}"></i></span>
                                        <div>
                                            <b>{{ $log->recipient_name ?: 'بدون اسم' }}</b>
                                            <span class="phone">{{ $log->recipient }}</span>
                                            <span class="msg-tag">{{ $channels[$channelKey] ?? $channelKey }}@if ($log->provider) · {{ $log->provider }}@endif</span>
                                            @if ($log->purpose === 'verification')<span class="msg-tag" style="background:#f4f3ff;color:#6d28d9">OTP / تحقق</span>@endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="msg-preview">{{ \Illuminate\Support\Str::limit($log->message, 160) }}</div>
                                    @if ($log->error)
                                        <div class="msg-error"><i class="fa fa-exclamation-circle mt-1"></i><span>{{ $log->error }}</span></div>
                                    @endif
                                </td>
                                <td>
                                    <span class="msg-status-pill st-{{ $log->status }}"><i class="{{ $statusIcons[$log->status] ?? 'fa fa-circle' }}"></i> {{ $isInternal && $log->status === 'delivered' ? 'متاح داخل النظام' : ($statuses[$log->status] ?? $log->status) }}</span>
                                    @if ($channelKey === 'whatsapp')
                                        @include('admin.ingaz.partials.whatsapp-ticks', ['status' => $log->status])
                                    @endif
                                </td>
                                <td class="msg-source">
                                    {{ \App\Models\OutboundMessageLog::sourceLabel($log->source) }}
                                    <small><i class="fa {{ $log->actor ? 'fa-user' : 'fa-robot' }}"></i> {{ $log->actor?->name ?: 'آلي / لم يسجل موظف' }}</small>
                                    @if ($log->branch)<small><i class="fa fa-code-branch"></i> {{ $log->branch->name }}</small>@endif
                                </td>
                                <td class="msg-date">
                                    {{ $log->created_at?->format('Y-m-d') }}
                                    <small>{{ $log->created_at?->format('H:i:s') }} · #{{ $log->id }}</small>
                                </td>
                                <td>
                                    <button class="msg-details-btn" type="button" data-detail="msgDetail{{ $log->id }}" aria-expanded="false" aria-controls="msgDetail{{ $log->id }}">
                                        <i class="fa fa-chevron-down"></i> التفاصيل
                                    </button>
                                    @if ($log->canResend())
                                        <form method="POST" action="{{ route('messages.resend', $log->id) }}" class="mt-1"
                                            onsubmit="return confirm('إعادة إرسال هذه الرسالة إلى {{ e($log->recipient) }}؟');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-redo"></i> إعادة الإرسال</button>
                                        </form>
                                    @elseif (!empty(($log->context ?? [])['resent_at']))
                                        <small class="d-block text-muted mt-1">أُعيد إرسالها {{ ($log->context ?? [])['resent_at'] }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr class="msg-detail-row" id="msgDetail{{ $log->id }}">
                                <td colspan="6">
                                    <div>
                                        <div class="msg-detail">
                                            <div>
                                                <h3>نص الرسالة كاملًا</h3>
                                                <div class="msg-full">{{ $log->message ?: '—' }}</div>
                                                @if ($log->error)
                                                    <div class="msg-error mt-2"><i class="fa fa-exclamation-circle mt-1"></i><span>{{ $log->error }}</span></div>
                                                @endif
                                                <details class="msg-code">
                                                    <summary>الارتباطات وبيانات الإرسال</summary>
                                                    <div>المصدر التقني: {{ $log->source ?: '—' }}</div>
                                                    <pre>{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                                <details class="msg-code">
                                                    <summary>استجابة المزوّد (بعد حجب البيانات الحساسة)</summary>
                                                    <pre>{{ json_encode($log->provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                            </div>
                                            <div>
                                                <h3>مسار الرسالة</h3>
                                                <ul class="msg-timeline">
                                                    @foreach ($steps as $step)
                                                        <li class="{{ $step['state'] }}">
                                                            <span class="dot"><i class="fa {{ $step['state'] === 'fail' ? 'fa-times' : 'fa-check' }}"></i></span>
                                                            <div>
                                                                <b>{{ $step['label'] }}</b>
                                                                <small>{{ $step['at'] ? $step['at']->format('Y-m-d H:i:s') : ($step['state'] === 'fail' ? 'الوقت غير مسجل' : 'لم يرد تأكيد') }}</small>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="msg-meta">
                                                    <div><span>معرّف الرسالة لدى المزوّد</span><strong dir="ltr">{{ $log->provider_message_id ?: 'غير متاح' }}</strong></div>
                                                    <div><span>استجابة HTTP</span><strong>{{ $log->http_status ?: 'غير متاحة' }}</strong></div>
                                                </div>
                                                @if ($log->legacy_key)
                                                    <small class="text-muted">سجل تاريخي: تظهر المعلومات التي حفظها النظام سابقًا فقط.</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 pt-3">{{ $logs->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>

    {{-- شرح الحالات --}}
    <div class="msg-card">
        <div class="msg-card-head">
            <h2><i class="fa fa-info-circle"></i> كيف تقرأ حالة الرسالة؟</h2>
            <button class="msg-toggle-advanced" type="button" data-bs-toggle="collapse" data-bs-target="#msgLegend" aria-expanded="false">عرض الشرح</button>
        </div>
        <div class="collapse" id="msgLegend">
            <div class="msg-card-body">
                <p class="mb-2" style="font-size:.84rem">يشمل السجل الرسائل الخارجية والإشعارات الداخلية؛ إجمالي السجل لا يعني عدد رسائل الجوال. الإشعار المتاح داخل النظام لم تُؤكد قراءته حتى تظهر حالة «تمت القراءة». محتوى رموز التحقق وروابط الدخول محجوب.</p>
                <div class="msg-legend">
                    <div><span class="msg-status-pill st-accepted">قبله المزوّد</span><span>استلمها المزوّد للإرسال، ولا يعني ذلك أنها وصلت للهاتف.</span></div>
                    <div><span class="msg-status-pill st-delivered">تم التسليم</span><span>وصل تأكيد من المزوّد بأنها وصلت للمستلم.</span></div>
                    <div><span class="msg-status-pill st-read">تمت القراءة</span><span>فتحها المستلم، عند توفر تأكيد القراءة من المزوّد.</span></div>
                    <div><span class="msg-status-pill st-failed">فشل</span><span>رفضها المزوّد أو حدث خطأ؛ السبب يظهر تحت الرسالة.</span></div>
                    <div><span class="msg-status-pill st-skipped">لم يُرسل</span><span>القناة معطلة أو الإعدادات ناقصة، فلم تخرج الرسالة.</span></div>
                    <div><span class="msg-status-pill st-unknown">سجل قديم</span><span>من قبل تفعيل السجل؛ نتيجته غير مؤكدة.</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.msg-page [data-detail]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById(button.dataset.detail);
            const open = row.classList.toggle('is-open');
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            button.querySelector('i').className = open ? 'fa fa-chevron-up' : 'fa fa-chevron-down';
        });
    });
});
</script>
@endsection
