<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ documentSetting('invoice_title', 'فاتورة ضريبية مبسطة') }}</title>
    <style>
        :root {
            --border: #d1d5db;
            --muted: #6b7280;
            --text: #111827;
            --soft: #f3f4f6;
            --accent: #0f172a;
            --teal: #0f7a87;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            direction: rtl;
            color: var(--text);
            line-height: 1.5;
            font-size: 14px;
            font-family: "Tahoma", "Segoe UI", "Arial", sans-serif;
            background: #f8fafc;
        }

        .page {
            width: min(1080px, 100%);
            margin: 20px auto;
            padding: 16px;
        }

        .tools {
            display: flex;
            gap: 8px;
            justify-content: flex-start;
            margin-bottom: 12px;
        }

        .btn {
            border: 1px solid #1f2937;
            background: #1f2937;
            color: #fff;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn.secondary {
            background: #fff;
            color: #1f2937;
        }

        .invoice {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .brand-bar {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brand-logo {
            max-width: calc(100% - 150px);
        }

        .brand-logo img {
            width: auto;
            max-width: 100%;
            max-height: 82px;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .zatca-logo {
            width: 130px;
            text-align: center;
        }

        .zatca-logo img {
            max-width: 100%;
            max-height: 72px;
            height: auto;
            object-fit: contain;
        }

        .title-row {
            text-align: center;
            margin-bottom: 12px;
        }

        .title-row h1 {
            margin: 0;
            font-size: 34px;
            color: var(--accent);
        }

        .title-row h2 {
            margin: 4px 0 0;
            font-size: 28px;
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

        .invoice-no-box {
            border: 1px solid #111827;
            width: 150px;
            text-align: center;
            margin-top: 22px;
            margin-right: auto;
            margin-left: auto;
            padding: 8px 6px;
        }

        .invoice-no-box .label {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .invoice-no-box .value {
            color: #dc2626;
            font-size: 34px;
            font-weight: 700;
            line-height: 1.1;
        }

        .qr-wrap {
            text-align: center;
        }

        .qr-wrap img {
            width: 170px;
            height: 170px;
            object-fit: contain;
            border: 1px solid var(--border);
            padding: 4px;
            background: #fff;
        }

        .supplier-meta {
            margin-top: 10px;
            font-size: 14px;
            line-height: 1.7;
        }

        .supplier-meta .tax {
            font-size: 17px;
            font-weight: 700;
            color: var(--teal);
            direction: ltr;
            text-align: left;
            display: inline-block;
        }

        .info-table,
        .items,
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
            padding: 8px 10px;
            color: #1f2937;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 12px;
            word-break: break-word;
        }

        .items th,
        .items td {
            border: 1px solid var(--border);
            padding: 8px 6px;
            text-align: center;
        }

        .items th {
            background: var(--soft);
            font-weight: 700;
            color: #1f2937;
        }

        .items td.desc {
            text-align: right;
            word-break: break-word;
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
            width: 70%;
        }

        .summary .total td {
            background: #ecfeff;
            font-weight: 700;
        }

        .num {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
            font-weight: 700;
        }

        .tax-note,
        .printed-by {
            text-align: center;
            margin-top: 12px;
            font-size: 18px;
            color: #374151;
        }

        .printed-by {
            font-size: 20px;
            font-weight: 700;
        }

        .link-box {
            margin-top: 14px;
            border: 1px solid #9ca3af;
            padding: 5px 8px;
            text-align: center;
            font-size: 15px;
            direction: ltr;
        }

        .footer-strip {
            margin-top: 14px;
            border: 1px solid var(--border);
            background: #ecfeff;
            padding: 8px;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.7;
        }

        .footer-strip .row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .footer-strip .col {
            display: table-cell;
            width: 33.33%;
            vertical-align: middle;
            text-align: center;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: #fff;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .tools {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="tools">
            <button type="button" class="btn" data-action="print">طباعة المستند</button>
            <button type="button" class="btn" data-action="save-pdf">حفظ PDF</button>
            <a class="btn secondary" href="{{ $backUrl ?? route('invoices.index') }}">رجوع</a>
        </div>

        <div class="invoice">
            <div class="brand-bar">
                <div class="brand-logo">
                    <img src="{{ $supplierLogo }}" alt="شعار الشركة">
                </div>
                <div class="zatca-logo">
                    <img src="{{ $zatcaLogo }}" alt="شعار الضريبة">
                </div>
            </div>

            <div class="title-row">
                <h1>{{ $invoiceTitle ?? 'فاتورة ضريبية مبسطة' }}</h1>
                <h2>{{ $invoiceSubtitle ?? 'Simplified Tax Invoice' }}</h2>
            </div>

            <div class="top-grid">
                <div class="cell">
                    <div class="invoice-no-box">
                        <div class="label">{{ $documentNumberLabel ?? 'رقم الفاتورة' }}</div>
                        <div class="value">{{ $documentNumber ?? $invoice->id }}</div>
                    </div>
                </div>
                <div class="cell qr-wrap">
                    <img src="{{ $qrImage }}" alt="QR">
                </div>
                <div class="cell supplier-meta">
                    <div>اسم مقدم الخدمة: <strong>{{ $supplierName }}</strong></div>
                    <div>رقم ضريبي: <span class="tax">{{ $supplierTaxNumber }}</span></div>
                </div>
            </div>

            <table class="info-table">
                <tr>
                    <td class="info-label">{{ isset($documentNumberLabel) ? 'تاريخ المستند' : 'تاريخ الفاتورة' }}</td>
                    <td class="info-value">{{ $invoiceDateTime }}</td>
                    <td class="info-label">التاريخ الموافق</td>
                    <td class="info-value">{{ $matchedHijriDate }}</td>
                </tr>
                <tr>
                    <td class="info-label">اسم العميل</td>
                    <td class="info-value">{{ $customerName }}</td>
                    <td class="info-label">رقم الهاتف</td>
                    <td class="info-value">{{ $supplierPhone }}</td>
                </tr>
                <tr>
                    <td class="info-label">{{ $customerDocumentLabel ?? 'رقم الهوية' }}</td>
                    <td class="info-value">{{ $customerNationalId }}</td>
                    <td class="info-label">العنوان</td>
                    <td class="info-value">{{ $supplierAddress }}</td>
                </tr>
                <tr>
                    <td class="info-label">رقم الجوال</td>
                    <td class="info-value">{{ $customerPhone }}</td>
                    <td class="info-label">حساب رقم</td>
                    <td class="info-value">{{ $accountNumber }}</td>
                </tr>
                <tr>
                    <td class="info-label">حالة السداد</td>
                    <td class="info-value">{{ $invoiceStatus }}</td>
                    <td class="info-label">هاتف إضافي</td>
                    <td class="info-value">{{ $supplierOtherPhone }}</td>
                </tr>
            </table>

            <p class="section-title">وصف</p>
            <div class="desc-box">{{ $serviceLongDescription }}</div>

            @if(!empty($originalInvoiceNumber))
                <table class="info-table" style="margin-bottom: 12px;">
                    <tr>
                        <td class="info-label">رقم الفاتورة الأصلية</td>
                        <td class="info-value">{{ $originalInvoiceNumber }}</td>
                        <td class="info-label">سبب إصدار الإشعار</td>
                        <td class="info-value">{{ $creditNoteReason }}</td>
                    </tr>
                </table>
            @endif

            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 28%;">بند</th>
                        <th style="width: 9%;">السعر</th>
                        <th style="width: 7%;">كمية</th>
                        <th style="width: 12%;">الإجمالي الفرعي</th>
                        <th style="width: 8%;">خصم</th>
                        <th style="width: 10%;">إجمالي</th>
                        <th style="width: 10%;">نسبة الضريبة</th>
                        <th style="width: 8%;">الضريبة</th>
                        <th style="width: 8%;">مجموع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="desc">{{ $item['label'] }}</td>
                            <td class="num">{{ number_format($item['unit'], 2) }}</td>
                            <td>{{ $item['qty'] }}</td>
                            <td class="num">{{ number_format($item['line_subtotal'], 2) }}</td>
                            <td class="num">{{ number_format($item['line_discount'], 2) }}</td>
                            <td class="num">{{ number_format($item['line_total'], 2) }}</td>
                            <td>{{ (int) ($item['vat_rate'] * 100) }}%</td>
                            <td class="num">{{ number_format($item['vat_amount'], 2) }}</td>
                            <td class="num">{{ number_format($item['line_grand_total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">لا توجد بنود متاحة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary-wrap">
                <div class="summary-left">
                    <table class="summary">
                        <tr>
                            <td class="label">الإجمالي الفرعي</td>
                            <td class="num">{{ number_format($subtotalBeforeDiscount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">خصم</td>
                            <td class="num">{{ number_format($discountTotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">الإجمالي</td>
                            <td class="num">{{ number_format($netSubtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label">الضريبة</td>
                            <td class="num">{{ number_format($vatAmount, 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td class="label">مجموع شامل الضريبة</td>
                            <td class="num">{{ number_format($grandTotal, 2) }} ريال سعودي</td>
                        </tr>
                        @if ($invoice->rental_transfer_contract_id)
                            <tr><td class="label">المدفوع</td><td class="num">{{ number_format($invoice->rental_transfer_paid_amount, 2) }}</td></tr>
                            <tr><td class="label">المتبقي</td><td class="num">{{ number_format($invoice->rental_transfer_remaining_amount, 2) }}</td></tr>
                        @endif
                    </table>
                </div>
                <div class="summary-right">
                    <table class="summary">
                        <tr>
                            <td class="label">اسم مقدم الخدمة</td>
                            <td>{{ $supplierName }}</td>
                        </tr>
                        <tr>
                            <td class="label">البريد الإلكتروني</td>
                            <td>{{ $supplierEmail }}</td>
                        </tr>
                        <tr>
                            <td class="label">وقت الطباعة</td>
                            <td><span id="printed-at">{{ optional($printedAt)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s') }}</span></td>
                        </tr>
                        <tr>
                            <td class="label">حالة السداد</td>
                            <td>{{ $invoiceStatus }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="tax-note">يشمل الضريبة ({{ $invoice->rental_transfer_contract_id && $vatAmount <= 0 ? '0' : '15' }}%): {{ number_format($vatAmount, 2) }}</p>
            <p class="printed-by">بواسطة: {{ $printedBy ?? '---' }}</p>

            <div class="link-box">{{ $invoiceUrl }}</div>

            <div class="footer-strip">
                <div class="row">
                    <div class="col">{{ $supplierAddress }}</div>
                    <div class="col">هاتف: {{ $supplierPhone }} | إضافي: {{ $supplierOtherPhone }}</div>
                    <div class="col">الرقم الضريبي: {{ $supplierTaxNumber }}</div>
                </div>
            </div>

            @if (!empty($invoiceFooterText))
                <div class="footer-strip" style="margin-top: 10px; background: #fff;">
                    {!! nl2br(e($invoiceFooterText)) !!}
                </div>
            @endif

        </div>
    </div>

    <script src="{{ asset('admin/assets/js/invoice-print.js') }}?v={{ filemtime(public_path('admin/assets/js/invoice-print.js')) }}"></script>
</body>

</html>
