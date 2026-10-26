<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            direction: rtl;
            color: #192321;
            font-size: 9px;
        }

        .head {
            border-bottom: 2px solid #176b63;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 4px;
            color: #0f514b;
        }

        .meta {
            color: #66716e;
            font-size: 9px;
        }

        .filters {
            margin: 6px 0 8px;
            font-size: 9px;
        }

        .filters span {
            background: #edf6f4;
            border: 1px solid #d5e6e1;
            color: #0f514b;
            padding: 2px 6px;
        }

        .notice {
            color: #9a6700;
            background: #fff7e8;
            border: 1px solid #ead9b6;
            padding: 4px 6px;
            margin-bottom: 8px;
        }

        table.log {
            width: 100%;
            border-collapse: collapse;
        }

        table.log th,
        table.log td {
            border: 1px solid #dbe4e1;
            padding: 4px 5px;
            text-align: right;
            vertical-align: top;
        }

        table.log th {
            background: #176b63;
            color: #ffffff;
            font-weight: bold;
        }

        table.log tr.even td {
            background: #f8faf9;
        }

        .muted {
            color: #66716e;
        }

        .badge {
            font-weight: bold;
        }

        .is-created { color: #0f514b; }
        .is-updated { color: #815700; }
        .is-deleted { color: #912e33; }

        .change {
            margin-bottom: 1px;
        }

        .old {
            color: #8d2c31;
        }

        .new {
            color: #0f514b;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="head">
        <h1>سجل النشاط</h1>
        <div class="meta">
            تاريخ التصدير: {{ $printedAt->format('Y-m-d h:i A') }}
            @if ($printedBy)
                &nbsp;|&nbsp; بواسطة: {{ $printedBy }}
            @endif
            &nbsp;|&nbsp; عدد السجلات: {{ number_format($rows->count()) }}
            @if ($total > $rows->count())
                من أصل {{ number_format($total) }}
            @endif
        </div>
    </div>

    <div class="filters">
        <strong>الفلاتر:</strong>
        @forelse ($filterSummary as $label => $value)
            <span>{{ $label }}: {{ $value }}</span>&nbsp;
        @empty
            <span class="muted">بدون فلاتر — كل السجلات</span>
        @endforelse
    </div>

    @if ($total > $maxRows)
        <div class="notice">
            يحتوي هذا الملف على أحدث {{ number_format($maxRows) }} سجل فقط من أصل {{ number_format($total) }}.
            ضيّق الفترة الزمنية أو استخدم تصدير Excel للحصول على كل السجلات.
        </div>
    @endif

    <table class="log">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="9%">التاريخ</th>
                <th width="13%">المنفذ</th>
                <th width="7%">العملية</th>
                <th width="14%">السجل</th>
                <th width="24%">الملخص</th>
                <th width="28%">التغييرات</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->even ? 'even' : '' }}">
                    <td>{{ $row['id'] }}</td>
                    <td>{{ $row['date'] }}<br><span class="muted">{{ $row['time'] }}</span></td>
                    <td>{{ $row['actor_name'] }}<br><span class="muted">{{ $row['actor_role'] }}</span></td>
                    <td class="badge {{ $row['action_class'] }}">{{ $row['action_label'] }}</td>
                    <td>
                        {{ $row['subject'] }}
                        @if ($row['subject_descriptor'])
                            <br><span class="muted">{{ $row['subject_descriptor'] }}</span>
                        @endif
                    </td>
                    <td>{{ $row['summary'] }}</td>
                    <td>
                        @forelse ($row['changes'] as $change)
                            <div class="change">
                                <strong>{{ $change['label'] }}:</strong>
                                @if ($row['is_updated'])
                                    <span class="old">{{ $change['old_display'] }}</span> ←
                                    <span class="new">{{ $change['new_display'] }}</span>
                                @else
                                    {{ $change['new_display'] !== 'غير محدد' ? $change['new_display'] : $change['old_display'] }}
                                @endif
                            </div>
                        @empty
                            <span class="muted">-</span>
                        @endforelse
                        @if ($row['changes_more'] > 0)
                            <span class="muted">+{{ $row['changes_more'] }} تغيير آخر</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center;">لا توجد سجلات مطابقة للفلاتر.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
