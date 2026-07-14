<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            direction: rtl;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.7;
        }

        .report-header {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #d8dee8;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .meta {
            color: #667085;
        }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        table.entries {
            width: 100%;
            border-collapse: collapse;
        }

        .entries th {
            padding: 8px 6px;
            border: 1px solid #d8dee8;
            background: #eef2f7;
            font-weight: bold;
            text-align: right;
        }

        .entries td {
            padding: 7px 6px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .amount {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .multiline {
            white-space: pre-line;
        }

        .empty {
            padding: 24px;
            border: 1px solid #e5e7eb;
            color: #667085;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>{{ $title }}</h1>
        <div class="meta">تاريخ التصدير: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td>البحث: {{ $filters['keyword'] }}</td>
            <td>من تاريخ: {{ $filters['from_date'] }}</td>
            <td>إلى تاريخ: {{ $filters['to_date'] }}</td>
            <td>نوع القيد: {{ $filters['type'] }}</td>
            <td>عدد القيود: {{ $totals['count'] }}</td>
            <td>الإجمالي: <span class="amount">{{ number_format((float) $totals['value'], 2, '.', ',') }}</span></td>
        </tr>
    </table>

    @if (count($rows) > 0)
        <table class="entries">
            <thead>
                <tr>
                    <th>رقم القيد</th>
                    <th>{{ __('finance::finance.statement') }}</th>
                    <th>نوع القيد</th>
                    <th>{{ __('finance::finance.value') }}</th>
                    <th>{{ __('finance::finance.date') }}</th>
                    <th>مدينون</th>
                    <th>دائنون</th>
                    <th>المستند</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['statement'] }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td class="amount">{{ $row['value'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td class="multiline">{{ $row['debtors'] ?: '-' }}</td>
                        <td class="multiline">{{ $row['creditors'] ?: '-' }}</td>
                        <td>{{ $row['document'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">لا توجد قيود مطابقة للفلاتر الحالية.</div>
    @endif
</body>
</html>
