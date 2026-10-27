{{-- صفحة «الموقع تحت الصيانة» — تصميم محايد لا يخص شركة بعينها، يتغير فيه الشعار والاسم فقط. --}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>الموقع تحت الصيانة | {{ $siteName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f1f5f9;
            --accent: #334155;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            background: #ffffff;
            color: var(--ink);
            font-family: 'Tajawal', system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            overflow-x: hidden;
            position: relative;
        }

        /* شبكة نقاط خفيفة تتلاشى نحو الأطراف */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse at center, #000 0%, transparent 65%);
            mask-image: radial-gradient(ellipse at center, #000 0%, transparent 65%);
            opacity: .55;
            pointer-events: none;
        }

        .card {
            position: relative;
            width: 100%;
            max-width: 560px;
            text-align: center;
            animation: rise .8s cubic-bezier(.2, .8, .2, 1) both;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 120px;
            margin-bottom: 28px;
        }

        .logo img {
            max-width: min(280px, 80vw);
            max-height: 120px;
            object-fit: contain;
        }

        /* أيقونة ترس داخل حلقة دوّارة، تفصل الشعار عن النص */
        .gear {
            position: relative;
            width: 64px;
            height: 64px;
            margin: 0 auto 26px;
            display: grid;
            place-items: center;
            color: var(--accent);
        }

        .gear::before,
        .gear::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1.5px dashed var(--line);
            animation: spin 24s linear infinite;
        }

        .gear::after {
            inset: 6px;
            border: 2px solid transparent;
            border-top-color: var(--accent);
            animation-duration: 2.4s;
        }

        .gear svg { width: 24px; height: 24px; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--soft);
            color: var(--accent);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 18px;
        }

        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, .5);
            animation: pulse 1.8s ease-out infinite;
        }

        h1 {
            font-size: clamp(28px, 6vw, 40px);
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -.01em;
        }

        .soon {
            margin-top: 10px;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            direction: ltr;
            font-size: clamp(15px, 3.6vw, 18px);
            font-weight: 600;
            letter-spacing: .42em;
            text-transform: uppercase;
            color: var(--muted);
            padding-left: .42em;
        }

        .lead {
            margin: 22px auto 0;
            max-width: 420px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.9;
        }

        .progress {
            margin: 34px auto 0;
            width: 180px;
            height: 4px;
            border-radius: 999px;
            background: var(--soft);
            overflow: hidden;
            position: relative;
        }

        .progress span {
            position: absolute;
            inset-block: 0;
            width: 40%;
            border-radius: inherit;
            background: var(--accent);
            animation: slide 1.6s ease-in-out infinite;
        }

        footer {
            margin-top: 48px;
            font-size: 13px;
            color: #94a3b8;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slide { 0% { right: -40%; } 100% { right: 100%; } }
        @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .5); }
            100% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; }
        }
    </style>
</head>
<body>
    <main class="card">
        @if (!empty($logo))
            <div class="logo"><img src="{{ $logo }}" alt="{{ $siteName }}"></div>
        @endif

        <div class="gear" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>

        <div class="badge"><span class="dot"></span>نعمل على تحسين الموقع</div>

        <h1>الموقع تحت الصيانة</h1>
        <p class="soon">Coming Soon</p>

        <p class="lead">
            نجري حاليًا بعض التحديثات لنقدّم لكم تجربة أفضل.
            سنعود قريبًا، شكرًا لصبركم.
        </p>

        <div class="progress" aria-hidden="true"><span></span></div>

        <footer>&copy; {{ date('Y') }} {{ $siteName }}</footer>
    </main>
</body>
</html>
