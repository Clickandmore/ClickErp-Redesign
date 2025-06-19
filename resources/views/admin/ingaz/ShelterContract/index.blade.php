@extends('admin.layout.indexs.index')

@section("style")
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<style>
    .badge {
        font-size: 0.8em;
    }
    .text-muted {
        color: #6c757d !important;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .me-1 {
        margin-right: 0.25rem !important;
    }
    .table th {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    .table td {
        vertical-align: middle;
    }
    .dataTables_wrapper .dataTables_processing {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .dt-buttons {
        margin-bottom: 10px;
    }
    .dt-button {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }
    .dt-button:hover {
        background-color: #0056b3 !important;
        border-color: #0056b3 !important;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .card-tools {
        float: right;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 10px;
    }
    .dataTables_wrapper .dataTables_info {
        margin-top: 10px;
    }
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 10px;
    }
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .alert {
        border-radius: 0.25rem;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
    }
    .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeaa7;
    }
    .fa-exclamation-triangle {
        margin-right: 0.5rem;
    }
    .fa-spinner {
        margin-right: 0.5rem;
    }
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
    .badge.bg-primary {
        background-color: #007bff !important;
    }
    .badge.bg-success {
        background-color: #28a745 !important;
    }
    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    .badge.bg-info {
        background-color: #17a2b8 !important;
    }
    .badge.bg-secondary {
        background-color: #6c757d !important;
    }
</style>
@endsection

@section('page-title')
{{helperTrans("ingaz.All Shelter contracts")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">{{helperTrans("ingaz.All Shelter contracts")}}</h4>
            <div class="card-tools">
                <a href="{{ route('ShelterContract.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> إضافة عقد إيواء جديد
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{helperTrans("ingaz.maid_name")}}</th>
                            <th>{{helperTrans("ingaz.passport_number")}}</th>
                            <th>{{helperTrans("ingaz.maid_type")}}</th>
                            <th>{{helperTrans("ingaz.nationality")}}</th>
                            <th>{{helperTrans("ingaz.shelter_location")}}</th>
                            <th>{{helperTrans("ingaz.guarantee_status")}}</th>
                            <th>{{helperTrans("ingaz.arrival_date")}}</th>
                            <th>{{helperTrans("ingaz.return_date")}}</th>
                            <th>{{helperTrans("ingaz.status")}}</th>
                            {{-- <th>{{helperTrans("ingaz.make back")}}</th> --}}
                            <th>{{helperTrans("ingaz.actions")}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">تحديث الحالة</h5>
                    <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rowId" name="rowId">
                    <input type="hidden" id="status" name="status">
                    <div class="form-group">
                        <label for="statusDate">تاريخ الحالة:</label>
                        <input type="date" id="statusDate" required name="statusDate" class="form-control"
                            value="{{date('Y-m-d')}}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog" aria-labelledby="updateStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">ارجاع العقد</h5>
                    <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="contract_id" name="id">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        هل انت متأكد من ارجاع العقد ؟
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-danger" id="confirmBackBtn">تأكيد الإرجاع</button>
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {
    let columns = [
        {"data": 'id', name: 'id', orderable: false, searchable: false, width: '5%'},
        {"data": "maid_name", orderable: true, searchable: true, width: '10%'},
        {"data": "passport_number", orderable: true, searchable: true, width: '10%'},
        {"data": "maid_type", orderable: true, searchable: true, width: '10%'},
        {"data": "nationality", orderable: true, searchable: true, width: '10%'},
        {"data": "shelter_location", orderable: true, searchable: true, width: '15%'},
        {"data": "guarantee_status", orderable: true, searchable: true, width: '15%'},
        {"data": "arrival_date", orderable: true, searchable: true, width: '10%'},
        {"data": "return_date", orderable: true, searchable: true, width: '10%'},
        {"data": "status", orderable: true, searchable: true, width: '10%'},
        {"data": "actions", orderable: false, searchable: false, width: '15%'},
    ];

    $('#dataTableObject').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ShelterContract.index') }}",
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            error: function (xhr, error, thrown) {
                console.error('DataTables error:', error);
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    alert('خطأ في تحميل البيانات: ' + xhr.responseJSON.error);
                } else {
                    alert('حدث خطأ في تحميل البيانات');
                }
            }
        },
        columns: columns,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        drawCallback: function(settings) {
            // Add tooltips to buttons
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Add loading indicator
            if (settings.json) {
                console.log('Data loaded successfully');
            }
        },
        initComplete: function () {
            // Add custom filters if needed
            console.log('DataTable initialized successfully');
        }
    });

    // Add keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl + N for new contract
        if (e.ctrlKey && e.keyCode == 78) {
            e.preventDefault();
            window.location.href = "{{ route('ShelterContract.create') }}";
        }
    });

    // Add confirmation for delete buttons
    $(document).on('click', '.delete-btn', function(e) {
        if (!confirm('هل أنت متأكد من حذف هذا العقد؟')) {
            e.preventDefault();
            return false;
        }
    });
});

$('#updateStatusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var rowId = button.data('row-id');
    var status = button.data('status');
    var modal = $(this);
    modal.find('#rowId').val(rowId);
    modal.find('#status').val(status);
});

$('#saveStatusBtn').click(function () {
    var rowId = $('#rowId').val();
    var status = $('#status').val();
    var statusDate = $('#statusDate').val();

    if (!statusDate) {
        alert('يرجى إدخال تاريخ الحالة');
        return;
    }

    // Show loading state
    $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...');

    $.ajax({
        url: '{{ route('RecruitmentContract.show', ':rowId') }}'.replace(':rowId', rowId),
        method: 'GET',
        data: {
            status: status,
            date: statusDate,
        },
        success: function (response) {
            console.log('AJAX request successful');
            $('#dataTableObject').DataTable().ajax.reload();
            $('#updateStatusModal').modal('hide');
        },
        error: function (xhr, status, error) {
            console.error('AJAX request failed:', error);
            alert('حدث خطأ في تحديث الحالة');
        },
        complete: function () {
            // Reset button state
            $('#saveStatusBtn').prop('disabled', false).html('حفظ');
        }
    });
});

$(document).on('click', '.backContract', function () {
    var contractId = $(this).data('id');
    $('#contract_id').val(contractId);
    $('#confirmBackModal').modal('show');
});

$('#confirmBackBtn').click(function () {
    var rowId = $('#contract_id').val();

    // Show loading state
    $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري الإرجاع...');

    $.ajax({
        url: '{{ route('RecruitmentContract.back', ':rowId') }}'.replace(':rowId', rowId),
        method: 'GET',
        success: function (response) {
            console.log('AJAX request successful');
            $('#dataTableObject').DataTable().ajax.reload();
            $('#confirmBackModal').modal('hide');
        },
        error: function (xhr, status, error) {
            console.error('AJAX request failed:', error);
            alert('حدث خطأ في إرجاع العقد');
        },
        complete: function () {
            // Reset button state
            $('#confirmBackBtn').prop('disabled', false).html('تأكيد الإرجاع');
        }
    });
});
</script>
@endsection
