@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات الربط والإرسال
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
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

    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1">
                    <i class="fa fa-plug me-2"></i>
                    إعدادات الربط والإرسال
                </h4>
                <div class="text-muted small">
                    تم فصل الحفظ إلى أقسام مستقلة: البريد الإلكتروني، الرسائل النصية، وواتساب.
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">
                    <i class="fa fa-sms me-1"></i>
                    إرسال SMS
                </a>
                <a href="{{ route('whatsapp.notifications.index') }}" class="btn btn-outline-success">
                    <i class="fa fa-whatsapp me-1"></i>
                    إرسال واتساب
                </a>
                <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-inbox me-1"></i>
                    سجل الرسائل
                </a>
            </div>
        </div>

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

        <div class="card mb-4" id="email-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">إعدادات البريد الإلكتروني</h5>
                    <div class="text-muted small">هذا القسم خاص برسائل البريد مثل إعادة تعيين كلمة المرور والتنبيهات البريدية.</div>
                </div>
                <span class="badge bg-light text-dark">Mail</span>
            </div>
            <div class="card-body">
                <div class="bg-light rounded-3 p-3 mb-3">
                    <div class="fw-semibold mb-2">الإعدادات الحالية</div>
                    <div class="row g-3 small">
                        <div class="col-md-2"><strong>النوع:</strong> {{ data_get($currentSettings, 'email.mailer', 'smtp') }}</div>
                        <div class="col-md-3"><strong>Host:</strong> {{ data_get($currentSettings, 'email.host') ?: 'غير محدد' }}</div>
                        <div class="col-md-2"><strong>Port:</strong> {{ data_get($currentSettings, 'email.port', 587) }}</div>
                        <div class="col-md-2"><strong>Encryption:</strong> {{ data_get($currentSettings, 'email.encryption') ?: 'بدون' }}</div>
                        <div class="col-md-3"><strong>بريد المرسل:</strong> {{ data_get($currentSettings, 'email.from_address') ?: 'غير محدد' }}</div>
                        <div class="col-md-3"><strong>اسم المرسل:</strong> {{ data_get($currentSettings, 'email.from_name') ?: 'غير محدد' }}</div>
                        <div class="col-md-3"><strong>اسم المستخدم:</strong> {{ data_get($currentSettings, 'email.username') ?: 'غير محدد' }}</div>
                        <div class="col-md-3"><strong>كلمة المرور:</strong> {{ $maskSecret(data_get($currentSettings, 'email.password')) }}</div>
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
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات البريد
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4" id="sms-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">إعدادات الرسائل النصية SMS</h5>
                    <div class="text-muted small">هذا القسم خاص بمزود الرسائل النصية وأسماء المرسل وحدود الإرسال الجماعي.</div>
                </div>
                <span class="badge bg-light text-dark">SMS API</span>
            </div>
            <div class="card-body">
                <div class="bg-light rounded-3 p-3 mb-3">
                    <div class="fw-semibold mb-2">الإعدادات الحالية</div>
                    <div class="row g-3 small">
                        <div class="col-md-2"><strong>البوابة:</strong> {{ $currentSmsProvider === 'taqnyat' ? 'تقنيات' : 'Msegat' }}</div>
                        @if ($currentSmsProvider === 'taqnyat')
                            <div class="col-md-4"><strong>Base URL:</strong> {{ $currentTaqnyatBaseUrl }}</div>
                            <div class="col-md-3"><strong>مسار الإرسال:</strong> POST {{ $currentTaqnyatBaseUrl }}/v1/messages</div>
                            <div class="col-md-3"><strong>Authorization:</strong> Bearer {{ $maskSecret(data_get($currentSettings, 'sms.taqnyat.bearer_token')) }}</div>
                        @else
                            <div class="col-md-4"><strong>رابط API:</strong> {{ data_get($currentSettings, 'sms.url') ?: 'غير محدد' }}</div>
                            <div class="col-md-2"><strong>اسم المستخدم:</strong> {{ data_get($currentSettings, 'sms.username') ?: 'غير محدد' }}</div>
                            <div class="col-md-2"><strong>API Key:</strong> {{ $maskSecret(data_get($currentSettings, 'sms.api_key')) }}</div>
                        @endif
                        <div class="col-md-2"><strong>المرسل الافتراضي:</strong> {{ data_get($currentSettings, 'sms.sender') ?: 'غير محدد' }}</div>
                        <div class="col-md-2"><strong>النوع الافتراضي:</strong> {{ data_get($currentSettings, 'sms.default_sender_type') === 'verification' ? 'تحقق' : 'إعلاني' }}</div>
                        <div class="col-md-3"><strong>مرسل الإعلانات:</strong> {{ data_get($currentSettings, 'sms.advertising_sender') ?: 'غير محدد' }}</div>
                        <div class="col-md-3"><strong>مرسل التحقق:</strong> {{ data_get($currentSettings, 'sms.verification_sender') ?: 'غير محدد' }}</div>
                        <div class="col-md-3"><strong>App Domain:</strong> {{ data_get($currentSettings, 'sms.app_domain_name', 'aqro.sa') ?: 'aqro.sa' }}</div>
                        <div class="col-md-2"><strong>الترميز:</strong> {{ data_get($currentSettings, 'sms.encoding', 'UTF8') }}</div>
                        <div class="col-md-2"><strong>حد الإرسال:</strong> {{ data_get($currentSettings, 'sms.bulk_send_limit', 200) }}</div>
                        <div class="col-md-2"><strong>كلمة التأكيد:</strong> {{ data_get($currentSettings, 'sms.bulk_confirmation_keyword', 'SEND_ALL_SMS') }}</div>
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

                    <div class="col-12">
                        <div id="msegatSettingsBox" class="{{ $smsProvider === 'msegat' ? '' : 'd-none' }}">
                            <div class="bg-light rounded-3 p-3">
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
                            <div class="bg-light rounded-3 p-3">
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
                        <label class="form-label">app_domain_name</label>
                        <input type="text" name="sms[app_domain_name]" class="form-control"
                            value="{{ data_get($smsData, 'app_domain_name', 'aqro.sa') }}"
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
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card" id="whatsapp-settings">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">إعدادات WhatsApp</h5>
                    <div class="text-muted small">هذا القسم خاص باختيار المزود بين Meta API وUltraMsg مع دعم أكثر من رقم واتساب.</div>
                </div>
                <span class="badge bg-light text-dark">WhatsApp</span>
            </div>
            <div class="card-body">
                <div class="bg-light rounded-3 p-3 mb-3">
                    <div class="fw-semibold mb-2">الإعدادات الحالية</div>
                    <div class="row g-3 small">
                        <div class="col-md-3"><strong>المزود الحالي:</strong> {{ data_get($currentSettings, 'whatsapp.driver') === 'ultramsg' ? 'UltraMsg' : 'Meta API' }}</div>
                        <div class="col-md-2"><strong>رمز الدولة:</strong> {{ data_get($currentSettings, 'whatsapp.default_country_code', '966') }}</div>
                        <div class="col-md-2"><strong>Timeout:</strong> {{ data_get($currentSettings, 'whatsapp.timeout', 15) }}</div>
                        <div class="col-md-2"><strong>حد الإرسال:</strong> {{ data_get($currentSettings, 'whatsapp.bulk_send_limit', 200) }}</div>
                        <div class="col-md-3"><strong>الغرض الافتراضي:</strong>
                            @php $purpose = data_get($currentSettings, 'whatsapp.default_purpose', 'verification'); @endphp
                            {{ $purpose === 'follow_up' ? 'متابعة' : ($purpose === 'advertising' ? 'دعائي' : 'تحقق') }}
                        </div>
                        <div class="col-md-4"><strong>كلمة التأكيد:</strong> {{ data_get($currentSettings, 'whatsapp.bulk_confirmation_keyword', 'SEND_ALL_WHATSAPP') }}</div>
                        <div class="col-md-4"><strong>الإرسال الدعائي:</strong> {{ data_get($currentSettings, 'whatsapp.allow_advertising') ? 'مفعّل' : 'غير مفعّل' }}</div>
                    </div>
                </div>

                <form method="post" action="{{ route('notifications.settings.update') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="section" value="whatsapp">

                    <div class="col-md-3">
                        <label class="form-label">مزود واتساب</label>
                        <select name="whatsapp[driver]" id="whatsappDriver" class="form-control">
                            <option value="meta" {{ data_get($whatsappData, 'driver') === 'meta' ? 'selected' : '' }}>Meta API</option>
                            <option value="ultramsg" {{ data_get($whatsappData, 'driver') === 'ultramsg' ? 'selected' : '' }}>UltraMsg</option>
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
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="whatsapp[allow_advertising]" value="0">
                            <input class="form-check-input" type="checkbox" name="whatsapp[allow_advertising]" id="whatsappAllowAdvertising" value="1"
                                {{ data_get($whatsappData, 'allow_advertising') ? 'checked' : '' }}>
                            <label class="form-check-label" for="whatsappAllowAdvertising">
                                السماح بالإرسال الدعائي عبر واتساب
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="metaSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'meta' ? '' : 'd-none' }}">
                            <div class="bg-light rounded-3 p-3">
                                <h6 class="mb-3">إعدادات Meta API</h6>
                                <div class="row g-3 small mb-3">
                                    <div class="col-md-3"><strong>Graph Version:</strong> {{ data_get($currentSettings, 'whatsapp.meta.graph_version', 'v23.0') }}</div>
                                    <div class="col-md-3"><strong>Phone Number ID:</strong> {{ data_get($currentSettings, 'whatsapp.meta.phone_number_id') ?: 'غير محدد' }}</div>
                                    <div class="col-md-3"><strong>Access Token:</strong> {{ $maskSecret(data_get($currentSettings, 'whatsapp.meta.access_token')) }}</div>
                                    <div class="col-md-3"><strong>Webhook Verify Token:</strong> {{ $maskSecret(data_get($currentSettings, 'whatsapp.meta.webhook_verify_token')) }}</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Graph Version</label>
                                        <input type="text" name="whatsapp[meta][graph_version]" class="form-control"
                                            value="{{ data_get($whatsappData, 'meta.graph_version', 'v23.0') }}">
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
                                        <input type="password" name="whatsapp[meta][webhook_verify_token]" class="form-control"
                                            placeholder="{{ data_get($currentSettings, 'whatsapp.meta.webhook_verify_token') ? 'هناك Webhook Token محفوظ، اترك الحقل فارغًا للإبقاء عليه' : 'اتركه فارغًا إذا لا توجد قيمة' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div id="ultraMsgSettingsBox" class="{{ data_get($whatsappData, 'driver') === 'ultramsg' ? '' : 'd-none' }}">
                            <div class="bg-light rounded-3 p-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div>
                                        <h6 class="mb-1">ربط UltraMsg</h6>
                                        <div class="text-muted small">أضف أكثر من رقم واتساب عبر `instance_id` و`token`، وحدد الرقم الافتراضي المستخدم في الإرسال.</div>
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
                                                <div class="row g-3 align-items-end">
                                                    <input type="hidden" name="whatsapp[ultramsg][instances][{{ $index }}][key]" value="{{ $instanceKey }}" class="ultramsg-instance-key">
                                                    <div class="col-md-3">
                                                        <label class="form-label">اسم الرقم</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][name]" class="form-control"
                                                            value="{{ $instance['name'] ?? '' }}" placeholder="مثال: خدمة العملاء">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">رقم الواتساب</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][phone_number]" class="form-control"
                                                            value="{{ $instance['phone_number'] ?? '' }}" placeholder="مثال: 9665xxxxxxxx">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Instance ID</label>
                                                        <input type="text" name="whatsapp[ultramsg][instances][{{ $index }}][instance_id]" class="form-control"
                                                            value="{{ $instance['instance_id'] ?? '' }}" placeholder="instance167619">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label">Token</label>
                                                        <input type="password" name="whatsapp[ultramsg][instances][{{ $index }}][token]" class="form-control"
                                                            placeholder="{{ !empty($instance['token']) ? 'هناك Token محفوظ' : 'أدخل Token' }}">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <div class="form-check">
                                                            <input type="hidden" name="whatsapp[ultramsg][instances][{{ $index }}][enabled]" value="0">
                                                            <input class="form-check-input" type="checkbox" name="whatsapp[ultramsg][instances][{{ $index }}][enabled]" value="1"
                                                                {{ !empty($instance['enabled']) ? 'checked' : '' }}>
                                                            <label class="form-check-label">مفعل</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <div class="form-check">
                                                            <input class="form-check-input ultramsg-default-radio" type="radio" name="whatsapp[ultramsg][default_instance_key]" value="{{ $instanceKey }}"
                                                                {{ $defaultUltraMsgKey === $instanceKey ? 'checked' : '' }}>
                                                            <label class="form-check-label">افتراضي</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 text-end">
                                                        <button type="button" class="btn btn-outline-danger btn-sm js-remove-ultramsg-instance">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i>
                            حفظ إعدادات واتساب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
    function toggleSmsProviderSettings() {
        const provider = $('#smsProvider').val();
        $('#msegatSettingsBox').toggleClass('d-none', provider !== 'msegat');
        $('#taqnyatSettingsBox').toggleClass('d-none', provider !== 'taqnyat');
    }

    function toggleWhatsAppDriverSettings() {
        const driver = $('#whatsappDriver').val();
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
                    <div class="row g-3 align-items-end">
                        <input type="hidden" name="whatsapp[ultramsg][instances][${index}][key]" value="${key}" class="ultramsg-instance-key">
                        <div class="col-md-3">
                            <label class="form-label">اسم الرقم</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][name]" class="form-control" placeholder="مثال: خدمة العملاء">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">رقم الواتساب</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][phone_number]" class="form-control" placeholder="مثال: 9665xxxxxxxx">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Instance ID</label>
                            <input type="text" name="whatsapp[ultramsg][instances][${index}][instance_id]" class="form-control" placeholder="instance167619">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Token</label>
                            <input type="password" name="whatsapp[ultramsg][instances][${index}][token]" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <div class="form-check">
                                <input type="hidden" name="whatsapp[ultramsg][instances][${index}][enabled]" value="0">
                                <input class="form-check-input" type="checkbox" name="whatsapp[ultramsg][instances][${index}][enabled]" value="1" checked>
                                <label class="form-check-label">مفعل</label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-check">
                                <input class="form-check-input ultramsg-default-radio" type="radio" name="whatsapp[ultramsg][default_instance_key]" value="${key}">
                                <label class="form-check-label">افتراضي</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm js-remove-ultramsg-instance">
                                <i class="fa fa-trash"></i>
                            </button>
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
    });
</script>
@endsection
