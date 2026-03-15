<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>نسخة طباعة نموذج تسليم عاملة منزلية</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            background: #fff;
            direction: rtl;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.9;
        }

        .print-tools {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            margin: 12px auto;
            width: 190mm;
            flex-wrap: wrap;
        }

        .print-btn {
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #111;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-btn.secondary {
            background: #fff;
            color: #111;
        }

        .sheet {
            width: 190mm;
            min-height: 273mm;
            margin: 0 auto;
            padding: 10mm 9mm;
            border: 1px solid #d1d5db;
            background: #fff;
        }

        .title {
            margin: 0 0 18px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .field-table td,
        .signature-table td {
            padding: 8px 10px;
            vertical-align: top;
        }

        .field-table td,
        .single-row td {
            border: 1px solid #000;
        }

        .label {
            width: 20%;
            font-weight: 700;
            white-space: nowrap;
        }

        .value {
            width: 30%;
        }

        .single-label {
            width: 18%;
            font-weight: 700;
            white-space: nowrap;
        }

        .line {
            margin: 14px 0;
            border-top: 1px solid #000;
        }

        .statement {
            min-height: 170px;
            padding: 10px 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .statement p {
            margin: 0 0 8px;
        }

        .fill {
            display: inline-block;
            min-width: 120px;
            font-weight: 700;
        }

        .company-name {
            margin: 14px 0 8px;
            font-weight: 700;
        }

        .signature-table td {
            width: 50%;
        }

        .signature-line {
            display: inline-block;
            min-width: 160px;
            border-bottom: 1px solid #000;
            height: 18px;
            vertical-align: middle;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .note {
            margin-top: 12px;
            font-size: 11px;
            text-align: center;
        }

        @media print {
            .print-tools {
                display: none !important;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                border: 0;
            }
        }
    </style>
</head>

<body>
    @php
        $receiptText = trim((string) $text_receipt);
    @endphp

    <div class="print-tools">
        <button type="button" class="print-btn" onclick="window.print()">طباعة الآن</button>
        <a class="print-btn secondary" href="{{ $pdf_url }}" target="_blank" rel="noopener">تنزيل PDF</a>
        <a class="print-btn secondary" href="{{ $preview_url }}">العودة للمعاينة</a>
    </div>

    <div class="sheet">
        <h1 class="title">نموذج تسليم عاملة منزلية</h1>

        <table class="single-row">
            <tr>
                <td class="single-label">اسم العميل :</td>
                <td>{{ $client_name ?: '--' }}</td>
            </tr>
        </table>

        <table class="field-table" style="margin-top: 10px;">
            <tr>
                <td class="label">رقم السجل المدني / الإقامة :</td>
                <td class="value ltr">{{ $national_identification_number ?: '--' }}</td>
                <td class="label">الهاتف / الجوال: المنزل :</td>
                <td class="value ltr">{{ $client_phone ?: '--' }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <table class="field-table">
            <tr>
                <td class="label">رقم جواز السفر / الإقامة :</td>
                <td class="value ltr">{{ $passport_id ?: '--' }}</td>
                <td class="label">الجنسية :</td>
                <td class="value">{{ $nationality_id ?: '--' }}</td>
            </tr>
        </table>

        <div class="statement">
            <p>
                أقر أنا المستلم :
                <span class="fill">{{ $client_name ?: '--' }}</span>
                بطاقة رقم الهوية :
                <span class="fill ltr">{{ $national_identification_number ?: '--' }}</span>
            </p>
            <p>
                قد استلمت العاملة المنزلية الموضح بياناتها أعلاه وذلك يوم :
                <span class="fill">{{ $receipt_day_name ?: '--' }}</span>
                بتاريخ :
                <span class="fill ltr">{{ $date_receipt ?: '--' }}</span>
                م
            </p>
            <p>
                وبهذا قد أصبح شركة /
                <span class="fill">{{ $delivery_company_name ?: 'بلاتنيوم الشرقية للاستقدام' }}</span>
                خالي من المسؤولية منذ استلامي للخادمة .
            </p>
            @if ($receiptText !== '')
                <p>{!! nl2br(e($receiptText)) !!}</p>
            @endif
        </div>

        <p class="company-name">{{ $delivery_office_name ?: 'مكتب بلاتنيوم الشرقية للاستقدام' }}</p>

        <table class="signature-table">
            <tr>
                <td>
                    اسم الموظفة :
                    <span class="signature-line">{{ $company_representative ?: '' }}</span>
                </td>
                <td>
                    أسم المستلم :
                    <span class="signature-line">{{ $client_name ?: '' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    التاريخ :
                    <span class="signature-line ltr">{{ $date_receipt ?: '' }}</span>
                </td>
                <td>
                    العنوان :
                    <span class="signature-line">{{ $client_address ?: '' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    التوقيع :
                    <span class="signature-line"></span>
                </td>
                <td>
                    التوقيع :
                    <span class="signature-line"></span>
                </td>
            </tr>
        </table>

        <div class="note">إذا لم تظهر نافذة الطباعة تلقائياً استخدم زر "طباعة الآن" أعلى الصفحة.</div>
    </div>

    <script>
        (function() {
            function shouldAutoPrint() {
                try {
                    return new URLSearchParams(window.location.search).get('autoprint') === '1';
                } catch (e) {
                    return false;
                }
            }

            window.addEventListener('load', function() {
                if (shouldAutoPrint()) {
                    setTimeout(function() {
                        if (typeof window.print === 'function') {
                            window.print();
                        }
                    }, 350);
                }
            });
        })();
    </script>
</body>

</html>
