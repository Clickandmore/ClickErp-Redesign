@extends('admin.layout.indexs.index')
@section("style")
    <style>
        .admins-management-page {
            --admin-actions-width: 172px;
            --admin-border: #e5e7eb;
            --admin-soft-bg: #f8fafc;
            --admin-text: #111827;
            --admin-muted: #6b7280;
        }

        .admins-management-page .admins-toolbar {
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .admins-management-page .admins-toolbar .form-label {
            color: var(--admin-text);
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .admins-management-page .admins-toolbar .form-select,
        .admins-management-page .admins-toolbar .btn {
            min-height: 42px;
            border-radius: 6px;
        }

        .admins-management-page .admins-table-shell {
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .admins-management-page #dataTableObject_wrapper {
            padding: 1rem;
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        .admins-management-page .dataTables_wrapper .row:first-child,
        .admins-management-page .dataTables_wrapper .row:last-child {
            align-items: center;
            row-gap: 0.75rem;
        }

        .admins-management-page .dataTables_filter input,
        .admins-management-page .dataTables_length select {
            border-radius: 6px;
            border-color: var(--admin-border);
            min-height: 38px;
        }

        #dataTableObject {
            border-collapse: separate !important;
            border-spacing: 0;
            min-width: 1420px;
        }

        #dataTableObject thead th {
            background: var(--admin-soft-bg) !important;
            color: var(--admin-text) !important;
            border-top: 1px solid var(--admin-border);
            border-bottom: 1px solid var(--admin-border) !important;
            font-weight: 800;
            vertical-align: middle;
            white-space: nowrap;
        }

        #dataTableObject tbody td {
            color: #374151;
            vertical-align: middle;
            background: #fff;
        }

        #dataTableObject tbody tr:hover td {
            background: #f9fbff;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 58px;
            min-width: 58px;
            text-align: center;
            color: var(--admin-muted);
        }

        #dataTableObject td:nth-child(2) {
            min-width: 170px;
            font-weight: 700;
            color: var(--admin-text);
        }

        #dataTableObject td:nth-child(3),
        #dataTableObject td:nth-child(6),
        #dataTableObject td:nth-child(7),
        #dataTableObject td:nth-child(8),
        #dataTableObject td:nth-child(9),
        #dataTableObject td:nth-child(12) {
            white-space: nowrap;
        }

        #dataTableObject td:nth-child(13) {
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #dataTableObject .badge {
            min-width: 68px;
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            font-size: 0.78rem;
        }

        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            width: var(--admin-actions-width);
            min-width: var(--admin-actions-width);
            text-align: center;
            white-space: nowrap;
        }

        #dataTableObject .admin-actions-dropdown {
            display: inline-flex;
            justify-content: center;
            width: 100%;
        }

        #dataTableObject .admin-actions-dropdown .actions-btn {
            width: 132px;
            border-radius: 6px;
            padding: 0.48rem 0.75rem;
            font-size: 0.86rem;
            font-weight: 800;
            line-height: 1.3;
            box-shadow: 0 6px 14px rgba(39, 148, 235, 0.2);
        }

        #dataTableObject .admin-actions-template {
            display: none !important;
        }

        .admin-actions-menu {
            position: fixed;
            width: 260px;
            max-width: calc(100vw - 24px);
            max-height: min(430px, calc(100vh - 24px));
            overflow-y: auto;
            background: #fff;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
            padding: 0.45rem;
            margin: 0;
            list-style: none;
            z-index: 2147483000;
        }

        .admin-actions-menu li {
            list-style: none;
        }

        .admin-actions-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            width: 100%;
            border-radius: 6px;
            border: 0;
            background: transparent;
            color: #374151;
            padding: 0.72rem 0.78rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-align: start;
            text-decoration: none;
            cursor: pointer;
        }

        .admin-actions-menu .dropdown-item:hover,
        .admin-actions-menu .dropdown-item:focus {
            background: #f3f6fb;
            color: #111827;
        }

        .admin-actions-menu .dropdown-item i {
            width: 18px;
            text-align: center;
        }

        .admin-activity-icon {
            width: 36px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            padding: 0;
        }

        .admin-activity-details {
            text-align: start;
            display: grid;
            gap: 0.65rem;
        }

        .admin-activity-details .activity-row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 0.75rem;
            align-items: start;
            border-bottom: 1px solid #edf0f4;
            padding-bottom: 0.55rem;
        }

        .admin-activity-details .activity-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .admin-activity-details .activity-label {
            color: #6b7280;
            font-weight: 800;
        }

        .admin-activity-details .activity-value {
            color: #111827;
            font-weight: 700;
            word-break: break-word;
        }

        @media (max-width: 767.98px) {
            .admins-management-page #dataTableObject_wrapper {
                padding: 0.75rem;
            }

            #dataTableObject {
                min-width: 1180px;
            }

            #dataTableObject th:last-child,
            #dataTableObject td:last-child {
                width: 150px;
                min-width: 150px;
            }
        }
    </style>
