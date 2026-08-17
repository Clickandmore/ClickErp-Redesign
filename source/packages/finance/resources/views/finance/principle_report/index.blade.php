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

            .printExcel {
                display: none !important;
                /* Hide the printExcel button */
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

        <div class="col-md-6">
            <label for="TextInput" class="form-label"> {{ __('finance::finance.principles_group') }}</label>
            <select name="account_id" id="account_id" class="form-control  show-tick ms select2 search_keys">
                {!! showSelectElement($accounts, 'name') !!}
            </select>
        </div>

        <div class="col-md-1 mt-4">
            <br>
            <button type="button" id="search_button" class="btn btn-sm btn-success float-right">
                <i class="fa fa-search"></i> {{ __('finance::finance.Search') }}
            </button>
        </div>

    </div>


    <div class="row g-3 m-2" id="account_list"></div>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        $(document).ajaxComplete(function() {
            $('#toExcel').on('click', function() {
                let table = $('#account_list').find('table').get(0);
                if (table) {
                    // let fromDate = $('#from_date').val().replace(/-/g, '');
                    // let toDate = $('#to_date').val().replace(/-/g, '');
                    let filename = `principle_report.xlsx`;

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
        });
    </script>
    <script>
        $('.select2').select2();
        $("#search_button").on("click", function(e) {
            e.preventDefault()
            var account_id = $("#account_id").val();
            if (!account_id) {
                alert("Please select an account.");
                return false;
            }
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
