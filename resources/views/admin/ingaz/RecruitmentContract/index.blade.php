@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .maids-grid {
            max-height: 400px;
            overflow-y: auto;
        }

        .maid-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
        }

        .maid-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
            transform: translateY(-2px);
        }

        .maid-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
            box-shadow: 0 4px 8px rgba(40,167,69,0.2);
        }

        .maid-card .maid-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }

        .maid-card .maid-info h6 {
            margin-bottom: 5px;
            color: #333;
        }

        .maid-card .maid-info p {
            margin-bottom: 3px;
            font-size: 0.9em;
            color: #666;
        }

        .maid-card .maid-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .maid-card .maid-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .maid-card .maid-status.available {
            background: #d4edda;
            color: #155724;
        }

        .maid-card .maid-status.reserved {
            background: #fff3cd;
            color: #856404;
        }

        .filters-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 1px 3px;
            border-radius: 3px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }

        .btn-sm i {
            font-size: 0.875rem;
        }

        .table td {
            vertical-align: middle;
        }

        .contract-info {
            font-size: 0.8rem;
            line-height: 1.2;
        }

        .contract-info div {
            margin-bottom: 2px;
        }

        .maid-info {
            text-align: center;
        }

        .maid-info small {
            display: block;
            margin-top: 5px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-info small {
            display: block;
            margin-top: 3px;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .contract-info {
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .contract-info div {
            margin-bottom: 2px;
            color: #666;
        }

        .contract-info strong {
            color: #333;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .filters-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .filter-card .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .searching {
            position: relative;
        }

        .searching::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 10px;
            width: 16px;
            height: 16px;
            margin-top: -8px;
            border: 2px solid #667eea;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .statistics-card {
            transition: transform 0.2s ease-in-out;
        }

        .statistics-card:hover {
            transform: translateY(-2px);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-action {
            margin: 0 2px;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: scale(1.05);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .maid-image-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .maid-image-container:hover {
            transform: scale(1.05);
        }

        .maid-image-container img {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: border-color 0.2s ease;
        }

        .maid-image-container:hover img {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.2);
        }

        .shadow {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }

        .table-hover {
            background-color: #f8f9fa !important;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.All Recruitment contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))
    <!-- إحصائيات سريعة -->
    {{-- <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="totalContracts">0</h4>
                    <small>إجمالي العقود</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="activeContracts">0</h4>
                    <small>العقود النشطة</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="pendingContracts">0</h4>
                    <small>بانتظار العاملة</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="completedContracts">0</h4>
                    <small>العقود المكتملة</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="returnedContracts">0</h4>
                    <small>العقود المرتجعة</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white statistics-card">
                <div class="card-body text-center">
                    <h4 class="mb-0" id="closedContracts">0</h4>
                    <small>العقود المغلقة</small>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- فلتر الحالات -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card filter-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>فلتر العقود</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">حالة العقد:</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="new" {{ $status == 'new' ? 'selected' : '' }}>عقد جديد</option>
                                <option value="msaned" {{ $status == 'msaned' ? 'selected' : '' }}>عقود مساند</option>
                                <option value="tafwed" {{ $status == 'tafwed' ? 'selected' : '' }}>تفويض</option>
                                <option value="taffez" {{ $status == 'taffez' ? 'selected' : '' }}>التفييز</option>
                                <option value="tazker" {{ $status == 'tazker' ? 'selected' : '' }}>تذكرة</option>
                                <option value="arrival" {{ $status == 'arrival' ? 'selected' : '' }}>مرحلة الوصول</option>
                                <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>عقد مغلق</option>
                                <option value="pending_maid_selection" {{ $status == 'pending_maid_selection' ? 'selected' : '' }}>بانتظار اختيار العاملة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="clientPhoneFilter" class="form-label">رقم جوال العميل:</label>
                            <input type="text" id="clientPhoneFilter" class="form-control" placeholder="ابحث برقم الجوال">
                        </div>
                        <div class="col-md-2">
                            <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                            <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                        </div>
                        <div class="col-md-2">
                            <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                            <input type="text" id="maidPassportFilter" class="form-control" placeholder="ابحث برقم الجواز">
                        </div>
                        <div class="col-md-2">
                            <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                            <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                        </div>
                        <div class="col-md-2">
                            <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                            <input type="text" id="contractNumberFilter" class="form-control" placeholder="ابحث برقم العقد">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-2">
                            <label for="dateFromFilter" class="form-label">من تاريخ:</label>
                            <input type="date" id="dateFromFilter" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label for="dateToFilter" class="form-label">إلى تاريخ:</label>
                            <input type="date" id="dateToFilter" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label for="nationalityFilter" class="form-label">جنسية العاملة:</label>
                            <select id="nationalityFilter" class="form-select">
                                <option value="">جميع الجنسيات</option>
                                <option value="indonesia">إندونيسيا</option>
                                <option value="philippines">الفلبين</option>
                                <option value="sri_lanka">سريلانكا</option>
                                <option value="ethiopia">إثيوبيا</option>
                                <option value="kenya">كينيا</option>
                                <option value="uganda">أوغندا</option>
                                <option value="bangladesh">بنغلاديش</option>
                                <option value="nepal">نيبال</option>
                                <option value="india">الهند</option>
                                <option value="pakistan">باكستان</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="religionFilter" class="form-label">ديانة العاملة:</label>
                            <select id="religionFilter" class="form-select">
                                <option value="">جميع الديانات</option>
                                <option value="muslim">مسلمة</option>
                                <option value="christian">مسيحية</option>
                                <option value="buddhist">بوذية</option>
                                <option value="hindu">هندوسية</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="cityFilter" class="form-label">مدينة العميل:</label>
                            <input type="text" id="cityFilter" class="form-control" placeholder="ابحث بالمدينة">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" id="clearFilters" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>مسح الفلاتر
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT))
        <div class="col-sm-2">
            <a href="{{ route('RecruitmentContract.export.pdf') }}" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
            </a>
        </div>
        <div class="col-sm-2">
            <a href="{{ route('RecruitmentContract.export.excel') }}" class="btn btn-success">
                <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
            </a>
        </div>
        @endif
    </div>

    <div class="table-responsive">
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>{{helperTrans("ingaz.client")}}</th>
                <th>{{helperTrans("ingaz.phone")}}</th>
                <th>{{helperTrans("ingaz.maid")}}</th>
                <th>معلومات العقد</th>
                <th>{{helperTrans("ingaz.status")}}</th>
                <th>المرحلة التالية</th>
                <th>{{helperTrans("ingaz.make back")}}</th>
                <th>{{helperTrans("ingaz.the contract")}}</th>
                <th>{{helperTrans("ingaz.actions")}}</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
         aria-hidden="true">
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
                        <input type="date" id="statusDate" required name="statusDate" class="form-control" value="{{date('Y-m-d')}}">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        سيتم تحديث حالة العقد إلى المرحلة التالية في التاريخ المحدد
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
    <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog" aria-labelledby="confirmBackModalLabel"
         aria-hidden="true">
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



@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'id', name: 'id', orderable: false, searchable: false},
                {"data": "user_id", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "maid_id", orderable: false, searchable: false},
                {"data": "contract_info", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: true},
                {"data": "updateStatus", orderable: true, searchable: true},
                {"data": "back", orderable: true, searchable: true},
                {"data": "show_details", orderable: true, searchable: true},
                {"data": "actions", orderable: true, searchable: true},
            ];

            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('RecruitmentContract.index') }}",
                    data: function (d) {
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
                        d.city = $('#cityFilter').val();
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Response:', xhr.responseText);
                    }
                },
                columns: columns,
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
                    emptyTable: "لا توجد عقود متاحة",
                    zeroRecords: "لم يتم العثور على عقود تطابق معايير البحث",
                    processing: "جاري التحميل...",
                    search: "البحث:",
                    lengthMenu: "عرض _MENU_ سجل",
                    info: "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    infoEmpty: "عرض 0 إلى 0 من 0 سجل",
                    infoFiltered: "(مفلتر من _MAX_ سجل إجمالي)"
                },
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                initComplete: function () {
                    console.log('DataTable initialized successfully');
                    var info = this.api().page.info();
                    console.log('Records total:', info.recordsTotal);
                    console.log('Records displayed:', info.recordsDisplay);

                    // إظهار رسالة إذا لم توجد بيانات عند التحميل الأولي
                    if (info.recordsTotal === 0) {
                        console.log('No contracts found on initial load');
                    }
                }
            });

            // تحديث الجدول عند تغيير الفلتر
            $('#statusFilter').on('change', function() {
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

            // تحديث الجدول عند تغيير فلتر التاريخ
            $('#dateFromFilter, #dateToFilter').on('change', function() {
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

            // تحديث الجدول عند تغيير فلاتر البحث
            $('#clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #cityFilter').on('keyup change', function() {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(function() {
                    $('#dataTableObject').addClass('loading');
                    dataTable.ajax.reload(function() {
                        $('#dataTableObject').removeClass('loading');
                        // إظهار رسالة إذا لم توجد نتائج
                        var info = dataTable.page.info();
                        if (info.recordsTotal === 0) {
                            console.log('No results found after search filter');
                        }
                    });
                }, 500); // تأخير 500 مللي ثانية
            });

            // إضافة مؤشر البحث النشط
            $('.form-control, .form-select').on('focus', function() {
                $(this).parent().addClass('searching');
            }).on('blur', function() {
                $(this).parent().removeClass('searching');
            });

            // مسح الفلاتر
            $('#clearFilters').on('click', function() {
                $('#statusFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter, #nationalityFilter, #religionFilter, #cityFilter').val('');
                $('#dateFromFilter, #dateToFilter').val('');
                localStorage.removeItem('contractFilters'); // مسح الفلاتر المحفوظة
                $('#dataTableObject').addClass('loading');
                dataTable.ajax.reload(function() {
                    $('#dataTableObject').removeClass('loading');
                });
            });

                                    // تحديث الإحصائيات عند تحميل البيانات
            dataTable.on('xhr', function() {
                // تحديث الإحصائيات فقط إذا كانت معطلة
                // updateStatistics();

                // إظهار إشعار إذا لم توجد نتائج فقط عند البحث
                var info = dataTable.page.info();
                var hasFilters = $('#statusFilter').val() || $('#dateFromFilter').val() || $('#dateToFilter').val() ||
                                $('#clientPhoneFilter').val() || $('#clientNameFilter').val() || $('#maidPassportFilter').val() ||
                                $('#maidNameFilter').val() || $('#contractNumberFilter').val() || $('#nationalityFilter').val() ||
                                $('#religionFilter').val() || $('#cityFilter').val();

                console.log('DataTable XHR completed:', {
                    recordsTotal: info.recordsTotal,
                    recordsDisplay: info.recordsDisplay,
                    hasFilters: hasFilters
                });

                if (info.recordsTotal === 0 && hasFilters) {
                    Swal.fire({
                        icon: 'info',
                        title: 'لا توجد نتائج',
                        text: 'لم يتم العثور على عقود تطابق معايير البحث المحددة',
                        confirmButtonText: 'حسناً'
                    });
                }
            });

            // دالة تحديث الإحصائيات
            function updateStatistics() {
                // تم تعطيل الإحصائيات مؤقتاً
                return;

                $.ajax({
                    url: '{{ route("RecruitmentContract.statistics") }}',
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
                    clientPhone: $('#clientPhoneFilter').val(),
                    clientName: $('#clientNameFilter').val(),
                    maidPassport: $('#maidPassportFilter').val(),
                    maidName: $('#maidNameFilter').val(),
                    contractNumber: $('#contractNumberFilter').val(),
                    nationality: $('#nationalityFilter').val(),
                    religion: $('#religionFilter').val(),
                    city: $('#cityFilter').val(),
                    dateFrom: $('#dateFromFilter').val(),
                    dateTo: $('#dateToFilter').val()
                };
                localStorage.setItem('contractFilters', JSON.stringify(filters));
            }

            // استعادة الفلاتر من localStorage
            function loadFilters() {
                var filters = localStorage.getItem('contractFilters');
                if (filters) {
                    filters = JSON.parse(filters);
                    $('#statusFilter').val(filters.status || '');
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
                }
            }

            // تحميل الفلاتر عند تحميل الصفحة
            loadFilters();

            // حفظ الفلاتر عند تغييرها
            $('.form-control, .form-select').on('change', function() {
                saveFilters();
            });

            // إضافة تأثيرات بصرية للأزرار
            $('.btn-action').hover(
                function() {
                    $(this).addClass('shadow');
                },
                function() {
                    $(this).removeClass('shadow');
                }
            );

            // تحسين تجربة المستخدم
            $('.table').on('mouseenter', 'tr', function() {
                $(this).addClass('table-hover');
            }).on('mouseleave', 'tr', function() {
                $(this).removeClass('table-hover');
            });

            // معالج فتح Modal تحديث الحالة
            $('#updateStatusModal').on('show.bs.modal', function (event) {
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
            $('#saveStatusBtn').click(function () {
                var rowId = $('#rowId').val();
                var status = $('#status').val();
                var nextStatus = $('#nextStatus').val();
                var statusDate = $('#statusDate').val();

                if (!statusDate) {
                    alert('يرجى إدخال تاريخ الحالة');
                    return;
                }

                $.ajax({
                    url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId', rowId),
                    method: 'GET',
                    data: {
                        status: status,
                        next_status: nextStatus,
                        date: statusDate,
                    },
                    success: function (response) {
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
                    error: function (xhr, status, error) {
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء تحديث الحالة',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function () {
                        $('#dataTableObject').addClass('loading');
                        dataTable.ajax.reload(function() {
                            $('#dataTableObject').removeClass('loading');
                        });
                        $('#updateStatusModal').modal('hide');
                    }
                });
            });

            // معالج إرجاع العقد
            $(document).on('click', '.backContract', function () {
                var contractId = $(this).data('row-id');

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
                            url: '{{ route('RecruitmentContract.back', ':id') }}'.replace(':id', contractId),
                            method: 'GET',
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message || 'تم إرجاع العقد بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message || 'حدث خطأ أثناء إرجاع العقد',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('AJAX Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إرجاع العقد',
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function () {
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
            });

            // معالج إكمال الرحلة
            $(document).on('click', '.complete-trip-btn', function () {
                var tripId = $(this).data('trip-id');

                Swal.fire({
                    title: 'تأكيد إكمال الرحلة',
                    text: 'هل أنت متأكد من إكمال هذه الرحلة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إكمال',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('travel.complete', ':tripId') }}'.replace(':tripId', tripId),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message || 'تم إكمال الرحلة بنجاح',
                                        confirmButtonText: 'حسناً'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message || 'حدث خطأ أثناء إكمال الرحلة',
                                        confirmButtonText: 'حسناً'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('AJAX Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: 'حدث خطأ أثناء إكمال الرحلة',
                                    confirmButtonText: 'حسناً'
                                });
                            },
                            complete: function () {
                                dataTable.ajax.reload();
                            }
                        });
                    }
                });
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
                title: '{{ __("admin.confirm") }}',
                text: '{{ __("admin.mark_trip_completed_confirmation") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("admin.confirm") }}',
                cancelButtonText: '{{ __("admin.cancel") }}'
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
                                button.closest('td').find('.badge').removeClass('bg-warning').addClass('bg-success').text('{{ __("admin.trip_completed") }}');
                                // Remove the complete button
                                button.remove();
                                // Show success message
                                Swal.fire('{{ __("admin.success") }}', response.message, 'success');
                                // Reload the table to reflect the new contract status
                                $('#dataTableObject').DataTable().ajax.reload(null, false);

                                // If we're on the tazker status page, redirect to under warranty page
                                if (window.location.href.includes('status=tazker')) {
                                    setTimeout(function() {
                                        window.location.href = '{{ route("RecruitmentContract.index", ["status" => "under_warranty"]) }}';
                                    }, 1500);
                                }
                            } else {
                                Swal.fire('{{ __("admin.error") }}', response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Complete trip error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            Swal.fire('{{ __("admin.error") }}', '{{ __("admin.something_went_wrong") }}', 'error');
                        }
                    });
                }
            });
        });
    });
    </script>
@endsection
