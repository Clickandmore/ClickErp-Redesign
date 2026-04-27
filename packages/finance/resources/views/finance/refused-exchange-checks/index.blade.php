@extends('click.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    {{ __("finance::finance.refused_exchange_checks") }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ __("finance::finance.refused_exchange_checks") }}</li>
@endsection
@section('content')
    <div class="clearfix"></div>
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>{{ __('finance::finance.SL') }}</th>
            {{--            <th>{{__("hr.added_by")}}</th>--}}
            <th>{{__("finance::finance.check_num")}}</th>
            <th>{{__("finance::finance.check_value")}}</th>
            <th>{{__("finance::finance.check_date")}}</th>
            <th>{{__("finance::finance.it_is_for")}}</th>
            <th>{{__("finance::finance.bank")}}</th>
            {{--            <th>{{__("finance::finance.statement")}}</th>--}}
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                // {"data": "created_by_id", orderable: false, searchable: false},
                {"data": "check_num", orderable: false, searchable: false},
                {"data": "check_value", orderable: false, searchable: false},
                {"data": "check_date", orderable: false, searchable: false},
                {"data": "debit_account_id", orderable: false, searchable: false},
                {"data": "credit_account_id", orderable: false, searchable: false},
                // {"data": "statement", orderable: false, searchable: false},
                {"data": "actions", orderable: false, searchable: false}
            ];
            @php
                $bondType = \Nami\Finance\Enums\BondTypes::Sarf->value;
                $status = \Nami\Finance\Enums\BondCheckStatusEnum::RefuseSarf->value;
                $routeUrl = route('finance.exchange-checks-data.index', [
                     'bond_type' => $bondType,
                     'status' => $status
                ]);
            @endphp
            console.log("Route URL:", {!! json_encode($routeUrl) !!});
            showDataTable("{!! $routeUrl !!}", columns);
        });
    </script>
    <script>

    </script>
@endsection
