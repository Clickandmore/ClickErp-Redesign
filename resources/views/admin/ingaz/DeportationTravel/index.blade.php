@extends('admin.layout.indexs.index')

@section('title')
رحلات الترحيل
@endsection

@section('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<style>
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
#deportationTravelTable td,
#deportationTravelTable th,
#deportationTravelTable * {
    text-shadow: none !important;
    box-shadow: none !important;
    filter: none !important;
    opacity: 1 !important;
}

.maid-info, .user-info {
    line-height: 1.6;
}

.maid-info .fw-bold, .user-info .fw-bold {
    display: block;
    margin-bottom: 2px;
}

.travel-details {
    line-height: 1.8;
}

.travel-details i {
    width: 20px;
    color: #5a5c69;
    margin-left: 5px;
}

/* تنسيق زر الإجراءات */
.actions-dropdown {
    position: relative;
}

.actions-btn {
    background: #fff;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease-in-out;
}

.actions-btn:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #495057;
}

.actions-btn i {
    margin-left: 0.25rem;
}

/* القائمة المنسدلة */
.dropdown-menu {
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0;
    font-size: 0.875rem;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    color: #212529;
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #16181b;
}

.dropdown-item i {
    width: 20px;
    margin-left: 0.5rem;
    color: inherit;
}
</style>
@endsection

@section('content')
<div class="container-fluid rtl-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="page-title">
                    <i class="fa fa-plane-departure"></i> رحلات الترحيل
                </h4>
                <a href="{{ route('DeportationTravel.create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> جدولة رحلة جديدة
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="contract_number" class="form-label">رقم العقد</label>
                            <input type="text" class="form-control" id="contract_number" placeholder="ابحث برقم العقد">
                        </div>
                        <div class="col-md-3">
                            <label for="maid_name" class="form-label">اسم العاملة</label>
                            <input type="text" class="form-control" id="maid_name" placeholder="ابحث باسم العاملة">
                        </div>
                        <div class="col-md-3">
                            <label for="travel_date" class="form-label">تاريخ السفر</label>
                            <input type="date" class="form-control" id="travel_date">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadTable()">
                                <i class="fa fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-list"></i> قائمة رحلات الترحيل
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="deportationTravelTable" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم العقد</th>
                                    <th>بيانات العاملة</th>
                                    <th>بيانات العميل</th>
                                    <th>تفاصيل الرحلة</th>
                                    <th>الحالة</th>
                                    <th width="100">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    loadTable();
});

function loadTable() {
    if ($.fn.DataTable.isDataTable('#deportationTravelTable')) {
        $('#deportationTravelTable').DataTable().destroy();
    }

    $('#deportationTravelTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
        },
        ajax: {
            url: "{{ route('DeportationTravel.index') }}",
            type: 'GET',
            data: function(d) {
                d.contract_number = $('#contract_number').val();
                d.maid_name = $('#maid_name').val();
                d.travel_date = $('#travel_date').val();
            },
            dataSrc: function(json) {
                console.log('DataTables Response:', json);
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'contract_number', name: 'contract_number' },
            { data: 'maid_data', name: 'maid_data', orderable: false },
            { data: 'client', name: 'client', orderable: false },
            { data: 'travel_details', name: 'travel_details', orderable: false },
            { data: 'status', name: 'status' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let html = '<div class="dropdown actions-dropdown">';
                    html += '<button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">';
                    html += '<i class="fas fa-cogs me-1"></i> الإجراءات';
                    html += '</button>';
                    html += '<ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض التفاصيل
                    html += `<li><a class="dropdown-item" href="{{ route('DeportationTravel.show', '') }}/${row.id}">
                        <i class="fas fa-eye"></i> عرض التفاصيل
                    </a></li>`;

                    // تعديل
                    html += `<li><a class="dropdown-item" href="{{ route('DeportationTravel.create') }}?id=${row.id}">
                        <i class="fas fa-edit"></i> تعديل
                    </a></li>`;

                    // إكمال الرحلة
                    if (row.status !== 'completed') {
                        html += '<li><hr class="dropdown-divider"></li>';
                        html += `<li><a class="dropdown-item" href="javascript:void(0)" onclick="completeTravel(${row.id})">
                            <i class="fas fa-check"></i> إكمال الرحلة
                        </a></li>`;
                    }

                    html += '<li><hr class="dropdown-divider"></li>';

                    // حذف
                    html += `<li><a class="dropdown-item" href="javascript:void(0)" onclick="deleteTravel(${row.id})">
                        <i class="fas fa-trash"></i> حذف
                    </a></li>`;

                    html += '</ul>';
                    html += '</div>';

                    return html;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            // إخفاء الـ loader بعد اكتمال التحميل
            $('.dataTables_processing').hide();
        }
    });
}

// دالة حذف الرحلة
function deleteTravel(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "لن تتمكن من استرجاع هذه الرحلة بعد الحذف!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('DeportationTravel.index') }}/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم الحذف!', 'تم حذف الرحلة بنجاح.', 'success');
                    $('#deportationTravelTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            });
        }
    });
}

// دالة إكمال الرحلة
function completeTravel(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "هل تريد تأكيد إكمال هذه الرحلة؟",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، أكمل الرحلة!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('DeportationTravel') }}/${id}/complete`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم!', response.message || 'تم إكمال الرحلة بنجاح', 'success');
                    $('#deportationTravelTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ', 'error');
                }
            });
        }
    });
}
</script>
@endsection
