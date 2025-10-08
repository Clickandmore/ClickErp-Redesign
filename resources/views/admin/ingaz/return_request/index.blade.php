@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/custom/table-actions.css">
    <style>
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-filter {
            border: 1px solid #000000;
            border-radius: 8px;
            color: white;
            font-weight: 600;
        }

        .btn-reset {
            border: 1px solid #000000;
            border-radius: 8px;
            color: white;
            font-weight: 600;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.cancelled_orders")}}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_VIEW))

        <!-- Export Buttons -->
        <div class="export-buttons mb-3">
            @if (@AdminGuardHasPerm(\App\PermKeys::RETURN_REQUEST_EXPORT))
                <a href="{{ route('return_requests.export.pdf') }}" class="btn btn-outline-danger" target="_blank">
                    <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
                </a>
                <a href="{{ route('return_requests.export.excel') }}" class="btn btn-outline-success">
                    <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
                </a>
            @endif
        </div>

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
                        <input type="text" class="form-control" name="client_name" id="client_name"
                               placeholder="{{helperTrans('ingaz.search_by_client_name')}}">
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
                 <div class="card shadow">
            <div class="card-body">
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
                 </div>
    @else
        @include('components.no_permissions')
    @endif

@endsection
@section('js')

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
                    data: function (d) {
                        d.status = $('#status').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.client_name = $('#client_name').val();
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', error);
                        console.error('XHR:', xhr);
                        console.error('Thrown:', thrown);
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
            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                dataTable.ajax.reload();
            });

            // Handle reset button
            $('#resetFilter').on('click', function () {
                $('#filterForm')[0].reset();
                dataTable.ajax.reload();
            });

            // Handle restore order
            $(document).on('click', '.restore-order', function () {
                const orderId = $(this).data('id');
                if (confirm('{{helperTrans("ingaz.restore_confirm")}}')) {
                    $.ajax({
                        url: '/admin/orders/' + orderId + '/restore',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.data.success) {
                                dataTable.ajax.reload();
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('{{helperTrans("ingaz.order_restored")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.order_restored")}}');
                                }
                            } else {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('{{helperTrans("ingaz.restore_error")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.restore_error")}}');
                                }
                            }
                        },
                        error: function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{helperTrans("ingaz.restore_error")}}');
                            } else {
                                alert('{{helperTrans("ingaz.restore_error")}}');
                            }
                        }
                    });
                }
            });

            // Handle delete order
            $(document).on('click', '.delete-order', function () {
                const orderId = $(this).data('id');
                if (confirm('{{helperTrans("ingaz.delete_confirm")}}')) {
                    $.ajax({
                        url: '/admin/orders/' + orderId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.data.success) {
                                dataTable.ajax.reload();
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('{{helperTrans("ingaz.order_deleted")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.order_deleted")}}');
                                }
                            } else {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('{{helperTrans("ingaz.delete_error")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.delete_error")}}');
                                }
                            }
                        },
                        error: function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{helperTrans("ingaz.delete_error")}}');
                            } else {
                                alert('{{helperTrans("ingaz.delete_error")}}');
                            }
                        }
                    });
                }
            });
            $(document).on('click', '.force-delete-order', function () {
                const orderId = $(this).data('id');
                if (confirm('{{helperTrans("ingaz.delete_confirm")}}')) {
                    $.ajax({
                        url: '/admin/orders/' + orderId,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.data.success) {
                                dataTable.ajax.reload();
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('{{helperTrans("ingaz.order_deleted")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.order_deleted")}}');
                                }
                            } else {
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('{{helperTrans("ingaz.delete_error")}}');
                                } else {
                                    alert('{{helperTrans("ingaz.delete_error")}}');
                                }
                            }
                        },
                        error: function () {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{helperTrans("ingaz.delete_error")}}');
                            } else {
                                alert('{{helperTrans("ingaz.delete_error")}}');
                            }
                        }
                    });
                }
            });

            // Handle status update
            $(document).on('click', '.update-status', function (event) {
                var rowId = $(this).data('id');
                var status = $(this).data('value');
                var url = "{{ route('return_requests.show', ':id') }}";
                url = url.replace(':id', rowId);
                $.ajax({
                    type: 'Get',
                    url: url,
                    data: {status: status},
                    success: function (data) {
                        if (data.success) {
                            dataTable.ajax.reload();
                            if (typeof toastr !== 'undefined') {
                                toastr.success('{{helperTrans("ingaz.status_updated")}}');
                            } else {
                                alert('{{helperTrans("ingaz.status_updated")}}');
                            }
                        } else {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('{{helperTrans("ingaz.status_update_error")}}');
                            } else {
                                alert('{{helperTrans("ingaz.status_update_error")}}');
                            }
                        }
                    },
                    error: function () {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('{{helperTrans("ingaz.status_update_error")}}');
                        } else {
                            alert('{{helperTrans("ingaz.status_update_error")}}');
                        }
                    }
                });
            });

            // Initialize tooltips
            function initTooltips() {
                $('[data-tooltip]').each(function () {
                    $(this).hover(
                        function () {
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
                        function () {
                            $('.custom-tooltip').remove();
                        }
                    );
                });
            }

            // Initialize tooltips after DataTable loads
            dataTable.on('draw', function () {
                initTooltips();

                // Clean up dropdown text
                $('.dropdown-item').each(function () {
                    let text = $(this).text();
                    // Remove any repeated text
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

            // Handle dropdown toggle properly
            $(document).on('click', '.dropdown-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $dropdown = $(this).closest('.dropdown');
                const $dropdownMenu = $dropdown.find('.dropdown-menu');

                // Close all other dropdowns
                $('.dropdown').not($dropdown).removeClass('show');
                $('.dropdown-menu').not($dropdownMenu).hide();

                // Toggle current dropdown
                $dropdown.toggleClass('show');
                $dropdownMenu.toggle();
            });

            // Close dropdowns when clicking outside
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $('.dropdown').removeClass('show');
                    $('.dropdown-menu').hide();
                }
            });
        });

    </script>
@endsection
