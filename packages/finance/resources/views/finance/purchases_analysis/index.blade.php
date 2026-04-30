@extends('click.layout.indexs.index')
@section('style')
    <style>
        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid #3498db;
            border-radius: 50%;
            width: 90px;
            height: 90px;
            animation: spin 1.4s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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
    <div class="row g-3 m-2 to_hide">
        <div class="col-2">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.From Date') }}</label>
            <input type="date" name="from_date" class="form-control search_keys" id="from_date">
        </div>

        <div class="col-2">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.To Date') }} </label>
            <input type="date" name="to_date" value="" class="form-control search_keys" id="to_date">
        </div>

        <div class="col-1">
            <br>

            <button type="button" id="search_button" class="btn btn-sm btn-info float-right">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>


    </div>

    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();
        function loadReport() {
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
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault()
            loadReport();
        });

        loadReport();
    </script>
@endsection
