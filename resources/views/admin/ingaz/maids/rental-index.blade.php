@extends('admin.layout.indexs.index')

@php
    $pageTitle = $bladeTitle ?? helperTrans('ingaz.rental_maids_title');
    $createUrl = $createRoute ?? route('maids.rental.create');
    $dataTableUrl = $dataTableRoute ?? route('maids.rental.index');
    $resetUrl = $resetRoute ?? route('maids.rental.index');
    $pageBaseUrl = $pageBaseUrl ?? $dataTableUrl;
    $defaultFilters = $defaultFilters ?? [];
    $enforcedFilters = $enforcedFilters ?? [];
    $rentalStats = $rentalStats ?? [
        'total' => 0,
        'available' => 0,
        'reserved' => 0,
        'visible' => 0,
        'hidden' => 0,
        'in_accommodation' => 0,
    ];
    $rentalTypeId = $rentalTypeId ?? null;
    $visibleDefaultFilters = collect($defaultFilters)
        ->except(['job_type'])
        ->filter(fn($value) => !is_null($value) && $value !== '');
    $hasActiveFilters = $visibleDefaultFilters->isNotEmpty();
@endphp

@section('style')
    <style>
        .rental-page .statistics-card {
            border-left: 4px solid #4e73df;
            border-radius: 0.75rem;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .rental-page .statistics-card:hover,
        .rental-page .statistics-card.is-active {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        }

        .rental-page .statistics-card .card-body {
            padding: 1.25rem;
        }

        .rental-page .statistics-card .text-xs {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 700;
        }

        .rental-page .statistics-card .h5 {
            font-weight: 700;
            color: #1f2d3d;
        }

        .rental-page .available-card {
            border-left-color: #28a745;
        }

        .rental-page .contracts-card {
            border-left-color: #0d6efd;
        }

        .rental-page .reserved-card {
            border-left-color: #f6c23e;
        }

        .rental-page .visible-card {
            border-left-color: #198754;
        }

        .rental-page .hidden-card {
            border-left-color: #6c757d;
        }

        .rental-page .shelter-card {
            border-left-color: #1cc88a;
        }

        .rental-page .filter-card .card-header {
            background: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .rental-page .table img {
            max-width: 100%;
            height: auto;
        }

        .rental-page .finance-summary {
            min-width: 150px;
        }

        .rental-page .filter-toggle {
            min-width: 150px;
        }

        .rental-page .actions-btn {
            min-width: 118px;
        }

        .rental-page .maid-name {
            white-space: nowrap;
        }

        .rental-page .maid-name::before {
            content: attr(data-maid-display);
        }

        .dropdown-menu.dropdown-menu-end.rental-actions-floating-menu {
            position: fixed !important;
            top: var(--rental-actions-top) !important;
            left: var(--rental-actions-left) !important;
            right: auto !important;
            transform: none !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 2147483647 !important;
            min-width: 220px;
            max-height: min(420px, calc(100vh - 24px));
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .rental-page .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $pageTitle }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW))
        <div class="rental-page">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h4 class="mb-1">{{ $pageTitle }}</h4>
                    <div class="text-muted small">{{ helperTrans('ingaz.rental_maids_operational_hint') }}</div>
                </div>
                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                    <a href="{{ $createUrl }}" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i> {{ helperTrans('ingaz.add_rental_cv') }}
                    </a>
                @endif
            </div>

            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card total-card shadow h-100" data-rental-filter="total">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-list me-1"></i>{{ helperTrans('ingaz.total_rental_cvs') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['total'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-contract fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card available-card shadow h-100" data-rental-filter="available">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-check-circle me-1"></i>{{ helperTrans('ingaz.available') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['available'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card reserved-card shadow h-100" data-rental-filter="reserved">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-calendar-check me-1"></i>{{ helperTrans('ingaz.maid_status_reserved') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['reserved'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-handshake fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card visible-card shadow h-100" data-rental-filter="visible">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-eye me-1"></i>{{ helperTrans('ingaz.visible_on_public_site') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['visible'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-globe fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card hidden-card shadow h-100" data-rental-filter="hidden">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-eye-slash me-1"></i>{{ helperTrans('ingaz.hidden_on_public_site') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['hidden'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-secret fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card shelter-card shadow h-100" data-rental-filter="in_accommodation">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-home me-1"></i>{{ helperTrans('ingaz.inside_shelter_now') }}</div>
                                    <div class="h5 mb-0">{{ number_format($rentalStats['in_accommodation'] ?? 0) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-warehouse fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 fw-bold"><i class="fas fa-filter me-2"></i>{{ helperTrans('admin.filters') }}</h6>
                        <small class="text-muted">{{ helperTrans('ingaz.customize_results_by_filters') }}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if ($hasActiveFilters)
                            <span class="badge bg-primary-subtle text-primary">{{ __('ingaz.active_filters_count', ['count' => $visibleDefaultFilters->count()]) }}</span>
                        @endif
                        <button class="btn btn-outline-primary btn-sm filter-toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#rentalFilterCollapse" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                            aria-controls="rentalFilterCollapse">
                            <i class="fas {{ $hasActiveFilters ? 'fa-chevron-up' : 'fa-chevron-down' }} me-1 filter-toggle-icon"></i>
                            <span class="filter-toggle-text">{{ $hasActiveFilters ? helperTrans('ingaz.hide_filter') : helperTrans('ingaz.show_filter') }}</span>
                        </button>
                    </div>
                </div>
                <div id="rentalFilterCollapse" class="collapse {{ $hasActiveFilters ? 'show' : '' }}">
                    <div class="card-body">
                    <form id="filterForm">
                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <input type="text" class="form-control" id="keyword" name="keyword"
                                    value="{{ $request->keyword }}" placeholder="{{ helperTrans('ingaz.search_by_name_passport_id') }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <input type="text" class="form-control" id="passport_num" name="passport_num"
                                    value="{{ $request->passport_num }}" placeholder="{{ helperTrans('ingaz.passport_num') }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="nationality_id" id="nationality_id">
                                    <option value="">{{ helperTrans('ingaz.nationality') }}</option>
                                    @foreach ($filter['nationalities'] as $nationality)
                                        <option value="{{ $nationality->id }}" {{ (string) $request->nationality_id === (string) $nationality->id ? 'selected' : '' }}>
                                            {{ $nationality->sub_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="office_id" id="office_id">
                                    <option value="">{{ helperTrans('ingaz.recruitment office') }}</option>
                                    @foreach ($filter['offices'] as $office)
                                        <option value="{{ $office->id }}" {{ (string) $request->office_id === (string) $office->id ? 'selected' : '' }}>
                                            {{ $office->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="job_type" id="type" disabled>
                                    <option value="">{{ helperTrans('ingaz.select_cv_type') }}</option>
                                    @foreach ($filter['job_types'] as $jobType)
                                        <option value="{{ $jobType->id }}" {{ (string) $rentalTypeId === (string) $jobType->id ? 'selected' : '' }}>
                                            {{ $jobType->title ?? ($jobType->translations->first()->title ?? '-') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <input type="text" name="experience_years" id="experience_years"
                                    value="{{ $request->experience_years }}" class="form-control" placeholder="{{ helperTrans('ingaz.experience_years') }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="cv_status" id="cv_status">
                                    <option value="">{{ helperTrans('ingaz.cv_status') }}</option>
                                    <option value="available" {{ $request->cv_status === 'available' ? 'selected' : '' }}>{{ helperTrans('ingaz.available') }}</option>
                                    <option value="reserved" {{ $request->cv_status === 'reserved' ? 'selected' : '' }}>{{ helperTrans('ingaz.maid_status_reserved') }}</option>
                                    <option value="backout" {{ $request->cv_status === 'backout' ? 'selected' : '' }}>{{ helperTrans('ingaz.backout') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="visibility" id="visibility" class="form-control">
                                    <option value="">{{ helperTrans('ingaz.public_site_visibility_status') }}</option>
                                    <option value="visible" {{ $request->visibility === 'visible' ? 'selected' : '' }}>{{ helperTrans('ingaz.visible_on_public_site') }}</option>
                                    <option value="hidden" {{ $request->visibility === 'hidden' ? 'selected' : '' }}>{{ helperTrans('ingaz.hidden_on_public_site') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="experience_status" id="experience_status" class="form-control">
                                    <option value="">{{ helperTrans('ingaz.all_experience_statuses') }}</option>
                                    <option value="has_experience" {{ $request->experience_status === 'has_experience' ? 'selected' : '' }}>{{ helperTrans('ingaz.has_experience') }}</option>
                                    <option value="no_experience" {{ $request->experience_status === 'no_experience' ? 'selected' : '' }}>{{ helperTrans('ingaz.no_experience') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="request_type" id="request_type" class="form-control">
                                    <option value="">{{ helperTrans('ingaz.all_request_types') }}</option>
                                    @foreach (($filter['request_types'] ?? []) as $requestTypeKey => $requestTypeLabel)
                                        <option value="{{ $requestTypeKey }}" {{ $request->request_type === $requestTypeKey ? 'selected' : '' }}>
                                            {{ $requestTypeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="pinned" id="pinned" class="form-control">
                                    <option value="">{{ helperTrans('ingaz.all_pinning_statuses') }}</option>
                                    <option value="only" {{ $request->pinned === 'only' ? 'selected' : '' }}>{{ helperTrans('ingaz.only_pinned') }}</option>
                                    <option value="unpinned" {{ $request->pinned === 'unpinned' ? 'selected' : '' }}>{{ helperTrans('ingaz.only_unpinned') }}</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <label class="form-label small text-muted mb-1">{{ helperTrans('ingaz.from_date') }}</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                    value="{{ $request->date_from }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <label class="form-label small text-muted mb-1">{{ helperTrans('ingaz.to_date') }}</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                    value="{{ $request->date_to }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 mb-3 text-end">
                                <button id="btnSubmit" type="submit" class="btn btn-primary me-2">
                                    <i class="fa fa-search me-1"></i> {{ helperTrans('ingaz.search') }}
                                </button>
                                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-times me-1"></i> {{ helperTrans('ingaz.clear_filters') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell">ID</th>
                            <th>{{ helperTrans('ingaz.cv_file') }}</th>
                            <th>{{ helperTrans('ingaz.name') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.nationality_id') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.job_type') }}</th>
                            <th>{{ helperTrans('ingaz.passport_num') }}</th>
                            <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.price') }}</th>
                            <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.salary') }}</th>
                            <th>{{ helperTrans('ingaz.financial_items') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.religion_id') }}</th>
                            <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.age') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.maidContractRemaining') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.is_show') }}</th>
                            <th>{{ helperTrans('ingaz.cv_status') }}</th>
                            <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.created_at') }}</th>
                            <th>{{ helperTrans('ingaz.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="modal fade" id="rentalFinanceItemsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.financial_items_for_rental_worker') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="fw-semibold notranslate" id="rentalFinanceItemsTitle" translate="no" dir="auto">-</div>
                                <div class="text-muted small" id="rentalFinanceItemsStats">-</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 16%;">{{ helperTrans('ingaz.type') }}</th>
                                            <th>{{ helperTrans('ingaz.item') }}</th>
                                            <th style="width: 20%;">{{ helperTrans('ingaz.amount') }}</th>
                                            <th style="width: 22%;">{{ helperTrans('ingaz.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rentalFinanceItemsTableBody">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">{{ helperTrans('ingaz.no_financial_items') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="back_out_modal" tabindex="-1" role="dialog"
                aria-labelledby="backOutModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header align-items-center">
                            <h5 class="modal-title" id="backOutModalTitle">{{ helperTrans('ingaz.add_reason') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="back_out_form" method="POST">
                                @csrf
                                <input type="hidden" name="maid_id" id="maid_id">
                                <div class="mb-3">
                                    <textarea rows="8" class="form-control" name="backout_reason" placeholder="{{ helperTrans('ingaz.backout_reason') }}" required></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-success px-4" type="button"
                                        onclick="back_out_modal_func()">{{ helperTrans('ingaz.send') }}</button>
                                    <button class="btn btn-danger px-4" type="button" data-bs-dismiss="modal">
                                        {{ helperTrans('ingaz.close') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="back_out_modal_on" tabindex="-1" role="dialog"
                aria-labelledby="backOutOnModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header align-items-center">
                            <h5 class="modal-title" id="backOutOnModalTitle">{{ helperTrans('ingaz.return_cv_available') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="back_out_modal_on_form" method="POST">
                                @csrf
                                <input type="hidden" name="maid_id" id="maid_id_on">
                                <h5 class="mb-3">{{ helperTrans('ingaz.confirm_return_cv_available') }}</h5>
                                <div class="modal-footer">
                                    <button class="btn btn-success px-4" type="button"
                                        onclick="back_out_modal_on_func()">{{ helperTrans('ingaz.yes') }}</button>
                                    <button class="btn btn-danger px-4" type="button" data-bs-dismiss="modal">
                                        {{ helperTrans('ingaz.no') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script src="{{ asset('admin/assets/js/bundle/dataTables.bundle.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const dataTableBaseUrl = @json($dataTableUrl);
            const pageBaseUrl = @json($pageBaseUrl);
            const defaultFiltersConfig = @json($defaultFilters);
            const enforcedFiltersConfig = @json($enforcedFilters);
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            const rentalTranslations = {
                hideFilter: @json(helperTrans('ingaz.hide_filter')),
                showFilter: @json(helperTrans('ingaz.show_filter')),
                rentalWorker: @json(helperTrans('ingaz.rental_worker_in_hr')),
                financeSummary: @json(helperTrans('ingaz.finance_items_count_summary')),
                noFinancialItems: @json(helperTrans('ingaz.no_financial_items')),
                income: @json(helperTrans('ingaz.income')),
                expense: @json(helperTrans('ingaz.expense')),
                settledWithDate: @json(helperTrans('ingaz.settled_with_date')),
                unsettled: @json(helperTrans('ingaz.unsettled')),
            };

            let currentRentalStat = defaultFiltersConfig.rental_stat || '';
            const rentalFinanceItemsModalElement = document.getElementById('rentalFinanceItemsModal');
            const rentalFinanceItemsModal = rentalFinanceItemsModalElement && window.bootstrap
                ? new bootstrap.Modal(rentalFinanceItemsModalElement)
                : null;

            function closeRentalActionsDropdown() {
                $('#rentalActionsFloatingMenu').remove();

                $('#dataTableObject .actions-dropdown').each(function() {
                    $(this).removeClass('show');
                    $(this).find('.actions-btn').attr('aria-expanded', 'false').removeClass('show');
                    $(this).find('.dropdown-menu').removeClass('show').removeAttr('style');
                });
            }

            function placeRentalActionsMenu($button, $menu) {
                const rect = $button[0].getBoundingClientRect();
                const menuWidth = Math.max($menu.outerWidth() || 220, 220);
                const menuHeight = Math.max($menu.outerHeight() || 240, 160);
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
                    '--rental-actions-top': top + 'px',
                    '--rental-actions-left': left + 'px',
                    right: 'auto',
                    transform: 'none',
                    zIndex: 2147483647,
                    display: 'block',
                    minWidth: menuWidth + 'px'
                });
            }

            const columns = [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'd-none d-md-table-cell' },
                { data: 'cv_file', orderable: false, searchable: false },
                { data: 'name', orderable: false, searchable: false },
                { data: 'nationality_id', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
                { data: 'job_type', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
                { data: 'passport_num', orderable: false, searchable: false },
                { data: 'price', orderable: false, searchable: false, className: 'd-none d-md-table-cell' },
                { data: 'salary', orderable: false, searchable: false, className: 'd-none d-md-table-cell' },
                { data: 'financial_items', orderable: false, searchable: false },
                { data: 'religion_id', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
                { data: 'age', orderable: false, searchable: false, className: 'd-none d-md-table-cell' },
                { data: 'maidContractRemaining', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
                { data: 'is_show', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
                { data: 'cv_status', orderable: false, searchable: false },
                { data: 'created_at', orderable: false, searchable: false, className: 'd-none d-md-table-cell' },
                { data: 'actions', orderable: false, searchable: false }
            ];

            function mergeFilters(additionalFilters = {}) {
                const merged = Object.assign({}, defaultFiltersConfig || {});

                Object.keys(additionalFilters || {}).forEach(function(key) {
                    const value = additionalFilters[key];
                    if (value !== undefined && value !== null && value !== '') {
                        merged[key] = value;
                    } else {
                        delete merged[key];
                    }
                });

                Object.keys(enforcedFiltersConfig || {}).forEach(function(key) {
                    merged[key] = enforcedFiltersConfig[key];
                });

                return merged;
            }

            function buildQueryParams(filters) {
                return new URLSearchParams(filters).toString();
            }

            function buildUrl(filters, baseUrl) {
                const queryString = buildQueryParams(filters);
                return queryString ? `${baseUrl}?${queryString}` : baseUrl;
            }

            function collectFilters() {
                const filters = {
                    keyword: $('#keyword').val(),
                    passport_num: $('#passport_num').val(),
                    nationality_id: $('#nationality_id').val(),
                    office_id: $('#office_id').val(),
                    job_type: $('#type').val(),
                    experience_years: $('#experience_years').val(),
                    cv_status: $('#cv_status').val(),
                    visibility: $('#visibility').val(),
                    experience_status: $('#experience_status').val(),
                    request_type: $('#request_type').val(),
                    pinned: $('#pinned').val(),
                    date_from: $('#date_from').val(),
                    date_to: $('#date_to').val()
                };

                if (currentRentalStat && !filters.cv_status) {
                    filters.rental_stat = currentRentalStat;
                }

                return mergeFilters(filters);
            }

            function setActiveRentalStat(filterKey) {
                $('.statistics-card[data-rental-filter]').removeClass('is-active');
                if (filterKey) {
                    $(`.statistics-card[data-rental-filter="${filterKey}"]`).addClass('is-active');
                }
            }

            const initialFilters = mergeFilters();
            const table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: buildUrl(initialFilters, dataTableBaseUrl),
                    type: 'GET'
                },
                columns: columns,
                pageLength: 10,
                order: [[13, 'desc']],
                language: {
                    url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
                },
                columnDefs: [{
                    targets: '_all',
                    className: 'align-middle'
                }],
                drawCallback: function() {
                    $('#dataTableObject .actions-btn').removeAttr('data-bs-toggle data-bs-auto-close data-bs-display');
                    closeRentalActionsDropdown();
                }
            });

            $(document).on('click', '#dataTableObject .actions-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $button = $(this);
                $button.removeAttr('data-bs-toggle data-bs-auto-close data-bs-display');
                const $dropdown = $button.closest('.actions-dropdown');
                const $sourceMenu = $dropdown.find('.dropdown-menu');
                const isOpen = $button.attr('aria-expanded') === 'true';

                closeRentalActionsDropdown();

                if (isOpen) {
                    return;
                }

                const $menu = $('<ul id="rentalActionsFloatingMenu" class="dropdown-menu dropdown-menu-end shadow-lg show rental-actions-floating-menu"></ul>');
                $menu.html($sourceMenu.html());
                $('body').append($menu);

                $dropdown.addClass('show');
                $button.attr('aria-expanded', 'true').addClass('show');
                placeRentalActionsMenu($button, $menu);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dataTableObject .actions-dropdown, #rentalActionsFloatingMenu').length) {
                    closeRentalActionsDropdown();
                }
            });

            $(document).on('click', '#rentalActionsFloatingMenu .dropdown-item, #rentalActionsFloatingMenu form button', function() {
                setTimeout(closeRentalActionsDropdown, 0);
            });

            function updateFilterToggleState() {
                const collapseElement = document.getElementById('rentalFilterCollapse');
                const isExpanded = collapseElement && collapseElement.classList.contains('show');
                $('.filter-toggle-icon')
                    .toggleClass('fa-chevron-up', isExpanded)
                    .toggleClass('fa-chevron-down', !isExpanded);
                $('.filter-toggle-text').text(isExpanded ? rentalTranslations.hideFilter : rentalTranslations.showFilter);
            }

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                if ($('#cv_status').val()) {
                    currentRentalStat = '';
                    setActiveRentalStat($('#cv_status').val());
                } else if ($('#visibility').val()) {
                    currentRentalStat = '';
                    setActiveRentalStat($('#visibility').val());
                } else if (!currentRentalStat) {
                    setActiveRentalStat('total');
                }

                const filters = collectFilters();
                const dataUrl = buildUrl(filters, dataTableBaseUrl);
                const pageUrl = buildUrl(filters, pageBaseUrl);

                window.history.replaceState({ path: pageUrl }, '', pageUrl);
                table.ajax.url(dataUrl).load();
            });

            $('.statistics-card[data-rental-filter]').on('click', function() {
                const filterKey = $(this).data('rental-filter');

                if (filterKey === 'total') {
                    currentRentalStat = '';
                    $('#cv_status').val('');
                    $('#visibility').val('');
                } else if (filterKey === 'available' || filterKey === 'reserved' || filterKey === 'backout') {
                    currentRentalStat = '';
                    $('#cv_status').val(filterKey);
                    $('#visibility').val('');
                } else if (filterKey === 'visible' || filterKey === 'hidden') {
                    currentRentalStat = '';
                    $('#cv_status').val('');
                    $('#visibility').val(filterKey);
                } else {
                    currentRentalStat = filterKey;
                    $('#cv_status').val('');
                    $('#visibility').val('');
                }

                setActiveRentalStat(filterKey);
                $('#filterForm').trigger('submit');
            });

            $(document).on('click', '.rental-finance-view', function() {
                const maidName = $(this).data('maid-name') || rentalTranslations.rentalWorker;
                const rawItems = $(this).attr('data-items') || '[]';
                let items = [];

                try {
                    items = JSON.parse(rawItems);
                } catch (error) {
                    items = [];
                }

                const tbody = $('#rentalFinanceItemsTableBody');
                const expenseTotal = items
                    .filter(item => item.type === 'expense')
                    .reduce((sum, item) => sum + Number(item.amount || 0), 0);
                const incomeTotal = items
                    .filter(item => item.type === 'income')
                    .reduce((sum, item) => sum + Number(item.amount || 0), 0);

                $('#rentalFinanceItemsTitle').text(maidName);
                $('#rentalFinanceItemsStats').text(rentalTranslations.financeSummary
                    .replace(':count', items.length)
                    .replace(':expenses', expenseTotal.toFixed(2))
                    .replace(':income', incomeTotal.toFixed(2)));

                if (!items.length) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted">' + rentalTranslations.noFinancialItems + '</td></tr>');
                } else {
                    const rows = items.map(item => {
                        const typeLabel = item.type === 'income'
                            ? '<span class="badge bg-success">' + rentalTranslations.income + '</span>'
                            : '<span class="badge bg-danger">' + rentalTranslations.expense + '</span>';
                        const settledLabel = item.settled_at
                            ? '<span class="badge bg-info text-dark">' + rentalTranslations.settledWithDate.replace(':date', item.settled_at) + '</span>'
                            : '<span class="badge bg-secondary">' + rentalTranslations.unsettled + '</span>';

                        return `
                            <tr>
                                <td>${typeLabel}</td>
                                <td>${item.label || '-'}</td>
                                <td>${Number(item.amount || 0).toFixed(2)}</td>
                                <td>${settledLabel}</td>
                            </tr>
                        `;
                    }).join('');

                    tbody.html(rows);
                }

                if (rentalFinanceItemsModal) {
                    rentalFinanceItemsModal.show();
                }
            });

            $(document).on('click', '.update-status', function(e) {
                e.preventDefault();
                const button = $(this);
                const id = button.data('id');

                Swal.fire({
                    icon: 'question',
                    title: '{{ helperTrans('ingaz.confirm_toggle_visibility') }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ helperTrans('ingaz.confirm') }}',
                    cancelButtonText: '{{ helperTrans('ingaz.cancel') }}'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    button.prop('disabled', true);
                    $.ajax({
                        url: `/admin/maids/${id}`,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ helperTrans('ingaz.updated_successfully') }}',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ helperTrans('ingaz.error') }}',
                                    text: response.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                            });
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                });
            });

            $(document).on('click', '.toggle-pin', function(e) {
                e.preventDefault();
                const button = $(this);
                const id = button.data('id');
                const isPinned = button.data('pinned') === true || button.data('pinned') === 'true';

                Swal.fire({
                    icon: 'question',
                    title: isPinned ? '{{ helperTrans('ingaz.confirm_unpin') }}' : '{{ helperTrans('ingaz.confirm_pin') }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ helperTrans('ingaz.confirm') }}',
                    cancelButtonText: '{{ helperTrans('ingaz.cancel') }}'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: `/admin/maids/${id}/toggle-pin`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ helperTrans('ingaz.updated_successfully') }}',
                                    timer: 1800,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ helperTrans('ingaz.error') }}',
                                    text: response.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                            });
                        }
                    });
                });
            });

            $(document).on('click', '.generate-maid-cv', function(e) {
                e.preventDefault();
                const button = $(this);
                const url = button.data('url');

                if (!url) {
                    return;
                }

                button.prop('disabled', true);
                Swal.fire({
                    title: 'جاري إنشاء النموذج',
                    text: @json(($cvOutputType ?? 'pdf') === 'image'
                        ? 'يرجى الانتظار حتى يتم إنشاء صورة السيرة الذاتية.'
                        : 'يرجى الانتظار حتى يتم إنشاء ملف PDF.'),
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: response.message || 'تم إنشاء النموذج بنجاح',
                            timer: 2200,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'تعذر إنشاء النموذج',
                            text: xhr.responseJSON?.message || @json(helperTrans('ingaz.unexpected_error'))
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false);
                    }
                });
            });

            window.back_out_modal_func = function() {
                $('#back_out_modal').modal('hide');

                $.ajax({
                    url: "{{ route('maids.backout') }}",
                    type: 'POST',
                    data: $("#back_out_form").serialize(),
                    success: function(response) {
                        if (response.data && response.data.success === true) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message || '{{ helperTrans('ingaz.unexpected_error') }}',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                        });
                    }
                });
            };

            window.back_out_modal_on_func = function() {
                $('#back_out_modal_on').modal('hide');

                $.ajax({
                    url: "{{ route('maids.remove_backout') }}",
                    type: 'POST',
                    data: $("#back_out_modal_on_form").serialize(),
                    success: function(response) {
                        if (response.data && response.data.success === true) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message || '{{ helperTrans('ingaz.unexpected_error') }}',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                        });
                    }
                });
            };

            window.back_out_on = function(id) {
                $('#back_out_modal_on').modal('show', {
                    backdrop: 'static'
                });
                $("#maid_id_on").val(id);
            };

            window.back_out = function(id) {
                $('#back_out_modal').modal('show', {
                    backdrop: 'static'
                });
                $("#maid_id").val(id);
            };

            window.suspendCv = function(id, status) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('maids.suspendCv') }}",
                    data: {
                        id: id,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.data && response.data.success === true) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message || '{{ helperTrans('ingaz.unexpected_error') }}',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                        });
                    }
                });
            };

            $('#rentalFilterCollapse').on('shown.bs.collapse hidden.bs.collapse', function() {
                updateFilterToggleState();
            });

            if (initialFilters.rental_stat) {
                setActiveRentalStat(initialFilters.rental_stat);
            } else if (initialFilters.visibility === 'visible' || initialFilters.visibility === 'hidden') {
                setActiveRentalStat(initialFilters.visibility);
            } else if (initialFilters.cv_status === 'available' || initialFilters.cv_status === 'reserved' || initialFilters.cv_status === 'backout') {
                setActiveRentalStat(initialFilters.cv_status);
            } else {
                setActiveRentalStat('total');
            }

            updateFilterToggleState();
        });
    </script>
@endsection
