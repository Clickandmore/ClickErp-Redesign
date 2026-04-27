@extends('click.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dropify.min.css">
    <style>
        .custom-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .custom-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:focus+.slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        /* Rounded sliders */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
{{-- @section('up-card')
    <div class="container-fluid">
        <div class="row m-2 row-deck">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <i class="fa fa-bank fa-lg position-absolute top-0 end-0 p-3"></i>
                        <div class="mb-2 text-uppercase">{{ __("finance.Total") }}  </div>
                        <div><span class="h4">{{number_format($results[0]->total_value)}}</span>
                            <span class="small text-muted"><i class="fa fa-money"></i> </span></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-6">
                <div class="card">
                    <div class="card-body">
                        <i class="fa  fa-question fa-lg position-absolute top-0 end-0 p-3"></i>
                        <div class="mb-2 text-uppercase">{{ __("finance.Total per month") }}</div>
                        <div><span class="h4">{{ number_format($results[0]->total_value_current_month)}}</span>
                            <span class="small text-muted"><i class="fa fa-money"></i> </span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection --}}
@section('content')
    <div class="form-check form-switch">
        <label class="form-check-label" id="switchFilterLabel"
            for="switchFilter">{{ __('finance::finance.advanced_search') }}</label>
        <input class="form-check-input" type="checkbox" role="switch" id="switchFilter">
    </div>
    <div class="row mb-3 filter_data" style="display: none">
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.from_date') }}</label>
                <input class="form-control" name="from_date" id="from_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.to_date') }}</label>
                <input class="form-control" name="to_date" id="to_date" type="date">
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.cost_center') }}</label>
                <select name="cost_center_id" id="cost_center_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($cost_centers as $cost_center)
                        <option value="{{ $cost_center->id }}">{{ $cost_center->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.Cash/Drawer/Bank') }}</label>
                <select name="debit_account_id" id="debit_account_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($banks_boxes as $banks_box)
                        <option value="{{ $banks_box->id }}">{{ $banks_box->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.From Account') }}</label>
                <select name="credit_account_id" id="credit_account_id" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 mt-2">
            <div class="form-group">
                <label>{{ __('finance::finance.payment_type') }}</label>
                <select name="payment_type" id="payment_type_filter" class="form-control select2">
                    <option value="">{{ __('finance::finance.choose') }}</option>
                    @foreach ($payment_types as $payment_type)
                        <option value="{{ $payment_type->value }}">
                            {{ \Nami\Finance\Enums\BondPaymentTypes::tryFrom($payment_type->value)->lang() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    {!! addButton($createRoute, $addButtonText) !!}
    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{ __('finance::finance.Voucher Number') }}</th>
                <th>{{ __('finance::finance.Voucher Date') }}</th>
                <th>{{ __('finance::finance.Cash/Drawer/Bank') }}</th>
                <th>{{ __('finance::finance.From Account') }}</th>
                <th>{{ __('finance::finance.Amount') }}</th>
                <th>{{ __('finance::finance.Payment Method') }}</th>
                <th>{{ __('finance::finance.actions') }}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
@endsection
@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{ asset('admin') }}/assets/js/bundle/dropify.bundle.js"></script>
    <script>
        $(document).ready(function() {
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
                        response.from_date = $('#from_date').val();
                        response.to_date = $('#to_date').val();
                        response.cost_center_id = $('#cost_center_id').val();
                        response.debit_account_id = $('#debit_account_id').val();
                        response.credit_account_id = $('#credit_account_id').val();
                        response.payment_type = $('#payment_type').val();
                    }
                },
                columns: [{
                        "data": 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        "data": "bond_num",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "date",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "debit_account",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "account_text",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "value",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "payment_type_name",
                        orderable: false,
                        searchable: true
                    },
                    {
                        "data": "actions",
                        orderable: false,
                        searchable: false
                    }
                ],
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
            $('#from_date, #to_date , #cost_center_id,#debit_account_id,#credit_account_id,#payment_type').change(
                function() {
                    table.ajax.reload();
                });
        })
    </script>
    @include('finance::finance.general_bond._js_bond')
@endsection
