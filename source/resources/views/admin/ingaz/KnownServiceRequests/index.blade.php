@extends('admin.layout.indexs.index')

@section('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
    طلبات الخدمة المعروفة
@endsection

@section('content')
    <div class="page-body p-4 text-dark admin-tables">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">طلبات الخدمة المعروفة</h4>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card total-card shadow h-100"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-uppercase mb-1">إجمالي الطلبات</div><div class="h5 mb-0 font-weight-bold">{{ $data['count'] ?? 0 }}</div></div><div class="col-auto"><i class="fas fa-file-alt fa-2x text-primary"></i></div></div></div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card shadow h-100"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المقبولة</div><div class="h5 mb-0 font-weight-bold">{{ $data['approvedCount'] ?? 0 }}</div></div><div class="col-auto"><i class="fas fa-check-circle fa-2x text-success"></i></div></div></div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card shadow h-100"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المرفوضة</div><div class="h5 mb-0 font-weight-bold">{{ $data['rejectedCount'] ?? 0 }}</div></div><div class="col-auto"><i class="fas fa-times-circle fa-2x text-danger"></i></div></div></div></div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card statistics-card shadow h-100"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-uppercase mb-1">الطلبات المعلقة</div><div class="h5 mb-0 font-weight-bold">{{ $data['pendingCount'] ?? 0 }}</div></div><div class="col-auto"><i class="fas fa-hourglass-half fa-2x text-warning"></i></div></div></div></div>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-filter me-2"></i>{{ helperTrans('admin.filters') }}</h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse" id="filterCollapse">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3"><label for="nationality" class="form-label">الجنسية</label><select id="nationality" class="form-select"><option value="">الكل</option>@foreach ($data['nationalities'] as $nationality)<option value="{{ $nationality->id }}">{{ $nationality->title }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label for="religion" class="form-label">الديانة</label><select id="religion" class="form-select"><option value="">الكل</option>@foreach ($data['religions'] as $religion)<option value="{{ $religion->id }}">{{ $religion->title }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label for="social_status" class="form-label">الحالة الاجتماعية</label><select id="social_status" class="form-select"><option value="">الكل</option>@foreach ($data['socialStatuses'] as $socialStatus)<option value="{{ $socialStatus->id }}">{{ $socialStatus->title }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label for="status" class="form-label">الحالة</label><select id="status" class="form-select"><option value="">الكل</option>@foreach ($data['status'] as $state)<option value="{{ $state->value }}">{{ $state->lang() }}</option>@endforeach</select></div>
                        </div>
                        <div class="row mt-3"><div class="col-12 text-end"><button type="button" id="clearFilters" class="btn btn-secondary me-2"><i class="fas fa-times me-1"></i> مسح الفلاتر</button><button type="button" id="applyFilters" class="btn btn-primary"><i class="fas fa-search me-1"></i> بحث</button></div></div>
                    </div>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-primary text-white"><h6 class="m-0 font-weight-bold"><i class="fas fa-list me-2"></i>طلبات الخدمة المعروفة</h6></div>
                <div class="card-body">
                    <table id="knownServiceRequestsTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead><tr><th>#</th><th>اسم العميل</th><th>اسم العاملة</th><th>الجنسية</th><th>المدينة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/vendor/toster/js/jquery.toast.js"></script>
    <script>
        window.addEventListener('load', function() {
            const dataTable = $('#knownServiceRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: '{{ route('known-service-requests.index') }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function(d) {
                        d.status = $('#status').val();
                        d.nationality = $('#nationality').val();
                        d.religion = $('#religion').val();
                        d.social_status = $('#social_status').val();
                    },
                    error: function(xhr) {
                        let message = 'حدث خطأ في تحميل البيانات';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('خطأ!', message, 'error');
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'client_name', name: 'client_name'},
                    {data: 'worker_name', name: 'worker_name'},
                    {data: 'nationality', name: 'nationality'},
                    {data: 'city', name: 'city'},
                    {data: 'status', name: 'status', orderable: false, searchable: false},
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
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> جاري التحميل...');
                btn.prop('disabled', true);
                if (btn.attr('id') === 'clearFilters') {
                    $('#status').val('');
                    $('#nationality').val('');
                    $('#religion').val('');
                    $('#social_status').val('');
                }
                dataTable.ajax.reload(function() {
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                });
            });

            $(document).on('click', '.delete-order-request', function() {
                const orderId = $(this).data('id');
                const url = "{{ route('known-service-requests.destroy', ':id') }}".replace(':id', orderId);
                swal.fire({title: "{{ __('buttons.Are You Sure') }}", text: "{{ __('buttons.You Can not to rollback') }}", icon: 'question', showCancelButton: true, confirmButtonColor: '#DD6B55', confirmButtonText: "{{ __('buttons.accept') }}", cancelButtonText: "{{ __('buttons.cancel') }}"}).then((result) => {
                    if (!result.isConfirmed) return;
                    $.ajax({url: url, type: 'DELETE', data: {_token: '{{ csrf_token() }}'}, success: function(response) { if (response.data.success) { dataTable.ajax.reload(); swal.fire({title: 'تم بنجاح', text: 'تم حذف الطلب بنجاح', icon: 'success'}); } }, error: function() { swal.fire({title: 'خطأ', text: 'تعذر حذف الطلب', icon: 'error'}); }});
                });
            });

            $(document).on('click', '.accept-request', function() { confirmAction($(this).data('id'), 'approved', 'سيتم قبول الطلب', 'تم قبول الطلب بنجاح'); });
            $(document).on('click', '.reject-request', function() { confirmAction($(this).data('id'), 'rejected', 'سيتم رفض الطلب', 'تم رفض الطلب بنجاح'); });

            function confirmAction(id, status, confirmText, successText) {
                Swal.fire({title: 'هل أنت متأكد؟', text: confirmText, icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'نعم', cancelButtonText: 'لا'}).then((result) => {
                    if (!result.isConfirmed) return;
                    $.ajax({url: "{{ route('known-service-requests.update', ':id') }}".replace(':id', id), type: 'PUT', data: {_token: '{{ csrf_token() }}', status: status}, success: function() { Swal.fire({icon: 'success', title: 'تم بنجاح', text: successText}); dataTable.ajax.reload(); }, error: function(errorObj) { handleErrorResponce(errorObj); }});
                });
            }
        });
    </script>
@endsection
