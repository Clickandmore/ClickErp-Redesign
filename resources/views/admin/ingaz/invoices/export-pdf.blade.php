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
    <h1>تقرير فواتير إنجاز</h1>
    <div class="meta">تاريخ التصدير: {{ $printedAt->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>المرجع</th>
                <th>العميل</th>
                <th>الإجمالي</th>
                <th>الضريبة</th>
                <th>الخصم</th>
                <th>المطلوب</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td>#{{ $invoice->id }}</td>
                    <td>{{ $invoice->contract_id ? 'عقد #' . $invoice->contract_id : 'تفويض #' . $invoice->ingaz_delegation_id }}</td>
                    <td>{{ $invoice->contract?->user?->name ?: ($invoice->ingazDelegation?->user?->name ?: '-') }}</td>
                    <td>{{ number_format((float) $invoice->subtotal, 2) }}</td>
                    <td>{{ number_format((float) $invoice->vat_amount, 2) }}</td>
                    <td>{{ number_format((float) $invoice->discount, 2) }}</td>
                    <td>{{ number_format((float) $invoice->total_due, 2) }}</td>
                    <td>{{ optional($invoice->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
