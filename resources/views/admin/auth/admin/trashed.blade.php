@extends('admin.layout.indexs.index')
@section("style")
    <style>
        .trashed-admins-page {
            --ta-border: #e5e7eb;
            --ta-soft: #f8fafc;
            --ta-text: #111827;
            --ta-muted: #6b7280;
            --ta-danger: #dc2626;
            --ta-danger-soft: #fef2f2;
            --ta-success: #16a34a;
        }

        .trashed-admins-page .ta-hero {
            position: relative;
            overflow: hidden;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            background: linear-gradient(135deg, #111827 0%, #1f2937 55%, #374151 100%);
            color: #fff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18);
        }

        .trashed-admins-page .ta-hero::after {
            content: "";
            position: absolute;
            inset-inline-end: -60px;
            top: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.35), transparent 70%);
        }

        .trashed-admins-page .ta-hero-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .trashed-admins-page .ta-hero h1 {
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0;
            color: #fff;
        }

        .trashed-admins-page .ta-hero p {
            margin: 0.35rem 0 0;
            color: rgba(255, 255, 255, 0.78);
            max-width: 720px;
            line-height: 1.7;
        }

        .trashed-admins-page .ta-hero .btn-back {
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            border-radius: 8px;
            min-height: 42px;
            white-space: nowrap;
        }

        .trashed-admins-page .ta-hero .btn-back:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .trashed-admins-page .ta-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-top: -1.35rem;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }

        .trashed-admins-page .ta-stat {
            background: #fff;
            border: 1px solid var(--ta-border);
            border-radius: 12px;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        }

        .trashed-admins-page .ta-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .trashed-admins-page .ta-stat-icon.is-danger { background: var(--ta-danger-soft); color: var(--ta-danger); }
        .trashed-admins-page .ta-stat-icon.is-warning { background: #fffbeb; color: #d97706; }
        .trashed-admins-page .ta-stat-icon.is-info { background: #eff6ff; color: #2563eb; }

        .trashed-admins-page .ta-stat-label {
            color: var(--ta-muted);
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .trashed-admins-page .ta-stat-value {
            color: var(--ta-text);
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .trashed-admins-page .ta-stat-value.is-small {
            font-size: 0.98rem;
        }

        .trashed-admins-page .ta-toolbar {
            margin-top: 1.25rem;
            border: 1px solid var(--ta-border);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }

        .trashed-admins-page .ta-toolbar .form-label {
            color: var(--ta-text);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .trashed-admins-page .ta-toolbar .form-control,
        .trashed-admins-page .ta-toolbar .form-select,
        .trashed-admins-page .ta-toolbar .btn {
            min-height: 42px;
            border-radius: 8px;
        }

        .trashed-admins-page .ta-search {
            position: relative;
        }

        .trashed-admins-page .ta-search .fa-search {
            position: absolute;
            inset-inline-start: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ta-muted);
        }

        .trashed-admins-page .ta-search .form-control {
            padding-inline-start: 2.4rem;
        }

        .trashed-admins-page .ta-hint {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            margin-top: 1rem;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px dashed #fca5a5;
            background: var(--ta-danger-soft);
            color: #991b1b;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .trashed-admins-page .ta-table-shell {
            margin-top: 1rem;
            background: #fff;
            border: 1px solid var(--ta-border);
            border-radius: 12px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .trashed-admins-page #dataTableObject_wrapper {
            padding: 1rem;
            overflow-x: auto;
        }

        .trashed-admins-page .dataTables_wrapper .row:first-child,
        .trashed-admins-page .dataTables_wrapper .row:last-child {
            align-items: center;
            row-gap: 0.75rem;
        }

        .trashed-admins-page .dataTables_filter {
            display: none;
        }

        .trashed-admins-page .dataTables_length select {
            border-radius: 8px;
            border-color: var(--ta-border);
            min-height: 38px;
        }

        #dataTableObject {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        #dataTableObject thead th {
            background: var(--ta-soft) !important;
            color: var(--ta-text) !important;
            border-top: 1px solid var(--ta-border);
            border-bottom: 1px solid var(--ta-border) !important;
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
            background: #fff7f7;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 58px;
            min-width: 58px;
            text-align: center;
            color: var(--ta-muted);
        }

        .trashed-admin-name {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 180px;
        }

        .trashed-admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ta-danger-soft);
            color: var(--ta-danger);
            font-weight: 800;
            flex-shrink: 0;
        }

        .trashed-admin-date {
            white-space: nowrap;
        }

        .trashed-admin-actions {
            display: flex;
            gap: 0.4rem;
            flex-wrap: nowrap;
            justify-content: flex-end;
        }

        .trashed-admin-actions .btn {
            border-radius: 8px;
            white-space: nowrap;
            font-weight: 700;
        }

        .trashed-admins-page .ta-empty {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--ta-muted);
        }

        .trashed-admins-page .ta-empty .fa {
            font-size: 2.6rem;
            color: var(--ta-success);
            margin-bottom: 0.75rem;
        }

        .trashed-admins-page .ta-empty h5 {
            color: var(--ta-text);
            font-weight: 800;
        }

        @media (max-width: 767.98px) {
            .trashed-admins-page .ta-hero {
                padding: 1.25rem;
            }

            .trashed-admins-page .ta-stats {
                margin-top: 1rem;
                padding: 0;
            }

            .trashed-admin-actions {
                flex-wrap: wrap;
                justify-content: flex-start;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ __('admin.trashed_admins.title') }}
