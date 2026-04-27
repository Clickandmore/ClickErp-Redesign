<html lang="Ar">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content=""/>

    <title>account statement</title>

    <!-- bootstrap -->
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css"/>
    <!-- Css -->
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/style.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link
            href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap"
            rel="stylesheet"/>
</head>
<style>
    * {
        box-sizing: border-box;
        padding: 0;
        margin: 0;
        direction: rtl;
        text-align: start;
        font-family: "IBM Plex Sans Arabic", sans-serif;
        color: #000;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        border: 1px solid #000;
    }

    header {
        padding: 25px;
    }

    header .Logo {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    header .Logo img {
        width: 75px;
    }

    header .Title {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    header .Title h1 {
        font-size: 21px;
        line-height: 1.8;
        color: #4c818b;
    }

    header .Title h2 {
        font-size: 18px;
        color: #e7ca08;
    }

    .main {
        padding: 25px;
    }

    .main .Title {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main .Title h2 {
        text-align: center;
        border: 1px solid #000;
        padding: 15px;
        border-radius: 12px;
        font-size: 18px;
        font-weight: bold;
    }

    .Date {
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        margin-top: 20px;
    }

    .adress {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 50px;
    }

    .adress h3 {
        border: 1px solid #000;
        padding: 10px;
        border-radius: 12px;
    }

    .Tabel {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 25px;
        border-radius: 5px;
    }

    .Tabel table,
    .Tabel th,
    .Tabel td {
        border: 1px solid #000;
        border-collapse: collapse;
        padding: 10px;
    }

    .Tabel tr th {
        background-color: #b5b3b3;
    }

    .Acountant h5 {
        text-align: center;
    }

    /*# sourceMappingURL=style.css.map */
</style>

<body>
<!-- header -->

@include('click.layout.inc.print_header')

<main class="main">
    <div class="Title">
        <h2>{{ $title ?? "كشف حساب البنوك" }}</h2>
    </div>
    <div class="Date">
        @if (request()->from_date)
            <span>من تـاريـخ {{ request()->from_date ? \Carbon\Carbon::parse(request()->from_date)->format('d/m/Y') : '__/__/__' }}</span>

        @endif
        @if (request()->to_date)
            <span>إلى تـاريخ {{ request()->to_date ? \Carbon\Carbon::parse(request()->to_date)->format('d/m/Y') : '__/__/__' }}</span>
        @endif


    </div>

    <div class="adress">
        <h3>اسم الحسـاب</h3>
        <h4> {{ $accountName ?? "-------------------" }}</h4>


    </div>
    @php
        $colspan = ($show_other_account == "yes") ? 11 : 10;
        $width   = 100 / $colspan;
    @endphp
    <div class="Tabel">
        <table>

            <tr>
                <th>#</th>
                <th>تاريخ الحركة</th>
                <th> رقم الحركة</th>
                <th> نوع الحركة</th>
                @if($show_other_account == "yes")
                    <th style="width: 10%" scope="col">الحساب المقابل</th>
                @endif
                <th> البيان</th>
                <th style="width: {{$width}}%" scope="col">رقم الشيك</th>
                <th style="width: {{$width}}%" scope="col">قيمة الشيك</th>
                <th style="width: {{$width}}%" scope="col">تاريخ الشيك</th>
                <th style="width: {{$width}}%" scope="col">طريقة الدفع</th>
                <th style="width: {{$width}}%" scope="col">رقم التحويل</th>
                <th> مدين</th>
                <th> دائن</th>
                <th> الرصيد</th>
                <th> المستخدم</th>
            </tr>
            @php
                $balance = 0;
                $sumCreditorValue = $sumDebtorValue = 0;
            @endphp
                    <!--------------------- $firstBalance ------------------------------------------>
            @if(isset($firstBalance->sum_debtor_value))
                @php
                    $balance += ($firstBalance->status == "debtor")? ($firstBalance->sum_debtor_value - $firstBalance->sum_creditor_value):($firstBalance->sum_creditor_value - $firstBalance->sum_debtor_value);
                    $sumDebtorValue += $firstBalance->sum_debtor_value ;
                    $sumCreditorValue += $firstBalance->sum_creditor_value ;
                // $trClass = ( $firstBalance->sum_debtor_value  == 0  &&  $firstBalance->sum_creditor_value == 0 )? "currency-usd":"currency-egp";
                @endphp
                <tr style="background-color: papayawhip;">
                    <td colspan="{{$colspan}}" class="text-center">رصيد اول مدة</td>
                    <td class="currency-egp">{{$firstBalance->sum_debtor_value}}</td>
                    <td class="currency-egp">{{$firstBalance->sum_creditor_value}}</td>
                    <td class="currency-egp">{{number_format($balance)}}</td>
                    <td></td>
                </tr>
            @endif
            <!--------------------- $firstBalance ------------------------------------------>
            @foreach ($data as $key => $row)
                @php
                    $balance += ($row->status == "debtor") ? ($row->debtor_value - $row->creditor_value) : ($row->creditor_value - $row->debtor_value);
                    $sumDebtorValue += $row->debtor_value;
                    $sumCreditorValue += $row->creditor_value;
                    $trClass = ($row->debtor_value == 0 && $row->creditor_value == 0) ? "currency-usd" : "currency-egp";
                @endphp
                <tr class="{{ $trClass }}">
                    <td>{{ ++$key }}</td>
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->constraint_id }}</td>
                    <td>{{ getEnumValue('constraint_types', $row->constraints_type) }}</td>
                    @if($show_other_account == "yes")
                        <td>
                            @foreach($otherAccount[$row->constraint_id]["accounts"] as $oneAccount)
                                <span>{{ $oneAccount->name }}</span><br>
                            @endforeach
                        </td>
                    @endif
                    <td>{{ $row->statement }}</td>
                    <td>{{ $row->check_num }}</td>
                    <td>{{ $row->check_value }}</td>
                    <td>{{ $row->check_date }}</td>
                    <td>{{  \Nami\Finance\Enums\BondPaymentTypes::langFromValue($row->payment_type) }}</td>
                    <td>{{ $row->transfer_number }}</td>
                    <td class="currency-egp">{{ $row->debtor_value }}</td>
                    <td class="currency-egp">{{ $row->creditor_value }}</td>
                    <td class="currency-egp">{{ $balance }}</td>
                    <td>{{ $row->name }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="{{ $colspan }}" class="text-center">{{__('finance.Balance')}}</td>
                <td class="currency-egp">{{ $sumDebtorValue }}</td>
                <td class="currency-egp">{{ $sumCreditorValue }}</td>
                <td class="currency-egp">{{ $balance }}</td>
                <td></td>
            </tr>

        </table>


    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 25px;">

        <div class="Acountant">
            <h5>محاسب</h5>
            <h6>.......................................</h6>

        </div>

        <div class="Acountant">
            <h5>يعتمد</h5>
            <h6>.......................................</h6>

        </div>


    </div>


</main>

<!-- js -->
<script>
    window.print(); // Print the current page

    window.onafterprint = function () {
        window.close(); // Close the current tab
    };
</script>

</body>

</html>
