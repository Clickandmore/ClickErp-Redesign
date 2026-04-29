<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>فاتورة العقد</title>
    <style>
        @page {
            margin: 20px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            direction: rtl;
            unicode-bidi: embed;
            color: #111827;
            line-height: 1.6;
            font-size: 12px;
            font-family: "DejaVu Sans", sans-serif;
        }

        .invoice-wrap {
            width: 100%;
        }

        .title-row {
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 14px;
            padding-bottom: 10px;
        }

        .title {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            margin: 0;
            color: #4b5563;
            font-size: 12px;
        }

        .section-title {
            margin: 14px 0 8px 0;
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
        }

        .info-table,
        .items-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
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

        .items-table th,
        .items-table td {
            border: 1px solid #d1d5db;
            padding: 7px 6px;
        }

        .items-table th {
            background: #f3f4f6;
            font-weight: 700;
            text-align: center;
            color: #1f2937;
        }

        .items-table td {
            text-align: center;
        }

        .items-table td.desc {
            text-align: right;
        }

        .summary-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
        }

        .summary-label {
            background: #f9fafb;
            font-weight: 700;
            width: 75%;
        }

        .total-row td {
            background: #eef2ff;
            font-weight: 700;
        }

        .num {
            direction: ltr;
            text-align: left;
            white-space: nowrap;
        }

        .note {
            margin-top: 10px;
            color: #6b7280;
            font-size: 11px;
        }

        .credit-note {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d1d5db;
            color: #374151;
            font-size: 11px;
            text-align: center;
            line-height: 1.7;
        }

        .credit-note img {
            width: 16px;
            height: 16px;
            object-fit: contain;
            vertical-align: middle;
            margin-left: 5px;
        }

        .credit-note a {
            color: #0f7a87;
            text-decoration: none;
            font-weight: 700;
            direction: ltr;
            unicode-bidi: embed;
        }
    </style>
</head>

<body>
    @php
        $clickUrl = 'https://clickerp.com.sa';
        $clickLogoExists = file_exists(public_path('admin/assets/img/fav.png'));
    @endphp

    <div class="invoice-wrap">
        <div class="title-row">
            <p class="title">{{ $invoiceTitle ?? 'فاتورة ضريبية مبسطة' }}</p>
            <p class="subtitle">
                {{ $invoiceSubtitle ?? ('رقم الفاتورة: ' . $invoice->id . ' | ' . ($invoice->contract_id ? 'رقم العقد: ' . $invoice->contract_id : 'رقم التفويض: ' . $invoice->ingaz_delegation_id)) }}
            </p>
        </div>

        <table class="info-table">
            <tr>
                <td class="info-label">تاريخ الفاتورة</td>
                <td class="info-value">{{ $invoiceDateTime }}</td>
                <td class="info-label">التاريخ الموافق</td>
                <td class="info-value">{{ $matchedHijriDate }}</td>
            </tr>
            <tr>
                <td class="info-label">اسم العميل</td>
                <td class="info-value">{{ $customerName }}</td>
                <td class="info-label">رقم الهوية</td>
                <td class="info-value">{{ $customerNationalId }}</td>
            </tr>
            <tr>
                <td class="info-label">رقم الجوال</td>
                <td class="info-value">{{ $customerPhone }}</td>
                <td class="info-label">اسم مقدم الخدمة</td>
                <td class="info-value">{{ $supplierName }}</td>
            </tr>
            <tr>
                <td class="info-label">عنوان مقدم الخدمة</td>
                <td class="info-value">{{ $supplierAddress }}</td>
                <td class="info-label">الرقم الضريبي</td>
                <td class="info-value">{{ $supplierTaxNumber }}</td>
            </tr>
            <tr>
                <td class="info-label">حالة الفاتورة</td>
                <td class="info-value">{{ $invoiceStatus }}</td>
                <td class="info-label">حساب رقم</td>
                <td class="info-value">{{ $accountNumber }}</td>
            </tr>
        </table>

        <p class="section-title">وصف</p>
        <table class="info-table">
            <tr>
                <td>{{ $serviceLongDescription }}</td>
            </tr>
        </table>

        <p class="section-title">تفاصيل البنود</p>
        <table class="items-table">
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

        <p class="section-title">ملخص الفاتورة</p>
        <table class="summary-table">
            <tr>
                <td class="summary-label">الإجمالي الفرعي</td>
                <td class="num">{{ number_format($subtotalBeforeDiscount, 2) }} ريال</td>
            </tr>
            <tr>
                <td class="summary-label">خصم</td>
                <td class="num">{{ number_format($discountTotal, 2) }} ريال</td>
            </tr>
            <tr>
                <td class="summary-label">الإجمالي</td>
                <td class="num">{{ number_format($netSubtotal, 2) }} ريال</td>
            </tr>
            <tr>
                <td class="summary-label">الضريبة (%15)</td>
                <td class="num">{{ number_format($vatAmount, 2) }} ريال</td>
            </tr>
            <tr class="total-row">
                <td class="summary-label">المجموع شامل الضريبة</td>
                <td class="num">{{ number_format($grandTotal, 2) }} ريال</td>
            </tr>
        </table>

        <p class="note">تمت الطباعة بواسطة: {{ $printedBy ?? '---' }} | وقت الطباعة: {{ optional($printedAt)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s') }}</p>
        @if (!empty($invoiceFooterText))
            <p class="note">{!! nl2br(e($invoiceFooterText)) !!}</p>
        @endif
        <p class="note">رابط الفاتورة: {{ $invoiceUrl }}</p>
        <p class="credit-note">
            @if ($clickLogoExists)
                <img src="{{ asset('admin/assets/img/fav.png') }}" alt="Click Logo">
            @endif
            تم تصميم وتطوير هذه الفاتورة بواسطة
            <a href="{{ $clickUrl }}">نظام كليك لإدارة مكاتب وشركات الاستقدام - clickerp.com.sa</a>
        </p>
    </div>
</body>

</html>
