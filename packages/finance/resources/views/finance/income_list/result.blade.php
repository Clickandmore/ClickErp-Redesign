@if(count($data))
    <div class="col d-flex justify-content-end no-print" id="print_excel">
        <button onclick="handlePrint()" class="btn btn-success ml-2"><i class="fa fa-print"></i></button>
    </div>

    <div id="print_section">
        @include('finance::finance.print-header')
        <div class="text-center" style="margin: 10px auto">
            <h5>{{ __("finance::finance.Income statement") }} {{ request('from_date') ? __('finance::finance.from_date') : '' }} {{ request('from_date') }} {{ request('to_date') ? __('finance::finance.to_date') : '' }} {{ request('to_date') }}</h5>
        </div>
        <table class="table table-hover table-bordered table-striped" style="width:100%;font-size: 17px">
            @php
                $income = 0;
                $outcome = 0;
            @endphp
            @foreach ($data as $account)
                <tbody style="font-size: 19px">
                <tr>
                    <th rowspan="{{count($account['list']) +1}}"
                        style="text-align: center;background-color: #dddddd; font-size: 20px"
                        scope="col">{{$account['account']->name}}</th>
                </tr>
                @foreach($account['list'] as $children)
                    <tr>
                        <td class="one">{{ $children['account']->name ?? " " }}</td>
                        <td class="one" colspan="2">{{ $children['total_price'] ?? " " }}</td>
                    </tr>
            @endforeach
            <tbody>

            <tbody style="font-size: 19px">
            <tr style="background-color: #7dd3e4;text-align: center;font-size: 20px">
                <td class="one"> مجموع {{$account['account']->name}}</td>
                <td class="one" colspan="2">{{ $account['account_total_price'] ?? " " }}</td>
            </tr>
            </tbody>
            @php
                if($account['account']->type == Nami\Finance\Enums\AccountTypesEnum::GeneralIncome){
                    $income += $account['account_total_price'];
                }elseif($account['account']->type == Nami\Finance\Enums\AccountTypesEnum::GeneralOutcome){
                    $outcome += $account['account_total_price'];
                }
            @endphp
            @endforeach
            <tr style="font-weight: bold;text-align: center;font-size: 21px">
                @if($income > $outcome)
                    <td class="one">صافي الربح</td>
                    <td class="one" colspan="2">{{ $income - $outcome ?? " " }}</td>
                @else
                    <td class="one">صافي الخسارة</td>
                    <td class="one" colspan="2">{{ $outcome - $income ?? " " }}</td>
                @endif
            </tr>
        </table>
    </div>
@else
    <div role="alert" class="alert alert-danger text-center"> {{__('finance::finance.no_data_found')}}</div>
@endif

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
