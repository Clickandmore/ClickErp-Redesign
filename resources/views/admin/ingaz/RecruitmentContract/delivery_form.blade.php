<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>نموذج تسليم عاملة عند الوصول</title>
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
            padding: 28px 32px;
        }

        .header {
            margin-bottom: 22px;
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
            width: 18%;
            font-weight: 700;
            color: #1e3a8a;
            background: #f8fafc;
        }

        .value {
            width: 32%;
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

        .signature-table td {
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
        $defaultReceiptText = 'أقر بأنني استلمت العاملة الموضحة بياناتها أعلاه من مكتب / شركة بلاتنيوم الشرقية للاستقدام، وبذلك تصبح مسؤولية الاستلام والمتابعة على المستلم من تاريخ الاستلام المثبت في هذا النموذج.';
        $defaultTransferText = 'كما أتعهد في حالة نقل خدمات العاملة إلى صاحب عمل آخر بإخطار مكتب / شركة بلاتنيوم الشرقية للاستقدام وتزويده كتابياً بكافة بيانات صاحب العمل الجديد من اسم وهوية وعنوان ورقم جوال.';
        $defaultSettlementText = 'كما أتعهد بتسليم العاملة كافة مستحقاتها وإثبات ذلك بمخالصة رسمية تفيد باستلامها جميع رواتبها ومستحقاتها وعدم مطالبتها بأي حق، وذلك وفق النموذج المعتمد لدى الجهات الرسمية.';
        $receiptText = trim((string) $text_receipt);
    @endphp

    <div class="page">
        <div class="header">
            @if (file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="شركة بلاتنيوم">
            @endif
            <p class="company-name">مكتب / شركة بلاتنيوم الشرقية للاستقدام</p>
            <h1 class="title">نموذج تسليم عاملة عند الوصول</h1>
            <p class="subtitle">نموذج إلكتروني خاص بعقود الاستقدام بعد وصول العاملة إلى المملكة</p>
        </div>

        <h2 class="section-title">بيانات العميل والعاملة</h2>
        <table class="info-table">
            <tr>
                <td class="label">رقم العقد</td>
                <td class="value">{{ $contract_number ?: '--' }}</td>
                <td class="label">اسم العميل</td>
                <td class="value">{{ $client_name ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">رقم الهوية</td>
                <td class="value">{{ $national_identification_number ?: '--' }}</td>
                <td class="label">رقم الجوال</td>
                <td class="value">{{ $client_phone ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">العنوان</td>
                <td class="value">{{ $client_address ?: '--' }}</td>
                <td class="label">اسم العاملة</td>
                <td class="value">{{ $cvs_name ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">الجنسية</td>
                <td class="value">{{ $nationality_id ?: '--' }}</td>
                <td class="label">رقم الجواز</td>
                <td class="value">{{ $passport_id ?: '--' }}</td>
            </tr>
            <tr>
                <td class="label">تاريخ الوصول</td>
                <td class="value">{{ $arrive_date_time ?: '--' }}</td>
                <td class="label">تاريخ الاستلام</td>
                <td class="value">{{ $date_receipt_time ?: '--' }}</td>
            </tr>
        </table>

        <div class="statement">
            <p>
                أقر أنا <strong>{{ $client_name ?: '....................' }}</strong>، هوية رقم
                <strong>{{ $national_identification_number ?: '....................' }}</strong>، بأنني قد استلمت
                العاملة <strong>{{ $cvs_name ?: '....................' }}</strong> ذات الجواز رقم
                <strong>{{ $passport_id ?: '....................' }}</strong> من مكتب / شركة بلاتنيوم الشرقية
                للاستقدام بتاريخ <strong>{{ $date_receipt_time ?: '--' }}</strong>.
            </p>

            @if ($receiptText !== '')
                <p>{!! nl2br(e($receiptText)) !!}</p>
            @else
                <p>{{ $defaultReceiptText }}</p>
                <p>{{ $defaultTransferText }}</p>
                <p>{{ $defaultSettlementText }}</p>
            @endif

            <p>والله ولي التوفيق.</p>
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    <span class="signature-line"></span>
                    <div class="signature-label">اسم المستلم</div>
                    <div>{{ $client_name ?: '--' }}</div>
                </td>
                <td>
                    <span class="signature-line"></span>
                    <div class="signature-label">ممثل الشركة</div>
                    <div>{{ $company_representative ?: 'ممثل الشركة' }}</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            تم إنشاء هذا النموذج إلكترونياً بواسطة نظام بلاتنيوم.
        </div>
    </div>
</body>

</html>
