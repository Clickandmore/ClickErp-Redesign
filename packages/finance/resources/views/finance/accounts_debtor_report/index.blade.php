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
            body * {
                visibility: hidden;
            }

            #account_list,
            #account_list * {
                visibility: visible;
            }

            #account_list {
                position: absolute;
                width: 100%;
                left: 0;
                top: 0;
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

<div class="row g-2 m-2">
    <div class="col d-flex justify-content-end">
        <button onclick="window.print()" class="btn btn-primary mr-2 mx-2">
            <i class="fa fa-print"></i> {{ __("finance.print") }}
        </button>
        <button type="button" id="toExcel" class="btn btn-sm btn-warning">
            <i class="fa fa-file-excel"></i> {{ __("finance.toExcel") }}
        </button>
    </div>
</div>
                <div id="account_list">
                    <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
                        <thead>
                            <tr class="header">
                                <th>{{ __('finance.account_code') }}</th>
                                <th>{{ __('finance.account_name') }}</th>
                                <th>{{ __('finance.Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($data_report) > 0)
                                @foreach($data_report as $index => $row)
                                    @if($row->account->status == 'debtor')
                                        <tr>
                                            <td>{{ $row->account->code ?? $index + 1 }}</td>
                                            <td>{{ $row->account->name ?? '' }}</td>
                                            <td>{{ $row->debtor_value_total - $row->creditor_value_total }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3" class="text-center">{{ __('finance.No data available') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
@endsection
@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
   $('#toExcel').on('click', function () {
            let table = $('#result').find('table').get(0);
            if (table) {
                let fromDate = $('#from_date').val().replace(/-/g, '');
                let toDate = $('#to_date').val().replace(/-/g, '');
                let filename = `financial_report_${fromDate}_to_${toDate}.xlsx`;

                let wb = XLSX.utils.table_to_book(table, {sheet: "Sheet JS"});
                XLSX.writeFile(wb, filename);
            } else {
                $.toast({
                    heading: '{{helperTrans('finance.error')}}',
                    text: '{{helperTrans('finance.No data available to export')}}',
                    icon: 'error',
                    position: 'top-right',
                    stack: false
                });
            }
        });
</script>
@endsection
