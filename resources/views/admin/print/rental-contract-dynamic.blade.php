<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $documentTitle = documentSetting('rental_contract_title', 'عقد تأجير عاملة منزلية');
        $documentCompanyName = documentSetting('document_company_name', $contractSettings['company_name'] ?? documentSetting('website_name', config('app.name')));
        $documentLogo = documentLogoUrl(asset('admin/assets/img/company_logo.png'));
        $documentAddress = $contractSettings['address'] ?? documentSetting('location', 'الرياض');
        $documentPhone = $contractSettings['phone'] ?? documentSetting('phone', '');
        $documentEmail = $contractSettings['email'] ?? documentSetting('email', '');
        $rentalIntroText = replaceDocumentPlaceholders((string) documentSetting(
            'rental_contract_intro_text',
            'حيث أن الطرف الأول مرخص ويسمح له بتقديم خدمات العمالة المنزلية ولديه الخبرات والإمكانيات اللازمة لتوفير خدمات العمالة المنزلية، وذلك وفق الأنظمة والقرارات والتعليمات المعمول بها في المملكة العربية السعودية وبما لا يتعارض مع أحكام الشريعة الإسلامية، وأحاجة الطرف الثاني لخدمات بعض العمالة المنزلية، فقد اتفق الطرفان وهما بكامل أهليتهما المعتبرة شرعا ونظاما على ما يلي:'
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
        $clientAddress = data_get($contract, 'user.address', '');
        $clientNationality = data_get($contract, 'user.nationality.name')
            ?? data_get($contract, 'user.nationality.title')
            ?? 'السعودية';
        $maidName = data_get($contract, 'maid.name', '______');
        $maidNationality = data_get($contract, 'maid.nationality.title')
            ?? data_get($contract, 'maid.nationality.name')
            ?? '______';
        $maidPassportNumber = data_get($contract, 'maid.passport_num', '______');
        $maidExperience = data_get($contract, 'maid.experience', '-');
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
            'مدة_العقد' => $contractDurationDays ?: '______',
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
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap');

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            font-size: 12.5px;
            line-height: 1.75;
            color: #111827;
            background: #eef2f5;
            direction: rtl;
        }

        .container {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 10mm 0;
            position: relative;
        }

        .watermark {
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
            margin: 0 auto 10mm;
            padding: 12mm;
            background: #fff;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .10);
            overflow: hidden;
        }

        .contract-page::before {
            content: "";
            position: absolute;
            inset: 6mm;
            border: 1px solid #d7dee6;
            pointer-events: none;
        }

        .page-break {
            page-break-before: always;
        }

        .header {
            display: grid;
            grid-template-columns: 40mm 1fr 42mm;
            gap: 8mm;
            align-items: center;
            padding: 0 0 7mm;
            margin-bottom: 7mm;
            border-bottom: 2px solid #1f6f78;
            position: relative;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-container img {
            max-width: 34mm;
            max-height: 25mm;
            object-fit: contain;
        }

        .document-title {
            text-align: center;
        }

        h1 {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.3;
        }

        .document-subtitle {
            margin-top: 3mm;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .contract-number-badge {
            border: 1px solid #bfd4d8;
            background: #f4fbfc;
            border-radius: 4px;
            padding: 4mm 3mm;
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
            color: #1f6f78;
            font-size: 17px;
            font-weight: 800;
            direction: ltr;
        }

        h3 {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 7mm 0 3mm;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
            break-after: avoid;
        }

        h3::before {
            content: "";
            width: 4px;
            height: 18px;
            border-radius: 3px;
            background: #1f6f78;
            flex: 0 0 auto;
        }

        .contract-info {
            margin-bottom: 5mm;
            padding: 4mm 5mm;
            border: 1px solid #dbe4ea;
            background: #f8fafc;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
        }

        .parties-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4mm;
            margin: 5mm 0;
        }

        .party {
            border: 1px solid #dbe4ea;
            border-radius: 4px;
            padding: 4mm;
            background: #fff;
            line-height: 1.75;
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
            margin: 5mm 0;
            padding: 4mm 5mm;
            border-right: 4px solid #1f6f78;
            background: #fbfdfe;
            text-align: justify;
            break-inside: avoid;
        }

        .contract-body p {
            margin: 0 0 2mm;
        }

        .terms-section {
            margin: 5mm 0 0;
        }

        .term-item {
            position: relative;
            margin-bottom: 2.4mm;
            padding: 2.2mm 4mm;
            border: 1px solid #e6edf2;
            border-radius: 4px;
            background: rgba(255, 255, 255, .86);
            line-height: 1.72;
            text-align: justify;
            break-inside: avoid;
        }

        .term-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 7mm;
            height: 7mm;
            margin-left: 2mm;
            border-radius: 50%;
            background: #e8f5f7;
            color: #1f6f78;
            font-weight: 800;
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
            gap: 8mm;
            margin-top: 10mm;
            break-inside: avoid;
        }

        .signature-box {
            min-height: 42mm;
            padding: 5mm;
            border: 1px solid #cfdbe3;
            border-radius: 4px;
            background: #fff;
            text-align: center;
        }

        .signature-box h4 {
            margin: 0 0 4mm;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .signature-line {
            min-height: 10mm;
            margin: 2mm 0;
            padding: 2mm 0;
            border-bottom: 1px dashed #94a3b8;
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
            }

            .container {
                width: auto;
                padding: 0;
            }

            .contract-page {
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                page-break-after: always;
            }

            .contract-page::before {
                inset: 5mm;
            }

            .no-print {
                display: none !important;
            }

            h3 {
                margin-top: 5mm;
                margin-bottom: 2mm;
            }

            .term-item {
                margin-bottom: 1.8mm;
                padding-top: 1.8mm;
                padding-bottom: 1.8mm;
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
                <div class="logo-container">
                    {{-- <img src="{{ $logoUrl }}" alt="{{ $documentCompanyName }}"> --}}
                    <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                </div>
                <div class="document-title">
                    <h1>{{ $documentTitle }}</h1>
                    <div class="document-subtitle">{{ $documentCompanyName }}</div>
                </div>
                <div class="contract-number-badge">
                    <span>رقم العقد</span>
                    <strong>{{ data_get($contract, 'contract_number', data_get($contract, 'id', '______')) }}</strong>
                    <span>Contract No.</span>
                </div>
            </div>

            <div class="contract-info">
                الحمد لله وحده وبعد، ففي يوم الموافق {{ $formatRentalDate(data_get($contract, 'contract_date', now())) }} تم الاتفاق بمدينة الرياض بين كل من:
            </div>

            <div class="parties-section">
                <div class="party">
                    <div class="party-title">1- {{ $documentCompanyName }}</div>
                    <div>لاستقدام العمالة المنزلية ترخيص وزارة العمل رقم / {{ $contractSettings['license_number'] ?? '4001772' }} وتاريخ : {{ $contractSettings['license_date'] ?? '2022/10/13' }} م سجل تجاري رقم / {{ $contractSettings['commercial_register'] ?? '1010601958' }}</div>
                    <div>وتاريخ: {{ \Carbon\Carbon::parse($contractSettings['commercial_date'] ?? '1441/03/03')->format('Y/m/d') }} هـ صادر من الرياض</div>
                    <div>وعنوانه {{ $documentAddress ?: '-' }} | هاتف: {{ $documentPhone ?: '-' }} | البريد الالكتروني: {{ $documentEmail ?: '-' }}</div>
                    <div>ويمثله في التوقيع من هذا العقد المدير بصفته المالك ويشار إليه فيما بعد بـ (الطرف الأول).</div>
                </div>

                <div class="party">
                    <div class="party-title">2- {{ $clientName }}</div>
                    <div>الجنسية {{ $clientNationality }} - بموجب هوية وطنية رقم {{ $clientIdentityNumber }} وعنوانه {{ $clientAddress }} الرمز البريدي مكتب جوال {{ $clientPhone }}</div>
                    <div>ويشار إليه فيما بعد بـ (الطرف الثاني) -------------- فاكس {{ $contractSettings['fax'] ?? '' }}</div>
                </div>
            </div>

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
                    <h3>أولاً: موضوع العقد</h3>
                    <div class="term-item">
                        يقر الطرف الأول بأنه يزاول نشاط تقديم خدمات العمالة المنزلية بموجب ترخيص رقم -------------- ويقدم الطرف الأول خدمات العمالة المنزلية للطرف الثاني بناء على طلبه وفقا للشروط والأحكام الواردة في هذا العقد.
                    </div>
                @endif

                <!-- جدول بيانات العاملة -->
                <table class="worker-table">
                    <tr>
                        <td><strong>الجنسية</strong></td>
                        <td><strong>رقم جواز السفر</strong></td>
                        <td><strong>المؤهلات والخبرات</strong></td>
                        <td><strong>ساعات العمل اليومية</strong></td>
                        <td><strong>مدة الخدمة المطلوبة</strong></td>
                        <td><strong>التكلفة الشهرية</strong></td>
                    </tr>
                    <tr>
                        <td>{{ $maidNationality }}</td>
                        <td>{{ $maidPassportNumber }}</td>
                        <td>{{ $maidExperience }}</td>
                        <td>{{ $workingHours }} ساعات</td>
                        <td>{{ $contractDurationDays }} يوم</td>
                        <td>{{ $formatRentalMoney($monthlyCost, '-') }} ريال</td>
                    </tr>
                </table>
            </div>
        </div>

        @forelse($extraTermPages as $pageNumber => $pageTerms)
            <div class="contract-page page-break">
                @include('admin.print.partials.rental-contract-terms', [
                    'terms' => $pageTerms,
                    'formatRentalContractTerm' => $formatRentalContractTerm,
                ])

                @if($loop->last)
                    @include('admin.print.partials.rental-contract-signatures')
                @endif
            </div>
        @empty
            <div class="contract-page page-break">
                @include('admin.print.partials.rental-contract-signatures')
            </div>
        @endforelse
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
