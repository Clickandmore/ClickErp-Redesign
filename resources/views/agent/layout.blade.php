<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'لوحة الوكيل الخارجي')</title>
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/luno-style.css">
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
            --agent-accent: #b45309;
        }

        body {
            margin: 0;
            color: var(--agent-ink);
            background: var(--agent-bg);
            font-family: Tahoma, Arial, sans-serif;
            letter-spacing: 0;
        }

        .agent-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .agent-sidebar {
            background: #172033;
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

        @media (max-width: 992px) {
            .agent-shell {
                grid-template-columns: 1fr;
            }

            .agent-sidebar {
                position: static;
                height: auto;
            }

            .agent-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .agent-main {
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
        }
    </style>
    @stack('style')
</head>

<body dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @php
        $agentUser = auth('agent')->user();
        $agentOfficeNames = $agentUser
            ? $agentUser->agentOffices()->get()->pluck('title')->filter()->implode('، ')
            : '';
    @endphp

    <div class="agent-shell">
        <aside class="agent-sidebar">
            <div class="agent-brand">
                <span class="agent-brand-mark"><i class="fa fa-building"></i></span>
                <div>
                    <h1>لوحة الوكيل</h1>
                    <small>{{ $agentOfficeNames ?: 'المكتب الخارجي' }}</small>
                </div>
            </div>

            <nav class="agent-nav">
                <a href="{{ route('agent.dashboard') }}" class="{{ request()->routeIs('agent.dashboard') ? 'active' : '' }}">
                    <i class="fa fa-chart-line"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="{{ route('agent.maids.index') }}" class="{{ request()->routeIs('agent.maids.*') ? 'active' : '' }}">
                    <i class="fa fa-id-card"></i>
                    <span>السير الذاتية</span>
                </a>
                <a href="{{ route('agent.contracts.index') }}" class="{{ request()->routeIs('agent.contracts.*') ? 'active' : '' }}">
                    <i class="fa fa-file-signature"></i>
                    <span>العقود</span>
                </a>

                <form action="{{ route('agent.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="agent-logout">
                        <i class="fa fa-arrow-right-from-bracket"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </nav>
        </aside>

        <main class="agent-main">
            <header class="agent-topbar">
                <div>
                    <h2>@yield('page-title', 'لوحة الوكيل الخارجي')</h2>
                    <div class="agent-user">{{ $agentUser?->name }} {{ $agentUser?->email ? ' - ' . $agentUser->email : '' }}</div>
                </div>
                @yield('page-action')
            </header>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>

</html>
