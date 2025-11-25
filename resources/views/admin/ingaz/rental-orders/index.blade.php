@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection
@section('page-title')
    طلبات الإيجار
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
        <div class="page-body">
            <div class="container-fluid">
                <!-- Header Section -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-exchange-alt text-primary me-2"></i>
                        طلبات الإيجار
                    </h3>
                    <div class="export-buttons text-end">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EXPORT))
                            <a href="#" class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="fa fa-file-pdf"></i> {{ helperTrans('ingaz.export_pdf') }}
                            </a>
                            <a href="#" class="btn btn-outline-success btn-sm">
                                <i class="fa fa-file-excel"></i> {{ helperTrans('ingaz.export_excel') }}
                            </a>
                        @endif
                    </div>
                </div>


                <!-- Filter Form -->
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
                                    @foreach (\App\Enums\ingaz\OrderEnum::cases() as $status)
                                        <option value="{{ $status->value }}">
                                            {{ getEnumValue('orders_statuses', $status->value) }}</option>
                                    @endforeach
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
                            <div class="col-md-2">
                                <label class="form-label">{{ helperTrans('ingaz.timer_status') }}</label>
                                <select class="form-select" name="timer_status" id="timer_status">
                                    <option value="">{{ helperTrans('ingaz.all') }}</option>
                                    <option value="normal">{{ helperTrans('ingaz.normal') }}</option>
                                    <option value="urgent">{{ helperTrans('ingaz.urgent') }}</option>
                                    <option value="overdue">{{ helperTrans('ingaz.overdue') }}</option>
                                </select>
                            </div>
                            <!-- Search Fields -->
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

                <!-- Data Table -->
                <div class="card shadow">
                    {{-- <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fa fa-list"></i> قائمة طلبات الإيجار
                        </h5>
                    </div> --}}
                    <div class="card-body">

                        <table id="dataTableObject" class="table table-hover align-middle rental-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{ helperTrans('ingaz.order_code') }}</th>
                                    <th>{{ helperTrans('ingaz.client') }}</th>
                                    <th>{{ helperTrans('ingaz.phone') }}</th>
                                    <th>الباقة المختارة</th>
                                    <th>حالة العنوان</th>
                                    <th>حالة الدفع</th>
                                    <th>{{ helperTrans('ingaz.nationality') }}</th>
                                    <th>{{ helperTrans('ingaz.passport_num') }}</th>
                                    <th>{{ helperTrans('ingaz.order_job_type') }}</th>
                                    <th>{{ helperTrans('ingaz.status') }}</th>
                                    <th>{{ helperTrans('ingaz.timer') }}</th>
                                    <th>{{ helperTrans('ingaz.contract_status') }}</th>
                                    <th>{{ helperTrans('ingaz.order_date') }}</th>
                                    <th class="text-center">{{ helperTrans('ingaz.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

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

    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            let columns = [{
                    "data": 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "order_code",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "user_id",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "phone",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "package_name",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "address_status",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-lg-table-cell'
                },
                {
                    "data": "payment_status",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-lg-table-cell'
                },
                {
                    "data": "nationality_name",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-md-table-cell'
                },
                {
                    "data": "maid_passport_num",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "job_type_id",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "status",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "timer",
                    orderable: false,
                    searchable: false,
                    className: 'd-none d-lg-table-cell'
                },
                {
                    "data": "contract_status",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "created_at",
                    orderable: true,
                    searchable: true,
                    className: 'd-none d-xl-table-cell'
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ];

            // Get URL parameters
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                deferRender: true, // تأجيل رسم الصفوف لتحسين الأداء
                pageLength: 25, // عرض 25 صف فقط بدلاً من 10
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: {
                    url: "{{ route('rental-orders.index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.status = $('#status').val() || getUrlParameter('status');
                        d.job_type = $('#job_type').val();
                        d.admin_id = $('#admin_id').val();
                        d.timer_status = $('#timer_status').val();
                        d.client_name = $('#client_name').val();
                        d.phone = $('#phone').val();
                        d.passport_num = $('#passport_num').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', error);
                        console.error('XHR:', xhr);
                        console.error('Thrown:', thrown);
                        console.error('Response Text:', xhr.responseText);
                        console.error('Status:', xhr.status);
                    },
                    dataSrc: function(json) {
                        // معالجة البيانات قبل عرضها
                        console.log('Received ' + json.data.length + ' records');
                        return json.data;
                    }
                },
                columns: columns,
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
                drawCallback: function() {
                    // admin-tables.js يتولى معالجة القوائم المنسدلة
                },
                scrollX: true,
                scrollCollapse: true,
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                stateSave: false, // عدم حفظ حالة الجدول
                autoWidth: false // عدم حساب العرض تلقائياً
            });

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload();
            });

            // Set form values from URL parameters on page load
            var urlStatus = getUrlParameter('status');
            if (urlStatus) {
                $('#status').val(urlStatus);
            }

            // Handle reset button
            $('#resetFilter').on('click', function() {
                $('#filterForm')[0].reset();
                dataTable.ajax.reload();
            });

            // Update timer every minute
            setInterval(function() {
                dataTable.ajax.reload(null, false); // false to maintain pagination
            }, 60000); // 60 seconds

            // Handle delete order
            $(document).on('click', '.delete-order', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                Swal.fire({
                    title: "{{ helperTrans('buttons.Are You Sure') }}",
                    text: '{{ helperTrans('buttons.You Can not to rollback') ?? 'This action cannot be undone!' }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ helperTrans('buttons.accept') ?? 'Yes, delete it!' }}',
                    cancelButtonText: '{{ helperTrans('buttons.cancel') ?? 'Cancel' }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        // deleteOrder(orderId);
                        $.ajax({
                            url: '/admin/orders/' + orderId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.data.success) {
                                    dataTable.ajax.reload();
                                    // toastr.success(
                                    //     response.message || '{{ helperTrans('ingaz.order_deleted') }}');
                                    Swal.fire('نجاح', response.message || '{{ helperTrans('ingaz.order_deleted') }}', 'success');
                                } else {
                                    // toastr.error(
                                    //     '{{ helperTrans('ingaz.delete_error') }}');
                                    Swal.fire('خطأ', response.message || '{{ helperTrans('ingaz.delete_error') }}', 'error');
                                }
                                dataTable.ajax.reload();
                            },
                            error: function() {
                                // toastr.error(
                                // '{{ helperTrans('ingaz.delete_error') }}');
                                Swal.fire('خطأ', '{{ helperTrans('ingaz.delete_error') }}', 'error');
                            }
                        });
                    }
                });
            });
            // $(document).on('click', '.delete-order', function() {
            //     const orderId = $(this).data('id');
            //     if (confirm('{{ helperTrans('ingaz.delete_confirm') }}')) {
            //         $.ajax({
            //             url: '/admin/orders/' + orderId,
            //             type: 'DELETE',
            //             data: {
            //                 _token: '{{ csrf_token() }}'
            //             },
            //             success: function(response) {
            //                 if (response.success) {
            //                     dataTable.ajax.reload();
            //                     toastr.success('{{ helperTrans('ingaz.order_deleted') }}');
            //                 } else {
            //                     toastr.error('{{ helperTrans('ingaz.delete_error') }}');
            //                 }
            //             },
            //             error: function() {
            //                 toastr.error('{{ helperTrans('ingaz.delete_error') }}');
            //             }
            //         });
            //     }
            // });

            // Handle edit status
            $(document).on('click', '.edit-status', function() {
                const orderId = $(this).data('id');
                console.log('Edit status clicked for order:', orderId);

                // Show loading
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

                // Remove existing modals
                $('#loadingModal').remove();
                $('#editStatusModal').remove();

                $('body').append(loadingModal);
                $('#loadingModal').modal('show');

                // Show modal with edit form
                $.ajax({
                    url: '/admin/orders/' + orderId + '/edit',
                    type: 'GET',
                    beforeSend: function() {
                        console.log('Sending AJAX request to:', '/admin/orders/' + orderId +
                            '/edit');
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        $('#loadingModal').modal('hide');
                        $('#loadingModal').remove();

                        if (response.success) {
                            // Create modal
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

                            // Remove existing modal if any
                            $('#editStatusModal').remove();

                            // Add modal to body
                            $('body').append(modal);

                            // Show modal
                            $('#editStatusModal').modal('show');

                            // Handle form submission
                            $('#editStatusModal form').on('submit', function(e) {
                                e.preventDefault();
                                console.log('Form submitted');
                                const formData = new FormData(this);

                                // Log form data
                                for (let pair of formData.entries()) {
                                    console.log(pair[0] + ': ' + pair[1]);
                                }

                                $.ajax({
                                    url: $(this).attr('action'),
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        console.log('Update response:',
                                            response);
                                        if (response.success) {
                                            $('#editStatusModal').modal(
                                                'hide');
                                            $('.modal-backdrop').remove();
                                            $('body').removeClass(
                                                'modal-open');
                                            dataTable.ajax.reload();
                                            if (typeof toastr !==
                                                'undefined') {
                                                toastr.success(
                                                    '{{ helperTrans('ingaz.status_updated') }}'
                                                    );
                                            } else {
                                                alert(
                                                    '{{ helperTrans('ingaz.status_updated') }}');
                                            }
                                        } else {
                                            if (typeof toastr !==
                                                'undefined') {
                                                toastr.error(response
                                                    .message ||
                                                    '{{ helperTrans('ingaz.status_update_error') }}'
                                                    );
                                            } else {
                                                alert(response.message ||
                                                    '{{ helperTrans('ingaz.status_update_error') }}'
                                                    );
                                            }
                                        }
                                    },
                                    error: function(xhr) {
                                        console.log('Update error:', xhr);
                                        let errorMessage =
                                            '{{ helperTrans('ingaz.status_update_error') }}';
                                        if (xhr.responseJSON && xhr
                                            .responseJSON.message) {
                                            errorMessage = xhr.responseJSON
                                                .message;
                                        }
                                        if (typeof toastr !== 'undefined') {
                                            toastr.error(errorMessage);
                                        } else {
                                            alert(errorMessage);
                                        }
                                    }
                                });
                            });

                            // Handle modal close button
                            $('#editStatusModal .btn-close, #editStatusModal [data-bs-dismiss="modal"]')
                                .on('click', function() {
                                    $('#editStatusModal').modal('hide');
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                });

                            // Handle modal hidden event
                            $('#editStatusModal').on('hidden.bs.modal', function() {
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                            });
                        } else {
                            console.log('Response not successful:', response);
                            let errorMessage = '{{ helperTrans('ingaz.load_form_error') }}';
                            if (response.message) {
                                errorMessage = response.message;
                            }
                            if (typeof toastr !== 'undefined') {
                                toastr.error(errorMessage);
                            } else {
                                alert(errorMessage);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.log('AJAX error:', xhr);
                        $('#loadingModal').modal('hide');
                        $('#loadingModal').remove();

                        let errorMessage = '{{ helperTrans('ingaz.load_form_error') }}';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            errorMessage = 'الطلب غير موجود';
                        } else if (xhr.status === 500) {
                            errorMessage = 'خطأ في الخادم';
                        } else if (xhr.status === 0) {
                            errorMessage = 'فشل في الاتصال بالخادم';
                        }

                        toastr.error(errorMessage);
                        console.error('Error details:', xhr);
                    }
                });
            });

            // Old change maid code removed - using new advanced system below
            /*
            $(document).on('click', '.change-maid', function() {
                const orderId = $(this).data('id');

                // Show modal to select new maid with search
                const modal = `
                <div class="modal fade" id="changeMaidModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ helperTrans('ingaz.change_maid') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="changeMaidForm">
                                    <div class="mb-3">
                                        <label class="form-label">{{ helperTrans('ingaz.search_maid') }}</label>
                                        <input type="text" class="form-control" id="maidSearch" placeholder="{{ helperTrans('ingaz.search_by_name_or_passport') }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ helperTrans('ingaz.select_maid') }}</label>
                                        <select class="form-select" name="maid_id" required>
                                            <option value="">{{ helperTrans('ingaz.select_maid') }}</option>
                                        </select>
                                    </div>
                                    <div id="maidInfo" class="alert alert-info" style="display: none;">
                                        <h6>{{ helperTrans('ingaz.maid_info') }}</h6>
                                        <div id="maidDetails"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                                <button type="button" class="btn btn-primary" id="saveMaid">{{ helperTrans('ingaz.save') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                $('#changeMaidModal').remove();
                $('body').append(modal);
                $('#changeMaidModal').modal('show');

                // Handle modal close for change maid modal
                $('#changeMaidModal .btn-close, #changeMaidModal [data-bs-dismiss="modal"]').on('click', function() {
                    $('#changeMaidModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                });

                $('#changeMaidModal').on('hidden.bs.modal', function() {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                });

                // Load available maids
                loadAvailableMaids();

                // Handle search
                $('#maidSearch').on('input', function() {
                    const searchTerm = $(this).val();
                    if (searchTerm.length >= 2) {
                        searchMaids(searchTerm);
                    } else {
                        loadAvailableMaids();
                    }
                });

                // Handle maid selection
                $('#changeMaidForm select[name="maid_id"]').on('change', function() {
                    const maidId = $(this).val();
                    if (maidId) {
                        showMaidInfo(maidId);
                    } else {
                        $('#maidInfo').hide();
                    }
                });

                // Handle save
                $('#saveMaid').on('click', function() {
                    const maidId = $('#changeMaidForm select[name="maid_id"]').val();
                    if (!maidId) {
                        toastr.error('{{ helperTrans('ingaz.please_select_maid') }}');
                        return;
                    }

                    $.ajax({
                        url: '/admin/orders/' + orderId + '/change-maid',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            maid_id: maidId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#changeMaidModal').modal('hide');
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                dataTable.ajax.reload();
                                toastr.success('{{ helperTrans('ingaz.maid_updated') }}');
                            } else {
                                toastr.error(response.message || '{{ helperTrans('ingaz.update_error') }}');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error('{{ helperTrans('ingaz.update_error') }}');
                            }
                        }
                    });
                });
            });

            // Function to load available maids
            function loadAvailableMaids() {
                $.ajax({
                    url: '/admin/maids/available',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const select = $('#changeMaidForm select[name="maid_id"]');
                            select.empty();
                            select.append('<option value="">{{ helperTrans('ingaz.select_maid') }}</option>');

                            response.maids.forEach(function(maid) {
                                select.append(`<option value="${maid.id}">${maid.name} - ${maid.passport_num} - ${maid.nationality}</option>`);
                            });
                        }
                    }
                });
            }

            // Function to search maids
            function searchMaids(searchTerm) {
                $.ajax({
                    url: '/admin/maids/search',
                    type: 'GET',
                    data: { search: searchTerm },
                    success: function(response) {
                        if (response.success) {
                            const select = $('#changeMaidForm select[name="maid_id"]');
                            select.empty();
                            select.append('<option value="">{{ helperTrans('ingaz.select_maid') }}</option>');

                            response.maids.forEach(function(maid) {
                                select.append(`<option value="${maid.id}">${maid.name} - ${maid.passport_num} - ${maid.nationality}</option>`);
                            });
                        }
                    }
                });
            }

            // Function to show maid info
            function showMaidInfo(maidId) {
                $.ajax({
                    url: '/admin/maids/' + maidId + '/info',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const maid = response.maid;
                            $('#maidDetails').html(`
                            <p><strong>{{ helperTrans('ingaz.name') }}:</strong> ${maid.name}</p>
                            <p><strong>{{ helperTrans('ingaz.passport_num') }}:</strong> ${maid.passport_num}</p>
                            <p><strong>{{ helperTrans('ingaz.nationality') }}:</strong> ${maid.nationality}</p>
                            <p><strong>{{ helperTrans('ingaz.age') }}:</strong> ${maid.age}</p>
                            <p><strong>{{ helperTrans('ingaz.experience') }}:</strong> ${maid.experience}</p>
                        `);
                            $('#maidInfo').show();
                        }
                    }
                });
            }
            */

            // Handle change responsible employee
            $(document).on('click', '.change-responsible', function() {
                const orderId = $(this).data('id');

                // Show modal to select new responsible employee
                const modal = `
            <div class="modal fade" id="changeResponsibleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.change_responsible_employee') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeResponsibleForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_employee') }}</label>
                                    <select class="form-select" name="admin_id" required>
                                        <option value="">{{ helperTrans('ingaz.select_employee') }}</option>
                                        @foreach (\App\Models\auth\Admin::where('admin_type', 'customer_service')->get() as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-primary" id="saveResponsible">{{ helperTrans('ingaz.save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#changeResponsibleModal').remove();
                $('body').append(modal);
                $('#changeResponsibleModal').modal('show');

                // Handle modal close for change responsible modal
                $('#changeResponsibleModal .btn-close, #changeResponsibleModal [data-bs-dismiss="modal"]')
                    .on('click', function() {
                        $('#changeResponsibleModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    });

                $('#changeResponsibleModal').on('hidden.bs.modal', function() {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                });

                // Handle save
                $('#saveResponsible').on('click', function() {
                    const adminId = $('#changeResponsibleForm select[name="admin_id"]').val();
                    if (!adminId) {
                        toastr.error('{{ helperTrans('ingaz.please_select_employee') }}');
                        return;
                    }

                    $.ajax({
                        url: '/admin/orders/' + orderId + '/change-responsible',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            admin_id: adminId
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#changeResponsibleModal').modal('hide');
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                dataTable.ajax.reload();
                                toastr.success(
                                    '{{ helperTrans('ingaz.responsible_updated') }}'
                                    );
                            } else {
                                toastr.error(response.message ||
                                    '{{ helperTrans('ingaz.update_error') }}');
                            }
                        },
                        error: function() {
                            toastr.error('{{ helperTrans('ingaz.update_error') }}');
                        }
                    });
                });
            });

            // Handle refuse/return order
            $(document).on('click', '.refuse-order', function() {
                const orderId = $(this).data('id');

                const modal = `
            <div class="modal fade" id="refuseOrderModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.refuse_return_order') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="refuseOrderForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_action') }}</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="refused" id="refused">
                                        <label class="form-check-label" for="refused">
                                            {{ helperTrans('ingaz.refuse_order') }}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="return" id="return">
                                        <label class="form-check-label" for="return">
                                            {{ helperTrans('ingaz.return_order') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.reason') }}</label>
                                    <textarea class="form-control" name="reason" rows="3" placeholder="{{ helperTrans('ingaz.enter_reason') }}"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-danger" id="saveRefuse">{{ helperTrans('ingaz.confirm') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#refuseOrderModal').remove();
                $('body').append(modal);
                $('#refuseOrderModal').modal('show');

                // Handle modal close for refuse order modal
                $('#refuseOrderModal .btn-close, #refuseOrderModal [data-bs-dismiss="modal"]').on('click',
                    function() {
                        $('#refuseOrderModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    });

                $('#refuseOrderModal').on('hidden.bs.modal', function() {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                });

                // Handle save
                $('#saveRefuse').on('click', function() {
                    const action = $('#refuseOrderForm input[name="action"]:checked').val();
                    const reason = $('#refuseOrderForm textarea[name="reason"]').val();

                    if (!action) {
                        toastr.error('{{ helperTrans('ingaz.please_select_action') }}');
                        return;
                    }

                    $.ajax({
                        url: '/admin/orders/' + orderId + '/refuse-return',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            action: action,
                            reason: reason
                        },
                        success: function(response) {
                            if (response.data.success) {
                                $('#refuseOrderModal').modal('hide');
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                dataTable.ajax.reload();
                                toastr.success(
                                    response.message || '{{ helperTrans('ingaz.order_updated') }}');
                            } else {
                                toastr.error(
                                    '{{ helperTrans('ingaz.update_error') }}');
                            }
                        },
                        error: function() {
                            toastr.error('{{ helperTrans('ingaz.update_error') }}');
                        }
                    });
                });
            });

            // Initialize tooltips
            function initTooltips() {
                $('[data-tooltip]').each(function() {
                    $(this).hover(
                        function() {
                            const tooltip = $('<div class="custom-tooltip">' + $(this).attr(
                                'data-tooltip') + '</div>');
                            $('body').append(tooltip);

                            const offset = $(this).offset();
                            tooltip.css({
                                position: 'absolute',
                                top: offset.top - tooltip.outerHeight() - 10,
                                left: offset.left + ($(this).outerWidth() / 2) - (tooltip
                                    .outerWidth() / 2),
                                zIndex: 9999,
                                backgroundColor: '#333',
                                color: 'white',
                                padding: '4px 8px',
                                borderRadius: '4px',
                                fontSize: '0.75rem',
                                whiteSpace: 'nowrap',
                                boxShadow: '0 2px 4px rgba(0,0,0,0.2)'
                            });
                        },
                        function() {
                            $('.custom-tooltip').remove();
                        }
                    );
                });
            }

            // Initialize tooltips after DataTable loads
            dataTable.on('draw', function() {
                initTooltips();

                // Clean up dropdown text
                $('.dropdown-item').each(function() {
                    let text = $(this).text();
                    // Remove any repeated "الإجراءات" text
                    text = text.replace(/الإجراءات/g, '').trim();
                    // Remove any extra spaces
                    text = text.replace(/\s+/g, ' ').trim();
                    $(this).text(text);

                    // Ensure only one icon per item
                    $(this).find('i').slice(1).remove();
                });

                // Ensure all dropdowns are closed after DataTable redraw
                $('.dropdown').removeClass('show');
                $('.dropdown-menu').hide();
            });

            // Initialize tooltips on page load
            initTooltips();

            // Clean up dropdown text on page load
            // $('.dropdown-item').each(function() {
            //     let text = $(this).text();
            //     // Remove any repeated "الإجراءات" text
            //     text = text.replace(/الإجراءات/g, '').trim();
            //     // Remove any extra spaces
            //     text = text.replace(/\s+/g, ' ').trim();
            //     $(this).text(text);

            //     // Ensure only one icon per item
            //     $(this).find('i').slice(1).remove();
            // });

            // Ensure dropdowns are closed by default
            // $('.dropdown').removeClass('show');
            // $('.dropdown-menu').hide();

            // Force close all dropdowns on page load
            // setTimeout(function() {
            //     $('.dropdown').removeClass('show');
            //     $('.dropdown-menu').hide();
            //     $('.dropdown-toggle').attr('aria-expanded', 'false');
            // }, 100);

            // Handle dropdown toggle properly
            // $(document).on('click', '.dropdown-toggle', function(e) {
            //     e.preventDefault();
            //     e.stopPropagation();

            //     const $dropdown = $(this).closest('.dropdown');
            //     const $dropdownMenu = $dropdown.find('.dropdown-menu');

            //     // إغلاق جميع الدروب داون الأخرى
            //     $('.dropdown').not($dropdown).removeClass('show');
            //     $('.dropdown-menu').not($dropdownMenu).hide();

            //     // تبديل حالة الدروب داون الحالي
            //     $dropdown.toggleClass('show');
            //     $dropdownMenu.toggle();

            //     console.log('Dropdown clicked:', $dropdown.hasClass('show'));
            // });

            // Close dropdowns when clicking outside
            // $(document).on('click', function(e) {
            //     if (!$(e.target).closest('.dropdown').length) {
            //         $('.dropdown').removeClass('show');
            //         $('.dropdown-menu').hide();
            //     }
            // });

            // Prevent dropdown from showing on hover
            // $(document).on('mouseenter', '.table tbody tr', function() {
            //     // Ensure dropdowns in this row are closed
            //     $(this).find('.dropdown').removeClass('show');
            //     $(this).find('.dropdown-menu').hide();
            // });

            // Prevent dropdown from showing on hover of dropdown itself
            // $(document).on('mouseenter', '.dropdown-menu', function(e) {
            //     e.stopPropagation();
            // });

            // Ensure dropdowns are always closed except when explicitly clicked
            // $(document).on('mouseenter mouseleave', '.table tbody tr', function() {
            //     // Only allow dropdown to show if it was explicitly clicked
            //     if (!$(this).find('.dropdown').hasClass('show')) {
            //         $(this).find('.dropdown-menu').hide();
            //     }
            // });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#from_date, #to_date, #status, #job_type, #admin_id, #timer_status, #client_name, #phone, #passport_num')
                    .val('');
                dataTable.ajax.reload();
            });

            // Export buttons
            $('#export_excel').on('click', function() {
                window.location.href = '{{ route('orders.export.excel') }}?' + $.param({
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    status: $('#status').val() || getUrlParameter('status'),
                    job_type: $('#job_type').val(),
                    admin_id: $('#admin_id').val(),
                    timer_status: $('#timer_status').val(),
                    client_name: $('#client_name').val(),
                    phone: $('#phone').val(),
                    passport_num: $('#passport_num').val()
                });
            });

            $('#export_pdf').on('click', function() {
                window.location.href = '{{ route('orders.export.pdf') }}?' + $.param({
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    status: $('#status').val() || getUrlParameter('status'),
                    job_type: $('#job_type').val(),
                    admin_id: $('#admin_id').val(),
                    timer_status: $('#timer_status').val(),
                    client_name: $('#client_name').val(),
                    phone: $('#phone').val(),
                    passport_num: $('#passport_num').val()
                });
            });

            // Handle action buttons
            $(document).on('click', '.addNewContract', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    window.location.href = '{{ route('RecruitmentContract.create') }}?orderId=' + orderId;
                } else {
                    console.error('Order ID not found!');
                    Swal.fire('خطأ!', 'لم يتم العثور على معرف الطلب', 'error');
                }
            });

            $(document).on('click', '.change-maid', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    // Show change maid modal
                    showChangeMaidModal(orderId);
                } else {
                    console.error('Order ID not found!');
                    Swal.fire('خطأ!', 'لم يتم العثور على معرف الطلب', 'error');
                }
            });

            $(document).on('click', '.change-responsible', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    // Show change responsible modal
                    showChangeResponsibleModal(orderId);
                } else {
                    console.error('Order ID not found!');
                    Swal.fire('خطأ!', 'لم يتم العثور على معرف الطلب', 'error');
                }
            });

            // $(document).on('click', '.refuse-order', function(e) {
            //     e.preventDefault();
            //     var orderId = $(this).data('id');

            //     Swal.fire({
            //         title: 'تأكيد رفض الطلب',
            //         text: 'هل أنت متأكد من رفض هذا الطلب؟',
            //         icon: 'warning',
            //         showCancelButton: true,
            //         confirmButtonColor: '#d33',
            //         cancelButtonColor: '#6c757d',
            //         confirmButtonText: 'نعم، ارفض الطلب',
            //         cancelButtonText: 'إلغاء'
            //     }).then((result) => {
            //         if (result.isConfirmed) {
            //             refuseOrder(orderId);
            //         }
            //     });
            // });

            // Handle approve order
            $(document).on('click', '.approve-order', function() {
                const orderId = $(this).data('id');

                Swal.fire({
                    title: 'قبول الطلب',
                    text: 'هل تريد قبول هذا الطلب؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، اقبل الطلب',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        approveOrder(orderId);
                    }
                });
            });

            // Handle reject order
            $(document).on('click', '.reject-order', function() {
                const orderId = $(this).data('id');

                Swal.fire({
                    title: 'رفض الطلب',
                    text: 'هل تريد رفض هذا الطلب؟ سيتم إرجاع العاملة متاحة.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، ارفض الطلب',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        rejectOrder(orderId);
                    }
                });
            });


        });

        // Functions for modals and actions
        function showChangeMaidModal(orderId) {
            // Create and show the modal
            const modalHtml = `
        <div class="modal fade" id="changeMaidOrderModal" tabindex="-1" aria-labelledby="changeMaidOrderModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeMaidOrderModalLabel">
                            <i class="fas fa-exchange-alt"></i>
                            تغيير العاملة في الطلب رقم #${orderId}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="changeMaidOrderForm">
                            <input type="hidden" id="order_id" value="${orderId}">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="maid_search_order" class="form-label">
                                            <i class="fas fa-search"></i>
                                            البحث في العاملات
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="maid_search_order"
                                                   placeholder="ابحث بالاسم أو رقم الجواز..."
                                                   onkeyup="searchMaidsOrderWithDelay(this.value)"
                                                   onkeydown="handleSearchOrderKeydown(event)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearOrderSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">
                                            اكتب للبحث في العاملات المتاحات - اضغط Enter لفتح القائمة
                                        </small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="new_maid_id_order" class="form-label">
                                            <i class="fas fa-user-plus"></i>
                                            اختيار العاملة الجديدة
                                        </label>
                                        <select class="form-control" id="new_maid_id_order" name="new_maid_id" required>
                                            <option value="">اختر العاملة الجديدة...</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            سيتم تحميل العاملات المتاحات...
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            حالة العاملة السابقة
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                   id="status_available_order" value="available" required>
                                            <label class="form-check-label" for="status_available_order">
                                                <i class="fas fa-check text-success"></i>
                                                متاحة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                   id="status_unavailable_order" value="backout" required>
                                            <label class="form-check-label" for="status_unavailable_order">
                                                <i class="fas fa-ban text-danger"></i>
                                                باك اوت
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="reason_order" class="form-label">
                                    <i class="fas fa-comment"></i>
                                    سبب التغيير (اختياري)
                                </label>
                                <textarea class="form-control" id="reason_order" name="reason" rows="3"
                                          placeholder="اكتب سبب تغيير العاملة..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitChangeMaidOrder()">
                            <i class="fas fa-save"></i>
                            حفظ التغيير
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

            // Remove existing modal if any
            $('#changeMaidOrderModal').remove();

            // Add modal to body
            $('body').append(modalHtml);

            // Show modal
            $('#changeMaidOrderModal').modal('show');

            // Load available maids after modal is shown
            setTimeout(function() {
                loadAvailableMaidsOrder();
            }, 300);
        }

        // تحميل العاملات المتاحات للطلبات
        function loadAvailableMaidsOrder(searchTerm = '') {
            console.log('Loading available maids for order with search term:', searchTerm);

            $.ajax({
                url: '{{ route('RecruitmentContract.availableMaids') }}',
                method: 'GET',
                data: {
                    search: searchTerm,
                    limit: 50
                },
                success: function(response) {
                    console.log('Maids loaded:', response);

                    if (response.success && response.maids) {
                        const select = $('#new_maid_id_order');
                        select.empty();
                        select.append('<option value="">اختر العاملة الجديدة...</option>');

                        if (response.maids.length > 0) {
                            response.maids.forEach(function(maid) {
                                const optionText = `${maid.name} - ${maid.passport_num}` +
                                    (maid.nationality_name ? ` (${maid.nationality_name})` : '');
                                select.append(`<option value="${maid.id}">${optionText}</option>`);
                            });

                            select.siblings('.form-text').text(`تم تحميل ${response.maids.length} عاملة متاحة`);
                        } else {
                            select.siblings('.form-text').text('لا توجد عاملات متاحة');
                        }
                    } else {
                        console.error('Failed to load maids:', response);
                        $('#new_maid_id_order').siblings('.form-text').text('فشل في تحميل العاملات');
                    }
                },
                error: function(xhr) {
                    console.error('Error loading maids:', xhr);
                    $('#new_maid_id_order').siblings('.form-text').text('حدث خطأ أثناء تحميل العاملات');
                }
            });
        }

        // البحث في العاملات مع تأخير للطلبات
        let searchTimeoutOrder;

        function searchMaidsOrderWithDelay(searchTerm) {
            clearTimeout(searchTimeoutOrder);
            searchTimeoutOrder = setTimeout(function() {
                loadAvailableMaidsOrder(searchTerm);
            }, 300);
        }

        // التعامل مع الضغط على Enter في البحث للطلبات
        function handleSearchOrderKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const searchTerm = event.target.value;
                loadAvailableMaidsOrder(searchTerm);

                // فتح القائمة المنسدلة
                setTimeout(function() {
                    $('#new_maid_id_order').focus().click();
                }, 500);
            }
        }

        // مسح البحث للطلبات
        function clearOrderSearch() {
            $('#maid_search_order').val('');
            loadAvailableMaidsOrder('');
        }

        // إرسال نموذج تغيير العاملة للطلبات
        function submitChangeMaidOrder() {
            const form = document.getElementById('changeMaidOrderForm');
            const formData = new FormData(form);
            const orderId = document.getElementById('order_id').value;

            // التحقق من البيانات
            if (!formData.get('new_maid_id')) {
                Swal.fire('خطأ!', 'يرجى اختيار العاملة الجديدة', 'error');
                return;
            }

            if (!formData.get('old_maid_status')) {
                Swal.fire('خطأ!', 'يرجى اختيار حالة العاملة السابقة', 'error');
                return;
            }

            // تأكيد العملية
            Swal.fire({
                title: 'تأكيد تغيير العاملة',
                text: 'هل أنت متأكد من تغيير العاملة في هذا الطلب؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، غير العاملة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    // إرسال البيانات
                    $.ajax({
                        url: `{{ route('orders.changeMaidById', ':id') }}`.replace(':id', orderId),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            maid_id: formData.get('new_maid_id'),
                            old_maid_status: formData.get('old_maid_status'),
                            reason: formData.get('reason')
                        },
                        success: function(response) {
                            console.log('Change maid response:', response);

                            if (response.success) {
                                Swal.fire('نجح!', response.message || 'تم تغيير العاملة بنجاح',
                                    'success');
                                $('#changeMaidOrderModal').modal('hide');
                                dataTable.ajax.reload();
                            } else {
                                Swal.fire('خطأ!', response.message || 'فشل في تغيير العاملة', 'error');
                            }
                        },
                        error: function(xhr) {
                            console.error('Error changing maid:', xhr);
                            let message = 'حدث خطأ أثناء تغيير العاملة';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire('خطأ!', message, 'error');
                        }
                    });
                }
            });
        }

        function showChangeResponsibleModal(orderId) {
            // This function can be implemented similar to change maid
            Swal.fire('قريباً!', 'هذه الميزة قيد التطوير', 'info');
        }

        function approveOrder(orderId) {
            $.ajax({
                url: '/admin/rental-orders/' + orderId + '/approve',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        dataTable.ajax.reload();
                        toastr.success(response.message || 'تم قبول الطلب بنجاح');
                    } else {
                        toastr.error(response.message || 'حدث خطأ أثناء قبول الطلب');
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء قبول الطلب');
                }
            });
        }

        function rejectOrder(orderId) {
            $.ajax({
                url: '/admin/rental-orders/' + orderId + '/reject',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        dataTable.ajax.reload();
                        toastr.success(response.message || 'تم رفض الطلب بنجاح');
                    } else {
                        toastr.error(response.message || 'حدث خطأ أثناء رفض الطلب');
                    }
                },
                error: function() {
                    toastr.error('حدث خطأ أثناء رفض الطلب');
                }
            });
        }

        function refuseOrder(orderId) {
            // This function can be implemented to refuse orders
            Swal.fire('قريباً!', 'هذه الميزة قيد التطوير', 'info');
        }

        function deleteOrder(orderId) {
            // This function can be implemented to delete orders
            Swal.fire('قريباً!', 'هذه الميزة قيد التطوير', 'info');
        }
    </script>
@endsection
