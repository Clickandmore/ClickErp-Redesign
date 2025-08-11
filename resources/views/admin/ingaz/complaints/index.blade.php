@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
    <style>
    /* إحصائيات محسنة */
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid;
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .stats-card.total { border-left-color: #667eea; }
    .stats-card.pending { border-left-color: #ffc107; }
    .stats-card.progress { border-left-color: #17a2b8; }
    .stats-card.resolved { border-left-color: #28a745; }

    .stats-number {
        font-size: 32px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stats-label {
        font-size: 14px;
        color: #6c757d;
        font-weight: 500;
    }

    .stats-icon {
        font-size: 40px;
        opacity: 0.2;
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
    }

    /* فلاتر محسنة */
    .filter-section {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }

    .filter-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* عرض البطاقات */
    .ticket-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border-left: 4px solid;
        transition: all 0.3s ease;
    }

    .ticket-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .ticket-card.priority-urgent { border-left-color: #dc3545; }
    .ticket-card.priority-high { border-left-color: #fd7e14; }
    .ticket-card.priority-medium { border-left-color: #ffc107; }
    .ticket-card.priority-low { border-left-color: #17a2b8; }

    .ticket-number {
        background: #6c757d;
        color: white;
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: bold;
        display: inline-block;
    }

    /* شارات الحالة */
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
            font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-in_progress {
        background: #cce5ff;
        color: #004085;
    }

    .status-resolved {
        background: #d4edda;
        color: #155724;
    }

    .status-closed {
        background: #f8d7da;
        color: #721c24;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    /* تبديل العرض */
    .view-toggle {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .view-toggle .btn {
        padding: 8px 15px;
        background: white;
        border: 1px solid #dee2e6;
        color: #6c757d;
    }

    .view-toggle .btn.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    /* أزرار الإجراءات */
    .action-buttons {
            display: flex;
        gap: 5px;
        justify-content: center;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
            align-items: center;
        justify-content: center;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-icon:hover {
        transform: scale(1.1);
    }

    /* معلومات التذكرة */
    .ticket-meta {
        display: flex;
        gap: 20px;
        margin-top: 10px;
        font-size: 13px;
        color: #6c757d;
    }

    .ticket-meta i {
        margin-left: 5px;
        color: #667eea;
        }
    </style>
@endsection

@section('page-title')
إدارة الشكاوى
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))

<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-primary me-2"></i>
            إدارة الشكاوى والتذاكر
        </h1>
        @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))
        <a href="{{ route('complaints.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            إضافة شكوى جديدة
        </a>
        @endif
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card total position-relative">
                <div class="stats-number" id="totalTickets">0</div>
                <div class="stats-label">إجمالي الشكاوى</div>
                <i class="fas fa-ticket-alt stats-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card pending position-relative">
                <div class="stats-number" id="pendingTickets">0</div>
                <div class="stats-label">قيد الانتظار</div>
                <i class="fas fa-clock stats-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card progress position-relative">
                <div class="stats-number" id="inProgressTickets">0</div>
                <div class="stats-label">قيد المعالجة</div>
                <i class="fas fa-spinner stats-icon"></i>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card resolved position-relative">
                <div class="stats-number" id="resolvedTickets">0</div>
                <div class="stats-label">تم الحل</div>
                <i class="fas fa-check-circle stats-icon"></i>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
        <h5 class="mb-4">
            <i class="fas fa-filter me-2"></i>
            فلاتر البحث المتقدمة
        </h5>

        <div class="row">
            <div class="col-md-2 mb-3">
                <label class="filter-label">رقم التذكرة</label>
                <input type="text" id="filterTicketNumber" class="form-control" placeholder="#000000">
            </div>

            <div class="col-md-2 mb-3">
                <label class="filter-label">الحالة</label>
                <select id="filterStatus" class="form-select">
                    <option value="">جميع الحالات</option>
                    <option value="pending">قيد الانتظار</option>
                    <option value="in_progress">قيد المعالجة</option>
                    <option value="resolved">تم الحل</option>
                    <option value="closed">مغلقة</option>
                    <option value="rejected">مرفوضة</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="filter-label">الأولوية</label>
                <select id="filterPriority" class="form-select">
                    <option value="">جميع الأولويات</option>
                    <option value="urgent">عاجلة</option>
                    <option value="high">عالية</option>
                    <option value="medium">متوسطة</option>
                    <option value="low">منخفضة</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="filter-label">نوع الشكوى</label>
                <select id="filterType" class="form-select select2">
                    <option value="">جميع الأنواع</option>
                    @if(isset($complaintTypes))
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="filter-label">الموظف المختص</label>
                <select id="filterAssigned" class="form-select select2">
                    <option value="">جميع الموظفين</option>
                    @if(isset($admins))
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                        @endforeach
@endif
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="filter-label">الفترة الزمنية</label>
                <select id="filterPeriod" class="form-select">
                    <option value="">كل الأوقات</option>
                    <option value="today">اليوم</option>
                    <option value="yesterday">أمس</option>
                    <option value="week">هذا الأسبوع</option>
                    <option value="month">هذا الشهر</option>
                    <option value="custom">تاريخ مخصص</option>
                </select>
            </div>
        </div>

        <!-- Custom Date Range -->
        <div class="row mt-3" id="customDateRange" style="display: none;">
            <div class="col-md-3">
                <label class="filter-label">من تاريخ</label>
                <input type="date" id="filterDateFrom" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="filter-label">إلى تاريخ</label>
                <input type="date" id="filterDateTo" class="form-control">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <button id="applyFilters" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>تطبيق الفلاتر
                </button>
                <button id="resetFilters" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>إعادة تعيين
                </button>
                <button id="exportData" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i>تصدير Excel
                </button>
            </div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="view-toggle">
        <button class="btn active" id="tableView">
            <i class="fas fa-table me-2"></i>عرض جدول
        </button>
        <button class="btn" id="cardView">
            <i class="fas fa-th-large me-2"></i>عرض بطاقات
        </button>
    </div>

    <!-- Data Display -->
    <div class="row">
        <div class="col-lg-12">
            <!-- Table View -->
            <div id="tableViewContent">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            قائمة الشكاوى
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="complaintsTable" class="table display dataTable table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th width="80">رقم التذكرة</th>
                                        <th>مقدم الشكوى</th>
                                        <th>الموضوع</th>
                                        <th>النوع</th>
                                        <th width="80">الأولوية</th>
                                        <th width="100">الحالة</th>
                                        <th>الموظف المختص</th>
                                        <th>التاريخ</th>
                                        <th width="150">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card View -->
            <div id="cardViewContent" style="display: none;">
                <div class="row" id="ticketsCards"></div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Response Modal -->
<div class="modal fade" id="quickResponseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>
                    رد سريع على الشكوى
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickResponseForm">
                    @csrf
                    <input type="hidden" name="complaint_id" id="quick_complaint_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">الرد على الشكوى</label>
                        <textarea name="response" class="form-control" rows="5" required
                                  placeholder="اكتب ردك هنا..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تغيير الحالة</label>
                            <select name="status" class="form-select">
                                <option value="">-- بدون تغيير --</option>
                                <option value="in_progress">قيد المعالجة</option>
                                <option value="resolved">تم الحل</option>
                                <option value="closed">إغلاق التذكرة</option>
                                <option value="rejected">رفض الشكوى</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">تحويل إلى موظف</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- بدون تغيير --</option>
                                @if(isset($admins))
                                    @foreach($admins as $admin)
                                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>إلغاء
                </button>
                <button type="button" class="btn btn-primary" id="saveQuickResponse">
                    <i class="fas fa-paper-plane me-2"></i>إرسال الرد
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'اختر...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        }
    });

    // Initialize DataTable
    let table = $('#complaintsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('complaints.index') }}",
            type: 'GET',
            data: function(d) {
                d.ticket_number = $('#filterTicketNumber').val();
                d.status = $('#filterStatus').val();
                d.priority = $('#filterPriority').val();
                d.type = $('#filterType').val();
                d.assigned_to = $('#filterAssigned').val();
                d.period = $('#filterPeriod').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            },
            dataSrc: function(json) {
                updateStatistics(json.statistics || {});
                return json.data;
            }
        },
        columns: [
            {
                data: 'id',
                    render: function(data) {
                    return '<span class="ticket-number">#' + String(data).padStart(6, '0') + '</span>';
                }
            },
            {
                data: 'complainant_name',
                render: function(data, type, row) {
                    let html = '<div>';
                    html += '<strong>' + (data || row.user?.name || 'غير محدد') + '</strong>';
                    if (row.complainant_phone) {
                        html += '<br><small class="text-muted"><i class="fas fa-phone fa-sm me-1"></i>' + row.complainant_phone + '</small>';
                        }
                        html += '</div>';
                        return html;
                    }
                },
            {
                data: 'subject',
                    render: function(data) {
                    return data || '<span class="text-muted">بدون موضوع</span>';
                }
            },
            {
                data: 'complaint_type',
                render: function(data, type, row) {
                    if (row.complaintType) {
                        return '<span class="badge bg-info">' + row.complaintType.name + '</span>';
                    }
                    return '<span class="text-muted">غير محدد</span>';
                }
            },
            {
                data: 'priority',
                render: function(data) {
                    const priorities = {
                        'urgent': '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>عاجلة</span>',
                        'high': '<span class="badge bg-warning"><i class="fas fa-arrow-up me-1"></i>عالية</span>',
                        'medium': '<span class="badge bg-info"><i class="fas fa-minus me-1"></i>متوسطة</span>',
                        'low': '<span class="badge bg-secondary"><i class="fas fa-arrow-down me-1"></i>منخفضة</span>'
                    };
                    return priorities[data] || '<span class="badge bg-light text-dark">غير محدد</span>';
                }
            },
            {
                data: 'status',
                    render: function(data) {
                    const statuses = {
                        'pending': '<span class="status-badge status-pending"><i class="fas fa-clock me-1"></i>قيد الانتظار</span>',
                        'in_progress': '<span class="status-badge status-in_progress"><i class="fas fa-spinner me-1"></i>قيد المعالجة</span>',
                        'resolved': '<span class="status-badge status-resolved"><i class="fas fa-check me-1"></i>تم الحل</span>',
                        'closed': '<span class="status-badge status-closed"><i class="fas fa-times me-1"></i>مغلقة</span>',
                        'rejected': '<span class="status-badge status-rejected"><i class="fas fa-ban me-1"></i>مرفوضة</span>'
                    };
                    return statuses[data] || data;
                }
            },
            {
                data: 'assignedTo',
                render: function(data, type, row) {
                    if (data && data.name) {
                        return '<span class="text-primary"><i class="fas fa-user-tie me-1"></i>' + data.name + '</span>';
                    }
                    return '<span class="text-muted">غير محدد</span>';
                }
            },
            {
                data: 'created_at',
                render: function(data) {
                    let date = new Date(data);
                    // تنسيق التاريخ الميلادي
                    let day = date.getDate().toString().padStart(2, '0');
                    let month = (date.getMonth() + 1).toString().padStart(2, '0');
                    let year = date.getFullYear();
                    let hours = date.getHours().toString().padStart(2, '0');
                    let minutes = date.getMinutes().toString().padStart(2, '0');

                    return '<small>' + day + '/' + month + '/' + year + '<br>' + hours + ':' + minutes + '</small>';
                }
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let actions = '<div class="action-buttons">';

                    // View button
                    actions += '<a href="/admin/complaints/' + row.id + '" class="btn btn-icon btn-sm btn-info" title="عرض التفاصيل">';
                    actions += '<i class="fas fa-eye"></i></a>';

                    // Edit button
                    @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                    if (row.status === 'pending' || row.status === 'in_progress') {
                        actions += '<a href="/admin/complaints/' + row.id + '/edit" class="btn btn-icon btn-sm btn-warning" title="تعديل">';
                        actions += '<i class="fas fa-edit"></i></a>';
                    }
                    @endif

                    // Quick response button
                    @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_RESPONSE))
                    if (row.status !== 'closed' && row.status !== 'resolved') {
                        actions += '<button class="btn btn-icon btn-sm btn-success quick-response" data-id="' + row.id + '" title="رد سريع">';
                        actions += '<i class="fas fa-reply"></i></button>';
                    }
                    @endif

                    // Delete button
                    @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_DELETE))
                    actions += '<button class="btn btn-icon btn-sm btn-danger delete-complaint" data-id="' + row.id + '" title="حذف">';
                    actions += '<i class="fas fa-trash"></i></button>';
                    @endif

                    actions += '</div>';
                    return actions;
                }
            }
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
                order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        dom: 'Bfrtip',
        buttons: []
    });

    // Apply filters
    $('#applyFilters').click(function() {
        table.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').click(function() {
        $('#filterTicketNumber').val('');
        $('#filterStatus').val('');
        $('#filterPriority').val('');
        $('#filterType').val('').trigger('change');
        $('#filterAssigned').val('').trigger('change');
        $('#filterPeriod').val('');
        $('#filterDateFrom').val('');
        $('#filterDateTo').val('');
        $('#customDateRange').hide();
        table.ajax.reload();
    });

    // Show custom date range
    $('#filterPeriod').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').slideDown();
        } else {
            $('#customDateRange').slideUp();
        }
    });

    // View toggle
    $('#tableView').click(function() {
        $(this).addClass('active');
        $('#cardView').removeClass('active');
        $('#tableViewContent').show();
        $('#cardViewContent').hide();
    });

    $('#cardView').click(function() {
        $(this).addClass('active');
        $('#tableView').removeClass('active');
        $('#tableViewContent').hide();
        $('#cardViewContent').show();
        loadCardView();
    });

    // Quick response
    $(document).on('click', '.quick-response', function() {
        let complaintId = $(this).data('id');
        $('#quick_complaint_id').val(complaintId);
        $('#quickResponseModal').modal('show');
    });

    // Save quick response
    $('#saveQuickResponse').click(function() {
        let formData = $('#quickResponseForm').serialize();

                $.ajax({
            url: "{{ route('complaints.quick-response') }}",
                    type: 'POST',
                    data: formData,
            success: function(response) {
                if (response.success) {
                    $('#quickResponseModal').modal('hide');
                    $('#quickResponseForm')[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح',
                        text: 'تم إرسال الرد بنجاح',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    table.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: response.message || 'حدث خطأ'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء إرسال الرد'
                });
            }
        });
    });

    // Delete complaint
    $(document).on('click', '.delete-complaint', function() {
        let complaintId = $(this).data('id');

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "لن تتمكن من التراجع عن هذا الإجراء!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/complaints/' + complaintId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم الحذف',
                                text: 'تم حذف الشكوى بنجاح',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء الحذف'
                        });
                    }
                });
            }
        });
    });

    // Update statistics
    function updateStatistics(stats) {
        $('#totalTickets').text(stats.total || 0);
        $('#pendingTickets').text(stats.pending || 0);
        $('#inProgressTickets').text(stats.in_progress || 0);
        $('#resolvedTickets').text(stats.resolved || 0);
    }

    // Load card view
    function loadCardView() {
        $.ajax({
            url: "{{ route('complaints.cards') }}",
            type: 'GET',
            data: {
                status: $('#filterStatus').val(),
                priority: $('#filterPriority').val(),
                type: $('#filterType').val(),
                assigned_to: $('#filterAssigned').val()
            },
            success: function(response) {
                let html = '';

                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(ticket) {
                        html += createTicketCard(ticket);
                    });
                } else {
                    html = '<div class="col-12 text-center"><p class="text-muted">لا توجد شكاوى</p></div>';
                }

                $('#ticketsCards').html(html);
            }
        });
    }

    // Create ticket card
    function createTicketCard(ticket) {
        let priorityClass = 'priority-' + (ticket.priority || 'medium');
        let statusBadge = getStatusBadge(ticket.status);
        let priorityBadge = getPriorityBadge(ticket.priority);

        return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="ticket-card ${priorityClass}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="ticket-number">#${String(ticket.id).padStart(6, '0')}</span>
                        ${statusBadge}
                    </div>

                    <h5 class="card-title">${ticket.subject || 'بدون موضوع'}</h5>

                    <div class="ticket-meta">
                        <span><i class="fas fa-user"></i>${ticket.complainant_name || 'غير محدد'}</span>
                        <span><i class="fas fa-calendar"></i>${new Date(ticket.created_at).toLocaleDateString('ar-SA')}</span>
                    </div>

                    <div class="mt-3">
                        ${priorityBadge}
                        ${ticket.complaintType ? '<span class="badge bg-info ms-2">' + ticket.complaintType.name + '</span>' : ''}
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <a href="/admin/complaints/${ticket.id}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i>عرض
                        </a>
                        ${ticket.status === 'pending' || ticket.status === 'in_progress' ?
                            '<a href="/admin/complaints/' + ticket.id + '/edit" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>تعديل</a>' : ''}
                        ${ticket.status !== 'closed' && ticket.status !== 'resolved' ?
                            '<button class="btn btn-sm btn-success quick-response" data-id="' + ticket.id + '"><i class="fas fa-reply me-1"></i>رد</button>' : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Get status badge
    function getStatusBadge(status) {
        const statuses = {
            'pending': '<span class="status-badge status-pending">قيد الانتظار</span>',
            'in_progress': '<span class="status-badge status-in_progress">قيد المعالجة</span>',
            'resolved': '<span class="status-badge status-resolved">تم الحل</span>',
            'closed': '<span class="status-badge status-closed">مغلقة</span>',
            'rejected': '<span class="status-badge status-rejected">مرفوضة</span>'
        };
        return statuses[status] || '<span class="badge bg-secondary">' + status + '</span>';
    }

    // Get priority badge
    function getPriorityBadge(priority) {
        const priorities = {
            'urgent': '<span class="badge bg-danger">عاجلة</span>',
            'high': '<span class="badge bg-warning">عالية</span>',
            'medium': '<span class="badge bg-info">متوسطة</span>',
            'low': '<span class="badge bg-secondary">منخفضة</span>'
        };
        return priorities[priority] || '';
    }

    // Export data
    $('#exportData').click(function() {
        window.location.href = "{{ route('complaints.index') }}?export=excel&" + $.param({
            status: $('#filterStatus').val(),
            priority: $('#filterPriority').val(),
            type: $('#filterType').val(),
            assigned_to: $('#filterAssigned').val(),
            period: $('#filterPeriod').val(),
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val()
        });
    });
});
    </script>
@endsection
