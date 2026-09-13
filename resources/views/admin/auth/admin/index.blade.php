@extends('admin.layout.indexs.index')
@section("style")
    <style>
        .admins-management-page {
            --admin-actions-width: 172px;
            --admin-border: #e5e7eb;
            --admin-soft-bg: #f8fafc;
            --admin-text: #111827;
            --admin-muted: #6b7280;
        }

        .admins-management-page .admins-toolbar {
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .admins-management-page .admins-toolbar .form-label {
            color: var(--admin-text);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .admins-management-page .admins-toolbar .form-select,
        .admins-management-page .admins-toolbar .btn {
            min-height: 42px;
            border-radius: 6px;
        }

        .admins-management-page .admins-table-shell {
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .admins-management-page #dataTableObject_wrapper {
            padding: 1rem;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        .admins-management-page .dataTables_wrapper .row:first-child,
        .admins-management-page .dataTables_wrapper .row:last-child {
            align-items: center;
            row-gap: 0.75rem;
        }

        .admins-management-page .dataTables_filter input,
        .admins-management-page .dataTables_length select {
            border-radius: 6px;
            border-color: var(--admin-border);
            min-height: 38px;
        }

        #dataTableObject {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        /* responsive expand control column */
        #dataTableObject th:first-child,
        #dataTableObject td.dtr-control {
            width: 36px !important;
            min-width: 36px !important;
            text-align: center;
            cursor: pointer;
            padding: 0 !important;
        }

        #dataTableObject td.dtr-control::before {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #0d6efd url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3E%3Cpath d='M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z'/%3E%3C/svg%3E") center/14px no-repeat;
            vertical-align: middle;
            transition: transform 0.2s ease;
        }

        #dataTableObject tr.parent td.dtr-control::before {
            background-color: #dc3545;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3E%3Cpath d='M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z'/%3E%3C/svg%3E");
        }

        #dataTableObject tr.child td {
            background: #f8fafc !important;
        }

        #dataTableObject thead th {
            background: var(--admin-soft-bg) !important;
            color: var(--admin-text) !important;
            border-top: 1px solid var(--admin-border);
            border-bottom: 1px solid var(--admin-border) !important;
            font-weight: 800;
            vertical-align: middle;
            white-space: nowrap;
        }

        #dataTableObject tbody td {
            color: #374151;
            vertical-align: middle;
            background: #fff;
        }

        #dataTableObject tbody tr:hover td {
            background: #f9fbff;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 58px;
            min-width: 58px;
            text-align: center;
            color: var(--admin-muted);
        }

        #dataTableObject td:nth-child(2) {
            min-width: 170px;
            font-weight: 700;
            color: var(--admin-text);
        }

        #dataTableObject td:nth-child(3),
        #dataTableObject td:nth-child(6),
        #dataTableObject td:nth-child(7),
        #dataTableObject td:nth-child(8),
        #dataTableObject td:nth-child(9),
        #dataTableObject td:nth-child(10),
        #dataTableObject td:nth-child(13) {
            white-space: nowrap;
        }

        #dataTableObject td:nth-child(14) {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #dataTableObject .badge {
            min-width: 68px;
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            font-size: 0.78rem;
        }

        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            width: var(--admin-actions-width);
            min-width: var(--admin-actions-width);
            text-align: center;
            white-space: nowrap;
            position: sticky;
            right: 0;
            z-index: 2;
            background: #fff;
        }

        #dataTableObject thead th:last-child {
            background: var(--admin-soft-bg) !important;
            z-index: 3;
        }

        #dataTableObject tbody tr:hover td:last-child {
            background: #f9fbff;
        }

        .admins-management-page .admins-table-shell {
            overflow-x: auto;
        }

        #dataTableObject .admin-actions-dropdown {
            display: inline-flex;
            justify-content: center;
            width: 100%;
        }

        #dataTableObject .admin-actions-dropdown .actions-btn {
            width: 132px;
            border-radius: 6px;
            padding: 0.48rem 0.75rem;
            font-size: 0.86rem;
            font-weight: 800;
            line-height: 1.3;
            box-shadow: 0 6px 14px rgba(39, 148, 235, 0.2);
        }

        #dataTableObject .admin-actions-template {
            display: none !important;
        }

        .admin-actions-menu {
            position: fixed;
            width: 260px;
            max-width: calc(100vw - 24px);
            max-height: min(430px, calc(100vh - 24px));
            overflow-y: auto;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
            padding: 0.45rem;
            margin: 0;
            list-style: none;
            z-index: 2147483000;
        }

        .admin-actions-menu li {
            list-style: none;
        }

        .admin-actions-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            width: 100%;
            border-radius: 6px;
            border: 0;
            background: transparent;
            color: #374151;
            padding: 0.72rem 0.78rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-align: start;
            text-decoration: none;
            cursor: pointer;
        }

        .admin-actions-menu .dropdown-item:hover,
        .admin-actions-menu .dropdown-item:focus {
            background: #f3f6fb;
            color: #111827;
        }

        .admin-actions-menu .dropdown-item i {
            width: 18px;
            text-align: center;
        }

        .admin-activity-icon {
            width: 36px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 0;
        }

        /* ===== نوافذ الإجراءات (SweetAlert2) =====
           التنسيق كله محصور بـ .admin-swal المضافة عبر customClass، فلا يتأثر
           أي تنبيه آخر في لوحة التحكم. العنوان والأيقونة يُرسمان داخل html
           لذلك نُخفي عنصري swal2-title و swal2-icon الافتراضيين. */
        .admin-swal.swal2-popup {
            --asw-line: #e5e7eb;
            /* العرض يأتي من خيار width في adminDialog (نمط inline)؛ هنا نضمن ألا
               تتجاوز النافذة الشاشة على الجوال فقط. */
            max-width: calc(100vw - 1.5rem);
            padding: 0;
            border: 1px solid var(--asw-line);
            border-radius: 16px;
            box-shadow: 0 26px 64px rgba(15, 23, 42, 0.18);
            overflow: hidden;
            font-family: inherit;
        }

        .admin-swal .swal2-title,
        .admin-swal .swal2-icon {
            display: none !important;
        }

        .admin-swal .swal2-html-container {
            margin: 0;
            padding: 0;
            overflow: visible;
            text-align: start;
            font-size: 0.94rem;
            color: #374151;
        }

        .admin-swal .asw-head {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--asw-line);
            background: linear-gradient(180deg, #f8fafc, #fff);
        }

        .admin-swal .asw-head-icon {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.05rem;
            background: #e0ecfd;
            color: #1d4ed8;
        }

        .admin-swal .asw-head h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
            line-height: 1.4;
        }

        .admin-swal .asw-head p {
            margin: 0.18rem 0 0;
            font-size: 0.82rem;
            font-weight: 700;
            color: #6b7280;
        }

        .admin-swal--warning .asw-head-icon {
            background: #fef3c7;
            color: #b45309;
        }

        .admin-swal--warning .asw-head {
            background: linear-gradient(180deg, #fffbeb, #fff);
        }

        .admin-swal--danger .asw-head-icon {
            background: #fee2e2;
            color: #b91c1c;
        }

        .admin-swal--danger .asw-head {
            background: linear-gradient(180deg, #fef2f2, #fff);
        }

        .admin-swal--success .asw-head-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .admin-swal--success .asw-head {
            background: linear-gradient(180deg, #f0fdf4, #fff);
        }

        .admin-swal .asw-body {
            display: grid;
            gap: 0.9rem;
            padding: 1.15rem 1.25rem;
        }

        .admin-swal .asw-body > p {
            margin: 0;
            line-height: 1.8;
        }

        .admin-swal .asw-note {
            margin: 0;
            padding: 0.7rem 0.85rem;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid var(--asw-line);
            color: #475569;
            font-size: 0.86rem;
            line-height: 1.75;
        }

        /* ----- حقول النماذج داخل النوافذ ----- */
        .admin-swal .asw-field {
            display: grid;
            gap: 0.35rem;
        }

        .admin-swal .asw-field > label {
            font-size: 0.82rem;
            font-weight: 800;
            color: #111827;
        }

        .admin-swal .asw-field > label .asw-req {
            color: #dc2626;
        }

        .admin-swal .asw-control {
            width: 100%;
            min-height: 42px;
            padding: 0.5rem 0.7rem;
            border: 1px solid var(--asw-line);
            border-radius: 10px;
            background: #fff;
            color: #111827;
            font-family: inherit;
            font-size: 0.92rem;
        }

        .admin-swal .asw-control:focus {
            outline: 0;
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .admin-swal textarea.asw-control {
            min-height: 96px;
            resize: vertical;
            line-height: 1.7;
        }

        .admin-swal .asw-hint {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6b7280;
        }

        .admin-swal .asw-error {
            min-height: 1.1rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #dc2626;
        }

        .admin-swal .asw-password {
            position: relative;
        }

        .admin-swal .asw-password .asw-control {
            padding-inline-end: 2.6rem;
        }

        .admin-swal .asw-password-toggle {
            position: absolute;
            inset-inline-end: 0.45rem;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
        }

        .admin-swal .asw-password-toggle:hover {
            background: #f1f5f9;
            color: #111827;
        }

        /* ----- بطاقات الاختيار (نعم/لا) ----- */
        .admin-swal .asw-choices {
            display: grid;
            gap: 0.5rem;
        }

        .admin-swal .asw-choice {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
            padding: 0.62rem 0.72rem;
            border: 1px solid var(--asw-line);
            border-radius: 10px;
            background: #fff;
            font-size: 0.89rem;
            font-weight: 700;
            color: #374151;
            cursor: pointer;
        }

        .admin-swal .asw-choice input {
            width: 17px;
            height: 17px;
            margin: 0;
            flex: 0 0 auto;
            accent-color: #2563eb;
        }

        .admin-swal .asw-choice.is-active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        /* بطاقة اختيار بعنوان وشرح */
        .admin-swal .asw-choice-card {
            display: grid;
            gap: 0.4rem;
            margin: 0;
            padding: 0.75rem 0.85rem;
            border: 1px solid var(--asw-line);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .admin-swal .asw-choice-card.is-active {
            border-color: #2563eb;
            background: #f5f9ff;
        }

        .admin-swal .asw-choice-head {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            font-size: 0.9rem;
            font-weight: 800;
            color: #111827;
        }

        .admin-swal .asw-choice-head input {
            width: 17px;
            height: 17px;
            margin: 0;
            flex: 0 0 auto;
            accent-color: #2563eb;
        }

        .admin-swal .asw-choice-desc {
            padding-inline-start: 1.7rem;
            font-size: 0.81rem;
            font-weight: 600;
            line-height: 1.75;
            color: #6b7280;
        }

        /* شريط تنبيه أعلى جسم النافذة */
        .admin-swal .asw-alert {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            margin: 0;
            padding: 0.7rem 0.85rem;
            border: 1px solid var(--asw-line);
            border-radius: 10px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.7;
        }

        .admin-swal .asw-alert i {
            margin-top: 0.2rem;
        }

        .admin-swal .asw-alert--warning {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .admin-swal .asw-alert--danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        /* ----- قائمة تفاصيل (مفتاح/قيمة) ----- */
        .admin-swal .asw-list {
            border: 1px solid var(--asw-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .admin-swal .asw-list-row {
            display: grid;
            grid-template-columns: 140px minmax(0, 1fr);
            gap: 0.75rem;
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        .admin-swal .asw-list-row:last-child {
            border-bottom: 0;
        }

        .admin-swal .asw-list-row:nth-child(odd) {
            background: #fbfcfe;
        }

        .admin-swal .asw-list-label {
            color: #6b7280;
            font-weight: 800;
        }

        .admin-swal .asw-list-value {
            color: #111827;
            font-weight: 700;
            word-break: break-word;
        }

        /* ----- قائمة اختيار متعدد (المكاتب الخارجية) ----- */
        .admin-swal .asw-check-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: #6b7280;
        }

        .admin-swal .asw-check-toolbar button {
            border: 1px solid var(--asw-line);
            border-radius: 8px;
            background: #fff;
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 0.3rem 0.6rem;
            cursor: pointer;
        }

        .admin-swal .asw-check-toolbar button:hover {
            background: #eff6ff;
        }

        .admin-swal .asw-check-list {
            max-height: 262px;
            overflow-y: auto;
            padding: 0.35rem;
            border: 1px solid var(--asw-line);
            border-radius: 12px;
        }

        .admin-swal .asw-check {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0;
            padding: 0.5rem 0.6rem;
            border-radius: 8px;
            font-size: 0.89rem;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }

        .admin-swal .asw-check:hover {
            background: #f3f6fb;
        }

        .admin-swal .asw-check input {
            width: 17px;
            height: 17px;
            margin: 0;
            flex: 0 0 auto;
            accent-color: #2563eb;
        }

        /* ----- التذييل والأزرار ----- */
        .admin-swal .swal2-actions {
            width: 100%;
            margin: 0;
            padding: 0.9rem 1.25rem;
            gap: 0.5rem;
            border-top: 1px solid var(--asw-line);
            background: #f8fafc;
            justify-content: flex-end;
        }

        .admin-swal .swal2-styled {
            margin: 0;
            padding: 0.55rem 1.2rem;
            border-radius: 9px;
            font-size: 0.9rem;
            font-weight: 800;
            box-shadow: none;
        }

        .admin-swal .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .admin-swal .swal2-cancel.swal2-styled {
            background: #fff !important;
            border: 1px solid var(--asw-line) !important;
            color: #374151 !important;
        }

        .admin-swal .swal2-cancel.swal2-styled:hover {
            background: #f1f5f9 !important;
        }

        .admin-swal .swal2-validation-message {
            margin: 0 1.25rem 0.9rem;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-weight: 700;
        }

        /* رسائل النجاح تظهر كإشعار خفيف بدل نافذة تحتاج إغلاقاً */
        .admin-swal-toast.swal2-popup {
            padding: 0.75rem 0.95rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16);
        }

        .admin-swal-toast .swal2-title {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 800;
            color: #111827;
        }

        @media (max-width: 575.98px) {
            .admin-swal .asw-list-row {
                grid-template-columns: 1fr;
                gap: 0.15rem;
            }
        }

        @media (max-width: 767.98px) {
            .admins-management-page #dataTableObject_wrapper {
                padding: 0.75rem;
            }

            #dataTableObject {
                min-width: 1180px;
            }

            #dataTableObject th:last-child,
            #dataTableObject td:last-child {
                width: 150px;
                min-width: 150px;
            }
        }
    </style>
@endsection
@section('page-title')
    {{__("auth.users")}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("auth.admins")}}</li>
@endsection
@section('content')

    @if(@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
        <div class="admins-management-page">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 admins-header-actions">
            <div>
                @if($canViewTrashed ?? false)
                    <a href="{{ route('admins.trashed') }}" class="btn btn-outline-danger admins-trashed-link">
                        <i class="fa fa-user-times"></i> {{ __('admin.trashed_admins.open_page') }}
                        <span class="badge bg-danger ms-1">{{ $trashedCount ?? 0 }}</span>
                    </a>
                @endif
            </div>
            <div class="admins-add-button">
                @if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL) && ($canCreateUser ?? true))
                    {!! addButton(route("admins.create") , __("auth.admin")) !!}
                @elseif(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL) && isset($userLimit))
                    <span class="text-danger small">تم الوصول إلى حد المستخدمين ({{ $userLimit }})</span>
                @endif
            </div>
        </div>

        <div class="card admins-toolbar mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">{{ helperTrans('ingaz.admin_type') }}</label>
                        <select id="filter_admin_type" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach(($filters['admin_type'] ?? []) as $key => $label)
                                <option value="{{ $key }}">{{ $label[app()->getLocale()] ?? $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ helperTrans('ingaz.sex_type') }}</label>
                        <select id="filter_sex_type" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="0">{{ __('admin.male') }}</option>
                            <option value="1">{{ __('admin.female') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.status') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="active">{{ __('admin.active') }}</option>
                            <option value="suspended">{{ __('admin.suspended') }}</option>
                        </select>
                    </div>
                    @if($branchesEnabled ?? false)
                        <div class="col-md-3">
                            <label class="form-label">{{ __('admin.branches') }}</label>
                            <select id="filter_branch_id" class="form-select">
                                <option value="">{{ __('admin.all') }}</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3 d-flex gap-2">
                        <button id="applyFilters" class="btn btn-primary w-50">{{ __('admin.filter') }}</button>
                        <button id="resetFilters" class="btn btn-outline-secondary w-50">{{ __('admin.reset') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="admins-table-shell">
            <table id="dataTableObject" class="table display dataTable table-hover align-middle" style="width:100%">
                <thead>
                <tr>
                    <th>{{ __('auth.SL') }}</th>
                    <th>{{__("auth.name")}}</th>
                    <th>{{ __('admin.username') }}</th>
                    <th>{{helperTrans("ingaz.admin_type")}}</th>
                    <th>{{helperTrans("ingaz.role")}}</th>
                    @if($branchesEnabled ?? false)
                        <th>{{ __('admin.branches') }}</th>
                    @endif
                    <th>{{ __('admin.contact_phone') }}</th>
                    <th>{{ __('admin.whatsapp_phone') }}</th>
                    <th>{{ __('admin.verification_phone') }}</th>
                    <th>{{__("auth.email")}}</th>
                    <th>{{helperTrans("ingaz.sex_type")}}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th>{{ __('admin.activity') }}</th>
                    <th>{{ __('admin.note') }}</th>
                    <th>المكاتب الخارجية</th>
                    <th>{{__("auth.actions")}}</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        </div>

    @else
        @include('components.no_permissions')
    @endif

@endsection
@section('js')
    <script>
        $(document).ready(function () {
            let baseUrl = "{{route("admins.index")}}";
            let filterScope = "{{ request()->get('filter_scope', '') }}";
            const adminStoreUrl = "{{ route('admins.store') }}";
            const reassignmentAdmins = @json($reassignmentAdmins->map(fn($admin) => ['id' => $admin->id, 'name' => $admin->name])->values());
            const adminSwal = window.Swal || window.swal;
            let tableInstance;

            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, defaultContent: ''},
                {"data": "name", orderable: true, searchable: true},
                {"data": "username", orderable: true, searchable: true},
                {"data": "admin_type", orderable: true, searchable: true},
                {"data": "role", orderable: true, searchable: true},
                @if($branchesEnabled ?? false)
                {"data": "branch", orderable: true, searchable: true},
                @endif
                {"data": "phone", orderable: true, searchable: true},
                {"data": "whatsapp", orderable: true, searchable: true, visible: false},
                {"data": "verification_phone", orderable: true, searchable: true, visible: false},
                {"data": "email", orderable: true, searchable: true},
                {"data": "sex_type", orderable: false, searchable: false, visible: false},
                {"data": "status", orderable: true, searchable: false},
                {"data": "activity_summary", orderable: false, searchable: false, visible: false},
                {"data": "note", orderable: false, searchable: true, visible: false},
                {"data": "offices", orderable: false, searchable: false, visible: false},
                {"data": "actions", orderable: false, searchable: false}
            ];

            function dataTableLanguage() {
                return {
                    "sProcessing": "{{trans('dataTable.sProcessing')}}",
                    "sLengthMenu": "{{trans('dataTable.sLengthMenu')}}",
                    "sZeroRecords": "{{trans('dataTable.sZeroRecords')}}",
                    "sInfo": "{{trans('dataTable.sInfo')}}",
                    "sInfoEmpty": "{{trans('dataTable.sInfoEmpty')}}",
                    "sInfoFiltered": "{{trans('dataTable.sInfoFiltered')}}",
                    "sInfoPostFix": "",
                    "sSearch": "{{trans('dataTable.sSearch')}}:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "{{trans('dataTable.sFirst')}}",
                        "sPrevious": "{{trans('dataTable.sPrevious')}}",
                        "sNext": "{{trans('dataTable.sNext')}}",
                        "sLast": "{{trans('dataTable.sLast')}}"
                    }
                };
            }

            function closeAdminActionsMenu() {
                $('#adminActionsMenu').remove();
                $('#dataTableObject .actions-btn').attr('aria-expanded', 'false');
            }

            function initAdminUsersTable(url) {
                tableInstance = $("#dataTableObject").DataTable({
                    responsive: {
                        details: {
                            type: 'column',
                            target: 0,
                            renderer: $.fn.dataTable.Responsive.renderer.listHiddenNodes()
                        }
                    },
                    columnDefs: [
                        { className: 'dtr-control', orderable: false, targets: 0 },
                        { className: 'all',  orderable: false, targets: -1 }
                    ],
                    autoWidth: false,
                    ordering: true,
                    searching: true,
                    iDisplayLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "{{ __('admin.all') }}"]],
                    processing: true,
                    serverSide: false,
                    ajax: url,
                    columns: columns,
                    language: dataTableLanguage(),
                    order: [[0, "asc"]],
                    drawCallback: function () {
                        closeAdminActionsMenu();
                    }
                });
                $("#dataTableObject_wrapper").off('scroll.adminActions').on('scroll.adminActions', closeAdminActionsMenu);
            }

            function reloadWithFilters() {
                let params = new URLSearchParams();
                let adminType = $('#filter_admin_type').val();
                let sexType = $('#filter_sex_type').val();
                let status = $('#filter_status').val();
                let branchId = $('#filter_branch_id').length ? $('#filter_branch_id').val() : '';
                if (adminType) params.append('admin_type', adminType);
                if (sexType !== '') params.append('sex_type', sexType);
                if (status) params.append('status', status);
                if (branchId) params.append('branch_id', branchId);
                if (filterScope) params.append('filter_scope', filterScope);
                if (tableInstance) {
                    $("#dataTableObject").DataTable().clear().destroy();
                }
                initAdminUsersTable(baseUrl + (params.toString() ? '?' + params.toString() : ''));
            }

            reloadWithFilters();

            $('#applyFilters').on('click', function () {
                reloadWithFilters();
            });

            $('#resetFilters').on('click', function () {
                $('#filter_admin_type').val('');
                $('#filter_sex_type').val('');
                $('#filter_status').val('');
                $('#filter_branch_id').val('');
                reloadWithFilters();
            });

            $(document).on('click', '#dataTableObject .actions-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $button = $(this);
                const $dropdown = $button.closest('.admin-actions-dropdown');
                const $sourceMenu = $dropdown.find('.admin-actions-template').first();
                const existingMenu = $('#adminActionsMenu');

                if (existingMenu.length && Number(existingMenu.data('source-id')) === Number($button.closest('tr').index())) {
                    closeAdminActionsMenu();
                    return;
                }

                closeAdminActionsMenu();

                const menu = $('<ul id="adminActionsMenu" class="admin-actions-menu"></ul>').html($sourceMenu.html());
                menu.data('source-id', $button.closest('tr').index());
                $('body').append(menu);

                const rect = $button[0].getBoundingClientRect();
                const menuWidth = menu.outerWidth();
                const menuHeight = menu.outerHeight();
                const margin = 12;
                const isRtl = $('html').attr('dir') === 'rtl';
                let top = rect.bottom + 8;
                let left = isRtl ? rect.left : rect.right - menuWidth;

                if (top + menuHeight > window.innerHeight - margin) {
                    top = rect.top - menuHeight - 8;
                }

                if (top < margin) {
                    top = margin;
                }

                if (left + menuWidth > window.innerWidth - margin) {
                    left = window.innerWidth - menuWidth - margin;
                }

                if (left < margin) {
                    left = margin;
                }

                menu.css({
                    top: top + 'px',
                    left: left + 'px'
                });
                $button.attr('aria-expanded', 'true');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#adminActionsMenu, #dataTableObject .admin-actions-dropdown').length) {
                    closeAdminActionsMenu();
                }
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeAdminActionsMenu();
                }
            });

            $(document).on('click', '#adminActionsMenu .dropdown-item', function () {
                setTimeout(closeAdminActionsMenu, 120);
            });

            $(window).on('resize scroll', closeAdminActionsMenu);

            function escapeHtml(value) {
                return $('<div>').text(value || '-').html();
            }

            /** مثل escapeHtml لكن دون استبدال القيمة الفارغة بشرطة — للسمات مثل value="". */
            function escapeText(value) {
                return $('<div>').text(value == null ? '' : String(value)).html();
            }

            function activityRow(label, value) {
                return '<div class="asw-list-row">'
                    + '<div class="asw-list-label">' + escapeHtml(label) + '</div>'
                    + '<div class="asw-list-value">' + escapeHtml(value) + '</div>'
                    + '</div>';
            }

            /* ===== نوافذ الإجراءات =====
               adminDialog يبني رأس النافذة (أيقونة + عنوان + اسم الموظف) وجسمها
               بشكل موحّد، ويمرّر أي خيار SweetAlert آخر كما هو (preConfirm،
               didOpen، showCancelButton ...). التنسيق في قسم style تحت .admin-swal. */
            const adminDialogVariants = {
                primary: {icon: 'fa-pen-to-square', confirmColor: '#2563eb'},
                info: {icon: 'fa-circle-info', confirmColor: '#2563eb'},
                success: {icon: 'fa-circle-check', confirmColor: '#16a34a'},
                warning: {icon: 'fa-triangle-exclamation', confirmColor: '#d97706'},
                danger: {icon: 'fa-trash-can', confirmColor: '#dc2626'}
            };

            function adminDialog(options) {
                const config = Object.assign({}, options);
                const variantKey = config.variant || 'primary';
                const variant = adminDialogVariants[variantKey] || adminDialogVariants.primary;
                const head = '<div class="asw-head">'
                    + '<span class="asw-head-icon"><i class="fa ' + (config.icon || variant.icon) + '"></i></span>'
                    + '<div><h3>' + escapeText(config.title) + '</h3>'
                    + (config.subtitle ? '<p>' + escapeText(config.subtitle) + '</p>' : '')
                    + '</div></div>';
                const body = '<div class="asw-body">' + (config.body || '') + '</div>';

                ['variant', 'icon', 'title', 'subtitle', 'body'].forEach(function (key) {
                    delete config[key];
                });

                return adminSwal.fire(Object.assign({
                    html: head + body,
                    width: 540,
                    confirmButtonColor: variant.confirmColor,
                    confirmButtonText: "{{ __('buttons.save') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                    focusConfirm: false,
                    buttonsStyling: true
                }, config, {
                    customClass: {popup: 'admin-swal admin-swal--' + variantKey}
                }));
            }

            function adminField(label, controlHtml, options) {
                const settings = options || {};
                return '<div class="asw-field">'
                    + '<label' + (settings.for ? ' for="' + settings.for + '"' : '') + '>'
                    + escapeText(label)
                    + (settings.required ? ' <span class="asw-req">*</span>' : '')
                    + '</label>'
                    + controlHtml
                    + (settings.hint ? '<span class="asw-hint">' + escapeText(settings.hint) + '</span>' : '')
                    + '</div>';
            }

            function adminSelectOptions(entries, selectedValue) {
                return entries.map(function (entry) {
                    const selected = String(entry.value) === String(selectedValue == null ? '' : selectedValue) ? ' selected' : '';
                    return '<option value="' + escapeText(entry.value) + '"' + selected + '>' + escapeText(entry.label) + '</option>';
                }).join('');
            }

            /** إشعار خفيف لرسائل النجاح — الصفحة تُحدَّث بعده فلا داعي لنافذة تحتاج إغلاقاً. */
            function adminToast(message, icon) {
                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    return;
                }

                adminSwal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon || 'success',
                    title: message,
                    showConfirmButton: false,
                    timer: 2800,
                    timerProgressBar: true,
                    customClass: {popup: 'admin-swal-toast'}
                });
            }

            $(document).on('click', '.admin-activity-button', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const button = $(this);
                const isOnline = Number(button.data('online')) === 1;
                const html = '<div class="asw-list">'
                    + activityRow("{{ __('admin.online_status') }}", button.data('status'))
                    + activityRow("{{ __('admin.last_login_at') }}", button.data('last-login'))
                    + activityRow("{{ __('admin.last_activity_at') }}", button.data('last-activity'))
                    + activityRow("{{ __('admin.login_ip') }}", button.data('ip'))
                    + activityRow("{{ __('admin.device') }}", button.data('device'))
                    + '</div>';

                if (adminSwal && typeof adminSwal.fire === 'function') {
                    adminDialog({
                        variant: isOnline ? 'success' : 'info',
                        icon: isOnline ? 'fa-wifi' : 'fa-clock-rotate-left',
                        title: "{{ __('admin.activity') }}",
                        subtitle: button.data('name'),
                        body: html,
                        width: 620,
                        confirmButtonText: "{{ __('buttons.close') }}"
                    });
                    return;
                }

                alert([
                    "{{ __('admin.online_status') }}: " + button.data('status'),
                    "{{ __('admin.last_login_at') }}: " + button.data('last-login'),
                    "{{ __('admin.last_activity_at') }}: " + button.data('last-activity'),
                    "{{ __('admin.login_ip') }}: " + button.data('ip'),
                    "{{ __('admin.device') }}: " + button.data('device')
                ].join("\n"));
            });

            $(document).on('click', '.toggle-status', function (e) {
                e.preventDefault();
                const button = $(this);
                const isSuspended = button.data('current-status') === 'suspended';
                const submitStatusChange = function (data) {
                    $.post({
                        url: button.attr('href'),
                        data: Object.assign({_token: "{{ csrf_token() }}"}, data),
                        success: function (response) {
                            adminToast(response.message);
                            reloadWithFilters();
                        },
                        error: handleErrorResponce
                    });
                };

                if (isSuspended) {
                    submitStatusChange({});
                    return;
                }

                const replacementOptions = reassignmentAdmins.filter(function (admin) {
                    return Number(admin.id) !== Number(button.data('id'));
                });
                const optionsHtml = adminSelectOptions(
                    [{value: '', label: 'اختر الموظف'}].concat(replacementOptions.map(function (admin) {
                        return {value: admin.id, label: admin.name};
                    }))
                );
                const modalHtml = '<p class="asw-alert asw-alert--warning"><i class="fa fa-circle-info"></i><span>سيتم منع الموظف من تسجيل الدخول فور تعليق حسابه. اختر أدناه ما تريد فعله بالأعمال المرتبطة به.</span></p>'
                    + adminField('سبب التعليق', '<textarea id="suspendReason" class="asw-control" rows="3" maxlength="1000" placeholder="اكتب سبب تعليق الحساب"></textarea>', {
                        for: 'suspendReason',
                        required: true
                    })
                    + adminField('هل تريد إسناد الأعمال النشطة لموظف آخر؟', '<div class="asw-choices">'
                        + '<label class="asw-choice-card is-active" for="suspendReassignNo">'
                        + '<span class="asw-choice-head"><input class="suspend-reassign-choice" type="radio" name="suspend_reassign" id="suspendReassignNo" value="0" checked><span>لا، تعليق الموظف فقط</span></span>'
                        + '<span class="asw-choice-desc">سيتم تعليق الحساب دون تغيير أي ارتباط. ستظل الطلبات والحجوزات والعقود الحالية والتاريخية مسندة إلى الموظف المعلق، حفاظًا على البيانات.</span>'
                        + '</label>'
                        + '<label class="asw-choice-card" for="suspendReassignYes">'
                        + '<span class="asw-choice-head"><input class="suspend-reassign-choice" type="radio" name="suspend_reassign" id="suspendReassignYes" value="1"><span>نعم، تعليق الموظف وإسناد العمل النشط</span></span>'
                        + '<span class="asw-choice-desc">سيتم نقل الطلبات والحجوزات والعقود النشطة فقط إلى الموظف الذي تختاره. أما الأعمال المنتهية والسجلات التاريخية فستبقى مرتبطة بالموظف الأصلي.</span>'
                        + '</label>'
                        + '</div>')
                    + '<div id="suspendReplacementWrap" hidden>'
                    + adminField('الموظف البديل', '<select id="suspendReplacementAdmin" class="asw-control">' + optionsHtml + '</select>', {
                        for: 'suspendReplacementAdmin',
                        required: true
                    })
                    + '</div>'
                    + '<div id="suspendModalError" class="asw-error"></div>';

                adminDialog({
                    variant: 'warning',
                    icon: 'fa-user-lock',
                    title: 'تعليق الموظف',
                    subtitle: button.data('name') || '',
                    body: modalHtml,
                    width: 580,
                    showCancelButton: true,
                    confirmButtonText: 'تعليق الموظف',
                    didOpen: function () {
                        $('.suspend-reassign-choice').on('change', function () {
                            $('.asw-choice-card').removeClass('is-active');
                            $(this).closest('.asw-choice-card').addClass('is-active');
                            $('#suspendReplacementWrap').prop('hidden', $(this).val() !== '1');
                        });
                    },
                    preConfirm: function () {
                        const reason = String($('#suspendReason').val() || '').trim();
                        const reassign = $('input[name="suspend_reassign"]:checked').val() === '1';
                        const replacement = $('#suspendReplacementAdmin').val();
                        if (!reason) {
                            $('#suspendModalError').text('يرجى كتابة سبب التعليق.');
                            return false;
                        }
                        if (reassign && !replacement) {
                            $('#suspendModalError').text(replacementOptions.length ? 'يرجى اختيار الموظف البديل.' : 'لا يوجد موظف نشط متاح للإسناد.');
                            return false;
                        }
                        return {note: reason, reassign_work: reassign ? 1 : 0, reassigned_admin_id: reassign ? replacement : ''};
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        submitStatusChange(result.value);
                    }
                });
            });

            // عند محاولة إنشاء/تعديل مستخدم ببريد أو اسم مستخدم يعود لحساب محذوف، يرجع الخادم 422 مع
            // trashed_conflict؛ نعرض هنا نافذة توضيحية مع زر انتقال مباشر لصفحة المحذوفين.
            $(document).on('ajaxError', function (event, jqxhr, settings) {
                if (!jqxhr || jqxhr.status !== 422 || !settings || !settings.url) {
                    return;
                }

                const isAdminForm = settings.url.indexOf(adminStoreUrl) === 0;
                if (!isAdminForm) {
                    return;
                }

                let payload = jqxhr.responseJSON;
                if (!payload) {
                    try { payload = $.parseJSON(jqxhr.responseText); } catch (err) { payload = null; }
                }

                const conflict = payload && payload.trashed_conflict;
                if (!conflict) {
                    return;
                }

                const escape = function (value) { return $('<div>').text(value == null ? '' : String(value)).html(); };
                const messages = payload.errors && payload.errors[conflict.field] ? payload.errors[conflict.field] : [];
                const html = '<p>' + escape(messages[0] || '') + '</p>'
                    + '<div class="asw-list">'
                    + '<div class="asw-list-row"><div class="asw-list-label">{{ __('auth.name') }}</div><div class="asw-list-value">' + escape(conflict.name) + '</div></div>'
                    + '<div class="asw-list-row"><div class="asw-list-label">' + escape(conflict.label) + '</div><div class="asw-list-value">' + escape(conflict.value) + '</div></div>'
                    + '<div class="asw-list-row"><div class="asw-list-label">{{ __('admin.trashed_admins.deleted_at') }}</div><div class="asw-list-value">' + escape(conflict.deleted_at || '-') + '</div></div>'
                    + '</div>';

                if (adminSwal && typeof adminSwal.fire === 'function') {
                    adminDialog({
                        variant: 'warning',
                        icon: 'fa-user-slash',
                        title: "{{ __('admin.trashed_admins.conflict_title') }}",
                        body: html,
                        showCancelButton: true,
                        confirmButtonText: conflict.trashed_url ? "{{ __('admin.trashed_admins.conflict_go') }}" : "{{ __('buttons.close') }}",
                        cancelButtonText: "{{ __('buttons.close') }}"
                    }).then(function (result) {
                        if (result.isConfirmed && conflict.trashed_url) {
                            window.location.href = conflict.trashed_url;
                        }
                    });
                }
            });

            function deleteAdmin(button, isPermanent, forcedCounts) {
                const adminId = Number(button.data('id'));
                const deleteRoute = button.data('delete-route');
                const adminName = button.data('name') || '';
                const requiresReassignment = forcedCounts ? true : Number(button.data('requires-reassignment')) === 1;
                const ordersCount = forcedCounts ? Number(forcedCounts.orders) || 0 : Number(button.data('orders-count')) || 0;
                const bookingsCount = forcedCounts ? Number(forcedCounts.bookings) || 0 : Number(button.data('bookings-count')) || 0;
                const contractsCount = forcedCounts ? Number(forcedCounts.contracts) || 0 : Number(button.data('contracts-count')) || 0;
                const replacementOptions = {};

                reassignmentAdmins.forEach(function (admin) {
                    if (Number(admin.id) !== adminId) {
                        replacementOptions[admin.id] = admin.name;
                    }
                });

                if (requiresReassignment && Object.keys(replacementOptions).length === 0) {
                    const noEmployeeMessage = "{{ __('admin.no_active_reassignment_employee') }}";
                    if (adminSwal && typeof adminSwal.fire === 'function') {
                        adminDialog({
                            variant: 'danger',
                            icon: 'fa-ban',
                            title: "{{ __('admin.delete_blocked') }}",
                            subtitle: adminName,
                            body: '<p>' + escapeHtml(noEmployeeMessage) + '</p>',
                            confirmButtonText: "{{ __('buttons.close') }}"
                        });
                    } else {
                        alert(noEmployeeMessage);
                    }
                    return;
                }

                const reassignmentText = requiresReassignment
                    ? "{{ __('admin.reassignment_required_text') }}"
                        .replace(':orders', ordersCount)
                        .replace(':bookings', bookingsCount)
                        .replace(':contracts', contractsCount)
                    : "{{ __('admin.reassignment_optional_text') }}";

                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    if (!window.confirm(isPermanent ? "{{ __('admin.confirm_force_delete_employee') }}" : "{{ __('admin.confirm_delete_employee') }}")) {
                        return;
                    }
                }

                const replacementSelectHtml = '<select id="deleteReplacementAdmin" class="asw-control">'
                    + adminSelectOptions([{value: '', label: "{{ __('admin.select_replacement_employee_placeholder') }}"}].concat(
                        Object.keys(replacementOptions).map(function (id) {
                            return {value: id, label: replacementOptions[id]};
                        })
                    ))
                    + '</select>';

                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminDialog({
                        variant: 'danger',
                        icon: isPermanent ? 'fa-trash-can' : 'fa-user-xmark',
                        title: isPermanent ? "{{ __('admin.force_delete_employee') }}" : "{{ __('admin.delete_employee') }}",
                        subtitle: adminName,
                        body: '<p class="asw-alert asw-alert--danger"><i class="fa fa-triangle-exclamation"></i><span>'
                            + escapeHtml(isPermanent
                                ? "{{ __('admin.force_delete_employee_text') }}".replace(':name', adminName)
                                : "{{ __('admin.delete_employee_text') }}".replace(':name', adminName))
                            + '</span></p>'
                            + '<p class="asw-note">' + escapeHtml(reassignmentText) + '</p>'
                            + adminField("{{ __('admin.select_replacement_employee_placeholder') }}", replacementSelectHtml, {
                                for: 'deleteReplacementAdmin',
                                required: requiresReassignment
                            })
                            + '<div id="deleteModalError" class="asw-error"></div>',
                        width: 580,
                        showCancelButton: true,
                        confirmButtonText: isPermanent ? "{{ __('admin.force_delete') }}" : "{{ __('admin.delete') }}",
                        preConfirm: function () {
                            const replacement = String($('#deleteReplacementAdmin').val() || '');

                            if (requiresReassignment && !replacement) {
                                $('#deleteModalError').text("{{ __('admin.replacement_employee_required') }}");
                                return false;
                            }

                            return replacement;
                        }
                    })
                    : Promise.resolve({isConfirmed: true, value: ''});

                confirmation.then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: deleteRoute,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}",
                            reassigned_admin_id: result.value || ''
                        },
                        success: function (data) {
                            handleSuccessResponce(data);
                            adminToast(data.message || (isPermanent ? "{{ __('admin.employee_force_deleted_successfully') }}" : "{{ __('admin.employee_deleted_successfully') }}"));
                            reloadWithFilters();
                        },
                        error: function (errorObj) {
                            if (errorObj.responseJSON && errorObj.responseJSON.requires_reassignment) {
                                deleteAdmin(button, isPermanent, errorObj.responseJSON.assignment_counts || {});
                                return;
                            }

                            handleErrorResponce(errorObj);
                        }
                    });
                });
            }

            $(document).on('click', '.admin-delete-button', function (e) {
                e.preventDefault();
                deleteAdmin($(this), false);
            });

            $(document).on('click', '.admin-force-delete-button', function (e) {
                e.preventDefault();
                deleteAdmin($(this), true);
            });

            function postAdminAction(url, data, successMessage) {
                $.post({
                    url: url,
                    data: Object.assign({_token: "{{ csrf_token() }}"}, data || {}),
                    success: function (response) {
                        if (adminSwal && typeof adminSwal.fire === 'function') {
                            adminToast(response.message || successMessage);
                        } else {
                            handleSuccessResponce(response);
                        }
                        reloadWithFilters();
                    },
                    error: handleErrorResponce
                });
            }

            $(document).on('click', '.admin-terminate-session-button', function (e) {
                e.preventDefault();
                const url = $(this).data('terminate-route');
                const name = $(this).data('name') || '';
                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminDialog({
                        variant: 'warning',
                        icon: 'fa-power-off',
                        title: "{{ __('admin.terminate_employee_session') }}",
                        subtitle: name,
                        body: '<p>' + escapeHtml("{{ __('admin.terminate_employee_session_text') }}".replace(':name', name)) + '</p>',
                        showCancelButton: true,
                        confirmButtonText: "{{ __('admin.terminate_session') }}"
                    })
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.confirm_terminate_employee_session') }}")});

                confirmation.then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, {}, "{{ __('admin.employee_session_terminated') }}");
                    }
                });
            });

            $(document).on('click', '.admin-send-reset-link-button', function (e) {
                e.preventDefault();
                postAdminAction($(this).data('reset-link-route'), {}, "{{ __('admin.password_link_sent_successfully') }}");
            });

            $(document).on('click', '.admin-impersonate-button', function (e) {
                e.preventDefault();
                const url = $(this).data('impersonate-route');
                const name = $(this).data('name') || '';
                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminDialog({
                        variant: 'warning',
                        icon: 'fa-user-secret',
                        title: "{{ __('admin.impersonate_employee') }}",
                        subtitle: name,
                        body: '<p>' + escapeHtml("{{ __('admin.impersonate_employee_text') }}".replace(':name', name)) + '</p>',
                        showCancelButton: true,
                        confirmButtonText: "{{ __('admin.login_action') }}"
                    })
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.confirm_impersonate_employee') }}")});

                confirmation.then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.post({
                        url: url,
                        data: {_token: "{{ csrf_token() }}"},
                        success: function (response) {
                            window.location.href = response.data.url;
                        },
                        error: handleErrorResponce
                    });
                });
            });

            const quickOfficesList = @json($recruitmentOffices ?? collect());
            const quickRolesMap = @json($roles->pluck('name', 'id'));
            const quickBranchesMap = @json($branches->mapWithKeys(fn($b) => [$b->id => $b->name_ar ?: $b->name_en]));

            $(document).on('click', '.admin-quick-role-button', function (e) {
                e.preventDefault();
                const url = $(this).data('assign-route');
                const name = $(this).data('name') || '';
                const currentRoleId = String($(this).data('role-id') || '');

                const roleEntries = [{value: '', label: '— {{ __('admin.no_role') ?? 'بدون دور' }} —'}].concat(
                    Object.entries(quickRolesMap).map(([id, label]) => ({value: id, label: label}))
                );

                adminDialog({
                    variant: 'primary',
                    icon: 'fa-user-shield',
                    title: '{{ __('admin.role_id') }}',
                    subtitle: name,
                    body: adminField('{{ __('admin.role_id') }}', '<select id="quickRoleSelect" class="asw-control">'
                        + adminSelectOptions(roleEntries, currentRoleId) + '</select>', {for: 'quickRoleSelect'}),
                    showCancelButton: true,
                    preConfirm: function () {
                        return String($('#quickRoleSelect').val() || '');
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, { role_id: result.value }, '{{ __('admin.updated_successfully') }}');
                    }
                });
            });

            $(document).on('click', '.admin-quick-office-button', function (e) {
                e.preventDefault();
                const url = $(this).data('assign-route');
                const name = $(this).data('name') || '';
                const currentIds = $(this).data('office-ids') || [];

                if (!quickOfficesList.length) {
                    adminDialog({
                        variant: 'info',
                        title: 'المكاتب الخارجية',
                        body: '<p>لا توجد مكاتب خارجية مسجّلة في النظام</p>',
                        confirmButtonText: '{{ __('buttons.close') }}'
                    });
                    return;
                }

                let checkboxesHtml = '<div class="asw-check-toolbar">'
                    + '<span>عدد المكاتب: ' + quickOfficesList.length + '</span>'
                    + '<button type="button" id="officesToggleAll">تحديد الكل / إلغاء التحديد</button>'
                    + '</div>'
                    + '<div class="asw-check-list">';
                quickOfficesList.forEach(function (o) {
                    const checked = currentIds.includes(o.id) ? ' checked' : '';
                    checkboxesHtml += '<label class="asw-check" for="oc_' + o.id + '">'
                        + '<input class="office-checkbox" type="checkbox" value="' + o.id + '" id="oc_' + o.id + '"' + checked + '>'
                        + '<span>' + escapeHtml(o.label) + '</span>'
                        + '</label>';
                });
                checkboxesHtml += '</div>';

                adminDialog({
                    variant: 'primary',
                    icon: 'fa-building',
                    title: 'المكاتب الخارجية',
                    subtitle: name,
                    body: checkboxesHtml,
                    showCancelButton: true,
                    didOpen: function () {
                        $('#officesToggleAll').on('click', function () {
                            const boxes = $('.office-checkbox');
                            const shouldCheck = boxes.filter(':checked').length !== boxes.length;
                            boxes.prop('checked', shouldCheck);
                        });
                    },
                    preConfirm: function () {
                        return [...document.querySelectorAll('.office-checkbox:checked')].map(c => parseInt(c.value));
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.post({
                            url: url,
                            data: { _token: "{{ csrf_token() }}", office_ids: result.value },
                            success: function (res) {
                                adminToast(res.message || '{{ __('admin.updated_successfully') }}');
                                reloadWithFilters();
                            },
                            error: handleErrorResponce
                        });
                    }
                });
            });

            $(document).on('click', '.admin-quick-branch-button', function (e) {
                e.preventDefault();
                const url = $(this).data('assign-route');
                const name = $(this).data('name') || '';
                const currentBranchId = String($(this).data('branch-id') || '');

                const branchEntries = [{value: '', label: '— {{ __('admin.no_branch') ?? 'بدون فرع (Super Admin)' }} —'}].concat(
                    Object.entries(quickBranchesMap).map(([id, label]) => ({value: id, label: label}))
                );

                adminDialog({
                    variant: 'primary',
                    icon: 'fa-sitemap',
                    title: '{{ __('admin.branches') }}',
                    subtitle: name,
                    body: adminField('{{ __('admin.branches') }}', '<select id="quickBranchSelect" class="asw-control">'
                        + adminSelectOptions(branchEntries, currentBranchId) + '</select>', {
                        for: 'quickBranchSelect',
                        hint: 'الموظف بدون فرع يرى بيانات كل الفروع.'
                    }),
                    showCancelButton: true,
                    preConfirm: function () {
                        return String($('#quickBranchSelect').val() || '');
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, { branch_id: result.value }, '{{ __('admin.updated_successfully') }}');
                    }
                });
            });

            $(document).on('click', '.admin-reset-password-button', function (e) {
                e.preventDefault();
                const url = $(this).data('reset-route');
                const name = $(this).data('name') || '';

                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    const password = window.prompt("{{ __('admin.new_password') }}");
                    if (password) {
                        postAdminAction(url, {
                            password: password,
                            password_confirmation: password
                        }, "{{ __('admin.password_changed_sessions_terminated') }}");
                    }
                    return;
                }

                const passwordFieldHtml = function (id, placeholder) {
                    return '<div class="asw-password">'
                        + '<input id="' + id + '" type="password" class="asw-control" autocomplete="new-password" placeholder="' + placeholder + '">'
                        + '<button type="button" class="asw-password-toggle" data-target="' + id + '" tabindex="-1" aria-label="{{ __('admin.new_password') }}"><i class="fa fa-eye"></i></button>'
                        + '</div>';
                };

                adminDialog({
                    variant: 'primary',
                    icon: 'fa-key',
                    title: "{{ __('admin.change_password') }}",
                    subtitle: name,
                    body: '<p class="asw-alert"><i class="fa fa-circle-info"></i><span>{{ __('admin.password_changed_sessions_terminated') }}</span></p>'
                        + adminField("{{ __('admin.new_password') }}", passwordFieldHtml('adminPassword', "{{ __('admin.new_password') }}"), {
                            for: 'adminPassword',
                            required: true,
                            hint: "{{ __('admin.password_min_six') }}"
                        })
                        + adminField("{{ __('admin.new_password_confirmation') }}", passwordFieldHtml('adminPasswordConfirmation', "{{ __('admin.new_password_confirmation') }}"), {
                            for: 'adminPasswordConfirmation',
                            required: true
                        })
                        + '<div id="passwordModalError" class="asw-error"></div>',
                    showCancelButton: true,
                    didOpen: function () {
                        $('.asw-password-toggle').on('click', function () {
                            const input = document.getElementById($(this).data('target'));
                            const reveal = input.type === 'password';
                            input.type = reveal ? 'text' : 'password';
                            $(this).find('i').toggleClass('fa-eye', !reveal).toggleClass('fa-eye-slash', reveal);
                        });
                        document.getElementById('adminPassword').focus();
                    },
                    preConfirm: function () {
                        const password = document.getElementById('adminPassword').value;
                        const confirmation = document.getElementById('adminPasswordConfirmation').value;

                        if (!password || password.length < 6) {
                            $('#passwordModalError').text("{{ __('admin.password_min_six') }}");
                            return false;
                        }

                        if (password !== confirmation) {
                            $('#passwordModalError').text("{{ __('admin.password_confirmation_mismatch') }}");
                            return false;
                        }

                        return {password: password, password_confirmation: confirmation};
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, result.value, "{{ __('admin.password_changed_sessions_terminated') }}");
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ajaxComplete(function () {
            $('.all').click(function () {
                $(this).closest('div').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
            });
        });
    </script>
@endsection
