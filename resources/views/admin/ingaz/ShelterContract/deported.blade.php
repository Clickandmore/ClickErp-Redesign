@extends('admin.layout.indexs.index')

@section('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- <style>
    /* إزالة الظلال من الجدول */
    .table * {
        text-shadow: none !important;
        box-shadow: none !important;
    }

    /* تحسين وضوح النص */
    .table td, .table th {
        color: #212529 !important;
        background-color: transparent;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    /* تحسين الشارات */
    .badge {
        text-shadow: none !important;
        box-shadow: none !important;
    }

    /* التأكد من وضوح البيانات */
    .text-muted {
        color: #6c757d !important;
        text-shadow: none !important;
    }

    .table strong {
        color: #212529 !important;
        font-weight: 600;
    }

    .table small {
        color: #6c757d !important;
    }

    /* إزالة أي تأثيرات من admin-tables.css */
    #deportedTable td,
    #deportedTable th,
    #deportedTable * {
        text-shadow: none !important;
        box-shadow: none !important;
        filter: none !important;
        opacity: 1 !important;
    }

    .deported-badge {
        background: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.875rem;
    }
</style> --}}
@endsection

@section('page-title')
تم الترحيل
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
<div class="container-fluid">
    <!-- Page Title Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">قائمة العقود التي تم ترحيلها</h4>
            </div>
        </div>
    </div>

    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle text-success me-2"></i>
            تم الترحيل
        </h1>
    </div>

    <!-- Filter Section -->
    <div class="card filter-card mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-filter me-2"></i>{{helperTrans("admin.filters")}}
            </h6>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">رقم العقد</label>
                        <input type="text" class="form-control" id="filterContractNumber" placeholder="رقم العقد">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">اسم العاملة</label>
                        <input type="text" class="form-control" id="filterMaidName" placeholder="اسم العاملة">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">تاريخ الترحيل</label>
                        <input type="date" class="form-control" id="filterDeportationDate">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <button class="btn btn-secondary" id="clearFilters">
                                <i class="fas fa-times"></i> مسح
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card">
        <div class="card-body">
                <table id="deportedTable" class="table table-bordered table-hover table-style">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم العقد</th>
                            <th>بيانات العاملة</th>
                            <th>العميل</th>
                            <th>الجنسية</th>
                            <th>تاريخ الترحيل</th>
                            <th>المرحل بواسطة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#deportedTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: '{{ route("ShelterContract.deported") }}',
                type: 'GET',
                data: function(d) {
                    d.contract_number = $('#filterContractNumber').val();
                    d.maid_name = $('#filterMaidName').val();
                    d.deportation_date = $('#filterDeportationDate').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    console.error('Response:', xhr.responseText);
                    Swal.fire('خطأ!', 'حدث خطأ في تحميل البيانات', 'error');
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'contract_number', name: 'contract_id' },
                { data: 'maid_data', name: 'maid_data', orderable: false },
                { data: 'client', name: 'client', orderable: false },
                { data: 'nationality', name: 'nationality', orderable: false },
                { data: 'deportation_date', name: 'completed_at' },
                { data: 'completed_by', name: 'completed_by', orderable: false },
                { data: 'status', name: 'status', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            },
            order: [[5, 'desc']], // ترتيب حسب تاريخ الترحيل
            pageLength: 25,
            dom: 'lrtip'
        });

        // Apply filters
        $('#applyFilters').on('click', function() {
            dataTable.ajax.reload();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#filterContractNumber').val('');
            $('#filterMaidName').val('');
            $('#filterDeportationDate').val('');
            dataTable.ajax.reload();
        });
    });
    </script>
@endsection
