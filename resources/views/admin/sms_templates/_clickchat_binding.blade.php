<fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto fs-6">قالب ميتا عبر Click Live Chat (اختياري)</legend>
    <p class="text-muted">يُستخدم فقط عند اختيار مزود Click Live Chat ورقم ميتا رسمي، بما يشمل الإرسال خارج نافذة 24 ساعة. المزودون الآخرون وأرقام QR يستخدمون النص الحالي.</p>
    @if (isset($smsTemplate))
        @include('admin.sms_templates._clickchat_status', ['statusTemplate' => $smsTemplate, 'showStatusDetail' => true])
    @endif
    <div class="alert alert-info small mt-2">
        حفظ هذا النموذج يحفظ قالب ERP والربط فقط، ولا يقدّم قالباً إلى ميتا أو ينشئه في ClickChat.
        لإنشاء القالب وتقديمه افتح <a href="{{ route('whatsapp.inbox.index') }}">صندوق واتساب</a> ثم «إنشاء قالب جديد وإرساله إلى Meta للمراجعة» مع اختيار مزود Click Live Chat.
    </div>
    @if (!empty($clickChatTemplates))
        <label class="form-label">القوالب المعتمدة للرقم الافتراضي (لنسخ الاسم واللغة وترتيب المتغيرات)</label>
        <ul class="small">
        @foreach ($clickChatTemplates as $approved)
            <li><code>{{ $approved['name'] }}</code> — {{ $approved['language'] }} — {{ $approved['body'] }}</li>
        @endforeach
        </ul>
    @endif
    <label class="form-label">اسم القالب المعتمد في اللايف شات</label>
    <input class="form-control mb-2" name="clickchat_name" value="{{ old('clickchat_name', data_get($smsTemplate ?? null, 'clickchat_binding.name')) }}" placeholder="contract_update" pattern="[a-z0-9_]+">
    <label class="form-label">لغة القالب</label>
    <input class="form-control mb-2" name="clickchat_language" value="{{ old('clickchat_language', data_get($smsTemplate ?? null, 'clickchat_binding.language', 'ar')) }}" placeholder="ar">
    <label class="form-label">أسماء المتغيرات بترتيب قالب ميتا، مفصولة بفواصل</label>
    <input class="form-control" name="clickchat_variables" value="{{ old('clickchat_variables', implode(', ', data_get($smsTemplate ?? null, 'clickchat_binding.variables', []))) }}" placeholder="client_name, contract_number">
    <small class="text-muted">مثال: client_name للقيمة &#123;&#123;1&#125;&#125; ثم contract_number للقيمة &#123;&#123;2&#125;&#125;. انسخ اسم القالب واللغة من اللايف شات بعد اعتماده. ترك الاسم فارغًا يلغي الربط.</small>
</fieldset>
