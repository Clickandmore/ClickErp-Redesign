<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @include('admin.superadmin.partials.head', ['pageTitle' => 'بوابة الدفع ميسر — Super Admin'])
    <style>
        /* صفحة إعدادات ميسر: تبني على نظام تصميم Super Admin الموحّد في partials.head */
        .my-icon { width: 18px; height: 18px; flex: 0 0 auto; }
        .my-head-title { display: flex; align-items: center; gap: 12px; }
        .my-head-icon { width: 40px; height: 40px; flex: 0 0 auto; border-radius: var(--r-sm); display: grid; place-items: center;
            background: var(--accent-soft); color: var(--accent-dark); border: 1px solid var(--accent-line); }
        .my-head-icon.is-navy { background: var(--navy-soft); color: var(--navy); border-color: #c9d8e8; }
        .my-head-icon.is-warn { background: var(--warning-soft); color: var(--warning); border-color: var(--warning-line); }
        .my-stack { display: grid; gap: 18px; }
        .my-side { display: grid; gap: 18px; position: sticky; top: 16px; }
        .my-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .my-ltr { direction: ltr; text-align: left; font-family: var(--mono); font-size: .86rem; }
        .help { margin: 0; color: var(--muted); font-size: .8rem; line-height: 1.7; }

        /* مفتاح التشغيل الرئيسي */
        .my-power { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 18px 20px;
            border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); transition: border-color .2s, background .2s; }
        .my-power.is-on { border-color: var(--accent-line); background: linear-gradient(180deg, #f2fbf9 0%, var(--accent-soft) 100%); }
        .my-power h3 { font-size: .98rem; margin: 0 0 3px; }
        .my-power p { color: var(--muted); font-size: .84rem; line-height: 1.7; }
        .my-power .switch { transform: scale(1.18); transform-origin: center; }
        .my-power-sub { padding: 14px 18px; margin-top: -6px; }
        .my-power-sub h3 { font-size: .9rem; display: flex; align-items: center; gap: 8px; }
        .my-power-sub .switch { transform: none; }
        .my-power-state { font-weight: 700; font-size: .82rem; margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; }
        .my-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .my-power.is-on .my-dot { background: var(--success); box-shadow: 0 0 0 4px rgba(6, 118, 71, .14); }

        /* حقول المفاتيح */
        /* الحقل LTR داخل صفحة RTL: نثبت الزر والمسافة على اليمين فعلياً (نهاية النص) حتى لا يغطي بداية القيمة */
        .my-secret { position: relative; direction: ltr; }
        .field .my-secret input { padding-left: 13px; padding-right: 48px; text-align: left; }
        .my-reveal { position: absolute; right: 6px; left: auto; top: 50%; transform: translateY(-50%); width: 34px; height: 34px;
            border: 0; border-radius: var(--r-xs); background: transparent; color: var(--muted); cursor: pointer; display: grid; place-items: center; }
        .my-reveal:hover { background: var(--surface-alt); color: var(--text); }
        .my-saved { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; font-size: .78rem; color: var(--muted); }
        .my-saved code { font-family: var(--mono); background: var(--surface-alt); border: 1px solid var(--border); border-radius: 6px; padding: 1px 8px; color: var(--text-soft); direction: ltr; }
        .my-pill { display: inline-flex; align-items: center; gap: 5px; padding: 1px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
        .my-pill.is-live { background: var(--success-soft); color: var(--success); border-color: var(--success-line); }
        .my-pill.is-test { background: var(--warning-soft); color: var(--warning); border-color: var(--warning-line); }
        .my-pill.is-empty { background: var(--surface-alt); color: var(--muted); border-color: var(--border); }

        .my-test { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 18px; padding-top: 16px; border-top: 1px dashed var(--border-strong); }
        .my-test-result { font-size: .85rem; font-weight: 600; display: none; align-items: center; gap: 8px; padding: 7px 12px; border-radius: var(--r-sm); border: 1px solid; }
        .my-test-result.is-ok { display: inline-flex; color: var(--success); background: var(--success-soft); border-color: var(--success-line); }
        .my-test-result.is-bad { display: inline-flex; color: var(--danger); background: var(--danger-soft); border-color: var(--danger-line); }
        .my-test-result.is-busy { display: inline-flex; color: var(--info); background: var(--info-soft); border-color: var(--info-line); }

        /* الويبهوك */
        .my-copy { display: flex; gap: 8px; }
        .my-copy input { flex: 1; min-width: 0; }
        .my-events { display: flex; gap: 8px; flex-wrap: wrap; }
        .my-event { font-family: var(--mono); font-size: .78rem; direction: ltr; padding: 3px 10px; border-radius: 999px;
            background: var(--navy-soft); color: var(--navy); border: 1px solid #d3e0ee; }

        /* معاينة الفاتورة والرسائل */
        .my-preview { border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); padding: 14px 16px; }
        .my-preview-label { display: flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 700; color: var(--muted); margin-bottom: 8px; text-transform: none; }
        .my-invoice-line { font-weight: 600; color: var(--text); line-height: 1.8; overflow-wrap: anywhere; }
        .my-template { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(260px, .85fr); gap: 16px; align-items: start; }
        .my-template + .my-template { margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--border); }
        .my-template-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .my-template-head h3 { font-size: .93rem; display: flex; align-items: center; gap: 8px; }
        .my-vars { display: flex; gap: 6px; flex-wrap: wrap; }
        .my-var { font: inherit; font-size: .76rem; font-weight: 600; padding: 3px 9px; border-radius: 999px; cursor: pointer;
            border: 1px dashed var(--accent-line); background: #fff; color: var(--accent-dark); }
        .my-var:hover { background: var(--accent-soft); border-style: solid; }
        .my-var code { font-family: var(--mono); direction: ltr; font-size: .72rem; opacity: .8; }
        .my-phone { border-radius: 22px; padding: 14px; background: linear-gradient(180deg, #e9eef3 0%, #dfe6ed 100%); border: 1px solid var(--border-strong); }
        .my-bubble { background: #fff; border-radius: 16px 16px 4px 16px; padding: 11px 13px; font-size: .84rem; line-height: 1.85;
            white-space: pre-line; overflow-wrap: anywhere; box-shadow: var(--shadow-xs); color: var(--text); min-height: 60px; }
        .my-bubble a { color: var(--info); text-decoration: underline; direction: ltr; unicode-bidi: embed; }
        .my-bubble-meta { display: flex; justify-content: space-between; font-size: .72rem; color: var(--muted); margin-top: 8px; padding: 0 4px; }
        .my-warn-inline { color: var(--danger); font-weight: 700; }

        /* الجانب */
        .my-checklist { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
        .my-check { display: flex; gap: 11px; align-items: flex-start; padding: 11px 13px; border-radius: var(--r-sm); border: 1px solid var(--border); background: #fff; }
        .my-check-mark { width: 24px; height: 24px; border-radius: 50%; flex: 0 0 auto; display: grid; place-items: center; margin-top: 1px; }
        .my-check.is-done .my-check-mark { background: var(--success-soft); color: var(--success); border: 1px solid var(--success-line); }
        .my-check.is-todo .my-check-mark { background: var(--warning-soft); color: var(--warning); border: 1px solid var(--warning-line); }
        .my-check strong { display: block; font-size: .87rem; }
        .my-check span { display: block; color: var(--muted); font-size: .78rem; line-height: 1.65; }
        .my-progress { height: 8px; border-radius: 999px; background: var(--surface-alt); overflow: hidden; margin: 2px 0 14px; }
        .my-progress span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--accent) 0%, #14b8a6 100%); transition: width .3s; }
        .my-steps { counter-reset: step; list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
        .my-steps li { counter-increment: step; position: relative; padding-inline-start: 38px; font-size: .84rem; line-height: 1.75; color: var(--text-soft); }
        .my-steps li::before { content: counter(step); position: absolute; inset-inline-start: 0; top: 0; width: 26px; height: 26px; border-radius: 50%;
            display: grid; place-items: center; font-size: .78rem; font-weight: 700; background: var(--navy); color: #fff; }
        .my-uses { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .my-uses li { display: flex; align-items: center; gap: 10px; font-size: .85rem; color: var(--text-soft); }
        .my-uses li::before { content: ""; width: 7px; height: 7px; border-radius: 2px; background: var(--accent); flex: 0 0 auto; }

        /* شريط الحفظ */
        .my-savebar { position: sticky; bottom: 14px; z-index: 40; margin-top: 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px;
            padding: 12px 16px; border-radius: var(--r-md); background: rgba(255, 255, 255, .92); backdrop-filter: blur(8px);
            border: 1px solid var(--border); box-shadow: var(--shadow-lg); }
        .my-savebar-state { display: flex; align-items: center; gap: 8px; font-size: .86rem; color: var(--muted); font-weight: 600; }
        .my-savebar.is-dirty { border-color: var(--warning-line); }
        .my-savebar.is-dirty .my-savebar-state { color: var(--warning); }
        .my-savebar.is-dirty .my-dot { background: var(--warning); box-shadow: 0 0 0 4px rgba(181, 71, 8, .14); }

        @media (max-width: 1080px) { .my-side { position: static; } .my-template { grid-template-columns: 1fr; } }
        @media (max-width: 760px) {
            .my-grid-2 { grid-template-columns: 1fr; }
            .my-power { flex-direction: column; align-items: flex-start; }
            .my-savebar { flex-direction: column; align-items: stretch; }
            .my-copy { flex-direction: column; }
        }
    </style>
</head>
<body>
@php
    $s = $moyasarSettings;
    $enabled = (bool) old('is_enabled', $s['is_enabled'] ?? false);
    $reportsEnabled = (bool) old('reports_enabled', $s['reports_enabled'] ?? false);
    $environmentLabels = ['live' => 'بيئة الإنتاج', 'test' => 'بيئة الاختبار'];
    $envChip = $readiness['environment'] ? $environmentLabels[$readiness['environment']] : 'غير معروفة';
    $checks = [
        ['done' => $readiness['keys'], 'title' => 'مفتاحا الربط محفوظان', 'hint' => 'مفتاح النشر والمفتاح السري من لوحة ميسر.'],
        ['done' => $readiness['webhook'], 'title' => 'سر الويبهوك مضبوط', 'hint' => 'حتى تصل تأكيدات السداد والاسترداد تلقائياً.'],
        ['done' => $readiness['environment'] === 'live', 'title' => 'مفاتيح الإنتاج', 'hint' => $readiness['environment'] === 'test' ? 'المفاتيح الحالية تجريبية؛ لن تُحصّل مبالغ حقيقية.' : 'استخدم مفاتيح live عند التشغيل الفعلي.'],
        ['done' => $readiness['enabled'], 'title' => 'البوابة مفعّلة', 'hint' => 'يظهر خيار ميسر في العقود والطلبات بعد التفعيل.'],
    ];
    $doneCount = collect($checks)->where('done', true)->count();
    $keyMeta = function (string $value, string $prefix) {
        if ($value === '') return ['label' => 'غير محفوظ', 'class' => 'is-empty'];
        if (str_starts_with($value, $prefix . '_live')) return ['label' => 'Live', 'class' => 'is-live'];
        if (str_starts_with($value, $prefix . '_test')) return ['label' => 'Test', 'class' => 'is-test'];
        return ['label' => 'محفوظ', 'class' => 'is-empty'];
    };
    $icons = [
        'power' => '<path d="M12 3v8"/><path d="M6.4 6.9a8 8 0 1 0 11.2 0"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M18 5l2 2"/>',
        'bell' => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'file' => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M10 13h6M10 17h4"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8 9h8M8 12h5"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
        'plug' => '<path d="M9 3v5M15 3v5"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v4"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
    ];
    $icon = fn (string $name, string $class = 'my-icon') => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$name] . '</svg>';
    $templateDefaults = [];
    foreach (array_keys(\App\Support\PaymentMessageTemplates::TYPES) as $templateType) {
        $templateDefaults[$templateType] = \App\Support\PaymentMessageTemplates::defaultTemplate($templateType);
    }
    $samples = [
        'rental' => ['customer_name' => 'محمد العتيبي', 'contract_number' => 'R-1024', 'amount' => '4,500.00'],
        'transfer' => ['customer_name' => 'سارة القحطاني', 'contract_number' => '318', 'amount' => '7,250.00'],
    ];
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">بوابة الدفع الإلكتروني — ميسر</h1>
                <p class="brand-text">ربط حساب ميسر، وإشعارات السداد، ونصوص الفواتير والرسائل التي تصل للعملاء.</p>
            </div>
        </div>
        <div class="topbar-actions">
            <a class="btn" href="{{ route('dev-superadmin.modules') }}">لوحة Super Admin</a>
            @if (($s['reports_enabled'] ?? false) && \Illuminate\Support\Facades\Route::has('moyasar-payments.index'))
                <a class="btn" href="{{ route('moyasar-payments.index') }}" target="_blank" rel="noopener">{!! $icon('chart') !!} تقارير المدفوعات</a>
            @endif
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    @if (session('success') || session('error') || $errors->any())
        <div class="alerts">
            @if (session('success'))<div class="alert">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h2>إعدادات بوابة ميسر</h2>
            <p>كل ما يحتاجه النظام لإنشاء روابط الدفع وتأكيد السداد آلياً من ميسر، في مكان واحد.</p>
        </div>
        <div class="page-heading-meta">
            <span class="heading-chip {{ $readiness['enabled'] ? 'is-good' : 'is-bad' }}">البوابة: <b>{{ $readiness['enabled'] ? 'مفعّلة' : 'معطّلة' }}</b></span>
            <span class="heading-chip {{ $readiness['environment'] === 'live' ? 'is-good' : ($readiness['environment'] === 'test' ? 'is-bad' : '') }}">البيئة: <b>{{ $envChip }}</b></span>
            <span class="heading-chip {{ $readiness['webhook'] ? 'is-good' : 'is-bad' }}">الويبهوك: <b>{{ $readiness['webhook'] ? 'مضبوط' : 'غير مضبوط' }}</b></span>
            <span class="heading-chip">الجاهزية: <b>{{ $doneCount }} / {{ count($checks) }}</b></span>
        </div>
    </div>

    @if ($readiness['masked'])
        <div class="alerts"><div class="alert alert-warning">توجد قيمة محفوظة تحتوي على نجوم (*) وليست مفتاحاً حقيقياً. أعد إدخال مفاتيح ميسر الحقيقية قبل إنشاء روابط الدفع.</div></div>
    @endif
    @if ($readiness['mismatched'])
        <div class="alerts"><div class="alert alert-warning">مفتاح النشر والمفتاح السري من بيئتين مختلفتين (اختبار / إنتاج). استخدم مفتاحين من نفس البيئة.</div></div>
    @endif

    <form method="POST" action="{{ route('dev-superadmin.moyasar-settings.update') }}" id="moyasarForm" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="layout">
            <div class="my-stack">
                {{-- التشغيل --}}
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon">{!! $icon('power') !!}</span>
                            <div>
                                <h2>تشغيل البوابة</h2>
                                <p class="panel-subtitle">عند التعطيل يختفي خيار ميسر من نماذج العقود والطلبات ولا تُنشأ روابط جديدة. الروابط القائمة تبقى قابلة للمزامنة.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body my-stack">
                        <div class="my-power {{ $enabled ? 'is-on' : '' }}" id="powerCard">
                            <div>
                                <h3>استقبال المدفوعات عبر ميسر</h3>
                                <p>مدى، فيزا، ماستركارد، وApple Pay — لعقود التأجير وتجديدها، ونقل الخدمات، والتأجير بالساعة، وطلبات التطبيق.</p>
                                <span class="my-power-state"><span class="my-dot"></span><span id="powerState">{{ $enabled ? 'البوابة تعمل' : 'البوابة متوقفة' }}</span></span>
                            </div>
                            <label class="switch" title="تفعيل أو تعطيل بوابة ميسر">
                                <input type="hidden" name="is_enabled" value="0">
                                <input type="checkbox" name="is_enabled" value="1" id="is_enabled" {{ $enabled ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="my-power my-power-sub {{ $reportsEnabled ? 'is-on' : '' }}" id="reportsCard">
                            <div>
                                <h3>{!! $icon('chart', 'my-icon') !!} تقارير مدفوعات ميسر في موديول المالية</h3>
                                <p>يُظهر صفحة «تقارير مدفوعات ميسر» أسفل لوحة تحكم المالية لمن يملك صلاحية عرضها. عند الإخفاء تُغلق الصفحة وتصديرها للجميع.</p>
                                <span class="my-power-state"><span class="my-dot"></span><span id="reportsState">{{ $reportsEnabled ? 'ظاهرة في قائمة المالية' : 'مخفية' }}</span></span>
                            </div>
                            <label class="switch" title="إظهار أو إخفاء تقارير مدفوعات ميسر">
                                <input type="hidden" name="reports_enabled" value="0">
                                <input type="checkbox" name="reports_enabled" value="1" id="reports_enabled" {{ $reportsEnabled ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="field">
                            <label for="base_url">رابط Moyasar API <span class="required">*</span></label>
                            <input type="url" id="base_url" name="base_url" class="my-ltr" required
                                value="{{ old('base_url', $s['base_url'] ?? 'https://api.moyasar.com/v1') }}">
                            <p class="help">القيمة الافتراضية <code class="my-ltr">https://api.moyasar.com/v1</code> مناسبة لبيئتي الاختبار والإنتاج؛ البيئة تحددها المفاتيح.</p>
                        </div>
                    </div>
                </section>

                {{-- المفاتيح --}}
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon is-navy">{!! $icon('key') !!}</span>
                            <div>
                                <h2>مفاتيح الربط</h2>
                                <p class="panel-subtitle">من لوحة ميسر ← Settings ← API Keys. القيم المحفوظة لا تُعرض كاملة؛ اترك الحقل فارغاً للإبقاء عليها.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="my-grid-2">
                            @foreach ([
                                ['name' => 'publishable_key', 'label' => 'مفتاح النشر (Publishable Key)', 'prefix' => 'pk', 'placeholder' => 'pk_live_...'],
                                ['name' => 'secret_key', 'label' => 'المفتاح السري (Secret Key)', 'prefix' => 'sk', 'placeholder' => 'sk_live_...'],
                            ] as $keyField)
                                @php $meta = $keyMeta($s[$keyField['name']] ?? '', $keyField['prefix']); @endphp
                                <div class="field">
                                    <label for="{{ $keyField['name'] }}">{{ $keyField['label'] }}</label>
                                    <div class="my-secret">
                                        <input type="password" id="{{ $keyField['name'] }}" name="{{ $keyField['name'] }}" class="my-ltr"
                                            value="{{ old($keyField['name']) }}" placeholder="{{ $keyField['placeholder'] }}" autocomplete="new-password" spellcheck="false">
                                        <button type="button" class="my-reveal" data-reveal="{{ $keyField['name'] }}" title="إظهار / إخفاء">{!! $icon('eye') !!}</button>
                                    </div>
                                    <div class="my-saved">
                                        <span>المحفوظ: <code>{{ \App\Support\MoyasarSettings::mask($s[$keyField['name']] ?? '') }}</code></span>
                                        <span class="my-pill {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="my-test">
                            <button type="button" class="btn" id="testConnection" data-url="{{ route('dev-superadmin.moyasar-settings.test') }}">
                                {!! $icon('plug') !!} اختبار الاتصال بميسر
                            </button>
                            <span class="my-test-result" id="testResult" role="status"></span>
                            <p class="help" style="flex-basis:100%">يختبر المفتاح السري <b>المحفوظ</b> بطلب قراءة فقط، دون إنشاء أي فاتورة. احفظ المفاتيح الجديدة أولاً.</p>
                        </div>
                    </div>
                </section>

                {{-- الويبهوك --}}
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon is-navy">{!! $icon('bell') !!}</span>
                            <div>
                                <h2>إشعارات السداد (Webhook)</h2>
                                <p class="panel-subtitle">يرسل ميسر عبرها تأكيد السداد والاسترداد، فيُسجَّل سند القبض أو الصرف دون تدخل الموظف.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body my-stack">
                        <div class="field">
                            <label for="webhookUrl">رابط الويبهوك — انسخه إلى لوحة ميسر (Settings ← Webhooks)</label>
                            <div class="my-copy">
                                <input type="text" id="webhookUrl" class="my-ltr" value="{{ $webhookUrl }}" readonly>
                                <button type="button" class="btn" data-copy="webhookUrl">{!! $icon('copy') !!} <span>نسخ</span></button>
                            </div>
                        </div>
                        <div class="field">
                            <label>الأحداث المطلوب تفعيلها في ميسر</label>
                            <div class="my-events">
                                <span class="my-event">payment_paid</span>
                                <span class="my-event">payment_failed</span>
                                <span class="my-event">payment_refunded</span>
                            </div>
                        </div>
                        <div class="field">
                            <label for="webhook_secret">سر الويبهوك (Secret Token)</label>
                            <div class="my-secret">
                                <input type="password" id="webhook_secret" name="webhook_secret" class="my-ltr" value="{{ old('webhook_secret') }}"
                                    placeholder="Secret Token" autocomplete="new-password" spellcheck="false">
                                <button type="button" class="my-reveal" data-reveal="webhook_secret" title="إظهار / إخفاء">{!! $icon('eye') !!}</button>
                            </div>
                            <div class="my-saved">
                                <span>المحفوظ: <code>{{ \App\Support\MoyasarSettings::mask($s['webhook_secret'] ?? '') }}</code></span>
                                <span class="my-pill {{ $readiness['webhook'] ? 'is-live' : 'is-empty' }}">{{ $readiness['webhook'] ? 'مضبوط' : 'غير محفوظ' }}</span>
                            </div>
                            <p class="help">لا تُقبل أي إشعارات ما لم يكن السر مضبوطاً ومطابقاً. حالة السداد تُجلب دائماً من ميسر للتحقق، ولا تؤخذ من الإشعار وحده.</p>
                        </div>
                    </div>
                </section>

                {{-- نص الفاتورة --}}
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon">{!! $icon('file') !!}</span>
                            <div>
                                <h2>نص فاتورة ميسر</h2>
                                <p class="panel-subtitle">يظهر للعميل في صفحة الدفع قبل وصف السجل. اتركهما فارغين للإبقاء على الوصف الحالي دون تغيير.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body my-stack">
                        <div class="my-grid-2">
                            <div class="field">
                                <label for="company_name">اسم الشركة في الفاتورة</label>
                                <input type="text" id="company_name" name="company_name" maxlength="255" data-preview="invoice"
                                    value="{{ old('company_name', $s['company_name'] ?? '') }}" placeholder="مثال: شركة كليك للاستقدام">
                            </div>
                            <div class="field">
                                <label for="payment_description">وصف إضافي للدفعة</label>
                                <input type="text" id="payment_description" name="payment_description" maxlength="500" data-preview="invoice"
                                    value="{{ old('payment_description', $s['payment_description'] ?? '') }}" placeholder="مثال: خدمات التأجير">
                            </div>
                        </div>
                        <div class="my-preview">
                            <div class="my-preview-label">{!! $icon('eye') !!} كيف يظهر الوصف في صفحة دفع ميسر</div>
                            <div class="my-invoice-line" id="invoicePreview"></div>
                        </div>
                    </div>
                </section>

                {{-- الرسائل --}}
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon">{!! $icon('chat') !!}</span>
                            <div>
                                <h2>رسائل رابط الدفع</h2>
                                <p class="panel-subtitle">النص الذي يصل العميل عبر SMS أو واتساب. انقر على أي متغير لإدراجه، والسطر الذي يحوي متغيراً فارغاً لا يُرسل.</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="field" style="max-width:520px;margin-bottom:22px">
                            <label for="payment_messages_company">اسم الشركة في الرسائل <code class="my-ltr">{company_name}</code></label>
                            <input type="text" id="payment_messages_company" name="payment_messages[company_name]" maxlength="255" data-preview="messages"
                                value="{{ old('payment_messages.company_name', $paymentMessages['company_name'] ?? '') }}"
                                placeholder="{{ ($s['company_name'] ?? '') !== '' ? $s['company_name'] . ' (من نص الفاتورة)' : 'اختياري' }}">
                        </div>

                        @foreach (\App\Support\PaymentMessageTemplates::TYPES as $type => $meta)
                            <div class="my-template">
                                <div class="field">
                                    <div class="my-template-head">
                                        <h3><span class="my-head-icon" style="width:30px;height:30px">{!! $icon($type === 'rental' ? 'file' : 'list', 'my-icon') !!}</span> {{ $meta['label'] }}</h3>
                                        <button type="button" class="btn btn-small" data-reset="{{ $type }}">استعادة النص الافتراضي</button>
                                    </div>
                                    <textarea id="payment_messages_{{ $type }}" name="payment_messages[{{ $type }}]" rows="7" maxlength="1000" dir="auto"
                                        data-template="{{ $type }}">{{ old('payment_messages.' . $type, $paymentMessages[$type] ?? '') }}</textarea>
                                    <div class="my-vars">
                                        @foreach (\App\Support\PaymentMessageTemplates::VARIABLES as $variable => $label)
                                            <button type="button" class="my-var" data-insert="{{ $type }}" data-variable="{{ $variable }}">{{ $label }} <code>{{ '{' . $variable . '}' }}</code></button>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <div class="my-preview-label">{!! $icon('eye') !!} معاينة بمثال</div>
                                    <div class="my-phone">
                                        <div class="my-bubble" id="bubble_{{ $type }}"></div>
                                        <div class="my-bubble-meta"><span id="count_{{ $type }}"></span><span>رسالة نصية</span></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="my-side">
                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon">{!! $icon('shield') !!}</span>
                            <div><h2>جاهزية الربط</h2><p class="panel-subtitle">{{ $doneCount }} من {{ count($checks) }} خطوات مكتملة.</p></div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="my-progress"><span style="width: {{ round($doneCount / count($checks) * 100) }}%"></span></div>
                        <ul class="my-checklist">
                            @foreach ($checks as $check)
                                <li class="my-check {{ $check['done'] ? 'is-done' : 'is-todo' }}">
                                    <span class="my-check-mark">{!! $icon($check['done'] ? 'check' : 'clock', 'my-icon') !!}</span>
                                    <div><strong>{{ $check['title'] }}</strong><span>{{ $check['hint'] }}</span></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon is-navy">{!! $icon('list') !!}</span>
                            <div><h2>خطوات الربط في ميسر</h2></div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <ol class="my-steps">
                            <li>من لوحة ميسر انسخ <b>مفتاح النشر</b> و<b>المفتاح السري</b> من نفس البيئة، واحفظهما هنا.</li>
                            <li>أضف <b>رابط الويبهوك</b> في ميسر بطريقة POST، وفعّل الأحداث الثلاثة المذكورة.</li>
                            <li>اختر <b>Secret Token</b> في ميسر وضع نفس القيمة في حقل سر الويبهوك.</li>
                            <li>اضغط <b>اختبار الاتصال</b>، ثم فعّل البوابة واحفظ.</li>
                            <li>جرّب رابط دفع بمبلغ صغير قبل إرسال الروابط للعملاء.</li>
                        </ol>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div class="my-head-title">
                            <span class="my-head-icon is-warn">{!! $icon('plug') !!}</span>
                            <div><h2>أين تُستخدم ميسر</h2></div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <ul class="my-uses">
                            <li>عقود التأجير: رابط عند الإنشاء ومن تفاصيل العقد</li>
                            <li>تجديد العقود من تنبيه انتهاء العقد</li>
                            <li>طلبات نقل الخدمات (المسار المالي)</li>
                            <li>التأجير بالساعة من الموقع والتطبيق</li>
                            <li>حجوزات تطبيق العملاء</li>
                        </ul>
                        <p class="help" style="margin-top:12px">إغلاق العقد أو إلغاء الطلب يلغي فاتورته غير المدفوعة في ميسر تلقائياً.</p>
                    </div>
                </section>
            </aside>
        </div>

        <div class="my-savebar" id="saveBar">
            <span class="my-savebar-state"><span class="my-dot"></span><span id="saveState">لا توجد تغييرات غير محفوظة</span></span>
            <div class="form-actions">
                <a class="btn" href="{{ route('dev-superadmin.moyasar-settings') }}">تراجع</a>
                <button type="submit" class="btn btn-primary">{!! $icon('check') !!} حفظ الإعدادات</button>
            </div>
        </div>
    </form>
</main>

<script>
(function () {
    var form = document.getElementById('moyasarForm');
    var defaults = @json($templateDefaults);
    var samples = @json($samples);
    var savedCompany = @json($s['company_name'] ?? '');
    var link = 'https://checkout.moyasar.com/invoices/…';

    // مفتاح التشغيل
    var power = document.getElementById('is_enabled');
    power.addEventListener('change', function () {
        document.getElementById('powerCard').classList.toggle('is-on', power.checked);
        document.getElementById('powerState').textContent = power.checked ? 'البوابة تعمل' : 'البوابة متوقفة';
    });
    var reports = document.getElementById('reports_enabled');
    reports.addEventListener('change', function () {
        document.getElementById('reportsCard').classList.toggle('is-on', reports.checked);
        document.getElementById('reportsState').textContent = reports.checked ? 'ظاهرة في قائمة المالية' : 'مخفية';
    });

    // إظهار / إخفاء المفاتيح
    document.querySelectorAll('[data-reveal]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.reveal);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // نسخ رابط الويبهوك
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.copy);
            var done = function () {
                var label = button.querySelector('span');
                label.textContent = 'تم النسخ';
                setTimeout(function () { label.textContent = 'نسخ'; }, 1800);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(input.value).then(done);
            } else {
                input.select();
                document.execCommand('copy');
                done();
            }
        });
    });

    // معاينة وصف الفاتورة
    function renderInvoice() {
        var parts = [document.getElementById('company_name').value.trim(), document.getElementById('payment_description').value.trim(), 'دفع عقد إيجار رقم R-1024 - محمد العتيبي'];
        document.getElementById('invoicePreview').textContent = parts.filter(Boolean).join(' — ');
    }

    // معاينة الرسائل: نفس قاعدة الخادم (يُحذف السطر الذي يحوي متغيراً فارغاً)
    var known = @json(array_keys(\App\Support\PaymentMessageTemplates::VARIABLES));
    function renderMessage(type) {
        var area = document.getElementById('payment_messages_' + type);
        var text = area.value.trim() === '' ? defaults[type] : area.value;
        var company = document.getElementById('payment_messages_company').value.trim() || savedCompany;
        var values = Object.assign({company_name: company, payment_link: link}, samples[type]);
        var lines = text.split(/\r?\n/).map(function (line) {
            var missing = false;
            var out = line.replace(/\{(\w+)\}/g, function (match, name) {
                if (known.indexOf(name) === -1) return match;
                var value = (values[name] || '').toString().trim();
                if (!value) missing = true;
                return value;
            });
            return missing ? null : out.replace(/\s+$/, '');
        }).filter(function (line) { return line !== null; });
        var bubble = document.getElementById('bubble_' + type);
        bubble.textContent = lines.join('\n').trim();
        var counter = document.getElementById('count_' + type);
        if (area.value.trim() !== '' && area.value.indexOf('{payment_link}') === -1) {
            counter.innerHTML = '<span class="my-warn-inline">النص بلا {payment_link}: لن يصل الرابط</span>';
        } else {
            counter.textContent = bubble.textContent.length + ' حرف';
        }
    }

    document.querySelectorAll('[data-insert]').forEach(function (button) {
        button.addEventListener('click', function () {
            var area = document.getElementById('payment_messages_' + button.dataset.insert);
            var token = '{' + button.dataset.variable + '}';
            var start = area.selectionStart ?? area.value.length;
            var end = area.selectionEnd ?? area.value.length;
            area.value = area.value.slice(0, start) + token + area.value.slice(end);
            area.focus();
            area.selectionStart = area.selectionEnd = start + token.length;
            area.dispatchEvent(new Event('input', {bubbles: true}));
        });
    });

    document.querySelectorAll('[data-reset]').forEach(function (button) {
        button.addEventListener('click', function () {
            var area = document.getElementById('payment_messages_' + button.dataset.reset);
            area.value = defaults[button.dataset.reset];
            area.dispatchEvent(new Event('input', {bubbles: true}));
        });
    });

    document.querySelectorAll('[data-template]').forEach(function (area) {
        area.addEventListener('input', function () { renderMessage(area.dataset.template); });
    });
    document.querySelectorAll('[data-preview="invoice"]').forEach(function (input) { input.addEventListener('input', renderInvoice); });
    document.getElementById('payment_messages_company').addEventListener('input', function () { Object.keys(defaults).forEach(renderMessage); });
    renderInvoice();
    Object.keys(defaults).forEach(renderMessage);

    // اختبار الاتصال
    var testButton = document.getElementById('testConnection');
    var testResult = document.getElementById('testResult');
    testButton.addEventListener('click', function () {
        testButton.disabled = true;
        testResult.className = 'my-test-result is-busy';
        testResult.textContent = 'جارٍ الاتصال بميسر…';
        fetch(testButton.dataset.url, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'},
        }).then(function (response) {
            return response.json().then(function (data) { return {ok: response.ok, data: data}; });
        }).then(function (result) {
            testResult.className = 'my-test-result ' + (result.ok ? 'is-ok' : 'is-bad');
            testResult.textContent = result.data.message || (result.ok ? 'تم الاتصال.' : 'تعذر الاتصال.');
        }).catch(function () {
            testResult.className = 'my-test-result is-bad';
            testResult.textContent = 'تعذر تنفيذ الاختبار. حاول مجدداً.';
        }).finally(function () { testButton.disabled = false; });
    });

    // تنبيه التغييرات غير المحفوظة
    var dirty = false;
    var saveBar = document.getElementById('saveBar');
    form.addEventListener('input', function () {
        if (dirty) return;
        dirty = true;
        saveBar.classList.add('is-dirty');
        document.getElementById('saveState').textContent = 'لديك تغييرات غير محفوظة';
    });
    form.addEventListener('change', function () { form.dispatchEvent(new Event('input')); });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (event) {
        if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });
})();
</script>
</body>
</html>
