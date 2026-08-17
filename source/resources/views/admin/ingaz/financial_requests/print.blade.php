<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>الطلبات المالية</title>
    <style>
        body{font-family: DejaVu Sans, Arial, sans-serif}
        table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ddd;padding:8px;text-align:right}
        th{background:#f3f4f6}
    </style>
</head>
<body onload="window.print()">
<h2>إدارة الطلبات المالية</h2>
<table>
    <thead>
    <tr>
        <th>رقم الطلب</th><th>النوع</th><th>الحالة</th><th>التصعيد</th><th>المبلغ</th><th>العقد</th><th>العميل</th><th>مقدم الطلب</th>
    </tr>
    </thead>
    <tbody>
    @foreach($requests as $item)
        <tr>
            <td>{{ $item->request_no }}</td>
            <td>{{ $types[$item->type] ?? $item->type }}</td>
            <td>{{ $statuses[$item->status] ?? $item->status }}</td>
            <td>{{ $priorities[$item->priority] ?? $item->priority }}</td>
            <td>{{ $item->amount }}</td>
            <td>{{ $item->contract_number }}</td>
            <td>{{ $item->client_name }} / {{ $item->client_identity }}</td>
            <td>{{ $item->creator?->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
