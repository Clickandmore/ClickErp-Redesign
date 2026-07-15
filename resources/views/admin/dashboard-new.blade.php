@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/css/dashboard-new.css') }}?v=16">
    <link rel="stylesheet" href="{{ asset('admin/assets/cssbundle/fullcalendar.min.css') }}">
@endsection

@section('page-title')
    {{ __('admin.dashboard') }}
@endsection

@section('content')
    @php
        $canViewDashboard = AdminGuardHasPerm(\App\PermKeys::DASHBOARD_VIEW);
        $canManageDashboard = AdminGuardHasPerm(\App\PermKeys::DASHBOARD_MANAGE);
        $canViewDashboardStatistics =
            $canViewDashboard || $canManageDashboard || AdminGuardHasPerm(\App\PermKeys::DASHBOARD_VIEW_STATISTICS);
        $canViewDashboardAnalytics =
            $canViewDashboard || $canManageDashboard || AdminGuardHasPerm(\App\PermKeys::DASHBOARD_ANALYTICS);

        $canViewDashboardSection = static function (
            ?string $permission = null,
            array $fallbackPermissions = [],
            bool $allowStatistics = false,
            bool $allowAnalytics = false,
        ) use ($canManageDashboard, $canViewDashboardStatistics, $canViewDashboardAnalytics): bool {
            if ($canManageDashboard) {
                return true;
            }

            if ($permission && AdminGuardHasPerm($permission)) {
                return true;
            }

            foreach ($fallbackPermissions as $fallbackPermission) {
                if ($fallbackPermission && AdminGuardHasPerm($fallbackPermission)) {
                    return true;
                }
            }

            if ($allowStatistics && $canViewDashboardStatistics) {
                return true;
            }

            if ($allowAnalytics && $canViewDashboardAnalytics) {
                return true;
            }

            return false;
        };

        $dashboardBranchContext = $dashboardBranchContext ?? [];
        $dashboardBranchQuery = $dashboardBranchQuery ?? [];
        $dashboardBranches = $dashboardBranchContext['branches'] ?? collect();
        $canSelectDashboardBranch = (bool) ($dashboardBranchContext['canSelect'] ?? false);
        $selectedDashboardBranchId = $dashboardBranchContext['selectedBranchId'] ?? null;
        $withDashboardBranch = static function (array $params = []) use ($dashboardBranchQuery): array {
            return array_merge($params, $dashboardBranchQuery);
        };
        $contractIndexUrl = static function (array $params = []) use ($withDashboardBranch): string {
            return route('RecruitmentContract.index', $withDashboardBranch($params));
        };

        $canViewRecruitmentOrdersCard = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_RECRUITMENT_ORDERS_COUNT,
            [\App\PermKeys::DASHBOARD_CURRENT_ORDERS_COUNT],
            true,
        );
        $canViewRecruitmentContractsCard = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_RECRUITMENT_CONTRACTS_COUNT,
            [\App\PermKeys::DASHBOARD_ORDERS_STATISTICS],
            true,
        );
        $canViewCvCard = $canViewDashboardSection(\App\PermKeys::DASHBOARD_CV_COUNT, [], true);
        $canViewClientsCard = $canViewDashboardSection(\App\PermKeys::DASHBOARD_CLIENTS_COUNT, [], true);
        $canViewRentalContractsCard = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_RENTAL_CONTRACTS_COUNT,
            [\App\PermKeys::RENT_CONTRACT_VIEW],
            true,
        );
        $canViewRentalMaidsCard = $canViewDashboardSection(\App\PermKeys::MAIDS_RENTAL_VIEW, [], true);
        $canViewRequestGenderCard = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_ORDERS_TYPES,
            [\App\PermKeys::INGAZ_DELEGATIONS_VIEW],
            true,
        );
        $canViewTopStatsRow =
            $canViewRecruitmentOrdersCard ||
            $canViewRecruitmentContractsCard ||
            $canViewCvCard ||
            $canViewRentalContractsCard ||
            $canViewClientsCard ||
            $canViewRentalMaidsCard;

        $canViewIngazDelegationsCard = $canViewDashboardSection(
            null,
            [\App\PermKeys::INGAZ_DELEGATIONS_VIEW, \App\PermKeys::INGAZ_DELEGATIONS_CONTROL],
            true,
        );
        $canViewComplaintsCard = $canViewDashboardSection(
            \App\PermKeys::COMPLAINT_VIEW,
            [\App\PermKeys::COMPLAINT_CONTROL],
            true,
        );
        $canViewSponsorshipTransfersCard = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_SPONSORSHIP_TRANSFERS_COUNT,
            [\App\PermKeys::SPONSOR_TRANSFER_VIEW],
            true,
        );
        $canViewShelterCard = $canViewDashboardSection(\App\PermKeys::DASHBOARD_SHELTER_COUNT, [], true);
        $canViewTopStatsRow = $canViewTopStatsRow ||
            $canViewIngazDelegationsCard ||
            $canViewComplaintsCard ||
            $canViewSponsorshipTransfersCard ||
            $canViewShelterCard;

        $canViewOperationsSection = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_OPERATIONS_OVERVIEW,
            [\App\PermKeys::DASHBOARD_ORDERS_STATISTICS],
            false,
            true,
        );
        $canViewContractsOverviewSection = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_CONTRACTS_OVERVIEW,
            [],
            false,
            true,
        );
        $canViewNationalitiesPanel = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_NATIONALITIES_STATISTICS,
            [\App\PermKeys::DASHBOARD_ORDERS_TYPES],
            false,
            true,
        );
        $canViewOfficesPanel = $canViewDashboardSection(\App\PermKeys::DASHBOARD_OFFICES_STATISTICS, [], false, true);
        $canViewRecentActivitiesPanel = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_RECENT_ACTIVITIES,
            [\App\PermKeys::DASHBOARD_TOP_CUSTOMER_SERVICES],
            false,
            true,
        );
        $canViewAnalyticsSummaryRow =
            $canViewNationalitiesPanel ||
            $canViewRequestGenderCard ||
            $canViewOfficesPanel ||
            $canViewRecentActivitiesPanel;

        $canViewCalendarPanel = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_CALENDAR_EVENTS,
            [\App\PermKeys::TRAVEL_VIEW],
            false,
            true,
        );
        $canViewEmployeePerformancePanel = $canViewDashboardSection(
            \App\PermKeys::DASHBOARD_EMPLOYEE_PERFORMANCE,
            [\App\PermKeys::DASHBOARD_EMPLOYEES_COUNT],
            false,
            true,
        );
        $canViewBottomRow = $canViewCalendarPanel || $canViewEmployeePerformancePanel;

        $hasAnyDashboardSection =
            $canViewTopStatsRow ||
            $canViewOperationsSection ||
            $canViewContractsOverviewSection ||
            $canViewAnalyticsSummaryRow ||
            $canViewBottomRow;
        $hasDashboardAccess = $canViewDashboard || $hasAnyDashboardSection;
    @endphp

    @if ($hasDashboardAccess)
        <div class="dashboard-hero surface-card">
            <div class="hero-main">
                <p class="hero-eyebrow">{{ __('admin.dashboard_new.instant_indicators') }}</p>
                <h1>{{ __('admin.dashboard_new.welcome_user', ['name' => auth()->guard('admin')->user()->name ?? '']) }} 👋</h1>
                <p>{{ __('admin.dashboard_new.hero_description') }}</p>
                <div class="hero-meta">
                    <span class="live-pill">{{ __('admin.dashboard_new.updated_now') }}</span>
                    <span>{{ __('admin.dashboard_new.last_update', ['date' => now()->translatedFormat('d F Y - H:i')]) }}</span>
                </div>
                <div class="hero-quick-actions">
                    <a href="{{ route('RecruitmentContract.create') }}"><i class="fas fa-plus-circle ms-1"></i> {{ __('admin.dashboard_new.new_contract') }}</a>
                    <a href="{{ route('orders.index', $dashboardBranchQuery) }}"><i class="fas fa-shopping-basket ms-1"></i> {{ __('admin.dashboard_new.direct_orders') }}</a>
                    <a href="{{ route('admins.index', $dashboardBranchQuery) }}"><i class="fas fa-users-cog ms-1"></i> {{ __('admin.dashboard_new.team_management') }}</a>
                    <a href="{{ route('reports.index', $dashboardBranchQuery) }}"><i class="fas fa-chart-pie ms-1"></i> {{ __('admin.dashboard_new.reports') }}</a>
                    <a href="{{ route('finance.home.index', $dashboardBranchQuery) }}"><i class="fas fa-wallet ms-1"></i> {{ __('admin.dashboard_new.finance') }}</a>
                </div>
            </div>
            <div class="hero-controls">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small>{{ __('admin.dashboard_new.select_period') }}</small>
                        <h5 class="mb-0">{{ __('admin.dashboard_new.refresh_values') }}</h5>
                    </div>
                    <i class="fas fa-sliders-h"></i>
                </div>
                @if ($canSelectDashboardBranch)
                    <form method="GET" action="{{ route('dashboard.index') }}" class="mt-3">
                        @foreach (['contract_nationality_id', 'contract_profession_id'] as $dashboardFilterKey)
                            @if (request()->filled($dashboardFilterKey))
                                <input type="hidden" name="{{ $dashboardFilterKey }}" value="{{ request($dashboardFilterKey) }}">
                            @endif
                        @endforeach
                        <label for="dashboardBranchFilter" class="form-label mb-1">{{ __('admin.branches') }}</label>
                        <select id="dashboardBranchFilter" name="branch_id" class="form-select" onchange="this.form.submit()">
                            <option value="">كل الفروع</option>
                            @foreach ($dashboardBranches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $selectedDashboardBranchId === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <div class="range-switch" role="group" aria-label="{{ __('admin.dashboard_new.stats_filter_period') }}">
                    <button class="range-btn active" data-range="today">{{ __('admin.dashboard_new.today') }}</button>
                    <button class="range-btn" data-range="week">{{ __('admin.dashboard_new.this_week') }}</button>
                    <button class="range-btn" data-range="month">{{ __('admin.dashboard_new.this_month') }}</button>
                    <button class="range-btn" data-range="total">{{ __('admin.dashboard_new.total') }}</button>
                </div>
                <div class="hero-micro-grid">
                    <div>
                        <small data-range-label data-today="{{ __('admin.dashboard_new.today_orders') }}" data-week="{{ __('admin.dashboard_new.week_orders') }}"
                            data-month="{{ __('admin.dashboard_new.month_orders') }}" data-total="{{ __('admin.dashboard_new.total_orders') }}">{{ __('admin.dashboard_new.today_orders') }}</small>
                        <strong data-range-value data-range-format="number" data-today="{{ $ordersStats['today'] ?? 0 }}"
                            data-week="{{ $ordersStats['thisWeek'] ?? 0 }}"
                            data-month="{{ $ordersStats['thisMonth'] ?? 0 }}"
                            data-total="{{ $ordersStats['total'] ?? 0 }}">{{ number_format($ordersStats['today'] ?? 0) }}</strong>
                    </div>
                    <div>
                        <small data-range-label data-today="{{ __('admin.dashboard_new.today_contracts') }}" data-week="{{ __('admin.dashboard_new.week_contracts') }}"
                            data-month="{{ __('admin.dashboard_new.month_contracts') }}" data-total="{{ __('admin.dashboard_new.month_contracts') }}">{{ __('admin.dashboard_new.today_contracts') }}</small>
                        <strong data-range-value data-range-format="number"
                            data-today="{{ $contractsStats['today'] ?? 0 }}"
                            data-week="{{ $contractsStats['thisWeek'] ?? 0 }}"
                            data-month="{{ $contractsStats['thisMonth'] ?? 0 }}"
                            data-total="{{ $contractsStats['total'] ?? 0 }}">{{ number_format($contractsStats['today'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @if (!$hasAnyDashboardSection)
            @include('components.no_permissions')
        @endif

        <div class="dashboard-route-grid">
            <a href="{{ route('reports.index', $dashboardBranchQuery) }}" class="dashboard-route-card rc-1">
                <span class="dashboard-route-icon"><i class="fas fa-chart-line"></i></span>
                <span>
                    <strong>{{ __('admin.dashboard_new.reports_center') }}</strong>
                    <small>{{ __('admin.dashboard_new.reports_center_hint') }}</small>
                </span>
                <i class="fas fa-arrow-left"></i>
            </a>
            <a href="{{ route('finance.home.index', $dashboardBranchQuery) }}" class="dashboard-route-card rc-2">
                <span class="dashboard-route-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <span>
                    <strong>{{ __('admin.dashboard_new.finance_dashboard') }}</strong>
                    <small>{{ __('admin.dashboard_new.finance_dashboard_hint') }}</small>
                </span>
                <i class="fas fa-arrow-left"></i>
            </a>
            <a href="{{ route('ShelterContract.index', $dashboardBranchQuery) }}" class="dashboard-route-card rc-3">
                <span class="dashboard-route-icon"><i class="fas fa-house-user"></i></span>
                <span>
                    <strong>{{ __('admin.dashboard_new.shelter_management') }}</strong>
                    <small>{{ __('admin.dashboard_new.shelter_management_hint') }}</small>
                </span>
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        @if ($canViewTopStatsRow)
            <div class="stat-row">
                @if ($canViewRecruitmentContractsCard)
                    <div class="stat-card is-1" style="order: 1;">
                        <div class="stat-card-icon"><i class="fas fa-handshake"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.recruitment_contracts') }}</small>
                                <h3 class="mb-0">{{ number_format($contractsStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('RecruitmentContract.index', $dashboardBranchQuery) }}"
                                class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="mt-3 stat-meta">{{ __('admin.dashboard_new.pre_arrival') }}: {{ number_format($contractsPipeline['pre_arrival'] ?? 0) }}
                            |
                            {{ __('admin.dashboard_new.arrival_stage') }}: {{ number_format($contractsPipeline['arrival_stage'] ?? 0) }} | {{ __('admin.dashboard_new.under_warranty') }}:
                            {{ number_format($contractsPipeline['under_warranty'] ?? 0) }}</div>
                        <div class="progress mt-2" style="height:6px;">
                            @php $ctTotal = max($contractsStats['total'] ?? 1, 1); @endphp
                            <div class="progress-bar bg-success"
                                style="width: {{ round((($contractsStats['completed'] ?? 0) / $ctTotal) * 100) }}%"></div>
                            <div class="progress-bar bg-info"
                                style="width: {{ round((($contractsPipeline['pre_arrival'] ?? 0) / $ctTotal) * 100) }}%">
                            </div>
                        </div>
                    </div>
                @endif

                @if ($canViewRecruitmentOrdersCard)
                    <div class="stat-card is-2" style="order: 2;">
                        <div class="stat-card-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.recruitment_orders') }}</small>
                                <h3 class="mb-0">{{ number_format($ordersStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('orders.index', $dashboardBranchQuery) }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="stat-chip-list mt-3">
                            <a href="{{ route('orders.index', $withDashboardBranch(['status' => \App\Enums\ingaz\OrderEnum::New->value])) }}">
                                {{ __('admin.dashboard_new.new') }}: {{ number_format($ordersStats['new'] ?? 0) }}
                            </a>
                            <a href="{{ route('orders.index', $withDashboardBranch(['status' => \App\Enums\ingaz\OrderEnum::UnderProcess->value])) }}">
                                {{ __('admin.dashboard_new.under_process') }}: {{ number_format($ordersStats['under_process'] ?? 0) }}
                            </a>
                            <a href="{{ route('orders.index', $withDashboardBranch(['status' => 'completed'])) }}">
                                {{ __('admin.dashboard_new.completed') }}: {{ number_format($ordersStats['completed'] ?? 0) }}
                            </a>
                            <a href="{{ route('orders.index', $withDashboardBranch(['status' => 'not_completed'])) }}">
                                {{ __('admin.dashboard_new.canceled') }}: {{ number_format($ordersStats['canceled'] ?? 0) }}
                            </a>
                        </div>
                        <div class="progress mt-2" style="height:6px;">
                            @php $orderTotal = max($ordersStats['total'] ?? 1, 1); @endphp
                            <div class="progress-bar bg-success"
                                style="width: {{ round((($ordersStats['contracted'] ?? 0) / $orderTotal) * 100) }}%"></div>
                            <div class="progress-bar bg-warning"
                                style="width: {{ round((($ordersStats['under_process'] ?? 0) / $orderTotal) * 100) }}%">
                            </div>
                        </div>
                    </div>
                @endif

                @if ($canViewCvCard)
                    <div class="stat-card is-3" style="order: 5;">
                        <div class="stat-card-icon"><i class="fas fa-id-card-alt"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.cvs') }}</small>
                                <h3 class="mb-0">{{ number_format($cvStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ url('admin/maids') }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="mt-3 stat-meta">{{ __('admin.dashboard_new.available') }}: {{ number_format($cvStats['available'] ?? 0) }} | {{ __('admin.dashboard_new.booked') }}:
                            {{ number_format($cvStats['booked'] ?? 0) }}</div>
                        <div class="progress mt-2" style="height:6px;">
                            @php $cvTotal = max($cvStats['total'] ?? 1, 1); @endphp
                            <div class="progress-bar bg-success"
                                style="width: {{ round((($cvStats['available'] ?? 0) / $cvTotal) * 100) }}%"></div>
                            <div class="progress-bar bg-warning"
                                style="width: {{ round((($cvStats['booked'] ?? 0) / $cvTotal) * 100) }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($canViewRentalContractsCard)
                    <div class="stat-card is-3" style="order: 3;">
                        <div class="stat-card-icon"><i class="fas fa-home"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.rental_contracts') }}</small>
                                <h3 class="mb-0">{{ number_format($rentalStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('RentContract.index', $dashboardBranchQuery) }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="stat-chip-list mt-3">
                            <span>{{ __('admin.dashboard_new.active') }}: {{ number_format($rentalStats['active'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.sent') }}: {{ number_format($rentalStats['sent'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.signed') }}: {{ number_format($rentalStats['signed'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.delivered') }}: {{ number_format($rentalStats['delivered'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.completed') }}: {{ number_format($rentalStats['completed'] ?? 0) }}</span>
                        </div>
                    </div>
                @endif

                @if ($canViewClientsCard)
                    <div class="stat-card is-4" style="order: 6;">
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.clients') }}</small>
                                <h3 class="mb-0">{{ number_format($clientsStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('clients.index', $dashboardBranchQuery) }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="mt-3 stat-meta">{{ __('admin.dashboard_new.with_orders') }}: {{ number_format($clientsStats['with_orders'] ?? 0) }} |
                            {{ __('admin.dashboard_new.without_orders') }}: {{ number_format($clientsStats['no_orders'] ?? 0) }}</div>
                        <div class="progress mt-2" style="height:6px;">
                            @php $clTotal = max($clientsStats['total'] ?? 1, 1); @endphp
                            <div class="progress-bar bg-success"
                                style="width: {{ round((($clientsStats['with_orders'] ?? 0) / $clTotal) * 100) }}%"></div>
                            <div class="progress-bar bg-secondary"
                                style="width: {{ round((($clientsStats['no_orders'] ?? 0) / $clTotal) * 100) }}%"></div>
                        </div>
                    </div>
                @endif

                @if ($canViewRequestGenderCard)
                    <div class="stat-card gender-chart-card is-3" style="order: 7;">
                        <div class="stat-card-icon"><i class="fas fa-venus-mars"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.requests_by_gender') }}</small>
                                <h3 class="mb-0">{{ number_format($requestGenderStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('orders.index', $dashboardBranchQuery) }}"
                                class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="gender-chart-layout mt-3">
                            <div class="gender-chart-wrap">
                                <canvas id="requestGenderChart"
                                    aria-label="{{ __('admin.dashboard_new.requests_by_gender') }}" role="img"></canvas>
                            </div>
                            <div class="gender-chart-legend">
                                <span><i class="gender-dot is-male"></i>{{ __('admin.dashboard_new.male') }} <strong>{{ number_format($requestGenderStats['male'] ?? 0) }}</strong></span>
                                <span><i class="gender-dot is-female"></i>{{ __('admin.dashboard_new.female') }} <strong>{{ number_format($requestGenderStats['female'] ?? 0) }}</strong></span>
                                <span><i class="gender-dot is-unknown"></i>{{ __('admin.dashboard_new.unspecified') }} <strong>{{ number_format($requestGenderStats['unknown'] ?? 0) }}</strong></span>
                            </div>
                        </div>
                        <a href="{{ route('ingaz-delegations.index', $dashboardBranchQuery) }}" class="stat-meta stat-link d-inline-block mt-2">
                            {{ __('admin.dashboard_new.includes_ingaz_delegations') }} <i class="fas fa-arrow-left ms-1"></i>
                        </a>
                    </div>
                @endif

                @if ($canViewRentalMaidsCard)
                    <div class="stat-card is-4" style="order: 4;">
                        <div class="stat-card-icon"><i class="fas fa-female"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.rental_maids') }}</small>
                                <h3 class="mb-0">{{ number_format($rentalMaidStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('maids.rental.index') }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="mt-3 stat-meta">
                            {{ __('admin.dashboard_new.available') }}: {{ number_format($rentalMaidStats['available'] ?? 0) }} |
                            {{ __('admin.dashboard_new.in_rental') }}: {{ number_format($rentalMaidStats['in_rental'] ?? 0) }}
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @php

            $statusCounts = $contractStatusBreakdown ?? [];
            $insights = $operationsInsights ?? [];

            $arrivalTodayCount = $insights['arrival_today'] ?? ($contractsPipeline['arrival_stage'] ?? 0);
            $delayedWithoutTickets = $insights['delayed_no_tickets'] ?? ($contractsPipeline['delayed_arrival'] ?? 0);
            $expiredLongTerm = $insights['expired_over_2y'] ?? ($contractsPipeline['expired_over_2y'] ?? 0);
            $warrantyActive = $insights['warranty_active'] ?? ($contractsStats['underWarranty'] ?? 0);
            $ticketsReady =
                $insights['tickets_ready'] ?? ($statusCounts[\App\Enums\ingaz\ContractStatusEnum::Tazker->value] ?? 0);
            $completedContractsCount = $insights['completed'] ?? ($contractsStats['completed'] ?? 0);

            $operationsHighlights = [
                [
                    'title' => __('admin.dashboard_new.new_contracts'),
                    'subtitle' => __('admin.dashboard_new.first_operation_stage'),
                    'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                    'meta' => __('admin.dashboard_new.today') . ': ' . number_format($contractsStats['today'] ?? 0),
                    'video' => asset('admin/assets/img/new-dashboard/contracted.webm'),
                    'icon' => 'fas fa-file-signature',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::New->value,
                    ]),
                ],
                [
                    'title' => __('admin.dashboard_new.arrival_stage'),
                    'subtitle' => __('admin.dashboard_new.tickets_ready'),
                    'value' => $arrivalTodayCount,
                    'meta' => __('admin.dashboard_new.tickets_ready') . ': ' . number_format($ticketsReady),
                    'video' => asset('admin/assets/img/new-dashboard/daily-arraivel.webm'),
                    'icon' => 'fas fa-plane-arrival',
                    'link' => $contractIndexUrl(['status' => 'arrival_today']),
                ],
                [
                    'title' => __('admin.dashboard_new.delayed_arrival'),
                    'subtitle' => __('admin.dashboard_new.delayed_arrival_hint'),
                    'value' => $delayedWithoutTickets,
                    'meta' => __('admin.dashboard_new.urgent_followup_required'),
                    'video' => asset('admin/assets/img/new-dashboard/ezgif.com-resize.webm'),
                    'icon' => 'fas fa-triangle-exclamation',
                    'link' => $contractIndexUrl(['status' => 'delayed_arrival']),
                ],
                [
                    'title' => __('admin.dashboard_new.expired_contracts'),
                    'subtitle' => __('admin.dashboard_new.older_than_two_years'),
                    'value' => $expiredLongTerm,
                    'meta' =>
                        __('admin.dashboard_new.under_warranty') . ' ' .
                        number_format($warrantyActive) .
                        ' | ' . __('admin.dashboard_new.completed') . ' ' .
                        number_format($completedContractsCount),
                    'video' => asset('admin/assets/img/new-dashboard/ended-contracted.webm'),
                    'icon' => 'fas fa-hourglass-end',
                    'link' => $contractIndexUrl(['status' => 'expired_over_2y']),
                ],
            ];

            $operationsLeft = [
                [
                    'title' => __('admin.dashboard_new.new_contracts'),
                    'subtitle' => __('ingaz.new'),
                    'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                    'icon' => 'fas fa-file-signature',
                    'color' => 'var(--dash-chart-1)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::New->value,
                    ]),
                ],
                [
                    'title' => __('ingaz.msaned'),
                    'subtitle' => __('admin.dashboard_new.after_new_contract_approval'),
                    'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Msaned->value] ?? 0,
                    'icon' => 'fas fa-folder-open',
                    'color' => 'var(--dash-chart-2)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Msaned->value,
                    ]),
                ],
                [
                    'title' => __('ingaz.tafwed'),
                    'subtitle' => __('admin.dashboard_new.authorization_steps'),
                    'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Tafwed->value] ?? 0,
                    'icon' => 'fas fa-key',
                    'color' => 'var(--dash-chart-3)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Tafwed->value,
                    ]),
                ],
                [
                    'title' => __('ingaz.taffez'),
                    'subtitle' => __('admin.dashboard_new.visa_preparation'),
                    'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Taffez->value] ?? 0,
                    'icon' => 'fas fa-check-double',
                    'color' => 'var(--dash-chart-4)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Taffez->value,
                    ]),
                ],
            ];

            $operationsRight = [
                [
                    'title' => __('ingaz.tazker'),
                    'subtitle' => __('admin.dashboard_new.approved_tickets'),
                    'value' => $ticketsReady,
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'var(--dash-chart-5)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Tazker->value,
                    ]),
                ],
                [
                    'title' => __('admin.dashboard_new.under_warranty'),
                    'subtitle' => __('admin.dashboard_new.last_90_days'),
                    'value' => $warrantyActive,
                    'icon' => 'fas fa-shield-alt',
                    'color' => 'var(--warning)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Completed->value,
                        'warranty_status' => 'active',
                    ]),
                ],
                [
                    'title' => __('admin.dashboard_new.completed_contracts'),
                    'subtitle' => __('admin.dashboard_new.final_total'),
                    'value' => $completedContractsCount,
                    'icon' => 'fas fa-check-circle',
                    'color' => 'var(--success)',
                    'link' => $contractIndexUrl([
                        'status' => \App\Enums\ingaz\ContractStatusEnum::Completed->value,
                    ]),
                ],
            ];

            $operationsTotal = max(
                1,
                array_reduce(
                    array_merge($operationsLeft, $operationsRight),
                    function ($carry, $row) {
                        return $carry + ($row['value'] ?? 0);
                    },
                    0,
                ),
            );
        @endphp

        @if ($canViewOperationsSection)
            <div class="data-section" id="operations">
                <div class="section-head">
                    <div>
                        <p class="section-eyebrow">{{ __('admin.dashboard_new.operations') }}</p>
                        <h3 class="mb-0">{{ __('admin.dashboard_new.quick_operations_indicators') }}</h3>
                    </div>
                    <button class="section-toggle" data-toggle-section="operations" aria-expanded="true">
                        <i class="fas fa-eye"></i> {{ __('admin.dashboard_new.collapse_show') }}
                    </button>
                </div>
                <div class="section-body operations-showcase" data-section-body="operations">
                    <div class="operations-highlight-grid">
                        @foreach ($operationsHighlights as $index => $highlight)
                            <a href="{{ $highlight['link'] ?? route('RecruitmentContract.index', $dashboardBranchQuery) }}"
                                class="operation-tile op-tile-{{ $index }} operation-link">
                                <div class="operation-icon">
                                    {{-- <video src="{{ $highlight['video'] }}" autoplay muted loop playsinline></video> --}}
                                    <i class="{{ $highlight['icon'] ?? 'fas fa-layer-group' }}"></i>
                                </div>
                                <p class="operation-subtitle">{{ $highlight['subtitle'] }}</p>
                                <p class="operation-value">{{ number_format($highlight['value']) }}</p>
                                <div class="operation-foot">
                                    <span>{{ $highlight['title'] }}</span>
                                    <span>{{ $highlight['meta'] }}</span>
                                </div>
                                <span class="operation-cta">{{ __('admin.dashboard_new.show_details') }} <i class="fas fa-arrow-left ms-1"></i></span>
                            </a>
                        @endforeach
                    </div>

                    <div class="operations-detail-grid">
                        <div class="operations-column">
                            <div class="operations-column-head">
                                <strong>{{ __('admin.dashboard_new.before_arrival_stages') }}</strong>
                                <small>{{ __('admin.dashboard_new.before_arrival_stages_hint') }}</small>
                            </div>
                            @foreach ($operationsLeft as $card)
                                @php
                                    $cardShare = $operationsTotal
                                        ? round((($card['value'] ?? 0) / $operationsTotal) * 100)
                                        : 0;
                                @endphp
                                <a href="{{ $card['link'] ?? route('RecruitmentContract.index', $dashboardBranchQuery) }}"
                                    class="mini-operation-card mini-operation-link">
                                    <div class="mini-operation-main">
                                        <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </span>
                                        <div class="mini-operation-text">
                                            <strong>{{ $card['title'] }}</strong>
                                            <small>{{ $card['subtitle'] }}</small>
                                        </div>
                                        <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                                    </div>
                                    <div class="mini-operation-meter">
                                        <small>{{ $cardShare }}% {{ __('admin.dashboard_new.from_operations_indicators') }}</small>
                                        <div class="stage-progress">
                                            <span
                                                style="width: {{ $cardShare }}%; background: {{ $card['color'] }};"></span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="operations-column">
                            <div class="operations-column-head">
                                <strong>{{ __('admin.dashboard_new.after_arrival_stages') }}</strong>
                                <small>{{ __('admin.dashboard_new.after_arrival_stages_hint') }}</small>
                            </div>
                            @foreach ($operationsRight as $card)
                                @php
                                    $cardShare = $operationsTotal
                                        ? round((($card['value'] ?? 0) / $operationsTotal) * 100)
                                        : 0;
                                @endphp
                                <a href="{{ $card['link'] ?? route('RecruitmentContract.index', $dashboardBranchQuery) }}"
                                    class="mini-operation-card mini-operation-link">
                                    <div class="mini-operation-main">
                                        <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </span>
                                        <div class="mini-operation-text">
                                            <strong>{{ $card['title'] }}</strong>
                                            <small>{{ $card['subtitle'] }}</small>
                                        </div>
                                        <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                                    </div>
                                    <div class="mini-operation-meter">
                                        <small>{{ $cardShare }}% {{ __('admin.dashboard_new.from_operations_indicators') }}</small>
                                        <div class="stage-progress">
                                            <span
                                                style="width: {{ $cardShare }}%; background: {{ $card['color'] }};"></span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($canViewSecondStatsRow)
            <div class="stat-row mt-3">
                @if ($canViewIngazDelegationsCard)
                    <div class="stat-card is-5">
                        <div class="stat-card-icon"><i class="fas fa-file-signature"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.ingaz_delegations') }}</small>
                                <h3 class="mb-0">{{ number_format($delegationStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('ingaz-delegations.index', $dashboardBranchQuery) }}"
                                class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="stat-chip-list mt-3">
                            <span>{{ __('admin.dashboard_new.under_process') }}: {{ number_format($delegationStats['under_process'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.executed') }}: {{ number_format($delegationStats['executed'] ?? 0) }}</span>
                        </div>
                    </div>
                @endif

                @if ($canViewComplaintsCard)
                    <div class="stat-card is-4">
                        <div class="stat-card-icon"><i class="fas fa-headset"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.complaints') }}</small>
                                <h3 class="mb-0">{{ number_format($complaintStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('complaints.index', $dashboardBranchQuery) }}"
                                class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="stat-chip-list mt-3">
                            <span>{{ __('admin.dashboard_new.total') }}: {{ number_format($complaintStats['total'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.open') }}: {{ number_format($complaintStats['open'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.closed') }}: {{ number_format($complaintStats['closed'] ?? 0) }}</span>
                        </div>
                    </div>
                @endif

                @if ($canViewSponsorshipTransfersCard)
                    <div class="stat-card is-2">
                        <div class="stat-card-icon"><i class="fas fa-exchange-alt"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.sponsorship_transfers') }}</small>
                                <h3 class="mb-0">{{ number_format($transferStats['total'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('SponsorshipTransfer.index') }}"
                                class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="mt-3 stat-meta">{{ __('admin.dashboard_new.pending_review') }}: {{ number_format($transferStats['pending'] ?? 0) }} |
                            {{ __('admin.dashboard_new.approved') }}:
                            {{ number_format($transferStats['approved'] ?? 0) }} | {{ __('admin.dashboard_new.transferred') }}:
                            {{ number_format($transferStats['transferred'] ?? 0) }}</div>
                    </div>
                @endif

                @if ($canViewShelterCard)
                    <div class="stat-card is-7">
                        <div class="stat-card-icon"><i class="fas fa-house-user"></i></div>
                        <div class="stat-head d-flex justify-content-between align-items-center">
                            <div>
                                <small class="stat-label">{{ __('admin.dashboard_new.inside_shelter') }}</small>
                                <h3 class="mb-0">{{ number_format($shelterStats['inside'] ?? 0) }}</h3>
                            </div>
                            <a href="{{ route('ShelterContract.index', $dashboardBranchQuery) }}" class="btn btn-sm btn-light text-dark">{{ __('admin.dashboard_new.view') }}</a>
                        </div>
                        <div class="stat-chip-list mt-3">
                            <span>{{ __('admin.dashboard_new.inside_shelter') }}: {{ number_format($shelterStats['inside'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.outside_shelter') }}: {{ number_format($shelterStats['outside'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.ready_for_transfer') }}: {{ number_format($shelterStats['available_for_transfer'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.deportation_stage') }}: {{ number_format($shelterStats['deportation_stage'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.deported') }}: {{ number_format($shelterStats['deported'] ?? 0) }}</span>
                            <span>{{ __('admin.dashboard_new.places') }}: {{ number_format($shelterStats['places'] ?? 0) }}</span>
                        </div>
                        <a href="{{ route('ShelterContract.outside') }}" class="stat-meta stat-link d-inline-block mt-2">
                            {{ __('admin.dashboard_new.follow_shelter_exit') }} <i class="fas fa-arrow-left ms-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        @php
            $analyticsStats = $contractAnalyticsStats ?? [];
            $analyticsPipeline = $contractAnalyticsPipeline ?? [];
            $analyticsStatusCounts = $contractAnalyticsStatusBreakdown ?? [];
            $threeYearSeries = collect($contractsYearSeries ?? []);
            $currentYearSeries = $threeYearSeries->last();
            $contractsThreeYearsDisplayedTotal = $contractsThreeYearsTotal ?? (int) $threeYearSeries->sum('total');
            $missingContractDateCount = (int) ($analyticsStats['missingContractDate'] ?? 0);
            $professionStats = collect($contractProfessionStats ?? []);
            $professionSummary = $contractProfessionSummary ?? [];
            $contractOverviewCards = [
                [
                    'label' => __('admin.dashboard_new.contracts_total'),
                    'value' => $analyticsStats['total'] ?? 0,
                    'meta' =>
                        $contractFiltersApplied ?? false ? __('admin.dashboard_new.filtered_contracts') : __('admin.dashboard_new.all_registered_contracts'),
                    'color' => 'var(--dash-chart-1)',
                ],
                [
                    'label' => __('admin.dashboard_new.contracts_year', ['year' => ($currentYearSeries['year'] ?? now()->year)]),
                    'value' => $currentYearSeries['total'] ?? 0,
                    'meta' => __('admin.dashboard_new.by_contract_date_only'),
                    'color' => 'var(--dash-chart-2)',
                ],
                [
                    'label' => __('admin.dashboard_new.month_contracts'),
                    'value' => $analyticsStats['thisMonth'] ?? 0,
                    'meta' => __('admin.dashboard_new.by_contract_date_only'),
                    'color' => 'var(--success)',
                ],
                [
                    'label' => __('admin.dashboard_new.completed_contracts'),
                    'value' => $analyticsStats['completed'] ?? 0,
                    'meta' => __('admin.dashboard_new.arrival_completed_like_contracts'),
                    'color' => 'var(--warning)',
                ],
            ];
            $contractStageRows = [
                [
                    'label' => __('admin.dashboard_new.new_contracts'),
                    'value' =>
                        $analyticsStatusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ??
                        ($analyticsPipeline['pre_arrival'] ?? 0),
                    'meta' => __('admin.dashboard_new.first_stage_before_musaned'),
                    'color' => 'var(--dash-chart-1)',
                ],
                [
                    'label' => __('admin.dashboard_new.arrival_stage'),
                    'value' => $analyticsPipeline['arrival_stage'] ?? 0,
                    'meta' => __('admin.dashboard_new.confirmed_tickets_on_way'),
                    'color' => 'var(--dash-chart-2)',
                ],
                [
                    'label' => __('admin.dashboard_new.under_warranty'),
                    'value' => $analyticsPipeline['under_warranty'] ?? 0,
                    'meta' => __('admin.dashboard_new.by_warranty_end_date'),
                    'color' => 'var(--warning)',
                ],
                [
                    'label' => __('admin.dashboard_new.completed'),
                    'value' => $analyticsPipeline['completed'] ?? 0,
                    'meta' => __('admin.dashboard_new.arrival_completed'),
                    'color' => 'var(--success)',
                ],
                [
                    'label' => __('admin.dashboard_new.delayed_arrival'),
                    'value' => $analyticsPipeline['delayed_arrival'] ?? 0,
                    'meta' => __('admin.dashboard_new.delayed_arrival_hint'),
                    'color' => 'var(--dash-chart-5)',
                ],
                [
                    'label' => __('admin.dashboard_new.expired_contracts_over_two_years'),
                    'value' => $analyticsPipeline['expired_over_2y'] ?? 0,
                    'meta' => __('admin.dashboard_new.archive_review'),
                    'color' => 'var(--danger)',
                ],
            ];
            $contractStagesRawTotal = array_reduce(
                $contractStageRows,
                function ($carry, $row) {
                    return $carry + ($row['value'] ?? 0);
                },
                0,
            );
            $contractStagesTotal = max(1, $contractStagesRawTotal);
            $professionPalette = [
                'var(--dash-chart-1)',
                'var(--dash-chart-2)',
                'var(--dash-chart-3)',
                'var(--dash-chart-4)',
                'var(--dash-chart-5)',
                'var(--success)',
                'var(--warning)',
            ];
        @endphp

        @if ($canViewContractsOverviewSection)
            <div id="contracts-overview" class="surface-card mt-3 contracts-overview-section">
                <div class="contract-section-header">
                    <div class="contract-section-heading">
                        <p class="section-eyebrow">{{ __('admin.dashboard_new.contracts') }}</p>
                        <h4 class="mb-0">{{ __('admin.dashboard_new.contracts_total') }}</h4>
                        <small>{{ __('admin.dashboard_new.contracts_analytics_hint') }}</small>
                    </div>
                    <div class="contract-section-summary">
                        <span class="contract-summary-pill">{{ __('admin.dashboard_new.last_three_years') }}:
                            {{ number_format($contractsThreeYearsDisplayedTotal) }} {{ __('admin.dashboard_new.contract') }}</span>
                        <span class="contract-summary-pill">{{ __('admin.dashboard_new.today_contracts_short') }}:
                            {{ number_format($analyticsStats['today'] ?? 0) }}</span>
                        @if ($missingContractDateCount > 0)
                            <span class="contract-summary-pill">{{ __('admin.dashboard_new.missing_contract_date') }}:
                                {{ number_format($missingContractDateCount) }}</span>
                        @endif
                    </div>
                </div>
                <div class="contract-section-body">
                    <form class="contract-filter-form" method="GET"
                        action="{{ route('dashboard.index') }}#contracts-overview">
                        <div class="contract-filter-grid">
                            <label class="contract-filter-field">
                                <span>{{ __('admin.dashboard_new.nationality') }}</span>
                                <select name="contract_nationality_id">
                                    <option value="">{{ __('admin.dashboard_new.all_nationalities') }}</option>
                                    @foreach ($contractNationalityOptions ?? [] as $nationalityOption)
                                        <option value="{{ $nationalityOption['id'] }}"
                                            {{ (int) ($selectedContractNationalityId ?? 0) === (int) $nationalityOption['id'] ? 'selected' : '' }}>
                                            {{ $nationalityOption['title'] }}
                                            ({{ number_format($nationalityOption['total']) }})
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="contract-filter-field">
                                <span>{{ __('admin.dashboard_new.profession') }}</span>
                                <select name="contract_profession_id">
                                    <option value="">{{ __('admin.dashboard_new.all_professions') }}</option>
                                    @foreach ($contractProfessionOptions ?? [] as $professionOption)
                                        <option value="{{ $professionOption['id'] }}"
                                            {{ (int) ($selectedContractProfessionId ?? 0) === (int) $professionOption['id'] ? 'selected' : '' }}>
                                            {{ $professionOption['title'] }}
                                            ({{ number_format($professionOption['total']) }})
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <div class="contract-filter-actions">
                                <button type="submit" class="btn btn-primary">{{ __('admin.dashboard_new.apply_filter') }}</button>
                                <a href="{{ route('dashboard.index') }}#contracts-overview"
                                    class="btn btn-outline-secondary">{{ __('admin.dashboard_new.reset') }}</a>
                            </div>
                        </div>

                        @if ($contractFiltersApplied ?? false)
                            <div class="contract-active-filters">
                                <span class="contract-summary-pill">{{ __('admin.dashboard_new.active_filters') }}</span>
                                @if (!empty($selectedContractNationalityTitle))
                                    <span class="contract-summary-pill">{{ __('admin.dashboard_new.nationality') }}:
                                        {{ $selectedContractNationalityTitle }}</span>
                                @endif
                                @if (!empty($selectedContractProfessionTitle))
                                    <span class="contract-summary-pill">{{ __('admin.dashboard_new.profession') }}:
                                        {{ $selectedContractProfessionTitle }}</span>
                                @endif
                                <span class="contract-summary-pill">{{ __('admin.dashboard_new.results') }}:
                                    {{ number_format($analyticsStats['total'] ?? 0) }} {{ __('admin.dashboard_new.contract') }}</span>
                            </div>
                        @endif
                    </form>

                    <div class="contracts-kpi-grid">
                        @foreach ($contractOverviewCards as $overviewCard)
                            <div class="contract-kpi-card" style="--contract-kpi-accent: {{ $overviewCard['color'] }};">
                                <small>{{ $overviewCard['label'] }}</small>
                                <strong>{{ number_format($overviewCard['value']) }}</strong>
                                <span>{{ $overviewCard['meta'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="contracts-layout-grid">
                        <div class="contracts-chart-panel">
                            <div class="contracts-chart-head">
                                <div>
                                    <strong>{{ __('admin.dashboard_new.contracts_movement_three_years') }}</strong>
                                    <small>{{ __('admin.dashboard_new.contracts_movement_three_years_hint') }}</small>
                                </div>
                                <span class="contract-summary-pill">{{ __('admin.dashboard_new.this_week_label') }}:
                                    {{ number_format($analyticsStats['thisWeek'] ?? 0) }}</span>
                            </div>
                            <div class="contracts-chart-wrap">
                                <canvas id="contractsChart"></canvas>
                            </div>
                        </div>

                        <div class="contract-stage-panel">
                            <div class="contract-stage-head">
                                <div class="contract-stage-heading">
                                    <strong>{{ __('admin.dashboard_new.contract_stages') }}</strong>
                                    <small>{{ __('admin.dashboard_new.contract_stages_hint') }}</small>
                                </div>
                                <div class="contract-stage-total">
                                    <small>{{ __('admin.dashboard_new.total_label') }}</small>
                                    <strong>{{ number_format($contractStagesRawTotal) }}</strong>
                                </div>
                            </div>
                            <a href="{{ route('RecruitmentContract.index', $dashboardBranchQuery) }}" class="contract-stage-link">{{ __('admin.dashboard_new.all_contracts') }}</a>
                            <div class="contract-stages mt-3 flex-grow-1">
                                @foreach ($contractStageRows as $stage)
                                    @php
                                        $stageWidth = $contractStagesTotal
                                            ? round(($stage['value'] / $contractStagesTotal) * 100)
                                            : 0;
                                    @endphp
                                    <div class="contract-stage-row">
                                        <header>
                                            <div class="contract-stage-title">
                                                <span class="stage-dot"
                                                    style="background: {{ $stage['color'] }};"></span>
                                                <strong>{{ $stage['label'] }}</strong>
                                            </div>
                                            <span
                                                class="contract-stage-value">{{ number_format($stage['value']) }}</span>
                                        </header>
                                        <div class="contract-stage-meta">
                                            @if (!empty($stage['meta']))
                                                <small>{{ $stage['meta'] }}</small>
                                            @else
                                                <small>{{ __('admin.dashboard_new.no_extra_description') }}</small>
                                            @endif
                                            <small>{{ $stageWidth }}%</small>
                                        </div>
                                        <div class="stage-progress">
                                            <span
                                                style="width: {{ $stageWidth }}%; background: {{ $stage['color'] }};"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="contract-profession-panel">
                        <div class="contract-profession-head">
                            <div>
                                <strong>{{ __('admin.dashboard_new.contracts_by_profession') }}</strong>
                                <small>{{ __('admin.dashboard_new.contracts_by_profession_hint') }}</small>
                            </div>
                            <div class="contract-profession-pills">
                                <span class="contract-profession-pill">{{ __('admin.dashboard_new.observed_professions') }}:
                                    {{ number_format($professionSummary['tracked_professions'] ?? 0) }}</span>
                                @if (!empty($professionSummary['top_profession']))
                                    <span class="contract-profession-pill">{{ __('admin.dashboard_new.top') }}:
                                        {{ $professionSummary['top_profession'] }}</span>
                                @endif
                                <span class="contract-profession-pill">{{ __('admin.dashboard_new.known') }}:
                                    {{ number_format($professionSummary['known_contracts'] ?? 0) }}</span>
                                <span class="contract-profession-pill">{{ __('admin.dashboard_new.undefined') }}:
                                    {{ number_format($professionSummary['unknown_contracts'] ?? 0) }}</span>
                            </div>
                        </div>

                        @if ($professionStats->count())
                            <div class="contract-profession-grid">
                                @foreach ($professionStats as $profession)
                                    @php
                                        $isUnknownProfession = $profession['is_unknown'] ?? false;
                                        $accent = $isUnknownProfession
                                            ? 'var(--danger)'
                                            : $professionPalette[$loop->index % count($professionPalette)];
                                        $shareWidth = max(
                                            min((int) ($profession['share'] ?? 0), 100),
                                            (int) ($profession['total'] ?? 0) > 0 ? 6 : 0,
                                        );
                                    @endphp
                                    <div class="contract-profession-card {{ $isUnknownProfession ? 'is-unknown' : '' }}"
                                        style="--profession-accent: {{ $accent }};">
                                        <div class="contract-profession-card-head">
                                            <span class="contract-profession-icon">
                                                <i class="fas fa-briefcase"></i>
                                            </span>
                                            <span
                                                class="contract-profession-share">{{ number_format((int) ($profession['share'] ?? 0)) }}%</span>
                                        </div>
                                        <strong
                                            class="contract-profession-name">{{ $profession['profession'] ?? __('admin.dashboard_new.undefined') }}</strong>
                                        <div class="contract-profession-total">
                                            {{ number_format((int) ($profession['total'] ?? 0)) }}</div>
                                        <div class="contract-profession-meta">
                                            <span>{{ number_format((int) ($profession['total'] ?? 0)) }} {{ __('admin.dashboard_new.contract') }}</span>
                                            <span>{{ $isUnknownProfession ? __('admin.dashboard_new.needs_profession_link') : __('admin.dashboard_new.from_total_contracts') }}</span>
                                        </div>
                                        <div class="stage-progress contract-profession-progress">
                                            <span
                                                style="width: {{ $shareWidth }}%; background: var(--profession-accent);"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state py-3">{{ __('admin.dashboard_new.no_profession_contracts') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($canViewAnalyticsSummaryRow)
            <div class="row g-4 mt-3">
                @if ($canViewNationalitiesPanel)
                    <div class="col-xxl-4 col-lg-5">
                        <div class="panel-card h-100">
                            <div class="panel-head">
                                <div>
                                    <p class="section-eyebrow">{{ __('admin.dashboard_new.nationality_distribution') }}</p>
                                    <h4 class="mb-0">{{ __('admin.dashboard_new.top_available_nationalities') }}</h4>
                                </div>
                                <small>{{ __('admin.dashboard_new.workers_total', ['count' => array_sum($nationalityData ?? [])]) }}</small>
                            </div>
                            <div class="chart-holder" style="height: 260px;">
                                <canvas id="nationalitiesChart"></canvas>
                            </div>
                            <ul class="legend-list">
                                @if (isset($nationalityLabels) && isset($nationalityData))
                                    @foreach ($nationalityLabels as $index => $label)
                                        <li>
                                            <span>{{ $label }}</span>
                                            <strong>{{ number_format($nationalityData[$index] ?? 0) }}</strong>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="empty-state">{{ __('admin.dashboard_new.no_data_available') }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif
                @if ($canViewOfficesPanel)
                    <div class="col-xxl-4 col-lg-4">
                        <div class="panel-card h-100">
                            <div class="panel-head">
                                <div>
                                    <p class="section-eyebrow">{{ __('admin.dashboard_new.external_offices') }}</p>
                                    <h4 class="mb-0">{{ __('admin.dashboard_new.office_inventory') }}</h4>
                                </div>
                                <small>{{ __('admin.dashboard_new.office_count', ['count' => count($officeStats ?? [])]) }}</small>
                            </div>
                            <div class="office-stock-list">
                                @forelse($officeStats ?? [] as $office)
                                    @php
                                        $available = (int) ($office->maids_available ?? 0);
                                        $returned = (int) ($office->maids_returned ?? 0);
                                        $backout = (int) ($office->maids_backout ?? 0);
                                        $total = (int) ($office->maids_total ?? 0);
                                        $other =
                                            (int) ($office->maids_other ??
                                                max($total - $available - $returned - $backout, 0));
                                        $avgRecruitmentDays = isset($office->avg_recruitment_days)
                                            ? (int) $office->avg_recruitment_days
                                            : null;
                                        $arrivalsTotal = (int) ($office->arrivals_total ?? 0);
                                        $nationalityFlagUrl = $office->nationality_flag_url ?? null;
                                    @endphp
                                    <a href="{{ route('maids.index', ['office_id' => $office->id]) }}"
                                        class="office-stock-card office-stock-link">
                                        <div class="office-stock-head">
                                            <div class="office-stock-heading">
                                                <strong>{{ $office->title ?? __('admin.dashboard_new.office') }}</strong>
                                                <div class="office-stock-meta">
                                                    <span class="office-stock-pill">{{ __('admin.dashboard_new.total_label') }}
                                                        {{ number_format($total) }}</span>
                                                    @if ($avgRecruitmentDays !== null)
                                                        <span class="office-stock-pill">{{ __('admin.dashboard_new.average_arrival') }}
                                                            {{ number_format($avgRecruitmentDays) }}
                                                            {{ __('admin.dashboard_new.day') }}</span>
                                                    @else
                                                        <span class="office-stock-pill is-muted">{{ __('admin.dashboard_new.average_arrival_unavailable') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="office-stock-total">{{ __('admin.dashboard_new.cv_count_short', ['count' => number_format($total)]) }}</span>
                                        </div>

                                        <div class="office-stock-nationality">
                                            @if ($nationalityFlagUrl)
                                                <img src="{{ $nationalityFlagUrl }}"
                                                    alt="{{ $office->nationality->title ?? __('admin.dashboard_new.nationality') }}">
                                            @else
                                                <span class="office-stock-flag-fallback">
                                                    <i class="fas fa-flag"></i>
                                                </span>
                                            @endif
                                            <span>{{ $office->nationality->title ?? __('admin.dashboard_new.not_specified') }}</span>
                                        </div>

                                        <div class="office-stock-stats">
                                            <span class="office-status-pill is-available">{{ __('admin.dashboard_new.available') }}
                                                {{ number_format($available) }}</span>
                                            <span class="office-status-pill is-returned">{{ __('admin.dashboard_new.returned') }}
                                                {{ number_format($returned) }}</span>
                                            <span class="office-status-pill is-backout">{{ __('admin.dashboard_new.backout') }}
                                                {{ number_format($backout) }}</span>
                                            @if ($other > 0)
                                                <span class="office-status-pill is-other">{{ __('admin.dashboard_new.other') }}
                                                    {{ number_format($other) }}</span>
                                            @endif
                                        </div>

                                        <small class="office-stock-foot">
                                            @if ($arrivalsTotal > 0)
                                                {{ __('admin.dashboard_new.average_from_arrivals', ['count' => number_format($arrivalsTotal)]) }}
                                            @else
                                                {{ __('admin.dashboard_new.no_completed_arrivals_for_average') }}
                                            @endif
                                        </small>
                                    </a>
                                @empty
                                    <div class="empty-state py-3">{{ __('admin.dashboard_new.no_office_data') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
                @if ($canViewRecentActivitiesPanel)
                    <div class="col-xxl-4 col-lg-3">
                        <div class="panel-card h-100">
                            <div class="panel-head">
                                <div>
                                    <p class="section-eyebrow">{{ __('admin.dashboard_new.recent_updates') }}</p>
                                    <h4 class="mb-0">{{ __('admin.dashboard_new.recent_activities') }}</h4>
                                </div>
                                <small>{{ __('admin.dashboard_new.item_count', ['count' => count($recentActivities ?? [])]) }}</small>
                            </div>
                            <div class="activity-feed">
                                @forelse($recentActivities ?? [] as $activity)
                                    <div class="activity-line">
                                        <div class="activity-icon">
                                            <i class="fas fa-{{ $activity['icon'] ?? 'circle' }} text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <strong class="d-block">{{ $activity['title'] }}</strong>
                                            <small>{{ $activity['time'] }}</small>
                                        </div>
                                        <span
                                            class="status-chip {{ $activity['type'] ?? 'info' }}">{{ $activity['status'] }}</span>
                                    </div>
                                @empty
                                    <div class="empty-state">{{ __('admin.dashboard_new.no_recent_activity') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if ($canViewBottomRow)
            <div class="row g-4 mt-3">
                @if ($canViewCalendarPanel)
                    <div class="col-xxl-8">
                        <div class="panel-card h-100">
                            <div class="panel-head">
                                <div>
                                    <p class="section-eyebrow">{{ __('admin.dashboard_new.timeline') }}</p>
                                    <h4 class="mb-0">{{ __('admin.dashboard_new.important_dates') }}</h4>
                                </div>
                                <small id="calendarCount">{{ __('admin.dashboard_new.appointment_count', ['count' => count($calendarEvents ?? [])]) }}</small>
                            </div>
                            <div id="calendar" class="calendar-shell"></div>
                        </div>
                    </div>
                @endif
                @if ($canViewEmployeePerformancePanel)
                    <div class="col-xxl-4">
                        <div class="panel-card h-100">
                            <div class="panel-head">
                                <div>
                                    <p class="section-eyebrow">{{ __('admin.dashboard_new.staff') }}</p>
                                    <h4 class="mb-0">{{ __('admin.dashboard_new.employee_delegations_contracts') }}</h4>
                                </div>
                                <a href="{{ route('admins.index', $dashboardBranchQuery) }}" class="btn btn-outline-primary btn-sm">{{ __('admin.dashboard_new.manage') }}</a>
                            </div>
                            <div class="employee-summary-row">
                                <span class="employee-summary-pill">{{ __('admin.dashboard_new.active_employees') }}:
                                    {{ number_format($employeeBookingSummary['employees_with_activity'] ?? 0) }}</span>
                                <span class="employee-summary-pill">{{ __('admin.dashboard_new.ingaz_delegations') }}:
                                    {{ number_format($employeeBookingSummary['delegations_total'] ?? 0) }}</span>
                                <span class="employee-summary-pill">{{ __('admin.dashboard_new.contracts') }}:
                                    {{ number_format($employeeBookingSummary['contracts_total'] ?? 0) }}</span>
                            </div>
                            <div class="employee-performance-list">
                                @forelse($employeeBookings ?? [] as $emp)
                                    @php
                                        $handledTotal = (int) ($emp->handled_total ?? 0);
                                    @endphp
                                    <div class="employee-performance-card">
                                        <div class="employee-performance-head">
                                            <a href="{{ route('dashboard.employees.show', $withDashboardBranch(['employee' => $emp->id, 'range' => 'all'])) }}"
                                                class="employee-profile-link">{{ $emp->name }}</a>
                                            <span class="employee-total-badge">{{ number_format($handledTotal) }}
                                                {{ __('admin.dashboard_new.followups') }}</span>
                                        </div>
                                        <div class="employee-performance-metrics">
                                            <span class="employee-metric-chip chip-orders">{{ __('admin.dashboard_new.ingaz_delegations') }}:
                                                {{ number_format((int) $emp->delegations_count) }}</span>
                                            <span class="employee-metric-chip">{{ __('admin.dashboard_new.under_process') }}:
                                                {{ number_format((int) $emp->delegations_under_process) }}</span>
                                            <span class="employee-metric-chip">{{ __('admin.dashboard_new.executed') }}:
                                                {{ number_format((int) $emp->delegations_executed) }}</span>
                                            <span class="employee-metric-chip chip-contracts">{{ __('admin.dashboard_new.contracts') }}:
                                                {{ number_format((int) $emp->contracts_count) }}</span>
                                            <span class="employee-metric-chip">{{ __('admin.dashboard_new.current') }}:
                                                {{ number_format((int) $emp->contracts_current) }}</span>
                                            <span class="employee-metric-chip">{{ __('admin.dashboard_new.completed') }}:
                                                {{ number_format((int) $emp->contracts_completed) }}</span>
                                            <span class="employee-metric-chip">{{ __('admin.dashboard_new.returned') }}:
                                                {{ number_format((int) $emp->contracts_returned) }}</span>
                                        </div>
                                        <div class="employee-performance-footer">
                                            <small>{{ __('admin.dashboard_new.delegation_execution_rate') }}:
                                                {{ number_format((int) ($emp->execution_rate ?? 0)) }}%</small>
                                            <div class="employee-performance-actions">
                                                <a href="{{ route('ingaz-delegations.index', $withDashboardBranch(['marketer_id' => $emp->id])) }}"
                                                    class="btn btn-primary btn-sm">{{ __('admin.dashboard_new.ingaz_delegations') }}</a>
                                                <a href="{{ route('RecruitmentContract.index', $withDashboardBranch(['marketer_id' => $emp->id])) }}"
                                                    class="btn btn-outline-success btn-sm">{{ __('admin.dashboard_new.contracts') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state py-3">{{ __('admin.dashboard_new.no_employee_data') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            document.querySelectorAll('.stat-card').forEach((card) => {
                const primaryLink = card.querySelector('.stat-head a[href], .stat-icon-action[href], a[href]');
                if (!primaryLink) return;

                card.classList.add('is-clickable');
                card.setAttribute('role', 'link');
                card.setAttribute('tabindex', '0');

                const openCard = () => window.location.href = primaryLink.href;
                card.addEventListener('click', (event) => {
                    if (!event.target.closest('a, button, input, select, textarea')) openCard();
                });
                card.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openCard();
                    }
                });
            });

            const rootStyles = getComputedStyle(document.documentElement);
            const getCssVar = (name, fallback) => {
                const value = rootStyles.getPropertyValue(name).trim();
                return value || fallback;
            };
            const hexToRgba = (hex, alpha) => {
                let value = hex.replace('#', '').trim();
                if (value.length === 3) {
                    value = value.split('').map((c) => c + c).join('');
                }
                if (value.length !== 6) return hex;
                const bigint = parseInt(value, 16);
                const r = (bigint >> 16) & 255;
                const g = (bigint >> 8) & 255;
                const b = bigint & 255;
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            };
            const toRgba = (color, alpha) => {
                if (!color) return `rgba(95, 91, 255, ${alpha})`;
                const trimmed = color.trim();
                if (trimmed.startsWith('rgba(')) {
                    return trimmed.replace(/rgba\(([^)]+)\)/, (match, values) => {
                        const parts = values.split(',').map((v) => v.trim());
                        return `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, ${alpha})`;
                    });
                }
                if (trimmed.startsWith('rgb(')) {
                    return trimmed.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
                }
                if (trimmed.startsWith('#')) {
                    return hexToRgba(trimmed, alpha);
                }
                return trimmed;
            };

            const chartColor1 = getCssVar('--dash-chart-1', '#5f5bff');
            const chartColor2 = getCssVar('--dash-chart-2', '#28c76f');
            const chartColor3 = getCssVar('--dash-chart-3', '#36b9cc');
            const chartColor4 = getCssVar('--dash-chart-4', '#f4b347');
            const chartColor5 = getCssVar('--dash-chart-5', '#ef5350');
            const successColor = getCssVar('--success', '#28c76f');
            const loadedAssets = new Map();

            function loadExternalScript(src) {
                if (loadedAssets.has(src)) {
                    return loadedAssets.get(src);
                }

                const promise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = src;
                    script.async = true;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.body.appendChild(script);
                });

                loadedAssets.set(src, promise);
                return promise;
            }

            function loadExternalStyle(href) {
                if (document.querySelector(`link[href="${href}"]`)) {
                    return;
                }

                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            }

            const rangeButtons = document.querySelectorAll('.range-btn');
            const rangeValues = document.querySelectorAll('[data-range-value]');
            const rangeLabels = document.querySelectorAll('[data-range-label]');

            const dashboardLocale = @json(app()->getLocale() === 'ar' ? 'ar-SA' : 'en-US');
            const dashboardDirection = @json(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');
            const numberFormatter = new Intl.NumberFormat(dashboardLocale);

            function formatValue(value, format) {
                if (format === 'currency') {
                    return new Intl.NumberFormat('ar-SA', {
                        style: 'currency',
                        currency: 'SAR',
                        maximumFractionDigits: 0
                    }).format(value);
                }
                return numberFormatter.format(value);
            }

            rangeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const targetRange = button.dataset.range;
                    rangeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));

                    rangeValues.forEach((element) => {
                        const format = element.dataset.rangeFormat || 'number';
                        const datasetValue = element.dataset[targetRange];
                        if (datasetValue === undefined) return;
                        element.textContent = formatValue(Number(datasetValue) || 0,
                            format);
                    });

                    rangeLabels.forEach((element) => {
                        const labelText = element.dataset[targetRange];
                        if (labelText) {
                            element.textContent = labelText;
                        }
                    });
                });
            });

            if (rangeButtons.length) {
                rangeButtons[0].click();
            }

            document.querySelectorAll('[data-toggle-section]').forEach((button) => {
                button.addEventListener('click', () => {
                    const sectionName = button.dataset.toggleSection;
                    const target = document.querySelector(`[data-section-body="${sectionName}"]`);
                    if (!target) return;
                    target.classList.toggle('collapsed');
                    button.setAttribute('aria-expanded', !target.classList.contains('collapsed'));
                    button.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });

            const contractsCanvas = document.getElementById('contractsChart');
            const nationalitiesCanvas = document.getElementById('nationalitiesChart');
            const requestGenderCanvas = document.getElementById('requestGenderChart');

            if (contractsCanvas || nationalitiesCanvas || requestGenderCanvas) {
                loadExternalScript('https://cdn.jsdelivr.net/npm/chart.js').then(() => {
                    if (requestGenderCanvas) {
                        const requestGenderStats = @json($requestGenderStats ?? []);

                        new Chart(requestGenderCanvas.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: [
                                    @json(__('admin.dashboard_new.male')),
                                    @json(__('admin.dashboard_new.female')),
                                    @json(__('admin.dashboard_new.unspecified'))
                                ],
                                datasets: [{
                                    data: [
                                        Number(requestGenderStats.male || 0),
                                        Number(requestGenderStats.female || 0),
                                        Number(requestGenderStats.unknown || 0)
                                    ],
                                    backgroundColor: [chartColor1, chartColor3, chartColor4],
                                    borderColor: '#fff',
                                    borderWidth: 3,
                                    hoverOffset: 7
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '62%',
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        rtl: dashboardDirection === 'rtl',
                                        textDirection: dashboardDirection,
                                        callbacks: {
                                            label: (context) => `${context.label}: ${numberFormatter.format(context.raw || 0)}`
                                        }
                                    }
                                }
                            }
                        });
                    }

                    if (contractsCanvas) {
                        const contractsCtx = contractsCanvas.getContext('2d');
                        const contractsYearSeries = {!! json_encode($contractsYearSeries ?? []) !!};
                        const contractsYearColors = [chartColor1, chartColor2, successColor];

                        new Chart(contractsCtx, {
                            type: 'bar',
                            data: {
                                labels: {!! json_encode($contractsChartLabels ?? []) !!},
                                datasets: contractsYearSeries.map((series, index) => {
                                    const color = contractsYearColors[index % contractsYearColors.length];

                                    return {
                                        label: series.label,
                                        data: series.data || [],
                                        borderColor: color,
                                        backgroundColor: toRgba(color, 0.82),
                                        borderWidth: 2,
                                        borderRadius: 10,
                                        borderSkipped: false,
                                        maxBarThickness: 26,
                                        categoryPercentage: 0.72,
                                        barPercentage: 0.9
                                    };
                                })
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        padding: 12,
                                        rtl: dashboardDirection === 'rtl',
                                        textDirection: dashboardDirection
                                    }
                                },
                                interaction: {
                                    mode: 'index',
                                    intersect: false
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        },
                                        grid: {
                                            borderDash: [4, 4],
                                            color: 'rgba(15, 23, 42, 0.08)'
                                        }
                                    }
                                }
                            }
                        });
                    }

                    if (nationalitiesCanvas) {
                        const nationalitiesCtx = nationalitiesCanvas.getContext('2d');

                        new Chart(nationalitiesCtx, {
                            type: 'doughnut',
                            data: {
                                labels: {!! json_encode($nationalityLabels ?? ['الفلبين', 'إندونيسيا', 'بنغلاديش', 'الهند', 'أخرى']) !!},
                                datasets: [{
                                    data: {!! json_encode($nationalityData ?? [35, 25, 20, 15, 5]) !!},
                                    backgroundColor: [chartColor1, successColor, chartColor2, chartColor4,
                                        chartColor5
                                    ],
                                    borderColor: '#fff',
                                    borderWidth: 3,
                                    hoverOffset: 8,
                                    cutout: '68%'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                        rtl: dashboardDirection === 'rtl',
                                        textDirection: dashboardDirection
                                    }
                                }
                            }
                        });
                    }
                }).catch((error) => {
                    console.error('Chart library load failed', error);
                });
            }

            // التقويم
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                const calendarEventsUrl = "{{ route('travel.calendar.events') }}";
                const calendarCountEl = document.getElementById('calendarCount');

                const appointmentCountText = @json(__('admin.dashboard_new.appointment_count', ['count' => '__count__']));

                const renderCalendar = () => {
                    const calendar = new FullCalendar.Calendar(calendarEl, {
                        locale: @json(app()->getLocale()),
                        direction: dashboardDirection,
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        height: 500,
                        events: function(fetchInfo, successCallback, failureCallback) {
                            $.ajax({
                                url: calendarEventsUrl,
                                type: 'GET',
                                data: {
                                    start: fetchInfo.startStr,
                                    end: fetchInfo.endStr
                                },
                                success: function(response) {
                                    const events = response && response.success && Array.isArray(response.events)
                                        ? response.events
                                        : [];

                                    successCallback(events);
                                    if (calendarCountEl) {
                                        calendarCountEl.textContent = appointmentCountText.replace('__count__', events.length);
                                    }
                                },
                                error: function(xhr) {
                                    console.error('Calendar events load failed', xhr);
                                    failureCallback(xhr);
                                    if (calendarCountEl) {
                                        calendarCountEl.textContent = appointmentCountText.replace('__count__', 0);
                                    }
                                }
                            });
                        },
                        eventClick: function(info) {
                            if (info.jsEvent) {
                                info.jsEvent.preventDefault();
                            }

                            const eventUrl = info.event.url || info.event.extendedProps.url;
                            if (eventUrl) {
                                window.location.href = eventUrl;
                            }
                        }
                    });

                    calendar.render();
                };

                const loadCalendar = () => {
                    const fullCalendarSrc = "{{ asset('admin/assets/js/bundle/fullcalendar.bundle.js') }}";
                    const calendarLoader = window.FullCalendar
                        ? Promise.resolve()
                        : loadExternalScript(fullCalendarSrc);

                    calendarLoader.then(renderCalendar).catch((error) => {
                        console.error('Calendar library load failed', error);
                        if (calendarCountEl) {
                            calendarCountEl.textContent = appointmentCountText.replace('__count__', 0);
                        }
                    });
                };

                if ('IntersectionObserver' in window) {
                    const calendarObserver = new IntersectionObserver((entries, observer) => {
                        if (!entries.some((entry) => entry.isIntersecting)) {
                            return;
                        }
                        observer.disconnect();
                        loadCalendar();
                    }, {
                        rootMargin: '240px'
                    });
                    calendarObserver.observe(calendarEl);
                } else {
                    loadCalendar();
                }
            }
        });
    </script>
@endsection
