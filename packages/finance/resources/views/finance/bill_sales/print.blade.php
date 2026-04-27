<html lang="Ar">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />
    <title>
        فاتورة مبيعات ضريببة
        #{{ $obj->code }}
    </title>
    <!-- bootstrap -->
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/bootstrap.rtl.min.css" />
    <!-- Css -->
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap"
        rel="stylesheet" />
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
            padding-bottom: 100px;
        }

        @page {
            margin-bottom: 100px;
        }

        .main {
            display: flex;
            flex-direction: column;
            background-color: #fff;

            .Title {
                display: flex;
                align-items: center;
                justify-content: center;

                h2 {
                    text-align: center;
                    border: 1px solid #000;
                    border-radius: 12px;
                    font-weight: bold;
                }
            }

            .Head {
                display: flex;
                align-items: center;
                gap: 12px;
                padding-top: 0;
            }

            .info {
                display: flex;
                border: 1px solid #e6e0e0;
                flex-direction: column;
                width: 100%;
                gap: 4px;
                padding: 12px;

                .info_title {
                    display: flex;
                    align-items: center;
                    justify-content: space-around;
                    background-color: #777;
                    padding: 8px 0;
                    margin-bottom: 12px;

                    * {
                        color: #fff;
                    }
                }

                .info_content {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 5px;
                    padding: 2px 12px;

                    &:last-child {
                        padding-bottom: 12px;
                    }

                    * {
                        flex: 1;
                    }

                    h3 {
                        font-weight: bold;
                        text-align: start;

                        &:last-child {
                            text-align: end;
                        }
                    }

                    h4 {
                        text-align: center;
                    }
                }
            }

            .Tabel {
                display: flex;
                align-items: center;
                justify-content: center;
                border-top: 2px solid #000;
                padding: 12px;

                table {
                    width: 100%;

                    th {
                        padding: 5px;
                        border: 1px solid #a4a4a4;
                        border-radius: 10px;
                        text-align: center;
                        background-color: #eee;
                    }

                    tbody {
                        font-size: 14px;

                        td {
                            padding: 12px;
                            text-align: center;
                            border-left: 1px solid #eee;
                            border-bottom: 1px dashed #353434;
                        }
                    }
                }
            }

            .invoice-summary {
                padding: 12px;
                page-break-inside: avoid;

                .summary-row {
                    display: flex;
                    gap: 12px;
                    padding: 0 12px;

                    .value {
                        min-width: 100px;
                        text-align: center;
                    }
                }
            }

            .signature-section {
                page-break-inside: avoid;
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-top: auto;
                padding: 12px;
                padding-top: 24px;

                .signature-block {
                    display: flex;
                    align-items: center;
                    flex-direction: column;
                    gap: 5px;
                }
            }

            .qr {
                img {
                    width: 230px;
                    aspect-ratio: 1;
                    background-color: white;
                    object-fit: contain;
                }
            }

            .financeNot {
                padding: 12px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;

                .value {
                    text-align: end;
                }
            }
        }
    </style>
</head>

