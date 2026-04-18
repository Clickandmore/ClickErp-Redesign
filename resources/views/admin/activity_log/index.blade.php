@extends('admin.layout.indexs.index')

@section('content')

    @include('components.session-messages')

    @php
        $filters = $filters ?? [];
        $filterOptions = $filterOptions ?? [];
        $stats = $stats ?? ['total' => 0, 'filtered' => 0, 'active_filters' => 0];
    @endphp

    <style>
        .activity-log-wrap {
            --page-bg: #f5f7f6;
            --surface: #ffffff;
            --surface-muted: #f8faf9;
            --surface-soft: #f0f4f3;
            --border: #dbe4e1;
            --border-strong: #c9d6d1;
            --ink: #192321;
            --muted: #65706d;
            --accent: #176b63;
            --accent-strong: #0f514b;
            --accent-soft: #edf6f4;
            --danger: #b42318;
            --danger-soft: #fff1ef;
            --warn: #9a6700;
            --warn-soft: #fff7e8;
            --neutral-soft: #f3f6f5;
            --shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
            font-family: "Cairo", "Tajawal", "IBM Plex Sans Arabic", "Segoe UI", sans-serif;
            color: var(--ink);
            background: var(--page-bg);
        }

        .activity-log-shell {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .activity-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 18px;
        }

        .activity-hero {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            border-inline-start: 4px solid var(--accent);
        }

        .activity-hero h2 {
            margin: 6px 0 6px;
            font-size: 24px;
            font-weight: 800;
        }

        .activity-hero p {
            margin: 0;
            max-width: 640px;
            color: var(--muted);
            line-height: 1.9;
        }

        .activity-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 10px;
            background: var(--surface-soft);
            border: 1px solid var(--border);
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 800;
        }

        .activity-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            min-width: min(100%, 420px);
        }

        .activity-stat {
            padding: 14px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
        }

        .activity-stat span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .activity-stat strong {
            font-size: 21px;
            font-weight: 800;
            color: var(--accent-strong);
        }

        .activity-section-head {
            margin-bottom: 14px;
        }

        .activity-section-head h3 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 800;
        }

        .activity-section-head p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .activity-filter-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .activity-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
        }

        .activity-filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }

        .activity-filter-group .form-control,
        .activity-filter-group .form-select {
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 11px 14px;
            background: var(--surface);
            box-shadow: none;
        }

        .activity-filter-group .form-control:focus,
        .activity-filter-group .form-select:focus {
            border-color: rgba(23, 107, 99, 0.4);
            box-shadow: 0 0 0 0.18rem rgba(23, 107, 99, 0.08);
        }

        .activity-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .activity-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .activity-btn:hover {
            text-decoration: none;
        }

        .activity-btn.is-primary {
            background: var(--accent);
            border-color: var(--accent);
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

        .activity-active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .activity-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--surface-soft);
            border: 1px solid var(--border);
            font-size: 12px;
            color: var(--ink);
        }

        .activity-chip strong {
            font-weight: 800;
        }

        .activity-stream {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .activity-entry {
            background: var(--surface);
            border: 1px solid var(--border);
            border-inline-start: 4px solid var(--border-strong);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow);
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

        .activity-entry-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .activity-entry-main {
            flex: 1 1 580px;
        }

        .activity-entry-main h3 {
            margin: 10px 0 8px;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.8;
        }

        .activity-entry-main p {
            margin: 0;
            color: var(--muted);
            line-height: 1.9;
        }

        .activity-entry-meta {
            min-width: min(100%, 300px);
            padding: 14px 16px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            display: grid;
            gap: 10px;
        }

        .activity-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .activity-meta-row span {
            color: var(--muted);
        }

        .activity-meta-row strong {
            text-align: left;
            font-weight: 800;
        }

        .activity-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
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

        .activity-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .activity-detail {
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
        }

        .activity-detail span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .activity-detail strong {
            display: block;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.8;
            word-break: break-word;
        }

        .activity-changes {
            padding: 14px;
            border-radius: 14px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
        }

        .activity-changes-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 12px;
        }

        .activity-changes-head h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .activity-change-list {
            display: grid;
            gap: 10px;
        }

        .activity-change-item {
            display: grid;
            grid-template-columns: minmax(120px, 170px) 1fr;
            gap: 12px;
            align-items: start;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--surface);
            border: 1px solid var(--border);
        }

        .activity-change-label {
            font-size: 13px;
            font-weight: 800;
        }

        .activity-change-values {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            color: var(--ink);
            line-height: 1.8;
        }

        .activity-change-box {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 145px;
            max-width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 12px;
            word-break: break-word;
        }

        .activity-change-box small {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
        }

        .activity-change-box strong {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.7;
        }

        .activity-change-box.is-old {
            background: var(--danger-soft);
            border-color: #f3d3cf;
            color: #8d2c31;
        }

        .activity-change-box.is-new {
            background: var(--accent-soft);
            border-color: #d5e6e1;
            color: var(--accent-strong);
        }

        .activity-change-box.is-single {
            background: var(--surface-soft);
        }

        .activity-change-arrow {
            color: var(--muted);
            font-size: 13px;
        }

        .activity-expander {
            margin-top: 12px;
            border-radius: 12px;
            background: var(--surface);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .activity-expander summary {
            cursor: pointer;
            list-style: none;
            padding: 14px 16px;
            font-weight: 800;
            background: var(--surface-soft);
        }

        .activity-expander summary::-webkit-details-marker {
            display: none;
        }

        .activity-expander-body {
            padding: 0 16px 16px;
        }

        .activity-raw {
            margin: 0;
            padding: 14px;
            border-radius: 12px;
            background: var(--surface-soft);
            color: var(--ink);
            border: 1px solid var(--border);
            font-size: 12px;
            line-height: 1.8;
            direction: ltr;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .activity-entry-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .activity-empty {
            text-align: center;
            padding: 38px 22px;
        }

        .activity-empty h3 {
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: 800;
        }

        .activity-empty p {
            margin: 0;
            color: var(--muted);
            line-height: 1.9;
        }

        .activity-pagination .page-item .page-link {
            border: 1px solid var(--border);
            color: var(--ink);
            background: var(--surface);
            border-radius: 999px;
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

        @media (max-width: 768px) {

            .activity-card,
            .activity-entry {
                padding: 14px;
            }

            .activity-hero h2 {
                font-size: 22px;
            }

            .activity-entry-main h3 {
                font-size: 18px;
            }

            .activity-change-item {
                grid-template-columns: 1fr;
            }

            .activity-meta-row {
                flex-direction: column;
                gap: 4px;
            }
        }
    </style>

    <div class="row activity-log-wrap">
        <div class="col-md-12">
            <div class="activity-log-shell">
                <section class="activity-card activity-hero">
                    <div>
                        <span class="activity-eyebrow">
                            <i class="fas fa-stream"></i>
                            سجل النشاط
                        </span>
                        <h2>متابعة ما تم تنفيذه داخل النظام</h2>
                        <p>عرض واضح لكل عملية مع تفاصيلها الكاملة وروابطها المباشرة بدون ازدحام بصري.</p>
                    </div>

                    <div class="activity-stats">
                        <div class="activity-stat">
                            <span>إجمالي السجلات</span>
                            <strong>{{ number_format($stats['total'] ?? 0) }}</strong>
                        </div>
                        <div class="activity-stat">
                            <span>نتائج الفلترة الحالية</span>
                            <strong>{{ number_format($stats['filtered'] ?? 0) }}</strong>
                        </div>
                        <div class="activity-stat">
                            <span>عدد الفلاتر النشطة</span>
                            <strong>{{ number_format($stats['active_filters'] ?? 0) }}</strong>
                        </div>
                    </div>
                </section>

                <section class="activity-card">
                    <div class="activity-section-head">
                        <h3>فلترة السجل</h3>
                        <p>يمكن البحث حسب الموظف أو نوع العملية أو الكيان أو التاريخ.</p>
                    </div>

                    <form method="GET" action="{{ route('dash.activity.log') }}" class="activity-filter-form">
                        <div class="activity-filter-grid">
                            <div class="activity-filter-group">
                                <label for="employee_id">الموظف</label>
                                <select id="employee_id" name="employee_id" class="form-select">
                                    <option value="">الكل</option>
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
                                    <option value="">الكل</option>
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
                                    <option value="">الكل</option>
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
                                <label for="date_from">من تاريخ</label>
                                <input id="date_from" type="date" name="date_from" class="form-control"
                                    value="{{ $filters['date_from'] ?? '' }}">
                            </div>

                            <div class="activity-filter-group">
                                <label for="date_to">إلى تاريخ</label>
                                <input id="date_to" type="date" name="date_to" class="form-control"
                                    value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                        </div>

                        <div class="activity-filter-actions">
                            <button type="submit" class="activity-btn is-primary">
                                <i class="fas fa-filter"></i>
                                تطبيق الفلاتر
                            </button>

                            <a href="{{ route('dash.activity.log') }}" class="activity-btn is-light">
                                <i class="fas fa-rotate-left"></i>
                                إعادة التعيين
                            </a>
                        </div>

                        @if (($stats['active_filters'] ?? 0) > 0)
                            <div class="activity-active-filters">
                                @if (!empty($filters['employee_id']))
                                    @php
                                        $selectedEmployee = collect($filterOptions['employees'] ?? [])->firstWhere(
                                            'id',
                                            (int) $filters['employee_id'],
                                        );
                                    @endphp
                                    <span class="activity-chip">
                                        <strong>الموظف:</strong>
                                        {{ $selectedEmployee->name ?? '#' . $filters['employee_id'] }}
                                    </span>
                                @endif

                                @if (!empty($filters['action']))
                                    <span class="activity-chip">
                                        <strong>العملية:</strong>
                                        {{ $filterOptions['actions'][$filters['action']] ?? $filters['action'] }}
                                    </span>
                                @endif

                                @if (!empty($filters['subject_type']))
                                    <span class="activity-chip">
                                        <strong>السجل:</strong>
                                        {{ $filterOptions['subject_types'][$filters['subject_type']] ?? $filters['subject_type'] }}
                                    </span>
                                @endif

                                @if (!empty($filters['subject_id']))
                                    <span class="activity-chip">
                                        <strong>الرقم:</strong>
                                        #{{ $filters['subject_id'] }}
                                    </span>
                                @endif

                                @if (!empty($filters['date_from']))
                                    <span class="activity-chip">
                                        <strong>من:</strong>
                                        {{ $filters['date_from'] }}
                                    </span>
                                @endif

                                @if (!empty($filters['date_to']))
                                    <span class="activity-chip">
                                        <strong>إلى:</strong>
                                        {{ $filters['date_to'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </form>
                </section>

                @if ($data->count())
                    <section class="activity-stream">
                        @foreach ($data as $activity)
                            @php
                                $isUpdated = ($activity['action'] ?? '') === 'updated';
                            @endphp
                            <article class="activity-entry {{ $activity['action_class'] }}">
                                <div class="activity-entry-head">
                                    <div class="activity-entry-main">
                                        <span class="activity-badge {{ $activity['action_class'] }}">
                                            <i class="fas fa-bolt"></i>
                                            {{ $activity['action_label'] }}
                                        </span>

                                        <h3>{{ $activity['summary'] }}</h3>
                                        <p>{{ $activity['description'] }}</p>
                                    </div>

                                    <aside class="activity-entry-meta">
                                        <div class="activity-meta-row">
                                            <span>منفذ العملية</span>
                                            <strong>{{ $activity['actor_name'] }}</strong>
                                        </div>
                                        <div class="activity-meta-row">
                                            <span>الصفة</span>
                                            <strong>{{ $activity['actor_role'] }}</strong>
                                        </div>
                                        <div class="activity-meta-row">
                                            <span>وقت التنفيذ</span>
                                            <strong>{{ $activity['created_at_label'] }}</strong>
                                        </div>
                                        <div class="activity-meta-row">
                                            <span>منذ</span>
                                            <strong>{{ $activity['created_at_relative'] ?? '-' }}</strong>
                                        </div>
                                        <div class="activity-meta-row">
                                            <span>رقم سجل النشاط</span>
                                            <strong>#{{ $activity['id'] }}</strong>
                                        </div>
                                    </aside>
                                </div>

                                <div class="activity-detail-grid">
                                    <div class="activity-detail">
                                        <span>نوع الكيان</span>
                                        <strong>{{ $activity['subject_type_label'] }}</strong>
                                    </div>

                                    @if (!empty($activity['subject_id']))
                                        <div class="activity-detail">
                                            <span>المعرف</span>
                                            <strong>#{{ $activity['subject_id'] }}</strong>
                                        </div>
                                    @endif

                                    @if (!empty($activity['subject_descriptor']))
                                        <div class="activity-detail">
                                            <span>مرجع إضافي</span>
                                            <strong>{{ $activity['subject_descriptor'] }}</strong>
                                        </div>
                                    @endif

                                    @if (!empty($activity['changes_count']))
                                        <div class="activity-detail">
                                            <span>عدد التغييرات</span>
                                            <strong>{{ $activity['changes_count'] }}</strong>
                                        </div>
                                    @endif
                                </div>

                                @if (!empty($activity['changes_preview']))
                                    <div class="activity-changes">
                                        <div class="activity-changes-head">
                                            <h4>أهم التغييرات المسجلة</h4>
                                            @if (($activity['changes_hidden_count'] ?? 0) > 0)
                                                <span class="activity-chip">
                                                    +{{ $activity['changes_hidden_count'] }} تغيير إضافي
                                                </span>
                                            @endif
                                        </div>

                                        <div class="activity-change-list">
                                            @foreach ($activity['changes_preview'] as $change)
                                                <div class="activity-change-item">
                                                    <div class="activity-change-label">{{ $change['label'] }}</div>
                                                    <div class="activity-change-values">
                                                        @if ($isUpdated)
                                                            <span class="activity-change-box is-old">
                                                                <small>قبل</small>
                                                                <strong>{{ $change['old_display'] }}</strong>
                                                            </span>
                                                            <span class="activity-change-arrow">
                                                                <i class="fas fa-arrow-left"></i>
                                                            </span>
                                                            <span class="activity-change-box is-new">
                                                                <small>بعد</small>
                                                                <strong>{{ $change['new_display'] }}</strong>
                                                            </span>
                                                        @else
                                                            <span class="activity-change-box is-single">
                                                                <small>القيمة</small>
                                                                <strong>
                                                                    {{ $change['new_display'] !== 'غير محدد' ? $change['new_display'] : $change['old_display'] }}
                                                                </strong>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if (!empty($activity['changes_extra']))
                                            <details class="activity-expander">
                                                <summary>عرض بقية التغييرات</summary>
                                                <div class="activity-expander-body">
                                                    <div class="activity-change-list">
                                                        @foreach ($activity['changes_extra'] as $change)
                                                            <div class="activity-change-item">
                                                                <div class="activity-change-label">{{ $change['label'] }}
                                                                </div>
                                                                <div class="activity-change-values">
                                                                    @if ($isUpdated)
                                                                        <span class="activity-change-box is-old">
                                                                            <small>قبل</small>
                                                                            <strong>{{ $change['old_display'] }}</strong>
                                                                        </span>
                                                                        <span class="activity-change-arrow">
                                                                            <i class="fas fa-arrow-left"></i>
                                                                        </span>
                                                                        <span class="activity-change-box is-new">
                                                                            <small>بعد</small>
                                                                            <strong>{{ $change['new_display'] }}</strong>
                                                                        </span>
                                                                    @else
                                                                        <span class="activity-change-box is-single">
                                                                            <small>القيمة</small>
                                                                            <strong>
                                                                                {{ $change['new_display'] !== 'غير محدد' ? $change['new_display'] : $change['old_display'] }}
                                                                            </strong>
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    @if (($activity['changes_raw_hidden_count'] ?? 0) > 0)
                                                        <div class="mt-3 text-muted small">
                                                            يوجد {{ $activity['changes_raw_hidden_count'] }} حقل إضافي داخل
                                                            البيانات الخام أدناه.
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endif

                                <div class="activity-entry-actions">
                                    @if (!empty($activity['target_url']))
                                        <a href="{{ $activity['target_url'] }}" class="activity-btn is-primary">
                                            <i class="fas fa-arrow-up-right-from-square"></i>
                                            {{ $activity['target_label'] }}
                                        </a>
                                    @endif

                                    @if (!empty($activity['properties_pretty']))
                                        <details class="activity-expander" style="flex: 1 1 100%;">
                                            <summary>عرض البيانات الخام للسجل</summary>
                                            <div class="activity-expander-body">
                                                <pre class="activity-raw">{{ $activity['properties_pretty'] }}</pre>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </section>
                @else
                    <section class="activity-card activity-empty">
                        <h3>لا توجد سجلات مطابقة</h3>
                        <p>
                            لم يتم العثور على نتائج وفق الفلاتر الحالية. جرّب إزالة بعض الفلاتر أو تحديد فترة زمنية أوسع.
                        </p>
                    </section>
                @endif

                @if ($data->hasPages())
                    <nav aria-label="Page navigation" class="mt-2">
                        <ul class="pagination justify-content-center activity-pagination">
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
