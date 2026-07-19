<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2937; }
        h1 { text-align: center; margin: 0 0 10px; font-size: 18px; }
        .meta { margin-bottom: 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; text-align: right; vertical-align: top; }
        th { background: #e8f3f1; font-weight: bold; }
        .number { direction: ltr; text-align: left; white-space: nowrap; }
        tfoot td { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        من: {{ $filters['from_date'] ?: 'البداية' }} — إلى: {{ $filters['to_date'] ?: 'الآن' }}
        — عدد الحركات: {{ count($rows) }}
    </div>
    <table>
        <thead>
            <tr>
                <th>رمز الحساب</th><th>اسم الحساب</th><th>التاريخ</th><th>رقم القيد</th><th>البيان</th>
                <th>مدين</th><th>دائن</th><th>الرصيد خلال الفترة</th><th>مركز التكلفة</th><th>أنشأ بواسطة</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['account_code'] }}</td><td>{{ $row['account_name'] }}</td>
                    <td class="number">{{ $row['date'] }}</td><td>{{ $row['constraint_code'] }}</td><td>{{ $row['statement'] }}</td>
                    <td class="number">{{ number_format($row['debtor'], 2) }}</td>
                    <td class="number">{{ number_format($row['creditor'], 2) }}</td>
                    <td class="number">{{ number_format($row['balance'], 2) }}</td>
                    <td>{{ $row['cost_center'] }}</td><td>{{ $row['created_by'] }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center">لا توجد حركات مطابقة للفلاتر.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="5">الإجمالي</td><td class="number">{{ number_format($totalDebtor, 2) }}</td><td class="number">{{ number_format($totalCreditor, 2) }}</td><td colspan="3"></td></tr>
        </tfoot>
    </table>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
