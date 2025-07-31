@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="{{ asset('admin/custom/admin-tables.css') }}" rel="stylesheet" type="text/css" />

@endsection
@section('page-title')
{{helperTrans("ingaz.orders")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
    <div class="page-body">
        <div class="container-fluid">
           <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-exchange-alt text-primary me-2"></i>
                {{helperTrans("ingaz.orders_list")}}
            </h3>
            <div class="export-buttons text-end">
                @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EXPORT))
                <a href="{{ route('orders.export.pdf') }}" class="btn btn-outline-danger btn-sm" target="_blank">
                    <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
                </a>
                <a href="{{ route('orders.export.excel') }}" class="btn btn-outline-success btn-sm">
                    <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
                </a>
                @endif
            </div>
        </div>


            <!-- Filter Form -->
            <div class="card mb-4 filter-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-filter me-2"></i>
                        {{helperTrans("ingaz.filters")}}
                    </h6>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                        <i class="fas fa-chevron-down" id="filtersIcon"></i>
                    </button>
                </div>
                <div class="card-body collapse" id="filtersCollapse">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.from_date")}}</label>
                            <input type="date" class="form-control" name="from_date" id="from_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.to_date")}}</label>
                            <input type="date" class="form-control" name="to_date" id="to_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.status")}}</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">{{helperTrans("ingaz.all")}}</option>
                                @foreach(\App\Enums\ingaz\OrderEnum::cases() as $status)
                                    <option value="{{ $status->value }}">{{ getEnumValue('orders_statuses', $status->value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.job_type")}}</label>
                            <select class="form-select" name="job_type" id="job_type">
                                <option value="">{{helperTrans("ingaz.all")}}</option>
                                @foreach(\App\Models\ingaz\JobType::all() as $jobType)
                                    <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.responsible_employee")}}</label>
                            <select class="form-select" name="admin_id" id="admin_id">
                                <option value="">{{helperTrans("ingaz.all")}}</option>
                                @foreach(\App\Models\auth\Admin::all() as $admin)
                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{helperTrans("ingaz.timer_status")}}</label>
                            <select class="form-select" name="timer_status" id="timer_status">
                                <option value="">{{helperTrans("ingaz.all")}}</option>
                                <option value="normal">{{helperTrans("ingaz.normal")}}</option>
                                <option value="urgent">{{helperTrans("ingaz.urgent")}}</option>
                                <option value="overdue">{{helperTrans("ingaz.overdue")}}</option>
                            </select>
                        </div>
                        <!-- Search Fields -->
                        <div class="col-md-4">
                            <label class="form-label">{{helperTrans("ingaz.client_name")}}</label>
                            <input type="text" class="form-control" name="client_name" id="client_name" placeholder="{{helperTrans('ingaz.search_by_client_name')}}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{helperTrans("ingaz.phone")}}</label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="{{helperTrans('ingaz.search_by_phone')}}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{helperTrans("ingaz.passport_num")}}</label>
                            <input type="text" class="form-control" name="passport_num" id="passport_num" placeholder="{{helperTrans('ingaz.search_by_passport')}}">
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa fa-filter"></i> {{helperTrans("ingaz.filter")}}
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="resetFilter">
                                    <i class="fa fa-refresh"></i> {{helperTrans("ingaz.reset")}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-list"></i> {{helperTrans("ingaz.orders_list")}}
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="dataTableObject" class="table table-hover align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{helperTrans("ingaz.order_code")}}</th>
                                    <th>{{helperTrans("ingaz.client")}}</th>
                                    <th>{{helperTrans("ingaz.phone")}}</th>
                                    <th>{{helperTrans("ingaz.nationality")}}</th>
                                    <th>{{helperTrans("ingaz.passport_num")}}</th>
                                    {{-- <th>{{helperTrans("ingaz.maid")}}</th> --}}
                                    {{-- <th>{{helperTrans("ingaz.deliver_status")}}</th> --}}
                                    <th>{{helperTrans("ingaz.order_job_type")}}</th>
                                    <th>{{helperTrans("ingaz.status")}}</th>
                                    <th>{{helperTrans("ingaz.timer")}}</th>
                                    <th>{{helperTrans("ingaz.contract_status")}}</th>
                                    <th>{{helperTrans("ingaz.order_date")}}</th>
                                    <th>{{helperTrans("ingaz.responsible_employee")}}</th>
                                    <th class="text-center">{{helperTrans("ingaz.actions")}}</th>
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

@section('js')
<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{asset("admin")}}/custom/admin-tables.js"></script>

<script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script>
$(document).ready(function () {
    let columns = [
        { "data": 'id', name: 'id', orderable: false, searchable: false },
        { "data": "order_code", orderable: true, searchable: true },
        { "data": "user_id", orderable: true, searchable: true },
        { "data": "phone", orderable: true, searchable: true },
        { "data": "nationality_name", orderable: true, searchable: true, className: 'd-none d-md-table-cell' },
        { "data": "maid_passport_num", orderable: true, searchable: true },
        { "data": "maid_id", orderable: false, searchable: false },
        { "data": "deliver_status", orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
        { "data": "job_type_id", orderable: true, searchable: true },
        { "data": "status", orderable: true, searchable: true },
        { "data": "timer", orderable: false, searchable: false, className: 'd-none d-lg-table-cell' },
        { "data": "contract_status", orderable: true, searchable: true },
        { "data": "created_at", orderable: true, searchable: true, className: 'd-none d-xl-table-cell' },
        { "data": "admin_name", orderable: true, searchable: true, className: 'd-none d-xl-table-cell' },
        { "data": "actions", orderable: false, searchable: false, className: 'text-center' }
    ];

    // Get URL parameters
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    let dataTable = $('#dataTableObject').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        ajax: {
            url: "{{ route('orders.index') }}",
            data: function(d) {
                d.from_date = $('#from_date').val();
                d.to_date = $('#to_date').val();
                d.status = $('#status').val() || getUrlParameter('status');
                d.job_type = $('#job_type').val();
                d.admin_id = $('#admin_id').val();
                d.timer_status = $('#timer_status').val();
                d.client_name = $('#client_name').val();
                d.phone = $('#phone').val();
                d.passport_num = $('#passport_num').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error);
                console.error('XHR:', xhr);
                console.error('Thrown:', thrown);
            }
        },
        columns: columns,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json",
            processing: '<div class="text-primary">جاري التحميل...</div>'
        },
        drawCallback: function() {
            // admin-tables.js يتولى معالجة القوائم المنسدلة
        },
        scrollX: true,
        scrollCollapse: true,
        fixedColumns: {
            leftColumns: 0,
            rightColumns: 1
        }
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        dataTable.ajax.reload();
    });

    // Set form values from URL parameters on page load
    var urlStatus = getUrlParameter('status');
    if (urlStatus) {
        $('#status').val(urlStatus);
    }

    // Handle reset button
    $('#resetFilter').on('click', function() {
        $('#filterForm')[0].reset();
        dataTable.ajax.reload();
    });

    // Update timer every minute
    setInterval(function() {
        dataTable.ajax.reload(null, false); // false to maintain pagination
    }, 60000); // 60 seconds

    // Handle delete order
    $(document).on('click', '.delete-order', function() {
        const orderId = $(this).data('id');
        if (confirm('{{helperTrans("ingaz.delete_confirm")}}')) {
            $.ajax({
                url: '/admin/orders/' + orderId,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        dataTable.ajax.reload();
                        toastr.success('{{helperTrans("ingaz.order_deleted")}}');
                    } else {
                        toastr.error('{{helperTrans("ingaz.delete_error")}}');
                    }
                },
                error: function() {
                    toastr.error('{{helperTrans("ingaz.delete_error")}}');
                }
            });
        }
    });

    // Handle edit status
    $(document).on('click', '.edit-status', function() {
        const orderId = $(this).data('id');
        console.log('Edit status clicked for order:', orderId);

        // Show loading
        const loadingModal = `
            <div class="modal fade" id="loadingModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-body text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">جاري التحميل...</span>
                            </div>
                            <p class="mt-2">جاري تحميل نموذج التعديل...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modals
        $('#loadingModal').remove();
        $('#editStatusModal').remove();

        $('body').append(loadingModal);
        $('#loadingModal').modal('show');

        // Show modal with edit form
        $.ajax({
            url: '/admin/orders/' + orderId + '/edit',
            type: 'GET',
            beforeSend: function() {
                console.log('Sending AJAX request to:', '/admin/orders/' + orderId + '/edit');
            },
            success: function(response) {
                console.log('AJAX response:', response);
                $('#loadingModal').modal('hide');
                $('#loadingModal').remove();

                if (response.success) {
                    // Create modal
                    const modal = `
                        <div class="modal fade" id="editStatusModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{helperTrans("ingaz.edit_order_status")}}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        ${response.html}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Remove existing modal if any
                    $('#editStatusModal').remove();

                    // Add modal to body
                    $('body').append(modal);

                    // Show modal
                    $('#editStatusModal').modal('show');

                    // Handle form submission
                    $('#editStatusModal form').on('submit', function(e) {
                        e.preventDefault();
                        console.log('Form submitted');
                        const formData = new FormData(this);

                        // Log form data
                        for (let pair of formData.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }

                        $.ajax({
                            url: $(this).attr('action'),
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                console.log('Update response:', response);
                                if (response.success) {
                                    $('#editStatusModal').modal('hide');
                                    $('.modal-backdrop').remove();
                                    $('body').removeClass('modal-open');
                                    dataTable.ajax.reload();
                                    if (typeof toastr !== 'undefined') {
                                        toastr.success('{{helperTrans("ingaz.status_updated")}}');
                                    } else {
                                        alert('{{helperTrans("ingaz.status_updated")}}');
                                    }
                                } else {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error(response.message || '{{helperTrans("ingaz.status_update_error")}}');
                                    } else {
                                        alert(response.message || '{{helperTrans("ingaz.status_update_error")}}');
                                    }
                                }
                            },
                            error: function(xhr) {
                                console.log('Update error:', xhr);
                                let errorMessage = '{{helperTrans("ingaz.status_update_error")}}';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(errorMessage);
                                } else {
                                    alert(errorMessage);
                                }
                            }
                        });
                    });

                    // Handle modal close button
                    $('#editStatusModal .btn-close, #editStatusModal [data-bs-dismiss="modal"]').on('click', function() {
                        $('#editStatusModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    });

                    // Handle modal hidden event
                    $('#editStatusModal').on('hidden.bs.modal', function() {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    });
                } else {
                    console.log('Response not successful:', response);
                    let errorMessage = '{{helperTrans("ingaz.load_form_error")}}';
                    if (response.message) {
                        errorMessage = response.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            },
            error: function(xhr) {
                console.log('AJAX error:', xhr);
                $('#loadingModal').modal('hide');
                $('#loadingModal').remove();

                let errorMessage = '{{helperTrans("ingaz.load_form_error")}}';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = 'الطلب غير موجود';
                } else if (xhr.status === 500) {
                    errorMessage = 'خطأ في الخادم';
                } else if (xhr.status === 0) {
                    errorMessage = 'فشل في الاتصال بالخادم';
                }

                toastr.error(errorMessage);
                console.error('Error details:', xhr);
            }
        });
    });

    // Handle change maid with search functionality
    $(document).on('click', '.change-maid', function() {
        const orderId = $(this).data('id');

        // Show modal to select new maid with search
        const modal = `
            <div class="modal fade" id="changeMaidModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{helperTrans("ingaz.change_maid")}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeMaidForm">
                                <div class="mb-3">
                                    <label class="form-label">{{helperTrans("ingaz.search_maid")}}</label>
                                    <input type="text" class="form-control" id="maidSearch" placeholder="{{helperTrans('ingaz.search_by_name_or_passport')}}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{helperTrans("ingaz.select_maid")}}</label>
                                    <select class="form-select" name="maid_id" required>
                                        <option value="">{{helperTrans("ingaz.select_maid")}}</option>
                                    </select>
                                </div>
                                <div id="maidInfo" class="alert alert-info" style="display: none;">
                                    <h6>{{helperTrans("ingaz.maid_info")}}</h6>
                                    <div id="maidDetails"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{helperTrans("ingaz.cancel")}}</button>
                            <button type="button" class="btn btn-primary" id="saveMaid">{{helperTrans("ingaz.save")}}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#changeMaidModal').remove();
        $('body').append(modal);
        $('#changeMaidModal').modal('show');

        // Handle modal close for change maid modal
        $('#changeMaidModal .btn-close, #changeMaidModal [data-bs-dismiss="modal"]').on('click', function() {
            $('#changeMaidModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        $('#changeMaidModal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        // Load available maids
        loadAvailableMaids();

        // Handle search
        $('#maidSearch').on('input', function() {
            const searchTerm = $(this).val();
            if (searchTerm.length >= 2) {
                searchMaids(searchTerm);
            } else {
                loadAvailableMaids();
            }
        });

        // Handle maid selection
        $('#changeMaidForm select[name="maid_id"]').on('change', function() {
            const maidId = $(this).val();
            if (maidId) {
                showMaidInfo(maidId);
            } else {
                $('#maidInfo').hide();
            }
        });

        // Handle save
        $('#saveMaid').on('click', function() {
            const maidId = $('#changeMaidForm select[name="maid_id"]').val();
            if (!maidId) {
                toastr.error('{{helperTrans("ingaz.please_select_maid")}}');
                return;
            }

            $.ajax({
                url: '/admin/orders/' + orderId + '/change-maid',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    maid_id: maidId
                },
                success: function(response) {
                    if (response.success) {
                        $('#changeMaidModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        dataTable.ajax.reload();
                        toastr.success('{{helperTrans("ingaz.maid_updated")}}');
                    } else {
                        toastr.error(response.message || '{{helperTrans("ingaz.update_error")}}');
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('{{helperTrans("ingaz.update_error")}}');
                    }
                }
            });
        });
    });

    // Function to load available maids
    function loadAvailableMaids() {
        $.ajax({
            url: '/admin/maids/available',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#changeMaidForm select[name="maid_id"]');
                    select.empty();
                    select.append('<option value="">{{helperTrans("ingaz.select_maid")}}</option>');

                    response.maids.forEach(function(maid) {
                        select.append(`<option value="${maid.id}">${maid.name} - ${maid.passport_num} - ${maid.nationality}</option>`);
                    });
                }
            }
        });
    }

    // Function to search maids
    function searchMaids(searchTerm) {
        $.ajax({
            url: '/admin/maids/search',
            type: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                if (response.success) {
                    const select = $('#changeMaidForm select[name="maid_id"]');
                    select.empty();
                    select.append('<option value="">{{helperTrans("ingaz.select_maid")}}</option>');

                    response.maids.forEach(function(maid) {
                        select.append(`<option value="${maid.id}">${maid.name} - ${maid.passport_num} - ${maid.nationality}</option>`);
                    });
                }
            }
        });
    }

    // Function to show maid info
    function showMaidInfo(maidId) {
        $.ajax({
            url: '/admin/maids/' + maidId + '/info',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const maid = response.maid;
                    $('#maidDetails').html(`
                        <p><strong>{{helperTrans("ingaz.name")}}:</strong> ${maid.name}</p>
                        <p><strong>{{helperTrans("ingaz.passport_num")}}:</strong> ${maid.passport_num}</p>
                        <p><strong>{{helperTrans("ingaz.nationality")}}:</strong> ${maid.nationality}</p>
                        <p><strong>{{helperTrans("ingaz.age")}}:</strong> ${maid.age}</p>
                        <p><strong>{{helperTrans("ingaz.experience")}}:</strong> ${maid.experience}</p>
                    `);
                    $('#maidInfo').show();
                }
            }
        });
    }

    // Handle change responsible employee
    $(document).on('click', '.change-responsible', function() {
        const orderId = $(this).data('id');

        // Show modal to select new responsible employee
        const modal = `
            <div class="modal fade" id="changeResponsibleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{helperTrans("ingaz.change_responsible_employee")}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeResponsibleForm">
                                <div class="mb-3">
                                    <label class="form-label">{{helperTrans("ingaz.select_employee")}}</label>
                                    <select class="form-select" name="admin_id" required>
                                        <option value="">{{helperTrans("ingaz.select_employee")}}</option>
                                        @foreach(\App\Models\auth\Admin::where('admin_type', 'customer_service')->get() as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{helperTrans("ingaz.cancel")}}</button>
                            <button type="button" class="btn btn-primary" id="saveResponsible">{{helperTrans("ingaz.save")}}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#changeResponsibleModal').remove();
        $('body').append(modal);
        $('#changeResponsibleModal').modal('show');

        // Handle modal close for change responsible modal
        $('#changeResponsibleModal .btn-close, #changeResponsibleModal [data-bs-dismiss="modal"]').on('click', function() {
            $('#changeResponsibleModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        $('#changeResponsibleModal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        // Handle save
        $('#saveResponsible').on('click', function() {
            const adminId = $('#changeResponsibleForm select[name="admin_id"]').val();
            if (!adminId) {
                toastr.error('{{helperTrans("ingaz.please_select_employee")}}');
                return;
            }

            $.ajax({
                url: '/admin/orders/' + orderId + '/change-responsible',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    admin_id: adminId
                },
                success: function(response) {
                    if (response.success) {
                        $('#changeResponsibleModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        dataTable.ajax.reload();
                        toastr.success('{{helperTrans("ingaz.responsible_updated")}}');
                    } else {
                        toastr.error(response.message || '{{helperTrans("ingaz.update_error")}}');
                    }
                },
                error: function() {
                    toastr.error('{{helperTrans("ingaz.update_error")}}');
                }
            });
        });
    });

    // Handle refuse/return order
    $(document).on('click', '.refuse-order', function() {
        const orderId = $(this).data('id');

        const modal = `
            <div class="modal fade" id="refuseOrderModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{helperTrans("ingaz.refuse_return_order")}}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="refuseOrderForm">
                                <div class="mb-3">
                                    <label class="form-label">{{helperTrans("ingaz.select_action")}}</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="refused" id="refused">
                                        <label class="form-check-label" for="refused">
                                            {{helperTrans("ingaz.refuse_order")}}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="return" id="return">
                                        <label class="form-check-label" for="return">
                                            {{helperTrans("ingaz.return_order")}}
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{helperTrans("ingaz.reason")}}</label>
                                    <textarea class="form-control" name="reason" rows="3" placeholder="{{helperTrans('ingaz.enter_reason')}}"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{helperTrans("ingaz.cancel")}}</button>
                            <button type="button" class="btn btn-danger" id="saveRefuse">{{helperTrans("ingaz.confirm")}}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#refuseOrderModal').remove();
        $('body').append(modal);
        $('#refuseOrderModal').modal('show');

        // Handle modal close for refuse order modal
        $('#refuseOrderModal .btn-close, #refuseOrderModal [data-bs-dismiss="modal"]').on('click', function() {
            $('#refuseOrderModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        $('#refuseOrderModal').on('hidden.bs.modal', function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
        });

        // Handle save
        $('#saveRefuse').on('click', function() {
            const action = $('#refuseOrderForm input[name="action"]:checked').val();
            const reason = $('#refuseOrderForm textarea[name="reason"]').val();

            if (!action) {
                toastr.error('{{helperTrans("ingaz.please_select_action")}}');
                return;
            }

            $.ajax({
                url: '/admin/orders/' + orderId + '/refuse-return',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    action: action,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        $('#refuseOrderModal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        dataTable.ajax.reload();
                        toastr.success('{{helperTrans("ingaz.order_updated")}}');
                    } else {
                        toastr.error('{{helperTrans("ingaz.update_error")}}');
                    }
                },
                error: function() {
                    toastr.error('{{helperTrans("ingaz.update_error")}}');
                }
            });
        });
    });

    // Initialize tooltips
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).hover(
                function() {
                    const tooltip = $('<div class="custom-tooltip">' + $(this).attr('data-tooltip') + '</div>');
                    $('body').append(tooltip);

                    const offset = $(this).offset();
                    tooltip.css({
                        position: 'absolute',
                        top: offset.top - tooltip.outerHeight() - 10,
                        left: offset.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2),
                        zIndex: 9999,
                        backgroundColor: '#333',
                        color: 'white',
                        padding: '4px 8px',
                        borderRadius: '4px',
                        fontSize: '0.75rem',
                        whiteSpace: 'nowrap',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.2)'
                    });
                },
                function() {
                    $('.custom-tooltip').remove();
                }
            );
        });
    }

    // Initialize tooltips after DataTable loads
    dataTable.on('draw', function() {
        initTooltips();

        // Clean up dropdown text
        $('.dropdown-item').each(function() {
            let text = $(this).text();
            // Remove any repeated "الإجراءات" text
            text = text.replace(/الإجراءات/g, '').trim();
            // Remove any extra spaces
            text = text.replace(/\s+/g, ' ').trim();
            $(this).text(text);

            // Ensure only one icon per item
            $(this).find('i').slice(1).remove();
        });

        // Ensure all dropdowns are closed after DataTable redraw
        $('.dropdown').removeClass('show');
        $('.dropdown-menu').hide();
    });

    // Initialize tooltips on page load
    initTooltips();

    // Clean up dropdown text on page load
    // $('.dropdown-item').each(function() {
    //     let text = $(this).text();
    //     // Remove any repeated "الإجراءات" text
    //     text = text.replace(/الإجراءات/g, '').trim();
    //     // Remove any extra spaces
    //     text = text.replace(/\s+/g, ' ').trim();
    //     $(this).text(text);

    //     // Ensure only one icon per item
    //     $(this).find('i').slice(1).remove();
    // });

    // Ensure dropdowns are closed by default
    // $('.dropdown').removeClass('show');
    // $('.dropdown-menu').hide();

    // Force close all dropdowns on page load
    // setTimeout(function() {
    //     $('.dropdown').removeClass('show');
    //     $('.dropdown-menu').hide();
    //     $('.dropdown-toggle').attr('aria-expanded', 'false');
    // }, 100);

    // Handle dropdown toggle properly
    // $(document).on('click', '.dropdown-toggle', function(e) {
    //     e.preventDefault();
    //     e.stopPropagation();

    //     const $dropdown = $(this).closest('.dropdown');
    //     const $dropdownMenu = $dropdown.find('.dropdown-menu');

    //     // إغلاق جميع الدروب داون الأخرى
    //     $('.dropdown').not($dropdown).removeClass('show');
    //     $('.dropdown-menu').not($dropdownMenu).hide();

    //     // تبديل حالة الدروب داون الحالي
    //     $dropdown.toggleClass('show');
    //     $dropdownMenu.toggle();

    //     console.log('Dropdown clicked:', $dropdown.hasClass('show'));
    // });

    // Close dropdowns when clicking outside
    // $(document).on('click', function(e) {
    //     if (!$(e.target).closest('.dropdown').length) {
    //         $('.dropdown').removeClass('show');
    //         $('.dropdown-menu').hide();
    //     }
    // });

    // Prevent dropdown from showing on hover
    // $(document).on('mouseenter', '.table tbody tr', function() {
    //     // Ensure dropdowns in this row are closed
    //     $(this).find('.dropdown').removeClass('show');
    //     $(this).find('.dropdown-menu').hide();
    // });

    // Prevent dropdown from showing on hover of dropdown itself
    // $(document).on('mouseenter', '.dropdown-menu', function(e) {
    //     e.stopPropagation();
    // });

    // Ensure dropdowns are always closed except when explicitly clicked
    // $(document).on('mouseenter mouseleave', '.table tbody tr', function() {
    //     // Only allow dropdown to show if it was explicitly clicked
    //     if (!$(this).find('.dropdown').hasClass('show')) {
    //         $(this).find('.dropdown-menu').hide();
    //     }
    // });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#from_date, #to_date, #status, #job_type, #admin_id, #timer_status, #client_name, #phone, #passport_num').val('');
        dataTable.ajax.reload();
    });

    // Export buttons
    $('#export_excel').on('click', function() {
        window.location.href = '{{ route("orders.export.excel") }}?' + $.param({
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val(),
            status: $('#status').val() || getUrlParameter('status'),
            job_type: $('#job_type').val(),
            admin_id: $('#admin_id').val(),
            timer_status: $('#timer_status').val(),
            client_name: $('#client_name').val(),
            phone: $('#phone').val(),
            passport_num: $('#passport_num').val()
        });
    });

    $('#export_pdf').on('click', function() {
        window.location.href = '{{ route("orders.export.pdf") }}?' + $.param({
            from_date: $('#from_date').val(),
            to_date: $('#to_date').val(),
            status: $('#status').val() || getUrlParameter('status'),
            job_type: $('#job_type').val(),
            admin_id: $('#admin_id').val(),
            timer_status: $('#timer_status').val(),
            client_name: $('#client_name').val(),
            phone: $('#phone').val(),
            passport_num: $('#passport_num').val()
        });
    });
});
</script>
@endsection
