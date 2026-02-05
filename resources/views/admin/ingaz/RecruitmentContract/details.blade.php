@extends('admin.layout.indexs.index')
@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/custom/status-badges.css">
@endsection
@section('page-title')
    {{ helperTrans('ingaz.contract details') }} - العقد رقم #{{ $contract->id }}
@endsection

<style>
    /* تحسين المظهر العام */
    .contract-details-container {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .contract-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }

    .contract-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: bold;
    }

    .contract-header .contract-status {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 10px;
        font-weight: 600;
    }

    /* تحسين البطاقات */
    .info-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #007bff;
    }

    .info-card.client-info {
        border-left-color: #28a745;
    }

    .info-card.maid-info {
        border-left-color: #ffc107;
    }

    .info-card.contract-info {
        border-left-color: #17a2b8;
    }

    .info-card.financial-info {
        border-left-color: #dc3545;
    }

    .card-title {
        font-size: 18px;
        font-weight: bold;
        color: #495057;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }

    .card-title i {
        margin-left: 10px;
        font-size: 20px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f8f9fa;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        min-width: 150px;
    }

    .info-value {
        color: #212529;
        text-align: left;
    }

    .info-value.highlight {
        color: #007bff;
        font-weight: 600;
    }

    .info-value.success {
        color: #28a745;
        font-weight: 600;
    }

    .info-value.warning {
        color: #ffc107;
        font-weight: 600;
    }

    .info-value.danger {
        color: #dc3545;
        font-weight: 600;
    }

    /* تحسين أزرار الإجراءات */
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .btn-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        color: white;
    }

    .btn-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-print {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }

    .btn-edit {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #545b62);
    }

    .btn-delete {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .btn-change-maid {
        background: linear-gradient(135deg, #ffc107, #e0a800);
    }

    /* تحسين البادجات */
    .status-badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 25px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* تحسين الجداول */
    .info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .info-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: right;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    .info-table td {
        padding: 12px;
        border-bottom: 1px solid #f8f9fa;
    }

    .info-table tr:hover {
        background: #f8f9fa;
    }

    /* تحسين Modal */
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #dee2e6;
        border-radius: 15px 15px 0 0;
    }

    .modal-title {
        color: #495057;
        font-weight: 600;
    }

    .modal-title i {
        margin-left: 8px;
        color: #007bff;
    }

    /* تحسين النماذج */
    .form-check {
        margin-bottom: 10px;
        padding: 10px;
        border-radius: 8px;
        transition: background-color 0.2s ease;
    }

    .form-check:hover {
        background-color: #f8f9fa;
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .form-check-label i {
        margin-left: 8px;
        font-size: 16px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* تحسين التنبيهات */
    .alert {
        border-radius: 10px;
        border: none;
        padding: 15px 20px;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }

    /* تحسين الطباعة */
    @media print {

        .action-buttons,
        .modal {
            display: none !important;
        }

        .contract-details-container {
            background: white !important;
            box-shadow: none !important;
        }
    }

    a.btn-edit {
        width: 60px !important;
        height: 60px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 20px !important;
        transition: all 0.3s ease !important;
        border: none !important;
        cursor: pointer !important;
        text-decoration: none !important;
        color: white !important;
    }
</style>

@section('content')
    @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW))

        <div class="contract-details-container" id="printDiv">
            <!-- رأس العقد -->
            <div class="contract-header">
                <h2><i class="fas fa-file-contract me-2"></i>تفاصيل العقد</h2>
                <div class="contract-status">
                    <i class="fas fa-circle me-2"></i>
                    {{ getContractStatus($contract->status) }}
                </div>
            </div>

            <div class="row">
                <!-- معلومات العميل -->
                <div class="col-md-6 mb-4">
                    <div class="info-card client-info">
                        <div class="card-title">
                            <i class="fas fa-user text-success"></i>
                            معلومات العميل
                        </div>
                        <div class="info-row">
                            <span class="info-label">الاسم:</span>
                            <span class="info-value highlight">{{ $contract->user->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الجوال:</span>
                            <span
                                class="info-value">{{ $contract->user->phone_code . $contract->user->phone ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ الميلاد:</span>
                            <span
                                class="info-value">{{ $contract->user->birthdate ? \Carbon\Carbon::parse($contract->user->birthdate)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الجنسية:</span>
                            <span class="info-value">{{ $contract->user->nationality->title ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم الهوية:</span>
                            <span class="info-value">{{ $contract->user->national_num ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">البريد الإلكتروني:</span>
                            <span class="info-value">{{ $contract->user->email ?? 'غير محدد' }}</span>
                        </div>
                        @if (isset($contract->user->addresses[0]))
                            <div class="info-row">
                                <span class="info-label">العنوان:</span>
                                <span
                                    class="info-value">{{ $contract->user->addresses[0]->address . ' ' . $contract->user->addresses[0]->title }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- معلومات العاملة -->
                <div class="col-md-6 mb-4">
                    <div class="info-card maid-info">
                        <div class="card-title">
                            <i class="fas fa-user-tie text-warning"></i>
                            معلومات العاملة
                        </div>
                        @if ($contract->maid)
                            <div class="info-row">
                                <span class="info-label">اسم العاملة:</span>
                                <span class="info-value highlight">{{ $contract->maid->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجواز:</span>
                                <span class="info-value">{{ $contract->maid->passport_num ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الجنسية:</span>
                                <span
                                    class="info-value">{{ $contract->maid && $contract->maid->nationality ? $contract->maid->nationality->sub_title : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الدين:</span>
                                <span
                                    class="info-value">{{ $contract->maid && $contract->maid->religion ? $contract->maid->religion->title : 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">المهنة:</span>
                                <span class="info-value">
                                    @if ($contract->maid && $contract->maid->jobTitles && $contract->maid->jobTitles->count() > 0)
                                        {{ $contract->maid->jobTitles->first()->title }}
                                    @else
                                        غير محدد
                                    @endif
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">سنوات الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_years ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">حالة الخبرة:</span>
                                <span class="info-value">{{ $contract->maid->experience_status ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">العمر:</span>
                                <span class="info-value">{{ $contract->maid->age ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">السعر:</span>
                                <span class="info-value success">{{ $contract->maid->price ?? 'غير محدد' }} ريال</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">الراتب:</span>
                                <span class="info-value success">{{ $contract->maid->salary ?? 'غير محدد' }} ريال</span>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                                    @if ($contract->status == 'pending_maid_selection')
                                        <h5 class="text-warning"><strong>في انتظار اختيار العاملة</strong></h5>
                                        <p class="text-muted mb-2">هذا العقد في مرحلة انتظار اختيار العاملة المناسبة</p>
                                        <a href="{{ route('RecruitmentContract.selectMaid', $contract->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                                        </a>
                                    @else
                                        <h5 class="text-danger"><strong>لا توجد عاملة مخصصة</strong></h5>
                                        <p class="text-muted mb-0">لم يتم تخصيص عاملة لهذا العقد بعد</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- معلومات العقد الأساسية -->
                <div class="col-md-6 mb-4">
                    <div class="info-card contract-info">
                        <div class="card-title">
                            <i class="fas fa-calendar-alt text-info"></i>
                            معلومات العقد الأساسية
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم العقد:</span>
                            <span class="info-value highlight">#{{ $contract->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ العقد:</span>
                            <span
                                class="info-value">{{ $info->contract_date ? \Carbon\Carbon::parse($info->contract_date)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم مساند:</span>
                            <span class="info-value">{{ $info->msaned_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم تفويض:</span>
                            <span class="info-value">{{ $info->tafwed_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم التأشيرة:</span>
                            <span class="info-value">{{ $info->visa_number ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ التأشيرة الميلادي:</span>
                            <span
                                class="info-value">{{ $info->visa_milad_date ? \Carbon\Carbon::parse($info->visa_milad_date)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ التأشيرة الهجري:</span>
                            <span
                                class="info-value">{{ $info->visa_hijry_date ? \Carbon\Carbon::parse($info->visa_hijry_date)->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مدة العقد:</span>
                            <span class="info-value">{{ $info->contract_duration ?? 'غير محدد' }} سنة</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ انتهاء العقد:</span>
                            @php
                                $contractDate = $info->contract_date
                                    ? \Carbon\Carbon::parse($info->contract_date)
                                    : null;
                                $expiryDate = $contractDate
                                    ? $contractDate->addYears($info->contract_duration ?? 2)
                                    : null;
                            @endphp
                            <span
                                class="info-value danger">{{ $expiryDate ? $expiryDate->format('Y-m-d') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ إنشاء العقد:</span>
                            <span
                                class="info-value">{{ $contract->created_at ? \Carbon\Carbon::parse($contract->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</span>
                        </div>

                        @if ($contract->status == 'completed')
                            <div class="info-row">
                                <span class="info-label">حالة الضمان:</span>
                                <span class="info-value">
                                    @php
                                        $warrantyStatus = $contract->warranty_status ?? 'not_applicable';
                                        $warrantyText = $contract->warranty_status_text ?? 'غير محدد';
                                        $remainingDays = $contract->warranty_remaining_days ?? 0;
                                    @endphp

                                    @switch($warrantyStatus)
                                        @case('active')
                                            <span class="badge bg-success">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                نشط ({{ $remainingDays }} يوم متبقي)
                                            </span>
                                        @break

                                        @case('expired')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                منتهي
                                            </span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-minus-circle me-1"></i>
                                                غير قابل للتطبيق
                                            </span>
                                    @endswitch
                                </span>
                            </div>

                            @if ($contract->warranty_start_date)
                                <div class="info-row">
                                    <span class="info-label">تاريخ بداية الضمان:</span>
                                    <span
                                        class="info-value">{{ \Carbon\Carbon::parse($contract->warranty_start_date)->format('Y-m-d H:i') }}</span>
                                </div>
                            @endif

                            @if ($contract->warranty_end_date)
                                <div class="info-row">
                                    <span class="info-label">تاريخ انتهاء الضمان:</span>
                                    <span
                                        class="info-value {{ $contract->warranty_status == 'expired' ? 'danger' : '' }}">
                                        {{ \Carbon\Carbon::parse($contract->warranty_end_date)->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- معلومات مالية -->
                <div class="col-md-6 mb-4">
                    <div class="info-card financial-info">
                        <div class="card-title">
                            <i class="fas fa-money-bill-wave text-danger"></i>
                            المعلومات المالية
                        </div>
                        <div class="info-row">
                            <span class="info-label">عدد ساعات العمل:</span>
                            <span class="info-value">{{ $info->working_hours_num ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مبلغ نقل الكفالة:</span>
                            <span class="info-value success">{{ $info->sponsorship_price ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الراتب بعد الكفالة:</span>
                            <span class="info-value success">{{ $info->salary_after_sponsorship ?? 'غير محدد' }}
                                ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">التكلفة الشهرية:</span>
                            <span class="info-value warning">{{ $info->monthly_cost ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">قيمة العقد:</span>
                            <span class="info-value highlight">{{ $info->contract_value ?? 'غير محدد' }} ريال</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الانجاز من:</span>
                            <span class="info-value">{{ $info->ingaz_from ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الانجاز إلى:</span>
                            <span class="info-value">{{ $info->ingaz_to ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الكفيل السابق:</span>
                            <span class="info-value">{{ $info->prevSponsorship->title ?? 'غير محدد' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">مقر الإيواء:</span>
                            <span class="info-value">{{ $info->shelter->title ?? 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات المسوق -->
            @if ($marketer)
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-user-tie text-primary"></i>
                                معلومات المسوق
                            </div>
                            <div class="info-row">
                                <span class="info-label">اسم المسوق:</span>
                                <span class="info-value highlight">{{ $marketer->name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">رقم الجوال:</span>
                                <span class="info-value">{{ $marketer->phone ?? 'غير محدد' }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">البريد الإلكتروني:</span>
                                <span class="info-value">{{ $marketer->email ?? 'غير محدد' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- مستندات الطلب --}}
                    @if ($contract->signature)
                        <div class="col-md-6 mb-4">
                            <div class="info-card">
                                <div class="card-title">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                    مستندات الطلب
                                </div>

                                <div class="info-row d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="info-label">عرض الملف:</span>
                                        <span class="info-value highlight">
                                            {{-- {{ $document->file_name }} --}}
                                        </span>
                                    </div>

                                    <!-- Eye icon -->
                                    <a href="{{ showFile($contract->signature) }}" target="_blank"
                                        class="text-secondary fs-5" title="عرض الملف">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- معلومات الطلب المسبق -->
            @if ($contract->order)
                <div class="row">
                    <div class="col-12 mb-4">
                        <div class="info-card">
                            <div class="card-title">
                                <i class="fas fa-list-check text-secondary"></i>
                                معلومات الطلب المسبق
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">رقم الطلب:</span>
                                        <span class="info-value highlight">{{ $contract->order->order_code }}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">تاريخ الطلب:</span>
                                        <span
                                            class="info-value">{{ $contract->order->created_at ? \Carbon\Carbon::parse($contract->order->created_at)->format('Y-m-d H:i') : 'غير محدد' }}</span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">حالة الطلب:</span>
                                        <span
                                            class="status-badge bg-{{ $contract->order->status == 'completed' ? 'success' : 'warning' }}">
                                            {{ $contract->order->status == 'completed' ? 'مكتمل' : 'قيد المعالجة' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">طريقة الدفع:</span>
                                        <span
                                            class="info-value">{{ $contract->order->payment_method ?? 'غير محدد' }}</span>
                                    </div>
                                </div>
                                @if ($contract->order->details && $contract->order->details->package)
                                    <div class="col-md-6 mb-3">
                                        <div class="info-row">
                                            <span class="info-label">الحزمة المختارة:</span>
                                            <span
                                                class="info-value highlight">{{ $contract->order->details->package->title ?? 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="info-row">
                                            <span class="info-label">سعر الحزمة:</span>
                                            <span
                                                class="info-value success">{{ $contract->order->details->package->price ? number_format($contract->order->details->package->price, 2) . ' ريال' : 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-3">
                                    <div class="info-row">
                                        <span class="info-label">نوع العمل:</span>
                                        <span
                                            class="info-value">{{ $contract->order->jobType->title ?? 'غير محدد' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- سجل الحالات -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="info-card">
                        <div class="card-title">
                            <i class="fas fa-history text-info"></i>
                            سجل حالات العقد
                        </div>
                        <table class="info-table">
                            <thead>
                                <tr>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contract->contract_status_logs as $logs)
                                    <tr>
                                        <td>
                                            <span class="status-badge bg-info">
                                                {{ getEnumValue('contract_statuses', $logs->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $logs->date }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="action-buttons">
                {{-- <button type="button" class="btn-icon btn-print" onclick="printDiv()" title="طباعة العقد">
                    <i class="fas fa-print"></i>
                </button> --}}
                <a href="{{ route('contract_download_form.download', $contract->id) . '?type=download' }}"
                    class="btn-icon btn-print" title="طباعة العقد" target="_blank">
                    <i class="fas fa-print"></i>
                </a>

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT))
                    <a href="{{ route('RecruitmentContract.edit', $contract->id) }}" class="btn-icon btn-edit"
                        title="تعديل العقد">
                        <i class="fas fa-edit"></i>
                    </a>
                @endif

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE))
                    <button type="button" class="btn-icon btn-change-maid" onclick="openChangeMaidModal()"
                        title="تغيير العاملة">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                @endif

                <a href="{{ route('RecruitmentContract.index') }}" class="btn-icon btn-back" title="العودة للقائمة">
                    <i class="fas fa-arrow-right"></i>
                </a>

                @if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE))
                    <button type="button" class="btn-icon btn-delete" onclick="deleteContract({{ $contract->id }})"
                        title="حذف العقد">
                        <i class="fas fa-trash"></i>
                    </button>
                @endif
            </div>
        </div>

        <!-- دايلوج تغيير العاملة -->
        <div class="modal fade" id="changeMaidModal" tabindex="-1" aria-labelledby="changeMaidModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changeMaidModalLabel">
                            <i class="fas fa-exchange-alt"></i>
                            تغيير العاملة في العقد رقم #{{ $contract->id }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- معلومات العاملة الحالية -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-user-tie"></i> العاملة الحالية:</h6>
                            <strong>{{ $contract->maid->name ?? 'غير محدد' }}</strong> -
                            {{ $contract->maid->passport_num ?? 'غير محدد' }}
                            ({{ $contract->maid && $contract->maid->nationality ? $contract->maid->nationality->sub_title : 'غير محدد' }})
                        </div>

                        <form id="changeMaidForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="maid_search" class="form-label">
                                            <i class="fas fa-search"></i>
                                            البحث في العاملات
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="maid_search"
                                                placeholder="ابحث بالاسم أو رقم الجواز..."
                                                onkeyup="searchMaidsWithDelay(this.value)"
                                                onkeydown="handleSearchKeydown(event)">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="clearSearch()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <small class="form-text text-muted">
                                            اكتب للبحث في العاملات المتاحات - اضغط Enter لفتح القائمة
                                        </small>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="new_maid_id" class="form-label">
                                            <i class="fas fa-user-plus"></i>
                                            اختيار العاملة الجديدة
                                        </label>
                                        <select class="form-control" id="new_maid_id" name="new_maid_id" required>
                                            <option value="">اختر العاملة الجديدة...</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            سيتم تحميل العاملات المتاحات...
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-user-minus"></i>
                                            حالة العاملة السابقة
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                id="status_available" value="available" required>
                                            <label class="form-check-label" for="status_available">
                                                <i class="fas fa-check text-success"></i>
                                                متاحة
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="old_maid_status"
                                                id="status_unavailable" value="backout" required>
                                            <label class="form-check-label" for="status_unavailable">
                                                <i class="fas fa-ban text-danger"></i>
                                                باك اوت
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="reason" class="form-label">
                                    <i class="fas fa-comment"></i>
                                    سبب التغيير (اختياري)
                                </label>
                                <textarea class="form-control" id="reason" name="reason" rows="3"
                                    placeholder="اكتب سبب تغيير العاملة..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitChangeMaid()">
                            <i class="fas fa-save"></i>
                            حفظ التغيير
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

@endsection

@section('js')
    <script>
        function printDiv() {
            var printContents = document.getElementById('printDiv').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }

        function deleteContract(contractId) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف هذا العقد؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، احذف العقد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/RecruitmentContract/${contractId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                                'Content-Type': 'application/json',
                            },
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('نجاح!', data.message || 'تم حذف العقد بنجاح', 'success');
                                window.location.href = '{{ route('RecruitmentContract.index') }}';
                            } else {
                                Swal.fire('خطأ!', data.message || 'حدث خطأ أثناء حذف العقد', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ!', 'حدث خطأ أثناء حذف العقد', 'error');
                        });
                }
            });
        }

        // فتح دايلوج تغيير العاملة
        function openChangeMaidModal() {
            console.log('Opening change maid modal...');

            // فتح الدايلوج أولاً
            $('#changeMaidModal').modal('show');

            // تحميل العاملات المتاحات بعد فتح الدايلوج
            setTimeout(function() {
                loadAvailableMaids();
            }, 300);
        }

        // تحميل العاملات المتاحات
        function loadAvailableMaids(searchTerm = '') {
            console.log('Loading available maids...', {
                searchTerm
            });

            const url = '{{ route('RecruitmentContract.availableMaids') }}';
            const fullUrl = searchTerm ? `${url}?search=${encodeURIComponent(searchTerm)}` : url;
            console.log('Fetching from URL:', fullUrl);

            // الحصول على CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch(fullUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        const select = document.getElementById('new_maid_id');
                        select.innerHTML = '<option value="">اختر العاملة الجديدة...</option>';

                        if (data.maids && data.maids.length > 0) {
                            data.maids.forEach(maid => {
                                const option = document.createElement('option');
                                option.value = maid.id;
                                option.textContent =
                                    `${maid.name} - ${maid.passport_num} (${maid.nationality})`;
                                // إضافة بيانات للبحث
                                option.setAttribute('data-name', maid.name);
                                option.setAttribute('data-passport', maid.passport_num);
                                option.setAttribute('data-nationality', maid.nationality);
                                select.appendChild(option);
                            });
                            console.log('Loaded ' + data.maids.length + ' maids');
                        } else {
                            select.innerHTML = '<option value="">لا توجد عاملات متاحات</option>';
                            console.log('No available maids found');
                        }
                    } else {
                        console.error('Error loading maids:', data.message);
                        const select = document.getElementById('new_maid_id');
                        select.innerHTML = '<option value="">خطأ في تحميل العاملات</option>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    const select = document.getElementById('new_maid_id');
                    select.innerHTML = '<option value="">خطأ في الاتصال: ' + error.message + '</option>';
                });
        }

        // البحث مع تأخير لتجنب طلبات كثيرة
        function searchMaidsWithDelay(searchTerm) {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                filterMaidOptions(searchTerm);
            }, 200); // تأخير 200 مللي ثانية
        }

        // إرسال نموذج تغيير العاملة
        function submitChangeMaid() {
            const form = document.getElementById('changeMaidForm');
            const formData = new FormData(form);

            // التحقق من البيانات
            if (!formData.get('new_maid_id')) {
                Swal.fire('خطأ!', 'يرجى اختيار العاملة الجديدة', 'error');
                return;
            }

            if (!formData.get('old_maid_status')) {
                Swal.fire('خطأ!', 'يرجى اختيار حالة العاملة السابقة', 'error');
                return;
            }

            // تأكيد العملية
            // if (!confirm('هل أنت متأكد من تغيير العاملة في هذا العقد؟')) {
            //     return;
            // }
            Swal.fire({
                title: 'تأكيد تغيير العاملة',
                text: 'هل أنت متأكد من تغيير العاملة في هذا الطلب؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، غير العاملة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Submitting change maid form:', {
                        new_maid_id: formData.get('new_maid_id'),
                        old_maid_status: formData.get('old_maid_status'),
                        reason: formData.get('reason')
                    });

                    // إرسال البيانات
                    fetch('{{ route('RecruitmentContract.changeMaid', $contract->id) }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content'),
                            },
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);

                            if (data.success) {
                                Swal.fire('نجاح!', data.message || 'تم تغيير العاملة بنجاح', 'success');
                                console.log('Change maid details:', data.data);
                                location.reload(); // إعادة تحميل الصفحة لتحديث البيانات
                            } else {
                                Swal.fire('خطأ!', data.message || 'حدث خطأ أثناء تغيير العاملة', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير العاملة', 'error');
                        });
                }
            });
        }

        // فلترة خيارات العاملات في القائمة المنسدلة
        function filterMaidOptions(searchTerm) {
            console.log('Filtering maids with term:', searchTerm);

            const select = document.getElementById('new_maid_id');
            const options = Array.from(select.options);

            // إزالة رسالة "لا توجد نتائج" السابقة
            const noResultsOption = select.querySelector('option[disabled]');
            if (noResultsOption) {
                noResultsOption.remove();
            }

            // إذا كان البحث فارغاً، إظهار جميع الخيارات
            if (!searchTerm.trim()) {
                options.forEach(option => {
                    option.style.display = '';
                });
                return;
            }

            // إظهار/إخفاء الخيارات بناءً على البحث
            let visibleCount = 0;
            const search = searchTerm.toLowerCase().trim();

            options.forEach(option => {
                if (option.value === '') {
                    // إبقاء الخيار الافتراضي دائماً
                    option.style.display = '';
                } else {
                    const name = option.getAttribute('data-name')?.toLowerCase() || '';
                    const passport = option.getAttribute('data-passport')?.toLowerCase() || '';
                    const nationality = option.getAttribute('data-nationality')?.toLowerCase() || '';
                    const fullText = option.textContent.toLowerCase();

                    // البحث في جميع الحقول
                    const matches = name.includes(search) ||
                        passport.includes(search) ||
                        nationality.includes(search) ||
                        fullText.includes(search);

                    if (matches) {
                        option.style.display = '';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                    }
                }
            });

            // إذا لم يتم العثور على نتائج، إظهار رسالة
            if (visibleCount === 0 && searchTerm.trim()) {
                const noResultsOption = document.createElement('option');
                noResultsOption.value = '';
                noResultsOption.textContent = 'لا توجد نتائج تطابق البحث';
                noResultsOption.disabled = true;
                select.appendChild(noResultsOption);
            }

            console.log('Visible options:', visibleCount);
        }

        // معالجة المفاتيح في حقل البحث
        function handleSearchKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const select = document.getElementById('new_maid_id');
                select.focus();
                select.click(); // فتح القائمة المنسدلة
            }
        }

        // مسح البحث
        function clearSearch() {
            document.getElementById('maid_search').value = '';
            filterMaidOptions(''); // إظهار جميع الخيارات
        }
    </script>
@endsection
