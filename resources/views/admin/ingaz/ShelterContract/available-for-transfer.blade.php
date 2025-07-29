@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
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
    #availableForTransferTable td,
    #availableForTransferTable th,
    #availableForTransferTable * {
        text-shadow: none !important;
        box-shadow: none !important;
        filter: none !important;
        opacity: 1 !important;
    }
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
    <div class="row clearfix g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">متاح للنقل</h4>
                </div>
                <div class="card-body">
                    <!-- الفلاتر -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <button class="btn btn-link" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                    <i class="fas fa-filter"></i> الفلاتر
                                </button>
                            </h5>
                        </div>
                        <div id="filterCollapse" class="collapse">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">رقم العقد</label>
                                        <input type="text" class="form-control" id="filterContractNumber" placeholder="رقم العقد">
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

                    <!-- الجدول -->
                    <div class="table-responsive">
                        <table id="availableForTransferTable" class="table table-bordered table-hover">
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
                                    <th>الإجراءات</th>
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
@else
    @include('components.no_permissions')
@endif
@endsection

@section("js")
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#availableForTransferTable').DataTable({
            processing: false,
            serverSide: true,
            ajax: {
                url: '{{ route("ShelterContract.availableForTransfer") }}',
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
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'contract_number', name: 'id' },
                { data: 'maid_data', name: 'maid_data', orderable: false },
                { data: 'client', name: 'client', orderable: false },
                { data: 'nationality', name: 'nationality', orderable: false },
                { data: 'shelter', name: 'shelter', orderable: false },
                { data: 'arrival_date', name: 'arrival_date' },
                { data: 'guarantee_status', name: 'guarantee_status', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            },
            order: [[1, 'desc']],
            pageLength: 25,
            dom: 'lrtip'
        });

        // Load nationalities
        $.ajax({
            url: '{{ route("ShelterContract.getNationalities") }}',
            type: 'GET',
            success: function(data) {
                var select = $('#filterNationality');
                $.each(data, function(index, nationality) {
                    select.append('<option value="' + nationality.id + '">' + nationality.title + '</option>');
                });
            }
        });

        // Load shelters
        $.ajax({
            url: '{{ route("ShelterContract.getShelterPlaces") }}',
            type: 'GET',
            success: function(data) {
                var select = $('#filterShelter');
                $.each(data, function(index, shelter) {
                    select.append('<option value="' + shelter.id + '">' + shelter.title + '</option>');
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

            if (action === 'transfer') {
                window.location.href = '{{ route("SponsorshipTransfer.create") }}?contract_id=' + id;
            } else if (action === 'back') {
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
                            url: '{{ route("ShelterContract.back", ":id") }}'.replace(':id', id),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('نجح!', response.message || 'تم تنفيذ العملية بنجاح', 'success');
                                dataTable.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء تنفيذ العملية', 'error');
                            }
                        });
                    }
                });
            }
        });
    });
    </script>
@endsection
