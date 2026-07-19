<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2937; }
        h1 { text-align: center; margin: 0 0 12px; font-size: 18px; }
        .meta { margin-bottom: 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: right; vertical-align: top; }
        th { background: #e8f3f1; font-weight: bold; }
        .number { direction: ltr; text-align: left; white-space: nowrap; }
        tfoot td { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        تاريخ التصدير: {{ now()->format('Y-m-d H:i') }} — عدد السجلات: {{ count($rows) }}
    </div>
    <table>
        <thead>
            <tr>
                <th>رقم السند</th><th>الدفتر</th><th>التاريخ</th><th>الحساب المدين</th>
                <th>الحساب الدائن</th><th>طريقة الدفع</th><th>القيمة</th><th>الحالة</th>
                <th>القيد</th><th>البيان</th><th>مركز التكلفة</th><th>أنشأ بواسطة</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td><td>{{ $row['book_number'] }}</td><td class="number">{{ $row['date'] }}</td>
                    <td>{{ $row['debit_account'] }}</td><td>{{ $row['credit_account'] }}</td><td>{{ $row['payment_type'] }}</td>
                    <td class="number">{{ number_format($row['value'], 2) }}</td><td>{{ $row['status'] }}</td>
                    <td>{{ $row['constraint'] }}</td><td>{{ $row['statement'] }}</td><td>{{ $row['cost_center'] }}</td><td>{{ $row['created_by'] }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center">لا توجد بيانات مطابقة للفلاتر.</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="6">الإجمالي</td><td class="number">{{ number_format($total, 2) }}</td><td colspan="5"></td></tr></tfoot>
    </table>
</body>
</html>
