@php
    $isPdf = request()->query('driver') === 'dompdf';
    $statusClass = match ($dispatchState['code']) {
        'completed' => '#198754',
        'outdated' => '#f59f00',
        'incomplete' => '#dc3545',
        default => '#6c757d',
    };
    $documentCompanyName = documentSetting('document_company_name', documentSetting('website_name', config('app.name')));
    $documentFooterNote = documentBranding()['footer_note'];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسالية المكتب الخارجي - العقد #{{ $contract->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            direction: rtl;
            color: #1f2937;
            background: #f4f6f8;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            display: {{ $isPdf ? 'none' : 'flex' }};
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
        }

        .toolbar-actions a,
        .toolbar-actions button {
            border: 0;
            background: #0d6efd;
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            margin-inline-start: 8px;
        }

        .toolbar-actions a.secondary {
            background: #6c757d;
        }

        .document {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #0f4c81, #1d7ddf);
            color: #fff;
            padding: 28px 32px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .header p {
            margin: 0;
            opacity: 0.92;
            font-size: 15px;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 18px 32px 0;
        }

        .meta-item {
            background: #eef5ff;
            color: #0f4c81;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 14px;
        }

        .status-note {
            margin: 20px 32px 0;
            padding: 12px 16px;
            border-radius: 10px;
            background: #f8fafc;
            border-right: 4px solid {{ $statusClass }};
            font-size: 14px;
        }

        .content {
            padding: 24px 32px 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border: 1px solid #dbe3ea;
            padding: 12px 14px;
            text-align: right;
            vertical-align: top;
            font-size: 15px;
        }

        th {
            width: 28%;
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }

        .footer {
            padding: 0 32px 32px;
            color: #64748b;
            font-size: 13px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .document {
                box-shadow: none;
                max-width: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>إرسالية المكتب الخارجي</strong>
        </div>
        <div class="toolbar-actions">
            <a href="{{ $previewUrl }}" class="secondary">تحديث العرض</a>
            <a href="{{ $pdfUrl }}">تنزيل PDF</a>
            <button type="button" onclick="window.print()">طباعة</button>
        </div>
    </div>

    <div class="document">
        <div class="header">
            <h1>إرسالية المكتب الخارجي</h1>
            <p>العقد رقم #{{ $contract->id }} - {{ $documentCompanyName }}</p>
        </div>

        <div class="meta">
            <div class="meta-item">تاريخ الإرسالية: {{ optional($dispatch->dispatch_date)->format('Y-m-d') ?: '-' }}</div>
            <div class="meta-item">رقم العقد: {{ $dispatch->contract_number ?: $contract->id }}</div>
            <div class="meta-item">محطة الوصول: {{ $dispatch->arrival_station ?: '-' }}</div>
        </div>

        <div class="status-note">
            {{ $dispatchState['message'] }}
        </div>

        <div class="content">
            <table>
                <tbody>
                    <tr>
                        <th>رقم العقد</th>
                        <td>{{ $dispatch->contract_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>رقم التأشيرة</th>
                        <td>{{ $dispatch->visa_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>اسم الكفيل / العميل</th>
                        <td>{{ $dispatch->sponsor_name ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>رقم الهوية</th>
                        <td>{{ $dispatch->sponsor_identity_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>اسم العاملة / المستقدم</th>
                        <td>{{ $dispatch->worker_name ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>رقم الجواز</th>
                        <td>{{ $dispatch->passport_number ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>العمر</th>
                        <td>{{ $dispatch->worker_age ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>الخبرة</th>
                        <td>{{ $dispatch->worker_experience ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>الديانة</th>
                        <td>{{ $dispatch->worker_religion ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>المكتب الخارجي</th>
                        <td>{{ $dispatch->office_name ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>تكلفة العقد بالدولار</th>
                        <td>
                            {{ $dispatch->office_contract_cost_usd !== null ? number_format((float) $dispatch->office_contract_cost_usd, 2) . ' USD' : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>المقابل بالريال</th>
                        <td>
                            {{ $dispatch->office_contract_cost_usd !== null ? number_format((float) $dispatch->office_contract_cost_usd * $usdToSarRate, 2) . ' ريال' : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>محطة الوصول</th>
                        <td>{{ $dispatch->arrival_station ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            {{ $documentFooterNote }}<br>
            تم إنشاء هذه النسخة من النظام بتاريخ {{ now()->format('Y-m-d H:i') }}.
        </div>
    </div>
</body>
</html>