<body>
    @include('print-header')
    <main class="main">
        <div class="Head">
            @if ($obj->zatca)
                <div class="qr">
                    {{-- <img
                        src="https://www.hellotech.com/guide/wp-content/uploads/2020/05/HelloTech-qr-code-1024x1024.jpg" />
                    --}}
                    <img src="{{ (new chillerlan\QRCode\QRCode())->render($obj->zatca->qr) }}" />
                </div>
            @endif
            <div class="info">
                <div class="info_title">
                    <h1>معلومات العميل</h1>
                    <h1>Customer Information</h1>
                </div>
                <div class="info_content">
                    <h3>نوع العمليه</h3>
                    <h4>فاتورة مبيعات ضريبية Sales Tax Invoice</h4>
                    <h3>Invoice Type</h3>
                </div>
                <div class="info_content">
                    <h3>رقم الفاتوره</h3>
                    <h4>{{ $obj->code }}</h4>
                    <h3>Invoice No</h3>
                </div>
                <div class="info_content">
                    <h3>التاريخ</h3>
                    <h4>{{ $obj->date }}</h4>
                    <h3>Date</h3>
                </div>
                <div class="info_content">
                    <h3>رقم الحساب</h3>
                    <h4>{{ @$obj->client->code }}</h4>
                    <h3>Account No</h3>
                </div>
                <div class="info_content">
                    <h3>السيد / السـاده</h3>
                    <h4>{{ @$obj->client->name }}</h4>
                    <h3>Mr / Messrs</h3>
                </div>
                <div class="info_content">
                    <h3>الرقم الضريبي</h3>
                    <h4>{{ @$obj->client->tax_number }}</h4>
                    <h3>Customer VAT #</h3>
                </div>
            </div>
        </div>
        <div class="Tabel">
            <table>
                <thead>
                    <tr>
                        <th>الاجمالي total</th>
                        <th>الضريبة Tax</th>
                        <th>السعر price</th>
                        <th>الكميه Qty</th>
                        <th>البيان Descreption</th>
                        <th>الرقم Code</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $groupedItems = $obj->items->groupBy('item_id');
                        $total_price = 0;
                        $total_discount = 0;
                    @endphp
                    @foreach ($groupedItems as $itemGroup)
                        @php
                            $firstItem = $itemGroup->first();
                            $totalAmount = $itemGroup->sum('amount');
                            $price = $firstItem->price;
//                            $price = $itemGroup->sum('price');
                            $totalFinalTotal = $itemGroup->sum('final_total');
                            $discount_value = $itemGroup->sum('discount_value');
                            $tax_value = $itemGroup->sum('tax_value');
                            $total_price += $totalFinalTotal;
                            $total_discount += $discount_value;
                            $tax_value += $tax_value;
                        @endphp
                        <tr>
                            <td>{{ number_format($totalFinalTotal, 2) }}</td>
                            <td>{{ $firstItem->tax_per ? $firstItem->tax_per . '%' : 0 }}</td>
                            <td>{{ number_format($price, 2) }}</td>
                            <td>{{ $totalAmount }}</td>
                            <td>{{ @$firstItem->item->name }}</td>
                            <td>{{ @$firstItem->item->code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="invoice-summary">
            {{-- الاجمالي قبل الخصم --}}
            <div class="summary-row">
                <h3 class="value">{{ number_format($obj->total_item, 2) }}</h3>
                <h3 class="label">{{ __('finance::finance.Total before discount') }} </h3>
            </div>
            {{-- الخصم --}}
            <div class="summary-row">
                <h3 class="value">{{ number_format($obj->discount, 2) }}</h3>
                <h3 class="label">{{ __('finance::finance.discount_value') }} </h3>
            </div>
            {{-- الاجمالي بعد الخصم --}}
            <div class="summary-row">
                <h3 class="value">{{ number_format($obj->total_item - $obj->discount, 2) }}</h3>
                <h3 class="label">{{ __('finance::finance.Total after discount') }}</h3>
            </div>
            {{-- الضريبة --}}
            <div class="summary-row">
                <h3 class="value">{{ number_format($obj->total_tax_value, 2) }}</h3>
                <h3 class="label">{{ __('finance::finance.the_tax_value') }} </h3>
            </div>
            {{-- الاجمالي النهائي بعد الضريبة والخصم --}}
            <div class="summary-row">
                <h3 class="value">{{ number_format($obj->total_item - $obj->discount + $obj->total_tax_value, 2) }}
                </h3>
                <h3 class="label">{{ __('finance::finance.net') }} </h3>
            </div>
        </div>
        <div class="signature-section">
            <div class="signature-block">
                <h5>اسم المستلم وتوقيعه Receiver signature / Name</h5>
                <span>..................................</span>
            </div>
        </div>
        @if ($obj->notes != '-')
            <div class="financeNot">
                <h3 class="label">{{ __('finance::finance.notes') }} </h3>
                <h3 class="value">{{ $obj->notes }}</h3>
            </div>
        @endif
    </main>
    @include('print-footer')

    <script>
        window.print(); // Print the current page
        window.onafterprint = function () {
            window.close(); // Close the current tab
        };
    </script>
</body>

</html>
