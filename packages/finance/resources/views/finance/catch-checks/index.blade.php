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
    <div class="form-check form-switch">
        <label class="form-check-label" id="switchFilterLabel"
               for="switchFilter">{{__('finance::finance.advanced_search')}}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.from_date")}}</label>
                <input class="form-control" name="from_date" id="from_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.to_date")}}</label>
                <input class="form-control" name="to_date" id="to_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.cost_center")}}</label>
                <select name="cost_center_id" id="cost_center_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($cost_centers as $cost_center)
                        <option value="{{ $cost_center->id }}">{{ $cost_center->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.it_is_for")}}</label>
                <select name="credit_account_id" id="credit_account_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{__("finance::finance.bank")}}</label>
                <select name="debit_account_id" id="debit_account_id" class="form-control select2">
                    <option value="">{{__("finance::finance.choose")}}</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            {{--            <th>{{__("hr.added_by")}}</th>--}}
            <th>{{__("finance::finance.Voucher Number")}}</th>
            <th>{{__("finance::finance.Voucher Date")}}</th>
            <th>{{__("finance::finance.check_num")}}</th>
            <th>{{__("finance::finance.check_value")}}</th>
            <th>{{__("finance::finance.check_date")}}</th>
            <th>{{__("finance::finance.it_is_from")}}</th>
            <th>{{__("finance::finance.bank")}}</th>
            {{--            <th>{{__("finance.statement")}}</th>--}}
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
                        response.from_date = $('#from_date').val();
                        response.to_date = $('#to_date').val();
                        response.cost_center_id = $('#cost_center_id').val();
                        response.debit_account_id = $('#debit_account_id').val();
                        response.credit_account_id = $('#credit_account_id').val();
                    }
                },
                columns: [
                    {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    // {"data": "created_by_id", orderable: false, searchable: false},
                    {"data": "con_number", orderable: false, searchable: true},
                    {"data": "con_date", orderable: false, searchable: true},
                    {"data": "check_num", orderable: false, searchable: true},
                    {"data": "check_value", orderable: false, searchable: true},
                    {"data": "check_date", orderable: false, searchable: true},
                    {"data": "debit_account_id", orderable: false, searchable: true},
                    {"data": "credit_account_id", orderable: false, searchable: true},
                    // {"data": "statement", orderable: false, searchable: false},
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
            $('#from_date, #to_date , #cost_center_id,#debit_account_id,#credit_account_id').change(function () {
                table.ajax.reload();
            });
        });
    </script>
    <script>

    </script>
@endsection
