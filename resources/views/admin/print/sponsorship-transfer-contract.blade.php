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
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #333;
        }

        .header .company-info {
            font-size: 11pt;
            color: #666;
            margin: 5px 0;
        }

        .contract-parties {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }

        .party {
            margin-bottom: 15px;
        }

        .party-title {
            font-weight: bold;
            font-size: 13pt;
            color: #333;
            margin-bottom: 8px;
        }

        .party-info {
            font-size: 11pt;
            line-height: 1.4;
        }

        .contract-details {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
        }

        .contract-details h3 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #333;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11pt;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .terms-section {
            margin: 20px 0;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
        }

        .term-content {
            font-size: 12pt;
            line-height: 1.6;
            margin-bottom: 12px;
            text-align: justify;
        }

        .term-number {
            font-weight: bold;
            color: #333;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #333;
        }

        .signature-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
        }

        .signature-info {
            font-size: 10pt;
            color: #666;
        }

        .print-controls {
            margin: 20px 0;
            text-align: center;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12pt;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .variable {
            background-color: #ffffcc;
            padding: 2px 4px;
            border: 1px solid #ffeb3b;
            border-radius: 3px;
            font-weight: bold;
        }

        .signature-controls {
            text-align: center;
            margin: 10px 0;
        }

        .saved-signature {
            text-align: center;
        }

        .print-only {
            display: none;
        }

        @media print {
            .print-only {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="contract-container">
        <!-- أزرار التحكم -->
        <div class="print-controls no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة
            </button>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="fas fa-times"></i> إغلاق
            </button>
        </div>

        <!-- رأس العقد -->
        <div class="header">
            <h1>عقد نقل كفالة العمالة المنزلية</h1>
            <div class="company-info">
                {{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}
            </div>
            <div class="company-info">ترخيص رقم: {{ $contractSettings['license_number'] ?? '1234567890' }}</div>
            <div class="company-info">{{ $contractSettings['address'] ?? 'الرياض، المملكة العربية السعودية' }}</div>
            <div class="company-info">هاتف: {{ $contractSettings['phone'] ?? '+966 11 123 4567' }} | البريد الإلكتروني:
                {{ $contractSettings['email'] ?? 'info@ingaz.com' }}</div>
        </div>

        <!-- أطراف العقد -->
        <div class="contract-parties">
            <div class="party">
                <div class="party-title">الطرف الأول (مقدم الخدمة):</div>
                <div class="party-info">
                    {{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}<br>
                    ترخيص رقم: {{ $contractSettings['license_number'] ?? '1234567890' }}<br>
                    العنوان: {{ $contractSettings['address'] ?? 'الرياض، المملكة العربية السعودية' }}
                </div>
            </div>

            <div class="party">
                <div class="party-title">الطرف الثاني (العميل الجديد):</div>
                <div class="party-info">
                    الاسم: <span class="variable">{{ $transfer->newSponsor->name ?? '[اسم_العميل_الجديد]' }}</span><br>
                    رقم الهوية: <span
                        class="variable">{{ $transfer->newSponsor->identity ?? '[رقم_الهوية]' }}</span><br>
                    الهاتف: <span class="variable">{{ $transfer->newSponsor->phone ?? '[رقم_الهاتف]' }}</span><br>
                    العنوان: <span class="variable">{{ $transfer->newSponsor->address ?? '[العنوان]' }}</span>
                </div>
            </div>
        </div>

        <!-- تفاصيل العقد -->
        <div class="contract-details">
            <h3>تفاصيل العقد</h3>
            <div class="detail-row">
                <span class="detail-label">رقم طلب النقل:</span>
                <span class="detail-value">{{ $transfer->id }}</span>
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
                <span class="detail-label">رقم جواز السفر:</span>
                <span class="detail-value">{{ $transfer->contract->maid->passport_number ?? 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">العميل السابق:</span>
                <span class="detail-value">{{ $transfer->oldSponsor->name ?? 'غير محدد' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">قيمة العقد:</span>
                <span class="detail-value">{{ number_format($transfer->contract_value, 2) }} ريال سعودي</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">فترة التجربة:</span>
                <span class="detail-value">{{ $transfer->trial_days }} يوماً</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">تاريخ الإنشاء:</span>
                <span
                    class="detail-value">{{ $transfer->created_at ? $transfer->created_at->format('Y-m-d') : 'غير محدد' }}</span>
            </div>
        </div>

        <!-- بنود العقد -->
        @if (isset($contractTerms) && $contractTerms->count() > 0)
            @foreach ($contractTerms as $pageNumber => $pageTerms)
                @if ($pageNumber > 1)
                    <div class="page-break"></div>
                @endif

                <div class="terms-section">
                    @php
                        $currentSection = '';
                    @endphp

                    @foreach ($pageTerms as $term)
                        @if ($currentSection !== $term->section_title)
                            @php $currentSection = $term->section_title; @endphp
                            <div class="section-title">{{ $term->section_title }}</div>
                        @endif

                        <div class="term-content">
                            @if ($term->term_number)
                                <span class="term-number">{{ $term->term_number }}.</span>
                            @endif
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
                        </div>
                    @endforeach
                </div>
            @endforeach
        @else
            <div class="terms-section">
                <div class="section-title">بنود العقد</div>
                <div class="term-content">
                    <p><strong>ملاحظة:</strong> لم يتم تحديد بنود العقد بعد. يرجى إضافة البنود من لوحة التحكم.</p>
                </div>
            </div>
        @endif

        <!-- التوقيعات -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">الطرف الأول (الشركة)</div>
                <div class="signature-info">
                    {{ documentSetting('document_company_name', $contractSettings['company_name'] ?? 'شركة بلاتينيوم الشرقية للاستقدام') }}<br>
                    التوقيع: ___________________<br>
                    التاريخ: ___________________
                </div>
            </div>

            <div class="signature-box">
                <div class="signature-title">الطرف الثاني (العميل)</div>
                <div class="signature-info">
                    {{ $transfer->newSponsor->name ?? '[اسم_العميل]' }}<br>
                    @if ($transfer->signature)
                        <div class="saved-signature">
                            <img src="{{ asset('storage/signatures/' . $transfer->signature) }}" alt="توقيع العميل"
                                style="max-width: 200px; max-height: 80px; border: 1px solid #ccc;">
                            <br><small>تم التوقيع في:
                                {{ $transfer->contract_signed_at ? $transfer->contract_signed_at->format('Y-m-d H:i') : 'غير محدد' }}</small>
                        </div>
                    @elseif($transfer->status === 'contract_sent')
                        <div class="signature-controls no-print">
                            <canvas id="signatureCanvas" width="300" height="100"
                                style="border: 1px solid #ccc; cursor: crosshair;"></canvas>
                            <div class="mt-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="saveSignature()">
                                    <i class="fas fa-save"></i> حفظ التوقيع
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="clearSignature()">
                                    <i class="fas fa-eraser"></i> مسح
                                </button>
                            </div>
                        </div>
                        <div class="print-only">
                            التوقيع: ___________________<br>
                            التاريخ: ___________________
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // متغيرات التوقيع للعميل
        let canvas = document.getElementById('signatureCanvas');
        let ctx = canvas ? canvas.getContext('2d') : null;
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // إعداد Canvas للعميل
        function setupCanvas() {
            if (!canvas || !ctx) return;

            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';

            // إضافة نص توجيهي
            ctx.fillStyle = '#999';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('اضغط هنا للتوقيع', canvas.width / 2, canvas.height / 2);
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

        // طباعة تلقائية عند تحميل الصفحة (اختيارية)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>
