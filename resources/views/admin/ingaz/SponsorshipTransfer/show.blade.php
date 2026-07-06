@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/custom/status-badges.css">
    <style>
        /* --- إعدادات الخطوط والألوان الأساسية --- */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #06d6a0;
            --warning-color: #f72585;
            --info-color: #4895ef;
            --text-dark: #2b2d42;
            --text-light: #8d99ae;
            --bg-light: #f8f9fa;
            --card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
            --border-radius: 16px;
        }

        /* استيراد خط عربي جميل (Tajawal) */
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');

        .details-container {
            font-family: 'Tajawal', sans-serif;
            direction: rtl;
            background-color: #f4f7fc;
            padding: 30px;
            min-height: 100vh;
        }

        /* --- الهيدر والأزرار --- */
        .details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #fff;
            padding: 20px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .details-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
        }

        .details-header h3 i {
            color: var(--primary-color);
            background: rgba(67, 97, 238, 0.1);
            padding: 10px;
            border-radius: 12px;
            margin-left: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: flex-end;
        }

        .btn-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.2rem;
        }

        .btn-back {
            background: #e9ecef;
            color: var(--text-dark);
        }

        .btn-edit {
            background: rgba(72, 149, 239, 0.15);
            color: var(--info-color);
        }

        .btn-print {
            background: rgba(6, 214, 160, 0.15);
            color: var(--success-color);
        }

        .btn-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* --- البطاقات (Cards) --- */
        .info-card {
            background: #fff;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        /* تلوين الحدود العلوية للتمييز البصري */
        .contract-info {
            border-top: 4px solid var(--primary-color);
        }

        .financial-info {
            border-top: 4px solid var(--success-color);
        }

        .sponsor-info {
            border-top: 4px solid var(--text-light);
        }

        .client-info {
            border-top: 4px solid var(--secondary-color);
        }

        .maid-info {
            border-top: 4px solid var(--warning-color);
        }

        .history-info {
            border-top: 4px solid var(--text-dark);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e0e0e0;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-left: 10px;
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* --- صفوف البيانات --- */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-light);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .info-value {
            color: var(--text-dark);
            font-weight: 600;
        }

        .info-value.highlight {
            color: var(--primary-color);
            font-weight: 800;
        }

        .info-value.success {
            color: var(--success-color);
        }

        .info-value.warning {
            color: #e67e22;
        }

        .info-value.danger {
            color: var(--danger-color);
        }

        /* --- الشارات (Badges) --- */
        .details-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-block;
        }

        /* حالات الطلب - قم بتعديل الألوان حسب الكلاسات القادمة من الباك إند */
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .bg-success {
            background: rgba(6, 214, 160, 0.15);
            color: var(--success-color);
        }

        .bg-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #d35400;
        }

        .bg-danger {
            background: rgba(239, 35, 60, 0.15);
            color: #d90429;
        }

        /* --- الخط الزمني (Timeline) --- */
        .timeline {
            position: relative;
            padding-right: 30px;
            border-right: 2px solid #e9ecef;
            margin-top: 20px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-marker {
            position: absolute;
            right: -36px;
            /* تعديل الموضع بالنسبة للخط */
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #e9ecef;
        }

        .timeline-marker.success {
            background: var(--success-color);
            box-shadow: 0 0 0 2px rgba(6, 214, 160, 0.3);
        }

        .timeline-marker.info {
            background: var(--info-color);
            box-shadow: 0 0 0 2px rgba(72, 149, 239, 0.3);
        }

        .timeline-marker.danger {
            background: var(--warning-color);
            box-shadow: 0 0 0 2px rgba(247, 37, 133, 0.3);
        }

        .timeline-content {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            position: relative;
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            right: -8px;
            top: 10px;
            width: 15px;
            height: 15px;
            background: #f8f9fa;
            transform: rotate(45deg);
        }

        .timeline-title {
            margin: 0 0 5px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .timeline-text {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* --- تنسيقات الطباعة (Print) --- */
        @media print {

            .action-buttons,
            .btn-back,
            .btn-edit {
                display: none !important;
            }

            .details-container {
                padding: 0;
                background: #fff;
            }

            .info-card {
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }

            .col-lg-6 {
                width: 50% !important;
                float: right;
            }

            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
@endsection

@section('title')
    تفاصيل طلب نقل الكفالة
@endsection

@section('content')
    <div class="details-container">
        <!-- Header مع معلومات أساسية -->
        <div class="details-header">
            <h3>
                <i class="fas fa-exchange-alt me-2"></i>
                تفاصيل طلب نقل الكفالة رقم: {{ $transfer->id }}
            </h3>
            <div class="status-info">
                <span class="{{ $transfer->status_badge }}">{{ $transfer->status_text }}</span>
            </div>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="action-buttons">
            <a href="{{ route('SponsorshipTransfer.index') }}" class="btn-icon btn-back" title="العودة">
                <i class="fas fa-arrow-right"></i>
            </a>
            @if ($transfer->canEditDetails() && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT))
                <a href="{{ route('SponsorshipTransfer.edit', $transfer->id) }}" class="btn-icon btn-edit" title="تحرير">
                    <i class="fas fa-edit"></i>
                </a>
            @endif
            {{-- <button onclick="window.print()" class="btn-icon btn-print" title="طباعة">
                <i class="fas fa-print"></i>
            </button> --}}
        </div>

        <div class="row">
            <!-- معلومات الطلب الأساسية -->
            <div class="col-lg-6 col-md-12">
                <div class="info-card contract-info">
                    <div class="card-title">
                        <i class="fas fa-info-circle"></i>معلومات الطلب
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم الطلب:</span>
                        <span class="info-value highlight">#{{ $transfer->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الإنشاء:</span>
                        <span class="info-value">{{ $transfer->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">الحالة:</span>
                        <span class="info-value">
                            <span class="details-badge {{ $transfer->status_badge }}">{{ $transfer->status_text }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">رقم العقد:</span>
                        <span class="info-value">
                            @if ($transfer->contract)
                                <a href="{{ route('RecruitmentContract.show', $transfer->contract->id) }}"
                                    class="info-value highlight">
                                    عقد رقم: {{ $transfer->contract->id }}
                                </a>
                            @else
                                <span class="info-value warning">غير محدد</span>
                            @endif
                        </span>
                    </div>
                    @if ($transfer->notes)
                        <div class="info-row">
                            <span class="info-label">ملاحظات:</span>
                            <span class="info-value">{{ $transfer->notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- المعلومات المالية -->
            <div class="col-lg-6 col-md-12">
                <div class="info-card financial-info">
                    <div class="card-title">
                        <i class="fas fa-money-bill-wave"></i>المعلومات المالية
                    </div>
                    <div class="info-row">
                        <span class="info-label">قيمة العقد:</span>
                        <span class="info-value info">{{ number_format($transfer->contract_value, 2) }} ريال</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المبلغ المدفوع:</span>
                        <span class="info-value success">{{ number_format($transfer->paid_amount, 2) }} ريال</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المبلغ المتبقي:</span>
                        <span class="info-value warning">{{ number_format($transfer->remaining_amount, 2) }} ريال</span>
                    </div>
                    @if ($transfer->status === 'failed')
                        <div class="info-row">
                            <span class="info-label">نوع التسوية:</span>
                            <span class="info-value">{{ $transfer->failure_settlement_type_text }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">المستحق للعميل:</span>
                            <span class="info-value danger">{{ number_format((float) $transfer->failure_settlement_amount, 2) }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مبلغ تصفير المتبقي:</span>
                            <span class="info-value">{{ number_format((float) $transfer->failure_clear_remaining_amount, 2) }} ريال</span>
                        </div>
                        @if ($transfer->failure_client_iban)
                            <div class="info-row">
                                <span class="info-label">آيبان العميل:</span>
                                <span class="info-value" dir="ltr">{{ $transfer->failure_client_iban }}</span>
                            </div>
                        @endif
                        @if ($transfer->failure_client_iban_attachment_path)
                            <div class="info-row">
                                <span class="info-label">مرفق الآيبان:</span>
                                <span class="info-value">
                                    <a href="{{ Storage::disk('public')->url($transfer->failure_client_iban_attachment_path) }}" target="_blank" class="text-primary">
                                        عرض المرفق
                                    </a>
                                </span>
                            </div>
                        @endif
                    @endif
                    <div class="info-row">
                        <span class="info-label">طريقة الدفع:</span>
                        <span class="info-value">{{ $transfer->payment_type_text }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">حالة الدفع:</span>
                        <span class="info-value">{{ $transfer->payment_status_text }}</span>
                    </div>
                    @if ($transfer->payment_receipt_path)
                        <div class="info-row">
                            <span class="info-label">إيصال الدفع:</span>
                            <span class="info-value">
                                <a href="{{ Storage::disk('public')->url($transfer->payment_receipt_path) }}" target="_blank" class="text-primary">
                                    عرض الإيصال
                                </a>
                            </span>
                        </div>
                    @endif
                    @if (!empty($transfer->attachments))
                        <div class="info-row">
                            <span class="info-label">المرفقات:</span>
                            <span class="info-value">
                                @foreach ($transfer->attachments as $attachment)
                                    <a href="{{ Storage::disk('public')->url($attachment['path'] ?? '') }}" target="_blank" class="d-block text-primary">
                                        {{ $attachment['name'] ?? 'مرفق' }}
                                    </a>
                                @endforeach
                            </span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">نسبة السداد:</span>
                        <span class="info-value">
                            @php
                                $percentage =
                                    $transfer->contract_value > 0
                                        ? ($transfer->paid_amount / $transfer->contract_value) * 100
                                        : 0;
                            @endphp
                            <span
                                class="details-badge {{ $percentage >= 100 ? 'text-bg-success' : ($percentage >= 50 ? 'text-bg-warning' : 'text-bg-danger') }}">
                                {{ number_format($percentage, 1) }}%
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- معلومات الكفيل القديم -->
            <div class="col-lg-6 col-md-12">
                <div class="info-card sponsor-info">
                    <div class="card-title">
                        <i class="fas fa-user"></i>الكفيل القديم
                    </div>
                    @if ($transfer->oldSponsor)
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value">{{ $transfer->oldSponsor->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ $transfer->oldSponsor?->documentNumberLabel() ?? 'رقم الهوية' }}:</span>
                            <span class="info-value">{{ $transfer->oldSponsor?->documentNumber() ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الجوال:</span>
                            <span class="info-value">{{ $transfer->oldSponsor->phone }}</span>
                        </div>
                        @if ($transfer->oldSponsor->email)
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $transfer->oldSponsor->email }}</span>
                            </div>
                        @endif
                        @if ($transfer->oldSponsor->city)
                            <div class="info-row">
                                <span class="info-label">المدينة:</span>
                                <span class="info-value">{{ $transfer->oldSponsor->city }}</span>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>معلومات الكفيل القديم غير متاحة
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات الكفيل الجديد -->
            <div class="col-lg-6 col-md-12">
                <div class="info-card client-info">
                    <div class="card-title">
                        <i class="fas fa-user-plus"></i>الكفيل الجديد
                    </div>
                    @if ($transfer->newSponsor)
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value">{{ $transfer->newSponsor->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ $transfer->newSponsor?->documentNumberLabel() ?? 'رقم الهوية' }}:</span>
                            <span class="info-value">{{ $transfer->newSponsor?->documentNumber() ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الجوال:</span>
                            <span class="info-value">{{ $transfer->newSponsor->phone }}</span>
                        </div>
                        @if ($transfer->newSponsor->email)
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $transfer->newSponsor->email }}</span>
                            </div>
                        @endif
                        @if ($transfer->newSponsor->city)
                            <div class="info-row">
                                <span class="info-label">المدينة:</span>
                                <span class="info-value">{{ $transfer->newSponsor->city }}</span>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>معلومات الكفيل الجديد غير متاحة
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات العاملة -->
            @if ($transfer->contract && $transfer->contract->maid)
                <div class="col-lg-6 col-md-12">
                    <div class="info-card maid-info">
                        <div class="card-title">
                            <i class="fas fa-female"></i>معلومات العاملة
                        </div>
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value">{{ $transfer->contract->maid->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الجواز:</span>
                            <span class="info-value">{{ $transfer->contract->maid->passport_num ?? 'غير محدد' }}</span>
                        </div>
                        @if ($transfer->contract->maid->nationality)
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span class="info-value">{{ $transfer->contract->maid->nationality->title }}</span>
                            </div>
                        @endif
                        @if ($transfer->contract->maid->age)
                            <div class="info-row">
                                <span class="info-label">العمر:</span>
                                <span class="info-value">{{ $transfer->contract->maid->age }} سنة</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- معلومات فترة التجربة -->
            <div class="col-lg-6 col-md-12">
                <div class="info-card trial-info">
                    <div class="card-title">
                        <i class="fas fa-clock"></i>معلومات فترة التجربة
                    </div>
                    <div class="info-row">
                        <span class="info-label">مدة التجربة:</span>
                        <span class="info-value">{{ $transfer->trial_days }} يوم</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ بداية التجربة:</span>
                        <span class="info-value">
                            {{ $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'لم تبدأ بعد' }}
                        </span>
                    </div>
                    @if ($transfer->trial_start_date && $transfer->trial_days)
                        <div class="info-row">
                            <span class="info-label">تاريخ انتهاء التجربة:</span>
                            <span class="info-value">
                                @php
                                    $endDate = $transfer->trial_start_date->addDays($transfer->trial_days);
                                    $now = now();
                                    $daysRemaining = $now->diffInDays($endDate, false);
                                @endphp
                                {{ $endDate->format('Y-m-d') }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">حالة التجربة:</span>
                            <span class="info-value">
                                @if ($daysRemaining > 0)
                                    <span class="details-badge text-bg-success">متبقي {{ $daysRemaining }} يوم</span>
                                @elseif($daysRemaining == 0)
                                    <span class="details-badge text-bg-warning">تنتهي اليوم</span>
                                @else
                                    <span class="details-badge text-bg-danger">انتهت منذ {{ abs($daysRemaining) }} يوم</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- سجل الإجراءات -->
            @if ($transfer->approved_by || $transfer->transferred_by || $transfer->failed_by)
                <div class="col-12">
                    <div class="info-card history-info">
                        <div class="card-title">
                            <i class="fas fa-history"></i>سجل الإجراءات
                        </div>
                        <div class="timeline">
                            @if ($transfer->approved_by)
                                <div class="timeline-item">
                                    <div class="timeline-marker success"></div>
                                    <div class="timeline-content success">
                                        <h6 class="timeline-title">تمت الموافقة على الطلب</h6>
                                        <p class="timeline-text">
                                            <strong>بواسطة:</strong> {{ $transfer->approvedBy->name ?? 'غير محدد' }}<br>
                                            <strong>التاريخ:</strong>
                                            {{ $transfer->approved_at ? $transfer->approved_at->format('Y-m-d H:i') : 'غير محدد' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            @if ($transfer->transferred_by)
                                <div class="timeline-item">
                                    <div class="timeline-marker info"></div>
                                    <div class="timeline-content info">
                                        <h6 class="timeline-title">تم تأكيد النقل</h6>
                                        <p class="timeline-text">
                                            <strong>بواسطة:</strong> {{ $transfer->transferredBy->name ?? 'غير محدد' }}<br>
                                            <strong>التاريخ:</strong>
                                            {{ $transfer->transferred_at ? $transfer->transferred_at->format('Y-m-d H:i') : 'غير محدد' }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            @if ($transfer->failed_by)
                                <div class="timeline-item">
                                    <div class="timeline-marker danger"></div>
                                    <div class="timeline-content danger">
                                        <h6 class="timeline-title">فشل النقل</h6>
                                        <p class="timeline-text">
                                            <strong>بواسطة:</strong> {{ $transfer->failedBy->name ?? 'غير محدد' }}<br>
                                            <strong>التاريخ:</strong>
                                            {{ $transfer->failed_at ? $transfer->failed_at->format('Y-m-d H:i') : 'غير محدد' }}
                                            @if ($transfer->failure_reason)
                                                <br><strong>السبب:</strong> {{ $transfer->failure_reason }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- القيود المحاسبية --}}
        @if ($transfer->trial_start_constraint_id || $transfer->completion_constraint_id || $transfer->failure_constraint_id)
            <div class="col-12 mt-3">
                <div class="info-card" style="border-right: 4px solid #6f42c1;">
                    <div class="card-title">
                        <i class="fas fa-book-open" style="color:#6f42c1;"></i> القيود المحاسبية
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if ($transfer->trial_start_constraint_id)
                            <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $transfer->trial_start_constraint_id]) }}"
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-eye me-1"></i> قيد خروج العاملة للتجربة
                            </a>
                        @endif
                        @if ($transfer->completion_constraint_id)
                            <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $transfer->completion_constraint_id]) }}"
                               class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="fas fa-eye me-1"></i> قيد إتمام النقل
                            </a>
                        @endif
                        @if ($transfer->failure_constraint_id)
                            <a href="{{ route('finance.add-constraint.show', ['add_constraint' => $transfer->failure_constraint_id]) }}"
                               class="btn btn-sm btn-outline-danger" target="_blank">
                                <i class="fas fa-eye me-1"></i> قيد فشل التجربة
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('script')
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>
@endsection
