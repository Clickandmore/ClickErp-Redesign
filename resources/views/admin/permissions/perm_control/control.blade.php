@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.permissions') }}
@endsection

@section('css')
    <style>
        .permissions-page {
            color: #1f2937;
            --permissions-border: #e5e7eb;
            --permissions-border-strong: #d1d5db;
            --permissions-surface: #f8fafc;
            --permissions-surface-alt: #f3f4f6;
            --permissions-text-muted: #6b7280;
            --permissions-accent: #0f766e;
            --permissions-selected: #f0fdfa;
        }

        .permissions-shell {
            background: #ffffff;
            border: 1px solid var(--permissions-border);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .permissions-toolbar {
            padding: 20px;
            border-bottom: 1px solid var(--permissions-border);
            background: linear-gradient(180deg, #fcfffe 0%, #f8fafc 100%);
        }

        .permissions-title {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .permissions-subtitle {
            margin: 6px 0 0;
            color: var(--permissions-text-muted);
            line-height: 1.7;
        }

        .permissions-toolbar-grid {
            display: grid;
            grid-template-columns: minmax(230px, 320px) minmax(260px, 1fr);
            gap: 12px;
            margin-top: 18px;
        }

        .permissions-toolbar select,
        .permissions-toolbar input {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--permissions-border-strong);
            border-radius: 12px;
            background: #ffffff;
            color: #1f2937;
            padding: 0 14px;
        }

        .permissions-toolbar select:focus,
        .permissions-toolbar input:focus {
            outline: none;
            border-color: #9ca3af;
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.08);
        }

        .permissions-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .permissions-toolbar-actions .btn,
        .permissions-footer .btn {
            min-height: 42px;
            border-radius: 12px;
            font-weight: 700;
            padding: 0 16px;
        }

        .permissions-toolbar-actions .btn-primary,
        .permissions-footer .btn-success {
            background: #0f766e;
            border-color: #0f766e;
            color: #ffffff;
        }

        .permissions-toolbar-actions .btn-primary:hover,
        .permissions-footer .btn-success:hover {
            background: #115e59;
            border-color: #115e59;
        }

        .permissions-toolbar-actions .btn-outline-danger {
            color: #475569;
            border-color: var(--permissions-border-strong);
        }

        .permissions-toolbar-actions .btn-outline-danger:hover {
            background: var(--permissions-surface-alt);
            color: #1f2937;
            border-color: #9ca3af;
        }

        .permissions-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .permissions-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--permissions-border);
            background: #ffffff;
            color: #334155;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .permissions-pill.is-success {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        .permissions-pill.is-warning {
            background: #fff7ed;
            border-color: #fdba74;
            color: #c2410c;
        }

        .permissions-body {
            padding: 20px;
        }

        .permission-lane {
            margin-bottom: 24px;
            --lane-accent: #0f766e;
            --lane-accent-strong: #115e59;
            --lane-surface: #ecfeff;
            --lane-border: #99f6e4;
        }

        .permission-lane:last-child {
            margin-bottom: 0;
        }

        .permission-lane[data-lane-tone="packages"] {
            --lane-accent: #c2410c;
            --lane-accent-strong: #9a3412;
            --lane-surface: #fff7ed;
            --lane-border: #fdba74;
        }

        .permission-lane[data-lane-tone="management"] {
            --lane-accent: #4f46e5;
            --lane-accent-strong: #3730a3;
            --lane-surface: #eef2ff;
            --lane-border: #c7d2fe;
        }

        .permission-lane[data-lane-tone="neutral"] {
            --lane-accent: #475569;
            --lane-accent-strong: #334155;
            --lane-surface: #f8fafc;
            --lane-border: #cbd5e1;
        }

        .permission-lane__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border: 1px solid var(--lane-border);
            border-radius: 18px;
            background: linear-gradient(135deg, var(--lane-surface) 0%, #ffffff 100%);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.03);
        }

        .permission-lane__eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid var(--lane-border);
            background: #ffffff;
            color: var(--lane-accent-strong);
            font-size: 0.78rem;
            font-weight: 800;
        }

        .permission-lane__title {
            margin: 10px 0 6px;
            font-size: 1.08rem;
            font-weight: 850;
            color: var(--lane-accent-strong);
        }

        .permission-lane__description {
            margin: 0;
            color: #64748b;
            line-height: 1.7;
        }

        .permission-lane__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 16px;
        }

        .permission-section-card {
            border: 1px solid var(--permissions-border);
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
        }

        .permission-section-card__header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: start;
            padding: 18px;
            background: linear-gradient(180deg, #ffffff 0%, var(--permissions-surface-alt) 100%);
            border-bottom: 1px solid var(--permissions-border);
        }

        .permission-section-card__heading {
            min-width: 0;
        }

        .permission-section-card__toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: 0;
            color: var(--permissions-accent);
            padding: 0;
            font-size: 1.02rem;
            font-weight: 850;
            text-align: right;
            flex: 1;
        }

        .permission-section-card__toggle::before {
            content: "";
            width: 11px;
            height: 11px;
            border-left: 2px solid #6b7280;
            border-bottom: 2px solid #6b7280;
            transform: rotate(-45deg);
            transition: transform 0.16s ease;
            flex-shrink: 0;
            margin-top: -3px;
        }

        .permission-section-card__toggle[aria-expanded="true"]::before {
            transform: rotate(-225deg);
            margin-top: 3px;
        }

        .permission-section-card__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            align-self: start;
        }

        .permission-section-card__hint {
            margin: 10px 0 0;
            color: var(--permissions-text-muted);
            font-size: 0.84rem;
            line-height: 1.7;
        }

        .permission-section-card__actions {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px dashed var(--permissions-border);
        }

        .permission-section-card__body {
            padding: 16px;
            background: var(--permissions-surface);
        }

        .permission-group {
            border: 1px solid var(--permissions-border);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            margin-bottom: 14px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }

        .permission-group:last-child {
            margin-bottom: 0;
        }

        .permission-group.permission-group--simple .permission-group__header {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .permission-group__header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 12px;
            padding: 14px 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--permissions-border);
        }

        .permission-group__heading {
            min-width: 0;
        }

        .permission-group__title {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 850;
            color: #334155;
        }

        .permission-group__subtitle {
            margin: 6px 0 0;
            color: var(--permissions-text-muted);
            font-size: 0.82rem;
            line-height: 1.6;
        }

        .permission-group__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .scope-btn {
            min-height: 36px;
            border: 1px solid var(--permissions-border-strong);
            background: #ffffff;
            color: #1f2937;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .scope-btn:hover {
            background: var(--permissions-surface);
        }

        .scope-btn.is-all {
            background: var(--permissions-surface);
            border-color: #9ca3af;
            color: #1f2937;
        }

        .scope-btn.is-partial {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #475569;
        }

        .permission-save-btn {
            min-height: 36px;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .permissions-table-wrap {
            padding: 12px;
            overflow-x: auto;
        }

        .permissions-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            border: 1px solid var(--permissions-border);
            border-radius: 14px;
            overflow: hidden;
        }

        .permissions-table th,
        .permissions-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--permissions-border);
            vertical-align: middle;
            text-align: center;
            white-space: nowrap;
        }

        .permissions-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .permissions-table tbody tr:nth-child(even) {
            background: #fcfcfd;
        }

        .permissions-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .permissions-table tbody tr.is-selected {
            background: var(--permissions-selected);
        }

        .permissions-table th.permission-resource-col,
        .permissions-table td.permission-resource-col {
            width: 100%;
            min-width: 260px;
            text-align: right;
            white-space: normal;
        }

        .permission-master-col {
            width: 62px;
            min-width: 62px;
        }

        .permission-action-col {
            width: 58px;
            min-width: 58px;
        }

        .permission-checkbox,
        .permission-row-toggle {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #4b5563;
        }

        .permission-resource-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #1f2937;
        }

        .permission-resource-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .permission-resource-stat {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 10px;
            border-radius: 999px;
            background: #eef2f7;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .permission-extra-title {
            margin-top: 12px;
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .permission-extra-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .permission-extra-item {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            color: #334155;
            font-size: 0.77rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .permission-extra-item:hover {
            background: #ffffff;
            border-color: var(--permissions-border-strong);
        }

        .permission-extra-item input {
            width: 15px;
            height: 15px;
            margin: 0;
            accent-color: #4b5563;
            cursor: pointer;
            flex-shrink: 0;
        }

        .permission-extra-item span {
            white-space: normal;
        }

        .permission-empty-cell {
            color: #cbd5e1;
            font-weight: 700;
        }

        .permissions-empty {
            display: none;
            border: 1px dashed #cbd5e1;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
        }

        .permissions-empty.is-visible {
            display: block;
        }

        .permission-lane .permissions-pill {
            border-color: var(--lane-border);
        }

        .permission-lane .permission-section-card {
            border-color: var(--lane-border);
        }

        .permission-lane .permission-section-card__header {
            background: var(--lane-surface);
            border-bottom-color: var(--lane-border);
        }

        .permission-lane .permission-section-card__toggle {
            color: var(--lane-accent-strong);
        }

        .permission-lane .permission-section-card__toggle::before {
            border-left-color: var(--lane-accent);
            border-bottom-color: var(--lane-accent);
        }

        .permission-lane .permission-group {
            border-color: var(--lane-border);
        }

        .permission-lane .permission-group__header {
            border-bottom-color: var(--lane-border);
        }

        .permission-lane .permission-group__title {
            color: var(--lane-accent-strong);
        }

        .permission-lane .permissions-table thead th {
            background: var(--lane-surface);
        }

        .permission-lane .permission-checkbox,
        .permission-lane .permission-row-toggle,
        .permission-lane .permission-extra-item input {
            accent-color: var(--lane-accent-strong);
        }

        .permission-lane .scope-btn.is-all {
            background: var(--lane-surface);
            border-color: var(--lane-border);
            color: var(--lane-accent-strong);
        }

        .permissions-footer {
            padding: 0 20px 20px;
        }

        .permissions-footer__inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #edf2f7;
            padding-top: 18px;
        }

        .permissions-footer__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (min-width: 1650px) {
            .permissions-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 1199.98px) {
            .permissions-toolbar-grid,
            .permissions-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .permissions-toolbar,
            .permissions-body,
            .permissions-footer {
                padding: 16px;
            }

            .permissions-toolbar-actions,
            .permissions-footer__actions,
            .permission-group__actions,
            .permission-section-card__meta,
            .permission-section-card__actions {
                justify-content: stretch;
            }

            .permissions-toolbar-actions .btn,
            .permissions-footer .btn,
            .scope-btn,
            .permission-save-btn {
                width: 100%;
            }

            .permission-lane__header,
            .permissions-footer__inner {
                flex-direction: column;
                align-items: stretch;
            }

            .permission-section-card__header,
            .permission-group__header {
                grid-template-columns: 1fr;
            }

            .permission-extra-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SET_PERMS))
        @php
            $isSuperAdmin = $role->name === 'super_admin';
            $totalLanes = count($permissionMatrixLanes ?? []);
            $totalSections = count($permissionMatrixSections);
            $totalGroups = collect($permissionMatrixSections)->sum(fn ($section) => count($section['groups']));
            $totalPermissions = collect($permissionMatrixSections)->sum(fn ($section) => (int) ($section['permissions_count'] ?? 0));
            $assignedLookup = array_fill_keys($assignedPermissionNames ?? [], true);
        @endphp

        <div class="page_content permissions-page">
            <div class="permissions-shell">
                <div class="permissions-toolbar">
                    <h3 class="permissions-title">{{ __('admin.perms_assign_now') }}</h3>
                    <p class="permissions-subtitle">
                        الدور الحالي:
                        <strong>{{ $role->name }}</strong>
                        @if ($isSuperAdmin)
                            <span class="permissions-pill is-success" style="margin-right: 8px;">كل الصلاحيات محددة</span>
                        @endif
                    </p>

                    <div class="permissions-toolbar-grid">
                        <select id="roleSwitcher">
                            @foreach ($availableRoles as $singleRole)
                                <option value="{{ route('dash.perms.setPerms', ['id' => $singleRole->id]) }}"
                                    {{ (int) $singleRole->id === (int) $role->id ? 'selected' : '' }}>
                                    {{ $singleRole->name }} ({{ (int) ($singleRole->permissions_count ?? 0) }} صلاحية)
                                </option>
                            @endforeach
                        </select>

                        <input id="permissionsSearch"
                               type="search"
                               placeholder="ابحث باسم العنصر أو الصلاحية الإضافية أو المفتاح">
                    </div>

                    <div class="permissions-toolbar-actions">
                        <button type="button" class="btn btn-outline-secondary" id="expandSectionsButton">فتح الكل</button>
                        <button type="button" class="btn btn-outline-secondary" id="collapseSectionsButton">طي الكل</button>
                        <button type="button" class="btn btn-primary" id="selectVisibleButton">تحديد الظاهر</button>
                        <button type="button" class="btn btn-outline-danger" id="clearVisibleButton">إلغاء الظاهر</button>
                    </div>

                    <div class="permissions-summary">
                        <span class="permissions-pill">المسارات: {{ $totalLanes }}</span>
                        <span class="permissions-pill">الأقسام: {{ $totalSections }}</span>
                        <span class="permissions-pill">المجموعات: {{ $totalGroups }}</span>
                        <span class="permissions-pill">الإجمالي: <span id="totalCount">{{ $totalPermissions }}</span></span>
                        <span class="permissions-pill is-success">المحدد: <span id="selectedCount">0</span></span>
                        <span class="permissions-pill is-warning">النسبة: <span id="selectionPercentage">0%</span></span>
                    </div>
                </div>

                @if (@AdminGuardHasPerm(\App\PermKeys::PERMISSIONS_ASSIGN))
                    <form id="storePermForm" method="post" action="{{ route('dash.perms.storePerms') }}" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <input type="hidden" name="role_id" value="{{ $role->id }}">

                        <div class="permissions-body">
                            <div id="permissionsGrid">
                                @foreach ($permissionMatrixLanes as $laneIndex => $lane)
                                    @php
                                        $laneLabel = match ($lane['key']) {
                                            'systems' => 'نظام مستقل',
                                            'additional' => 'فوق الأنظمة',
                                            default => 'ترتيب القائمة',
                                        };
                                    @endphp

                                    <section class="permission-lane"
                                             data-permission-lane
                                             data-lane-tone="{{ $lane['tone'] }}">
                                        <div class="permission-lane__header">
                                            <div class="permission-lane__content">
                                                <span class="permission-lane__eyebrow">{{ $laneLabel }}</span>
                                                <h4 class="permission-lane__title">{{ $lane['title'] }}</h4>
                                                <p class="permission-lane__description">{{ $lane['description'] }}</p>
                                            </div>

                                            <div class="permission-lane__meta">
                                                <span class="permissions-pill">{{ $lane['sections_count'] }} أقسام</span>
                                                <span class="permissions-pill">{{ $lane['groups_count'] }} مجموعات</span>
                                                <span class="permissions-pill">{{ $lane['permissions_count'] }} صلاحيات</span>
                                            </div>
                                        </div>

                                        <div class="permissions-grid">
                                            @foreach ($lane['sections'] as $sectionIndex => $section)
                                                @php
                                                    $collapseId = 'permission-section-' . $lane['key'] . '-' . $section['key'] . '-' . $sectionIndex;
                                                @endphp

                                                <div class="permission-section-card"
                                                     data-section-card
                                                     data-scope-label="{{ $section['title'] }}">
                                                    <div class="permission-section-card__header">
                                                        <div class="permission-section-card__heading">
                                                            <button type="button"
                                                                    class="permission-section-card__toggle"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#{{ $collapseId }}"
                                                                    aria-expanded="true"
                                                                    aria-controls="{{ $collapseId }}">
                                                                <span>{{ $section['title'] }}</span>
                                                            </button>
                                                            <p class="permission-section-card__hint">تحديد وحفظ الصلاحيات الخاصة بهذا القسم دفعة واحدة بدون التأثير على الأقسام الأخرى.</p>
                                                        </div>

                                                        <div class="permission-section-card__meta">
                                                            <span class="permissions-pill">{{ count($section['groups']) }} مجموعات</span>
                                                            <span class="permissions-pill">{{ $section['permissions_count'] }} صلاحيات</span>
                                                        </div>

                                                        <div class="permission-section-card__actions">
                                                            <button type="button" class="scope-btn" data-toggle-scope="section">تحديد القسم</button>
                                                            <button type="button"
                                                                    class="btn btn-success permission-save-btn"
                                                                    data-save-scope="section">حفظ القسم</button>
                                                        </div>
                                                    </div>

                                                    <div id="{{ $collapseId }}" class="collapse show permission-section-card__body">
                                                        @foreach ($section['groups'] as $groupIndex => $group)
                                                            @php
                                                                $isSimpleGroup = count($section['groups']) === 1 && $group['title'] === $section['title'];
                                                            @endphp

                                                            <div class="permission-group {{ $isSimpleGroup ? 'permission-group--simple' : '' }}"
                                                                 data-group-card
                                                                 data-scope-label="{{ $group['title'] }}">
                                                                <div class="permission-group__header">
                                                                    <div class="permission-group__heading">
                                                                        <h4 class="permission-group__title">{{ $group['title'] }}</h4>
                                                                        <p class="permission-group__subtitle">تنظيم عناصر هذه المجموعة قبل حفظ القسم بالكامل.</p>
                                                                    </div>

                                                                    <div class="permission-group__actions">
                                                                        <span class="permissions-pill">{{ count($group['rows']) }} عناصر</span>
                                                                        <span class="permissions-pill">{{ $group['permissions_count'] }} صلاحيات</span>
                                                                        <button type="button" class="scope-btn" data-toggle-scope="group">تحديد المجموعة</button>
                                                                    </div>
                                                                </div>

                                                                <div class="permissions-table-wrap">
                                                                    <table class="permissions-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="permission-resource-col">العنصر</th>
                                                                                <th class="permission-master-col">الكل</th>
                                                                                <th class="permission-action-col">إضافة</th>
                                                                                <th class="permission-action-col">قراءة</th>
                                                                                <th class="permission-action-col">تعديل</th>
                                                                                <th class="permission-action-col">حذف</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($group['rows'] as $rowIndex => $row)
                                                                                @php
                                                                                    $searchText = collect([
                                                                                        $lane['title'],
                                                                                        $section['title'],
                                                                                        $group['title'],
                                                                                        $row['title'],
                                                                                        ...collect($row['crud'])->filter()->pluck('display_name')->all(),
                                                                                        ...collect($row['extras'])->pluck('extra_label')->all(),
                                                                                        ...collect($row['permissions'])->all(),
                                                                                    ])->implode(' ');
                                                                                @endphp

                                                                                <tr data-permission-row data-search="{{ $searchText }}">
                                                                                    <td class="permission-resource-col">
                                                                                        <div class="permission-resource-title">{{ $row['title'] }}</div>
                                                                                        <div class="permission-resource-meta">
                                                                                            <span class="permission-resource-stat">{{ count($row['permissions']) }} صلاحية</span>
                                                                                            @if (!empty($row['extras']))
                                                                                                <span class="permission-resource-stat">{{ count($row['extras']) }} خيار إضافي</span>
                                                                                            @endif
                                                                                        </div>

                                                                                        @if (!empty($row['extras']))
                                                                                            <div class="permission-extra-title">خيارات إضافية</div>
                                                                                            <div class="permission-extra-list">
                                                                                                @foreach ($row['extras'] as $extra)
                                                                                                    <label class="permission-extra-item">
                                                                                                        <input class="permission-checkbox"
                                                                                                               type="checkbox"
                                                                                                               name="perms[]"
                                                                                                               value="{{ $extra['name'] }}"
                                                                                                               {{ $isSuperAdmin || isset($assignedLookup[$extra['name']]) ? 'checked' : '' }}>
                                                                                                        <span>{{ $extra['extra_label'] }}</span>
                                                                                                    </label>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                    </td>

                                                                                    <td class="permission-master-col">
                                                                                        <input type="checkbox" class="permission-row-toggle">
                                                                                    </td>

                                                                                    @foreach (['create', 'read', 'update', 'delete'] as $column)
                                                                                        @php
                                                                                            $columnPermission = $row['crud'][$column] ?? null;
                                                                                        @endphp
                                                                                        <td class="permission-action-col">
                                                                                            @if ($columnPermission)
                                                                                                <input class="permission-checkbox"
                                                                                                       type="checkbox"
                                                                                                       name="perms[]"
                                                                                                       value="{{ $columnPermission['name'] }}"
                                                                                                       title="{{ $columnPermission['display_name'] }}"
                                                                                                       {{ $isSuperAdmin || isset($assignedLookup[$columnPermission['name']]) ? 'checked' : '' }}>
                                                                                            @else
                                                                                                <span class="permission-empty-cell">-</span>
                                                                                            @endif
                                                                                        </td>
                                                                                    @endforeach
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>

                            <div class="permissions-empty" id="permissionsEmptyState">
                                لا توجد عناصر مطابقة لعبارة البحث الحالية.
                            </div>
                        </div>
                    </form>

                    <div class="permissions-footer">
                        <div class="permissions-footer__inner">
                            <div class="permissions-summary">
                                <span class="permissions-pill">الإجمالي: <span id="footerTotalCount">{{ $totalPermissions }}</span></span>
                                <span class="permissions-pill is-success">المحدد: <span id="footerSelectedCount">0</span></span>
                                <span class="permissions-pill is-warning">النسبة: <span id="footerSelectionPercentage">0%</span></span>
                            </div>

                            <div class="permissions-footer__actions">
                                <a href="{{ route('dash.roles.index') }}" class="btn btn-outline-secondary">{{ __('admin.back') }}</a>
                                <button type="button" class="btn btn-success" id="savePermissionsButton">حفظ الكل</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script>
        function getCollapseInstance(element) {
            if (!element || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
                return null;
            }

            return bootstrap.Collapse.getOrCreateInstance(element, { toggle: false });
        }

        function getRowPermissionCheckboxes(row) {
            return Array.from(row.querySelectorAll('.permission-checkbox'));
        }

        function getVisibleRows(scope) {
            return Array.from(scope.querySelectorAll('[data-permission-row]'))
                .filter((row) => !row.classList.contains('d-none'));
        }

        function updateRowState(row) {
            const checkboxes = getRowPermissionCheckboxes(row);
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const masterCheckbox = row.querySelector('.permission-row-toggle');

            row.classList.toggle('is-selected', checkedCount > 0);

            if (!masterCheckbox) {
                return;
            }

            masterCheckbox.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            masterCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }

        function updateScopeButton(button, selectedCount, totalCount, idleLabel, fullLabel) {
            if (!button) {
                return;
            }

            button.classList.remove('is-all', 'is-partial');

            if (selectedCount === 0) {
                button.textContent = idleLabel;
                return;
            }

            if (selectedCount === totalCount) {
                button.textContent = fullLabel;
                button.classList.add('is-all');
                return;
            }

            button.textContent = idleLabel + ' (' + selectedCount + '/' + totalCount + ')';
            button.classList.add('is-partial');
        }

        function updateGroupState(groupCard) {
            const checkboxes = Array.from(groupCard.querySelectorAll('.permission-checkbox'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const button = groupCard.querySelector('[data-toggle-scope="group"]');

            updateScopeButton(button, checkedCount, checkboxes.length, 'تحديد المجموعة', 'إلغاء المجموعة');
        }

        function updateSectionState(sectionCard) {
            const checkboxes = Array.from(sectionCard.querySelectorAll('.permission-checkbox'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const button = sectionCard.querySelector('[data-toggle-scope="section"]');

            updateScopeButton(button, checkedCount, checkboxes.length, 'تحديد القسم', 'إلغاء القسم');
        }

        function updateSelectionStats() {
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const totalCount = checkboxes.length;
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const percentage = totalCount === 0 ? 0 : Math.round((selectedCount / totalCount) * 100);

            [
                ['totalCount', totalCount],
                ['selectedCount', selectedCount],
                ['selectionPercentage', percentage + '%'],
                ['footerTotalCount', totalCount],
                ['footerSelectedCount', selectedCount],
                ['footerSelectionPercentage', percentage + '%'],
            ].forEach(([id, value]) => {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = value;
                }
            });
        }

        function refreshStates() {
            document.querySelectorAll('[data-permission-row]').forEach(updateRowState);
            document.querySelectorAll('[data-group-card]').forEach(updateGroupState);
            document.querySelectorAll('[data-section-card]').forEach(updateSectionState);
            updateSelectionStats();
        }

        function filterPermissions(query) {
            const normalizedQuery = (query || '').toLowerCase().trim();
            const laneCards = Array.from(document.querySelectorAll('[data-permission-lane]'));
            let visibleSections = 0;

            laneCards.forEach((laneCard) => {
                const sectionCards = Array.from(laneCard.querySelectorAll('[data-section-card]'));
                let laneHasVisibleSections = false;

                sectionCards.forEach((sectionCard) => {
                    const groups = Array.from(sectionCard.querySelectorAll('[data-group-card]'));
                    let sectionHasVisibleGroups = false;

                    groups.forEach((groupCard) => {
                        const rows = Array.from(groupCard.querySelectorAll('[data-permission-row]'));
                        let visibleRows = 0;

                        rows.forEach((row) => {
                            const haystack = (row.dataset.search || '').toLowerCase();
                            const matches = normalizedQuery === '' || haystack.includes(normalizedQuery);

                            row.classList.toggle('d-none', !matches);

                            if (matches) {
                                visibleRows++;
                            }
                        });

                        groupCard.classList.toggle('d-none', visibleRows === 0);

                        if (visibleRows > 0) {
                            sectionHasVisibleGroups = true;
                        }
                    });

                    sectionCard.classList.toggle('d-none', !sectionHasVisibleGroups);

                    if (sectionHasVisibleGroups) {
                        laneHasVisibleSections = true;
                        visibleSections++;

                        if (normalizedQuery !== '') {
                            const collapseElement = sectionCard.querySelector('.permission-section-card__body');
                            const collapseInstance = getCollapseInstance(collapseElement);

                            if (collapseInstance) {
                                collapseInstance.show();
                            } else if (collapseElement) {
                                collapseElement.classList.add('show');
                            }

                            const toggleButton = sectionCard.querySelector('.permission-section-card__toggle');

                            if (toggleButton) {
                                toggleButton.setAttribute('aria-expanded', 'true');
                            }
                        }
                    }
                });

                laneCard.classList.toggle('d-none', !laneHasVisibleSections);
            });

            const emptyState = document.getElementById('permissionsEmptyState');

            if (emptyState) {
                emptyState.classList.toggle('is-visible', visibleSections === 0);
            }
        }

        function toggleScope(button, selector) {
            const scope = button.closest(selector);

            if (!scope) {
                return;
            }

            const visibleRows = getVisibleRows(scope);
            const rows = visibleRows.length > 0
                ? visibleRows
                : Array.from(scope.querySelectorAll('[data-permission-row]'));
            const checkboxes = rows.flatMap((row) => getRowPermissionCheckboxes(row));
            const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);

            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldCheck;
            });

            refreshStates();
        }

        function appendHiddenInput(form, name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        function buildPermissionSubmitForm(baseForm, scopeElement = null) {
            if (!baseForm) {
                return null;
            }

            const tokenInput = baseForm.querySelector('input[name="_token"]');
            const roleInput = baseForm.querySelector('input[name="role_id"]');

            if (!tokenInput || !roleInput) {
                return null;
            }

            const submissionForm = document.createElement('form');
            submissionForm.method = 'post';
            submissionForm.action = baseForm.action;
            submissionForm.style.display = 'none';

            appendHiddenInput(submissionForm, '_token', tokenInput.value);
            appendHiddenInput(submissionForm, 'role_id', roleInput.value);

            if (scopeElement) {
                const scopeCheckboxes = Array.from(scopeElement.querySelectorAll('.permission-checkbox'));
                const scopeLabel = scopeElement.dataset.scopeLabel || 'هذا القسم';
                const scopePermissions = scopeCheckboxes.map((checkbox) => checkbox.value);
                const selectedPermissions = scopeCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);

                appendHiddenInput(submissionForm, 'scope_permissions_json', JSON.stringify(scopePermissions));
                appendHiddenInput(submissionForm, 'perms_json', JSON.stringify(selectedPermissions));
                appendHiddenInput(submissionForm, 'scope_label', scopeLabel);
            } else {
                const selectedPermissions = Array.from(baseForm.querySelectorAll('.permission-checkbox'))
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);

                appendHiddenInput(submissionForm, 'perms_json', JSON.stringify(selectedPermissions));
            }

            return submissionForm;
        }

        function confirmAndSubmitPermissions(baseForm, scopeElement = null) {
            const isScoped = Boolean(scopeElement);
            const scopeLabel = scopeElement ? (scopeElement.dataset.scopeLabel || 'هذا القسم') : '';
            const title = isScoped ? 'حفظ ' + scopeLabel : 'حفظ جميع الصلاحيات';
            const text = isScoped
                ? 'سيتم تحديث صلاحيات هذا القسم فقط، ولن تتأثر بقية الأقسام.'
                : `{!! __('admin.confirm_note') !!}`;

            swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCloseButton: true,
                showCancelButton: true,
                confirmButtonText: 'تأكيد',
                cancelButtonText: 'إلغاء',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const submissionForm = buildPermissionSubmitForm(baseForm, scopeElement);

                if (!submissionForm) {
                    return;
                }

                document.body.appendChild(submissionForm);
                submissionForm.submit();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const roleSwitcher = document.getElementById('roleSwitcher');
            const searchInput = document.getElementById('permissionsSearch');
            const selectVisibleButton = document.getElementById('selectVisibleButton');
            const clearVisibleButton = document.getElementById('clearVisibleButton');
            const expandSectionsButton = document.getElementById('expandSectionsButton');
            const collapseSectionsButton = document.getElementById('collapseSectionsButton');
            const savePermissionsButton = document.getElementById('savePermissionsButton');
            const form = document.getElementById('storePermForm');

            if (roleSwitcher) {
                roleSwitcher.addEventListener('change', function () {
                    if (roleSwitcher.value) {
                        window.location.href = roleSwitcher.value;
                    }
                });
            }

            document.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    refreshStates();
                });
            });

            document.querySelectorAll('.permission-row-toggle').forEach((masterCheckbox) => {
                masterCheckbox.addEventListener('change', function () {
                    const row = masterCheckbox.closest('[data-permission-row]');

                    if (!row) {
                        return;
                    }

                    getRowPermissionCheckboxes(row).forEach((checkbox) => {
                        checkbox.checked = masterCheckbox.checked;
                    });

                    refreshStates();
                });
            });

            document.querySelectorAll('[data-toggle-scope="group"]').forEach((button) => {
                button.addEventListener('click', function () {
                    toggleScope(button, '[data-group-card]');
                });
            });

            document.querySelectorAll('[data-toggle-scope="section"]').forEach((button) => {
                button.addEventListener('click', function () {
                    toggleScope(button, '[data-section-card]');
                });
            });

            document.querySelectorAll('[data-save-scope]').forEach((button) => {
                button.addEventListener('click', function () {
                    if (!form) {
                        return;
                    }

                    const scopeSelector = button.dataset.saveScope === 'section'
                        ? '[data-section-card]'
                        : '[data-group-card]';
                    const scopeElement = button.closest(scopeSelector);

                    if (!scopeElement) {
                        return;
                    }

                    confirmAndSubmitPermissions(form, scopeElement);
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    filterPermissions(searchInput.value);
                });
            }

            if (selectVisibleButton) {
                selectVisibleButton.addEventListener('click', function () {
                    const visibleRows = getVisibleRows(document);
                    const rows = visibleRows.length > 0
                        ? visibleRows
                        : searchInput && searchInput.value.trim() !== ''
                            ? []
                            : Array.from(document.querySelectorAll('[data-permission-row]'));

                    rows.forEach((row) => {
                        getRowPermissionCheckboxes(row).forEach((checkbox) => {
                            checkbox.checked = true;
                        });
                    });

                    refreshStates();
                });
            }

            if (clearVisibleButton) {
                clearVisibleButton.addEventListener('click', function () {
                    const visibleRows = getVisibleRows(document);
                    const rows = visibleRows.length > 0
                        ? visibleRows
                        : searchInput && searchInput.value.trim() !== ''
                            ? []
                            : Array.from(document.querySelectorAll('[data-permission-row]'));

                    rows.forEach((row) => {
                        getRowPermissionCheckboxes(row).forEach((checkbox) => {
                            checkbox.checked = false;
                        });
                    });

                    refreshStates();
                });
            }

            if (expandSectionsButton) {
                expandSectionsButton.addEventListener('click', function () {
                    document.querySelectorAll('.permission-section-card__body').forEach((element) => {
                        const collapseInstance = getCollapseInstance(element);

                        if (collapseInstance) {
                            collapseInstance.show();
                        } else {
                            element.classList.add('show');
                        }
                    });

                    document.querySelectorAll('.permission-section-card__toggle').forEach((button) => {
                        button.setAttribute('aria-expanded', 'true');
                    });
                });
            }

            if (collapseSectionsButton) {
                collapseSectionsButton.addEventListener('click', function () {
                    document.querySelectorAll('.permission-section-card__body').forEach((element) => {
                        const collapseInstance = getCollapseInstance(element);

                        if (collapseInstance) {
                            collapseInstance.hide();
                        } else {
                            element.classList.remove('show');
                        }
                    });

                    document.querySelectorAll('.permission-section-card__toggle').forEach((button) => {
                        button.setAttribute('aria-expanded', 'false');
                    });
                });
            }

            if (savePermissionsButton && form) {
                savePermissionsButton.addEventListener('click', function () {
                    confirmAndSubmitPermissions(form);
                });
            }

            refreshStates();
            filterPermissions('');
        });
    </script>
@endsection
