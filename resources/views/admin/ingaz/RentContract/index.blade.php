@extends('admin.layout.indexs.index')
@section('style')
    <!-- DataTables CSS من CDN -->
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- الملفات المحلية -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> --}}
    <style>
        #dataTableObject {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        #dataTableObject_wrapper {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }

        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            min-width: 155px;
        }

        #dataTableObject .actions-dropdown {
            position: static !important;
        }

        #dataTableObject .actions-dropdown .dropdown-menu.dropdown-menu-end {
            position: fixed !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 2147483647 !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
            will-change: top, left;
        }

        .rent-contract-actions-menu.dropdown-menu,
        #dataTableObject .actions-dropdown .dropdown-menu.dropdown-menu-end.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 2147483647 !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
        }

        .extend-contract-modal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .extend-contract-modal .modal-header {
            background: #0f766e;
            border-bottom: 0;
        }

        .extend-summary-band {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }

        .extend-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .extend-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            min-height: 74px;
        }

        .extend-stat-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .extend-stat-value {
            color: #111827;
            font-weight: 700;
            font-size: 15px;
            word-break: break-word;
        }

        .extend-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .extend-status-pill.is-ok {
            background: #dcfce7;
            color: #166534;
        }

        .extend-status-pill.is-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .extend-status-pill.is-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .extend-preview-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 10px;
            padding: 14px;
        }

        .rent-stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 1.5rem;
        }

        .rent-stat-card {
            appearance: none;
            width: 100%;
            border: 1px solid #e5e7eb;
            border-right: 4px solid var(--rent-stat-color, #2563eb);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            padding: 16px;
            min-height: 116px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: inherit;
            font: inherit;
            text-align: right;
            cursor: pointer;
            transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .rent-stat-card:hover,
        .rent-stat-card:focus-visible {
            transform: translateY(-2px);
            border-color: var(--rent-stat-color, #2563eb);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
            outline: none;
        }

        .rent-stat-card.is-active {
            border-color: var(--rent-stat-color, #2563eb);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--rent-stat-color, #2563eb) 18%, transparent),
                0 12px 28px rgba(15, 23, 42, 0.12);
        }

        .rent-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .rent-stat-value {
            color: #111827;
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
        }

        .rent-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #eff6ff;
            background: color-mix(in srgb, var(--rent-stat-color, #2563eb) 12%, #fff);
            color: var(--rent-stat-color, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .rent-stat-primary {
            --rent-stat-color: #2563eb;
        }

        .rent-stat-success {
            --rent-stat-color: #16a34a;
        }

        .rent-stat-info {
            --rent-stat-color: #0891b2;
        }

        .rent-stat-warning {
            --rent-stat-color: #d97706;
        }

        .rent-stat-danger {
            --rent-stat-color: #dc2626;
        }

        .rent-stat-muted {
            --rent-stat-color: #475569;
        }

        .filter-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .filter-section-title {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            margin: 14px 0 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .rent-period-box {
            min-width: 210px;
            display: grid;
            gap: 6px;
        }

        .rent-period-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: #334155;
        }

        .rent-period-row strong {
            color: #64748b;
            font-weight: 700;
            white-space: nowrap;
        }

        .rent-period-row span {
            color: #111827;
            font-weight: 700;
            direction: ltr;
            text-align: left;
        }

        .rent-period-money {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px dashed #e5e7eb;
        }

        #dataTableObject th,
        #dataTableObject td {
            vertical-align: middle;
        }

        #dataTableObject td {
            padding: 0.55rem 0.65rem;
        }

        #dataTableObject thead th {
            white-space: nowrap;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            background: #f8fafc;
        }

        .rent-person-cell {
            min-width: 220px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rent-person-avatar {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            overflow: hidden;
        }

        .rent-person-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rent-person-main {
            min-width: 0;
        }

        .rent-person-name {
            color: #111827;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .rent-person-meta {
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        .rent-person-meta a {
            color: #2563eb;
            text-decoration: none;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 54px;
            text-align: center;
            color: #64748b;
            font-weight: 800;
        }

        #dataTableObject td:nth-last-child(2),
        #dataTableObject td:nth-last-child(3) {
            white-space: nowrap;
        }

        .rent-days-cell {
            width: 150px;
            display: grid;
            gap: 0;
            overflow: hidden;
            border: 1px solid #dbe4ee;
            border-radius: 8px;
            background: #fff;
            font-size: 13px;
        }

        .rent-day-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 30px;
            padding: 4px 7px;
        }

        .rent-day-row + .rent-day-row {
            border-top: 1px solid #e5e7eb;
            background: #fafcfe;
        }

        .rent-day-label {
            color: #475569;
            font-weight: 800;
        }

        .rent-day-value {
            min-width: 36px;
            border-radius: 6px;
            padding: 3px 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            text-align: center;
        }

        .rent-day-value.is-remaining {
            background: #ecfdf3;
            color: #15803d;
        }

        .rent-day-value.is-ended,
        .rent-day-value.is-cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .rent-day-value.is-muted {
            background: #f1f5f9;
            color: #64748b;
        }

        .close-contract-modal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .close-contract-modal .modal-header {
            background: #155e75;
            border-bottom: 0;
        }

        .close-section-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .close-section-card .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            font-weight: 700;
        }

        .close-section-card .section-body {
            padding: 14px;
        }

        .close-section-card .section-title i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #e0f2f7;
            color: #155e75;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
        }

        .close-contract-modal .modal-body {
            background: #f4f7fa;
        }

        .close-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .close-info-item,
        .close-timeline-item {
            min-width: 0;
            padding: 10px 12px;
            border: 1px solid #e5edf3;
            border-radius: 10px;
            background: #f8fafc;
        }

        .close-info-item--wide {
            grid-column: 1 / -1;
        }

        .close-info-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            font-size: .8rem;
            font-weight: 600;
            color: #64748b;
        }

        .close-info-label i {
            color: #94a3b8;
            font-size: .75rem;
        }

        .close-info-value {
            display: block;
            font-weight: 700;
            color: #0f172a;
            overflow-wrap: anywhere;
        }

        .close-timeline {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .close-timeline-item--input {
            border-color: #7dd3e0;
            background: #ecfeff;
            box-shadow: 0 0 0 3px rgba(21, 94, 117, 0.06);
        }

        .close-timeline-item--input .close-info-label,
        .close-timeline-item--input .close-info-label i {
            color: #155e75;
        }

        .close-timeline-item--input .form-control {
            min-height: 36px;
            padding: 4px 10px;
            font-weight: 700;
        }

        .close-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            border-inline-start: 4px solid #dc2626;
            background: #fef2f2;
            color: #991b1b;
            font-size: .9rem;
        }

        .close-alert.d-none {
            display: none !important;
        }

        .close-alert i {
            margin-top: 3px;
        }

        .close-fin-groups {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .close-fin-group {
            border: 1px solid #e5edf3;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .close-fin-group-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e5edf3;
            font-size: .85rem;
            font-weight: 700;
            color: #155e75;
        }

        .close-fin-list {
            margin: 0;
            padding: 4px 12px;
        }

        .close-fin-list > div {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
            padding: 7px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .close-fin-list > div:last-child {
            border-bottom: 0;
        }

        .close-fin-list dt {
            font-weight: 500;
            color: #64748b;
            font-size: .875rem;
        }

        .close-fin-list dd {
            margin: 0;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .close-fin-totals {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .close-fin-total {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid;
        }

        .close-fin-total--collect {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .close-fin-total--refund {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .close-fin-total-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(255, 255, 255, .75);
            font-size: 1.1rem;
        }

        .close-fin-total-label {
            display: block;
            font-weight: 700;
        }

        .close-fin-total-hint {
            display: block;
            font-size: .75rem;
            opacity: .75;
        }

        .close-fin-total-value {
            display: block;
            margin-top: 2px;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .close-fin-notes {
            margin: 12px 0 0;
            padding-inline-start: 18px;
            color: #64748b;
            font-size: .82rem;
            line-height: 1.8;
        }

        @media (max-width: 991.98px) {
            .close-info-grid,
            .close-timeline {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .close-fin-groups,
            .close-fin-totals {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .close-info-grid,
            .close-timeline {
                grid-template-columns: 1fr;
            }
        }

        .close-option-card {
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .close-option-card:hover {
            border-color: #94a3b8;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .close-option-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .close-option-list {
            padding-right: 18px;
            margin-bottom: 16px;
            color: #475569;
            font-size: 13px;
            line-height: 1.8;
            text-align: right;
        }

        .transfer-summary-box {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 10px;
            padding: 12px 14px;
            color: #78350f;
        }

        @media (max-width: 991.98px) {
            .rent-stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .extend-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .rent-stats-grid {
                grid-template-columns: 1fr;
            }

            .extend-stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.All Rent contracts') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))
        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-handshake text-primary me-2"></i>
                {{ helperTrans('ingaz.All Rent contracts') }}
            </h3>
            <div>
                @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
                    <a href="{{ route('RentContract.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        {{ helperTrans('ingaz.add Rent contract') }}
                    </a>
                @endif
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="rent-stats-grid">
            <button type="button" class="rent-stat-card rent-stat-primary" data-status-filter=""
                aria-pressed="false" title="انقر لعرض جميع العقود">
                <div>
                    <div class="rent-stat-label">إجمالي عقود التأجير</div>
                    <div class="rent-stat-value" id="totalContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
            </button>

            <button type="button" class="rent-stat-card rent-stat-success" data-status-filter="active"
                aria-pressed="false" title="انقر لتصفية العقود النشطة">
                <div>
                    <div class="rent-stat-label">العقود النشطة</div>
                    <div class="rent-stat-value" id="activeContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </button>

            <button type="button" class="rent-stat-card rent-stat-info" data-status-filter="completed"
                aria-pressed="false" title="انقر لتصفية العقود المكتملة">
                <div>
                    <div class="rent-stat-label">العقود المكتملة</div>
                    <div class="rent-stat-value" id="completedContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
            </button>

            <button type="button" class="rent-stat-card rent-stat-muted" data-status-filter="available"
                aria-pressed="false" title="انقر لتصفية العقود المغلقة والمتاحة">
                <div>
                    <div class="rent-stat-label">مغلقة - متاحة</div>
                    <div class="rent-stat-value" id="availableContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </button>

            <button type="button" class="rent-stat-card rent-stat-warning" data-status-filter="transferred"
                aria-pressed="false" title="انقر لتصفية العقود المنقولة">
                <div>
                    <div class="rent-stat-label">تم النقل</div>
                    <div class="rent-stat-value" id="transferredContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </button>

            <button type="button" class="rent-stat-card rent-stat-danger" data-status-filter="cancelled"
                aria-pressed="false" title="انقر لتصفية العقود الملغية">
                <div>
                    <div class="rent-stat-label">ملغية قبل اكتمال المدة</div>
                    <div class="rent-stat-value" id="cancelledContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="card mb-4">
            <div class="card-body">
                <label for="quickSearchFilter" class="form-label fw-bold">بحث شامل في عقود التأجير</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                    <input type="search" id="quickSearchFilter" class="form-control"
                        value="{{ request('quick_search') }}" autocomplete="off"
                        placeholder="رقم العقد، هوية العميل، الاسم، الجوال، جواز أو إقامة العاملة..."
                        aria-describedby="quickSearchHelp">
                    <button type="button" class="btn btn-outline-secondary" id="clearQuickSearch">مسح البحث</button>
                </div>
                <small id="quickSearchHelp" class="text-muted">تظهر النتائج تلقائيًا أثناء الكتابة ضمن الفلاتر المحددة. يمكنك كتابة أكثر من معلومة معًا، مثل اسم العميل واسم العاملة.</small>
            </div>
        </div>

        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلاتر البحث
                    </h6>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="card-body collapse" id="filtersCollapse">
                <div class="filter-section-title">حالة العقد وتاريخ الإنشاء</div>
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">حالة العقد:</label>
                        <select id="statusFilter" class="form-select">
                            <option value="" {{ request('status') ? '' : 'selected' }}>جميع الحالات</option>
                            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>جديد</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="contract_sent"
                                {{ in_array(request('status'), ['contract_sent', 'waiting_contract_send'], true) ? 'selected' : '' }}>
                                بانتظار التوقيع
                            </option>
                            <option value="contract_signed"
                                {{ request('status') === 'contract_signed' ? 'selected' : '' }}>
                                تم توقيع العقد
                            </option>
                            <option value="maid_delivered" {{ request('status') === 'maid_delivered' ? 'selected' : '' }}>
                                تم تسليم العاملة
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل
                            </option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>مغلق -
                                متاحة</option>
                            <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>تم
                                النقل</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي
                            </option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>معلق
                            </option>
                            <option value="frozen" {{ request('status') === 'frozen' ? 'selected' : '' }}>مجمد</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="paymentStatusFilter" class="form-label">حالة الدفع:</label>
                        <select id="paymentStatusFilter" class="form-select">
                            <option value="">جميع حالات الدفع</option>
                            @foreach (['paid' => 'تم الدفع', 'partial' => 'مدفوع جزئياً', 'pending' => 'معلق', 'later' => 'الدفع لاحقاً', 'bank_pending' => 'بانتظار التحويل'] as $paymentStatus => $paymentLabel)
                                <option value="{{ $paymentStatus }}" {{ request('payment_status') === $paymentStatus ? 'selected' : '' }}>{{ $paymentLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">تاريخ إنشاء العقد من:</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="dateToFilter" class="form-label">تاريخ إنشاء العقد إلى:</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="expiryFilter" class="form-label">حالة انتهاء العقد:</label>
                        <select id="expiryFilter" class="form-select">
                            <option value="" {{ request('expiry_filter') ? '' : 'selected' }}>جميع العقود</option>
                            <option value="expired" {{ request('expiry_filter') === 'expired' ? 'selected' : '' }}>
                                العقود المنتهية
                            </option>
                            <option value="not_expired"
                                {{ request('expiry_filter') === 'not_expired' ? 'selected' : '' }}>
                                العقود غير المنتهية
                            </option>
                        </select>
                    </div>
                </div>

                <div class="filter-section-title">تواريخ مدة العقد</div>
                <div class="row">
                    <div class="col-md-3">
                        <label for="startDateFromFilter" class="form-label">بداية العقد من:</label>
                        <input type="date" id="startDateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="startDateToFilter" class="form-label">بداية العقد إلى:</label>
                        <input type="date" id="startDateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDateFromFilter" class="form-label">نهاية العقد من:</label>
                        <input type="date" id="endDateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDateToFilter" class="form-label">نهاية العقد إلى:</label>
                        <input type="date" id="endDateToFilter" class="form-control">
                    </div>
                </div>

                <div class="filter-section-title">بيانات العميل والعاملة</div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="clientPhoneFilter" class="form-label">رقم جوال العميل:</label>
                        <input type="text" id="clientPhoneFilter" class="form-control"
                            placeholder="ابحث برقم الجوال (5********)">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                        <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                    </div>
                    <div class="col-md-3">
                        <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                        <input type="text" id="maidPassportFilter" class="form-control"
                            placeholder="ابحث برقم الجواز">
                    </div>
                    <div class="col-md-3">
                        <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                        <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                    </div>
                    <div class="col-md-3">
                        <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                        <input type="text" id="contractNumberFilter" class="form-control"
                            placeholder="ابحث برقم العقد">
                    </div>
                </div>
                <div class="filter-section-title">التصنيف والمتابعة</div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="marketerFilter" class="form-label">المسوق:</label>
                        <select id="marketerFilter" class="form-select">
                            <option value="">جميع المسوقين</option>
                            @foreach ($marketers ?? collect() as $marketer)
                                <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-3'])
                    <div class="col-md-3">
                        <label for="extensionFilter" class="form-label">حالة التمديد:</label>
                        <select id="extensionFilter" class="form-select">
                            <option value="">جميع العقود</option>
                            <option value="extended">عقود ممددة</option>
                            <option value="not_extended">غير ممددة</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-search me-2"></i>تطبيق الفلاتر
                            </button>
                            <button type="button" class="btn btn-secondary" id="clearFilters">
                                <i class="fas fa-times me-2"></i>مسح الفلاتر
                            </button>
                            <button type="button" class="btn btn-success" id="exportExcel">
                                <i class="fas fa-file-excel me-2"></i>تصدير Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="clean-loading-overlay" style="display: none;">
            <div class="clean-loading-content">
                <div class="clean-loader-main">
                    <div class="clean-loader-dot"></div>
                    <div class="clean-loader-dot"></div>
                    <div class="clean-loader-dot"></div>
                </div>
                <div class="clean-loading-text">جاري تحميل عقود التأجير...</div>
                <div class="clean-loading-subtitle">يرجى الانتظار</div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>
                    قائمة عقود التأجير
                </h6>
            </div>
            <div class="card-body">

                <table class="table table-bordered table-style" id="dataTableObject" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID العقد</th>
                            <th>بيانات العميل</th>
                            <th>العاملة</th>
                            <th>معلومات العقد</th>
                            <th>المسوق</th>
                            @if ($branchesEnabled ?? false)
                                <th>{{ __('admin.branches') }}</th>
                            @endif
                            <th>الحالة</th>
                            <th title="الأيام المستفادة والمتبقية">الأيام</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- سيتم تعبئتها بواسطة DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

    <!-- Modal لإغلاق العقد -->
    <div class="modal fade" id="closeContractModal" tabindex="-1" aria-labelledby="closeContractModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl close-contract-modal">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="closeContractModalLabel">
                        <i class="fas fa-file-signature me-2"></i>
                        إنهاء عقد الإيجار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- معلومات العقد -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-info-circle"></i>معلومات العقد</div>
                                <div class="section-body">
                                    <div class="close-info-grid" id="contractDetails">
                                        <div class="close-info-item">
                                            <span class="close-info-label"><i class="fas fa-hashtag"></i>رقم العقد</span>
                                            <span class="close-info-value" id="contractId">-</span>
                                        </div>
                                        <div class="close-info-item">
                                            <span class="close-info-label"><i class="fas fa-user"></i>العميل</span>
                                            <span class="close-info-value" id="clientName">-</span>
                                        </div>
                                        <div class="close-info-item">
                                            <span class="close-info-label"><i class="fas fa-female"></i>العاملة</span>
                                            <span class="close-info-value" id="maidName">-</span>
                                        </div>
                                        <div class="close-info-item">
                                            <span class="close-info-label"><i class="fas fa-money-bill-wave"></i>قيمة العقد</span>
                                            <span class="close-info-value" id="contractValue">-</span>
                                        </div>
                                        <div class="close-info-item close-info-item--wide">
                                            <span class="close-info-label"><i class="fas fa-flag"></i>الحالة الحالية</span>
                                            <span class="close-info-value" id="currentStatus">-</span>
                                        </div>
                                    </div>

                                    <div class="close-timeline">
                                        <div class="close-timeline-item">
                                            <span class="close-info-label"><i class="fas fa-play"></i>تاريخ البداية</span>
                                            <span class="close-info-value" id="startDate">-</span>
                                        </div>
                                        <div class="close-timeline-item">
                                            <span class="close-info-label"><i class="fas fa-flag-checkered"></i>تاريخ النهاية</span>
                                            <span class="close-info-value" id="endDate">-</span>
                                        </div>
                                        <div class="close-timeline-item close-timeline-item--input">
                                            <label for="closureDate" class="close-info-label"><i class="fas fa-calendar-times"></i>تاريخ الإنهاء <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="closureDate" required>
                                        </div>
                                        <div class="close-timeline-item">
                                            <span class="close-info-label"><i class="fas fa-hourglass-half"></i>المدة المتبقية</span>
                                            <span class="close-info-value" id="remainingDays">-</span>
                                        </div>
                                    </div>

                                    <div class="close-alert d-none" id="earlyClosureNotice">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>سيتم تسجيل هذا العقد كعقد <strong>ملغي</strong> لأن تاريخ الإنهاء قبل تاريخ نهاية العقد، وسيظهر ذلك
                                        في التفاصيل والإحصائيات.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4" id="closeContractAttachmentsRow" style="display: none;">
                        <div class="col-12">
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-paperclip"></i>مرفقات العقد</div>
                                <div class="section-body">
                                    <div id="contractAttachmentsList" class="d-flex flex-wrap gap-2">
                                        <span class="text-muted">لا توجد مرفقات مسجلة على هذا العقد.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4" id="closeFinancialSettlementRow" style="display: none;">
                        <div class="col-12">
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-file-invoice-dollar"></i>البيان المالي قبل
                                    الإنهاء</div>
                                <div class="section-body">
                                    <div class="close-fin-groups">
                                        <div class="close-fin-group">
                                            <div class="close-fin-group-title"><i class="fas fa-file-contract"></i>قيمة العقد والسداد</div>
                                            <dl class="close-fin-list">
                                                <div><dt>التكلفة الشهرية</dt><dd id="financialMonthlyCost">-</dd></div>
                                                <div><dt>مدة العقد</dt><dd id="financialDurationMonths">-</dd></div>
                                                <div><dt>مدة العقد (يوم)</dt><dd id="financialDurationDays">-</dd></div>
                                                <div><dt>قيمة العقد</dt><dd id="financialContractValue">-</dd></div>
                                                <div><dt>المبلغ المدفوع</dt><dd id="financialPaidAmount">-</dd></div>
                                                <div><dt>المبلغ المتبقي</dt><dd id="financialRemainingAmount">-</dd></div>
                                            </dl>
                                        </div>
                                        <div class="close-fin-group">
                                            <div class="close-fin-group-title"><i class="fas fa-calendar-day"></i>الأيام وتكلفتها</div>
                                            <dl class="close-fin-list">
                                                <div><dt>الأيام المستخدمة</dt><dd id="financialUsedDays">-</dd></div>
                                                <div><dt>الأيام المتبقية</dt><dd id="financialRemainingDays">-</dd></div>
                                                <div><dt>تكلفة اليوم</dt><dd id="financialDailyRate">-</dd></div>
                                                <div><dt>تكلفة اليوم المعتمدة داخل العقد</dt><dd id="financialUsedDailyRate">-</dd></div>
                                                <div><dt>تكلفة الأيام المستخدمة</dt><dd id="financialUsedCost">-</dd></div>
                                            </dl>
                                        </div>
                                        <div class="close-fin-group">
                                            <div class="close-fin-group-title"><i class="fas fa-clock"></i>بعد انتهاء العقد</div>
                                            <dl class="close-fin-list">
                                                <div><dt>أيام بعد انتهاء العقد</dt><dd id="financialOverdueDays">-</dd></div>
                                                <div><dt>تكلفة اليوم بعد انتهاء العقد</dt><dd id="financialOverdueDailyRate">-</dd></div>
                                                <div><dt>تكلفة الأيام بعد انتهاء العقد</dt><dd id="financialOverdueCost">-</dd></div>
                                            </dl>
                                        </div>
                                        <div class="close-fin-group">
                                            <div class="close-fin-group-title"><i class="fas fa-receipt"></i>إشعارات تعديل الفاتورة</div>
                                            <dl class="close-fin-list">
                                                <div><dt>الإشعار الدائن لتخفيض الفاتورة</dt><dd id="financialSuggestedRefund">-</dd></div>
                                                <div><dt>الإشعار المدين لزيادة الفاتورة</dt><dd id="financialCompanyDue">-</dd></div>
                                            </dl>
                                        </div>
                                    </div>

                                    <div class="close-fin-totals">
                                        <div class="close-fin-total close-fin-total--collect">
                                            <span class="close-fin-total-icon"><i class="fas fa-hand-holding-usd"></i></span>
                                            <div>
                                                <span class="close-fin-total-label">المطلوب تحصيله من العميل</span>
                                                <span class="close-fin-total-hint">حسب مدة الاستخدام والمدفوع</span>
                                                <span class="close-fin-total-value" id="financialCashToCollect" aria-live="polite">-</span>
                                            </div>
                                        </div>
                                        <div class="close-fin-total close-fin-total--refund">
                                            <span class="close-fin-total-icon"><i class="fas fa-undo-alt"></i></span>
                                            <div>
                                                <span class="close-fin-total-label">المبلغ المسترد للعميل</span>
                                                <span class="close-fin-total-hint">حسب مدة الاستخدام والمدفوع</span>
                                                <span class="close-fin-total-value" id="financialCashRefund" aria-live="polite">-</span>
                                            </div>
                                        </div>
                                    </div>

                                    <ul class="close-fin-notes">
                                        <li>الإشعار الدائن يخفض قيمة الفاتورة السابقة، ولا يعني صرف هذا المبلغ للعميل. المبلغ المسترد يعتمد على ما دفعه العميل مقابل تكلفة الاستخدام.</li>
                                        <li>حساب الأيام الحالي يشمل يوم البداية ويوم الإنهاء ضمن مدة العقد.</li>
                                    </ul>
                                    <div class="row g-3 mt-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">نوع إشعار تعديل الفاتورة</label>
                                            <input type="hidden" id="closeAdjustmentType" value="none">
                                            <input type="text" class="form-control" id="closeAdjustmentTypeLabel"
                                                value="يُحدد تلقائياً" readonly>
                                            <div class="form-text">يُحدد من فرق المفوتر والمستحق الفعلي ولا يقبل التعديل.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" id="closeAdjustmentAmountLabel">قيمة
                                                الإشعار الضريبي</label>
                                            <input type="number" class="form-control" id="closeAdjustmentAmount"
                                                min="0" step="0.01" value="0" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">قيمة الإشعار المعتمدة</label>
                                            <div class="fw-bold">
                                                <span id="approvedClosureNoteAmount">0.00 ريال</span>
                                            </div>
                                        </div>
                                        <div id="manualClosureCashNotice" class="col-12 text-muted d-none">
                                            مبالغ التحصيل والاسترداد أعلاه حسب الحسبة الآلية قبل تعديل قيمة الإشعار يدويًا.
                                        </div>
                                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE) && @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE))
                                            <div class="col-12">
                                                <div class="border rounded p-3 bg-light">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="useCustomClosureDailyRates">
                                                        <label class="form-check-label fw-bold" for="useCustomClosureDailyRates">تعديل تكلفة اليوم عند الإنهاء</label>
                                                    </div>
                                                    <div class="row g-3 d-none" id="customClosureDailyRateFields">
                                                        <div class="col-md-4" id="earlyClosureDailyRateWrapper">
                                                            <label class="form-label" for="earlyClosureDailyRate">تكلفة اليوم للإنهاء المبكر (ريال)</label>
                                                            <input type="number" class="form-control" id="earlyClosureDailyRate" min="0" max="999999.99" step="0.01">
                                                            <div class="form-text">تطبق على الأيام المستخدمة فقط عند الإنهاء قبل اكتمال المدة، ولو تجاوزت شهرًا.</div>
                                                        </div>
                                                        <div class="col-md-4" id="overdueClosureDailyRateWrapper">
                                                            <label class="form-label" for="overdueClosureDailyRate">تكلفة اليوم بعد انتهاء العقد (ريال)</label>
                                                            <input type="number" class="form-control" id="overdueClosureDailyRate" min="0" max="999999.99" step="0.01">
                                                            <div class="form-text">تطبق على الأيام الزائدة فقط؛ مدة العقد المكتملة تحتفظ بقيمتها المتفق عليها.</div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="customClosureDailyRateReason">سبب اعتماد السعر</label>
                                                            <textarea class="form-control" id="customClosureDailyRateReason" rows="2" maxlength="1000"></textarea>
                                                        </div>
                                                        <div class="col-12 form-text">يمكن زيادة السعر أو تخفيضه. المبالغ شاملة الضريبة حسب تركيبة الفاتورة، ويعاد حساب التحصيل والاسترداد والإشعار تلقائيًا.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light">
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="useDailyClosureDiscount">
                                                        <label class="form-check-label fw-bold"
                                                            for="useDailyClosureDiscount">
                                                            تطبيق خصم على تكلفة اليوم
                                                        </label>
                                                    </div>
                                                    <div class="row g-3 mb-3 d-none" id="dailyClosureDiscountFields">
                                                        <div class="col-md-3">
                                                            <label class="form-label">نوع الخصم</label>
                                                            <select class="form-select" id="dailyClosureDiscountType">
                                                                <option value="fixed">قيمة ثابتة من اليوم</option>
                                                                <option value="percentage">نسبة مئوية من اليوم</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">قيمة الخصم</label>
                                                            <input type="number" class="form-control"
                                                                id="dailyClosureDiscountValue" min="0.01" step="0.01">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">تكلفة اليوم بعد الخصم</label>
                                                            <input type="text" class="form-control"
                                                                id="discountedClosureDailyRate" readonly>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">سبب الخصم</label>
                                                            <input type="text" class="form-control"
                                                                id="dailyClosureDiscountReason" maxlength="1000"
                                                                placeholder="سبب اعتماد الخصم...">
                                                        </div>
                                                    </div>
                                            </div>
                                        </div>
                                        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE))
                                            <div class="col-12">
                                                <div class="border rounded p-3 bg-light">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="useManualClosureAdjustment"
                                                            {{ @AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE) ? '' : 'disabled' }}>
                                                        <label class="form-check-label fw-bold"
                                                            for="useManualClosureAdjustment">
                                                            استخدام قيمة تسوية يدوية معتمدة
                                                        </label>
                                                    </div>
                                                    @if (!@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE))
                                                        <div class="alert alert-warning py-2 mt-2 mb-0">
                                                            لديك صلاحية إدخال التسوية، لكن يلزم منح صلاحية اعتماد التسوية قبل استخدامها.
                                                        </div>
                                                    @endif
                                                    <div class="row g-3 mt-1 d-none" id="manualClosureAdjustmentFields">
                                                        <div class="col-md-4">
                                                            <label class="form-label">القيمة النهائية المعتمدة</label>
                                                            <input type="number" class="form-control"
                                                                id="manualClosureAdjustmentAmount" min="0.01"
                                                                step="0.01" value="0">
                                                            <div class="form-text">نوع الإشعار يظل محسوباً آلياً ولا يمكن تغييره.</div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">سبب فرق التسوية</label>
                                                            <textarea class="form-control" id="manualClosureAdjustmentReason" rows="2"
                                                                maxlength="1000"
                                                                placeholder="اذكر سبب الفرق والمرجع الخارجي..."></textarea>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">مرفق اعتماد التسوية</label>
                                                            <input type="file" class="form-control"
                                                                id="manualClosureAdjustmentAttachment"
                                                                accept=".jpg,.jpeg,.png,.pdf">
                                                            <div class="form-text">PDF أو صورة، بحد أقصى 4 ميجابايت.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-12">
                                            <label class="form-label">سبب الإرجاع / الإغلاق المبكر</label>
                                            <textarea class="form-control" id="closeReturnReason" rows="3"
                                                placeholder="اكتب سبب إرجاع العاملة أو الإغلاق المبكر..."></textarea>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-3">
                                        <div class="col-md-4">
                                            <label class="form-label">تحديث حالة الدفع بعد الإغلاق</label>
                                            <select class="form-select" id="closePaymentStatus">
                                                <option value="">بدون تغيير</option>
                                                <option value="paid">تم الدفع</option>
                                                <option value="partial">مدفوع جزئياً</option>
                                                <option value="pending">معلق</option>
                                                <option value="later">الدفع لاحقاً</option>
                                                <option value="bank_pending">بانتظار التحويل</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">إيصال التحويل</label>
                                            <input type="file" class="form-control" id="closePaymentReceipt"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">آيبان العميل <small
                                                    class="text-muted">(اختياري)</small></label>
                                            <input type="text" class="form-control" id="closeClientIban"
                                                dir="ltr" maxlength="34" placeholder="SAxxxxxxxxxxxxxxxxxxxxxx">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">مرفق آيبان العميل <small
                                                    class="text-muted">(اختياري)</small></label>
                                            <input type="file" class="form-control" id="closeClientIbanAttachment"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                            <div class="form-text d-none" id="closeClientIbanAttachmentCurrentWrapper">
                                                <a href="#" target="_blank"
                                                    id="closeClientIbanAttachmentCurrent">عرض المرفق الحالي</a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox"
                                                    id="sendClosureMessageToggle" checked>
                                                <label class="form-check-label" for="sendClosureMessageToggle">
                                                    إرسال رسالة إنهاء العقد للعميل
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="button" class="btn btn-secondary" id="backToCloseOptionsBtn">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            رجوع للخيارات
                                        </button>
                                        <button type="button" class="btn btn-success"
                                            id="confirmCloseAndMakeAvailableBtn">
                                            <i class="fas fa-check me-2"></i>
                                            تأكيد الإغلاق والإتاحة
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- خيارات الإغلاق -->
                    <div class="row g-3" id="closeContractOptionsRow">
                        <div class="col-md-6">
                            <div class="close-option-card">
                                <div class="close-option-icon bg-success text-white">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h6 class="fw-bold mb-2">إغلاق وإرجاع العاملة متاحة</h6>
                                <ul class="close-option-list">
                                    <li>يتم إنهاء عقد الإيجار الحالي.</li>
                                    <li>تعود العاملة إلى حالة متاحة للتأجير.</li>
                                    <li>الرصيد النهائي يُحسب تلقائياً من المستحق الفعلي والمدفوع، ولا يُصفّر يدوياً.</li>
                                </ul>
                                <button type="button" class="btn btn-success w-100" id="closeAndMakeAvailableBtn">
                                    <i class="fas fa-check me-2"></i>
                                    اختيار الإغلاق والإتاحة
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="close-option-card">
                                <div class="close-option-icon bg-warning text-dark">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <h6 class="fw-bold mb-2">إغلاق ونقل الخدمات</h6>
                                <ul class="close-option-list">
                                    <li>يتم إنهاء عقد الإيجار الحالي بحالة تم النقل.</li>
                                    <li>تبقى العاملة غير متاحة لأنها خرجت بنقل خدمات.</li>
                                    <li>لا يتم إنشاء عقد جديد، وتسجل مبالغ النقل فقط.</li>
                                </ul>
                                <button type="button" class="btn btn-warning w-100" id="transferSponsorshipBtn">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    اختيار نقل الخدمات
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- نموذج نقل الخدمات (مخفي في البداية) -->
                    <div id="transferSponsorshipForm" style="display: none;" class="mt-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    تأكيد نقل الخدمات
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- معلومات النقل -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <small class="text-muted">الكفيل القديم</small>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="mb-1">
                                                    {{ documentSetting('document_company_name', documentSetting('website_name', config('app.name'))) }}
                                                </h6>
                                                <small class="text-muted">عقد إيجار</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <small class="text-muted">الكفيل الجديد</small>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="mb-1" id="transferClientName">-</h6>
                                                <small class="text-muted">عقد استقدام</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form id="sponsorshipTransferForm" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="transfer-summary-box mb-3">
                                                <i class="fas fa-info-circle me-1"></i>
                                                نقل الخدمات لا ينشئ عقداً جديداً. أدخل المبلغ الكامل للنقل، وسيتم استخدامه
                                                في التوثيق والبنود المالية.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="totalAmount" class="form-label">المبلغ الكامل <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="totalAmount"
                                                    name="totalAmount" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="paidAmount" class="form-label">المبلغ المدفوع <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="paidAmount"
                                                    name="paidAmount" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="remainingAmount" class="form-label">المبلغ المتبقي</label>
                                                <input type="number" class="form-control" id="remainingAmount"
                                                    name="remainingAmount" step="0.01" readonly>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-credit-card text-primary me-2"></i>
                                                        بيانات الدفع
                                                    </h6>
                                                    <small class="text-muted">طريقة الدفع وحالة السداد والمرفقات</small>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentType" class="form-label">طريقة الدفع
                                                            <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="transferPaymentType"
                                                            name="payment_type" required>
                                                            <option value="">اختر الطريقة</option>
                                                            <option value="cash">نقداً</option>
                                                            <option value="bank_transfer">تحويل بنكي</option>
                                                            <option value="mada">مدى</option>
                                                            <option value="tabby">تابي</option>
                                                            <option value="tamara">تمارا</option>
                                                            <option value="pay_later">الدفع لاحقاً</option>
                                                            <option value="other">أخرى</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentStatus" class="form-label">حالة الدفع
                                                            <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="transferPaymentStatus"
                                                            name="payment_status" required>
                                                            <option value="">اختر الحالة</option>
                                                            <option value="paid">تم الدفع</option>
                                                            <option value="partial">مدفوع جزئياً</option>
                                                            <option value="pending">معلق</option>
                                                            <option value="later">الدفع لاحقاً</option>
                                                            <option value="bank_pending">بانتظار التحويل</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentReceipt" class="form-label">
                                                            إيصال الدفع <span class="text-danger d-none"
                                                                id="transferReceiptRequiredMark">*</span>
                                                        </label>
                                                        <input type="file" class="form-control"
                                                            id="transferPaymentReceipt" name="payment_receipt"
                                                            accept=".jpg,.jpeg,.png,.pdf">
                                                        <div class="form-text d-none" id="transferReceiptRequiredNote">
                                                            الإيصال مطلوب عند اختيار تم الدفع أو مدفوع جزئياً.
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 d-none" id="transferReceiptAccountWrapper">
                                                        <label for="transferReceiptAccount" class="form-label">البنك / حساب السداد <span class="text-danger">*</span></label>
                                                        <select class="form-select" id="transferReceiptAccount" name="receipt_account_id">
                                                            <option value="">اختر الحساب الذي سيصدر عليه سند القبض</option>
                                                            @foreach($extensionReceiptAccounts as $account)
                                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="transferAttachments" class="form-label">مرفقات
                                                            إضافية</label>
                                                        <input type="file" class="form-control"
                                                            id="transferAttachments" name="attachments[]"
                                                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="transferNotes" class="form-label">ملاحظات</label>
                                                <textarea class="form-control" id="transferNotes" name="transferNotes" rows="3"
                                                    placeholder="ملاحظات إضافية حول عملية نقل الخدمات..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox"
                                                    id="sendTransferClosureMessageToggle" checked>
                                                <label class="form-check-label" for="sendTransferClosureMessageToggle">
                                                    إرسال رسالة إغلاق ونقل خدمات للعميل
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">بنود نقل الخدمات</h6>
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                        id="add-transfer-adjustment">
                                                        <i class="fas fa-plus me-1"></i>إضافة بند
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 18%;">النوع</th>
                                                                <th>البند</th>
                                                                <th style="width: 20%;">المبلغ</th>
                                                                <th style="width: 12%;">إجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="transfer-adjustment-rows"></tbody>
                                                    </table>
                                                </div>
                                                <template id="transfer-adjustment-row-template">
                                                    <tr>
                                                        <td>
                                                            <select
                                                                class="form-select form-select-sm transfer-adjustment-type">
                                                                <option value="income">إيراد</option>
                                                                <option value="expense">مصروف</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm transfer-adjustment-label"
                                                                placeholder="اسم البند">
                                                        </td>
                                                        <td>
                                                            <input type="number"
                                                                class="form-control form-control-sm text-end transfer-adjustment-amount"
                                                                step="0.01" min="0" value="0.00">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm remove-transfer-adjustment">
                                                                حذف
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" id="cancelTransferBtn">
                                            <i class="fas fa-times me-2"></i>
                                            إلغاء
                                        </button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-check me-2"></i>
                                            تأكيد نقل الخدمات
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="extendContractModal" tabindex="-1" aria-labelledby="extendContractModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl extend-contract-modal">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="extendContractModalLabel">
                        <i class="fas fa-calendar-plus me-2"></i>
                        تمديد عقد الإيجار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="extendContractErrors" class="alert alert-danger d-none"></div>

                    <div class="extend-summary-band mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <div class="text-muted small">رقم العقد</div>
                                <div class="h5 mb-0" id="extend_contract_number">-</div>
                            </div>
                            <div class="extend-status-pill is-ok" id="extend_contract_status_badge">
                                <i class="fas fa-circle-check"></i>
                                <span>ضمن المدة</span>
                            </div>
                        </div>
                        <div class="extend-stat-grid">
                            <div class="extend-stat">
                                <div class="extend-stat-label">بداية العقد</div>
                                <div class="extend-stat-value" id="extend_current_start_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">نهاية العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_current_end_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">مدة العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_current_duration_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">الأيام المستخدمة</div>
                                <div class="extend-stat-value" id="extend_used_days_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">الأيام المتبقية / التأخير</div>
                                <div class="extend-stat-value" id="extend_remaining_days_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">قيمة العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_contract_value_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">تكلفة اليوم التقريبية</div>
                                <div class="extend-stat-value" id="extend_daily_rate_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">عدد مرات التمديد</div>
                                <div class="extend-stat-value" id="extend_extensions_count_text">-</div>
                            </div>
                        </div>
                    </div>

                    <form id="extendContractForm" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="extend_current_end_value">
                        <input type="hidden" id="extend_monthly_cost_value">
                        <input type="hidden" id="extend_contract_value_value">
                        <input type="hidden" id="extend_duration_days_value">
                        <input type="hidden" id="extend_daily_rate_value">
                        <input type="hidden" id="extend_net_value">

                        <div class="mb-3">
                            <label for="extend_package_id" class="form-label">باقة التمديد</label>
                            <select class="form-select" id="extend_package_id" name="extension_package_id">
                                <option value="">الباقة الحالية — بنفس سعر العقد المتفق عليه</option>
                                @foreach ($extensionPackages ?? [] as $package)
                                    @if ($package->rental_type !== 'hourly' && $package->duration_unit !== 'hour' && $package->days_count > 0 && $package->price_with_tax > 0)
                                        <option value="{{ $package->id }}" data-nationality="{{ $package->nationality_id }}"
                                            data-days="{{ $package->days_count }}" data-price="{{ $package->price_with_tax }}">
                                            {{ $package->title }} — {{ $package->days_count }} يوم — {{ number_format($package->price_with_tax, 2) }} ريال
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">الباقة الجديدة تخص فترة التمديد فقط. تعديل المدة يحسب السعر بنسبة أيام الباقة.</div>
                            <div class="form-text d-none" id="extend_package_tax_preview"></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="extend_extension_months" class="form-label">شهور التمديد</label>
                                <input type="number" class="form-control" id="extend_extension_months"
                                    name="extension_months" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_extra_days" class="form-label">أيام إضافية</label>
                                <input type="number" class="form-control" id="extend_extension_extra_days"
                                    name="extension_days" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_days" class="form-label">إجمالي أيام التمديد</label>
                                <input type="text" class="form-control bg-light" id="extend_extension_days" readonly>
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_end_date" class="form-label">تاريخ النهاية الجديد</label>
                                <input type="text" class="form-control bg-light" id="extend_extension_end_date"
                                    readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="extend_extension_discount_type" class="form-label">نوع الخصم</label>
                                <select class="form-select" id="extend_extension_discount_type"
                                    name="extension_discount_type">
                                    <option value="">بدون خصم</option>
                                    <option value="percentage">نسبة مئوية (%)</option>
                                    <option value="amount">مبلغ مباشر (ريال)</option>
                                </select>
                            </div>
                            <div class="col-md-4 extend-discount-fields d-none">
                                <label for="extend_extension_discount_value" class="form-label">
                                    قيمة الخصم <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="extend_extension_discount_value"
                                        name="extension_discount_value" min="0" step="0.01" value="">
                                    <span class="input-group-text" id="extend_extension_discount_unit">ريال</span>
                                </div>
                                <div class="form-text">الخصم يُطرح من إجمالي التمديد شامل الضريبة</div>
                            </div>
                            <div class="col-md-4 extend-discount-fields d-none">
                                <label for="extend_extension_discount_notes" class="form-label">سبب الخصم</label>
                                <input type="text" class="form-control" id="extend_extension_discount_notes"
                                    name="extension_discount_notes" maxlength="255" placeholder="اختياري">
                            </div>
                            <div class="col-12">
                                <div class="extend-preview-box">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="text-muted small">قيمة التمديد قبل الخصم</div>
                                            <div class="fw-bold" id="extend_extension_value">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-muted small">الخصم</div>
                                            <div class="fw-bold text-danger" id="extend_extension_discount_text">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-muted small">مدة التمديد المحفوظة</div>
                                            <div class="fw-bold" id="extend_new_duration_text">-</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-muted small">الصافي المستحق على العميل</div>
                                            <div class="fw-bold text-success" id="extend_new_contract_value_text">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_payment_status" class="form-label">حالة دفع التمديد <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="extend_extension_payment_status"
                                    name="extension_payment_status" required>
                                    <option value="">اختر الحالة</option>
                                    <option value="paid">تم الدفع</option>
                                    <option value="partial">مدفوع جزئياً</option>
                                    <option value="pending">معلق</option>
                                    <option value="later">الدفع لاحقاً</option>
                                    <option value="bank_pending">بانتظار التحويل</option>
                                </select>
                            </div>
                            <div class="col-md-6 extend-receipt-fields d-none">
                                <label for="extend_extension_payment_type" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                <select class="form-select" id="extend_extension_payment_type" name="extension_payment_type">
                                    <option value="">اختر طريقة الدفع</option>
                                    <option value="cash">نقدي</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="mada">مدى / شبكة</option>
                                    <option value="tabby">تابي</option>
                                    <option value="tamara">تمارا</option>
                                    <option value="moyasar">ميسر</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-6 extend-receipt-fields d-none">
                                <label for="extend_extension_receipt_account_id" class="form-label">البنك / حساب القبض <span class="text-danger">*</span></label>
                                <select class="form-select" id="extend_extension_receipt_account_id" name="extension_receipt_account_id">
                                    <option value="">اختر الحساب الذي سيصدر عليه السند</option>
                                    @foreach ($extensionReceiptAccounts as $account)
                                        @php
                                            $accountType = (int) $account->getRawOriginal('type');
                                            $accountTypeLabel = match ($accountType) {
                                                \Nami\Finance\Enums\AccountTypesEnum::Bank->value => 'بنك',
                                                \Nami\Finance\Enums\AccountTypesEnum::Box->value => 'صندوق',
                                                \Nami\Finance\Enums\AccountTypesEnum::ElectronicWallet->value => 'محفظة إلكترونية',
                                                default => 'حساب قبض',
                                            };
                                        @endphp
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }} ({{ $accountTypeLabel }})</option>
                                    @endforeach
                                </select>
                                @if ($extensionReceiptAccounts->isEmpty())
                                    <small class="text-danger">لا توجد حسابات بنك/صندوق معرفة في النظام المالي.</small>
                                @endif
                            </div>
                            <div class="col-md-6 extend-receipt-fields d-none">
                                <label for="extend_extension_payment_amount" class="form-label">مبلغ سند القبض <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="extend_extension_payment_amount"
                                    name="extension_payment_amount" min="0.01" step="0.01">
                            </div>
                            <div class="col-md-6 extend-receipt-fields d-none">
                                <label for="extend_extension_payment_date" class="form-label">تاريخ سند القبض <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="extend_extension_payment_date"
                                    name="extension_payment_date" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_payment_receipt" class="form-label">
                                    إرفاق إثبات الدفع (اختياري)
                                </label>
                                <input type="file" class="form-control" id="extend_extension_payment_receipt"
                                    name="extension_payment_receipt" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-12">
                                <label for="extend_extension_notes" class="form-label">ملاحظات</label>
                                <textarea class="form-control" id="extend_extension_notes" name="extension_notes" rows="3"
                                    placeholder="أي ملاحظات إضافية حول التمديد"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="sendExtensionMessageToggle" name="send_extension_message" checked>
                                    <label class="form-check-label" for="sendExtensionMessageToggle">
                                        إرسال رسالة نصية للعميل بتأكيد التمديد وتاريخ النهاية الجديد
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success" id="extendContractSubmit" form="extendContractForm">
                        <i class="fas fa-save me-2"></i>
                        حفظ التمديد
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    {{-- jQuery وDataTables محمّلان في القالب؛ إعادة تحميلهما تلغي معالج فتح نموذج التسليم. --}}

    <!-- الملفات المحلية -->
    <!-- <script src="{{ asset('admin') }}/assets/jsbundle/dataTables.bundle.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        console.log('Script loaded, jQuery available:', typeof $);
        console.log('DataTable available:', typeof $.fn.DataTable);

        $(document).ready(function() {
            @if(AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CHANGE_MARKETER))
            const contractMarketers = @json($marketers->pluck('name', 'id'));
            $(document).on('click', '.change-contract-marketer', function() {
                const button = $(this);
                const options = Object.assign({}, contractMarketers);
                delete options[String(button.attr('data-marketer-id'))];

                Swal.fire({
                    title: 'تغيير المسوق',
                    text: 'المسوق الحالي: ' + button.attr('data-marketer-name'),
                    input: 'select',
                    inputOptions: options,
                    inputPlaceholder: 'اختر المسوق الجديد',
                    showCancelButton: true,
                    confirmButtonText: 'حفظ المسوق',
                    cancelButtonText: 'إلغاء',
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),
                    allowEscapeKey: () => !Swal.isLoading(),
                    inputValidator: value => value ? undefined : 'يرجى اختيار المسوق الجديد',
                    preConfirm: function(marketerId) {
                        return $.ajax({
                            url: button.attr('data-url'),
                            type: 'POST',
                            headers: { 'Accept': 'application/json' },
                            data: { _token: @json(csrf_token()), marketer_id: marketerId }
                        }).catch(function(xhr) {
                            const response = xhr.responseJSON || {};
                            const errors = response.errors ? Object.values(response.errors).flat().join('\n') : '';
                            const message = errors || response.message || 'تعذر تغيير المسوق. يرجى التحقق من الاتصال والمحاولة مجدداً.';
                            Swal.showValidationMessage($('<div>').text(message).html());
                            return false;
                        });
                    }
                }).then(function(result) {
                    if (result.isConfirmed && result.value && result.value.success) {
                        $('#dataTableObject').DataTable().ajax.reload(null, false);
                        Swal.fire({ icon: 'success', text: result.value.message, confirmButtonText: 'حسناً' });
                    }
                });
            });
            @endif

            console.log('Document ready, initializing DataTable...');
            console.log('Table element found:', $('#dataTableObject').length);

            // التحقق من تحميل DataTables
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables library is not loaded!');
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في تحميل المكتبات',
                    text: 'لم يتم تحميل مكتبة DataTables بشكل صحيح'
                });
                return;
            }

            $.fn.dataTable.ext.errMode = 'none';

            let openRentContractActionMenu = null;

            // حفظ/استعادة حالة القائمة (الفلاتر + رقم الصفحة) عند الرجوع من صفحة العقد
            const rentListFilterSelectors = [
                '#quickSearchFilter',
                '#statusFilter',
                '#paymentStatusFilter',
                '#dateFromFilter',
                '#dateToFilter',
                '#startDateFromFilter',
                '#startDateToFilter',
                '#endDateFromFilter',
                '#endDateToFilter',
                '#clientPhoneFilter',
                '#clientNameFilter',
                '#maidPassportFilter',
                '#maidNameFilter',
                '#contractNumberFilter',
                '#marketerFilter',
                '#branchFilter',
                '#extensionFilter',
                '#expiryFilter'
            ];

            const rentListState = window.ListState
                ? window.ListState.create({
                    key: 'rent-contracts',
                    filters: rentListFilterSelectors,
                    onRestore: function(filters) {
                        // فتح لوحة الفلاتر إذا كان هناك فلتر مُستعاد
                        const hasActiveFilter = Object.keys(filters).some(function(selector) {
                            return filters[selector];
                        });

                        if (hasActiveFilter) {
                            $('#filtersCollapse').addClass('show');
                            $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                        }
                    }
                })
                : null;

            // Initialize DataTable with simplified settings
            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                stateSave: !!rentListState,
                stateDuration: 0,
                stateSaveCallback: rentListState ? rentListState.saveCallback : undefined,
                stateLoadCallback: rentListState ? rentListState.loadCallback : undefined,
                stateSaveParams: rentListState ? rentListState.saveParams : undefined,
                stateLoadParams: rentListState ? rentListState.loadParams : undefined,
                ajax: {
                    url: "{{ route('RentContract.index') }}",
                    data: function(d) {
                        d.quick_search = $('#quickSearchFilter').val();
                        d.status_filter = $('#statusFilter').val();
                        d.payment_status = $('#paymentStatusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.start_date_from = $('#startDateFromFilter').val();
                        d.start_date_to = $('#startDateToFilter').val();
                        d.end_date_from = $('#endDateFromFilter').val();
                        d.end_date_to = $('#endDateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.marketer_id = $('#marketerFilter').val();
                        d.branch_id = $('#branchFilter').length ? $('#branchFilter').val() : '';
                        d.extension_filter = $('#extensionFilter').val();
                        d.expiry_filter = $('#expiryFilter').val();
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        const responseText = xhr.responseText || '';
                        let message = 'تعذر تحميل بيانات عقود الإيجار.';

                        if (xhr.status === 401 || xhr.status === 419) {
                            message = 'انتهت الجلسة أو لا توجد صلاحية للطلب. أعد تسجيل الدخول ثم حاول مرة أخرى.';
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        console.error('RentContract DataTable Ajax error', {
                            status: xhr.status,
                            textStatus: textStatus,
                            errorThrown: errorThrown,
                            response: responseText.substring(0, 3000)
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في تحميل جدول عقود الإيجار',
                            text: message
                        });
                    }
                },
                order: [[0, 'desc']],
                columns: [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'user_id',
                        name: 'user_id'
                    },
                    {
                        data: 'maid_id',
                        name: 'maid_id'
                    },
                    {
                        data: 'contract_info',
                        name: 'contract_info'
                    },
                    {
                        data: 'marketer',
                        name: 'marketer'
                    },
                    @if ($branchesEnabled ?? false)
                        {
                            data: 'branch',
                            name: 'branch'
                        },
                    @endif {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'remaining_days',
                        name: 'remaining_days'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    "sProcessing": "جارٍ التحميل...",
                    "sLengthMenu": "أظهر _MENU_ مدخلات",
                    "sZeroRecords": "لم يعثر على أية سجلات",
                    "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                    "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                    "sInfoFiltered": "(منتقاة من مجموع _MAX_ مُدخل)",
                    "sInfoPostFix": "",
                    "sSearch": "ابحث:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "الأول",
                        "sPrevious": "السابق",
                        "sNext": "التالي",
                        "sLast": "الأخير"
                    }
                },
                preDrawCallback: function() {
                    closeRentContractActionMenu();
                },
                drawCallback: function() {
                    console.log('Table drawn successfully');
                    $('#loadingOverlay').hide();
                    initializeRentContractActionDropdowns();
                }
            });

            function setImportantStyle(element, property, value) {
                element.style.setProperty(property, value, 'important');
            }

            function positionRentContractActionMenu(menu, toggle) {
                if (!toggle) {
                    toggle = menu.data('rentContractToggle');
                }

                if (!toggle) {
                    return;
                }

                const buttonRect = toggle.getBoundingClientRect();
                const menuElement = menu instanceof jQuery ? menu[0] : menu;
                const menuRect = menuElement.getBoundingClientRect();
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const spacing = 8;
                const menuWidth = menuRect.width || 220;
                const menuHeight = menuRect.height || 260;

                let left = buttonRect.right - menuWidth;
                let top = buttonRect.bottom + spacing;

                if (top + menuHeight > viewportHeight - spacing) {
                    top = buttonRect.top - menuHeight - spacing;
                }

                left = Math.max(spacing, Math.min(left, viewportWidth - menuWidth - spacing));
                top = Math.max(spacing, Math.min(top, viewportHeight - menuHeight - spacing));

                setImportantStyle(menuElement, 'position', 'fixed');
                setImportantStyle(menuElement, 'left', `${Math.round(left)}px`);
                setImportantStyle(menuElement, 'top', `${Math.round(top)}px`);
                setImportantStyle(menuElement, 'right', 'auto');
                setImportantStyle(menuElement, 'bottom', 'auto');
                setImportantStyle(menuElement, 'transform', 'none');
                setImportantStyle(menuElement, 'z-index', '2147483647');
            }

            function closeRentContractActionMenu() {
                if (!openRentContractActionMenu) {
                    return;
                }

                const $menu = openRentContractActionMenu;
                const $parent = $menu.data('rentContractParent');
                const $toggle = $($menu.data('rentContractToggle'));

                $menu.removeClass('show rent-contract-actions-menu').hide().removeAttr('style');

                if ($parent && $parent.length) {
                    $menu.appendTo($parent);
                }

                $toggle.removeClass('show').attr('aria-expanded', 'false');
                openRentContractActionMenu = null;
            }

            function escapeRentContractHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function copyRentContractText(text, button) {
                const markCopied = function() {
                    if (!button) {
                        return;
                    }

                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check me-1"></i>تم النسخ';
                    setTimeout(function() {
                        button.innerHTML = originalText;
                    }, 1600);
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(markCopied);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                markCopied();
            }

            function showPaymentLinkResult(response) {
                const paymentUrl = response.payment_url || '';
                let html = '<div class="text-end" dir="rtl"><p class="mb-2">' + escapeRentContractHtml(response.message || '') + '</p>';

                if (paymentUrl) {
                    html += '<label class="form-label fw-bold">رابط الدفع</label>' +
                        '<div class="input-group" dir="ltr">' +
                        '<input type="text" class="form-control" id="rentPaymentLinkInput" value="' + escapeRentContractHtml(paymentUrl) + '" readonly>' +
                        '<button type="button" class="btn btn-outline-primary" id="copyRentPaymentLinkBtn">' +
                        '<i class="fas fa-copy me-1"></i>نسخ الرابط' +
                        '</button>' +
                        '</div>' +
                        '<small class="text-muted d-block mt-2" dir="rtl">يمكنك نسخ الرابط وإرساله للعميل لاحقاً.</small>';
                }

                html += '</div>';

                Swal.fire({
                    icon: response.message_sent === false ? 'warning' : 'success',
                    title: response.message_sent === false ? 'تم إنشاء الرابط ولم ترسل الرسالة' : 'تم إرسال رابط الدفع',
                    html: html,
                    confirmButtonText: 'موافق',
                    didOpen: function() {
                        const copyButton = document.getElementById('copyRentPaymentLinkBtn');
                        const input = document.getElementById('rentPaymentLinkInput');

                        if (copyButton && input) {
                            copyButton.addEventListener('click', function() {
                                copyRentContractText(input.value, copyButton);
                            });
                        }
                    }
                });
            }

            function initializeRentContractActionDropdowns() {
                $('#dataTableObject .actions-dropdown .actions-btn').each(function() {
                    $(this)
                        .removeAttr('data-bs-toggle data-bs-display data-bs-popper')
                        .attr('aria-expanded', 'false');
                });
            }

            initializeRentContractActionDropdowns();

            $(document).on('click', '#dataTableObject .actions-dropdown .actions-btn', function(event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                const toggle = this;
                const $dropdown = $(toggle).closest('.actions-dropdown');
                const $menu = $dropdown.find('.dropdown-menu').first();

                if (!$menu.length) {
                    return false;
                }

                if (openRentContractActionMenu && openRentContractActionMenu[0] === $menu[0]) {
                    closeRentContractActionMenu();
                    return false;
                }

                closeRentContractActionMenu();

                $menu
                    .data('rentContractParent', $dropdown)
                    .data('rentContractToggle', toggle)
                    .appendTo(document.body)
                    .addClass('show rent-contract-actions-menu')
                    .show();

                $(toggle).addClass('show').attr('aria-expanded', 'true');
                openRentContractActionMenu = $menu;
                positionRentContractActionMenu($menu, toggle);

                return false;
            });

            $(document).on('click', '.rent-contract-actions-menu', function(event) {
                event.stopPropagation();
            });

            $(document).on('click', '.rent-contract-actions-menu .dropdown-item', function() {
                setTimeout(closeRentContractActionMenu, 150);
            });

            $(document).on('click', function() {
                closeRentContractActionMenu();
            });

            $(window).on('scroll resize', function() {
                if (openRentContractActionMenu) {
                    positionRentContractActionMenu(openRentContractActionMenu);
                }
            });

            const filterFields = rentListFilterSelectors
                .filter((selector) => $(selector).length)
                .join(', ');

            const statisticsCards = $('.rent-stat-card[data-status-filter]');

            function syncStatisticsCardSelection() {
                const selectedStatus = String($('#statusFilter').val() || '');

                statisticsCards.each(function() {
                    const cardStatus = String($(this).data('status-filter') || '');
                    const isSelected = cardStatus === selectedStatus;
                    $(this).toggleClass('is-active', isSelected).attr('aria-pressed', isSelected ? 'true' : 'false');
                });
            }

            function syncStatusFilterUrl() {
                const url = new URL(window.location.href);
                const selectedStatus = String($('#statusFilter').val() || '');
                url.searchParams.delete('status_filter');

                if (selectedStatus) {
                    url.searchParams.set('status', selectedStatus);
                } else {
                    url.searchParams.delete('status');
                }

                const paymentStatus = $('#paymentStatusFilter').val();
                if (paymentStatus) {
                    url.searchParams.set('payment_status', paymentStatus);
                } else {
                    url.searchParams.delete('payment_status');
                }

                window.history.replaceState({}, '', url.toString());
            }

            let quickSearchTimer;

            function applyRentContractFilters() {
                clearTimeout(quickSearchTimer);
                syncStatisticsCardSelection();
                syncStatusFilterUrl();
                updateStatistics();
                dataTable.ajax.reload();
            }

            statisticsCards.on('click', function() {
                $('#statusFilter').val(String($(this).data('status-filter') || ''));
                applyRentContractFilters();
            });

            $('#quickSearchFilter').on('input', function() {
                clearTimeout(quickSearchTimer);
                quickSearchTimer = setTimeout(applyRentContractFilters, 350);
            });

            $('#clearQuickSearch').on('click', function() {
                $('#quickSearchFilter').val('').trigger('focus');
                applyRentContractFilters();
            });

            // Apply Filters
            $('#applyFilters').click(function() {
                applyRentContractFilters();
            });

            $(document).on('keydown', filterFields, function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyRentContractFilters();
                }
            });

            // Clear Filters
            $('#clearFilters').click(function() {
                $('#statusFilter, #paymentStatusFilter, #marketerFilter, #branchFilter, #extensionFilter, #expiryFilter')
                    .val('');
                $('#quickSearchFilter, #dateFromFilter, #dateToFilter, #startDateFromFilter, #startDateToFilter, #endDateFromFilter, #endDateToFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter')
                    .val('');
                applyRentContractFilters();
            });

            // Export Excel
            $('#exportExcel').click(function() {
                window.location.href = "{{ route('RentContract.export.excel') }}?" + $.param({
                    quick_search: $('#quickSearchFilter').val(),
                    status: $('#statusFilter').val(),
                    payment_status: $('#paymentStatusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    start_date_from: $('#startDateFromFilter').val(),
                    start_date_to: $('#startDateToFilter').val(),
                    end_date_from: $('#endDateFromFilter').val(),
                    end_date_to: $('#endDateToFilter').val(),
                    client_phone: $('#clientPhoneFilter').val(),
                    client_name: $('#clientNameFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_name: $('#maidNameFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                    extension_filter: $('#extensionFilter').val(),
                    expiry_filter: $('#expiryFilter').val()
                });
            });

            function formatDate(date) {
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            function formatCurrency(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function toNumber(value, fallback = 0) {
                const number = Number(value);
                return Number.isFinite(number) ? number : fallback;
            }

            function setExtendStatusBadge(remainingDays) {
                const badge = $('#extend_contract_status_badge');
                const icon = badge.find('i');
                const text = badge.find('span');

                badge.removeClass('is-ok is-warning is-danger');
                icon.removeClass('fa-circle-check fa-triangle-exclamation fa-circle-xmark');

                if (remainingDays === null || typeof remainingDays === 'undefined' || remainingDays === '') {
                    badge.addClass('is-warning');
                    icon.addClass('fa-triangle-exclamation');
                    text.text('المدة غير محددة');
                    return;
                }

                const days = parseInt(remainingDays, 10);
                if (days < 0) {
                    badge.addClass('is-danger');
                    icon.addClass('fa-circle-xmark');
                    text.text('متأخر ' + Math.abs(days) + ' يوم');
                } else if (days === 0) {
                    badge.addClass('is-warning');
                    icon.addClass('fa-triangle-exclamation');
                    text.text('ينتهي اليوم');
                } else {
                    badge.addClass('is-ok');
                    icon.addClass('fa-circle-check');
                    text.text('ضمن المدة');
                }
            }

            function renderRemainingDays(remainingDays) {
                if (remainingDays === null || typeof remainingDays === 'undefined' || remainingDays === '') {
                    return '-';
                }

                const days = parseInt(remainingDays, 10);
                if (days < 0) {
                    return 'متأخر ' + Math.abs(days) + ' يوم';
                }

                if (days === 0) {
                    return 'ينتهي اليوم';
                }

                return days + ' يوم متبقي';
            }

            function updateExtensionPreview() {
                const months = parseInt($('#extend_extension_months').val(), 10) || 0;
                const extraDays = parseInt($('#extend_extension_extra_days').val(), 10) || 0;
                const endDateStr = $('#extend_current_end_value').val();
                const contractValue = toNumber($('#extend_contract_value_value').val());
                const currentDurationDays = toNumber($('#extend_duration_days_value').val());
                const storedDailyRate = toNumber($('#extend_daily_rate_value').val());
                const dailyRate = storedDailyRate || (currentDurationDays > 0 && contractValue > 0 ? contractValue /
                    currentDurationDays : 0);
                const extensionDays = (months * 30) + extraDays;

                const resetPreview = () => {
                    $('#extend_extension_days').val('');
                    $('#extend_extension_end_date').val('');
                    $('#extend_extension_value').text('-');
                    $('#extend_extension_discount_text').text('-');
                    $('#extend_new_duration_text').text('-');
                    $('#extend_new_contract_value_text').text('-');
                    $('#extend_extension_payment_amount').val('');
                    $('#extend_net_value').val('');
                };

                if (!extensionDays || !endDateStr) {
                    resetPreview();
                    return;
                }

                const baseDate = new Date(endDateStr);
                if (Number.isNaN(baseDate.getTime())) {
                    resetPreview();
                    return;
                }

                const newEndDate = new Date(baseDate.getTime() + (extensionDays * 24 * 60 * 60 * 1000));
                const extensionValue = $('#extend_package_id').val()
                    ? Math.round(dailyRate * extensionDays * 100) / 100
                    : dailyRate * extensionDays;
                const discount = calculateExtensionDiscount(extensionValue);
                const netValue = Math.max(0, extensionValue - discount);
                if ($('#extend_package_id').val()) {
                    const salary = Math.min(netValue, Math.max(0, toNumber($('#extend_package_id').data('monthly-salary')) * extensionDays / 30));
                    const taxableGross = Math.max(0, netValue - Math.round(salary * 100) / 100);
                    const taxable = Math.round(taxableGross / 1.15 * 100) / 100;
                    const vat = Math.round((taxableGross - taxable) * 100) / 100;
                    $('#extend_package_tax_preview').removeClass('d-none').text(
                        'خدمة قبل الضريبة: ' + formatCurrency(taxable) + ' ريال — ضريبة: ' + formatCurrency(vat) +
                        ' ريال — راتب معفى: ' + formatCurrency(salary) + ' ريال');
                } else {
                    $('#extend_package_tax_preview').addClass('d-none').empty();
                }

                $('#extend_extension_days').val(extensionDays + ' يوم');
                $('#extend_extension_end_date').val(formatDate(newEndDate));
                $('#extend_extension_value').text(formatCurrency(extensionValue) + ' ريال');
                $('#extend_extension_discount_text').text(discount > 0 ? '- ' + formatCurrency(discount) + ' ريال' : '-');
                $('#extend_new_duration_text').text(extensionDays + ' يوم');
                $('#extend_new_contract_value_text').text(formatCurrency(netValue) + ' ريال');
                $('#extend_extension_payment_amount').val(netValue.toFixed(2));
                $('#extend_net_value').val(netValue.toFixed(2));
                toggleExtendReceiptRequirement();
            }

            // الخصم: نسبة من إجمالي التمديد أو مبلغ مباشر — مقيد بقيمة التمديد
            function calculateExtensionDiscount(extensionValue) {
                const type = $('#extend_extension_discount_type').val();
                const value = toNumber($('#extend_extension_discount_value').val());

                if (!type || value <= 0 || extensionValue <= 0) {
                    return 0;
                }

                const discount = type === 'percentage'
                    ? extensionValue * (Math.min(100, value) / 100)
                    : value;

                return $('#extend_package_id').val()
                    ? Math.round(Math.min(extensionValue, discount) * 100) / 100
                    : Math.min(extensionValue, discount);
            }

            function toggleExtendDiscountFields() {
                const type = $('#extend_extension_discount_type').val();

                $('.extend-discount-fields').toggleClass('d-none', !type);
                $('#extend_extension_discount_value').prop('required', Boolean(type));
                $('#extend_extension_discount_unit').text(type === 'percentage' ? '%' : 'ريال');
                $('#extend_extension_discount_value').attr('max', type === 'percentage' ? 100 : null);

                if (!type) {
                    $('#extend_extension_discount_value').val('');
                    $('#extend_extension_discount_notes').val('');
                }
            }

            function toggleExtendReceiptRequirement() {
                const paymentStatus = $('#extend_extension_payment_status').val();
                const netValue = toNumber($('#extend_net_value').val());
                // التمديد المجاني (خصم كامل) لا يحتاج سند قبض
                const receiptRequired = (paymentStatus === 'paid' || paymentStatus === 'partial') && netValue > 0;

                $('.extend-receipt-fields').toggleClass('d-none', !receiptRequired);
                $('#extend_extension_payment_type, #extend_extension_receipt_account_id, #extend_extension_payment_amount, #extend_extension_payment_date')
                    .prop('required', receiptRequired);
            }

            function resetExtendModal() {
                $('#extendContractErrors').addClass('d-none').empty();
                $('#extendContractForm')[0].reset();
                $('#extend_extension_days').val('');
                $('#extend_extension_end_date').val('');
                $('#extend_extension_value').text('-');
                $('#extend_new_duration_text').text('-');
                $('#extend_new_contract_value_text').text('-');
                $('#extend_current_end_value').val('');
                $('#extend_monthly_cost_value').val('');
                $('#extend_contract_value_value').val('');
                $('#extend_duration_days_value').val('');
                $('#extend_daily_rate_value').val('');
                $('#extend_net_value').val('');
                $('#extend_contract_number').text('-');
                $('#extend_current_start_text').text('-');
                $('#extend_current_end_text').text('-');
                $('#extend_current_duration_text').text('-');
                $('#extend_used_days_text').text('-');
                $('#extend_remaining_days_text').text('-');
                $('#extend_contract_value_text').text('-');
                $('#extend_daily_rate_text').text('-');
                $('#extend_extensions_count_text').text('-');
                $('#extend_extension_months').val('0');
                $('#extend_extension_extra_days').val('0');
                $('#extend_extension_discount_type').val('');
                $('#extend_extension_discount_value').val('');
                $('#extend_extension_discount_notes').val('');
                $('#extend_extension_discount_text').text('-');
                toggleExtendDiscountFields();
                $('#extend_extension_payment_date').val('{{ now()->format('Y-m-d') }}');
                $('.extend-receipt-fields').addClass('d-none');
                $('#extend_extension_payment_type, #extend_extension_receipt_account_id, #extend_extension_payment_amount, #extend_extension_payment_date')
                    .prop('required', false);
                setExtendStatusBadge(null);
            }

            $(document).on('click', '.open-extend-modal', function() {
                const button = $(this);
                const extendUrl = button.data('extend-url');
                const contractNumber = button.data('contract-number') || '-';
                const ingazFrom = button.data('ingaz-from') || '';
                const ingazTo = button.data('ingaz-to') || '';
                const monthlyCost = toNumber(button.data('monthly-cost'));
                const contractValue = toNumber(button.data('contract-value'));
                const durationMonths = toNumber(button.data('duration-months'));
                const durationDays = toNumber(button.data('duration-days'));
                const remainingDays = button.data('remaining-days');
                const usedDays = button.data('used-days');
                const extensionsCount = toNumber(button.data('extensions-count'));
                const dailyRate = durationDays > 0 && contractValue > 0 ? contractValue / durationDays :
                    monthlyCost / 30;

                resetExtendModal();
                $('#extend_package_id').val('').data('original-rate', dailyRate).data('monthly-salary', button.data('monthly-salary'));
                $('#extend_package_tax_preview').addClass('d-none').empty();
                $('#extend_package_id option[value!=""]').each(function () {
                    const nationality = $(this).data('nationality');
                    const allowed = !nationality || Number(nationality) === Number(button.data('nationality-id'));
                    $(this).prop('disabled', !allowed).prop('hidden', !allowed);
                });
                $('#extendContractForm').attr('action', extendUrl);
                $('#extend_contract_number').text(contractNumber);
                $('#extend_current_start_text').text(ingazFrom || 'غير محدد');
                $('#extend_current_end_text').text(ingazTo || 'غير محدد');
                const durationParts = [];
                if (durationMonths) {
                    durationParts.push(durationMonths + ' شهر');
                }
                if (durationDays) {
                    durationParts.push(durationDays + ' يوم');
                }
                $('#extend_current_duration_text').text(durationParts.length ? durationParts.join(' / ') :
                    '-');
                $('#extend_used_days_text').text(usedDays !== '' && typeof usedDays !== 'undefined' ? (
                    usedDays + ' يوم') : '-');
                $('#extend_remaining_days_text').text(renderRemainingDays(remainingDays));
                $('#extend_contract_value_text').text(formatCurrency(contractValue) + ' ريال');
                $('#extend_daily_rate_text').text(formatCurrency(dailyRate) + ' ريال');
                $('#extend_extensions_count_text').text(extensionsCount + ' مرة');
                $('#extend_current_end_value').val(ingazTo || '');
                $('#extend_monthly_cost_value').val(monthlyCost || 0);
                $('#extend_contract_value_value').val(contractValue || 0);
                $('#extend_duration_days_value').val(durationDays || 0);
                $('#extend_daily_rate_value').val(dailyRate || 0);
                setExtendStatusBadge(remainingDays);

                updateExtensionPreview();
                $('#extendContractModal').modal('show');
            });

            $('#extend_package_id').on('change', function () {
                const option = $(this).find('option:selected');
                let rate = toNumber($(this).data('original-rate'));
                if ($(this).val()) {
                    const days = Number(option.data('days'));
                    rate = toNumber(option.data('price')) / days;
                    $('#extend_extension_months').val(Math.floor(days / 30));
                    $('#extend_extension_extra_days').val(days % 30);
                }
                $('#extend_daily_rate_value').val(rate);
                updateExtensionPreview();
            });

            $('#extend_extension_months, #extend_extension_extra_days, #extend_extension_discount_value')
                .on('input change', function() {
                    updateExtensionPreview();
                });

            $('#extend_extension_discount_type').on('change', function() {
                toggleExtendDiscountFields();
                updateExtensionPreview();
            });

            $('#extend_extension_payment_status').on('change', function() {
                toggleExtendReceiptRequirement();
            });

            $('#extendContractForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#extendContractSubmit');
                const paymentStatus = $('#extend_extension_payment_status').val();
                const months = parseInt($('#extend_extension_months').val(), 10) || 0;
                const extraDays = parseInt($('#extend_extension_extra_days').val(), 10) || 0;

                $('#extendContractErrors').addClass('d-none').empty();
                submitBtn.prop('disabled', true);

                const failExtendValidation = (message) => {
                    $('#extendContractErrors').removeClass('d-none').html(message);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: message
                    });
                    submitBtn.prop('disabled', false);
                };

                if ((months * 30) + extraDays <= 0) {
                    failExtendValidation('يرجى تحديد مدة التمديد بالشهور أو الأيام.');
                    return;
                }

                const discountType = $('#extend_extension_discount_type').val();
                const discountValue = toNumber($('#extend_extension_discount_value').val());
                const grossValue = toNumber($('#extend_daily_rate_value').val()) * ((months * 30) + extraDays);

                if (discountType && discountValue <= 0) {
                    failExtendValidation('يرجى إدخال قيمة الخصم أو اختيار "بدون خصم".');
                    return;
                }

                if (discountType === 'percentage' && discountValue > 100) {
                    failExtendValidation('نسبة الخصم لا يمكن أن تتجاوز 100%.');
                    return;
                }

                if (discountType === 'amount' && grossValue > 0 && discountValue > grossValue) {
                    failExtendValidation('مبلغ الخصم لا يمكن أن يتجاوز قيمة التمديد (' +
                        formatCurrency(grossValue) + ' ريال).');
                    return;
                }

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: new FormData(form[0]),
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        $('#extendContractModal').modal('hide');
                        const successAlert = Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message || 'تم تمديد عقد التأجير بنجاح',
                            showCancelButton: !!response.receipt_bond_print_url,
                            confirmButtonText: response.receipt_bond_print_url ? 'طباعة سند القبض' : 'حسناً',
                            cancelButtonText: 'إغلاق'
                        });
                        if (response.receipt_bond_print_url) {
                            successAlert.then(function(result) {
                                if (result.isConfirmed) {
                                    window.open(response.receipt_bond_print_url, '_blank');
                                }
                            });
                        }
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء تمديد العقد';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const messages = [];
                            Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                                messages.push(xhr.responseJSON.errors[key][0]);
                            });

                            if (messages.length) {
                                $('#extendContractErrors').removeClass('d-none').html(messages
                                    .join('<br>'));
                                errorMessage = messages[0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            function updateCloseFinancialPreview() {
                const useManualAdjustment = $('#useManualClosureAdjustment').is(':checked');
                const adjustmentAmount = useManualAdjustment
                    ? parseFloat($('#manualClosureAdjustmentAmount').val() || 0)
                    : parseFloat($('#closeAdjustmentAmount').val() || 0);
                const adjustmentType = $('#closeAdjustmentType').val() || 'none';

                $('#closeAdjustmentAmountLabel').text(adjustmentType === 'company_due'
                    ? 'قيمة الإشعار المدين'
                    : (adjustmentType === 'refund' ? 'قيمة الإشعار الدائن' : 'قيمة الإشعار'));

                $('#approvedClosureNoteAmount').text(formatCurrency(adjustmentAmount) + ' ريال');
                $('#manualClosureCashNotice').toggleClass('d-none', !useManualAdjustment);
            }

            function formatCloseRemainingDaysText(contract) {
                const overdueDays = parseInt(contract.overdue_days || 0, 10);

                if (overdueDays > 0) {
                    return 'منتهي منذ ' + overdueDays + ' يوم';
                }

                return contract.remaining_days || '-';
            }

            function formatContractDuration(contract) {
                if (contract.duration_label) {
                    return contract.duration_label;
                }

                const durationDays = parseInt(contract.duration_days || 0, 10);
                const durationMonths = parseInt(contract.duration_months || 0, 10);

                if (durationDays && (!durationMonths || durationDays !== durationMonths * 30)) {
                    if (durationDays === 7) {
                        return 'أسبوع';
                    }
                    if (durationDays === 14) {
                        return 'أسبوعين';
                    }
                    if (durationDays % 7 === 0) {
                        return (durationDays / 7) + ' أسابيع';
                    }
                    return durationDays + ' يوم';
                }

                return durationMonths ? (durationMonths + ' شهر') : (durationDays ? durationDays + ' يوم' : '-');
            }

            let closeFinancialSummary = null;
            let closeSummaryRequest = null;
            let closeSummaryVersion = 0;
            let closeSummaryValid = true;

            function fillCloseFinancialFields(contract) {
                closeFinancialSummary = contract;
                $('#remainingDays').text(formatCloseRemainingDaysText(contract));
                $('#financialMonthlyCost').text(formatCurrency(contract.monthly_cost || 0) + ' ريال');
                $('#financialDurationMonths').text(formatContractDuration(contract));
                $('#financialDurationDays').text((contract.duration_days || 0) ? (contract.duration_days + ' يوم') :
                    '-');
                $('#financialContractValue').text(formatCurrency(contract.contract_value || 0) + ' ريال');
                $('#financialPaidAmount').text(formatCurrency(contract.paid_amount || 0) + ' ريال');
                $('#financialRemainingAmount')
                    .text(formatCurrency(contract.remaining_amount || 0) + ' ريال');
                $('#financialUsedDays').text(contract.used_days !== null && typeof contract.used_days !==
                    'undefined' ? (contract.used_days + ' يوم') : '-');
                $('#financialRemainingDays').text(contract.remaining_days_value !== null && typeof contract
                    .remaining_days_value !== 'undefined' ? (contract.remaining_days_value + ' يوم') : '-');
                $('#financialOverdueDays').text(contract.overdue_days !== null && typeof contract.overdue_days !==
                    'undefined' ? (contract.overdue_days + ' يوم') : '-');
                $('#financialDailyRate').text(formatCurrency(contract.daily_rate || 0) + ' ريال');
                $('#financialUsedCost').text(formatCurrency(contract.used_cost || 0) + ' ريال');
                $('#financialUsedDailyRate').text(formatCurrency(contract.used_daily_rate ?? contract.daily_rate ?? 0) + ' ريال');
                $('#financialOverdueDailyRate').text(formatCurrency(contract.overdue_daily_rate ?? contract.daily_rate ?? 0) + ' ريال');
                $('#financialOverdueCost').text(formatCurrency(contract.overdue_cost || 0) + ' ريال');
                const earlyRateApplicable = contract.is_early_closure && contract.used_days < contract.duration_days;
                const overdueRateApplicable = parseInt(contract.overdue_days || 0, 10) > 0;
                $('#earlyClosureDailyRateWrapper').toggleClass('d-none', !earlyRateApplicable);
                $('#overdueClosureDailyRateWrapper').toggleClass('d-none', !overdueRateApplicable);
                $('#earlyClosureDailyRate').prop('disabled', !earlyRateApplicable);
                $('#overdueClosureDailyRate').prop('disabled', !overdueRateApplicable);
                $('#useCustomClosureDailyRates').prop('disabled', !earlyRateApplicable && !overdueRateApplicable);
                $('#discountedClosureDailyRate').val(formatCurrency(contract.adjusted_daily_rate ?? contract.daily_rate ?? 0) + ' ريال');
                $('#financialSuggestedRefund').text(formatCurrency(contract.calculated_credit_note_value || 0) +
                ' ريال');
                $('#financialCompanyDue').text(formatCurrency(contract.calculated_debit_note_value || 0) +
                ' ريال');
                $('#financialCashToCollect').text(formatCurrency(contract.cash_to_collect_value || 0) + ' ريال');
                $('#financialCashRefund').text(formatCurrency(contract.cash_refund_value || 0) + ' ريال');
                $('#earlyClosureNotice').toggleClass('d-none', !contract.is_early_closure);
                if (contract.closure_status_text) {
                    $('#currentStatus').text((contract.status_text || '-') + ' / بعد الإنهاء: ' + contract
                        .closure_status_text);
                }
                const documentType = contract.document_type || 'none';
                $('#closeAdjustmentType').val(documentType);
                $('#closeAdjustmentTypeLabel').val(documentType === 'company_due'
                    ? 'إشعار مدين'
                    : (documentType === 'refund' ? 'إشعار دائن' : 'لا يوجد إشعار'));
                $('#closeAdjustmentAmount').val(parseFloat(contract.document_value || 0).toFixed(2));
                if (!$('#useManualClosureAdjustment').is(':checked')) {
                    $('#manualClosureAdjustmentAmount').val(parseFloat(contract.document_value || 0).toFixed(2));
                }
                updateCloseFinancialPreview();
            }

            function reloadCloseFinancialSummary() {
                if (!selectedContractId) {
                    return;
                }

                const version = ++closeSummaryVersion;
                if (closeSummaryRequest) closeSummaryRequest.abort();
                closeSummaryValid = false;
                $('#confirmCloseAndMakeAvailableBtn').prop('disabled', true);
                closeSummaryRequest = $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/details`,
                    type: 'GET',
                    data: {
                        closure_date: $('#closureDate').val(),
                        daily_discount_type: $('#useDailyClosureDiscount').is(':checked')
                            ? $('#dailyClosureDiscountType').val() : null,
                        daily_discount_value: $('#useDailyClosureDiscount').is(':checked')
                            ? $('#dailyClosureDiscountValue').val() : null,
                        use_custom_daily_rates: $('#useCustomClosureDailyRates').is(':checked') ? 1 : 0,
                        early_closure_daily_rate: $('#useCustomClosureDailyRates').is(':checked') && !$('#earlyClosureDailyRate').prop('disabled')
                            ? $('#earlyClosureDailyRate').val() : null,
                        overdue_daily_rate: $('#useCustomClosureDailyRates').is(':checked') && !$('#overdueClosureDailyRate').prop('disabled')
                            ? $('#overdueClosureDailyRate').val() : null
                    },
                    success: function(response) {
                        if (version === closeSummaryVersion && response.success) {
                            closeSummaryValid = true;
                            fillCloseFinancialFields(response.contract);
                        }
                    },
                    error: function(xhr, status) {
                        if (status === 'abort' || version !== closeSummaryVersion) return;
                        const errors = xhr.responseJSON?.errors;
                        Swal.fire({icon: 'warning', title: 'تعذر تحديث الحسبة',
                            text: errors ? Object.values(errors).flat().join('، ') : (xhr.responseJSON?.message || 'راجع الأسعار وتاريخ الإنهاء.')});
                    },
                    complete: function() {
                        if (version === closeSummaryVersion) {
                            $('#confirmCloseAndMakeAvailableBtn').prop('disabled', !closeSummaryValid);
                        }
                    }
                });
            }

            function resetCloseFinancialFields() {
                ++closeSummaryVersion;
                if (closeSummaryRequest) closeSummaryRequest.abort();
                clearTimeout(dailyDiscountReloadTimer);
                closeFinancialSummary = null;
                closeSummaryValid = true;
                $('#confirmCloseAndMakeAvailableBtn').prop('disabled', false);
                $('#useCustomClosureDailyRates').prop('checked', false).prop('disabled', false);
                $('#customClosureDailyRateFields').addClass('d-none');
                $('#earlyClosureDailyRate, #overdueClosureDailyRate, #customClosureDailyRateReason').val('');
                $('#closeAdjustmentType').val('none');
                $('#closeAdjustmentTypeLabel').val('يُحدد تلقائياً');
                $('#closeAdjustmentAmount').val('0');
                $('#useManualClosureAdjustment').prop('checked', false);
                $('#manualClosureAdjustmentFields').addClass('d-none');
                $('#manualClosureAdjustmentAmount').val('0');
                $('#manualClosureAdjustmentReason').val('');
                $('#manualClosureAdjustmentAttachment').val('');
                $('#useDailyClosureDiscount').prop('checked', false);
                $('#dailyClosureDiscountFields').addClass('d-none');
                $('#dailyClosureDiscountType').val('fixed');
                $('#dailyClosureDiscountValue').val('');
                $('#dailyClosureDiscountReason').val('');
                $('#discountedClosureDailyRate').val('');
                $('#closePaymentStatus').val('');
                $('#closePaymentReceipt').val('');
                $('#closeClientIban').val('');
                $('#closeClientIbanAttachment').val('');
                $('#sendClosureMessageToggle').prop('checked', true);
                $('#closeReturnReason').val('');
                $('#closureDate').val('').removeAttr('min');
                $('#closeClientIbanAttachmentCurrentWrapper').addClass('d-none');
                $('#closeClientIbanAttachmentCurrent').attr('href', '#');
                $('#financialDailyRate').text('-');
                $('#financialUsedCost').text('-');
                $('#financialUsedDailyRate, #financialOverdueDailyRate, #financialOverdueCost').text('-');
                $('#financialSuggestedRefund').text('-');
                $('#financialOverdueDays').text('-');
                $('#financialCompanyDue').text('-');
                $('#financialCashToCollect').text('-');
                $('#financialCashRefund').text('-');
                updateCloseFinancialPreview();
            }

            $('#closureDate').on('change', function() {
                // Eligibility may change between early and overdue closure.
                $('#useCustomClosureDailyRates').prop('checked', false);
                $('#customClosureDailyRateFields').addClass('d-none');
                $('#earlyClosureDailyRate, #overdueClosureDailyRate').val('');
                reloadCloseFinancialSummary();
            });

            $('#closeAdjustmentAmount').on('input change', function() {
                updateCloseFinancialPreview();
            });

            $('#useManualClosureAdjustment').on('change', function() {
                if (this.checked) {
                    $('#useDailyClosureDiscount').prop('checked', false);
                    $('#dailyClosureDiscountFields').addClass('d-none');
                    $('#useCustomClosureDailyRates').prop('checked', false);
                    $('#customClosureDailyRateFields').addClass('d-none');
                    reloadCloseFinancialSummary();
                }
                $('#manualClosureAdjustmentFields').toggleClass('d-none', !this.checked);
                if (this.checked && !parseFloat($('#manualClosureAdjustmentAmount').val() || 0)) {
                    $('#manualClosureAdjustmentAmount').val($('#closeAdjustmentAmount').val() || '0');
                }
                updateCloseFinancialPreview();
            });

            $('#manualClosureAdjustmentAmount').on('input change', function() {
                updateCloseFinancialPreview();
            });

            let dailyDiscountReloadTimer = null;
            function scheduleDailyDiscountPreview() {
                ++closeSummaryVersion;
                if (closeSummaryRequest) closeSummaryRequest.abort();
                closeSummaryValid = false;
                $('#confirmCloseAndMakeAvailableBtn').prop('disabled', true);
                clearTimeout(dailyDiscountReloadTimer);
                dailyDiscountReloadTimer = setTimeout(reloadCloseFinancialSummary, 250);
            }

            $('#useDailyClosureDiscount').on('change', function() {
                $('#dailyClosureDiscountFields').toggleClass('d-none', !this.checked);
                if (this.checked) {
                    $('#useManualClosureAdjustment').prop('checked', false);
                    $('#manualClosureAdjustmentFields').addClass('d-none');
                    $('#useCustomClosureDailyRates').prop('checked', false);
                    $('#customClosureDailyRateFields').addClass('d-none');
                }
                scheduleDailyDiscountPreview();
            });

            $('#dailyClosureDiscountType, #dailyClosureDiscountValue').on('input change', scheduleDailyDiscountPreview);
            $('#useCustomClosureDailyRates').on('change', function() {
                $('#customClosureDailyRateFields').toggleClass('d-none', !this.checked);
                if (this.checked) {
                    $('#useDailyClosureDiscount, #useManualClosureAdjustment').prop('checked', false);
                    $('#dailyClosureDiscountFields, #manualClosureAdjustmentFields').addClass('d-none');
                    if (!$('#earlyClosureDailyRate').prop('disabled') && $('#earlyClosureDailyRate').val() === '') {
                        $('#earlyClosureDailyRate').val(closeFinancialSummary?.daily_rate ?? 0);
                    }
                    if (!$('#overdueClosureDailyRate').prop('disabled') && $('#overdueClosureDailyRate').val() === '') {
                        $('#overdueClosureDailyRate').val(closeFinancialSummary?.daily_rate ?? 0);
                    }
                }
                scheduleDailyDiscountPreview();
            });
            $('#earlyClosureDailyRate, #overdueClosureDailyRate').on('input change', scheduleDailyDiscountPreview);

            function addTransferAdjustmentRow(item = null) {
                const template = document.querySelector('#transfer-adjustment-row-template');
                if (!template) {
                    return;
                }

                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('tr');
                if (item) {
                    row.querySelector('.transfer-adjustment-type').value = item.type || 'income';
                    row.querySelector('.transfer-adjustment-label').value = item.label || '';
                    row.querySelector('.transfer-adjustment-amount').value = item.amount ?? '0.00';
                }
                document.querySelector('#transfer-adjustment-rows').appendChild(fragment);
            }

            function resetTransferAdjustments() {
                $('#transfer-adjustment-rows').empty();
            }

            function toggleTransferReceiptRequirement() {
                const paymentStatus = $('#transferPaymentStatus').val();
                const receiptRequired = paymentStatus === 'paid' || paymentStatus === 'partial';

                $('#transferPaymentReceipt').prop('required', receiptRequired);
                $('#transferReceiptAccountWrapper').toggleClass('d-none', !receiptRequired);
                $('#transferReceiptAccount').prop('required', receiptRequired);
                $('#transferReceiptRequiredMark, #transferReceiptRequiredNote').toggleClass('d-none', !
                    receiptRequired);
            }

            function resetTransferPaymentFields() {
                $('#transferPaymentType').val('');
                $('#transferPaymentStatus').val('');
                $('#transferPaymentReceipt').val('');
                $('#transferReceiptAccount').val('');
                $('#transferAttachments').val('');
                $('#sendTransferClosureMessageToggle').prop('checked', true);
                toggleTransferReceiptRequirement();
            }

            function getTransferAdjustments() {
                const items = [];

                $('#transfer-adjustment-rows tr').each(function() {
                    const row = $(this);
                    const label = (row.find('.transfer-adjustment-label').val() || '').trim();
                    const amount = parseFloat(row.find('.transfer-adjustment-amount').val() || 0);
                    const type = row.find('.transfer-adjustment-type').val() || 'income';

                    if (!label) {
                        return;
                    }

                    items.push({
                        type: type,
                        label: label,
                        amount: Number.isFinite(amount) ? amount : 0
                    });
                });

                return items;
            }

            $(document).on('click', '#add-transfer-adjustment', function() {
                addTransferAdjustmentRow();
            });

            $('#transferPaymentStatus').on('change', function() {
                toggleTransferReceiptRequirement();
            });

            $(document).on('click', '.remove-transfer-adjustment', function() {
                $(this).closest('tr').remove();
            });



            // Delete Contract Handler
            $(document).on('click', '.delete-contract', function() {
                let contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع عن هذا الإجراء.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = $('<form>', {
                            'method': 'POST',
                            'action': "{{ route('RentContract.destroy', ':id') }}"
                                .replace(':id', contractId)
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            // Update Statistics
            let statisticsRequest;
            function updateStatistics() {
                if (statisticsRequest) statisticsRequest.abort();
                statisticsRequest = $.ajax({
                    url: "{{ route('RentContract.statistics') }}",
                    method: 'GET',
                    data: {
                        quick_search: $('#quickSearchFilter').val(),
                        status_filter: $('#statusFilter').val(),
                        payment_status: $('#paymentStatusFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val(),
                        start_date_from: $('#startDateFromFilter').val(),
                        start_date_to: $('#startDateToFilter').val(),
                        end_date_from: $('#endDateFromFilter').val(),
                        end_date_to: $('#endDateToFilter').val(),
                        client_phone: $('#clientPhoneFilter').val(),
                        client_name: $('#clientNameFilter').val(),
                        maid_passport: $('#maidPassportFilter').val(),
                        maid_name: $('#maidNameFilter').val(),
                        contract_number: $('#contractNumberFilter').val(),
                        marketer_id: $('#marketerFilter').val(),
                        branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                        extension_filter: $('#extensionFilter').val(),
                        expiry_filter: $('#expiryFilter').val()
                    },
                    success: function(data) {
                        $('#totalContracts').text(data.total || 0);
                        $('#activeContracts').text(data.active || 0);
                        $('#completedContracts').text(data.completed || 0);
                        $('#cancelledContracts').text(data.cancelled || 0);
                        $('#availableContracts').text(data.available || 0);
                        $('#transferredContracts').text(data.transferred || 0);
                    },
                    error: function() {
                        console.log('Error loading statistics');
                    }
                });
            }

            // Toggle filters collapse icon
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });
            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            // Load initial statistics
            syncStatisticsCardSelection();
            updateStatistics();

            // إضافة متغير لحفظ معرف العقد المحدد
            let selectedContractId = null;

            // التعامل مع زر إنهاء العقد
            $(document).on('click', '.complete-contract', function() {
                selectedContractId = $(this).data('id');

                // جلب بيانات العقد
                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/details`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const contract = response.contract;
                            resetCloseFinancialFields();
                            resetTransferAdjustments();
                            resetTransferPaymentFields();
                            $('#transferSponsorshipForm').hide();
                            $('#closeContractAttachmentsRow').hide();
                            $('#closeFinancialSettlementRow').hide();
                            $('#closeContractOptionsRow').show();
                            $('#sponsorshipTransferForm')[0].reset();
                            resetTransferPaymentFields();

                            // تعبئة بيانات العقد في الـ modal
                            $('#contractId').text(contract.id);
                            $('#clientName').text(contract.client_name || '-');
                            $('#maidName').text(contract.maid_name || '-');
                            $('#currentStatus').text(contract.status_text || '-');
                            $('#startDate').text(contract.start_date || '-');
                            $('#endDate').text(contract.end_date || '-');
                            $('#closureDate')
                                .attr('min', contract.start_date || null)
                                .val(contract.closure_date || new Date().toISOString().slice(0,
                                    10));
                            $('#remainingDays').text(contract.remaining_days || '-');
                            $('#contractValue').text((contract.amount || 0) + ' ريال');
                            $('#financialMonthlyCost').text(formatCurrency(contract
                                .monthly_cost || 0) + ' ريال');
                            $('#financialDurationMonths').text((contract.duration_months || 0) ?
                                (contract.duration_months + ' شهر') : '-');
                            $('#financialDurationDays').text((contract.duration_days || 0) ? (
                                contract.duration_days + ' يوم') : '-');
                            $('#financialContractValue').text(formatCurrency(contract
                                .contract_value || 0) + ' ريال');
                            $('#financialPaidAmount').text(formatCurrency(contract
                                .paid_amount || 0) + ' ريال');
                            $('#financialRemainingAmount')
                                .text(formatCurrency(contract.remaining_amount || 0) + ' ريال');
                            $('#financialUsedDays').text(contract.used_days !== null &&
                                typeof contract.used_days !== 'undefined' ? (contract
                                    .used_days + ' يوم') : '-');
                            $('#financialRemainingDays').text(contract.remaining_days_value !==
                                null && typeof contract.remaining_days_value !==
                                'undefined' ? (contract.remaining_days_value + ' يوم') : '-'
                                );
                            $('#financialDailyRate').text(formatCurrency(contract.daily_rate ||
                                0) + ' ريال');
                            $('#financialUsedCost').text(formatCurrency(contract.used_cost ||
                                0) + ' ريال');
                            $('#financialSuggestedRefund').text(formatCurrency(contract
                                .calculated_credit_note_value || 0) + ' ريال');
                            $('#closeAdjustmentAmount').val(parseFloat(contract.calculated_credit_note_value ||
                                0).toFixed(2));
                            fillCloseFinancialFields(contract);
                            $('#closeReturnReason').val(contract.closure_return_reason || '');
                            $('#closePaymentStatus').val(contract.payment_status || '');
                            $('#closeClientIban').val(contract.closure_client_iban || '');
                            renderContractAttachments(contract.attachments || []);

                            if (contract.closure_client_iban_attachment_url) {
                                $('#closeClientIbanAttachmentCurrent').attr('href', contract
                                    .closure_client_iban_attachment_url);
                                $('#closeClientIbanAttachmentCurrentWrapper').removeClass(
                                    'd-none');
                            }

                            updateCloseFinancialPreview();

                            // تعبئة اسم العميل في نموذج نقل الخدمات
                            $('#transferClientName').text(contract.client_name || '-');

                            // إظهار الـ modal
                            $('#closeContractModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message ||
                                    'حدث خطأ أثناء جلب بيانات العقد'
                            });
                        }

                        dataTable.ajax.reload();
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء جلب بيانات العقد'
                        });
                    }
                });
            });

            function executeCloseAndMakeAvailable() {
                if (!closeSummaryValid) {
                    Swal.fire({icon: 'warning', title: 'الحسبة غير جاهزة', text: 'انتظر تحديث الحسبة أو صحح بيانات التسعير أولًا.'});
                    return;
                }
                if ($('#useCustomClosureDailyRates').is(':checked')) {
                    const applicableInputs = $('#earlyClosureDailyRate, #overdueClosureDailyRate').filter(':enabled');
                    const hasValidRate = applicableInputs.toArray().some(input => input.value !== '' && Number.isFinite(Number(input.value)) && Number(input.value) >= 0);
                    if (!hasValidRate || !($('#customClosureDailyRateReason').val() || '').trim()) {
                        Swal.fire({icon: 'warning', title: 'بيانات التسعير غير مكتملة', text: 'أدخل تكلفة اليوم وسبب اعتماد السعر.'});
                        return;
                    }
                }
                if ($('#useDailyClosureDiscount').is(':checked')) {
                    const discountValue = parseFloat($('#dailyClosureDiscountValue').val() || 0);
                    const discountReason = ($('#dailyClosureDiscountReason').val() || '').trim();

                    if (discountValue <= 0 || !discountReason) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'بيانات الخصم غير مكتملة',
                            text: 'اختر نوع الخصم وأدخل قيمته وسبب اعتماده.'
                        });
                        return;
                    }
                }

                if ($('#useManualClosureAdjustment').is(':checked')) {
                    const manualAmount = parseFloat($('#manualClosureAdjustmentAmount').val() || 0);
                    const manualReason = ($('#manualClosureAdjustmentReason').val() || '').trim();
                    const manualAttachment = $('#manualClosureAdjustmentAttachment')[0];

                    if (manualAmount <= 0 || !manualReason || !manualAttachment?.files?.length) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'بيانات التسوية غير مكتملة',
                            text: 'أدخل القيمة النهائية وسبب فرق التسوية وأرفق مستند الاعتماد.'
                        });
                        return;
                    }
                }

                Swal.fire({
                    title: 'تأكيد الإغلاق',
                    text: 'هل أنت متأكد من إغلاق العقد وإرجاع العاملة متاحة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إغلاق العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        if (!closeSummaryValid) {
                            Swal.fire({icon: 'warning', title: 'تغيرت الحسبة', text: 'راجع الحسبة المحدثة ثم أكد الإنهاء مجددًا.'});
                            return;
                        }
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('adjustment_type', $('#closeAdjustmentType').val());
                        formData.append('adjustment_amount', $('#closeAdjustmentAmount').val() || 0);
                        const useManualAdjustment = $('#useManualClosureAdjustment').is(':checked');
                        formData.append('use_manual_adjustment', useManualAdjustment ? 1 : 0);
                        if (useManualAdjustment) {
                            formData.append('manual_adjustment_amount', $('#manualClosureAdjustmentAmount').val() || 0);
                            formData.append('manual_adjustment_reason', $('#manualClosureAdjustmentReason').val());
                            const manualAttachment = $('#manualClosureAdjustmentAttachment')[0];
                            if (manualAttachment && manualAttachment.files && manualAttachment.files[0]) {
                                formData.append('manual_adjustment_attachment', manualAttachment.files[0]);
                            }
                        }
                        const useDailyDiscount = $('#useDailyClosureDiscount').is(':checked');
                        formData.append('use_daily_discount', useDailyDiscount ? 1 : 0);
                        if (useDailyDiscount) {
                            formData.append('daily_discount_type', $('#dailyClosureDiscountType').val());
                            formData.append('daily_discount_value', $('#dailyClosureDiscountValue').val());
                            formData.append('daily_discount_reason', $('#dailyClosureDiscountReason').val());
                        }
                        const useCustomRates = $('#useCustomClosureDailyRates').is(':checked');
                        formData.append('use_custom_daily_rates', useCustomRates ? 1 : 0);
                        if (useCustomRates) {
                            if (!$('#earlyClosureDailyRate').prop('disabled') && $('#earlyClosureDailyRate').val() !== '') {
                                formData.append('early_closure_daily_rate', $('#earlyClosureDailyRate').val());
                            }
                            if (!$('#overdueClosureDailyRate').prop('disabled') && $('#overdueClosureDailyRate').val() !== '') {
                                formData.append('overdue_daily_rate', $('#overdueClosureDailyRate').val());
                            }
                            formData.append('custom_daily_rate_reason', $('#customClosureDailyRateReason').val());
                        }
                        formData.append('closure_date', $('#closureDate').val());
                        formData.append('closure_return_reason', $('#closeReturnReason').val());
                        formData.append('close_payment_status', $('#closePaymentStatus').val());
                        formData.append('close_client_iban', $('#closeClientIban').val());
                        formData.append('send_closure_message', $('#sendClosureMessageToggle').is(
                            ':checked') ? 1 : 0);

                        const closePaymentReceipt = $('#closePaymentReceipt')[0];
                        if (closePaymentReceipt && closePaymentReceipt.files && closePaymentReceipt.files[
                            0]) {
                            formData.append('close_payment_receipt', closePaymentReceipt.files[0]);
                        }

                        const closeClientIbanAttachment = $('#closeClientIbanAttachment')[0];
                        if (closeClientIbanAttachment && closeClientIbanAttachment.files &&
                            closeClientIbanAttachment.files[0]) {
                            formData.append('close_client_iban_attachment', closeClientIbanAttachment.files[
                                0]);
                        }

                        $.ajax({
                            url: `/admin/RentContract/${selectedContractId}/close-and-make-available`,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message ||
                                            'تم إغلاق العقد وإرجاع العاملة متاحة بنجاح'
                                    }).then(() => {
                                        $('#closeContractModal').modal('hide');
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message ||
                                            'حدث خطأ أثناء إغلاق العقد'
                                    });
                                }

                                dataTable.ajax.reload();
                            },
                            error: function(xhr) {
                                let message = 'حدث خطأ أثناء إغلاق العقد';
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                                    if (firstKey) {
                                        message = xhr.responseJSON.errors[firstKey][0];
                                    }
                                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: message
                                });
                            }
                        });
                    }
                });
            }

            // إظهار بيان الإغلاق بعد اختيار إرجاع العاملة متاحة
            $('#closeAndMakeAvailableBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('#closeContractOptionsRow').hide();
                $('#closeContractAttachmentsRow').show();
                $('#closeFinancialSettlementRow').show();
            });

            $('#confirmCloseAndMakeAvailableBtn').click(function() {
                executeCloseAndMakeAvailable();
            });

            $('#backToCloseOptionsBtn').click(function() {
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#transferSponsorshipForm').hide();
                $('#closeContractOptionsRow').show();
            });

            function renderContractAttachments(attachments) {
                const container = $('#contractAttachmentsList');
                container.empty();

                if (!attachments.length) {
                    container.append($('<span class="text-muted">لا توجد مرفقات مسجلة على هذا العقد.</span>'));
                    return;
                }

                attachments.forEach(function(attachment) {
                    const link = $(
                        '<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"></a>');
                    link.attr('href', attachment.url);
                    link.append($('<i class="fas fa-paperclip me-1"></i>'));
                    link.append(document.createTextNode(attachment.label || 'مرفق'));
                    container.append(link);
                });
            }

            // إظهار نموذج نقل الخدمات
            $('#transferSponsorshipBtn').click(function() {
                $('#transferSponsorshipForm').show();
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#closeContractOptionsRow').hide();
                $('#closeContractModal .modal-body').animate({
                    scrollTop: $('#transferSponsorshipForm').position().top
                }, 200);
                if (!$('#transfer-adjustment-rows tr').length) {
                    addTransferAdjustmentRow({
                        type: 'income',
                        label: 'نقل خدمات',
                        amount: $('#totalAmount').val() || '0.00'
                    });
                }
            });

            // إلغاء نقل الخدمات
            $('#cancelTransferBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#closeContractOptionsRow').show();
                $('#sponsorshipTransferForm')[0].reset();
                resetTransferAdjustments();
                resetTransferPaymentFields();
            });

            // حساب المبلغ المتبقي تلقائياً
            $('#totalAmount, #paidAmount').on('input', function() {
                const total = parseFloat($('#totalAmount').val()) || 0;
                const paid = parseFloat($('#paidAmount').val()) || 0;
                const remaining = total - paid;
                $('#remainingAmount').val(remaining.toFixed(2));

                const firstRow = $('#transfer-adjustment-rows tr:first');
                const firstRowAmount = firstRow.find('.transfer-adjustment-amount');
                const firstRowLabel = (firstRow.find('.transfer-adjustment-label').val() || '').trim();
                const currentRowAmount = parseFloat(firstRowAmount.val() || 0);
                if (firstRowAmount.length && firstRowLabel === 'نقل خدمات' && currentRowAmount <= 0) {
                    firstRowAmount.val(total.toFixed(2));
                }
            });

            // تأكيد نقل الخدمات
            $('#sponsorshipTransferForm').submit(function(e) {
                e.preventDefault();

                const paymentStatus = $('#transferPaymentStatus').val();
                const receiptInput = $('#transferPaymentReceipt')[0];
                const hasReceipt = !!(receiptInput && receiptInput.files && receiptInput.files.length > 0);

                if ((paymentStatus === 'paid' || paymentStatus === 'partial') && !hasReceipt) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع "تم الدفع" أو "مدفوع جزئياً".'
                    });
                    return;
                }

                const formData = new FormData(this);
                formData.append('contract_id', selectedContractId);
                formData.append('total_amount', $('#totalAmount').val());
                formData.append('paid_amount', $('#paidAmount').val());
                formData.append('remaining_amount', $('#remainingAmount').val());
                formData.append('notes', $('#transferNotes').val());
                formData.append('closure_date', $('#closureDate').val());
                formData.append('transfer_finance_items_present', '1');
                formData.append('send_closure_message', $('#sendTransferClosureMessageToggle').is(
                    ':checked') ? 1 : 0);
                formData.append('_token', '{{ csrf_token() }}');

                getTransferAdjustments().forEach(function(item, index) {
                    formData.append(`transfer_finance_items[${index}][type]`, item.type);
                    formData.append(`transfer_finance_items[${index}][label]`, item.label);
                    formData.append(`transfer_finance_items[${index}][amount]`, item.amount);
                });

                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/transfer-sponsorship`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message ||
                                    'تم إغلاق العقد ونقل الخدمات بنجاح'
                            }).then(() => {
                                $('#closeContractModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء نقل الخدمات'
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'حدث خطأ أثناء نقل الخدمات';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            if (firstKey) {
                                message = xhr.responseJSON.errors[firstKey][0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: message
                        });
                    }
                });
            });

            $(document).on('click', '.sign-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد توقيع العقد',
                    text: 'هل تم توقيع العقد من العميل فعلاً؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تم التوقيع',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'sign-contract',
                            'تم تأكيد توقيع العقد بنجاح');
                    }
                });
            });

            $(document).on('click', '.deliver-maid', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد تسليم العاملة',
                    text: 'هل تم تسليم العاملة للعميل فعلاً؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تم التسليم',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'deliver-maid',
                            'تم تأكيد تسليم العاملة بنجاح');
                    }
                });
            });

            $(document).on('click', '.activate-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد تفعيل العقد',
                    text: 'هل تريد تفعيل العقد وبدء الخدمة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، فعّل العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'activate-contract',
                            'تم تفعيل العقد بنجاح');
                    }
                });
            });

            @include('admin.ingaz.RentContract._freeze-dialogs')

            $(document).on('click', '.freeze-contract', async function() {
                const data = await rentalFreezeDialog();
                if (data) executeWorkflowAction($(this).data('id'), 'freeze', 'تم تجميد عقد الإيجار بنجاح', data);
            });

            $(document).on('click', '.edit-freeze-date', function() {
                const contractId = $(this).data('id');
                const currentFrozenAt = $(this).data('frozen-at') || '';

                Swal.fire({
                    title: 'تغيير تاريخ التجميد',
                    input: 'datetime-local',
                    inputLabel: 'تاريخ التجميد',
                    inputValue: currentFrozenAt,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'حفظ التاريخ',
                    cancelButtonText: 'إلغاء',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'يرجى تحديد تاريخ التجميد';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'update-freeze-date',
                            'تم تحديث تاريخ التجميد بنجاح', {
                                frozen_at: result.value
                            });
                    }
                });
            });

            $(document).on('click', '.resume-contract', async function() {
                const contractId = $(this).data('id');
                const url = @json(route('RentContract.resumeOptions', ['id' => '__ID__'])).replace('__ID__', contractId);
                const data = await rentalResumeDialog(url);
                if (data) executeWorkflowAction(contractId, 'resume', 'تم استئناف عقد الإيجار بنجاح', data);
            });

            $(document).on('click', '.send-payment-link', function() {
                const url = $(this).data('url');

                Swal.fire({
                    title: 'إرسال رابط الدفع',
                    text: 'هل تريد إرسال رابط الدفع للعميل الآن؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، أرسل الرابط',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'جارٍ إرسال رابط الدفع...',
                                allowOutsideClick: false,
                                didOpen: function() {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            dataTable.ajax.reload(null, false);
                            showPaymentLinkResult(response);
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};

                            if (response.payment_url) {
                                showPaymentLinkResult({
                                    message_sent: false,
                                    message: response.message || 'تعذر إرسال الرسالة للعميل.',
                                    payment_url: response.payment_url
                                });
                                return;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر إرسال رابط الدفع',
                                text: response.message || 'حدث خطأ أثناء إرسال رابط الدفع.'
                            });
                        }
                    });
                });
            });

            $(document).on('click', '.send-expiry-reminder', function() {
                const url = $(this).data('url');

                Swal.fire({
                    title: 'إرسال تنبيه انتهاء العقد',
                    text: 'هل تريد إرسال رابط اختيار التمديد أو التسليم للعميل الآن؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، أرسل التنبيه',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'جارٍ إرسال التنبيه...',
                                allowOutsideClick: false,
                                didOpen: function() {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            dataTable.ajax.reload(null, false);
                            Swal.fire({
                                icon: response.message_sent === false ? 'warning' : 'success',
                                title: response.message_sent === false ? 'تم إنشاء التنبيه' : 'تم إرسال التنبيه',
                                text: response.message || 'تم تنفيذ العملية.',
                                confirmButtonText: 'موافق'
                            });
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر إرسال التنبيه',
                                text: response.message || 'حدث خطأ أثناء إرسال تنبيه انتهاء العقد.'
                            });
                        }
                    });
                });
            });

            function executeWorkflowAction(contractId, action, successMessage, extraData = {}) {
                $.ajax({
                    url: `{{ url('/admin') }}/RentContract/${contractId}/${action}`,
                    type: 'POST',
                    data: Object.assign({
                        _token: '{{ csrf_token() }}'
                    }, extraData),
                    success: function(response) {
                        dataTable.ajax.reload();

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح!',
                                text: response.message || successMessage,
                                confirmButtonText: 'موافق'
                            })
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ!',
                                text: response.message || 'حدث خطأ أثناء تنفيذ العملية'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء تنفيذ العملية';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    </script>
@endsection
