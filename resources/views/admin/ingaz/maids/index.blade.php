@extends('admin.layout.indexs.index')

@section('style')
<style>
    .stats-card {
        border-radius: 16px;
        border: 1px solid #eef2f6;
        box-shadow: 0 6px 18px rgba(15, 34, 58, 0.05);
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

    .maids-table th {
        font-size: 0.82rem;
        text-transform: uppercase;
        background-color: #f8f9fc;
        letter-spacing: 0.06em;
    }

    .badge {
        border-radius: 999px;
    }

    .dataTables_wrapper .table-responsive,
    .table-responsive {
        overflow: visible !important;
    }

    .dataTables_wrapper {
        overflow: visible;
    }

    @media (max-width: 767.98px) {
        .stats-card {
            border-radius: 12px;
        }

        .filter-card {
            border-radius: 14px;
        }
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
            'pinned' => 0,
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="btn-toolbar gap-2">
            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                <a href="{{ $createRoute }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i>{{ helperTrans('ingaz.add_maid') }}
                </a>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_PDF))
                <a href="{{ route('maids.export.pdf') }}" class="btn btn-outline-danger" target="_blank">
                    <i class="fas fa-file-pdf me-1"></i>{{ helperTrans('ingaz.export_pdf') }}
                </a>
            @endif
            @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_EXCEL))
                <a href="{{ route('maids.export.excel') }}" class="btn btn-outline-success">
                    <i class="fas fa-file-excel me-1"></i>{{ helperTrans('ingaz.export_excel') }}
                </a>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_maids') }}</div>
                        <div class="fs-4 fw-bold">{{ number_format($stats['total']) }}</div>
                    </div>
                    <span class="stats-icon bg-primary-subtle text-primary"><i class="fas fa-users"></i></span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.available_maids') }}</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($stats['available']) }}</div>
                    </div>
                    <span class="stats-icon bg-success-subtle text-success"><i class="fas fa-check-circle"></i></span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.reserved_maids') }}</div>
                        <div class="fs-4 fw-bold text-warning">{{ number_format($stats['reserved']) }}</div>
                    </div>
                    <span class="stats-icon bg-warning-subtle text-warning"><i class="fas fa-hand-paper"></i></span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.shelter_maids') }}</div>
                        <div class="fs-4 fw-bold text-info">{{ number_format($stats['in_accommodation']) }}</div>
                    </div>
                    <span class="stats-icon bg-info-subtle text-info"><i class="fas fa-warehouse"></i></span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.backout_maids') }}</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($stats['backout']) }}</div>
                    </div>
                    <span class="stats-icon bg-danger-subtle text-danger"><i class="fas fa-undo"></i></span>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold">{{ helperTrans('ingaz.pinned_maids') }}</div>
                        <div class="fs-4 fw-bold text-secondary">{{ number_format($stats['pinned']) }}</div>
                    </div>
                    <span class="stats-icon bg-secondary-subtle text-secondary"><i class="fas fa-thumbtack"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body">
            <form id="maidFilters" class="row g-3 align-items-end">
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label class="form-label">{{ helperTrans('ingaz.search_keyword') }}</label>
                    <input type="text" id="filter-keyword" class="form-control filter-trigger" placeholder="{{ helperTrans('ingaz.search_maids_placeholder') }}">
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.maid_status_filter') }}</label>
                    <select id="filter-status" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($filters['statuses'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.maid_job_type_filter') }}</label>
                    <select id="filter-job-type" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($filters['job_types'] as $jobType)
                            <option value="{{ $jobType->id }}">{{ $jobType->title ?? ($jobType->translations->first()->title ?? '-') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.nationality_filter') }}</label>
                    <select id="filter-nationality" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($filters['nationalities'] as $nationality)
                            <option value="{{ $nationality->id }}">{{ $nationality->sub_title ?? $nationality->title ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label class="form-label">{{ helperTrans('ingaz.maid_office_filter') }}</label>
                    <select id="filter-office" class="form-select filter-trigger">
                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                        @foreach($filters['offices'] as $office)
                            <option value="{{ $office->id }}">{{ $office->title ?? ($office->translations->first()->title ?? '-') }}</option>
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
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableObject" class="table table-hover align-middle maids-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ helperTrans('ingaz.profile') }}</th>
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
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
<script src="{{ asset('admin/assets/js/bundle/dataTables.bundle.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function () {
        const table = $('#dataTableObject').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ $dataTableRoute }}",
                data: function (d) {
                    d.keyword = $('#filter-keyword').val();
                    d.status = $('#filter-status').val();
                    d.job_type_id = $('#filter-job-type').val();
                    d.nationality_id = $('#filter-nationality').val();
                    d.office_id = $('#filter-office').val();
                    d.visibility = $('#filter-visibility').val();
                    d.pinned = $('#filter-pinned').val();
                    d.date_from = $('#filter-date-from').val();
                    d.date_to = $('#filter-date-to').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'profile', name: 'profile', orderable: false, searchable: false},
                {data: 'status_badge', name: 'status_badge', orderable: false, searchable: false},
                {data: 'job_info', name: 'job_info', orderable: false, searchable: false},
                {data: 'metrics', name: 'metrics', orderable: false, searchable: false},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[1, 'desc']],
            language: {
                url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
            }
        });

        $('#maidFilters').on('submit', function (e) {
            e.preventDefault();
            table.draw();
        });

        $('#resetFilters').on('click', function () {
            $('#maidFilters')[0].reset();
            table.draw();
        });

        $('.filter-trigger').on('change', function () {
            table.draw();
        });

        $(document).on('click', '.update-status', function (e) {
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
                    success: function (response) {
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
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                        });
                    },
                    complete: function () {
                        button.prop('disabled', false);
                    }
                });
            });
        });

        $(document).on('click', '.toggle-pin', function (e) {
            e.preventDefault();
            const button = $(this);
            const id = button.data('id');
            const isPinned = button.data('pinned') === 'true';
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
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function (response) {
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
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ helperTrans('ingaz.error') }}',
                            text: xhr.responseJSON?.message || '{{ helperTrans('ingaz.unexpected_error') }}'
                        });
                    }
                });
            });
        });
    });
</script>
@endsection
