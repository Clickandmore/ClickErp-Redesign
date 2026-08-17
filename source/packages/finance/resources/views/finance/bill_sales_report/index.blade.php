@extends('click.layout.indexs.index')
@section('style')
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="row g-3 m-2 to_hide">
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" id="from_date" class="form-control search_keys">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" id="to_date" class="form-control search_keys">
        </div>
        <div class="col-md-2">
            <label for="">{{ __('finance::finance.code') }}</label>
            <input type="text" name="code" id="code" class="form-control search_keys">
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.clients') }}</label>
            <select name="client_id" id="client_id" class="form-control search_keys">
                {!! showSelectElement($clients, 'name') !!}
            </select>
        </div>
        <div class="col-md-3">
            <label for="">{{ __('finance::finance.items') }}</label>
            <select name="item_id" id="item_id" class="form-control search_keys">
                {!! showSelectElement($items, 'name') !!}
            </select>
        </div>
        <div class="col-1">
            <br>

            <button type="button" id="search_button" class="btn btn-sm btn-info float-right">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>
    </div>

    {{-- <div class="row g-3 m-2" id="account_list"></div> --}}
    <div class="row g-3 m-2" id="account_list">
        @includeIf('finance::finance.bill_sales_report.result', ['result' => $result ?? collect()])
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $("#search_button").click(); // هيعمل بحث تلقائي أول ما الصفحة تفتح
        });
        $('.select2').select2();
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
@endsection
