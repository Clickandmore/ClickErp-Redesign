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
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="branch_admin_id">{{ __('finance::finance.banks') }}</label>
            <select id="bank_id" name="bank_id" class="form-control select2">
                <option value="">{{ __('finance::finance.choose') }}</option>
                @foreach (\Nami\Finance\Models\Bank::all() ?? [] as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.banks') }}</th>
                <th>{{ __('finance::finance.account_name') }}</th>
                <th>{{ __('finance::finance.code') }}</th>
                <th>{{ __('finance::finance.bank_number') }}</th>
                <th>{{ __('finance::finance.iban') }}</th>
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
                    "data": "bank",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "name",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "code",
                    orderable: true,
                    searchable: true
                },

                {
                    "data": "bank_number",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "iban",
                    orderable: true,
                    searchable: true
                },
                {
                    "data": "actions",
                    orderable: true,
                    searchable: true
                }
            ];
            {{--            showDataTable("{{ $dataTableRoute }}", columns); --}}


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
                        response.bank_id = $('#bank_id').val();
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

            $('#bank_id').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
