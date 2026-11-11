{{-- جاهزية Click Live Chat + قناة رمز التحقق. أول ما يراه المدير في «إعدادات الربط». --}}
@php
    $readinessChecks = \App\Support\ClickChatReadiness::checks();
    $readyCount = collect($readinessChecks)->where('ok', true)->count();
    $verificationChannel = \App\Support\CommunicationSettings::verificationChannel();
    $verificationSettings = \App\Support\CommunicationSettings::verification();
    $verificationTemplate = \App\Models\SmsTemplate::getByName(\App\Support\CommunicationSettings::VERIFICATION_TEMPLATE);
    $whatsappReady = \App\Support\ClickChatReadiness::whatsappIsConfigured();
    $smsReady = \App\Support\CommunicationSettings::smsIsConfigured();
    $verificationDisabled = \App\Support\CommunicationSettings::phoneVerificationIsDisabled();
@endphp

<div class="card ns-card" id="clickchat-readiness">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5>جاهزية واتساب عبر Click Live Chat بدلاً من الرسائل النصية</h5>
            <div class="text-muted small">
                Click Live Chat يرسل من قوالب واتساب المعتمدة من Meta، فتصل الرسالة للعميل في أي وقت.
                الرسالة بدون قالب معتمد لا تصل إلا لعميل راسلكم خلال آخر 24 ساعة.
            </div>
        </div>
        <span class="badge {{ $readyCount === count($readinessChecks) ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ $readyCount }} / {{ count($readinessChecks) }} جاهز
        </span>
    </div>
    <div class="card-body">
        <ol class="list-unstyled mb-0">
            @foreach ($readinessChecks as $check)
                <li class="d-flex gap-2 align-items-start mb-2" data-check="{{ $check['key'] }}">
                    <i class="fa {{ $check['ok'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mt-1"></i>
                    <div>
                        <div class="fw-semibold">{{ $check['label'] }}</div>
                        @unless ($check['ok'])
                            <div class="text-muted small">{{ $check['hint'] }}</div>
                        @endunless
                    </div>
                </li>
            @endforeach
        </ol>
        <div class="small text-muted mt-2">
            ترتيب العمل: ١) اختر Click Live Chat واحفظ بيانات الربط في قسم واتساب ← ٢) اختبر الاتصال واختر الرقم المرسِل
            ← ٣) أنشئ القوالب في Meta من «محادثات واتساب» وانتظر اعتمادها ← ٤) اربط كل قالب من «قوالب الرسائل»
            ← ٥) حوّل رمز التحقق إلى واتساب من البطاقة التالية ← ٦) أبقِ SMS قناة احتياطية.
        </div>
    </div>
</div>

<div class="card ns-card" id="verification-settings">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5>رمز التحقق (OTP)</h5>
            <div class="text-muted small">رمز تسجيل الدخول والتسجيل واستعادة كلمة المرور للعملاء في الموقع والتطبيق، وتطبيق فريق التأجير بالساعة.</div>
        </div>
        <span class="badge bg-light text-dark">{{ $verificationChannel === 'whatsapp' ? 'واتساب' : 'SMS' }}</span>
    </div>
    <div class="card-body">
        @if ($verificationDisabled)
            <div class="alert alert-warning small">
                التحقق بالجوال ملغى حالياً من قسم SMS (الرمز الثابت 12345 مقبول لكل العملاء ولا يُرسل شيء). ألغِ هذا الخيار قبل التشغيل الفعلي.
            </div>
        @endif
        @unless ($verificationTemplate)
            <div class="alert alert-danger small">قالب «phone_verification_code» غير موجود أو غير نشط في «قوالب الرسائل»؛ يُرسل نص افتراضي عبر SMS.</div>
        @endunless

        <form method="post" action="{{ route('notifications.settings.update') }}" class="row g-3">
            @csrf
            <input type="hidden" name="section" value="verification">
            <div class="col-md-6">
                <label class="form-label" for="verificationChannel">إرسال رمز التحقق عبر</label>
                <select class="form-control" name="verification[channel]" id="verificationChannel">
                    <option value="sms" @selected($verificationChannel === 'sms')>رسالة نصية SMS {{ $smsReady ? '' : '— البوابة غير جاهزة' }}</option>
                    <option value="whatsapp" @selected($verificationChannel === 'whatsapp')>واتساب ({{ app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->providerLabel() }}) {{ $whatsappReady ? '' : '— غير مربوط' }}</option>
                </select>
                <div class="form-text">يغيّر قناة قالب «رمز التحقق» في مكتبة القوالب، فالإعدادان متطابقان دائماً.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">عند فشل واتساب</label>
                <input type="hidden" name="verification[fallback_to_sms]" value="0">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="verificationFallback" name="verification[fallback_to_sms]" value="1"
                        @checked($verificationSettings['fallback_to_sms'])>
                    <label class="form-check-label" for="verificationFallback">أرسل الرمز برسالة SMS بدلاً منه {{ $smsReady ? '' : '(بوابة SMS غير جاهزة الآن)' }}</label>
                </div>
                <div class="form-text">مفيد للعملاء الذين لا يملكون واتساب على الرقم نفسه. رمز التحقق لا يُعاد إرساله تلقائياً لأنه ينتهي خلال دقائق.</div>
            </div>
            @if ($verificationChannel === 'whatsapp' && $verificationTemplate && empty(data_get($verificationTemplate, 'clickchat_binding.name')))
                <div class="col-12">
                    <div class="alert alert-warning small mb-0">
                        قالب رمز التحقق غير مربوط بقالب معتمد في Click Live Chat، فلن يصل إلا لمن راسلكم خلال 24 ساعة.
                        <a href="{{ route('admin.sms-templates.edit', $verificationTemplate->id) }}">اربطه الآن</a> بقالب من فئة AUTHENTICATION متغيره الأول هو verification_code.
                    </div>
                </div>
            @endif
            <div class="col-12">
                <button type="submit" class="btn btn-primary">حفظ إعدادات رمز التحقق</button>
            </div>
        </form>
    </div>
</div>
