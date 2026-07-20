<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            direction: rtl;
            color: #111827;
            font-size: 11px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 8px;
        }

        .meta {
            color: #6b7280;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px 6px;
            text-align: right;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <h1>{{ $bladeTitle }}</h1>
    <div class="meta">تاريخ التصدير: {{ $printedAt->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>{{ __('finance::finance.Invoice Number') }}</th>
                <th>رقم المبيعات</th>
                <th>مرتبطة بـ</th>
                <th>{{ __('finance::finance.date') }}</th>
                <th>{{ __('finance::finance.client') }}</th>
                <th>{{ __('finance::finance.Total before discount') }}</th>
                <th>{{ __('finance::finance.Total discount') }}</th>
                <th>{{ __('finance::finance.the_tax_value') }}</th>
                <th>{{ __('finance::finance.total_after_all') }}</th>
                <th>{{ __('finance::finance.share') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $sale->sourceInvoice?->legacy_invoice_number ?: ($sale->sourceInvoice?->id ?: $sale->code) }}</td>
                    <td>{{ $sale->code }}</td>
                    <td>
                        @if($sale->sourceInvoice?->contract_id)
                            عقد استقدام #{{ $sale->sourceInvoice->contract_id }}
                        @elseif($sale->sourceInvoice?->ingaz_delegation_id)
                            تفويض إنجاز #{{ $sale->sourceInvoice->ingaz_delegation_id }}
                        @elseif($sale->sourceInvoice?->legacy_order_number)
                            طلب رقم {{ $sale->sourceInvoice->legacy_order_number }}
                        @elseif($sale->sourceInvoice)
                            فاتورة النظام
                        @else
                            فاتورة مبيعات يدوية
                        @endif
                    </td>
                    <td>{{ $sale->date }}</td>
                    <td>{{ $sale->client?->name ?: '-' }}</td>
                    <td>{{ number_format((float) $sale->total_item, 2) }}</td>
                    <td>{{ number_format((float) $sale->discount, 2) }}</td>
                    <td>{{ number_format((float) $sale->total_tax_value, 2) }}</td>
                    <td>{{ number_format((float) $sale->final_total, 2) }}</td>
                    <td>{{ $sale->zatca?->zatca_status ?: 'معلقة للهيئة' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
