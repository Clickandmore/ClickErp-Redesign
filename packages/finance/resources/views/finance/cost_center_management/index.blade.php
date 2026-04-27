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
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-group">
                <label>{{__("finance::finance.main account")}}</label>
                <select name="parent_id" id="parent_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($main_accounts as $main_account)
                        <option value="{{ $main_account->id }}">{{ $main_account->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            <th>{{__("finance::finance.name")}}</th>
            <th>{{__("finance::finance.main account")}}</th>
            <th>{{__("finance::finance.code")}}</th>
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
        $('.select2').select2();
        $(document).ready(function () {
            let table = $("#dataTableObject").DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                ordering: true,
                searching: true,
                iDisplayLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "الكل"]],
                ajax: {
                    url: "{{ $dataTableRoute }}",
                    data: function (response) {
                        response.parent_id = $('#parent_id').val();
                    }
                },
                columns: [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {"data": "title", orderable: false, searchable: true},
                    {"data": "parent_id", orderable: false, searchable: true},
                    {"data": "code", orderable: false, searchable: true},
                    {"data": "actions", orderable: false, searchable: false}
                ],
                language: {
                    sProcessing: "{{trans('dataTable.sProcessing')}}",
                    sLengthMenu: "{{trans('dataTable.sLengthMenu')}}",
                    sZeroRecords: "{{trans('dataTable.sZeroRecords')}}",
                    sInfo: "{{trans('dataTable.sInfo')}}",
                    sInfoEmpty: "{{trans('dataTable.sInfoEmpty')}}",
                    sInfoFiltered: "{{trans('dataTable.sInfoFiltered')}}",
                    sSearch: "{{trans('dataTable.sSearch')}}:",
                    oPaginate: {
                        sFirst: "{{trans('dataTable.sFirst')}}",
                        sPrevious: "{{trans('dataTable.sPrevious')}}",
                        sNext: "{{trans('dataTable.sNext')}}",
                        sLast: "{{trans('dataTable.sLast')}}"
                    }
                },
                order: [[2, "desc"]]
            });
            $('#parent_id').change(function () {
                table.ajax.reload();
            });
        })
        {{--$(document).ready(function () {--}}
        {{--    let columns = [--}}
        {{--        {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },--}}
        {{--        {"data": "title", orderable: false, searchable: true},--}}
        {{--        {"data": "parent_id", orderable: false, searchable: true},--}}
        {{--        {"data": "code", orderable: false, searchable: true},--}}
        {{--        {"data": "actions", orderable: false, searchable: false}--}}
        {{--    ];--}}
        {{--    showDataTable("{{ $dataTableRoute }}", columns);--}}
        {{--});--}}
    </script>
@endsection