@endsection
@section('page-title')
    {{__("auth.users")}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("auth.admins")}}</li>
@endsection
@section('content')

    @if(@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
        <div class="admins-management-page">

        @if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))
            {!! addButton(route("admins.create") , __("auth.admin")) !!}
        @endif

        <div class="card admins-toolbar mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">{{ helperTrans('ingaz.admin_type') }}</label>
                        <select id="filter_admin_type" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            @foreach(($filters['admin_type'] ?? []) as $key => $label)
                                <option value="{{ $key }}">{{ $label[app()->getLocale()] ?? $key }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ helperTrans('ingaz.sex_type') }}</label>
                        <select id="filter_sex_type" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="0">{{ __('admin.male') }}</option>
                            <option value="1">{{ __('admin.female') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('admin.status') }}</label>
                        <select id="filter_status" class="form-select">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="active">{{ __('admin.active') }}</option>
                            <option value="suspended">{{ __('admin.suspended') }}</option>
                            <option value="deleted">{{ __('ingaz.deleted') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button id="applyFilters" class="btn btn-primary w-50">{{ __('admin.filter') }}</button>
                        <button id="resetFilters" class="btn btn-outline-secondary w-50">{{ __('admin.reset') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="admins-table-shell">
            <table id="dataTableObject" class="table display dataTable table-hover align-middle" style="width:100%">
                <thead>
                <tr>
                    <th>{{ __('auth.SL') }}</th>
                    <th>{{__("auth.name")}}</th>
                    <th>{{ __('admin.username') }}</th>
                    <th>{{helperTrans("ingaz.admin_type")}}</th>
                    <th>{{helperTrans("ingaz.role")}}</th>
                    <th>{{ __('admin.contact_phone') }}</th>
                    <th>{{ __('admin.whatsapp_phone') }}</th>
                    <th>{{ __('admin.verification_phone') }}</th>
                    <th>{{__("auth.email")}}</th>
                    <th>{{helperTrans("ingaz.sex_type")}}</th>
                    <th>{{ __('admin.status') }}</th>
                    <th>{{ __('admin.activity') }}</th>
                    <th>{{ __('admin.note') }}</th>
                    <th>{{__("auth.actions")}}</th>
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
            let baseUrl = "{{route("admins.index")}}";
            const restoreAdminRouteTemplate = "{{ route('admins.restore', ['id' => 'ADMIN_ID']) }}";
            const reassignmentAdmins = @json($reassignmentAdmins->map(fn($admin) => ['id' => $admin->id, 'name' => $admin->name])->values());
            const adminSwal = window.Swal || window.swal;
            let tableInstance;

            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "name", orderable: true, searchable: true},
                {"data": "username", orderable: true, searchable: true},
                {"data": "admin_type", orderable: true, searchable: true},
                {"data": "role", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "whatsapp", orderable: true, searchable: true},
                {"data": "verification_phone", orderable: true, searchable: true},
                {"data": "email", orderable: true, searchable: true},
                {"data": "sex_type", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: false},
                {"data": "activity_summary", orderable: false, searchable: false},
                {"data": "note", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];

            function dataTableLanguage() {
                return {
                    "sProcessing": "{{trans('dataTable.sProcessing')}}",
                    "sLengthMenu": "{{trans('dataTable.sLengthMenu')}}",
                    "sZeroRecords": "{{trans('dataTable.sZeroRecords')}}",
                    "sInfo": "{{trans('dataTable.sInfo')}}",
                    "sInfoEmpty": "{{trans('dataTable.sInfoEmpty')}}",
                    "sInfoFiltered": "{{trans('dataTable.sInfoFiltered')}}",
                    "sInfoPostFix": "",
                    "sSearch": "{{trans('dataTable.sSearch')}}:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "{{trans('dataTable.sFirst')}}",
                        "sPrevious": "{{trans('dataTable.sPrevious')}}",
                        "sNext": "{{trans('dataTable.sNext')}}",
                        "sLast": "{{trans('dataTable.sLast')}}"
                    }
                };
            }

            function closeAdminActionsMenu() {
                $('#adminActionsMenu').remove();
                $('#dataTableObject .actions-btn').attr('aria-expanded', 'false');
            }

            function initAdminUsersTable(url) {
                tableInstance = $("#dataTableObject").DataTable({
                    responsive: false,
                    autoWidth: false,
                    ordering: true,
                    searching: true,
                    iDisplayLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "{{ __('admin.all') }}"]],
                    processing: true,
                    serverSide: false,
                    ajax: url,
                    columns: columns,
                    language: dataTableLanguage(),
                    order: [[0, "asc"]],
                    drawCallback: function () {
                        closeAdminActionsMenu();
                    }
                });
                $("#dataTableObject_wrapper").off('scroll.adminActions').on('scroll.adminActions', closeAdminActionsMenu);
            }

            function reloadWithFilters() {
                let params = new URLSearchParams();
                let adminType = $('#filter_admin_type').val();
                let sexType = $('#filter_sex_type').val();
                let status = $('#filter_status').val();
                if (adminType) params.append('admin_type', adminType);
                if (sexType !== '') params.append('sex_type', sexType);
                if (status) params.append('status', status);
                if (tableInstance) {
                    $("#dataTableObject").DataTable().clear().destroy();
                }
                initAdminUsersTable(baseUrl + (params.toString() ? '?' + params.toString() : ''));
            }

            reloadWithFilters();

            $('#applyFilters').on('click', function () {
                reloadWithFilters();
            });

            $('#resetFilters').on('click', function () {
                $('#filter_admin_type').val('');
                $('#filter_sex_type').val('');
                $('#filter_status').val('');
                reloadWithFilters();
            });

            $(document).on('click', '#dataTableObject .actions-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $button = $(this);
                const $dropdown = $button.closest('.admin-actions-dropdown');
                const $sourceMenu = $dropdown.find('.admin-actions-template').first();
                const existingMenu = $('#adminActionsMenu');

                if (existingMenu.length && Number(existingMenu.data('source-id')) === Number($button.closest('tr').index())) {
                    closeAdminActionsMenu();
                    return;
                }

                closeAdminActionsMenu();

                const menu = $('<ul id="adminActionsMenu" class="admin-actions-menu"></ul>').html($sourceMenu.html());
                menu.data('source-id', $button.closest('tr').index());
                $('body').append(menu);

                const rect = $button[0].getBoundingClientRect();
                const menuWidth = menu.outerWidth();
                const menuHeight = menu.outerHeight();
                const margin = 12;
                const isRtl = $('html').attr('dir') === 'rtl';
                let top = rect.bottom + 8;
                let left = isRtl ? rect.left : rect.right - menuWidth;

                if (top + menuHeight > window.innerHeight - margin) {
                    top = rect.top - menuHeight - 8;
                }

                if (top < margin) {
                    top = margin;
                }

                if (left + menuWidth > window.innerWidth - margin) {
                    left = window.innerWidth - menuWidth - margin;
                }

                if (left < margin) {
                    left = margin;
                }

                menu.css({
                    top: top + 'px',
                    left: left + 'px'
                });
                $button.attr('aria-expanded', 'true');
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#adminActionsMenu, #dataTableObject .admin-actions-dropdown').length) {
                    closeAdminActionsMenu();
                }
            });

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeAdminActionsMenu();
                }
            });

            $(document).on('click', '#adminActionsMenu .dropdown-item', function () {
                setTimeout(closeAdminActionsMenu, 120);
            });

            $(window).on('resize scroll', closeAdminActionsMenu);

            function escapeHtml(value) {
                return $('<div>').text(value || '-').html();
            }

            function activityRow(label, value) {
                return '<div class="activity-row">'
                    + '<div class="activity-label">' + escapeHtml(label) + '</div>'
                    + '<div class="activity-value">' + escapeHtml(value) + '</div>'
                    + '</div>';
            }

            $(document).on('click', '.admin-activity-button', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const button = $(this);
                const isOnline = Number(button.data('online')) === 1;
                const html = '<div class="admin-activity-details">'
                    + activityRow("{{ __('admin.online_status') }}", button.data('status'))
                    + activityRow("{{ __('admin.last_login_at') }}", button.data('last-login'))
                    + activityRow("{{ __('admin.last_activity_at') }}", button.data('last-activity'))
                    + activityRow("{{ __('admin.login_ip') }}", button.data('ip'))
                    + activityRow("{{ __('admin.device') }}", button.data('device'))
                    + '</div>';

                if (adminSwal && typeof adminSwal.fire === 'function') {
                    adminSwal.fire({
                        title: "{{ __('admin.activity') }} - " + button.data('name'),
                        html: html,
                        icon: isOnline ? 'success' : 'info',
                        confirmButtonText: "{{ __('buttons.close') }}",
                        width: 620
                    });
                    return;
                }

                alert([
                    "{{ __('admin.online_status') }}: " + button.data('status'),
                    "{{ __('admin.last_login_at') }}: " + button.data('last-login'),
                    "{{ __('admin.last_activity_at') }}: " + button.data('last-activity'),
                    "{{ __('admin.login_ip') }}: " + button.data('ip'),
                    "{{ __('admin.device') }}: " + button.data('device')
                ].join("\n"));
            });

            $(document).on('click', '.toggle-status', function (e) {
                e.preventDefault();
                let id = $(this).data('id');
                let note = prompt("{{ __('admin.enter_suspend_note') }}");
                $.post({
                    url: $(this).attr('href'),
                    data: {
                        _token: "{{ csrf_token() }}",
                        note: note
                    },
                    success: function () {
                        reloadWithFilters();
                    },
                    error: handleErrorResponce
                });
            });

            $(document).on('click', '.restore-admin', function (e) {
                e.preventDefault();
                let adminId = $(this).data('id');
                let restoreUrl = restoreAdminRouteTemplate.replace('ADMIN_ID', adminId);

                $.post({
                    url: restoreUrl,
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function () {
                        reloadWithFilters();
                    },
                    error: handleErrorResponce
                });
            });

            function deleteAdmin(button, isPermanent, forcedCounts) {
                const adminId = Number(button.data('id'));
                const deleteRoute = button.data('delete-route');
                const adminName = button.data('name') || '';
                const requiresReassignment = forcedCounts ? true : Number(button.data('requires-reassignment')) === 1;
                const ordersCount = forcedCounts ? Number(forcedCounts.orders) || 0 : Number(button.data('orders-count')) || 0;
                const bookingsCount = forcedCounts ? Number(forcedCounts.bookings) || 0 : Number(button.data('bookings-count')) || 0;
                const contractsCount = forcedCounts ? Number(forcedCounts.contracts) || 0 : Number(button.data('contracts-count')) || 0;
                const replacementOptions = {};

                reassignmentAdmins.forEach(function (admin) {
                    if (Number(admin.id) !== adminId) {
                        replacementOptions[admin.id] = admin.name;
                    }
                });

                if (requiresReassignment && Object.keys(replacementOptions).length === 0) {
                    const noEmployeeMessage = "{{ __('admin.no_active_reassignment_employee') }}";
                    if (adminSwal && typeof adminSwal.fire === 'function') {
                        adminSwal.fire("{{ __('admin.delete_blocked') }}", noEmployeeMessage, 'error');
                    } else {
                        alert(noEmployeeMessage);
                    }
                    return;
                }

                const reassignmentText = requiresReassignment
                    ? "{{ __('admin.reassignment_required_text') }}"
                        .replace(':orders', ordersCount)
                        .replace(':bookings', bookingsCount)
                        .replace(':contracts', contractsCount)
                    : "{{ __('admin.reassignment_optional_text') }}";

                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    if (!window.confirm(isPermanent ? "{{ __('admin.confirm_force_delete_employee') }}" : "{{ __('admin.confirm_delete_employee') }}")) {
                        return;
                    }
                }

                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire({
                        title: isPermanent ? "{{ __('admin.force_delete_employee') }}" : "{{ __('admin.delete_employee') }}",
                        text: isPermanent
                            ? "{{ __('admin.force_delete_employee_text') }}".replace(':name', adminName) + reassignmentText
                            : "{{ __('admin.delete_employee_text') }}".replace(':name', adminName) + reassignmentText,
                        icon: 'warning',
                        input: 'select',
                        inputOptions: replacementOptions,
                        inputPlaceholder: "{{ __('admin.select_replacement_employee_placeholder') }}",
                        showCancelButton: true,
                        confirmButtonColor: '#DD6B55',
                        confirmButtonText: isPermanent ? "{{ __('admin.force_delete') }}" : "{{ __('admin.delete') }}",
                        cancelButtonText: "{{ __('buttons.cancel') }}",
                        inputValidator: function (value) {
                            if (requiresReassignment && !value) {
                                return "{{ __('admin.replacement_employee_required') }}";
                            }

                            return null;
                        }
                    })
                    : Promise.resolve({isConfirmed: true, value: ''});

                confirmation.then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: deleteRoute,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}",
                            reassigned_admin_id: result.value || ''
                        },
                        success: function (data) {
                            handleSuccessResponce(data);
                            if (adminSwal && typeof adminSwal.fire === 'function') {
                                adminSwal.fire({
                                    icon: 'success',
                                    title: data.message || (isPermanent ? "{{ __('admin.employee_force_deleted_successfully') }}" : "{{ __('admin.employee_deleted_successfully') }}"),
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                            }
                            reloadWithFilters();
                        },
                        error: function (errorObj) {
                            if (errorObj.responseJSON && errorObj.responseJSON.requires_reassignment) {
                                deleteAdmin(button, isPermanent, errorObj.responseJSON.assignment_counts || {});
                                return;
                            }

                            handleErrorResponce(errorObj);
                        }
                    });
                });
            }

            $(document).on('click', '.admin-delete-button', function (e) {
                e.preventDefault();
                deleteAdmin($(this), false);
            });

            $(document).on('click', '.admin-force-delete-button', function (e) {
                e.preventDefault();
                deleteAdmin($(this), true);
            });

            function postAdminAction(url, data, successMessage) {
                $.post({
                    url: url,
                    data: Object.assign({_token: "{{ csrf_token() }}"}, data || {}),
                    success: function (response) {
                        handleSuccessResponce(response);
                        if (adminSwal && typeof adminSwal.fire === 'function') {
                            adminSwal.fire({
                                icon: 'success',
                                title: response.message || successMessage,
                                confirmButtonText: "{{ __('buttons.close') }}",
                            });
                        }
                        reloadWithFilters();
                    },
                    error: handleErrorResponce
                });
            }

            $(document).on('click', '.admin-terminate-session-button', function (e) {
                e.preventDefault();
                const url = $(this).data('terminate-route');
                const name = $(this).data('name') || '';
                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire({
                        title: "{{ __('admin.terminate_employee_session') }}",
                        text: "{{ __('admin.terminate_employee_session_text') }}".replace(':name', name),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: "{{ __('admin.terminate_session') }}",
                        cancelButtonText: "{{ __('buttons.cancel') }}",
                    })
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.confirm_terminate_employee_session') }}")});

                confirmation.then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, {}, "{{ __('admin.employee_session_terminated') }}");
                    }
                });
            });

            $(document).on('click', '.admin-send-reset-link-button', function (e) {
                e.preventDefault();
                postAdminAction($(this).data('reset-link-route'), {}, "{{ __('admin.password_link_sent_successfully') }}");
            });

            $(document).on('click', '.admin-impersonate-button', function (e) {
                e.preventDefault();
                const url = $(this).data('impersonate-route');
                const name = $(this).data('name') || '';
                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire({
                        title: "{{ __('admin.impersonate_employee') }}",
                        text: "{{ __('admin.impersonate_employee_text') }}".replace(':name', name),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: "{{ __('admin.login_action') }}",
                        cancelButtonText: "{{ __('buttons.cancel') }}",
                    })
                    : Promise.resolve({isConfirmed: window.confirm("{{ __('admin.confirm_impersonate_employee') }}")});

                confirmation.then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.post({
                        url: url,
                        data: {_token: "{{ csrf_token() }}"},
                        success: function (response) {
                            window.location.href = response.data.url;
                        },
                        error: handleErrorResponce
                    });
                });
            });

            $(document).on('click', '.admin-reset-password-button', function (e) {
                e.preventDefault();
                const url = $(this).data('reset-route');
                const name = $(this).data('name') || '';

                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    const password = window.prompt("{{ __('admin.new_password') }}");
                    if (password) {
                        postAdminAction(url, {
                            password: password,
                            password_confirmation: password
                        }, "{{ __('admin.password_changed_sessions_terminated') }}");
                    }
                    return;
                }

                adminSwal.fire({
                    title: "{{ __('admin.change_password') }}",
                    html: '<input id="adminPassword" type="password" class="swal2-input" placeholder="{{ __('admin.new_password') }}">'
                        + '<input id="adminPasswordConfirmation" type="password" class="swal2-input" placeholder="{{ __('admin.new_password_confirmation') }}">',
                    text: name,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('buttons.save') }}",
                    cancelButtonText: "{{ __('buttons.cancel') }}",
                    preConfirm: function () {
                        const password = document.getElementById('adminPassword').value;
                        const confirmation = document.getElementById('adminPasswordConfirmation').value;

                        if (!password || password.length < 6) {
                            adminSwal.showValidationMessage("{{ __('admin.password_min_six') }}");
                            return false;
                        }

                        if (password !== confirmation) {
                            adminSwal.showValidationMessage("{{ __('admin.password_confirmation_mismatch') }}");
                            return false;
                        }

                        return {password: password, password_confirmation: confirmation};
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        postAdminAction(url, result.value, "{{ __('admin.password_changed_sessions_terminated') }}");
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ajaxComplete(function () {
            $('.all').click(function () {
                $(this).closest('div').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
            });
        });
    </script>
@endsection
