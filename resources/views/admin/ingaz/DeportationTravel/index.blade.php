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

.actions-btn.show {
    background: #f8f9fa;
    border-color: #adb5bd;
}

/* تحسين dropdown menu */
.actions-dropdown .dropdown-menu {
    position: absolute;
    z-index: 1050;
    display: none;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: right;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.25rem;
}

.actions-dropdown .dropdown-menu.show {
    display: block;
}

.actions-dropdown .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
}

.actions-dropdown .dropdown-item:hover {
    color: #1e2125;
    background-color: #f8f9fa;
}

.actions-dropdown .dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid #e9ecef;
}
</style>
@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
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
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            // إخفاء الـ loader بعد اكتمال التحميل
            $('.dataTables_processing').hide();

            // تهيئة dropdown buttons
            initializeDropdowns();
        }
    });
}

// تهيئة dropdown
function initializeDropdowns() {
    // إزالة أي event listeners سابقة
    $(document).off('click', '.actions-btn');
    $(document).off('click', '.dropdown-item');

    // عند النقر على زر الإجراءات
    $(document).on('click', '.actions-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        var $dropdown = $button.next('.dropdown-menu');
        var isOpen = $dropdown.hasClass('show');

        // إغلاق جميع القوائم المفتوحة
        $('.dropdown-menu').removeClass('show');
        $('.actions-btn').attr('aria-expanded', 'false');

        // فتح/إغلاق القائمة الحالية
        if (!isOpen) {
            $dropdown.addClass('show');
            $button.attr('aria-expanded', 'true');

            // تحديد موضع القائمة
            var buttonOffset = $button.offset();
            var buttonHeight = $button.outerHeight();
            var dropdownHeight = $dropdown.outerHeight();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();

            // التحقق من وجود مساحة كافية أسفل الزر
            if (buttonOffset.top + buttonHeight + dropdownHeight > windowHeight + scrollTop) {
                $dropdown.css({
                    'position': 'absolute',
                    'top': 'auto',
                    'bottom': '100%',
                    'margin-bottom': '0.125rem'
                });
            } else {
                $dropdown.css({
                    'position': 'absolute',
                    'top': '100%',
                    'bottom': 'auto',
                    'margin-top': '0.125rem'
                });
            }
        }
    });

    // إغلاق القائمة عند النقر خارجها
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.actions-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('.actions-btn').attr('aria-expanded', 'false');
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
