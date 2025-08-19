@extends('admin.layout.indexs.index')
@section("style")
    <!-- DataTables CSS من CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- الملفات المحلية -->
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
    <div>
        @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
        <a href="{{ route('rent-contract.settings') }}" class="btn btn-info me-2">
            <i class="fas fa-sliders-h me-2"></i>
            إعدادات العقد
        </a>
        <a href="{{ route('rent-contract-terms.edit') }}" class="btn btn-warning me-2">
            <i class="fas fa-list-alt me-2"></i>
            إدارة بنود العقد
        </a>
        <a href="{{ route('RentContract.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            {{helperTrans("ingaz.add Rent contract")}}
        </a>
        @endif
    </div>
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                مغلقة - متاحة</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableContracts">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                تم النقل</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="transferredContracts">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
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
                        <option value="available">مغلق - متاحة</option>
                        <option value="transferred">تم النقل</option>
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

<!-- Modal لإغلاق العقد -->
<div class="modal fade" id="closeContractModal" tabindex="-1" aria-labelledby="closeContractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="closeContractModalLabel">
                    <i class="fas fa-times-circle me-2"></i>
                    إنهاء عقد الإيجار
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- معلومات العقد -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات العقد</h6>
                            </div>
                            <div class="card-body">
                                <div class="row" id="contractDetails">
                                    <div class="col-md-3">
                                        <strong>رقم العقد:</strong>
                                        <p id="contractId">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>العميل:</strong>
                                        <p id="clientName">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>العاملة:</strong>
                                        <p id="maidName">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>الحالة الحالية:</strong>
                                        <p id="currentStatus">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>تاريخ البداية:</strong>
                                        <p id="startDate">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>تاريخ النهاية:</strong>
                                        <p id="endDate">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>المدة المتبقية:</strong>
                                        <p id="remainingDays">-</p>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>قيمة العقد:</strong>
                                        <p id="contractValue">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- خيارات الإغلاق -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-success h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user-check me-2"></i>
                                    إغلاق العقد وإرجاع العاملة متاحة
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="mb-3">سيتم إغلاق العقد وإرجاع العاملة لتصبح متاحة للإيجار مرة أخرى</p>
                                <button type="button" class="btn btn-success btn-lg" id="closeAndMakeAvailableBtn">
                                    <i class="fas fa-check me-2"></i>
                                    إغلاق وإرجاع العاملة
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning h-100">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    إغلاق العقد ونقل الكفالة
                                </h6>
                            </div>
                            <div class="card-body text-center">
                                <i class="fas fa-handshake fa-3x text-warning mb-3"></i>
                                <p class="mb-3">سيتم إغلاق العقد ونقل كفالة العاملة لعميل آخر</p>
                                <button type="button" class="btn btn-warning btn-lg" id="transferSponsorshipBtn">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    إغلاق ونقل الكفالة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نموذج نقل الكفالة (مخفي في البداية) -->
                <div id="transferSponsorshipForm" style="display: none;" class="mt-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>
                                تأكيد نقل الكفالة
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- معلومات النقل -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <small class="text-muted">الكفيل القديم</small>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="mb-1">شركة أسس الإنجاز للاستقدام</h6>
                                            <small class="text-muted">عقد إيجار</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <small class="text-muted">الكفيل الجديد</small>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="mb-1" id="transferClientName">-</h6>
                                            <small class="text-muted">عقد استقدام</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form id="sponsorshipTransferForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="transferAmount" class="form-label">قيمة العقد الجديد <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="transferAmount" name="transferAmount" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="totalAmount" class="form-label">المبلغ الكامل <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="totalAmount" name="totalAmount" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="paidAmount" class="form-label">المبلغ المدفوع <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="paidAmount" name="paidAmount" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="remainingAmount" class="form-label">المبلغ المتبقي</label>
                                            <input type="number" class="form-control" id="remainingAmount" name="remainingAmount" step="0.01" readonly>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="transferNotes" class="form-label">ملاحظات</label>
                                            <textarea class="form-control" id="transferNotes" name="transferNotes" rows="3" placeholder="ملاحظات إضافية حول عملية نقل الكفالة..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" id="cancelTransferBtn">
                                        <i class="fas fa-times me-2"></i>
                                        إلغاء
                                    </button>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-check me-2"></i>
                                        تأكيد نقل الكفالة
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section("js")
    <!-- DataTables JS من CDN كبديل آمن -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- الملفات المحلية -->
    <!-- <script src="{{asset("admin")}}/assets/jsbundle/dataTables.bundle.js"></script> -->
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script src="{{asset("admin")}}/custom/orders-dropdown-fix.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
        console.log('Script loaded, jQuery available:', typeof $);
        console.log('DataTable available:', typeof $.fn.DataTable);

        $(document).ready(function() {
            console.log('Document ready, initializing DataTable...');
            console.log('Table element found:', $('#dataTableObject').length);

            // التحقق من تحميل DataTables
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables library is not loaded!');
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في تحميل المكتبات',
                    text: 'لم يتم تحميل مكتبة DataTables بشكل صحيح'
                });
                return;
            }

                        try {
                // Initialize DataTable with simplified settings
                let dataTable = $('#dataTableObject').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('RentContract.index') }}",
                        data: function(d) {
                            d.status_filter = $('#statusFilter').val();
                            d.date_from = $('#dateFromFilter').val();
                            d.date_to = $('#dateToFilter').val();
                            d.marketer_id = $('#marketerFilter').val();
                        }
                    },
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'user_id', name: 'user_id'},
                        {data: 'phone', name: 'phone'},
                        {data: 'maid_id', name: 'maid_id'},
                        {data: 'contract_info', name: 'contract_info'},
                        {data: 'marketer', name: 'marketer'},
                        {data: 'status', name: 'status'},
                        {data: 'remaining_days', name: 'remaining_days'},
                        {data: 'actions', name: 'actions', orderable: false, searchable: false}
                    ],
                    language: {
                        "sProcessing": "جارٍ التحميل...",
                        "sLengthMenu": "أظهر _MENU_ مدخلات",
                        "sZeroRecords": "لم يعثر على أية سجلات",
                        "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                        "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                        "sInfoFiltered": "(منتقاة من مجموع _MAX_ مُدخل)",
                        "sInfoPostFix": "",
                        "sSearch": "ابحث:",
                        "sUrl": "",
                        "oPaginate": {
                            "sFirst": "الأول",
                            "sPrevious": "السابق",
                            "sNext": "التالي",
                            "sLast": "الأخير"
                        }
                    },
                    drawCallback: function() {
                        console.log('Table drawn successfully');
                        $('#loadingOverlay').hide();
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
                        $('#availableContracts').text(data.available || 0);
                        $('#transferredContracts').text(data.transferred || 0);
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

            // إضافة متغير لحفظ معرف العقد المحدد
            let selectedContractId = null;

            // التعامل مع زر إنهاء العقد
            $(document).on('click', '.complete-contract', function() {
                selectedContractId = $(this).data('id');

                // جلب بيانات العقد
                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/details`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const contract = response.contract;

                            // تعبئة بيانات العقد في الـ modal
                            $('#contractId').text(contract.id);
                            $('#clientName').text(contract.client_name || '-');
                            $('#maidName').text(contract.maid_name || '-');
                            $('#currentStatus').text(contract.status_text || '-');
                            $('#startDate').text(contract.start_date || '-');
                            $('#endDate').text(contract.end_date || '-');
                            $('#remainingDays').text(contract.remaining_days || '-');
                            $('#contractValue').text((contract.amount || 0) + ' ريال');

                            // تعبئة اسم العميل في نموذج نقل الكفالة
                            $('#transferClientName').text(contract.client_name || '-');

                            // إظهار الـ modal
                            $('#closeContractModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء جلب بيانات العقد'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء جلب بيانات العقد'
                        });
                    }
                });
            });

            // إغلاق العقد وإرجاع العاملة متاحة
            $('#closeAndMakeAvailableBtn').click(function() {
                Swal.fire({
                    title: 'تأكيد الإغلاق',
                    text: 'هل أنت متأكد من إغلاق العقد وإرجاع العاملة متاحة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إغلاق العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/RentContract/${selectedContractId}/close-and-make-available`,
                            type: 'POST',
                data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message || 'تم إغلاق العقد وإرجاع العاملة متاحة بنجاح'
                                    }).then(() => {
                                        $('#closeContractModal').modal('hide');
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message || 'حدث خطأ أثناء إغلاق العقد'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إغلاق العقد'
                                });
                            }
                        });
                }
            });
        });

            // إظهار نموذج نقل الكفالة
            $('#transferSponsorshipBtn').click(function() {
                $('#transferSponsorshipForm').show();
                $(this).closest('.row').hide();
            });

            // إلغاء نقل الكفالة
            $('#cancelTransferBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('.row').first().show();
                $('#sponsorshipTransferForm')[0].reset();
            });

            // حساب المبلغ المتبقي تلقائياً
            $('#totalAmount, #paidAmount').on('input', function() {
                const total = parseFloat($('#totalAmount').val()) || 0;
                const paid = parseFloat($('#paidAmount').val()) || 0;
                const remaining = total - paid;
                $('#remainingAmount').val(remaining.toFixed(2));
            });

            // تأكيد نقل الكفالة
            $('#sponsorshipTransferForm').submit(function(e) {
                e.preventDefault();

                const formData = {
                    contract_id: selectedContractId,
                    transfer_amount: $('#transferAmount').val(),
                    total_amount: $('#totalAmount').val(),
                    paid_amount: $('#paidAmount').val(),
                    remaining_amount: $('#remainingAmount').val(),
                    notes: $('#transferNotes').val(),
                    _token: '{{ csrf_token() }}'
                };

            $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/transfer-sponsorship`,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message || 'تم إغلاق العقد ونقل الكفالة بنجاح'
                            }).then(() => {
                                $('#closeContractModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء نقل الكفالة'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء نقل الكفالة'
                        });
                    }
                });
            });
        });
</script>
@endsection
