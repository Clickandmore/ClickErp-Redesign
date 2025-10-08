@extends('admin.layout.indexs.index')
@section("style")
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .instant-receipt-card {
            border-left: 4px solid #ffc107;
        }

        .instant-receipt-card .card-body {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }

        .instant-receipt-card .text-xs {
            color: #856404;
        }

        .instant-receipt-card .h5 {
            color: #856404;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.request_professional_labor")}}
@endsection
@section('content')
    <div class="page-body p-4 text-dark admin-tables">
{{--        @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))--}}
            <div class="container-fluid ">
                <!-- Page Title Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title"> {{helperTrans("ingaz.request_professional_labor")}}</h4>
                        </div>
                    </div>
                </div>
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card total-card shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ __("ingaz.total_requests") }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">{{ $data['count']  ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card wants-work-card shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ __("ingaz.total_approved_requests") }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">{{ $data['approvedCount']  ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card within-guarantee-card shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ __("ingaz.total_rejected_requests") }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">{{ $data['rejectedCount']  }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card expired-guarantee-card shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ __("ingaz.total_pending_requests") }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">{{ $data['pendingCount']  ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Filter Section -->
                <div class="card filter-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
                         data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <h6 class="m-0 font-weight-bold ">
                            <i class="fas fa-filter me-2"></i>{{helperTrans("admin.filters")}}
                        </h6>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="collapse" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="jobTitle" class="form-label">نوع العمالة</label>
                                    <select id="jobTitle" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach( $data['jobTitles'] as $jobTitle)
                                            <option value="{{ $jobTitle->id }}">{{ $jobTitle->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select id="status" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach($data['status'] as $state)
                                            <option value="{{ $state->value }}">
                                                {{ $state->lang() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end">
                                    <button type="button" id="clearFilters" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i> مسح الفلاتر
                                    </button>
                                    <button type="button" id="applyFilters" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> بحث
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول العقود -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-list me-2"></i>
                            {{helperTrans("ingaz.request_professional_labor")}}
                        </h6>
                    </div>
                    <div class="card-body">
                        <table id="shelterContractsTable" class="table table-hover align-middle mb-0"
                               style="width: 100%;">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{helperTrans("ingaz.facility_name")}}</th>
                                <th>{{helperTrans("ingaz.contact_number")}}</th>
                                <th>{{helperTrans("ingaz.type_of_labor_required")}}</th>
                                <th>{{helperTrans("ingaz.number_of_workers_required")}}</th>
                                <th>{{helperTrans("ingaz.visa_number")}}</th>
                                <th>{{helperTrans("ingaz.status")}}</th>
                                <th>{{helperTrans("ingaz.actions")}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
{{--        @else--}}
{{--            @include('components.no_permissions')--}}
{{--        @endif--}}
    </div>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/vendor/toster/js/jquery.toast.js"></script>
    <script>
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
        } else {
            console.log('jQuery version:', jQuery.fn.jquery);
        }
        // الانتظار حتى يتم تحميل الصفحة بالكامل
        window.addEventListener('load', function () {
            console.log('Window loaded - checking for DataTable');
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTable plugin is not loaded!');
                return;
            }
            console.log('DataTable plugin is loaded');
            // طباعة URL للتحقق
            const dataUrl = '{{ route("ShelterContract.data") }}';
            console.log('Data URL:', dataUrl);
            // Initialize DataTable
            const dataTable = $('#shelterContractsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                searching: false,
                ajax: {
                    url: '{{ route("professional_application.index") }}',
                    type: 'GET',
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function (d) {
                        console.log('Sending request with data:', d);
                        d.status = $('#status').val();
                        d.jobTitle = $('#jobTitle').val();
                    },
                    dataSrc: function (json) {
                        console.log('Received data:', json);
                        console.log('Data array:', json.data);
                        if (json.data && json.data.length > 0) {
                            console.log('First row columns:', Object.keys(json.data[0]));
                        }
                        return json.data;
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('XHR status:', xhr.status);
                        console.error('XHR response:', xhr.responseText);
                        console.error('Thrown error:', thrown);
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.error('Parsed error response:', response);
                            Swal.fire('خطأ!', response.message || 'حدث خطأ في تحميل البيانات', 'error');
                        } catch (e) {
                            console.error('Could not parse error response');
                            Swal.fire('خطأ!', 'حدث خطأ في تحميل البيانات', 'error');
                        }
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'facility_name', name: 'facility_name'},
                    {data: 'contact_number', name: 'contact_number'},
                    {data: 'type_of_labor_required', name: 'type_of_labor_required'},
                    {data: 'number_of_workers_required', name: 'number_of_workers_required', orderable: false},
                    {data: 'visa_number', name: 'visa_number', orderable: false},
                    {data: 'status', name: 'status', orderable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                createdRow: function (row, data, dataIndex) {
                    console.log('Row created with data:', data);
                },
                drawCallback: function (settings) {
                    var api = this.api();
                    console.log('Table drawn. Total rows:', api.rows().count());
                    console.log('Displayed rows:', api.rows({page: 'current'}).count());
                },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                },
                order: [[1, 'desc']],
                pageLength: 25,
                dom: 'lrtip'
            });
            $('#applyFilters, #clearFilters').on('click', function () {
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i> جاري التحميل...');
                btn.prop('disabled', true);
                if (btn.attr('id') === 'applyFilters') {
                    dataTable.ajax.reload(function () {
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                    });
                } else {
                    $('#jobTitle').val('');
                    $('#status').val('');
                    dataTable.ajax.reload(function () {
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                    });
                }
            });
            $(document).on('click', '.delete-order-request', function () {
                const orderId = $(this).data('id');
                var url = "{{route('professional_application.destroy','orderId')}}".replace('orderId', orderId);
                swal.fire({
                    title: "{{__('buttons.Are You Sure')}}",
                    text: "{{__('buttons.You Can not to rollback')}}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "{{__('buttons.accept')}}",
                    cancelButtonText: "{{__('buttons.cancel')}}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.data.success) {
                                    dataTable.ajax.reload();
                                    swal.fire({
                                        title: "{{ __('ingaz.success') }}",
                                        text: "{{helperTrans("ingaz.order_deleted")}}",
                                        icon: 'success',
                                    });
                                }
                            },
                            error: function () {
                                swal.fire({
                                    title: "{{ __('ingaz.error') }}",
                                    text: "{{helperTrans("ingaz.delete_error")}}",
                                    icon: 'error',

                                });
                            }
                        });
                    }
                });
            });
            $(document).ready(function () {
                $(document).on("click", ".accept-request", function () {
                    let id = $(this).data("id");
                    confirmAction(id, 'approved', "{{ __('ingaz.confirm_accept') }}", "{{ __('ingaz.approved') }}");
                });
                $(document).on("click", ".reject-request", function () {
                    let id = $(this).data("id");
                    confirmAction(id, 'rejected', "{{ __('ingaz.confirm_reject') }}", "{{ __('ingaz.rejected') }}");
                });

                function confirmAction(id, status, confirmText, successText) {
                    Swal.fire({
                        title: "{{ __('ingaz.are_you_sure') }}",
                        text: confirmText,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "{{ __('ingaz.yes') }}",
                        cancelButtonText: "{{ __('ingaz.no') }}"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateSubscriptionStatus(id, status, successText);
                        }
                    });
                }

                function updateSubscriptionStatus(id, status, successText) {
                    $.ajax({
                        url: "{{ route('professional_application.update', ':id') }}".replace(":id", id),
                        type: "put",
                        data: {
                            _token: "{{ csrf_token() }}",
                            status: status,
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: "success",
                                title: "{{ __('ingaz.success') }}",
                                text: successText,
                            });
                            dataTable.ajax.reload();
                        },
                        error: function (errorObj, errorText, errorThrown) {
                            console.log(500)
                            console.log(errorObj)
                            handleErrorResponce(errorObj);
                        }
                    });

                }
            });
        });
    </script>
@endsection
