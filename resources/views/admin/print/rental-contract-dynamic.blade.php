<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $documentTitle = documentSetting('rental_contract_title', 'عقد تأجير عاملة منزلية');
        $documentCompanyName = documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتنيوم الشرقية للاستقدام');
        $documentLogo = documentLogoUrl(asset('admin/assets/img/company_logo.png'));
        $documentAddress = $contractSettings['address'] ?? documentSetting('location', 'الرياض');
        $documentPhone = $contractSettings['phone'] ?? documentSetting('phone', '');
        $documentEmail = $contractSettings['email'] ?? documentSetting('email', '');
        $rentalIntroText = replaceDocumentPlaceholders((string) documentSetting(
            'rental_contract_intro_text',
            'حيث أن الطرف الأول مرخص ويسمح له بتقديم خدمات العمالة المنزلية ولديه الخبرات والإمكانيات اللازمة لتوفير خدمات العمالة المنزلية، وذلك وفق الأنظمة والقرارات والتعليمات المعمول بها في المملكة العربية السعودية وبما لا يتعارض مع أحكام الشريعة الإسلامية، وأحاجة الطرف الثاني لخدمات بعض العمالة المنزلية، فقد اتفق الطرفان وهما بكامل أهليتهما المعتبرة شرعا ونظاما على ما يلي:'
        ));
    @endphp
    <title>{{ $documentTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            font-size: 13px;
            line-height: 1.6;
            color: #000;
            background: white;
            direction: rtl;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            width: 400px;
            height: auto;
            z-index: 0;
        }

        .page-1, .page-2, .page-3 {
            position: relative;
            z-index: 1;
            min-height: 297mm;
            padding: 15mm;
            background: white;
        }

        .page-break {
            page-break-before: always;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo-container img {
            max-width: 180px;
            height: auto;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 10px 0;
        }

        h3 {
            font-size: 16px;
            font-weight: 600;
            margin: 12px 0 8px;
            color: #000;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .contract-info {
            margin-bottom: 15px;
        }

        .contract-info p {
            line-height: 1.7;
        }

        .parties-section {
            margin: 20px 0;
        }

        .party {
            margin-bottom: 8px;
            line-height: 1.7;
        }

        .party-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .contract-body {
            margin: 20px 0;
        }

        .contract-body p {
            margin-bottom: 4px;
        }

        .terms-section {
            margin: 20px 0;
        }

        .term-item {
            margin-bottom: 8px;
            line-height: 1.6;
            text-align: justify;
            padding: 3px 0;
        }

        .term-number {
            font-weight: 600;
            margin-left: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }

        table tr:first-child td {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
        }

        .signature-box h4 {
            margin-bottom: 10px;
            font-weight: 600;
        }

        .signature-line {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #333;
            min-height: 30px;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                padding: 0;
            }

            .page-1, .page-2, .page-3 {
                padding: 10mm;
                page-break-after: always;
            }

            .no-print {
                display: none;
            }

            h3 {
                margin-top: 8px;
                margin-bottom: 6px;
            }

            .term-item {
                margin-bottom: 6px;
                padding: 2px 0;
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
        <div class="page-1">
            <div class="header">
                <div class="logo-container">
                    {{-- <img src="{{ $logoUrl }}" alt="شركة بلاتنيوم الشرقية"> --}}
                    <img src="{{ $documentLogo }}" alt="{{ $documentCompanyName }}">
                </div>
                <h1>{{ $documentTitle }}</h1>
            </div>

            <div class="contract-info">
                <p style="text-align: center;">الحمد لله وحده وبعد ففي يوم الموافق {{ \Carbon\Carbon::parse($contract->contract_date ?? now())->format('Y/m/d') }} تم الاتفاق بمدينة الرياض بين كل من:</p>
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
                    <div class="party-title">2- {{ $contract->user->name ?? 'اسم العميل' }}</div>
                    <div>الجنسية {{ $contract->user->nationality->name ?? 'السعودية' }} - بموجب هوية وطنية رقم {{ $contract->user->iqama_number ?? '' }} وعنوانه {{ $contract->user->address ?? '' }} الرمز البريدي مكتب جوال {{ $contract->user->phone ?? '' }}</div>
                    <div>ويشار إليه فيما بعد بـ (الطرف الثاني) -------------- فاكس {{ $contractSettings['fax'] ?? '' }}</div>
                </div>
            </div>

            <div class="contract-body">
                <p><strong>تمهيد:</strong></p>
                <p>{{ $rentalIntroText }}</p>
            </div>

            <div class="terms-section">
                @if(isset($termsPage1) && $termsPage1->count() > 0)
                    @php $currentSection = '' @endphp
                    @foreach($termsPage1 as $term)
                        @if($currentSection != $term->section_title)
                            <h3>{{ $term->section_title }}</h3>
                            @php $currentSection = $term->section_title @endphp
                        @endif

                        <div class="term-item">
                            @if($term->term_number)
                                <span class="term-number">{{ $term->term_number }}-</span>
                            @endif
                            @php
                                // استبدال المتغيرات في النص
                                $content = $term->term_content;
                                $content = str_replace('----------', '________', $content);
                                $content = str_replace('[مدة_العقد]', $contract->info->contract_duration ?? '______', $content);
                                $content = str_replace('[تاريخ_البداية]', \Carbon\Carbon::parse($contract->info->contract_date ?? now())->format('Y/m/d'), $content);
                                $content = str_replace('[التكلفة_الشهرية]', number_format($contract->info->monthly_cost ?? 0), $content);
                                $content = str_replace('[إجمالي_التكلفة]', number_format($contract->info->total_cost ?? 0), $content);
                                $content = str_replace('[اسم_العاملة]', $contract->maid->name ?? '______', $content);
                                $content = str_replace('[جنسية_العاملة]', $contract->maid->nationality->name ?? '______', $content);
                                $content = str_replace('[رقم_الجواز]', $contract->maid->passport_num ?? '______', $content);
                            @endphp
                            {!! nl2br(e($content)) !!}
                        </div>
                    @endforeach
                @else
                    <h3>أولاً: موضوع العقد</h3>
                    <div class="term-item">
                        يقر الطرف الأول بأنه يزاول نشاط تقديم خدمات العمالة المنزلية بموجب ترخيص رقم -------------- ويقدم الطرف الأول خدمات العمالة المنزلية للطرف الثاني بناء على طلبه وفقا للشروط والأحكام الواردة في هذا العقد.
                    </div>
                @endif

                <!-- جدول بيانات العاملة -->
                <table>
                    <tr>
                        <td><strong>الجنسية</strong></td>
                        <td><strong>رقم جواز السفر</strong></td>
                        <td><strong>المؤهلات والخبرات</strong></td>
                        <td><strong>ساعات العمل اليومية</strong></td>
                        <td><strong>مدة الخدمة المطلوبة</strong></td>
                        <td><strong>التكلفة الشهرية</strong></td>
                    </tr>
                    <tr>
                        <td>{{ $contract->maid->nationality->name ?? '-' }}</td>
                        <td>{{ $contract->maid->passport_num ?? '-' }}</td>
                        <td>{{ $contract->maid->experience ?? '-' }}</td>
                        <td>{{ $contract->info->working_hours ?? '8' }} ساعات</td>
                        <td>{{ $contract->info->contract_duration ?? '-' }} يوم</td>
                        <td>{{ $contract->info->monthly_cost ?? '-' }} ريال</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- الصفحة الثانية -->
        <div class="page-2 page-break">
            @if(isset($termsPage2) && $termsPage2->count() > 0)
                @php $currentSection = '' @endphp
                @foreach($termsPage2 as $term)
                    @if($currentSection != $term->section_title)
                        <h3>{{ $term->section_title }}</h3>
                        @php $currentSection = $term->section_title @endphp
                    @endif

                    <div class="term-item">
                        @if($term->term_number)
                            <span class="term-number">{{ $term->term_number }}-</span>
                        @endif
                        @php
                            // استبدال المتغيرات في النص
                            $content = $term->term_content;
                            $content = str_replace('----------', '________', $content);
                            $content = str_replace('[مدة_العقد]', $contract->info->contract_duration ?? '______', $content);
                            $content = str_replace('[تاريخ_البداية]', \Carbon\Carbon::parse($contract->info->contract_date ?? now())->format('Y/m/d'), $content);
                            $content = str_replace('[التكلفة_الشهرية]', number_format($contract->info->monthly_cost ?? 0), $content);
                            $content = str_replace('[إجمالي_التكلفة]', number_format($contract->info->total_cost ?? 0), $content);
                            $content = str_replace('[اسم_العاملة]', $contract->maid->name ?? '______', $content);
                            $content = str_replace('[جنسية_العاملة]', $contract->maid->nationality->name ?? '______', $content);
                            $content = str_replace('[رقم_الجواز]', $contract->maid->passport_num ?? '______', $content);
                        @endphp
                        {!! nl2br(e($content)) !!}
                    </div>
                @endforeach
            @else
                <p>لا توجد بنود للصفحة الثانية</p>
            @endif
        </div>

        <!-- الصفحة الثالثة -->
        <div class="page-3 page-break">
            @if(isset($termsPage3) && $termsPage3->count() > 0)
                @php $currentSection = '' @endphp
                @foreach($termsPage3 as $term)
                    @if($currentSection != $term->section_title)
                        <h3>{{ $term->section_title }}</h3>
                        @php $currentSection = $term->section_title @endphp
                    @endif

                    <div class="term-item">
                        @if($term->term_number)
                            <span class="term-number">{{ $term->term_number }}-</span>
                        @endif
                        @php
                            // استبدال المتغيرات في النص
                            $content = $term->term_content;
                            $content = str_replace('----------', '________', $content);
                            $content = str_replace('[مدة_العقد]', $contract->info->contract_duration ?? '______', $content);
                            $content = str_replace('[تاريخ_البداية]', \Carbon\Carbon::parse($contract->info->contract_date ?? now())->format('Y/m/d'), $content);
                            $content = str_replace('[التكلفة_الشهرية]', number_format($contract->info->monthly_cost ?? 0), $content);
                            $content = str_replace('[إجمالي_التكلفة]', number_format($contract->info->total_cost ?? 0), $content);
                            $content = str_replace('[اسم_العاملة]', $contract->maid->name ?? '______', $content);
                            $content = str_replace('[جنسية_العاملة]', $contract->maid->nationality->name ?? '______', $content);
                            $content = str_replace('[رقم_الجواز]', $contract->maid->passport_num ?? '______', $content);
                        @endphp
                        {!! nl2br(e($content)) !!}
                    </div>
                @endforeach
            @else
                <p>لا توجد بنود للصفحة الثالثة</p>
            @endif

            <!-- قسم التوقيع التفاعلي -->
            <div class="signature-container no-print">
                <div class="signature-title">توقيع الطرف الثاني ({{ $contract->user->name ?? 'العميل' }})</div>

                <!-- حالة التوقيع -->
                <div class="signature-status {{ $contract->signature ? 'signed' : 'not-signed' }}">
                    @if($contract->signature)
                        ✅ تم التوقيع على العقد
                    @else
                        ❌ لم يتم التوقيع بعد
                    @endif
                </div>

                <!-- عرض التوقيع المحفوظ -->
                @if($contract->signature)
                    <div class="signature-display">
                        <img src="{{ asset('storage/signatures/' . $contract->signature) }}" alt="التوقيع المحفوظ">
                    </div>
                @elseif(isset($allowSignature) && $allowSignature)
                    <!-- Canvas للتوقيع - يظهر فقط بعد إرسال العقد -->
                    <div class="signature-canvas-container">
                        <canvas id="signatureCanvas" width="400" height="150"></canvas>
                    </div>

                    <!-- أزرار التحكم -->
                    <div class="signature-controls">
                        <button type="button" class="signature-btn signature-btn-primary" onclick="saveSignature()">
                            حفظ التوقيع
                        </button>
                        <button type="button" class="signature-btn signature-btn-secondary" onclick="clearSignature()">
                            مسح التوقيع
                        </button>
                        <button type="button" class="signature-btn signature-btn-danger" onclick="downloadSignature()">
                            تحميل التوقيع
                        </button>
                    </div>
                @else
                    <!-- رسالة عدم إمكانية التوقيع -->
                    <div class="signature-not-available">
                        <p style="color: #666; text-align: center; padding: 20px;">
                            <i class="fas fa-info-circle"></i>
                            التوقيع متاح بعد إرسال العقد للعميل
                        </p>
                    </div>
                @endif
            </div>

            <!-- قسم التوقيعات -->
            <div class="signature-section">
                <div class="signature-box">
                    <h4>الطرف الأول</h4>
                    <div class="signature-line">الاسم: {{ $documentCompanyName }}</div>
                    <div class="signature-line">التوقيع: ........................</div>
                    <div class="signature-line">التاريخ: {{ \Carbon\Carbon::now()->format('Y/m/d') }}</div>
                </div>

                <div class="signature-box">
                    <h4>الطرف الثاني</h4>
                    <div class="signature-line">الاسم: {{ $contract->user->name ?? '' }}</div>
                    <div class="signature-line">
                        التوقيع:
                        @if($contract->signature)
                            <img src="{{ asset('storage/signatures/' . $contract->signature) }}" style="max-width: 150px; max-height: 60px; vertical-align: middle;">
                        @else
                            ........................
                        @endif
                    </div>
                    <div class="signature-line">التاريخ: {{ \Carbon\Carbon::now()->format('Y/m/d') }}</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // متغيرات التوقيع
        let canvas = document.getElementById('signatureCanvas');
        let ctx = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // إعداد Canvas
        function setupCanvas() {
            if (!canvas) return;

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
            isDrawing = true;
            [lastX, lastY] = [e.offsetX, e.offsetY];
        }

        // الرسم
        function draw(e) {
            if (!isDrawing) return;

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
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            setupCanvas();
        }

        // حفظ التوقيع
        function saveSignature() {
            // التحقق من وجود توقيع
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const hasSignature = imageData.data.some(channel => channel !== 0);

            if (!hasSignature) {
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
