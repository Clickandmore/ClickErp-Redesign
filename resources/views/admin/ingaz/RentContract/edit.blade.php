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

    <form id="editRentalContractForm" method="post" action="{{ route('RentContract.update', $contract->id) }}">
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
                            رقم الهوية
                        </label>
                        <input type="text" class="form-control" id="client_national_num" name="client_national_num"
                               value="{{ $contract->user->national_num ?? '' }}" required>
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
                            @foreach($nationalities as $nationality)
                                <option value="{{$nationality->id}}"
                                    {{ ($contract->user->nationality_id ?? '') == $nationality->id ? 'selected' : '' }}>
                                    {{ $nationality->translations->first()->title ?? $nationality->name }}
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

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            تاريخ الميلاد
                        </label>
                        <input type="date" class="form-control" id="client_birthdate" name="client_birthdate"
                               value="{{ $contract->user->birthdate ? $contract->user->birthdate->format('Y-m-d') : '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            المهنة
                        </label>
                        <input type="text" class="form-control" id="profession" name="profession"
                               value="{{ $contract->user->profession ?? '' }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الدخل الشهري (ريال)
                        </label>
                        <input type="number" class="form-control" id="monthly_income" name="monthly_income"
                               value="{{ $contract->user->monthly_income ?? '' }}" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            العنوان
                        </label>
                        <input type="text" class="form-control" id="client_address" name="client_address"
                               value="{{ $contract->user->address ?? '' }}" required>
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
                                    data-salary="{{$maid->salary}}"
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
                        <label class="form-label">
                            <span class="required-star">*</span>
                            ساعات العمل يومياً
                        </label>
                        <input type="number" class="form-control" id="working_hours_per_day" name="working_hours_per_day"
                               value="{{ $contract->info->working_hours_per_day ?? '' }}" min="1" max="24" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            أجرة الساعة (ريال)
                        </label>
                        <input type="number" class="form-control" id="hourly_rate" name="hourly_rate"
                               value="{{ $contract->info->hourly_rate ?? '' }}" min="0" step="0.01" required>
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
                        <label class="form-label">
                            <span class="required-star">*</span>
                            مبلغ نقل الكفالة (ريال)
                        </label>
                        <input type="number" class="form-control" id="sponsorship_transfer_amount" name="sponsorship_transfer_amount"
                               value="{{ $contract->info->sponsorship_transfer_amount ?? '' }}" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الراتب بعد نقل الكفالة (ريال)
                        </label>
                        <input type="number" class="form-control" id="salary_after_sponsorship" name="salary_after_sponsorship"
                               value="{{ $contract->info->salary_after_sponsorship ?? '' }}" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            التكلفة الشهرية (ريال)
                        </label>
                        <input type="number" class="form-control" id="monthly_cost" name="monthly_cost"
                               value="{{ $contract->info->monthly_cost ?? '' }}" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            مدة العقد (شهور)
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

@section('script')
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
    });

    $('#contract_duration_months').on('input', function() {
        calculateDurationInDays();
    });

    $('#ingaz_from, #contract_duration_days').on('change', function() {
        calculateIngazTo();
    });

    // Form submission
    $('#editRentalContractForm').submit(function(e) {
        e.preventDefault();

        if (validateForm()) {
            submitForm();
        }
    });

    // Functions
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

        $('#summary-monthly-cost').text(monthlyCost.toLocaleString() + ' ريال');
        $('#summary-duration').text(durationMonths + ' شهر');
        $('#summary-total-value').text(totalValue.toLocaleString() + ' ريال');

        const dailyRate = monthlyCost / 30;
        $('#summary-daily-rate').text(dailyRate.toLocaleString() + ' ريال');
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
        }

        return isValid;
    }

    function submitForm() {
        const submitBtn = $('#submit-btn');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

        $.ajax({
            url: $('#editRentalContractForm').attr('action'),
            method: 'POST',
            data: new FormData($('#editRentalContractForm')[0]),
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: 'تم تحديث عقد التأجير بنجاح',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.href = "{{ route('RentContract.show', $contract->id) }}";
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
});
</script>
@endsection
