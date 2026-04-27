@extends('click.layout.indexs.index')
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('style')
    <style>
        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid #F7941D;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }


        .header{
            position:sticky;
            top: 0 ;
            background-color: #ffc800;
        }

        td{
            font-size: small;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #simpleTable4, #simpleTable4 * {
                visibility: visible;
            }
            #simpleTable4 {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
                font-size: 12pt;
            }
            .sidebar {
                display: none !important;
            }
        }
    </style>
@endsection
@section('content')

    @if(count($accounts))
        <div class="row print-and-excel-buttons mb-3">
            <div class="col-12 mt-4" style="text-align: left">
                <button onclick="window.print()" class="btn btn-primary" id="printBalanceTable"><i class="fa fa-print"></i></button>
                <button id="toExcel" class="btn btn-info"><i class="fa fa-file-excel-o"></i></button>
            </div>
        </div>
        <table class="table table-hover table-bordered table-striped" id="simpleTable4" style="width:100%">
            <thead>
                <tr class="header">
                    <th style="width: 3%" scope="col">#</th>
                    <th style="width: 10%" scope="col">  {{ __('finance::finance.code') }}</th>
                    <th style="width: 10%" scope="col"> {{ __("finance::finance.Account") }}</th>
                    <th style="width: 5%" scope="col">{{ __("finance::finance.total_depit") }}</th>
                    <th style="width: 5%" scope="col">{{ __("finance::finance.total_credit") }}</th>
                    <th style="width: 10%" scope="col">{{__("finance::finance.Balance")}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $key => $account)
                    <tr>
                        <td>{{ ++$key}}</td>
                        <td>{{ $account['code'] }}</td>
                        <td>{{ $account['name'] }}</td>
                        <td>{{ $account['debtor_value'] }}</td>
                        <td>{{ $account['creditor_value'] }}</td>
                        <td>{{ $account['balance'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <th colspan="3">{{ __('finance::finance.Total') }}</th>
                <th class="currency-egp">{{ $total_depitors }}</th>
                <th class="currency-egp">{{ $total_creditors }}</th>
                <th class="currency-egp" style="background-color: yellow">{{ $total_balance }}</th>
            </tfoot>
        </table>
    @else
        <div class="d-flex justify-content-center align-items-center" style="height: 50vh !important;">
            <div class="no-data text-center">
                {{ __('finance::finance.no_data') }}
            </div>
        </div>
    @endif
@endsection
@section('js')
    <script src="{{asset("plugins")}}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function (e) {
            var table = document.querySelector('#simpleTable4');
            TableToExcel.convert(table);
        });
    </script>
@endsection
