<style>
    .visitor-chat-widget{position:fixed;right:24px;bottom:92px;z-index:1055;font-family:inherit}
    .visitor-chat-toggle{width:58px;height:58px;border:0;border-radius:50%;color:#fff;background:linear-gradient(135deg,#1763a6,#23b7d9);box-shadow:0 10px 28px rgba(23,99,166,.35);font-size:23px;cursor:pointer}
    .visitor-chat-badge{position:absolute;top:-3px;right:-3px;min-width:20px;height:20px;padding:0 5px;border-radius:10px;background:#dc3545;color:#fff;font-size:11px;line-height:20px;text-align:center;display:none}
    .visitor-chat-panel{position:absolute;right:0;bottom:70px;width:min(360px,calc(100vw - 32px));height:440px;max-height:calc(100vh - 150px);display:none;flex-direction:column;background:#fff;border:1px solid #e8edf3;border-radius:18px;overflow:hidden;box-shadow:0 18px 55px rgba(15,27,64,.22)}
    .visitor-chat-panel.is-open{display:flex}
    .visitor-chat-header{display:flex;align-items:center;justify-content:space-between;padding:15px 17px;color:#fff;background:linear-gradient(135deg,#0f1b40,#1763a6)}
    .visitor-chat-header strong{display:block}.visitor-chat-header small{opacity:.8}
    .visitor-chat-close{border:0;background:transparent;color:#fff;font-size:25px;line-height:1;cursor:pointer}
    .visitor-chat-messages{flex:1;overflow-y:auto;padding:16px;background:#f7f9fc}
    .visitor-chat-empty{text-align:center;color:#7c83a6;font-size:13px;padding:45px 20px}
    .visitor-chat-message{display:flex;margin-bottom:10px}.visitor-chat-message.visitor{justify-content:flex-start}.visitor-chat-message.admin{justify-content:flex-end}
    .visitor-chat-bubble{max-width:82%;padding:9px 12px;border-radius:14px;white-space:pre-wrap;overflow-wrap:anywhere;font-size:14px}
    .visitor-chat-message.visitor .visitor-chat-bubble{background:#fff;border:1px solid #e5eaf0;color:#1f2937}
    .visitor-chat-message.admin .visitor-chat-bubble{background:#1763a6;color:#fff}
    .visitor-chat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #edf1f5;background:#fff}
    .visitor-chat-input{flex:1;min-width:0;border:1px solid #dbe3ec;border-radius:12px;padding:10px 12px;outline:none}
    .visitor-chat-send{border:0;border-radius:12px;padding:0 15px;background:#1763a6;color:#fff;cursor:pointer}
    @media(max-width:575px){.visitor-chat-widget{right:16px;bottom:82px}.visitor-chat-panel{position:fixed;right:16px;bottom:154px}}
</style>

<div class="visitor-chat-widget" id="visitorChatWidget">
    <div class="visitor-chat-panel" id="visitorChatPanel" role="dialog" aria-label="المحادثة المباشرة">
        <div class="visitor-chat-header">
            <div>
                <strong>{{ app()->getLocale() === 'ar' ? 'خدمة العملاء' : 'Customer service' }}</strong>
                <small>{{ app()->getLocale() === 'ar' ? 'نحن هنا لمساعدتك' : 'We are here to help' }}</small>
            </div>
            <button type="button" class="visitor-chat-close" id="visitorChatClose" aria-label="إغلاق">&times;</button>
        </div>
        <div class="visitor-chat-messages" id="visitorChatMessages">
            <div class="visitor-chat-empty" id="visitorChatEmpty">
                {{ app()->getLocale() === 'ar' ? 'اكتب رسالتك وسيقوم أحد موظفي خدمة العملاء بالرد عليك.' : 'Send a message and our team will reply.' }}
            </div>
        </div>
        <form class="visitor-chat-form" id="visitorChatForm">
            <input class="visitor-chat-input" id="visitorChatInput" maxlength="2000"
                placeholder="{{ app()->getLocale() === 'ar' ? 'اكتب رسالتك...' : 'Type your message...' }}" autocomplete="off">
            <button class="visitor-chat-send" type="submit" aria-label="إرسال"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
    <button type="button" class="visitor-chat-toggle" id="visitorChatToggle" aria-label="فتح المحادثة">
        <i class="fas fa-comments"></i>
        <span class="visitor-chat-badge" id="visitorChatBadge">0</span>
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const panel = document.getElementById('visitorChatPanel');
    const messagesBox = document.getElementById('visitorChatMessages');
    const empty = document.getElementById('visitorChatEmpty');
    const badge = document.getElementById('visitorChatBadge');
    const input = document.getElementById('visitorChatInput');
    let lastMessageId = 0;
    let unread = 0;
    let initialized = false;

    function request(url, options) {
        return fetch(url, Object.assign({
            headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json'},
            credentials: 'same-origin'
        }, options || {})).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        });
    }

    function appendMessage(message, isNew) {
        if (document.getElementById('visitor-chat-message-' + message.id)) return;
        empty.style.display = 'none';
        const row = document.createElement('div');
        row.id = 'visitor-chat-message-' + message.id;
        row.className = 'visitor-chat-message ' + message.sender;
        const bubble = document.createElement('div');
        bubble.className = 'visitor-chat-bubble';
        bubble.textContent = message.body;
        row.appendChild(bubble);
        messagesBox.appendChild(row);
        lastMessageId = Math.max(lastMessageId, Number(message.id));
        messagesBox.scrollTop = messagesBox.scrollHeight;

        if (isNew && message.sender === 'admin' && !panel.classList.contains('is-open')) {
            unread++;
            badge.textContent = unread;
            badge.style.display = 'block';
            panel.classList.add('is-open');
        }
    }

    function pollMessages() {
        request(@json(route('visitor-chat.messages')) + '?after_id=' + lastMessageId)
            .then(function (data) {
                (data.messages || []).forEach(function (message) {
                    appendMessage(message, initialized);
                });
                initialized = true;
            }).catch(function () {});
    }

    function heartbeat() {
        request(@json(route('visitor-chat.heartbeat')), {
            method: 'POST',
            body: JSON.stringify({
                current_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                screen_width: window.screen.width,
                screen_height: window.screen.height
            })
        }).catch(function () {});
    }

    document.getElementById('visitorChatToggle').addEventListener('click', function () {
        panel.classList.toggle('is-open');
        if (panel.classList.contains('is-open')) {
            unread = 0;
            badge.style.display = 'none';
            input.focus();
            pollMessages();
        }
    });
    document.getElementById('visitorChatClose').addEventListener('click', function () {
        panel.classList.remove('is-open');
    });
    document.getElementById('visitorChatForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.disabled = true;
        request(@json(route('visitor-chat.send')), {
            method: 'POST',
            body: JSON.stringify({body: body})
        }).then(function (data) {
            appendMessage(data.message, false);
            input.value = '';
        }).catch(function () {}).finally(function () {
            input.disabled = false;
            input.focus();
        });
    });

    heartbeat();
    pollMessages();
    window.setInterval(heartbeat, 30000);
    window.setInterval(pollMessages, 5000);
});
</script>
