@extends('admin.layout.indexs.index')

@section('page-title')
إرسال رسائل واتساب
@endsection

@section('content')
@if (\App\Support\MessagingPermissions::allows(\App\Support\MessagingPermissions::SEND))
    <div class="container-fluid">
        @include('admin.ingaz.messaging._nav')
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="mb-0">
                    <i class="fa fa-whatsapp text-success me-2"></i>
                    إرسال رسالة واتساب
                </h4>
            </div>
            <div class="card-body">
                <div class="alert {{ $whatsappConfig['configured'] ? 'alert-success' : 'alert-danger' }} mb-4">
                    @if ($whatsappConfig['configured'])
                        <strong>حالة الربط:</strong> تكامل واتساب عبر {{ $whatsappConfig['provider_label'] }} جاهز للإرسال.
                    @else
                        <strong>حالة الربط:</strong> غير مكتمل. القيم المفقودة:
                        <code>{{ implode(', ', $whatsappConfig['missing']) }}</code>
                    @endif
                    @if ($whatsappConfig['driver'] === 'meta')
                        <hr>
                        <div class="small">
                            <div><strong>Webhook Verify URL:</strong> <code>{{ $whatsappConfig['webhook_verify_url'] }}</code></div>
                            <div><strong>Webhook Receive URL:</strong> <code>{{ $whatsappConfig['webhook_receive_url'] }}</code></div>
                            <div class="mt-1 text-muted">استخدم نفس المسارين على دومين عام (HTTPS) داخل Meta.</div>
                        </div>
                    @elseif (($whatsappConfig['driver'] ?? '') === 'clickchat')
                        <hr>
                        <div class="small">
                            <div><strong>المزود الحالي:</strong> Click Live Chat</div>
                            <div><strong>الأرقام النشطة في المنصة:</strong> {{ count($whatsappConfig['senders']) }}</div>
                            <div class="mt-1 text-muted">الأرقام تُدار من المنصة (ميتا أو QR)، ويمكن اختيار الرقم المرسل منه عند التنفيذ.</div>
                        </div>
                    @else
                        <hr>
                        <div class="small">
                            <div><strong>المزود الحالي:</strong> UltraMsg</div>
                            <div><strong>عدد الأرقام المفعلة:</strong> {{ count($whatsappConfig['senders']) }}</div>
                            <div class="mt-1 text-muted">يمكنك ربط أكثر من رقم من شاشة إعدادات الربط، ثم اختيار الرقم المرسل منه عند التنفيذ.</div>
                        </div>
                    @endif
                </div>

                <div class="alert alert-info mb-4">
                    <strong>ملاحظة الاستخدام:</strong>
                    هذه الشاشة مخصصة حاليًا لرسائل <strong>التحقق والمتابعة</strong>.
                    @if ($whatsappConfig['allow_advertising'])
                        تم تفعيل الإرسال الدعائي من الإعدادات.
                    @else
                        الإرسال الدعائي غير مفعل حتى يتم تشغيله من إعدادات الربط.
                    @endif
                </div>

                <form id="whatsappDispatchForm" class="row g-3 from-submit-global" method="post" action="{{ route('whatsapp.notifications.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="whatsapp">

                    <div class="col-md-4">
                        <label for="whatsapp_purpose" class="form-label">غرض الرسالة</label>
                        <select name="whatsapp_purpose" id="whatsapp_purpose" class="form-control" required>
                            <option value="verification" {{ $whatsappConfig['default_purpose'] === 'verification' ? 'selected' : '' }}>تحقق</option>
                            <option value="follow_up" {{ $whatsappConfig['default_purpose'] === 'follow_up' ? 'selected' : '' }}>متابعة</option>
                            @if ($whatsappConfig['allow_advertising'])
                                <option value="advertising" {{ $whatsappConfig['default_purpose'] === 'advertising' ? 'selected' : '' }}>دعائي</option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="all_or_some" class="form-label">نطاق الإرسال</label>
                        <select name="all_or_some" id="all_or_some" class="form-control" required>
                            <option value="some" selected>عملاء محددون</option>
                            <option value="all">كل العملاء (يتطلب تأكيد)</option>
                        </select>
                    </div>

                    @if (in_array($whatsappConfig['driver'], ['ultramsg', 'clickchat'], true) && count($whatsappConfig['senders']) > 0)
                        <div class="col-md-4">
                            <label for="whatsapp_sender_key" class="form-label">الرقم المُرسل منه</label>
                            <select name="whatsapp_sender_key" id="whatsapp_sender_key" class="form-control">
                                @foreach ($whatsappConfig['senders'] as $sender)
                                    <option value="{{ $sender['key'] }}" {{ $whatsappConfig['default_sender_key'] === $sender['key'] ? 'selected' : '' }}>
                                        {{ $sender['name'] }}@if(!empty($sender['phone_number'])) - {{ $sender['phone_number'] }} @endif @if(!empty($sender['instance_id']))({{ $sender['instance_id'] }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="w-100 bg-light rounded p-3 text-center">
                            <strong>العدد المتوقع:</strong>
                            <span id="recipientCount" class="badge bg-success ms-1">0</span>
                        </div>
                    </div>

                    <div class="col-12">
                        @if(in_array($whatsappConfig['driver'] ?? '', ['chatwoot', 'clickchat'], true))
                            <label for="chatwootTemplate">{{ ($whatsappConfig['driver'] ?? '') === 'clickchat' ? 'قالب واتساب المعتمد (أرقام ميتا)' : 'قالب Widers المعتمد' }}</label>
                            <select id="chatwootTemplate" name="chatwoot_template" class="form-control mb-3">
                                <option value="">{{ ($whatsappConfig['driver'] ?? '') === 'clickchat' ? 'نص حر — أرقام ميتا تحتاج نافذة رد مفتوحة خلال 24 ساعة، وأرقام QR بلا قيد' : 'نص حر — يتطلب نافذة رد مفتوحة خلال 24 ساعة' }}</option>
                                @foreach(($chatwootTemplates ?? []) as $template)
                                    <option value="{{ $template['name'].'|'.$template['language'] }}">{{ $template['name'] }} — {{ $template['language'] }} — {{ $template['category'] }}</option>
                                @endforeach
                            </select>
                            <div id="chatwootVariables"></div>
                            <p class="text-muted">اختر قالبًا لبدء المحادثة. القائمة تدعم قوالب النص دون رؤوس أو أزرار. القيم المدخلة مشتركة لكل المستلمين المحددين.</p>
                            @if($chatwootTemplateError ?? null)<div class="alert alert-warning">{{ $chatwootTemplateError }}</div>@endif
                        @endif
                        <label for="message_override" class="form-label">نص رسالة واتساب</label>
                        <textarea name="message_override" id="message_override" class="form-control" rows="6" required
                            placeholder="اكتب رسالة التحقق أو المتابعة هنا"></textarea>
                    </div>

                    <div class="col-12" id="someRecipientsBox">
                        <label for="usersSelect" class="form-label">اختر العملاء</label>
                        <select id="usersSelect" name="user_id[]" class="form-control js-select-users" multiple>
                            @foreach ($appUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->phone }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-none" id="allRecipientsBox">
                        <div class="border border-danger rounded p-3">
                            <h6 class="text-danger mb-2"><i class="fa fa-triangle-exclamation me-1"></i> حماية الإرسال الجماعي</h6>
                            <p class="mb-3 text-muted">سيتم الإرسال لكل العملاء أصحاب الأرقام الصالحة في واتساب.</p>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_bulk_send" id="confirm_bulk_send" value="1">
                                <label class="form-check-label" for="confirm_bulk_send">أؤكد الإرسال لكل العملاء</label>
                            </div>

                            <label for="bulk_confirmation_text" class="form-label">
                                اكتب كلمة التأكيد: <code>{{ $whatsappConfig['bulk_keyword'] }}</code>
                            </label>
                            <input type="text" name="bulk_confirmation_text" id="bulk_confirmation_text" class="form-control"
                                autocomplete="off" placeholder="اكتب كلمة التأكيد كما هي">

                            <small class="text-muted d-block mt-2">الحد الأقصى لكل دفعة: <strong>{{ $whatsappConfig['bulk_limit'] }}</strong></small>
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-success" type="submit">
                            <i class="fa fa-paper-plane me-1"></i>
                            إرسال واتساب
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
    const cwTemplates = @json($chatwootTemplates ?? []);
    document.getElementById('chatwootTemplate')?.addEventListener('change', function () {
        const template = cwTemplates.find(t => t.name + '|' + t.language === this.value);
        const container = document.getElementById('chatwootVariables');
        const textarea = document.getElementById('message_override');
        container.replaceChildren();
        textarea.readOnly = !!template;
        if (!template) { textarea.value = ''; return; }
        const body = (template.components || []).find(c => c.type.toUpperCase() === 'BODY')?.text || '';
        const variables = [...new Set([...body.matchAll(/\{\{\s*(\w+)\s*\}\}/g)].map(m => m[1]))];
        const inputs = {};
        const render = () => { textarea.value = body.replace(/\{\{\s*(\w+)\s*\}\}/g, (match, key) => inputs[key]?.value || match); };
        variables.forEach(key => {
            const label = document.createElement('label'); label.textContent = 'قيمة المتغير ' + key;
            const input = document.createElement('input'); input.name = 'chatwoot_variables[' + key + ']';
            input.className = 'form-control mb-2'; input.required = true; input.maxLength = 1000;
            inputs[key] = input; input.addEventListener('input', render); container.append(label, input);
        });
        render();
    });
    const totalEligibleUsers = {{ (int) $totalEligibleUsers }};

    function updateRecipientCount() {
        const mode = $('#all_or_some').val();
        let count = 0;

        if (mode === 'all') {
            count = totalEligibleUsers;
        } else {
            count = ($('#usersSelect').val() || []).length;
        }

        $('#recipientCount').text(count);
    }

    function toggleRecipientMode() {
        const isAll = $('#all_or_some').val() === 'all';

        $('#someRecipientsBox').toggleClass('d-none', isAll);
        $('#allRecipientsBox').toggleClass('d-none', !isAll);

        $('#usersSelect').prop('required', !isAll);
        $('#confirm_bulk_send').prop('required', isAll);
        $('#bulk_confirmation_text').prop('required', isAll);

        if (!isAll) {
            $('#confirm_bulk_send').prop('checked', false);
            $('#bulk_confirmation_text').val('');
        }

        updateRecipientCount();
    }

    $(document).ready(function () {
        $('.js-select-users').select2({
            placeholder: 'اختر العملاء',
            allowClear: true
        });

        $('#usersSelect, #all_or_some').on('change', function () {
            toggleRecipientMode();
            updateRecipientCount();
        });

        $('#whatsappDispatchForm').on('submit', function (event) {
            if ($('#all_or_some').val() !== 'all') {
                return true;
            }

            const recipientCount = Number($('#recipientCount').text() || 0);
            const confirmed = window.confirm(`سيتم إرسال واتساب إلى ${recipientCount} عميل. هل تريد المتابعة؟`);
            if (!confirmed) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }

            return true;
        });

        toggleRecipientMode();
        updateRecipientCount();
    });
</script>
@endsection
