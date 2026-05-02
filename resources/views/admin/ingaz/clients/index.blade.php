@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .stats-card {
            border-radius: 16px;
            border: 1px solid #eef2f6;
            box-shadow: 0 4px 12px rgba(15, 34, 58, 0.05);
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
            box-shadow: 0 6px 18px rgba(15, 34, 58, 0.05);
        }

        .filter-card label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .badge {
            border-radius: 999px;
        }


        @media (max-width: 575.98px) {
            .stats-card {
                border-radius: 12px;
            }

            .filter-card {
                border-radius: 14px;
            }
        }

        .client-data .phone a,
        .client-data .email a {
            font-size: 13px;
            white-space: nowrap;
        }

        .last-activity span {
            color: #2794eb !important;
        }

        .name-column .name {
            white-space: nowrap;
            font-size: 14px;
        }

        .bulk-actions-card {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fbff;
        }

        .bulk-actions-card .form-select {
            min-width: 170px;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 42px;
            text-align: center;
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.clients') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
        @php
            $stats = $stats ?? [
                'total' => 0,
                'active' => 0,
                'blocked' => 0,
                'new_this_month' => 0,
                'with_orders' => 0,
            ];
        @endphp

        <div class="page-body">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h3 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-users text-primary me-2"></i>{{ helperTrans('ingaz.clients_management') }}
                        </h3>
                        <div class="text-muted small">{{ helperTrans('ingaz.total_clients') }}
                            : {{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="btn-toolbar gap-2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE))
                            <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus me-1"></i>{{ helperTrans('ingaz.add_client') }}
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF))
                            <a href="{{ route('clients.export.pdf') }}" class="btn btn-outline-danger btn-sm"
                                target="_blank">
                                <i class="fa fa-file-pdf me-1"></i>{{ helperTrans('ingaz.export_pdf') }}
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL))
                            <a href="{{ route('clients.export.excel') }}" class="btn btn-outline-success btn-sm">
                                <i class="fa fa-file-excel me-1"></i>{{ helperTrans('ingaz.export_excel') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span
                                            class="text-muted small fw-semibold">{{ helperTrans('ingaz.total_clients') }}</span>
                                        <h3 class="mb-0 mt-1">{{ number_format($stats['total']) }}</h3>
                                    </div>
                                    <span class="stats-icon bg-primary-subtle text-primary">
                                        <i class="fas fa-users"></i>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    <i
                                        class="fas fa-chart-line text-success me-1"></i>{{ helperTrans('ingaz.new_clients_this_month') }}
                                    :
                                    <span
                                        class="fw-semibold text-success">{{ number_format($stats['new_this_month']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span
                                            class="text-muted small fw-semibold">{{ helperTrans('ingaz.active_clients') }}</span>
                                        <h3 class="mb-0 mt-1 text-success">{{ number_format($stats['active']) }}</h3>
                                    </div>
                                    <span class="stats-icon bg-success-subtle text-success">
                                        <i class="fas fa-user-check"></i>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    {{ helperTrans('ingaz.clients_with_orders') }}:
                                    <span class="fw-semibold">{{ number_format($stats['with_orders']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span
                                            class="text-muted small fw-semibold">{{ helperTrans('ingaz.blocked_clients') }}</span>
                                        <h3 class="mb-0 mt-1 text-danger">{{ number_format($stats['blocked']) }}</h3>
                                    </div>
                                    <span class="stats-icon bg-danger-subtle text-danger">
                                        <i class="fas fa-user-slash"></i>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    {{ helperTrans('ingaz.status_filter') }}: {{ helperTrans('ingaz.blocked') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card stats-card h-100">
                            <div class="card-body d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span
                                            class="text-muted small fw-semibold">{{ helperTrans('ingaz.registration_date') }}</span>
                                        <h3 class="mb-0 mt-1 text-info">
                                            {{ $stats['total'] ? round(($stats['new_this_month'] / max($stats['total'], 1)) * 100, 1) : 0 }}
                                            %
                                        </h3>
                                    </div>
                                    <span class="stats-icon bg-info-subtle text-info">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    {{ helperTrans('ingaz.new_clients_this_month') }}
                                </div>
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
                            data-bs-target="#clientsFiltersCollapse" aria-expanded="false"
                            aria-controls="clientsFiltersCollapse">
                            <i class="fas fa-chevron-down" id="clientsFiltersIcon"></i>
                        </button>
                    </div>
                    <div class="card-body collapse" id="clientsFiltersCollapse">
                        <form id="clientsFilters" class="row g-3">
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <label class="form-label">{{ helperTrans('ingaz.search_keyword') }}</label>
                                <input type="text" class="form-control" id="filter-keyword"
                                    placeholder="{{ helperTrans('ingaz.search_keyword_placeholder') }}">
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.status_filter') }}</label>
                                <select id="filter-status" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="active">{{ helperTrans('ingaz.active') }}</option>
                                    <option value="blocked">{{ helperTrans('ingaz.blocked') }}</option>
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.nationality_filter') }}</label>
                                <select id="filter-nationality" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($nationalities as $nationality)
                                        <option value="{{ $nationality->id }}">
                                            {{ app()->getLocale() === 'ar' ? $nationality->name_ar : $nationality->name_en }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.city_filter') }}</label>
                                <select id="filter-city" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->name_ar }}">
                                            {{ app()->getLocale() === 'ar' ? $city->name_ar : $city->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.has_orders_filter') }}</label>
                                <select id="filter-orders" class="form-select filter-trigger">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="with">{{ helperTrans('ingaz.with_orders') }}</option>
                                    <option value="without">{{ helperTrans('ingaz.without_orders') }}</option>
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
                <div class="row g-3 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <button class="btn btn-danger" id="btnMakeAccounts">
                            {{ helperTrans('ingaz.make_accounts_for_uses') }}
                        </button>
                    </div>
                </div>
                @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_TOGGLE_STATUS))
                    <div class="card bulk-actions-card shadow-sm mb-4 d-none" id="clientsBulkActions">
                        <div class="card-body">
                            <div class="row g-3 align-items-center">
                                <div class="col-lg-4">
                                    <div class="fw-semibold">
                                        الإجراءات المجمعة
                                        <span class="badge bg-primary ms-2" id="clientsBulkCount">0</span>
                                    </div>
                                    <div class="small text-muted mt-1" id="clientsBulkSummary">
                                        اختر عملاء من الجدول لتفعيل الإجراءات المجمعة.
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <select class="form-select" id="clientsBulkStatus">
                                        <option value="active">تفعيل العملاء المحددين</option>
                                        <option value="blocked">حظر العملاء المحددين</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-4 d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="clientsBulkApply" disabled>
                                        تنفيذ
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="clientsBulkClear">
                                        إلغاء التحديد
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTableObject"
                                class="table table-hover align-middle table-style"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>
                                            @if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_TOGGLE_STATUS))
                                                <input class="form-check-input" type="checkbox" id="clientsBulkSelectPage">
                                            @endif
                                        </th>
                                        <th>#</th>
                                        <th>{{ helperTrans('ingaz.name') }}</th>
                                        <th>{{ helperTrans('ingaz.client_data') }}</th>
                                        <th>{{ helperTrans('ingaz.client_activity') }}</th>
                                        <th>{{ helperTrans('ingaz.last_activity') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>{{ helperTrans('ingaz.have_account') }}</th>
                                        <th>{{ helperTrans('ingaz.created_at') }}</th>
                                        <th>{{ helperTrans('ingaz.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
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
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(function() {
            const selectedClients = new Map();

            function syncBulkCheckboxes() {
                $('.client-bulk-checkbox').each(function() {
                    $(this).prop('checked', selectedClients.has(String($(this).val())));
                });

                const pageCheckboxes = $('.client-bulk-checkbox');
                const checkedPageCheckboxes = pageCheckboxes.filter(':checked');
                $('#clientsBulkSelectPage').prop('checked', pageCheckboxes.length > 0 && checkedPageCheckboxes.length === pageCheckboxes.length);
            }

            function updateBulkActions() {
                const selectedCount = selectedClients.size;
                $('#clientsBulkCount').text(selectedCount);
                $('#clientsBulkApply').prop('disabled', selectedCount === 0);

                if (selectedCount === 0) {
                    $('#clientsBulkActions').addClass('d-none');
                    $('#clientsBulkSummary').text('اختر عملاء من الجدول لتفعيل الإجراءات المجمعة.');
                } else {
                    $('#clientsBulkActions').removeClass('d-none');
                    $('#clientsBulkSummary').text('تم تحديد ' + selectedCount + ' عميل.');
                }

                syncBulkCheckboxes();
            }

            function clearBulkSelection() {
                selectedClients.clear();
                updateBulkActions();
            }

            const table = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('clients.index') }}",
                    data: function(d) {
                        d.status = $('#filter-status').val();
                        d.nationality_id = $('#filter-nationality').val();
                        d.city = $('#filter-city').val();
                        d.has_orders = $('#filter-orders').val();
                        d.date_from = $('#filter-date-from').val();
                        d.date_to = $('#filter-date-to').val();
                        d.keyword = $('#filter-keyword').val();
                    }
                },
                columns: [{
                        data: 'bulk_select',
                        name: 'bulk_select',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        orderable: false
                    },
                    {
                        data: 'client_data',
                        name: 'client_data',
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
                        data: 'last_activity',
                        name: 'last_activity',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'is_blocked',
                        name: 'is_blocked',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'account_id',
                        name: 'account_id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [8, 'desc']
                ],
                language: {
                    url: "{{ asset('admin/assets/js/datatable-ar.json') }}"
                },
                drawCallback: function() {
                    if (typeof bootstrap !== 'undefined') {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                    syncBulkCheckboxes();
                }
            });

            $('#clientsFilters').on('submit', function(e) {
                e.preventDefault();
                table.draw();
            });

            $('#resetFilters').on('click', function() {
                $('#clientsFilters')[0].reset();
                clearBulkSelection();
                table.draw();
            });

            $('.filter-trigger').on('change', function() {
                clearBulkSelection();
                table.draw();
            });

            $(document).on('change', '.client-bulk-checkbox', function() {
                const id = String($(this).val());

                if ($(this).is(':checked')) {
                    selectedClients.set(id, {
                        id: id,
                        name: $(this).data('name') || '',
                        status: $(this).data('status')
                    });
                } else {
                    selectedClients.delete(id);
                }

                updateBulkActions();
            });

            $('#clientsBulkSelectPage').on('change', function() {
                const checked = $(this).is(':checked');
                $('.client-bulk-checkbox').each(function() {
                    const id = String($(this).val());

                    $(this).prop('checked', checked);
                    if (checked) {
                        selectedClients.set(id, {
                            id: id,
                            name: $(this).data('name') || '',
                            status: $(this).data('status')
                        });
                    } else {
                        selectedClients.delete(id);
                    }
                });

                updateBulkActions();
            });

            $('#clientsBulkClear').on('click', function() {
                clearBulkSelection();
            });

            $('#clientsBulkApply').on('click', function() {
                const ids = Array.from(selectedClients.keys());
                const status = $('#clientsBulkStatus').val();
                const actionText = status === 'blocked' ? 'حظر' : 'تفعيل';

                if (ids.length === 0) {
                    return;
                }

                const runRequest = () => {
                    const btn = $('#clientsBulkApply');
                    btn.prop('disabled', true).text('جاري التنفيذ...');

                    $.ajax({
                        url: "{{ route('clients.bulk-update-status') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            ids: ids,
                            status: status
                        },
                        success: function(response) {
                            successToster('{{ __('auth.done successfully') }}', response.message);
                            clearBulkSelection();
                            table.ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'حصل خطأ، حاول مرة أخرى';
                            errorToster('خطأ!', message);
                        },
                        complete: function() {
                            btn.text('تنفيذ');
                            updateBulkActions();
                        }
                    });
                };

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'تأكيد الإجراء',
                        text: 'سيتم ' + actionText + ' ' + ids.length + ' عميل.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'تنفيذ',
                        cancelButtonText: 'إلغاء'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            runRequest();
                        }
                    });
                } else if (confirm('سيتم ' + actionText + ' ' + ids.length + ' عميل. هل تريد المتابعة؟')) {
                    runRequest();
                }
            });

            const collapseEl = document.getElementById('clientsFiltersCollapse');
            if (collapseEl) {
                const updateIconState = () => {
                    if ($(collapseEl).hasClass('show')) {
                        $('#clientsFiltersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $('#clientsFiltersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                };

                updateIconState();

                collapseEl.addEventListener('show.bs.collapse', function() {
                    $('#clientsFiltersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                });
                collapseEl.addEventListener('hide.bs.collapse', function() {
                    $('#clientsFiltersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                });
            }
        });
        $('#btnMakeAccounts').on('click', function() {
            let btn = $(this);
            let url = "{{ route('make-accounts-for-clients.store') }}";
            btn.prop('disabled', true).text('جاري التنفيذ...');
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    successToster('{{ __('auth.done successfully') }}', response.message);
                    $('#dataTableObject').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    console.log(xhr);
                    
                    errorToster('خطأ!', xhr.responseJSON.message || 'حصل خطأ، حاول مرة أخرى ❌');
                    // alert('حصل خطأ، حاول مرة أخرى ❌');
                },
                complete: function() {
                    btn.prop('disabled', false).text(
                        ' {{ helperTrans('ingaz.make_accounts_for_uses') }}');
                }
            });
        });
    </script>
@endsection
