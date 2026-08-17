<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @php
        $documentBranding = documentBranding();
        $documentTitle = documentSetting('delivery_form_title', 'نموذج تسليم عاملة منزلية');
        $deliveryCompanyName = $documentBranding['company_name'];
        $deliveryOfficeName = $documentBranding['office_name'];
        $defaultReceiptText = trim((string) replaceDocumentPlaceholders(documentSetting('delivery_form_default_text', '')));
        $receiptText = trim((string) ($text_receipt ?: $defaultReceiptText));
        $documentFooterNote = $document_footer_note ?? $documentBranding['footer_note'];
    @endphp
    <title>{{ $documentTitle }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px 30px;
            direction: rtl;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 13px;
            line-height: 1.9;
            color: #000;
        }

        .title {
            margin: 0 0 18px;
            text-align: center;
            font-size: 20px;
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

        .field-table td {
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

        .single-row td {
            border: 1px solid #000;
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

        .signature-table {
            margin-top: 10px;
        }

        .signature-table td {
            width: 50%;
        }

        .signature-line {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #000;
            height: 16px;
            vertical-align: middle;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .document-note {
            margin-top: 12px;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>

<body>
    <h1 class="title">{{ $documentTitle }}</h1>

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
            <span class="fill">{{ $deliveryCompanyName }}</span>
            خالي من المسؤولية منذ استلامي للخادمة .
        </p>
        @if ($receiptText !== '')
            <p>{!! nl2br(e($receiptText)) !!}</p>
        @endif
    </div>

    <p class="company-name">{{ $deliveryOfficeName }}</p>

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

    @if ($documentFooterNote)
        <p class="document-note">{{ $documentFooterNote }}</p>
    @endif
</body>

</html>
