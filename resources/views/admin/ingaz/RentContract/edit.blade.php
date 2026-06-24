@extends('admin.layout.indexs.index')
@section('page-title')
تعديل عقد التأجير - رقم {{ $contract->contract_number ?? $contract->id }}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    :root {
        --primary-color: #2794eb;
        --primary-dark: #1e7bc8;
        --primary-light: #e3f2fd;
    }

    .contract-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
        margin-bottom: 1.5rem;
    }

    .form-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(39, 148, 235, 0.1);
        border-left: 4px solid var(--primary-color);
    }

    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f3f4;
    }

    .section-icon {
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        margin-left: 12px;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }

    .section-subtitle {
        color: #718096;
        font-size: 0.85rem;
        margin: 0;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        font-size: 0.9rem;
    }

    .required-star {
        color: #e53e3e;
        margin-right: 4px;
    }

    .form-control, .form-select {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(39, 148, 235, 0.1);
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        height: 40px;
        padding: 6px 10px;
    }

    .contract-summary {
        background: linear-gradient(135deg, #48bb78, #38a169);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin: 1.5rem 0;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .summary-item {
        text-align: center;
        padding: 0.75rem;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
    }

    .summary-value {
        font-size: 1.2rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .summary-label {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .btn-primary {
        background: var(--primary-color);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.8rem;
    }

    .current-maid-info {
        background: var(--primary-light);
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .payment-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
    }

    .payment-summary {
        background: white;
        border-radius: 8px;
        border: 1px dashed #cbd5e0;
        padding: 0.85rem;
    }

    .maid-photo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
    }

    @media (max-width: 768px) {
        .contract-header {
            padding: 1rem;
        }

        .form-section {
            padding: 1rem;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_EDIT))

    <!-- Contract Edit Header -->
    <div class="contract-header">
        <div class="text-center">
            <h4 class="mb-2">
                <i class="fas fa-edit me-2"></i>
                تعديل عقد التأجير
            </h4>
            <p class="mb-2 opacity-75">رقم العقد: {{ $contract->contract_number ?? $contract->id }}</p>
            <span class="status-badge bg-{{ $contract->status == 'active' ? 'success' : ($contract->status == 'completed' ? 'info' : 'warning') }}">
                حالة العقد: {{ $contract->status == 'active' ? 'نشط' : ($contract->status == 'completed' ? 'مكتمل' : 'جديد') }}
            </span>
        </div>
    </div>

    <form id="editRentalContractForm" method="post" action="{{ route('RentContract.update', $contract->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Client Information -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h5 class="section-title">معلومات العميل</h5>
                    <p class="section-subtitle">البيانات الشخصية والمهنية</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            اسم العميل
                        </label>
                        <input type="text" class="form-control" id="client_name" name="client_name"
                               value="{{ $contract->user->name ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            رقم الجوال
                        </label>
                        <input type="tel" class="form-control" id="client_phone" name="client_phone"
                               value="{{ $contract->user->phone ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            {{ $contract->user?->documentNumberLabel() ?? 'رقم الهوية' }}
                        </label>
                        <input type="text" class="form-control" id="client_national_num" name="client_national_num"
                               value="{{ $contract->user?->documentNumber() ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الجنسية
                        </label>
                        <select class="form-select select2" id="client_nationality_id" name="client_nationality_id" required>
                            <option value="" disabled>اختر الجنسية...</option>
                            @foreach($countries as $country)
                                <option value="{{$country->id}}"
                                    {{ ($contract->user->nationality_id ?? '') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="client_email" name="client_email"
                               value="{{ $contract->user->email ?? '' }}">
                    </div>
                </div>

                @unless($contract->user?->isCompanyClient())
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" class="form-control" id="client_birthdate" name="client_birthdate"
                               value="{{ @$contract->user->birthdate ? \Carbon\Carbon::parse($contract->user->birthdate)->format('Y-m-d') : '' }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">المهنة</label>
                        <input type="text" class="form-control" id="profession" name="profession"
                               value="{{ $contract->user->profession ?? '' }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">الدخل الشهري (ريال)</label>
                        <input type="number" class="form-control" id="monthly_income" name="monthly_income"
                               value="{{ $contract->user->monthly_income ?? '' }}" min="0" step="0.01">
                    </div>
                </div>
                @endunless

                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">العنوان</label>
                        <input type="text" class="form-control" id="client_address" name="client_address"
                               value="{{ $contract->user->address ?? '' }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            المدينة
                        </label>
                        <input type="text" class="form-control" id="client_city" name="client_city"
                               value="{{ $contract->user->city ?? '' }}" required>
                    </div>
                </div>

                @unless($contract->user?->isCompanyClient())
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">عدد أفراد الأسرة</label>
                        <input type="number" class="form-control" id="family_num" name="family_num"
                               value="{{ $contract->user->family_num ?? '' }}" min="1" max="20">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">عدد الأطفال</label>
                        <input type="number" class="form-control" id="children" name="children"
                               value="{{ $contract->user->children ?? '' }}" min="0" max="15">
                    </div>
                </div>
                @endunless
            </div>
        </div>

        <!-- Maid Information -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-female"></i>
                </div>
                <div>
                    <h5 class="section-title">معلومات العاملة</h5>
                    <p class="section-subtitle">اختيار وتعديل بيانات العاملة</p>
                </div>
            </div>

            <!-- Current Maid Info -->
            @if($contract->maid)
            <div class="current-maid-info">
                <h6 class="text-primary mb-2">
                    <i class="fas fa-info-circle me-2"></i>
                    العاملة الحالية
                </h6>
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="{{ $contract->maid->photo_url }}" alt="صورة العاملة" class="maid-photo">
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>الاسم:</strong> {{ $contract->maid->name }}</p>
                                <p class="mb-1"><strong>رقم الجواز:</strong> {{ $contract->maid->passport_num }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>العمر:</strong> {{ $contract->maid->age }} سنة</p>
                                <p class="mb-1"><strong>الراتب:</strong> {{ number_format($contract->maid->salary, 0) }} ريال</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            اختر العاملة
                        </label>
                        <select name="maid_id" id="maid_id" class="form-select select2" required>
                            <option value="" disabled>اختر عاملة...</option>
                            @foreach($availableRentalMaids as $maid)
                                <option value="{{$maid->id}}"
                                    {{ ($contract->maid_id ?? '') == $maid->id ? 'selected' : '' }}
                                    data-name="{{$maid->name}}"
                                    data-passport="{{$maid->passport_num}}"
                                    data-age="{{$maid->age}}"
                                    data-experience="{{$maid->experience_years}}"
                                    data-salary="{{ $maid->resolved_salary ?? $maid->salary }}"
                                    data-photo="{{$maid->photo_url}}">
                                    {{$maid->name}} - {{$maid->passport_num}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Details -->
        @if($contract->info)
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <h5 class="section-title">الباقة والدفع</h5>
                    <p class="section-subtitle">تفاصيل الباقة وحالة السداد</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="form-group">
                        <label for="package_id" class="form-label">
                            <span class="required-star">*</span>
                            اختر الباقة
                        </label>
                        <select name="package_id" id="package_id" class="form-select select2" required>
                            <option value="">اختر الباقة...</option>
                            @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}"
                                        data-days="{{ $package->days_count }}"
                                        data-rental-type="{{ $package->rental_type ?? 'monthly' }}"
                                        data-duration-count="{{ $package->duration_count ?: max(1, (int) ceil(($package->days_count ?: 30) / 30)) }}"
                                        data-duration-unit="{{ $package->duration_unit ?? 'month' }}"
                                        data-duration-label="{{ $package->duration_label }}"
                                        data-working-hours="{{ $package->working_hours_per_day }}"
                                        data-price="{{ $package->price_after_discount }}"
                                        data-total="{{ $package->price_with_tax }}"
                                        {{ (int) ($contract->info->package_id ?? 0) === (int) $package->id ? 'selected' : '' }}>
                                    {{ $package->title }} - {{ $package->rental_type_label }} - {{ $package->duration_count ?: max(1, (int) ceil(($package->days_count ?: 30) / 30)) }} {{ $package->duration_unit_label }} - {{ number_format((float) $package->price_after_discount, 2) }} ريال بدون الضريبة
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="selected_package_id" id="selected_package_id"
                               value="{{ $contract->info->package_id ?? '' }}">
                        <input type="hidden" name="rental_type" id="rental_type"
                               value="{{ $contract->info->rental_type ?? $contract->info->package?->rental_type ?? 'monthly' }}">
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="payment-summary h-100 d-flex flex-column justify-content-center">
                        <div>إجمالي العقد: <strong id="paymentTotalValue">{{ number_format((float) ($contract->info->contract_value ?? 0), 2) }} ريال</strong></div>
                        <div class="mt-1">المتبقي بعد الدفع: <strong id="paymentRemainingText">{{ number_format((float) ($contract->info->remaining_amount ?? 0), 2) }} ريال</strong></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="payment-box">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="payment_type" class="form-label">
                                    <span class="required-star">*</span>
                                    طريقة الدفع
                                </label>
                                <select name="payment_type" id="payment_type" class="form-select" required>
                                    <option value="">اختر الطريقة</option>
                                    <option value="cash" {{ ($contract->info->payment_type ?? '') === 'cash' ? 'selected' : '' }}>نقداً</option>
                                    <option value="bank_transfer" {{ ($contract->info->payment_type ?? '') === 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                                    <option value="mada" {{ ($contract->info->payment_type ?? '') === 'mada' ? 'selected' : '' }}>مدى</option>
                                    <option value="tabby" {{ ($contract->info->payment_type ?? '') === 'tabby' ? 'selected' : '' }}>تابي</option>
                                    <option value="tamara" {{ ($contract->info->payment_type ?? '') === 'tamara' ? 'selected' : '' }}>تمارا</option>
                                    @if(($moyasarEnabled ?? false) || ($contract->info->payment_type ?? '') === 'moyasar')
                                        <option value="moyasar" {{ ($contract->info->payment_type ?? '') === 'moyasar' ? 'selected' : '' }}>ميسر: فيزا، مدى، ماستر كارد، أبل باي</option>
                                    @endif
                                    <option value="pay_later" {{ ($contract->info->payment_type ?? '') === 'pay_later' ? 'selected' : '' }}>الدفع لاحقاً</option>
                                    <option value="other" {{ ($contract->info->payment_type ?? '') === 'other' ? 'selected' : '' }}>أخرى</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="payment_status" class="form-label">
                                    <span class="required-star">*</span>
                                    حالة الدفع
                                </label>
                                <select name="payment_status" id="payment_status" class="form-select" required>
                                    <option value="">اختر الحالة</option>
                                    <option value="paid" {{ ($contract->info->payment_status ?? '') === 'paid' ? 'selected' : '' }}>تم الدفع</option>
                                    <option value="partial" {{ ($contract->info->payment_status ?? '') === 'partial' ? 'selected' : '' }}>مدفوع جزئياً</option>
                                    <option value="pending" {{ ($contract->info->payment_status ?? '') === 'pending' ? 'selected' : '' }}>معلق</option>
                                    <option value="later" {{ ($contract->info->payment_status ?? '') === 'later' ? 'selected' : '' }}>الدفع لاحقاً</option>
                                    <option value="bank_pending" {{ ($contract->info->payment_status ?? '') === 'bank_pending' ? 'selected' : '' }}>بانتظار التحويل</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="payment_receipt" class="form-label">إيصال الدفع</label>
                                <input type="file" id="payment_receipt" name="payment_receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                @if(!empty($paymentReceipts))
                                    <div id="paymentReceiptsList" class="mt-2">
                                        @foreach($paymentReceipts as $receipt)
                                            <div class="payment-receipt-item d-flex align-items-center justify-content-between gap-2 border rounded p-2 mb-2">
                                                <a href="{{ route('RentContract.attachments.view', [$contract->id, $receipt['key']]) }}" target="_blank" class="small">
                                                    {{ $receipt['label'] }}
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger delete-payment-receipt"
                                                    data-delete-url="{{ route('RentContract.paymentReceipts.delete', [$contract->id, $receipt['key']]) }}"
                                                >
                                                    حذف
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-4" id="paidAmountWrapper">
                                <label for="paid_amount" class="form-label">المبلغ المدفوع</label>
                                <input type="number" id="paid_amount" name="paid_amount" class="form-control" step="0.01" min="0"
                                       value="{{ $contract->info->paid_amount ?? '0.00' }}">
                            </div>

                            <div class="col-md-4">
                                <label for="remaining_amount" class="form-label">المبلغ المتبقي</label>
                                <input type="number" id="remaining_amount" name="remaining_amount" class="form-control" step="0.01" min="0" readonly
                                       value="{{ $contract->info->remaining_amount ?? '0.00' }}">
                            </div>

                            <div class="col-md-4">
                                <label for="total_contract_value" class="form-label">تكلفة العقد الإجمالية</label>
                                <input type="number" id="total_contract_value" name="total_contract_value" class="form-control" readonly
                                       value="{{ $contract->info->contract_value ?? '0.00' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div>
                    <h5 class="section-title">تفاصيل العقد</h5>
                    <p class="section-subtitle">المعلومات المالية والزمنية</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            المسوق المسؤول
                        </label>
                        <select class="form-select select2" id="marketer_id" name="marketer_id" required>
                            <option value="" disabled>اختر المسوق...</option>
                            @foreach($marketers as $marketer)
                                <option value="{{$marketer->id}}"
                                    {{ ($contract->marketer_id ?? '') == $marketer->id ? 'selected' : '' }}>
                                    {{$marketer->name}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            تاريخ العقد
                        </label>
                        <input type="date" class="form-control" id="contract_date" name="contract_date"
                               value="{{ $contract->contract_date ? $contract->contract_date->format('Y-m-d') : '' }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">ساعات العمل يومياً</label>
                        <input type="number" class="form-control" id="working_hours_per_day" name="working_hours_per_day"
                               value="{{ $contract->info->working_hours_per_day ?? '' }}" min="1" max="24">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">أجرة الساعة (ريال)</label>
                        <input type="number" class="form-control" id="hourly_rate" name="hourly_rate"
                               value="{{ $contract->info->hourly_rate ?? '' }}" min="0" step="0.01">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">الراتب الشهري (ريال)</label>
                        <input type="number" class="form-control" id="monthly_salary" name="monthly_salary"
                               value="{{ $contract->info->monthly_salary ?? '' }}" min="0" step="0.01" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">مبلغ نقل الكفالة (ريال)</label>
                        <input type="number" class="form-control" id="sponsorship_transfer_amount" name="sponsorship_transfer_amount"
                               value="{{ $contract->info->sponsorship_transfer_amount ?? '' }}" min="0" step="0.01">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">الراتب بعد نقل الكفالة (ريال)</label>
                        <input type="number" class="form-control" id="salary_after_sponsorship" name="salary_after_sponsorship"
                               value="{{ $contract->info->salary_after_sponsorship ?? '' }}" min="0" step="0.01">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            تكلفة الفترة (ريال)
                        </label>
                        <input type="number" class="form-control" id="monthly_cost" name="monthly_cost"
                               value="{{ $contract->info->monthly_cost ?? '' }}" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            مدة العقد بالشهور للحساب
                        </label>
                        <input type="number" class="form-control" id="contract_duration_months" name="contract_duration_months"
                               value="{{ $contract->info->contract_duration_months ?? '' }}" min="1" max="60" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">مدة العقد (أيام)</label>
                        <input type="number" class="form-control" id="contract_duration_days" name="contract_duration_days"
                               value="{{ $contract->info->contract_duration_days ?? '' }}" min="1" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الإنجاز من
                        </label>
                        <input type="date" class="form-control" id="ingaz_from" name="ingaz_from"
                               value="{{ $contract->info->ingaz_from ? $contract->info->ingaz_from->format('Y-m-d') : '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">الإنجاز إلى</label>
                        <input type="text" class="form-control" id="ingaz_to_display" readonly
                               value="{{ $contract->info->ingaz_to ? $contract->info->ingaz_to->format('Y-m-d') : '' }}">
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2">{{ $contract->info->notes ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Contract Summary -->
        <div class="contract-summary">
            <h5 class="text-center mb-3">
                <i class="fas fa-calculator me-2"></i>
                ملخص العقد المالي
            </h5>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value" id="summary-monthly-cost">
                        {{ number_format($contract->info->monthly_cost ?? 0, 0) }} ريال
                    </div>
                    <div class="summary-label">التكلفة الشهرية</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-duration">
                        {{ $contract->info->contract_duration_months ?? 0 }} شهر
                    </div>
                    <div class="summary-label">مدة العقد</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-total-value">
                        {{ number_format($contract->info->contract_value ?? 0, 0) }} ريال
                    </div>
                    <div class="summary-label">قيمة العقد الإجمالية</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-daily-rate">
                        {{ number_format(($contract->info->monthly_cost ?? 0) / 30, 0) }} ريال
                    </div>
                    <div class="summary-label">المعدل اليومي</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="{{ route('RentContract.show', $contract->id) }}" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>
                إلغاء
            </a>
            <button class="btn btn-primary" type="submit" id="submit-btn">
                <i class="fas fa-save me-2"></i>
                حفظ التعديلات
            </button>
        </div>
    </form>

@else
    @include('components.no_permissions')
@endif

@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'اختر...',
        allowClear: true,
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        }
    });

    // Auto calculations
    $('#hourly_rate, #working_hours_per_day').on('input', function() {
        calculateMonthlySalary();
    });

    $('#monthly_cost, #contract_duration_months').on('input', function() {
        calculateContractValue();
        syncPaymentSummary();
    });

    $('#contract_duration_months').on('input', function() {
        calculateDurationInDays();
    });

    $('#ingaz_from, #contract_duration_days').on('change', function() {
        calculateIngazTo();
    });

    $('#package_id').on('change', function() {
        applyPackageData();
    });

    $('#payment_status').on('change', function() {
        updatePaymentState();
    });

    $('#payment_type').on('change', function() {
        enforcePaymentCompatibility();
        updatePaymentState();
    });

    $('#paid_amount').on('input', function() {
        syncPaymentSummary();
    });

    // Form submission
    $('#editRentalContractForm').submit(function(e) {
        e.preventDefault();

        if (validateForm()) {
            submitForm();
        }
    });

    // Functions
    function formatCurrency(value) {
        return `${(parseFloat(value) || 0).toFixed(2)} ريال`;
    }

    function calculateMonthlySalary() {
        const hourlyRate = parseFloat($('#hourly_rate').val()) || 0;
        const workingHours = parseInt($('#working_hours_per_day').val()) || 0;

        if (hourlyRate && workingHours) {
            const monthlySalary = hourlyRate * workingHours * 30;
            $('#monthly_salary').val(monthlySalary.toFixed(2));
        }
    }

    function calculateContractValue() {
        const monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
        const durationMonths = parseInt($('#contract_duration_months').val()) || 0;
        const totalValue = monthlyCost * durationMonths;

        $('#total_contract_value').val(totalValue ? totalValue.toFixed(2) : '0.00');
        $('#summary-monthly-cost').text(monthlyCost.toLocaleString() + ' ريال');
        $('#summary-duration').text(durationMonths + ' شهر');
        $('#summary-total-value').text(totalValue.toLocaleString() + ' ريال');

        const dailyRate = monthlyCost / 30;
        $('#summary-daily-rate').text(dailyRate.toLocaleString() + ' ريال');
    }

    function updateSelectedPackageSummary(monthlyCost, durationMonths, totalValue) {
        return updateContractSummary(monthlyCost, durationMonths, totalValue);
        $('#summary-monthly-cost').text(monthlyCost.toLocaleString() + ' Ø±ÙŠØ§Ù„');
        $('#summary-duration').text(durationMonths + ' Ø´Ù‡Ø±');
        $('#summary-total-value').text(totalValue.toLocaleString() + ' Ø±ÙŠØ§Ù„');

        const dailyRate = monthlyCost / 30;
        $('#summary-daily-rate').text(dailyRate.toLocaleString() + ' Ø±ÙŠØ§Ù„');
    }

    function updateContractSummary(monthlyCost, durationMonths, totalValue) {
        const currencyLabel = '\u0631\u064a\u0627\u0644';
        const monthLabel = '\u0634\u0647\u0631';

        $('#summary-monthly-cost').text(monthlyCost.toLocaleString() + ' ' + currencyLabel);
        $('#summary-duration').text(durationMonths + ' ' + monthLabel);
        $('#summary-total-value').text(totalValue.toLocaleString() + ' ' + currencyLabel);

        const dailyRate = monthlyCost / 30;
        $('#summary-daily-rate').text(dailyRate.toLocaleString() + ' ' + currencyLabel);
    }

    function syncPaymentSummary() {
        const totalValue = parseFloat($('#total_contract_value').val()) || 0;
        const status = $('#payment_status').val();
        let paidAmount = parseFloat($('#paid_amount').val()) || 0;

        if (status === 'paid') {
            paidAmount = totalValue;
            $('#paid_amount').val(totalValue ? totalValue.toFixed(2) : '0.00');
        } else if (!['partial', 'paid'].includes(status)) {
            paidAmount = 0;
            $('#paid_amount').val('0.00');
        }

        paidAmount = Math.min(paidAmount, totalValue);
        const remainingAmount = Math.max(totalValue - paidAmount, 0);

        $('#remaining_amount').val(remainingAmount.toFixed(2));
        $('#paymentTotalValue').text(formatCurrency(totalValue));
        $('#paymentRemainingText').text(formatCurrency(remainingAmount));
    }

    function updatePaymentState() {
        enforcePaymentCompatibility();

        const paymentStatus = $('#payment_status').val();

        if (paymentStatus === 'paid') {
            $('#paidAmountWrapper').removeClass('d-none');
            $('#paid_amount').prop('readonly', true);
        } else if (paymentStatus === 'partial') {
            $('#paidAmountWrapper').removeClass('d-none');
            $('#paid_amount').prop('readonly', false);
        } else {
            $('#paidAmountWrapper').addClass('d-none');
            $('#paid_amount').prop('readonly', false);
        }

        syncPaymentSummary();
    }

    function enforcePaymentCompatibility() {
        const paymentType = $('#payment_type').val();
        const $paymentStatus = $('#payment_status');

        $paymentStatus.find('option').prop('disabled', false);

        if (paymentType === 'moyasar') {
            if ($paymentStatus.val() !== 'paid') {
                $paymentStatus.val('pending');
            }

            $paymentStatus.find('option').not('[value=""], [value="pending"], [value="paid"]').prop('disabled', true);
        } else if (paymentType === 'pay_later') {
            $paymentStatus.val('later');
            $paymentStatus.find('option').not('[value=""], [value="later"]').prop('disabled', true);
        } else {
            $paymentStatus.find('option[value="later"]').prop('disabled', true);

            if (paymentType !== 'bank_transfer') {
                $paymentStatus.find('option[value="bank_pending"]').prop('disabled', true);
            }

            if ($paymentStatus.val() === 'later' || ($paymentStatus.val() === 'bank_pending' && paymentType !== 'bank_transfer')) {
                $paymentStatus.val('');
            }
        }
    }

    function applyPackageData() {
        const selectedOption = $('#package_id').find('option:selected');
        const packageId = $('#package_id').val();

        if (!packageId) {
            $('#selected_package_id').val('');
            $('#total_contract_value').val('0.00');
            syncPaymentSummary();
            return;
        }

        $('#selected_package_id').val(packageId);

        const days = parseInt(selectedOption.data('days'), 10) || 0;
        const rentalType = selectedOption.data('rental-type') || '';
        const durationUnit = selectedOption.data('duration-unit') || '';
        const durationCount = parseInt(selectedOption.data('duration-count'), 10) || 0;
        const workingHours = parseInt(selectedOption.data('working-hours'), 10) || 0;
        const totalPrice = parseFloat(selectedOption.data('total')) || 0;
        const months = durationUnit === 'month' && durationCount
            ? durationCount
            : Math.max(1, Math.ceil((days || 30) / 30));
        const monthlyCost = (rentalType === 'monthly' || durationUnit === 'month') && months
            ? (totalPrice / months)
            : totalPrice;

        $('#rental_type').val(rentalType || 'monthly');
        $('#contract_duration_months').val(months);
        $('#contract_duration_days').val(days);
        $('#monthly_cost').val(monthlyCost ? monthlyCost.toFixed(2) : '');
        $('#total_contract_value').val(totalPrice ? totalPrice.toFixed(2) : '0.00');

        if (workingHours) {
            $('#working_hours_per_day').val(workingHours);
        }

        calculateIngazTo();
        updateContractSummary(monthlyCost, months, totalPrice);
        updatePaymentState();
    }

    function calculateDurationInDays() {
        const months = parseInt($('#contract_duration_months').val()) || 0;
        const days = months * 30;
        $('#contract_duration_days').val(days);
        calculateIngazTo();
    }

    function calculateIngazTo() {
        const ingazFrom = $('#ingaz_from').val();
        const durationDays = parseInt($('#contract_duration_days').val()) || 0;

        if (ingazFrom && durationDays) {
            const fromDate = new Date(ingazFrom);
            const toDate = new Date(fromDate.getTime() + (durationDays * 24 * 60 * 60 * 1000));
            $('#ingaz_to_display').val(toDate.toLocaleDateString('ar-SA'));
        }
    }

    function validateForm() {
        let isValid = true;
        const requiredFields = $('#editRentalContractForm').find('[required]');

        requiredFields.each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في البيانات',
                text: 'يرجى تعبئة جميع الحقول المطلوبة'
            });
            return false;
        }

        const totalContractValue = parseFloat($('#total_contract_value').val()) || 0;
        const paidAmount = parseFloat($('#paid_amount').val()) || 0;
        const paymentStatus = $('#payment_status').val();

        if (paymentStatus === 'partial' && paidAmount <= 0) {
            Swal.fire('خطأ', 'أدخل المبلغ المدفوع عند اختيار حالة مدفوع جزئياً', 'error');
            return false;
        }

        if (paymentStatus === 'partial' && paidAmount >= totalContractValue) {
            Swal.fire('خطأ', 'إذا تم سداد كامل المبلغ اختر حالة تم الدفع بدلاً من مدفوع جزئياً', 'error');
            return false;
        }

        return isValid;
    }

    $(document).on('click', '.delete-payment-receipt', function() {
        const button = $(this);

        Swal.fire({
            icon: 'warning',
            title: 'حذف الإيصال',
            text: 'هل تريد حذف هذا الإيصال؟',
            showCancelButton: true,
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            button.prop('disabled', true);

            $.ajax({
                url: button.data('delete-url'),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    button.closest('.payment-receipt-item').remove();

                    if (!$('#paymentReceiptsList .payment-receipt-item').length) {
                        $('#paymentReceiptsList').remove();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحذف',
                        text: response.message || 'تم حذف الإيصال بنجاح'
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'حدث خطأ أثناء حذف الإيصال';

                    button.prop('disabled', false);

                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: message
                    });
                }
            });
        });
    });

    function submitForm() {
        const submitBtn = $('#submit-btn');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

        const formData = new FormData($('#editRentalContractForm')[0]);
        formData.set('package_id', $('#package_id').val() || '');
        formData.set('selected_package_id', $('#package_id').val() || '');
        formData.set('rental_type', $('#rental_type').val() || 'monthly');
        formData.set('monthly_cost', $('#monthly_cost').val() || '0');
        formData.set('contract_duration_months', $('#contract_duration_months').val() || '1');
        formData.set('contract_duration_days', $('#contract_duration_days').val() || '1');
        formData.set('total_contract_value', $('#total_contract_value').val() || '0');
        formData.set('paid_amount', $('#paid_amount').val() || '0');
        formData.set('remaining_amount', $('#remaining_amount').val() || '0');

        $.ajax({
            url: $('#editRentalContractForm').attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: 'تم تحديث عقد التأجير بنجاح',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    if (response.receipt_created && response.receipt_bond_print_url) {
                        window.open(response.receipt_bond_print_url, '_blank');
                    }

                    window.location.href = response.redirect_url || "{{ route('RentContract.show', $contract->id) }}";
                });
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء تحديث العقد';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors)[0][0];
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: errorMessage
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Initialize calculations on page load
    calculateContractValue();
    updatePaymentState();
});
</script>
@endsection
