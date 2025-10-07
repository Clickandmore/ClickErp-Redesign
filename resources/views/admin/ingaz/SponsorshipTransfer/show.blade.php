@extends('admin.layout.indexs.index')

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/custom/status-badges.css">
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
        @if($transfer->status === 'pending')
        <a href="{{ route('SponsorshipTransfer.edit', $transfer->id) }}" class="btn-icon btn-edit" title="تحرير">
            <i class="fas fa-edit"></i>
        </a>
        @endif
        <button onclick="window.print()" class="btn-icon btn-print" title="طباعة">
            <i class="fas fa-print"></i>
        </button>
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
                        @if($transfer->contract)
                            <a href="{{ route('RecruitmentContract.show', $transfer->contract->id) }}" class="info-value highlight">
                                عقد رقم: {{ $transfer->contract->id }}
                            </a>
                        @else
                            <span class="info-value warning">غير محدد</span>
                        @endif
                    </span>
                </div>
                @if($transfer->notes)
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
                <div class="info-row">
                    <span class="info-label">نسبة السداد:</span>
                    <span class="info-value">
                        @php
                            $percentage = $transfer->contract_value > 0 ? ($transfer->paid_amount / $transfer->contract_value) * 100 : 0;
                        @endphp
                        <span class="details-badge {{ $percentage >= 100 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger') }}">
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
                @if($transfer->oldSponsor)
                <div class="info-row">
                    <span class="info-label">الاسم:</span>
                    <span class="info-value">{{ $transfer->oldSponsor->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الهوية:</span>
                    <span class="info-value">{{ $transfer->oldSponsor->national_num ?? 'غير محدد' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الجوال:</span>
                    <span class="info-value">{{ $transfer->oldSponsor->phone }}</span>
                </div>
                @if($transfer->oldSponsor->email)
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني:</span>
                    <span class="info-value">{{ $transfer->oldSponsor->email }}</span>
                </div>
                @endif
                @if($transfer->oldSponsor->city)
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
                @if($transfer->newSponsor)
                <div class="info-row">
                    <span class="info-label">الاسم:</span>
                    <span class="info-value">{{ $transfer->newSponsor->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الهوية:</span>
                    <span class="info-value">{{ $transfer->newSponsor->national_num ?? 'غير محدد' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم الجوال:</span>
                    <span class="info-value">{{ $transfer->newSponsor->phone }}</span>
                </div>
                @if($transfer->newSponsor->email)
                <div class="info-row">
                    <span class="info-label">البريد الإلكتروني:</span>
                    <span class="info-value">{{ $transfer->newSponsor->email }}</span>
                </div>
                @endif
                @if($transfer->newSponsor->city)
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
        @if($transfer->contract && $transfer->contract->maid)
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
                @if($transfer->contract->maid->nationality)
                <div class="info-row">
                    <span class="info-label">الجنسية:</span>
                    <span class="info-value">{{ $transfer->contract->maid->nationality->title }}</span>
                </div>
                @endif
                @if($transfer->contract->maid->age)
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
                @if($transfer->trial_start_date && $transfer->trial_days)
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
                        @if($daysRemaining > 0)
                            <span class="details-badge bg-success">متبقي {{ $daysRemaining }} يوم</span>
                        @elseif($daysRemaining == 0)
                            <span class="details-badge bg-warning">تنتهي اليوم</span>
                        @else
                            <span class="details-badge bg-danger">انتهت منذ {{ abs($daysRemaining) }} يوم</span>
                        @endif
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- سجل الإجراءات -->
        @if($transfer->approved_by || $transfer->transferred_by || $transfer->failed_by)
        <div class="col-12">
            <div class="info-card history-info">
                <div class="card-title">
                    <i class="fas fa-history"></i>سجل الإجراءات
                </div>
                <div class="timeline">
                    @if($transfer->approved_by)
                    <div class="timeline-item">
                        <div class="timeline-marker success"></div>
                        <div class="timeline-content success">
                            <h6 class="timeline-title">تمت الموافقة على الطلب</h6>
                            <p class="timeline-text">
                                <strong>بواسطة:</strong> {{ $transfer->approvedBy->name ?? 'غير محدد' }}<br>
                                <strong>التاريخ:</strong> {{ $transfer->approved_at ? $transfer->approved_at->format('Y-m-d H:i') : 'غير محدد' }}
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($transfer->transferred_by)
                    <div class="timeline-item">
                        <div class="timeline-marker info"></div>
                        <div class="timeline-content info">
                            <h6 class="timeline-title">تم تأكيد النقل</h6>
                            <p class="timeline-text">
                                <strong>بواسطة:</strong> {{ $transfer->transferredBy->name ?? 'غير محدد' }}<br>
                                <strong>التاريخ:</strong> {{ $transfer->transferred_at ? $transfer->transferred_at->format('Y-m-d H:i') : 'غير محدد' }}
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($transfer->failed_by)
                    <div class="timeline-item">
                        <div class="timeline-marker danger"></div>
                        <div class="timeline-content danger">
                            <h6 class="timeline-title">فشل النقل</h6>
                            <p class="timeline-text">
                                <strong>بواسطة:</strong> {{ $transfer->failedBy->name ?? 'غير محدد' }}<br>
                                <strong>التاريخ:</strong> {{ $transfer->failed_at ? $transfer->failed_at->format('Y-m-d H:i') : 'غير محدد' }}
                                @if($transfer->failure_reason)
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
</div>
@endsection

@section("script")
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
@endsection
