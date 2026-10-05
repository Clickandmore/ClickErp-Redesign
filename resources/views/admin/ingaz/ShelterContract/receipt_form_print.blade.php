<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ documentSetting('shelter_receipt_title', 'استلام عاملة وتعهد') }}</title>
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
            font-family: "IBM Plex Sans Arabic", "Noto Sans Arabic", Tahoma, "Segoe UI", Arial, sans-serif;
            font-size: 16px;
            line-height: 1.9;
            letter-spacing: 0;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
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
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
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
            font-size: 19px;
            font-weight: 800;
        }

        .title {
            margin: 0;
            font-size: 31px;
            font-weight: 800;
            line-height: 1.3;
        }

        .subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
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
            font-size: 17px;
            font-weight: 800;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 12px 13px;
            border: 1px solid var(--line);
            vertical-align: middle;
            line-height: 1.7;
        }

        .label {
            width: 18%;
            background: #f8fbfe;
            color: var(--accent);
            font-weight: 800;
            white-space: nowrap;
        }

        .value {
            width: 32%;
            background: #fff;
        }

        .field-value {
            display: inline-block;
            width: 100%;
            font-weight: 600;
            unicode-bidi: plaintext;
            word-break: break-word;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .statement {
            margin-top: 16px;
            padding: 20px 22px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fbfdff;
            font-size: 16.5px;
            line-height: 2;
        }

        .statement p {
            margin: 0 0 14px;
            text-align: justify;
            text-justify: inter-word;
        }

        .statement p:last-child {
            margin-bottom: 0;
        }

        .intro-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .intro-grid td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }

        .intro-label {
            width: 155px;
            color: var(--accent);
            font-weight: 800;
            white-space: nowrap;
        }

        .intro-value {
            padding-right: 8px;
            font-weight: 600;
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
            font-weight: 800;
            margin-bottom: 4px;
        }

        .footer-note {
            margin-top: 18px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
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
            font-weight: 800;
            font-size: 13px;
        }

        @media (max-width: 640px) {
            body {
                font-size: 15px;
            }

            .header,
            .content {
                padding-right: 18px;
                padding-left: 18px;
            }

            .title {
                font-size: 25px;
            }

            .info-table td {
                padding: 10px;
            }
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
        $documentBranding = documentBranding();
        $documentTitle = documentSetting('shelter_receipt_title', 'استلام عاملة وتعهد');
        $documentSubtitle = documentSetting('shelter_receipt_subtitle', 'نموذج تسليم لحالات الإيواء');
        $documentCompanyName = $document_company_name ?? $documentBranding['company_name'];
        $documentOfficeName = $document_office_name ?? $documentBranding['office_name'];
        $logoPath = documentLogoPath() ?: public_path('admin/assets/img/company_logo.jpeg');
        $logoUrl = documentLogoUrl(asset('admin/assets/img/company_logo.jpeg'));
        $defaultReceiptText = replaceDocumentPlaceholders((string) documentSetting(
            'shelter_receipt_default_text',
            'كما أتعهد في حالة نقل خدمات العاملة إلى صاحب عمل آخر بإخطار [اسم_المكتب] وتزويده كتابياً بكافة بيانات صاحب العمل الجديد من اسم وهوية وعنوان ورقم جوال. وفي حال عدم القيام بذلك أتحمل كامل المسؤولية عن أي مطالبات أو التزامات تخص العاملة.'
        ));
        $defaultSettlementText = replaceDocumentPlaceholders((string) documentSetting(
            'shelter_receipt_default_settlement_text',
            'كما أتعهد بتسليم العاملة كافة مستحقاتها وإثبات ذلك بمخالصة رسمية تفيد باستلامها جميع رواتبها ومستحقاتها وعدم مطالبتها بأي حق، وذلك وفق النموذج المعتمد لدى الجهات الرسمية.'
        ));
        $receiptText = trim((string) $text_receipt);
        $documentFooterNote = $document_footer_note ?? $documentBranding['footer_note'];
    @endphp

    <div class="page-shell">
        <div class="toolbar">
            <a class="btn" href="{{ $print_url }}" target="_blank" rel="noopener">طباعة النموذج</a>
            <a class="btn secondary" href="{{ $print_url }}" target="_blank" rel="noopener">حفظ PDF</a>
        </div>

        <div class="paper">
            <div class="header">
                @if ($logoUrl && file_exists($logoPath))
                    <img src="{{ $logoUrl }}" alt="{{ $documentCompanyName }}">
                @endif
                <p class="company">{{ $documentCompanyName }}</p>
                <h1 class="title">{{ $documentTitle }}</h1>
                <p class="subtitle">{{ $documentSubtitle }}</p>
            </div>

            <div class="content">
                <h2 class="section-title">بيانات العميل والعامل/ة</h2>
                <table class="info-table">
                    <tr>
                        <td class="label">اسم العميل</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $client_name ?: '--' }}</span></td>
                        <td class="label">رقم الهوية</td>
                        <td class="value"><span class="field-value ltr">{{ $national_identification_number ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">اسم العامل/ة</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $cvs_name ?: '--' }}</span></td>
                        <td class="label">رقم الجواز</td>
                        <td class="value"><span class="field-value ltr">{{ $passport_id ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">الجنسية</td>
                        <td class="value"><span class="field-value" dir="auto">{{ $nationality_id ?: '--' }}</span></td>
                        <td class="label">تاريخ الوصول</td>
                        <td class="value"><span class="field-value ltr">{{ $arrive_date ?: '--' }}</span></td>
                    </tr>
                    <tr>
                        <td class="label">تاريخ الاستلام</td>
                        <td class="value"><span class="field-value ltr">{{ $date_receipt ?: '--' }}</span></td>
                        <td class="label">صفة المستلم</td>
                        <td class="value"><span class="field-value" dir="auto">صاحب العمل / من ينوب عنه</span></td>
                    </tr>
                </table>

                <div class="statement">
                    <h2 class="section-title">نص العقد / الإقرار</h2>
                    <table class="intro-grid">
                        <tr>
                            <td class="intro-label">أقر أنا</td>
                            <td class="intro-value"><strong><span class="field-value" dir="auto">{{ $client_name ?: '....................' }}</span></strong></td>
                        </tr>
                        <tr>
                            <td class="intro-label">هوية رقم</td>
                            <td class="intro-value"><strong><span class="field-value ltr">{{ $national_identification_number ?: '....................' }}</span></strong></td>
                        </tr>
                        <tr>
                            <td class="intro-label">بأنني قد استلمت العامل/ة</td>
                            <td class="intro-value"><strong><span class="field-value" dir="auto">{{ $cvs_name ?: '....................' }}</span></strong></td>
                        </tr>
                        <tr>
                            <td class="intro-label">من جهة</td>
                            <td class="intro-value"><strong>{{ $documentOfficeName }}</strong></td>
                        </tr>
                        <tr>
                            <td class="intro-label">بتاريخ</td>
                            <td class="intro-value"><strong><span class="field-value ltr">{{ $date_receipt ?: '--' }}</span></strong></td>
                        </tr>
                    </table>

                    @if ($receiptText !== '')
                        <p><span class="field-value" dir="auto">{!! nl2br(e($receiptText)) !!}</span></p>
                    @else
                        <p>{{ $defaultReceiptText }}</p>
                        <p>{{ $defaultSettlementText }}</p>
                    @endif

                    <p>والله ولي التوفيق.</p>
                </div>

                <table class="signatures">
                    <tr>
                        <td>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">اسم المستلم</div>
                                <div class="field-value" dir="auto">{{ $client_name ?: '--' }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="signature-box">
                                <div class="signature-line"></div>
                                <div class="signature-label">التوقيع</div>
                                <div>................................</div>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="footer-note">
                    {{ $documentFooterNote }}
                </div>

                <div class="meta-footer">
                    <table>
                        <tr>
                            <td>
                                <span class="meta-title">الجهة المتعاقدة</span>
                                <span class="field-value" dir="auto">{{ $documentCompanyName }}</span>
                            </td>
                            <td>
                                <span class="meta-title">تاريخ الاستلام / العقد</span>
                                <span class="field-value ltr">{{ $date_receipt ?: '--' }}</span>
                            </td>
                            <td>
                                <span class="meta-title">وقت توقيع / تسجيل العقد</span>
                                <span class="field-value ltr">{{ $contract_signed_at ?? '--' }}</span>
                            </td>
                            <td>
                                <span class="meta-title">تمت الطباعة بواسطة</span>
                                <span class="field-value" dir="auto">{{ $printed_by ?? 'النظام' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <span class="meta-title">وقت طباعة النموذج</span>
                                <span id="printed-at" class="field-value ltr">{{ $printed_at ?? '--' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
