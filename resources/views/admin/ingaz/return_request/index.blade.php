@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <style>
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table thead th {
            color: rgb(0, 0, 0);
            border: none;
            font-weight: 600;
            text-align: center;
            padding: 15px 10px;
        }
        .table tbody tr {
            transition: all 0.3s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .badge {
            font-size: 0.75em;
            padding: 6px 12px;
            border-radius: 20px;
        }
        .btn-group-sm .btn {
            margin: 2px;
            border-radius: 6px;
        }
        .order-code {
            font-weight: bold;
            color: #495057;
            font-family: 'Courier New', monospace;
        }
        .client-name {
            font-weight: 500;
            color: #212529;
        }
        .phone-number {
            direction: ltr;
            font-family: 'Courier New', monospace;
        }
        .maid-image {
            border-radius: 50%;
            border: 2px solid #dee2e6;
        }
        .status-badge {
            min-width: 80px;
            text-align: center;
        }
        .actions-column {
            min-width: 120px;
        }
        .filter-card {
            border-radius: 10px;
        }
        .filter-card .card-body {
            padding: 20px;
        }
        .filter-card .form-label {
            color: white;
            font-weight: 600;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-filter {
            border: 1px solid #000000;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
        }
        .btn-reset {
            border: 1px solid #000000;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.cancelled_orders")}}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_VIEW))

    {{-- <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="refused-count">0</h4>
                            <small>{{helperTrans("ingaz.refused")}}</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="return-count">0</h4>
                            <small>{{helperTrans("ingaz.return")}}</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-undo fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="deleted-count">0</h4>
                            <small>{{helperTrans("ingaz.deleted")}}</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-trash fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="total-count">0</h4>
                            <small>{{helperTrans("ingaz.total")}}</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fa fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Filter Form -->
    <div class="card mb-4 filter-card">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.status")}}</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">{{helperTrans("ingaz.all")}}</option>
                        <option value="refused">{{helperTrans("ingaz.refused")}}</option>
                        <option value="return">{{helperTrans("ingaz.return")}}</option>
                        <option value="deleted">{{helperTrans("ingaz.deleted")}}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.from_date")}}</label>
                    <input type="date" class="form-control" name="from_date" id="from_date">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{helperTrans("ingaz.to_date")}}</label>
                    <input type="date" class="form-control" name="to_date" id="to_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{helperTrans("ingaz.client_name")}}</label>
                    <input type="text" class="form-control" name="client_name" id="client_name" placeholder="{{helperTrans('ingaz.search_by_client_name')}}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-filter flex-fill">
                            <i class="fa fa-filter"></i> {{helperTrans("ingaz.filter")}}
                        </button>
                        <button type="button" class="btn btn-reset" id="resetFilter">
                            <i class="fa fa-refresh"></i> {{helperTrans("ingaz.reset")}}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table id="dataTableObject" class="table table-hover" style="width:100%">
        <thead>
        <tr>
            <th>#</th>
                <th>{{helperTrans("ingaz.order_code")}}</th>
            <th>{{helperTrans("ingaz.client")}}</th>
            <th>{{helperTrans("ingaz.phone")}}</th>
            <th>{{helperTrans("ingaz.maid")}}</th>
            <th>{{helperTrans("ingaz.status")}}</th>
                <th>{{helperTrans("ingaz.order_date")}}</th>
                <th>{{helperTrans("ingaz.responsible_employee")}}</th>
                <th class="actions-column">{{helperTrans("ingaz.actions")}}</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>

@else
    @include('components.no_permissions')
@endif

@endsection
@section('js')

    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "order_code", orderable: true, searchable: true},
                {"data": "user_id", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "maid_id", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: true},
                {"data": "created_at", orderable: true, searchable: true},
                {"data": "admin_name", orderable: true, searchable: true},
                {"data": "actions", orderable: false, searchable: false},
            ];

            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('return_requests.index') }}",
                    data: function(d) {
                        d.status = $('#status').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.client_name = $('#client_name').val();
                    }
                },
                columns: columns,
                language: {
                    url: "{{ asset('admin/assets/js/dataTables.arabic.json') }}",
                    search: "{{helperTrans('ingaz.search')}}",
                    lengthMenu: "{{helperTrans('ingaz.show')}} _MENU_ {{helperTrans('ingaz.records')}}",
                    info: "{{helperTrans('ingaz.showing')}} _START_ {{helperTrans('ingaz.to')}} _END_ {{helperTrans('ingaz.of')}} _TOTAL_ {{helperTrans('ingaz.records')}}",
                    infoEmpty: "{{helperTrans('ingaz.no_records')}}",
                    infoFiltered: "({{helperTrans('ingaz.filtered_from')}} _MAX_ {{helperTrans('ingaz.records')}})",
                    zeroRecords: "{{helperTrans('ingaz.no_matching_records')}}",
                    paginate: {
                        first: "{{helperTrans('ingaz.first')}}",
                        last: "{{helperTrans('ingaz.last')}}",
                        next: "{{helperTrans('ingaz.next')}}",
                        previous: "{{helperTrans('ingaz.previous')}}"
                    }
                }
            });

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload();
            });

            // Handle reset button
            $('#resetFilter').on('click', function() {
                $('#filterForm')[0].reset();
                dataTable.ajax.reload();
            });

            // Update statistics
            function updateStatistics() {
                $.ajax({
                    url: '{{ route("return_requests.index") }}',
                    type: 'GET',
                    data: {
                        ajax: true,
                        statistics: true
                    },
                    success: function(response) {
                        if (response.statistics) {
                            $('#refused-count').text(response.statistics.refused || 0);
                            $('#return-count').text(response.statistics.return || 0);
                            $('#deleted-count').text(response.statistics.deleted || 0);
                            $('#total-count').text(response.statistics.total || 0);
                        }
                    }
                });
            }

            // Update statistics on page load
            updateStatistics();

            // Update statistics after data table reload
            dataTable.on('draw', function() {
                updateStatistics();
            });

            // Handle restore order
            $(document).on('click', '.restore-order', function() {
                const orderId = $(this).data('id');
                if (confirm('{{helperTrans("ingaz.restore_confirm")}}')) {
                    $.ajax({
                        url: '/admin/orders/' + orderId + '/restore',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                dataTable.ajax.reload();
                                toastr.success('{{helperTrans("ingaz.order_restored")}}');
                            } else {
                                toastr.error('{{helperTrans("ingaz.restore_error")}}');
                            }
                        },
                        error: function() {
                            toastr.error('{{helperTrans("ingaz.restore_error")}}');
                        }
                    });
                }
            });

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
        });

        $(document).on('click', '.update-status', function (event) {
            var rowId = $(this).data('id');
            var status = $(this).data('value');
            var url = "{{ route('return_requests.show', ':id') }}";
            url = url.replace(':id', rowId);
            $.ajax({
                type: 'Get',
                url: url,
                data: {status:status},
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تغيير الحالة بنجاح!',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    }
                    else {
                        alert('فشل في تحديث الحالة.');
                    }
                }
            });
        });

    </script>
@endsection
