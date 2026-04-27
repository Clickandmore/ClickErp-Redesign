@extends('click.layout.indexs.index')
@section('style')
    <style>
        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid #F7941D;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 1s linear infinite;
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
            background-color: #F7941D;
        }

        td {
            font-size: small;
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

    @if ($active_year)
        <div class="row g-3 my-2">

            <div class="col-md-3">
                <label for="from_date" class="form-label"> {{ __('finance::finance.from_date') }}</label>
                <input type="date" name="from_date" class="form-control search_keys" id="from_date"
                    value="{{ $active_year['from_date'] }}">
            </div>

            <div class="col-md-3">
                <label for="to_date" class="form-label"> {{ __('finance::finance.to_date') }}</label>
                <input type="date" name="to_date" class="form-control search_keys" id="to_date"
                    value="{{ $active_year['to_date'] }}">
            </div>

            <div class="col-md-3">
                <label for="levelSelect" class="form-label"> {{ __('finance::finance.select the level') }}</label>
                <select name="level" id="levelSelect" class="form-control search_keys">
                    @foreach ($levels as $level)
                        <option value="{{ $level }}">
                            {{ helperTrans('finance::finance.level') }}
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="show_first_balance" class="form-label"> {{ __('finance::finance.show first balance') }}</label>
                <select name="show_first_balance" id="show_first_balance" class="form-control search_keys">
                    <option value="0">{{ helperTrans('finance::finance.No') }}</option>
                    <option value="1">{{ helperTrans('finance::finance.Yes') }}</option>
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
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i>
                {{ helperTrans('dataTable.print') }}</button>
            <button id="toExcel" class="btn btn-warning text-white"><i class="fa fa-file-excel-o"></i>
                {{ helperTrans('dataTable.Export to Excel') }}</button>
        </div>
    </div>
    <div class="row g-3 m-2" id="result"></div>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        let debounceTimer;
        const activeYearFromDate = new Date('{{ @$active_year['from_date'] }}');
        const activeYearToDate = new Date('{{ @$active_year['to_date'] }}');

        function debounce(func, delay) {
            return function(...args) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => func.apply(this, args), delay);
            };
        }

        const validateDateRangeDebounced = debounce(validateDateRange, 1300);

        $('#from_date, #to_date').on('input', validateDateRangeDebounced);

        function validateDateRange() {
            const fromDate = new Date($('#from_date').val());
            const toDate = new Date($('#to_date').val());

            if (fromDate < activeYearFromDate || fromDate > activeYearToDate) {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.The From Date must be within the active financial year') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
                $('#from_date').val(activeYearFromDate.toISOString().split('T')[0]);
                return false;
            }

            if (toDate < activeYearFromDate || toDate > activeYearToDate) {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.The To Date must be within the active financial year') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
                $('#to_date').val(activeYearToDate.toISOString().split('T')[0]);
                return false;
            }

            if (toDate < fromDate) {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.To Date cannot be earlier than From Date') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
                $('#to_date').val(''); // Clear the invalid input
                return false;
            }

            return true;
        }

        $("#search_button").on("click", function(e) {
            e.preventDefault();
            if (!validateDateRange()) {
                return; // Exit if validation fails
            }
            var data = $(".search_keys").serialize();
            let url = '{{ $dataTableRoute }}';
            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function() {
                    $("#result").html('<div class="col-sm-12 col-md-12 col-lg-12 col-xs-12">' +
                        '  <div class="loader" style="margin: auto;"></div>' +
                        '</div>');
                },
                success: function(result) {
                    console.log(result);
                    $("#result").html(result.data.html);
                    $(".print-and-excel-buttons").removeClass("d-none");
                },
                error: function(errorObj, errorText, errorThrown) {
                    handleErrorResponce(errorObj);
                }
            });
        });

        // Excel export functionality
        $('#toExcel').on('click', function() {
            let table = $('#result').find('table').get(0);
            if (table) {
                let fromDate = $('#from_date').val().replace(/-/g, '');
                let toDate = $('#to_date').val().replace(/-/g, '');
                let filename = `financial_report_${fromDate}_to_${toDate}.xlsx`;

                let wb = XLSX.utils.table_to_book(table, {
                    sheet: "Sheet JS"
                });
                XLSX.writeFile(wb, filename);
            } else {
                $.toast({
                    heading: '{{ helperTrans('finance::finance.error') }}',
                    text: '{{ helperTrans('finance::finance.No data available to export') }}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
            }
        });
    </script>
@endsection
