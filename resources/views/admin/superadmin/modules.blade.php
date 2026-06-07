<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة Super Admin التطويرية</title>
    <style>
        :root {
            --bg: #eef3f5;
            --surface: #ffffff;
            --surface-soft: #f8fbfc;
            --border: #d7e1e7;
            --border-strong: #c4d1da;
            --text: #172033;
            --muted: #64748b;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --accent-soft: #e5f5f2;
            --danger: #b91c1c;
            --danger-soft: #fef2f2;
            --warning: #a16207;
            --warning-soft: #fffbeb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Tahoma, Arial, sans-serif;
        }

        .dev-shell {
            width: min(1240px, calc(100% - 32px));
            margin: 24px auto;
        }

        .topbar {
            min-height: 74px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            padding: 16px 18px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 14px 35px rgba(23, 32, 51, .06);
            margin-bottom: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--accent);
            color: #fff;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .brand-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 900;
            color: #0f172a;
        }

        .brand-text {
            margin: 5px 0 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: .9rem;
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            min-height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid var(--border-strong);
            background: #fff;
            color: var(--text);
            font: inherit;
            font-weight: 900;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--accent-dark);
        }

        .btn-danger {
            border-color: #f2c3c3;
            color: var(--danger);
        }

        .alerts {
            display: grid;
            gap: 10px;
            margin-bottom: 16px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            line-height: 1.7;
        }

        .alert-danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }

        .stat-label {
            color: var(--muted);
            font-size: .82rem;
            font-weight: 800;
        }

        .stat-value {
            margin-top: 8px;
            font-size: 1.45rem;
            font-weight: 900;
            color: #0f172a;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 420px;
            gap: 16px;
            align-items: start;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(23, 32, 51, .05);
            overflow: hidden;
        }

        .panel-head {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-soft);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .panel-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 900;
            color: #0f172a;
        }

        .panel-subtitle {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .88rem;
            line-height: 1.7;
        }

        .panel-body {
            padding: 16px;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .module-card {
            min-height: 154px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            display: grid;
            gap: 14px;
            align-content: space-between;
        }

        .module-card.is-off {
            background: var(--danger-soft);
            border-color: #fecaca;
        }

        .module-card.is-on {
            background: #ffffff;
        }

        .module-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .module-name {
            margin: 0;
            font-size: 1rem;
            font-weight: 900;
            color: #0f172a;
        }

        .module-key {
            margin-top: 7px;
            display: inline-flex;
            min-height: 24px;
            align-items: center;
            padding: 0 8px;
            border-radius: 8px;
            background: #eef3f5;
            color: #526174;
            font-size: .76rem;
            direction: ltr;
        }

        .module-description {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: .88rem;
        }

        .status-badge {
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            padding: 0 10px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .status-on {
            background: var(--accent-soft);
            color: var(--accent-dark);
        }

        .status-off {
            background: #fee2e2;
            color: var(--danger);
        }

        .switch-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-top: 1px solid var(--border);
            padding-top: 12px;
            color: #334155;
            font-weight: 900;
        }

        .limit-row {
            display: grid;
            gap: 7px;
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        .limit-row label {
            color: #334155;
            font-weight: 900;
            font-size: .84rem;
        }

        .limit-input {
            width: 100%;
            min-height: 38px;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            padding: 0 12px;
            font: inherit;
            background: #fff;
        }

        .limit-help {
            margin: 0;
            color: var(--muted);
            font-size: .78rem;
            line-height: 1.6;
        }

        .switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex: 0 0 auto;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: #cbd5e1;
            border-radius: 999px;
            transition: .18s ease;
        }

        .slider:before {
            content: "";
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .22);
            transition: .18s ease;
        }

        .switch input:checked + .slider {
            background: var(--accent);
        }

        .switch input:checked + .slider:before {
            transform: translateX(22px);
        }

        .save-row {
            margin-top: 14px;
            display: flex;
            justify-content: flex-end;
        }

        .dashboard-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .dashboard-option {
            position: relative;
            display: grid;
            gap: 8px;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
        }

        .dashboard-option input {
            position: absolute;
            inset-inline-start: 14px;
            top: 16px;
        }

        .dashboard-option-title {
            padding-inline-start: 28px;
            font-weight: 900;
            color: #0f172a;
        }

        .dashboard-option-desc {
            padding-inline-start: 28px;
            color: var(--muted);
            line-height: 1.7;
            font-size: .86rem;
        }

        .dashboard-option-url {
            justify-self: start;
            direction: ltr;
            display: inline-flex;
            min-height: 24px;
            align-items: center;
            padding: 0 8px;
            border-radius: 8px;
            background: #eef3f5;
            color: #526174;
            font-size: .76rem;
        }

        .dashboard-option:has(input:checked) {
            border-color: #58aaa3;
            background: var(--accent-soft);
        }

        .employee-tools {
            display: grid;
            gap: 10px;
            margin-bottom: 12px;
        }

        .search-input {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            padding: 0 12px;
            font: inherit;
            background: #fff;
        }

        .search-input:focus {
            outline: none;
            border-color: #58aaa3;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .10);
        }

        .employee-list {
            display: grid;
            gap: 10px;
            max-height: 676px;
            overflow: auto;
            padding-inline-end: 4px;
        }

        .employee-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
        }

        .employee-card.is-suspended {
            background: var(--warning-soft);
            border-color: #fde68a;
        }

        .employee-name {
            margin: 0;
            font-size: .95rem;
            font-weight: 900;
            color: #0f172a;
        }

        .employee-meta {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.65;
            word-break: break-word;
        }

        .employee-role {
            display: inline-flex;
            min-height: 24px;
            align-items: center;
            margin-top: 8px;
            padding: 0 8px;
            border-radius: 8px;
            background: #eef3f5;
            color: #526174;
            font-size: .76rem;
            font-weight: 800;
        }

        .empty-state {
            display: none;
            padding: 24px 12px;
            color: var(--muted);
            text-align: center;
            border: 1px dashed var(--border-strong);
            border-radius: 12px;
        }

        @media (max-width: 1050px) {
            .layout,
            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .dev-shell {
                width: min(100% - 20px, 1240px);
                margin: 12px auto;
            }

            .topbar,
            .stats,
            .module-grid,
            .dashboard-options {
                grid-template-columns: 1fr;
            }

            .topbar-actions,
            .panel-head {
                align-items: stretch;
                flex-direction: column;
            }

            .topbar-actions .btn,
            .topbar-actions form,
            .save-row .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
@php
    $modules = $modules ?? [];
    $enabledMap = $enabledMap ?? [];
    $dashboards = $dashboards ?? [];
    $defaultDashboardKey = $defaultDashboardKey ?? 'recruitment';
    $branchLimit = $branchLimit ?? null;
    $admins = $admins ?? collect();
    $enabledCount = collect($enabledMap)->filter()->count();
    $disabledCount = max(count($modules) - $enabledCount, 0);
    $activeAdminsCount = $admins->filter(fn ($admin) => ($admin->status ?? 'active') !== 'suspended')->count();
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">لوحة Super Admin التطويرية</h1>
                <p class="brand-text">تحكم مستقل في الموديلات مع إمكانية الدخول كأي موظف للفحص التقني.</p>
            </div>
        </div>

        <div class="topbar-actions">
            <a class="btn" href="{{ route('dashboard.index') }}">لوحة الإدارة</a>
            <form method="POST" action="{{ route('dev-superadmin.logout') }}">
                @csrf
                <button class="btn btn-danger" type="submit">خروج</button>
            </form>
        </div>
    </header>

    @if (session('success') || (isset($errors) && $errors->any()))
        <div class="alerts">
            @if (session('success'))
                <div class="alert">{{ session('success') }}</div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
        </div>
    @endif

    <section class="stats" aria-label="ملخص اللوحة">
        <div class="stat">
            <div class="stat-label">كل الموديلات</div>
            <div class="stat-value">{{ count($modules) }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">المفعلة</div>
            <div class="stat-value">{{ $enabledCount }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">المغلقة</div>
            <div class="stat-value">{{ $disabledCount }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">الموظفون النشطون</div>
            <div class="stat-value">{{ $activeAdminsCount }}</div>
        </div>
    </section>

    <div class="layout">
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">إدارة الموديلات</h2>
                    <p class="panel-subtitle">إغلاق أي موديل يخفيه من القائمة ويمنع الوصول لمساراته داخل لوحة الإدارة.</p>
                </div>
            </div>

            <div class="panel-body">
                <form method="POST" action="{{ route('dev-superadmin.modules.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="dashboard-options">
                        @foreach ($dashboards as $dashboardKey => $dashboard)
                            <label class="dashboard-option">
                                <input type="radio" name="default_dashboard" value="{{ $dashboardKey }}" {{ $defaultDashboardKey === $dashboardKey ? 'checked' : '' }}>
                                <span class="dashboard-option-title">{{ $dashboard['name'] ?? $dashboardKey }}</span>
                                <span class="dashboard-option-desc">{{ $dashboard['description'] ?? '' }}</span>
                                <span class="dashboard-option-url">{{ $dashboard['url'] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="module-grid">
                        @foreach ($modules as $key => $module)
                            @php
                                $isEnabled = $enabledMap[$key] ?? true;
                            @endphp
                            <article class="module-card {{ $isEnabled ? 'is-on' : 'is-off' }}">
                                <div>
                                    <div class="module-top">
                                        <div>
                                            <h3 class="module-name">{{ $module['name'] ?? $key }}</h3>
                                            <span class="module-key">{{ $key }}</span>
                                        </div>
                                        <span class="status-badge {{ $isEnabled ? 'status-on' : 'status-off' }}">
                                            {{ $isEnabled ? 'مفعل' : 'مغلق' }}
                                        </span>
                                    </div>
                                    <p class="module-description">{{ $module['description'] ?? '' }}</p>
                                </div>

                                <div class="switch-row">
                                    <span>حالة الموديل</span>
                                    <label class="switch" title="تفعيل أو إغلاق {{ $module['name'] ?? $key }}">
                                        <input type="checkbox" name="modules[{{ $key }}]" value="1" {{ $isEnabled ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                @if ($key === 'branches')
                                    <div class="limit-row">
                                        <label for="branchesLimit">عدد الفروع المسموح به</label>
                                        <input
                                            id="branchesLimit"
                                            class="limit-input"
                                            type="number"
                                            name="branches_limit"
                                            min="0"
                                            step="1"
                                            value="{{ old('branches_limit', $branchLimit) }}"
                                            placeholder="غير محدود"
                                        >
                                        <p class="limit-help">اترك الحقل فارغًا للسماح بعدد غير محدود من الفروع.</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="save-row">
                        <button class="btn btn-primary" type="submit">حفظ تغييرات الموديلات</button>
                    </div>
                </form>
            </div>
        </section>

        <aside class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">الدخول كموظف</h2>
                    <p class="panel-subtitle">ابحث بالاسم أو البريد أو الهاتف ثم افتح جلسة الإدارة بهذا الموظف.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="employee-tools">
                    <input class="search-input" type="search" id="employeeSearch" placeholder="بحث عن موظف..." autocomplete="off">
                </div>

                <div class="employee-list" id="employeeList">
                    @foreach ($admins as $admin)
                        <div class="employee-card {{ $admin->dev_is_suspended ? 'is-suspended' : '' }}" data-search="{{ e(mb_strtolower($admin->dev_search_text)) }}">
                            <div>
                                <h3 class="employee-name">{{ $admin->name ?: 'بدون اسم' }}</h3>
                                <p class="employee-meta">#{{ $admin->id }} - {{ $admin->dev_contact }}</p>
                                <span class="employee-role">{{ $admin->dev_role_name }}{{ $admin->dev_is_suspended ? ' - موقوف' : '' }}</span>
                            </div>
                            <form method="POST" action="{{ route('dev-superadmin.impersonate', $admin->id) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit" {{ $admin->dev_is_suspended ? 'disabled' : '' }}>دخول</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="empty-state" id="employeeEmpty">لا توجد نتائج مطابقة للبحث.</div>
            </div>
        </aside>
    </div>
</main>

<script>
    const searchInput = document.getElementById('employeeSearch');
    const employeeCards = Array.from(document.querySelectorAll('.employee-card'));
    const emptyState = document.getElementById('employeeEmpty');

    searchInput?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        let visibleCount = 0;

        employeeCards.forEach((card) => {
            const isVisible = !query || card.dataset.search.includes(query);
            card.style.display = isVisible ? 'grid' : 'none';
            if (isVisible) {
                visibleCount++;
            }
        });

        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    });
</script>
</body>
</html>
