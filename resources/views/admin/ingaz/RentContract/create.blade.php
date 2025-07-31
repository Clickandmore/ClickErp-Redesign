@extends('admin.layout.indexs.index')
@section('page-title')
{{helperTrans("ingaz.add Rent contract")}}
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
        padding: .675rem 2.25rem .375rem .75rem;
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

    .select2-container--default .select2-selection--single:focus {
        border-color: var(--primary-color);
    }

    .maid-preview {
        background: var(--primary-light);
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .maid-photo {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
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

    .alert-info {
        background: var(--primary-light);
        color: var(--primary-dark);
        border: 1px solid var(--primary-color);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .client-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 0.5rem;
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

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #ddd;
        padding: 4px 6px;
        outline: none;
    }

    .select2-dropdown {
        z-index: 9999 !important;
    }

    .select2-container--open .select2-dropdown {
        border: 1px solid var(--primary-color);
    }

    .select2-results__option {
        padding: 8px 12px;
        font-size: 0.9rem;
    }

    .select2-results__option--highlighted {
        background-color: var(--primary-color) !important;
        color: white !important;
    }
</style>
@endsection

@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))

    <!-- Contract Header -->
    <div class="contract-header">
        <div class="text-center">
            <h4 class="mb-2">
                <i class="fas fa-handshake me-2"></i>
                إنشاء عقد تأجير جديد
            </h4>
            <p class="mb-0 opacity-75">قم بتعبئة البيانات المطلوبة لإنشاء عقد تأجير احترافي</p>
        </div>
    </div>

    @if($endedOrders->count() > 0)
    <div class="alert alert-info">
        <h6 class="fw-bold mb-2">
            <i class="fas fa-info-circle me-2"></i>
            طلبات متاحة للتأجير
        </h6>
        <p class="mb-0">
            يوجد {{ $endedOrders->count() }} طلب تأجير مكتمل يمكن تحويله إلى عقد.
        </p>
    </div>
    @endif

    <form id="rentalContractForm" method="post" action="{{ route('RentContract.store') }}">
        @csrf
        <input type="hidden" name="contract_type" value="new_contract">

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
                <!-- Existing Client Selection -->
                <div class="col-md-6 mb-3">
                    <label for="new_existing_client_id" class="form-label">اختر عميل <span class="text-danger">*</span></label>
                    <select name="new_existing_client_id" id="new_existing_client_id" class="form-select select2" style="width:100%">
                      <option value="" selected disabled>اختر عميل...</option>
                      @if(isset($existing_clients))
                        @foreach($existing_clients as $client)
                          <option value="{{$client->id}}"
                            data-name="{{$client->name}}"
                            data-phone="{{$client->phone}}"
                            data-email="{{$client->email}}"
                            data-nationality="{{$client->nationality_id}}"
                            data-national-num="{{$client->national_num}}"
                            data-birthdate="{{$client->birthdate}}"
                            data-phone2="{{$client->phone2}}"
                            data-address="{{$client->address}}"
                            data-city="{{$client->city}}"
                            data-beds-num="{{$client->rooms_num}}"
                            data-family-num="{{$client->family_num}}"
                            data-children="{{$client->children}}"
                            data-job="{{$client->job}}">
                            {{$client->name}} - {{$client->phone}} - {{$client->national_num}}
                          </option>
                        @endforeach
                      @endif
                    </select>
                    <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary mt-2" title="إضافة عميل جديد"><i class="fas fa-user-plus"></i> إضافة عميل جديد</a>

                    <!-- Test Button -->
                    <button type="button" class="btn btn-warning btn-sm mt-2" onclick="testClientSelection()">
                        <i class="fas fa-bug"></i> اختبار التعبئة التلقائية
                    </button>
                  </div>
                <!-- Client Preview -->
                <div class="col-12" id="client-preview" style="display: none;">
                    <div class="client-preview">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-user-check me-2"></i>
                            بيانات العميل المختار
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small><strong>الاسم:</strong> <span id="preview-name">-</span></small><br>
                                <small><strong>الجوال:</strong> <span id="preview-phone">-</span></small><br>
                                <small><strong>المهنة:</strong> <span id="preview-profession">-</span></small>
                            </div>
                            <div class="col-md-6">
                                <small><strong>المدينة:</strong> <span id="preview-city">-</span></small><br>
                                <small><strong>الهوية:</strong> <span id="preview-national">-</span></small><br>
                                <small><strong>الدخل:</strong> <span id="preview-income">-</span></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            اسم العميل
                        </label>
                        <input type="text" class="form-control" id="client_name" name="client_name" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            رقم الجوال
                        </label>
                        <input type="tel" class="form-control" id="client_phone" name="client_phone" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            رقم الهوية
                        </label>
                        <input type="text" class="form-control" id="client_national_num" name="client_national_num" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الجنسية
                        </label>
                        <select class="form-select select2" id="client_nationality_id" name="client_nationality_id" required>
                            <option value="" disabled selected>اختر الجنسية...</option>
                            @foreach($nationalities as $nationality)
                                <option value="{{$nationality->id}}">
                                    {{ $nationality->translations->first()->title ?? $nationality->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="client_email" name="client_email">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            تاريخ الميلاد
                        </label>
                        <input type="date" class="form-control" id="client_birthdate" name="client_birthdate" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            المهنة
                        </label>
                        <input type="text" class="form-control" id="profession" name="profession" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الدخل الشهري (ريال)
                        </label>
                        <input type="number" class="form-control" id="monthly_income" name="monthly_income" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            العنوان
                        </label>
                        <input type="text" class="form-control" id="client_address" name="client_address" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            المدينة
                        </label>
                        <input type="text" class="form-control" id="client_city" name="client_city" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">عدد أفراد الأسرة</label>
                        <input type="number" class="form-control" id="family_num" name="family_num" min="1" max="20">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">عدد الأطفال</label>
                        <input type="number" class="form-control" id="children" name="children" min="0" max="15">
                    </div>
                </div>
            </div>
        </div>

        <!-- Maid Selection -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-female"></i>
                </div>
                <div>
                    <h5 class="section-title">اختيار العاملة</h5>
                    <p class="section-subtitle">اختر العاملة المناسبة للتأجير</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            اختر العاملة
                        </label>
                        <select name="maid_id" id="maid_id" class="form-select select2" required>
                            <option value="" disabled selected>اختر عاملة...</option>
                            @foreach($availableRentalMaids as $maid)
                                <option value="{{$maid->id}}"
                                    data-name="{{$maid->name}}"
                                    data-passport="{{$maid->passport_num}}"
                                    data-nationality="{{optional($maid->nationality->translations->first())->title ?? 'غير محدد'}}"
                                    data-religion="{{optional($maid->religion->translations->first())->title ?? 'غير محدد'}}"
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

                <!-- Maid Preview -->
                <div class="col-12" id="maid-details" style="display: none;">
                    <div class="maid-preview">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            معلومات العاملة المختارة
                        </h6>
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <img id="maid-photo" src="" alt="صورة العاملة" class="maid-photo">
                            </div>
                            <div class="col-md-10">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>الاسم:</strong> <span id="maid-name">-</span></p>
                                        <p class="mb-1"><strong>رقم الجواز:</strong> <span id="maid-passport">-</span></p>
                                        <p class="mb-1"><strong>الجنسية:</strong> <span id="maid-nationality">-</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>العمر:</strong> <span id="maid-age">-</span> سنة</p>
                                        <p class="mb-1"><strong>الخبرة:</strong> <span id="maid-experience">-</span> سنة</p>
                                        <p class="mb-1"><strong>الراتب:</strong> <span id="maid-salary">-</span> ريال</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Details -->
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
                            <option value="" disabled selected>اختر المسوق...</option>
                            @foreach($marketers as $marketer)
                                <option value="{{$marketer->id}}"
                                    @if(auth()->guard('admin')->user()->id == $marketer->id) selected @endif>
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
                        <input type="date" class="form-control" id="contract_date" name="contract_date" value="{{date('Y-m-d')}}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            ساعات العمل يومياً
                        </label>
                        <input type="number" class="form-control" id="working_hours_per_day" name="working_hours_per_day" min="1" max="24" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            أجرة الساعة (ريال)
                        </label>
                        <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">الراتب الشهري (ريال)</label>
                        <input type="number" class="form-control" id="monthly_salary" name="monthly_salary" min="0" step="0.01" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            مبلغ نقل الكفالة (ريال)
                        </label>
                        <input type="number" class="form-control" id="sponsorship_transfer_amount" name="sponsorship_transfer_amount" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الراتب بعد نقل الكفالة (ريال)
                        </label>
                        <input type="number" class="form-control" id="salary_after_sponsorship" name="salary_after_sponsorship" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            التكلفة الشهرية (ريال)
                        </label>
                        <input type="number" class="form-control" id="monthly_cost" name="monthly_cost" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            مدة العقد (شهور)
                        </label>
                        <input type="number" class="form-control" id="contract_duration_months" name="contract_duration_months" min="1" max="60" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">مدة العقد (أيام)</label>
                        <input type="number" class="form-control" id="contract_duration_days" name="contract_duration_days" min="1" readonly>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <span class="required-star">*</span>
                            الإنجاز من
                        </label>
                        <input type="date" class="form-control" id="ingaz_from" name="ingaz_from" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">الإنجاز إلى</label>
                        <input type="text" class="form-control" id="ingaz_to_display" readonly placeholder="سيتم حسابه تلقائياً">
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="أي ملاحظات أو شروط إضافية..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Summary -->
        <div class="contract-summary" id="contract-summary" style="display: none;">
            <h5 class="text-center mb-3">
                <i class="fas fa-calculator me-2"></i>
                ملخص العقد المالي
            </h5>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value" id="summary-monthly-cost">0 ريال</div>
                    <div class="summary-label">التكلفة الشهرية</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-duration">0 شهر</div>
                    <div class="summary-label">مدة العقد</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-total-value">0 ريال</div>
                    <div class="summary-label">قيمة العقد الإجمالية</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="summary-daily-rate">0 ريال</div>
                    <div class="summary-label">المعدل اليومي</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="{{ route('RentContract.index') }}" class="btn btn-secondary">
                <i class="fas fa-times me-2"></i>
                إلغاء
            </a>
            <button class="btn btn-primary" type="submit" id="submit-btn">
                <i class="fas fa-save me-2"></i>
                حفظ عقد التأجير
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
    // تهيئة Select2 - نفس الطريقة المستخدمة في عقود الاستقدام
    $('#new_existing_client_id').select2({
        placeholder: 'اختر عميل...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() { return "لا توجد نتائج"; },
            searching: function() { return "جاري البحث..."; }
        }
    });

    // تهيئة باقي Select2
    $('.select2').select2({
        placeholder: 'اختر...',
        allowClear: true,
        dir: 'rtl',
        width: '100%',
        language: {
            noResults: function() { return "لا توجد نتائج"; },
            searching: function() { return "جاري البحث..."; }
        }
    });

    // إعادة تهيئة Select2 بعد تحميل الصفحة للتأكد من عمل البحث
    setTimeout(function() {
        if ($.fn.select2) {
            $('#new_existing_client_id').select2('destroy').select2({
                placeholder: 'اختر عميل...',
                allowClear: true,
                dir: 'rtl',
                width: '100%',
                language: {
                    noResults: function() { return "لا توجد نتائج"; },
                    searching: function() { return "جاري البحث..."; }
                }
            });
        }
    }, 500);

    console.log('Select2 initialized. Client options count:', $('#new_existing_client_id option').length);

    // معالج تغيير العميل - نفس الطريقة المستخدمة في عقود الاستقدام
    $('#new_existing_client_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        console.log('Client selection changed:', selectedOption.val(), selectedOption.text());

        if (selectedOption.val()) {
            console.log('Populating client data...');

            // تعبئة بيانات العميل - نفس الطريقة المستخدمة في عقود الاستقدام
            $('#client_name').val(selectedOption.data('name') || '');
            $('#client_phone').val(selectedOption.data('phone') || '');
            $('#client_email').val(selectedOption.data('email') || '');
            $('#client_national_num').val(selectedOption.data('national-num') || '');
            $('#client_birthdate').val(selectedOption.data('birthdate') || '');
            $('#client_address').val(selectedOption.data('address') || '');
            $('#client_city').val(selectedOption.data('city') || '');
            $('#family_num').val(selectedOption.data('family-num') || '');
            $('#children').val(selectedOption.data('children') || '');
            $('#profession').val(selectedOption.data('job') || '');

            // معالجة الجنسية
            if (selectedOption.data('nationality')) {
                $('#client_nationality_id').val(selectedOption.data('nationality')).trigger('change');
            }

            console.log('Client data populated successfully');

            // إظهار معاينة العميل
            $('#client-preview').show();
            $('#preview-name').text(selectedOption.data('name') || '-');
            $('#preview-phone').text(selectedOption.data('phone') || '-');
            $('#preview-profession').text(selectedOption.data('job') || '-');
            $('#preview-city').text(selectedOption.data('city') || '-');
            $('#preview-national').text(selectedOption.data('national-num') || '-');
            $('#preview-income').text('-');

        } else {
            console.log('No client selected, clearing fields...');
            // مسح جميع الحقول
            $('#client_name, #client_phone, #client_email, #client_national_num, #client_address, #client_city, #profession').val('');
            $('#client_nationality_id, #client_birthdate, #monthly_income, #family_num, #children').val('');
            $('#client_nationality_id').trigger('change');
            $('#client-preview').hide();
        }
    });

    // Handle maid selection
    $('#maid_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            showMaidDetails(selectedOption);
        } else {
            hideMaidDetails();
        }
    });

    // Auto calculations
    $('#hourly_rate, #working_hours_per_day').on('input', function() {
        calculateMonthlySalary();
    });

    $('#monthly_cost, #contract_duration_months').on('input', function() {
        calculateContractValue();
        showContractSummary();
    });

    $('#contract_duration_months').on('input', function() {
        calculateDurationInDays();
    });

    $('#ingaz_from, #contract_duration_days').on('change', function() {
        calculateIngazTo();
    });

    // Set default values
    setDefaultValues();

    // Form validation and submission
    $('#rentalContractForm').submit(function(e) {
        e.preventDefault();

        if (validateForm()) {
            submitForm();
        }
    });

    // Functions
    function showMaidDetails(option) {
        $('#maid-details').show();
        $('#maid-photo').attr('src', option.data('photo') || '/images/no-image.png');
        $('#maid-name').text(option.data('name') || '-');
        $('#maid-passport').text(option.data('passport') || '-');
        $('#maid-nationality').text(option.data('nationality') || '-');
        $('#maid-age').text(option.data('age') || '-');
        $('#maid-experience').text(option.data('experience') || '-');

        const salary = option.data('salary');
        $('#maid-salary').text(salary ? parseFloat(salary).toLocaleString() : '-');
    }

    function hideMaidDetails() {
        $('#maid-details').hide();
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
            const formattedDate = toDate.toLocaleDateString('ar-SA');
            $('#ingaz_to_display').val(formattedDate);
        }
    }

    function showContractSummary() {
        const monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
        const duration = parseInt($('#contract_duration_months').val()) || 0;

        if (monthlyCost > 0 && duration > 0) {
            $('#contract-summary').show();
        }
    }

    function setDefaultValues() {
        @if(auth()->guard('admin')->check())
            $('#marketer_id').val('{{ auth()->guard("admin")->user()->id }}').trigger('change');
        @endif

        $('#contract_date').change(function() {
            if (!$('#ingaz_from').val()) {
                $('#ingaz_from').val($(this).val());
                calculateIngazTo();
            }
        });
    }

    function validateForm() {
        let isValid = true;
        const requiredFields = $('#rentalContractForm').find('[required]');

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
            url: $('#rentalContractForm').attr('action'),
            method: 'POST',
            data: new FormData($('#rentalContractForm')[0]),
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: 'تم إنشاء عقد التأجير بنجاح',
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.href = "{{ route('RentContract.index') }}";
                });
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء حفظ العقد';

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
});

