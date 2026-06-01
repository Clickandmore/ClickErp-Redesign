@extends('click.layout.indexs.index')
@section('style')
    <style>
        .loader {
            border: 16px solid #f3f3f3;
            /* Light grey */
            border-top: 16px solid #3498db;
            /* Blue */
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
            background-color: #fcc436;
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
    </style>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link
        href="http://pharma.octoberservicesguide.com/asisst/admin_asisst/plugins/date-p/jquery.datetimepicker.css?v=1723713261"
        rel="stylesheet">
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    @if ($year)
        <div class="row g-3 my-2">

            <input type="hidden" name="start_date" value="{{ $year->from_date }}" class=" search_keys">
            <div class="col-md-3">
                <label for="TextInput" class="form-label"> {{ __('finance::finance.From Date') }}</label>
                <input type="month" value="{{ substr($year->from_date, 0, 7) }}" min="{{ substr($year->from_date, 0, 7) }}" max="{{ substr($year->to_date, 0, 7) }}"
                    name="from_date" class="form-control search_keys " id="from_date">
            </div>

            <div class="col-md-3">
                <label for="TextInput" class="form-label"> {{ __('finance::finance.To Date') }}</label>
                <input type="month" lang="ar" value="{{ substr($year->to_date, 0, 7) }}" name="to_date"
                    min="{{ substr($year->from_date, 0, 7) }}" max="{{ substr($year->to_date, 0, 7) }}" class="form-control search_keys "
                    id="to_date">
            </div>
            <div class="col-md-3">
                <label for="TextInput" class="form-label"> {{ __('finance::finance.first_balance') }}</label>
                <select name="first_balance" id="first_balance" class="form-control search_keys">
                    <option value="0"> {{ __('finance::finance.No') }} </option>
                    <option value="1">{{ __('finance::finance.Yes') }} </option>
                </select>
            </div>

            <div class="col-md-1 mt-4">
                <br>
                <button type="button" id="search_button" class="btn btn-sm btn-success float-right">
                    <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
                </button>
            </div>

        </div>
    @else
        <div class="row g-3 my-2">
            <h5 class="alert alert-info" style="color:red;">{{ trans('finance::finance.the year is not active') }}</h5>
        </div>
    @endif
    <div class="row print-and-excel-buttons d-none">
        <div class="col-12 mt-4" style="text-align: left">
            {{--            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i></button> --}}
            <button id="toExcel" class="btn btn-info"><i class="fa fa-file-excel-o"></i></button>
        </div>
    </div>
    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();
        $("#search_button").on("click", function(e) {
            e.preventDefault()
            var data = $(".search_keys").serialize();
            let url = '{{ $ajaxRoute }}';
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
                    // console.log(result);
                    $("#account_list").html(result.data.html);
                    if (result.data.count > 0) {
                        $('.print-and-excel-buttons').removeClass('d-none');
                    } else {
                        $('.print-and-excel-buttons').addClass('d-none');
                    }
                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>
    <script src="{{ asset('plugins') }}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script>
        var button = document.querySelector('#toExcel');
        button.addEventListener('click', function(e) {
            var table = document.querySelector('#simpleTable4');
            TableToExcel.convert(table);
        });
    </script>
@endsection
