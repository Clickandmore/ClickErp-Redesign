@extends('admin.layout.indexs.index')
@section("style")
    <style>
        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            width: 120px;
            min-width: 120px;
            text-align: center;
            white-space: nowrap;
        }

        #dataTableObject .admin-actions-dropdown {
            display: inline-flex;
            justify-content: center;
        }

        #dataTableObject .admin-actions-dropdown .actions-btn {
            min-width: 104px;
            border-radius: 6px;
            padding: 0.35rem 0.7rem;
            font-size: 0.82rem;
            line-height: 1.4;
        }

        #dataTableObject .admin-actions-dropdown .dropdown-menu {
            min-width: 170px;
            padding: 0.35rem;
            border: 0;
            border-radius: 8px;
        }

        #dataTableObject .admin-actions-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 6px;
            padding: 0.55rem 0.65rem;
            font-size: 0.86rem;
            text-align: start;
        }

        #dataTableObject .admin-actions-dropdown .dropdown-item i {
            width: 16px;
            text-align: center;
        }
    </style>
@endsection
@section('page-title')
    {{__("auth.users")}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>
@endsection
@section('content')

    @if(@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))

        @if(@AdminGuardHasPerm(\App\PermKeys::USERS_CONTROL))
            {!! addButton(route("admins.create") , __("auth.admin")) !!}
        @endif

        <div class="card mb-3">
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

        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th>{{ __('auth.SL') }}</th>
                <th>{{__("auth.name")}}</th>
                <th>{{helperTrans("ingaz.admin_type")}}</th>
                <th>{{helperTrans("ingaz.role")}}</th>
                <th>رقم الاتصال</th>
                <th>رقم الواتساب</th>
                <th>رقم التحقق</th>
                <th>{{__("auth.email")}}</th>
                <th>{{helperTrans("ingaz.sex_type")}}</th>
                <th>{{ __('admin.status') }}</th>
                <th>{{ __('admin.note') }}</th>
                <th>{{__("auth.actions")}}</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

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
                {"data": "admin_type", orderable: true, searchable: true},
                {"data": "role", orderable: true, searchable: true},
                {"data": "phone", orderable: true, searchable: true},
                {"data": "whatsapp", orderable: true, searchable: true},
                {"data": "verification_phone", orderable: true, searchable: true},
                {"data": "email", orderable: true, searchable: true},
                {"data": "sex_type", orderable: false, searchable: false},
                {"data": "status", orderable: true, searchable: false},
                {"data": "note", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];

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
                showDataTable(baseUrl + (params.toString() ? '?' + params.toString() : ''), columns);
                tableInstance = $("#dataTableObject").DataTable();
                tableInstance.order([0, 'asc']).draw();
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

            function deleteAdmin(button, isPermanent) {
                const adminId = Number(button.data('id'));
                const deleteRoute = button.data('delete-route');
                const adminName = button.data('name') || '';
                const requiresReassignment = Number(button.data('requires-reassignment')) === 1;
                const ordersCount = Number(button.data('orders-count')) || 0;
                const bookingsCount = Number(button.data('bookings-count')) || 0;
                const contractsCount = Number(button.data('contracts-count')) || 0;
                const replacementOptions = {};

                reassignmentAdmins.forEach(function (admin) {
                    if (Number(admin.id) !== adminId) {
                        replacementOptions[admin.id] = admin.name;
                    }
                });

                if (requiresReassignment && Object.keys(replacementOptions).length === 0) {
                    const noEmployeeMessage = 'لا يوجد موظف نشط آخر يمكن إسناد الطلبات والعقود له.';
                    if (adminSwal && typeof adminSwal.fire === 'function') {
                        adminSwal.fire('تعذر الحذف', noEmployeeMessage, 'error');
                    } else {
                        alert(noEmployeeMessage);
                    }
                    return;
                }

                const reassignmentText = requiresReassignment
                    ? ' الأعمال المرتبطة: الطلبات ' + ordersCount + '، الحجوزات ' + bookingsCount + '، العقود ' + contractsCount + '. يجب اختيار موظف بديل.'
                    : ' لا توجد أعمال مرتبطة، واختيار موظف بديل اختياري.';

                if (!adminSwal || typeof adminSwal.fire !== 'function') {
                    if (!window.confirm(isPermanent ? 'تأكيد الحذف النهائي؟' : 'تأكيد حذف الموظف؟')) {
                        return;
                    }
                }

                const confirmation = adminSwal && typeof adminSwal.fire === 'function'
                    ? adminSwal.fire({
                        title: isPermanent ? 'حذف نهائي للموظف' : 'حذف الموظف',
                        text: isPermanent
                            ? 'سيتم حذف ' + adminName + ' نهائياً.' + reassignmentText
                            : 'سيتم حذف ' + adminName + ' ويمكن استعادته لاحقاً من فلتر المحذوفين.' + reassignmentText,
                        icon: 'warning',
                        input: 'select',
                        inputOptions: replacementOptions,
                        inputPlaceholder: 'اختر الموظف البديل عند وجود أعمال مرتبطة',
                        showCancelButton: true,
                        confirmButtonColor: '#DD6B55',
                        confirmButtonText: isPermanent ? 'حذف نهائي' : 'حذف',
                        cancelButtonText: "{{ __('buttons.cancel') }}",
                        inputValidator: function (value) {
                            if (requiresReassignment && !value) {
                                return 'اختر موظفاً بديلاً لإسناد الطلبات والعقود له.';
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
                                    title: data.message || (isPermanent ? 'تم حذف الموظف نهائياً' : 'تم حذف الموظف'),
                                    confirmButtonText: "{{ __('buttons.close') }}",
                                });
                            }
                            reloadWithFilters();
                        },
                        error: function (errorObj) {
                            if (errorObj.responseJSON && errorObj.responseJSON.requires_reassignment) {
                                if (adminSwal && typeof adminSwal.fire === 'function') {
                                    adminSwal.fire('تنبيه', errorObj.responseJSON.message, 'warning');
                                } else {
                                    alert(errorObj.responseJSON.message);
                                }
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
