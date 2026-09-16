@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 16px 18px;
            border-radius: 14px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            background: #fff;
            text-align: start;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
            min-height: 86px;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 0, 0, 0.12);
        }

        .stat-card.active {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2);
            border-color: rgba(13, 110, 253, 0.6);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            flex-shrink: 0;
            background: #334155;
        }

        .stat-body {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            min-width: 0;
        }

        .stat-title {
            font-size: 0.95rem;
            color: #4b5563;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-card-current {
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
        }

        .stat-card-all {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .stat-card-refunded {
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
        }

        .stat-card-pending {
            background: linear-gradient(135deg, #f0fdfa, #ecfeff);
        }

        .stat-card-new {
            background: linear-gradient(135deg, #eff6ff, #e0f2fe);
        }

        .stat-card-msaned {
            background: linear-gradient(135deg, #eff6ff, #e0e7ff);
        }

        .stat-card-tafwed {
            background: linear-gradient(135deg, #e0f2fe, #e0f7ff);
        }

        .stat-card-taffez {
            background: linear-gradient(135deg, #ecfeff, #ecfdf5);
        }

        .stat-card-tazker {
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
        }

        .stat-card-completed {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }

        .stat-card-shelter {
            background: linear-gradient(135deg, #eef2ff, #e0f2fe);
        }

        .stat-card-arrival {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        }

        .stat-card-warranty-active {
            background: linear-gradient(135deg, #e0f2fe, #dbeafe);
        }

        .stat-card-warranty-expired {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
        }

        .stat-card-warranty-na {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        }

        .stat-card-back {
            background: linear-gradient(135deg, #fef2f2, #ffe4e6);
        }

        .stat-card-current .stat-icon {
            background: #64748b;
        }

        .stat-card-pending .stat-icon {
            background: #0f766e;
        }

        .stat-card-new .stat-icon {
            background: #2563eb;
        }

        .stat-card-msaned .stat-icon {
            background: #1d4ed8;
        }

        .stat-card-tafwed .stat-icon {
            background: #0ea5e9;
        }

        .stat-card-taffez .stat-icon {
            background: #14b8a6;
        }

        .stat-card-tazker .stat-icon {
            background: #f97316;
        }

        .stat-card-completed .stat-icon {
            background: #16a34a;
        }

        .stat-card-shelter .stat-icon {
            background: #4f46e5;
        }

        .stat-card-arrival .stat-icon {
            background: #22c55e;
        }

        .stat-card-warranty-active .stat-icon {
            background: #0ea5e9;
        }

        .stat-card-warranty-expired .stat-icon {
            background: #ef4444;
        }

        .stat-card-warranty-na .stat-icon {
            background: #6b7280;
        }

        .stat-card-back .stat-icon {
            background: #dc2626;
        }

        .stat-card-all .stat-icon {
            background: #334155;
        }

        .stat-card-refunded .stat-icon {
            background: #c2410c;
        }

        .stats-overview .stat-card {
            min-height: 104px;
            border-width: 0 4px 0 0;
        }

        .stats-overview .stat-card-all {
            border-right-color: #334155;
        }

        .stats-overview .stat-card-current {
            border-right-color: #64748b;
        }

        .stats-overview .stat-card-completed {
            border-right-color: #16a34a;
        }

        .stats-overview .stat-card-back {
            border-right-color: #dc2626;
        }

        .stats-group {
            height: 100%;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
        }

        .stats-group-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            color: #1f2937;
            font-weight: 700;
        }

        .stats-group-header .stats-group-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #e0e7ff;
            color: #4338ca;
        }

        .stats-group .stat-card {
            background-color: #fff;
            min-height: 82px;
            padding: 13px 14px;
        }

        .stats-group .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 17px;
        }

        .stats-help {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-size: 0.82rem;
        }

        .contract-stats-header {
            gap: 12px;
            flex-wrap: wrap;
        }

        .contract-stats-heading {
            min-width: 0;
        }

        .stats-collapse-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-width: 132px;
            white-space: nowrap;
        }

        #contractStatsToggleIcon {
            transition: transform 0.2s ease;
        }

        .stats-collapse-toggle[aria-expanded="false"] #contractStatsToggleIcon {
            transform: rotate(180deg);
        }

        .quick-followup-dropdown .dropdown-menu {
            width: min(330px, calc(100vw - 32px));
            padding: 8px;
            border: 1px solid #e2e8f0;
        }

        .quick-followup-item {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr) auto;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border-radius: 9px;
            text-align: start;
        }

        .quick-followup-item i {
            color: #64748b;
            text-align: center;
        }

        .quick-followup-item .quick-followup-label {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .quick-followup-item small {
            color: #64748b;
            font-size: 0.72rem;
        }

        .quick-followup-item .quick-filter-count {
            min-width: 29px;
            padding: 3px 7px;
            border-radius: 999px;
            color: #1d4ed8;
            background: #dbeafe;
            font-size: 0.78rem;
            font-weight: 800;
            text-align: center;
        }

        .quick-followup-item.active,
        .quick-followup-item:active {
            color: #1e3a8a;
            background: #eff6ff;
        }

        .quick-followup-item.active small {
            color: #475569;
        }

        .application-duration-cell {
            min-width: 185px;
            color: #475569;
        }

        .application-duration-main {
            display: flex;
            align-items: baseline;
            gap: 7px;
            margin-bottom: 6px;
        }

        .application-duration-main i {
            color: #64748b;
        }

        .application-duration-value {
            color: #1e293b;
            font-size: 1.08rem;
            font-weight: 800;
        }

        .duration-warning .application-duration-value {
            color: #b45309;
        }

        .duration-critical .application-duration-value {
            color: #b91c1c;
        }

        .duration-completed .application-duration-value {
            color: #047857;
        }

        .application-duration-range {
            display: grid;
            gap: 3px;
            color: #64748b;
            font-size: 0.74rem;
        }

        .application-duration-range span,
        .application-duration-msaned,
        .arrival-info-row {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .application-duration-note {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            color: #047857;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .application-duration-msaned {
            margin-top: 5px;
            color: #64748b;
            font-size: 0.72rem;
        }

        .arrival-info-cell {
            min-width: 185px;
            color: #475569;
        }

        .arrival-info-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
            color: #047857;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .arrival-info-date {
            margin-bottom: 6px;
            color: #334155;
            font-size: 0.75rem;
        }

        .arrival-info-date strong {
            display: block;
            margin-top: 2px;
            color: #065f46;
            font-size: 0.98rem;
        }

        .arrival-info-row {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.72rem;
        }

        .arrival-info-row strong {
            color: #334155;
        }

        .arrival-date-missing {
            color: #b45309;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .operations-column-heading {
            min-width: 185px;
            color: #1e293b !important;
        }

        .operations-column-heading i {
            margin-inline-end: 6px;
            color: #2563eb;
        }

        .operations-column-heading-arrival i {
            color: #059669;
        }

        @media (max-width: 767.98px) {
            .stats-group {
                padding: 14px;
            }

            .stat-card {
                padding: 13px;
            }

            .stat-value {
                font-size: 1.3rem;
            }
        }

        .bulk-update-card {
            border: 1px solid rgba(13, 110, 253, 0.18);
            background: linear-gradient(135deg, #f8fbff, #f3f8ff);
        }

        .bulk-stage-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0 8px 8px 0;
        }

        .contracts-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 1.5rem;
        }

        .contracts-header-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            max-width: 760px;
        }

        .contracts-header-actions .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 38px;
            white-space: nowrap;
        }

        .contracts-header-actions .btn i {
            margin: 0 !important;
        }

        @media (max-width: 767.98px) {
            .contracts-page-header {
                flex-direction: column;
            }

            .contracts-header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .contracts-header-actions .btn {
                flex: 1 1 calc(50% - 8px);
                justify-content: center;
            }
        }

        #dataTableObject tbody tr.table-primary > td {
            background-color: rgba(13, 110, 253, 0.08) !important;
        }

        .actions-dropdown .dropdown-menu {
            min-width: 250px;
        }

        .actions-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            font-size: 0.9rem;
        }

        .actions-dropdown .dropdown-item i {
            width: 18px;
            text-align: center;
            color: #64748b;
            flex-shrink: 0;
        }

        .actions-dropdown .dropdown-header {
            font-weight: 700;
            color: #334155;
        }

        .actions-dropdown .dropdown-item.action-refund {
            color: #0d6efd;
            font-weight: 700;
            background: rgba(13, 110, 253, 0.08);
        }

        .actions-dropdown .dropdown-item.action-refund i {
            color: #0d6efd;
        }

        .actions-dropdown .attachment-count-badge {
            margin-inline-start: auto;
        }

        .contract-notes-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .contract-notes-indicator:hover {
            color: #7c2d12;
            background: #ffedd5;
            text-decoration: none;
        }

        .contract-arrival-note {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .contract-arrival-note--expected {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .contract-arrival-note--arrived {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .dropdown-menu.dropdown-menu-end.recruitment-actions-floating-menu {
            position: fixed !important;
            top: var(--recruitment-actions-top) !important;
            left: var(--recruitment-actions-left) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 2147483647 !important;
            right: auto !important;
            transform: none !important;
            min-width: 250px;
            max-height: min(460px, calc(100vh - 24px));
            overflow-y: auto;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.All Recruitment contracts') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
        @php
            $requestedStatus = request()->get('status');
            $showBackStatsInitially = !$requestedStatus || $requestedStatus === 'back';
            $showCurrentStatsInitially = !$requestedStatus || in_array($requestedStatus, [
                'current',
                'pending_maid_selection',
                'new',
                'msaned',
                'tafwed',
                'taffez',
                'tazker',
                'sari',
            ]);
            $showCompletedStatsInitially = !$requestedStatus || in_array($requestedStatus, ['completed_stage', 'completed', 'refunded', 'arrival']);
            $contractStageLabels = \App\Support\RecruitmentContractStageLabels::labels();
            $contractStageLabel = fn($status) => $contractStageLabels[$status][app()->getLocale() === 'en' ? 'en' : 'ar'] ?? $status;
        @endphp

        <!-- Header Section -->
        <div class="contracts-page-header">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-contract text-primary me-2"></i>
                {{ $shelterContractsMode ? 'عقود الإيواء' : helperTrans('ingaz.All Recruitment contracts') }}
            </h3>
            <div class="contracts-header-actions">
                <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-outline-dark">
                    <i class="fas fa-list"></i>
                    كل العقود
                </a>
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-layer-group"></i>
                    العقود السارية
                </a>
                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW_SHELTER))
                    <a href="{{ route('RecruitmentContract.index', ['shelter_contracts' => 1]) }}"
                        class="btn {{ $shelterContractsMode ? 'btn-dark' : 'btn-outline-dark' }}">
                        <i class="fas fa-house-user"></i>
                        عقود الإيواء
                    </a>
                @endif
                <a href="{{ route('RecruitmentContract.externalOfficeDispatch.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-paper-plane"></i>
                    إرسالات المكتب الخارجي
                </a>
                <a href="{{ route('musaned-transfers.create') }}" class="btn btn-outline-info">
                    <i class="fas fa-money-bill-transfer"></i>
                    تحويل مساند
                </a>
                @if (AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_CREATE, \App\NewPermKeys::REC_CONTRACTS_MANAGE]))
                    <a href="{{ route('RecruitmentContract.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        {{ helperTrans('ingaz.add_recruitment_contract') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- احصائيات العقود --}}
        <div class="row mb-3 {{ $shelterContractsMode ? 'd-none' : '' }}" id="contractStatsSection">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center contract-stats-header">
                        <div class="contract-stats-heading">
                            <h6 class="mb-1">
                                <i class="fas fa-chart-bar me-2"></i>
                                إحصائيات مراحل العقود
                            </h6>
                            <small class="text-muted">الأرقام تتبع فلاتر البحث وتعرض توزيع النتائج على المراحل</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary stats-collapse-toggle"
                            id="contractStatsToggle" data-bs-toggle="collapse" data-bs-target="#contractStatsCollapse"
                            aria-expanded="true" aria-controls="contractStatsCollapse">
                            <span id="contractStatsToggleLabel">طي الإحصائيات</span>
                            <i class="fas fa-chevron-up" id="contractStatsToggleIcon"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="contractStatsCollapse">
                    <div class="card-body">
                        <div class="stats-help mb-3">
                            <i class="fas fa-circle-info"></i>
                            اضغط على أي بطاقة لعرض العقود التابعة لها مباشرة
                        </div>

                        <div class="row g-3 stats-overview mb-4">
                            <div class="col-12 col-sm-6 col-xl-3">
                                <button type="button" class="stat-card stat-filter stat-card-all" data-status="">
                                    <div class="stat-icon"><i class="fas fa-list-check"></i></div>
                                    <div class="stat-body">
                                        <div class="stat-title">جميع العقود والطلبات</div>
                                        <div class="stat-value" data-stat-key="total">0</div>
                                    </div>
                                </button>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <button type="button" class="stat-card stat-filter stat-card-current" data-status="current">
                                    <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                                    <div class="stat-body">
                                        <div class="stat-title">العقود السارية</div>
                                        <div class="stat-value" data-stat-key="current_total">0</div>
                                    </div>
                                </button>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <button type="button" class="stat-card stat-filter stat-card-completed" data-status="completed_stage">
                                    <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                                    <div class="stat-body">
                                        <div class="stat-title">العقود المكتملة</div>
                                        <div class="stat-value" data-stat-key="completed_total">0</div>
                                    </div>
                                </button>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <button type="button" class="stat-card stat-filter stat-card-back" data-status="back">
                                    <div class="stat-icon"><i class="fas fa-arrow-rotate-left"></i></div>
                                    <div class="stat-body">
                                        <div class="stat-title">العقود المرتجعة</div>
                                        <div class="stat-value" data-stat-key="stage_back">0</div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12" id="currentStatsSection" @if (!$showCurrentStatsInitially) style="display: none;" @endif>
                                <div class="stats-group">
                                    <div class="stats-group-header">
                                        <span class="stats-group-icon"><i class="fas fa-route"></i></span>
                                        <span>مراحل تنفيذ العقود السارية</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-pending" data-status="pending_maid_selection">
                                                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('pending_maid_selection') }}</div><div class="stat-value" data-stat-key="stage_pending_maid_selection">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-new" data-status="new">
                                                <div class="stat-icon"><i class="fas fa-file-circle-plus"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('new') }}</div><div class="stat-value" data-stat-key="stage_new">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-msaned" data-status="msaned">
                                                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('msaned') }}</div><div class="stat-value" data-stat-key="stage_msaned">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-tafwed" data-status="tafwed">
                                                <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('tafwed') }}</div><div class="stat-value" data-stat-key="stage_tafwed">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-taffez" data-status="taffez">
                                                <div class="stat-icon"><i class="fas fa-passport"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('taffez') }}</div><div class="stat-value" data-stat-key="stage_taffez">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                            <button type="button" class="stat-card stat-filter stat-card-tazker" data-status="tazker">
                                                <div class="stat-icon"><i class="fas fa-plane-departure"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('tazker') }}</div><div class="stat-value" data-stat-key="stage_tazker">0</div></div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-7" id="completedStatsSection" @if (!$showCompletedStatsInitially) style="display: none;" @endif>
                                <div class="stats-group">
                                    <div class="stats-group-header">
                                        <span class="stats-group-icon"><i class="fas fa-flag-checkered"></i></span>
                                        <span>الوصول والنتائج النهائية</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-arrival" data-status="arrival">
                                                <div class="stat-icon"><i class="fas fa-plane-arrival"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('arrival') }}</div><div class="stat-value" data-stat-key="stage_arrival">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-completed" data-status="completed">
                                                <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('completed') }}</div><div class="stat-value" data-stat-key="stage_completed">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-refunded" data-status="refunded">
                                                <div class="stat-icon"><i class="fas fa-money-bill-transfer"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('refunded') }}</div><div class="stat-value" data-stat-key="stage_refunded">0</div></div>
                                            </button>
                                        </div>
                                        {{-- عقود الإيواء لها شاشة مستقلة ولا تُعرض ضمن عقود الاستقدام.
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-shelter" data-status="shelter">
                                                <div class="stat-icon"><i class="fas fa-house-user"></i></div>
                                                <div class="stat-body"><div class="stat-title">{{ $contractStageLabel('shelter') }}</div><div class="stat-value" data-stat-key="stage_shelter">0</div></div>
                                            </button>
                                        </div>
                                        --}}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5" id="warrantyStatsSection" @if (!$showCompletedStatsInitially) style="display: none;" @endif>
                                <div class="stats-group">
                                    <div class="stats-group-header">
                                        <span class="stats-group-icon"><i class="fas fa-shield-halved"></i></span>
                                        <span>حالة الضمان</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <button type="button" class="stat-card stat-filter stat-card-warranty-active" data-status="arrival" data-warranty="active">
                                                <div class="stat-icon"><i class="fas fa-shield"></i></div>
                                                <div class="stat-body"><div class="stat-title">داخل الضمان</div><div class="stat-value" data-stat-key="warranty_active">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-warranty-expired" data-status="completed" data-warranty="expired">
                                                <div class="stat-icon"><i class="fas fa-shield-circle-xmark"></i></div>
                                                <div class="stat-body"><div class="stat-title">منتهي الضمان</div><div class="stat-value" data-stat-key="warranty_expired">0</div></div>
                                            </button>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <button type="button" class="stat-card stat-filter stat-card-warranty-na" data-status="completed" data-warranty="not_applicable">
                                                <div class="stat-icon"><i class="fas fa-shield-slash"></i></div>
                                                <div class="stat-body"><div class="stat-title">بدون ضمان</div><div class="stat-value" data-stat-key="warranty_not_applicable">0</div></div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-none" id="backStatsSection"></div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلاتر البحث
                    </h6>
                    <div class="dropdown quick-followup-dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-route me-1"></i>
                            متابعة التقديم والوصول
                        </button>
                        <div class="dropdown-menu shadow-sm">
                            <button type="button" class="dropdown-item quick-followup-item stat-filter"
                                data-status="delayed_arrival">
                                <i class="fas fa-hourglass-end"></i>
                                <span class="quick-followup-label">
                                    <strong>تجاوزت 45 يومًا</strong>
                                    <small>بدون الوصول إلى مرحلة التذاكر</small>
                                </span>
                                <span class="quick-filter-count" data-stat-key="delayed_arrival">0</span>
                            </button>
                            <button type="button" class="dropdown-item quick-followup-item stat-filter"
                                data-status="arrival_today">
                                <i class="fas fa-plane-arrival"></i>
                                <span class="quick-followup-label">
                                    <strong>وصول اليوم</strong>
                                    <small>الوصول المجدول بتاريخ اليوم</small>
                                </span>
                                <span class="quick-filter-count" data-stat-key="arrival_today">0</span>
                            </button>
                            <button type="button" class="dropdown-item quick-followup-item stat-filter"
                                data-status="arrival">
                                <i class="fas fa-flag-checkered"></i>
                                <span class="quick-followup-label">
                                    <strong>مرحلة الوصول</strong>
                                    <small>العقود الموجودة في مرحلة الوصول</small>
                                </span>
                                <span class="quick-filter-count" data-stat-key="stage_arrival">0</span>
                            </button>
                        </div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="card-body collapse" id="filtersCollapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="globalSearchFilter" class="form-label">بحث شامل:</label>
                            <input type="text" id="globalSearchFilter" class="form-control"
                                placeholder="هوية العميل / الاسم / الجوال / رقم التأشيرة / رقم مساند / رقم العقد">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3 {{ $shelterContractsMode ? 'd-none' : '' }}">
                            <label for="statusFilter" class="form-label">حالة العقد:</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="current">{{ helperTrans('ingaz.contracts_are_valid') }}</option>
                                <option value="arrival_today" hidden>وصول اليوم</option>
                                <option value="delayed_arrival" hidden>مدة التقديم تجاوزت 45 يومًا بدون تذاكر</option>
                                <option value="expired_over_2y">أقدم من عامين</option>

                                <option value="new">{{ $contractStageLabel('new') }}</option>
                                <option value="msaned">{{ $contractStageLabel('msaned') }}</option>
                                <option value="tafwed">{{ $contractStageLabel('tafwed') }}</option>
                                <option value="taffez">{{ $contractStageLabel('taffez') }}</option>
                                <option value="tazker">{{ $contractStageLabel('tazker') }}</option>
                                <option value="completed_stage">المكتملة (إجمالي)</option>
                                <option value="arrival">{{ $contractStageLabel('arrival') }}</option>
                                <option value="sari">{{ $contractStageLabel('sari') }}</option>
                                <option value="completed">{{ $contractStageLabel('completed') }}</option>
                                <option value="refunded">{{ $contractStageLabel('refunded') }}</option>
                                <option value="back">{{ $contractStageLabel('back') }}</option>
                                {{-- <option value="shelter">{{ $contractStageLabel('shelter') }}</option> --}}
                                <option value="pending_maid_selection">{{ $contractStageLabel('pending_maid_selection') }}</option>
                                <option value="accreditation_contracts">{{ $contractStageLabel('accreditation_contracts') }}</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="dateFromFilter" class="form-label">تاريخ من:</label>
                            <input type="date" id="dateFromFilter" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="dateToFilter" class="form-label">تاريخ إلى:</label>
                            <input type="date" id="dateToFilter" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="clientPhoneFilter" class="form-label">رقم جوال العميل:</label>
                            <input type="text" id="clientPhoneFilter" class="form-control"
                                placeholder="ابحث برقم الجوال (5********)">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                            <input type="text" id="clientNameFilter" class="form-control"
                                placeholder="ابحث باسم العميل">
                        </div>
                        <div class="col-md-3">
                            <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                            <input type="text" id="maidPassportFilter" class="form-control"
                                placeholder="ابحث برقم الجواز">
                        </div>
                        <div class="col-md-3">
                            <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                            <input type="text" id="maidNameFilter" class="form-control"
                                placeholder="ابحث باسم العاملة">
                        </div>
                        <div class="col-md-3">
                            <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                            <input type="text" id="contractNumberFilter" class="form-control"
                                placeholder="ابحث برقم العقد">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="nationalityFilter" class="form-label">جنسية العاملة:</label>
                            <select id="nationalityFilter" class="form-select">
                                <option value="">جميع الجنسيات</option>

                                @if (isset($filterOptions) && isset($filterOptions['nationalities']))
                                    @foreach ($filterOptions['nationalities'] as $nationality)
                                        <option value="{{ $nationality->sub_title }}">{{ $nationality->sub_title }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="religionFilter" class="form-label">ديانة العاملة:</label>
                            <select id="religionFilter" class="form-select">
                                <option value="">جميع الديانات</option>

                                @if (isset($filterOptions) && isset($filterOptions['religions']))
                                    @foreach ($filterOptions['religions'] as $religion)
                                        <option value="{{ $religion->title }}">{{ $religion->title }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="officeFilter" class="form-label"> المكتب الخارجي:</label>
                            <select id="officeFilter" class="form-select">
                                <option value="">جميع المكاتب الخارجية</option>

                                @if (isset($filterOptions) && isset($filterOptions['offices']))
                                    @foreach ($filterOptions['offices'] as $office)
                                        <option value="{{ $office->id }}" {{ (string) request('office_id') === (string) $office->id ? 'selected' : '' }}>{{ $office->title }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="requestTypeFilter" class="form-label">{{ helperTrans('ingaz.request_type') }}:</label>
                            <select id="requestTypeFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all') }}</option>
                                @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3" style="display: none;">
                            <label for="warrantyStatusFilter" class="form-label">حالة الضمان:</label>
                            <select id="warrantyStatusFilter" class="form-select">
                                <option value="">جميع حالات الضمان</option>

                                <option value="active">نشط (تحت الضمان)</option>
                                <option value="expired">منتهي (خارج الضمان)</option>
                                <option value="not_applicable">غير قابل للتطبيق</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="marketerFilter" class="form-label">المسوق:</label>
                            <select id="marketerFilter" class="form-select">
                                <option value="">جميع المسوقين</option>

                                @if (isset($filterOptions) && isset($filterOptions['marketers']))
                                    @foreach ($filterOptions['marketers'] as $marketer)
                                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-3'])

                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="cityFilter" class="form-label">مدينة العميل:</label>
                            <input type="text" id="cityFilter" class="form-control" placeholder="ابحث بالمدينة">
                        </div>
                        <div class="col-md-3">
                            <label for="invoicePresenceFilter" class="form-label">الفاتورة:</label>
                            <select id="invoicePresenceFilter" class="form-select">
                                <option value="">الكل</option>
                                <option value="1">لها فاتورة</option>
                                <option value="0">بدون فاتورة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="musanedTransferPresenceFilter" class="form-label">تحويل مساند:</label>
                            <select id="musanedTransferPresenceFilter" class="form-select">
                                <option value="">الكل</option>
                                <option value="1">تم تسجيل التحويل</option>
                                <option value="0">لم يسجل التحويل</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="externalDispatchPresenceFilter" class="form-label">الإرسالية:</label>
                            <select id="externalDispatchPresenceFilter" class="form-select">
                                <option value="">الكل</option>
                                <option value="1">لها إرسالية</option>
                                <option value="0">بدون إرسالية</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>

                                <button type="button" id="clearFilters" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> مسح
                                </button>
                                <button type="button" id="btnSubmit" class="btn btn-primary ms-2">
                                    <i class="fas fa-search me-1"></i>بحث
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3 {{ $shelterContractsMode ? 'd-none' : '' }}">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-file-export me-2"></i>
                                    تصدير البيانات
                                </h5>
                            </div>
                            <div>
                                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT))
                                    <a href="{{ route('RecruitmentContract.export.pdf', request()->query()) }}"
                                        data-export-url="{{ route('RecruitmentContract.export.pdf') }}"
                                        class="btn btn-danger me-2 recruitment-contract-export" target="_blank">
                                        <i class="fa fa-file-pdf me-1"></i> {{ helperTrans('ingaz.export_pdf') }}
                                    </a>
                                    <a href="{{ route('RecruitmentContract.export.excel', request()->query()) }}"
                                        data-export-url="{{ route('RecruitmentContract.export.excel') }}"
                                        class="btn btn-success recruitment-contract-export">
                                        <i class="fa fa-file-excel me-1"></i> {{ helperTrans('ingaz.export_excel') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bulk-update-card shadow-sm mb-3 d-none" id="bulkUpdateCard">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary" id="bulkSelectedCount">0</span>
                            <h6 class="mb-0">التحديث الجماعي للعقود</h6>
                        </div>
                        <div class="text-muted" id="bulkSelectionSummary">
                            اختر العقود القابلة للتحديث من الجدول لتفعيل التحديث الجماعي.
                        </div>
                        <div class="mt-2" id="bulkStageSummary"></div>
                    </div>
                    <div class="col-md-3 col-lg-2" id="bulkStatusDateWrapper">
                        <label for="bulkStatusDate" class="form-label">تاريخ التحديث</label>
                        <input type="date" id="bulkStatusDate" class="form-control"
                            value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4 col-lg-2" id="bulkSmsWrapper">
                        <div class="form-check mt-md-4 pt-md-2">
                            <input class="form-check-input" type="checkbox" id="bulkSendSmsNotification" checked>
                            <label class="form-check-label" for="bulkSendSmsNotification">
                                إشعار العميل
                            </label>
                        </div>
                        <small class="text-muted">يتم إرسال رسالة نصية لكل عقد يتم تحديثه</small>
                    </div>
                    <div class="col-lg-3 text-lg-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="clearBulkSelectionBtn">
                            <i class="fas fa-times me-1"></i>إلغاء التحديد
                        </button>
                        <button type="button" class="btn btn-primary" id="bulkUpdateStatusBtn" disabled>
                            <i class="fas fa-layer-group me-1"></i>تحديث المحدد
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <div class="card shadow">
                <div class="card-body">
                    <!-- Modern Loading Overlay -->
                    <div id="loadingOverlay"
                        style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(74, 144, 226, 0.1), rgba(80, 200, 120, 0.1)); backdrop-filter: blur(5px); z-index: 1000; display: flex; justify-content: center; align-items: center; border-radius: 0.375rem;">
                        <div class="text-center">
                            <!-- Clean Modern Spinner -->
                            <div class="clean-loader mb-3">
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                            </div>
                            <div class="loading-text">
                                <i class="fas fa-database me-2"></i>
                                جاري تحميل بيانات العقود...
                            </div>
                            <div class="loading-subtitle">يرجى الانتظار</div>
                        </div>
                    </div>

                    <table id="dataTableObject" class="table table-hover align-middle mb-0 table-style"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 48px;">
                                    <div class="form-check d-inline-flex mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAllContracts"
                                            title="تحديد جميع العقود القابلة للتحديث في الصفحة الحالية">
                                    </div>
                                </th>
                                <th>رقم العقد</th>
                                <th>معلومات العميل</th>
                                <th>العاملة</th>
                                <th>معلومات العقد</th>
                                <th class="operations-column-heading">
                                    <i class="fas fa-hourglass-half"></i>مدة التقديم
                                </th>
                                <th class="operations-column-heading operations-column-heading-arrival">
                                    <i class="fas fa-plane-arrival"></i>الوصول للمملكة
                                    <small class="d-block mt-1 text-muted fw-normal">المتوقع من الرحلة أو الفعلي بعد الوصول</small>
                                </th>
                                <th>{{ helperTrans('ingaz.recruitment_office') }}</th>
                                <th>المسوق</th>
                                @if($branchesEnabled ?? false)
                                    <th>{{ __('admin.branches') }}</th>
                                @endif
                                <th>الحالة</th>
                                <th>تحديث الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="completeStageStepModal" tabindex="-1"
            aria-labelledby="completeStageStepModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="completeStageStepModalLabel">
                            <i class="fas fa-list-check text-success me-1"></i>
                            تأكيد المرحلة الفرعية
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="stageStepUpdateUrl">
                        <div class="alert alert-light border mb-3">
                            <div class="text-muted small mb-1">المرحلة الأم</div>
                            <strong id="stageStepParentName">—</strong>
                            <div class="text-muted small mt-3 mb-1">الخطوة المطلوب تأكيدها</div>
                            <strong class="text-success" id="stageStepName">—</strong>
                        </div>
                        <div class="mb-0">
                            <label for="stageStepNotes" class="form-label">ملاحظة عن التنفيذ (اختياري)</label>
                            <textarea id="stageStepNotes" class="form-control" rows="3" maxlength="2000"
                                placeholder="يمكن إضافة تفاصيل تنفيذ الخطوة"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-success" id="confirmStageStepBtn">
                            <i class="fas fa-check me-1"></i> تأكيد الإنجاز
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog"
            aria-labelledby="updateStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStatusModalLabel">تحديث حالة العقد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="rowId" name="rowId">
                        <input type="hidden" id="status" name="status">
                        <input type="hidden" id="nextStatus" name="nextStatus">

                        <div class="mb-3">
                            <label for="currentStatus" class="form-label">الحالة الحالية:</label>
                            <input type="text" id="currentStatus" class="form-control" readonly>
                        </div>

                        <div class="mb-3" id="nextStatusDisplayWrapper">
                            <label for="nextStatusDisplay" class="form-label">المرحلة التالية:</label>
                            <input type="text" id="nextStatusDisplay" class="form-control" readonly>
                        </div>

                        <div class="mb-3 d-none" id="manualNextStatusWrapper">
                            <label for="manualNextStatus" class="form-label">المرحلة التالية:</label>
                            <select id="manualNextStatus" class="form-select">
                                <option value="">اختر المرحلة التالية</option>
                            </select>
                            <small class="text-muted">يرجى اختيار المرحلة التالية للعقد.</small>
                        </div>
                        <div class="mb-3">
                            <label for="statusDate" class="form-label">تاريخ التحديث:</label>
                            <input type="date" id="statusDate" required name="statusDate" class="form-control"
                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sendSmsNotification"
                                    name="sendSmsNotification" checked>
                                <label class="form-check-label" for="sendSmsNotification">
                                    <i class="fas fa-sms me-1"></i>
                                    إرسال رسالة نصية للعميل بتحديث الحالة
                                </label>
                            </div>
                            <small class="text-muted">سيتم إرسال رسالة نصية للعميل تحتوي على الحالة الجديدة للعقد</small>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-success" id="saveStatusBtn">
                            <i class="fas fa-save me-1"></i>تحديث الحالة
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal تأكيد الإرجاع -->
        <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmBackModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmBackModalLabel">تأكيد إرجاع العقد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            هل أنت متأكد من إرجاع هذا العقد؟ هذا الإجراء لا يمكن التراجع عنه.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-danger" id="confirmBackBtn">
                            <i class="fas fa-undo me-1"></i>تأكيد الإرجاع
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- موديل إلغاء العقد -->
        <div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelContractModalLabel">
                            <i class="fas fa-times-circle text-danger"></i>
                            إلغاء العقد
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- معلومات العقد -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> تحذير:</h6>
                            <p>سيتم إلغاء العقد رقم <strong id="contractNumber"></strong></p>
                            <p>العميل: <strong id="clientName"></strong></p>
                            <p>العاملة: <strong id="maidName"></strong></p>
                        </div>

                        <form id="cancelContractForm">
                            @csrf
                            <input type="hidden" id="contractId" name="contract_id">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="cancel_reason" class="form-label">
                                            <i class="fas fa-comment"></i>
                                            سبب الإلغاء
                                        </label>
                                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3"
                                            placeholder="اكتب سبب إلغاء العقد..." required></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            حالة العاملة بعد الإلغاء
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="maid_status_after_cancel" id="status_available" value="available"
                                                required>
                                            <label class="form-check-label" for="status_available">
                                                <i class="fas fa-check text-success"></i>
                                                متاحة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="maid_status_after_cancel" id="status_unavailable" value="backout"
                                                required>
                                            <label class="form-check-label" for="status_unavailable">
                                                <i class="fas fa-ban text-danger"></i>
                                                باك اوت
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="button" class="btn btn-danger" onclick="submitCancelContract()">
                            <i class="fas fa-trash"></i>
                            تأكيد الإلغاء
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="refundContractModal" tabindex="-1" aria-labelledby="refundContractModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="refundContractModalLabel">
                            <i class="fas fa-rotate-left text-primary"></i>
                            استرداد العقد
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <p class="mb-1">العقد رقم <strong id="refundContractNumber"></strong></p>
                            <p class="mb-1">العميل: <strong id="refundClientName"></strong></p>
                            <p class="mb-0">قيمة العقد: <strong id="refundContractAmount"></strong></p>
                        </div>

                        <div class="alert alert-warning d-none" id="refundBeforeTransferNote">
                            لم يتم تسجيل تحويل مساند لهذا العقد، لذلك سيتم الاسترداد بدون خصم على أي طرف وبدون قيد خصم مالي.
                        </div>

                        <form id="refundContractForm">
                            @csrf
                            <input type="hidden" id="refundContractId" name="contract_id">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="refund_date" class="form-label">تاريخ الاسترداد</label>
                                        <input type="date" class="form-control" id="refund_date" name="refund_date"
                                            value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6" id="refundCancelledByWrapper">
                                    <div class="form-group mb-3">
                                        <label class="form-label">الطرف المتسبب في الإلغاء</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="cancelled_by"
                                                id="refund_by_client" value="client">
                                            <label class="form-check-label" for="refund_by_client">العميل</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="cancelled_by"
                                                id="refund_by_office" value="office">
                                            <label class="form-check-label" for="refund_by_office">المكتب</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="cancelled_by"
                                                id="refund_by_external_office" value="external_office">
                                            <label class="form-check-label" for="refund_by_external_office">الوكيل/المكتب الخارجي</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3" id="refundPenaltyFields">
                                <div class="col-md-6">
                                    <label for="refund_penalty_percent" class="form-label">نسبة الخصم (%)</label>
                                    <input type="number" class="form-control" id="refund_penalty_percent" name="penalty_percent" min="0" max="100" step="0.0001" placeholder="تلقائي">
                                </div>
                                <div class="col-md-6">
                                    <label for="refund_penalty_amount" class="form-label">مبلغ الخصم</label>
                                    <input type="number" class="form-control" id="refund_penalty_amount" name="penalty_amount" min="0" step="0.01" placeholder="تلقائي">
                                </div>
                                <div class="col-12 form-text">يمكن تعديل النسبة أو المبلغ؛ يتم تحديث الآخر وصافي المسترد تلقائيًا. امسح القيمة للعودة للحساب التلقائي.</div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group mb-3">
                                        <label for="refund_reason" class="form-label">سبب الاسترداد</label>
                                        <textarea class="form-control" id="refund_reason" name="reason" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="mt-3" id="refundPreviewWrapper" style="display: none;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0 fw-bold">
                                    <i class="fas fa-calculator text-primary me-1"></i>
                                    تفاصيل الاسترداد والقيود المتوقعة
                                </h6>
                                <span class="badge bg-light text-dark" id="refundPreviewParty"></span>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 bg-light">
                                        <small class="text-muted d-block">قيمة العقد</small>
                                        <strong id="refundPreviewContractAmount">0.00</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 bg-light">
                                        <small class="text-muted d-block">نسبة الخصم</small>
                                        <strong id="refundPreviewPenaltyPercent">0%</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 bg-light">
                                        <small class="text-muted d-block">قيمة الخصم</small>
                                        <strong id="refundPreviewPenaltyAmount">0.00</strong>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 bg-success-subtle">
                                        <small class="text-muted d-block">المدفوع للعميل</small>
                                        <strong class="text-success" id="refundPreviewRefundAmount">0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="border rounded p-2 bg-info-subtle">
                                        <small class="text-muted d-block">إشعارات دائنة سابقة</small>
                                        <strong id="refundPreviewPreviousCredit">0.00</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2 bg-danger-subtle">
                                        <small class="text-muted d-block">الإشعار الذي سينشأ الآن</small>
                                        <strong id="refundPreviewRemainingCredit">0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning py-2 d-none" id="refundPreviewWarnings"></div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النوع</th>
                                            <th>الحساب</th>
                                            <th>مدين</th>
                                            <th>دائن</th>
                                            <th>البيان</th>
                                        </tr>
                                    </thead>
                                    <tbody id="refundPreviewLines">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">لا توجد بيانات معاينة</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2">الإجمالي</th>
                                            <th id="refundPreviewTotalDebtor">0.00</th>
                                            <th id="refundPreviewTotalCreditor">0.00</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mt-3" id="refundPreviewPlaceholder">
                            اختر تاريخ الاسترداد والطرف المتسبب لعرض المبلغ المدفوع للعميل والقيود المتوقعة قبل التسجيل.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" id="submitRefundContractBtn">
                            <i class="fas fa-save me-1"></i>
                            تسجيل الاسترداد
                        </button>
                    </div>
                </div>
            </div>
        </div>


        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
            <!-- دايلوج إضافة ملاحظة -->
            <div class="modal fade" id="addContractNoteModal" tabindex="-1" aria-labelledby="addContractNoteModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" id="addContractNoteForm">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addContractNoteModalLabel">
                                    <i class="fas fa-sticky-note"></i>
                                    إضافة ملاحظة للعقد
                                    <span id="contractNoteContractId"></span>
                                    <span class="badge bg-info ms-2" id="contractNotesCountBadge">0</span>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="contract_note" class="form-label">الملاحظة</label>
                                    <textarea class="form-control" id="contract_note" name="note" rows="4" maxlength="1000" required>{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    إلغاء
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    حفظ الملاحظة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @else
        @include('components.no_permissions')
    @endif

@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>

    <script>
        $(document).ready(function() {
            const shelterContractsMode = @json((bool) $shelterContractsMode);
            const statsCollapsePreferenceKey = 'recruitmentContractStatsCollapsed';
            const statsCollapseElement = document.getElementById('contractStatsCollapse');
            const statsToggleButton = document.getElementById('contractStatsToggle');
            const statsToggleLabel = document.getElementById('contractStatsToggleLabel');

            function updateStatsCollapseButton(isExpanded) {
                if (!statsToggleButton || !statsToggleLabel) {
                    return;
                }

                statsToggleButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                statsToggleLabel.textContent = isExpanded ? 'طي الإحصائيات' : 'عرض الإحصائيات';
            }

            if (statsCollapseElement && statsToggleButton) {
                let shouldStartCollapsed = false;
                try {
                    shouldStartCollapsed = localStorage.getItem(statsCollapsePreferenceKey) === '1';
                } catch (error) {
                    shouldStartCollapsed = false;
                }

                if (shouldStartCollapsed) {
                    statsCollapseElement.classList.remove('show');
                }
                updateStatsCollapseButton(!shouldStartCollapsed);

                statsCollapseElement.addEventListener('shown.bs.collapse', function() {
                    updateStatsCollapseButton(true);
                    try {
                        localStorage.setItem(statsCollapsePreferenceKey, '0');
                    } catch (error) {}
                });

                statsCollapseElement.addEventListener('hidden.bs.collapse', function() {
                    updateStatsCollapseButton(false);
                    try {
                        localStorage.setItem(statsCollapsePreferenceKey, '1');
                    } catch (error) {}
                });
            }

            // دالة لقراءة الـ status من الـ URL
            function getStatusFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('status');
            }

            // دالة لقراءة warranty_status من الـ URL
            function getWarrantyStatusFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('warranty_status');
            }

            // دالة لقراءة marketer_id من الـ URL
            function getMarketerFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('marketer_id');
            }

            function getDateFilterFromUrl(key) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(key);
            }

            function shouldExcludeBackFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('exclude_back') === '1';
            }

            function toggleWarrantyFilter() {
                const statusValue = $('#statusFilter').val();
                const shouldShow = statusValue === 'arrival' || statusValue === 'completed' || statusValue === 'refunded' || statusValue === 'completed_stage';
                const wrapper = $('#warrantyStatusFilter').closest('.col-md-3');

                if (shouldShow) {
                    wrapper.show();
                    $('#warrantyStatusFilter').show();
                } else {
                    wrapper.hide();
                    $('#warrantyStatusFilter').hide();
                    $('#warrantyStatusFilter').val('');
                }
            }

            function toggleStatsSections() {
                const statusValue = $('#statusFilter').val();
                const currentStatuses = ['current', 'pending_maid_selection', 'new', 'msaned', 'tafwed', 'taffez',
                    'tazker', 'sari', 'accreditation_contracts'
                ];
            const completedStatuses = ['completed_stage', 'completed', 'refunded', 'arrival'];

                if (!statusValue) {
                    $('#currentStatsSection').show();
                    $('#completedStatsSection, #warrantyStatsSection').show();
                    $('#backStatsSection').show();
                    return;
                }

                if (statusValue === 'back') {
                    $('#currentStatsSection').hide();
                    $('#completedStatsSection, #warrantyStatsSection').hide();
                    $('#backStatsSection').show();
                    return;
                }

                if (currentStatuses.includes(statusValue)) {
                    $('#currentStatsSection').show();
                    $('#completedStatsSection, #warrantyStatsSection').hide();
                    $('#backStatsSection').hide();
                    return;
                }

                if (completedStatuses.includes(statusValue)) {
                    $('#currentStatsSection').hide();
                    $('#completedStatsSection, #warrantyStatsSection').show();
                    $('#backStatsSection').hide();
                    return;
                }

                $('#currentStatsSection').show();
                $('#completedStatsSection, #warrantyStatsSection').show();
                $('#backStatsSection').hide();
            }

            function syncStatSelection() {
                $('.stat-filter').removeClass('active');

                const statusValue = $('#statusFilter').val();
                const warrantyValue = $('#warrantyStatusFilter').val();

                if (warrantyValue) {
                    const warrantyBtn = $(`.stat-card.stat-filter[data-warranty="${warrantyValue}"]`).first();
                    if (warrantyBtn.length) {
                        warrantyBtn.addClass('active');
                    }
                    return;
                }

                if (statusValue) {
                    const statusCard = $(`.stat-filter[data-status="${statusValue}"]:not([data-warranty])`)
                        .first();
                    if (statusCard.length) {
                        statusCard.addClass('active');
                    }
                    return;
                }

                $('.stat-card.stat-filter[data-status=""]').first().addClass('active');
            }

            // تطبيق الـ status من الـ URL على الفلتر أولاً
            const urlStatus = getStatusFromUrl();
            const urlWarrantyStatus = getWarrantyStatusFromUrl();
            const urlMarketer = getMarketerFromUrl();
            const urlDateFrom = getDateFilterFromUrl('date_from');
            const urlDateTo = getDateFilterFromUrl('date_to');
            const urlExcludeBack = shouldExcludeBackFromUrl();
            const normalizeCompletedTotalUrl = urlStatus === 'arrival' && urlWarrantyStatus === 'expired,not_applicable';
            const effectiveUrlStatus = normalizeCompletedTotalUrl ? 'completed_stage' : urlStatus;
            const effectiveUrlWarrantyStatus = normalizeCompletedTotalUrl ? '' : urlWarrantyStatus;

            if (effectiveUrlStatus) {
                $('#statusFilter').val(effectiveUrlStatus);
                console.log('Status from URL applied to filter:', effectiveUrlStatus);
                // إزالة الفلتر من localStorage لتجنب التضارب
                localStorage.removeItem('contractFilters');

                toggleWarrantyFilter();

                // تطبيق فلتر warranty_status إذا كان موجود في URL
                if (effectiveUrlWarrantyStatus) {
                    $('#warrantyStatusFilter').val(effectiveUrlWarrantyStatus);
                    console.log('Warranty status from URL applied to filter:', effectiveUrlWarrantyStatus);
                }
            }

            if (urlMarketer) {
                $('#marketerFilter').val(urlMarketer);
                console.log('Marketer from URL applied to filter:', urlMarketer);
            }

            if (urlDateFrom) {
                $('#dateFromFilter').val(urlDateFrom);
            }

            if (urlDateTo) {
                $('#dateToFilter').val(urlDateTo);
            }

            toggleStatsSections();
            syncStatSelection();

            const selectedContracts = new Map();
            let dataTable;

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            function clearBulkSelection() {
                selectedContracts.clear();
                $('#selectAllContracts').prop({
                    checked: false,
                    indeterminate: false
                });
                $('.contract-bulk-checkbox').prop('checked', false);
                $('#dataTableObject tbody tr').removeClass('table-primary');
                updateBulkSelectionState();
            }

            function buildBulkStageGroups(selectedItems) {
                const groups = new Map();

                selectedItems.forEach(function(item) {
                    const targetText = item.actionType === 'travel_create'
                        ? (item.actionText || 'إضافة رحلة')
                        : item.nextStatusText;
                    const key = `${item.actionType}:${item.currentStatus}->${item.nextStatus || item.actionText}`;
                    if (!groups.has(key)) {
                        groups.set(key, {
                            count: 0,
                            actionType: item.actionType,
                            actionText: item.actionText,
                            currentStatusText: item.currentStatusText,
                            nextStatusText: targetText
                        });
                    }

                    groups.get(key).count += 1;
                });

                return Array.from(groups.values());
            }

            function getSelectedActionTypes(selectedItems) {
                return [...new Set(selectedItems.map(function(item) {
                    return item.actionType;
                }))];
            }

            function syncSelectAllCheckbox() {
                const pageCheckboxes = $('.contract-bulk-checkbox:enabled');
                const checkedCheckboxes = pageCheckboxes.filter(':checked');

                $('#selectAllContracts').prop('disabled', pageCheckboxes.length === 0);
                $('#selectAllContracts').prop('checked', pageCheckboxes.length > 0 && checkedCheckboxes.length === pageCheckboxes.length);
                $('#selectAllContracts').prop('indeterminate', checkedCheckboxes.length > 0 && checkedCheckboxes.length < pageCheckboxes.length);
            }

            function updateBulkSelectionState() {
                const selectedItems = Array.from(selectedContracts.values());
                const selectedCount = selectedItems.length;
                const actionTypes = getSelectedActionTypes(selectedItems);
                const actionType = actionTypes.length === 1 ? actionTypes[0] : null;

                $('#bulkSelectedCount').text(selectedCount);
                $('#clearBulkSelectionBtn').prop('disabled', selectedCount === 0);

                if (selectedCount === 0) {
                    $('#bulkUpdateCard').addClass('d-none');
                    $('#bulkSelectionSummary').text('اختر العقود القابلة للتحديث من الجدول لتفعيل التحديث الجماعي.');
                    $('#bulkStageSummary').empty();
                    $('#bulkStatusDateWrapper, #bulkSmsWrapper').removeClass('d-none');
                    $('#bulkUpdateStatusBtn').text('تحديث المحدد').prop('disabled', true);
                    syncSelectAllCheckbox();
                    return;
                }

                $('#bulkUpdateCard').removeClass('d-none');

                const groups = buildBulkStageGroups(selectedItems);
                if (!actionType) {
                    $('#bulkSelectionSummary').text(
                        `تم تحديد ${selectedCount} عقد بعمليات مختلفة. اختر عقود تحديث المراحل وحدها أو عقود التفييز وحدها لإضافة رحلة جماعية.`
                    );
                    $('#bulkStatusDateWrapper, #bulkSmsWrapper').addClass('d-none');
                    $('#bulkUpdateStatusBtn').text('اختر نوعاً واحداً').prop('disabled', true);
                } else if (actionType === 'travel_create') {
                    $('#bulkSelectionSummary').text(
                        `تم تحديد ${selectedCount} عقد في مرحلة التفييز وسيتم تحويلها إلى شاشة إنشاء رحلة واحدة بشكل مجمع.`
                    );
                    $('#bulkStatusDateWrapper, #bulkSmsWrapper').addClass('d-none');
                    $('#bulkUpdateStatusBtn').text('إضافة رحلة للمحدد').prop('disabled', false);
                } else if (groups.length === 1) {
                    const group = groups[0];
                    $('#bulkSelectionSummary').text(
                        `تم تحديد ${selectedCount} عقد في نفس المرحلة، وسيتم نقلها من ${group.currentStatusText} إلى ${group.nextStatusText}.`
                    );
                    $('#bulkStatusDateWrapper, #bulkSmsWrapper').removeClass('d-none');
                    $('#bulkUpdateStatusBtn').text('تحديث المحدد').prop('disabled', false);
                } else {
                    $('#bulkSelectionSummary').text(
                        `تم تحديد ${selectedCount} عقد من مراحل مختلفة، وسيتم تحديث كل عقد إلى مرحلته التالية المناسبة تلقائياً.`
                    );
                    $('#bulkStatusDateWrapper, #bulkSmsWrapper').removeClass('d-none');
                    $('#bulkUpdateStatusBtn').text('تحديث المحدد').prop('disabled', false);
                }

                const summaryHtml = groups.map(function(group) {
                    const actionIcon = group.actionType === 'travel_create'
                        ? 'fa-plane'
                        : 'fa-arrow-left';

                    return `<span class="bulk-stage-badge">${escapeHtml(group.currentStatusText)} <i class="fas ${actionIcon}"></i> ${escapeHtml(group.nextStatusText)} <span class="badge bg-light text-primary">${group.count}</span></span>`;
                }).join('');

                $('#bulkStageSummary').html(summaryHtml);
                syncSelectAllCheckbox();
            }

            function buildBulkUpdateResultHtml(response) {
                const skippedContracts = response.skipped_contracts || [];
                const visibleSkippedContracts = skippedContracts.slice(0, 6);
                let html = '<div class="text-start">';

                html += `<div class="mb-2">تم تحديث <strong>${response.updated_count || 0}</strong> عقد.</div>`;

                if ((response.skipped_count || 0) > 0) {
                    html += `<div class="mb-2">العقود التي لم تُحدّث: <strong>${response.skipped_count}</strong></div>`;
                }

                if ((response.sms_sent_count || 0) > 0) {
                    html += `<div class="mb-2">الإشعارات المرسلة: <strong>${response.sms_sent_count}</strong></div>`;
                }

                if (visibleSkippedContracts.length > 0) {
                    html += '<div class="small text-muted mt-3">';
                    visibleSkippedContracts.forEach(function(item) {
                        html += `<div>#${escapeHtml(item.id)}: ${escapeHtml(item.message)}</div>`;
                    });

                    if (skippedContracts.length > visibleSkippedContracts.length) {
                        html += `<div>... و${skippedContracts.length - visibleSkippedContracts.length} عقود أخرى</div>`;
                    }

                    html += '</div>';
                }

                html += '</div>';

                return html;
            }

            function syncSelectedRows() {
                $('.contract-bulk-checkbox').each(function() {
                    const checkbox = $(this);
                    const contractId = String(checkbox.val());
                    const isSelected = selectedContracts.has(contractId);

                    checkbox.prop('checked', isSelected);
                    checkbox.closest('tr').toggleClass('table-primary', isSelected);
                });

                updateBulkSelectionState();
            }

            let columns = [{
                    "data": 'bulk_select',
                    name: 'bulk_select',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    "data": 'id',
                    name: 'id',
                    orderable: true,
                    searchable: false
                },
                {
                    "data": "clirnt_data",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "maid_id",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "contract_info",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "duration",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "arrival_info",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "recruitment_office",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "marketer",
                    orderable: false,
                    searchable: false
                },
                @if($branchesEnabled ?? false)
                {
                    "data": "branch",
                    orderable: false,
                    searchable: false
                },
                @endif
                {
                    "data": "status",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "updateStatus",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                },
            ];

            // حفظ/استعادة حالة القائمة (الفلاتر + رقم الصفحة) عند الرجوع من صفحة تفاصيل العقد
            const recruitmentListFilterSelectors = [
                '#globalSearchFilter',
                '#statusFilter',
                '#warrantyStatusFilter',
                '#dateFromFilter',
                '#dateToFilter',
                '#clientPhoneFilter',
                '#clientNameFilter',
                '#maidPassportFilter',
                '#maidNameFilter',
                '#contractNumberFilter',
                '#nationalityFilter',
                '#religionFilter',
                '#officeFilter',
                '#requestTypeFilter',
                '#cityFilter',
                '#marketerFilter',
                '#branchFilter',
                '#invoicePresenceFilter',
                '#musanedTransferPresenceFilter',
                '#externalDispatchPresenceFilter'
            ];

            // الحالة المحفوظة لها الأولوية على فلاتر الـ URL لأنها تُحفظ عند كل عرض للجدول
            const recruitmentListState = window.ListState
                ? window.ListState.create({
                    key: 'recruitment-contracts' + (shelterContractsMode ? ':shelter' : ''),
                    filters: recruitmentListFilterSelectors,
                    onRestore: function(filters) {
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

            // إظهار مؤشر التحميل
            $('#loadingOverlay').show();
            $('body').addClass('loading');

            // إنشاء DataTable مع الفلتر المطبق
            dataTable = $('#dataTableObject').DataTable({
                processing: CleanLoader.getCleanProcessingMessage(),
                serverSide: true,
                searching: false,
                paging: true, // التأكد من تفعيل التصفح
                pageLength: 10, // تقليل عدد الصفوف لتسريع التحميل
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ], // خيارات عملية حتى لا يتأخر تحميل علاقات العقود
                deferRender: true, // تحسين الأداء للبيانات الكبيرة
                scroller: false,
                autoWidth: false, // إيقاف حساب العرض التلقائي
                destroy: true, // السماح بإعادة التهيئة
                ajax: {
                    url: "{{ route('RecruitmentContract.index') }}",
                    timeout: 45000,
                    type: 'GET',
                    dataSrc: function(json) {
                        // معالجة البيانات قبل العرض
                        console.log('Data received:', json.data.length, 'records');
                        return json.data;
                    },
                    data: function(d) {
                        d.global_search = $('#globalSearchFilter').val();
                        d.status = shelterContractsMode ? '' : $('#statusFilter').val();
                        d.shelter_contracts = shelterContractsMode ? 1 : '';
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.nationality = $('#nationalityFilter').val();
                        d.religion = $('#religionFilter').val();
                        d.office_id = $('#officeFilter').val();
                        d.request_type = $('#requestTypeFilter').val();
                        d.city = $('#cityFilter').val();
                        d.marketer_id = $('#marketerFilter').val(); // Add marketer filter
                        d.branch_id = $('#branchFilter').length ? $('#branchFilter').val() : '';
                        d.has_invoice = $('#invoicePresenceFilter').val();
                        d.has_musaned_transfer = $('#musanedTransferPresenceFilter').val();
                        d.has_external_dispatch = $('#externalDispatchPresenceFilter').val();
                        d.exclude_back = urlExcludeBack ? 1 : '';

                        // إضافة فلتر حالة الضمان فقط إذا كانت الحالة "عقود مكتملة"
                        const statusValue = $('#statusFilter').val();
                        if (!shelterContractsMode && (statusValue === 'arrival' || statusValue === 'completed' || statusValue === 'completed_stage') && $(
                                '#warrantyStatusFilter').val()) {
                            d.warranty_status = $('#warrantyStatusFilter').val();
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Response:', xhr.responseText);
                        $('#loadingOverlay').hide();
                        $('#dataTableObject').removeClass('loading');
                        $('body').removeClass('loading');

                        // إظهار رسالة خطأ للمستخدم
                        $('#dataTableObject tbody').html(
                            '<tr><td colspan="100%" class="text-center text-danger">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' +
                            'حدث خطأ في تحميل البيانات. يرجى المحاولة مرة أخرى.' +
                            '</td></tr>'
                        );
                    }
                },
                columns: columns,
                order: [
                    [1, 'desc']
                ],
                pageLength: 10, // تقليل عدد الصفوف لتسريع التحميل
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ], // خيارات عملية حتى لا يتأخر تحميل علاقات العقود
                // حفظ الحالة يقتصر على الرجوع من صفحة التفاصيل عبر زر العودة
                stateSave: !!recruitmentListState,
                stateDuration: 0,
                stateSaveCallback: recruitmentListState ? recruitmentListState.saveCallback : undefined,
                stateLoadCallback: recruitmentListState ? recruitmentListState.loadCallback : undefined,
                stateSaveParams: recruitmentListState ? recruitmentListState.saveParams : undefined,
                stateLoadParams: recruitmentListState ? recruitmentListState.loadParams : undefined,
                deferRender: true, // تحسين الأداء للبيانات الكبيرة
                scroller: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                    emptyTable: "لا توجد عقود متاحة",
                    zeroRecords: "لم يتم العثور على عقود تطابق معايير البحث",
                    processing: '<div class="d-flex justify-content-center align-items-center p-4"><div class="clean-loader-small me-3"><div class="loader-dot-small"></div><div class="loader-dot-small"></div><div class="loader-dot-small"></div></div><div class="text-primary fw-bold">جاري المعالجة...</div></div>',
                    search: "البحث:",
                    lengthMenu: "عرض _MENU_ سجل",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    infoEmpty: "عرض 0 إلى 0 من 0 سجل",
                    infoFiltered: "(مفلتر من _MAX_ سجل إجمالي)"
                },
                responsive: true,
                dom: 'lrtip', // إزالة الأزرار لتسريع التحميل
                initComplete: function() {
                    console.log('DataTable initialized successfully');
                    var info = this.api().page.info();
                    console.log('Records total:', info.recordsTotal);
                    console.log('Records displayed:', info.recordsDisplay);
                    console.log('Current status filter:', $('#statusFilter').val());

                    // إظهار رسالة إذا لم توجد بيانات عند التحميل الأولي
                    if (info.recordsTotal === 0) {
                        console.log('No contracts found on initial load');
                    }

                    // إزالة overlay التحميل وتمكين التفاعل
                    $('#loadingOverlay').hide();
                    $('body').removeClass('loading');
                },
                preDrawCallback: function(settings) {
                    // منع التفاعل أثناء التحميل
                    $('#dataTableObject').addClass('loading');
                },
                drawCallback: function(settings) {
                    const tableApi = this.api();

                    // تمكين التفاعل بعد التحميل
                    $('#dataTableObject').removeClass('loading');
                    syncSelectedRows();
                    closeRecruitmentActionsDropdown();
                    handleEmptyTableNotice(tableApi);
                    scheduleStatisticsUpdate();

                    // إعادة تهيئة tooltips إذا كانت موجودة
                    if (typeof $().tooltip === 'function') {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                }
            });

            function closeRecruitmentActionsDropdown() {
                $('#recruitmentActionsFloatingMenu').remove();

                $('#dataTableObject .actions-dropdown').each(function() {
                    $(this).removeClass('show');
                    $(this).find('.actions-btn').attr('aria-expanded', 'false').removeClass('show');
                    $(this).find('.dropdown-menu').removeClass('show').removeAttr('style');
                });
            }

            function placeRecruitmentActionsMenu($button, $menu) {
                const rect = $button[0].getBoundingClientRect();
                const menuWidth = Math.max($menu.outerWidth() || 250, 250);
                const menuHeight = Math.max($menu.outerHeight() || 280, 180);
                const viewportPadding = 12;
                const topSpace = rect.top - viewportPadding;
                const bottomSpace = window.innerHeight - rect.bottom - viewportPadding;
                const openUp = bottomSpace < menuHeight && topSpace > bottomSpace;
                const top = openUp
                    ? Math.max(viewportPadding, rect.top - menuHeight - 6)
                    : Math.max(
                        viewportPadding,
                        Math.min(window.innerHeight - viewportPadding - menuHeight, rect.bottom + 6)
                    );
                const left = Math.max(
                    viewportPadding,
                    Math.min(window.innerWidth - viewportPadding - menuWidth, rect.right - menuWidth)
                );

                $menu.css({
                    '--recruitment-actions-top': top + 'px',
                    '--recruitment-actions-left': left + 'px',
                    right: 'auto',
                    transform: 'none',
                    zIndex: 2147483647,
                    display: 'block',
                    minWidth: menuWidth + 'px'
                });
            }

            document.addEventListener('click', function(e) {
                const button = e.target.closest('#dataTableObject .actions-btn');

                if (!button) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $button = $(button);
                const $dropdown = $button.closest('.actions-dropdown');
                const $sourceMenu = $dropdown.find('.dropdown-menu');
                const isOpen = $button.attr('aria-expanded') === 'true';

                closeRecruitmentActionsDropdown();

                if (isOpen) {
                    return;
                }

                const $menu = $('<ul id="recruitmentActionsFloatingMenu" class="dropdown-menu dropdown-menu-end shadow-lg show recruitment-actions-floating-menu"></ul>');
                $menu.html($sourceMenu.html());
                $('body').append($menu);

                $dropdown.addClass('show');
                $button.attr('aria-expanded', 'true').addClass('show');
                placeRecruitmentActionsMenu($button, $menu);
            }, true);

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dataTableObject .actions-dropdown, #recruitmentActionsFloatingMenu').length) {
                    closeRecruitmentActionsDropdown();
                }
            });

            $(document).on('click', '#recruitmentActionsFloatingMenu .dropdown-item, #recruitmentActionsFloatingMenu form button', function() {
                setTimeout(closeRecruitmentActionsDropdown, 0);
            });

            $(document).on('change', '.contract-bulk-checkbox', function() {
                const checkbox = $(this);
                const contractId = String(checkbox.val());

                if (checkbox.is(':checked')) {
                    selectedContracts.set(contractId, {
                        id: contractId,
                        actionType: checkbox.data('action-type'),
                        actionText: checkbox.data('action-text'),
                        currentStatus: checkbox.data('current-status'),
                        currentStatusText: checkbox.data('current-status-text'),
                        nextStatus: checkbox.data('next-status'),
                        nextStatusText: checkbox.data('next-status-text')
                    });
                } else {
                    selectedContracts.delete(contractId);
                }

                checkbox.closest('tr').toggleClass('table-primary', checkbox.is(':checked'));
                updateBulkSelectionState();
            });

            $('#selectAllContracts').on('change', function() {
                const shouldSelect = $(this).is(':checked');

                $('.contract-bulk-checkbox:enabled').each(function() {
                    const checkbox = $(this);

                    if (checkbox.is(':checked') !== shouldSelect) {
                        checkbox.prop('checked', shouldSelect).trigger('change');
                    }
                });
            });

            $('#clearBulkSelectionBtn').on('click', function() {
                clearBulkSelection();
            });

            $('#bulkUpdateStatusBtn').on('click', function() {
                const selectedItems = Array.from(selectedContracts.values());
                if (selectedItems.length === 0) {
                    return;
                }

                const actionTypes = getSelectedActionTypes(selectedItems);
                if (actionTypes.length !== 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'اختيار غير متجانس',
                        text: 'اختر نوع عملية واحد فقط: إما تحديث مراحل العقود أو إضافة رحلة جماعية لعقود التفييز.'
                    });
                    return;
                }

                const bulkButton = $(this);
                const actionType = actionTypes[0];

                if (actionType === 'travel_create') {
                    const params = new URLSearchParams();
                    selectedItems.forEach(function(item) {
                        params.append('order_ids[]', item.id);
                    });

                    window.location.href = '{{ route('travel.control') }}' + '?' + params.toString();
                    return;
                }

                const bulkDate = $('#bulkStatusDate').val();
                const sendSms = $('#bulkSendSmsNotification').is(':checked');
                const confirmationHtml = `
                    <div class="text-start">
                        <div class="mb-2">${escapeHtml($('#bulkSelectionSummary').text())}</div>
                        <div class="mb-3">${$('#bulkStageSummary').html()}</div>
                        <div class="small text-muted">تاريخ التحديث: ${escapeHtml(bulkDate)}</div>
                        <div class="small text-muted mt-1">${sendSms ? 'سيتم إرسال إشعار نصي للعملاء المرتبطين بالعقود المحدثة.' : 'لن يتم إرسال إشعارات للعملاء في هذه العملية.'}</div>
                    </div>
                `;

                Swal.fire({
                    title: 'تأكيد التحديث الجماعي',
                    html: confirmationHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حدّث العقود',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    bulkButton.prop('disabled', true);

                    $.ajax({
                        url: '{{ route('RecruitmentContract.bulkUpdateStatus') }}',
                        method: 'POST',
                        data: {
                            row_ids: selectedItems.map(item => item.id),
                            date: bulkDate,
                            send_sms: sendSms,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            clearBulkSelection();

                            Swal.fire({
                                icon: 'success',
                                title: 'تم تنفيذ التحديث الجماعي',
                                html: buildBulkUpdateResultHtml(response)
                            }).then(() => {
                                dataTable.ajax.reload(null, false);
                            });
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            const message = response.message || 'حدث خطأ أثناء تنفيذ التحديث الجماعي';

                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر تنفيذ التحديث الجماعي',
                                html: buildBulkUpdateResultHtml({
                                    updated_count: response.updated_count || 0,
                                    skipped_count: response.skipped_count || 0,
                                    sms_sent_count: response.sms_sent_count || 0,
                                    skipped_contracts: response.skipped_contracts || [],
                                    message: message
                                }) + `<div class="text-start text-danger mt-3">${escapeHtml(message)}</div>`
                            });
                        },
                        complete: function() {
                            bulkButton.prop('disabled', selectedContracts.size === 0);
                        }
                    });
                });
            });

            // تحديث الجدول عند تغيير الفلتر
            $('#statusFilter').on('change', function() {
                toggleWarrantyFilter();
                toggleStatsSections();
                syncStatSelection();

                // لا تحدث الجدول إذا كان التغيير من الـ URL
                if (urlStatus && $(this).val() === urlStatus) {
                    console.log('Skipping table reload - filter change from URL');
                    return;
                }

                clearBulkSelection();
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                    // إظهار رسالة إذا لم توجد نتائج
                    var info = dataTable.page.info();
                    if (info.recordsTotal === 0) {
                        console.log('لا توجد نتائج بعد تطبيق الفلتر');
                    }
                });
            });

            // تحديث الجدول عند تغيير فلتر حالة الضمان
            $('#warrantyStatusFilter').on('change', function() {
                syncStatSelection();
                clearBulkSelection();
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // الفلترة من خلال بطاقات الإحصائيات
            $(document).on('click', '.stat-filter', function() {
                const statusValue = $(this).data('status');
                const warrantyValue = $(this).data('warranty');

                if (statusValue !== undefined) {
                    $('#statusFilter').val(statusValue);
                }

                toggleWarrantyFilter();

                if (warrantyValue !== undefined) {
                    $('#warrantyStatusFilter').val(warrantyValue);
                } else {
                    $('#warrantyStatusFilter').val('');
                }

                toggleStatsSections();
                syncStatSelection();

                const statsUrl = new URL(window.location.href);
                if (statusValue) {
                    statsUrl.searchParams.set('status', statusValue);
                } else {
                    statsUrl.searchParams.delete('status');
                }
                if (warrantyValue !== undefined && warrantyValue !== '') {
                    statsUrl.searchParams.set('warranty_status', warrantyValue);
                } else {
                    statsUrl.searchParams.delete('warranty_status');
                }
                window.history.replaceState({}, '', statsUrl.toString());

                clearBulkSelection();
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الجدول عند تغيير فلتر التاريخ
            $('#dateFromFilter, #dateToFilter').on('change', function() {
                // لا تحدث الجدول تلقائياً - انتظر زر البحث
            });

            // تحديث الجدول عند تغيير فلاتر البحث
            $('#globalSearchFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #requestTypeFilter, #cityFilter, #marketerFilter, #branchFilter, #invoicePresenceFilter, #musanedTransferPresenceFilter, #externalDispatchPresenceFilter')
                .on('change', function() {
                    // لا تحدث الجدول تلقائياً - انتظر زر البحث
                });

            $('#globalSearchFilter').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#btnSubmit').trigger('click');
                }
            });

            // زر البحث
            $('#btnSubmit').on('click', function() {
                clearBulkSelection();
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // Export exactly the records represented by the current table filters.
            $('.recruitment-contract-export').on('click', function() {
                const status = $('#statusFilter').val();
                const filters = {
                    global_search: $('#globalSearchFilter').val(),
                    status: status,
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    client_phone: $('#clientPhoneFilter').val(),
                    client_name: $('#clientNameFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_name: $('#maidNameFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    nationality: $('#nationalityFilter').val(),
                    religion: $('#religionFilter').val(),
                    office_id: $('#officeFilter').val(),
                    request_type: $('#requestTypeFilter').val(),
                    city: $('#cityFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                    exclude_back: urlExcludeBack ? 1 : ''
                };

                if ((status === 'arrival' || status === 'completed' || status === 'completed_stage') &&
                    $('#warrantyStatusFilter').val()) {
                    filters.warranty_status = $('#warrantyStatusFilter').val();
                }

                const exportUrl = new URL($(this).data('export-url'), window.location.origin);
                Object.entries(filters).forEach(function([key, value]) {
                    if (value !== null && value !== undefined && value !== '') {
                        exportUrl.searchParams.set(key, value);
                    }
                });

                this.href = exportUrl.toString();
            });

            // مسح الفلاتر
            $('#clearFilters').on('click', function() {
                $('#globalSearchFilter, #statusFilter, #warrantyStatusFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #requestTypeFilter, #cityFilter, #marketerFilter, #branchFilter, #invoicePresenceFilter, #musanedTransferPresenceFilter, #externalDispatchPresenceFilter')
                    .val('');
                $('#dateFromFilter, #dateToFilter').val('');
                localStorage.removeItem('contractFilters'); // مسح الفلاتر المحفوظة

                toggleWarrantyFilter();
                toggleStatsSections();
                syncStatSelection();

                clearBulkSelection();
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            function hasActiveSearchFilters() {
                return $('#globalSearchFilter').val() || $('#dateFromFilter').val() || $('#dateToFilter').val() ||
                    $('#clientPhoneFilter').val() || $('#clientNameFilter').val() ||
                    $('#maidPassportFilter').val() || $('#maidNameFilter').val() ||
                    $('#contractNumberFilter').val() || $('#nationalityFilter').val() ||
                    $('#religionFilter').val() || $('#officeFilter').val() || $('#requestTypeFilter').val() || $('#cityFilter').val() ||
                    $('#marketerFilter').val() || $('#invoicePresenceFilter').val() ||
                    $('#musanedTransferPresenceFilter').val() || $('#externalDispatchPresenceFilter').val() ||
                    ($('#branchFilter').length ? $('#branchFilter').val() : '');
            }

            function handleEmptyTableNotice(tableApi) {
                var info = tableApi.page.info();
                var hasSearchFilters = hasActiveSearchFilters();

                console.log('DataTable XHR completed:', {
                    recordsTotal: info.recordsTotal,
                    recordsDisplay: info.recordsDisplay,
                    hasSearchFilters: hasSearchFilters,
                    statusFilter: $('#statusFilter').val()
                });

                if (info.recordsTotal === 0 && hasSearchFilters) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لا توجد نتائج',
                        text: 'لم يتم العثور على عقود تطابق معايير البحث المحددة',
                        confirmButtonText: 'حسناً'
                    });
                } else if (info.recordsTotal === 0 && !hasSearchFilters && $('#statusFilter').val()) {
                    // عرض رسالة مناسبة عندما لا توجد عقود في المرحلة المحددة
                    var statusText = $('#statusFilter option:selected').text();
                    var statusValue = $('#statusFilter').val();
                    console.log('No contracts found for status:', statusText);

                    // رسالة خاصة لمرحلة انتظار اختيار العاملة
                    var message = '';
                    if (statusValue === 'pending_maid_selection') {
                        message = '<tr><td colspan="10" class="text-center">' +
                            '<div class="alert alert-warning mb-0">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' +
                            '<strong>لا توجد عقود في انتظار اختيار العاملة</strong><br>' +
                            '<small class="text-muted">جميع العقود إما تم تخصيص عاملة لها أو في مراحل أخرى</small>' +
                            '</div></td></tr>';
                    } else {
                        message =
                            '<tr><td colspan="10" class="text-center text-muted">لا توجد عقود في مرحلة "' +
                            statusText + '"</td></tr>';
                    }

                    // إظهار رسالة في الجدول بدلاً من الدايلوج
                    if ($('#dataTableObject tbody tr').length === 0) {
                        $('#dataTableObject tbody').html(message);
                    }
                }
            }

            // دالة تحديث الإحصائيات
            let statisticsRequest = null;
            let statisticsTimer = null;

            function scheduleStatisticsUpdate() {
                clearTimeout(statisticsTimer);

                statisticsTimer = setTimeout(function() {
                    updateStatistics();
                }, 450);
            }

            function updateStatistics() {
                if (statisticsRequest && statisticsRequest.readyState !== 4) {
                    statisticsRequest.abort();
                }

                statisticsRequest = $.ajax({
                    url: '{{ route('RecruitmentContract.statistics') }}',
                    method: 'GET',
                    data: {
                        global_search: $('#globalSearchFilter').val(),
                        status: $('#statusFilter').val(),
                        warranty_status: $('#warrantyStatusFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val(),
                        client_phone: $('#clientPhoneFilter').val(),
                        client_name: $('#clientNameFilter').val(),
                        maid_passport: $('#maidPassportFilter').val(),
                        maid_name: $('#maidNameFilter').val(),
                        contract_number: $('#contractNumberFilter').val(),
                        nationality: $('#nationalityFilter').val(),
                        religion: $('#religionFilter').val(),
                        office_id: $('#officeFilter').val(),
                        request_type: $('#requestTypeFilter').val(),
                        city: $('#cityFilter').val(),
                        marketer_id: $('#marketerFilter').val(),
                        branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                        has_invoice: $('#invoicePresenceFilter').val(),
                        has_musaned_transfer: $('#musanedTransferPresenceFilter').val(),
                        has_external_dispatch: $('#externalDispatchPresenceFilter').val(),
                        exclude_back: urlExcludeBack ? 1 : ''
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data || {};
                            $('[data-stat-key]').each(function() {
                                const key = $(this).data('statKey');
                                $(this).text(data[key] ?? 0);
                            });
                            toggleStatsSections();
                            syncStatSelection();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.statusText === 'abort') {
                            return;
                        }

                        console.error('Statistics error:', xhr.responseText);
                    }
                });
            }

            // حفظ الفلاتر في localStorage
            function saveFilters() {
                var filters = {
                    globalSearch: $('#globalSearchFilter').val(),
                    status: $('#statusFilter').val(),
                    warrantyStatus: $('#warrantyStatusFilter').val(),
                    clientPhone: $('#clientPhoneFilter').val(),
                    clientName: $('#clientNameFilter').val(),
                    maidPassport: $('#maidPassportFilter').val(),
                    maidName: $('#maidNameFilter').val(),
                    contractNumber: $('#contractNumberFilter').val(),
                    nationality: $('#nationalityFilter').val(),
                    religion: $('#religionFilter').val(),
                    requestType: $('#requestTypeFilter').val(),
                    city: $('#cityFilter').val(),
                    dateFrom: $('#dateFromFilter').val(),
                    dateTo: $('#dateToFilter').val(),
                    marketer: $('#marketerFilter').val(), // Save marketer filter
                    branch: $('#branchFilter').length ? $('#branchFilter').val() : '',
                    invoicePresence: $('#invoicePresenceFilter').val(),
                    musanedTransferPresence: $('#musanedTransferPresenceFilter').val(),
                    externalDispatchPresence: $('#externalDispatchPresenceFilter').val()
                };
                localStorage.setItem('contractFilters', JSON.stringify(filters));
            }

            // استعادة الفلاتر من localStorage (فقط إذا لم يكن هناك status في الـ URL)
            function loadFilters() {
                // الفلاتر المستعادة عند الرجوع من صفحة التفاصيل لها الأولوية
                if (recruitmentListState && recruitmentListState.restored) {
                    console.log('Skipping localStorage load because list state was restored');
                    return;
                }

                // لا تحمل الفلاتر من localStorage إذا كان هناك status في الـ URL
                if (urlStatus || urlDateFrom || urlDateTo || urlExcludeBack) {
                    console.log('Skipping localStorage load because filters are in URL');
                    return;
                }

                var filters = localStorage.getItem('contractFilters');
                if (filters) {
                    filters = JSON.parse(filters);
                    $('#globalSearchFilter').val(filters.globalSearch || '');
                    $('#statusFilter').val(filters.status || '');
                    $('#warrantyStatusFilter').val(filters.warrantyStatus || '');
                    $('#clientPhoneFilter').val(filters.clientPhone || '');
                    $('#clientNameFilter').val(filters.clientName || '');
                    $('#maidPassportFilter').val(filters.maidPassport || '');
                    $('#maidNameFilter').val(filters.maidName || '');
                    $('#contractNumberFilter').val(filters.contractNumber || '');
                    $('#nationalityFilter').val(filters.nationality || '');
                    $('#religionFilter').val(filters.religion || '');
                    $('#requestTypeFilter').val(filters.requestType || '');
                    $('#cityFilter').val(filters.city || '');
                    $('#dateFromFilter').val(filters.dateFrom || '');
                    $('#dateToFilter').val(filters.dateTo || '');
                    $('#marketerFilter').val(filters.marketer || ''); // Load marketer filter
                    $('#branchFilter').val(filters.branch || '');
                    $('#invoicePresenceFilter').val(filters.invoicePresence || '');
                    $('#musanedTransferPresenceFilter').val(filters.musanedTransferPresence || '');
                    $('#externalDispatchPresenceFilter').val(filters.externalDispatchPresence || '');
                }
            }

            // تحميل الفلاتر عند تحميل الصفحة
            loadFilters();
            toggleWarrantyFilter();
            toggleStatsSections();
            syncStatSelection();

            // حفظ الفلاتر عند تغييرها (فقط إذا لم يكن هناك status في الـ URL)
            $('.form-control, .form-select').on('change', function() {
                // لا تحفظ الفلاتر إذا كان هناك status في الـ URL لتجنب التضارب
                if (!urlStatus) {
                    saveFilters();
                }
            });

            // التحكم في طي الفلاتر
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            function addTravel(contractId) {
                console.log('Add travel button clicked for contract ID:', contractId);

                if (!contractId) {
                    console.error('No contract ID found');
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف العقد',
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                var url = '{{ route('travel.control') }}?contract_id=' + contractId;
                console.log('Redirecting to:', url);
                window.location.href = url;
            }

            $(document).on('click', '.add-travel-btn', function(e) {
                e.preventDefault();
                addTravel($(this).data('contract-id'));
            });

            $(document).on('submit', '.release-completed-maid-form', function(e) {
                e.preventDefault();

                const form = this;
                const maidName = $(form).data('maid-name');
                const contractId = $(form).data('contract-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'اكتمال العقد وتسفير العاملة',
                    html: 'سيتم اعتماد اكتمال مدة العقد <strong>#' + escapeHtml(String(contractId)) + '</strong> وتسفير العاملة <strong>' + escapeHtml(String(maidName)) + '</strong> وإتاحتها لإنشاء عقد استقدام جديد.<br><br>هل تريد المتابعة؟',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i> نعم، تنفيذ الإجراء',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'جاري تنفيذ الإجراء...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function() {
                                Swal.showLoading();
                            }
                        });
                        form.submit();
                    }
                });
            });

            // معالج زر الإرجاع من عمود المرحلة التالية
            var pendingBackContractId = null;
            var pendingBackTargetLabel = null;

            function backContract(rowId, targetLabel) {
                pendingBackContractId = rowId;
                pendingBackTargetLabel = targetLabel || '';

                var message = pendingBackTargetLabel
                    ? 'هل أنت متأكد من إرجاع هذا العقد إلى مرحلة ' + escapeHtml(pendingBackTargetLabel) + '؟'
                    : 'هل أنت متأكد من إرجاع هذا العقد؟';

                $('#confirmBackModal .modal-body .alert').html(
                    '<i class="fas fa-exclamation-triangle me-2"></i>' + message
                );
                $('#confirmBackModal').modal('show');
            }

            $(document).on('click', '.backContract', function() {
                var rowId = $(this).data('row-id');
                var targetLabel = $(this).data('target-label');
                backContract(rowId, targetLabel);
            });

            $('#confirmBackBtn').click(function() {
                if (!pendingBackContractId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف العقد'
                    });
                    return;
                }

                var button = $(this);
                var originalHtml = button.html();

                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> جاري الإرجاع...');

                $.ajax({
                    url: '{{ route('RecruitmentContract.back', ':rowId') }}'.replace(':rowId', pendingBackContractId),
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم الإرجاع',
                                text: response.message
                            }).then(function() {
                                $('#confirmBackModal').modal('hide');
                                pendingBackContractId = null;
                                pendingBackTargetLabel = null;
                                dataTable.ajax.reload(null, false);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'تعذر إرجاع العقد'
                            });
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'حدث خطأ أثناء إرجاع العقد';

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: message
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // معالج زر تحديث الحالة
            $(document).on('click', '.complete-stage-step-btn', function() {
                const button = $(this);
                $('#stageStepUpdateUrl').val(button.data('url'));
                $('#stageStepParentName').text(button.data('stage-name') || '—');
                $('#stageStepName').text(button.data('step-name') || '—');
                $('#stageStepNotes').val('');
                $('#completeStageStepModal').modal('show');
            });

            $('#confirmStageStepBtn').on('click', function() {
                const button = $(this);
                const originalHtml = button.html();
                const url = $('#stageStepUpdateUrl').val();

                if (!url) return;

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> جارٍ الحفظ');

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    data: {
                        completed: 1,
                        notes: $('#stageStepNotes').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#completeStageStepModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'تم تأكيد الخطوة',
                            text: response.next_step
                                ? 'الخطوة التالية: ' + response.next_step.name
                                : 'اكتملت جميع الخطوات الفرعية لهذه المرحلة.',
                            timer: 2200,
                            showConfirmButton: false
                        });
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'تعذر تأكيد الخطوة',
                            text: xhr.responseJSON?.message || 'حدث خطأ أثناء حفظ الخطوة.'
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            $(document).on('click', '.update-status-btn', function() {
                var rowId = $(this).data('row-id');
                var status = $(this).data('status');
                var nextStatus = $(this).data('next-status');

                $('#updateStatusModal #rowId').val(rowId);
                $('#updateStatusModal #status').val(status);
                $('#updateStatusModal #nextStatus').val(nextStatus);
                $('#updateStatusModal #sendSmsNotification').prop('checked', true);

                var today = new Date().toISOString().split('T')[0];
                $('#updateStatusModal #statusDate').val(today);
                $('#updateStatusModal').modal('show');
            });

            $('#manualNextStatus').on('change', function() {
                $('#updateStatusModal #nextStatus').val($(this).val());
            });

            // معالج زر حفظ الحالة
            $('#saveStatusBtn').click(function() {
                var rowId = $('#updateStatusModal #rowId').val();
                var nextStatus = $('#updateStatusModal #manualNextStatusWrapper').hasClass('d-none')
                    ? $('#updateStatusModal #nextStatus').val()
                    : $('#updateStatusModal #manualNextStatus').val();
                var date = $('#updateStatusModal #statusDate').val();
                var sendSms = $('#updateStatusModal #sendSmsNotification').is(':checked');

                if (!nextStatus) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'تحذير',
                        text: 'يرجى اختيار الحالة التالية'
                    });
                    return;
                }

                $.ajax({
                    url: '{{ route('RecruitmentContract.updateStatus') }}',
                    method: 'POST',
                    data: {
                        row_id: rowId,
                        next_status: nextStatus,
                        date: date,
                        send_sms: sendSms,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم تحديث الحالة بنجاح',
                                text: response.message
                            }).then(() => {
                                $('#updateStatusModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة'
                        });
                    }
                });
            });

            function populateManualStatusOptions(modal, options, selectedValue) {
                var select = modal.find('#manualNextStatus');
                select.empty();

                if (!options.length) {
                    select.append('<option value="">?? ???? ????? ?????</option>');
                    modal.find('#saveStatusBtn').prop('disabled', true);
                    modal.find('#nextStatus').val('');
                    return;
                }

                options.forEach(function(option) {
                    select.append(new Option(option.label, option.value));
                });

                var targetValue = selectedValue && options.some(function(option) {
                    return option.value === selectedValue;
                }) ? selectedValue : options[0].value;

                select.val(targetValue);
                modal.find('#nextStatus').val(targetValue);
                modal.find('#saveStatusBtn').prop('disabled', false);
            }

            // معالج فتح Modal تحديث الحالة
            $('#updateStatusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var rowId = button.data('row-id');
                var status = button.data('status');
                var nextStatus = button.data('next-status');
                var date = button.data('date');
                var modal = $(this);
                var isManual = String(button.data('manual-status-select') || '0') === '1';
                var rawStatuses = button.attr('data-target-statuses') || '[]';
                var targetStatuses = [];

                try {
                    targetStatuses = JSON.parse(rawStatuses);
                } catch (error) {
                    targetStatuses = [];
                }

                modal.find('#rowId').val(rowId);
                modal.find('#status').val(status);
                modal.find('#nextStatus').val(nextStatus);
                modal.find('#statusDate').val(date);
                modal.find('#currentStatus').val(getContractStatusText(status));
                modal.find('#sendSmsNotification').prop('checked', true);

                if (isManual) {
                    modal.find('#nextStatusDisplayWrapper').addClass('d-none');
                    modal.find('#manualNextStatusWrapper').removeClass('d-none');
                    populateManualStatusOptions(modal, targetStatuses, nextStatus);
                } else {
                    modal.find('#manualNextStatusWrapper').addClass('d-none');
                    modal.find('#nextStatusDisplayWrapper').removeClass('d-none');
                    modal.find('#nextStatusDisplay').val(getContractStatusText(nextStatus));
                    modal.find('#saveStatusBtn').prop('disabled', false);
                }
            });

            // دالة للحصول على نص الحالة
            function getContractStatusText(status) {
                var statusLabels = @json($contractStageLabels, JSON_UNESCAPED_UNICODE);
                var statusMap = {};
                Object.keys(statusLabels).forEach(function(key) {
                    statusMap[key] = statusLabels[key]['{{ app()->getLocale() === 'en' ? 'en' : 'ar' }}'] || key;
                });
                return statusMap[status] || status;
            }

            // معالج إكمال الرحلة
            $(document).on('click', '.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                var button = $(this);

                console.log('Complete trip button clicked for trip ID:', tripId);

                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟ سيتم تحديث حالة الرحلة إلى مكتملة.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إكمال',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // إظهار حالة التحميل
                        button.prop('disabled', true).html(
                            '<i class="fa fa-spinner fa-spin me-1"></i>جاري الإكمال...');

                        $.ajax({
                            url: '{{ url('/admin/travel/complete') }}/' + tripId,
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                console.log('Complete trip response:', response);

                                if (response.success) {
                                    const hasAccountingWarnings = Array.isArray(response.accounting_warnings) &&
                                        response.accounting_warnings.length > 0;
                                    Swal.fire({
                                        icon: hasAccountingWarnings ? 'warning' : 'success',
                                        title: hasAccountingWarnings ? 'تم الإكمال مع تنبيه محاسبي' : 'تم بنجاح',
                                        text: response.message ||
                                            'تم إكمال الرحلة بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message ||
                                            'حدث خطأ أثناء إكمال الرحلة',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });

                                let errorMessage = 'حدث خطأ أثناء إكمال الرحلة';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: errorMessage,
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function() {
                                // إعادة تفعيل الزر
                                button.prop('disabled', false).html(
                                    '<i class="fas fa-check me-1"></i>إكمال الرحلة');

                                // إعادة تحميل الجدول
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.cancel-travel-btn', function(e) {
                e.preventDefault();

                var tripId = $(this).data('trip-id');
                if (!tripId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف الرحلة'
                    });
                    return;
                }

                Swal.fire({
                    title: 'إلغاء الرحلة',
                    text: 'سيتم إلغاء الرحلة فقط دون تغيير حالة العقد أو القيود المالية المرتبطة به.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إلغاء الرحلة',
                    cancelButtonText: 'تراجع'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('travel.delete', ':tripId') }}'.replace(':tripId', tripId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم الإلغاء',
                                text: 'تم إلغاء الرحلة بنجاح'
                            }).then(function() {
                                dataTable.ajax.reload(null, false);
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: xhr.responseJSON?.message || 'حدث خطأ أثناء إلغاء الرحلة'
                            });
                        }
                    });
                });
            });

            // معالج زر إضافة عاملة من عمود المرحلة التالية
            $(document).on('click', '.btn-action[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });


            // معالج زر إلغاء العقد
            $(document).on('click', '.cancel-contract-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var contractId = button.data('contract-id');
                var contractNumber = button.data('contract-number');
                var clientName = button.data('client-name');
                var maidName = button.data('maid-name');

                openCancelContractModal(contractId, contractNumber, clientName, maidName);
            });

            @php
                $restorableReturnedContractStatuses = collect(\App\Enums\ingaz\ContractStatusEnum::cases())
                    ->reject(function ($status) {
                        return in_array($status, [
                            \App\Enums\ingaz\ContractStatusEnum::Back,
                            \App\Enums\ingaz\ContractStatusEnum::Refunded,
                            \App\Enums\ingaz\ContractStatusEnum::Closed,
                            \App\Enums\ingaz\ContractStatusEnum::Sari,
                        ], true);
                    })
                    ->mapWithKeys(function ($status) {
                        return [$status->value => getContractStatus($status->value)];
                    })
                    ->all();
            @endphp

            $(document).on('click', '.restore-returned-contract-btn', function(e) {
                e.preventDefault();
                var contractId = $(this).data('contract-id');
                var restorableStatuses = @json($restorableReturnedContractStatuses);

                function submitRestoreReturnedContract(previousStatus) {
                    var requestData = {
                        _token: '{{ csrf_token() }}'
                    };

                    if (previousStatus) {
                        requestData.previous_status = previousStatus;
                    }

                    $.ajax({
                        url: '{{ route('RecruitmentContract.restoreReturned', ':contractId') }}'.replace(':contractId', contractId),
                        method: 'POST',
                        data: requestData,
                        success: function(response) {
                            Swal.fire('نجح', response.message, 'success');
                            dataTable.ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            var message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'تعذر التراجع عن إرجاع العقد.';

                            if (xhr.status === 422 && message.indexOf('تعذر تحديد حالة العقد السابقة') !== -1) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'حدد الحالة السابقة',
                                    text: 'هذا العقد قديم ولا يوجد له سجل حالة سابق كافٍ، اختر الحالة التي كان عليها قبل الإرجاع.',
                                    input: 'select',
                                    inputOptions: restorableStatuses,
                                    inputPlaceholder: 'اختر الحالة السابقة',
                                    showCancelButton: true,
                                    confirmButtonText: 'استعادة',
                                    cancelButtonText: 'إلغاء',
                                    confirmButtonColor: '#198754',
                                    inputValidator: function(value) {
                                        if (!value) {
                                            return 'يجب اختيار الحالة السابقة';
                                        }
                                    }
                                }).then(function(result) {
                                    if (result.isConfirmed) {
                                        submitRestoreReturnedContract(result.value);
                                    }
                                });

                                return;
                            }

                            Swal.fire('تعذر الاستعادة', message, 'error');
                        }
                    });
                }

                Swal.fire({
                    icon: 'question',
                    title: 'التراجع عن إرجاع العقد؟',
                    text: 'سيُعاد العقد إلى آخر حالة موثقة قبل الإرجاع بعد التأكد من عدم وجود ارتباطات تمنع ذلك.',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، استعادة العقد',
                    cancelButtonText: 'إلغاء',
                    confirmButtonColor: '#198754'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    submitRestoreReturnedContract();
                });
            });

            function formatRefundMoney(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function escapeRefundHtml(value) {
                return String(value || '').replace(/[&<>"']/g, function(character) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[character];
                });
            }

            function resetRefundPreview(message) {
                $('#refundPreviewWrapper').hide();
                $('#refundPreviewLines').html('<tr><td colspan="5" class="text-center text-muted">لا توجد بيانات معاينة</td></tr>');
                $('#refundPreviewWarnings').addClass('d-none').empty();
                $('#refundPreviewPlaceholder')
                    .removeClass('alert-danger alert-info')
                    .addClass('alert-info')
                    .text(message || 'اختر تاريخ الاسترداد والطرف المتسبب لعرض المبلغ المدفوع للعميل والقيود المتوقعة قبل التسجيل.')
                    .show();
            }

            function renderRefundPreview(preview) {
                var summary = preview.summary || {};
                var lines = preview.lines || [];
                var totals = preview.totals || {};
                var warnings = preview.warnings || [];

                $('#refund_penalty_percent').val(summary.penalty_percent);
                $('#refund_penalty_amount').val(Number(summary.penalty_amount).toFixed(2)).attr('max', summary.contract_amount);
                $('#refundPreviewContractAmount').text(formatRefundMoney(summary.contract_amount));
                $('#refundPreviewPenaltyPercent').text(formatRefundMoney(summary.penalty_percent) + '%');
                $('#refundPreviewPenaltyAmount').text(formatRefundMoney(summary.penalty_amount));
                $('#refundPreviewRefundAmount').text(formatRefundMoney(summary.refund_amount));
                $('#refundPreviewPreviousCredit').text(formatRefundMoney(summary.previous_credit_amount));
                $('#refundPreviewRemainingCredit').text(formatRefundMoney(summary.remaining_credit_note_amount));
                $('#refundPreviewParty').text(summary.cancelled_by_label || 'غير محدد');
                $('#refundPreviewTotalDebtor').text(formatRefundMoney(totals.debtor));
                $('#refundPreviewTotalCreditor').text(formatRefundMoney(totals.creditor));

                if (warnings.length) {
                    $('#refundPreviewWarnings')
                        .removeClass('d-none')
                        .html(warnings.map(function(warning) {
                            return '<div><i class="fas fa-triangle-exclamation me-1"></i>' + escapeRefundHtml(warning) + '</div>';
                        }).join(''));
                } else {
                    $('#refundPreviewWarnings').addClass('d-none').empty();
                }

                if (lines.length) {
                    $('#refundPreviewLines').html(lines.map(function(line) {
                        var accountCode = line.account_code ? '<span class="text-muted me-1">#' + escapeRefundHtml(line.account_code) + '</span>' : '';
                        return '<tr>' +
                            '<td><span class="badge bg-secondary">' + escapeRefundHtml(line.section || '-') + '</span></td>' +
                            '<td>' + accountCode + escapeRefundHtml(line.account_name || 'غير محدد') + '</td>' +
                            '<td class="text-success fw-semibold">' + formatRefundMoney(line.debtor_value) + '</td>' +
                            '<td class="text-danger fw-semibold">' + formatRefundMoney(line.creditor_value) + '</td>' +
                            '<td class="small text-muted">' + escapeRefundHtml(line.statement || '') + '</td>' +
                            '</tr>';
                    }).join(''));
                } else {
                    $('#refundPreviewLines').html('<tr><td colspan="5" class="text-center text-muted">لا توجد قيود متوقعة قبل تحويل مساند</td></tr>');
                }

                $('#refundPreviewPlaceholder').hide();
                $('#refundPreviewWrapper').show();
            }

            var refundPreviewRequest = null;
            var refundPenaltySource = null;
            function refundFormData() {
                return $('#refundContractForm').serializeArray().filter(function(field) {
                    return !['penalty_percent', 'penalty_amount'].includes(field.name) || field.name === refundPenaltySource;
                });
            }
            function loadRefundPreview() {
                if (refundPreviewRequest) refundPreviewRequest.abort();
                var hasMusanedTransfer = $('#refundContractForm').data('has-musaned-transfer') === 1;
                var cancelledBy = $('input[name="cancelled_by"]:checked').val() || '';

                if (hasMusanedTransfer && !cancelledBy) {
                    resetRefundPreview('اختر الطرف المتسبب في الإلغاء لاحتساب الخصم والقيود بدقة.');
                    return;
                }

                refundPreviewRequest = $.ajax({
                    url: '{{ route('RecruitmentContract.refundPreview') }}',
                    method: 'POST',
                    data: refundFormData(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderRefundPreview(response.preview);
                        } else {
                            resetRefundPreview(response.message || 'تعذر تحميل معاينة الاسترداد.');
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'تعذر تحميل معاينة الاسترداد.';
                        if (xhr.statusText === 'abort') return;
                        resetRefundPreview(message);
                        $('#refundPreviewPlaceholder')
                            .removeClass('alert-info')
                            .addClass('alert-danger');
                    }
                });
            }

            $(document).on('click', '.refund-contract-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var hasMusanedTransfer = String(button.data('has-musaned-transfer')) === '1';

                $('#refundContractId').val(button.data('contract-id'));
                $('#refundContractNumber').text(button.data('contract-number'));
                $('#refundClientName').text(button.data('client-name'));
                $('#refundContractAmount').text(Number(button.data('contract-amount') || 0).toFixed(2));
                if (refundPreviewRequest) refundPreviewRequest.abort();
                refundPenaltySource = null;
                $('#refundPenaltyFields').toggle(hasMusanedTransfer);
                $('#refund_penalty_percent, #refund_penalty_amount').val('').prop('disabled', !hasMusanedTransfer);
                $('#refund_reason').val('');
                $('#refund_date').val('{{ date('Y-m-d') }}');
                $('input[name="cancelled_by"]').prop('checked', false).prop('required', hasMusanedTransfer);
                $('#refundContractForm').data('has-musaned-transfer', hasMusanedTransfer ? 1 : 0);
                resetRefundPreview();

                if (hasMusanedTransfer) {
                    $('#refundCancelledByWrapper').removeClass('d-none');
                    $('#refundBeforeTransferNote').addClass('d-none');
                } else {
                    $('#refundCancelledByWrapper').addClass('d-none');
                    $('#refundBeforeTransferNote').removeClass('d-none');
                    loadRefundPreview();
                }

                $('#refundContractModal').modal('show');
            });

            $('#refund_penalty_percent, #refund_penalty_amount').on('input', function() {
                if (refundPreviewRequest) refundPreviewRequest.abort();
                refundPenaltySource = this.value === '' ? null : this.name;
                $(this.id === 'refund_penalty_percent' ? '#refund_penalty_amount' : '#refund_penalty_percent').val('');
                resetRefundPreview('اضغط خارج الحقل لتحديث معاينة الاسترداد.');
            }).on('change', loadRefundPreview);
            $('#refund_date').on('change', function() {
                refundPenaltySource = null;
                $('#refund_penalty_percent, #refund_penalty_amount').val('');
                loadRefundPreview();
            });
            $(document).on('change', 'input[name="cancelled_by"]', loadRefundPreview);

            $('#submitRefundContractBtn').on('click', function() {
                var button = $(this);
                var originalHtml = button.html();
                var form = $('#refundContractForm')[0];

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>جاري الحفظ...');

                $.ajax({
                    url: '{{ route('RecruitmentContract.refund') }}',
                    method: 'POST',
                    data: refundFormData(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#refundContractModal').modal('hide');
                            Swal.fire('نجح', response.message, 'success');
                            dataTable.ajax.reload(null, false);
                        } else {
                            Swal.fire('خطأ', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'حدث خطأ أثناء تسجيل الاسترداد';
                        Swal.fire('خطأ', message, 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // تفعيل tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // إعادة تفعيل tooltips بعد تحميل الجدول
            dataTable.on('draw', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                    '[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });


            });
        });
    </script>


    <script>
        // دالة فتح موديل إلغاء العقد
        function openCancelContractModal(contractId, contractNumber, clientName, maidName) {
            $('#contractId').val(contractId);
            $('#contractNumber').text(contractNumber);
            $('#clientName').text(clientName);
            $('#maidName').text(maidName);
            $('#cancel_reason').val('');
            $('input[name="maid_status_after_cancel"]').prop('checked', false);
            $('#cancelContractModal').modal('show');
        }

        // دالة إرسال إلغاء العقد
        function submitCancelContract() {
            var formData = $('#cancelContractForm').serialize();

            $.ajax({
                url: '{{ route('RecruitmentContract.cancel') }}',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#cancelContractModal').modal('hide');
                        Swal.fire('نجح', response.message, 'success');
                        $('#dataTableObject').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('خطأ', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cancel contract error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    Swal.fire('خطأ', 'حدث خطأ أثناء إلغاء العقد', 'error');
                }
            });
        }
    </script>

    <!-- موديل حذف العقد -->
    <div class="modal fade" id="deleteContractModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>
                        حذف العقد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>تنبيه:</strong> سيتم حذف العقد نهائياً ولا يمكن التراجع عن هذا الإجراء.
                    </div>

                    <div class="mb-3">
                        <strong>تفاصيل العقد:</strong>
                        <ul class="list-unstyled mt-2">
                            <li><strong>رقم العقد:</strong> <span id="deleteContractNumber"></span></li>
                            <li><strong>اسم العميل:</strong> <span id="deleteClientName"></span></li>
                            <li><strong>اسم العاملة:</strong> <span id="deleteMaidName"></span></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>حالة العاملة بعد الحذف:</strong></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="maid_status" id="maidAvailable"
                                value="available" checked>
                            <label class="form-check-label" for="maidAvailable">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                متاحة (يمكن إضافتها لعقود أخرى)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="maid_status" id="maidBackOut"
                                value="back_out">
                            <label class="form-check-label" for="maidBackOut">
                                <i class="fas fa-times-circle text-danger me-1"></i>
                                Back Out (غير متاحة)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>
                        تأكيد الحذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var contractToDelete = null;

            // فتح موديل الحذف
            $(document).on('click', '.delete-contract-btn', function() {
                var contractId = $(this).data('contract-id');
                var contractNumber = $(this).data('contract-number');
                var clientName = $(this).data('client-name');
                var maidName = $(this).data('maid-name');

                contractToDelete = contractId;
                $('#deleteContractNumber').text(contractNumber);
                $('#deleteClientName').text(clientName);
                $('#deleteMaidName').text(maidName);

                $('#deleteContractModal').modal('show');
            });

            // تأكيد الحذف
            $('#confirmDeleteBtn').click(function() {
                if (!contractToDelete) return;

                var maidStatus = $('input[name="maid_status"]:checked').val();
                var button = $(this);
                var originalText = button.html();

                button.prop('disabled', true).html(
                    '<i class="fa fa-spinner fa-spin me-1"></i>جاري الحذف...');

                $.ajax({
                    url: '{{ route('RecruitmentContract.destroy', ':id') }}'.replace(':id',
                        contractToDelete),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        maid_status: maidStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#deleteContractModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message,
                                confirmButtonText: 'حسناً'
                            }).then(() => {
                                $('#dataTableObject').DataTable().ajax.reload(null,
                                    false);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message,
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete contract error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء حذف العقد',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });

            // إعادة تعيين المتغير عند إغلاق الموديل
            $('#deleteContractModal').on('hidden.bs.modal', function() {
                contractToDelete = null;
            });


            // حفظ ملاحظة العقد
            let currentNoteContractId = null;
            let currentNotesCount = 0;
            let currentNoteButton = null;

            $(document).on('click', '.add-contract-note-btn', function() {
                currentNoteButton = this;
                currentNoteContractId = $(this).data('contract-id');
                currentNotesCount = parseInt($(this).data('notes-count'), 10) || 0;

                const url = "{{ route('RecruitmentContract.notes.store', ':id') }}".replace(':id', currentNoteContractId);
                $('#addContractNoteForm').attr('action', url);
                $('#contractNoteContractId').text('#' + currentNoteContractId);
                $('#contractNotesCountBadge').text(`هناك ${currentNotesCount} ملاحظات سابقة`);
            });

            $('#addContractNoteForm').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const url = $form.attr('action');
                const data = $form.serialize();
                const $submitBtn = $form.find('button[type="submit"]');

                if (!url) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم تحديد العقد'
                    });
                    return;
                }

                $submitBtn.prop('disabled', true);

                $.ajax({
                    url: url,
                    method: "POST",
                    data: data,
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        if (res && res.success) {
                            if (typeof res.notes_count !== 'undefined') {
                                currentNotesCount = res.notes_count;
                            } else {
                                currentNotesCount = (currentNotesCount || 0) + 1;
                            }

                            $('#contractNotesCountBadge').text(currentNotesCount);
                            if (currentNoteButton) {
                                $(currentNoteButton).data('notes-count', currentNotesCount);
                            }

                            $('#addContractNoteModal').modal('hide');
                            $form[0].reset();

                            Swal.fire({
                                icon: 'success',
                                title: 'نجاح',
                                text: res.message || 'تم إضافة الملاحظة بنجاح'
                            });

                            if ($.fn.DataTable.isDataTable('#dataTableObject')) {
                                $('#dataTableObject').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: res.message || 'حدث خطأ أثناء إضافة الملاحظة'
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'حدث خطأ أثناء إضافة الملاحظة';
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            if (errors.length) {
                                message = errors.join('\\n');
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: message
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
