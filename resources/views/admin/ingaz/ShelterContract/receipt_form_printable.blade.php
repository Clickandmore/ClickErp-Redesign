<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>نسخة طباعة نموذج استلام عاملة وتعهد</title>
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
            font-family: Tahoma, "Segoe UI", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.7;
        }

        .print-tools {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
            margin: 12px auto;
            width: 190mm;
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

        .header {
            text-align: center;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 2px solid #000;
        }

        .header img {
            max-width: 130px;
            max-height: 65px;
            margin-bottom: 8px;
        }

        .company-name {
            margin: 0 0 4px;
            font-size: 17px;
            font-weight: 700;
        }

        .title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .subtitle {
            margin: 6px 0 0;
            font-size: 12px;
        }

        .section-title {
            margin: 14px 0 10px;
            padding: 6px 10px;
            border: 1px solid #000;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td,
        .meta-table td,
        .intro-grid td {
            border: 1px solid #000;
            padding: 8px 10px;
            vertical-align: top;
        }

        .info-label,
        .intro-label,
        .meta-label {
            width: 20%;
            font-weight: 700;
            white-space: nowrap;
        }

        .field-value {
            unicode-bidi: plaintext;
            word-break: break-word;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .body-text {
            margin-top: 12px;
            text-align: justify;
        }

        .body-text p {
            margin: 0 0 10px;
        }

        .signatures {
            margin-top: 28px;
        }

        .signatures td {
            width: 50%;
            padding-top: 38px;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 10px;
        }

        .footer-meta {
            margin-top: 18px;
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
        $logoPath = public_path('admin/assets/img/company_logo.png');
        $defaultReceiptText = 'كما أتعهد في حالة نقل خدمات العاملة إلى صاحب عمل آخر بإخطار مكتب / شركة بلاتنيوم الشرقية للاستقدام وتزويده كتابياً بكافة بيانات صاحب العمل الجديد من اسم وهوية وعنوان ورقم جوال. وفي حال عدم القيام بذلك أتحمل كامل المسؤولية عن أي مطالبات أو التزامات تخص العاملة.';
        $defaultSettlementText = 'كما أتعهد بتسليم العاملة كافة مستحقاتها وإثبات ذلك بمخالصة رسمية تفيد باستلامها جميع رواتبها ومستحقاتها وعدم مطالبتها بأي حق، وذلك وفق النموذج المعتمد لدى الجهات الرسمية.';
        $receiptText = trim((string) $text_receipt);
    @endphp

    <div class="print-tools">
        <button type="button" class="print-btn" onclick="window.print()">طباعة الآن</button>
        <button type="button" class="print-btn secondary" onclick="window.print()">حفظ PDF</button>
        <a class="print-btn secondary" href="{{ route('shelter_contract_form.download', $contract->id) }}">العودة للمعاينة</a>
    </div>

    <div class="sheet">
        <div class="header">
            @if (file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="شركة بلاتينيوم">
            @endif
            <p class="company-name">مكتب / شركة بلاتنيوم الشرقية للاستقدام</p>
            <h1 class="title">استلام عاملة وتعهد</h1>
            <p class="subtitle">نسخة مخصصة للطباعة والحفظ</p>
        </div>

        <div class="section-title">بيانات العميل والعاملة</div>
        <table class="info-table">
            <tr>
                <td class="info-label">اسم العميل</td>
                <td><span class="field-value" dir="auto">{{ $client_name ?: '--' }}</span></td>
                <td class="info-label">رقم الهوية</td>
                <td><span class="field-value ltr">{{ $national_identification_number ?: '--' }}</span></td>
            </tr>
            <tr>
                <td class="info-label">اسم العاملة</td>
                <td><span class="field-value" dir="auto">{{ $cvs_name ?: '--' }}</span></td>
                <td class="info-label">رقم الجواز</td>
                <td><span class="field-value ltr">{{ $passport_id ?: '--' }}</span></td>
            </tr>
            <tr>
                <td class="info-label">الجنسية</td>
                <td><span class="field-value" dir="auto">{{ $nationality_id ?: '--' }}</span></td>
                <td class="info-label">تاريخ الوصول</td>
                <td><span class="field-value ltr">{{ $arrive_date ?: '--' }}</span></td>
            </tr>
            <tr>
                <td class="info-label">تاريخ الاستلام</td>
                <td><span class="field-value ltr">{{ $date_receipt ?: '--' }}</span></td>
                <td class="info-label">صفة المستلم</td>
                <td><span class="field-value" dir="auto">صاحب العمل / من ينوب عنه</span></td>
            </tr>
        </table>

        <div class="section-title">نص العقد / الإقرار</div>
        <table class="intro-grid">
            <tr>
                <td class="intro-label">أقر أنا</td>
                <td><strong><span class="field-value" dir="auto">{{ $client_name ?: '....................' }}</span></strong></td>
            </tr>
            <tr>
                <td class="intro-label">هوية رقم</td>
                <td><strong><span class="field-value ltr">{{ $national_identification_number ?: '....................' }}</span></strong></td>
            </tr>
            <tr>
                <td class="intro-label">بأنني قد استلمت العاملة</td>
                <td><strong><span class="field-value" dir="auto">{{ $cvs_name ?: '....................' }}</span></strong></td>
            </tr>
            <tr>
                <td class="intro-label">من جهة</td>
                <td><strong>مكتب / شركة بلاتنيوم الشرقية للاستقدام</strong></td>
            </tr>
            <tr>
                <td class="intro-label">بتاريخ</td>
                <td><strong><span class="field-value ltr">{{ $date_receipt ?: '--' }}</span></strong></td>
            </tr>
        </table>

        <div class="body-text">
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
                    <div class="signature-line"></div>
                    <div><strong>اسم المستلم</strong></div>
                    <div class="field-value" dir="auto">{{ $client_name ?: '--' }}</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div><strong>التوقيع</strong></div>
                    <div>................................</div>
                </td>
            </tr>
        </table>

        <div class="section-title">بيانات التوثيق والطباعة</div>
        <table class="meta-table footer-meta">
            <tr>
                <td class="meta-label">الجهة المتعاقدة</td>
                <td><span class="field-value" dir="auto">مكتب / شركة بلاتنيوم الشرقية للاستقدام</span></td>
                <td class="meta-label">تاريخ الاستلام / العقد</td>
                <td><span class="field-value ltr">{{ $date_receipt ?: '--' }}</span></td>
            </tr>
            <tr>
                <td class="meta-label">وقت توقيع / تسجيل العقد</td>
                <td><span class="field-value ltr">{{ $contract_signed_at ?? '--' }}</span></td>
                <td class="meta-label">تمت الطباعة بواسطة</td>
                <td><span class="field-value" dir="auto">{{ $printed_by ?? 'النظام' }}</span></td>
            </tr>
            <tr>
                <td class="meta-label">وقت طباعة النموذج</td>
                <td colspan="3"><span id="printed-at" class="field-value ltr">{{ $printed_at ?? '--' }}</span></td>
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

            function updatePrintedAt() {
                var printedAt = document.getElementById('printed-at');
                if (!printedAt) {
                    return;
                }

                var now = new Date();
                var pad = function(number) {
                    return number < 10 ? '0' + number : String(number);
                };

                printedAt.textContent =
                    now.getFullYear() + '/' +
                    pad(now.getMonth() + 1) + '/' +
                    pad(now.getDate()) + ' ' +
                    pad(now.getHours()) + ':' +
                    pad(now.getMinutes()) + ':' +
                    pad(now.getSeconds());
            }

            window.addEventListener('load', function() {
                updatePrintedAt();

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
