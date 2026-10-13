@extends('admin.layout.indexs.index')

@section('page-title')
إعدادات التنبيهات الآلية
@endsection

@section('style')
    <style>
        .alerts-page {
            --ap-primary: #4f46e5;
            --ap-primary-dark: #3730a3;
            --ap-primary-soft: #eef2ff;
            --ap-border: #e5e7eb;
            --ap-muted: #6b7280;
            --ap-text: #111827;
            --ap-radius: 16px;
            position: relative;
            color: var(--ap-text);
        }

        /* Keep the visually-hidden checkboxes (btn-check / switches) positioned inside the page,
           otherwise focusing them on click scrolls the document to a far-away offset. */
        .channel-pills,
        .alerts-switch,
        .rule-card,
        .custom-alert-card {
            position: relative;
        }

        #alert-rules-settings {
            scroll-margin-top: 90px;
        }

        /* ---------- Hero ---------- */
        .alerts-hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--ap-radius);
            padding: 26px 28px;
            margin-bottom: 22px;
            color: #fff;
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 55%, #7c3aed 100%);
            box-shadow: 0 18px 40px -20px rgba(79, 70, 229, 0.65);
        }

        .alerts-hero::before,
        .alerts-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .alerts-hero::before {
            width: 260px;
            height: 260px;
            top: -120px;
            left: -60px;
        }

        .alerts-hero::after {
            width: 180px;
            height: 180px;
            bottom: -90px;
            right: 20%;
        }

        .alerts-hero__inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .alerts-hero__title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .alerts-hero__icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.28);
            backdrop-filter: blur(6px);
        }

        .alerts-hero__title h4 {
            margin: 0;
            font-weight: 800;
            font-size: 1.35rem;
            color: #fff;
        }

        .alerts-hero__title p {
            margin: 4px 0 0;
            font-size: 0.86rem;
            color: rgba(255, 255, 255, 0.82);
        }

        .alerts-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .alerts-hero__actions .btn {
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.86rem;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.12);
            transition: all .18s ease;
        }

        .alerts-hero__actions .btn:hover {
            background: #fff;
            color: var(--ap-primary-dark);
            border-color: #fff;
        }

        /* ---------- Cards ---------- */
        .alerts-card {
            border: 1px solid var(--ap-border);
            border-radius: var(--ap-radius);
            background: #fff;
            box-shadow: 0 10px 30px -22px rgba(17, 24, 39, 0.35);
            margin-bottom: 22px;
            overflow: hidden;
        }

        .alerts-card__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--ap-border);
            background: linear-gradient(180deg, #fafafe 0%, #fff 100%);
        }

        .alerts-card__heading {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alerts-card__icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .alerts-card__icon--indigo { background: #eef2ff; color: #4338ca; }
        .alerts-card__icon--amber  { background: #fffbeb; color: #b45309; }
        .alerts-card__icon--teal   { background: #f0fdfa; color: #0f766e; }

        .alerts-card__heading h5 {
            margin: 0;
            font-weight: 800;
            font-size: 1.02rem;
        }

        .alerts-card__heading p {
            margin: 3px 0 0;
            font-size: 0.82rem;
            color: var(--ap-muted);
        }

        .alerts-card__body {
            padding: 22px;
        }

        /* ---------- Cron groups ---------- */
        .cron-group + .cron-group {
            margin-top: 22px;
            padding-top: 22px;
            border-top: 1px dashed var(--ap-border);
        }

        .cron-group__title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--ap-primary-dark);
        }

        .cron-group__title i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ap-primary-soft);
            font-size: 0.8rem;
        }

        .cron-group__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 14px;
        }

        .cron-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 14px;
            border: 1px solid var(--ap-border);
            border-radius: 12px;
            background: #fbfbfd;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .cron-field:focus-within {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
            background: #fff;
        }

        .cron-field .form-label {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            color: #374151;
        }

        .cron-field .field-hint {
            font-size: 0.74rem;
            line-height: 1.5;
            color: var(--ap-muted);
        }

        .alerts-page .form-control,
        .alerts-page .form-select {
            border-radius: 10px;
            border-color: #d1d5db;
            font-size: 0.88rem;
            min-height: 40px;
        }

        .alerts-page .form-control:focus,
        .alerts-page .form-select:focus {
            border-color: #a5b4fc;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .alerts-page .input-group .form-control,
        .alerts-page .input-group .form-select {
            min-width: 0;
        }

        .alerts-page .input-group-text {
            background: var(--ap-primary-soft);
            color: var(--ap-primary-dark);
            border-color: #d1d5db;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* ---------- Switch ---------- */
        .alerts-switch .form-check-input {
            width: 2.6em;
            height: 1.4em;
            cursor: pointer;
            border-color: #cbd5e1;
        }

        .alerts-switch .form-check-input:checked {
            background-color: #16a34a;
            border-color: #16a34a;
        }

        .alerts-switch .form-check-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ap-muted);
            margin-inline-start: 6px;
            cursor: pointer;
        }

        /* ---------- Channel pills ---------- */
        .channel-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .channel-pills .btn-check + .btn {
            --pill: #6b7280;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #4b5563;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.2;
            transition: all .16s ease;
        }

        .channel-pills .btn-check + .btn i {
            font-size: 0.8rem;
            color: var(--pill);
        }

        .channel-pills .btn-check + .btn[data-channel="sms"]          { --pill: #0284c7; }
        .channel-pills .btn-check + .btn[data-channel="whatsapp"]     { --pill: #16a34a; }
        .channel-pills .btn-check + .btn[data-channel="notification"] { --pill: #4f46e5; }
        .channel-pills .btn-check + .btn[data-channel="email"]        { --pill: #d97706; }

        .channel-pills .btn-check:checked + .btn {
            background: var(--pill);
            border-color: var(--pill);
            color: #fff;
            box-shadow: 0 6px 14px -8px var(--pill);
        }

        .channel-pills .btn-check:checked + .btn i {
            color: #fff;
        }

        .channel-pills .btn-check:focus-visible + .btn {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        /* ---------- Rules list ---------- */
        .alert-rules-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .rule-card {
            border: 1px solid var(--ap-border);
            border-inline-start: 4px solid #16a34a;
            border-radius: 14px;
            background: #fff;
            transition: box-shadow .18s ease, border-color .18s ease, background .18s ease;
        }

        .rule-card:hover {
            box-shadow: 0 12px 28px -22px rgba(79, 70, 229, 0.55);
        }

        .rule-card.is-disabled {
            border-inline-start-color: #cbd5e1;
            background: #fcfcfd;
        }

        .rule-card.is-disabled .rule-title,
        .rule-card.is-disabled .rule-desc,
        .rule-card.is-disabled .rule-icon,
        .rule-card.is-disabled .rule-card__body {
            opacity: 0.6;
        }

        .rule-card.is-disabled .rule-card__body:focus-within {
            opacity: 1;
        }

        .rule-card__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 16px 18px;
            border-bottom: 1px solid #f1f2f6;
        }

        .rule-card__toggle {
            flex-shrink: 0;
            padding: 6px 12px;
            border-radius: 999px;
            background: #f8f9fc;
            border: 1px solid var(--ap-border);
        }

        .rule-card__toggle .form-check-label {
            min-width: 42px;
            display: inline-block;
        }

        .rule-card__body {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: 14px 16px;
            padding: 16px 18px 18px;
            transition: opacity .18s ease;
        }

        .rule-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
            grid-column: span 3;
        }

        .rule-field--channels {
            grid-column: span 12;
        }

        .rule-field__label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            font-size: 0.78rem;
            font-weight: 700;
            color: #4b5563;
        }

        .rule-field__label i {
            color: #818cf8;
            font-size: 0.75rem;
        }

        .rule-field .form-text {
            margin-top: 0;
        }

        @media (max-width: 1199.98px) {
            .rule-field { grid-column: span 6; }
        }

        @media (max-width: 575.98px) {
            .rule-field { grid-column: span 12; }

            .rule-card__header {
                flex-direction: column;
            }
        }

        /* ---------- Section nav ---------- */
        .alerts-nav {
            position: sticky;
            top: 70px;
            z-index: 6;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
            padding: 8px;
            border: 1px solid var(--ap-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(8px);
        }

        .alerts-nav a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #4b5563;
            text-decoration: none;
            transition: all .15s ease;
        }

        .alerts-nav a:hover {
            background: var(--ap-primary-soft);
            color: var(--ap-primary-dark);
        }

        .alerts-nav__count {
            padding: 1px 8px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 0.72rem;
        }

        .alerts-section {
            scroll-margin-top: 140px;
        }

        .rule-cell {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .rule-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ap-primary-soft);
            color: var(--ap-primary-dark);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .rule-title {
            font-weight: 800;
            font-size: 0.92rem;
            color: var(--ap-text);
        }

        .rule-desc {
            font-size: 0.78rem;
            color: var(--ap-muted);
            line-height: 1.55;
            margin-top: 2px;
        }

        .rule-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .rule-stat {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .rule-stat--info   { background: #e0f2fe; color: #075985; }
        .rule-stat--danger { background: #fee2e2; color: #991b1b; }

        .rule-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 4px;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--ap-primary);
            text-decoration: none;
        }

        .rule-link:hover {
            text-decoration: underline;
            color: var(--ap-primary-dark);
        }

        .alerts-page .form-text {
            font-size: 0.72rem;
            color: var(--ap-muted);
        }

        /* ---------- Custom alerts ---------- */
        .custom-alert-card {
            border: 1px solid var(--ap-border);
            border-radius: 14px;
            background: #fff;
            margin-bottom: 16px;
            overflow: hidden;
            transition: box-shadow .18s ease, border-color .18s ease;
        }

        .custom-alert-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 12px 28px -20px rgba(79, 70, 229, 0.5);
        }

        .custom-alert-card__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 18px;
            background: #fafafe;
            border-bottom: 1px solid var(--ap-border);
        }

        .custom-alert-card__title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .custom-alert-card__num {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ap-primary);
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .custom-alert-card__tools {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .custom-alert-card__body {
            padding: 18px;
        }

        .custom-alert-card .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
        }

        .btn-remove-alert {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid #fecaca;
            background: #fff5f5;
            color: #b91c1c;
            font-size: 0.78rem;
            font-weight: 700;
            transition: all .16s ease;
        }

        .btn-remove-alert:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }

        .btn-add-alert {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 999px;
            border: 1px solid #c7d2fe;
            background: var(--ap-primary-soft);
            color: var(--ap-primary-dark);
            font-size: 0.84rem;
            font-weight: 700;
            transition: all .16s ease;
        }

        .btn-add-alert:hover {
            background: var(--ap-primary);
            border-color: var(--ap-primary);
            color: #fff;
        }

        .custom-alerts-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 34px 16px;
            border: 1px dashed #d1d5db;
            border-radius: 14px;
            background: #fafafe;
            color: var(--ap-muted);
            text-align: center;
        }

        .custom-alerts-empty i {
            font-size: 1.8rem;
            color: #a5b4fc;
        }

        .custom-alerts-empty strong {
            color: #374151;
        }

        /* ---------- Save bar ---------- */
        .alerts-savebar {
            position: sticky;
            bottom: 12px;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 6px;
            margin-bottom: 20px;
            padding: 12px 18px;
            border-radius: 14px;
            border: 1px solid var(--ap-border);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            box-shadow: 0 14px 34px -18px rgba(17, 24, 39, 0.45);
        }

        .alerts-savebar__hint {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--ap-muted);
        }

        .btn-save-alerts {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 999px;
            border: 0;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 10px 22px -10px rgba(79, 70, 229, 0.8);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .btn-save-alerts:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 14px 26px -10px rgba(79, 70, 229, 0.9);
        }

        /* ---------- Alerts / flash ---------- */
        .alerts-page .alert {
            border-radius: 12px;
            border: 0;
            font-size: 0.88rem;
        }

        @media (max-width: 768px) {
            .alerts-hero {
                padding: 20px;
            }

            .alerts-card__body {
                padding: 16px;
            }

            .cron-group__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::NOTIFICATIONS_CONTROL))
    @php
        $oldSection = old('section');
        $alertDefinitions = $alertDefinitions ?? \App\Support\NotificationAlertSettings::definitions();
        $alertSettings = $alertSettings ?? \App\Support\NotificationAlertSettings::all();
        $customAlertSettings = $customAlertSettings ?? \App\Support\NotificationAlertSettings::custom();
        $alertChannelOptions = $alertChannelOptions ?? \App\Support\NotificationAlertSettings::channelOptions();
        $alertRecipientOptions = $alertRecipientOptions ?? \App\Support\NotificationAlertSettings::recipientOptions();
        $alertUnitOptions = $alertUnitOptions ?? \App\Support\NotificationAlertSettings::unitOptions();
        $alertTemplateOptions = $alertTemplateOptions ?? \App\Support\NotificationAlertSettings::templateOptions();
        $automationSettings = $automationSettings ?? \App\Support\AutomationSettings::all();

        if ($oldSection === 'alerts') {
            foreach (old('alerts', []) as $key => $submittedRule) {
                $alertSettings[$key] = array_replace($alertSettings[$key] ?? [], $submittedRule);
                $alertSettings[$key]['channels'] = $submittedRule['channels'] ?? [];
            }
            $customAlertSettings = old('custom_alerts', $customAlertSettings);
            $automationSettings = array_replace($automationSettings, old());
        }

        $weekdayOptions = [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];

        $channelIcons = [
            'sms' => 'fa-solid fa-comment-sms',
            'whatsapp' => 'fa-brands fa-whatsapp',
            'notification' => 'fa-solid fa-bell',
            'email' => 'fa-solid fa-envelope',
        ];

        $enabledRulesCount = collect(array_keys($alertDefinitions))
            ->filter(fn ($key) => (bool) data_get($alertSettings, "{$key}.enabled"))
            ->count();
    @endphp

    <div class="container-fluid alerts-page" id="alert-rules-settings">
        <div class="alerts-hero">
            <div class="alerts-hero__inner">
                <div class="alerts-hero__title">
                    <span class="alerts-hero__icon"><i class="fa-solid fa-bell"></i></span>
                    <div>
                        <h4>إعدادات التنبيهات الآلية</h4>
                        <p>تحكم في قواعد التنبيه، قنوات الإرسال، والمدد التي تعمل عليها مهام الجدولة.</p>
                    </div>
                </div>
                <div class="alerts-hero__actions">
                    <a href="{{ route('notifications.settings') }}" class="btn">
                        <i class="fa-solid fa-plug me-1"></i>
                        إعدادات القنوات
                    </a>
                    <a href="{{ route('admin.sms-templates.index') }}" class="btn">
                        <i class="fa-regular fa-file-lines me-1"></i>
                        قوالب الرسائل
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" id="alert-save-errors" role="alert" tabindex="-1">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('notifications.settings.update') }}">
            @csrf
            <input type="hidden" name="section" value="alerts">

            <nav class="alerts-nav" aria-label="أقسام الصفحة">
                <a href="#alerts-core">
                    <i class="fa-solid fa-bell"></i>
                    التنبيهات الأساسية
                    <span class="alerts-nav__count"><span data-enabled-rules-count>{{ $enabledRulesCount }}</span> / {{ count($alertDefinitions) }}</span>
                </a>
                <a href="#alerts-custom"><i class="fa-solid fa-wand-magic-sparkles"></i> التنبيهات المخصصة</a>
                <a href="#alerts-cron"><i class="fa-solid fa-clock-rotate-left"></i> المهام الآلية (Cron)</a>
            </nav>

            {{-- ===================== Core alert rules ===================== --}}
            <div class="alerts-card alerts-section" id="alerts-core">
                <div class="alerts-card__header">
                    <div class="alerts-card__heading">
                        <span class="alerts-card__icon alerts-card__icon--indigo"><i class="fa-solid fa-bell"></i></span>
                        <div>
                            <h5>تنبيهات النظام الأساسية</h5>
                            <p>حدد القناة، المستلم، والمدة قبل الحدث لكل جزء في النظام.</p>
                        </div>
                    </div>
                </div>
                <div class="alerts-card__body">
                    @include('admin.ingaz.notifications.partials.alert-rules-table', [
                        'rules' => $alertDefinitions,
                        'alertRuleSettings' => $alertSettings,
                        'inputPrefix' => 'alerts',
                        'canEditMeta' => false,
                        'channelIcons' => $channelIcons,
                    ])
                </div>
            </div>

            {{-- ===================== Custom alerts ===================== --}}
            <div class="alerts-card alerts-section" id="alerts-custom">
                <div class="alerts-card__header">
                    <div class="alerts-card__heading">
                        <span class="alerts-card__icon alerts-card__icon--teal"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                        <div>
                            <h5>تنبيهات مخصصة</h5>
                            <p>أضف تنبيها جديدا ليكون محفوظا ضمن إعدادات النظام وقابلا للاستخدام في أي مهمة لاحقة.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-add-alert" id="addCustomAlert">
                        <i class="fa-solid fa-plus"></i>
                        إضافة تنبيه
                    </button>
                </div>
                <div class="alerts-card__body">
                    <div id="customAlertsContainer">
                        @forelse ($customAlertSettings as $index => $customRule)
                            @include('admin.ingaz.notifications.partials.custom-alert-row', [
                                'index' => $index,
                                'rule' => $customRule,
                                'channelIcons' => $channelIcons,
                            ])
                        @empty
                            <div class="custom-alerts-empty" id="emptyCustomAlertsHint">
                                <i class="fa-regular fa-bell-slash"></i>
                                <strong>لا توجد تنبيهات مخصصة حتى الآن</strong>
                                <span class="small">اضغط "إضافة تنبيه" لإنشاء أول قاعدة مخصصة.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ===================== Cron / automation ===================== --}}
            <div class="alerts-card alerts-section" id="alerts-cron">
                <div class="alerts-card__header">
                    <div class="alerts-card__heading">
                        <span class="alerts-card__icon alerts-card__icon--amber"><i class="fa-solid fa-clock-rotate-left"></i></span>
                        <div>
                            <h5>إعدادات الـ Cron والمهام الآلية</h5>
                            <p>هذه القيم تتحكم في مواعيد تشغيل المهام المجدولة الموجودة في النظام.</p>
                        </div>
                    </div>
                </div>
                <div class="alerts-card__body">

                    <div class="cron-group">
                        <div class="cron-group__title"><i class="fa-solid fa-shield-halved"></i> الضمان</div>
                        <div class="cron-group__grid">
                            <div class="cron-field">
                                <label class="form-label">مدة الضمان بالأيام</label>
                                <div class="input-group">
                                    <input type="number" name="warranty_period_days" class="form-control" min="1" max="3650"
                                        value="{{ old('warranty_period_days', $automationSettings['warranty_period_days'] ?? 90) }}" required>
                                    <span class="input-group-text">يوم</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">وقت تحديث حالة الضمان</label>
                                <input type="time" name="warranty_status_update_time" class="form-control"
                                    value="{{ old('warranty_status_update_time', $automationSettings['warranty_status_update_time'] ?? '01:00') }}" required>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">تهيئة الضمان كل</label>
                                <div class="input-group">
                                    <input type="number" name="warranty_initialize_interval_hours" class="form-control" min="1" max="24"
                                        value="{{ old('warranty_initialize_interval_hours', $automationSettings['warranty_initialize_interval_hours'] ?? 6) }}" required>
                                    <span class="input-group-text">ساعة</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cron-group">
                        <div class="cron-group__title"><i class="fa-solid fa-file-signature"></i> الطلبات</div>
                        <div class="cron-group__grid">
                            <div class="cron-field">
                                <label class="form-label">تايمر الطلبات كل</label>
                                <div class="input-group">
                                    <input type="number" name="orders_timer_interval_minutes" class="form-control" min="1" max="60"
                                        value="{{ old('orders_timer_interval_minutes', $automationSettings['orders_timer_interval_minutes'] ?? 30) }}" required>
                                    <span class="input-group-text">دقيقة</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">مهلة الطلب الافتراضية</label>
                                <div class="input-group">
                                    <input type="number" name="order_expiration_hours" class="form-control" min="1" max="720"
                                        value="{{ old('order_expiration_hours', $automationSettings['order_expiration_hours'] ?? 24) }}" required>
                                    <span class="input-group-text">ساعة</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">مهلة الطلب الجديد</label>
                                <div class="input-group">
                                    <input type="number" name="order_new_deadline_hours" class="form-control" min="1" max="720"
                                        value="{{ old('order_new_deadline_hours', $automationSettings['order_new_deadline_hours'] ?? 24) }}" required>
                                    <span class="input-group-text">ساعة</span>
                                </div>
                                <div class="field-hint">بعد انتهاء هذه المهلة يُلغى الطلب الجديد تلقائياً وتتاح العاملة ما لم ترتبط بطلب آخر أو عقد قائم. تعديل المدد يعيد حساب تنبيهات الطلبات الحالية من تاريخ بداية حالتها.</div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">مهلة الطلب تحت الإجراء</label>
                                <div class="input-group">
                                    <input type="number" name="order_under_process_deadline_hours" class="form-control" min="1" max="720"
                                        value="{{ old('order_under_process_deadline_hours', $automationSettings['order_under_process_deadline_hours'] ?? 48) }}" required>
                                    <span class="input-group-text">ساعة</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">حد الطلب العاجل</label>
                                <div class="input-group">
                                    <input type="number" name="order_urgent_threshold_hours" class="form-control" min="1" max="720"
                                        value="{{ old('order_urgent_threshold_hours', $automationSettings['order_urgent_threshold_hours'] ?? 2) }}" required>
                                    <span class="input-group-text">ساعة</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">الطلبات المعلقة القديمة</label>
                                <div class="input-group">
                                    <input type="number" name="old_pending_orders_days" class="form-control" min="1" max="3650"
                                        value="{{ old('old_pending_orders_days', $automationSettings['old_pending_orders_days'] ?? 30) }}" required>
                                    <span class="input-group-text">يوم</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">إلغاء الطلبات تلقائيا كل</label>
                                <div class="input-group">
                                    <input type="number" name="orders_auto_cancel_interval_minutes" class="form-control" min="1" max="60"
                                        value="{{ old('orders_auto_cancel_interval_minutes', $automationSettings['orders_auto_cancel_interval_minutes'] ?? 60) }}" required>
                                    <span class="input-group-text">دقيقة</span>
                                </div>
                            </div>
                            <div class="cron-field d-none">
                                <label class="form-label">فاصل المعالجة القديم (غير مستخدم)</label>
                                <div class="input-group">
                                    <input type="number" name="orders_expired_handler_interval_minutes" class="form-control" min="1" max="60"
                                        value="{{ old('orders_expired_handler_interval_minutes', $automationSettings['orders_expired_handler_interval_minutes'] ?? 5) }}" required>
                                    <span class="input-group-text">دقيقة</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cron-group">
                        <div class="cron-group__title"><i class="fa-solid fa-screwdriver-wrench"></i> الصيانة والسجلات</div>
                        <div class="cron-group__grid">
                            <div class="cron-field">
                                <label class="form-label">تحديث توفر العاملات</label>
                                <input type="time" name="maids_availability_update_time" class="form-control"
                                    value="{{ old('maids_availability_update_time', $automationSettings['maids_availability_update_time'] ?? '02:00') }}" required>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">الاحتفاظ بالسجلات</label>
                                <div class="input-group">
                                    <input type="number" name="log_clear_keep_days" class="form-control" min="1" max="365"
                                        value="{{ old('log_clear_keep_days', $automationSettings['log_clear_keep_days'] ?? 30) }}" required>
                                    <span class="input-group-text">يوم</span>
                                </div>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">تنظيف السجلات يوم</label>
                                <select name="log_clear_weekday" class="form-select" required>
                                    @foreach ($weekdayOptions as $weekdayValue => $weekdayLabel)
                                        <option value="{{ $weekdayValue }}" {{ (int) old('log_clear_weekday', $automationSettings['log_clear_weekday'] ?? 0) === (int) $weekdayValue ? 'selected' : '' }}>
                                            {{ $weekdayLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">وقت تنظيف السجلات</label>
                                <input type="time" name="log_clear_time" class="form-control"
                                    value="{{ old('log_clear_time', $automationSettings['log_clear_time'] ?? '03:00') }}" required>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">تحسين قاعدة البيانات يوم</label>
                                <select name="database_optimize_weekday" class="form-select" required>
                                    @foreach ($weekdayOptions as $weekdayValue => $weekdayLabel)
                                        <option value="{{ $weekdayValue }}" {{ (int) old('database_optimize_weekday', $automationSettings['database_optimize_weekday'] ?? 0) === (int) $weekdayValue ? 'selected' : '' }}>
                                            {{ $weekdayLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="cron-field">
                                <label class="form-label">وقت تحسين قاعدة البيانات</label>
                                <input type="time" name="database_optimize_time" class="form-control"
                                    value="{{ old('database_optimize_time', $automationSettings['database_optimize_time'] ?? '04:00') }}" required>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="alerts-savebar">
                <div class="alerts-savebar__hint">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>يتم تطبيق التغييرات فور الحفظ على المهام المجدولة وقواعد التنبيه.</span>
                </div>
                <button type="submit" class="btn-save-alerts">
                    <i class="fa-solid fa-floppy-disk"></i>
                    حفظ إعدادات التنبيهات
                </button>
            </div>
        </form>
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
@if ($errors->any())
<script>
    window.addEventListener('load', function () {
        var errors = document.getElementById('alert-save-errors');
        if (errors) { errors.scrollIntoView({block: 'center'}); errors.focus({preventScroll: true}); }
    });
</script>
@endif
<script>
    const alertChannelOptions = @json($alertChannelOptions ?? []);
    const alertRecipientOptions = @json($alertRecipientOptions ?? []);
    const alertUnitOptions = @json($alertUnitOptions ?? []);
    const alertTemplateOptions = @json($alertTemplateOptions ?? []);
    const alertChannelIcons = @json($channelIcons ?? []);

    function nextCustomAlertIndex() {
        let maxIndex = -1;

        $('#customAlertsContainer [data-custom-alert-row]').each(function () {
            maxIndex = Math.max(maxIndex, Number($(this).data('custom-alert-row')));
        });

        return maxIndex + 1;
    }

    function optionList(options, selectedValue) {
        return Object.entries(options).map(([value, label]) => {
            return `<option value="${value}" ${selectedValue === value ? 'selected' : ''}>${label}</option>`;
        }).join('');
    }

    function channelChecks(index) {
        return Object.entries(alertChannelOptions).map(([value, label], channelIndex) => {
            const id = `customAlert${index}Channel${channelIndex}`;
            const icon = alertChannelIcons[value] || 'fa-solid fa-paper-plane';
            return `
                <input class="btn-check" type="checkbox" autocomplete="off" name="custom_alerts[${index}][channels][]" value="${value}" id="${id}" ${value === 'notification' ? 'checked' : ''}>
                <label class="btn" data-channel="${value}" for="${id}"><i class="${icon}"></i>${label}</label>
            `;
        }).join('');
    }

    function buildCustomAlertRow(index) {
        const enabledId = `customAlert${index}Enabled`;
        return `
            <div class="custom-alert-card" data-custom-alert-row="${index}">
                <input type="hidden" name="custom_alerts[${index}][key]" value="custom_${Date.now()}_${index}">
                <div class="custom-alert-card__header">
                    <div class="custom-alert-card__title">
                        <span class="custom-alert-card__num" data-custom-alert-num>${index + 1}</span>
                        <span>تنبيه مخصص</span>
                    </div>
                    <div class="custom-alert-card__tools">
                        <input type="hidden" name="custom_alerts[${index}][enabled]" value="0">
                        <div class="form-check form-switch alerts-switch m-0">
                            <input class="form-check-input" type="checkbox" name="custom_alerts[${index}][enabled]" value="1" id="${enabledId}" checked>
                            <label class="form-check-label" for="${enabledId}">مفعل</label>
                        </div>
                        <button type="button" class="btn-remove-alert js-remove-custom-alert">
                            <i class="fa-solid fa-trash-can"></i>
                            حذف
                        </button>
                    </div>
                </div>
                <div class="custom-alert-card__body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">اسم التنبيه</label>
                            <input type="text" name="custom_alerts[${index}][label]" class="form-control" required>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">كود الحدث</label>
                            <input type="text" name="custom_alerts[${index}][event_key]" class="form-control" placeholder="مثال: custom_contract_review">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">رابط الصفحة</label>
                            <input type="text" name="custom_alerts[${index}][source_url]" class="form-control" placeholder="/admin/...">
                        </div>
                        <div class="col-lg-8">
                            <label class="form-label">الوصف</label>
                            <input type="text" name="custom_alerts[${index}][description]" class="form-control">
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">المستلم</label>
                            <select name="custom_alerts[${index}][recipient]" class="form-select">${optionList(alertRecipientOptions, 'admin')}</select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">قنوات الإرسال</label>
                            <div class="channel-pills">${channelChecks(index)}</div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">التنبيه قبل</label>
                            <div class="input-group">
                                <input type="number" min="0" max="3650" name="custom_alerts[${index}][lead_time_value]" class="form-control" value="1">
                                <select name="custom_alerts[${index}][lead_time_unit]" class="form-select">${optionList(alertUnitOptions, 'days')}</select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">إعادة التذكير</label>
                            <div class="input-group">
                                <input type="number" min="0" max="3650" name="custom_alerts[${index}][repeat_every_value]" class="form-control" value="0">
                                <select name="custom_alerts[${index}][repeat_every_unit]" class="form-select">${optionList(alertUnitOptions, 'days')}</select>
                            </div>
                            <div class="form-text">0 يعني بدون تكرار.</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">قالب الرسالة</label>
                            <select name="custom_alerts[${index}][template_id]" class="form-select">
                                <option value="">بدون قالب محدد</option>
                                ${optionList(alertTemplateOptions, '')}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renumberCustomAlerts() {
        $('#customAlertsContainer [data-custom-alert-row]').each(function (i) {
            $(this).find('[data-custom-alert-num]').text(i + 1);
        });
    }

    function syncRuleRowState(input) {
        const $label = $(input).siblings('.js-rule-enabled-label');
        $(input).closest('.rule-card').toggleClass('is-disabled', !input.checked);
        $label.text(input.checked ? $label.data('on') : $label.data('off'));
        $('[data-enabled-rules-count]').text($('.alert-rules-list .js-rule-enabled:checked').length);
    }

    $(document).ready(function () {
        $('#addCustomAlert').on('click', function () {
            $('#emptyCustomAlertsHint').remove();
            const $row = $(buildCustomAlertRow(nextCustomAlertIndex()));
            $('#customAlertsContainer').append($row);
            renumberCustomAlerts();
            $row.find('input[type="text"]').first().trigger('focus');
        });

        $(document).on('click', '.js-remove-custom-alert', function () {
            $(this).closest('[data-custom-alert-row]').remove();
            renumberCustomAlerts();
        });

        $('.alert-rules-list .js-rule-enabled').each(function () {
            syncRuleRowState(this);
        }).on('change', function () {
            syncRuleRowState(this);
        });
    });
</script>
@endsection
