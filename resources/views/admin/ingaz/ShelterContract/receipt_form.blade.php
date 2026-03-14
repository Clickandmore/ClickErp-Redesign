<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>نموذج استلام عاملة وتعهد</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #0f172a;
            direction: rtl;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 14px;
            line-height: 1.8;
        }

        .page {
            padding: 32px 36px;
        }

        .header {
            margin-bottom: 24px;
            padding-bottom: 18px;
            text-align: center;
            border-bottom: 2px solid #cbd5e1;
        }

        .logo {
            display: block;
            width: 150px;
            margin: 0 auto 10px;
        }

        .company-name {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }

        .title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            margin: 8px 0 0;
            font-size: 12px;
            color: #475569;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 10px 12px;
            border: 1px solid #dbe4f0;
            vertical-align: top;
        }

        .label {
            width: 22%;
            font-weight: 700;
            color: #1e3a8a;
            background: #f8fafc;
        }

        .value {
            width: 28%;
        }

        .statement {
            margin-top: 14px;
            padding: 16px 18px;
            border: 1px solid #dbe4f0;
            background: #f8fafc;
            text-align: justify;
        }

        .statement p {
            margin: 0 0 12px;
        }

        .statement p:last-child {
            margin-bottom: 0;
        }

        .signature-table {
            margin-top: 36px;
        }

        .signature-cell {
            width: 50%;
            padding-top: 42px;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            display: block;
            margin-bottom: 10px;
            border-top: 1px solid #0f172a;
        }

        .signature-label {
            font-weight: 700;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #475569;
        }
    </style>
</head>

<body>
    @php
        $logoPath = public_path('admin/assets/img/company_logo.png');
        $defaultReceiptText = 'كما يتعهد صاحب العمل في حالة نقل خدمات العاملة إلى صاحب عمل آخر بإخطار مكتب بلاتنيوم الشرقية للاستقدام وتزويده كتابياً بكافة بيانات صاحب العمل الجديد من اسم وهوية وعنوان ورقم جوال. وفي حال عدم القيام بذلك يكون صاحب العمل الأول مسؤولاً مسؤولية كاملة عن كل ما يحدث للعاملة أو أي مطالبات تتقدم بها.';
        $defaultSettlementText = 'ويتعهد صاحب العمل بتسليم العاملة كافة مستحقاتها وإثبات ذلك بمخالصة تفيد باستلامها جميع رواتبها ومستحقاتها وعدم مطالبتها بأي حق، وتكون المخالصة حسب النموذج الرسمي المعتمد لدى الملحقية العمالية سواء عادت بعد نهاية عقدها أو قبله.';
        $receiptText = trim((string) $text_receipt);
    @endphp

    <div class="page">
        <div class="header">
            @if (file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="شركة بلاتينيوم">
            @endif
            <p class="company-name">مكتب بلاتنيوم الشرقية للاستقدام</p>
            <h1 class="title">استلام عاملة وتعهد</h1>
            <p class="subtitle">نموذج تسليم مخصص لحالات الإيواء</p>
        </div>

        <h2 class="section-title">بيانات العميل والعاملة</h2>
        <table class="info-table">
            <tr>
                <td class="label">اسم العميل</td>
                <td class="value">{{ $client_name ?: '--' }}</td>
                <td class="label">رقم الهوية</td>
                <td class="value">{{ $national_identification_number ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">اسم العاملة</td>
                <td class="value">{{ $cvs_name ?: '--' }}</td>
                <td class="label">رقم الجواز</td>
                <td class="value">{{ $passport_id ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">الجنسية</td>
                <td class="value">{{ $nationality_id ?: '--' }}</td>
                <td class="label">تاريخ الوصول</td>
                <td class="value">{{ $arrive_date ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">تاريخ الاستلام</td>
                <td class="value">{{ $date_receipt ?: '--' }}</td>
                <td class="label">صفة المستلم</td>
                <td class="value">صاحب العمل / من ينوب عنه</td>
            </tr>
        </table>

        <div class="statement">
            <p>
                أقر أنا <strong>{{ $client_name ?: '....................' }}</strong>، هوية رقم
                <strong>{{ $national_identification_number ?: '....................' }}</strong>، بأنني قد استلمت العاملة
                <strong>{{ $cvs_name ?: '....................' }}</strong> من مكتب بلاتنيوم الشرقية للاستقدام بتاريخ
                <strong>{{ $date_receipt ?: '--' }}</strong>.
            </p>

            @if ($receiptText !== '')
                <p>{!! nl2br(e($receiptText)) !!}</p>
            @else
                <p>{{ $defaultReceiptText }}</p>
                <p>{{ $defaultSettlementText }}</p>
            @endif

            <p>والله ولي التوفيق.</p>
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <span class="signature-line"></span>
                    <div class="signature-label">اسم المستلم</div>
                    <div>{{ $client_name ?: '--' }}</div>
                </td>
                <td class="signature-cell">
                    <span class="signature-line"></span>
                    <div class="signature-label">التوقيع</div>
                    <div>................................</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            تم إنشاء هذا النموذج إلكترونياً بواسطة نظام بلاتنيوم.
        </div>
    </div>
</body>

</html>
