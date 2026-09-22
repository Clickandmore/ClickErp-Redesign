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


        .header {
            position: sticky;
            top: 0;
            background-color: #ffc800;
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
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="row g-3 my-2">
        <div class="col-md-3">
            <label for="TextInput" class="form-label">{{ __('finance::finance.Select the Duplicated Account') }}</label>
            <select name="double_account_id" id="account_id" class="form-control  show-tick ms select2 search_keys">
                {!! showSelectElement($double_accounts, 'name') !!}
            </select>
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.from_date') }}</label>
            <input type="date" name="from_date" class="form-control search_keys" id="from_date">
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.to_date') }}</label>
            <input type="date" name="to_date" value="{{ date('Y-m-d', time()) }}" class="form-control search_keys"
                id="from_date">
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label">{{ __('finance::finance.Transaction status') }}</label>
            <select name="type" id="type" class="form-control search_keys">
                <option value="both">{{ __('finance::finance.choose') }}</option>
                <option value="debtor">{{ __('finance::finance.debtor') }}</option>
                <option value="creditor">{{ __('finance::finance.creditor') }}</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.Show Counter Account') }}</label>
            <select name="other_account" id="other_account" class="form-control search_keys">
                <option value="no">{{ __('finance::finance.No') }} </option>
                <option value="yes">{{ __('finance::finance.Yes') }} </option>
            </select>
        </div>

        <div class="col-md-1 mt-4">
            <br>
            <button type="button" id="search_button" class="btn btn-sm btn-success float-right">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>
    </div>
    @if (@AdminGuardHasPerm('finance_double_account_statement_export') || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
    <div class="row mt-3">
        <div class="col-12" style="text-align: left">
            <a href="#" class="btn btn-success comprehensive-export"
                data-url="{{ route('finance.double-account-statement.export', 'excel') }}">
                <i class="fa fa-file-excel-o"></i> تصدير شامل Excel
            </a>
            <a href="#" class="btn btn-danger comprehensive-export"
                data-url="{{ route('finance.double-account-statement.export', 'pdf') }}" data-new-window="true">
                <i class="fa fa-file-pdf-o"></i> طباعة / حفظ PDF شامل
            </a>
        </div>
    </div>
    @endif
    <div class="row print-and-excel-buttons d-none">
        <div class="col-12 mt-4" style="text-align: left">
            {{-- <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i></button> --}}
            {!! printButton(
                route('finance.double-account-statement-print.index', request()->all()),
                null,
                'finance_double_account_statement_export',
            ) !!}
            @if (@AdminGuardHasPerm('finance_double_account_statement_export') || auth('admin')->user()?->admin_type == \App\Enums\AdminTypeisEnum::Developer->value)
            <button id="toExcel" class="btn btn-info"><i class="fa fa-file-excel-o"></i></button>
            @endif
        </div>
    </div>
    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();
        $('.comprehensive-export').on('click', function(e) {
            e.preventDefault();
            const query = $('.search_keys').serialize();
            const url = $(this).data('url') + (query ? '?' + query : '');
            if ($(this).data('new-window')) {
                window.open(url, '_blank', 'noopener');
                return;
            }
            window.location.href = url;
        });
        function searchAccountStatement(urlOverride = null) {
            var data = urlOverride ? null : $(".search_keys").serialize();
            let accountId = $("#account_id").val();

            if (!accountId || accountId === "") {
                validToster("من فضلك اختر حساب أولاً")
                return; // وقف التنفيذ
            }
            let url = '{{ route('finance.double-account-statement.index') }}';
            $.ajax({
                url: urlOverride || url,
                type: 'GET',
                data: data,
                beforeSend: function() {
                    $("#account_list").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function(result) {
                    console.log(result);
                    $("#account_list").html(result.data.html);
                    $('.print-and-excel-buttons').removeClass('d-none');
                    const filterData = $(".search_keys").serialize();
                    $('#print_button').attr('href',
                        `{{ route('finance.double-account-statement-print.index') }}?${filterData}`
                        );
                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        }
        $('#search_button').on('click', function(e) {
            e.preventDefault();
            searchAccountStatement();
        });
        $('#account_list').on('click', '.statement-pagination a', function(e) {
            e.preventDefault();
            searchAccountStatement(this.href);
        });
    </script>
    <script src="{{ asset('plugins') }}/exelJs/dist/tableToExcel.517b1af9.js"></script>
    <script src="{{ asset('admin/assets/js/statement-export.js') }}"></script>
    <script>
        var button = document.querySelector('#toExcel');
        if (button) button.addEventListener('click', function(e) {
            var table = document.querySelector('#simpleTable4');
            exportFullStatement(@json(route('finance.double-account-statement-print.index')) + '?' + $('.search_keys').serialize(), this);
        });
    </script>
@endsection
