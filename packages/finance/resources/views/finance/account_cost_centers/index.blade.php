@extends('click.layout.indexs.index')
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
            background-color: #fcc436;
        }
        td{
            font-size: small;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #account_list, #account_list * {
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
    </style>
@endsection
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')
    @php
        $filters = request()->only(['cost_center_id', 'account_id', 'from_date', 'to_date', 'type']);
    @endphp

    <div class="row g-3 my-2">
        <div class="col-3">
            <label for="TextInput" class="form-label"> {{__('finance::finance.fn-cost-centers')}}</label>
            <select name="cost_center_id" id="cost_center_id" class="form-control  show-tick ms select2 search_keys"  >
                {!! showSelectElement($cost_centers,'title', $filters['cost_center_id'] ?? null) !!}
            </select>
        </div>

        <div class="col-3">
            <label for="TextInput" class="form-label"> {{__('finance::finance.account')}}</label>
            <select name="account_id" id="account_id" class="form-control  show-tick ms select2 search_keys">
                {!! showSelectElement($accounts,'name', $filters['account_id'] ?? null) !!}
            </select>
        </div>


        <div class="col-md-2">
            <label for="TextInput" class="form-label"> {{__('finance::finance.From Date')}}</label>
            <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control search_keys" id="from_date">
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label"> {{__('finance::finance.To Date')}}</label>
            <input type="date" name="to_date" value="{{ $filters['to_date'] ?? date("Y-m-d",time()) }}" class="form-control search_keys" id="to_date">
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label">{{__('finance::finance.Transaction Type')}}</label>
            <select name="type" id="type" class="form-control search_keys">
                <option value="both" {{ ($filters['type'] ?? 'both') === 'both' ? 'selected' : '' }}>{{__('finance::finance.All')}}</option>
                <option value="debtor" {{ ($filters['type'] ?? '') === 'debtor' ? 'selected' : '' }}>{{__('finance::finance.debtor')}}</option>
                <option value="creditor" {{ ($filters['type'] ?? '') === 'creditor' ? 'selected' : '' }}>{{__('finance::finance.creditor')}}</option>
            </select>
        </div>



        <div class="col-md-1 mt-4">
            <br>
            <button type="button" id="search_button" class="btn btn-sm btn-success float-right">
                <i class="fa fa-search"></i> {{ __("finance::finance.Search") }}
            </button>
        </div>

    </div>

    <div class="row print-and-excel-buttons d-none">
        <div class="col-12 mt-4" style="text-align: left">
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i></button>
            <button id="toExcel" class="btn btn-info"><i class="fa fa-file-excel-o"></i></button>
        </div>
    </div>
    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();
        function searchCostCenterStatement() {
            var data = $(".search_keys").serialize();
            let url = '{{$ajaxRoute}}';

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function () {
                    $("#account_list").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function (result) {
                    $("#account_list").html(result.data.html);
                    if(result.data.count > 0) {
                        $('.print-and-excel-buttons').removeClass('d-none');
                    }else{
                        $('.print-and-excel-buttons').addClass('d-none');
                    }
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }

        $("#search_button").on("click", function (e) {
            e.preventDefault()
            searchCostCenterStatement();
        });
    </script>
    <script src="{{asset("plugins")}}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        if (button) {
            button.addEventListener('click', function () {
                var table = document.querySelector('#simpleTable4');
                if (!table) {
                    return;
                }
                TableToExcel.convert(table);
            });
        }

        @if (request()->filled('cost_center_id') || request()->boolean('auto_search'))
            searchCostCenterStatement();
        @endif
    </script>
@endsection
