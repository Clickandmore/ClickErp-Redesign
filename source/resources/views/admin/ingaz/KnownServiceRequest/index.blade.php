@extends('admin.layout.indexs.index')

@section('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
    طلبات خدمة معروفة
@endsection

@section('content')
    <div class="page-body p-4 text-dark admin-tables">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">طلبات خدمة معروفة</h4>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card total-card shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">إجمالي الطلبات</div>
                                    <div class="h5 mb-0 font-weight-bold">{{ $data['count'] ?? 0 }}</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-file-alt fa-2x text-primary"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card wants-work-card shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المعتمدة</div>
                                    <div class="h5 mb-0 font-weight-bold">{{ $data['approvedCount'] ?? 0 }}</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-check-circle fa-2x text-success"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card within-guarantee-card shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المرفوضة</div>
                                    <div class="h5 mb-0 font-weight-bold">{{ $data['rejectedCount'] ?? 0 }}</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-times-circle fa-2x text-danger"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card expired-guarantee-card shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المعلقة</div>
                                    <div class="h5 mb-0 font-weight-bold">{{ $data['pendingCount'] ?? 0 }}</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-hourglass-half fa-2x text-warning"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-filter me-2"></i>الفلاتر</h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse" id="filterCollapse">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nationality" class="form-label">الجنسية</label>
                                <select id="nationality" class="form-select">
                                    <option value="">الكل</option>
                                    @foreach ($data['nationalities'] as $nationality)
                                        <option value="{{ $nationality->id }}">{{ $nationality->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">الحالة</label>
                                <select id="status" class="form-select">
                                    <option value="">الكل</option>
                                    @foreach ($data['status'] as $state)
                                        <option value="{{ $state->value }}">{{ $state->lang() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <button type="button" id="clearFilters" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i> مسح الفلاتر</button>
                                <button type="button" id="applyFilters" class="btn btn-primary"><i class="fas fa-search me-1"></i> بحث</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-list me-2"></i>طلبات خدمة معروفة</h6>
                </div>
                <div class="card-body">
                    <table id="knownServiceRequestsTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم العميل</th>
                                <th>هاتف العميل</th>
                                <th>اسم العاملة</th>
                                <th>الجنسية</th>
                                <th>رقم الجواز</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.addEventListener('load', function() {
            if (typeof $.fn.DataTable === 'undefined') {
                return;
            }

            const dataTable = $('#knownServiceRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: '{{ route("known-service-requests.index") }}',
                    type: 'GET',
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function(d) {
                        d.status = $('#status').val();
                        d.nationality = $('#nationality').val();
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', 'حدث خطأ في تحميل البيانات', 'error');
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'client_name', name: 'client_name'},
                    {data: 'client_phone', name: 'client_phone'},
                    {data: 'worker_name', name: 'worker_name'},
                    {data: 'nationality', name: 'nationality', orderable: false},
                    {data: 'passport_number', name: 'passport_number'},
                    {data: 'status', name: 'status', orderable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                },
                order: [[1, 'desc']],
                pageLength: 25,
                dom: 'lrtip'
            });

            $('#applyFilters, #clearFilters').on('click', function() {
                if ($(this).attr('id') === 'clearFilters') {
                    $('#status').val('');
                    $('#nationality').val('');
                }

                dataTable.ajax.reload();
            });

            $(document).on('click', '.delete-order-request', function() {
                const orderId = $(this).data('id');
                const url = "{{ route('known-service-requests.destroy', 'requestId') }}".replace('requestId', orderId);

                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    text: 'لا يمكن التراجع عن هذا الإجراء',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {_token: '{{ csrf_token() }}'},
                            success: function(response) {
                                dataTable.ajax.reload();
                                Swal.fire('تم', 'تم حذف الطلب بنجاح', 'success');
                            },
                            error: function() {
                                Swal.fire('خطأ!', 'تعذر حذف الطلب', 'error');
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.accept-request', function() {
                updateStatus($(this).data('id'), 'approved', 'تم اعتماد الطلب');
            });

            $(document).on('click', '.reject-request', function() {
                updateStatus($(this).data('id'), 'rejected', 'تم رفض الطلب');
            });

            function updateStatus(id, status, successText) {
                Swal.fire({
                    title: 'هل أنت متأكد؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم',
                    cancelButtonText: 'لا'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: "{{ route('known-service-requests.update', ':id') }}".replace(':id', id),
                        type: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status,
                        },
                        success: function() {
                            Swal.fire('تم', successText, 'success');
                            dataTable.ajax.reload();
                        },
                        error: function() {
                            Swal.fire('خطأ!', 'تعذر تحديث الحالة', 'error');
                        }
                    });
                });
            }
        });
    </script>
@endsection
