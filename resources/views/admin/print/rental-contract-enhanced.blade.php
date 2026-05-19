<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>عقد إيجار رقم {{ $contract->contract_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            background: #f8f9fa;
        }
        .contract-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .contract-number {
            font-size: 18px;
            margin-top: 10px;
            opacity: 0.9;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background: #f8f9fa;
            padding: 15px;
            border-right: 4px solid #667eea;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            font-size: 15px;
        }
        .terms-list {
            list-style: none;
            padding: 0;
        }
        .terms-list li {
            background: #f8f9fa;
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #667eea;
        }
        .term-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .term-content {
            color: #6c757d;
            line-height: 1.8;
        }
        .signature-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }
        .signature-box {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            margin: 20px 0;
            min-height: 200px;
            position: relative;
        }
        .signature-controls {
            margin-bottom: 20px;
        }
        .signature-controls button {
            margin: 0 10px;
        }
        #signaturePad {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: white;
            cursor: crosshair;
        }
        .signature-saved {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .print-hide {
            display: none;
        }
        @media print {
            body { background: white; }
            .contract-container {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }
            .signature-controls { display: none !important; }
            .print-hide { display: none !important; }
            .no-print { display: none !important; }
        }
        @media screen {
            .print-only { display: none; }
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-waiting { background: #fff3cd; color: #856404; }
        .status-sent { background: #d1ecf1; color: #0c5460; }
        .status-signed { background: #d4edda; color: #155724; }
        .status-delivered { background: #cce7ff; color: #004085; }
        .status-trial { background: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="contract-container">
        <!-- Header -->
        <div class="header">
            <h1>عقد إيجار خدمات منزلية</h1>
            <div class="contract-number">رقم العقد: {{ $contract->contract_number }}</div>
            <div class="mt-2">
                <span class="status-badge status-{{ str_replace('_', '-', $contract->status) }}">
                    {{ $contract->status_text }}
                </span>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- معلومات الأطراف -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-users me-2"></i>
                    معلومات أطراف العقد
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">الطرف الأول (صاحب العمل)</div>
                        <div class="info-value">{{ $contract->user->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">رقم الهوية</div>
                        <div class="info-value">{{ $contract->user->national_num ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">رقم الجوال</div>
                        <div class="info-value">{{ $contract->user->phone_code ?? '' }}{{ $contract->user->phone ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">العنوان</div>
                        <div class="info-value">{{ $contract->user->address ?? 'غير محدد' }}</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">الطرف الثاني (العاملة)</div>
                        <div class="info-value">{{ $contract->maid->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">رقم الجواز</div>
                        <div class="info-value">{{ $contract->maid->passport_num ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">الجنسية</div>
                        <div class="info-value">{{ $contract->maid->nationality->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">العمر</div>
                        <div class="info-value">{{ $contract->maid->age ?? 'غير محدد' }} سنة</div>
                    </div>
                </div>
            </div>

            <!-- تفاصيل العقد -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-file-contract me-2"></i>
                    تفاصيل العقد
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">تاريخ العقد</div>
                        <div class="info-value">{{ $contract->contract_date ? $contract->contract_date->format('Y-m-d') : 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">مدة العقد</div>
                        <div class="info-value">{{ $contract->info?->package?->duration_label ?? (($contract->info->contract_duration_days ?? null) ? $contract->info->contract_duration_days . ' يوم' : (($contract->info->contract_duration_months ?? 'غير محدد') . ' شهر')) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">التكلفة الشهرية</div>
                        <div class="info-value">{{ number_format($contract->info->monthly_cost ?? 0, 2) }} ريال</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">قيمة العقد الإجمالية</div>
                        <div class="info-value">{{ number_format($contract->info->contract_value ?? 0, 2) }} ريال</div>
                    </div>
                </div>

                @if($contract->info)
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ساعات العمل اليومية</div>
                        <div class="info-value">{{ $contract->info->working_hours_per_day ?? 8 }} ساعة</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">الراتب الشهري للعاملة</div>
                        <div class="info-value">{{ number_format($contract->info->monthly_salary ?? 0, 2) }} ريال</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">من تاريخ</div>
                        <div class="info-value">{{ $contract->info->ingaz_from ? $contract->info->ingaz_from->format('Y-m-d') : 'غير محدد' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">إلى تاريخ</div>
                        <div class="info-value">{{ $contract->info->ingaz_to ? $contract->info->ingaz_to->format('Y-m-d') : 'غير محدد' }}</div>
                    </div>
                </div>
                @endif
            </div>

            <!-- بنود العقد -->
            @if($contractTerms && $contractTerms->count() > 0)
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-list-alt me-2"></i>
                    بنود وشروط العقد
                </div>
                <ul class="terms-list">
                    @foreach($contractTerms as $term)
                    <li>
                        <div class="term-title">{{ $term->title }}</div>
                        <div class="term-content">{{ $term->content }}</div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- قسم التوقيع -->
            @if($contract->status === 'contract_sent' || $contract->status === 'contract_signed')
            <div class="signature-section">
                <div class="section-title">
                    <i class="fas fa-signature me-2"></i>
                    توقيع العميل
                </div>

                @if(!$contract->signature)
                <div class="signature-controls no-print">
                    <button type="button" class="btn btn-primary" onclick="startSigning()">
                        <i class="fas fa-pen"></i> بدء التوقيع
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearSignature()" style="display:none;" id="clearBtn">
                        <i class="fas fa-eraser"></i> مسح
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveSignature()" style="display:none;" id="saveBtn">
                        <i class="fas fa-save"></i> حفظ التوقيع
                    </button>
                </div>

                <div class="signature-box" id="signatureBox">
                    <canvas id="signaturePad" width="400" height="150" style="display:none;"></canvas>
                    <div id="signaturePrompt">
                        <i class="fas fa-signature fa-3x text-muted mb-3"></i>
                        <p class="text-muted">انقر على "بدء التوقيع" للتوقيع على العقد</p>
                    </div>
                </div>
                @else
                <div class="signature-box">
                    <img src="{{ $contract->signature }}" alt="توقيع العميل" class="signature-saved">
                    <p class="mt-2 text-success">
                        <i class="fas fa-check-circle"></i> تم توقيع العقد
                    </p>
                </div>
                @endif
            </div>
            @endif

            <!-- معلومات إضافية -->
            @if($contract->info && $contract->info->notes)
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-sticky-note me-2"></i>
                    ملاحظات إضافية
                </div>
                <div class="info-item">
                    <div class="info-value">{{ $contract->info->notes }}</div>
                </div>
            </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="mb-2"><strong>شركة الخدمات المنزلية</strong></p>
            <p class="mb-0 text-muted">تاريخ الطباعة: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let canvas, ctx, isDrawing = false;

        function startSigning() {
            document.getElementById('signaturePrompt').style.display = 'none';
            document.getElementById('signaturePad').style.display = 'block';
            document.getElementById('clearBtn').style.display = 'inline-block';
            document.getElementById('saveBtn').style.display = 'inline-block';

            canvas = document.getElementById('signaturePad');
            ctx = canvas.getContext('2d');
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';

            // Mouse events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);

            // Touch events
            canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
            canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
            canvas.addEventListener('touchend', stopDrawing);
        }

        function startDrawing(e) {
            isDrawing = true;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function draw(e) {
            if (!isDrawing) return;
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            ctx.lineTo(x, y);
            ctx.stroke();
        }

        function stopDrawing() {
            isDrawing = false;
        }

        function handleTouchStart(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }

        function handleTouchMove(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            canvas.dispatchEvent(mouseEvent);
        }

        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        function saveSignature() {
            const signatureData = canvas.toDataURL();

            fetch(`{{ route('RentContract.saveSignature', $contract->id) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    signature: signatureData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'تم الحفظ!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'موافق'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('خطأ!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('خطأ!', 'حدث خطأ أثناء حفظ التوقيع', 'error');
            });
        }

        // Print function
        function printContract() {
            window.print();
        }
    </script>
</body>
</html>
