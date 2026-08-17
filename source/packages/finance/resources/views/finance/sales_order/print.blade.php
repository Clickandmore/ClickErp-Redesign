<html lang="Ar">

<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content=""/>

    <title>عرض اسعار</title>

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

    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    div,
    th,
    td {
        font-size: 95%;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
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

    header .Title h1,
    header .Title h2 {
        color: #4c818b;
        font-weight: bold;
        margin: 0;
    }

    .main {
        padding: 5px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .main .Title {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .main .Title h2 {
        text-align: center;
        border: 1px solid #000;
        border-radius: 12px;
        font-weight: bold;
    }

    .main .Head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        border-right: none;
        border-left: none;
        padding-top: 0
    }

    .info {
        display: flex;
        border: 1px solid #e6e0e0;
        flex-direction: column;
        width: 100%;
        gap: 4px;
    }

    .info .info_title {
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        background-color: #777;
        padding: 8px 0;
        margin-bottom: 12px;
    }

    .info .info_title * {
        color: #fff
    }

    .info_content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 5px;
        padding: 2px 12px;
    }

    .info_content:last-child {
        padding-bottom: 12px;
    }

    .info_content * {
        flex: 1;
    }

    .info_content h3 {
        font-weight: bold;
        text-align: start;
    }

    .info_content h3:last-child {
        text-align: end;
    }

    .info_content h4 {
        text-align: center;
    }

    .Tabel {
        display: flex;
        align-items: center;
        justify-content: center;
        border-top: 2px solid #000;
        padding-top: 10px;
        padding: 12px;
        background-color: #fff;
    }

    .Tabel table {
        width: 100%;
    }

    .Tabel th {
        padding: 5px;
        border: 1px solid #a4a4a4;
        border-radius: 10px;
        text-align: center;
    }

    .Tabel tbody {
        font-size: 14px;
    }

    .Tabel td {
        padding: 12px;
        text-align: center;
        border-left: 1px solid #eee;
        border-bottom: 1px dashed #353434;
    }

    .Tabel table tr th {
        background-color: #eee;
    }

    .invoice-summary {
        padding: 12px;
    }

    .invoice-summary .summary-row {
        display: flex;
        /* justify-content: space-between; */
        gap: 12px;
        padding: 0 12px;

        .value {
            min-width: 100px;
            text-align: center;
        }

    }

    .signature-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
        padding: 12px;
        padding-top: 24px;
    }

    .signature-section .signature-block {
        display: flex;
        align-items: center;
        flex-direction: column;
        gap: 5px;
    }

    .qr img {
        width: 230px;
        aspect-ratio: 1;
        background-color: white;
        object-fit: contain;
    }

    .noteSection {
        display: flex;
        margin-top: 20px;
    }

    .noteSection .label {
        margin-left: 10px;
    }

    .financeNot {
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .financeNot .value {
        text-align: end;
    }
</style>

<body>
<!-- header -->
{{-- <header>
    <div class="Logo">
        <img src="@if (isset($setting_web) && isset($setting_web->logo_header)) {{ showFile($setting_web->logo_header) }}
        @else
        {{ url('/') }}/web/img/logo.svg @endif"
            alt="logo" />
        <div class="Title">
            <h1> {{ isset($setting_web) && isset($setting_web->website_name) ? $setting_web->translate('ar')->website_name : 'مؤسسه صلات الوطنيه التجاريه ' }}
            </h1>
            <h2>{{ isset($setting_web) && isset($setting_web->website_name) ? $setting_web->translate('en')->website_name : ' National Selat Trading Company ' }}
            </h2>

        </div>
    </div>
</header> --}}
@include('print-header')

<main class="main">

    <div class="Head">
        <div class="info">

            <div class="info_title " style="margin-bottom: 12px;">
                <h2>معلومات العميل</h2>
                <h2> Customer Information</h2>

            </div>

            <div class="info_content">
                <h3>نوع العمليه</h3>
                <h4>عرض سعر</h4>
                <h3>Invoice Type </h3>
            </div>
            <div class="info_content">
                <h3>رقم عرض سعر </h3>
                <h4>{{ $obj->code }} </h4>
                <h3>Invoice No </h3>
            </div>

            <div class="info_content">
                <h3> التاريخ </h3>
                <h4>{{ $obj->date }} </h4>
                <h3> Date </h3>
            </div>

            <div class="info_content">
                <h3> رقم الحساب </h3>
                <h4>{{ @$obj->client->code }} </h4>
                <h3> Account No </h3>
            </div>

            <div class="info_content">
                <h3> السيد / السـاده </h3>
                <h4> {{ @$obj->client->name }} </h4>
                <h3> Mr / Messrs </h3>
            </div>

            <div class="info_content">
                <h3> الرقم الضريبي </h3>
                <h4> {{ @$obj->client->tax_number }} </h4>
                <h3> Customer VAT # </h3>
            </div>


        </div>
    </div>


    <div class="Tabel">

        <table>
            <thead>
            <tr>
                <th>الاجمالي total</th>
                {{--                 <th> الخصم Dis</th>--}}
                <th>سعر الوحدة price</th>
                <th>الكميه Qty</th>
                <th>الوحدة unit</th>
                <th>البيان Descreption</th>
                <th>الرقم Code</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($obj->salesOrderItems as $key => $item)
                <tr>
                    <td>{{ $item->final_total }}</td>
                    {{--                    <td>{{ $item->discount_per ? $item->discount_per . '%' : '0%' }}</td>--}}
                    <td>{{ number_format($item->sales_price,2) }}</td>
                    <td>{{ $item->sales_amount }}</td>
                    <td>{{ @$item->item->saleUnit->title }}</td>
                    <td>{{ $item->item->name }}</td>
                    <td>{{ $item->item->code }}</td>
                </tr>
            @empty
            @endforelse

            @forelse ($obj->additionItems as $key => $item)
                <tr>
                    <td>{{ $item->final_price }}</td>
                    {{--                    <td>{{ $item->discount_per ? $item->discount_per . '%' : '0%' }}</td>--}}
                    <td>{{ number_format($item->price,2) }}</td>
                    <td>{{ $item->amount }}</td>
                    <td>{{ $item->unit }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->code }}</td>
                </tr>
            @empty
            @endforelse
            </tbody>
        </table>


    </div>
    @php
        $additionItems = $obj->additionItems ?? collect(); // استخدم collect لتجنب الأخطاء إذا كان فارغًا
        $salesOrderItems = $obj->salesOrderItems ?? collect();

        $totalDiscountValue = $salesOrderItems->sum('discount_value') + $additionItems->sum('discount_value');
        $salesItemsTotal = $salesOrderItems->sum('final_total');
        $additionItemsTotal = $additionItems->sum('final_price');
        $totalPrice = $salesItemsTotal + $additionItemsTotal + $totalDiscountValue;
        $finalTotalAfterDiscount = $totalPrice - $totalDiscountValue ;
        $finalTotal = $salesItemsTotal + $additionItemsTotal;
    @endphp

    <div class="invoice-summary">
        <div class="summary-row">
            <h3 class="value">{{ number_format($totalDiscountValue + $finalTotalAfterDiscount, 2) }}</h3>
            <h3 class="label">{{ __('finance::finance.Total before discount') }}</h3>
        </div>
        <div class="summary-row">
            <h3 class="value">{{ number_format($totalDiscountValue, 2) }}</h3>
            <h3 class="label">{{ __('finance::finance.discount_value') }}</h3>
        </div>
        <div class="summary-row">
            <h3 class="value">{{ number_format($finalTotalAfterDiscount, 2) }}</h3>
            <h3 class="label">{{ __('finance::finance.Total after discount') }}</h3>
        </div>
        <div class="summary-row">
            <h3 class="value">{{ number_format($finalTotal, 2) }}</h3>
            <h3 class="label">{{ __('finance::finance.net') }}</h3>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <h5></h5>
            <span> </span>
        </div>
    </div>
    @if($obj->notes != '-')
        <div class="financeNot">
            <h3 class="label">{{ __('finance::finance.notes') }} </h3>
            <h3 class="value">{{ $obj->notes }}</h3>
        </div>
    @endif
</main>

@include('print-footer')
<!-- js -->
<script>
    window.print(); // Print the current page

    window.onafterprint = function () {
        window.close(); // Close the current tab
    };
</script>
</body>

</html>
