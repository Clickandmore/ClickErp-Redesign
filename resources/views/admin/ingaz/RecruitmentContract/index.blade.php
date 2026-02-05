@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection
@section('page-title')
    {{ helperTrans('ingaz.All Recruitment contracts') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-contract text-primary me-2"></i>
                {{ helperTrans('ingaz.All Recruitment contracts') }}
            </h3>
            <a href="{{ route('RecruitmentContract.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                {{ helperTrans('ingaz.add_recruitment_contract') }}
            </a>
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
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="card-body collapse" id="filtersCollapse">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">حالة العقد:</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="new">جديد</option>
                                <option value="msaned">مساند</option>
                                <option value="tafwed">تفويض</option>
                                <option value="taffez">التفييز</option>
                                <option value="tazker">التذاكر</option>
                                <option value="arrival">وصول</option>
                                <option value="sari">ساري</option>
                                <option value="completed">مكتمل</option>
                                <option value="back">مرتجع</option>
                                <option value="shelter">الايواء</option>
                                <option value="pending_maid_selection">انتظار اختيار العاملة</option>
                                <option value="accreditation_contracts">اعتماد العقود</option>
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
                            <input type="text" id="clientPhoneFilter" class="form-control"
                                placeholder="ابحث برقم الجوال (5********)">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                            <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                        </div>
                        <div class="col-md-3">
                            <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                            <input type="text" id="maidPassportFilter" class="form-control"
                                placeholder="ابحث برقم الجواز">
                        </div>
                        <div class="col-md-3">
                            <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                            <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                        </div>
                        <div class="col-md-3">
                            <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                            <input type="text" id="contractNumberFilter" class="form-control"
                                placeholder="ابحث برقم العقد">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="nationalityFilter" class="form-label">جنسية العاملة:</label>
                            <select id="nationalityFilter" class="form-select">
                                <option value="">جميع الجنسيات</option>
                                @if (isset($filterOptions) && isset($filterOptions['nationalities']))
                                    @foreach ($filterOptions['nationalities'] as $nationality)
                                        <option value="{{ $nationality->sub_title }}">{{ $nationality->sub_title }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="religionFilter" class="form-label">ديانة العاملة:</label>
                            <select id="religionFilter" class="form-select">
                                <option value="">جميع الديانات</option>
                                @if (isset($filterOptions) && isset($filterOptions['religions']))
                                    @foreach ($filterOptions['religions'] as $religion)
                                        <option value="{{ $religion->title }}">{{ $religion->title }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="officeFilter" class="form-label"> المكتب الخارجي:</label>
                            <select id="officeFilter" class="form-select">
                                <option value="">جميع المكاتب الخارجية</option>
                                @if (isset($filterOptions) && isset($filterOptions['offices']))
                                    @foreach ($filterOptions['offices'] as $office)
                                        <option value="{{ $office->id }}">{{ $office->title }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3" style="display: none;">
                            <label for="warrantyStatusFilter" class="form-label">حالة الضمان:</label>
                            <select id="warrantyStatusFilter" class="form-select">
                                <option value="">جميع حالات الضمان</option>
                                <option value="active">نشط (تحت الضمان)</option>
                                <option value="expired">منتهي (خارج الضمان)</option>
                                <option value="not_applicable">غير قابل للتطبيق</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="marketerFilter" class="form-label">المسوق:</label>
                            <select id="marketerFilter" class="form-select">
                                <option value="">جميع المسوقين</option>
                                @if (isset($filterOptions) && isset($filterOptions['marketers']))
                                    @foreach ($filterOptions['marketers'] as $marketer)
                                        <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">لا توجد بيانات</option>
                                @endif
                            </select>
                        </div>

                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="cityFilter" class="form-label">مدينة العميل:</label>
                            <input type="text" id="cityFilter" class="form-control" placeholder="ابحث بالمدينة">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>

                                <button type="button" id="clearFilters" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> مسح
                                </button>
                                <button type="button" id="btnSubmit" class="btn btn-primary ms-2">
                                    <i class="fas fa-search me-1"></i>بحث
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-file-export me-2"></i>
                                    تصدير البيانات
                                </h5>
                            </div>
                            <div>
                                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT))
                                    <a href="{{ route('RecruitmentContract.export.pdf') }}" class="btn btn-danger me-2"
                                        target="_blank">
                                        <i class="fa fa-file-pdf me-1"></i> {{ helperTrans('ingaz.export_pdf') }}
                                    </a>
                                    <a href="{{ route('RecruitmentContract.export.excel') }}" class="btn btn-success">
                                        <i class="fa fa-file-excel me-1"></i> {{ helperTrans('ingaz.export_excel') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        قائمة العقود
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Modern Loading Overlay -->
                    <div id="loadingOverlay"
                        style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(74, 144, 226, 0.1), rgba(80, 200, 120, 0.1)); backdrop-filter: blur(5px); z-index: 1000; display: flex; justify-content: center; align-items: center; border-radius: 0.375rem;">
                        <div class="text-center">
                            <!-- Clean Modern Spinner -->
                            <div class="clean-loader mb-3">
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                                <div class="loader-dot"></div>
                            </div>
                            <div class="loading-text">
                                <i class="fas fa-database me-2"></i>
                                جاري تحميل بيانات العقود...
                            </div>
                            <div class="loading-subtitle">يرجى الانتظار</div>
                        </div>
                    </div>

                    <table id="dataTableObject" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>معلومات العميل</th>
                                <th>العاملة</th>
                                <th>معلومات العقد</th>
                                <th>المسوق</th>
                                <th>الحالة</th>
                                <th>تحديث الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog"
            aria-labelledby="updateStatusModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateStatusModalLabel">تحديث حالة العقد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="rowId" name="rowId">
                        <input type="hidden" id="status" name="status">
                        <input type="hidden" id="nextStatus" name="nextStatus">

                        <div class="mb-3">
                            <label for="currentStatus" class="form-label">الحالة الحالية:</label>
                            <input type="text" id="currentStatus" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="nextStatusDisplay" class="form-label">المرحلة التالية:</label>
                            <input type="text" id="nextStatusDisplay" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="statusDate" class="form-label">تاريخ التحديث:</label>
                            <input type="date" id="statusDate" required name="statusDate" class="form-control"
                                value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sendSmsNotification"
                                    name="sendSmsNotification" checked>
                                <label class="form-check-label" for="sendSmsNotification">
                                    <i class="fas fa-sms me-1"></i>
                                    إرسال رسالة نصية للعميل بتحديث الحالة
                                </label>
                            </div>
                            <small class="text-muted">سيتم إرسال رسالة نصية للعميل تحتوي على الحالة الجديدة للعقد</small>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-success" id="saveStatusBtn">
                            <i class="fas fa-save me-1"></i>تحديث الحالة
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal تأكيد الإرجاع -->
        <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmBackModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmBackModalLabel">تأكيد إرجاع العقد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            هل أنت متأكد من إرجاع هذا العقد؟ هذا الإجراء لا يمكن التراجع عنه.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-danger" id="confirmBackBtn">
                            <i class="fas fa-undo me-1"></i>تأكيد الإرجاع
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- موديل إلغاء العقد -->
        <div class="modal fade" id="cancelContractModal" tabindex="-1" aria-labelledby="cancelContractModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelContractModalLabel">
                            <i class="fas fa-times-circle text-danger"></i>
                            إلغاء العقد
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- معلومات العقد -->
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> تحذير:</h6>
                            <p>سيتم إلغاء العقد رقم <strong id="contractNumber"></strong></p>
                            <p>العميل: <strong id="clientName"></strong></p>
                            <p>العاملة: <strong id="maidName"></strong></p>
                        </div>

                        <form id="cancelContractForm">
                            @csrf
                            <input type="hidden" id="contractId" name="contract_id">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="cancel_reason" class="form-label">
                                            <i class="fas fa-comment"></i>
                                            سبب الإلغاء
                                        </label>
                                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3"
                                            placeholder="اكتب سبب إلغاء العقد..." required></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            حالة العاملة بعد الإلغاء
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="maid_status_after_cancel" id="status_available" value="available"
                                                required>
                                            <label class="form-check-label" for="status_available">
                                                <i class="fas fa-check text-success"></i>
                                                متاحة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                name="maid_status_after_cancel" id="status_unavailable" value="backout"
                                                required>
                                            <label class="form-check-label" for="status_unavailable">
                                                <i class="fas fa-ban text-danger"></i>
                                                باك اوت
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="button" class="btn btn-danger" onclick="submitCancelContract()">
                            <i class="fas fa-trash"></i>
                            تأكيد الإلغاء
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>

    <script>
        $(document).ready(function() {
            // دالة لقراءة الـ status من الـ URL
            function getStatusFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('status');
            }

            // دالة لقراءة warranty_status من الـ URL
            function getWarrantyStatusFromUrl() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('warranty_status');
            }

            function toggleWarrantyFilter() {
                const isArrival = $('#statusFilter').val() === 'arrival';
                const wrapper = $('#warrantyStatusFilter').closest('.col-md-3');

                if (isArrival) {
                    wrapper.show();
                    $('#warrantyStatusFilter').show();
                } else {
                    wrapper.hide();
                    $('#warrantyStatusFilter').hide();
                    $('#warrantyStatusFilter').val('');
                }
            }

            // تطبيق الـ status من الـ URL على الفلتر أولاً
            const urlStatus = getStatusFromUrl();
            const urlWarrantyStatus = getWarrantyStatusFromUrl();

            if (urlStatus) {
                $('#statusFilter').val(urlStatus);
                console.log('Status from URL applied to filter:', urlStatus);
                // إزالة الفلتر من localStorage لتجنب التضارب
                localStorage.removeItem('contractFilters');

                toggleWarrantyFilter();

                // تطبيق فلتر warranty_status إذا كان موجود في URL
                if (urlStatus === 'arrival' && urlWarrantyStatus) {
                    $('#warrantyStatusFilter').val(urlWarrantyStatus);
                    console.log('Warranty status from URL applied to filter:', urlWarrantyStatus);
                }
            }

            let columns = [{
                    "data": 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "phone",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "maid_id",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "contract_info",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "marketer",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "status",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "updateStatus",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "actions",
                    orderable: true,
                    searchable: true
                },
            ];

            // إظهار مؤشر التحميل
            $('#loadingOverlay').show();
            $('body').addClass('loading');

            // إنشاء DataTable مع الفلتر المطبق
            let dataTable = $('#dataTableObject').DataTable({
                processing: CleanLoader.getCleanProcessingMessage(),
                serverSide: true,
                searching: false,
                paging: true, // التأكد من تفعيل التصفح
                pageLength: 10, // تقليل عدد الصفوف لتسريع التحميل
                lengthMenu: [
                    [10, 25, 50, 100, 500],
                    [10, 25, 50, 100, 500]
                ], // خيارات أقل
                stateSave: false, // إيقاف حفظ الحالة لتسريع التحميل
                deferRender: true, // تحسين الأداء للبيانات الكبيرة
                scroller: false,
                autoWidth: false, // إيقاف حساب العرض التلقائي
                destroy: true, // السماح بإعادة التهيئة
                ajax: {
                    url: "{{ route('RecruitmentContract.index') }}",
                    timeout: 15000, // تقليل timeout إلى 15 ثانية
                    type: 'GET',
                    dataSrc: function(json) {
                        // معالجة البيانات قبل العرض
                        console.log('Data received:', json.data.length, 'records');
                        return json.data;
                    },
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.nationality = $('#nationalityFilter').val();
                        d.religion = $('#religionFilter').val();
                        d.office_id = $('#officeFilter').val();
                        d.city = $('#cityFilter').val();
                        d.marketer_id = $('#marketerFilter').val(); // Add marketer filter

                        // إضافة فلتر حالة الضمان فقط إذا كانت الحالة "عقود مكتملة"
                        if ($('#statusFilter').val() === 'arrival') {
                            d.warranty_status = $('#warrantyStatusFilter').val();
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Response:', xhr.responseText);

                        // إظهار رسالة خطأ للمستخدم
                        $('#dataTableObject tbody').html(
                            '<tr><td colspan="100%" class="text-center text-danger">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' +
                            'حدث خطأ في تحميل البيانات. يرجى المحاولة مرة أخرى.' +
                            '</td></tr>'
                        );
                    }
                },
                columns: columns,
                order: [
                    [0, 'desc']
                ],
                pageLength: 10, // تقليل عدد الصفوف لتسريع التحميل
                lengthMenu: [
                    [10, 25, 50, 100, 500],
                    [10, 25, 50, 100, 500]
                ], // خيارات أقل
                stateSave: false, // إيقاف حفظ الحالة لتسريع التحميل
                deferRender: true, // تحسين الأداء للبيانات الكبيرة
                scroller: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                    emptyTable: "لا توجد عقود متاحة",
                    zeroRecords: "لم يتم العثور على عقود تطابق معايير البحث",
                    processing: '<div class="d-flex justify-content-center align-items-center p-4"><div class="clean-loader-small me-3"><div class="loader-dot-small"></div><div class="loader-dot-small"></div><div class="loader-dot-small"></div></div><div class="text-primary fw-bold">جاري المعالجة...</div></div>',
                    search: "البحث:",
                    lengthMenu: "عرض _MENU_ سجل",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    infoEmpty: "عرض 0 إلى 0 من 0 سجل",
                    infoFiltered: "(مفلتر من _MAX_ سجل إجمالي)"
                },
                responsive: true,
                dom: 'lrtip', // إزالة الأزرار لتسريع التحميل
                initComplete: function() {
                    console.log('DataTable initialized successfully');
                    var info = this.api().page.info();
                    console.log('Records total:', info.recordsTotal);
                    console.log('Records displayed:', info.recordsDisplay);
                    console.log('Current status filter:', $('#statusFilter').val());

                    // إظهار رسالة إذا لم توجد بيانات عند التحميل الأولي
                    if (info.recordsTotal === 0) {
                        console.log('No contracts found on initial load');
                    }

                    // إزالة overlay التحميل وتمكين التفاعل
                    $('#loadingOverlay').hide();
                    $('body').removeClass('loading');
                },
                preDrawCallback: function(settings) {
                    // منع التفاعل أثناء التحميل
                    $('#dataTableObject').addClass('loading');
                },
                drawCallback: function(settings) {
                    // تمكين التفاعل بعد التحميل
                    $('#dataTableObject').removeClass('loading');

                    // إعادة تهيئة tooltips إذا كانت موجودة
                    if (typeof $().tooltip === 'function') {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                }
            });

            // تحديث الجدول عند تغيير الفلتر
            $('#statusFilter').on('change', function() {
                toggleWarrantyFilter();

                // لا تحدث الجدول إذا كان التغيير من الـ URL
                if (urlStatus && $(this).val() === urlStatus) {
                    console.log('Skipping table reload - filter change from URL');
                    return;
                }

                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                    // إظهار رسالة إذا لم توجد نتائج
                    var info = dataTable.page.info();
                    if (info.recordsTotal === 0) {
                        console.log('لا توجد نتائج بعد تطبيق الفلتر');
                    }
                });
            });

            // تحديث الجدول عند تغيير فلتر حالة الضمان
            $('#warrantyStatusFilter').on('change', function() {
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الجدول عند تغيير فلتر التاريخ
            $('#dateFromFilter, #dateToFilter').on('change', function() {
                // لا تحدث الجدول تلقائياً - انتظر زر البحث
            });

            // تحديث الجدول عند تغيير فلاتر البحث
            $('#clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #cityFilter, #marketerFilter')
                .on('change', function() {
                    // لا تحدث الجدول تلقائياً - انتظر زر البحث
                });

            // زر البحث
            $('#btnSubmit').on('click', function() {
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // مسح الفلاتر
            $('#clearFilters').on('click', function() {
                $('#statusFilter, #warrantyStatusFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #officeFilter, #cityFilter, #marketerFilter')
                    .val('');
                $('#dateFromFilter, #dateToFilter').val('');
                localStorage.removeItem('contractFilters'); // مسح الفلاتر المحفوظة

                toggleWarrantyFilter();

                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الإحصائيات عند تحميل البيانات
            dataTable.on('xhr', function() {
                // تحديث الإحصائيات فقط إذا كانت معطلة
                // updateStatistics();

                // إظهار إشعار إذا لم توجد نتائج فقط عند البحث (وليس عند فلتر الحالة الأساسي)
                var info = dataTable.page.info();
                var hasSearchFilters = $('#dateFromFilter').val() || $('#dateToFilter').val() ||
                    $('#clientPhoneFilter').val() || $('#clientNameFilter').val() ||
                    $('#maidPassportFilter').val() || $('#maidNameFilter').val() ||
                    $('#contractNumberFilter').val() || $('#nationalityFilter').val() ||
                    $('#religionFilter').val() || $('#officeFilter').val() || $('#cityFilter').val() ||
                    $('#marketerFilter').val();

                console.log('DataTable XHR completed:', {
                    recordsTotal: info.recordsTotal,
                    recordsDisplay: info.recordsDisplay,
                    hasSearchFilters: hasSearchFilters,
                    statusFilter: $('#statusFilter').val()
                });

                if (info.recordsTotal === 0 && hasSearchFilters) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لا توجد نتائج',
                        text: 'لم يتم العثور على عقود تطابق معايير البحث المحددة',
                        confirmButtonText: 'حسناً'
                    });
                } else if (info.recordsTotal === 0 && !hasSearchFilters && $('#statusFilter').val()) {
                    // عرض رسالة مناسبة عندما لا توجد عقود في المرحلة المحددة
                    var statusText = $('#statusFilter option:selected').text();
                    var statusValue = $('#statusFilter').val();
                    console.log('No contracts found for status:', statusText);

                    // رسالة خاصة لمرحلة انتظار اختيار العاملة
                    var message = '';
                    if (statusValue === 'pending_maid_selection') {
                        message = '<tr><td colspan="10" class="text-center">' +
                            '<div class="alert alert-warning mb-0">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>' +
                            '<strong>لا توجد عقود في انتظار اختيار العاملة</strong><br>' +
                            '<small class="text-muted">جميع العقود إما تم تخصيص عاملة لها أو في مراحل أخرى</small>' +
                            '</div></td></tr>';
                    } else {
                        message =
                            '<tr><td colspan="10" class="text-center text-muted">لا توجد عقود في مرحلة "' +
                            statusText + '"</td></tr>';
                    }

                    // إظهار رسالة في الجدول بدلاً من الدايلوج
                    if ($('#dataTableObject tbody tr').length === 0) {
                        $('#dataTableObject tbody').html(message);
                    }
                }
            });

            // دالة تحديث الإحصائيات
            function updateStatistics() {
                // تم تعطيل الإحصائيات مؤقتاً
                return;

                $.ajax({
                    url: '{{ route('RecruitmentContract.statistics') }}',
                    method: 'GET',
                    data: {
                        status: $('#statusFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#totalContracts').text(response.data.total || 0);
                            $('#activeContracts').text(response.data.active || 0);
                            $('#pendingContracts').text(response.data.pending || 0);
                            $('#completedContracts').text(response.data.completed || 0);
                            $('#returnedContracts').text(response.data.returned || 0);
                            $('#closedContracts').text(response.data.closed || 0);
                        }
                    }
                });
            }

            // تحديث الإحصائيات عند تحميل الصفحة
            updateStatistics();

            // حفظ الفلاتر في localStorage
            function saveFilters() {
                var filters = {
                    status: $('#statusFilter').val(),
                    warrantyStatus: $('#warrantyStatusFilter').val(),
                    clientPhone: $('#clientPhoneFilter').val(),
                    clientName: $('#clientNameFilter').val(),
                    maidPassport: $('#maidPassportFilter').val(),
                    maidName: $('#maidNameFilter').val(),
                    contractNumber: $('#contractNumberFilter').val(),
                    nationality: $('#nationalityFilter').val(),
                    religion: $('#religionFilter').val(),
                    city: $('#cityFilter').val(),
                    dateFrom: $('#dateFromFilter').val(),
                    dateTo: $('#dateToFilter').val(),
                    marketer: $('#marketerFilter').val() // Save marketer filter
                };
                localStorage.setItem('contractFilters', JSON.stringify(filters));
            }

            // استعادة الفلاتر من localStorage (فقط إذا لم يكن هناك status في الـ URL)
            function loadFilters() {
                // لا تحمل الفلاتر من localStorage إذا كان هناك status في الـ URL
                if (urlStatus) {
                    console.log('Skipping localStorage load because status is in URL:', urlStatus);
                    return;
                }

                var filters = localStorage.getItem('contractFilters');
                if (filters) {
                    filters = JSON.parse(filters);
                    $('#statusFilter').val(filters.status || '');
                    $('#warrantyStatusFilter').val(filters.warrantyStatus || '');
                    $('#clientPhoneFilter').val(filters.clientPhone || '');
                    $('#clientNameFilter').val(filters.clientName || '');
                    $('#maidPassportFilter').val(filters.maidPassport || '');
                    $('#maidNameFilter').val(filters.maidName || '');
                    $('#contractNumberFilter').val(filters.contractNumber || '');
                    $('#nationalityFilter').val(filters.nationality || '');
                    $('#religionFilter').val(filters.religion || '');
                    $('#cityFilter').val(filters.city || '');
                    $('#dateFromFilter').val(filters.dateFrom || '');
                    $('#dateToFilter').val(filters.dateTo || '');
                    $('#marketerFilter').val(filters.marketer || ''); // Load marketer filter
                }
            }

            // تحميل الفلاتر عند تحميل الصفحة
            loadFilters();
            toggleWarrantyFilter();

            // حفظ الفلاتر عند تغييرها (فقط إذا لم يكن هناك status في الـ URL)
            $('.form-control, .form-select').on('change', function() {
                // لا تحفظ الفلاتر إذا كان هناك status في الـ URL لتجنب التضارب
                if (!urlStatus) {
                    saveFilters();
                }
            });

            // التحكم في طي الفلاتر
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });

            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });



            // معالج زر إكمال الرحلة من عمود المرحلة التالية
            $(document).on('click', '.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                completeTrip(tripId);
            });

            // معالج زر الإرجاع من عمود المرحلة التالية
            $(document).on('click', '.backContract', function() {
                var rowId = $(this).data('row-id');
                backContract(rowId);
            });

            // معالج زر إضافة رحلة
            $(document).on('click', '.add-travel-btn', function() {
                var contractId = $(this).data('contract-id');
                addTravel(contractId);
            });

            // معالج زر تحديث الحالة
            $(document).on('click', '.update-status-btn', function() {
                var rowId = $(this).data('row-id');
                var status = $(this).data('status');
                var nextStatus = $(this).data('next-status');
                var date = $(this).data('date');

                // تعبئة البيانات في Modal
                $('#updateStatusModal #rowId').val(rowId);
                $('#updateStatusModal #status').val(status);
                $('#updateStatusModal #nextStatus').val(nextStatus);

                // تعيين تاريخ اليوم دائماً
                var today = new Date().toISOString().split('T')[0];
                $('#updateStatusModal #statusDate').val(today);

                // عرض Modal
                $('#updateStatusModal').modal('show');
            });

            // معالج زر حفظ الحالة
            $('#saveStatusBtn').click(function() {
                var rowId = $('#updateStatusModal #rowId').val();
                var nextStatus = $('#updateStatusModal #nextStatus').val();
                var date = $('#updateStatusModal #statusDate').val();
                var sendSms = $('#updateStatusModal #sendSmsNotification').is(':checked');

                $.ajax({
                    url: '{{ route('RecruitmentContract.updateStatus') }}',
                    method: 'POST',
                    data: {
                        row_id: rowId,
                        next_status: nextStatus,
                        date: date,
                        send_sms: sendSms,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم تحديث الحالة بنجاح',
                                text: response.message
                            }).then(() => {
                                $('#updateStatusModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة'
                        });
                    }
                });
            });

            // دالة إكمال الرحلة
            function completeTrip(tripId) {
                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، أكمل الرحلة',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('RecruitmentContract.completeTrip') }}',
                            method: 'POST',
                            data: {
                                trip_id: tripId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إكمال الرحلة بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إكمال الرحلة'
                                });
                            }
                        });
                    }
                });
            }

            // دالة إرجاع العقد
            function backContract(rowId) {
                Swal.fire({
                    title: 'تأكيد إرجاع العقد',
                    text: 'هل أنت متأكد من إرجاع هذا العقد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، إرجاع العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('RecruitmentContract.back', ':id') }}'.replace(':id',
                                rowId),
                            method: 'GET',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إرجاع العقد بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إرجاع العقد'
                                });
                            }
                        });
                    }
                });
            }

            // دالة إضافة رحلة
            function addTravel(contractId) {
                Swal.fire({
                    title: 'إضافة رحلة جديدة',
                    text: 'هل تريد إضافة رحلة جديدة لهذا العقد؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إضافة رحلة',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('travel.add') }}',
                            method: 'POST',
                            data: {
                                contract_id: contractId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم إضافة الرحلة بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إضافة الرحلة'
                                });
                            }
                        });
                    }
                });
            }


            // معالج فتح Modal تحديث الحالة
            $('#updateStatusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var rowId = button.data('row-id');
                var status = button.data('status');
                var nextStatus = button.data('next-status');
                var date = button.data('date');
                var modal = $(this);

                modal.find('#rowId').val(rowId);
                modal.find('#status').val(status);
                modal.find('#nextStatus').val(nextStatus);
                modal.find('#statusDate').val(date);

                // عرض الحالة الحالية
                var currentStatusText = getContractStatusText(status);
                modal.find('#currentStatus').val(currentStatusText);

                // عرض المرحلة التالية
                var nextStatusText = getContractStatusText(nextStatus);
                modal.find('#nextStatusDisplay').val(nextStatusText);
            });

            // دالة للحصول على نص الحالة
            function getContractStatusText(status) {
                var statusMap = {
                    'new': 'عقد جديد',
                    'msaned': 'عقود مساند',
                    'tafwed': 'تفويض',
                    'taffez': 'التفييز',
                    'tazker': 'تذكرة',
                    'completed': 'عقد مكتمل',
                    'under_warranty': 'تحت الضمان',
                    'back': 'مرتجع',
                    'arrival': 'مرحلة الوصول',
                    'sari': 'عقد ساري',
                    'closed': 'عقد مغلق',
                    'shelter': 'عقد إيواء',
                    'pending_maid_selection': 'بانتظار اختيار العاملة'
                };
                return statusMap[status] || status;
            }

            // معالج تحديث الحالة
            $('#saveStatusBtn').click(function() {
                var rowId = $('#rowId').val();
                var status = $('#status').val();
                var nextStatus = $('#nextStatus').val();
                var statusDate = $('#statusDate').val();

                if (!statusDate) {
                    alert('يرجى إدخال تاريخ الحالة');
                    return;
                }

                $.ajax({
                    url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId',
                        rowId),
                    method: 'GET',
                    data: {
                        status: status,
                        next_status: nextStatus,
                        date: statusDate,
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message || 'تم تحديث الحالة بنجاح',
                                confirmButtonText: 'حسناً'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء تحديث الحالة',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        $('#dataTableObject').addClass('loading');
                        dataTable.ajax.reload(function() {
                            $('#dataTableObject').removeClass('loading');
                        });
                        $('#updateStatusModal').modal('hide');
                    }
                });
            });

            // معالج إرجاع العقد
            $(document).on('click', '.backContract', function() {
                var contractId = $(this).data('row-id');
                console.log('Back contract clicked, contractId:', contractId);

                if (!contractId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف العقد',
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                Swal.fire({
                    title: 'تأكيد الإرجاع',
                    text: 'هل أنت متأكد من إرجاع هذا العقد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'نعم، إرجاع',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('RecruitmentContract.back', ':id') }}'.replace(
                                ':id', contractId),
                            method: 'GET',
                            success: function(response) {
                                console.log('Back contract response:', response);
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message ||
                                            'تم إرجاع العقد بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message ||
                                            'حدث خطأ أثناء إرجاع العقد',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إرجاع العقد',
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function() {
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

            // معالج إضافة رحلة
            $(document).on('click', '.add-travel-btn', function(e) {
                e.preventDefault();
                var contractId = $(this).data('contract-id');

                console.log('Add travel button clicked for contract ID:', contractId);

                if (!contractId) {
                    console.error('No contract ID found');
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'لم يتم العثور على معرف العقد',
                        confirmButtonText: 'حسناً'
                    });
                    return;
                }

                // التوجيه إلى صفحة إضافة رحلة
                var url = '{{ route('travel.control') }}?contract_id=' + contractId;
                console.log('Redirecting to:', url);
                window.location.href = url;
            });

            // معالج إكمال الرحلة
            $(document).on('click', '.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                var button = $(this);

                console.log('Complete trip button clicked for trip ID:', tripId);

                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟ سيتم تحديث حالة الرحلة إلى مكتملة.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إكمال',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // إظهار حالة التحميل
                        button.prop('disabled', true).html(
                            '<i class="fa fa-spinner fa-spin me-1"></i>جاري الإكمال...');

                        $.ajax({
                            url: '{{ route('travel.complete', ':tripId') }}'.replace(
                                ':tripId', tripId),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                console.log('Complete trip response:', response);

                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message ||
                                            'تم إكمال الرحلة بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message ||
                                            'حدث خطأ أثناء إكمال الرحلة',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });

                                let errorMessage = 'حدث خطأ أثناء إكمال الرحلة';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: errorMessage,
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function() {
                                // إعادة تفعيل الزر
                                button.prop('disabled', false).html(
                                    '<i class="fas fa-check me-1"></i>إكمال الرحلة');

                                // إعادة تحميل الجدول
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

            // معالج زر إضافة عاملة
            $(document).on('click', '.btn-action[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });



            // معالج زر إضافة عاملة من عمود المرحلة التالية
            $(document).on('click', '.btn-action[href*="selectMaid"]', function(e) {
                // السماح بالانتقال الطبيعي للرابط
                return true;
            });


            // معالج زر إلغاء العقد
            $(document).on('click', '.cancel-contract-btn', function(e) {
                e.preventDefault();
                var button = $(this);
                var contractId = button.data('contract-id');
                var contractNumber = button.data('contract-number');
                var clientName = button.data('client-name');
                var maidName = button.data('maid-name');

                openCancelContractModal(contractId, contractNumber, clientName, maidName);
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Handle complete trip button click
            $(document).on('click', '.complete-trip-btn', function() {
                var tripId = $(this).data('trip-id');
                var button = $(this);

                console.log('Complete trip button clicked for trip ID:', tripId);

                Swal.fire({
                    title: '{{ __('admin.confirm') }}',
                    text: '{{ __('admin.mark_trip_completed_confirmation') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('admin.confirm') }}',
                    cancelButtonText: '{{ __('admin.cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Sending complete trip request for trip ID:', tripId);

                        $.ajax({
                            url: '/admin/travel/complete/' + tripId,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                console.log('Complete trip response:', response);

                                if (response.success) {
                                    // Update the status badge
                                    button.closest('td').find('.badge').removeClass(
                                        'bg-warning').addClass('bg-success').text(
                                        '{{ __('admin.trip_completed') }}');
                                    // Remove the complete button
                                    button.remove();
                                    // Show success message
                                    Swal.fire('{{ __('admin.success') }}', response
                                        .message, 'success');
                                    // Reload the table to reflect the new contract status
                                    $('#dataTableObject').DataTable().ajax.reload(null,
                                        false);

                                    // If we're on the tazker status page, redirect to under warranty page
                                    if (window.location.href.includes(
                                        'status=tazker')) {
                                        setTimeout(function() {
                                            window.location.href =
                                                '{{ route('RecruitmentContract.index', ['status' => 'under_warranty']) }}';
                                        }, 1500);
                                    }
                                } else {
                                    Swal.fire('{{ __('admin.error') }}', response
                                        .message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Complete trip error:', {
                                    status: status,
                                    error: error,
                                    response: xhr.responseText
                                });
                                Swal.fire('{{ __('admin.error') }}',
                                    '{{ __('admin.something_went_wrong') }}',
                                    'error');
                            }
                        });
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // تفعيل tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // إعادة تفعيل tooltips بعد تحميل الجدول
            dataTable.on('draw', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll(
                    '[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });


            });
        });
    </script>


    <script>
        // دالة فتح موديل إلغاء العقد
        function openCancelContractModal(contractId, contractNumber, clientName, maidName) {
            $('#contractId').val(contractId);
            $('#contractNumber').text(contractNumber);
            $('#clientName').text(clientName);
            $('#maidName').text(maidName);
            $('#cancel_reason').val('');
            $('input[name="maid_status_after_cancel"]').prop('checked', false);
            $('#cancelContractModal').modal('show');
        }

        // دالة إرسال إلغاء العقد
        function submitCancelContract() {
            var formData = $('#cancelContractForm').serialize();

            $.ajax({
                url: '{{ route('RecruitmentContract.cancel') }}',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#cancelContractModal').modal('hide');
                        Swal.fire('نجح', response.message, 'success');
                        $('#dataTableObject').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire('خطأ', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Cancel contract error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    Swal.fire('خطأ', 'حدث خطأ أثناء إلغاء العقد', 'error');
                }
            });
        }
    </script>

    <!-- موديل حذف العقد -->
    <div class="modal fade" id="deleteContractModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>
                        حذف العقد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>تنبيه:</strong> سيتم حذف العقد نهائياً ولا يمكن التراجع عن هذا الإجراء.
                    </div>

                    <div class="mb-3">
                        <strong>تفاصيل العقد:</strong>
                        <ul class="list-unstyled mt-2">
                            <li><strong>رقم العقد:</strong> <span id="deleteContractNumber"></span></li>
                            <li><strong>اسم العميل:</strong> <span id="deleteClientName"></span></li>
                            <li><strong>اسم العاملة:</strong> <span id="deleteMaidName"></span></li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>حالة العاملة بعد الحذف:</strong></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="maid_status" id="maidAvailable"
                                value="available" checked>
                            <label class="form-check-label" for="maidAvailable">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                متاحة (يمكن إضافتها لعقود أخرى)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="maid_status" id="maidBackOut"
                                value="back_out">
                            <label class="form-check-label" for="maidBackOut">
                                <i class="fas fa-times-circle text-danger me-1"></i>
                                Back Out (غير متاحة)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>
                        تأكيد الحذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var contractToDelete = null;

            // فتح موديل الحذف
            $(document).on('click', '.delete-contract-btn', function() {
                var contractId = $(this).data('contract-id');
                var contractNumber = $(this).data('contract-number');
                var clientName = $(this).data('client-name');
                var maidName = $(this).data('maid-name');

                contractToDelete = contractId;
                $('#deleteContractNumber').text(contractNumber);
                $('#deleteClientName').text(clientName);
                $('#deleteMaidName').text(maidName);

                $('#deleteContractModal').modal('show');
            });

            // تأكيد الحذف
            $('#confirmDeleteBtn').click(function() {
                if (!contractToDelete) return;

                var maidStatus = $('input[name="maid_status"]:checked').val();
                var button = $(this);
                var originalText = button.html();

                button.prop('disabled', true).html(
                    '<i class="fa fa-spinner fa-spin me-1"></i>جاري الحذف...');

                $.ajax({
                    url: '{{ route('RecruitmentContract.destroy', ':id') }}'.replace(':id',
                        contractToDelete),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        maid_status: maidStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#deleteContractModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message,
                                confirmButtonText: 'حسناً'
                            }).then(() => {
                                $('#dataTableObject').DataTable().ajax.reload(null,
                                    false);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message,
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete contract error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء حذف العقد',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            });

            // إعادة تعيين المتغير عند إغلاق الموديل
            $('#deleteContractModal').on('hidden.bs.modal', function() {
                contractToDelete = null;
            });
        });
    </script>
@endsection
