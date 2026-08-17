@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="alert alert-info mb-3">
        {{ __('access-control::sidebar.package_permissions') }}
    </div>
    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('auth.SL') }}</th>
            <th>{{ __("auth.name") }}</th>
            <th>{{ __("auth.actions") }}</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "name", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });
    </script>
    <script>
        $(document).ajaxComplete(function() {
            $('#admin_type').change(function() {
                var adminType = $(this).val();

                if (adminType == 'department_manger') {
                    $('#department_div').show();
                    $('#employee_div').show();
                } else if (adminType == 'hr_employee' || adminType == 'employee') {
                    $('#department_div').hide();
                    $('#employee_div').show();
                } else {
                    $('#department_div').hide();
                    $('#employee_div').hide();
                }
            });
        });
    </script>
    <script>
        $(document).ajaxComplete(function () {
            $('input[name="permissions[]"]').on('change', function() {
                var model = $(this).val().split('-')[0];
                var $row = $(this).closest('tr');
                var totalPermissions = $row.find('input[name="permissions[]"]').length;
                var checkedPermissions = $row.find('input[name="permissions[]"]:checked').length;

                if (totalPermissions === checkedPermissions) {
                    $row.find('input.all[data-model="' + model + '"]').prop('checked', true);
                } else {
                    $row.find('input.all[data-model="' + model + '"]').prop('checked', false);
                }
            });

            $('.all').on('change', function() {
                var model = $(this).data('model');
                var isChecked = $(this).is(':checked');
                var $row = $(this).closest('tr');

                $row.find('input[name="permissions[]"]').each(function() {
                    var valueWithoutLastSegment = $(this).val().split('-').slice(0, -1).join('-');
                    if (valueWithoutLastSegment === model) {
                        $(this).prop('checked', isChecked).trigger('change');
                    }
                });
            });

            $('.all').each(function() {
                var model = $(this).data('model');
                var $row = $(this).closest('tr');
                var allChecked = true;

                $row.find('input[name="permissions[]"]').each(function() {
                    var valueWithoutLastPart = $(this).val().split('-').slice(0, -1).join('-');
                    if (valueWithoutLastPart === model && !$(this).is(':checked')) {
                        allChecked = false;
                    }
                });

                $(this).prop('checked', allChecked);
            });

            $('.check-all-table').on('change', function() {
                var isChecked = $(this).is(':checked');
                var $table = $(this).closest('.permissions-table');

                $table.find('input[name="permissions[]"], input.all').prop('checked', isChecked);
            });
        });
    </script>
@endsection
