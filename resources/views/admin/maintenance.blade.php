<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0a1322">
    <noscript><meta http-equiv="refresh" content="60"></noscript>
    <title>النظام تحت التحديث — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* صفحة صيانة لوحة التحكم: شاشة كاملة داكنة بهوية كليك (كحلي وأخضر) */
        :root {
            color-scheme: dark;
            --bg: #0a1322;
            --bg-2: #0f1d33;
            --navy: #16233f;
            --green: #7ab648;
            --green-2: #a3d46f;
            --teal: #14b8a6;
            --ink: #f1f5fb;
            --muted: #9aa8bd;
            --line: rgba(255, 255, 255, .09);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            color: var(--ink);
            font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, Arial, sans-serif;
            background: radial-gradient(1200px 700px at 50% 38%, var(--bg-2) 0%, var(--bg) 62%), var(--bg);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* الخلفية: شبكة خافتة وهالات ضوئية متحركة */
        .backdrop { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
        .grid {
            position: absolute; inset: -2px;
            background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 42%, #000 0%, transparent 78%);
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 42%, #000 0%, transparent 78%);
            opacity: .55;
        }
        .glow { position: absolute; border-radius: 50%; filter: blur(90px); opacity: .5; animation: drift 18s ease-in-out infinite alternate; }
        .glow-green { width: 520px; height: 520px; background: rgba(122, 182, 72, .30); top: -160px; inset-inline-end: -140px; }
        .glow-teal { width: 460px; height: 460px; background: rgba(20, 184, 166, .22); bottom: -180px; inset-inline-start: -120px; animation-delay: -6s; }
        .glow-navy { width: 600px; height: 600px; background: rgba(56, 97, 170, .22); top: 30%; left: 50%; transform: translateX(-50%); animation-duration: 24s; }

        /* الشريط العلوي */
        .top { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 22px clamp(18px, 4vw, 44px); }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; letter-spacing: .01em; }
        .brand-mark { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center;
            background: linear-gradient(145deg, var(--navy), #1f3a63); border: 1px solid rgba(122, 182, 72, .45);
            box-shadow: 0 0 0 4px rgba(122, 182, 72, .08); color: var(--green-2); font-size: .95rem; }
        .live { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; font-size: .82rem; font-weight: 600;
            color: #ffd88a; background: rgba(245, 166, 35, .1); border: 1px solid rgba(245, 166, 35, .28); }
        .live::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #f5a623; animation: pulse 1.8s ease-out infinite; }

        /* المحتوى */
        main { position: relative; z-index: 1; flex: 1; display: grid; place-items: center; padding: 12px clamp(18px, 4vw, 44px) 28px; }
        .stage { width: min(760px, 100%); text-align: center; }

        .emblem { position: relative; width: 196px; height: 196px; margin: 0 auto 30px; display: grid; place-items: center; }
        .ring { position: absolute; inset: 0; border-radius: 50%; border: 1px solid rgba(255, 255, 255, .10); }
        .ring-2 { inset: 22px; border-color: rgba(122, 182, 72, .22); }
        .orbit { position: absolute; inset: 0; animation: spin 14s linear infinite; }
        .orbit::before { content: ""; position: absolute; top: -5px; left: 50%; width: 10px; height: 10px; margin-left: -5px; border-radius: 50%;
            background: var(--green); box-shadow: 0 0 16px 4px rgba(122, 182, 72, .55); }
        .orbit-2 { inset: 22px; animation-duration: 9s; animation-direction: reverse; }
        .orbit-2::before { background: var(--teal); box-shadow: 0 0 14px 3px rgba(20, 184, 166, .5); width: 8px; height: 8px; margin-left: -4px; top: -4px; }
        .core { position: relative; width: 108px; height: 108px; border-radius: 30px; display: grid; place-items: center;
            background: linear-gradient(150deg, rgba(122, 182, 72, .22), rgba(22, 35, 63, .9) 60%);
            border: 1px solid rgba(163, 212, 111, .35);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .12), 0 0 60px rgba(122, 182, 72, .18); }
        .core svg { width: 56px; height: 56px; color: var(--green-2); animation: spin 9s linear infinite; }

        .eyebrow { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px; padding: 6px 14px; border-radius: 999px;
            font-size: .82rem; font-weight: 600; color: var(--green-2); background: rgba(122, 182, 72, .1); border: 1px solid rgba(122, 182, 72, .25); }
        h1 { margin: 0 0 16px; font-size: clamp(2rem, 6vw, 3.25rem); line-height: 1.25; font-weight: 700; letter-spacing: -.01em;
            background: linear-gradient(180deg, #ffffff 30%, #c9d6e8); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .message { max-width: 600px; margin: 0 auto; color: var(--muted); font-size: clamp(1rem, 2.4vw, 1.12rem); line-height: 2; white-space: pre-line; }

        /* شريط تقدم غير محدد */
        .progress { position: relative; width: min(360px, 80%); height: 4px; margin: 34px auto 0; border-radius: 999px; background: rgba(255, 255, 255, .08); overflow: hidden; }
        .progress span { position: absolute; inset-block: 0; width: 38%; border-radius: inherit;
            background: linear-gradient(90deg, transparent, var(--green), var(--teal), transparent); animation: slide 2.2s ease-in-out infinite; }

        .actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 12px 18px; margin-top: 30px; }
        .retry { display: inline-flex; align-items: center; gap: 9px; min-height: 46px; padding: 0 22px; border-radius: 12px; cursor: pointer;
            font: inherit; font-weight: 600; font-size: .95rem; color: #0a1322; background: linear-gradient(180deg, var(--green-2), var(--green));
            border: 0; box-shadow: 0 10px 26px rgba(122, 182, 72, .3); transition: transform .15s ease, box-shadow .15s ease; }
        .retry:hover { transform: translateY(-1px); box-shadow: 0 14px 30px rgba(122, 182, 72, .38); }
        .retry:focus-visible { outline: 3px solid rgba(163, 212, 111, .5); outline-offset: 3px; }
        .retry svg { width: 18px; height: 18px; }
        .countdown { display: inline-flex; align-items: center; gap: 8px; color: var(--muted); font-size: .88rem; }
        .countdown svg { width: 17px; height: 17px; color: var(--teal); }
        .countdown b { color: var(--ink); font-variant-numeric: tabular-nums; font-weight: 600; direction: ltr; unicode-bidi: embed; }

        .notes { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 44px; }
        .note { display: flex; align-items: center; gap: 10px; padding: 13px 14px; border-radius: 14px; text-align: start;
            background: rgba(255, 255, 255, .035); border: 1px solid var(--line); color: #c3cfdf; font-size: .84rem; line-height: 1.6; }
        .note svg { width: 20px; height: 20px; flex: 0 0 auto; color: var(--green-2); }

        footer { position: relative; z-index: 1; padding: 18px; text-align: center; color: #6f7f96; font-size: .8rem; }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slide { 0% { inset-inline-start: -40%; } 100% { inset-inline-start: 100%; } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(245, 166, 35, .55); } 100% { box-shadow: 0 0 0 9px rgba(245, 166, 35, 0); } }
        @keyframes drift { to { transform: translate3d(40px, 30px, 0) scale(1.08); } }
        .glow-navy { animation-name: none; }

        @media (prefers-reduced-motion: reduce) {
            .orbit, .core svg, .progress span, .glow, .live::before { animation: none; }
        }
        @media (max-width: 640px) {
            .top { padding: 16px 18px; }
            .brand span { display: none; }
            .emblem { width: 156px; height: 156px; margin-bottom: 24px; }
            .ring-2, .orbit-2 { inset: 18px; }
            .core { width: 88px; height: 88px; border-radius: 24px; }
            .core svg { width: 46px; height: 46px; }
            .notes { grid-template-columns: 1fr; margin-top: 34px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="backdrop" aria-hidden="true">
        <div class="grid"></div>
        <div class="glow glow-navy"></div>
        <div class="glow glow-green"></div>
        <div class="glow glow-teal"></div>
    </div>

    <header class="top">
        <div class="brand">
            <span class="brand-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) config('app.name', 'C'), 0, 1)) }}</span>
            <span>{{ config('app.name') }}</span>
        </div>
        <span class="live">صيانة مجدولة</span>
    </header>

    <main role="main">
        <div class="stage">
            <div class="emblem" aria-hidden="true">
                <span class="ring"></span>
                <span class="ring ring-2"></span>
                <span class="orbit"></span>
                <span class="orbit orbit-2"></span>
                <div class="core">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.55V21h-4v-.08A1.7 1.7 0 0 0 8.97 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.52-1.03H3v-4h.08A1.7 1.7 0 0 0 4.6 8.94a1.7 1.7 0 0 0-.34-1.88L4.2 7l2.83-2.83.06.06a1.7 1.7 0 0 0 1.88.34A1.7 1.7 0 0 0 10 3.05V3h4v.05a1.7 1.7 0 0 0 1.03 1.52 1.7 1.7 0 0 0 1.88-.34l.06-.06L19.8 7l-.06.06a1.7 1.7 0 0 0-.34 1.88 1.7 1.7 0 0 0 1.52 1.03H21v4h-.08A1.7 1.7 0 0 0 19.4 15Z"/>
                    </svg>
                </div>
            </div>

            <span class="eyebrow">تحديثات قيد التنفيذ</span>
            <h1>النظام تحت التحديث</h1>
            <p class="message">{{ $maintenance['message'] }}</p>

            <div class="progress" role="progressbar" aria-label="جارٍ التحديث"><span></span></div>

            <div class="actions">
                <button type="button" class="retry" id="retryNow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v6h-6"/></svg>
                    تحقق الآن
                </button>
                <span class="countdown" aria-live="polite">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    تحديث تلقائي خلال <b id="countdown">01:00</b>
                </span>
            </div>

            <div class="notes">
                <div class="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-4.5"/></svg>
                    بياناتك محفوظة بأمان ولن تتأثر بالتحديث.
                </div>
                <div class="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v6h-6"/></svg>
                    تعود الصفحة تلقائيًا فور انتهاء التحديث.
                </div>
                <div class="note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4"/></svg>
                    نعمل على تحسين الأداء وإضافة مزايا جديدة.
                </div>
            </div>
        </div>
    </main>

    <footer>&copy; {{ now()->year }} {{ config('app.name') }}</footer>

    <script>
        (function () {
            // يعيد المحاولة كل دقيقة؛ عند انتهاء الصيانة يفتح النظام مباشرة.
            var seconds = 60;
            var label = document.getElementById('countdown');
            var reload = function () { window.location.reload(); };
            document.getElementById('retryNow').addEventListener('click', reload);
            setInterval(function () {
                seconds -= 1;
                if (seconds <= 0) { reload(); return; }
                label.textContent = '00:' + String(seconds).padStart(2, '0');
            }, 1000);
        })();
    </script>
</body>
</html>
