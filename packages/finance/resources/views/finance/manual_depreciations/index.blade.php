@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    {!! addButton($createRoute , $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th> {{ __("finance::finance.principle") }} </th>
            <th> {{ __("finance::finance.value") }} </th>
            <th> {{ __("finance::finance.date") }} </th>
            <th>{{__("finance::finance.actions")}}</th>
        </tr>
        </thead>
        <tbody>

        </tbody>
    </table>


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                {"data": "principle_id", orderable: false, searchable: true},
                {"data": "value", orderable: false, searchable: true},
                {"data": "date", orderable: false, searchable: true},
                {"data": "actions", orderable: false, searchable: false}
            ];
            showDataTable("{{ $dataTableRoute }}", columns);
        });

        $(document).ajaxComplete(function() {
            // عند تغيير القائمة المنسدلة
            $('#principle_id').on('change', function() {
                // جلب القيم من الخيار المختار
                const selectedOption = $(this).find('option:selected');
                const perishedAccountId = selectedOption.data('perished_account_id');
                const perishedAccountName = selectedOption.data('perished_account_name');
                const expenseAccountId = selectedOption.data('expense_account_id');
                const expenseAccountName = selectedOption.data('expense_account_name');
                const accountId = selectedOption.data('account_id');

                // تحديث الحقول المخفية والنصية
                $('#perished_account_id').val(perishedAccountId);
                $('#expense_account_id').val(expenseAccountId);
                $('#account_id').val(accountId);

                // تحديث الحقول النصية إذا كانت هناك أسماء معروفة
                $('#perished_account_name').val(perishedAccountId ? `${perishedAccountName}` : '');
                $('#expense_account_name').val(expenseAccountId ? `${expenseAccountName}` : '');
            });
        });

    </script>
@endsection
