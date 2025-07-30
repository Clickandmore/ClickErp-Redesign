@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
@endsection

@section('content')
@if(auth()->guard('admin')->user()->can('sponsor_transfer_view'))
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-exchange-alt text-primary me-2"></i>
                {{helperTrans("ingaz.sponsorship_transfer_requests")}}
            </h1>
            <a href="{{ route('SponsorshipTransfer.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                {{helperTrans("ingaz.add_sponsorship_transfer_request")}}
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card statistics-card pending-card shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">
                                    {{helperTrans("ingaz.pending_requests")}}
                                </div>
                                <div class="h5 mb-0 font-weight-bold">
                                    {{ $pendingCount ?? 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card statistics-card trial-card shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">
                                    {{helperTrans("ingaz.trial_period")}}
                                </div>
                                <div class="h5 mb-0 font-weight-bold">
                                    {{ $trialCount ?? 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card statistics-card transferred-card shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">
                                    {{helperTrans("ingaz.transferred_successfully")}}
                                </div>
                                <div class="h5 mb-0 font-weight-bold">
                                    {{ $transferredCount ?? 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card statistics-card failed-card shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">
                                    {{helperTrans("ingaz.failed_trial")}}
                                </div>
                                <div class="h5 mb-0 font-weight-bold">
                                    {{ $failedCount ?? 0 }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter me-2"></i>
                    {{helperTrans("ingaz.filters")}}
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="collapse" id="filtersCollapse">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">{{helperTrans("ingaz.status")}}</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">{{helperTrans("ingaz.all_statuses")}}</option>
                                <option value="pending">{{helperTrans("ingaz.pending")}}</option>
                                <option value="approved">{{helperTrans("ingaz.approved")}}</option>
                                <option value="rejected">{{helperTrans("ingaz.rejected")}}</option>
                                <option value="completed">{{helperTrans("ingaz.completed")}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFromFilter" class="form-label">{{helperTrans("ingaz.date_from")}}</label>
                            <input type="date" class="form-control" id="dateFromFilter">
                        </div>
                        <div class="col-md-3">
                            <label for="dateToFilter" class="form-label">{{helperTrans("ingaz.date_to")}}</label>
                            <input type="date" class="form-control" id="dateToFilter">
                        </div>
                        <div class="col-md-3">
                            <label for="contractNumberFilter" class="form-label">{{helperTrans("ingaz.contract_number")}}</label>
                            <input type="text" class="form-control" id="contractNumberFilter" placeholder="{{helperTrans("ingaz.search_contract_number")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="oldSponsorFilter" class="form-label">{{helperTrans("ingaz.old_sponsor")}}</label>
                            <input type="text" class="form-control" id="oldSponsorFilter" placeholder="{{helperTrans("ingaz.search_old_sponsor")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="newSponsorFilter" class="form-label">{{helperTrans("ingaz.new_sponsor")}}</label>
                            <input type="text" class="form-control" id="newSponsorFilter" placeholder="{{helperTrans("ingaz.search_new_sponsor")}}">
                        </div>
                        <div class="col-md-3">
                            <label for="trialDaysFilter" class="form-label">{{helperTrans("ingaz.trial_days")}}</label>
                            <select class="form-select" id="trialDaysFilter">
                                <option value="">{{helperTrans("ingaz.all_trial_days")}}</option>
                                <option value="30">30 {{helperTrans("ingaz.days")}}</option>
                                <option value="60">60 {{helperTrans("ingaz.days")}}</option>
                                <option value="90">90 {{helperTrans("ingaz.days")}}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="amountFilter" class="form-label">{{helperTrans("ingaz.contract_value")}}</label>
                            <select class="form-select" id="amountFilter">
                                <option value="">{{helperTrans("ingaz.all_amounts")}}</option>
                                <option value="0-5000">0 - 5,000 ريال</option>
                                <option value="5000-10000">5,000 - 10,000 ريال</option>
                                <option value="10000-15000">10,000 - 15,000 ريال</option>
                                <option value="15000+">15,000+ ريال</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" id="clearFiltersBtn">
                                <i class="fas fa-times me-2"></i>
                                {{helperTrans("ingaz.clear_filters")}}
                            </button>
                            <button type="button" class="btn btn-primary" id="searchBtn">
                                <i class="fas fa-search me-2"></i>
                                {{helperTrans("ingaz.search")}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    {{helperTrans("ingaz.sponsorship_transfer_requests_list")}}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="sponsorshipTransferTable" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{helperTrans("ingaz.contract_number")}}</th>
                                <th>{{helperTrans("ingaz.old_sponsor")}}</th>
                                <th>{{helperTrans("ingaz.new_sponsor")}}</th>
                                <th>بيانات العاملة</th>
                                <th>{{helperTrans("ingaz.trial_days")}}</th>
                                <th>{{helperTrans("ingaz.trial_start_date")}}</th>
                                <th>نهاية الفترة التجريبية</th>
                                <th>{{helperTrans("ingaz.contract_value")}}</th>
                                <th>{{helperTrans("ingaz.paid_amount")}}</th>
                                <th>{{helperTrans("ingaz.remaining_amount")}}</th>
                                <th>{{helperTrans("ingaz.status")}}</th>
                                <th>{{helperTrans("ingaz.created_at")}}</th>
                                <th>{{helperTrans("ingaz.actions")}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تأكيد الإجراءات -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionModalLabel">تأكيد الإجراء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="confirmMessage">هل أنت متأكد من تنفيذ هذا الإجراء؟</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">
                        <i class="fas fa-check me-1"></i>تأكيد
                    </button>
                </div>
            </div>
        </div>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script>
        $(document).ready(function() {
            // إعداد DataTable
            var dataTable = $('#sponsorshipTransferTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: '{{ route("SponsorshipTransfer.index") }}',
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.old_sponsor = $('#oldSponsorFilter').val();
                        d.new_sponsor = $('#newSponsorFilter').val();
                        d.trial_days = $('#trialDaysFilter').val();
                        d.amount = $('#amountFilter').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'contract_number', name: 'contract_number', orderable: false, searchable: false},
                    {data: 'old_sponsor', name: 'old_sponsor', orderable: false, searchable: false},
                    {data: 'new_sponsor', name: 'new_sponsor', orderable: false, searchable: false},
                    {data: 'maid_info', name: 'maid_info', orderable: false, searchable: false},
                    {data: 'trial_days', name: 'trial_days', orderable: true, searchable: false},
                    {data: 'trial_start_date', name: 'trial_start_date', orderable: true, searchable: false},
                    {data: 'trial_end_info', name: 'trial_end_info', orderable: false, searchable: false},
                    {data: 'contract_value', name: 'contract_value', orderable: true, searchable: false},
                    {data: 'paid_amount', name: 'paid_amount', orderable: true, searchable: false},
                    {data: 'remaining_amount', name: 'remaining_amount', orderable: true, searchable: false},
                    {data: 'status', name: 'status', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at', orderable: true, searchable: false},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
                },
                responsive: true,
                order: [[12, 'desc']] // ترتيب حسب تاريخ الإنشاء
            });

            // البحث عند الضغط على زر البحث
            $('#searchBtn').click(function() {
                dataTable.draw();
            });

            // مسح الفلاتر
            $('#clearFiltersBtn').click(function() {
                $('#statusFilter').val('');
                $('#dateFromFilter').val('');
                $('#dateToFilter').val('');
                $('#contractNumberFilter').val('');
                $('#oldSponsorFilter').val('');
                $('#newSponsorFilter').val('');
                $('#trialDaysFilter').val('');
                $('#amountFilter').val('');
                dataTable.draw();
            });

            // Handle confirm actions (using SweetAlert2)
            $(document).on('click', '.confirm-action', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                const id = $(this).data('id');
                const text = $(this).data('text');

                Swal.fire({
                    title: 'تأكيد العملية',
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performAction(action, id);
                    }
                });
            });

            function performAction(action, id) {
                let url = `/admin/SponsorshipTransfer/${id}/${action}`;
                let method = 'POST';

                if (action === 'destroy') {
                    method = 'DELETE';
                    url = `/admin/SponsorshipTransfer/${id}`;
                }

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('نجح!', response.message, 'success').then(() => {
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire('خطأ!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ غير متوقع', 'error');
                    }
                });
            }
        });
    </script>
@endsection
