@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .stats-card {
            border-radius: 16px;
            border: 1px solid #eef2f6;
            box-shadow: 0 6px 18px rgba(15, 34, 58, 0.05);
        }

        .filter-stat-card {
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .filter-stat-card:hover,
        .filter-stat-card.active {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(15, 34, 58, 0.12);
        }

        .stats-card .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .filter-card {
            border-radius: 18px;
            border: 1px solid #eef2f6;
            box-shadow: 0 8px 22px rgba(15, 34, 58, 0.05);
        }

        .filter-card label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .badge {
            border-radius: 999px;
        }

        @media (max-width: 767.98px) {
            .stats-card {
                border-radius: 12px;
            }

            .filter-card {
                border-radius: 14px;
            }
        }

        .maid-name {
            white-space: nowrap;
        }

        .maid-name::before {
            content: attr(data-maid-display);
        }

        .job-type {
            border: 0.5px solid #2794eb;
            padding: 2px 13px;
            border-radius: 20px;
            white-space: nowrap !important;
        }

        .job-type.istqdam {
            border-color: #2794eb;
            color: #2794eb;
        }

        .job-type.egar {
            border-color: #00760a;
            color: #029f0f;
        }

        .job-type.transfer {
            border-color: #820000;
            color: #820000;
        }

        .dropdown-menu.dropdown-menu-end.maids-actions-floating-menu {
            position: fixed !important;
            top: var(--maids-actions-top) !important;
            left: var(--maids-actions-left) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 2147483647 !important;
            right: auto !important;
            transform: none !important;
            min-width: 220px;
            max-height: min(420px, calc(100vh - 24px));
            overflow-y: auto;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.maids') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))
        @php
            $stats = $stats ?? [
                'total' => 0,
                'available' => 0,
                'reserved' => 0,
                'in_accommodation' => 0,
                'backout' => 0,
                'suspended' => 0,
                'deported' => 0,
                'pinned' => 0,
            ];
            $canBulkGenerateCv = @AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT);
            $canExportGeneratedCv = @AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_PDF);
            $canSelectCvRows = $canBulkGenerateCv || $canExportGeneratedCv;
        @endphp

        <div class="page-body">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-user-friends text-primary me-2"></i>{{ helperTrans('ingaz.maids_management') }}
                        </h3>
                        <div class="text-muted small">{{ helperTrans('ingaz.total_maids') }}:
                            <span data-stat-value="total">{{ number_format($stats['total']) }}</span>
                        </div>
                    </div>
                    <div class="btn-toolbar gap-2">
                        @if ($canBulkGenerateCv)
                            <button type="button" class="btn btn-outline-danger btn-sm maid-cv-bulk-action"
                                id="bulkGenerateMaidCvsButton" disabled>
                                <i class="fas fa-cogs me-1"></i>
                                إنشاء نموذج للمحدد (<span class="maid-cv-selected-count">0</span>)
                            </button>
                        @endif
                        @if ($canExportGeneratedCv)
                            <button type="button" class="btn btn-danger btn-sm maid-cv-bulk-action"
                                id="bulkExportMaidCvsButton" disabled>
                                <i class="fas fa-file-archive me-1"></i>
                                تصدير ملفات السيرة (<span class="maid-cv-selected-count">0</span>)
                            </button>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_BULK_VISIBILITY))
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="bulkMaidVisibilityButton">
                                <i class="fas fa-eye me-1"></i>{{ helperTrans('ingaz.maids_bulk_visibility') }}
                            </button>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                            <a href="{{ $createRoute }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.add_maid') }}
                            </a>
                            <a href="{{ route('maids.bulk-import.create') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-file-upload me-1"></i>{{ helperTrans('ingaz.bulk_import') }}
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_PDF))
                            <a href="{{ route('maids.export.pdf') }}" class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i>{{ helperTrans('ingaz.export_pdf') }}
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_EXCEL))
                            <a href="{{ route('maids.export.excel') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-excel me-1"></i>{{ helperTrans('ingaz.export_excel') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="total" data-reset="true">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_maids') }}</div>
                                    <div class="fs-4 fw-bold" data-stat-value="total">{{ number_format($stats['total']) }}
                                    </div>
                                </div>
                                <span class="stats-icon bg-primary-subtle text-primary"><i class="fas fa-users"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="available" data-status="available">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.available_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-success" data-stat-value="available">
                                        {{ number_format($stats['available']) }}</div>
                                </div>
                                <span class="stats-icon bg-success-subtle text-success"><i
                                        class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="reserved" data-status="reserved">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.reserved_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-warning" data-stat-value="reserved">
                                        {{ number_format($stats['reserved']) }}</div>
                                </div>
                                <span class="stats-icon bg-warning-subtle text-warning"><i
                                        class="fas fa-hand-paper"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="contracted" data-status="contracted">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.maid_status_contracted') }}</div>
                                    <div class="fs-4 fw-bold text-primary" data-stat-value="contracted">
                                        {{ number_format($stats['contracted']) }}</div>
                                </div>
                                <span class="stats-icon bg-primary-subtle text-primary"><i class="fas fa-file-signature"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="accommodation"
                            data-status="in_accommodation">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.shelter_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-info" data-stat-value="in_accommodation">
                                        {{ number_format($stats['in_accommodation']) }}</div>
                                </div>
                                <span class="stats-icon bg-info-subtle text-info"><i class="fas fa-warehouse"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="backout" data-status="backout">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.backout_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-danger" data-stat-value="backout">
                                        {{ number_format($stats['backout']) }}</div>
                                </div>
                                <span class="stats-icon bg-danger-subtle text-danger"><i class="fas fa-undo"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="pinned" data-pinned="only">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.pinned_maids') }}</div>
                                    <div class="fs-4 fw-bold text-secondary" data-stat-value="pinned">
                                        {{ number_format($stats['pinned']) }}</div>
                                </div>
                                <span class="stats-icon bg-secondary-subtle text-secondary"><i
                                        class="fas fa-thumbtack"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="suspended" data-status="suspended">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.suspended_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-dark" data-stat-value="suspended">
                                        {{ number_format($stats['suspended']) }}</div>
                                </div>
                                <span class="stats-icon bg-dark-subtle text-dark"><i class="fas fa-ban"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card stats-card h-100 filter-stat-card" data-card="deported" data-status="deported">
                            <div class="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.deported_maids') }}
                                    </div>
                                    <div class="fs-4 fw-bold text-secondary" data-stat-value="deported">
                                        {{ number_format($stats['deported']) }}</div>
                                </div>
                                <span class="stats-icon bg-secondary-subtle text-secondary"><i
                                        class="fas fa-plane-departure"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card filter-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-semibold">
                            <i class="fas fa-filter me-2"></i>{{ helperTrans('ingaz.filters') }}
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#maidFiltersCollapse" aria-expanded="false" aria-controls="maidFiltersCollapse">
                            <i class="fas fa-chevron-down" id="maidFiltersIcon"></i>
                        </button>
                    </div>
                    <div class="card-body collapse" id="maidFiltersCollapse">
                        <form id="maidFilters" class="row g-3">
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <label class="form-label">{{ helperTrans('ingaz.search_keyword') }}</label>
                                <input type="text" id="filter-keyword" class="form-control filter-trigger"
                                    placeholder="{{ helperTrans('ingaz.search_maids_placeholder') }}">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.maid_status_filter') }}</label>
                                <select id="filter-status" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($filters['statuses'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.maid_job_type_filter') }}</label>
                                <select id="filter-job-type" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($filters['job_types'] as $jobType)
                                        <option value="{{ $jobType->id }}">
                                            {{ $jobType->title ?? ($jobType->translations->first()->title ?? '-') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                                <select id="filter-request-type" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($filters['request_types'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.nationality_filter') }}</label>
                                <select id="filter-nationality" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($filters['nationalities'] as $nationality)
                                        <option value="{{ $nationality->id }}">
                                            {{ $nationality->sub_title ?? ($nationality->title ?? '-') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.outside_agent') }}</label>
                                <select id="filter-office" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($filters['offices'] as $office)
                                        <option value="{{ $office->id }}"
                                            {{ (string) request('office_id') === (string) $office->id ? 'selected' : '' }}>
                                            {{ $office->title ?? '-' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.maid_visibility_filter') }}</label>
                                <select id="filter-visibility" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="visible">{{ helperTrans('ingaz.maid_visibility_visible') }}</option>
                                    <option value="hidden">{{ helperTrans('ingaz.maid_visibility_hidden') }}</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.maid_pinned_filter') }}</label>
                                <select id="filter-pinned" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="only">{{ helperTrans('ingaz.pinned_maids_only') }}</option>
                                    <option value="unpinned">{{ helperTrans('ingaz.unpinned_maids_only') }}</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.from_date') }}</label>
                                <input type="date" id="filter-date-from" class="form-control">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.to_date') }}</label>
                                <input type="date" id="filter-date-to" class="form-control">
                            </div>
                            <div class="col-12 row mt-4">
                                <div class="col-lg-2 col-md-4 col-6">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i>{{ helperTrans('ingaz.filter') }}
                                    </button>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">
                                        <i class="fas fa-undo me-1"></i>{{ helperTrans('ingaz.reset') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTableObject" class="table table-hover align-middle table-style"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>
                                            @if ($canSelectCvRows)
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <input type="checkbox" class="form-check-input m-0"
                                                        id="selectAllMaidCvs" title="تحديد كل السير في الصفحة الحالية">
                                                    <span>#</span>
                                                </span>
                                            @else
                                                #
                                            @endif
                                        </th>
                                        <th>{{ helperTrans('ingaz.maid_photo') }}</th>
                                        <th>{{ helperTrans('ingaz.maid_data') }}</th>
                                        <th>{{ helperTrans('ingaz.added_details') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>{{ helperTrans('ingaz.job_information') }}</th>
                                        <th>{{ helperTrans('ingaz.metrics') }}</th>
                                        <th>{{ helperTrans('ingaz.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="maidOfferModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="maidOfferModalLabel">{{ helperTrans('ingaz.manage_offer') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="maidOfferForm">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small mb-3" id="maidOfferSubtitle"></p>
                                <div id="offerFormErrors" class="alert alert-danger d-none"></div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="offerPriceInput"
                                            class="form-label">{{ helperTrans('ingaz.offer_price_new') }}</label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                            name="offer_price" id="offerPriceInput" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="offerStartsInput"
                                            class="form-label">{{ helperTrans('ingaz.offer_starts_at') }}</label>
                                        <input type="date" class="form-control" name="offer_starts_at"
                                            id="offerStartsInput">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="offerExpiresInput"
                                            class="form-label">{{ helperTrans('ingaz.offer_expires_at') }}</label>
                                        <input type="date" class="form-control" name="offer_expires_at"
                                            id="offerExpiresInput">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ helperTrans('ingaz.offer_duration') }}</label>
                                        <div class="form-control-plaintext px-3 py-2 bg-light border rounded text-muted"
                                            id="offerDurationPreview">{{ helperTrans('ingaz.not_specified') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="offerDescriptionInput"
                                            class="form-label">{{ helperTrans('ingaz.offer_description') }}</label>
                                        <textarea class="form-control" name="offer_description" id="offerDescriptionInput" rows="3"
                                            placeholder="{{ helperTrans('ingaz.offer_description_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-danger me-auto d-none"
                                    id="removeOfferButton">
                                    <i class="fas fa-trash-alt me-1"></i>{{ helperTrans('ingaz.remove_offer') }}
                                </button>
                                <div class="d-flex gap-2 ms-auto">
                                    <button type="button" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>{{ helperTrans('ingaz.save') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="back_out_modal" tabindex="-1" role="dialog"
                aria-labelledby="addContactModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header  align-items-center">
                            <h5 class="modal-title">{{ helperTrans('ingaz.add_reason') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="add-contact-box">
                                <div class="add-contact-content">
                                    <form id="back_out_form" method="POST">
                                        @csrf
                                        <input type="hidden" name="maid_id" id="maid_id">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3 contact-cost">
                                                    <textarea rows="8" class="form-control" name="backout_reason" placeholder="{{ helperTrans('ingaz.backout_reason') }}" required></textarea>

                                                    <span class="validation-text text-danger">
                                                        @error('backout_reason')
                                                            {{ $message }}
                                                        @enderror
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-success px-4" type="button"
                                                onclick="back_out_modal_func()"> {{ helperTrans('ingaz.send') }}</button>
                                            <button class="btn btn-danger  px-4" data-bs-dismiss="modal">
                                                {{ helperTrans('ingaz.close') }}</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="modal fade" id="back_out_modal_on" tabindex="-1" role="dialog"
                aria-labelledby="addContactModalTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header  align-items-center">
                            <h5 class="modal-title">{{ helperTrans('ingaz.return_cv_available') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="add-contact-box">
                                <div class="add-contact-content">
                                    <form id="back_out_modal_on_form" method="POST">
                                        @csrf
                                        <input type="hidden" name="maid_id" id="maid_id_on">

                                        <h3>{{ helperTrans('ingaz.confirm_return_cv_available') }}</h3>

                                        <div class="modal-footer">
                                            <button class="btn btn-success  px-4" type="button"
                                                onclick="back_out_modal_on_func()"> {{ helperTrans('ingaz.yes') }}</button>

                                            <button class="btn btn-danger  px-4" data-bs-dismiss="modal">
                                                {{ helperTrans('ingaz.no') }}</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

    <div class="modal fade" id="maidCvPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="height:92vh">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-pdf text-danger me-2"></i>معاينة ملف السيرة الذاتية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body p-0 bg-secondary" style="min-height:0">
                    <iframe id="maidCvPreviewFrame" title="معاينة ملف السيرة" class="w-100 h-100 border-0 bg-white"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-cv-preview-url]');
            if (!trigger) return;

            const frame = document.getElementById('maidCvPreviewFrame');
            const modalElement = document.getElementById('maidCvPreviewModal');
            frame.src = trigger.dataset.cvPreviewUrl;
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });

        document.getElementById('maidCvPreviewModal')?.addEventListener('hidden.bs.modal', function () {
            document.getElementById('maidCvPreviewFrame').src = 'about:blank';
        });
    </script>
@endsection

@section('js')
    <script src="{{ asset('admin/assets/js/bundle/dataTables.bundle.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            const statNumberFormat = new Intl.NumberFormat();
            const selectedMaidCvIds = new Set();
            const updateStats = (stats) => {
                if (!stats) {
                    return;
                }
                Object.keys(stats).forEach((key) => {
                    const value = stats[key] ?? 0;
                    document.querySelectorAll(`[data-stat-value="${key}"]`).forEach((el) => {
                        el.textContent = statNumberFormat.format(value);
                    });
                });
            };

            const updateMaidCvSelectionUi = () => {
                const selectedCount = selectedMaidCvIds.size;
                $('.maid-cv-selected-count').text(selectedCount);
                $('.maid-cv-bulk-action').prop('disabled', selectedCount === 0);

                const pageCheckboxes = $('#dataTableObject .maid-cv-select');
                const checkedOnPage = pageCheckboxes.filter(':checked').length;
                const selectAll = document.getElementById('selectAllMaidCvs');
                if (selectAll) {
                    selectAll.checked = pageCheckboxes.length > 0 && checkedOnPage === pageCheckboxes.length;
                    selectAll.indeterminate = checkedOnPage > 0 && checkedOnPage < pageCheckboxes.length;
                }
            };

            const restoreMaidCvSelection = () => {
                $('#dataTableObject .maid-cv-select').each(function() {
                    this.checked = selectedMaidCvIds.has(String(this.value));
                });
                updateMaidCvSelectionUi();
            };

            function closeMaidsActionsDropdown() {
                $('#maidsActionsFloatingMenu').remove();

                $('#dataTableObject .actions-dropdown').each(function() {
                    $(this).removeClass('show');
                    $(this).find('.actions-btn').attr('aria-expanded', 'false').removeClass('show');
                    $(this).find('.dropdown-menu').removeClass('show').removeAttr('style');
                });
            }

            function placeMaidsActionsMenu($button, $menu) {
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
                    '--maids-actions-top': top + 'px',
                    '--maids-actions-left': left + 'px',
                    right: 'auto',
                    transform: 'none',
                    zIndex: 2147483647,
                    display: 'block',
                    minWidth: menuWidth + 'px'
                });
            }

            const table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('maids.index') }}",
                    data: function(d) {
                        d.keyword = $('#filter-keyword').val();
                        d.status = $('#filter-status').val();
                        d.job_type_id = $('#filter-job-type').val();
                        d.request_type = $('#filter-request-type').val();
                        d.nationality_id = $('#filter-nationality').val();
                        d.office_id = $('#filter-office').val();
                        d.visibility = $('#filter-visibility').val();
                        d.pinned = $('#filter-pinned').val();
                        d.date_from = $('#filter-date-from').val();
                        d.date_to = $('#filter-date-to').val();
                    },
                    dataSrc: function(json) {
                        if (json && json.stats) {
                            updateStats(json.stats);
                        }
                        return json.data || [];
                    }
                },
                columns: [{
                        data: 'worker_display_id',
                        name: 'arabia_worker_number',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'photo',
                        name: 'photo',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'profile',
                        name: 'profile',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'added_info',
                        name: 'created_at',
                        orderable: true,
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'job_info',
                        name: 'job_info',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'metrics',
                        name: 'metrics',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                language: {
                    url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
                },
                drawCallback: function() {
                    $('#dataTableObject .actions-btn').removeAttr('data-bs-toggle data-bs-auto-close data-bs-display');
                    closeMaidsActionsDropdown();
                    restoreMaidCvSelection();
                }
            });

            $(document).on('change', '#dataTableObject .maid-cv-select', function() {
                const id = String(this.value);
                if (this.checked) {
                    selectedMaidCvIds.add(id);
                } else {
                    selectedMaidCvIds.delete(id);
                }
                updateMaidCvSelectionUi();
            });

            $('#selectAllMaidCvs').on('change', function() {
                const checked = this.checked;
                $('#dataTableObject .maid-cv-select').each(function() {
                    const id = String(this.value);
                    this.checked = checked;
                    if (checked) {
                        selectedMaidCvIds.add(id);
                    } else {
                        selectedMaidCvIds.delete(id);
                    }
                });
                updateMaidCvSelectionUi();
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

                closeMaidsActionsDropdown();

                if (isOpen) {
                    return;
                }

                const $menu = $('<ul id="maidsActionsFloatingMenu" class="dropdown-menu dropdown-menu-end shadow-lg show maids-actions-floating-menu"></ul>');
                $menu.html($sourceMenu.html());
                $('body').append($menu);

                $dropdown.addClass('show');
                $button.attr('aria-expanded', 'true').addClass('show');
                placeMaidsActionsMenu($button, $menu);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#dataTableObject .actions-dropdown, #maidsActionsFloatingMenu').length) {
                    closeMaidsActionsDropdown();
                }
            });

            $(document).on('click', '#maidsActionsFloatingMenu .dropdown-item, #maidsActionsFloatingMenu form button', function() {
                setTimeout(closeMaidsActionsDropdown, 0);
            });

            const statsCards = $('.filter-stat-card');
            const defaultStatCard = statsCards.filter('[data-card="total"]').first();
            const maidFiltersForm = document.getElementById('maidFilters');

            const highlightStatCard = (card) => {
                statsCards.removeClass('active');
                if (card && card.length) {
                    card.addClass('active');
                }
            };

            if (defaultStatCard.length) {
                highlightStatCard(defaultStatCard);
            }

            const resetFiltersForm = () => {
                if (maidFiltersForm) {
                    maidFiltersForm.reset();
                }
            };

            const clearStatHighlights = () => highlightStatCard(null);

            statsCards.on('click', function() {
                const card = $(this);
                if (!maidFiltersForm) {
                    return;
                }

                resetFiltersForm();

                const statusVal = card.data('status');
                const pinnedVal = card.data('pinned');
                const isResetCard = !!card.data('reset');

                if (!isResetCard) {
                    if (typeof statusVal !== 'undefined') {
                        $('#filter-status').val(statusVal);
                    }
                    if (typeof pinnedVal !== 'undefined') {
                        $('#filter-pinned').val(pinnedVal);
                    }
                }

                table.draw();
                highlightStatCard(card);
            });

            const offerModalEl = document.getElementById('maidOfferModal');
            const offerModal = offerModalEl ? new bootstrap.Modal(offerModalEl) : null;
            const offerForm = $('#maidOfferForm');
            const offerErrors = $('#offerFormErrors');
            const offerSubtitle = $('#maidOfferSubtitle');
            const removeOfferBtn = $('#removeOfferButton');
            const offerDurationPreview = $('#offerDurationPreview');
            const offerRouteTemplate = "{{ route('maids.offer', ['id' => 'MAID_ID']) }}";
            const offerSubtitleTemplate = "{{ helperTrans('ingaz.offer_for_maid') }}";
            const csrfToken = $('meta[name=\"csrf-token\"]').attr('content');
            const daysLabel = "{{ helperTrans('ingaz.days') }}";
            const notSpecifiedLabel = "{{ helperTrans('ingaz.not_specified') }}";

            $('#bulkGenerateMaidCvsButton').on('click', function() {
                const maidIds = Array.from(selectedMaidCvIds);
                if (!maidIds.length) {
                    return;
                }

                if (maidIds.length > 50) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'عدد السير أكبر من الحد المسموح',
                        text: 'يمكن إنشاء 50 نموذجًا كحد أقصى في العملية الواحدة.'
                    });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: 'إنشاء نماذج السيرة الذاتية',
                    text: `سيتم إنشاء أو إعادة إنشاء ${maidIds.length} نموذج {{ ($cvOutputType ?? 'pdf') === 'image' ? 'صورة' : 'PDF' }}. هل تريد المتابعة؟`,
                    showCancelButton: true,
                    confirmButtonText: 'نعم، ابدأ الإنشاء',
                    cancelButtonText: @json(helperTrans('ingaz.cancel')),
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'جاري إنشاء نماذج السيرة',
                        text: 'قد تستغرق العملية بعض الوقت حسب عدد السير المحددة.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    $.ajax({
                        url: @json(route('maids.generate-cv.bulk')),
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            maid_ids: maidIds
                        },
                        success: function(response) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: response.failed_count > 0 ? 'warning' : 'success',
                                title: response.message,
                                text: response.failed_count > 0
                                    ? 'يمكن إعادة المحاولة للنماذج التي تعذر إنشاؤها.'
                                    : 'يمكنك الآن تصدير الملفات المحددة.',
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر إنشاء نماذج السيرة',
                                text: xhr.responseJSON?.message
                                    || xhr.responseJSON?.errors?.maid_ids?.[0]
                                    || @json(helperTrans('ingaz.unexpected_error'))
                            });
                        }
                    });
                });
            });

            $('#bulkExportMaidCvsButton').on('click', function() {
                const maidIds = Array.from(selectedMaidCvIds);
                if (!maidIds.length) {
                    return;
                }

                if (maidIds.length > 100) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'عدد السير أكبر من الحد المسموح',
                        text: 'يمكن تصدير 100 سيرة كحد أقصى في العملية الواحدة.'
                    });
                    return;
                }

                const form = $('<form>', {
                    method: 'POST',
                    action: @json(route('maids.generated-cvs.export')),
                    target: '_blank',
                    class: 'd-none'
                });
                form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: csrfToken
                }));
                maidIds.forEach((id) => {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'maid_ids[]',
                        value: id
                    }));
                });
                $('body').append(form);
                form.trigger('submit');
                setTimeout(() => form.remove(), 1000);
            });

            $('#bulkMaidVisibilityButton').on('click', function() {
                const button = $(this);

                Swal.fire({
                    icon: 'question',
                    title: @json(helperTrans('ingaz.maids_bulk_visibility_confirm_title')),
                    text: @json(helperTrans('ingaz.maids_bulk_visibility_confirm_text')),
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: @json(helperTrans('ingaz.show_all_maids')),
                    denyButtonText: @json(helperTrans('ingaz.hide_all_maids')),
                    cancelButtonText: @json(helperTrans('ingaz.cancel')),
                    confirmButtonColor: '#198754',
                    denyButtonColor: '#dc3545'
                }).then((result) => {
                    if (!result.isConfirmed && !result.isDenied) {
                        return;
                    }

                    button.prop('disabled', true);
                    $.ajax({
                        url: @json(route('maids.bulk-visibility')),
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            is_show: result.isConfirmed ? 1 : 0
                        },
                        success: function(response) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: response.message || @json(helperTrans('ingaz.updated_successfully')),
                                timer: 2500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: @json(helperTrans('ingaz.error')),
                                text: xhr.responseJSON?.message || @json(helperTrans('ingaz.unexpected_error'))
                            });
                        },
                        complete: function() {
                            button.prop('disabled', false);
                        }
                    });
                });
            });

            const resetOfferErrors = () => {
                offerErrors.addClass('d-none').empty();
            };

            const computeDurationLabel = (start, end, fallback = '') => {
                if (start && end) {
                    const startDate = new Date(start);
                    const endDate = new Date(end);
                    if (!Number.isNaN(startDate.getTime()) && !Number.isNaN(endDate.getTime()) && endDate >=
                        startDate) {
                        const diffMs = endDate - startDate;
                        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
                        if (diffDays > 0) {
                            return diffDays + ' ' + daysLabel;
                        }
                    }
                }
                if (fallback) {
                    const trimmed = String(fallback).trim();
                    if (/^\d+$/.test(trimmed)) {
                        return trimmed + ' ' + daysLabel;
                    }
                    return trimmed;
                }
                return '';
            };

            const refreshDurationPreview = () => {
                const startVal = $('#offerStartsInput').val();
                const endVal = $('#offerExpiresInput').val();
                const stored = offerForm.data('initialDuration') || '';
                const label = computeDurationLabel(startVal, endVal, stored);
                offerDurationPreview.text(label || notSpecifiedLabel);
            };

            $('#offerStartsInput, #offerExpiresInput').on('change', refreshDurationPreview);

            $(document).on('click', '.manage-offer', function(e) {
                e.preventDefault();
                if (!offerModal) {
                    return;
                }

                const btn = $(this);
                const maidId = btn.data('id');
                const maidName = btn.data('name') || '';
                const offerPrice = btn.data('offerPrice');
                const offerDuration = btn.data('offerDuration') || '';
                const offerDescription = btn.data('offerDescription') || '';
                const offerStart = btn.data('offerStart') || '';
                const offerExpire = btn.data('offerExpire') || '';

                offerForm[0].reset();
                offerForm.data('maidId', maidId);
                offerForm.data('initialDuration', offerDuration ? String(offerDuration) : '');
                resetOfferErrors();

                if (maidName) {
                    offerSubtitle.text(offerSubtitleTemplate.replace(':name', maidName));
                } else {
                    offerSubtitle.text('');
                }

                if (offerPrice !== undefined && offerPrice !== null && offerPrice !== '') {
                    $('#offerPriceInput').val(offerPrice);
                } else {
                    $('#offerPriceInput').val('');
                }

                $('#offerDescriptionInput').val(offerDescription);
                $('#offerStartsInput').val(offerStart);
                $('#offerExpiresInput').val(offerExpire);
                refreshDurationPreview();

                const hasExistingOffer = offerPrice !== undefined && offerPrice !== null && offerPrice !==
                    '';
                removeOfferBtn.toggleClass('d-none', !hasExistingOffer);
                removeOfferBtn.data('maidId', maidId);

                offerModal.show();
            });

            offerForm.on('submit', function(e) {
                e.preventDefault();
                const maidId = $(this).data('maidId');
                if (!maidId) {
                    return;
                }

                const url = offerRouteTemplate.replace('MAID_ID', maidId);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: offerForm.serialize(),
                    success: function(response) {
                        resetOfferErrors();
                        if (offerModal) {
                            offerModal.hide();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: response.message ||
                                '{{ helperTrans('ingaz.updated_successfully') }}',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422 && xhr.responseJSON) {
                            const payload = xhr.responseJSON;
                            const errors = payload.errors;
                            let messages = [];

                            if (errors) {
                                Object.values(errors).forEach(function(item) {
                                    if (Array.isArray(item)) {
                                        messages = messages.concat(item);
                                    } else {
                                        messages.push(item);
                                    }
                                });
                            } else if (payload.message) {
                                messages.push(payload.message);
                            }

                            if (messages.length) {
                                offerErrors.removeClass('d-none').html(messages.map(function(
                                    message) {
                                    return '<div>' + message + '</div>';
                                }).join(''));
                            } else {
                                resetOfferErrors();
                            }
                        } else {
                            resetOfferErrors();
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ helperTrans('ingaz.unexpected_error') }}'
                            });
                        }
                    }
                });
            });

            removeOfferBtn.on('click', function() {
                const maidId = $(this).data('maidId');
                if (!maidId) {
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: '{{ helperTrans('ingaz.remove_offer') }}',
                    text: '{{ helperTrans('ingaz.offer_remove_confirmation') }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ helperTrans('ingaz.confirm') }}',
                    cancelButtonText: '{{ helperTrans('ingaz.cancel') }}'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    const url = offerRouteTemplate.replace('MAID_ID', maidId);

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: csrfToken,
                            clear_offer: 1
                        },
                        success: function(response) {
                            resetOfferErrors();
                            offerForm.data('initialDuration', '');
                            offerDurationPreview.text(notSpecifiedLabel);
                            if (offerModal) {
                                offerModal.hide();
                            }
                            Swal.fire({
                                icon: 'success',
                                title: response.message ||
                                    '{{ helperTrans('ingaz.offer_cleared_successfully') }}',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            table.ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ helperTrans('ingaz.unexpected_error') }}'
                            });
                        }
                    });
                });
            });

            $('#maidFilters').on('submit', function(e) {
                e.preventDefault();
                clearStatHighlights();
                table.draw();
            });

            $('#resetFilters').on('click', function() {
                resetFiltersForm();
                highlightStatCard(defaultStatCard);
                table.draw();
            });

            $('.filter-trigger').on('change', function() {
                clearStatHighlights();
                table.draw();
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
                                    text: response.message ||
                                        '{{ helperTrans('ingaz.unexpected_error') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ helperTrans('ingaz.unexpected_error') }}'
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
                const isPinned = button.data('pinned') === 'true';
                Swal.fire({
                    icon: 'question',
                    title: isPinned ? '{{ helperTrans('ingaz.confirm_unpin') }}' :
                        '{{ helperTrans('ingaz.confirm_pin') }}',
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
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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
                                    text: response.message ||
                                        '{{ helperTrans('ingaz.unexpected_error') }}'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('ingaz.error') }}',
                                text: xhr.responseJSON?.message ||
                                    '{{ helperTrans('ingaz.unexpected_error') }}'
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

            const collapseEl = document.getElementById('maidFiltersCollapse');
            if (collapseEl) {
                const updateIconState = () => {
                    if ($(collapseEl).hasClass('show')) {
                        $('#maidFiltersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $('#maidFiltersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                };

                updateIconState();

                collapseEl.addEventListener('show.bs.collapse', function() {
                    $('#maidFiltersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                });
                collapseEl.addEventListener('hide.bs.collapse', function() {
                    $('#maidFiltersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                });
            }

            window.back_out_modal_func = function() {
                $('#back_out_modal').modal('hide');

                $.ajax({
                    url: "{{ route('maids.backout') }}",
                    type: 'Post',
                    data: $("#back_out_form").serialize(),
                    success: function(response) {
                        console.log(response);
                        if (response.data.success == true) {
                            table.ajax.reload();

                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                        }
                    },
                });
            };

            window.back_out_modal_on_func = function() {
                $('#back_out_modal_on').modal('hide');

                $.ajax({
                    url: "{{ route('maids.remove_backout') }}",
                    type: 'Post',
                    data: $("#back_out_modal_on_form").serialize(),
                    success: function(response) {
                        console.log(response);
                        if (response.data.success == true) {
                            table.ajax.reload();

                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                        }
                    },
                });
            };

            window.back_out_on = function(id) {
                $('#back_out_modal_on').modal('show', {
                    backdrop: 'static'
                });
                $("#maid_id_on").val(id);
            };

            window.back_out = function(id) {
                console.log("Backout Here!");

                $('#back_out_modal').modal('show', {
                    backdrop: 'static'
                });
                $("#maid_id").val(id);
            };


            window.suspendCv = function suspendCv(id, status) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('maids.suspendCv') }}",
                    data: {
                        'id': id,
                        'status': status
                    },
                    async: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.data.success == true) {
                            table.ajax.reload();

                            Swal.fire({
                                icon: 'success',
                                title: response.data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                        }
                    },

                });
            };
        });
    </script>
@endsection
