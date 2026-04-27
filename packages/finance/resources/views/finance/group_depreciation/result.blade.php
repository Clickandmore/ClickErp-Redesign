@php
    $hasData = $data_report->some(function($data) {
        return count($data['constraints']) > 0;
    });
@endphp

@if($hasData)
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
        @if(!isset($type_search))
            <div class="row">
                <div class="col-md-12 text-center">
                    <div class="title-box">
                        <h3>@lang('finance::finance.group-depreciation')</h3>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>@lang('finance::finance.group_code') : {{ $group_code }}</h6>
                </div>
                <div class="col-md-6">
                    <h6>@lang('finance::finance.group_name') : {{ $group_name }}</h6>
                </div>
            </div>
        @endif
        <br>
    </div>

    <div class="col-md-12">
        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
            <tr class="section-header">
                @if(!isset($type_search))
                    <th>@lang('finance::finance.principle_code')</th>
                    <th>@lang('finance::finance.principle_name')</th>
                @endif
                <th>@lang('finance::finance.date')</th>
                <th>@lang('finance::finance.value')</th>
                <th>@lang('finance::finance.notes')</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total_value = 0;
            @endphp

            @foreach($data_report as $data)
                @foreach($data['constraints'] as $constraint)
                    <tr>
                        @if(!isset($type_search))
                            <td>{{ $data['principle_code'] }}</td>
                            <td>{{ $data['principle_name'] }}</td>
                        @endif
                        <td>{{ $constraint['date'] }}</td>
                        <td>{{ $constraint['value'] }}</td>
                        <td>{{ $constraint['statement'] }}</td>
                    </tr>
                    @php
                        $total_value += $constraint['value'];
                    @endphp
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
            <tr>
                <td colspan="{{!isset($type_search) ? '3' : '0'}}" class="text-right font-weight-bold">@lang('finance::finance.total')</td>
                <td>{{ $total_value }}</td>
                <td></td>
            </tr>
            </tfoot>
        </table>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"
         style="width: 100%">{{ __("finance::finance.No data available") }}</div>
    <br>
@endif

