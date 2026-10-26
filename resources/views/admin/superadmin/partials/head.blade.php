{{-- الهيدر الموحّد لصفحات Super Admin: خطوط + نظام تصميم واحد لكل الصفحات --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $pageTitle ?? 'لوحة Super Admin' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* ===================================================================
       Super Admin — نظام التصميم الموحّد
       ملف واحد يخدم صفحات: login / recovery / modules بكل أقسامها
       =================================================================== */

    :root {
        /* الأسطح والحدود */
        --bg: #eef2f6;
        --surface: #ffffff;
        --surface-soft: #f7fafc;
        --surface-alt: #eff4f8;
        --border: #e3eaf0;
        --border-strong: #cbd8e2;

        /* النص */
        --text: #0f1e2e;
        --text-soft: #33465c;
        --muted: #6b7c90;

        /* الهوية */
        --accent: #0f766e;
        --accent-dark: #0b5f58;
        --accent-soft: #e6f5f2;
        --accent-line: #9fd3cb;
        --ring: rgba(15, 118, 110, .16);
        --navy: #16324f;
        --navy-deep: #102439;
        --navy-soft: #eef4fa;

        /* الحالات */
        --danger: #b42318;
        --danger-soft: #fef3f2;
        --danger-line: #fbd5d1;
        --success: #067647;
        --success-soft: #ecfdf3;
        --success-line: #abefc6;
        --warning: #b54708;
        --warning-soft: #fffaeb;
        --warning-line: #fedf89;
        --info: #175cd3;
        --info-soft: #eff8ff;
        --info-line: #b2ddff;

        /* الشكل */
        --r-xs: 8px;
        --r-sm: 10px;
        --r-md: 14px;
        --r-lg: 18px;
        --r-xl: 22px;
        --shadow-xs: 0 1px 2px rgba(16, 36, 57, .05);
        --shadow-sm: 0 2px 8px rgba(16, 36, 57, .05), 0 1px 2px rgba(16, 36, 57, .04);
        --shadow-md: 0 8px 24px rgba(16, 36, 57, .07), 0 2px 6px rgba(16, 36, 57, .04);
        --shadow-lg: 0 20px 48px rgba(16, 36, 57, .10), 0 4px 12px rgba(16, 36, 57, .05);
        --mono: "SFMono-Regular", ui-monospace, Consolas, "Liberation Mono", Menlo, monospace;
    }

    *, *::before, *::after { box-sizing: border-box; }

    html { -webkit-text-size-adjust: 100%; }

    body {
        margin: 0;
        min-height: 100vh;
        background:
            radial-gradient(1100px 520px at 100% -10%, #e4f1ef 0%, rgba(228, 241, 239, 0) 60%),
            radial-gradient(900px 460px at 0% 0%, #e8eef7 0%, rgba(232, 238, 247, 0) 55%),
            var(--bg);
        background-attachment: fixed;
        color: var(--text);
        font-family: "IBM Plex Sans Arabic", "Segoe UI", Tahoma, Arial, sans-serif;
        font-size: 15px;
        line-height: 1.7;
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4 { margin: 0; font-weight: 700; line-height: 1.45; letter-spacing: -.01em; }
    p { margin: 0; }
    a { color: var(--accent-dark); }

    ::selection { background: var(--accent-soft); color: var(--accent-dark); }

    /* شريط تمرير أنيق */
    * { scrollbar-width: thin; scrollbar-color: #c3d0dc transparent; }
    *::-webkit-scrollbar { width: 10px; height: 10px; }
    *::-webkit-scrollbar-track { background: transparent; }
    *::-webkit-scrollbar-thumb { background: #ccd8e3; border: 3px solid transparent; border-radius: 999px; background-clip: content-box; }
    *::-webkit-scrollbar-thumb:hover { background: #adbecd; border: 3px solid transparent; background-clip: content-box; }

    /* ============================ الهيكل ============================ */

    .dev-shell,
    .shell {
        width: min(1360px, calc(100% - 40px));
        margin: 24px auto 56px;
    }

    .shell { display: grid; gap: 18px; }

    /* ============================ الشريط العلوي ============================ */

    .topbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        padding: 18px 22px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-sm);
        margin-bottom: 16px;
    }

    .shell > .topbar { margin-bottom: 0; }

    .brand { display: flex; align-items: center; gap: 14px; min-width: 0; }

    .brand-mark,
    .login-mark {
        width: 46px;
        height: 46px;
        flex: 0 0 auto;
        border-radius: var(--r-md);
        display: grid;
        place-items: center;
        background: linear-gradient(140deg, var(--navy) 0%, var(--accent) 100%);
        color: #fff;
        font-weight: 700;
        font-size: 1.02rem;
        letter-spacing: .04em;
        box-shadow: 0 6px 16px rgba(15, 118, 110, .28);
    }

    .brand-title,
    .topbar h1 { margin: 0; font-size: 1.16rem; color: var(--text); }

    .brand-text,
    .topbar .muted { margin: 3px 0 0; color: var(--muted); font-size: .86rem; line-height: 1.6; }

    .topbar-actions,
    .actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

    .topbar-actions form,
    .actions form { margin: 0; }

    /* ============================ الأزرار ============================ */

    .btn {
        min-height: 42px;
        padding: 0 16px;
        border-radius: var(--r-sm);
        border: 1px solid var(--border-strong);
        background: #fff;
        color: var(--text-soft);
        font: inherit;
        font-weight: 600;
        font-size: .89rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        white-space: nowrap;
        box-shadow: var(--shadow-xs);
        transition: background .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease, transform .12s ease;
    }

    .btn:hover { background: var(--surface-alt); border-color: #b8c8d6; color: var(--text); }
    .btn:active { transform: translateY(1px); }
    .btn:focus-visible { outline: none; box-shadow: 0 0 0 4px var(--ring); }
    .btn[disabled], .btn:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }
    .btn[disabled]:hover, .btn:disabled:hover { background: #fff; border-color: var(--border-strong); }

    .btn-primary {
        border-color: transparent;
        background: linear-gradient(180deg, #12857b 0%, var(--accent) 100%);
        color: #fff;
        box-shadow: 0 4px 12px rgba(15, 118, 110, .26);
    }

    .btn-primary:hover { background: var(--accent-dark); border-color: transparent; color: #fff; }

    .btn-danger { border-color: var(--danger-line); background: #fff; color: var(--danger); }
    .btn-danger:hover { background: var(--danger-soft); border-color: #f3aca6; color: var(--danger); }

    .btn-small { min-height: 34px; padding: 0 11px; font-size: .8rem; border-radius: var(--r-xs); }

    /* ============================ التنبيهات ============================ */

    .alerts { display: grid; gap: 10px; margin-bottom: 16px; }

    .alert,
    .error,
    .success {
        position: relative;
        padding: 13px 16px;
        border-radius: var(--r-sm);
        border: 1px solid var(--success-line);
        background: var(--success-soft);
        color: var(--success);
        font-size: .89rem;
        font-weight: 500;
        line-height: 1.75;
        overflow: hidden;
    }

    .alert::before,
    .error::before,
    .success::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: currentColor;
        opacity: .55;
    }

    .alert-danger,
    .error { border-color: var(--danger-line); background: var(--danger-soft); color: var(--danger); }
    .alert-warning { border-color: var(--warning-line); background: var(--warning-soft); color: var(--warning); }
    .alert-info { border-color: var(--info-line); background: var(--info-soft); color: var(--info); }
    .alert strong { font-weight: 700; }
    .alert a { color: inherit; font-weight: 700; }

    /* ============================ تنقّل الأقسام ============================ */

    .section-nav {
        position: sticky;
        top: 12px;
        z-index: 30;
        display: flex;
        gap: 4px;
        padding: 7px;
        margin-bottom: 18px;
        overflow-x: auto;
        background: rgba(255, 255, 255, .86);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        box-shadow: var(--shadow-md);
        backdrop-filter: blur(14px) saturate(160%);
        -webkit-backdrop-filter: blur(14px) saturate(160%);
        scrollbar-width: none;
    }

    .section-nav::-webkit-scrollbar { display: none; }

    .section-nav a {
        min-height: 40px;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        border-radius: var(--r-xs);
        color: #5b6d81;
        text-decoration: none;
        font-size: .855rem;
        font-weight: 600;
        white-space: nowrap;
        transition: background .15s ease, color .15s ease;
    }

    .section-nav a svg { width: 17px; height: 17px; flex: 0 0 auto; opacity: .8; }
    .section-nav a:hover { color: var(--accent-dark); background: var(--surface-alt); }

    .section-nav a.active {
        color: #fff;
        background: linear-gradient(140deg, var(--navy) 0%, #17564f 100%);
        box-shadow: 0 4px 12px rgba(16, 36, 57, .22);
    }

    .section-nav a.active svg { opacity: 1; }
    .nav-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .5; }

    /* ============================ عنوان الصفحة ============================ */

    .page-heading {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        margin: 0 0 18px;
        padding: 22px 26px;
        background: linear-gradient(120deg, var(--navy-deep) 0%, var(--navy) 45%, #12554f 100%);
        color: #fff;
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    .page-heading::after {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        inset-inline-end: -70px;
        top: -140px;
        border-radius: 50%;
        border: 46px solid rgba(255, 255, 255, .05);
        pointer-events: none;
    }

    .page-heading > * { position: relative; }
    .page-heading h2 { margin: 0; font-size: 1.18rem; }
    .page-heading p { margin: 5px 0 0; color: rgba(255, 255, 255, .74); font-size: .87rem; }

    .page-heading-meta { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }

    .heading-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 32px;
        padding: 0 13px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .16);
        color: #fff;
        font-size: .8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .heading-chip b { font-weight: 700; }
    .heading-chip.is-good { background: rgba(110, 231, 183, .16); border-color: rgba(110, 231, 183, .3); }
    .heading-chip.is-bad { background: rgba(252, 165, 165, .16); border-color: rgba(252, 165, 165, .3); }

    /* ============================ اللوحات ============================ */

    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        scroll-margin-top: 84px;
    }

    .panel + .panel { margin-top: 18px; }

    .panel-head {
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
        background: linear-gradient(180deg, #fbfdfe 0%, var(--surface-soft) 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .panel-title,
    .panel-head h2 { margin: 0; font-size: 1.02rem; font-weight: 700; color: var(--text); }

    .panel-subtitle,
    .panel-head .muted { margin: 5px 0 0; color: var(--muted); font-size: .855rem; line-height: 1.7; }

    .panel-body { padding: 22px; }

    .layout { display: grid; grid-template-columns: minmax(0, 1fr) 420px; gap: 18px; align-items: start; }
    .layout.is-single { grid-template-columns: minmax(0, 1fr); }

    .maintenance-control {
        display: grid;
        grid-template-columns: minmax(260px, .9fr) minmax(360px, 1.25fr);
        gap: 24px;
        align-items: center;
        margin-bottom: 18px;
        padding: 22px;
        border: 1px solid var(--success-line);
        border-radius: var(--r-lg);
        background: linear-gradient(135deg, #f4fcf8 0%, #fff 65%);
        box-shadow: var(--shadow-sm);
    }

    .maintenance-control.is-active { border-color: var(--warning-line); background: linear-gradient(135deg, #fff9eb 0%, #fff 65%); }
    .maintenance-control-status { display: flex; align-items: flex-start; gap: 15px; }
    .maintenance-control-icon { width: 48px; height: 48px; flex: 0 0 auto; display: grid; place-items: center; border-radius: var(--r-md); color: var(--success); background: var(--success-soft); border: 1px solid var(--success-line); }
    .maintenance-control.is-active .maintenance-control-icon { color: var(--warning); background: var(--warning-soft); border-color: var(--warning-line); }
    .maintenance-control-icon svg { width: 25px; height: 25px; }
    .maintenance-control-label { display: block; margin-bottom: 3px; color: var(--muted); font-size: .76rem; font-weight: 700; }
    .maintenance-control h2 { font-size: 1.06rem; }
    .maintenance-control p { margin-top: 5px; color: var(--muted); font-size: .82rem; line-height: 1.7; }
    .maintenance-control form { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: end; }
    .maintenance-message-field textarea { min-height: 76px; }
    .maintenance-control form > .btn { min-height: 44px; margin-bottom: 1px; }

    /* عنوان فرعي داخل اللوحة */
    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 0 0 16px;
        padding-inline-start: 12px;
        border-inline-start: 3px solid var(--accent-line);
    }

    .section-heading h2,
    .section-heading h3 { margin: 0; font-size: .98rem; font-weight: 700; }
    .section-heading p { margin: 4px 0 0; color: var(--muted); font-size: .84rem; line-height: 1.7; }

    .panel-body > * + .section-heading { margin-top: 26px; }

    /* فاصل */
    .sa-divider { height: 1px; margin: 22px 0; background: linear-gradient(90deg, transparent, var(--border) 12%, var(--border) 88%, transparent); border: 0; }

    /* ============================ البطاقات الإحصائية ============================ */

    .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }

    .stat {
        position: relative;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        padding: 18px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .stat::before {
        content: "";
        position: absolute;
        top: 0;
        inset-inline: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent), #3ba99a);
    }

    .stat-label { color: var(--muted); font-size: .82rem; font-weight: 600; }
    .stat-value { margin-top: 6px; font-size: 1.55rem; font-weight: 700; color: var(--text); letter-spacing: -.02em; line-height: 1.25; }
    .stat-value small { font-weight: 600; }

    /* شبكة حالة (recovery) */
    .status-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }

    .status-card {
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        background: var(--surface-soft);
    }

    .status-card span { display: block; margin-bottom: 8px; color: var(--muted); font-size: .8rem; font-weight: 600; }
    .status-card strong { font-size: 1rem; font-weight: 700; overflow-wrap: anywhere; }

    /* ============================ الشارات ============================ */

    .badge,
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 27px;
        padding: 0 11px;
        border-radius: 999px;
        font-size: .77rem;
        font-weight: 600;
        white-space: nowrap;
        border: 1px solid transparent;
    }

    .status-on, .ok { background: var(--success-soft); color: var(--success); border-color: var(--success-line); }
    .status-off, .bad { background: var(--danger-soft); color: var(--danger); border-color: var(--danger-line); }

    .risk-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; margin-bottom: 6px; border: 1px solid transparent; }
    .risk-low { color: var(--success); background: var(--success-soft); border-color: var(--success-line); }
    .risk-medium { color: var(--warning); background: var(--warning-soft); border-color: var(--warning-line); }
    .risk-high, .risk-invalid { color: var(--danger); background: var(--danger-soft); border-color: var(--danger-line); }
    .risk-unknown { color: var(--muted); background: var(--surface-alt); border-color: var(--border); }

    .text-muted { color: var(--muted); font-size: .84rem; }

    /* ============================ الحقول ============================ */

    .field { display: grid; gap: 7px; }
    /* الاستثناءات لعناصر label التي تحمل تخطيطها الخاص (صف مفتاح، أو حالة نسخ) */
    .field > label:not(.switch-row):not(.backup-status) { display: block; font-size: .82rem; font-weight: 600; color: var(--text-soft); }
    .field .required { color: var(--danger); }

    .field input,
    .field select,
    .field textarea,
    .search-input,
    .limit-input {
        width: 100%;
        min-height: 44px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-sm);
        padding: 9px 13px;
        color: var(--text);
        font: inherit;
        font-size: .89rem;
        background: #fff;
        box-shadow: var(--shadow-xs);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .field textarea { min-height: 84px; resize: vertical; line-height: 1.75; }
    .field input::placeholder,
    .field textarea::placeholder,
    .search-input::placeholder,
    .limit-input::placeholder { color: #9aa9b8; }

    .field input:focus,
    .field select:focus,
    .field textarea:focus,
    .search-input:focus,
    .limit-input:focus { outline: none; border-color: var(--accent-line); box-shadow: 0 0 0 4px var(--ring); }

    .field input[readonly] { background: var(--surface-alt); color: var(--muted); box-shadow: none; }

    .form-actions { display: flex; justify-content: flex-end; align-items: center; gap: 10px; }
    .save-row { margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }

    /* ============================ الجداول ============================ */

    .table-wrap,
    .backup-table-wrap { overflow-x: auto; margin-top: 18px; border: 1px solid var(--border); border-radius: var(--r-md); background: #fff; }

    .backup-table-wrap > h3 { padding: 15px 16px 0; font-size: .95rem; }

    .backup-table,
    .table-wrap table { width: 100%; border-collapse: collapse; }
    .backup-table { min-width: 780px; }

    .backup-table th,
    .backup-table td,
    .table-wrap th,
    .table-wrap td { text-align: right; padding: 13px 15px; border-bottom: 1px solid var(--border); vertical-align: top; }

    .backup-table thead th,
    .table-wrap thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        color: var(--muted);
        font-size: .78rem;
        font-weight: 700;
        background: var(--surface-soft);
        border-bottom: 1px solid var(--border-strong);
        white-space: nowrap;
    }

    .backup-table tbody tr:last-child td,
    .table-wrap tbody tr:last-child td { border-bottom: 0; }
    .backup-table tbody tr:hover,
    .table-wrap tbody tr:hover { background: #fafcfd; }
    .backup-table td { font-size: .855rem; }
    .backup-file { direction: ltr; text-align: left; font-family: var(--mono); font-size: .8rem; overflow-wrap: anywhere; }
    /* التواريخ والأحجام تُقلب في سياق RTL، لذا تُثبّت على اتجاه LTR مع بقاء المحاذاة لليمين */
    .cell-ltr { direction: ltr; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

    code { direction: ltr; unicode-bidi: embed; font-family: var(--mono); }

    /* ============================ بطاقات الموديلات ============================ */

    .module-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }

    .module-card {
        position: relative;
        min-height: 168px;
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        background: #fff;
        padding: 18px;
        display: grid;
        gap: 14px;
        align-content: space-between;
        box-shadow: var(--shadow-xs);
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .module-card::before {
        content: "";
        position: absolute;
        top: 0;
        inset-inline-start: 0;
        width: 3px;
        height: 100%;
        background: var(--accent);
        opacity: .85;
    }

    .module-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
    .module-card.is-on { background: #fff; }
    .module-card.is-off { background: var(--surface-soft); border-color: var(--border); }
    .module-card.is-off::before { background: #cbd5e1; }
    .module-card.is-off .module-name { color: var(--muted); }

    .module-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .module-name { margin: 0; font-size: .98rem; font-weight: 700; color: var(--text); }

    .module-key,
    .dashboard-option-url,
    .employee-role {
        margin-top: 7px;
        display: inline-flex;
        min-height: 23px;
        align-items: center;
        padding: 0 8px;
        border-radius: var(--r-xs);
        background: var(--surface-alt);
        border: 1px solid var(--border);
        color: #5c7085;
        font-size: .74rem;
        font-weight: 500;
        direction: ltr;
        font-family: var(--mono);
    }

    .employee-role { font-family: inherit; direction: inherit; font-weight: 600; }

    .module-description { margin: 10px 0 0; color: var(--muted); line-height: 1.75; font-size: .85rem; }

    .switch-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        color: var(--text-soft);
        font-weight: 600;
        font-size: .86rem;
    }

    /* الفاصل العلوي مطلوب داخل بطاقة الموديل فقط، لا في الحقول العادية */
    .module-card .switch-row { border-top: 1px solid var(--border); padding-top: 14px; }

    /* داخل حقل عادي يأخذ صف المفتاح شكل عنصر إدخال ليطابق ارتفاع الحقول المجاورة */
    .field > label.switch-row {
        min-height: 44px;
        padding: 0 13px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-sm);
        background: #fff;
        box-shadow: var(--shadow-xs);
        cursor: pointer;
    }

    .limit-row { display: grid; gap: 7px; border-top: 1px solid var(--border); padding-top: 14px; }
    .limit-row label { color: var(--text-soft); font-weight: 600; font-size: .83rem; }
    .limit-help { margin: 0; color: var(--muted); font-size: .77rem; line-height: 1.65; }

    /* المفتاح */
    .switch { position: relative; display: inline-block; width: 46px; height: 26px; flex: 0 0 auto; }
    .switch input { opacity: 0; width: 0; height: 0; }

    .slider {
        position: absolute;
        inset: 0;
        cursor: pointer;
        background: #cbd5e1;
        border-radius: 999px;
        transition: background .2s ease;
        box-shadow: inset 0 1px 2px rgba(16, 36, 57, .12);
    }

    .slider::before {
        content: "";
        position: absolute;
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(16, 36, 57, .3);
        transition: transform .2s ease;
    }

    .switch input:checked + .slider { background: var(--accent); }
    .switch input:checked + .slider::before { transform: translateX(20px); }
    .switch input:focus-visible + .slider { box-shadow: 0 0 0 4px var(--ring); }
    .switch input:disabled + .slider { opacity: .45; cursor: not-allowed; }

    /* ============================ خيارات اللوحة/النوع ============================ */

    .dashboard-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }

    .dashboard-option {
        position: relative;
        display: grid;
        gap: 7px;
        align-content: start;
        padding: 16px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-md);
        background: #fff;
        cursor: pointer;
        box-shadow: var(--shadow-xs);
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }

    .dashboard-option:hover { border-color: var(--accent-line); box-shadow: var(--shadow-sm); }
    .dashboard-option input { position: absolute; inset-inline-start: 16px; top: 19px; width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }
    .dashboard-option-title { padding-inline-start: 26px; font-weight: 700; font-size: .95rem; color: var(--text); }
    .dashboard-option-desc { padding-inline-start: 26px; color: var(--muted); line-height: 1.7; font-size: .84rem; }
    .dashboard-option-url { margin-inline-start: 26px; justify-self: start; }
    .dashboard-option:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); box-shadow: 0 0 0 3px var(--ring); }
    .dashboard-option:has(input:checked) .dashboard-option-title { color: var(--accent-dark); }
    .dashboard-option:has(input:disabled) { opacity: .6; cursor: not-allowed; }

    /* تأكيد تغيير النوع */
    .profile-confirmation { display: none; margin: 0 0 20px; padding: 18px; border: 1px solid var(--danger-line); border-radius: var(--r-md); background: var(--danger-soft); }
    .profile-confirmation.is-visible { display: grid; gap: 14px; }
    .profile-confirmation strong { color: var(--danger); font-size: .95rem; }
    .profile-confirmation p { margin: 0; color: #8a2b22; line-height: 1.8; font-size: .87rem; }
    .profile-confirmation-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .profile-confirmation-fields label { display: grid; gap: 7px; color: #8a2b22; font-size: .84rem; font-weight: 600; }
    .profile-confirmation-fields input,
    .profile-confirmation-fields textarea { width: 100%; border: 1px solid var(--danger-line); border-radius: var(--r-sm); padding: 10px 13px; background: #fff; font: inherit; font-size: .89rem; }
    .profile-confirmation-fields input { min-height: 44px; }
    .profile-confirmation-fields .wide { grid-column: 1 / -1; }

    /* ============================ الاشتراك ============================ */

    .subscription-panel {
        display: grid;
        grid-template-columns: minmax(300px, .78fr) minmax(0, 1.6fr);
        margin-bottom: 18px;
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        overflow: hidden;
        background: var(--surface);
        box-shadow: var(--shadow-md);
        scroll-margin-top: 84px;
    }

    .subscription-overview {
        position: relative;
        padding: 30px 28px;
        overflow: hidden;
        color: #fff;
        background: linear-gradient(150deg, var(--navy-deep) 0%, var(--navy) 40%, #0d635c 100%);
    }

    .subscription-overview::after {
        content: "";
        position: absolute;
        width: 230px;
        height: 230px;
        inset-inline-end: -95px;
        bottom: -125px;
        border: 44px solid rgba(255, 255, 255, .07);
        border-radius: 50%;
    }

    .subscription-overview > * { position: relative; }
    .eyebrow { margin: 0 0 12px; font-size: .76rem; font-weight: 600; opacity: .7; letter-spacing: .02em; }
    .client-title { margin: 0; font-size: 1.5rem; line-height: 1.45; }
    /* قد يحمل رقم عميل لاتيني أو نصًا عربيًا، لذا plaintext بدل فرض LTR */
    .client-code { margin: 8px 0 22px; font-size: .82rem; opacity: .72; unicode-bidi: plaintext; text-align: right; }

    .subscription-state {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .13);
        border: 1px solid rgba(255, 255, 255, .16);
        font-weight: 600;
        font-size: .83rem;
    }

    .subscription-state::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #64f1c2; box-shadow: 0 0 0 4px rgba(100, 241, 194, .16); }
    .subscription-state.is-expired::before,
    .subscription-state.is-paused::before { background: #fca5a5; box-shadow: 0 0 0 4px rgba(252, 165, 165, .16); }
    .subscription-state.is-trial::before { background: #fcd34d; box-shadow: 0 0 0 4px rgba(252, 211, 77, .16); }

    .subscription-plan { margin: 28px 0 0; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, .15); }
    .subscription-plan small { display: block; opacity: .66; margin-bottom: 5px; font-size: .78rem; }
    .subscription-plan strong { font-size: 1.05rem; font-weight: 700; }

    .subscription-content { padding: 26px; }
    .subscription-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px; }
    .subscription-form .span-2 { grid-column: span 2; }
    .subscription-form .span-4 { grid-column: 1 / -1; }

    /* ============================ الموظفون ============================ */

    .employee-tools { display: grid; gap: 10px; margin-bottom: 14px; }

    .search-field { position: relative; }
    .search-field svg { position: absolute; inset-inline-start: 14px; top: 50%; transform: translateY(-50%); width: 17px; height: 17px; color: var(--muted); pointer-events: none; }
    .search-field .search-input { padding-inline-start: 42px; }

    .employee-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 12px;
        max-height: 680px;
        overflow: auto;
        padding: 2px;
    }

    .employee-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        padding: 15px;
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        background: #fff;
        box-shadow: var(--shadow-xs);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .employee-card:hover { border-color: var(--accent-line); box-shadow: var(--shadow-sm); }
    .employee-card.is-suspended { background: var(--warning-soft); border-color: var(--warning-line); }
    .employee-name { margin: 0; font-size: .93rem; font-weight: 700; color: var(--text); }
    /* السطر يبدأ بـ "#المعرّف" ثم بريد لاتيني، وplaintext يمنع قلبه في سياق RTL */
    .employee-meta { margin: 5px 0 0; color: var(--muted); font-size: .8rem; line-height: 1.6; word-break: break-word; unicode-bidi: plaintext; text-align: right; }

    .empty-state {
        display: none;
        padding: 34px 16px;
        color: var(--muted);
        text-align: center;
        font-size: .89rem;
        border: 1px dashed var(--border-strong);
        border-radius: var(--r-md);
        background: var(--surface-soft);
    }

    /* ============================ النسخ الاحتياطية ============================ */

    .backup-panel { margin-top: 0; }
    .backup-settings { display: grid; grid-template-columns: repeat(3, minmax(170px, 1fr)); gap: 15px; align-items: end; }
    .backup-settings > .btn { align-self: end; }
    .backup-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

    .backup-status {
        display: block;
        padding: 12px 14px;
        border-radius: var(--r-sm);
        background: var(--surface-soft);
        border: 1px solid var(--border);
        color: var(--text-soft);
        font-weight: 500;
        font-size: .85rem;
        line-height: 1.75;
    }

    .backup-status + .backup-status { margin-top: 10px; }
    .backup-status.is-ready { color: var(--success); background: var(--success-soft); border-color: var(--success-line); }
    .backup-status.is-off { color: var(--danger); background: var(--danger-soft); border-color: var(--danger-line); }
    .backup-status strong { font-weight: 700; }
    label.backup-status { display: flex; align-items: center; gap: 9px; cursor: pointer; min-height: 44px; font-weight: 600; }
    label.backup-status input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--accent); cursor: pointer; }

    .settings-disclosure { margin-top: 14px; border: 1px solid var(--border); border-radius: var(--r-md); background: #fff; overflow: hidden; }

    .settings-disclosure > summary {
        padding: 14px 18px;
        cursor: pointer;
        font-weight: 600;
        font-size: .89rem;
        color: var(--text-soft);
        background: var(--surface-soft);
        list-style: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background .15s ease;
    }

    .settings-disclosure > summary::-webkit-details-marker { display: none; }

    .settings-disclosure > summary::before {
        content: "";
        width: 7px;
        height: 7px;
        flex: 0 0 auto;
        border-inline-start: 2px solid var(--muted);
        border-bottom: 2px solid var(--muted);
        transform: rotate(45deg);
        transition: transform .18s ease;
        margin-top: -3px;
    }

    .settings-disclosure[open] > summary::before { transform: rotate(-45deg); margin-top: 3px; }
    .settings-disclosure > summary:hover { background: var(--surface-alt); }
    .settings-disclosure[open] > summary { border-bottom: 1px solid var(--border); }
    .settings-disclosure-content { padding: 20px; }
    .settings-disclosure .settings-disclosure { background: var(--surface-soft); }

    /* صندوق الأوامر */
    .terminal-command-box { margin-top: 16px; padding: 18px; border: 1px solid var(--info-line); border-radius: var(--r-md); background: var(--info-soft); }
    .terminal-command-box > p { margin: 0 0 10px; color: #17427e; line-height: 1.75; font-size: .86rem; font-weight: 600; }
    .terminal-command-box > code + p,
    .terminal-command-box > .terminal-command-actions + p,
    .terminal-command-box > .verification-note + p { margin-top: 18px; }
    .terminal-command-box > label { display: block; margin: 4px 0 7px; font-size: .82rem; font-weight: 600; color: #17427e; }
    .terminal-command-box > label:not(:first-child) { margin-top: 4px; }
    .terminal-command-box input[type="text"],
    .terminal-command-box input[type="file"] {
        width: 100%;
        min-height: 44px;
        margin-bottom: 14px;
        border: 1px solid #b8d4f5;
        border-radius: var(--r-sm);
        padding: 9px 13px;
        font: inherit;
        font-size: .86rem;
        background: #fff;
    }

    .terminal-command-box input[type="file"] { cursor: pointer; }
    .terminal-command-box input[type="file"]::file-selector-button {
        margin-inline-end: 12px;
        min-height: 30px;
        padding: 0 12px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-xs);
        background: #fff;
        color: var(--text-soft);
        font: inherit;
        font-size: .82rem;
        font-weight: 600;
        cursor: pointer;
    }

    .terminal-command {
        display: block;
        /* plaintext حتى تظهر رسالة "اختر الملف..." العربية بشكل صحيح قبل توليد الأمر */
        unicode-bidi: plaintext;
        direction: ltr;
        text-align: left;
        overflow-x: auto;
        padding: 14px 16px;
        border-radius: var(--r-sm);
        background: #0d1a2b;
        color: #cfe6df;
        font: .78rem/1.85 var(--mono);
        white-space: pre;
        border: 1px solid #1e3550;
    }

    .terminal-command-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 11px; }
    .terminal-selected-file { color: var(--muted); font-size: .81rem; font-weight: 600; }

    .verification-note { width: 100%; margin: 9px 0 0; color: var(--warning); font-size: .78rem; line-height: 1.75; }

    .restore-form, .verification-request, .verification-confirm { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .restore-form input,
    .verification-request select,
    .verification-confirm input {
        min-height: 40px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-xs);
        padding: 7px 11px;
        font: inherit;
        font-size: .85rem;
        background: #fff;
    }

    .verification-request select { max-width: 230px; }
    .verification-confirm input[type="text"] { width: 120px; direction: ltr; }
    .verification-confirm input[type="password"] { width: 190px; }
    .verification-confirm input[type="file"] { width: min(100%, 380px); direction: rtl; background: #fff; cursor: pointer; }
    .restore-form input { width: 100px; }

    .database-import-box { margin-top: 20px; padding: 20px; border: 1px solid var(--danger-line); border-radius: var(--r-md); background: linear-gradient(180deg, #fffbfa 0%, var(--danger-soft) 100%); }
    .database-import-box h3 { margin: 0 0 6px; color: var(--danger); font-size: 1rem; }
    .database-import-box > p { margin: 0 0 14px; color: var(--muted); font-size: .85rem; line-height: 1.75; }
    .database-connection-details { grid-template-columns: repeat(3, minmax(160px, 1fr)); }

    .import-progress { width: 100%; margin-top: 14px; }
    .import-progress-track { height: 12px; overflow: hidden; border-radius: 999px; background: #e2e8f0; border: 1px solid var(--border-strong); }
    .import-progress-bar { width: 0; height: 100%; background: linear-gradient(90deg, var(--accent), #22c55e); transition: width .2s ease; }
    .import-progress.is-processing .import-progress-bar { width: 100% !important; animation: import-pulse 1.2s ease-in-out infinite; }
    .import-progress-text { margin: 8px 0 0 !important; color: var(--text) !important; font-weight: 700; font-size: .84rem; }
    .import-progress-message { margin-top: 10px; padding: 11px 13px; border-radius: var(--r-sm); font-size: .85rem; font-weight: 600; }
    .import-progress-message.is-success { color: var(--success); background: var(--success-soft); border: 1px solid var(--success-line); }
    .import-progress-message.is-error { color: var(--danger); background: var(--danger-soft); border: 1px solid var(--danger-line); }
    @keyframes import-pulse { 0%, 100% { opacity: .55; } 50% { opacity: 1; } }

    /* ---------- تخطيط صفحة النسخ الاحتياطية ---------- */

    .bk { display: grid; gap: 18px; }
    .bk svg { width: 18px; height: 18px; flex: 0 0 auto; }
    .bk-alert { margin: 0; }

    .bk-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 16px 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        box-shadow: var(--shadow-sm);
    }

    .bk-toolbar h2 { font-size: 1rem; }
    .bk-toolbar p { margin-top: 3px; color: var(--muted); font-size: .84rem; }
    .bk-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .bk-toolbar-actions form { margin: 0; }
    .bk-toolbar-actions svg { width: 17px; height: 17px; }

    .bk-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }

    .bk-kpi {
        position: relative;
        display: flex;
        gap: 13px;
        align-items: flex-start;
        padding: 17px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        --tone: var(--muted);
        --tone-soft: var(--surface-alt);
        --tone-line: var(--border);
    }

    .bk-kpi.is-good { --tone: var(--success); --tone-soft: var(--success-soft); --tone-line: var(--success-line); }
    .bk-kpi.is-bad { --tone: var(--danger); --tone-soft: var(--danger-soft); --tone-line: var(--danger-line); }
    .bk-kpi::before { content: ""; position: absolute; top: 0; inset-inline: 0; height: 3px; background: var(--tone); opacity: .7; }

    .bk-kpi-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 auto;
        display: grid;
        place-items: center;
        border-radius: var(--r-sm);
        color: var(--tone);
        background: var(--tone-soft);
        border: 1px solid var(--tone-line);
    }

    .bk-kpi-icon svg { width: 21px; height: 21px; }
    .bk-kpi-body { display: grid; gap: 3px; min-width: 0; }
    .bk-kpi-label { color: var(--muted); font-size: .78rem; font-weight: 600; }
    .bk-kpi-value { font-size: 1.12rem; font-weight: 700; color: var(--text); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: right; }
    .bk-kpi-meta { color: var(--muted); font-size: .76rem; line-height: 1.6; overflow-wrap: anywhere; }
    .bk-dot { display: inline-block; width: 7px; height: 7px; margin-inline-end: 5px; border-radius: 50%; background: var(--tone); vertical-align: middle; }

    .bk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 18px; align-items: start; }
    .bk-main, .bk-side { display: grid; gap: 18px; min-width: 0; }
    .bk .panel + .panel { margin-top: 0; }

    .bk-head-title { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
    .bk-head-icon { width: 36px; height: 36px; flex: 0 0 auto; display: grid; place-items: center; border-radius: var(--r-sm); color: var(--accent-dark); background: var(--accent-soft); border: 1px solid var(--accent-line); }
    .bk-count { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; height: 22px; margin-inline-start: 6px; padding: 0 7px; border-radius: 999px; background: var(--navy-soft); color: var(--navy); font-size: .74rem; font-weight: 700; vertical-align: middle; }

    /* جدول النسخ */
    .bk-table-wrap { overflow-x: auto; }
    .bk-table { width: 100%; min-width: 640px; border-collapse: collapse; }
    .bk-table th, .bk-table td { padding: 13px 18px; text-align: right; vertical-align: middle; border-bottom: 1px solid var(--border); font-size: .855rem; }
    .bk-table thead th { color: var(--muted); font-size: .76rem; font-weight: 700; background: var(--surface-soft); white-space: nowrap; }
    .bk-table tbody tr:last-child td { border-bottom: 0; }
    .bk-table tbody tr:hover { background: #fafcfd; }
    .bk-table tbody tr.is-selected { background: var(--accent-soft); }
    .bk-col-actions { width: 1%; white-space: nowrap; }
    .bk-file { direction: ltr; text-align: left; font-family: var(--mono); font-size: .79rem; color: var(--text); overflow-wrap: anywhere; margin-bottom: 6px; }
    .bk-sub { color: var(--muted); font-size: .76rem; }

    .bk-tag { display: inline-flex; align-items: center; min-height: 22px; padding: 0 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; color: var(--text-soft); background: var(--surface-alt); border: 1px solid var(--border); white-space: nowrap; }
    .bk-tag.is-warning { color: var(--warning); background: var(--warning-soft); border-color: var(--warning-line); }
    .bk-tag.is-info { color: var(--info); background: var(--info-soft); border-color: var(--info-line); }
    .bk-tag.is-navy { color: var(--navy); background: var(--navy-soft); border-color: #cddcec; }
    .bk-tag.is-danger { color: var(--danger); background: #fff; border-color: var(--danger-line); }

    .bk-row-actions { display: flex; gap: 6px; align-items: flex-start; justify-content: flex-end; }
    .bk-row-actions .bk-protected { min-height: 34px; }
    .bk-row-actions .btn svg { width: 15px; height: 15px; }
    .bk-protected { display: inline-flex; align-items: center; gap: 5px; color: var(--muted); font-size: .78rem; font-weight: 600; }
    .bk-protected svg { width: 14px; height: 14px; }

    /* قائمة منسدلة للإجراءات الحساسة داخل الصف */
    .bk-menu { position: relative; }
    .bk-menu > summary { list-style: none; }
    .bk-menu > summary::-webkit-details-marker { display: none; }
    .bk-menu[open] > summary { background: var(--surface-alt); }

    /* داخل الصف وليس طافيًا: حاوية الجدول القابلة للتمرير كانت ستقصّه */
    .bk-menu-body {
        margin-top: 8px;
        width: 290px;
        display: grid;
        gap: 9px;
        padding: 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        box-shadow: var(--shadow-lg);
        white-space: normal;
    }

    .bk-menu-body label { color: var(--text-soft); font-size: .78rem; font-weight: 600; }
    .bk-menu-body select,
    .bk-menu-body input { width: 100%; min-height: 38px; padding: 6px 10px; border: 1px solid var(--border-strong); border-radius: var(--r-xs); font: inherit; font-size: .84rem; background: #fff; }
    .bk-menu-actions { display: flex; gap: 6px; flex-wrap: wrap; }
    .bk-menu-actions .btn { flex: 1 1 auto; }

    /* صف تأكيد رمز التحقق */
    .bk-table tr.bk-challenge-row td { padding: 0 18px 16px; background: var(--accent-soft); }
    .bk-challenge { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 14px; border-radius: var(--r-md); background: #fff; border: 1px solid var(--accent-line); }
    .bk-challenge.is-danger { border-color: var(--danger-line); }
    .bk-challenge-text { display: grid; gap: 2px; flex: 1 1 240px; }
    .bk-challenge-text strong { font-size: .88rem; }
    .bk-challenge.is-danger .bk-challenge-text strong { color: var(--danger); }
    .bk-challenge-text span { color: var(--muted); font-size: .78rem; }
    .bk-challenge input { width: 130px; min-height: 38px; padding: 6px 10px; border: 1px solid var(--border-strong); border-radius: var(--r-xs); font: inherit; font-size: .85rem; direction: ltr; }

    .bk-empty { display: grid; justify-items: center; gap: 6px; padding: 30px 16px; text-align: center; color: var(--muted); font-size: .85rem; border: 1px dashed var(--border-strong); border-radius: var(--r-md); background: var(--surface-soft); }
    .bk-empty svg { width: 30px; height: 30px; color: #9fb1c2; }
    .bk-empty strong { color: var(--text-soft); font-size: .92rem; }
    .bk-empty .btn { margin-top: 6px; }

    /* الإعدادات الجانبية */
    .bk-side .panel-head { padding: 16px 18px; }
    .bk-side .panel-body { padding: 18px; }
    .bk-form { display: grid; gap: 14px; }
    .bk-form > .btn { justify-self: start; }
    .bk-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }

    .bk-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--r-sm); background: var(--surface-soft); cursor: pointer; }
    .bk-toggle strong { display: block; font-size: .86rem; color: var(--text); }
    .bk-toggle small { display: block; color: var(--muted); font-size: .76rem; }

    .bk-note { color: var(--muted); font-size: .78rem; line-height: 1.75; }

    .bk-collapse > summary { list-style: none; cursor: pointer; border-bottom: 0; flex-wrap: nowrap; }
    .bk-collapse > summary::-webkit-details-marker { display: none; }
    .bk-collapse > summary::after { content: ""; width: 8px; height: 8px; flex: 0 0 auto; margin-inline-start: auto; border-inline-start: 2px solid var(--muted); border-bottom: 2px solid var(--muted); transform: rotate(-45deg); transition: transform .18s ease; }
    .bk-collapse[open] > summary { border-bottom: 1px solid var(--border); }
    .bk-collapse[open] > summary::after { transform: rotate(135deg); }
    .bk-collapse > summary:hover { background: var(--surface-alt); }

    .bk-dl { margin: 0; padding: 6px 18px 12px; }
    .bk-dl > div { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed var(--border); }
    .bk-dl > div:last-child { border-bottom: 0; }
    .bk-dl dt { color: var(--muted); font-size: .78rem; font-weight: 600; }
    .bk-dl dd { margin: 0; direction: ltr; font-family: var(--mono); font-size: .78rem; color: var(--text); text-align: left; overflow-wrap: anywhere; }

    /* منطقة الاستيراد الخطرة */
    .bk-danger-zone { border-color: var(--danger-line); }
    .bk-danger-zone > .panel-head { background: linear-gradient(180deg, #fffbfa 0%, var(--danger-soft) 100%); border-bottom-color: var(--danger-line); }
    .bk-danger-zone .bk-head-icon { color: var(--danger); background: #fff; border-color: var(--danger-line); }
    .bk-danger-zone .panel-title { color: var(--danger); }

    .bk-steps { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.1fr) minmax(0, 1.1fr); gap: 14px; }
    .bk-step { display: flex; gap: 12px; padding: 16px; border: 1px solid var(--border); border-radius: var(--r-md); background: var(--surface-soft); min-width: 0; }
    .bk-step-num { width: 28px; height: 28px; flex: 0 0 auto; display: grid; place-items: center; border-radius: 50%; background: var(--navy); color: #fff; font-size: .82rem; font-weight: 700; }
    .bk-step-body { display: grid; gap: 10px; align-content: start; min-width: 0; flex: 1; }
    .bk-step-body h3 { font-size: .9rem; margin-top: 2px; }
    .bk-step-body .terminal-command { max-height: 190px; overflow: auto; }
    .bk-step-body .terminal-command-actions { margin-top: 0; }
    .bk-step-body input[type="file"] { padding: 7px 10px; cursor: pointer; }

    .bk-disclosure-danger { border-color: var(--danger-line); }
    .bk-disclosure-danger > summary { color: var(--danger); background: var(--danger-soft); }
    .bk-disclosure-danger .settings-disclosure-content { display: grid; gap: 10px; }
    .bk-disclosure-danger .terminal-command-actions { margin-top: 0; }
    .bk-danger-text { color: #991b1b; font-size: .86rem; font-weight: 600; }

    /* ============================ المهام المجدولة ============================ */

    .scheduler-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }

    .scheduler-stat {
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        background: var(--surface-soft);
        color: var(--muted);
        font-size: .84rem;
        font-weight: 600;
    }

    .scheduler-stat strong { display: block; margin-top: 7px; font-size: 1rem; font-weight: 700; }
    .scheduler-stat.is-active { border-color: var(--success-line); background: var(--success-soft); color: var(--success); }
    .scheduler-stat.is-inactive { border-color: var(--danger-line); background: var(--danger-soft); color: var(--danger); }

    .cron-expression { direction: ltr; text-align: left; font-family: var(--mono); font-size: .8rem; white-space: nowrap; font-weight: 600; }
    .cron-command { direction: ltr; text-align: left; font-family: var(--mono); font-size: .74rem; overflow-wrap: anywhere; color: var(--text-soft); }
    /* جدول المهام فيه 7 أعمدة؛ بدون عرض أدنى ينضغط عمود الأمر لحرف واحد ويطيل الصف بلا داعٍ */
    .scheduler-table { min-width: 1150px; }
    .scheduler-table th, .scheduler-table td { padding: 12px; font-size: .82rem; }
    .scheduler-table .cron-command { min-width: 185px; }
    .cron-entry { margin-top: 10px; direction: ltr; text-align: left; padding: 12px 14px; border-radius: var(--r-sm); background: #0d1a2b; color: #cfe6df; overflow-wrap: anywhere; font-family: var(--mono); font-size: .76rem; line-height: 1.8; border: 1px solid #1e3550; }
    .job-explanation { min-width: 170px; line-height: 1.7; }
    .job-effect { min-width: 190px; line-height: 1.7; }
    .job-safety { min-width: 180px; line-height: 1.7; }

    /* ============================ ما الجديد ============================ */


    /* ============================ الاستعادة (recovery) ============================ */

    .sa-recovery .danger-zone { border-color: var(--danger-line); }
    .sa-recovery .danger-zone .panel-head { background: var(--danger-soft); border-bottom-color: var(--danger-line); }
    .sa-recovery .danger-zone .panel-head h2 { color: var(--danger); }
    .sa-recovery .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
    .sa-recovery label { display: grid; gap: 7px; color: var(--text-soft); font-size: .84rem; font-weight: 600; }
    .sa-recovery input[type="text"],
    .sa-recovery input[type="password"] {
        width: 100%;
        min-height: 44px;
        border: 1px solid var(--border-strong);
        border-radius: var(--r-sm);
        padding: 9px 13px;
        font: inherit;
        font-size: .89rem;
        background: #fff;
        box-shadow: var(--shadow-xs);
    }

    .sa-recovery input:focus { outline: none; border-color: var(--accent-line); box-shadow: 0 0 0 4px var(--ring); }
    .sa-recovery .help { margin: 12px 0; color: var(--muted); font-size: .82rem; line-height: 1.75; }
    .sa-recovery .table-wrap th,
    .sa-recovery .table-wrap td { white-space: nowrap; }
    .sa-recovery .panel-body.table-wrap { padding: 0; margin-top: 0; border: 0; border-radius: 0; }
    .command-output { margin-top: 14px; direction: ltr; text-align: left; max-height: 320px; overflow: auto; padding: 16px; border-radius: var(--r-md); background: #0d1a2b; color: #a7f3d0; white-space: pre-wrap; font: .78rem/1.8 var(--mono); border: 1px solid #1e3550; }

    /* ============================ الدخول (login) ============================ */

    body.sa-login { display: grid; place-items: center; padding: 24px; }
    .login-shell { width: min(470px, 100%); }
    .login-brand { margin-bottom: 18px; display: flex; align-items: center; gap: 14px; justify-content: center; }
    .login-brand h1 { margin: 0; font-size: 1.2rem; }
    .login-brand p { margin: 4px 0 0; color: var(--muted); font-size: .86rem; }

    .login-panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        box-shadow: var(--shadow-lg);
        padding: 30px;
    }

    .login-panel .field { margin-bottom: 18px; }
    .login-panel .field > span { display: block; margin-bottom: 7px; font-size: .84rem; font-weight: 600; color: var(--text-soft); }
    .login-panel .field input { min-height: 48px; }
    .login-panel .error, .login-panel .success { margin-bottom: 18px; }

    .login-submit {
        width: 100%;
        min-height: 50px;
        border: 0;
        border-radius: var(--r-sm);
        background: linear-gradient(180deg, #12857b 0%, var(--accent) 100%);
        color: #fff;
        font: inherit;
        font-weight: 700;
        font-size: .95rem;
        cursor: pointer;
        box-shadow: 0 6px 16px rgba(15, 118, 110, .28);
        transition: background .15s ease, transform .12s ease;
    }

    .login-submit:hover { background: var(--accent-dark); }
    .login-submit:active { transform: translateY(1px); }
    .login-submit:focus-visible { outline: none; box-shadow: 0 0 0 4px var(--ring); }

    .login-note { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); color: var(--muted); font-size: .83rem; line-height: 1.8; text-align: center; }

    /* ============================ الاستجابة ============================ */

    @media (max-width: 1250px) {
        .module-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 1080px) {
        .dev-shell, .shell { width: calc(100% - 28px); }
        .layout { grid-template-columns: 1fr; }
        .maintenance-control { grid-template-columns: 1fr; }
        .maintenance-control form { grid-template-columns: 1fr; }
        .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .subscription-panel { grid-template-columns: 1fr; }
        .subscription-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .subscription-form .span-2, .subscription-form .span-4 { grid-column: 1 / -1; }
        .database-connection-details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .scheduler-summary { grid-template-columns: 1fr; }
        .status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .bk-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .bk-layout, .bk-steps { grid-template-columns: 1fr; }
    }

    @media (max-width: 760px) {
        body { font-size: 14.5px; }
        .dev-shell, .shell { width: calc(100% - 20px); margin: 14px auto 32px; }
        .topbar { grid-template-columns: 1fr; }
        .topbar-actions, .actions { flex-direction: column; align-items: stretch; }
        .topbar-actions .btn, .topbar-actions form, .actions .btn, .actions form, .save-row .btn { width: 100%; }
        .page-heading { flex-direction: column; align-items: flex-start; padding: 20px; }
        .page-heading-meta { justify-content: flex-start; }
        .panel-head { flex-direction: column; align-items: stretch; }
        .panel-body, .subscription-content, .subscription-overview { padding: 18px; }
        .stats, .module-grid, .dashboard-options, .status-grid,
        .backup-settings, .subscription-form,
        .profile-confirmation-fields, .database-connection-details,
        .sa-recovery .form-grid, .employee-list { grid-template-columns: 1fr; }
        .subscription-form .span-2, .subscription-form .span-4,
        .profile-confirmation-fields .wide { grid-column: auto; }
        .employee-card { grid-template-columns: 1fr; }
        .employee-card form, .employee-card .btn { width: 100%; }
        .verification-confirm input[type="text"], .verification-confirm input[type="password"] { width: 100%; }
        .bk-kpis, .bk-form-grid { grid-template-columns: 1fr; }
        .bk-toolbar-actions, .bk-toolbar-actions form, .bk-toolbar-actions .btn { width: 100%; }
        .bk-challenge input { width: 100%; }
        .bk-menu-body { width: min(290px, calc(100vw - 48px)); }
        .login-panel { padding: 22px; }
    }

    @media print {
        body { background: #fff; }
        .section-nav, .topbar-actions, .btn { display: none !important; }
        .panel { box-shadow: none; break-inside: avoid; }
    }
</style>
