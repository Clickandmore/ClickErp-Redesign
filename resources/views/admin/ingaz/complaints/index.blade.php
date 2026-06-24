@extends('admin.layout.indexs.index')

@section("style")
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* إحصائيات محسنة */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-card.total {
            border-left-color: #667eea;
        }

        .stats-card.pending {
            border-left-color: #ffc107;
        }

        .stats-card.progress2 {
            border-left-color: #17a2b8;
        }

        .stats-card.resolved {
            border-left-color: #28a745;
        }


        /* فلاتر محسنة */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .filter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* عرض البطاقات */
        .ticket-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .ticket-card.priority-urgent {
            border-left-color: #dc3545;
        }

        .ticket-card.priority-high {
            border-left-color: #fd7e14;
        }

        .ticket-card.priority-medium {
            border-left-color: #ffc107;
        }

        .ticket-card.priority-low {
            border-left-color: #17a2b8;
        }

        .ticket-number {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        /* شارات الحالة */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-in_progress {
            background: #cce5ff;
            color: #004085;
        }

        .status-resolved {
            background: #d4edda;
            color: #155724;
        }

        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        /* تبديل العرض */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .view-toggle .btn {
            padding: 8px 15px;
            background: white;
            border: 1px solid #dee2e6;
            color: #6c757d;
        }

        .view-toggle .btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* أزرار الإجراءات */
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        /* معلومات التذكرة */
        .ticket-meta {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 13px;
            color: #6c757d;
        }

        .ticket-meta i {
            margin-left: 5px;
            color: #667eea;
        }
    </style>
@endsection

@section('page-title')
    {{ __('ingaz.complaints') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_VIEW))

        <div class="container-fluid">
            <!-- Header Section -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h3 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-exclamation-triangle text-primary me-2"></i>
                    {{ __('ingaz.complaints_and_tickets_management') }}
                </h3>
                @if (@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_CREATE))
                    <a href="{{ route('complaints.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        {{ __('ingaz.complaint_add_new') }}
                    </a>
                @endif
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card total position-relative">
                        <div class="stats-number" id="totalTickets">0</div>
                        <div class="stats-label">{{ __('ingaz.total_complaints') }}</div>
                        <i class="fas fa-ticket-alt stats-icon"></i>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card pending position-relative">
                        <div class="stats-number" id="pendingTickets">0</div>
                        <div class="stats-label">{{ __('ingaz.complaint_status_pending') }}</div>
                        <i class="fas fa-clock stats-icon"></i>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card progress2 position-relative">
                        <div class="stats-number" id="inProgressTickets">0</div>
                        <div class="stats-label">{{ __('ingaz.complaint_status_in_progress') }}</div>
                        <i class="fas fa-spinner stats-icon"></i>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card resolved position-relative">
                        <div class="stats-number" id="resolvedTickets">0</div>
                        <div class="stats-label">{{ __('ingaz.complaint_status_resolved') }}</div>
                        <i class="fas fa-check-circle stats-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filter-section">
                <h5 class="mb-4">
                    <i class="fas fa-filter me-2"></i>
                    {{ __('ingaz.advanced_search_filters') }}
                </h5>

                <div class="row">
                    @include('admin.partials.branch-filter', ['id' => 'branchFilter', 'containerClass' => 'col-md-2 mb-3'])
                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.ticket_number') }}</label>
                        <input type="text" id="filterTicketNumber" class="form-control" placeholder="#000000">
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.status') }}</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">{{ __('ingaz.all_statuses') }}</option>
                            <option value="pending">{{ __('ingaz.complaint_status_pending') }}</option>
                            <option value="in_progress">{{ __('ingaz.complaint_status_in_progress') }}</option>
                            <option value="resolved">{{ __('ingaz.complaint_status_resolved') }}</option>
                            <option value="closed">{{ __('ingaz.complaint_status_closed') }}</option>
                            <option value="rejected">{{ __('ingaz.complaint_status_rejected') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.priority') }}</label>
                        <select id="filterPriority" class="form-select">
                            <option value="">{{ __('ingaz.all_priorities') }}</option>
                            <option value="urgent">{{ __('ingaz.priority_urgent') }}</option>
                            <option value="high">{{ __('ingaz.priority_high') }}</option>
                            <option value="medium">{{ __('ingaz.priority_medium') }}</option>
                            <option value="low">{{ __('ingaz.priority_low') }}</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.complaint_type') }}</label>
                        <select id="filterType" class="form-select select2">
                            <option value="">{{ __('ingaz.all_types') }}</option>
                            @if(isset($complaintTypes))
                                @foreach($complaintTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.assigned_employee') }}</label>
                        <select id="filterAssigned" class="form-select select2">
                            <option value="">{{ __('ingaz.all_employees') }}</option>
                            @if(isset($admins))
                                @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label class="filter-label">{{ __('ingaz.time_period') }}</label>
                        <select id="filterPeriod" class="form-select">
                            <option value="">{{ __('ingaz.all_times') }}</option>
                            <option value="today">{{ __('ingaz.today') }}</option>
                            <option value="yesterday">{{ __('ingaz.yesterday') }}</option>
                            <option value="week">{{ __('ingaz.this_week') }}</option>
                            <option value="month">{{ __('ingaz.this_month') }}</option>
                            <option value="custom">{{ __('ingaz.custom_date') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Date Range -->
                <div class="row mt-3" id="customDateRange" style="display: none;">
                    <div class="col-md-3">
                        <label class="filter-label">{{ __('ingaz.from_date') }}</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label">{{ __('ingaz.to_date') }}</label>
                        <input type="date" id="filterDateTo" class="form-control">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button id="applyFilters" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>{{ __('ingaz.apply_filters') }}
                        </button>
                        <button id="resetFilters" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>{{ __('ingaz.reset_filters') }}
                        </button>
                        <button id="exportData" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>{{ __('ingaz.export_excel') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="btn active" id="tableView">
                    <i class="fas fa-table me-2"></i>{{ __('ingaz.table_view') }}
                </button>
                <button class="btn" id="cardView">
                    <i class="fas fa-th-large me-2"></i>{{ __('ingaz.card_view') }}
                </button>
            </div>

            <!-- Data Display -->
            <div class="row">
                <div class="col-lg-12">
                    <!-- Table View -->
                    <div id="tableViewContent">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    {{ __('ingaz.complaints_list') }}
                                </h5>
                            </div>
                            <div class="card-body">

                                    <table id="complaintsTable" class="table display dataTable table-hover"
                                           style="width:100%">
                                        <thead>
                                        <tr>
                                            <th width="80">{{ __('ingaz.ticket_number') }}</th>
                                            <th>{{ __('ingaz.complainant_name') }}</th>
                                            <th>{{ __('ingaz.subject') }}</th>
                                            <th>عقد الإيواء</th>
                                            <th>{{ __('ingaz.type') }}</th>
                                            <th width="80">{{ __('ingaz.priority') }}</th>
                                            <th width="100">{{ __('ingaz.status') }}</th>
                                            <th>{{ __('ingaz.assigned_employee') }}</th>
                                            <th>{{ __('ingaz.date') }}</th>
                                            <th width="150">{{ __('ingaz.actions') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>


                            </div>
                        </div>
                    </div>

                    <!-- Card View -->
                    <div id="cardViewContent" style="display: none;">
                        <div class="row" id="ticketsCards"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Response Modal -->
        <div class="modal fade" id="quickResponseModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-reply me-2"></i>
                            {{ __('ingaz.quick_response_to_complaint') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="quickResponseForm">
                            @csrf
                            <input type="hidden" name="complaint_id" id="quick_complaint_id">

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('ingaz.response_to_complaint') }}</label>
                                <textarea name="response" class="form-control" rows="5" required
                                          placeholder="{{ __('ingaz.write_response_here') }}"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('ingaz.change_status') }}</label>
                                    <select name="status" class="form-select">
                                        <option value="">{{ __('ingaz.no_change') }}</option>
                                        <option value="in_progress">{{ __('ingaz.complaint_status_in_progress') }}</option>
                                        <option value="resolved">{{ __('ingaz.complaint_status_resolved') }}</option>
                                        <option value="closed">{{ __('ingaz.close_ticket') }}</option>
                                        <option value="rejected">{{ __('ingaz.complaint_status_rejected') }}</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('ingaz.transfer_to_employee') }}</label>
                                    <select name="assigned_to" class="form-select">
                                        <option value="">{{ __('ingaz.no_change') }}</option>
                                        @if(isset($admins))
                                            @foreach($admins as $admin)
                                                <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>{{ __('buttons.cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="saveQuickResponse">
                            <i class="fas fa-paper-plane me-2"></i>{{ __('ingaz.send_response') }}
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            const complaintsTranslations = {
                selectPlaceholder: @json(__('ingaz.select_placeholder')),
                noResults: @json(__('ingaz.no_results')),
                searching: @json(__('ingaz.searching')),
                notSpecified: @json(__('ingaz.not_specified')),
                noSubject: @json(__('ingaz.no_subject')),
                priorityUrgent: @json(__('ingaz.priority_urgent')),
                priorityHigh: @json(__('ingaz.priority_high')),
                priorityMedium: @json(__('ingaz.priority_medium')),
                priorityLow: @json(__('ingaz.priority_low')),
                statusPending: @json(__('ingaz.complaint_status_pending')),
                statusInProgress: @json(__('ingaz.complaint_status_in_progress')),
                statusResolved: @json(__('ingaz.complaint_status_resolved')),
                statusClosed: @json(__('ingaz.complaint_status_closed')),
                statusRejected: @json(__('ingaz.complaint_status_rejected')),
                viewDetails: @json(__('ingaz.view_details')),
                quickResponse: @json(__('ingaz.quick_response')),
                edit: @json(__('buttons.edit')),
                delete: @json(__('ingaz.delete')),
                processing: @json(__('dataTable.sProcessing')),
                lengthMenu: @json(__('dataTable.sLengthMenu')),
                zeroRecords: @json(__('dataTable.sZeroRecords')),
                info: @json(__('dataTable.sInfo')),
                infoEmpty: @json(__('dataTable.sInfoEmpty')),
                infoFiltered: @json(__('dataTable.sInfoFiltered')),
                search: @json(__('dataTable.sSearch')),
                first: @json(__('dataTable.sFirst')),
                previous: @json(__('dataTable.sPrevious')),
                next: @json(__('dataTable.sNext')),
                last: @json(__('dataTable.sLast')),
                successTitle: @json(__('ingaz.success_title')),
                errorTitle: @json(__('ingaz.error_title')),
                replySent: @json(__('ingaz.reply_sent_successfully')),
                genericError: @json(__('ingaz.unexpected_error')),
                replySendError: @json(__('ingaz.reply_send_error')),
                areYouSure: @json(__('ingaz.are_you_sure')),
                cannotUndoAction: @json(__('ingaz.cannot_undo_action')),
                yesDelete: @json(__('ingaz.yes_delete')),
                cancel: @json(__('buttons.cancel')),
                deletedTitle: @json(__('admin.deletedSuccess')),
                complaintDeleted: @json(__('ingaz.complaint_deleted_successfully')),
                deleteError: @json(__('ingaz.delete_error')),
                noComplaints: @json(__('ingaz.no_complaints')),
                view: @json(__('ingaz.view')),
                reply: @json(__('ingaz.reply')),
            };

            // Initialize Select2
            $('.select2').select2({
                placeholder: complaintsTranslations.selectPlaceholder,
                allowClear: true,
                dir: 'rtl',
                width: '100%',
                language: {
                    noResults: function () {
                        return complaintsTranslations.noResults;
                    },
                    searching: function () {
                        return complaintsTranslations.searching;
                    }
                }
            });

            // Initialize DataTable
            let table = $('#complaintsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('complaints.index') }}",
                    type: 'GET',
                    data: function (d) {
                        d.branch_id = $('#branchFilter').val();
                        d.ticket_number = $('#filterTicketNumber').val();
                        d.status = $('#filterStatus').val();
                        d.priority = $('#filterPriority').val();
                        d.type = $('#filterType').val();
                        d.assigned_to = $('#filterAssigned').val();
                        d.period = $('#filterPeriod').val();
                        d.date_from = $('#filterDateFrom').val();
                        d.date_to = $('#filterDateTo').val();
                    },
                    dataSrc: function (json) {
                        updateStatistics(json.statistics || {});
                        return json.data;
                    }
                },
                columns: [

                    {
                        data: 'id',
                        render: function (data) {
                            return '<span class="ticket-number">#' + String(data).padStart(6, '0') + '</span>';
                        }, searchable: false
                    },
                    {
                        data: 'complainant_name',
                        render: function (data, type, row) {
                            let html = '<div>';
                            html += '<strong>' + (data || row.user?.name || complaintsTranslations.notSpecified) + '</strong>';
                            if (row.complainant_phone) {
                                html += '<br><small class="text-muted"><i class="fas fa-phone fa-sm me-1"></i>' + row.complainant_phone + '</small>';
                            }
                            html += '</div>';
                            return html;
                        },searchable: true
                    },
                    {
                        data: 'subject',
                        render: function (data) {
                            return data || '<span class="text-muted">' + complaintsTranslations.noSubject + '</span>';
                        },searchable: true
                    },
                    {
                        data: 'contract',
                        render: function (data) {
                            if (!data) {
                                return '<span class="text-muted">غير مرتبط</span>';
                            }

                            return '<a href="' + data.url + '" class="badge bg-secondary text-decoration-none">#' + data.id + '</a>';
                        }, searchable: false
                    },
                    {
                        data: 'complaint_type',
                        render: function (data, type, row) {
                            if (row.complaintType) {
                                return '<span class="badge bg-info">' + row.complaintType.name + '</span>';
                            }
                            return '<span class="text-muted">' + complaintsTranslations.notSpecified + '</span>';
                        },searchable: true
                    },
                    {
                        data: 'priority',
                        render: function (data) {
                            const priorities = {
                                'urgent': '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>' + complaintsTranslations.priorityUrgent + '</span>',
                                'high': '<span class="badge bg-warning"><i class="fas fa-arrow-up me-1"></i>' + complaintsTranslations.priorityHigh + '</span>',
                                'medium': '<span class="badge bg-info"><i class="fas fa-minus me-1"></i>' + complaintsTranslations.priorityMedium + '</span>',
                                'low': '<span class="badge bg-secondary"><i class="fas fa-arrow-down me-1"></i>' + complaintsTranslations.priorityLow + '</span>'
                            };
                            return priorities[data] || '<span class="badge bg-light text-dark">' + complaintsTranslations.notSpecified + '</span>';
                        },searchable: true
                    },
                    {
                        data: 'status',
                        render: function (data) {
                            const statuses = {
                                'pending': '<span class="status-badge status-pending"><i class="fas fa-clock me-1"></i>' + complaintsTranslations.statusPending + '</span>',
                                'in_progress': '<span class="status-badge status-in_progress"><i class="fas fa-spinner me-1"></i>' + complaintsTranslations.statusInProgress + '</span>',
                                'resolved': '<span class="status-badge status-resolved"><i class="fas fa-check me-1"></i>' + complaintsTranslations.statusResolved + '</span>',
                                'closed': '<span class="status-badge status-closed"><i class="fas fa-times me-1"></i>' + complaintsTranslations.statusClosed + '</span>',
                                'rejected': '<span class="status-badge status-rejected"><i class="fas fa-ban me-1"></i>' + complaintsTranslations.statusRejected + '</span>'
                            };
                            return statuses[data] || data;
                        },searchable: true
                    },
                    {
                        data: 'assignedTo',
                        render: function (data, type, row) {
                            if (data && data.name) {
                                return '<span class="text-primary"><i class="fas fa-user-tie me-1"></i>' + data.name + '</span>';
                            }
                            return '<span class="text-muted">' + complaintsTranslations.notSpecified + '</span>';
                        },searchable: true
                    },
                    {
                        data: 'created_at',
                        render: function (data) {
                            let date = new Date(data);
                            // تنسيق التاريخ الميلادي
                            let day = date.getDate().toString().padStart(2, '0');
                            let month = (date.getMonth() + 1).toString().padStart(2, '0');
                            let year = date.getFullYear();
                            let hours = date.getHours().toString().padStart(2, '0');
                            let minutes = date.getMinutes().toString().padStart(2, '0');

                            return '<small>' + day + '/' + month + '/' + year + '<br>' + hours + ':' + minutes + '</small>';
                        },searchable: true
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            let actions = '<div class="action-buttons">';
                            let isClosed = (row.status === 'closed' || row.status === 'resolved' || row.status === 'rejected');

                            // View button - متاح دائماً
                            actions += '<a href="/admin/complaints/' + row.id + '" class="btn btn-icon btn-sm btn-info" title="' + complaintsTranslations.viewDetails + '">';
                            actions += '<i class="fas fa-eye"></i></a>';

                            // Quick response button - غير متاح للتذاكر المغلقة
                            @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_RESPONSE))
                            if (!isClosed) {
                                actions += '<button class="btn btn-icon btn-sm btn-success quick-response" data-id="' + row.id + '" title="' + complaintsTranslations.quickResponse + '">';
                                actions += '<i class="fas fa-reply"></i></button>';
                            }
                            @endif

                            // Edit button - غير متاح للتذاكر المغلقة
                            @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_EDIT))
                            if (!isClosed) {
                                actions += '<a href="/admin/complaints/' + row.id + '/edit" class="btn btn-icon btn-sm btn-warning" title="' + complaintsTranslations.edit + '">';
                                actions += '<i class="fas fa-edit"></i></a>';
                            }
                            @endif

                            // Delete button - غير متاح للتذاكر المغلقة
                            @if(@AdminGuardHasPerm(\App\PermKeys::COMPLAINT_DELETE))
                            if (!isClosed) {
                                actions += '<button class="btn btn-icon btn-sm btn-danger delete-complaint" data-id="' + row.id + '" title="' + complaintsTranslations.delete + '">';
                                actions += '<i class="fas fa-trash"></i></button>';
                            }
                            @endif

                                actions += '</div>';
                            return actions;
                        }
                    }
                ],
                language: {
                    "sProcessing": complaintsTranslations.processing,
                    "sLengthMenu": complaintsTranslations.lengthMenu,
                    "sZeroRecords": complaintsTranslations.zeroRecords,
                    "sInfo": complaintsTranslations.info,
                    "sInfoEmpty": complaintsTranslations.infoEmpty,
                    "sInfoFiltered": complaintsTranslations.infoFiltered,
                    "sInfoPostFix": "",
                    "sSearch": complaintsTranslations.search + ':',
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": complaintsTranslations.first,
                        "sPrevious": complaintsTranslations.previous,
                        "sNext": complaintsTranslations.next,
                        "sLast": complaintsTranslations.last
                    }
                },
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                dom: 'Bfrtip',
                buttons: []
            });

            // Apply filters
            $('#applyFilters').click(function () {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFilters').click(function () {
                $('#branchFilter').val('');
                $('#filterTicketNumber').val('');
                $('#filterStatus').val('');
                $('#filterPriority').val('');
                $('#filterType').val('').trigger('change');
                $('#filterAssigned').val('').trigger('change');
                $('#filterPeriod').val('');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                $('#customDateRange').hide();
                table.ajax.reload();
            });

            // Show custom date range
            $('#filterPeriod').change(function () {
                if ($(this).val() === 'custom') {
                    $('#customDateRange').slideDown();
                } else {
                    $('#customDateRange').slideUp();
                }
            });

            // View toggle
            $('#tableView').click(function () {
                $(this).addClass('active');
                $('#cardView').removeClass('active');
                $('#tableViewContent').show();
                $('#cardViewContent').hide();
            });

            $('#cardView').click(function () {
                $(this).addClass('active');
                $('#tableView').removeClass('active');
                $('#tableViewContent').hide();
                $('#cardViewContent').show();
                loadCardView();
            });

            // Quick response
            $(document).on('click', '.quick-response', function () {
                let complaintId = $(this).data('id');
                $('#quick_complaint_id').val(complaintId);
                $('#quickResponseModal').modal('show');
            });

            // Save quick response
            $('#saveQuickResponse').click(function () {
                let formData = $('#quickResponseForm').serialize();

                $.ajax({
                    url: "{{ route('complaints.quick-response') }}",
                    type: 'POST',
                    data: formData,
                    success: function (response) {
                        if (response.success) {
                            $('#quickResponseModal').modal('hide');
                            $('#quickResponseForm')[0].reset();
                            Swal.fire({
                                icon: 'success',
                                title: complaintsTranslations.successTitle,
                                text: complaintsTranslations.replySent,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: complaintsTranslations.errorTitle,
                                text: response.message || complaintsTranslations.genericError
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: complaintsTranslations.errorTitle,
                            text: complaintsTranslations.replySendError
                        });
                    }
                });
            });

            // Delete complaint
            $(document).on('click', '.delete-complaint', function () {
                let complaintId = $(this).data('id');

                Swal.fire({
                    title: complaintsTranslations.areYouSure,
                    text: complaintsTranslations.cannotUndoAction,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: complaintsTranslations.yesDelete,
                    cancelButtonText: complaintsTranslations.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/complaints/' + complaintId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: complaintsTranslations.deletedTitle,
                                        text: complaintsTranslations.complaintDeleted,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    table.ajax.reload();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: complaintsTranslations.errorTitle,
                                        text: response.message || complaintsTranslations.genericError
                                    });
                                }
                            },
                            error: function () {
                                Swal.fire({
                                    icon: 'error',
                                    title: complaintsTranslations.errorTitle,
                                    text: complaintsTranslations.deleteError
                                });
                            }
                        });
                    }
                });
            });

            // Update statistics
            function updateStatistics(stats) {
                $('#totalTickets').text(stats.total || 0);
                $('#pendingTickets').text(stats.pending || 0);
                $('#inProgressTickets').text(stats.in_progress || 0);
                $('#resolvedTickets').text(stats.resolved || 0);
            }

            // Load card view
            function loadCardView() {
                $.ajax({
                    url: "{{ route('complaints.cards') }}",
                    type: 'GET',
                    data: {
                        branch_id: $('#branchFilter').val(),
                        status: $('#filterStatus').val(),
                        priority: $('#filterPriority').val(),
                        type: $('#filterType').val(),
                        assigned_to: $('#filterAssigned').val()
                    },
                    success: function (response) {
                        let html = '';

                        if (response.data && response.data.length > 0) {
                            response.data.forEach(function (ticket) {
                                html += createTicketCard(ticket);
                            });
                        } else {
                            html = '<div class="col-12 text-center"><p class="text-muted">' + complaintsTranslations.noComplaints + '</p></div>';
                        }

                        $('#ticketsCards').html(html);
                    }
                });
            }

            // Create ticket card
            function createTicketCard(ticket) {
                let priorityClass = 'priority-' + (ticket.priority || 'medium');
                let statusBadge = getStatusBadge(ticket.status);
                let priorityBadge = getPriorityBadge(ticket.priority);

                return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="ticket-card ${priorityClass}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="ticket-number">#${String(ticket.id).padStart(6, '0')}</span>
                        ${statusBadge}
                    </div>

                    <h5 class="card-title">${ticket.subject || complaintsTranslations.noSubject}</h5>

                    <div class="ticket-meta">
                        <span><i class="fas fa-user"></i>${ticket.complainant_name || complaintsTranslations.notSpecified}</span>
                        <span><i class="fas fa-calendar"></i>${new Date(ticket.created_at).toLocaleDateString('ar-SA')}</span>
                    </div>

                    <div class="mt-3">
                        ${priorityBadge}
                        ${ticket.complaintType ? '<span class="badge bg-info ms-2">' + ticket.complaintType.name + '</span>' : ''}
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <a href="/admin/complaints/${ticket.id}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i>${complaintsTranslations.view}
                        </a>
                        ${ticket.status === 'pending' || ticket.status === 'in_progress' ?
                    '<a href="/admin/complaints/' + ticket.id + '/edit" class="btn btn-sm btn-warning"><i class="fas fa-edit me-1"></i>' + complaintsTranslations.edit + '</a>' : ''}
                        ${ticket.status !== 'closed' && ticket.status !== 'resolved' ?
                    '<button class="btn btn-sm btn-success quick-response" data-id="' + ticket.id + '"><i class="fas fa-reply me-1"></i>' + complaintsTranslations.reply + '</button>' : ''}
                    </div>
                </div>
            </div>
        `;
            }

            // Get status badge
            function getStatusBadge(status) {
                const statuses = {
                    'pending': '<span class="status-badge status-pending">' + complaintsTranslations.statusPending + '</span>',
                    'in_progress': '<span class="status-badge status-in_progress">' + complaintsTranslations.statusInProgress + '</span>',
                    'resolved': '<span class="status-badge status-resolved">' + complaintsTranslations.statusResolved + '</span>',
                    'closed': '<span class="status-badge status-closed">' + complaintsTranslations.statusClosed + '</span>',
                    'rejected': '<span class="status-badge status-rejected">' + complaintsTranslations.statusRejected + '</span>'
                };
                return statuses[status] || '<span class="badge bg-secondary">' + status + '</span>';
            }

            // Get priority badge
            function getPriorityBadge(priority) {
                const priorities = {
                    'urgent': '<span class="badge bg-danger">' + complaintsTranslations.priorityUrgent + '</span>',
                    'high': '<span class="badge bg-warning">' + complaintsTranslations.priorityHigh + '</span>',
                    'medium': '<span class="badge bg-info">' + complaintsTranslations.priorityMedium + '</span>',
                    'low': '<span class="badge bg-secondary">' + complaintsTranslations.priorityLow + '</span>'
                };
                return priorities[priority] || '';
            }

            // Export data
            $('#exportData').click(function () {
                window.location.href = "{{ route('complaints.index') }}?export=excel&" + $.param({
                    branch_id: $('#branchFilter').val(),
                    status: $('#filterStatus').val(),
                    priority: $('#filterPriority').val(),
                    type: $('#filterType').val(),
                    assigned_to: $('#filterAssigned').val(),
                    period: $('#filterPeriod').val(),
                    date_from: $('#filterDateFrom').val(),
                    date_to: $('#filterDateTo').val()
                });
            });
        });
    </script>
@endsection
