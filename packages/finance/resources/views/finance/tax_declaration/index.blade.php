@extends('click.layout.indexs.index')
@section('style')
    <style>
        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
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
            background-color: #fff;
        }

        @media print {
            .to_hide {
                display: none !important;
            }
            #print_content {
                display: block !important;
            }

            #account_list table {
                table-layout: fixed;
            }
        }
    </style>
    <style>
        .vat-box {
            border: 1px dotted #000;
            padding: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .vat-box .date-top {
            text-align: left;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .vat-box .title-center {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #aaa;
            display: inline-block;
            padding: 4px 10px;
            margin: 5px auto 10px;
        }

        .vat-box .row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .vat-box .col {
            width: 48%;
        }

        .vat-box .label {
            font-weight: bold;
            display: inline-block;
            min-width: 90px;
        }

        .vat-box .item {
            margin-bottom: 5px;
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
    <div class="row g-3 m-2 to_hide">
        <div class="col-3">
            <label for="TextInput" class="form-label">  {{ __("finance::finance.From Date") }}</label>
            <input type="date" name="from_date" class="form-control search_keys" id="from_date">
        </div>
        <div class="col-3">
            <label for="TextInput" class="form-label">  {{ __("finance::finance.To Date") }} </label>
            <input type="date" name="to_date" value=""
                class="form-control search_keys" id="from_date">
        </div>
        <div class="col-1">
            <br>

            <button type="button" id="search_button" class="btn btn-sm btn-info float-right">
                <i class="fa fa-search"></i> {{ __("finance::finance.Search") }}
            </button>
        </div>


    </div>

    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();
        $("#search_button").on("click", function (e) {
            e.preventDefault()
            var data = $(".search_keys").serialize();
            let url = '{{route("finance.tax-declaration.index")}}';
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
                    console.log(result);
                    $("#account_list").html(result.data.html)
                },
                error: function (errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });
    </script>
@endsection
