@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات الربط والإرسال
@endsection

@section('style')
<style>
    .notif-settings { --ns-ink:#172033; --ns-muted:#6b7688; --ns-line:#e6eaf0; --ns-soft:#f6f8fb;
        --ns-blue:#2563eb; color:var(--ns-ink); }

    /* ——— تحييد أنماط الجداول العامة في style.css / style-rtl.css التي تفرض
       ظلاً وهوامش على كل <table> و padding:16px !important على رؤوس الأعمدة ——— */
    .notif-settings table { margin:0; box-shadow:none; border-collapse:collapse; border-spacing:0; }
    .notif-settings thead { background:none; box-shadow:none; }
    .notif-settings tbody tr:hover { transform:none; }
    .notif-settings th, .notif-settings td { padding:9px 12px !important; vertical-align:middle;
        border-bottom:1px solid var(--ns-line); font-size:.83rem; }
    .notif-settings thead th { background:var(--ns-soft); color:#3a4658; font-weight:700;
        white-space:nowrap; border-bottom:1px solid #dde3ec; }
    .notif-settings .table-responsive { border:1px solid var(--ns-line); border-radius:10px;
        overflow:hidden; background:#fff; }

    /* ——— ترويسة الصفحة وأزرار التنقل ——— */
    .notif-settings .ns-hero { display:flex; flex-wrap:wrap; align-items:center;
        justify-content:space-between; gap:14px; padding:18px 20px; margin-bottom:16px;
        border-radius:16px; color:#fff;
        background:linear-gradient(125deg,#132238 0%,#1d3553 68%,#27507a 100%); }
    .notif-settings .ns-hero h4 { color:#fff; margin:0 0 4px; font-weight:800; font-size:1.18rem; }
    .notif-settings .ns-hero .ns-hero-sub { margin:0; font-size:.82rem; opacity:.75; line-height:1.8; }
    .notif-settings .ns-hero-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .notif-settings .ns-hero-actions .btn { --bs-btn-padding-y:.34rem; --bs-btn-padding-x:.7rem;
        font-size:.79rem; font-weight:700; border-radius:9px; background:rgba(255,255,255,.1);
        border-color:rgba(255,255,255,.28); color:#fff; }
    .notif-settings .ns-hero-actions .btn:hover { background:rgba(255,255,255,.22); color:#fff;
        border-color:rgba(255,255,255,.45); }

    /* ——— شريط الانتقال بين الأقسام ——— */
    .notif-settings .ns-nav { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
    .notif-settings .ns-nav a { display:inline-flex; align-items:center; gap:7px; padding:7px 14px;
        border:1px solid var(--ns-line); border-radius:999px; background:#fff; color:#3a4658;
        font-size:.82rem; font-weight:700; text-decoration:none; transition:all .15s ease; }
    .notif-settings .ns-nav a:hover { border-color:#bfd4ff; background:#eff5ff; color:var(--ns-blue); }
    .notif-settings .ns-nav a i { color:var(--ns-blue); }

    /* ——— بطاقات الأقسام ——— */
    .notif-settings .ns-card { border:1px solid var(--ns-line); border-radius:16px;
        margin-bottom:20px; box-shadow:0 2px 10px rgba(15,27,45,.04); scroll-margin-top:96px; }
    .notif-settings .ns-card > .card-header { background:#fbfcfe; border-bottom:1px solid var(--ns-line);
        border-radius:16px 16px 0 0; padding:15px 20px; }
    .notif-settings .ns-card > .card-header h5 { margin:0 0 3px; font-size:1rem; font-weight:800; }
    .notif-settings .ns-card > .card-body { padding:20px; }
    .notif-settings .ns-card .card-header .badge { font-size:.7rem; font-weight:700;
        padding:.42em .75em; border-radius:999px; }

    /* ——— صندوق «الإعدادات الحالية»: شبكة موحّدة بدل أعمدة متفاوتة ——— */
    .notif-settings .cfg-box { background:var(--ns-soft); border:1px solid var(--ns-line);
        border-radius:12px; padding:14px 16px; margin-bottom:18px; }
    .notif-settings .cfg-box > .cfg-box-title { display:flex; align-items:center; gap:7px;
        margin-bottom:11px; font-size:.83rem; font-weight:800; color:#3a4658; }
    .notif-settings .cfg-grid { display:grid; gap:9px;
        grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); }
    .notif-settings .cfg-item { min-width:0; background:#fff; border:1px solid var(--ns-line);
        border-radius:10px; padding:9px 11px; }
    .notif-settings .cfg-item > span { display:block; margin-bottom:3px; color:var(--ns-muted);
        font-size:.71rem; font-weight:700; line-height:1.5; }
    .notif-settings .cfg-item > b { display:block; font-size:.82rem; font-weight:700;
        line-height:1.6; overflow-wrap:anywhere; }
    .notif-settings .cfg-item.is-wide { grid-column:span 2; }
    @media (max-width:575.98px) { .notif-settings .cfg-item.is-wide { grid-column:auto; } }

    /* ——— صناديق فرعية داخل النموذج ——— */
    .notif-settings .ns-subcard { background:#fff; border:1px solid var(--ns-line);
        border-radius:12px; padding:16px; }
    .notif-settings .ns-subcard > .ns-subcard-head { display:flex; flex-wrap:wrap;
        align-items:flex-start; justify-content:space-between; gap:10px 16px; margin-bottom:14px; }
    .notif-settings .ns-subcard > .ns-subcard-head > * { min-width:0; }
    .notif-settings .ns-subcard h6 { margin:0 0 3px; font-size:.9rem; font-weight:800; }
    .notif-settings .ns-subcard .text-muted.small { line-height:1.8; }

    /* ——— خانات التفعيل: صندوق مستقل كي لا تتداخل مع الحقول المجاورة ——— */
    .notif-settings .ns-toggle { display:block; height:100%; background:var(--ns-soft);
        border:1px solid var(--ns-line); border-radius:11px; padding:11px 13px; }
    .notif-settings .ns-toggle .form-check { margin:0; min-height:auto; }
    .notif-settings .ns-toggle .form-check-label { font-size:.83rem; font-weight:700; cursor:pointer; }
    .notif-settings .ns-toggle .form-text { margin-top:5px; font-size:.73rem; line-height:1.7; }

    /* ——— الحقول ——— */
    .notif-settings .form-label { margin-bottom:5px; font-size:.79rem; font-weight:700; color:#42506b; }
    .notif-settings .form-text { color:var(--ns-muted); font-size:.73rem; line-height:1.7; }
    .notif-settings code { background:#eef2f8; border-radius:5px; padding:1px 5px;
        color:#1d4ed8; font-size:.78rem; }
    .notif-settings .ns-actions { display:flex; justify-content:flex-end; gap:8px;
        padding-top:14px; margin-top:4px; border-top:1px dashed var(--ns-line); }

    /* ——— بطاقات أرقام UltraMsg ——— */
    .notif-settings .ultramsg-instance { border:1px solid var(--ns-line) !important;
        border-radius:12px; background:var(--ns-soft); }
    .notif-settings .ultramsg-instance .card-body { padding:14px 16px; }
    .notif-settings .ns-instance-footer { display:flex; flex-wrap:wrap; align-items:center; gap:10px 20px;
        padding-top:11px; border-top:1px dashed var(--ns-line); }
    .notif-settings .ns-instance-footer .form-check { margin:0; min-height:auto; }
    .notif-settings .ns-instance-footer .form-check-label { font-size:.81rem; font-weight:700; cursor:pointer; }
    .notif-settings .ns-instance-footer .btn { margin-inline-start:auto; }

    /* ——— التنبيهات داخل النموذج ——— */
    .notif-settings .alert { border-radius:11px; }
    .notif-settings .alert.small, .notif-settings .alert-info.small { line-height:1.9; }

    @media (max-width:575.98px) {
        .notif-settings .ns-hero { padding:15px; }
        .notif-settings .ns-card > .card-body { padding:14px; }
        .notif-settings .ns-hero-actions { width:100%; }
    }
</style>
@endsection

@section('content')
    <form id="chatwootConnectionTest" method="POST" action="{{ route('notifications.settings.whatsapp.test') }}">@csrf</form>
@if (\App\Support\MessagingPermissions::allows(\App\Support\MessagingPermissions::INTEGRATIONS))
    @php
        $oldSection = old('section');
        $communicationSettings = \App\Support\CommunicationSettings::all();
        $currentSettings = $communicationSettings;
        $maskSecret = function ($value, int $visible = 4) {
            $value = trim((string) $value);
            if ($value === '') {
                return 'غير محفوظ';
            }

            $length = strlen($value);
            if ($length <= $visible) {
                return str_repeat('*', $length);
            }

            return str_repeat('*', max(6, $length - $visible)) . substr($value, -$visible);
        };

        $emailData = $communicationSettings['email'] ?? [];
        if ($oldSection === 'email') {
            $emailData = array_replace_recursive($emailData, old('email', []));
        }

        $smsData = $communicationSettings['sms'] ?? [];
        if ($oldSection === 'sms') {
            $smsData = array_replace_recursive($smsData, old('sms', []));
        }
        $smsProvider = data_get($smsData, 'provider', 'msegat');
        $currentSmsProvider = data_get($currentSettings, 'sms.provider', 'msegat');
        $currentTaqnyatBaseUrl = data_get($currentSettings, 'sms.taqnyat.base_url') ?: 'https://api.taqnyat.sa';

        $whatsappData = $communicationSettings['whatsapp'] ?? [];
        if ($oldSection === 'whatsapp') {
            $whatsappData = array_replace_recursive($whatsappData, old('whatsapp', []));
        }
        $webhookVerifyToken = data_get($whatsappData, 'meta.webhook_verify_token') ?: \Illuminate\Support\Str::random(48);
        $metaAppId = (string) data_get($currentSettings, 'whatsapp.meta.app_id', '');
        $metaConfigurationId = (string) data_get($currentSettings, 'whatsapp.meta.configuration_id', '');
        $metaEmbeddedSignupReady = $metaAppId !== ''
            && $metaConfigurationId !== ''
            && (string) data_get($currentSettings, 'whatsapp.meta.app_secret', '') !== '';
        $metaEmbeddedSignupAvailable = $metaEmbeddedSignupReady && request()->secure();
        $metaConnected = (string) data_get($currentSettings, 'whatsapp.meta.business_account_id', '') !== ''
            && (string) data_get($currentSettings, 'whatsapp.meta.phone_number_id', '') !== ''
            && (string) data_get($currentSettings, 'whatsapp.meta.access_token', '') !== '';

        $ultraMsgInstances = data_get($whatsappData, 'ultramsg.instances', []);
        if (empty($ultraMsgInstances)) {
            $ultraMsgInstances = [[
                'key' => uniqid('ultramsg_', false),
                'name' => '',
                'phone_number' => '',
                'instance_id' => '',
                'token' => '',
                'enabled' => true,
            ]];
        }
        $defaultUltraMsgKey = data_get($whatsappData, 'ultramsg.default_instance_key', '');
    @endphp

    <div class="container-fluid notif-settings">
        @include('admin.ingaz.messaging._nav')
        <div class="ns-hero">
            <div>
                <h4>
                    <i class="fa fa-plug me-2"></i>
                    إعدادات الربط مع مزودي الإرسال
                </h4>
                <p class="ns-hero-sub">
                    بيانات الاتصال بمزودي البريد والرسائل النصية وواتساب (Click Live Chat وMeta وUltraMsg وWiders)، وإشعارات التطبيق عبر Firebase.
                    هذه الصفحة لمن يملك صلاحية «إدارة إعدادات الربط» فقط. قواعد التنبيه الآلي ومن تصله في «إعدادات التنبيهات».
                </p>
            </div>
        </div>
        <nav class="ns-nav">
            <a href="#clickchat-readiness"><i class="fa fa-list-check"></i> جاهزية Click Live Chat</a>
            <a href="#verification-settings"><i class="fa fa-key"></i> رمز التحقق</a>
            <a href="#whatsapp-settings"><i class="fa fa-whatsapp"></i> واتساب</a>
            <a href="#sms-settings"><i class="fa fa-sms"></i> الرسائل النصية SMS</a>
            <a href="#email-settings"><i class="fa fa-envelope"></i> البريد الإلكتروني</a>
            <a href="{{ route('dash.firebase-settings.index') }}"><i class="fa fa-mobile-alt"></i> إشعارات التطبيق (Firebase)</a>
        </nav>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('admin.ingaz.notifications.partials.verification-and-readiness')

        <div class="card ns-card" id="email-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5>إعدادات البريد الإلكتروني</h5>
                    <div class="text-muted small">هذا القسم خاص برسائل البريد مثل إعادة تعيين كلمة المرور والتنبيهات البريدية.</div>
                </div>
                <span class="badge bg-light text-dark">Mail</span>
            </div>
            <div class="card-body">
                <div class="cfg-box">
                    <div class="cfg-box-title"><i class="fa fa-circle-info"></i> الإعدادات الحالية</div>
                    <div class="cfg-grid">
                        <div class="cfg-item"><span>النوع</span><b>{{ data_get($currentSettings, 'email.mailer', 'smtp') }}</b></div>
                        <div class="cfg-item"><span>Host</span><b>{{ data_get($currentSettings, 'email.host') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>Port</span><b>{{ data_get($currentSettings, 'email.port', 587) }}</b></div>
                        <div class="cfg-item"><span>Encryption</span><b>{{ data_get($currentSettings, 'email.encryption') ?: 'بدون' }}</b></div>
                        <div class="cfg-item"><span>بريد المرسل</span><b>{{ data_get($currentSettings, 'email.from_address') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>اسم المرسل</span><b>{{ data_get($currentSettings, 'email.from_name') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>اسم المستخدم</span><b>{{ data_get($currentSettings, 'email.username') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>كلمة المرور</span><b>{{ $maskSecret(data_get($currentSettings, 'email.password')) }}</b></div>
                    </div>
                </div>

                <form method="post" action="{{ route('notifications.settings.update') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="section" value="email">

                    <div class="col-md-3">
                        <label class="form-label">نوع المرسل</label>
                        <select name="email[mailer]" class="form-control">
                            <option value="smtp" {{ data_get($emailData, 'mailer') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                            <option value="sendmail" {{ data_get($emailData, 'mailer') === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                            <option value="log" {{ data_get($emailData, 'mailer') === 'log' ? 'selected' : '' }}>Log</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Host</label>
                        <input type="text" name="email[host]" class="form-control"
                            value="{{ data_get($emailData, 'host') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Port</label>
                        <input type="number" name="email[port]" class="form-control"
                            value="{{ data_get($emailData, 'port', 587) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Encryption</label>
                        <select name="email[encryption]" class="form-control">
                            <option value="" {{ data_get($emailData, 'encryption', '') === '' ? 'selected' : '' }}>بدون</option>
                            <option value="tls" {{ data_get($emailData, 'encryption') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ data_get($emailData, 'encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">اسم المرسل</label>
                        <input type="text" name="email[from_name]" class="form-control"
                            value="{{ data_get($emailData, 'from_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="email[username]" class="form-control"
                            value="{{ data_get($emailData, 'username') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="email[password]" class="form-control"
                            placeholder="{{ data_get($currentSettings, 'email.password') ? 'هناك كلمة مرور محفوظة، اترك الحقل فارغًا للإبقاء عليها' : 'اتركها فارغة إذا لا توجد كلمة مرور' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">بريد المرسل</label>
                        <input type="email" name="email[from_address]" class="form-control"
                            value="{{ data_get($emailData, 'from_address') }}">
                    </div>
                    <div class="col-12 ns-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات البريد
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ns-card" id="sms-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5>إعدادات الرسائل النصية SMS</h5>
                    <div class="text-muted small">هذا القسم خاص بمزود الرسائل النصية وأسماء المرسل وحدود الإرسال الجماعي.</div>
                </div>
                <span class="badge bg-light text-dark">SMS API</span>
            </div>
            <div class="card-body">
                <div class="cfg-box">
                    <div class="cfg-box-title"><i class="fa fa-circle-info"></i> الإعدادات الحالية</div>
                    <div class="cfg-grid">
                        <div class="cfg-item"><span>البوابة</span><b>{{ $currentSmsProvider === 'taqnyat' ? 'تقنيات' : 'Msegat' }}</b></div>
                        <div class="cfg-item"><span>حالة بوابة SMS</span><b>{{ data_get($currentSettings, 'sms.gateway_status', 'on') === 'off' ? 'متوقفة' : 'مفعلة' }}</b></div>
                        @if ($currentSmsProvider === 'taqnyat')
                            <div class="cfg-item is-wide"><span>Base URL</span><b dir="ltr">{{ $currentTaqnyatBaseUrl }}</b></div>
                            <div class="cfg-item is-wide"><span>مسار الإرسال</span><b dir="ltr">POST {{ $currentTaqnyatBaseUrl }}/v1/messages</b></div>
                            <div class="cfg-item is-wide"><span>Authorization</span><b dir="ltr">Bearer {{ $maskSecret(data_get($currentSettings, 'sms.taqnyat.bearer_token')) }}</b></div>
                        @else
                            <div class="cfg-item is-wide"><span>رابط API</span><b dir="ltr">{{ data_get($currentSettings, 'sms.url') ?: 'غير محدد' }}</b></div>
                            <div class="cfg-item"><span>اسم المستخدم</span><b>{{ data_get($currentSettings, 'sms.username') ?: 'غير محدد' }}</b></div>
                            <div class="cfg-item"><span>API Key</span><b dir="ltr">{{ $maskSecret(data_get($currentSettings, 'sms.api_key')) }}</b></div>
                        @endif
                        <div class="cfg-item"><span>المرسل الافتراضي</span><b>{{ data_get($currentSettings, 'sms.sender') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>النوع الافتراضي</span><b>{{ data_get($currentSettings, 'sms.default_sender_type') === 'verification' ? 'تحقق' : 'إعلاني' }}</b></div>
                        <div class="cfg-item"><span>مرسل الإعلانات</span><b>{{ data_get($currentSettings, 'sms.advertising_sender') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>مرسل التحقق</span><b>{{ data_get($currentSettings, 'sms.verification_sender') ?: 'غير محدد' }}</b></div>
                        <div class="cfg-item"><span>App Domain</span><b dir="ltr">{{ data_get($currentSettings, 'sms.app_domain_for_msegat', 'aqro.sa') ?: 'aqro.sa' }}</b></div>
                        <div class="cfg-item"><span>الترميز</span><b>{{ data_get($currentSettings, 'sms.encoding', 'UTF8') }}</b></div>
                        <div class="cfg-item"><span>حد الإرسال</span><b>{{ data_get($currentSettings, 'sms.bulk_send_limit', 200) }}</b></div>
                        <div class="cfg-item"><span>كلمة التأكيد</span><b dir="ltr">{{ data_get($currentSettings, 'sms.bulk_confirmation_keyword', 'SEND_ALL_SMS') }}</b></div>
                        <div class="cfg-item"><span>التحقق بالجوال</span><b>{{ data_get($currentSettings, 'sms.disable_phone_verification') ? 'ملغى - الرمز 12345' : 'مفعل' }}</b></div>
                    </div>
                </div>

                <form method="post" action="{{ route('notifications.settings.update') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="section" value="sms">

                    <div class="col-md-3">
                        <label class="form-label">بوابة SMS</label>
                        <select name="sms[provider]" id="smsProvider" class="form-control">
                            <option value="msegat" {{ data_get($smsData, 'provider', 'msegat') === 'msegat' ? 'selected' : '' }}>Msegat</option>
                            <option value="taqnyat" {{ data_get($smsData, 'provider') === 'taqnyat' ? 'selected' : '' }}>تقنيات Taqnyat</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="ns-toggle">
                            <div class="form-check">
                                <input type="hidden" name="sms[gateway_status]" value="on">
                                <input class="form-check-input" type="checkbox" name="sms[gateway_status]" id="smsGatewayDisabled" value="off"
                                    {{ data_get($smsData, 'gateway_status', 'on') === 'off' ? 'checked' : '' }}>
                                <label class="form-check-label" for="smsGatewayDisabled">
                                    إلغاء بوابة الرسائل النصية SMS
                                </label>
                            </div>
                            <div class="form-text">عند التفعيل يتم حفظ حالة البوابة بقيمة off.</div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="ns-toggle">
                            <div class="form-check">
                                <input type="hidden" name="sms[disable_phone_verification]" value="0">
                                <input class="form-check-input" type="checkbox" name="sms[disable_phone_verification]" id="smsDisablePhoneVerification" value="1"
                                    {{ data_get($smsData, 'disable_phone_verification') ? 'checked' : '' }}>
                                <label class="form-check-label" for="smsDisablePhoneVerification">
                                    إلغاء التحقق عن طريق الجوال
                                </label>
                            </div>
                            <div class="form-text">عند التفعيل يتم قبول الرمز 12345 ولا يتم إرسال رسالة تحقق للعميل.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <i class="fa fa-bell me-1"></i>
                            تنبيه المسؤول عند وصول حجز من الموقع الخارجي انتقل إلى
                            <a href="{{ route('notifications.settings.alerts') }}#external-order-alert-settings">إعدادات التنبيهات</a>.
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="msegatSettingsBox" class="{{ $smsProvider === 'msegat' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <h6 class="mb-3">إعدادات Msegat</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label">رابط API لـ Msegat</label>
                                        <input type="url" name="sms[url]" class="form-control"
                                            value="{{ data_get($smsData, 'url') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">اسم مستخدم Msegat</label>
                                        <input type="text" name="sms[username]" class="form-control"
                                            value="{{ data_get($smsData, 'username') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">API Key لـ Msegat</label>
                                        <input type="password" name="sms[api_key]" class="form-control"
                                            placeholder="{{ data_get($currentSettings, 'sms.api_key') ? 'هناك API Key محفوظ، اترك الحقل فارغًا للإبقاء عليه' : 'اتركه فارغًا إذا لا توجد قيمة' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="taqnyatSettingsBox" class="{{ $smsProvider === 'taqnyat' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                                    <div>
                                        <h6 class="mb-1">إعدادات تقنيات Taqnyat</h6>
                                        <div class="text-muted small">حسب التوثيق: يتم الإرسال إلى <code>/v1/messages</code> مع Header باسم <code>Authorization: Bearer</code>.</div>
                                    </div>
                                    <a href="https://dev.taqnyat.sa/ar/doc/sms/" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                        توثيق تقنيات
                                    </a>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Base URL لبوابة تقنيات</label>
                                        <input type="url" name="sms[taqnyat][base_url]" class="form-control"
                                            value="{{ data_get($smsData, 'taqnyat.base_url', 'https://api.taqnyat.sa') }}"
                                            placeholder="https://api.taqnyat.sa">
                                        <div class="form-text">لا تضع <code>/v1/messages</code> هنا، النظام يضيف مسار الإرسال تلقائياً.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bearer Token لبوابة تقنيات</label>
                                        <input type="password" name="sms[taqnyat][bearer_token]" class="form-control"
                                            placeholder="{{ data_get($currentSettings, 'sms.taqnyat.bearer_token') ? 'هناك Bearer Token محفوظ، اترك الحقل فارغًا للإبقاء عليه' : 'أدخل Bearer Token من حساب تقنيات' }}">
                                        <div class="form-text">أدخل قيمة التوكن فقط بدون كلمة <code>Bearer</code>.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المرسل الافتراضي</label>
                        <input type="text" name="sms[sender]" class="form-control"
                            value="{{ data_get($smsData, 'sender') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">مرسل الإعلانات</label>
                        <input type="text" name="sms[advertising_sender]" class="form-control"
                            value="{{ data_get($smsData, 'advertising_sender') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">مرسل التحقق</label>
                        <input type="text" name="sms[verification_sender]" class="form-control"
                            value="{{ data_get($smsData, 'verification_sender') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">APP_DOMAIN_FOR_MSEGAT</label>
                        <input type="text" name="sms[app_domain_for_msegat]" class="form-control"
                            value="{{ data_get($smsData, 'app_domain_for_msegat', data_get($smsData, 'app_domain_name', 'aqro.sa')) }}"
                            placeholder="aqro.sa">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">نوع المرسل الافتراضي</label>
                        <select name="sms[default_sender_type]" class="form-control">
                            <option value="advertising" {{ data_get($smsData, 'default_sender_type') === 'advertising' ? 'selected' : '' }}>إعلاني</option>
                            <option value="verification" {{ data_get($smsData, 'default_sender_type') === 'verification' ? 'selected' : '' }}>تحقق</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الترميز</label>
                        <input type="text" name="sms[encoding]" class="form-control"
                            value="{{ data_get($smsData, 'encoding', 'UTF8') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">حد الإرسال الجماعي</label>
                        <input type="number" name="sms[bulk_send_limit]" class="form-control"
                            value="{{ data_get($smsData, 'bulk_send_limit', 200) }}" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كلمة تأكيد الإرسال الجماعي</label>
                        <input type="text" name="sms[bulk_confirmation_keyword]" class="form-control"
                            value="{{ data_get($smsData, 'bulk_confirmation_keyword', 'SEND_ALL_SMS') }}">
                    </div>
                    <div class="col-12 ns-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ns-card" id="whatsapp-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5>إعدادات WhatsApp</h5>
                    <div class="text-muted small">اختيار مزود واتساب: Click Live Chat (المعتمد للإرسال بقوالب Meta المعتمدة)، أو Meta API مباشرة، أو UltraMsg، أو Widers / Chatwoot.</div>
                </div>
                <span class="badge bg-light text-dark">WhatsApp</span>
            </div>
            <div class="card-body">
                <div class="cfg-box">
                    <div class="cfg-box-title"><i class="fa fa-circle-info"></i> الإعدادات الحالية</div>
                    <div class="cfg-grid">
                        <div class="cfg-item"><span>المزود الحالي</span><b>{{ app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->providerLabel() }}</b></div>
                        <div class="cfg-item"><span>رمز الدولة</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.default_country_code', '966') }}</b></div>
                        <div class="cfg-item"><span>Timeout</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.timeout', 15) }}</b></div>
                        <div class="cfg-item"><span>حد الإرسال</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.bulk_send_limit', 200) }}</b></div>
                        <div class="cfg-item">
                            <span>الغرض الافتراضي</span>
                            @php $purpose = data_get($currentSettings, 'whatsapp.default_purpose', 'verification'); @endphp
                            <b>{{ $purpose === 'follow_up' ? 'متابعة' : ($purpose === 'advertising' ? 'دعائي' : 'تحقق') }}</b>
                        </div>
                        <div class="cfg-item is-wide"><span>كلمة التأكيد</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.bulk_confirmation_keyword', 'SEND_ALL_WHATSAPP') }}</b></div>
                        <div class="cfg-item"><span>الإرسال الدعائي</span><b>{{ data_get($currentSettings, 'whatsapp.allow_advertising') ? 'مفعّل' : 'غير مفعّل' }}</b></div>
                    </div>
                </div>

                <form method="post" action="{{ route('notifications.settings.update') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="section" value="whatsapp">

                    <div class="col-md-3">
                        <label class="form-label">مزود واتساب</label>
                        <select name="whatsapp[driver]" id="whatsappDriver" class="form-control">
                            <option value="chatwoot" {{ data_get($whatsappData, 'driver') === 'chatwoot' ? 'selected' : '' }}>Widers / Chatwoot</option>
                            <option value="meta" {{ data_get($whatsappData, 'driver') === 'meta' ? 'selected' : '' }}>Meta API</option>
                            <option value="ultramsg" {{ data_get($whatsappData, 'driver') === 'ultramsg' ? 'selected' : '' }}>UltraMsg</option>
                            <option value="clickchat" {{ data_get($whatsappData, 'driver') === 'clickchat' ? 'selected' : '' }}>Click Live Chat</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">رمز الدولة الافتراضي</label>
                        <input type="text" name="whatsapp[default_country_code]" class="form-control"
                            value="{{ data_get($whatsappData, 'default_country_code', '966') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Timeout</label>
                        <input type="number" name="whatsapp[timeout]" class="form-control"
                            value="{{ data_get($whatsappData, 'timeout', 15) }}" min="5">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">حد الإرسال الجماعي</label>
                        <input type="number" name="whatsapp[bulk_send_limit]" class="form-control"
                            value="{{ data_get($whatsappData, 'bulk_send_limit', 200) }}" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الغرض الافتراضي</label>
                        <select name="whatsapp[default_purpose]" class="form-control">
                            <option value="verification" {{ data_get($whatsappData, 'default_purpose') === 'verification' ? 'selected' : '' }}>تحقق</option>
                            <option value="follow_up" {{ data_get($whatsappData, 'default_purpose') === 'follow_up' ? 'selected' : '' }}>متابعة</option>
                            <option value="advertising" {{ data_get($whatsappData, 'default_purpose') === 'advertising' ? 'selected' : '' }}>دعائي</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">كلمة تأكيد الإرسال الجماعي</label>
                        <input type="text" name="whatsapp[bulk_confirmation_keyword]" class="form-control"
                            value="{{ data_get($whatsappData, 'bulk_confirmation_keyword', 'SEND_ALL_WHATSAPP') }}">
                    </div>
                    <div class="col-md-6">
                        <div class="ns-toggle">
                            <div class="form-check">
                                <input type="hidden" name="whatsapp[allow_advertising]" value="0">
                                <input class="form-check-input" type="checkbox" name="whatsapp[allow_advertising]" id="whatsappAllowAdvertising" value="1"
                                    {{ data_get($whatsappData, 'allow_advertising') ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsappAllowAdvertising">
                                    السماح بالإرسال الدعائي عبر واتساب
                                </label>
                            </div>
                            <div class="form-text">يُستخدم للرسائل الدعائية خارج نافذة خدمة واتساب.</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="ns-toggle">
                            <div class="form-check">
                                <input type="hidden" name="whatsapp[opt_out][enabled]" value="0">
                                <input class="form-check-input" type="checkbox" name="whatsapp[opt_out][enabled]" id="whatsappOptOut" value="1"
                                    {{ data_get($whatsappData, 'opt_out.enabled') ? 'checked' : '' }}>
                                <label class="form-check-label" for="whatsappOptOut">
                                    إيقاف الرسائل الآلية لمن يطلب ذلك
                                </label>
                            </div>
                            <div class="form-text">إذا رد العميل بإحدى كلمات الإيقاف (الرسالة كاملة)، لا تصله رسائل واتساب الآلية بعدها. رموز التحقق وردود الموظفين من صندوق الوارد تصله دائمًا. تعمل مع Click Live Chat و Meta.</div>
                            <div class="row g-2 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label small">كلمات الإيقاف (مفصولة بفاصلة)</label>
                                    <input type="text" class="form-control" name="whatsapp[opt_out][stop_keywords]"
                                        value="{{ data_get($whatsappData, 'opt_out.stop_keywords') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">كلمات إعادة التفعيل</label>
                                    <input type="text" class="form-control" name="whatsapp[opt_out][start_keywords]"
                                        value="{{ data_get($whatsappData, 'opt_out.start_keywords') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="chatwootSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'chatwoot' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <div class="ns-subcard-head">
                                    <div>
                                        <h6>ربط Widers / Chatwoot</h6>
                                        <div class="text-muted small">احفظ عنوان المنصة ومعرف الحساب والرمز، ثم اختبر الاتصال لمعرفة صناديق الوارد. يلزم اختيار صندوق WhatsApp قبل الإرسال.</div>
                                    </div>
                                    <button type="submit" form="chatwootConnectionTest" class="btn btn-outline-primary btn-sm">
                                        <i class="fa fa-plug me-1"></i>
                                        اختبار الاتصال وجلب الصناديق
                                    </button>
                                </div>

                                <div class="row g-3">
                                    @foreach (['base_url' => 'عنوان المنصة HTTPS', 'account_id' => 'معرف الحساب Account ID', 'inbox_id' => 'معرف صندوق الوارد Inbox ID'] as $key => $label)
                                        <div class="col-md-4">
                                            <label class="form-label">{{ $label }}</label>
                                            <input class="form-control" name="whatsapp[chatwoot][{{ $key }}]" value="{{ data_get($whatsappData, 'chatwoot.'.$key) }}" dir="ltr">
                                        </div>
                                    @endforeach
                                    <div class="col-md-6">
                                        <label class="form-label">رمز الوصول API Access Token</label>
                                        <input type="password" autocomplete="new-password" class="form-control" name="whatsapp[chatwoot][access_token]" value="">
                                        <div class="form-text">اتركه فارغًا للاحتفاظ بالرمز المحفوظ.</div>
                                    </div>
                                </div>

                                @if(session()->has('chatwoot_inboxes'))
                                    <div class="table-responsive mt-3">
                                        <table class="table mb-0">
                                            <thead><tr><th>المعرف</th><th>الاسم</th><th>القناة</th><th>الرقم</th></tr></thead>
                                            <tbody>
                                                @foreach(session('chatwoot_inboxes', []) as $inbox)
                                                    <tr><td>{{ $inbox['id'] }}</td><td>{{ $inbox['name'] }}</td><td>{{ $inbox['channel_type'] }}</td><td dir="ltr">{{ $inbox['phone_number'] }}</td></tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div class="alert alert-light border small mt-3 mb-0">
                                    هذا الربط للإرسال. إدارة الردود تتم في Widers. الرسائل خارج نافذة خدمة واتساب قد تتطلب قالبًا معتمدًا.
                                </div>
                            </div>
                        </div>
                        @php
                            $clickChatSaved = data_get($currentSettings, 'whatsapp.clickchat', []);
                            $clickChatChannels = session('clickchat_channels')
                                ?? (data_get($whatsappData, 'driver') === 'clickchat'
                                    ? app(\App\Services\WhatsApp\ClickChatWhatsAppService::class)->channels()
                                    : []);
                            $clickChatDefault = (string) data_get($whatsappData, 'clickchat.default_channel_account_id', '');
                        @endphp
                        <div id="clickChatSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'clickchat' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <div class="ns-subcard-head">
                                    <div>
                                        <h6>ربط Click Live Chat</h6>
                                        <div class="text-muted small">احفظ رابط المنصة ومفتاح API أولًا، ثم اختبر الاتصال لجلب أرقام واتساب المربوطة (ميتا أو QR) واختيار الرقم المرسِل.</div>
                                    </div>
                                    <button type="submit" form="chatwootConnectionTest" class="btn btn-outline-primary btn-sm">
                                        <i class="fa fa-plug me-1"></i>
                                        اختبار الاتصال وجلب الأرقام
                                    </button>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">رابط المنصة</label>
                                        <input class="form-control" name="whatsapp[clickchat][base_url]" dir="ltr"
                                            placeholder="https://chat.example.com"
                                            value="{{ data_get($whatsappData, 'clickchat.base_url') }}">
                                        <div class="form-text">بدون / في النهاية.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">الرقم المرسِل الافتراضي</label>
                                        @if (!empty($clickChatChannels))
                                            <select name="whatsapp[clickchat][default_channel_account_id]" class="form-control">
                                                <option value="">أول رقم نشط (ميتا ثم QR)</option>
                                                @foreach ($clickChatChannels as $channel)
                                                    <option value="{{ $channel['id'] }}" @selected($clickChatDefault === (string) $channel['id'])>
                                                        {{ $channel['name'] }} — {{ $channel['type'] === 'whatsapp_qr' ? 'QR' : 'ميتا' }}
                                                        {{ $channel['status'] !== 'active' ? '(غير نشط)' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="number" min="1" class="form-control" name="whatsapp[clickchat][default_channel_account_id]"
                                                value="{{ $clickChatDefault }}" placeholder="channel_account_id">
                                            <div class="form-text">اختبر الاتصال لعرض الأرقام كقائمة. اتركه فارغًا لاستخدام أول رقم نشط.</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">مفتاح API</label>
                                        <input type="password" autocomplete="new-password" class="form-control" name="whatsapp[clickchat][api_token]" value="" dir="ltr">
                                        <div class="form-text">
                                            {{ !empty($clickChatSaved['api_token']) ? 'محفوظ. ' : '' }}اتركه فارغًا للاحتفاظ بالمفتاح المحفوظ.
                                            الصلاحيات المطلوبة: messages:write و conversations:read.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">المفتاح السري للـ Webhook</label>
                                        <input type="password" autocomplete="new-password" class="form-control" name="whatsapp[clickchat][webhook_secret]" value="" dir="ltr" placeholder="whsec_...">
                                        <div class="form-text">{{ !empty($clickChatSaved['webhook_secret']) ? 'محفوظ. ' : '' }}اتركه فارغًا للاحتفاظ بالمفتاح المحفوظ.</div>
                                    </div>
                                </div>

                                @if (session()->has('clickchat_channels'))
                                    <div class="table-responsive mt-3">
                                        <table class="table mb-0">
                                            <thead><tr><th>المعرف</th><th>الرقم</th><th>النوع</th><th>الحالة</th></tr></thead>
                                            <tbody>
                                                @forelse (session('clickchat_channels', []) as $channel)
                                                    <tr>
                                                        <td>{{ $channel['id'] }}</td>
                                                        <td dir="ltr">{{ $channel['name'] }}</td>
                                                        <td>{{ $channel['type'] === 'whatsapp_qr' ? 'QR' : 'ميتا الرسمي' }}</td>
                                                        <td>{{ $channel['status'] === 'active' ? 'نشط' : $channel['status'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-muted">لا توجد أرقام واتساب مربوطة في المنصة.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="form-label">عند تعذر الإرسال عبر المنصة</label>
                                        <select name="whatsapp[clickchat][fallback]" class="form-control">
                                            @foreach (['none' => 'لا شيء — تُسجَّل كفاشلة', 'ultramsg' => 'أرسل عبر رقم UltraMsg', 'sms' => 'أرسل عبر SMS'] as $value => $label)
                                                <option value="{{ $value }}" @selected(data_get($whatsappData, 'clickchat.fallback', 'none') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">يُستخدم عند رفض المفتاح، أو انتهاء نافذة الـ 24 ساعة، أو فصل الرقم، أو بعد فشل إعادة المحاولة. لا يُستخدم للقوالب ولا لردود صندوق الوارد. UltraMsg يحتاج رقمًا مفعّلًا في إعداداته.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ns-toggle">
                                            <div class="form-check">
                                                <input type="hidden" name="whatsapp[clickchat][auto_retry]" value="0">
                                                <input class="form-check-input" type="checkbox" name="whatsapp[clickchat][auto_retry]" id="clickChatAutoRetry" value="1"
                                                    {{ data_get($whatsappData, 'clickchat.auto_retry', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="clickChatAutoRetry">إعادة المحاولة تلقائيًا</label>
                                            </div>
                                            <div class="form-text">عند ضغط المنصة (أكثر من 60 رسالة في الدقيقة) أو تعطلها المؤقت: إعادة بعد 1 ثم 5 ثم 15 دقيقة. رموز التحقق لا يُعاد إرسالها.</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">من يصله تنبيه عند توقف الإرسال</label>
                                        <input type="hidden" name="whatsapp[clickchat][alert_admin_ids][]" value="">
                                        @php $alertAdmins = array_map('intval', (array) data_get($whatsappData, 'clickchat.alert_admin_ids', [])); @endphp
                                        <select name="whatsapp[clickchat][alert_admin_ids][]" class="form-control" multiple size="4">
                                            @foreach (\App\Models\auth\Admin::query()->activeForOperations()->orderBy('name')->get(['id', 'name']) as $alertAdmin)
                                                <option value="{{ $alertAdmin->id }}" @selected(in_array($alertAdmin->id, $alertAdmins, true))>{{ $alertAdmin->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">إشعار داخل النظام (مرة كل 6 ساعات لكل مشكلة) عند رفض مفتاح API، أو تعذر الوصول للمنصة، أو فصل رقم مرسِل. اضغط Ctrl لاختيار أكثر من موظف.</div>
                                    </div>
                                    @php $branchList = \App\Models\Branch::query()->orderBy('id')->get(); @endphp
                                    @if ($branchList->count() > 1)
                                        <div class="col-12">
                                            <label class="form-label">رقم الإرسال لكل فرع</label>
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead><tr><th>الفرع</th><th>الرقم المرسِل</th></tr></thead>
                                                    <tbody>
                                                        @foreach ($branchList as $branch)
                                                            @php $branchSender = (string) data_get($whatsappData, 'clickchat.branch_senders.' . $branch->id, ''); @endphp
                                                            <tr>
                                                                <td>{{ $branch->name }}</td>
                                                                <td>
                                                                    @if (!empty($clickChatChannels))
                                                                        <select name="whatsapp[clickchat][branch_senders][{{ $branch->id }}]" class="form-control form-control-sm">
                                                                            <option value="">الرقم الافتراضي</option>
                                                                            @foreach ($clickChatChannels as $channel)
                                                                                <option value="{{ $channel['id'] }}" @selected($branchSender === (string) $channel['id'])>{{ $channel['name'] }} — {{ $channel['type'] === 'whatsapp_qr' ? 'QR' : 'ميتا' }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    @else
                                                                        <input type="number" min="1" class="form-control form-control-sm" name="whatsapp[clickchat][branch_senders][{{ $branch->id }}]"
                                                                            value="{{ $branchSender }}" placeholder="الافتراضي">
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="form-text">رسائل الفرع تخرج من رقمه، إلا إذا اختار الموظف رقمًا آخر عند الإرسال.</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="alert alert-light border small mt-3 mb-0">
                                    سجّل هذا الرابط في المنصة (المطورون ← Webhooks) مع الأحداث
                                    <code>message.received</code> و <code>message.sent</code> و <code>message.status</code>:
                                    <div class="mt-1"><code dir="ltr">{{ route('api.webhooks.clickchat') }}</code></div>
                                    <div class="mt-1 text-muted">الرسائل والردود تظهر في صندوق وارد واتساب هنا. أرقام ميتا تحتاج قالبًا معتمدًا بعد مرور 24 ساعة على آخر رسالة من العميل.</div>
                                </div>
                            </div>
                        </div>
                        <div id="metaSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'meta' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <div class="ns-subcard-head">
                                    <div>
                                        <h6>
                                            <i class="fab fa-facebook text-primary me-1"></i>
                                            إعدادات Meta API — الربط المباشر
                                        </h6>
                                        <div class="text-muted small">
                                            احفظ App ID وConfiguration ID وApp Secret أولًا، ثم افتح معالج Meta لإكمال اختيار النشاط والرقم.
                                        </div>
                                    </div>
                                    <span class="badge {{ $metaConnected ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $metaConnected ? 'الحساب متصل' : 'غير متصل' }}
                                    </span>
                                </div>

                                <div class="row g-3 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">PIN تسجيل الرقم (اختياري)</label>
                                        <input type="text" id="metaRegistrationPin" class="form-control" inputmode="numeric"
                                            pattern="\d{6}" maxlength="6" placeholder="6 أرقام">
                                    </div>
                                    <div class="col-md-5">
                                        <button type="button" id="launchMetaEmbeddedSignup" class="btn btn-success w-100"
                                            {{ $metaEmbeddedSignupAvailable ? '' : 'disabled' }}>
                                            <i class="fab fa-facebook me-1"></i>
                                            {{ $metaConnected ? 'إعادة الربط أو تغيير الرقم' : 'فتح Meta ومتابعة الربط' }}
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <div id="metaEmbeddedStatus" class="alert d-none mb-0"></div>
                                        @unless($metaEmbeddedSignupReady)
                                            <div class="alert alert-warning small mb-0">
                                                أدخل بيانات تطبيق Meta أدناه واضغط «حفظ إعدادات واتساب»، ثم سيعمل زر الربط المباشر.
                                            </div>
                                        @endunless
                                        @if($metaEmbeddedSignupReady && !request()->secure())
                                            <div class="alert alert-warning small mb-0">
                                                Meta تمنع Embedded Signup على HTTP. افتح هذه الصفحة من رابط عام HTTPS أو نفق تطوير HTTPS لتفعيل الزر.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="cfg-box">
                                    <div class="cfg-box-title"><i class="fa fa-circle-info"></i> بيانات Meta المحفوظة</div>
                                    <div class="cfg-grid">
                                        <div class="cfg-item"><span>Graph Version</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.meta.graph_version', 'v23.0') }}</b></div>
                                        <div class="cfg-item"><span>WABA ID</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.meta.business_account_id') ?: 'غير محدد' }}</b></div>
                                        <div class="cfg-item"><span>Phone Number ID</span><b dir="ltr">{{ data_get($currentSettings, 'whatsapp.meta.phone_number_id') ?: 'غير محدد' }}</b></div>
                                        <div class="cfg-item"><span>Access Token</span><b dir="ltr">{{ $maskSecret(data_get($currentSettings, 'whatsapp.meta.access_token')) }}</b></div>
                                        <div class="cfg-item"><span>Webhook Verify Token</span><b dir="ltr">{{ $maskSecret(data_get($currentSettings, 'whatsapp.meta.webhook_verify_token')) }}</b></div>
                                        <div class="cfg-item"><span>App Secret</span><b dir="ltr">{{ $maskSecret(data_get($currentSettings, 'whatsapp.meta.app_secret')) }}</b></div>
                                    </div>
                                </div>
                                <div class="alert alert-info small">
                                    <div><strong>Callback URL داخل Meta:</strong> <code>{{ route('api.webhooks.whatsapp.receive') }}</code></div>
                                    <div class="mt-1"><strong>Verify Token:</strong> استخدم نفس القيمة التي تحفظها أدناه.</div>
                                    <div class="mt-1">يجب أن يكون رابط النظام عامًا ويعمل عبر HTTPS؛ العنوان المحلي 127.0.0.1 لا يمكن لـ Meta الوصول إليه.</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Graph Version</label>
                                        <input type="text" name="whatsapp[meta][graph_version]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.graph_version', 'v23.0') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Meta App ID</label>
                                        <input type="text" name="whatsapp[meta][app_id]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.app_id') }}" dir="ltr">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Embedded Signup Configuration ID</label>
                                        <input type="text" name="whatsapp[meta][configuration_id]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.configuration_id') }}" dir="ltr">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">WhatsApp Business Account ID</label>
                                        <input type="text" name="whatsapp[meta][business_account_id]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.business_account_id') }}" dir="ltr"
                                            placeholder="يُملأ تلقائيًا بعد الربط">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Phone Number ID</label>
                                        <input type="text" name="whatsapp[meta][phone_number_id]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.phone_number_id') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Access Token</label>
                                        <input type="password" name="whatsapp[meta][access_token]" class="form-control"
                                            placeholder="{{ data_get($currentSettings, 'whatsapp.meta.access_token') ? 'هناك Access Token محفوظ، اترك الحقل فارغًا للإبقاء عليه' : 'اتركه فارغًا إذا لا توجد قيمة' }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Webhook Verify Token</label>
                                        <div class="input-group">
                                            <input type="text" id="metaWebhookVerifyToken" name="whatsapp[meta][webhook_verify_token]" class="form-control"
                                                value="{{ $webhookVerifyToken }}" autocomplete="off" dir="ltr">
                                            <button class="btn btn-outline-secondary" type="button" id="copyMetaWebhookToken" title="نسخ">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">انسخ هذه القيمة إلى Verify Token في إعدادات Meta.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">App Secret</label>
                                        <input type="password" name="whatsapp[meta][app_secret]" class="form-control"
                                            placeholder="{{ data_get($currentSettings, 'whatsapp.meta.app_secret') ? 'هناك App Secret محفوظ، اترك الحقل فارغًا للإبقاء عليه' : 'أدخل App Secret من Meta' }}">
                                        <div class="form-text">يُستخدم للتحقق من توقيع X-Hub-Signature-256 للرسائل الواردة.</div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" form="testMetaConnectionForm" class="btn btn-outline-success btn-sm">
                                        <i class="fa fa-check-circle me-1"></i>
                                        اختبار الإعدادات المحفوظة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="ultraMsgSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'ultramsg' ? '' : 'd-none' }}">
                            <div class="ns-subcard">
                                <div class="ns-subcard-head">
                                    <div>
                                        <h6>ربط UltraMsg</h6>
                                        <div class="text-muted small">أضف أكثر من رقم واتساب عبر <code>instance_id</code> و<code>token</code>، وحدد الرقم الافتراضي المستخدم في الإرسال.</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addUltraMsgInstance">
                                        <i class="fa fa-plus me-1"></i>
                                        إضافة رقم جديد
                                    </button>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">رابط UltraMsg API</label>
                                        <input type="url" name="whatsapp[ultramsg][api_url]" class="form-control"
                                            value="{{ data_get($whatsappData, 'ultramsg.api_url', 'https://api.ultramsg.com') }}">
                                    </div>
                                </div>

                                @if (!empty(data_get($currentSettings, 'whatsapp.ultramsg.instances', [])))
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered align-middle bg-white">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>الاسم</th>
                                                    <th>رقم الواتساب</th>
                                                    <th>Instance ID</th>
                                                    <th>Token</th>
                                                    <th>الحالة</th>
                                                    <th>الافتراضي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (data_get($currentSettings, 'whatsapp.ultramsg.instances', []) as $savedInstance)
                                                    <tr>
                                                        <td>{{ $savedInstance['name'] ?? 'غير محدد' }}</td>
                                                        <td>{{ $savedInstance['phone_number'] ?? 'غير مدخل' }}</td>
                                                        <td>{{ $savedInstance['instance_id'] ?? 'غير محدد' }}</td>
                                                        <td>{{ $maskSecret($savedInstance['token'] ?? '') }}</td>
                                                        <td>{{ !empty($savedInstance['enabled']) ? 'مفعل' : 'غير مفعل' }}</td>
                                                        <td>{{ $defaultUltraMsgKey === ($savedInstance['key'] ?? '') ? 'نعم' : 'لا' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <div id="ultraMsgInstancesContainer">
                                    @foreach ($ultraMsgInstances as $index => $instance)
                                        @php
                                            $instanceKey = $instance['key'] ?? uniqid('ultramsg_', false);
                                        @endphp
                                        <div class="card border mb-3 ultramsg-instance" data-instance-key="{{ $instanceKey }}">
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <input type="hidden" name="whatsapp[ultramsg][instances][{{ $index }}][key]" value="{{ $instanceKey }}" class="ultramsg-instance-key">
                                                    <div class="col-sm-6 col-lg-3">
                                                        <label class="form-label">اسم الرقم</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][name]" class="form-control"
                                                            value="{{ $instance['name'] ?? '' }}" placeholder="مثال: خدمة العملاء">
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <label class="form-label">رقم الواتساب</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][phone_number]" class="form-control"
                                                            value="{{ $instance['phone_number'] ?? '' }}" placeholder="مثال: 9665xxxxxxxx" dir="ltr">
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <label class="form-label">Instance ID</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][instance_id]" class="form-control"
                                                            value="{{ $instance['instance_id'] ?? '' }}" placeholder="instance167619" dir="ltr">
                                                    </div>
                                                    <div class="col-sm-6 col-lg-3">
                                                        <label class="form-label">Token</label>
                                                        <input type="password" name="whatsapp[ultramsg][instances][{{ $index }}][token]" class="form-control"
                                                            placeholder="{{ !empty($instance['token']) ? 'هناك Token محفوظ' : 'أدخل Token' }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="ns-instance-footer">
                                                            <div class="form-check">
                                                                <input type="hidden" name="whatsapp[ultramsg][instances][{{ $index }}][enabled]" value="0">
                                                                <input class="form-check-input" type="checkbox" id="ultramsgEnabled{{ $instanceKey }}" name="whatsapp[ultramsg][instances][{{ $index }}][enabled]" value="1"
                                                                    {{ !empty($instance['enabled']) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="ultramsgEnabled{{ $instanceKey }}">مفعل</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input ultramsg-default-radio" type="radio" id="ultramsgDefault{{ $instanceKey }}" name="whatsapp[ultramsg][default_instance_key]" value="{{ $instanceKey }}"
                                                                    {{ $defaultUltraMsgKey === $instanceKey ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="ultramsgDefault{{ $instanceKey }}">الرقم الافتراضي</label>
                                                            </div>
                                                            <button type="button" class="btn btn-outline-danger btn-sm js-remove-ultramsg-instance">
                                                                <i class="fa fa-trash me-1"></i>
                                                                حذف الرقم
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 ns-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات واتساب
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <form id="testMetaConnectionForm" method="post" action="{{ route('notifications.settings.whatsapp.test') }}" class="d-none">
            @csrf
        </form>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<div id="fb-root"></div>
<script>
    function toggleSmsProviderSettings() {
        const provider = $('#smsProvider').val();
        $('#msegatSettingsBox').toggleClass('d-none', provider !== 'msegat');
        $('#taqnyatSettingsBox').toggleClass('d-none', provider !== 'taqnyat');
    }

    function toggleWhatsAppDriverSettings() {
        const driver = $('#whatsappDriver').val();
        $('#chatwootSettingsBox').toggleClass('d-none', driver !== 'chatwoot');
        $('#clickChatSettingsBox').toggleClass('d-none', driver !== 'clickchat');
        $('#metaSettingsBox').toggleClass('d-none', driver !== 'meta');
        $('#ultraMsgSettingsBox').toggleClass('d-none', driver !== 'ultramsg');
    }

    function nextUltraMsgIndex() {
        let maxIndex = -1;

        $('#ultraMsgInstancesContainer .ultramsg-instance-key').each(function () {
            const name = $(this).attr('name') || '';
            const match = name.match(/\[instances\]\[(\d+)\]/);
            if (match) {
                maxIndex = Math.max(maxIndex, Number(match[1]));
            }
        });

        return maxIndex + 1;
    }

    function buildUltraMsgInstanceRow(index, key) {
        return `
            <div class="card border mb-3 ultramsg-instance" data-instance-key="${key}">
                <div class="card-body">
                    <div class="row g-3">
                        <input type="hidden" name="whatsapp[ultramsg][instances][${index}][key]" value="${key}" class="ultramsg-instance-key">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">اسم الرقم</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][name]" class="form-control" placeholder="مثال: خدمة العملاء">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">رقم الواتساب</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][phone_number]" class="form-control" placeholder="مثال: 9665xxxxxxxx" dir="ltr">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Instance ID</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][instance_id]" class="form-control" placeholder="instance167619" dir="ltr">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label">Token</label>
                            <input type="password" name="whatsapp[ultramsg][instances][${index}][token]" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="ns-instance-footer">
                                <div class="form-check">
                                    <input type="hidden" name="whatsapp[ultramsg][instances][${index}][enabled]" value="0">
                                    <input class="form-check-input" type="checkbox" id="ultramsgEnabled${key}" name="whatsapp[ultramsg][instances][${index}][enabled]" value="1" checked>
                                    <label class="form-check-label" for="ultramsgEnabled${key}">مفعل</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input ultramsg-default-radio" type="radio" id="ultramsgDefault${key}" name="whatsapp[ultramsg][default_instance_key]" value="${key}">
                                    <label class="form-check-label" for="ultramsgDefault${key}">الرقم الافتراضي</label>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm js-remove-ultramsg-instance">
                                    <i class="fa fa-trash me-1"></i>
                                    حذف الرقم
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    $(document).ready(function () {
        toggleSmsProviderSettings();
        toggleWhatsAppDriverSettings();

        $('#smsProvider').on('change', toggleSmsProviderSettings);
        $('#whatsappDriver').on('change', toggleWhatsAppDriverSettings);

        $('#addUltraMsgInstance').on('click', function () {
            const index = nextUltraMsgIndex();
            const key = `ultramsg_${Date.now()}_${index}`;
            $('#ultraMsgInstancesContainer').append(buildUltraMsgInstanceRow(index, key));
        });

        $(document).on('click', '.js-remove-ultramsg-instance', function () {
            $(this).closest('.ultramsg-instance').remove();
        });

        $('#copyMetaWebhookToken').on('click', function () {
            const input = document.getElementById('metaWebhookVerifyToken');
            if (!input) return;
            navigator.clipboard.writeText(input.value).then(function () {
                $('#copyMetaWebhookToken').attr('title', 'تم النسخ');
            });
        });

        @if($metaEmbeddedSignupAvailable)
        let embeddedSignupCode = null;
        let embeddedSignupSession = null;
        let embeddedSignupSubmitting = false;

        window.fbAsyncInit = function () {
            FB.init({
                appId: @json($metaAppId),
                cookie: true,
                xfbml: false,
                version: @json(data_get($currentSettings, 'whatsapp.meta.graph_version', 'v23.0'))
            });
        };

        function showMetaEmbeddedStatus(type, message) {
            $('#metaEmbeddedStatus')
                .removeClass('d-none alert-success alert-danger alert-info')
                .addClass('alert-' + type)
                .text(message);
        }

        function completeMetaEmbeddedSignupIfReady() {
            if (!embeddedSignupCode || !embeddedSignupSession || embeddedSignupSubmitting) return;
            if (!embeddedSignupSession.waba_id) {
                showMetaEmbeddedStatus('danger', 'لم ترسل Meta رقم WABA ID. أعد المحاولة واختر حساب واتساب كاملًا.');
                return;
            }

            embeddedSignupSubmitting = true;
            showMetaEmbeddedStatus('info', 'جاري حفظ الحساب والاشتراك في Webhook...');

            $.ajax({
                url: @json(route('notifications.settings.whatsapp.embedded-signup')),
                method: 'POST',
                data: {
                    _token: @json(csrf_token()),
                    code: embeddedSignupCode,
                    waba_id: embeddedSignupSession.waba_id,
                    phone_number_id: embeddedSignupSession.phone_number_id || '',
                    pin: ($('#metaRegistrationPin').val() || '').trim()
                }
            }).done(function (response) {
                const warnings = response.result && response.result.warnings ? response.result.warnings : [];
                showMetaEmbeddedStatus('success', response.message + (warnings.length ? ' ' + warnings.join(' ') : ''));
                window.setTimeout(function () { window.location.reload(); }, 1400);
            }).fail(function (xhr) {
                embeddedSignupSubmitting = false;
                showMetaEmbeddedStatus('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'تعذر إكمال الربط مع Meta.');
            });
        }

        window.addEventListener('message', function (event) {
            if (!['https://www.facebook.com', 'https://web.facebook.com'].includes(event.origin)) return;

            let data = event.data;
            try {
                data = typeof data === 'string' ? JSON.parse(data) : data;
            } catch (error) {
                return;
            }

            if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') return;
            if (data.event === 'FINISH' || data.event === 'FINISH_ONLY_WABA') {
                embeddedSignupSession = data.data || {};
                showMetaEmbeddedStatus('info', 'اكتملت خطوات Meta، جاري إنهاء الربط داخل كليك...');
                completeMetaEmbeddedSignupIfReady();
            } else if (data.event === 'CANCEL') {
                showMetaEmbeddedStatus('danger', 'تم إلغاء عملية الربط من Meta.');
            } else if (data.event === 'ERROR') {
                showMetaEmbeddedStatus('danger', 'أبلغت Meta عن خطأ أثناء الربط.');
            }
        });

        $('#launchMetaEmbeddedSignup').on('click', function () {
            if (typeof FB === 'undefined') {
                showMetaEmbeddedStatus('danger', 'لم يكتمل تحميل Facebook SDK. تحقق من الاتصال ثم أعد المحاولة.');
                return;
            }

            embeddedSignupCode = null;
            embeddedSignupSession = null;
            embeddedSignupSubmitting = false;
            showMetaEmbeddedStatus('info', 'سيتم فتح نافذة Meta الآن...');

            FB.login(function (response) {
                const code = response && response.authResponse ? response.authResponse.code : null;
                if (!code) {
                    showMetaEmbeddedStatus('danger', 'لم يتم منح كود الربط من Meta أو تم إغلاق النافذة.');
                    return;
                }
                embeddedSignupCode = code;
                completeMetaEmbeddedSignupIfReady();
            }, {
                config_id: @json($metaConfigurationId),
                response_type: 'code',
                override_default_response_type: true,
                extras: {
                    setup: {},
                    featureType: '',
                    sessionInfoVersion: '3'
                }
            });
        });

        const facebookSdk = document.createElement('script');
        facebookSdk.async = true;
        facebookSdk.defer = true;
        facebookSdk.crossOrigin = 'anonymous';
        facebookSdk.src = 'https://connect.facebook.net/en_US/sdk.js';
        document.head.appendChild(facebookSdk);
        @endif
    });
</script>
@endsection
