<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ strtoupper($paperSize ?? 'a4') }} landscape;
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            unicode-bidi: embed;
            color: #1f2937;
            font-size: 12px;
            margin: 18px;
        }

        .header {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #f8fbff;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 20px;
            color: #0f172a;
        }

        .meta {
            margin: 0;
            color: #475569;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        thead th {
            background: #eef2f7;
            color: #0f172a;
            font-weight: 700;
        }

        th,
        td {
            border: 1px solid #dbe4f0;
            padding: 8px 6px;
            text-align: right;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            line-height: 1.7;
            unicode-bidi: plaintext;
        }

        tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        body.report-wide {
            margin: 8px;
            font-size: 8px;
        }

        body.report-wide .header {
            margin-bottom: 10px;
            padding: 10px 12px;
        }

        body.report-wide th,
        body.report-wide td {
            padding: 4px 3px;
            line-height: 1.45;
        }

        td.status-cell {
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            text-align: center;
        }

        td.status-cell.status-danger {
            background: #fef2f2;
            color: #dc2626;
        }

        td.days-remaining {
            background: #f0fdf4;
            color: #15803d;
            font-weight: 800;
            text-align: center;
        }

        td.days-expired {
            background: #fef2f2;
            color: #dc2626;
            font-weight: 800;
            text-align: center;
        }

        a.document-link {
            color: #2563eb;
            font-weight: 800;
            text-decoration: underline;
        }

        .empty {
            text-align: center;
            padding: 30px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            color: #64748b;
            margin-top: 24px;
        }
    </style>
</head>

<body class="{{ !empty($wide) ? 'report-wide' : '' }}">
    <div class="header">
        <h1>{{ $title }}</h1>
        <p class="meta">تاريخ التصدير: {{ $exportedAt }}</p>
        <p class="meta">عدد السجلات: {{ number_format($recordCount) }}</p>
    </div>

    @if (!empty($rows))
        <table>
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $rowIndex => $row)
                    <tr>
                        @foreach ($row as $cellIndex => $cell)
                            @php
                                $isRemainingCell = isset($remainingDaysColumn) && $cellIndex === $remainingDaysColumn;
                                $hasRemainingDays = $isRemainingCell && str_starts_with(trim((string) $cell), '+');
                                $isExpiredDays = $isRemainingCell && !$hasRemainingDays
                                    && ((trim((string) $cell) !== '-' && str_starts_with(trim((string) $cell), '-'))
                                        || in_array(trim((string) $cell), ['0', 'منتهي', 'ملغي'], true));
                                $cellLink = data_get($cellLinks ?? [], $rowIndex . '.' . $cellIndex);
                            @endphp
                            <td @class([
                                'status-cell' => isset($statusColumn) && $cellIndex === $statusColumn,
                                'status-danger' => isset($statusColumn) && $cellIndex === $statusColumn
                                    && in_array($cell, ['ملغي', 'معلق', 'مجمد'], true),
                                'days-remaining' => $hasRemainingDays,
                                'days-expired' => $isExpiredDays,
                            ])>
                                @if ($cellLink)
                                    <a class="document-link" href="{{ $cellLink }}" target="_blank">{{ $cell }}</a>
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">لا توجد بيانات مطابقة للفلاتر المحددة.</div>
    @endif
</body>

</html>
