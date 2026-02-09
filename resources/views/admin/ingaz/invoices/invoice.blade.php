<!DOCTYPE html>

<head>
    <!-- <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/> -->
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <title>فاتورة العقد</title>
    <style type="text/css">
        div,
        td {
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .invoice-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .invoice-title {
            font-weight: 700;
            font-size: 20px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .invoice-meta {
            color: #6c757d;
            font-size: 14px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .invoice-table thead th {
            background: #f3f4f6;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }

        .invoice-summary .label {
            color: #6c757d;
            font-size: 14px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', 'sans-seri';
        }
        

        .table-responsive {
            margin-block: 10px !important;
        }

        .table-responsive tbody th {
            padding: 15px !important;
        }
    </style>
</head>

<body dir="rtl">
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice-header mb-4">
                        {{-- <div class="d-flex align-items-center gap-2 no-print">
                            <button type="button" class="btn btn-light border" onclick="window.print()">
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div> --}}
                        <div class="text-end">
                            <div class="invoice-title">فاتورة إلكترونية</div>
                            <div class="invoice-meta">رقم العقد: {{ $invoice->contract_id }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>رقم العقد :</strong>
                                <span>{{ $invoice->contract_id }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>حالة الفاتورة :</strong>
                                <span>{{ $invoiceStatus }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>وصف الخدمة :</strong>
                                <span>{{ $serviceDesc }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>اسم المورد :</strong>
                                <span>{{ $supplierName }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>عنوان المورد :</strong>
                                <span>{{ $supplierAddress }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>اسم صاحب العمل :</strong>
                                <span>{{ $invoice->contract->user->name ?? '---' }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <strong>تاريخ الدفع :</strong>
                                <span>{{ optional($invoice->created_at)->format('Y-m-d H:i') ?? '---' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle invoice-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">وصف الخدمة</th>
                                    <th style="width: 10%;">الكمية</th>
                                    <th style="width: 15%;">القيمة</th>
                                    <th style="width: 15%;">نسبة الضريبة</th>
                                    <th style="width: 15%;">قيمة الضريبة</th>
                                    <th style="width: 15%;">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $index => $item)
                                    <tr>
                                        <td>
                                            {{ $index + 1 }} - {{ $item['label'] }}
                                        </td>
                                        <td>{{ $item['qty'] }}</td>
                                        <td>{{ number_format($item['unit'], 2) }}</td>
                                        <td>{{ (int) ($item['vat_rate'] * 100) }}%</td>
                                        <td>{{ number_format($item['vat_amount'], 2) }}</td>
                                        <td>{{ number_format($item['line_total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-lg-12 invoice-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="label">المجموع الفرعي : </span>
                                <strong>{{ number_format($invoice->subtotal, 2) }} ريال</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="label">مبلغ الضريبة (15%) : </span>
                                <strong>{{ number_format($invoice->vat_amount, 2) }} ريال</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="label">خصم : </span>
                                <strong>{{ number_format($invoice->discount, 2) }} ريال</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="label">إجمالي السعر : </span>
                                <strong>{{ number_format($invoice->total_due, 2) }} ريال</strong>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="d-flex gap-2 mt-4 no-print">
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">إغلاق</a>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
</body>

</html>
