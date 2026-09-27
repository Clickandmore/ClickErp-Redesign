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
@endsection
@section('page-title')
    {{ $bladeTitle }}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection
@section('content')
    <div class="d-flex justify-content-end m-2 to_hide">
        <a class="btn btn-outline-success" href="{{ route('finance.finance-center-list.index') }}">
            <i class="fa fa-balance-scale me-1"></i> قائمة المركز المالي
        </a>
    </div>
    <div class="row g-3 m-2 to_hide">
        <div class="col">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.From Date') }}</label>
            <input type="date" name="from_date" value="{{ $report_period->from_date ?? '' }}"
                class="form-control search_keys" id="from_date">
        </div>

        <div class="col">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.To Date') }}</label>
            <input type="date" name="to_date" value="{{ $report_period->to_date ?? '' }}"
                class="form-control search_keys" id="to_date">
        </div>

        @include('admin.partials.branch-filter', [
            'name' => 'branch_id',
            'label' => __('financial_requests.branch'),
            'id' => 'income_branch_id',
            'containerClass' => 'col',
            'selectClass' => 'form-control search_keys',
            'labelClass' => 'form-label',
        ])

        <div class="col">
            <br>
            <button type="button" id="search_button" class="btn btn-sm btn-success float-right mt-2">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>

    </div>
    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script>
        $('.select2').select2();

        function loadIncomeList() {
            var emptyFields = $(".search_keys").not("[data-branch-filter]").filter(function() {
                return $(this).val() === "";
            });

            if (emptyFields.length > 0) {
                alert("يرجي ادخال كل البيانات اولا");
            } else {
                var data = $(".search_keys").serialize();
                let url = '{{ route('finance.income-list.index') }}';
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: data,
                    beforeSend: function() {
                        $("#account_list").html(
                            '<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                            '  <div class="loader" style="margin: auto;"></div>' +
                            '</div>');
                    },
                    success: function(result) {
                        console.log(result);
                        $("#account_list").html(result.data.html)
                        // $("#account_list").html(result.data2.html)
                    },
                    error: function(errorObj, errorText, errorThrown) {
                        handleErrorResponce(errorObj);
                    }
                });
            }
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault();
            loadIncomeList();
        });

        loadIncomeList();
    </script>
@endsection
