<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="300">
    <title>النظام تحت التحديث</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; --ink:#102439; --muted:#64748b; --brand:#0f766e; --line:#dfe8ee; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px; color:var(--ink); font-family:"IBM Plex Sans Arabic","Segoe UI",Tahoma,Arial,sans-serif; background:radial-gradient(circle at 85% 15%,#dff3ef 0,transparent 32%),radial-gradient(circle at 10% 90%,#e6edf7 0,transparent 34%),#f4f7f9; }
        .card { width:min(720px,100%); overflow:hidden; border:1px solid var(--line); border-radius:28px; background:rgba(255,255,255,.94); box-shadow:0 24px 70px rgba(16,36,57,.12); }
        .visual { position:relative; min-height:220px; display:grid; place-items:center; overflow:hidden; background:linear-gradient(145deg,#102f48,#0f766e); }
        .visual::before,.visual::after { content:""; position:absolute; border:38px solid rgba(255,255,255,.07); border-radius:50%; }
        .visual::before { width:210px; height:210px; inset:-100px auto auto -65px; }
        .visual::after { width:160px; height:160px; inset:auto -50px -85px auto; }
        .gear { width:112px; height:112px; display:grid; place-items:center; border:2px solid rgba(255,255,255,.28); border-radius:32px; background:rgba(255,255,255,.1); color:white; box-shadow:0 18px 40px rgba(0,0,0,.16); transform:rotate(-7deg); }
        .gear svg { width:62px; height:62px; animation:turn 8s linear infinite; }
        .content { padding:38px 42px 34px; text-align:center; }
        .status { display:inline-flex; align-items:center; gap:8px; padding:6px 13px; border-radius:999px; color:#0b675f; background:#e7f6f3; font-size:.8rem; font-weight:700; }
        .status::before { content:""; width:8px; height:8px; border-radius:50%; background:#13a394; box-shadow:0 0 0 5px rgba(19,163,148,.12); }
        h1 { margin:18px 0 10px; font-size:clamp(1.65rem,5vw,2.35rem); line-height:1.35; }
        p { max-width:570px; margin:0 auto; color:var(--muted); font-size:1.02rem; line-height:1.95; white-space:pre-line; }
        .footer { display:flex; align-items:center; justify-content:center; gap:10px; margin-top:26px; padding-top:22px; border-top:1px solid #edf1f4; color:#8190a2; font-size:.82rem; }
        .footer svg { width:18px; height:18px; color:var(--brand); }
        @keyframes turn { to { transform:rotate(360deg); } }
        @media (prefers-reduced-motion:reduce) { .gear svg { animation:none; } }
        @media (max-width:560px) { .visual{min-height:180px}.content{padding:30px 22px 26px}.gear{width:94px;height:94px}.gear svg{width:54px;height:54px} }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="visual" aria-hidden="true">
            <div class="gear">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.55V21h-4v-.08A1.7 1.7 0 0 0 8.97 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.52-1.03H3v-4h.08A1.7 1.7 0 0 0 4.6 8.94a1.7 1.7 0 0 0-.34-1.88L4.2 7l2.83-2.83.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 10 3.05V3h4v.05a1.7 1.7 0 0 0 1.03 1.52 1.7 1.7 0 0 0 1.88-.34l.06-.06L19.8 7l-.06.06a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.52 1.03H21v4h-.08A1.7 1.7 0 0 0 19.4 15Z"/>
                </svg>
            </div>
        </div>
        <section class="content">
            <span class="status">تحديثات قيد التنفيذ</span>
            <h1>النظام تحت التحديث</h1>
            <p>{{ $maintenance['message'] }}</p>
            <div class="footer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                يتم تحديث الصفحة تلقائيًا عند عودة الخدمة
            </div>
        </section>
    </main>
</body>
</html>
