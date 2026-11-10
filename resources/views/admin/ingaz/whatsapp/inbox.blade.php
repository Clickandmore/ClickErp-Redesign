@extends('admin.layout.indexs.index')

@section('page-title')
محادثات واتساب
@endsection

@section('style')
<style>
    .wa-inbox { min-height: 680px; overflow: hidden; }
    .wa-list { height: 680px; overflow-y: auto; border-left: 1px solid #e7e9ed; }
    .wa-chat { height: 680px; display: flex; flex-direction: column; background: #efeae2; }
    .wa-chat__messages { flex: 1; overflow-y: auto; padding: 22px; }
    .wa-chat__empty { display: grid; place-items: center; height: 100%; color: #6c757d; }
    .wa-conversation { display: block; color: inherit; padding: 14px; border-bottom: 1px solid #eef0f2; text-decoration: none; }
    .wa-conversation:hover, .wa-conversation.active { background: #edf8f3; color: inherit; }
    .wa-avatar { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; color: #fff; background: #25d366; flex: 0 0 44px; }
    .wa-message { display: flex; margin-bottom: 10px; }
    .wa-message.inbound { justify-content: flex-start; }
    .wa-message.outbound { justify-content: flex-end; }
    .wa-bubble { max-width: 75%; padding: 9px 12px 6px; border-radius: 10px; box-shadow: 0 1px 1px rgba(0,0,0,.08); white-space: pre-wrap; overflow-wrap: anywhere; }
    .wa-message.inbound .wa-bubble { background: #fff; }
    .wa-message.outbound .wa-bubble { background: #d9fdd3; }
    .wa-meta { font-size: 11px; color: #6c757d; margin-top: 4px; display: flex; gap: 7px; justify-content: flex-end; }
    .wa-composer { background: #f7f7f7; padding: 12px; border-top: 1px solid #ddd; }
    @media (max-width: 991px) {
        .wa-list { height: 300px; border-left: 0; border-bottom: 1px solid #ddd; }
        .wa-chat { height: 620px; }
    }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1"><i class="fab fa-whatsapp text-success me-2"></i>محادثات واتساب</h4>
                <div class="text-muted small">الرسائل الواردة من WhatsApp Cloud API والرد عليها من داخل كليك.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#metaTemplatesModal">
                    <i class="fa fa-layer-group me-1"></i> قوالب Meta
                </button>
                <a href="{{ route('whatsapp.notifications.index') }}" class="btn btn-success">
                    <i class="fa fa-paper-plane me-1"></i> إرسال جديد
                </a>
                <a href="{{ route('notifications.settings') }}#whatsapp-settings" class="btn btn-outline-dark">
                    <i class="fa fa-cog me-1"></i> إعدادات Meta
                </a>
                <a href="{{ route('whatsapp.inbox.index', array_filter(['conversation' => $selectedConversation?->id, 'q' => request('q')])) }}" class="btn btn-outline-success">
                    <i class="fa fa-sync me-1"></i> تحديث
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card wa-inbox">
            <div class="row g-0">
                <div class="col-lg-4 bg-white">
                    <form method="get" action="{{ route('whatsapp.inbox.index') }}" class="p-3 border-bottom">
                        <div class="input-group">
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث بالاسم أو الرقم">
                            <button class="btn btn-success" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                    <div class="wa-list">
                        @forelse($conversations as $conversation)
                            <a class="wa-conversation {{ $selectedConversation?->id === $conversation->id ? 'active' : '' }}"
                               href="{{ route('whatsapp.inbox.index', array_filter(['conversation' => $conversation->id, 'q' => request('q')])) }}">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="wa-avatar"><i class="fab fa-whatsapp"></i></div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong class="text-truncate">{{ $conversation->display_name }}</strong>
                                            <small class="text-muted text-nowrap">{{ optional($conversation->last_message_at)->format('d/m H:i') }}</small>
                                        </div>
                                        <div class="d-flex justify-content-between gap-2 mt-1">
                                            <span class="text-muted small text-truncate">
                                                {{ $conversation->last_direction === 'outbound' ? 'أنت: ' : '' }}{{ $conversation->last_message_preview }}
                                            </span>
                                            @if($conversation->unread_count > 0)
                                                <span class="badge rounded-pill bg-success">{{ $conversation->unread_count }}</span>
                                            @endif
                                        </div>
                                        <small class="text-muted" dir="ltr">{{ $conversation->phone }}</small>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center text-muted p-5">
                                <i class="fab fa-whatsapp fa-3x mb-3"></i>
                                <div>لا توجد محادثات واردة بعد.</div>
                            </div>
                        @endforelse
                    </div>
                    @if($conversations->hasPages())
                        <div class="p-2 bg-white">{{ $conversations->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
                    @endif
                </div>

                <div class="col-lg-8">
                    @if($selectedConversation)
                        <div class="wa-chat">
                            <div class="bg-white border-bottom px-3 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $selectedConversation->display_name }}</strong>
                                    @if(app(\App\Services\WhatsApp\WhatsAppOptOutService::class)->isOptedOut($selectedConversation->phone))
                                        <span class="badge bg-warning text-dark ms-1" title="لا تصله الرسائل الآلية؛ ردودك من هنا تصله">أوقف الرسائل الآلية</span>
                                    @endif
                                    <div class="small text-muted" dir="ltr">{{ $selectedConversation->phone }}</div>
                                </div>
                                <div class="text-end">
                                    @if($selectedConversation->customerServiceWindowIsOpen())
                                        <span class="badge bg-success">نافذة الرد مفتوحة</span>
                                        <div class="small text-muted mt-1">حتى {{ $selectedConversation->customer_service_window_expires_at->format('d/m/Y H:i') }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">تحتاج قالب Meta معتمد</span>
                                    @endif
                                    @if($selectedConversation->user_id)
                                        <a href="{{ route('clients.show', $selectedConversation->user_id) }}" class="small d-block mt-1">عرض العميل في كليك</a>
                                    @endif
                                </div>
                            </div>

                            <div class="wa-chat__messages" id="waMessages" data-last-id="{{ $messages->last()?->id ?? 0 }}">
                                @foreach($messages as $message)
                                    <div class="wa-message {{ $message->direction }}" data-message-id="{{ $message->id }}">
                                        <div class="wa-bubble">
                                            <div>{{ $message->body ?: 'رسالة بدون نص' }}</div>
                                            @if($message->media_url && preg_match('~^https?://~i', $message->media_url))
                                                <a href="{{ $message->media_url }}" target="_blank" rel="noopener noreferrer" class="small">فتح المرفق</a>
                                            @endif
                                            <div class="wa-meta">
                                                @if($message->sentBy?->name)<span>{{ $message->sentBy->name }}</span>@endif
                                                <span>{{ optional($message->occurred_at ?: $message->created_at)->format('d/m/Y H:i') }}</span>
                                                @if($message->direction === 'outbound')
                                                    <span class="wa-status">{{ ['sent' => 'مرسلة ✓', 'delivered' => 'مستلمة ✓✓', 'read' => 'مقروءة ✓✓', 'failed' => 'فشلت'] [$message->status] ?? $message->status }}</span>
                                                @endif
                                            </div>
                                            @if($message->error_message)
                                                <div class="small text-danger mt-1">{{ $message->error_message }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="wa-composer">
                                @if($selectedConversation->customerServiceWindowIsOpen())
                                    <form method="post" action="{{ route('whatsapp.inbox.send', $selectedConversation) }}" class="d-flex gap-2 align-items-end">
                                        @csrf
                                        <textarea name="message" class="form-control" rows="2" maxlength="4096" required placeholder="اكتب ردك هنا...">{{ old('message') }}</textarea>
                                        <button class="btn btn-success px-4" type="submit"><i class="fa fa-paper-plane"></i></button>
                                    </form>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        مرّ أكثر من 24 ساعة على آخر رسالة من العميل. Meta تسمح بالرد الآن فقط باستخدام قالب رسالة معتمد.
                                    </div>
                                @endif
                                @if($approvedTemplates->isNotEmpty())
                                    <div class="mt-2">
                                        <button class="btn btn-outline-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sendMetaTemplateBox">
                                            <i class="fa fa-paper-plane me-1"></i> إرسال قالب معتمد لهذا العميل
                                        </button>
                                        <div class="collapse mt-2" id="sendMetaTemplateBox">
                                            <form method="post" action="{{ route('whatsapp.inbox.template.send', $selectedConversation) }}" class="card card-body bg-white">
                                                @csrf
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-5">
                                                        <label class="form-label">القالب</label>
                                                        <select name="template_name" id="sendMetaTemplateSelect" class="form-control" required>
                                                            @foreach($approvedTemplates as $template)
                                                                @php
                                                                    $bodyComponent = collect($template['components'] ?? [])->first(fn($component) => strtoupper($component['type'] ?? '') === 'BODY');
                                                                    preg_match_all('/\{\{(\d+)\}\}/', $bodyComponent['text'] ?? '', $templateVariables);
                                                                    $templateVariableCount = count(array_unique($templateVariables[1] ?? []));
                                                                @endphp
                                                                <option value="{{ $template['name'] }}"
                                                                    data-language="{{ $template['language'] }}"
                                                                    data-category="{{ $template['category'] ?? '' }}"
                                                                    data-variable-count="{{ $templateVariableCount }}">
                                                                    {{ $template['name'] }} — {{ $template['language'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="template_language" id="sendMetaTemplateLanguage">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label">قيم المتغيرات</label>
                                                        <textarea name="parameters_text" class="form-control" rows="2"
                                                            placeholder="كل قيمة في سطر مستقل"></textarea>
                                                        <div class="form-text" id="sendMetaTemplateHelp"></div>
                                                        <div class="form-check mt-1 d-none" id="marketingOptInBox">
                                                            <input class="form-check-input" type="checkbox" name="confirm_marketing_opt_in"
                                                                id="confirmMarketingOptIn" value="1">
                                                            <label class="form-check-label" for="confirmMarketingOptIn">
                                                                أؤكد موافقة العميل على استقبال الرسائل التسويقية
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button class="btn btn-success w-100" type="submit">إرسال</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="wa-chat">
                            <div class="wa-chat__empty">
                                <div class="text-center">
                                    <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                                    <h5>اختر محادثة لعرض الرسائل</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="modal fade" id="metaTemplatesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fab fa-whatsapp text-success me-1"></i> إدارة قوالب Meta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        @if(!($templateResult['success'] ?? false))
                            <div class="alert alert-warning">
                                تعذر تحميل القوالب: {{
                                    data_get($templateResult, 'response.error.message')
                                    ?: data_get($templateResult, 'response.message')
                                    ?: 'أكمل ربط WABA ID وAccess Token أولًا.'
                                }}
                            </div>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">القوالب الموجودة في Meta</h6>
                            <a href="{{ route('whatsapp.inbox.index', array_filter(['conversation' => $selectedConversation?->id, 'refresh_templates' => 1])) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-sync me-1"></i> تحديث الحالات
                            </a>
                        </div>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered align-middle bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>الاسم</th>
                                        <th>اللغة</th>
                                        <th>التصنيف</th>
                                        <th>الحالة</th>
                                        <th>النص</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($templates as $template)
                                        @php
                                            $bodyComponent = collect($template['components'] ?? [])->first(fn($component) => strtoupper($component['type'] ?? '') === 'BODY');
                                            $templateStatus = strtoupper($template['status'] ?? '');
                                        @endphp
                                        <tr>
                                            <td><code>{{ $template['name'] ?? '-' }}</code></td>
                                            <td>{{ $template['language'] ?? '-' }}</td>
                                            <td>{{ $template['category'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{
                                                    $templateStatus === 'APPROVED' ? 'bg-success'
                                                    : ($templateStatus === 'REJECTED' ? 'bg-danger' : 'bg-warning text-dark')
                                                }}">{{ $templateStatus ?: '-' }}</span>
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($bodyComponent['text'] ?? '-', 100) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">لا توجد قوالب محملة.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($approvedTemplates->isNotEmpty())
                            <div class="card border-success mb-4">
                                <div class="card-body">
                                    <h6 class="text-success mb-3">استهداف عميل بقالب معتمد</h6>
                                    <form method="post" action="{{ route('whatsapp.templates.send-to-client') }}" class="row g-3 align-items-end">
                                        @csrf
                                        <div class="col-md-4">
                                            <label class="form-label">العميل</label>
                                            <select name="user_id" id="templateTargetUser" class="form-control" required>
                                                <option value="">اختر العميل</option>
                                                @foreach($templateRecipients as $recipient)
                                                    <option value="{{ $recipient->id }}">{{ $recipient->name }} — {{ $recipient->phone }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">القالب المعتمد</label>
                                            <select name="template_name" id="targetMetaTemplateSelect" class="form-control" required>
                                                @foreach($approvedTemplates as $template)
                                                    @php
                                                        $targetBody = collect($template['components'] ?? [])->first(fn($component) => strtoupper($component['type'] ?? '') === 'BODY');
                                                        preg_match_all('/\{\{(\d+)\}\}/', $targetBody['text'] ?? '', $targetVariables);
                                                    @endphp
                                                    <option value="{{ $template['name'] }}"
                                                        data-language="{{ $template['language'] }}"
                                                        data-category="{{ $template['category'] ?? '' }}"
                                                        data-variable-count="{{ count(array_unique($targetVariables[1] ?? [])) }}">
                                                        {{ $template['name'] }} — {{ $template['language'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="template_language" id="targetMetaTemplateLanguage">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">قيم المتغيرات</label>
                                            <textarea name="parameters_text" class="form-control" rows="2"
                                                placeholder="كل قيمة في سطر مستقل"></textarea>
                                            <div class="form-text" id="targetMetaTemplateHelp"></div>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-check d-none" id="targetMarketingOptInBox">
                                                <input class="form-check-input" type="checkbox" name="confirm_marketing_opt_in"
                                                    id="targetConfirmMarketingOptIn" value="1">
                                                <label class="form-check-label" for="targetConfirmMarketingOptIn">
                                                    أؤكد موافقة العميل على استقبال الرسائل التسويقية
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-success w-100" type="submit">
                                                <i class="fa fa-paper-plane me-1"></i> إرسال للعميل
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        <hr>
                        <h6 class="mb-3">إنشاء قالب جديد وإرساله إلى Meta للمراجعة</h6>
                        <form method="post" action="{{ route('whatsapp.templates.store') }}" class="row g-3">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">اسم القالب</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                    pattern="[a-z0-9_]+" placeholder="customer_follow_up" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">اللغة</label>
                                <select name="language" class="form-control">
                                    <option value="ar" {{ old('language', 'ar') === 'ar' ? 'selected' : '' }}>العربية ar</option>
                                    <option value="en_US" {{ old('language') === 'en_US' ? 'selected' : '' }}>English en_US</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">التصنيف</label>
                                <select name="category" class="form-control">
                                    <option value="UTILITY" {{ old('category') === 'UTILITY' ? 'selected' : '' }}>خدمي UTILITY</option>
                                    <option value="MARKETING" {{ old('category') === 'MARKETING' ? 'selected' : '' }}>تسويقي MARKETING</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">العنوان — اختياري</label>
                                <input type="text" name="header" class="form-control" maxlength="60" value="{{ old('header') }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">نص القالب</label>
                                <textarea name="body" class="form-control" rows="4" maxlength="1024" required
                                    placeholder="مرحبًا @{{1}}، نود متابعتك بخصوص الطلب رقم @{{2}}">{{ old('body') }}</textarea>
                                <div class="form-text">استخدم @{{1}} ثم @{{2}} للمتغيرات.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">أمثلة المتغيرات</label>
                                <textarea name="examples_text" class="form-control" rows="4"
                                    placeholder="محمد&#10;ORD-1001">{{ old('examples_text') }}</textarea>
                                <div class="form-text">مثال واحد في كل سطر وبنفس ترتيب المتغيرات.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">التذييل — اختياري</label>
                                <input type="text" name="footer" class="form-control" maxlength="60" value="{{ old('footer') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">أزرار الرد السريع — اختياري</label>
                                <textarea name="quick_replies_text" class="form-control" rows="2"
                                    placeholder="مهتم&#10;تواصل لاحقًا">{{ old('quick_replies_text') }}</textarea>
                                <div class="form-text">زر في كل سطر، بحد أقصى 3 أزرار.</div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button class="btn btn-success" type="submit">
                                    <i class="fa fa-cloud-upload-alt me-1"></i> إنشاء القالب في Meta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script>
    (function () {
        const box = document.getElementById('waMessages');
        const messagesUrl = @json($selectedConversation ? route('whatsapp.inbox.messages', $selectedConversation) : null);
        const statusLabels = { sent: 'مرسلة ✓', delivered: 'مستلمة ✓✓', read: 'مقروءة ✓✓', failed: 'فشلت' };
        const escapeHtml = (value) => $('<div>').text(value || '').html();

        if (box && messagesUrl) {
        function scrollToBottom() {
            box.scrollTop = box.scrollHeight;
        }

        function appendMessage(message) {
            if (box.querySelector(`[data-message-id="${message.id}"]`)) return;
            const status = message.direction === 'outbound'
                ? `<span class="wa-status">${escapeHtml(statusLabels[message.status] || message.status)}</span>`
                : '';
            const sender = message.sent_by ? `<span>${escapeHtml(message.sent_by)}</span>` : '';
            const error = message.error_message ? `<div class="small text-danger mt-1">${escapeHtml(message.error_message)}</div>` : '';
            box.insertAdjacentHTML('beforeend', `
                <div class="wa-message ${message.direction}" data-message-id="${message.id}">
                    <div class="wa-bubble">
                        <div>${escapeHtml(message.body || 'رسالة بدون نص')}</div>
                        ${/^https?:\/\//i.test(message.media_url || '') ? `<a href="${escapeHtml(message.media_url)}" target="_blank" rel="noopener noreferrer" class="small">فتح المرفق</a>` : ''}
                        <div class="wa-meta">${sender}<span>${escapeHtml(message.occurred_at)}</span>${status}</div>
                        ${error}
                    </div>
                </div>
            `);
            box.dataset.lastId = message.id;
        }

        function poll() {
            if (document.hidden) return;
            $.getJSON(messagesUrl, { after_id: Number(box.dataset.lastId || 0) })
                .done(function (response) {
                    const incoming = response.messages || [];
                    incoming.forEach(appendMessage);
                    (response.statuses || []).forEach(function (status) {
                        const row = box.querySelector(`[data-message-id="${status.id}"]`);
                        const statusElement = row ? row.querySelector('.wa-status') : null;
                        if (statusElement) {
                            statusElement.textContent = statusLabels[status.status] || status.status;
                        }
                    });
                    if (incoming.length) scrollToBottom();
                });
        }

        scrollToBottom();
        window.setInterval(poll, 8000);

        function updateTemplateSelection() {
            const select = document.getElementById('sendMetaTemplateSelect');
            if (!select || !select.selectedOptions.length) return;
            const option = select.selectedOptions[0];
            const language = document.getElementById('sendMetaTemplateLanguage');
            const help = document.getElementById('sendMetaTemplateHelp');
            if (language) language.value = option.dataset.language || '';
            if (help) help.textContent = `عدد القيم المطلوبة: ${option.dataset.variableCount || 0}`;
            const isMarketing = (option.dataset.category || '').toUpperCase() === 'MARKETING';
            $('#marketingOptInBox').toggleClass('d-none', !isMarketing);
            $('#confirmMarketingOptIn').prop('required', isMarketing);
            if (!isMarketing) $('#confirmMarketingOptIn').prop('checked', false);
        }

        $('#sendMetaTemplateSelect').on('change', updateTemplateSelection);
        updateTemplateSelection();
        }

        function updateTargetTemplateSelection() {
            const select = document.getElementById('targetMetaTemplateSelect');
            if (!select || !select.selectedOptions.length) return;
            const option = select.selectedOptions[0];
            $('#targetMetaTemplateLanguage').val(option.dataset.language || '');
            $('#targetMetaTemplateHelp').text(`عدد القيم المطلوبة: ${option.dataset.variableCount || 0}`);
            const isMarketing = (option.dataset.category || '').toUpperCase() === 'MARKETING';
            $('#targetMarketingOptInBox').toggleClass('d-none', !isMarketing);
            $('#targetConfirmMarketingOptIn').prop('required', isMarketing);
            if (!isMarketing) $('#targetConfirmMarketingOptIn').prop('checked', false);
        }

        $('#targetMetaTemplateSelect').on('change', updateTargetTemplateSelection);
        updateTargetTemplateSelection();
        if ($.fn.select2 && document.getElementById('templateTargetUser')) {
            $('#templateTargetUser').select2({
                dropdownParent: $('#metaTemplatesModal'),
                placeholder: 'ابحث عن العميل',
                allowClear: true,
                width: '100%'
            });
        }
    })();
</script>
@endsection
