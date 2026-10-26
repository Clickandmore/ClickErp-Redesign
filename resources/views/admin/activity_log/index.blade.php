@extends('admin.layout.indexs.index')

@section('content')

    @include('components.session-messages')

    @php
        $filters = $filters ?? [];
        $filterOptions = $filterOptions ?? [];
        $stats = $stats ?? ['total' => 0, 'filtered' => 0, 'active_filters' => 0];
        $maxExportRows = $maxExportRows ?? 50000;
        $maxPdfRows = $maxPdfRows ?? 500;
        $canExport = @AdminGuardHasPerm(\App\PermKeys::ACTIVITY_EXPORT);

        $selectedEmployee = !empty($filters['employee_id'])
            ? collect($filterOptions['employees'] ?? [])->firstWhere('id', (int) $filters['employee_id'])
            : null;

        // كل فلتر نشط مع رابط يزيله وحده ويحافظ على البقية
        $chipDefinitions = [
            'log_name' => ['المصدر', fn($v) => $filterOptions['log_names'][$v] ?? $v],
            'employee_id' => ['الموظف', fn($v) => $selectedEmployee->name ?? '#' . $v],
            'action' => ['العملية', fn($v) => $filterOptions['actions'][$v] ?? $v],
            'subject_type' => ['نوع السجل', fn($v) => $filterOptions['subject_types'][$v] ?? $v],
            'subject_id' => ['الرقم', fn($v) => '#' . $v],
            'date_from' => ['من', fn($v) => $v],
            'date_to' => ['إلى', fn($v) => $v],
        ];
        $activeChips = [];
        foreach ($chipDefinitions as $key => [$chipLabel, $formatter]) {
            if (filled($filters[$key] ?? null)) {
                $activeChips[] = [
                    'label' => $chipLabel,
                    'value' => $formatter($filters[$key]),
                    'remove_url' => route('dash.activity.log', \Illuminate\Support\Arr::except($filters, [$key])),
                ];
            }
        }

        $filtered = (int) ($stats['filtered'] ?? 0);
        $rangeFrom = $data->firstItem() ?? 0;
        $rangeTo = $data->lastItem() ?? 0;
    @endphp

    <style>
        .activity-log-wrap {
            --surface: #ffffff;
            --surface-muted: #f8faf9;
            --surface-soft: #f0f4f3;
            --border: #e1e8e5;
            --border-strong: #c9d6d1;
            --ink: #192321;
            --muted: #66716e;
            --accent: #176b63;
            --accent-strong: #0f514b;
            --accent-soft: #edf6f4;
            --danger: #b42318;
            --danger-soft: #fff1ef;
            --warn: #b07a00;
            --warn-soft: #fff7e8;
            --neutral-soft: #f3f6f5;
            --shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 6px 16px rgba(15, 23, 42, 0.04);
            font-family: "Cairo", "Tajawal", "IBM Plex Sans Arabic", "Segoe UI", sans-serif;
            color: var(--ink);
        }

        .activity-log-shell {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .activity-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 18px 20px;
        }

        /* ---------- Header ---------- */
        .activity-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .activity-header-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .activity-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 20px;
            flex-shrink: 0;
        }

        .activity-header h2 {
            margin: 0 0 2px;
            font-size: 21px;
            font-weight: 800;
        }

        .activity-header p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .activity-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .activity-stat {
            min-width: 130px;
            padding: 10px 14px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
        }

        .activity-stat span {
            display: block;
            font-size: 12px;
            color: var(--muted);
        }

        .activity-stat strong {
            font-size: 19px;
            font-weight: 800;
            color: var(--accent-strong);
        }

        /* ---------- Filters ---------- */
        .activity-filter-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .activity-filter-head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .activity-filter-head h3 i {
            color: var(--accent);
        }

        .activity-count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: var(--accent);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .activity-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px 14px;
        }

        .activity-filter-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
        }

        .activity-filter-group .form-control,
        .activity-filter-group .form-select {
            height: 42px;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 0 12px;
            background-color: var(--surface);
            box-shadow: none;
            font-size: 14px;
        }

        .activity-filter-group .form-control:focus,
        .activity-filter-group .form-select:focus {
            border-color: rgba(23, 107, 99, 0.45);
            box-shadow: 0 0 0 0.18rem rgba(23, 107, 99, 0.1);
        }

        .activity-date-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .activity-filter-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dashed var(--border);
        }

        .activity-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .activity-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            white-space: nowrap;
        }

        .activity-btn:hover {
            text-decoration: none;
        }

        .activity-btn.is-sm {
            height: 34px;
            padding: 0 12px;
            font-size: 13px;
        }

        .activity-btn.is-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .activity-btn.is-primary:hover {
            background: var(--accent-strong);
            border-color: var(--accent-strong);
            color: #fff;
        }

        .activity-btn.is-light {
            background: var(--surface);
            color: var(--ink);
            border-color: var(--border);
        }

        .activity-btn.is-light:hover {
            border-color: var(--accent);
            color: var(--accent-strong);
        }

        .activity-btn.is-export {
            background: #eef7f1;
            color: #1e6b3a;
            border-color: #cfe6d7;
        }

        .activity-btn.is-export:hover {
            background: #1e7b43;
            border-color: #1e7b43;
            color: #fff;
        }

        .activity-btn.is-pdf {
            background: #fdf1f0;
            color: #a1281f;
            border-color: #f3d3cf;
        }

        .activity-btn.is-pdf:hover {
            background: #b42318;
            border-color: #b42318;
            color: #fff;
        }

        .activity-btn[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .activity-active-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .activity-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 6px 5px 10px;
            border-radius: 999px;
            background: var(--accent-soft);
            border: 1px solid #d5e6e1;
            font-size: 12px;
            color: var(--accent-strong);
        }

        .activity-chip strong {
            font-weight: 800;
        }

        .activity-chip a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            color: var(--accent-strong);
            text-decoration: none;
            font-size: 11px;
        }

        .activity-chip a:hover {
            background: var(--accent);
            color: #fff;
        }

        .activity-hint {
            color: var(--muted);
            font-size: 12px;
        }

        .activity-tip {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 14px;
            padding: 12px 14px;
            background: var(--warn-soft);
            border: 1px solid #f3e2bd;
            border-inline-start: 4px solid var(--warn);
            border-radius: 10px;
        }

        .activity-tip-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: var(--warn);
            font-size: 14px;
            box-shadow: 0 1px 2px rgba(176, 122, 0, 0.15);
        }

        .activity-tip-title {
            font-weight: 700;
            font-size: 13px;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .activity-tip-list {
            margin: 0;
            padding-inline-start: 18px;
            color: var(--muted);
            font-size: 12.5px;
            line-height: 1.8;
        }

        .activity-tip-list strong {
            color: var(--ink);
            font-weight: 600;
        }

        /* ---------- Results toolbar ---------- */
        .activity-results-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 0 4px;
            color: var(--muted);
            font-size: 13px;
        }

        .activity-results-bar strong {
            color: var(--ink);
        }

        /* ---------- Timeline ---------- */
        .activity-day {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .activity-day-head {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 800;
            color: var(--accent-strong);
        }

        .activity-day-head::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .activity-day-head span {
            padding: 4px 12px;
            border-radius: 999px;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .activity-entry {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-inline-start: 4px solid var(--border-strong);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 14px 16px;
        }

        .activity-entry.is-created {
            border-inline-start-color: var(--accent);
        }

        .activity-entry.is-updated {
            border-inline-start-color: var(--warn);
        }

        .activity-entry.is-deleted {
            border-inline-start-color: var(--danger);
        }

        .activity-entry-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .activity-entry-main {
            min-width: 0;
            flex: 1;
        }

        .activity-entry-title {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .activity-entry-title h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.8;
        }

        .activity-entry-desc {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .activity-entry-time {
            flex-shrink: 0;
            text-align: end;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
        }

        .activity-entry-time strong {
            display: block;
            font-size: 14px;
            color: var(--ink);
            font-weight: 800;
            direction: ltr;
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 16px;
            margin-top: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .activity-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .activity-meta i {
            color: var(--border-strong);
        }

        .activity-meta b {
            color: var(--ink);
            font-weight: 700;
        }

        .activity-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .activity-badge.is-created {
            background: var(--accent-soft);
            border-color: #d5e6e1;
            color: var(--accent-strong);
        }

        .activity-badge.is-updated {
            background: var(--warn-soft);
            border-color: #ead9b6;
            color: #815700;
        }

        .activity-badge.is-deleted {
            background: var(--danger-soft);
            border-color: #f3d3cf;
            color: #912e33;
        }

        .activity-badge.is-generic {
            background: var(--neutral-soft);
            border-color: var(--border);
            color: var(--ink);
        }

        .activity-changes {
            margin-top: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .activity-changes table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: 13px;
        }

        .activity-changes th {
            background: var(--surface-soft);
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            padding: 8px 12px;
            text-align: start;
        }

        .activity-changes td {
            padding: 8px 12px;
            border-top: 1px solid var(--border);
            vertical-align: top;
            word-break: break-word;
        }

        .activity-changes td.is-label {
            width: 26%;
            font-weight: 700;
        }

        .activity-changes td.is-old {
            color: #8d2c31;
            background: #fffafa;
        }

        .activity-changes td.is-old span {
            text-decoration: line-through;
            text-decoration-color: rgba(141, 44, 49, 0.35);
        }

        .activity-changes td.is-new {
            color: var(--accent-strong);
            background: #fbfefd;
            font-weight: 700;
        }

        .activity-entry-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
        }

        .activity-expander {
            flex: 1 1 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .activity-expander summary {
            cursor: pointer;
            list-style: none;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            color: var(--accent-strong);
            background: var(--surface-muted);
        }

        .activity-expander summary::-webkit-details-marker {
            display: none;
        }

        .activity-expander summary::before {
            content: "\f104";
            font-family: "Font Awesome 6 Free", "Font Awesome 5 Free";
            font-weight: 900;
            display: inline-block;
            margin-inline-end: 8px;
            transition: transform 0.15s ease;
        }

        .activity-expander[open] summary::before {
            transform: rotate(-90deg);
        }

        .activity-expander-body {
            padding: 10px 12px 12px;
        }

        .activity-expander-body .activity-changes {
            margin-top: 0;
        }

        .activity-raw {
            margin: 0;
            padding: 12px;
            border-radius: 10px;
            background: var(--surface-soft);
            color: var(--ink);
            border: 1px solid var(--border);
            font-size: 12px;
            line-height: 1.7;
            direction: ltr;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 360px;
            overflow: auto;
        }

        .activity-empty {
            text-align: center;
            padding: 44px 22px;
        }

        .activity-empty i {
            font-size: 34px;
            color: var(--border-strong);
            margin-bottom: 12px;
        }

        .activity-empty h3 {
            margin-bottom: 8px;
            font-size: 19px;
            font-weight: 800;
        }

        .activity-empty p {
            margin: 0 0 16px;
            color: var(--muted);
        }

        .activity-pagination .page-item .page-link {
            border: 1px solid var(--border);
            color: var(--ink);
            background: var(--surface);
            border-radius: 10px;
            margin: 0 2px;
            min-width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .activity-pagination .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .activity-pagination .page-item .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .activity-pagination .page-item.disabled .page-link {
            color: var(--muted);
            background: var(--surface-soft);
        }

        .activity-pagination .page-ellipsis .page-link {
            border: 0;
            background: transparent;
            min-width: auto;
            padding: 0 6px;
        }

        @media (max-width: 1200px) {
            .activity-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .activity-card {
                padding: 14px;
            }

            .activity-filter-grid {
                grid-template-columns: 1fr;
            }

            .activity-stat {
                flex: 1 1 100px;
                min-width: 0;
            }

            .activity-entry-top {
                flex-direction: column;
            }

            .activity-entry-time {
                text-align: start;
            }

            .activity-changes td.is-label {
                width: auto;
            }
        }
    </style>

    <div class="row activity-log-wrap">
        <div class="col-md-12">
            <div class="activity-log-shell">

                {{-- العنوان والإحصائيات --}}
                <section class="activity-card activity-header">
                    <div class="activity-header-title">
                        <span class="activity-header-icon"><i class="fas fa-stream"></i></span>
                        <div>
                            <h2>سجل النشاط</h2>
                            <p>متابعة كل عملية تمت داخل النظام: من نفذها، ومتى، وما الذي تغيّر.</p>
                        </div>
                    </div>

                    <div class="activity-stats">
                        <div class="activity-stat">
                            <span>إجمالي السجلات</span>
                            <strong>{{ number_format($stats['total'] ?? 0) }}</strong>
                        </div>
                        <div class="activity-stat">
                            <span>نتائج الفلترة</span>
                            <strong>{{ number_format($filtered) }}</strong>
                        </div>
                        <div class="activity-stat">
                            <span>فلاتر نشطة</span>
                            <strong>{{ number_format($stats['active_filters'] ?? 0) }}</strong>
                        </div>
                    </div>
                </section>

                {{-- الفلاتر والتصدير --}}
                <section class="activity-card">
                    <form method="GET" action="{{ route('dash.activity.log') }}" id="activity-filter-form">
                        <div class="activity-filter-head">
                            <h3>
                                <i class="fas fa-sliders-h"></i>
                                فلترة السجل
                                @if (($stats['active_filters'] ?? 0) > 0)
                                    <span class="activity-count-pill">{{ $stats['active_filters'] }}</span>
                                @endif
                            </h3>
                        </div>

                        <div class="activity-filter-grid">
                            <div class="activity-filter-group">
                                <label for="employee_id">الموظف</label>
                                <select id="employee_id" name="employee_id" class="form-select">
                                    <option value="">كل الموظفين</option>
                                    @foreach ($filterOptions['employees'] ?? collect() as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ (string) ($filters['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="activity-filter-group">
                                <label for="action">نوع العملية</label>
                                <select id="action" name="action" class="form-select">
                                    <option value="">كل العمليات</option>
                                    @foreach ($filterOptions['actions'] ?? [] as $actionValue => $actionLabel)
                                        <option value="{{ $actionValue }}"
                                            {{ (string) ($filters['action'] ?? '') === (string) $actionValue ? 'selected' : '' }}>
                                            {{ $actionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="activity-filter-group">
                                <label for="subject_type">نوع السجل</label>
                                <select id="subject_type" name="subject_type" class="form-select">
                                    <option value="">كل الأنواع</option>
                                    @foreach ($filterOptions['subject_types'] ?? [] as $subjectTypeValue => $subjectTypeLabel)
                                        <option value="{{ $subjectTypeValue }}"
                                            {{ (string) ($filters['subject_type'] ?? '') === (string) $subjectTypeValue ? 'selected' : '' }}>
                                            {{ $subjectTypeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="activity-filter-group">
                                <label for="subject_id">رقم السجل المرتبط</label>
                                <input id="subject_id" type="number" min="1" name="subject_id" class="form-control"
                                    placeholder="مثال: 2203" value="{{ $filters['subject_id'] ?? '' }}">
                            </div>

                            <div class="activity-filter-group">
                                <label for="log_name">مصدر السجل</label>
                                <select id="log_name" name="log_name" class="form-select">
                                    <option value="">كل المصادر</option>
                                    @foreach ($filterOptions['log_names'] ?? [] as $logNameValue => $logNameLabel)
                                        <option value="{{ $logNameValue }}"
                                            {{ (string) ($filters['log_name'] ?? '') === (string) $logNameValue ? 'selected' : '' }}>
                                            {{ $logNameLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="activity-filter-group">
                                <label for="date_from">الفترة (من - إلى)</label>
                                <div class="activity-date-range">
                                    <input id="date_from" type="date" name="date_from" class="form-control"
                                        value="{{ $filters['date_from'] ?? '' }}" aria-label="من تاريخ">
                                    <input id="date_to" type="date" name="date_to" class="form-control"
                                        value="{{ $filters['date_to'] ?? '' }}" aria-label="إلى تاريخ">
                                </div>
                            </div>
                        </div>

                        <div class="activity-filter-footer">
                            <div class="activity-active-filters">
                                @forelse ($activeChips as $chip)
                                    <span class="activity-chip">
                                        <strong>{{ $chip['label'] }}:</strong>
                                        {{ $chip['value'] }}
                                        <a href="{{ $chip['remove_url'] }}" title="إزالة الفلتر"><i class="fas fa-times"></i></a>
                                    </span>
                                @empty
                                    <span class="activity-hint">لا توجد فلاتر مطبقة — يتم عرض كل السجلات.</span>
                                @endforelse
                            </div>

                            <div class="activity-filter-actions">
                                <button type="submit" class="activity-btn is-primary">
                                    <i class="fas fa-filter"></i>
                                    تطبيق
                                </button>

                                @if (($stats['active_filters'] ?? 0) > 0)
                                    <a href="{{ route('dash.activity.log') }}" class="activity-btn is-light">
                                        <i class="fas fa-rotate-left"></i>
                                        إعادة التعيين
                                    </a>
                                @endif

                                @if ($canExport)
                                    {{-- يرسل قيم الفلاتر الموجودة في النموذج مباشرة إلى رابط التصدير --}}
                                    <button type="submit" class="activity-btn is-export"
                                        formaction="{{ route('dash.activity.log.export') }}"
                                        title="تصدير السجلات المطابقة للفلاتر (حتى {{ number_format($maxExportRows) }} سجل)"
                                        {{ $filtered === 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-file-excel"></i>
                                        تصدير Excel
                                    </button>
                                    <button type="submit" class="activity-btn is-pdf"
                                        formaction="{{ route('dash.activity.log.export-pdf') }}"
                                        title="تصدير أحدث {{ number_format($maxPdfRows) }} سجل مطابق للفلاتر"
                                        {{ $filtered === 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-file-pdf"></i>
                                        تصدير PDF
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="activity-tip">
                            <span class="activity-tip-icon"><i class="fas fa-lightbulb"></i></span>
                            <div>
                                <div class="activity-tip-title">نصيحة لأداء أفضل</div>
                                <ul class="activity-tip-list">
                                    <li>سجل النشاط كبير الحجم، لذلك يُفضّل <strong>فلترة الصفحة</strong> (بفترة زمنية أو موظف أو نوع سجل) قبل التصفح.</li>
                                    @if ($canExport)
                                        <li>صدّر البيانات <strong>على أجزاء</strong> — مثلاً شهراً بشهر — بدل تصدير السجل كاملاً دفعة واحدة.</li>
                                    @endif
                                    <li>ذلك أسرع ويمنع توقف الصفحة.</li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </section>

                @if ($data->count())
                    <div class="activity-results-bar">
                        <div>
                            عرض <strong>{{ number_format($rangeFrom) }}–{{ number_format($rangeTo) }}</strong>
                            من أصل <strong>{{ number_format($filtered) }}</strong> سجل
                        </div>
                        @if ($canExport && $filtered > $maxPdfRows)
                            <div class="activity-hint">
                                <i class="fas fa-circle-info"></i>
                                @if ($filtered > $maxExportRows)
                                    تصدير Excel يشمل أحدث {{ number_format($maxExportRows) }} سجل، وPDF أحدث {{ number_format($maxPdfRows) }} — ضيّق الفترة لتصدير الباقي.
                                @else
                                    ملف PDF يشمل أحدث {{ number_format($maxPdfRows) }} سجل فقط، وExcel يشمل كل النتائج.
                                @endif
                            </div>
                        @endif
                    </div>

                    @foreach ($data->getCollection()->groupBy('day_key') as $dayEntries)
                        <section class="activity-day">
                            <div class="activity-day-head">
                                <span><i class="far fa-calendar"></i> {{ $dayEntries->first()['day_label'] }}</span>
                            </div>

                            @foreach ($dayEntries as $activity)
                                @php
                                    $isUpdated = ($activity['action'] ?? '') === 'updated';
                                @endphp
                                <article class="activity-entry {{ $activity['action_class'] }}">
                                    <div class="activity-entry-top">
                                        <div class="activity-entry-main">
                                            <div class="activity-entry-title">
                                                <span class="activity-badge {{ $activity['action_class'] }}">
                                                    {{ $activity['action_label'] }}
                                                </span>
                                                <h3>{{ $activity['summary'] }}</h3>
                                            </div>
                                            <p class="activity-entry-desc">{{ $activity['description'] }}</p>
                                        </div>

                                        <div class="activity-entry-time" title="{{ $activity['created_at_label'] }}">
                                            <strong>{{ $activity['created_at_time'] }}</strong>
                                            {{ $activity['created_at_relative'] ?? '' }}
                                        </div>
                                    </div>

                                    <div class="activity-meta">
                                        <span><i class="fas fa-user"></i> <b>{{ $activity['actor_name'] }}</b>
                                            ({{ $activity['actor_role'] }})</span>
                                        <span><i class="fas fa-folder"></i> {{ $activity['subject_type_label'] }}
                                            @if (!empty($activity['subject_id']))
                                                <b>#{{ $activity['subject_id'] }}</b>
                                            @endif
                                        </span>
                                        @if (!empty($activity['subject_descriptor']))
                                            <span><i class="fas fa-tag"></i> {{ $activity['subject_descriptor'] }}</span>
                                        @endif
                                        <span><i class="fas fa-hashtag"></i> سجل النشاط {{ $activity['id'] }}</span>
                                    </div>

                                    @if (!empty($activity['changes_preview']))
                                        <div class="activity-changes">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>الحقل</th>
                                                        @if ($isUpdated)
                                                            <th>قبل</th>
                                                            <th>بعد</th>
                                                        @else
                                                            <th>القيمة</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($activity['changes_preview'] as $change)
                                                        @include('admin.activity_log.partials.change-row', ['change' => $change, 'isUpdated' => $isUpdated])
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    @if (!empty($activity['target_url']) || !empty($activity['changes_extra']) || !empty($activity['properties_pretty']))
                                        <div class="activity-entry-footer">
                                            @if (!empty($activity['target_url']))
                                                <a href="{{ $activity['target_url'] }}" class="activity-btn is-light is-sm">
                                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                                    {{ $activity['target_label'] }}
                                                </a>
                                            @endif

                                            @if (!empty($activity['changes_extra']))
                                                <details class="activity-expander">
                                                    <summary>عرض {{ $activity['changes_hidden_count'] }} تغيير إضافي</summary>
                                                    <div class="activity-expander-body">
                                                        <div class="activity-changes">
                                                            <table>
                                                                <tbody>
                                                                    @foreach ($activity['changes_extra'] as $change)
                                                                        @include('admin.activity_log.partials.change-row', ['change' => $change, 'isUpdated' => $isUpdated])
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        @if (($activity['changes_raw_hidden_count'] ?? 0) > 0)
                                                            <div class="mt-2 activity-hint">
                                                                يوجد {{ $activity['changes_raw_hidden_count'] }} حقل إضافي داخل البيانات الخام.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </details>
                                            @endif

                                            @if (!empty($activity['properties_pretty']))
                                                <details class="activity-expander">
                                                    <summary>البيانات الخام للسجل</summary>
                                                    <div class="activity-expander-body">
                                                        <pre class="activity-raw">{{ $activity['properties_pretty'] }}</pre>
                                                    </div>
                                                </details>
                                            @endif
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </section>
                    @endforeach
                @else
                    <section class="activity-card activity-empty">
                        <i class="fas fa-magnifying-glass"></i>
                        <h3>لا توجد سجلات مطابقة</h3>
                        <p>لم يتم العثور على نتائج وفق الفلاتر الحالية. جرّب إزالة بعض الفلاتر أو تحديد فترة زمنية أوسع.</p>
                        @if (($stats['active_filters'] ?? 0) > 0)
                            <a href="{{ route('dash.activity.log') }}" class="activity-btn is-light">
                                <i class="fas fa-rotate-left"></i>
                                إعادة التعيين
                            </a>
                        @endif
                    </section>
                @endif

                @if ($data->hasPages())
                    <nav aria-label="Page navigation" class="mt-2">
                        <ul class="pagination justify-content-center flex-wrap activity-pagination">
                            @if ($data->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $data->previousPageUrl() }}"
                                        rel="prev">&laquo;</a></li>
                            @endif

                            @php
                                $current = $data->currentPage();
                                $last = $data->lastPage();
                                $start = max(1, $current - 2);
                                $end = min($last, $current + 2);
                            @endphp

                            @if ($start > 1)
                                <li class="page-item"><a class="page-link" href="{{ $data->url(1) }}">1</a></li>
                                @if ($start > 2)
                                    <li class="page-item page-ellipsis"><span class="page-link">…</span></li>
                                @endif
                            @endif

                            @foreach ($data->getUrlRange($start, $end) as $page => $url)
                                @if ($page == $current)
                                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link"
                                            href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($end < $last)
                                @if ($end < $last - 1)
                                    <li class="page-item page-ellipsis"><span class="page-link">…</span></li>
                                @endif
                                <li class="page-item"><a class="page-link"
                                        href="{{ $data->url($last) }}">{{ $last }}</a></li>
                            @endif

                            @if ($data->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}"
                                        rel="next">&raquo;</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                            @endif
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    </div>

@endsection

@include('components.sweetalert')
