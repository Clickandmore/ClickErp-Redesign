@php
    $timezone = config('app.timezone', 'Asia/Riyadh');
    $amount = round((float) $bond->value, 2);
    $riyals = (int) floor($amount);
    $halalas = (int) round(($amount - $riyals) * 100);
    if ($halalas === 100) {
        $riyals++;
        $halalas = 0;
    }

    $spellout = class_exists(NumberFormatter::class) ? new NumberFormatter('ar', NumberFormatter::SPELLOUT) : null;
    $riyalsText = ($spellout ? $spellout->format($riyals) : $riyals) . ' ريال';
    $halalasText = $halalas > 0 ? (($spellout ? $spellout->format($halalas) : $halalas) . ' هللة') : null;
    $amountInWords = trim($riyalsText . ($halalasText ? ' و ' . $halalasText : '')) . ' فقط لا غير';
    $amountFormatted = number_format($amount, 2, '.', ',');

    $companyNameAr = optional(optional($setting_web ?? null)->translate('ar'))->website_name
        ?: optional($setting_web ?? null)->website_name
        ?: config('app.name', 'Platinum');
    $companyNameEn = optional(optional($setting_web ?? null)->translate('en'))->website_name
        ?: optional($setting_web ?? null)->website_name
        ?: config('app.name', 'Platinum');
    $logoUrl = isset($setting_web) && !empty($setting_web->logo_header) ? showFile($setting_web->logo_header) : null;
    $taxNumber = isset($setting_web) && !empty($setting_web->tax_number) ? $setting_web->tax_number : '-';

    $statement = trim((string) $bond->statement);
    $requestNumber = '-';
    foreach ([
        '/رقم\s+الطلب\s*[:#-]?\s*(\d+)/u',
        '/رقم\s+العقد\s*[:#-]?\s*(\d+)/u',
        '/العقد\s*#\s*(\d+)/u',
        '/العقد\s+(\d+)/u',
        '/الطلب\s*#\s*(\d+)/u',
        '/الطلب\s+(\d+)/u',
    ] as $pattern) {
        if (preg_match($pattern, $statement, $matches)) {
            $requestNumber = $matches[1];
            break;
        }
    }

    $receivedFrom = trim(implode(' / ', array_filter([
        $bond->creditAccount->name ?? null,
        !empty($bond->creditAccount->code) ? $bond->creditAccount->code : null,
    ]))) ?: '-';

    $collectionAccount = trim(implode(' / ', array_filter([
        $bond->debitAccount->name ?? null,
        !empty($bond->debitAccount->code) ? $bond->debitAccount->code : null,
    ]))) ?: '-';

    $paymentMethod = getEnumValue('bond_payment_type', $bond->payment_type) ?: '-';
    $paymentReference = $bond->transfer_number ?: (optional($bond->check)->check_num ?: '-');
    $paymentDetails = implode(' / ', array_filter([
        $paymentMethod,
        ($bond->payment_type ?? null) != \Nami\Finance\Enums\BondPaymentTypes::Cash->value ? $collectionAccount : null,
        $paymentReference !== '-' ? $paymentReference : null,
    ]));

    $accountant = optional($bond->createdBy)->name
        ?: optional(auth('admin')->user())->name
        ?: 'النظام';

    $gregorianDate = $bond->date ?: now($timezone)->toDateString();
    $hijriDate = '-';
    if (class_exists(IntlDateFormatter::class)) {
        try {
            $dateObject = new DateTime($gregorianDate, new DateTimeZone($timezone));
            $formatter = new IntlDateFormatter(
                'ar_SA@calendar=islamic-umalqura',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                $timezone,
                IntlDateFormatter::TRADITIONAL,
                'dd-MM-yyyy هـ'
            );
            $hijriDate = $formatter->format($dateObject) ?: '-';
        } catch (Throwable $exception) {
            $hijriDate = '-';
        }
    }

    $taxAmount = (float) (optional($bond->bondTax)->tax_amount ?? 0);
    $netAmount = (float) ($bond->is_tax ? ($bond->net ?? $amount) : $amount);
    $printedAt = now($timezone)->format('Y-m-d H:i:s');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('finance::finance.print_qabd_sarf') }}</title>
    <style>
        :root {
            --border: #d1d5db;
            --muted: #6b7280;
            --text: #111827;
            --soft: #f3f4f6;
            --accent: #0f172a;
            --teal: #0f7a87;
            --teal-soft: #ecfeff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            direction: rtl;
            color: var(--text);
            line-height: 1.6;
            font-size: 14px;
            font-family: "IBM Plex Sans Arabic", "Tahoma", "Segoe UI", sans-serif;
            background: #f8fafc;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page {
            width: min(1080px, 100%);
            margin: 20px auto;
            padding: 16px;
        }

        .print-actions {
            width: min(1080px, 100%);
            margin: 20px auto 0;
            padding: 0 16px;
            display: flex;
            justify-content: flex-start;
        }

        .print-button {
            border: 0;
            border-radius: 8px;
            padding: 10px 20px;
            color: #fff;
            background: var(--teal);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .print-button:hover {
            background: #0c6570;
        }

        .print-button:focus-visible {
            outline: 3px solid rgba(15, 122, 135, 0.25);
            outline-offset: 3px;
        }

        .voucher {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .brand-bar {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-logo img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .brand-copy h3,
        .brand-copy h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
        }

        .brand-copy h4 {
            margin-top: 4px;
            font-size: 15px;
            font-weight: 600;
        }

        .brand-copy p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .brand-stamp {
            min-width: 180px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: linear-gradient(135deg, var(--teal-soft) 0%, #f8fafc 100%);
            text-align: center;
        }

        .brand-stamp span {
            display: block;
            color: var(--muted);
            font-size: 12px;
        }

        .brand-stamp strong {
            display: block;
            margin-top: 4px;
            color: var(--teal);
            font-size: 18px;
            font-weight: 700;
        }

        .title-row {
            text-align: center;
            margin-bottom: 14px;
        }

        .title-row h1 {
            margin: 0;
            font-size: 32px;
            color: var(--accent);
        }

        .title-row h2 {
            margin: 4px 0 0;
            font-size: 26px;
            color: #111;
        }

        .top-grid {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            table-layout: fixed;
        }

        .top-grid .cell {
            display: table-cell;
            vertical-align: top;
            width: 33.33%;
            padding: 0 6px;
        }

        .voucher-no-box,
        .amount-box,
        .meta-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            height: 100%;
        }

        .voucher-no-box {
            text-align: center;
        }

        .voucher-no-box .label,
        .amount-box .label {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .voucher-no-box .value {
            color: #dc2626;
            font-size: 34px;
            font-weight: 700;
            line-height: 1.1;
        }

        .amount-box {
            background: linear-gradient(135deg, var(--teal-soft) 0%, #f8fafc 100%);
            text-align: center;
        }

        .amount-box .value {
            color: var(--teal);
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .amount-box .note {
            margin-top: 8px;
            color: #1f2937;
            font-size: 13px;
            line-height: 1.8;
        }

        .meta-box {
            line-height: 1.9;
            color: #374151;
        }

        .meta-box strong {
            color: var(--accent);
        }

        .info-table,
        .summary {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-label {
            width: 18%;
            background: #f9fafb;
            font-weight: 700;
            color: #374151;
        }

        .info-value {
            width: 32%;
        }

        .section-title {
            margin: 14px 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 5px 8px;
        }

        .desc-box {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 10px 12px;
            color: #1f2937;
            font-size: 15px;
            line-height: 1.9;
            margin-bottom: 12px;
            word-break: break-word;
            min-height: 74px;
        }

        .summary-wrap {
            margin-top: 10px;
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .summary-left,
        .summary-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }

        .summary td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
        }

        .summary .label {
            background: #f9fafb;
            font-weight: 700;
            width: 60%;
        }

        .summary .total td {
            background: var(--teal-soft);
            font-weight: 700;
        }

        .num {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
            font-weight: 700;
        }

        .signatures {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .signature-box {
            border-top: 1px dashed #cbd5e1;
            padding-top: 14px;
            text-align: center;
        }

        .signature-box span {
            display: block;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .signature-box strong {
            display: block;
            min-height: 24px;
            color: var(--accent);
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" class="print-button" onclick="window.print()">
            طباعة / حفظ السند PDF
        </button>
    </div>

    <div class="page">
        <div class="voucher">
            <div class="brand-bar">
                <div class="brand-logo">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="company logo">
                    @endif

                    <div class="brand-copy">
                        <h3>{{ $companyNameAr }}</h3>
                        <h4>{{ $companyNameEn }}</h4>
                        <p>الرقم الضريبي / VAT: {{ $taxNumber }}</p>
                    </div>
                </div>

                <div class="brand-stamp">
                    <span>نوع المستند</span>
                    <strong>سند قبض</strong>
                    <span>Receipt Voucher</span>
                </div>
            </div>

            <div class="title-row">
                <h1>سند قبض</h1>
                <h2>Receipt Voucher</h2>
            </div>

            <div class="top-grid">
                <div class="cell">
                    <div class="voucher-no-box">
                        <div class="label">رقم السند</div>
                        <div class="value">{{ $bond->code ?: $bond->id }}</div>
                    </div>
                </div>

                <div class="cell">
                    <div class="amount-box">
                        <div class="label">المبلغ المقبوض</div>
                        <div class="value">{{ $amountFormatted }} ريال</div>
                        <div class="note">{{ $amountInWords }}</div>
                    </div>
                </div>

                <div class="cell">
                    <div class="meta-box">
                        <div>التاريخ الميلادي: <strong dir="ltr">{{ $gregorianDate }}</strong></div>
                        <div>التاريخ الهجري: <strong>{{ $hijriDate }}</strong></div>
                        <div>وقت الطباعة: <strong dir="ltr">{{ $printedAt }}</strong></div>
                    </div>
                </div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="info-label">استلمنا من</td>
                    <td class="info-value">{{ $receivedFrom }}</td>
                    <td class="info-label">رقم الطلب</td>
                    <td class="info-value">{{ $requestNumber }}</td>
                </tr>
                <tr>
                    <td class="info-label">طريقة السداد</td>
                    <td class="info-value">{{ $paymentMethod }}</td>
                    <td class="info-label">حساب التحصيل</td>
                    <td class="info-value">{{ $collectionAccount }}</td>
                </tr>
                <tr>
                    <td class="info-label">بيانات التحويل / الشيك</td>
                    <td class="info-value">{{ $paymentReference }}</td>
                    <td class="info-label">بواسطة</td>
                    <td class="info-value">{{ $accountant }}</td>
                </tr>
            </table>

            <p class="section-title">البيان</p>
            <div class="desc-box">{{ $statement ?: '-' }}</div>

            <div class="summary-wrap">
                <div class="summary-left">
                    <table class="summary">
                        <tr>
                            <td class="label">القيمة الأساسية</td>
                            <td class="num">{{ number_format($amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">الضريبة</td>
                            <td class="num">{{ number_format($taxAmount, 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td class="label">الصافي</td>
                            <td class="num">{{ number_format($netAmount, 2) }} ريال سعودي</td>
                        </tr>
                    </table>
                </div>

                <div class="summary-right">
                    <table class="summary">
                        <tr>
                            <td class="label">تفاصيل السداد</td>
                            <td>{{ $paymentDetails ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">الحساب الدائن</td>
                            <td>{{ $receivedFrom }}</td>
                        </tr>
                        <tr>
                            <td class="label">الحساب المدين</td>
                            <td>{{ $collectionAccount }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="signatures">
                <div class="signature-box">
                    <span>المحاسب / Accountant</span>
                    <strong>{{ $accountant }}</strong>
                </div>
                <div class="signature-box">
                    <span>المستلم / Receiver</span>
                    <strong>____________________</strong>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
