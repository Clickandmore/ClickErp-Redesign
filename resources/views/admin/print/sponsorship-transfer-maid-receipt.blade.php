<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @php
        $documentBranding = documentBranding();
        $documentTitle = documentSetting('transfer_receipt_title', 'نموذج استلام عاملة نقل الخدمات');
        $documentSubtitle = documentSetting('transfer_receipt_subtitle', 'إقرار استلام عاملة وشروط نقل الخدمات');
        $documentCompanyName = $documentBranding['company_name'];
        // الشعار كـ data URI (يُحوَّل WebP إلى PNG) لأن mPDF لا يقرأ روابط R2 الموقّعة ولا صيغة WebP.
        $logoSrc = documentLogoDataUri();
        if (!$logoSrc && file_exists(public_path('admin/assets/img/company_logo.jpeg'))) {
            $logoSrc = public_path('admin/assets/img/company_logo.jpeg');
        }
        $documentFooterNote = $documentBranding['footer_note'];
    @endphp
    <title>{{ $documentTitle }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #0f172a;
            direction: rtl;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }

        .page {
            padding: 0;
        }

        .header {
            margin-bottom: 14px;
            padding-bottom: 12px;
            text-align: center;
            border-bottom: 2px solid #cbd5e1;
        }

        .logo {
            display: block;
            width: 200px;
            max-height: 70px;
            margin: 0 auto 8px;
        }

        .company-name {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }

        .title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            margin: 8px 0 0;
            font-size: 12px;
            color: #475569;
        }

        .issue-date {
            margin: 0 0 14px;
            font-weight: 700;
            text-align: left;
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
            margin-bottom: 14px;
        }

        .info-table td {
            padding: 7px 12px;
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
            margin-top: 10px;
            padding: 12px 14px;
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

        .terms {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .terms li {
            margin: 0 0 5px;
            padding-right: 26px;
            text-indent: -26px;
            text-align: justify;
        }

        .terms li:last-child {
            margin-bottom: 0;
        }

        .terms .num {
            display: inline-block;
            width: 22px;
            font-weight: 700;
            color: #1e3a8a;
            text-indent: 0;
        }

        .acknowledgement {
            margin-top: 14px;
            font-weight: 700;
        }

        .signature-table {
            margin-top: 18px;
        }

        .signature-cell {
            width: 50%;
            padding-top: 26px;
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
    <div class="page">
        <div class="header">
            @if ($logoSrc)
                <img class="logo" src="{{ $logoSrc }}" alt="{{ $documentCompanyName }}">
            @endif
            <p class="company-name">{{ $documentCompanyName }}</p>
            <h1 class="title">{{ $documentTitle }}</h1>
            <p class="subtitle">{{ $documentSubtitle }}</p>
        </div>

        <p class="issue-date">حرر بتاريخ: {{ $issue_date }}</p>

        <h2 class="section-title">بيانات العميل والعاملة</h2>
        <table class="info-table">
            <tr>
                <td class="label">اسم العميل</td>
                <td class="value">{{ $client_name }}</td>
                <td class="label">رقم الهوية</td>
                <td class="value">{{ $client_id_number }}</td>
            </tr>
            <tr>
                <td class="label">اسم العاملة</td>
                <td class="value">{{ $maid_name }}</td>
                <td class="label">الجواز / رقم الإقامة</td>
                <td class="value">{{ $maid_passport }}</td>
            </tr>
            <tr>
                <td class="label">الجنسية</td>
                <td class="value">{{ $maid_nationality }}</td>
                <td class="label">فترة التجربة</td>
                <td class="value">{{ $trial_period }}</td>
            </tr>
        </table>

        <h2 class="section-title">البيانات المالية</h2>
        <table class="info-table">
            <tr>
                <td class="label">تكلفة نقل الخدمات</td>
                <td class="value">{{ $contract_value }}</td>
                <td class="label">الراتب الشهري</td>
                <td class="value">{{ $monthly_salary }} ريال سعودي</td>
            </tr>
            <tr>
                <td class="label">المبلغ المدفوع</td>
                <td class="value">{{ $paid_amount }}</td>
                <td class="label">المبلغ المتبقي</td>
                <td class="value">{{ $remaining_amount }}</td>
            </tr>
        </table>

        <div class="statement">
            <p>
                أقر أنا <strong>{{ $client_name }}</strong>، هوية رقم <strong>{{ $client_id_number }}</strong>،
                باستلامي العاملة <strong>{{ $maid_name }}</strong>، جواز / رقم الإقامة <strong>{{ $maid_passport }}</strong>،
                من <strong>{{ $company_name }}</strong> وذلك لنقل خدماتها وفق الشروط التالي ذكرها:
            </p>

            <ol class="terms">
                <li><span class="num">1-</span>تكلفة نقل الخدمات مبلغ وقدره <strong>{{ $contract_value }}</strong> شامل رسوم نقل الخدمات والإقامة، حيث إن المبلغ المدفوع <strong>{{ $paid_amount }}</strong> والمبلغ المتبقي <strong>{{ $remaining_amount }}</strong>.</li>
                <li><span class="num">2-</span>الراتب الشهري للعاملة <strong>{{ $monthly_salary }}</strong> ريال سعودي وذلك اعتباراً من تاريخ استلامها.</li>
                <li><span class="num">3-</span>فترة التجربة للعاملة هي <strong>{{ $trial_period }}</strong> من تاريخ استلامها.</li>
                <li><span class="num">4-</span>في حال إعادة العاملة من قبل صاحب العمل قبل انتهاء فترة التجربة لأي سبب من الأسباب، سواء من صاحب العمل الجديد أو من العاملة، يخصم من المبلغ المدفوع (100) ريال عن كل يوم من تاريخ الاستلام حتى تاريخ التسليم، ويخصم ذلك من المبلغ المدفوع ويتم تحويل المبلغ المتبقي عند إرجاع العاملة.</li>
                <li><span class="num">5-</span>في حال الاعتداء على العاملة من قبل الطرف الثاني خلال فترة عملها لديه وثبوت ذلك، يحق للشركة مطالبة صاحب العمل الجديد بالتعويضات المقررة نظامياً أو إحالة الحالة للجهات المختصة إن تطلب الأمر ذلك.</li>
                <li><span class="num">6-</span>يتعهد صاحب العمل الجديد بإنهاء أي سبب يعيق نقل خدمات العاملة على اسمه خلال مدة أقصاها 10 أيام من تاريخ استلامه للعاملة.</li>
                <li><span class="num">7-</span>بعد نقل خدمات العاملة لا يحق لصاحب العمل الجديد مطالبة الشركة بأي تعويضات سواءً مالية أو غيرها.</li>
                <li><span class="num">8-</span>يتم احتساب 150 ريالاً عن كل يوم في حال عدم نقل العاملة بعد انتهاء فترة التجربة من قبل صاحب العمل الجديد.</li>
            </ol>

            <p class="acknowledgement">أقر أنني قرأت ما ذكر أعلاه وما جاء فيه من شروط وموافق عليه موافقة غير مشروطة، والله الموفق.</p>
        </div>

        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <span class="signature-line"></span>
                    <div class="signature-label">اسم المستلم</div>
                    <div>{{ $client_name }}</div>
                    <div>السجل المدني: {{ $client_id_number }}</div>
                </td>
                <td class="signature-cell">
                    <span class="signature-line"></span>
                    <div class="signature-label">التوقيع</div>
                    <div>................................</div>
                    <div>التاريخ: {{ $issue_date }}</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            {{ $documentFooterNote }}
        </div>
    </div>
</body>

</html>
