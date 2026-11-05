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
        .table td,
        .table th {
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
        #availableForTransferTable td,
        #availableForTransferTable th,
        #availableForTransferTable * {
            text-shadow: none !important;
            box-shadow: none !important;
            filter: none !important;
            opacity: 1 !important;
        }
    </style> --}}
@endsection

@section('page-title')
    متاح للنقل
@endsection

@section('content')
    @if (@AdminGuardHasAnyPerm([\App\PermKeys::SHELTER_CONTRACT_AVAILABLE_FOR_TRANSFER_VIEW, \App\PermKeys::SHELTER_CONTRACT_VIEW, \App\PermKeys::SHELTER_CONTRACT_CONTROL]))
        <div class="container-fluid">
            <!-- Page Title Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">قائمة العقود المتاحة للنقل</h4>
                    </div>
                </div>
            </div>

            <!-- Header Section -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-exchange-alt text-success me-2"></i>
                    متاح للنقل
                </h1>
            </div>

            <!-- Filter Section -->
            <div class="card filter-card mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center" style="cursor: pointer;"
                    data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-filter me-2"></i>{{ helperTrans('admin.filters') }}
                    </h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse show" id="filterCollapse">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">رقم العقد</label>
                                <input type="text" class="form-control" id="filterContractNumber"
                                    placeholder="رقم العقد">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الجنسية</label>
                                <select class="form-select" id="filterNationality">
                                    <option value="">جميع الجنسيات</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الإيواء</label>
                                <select class="form-select" id="filterShelter">
                                    <option value="">جميع الأماكن</option>
                                </select>
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
                    <table id="availableForTransferTable" class="table table-bordered table-hover table-style">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم العقد</th>
                                <th>بيانات العاملة</th>
                                <th>العميل</th>
                                <th>الجنسية</th>
                                <th>الإيواء</th>
                                <th>تاريخ الوصول</th>
                                <th>حالة الضمان</th>
                                <th>حالة الظهور على الموقع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
        <!-- Change Work Desire Modal -->
        <div class="modal fade" id="changeWorkDesireModal" tabindex="-1" role="dialog"
            aria-labelledby="changeWorkDesireModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeWorkDesireModalLabel">تغيير رغبة العمل</h5>
                        <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="changeWorkDesireForm">
                            <input type="hidden" id="workDesireContractId" name="contract_id">

                            <div class="mb-3">
                                <label class="form-label"><strong>الرغبة الحالية:</strong></label>
                                <div id="currentWorkDesire" class="alert alert-info"></div>
                            </div>

                            <div class="mb-3">
                                <label for="newWorkDesire" class="form-label">الرغبة الجديدة <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newWorkDesire" name="wants_to_work" required>
                                    <option value="">اختر رغبة العمل</option>
                                    <option value="yes">ترغب بالعمل</option>
                                    <option value="no">لا ترغب بالعمل</option>
                                    <option value="unknown">غير محدد</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="changeReason" class="form-label">سبب التغيير (اختياري)</label>
                                <textarea class="form-control" id="changeReason" name="reason" rows="3"
                                    placeholder="اكتب سبب تغيير رغبة العمل..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" id="saveWorkDesireBtn">حفظ التغيير</button>
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
            // Initialize DataTable
            var dataTable = $('#availableForTransferTable').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: '{{ route('ShelterContract.availableForTransfer') }}',
                    type: 'GET',
                    data: function(d) {
                        d.contract_number = $('#filterContractNumber').val();
                        d.nationality = $('#filterNationality').val();
                        d.shelter = $('#filterShelter').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Response:', xhr.responseText);
                        Swal.fire('خطأ!', 'حدث خطأ في تحميل البيانات', 'error');
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
                        name: 'id'
                    },
                    {
                        data: 'maid_data',
                        name: 'maid_data',
                        orderable: false
                    },
                    {
                        data: 'client',
                        name: 'client',
                        orderable: false
                    },
                    {
                        data: 'nationality',
                        name: 'nationality',
                        orderable: false
                    },
                    {
                        data: 'shelter',
                        name: 'shelter',
                        orderable: false
                    },
                    {
                        data: 'arrival_date',
                        name: 'arrival_date'
                    },
                    {
                        data: 'guarantee_status',
                        name: 'guarantee_status',
                        orderable: false
                    },
                    {
                        data: 'show_site_status',
                        name: 'show_site_status',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                },
                order: [
                    [1, 'desc']
                ],
                pageLength: 25,
                dom: 'lrtip'
            });

            // Load nationalities
            $.ajax({
                url: '{{ route('ShelterContract.getNationalities') }}',
                type: 'GET',
                success: function(data) {
                    var select = $('#filterNationality');
                    $.each(data, function(index, nationality) {
                        select.append('<option value="' + nationality.id + '">' + nationality
                            .title + '</option>');
                    });
                }
            });

            // Load shelters
            $.ajax({
                url: '{{ route('ShelterContract.getShelterPlaces') }}',
                type: 'GET',
                success: function(data) {
                    var select = $('#filterShelter');
                    $.each(data, function(index, shelter) {
                        select.append('<option value="' + shelter.id + '">' + shelter.title +
                            '</option>');
                    });
                }
            });

            // Apply filters
            $('#applyFilters').on('click', function() {
                dataTable.ajax.reload();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#filterContractNumber').val('');
                $('#filterNationality').val('');
                $('#filterShelter').val('');
                dataTable.ajax.reload();
            });

            // Handle action buttons
            $(document).on('click', '.confirm-action', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const id = $(this).data('id');
                const text = $(this).data('text') || 'هل أنت متأكد من هذا الإجراء؟';
                const workflow = $(this).data('workflow') || 'standard';

                if (action === 'transfer') {
                    window.location.href = '{{ route('SponsorshipTransfer.create') }}?contract_id=' + id +
                        '&workflow_source=' + encodeURIComponent(workflow);
                } else if (action === 'back' || action === 'return-to-customer') {
                    Swal.fire({
                        title: 'تأكيد',
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'نعم، تأكيد',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: (action === 'return-to-customer' ? '{{ route('ShelterContract.returnToCustomer', ':id') }}' : '{{ route('ShelterContract.back', ':id') }}').replace(
                                    ':id', id),
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    Swal.fire('نجح!', response.message ||
                                        'تم تنفيذ العملية بنجاح', 'success');
                                    dataTable.ajax.reload();
                                },
                                error: function(xhr) {
                                    Swal.fire('خطأ!', xhr.responseJSON?.message ||
                                        'حدث خطأ أثناء تنفيذ العملية', 'error');
                                }
                            });
                        }
                    });
                }
            });

            // Handle toggle instant receipt action
            $(document).on('click', '.toggle-instant-receipt', function(e) {
                e.preventDefault();
                const contractId = $(this).data('id');
                const currentStatus = $(this).data('current');
                const actionText = $(this).data('text');

                // Show confirmation
                Swal.fire({
                    title: 'تأكيد تغيير حالة الاستلام الفوري',
                    text: actionText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Perform the toggle
                        $.ajax({
                            url: '{{ route('ShelterContract.toggleInstantReceipt', ':id') }}'
                                .replace(':id', contractId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                dataTable.ajax.reload();
                                Swal.fire('نجح!', response.message ||
                                    'تم تغيير حالة الاستلام الفوري بنجاح', 'success'
                                );
                            },
                            error: function(xhr) {
                                let errorMessage =
                                    'حدث خطأ أثناء تغيير حالة الاستلام الفوري';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('خطأ!', errorMessage, 'error');
                            }
                        });
                    }
                });
            });
        });
        // Handle change work desire action
        $(document).on('click', '.change-work-desire', function(e) {
            e.preventDefault();
            const contractId = $(this).data('id');
            const currentDesire = $(this).data('current') || 'unknown';

            // Set values in modal
            $('#workDesireContractId').val(contractId);

            // Display current desire
            let currentDesireText = '';
            let badgeClass = '';
            switch (currentDesire) {
                case 'yes':
                    currentDesireText = 'ترغب بالعمل';
                    badgeClass = 'bg-success';
                    break;
                case 'no':
                    currentDesireText = 'لا ترغب بالعمل';
                    badgeClass = 'bg-warning';
                    break;
                default:
                    currentDesireText = 'غير محدد';
                    badgeClass = 'bg-secondary';
            }

            $('#currentWorkDesire').html('<span class="badge ' + badgeClass + '">' + currentDesireText + '</span>');

            // Reset form
            $('#newWorkDesire').val('');
            $('#changeReason').val('');

            // Show modal
            $('#changeWorkDesireModal').modal('show');
        });

        // Handle save work desire
        $('#saveWorkDesireBtn').on('click', function() {
            const contractId = $('#workDesireContractId').val();
            const newDesire = $('#newWorkDesire').val();
            const reason = $('#changeReason').val();

            // Validation
            if (!newDesire) {
                Swal.fire('خطأ!', 'يرجى اختيار رغبة العمل الجديدة', 'error');
                return;
            }

            // Show confirmation
            Swal.fire({
                title: 'تأكيد تغيير رغبة العمل',
                text: 'هل أنت متأكد من تغيير رغبة العمل؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، تغيير',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform the change
                    $.ajax({
                        url: '{{ route('ShelterContract.changeWorkDesire', ':id') }}'.replace(
                            ':id', contractId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            wants_to_work: newDesire,
                            reason: reason
                        },
                        success: function(response) {
                            $('#changeWorkDesireModal').modal('hide');
                            Swal.fire('نجح!', response.message || 'تم تغيير رغبة العمل بنجاح',
                                'success');
                            dataTable.ajax.reload();
                        },
                        error: function(xhr) {
                            let errorMessage = 'حدث خطأ أثناء تغيير رغبة العمل';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire('خطأ!', errorMessage, 'error');
                        }
                    });
                }
            });
        });
    </script>
@endsection
