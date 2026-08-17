<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $site->widget_title }}</title>
    <style>
        *{box-sizing:border-box}html,body{width:100%;height:100%;margin:0;background:transparent;font-family:Tahoma,Arial,sans-serif;color:#172033}
        button,input{font:inherit}.launcher{position:absolute;bottom:4px;{{ $site->position === 'left' ? 'left' : 'right' }}:4px;width:64px;height:64px;border:0;border-radius:50%;background:{{ $site->primary_color }};color:#fff;cursor:pointer;box-shadow:0 9px 28px rgba(0,0,0,.28);font-size:28px}
        .unread{position:absolute;top:-3px;right:-3px;min-width:21px;height:21px;padding:2px 5px;border-radius:12px;background:#e6374c;color:#fff;font-size:11px;display:none}
        .panel{position:absolute;inset:0;background:#fff;border-radius:18px;box-shadow:0 12px 45px rgba(0,0,0,.3);overflow:hidden;display:none;border:1px solid #e6eaf0}
        .panel.open{display:flex;flex-direction:column}.header{background:{{ $site->primary_color }};color:#fff;padding:17px;display:flex;align-items:center;gap:11px}.header strong{display:block;font-size:16px}.header small{opacity:.85}.close{margin-right:auto;border:0;background:rgba(255,255,255,.16);color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:20px}
        .messages{flex:1;overflow:auto;background:#f3f5f8;padding:15px}.welcome{background:#fff;border-radius:12px;padding:11px 13px;color:#596273;font-size:13px;margin-bottom:12px;box-shadow:0 2px 7px rgba(0,0,0,.05)}
        .row{display:flex;margin:8px 0}.row.admin{justify-content:flex-start}.row.visitor{justify-content:flex-end}.bubble{max-width:82%;padding:9px 12px;border-radius:13px;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}.admin .bubble{background:#fff;border:1px solid #e3e7ec}.visitor .bubble{background:{{ $site->primary_color }};color:#fff}
        .form{display:flex;gap:8px;padding:11px;border-top:1px solid #e4e8ee}.form input{min-width:0;flex:1;border:1px solid #dce1e8;border-radius:10px;padding:10px;outline:none}.form button{width:44px;border:0;border-radius:10px;background:{{ $site->primary_color }};color:#fff;cursor:pointer}
    </style>
</head>
<body>
    <button class="launcher" id="launcher" type="button" aria-label="فتح المحادثة">💬<span class="unread" id="unread">1</span></button>
    <section class="panel" id="panel">
        <header class="header">
            <div><strong>{{ $site->widget_title }}</strong><small>متاحون لمساعدتك</small></div>
            <button class="close" id="close" type="button" aria-label="إغلاق">×</button>
        </header>
        <main class="messages" id="messages">
            @if($site->welcome_message)
                <div class="welcome">{{ $site->welcome_message }}</div>
            @endif
        </main>
        <form class="form" id="form">
            <input id="input" maxlength="2000" autocomplete="off" placeholder="اكتب رسالتك...">
            <button type="submit" aria-label="إرسال">➤</button>
        </form>
    </section>
<script>
(function () {
    const apiBase = @json($apiBase);
    const token = @json($token);
    const allowedDomains = @json($site->allowed_domains ?? []);
    const autoOpenAdmin = @json($site->launcher_mode !== 'trigger');
    const panel = document.getElementById('panel');
    const messages = document.getElementById('messages');
    const input = document.getElementById('input');
    const unread = document.getElementById('unread');
    let lastId = 0;
    let opened = false;

    function resize(open) {
        opened = open;
        panel.classList.toggle('open', open);
        unread.style.display = 'none';
        parent.postMessage({type: 'clickerp-widget-resize', open: open}, '*');
        if (open) {
            input.focus();
            messages.scrollTop = messages.scrollHeight;
        }
    }
    document.getElementById('launcher').addEventListener('click', () => resize(true));
    document.getElementById('close').addEventListener('click', () => resize(false));
    window.addEventListener('message', function (event) {
        if (event.source !== parent || !event.data || event.data.type !== 'clickerp-widget-open') return;
        let host = '';
        try { host = new URL(event.origin).hostname.toLowerCase(); } catch (error) { return; }
        const allowed = allowedDomains.some(function (domain) {
            domain = String(domain).toLowerCase();
            if (domain === '*') return true;
            if (domain.indexOf('*.') === 0) {
                const base = domain.slice(2);
                return host === base || host.endsWith('.' + base);
            }
            return host === domain;
        });
        if (!allowed) return;
        resize(true);
    });

    function addMessage(message) {
        if (document.querySelector('[data-message-id="' + message.id + '"]')) return;
        const row = document.createElement('div');
        row.className = 'row ' + (message.sender === 'admin' ? 'admin' : 'visitor');
        row.dataset.messageId = message.id;
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = message.body;
        row.appendChild(bubble);
        messages.appendChild(row);
        lastId = Math.max(lastId, Number(message.id));
        messages.scrollTop = messages.scrollHeight;
    }

    function loadMessages() {
        fetch(apiBase + '/messages?after_id=' + lastId, {
            headers: {'Accept': 'application/json', 'X-Visitor-Token': token}
        }).then(response => response.ok ? response.json() : Promise.reject())
          .then(data => {
              let newAdminMessage = false;
              (data.messages || []).forEach(message => {
                  if (message.sender === 'admin') newAdminMessage = true;
                  addMessage(message);
              });
              if (newAdminMessage && !opened && autoOpenAdmin) {
                  unread.style.display = 'block';
                  resize(true);
              }
          }).catch(() => {});
    }

    document.getElementById('form').addEventListener('submit', function (event) {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        input.disabled = true;
        fetch(apiBase + '/messages', {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Visitor-Token': token},
            body: JSON.stringify({body: body})
        }).then(response => response.ok ? response.json() : Promise.reject())
          .then(data => { addMessage(data.message); input.value = ''; })
          .catch(() => {})
          .finally(() => { input.disabled = false; input.focus(); });
    });

    loadMessages();
    setInterval(loadMessages, 3500);
})();
</script>
</body>
</html>
