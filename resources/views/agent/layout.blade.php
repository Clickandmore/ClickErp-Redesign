<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', __('agent.page_title_default'))</title>
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/luno-style.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/custom.css">
    <link rel="stylesheet" href="{{ asset('admin/login') }}/css/fontawesome.min.css">
    <style>
        :root {
            --agent-bg: #f5f7fb;
            --agent-panel: #ffffff;
            --agent-ink: #1f2937;
            --agent-muted: #6b7280;
            --agent-line: #d9e0ea;
            --agent-primary: #0f766e;
            --agent-primary-dark: #115e59;
            --agent-accent: #2563eb;
            --agent-sidebar: #172033;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--agent-ink);
            background: var(--agent-bg);
            font-family: "Baloo Bhaijaan 2", Tahoma, Arial, sans-serif;
            letter-spacing: 0;
        }

        .agent-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            grid-template-areas: "sidebar main";
        }

        .agent-sidebar {
            grid-area: sidebar;
        }

        .agent-main {
            grid-area: main;
        }

        .agent-sidebar {
            background: var(--agent-sidebar);
            color: #fff;
            padding: 22px 18px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .agent-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, .16);
            margin-bottom: 18px;
        }

        .agent-brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--agent-primary);
            flex: 0 0 38px;
        }

        .agent-brand h1 {
            font-size: 16px;
            margin: 0;
            color: #fff;
        }

        .agent-brand small {
            color: #cbd5e1;
            display: block;
            margin-top: 3px;
        }

        .agent-nav {
            display: grid;
            gap: 7px;
        }

        .agent-nav a,
        .agent-logout {
            min-height: 42px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            color: #dbeafe;
            text-decoration: none;
            border: 0;
            background: transparent;
            width: 100%;
            text-align: inherit;
            font: inherit;
        }

        .agent-nav a.active,
        .agent-nav a:hover,
        .agent-logout:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .agent-logout {
            margin-top: 12px;
            cursor: pointer;
        }

        .agent-main {
            min-width: 0;
        }

        .agent-admin-header {
            min-height: 76px;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid #e8edf4;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 24px;
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(10px);
        }

        .agent-header-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 210px;
        }

        .agent-header-logo {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #eef2f7;
            box-shadow: 0 6px 18px rgba(15, 23, 42, .08);
        }

        .agent-header-brand strong {
            display: block;
            font-size: 16px;
            color: #111827;
        }

        .agent-header-brand span {
            display: block;
            margin-top: 2px;
            font-size: 12px;
            color: var(--agent-muted);
        }

        .agent-header-meta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex: 1;
        }

        .agent-meta-card {
            min-height: 46px;
            border: 1px solid #e8edf4;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 0 14px;
            background: #f8fafc;
            color: #111827;
            white-space: nowrap;
        }

        .agent-meta-card i {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--agent-accent);
        }

        .agent-header-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            min-width: 220px;
        }

        .agent-header-action,
        .agent-lang-button {
            min-height: 44px;
            border: 1px solid #e1e8f2;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            background: #fff;
            color: #1f2937;
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .05);
            cursor: pointer;
            font: inherit;
        }

        .agent-header-action:hover,
        .agent-lang-button:hover {
            color: var(--agent-primary-dark);
            border-color: #cbd5e1;
            text-decoration: none;
        }

        .agent-open-site {
            background: var(--agent-primary);
            color: #fff;
            border-color: var(--agent-primary);
        }

        .agent-open-site:hover {
            background: var(--agent-primary-dark);
            color: #fff;
            border-color: var(--agent-primary-dark);
        }

        .agent-language {
            position: relative;
        }

        .agent-language-menu {
            position: absolute;
            top: calc(100% + 4px);
            right: 0;
            width: 190px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .16);
            padding: 6px;
            display: none;
            z-index: 30;
        }

        .agent-language-menu a {
            min-height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 10px;
            color: #1f2937;
            text-decoration: none;
        }

        .agent-language-menu a:hover,
        .agent-language-menu a.active {
            background: #edf5ff;
            color: var(--agent-accent);
        }

        .agent-page {
            padding: 24px;
        }

        .agent-topbar {
            min-height: 64px;
            background: var(--agent-panel);
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .agent-topbar h2 {
            font-size: 20px;
            margin: 0;
        }

        .agent-user {
            color: var(--agent-muted);
            font-size: 13px;
            margin-top: 4px;
        }

        .agent-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .agent-card,
        .agent-section {
            background: var(--agent-panel);
            border: 1px solid var(--agent-line);
            border-radius: 8px;
        }

        .agent-card {
            padding: 16px;
            min-height: 104px;
        }

        .agent-card span {
            color: var(--agent-muted);
            font-size: 13px;
        }

        .agent-card strong {
            display: block;
            font-size: 28px;
            margin-top: 8px;
            color: var(--agent-primary-dark);
        }

        .agent-section {
            padding: 18px;
            margin-bottom: 18px;
        }

        .agent-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .agent-section-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .agent-button {
            min-height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0 12px;
            color: #fff;
            background: var(--agent-primary);
            text-decoration: none;
            border: 0;
        }

        .agent-button:hover {
            color: #fff;
            background: var(--agent-primary-dark);
        }

        .agent-filter {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) 180px auto;
            gap: 10px;
            margin-bottom: 14px;
        }

        .agent-filter input,
        .agent-filter select {
            min-height: 40px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            padding: 0 12px;
            background: #fff;
        }

        .agent-maids-filter {
            grid-template-columns: minmax(180px, 1fr) auto;
        }

        .agent-worker-tabs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .agent-worker-tab {
            min-height: 74px;
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            background: #f8fafc;
            color: var(--agent-ink);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px;
            text-decoration: none;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .agent-worker-tab:hover,
        .agent-worker-tab.active {
            color: var(--agent-primary-dark);
            border-color: rgba(15, 118, 110, .42);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
            text-decoration: none;
        }

        .agent-worker-tab span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: inherit;
            font-weight: 700;
        }

        .agent-worker-tab strong {
            min-width: 42px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: var(--agent-primary-dark);
            font-size: 16px;
        }

        .agent-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .agent-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .agent-table th,
        .agent-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--agent-line);
            vertical-align: middle;
            text-align: start;
        }

        .agent-table th {
            color: var(--agent-muted);
            font-weight: 700;
            font-size: 13px;
            background: #f8fafc;
        }

        .agent-badge {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            border-radius: 8px;
            padding: 0 10px;
            background: #e0f2fe;
            color: #075985;
            font-size: 12px;
        }

        .agent-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .agent-empty {
            color: var(--agent-muted);
            margin: 0;
        }

        .agent-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .agent-detail-item {
            border: 1px solid var(--agent-line);
            border-radius: 8px;
            padding: 12px;
            background: #f8fafc;
        }

        .agent-detail-item span {
            display: block;
            color: var(--agent-muted);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .agent-detail-item strong {
            display: block;
            overflow-wrap: anywhere;
        }

        .agent-pagination {
            margin-top: 14px;
        }

        @media (max-width: 1100px) {
            .agent-header-meta {
                display: none;
            }
        }

        @media (max-width: 992px) {
            .agent-shell {
                grid-template-columns: 1fr;
            }

            .agent-sidebar {
                position: static;
                height: auto;
            }

            .agent-admin-header {
                position: static;
            }

            .agent-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .agent-admin-header {
                align-items: stretch;
                flex-direction: column;
            }

            .agent-header-brand,
            .agent-header-actions {
                min-width: 0;
                width: 100%;
            }

            .agent-header-actions {
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 640px) {
            .agent-page {
                padding: 14px;
            }

            .agent-topbar,
            .agent-section-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .agent-grid,
            .agent-detail-grid,
            .agent-filter {
                grid-template-columns: 1fr;
            }

            .agent-worker-tabs {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @stack('style')
    @if(app()->getLocale() === 'ar')
        <style>
            /* RTL grid: sidebar goes to the RIGHT */
            .agent-shell {
                grid-template-columns: minmax(0, 1fr) 260px;
                grid-template-areas: "main sidebar";
            }

            /* Force RTL direction on all content areas */
            .agent-main,
            .agent-page,
            .agent-admin-header,
            .agent-topbar,
            .agent-card,
            .agent-section,
            .agent-detail-item,
            .agent-section-header,
            .agent-filter,
            .agent-nav {
                direction: rtl;
            }

            /* Text alignment for content */
            .agent-card span,
            .agent-card strong,
            .agent-topbar h2,
            .agent-topbar .agent-user,
            .agent-section-header h3,
            .agent-detail-item span,
            .agent-detail-item strong,
            .agent-empty {
                text-align: right;
            }

            /* Table cells RTL */
            .agent-table th,
            .agent-table td {
                text-align: right;
            }

            /* Language menu position for RTL */
            .agent-language-menu {
                right: auto;
                left: 0;
            }
        </style>
    @endif
</head>

<body>
    @php
        $agentUser = auth('agent')->user();
        $agentOfficeNames = $agentUser
            ? $agentUser->agentOffices()->get()->pluck('title')->filter()->implode(', ')
            : '';
        $headerLogo =
            isset($settings) && !empty($settings->admin_logo)
            ? showFile($settings->admin_logo)
            : (isset($settings) && !empty($settings->logo_header)
                ? showFile($settings->logo_header)
                : asset('admin/assets/img/company_logo.jpeg'));
        $languageUrl = request()->fullUrlWithoutQuery('agent_locale');
        $englishUrl = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL('en', $languageUrl, [], true);
        $arabicUrl = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL('ar', $languageUrl, [], true);
    @endphp

    <div class="agent-shell">
        <aside class="agent-sidebar">
            <div class="agent-brand">
                <span class="agent-brand-mark"><i class="fa fa-building"></i></span>
                <div>
                    <h1>{{ __('agent.portal') }}</h1>
                    <small>{{ $agentOfficeNames ?: __('agent.external_office') }}</small>
                </div>
            </div>

            <nav class="agent-nav">
                <a href="{{ route('agent.dashboard') }}"
                    class="{{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                    <i class="fa fa-chart-line"></i>
                    <span>{{ __('agent.dashboard') }}</span>
                </a>
                <a href="{{ route('agent.maids.index') }}"
                    class="{{ request()->routeIs('agent.maids.*') ? 'active' : '' }}">
                    <i class="fa fa-id-card"></i>
                    <span>{{ __('agent.cvs') }}</span>
                </a>
                <a href="{{ route('agent.contracts.index') }}"
                    class="{{ request()->routeIs('agent.contracts.*') ? 'active' : '' }}">
                    <i class="fa fa-file-signature"></i>
                    <span>{{ __('agent.contracts') }}</span>
                </a>
                <a href="{{ route('agent.philippines-tracking.index') }}"
                    class="{{ request()->routeIs('agent.philippines-tracking.*') ? 'active' : '' }}">
                    <i class="fa fa-clipboard-list"></i>
                    <span>{{ __('agent.procedures_tracking') }}</span>
                </a>
                <a href="{{ route('agent.dispatches.index') }}"
                    class="{{ request()->routeIs('agent.dispatches.*') ? 'active' : '' }}">
                    <i class="fa fa-truck-fast"></i>
                    <span>{{ __('agent.dispatches') }}</span>
                </a>
                <a href="{{ route('agent.invoices.index') }}"
                    class="{{ request()->routeIs('agent.invoices.*') ? 'active' : '' }}">
                    <i class="fa fa-file-invoice-dollar"></i>
                    <span>{{ __('agent.invoices') }}</span>
                </a>

                <form action="{{ route('agent.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="agent-logout">
                        <i class="fa fa-arrow-right-from-bracket"></i>
                        <span>{{ __('agent.logout') }}</span>
                    </button>
                </form>
            </nav>
        </aside>

        <main class="agent-main">
            <header class="agent-admin-header">
                <a href="{{ route('agent.dashboard') }}" class="agent-header-brand">
                    <img class="agent-header-logo" src="{{ $headerLogo }}" alt="logo"
                        onerror="this.onerror=null;this.src='{{ asset('admin/assets/img/company_logo.jpeg') }}';">
                    <span>
                        <strong>{{ __('agent.dashboard_title') }}</strong>
                        <span>{{ __('agent.external_workspace') }}</span>
                    </span>
                </a>

                <div class="agent-header-meta" aria-label="Date and time">
                    <div class="agent-meta-card">
                        <i class="fa fa-clock-o"></i>
                        <strong id="agent-live-time">--:--</strong>
                    </div>
                    <div class="agent-meta-card">
                        <i class="fa fa-calendar"></i>
                        <strong id="agent-live-date">----</strong>
                    </div>
                </div>

                <div class="agent-header-actions">
                    @php
                        $unreadNotificationsCount = auth('agent')->check() ? \App\Models\AdminNotification::where('admin_id', auth('agent')->id())->whereNull('read_at')->count() : 0;
                    @endphp
                    <a href="{{ route('agent.notifications.index') }}" class="agent-header-action"
                        style="position: relative;">
                        <i class="fa fa-bell"></i>
                        @if($unreadNotificationsCount > 0)
                            <span
                                style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 5px; font-size: 10px; font-weight: bold; line-height: 1;">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>

                    <a class="agent-header-action agent-open-site" href="{{ publicSiteUrl() }}" target="_blank"
                        rel="noopener noreferrer">
                        <i class="fa fa-globe"></i>
                        <span>{{ __('agent.website') }}</span>
                    </a>

                    <div class="agent-language">
                        <button type="button" class="agent-lang-button" aria-haspopup="true">
                            <i class="fa fa-language"></i>
                            <span>{{ app()->getLocale() === 'ar' ? __('agent.arabic') : __('agent.english') }}</span>
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <div class="agent-language-menu">
                            <a href="{{ $englishUrl }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">
                                <span class="flag-icon flag-icon-us"></span>
                                <span>{{ __('agent.us_english') }}</span>
                            </a>
                            <a href="{{ $arabicUrl }}" class="{{ app()->getLocale() === 'ar' ? 'active' : '' }}">
                                <span class="flag-icon flag-icon-sa"></span>
                                <span>{{ __('agent.arabic_language') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="agent-page">
                <header class="agent-topbar">
                    <div>
                        <h2>@yield('page-title', __('agent.page_title_default'))</h2>
                        <div class="agent-user">{{ $agentUser?->name }}
                            {{ $agentUser?->email ? ' - ' . $agentUser->email : '' }}</div>
                    </div>
                    @yield('page-action')
                </header>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        (function () {
            // Language dropdown toggle
            const langBtn = document.querySelector('.agent-lang-button');
            const langMenu = document.querySelector('.agent-language-menu');
            if (langBtn && langMenu) {
                langBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const isOpen = langMenu.style.display === 'block';
                    langMenu.style.display = isOpen ? 'none' : 'block';
                });
                document.addEventListener('click', function () {
                    langMenu.style.display = 'none';
                });
            }
        })();

        (function () {
            const timeElement = document.getElementById('agent-live-time');
            const dateElement = document.getElementById('agent-live-date');
            const dateFormatter = new Intl.DateTimeFormat('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            const timeFormatter = new Intl.DateTimeFormat('en-GB', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            function updateClock() {
                const now = new Date();
                if (timeElement) {
                    timeElement.textContent = timeFormatter.format(now);
                }
                if (dateElement) {
                    dateElement.textContent = dateFormatter.format(now);
                }
            }

            updateClock();
            setInterval(updateClock, 1000);
        })();
    </script>
    @stack('scripts')
</body>

</html>
