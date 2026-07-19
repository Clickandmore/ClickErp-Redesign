<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { margin: 0 0 10px; text-align: center; font-size: 17px; }
        .meta { margin-bottom: 8px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; text-align: right; vertical-align: top; }
        th { background: #e8f3f1; }
        .number { direction: ltr; text-align: left; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">عدد السجلات: {{ count($rows) }} — تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}</div>
    <table>
        <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td @class(['number' => in_array($index, $numericColumns, true)])>
                            {{ in_array($index, $numericColumns, true) ? number_format((float) $value, 2) : $value }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" style="text-align:center">لا توجد بيانات مطابقة للفلاتر.</td></tr>
            @endforelse
        </tbody>
    </table>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
