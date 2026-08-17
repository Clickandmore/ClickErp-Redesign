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

        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            padding: 18px;
            background: #f4f7fb;
            color: #1f2937;
            direction: rtl;
            unicode-bidi: embed;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            font-size: 12px;
        }

        .page {
            background: #fff;
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.08);
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .toolbar-note {
            color: #64748b;
            line-height: 1.8;
        }

        .print-button {
            border: 0;
            border-radius: 12px;
            background: #dc2626;
            color: #fff;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .header {
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: #f8fbff;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 22px;
            color: #0f172a;
        }

        .meta {
            margin: 0;
            color: #475569;
            line-height: 1.8;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            border: 1px solid #dbe4f0;
            padding: 8px 6px;
            text-align: right;
            vertical-align: top;
            line-height: 1.7;
            word-break: break-word;
            overflow-wrap: anywhere;
            unicode-bidi: plaintext;
        }

        thead th {
            background: #eef2f7;
            color: #0f172a;
            font-weight: 700;
        }

        tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        body.report-wide {
            padding: 8px;
            font-size: 8px;
        }

        body.report-wide .page {
            padding: 10px;
        }

        body.report-wide .header {
            margin-bottom: 10px;
            padding: 10px 12px;
        }

        body.report-wide .header h1 {
            margin-bottom: 4px;
            font-size: 18px;
        }

        body.report-wide table {
            table-layout: fixed;
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
            background: #fff;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .page {
                border: 0;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</head>

<body class="{{ !empty($wide) ? 'report-wide' : '' }}">
    <div class="page">
        <div class="toolbar">
            <button type="button" class="print-button" onclick="window.print()">{{ $printLabel ?? 'طباعة / حفظ PDF' }}</button>
            <div class="toolbar-note">يمكنك اختيار "حفظ كملف PDF" أو "Save as PDF" من نافذة الطباعة.</div>
        </div>

        <div class="header">
            <h1>{{ $title }}</h1>
            <p class="meta">تاريخ التصدير: {{ $exportedAt }}</p>
            <p class="meta">عدد السجلات: {{ number_format($recordCount) }}</p>
        </div>

        @if (!empty($rows))
            <div class="table-wrap">
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
            </div>
        @else
            <div class="empty">لا توجد بيانات مطابقة للفلاتر المحددة.</div>
        @endif
    </div>
</body>

</html>
