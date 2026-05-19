<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $documentTitle = documentSetting('rental_contract_title', 'نموذج العقد الموحد الإلكتروني لتقديم خدمات إعارة العمالة المنزلية');
        $documentCompanyName = documentSetting('document_company_name', $contractSettings['company_name'] ?? documentSetting('website_name', config('app.name')));
        $documentLogo = filled($contractSettings['logo'] ?? null)
            ? asset('storage/' . ltrim($contractSettings['logo'], '/'))
            : documentLogoUrl(asset('site/assets/images/logo.svg'));
        $ministryLogo = asset('site/assets/images/Ministry-of-Labor-and-Social-Development.svg');
        $documentAddress = $contractSettings['address'] ?? documentSetting('location', 'الرياض');
        $documentPhone = $contractSettings['phone'] ?? documentSetting('phone', '');
        $documentMobile = $contractSettings['mobile'] ?? documentSetting('other_phone', '');
        $documentEmail = $contractSettings['email'] ?? documentSetting('email', '');
        $partyOneTitle = (string) documentSetting('rental_contract_party_one_title', 'بيانات الطرف الأول');
        $partyTwoTitle = (string) documentSetting('rental_contract_party_two_title', 'بيانات الطرف الثاني');
        $serviceTitle = (string) documentSetting('rental_contract_service_title', '1. معلومات الخدمة المطلوبة:');
        $sadadLabel = (string) documentSetting('rental_contract_sadad_label', 'رقم سداد');
        $sadadCode = (string) documentSetting('rental_contract_sadad_code', 'AQR');
        $partyOneTextTemplate = (string) documentSetting(
            'rental_contract_party_one_text',
            '[اسم_الشركة]، سجل تجاري رقم: [رقم_السجل]، صادر بتاريخ: [تاريخ_السجل]، العنوان: [عنوان_الشركة]، هاتف رقم: [هاتف_الشركة]، بريد إلكتروني: [بريد_الشركة]، ترخيص استقدام رقم [رقم_الترخيص]، مركز الاتصال [جوال_الشركة].'
        );
        $rentalIntroText = replaceDocumentPlaceholders((string) documentSetting(
            'rental_contract_intro_text',
            'لما كان الطرف الأول مرخص له في مجال استقدام عامل/عاملة الخدمة المنزلية وتقديم الخدمة المنزلية لدى صاحب العمل داخل المملكة العربية السعودية وفقاً لما هو مقرر نظاماً، وحيث إن الرغبة قد توافرت لدى الطرف الثاني في الانتفاع من خدمات العمالة التي يقدمها الطرف الأول وفقاً لما يتم الاتفاق عليه بينهما بموجب هذا العقد وطبقاً للأنظمة واللوائح الصادرة في المملكة والجهات المنظمة لعمل شركات ومكاتب الاستقدام، لذا فقد اتفق الطرفان وهما بكامل أهليتهما اللازمة لإبرام العقود والتصرفات شرعاً ونظاماً على ما يلي:'
        ));
        $formatRentalDate = function ($value, $fallback = '______') {
            if (blank($value)) {
                return $fallback;
            }

            try {
                return \Carbon\Carbon::parse($value)->format('Y/m/d');
            } catch (\Throwable $e) {
                return $fallback;
            }
        };
        $formatRentalMoney = function ($value, $fallback = '______') {
            if ($value === null || $value === '') {
                return $fallback;
            }

            return is_numeric($value) ? number_format((float) $value) : (string) $value;
        };
        $contractInfo = data_get($contract, 'info');
        $contractDurationDays = data_get($contractInfo, 'contract_duration_days')
            ?? data_get($contractInfo, 'contract_duration')
            ?? '______';
        $packageDurationLabel = data_get($contractInfo, 'package.duration_label');
        $contractDurationLabel = $packageDurationLabel
            ?: (is_numeric($contractDurationDays) ? $contractDurationDays . ' يوم' : $contractDurationDays);
        $contractStartDate = data_get($contractInfo, 'ingaz_from')
            ?? data_get($contractInfo, 'contract_date')
            ?? data_get($contract, 'contract_date');
        $contractEndDate = data_get($contractInfo, 'ingaz_to');
        $monthlyCost = data_get($contractInfo, 'monthly_cost');
        $contractValue = data_get($contractInfo, 'contract_value')
            ?? data_get($contractInfo, 'total_cost');
        $workingHours = data_get($contractInfo, 'working_hours_per_day')
            ?? data_get($contractInfo, 'working_hours')
            ?? 8;
        $clientName = data_get($contract, 'user.name', 'اسم العميل');
        $clientIdentityNumber = data_get($contract, 'user.national_num')
            ?? data_get($contract, 'user.iqama_number')
            ?? '';
        $clientPhone = data_get($contract, 'user.phone', '');
        $clientSecondPhone = data_get($contract, 'user.phone2', '');
        $clientEmail = data_get($contract, 'user.email', '');
        $clientAddress = data_get($contract, 'user.address', '');
        $clientCity = data_get($contract, 'user.city', $contractSettings['city'] ?? 'الرياض');
        $clientBirthdate = data_get($contract, 'user.birthdate');
        $clientGender = data_get($contract, 'user.gender', '______');
        $clientNationality = data_get($contract, 'user.nationality.name')
            ?? data_get($contract, 'user.nationality.title')
            ?? 'السعودية';
        $maidName = data_get($contract, 'maid.name', '______');
        $maidNationality = data_get($contract, 'maid.nationality.title')
            ?? data_get($contract, 'maid.nationality.name')
            ?? '______';
        $maidPassportNumber = data_get($contract, 'maid.passport_num', '______');
        $maidProfession = data_get($contract, 'maid.job_title', data_get($contract, 'maid.profession', 'عاملة منزلية'));
        $maidGender = data_get($contract, 'maid.gender', 'أنثى');
        $maidExperience = data_get($contract, 'maid.experience', '-');
        $paymentDueDate = $formatRentalDate($contractStartDate);
        $salaryAmount = data_get($contractInfo, 'monthly_salary');
        $taxAmount = is_numeric($contractValue) ? ((float) $contractValue * 0.15) : null;
        $partyOneText = replaceDocumentPlaceholders($partyOneTextTemplate, [
            'اسم_الشركة' => $documentCompanyName,
            'رقم_السجل' => $contractSettings['commercial_register'] ?? '______',
            'تاريخ_السجل' => filled($contractSettings['commercial_date'] ?? null) ? $formatRentalDate($contractSettings['commercial_date']) : '______',
            'عنوان_الشركة' => $documentAddress ?: '______',
            'هاتف_الشركة' => $documentPhone ?: '______',
            'جوال_الشركة' => $documentMobile ?: '....................',
            'بريد_الشركة' => $documentEmail ?: '______',
            'رقم_الترخيص' => $contractSettings['license_number'] ?? '______',
        ]);
        $partyOneRows = [
            [
                ['label' => 'اسم المنشأة', 'value' => $documentCompanyName],
                ['label' => 'السجل التجاري', 'value' => $contractSettings['commercial_register'] ?? '______'],
            ],
            [
                ['label' => 'تاريخ إصدار السجل', 'value' => filled($contractSettings['commercial_date'] ?? null) ? $formatRentalDate($contractSettings['commercial_date']) : '______'],
                ['label' => 'ترخيص الاستقدام', 'value' => $contractSettings['license_number'] ?? '______'],
            ],
            [
                ['label' => 'العنوان', 'value' => $documentAddress ?: '______'],
                ['label' => 'الهاتف', 'value' => $documentPhone ?: '______', 'dir' => 'ltr'],
            ],
            [
                ['label' => 'البريد الإلكتروني', 'value' => $documentEmail ?: '______', 'dir' => 'ltr'],
                ['label' => 'مركز الاتصال', 'value' => $documentMobile ?: '....................', 'dir' => 'ltr'],
            ],
        ];
        $termsByPage = isset($termsByPage)
            ? collect($termsByPage)
            : collect([
                1 => collect($termsPage1 ?? []),
                2 => collect($termsPage2 ?? []),
                3 => collect($termsPage3 ?? []),
            ]);
        $termsByPage = $termsByPage
            ->map(fn ($terms) => collect($terms))
            ->filter(fn ($terms) => $terms->isNotEmpty())
            ->sortKeys();
        $totalPages = max(3, $termsByPage->count() + 2);
        $pageOneTerms = $termsByPage->get(1, collect());
        $extraTermPages = $termsByPage->reject(fn ($terms, $pageNumber) => (int) $pageNumber === 1);
        $termReplacements = [
            'اسم_الشركة' => $documentCompanyName,
            'اسم_العميل' => $clientName,
            'رقم_هوية_العميل' => $clientIdentityNumber ?: '______',
            'جوال_العميل' => $clientPhone ?: '______',
            'عنوان_العميل' => $clientAddress ?: '______',
            'رقم_العقد' => data_get($contract, 'contract_number', '______'),
            'تاريخ_العقد' => $formatRentalDate(data_get($contract, 'contract_date')),
            'مدة_العقد' => $contractDurationLabel ?: '______',
            'تاريخ_البداية' => $formatRentalDate($contractStartDate),
            'تاريخ_النهاية' => $formatRentalDate($contractEndDate),
            'التكلفة_الشهرية' => $formatRentalMoney($monthlyCost),
            'إجمالي_التكلفة' => $formatRentalMoney($contractValue),
            'ساعات_العمل' => $workingHours ?: '______',
            'اسم_العاملة' => $maidName,
            'جنسية_العاملة' => $maidNationality,
            'رقم_الجواز' => $maidPassportNumber,
        ];
        $formatRentalContractTerm = function ($term) use ($termReplacements) {
            $content = replaceDocumentPlaceholders((string) data_get($term, 'term_content', ''), $termReplacements);
            $content = str_replace('----------', '________', $content);

            return nl2br(e($content));
        };
    @endphp
    <title>{{ $documentTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @font-face {
            font-family: "RentalContractArabic";
            src: url("{{ asset('cv/webfonts/BalooBhaijaan2-Regular.ttf') }}") format("truetype");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: "RentalContractArabic";
            src: url("{{ asset('cv/webfonts/BalooBhaijaan2-Bold.ttf') }}") format("truetype");
            font-weight: 700 900;
            font-style: normal;
            font-display: swap;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "RentalContractArabic", Tahoma, Arial, "DejaVu Sans", sans-serif;
            font-size: 14.2px;
            line-height: 1.45;
            color: #07516a;
            background: #f0f0f0;
            direction: rtl;
            text-align: right;
        }

        .container {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 6mm 0;
            position: relative;
        }

        .watermark {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.035;
            width: 118mm;
            height: auto;
            z-index: 0;
            pointer-events: none;
        }

        .contract-page {
            position: relative;
            z-index: 1;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 5mm;
            padding: 12mm 27mm 13mm;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
            overflow: visible;
            direction: rtl;
            text-align: right;
        }

        .contract-page::before {
            display: none;
        }

        .page-number {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 6mm;
            text-align: center;
            color: #07516a;
            font-size: 11px;
            direction: ltr;
        }

        .page-break {
            page-break-before: always;
        }

        .header {
            display: block;
            padding: 0 0 2mm;
            margin-bottom: 4mm;
            position: relative;
            direction: rtl;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 26mm;
            gap: 1.5mm;
        }

        .logo-container img {
            display: block;
            width: auto;
            height: 22mm;
            max-width: 44mm;
            max-height: 22mm;
            object-fit: contain;
        }

        .ministry-logo img {
            height: 22mm;
            max-width: 48mm;
            max-height: 22mm;
        }

        .page-logo-header {
            display: grid;
            grid-template-columns: 48mm 1fr 48mm;
            align-items: center;
            min-height: 26mm;
            margin-bottom: 4mm;
            direction: ltr;
        }

        .page-logo-header .logo-container {
            direction: rtl;
        }

        .document-title {
            text-align: center;
        }

        h1 {
            margin: 0;
            color: #07516a;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.35;
        }

        .document-subtitle {
            margin-top: 1mm;
            color: #07516a;
            font-size: 15px;
            font-weight: 700;
        }

        .sadad-inline {
            position: absolute;
            top: 40mm;
            right: 27mm;
            color: #07516a;
            font-size: 12px;
            font-weight: 700;
            direction: rtl;
        }

        .contract-number-badge {
            border: 0;
            background: transparent;
            padding: 0;
            text-align: center;
        }

        .contract-number-badge span {
            display: block;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }

        .contract-number-badge strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            direction: ltr;
            text-align: center;
        }

        .party-one-text {
            margin: 0 0 5mm;
            padding: 2.2mm 4mm;
            background: #f0f3f4;
            text-align: center;
            font-size: 13.2px;
            font-weight: 600;
        }

        .party-one-table {
            margin-bottom: 5mm;
        }

        .party-one-table th {
            width: 19%;
            white-space: nowrap;
        }

        .party-one-table td {
            width: 31%;
            text-align: right;
            font-weight: 600;
        }

        h3 {
            display: block;
            margin: 4mm 0 2.2mm;
            color: #07516a;
            font-size: 13px;
            font-weight: 700;
            break-after: avoid;
            direction: rtl;
            text-align: right;
        }

        h3::before {
            display: none;
        }

        .official-intro {
            margin: 8mm 0 5mm;
            text-align: center;
            font-weight: 700;
        }

        .section-heading {
            margin: 4mm 0 0;
            padding: 2mm 4mm;
            text-align: right;
            color: #fff;
            background: #07566b;
            font-size: 14px;
            font-weight: 700;
            direction: rtl;
        }

        .service-page-title {
            margin: 0 0 6mm;
            padding: 0;
            background: transparent;
            color: #07516a;
            text-align: right;
            font-size: 13px;
        }

        .data-table,
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 4.5mm;
            table-layout: fixed;
            break-inside: avoid;
            direction: rtl;
            font-size: 13.1px;
        }

        .data-table th,
        .data-table td,
        .payment-table th,
        .payment-table td {
            border: 0;
            padding: 2.2mm 1.5mm;
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
            direction: rtl;
        }

        .data-table th,
        .payment-table th {
            background: #bed3da;
            color: #07516a;
            font-weight: 700;
        }

        .data-table td {
            min-height: 8mm;
            background: #f0f3f4;
        }

        .payment-table td {
            background: #f0f3f4;
        }

        .contract-info {
            margin-bottom: 3mm;
            padding: 2.5mm 4mm;
            border: 1px solid #dbe4ea;
            background: #f8fafc;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
        }

        .parties-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 3mm;
            margin: 3mm 0;
        }

        .party {
            border: 1px solid #dbe4ea;
            border-radius: 4px;
            padding: 3mm;
            background: #fff;
            line-height: 1.5;
            break-inside: avoid;
        }

        .party-title {
            margin: -4mm -4mm 3mm;
            padding: 2.5mm 4mm;
            background: #f4fbfc;
            border-bottom: 1px solid #dbe4ea;
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .party div:not(.party-title) {
            margin-bottom: 1.6mm;
        }

        .contract-body {
            margin: 17mm 0 4mm;
            padding: 0;
            border-right: 0;
            background: transparent;
            text-align: center;
            break-inside: avoid;
            direction: rtl;
        }

        .contract-body p {
            margin: 0 0 2mm;
        }

        .terms-section {
            margin: 0;
        }

        .term-item {
            position: relative;
            margin-bottom: 3.1mm;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            line-height: 1.42;
            text-align: right;
            break-inside: avoid;
            direction: rtl;
        }

        .term-number {
            display: inline-block;
            min-width: 6mm;
            height: auto;
            margin-left: .8mm;
            border-radius: 0;
            background: transparent;
            color: #07516a;
            font-weight: 700;
        }

        .worker-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 6mm 0 0;
            overflow: hidden;
            border: 1px solid #cfdbe3;
            border-radius: 4px;
        }

        .worker-table td {
            border-left: 1px solid #dbe4ea;
            border-bottom: 1px solid #dbe4ea;
            padding: 3mm 2mm;
            text-align: center;
            font-size: 11.5px;
            vertical-align: middle;
        }

        .worker-table tr:first-child td {
            background: #1f6f78;
            color: #fff;
            font-weight: 800;
        }

        .worker-table tr:last-child td {
            border-bottom: 0;
        }

        .worker-table td:last-child {
            border-left: 0;
        }

        .signature-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16mm;
            margin-top: 12mm;
            break-inside: avoid;
        }

        .signature-closing {
            margin-top: 22mm;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .signature-box {
            min-height: 32mm;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: #fff;
            text-align: right;
        }

        .signature-box h4 {
            margin: 0 0 4mm;
            color: #000;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .signature-line {
            min-height: 7mm;
            margin: 1mm 0;
            padding: 0;
            border-bottom: 0;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 14.2px;
            }

            .container {
                width: auto;
                padding: 0;
            }

            .contract-page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 12mm 27mm 13mm;
                box-shadow: none;
                page-break-after: always;
            }

            .contract-page::before {
                display: none;
            }

            .no-print {
                display: none !important;
            }

            .phpdebugbar,
            .phpdebugbar-openhandler,
            script[src*="_debugbar"],
            link[href*="_debugbar"] {
                display: none !important;
            }

            h3 {
                margin-top: 3.5mm;
                margin-bottom: 1mm;
            }

            .term-item {
                margin-bottom: 3.1mm;
                padding-top: 0;
                padding-bottom: 0;
            }

            .sadad-inline {
                top: 40mm;
                right: 27mm;
            }

            .page-number {
                bottom: 6mm;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }

        @media print {
            .print-button {
                display: none;
            }
        }

        /* Signature Styles */
        .signature-container {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .signature-canvas-container {
            text-align: center;
            margin: 15px 0;
        }

        #signatureCanvas {
            border: 2px solid #ccc;
            border-radius: 5px;
            background: white;
            cursor: crosshair;
        }

        .signature-controls {
            margin-top: 10px;
            text-align: center;
        }

        .signature-btn {
            margin: 0 5px;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .signature-btn-primary {
            background: #28a745;
            color: white;
        }

        .signature-btn-primary:hover {
            background: #218838;
        }

        .signature-btn-secondary {
            background: #6c757d;
            color: white;
        }

        .signature-btn-secondary:hover {
            background: #5a6268;
        }

        .signature-btn-danger {
            background: #dc3545;
            color: white;
        }

        .signature-btn-danger:hover {
            background: #c82333;
        }

        .signature-display {
            margin-top: 15px;
            text-align: center;
        }

        .signature-display img {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .signature-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .signature-status.signed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .signature-status.not-signed {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media print {
            .signature-controls {
                display: none !important;
            }
            .signature-container {
                border: none;
                background: none;
            }
        }

        @media screen and (max-width: 900px) {
            .container,
            .contract-page {
                width: 100%;
            }

            .contract-page {
                min-height: auto;
                padding: 16px;
            }

            .header,
            .parties-section,
            .signature-section {
                grid-template-columns: 1fr;
            }

            .contract-number-badge {
                max-width: 260px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        طباعة العقد
    </button>

    <div class="container">
         <!-- Watermark Logo -->
         @php
         $logoPath = public_path('admin/images/logo.png');
         $logoUrl = documentLogoUrl(file_exists($logoPath) ? asset('admin/images/logo.png') : asset('a.png'));
     @endphp
     <img src="{{ $logoUrl }}" class="watermark" alt="">
        <!-- الصفحة الأولى -->
        <div class="contract-page page-1">
            <div class="header">
                <div class="page-logo-header">
                    <div class="logo-container ministry-logo">
                        <img src="{{ $ministryLogo }}" alt="وزارة الموارد البشرية والتنمية الاجتماعية">
                    </div>
                    <div></div>
                    <div class="logo-container">
                        <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                    </div>
                </div>
                <div class="document-title">
                    <h1>{{ $documentTitle }}</h1>
                    <div class="document-subtitle">عقد رقم ({{ data_get($contract, 'contract_number', data_get($contract, 'id', '______')) }})</div>
                </div>
                <div class="sadad-inline">{{ $sadadLabel }} {{ $sadadCode }}</div>
            </div>

            <div class="official-intro">
                بعون الله وتوفيقه، تم إبرام هذا العقد بتاريخ {{ $formatRentalDate(data_get($contract, 'contract_date', now())) }}م
                بمدينة {{ $contractSettings['city'] ?? 'الرياض' }} بين كل من الطرفين:
            </div>

            <div class="section-heading">{{ $partyOneTitle }}</div>
            <table class="data-table party-one-table">
                @foreach($partyOneRows as $row)
                    <tr>
                        @foreach($row as $field)
                            <th>{{ $field['label'] }}</th>
                            <td @if(($field['dir'] ?? 'rtl') === 'ltr') dir="ltr" @endif>{{ $field['value'] }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>

            <div class="section-heading">{{ $partyTwoTitle }}</div>
            <table class="data-table">
                <tr>
                    <th>رقم الهوية الوطنية / الإقامة</th>
                    <th colspan="3">الاسم باللغة العربية، مطابقاً للهوية الوطنية / الإقامة</th>
                </tr>
                <tr>
                    <td dir="ltr">{{ $clientIdentityNumber ?: '______' }}</td>
                    <td colspan="3">{{ $clientName }}</td>
                </tr>
                <tr>
                    <th>الدخل الشهري</th>
                    <th>الجنس</th>
                    <th>الجنسية</th>
                    <th>تاريخ الميلاد</th>
                </tr>
                <tr>
                    <td>______</td>
                    <td>{{ $clientGender ?: '______' }}</td>
                    <td>{{ $clientNationality ?: '______' }}</td>
                    <td>{{ $formatRentalDate($clientBirthdate) }}</td>
                </tr>
                <tr>
                    <th colspan="2">بيانات الاتصال</th>
                    <th colspan="2">بيانات العنوان الوطني</th>
                </tr>
                <tr>
                    <th>رقم الجوال الثاني</th>
                    <th>الجوال</th>
                    <th>الحي</th>
                    <th>المدينة</th>
                </tr>
                <tr>
                    <td dir="ltr">{{ $clientSecondPhone ?: '...................' }}</td>
                    <td dir="ltr">{{ $clientPhone ?: '______' }}</td>
                    <td>{{ $clientAddress ?: '...................' }}</td>
                    <td>{{ $clientCity ?: '______' }}</td>
                </tr>
                <tr>
                    <th>البريد الإلكتروني</th>
                    <th>هاتف المنزل</th>
                    <th>الرمز البريدي</th>
                    <th>العنوان المختصر</th>
                </tr>
                <tr>
                    <td dir="ltr">{{ $clientEmail ?: '...................' }}</td>
                    <td>...................</td>
                    <td>...................</td>
                    <td>...................</td>
                </tr>
                <tr>
                    <th>تحويلة</th>
                    <th>هاتف العمل</th>
                    <th colspan="2">نوع الوظيفة</th>
                </tr>
                <tr>
                    <td>...................</td>
                    <td>...................</td>
                    <td colspan="2">...................</td>
                </tr>
                <tr>
                    <th>رقم التواصل</th>
                    <th colspan="3">اسم أحد الأقارب يسكن معك يمكن الاتصال به</th>
                </tr>
                <tr>
                    <td>...................</td>
                    <td colspan="3">...................</td>
                </tr>
            </table>

            <div class="page-number">1/{{ $totalPages }}</div>
        </div>

        <div class="contract-page page-break">
            <div class="page-logo-header">
                <div class="logo-container ministry-logo">
                    <img src="{{ $ministryLogo }}" alt="وزارة الموارد البشرية والتنمية الاجتماعية">
                </div>
                <div></div>
                <div class="logo-container">
                    <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                </div>
            </div>
            <div class="section-heading service-page-title">{{ $serviceTitle }}</div>
            <table class="data-table service-table">
                <tr>
                    <th>مواصفات خاصة</th>
                    <th>الجنسية</th>
                    <th>الجنس</th>
                    <th>المهنة</th>
                </tr>
                <tr>
                    <td>{{ $maidExperience ?: '______' }}</td>
                    <td>{{ $maidNationality }}</td>
                    <td>{{ $maidGender ?: 'أنثى' }}</td>
                    <td>{{ $maidProfession ?: 'عاملة منزلية' }}</td>
                </tr>
                <tr>
                    <th>اسم العاملة</th>
                    <th>رقم الجواز</th>
                    <th>ساعات العمل اليومية</th>
                    <th>مدة الخدمة المطلوبة</th>
                </tr>
                <tr>
                    <td>{{ $maidName }}</td>
                    <td dir="ltr">{{ $maidPassportNumber }}</td>
                    <td>{{ $workingHours }} ساعات</td>
                    <td>{{ $contractDurationLabel }}</td>
                </tr>
            </table>

            <div class="contract-body">
                <p><strong>تمهيد:</strong></p>
                <p>{{ $rentalIntroText }}</p>
            </div>
            <div class="terms-section">
                @if($pageOneTerms->isNotEmpty())
                    @include('admin.print.partials.rental-contract-terms', [
                        'terms' => $pageOneTerms,
                        'formatRentalContractTerm' => $formatRentalContractTerm,
                    ])
                @else
                    <h3>البند الأول: أجزاء العقد</h3>
                    <div class="term-item">
                        يعتبر التمهيد والبيانات والمعلومات المذكورة أعلاه جزءاً لا يتجزأ من هذا العقد يقرأ ويفسر على هذا الأساس.
                    </div>
                @endif
            </div>
            <div class="page-number">2/{{ $totalPages }}</div>
        </div>

        @forelse($extraTermPages as $pageNumber => $pageTerms)
            <div class="contract-page page-break">
                <div class="page-logo-header">
                    <div class="logo-container ministry-logo">
                        <img src="{{ $ministryLogo }}" alt="وزارة الموارد البشرية والتنمية الاجتماعية">
                    </div>
                    <div></div>
                    <div class="logo-container">
                        <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                    </div>
                </div>
                @include('admin.print.partials.rental-contract-terms', [
                    'terms' => $pageTerms,
                    'formatRentalContractTerm' => $formatRentalContractTerm,
                    'suppressFirstHeading' => in_array((int) $pageNumber, [3, 4, 5, 6], true),
                ])

                @if((int) $pageNumber === 5)
                    <table class="payment-table">
                        <tr>
                            <th colspan="3">جدول سداد الدفعات</th>
                        </tr>
                        <tr>
                            <th>رقم الدفعة</th>
                            <th>قيمة الدفعة المستحقة</th>
                            <th>تاريخ الاستحقاق</th>
                        </tr>
                        <tr>
                            <td>دفعة #1</td>
                            <td>{{ $formatRentalMoney($contractValue, '______') }} ريال</td>
                            <td>{{ $paymentDueDate }}</td>
                        </tr>
                    </table>
                    <table class="payment-table">
                        <tr>
                            <th>البند</th>
                            <th>القيمة</th>
                            <th>ضريبة القيمة المضافة</th>
                            <th>القيمة الإجمالية</th>
                        </tr>
                        <tr>
                            <td>أجر العامل/العاملة</td>
                            <td>{{ $formatRentalMoney($salaryAmount, '0') }} ريال</td>
                            <td>0 ريال</td>
                            <td>{{ $formatRentalMoney($salaryAmount, '0') }} ريال</td>
                        </tr>
                        <tr>
                            <td>قيمة الخدمة</td>
                            <td>{{ $formatRentalMoney($contractValue, '______') }} ريال</td>
                            <td>{{ $formatRentalMoney($taxAmount, '______') }} ريال</td>
                            <td>{{ $formatRentalMoney(is_numeric($contractValue) ? ((float) $contractValue + (float) $taxAmount) : null, '______') }} ريال</td>
                        </tr>
                        <tr>
                            <th>قيمة العقد الإجمالي</th>
                            <th>{{ $formatRentalMoney($contractValue, '______') }} ريال</th>
                            <th>{{ $formatRentalMoney($taxAmount, '______') }} ريال</th>
                            <th>{{ $formatRentalMoney(is_numeric($contractValue) ? ((float) $contractValue + (float) $taxAmount) : null, '______') }} ريال</th>
                        </tr>
                    </table>
                @endif
                <div class="page-number">{{ $loop->iteration + 2 }}/{{ $totalPages }}</div>
            </div>
        @empty
        @endforelse

        <div class="contract-page page-break">
            <div class="page-logo-header">
                <div class="logo-container ministry-logo">
                    <img src="{{ $ministryLogo }}" alt="وزارة الموارد البشرية والتنمية الاجتماعية">
                </div>
                <div></div>
                <div class="logo-container">
                    <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                </div>
            </div>
            @include('admin.print.partials.rental-contract-signatures')
            <div class="page-number">{{ $totalPages }}/{{ $totalPages }}</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // متغيرات التوقيع
        let canvas = document.getElementById('signatureCanvas');
        let ctx = canvas ? canvas.getContext('2d') : null;
        let isDrawing = false;
        let hasDrawnSignature = false;
        let lastX = 0;
        let lastY = 0;

        // إعداد Canvas
        function setupCanvas() {
            if (!canvas || !ctx) return;

            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // إضافة نص توجيهي
            ctx.fillStyle = '#999';
            ctx.font = '16px Cairo';
            ctx.textAlign = 'center';
            ctx.fillText('اضغط هنا للتوقيع', canvas.width / 2, canvas.height / 2);
        }

        // بدء الرسم
        function startDrawing(e) {
            if (!canvas || !ctx) return;
            if (!hasDrawnSignature) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            hasDrawnSignature = true;
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // الرسم
        function draw(e) {
            if (!isDrawing || !ctx) return;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(e.offsetX, e.offsetY);
            ctx.stroke();

            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // إيقاف الرسم
        function stopDrawing() {
            isDrawing = false;
        }

        // مسح التوقيع
        function clearSignature() {
            if (!canvas || !ctx) return;
            hasDrawnSignature = false;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            setupCanvas();
        }

        // حفظ التوقيع
        function saveSignature() {
            if (!canvas || !ctx) return;
            if (!hasDrawnSignature) {
                Swal.fire({
                    title: 'تنبيه!',
                    text: 'يرجى التوقيع أولاً قبل الحفظ',
                    icon: 'warning',
                    confirmButtonText: 'حسناً'
                });
                return;
            }

            // تحويل Canvas إلى صورة
            const signatureImage = canvas.toDataURL('image/png');

            console.log('Saving signature for contract ID:', {{ $contract->id }});

            // إرسال التوقيع للخادم
            fetch('{{ route("RentContract.saveSignature", $contract->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    signature: signatureImage
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    Swal.fire({
                        title: 'تم الحفظ بنجاح!',
                        text: 'تم حفظ التوقيع على العقد',
                        icon: 'success',
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        // تحديث الصفحة مع إضافة timestamp لتجنب cache
                        window.location.href = window.location.href + '?t=' + Date.now();
                    });
                } else {
                    Swal.fire({
                        title: 'خطأ!',
                        text: data.message || 'حدث خطأ أثناء حفظ التوقيع',
                        icon: 'error',
                        confirmButtonText: 'حسناً'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'خطأ!',
                    text: 'حدث خطأ أثناء حفظ التوقيع',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            });
        }

        // تحميل التوقيع
        function downloadSignature() {
            if (!canvas) return;
            const link = document.createElement('a');
            link.download = 'توقيع_العقد_{{ $contract->id }}.png';
            link.href = canvas.toDataURL();
            link.click();
        }

        // إعداد الأحداث
        if (canvas) {
            setupCanvas();
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);

            // دعم اللمس للأجهزة المحمولة
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                startDrawing({ offsetX: x, offsetY: y });
            });

            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
                const touch = e.touches[0];
                const rect = canvas.getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                draw({ offsetX: x, offsetY: y });
            });

            canvas.addEventListener('touchend', stopDrawing);
        }
    </script>
</body>
</html>
