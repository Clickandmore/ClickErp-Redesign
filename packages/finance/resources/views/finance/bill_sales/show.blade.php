@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
            direction: rtl;
            text-align: start;
            color: #000;
        }
        h1, h2, h3, h4, h5, h6, p, span, div, th, td{
            font-size: 100% !important;
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

        header .Title h1,
        header .Title h2 {
            font-size: 20px;
            color: #4c818b;
            font-weight: bold;
            margin: 0;
            padding: 0;
        }

        .main {
            padding: 5px;
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
            font-size: 15px;
            font-weight: bold;
        }

        .main .Head {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #000;
            padding: 10px;
            border-top-left-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: none;
            border-left: none;
        }

        .main .Head .qr img {
            width: 120px;
            -o-object-fit: cover;
            object-fit: cover;
        }

        .info {
            display: flex;
            border: 1px solid #e6e0e0;
            flex-direction: column;
            border-top-left-radius: 12px;
            border-bottom-right-radius: 12px;
            width: 100%;
            padding: 10px;
            gap: 6px;
        }

        .info .info_title {
            display: flex;
            align-items: center;
            justify-content: space-evenly;
        }

        .info .info_title h2 {
            font-size: 16px;
        }

        .info_content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5px;
        }

        .info_content h3 {
            font-size: 15px;
            font-weight: bold;
        }

        .info_content h4 {
            font-size: 13px;
        }

        .Tabel {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            margin-top: 10px;
            border-top: 2px solid #000;
        }

        .Tabel table {
            width: 100%;
        }

        .Tabel th {
            padding: 5px;
            border: 1px solid #000;
            border-radius: 10px;
            text-align: center;
        }

        .Tabel td {
            padding: 25px;
            text-align: center;
            border-left: 1px solid #eee;
            border-bottom: 1px dashed #353434;
        }

        .Tabel table tr th {
            background-color: #eee;
            font-size: 15px;
        }

        /*# sourceMappingURL=style.css.map */
    </style>



      <style>


        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .print, .print * {
                visibility: visible;
            }
            .print {
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
            #printButton {
            display: none;
        }

        }
        .invoice-summary {
        margin-top: 20px;
        padding: 20px;
    }

    .invoice-summary .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
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
<button id="printButton" class="btn btn-primary" style="margin: 20px;">طباعة</button>
    <div class="print">
        {{-- <header>
            <div class="Logo">
                <img src="@if (isset($setting_web) && isset($setting_web->logo_header)) {{ showFile($setting_web->logo_header) }}
                @else
                {{ url('/') }}/web/img/logo.svg @endif" alt="logo" />
                <div class="Title">
                    <h1> {{ (isset($setting_web) && isset($setting_web->website_name)) ? $setting_web->translate('ar')->website_name : 'مؤسسه صلات الوطنيه التجاريه '  }}</h1>
                    <h2>{{ (isset($setting_web) && isset($setting_web->website_name)) ? $setting_web->translate('en')->website_name : ' National Selat Trading Company '  }}
                    </h2>

                </div>
            </div>
        </header> --}}
        @include('print-header')

        <main class="main">

            <div class="Head">

                {{-- <div class="qr">
                    <img src="Assets/images/Asset.png" alt="qr" />
                </div> --}}
                @if($obj->zatca)
                    <div class="qr">
                        <img style="width: 200px; height: 200px; background-color: white;" src="{{ (new chillerlan\QRCode\QRCode)->render($obj->zatca->qr) }}"/>
                    </div>
                @endif

                <div class="info">

                    <div class="info_title " style="margin-bottom: 12px;">
                        <h2>معلومات العميل</h2>
                        <h2> Customer Information</h2>

                    </div>

                    <div class="info_content">
                        <h3>نوع العمليه</h3>
                        <h4>فاتورة مبيعات ضريبة Sales Tax Invoice</h4>
                        <h3>Invoice Type </h3>
                    </div>
                    <div class="info_content">
                        <h3>رقم الفاتوره </h3>
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
                    {{-- <div class="info_content">
                        <h3> رقم امر الشراء </h3>
                        <h4> {{ $obj->client->tax_number }} </h4>
                        <h3> Po# </h3>
                    </div> --}}

                </div>
            </div>


            <div class="Tabel">

                <table>

                    <tr>
                        <th>الاجمالي total</th>
                        <th> الضريبة Tax</th>
                        <th> الخصم Dis</th>
                        <th>السعر price</th>
                        <th>الكميه Qty</th>
                        <th>البيان Descreption</th>
                        <th>الرقم Code</th>
                    </tr>

                    @php
                        // Group items by 'item_id'
                        $groupedItems = $obj->items->groupBy('item_id');
                        $total_price = 0;
                        $total_discount = 0;
                        $total_tax = 0;
                    @endphp
                    @foreach($groupedItems as $itemGroup)
                        @php
                            // Get the first item from the group
                            $firstItem = $itemGroup->first();
                            // Calculate total amount and final total for the group
                            $totalAmount = $itemGroup->sum('amount');
                            $price = $firstItem->price;
