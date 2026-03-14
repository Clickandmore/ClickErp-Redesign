<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>نموذج تسليم عاملة عند الوصول</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --paper: #ffffff;
            --ink: #0f172a;
            --muted: #475569;
            --line: #d7e0ea;
            --accent: #163b65;
            --soft: #eef4fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            direction: rtl;
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            font-size: 15px;
            line-height: 1.8;
        }

        .page-shell {
            width: min(1024px, 100%);
            margin: 18px auto;
            padding: 0 14px 24px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.secondary {
            background: #fff;
            color: var(--accent);
        }

        .paper {
            background: var(--paper);
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            padding: 26px 32px 18px;
            border-bottom: 2px solid var(--line);
            text-align: center;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f9fd 100%);
        }

        .header img {
            display: block;
            max-width: 170px;
            max-height: 82px;
            margin: 0 auto 12px;
            object-fit: contain;
        }

        .company {
            margin: 0 0 8px;
            color: var(--accent);
            font-size: 18px;
            font-weight: 700;
        }

        .title {
            margin: 0;
            font-size: 30px;
            line-height: 1.3;
        }

        .subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .content {
            padding: 26px 32px 34px;
        }

        .section-title {
            margin: 0 0 12px;
            padding: 8px 12px;
            background: var(--soft);
            border-right: 4px solid var(--accent);
            color: var(--accent);
            font-size: 16px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 11px 12px;
            border: 1px solid var(--line);
            vertical-align: middle;
        }

        .label {
            width: 18%;
            background: #f8fbfe;
            color: var(--accent);
            font-weight: 700;
        }

        .value {
            width: 32%;
            background: #fff;
        }

        .field-value {
            display: inline-block;
            width: 100%;
            unicode-bidi: plaintext;
            word-break: break-word;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .statement {
            margin-top: 16px;
            padding: 18px 20px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fbfdff;
        }

        .statement p {
            margin: 0 0 14px;
            text-align: justify;
        }

        .statement p:last-child {
            margin-bottom: 0;
        }

        .signatures {
            width: 100%;
            margin-top: 40px;
        }

        .signatures td {
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }

        .signature-box {
            padding-top: 42px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid var(--ink);
            margin-bottom: 12px;
        }

        .signature-label {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .footer-note {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        .meta-footer {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px dashed var(--line);
        }

        .meta-footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-footer td {
            width: 25%;
            padding: 8px 10px;
            border: 1px solid var(--line);
            vertical-align: top;
            background: #fcfdff;
        }

        .meta-title {
            display: block;
            margin-bottom: 4px;
            color: var(--accent);
            font-weight: 700;
            font-size: 13px;
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .page-shell {
                width: auto;
                margin: 0;
                padding: 0;
            }

            .toolbar {
                display: none !important;
            }

            .paper {
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
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

    <div class="page-shell">
        <div class="toolbar">
            <a class="btn" href="{{ $print_url }}" target="_blank" rel="noopener">طباعة النموذج</a>
            <a class="btn secondary" href="{{ $pdf_url }}" target="_blank" rel="noopener">تنزيل PDF</a>
        </div>

        <div class="paper">
            <div class="header">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="شركة بلاتنيوم">
                @endif
                <p class="company">مكتب / شركة بلاتنيوم الشرقية للاستقدام</p>
                <h1 class="title">نموذج تسليم عاملة عند الوصول</h1>
                <p class="subtitle">نموذج خاص بعقود الاستقدام بعد وصول العاملة إلى المملكة</p>
            </div>

            <div class="content">
                <h2 class="section-title">بيانات العميل والعاملة</h2>
                <table class="info-table">
                    <tr>
                        <td class="label">رقم العقد</td>
                        <td class="value"><span class="field-value ltr">{{ $contract_number ?: '--' }}</span></td>
                        <td class="label">اسم العميل</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $client_name ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">رقم الهوية</td>
                        <td class="value"><span class="field-value ltr">{{ $national_identification_number ?: '--' }}</span></td>
                        <td class="label">رقم الجوال</td>
                        <td class="value"><span class="field-value ltr">{{ $client_phone ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">العنوان</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $client_address ?: '--' }}</span></td>
                        <td class="label">اسم العاملة</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $cvs_name ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">الجنسية</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $nationality_id ?: '--' }}</span></td>
                        <td class="label">رقم الجواز</td>
                        <td class="value"><span class="field-value ltr">{{ $passport_id ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">تاريخ الوصول</td>
                        <td class="value"><span class="field-value ltr">{{ $arrive_date_time ?: '--' }}</span></td>
                        <td class="label">تاريخ الاستلام</td>
                        <td class="value"><span class="field-value ltr">{{ $date_receipt_time ?: '--' }}</span></td>
                    </tr>
                </table>

                <div class="statement">
                    <h2 class="section-title">نص الإقرار والتسليم</h2>
                    <p>
                        أقر أنا <strong><span class="field-value" dir="auto">{{ $client_name ?: '....................' }}</span></strong>،
                        هوية رقم <strong><span class="field-value ltr">{{ $national_identification_number ?: '....................' }}</span></strong>،
                        بأنني قد استلمت العاملة
                        <strong><span class="field-value" dir="auto">{{ $cvs_name ?: '....................' }}</span></strong>
                        ذات الجواز رقم
                        <strong><span class="field-value ltr">{{ $passport_id ?: '....................' }}</span></strong>
                        من مكتب / شركة بلاتنيوم الشرقية للاستقدام بتاريخ
                        <strong><span class="field-value ltr">{{ $date_receipt_time ?: '--' }}</span></strong>.
                    </p>

                    @if ($receiptText !== '')
                        <p><span class="field-value" dir="auto">{!! nl2br(e($receiptText)) !!}</span></p>
                    @else
                        <p>{{ $defaultReceiptText }}</p>
                        <p>{{ $defaultTransferText }}</p>
                        <p>{{ $defaultSettlementText }}</p>
                    @endif

                    <p>والله ولي التوفيق.</p>
                </div>

                <table class="signatures">
                    <tr>
                        <td>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">المستلم</div>
                                <div class="field-value" dir="auto">{{ $client_name ?: '--' }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">ممثل الشركة</div>
                                <div class="field-value" dir="auto">{{ $company_representative ?: 'ممثل الشركة' }}</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="footer-note">
                    يمكن طباعة النموذج أو تنزيله بصيغة PDF من الأزرار أعلى الصفحة.
                </div>

                <div class="meta-footer">
                    <table>
                        <tr>
                            <td>
                                <span class="meta-title">الجهة المتعاقدة</span>
                                <span class="field-value" dir="auto">مكتب / شركة بلاتنيوم الشرقية للاستقدام</span>
                            </td>
                            <td>
                                <span class="meta-title">تاريخ الاستلام</span>
                                <span class="field-value ltr">{{ $date_receipt_time ?: '--' }}</span>
                            </td>
                            <td>
                                <span class="meta-title">تسجيل العقد</span>
                                <span class="field-value ltr">{{ $contract_signed_at ?? '--' }}</span>
                            </td>
                            <td>
                                <span class="meta-title">تمت المعاينة بواسطة</span>
                                <span class="field-value" dir="auto">{{ $printed_by ?? 'النظام' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
