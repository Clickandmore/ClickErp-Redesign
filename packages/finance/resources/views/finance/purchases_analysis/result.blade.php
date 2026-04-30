@if($result->count() > 0 )
    <div class="col d-flex justify-content-end no-print" id="print_excel">
        <button onclick="handlePrint()" class="btn btn-success ml-2"><i class="fa fa-print"></i></button>
        <button id="toExcel" class="btn btn-warning"><i class="fa fa-file-excel-o"></i></button>
    </div>

    @php
        $fromDate = request('from_date') ?? optional($result->first())->date;
        $toDate = request('to_date') ?? optional($result->last())->date;
    @endphp
    <div id="print_section" class="print-content">
        @include('print-header')
        <div class="text-center" style="margin: 10px auto">
            <h5>
                {{ __("finance::finance.purchases-analysis") }}
                {{ $fromDate ? __('finance::finance.from_date') . ' ' . $fromDate : '' }}
                {{ $toDate ? __('finance::finance.to_date') . ' ' . $toDate : '' }}
            </h5>
        </div>
        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
            <tr class="header">
                <th scope="col">{{ __("finance::finance.bill_number") }} </th>
                <th scope="col">{{ __("finance::finance.date") }} </th>
                <th scope="col">{{ __("finance::finance.vendor") }} </th>
                <th scope="col">{{ __("finance::finance.tax_number") }} </th>
                <th scope="col">{{ __("finance::finance.value_under_tax") }} </th>
                <th scope="col">{{ __("finance::finance.tax_value") }} </th>
                <th scope="col">{{ __("finance::finance.bill_value") }} </th>
            </tr>
            </thead>
            <tbody>
            @foreach($result as $row)
                <tr>
                    <td>{{$row->id}}</td>
                    <td>{{$row->date}}</td>
                    <td>{{@$row->supplier->name}}</td>
                    <td>{{@$row->supplier->tax_number}}</td>
                    <td>{{$row->total_item}}</td>
                    <td>{{$row->tax_value}}</td>
                    <td>{{$row->final_total}}</td>
                </tr>
            @endforeach
            <tr>

                <td colspan="4">{{ __('finance::finance.Total')  }}</td>
                <td>{{ $result->sum(function($row) {return $row->total_item;} )}}</td>
                <td>{{$result->sum('tax_value')}}</td>
                <td>{{ $result->sum('final_total') }}</td>
            </tr>

            </tbody>
        </table>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>

    <br>
@endif


<script src="{{asset("plugins")}}/exelJs/dist/tableToExcel.517b1af9.js"></script>
<script>
    var button = document.querySelector('#toExcel');
    button.addEventListener('click', function (e) {
        var table = document.querySelector('#toExcelTable');
        TableToExcel.convert(table);
    });
</script>
<script>
    function handlePrint() {
        const printContent = document.getElementById('print_section').innerHTML;
        const styleSheet = `
            <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css">
            <style>
                body {
                    direction: rtl;
                    font-family: "Arial", sans-serif;
                    padding: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 6px 8px;
                    text-align: center;
                    vertical-align: middle;
                    font-size: 12px;
                }
                h5 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                @media print {
                    html, body {
                        width: 95%;
                        height: 100%;
                        margin: auto;
                        padding: 0;
                        overflow: visible;
                    }
                }
            </style>
        `;

        const printWindow = window.open('', '_blank');

        printWindow.document.open();
        printWindow.document.write(`
            <html>
                <head>
                    <title>{{ __("finance::finance.purchases-analysis") }}</title>
                    ${styleSheet}
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                        window.onafterprint = function() {
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>