//                            $price = $itemGroup->sum('price');
                            $totalFinalTotal = $itemGroup->sum('final_total');
                            $discount_value = $itemGroup->sum('discount_value');
                            $tax_value = $itemGroup->sum('tax_value');
                            $total_price += $totalFinalTotal;
                            $total_discount += $discount_value;
                        @endphp
                        <tr>
                            <td>{{ number_format($totalFinalTotal, 2) }}</td>
                            <td> {{ number_format($tax_value, 2) }} </td>
                            <td> {{ number_format($discount_value, 2) }} </td>
                            <td>{{ number_format($price, 2) }}</td>
                            <td>{{ $totalAmount }}</td>
                            <td>{{ @$firstItem->item->name }}</td>
                            <td>{{ @$firstItem->item->code }}</td>
                        </tr>
                    @endforeach
                </table>


            </div>

            <div class="invoice-summary">
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.Total before discount') }} :</h3>
                    <h3 class="value">{{ number_format($obj->total_item, 2) }} </h3>
                </div>
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.discount_value') }} :</h3>
                    <h3 class="value">{{ number_format($obj->discount, 2) }} </h3>
                </div>
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.Total after discount') }} :</h3>
                    <h3 class="value">{{ number_format($obj->total_item - $obj->discount , 2) }} </h3>
                </div>
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.the_tax_value') }} :</h3>
                    <h3 class="value">{{ number_format($obj->total_tax_value , 2) }} </h3>
                </div>
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.net') }} :</h3>
                    <h3 class="value">{{ number_format($obj->final_total , 2) }} </h3>
                </div>
                <div class="summary-row">
                    <h3 class="label">{{ __('finance::finance.profit_margin') }} :</h3>
                    <h3 class="value">{{ $obj->profit_with_margin , 2 }} </h3>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px;">
                <div style="display: flex; align-items: center;flex-direction: column; gap: 5px;">
                    <h5> توقيع المستلم Receiver signature</h5>
                    <span>..................................</span>

                </div>

                <div style="display: flex; align-items: center;flex-direction: column; gap: 5px;">
                    <h5>Receiver Name اسم المستلم </h5>
                    <span>..................................</span>

                </div>


                <div style="display: flex; align-items: center;flex-direction: column; gap: 5px;">
                    <h5>{{ (isset($setting_web) && isset($setting_web->website_name)) ? $setting_web->translate('ar')->website_name : 'مؤسسه صلات الوطنيه التجاريه '  }}</h5>
                    <span>..................................</span>

                </div>

            </div>







        </main>

        @include('print-footer')
    </div>
@endsection

@section('js')
<script>
    document.getElementById('printButton').addEventListener('click', function () {
        window.print();
    });
</script>
@endsection
