@extends('admin.layout.indexs.index')

@php
    $pageTitle = $bladeTitle ?? 'سير عاملات الإيجار';
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
                    <div class="text-muted small">ترتيب تشغيلي يوضح حالة التوفر والظهور بالموقع الخارجي ووضع الإيواء الحالي</div>
                </div>
                @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                    <a href="{{ $createUrl }}" class="btn btn-primary">
                        <i class="fa fa-plus me-1"></i> إضافة سيرة إيجار
                    </a>
                @endif
            </div>

            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card statistics-card total-card shadow h-100" data-rental-filter="total">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col me-2">
                                    <div class="text-xs mb-1"><i class="fas fa-list me-1"></i>إجمالي سير الإيجار</div>
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
                                    <div class="text-xs mb-1"><i class="fas fa-check-circle me-1"></i>المتاح</div>
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
                                    <div class="text-xs mb-1"><i class="fas fa-calendar-check me-1"></i>المحجوز</div>
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
                                    <div class="text-xs mb-1"><i class="fas fa-eye me-1"></i>تظهر بالموقع الخارجي</div>
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
                                    <div class="text-xs mb-1"><i class="fas fa-eye-slash me-1"></i>لا تظهر بالموقع الخارجي</div>
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
                                    <div class="text-xs mb-1"><i class="fas fa-home me-1"></i>داخل الإيواء حالياً</div>
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
                        <small class="text-muted">تخصيص النتائج طبقاً للمعايير المطلوبة</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if ($hasActiveFilters)
                            <span class="badge bg-primary-subtle text-primary">الفلاتر النشطة: {{ $visibleDefaultFilters->count() }}</span>
                        @endif
                        <button class="btn btn-outline-primary btn-sm filter-toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#rentalFilterCollapse" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}"
                            aria-controls="rentalFilterCollapse">
                            <i class="fas {{ $hasActiveFilters ? 'fa-chevron-up' : 'fa-chevron-down' }} me-1 filter-toggle-icon"></i>
                            <span class="filter-toggle-text">{{ $hasActiveFilters ? 'إخفاء الفلتر' : 'إظهار الفلتر' }}</span>
                        </button>
                    </div>
                </div>
                <div id="rentalFilterCollapse" class="collapse {{ $hasActiveFilters ? 'show' : '' }}">
                    <div class="card-body">
                    <form id="filterForm">
                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <input type="text" class="form-control" id="passport_num" name="passport_num"
                                    value="{{ $request->passport_num }}" placeholder="رقم الجواز">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="nationality_id" id="nationality_id">
                                    <option value="">الجنسية</option>
                                    @foreach ($filter['nationalities'] as $nationality)
                                        <option value="{{ $nationality->id }}" {{ (string) $request->nationality_id === (string) $nationality->id ? 'selected' : '' }}>
                                            {{ $nationality->sub_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="office_id" id="office_id">
                                    <option value="">مكتب الاستقدام</option>
                                    @foreach ($filter['offices'] as $office)
                                        <option value="{{ $office->id }}" {{ (string) $request->office_id === (string) $office->id ? 'selected' : '' }}>
                                            {{ $office->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="job_type" id="type" disabled>
                                    <option value="">نوع السيرة الذاتية</option>
                                    @foreach ($filter['job_types'] as $jobType)
                                        <option value="{{ $jobType->id }}" {{ (string) $rentalTypeId === (string) $jobType->id ? 'selected' : '' }}>
                                            {{ $jobType->title ?? ($jobType->translations->first()->title ?? '-') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <input type="text" name="experience_years" id="experience_years"
                                    value="{{ $request->experience_years }}" class="form-control" placeholder="سنوات الخبرة">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select class="form-control" name="cv_status" id="cv_status">
                                    <option value="">{{ helperTrans('ingaz.cv_status') }}</option>
                                    <option value="available" {{ $request->cv_status === 'available' ? 'selected' : '' }}>متاحة</option>
                                    <option value="reserved" {{ $request->cv_status === 'reserved' ? 'selected' : '' }}>محجوزة</option>
                                    <option value="backout" {{ $request->cv_status === 'backout' ? 'selected' : '' }}>باك اوت</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="visibility" id="visibility" class="form-control">
                                    <option value="">حالة الظهور بالموقع</option>
                                    <option value="visible" {{ $request->visibility === 'visible' ? 'selected' : '' }}>تظهر بالموقع</option>
                                    <option value="hidden" {{ $request->visibility === 'hidden' ? 'selected' : '' }}>لا تظهر بالموقع</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <select name="experience_status" id="experience_status" class="form-control">
                                    <option value="">جميع حالات الخبرة</option>
                                    <option value="has_experience" {{ $request->experience_status === 'has_experience' ? 'selected' : '' }}>سبق لها العمل</option>
                                    <option value="no_experience" {{ $request->experience_status === 'no_experience' ? 'selected' : '' }}>لم يسبق لها العمل</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <label class="form-label small text-muted mb-1">من تاريخ</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                    value="{{ $request->date_from }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                                <label class="form-label small text-muted mb-1">إلى تاريخ</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                    value="{{ $request->date_to }}">
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-12 mb-3 text-end">
                                <button id="btnSubmit" type="submit" class="btn btn-primary me-2">
                                    <i class="fa fa-search me-1"></i> بحث
                                </button>
                                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-times me-1"></i> إلغاء البحث
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
                            <th>البنود المالية</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.religion_id') }}</th>
                            <th class="d-none d-md-table-cell">{{ helperTrans('ingaz.age') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.maidContractRemaining') }}</th>
                            <th class="d-none d-lg-table-cell">{{ helperTrans('ingaz.is_show') }}</th>
                            <th class="d-none d-lg-table-cell">مثبتة</th>
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
                            <h5 class="modal-title">البنود المالية لعاملة الإيجار</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="fw-semibold" id="rentalFinanceItemsTitle">-</div>
                                <div class="text-muted small" id="rentalFinanceItemsStats">-</div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 16%;">النوع</th>
                                            <th>البند</th>
                                            <th style="width: 20%;">المبلغ</th>
                                            <th style="width: 22%;">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rentalFinanceItemsTableBody">
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">لا توجد بنود مالية</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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
    <script>
        $(document).ready(function() {
            const dataTableBaseUrl = @json($dataTableUrl);
            const pageBaseUrl = @json($pageBaseUrl);
            const defaultFiltersConfig = @json($defaultFilters);
            const enforcedFiltersConfig = @json($enforcedFilters);

            let currentRentalStat = defaultFiltersConfig.rental_stat || '';
            const rentalFinanceItemsModalElement = document.getElementById('rentalFinanceItemsModal');
            const rentalFinanceItemsModal = rentalFinanceItemsModalElement && window.bootstrap
                ? new bootstrap.Modal(rentalFinanceItemsModalElement)
                : null;

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
                { data: 'is_pinned', orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
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
                    passport_num: $('#passport_num').val(),
                    nationality_id: $('#nationality_id').val(),
                    office_id: $('#office_id').val(),
                    job_type: $('#type').val(),
                    experience_years: $('#experience_years').val(),
                    cv_status: $('#cv_status').val(),
                    visibility: $('#visibility').val(),
                    experience_status: $('#experience_status').val(),
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
                }]
            });

            function updateFilterToggleState() {
                const collapseElement = document.getElementById('rentalFilterCollapse');
                const isExpanded = collapseElement && collapseElement.classList.contains('show');
                $('.filter-toggle-icon')
                    .toggleClass('fa-chevron-up', isExpanded)
                    .toggleClass('fa-chevron-down', !isExpanded);
                $('.filter-toggle-text').text(isExpanded ? 'إخفاء الفلتر' : 'إظهار الفلتر');
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
                const maidName = $(this).data('maid-name') || 'عاملة إيجار';
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
                $('#rentalFinanceItemsStats').text(
                    `عدد البنود: ${items.length} | إجمالي المصروف: ${expenseTotal.toFixed(2)} | إجمالي الإيراد: ${incomeTotal.toFixed(2)}`
                );

                if (!items.length) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted">لا توجد بنود مالية</td></tr>');
                } else {
                    const rows = items.map(item => {
                        const typeLabel = item.type === 'income'
                            ? '<span class="badge bg-success">إيراد</span>'
                            : '<span class="badge bg-danger">مصروف</span>';
                        const settledLabel = item.settled_at
                            ? `<span class="badge bg-info text-dark">مسكن ${item.settled_at}</span>`
                            : '<span class="badge bg-secondary">غير مسكن</span>';

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
