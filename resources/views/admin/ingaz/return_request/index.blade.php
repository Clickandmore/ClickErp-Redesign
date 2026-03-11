@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .order-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .order-stat-card {
            border: 1px solid #e8ecf4;
            border-radius: 18px;
            padding: 16px 18px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            width: 100%;
        }

        .order-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }

        .order-stat-card.active {
            border-color: var(--bs-primary);
            box-shadow: 0 24px 40px rgba(13, 110, 253, 0.15);
        }

        .order-stat-card span {
            font-size: 0.8rem;
            color: #6c778a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .order-stat-card strong {
            display: block;
            font-size: 2rem;
            color: #111c3a;
            margin: 8px 0 4px;
        }

        .order-stat-card small {
            color: #8892a6;
            font-size: 0.85rem;
        }

        .status-badge {
            border-radius: 999px;
            min-width: 90px;
            text-align: center;
        }

        .phone-number {
            direction: ltr;
            font-family: 'Courier New', monospace;
        }

        .orders-page .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
        }

        .orders-page #dataTableObject_wrapper,
        .orders-page .dataTables_scroll,
        .orders-page .dataTables_scrollHead,
        .orders-page .dataTables_scrollBody {
            width: 100% !important;
        }

        @media (max-width: 991.98px) {
            .layout-1 .wrapper {
                overflow-y: auto !important;
            }

            .orders-page .container-fluid {
                padding-inline: 12px;
            }

            .orders-page .order-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .orders-page .order-stat-card {
                padding: 12px;
            }

            .orders-page .order-stat-card strong {
                font-size: 1.5rem;
            }

            .orders-page .export-buttons {
                width: 100%;
                margin-top: 10px;
                justify-content: stretch !important;
            }

            .orders-page .export-buttons .btn {
                flex: 1 1 auto;
            }

            .orders-page .table-responsive {
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            .orders-page .dataTables_scrollBody {
                overflow-x: auto !important;
                overflow-y: visible !important;
                max-height: none !important;
            }
        }

        @media (max-width: 575.98px) {
            .orders-page .order-stats-grid {
                grid-template-columns: 1fr;
            }

            .orders-page .table-style th,
            .orders-page table.dataTable tbody td {
                white-space: nowrap;
                font-size: 0.82rem;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.cancelled_orders') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_VIEW))
        <div class="page-body orders-page">
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-ban text-danger me-2"></i>
                        {{ helperTrans('ingaz.cancelled_orders') }}
                    </h3>
                    <div class="export-buttons text-end d-flex flex-wrap gap-2 justify-content-end">
                        @if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_EXPORT))
                            <a href="{{ route('return_requests.export.pdf') }}" id="export_pdf"
                                class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="fa fa-file-pdf"></i> {{ helperTrans('ingaz.export_pdf') }}
                            </a>
                            <a href="{{ route('return_requests.export.excel') }}" id="export_excel"
                                class="btn btn-outline-success btn-sm">
                                <i class="fa fa-file-excel"></i> {{ helperTrans('ingaz.export_excel') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="order-stats-grid">
                    <button type="button" class="order-stat-card active" data-status="">
                        <span>{{ helperTrans('ingaz.orders_overview') }}</span>
                        <strong>{{ number_format($totalOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.total') }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="refused">
                        <span>{{ helperTrans('ingaz.refused') }}</span>
                        <strong>{{ number_format($refusedOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.refused') }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="return">
                        <span>{{ helperTrans('ingaz.return') }}</span>
                        <strong>{{ number_format($returnedOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.return') }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="deleted">
                        <span>{{ helperTrans('ingaz.deleted') }}</span>
                        <strong>{{ number_format($deletedOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.deleted') }}</small>
                    </button>
                </div>

                <div class="card mb-4 filter-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-filter me-2"></i>
                            {{ helperTrans('ingaz.filters') }}
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                            <i class="fas fa-chevron-down" id="filtersIcon"></i>
                        </button>
                    </div>
                    <div class="card-body collapse" id="filtersCollapse">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.from_date') }}</label>
                                <input type="date" class="form-control" name="from_date" id="from_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.to_date') }}</label>
                                <input type="date" class="form-control" name="to_date" id="to_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.status') }}</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="refused">{{ helperTrans('ingaz.refused') }}</option>
                                    <option value="return">{{ helperTrans('ingaz.return') }}</option>
                                    <option value="deleted">{{ helperTrans('ingaz.deleted') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.job_type') }}</label>
                                <select class="form-select" name="job_type" id="job_type">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @if (isset($jobTypes))
                                        @foreach ($jobTypes as $jobType)
                                            <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                <select class="form-select" name="nationality_id" id="nationality_id">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @if (isset($nationalities))
                                        @foreach ($nationalities as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->title ?? $nationality->sub_title ?? $nationality->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.responsible_employee') }}</label>
                                <select class="form-select" name="admin_id" id="admin_id">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    @if (isset($admins))
                                        @foreach ($admins as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.client_name') }}</label>
                                <input type="text" class="form-control" name="client_name" id="client_name"
                                    placeholder="{{ helperTrans('ingaz.search_by_client_name') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.phone') }}</label>
                                <input type="text" class="form-control" name="phone" id="phone"
                                    placeholder="{{ helperTrans('ingaz.search_by_phone') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.passport_num') }}</label>
                                <input type="text" class="form-control" name="passport_num" id="passport_num"
                                    placeholder="{{ helperTrans('ingaz.search_by_passport') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ helperTrans('ingaz.recruitment_office') }}</label>
                                <input type="text" class="form-control" name="office_name" id="office_name"
                                    placeholder="{{ helperTrans('ingaz.recruitment_office') }}">
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fa fa-filter"></i> {{ helperTrans('ingaz.filter') }}
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="resetFilter">
                                        <i class="fa fa-refresh"></i> {{ helperTrans('ingaz.reset') }}
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
                                        <th>ID</th>
                                        <th>{{ helperTrans('ingaz.maid_photo') }}</th>
                                        <th>{{ helperTrans('ingaz.client') }}</th>
                                        <th>{{ helperTrans('ingaz.phone') }}</th>
                                        <th>{{ helperTrans('ingaz.nationality') }}</th>
                                        <th>{{ helperTrans('ingaz.passport_num') }}</th>
                                        <th>{{ helperTrans('ingaz.order_job_type') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>{{ helperTrans('ingaz.order_date') }}</th>
                                        <th>{{ helperTrans('ingaz.responsible_employee') }}</th>
                                        <th class="text-center">{{ helperTrans('ingaz.actions') }}</th>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>

    <script>
        $(document).ready(function() {
            const isMobileTableView = window.matchMedia('(max-width: 991.98px)').matches;

            let columns = [{
                    data: 'id',
                    name: 'id',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'maid_photo',
                    name: 'maid_photo',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user_id',
                    name: 'user_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'phone',
                    name: 'phone',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nationality_name',
                    name: 'nationality_name',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'maid_passport_num',
                    name: 'maid_passport_num',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'job_type_id',
                    name: 'job_type_id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'admin_name',
                    name: 'admin_name',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ];

            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                responsive: isMobileTableView,
                searching: false,
                deferRender: true,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: {
                    url: "{{ route('return_requests.index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.status = $('#status').val();
                        d.job_type = $('#job_type').val();
                        d.nationality_id = $('#nationality_id').val();
                        d.admin_id = $('#admin_id').val();
                        d.client_name = $('#client_name').val();
                        d.phone = $('#phone').val();
                        d.passport_num = $('#passport_num').val();
                        d.office_name = $('#office_name').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', error);
                        console.error('XHR:', xhr);
                        console.error('Thrown:', thrown);
                    }
                },
                columns: columns,
                order: [
                    [8, 'desc']
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>',
                    loadingRecords: "جاري التحميل...",
                    emptyTable: "لا توجد بيانات متاحة",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    infoEmpty: "عرض 0 إلى 0 من 0 سجل",
                    infoFiltered: "(تمت التصفية من _MAX_ إجمالي السجلات)",
                    lengthMenu: "عرض _MENU_ سجلات",
                    zeroRecords: "لم يتم العثور على سجلات مطابقة",
                    paginate: {
                        first: "الأول",
                        last: "الأخير",
                        next: "التالي",
                        previous: "السابق"
                    }
                },
                scrollX: !isMobileTableView,
                scrollCollapse: !isMobileTableView,
                fixedColumns: !isMobileTableView ? {
                    leftColumns: 0,
                    rightColumns: 1
                } : false,
                stateSave: false,
                autoWidth: false
            });

            function setActiveCard(value) {
                $('.order-stat-card').each(function() {
                    const status = $(this).data('status') || '';
                    $(this).toggleClass('active', status === (value || ''));
                });
            }

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload();
            });

            $('#resetFilter').on('click', function() {
                $('#filterForm')[0].reset();
                $('#status').val('');
                setActiveCard('');
                dataTable.ajax.reload();
            });

            $('#status').on('change', function() {
                setActiveCard($(this).val());
            });

            $('.order-stat-card').on('click', function() {
                const status = $(this).data('status') || '';
                $('#status').val(status);
                setActiveCard(status);
                dataTable.ajax.reload();

                const tableOffset = $('#dataTableObject').offset();
                if (tableOffset) {
                    const $wrapper = $('.layout-1 .wrapper');
                    if ($wrapper.length) {
                        const wrapperTop = $wrapper.offset().top;
                        const targetScroll = $wrapper.scrollTop() + tableOffset.top - wrapperTop - 120;
                        $wrapper.stop().animate({
                            scrollTop: Math.max(targetScroll, 0)
                        }, 300);
                    } else {
                        $('html, body').animate({
                            scrollTop: tableOffset.top - 120
                        }, 300);
                    }
                }
            });

            function getFilterQueryParams() {
                return $.param({
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    status: $('#status').val(),
                    job_type: $('#job_type').val(),
                    nationality_id: $('#nationality_id').val(),
                    admin_id: $('#admin_id').val(),
                    client_name: $('#client_name').val(),
                    phone: $('#phone').val(),
                    passport_num: $('#passport_num').val(),
                    office_name: $('#office_name').val(),
                });
            }

            $('#export_excel').on('click', function(e) {
                e.preventDefault();
                window.location.href = '{{ route('return_requests.export.excel') }}?' + getFilterQueryParams();
            });

            $('#export_pdf').on('click', function(e) {
                e.preventDefault();
                window.open('{{ route('return_requests.export.pdf') }}?' + getFilterQueryParams(), '_blank');
            });

            function handleOrderMutation(url, method, successMessage) {
                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        dataTable.ajax.reload(null, false);

                        if (response && (response.success || (response.data && response.data.success))) {
                            Swal.fire('نجح!', response.message || successMessage, 'success');
                            return;
                        }

                        Swal.fire('خطأ!', '{{ helperTrans('ingaz.update_error') }}', 'error');
                    },
                    error: function() {
                        Swal.fire('خطأ!', '{{ helperTrans('ingaz.update_error') }}', 'error');
                    }
                });
            }

            $(document).on('click', '.restore-order', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');

                Swal.fire({
                    title: '{{ helperTrans('buttons.Are You Sure') }}',
                    text: '{{ helperTrans('ingaz.restore_confirm') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ helperTrans('buttons.accept') }}',
                    cancelButtonText: '{{ helperTrans('buttons.cancel') }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleOrderMutation('/admin/orders/' + orderId + '/restore', 'POST',
                            '{{ helperTrans('ingaz.order_restored') }}');
                    }
                });
            });

            $(document).on('click', '.delete-order', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');

                Swal.fire({
                    title: '{{ helperTrans('buttons.Are You Sure') }}',
                    text: '{{ helperTrans('buttons.You Can not to rollback') ?? 'This action cannot be undone!' }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ helperTrans('buttons.accept') }}',
                    cancelButtonText: '{{ helperTrans('buttons.cancel') }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleOrderMutation('/admin/orders/' + orderId, 'DELETE',
                            '{{ helperTrans('ingaz.order_deleted') }}');
                    }
                });
            });

            $(document).on('click', '.force-delete-order', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');

                Swal.fire({
                    title: '{{ helperTrans('buttons.Are You Sure') }}',
                    text: '{{ helperTrans('buttons.You Can not to rollback') ?? 'This action cannot be undone!' }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ helperTrans('buttons.accept') }}',
                    cancelButtonText: '{{ helperTrans('buttons.cancel') }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        handleOrderMutation('/admin/orders/' + orderId, 'DELETE',
                            '{{ helperTrans('ingaz.order_deleted') }}');
                    }
                });
            });

            function toggleRefuseReasonField(selectedStatus) {
                const wrapper = $('#editStatusModal').find('[data-refuse-reason]');
                if (!wrapper.length) {
                    return;
                }

                const needsReason = ['refused', 'return'].includes(selectedStatus);
                wrapper.toggleClass('d-none', !needsReason);
            }

            $(document).on('shown.bs.modal', '#editStatusModal', function() {
                const checked = $(this).find('input[name="status"]:checked');
                toggleRefuseReasonField(checked.length ? checked.val() : '');
            });

            $(document).on('change', '#editStatusModal input[name="status"]', function() {
                toggleRefuseReasonField($(this).val());
            });

            $(document).on('click', '.edit-status', function(e) {
                e.preventDefault();
                const orderId = $(this).data('id');

                const loadingModal = `
                    <div class="modal fade" id="loadingModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-body text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">جاري التحميل...</span>
                                    </div>
                                    <p class="mt-2">جاري تحميل نموذج التعديل...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#loadingModal').remove();
                $('#editStatusModal').remove();
                $('body').append(loadingModal);
                $('#loadingModal').modal('show');

                $.ajax({
                    url: '/admin/orders/' + orderId + '/edit',
                    type: 'GET',
                    success: function(response) {
                        $('#loadingModal').modal('hide');
                        $('#loadingModal').remove();

                        if (!response.success) {
                            Swal.fire('خطأ!', response.message ||
                                '{{ helperTrans('ingaz.load_form_error') }}', 'error');
                            return;
                        }

                        const modal = `
                            <div class="modal fade" id="editStatusModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ helperTrans('ingaz.edit_order_status') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            ${response.html}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        $('#editStatusModal').remove();
                        $('body').append(modal);
                        $('#editStatusModal').modal('show');

                        $('#editStatusModal form').on('submit', function(submitEvent) {
                            submitEvent.preventDefault();
                            const formData = new FormData(this);

                            $.ajax({
                                url: $(this).attr('action'),
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(updateResponse) {
                                    if (updateResponse.success) {
                                        $('#editStatusModal').modal('hide');
                                        setTimeout(function() {
                                            $('#editStatusModal').remove();
                                            $('.modal-backdrop').remove();
                                            $('body').removeClass('modal-open');
                                        }, 200);

                                        dataTable.ajax.reload(null, false);
                                        Swal.fire('نجح!',
                                            '{{ helperTrans('ingaz.status_updated') }}',
                                            'success');
                                    } else {
                                        Swal.fire('خطأ!', updateResponse.message ||
                                            '{{ helperTrans('ingaz.status_update_error') }}',
                                            'error');
                                    }
                                },
                                error: function(xhr) {
                                    let errorMessage =
                                        '{{ helperTrans('ingaz.status_update_error') }}';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    Swal.fire('خطأ!', errorMessage, 'error');
                                }
                            });
                        });
                    },
                    error: function(xhr) {
                        $('#loadingModal').modal('hide');
                        $('#loadingModal').remove();

                        let errorMessage = '{{ helperTrans('ingaz.load_form_error') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('خطأ!', errorMessage, 'error');
                    }
                });
            });

            dataTable.on('draw', function() {
                $('.dropdown').removeClass('show');
                $('.dropdown-menu').hide();
            });
        });
    </script>
@endsection
