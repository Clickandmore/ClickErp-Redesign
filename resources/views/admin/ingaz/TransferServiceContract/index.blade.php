@extends('admin.layout.indexs.index')

@section('title')
    عقود نقل الخدمات
@endsection

@section('style')
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"> --}}
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-group .btn {
            margin-right: 2px;
        }

        .badge {
            font-size: 0.75rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">عقود نقل الخدمات</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">الرئيسية</a></li>
                            <li class="breadcrumb-item active">عقود نقل الخدمات</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="draftCount">0</div>
                    <div class="stats-label">مسودة</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="sentCount">0</div>
                    <div class="stats-label">تم الإرسال</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="signedCount">0</div>
                    <div class="stats-label">تم التوقيع</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="activeCount">0</div>
                    <div class="stats-label">نشط</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="completedCount">0</div>
                    <div class="stats-label">مكتمل</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-number" id="cancelledCount">0</div>
                    <div class="stats-label">ملغى</div>
                </div>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="filter-card">
            <h5 class="mb-3"><i class="fas fa-filter"></i> فلاتر البحث</h5>
            <div class="row">
                @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-3'])
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="status_filter" class="form-label">الحالة</label>
                        <select class="form-select" id="status_filter">
                            <option value="">جميع الحالات</option>
                            <option value="draft">مسودة</option>
                            <option value="sent">تم الإرسال</option>
                            <option value="signed">تم التوقيع</option>
                            <option value="active">نشط</option>
                            <option value="completed">مكتمل</option>
                            <option value="cancelled">ملغى</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="contract_number_filter" class="form-label">رقم العقد</label>
                        <input type="text" class="form-control" id="contract_number_filter" placeholder="رقم العقد">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="date_from_filter" class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" id="date_from_filter">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="date_to_filter" class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" id="date_to_filter">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="request_type_filter" class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                        <select class="form-select" id="request_type_filter">
                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                            @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="button" class="btn btn-primary" id="applyFilters">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <button type="button" class="btn btn-secondary" id="clearFilters">
                        <i class="fas fa-eraser"></i> مسح الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <!-- جدول العقود -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-list"></i> قائمة عقود نقل الخدمات</h5>
                <div>
                    <button type="button" class="btn btn-success" id="exportExcel">
                        <i class="fas fa-file-excel"></i> تصدير Excel
                    </button>
                    <button type="button" class="btn btn-danger" id="exportPdf">
                        <i class="fas fa-file-pdf"></i> تصدير PDF
                    </button>
                </div>
            </div>
            <div class="card shadow">

                <div class="card-body">
                    <table id="contractsTable" class="table table-striped table-bordered nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم العقد</th>
                                <th>طلب النقل</th>
                                <th>الكفيل القديم</th>
                                <th>الكفيل الجديد</th>
                                <th>العاملة</th>
                                <th>قيمة العقد</th>
                                <th>الحالة</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- سيتم تحميل البيانات عبر AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            let contractsTable;

            // تهيئة الجدول
            function initializeTable() {
                contractsTable = $('#contractsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('TransferServiceContract.index') }}",
                        data: function(d) {
                            d.branch_id = $('#branchFilter').val();
                            d.status = $('#status_filter').val();
                            d.contract_number = $('#contract_number_filter').val();
                            d.date_from = $('#date_from_filter').val();
                            d.date_to = $('#date_to_filter').val();
                            d.request_type = $('#request_type_filter').val();
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'contract_number',
                            name: 'contract_number'
                        },
                        {
                            data: 'transfer_info',
                            name: 'transfer_info',
                            orderable: false
                        },
                        {
                            data: 'old_sponsor',
                            name: 'old_sponsor'
                        },
                        {
                            data: 'new_sponsor',
                            name: 'new_sponsor'
                        },
                        {
                            data: 'maid_name',
                            name: 'maid_name'
                        },
                        {
                            data: 'contract_value',
                            name: 'contract_value'
                        },
                        {
                            data: 'status',
                            name: 'status'
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
                        url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ar.json'
                    },
                    responsive: true,
                    scrollX: true,
                    dom: 'Bfrtip',
                    buttons: [{
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            className: 'btn btn-success btn-sm'
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            className: 'btn btn-danger btn-sm'
                        }
                    ],
                    drawCallback: function(settings) {
                        updateStats();
                    }
                });
            }

            // تحديث الإحصائيات
            function updateStats() {
                $.ajax({
                    url: "{{ route('TransferServiceContract.index') }}",
                    data: {
                        get_stats: true,
                        branch_id: $('#branchFilter').val(),
                        status: $('#status_filter').val(),
                        contract_number: $('#contract_number_filter').val(),
                        date_from: $('#date_from_filter').val(),
                        date_to: $('#date_to_filter').val()
                    },
                    success: function(response) {
                        if (response.stats) {
                            $('#draftCount').text(response.stats.draft || 0);
                            $('#sentCount').text(response.stats.sent || 0);
                            $('#signedCount').text(response.stats.signed || 0);
                            $('#activeCount').text(response.stats.active || 0);
                            $('#completedCount').text(response.stats.completed || 0);
                            $('#cancelledCount').text(response.stats.cancelled || 0);
                        }
                    }
                });
            }

            // تطبيق الفلاتر
            $('#applyFilters').on('click', function() {
                contractsTable.ajax.reload();
            });

            // مسح الفلاتر
            $('#clearFilters').on('click', function() {
                $('#branchFilter').val('');
                $('#status_filter').val('');
                $('#contract_number_filter').val('');
                $('#date_from_filter').val('');
                $('#date_to_filter').val('');
                $('#request_type_filter').val('');
                contractsTable.ajax.reload();
            });

            // تصدير Excel
            $('#exportExcel').on('click', function() {
                window.location.href = "{{ route('TransferServiceContract.index') }}?export=excel&" +
                    "branch_id=" + $('#branchFilter').val() + "&" +
                    "status=" + $('#status_filter').val() + "&" +
                    "contract_number=" + $('#contract_number_filter').val() + "&" +
                    "date_from=" + $('#date_from_filter').val() + "&" +
                    "date_to=" + $('#date_to_filter').val() + "&" +
                    "request_type=" + $('#request_type_filter').val();
            });

            // تصدير PDF
            $('#exportPdf').on('click', function() {
                window.location.href = "{{ route('TransferServiceContract.index') }}?export=pdf&" +
                    "branch_id=" + $('#branchFilter').val() + "&" +
                    "status=" + $('#status_filter').val() + "&" +
                    "contract_number=" + $('#contract_number_filter').val() + "&" +
                    "date_from=" + $('#date_from_filter').val() + "&" +
                    "date_to=" + $('#date_to_filter').val() + "&" +
                    "request_type=" + $('#request_type_filter').val();
            });

            // البحث عند الضغط على Enter
            $('.form-control').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#applyFilters').click();
                }
            });

            // تهيئة الجدول
            initializeTable();
        });
    </script>
@endsection
