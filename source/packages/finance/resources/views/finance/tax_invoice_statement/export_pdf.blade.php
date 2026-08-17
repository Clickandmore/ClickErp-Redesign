<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px; }
        body {
            direction: rtl;
            font-family: "DejaVu Sans", sans-serif;
            color: #1f2937;
            font-size: 8px;
        }
        .header {
            border-bottom: 2px solid #315a63;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-table, .summary-table, .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { vertical-align: top; }
        h1 {
            margin: 0 0 5px;
            color: #315a63;
            font-size: 19px;
        }
        .meta { line-height: 1.8; }
        .summary-table { margin: 8px 0; }
        .summary-table td {
            width: 25%;
            padding: 7px;
            border: 1px solid #cbd5e1;
            background: #f6fbfc;
            text-align: center;
        }
        .summary-table strong {
            display: block;
            margin-top: 3px;
            color: #315a63;
            font-size: 11px;
        }
        .report-table { table-layout: fixed; }
        .report-table th, .report-table td {
            border: 1px solid #aebfc4;
            padding: 4px 2px;
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }
        .report-table th {
            background: #315a63;
            color: #fff;
            font-weight: bold;
        }
        .report-table tbody tr:nth-child(even) { background: #f8fafc; }
        .report-table tfoot td {
            background: #eaf3f5;
            font-weight: bold;
        }
        .number { direction: ltr; white-space: nowrap; }
        .empty { padding: 25px; text-align: center; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>كشف حساب ضريبة الفواتير</h1>
                    <div>{{ $periodLabel }}</div>
                </td>
                <td style="text-align: left">
                    <div class="meta">
                        <strong>{{ $supplierName }}</strong><br>
                        الرقم الضريبي: <span class="number">{{ $supplierTaxNumber }}</span><br>
                        تاريخ الإصدار: <span class="number">{{ $generatedAt->format('Y-m-d H:i') }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <tr>
            <td>عدد الفواتير<strong>{{ $rows->count() }}</strong></td>
            <td>المبلغ دون ضريبة<strong>{{ number_format($rows->sum('total_without_tax'), 2) }}</strong></td>
            <td>ضريبة القيمة المضافة<strong>{{ number_format($rows->sum('vat_amount'), 2) }}</strong></td>
            <td>المبلغ شامل الضريبة<strong>{{ number_format($rows->sum('total_with_tax'), 2) }}</strong></td>
        </tr>
    </table>

    @if ($rows->isEmpty())
        <div class="empty">لا توجد فواتير ضريبية مطابقة للفلاتر الحالية.</div>
    @else
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 3%">م</th>
                    <th style="width: 6%">رقم الفاتورة</th>
                    <th style="width: 8%">التاريخ</th>
                    <th style="width: 5%">المصدر</th>
                    <th style="width: 10%">النوع</th>
                    <th style="width: 9%">البائع</th>
                    <th style="width: 8%">الرقم الضريبي</th>
                    <th style="width: 7%">شامل الضريبة</th>
                    <th style="width: 7%">دون ضريبة</th>
                    <th style="width: 6%">الضريبة</th>
                    <th style="width: 8%">العميل</th>
                    <th style="width: 7%">الجوال</th>
                    <th style="width: 6%">المرجع</th>
                    <th style="width: 5%">المستند</th>
                    <th style="width: 7%">ZATCA</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row['invoice_number'] }}</td>
                        <td class="number">{{ $row['date'] ?: '-' }}</td>
                        <td>{{ $row['source'] }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td>{{ $row['seller'] }}</td>
                        <td class="number">{{ $row['seller_tax_number'] }}</td>
                        <td class="number">{{ number_format($row['total_with_tax'], 2) }}</td>
                        <td class="number">{{ number_format($row['total_without_tax'], 2) }}</td>
                        <td class="number">{{ number_format($row['vat_amount'], 2) }}</td>
                        <td>{{ $row['client_name'] }}</td>
                        <td class="number">{{ $row['client_phone'] }}</td>
                        <td>{{ $row['reference_number'] ?: '-' }}</td>
                        <td>{{ $row['document_type'] }}</td>
                        <td>{{ $row['zatca_status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">الإجمالي</td>
                    <td class="number">{{ number_format($rows->sum('total_with_tax'), 2) }}</td>
                    <td class="number">{{ number_format($rows->sum('total_without_tax'), 2) }}</td>
                    <td class="number">{{ number_format($rows->sum('vat_amount'), 2) }}</td>
                    <td colspan="5"></td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