// دالة اختبار التعبئة التلقائية
function testClientSelection() {
    console.log('=== بدء اختبار التعبئة التلقائية ===');

    // اختبار وجود العناصر
    var selectElement = document.getElementById('new_existing_client_id');
    console.log('1. عنصر القائمة موجود:', !!selectElement);
    console.log('2. عدد الخيارات:', selectElement ? selectElement.options.length : 0);

    if (selectElement && selectElement.options.length > 1) {
        // اختبار أول عميل
        var firstOption = selectElement.options[1]; // تجاهل الخيار الفارغ
        console.log('3. أول عميل:', {
            value: firstOption.value,
            text: firstOption.text,
            'data-name': firstOption.getAttribute('data-name'),
            'data-phone': firstOption.getAttribute('data-phone'),
            'data-email': firstOption.getAttribute('data-email')
        });

        // محاولة اختيار العميل برمجياً
        console.log('4. محاولة اختيار العميل...');
        $('#new_existing_client_id').val(firstOption.value).trigger('change');

        // فحص الحقول بعد التعبئة
        setTimeout(function() {
            console.log('5. فحص الحقول بعد التعبئة:');
            console.log('   - اسم العميل:', $('#client_name').val());
            console.log('   - رقم الجوال:', $('#client_phone').val());
            console.log('   - البريد الإلكتروني:', $('#client_email').val());
            console.log('   - رقم الهوية:', $('#client_national_num').val());

            // إظهار النتيجة
            var isWorking = $('#client_name').val() !== '';
            alert(isWorking ?
                'التعبئة التلقائية تعمل! ✅\nتم تعبئة: ' + $('#client_name').val() :
                'التعبئة التلقائية لا تعمل! ❌\nتحقق من Console للتفاصيل'
            );
        }, 500);
    } else {
        console.log('❌ لا توجد خيارات في القائمة');
        alert('لا توجد عملاء في القائمة!');
    }
}

// اختبار تلقائي عند تحميل الصفحة
$(document).ready(function() {
    setTimeout(function() {
        console.log('🔍 اختبار تلقائي للنظام...');
        console.log('Select2 جاهز:', $('#new_existing_client_id').hasClass('select2-hidden-accessible'));
        console.log('عدد العملاء:', $('#new_existing_client_id option').length - 1);
    }, 2000);
});
</script>
@endsection
