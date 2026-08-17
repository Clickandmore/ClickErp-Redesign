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
                {{ __("finance::finance.bill-sales-report") }}
                {{ $fromDate ? __('finance::finance.from_date') . ' ' . $fromDate : '' }}
                {{ $toDate ? __('finance::finance.to_date') . ' ' . $toDate : '' }}
            </h5>
        </div>
        <br>
        <table class="table table-hover table-bordered table-striped" id="toExcelTable" style="width:100%">
            <thead>
            <tr class="header">
                <th>{{ __('finance::finance.SL') }}</th>
                <th>{{__("finance::finance.Invoice Number")}}</th>
                <th>{{__("finance::finance.date")}}</th>
                <th>{{__("finance::finance.the_stock")}}</th>
                <th>{{__("finance::finance.client")}}</th>
                <th>{{__("finance::finance.Total before discount")}}</th>
                <th>{{__("finance::finance.Total discount")}}</th>
                <th>{{__("finance::finance.the_tax_value")}}</th>
                <th>{{__("finance::finance.total_after_all")}}</th>
                <th>{{__("finance::finance.profit_margin")}}</th>
            </tr>
            </thead>
            <tbody>
                @php
                    $total_item = 0;
                    $total_discount = 0;
                    $total_tax = 0;
                    $total_final = 0;
                    $total_profit = 0;
                @endphp
            @foreach($result as $key => $row)
            @php
$total_item += is_numeric($row->total_item) ? $row->total_item : 0;
$total_discount += is_numeric($row->discount) ? $row->discount : 0;
$total_tax += is_numeric($row->total_tax_value) ? $row->total_tax_value : 0;
$total_final += is_numeric($row->final_total) ? $row->final_total : 0;
    preg_match('/([\d,]+\.\d{2}|\d+)/', $row->profit_with_margin, $matches);
$profit = isset($matches[0]) ? floatval(str_replace(',', '', $matches[0])) : 0;
$total_profit += $profit;

            @endphp
                <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $row->code }}</td>
                <td>{{ $row->date }}</td>
                <td>{{ $row->stocksNames() }}</td>
                <td>{{ $row->client->name ?? '' }}</td>
                <td>{{ $row->total_item }}</td>
                <td>{{ $row->discount }}</td>
                <td>{{ $row->total_tax_value }}</td>
                <td>{{ $row->final_total }}</td>
                <td>{{ $row->profit_with_margin }}</td>
                </tr>
            @endforeach

            </tbody>
            <tfoot>
<tr>
    <th colspan="5" class="text-end">{{__('finance::finance.total')}}:</th>
<th>{{ number_format($total_item, 2) }}</th>
<th>{{ number_format($total_discount, 2) }}</th>
<th>{{ number_format($total_tax, 2) }}</th>
<th>{{ number_format($total_final, 2) }}</th>
<th>{{ number_format($total_profit, 2) }}</th>
</tr>
</tfoot>
        </table>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"
         style="width: 100%">{{ __("finance::finance.No data available") }}</div>
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
                    <title>{{ __("finance::finance.sales-analysis") }}</title>
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

