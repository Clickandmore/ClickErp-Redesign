<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة Super Admin التطويرية</title>
    <style>
        :root {
            --bg: #f3f6f8;
            --surface: #ffffff;
            --surface-soft: #f8fafb;
            --border: #dfe7eb;
            --border-strong: #c4d1da;
            --text: #152431;
            --muted: #64748b;
            --accent: #087f72;
            --accent-dark: #06665d;
            --accent-soft: #e6f7f3;
            --navy: #16324f;
            --navy-soft: #edf3f8;
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
            width: min(1320px, calc(100% - 32px));
            margin: 20px auto 40px;
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
            border-radius: 18px;
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
            background: linear-gradient(145deg, var(--navy), var(--accent));
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

        .section-nav {
            position: sticky;
            top: 10px;
            z-index: 20;
            display: flex;
            gap: 8px;
            padding: 8px;
            margin-bottom: 16px;
            overflow-x: auto;
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(23, 50, 79, .07);
            backdrop-filter: blur(12px);
        }

        .section-nav a {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0 13px;
            border-radius: 9px;
            color: #526174;
            text-decoration: none;
            font-size: .84rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .section-nav a.active,
        .section-nav a:hover {
            color: var(--accent-dark);
            background: var(--accent-soft);
        }

        .nav-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; opacity: .65; }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: 0 0 16px;
            padding: 18px 20px;
            background: var(--navy);
            color: #fff;
            border-radius: 16px;
        }

        .page-heading h2 { margin: 0; font-size: 1.1rem; }
        .page-heading p { margin: 5px 0 0; color: rgba(255,255,255,.72); font-size: .84rem; }
        .layout.is-single { grid-template-columns: minmax(0, 1fr); }

        .subscription-panel {
            display: grid;
            grid-template-columns: minmax(300px, .8fr) minmax(0, 1.55fr);
            margin-bottom: 16px;
            border: 1px solid #cfe1e3;
            border-radius: 18px;
            overflow: hidden;
            background: var(--surface);
            box-shadow: 0 16px 36px rgba(23, 50, 79, .07);
            scroll-margin-top: 72px;
        }

        .subscription-overview {
            position: relative;
            padding: 26px;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(145deg, #16324f 0%, #0b625e 100%);
        }

        .subscription-overview::after {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            inset-inline-end: -90px;
            bottom: -120px;
            border: 42px solid rgba(255,255,255,.07);
            border-radius: 50%;
        }

        .eyebrow { margin: 0 0 12px; font-size: .78rem; font-weight: 900; opacity: .72; }
        .client-title { margin: 0; font-size: 1.6rem; line-height: 1.45; }
        .client-code { margin: 8px 0 22px; font-size: .84rem; opacity: .75; direction: ltr; text-align: right; }
        .subscription-state { display: inline-flex; align-items: center; gap: 8px; min-height: 34px; padding: 0 12px; border-radius: 999px; background: rgba(255,255,255,.14); font-weight: 900; font-size: .84rem; }
        .subscription-state::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #64f1c2; box-shadow: 0 0 0 4px rgba(100,241,194,.14); }
        .subscription-state.is-expired::before, .subscription-state.is-paused::before { background: #fca5a5; box-shadow: 0 0 0 4px rgba(252,165,165,.14); }
        .subscription-plan { margin: 26px 0 0; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.15); }
        .subscription-plan small { display: block; opacity: .68; margin-bottom: 6px; }
        .subscription-plan strong { font-size: 1.05rem; }

        .subscription-content { padding: 22px; }
        .section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .section-heading h2 { margin: 0; font-size: 1.08rem; }
        .section-heading p { margin: 6px 0 0; color: var(--muted); font-size: .85rem; line-height: 1.7; }

        .subscription-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; }
        .subscription-form .span-2 { grid-column: span 2; }
        .subscription-form .span-4 { grid-column: 1 / -1; }
        .field select, .field textarea {
            width: 100%;
            border: 1px solid var(--border-strong);
            border-radius: 9px;
            padding: 8px 10px;
            font: inherit;
            background: #fff;
        }
        .field select { min-height: 42px; }
        .field textarea { min-height: 68px; resize: vertical; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: #58aaa3; box-shadow: 0 0 0 4px rgba(15,118,110,.09); }
        .form-actions { display: flex; justify-content: flex-end; align-items: center; }

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

        .alert-warning {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
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
            border-radius: 14px;
            padding: 16px;
            border-top: 3px solid var(--accent);
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

        .backup-panel {
            margin-top: 16px;
        }

        .backup-settings {
            display: grid;
            grid-template-columns: repeat(3, minmax(150px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: .82rem;
            font-weight: 900;
            color: var(--muted);
        }

        .field input {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--border-strong);
            border-radius: 9px;
            padding: 8px 10px;
            font: inherit;
            background: #fff;
        }

        .backup-status {
            padding: 11px 12px;
            border-radius: 9px;
            background: var(--surface-soft);
            border: 1px solid var(--border);
            font-weight: 800;
            font-size: .84rem;
        }

        .backup-status.is-ready { color: #166534; background: #f0fdf4; border-color: #bbf7d0; }
        .backup-status.is-off { color: var(--danger); background: var(--danger-soft); border-color: #fecaca; }

        .backup-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .backup-table-wrap { overflow-x: auto; margin-top: 18px; }
        .backup-table { width: 100%; border-collapse: collapse; min-width: 760px; }
        .backup-table th, .backup-table td { text-align: right; padding: 11px; border-bottom: 1px solid var(--border); }
        .backup-table th { color: var(--muted); font-size: .8rem; background: var(--surface-soft); }
        .backup-table td { font-size: .86rem; }
        .backup-file { direction: ltr; text-align: left; font-family: monospace; }
        .restore-form { display: flex; gap: 6px; align-items: center; }
        .restore-form input { width: 94px; min-height: 38px; border: 1px solid var(--border-strong); border-radius: 8px; padding: 6px; }
        .verification-request, .verification-confirm { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .verification-request select, .verification-confirm input { min-height: 38px; border: 1px solid var(--border-strong); border-radius: 8px; padding: 6px 8px; font: inherit; background: #fff; }
        .verification-request select { max-width: 190px; }
        .verification-confirm input { width: 105px; direction: ltr; }
        .verification-note { width: 100%; margin: 0; color: var(--warning); font-size: .76rem; line-height: 1.6; }
        .btn-small { min-height: 36px; padding: 0 10px; font-size: .78rem; }

        .scheduler-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .scheduler-stat {
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface-soft);
        }

        .scheduler-stat strong { display: block; margin-top: 7px; font-size: 1rem; }
        .scheduler-stat.is-active { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
        .scheduler-stat.is-inactive { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .cron-expression { direction: ltr; text-align: left; font-family: monospace; white-space: nowrap; }
        .cron-command { direction: ltr; text-align: left; font-family: monospace; font-size: .75rem; max-width: 420px; overflow-wrap: anywhere; }
        .cron-entry { direction: ltr; text-align: left; padding: 10px; border-radius: 8px; background: #0f172a; color: #e2e8f0; overflow-wrap: anywhere; font-family: monospace; font-size: .76rem; }
        .risk-badge { display: inline-flex; padding: 5px 9px; border-radius: 999px; font-size: .75rem; font-weight: 900; margin-bottom: 6px; }
        .risk-low { color: #166534; background: #dcfce7; }
        .risk-medium { color: #92400e; background: #fef3c7; }
        .risk-high, .risk-invalid { color: #991b1b; background: #fee2e2; }
        .risk-unknown { color: #475569; background: #e2e8f0; }
        .job-explanation { min-width: 220px; line-height: 1.7; }
        .job-effect { min-width: 250px; line-height: 1.7; }
        .job-safety { min-width: 230px; line-height: 1.7; }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(23, 32, 51, .05);
            overflow: hidden;
            scroll-margin-top: 72px;
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

            .subscription-panel { grid-template-columns: 1fr; }
            .subscription-form { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .subscription-form .span-4 { grid-column: 1 / -1; }
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

            .backup-settings { grid-template-columns: 1fr; }
            .scheduler-summary { grid-template-columns: 1fr; }
            .subscription-form { grid-template-columns: 1fr; }
            .subscription-form .span-2, .subscription-form .span-4 { grid-column: auto; }
            .subscription-overview, .subscription-content { padding: 19px; }
        }
    </style>
</head>
<body>
@php
    $modules = $modules ?? [];
    $enabledMap = $enabledMap ?? [];
    $configuredMap = $configuredMap ?? $enabledMap;
    $productProfiles = $productProfiles ?? [];
    $productProfileKey = $productProfileKey ?? 'recruitment';
    $dashboards = $dashboards ?? [];
    $defaultDashboardKey = $defaultDashboardKey ?? 'recruitment';
    $branchLimit = $branchLimit ?? null;
    $admins = $admins ?? collect();
    $enabledCount = collect($enabledMap)->filter()->count();
    $disabledCount = max(count($modules) - $enabledCount, 0);
    $activeAdminsCount = $admins->filter(fn ($admin) => ($admin->status ?? 'active') !== 'suspended')->count();
    $backupSettings = $backupSettings ?? ['enabled' => true, 'daily_time' => '03:30', 'retention_days' => 30, 'local_enabled' => true, 'local_retention_days' => 7, 'path' => 'database-backups'];
    $databaseBackups = $databaseBackups ?? [];
    $localDatabaseBackups = $localDatabaseBackups ?? [];
    $backupListError = $backupListError ?? null;
    $r2Ready = $r2Ready ?? false;
    $schedulerStatus = $schedulerStatus ?? ['active' => false, 'last_heartbeat_at' => null, 'crontab_configured' => null, 'crontab_entries' => [], 'crontab_error' => null, 'recommended_entry' => '', 'jobs' => []];
    $cronJobs = collect($schedulerStatus['jobs']);
    $highRiskCronCount = $cronJobs->where('risk', 'high')->count();
    $invalidCronCount = $cronJobs->where('risk', 'invalid')->count();
    $subscription = $subscription ?? [];
    $activeSection = $activeSection ?? 'modules';
    $productUpdates = $productUpdates ?? \App\Support\ProductUpdatesSettings::all();
    $editableProductUpdates = old('updates', $productUpdates['updates'] ?? []);
    if (empty($editableProductUpdates)) {
        $editableProductUpdates = [['title' => '', 'details' => '', 'url' => '', 'link_label' => 'عرض الميزة']];
    }
    $subscriptionStatuses = [
        'active' => 'اشتراك نشط',
        'trial' => 'فترة تجريبية',
        'paused' => 'الاشتراك موقوف',
        'expired' => 'الاشتراك منتهي',
    ];
    $subscriptionStatus = ($subscription['is_expired'] ?? false) ? 'expired' : ($subscription['status'] ?? 'active');
    $sectionMeta = [
        'subscription' => ['title' => 'العميل والاشتراك', 'description' => 'إدارة بيانات المنشأة والباقة وتواريخ الاشتراك.'],
        'modules' => ['title' => 'الموديلات', 'description' => 'تفعيل مكونات النظام واختيار لوحة التحكم الافتراضية.'],
        'employees' => ['title' => 'الموظفون', 'description' => 'البحث عن موظف والدخول إلى لوحة الإدارة بحسابه.'],
        'product-updates' => ['title' => 'ما الجديد', 'description' => 'إدارة بطاقات التحديثات وروابطها وترتيب ظهورها للعملاء.'],
        'backups' => ['title' => 'النسخ الاحتياطية', 'description' => 'إدارة النسخ التلقائية والتنزيل والاستعادة.'],
        'scheduler' => ['title' => 'المهام المجدولة', 'description' => 'متابعة حالة Scheduler والمهام الآلية القادمة.'],
    ][$activeSection] ?? ['title' => 'لوحة التطوير', 'description' => 'إدارة إعدادات النظام.'];
@endphp

<main class="dev-shell">
    <header class="topbar">
        <div class="brand">
            <div class="brand-mark">SA</div>
            <div>
                <h1 class="brand-title">إدارة حساب العميل</h1>
                <p class="brand-text">الاشتراك، صلاحيات النظام، المستخدمون، والبنية التقنية في مكان واحد.</p>
            </div>
        </div>

        <div class="topbar-actions">
            <a class="btn" href="{{ \App\Support\SystemModuleManager::defaultDashboardUrl() }}">لوحة الإدارة</a>
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

    <nav class="section-nav" aria-label="أقسام لوحة التحكم">
        <a class="{{ $activeSection === 'subscription' ? 'active' : '' }}" href="{{ route('dev-superadmin.subscription') }}"><span class="nav-dot"></span>العميل والاشتراك</a>
        <a class="{{ $activeSection === 'modules' ? 'active' : '' }}" href="{{ route('dev-superadmin.modules') }}"><span class="nav-dot"></span>الموديلات</a>
        <a class="{{ $activeSection === 'employees' ? 'active' : '' }}" href="{{ route('dev-superadmin.employees') }}"><span class="nav-dot"></span>الموظفون</a>
        <a class="{{ $activeSection === 'product-updates' ? 'active' : '' }}" href="{{ route('dev-superadmin.product-updates') }}"><span class="nav-dot"></span>ما الجديد</a>
        <a class="{{ $activeSection === 'backups' ? 'active' : '' }}" href="{{ route('dev-superadmin.database-backups') }}"><span class="nav-dot"></span>النسخ الاحتياطية</a>
        <a class="{{ $activeSection === 'scheduler' ? 'active' : '' }}" href="{{ route('dev-superadmin.scheduler') }}"><span class="nav-dot"></span>المهام المجدولة</a>
    </nav>

    <div class="page-heading">
        <div>
            <h2>{{ $sectionMeta['title'] }}</h2>
            <p>{{ $sectionMeta['description'] }}</p>
        </div>
    </div>

    @if ($activeSection === 'subscription')
    <section class="subscription-panel" id="subscription">
        <div class="subscription-overview">
            <p class="eyebrow">ملف العميل</p>
            <h2 class="client-title">{{ $subscription['client_name'] ?: config('app.name') }}</h2>
            <p class="client-code">{{ $subscription['client_code'] ? 'ID: ' . $subscription['client_code'] : 'لم يحدد رقم العميل' }}</p>
            <span class="subscription-state is-{{ $subscriptionStatus }}">
                {{ $subscriptionStatuses[$subscriptionStatus] ?? 'غير محدد' }}
            </span>
            <div class="subscription-plan">
                <small>الباقة الحالية</small>
                <strong>{{ $subscription['plan_name'] ?? 'الخطة الأساسية' }}</strong>
            </div>
        </div>

        <div class="subscription-content">
            <div class="section-heading">
                <div>
                    <h2>بيانات الاشتراك وبدء التشغيل</h2>
                    <p>هذه البيانات مرجع سريع لفريق الدعم ولا تؤثر تلقائيًا على إتاحة النظام.</p>
                </div>
            </div>

            <form class="subscription-form" method="POST" action="{{ route('dev-superadmin.subscription.update') }}">
                @csrf
                @method('PUT')
                <div class="field span-2">
                    <label for="clientName">اسم العميل / المنشأة</label>
                    <input id="clientName" name="client_name" type="text" required value="{{ old('client_name', $subscription['client_name'] ?? '') }}" placeholder="اسم المنشأة">
                </div>
                <div class="field">
                    <label for="clientCode">رقم العميل</label>
                    <input id="clientCode" name="client_code" type="text" value="{{ old('client_code', $subscription['client_code'] ?? '') }}" placeholder="CL-001" dir="ltr">
                </div>
                <div class="field">
                    <label for="subscriptionStatus">حالة الاشتراك</label>
                    <select id="subscriptionStatus" name="status" required>
                        @foreach ($subscriptionStatuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ old('status', $subscription['status'] ?? 'active') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="planName">اسم الباقة</label>
                    <input id="planName" name="plan_name" type="text" required value="{{ old('plan_name', $subscription['plan_name'] ?? '') }}" placeholder="الخطة الأساسية">
                </div>
                <div class="field">
                    <label for="workStartedAt">بداية عمل العميل بالبرنامج</label>
                    <input id="workStartedAt" name="work_started_at" type="date" value="{{ old('work_started_at', $subscription['work_started_at'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="subscriptionStartedAt">بداية الاشتراك</label>
                    <input id="subscriptionStartedAt" name="subscription_started_at" type="date" value="{{ old('subscription_started_at', $subscription['subscription_started_at'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="subscriptionEndsAt">نهاية الاشتراك</label>
                    <input id="subscriptionEndsAt" name="subscription_ends_at" type="date" value="{{ old('subscription_ends_at', $subscription['subscription_ends_at'] ?? '') }}">
                </div>
                <div class="field span-4">
                    <label for="subscriptionNotes">ملاحظات العميل</label>
                    <textarea id="subscriptionNotes" name="notes" placeholder="أي تفاصيل مهمة للتجديد أو الدعم الفني...">{{ old('notes', $subscription['notes'] ?? '') }}</textarea>
                </div>
                <div class="form-actions span-4">
                    <button class="btn btn-primary" type="submit">حفظ بيانات الاشتراك</button>
                </div>
            </form>
        </div>
    </section>

    <section class="stats" aria-label="ملخص اللوحة">
        <div class="stat">
            <div class="stat-label">مدة استخدام البرنامج</div>
            <div class="stat-value">{{ isset($subscription['days_using_system']) && $subscription['days_using_system'] !== null ? number_format($subscription['days_using_system']) . ' يوم' : '—' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">المتبقي في الاشتراك</div>
            <div class="stat-value">
                @if (($subscription['days_remaining'] ?? null) === null) —
                @elseif ($subscription['days_remaining'] < 0) منتهي
                @else {{ number_format($subscription['days_remaining']) }} يوم
                @endif
            </div>
        </div>
        <div class="stat">
            <div class="stat-label">الموديلات المفعلة</div>
            <div class="stat-value">{{ $enabledCount }} <small style="font-size:.8rem;color:var(--muted)">من {{ count($modules) }}</small></div>
        </div>
        <div class="stat">
            <div class="stat-label">الموظفون النشطون</div>
            <div class="stat-value">{{ $activeAdminsCount }}</div>
        </div>
    </section>
    @endif

    @if ($activeSection === 'product-updates')
    <section class="panel" id="product-updates" style="margin-bottom:24px;">
        <style>
            .product-updates-builder { display:grid; gap:14px; }
            .product-update-editor { border:1px solid #dfe7ef; border-radius:14px; padding:16px; background:#f8fafc; }
            .product-update-editor-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
            .product-update-order { display:flex; gap:6px; }
            .product-update-order button { width:34px; height:32px; border:1px solid #d7e0e8; border-radius:8px; background:#fff; cursor:pointer; }
            .product-update-order button:disabled { opacity:.3; cursor:not-allowed; }
            .product-update-fields { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
            .product-update-fields .wide { grid-column:1 / -1; }
            .product-update-remove { width:auto !important; padding:0 8px; border:0 !important; background:transparent !important; color:#dc3545; cursor:pointer; font-weight:700; }
            .product-updates-add { justify-self:start; border:1px dashed #0f6b6e; background:#effafa; color:#0f6b6e; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer; }
            @media (max-width: 760px) { .product-update-fields { grid-template-columns:1fr; } .product-update-fields .wide { grid-column:auto; } }
        </style>
        <div class="panel-head">
            <div>
                <h2 class="panel-title">ما الجديد في كليك</h2>
                <p class="panel-subtitle">أنشئ بطاقات مرتبة لكل ميزة، وأضف رابط الصفحة التي تريد توجيه العميل إليها.</p>
            </div>
        </div>
        <div class="panel-body">
            <form class="subscription-form" method="POST" action="{{ route('dev-superadmin.product-updates.update') }}">
                @csrf
                @method('PUT')
                <div class="field span-2">
                    <label for="productUpdateTitle">عنوان نافذة التحديثات</label>
                    <input id="productUpdateTitle" name="title" type="text" required value="{{ old('title', $productUpdates['title'] ?? '') }}" placeholder="جديد في كليك">
                </div>
                <div class="field">
                    <label for="productUpdateDate">تاريخ النشر</label>
                    <input id="productUpdateDate" name="published_at" type="date" value="{{ old('published_at', $productUpdates['published_at'] ?? '') }}">
                </div>
                <div class="field">
                    <label for="productUpdateEnabled">حالة الظهور</label>
                    <label class="switch-row" for="productUpdateEnabled" style="min-height:46px;margin:0;">
                        <span>إظهار في الهيدر</span>
                        <span class="switch">
                            <input name="is_enabled" type="hidden" value="0">
                            <input id="productUpdateEnabled" name="is_enabled" type="checkbox" value="1" {{ old('is_enabled', $productUpdates['is_enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </span>
                    </label>
                </div>
                <div class="field span-4">
                    <label for="productUpdateSummary">وصف مختصر</label>
                    <input id="productUpdateSummary" name="summary" type="text" value="{{ old('summary', $productUpdates['summary'] ?? '') }}" placeholder="تحسينات جديدة أصبحت متاحة الآن">
                </div>
                <div class="field span-4">
                    <label>بطاقات التحديث وترتيبها</label>
                    <div id="productUpdatesBuilder" class="product-updates-builder">
                        @foreach ($editableProductUpdates as $index => $update)
                            <article class="product-update-editor" data-update-row>
                                <div class="product-update-editor-head">
                                    <strong data-update-number>التحديث {{ $loop->iteration }}</strong>
                                    <div class="product-update-order">
                                        <button type="button" data-move-update="up" title="تحريك لأعلى" aria-label="تحريك لأعلى">↑</button>
                                        <button type="button" data-move-update="down" title="تحريك لأسفل" aria-label="تحريك لأسفل">↓</button>
                                        <button type="button" class="product-update-remove" data-remove-update title="حذف">حذف</button>
                                    </div>
                                </div>
                                <div class="product-update-fields">
                                    <div class="field wide">
                                        <label>عنوان الميزة</label>
                                        <input data-update-field="title" name="updates[{{ $index }}][title]" type="text" required maxlength="160" value="{{ $update['title'] ?? '' }}" placeholder="مثال: إنشاء إشعار دائن من الفاتورة">
                                    </div>
                                    <div class="field wide">
                                        <label>تفاصيل مختصرة</label>
                                        <textarea data-update-field="details" name="updates[{{ $index }}][details]" rows="2" maxlength="1000" placeholder="اشرح الفائدة للعميل باختصار">{{ $update['details'] ?? '' }}</textarea>
                                    </div>
                                    <div class="field">
                                        <label>رابط الصفحة</label>
                                        <input data-update-field="url" name="updates[{{ $index }}][url]" type="text" maxlength="1000" dir="ltr" value="{{ $update['url'] ?? '' }}" placeholder="/dashboard/invoices">
                                    </div>
                                    <div class="field">
                                        <label>نص زر الانتقال</label>
                                        <input data-update-field="link_label" name="updates[{{ $index }}][link_label]" type="text" maxlength="80" value="{{ $update['link_label'] ?? '' }}" placeholder="الانتقال إلى الفواتير">
                                    </div>
                                </div>
                            </article>
                        @endforeach
                        <button id="addProductUpdate" class="product-updates-add" type="button"><i class="fa fa-plus"></i> إضافة تحديث آخر</button>
                    </div>
                </div>
                <div class="form-actions span-4">
                    <button class="btn btn-primary" type="submit">حفظ ونشر التحديث</button>
                </div>
            </form>
        </div>
    </section>
    @endif

    @if (in_array($activeSection, ['modules', 'employees'], true))
    <div class="layout is-single">
        @if ($activeSection === 'modules')
        <section class="panel" id="modules">
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

                    <div class="panel-head" style="margin:-16px -16px 16px;border:0;border-bottom:1px solid var(--border)">
                        <div>
                            <h3 class="panel-title">نوع البرنامج</h3>
                            <p class="panel-subtitle">نظام الاستقدام هو الوضع الافتراضي الحالي. تغيير النوع لا يحذف أي بيانات أو إعدادات.</p>
                        </div>
                    </div>

                    <div class="dashboard-options">
                        @foreach ($productProfiles as $profileKey => $profile)
                            <label class="dashboard-option">
                                <input type="radio" name="product_profile" value="{{ $profileKey }}" {{ old('product_profile', $productProfileKey) === $profileKey ? 'checked' : '' }} required>
                                <span class="dashboard-option-title">{{ $profile['name'] ?? $profileKey }}</span>
                                <span class="dashboard-option-desc">{{ $profile['description'] ?? '' }}</span>
                            </label>
                        @endforeach
                    </div>

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
                                $isConfigured = $configuredMap[$key] ?? true;
                                $isAvailable = \App\Support\SystemModuleManager::isAvailableForProfile($key, $productProfileKey);
                                $isRequired = \App\Support\SystemModuleManager::isRequiredForProfile($key, $productProfileKey);
                                $isSwitchChecked = $isRequired || $isConfigured;
                            @endphp
                            <article class="module-card {{ $isEnabled ? 'is-on' : 'is-off' }}">
                                <div>
                                    <div class="module-top">
                                        <div>
                                            <h3 class="module-name">{{ $module['name'] ?? $key }}</h3>
                                            <span class="module-key">{{ $key }}</span>
                                        </div>
                                        <span class="status-badge {{ $isEnabled ? 'status-on' : 'status-off' }}">
                                            {{ !$isAvailable ? 'غير متاح لهذا النوع' : ($isRequired ? 'أساسي لهذا النوع' : ($isEnabled ? 'مفعل' : 'مغلق')) }}
                                        </span>
                                    </div>
                                    <p class="module-description">{{ $module['description'] ?? '' }}</p>
                                </div>

                                <div class="switch-row">
                                    <span>حالة الموديل</span>
                                    <label class="switch" title="تفعيل أو إغلاق {{ $module['name'] ?? $key }}">
                                        @if ($isRequired || (!$isAvailable && $isConfigured))
                                            <input type="hidden" name="modules[{{ $key }}]" value="1">
                                        @endif
                                        <input type="checkbox" name="modules[{{ $key }}]" value="1" {{ $isSwitchChecked ? 'checked' : '' }} {{ (!$isAvailable || $isRequired) ? 'disabled' : '' }}>
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
        @endif

        @if ($activeSection === 'employees')
        <aside class="panel" id="employees">
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
        @endif
    </div>
    @endif

    @if ($activeSection === 'backups')
    <section class="panel backup-panel" id="backups">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">نسخ قاعدة البيانات إلى R2 والسيرفر</h2>
                <p class="panel-subtitle">نسخة MySQL مضغوطة يوميًا؛ 30 يومًا في R2 و7 أيام على السيرفر. التنزيل يحفظ ملف القاعدة على جهازك مباشرة.</p>
            </div>
            <div class="backup-actions">
                <form method="POST" action="{{ route('dev-superadmin.database-backups.download-current') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit" {{ $r2Ready ? '' : 'disabled' }}>تنزيل قاعدة البيانات الآن</button>
                </form>
                <form method="POST" action="{{ route('dev-superadmin.database-backups.create') }}">
                    @csrf
                    <button class="btn" type="submit" {{ $r2Ready ? '' : 'disabled' }}>إنشاء نسخة دون تنزيل</button>
                </form>
            </div>
        </div>
        <div class="panel-body">
            <div class="section-heading">
                <div>
                    <h2>أمان Super Admin</h2>
                    <p>تحكم بجهات استلام رموز العمليات الخطرة وكلمة مرور الدخول. تُحفظ القيم في ملف خاص مشفّر ومستقل عن قاعدة البيانات.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('dev-superadmin.database-backups.security') }}" class="backup-settings" autocomplete="off">
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="superAdminVerificationEmail">بريد التحقق</label>
                    <input id="superAdminVerificationEmail" type="email" name="verification_email" maxlength="190" value="{{ old('verification_email', $devSuperAdminSecurity['email']) }}" placeholder="security@example.com" autocomplete="email">
                </div>
                <div class="field">
                    <label for="superAdminVerificationPhone">جوال التحقق</label>
                    <input id="superAdminVerificationPhone" type="tel" name="verification_phone" value="{{ old('verification_phone', $devSuperAdminSecurity['phone']) }}" placeholder="05xxxxxxxx" dir="ltr" autocomplete="tel">
                </div>
                <div class="field">
                    <label for="superAdminCurrentPassword">كلمة المرور الحالية <span class="required">*</span></label>
                    <input id="superAdminCurrentPassword" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="superAdminNewPassword">كلمة مرور جديدة (اختياري)</label>
                    <input id="superAdminNewPassword" type="password" name="password" minlength="12" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="superAdminNewPasswordConfirmation">تأكيد كلمة المرور الجديدة</label>
                    <input id="superAdminNewPasswordConfirmation" type="password" name="password_confirmation" minlength="12" autocomplete="new-password">
                </div>
                <button class="btn btn-primary" type="submit" onclick="return confirm('سيتم تحديث إعدادات أمان Super Admin. هل تريد المتابعة؟')">حفظ إعدادات الأمان</button>
            </form>
            <p class="backup-status">
                يجب إبقاء بريد أو جوال واحد على الأقل. كلمة المرور الجديدة لا تقل عن 12 حرفًا وتحتوي حرفًا كبيرًا وصغيرًا ورقمًا ورمزًا خاصًا. عند تغييرها سيتم تسجيل خروجك تلقائيًا.
            </p>

            <hr style="border:0;border-top:1px solid var(--border);margin:22px 0;">

            <form method="POST" action="{{ route('dev-superadmin.database-backups.settings') }}" class="backup-settings">
                @csrf
                @method('PUT')
                <div class="field">
                    <label>الجدولة اليومية</label>
                    <label class="backup-status {{ $backupSettings['enabled'] ? 'is-ready' : '' }}">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" {{ old('enabled', $backupSettings['enabled']) ? 'checked' : '' }}>
                        تفعيل النسخ التلقائي
                    </label>
                </div>
                <div class="field">
                    <label>نسخة السيرفر الخاصة</label>
                    <label class="backup-status {{ $backupSettings['local_enabled'] ? 'is-ready' : '' }}">
                        <input type="hidden" name="local_enabled" value="0">
                        <input type="checkbox" name="local_enabled" value="1" {{ old('local_enabled', $backupSettings['local_enabled']) ? 'checked' : '' }}>
                        الاحتفاظ بنسخة على السيرفر
                    </label>
                </div>
                <div class="field">
                    <label for="backupDailyTime">وقت التنفيذ</label>
                    <input id="backupDailyTime" type="time" name="daily_time" required value="{{ old('daily_time', $backupSettings['daily_time']) }}">
                </div>
                <div class="field">
                    <label for="backupRetention">مدة الاحتفاظ في R2 (يوم)</label>
                    <input id="backupRetention" type="number" name="retention_days" min="1" max="365" required value="{{ old('retention_days', $backupSettings['retention_days']) }}">
                </div>
                <div class="field">
                    <label for="localBackupRetention">مدة الاحتفاظ على السيرفر (يوم)</label>
                    <input id="localBackupRetention" type="number" name="local_retention_days" min="1" max="30" required value="{{ old('local_retention_days', $backupSettings['local_retention_days']) }}">
                </div>
                <div class="field">
                    <label for="backupPath">المجلد داخل R2</label>
                    <input id="backupPath" type="text" name="path" required value="{{ old('path', $backupSettings['path']) }}">
                </div>
                <button class="btn" type="submit">حفظ الإعداد</button>
            </form>

            <p class="backup-status {{ $r2Ready ? 'is-ready' : 'is-off' }}">
                {{ $r2Ready ? 'اتصال R2 جاهز للحفظ والاستعادة.' : 'R2 غير جاهز. فعّله واختبر الاتصال من إعدادات التخزين أولًا.' }}
                <a href="{{ route('storage.settings') }}">فتح إعدادات التخزين</a>
            </p>

            <p class="backup-status {{ $backupSettings['local_enabled'] ? 'is-ready' : '' }}">
                النسخ الخاصة الموجودة على السيرفر: {{ count($localDatabaseBackups) }} — محفوظة داخل storage/app/database-backups وغير متاحة للعامة.
            </p>

            @if (count($backupVerificationChannels) > 0)
                <div class="alert alert-warning">
                    الاستعادة والحذف محميان برمز تحقق أحادي الاستخدام. قبل كل استعادة ينشئ النظام نسخة أمان كاملة من قاعدة البيانات الحالية ويتحقق من رفعها إلى R2؛ وأي فشل في ذلك يلغي الاستعادة دون المساس بالبيانات.
                </div>
            @else
                <div class="alert alert-danger">
                    إجراءات الاستعادة والحذف مقفلة بالكامل. أضف بريد التحقق أو رقم الجوال من قسم «أمان Super Admin» أعلاه.
                </div>
            @endif

            @if ($backupListError)
                <div class="alert alert-danger">تعذر قراءة قائمة النسخ: {{ $backupListError }}</div>
            @endif

            <div class="backup-table-wrap">
                <table class="backup-table">
                    <thead><tr><th>الملف</th><th>التاريخ</th><th>الحجم</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                    @forelse ($databaseBackups as $backup)
                        <tr>
                            <td class="backup-file">{{ $backup['filename'] }}</td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($backup['created_at'])->format('Y-m-d H:i:s') }}</td>
                            <td>{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                            <td>
                                <div class="backup-actions">
                                    <a class="btn btn-small" href="{{ route('dev-superadmin.database-backups.download', ['key' => $backup['key']]) }}">تنزيل</a>
                                    @if (count($backupVerificationChannels) > 0)
                                        <form class="verification-request" method="POST" action="{{ route('dev-superadmin.database-backups.verification-code') }}">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $backup['key'] }}">
                                            <select name="verification_channel" required aria-label="وسيلة إرسال رمز التحقق">
                                                @foreach ($backupVerificationChannels as $channel => $details)
                                                    <option value="{{ $channel }}">{{ $details['label'] }} — {{ $details['destination'] }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-small" type="submit" name="action" value="restore">طلب رمز الاستعادة</button>
                                            <button class="btn btn-danger btn-small" type="submit" name="action" value="delete">طلب رمز الحذف</button>
                                        </form>
                                    @endif

                                    @if ($backupVerificationChallenge && $backupVerificationChallenge['key'] === $backup['key'])
                                        @if ($backupVerificationChallenge['action'] === 'restore')
                                            <form class="verification-confirm" method="POST" action="{{ route('dev-superadmin.database-backups.restore') }}" onsubmit="return confirm('سيتم أولًا أخذ نسخة أمان من البيانات الحالية، ثم استبدال قاعدة البيانات. هل تريد المتابعة؟')">
                                                @csrf
                                                <input type="hidden" name="key" value="{{ $backup['key'] }}">
                                                <input type="text" name="confirmation" required placeholder="RESTORE" autocomplete="off" aria-label="اكتب RESTORE">
                                                <input type="text" name="verification_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="رمز التحقق" autocomplete="one-time-code" aria-label="رمز التحقق">
                                                <button class="btn btn-primary btn-small" type="submit">تأكيد الاستعادة</button>
                                                <p class="verification-note">الرمز مرسل إلى {{ $backupVerificationChallenge['destination'] }} وصالح حتى {{ \Carbon\Carbon::createFromTimestamp($backupVerificationChallenge['expires_at'])->format('H:i') }}.</p>
                                            </form>
                                        @else
                                            <form class="verification-confirm" method="POST" action="{{ route('dev-superadmin.database-backups.destroy') }}" onsubmit="return confirm('سيتم حذف ملف النسخة نهائيًا من R2. هل تريد المتابعة؟')">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="key" value="{{ $backup['key'] }}">
                                                <input type="text" name="confirmation" required placeholder="DELETE" autocomplete="off" aria-label="اكتب DELETE">
                                                <input type="text" name="verification_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="رمز التحقق" autocomplete="one-time-code" aria-label="رمز التحقق">
                                                <button class="btn btn-danger btn-small" type="submit">تأكيد الحذف</button>
                                                <p class="verification-note">الرمز مرسل إلى {{ $backupVerificationChallenge['destination'] }} وصالح حتى {{ \Carbon\Carbon::createFromTimestamp($backupVerificationChallenge['expires_at'])->format('H:i') }}.</p>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">لا توجد نسخ احتياطية محفوظة حتى الآن.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    @if ($activeSection === 'scheduler')
    <section class="panel backup-panel" id="scheduler">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">مراقبة Cron Jobs وLaravel Scheduler</h2>
                <p class="panel-subtitle">الحالة تعتمد على نبض يُكتب بواسطة <span dir="ltr">schedule:run</span> كل دقيقة، وليست مجرد وجود المهمة داخل الكود.</p>
            </div>
            <div class="backup-actions">
                @if (!$schedulerStatus['active'] || $schedulerStatus['crontab_configured'] !== true)
                    <form method="POST" action="{{ route('dev-superadmin.scheduler.enable') }}" onsubmit="return confirm('سيتم تثبيت سطر Cron وتشغيل جميع المهام المستحقة الآن. هل تريد المتابعة؟')">
                        @csrf
                        <button class="btn btn-primary" type="submit">تفعيل وتشغيل Scheduler</button>
                    </form>
                @endif
                <a class="btn" href="{{ route('dev-superadmin.modules') }}">تحديث الحالة</a>
            </div>
        </div>

        <div class="panel-body">
            <div class="scheduler-summary">
                <div class="scheduler-stat {{ $schedulerStatus['active'] ? 'is-active' : 'is-inactive' }}">
                    حالة Laravel Scheduler
                    <strong>{{ $schedulerStatus['active'] ? 'يعمل الآن' : 'متوقف أو لم يعمل خلال 3 دقائق' }}</strong>
                </div>
                <div class="scheduler-stat {{ $schedulerStatus['crontab_configured'] === true ? 'is-active' : ($schedulerStatus['crontab_configured'] === false ? 'is-inactive' : '') }}">
                    إعداد Crontab للمستخدم الحالي
                    <strong>
                        @if ($schedulerStatus['crontab_configured'] === true) موجود
                        @elseif ($schedulerStatus['crontab_configured'] === false) غير موجود
                        @else تعذر التحقق
                        @endif
                    </strong>
                </div>
                <div class="scheduler-stat">
                    آخر نبض
                    <strong>{{ $schedulerStatus['last_heartbeat_at'] ? $schedulerStatus['last_heartbeat_at']->format('Y-m-d H:i:s') : 'لا يوجد بعد' }}</strong>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>ملخص تدقيق الأمان:</strong>
                لا توجد مهمة مجدولة تحذف صفوف عقود أو طلبات أو عملاء مباشرة.
                توجد {{ $highRiskCronCount }} مهمة عالية التأثير تغيّر حالة الطلب تلقائيًا،
                و{{ $invalidCronCount }} مهمة بأمر غير موجود لن تعمل حتى يتم إصلاحها.
                حذف النسخ الاحتياطية محصور بالملفات الأقدم من مدة الاحتفاظ فقط.
            </div>

            @if ($schedulerStatus['crontab_error'])
                <div class="alert alert-danger">تعذر فحص Crontab: {{ $schedulerStatus['crontab_error'] }}</div>
            @endif

            @foreach ($schedulerStatus['crontab_entries'] as $entry)
                <div class="cron-entry">{{ $entry }}</div>
            @endforeach

            @if ($schedulerStatus['crontab_configured'] === false)
                <div class="alert alert-danger">
                    Scheduler غير مضاف إلى Crontab لهذا المستخدم. أضف السطر التالي على السيرفر:
                    <div class="cron-entry">{{ $schedulerStatus['recommended_entry'] }}</div>
                </div>
            @endif

            <div class="backup-table-wrap">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>المهمة</th>
                            <th>الحالة</th>
                            <th>سبب التشغيل</th>
                            <th>التأثير والحذف</th>
                            <th>الخطورة وضابط الأمان</th>
                            <th>الجدولة والتشغيل القادم</th>
                            <th>الأمر</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($schedulerStatus['jobs'] as $job)
                        <tr>
                            <td><strong>{{ $job['name'] }}</strong></td>
                            <td>
                                <span class="status-badge {{ $job['enabled'] ? 'status-on' : 'status-off' }}">
                                    @if (!$job['command_exists']) أمر غير موجود — لن تعمل
                                    @elseif ($job['enabled']) مفعلة وتعمل
                                    @elseif ($job['configured']) مجدولة لكن Scheduler متوقف
                                    @else غير مفعلة في هذه البيئة
                                    @endif
                                </span>
                            </td>
                            <td class="job-explanation">{{ $job['purpose'] }}</td>
                            <td class="job-effect">
                                <div>{{ $job['data_effect'] }}</div>
                                <strong>{{ $job['deletion'] }}</strong>
                            </td>
                            <td class="job-safety">
                                <span class="risk-badge risk-{{ $job['risk'] }}">{{ $job['risk_label'] }}</span>
                                <div>{{ $job['safety_note'] }}</div>
                            </td>
                            <td>
                                <div class="cron-expression">{{ $job['expression'] }}</div>
                                <div>{{ $job['next_run_at'] ? $job['next_run_at']->format('Y-m-d H:i:s') : '—' }}</div>
                            </td>
                            <td>
                                <div class="cron-command">{{ $job['command'] }}</div>
                                <small class="text-muted">
                                    {{ $job['without_overlapping'] ? 'بدون تداخل' : 'يسمح بالتداخل' }}
                                    — {{ $job['background'] ? 'في الخلفية' : 'في المقدمة' }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">لم يتم العثور على مهمات مجدولة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif
</main>

<script>
    const productUpdatesBuilder = document.getElementById('productUpdatesBuilder');
    const addProductUpdateButton = document.getElementById('addProductUpdate');

    function refreshProductUpdateRows() {
        const rows = Array.from(productUpdatesBuilder?.querySelectorAll('[data-update-row]') || []);
        rows.forEach((row, index) => {
            const number = row.querySelector('[data-update-number]');
            if (number) number.textContent = `التحديث ${index + 1}`;

            const moveUp = row.querySelector('[data-move-update="up"]');
            const moveDown = row.querySelector('[data-move-update="down"]');
            if (moveUp) moveUp.disabled = index === 0;
            if (moveDown) moveDown.disabled = index === rows.length - 1;

            row.querySelectorAll('[data-update-field]').forEach((field) => {
                field.name = `updates[${index}][${field.dataset.updateField}]`;
            });
        });
    }

    function createProductUpdateRow() {
        const row = document.createElement('article');
        row.className = 'product-update-editor';
        row.dataset.updateRow = '';
        row.innerHTML = `
            <div class="product-update-editor-head">
                <strong data-update-number>تحديث جديد</strong>
                <div class="product-update-order">
                    <button type="button" data-move-update="up" title="تحريك لأعلى" aria-label="تحريك لأعلى">↑</button>
                    <button type="button" data-move-update="down" title="تحريك لأسفل" aria-label="تحريك لأسفل">↓</button>
                    <button type="button" class="product-update-remove" data-remove-update title="حذف">حذف</button>
                </div>
            </div>
            <div class="product-update-fields">
                <div class="field wide">
                    <label>عنوان الميزة</label>
                    <input data-update-field="title" type="text" required maxlength="160" placeholder="مثال: إنشاء إشعار دائن من الفاتورة">
                </div>
                <div class="field wide">
                    <label>تفاصيل مختصرة</label>
                    <textarea data-update-field="details" rows="2" maxlength="1000" placeholder="اشرح الفائدة للعميل باختصار"></textarea>
                </div>
                <div class="field">
                    <label>رابط الصفحة</label>
                    <input data-update-field="url" type="text" maxlength="1000" dir="ltr" placeholder="/dashboard/invoices">
                </div>
                <div class="field">
                    <label>نص زر الانتقال</label>
                    <input data-update-field="link_label" type="text" maxlength="80" placeholder="الانتقال إلى الفواتير">
                </div>
            </div>`;
        return row;
    }

    addProductUpdateButton?.addEventListener('click', function () {
        const row = createProductUpdateRow();
        productUpdatesBuilder.insertBefore(row, addProductUpdateButton);
        refreshProductUpdateRows();
        row.querySelector('[data-update-field="title"]')?.focus();
    });

    productUpdatesBuilder?.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-update]');
        const moveButton = event.target.closest('[data-move-update]');
        const row = event.target.closest('[data-update-row]');
        if (!row) return;

        if (removeButton) {
            const rows = productUpdatesBuilder.querySelectorAll('[data-update-row]');
            if (rows.length === 1) {
                row.querySelectorAll('input, textarea').forEach((field) => field.value = '');
                row.querySelector('[data-update-field="title"]')?.focus();
                return;
            }
            row.remove();
            refreshProductUpdateRows();
            return;
        }

        if (moveButton) {
            const direction = moveButton.dataset.moveUpdate;
            const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
            if (!sibling?.matches('[data-update-row]')) return;

            if (direction === 'up') {
                productUpdatesBuilder.insertBefore(row, sibling);
            } else {
                productUpdatesBuilder.insertBefore(sibling, row);
            }
            refreshProductUpdateRows();
        }
    });

    refreshProductUpdateRows();

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
