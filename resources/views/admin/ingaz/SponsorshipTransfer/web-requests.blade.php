@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">



@endsection
@section('page-title')
    طلبات نقل الكفالة من الموقع الخارجي
@endsection
@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">
                    <i class="fas fa-globe text-primary me-2"></i>
                    طلبات نقل الكفالة من الموقع الخارجي
                </h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">لوحة التحكم</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('SponsorshipTransfer.index') }}">إدارة نقل الكفالة</a></li>
                    <li class="breadcrumb-item active">طلبات الموقع الخارجي</li>
                </ul>
            </div>
            <div class="col-auto">
                <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة لإدارة نقل الكفالة
                </a>
            </div>
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
                                طلبات جديدة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="new-requests-count">{{ number_format($statistics['new'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                طلبات مدفوعة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="paid-requests-count">{{ number_format($statistics['paid'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
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
                                طلبات مكتملة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completed-requests-count">{{ number_format($statistics['completed'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                إجمالي الطلبات
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-requests-count">{{ number_format($statistics['total'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        قائمة طلبات نقل الكفالة من الموقع الخارجي
                    </h5>
                </div>
                <div class="card-body">

                        <table id="web-requests-table" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>معلومات الطلب</th>
                                    <th>معلومات العميل</th>
                                    <th>معلومات العاملة</th>
                                    <th>ممثل خدمة العملاء</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">تأكيد الإجراء</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="confirmAction">تأكيد</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}

/* إجراءات الطلب معروضة بجانب بعضها بدل القائمة المنسدلة. */
.order-actions-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: flex-start;
}

.order-actions-inline .order-action {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    font-size: 0.78rem;
    line-height: 1.4;
    white-space: nowrap;
}

/* الشاشات الضيقة تكتفي بالأيقونة، والاسم يبقى في تلميح الزر. */
@media (max-width: 1399.98px) {
    .order-actions-inline .order-action span {
        display: none;
    }
}
</style>
@endpush

@push('js')
<!-- DataTables CSS and JS -->
{{-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script> --}}
<script src="{{asset("admin")}}/custom/admin-tables.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#web-requests-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("SponsorshipTransfer.webRequests") }}',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'order_info', name: 'order_info', orderable: false, searchable: false},
            {data: 'client_info', name: 'client_info', orderable: false, searchable: false},
            {data: 'maid_info', name: 'maid_info', orderable: false, searchable: false},
            {data: 'admin_info', name: 'admin_info', orderable: false, searchable: false},
            {data: 'status', name: 'status', orderable: true, searchable: true},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Handle confirmation actions
    $(document).on('click', '.confirm-action', function(e) {
        e.preventDefault();

        var action = $(this).data('action');
        var id = $(this).data('id');
        var text = $(this).data('text');
        var status = $(this).data('status');

        $('#confirmationMessage').text(text);
        $('#confirmAction').data('action', action).data('id', id).data('status', status);
        $('#confirmationModal').modal('show');
    });

    // Handle confirm button click
    $('#confirmAction').on('click', function() {
        var action = $(this).data('action');
        var id = $(this).data('id');
        var status = $(this).data('status');

        if (action === 'update-status') {
            updateOrderStatus(id, status);
        }

        $('#confirmationModal').modal('hide');
    });

    // Function to update order status
    function updateOrderStatus(id, status) {
        $.ajax({
            url: '/admin/orders/' + id + '/change-status',
            type: 'POST',
            data: {
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم التحديث بنجاح',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        table.ajax.reload();
                        updateStatistics();
                    });
                } else {
                    Swal.fire('خطأ!', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('خطأ!', 'حدث خطأ أثناء تحديث الحالة', 'error');
            }
        });
    }

    // Update statistics on page load
    updateStatistics();

    function updateStatistics() {
        $.ajax({
            url: '{{ route("SponsorshipTransfer.webRequests") }}',
            type: 'GET',
            data: { statistics: true },
            success: function(response) {
                if (response.statistics) {
                    $('#new-requests-count').text(response.statistics.new || 0);
                    $('#paid-requests-count').text(response.statistics.paid || 0);
                    $('#completed-requests-count').text(response.statistics.completed || 0);
                    $('#total-requests-count').text(response.statistics.total || 0);
                }
            }
        });
    }
});
</script>
@endpush
