@extends('click.layout.indexs.index')
@section('style')
    <style>
        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid #3e40ba;
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


        .header {
            position: sticky;
            top: 0;
            /*background-color: #ffffff;*/
        }

        td {
            font-size: small;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #account_list,
            #account_list * {
                visibility: visible;
            }

            #account_list {
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

        #account_list {
            margin: 20px auto;
            text-align: center;
        }
    </style>
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="row">

        <div class="col-md-3">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" class="form-control search_keys">
        </div>
        <div class="col-md-3">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" class="form-control search_keys">
        </div>

        <div class="col">
            <br>
            <button type="button" id="search_button" class="btn btn-sm btn-info float-right">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>
        <div class="col justify-content-end" id="print_excel">
            <br>
            <button onclick="printDivById('account_list')" class="btn btn-sm btn-primary mr-2">
                <i class="fa fa-print"></i> {{ __('finance::finance.print') }}
            </button>
            <button type="button" id="toExcel" class="btn btn-sm btn-warning">
                <i class="fa fa-file-excel"></i> {{ __('finance::finance.toExcel') }}
            </button>
        </div>
    </div>
    <br>
    <div class="row">
        <div id="account_list">
            @if ($accounts->count() > 0)
                <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
                    <thead>
                        <tr class="header">
                            <th style="width: 10%" scope="col">{{ __('finance::finance.account_code') }}</th>
                            <th style="width: 10%" scope="col">{{ __('finance::finance.account_name') }}</th>
                            <th style="width: 5%" scope="col">{{ __('finance::finance.Balance') }} [
                                {{ __('finance::finance.creditor') }}]
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($balance = 0)
                        @foreach ($accounts as $row)
                            @php($balance += $row->balance)

                            <tr>
                                <td>{{ $row->code }}</td>
                                <td>{{ @$row->name }}</td>
                                <td>{{ $row->balance }}</td>

                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="text-center">{{ __('finance::finance.total') }}</td>

                            <td class="currency-egp">{{ $balance }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                @include('click.layout.inc.no-data')
            @endif
        </div>
    </div>
@endsection
@section('js')
    <script>
        $("#search_button").on("click", function(e) {
            e.preventDefault()

            var data = $(".search_keys").serialize();
            let url = '{{ $dataTableRoute }}';
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function() {
                    $("#account_list").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function(result) {
                    console.log(result);
                    $("#account_list").html(result.data.html)

                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@linways/table-to-excel@1.0.4/dist/tableToExcel.min.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function(e) {
            var table = document.querySelector('#toExcelTable');
            TableToExcel.convert(table);
        });
    </script>
@endsection
