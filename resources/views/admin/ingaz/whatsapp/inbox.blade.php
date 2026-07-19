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
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@if($selectedConversation)
@section('js')
<script>
    (function () {
        const box = document.getElementById('waMessages');
        if (!box) return;

        const messagesUrl = @json(route('whatsapp.inbox.messages', $selectedConversation));
        const statusLabels = { sent: 'مرسلة ✓', delivered: 'مستلمة ✓✓', read: 'مقروءة ✓✓', failed: 'فشلت' };
        const escapeHtml = (value) => $('<div>').text(value || '').html();

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
    })();
</script>
@endsection
@endif
