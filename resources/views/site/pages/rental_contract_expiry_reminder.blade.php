@php
    $companyName = documentSetting('website_name', config('app.name'));
    $logoUrl = documentLogoUrl();
    $endDate = $contract->info?->ingaz_to;
    $daysLeft = $endDate ? now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($endDate)->startOfDay(), false) : null;
    $returnOpen = old('preferred_return_date') || old('response_notes');
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0f5f63">
    <title>تجديد عقد الإيجار — {{ $companyName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0f5f63;
            --brand-dark: #0a4346;
            --brand-soft: #e7f3f3;
            --accent: #f2a541;
            --ink: #14202e;
            --muted: #627083;
            --line: #e3e8ef;
            --bg: #f3f6f9;
            --radius: 14px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Tajawal', Tahoma, Arial, sans-serif;
            background:
                radial-gradient(circle at 100% 0, rgba(15, 95, 99, .10), transparent 40%),
                radial-gradient(circle at 0 100%, rgba(242, 165, 65, .10), transparent 40%),
                var(--bg);
            color: var(--ink);
            line-height: 1.7;
        }

        .page { max-width: 760px; margin: 0 auto; padding: 24px 16px 40px; }

        /* شريط العلامة */
        .brand {
            display: flex; align-items: center; justify-content: center; gap: 12px;
            margin-bottom: 18px; font-weight: 700; color: var(--brand-dark); font-size: 17px;
        }
        .brand img { max-height: 54px; max-width: 180px; object-fit: contain; }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(20, 32, 46, .08);
            overflow: hidden;
        }

        /* الرأس */
        .hero {
            position: relative;
            padding: 32px 28px 28px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            text-align: center;
            overflow: hidden;
        }
        .hero::before, .hero::after {
            content: ''; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .06);
        }
        .hero::before { width: 220px; height: 220px; top: -90px; left: -60px; }
        .hero::after { width: 160px; height: 160px; bottom: -80px; right: -40px; }
        .hero-icon {
            width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 18px;
            display: grid; place-items: center; background: rgba(255, 255, 255, .14);
        }
        .hero h1 { margin: 0 0 6px; font-size: 26px; font-weight: 800; }
        .hero p { margin: 0; color: #d4ecec; font-size: 15px; }
        .countdown {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 16px;
            padding: 7px 16px; border-radius: 999px; font-weight: 700; font-size: 14px;
            background: rgba(255, 255, 255, .15); border: 1px solid rgba(255, 255, 255, .25);
        }
        .countdown .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); }
        .countdown.expired .dot { background: #ff7b7b; }

        .content { padding: 26px 28px 30px; }

        /* ملخص العقد */
        .summary {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 24px;
        }
        .summary-item {
            display: flex; align-items: center; gap: 12px;
            padding: 14px; border: 1px solid var(--line); border-radius: var(--radius); background: #fafcfd;
        }
        .summary-item .ic {
            flex: 0 0 40px; height: 40px; border-radius: 12px; display: grid; place-items: center;
            background: var(--brand-soft); color: var(--brand);
        }
        .summary-item small { display: block; color: var(--muted); font-size: 13px; }
        .summary-item strong { display: block; font-size: 16px; word-break: break-word; }

        /* التنبيهات */
        .alert {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px; border-radius: var(--radius); margin-bottom: 16px; font-size: 15px;
        }
        .alert svg { flex: 0 0 22px; margin-top: 2px; }
        .alert-success { background: #e9f8ef; border: 1px solid #c3ead2; color: #155d31; }
        .alert-warning { background: #fff7e2; border: 1px solid #f5e0a6; color: #77561a; }
        .alert-info { background: #ebf5ff; border: 1px solid #c4dff8; color: #17476f; }

        .status-box {
            text-align: center; padding: 26px 18px; border-radius: var(--radius);
            background: var(--brand-soft); border: 1px dashed #b6d8d9; margin-bottom: 16px;
        }
        .status-box .big { font-size: 22px; font-weight: 800; color: var(--brand-dark); margin: 6px 0 0; }

        .quote {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px;
        }
        .quote div { text-align: center; padding: 14px 8px; border-radius: var(--radius); background: #fafcfd; border: 1px solid var(--line); }
        .quote small { display: block; color: var(--muted); font-size: 13px; }
        .quote strong { font-size: 17px; }

        /* الأقسام */
        .section-title {
            display: flex; align-items: center; gap: 10px; margin: 0 0 12px; font-size: 18px; font-weight: 800;
        }
        .section-title .num {
            width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center;
            background: var(--brand); color: #fff; font-size: 14px;
        }
        .hint { color: var(--muted); font-size: 14px; margin: -4px 0 14px; }

        /* بطاقات مدد التمديد */
        .options { display: grid; gap: 10px; margin-bottom: 16px; }
        .option { position: relative; display: block; cursor: pointer; }
        .option input { position: absolute; opacity: 0; pointer-events: none; }
        .option-body {
            display: flex; align-items: center; gap: 14px;
            padding: 16px; border: 2px solid var(--line); border-radius: var(--radius); background: #fff;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .option:hover .option-body { border-color: #b8d6d7; }
        .option input:focus-visible + .option-body { outline: 3px solid rgba(15, 95, 99, .25); outline-offset: 2px; }
        .option input:checked + .option-body {
            border-color: var(--brand); background: var(--brand-soft); box-shadow: 0 6px 18px rgba(15, 95, 99, .12);
        }
        .radio {
            flex: 0 0 22px; height: 22px; border-radius: 50%; border: 2px solid #b9c4d0; display: grid; place-items: center;
        }
        .option input:checked + .option-body .radio { border-color: var(--brand); }
        .option input:checked + .option-body .radio::after {
            content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--brand);
        }
        .option-main { flex: 1; min-width: 0; }
        .option-main strong { display: block; font-size: 16px; }
        .option-main small { color: var(--muted); font-size: 13px; }
        .price { text-align: left; white-space: nowrap; }
        .price strong { display: block; font-size: 20px; font-weight: 800; color: var(--brand-dark); }
        .price small { color: var(--muted); font-size: 12px; }

        .empty {
            padding: 16px; border-radius: var(--radius); background: #fafcfd; border: 1px dashed var(--line);
            color: var(--muted); text-align: center; margin-bottom: 16px;
        }

        /* الأزرار */
        .btn {
            appearance: none; border: 0; border-radius: 12px; padding: 14px 22px; width: 100%;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            font: inherit; font-weight: 700; font-size: 16px; cursor: pointer; text-decoration: none;
            transition: transform .1s, box-shadow .15s, background .15s;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--brand); color: #fff; box-shadow: 0 10px 22px rgba(15, 95, 99, .25); }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-outline { background: #fff; color: var(--ink); border: 1.5px solid #cdd6e0; }
        .btn-outline:hover { background: #f6f8fa; }
        .btn[disabled] { opacity: .7; cursor: wait; }

        .secure { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 10px; color: var(--muted); font-size: 13px; }

        .divider {
            display: flex; align-items: center; gap: 12px; margin: 26px 0; color: var(--muted); font-size: 14px;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--line); }

        /* قسم التسليم القابل للطي */
        details.return { border: 1px solid var(--line); border-radius: var(--radius); background: #fafcfd; }
        details.return summary {
            list-style: none; cursor: pointer; padding: 16px; display: flex; align-items: center; gap: 10px; font-weight: 700;
        }
        details.return summary::-webkit-details-marker { display: none; }
        details.return summary .chev { margin-inline-start: auto; transition: transform .2s; color: var(--muted); }
        details.return[open] summary .chev { transform: rotate(180deg); }
        .return-body { padding: 0 16px 16px; }

        .field { margin-bottom: 14px; }
        .field label { display: block; margin-bottom: 6px; color: var(--muted); font-size: 14px; font-weight: 700; }
        .input {
            width: 100%; padding: 12px 14px; border: 1.5px solid #d3dbe5; border-radius: 10px; background: #fff;
            font: inherit; font-size: 15px; color: var(--ink);
        }
        .input:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(15, 95, 99, .15); }
        textarea.input { min-height: 90px; resize: vertical; }

        .footer-note { margin: 18px 0 0; color: var(--muted); text-align: center; font-size: 13px; }

        @media (max-width: 600px) {
            .page { padding: 14px 10px 30px; }
            .hero { padding: 26px 18px 22px; }
            .hero h1 { font-size: 22px; }
            .content { padding: 20px 16px 24px; }
            .summary { grid-template-columns: 1fr; }
            .quote { grid-template-columns: 1fr; }
            .price strong { font-size: 18px; }
        }
    </style>
</head>
<body>
@php
    $icons = [
        'check' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/></svg>',
        'info' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
        'warn' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    ];
@endphp
<main class="page">
    <div class="brand">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $companyName }}">
        @else
            <span>{{ $companyName }}</span>
        @endif
    </div>

    <section class="card" aria-labelledby="pageTitle">
        <header class="hero">
            <div class="hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/></svg>
            </div>
            <h1 id="pageTitle">تجديد عقد الإيجار</h1>
            <p>يقترب موعد انتهاء عقدكم، يسعدنا استمرار خدمتكم.</p>
            @if(!is_null($daysLeft))
                <div class="countdown {{ $daysLeft < 0 ? 'expired' : '' }}">
                    <span class="dot"></span>
                    @if($daysLeft > 1)
                        يتبقى {{ $daysLeft }} يوم على الانتهاء
                    @elseif($daysLeft === 1)
                        ينتهي العقد غدًا
                    @elseif($daysLeft === 0)
                        ينتهي العقد اليوم
                    @else
                        انتهى العقد منذ {{ abs($daysLeft) }} يوم
                    @endif
                </div>
            @endif
        </header>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success" role="status">{!! $icons['check'] !!}<div>{{ session('success') }}</div></div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" role="alert">{!! $icons['warn'] !!}<div>{{ session('warning') }}</div></div>
            @endif
            @if($errors->any())
                <div class="alert alert-warning" role="alert">{!! $icons['warn'] !!}<div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
            @endif

            <div class="summary">
                <div class="summary-item">
                    <span class="ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                    <div><small>رقم العقد</small><strong>{{ $contract->contract_number ?: $contract->id }}</strong></div>
                </div>
                <div class="summary-item">
                    <span class="ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                    <div><small>تاريخ الانتهاء</small><strong>{{ optional($endDate)->format('Y-m-d') ?: '-' }}</strong></div>
                </div>
                <div class="summary-item">
                    <span class="ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg></span>
                    <div><small>العميل</small><strong>{{ $contract->user?->name ?: '-' }}</strong></div>
                </div>
                <div class="summary-item">
                    <span class="ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg></span>
                    <div><small>العاملة</small><strong>{{ $contract->maid?->name ?: '-' }}</strong></div>
                </div>
            </div>

            @if($reminder->extension_id && $reminder->contract_effect_applied_at)
                <div class="status-box">
                    <div style="color:#155d31">{!! $icons['check'] !!}</div>
                    <div>تم تأكيد الدفع وتسجيل تمديد العقد بنجاح.</div>
                    <p class="big">تاريخ الانتهاء الجديد: {{ $contract->info?->ingaz_to?->format('Y-m-d') }}</p>
                </div>
            @elseif($reminder->payment_status === 'paid')
                <div class="alert alert-success">{!! $icons['check'] !!}<div>تم تأكيد استلام الدفعة. يتابع الموظف المختص تسجيل التمديد؛ لا يلزم الدفع مرة أخرى.</div></div>
            @elseif(!$canRespond && !$reminder->responded_at)
                <div class="alert alert-info">{!! $icons['info'] !!}<div>لم يعد هذا الرابط متاحًا لتسجيل إجراء جديد بعد تغير حالة العقد أو تاريخ انتهائه. يرجى التواصل مع الموظف المختص.</div></div>
            @elseif($reminder->responded_at)
                <div class="alert alert-info">
                    {!! $icons['info'] !!}
                    <div>
                        تم تسجيل اختياركم مسبقاً:
                        <strong>{{ $reminder->response === 'extend' ? 'تمديد العقد' : 'تسليم العاملة' }}</strong>
                        بتاريخ {{ $reminder->responded_at->format('Y-m-d H:i') }}.
                    </div>
                </div>

                @if($reminder->response === 'extend')
                    @if($reminder->customer_payment_method)
                        <p>طريقة الدفع: {{ \App\Support\NotificationAlertSettings::renewalPaymentMethodOptions()[$reminder->customer_payment_method] ?? $reminder->customer_payment_method }}</p>
                    @endif
                    @if($reminder->renewal_quote)
                        <div class="quote">
                            <div><small>مدة التمديد</small><strong>{{ $reminder->renewal_quote['days'] }} يوم</strong></div>
                            <div><small>من</small><strong>{{ $reminder->renewal_quote['start_date'] }}</strong></div>
                            <div><small>إلى</small><strong>{{ $reminder->renewal_quote['end_date'] }}</strong></div>
                        </div>
                        <div class="status-box">
                            <div>المبلغ المستحق شامل الضريبة</div>
                            <p class="big">{{ number_format($reminder->renewal_quote['amount'], 2) }} ريال</p>
                            <small style="color:var(--muted)">لم يسجل التمديد بعد؛ يلزم تأكيد الدفع.</small>
                        </div>
                    @endif
                    @if($canRespond && $reminder->renewal_quote && $reminder->customer_payment_method === 'moyasar' && isset($paymentMethods['moyasar']))
                        <form method="POST" action="{{ route('rental-contract-reminders.respond', $reminder->token) }}" class="js-submit-once">
                            @csrf
                            <button type="submit" name="response" value="extend" class="btn btn-primary">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                المتابعة إلى صفحة الدفع
                            </button>
                            <div class="secure">🔒 دفع إلكتروني آمن</div>
                        </form>
                    @else
                        <div class="alert alert-info">{!! $icons['info'] !!}<div>سيتابع الموظف المختص طلبكم لتأكيد المدة والسعر وإكمال الإجراءات.</div></div>
                    @endif
                @else
                    <div class="alert alert-info">{!! $icons['info'] !!}<div>طلب تسليم العاملة بانتظار متابعة الموظف وترتيب الاستلام وإكمال إجراءات الإغلاق.</div></div>
                    @if($reminder->preferred_return_date)
                        <div class="status-box">
                            <div>موعد التسليم المقترح</div>
                            <p class="big">{{ $reminder->preferred_return_date->format('Y-m-d') }}</p>
                        </div>
                    @endif
                @endif
            @else
                <form method="POST" action="{{ route('rental-contract-reminders.respond', $reminder->token) }}" class="js-submit-once">
                    @csrf

                    <h2 class="section-title"><span class="num">1</span> تمديد العقد</h2>
                    @if($options)
                        <p class="hint">اختر مدة التمديد المناسبة. لن يسجل التمديد إلا بعد تأكيد الدفع.</p>
                        <div class="options" role="radiogroup" aria-label="مدة التمديد">
                            @foreach($options as $option)
                                <label class="option">
                                    <input type="radio" name="quote_token" value="{{ $option['token'] }}" required
                                        @checked(old('quote_token') === $option['token'] || (count($options) === 1 && !old('quote_token')))>
                                    <span class="option-body">
                                        <span class="radio" aria-hidden="true"></span>
                                        <span class="option-main">
                                            <strong>{{ $option['label'] }} <small>({{ $option['days'] }} يوم)</small></strong>
                                            <small>من {{ $option['start_date'] }} إلى {{ $option['end_date'] }}</small>
                                        </span>
                                        <span class="price">
                                            <strong>{{ number_format($option['amount'], 2) }}</strong>
                                            <small>ريال شامل الضريبة</small>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="empty">لا تتوفر مدة مسعرة صالحة للدفع الإلكتروني حاليًا؛ يمكنكم طلب التمديد وسيتواصل معكم الموظف.</div>
                    @endif

                    @if($options)
                        <div class="field">
                            <label for="renewalPaymentMethod">طريقة الدفع</label>
                            <select class="input" id="renewalPaymentMethod" name="payment_method" required>
                                <option value="">اختر طريقة الدفع</option>
                                @foreach($paymentMethods as $method => $label)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="hint">التحويل البنكي والنقدي: سيتواصل معكم الموظف لتأكيد السداد وتنفيذ التمديد.</p>
                        </div>
                    @endif
                    <button type="submit" name="response" value="extend" class="btn btn-primary">
                        @if($options)
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                            تأكيد طلب التمديد ومتابعة الدفع
                        @else
                            طلب تمديد ومتابعة الموظف
                        @endif
                    </button>
                    @if($options)<div class="secure">يتم تنفيذ التمديد بعد تأكيد السداد.</div>@endif

                    <div class="divider">أو</div>

                    <details class="return" @if($returnOpen) open @endif>
                        <summary>
                            <span class="num" style="width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#8a96a5;color:#fff;font-size:14px">2</span>
                            لا أرغب بالتمديد — طلب تسليم العاملة
                            <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <div class="return-body">
                            <p class="hint" style="margin-top:0">يمكنكم اقتراح موعد للتسليم وإضافة ملاحظة، وسيتواصل معكم الموظف لترتيب الاستلام.</p>
                            <div class="field">
                                <label for="preferredReturnDate">موعد التسليم المقترح (اختياري)</label>
                                <input class="input" type="date" id="preferredReturnDate" name="preferred_return_date"
                                    min="{{ now()->toDateString() }}" value="{{ old('preferred_return_date') }}">
                            </div>
                            <div class="field">
                                <label for="responseNotes">ملاحظة (اختياري)</label>
                                <textarea class="input" id="responseNotes" name="response_notes" maxlength="1000">{{ old('response_notes') }}</textarea>
                            </div>
                            <button type="submit" name="response" value="return" class="btn btn-outline" formnovalidate>
                                تأكيد طلب تسليم العاملة
                            </button>
                        </div>
                    </details>
                </form>
            @endif

            <p class="footer-note">هذه صفحة خاصة بعقدكم ولا تتطلب تسجيل دخول. لا تشارك الرابط مع الآخرين.</p>
        </div>
    </section>
</main>
<script>
    // منع الإرسال المكرر مع الإبقاء على قيمة الزر المضغوط
    document.querySelectorAll('.js-submit-once').forEach(form => form.addEventListener('submit', event => {
        const button = event.submitter;
        if (!button) return;
        setTimeout(() => {
            form.querySelectorAll('button[type="submit"]').forEach(b => b.disabled = true);
            button.textContent = 'جارٍ المعالجة…';
        }, 0);
    }));
</script>
</body>
</html>
