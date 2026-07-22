@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .order-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .order-stat-card {
            border: 1px solid #e8ecf4;
            border-radius: 18px;
            padding: 16px 18px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            width: 100%;
        }

        .order-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
        }

        .order-stat-card.active {
            border-color: var(--bs-primary);
            box-shadow: 0 24px 40px rgba(13, 110, 253, 0.15);
        }

        .order-stat-card span {
            font-size: 0.8rem;
            color: #6c778a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .order-stat-card strong {
            display: block;
            font-size: 2rem;
            color: #111c3a;
            margin: 8px 0 4px;
        }

        .order-stat-card small {
            color: #8892a6;
            font-size: 0.85rem;
        }

        .timer-chip {
            border-radius: 12px;
            padding: 8px 10px;
            background: #f4f5fb;
            border: 1px solid #eef0f7;
            display: inline-flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
            line-height: 1.35;
        }

        .timer-chip__phase {
            font-size: 0.75rem;
            color: #6f78a2;
            white-space: nowrap;
        }

        .timer-chip__value {
            font-size: 1rem;
            font-weight: 700;
            color: #1b2240;
        }

        .timer-chip__hint {
            font-size: 0.7rem;
            color: #8791b4;
            white-space: nowrap;
        }

        .timer-chip--danger {
            background: #fff1f1;
            border-color: #ffd6d6;
        }

        .timer-chip--warning {
            background: #fff5e6;
            border-color: #ffe0b5;
        }

        .timer-chip--info {
            background: #eef6ff;
            border-color: #d1e6ff;
        }

        .timer-chip--muted {
            background: #f4f5fb;
        }

        .table-card {
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.05);
            border: 1px solid #e8ecf4;
        }

        /* .table-responsive {
                        border-radius: 0 0 24px 24px;
                    } */

        .orders-table th {
            font-size: 0.82rem;
            text-transform: uppercase;
            background-color: #f8f9fc;
            letter-spacing: 0.06em;
        }

        /* .orders-table .dropdown-menu .dropdown-menu-end {
                top: 0px !important;
            } */

        .badge {
            border-radius: 999px;
        }

        .order-notes-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #9a3412;
            border: 1px solid #fed7aa;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .order-notes-indicator:hover {
            color: #7c2d12;
            background: #ffedd5;
            text-decoration: none;
        }

        button.edit-status {
            padding: 4px 12px;
            border: #2794eb 1px solid;
            outline: none;
            background: white;
            color: #2794eb;
            display: flex;
            align-items: center;
            gap: 4px;
            border-radius: 9px;
            white-space: nowrap;
            transition: all 0.3s ease-in-out;
            font-size: 13px;
        }

        button.edit-status:hover {
            background: #111c3a;
            color: white;
            border-color: #111c3a;
        }

        /* To show actions */
        .dataTables_scrollBody {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        table tbody tr:first-child .dropdown-menu.dropdown-menu-end {
            right: 3% !important;
            left: unset !important;
            transform: translateX(50%) !important;
            /* top: 0 !important; */
            top: -50px !important;
            position: absolute !important;
            z-index: 99999 !important;
            min-width: 150px !important;
        }

        .orders-page .table-responsive {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
        }

        .orders-page #dataTableObject_wrapper,
        .orders-page .dataTables_scroll,
        .orders-page .dataTables_scrollHead,
        .orders-page .dataTables_scrollBody {
            width: 100% !important;
        }

        @media (max-width: 991.98px) {
            .layout-1 .wrapper {
                overflow-y: auto !important;
            }

            .orders-page .container-fluid {
                padding-inline: 12px;
            }

            .orders-page .order-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .orders-page .order-stat-card {
                padding: 12px;
            }

            .orders-page .order-stat-card strong {
                font-size: 1.5rem;
            }

            .orders-page .export-buttons {
                width: 100%;
                margin-top: 10px;
                justify-content: stretch !important;
            }

            .orders-page .export-buttons .btn {
                flex: 1 1 auto;
            }

            .orders-page .table-responsive {
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            .orders-page .dataTables_scrollBody {
                overflow-x: auto !important;
                overflow-y: visible !important;
                max-height: none !important;
            }

            .orders-page table tbody tr:first-child .dropdown-menu.dropdown-menu-end {
                right: 0 !important;
                left: auto !important;
                transform: none !important;
                top: auto !important;
            }
        }

        @media (max-width: 575.98px) {
            .orders-page .order-stats-grid {
                grid-template-columns: 1fr;
            }

            .orders-page .table-style th,
            .orders-page table.dataTable tbody td {
                white-space: nowrap;
                font-size: 0.82rem;
            }
        }
    </style>
@endsection
@section('page-title')
    {{ helperTrans('ingaz.recruitment_orders') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW))
        <div class="page-body orders-page">
            <div class="container-fluid">
                <!-- Header Section -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-exchange-alt text-primary me-2"></i>
                        {{ helperTrans('ingaz.recruitment_orders') }}
                    </h3>
                    <div class="export-buttons text-end d-flex flex-wrap gap-2 justify-content-end">
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CREATE))
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#quickRecruitmentModal">
                                <i class="fa fa-bolt"></i> {{ helperTrans('ingaz.quick_order') }}
                            </button>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EXPORT))
                            <a href="{{ route('orders.export.pdf') }}" class="btn btn-outline-danger btn-sm" target="_blank">
                                <i class="fa fa-file-pdf"></i> {{ helperTrans('ingaz.export_pdf') }}
                            </a>
                            <a href="{{ route('orders.export.excel') }}" class="btn btn-outline-success btn-sm">
                                <i class="fa fa-file-excel"></i> {{ helperTrans('ingaz.export_excel') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="order-stats-grid">
                    <button type="button" class="order-stat-card active" data-status="">
                        <span>{{ helperTrans('ingaz.orders_overview') }}</span>
                        <strong>{{ number_format($totalOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.total') }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="new">
                        <span>{{ helperTrans('ingaz.timer_phase_new') }}</span>
                        <strong>{{ number_format($newOrders ?? 0) }}</strong>
                        <small>{{ getEnumValue('orders_statuses', \App\Enums\ingaz\OrderEnum::New->value) }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="under_process">
                        <span>{{ helperTrans('ingaz.timer_phase_under_process') }}</span>
                        <strong>{{ number_format($underProcessOrders ?? 0) }}</strong>
                        <small>{{ getEnumValue('orders_statuses', \App\Enums\ingaz\OrderEnum::UnderProcess->value) }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="completed">
                        <span>{{ helperTrans('ingaz.ended orders') }}</span>
                        <strong>{{ number_format($completedOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.completed') }}</small>
                    </button>
                    <button type="button" class="order-stat-card" data-status="not_completed">
                        <span>{{ helperTrans('ingaz.cancelled_orders') }}</span>
                        <strong>{{ number_format($notCompletedOrders ?? 0) }}</strong>
                        <small>{{ helperTrans('ingaz.not completed orders') }}</small>
                    </button>
                </div>


                <div class="row g-4 align-items-start">
                    <div class="col-xxl-12">
                        <!-- Filter Form -->
                        <div class="card mb-4 filter-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-filter me-2"></i>
                                    {{ helperTrans('ingaz.filters') }}
                                </h6>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                                </button>
                            </div>
                            <div class="card-body collapse" id="filtersCollapse">
                                <form id="filterForm" class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.from_date') }}</label>
                                        <input type="date" class="form-control" name="from_date" id="from_date">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.to_date') }}</label>
                                        <input type="date" class="form-control" name="to_date" id="to_date">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.status') }}</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            @foreach (\App\Enums\ingaz\OrderEnum::cases() as $status)
                                                <option value="{{ $status->value }}">
                                                    {{ getEnumValue('orders_statuses', $status->value) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.job_type') }}</label>
                                        <select class="form-select" name="job_type" id="job_type">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            @if (isset($jobTypes))
                                                @foreach ($jobTypes as $jobType)
                                                    <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.nationality') }}</label>
                                        <select class="form-select" name="nationality_id" id="nationality_id">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            @if (isset($nationalities))
                                                @foreach ($nationalities as $nationality)
                                                    <option value="{{ $nationality->id }}">
                                                        {{ $nationality->title ?? $nationality->sub_title ?? $nationality->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                                        <select class="form-select" name="request_type" id="request_type">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.responsible_employee') }}</label>
                                        <select class="form-select" name="admin_id" id="admin_id">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            @if (isset($admins))
                                                @foreach ($admins as $admin)
                                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    @include('admin.partials.branch-filter', ['id' => 'branch_id', 'containerClass' => 'col-md-2'])
                                    <div class="col-md-2">
                                        <label class="form-label">{{ helperTrans('ingaz.timer_status') }}</label>
                                        <select class="form-select" name="timer_status" id="timer_status">
                                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                                            <option value="normal">{{ helperTrans('ingaz.normal') }}</option>
                                            <option value="urgent">{{ helperTrans('ingaz.urgent') }}</option>
                                            <option value="overdue">{{ helperTrans('ingaz.overdue') }}</option>
                                        </select>
                                    </div>
                                    <!-- Search Fields -->
                                    <div class="col-md-4">
                                        <label class="form-label">{{ helperTrans('ingaz.client_name') }}</label>
                                        <input type="text" class="form-control" name="client_name" id="client_name"
                                            placeholder="{{ helperTrans('ingaz.search_by_client_name') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ helperTrans('ingaz.phone') }}</label>
                                        <input type="text" class="form-control" name="phone" id="phone"
                                            placeholder="{{ helperTrans('ingaz.search_by_phone') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ helperTrans('ingaz.passport_num') }}</label>
                                        <input type="text" class="form-control" name="passport_num" id="passport_num"
                                            placeholder="{{ helperTrans('ingaz.search_by_passport') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ helperTrans('ingaz.recruitment_office') }}</label>
                                        <input type="text" class="form-control" name="office_name" id="office_name"
                                            placeholder="{{ helperTrans('ingaz.recruitment_office') }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fa fa-filter"></i> {{ helperTrans('ingaz.filter') }}
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="resetFilter">
                                                <i class="fa fa-refresh"></i> {{ helperTrans('ingaz.reset') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> <!-- end row -->

                <!-- Data Table -->
                <div class="card shadow-sm border-0">
                    {{-- <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fa fa-list"></i> قائمة طلبات الاستقدام
                                </h5>
                            </div> --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTableObject" class="table table-hover align-middle table-style"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ helperTrans('ingaz.maid_photo') }}</th>
                                        <th>{{ helperTrans('ingaz.client') }}</th>
                                        <th>{{ helperTrans('ingaz.phone') }}</th>
                                        <th>{{ helperTrans('ingaz.nationality') }}</th>
                                        <th>{{ helperTrans('ingaz.passport_num') }}</th>
                                        {{-- <th>{{helperTrans("ingaz.maid")}}</th> --}}
                                        {{-- <th>{{helperTrans("ingaz.deliver_status")}}</th> --}}
                                        <th>{{ helperTrans('ingaz.order_job_type') }}</th>
                                        <th>{{ helperTrans('ingaz.status') }}</th>
                                        <th>التنبيهات</th>
                                        <th>{{ helperTrans('ingaz.contract_status') }}</th>
                                        <th>{{ helperTrans('ingaz.order_date') }}</th>
                                        <th>{{ helperTrans('ingaz.responsible_employee') }}</th>
                                        @if($branchesEnabled ?? false)
                                            <th>{{ __('admin.branches') }}</th>
                                        @endif
                                        <th class="text-center">{{ helperTrans('ingaz.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- end container -->
        </div> <!-- end page body -->

        @if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_CREATE))
            <div class="modal fade" id="quickRecruitmentModal" tabindex="-1" aria-labelledby="quickRecruitmentModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="quickRecruitmentForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="quickRecruitmentModalLabel">{{ helperTrans('ingaz.quick_recruitment_order') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="quick_client_name" class="form-label">{{ helperTrans('ingaz.client_name') }}</label>
                                    <input type="text" class="form-control" id="quick_client_name" name="name"
                                        placeholder="{{ helperTrans('ingaz.client_name_example') }}" required>
                                    <div class="invalid-feedback" data-error-for="name"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="quick_client_phone" class="form-label">{{ helperTrans('ingaz.phone') }}</label>
                                    <input type="text" class="form-control" id="quick_client_phone" name="phone"
                                        placeholder="05XXXXXXXX" required>
                                    <div class="invalid-feedback" data-error-for="phone"></div>
                                </div>
                                <small class="text-muted">{!! __('ingaz.default_client_password_notice', ['password' => '<strong>123456</strong>']) !!}</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                                <button type="submit" class="btn btn-primary" id="quickRecruitmentSubmitBtn">
                                    <i class="fa fa-check"></i> {{ helperTrans('ingaz.continue_to_select_cv') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @else
        @include('components.no_permissions')
    @endif
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>

    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        const orderTranslations = {
            redirecting: @json(helperTrans('ingaz.quick_order_redirecting')),
            errorTitle: @json(helperTrans('ingaz.error_title')),
            successTitle: @json(helperTrans('ingaz.success')),
            quickOrderCreateError: @json(helperTrans('ingaz.quick_order_create_error')),
            loading: @json(helperTrans('ingaz.loading_text')),
            emptyTable: @json(trans('dataTable.sZeroRecords')),
            info: @json(trans('dataTable.sInfo')),
            infoEmpty: @json(trans('dataTable.sInfoEmpty')),
            infoFiltered: @json(trans('dataTable.sInfoFiltered')),
            lengthMenu: @json(trans('dataTable.sLengthMenu')),
            zeroRecords: @json(trans('dataTable.sZeroRecords')),
            paginateFirst: @json(trans('dataTable.sFirst')),
            paginateLast: @json(trans('dataTable.sLast')),
            paginateNext: @json(trans('dataTable.sNext')),
            paginatePrevious: @json(trans('dataTable.sPrevious')),
            loadingEditForm: @json(helperTrans('ingaz.loading_edit_form')),
            orderNotFound: @json(helperTrans('ingaz.order_not_found')),
            serverError: @json(helperTrans('ingaz.server_error')),
            serverConnectionFailed: @json(helperTrans('ingaz.server_connection_failed')),
            orderIdNotFound: @json(helperTrans('ingaz.order_id_not_found')),
            changeMaidInOrderNumber: @json(helperTrans('ingaz.change_maid_in_order_number')),
            searchAvailableMaids: @json(helperTrans('ingaz.search_available_maids')),
            searchByNameOrPassport: @json(helperTrans('ingaz.search_by_name_or_passport')),
            searchAvailableMaidsHint: @json(helperTrans('ingaz.search_available_maids_hint')),
            selectNewMaid: @json(helperTrans('ingaz.select_new_maid')),
            selectNewMaidPlaceholder: @json(helperTrans('ingaz.select_new_maid_placeholder')),
            availableMaidsLoading: @json(helperTrans('ingaz.available_maids_loading')),
            previousMaidStatus: @json(helperTrans('ingaz.previous_maid_status')),
            available: @json(helperTrans('ingaz.available')),
            backout: @json(helperTrans('ingaz.backout')),
            changeReasonOptional: @json(helperTrans('ingaz.change_reason_optional')),
            changeReasonPlaceholder: @json(helperTrans('ingaz.change_reason_placeholder')),
            cancel: @json(helperTrans('ingaz.cancel')),
            saveChange: @json(helperTrans('ingaz.save_change')),
            availableMaidsLoaded: @json(helperTrans('ingaz.available_maids_loaded')),
            noAvailableMaids: @json(helperTrans('ingaz.no_available_maids')),
            maidsLoadFailed: @json(helperTrans('ingaz.maids_load_failed')),
            maidsLoadError: @json(helperTrans('ingaz.maids_load_error')),
            selectNewMaidRequired: @json(helperTrans('ingaz.select_new_maid_required')),
            selectPreviousMaidStatusRequired: @json(helperTrans('ingaz.select_previous_maid_status_required')),
            confirmChangeMaidTitle: @json(helperTrans('ingaz.confirm_change_maid_title')),
            confirmChangeMaidText: @json(helperTrans('ingaz.confirm_change_maid_text')),
            yesChangeMaid: @json(helperTrans('ingaz.yes_change_maid')),
            maidChangedSuccessfully: @json(helperTrans('ingaz.maid_changed_successfully')),
            maidChangeFailed: @json(helperTrans('ingaz.maid_change_failed')),
            maidChangeError: @json(helperTrans('ingaz.maid_change_error')),
            comingSoon: @json(helperTrans('ingaz.coming_soon')),
            featureUnderDevelopment: @json(helperTrans('ingaz.feature_under_development')),
            deleteActionText: @json(helperTrans('ingaz.delete_item_confirm_text')),
            deleteConfirmButton: @json(helperTrans('ingaz.yes_delete')),
        };

        $(document).ready(function() {
            let columns = [{
                    "data": 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "maid_photo",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "user_id",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "phone",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "nationality_name",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "maid_passport_num",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "job_type_id",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "status",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "timer",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "contract_status",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "created_at",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "admin_name",
                    orderable: true,
                    searchable: true
                },
                @if($branchesEnabled ?? false)
                {
                    "data": "branch",
                    orderable: true,
                    searchable: true
                },
                @endif
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ];

            // Get URL parameters
            function getUrlParameter(name) {
                name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            }

            const quickRecruitmentModal = $('#quickRecruitmentModal');
            const quickRecruitmentForm = $('#quickRecruitmentForm');
            const quickRecruitmentSubmitBtn = $('#quickRecruitmentSubmitBtn');
            const quickRecruitmentSubmitDefaultHtml = quickRecruitmentSubmitBtn.length ?
                quickRecruitmentSubmitBtn.html() : '';

            function resetQuickRecruitmentValidation() {
                if (!quickRecruitmentForm.length) {
                    return;
                }

                quickRecruitmentForm.find('.form-control').removeClass('is-invalid');
                quickRecruitmentForm.find('[data-error-for]').text('');
            }

            if (quickRecruitmentModal.length) {
                quickRecruitmentModal.on('hidden.bs.modal', function() {
                    resetQuickRecruitmentValidation();
                    quickRecruitmentForm.trigger('reset');
                    quickRecruitmentSubmitBtn.prop('disabled', false).html(
                        quickRecruitmentSubmitDefaultHtml);
                });
            }

            if (quickRecruitmentForm.length) {
                quickRecruitmentForm.on('submit', function(e) {
                    e.preventDefault();
                    resetQuickRecruitmentValidation();

                    quickRecruitmentSubmitBtn.prop('disabled', true).html(
                        `<i class="fa fa-spinner fa-spin"></i> ${orderTranslations.redirecting}`);

                    $.ajax({
                        url: '{{ route('orders.quickRecruitment') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            name: $('#quick_client_name').val(),
                            phone: $('#quick_client_phone').val()
                        },
                        success: function(response) {
                            if (!response.success) {
                                Swal.fire(orderTranslations.errorTitle, response.message || orderTranslations.quickOrderCreateError, 'error');
                                quickRecruitmentSubmitBtn.prop('disabled', false).html(
                                    quickRecruitmentSubmitDefaultHtml);
                                return;
                            }

                            quickRecruitmentModal.modal('hide');
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            const errors = response.errors || {};

                            if (errors.name) {
                                $('#quick_client_name').addClass('is-invalid');
                                quickRecruitmentForm.find('[data-error-for="name"]').text(errors.name[0]);
                            }

                            if (errors.phone) {
                                $('#quick_client_phone').addClass('is-invalid');
                                quickRecruitmentForm.find('[data-error-for="phone"]').text(errors.phone[0]);
                            }

                            if (!errors.name && !errors.phone) {
                                Swal.fire(orderTranslations.errorTitle, response.message || orderTranslations.quickOrderCreateError, 'error');
                            }

                            quickRecruitmentSubmitBtn.prop('disabled', false).html(
                                quickRecruitmentSubmitDefaultHtml);
                        }
                    });
                });
            }

            const isMobileTableView = window.matchMedia('(max-width: 991.98px)').matches;

            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                responsive: isMobileTableView,
                searching: false,
                deferRender: true, // تأجيل رسم الصفوف لتحسين الأداء
                pageLength: 25, // عرض 25 صف فقط بدلاً من 10
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: @if($branchesEnabled ?? false) [0, 2, 7, 10, 13] @else [0, 2, 7, 10, 12] @endif
                    },
                    {
                        responsivePriority: 2,
                        targets: [3, 6, 8]
                    }
                ],
                ajax: {
                    url: "{{ route('orders.index') }}",
                    type: 'GET',
                    data: function(d) {
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                        d.status = $('#status').val() || getUrlParameter('status');
                        d.job_type = $('#job_type').val();
                        d.nationality_id = $('#nationality_id').val();
                        d.request_type = $('#request_type').val();
                        d.admin_id = $('#admin_id').val() || getUrlParameter('admin_id');
                        d.branch_id = $('#branch_id').length ? $('#branch_id').val() : '';
                        d.timer_status = $('#timer_status').val();
                        d.client_name = $('#client_name').val();
                        d.phone = $('#phone').val();
                        d.passport_num = $('#passport_num').val();
                        d.office_name = $('#office_name').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', error);
                        console.error('XHR:', xhr);
                        console.error('Thrown:', thrown);
                        console.error('Response Text:', xhr.responseText);
                        console.error('Status:', xhr.status);
                    },
                    dataSrc: function(json) {
                        // معالجة البيانات قبل عرضها
                        console.log('Received ' + json.data.length + ' records');
                        return json.data;
                    }
                },
                columns: columns,
                language: {
                    processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${orderTranslations.loading}</span></div>`,
                    loadingRecords: orderTranslations.loading,
                    emptyTable: orderTranslations.emptyTable,
                    info: orderTranslations.info,
                    infoEmpty: orderTranslations.infoEmpty,
                    infoFiltered: orderTranslations.infoFiltered,
                    lengthMenu: orderTranslations.lengthMenu,
                    zeroRecords: orderTranslations.zeroRecords,
                    paginate: {
                        first: orderTranslations.paginateFirst,
                        last: orderTranslations.paginateLast,
                        next: orderTranslations.paginateNext,
                        previous: orderTranslations.paginatePrevious
                    }
                },
                drawCallback: function() {
                    // admin-tables.js يتولى معالجة القوائم المنسدلة
                },
                scrollX: !isMobileTableView,
                scrollCollapse: !isMobileTableView,
                fixedColumns: !isMobileTableView ? {
                    leftColumns: 0,
                    rightColumns: 1
                } : false,
                stateSave: false, // عدم حفظ حالة الجدول
                autoWidth: false // عدم حساب العرض تلقائياً
            });

            function setActiveCard(value) {
                $('.order-stat-card').each(function() {
                    const status = $(this).data('status') || '';
                    $(this).toggleClass('active', status === (value || ''));
                });
            }

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                dataTable.ajax.reload();
            });

            // Set form values from URL parameters on page load
            var urlStatus = getUrlParameter('status');
            var urlAdminId = getUrlParameter('admin_id');
            if (urlStatus) {
                $('#status').val(urlStatus);
            }
            if (urlAdminId) {
                $('#admin_id').val(urlAdminId);
            }

            setActiveCard($('#status').val());

            // Handle reset button
            $('#resetFilter').on('click', function() {
                $('#filterForm')[0].reset();
                $('#status').val('').trigger('change');
                dataTable.ajax.reload();
            });

            $('#status').on('change', function() {
                setActiveCard($(this).val());
            });

            $('.order-stat-card').on('click', function() {
                const status = $(this).data('status') || '';
                $('#status').val(status);
                setActiveCard(status);
                dataTable.ajax.reload();
                const tableOffset = $('#dataTableObject').offset();
                if (tableOffset) {
                    const $wrapper = $('.layout-1 .wrapper');
                    if ($wrapper.length) {
                        const wrapperTop = $wrapper.offset().top;
                        const targetScroll = $wrapper.scrollTop() + tableOffset.top - wrapperTop - 120;
                        $wrapper.stop().animate({
                            scrollTop: Math.max(targetScroll, 0)
                        }, 300);
                    } else {
                        $('html, body').animate({
                            scrollTop: tableOffset.top - 120
                        }, 300);
                    }
                }
            });

            // Update timer every minute
            setInterval(function() {
                dataTable.ajax.reload(null, false); // false to maintain pagination
            }, 60000); // 60 seconds

            // Handle delete order
            $(document).on('click', '.delete-order', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                Swal.fire({
                    title: "{{ helperTrans('buttons.Are You Sure') }}",
                    text: '{{ helperTrans('buttons.You Can not to rollback') ?? 'This action cannot be undone!' }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ helperTrans('buttons.accept') ?? 'Yes, delete it!' }}',
                    cancelButtonText: '{{ helperTrans('buttons.cancel') ?? 'Cancel' }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/orders/' + orderId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.data.success) {
                                    dataTable.ajax.reload();
                                    // toastr.success(
                                    //     '{{ helperTrans('ingaz.order_deleted') }}');
                                    Swal.fire(orderTranslations.successTitle, response.message ||
                                        '{{ helperTrans('ingaz.order_deleted_successfully') }}',
                                        'success');
                                } else {
                                    dataTable.ajax.reload();
                                    // toastr.error(
                                    //     '{{ helperTrans('ingaz.delete_error') }}');
                                    Swal.fire(orderTranslations.errorTitle,
                                        '{{ helperTrans('ingaz.delete_error') }}',
                                        'error');
                                }
                            },
                            error: function() {
                                // toastr.error(
                                //     '{{ helperTrans('ingaz.delete_error') }}');
                                Swal.fire(orderTranslations.errorTitle,
                                    '{{ helperTrans('ingaz.delete_error') }}',
                                    'error');
                            }
                        });
                        // deleteOrder(orderId);
                    }
                });
            });
            // $(document).on('click', '.delete-order', function() {
            //     const orderId = $(this).data('id');
            //     if (confirm('{{ helperTrans('ingaz.delete_confirm') }}')) {
            //         $.ajax({
            //             url: '/admin/orders/' + orderId,
            //             type: 'DELETE',
            //             data: {
            //                 _token: '{{ csrf_token() }}'
            //             },
            //             success: function(response) {
            //                 if (response.success) {
            //                     dataTable.ajax.reload();
            //                     toastr.success('{{ helperTrans('ingaz.order_deleted') }}');
            //                 } else {
            //                     toastr.error('{{ helperTrans('ingaz.delete_error') }}');
            //                 }
            //             },
            //             error: function() {
            //                 toastr.error('{{ helperTrans('ingaz.delete_error') }}');
            //             }
            //         });
            //     }
            // });

            function toggleRefuseReasonField(selectedStatus) {
                const wrapper = $('#editStatusModal').find('[data-refuse-reason]');
                if (!wrapper.length) {
                    return;
                }

                const needsReason = ['refused', 'return'].includes(selectedStatus);
                wrapper.toggleClass('d-none', !needsReason);
            }

            $(document).on('shown.bs.modal', '#editStatusModal', function() {
                const checked = $(this).find('input[name="status"]:checked');
                toggleRefuseReasonField(checked.length ? checked.val() : '');
            });

            $(document).on('change', '#editStatusModal input[name="status"]', function() {
                toggleRefuseReasonField($(this).val());
            });

            function hideAndRemoveModal(modalSelector) {
                const modalElement = document.querySelector(modalSelector);

                if (!modalElement) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                    return;
                }

                $(modalElement).one('hidden.bs.modal', function() {
                    $(this).remove();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                });

                if (window.bootstrap && bootstrap.Modal) {
                    const bsModal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    bsModal.hide();
                    return;
                }

                $(modalElement).modal('hide');
            }

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
                                <span class="visually-hidden">${orderTranslations.loading}</span>
                            </div>
                            <p class="mt-2">${orderTranslations.loadingEditForm}</p>
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
                        console.log('Sending AJAX request to:', '/admin/orders/' + orderId +
                            '/edit');
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
                                        <h5 class="modal-title">{{ helperTrans('ingaz.edit_order_status') }}</h5>
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
                                        console.log('Update response:',
                                            response);
                                        const updateSucceeded = response.success === true ||
                                            (response.data && response.data.success === true) ||
                                            response.code === 200;

                                        if (updateSucceeded) {
                                            hideAndRemoveModal('#editStatusModal');

                                            if (typeof toastr !==
                                                'undefined') {
                                                toastr.success(
                                                    response.message ||
                                                    '{{ helperTrans('ingaz.status_updated') }}'
                                                );
                                            }

                                            if (typeof dataTable !==
                                                'undefined') {
                                                dataTable.ajax.reload(null,
                                                    false);
                                            }
                                        } else {
                                            if (typeof toastr !==
                                                'undefined') {
                                                toastr.error(response
                                                    .message ||
                                                    '{{ helperTrans('ingaz.status_update_error') }}'
                                                );
                                            } else {
                                                toastr.error(response
                                                    .message ||
                                                    '{{ helperTrans('ingaz.status_update_error') }}'
                                                );
                                                // alert(response.message ||
                                                //     '{{ helperTrans('ingaz.status_update_error') }}'
                                                // );
                                            }
                                        }

                                        $('#loadingModal').remove();
                                    },
                                    error: function(xhr) {
                                        console.log('Update error:', xhr);
                                        let errorMessage =
                                            '{{ helperTrans('ingaz.status_update_error') }}';
                                        if (xhr.responseJSON && xhr
                                            .responseJSON.message) {
                                            errorMessage = xhr.responseJSON
                                                .message;
                                        }
                                        if (typeof toastr !== 'undefined') {
                                            toastr.error(errorMessage);
                                        } else {
                                            toastr.error(errorMessage);
                                        }
                                    }
                                });
                            });

                            // Handle modal close button
                            $('#editStatusModal .btn-close, #editStatusModal [data-bs-dismiss="modal"]')
                                .on('click', function() {
                                    hideAndRemoveModal('#editStatusModal');
                                });

                            // Handle modal hidden event
                            $('#editStatusModal').on('hidden.bs.modal', function() {
                                $(this).remove();
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open').css('padding-right', '');
                            });
                        } else {
                            console.log('Response not successful:', response);
                            let errorMessage = '{{ helperTrans('ingaz.load_form_error') }}';
                            if (response.message) {
                                errorMessage = response.message;
                            }
                            if (typeof toastr !== 'undefined') {
                                toastr.error(errorMessage);
                            } else {
                                toastr.error(errorMessage);
                            }
                        }
                    },
                    error: function(xhr) {
                        console.log('AJAX error:', xhr);
                        $('#loadingModal').modal('hide');
                        $('#loadingModal').remove();

                        let errorMessage = '{{ helperTrans('ingaz.load_form_error') }}';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            errorMessage = orderTranslations.orderNotFound;
                        } else if (xhr.status === 500) {
                            errorMessage = orderTranslations.serverError;
                        } else if (xhr.status === 0) {
                            errorMessage = orderTranslations.serverConnectionFailed;
                        }

                        toastr.error(errorMessage);
                        console.error('Error details:', xhr);
                    }
                });
            });

            // Old change maid code removed - using new advanced system below
            /*
                                    $(document).on('click', '.change-maid', function() {
                                        const orderId = $(this).data('id');

                                        // Show modal to select new maid with search
                                        const modal = `
            <div class="modal fade" id="changeMaidModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.change_maid') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeMaidForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.search_maid') }}</label>
                                    <input type="text" class="form-control" id="maidSearch" placeholder="{{ helperTrans('ingaz.search_by_name_or_passport') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_maid') }}</label>
                                    <select class="form-select" name="maid_id" required>
                                        <option value="">{{ helperTrans('ingaz.select_maid') }}</option>
                                    </select>
                                </div>
                                <div id="maidInfo" class="alert alert-info" style="display: none;">
                                    <h6>{{ helperTrans('ingaz.maid_info') }}</h6>
                                    <div id="maidDetails"></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-primary" id="saveMaid">{{ helperTrans('ingaz.save') }}</button>
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
                                                toastr.error('{{ helperTrans('ingaz.please_select_maid') }}');
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
                                                        toastr.success('{{ helperTrans('ingaz.maid_updated') }}');
                                                    } else {
                                                        toastr.error(response.message || '{{ helperTrans('ingaz.update_error') }}');
                                                    }
                                                },
                                                error: function(xhr) {
                                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                                        toastr.error(xhr.responseJSON.message);
                                                    } else {
                                                        toastr.error('{{ helperTrans('ingaz.update_error') }}');
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
                                                    select.append('<option value="">{{ helperTrans('ingaz.select_maid') }}</option>');

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
                                                    select.append('<option value="">{{ helperTrans('ingaz.select_maid') }}</option>');

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
                        <p><strong>{{ helperTrans('ingaz.name') }}:</strong> ${maid.name}</p>
                        <p><strong>{{ helperTrans('ingaz.passport_num') }}:</strong> ${maid.passport_num}</p>
                        <p><strong>{{ helperTrans('ingaz.nationality') }}:</strong> ${maid.nationality}</p>
                        <p><strong>{{ helperTrans('ingaz.age') }}:</strong> ${maid.age}</p>
                        <p><strong>{{ helperTrans('ingaz.experience') }}:</strong> ${maid.experience}</p>
                    `);
                                                    $('#maidInfo').show();
                                                }
                                            }
                                        });
                                    }
                                    */

            // Handle change responsible employee
            $(document).on('click', '.change-responsible', function() {
                const orderId = $(this).data('id');

                // Show modal to select new responsible employee
                const modal = `
            <div class="modal fade" id="changeResponsibleModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ helperTrans('ingaz.change_responsible_employee') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changeResponsibleForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_employee') }}</label>
                                    <select class="form-select" name="admin_id" required>
                                        <option value="">{{ helperTrans('ingaz.select_employee') }}</option>
                                        @foreach (\App\Models\auth\Admin::query()->where('admin_type', 'customer_service')->where(function ($query) { $query->whereNull('status')->orWhere('status', 'active'); })->get() as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-primary" id="saveResponsible">{{ helperTrans('ingaz.save') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#changeResponsibleModal').remove();
                $('body').append(modal);
                $('#changeResponsibleModal').modal('show');

                // Handle modal close for change responsible modal
                $('#changeResponsibleModal .btn-close, #changeResponsibleModal [data-bs-dismiss="modal"]')
                    .on('click', function() {
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
                        // toastr.error('{{ helperTrans('ingaz.please_select_employee') }}');
                        Swal.fire(orderTranslations.errorTitle, '{{ helperTrans('ingaz.please_select_employee') }}',
                            'error');
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
                            if (response.data.success) {
                                $('#changeResponsibleModal').modal('hide');
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                dataTable.ajax.reload();
                                // toastr.success(
                                //     '{{ helperTrans('ingaz.responsible_updated') }}'
                                // );
                                Swal.fire(orderTranslations.successTitle, response.message ||
                                    '{{ helperTrans('ingaz.responsible_updated') }}',
                                    'success');
                            } else {
                                // toastr.error(response.message ||
                                //     '{{ helperTrans('ingaz.update_error') }}');
                                Swal.fire(orderTranslations.errorTitle, response.message ||
                                    '{{ helperTrans('ingaz.update_error') }}',
                                    'error');
                            }
                        },
                        error: function() {
                            // toastr.error('{{ helperTrans('ingaz.update_error') }}');
                            Swal.fire(orderTranslations.errorTitle,
                                '{{ helperTrans('ingaz.update_error') }}', 'error'
                                );
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
                            <h5 class="modal-title">{{ helperTrans('ingaz.refuse_return_order') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="refuseOrderForm">
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.select_action') }}</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="refused" id="refused">
                                        <label class="form-check-label" for="refused">
                                            {{ helperTrans('ingaz.refuse_order') }}
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" value="return" id="return">
                                        <label class="form-check-label" for="return">
                                            {{ helperTrans('ingaz.return_order') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ helperTrans('ingaz.reason') }}</label>
                                    <textarea class="form-control" name="reason" rows="3" placeholder="{{ helperTrans('ingaz.enter_reason') }}"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ helperTrans('ingaz.cancel') }}</button>
                            <button type="button" class="btn btn-danger" id="saveRefuse">{{ helperTrans('ingaz.confirm') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('#refuseOrderModal').remove();
                $('body').append(modal);
                $('#refuseOrderModal').modal('show');

                // Handle modal close for refuse order modal
                $('#refuseOrderModal .btn-close, #refuseOrderModal [data-bs-dismiss="modal"]').on('click',
                    function() {
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
                        toastr.error('{{ helperTrans('ingaz.please_select_action') }}');
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
                            if (response.data.success) {
                                dataTable.ajax.reload();
                                $('#refuseOrderModal').modal('hide');
                                $('#refuseOrderModal').remove();
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                dataTable.ajax.reload();
                                // toastr.success(
                                //     '{{ helperTrans('ingaz.order_updated') }}');
                                Swal.fire(orderTranslations.successTitle, response.message ||
                                    '{{ helperTrans('ingaz.order_updated') }}',
                                    'success');
                            } else {
                                dataTable.ajax.reload();
                                // toastr.error(
                                //     '{{ helperTrans('ingaz.update_error') }}');
                                Swal.fire(orderTranslations.errorTitle, response.message ||
                                    '{{ helperTrans('ingaz.update_error') }}',
                                    'error');
                            }
                        },
                        error: function() {
                            // toastr.error('{{ helperTrans('ingaz.update_error') }}');
                            Swal.fire(orderTranslations.errorTitle,
                                '{{ helperTrans('ingaz.update_error') }}', 'error'
                                );
                        }
                    });
                });
            });

            // Initialize tooltips
            function initTooltips() {
                $('[data-tooltip]').each(function() {
                    $(this).hover(
                        function() {
                            const tooltip = $('<div class="custom-tooltip">' + $(this).attr(
                                'data-tooltip') + '</div>');
                            $('body').append(tooltip);

                            const offset = $(this).offset();
                            tooltip.css({
                                position: 'absolute',
                                top: offset.top - tooltip.outerHeight() - 10,
                                left: offset.left + ($(this).outerWidth() / 2) - (tooltip
                                    .outerWidth() / 2),
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
                $('#dataTableObject tbody tr').removeClass('selected-row');
                initTooltips();

                // Clean up dropdown text
                $('.dropdown-item').each(function() {
                    let text = $(this).text();
                    // Remove any repeated "الإجراءات" text
                    text = text.replace(@json(helperTrans('ingaz.actions')), '').trim();
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
                $('#from_date, #to_date, #status, #job_type, #admin_id, #branch_id, #timer_status, #client_name, #phone, #passport_num, #office_name')
                    .val('');
                dataTable.ajax.reload();
            });

            // Export buttons
            $('#export_excel').on('click', function() {
                window.location.href = '{{ route('orders.export.excel') }}?' + $.param({
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    status: $('#status').val() || getUrlParameter('status'),
                    job_type: $('#job_type').val(),
                    nationality_id: $('#nationality_id').val(),
                    admin_id: $('#admin_id').val(),
                    branch_id: $('#branch_id').length ? $('#branch_id').val() : '',
                    timer_status: $('#timer_status').val(),
                    client_name: $('#client_name').val(),
                    phone: $('#phone').val(),
                    passport_num: $('#passport_num').val()
                    ,office_name: $('#office_name').val()
                });
            });

            $('#export_pdf').on('click', function() {
                window.location.href = '{{ route('orders.export.pdf') }}?' + $.param({
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    status: $('#status').val() || getUrlParameter('status'),
                    job_type: $('#job_type').val(),
                    nationality_id: $('#nationality_id').val(),
                    admin_id: $('#admin_id').val(),
                    branch_id: $('#branch_id').length ? $('#branch_id').val() : '',
                    timer_status: $('#timer_status').val(),
                    client_name: $('#client_name').val(),
                    phone: $('#phone').val(),
                    passport_num: $('#passport_num').val()
                    ,office_name: $('#office_name').val()
                });
            });

            // Handle action buttons
            $(document).on('click', '.addNewContract', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    window.location.href = '{{ route('RecruitmentContract.create') }}?orderId=' + orderId;
                } else {
                    console.error('Order ID not found!');
                    Swal.fire(orderTranslations.errorTitle, orderTranslations.orderIdNotFound, 'error');
                }
            });

            $(document).on('click', '.change-maid', function(e) {
                e.preventDefault();
                var orderId = $(this).data('id');

                if (orderId) {
                    // Show change maid modal
                    showChangeMaidModal(orderId);
                } else {
                    console.error('Order ID not found!');
                    Swal.fire(orderTranslations.errorTitle, orderTranslations.orderIdNotFound, 'error');
                }
            });

            // $(document).on('click', '.change-responsible', function(e) {
            //     e.preventDefault();
            //     var orderId = $(this).data('id');

            //     if (orderId) {
            //         // Show change responsible modal
            //         showChangeResponsibleModal(orderId);
            //     } else {
            //         console.error('Order ID not found!');
            //         Swal.fire('خطأ!', 'لم يتم العثور على معرف الطلب', 'error');
            //     }
            // });

            // $(document).on('click', '.refuse-order', function(e) {
            //     e.preventDefault();
            //     var orderId = $(this).data('id');

            //     Swal.fire({
            //         title: 'تأكيد رفض الطلب',
            //         text: 'هل أنت متأكد من رفض هذا الطلب؟',
            //         icon: 'warning',
            //         showCancelButton: true,
            //         confirmButtonColor: '#d33',
            //         cancelButtonColor: '#6c757d',
            //         confirmButtonText: 'نعم، ارفض الطلب',
            //         cancelButtonText: 'إلغاء'
            //     }).then((result) => {
            //         if (result.isConfirmed) {
            //             refuseOrder(orderId);
            //         }
            //     });
            // });


        });

        // Functions for modals and actions
        function showChangeMaidModal(orderId) {
            // Create and show the modal
            const modalHtml = `
        <div class="modal fade" id="changeMaidOrderModal" tabindex="-1" aria-labelledby="changeMaidOrderModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeMaidOrderModalLabel">
                            <i class="fas fa-exchange-alt"></i>
                            ${orderTranslations.changeMaidInOrderNumber.replace(':id', orderId)}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="changeMaidOrderForm">
                            <input type="hidden" id="order_id" value="${orderId}">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="maid_search_order" class="form-label">
                                            <i class="fas fa-search"></i>
                                            ${orderTranslations.searchAvailableMaids}
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="maid_search_order"
                                                   placeholder="${orderTranslations.searchByNameOrPassport}"
                                                   onkeyup="searchMaidsOrderWithDelay(this.value)"
                                                   onkeydown="handleSearchOrderKeydown(event)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="clearOrderSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">
                                            ${orderTranslations.searchAvailableMaidsHint}
                                        </small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="new_maid_id_order" class="form-label">
                                            <i class="fas fa-user-plus"></i>
                                            ${orderTranslations.selectNewMaid}
                                        </label>
                                        <select class="form-control" id="new_maid_id_order" name="new_maid_id" required>
                                            <option value="">${orderTranslations.selectNewMaidPlaceholder}</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            ${orderTranslations.availableMaidsLoading}
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            ${orderTranslations.previousMaidStatus}
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                   id="status_available_order" value="available" required>
                                            <label class="form-check-label" for="status_available_order">
                                                <i class="fas fa-check text-success"></i>
                                                ${orderTranslations.available}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                   id="status_unavailable_order" value="backout" required>
                                            <label class="form-check-label" for="status_unavailable_order">
                                                <i class="fas fa-ban text-danger"></i>
                                                ${orderTranslations.backout}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="reason_order" class="form-label">
                                    <i class="fas fa-comment"></i>
                                    ${orderTranslations.changeReasonOptional}
                                </label>
                                <textarea class="form-control" id="reason_order" name="reason" rows="3"
                                          placeholder="${orderTranslations.changeReasonPlaceholder}"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            ${orderTranslations.cancel}
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitChangeMaidOrder()">
                            <i class="fas fa-save"></i>
                            ${orderTranslations.saveChange}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

            // Remove existing modal if any
            $('#changeMaidOrderModal').remove();

            // Add modal to body
            $('body').append(modalHtml);

            // Show modal
            $('#changeMaidOrderModal').modal('show');

            // Load available maids after modal is shown
            setTimeout(function() {
                loadAvailableMaidsOrder();
            }, 300);
        }

        // تحميل العاملات المتاحات للطلبات
        function loadAvailableMaidsOrder(searchTerm = '') {
            console.log('Loading available maids for order with search term:', searchTerm);

            $.ajax({
                url: '{{ route('RecruitmentContract.availableMaids') }}',
                method: 'GET',
                data: {
                    search: searchTerm,
                    limit: 50
                },
                success: function(response) {
                    console.log('Maids loaded:', response);

                    if (response.success && response.maids) {
                        const select = $('#new_maid_id_order');
                        select.empty();
                        select.append(`<option value="">${orderTranslations.selectNewMaidPlaceholder}</option>`);

                        if (response.maids.length > 0) {
                            response.maids.forEach(function(maid) {
                                const optionText = `${maid.name} - ${maid.passport_num}` +
                                    (maid.nationality_name ? ` (${maid.nationality_name})` : '');
                                select.append(`<option value="${maid.id}">${optionText}</option>`);
                            });

                            select.siblings('.form-text').text(orderTranslations.availableMaidsLoaded.replace(':count', response.maids.length));
                        } else {
                            select.siblings('.form-text').text(orderTranslations.noAvailableMaids);
                        }
                    } else {
                        console.error('Failed to load maids:', response);
                        $('#new_maid_id_order').siblings('.form-text').text(orderTranslations.maidsLoadFailed);
                    }
                },
                error: function(xhr) {
                    console.error('Error loading maids:', xhr);
                    $('#new_maid_id_order').siblings('.form-text').text(orderTranslations.maidsLoadError);
                }
            });
        }

        // البحث في العاملات مع تأخير للطلبات
        let searchTimeoutOrder;

        function searchMaidsOrderWithDelay(searchTerm) {
            clearTimeout(searchTimeoutOrder);
            searchTimeoutOrder = setTimeout(function() {
                loadAvailableMaidsOrder(searchTerm);
            }, 300);
        }

        // التعامل مع الضغط على Enter في البحث للطلبات
        function handleSearchOrderKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const searchTerm = event.target.value;
                loadAvailableMaidsOrder(searchTerm);

                // فتح القائمة المنسدلة
                setTimeout(function() {
                    $('#new_maid_id_order').focus().click();
                }, 500);
            }
        }

        // مسح البحث للطلبات
        function clearOrderSearch() {
            $('#maid_search_order').val('');
            loadAvailableMaidsOrder('');
        }

        // إرسال نموذج تغيير العاملة للطلبات
        function submitChangeMaidOrder() {
            const form = document.getElementById('changeMaidOrderForm');
            const formData = new FormData(form);
            const orderId = document.getElementById('order_id').value;

            // التحقق من البيانات
            if (!formData.get('new_maid_id')) {
                Swal.fire(orderTranslations.errorTitle, orderTranslations.selectNewMaidRequired, 'error');
                return;
            }

            if (!formData.get('old_maid_status')) {
                Swal.fire(orderTranslations.errorTitle, orderTranslations.selectPreviousMaidStatusRequired, 'error');
                return;
            }

            // تأكيد العملية
            Swal.fire({
                title: orderTranslations.confirmChangeMaidTitle,
                text: orderTranslations.confirmChangeMaidText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: orderTranslations.yesChangeMaid,
                cancelButtonText: orderTranslations.cancel
            }).then((result) => {
                if (result.isConfirmed) {
                    // إرسال البيانات
                    $.ajax({
                        url: `{{ route('orders.changeMaidById', ':id') }}`.replace(':id', orderId),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            maid_id: formData.get('new_maid_id'),
                            old_maid_status: formData.get('old_maid_status'),
                            reason: formData.get('reason')
                        },
                        success: function(response) {
                            console.log('Change maid response:', response);

                            if (response.success) {
                                Swal.fire(orderTranslations.successTitle, response.message || orderTranslations.maidChangedSuccessfully,
                                    'success');
                                $('#changeMaidOrderModal').modal('hide');
                                dataTable.ajax.reload();
                            } else {
                                Swal.fire(orderTranslations.errorTitle, response.message || orderTranslations.maidChangeFailed, 'error');
                            }
                        },
                        error: function(xhr) {
                            console.error('Error changing maid:', xhr);
                            let message = orderTranslations.maidChangeError;
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            Swal.fire(orderTranslations.errorTitle, message, 'error');
                        }
                    });
                }
            });
        }

        function showChangeResponsibleModal(orderId) {
            // This function can be implemented similar to change maid
            Swal.fire(orderTranslations.comingSoon, orderTranslations.featureUnderDevelopment, 'info');
        }

        function refuseOrder(orderId) {
            // This function can be implemented to refuse orders
            Swal.fire(orderTranslations.comingSoon, orderTranslations.featureUnderDevelopment, 'info');
        }

        function deleteOrder(orderId) {
            // This function can be implemented to delete orders
            Swal.fire(orderTranslations.comingSoon, orderTranslations.featureUnderDevelopment, 'info');
        }
    </script>
@endsection
