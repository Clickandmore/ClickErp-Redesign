<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة عقد نقل الكفالة - {{ $transfer->id ?? 'غير محدد' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        @media print {
            @page {
                size: A4;
                margin: 15mm;
            }

            .no-print {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page-break {
                page-break-before: always;
            }

            .signature-controls {
                display: none !important;
            }
        }

        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            font-size: 12.5pt;
            line-height: 1.5;
            color: #000;
            background: white;
            margin: 0;
            padding: 10px;
        }

        .contract-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            position: relative;
        }

        .header h1 {
            font-size: 22pt;
            font-weight: bold;
            margin: 8px 0;
        }

        .logo-container {
            position: absolute;
            top: 0;
            right: 20px;
            width: 100px;
            height: 100px;
        }

        .logo-container img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.05;
            z-index: -1;
            width: 400px;
            height: 400px;
        }

        @media print {
            .watermark {
                opacity: 0.03;
            }
        }

        .contract-info {
            margin-bottom: 20px;
        }

        .contract-body {
            text-align: justify;
            margin: 20px 0;
            line-height: 1.8;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0 10px 0;
            color: #333;
        }

        .term-content {
            margin-bottom: 15px;
            text-indent: 20px;
        }

        .signatures-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signature-container {
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .signature-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }

        .signature-status {
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .signature-status.signed {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .signature-status.not-signed {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .signature-display {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            background-color: #f8f9ff;
        }

        .signature-display img {
            max-width: 300px;
            max-height: 120px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: white;
            padding: 5px;
        }

        .signature-area {
            margin: 20px 0;
            text-align: center;
        }

        .signature-canvas {
            border: 2px solid #007bff;
            border-radius: 8px;
            cursor: crosshair;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .signature-buttons {
            margin-top: 15px;
        }

        .signature-buttons button {
            margin: 0 5px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save {
            background-color: #28a745;
            color: white;
        }

        .btn-save:hover {
            background-color: #218838;
        }

        .btn-clear {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-clear:hover {
            background-color: #e0a800;
        }

        .btn-print {
            background-color: #007bff;
            color: white;
        }

        .btn-print:hover {
            background-color: #0056b3;
        }

        .contract-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        .print-controls {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .variable {
            background-color: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            border: 1px solid #ffeaa7;
        }

        .terms-section {
            margin: 25px 0;
        }

        .term-item {
            margin-bottom: 12px;
            padding-left: 15px;
        }

        .term-number {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>

<body>
    <!-- أزرار التحكم -->
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> طباعة
        </button>
        <button class="btn-clear" onclick="window.close()" style="margin-right: 10px;">
            <i class="fas fa-times"></i> إغلاق
        </button>
    </div>

    <!-- العلامة المائية -->
    <div class="watermark">
        <img src="{{ documentLogoUrl(asset('admin/assets/img/company_logo.png')) }}" alt="Logo"
            style="width: 100%; height: 100%; opacity: 0.1;">
    </div>

    <div class="contract-container">
        <!-- رأس العقد -->
        <div class="header">
            <div class="logo-container">
                <img src="{{ documentLogoUrl(asset('admin/assets/img/company_logo.png')) }}" alt="شعار الشركة">
            </div>
            <h1>عقد نقل كفالة العمالة المنزلية</h1>
            <p style="font-size: 14pt; margin: 5px 0;">
                {{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}
            </p>
            <p style="font-size: 12pt; color: #666;">ترخيص رقم:
                {{ $contractSettings['license_number'] ?? '1234567890' }}</p>
            <p style="font-size: 11pt; color: #888;">
                {{ $contractSettings['address'] ?? 'الرياض، المملكة العربية السعودية' }}</p>
        </div>

        <!-- تفاصيل العقد -->
        <div class="contract-details">
            <h3 style="margin-top: 0; color: #007bff;">تفاصيل طلب نقل الكفالة</h3>
            <div class="detail-row">
                <span class="detail-label">رقم طلب النقل:</span>
                <span class="detail-value">#{{ $transfer->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">تاريخ الطلب:</span>
                <span
                    class="detail-value">{{ $transfer->created_at ? $transfer->created_at->format('Y-m-d') : 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">اسم العاملة:</span>
                <span class="detail-value">{{ $transfer->contract->maid->name ?? 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الجنسية:</span>
                <span class="detail-value">{{ $transfer->contract->maid->nationality->name ?? 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">العميل الجديد:</span>
                <span class="detail-value">{{ $transfer->newSponsor->name ?? 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">قيمة العقد:</span>
                <span class="detail-value">{{ number_format($transfer->contract_value, 2) }} ريال سعودي</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">فترة التجربة:</span>
                <span class="detail-value">{{ $transfer->trial_days }} يوماً</span>
            </div>
        </div>

        <!-- بنود العقد -->
        @if (isset($contractTerms) && $contractTerms->count() > 0)
            @foreach ($contractTerms as $pageNumber => $pageTerms)
                @if ($pageNumber > 1)
                    <div class="page-break"></div>
                @endif

                <div class="terms-section">
                    @php $currentSection = ''; @endphp

                    @foreach ($pageTerms as $term)
                        @if ($currentSection !== $term->section_title)
                            @php $currentSection = $term->section_title; @endphp
                            <div class="section-title">{{ $term->section_title }}</div>
                        @endif

                        <div class="term-item">
                            @if ($term->term_number)
                                <span class="term-number">{{ $term->term_number }}.</span>
                            @endif
                            <span class="term-content">
                                {!! str_replace(
                                    [
                                        '[اسم_العاملة]',
                                        '[جنسية_العاملة]',
                                        '[رقم_الهوية]',
                                        '[رقم_الجواز]',
                                        '[المهنة]',
                                        '[مدة_العقد]',
                                        '[تاريخ_البداية]',
                                        '[فترة_التجربة]',
                                        '[قيمة_العقد]',
                                        '[طريقة_الدفع]',
                                        '[تاريخ_التحرير]',
                                    ],
                                    [
                                        '<span class="variable">' . ($transfer->contract->maid->name ?? 'غير محدد') . '</span>',
                                        '<span class="variable">' . ($transfer->contract->maid->nationality->name ?? 'غير محدد') . '</span>',
                                        '<span class="variable">' . ($transfer->newSponsor->identity ?? 'غير محدد') . '</span>',
                                        '<span class="variable">' . ($transfer->contract->maid->passport_number ?? 'غير محدد') . '</span>',
                                        '<span class="variable">عاملة منزلية</span>',
                                        '<span class="variable">' . ($transfer->contract->contract_duration ?? 'سنتان') . '</span>',
                                        '<span class="variable">' .
                                        ($transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'يحدد لاحقاً') .
                                        '</span>',
                                        '<span class="variable">' . $transfer->trial_days . ' يوماً</span>',
                                        '<span class="variable">' . number_format($transfer->contract_value, 2) . ' ريال سعودي</span>',
                                        '<span class="variable">نقداً عند التسليم</span>',
                                        '<span class="variable">' .
                                        ($transfer->created_at ? $transfer->created_at->format('Y-m-d') : date('Y-m-d')) .
                                        '</span>',
                                    ],
                                    $term->term_content,
                                ) !!}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="terms-section">
                <div class="section-title">بنود العقد</div>
                <div class="term-item">
                    <p><strong>ملاحظة:</strong> لم يتم تحديد بنود العقد بعد. يرجى إضافة البنود من لوحة التحكم.</p>
                </div>
            </div>
        @endif

        <!-- قسم التوقيعات مع ميزة التوقيع -->
        <div class="signatures-section">
            <div style="margin-top: 40px;">
                <div style="font-weight: bold; margin-bottom: 20px;">أطراف العقد:</div>
                <div>الطرف الأول:
                    {{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}
                </div>
                <div>الطرف الثاني: {{ $transfer->newSponsor->name ?? '[اسم_العميل]' }}</div>

                <!-- قسم التوقيع -->
                <div class="signature-container no-print">
                    <div class="signature-title">توقيع الطرف الثاني ({{ $transfer->newSponsor->name ?? 'العميل' }})
                    </div>

                    <!-- حالة التوقيع -->
                    <div class="signature-status {{ $transfer->signature ? 'signed' : 'not-signed' }}">
                        @if ($transfer->signature)
                            ✅ تم التوقيع على العقد
                        @else
                            ❌ لم يتم التوقيع بعد
                        @endif
                    </div>

                    <!-- عرض التوقيع المحفوظ -->
                    @if ($transfer->signature)
                        <div class="signature-display">
                            <img src="{{ asset('storage/signatures/' . $transfer->signature) }}" alt="التوقيع المحفوظ">
                            <div style="margin-top: 10px; font-size: 12pt; color: #666;">
                                تاريخ التوقيع:
                                {{ $transfer->contract_signed_at ? $transfer->contract_signed_at->format('Y-m-d H:i') : 'غير محدد' }}
                            </div>
                        </div>
                    @elseif($transfer->status === 'contract_sent')
                        <!-- منطقة التوقيع -->
                        <div class="signature-area">
                            <div style="margin-bottom: 15px; color: #007bff; font-weight: bold;">
                                يرجى التوقيع في المنطقة أدناه:
                            </div>
                            <canvas id="signatureCanvas" class="signature-canvas" width="400"
                                height="150"></canvas>
                            <div class="signature-buttons">
                                <button class="btn-save" onclick="saveSignature()">
                                    <i class="fas fa-save"></i> حفظ التوقيع
                                </button>
                                <button class="btn-clear" onclick="clearSignature()">
                                    <i class="fas fa-eraser"></i> مسح التوقيع
                                </button>
                            </div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 30px; color: #666;">
                            <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
                            <div>التوقيع متاح فقط عندما يكون العقد في حالة "تم إرسال العقد"</div>
                        </div>
                    @endif
                </div>

                <!-- التوقيعات للطباعة -->
                <div style="margin-top: 50px; display: flex; justify-content: space-between;">
                    <div style="text-align: center; width: 45%;">
                        <div style="border-top: 1px solid #000; padding-top: 10px; margin-top: 50px;">
                            <strong>{{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}</strong><br>
                            الطرف الأول
                        </div>
                    </div>
                    <div style="text-align: center; width: 45%;">
                        @if ($transfer->signature)
                            <div style="margin-bottom: 10px;">
                                <img src="{{ asset('storage/signatures/' . $transfer->signature) }}" alt="التوقيع"
                                    style="max-height: 60px; border: 1px solid #ccc;">
                            </div>
                        @endif
                        <div style="border-top: 1px solid #000; padding-top: 10px; margin-top: 50px;">
                            <strong>{{ $transfer->newSponsor->name ?? '[اسم_العميل]' }}</strong><br>
                            الطرف الثاني
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // متغيرات التوقيع
        let canvas = document.getElementById('signatureCanvas');
        let ctx = canvas ? canvas.getContext('2d') : null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // إعداد Canvas
        function setupCanvas() {
            if (!canvas || !ctx) return;

            ctx.strokeStyle = '#000';
            ctx.lineWidth = 3;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // إضافة نص توجيهي
            ctx.fillStyle = '#ccc';
            ctx.font = '16px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('وقع هنا بالماوس أو اللمس', canvas.width / 2, canvas.height / 2);
        }

        // بدء الرسم
        function startDrawing(e) {
            if (!canvas) return;
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        }

        // الرسم
        function draw(e) {
            if (!isDrawing || !canvas || !ctx) return;

            const rect = canvas.getBoundingClientRect();
            const currentX = e.clientX - rect.left;
            const currentY = e.clientY - rect.top;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(currentX, currentY);
            ctx.stroke();

            lastX = currentX;
            lastY = currentY;
        }

        // إيقاف الرسم
        function stopDrawing() {
            isDrawing = false;
        }

        // مسح التوقيع
        function clearSignature() {
            if (!canvas || !ctx) return;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            setupCanvas();
        }

        // حفظ التوقيع
        function saveSignature() {
            if (!canvas || !ctx) return;

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

            // إرسال التوقيع للخادم
            Swal.fire({
                title: 'حفظ التوقيع',
                text: 'جاري حفظ التوقيع وتحديث حالة العقد...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('{{ route('sponsorship-transfer.save-signature', $transfer->id) }}', {
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
                    if (data.success) {
                        Swal.fire({
                            title: 'تم الحفظ بنجاح!',
                            text: 'تم حفظ التوقيع وتحديث حالة العقد إلى "تم توقيع العقد"',
                            icon: 'success',
                            confirmButtonText: 'حسناً'
                        }).then(() => {
                            location.reload();
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
                        text: 'حدث خطأ في الاتصال بالخادم',
                        icon: 'error',
                        confirmButtonText: 'حسناً'
                    });
                });
        }

        // تهيئة Canvas عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            if (canvas) {
                setupCanvas();

                // أحداث الماوس
                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseout', stopDrawing);

                // أحداث اللمس للأجهزة المحمولة
                canvas.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const mouseEvent = new MouseEvent('mousedown', {
                        clientX: touch.clientX,
                        clientY: touch.clientY
                    });
                    canvas.dispatchEvent(mouseEvent);
                });

                canvas.addEventListener('touchmove', function(e) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    const mouseEvent = new MouseEvent('mousemove', {
                        clientX: touch.clientX,
                        clientY: touch.clientY
                    });
                    canvas.dispatchEvent(mouseEvent);
                });

                canvas.addEventListener('touchend', function(e) {
                    e.preventDefault();
                    const mouseEvent = new MouseEvent('mouseup', {});
                    canvas.dispatchEvent(mouseEvent);
                });
            }
        });
    </script>
</body>

</html>
