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
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ $createRoute }}" class="btn btn-success">
            <i class="fa fa-plus-circle"></i>
            {{ __('buttons.add button') }} {{ __('finance::finance.external_agents') }}
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('finance::finance.external_agents') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="externalAgentsTable" class="table display dataTable table-hover" style="width:100%">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('finance::finance.name') }}</th>
                        <th>{{ __('finance::finance.account_code') }}</th>
                        <th>{{ __('finance::finance.phone') }}</th>
                        <th>{{ __('ingaz.nationality') }}</th>
                        <th>{{ __('ingaz.office_type') }}</th>
                        <th>{{ __('ingaz.status') }}</th>
                        <th>{{ __('finance::finance.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script>
        $(document).ready(function () {
            $('#externalAgentsTable').DataTable({
                responsive: true,
                ordering: true,
                searching: true,
                iDisplayLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
                processing: true,
                serverSide: false,
                ajax: "{{ $dataTableRoute }}",
                columns: [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {"data": "title", orderable: false, searchable: true},
                    {"data": "account_code", orderable: false, searchable: true},
                    {"data": "phone_office", orderable: false, searchable: true},
                    {"data": "nationality", orderable: false, searchable: true},
                    {"data": "office_type", orderable: false, searchable: true},
                    {"data": "status_label", orderable: false, searchable: true},
                    {"data": "actions", orderable: false, searchable: false},
                ],
                language: {
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
                },
                order: [[0, "desc"]]
            });
        });
    </script>

@endsection
