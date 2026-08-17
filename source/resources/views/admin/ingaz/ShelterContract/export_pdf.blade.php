<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير عقود الإيواء</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 14px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d8d8d8;
            padding: 6px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f3f6fb;
            font-weight: 700;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }
    </style>
</head>
<body>
    <h1>تقرير عقود الإيواء</h1>
    <div class="meta">تاريخ التصدير: {{ $exportedAt }}</div>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($headings as $heading)
                        <td>{{ $row[$heading] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">لا توجد بيانات للتصدير.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
