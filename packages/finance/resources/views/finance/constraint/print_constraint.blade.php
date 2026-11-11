<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bladeTitle }}</title>

    @php
        $companyNameAr = documentSetting(
            'document_company_name',
            documentSetting('website_name', optional($setting_web ?? null)->website_name ?: config('app.name', 'Click ERP'), 'ar'),
            'ar',
        );
        $companyNameEn = documentSetting(
            'document_company_name',
            documentSetting('website_name', optional($setting_web ?? null)->website_name ?: config('app.name', 'Click ERP'), 'en'),
            'en',
        );
        $logoPath = documentSetting('document_logo') ?: optional($setting_web ?? null)->logo_header;
        $logoUrl = $logoPath ? showFile($logoPath) : null;
        $taxNumber = optional($setting_web ?? null)->tax_number;
        $commercialRegister = optional($setting_web ?? null)->commercial_register;
        $companyAddress = documentSetting('location', '');
        $companyPhone = documentSetting('phone', '');
        $entryCode = $obj->code ?: $obj->id;
        $printedAt = now(config('app.timezone', 'Asia/Riyadh'))->format('Y-m-d H:i');
        $printedBy = optional(auth('admin')->user())->name;
    @endphp

    <style>
        :root {
            color-scheme: light;
            --ink: #172033;
            --muted: #667085;
            --line: #cfd5df;
            --soft: #f4f7f9;
            --brand: #0f7a87;
            --brand-dark: #095763;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: #edf1f5;
            color: var(--ink);
            font-family: Tahoma, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.55;
        }

        .print-toolbar {
            width: 210mm;
            max-width: calc(100% - 24px);
            margin: 18px auto 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .print-toolbar__hint {
            color: #475467;
        }

        .print-toolbar__actions {
            display: flex;
            gap: 8px;
        }

        .print-button {
            appearance: none;
            border: 1px solid var(--brand);
            border-radius: 8px;
            padding: 9px 16px;
            background: var(--brand);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
        }

        .print-button--secondary {
            background: #fff;
            color: var(--ink);
            border-color: #98a2b3;
        }

        .journal-sheet {
            position: relative;
            width: 210mm;
            min-height: 277mm;
            margin: 12px auto 24px;
            padding: 12mm 12mm 23mm;
            background: #fff;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
        }

        .document-header {
            display: grid;
            grid-template-columns: 1fr 34mm 1fr;
            align-items: center;
            gap: 7mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid var(--brand);
        }

        .company-name {
            margin: 0;
            color: var(--brand-dark);
            font-size: 18px;
            font-weight: 800;
        }

        .company-name--en {
            margin-top: 2px;
            font-size: 12px;
            direction: ltr;
        }

        .company-meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 10px;
        }

        .company-logo {
            width: 30mm;
            height: 24mm;
            object-fit: contain;
            justify-self: center;
        }

        .document-identity {
            text-align: end;
        }

        .document-identity__label {
            color: var(--muted);
            font-size: 10px;
        }

        .document-identity__code {
            margin: 2px 0;
            color: var(--brand-dark);
            font-size: 19px;
            font-weight: 800;
            direction: ltr;
        }

        .document-title {
            margin: 6mm 0 4mm;
            text-align: center;
        }

        .document-title h1 {
            margin: 0;
            color: var(--ink);
            font-size: 20px;
        }

        .document-title p {
            margin: 1px 0 0;
            color: var(--muted);
            font-size: 11px;
            direction: ltr;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 5mm;
        }

        .summary__item {
            min-height: 18mm;
            padding: 3mm 4mm;
            border-inline-end: 1px solid var(--line);
        }

        .summary__item:last-child {
            border-inline-end: 0;
        }

        .summary__label {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 10px;
        }

        .summary__value {
            display: block;
            font-size: 13px;
            font-weight: 700;
        }

        .statement-box {
            margin-bottom: 5mm;
            padding: 3mm 4mm;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--soft);
        }

        .statement-box__label {
            display: block;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 10px;
        }

        .journal-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .journal-table th,
        .journal-table td {
            border: 1px solid var(--line);
            padding: 2.6mm 2.2mm;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .journal-table thead {
            display: table-header-group;
        }

        .journal-table th {
            background: #eaf3f4;
            color: var(--brand-dark);
            font-size: 10px;
            font-weight: 800;
            text-align: center;
        }

        .journal-table tbody tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .journal-table .amount {
            text-align: center;
            direction: ltr;
            font-weight: 700;
            white-space: nowrap;
        }

        .journal-table .center {
            text-align: center;
        }

        .journal-table tfoot td {
            background: var(--soft);
            font-weight: 800;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8mm;
            margin-top: 13mm;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .signature {
            min-height: 18mm;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        .signature::after {
            content: "";
            display: block;
            margin-top: 12mm;
            border-bottom: 1px dotted #7d8795;
        }

        .attachments {
            break-before: page;
            page-break-before: always;
            padding-top: 3mm;
        }

        .attachments h2 {
            margin: 0 0 5mm;
            font-size: 16px;
            color: var(--brand-dark);
        }

        .attachment-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6mm;
        }

        .attachment {
            min-height: 45mm;
            padding: 3mm;
            border: 1px solid var(--line);
            border-radius: 6px;
            break-inside: avoid;
            page-break-inside: avoid;
            text-align: center;
        }

        .attachment img {
            display: block;
            width: 100%;
            max-height: 105mm;
            object-fit: contain;
        }

        .attachment-file {
            display: flex;
            min-height: 38mm;
            align-items: center;
            justify-content: center;
            color: var(--ink);
            font-weight: 700;
            text-decoration: none;
        }

        .document-footer {
            position: absolute;
            inset-inline: 12mm;
            bottom: 8mm;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 5mm;
            align-items: end;
            padding-top: 3mm;
            border-top: 1px solid var(--brand);
            color: var(--muted);
            font-size: 9px;
        }

        .document-footer__center {
            color: var(--brand-dark);
            font-weight: 700;
            text-align: center;
        }

        .document-footer__end {
            text-align: end;
        }

        @media (max-width: 820px) {
            .journal-sheet {
                width: calc(100% - 20px);
                min-height: auto;
                padding: 18px 18px 75px;
            }

            .print-toolbar {
                width: calc(100% - 20px);
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html,
            body {
                width: 210mm;
                min-height: 297mm;
                background: #fff !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .journal-sheet {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 10mm 12mm 22mm;
                box-shadow: none;
            }

            .document-footer {
                position: fixed;
            }

            .attachments {
                padding-bottom: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <div class="print-toolbar__hint">يمكنك الطباعة مباشرة أو اختيار «حفظ كملف PDF» من نافذة الطباعة.</div>
        <div class="print-toolbar__actions">
            <a href="{{ route('finance.add-constraint.show', $obj->id) }}" class="print-button print-button--secondary">عودة</a>
            <button type="button" class="print-button" onclick="window.print()">طباعة / حفظ PDF</button>
        </div>
    </div>

    <main class="journal-sheet">
        <header class="document-header">
            <div>
                <h2 class="company-name">{{ $companyNameAr }}</h2>
                @if ($companyNameEn && $companyNameEn !== $companyNameAr)
                    <div class="company-name company-name--en">{{ $companyNameEn }}</div>
                @endif
                @if ($companyAddress || $companyPhone)
                    <div class="company-meta">
                        {{ collect([$companyAddress, $companyPhone])->filter()->implode(' | ') }}
                    </div>
                @endif
            </div>

            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="شعار المنشأة" class="company-logo">
            @else
                <div></div>
            @endif

            <div class="document-identity">
                <div class="document-identity__label">رقم القيد / Entry No.</div>
                <div class="document-identity__code">{{ $entryCode }}</div>
                <div class="document-identity__label">التاريخ / Date: <b dir="ltr">{{ $obj->date }}</b></div>
            </div>
        </header>

        <section class="document-title">
            <h1>سند قيد يومية</h1>
            <p>JOURNAL VOUCHER</p>
        </section>

        <section class="summary">
            <div class="summary__item">
                <span class="summary__label">رقم القيد</span>
                <span class="summary__value" dir="ltr">{{ $entryCode }}</span>
            </div>
            <div class="summary__item">
                <span class="summary__label">تاريخ القيد</span>
                <span class="summary__value" dir="ltr">{{ $obj->date }}</span>
            </div>
            <div class="summary__item">
                <span class="summary__label">إجمالي القيد</span>
                <span class="summary__value">{{ number_format((float) $obj->value, 2, '.', ',') }} ريال</span>
            </div>
        </section>

        <section class="statement-box">
            <span class="statement-box__label">البيان العام للقيد</span>
            <strong>{{ $obj->statement ?: '-' }}</strong>
        </section>

        <table class="journal-table">
            <colgroup>
                <col style="width: 8%">
                <col style="width: 25%">
                <col style="width: 13%">
                <col style="width: 13%">
                <col style="width: 17%">
                <col style="width: 24%">
            </colgroup>
            <thead>
                <tr>
                    <th>م</th>
                    <th>الحساب</th>
                    <th>مدين</th>
                    <th>دائن</th>
                    <th>مركز التكلفة</th>
                    <th>البيان</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($obj->constraintDetails as $detail)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>
                            {{ optional($detail->account)->name ?: '-' }}
                            @if ($obj->is_multi_branch && $detail->branch)
                                <br><small>الفرع: {{ $detail->branch->name_ar ?: $detail->branch->name_en }}</small>
                            @endif
                        </td>
                        <td class="amount">
                            {{ (float) $detail->debtor_value > 0 ? number_format((float) $detail->debtor_value, 2, '.', ',') : '-' }}
                        </td>
                        <td class="amount">
                            {{ (float) $detail->creditor_value > 0 ? number_format((float) $detail->creditor_value, 2, '.', ',') : '-' }}
                        </td>
                        <td>{{ optional($detail->costCenter)->title ?: '-' }}</td>
                        <td>{{ $detail->statement ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="center">الإجمالي</td>
                    <td class="amount">{{ number_format((float) $obj->value, 2, '.', ',') }}</td>
                    <td class="amount">{{ number_format((float) $obj->value, 2, '.', ',') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <section class="signatures">
            <div class="signature">إعداد المحاسب</div>
            <div class="signature">المراجعة</div>
            <div class="signature">الاعتماد</div>
        </section>

        @if ($obj->files->count() > 0)
            <section class="attachments">
                <h2>مرفقات القيد</h2>
                <div class="attachment-grid">
                    @foreach ($obj->files as $file)
                        @php
                            $extension = strtolower(pathinfo($file->file, PATHINFO_EXTENSION));
                            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                        @endphp
                        <div class="attachment">
                            @if ($isImage)
                                <img src="{{ showFile($file->file) }}" alt="مرفق القيد">
                            @else
                                <a href="{{ showFile($file->file) }}" class="attachment-file">
                                    ملف مرفق ({{ strtoupper($extension ?: 'FILE') }})
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <footer class="document-footer">
            <div>
                @if ($taxNumber)
                    الرقم الضريبي: {{ $taxNumber }}
                @endif
                @if ($commercialRegister)
                    <br>السجل التجاري: {{ $commercialRegister }}
                @endif
            </div>
            <div class="document-footer__center">{{ $companyNameAr }} — سند قيد رقم {{ $entryCode }}</div>
            <div class="document-footer__end">
                طُبع في: <span dir="ltr">{{ $printedAt }}</span>
                @if ($printedBy)
                    <br>بواسطة: {{ $printedBy }}
                @endif
            </div>
        </footer>
    </main>

    <script>
        window.addEventListener('load', function () {
            var images = Array.from(document.images);
            var waiting = images
                .filter(function (image) { return !image.complete; })
                .map(function (image) {
                    return new Promise(function (resolve) {
                        image.addEventListener('load', resolve, { once: true });
                        image.addEventListener('error', resolve, { once: true });
                    });
                });

            Promise.all(waiting).then(function () {
                window.setTimeout(function () {
                    window.print();
                }, 250);
            });
        });
    </script>
</body>
</html>
