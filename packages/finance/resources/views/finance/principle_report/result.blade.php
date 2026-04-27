@if($data_report->count() > 0 )
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #D9D9F3;
        }

        td {
            background-color: #F9F9F9;
        }

        .section-header {
            background-color: #CCCCFF;
        }

        .section-header th {
            border-bottom: 2px solid black;
        }

        .table thead th {
            vertical-align: bottom;
            border: 2px solid #23223e;
        }
    </style>
<div class="row g-2 m-2">
    <div class="col d-flex printExcel justify-content-end">
        <button onclick="window.print()" class="btn btn-primary mr-2 mx-2">
            <i class="fa fa-print"></i> {{ __("finance::finance.print") }}
        </button>
        <button type="button" id="toExcel" class="btn btn-sm btn-warning">
            <i class="fa fa-file-excel"></i> {{ __("finance::finance.toExcel") }}
        </button>
    </div>
</div>

<div class="col-md-12">
    <div class="row">
        <div class="col-md-12 text-center">
            <div class="title-box">
                <h3>@lang('finance::finance.asset_list')</h3>
            </div>
        </div>
        <div class="col-md-6">
            <h6>@lang('finance::finance.group_code') : {{ $group_code }}</h6>
        </div>
        <div class="col-md-6">
            <h6>@lang('finance::finance.group_name') : {{ $group_name }}</h6>
        </div>
    </div>
    <br>
</div>

    <div class="col-md-12">
        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
                <tr class="section-header">
                    <th>@lang('finance::finance.principle_code')</th>
                    <th>@lang('finance::finance.principle_name')</th>
                    <th>@lang('finance::finance.principle_site')</th>
                    <th>@lang('finance::finance.responsible_employee')</th>
                    <th>@lang('finance::finance.purchase_date')</th>
                    <th>@lang('finance::finance.purchase_value')</th>
                    <th>@lang('finance::finance.first_tax')</th>
                    <th>@lang('finance::finance.second_tax')</th>
                    <th>@lang('finance::finance.depreciated_value')</th>
                    <th>@lang('finance::finance.financial_value')</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total_purchase_value = 0;
                    $total_first_tax = 0;
                    $total_second_tax = 0;
                    $total_depreciated_value = 0;
                    $total_financial_value = 0;
                @endphp
                @foreach($data_report as $data)
                    <tr>
                        <td>{{ $data['principle_code'] }}</td>
                        <td>{{ $data['principle_name'] }}</td>
                        <td>{{ $data['principle_site'] }}</td>
                        <td>{{ $data['responsible_employee'] }}</td>
                        <td>{{ $data['purchase_date'] }}</td>
                        <td>{{ $data['purchase_value'] }}</td>
                        <td>{{ $data['first_value'] }}</td>
                        <td>{{ $data['second_value'] }}</td>
                        <td>{{ $data['depreciated_value'] }}</td>
                        <td>{{ $data['financial_value'] }}</td>
                    </tr>
                    @php
                        $total_purchase_value += $data['purchase_value'];
                        $total_first_tax += $data['first_value'];
                        $total_second_tax += $data['second_value'];
                        $total_depreciated_value += $data['depreciated_value'];
                        $total_financial_value += $data['financial_value'];
                    @endphp
                @endforeach

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">@lang('finance::finance.total')</td>
                    <td>{{ $total_purchase_value }}</td>
                    <td>{{ $total_first_tax }}</td>
                    <td>{{ $total_second_tax }}</td>
                    <td>{{ $total_depreciated_value }}</td>
                    <td>{{ $total_financial_value }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"
         style="width: 100%">{{ __("finance::finance.No data available") }}</div>
    <br>
@endif

