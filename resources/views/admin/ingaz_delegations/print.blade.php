<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة تفويض الإنجاز #{{ $delegation->id }}</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            color: #222;
            margin: 24px;
            line-height: 1.7;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            color: #666;
            margin: 6px 0 0;
            font-size: 14px;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section h3 {
            margin: 0 0 10px;
            font-size: 18px;
            border-right: 4px solid #0d6efd;
            padding-right: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            vertical-align: top;
            text-align: right;
        }

        th {
            width: 25%;
            background: #f8fafc;
        }

        .notes {
            border: 1px solid #d1d5db;
            padding: 12px;
            min-height: 70px;
            background: #fafafa;
        }

        .actions {
            margin-top: 24px;
            text-align: center;
        }

        .actions button {
            padding: 10px 20px;
            border: 0;
            background: #111827;
            color: #fff;
            cursor: pointer;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">تفويض الإنجاز #{{ $delegation->id }}</h1>
            <p class="subtitle">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
        <div>
            <strong>رقم إنجاز:</strong> {{ $delegation->ingaz_number ?: '-' }}<br>
            <strong>رقم مساند:</strong> {{ $delegation->msaned_contract_number ?: '-' }}
        </div>
    </div>

    <div class="section">
        <h3>البيانات الأساسية</h3>
        <table>
            <tr>
                <th>التاريخ الميلادي</th>
                <td>{{ optional($delegation->delegation_date_gregorian)->format('Y-m-d') }}</td>
                <th>التاريخ الهجري</th>
                <td>{{ $delegation->delegation_date_hijri ?: '-' }}</td>
            </tr>
            <tr>
                <th>الجنسية</th>
                <td>{{ $delegation->nationality?->sub_title ?? $delegation->nationality?->title ?? '-' }}</td>
                <th>المهنة</th>
                <td>{{ $delegation->profession?->title ?? '-' }}</td>
            </tr>
            <tr>
                <th>المسوق</th>
                <td>{{ $delegation->marketer?->name ?? '-' }}</td>
                <th>نوع العميل</th>
                <td>{{ $delegation->client_source_type === 'existing_in_program' ? 'موجود في البرنامج' : 'عميل منفصل' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>بيانات العميل</h3>
        <table>
            <tr>
                <th>اسم العميل</th>
                <td>{{ $delegation->user?->name ?? '-' }}</td>
                <th>رقم الهوية</th>
                <td>{{ $delegation->user?->national_num ?: '-' }}</td>
            </tr>
            <tr>
                <th>الجوال</th>
                <td>
                    @php($phone = $delegation->user?->phone)
                    {{ $phone && !str_starts_with($phone, 'ingaz-delegation-') ? trim(($delegation->user?->phone_code ?: '') . ' ' . $phone) : '-' }}
                </td>
                <th>العنوان</th>
                <td>{{ $delegation->user?->address ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>بيانات التأشيرة والعامل</h3>
        <table>
            <tr>
                <th>نوع التأشيرة</th>
                <td>{{ $visaTypeOptions[$delegation->visa_type] ?? $delegation->visa_type }}</td>
                <th>اسم العامل</th>
                <td>{{ $delegation->maid?->name ?? $delegation->worker_name ?: '-' }}</td>
            </tr>
            <tr>
                <th>رقم الجواز</th>
                <td>{{ $delegation->maid?->passport_num ?? $delegation->worker_passport_number ?: '-' }}</td>
                <th>تاريخ إصدار الجواز</th>
                <td>{{ optional($delegation->worker_passport_issue_date)->format('Y-m-d') ?: '-' }}</td>
            </tr>
            <tr>
                <th>رقم التأشيرة</th>
                <td>{{ $delegation->visa_number }}</td>
                <th>تاريخ التأشيرة</th>
                <td>{{ optional($delegation->visa_date)->format('Y-m-d') ?: '-' }}</td>
            </tr>
            <tr>
                <th>الديانة</th>
                <td>{{ $religionOptions[$delegation->worker_religion] ?? '-' }}</td>
                <th>الخبرة</th>
                <td>{{ $experienceOptions[$delegation->worker_experience] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>المكتب الخارجي</h3>
        <table>
            <tr>
                <th>اسم المكتب</th>
                <td>{{ $delegation->external_office_name ?: '-' }}</td>
                <th>رقم الترخيص</th>
                <td>{{ $delegation->external_office_license_number ?: '-' }}</td>
            </tr>
            <tr>
                <th>العنوان</th>
                <td>{{ $delegation->external_office_address ?: '-' }}</td>
                <th>الهاتف</th>
                <td>{{ $delegation->external_office_phone ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>ملاحظات</h3>
        <div class="notes">{{ $delegation->notes ?: 'لا توجد ملاحظات.' }}</div>
    </div>

    <div class="actions">
        <button type="button" onclick="window.print()">طباعة</button>
    </div>
</body>
</html>