@endsection
@section('page-links')
    <li class="breadcrumb-item"><a href="{{ route('admins.index') }}">{{ __('auth.admins') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('admin.trashed_admins.title') }}</li>
@endsection
@section('content')
    <div class="trashed-admins-page">
        <div class="ta-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="ta-hero-icon"><i class="fa fa-user-times"></i></span>
                <div>
                    <h1>{{ __('admin.trashed_admins.title') }}</h1>
                    <p>{{ __('admin.trashed_admins.subtitle') }}</p>
                </div>
            </div>
            <a href="{{ route('admins.index') }}" class="btn btn-back">
                <i class="fa fa-arrow-right ms-1"></i> {{ __('admin.trashed_admins.back_to_admins') }}
            </a>
        </div>

        <div class="ta-stats">
            <div class="ta-stat">
                <span class="ta-stat-icon is-danger"><i class="fa fa-trash"></i></span>
                <div>
                    <div class="ta-stat-label">{{ __('admin.trashed_admins.total') }}</div>
                    <div class="ta-stat-value" id="taTotalCount">{{ $totalTrashed }}</div>
                </div>
            </div>
            <div class="ta-stat">
                <span class="ta-stat-icon is-warning"><i class="fa fa-calendar"></i></span>
                <div>
                    <div class="ta-stat-label">{{ __('admin.trashed_admins.last_30_days') }}</div>
                    <div class="ta-stat-value">{{ $trashedLast30Days }}</div>
                </div>
            </div>
            <div class="ta-stat">
                <span class="ta-stat-icon is-info"><i class="fa fa-clock-o"></i></span>
                <div>
                    <div class="ta-stat-label">{{ __('admin.trashed_admins.latest_deletion') }}</div>
                    <div class="ta-stat-value is-small">
                        {{ $latestDeletion ? \Illuminate\Support\Carbon::parse($latestDeletion)->format('Y-m-d H:i') : __('admin.trashed_admins.none_yet') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card ta-toolbar">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="{{ ($branchesEnabled ?? false) ? 'col-md-6' : 'col-md-9' }}">
                        <label class="form-label" for="taSearch">{{ __('dataTable.sSearch') }}</label>
                        <div class="ta-search">
                            <i class="fa fa-search"></i>
                            <input type="search" id="taSearch" class="form-control" value="{{ $initialSearch }}"
                                   placeholder="{{ __('admin.trashed_admins.search_placeholder') }}">
                        </div>
                    </div>
                    @if($branchesEnabled ?? false)
                        <div class="col-md-3">
                            <label class="form-label" for="filter_branch_id">{{ __('admin.branches') }}</label>
                            <select id="filter_branch_id" class="form-select">
                                <option value="">{{ __('admin.all') }}</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3 d-flex gap-2">
                        <button id="taRefresh" class="btn btn-outline-secondary w-100" type="button">
                            <i class="fa fa-refresh"></i> {{ __('admin.reset') }}
                        </button>
                    </div>
                </div>
                @if($canForceDelete)
                    <div class="ta-hint">
                        <i class="fa fa-exclamation-triangle mt-1"></i>
                        <span>{{ __('admin.trashed_admins.force_delete_hint') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="ta-table-shell">
            <table id="dataTableObject" class="table display dataTable table-hover align-middle" style="width:100%">
                <thead>
                <tr>
                    <th>{{ __('auth.SL') }}</th>
                    <th>{{ __('auth.name') }}</th>
                    <th>{{ __('admin.username') }}</th>
                    <th>{{ __('auth.email') }}</th>
                    <th>{{ __('admin.contact_phone') }}</th>
                    <th>{{ helperTrans('ingaz.role') }}</th>
                    @if($branchesEnabled ?? false)
                        <th>{{ __('admin.branches') }}</th>
                    @endif
                    <th>{{ __('admin.trashed_admins.deleted_at') }}</th>
                    <th class="text-end">{{ __('auth.actions') }}</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            const baseUrl = "{{ route('admins.trashed') }}";
            const adminSwal = window.Swal || window.swal;
            const reassignmentAdmins = @json($reassignmentAdmins->map(fn($admin) => ['id' => $admin->id, 'name' => $admin->name])->values());
            const emptyTableHtml = '<div class="ta-empty"><i class="fa fa-check-circle"></i><h5>{{ __('admin.trashed_admins.empty_title') }}</h5><div>{{ __('admin.trashed_admins.empty_text') }}</div></div>';
            let tableInstance;

            const columns = [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, defaultContent: ''},
                {data: 'name', orderable: true, searchable: true},
                {data: 'username', orderable: true, searchable: true},
                {data: 'email', orderable: true, searchable: true},
                {data: 'phone', orderable: false, searchable: true},
                {data: 'role', orderable: true, searchable: true},
                @if($branchesEnabled ?? false)
                {data: 'branch', orderable: true, searchable: true},
                @endif
                {data: 'deleted_at', orderable: true, searchable: false},
                {data: 'actions', orderable: false, searchable: false, className: 'text-end'}
            ];

            function dataTableLanguage() {
                return {
                    "sProcessing": "{{ trans('dataTable.sProcessing') }}",
                    "sLengthMenu": "{{ trans('dataTable.sLengthMenu') }}",
                    "sZeroRecords": "{{ trans('dataTable.sZeroRecords') }}",
                    "sInfo": "{{ trans('dataTable.sInfo') }}",
                    "sInfoEmpty": "{{ trans('dataTable.sInfoEmpty') }}",
                    "sInfoFiltered": "{{ trans('dataTable.sInfoFiltered') }}",
                    "sInfoPostFix": "",
                    "sSearch": "{{ trans('dataTable.sSearch') }}:",
                    "sUrl": "",
                    "sEmptyTable": emptyTableHtml,
                    "oPaginate": {
                        "sFirst": "{{ trans('dataTable.sFirst') }}",
                        "sPrevious": "{{ trans('dataTable.sPrevious') }}",
                        "sNext": "{{ trans('dataTable.sNext') }}",
                        "sLast": "{{ trans('dataTable.sLast') }}"
                    }
                };
            }

            function currentUrl() {
                const params = new URLSearchParams();
                const branchId = $('#filter_branch_id').length ? $('#filter_branch_id').val() : '';
                if (branchId) params.append('branch_id', branchId);
                return baseUrl + (params.toString() ? '?' + params.toString() : '');
            }

            function initTable() {
                if (tableInstance) {
                    tableInstance.clear().destroy();
                }

                tableInstance = $('#dataTableObject').DataTable({
                    responsive: true,
                    autoWidth: false,
                    ordering: true,
                    searching: true,
                    iDisplayLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "{{ __('admin.all') }}"]],
                    processing: true,
                    serverSide: false,
                    ajax: currentUrl(),
                    columns: columns,
                    language: dataTableLanguage(),
                    order: [[{{ ($branchesEnabled ?? false) ? 7 : 6 }}, 'desc']],
                    drawCallback: function (settings) {
                        const total = settings.json && settings.json.data ? settings.json.data.length : 0;
                        $('#taTotalCount').text(total);
                    },
                    initComplete: function () {
                        const initial = $('#taSearch').val();
                        if (initial) {
                            tableInstance.search(initial).draw();
                        }
                    }
                });
            }

            initTable();

            $('#taSearch').on('input', function () {
                if (tableInstance) {
                    tableInstance.search($(this).val()).draw();
                }
            });

            $('#filter_branch_id').on('change', initTable);

            $('#taRefresh').on('click', function () {
                $('#taSearch').val('');
                $('#filter_branch_id').val('');
                initTable();
            });

            function showSuccess(message) {
                if (adminSwal && typeof adminSwal.fire === 'function') {
                    adminSwal.fire({
                        icon: 'success',
                        title: message,
                        confirmButtonText: "{{ __('buttons.close') }}",
                        timer: 2500,
                        timerProgressBar: true
                    });
                } else if (typeof successToster === 'function') {
                    successToster("{{ __('auth.done successfully') }}", message);
                }
            }

            $(document).on('click', '.trashed-restore-button', function (e) {
                e.preventDefault();
                const button = $(this);
                const name = button.data('name') || '';
                const url = button.data('restore-route');

                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire({
                        title: "{{ __('admin.trashed_admins.restore_title') }}",
                        text: "{{ __('admin.trashed_admins.restore_text') }}".replace(':name', name),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#16a34a',
                        confirmButtonText: "{{ __('admin.trashed_admins.restore_confirm') }}",
                        cancelButtonText: "{{ __('buttons.cancel') }}"
                    })
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.trashed_admins.restore_title') }}")});

                confirmation.then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    button.prop('disabled', true);
                    $.post({
                        url: url,
                        data: {_token: "{{ csrf_token() }}"},
                        success: function (response) {
                            showSuccess(response.message || "{{ __('admin.employee_restored_successfully', ['name' => '']) }}");
                            initTable();
                        },
                        error: function (errorObj) {
                            button.prop('disabled', false);
                            handleErrorResponce(errorObj);
                        }
                    });
                });
            });

            function forceDeleteAdmin(button, forcedCounts) {
                const adminId = Number(button.data('id'));
                const deleteRoute = button.data('delete-route');
                const adminName = button.data('name') || '';
                const requiresReassignment = !!forcedCounts;
                const ordersCount = forcedCounts ? Number(forcedCounts.orders) || 0 : 0;
                const bookingsCount = forcedCounts ? Number(forcedCounts.bookings) || 0 : 0;
                const contractsCount = forcedCounts ? Number(forcedCounts.contracts) || 0 : 0;
                const replacementOptions = {};

                reassignmentAdmins.forEach(function (admin) {
                    if (Number(admin.id) !== adminId) {
                        replacementOptions[admin.id] = admin.name;
                    }
                });

                if (requiresReassignment && Object.keys(replacementOptions).length === 0) {
                    const noEmployeeMessage = "{{ __('admin.no_active_reassignment_employee') }}";
                    if (adminSwal && typeof adminSwal.fire === 'function') {
                        adminSwal.fire("{{ __('admin.delete_blocked') }}", noEmployeeMessage, 'error');
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
                    : '';

                const swalOptions = {
                    title: "{{ __('admin.force_delete_employee') }}",
                    text: "{{ __('admin.force_delete_employee_text') }}".replace(':name', adminName) + ' ' + "{{ __('admin.trashed_admins.force_delete_hint') }}" + reassignmentText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: "{{ __('admin.force_delete') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}"
                };

                if (requiresReassignment) {
                    swalOptions.input = 'select';
                    swalOptions.inputOptions = replacementOptions;
                    swalOptions.inputPlaceholder = "{{ __('admin.select_replacement_employee_placeholder') }}";
                    swalOptions.inputValidator = function (value) {
                        return value ? null : "{{ __('admin.replacement_employee_required') }}";
                    };
                }

                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire(swalOptions)
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.confirm_force_delete_employee') }}"), value: ''});

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
                            showSuccess(data.message || "{{ __('admin.employee_force_deleted_successfully') }}");
                            initTable();
                        },
                        error: function (errorObj) {
                            if (errorObj.responseJSON && errorObj.responseJSON.requires_reassignment) {
                                forceDeleteAdmin(button, errorObj.responseJSON.assignment_counts || {});
                                return;
                            }

                            handleErrorResponce(errorObj);
                        }
                    });
                });
            }

            $(document).on('click', '.trashed-force-delete-button', function (e) {
                e.preventDefault();
                forceDeleteAdmin($(this), null);
            });
        });
    </script>
@endsection
