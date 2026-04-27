@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <label for="branch_admin_id">{{ __('finance::finance.client-categories') }}</label>
            <select id="category_id" name="category_id" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach (\DB::table('fn_client_categories')->get() ?? [] as $category_id)
                    <option value="{{ $category_id->id }}">{{ $category_id->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mt-3">
            {!! addButton($createRoute, $addButtonText) !!}
        </div>
    </div>


    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.code') }}</th>
                <th>{{ __('finance::finance.name') }}</th>
                <th>{{ __('finance::finance.phone') }}</th>
                <th>{{ __('finance::finance.tax_number') }}</th>
                <th>{{ __('finance::finance.current_balance') }}</th>
                <th>{{ __('finance::finance.debt_ceiling') }}</th>
                <th>{{ __('finance::finance.client_category') }}</th>
                <th>{{ __('finance::finance.status') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function() {
            let columns = [{
                    "data": 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "code",
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "name",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "phone",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "tax_number",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "current_balance",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "credit_limit",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "client_category_id",
                    true: false,
                    searchable: true
                },
                {
                    "data": "status",
                    orderable: false,
                    searchable: true
                },
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];
            let table = $("#dataTableObject").DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                ordering: true,
                searching: true,
                iDisplayLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "الكل"]
                ],
                ajax: {
                    url: "{{ $dataTableRoute }}",
                    data: function(response) {
                        response.category_id = $('#category_id').val();
                    }
                },
                columns,
                language: {
                    sProcessing: "{{ trans('dataTable.sProcessing') }}",
                    sLengthMenu: "{{ trans('dataTable.sLengthMenu') }}",
                    sZeroRecords: "{{ trans('dataTable.sZeroRecords') }}",
                    sInfo: "{{ trans('dataTable.sInfo') }}",
                    sInfoEmpty: "{{ trans('dataTable.sInfoEmpty') }}",
                    sInfoFiltered: "{{ trans('dataTable.sInfoFiltered') }}",
                    sSearch: "{{ trans('dataTable.sSearch') }}:",
                    oPaginate: {
                        sFirst: "{{ trans('dataTable.sFirst') }}",
                        sPrevious: "{{ trans('dataTable.sPrevious') }}",
                        sNext: "{{ trans('dataTable.sNext') }}",
                        sLast: "{{ trans('dataTable.sLast') }}"
                    }
                },
                order: [
                    [2, "desc"]
                ]
            });

            $('#category_id').on('change', function() {
                table.ajax.reload();
            });
        });
        $(document).on('click', '.update-status', function(e) {
            var id = $(this).data('id');
            var url = "{{ route('finance.clients.show', ':id') }}";
            url = url.replace(':id', id);
            $.ajax({
                type: 'get',
                url: url,
                success: function(data) {
                    console.log(data)
                    if (data.data.success) {
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تغيير الحالة بنجاح!',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });

                        setTimeout(function() {
                            $('#dataTableObject').DataTable().ajax.reload();
                        }, 1500);
                    } else {
                        alert('فشل في تحديث الحالة.');
                    }
                },
                error: function() {
                    alert('حدث خطأ أثناء التحديث.');
                }
            });
        });
    </script>
@endsection
