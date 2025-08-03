@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
@endsection

@section('page-title')
{{helperTrans("ingaz.All Rent contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))

  <!-- Header Section -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h3 class="h3 mb-0 text-gray-800">
        <i class="fas fa-handshake text-primary me-2"></i>
        {{helperTrans("ingaz.All Rent contracts")}}
    </h3>
    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
    <a href="{{ route('RentContract.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>
        {{helperTrans("ingaz.add Rent contract")}}
    </a>
    @endif
</div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                إجمالي عقود التأجير</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalContracts">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                العقود النشطة</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeContracts">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">العقود المكتملة</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedContracts">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                قيمة العقود الإجمالية</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalValue">0 ريال</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card filter-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    فلاتر البحث
                </h6>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                <i class="fas fa-chevron-down" id="filtersIcon"></i>
                    </button>
        </div>
        <div class="card-body collapse" id="filtersCollapse">
            <div class="row">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">حالة العقد:</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="new">جديد</option>
                        <option value="active">نشط</option>
                        <option value="completed">مكتمل</option>
                        <option value="cancelled">ملغي</option>
                        <option value="suspended">معلق</option>
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
                    <input type="text" id="clientPhoneFilter" class="form-control" placeholder="ابحث برقم الجوال">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                    <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                </div>
                <div class="col-md-3">
                    <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                    <input type="text" id="maidPassportFilter" class="form-control" placeholder="ابحث برقم الجواز">
                </div>
                <div class="col-md-3">
                    <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                    <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                </div>
                <div class="col-md-3">
                    <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                    <input type="text" id="contractNumberFilter" class="form-control" placeholder="ابحث برقم العقد">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <label for="marketerFilter" class="form-label">المسوق:</label>
                    <select id="marketerFilter" class="form-select">
                        <option value="">جميع المسوقين</option>
                        <!-- سيتم تعبئتها من الـ Controller -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="warrantyStatusFilter" class="form-label">حالة الضمان:</label>
                    <select id="warrantyStatusFilter" class="form-select">
                        <option value="">جميع حالات الضمان</option>
                        <option value="not_applicable">غير قابل للتطبيق</option>
                        <option value="active">نشط</option>
                        <option value="expired">منتهي</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="applyFilters">
                            <i class="fas fa-search me-2"></i>تطبيق الفلاتر
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearFilters">
                            <i class="fas fa-times me-2"></i>مسح الفلاتر
                        </button>
                        <button type="button" class="btn btn-success" id="exportExcel">
                            <i class="fas fa-file-excel me-2"></i>تصدير Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="clean-loading-overlay" style="display: none;">
        <div class="clean-loading-content">
            <div class="clean-loader-main">
                <div class="clean-loader-dot"></div>
                <div class="clean-loader-dot"></div>
                <div class="clean-loader-dot"></div>
            </div>
            <div class="clean-loading-text">جاري تحميل عقود التأجير...</div>
            <div class="clean-loading-subtitle">يرجى الانتظار</div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>
                قائمة عقود التأجير
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableObject" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>رقم الجوال</th>
                            <th>العاملة</th>
                            <th>معلومات العقد</th>
                            <th>المسوق</th>
                            <th>الحالة</th>
                            <th>الأيام المتبقية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- سيتم تعبئتها بواسطة DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section("js")
    <script src="{{asset("admin")}}/assets/jsbundle/dataTables.bundle.js"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script src="{{asset("admin")}}/custom/orders-dropdown-fix.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
        console.log('Script loaded, jQuery available:', typeof $);
        console.log('DataTable available:', typeof $.fn.DataTable);

        $(document).ready(function() {
            console.log('Document ready, initializing DataTable...');
            console.log('Table element found:', $('#dataTableObject').length);

            try {
                // Initialize DataTable
                let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('RentContract.index') }}",
                    timeout: 15000,
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.marketer_id = $('#marketerFilter').val();
                        d.warranty_status = $('#warrantyStatusFilter').val();
                        console.log('DataTable request data:', d);
                    },
                    success: function(response) {
                        console.log('DataTable success response:', response);
                    },
                    error: function(xhr, error, code) {
                        console.error('DataTable AJAX Error:', error);
                        console.error('XHR:', xhr);
                        console.error('Status:', xhr.status);
                        console.error('Response Text:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في تحميل البيانات',
                            text: 'حدث خطأ أثناء تحميل عقود التأجير: ' + error
                        });
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'user_id', name: 'user_id', orderable: true, searchable: true},
                    {data: 'phone', name: 'phone', orderable: false, searchable: false},
                    {data: 'maid_id', name: 'maid_id', orderable: false, searchable: false},
                    {data: 'contract_info', name: 'contract_info', orderable: false, searchable: false},
                    {data: 'marketer', name: 'marketer', orderable: true, searchable: true},
                    {data: 'status', name: 'status', orderable: true, searchable: true},
                    {data: 'remaining_days', name: 'remaining_days', orderable: false, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                stateSave: false,
                deferRender: true,
                autoWidth: false,
                destroy: true,
                dom: 'lrtip',
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                },
                initComplete: function() {
                    $('#loadingOverlay').hide();
                    $('body').removeClass('loading');
                },
                preDrawCallback: function() {
                    $('#loadingOverlay').show();
                    $('body').addClass('loading');
                },
                drawCallback: function() {
                    $('#loadingOverlay').hide();
                    $('body').removeClass('loading');

                    // Update statistics
                    updateStatistics();
                }
            });

            // Apply Filters
            $('#applyFilters').click(function() {
                dataTable.ajax.reload();
            });

            // Clear Filters
            $('#clearFilters').click(function() {
                $('#statusFilter, #marketerFilter, #warrantyStatusFilter').val('');
                $('#dateFromFilter, #dateToFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter').val('');
                dataTable.ajax.reload();
            });

            // Export Excel
            $('#exportExcel').click(function() {
                window.location.href = "{{ route('RentContract.export.excel') }}?" + $.param({
                    status: $('#statusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    client_phone: $('#clientPhoneFilter').val(),
                    client_name: $('#clientNameFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_name: $('#maidNameFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    warranty_status: $('#warrantyStatusFilter').val()
                });
            });

            // Complete Contract Handler
            $(document).on('click', '.complete-contract', function() {
                let contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد إنهاء العقد',
                    text: 'هل أنت متأكد من إنهاء هذا العقد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'نعم، إنهاء العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('RentContract.complete', ':id') }}".replace(':id', contractId),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message
                                    });
                                    dataTable.ajax.reload();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إنهاء العقد'
                                });
                            }
                        });
                }
            });
        });

            // Delete Contract Handler
            $(document).on('click', '.delete-contract', function() {
                let contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع عن هذا الإجراء.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = $('<form>', {
                            'method': 'POST',
                            'action': "{{ route('RentContract.destroy', ':id') }}".replace(':id', contractId)
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            // Update Statistics
            function updateStatistics() {
            $.ajax({
                    url: "{{ route('RentContract.statistics') }}",
                method: 'GET',
                    success: function(data) {
                        $('#totalContracts').text(data.total || 0);
                        $('#activeContracts').text(data.active || 0);
                        $('#completedContracts').text(data.completed || 0);
                        $('#totalValue').text((data.totalValue || 0).toLocaleString() + ' ريال');
                    },
                    error: function() {
                        console.log('Error loading statistics');
                    }
                });
            }

            // Toggle filters collapse icon
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });
            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            // Load initial statistics
            updateStatistics();

            } catch (error) {
                console.error('Error initializing DataTable:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في تحميل الصفحة',
                    text: 'حدث خطأ أثناء تحميل جدول البيانات: ' + error.message
                });
            }
        });
</script>
@endsection
