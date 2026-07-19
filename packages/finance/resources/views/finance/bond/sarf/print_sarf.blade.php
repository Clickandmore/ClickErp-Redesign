@php
    $timezone = config('app.timezone', 'Asia/Riyadh');
    $amount = round((float) $bond->value, 2);
    $riyals = (int) floor($amount);
    $halalas = (int) round(($amount - $riyals) * 100);
    if ($halalas === 100) {
        $riyals++;
        $halalas = 0;
    }

    $spellout = class_exists(NumberFormatter::class)
        ? new NumberFormatter('ar', NumberFormatter::SPELLOUT)
        : null;
    $amountInWords = ($spellout ? $spellout->format($riyals) : $riyals) . ' ريال';
    if ($halalas > 0) {
        $amountInWords .= ' و' . ($spellout ? $spellout->format($halalas) : $halalas) . ' هللة';
    }
    $amountInWords .= ' فقط لا غير';

    $companyNameAr = optional(optional($setting_web ?? null)->translate('ar'))->website_name
        ?: optional($setting_web ?? null)->website_name
        ?: config('app.name', 'ClickERP');
    $companyNameEn = optional(optional($setting_web ?? null)->translate('en'))->website_name
        ?: optional($setting_web ?? null)->website_name
        ?: config('app.name', 'ClickERP');
    $logoUrl = isset($setting_web) && !empty($setting_web->logo_header)
        ? showFile($setting_web->logo_header)
        : null;
    $taxNumber = isset($setting_web) && !empty($setting_web->tax_number)
        ? $setting_web->tax_number
        : '-';

    $debitAccount = trim(implode(' - ', array_filter([
        $bond->debitAccount->code ?? null,
        $bond->debitAccount->name ?? null,
    ]))) ?: '-';
    $creditAccount = trim(implode(' - ', array_filter([
        $bond->creditAccount->code ?? null,
        $bond->creditAccount->name ?? null,
    ]))) ?: '-';
    $paymentMethod = getEnumValue('bond_payment_type', $bond->payment_type) ?: '-';
    $paymentReference = $bond->transfer_number ?: (optional($bond->check)->check_num ?: '-');
    $accountant = optional($bond->createdBy)->name ?: 'النظام';
    $taxAmount = (float) (optional($bond->bondTax)->tax_amount ?? 0);
    $netAmount = (float) ($bond->is_tax ? ($bond->net ?? $amount) : $amount);
    $printedAt = now($timezone)->format('Y-m-d H:i');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند صرف #{{ $bond->code ?: $bond->id }}</title>
    <style>
        :root {
            --ink: #172033;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft: #f8fafc;
            --accent: #0f766e;
            --accent-soft: #ecfdf5;
            --danger: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--ink);
            background: #eef2f6;
            font: 14px/1.65 "IBM Plex Sans Arabic", Tahoma, "Segoe UI", sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .toolbar {
            width: min(1000px, calc(100% - 24px));
            margin: 18px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .toolbar-note { color: var(--muted); }
        .toolbar-actions { display: flex; gap: 8px; }
        .button {
            border: 0;
            border-radius: 8px;
            padding: 10px 18px;
            color: #fff;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .button.secondary { color: var(--ink); background: #fff; border: 1px solid var(--line); }
        .page { width: min(1000px, calc(100% - 24px)); margin: 14px auto 24px; }
        .voucher {
            min-height: 270mm;
            padding: 18px;
            background: #fff;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 13px 15px;
            border: 1px solid var(--line);
            border-top: 5px solid var(--accent);
            border-radius: 9px;
        }
        .brand { display: flex; align-items: center; gap: 13px; min-width: 0; }
        .brand img { width: 68px; height: 68px; object-fit: contain; }
        .brand h3, .brand h4, .brand p { margin: 0; }
        .brand h3 { font-size: 19px; }
        .brand h4 { margin-top: 2px; font-size: 14px; font-weight: 600; }
        .brand p { margin-top: 5px; color: var(--muted); font-size: 12px; }
        .document-type {
            min-width: 190px;
            padding: 9px 16px;
            text-align: center;
            border-radius: 8px;
            background: var(--accent-soft);
            border: 1px solid #a7f3d0;
        }
        .document-type strong { display: block; color: var(--accent); font-size: 20px; }
        .document-type span { color: var(--muted); font-size: 12px; }
        .title { margin: 16px 0 13px; text-align: center; }
        .title h1 { margin: 0; font-size: 30px; }
        .title p { margin: 0; color: var(--muted); font-size: 16px; }
        .top-grid { display: grid; grid-template-columns: .8fr 1.4fr 1fr; gap: 10px; }
        .top-box { padding: 12px; border: 1px solid var(--line); border-radius: 8px; text-align: center; }
        .top-box .label { color: var(--muted); font-weight: 700; }
        .voucher-number { color: var(--danger); font-size: 32px; font-weight: 800; }
        .amount-box { background: var(--accent-soft); }
        .amount-number { color: var(--accent); font-size: 27px; font-weight: 800; direction: ltr; }
        .amount-words { margin-top: 5px; font-size: 12px; }
        .date-box { text-align: right; line-height: 2; }
        .date-box strong { direction: ltr; display: inline-block; }
        .status {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 700;
            color: {{ $bond->is_confirm ? '#166534' : '#92400e' }};
            background: {{ $bond->is_confirm ? '#dcfce7' : '#fef3c7' }};
        }
        .section-title {
            margin: 15px 0 7px;
            padding: 6px 10px;
            text-align: center;
            font-weight: 800;
            background: #f1f5f9;
            border: 1px solid var(--line);
        }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 9px 11px; border: 1px solid #dbe2ea; vertical-align: top; }
        td.label { width: 18%; background: var(--soft); color: #334155; font-weight: 700; }
        td.value { width: 32%; }
        .statement {
            min-height: 74px;
            padding: 11px 13px;
            border: 1px solid #dbe2ea;
            background: #fdfdfd;
            font-size: 15px;
            word-break: break-word;
        }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .num { direction: ltr; text-align: left; white-space: nowrap; font-weight: 700; }
        .total td { background: var(--accent-soft); color: var(--accent); font-weight: 800; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 45px; }
        .signature { padding-top: 12px; text-align: center; border-top: 1px dashed #94a3b8; }
        .signature span { display: block; color: var(--muted); }
        .signature strong { display: block; margin-top: 8px; min-height: 24px; }
        .footer {
            margin-top: 25px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            color: var(--muted);
            font-size: 11px;
        }
        @media (max-width: 720px) {
            .toolbar, .header { align-items: stretch; flex-direction: column; }
            .toolbar-actions { width: 100%; }
            .toolbar-actions .button { flex: 1; text-align: center; }
            .top-grid, .summary-grid, .signatures { grid-template-columns: 1fr; }
            .voucher { min-height: auto; padding: 10px; }
            td.label, td.value { width: auto; display: block; }
        }
        @media print {
            @page { size: A4 portrait; margin: 9mm; }
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { width: 100%; margin: 0; }
            .voucher {
                min-height: 0;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
            .header, .top-box, table, .statement, .signature { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-note">راجع السند أولًا، ثم اختر الطباعة أو الحفظ بصيغة PDF.</div>
        <div class="toolbar-actions">
            <a href="{{ route('finance.bonds-sarf.index') }}" class="button secondary">العودة إلى سندات الصرف</a>
            <button type="button" class="button" onclick="window.print()">طباعة / حفظ PDF</button>
        </div>
    </div>

    <div class="page">
        <article class="voucher">
            <header class="header">
                <div class="brand">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="شعار المنشأة">
                    @endif
                    <div>
                        <h3>{{ $companyNameAr }}</h3>
                        <h4>{{ $companyNameEn }}</h4>
                        <p>الرقم الضريبي / VAT: {{ $taxNumber }}</p>
                    </div>
                </div>
                <div class="document-type">
                    <span>نوع المستند</span>
                    <strong>سند صرف</strong>
                    <span>Payment Voucher</span>
                </div>
            </header>

            <div class="title">
                <h1>سند صرف</h1>
                <p>Payment Voucher</p>
            </div>

            <div class="top-grid">
                <div class="top-box">
                    <div class="label">رقم السند</div>
                    <div class="voucher-number">{{ $bond->code ?: $bond->id }}</div>
                    <small>رقم النظام: {{ $bond->id }}</small>
                </div>
                <div class="top-box amount-box">
                    <div class="label">إجمالي المبلغ المصروف</div>
                    <div class="amount-number">{{ number_format($amount, 2) }} ر.س</div>
                    <div class="amount-words">{{ $amountInWords }}</div>
                </div>
                <div class="top-box date-box">
                    <div>تاريخ السند: <strong>{{ $bond->date }}</strong></div>
                    <div>رقم الدفتر: <strong>{{ filled(trim((string) $bond->bond_book_num)) ? $bond->bond_book_num : '-' }}</strong></div>
                    <div>الحالة: <span class="status">{{ $bond->is_confirm ? 'معتمد' : 'بانتظار الاعتماد' }}</span></div>
                </div>
            </div>

            <div class="section-title">بيانات الصرف والتوجيه المحاسبي</div>
            <table>
                <tr>
                    <td class="label">المستفيد / المدين</td>
                    <td class="value">{{ $debitAccount }}</td>
                    <td class="label">الصندوق / البنك</td>
                    <td class="value">{{ $creditAccount }}</td>
                </tr>
                <tr>
                    <td class="label">طريقة الدفع</td>
                    <td class="value">{{ $paymentMethod }}</td>
                    <td class="label">مرجع التحويل / الشيك</td>
                    <td class="value">{{ $paymentReference }}</td>
                </tr>
                <tr>
                    <td class="label">مركز التكلفة</td>
                    <td class="value">{{ optional($bond->costCenter)->title ?: '-' }}</td>
                    <td class="label">رقم القيد</td>
                    <td class="value">{{ $bond->constraint_id ?: 'ينشأ بعد اعتماد السند' }}</td>
                </tr>
            </table>

            <div class="section-title">البيان</div>
            <div class="statement">{{ $bond->statement ?: '-' }}</div>

            <div class="section-title">ملخص المبلغ</div>
            <div class="summary-grid">
                <table>
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
                        <td class="num">{{ number_format($netAmount, 2) }} ر.س</td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="label">أنشأ السند</td>
                        <td>{{ $accountant }}</td>
                    </tr>
                    <tr>
                        <td class="label">حالة الاعتماد</td>
                        <td>{{ $bond->is_confirm ? 'معتمد ومرحّل محاسبيًا' : 'بانتظار الاعتماد المالي' }}</td>
                    </tr>
                    <tr>
                        <td class="label">مرجع النظام</td>
                        <td>#{{ $bond->id }}</td>
                    </tr>
                </table>
            </div>

            <div class="signatures">
                <div class="signature">
                    <span>المحاسب / Accountant</span>
                    <strong>{{ $accountant }}</strong>
                </div>
                <div class="signature">
                    <span>المستلم / Receiver</span>
                    <strong>____________________</strong>
                </div>
                <div class="signature">
                    <span>الاعتماد / Approval</span>
                    <strong>____________________</strong>
                </div>
            </div>

            <footer class="footer">
                <span>طُبع في: {{ $printedAt }}</span>
                <span>سند صرف #{{ $bond->code ?: $bond->id }}</span>
            </footer>
        </article>
    </div>
</body>
</html>
