@extends('click.layout.indexs.index')

@section('style')
    <style>
        .bill-sale-page {
            direction: rtl;
            color: #111827;
            background: #f5f7fb;
            padding: 18px;
        }

        .bill-sale-actions {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 14px;
        }

        .bill-sale-print-btn {
            border-radius: 8px;
            font-weight: 700;
            padding: 9px 18px;
        }

        .bill-sale-document {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
            max-width: 1120px;
            margin: 0 auto;
            padding: 22px;
        }

        .invoice-heading {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: stretch;
            margin-top: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
        }

        .invoice-qr {
            width: 170px;
            min-width: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
        }

        .invoice-qr img {
            width: 150px;
            height: 150px;
            object-fit: contain;
        }

        .invoice-info {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .invoice-info-title {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            background: #eef6f8;
            color: #1f6f78;
            font-weight: 800;
            padding: 11px 14px;
            border-bottom: 1px solid #d7e6ea;
        }

        .invoice-info-row {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr;
            gap: 10px;
            align-items: center;
            padding: 9px 14px;
            border-bottom: 1px solid #edf2f7;
        }

        .invoice-info-row:last-child {
            border-bottom: 0;
        }

        .invoice-info-row .label {
            font-weight: 800;
            color: #334155;
        }

        .invoice-info-row .value {
            text-align: center;
            color: #0f172a;
            font-weight: 700;
            word-break: break-word;
        }

        .invoice-table-wrap {
            margin-top: 18px;
            overflow-x: auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 820px;
        }

        .invoice-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            padding: 11px 10px;
            border-bottom: 1px solid #cbd5e1;
            text-align: center;
            white-space: nowrap;
        }

        .invoice-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            border-left: 1px solid #eef2f7;
            text-align: center;
            vertical-align: middle;
        }

        .invoice-table tr:last-child td {
            border-bottom: 0;
        }

        .invoice-summary {
            width: min(430px, 100%);
            margin: 18px 0 0 auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-row .label {
            margin: 0;
            color: #475569;
            font-size: 14px;
            font-weight: 800;
        }

        .summary-row .value {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .summary-row.net {
            background: #eef6f8;
        }

        .invoice-signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 28px;
        }

        .signature-box {
            text-align: center;
            border-top: 1px dashed #64748b;
            padding-top: 12px;
            min-height: 68px;
        }

        .signature-box h5 {
            margin: 0;
            color: #1f2937;
            font-size: 14px;
            font-weight: 800;
        }

        @media (max-width: 768px) {
            .bill-sale-page {
                padding: 10px;
            }

            .bill-sale-document {
                padding: 14px;
            }

            .invoice-heading {
                flex-direction: column;
            }

            .invoice-qr {
                width: 100%;
                min-width: 0;
            }

            .invoice-info-row {
                grid-template-columns: 1fr;
            }

            .invoice-info-row .value {
                text-align: start;
            }

            .invoice-signatures {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body * {
                visibility: hidden;
            }

            .print,
            .print * {
                visibility: visible;
            }

            .bill-sale-page {
                padding: 0;
                background: #fff;
            }

            .print {
                position: absolute;
                inset: 0;
                width: 100%;
                padding: 0;
            }

            .bill-sale-document {
                box-shadow: none;
                border: 0;
                border-radius: 0;
                max-width: none;
                padding: 12px;
            }

            .bill-sale-actions,
            .sidebar,
            #printButton {
                display: none !important;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page">{{ $bladeTitle }}</li>
@endsection

@section('content')
    @php
        $groupedItems = $obj->items->groupBy('item_id');
    @endphp

    <div class="bill-sale-page">
        <div class="bill-sale-actions">
            <button id="printButton" class="btn btn-primary bill-sale-print-btn">
                <i class="fa fa-print"></i>
                طباعة
            </button>
        </div>

        <div class="print bill-sale-document">
            @includeIf('finance::finance.print-header')

            <main>
                <section class="invoice-heading">
                    @if ($obj->zatca)
                        <div class="invoice-qr">
                            <img src="{{ (new \chillerlan\QRCode\QRCode())->render($obj->zatca->qr) }}" alt="QR">
                        </div>
                    @endif

                    <div class="invoice-info">
                        <div class="invoice-info-title">
                            <span>معلومات العميل</span>
                            <span>Customer Information</span>
                        </div>

                        <div class="invoice-info-row">
                            <span class="label">نوع العملية</span>
                            <span class="value">فاتورة مبيعات ضريبية Sales Tax Invoice</span>
                            <span class="label">Invoice Type</span>
                        </div>
                        <div class="invoice-info-row">
                            <span class="label">رقم الفاتورة</span>
                            <span class="value">{{ $obj->code }}</span>
                            <span class="label">Invoice No</span>
                        </div>
                        <div class="invoice-info-row">
                            <span class="label">التاريخ</span>
                            <span class="value">{{ $obj->date }}</span>
                            <span class="label">Date</span>
                        </div>
                        <div class="invoice-info-row">
                            <span class="label">رقم الحساب</span>
                            <span class="value">{{ @$obj->client->code }}</span>
                            <span class="label">Account No</span>
                        </div>
                        <div class="invoice-info-row">
                            <span class="label">السيد / السادة</span>
                            <span class="value">{{ @$obj->client->name }}</span>
                            <span class="label">Mr / Messrs</span>
                        </div>
                        <div class="invoice-info-row">
                            <span class="label">الرقم الضريبي</span>
                            <span class="value">{{ @$obj->client->tax_number }}</span>
                            <span class="label">Customer VAT #</span>
                        </div>
                    </div>
                </section>

                <section class="invoice-table-wrap">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>الإجمالي total</th>
                                <th>الضريبة Tax</th>
                                <th>الخصم Dis</th>
                                <th>السعر price</th>
                                <th>الكمية Qty</th>
                                <th>البيان Description</th>
                                <th>الرقم Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedItems as $itemGroup)
                                @php
                                    $firstItem = $itemGroup->first();
                                    $totalAmount = $itemGroup->sum('amount');
                                    $price = $firstItem->price;
                                    $totalFinalTotal = $itemGroup->sum('final_total');
                                    $discount_value = $itemGroup->sum('discount_value');
                                    $tax_value = $itemGroup->sum('tax_value');
                                @endphp
                                <tr>
                                    <td>{{ number_format($totalFinalTotal, 2) }}</td>
                                    <td>{{ number_format($tax_value, 2) }}</td>
                                    <td>{{ number_format($discount_value, 2) }}</td>
                                    <td>{{ number_format($price, 2) }}</td>
                                    <td>{{ $totalAmount }}</td>
                                    <td>{{ @$firstItem->item->name }}</td>
                                    <td>{{ @$firstItem->item->code }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <section class="invoice-summary">
                    <div class="summary-row">
                        <h3 class="label">{{ __('finance::finance.Total before discount') }}</h3>
                        <h3 class="value">{{ number_format($obj->total_item, 2) }}</h3>
                    </div>
                    <div class="summary-row">
                        <h3 class="label">{{ __('finance::finance.discount_value') }}</h3>
                        <h3 class="value">{{ number_format($obj->discount, 2) }}</h3>
                    </div>
                    <div class="summary-row">
                        <h3 class="label">{{ __('finance::finance.Total after discount') }}</h3>
                        <h3 class="value">{{ number_format($obj->total_item - $obj->discount, 2) }}</h3>
                    </div>
                    <div class="summary-row">
                        <h3 class="label">{{ __('finance::finance.the_tax_value') }}</h3>
                        <h3 class="value">{{ number_format($obj->total_tax_value, 2) }}</h3>
                    </div>
                    <div class="summary-row net">
                        <h3 class="label">{{ __('finance::finance.net') }}</h3>
                        <h3 class="value">{{ number_format($obj->final_total, 2) }}</h3>
                    </div>
                    <div class="summary-row">
                        <h3 class="label">{{ __('finance::finance.profit_margin') }}</h3>
                        <h3 class="value">{{ $obj->profit_with_margin }}</h3>
                    </div>
                </section>

                <section class="invoice-signatures">
                    <div class="signature-box">
                        <h5>توقيع المستلم Receiver signature</h5>
                    </div>
                    <div class="signature-box">
                        <h5>Receiver Name اسم المستلم</h5>
                    </div>
                    <div class="signature-box">
                        <h5>
                            {{ isset($setting_web) && isset($setting_web->website_name) ? $setting_web->translate('ar')->website_name : 'مؤسسه صلات الوطنيه التجاريه ' }}
                        </h5>
                    </div>
                </section>
            </main>

            @includeIf('finance::finance.print-footer')
        </div>
    </div>
@endsection

@section('js')
    <script>
        document.getElementById('printButton').addEventListener('click', function() {
            window.print();
        });
    </script>
@endsection
