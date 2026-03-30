@extends('admin.layout.indexs.index')
@section("style")
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
