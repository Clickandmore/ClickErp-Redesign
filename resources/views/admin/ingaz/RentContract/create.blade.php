@extends('admin.layout.indexs.index')
@section('page-title')
    {{helperTrans("ingaz.add Rent contract")}}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('admin/custom/admin-tables.css') }}">

<style>
/* Form Sections Styling */
.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border-radius: 12px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    position: relative;
}

.section-header {
    margin-bottom: 1.5rem;
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 2px 10px rgba(0,123,255,0.3);
}

.section-title {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

/* Form Groups */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.required-star {
    color: #dc3545;
    margin-left: 0.25rem;
    font-weight: bold;
}

/* Form Controls */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Select2 Styling */
.select2-container--default .select2-selection--single {
    height: 48px !important;
    border-radius: 8px !important;
    border: 1px solid #ced4da !important;
    padding: 0.75rem 1rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px !important;
    padding-left: 0 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px !important;
}

/* Buttons */
.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,123,255,0.4);
}

.btn-outline-secondary {
    border: 2px solid #6c757d;
    color: #6c757d;
    font-weight: 600;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-2px);
}

/* Contract Header */
.contract-header-icon {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Card Styling */
.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .form-section {
        padding: 1rem;
    }

    .section-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .d-flex.gap-3 {
        flex-direction: column;
        gap: 1rem !important;
    }

    .btn-lg {
        width: 100%;
    }
}

/* Loading Animation */
.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="contract-header-icon mb-3">
                    <i class="fas fa-handshake text-primary" style="font-size: 3rem;"></i>
                </div>
                <h3 class="text-primary mb-2">إنشاء عقد تأجير جديد</h3>
                <p class="text-muted mb-0">قم بتعبئة البيانات المطلوبة لإنشاء عقد تأجير احترافي</p>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    بيانات العقد
                </h5>
            </div>
            <div class="card-body">
                <form id="rentalContractForm" action="{{ route('RentContract.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Client Section -->
                    <div class="form-section">
                        <div class="section-header mb-4">
                            <div class="d-flex align-items-center">
                                <div class="section-icon me-3">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div>
                                    <h5 class="section-title mb-1">بيانات العميل</h5>
                                    <small class="text-muted">المعلومات الشخصية والمهنية للعميل</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">

                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    اختر عميل موجود
                                </label>
                                <select name="new_existing_client_id" id="new_existing_client_id" class="form-select select2" style="width:100%" required>
                                    <option value="" selected disabled>ابحث عن عميل بالاسم أو رقم الجوال أو الهوية...</option>
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
                                <div class="d-flex gap-2 mt-2">
                                    <a href="{{ route('clients.create') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user-plus me-1"></i> إضافة عميل جديد
                                    </a>
                                    <small class="text-muted align-self-center">أو ابحث في القائمة أعلاه</small>
                                </div>
                            </div>
                        </div>

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
                                <input type="text" class="form-control" id="client_phone" name="client_phone" required>
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
                                    <option value="">اختر الجنسية</option>
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
                                <label class="form-label">الدخل الشهري</label>
                                <input type="number" class="form-control" id="monthly_income" name="monthly_income" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">العنوان</label>
                                <input type="text" class="form-control" id="client_address" name="client_address">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">المدينة</label>
                                <input type="text" class="form-control" id="client_city" name="client_city">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد أفراد الأسرة</label>
                                <input type="number" class="form-control" id="family_num" name="family_num">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد الأطفال</label>
                                <input type="number" class="form-control" id="children" name="children">
                                                         </div>
                         </div>
                        </div>
                    </div>

                    <!-- Maid Section -->
                    <div class="form-section">
                        <div class="section-header mb-4">
                            <div class="d-flex align-items-center">
                                <div class="section-icon me-3" style="background: #e91e63;">
                                    <i class="fas fa-female text-white"></i>
                                </div>
                                <div>
                                    <h5 class="section-title mb-1">بيانات العاملة</h5>
                                    <small class="text-muted">اختيار العاملة المناسبة للتأجير</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">

                                                <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    اختر عاملة للتأجير
                                </label>
                                <select name="maid_id" id="maid_id" class="form-select select2" required>
                                    <option value="" selected disabled>ابحث عن عاملة بالاسم أو رقم الجواز أو الجنسية...</option>
                                    @if(isset($availableRentalMaids))
                                        @foreach($availableRentalMaids as $maid)
                                            <option value="{{$maid->id}}"
                                                data-name="{{$maid->name}}"
                                                data-passport="{{$maid->passport_num}}"
                                                data-nationality="{{$maid->nationality->title ?? ''}}"
                                                data-age="{{$maid->age}}"
                                                data-experience="{{$maid->experience_years}}"
                                                data-salary="{{$maid->salary}}"
                                                data-photo="{{$maid->photo}}">
                                                {{$maid->name}} - {{$maid->passport_num}} - {{$maid->nationality->title ?? ''}} - {{$maid->age}} سنة
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>لا توجد عاملات متاحة للتأجير حالياً</option>
                                    @endif
                                </select>
                                @if(!isset($availableRentalMaids) || $availableRentalMaids->count() == 0)
                                    <div class="alert alert-warning mt-2">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        لا توجد عاملات متاحة للتأجير حالياً. يرجى التواصل مع الإدارة.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contract Section -->
                    <div class="form-section">
                        <div class="section-header mb-4">
                            <div class="d-flex align-items-center">
                                <div class="section-icon me-3" style="background: #28a745;">
                                    <i class="fas fa-file-contract text-white"></i>
                                </div>
                                <div>
                                    <h5 class="section-title mb-1">تفاصيل العقد</h5>
                                    <small class="text-muted">المعلومات المالية والزمنية للعقد</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-4">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    تاريخ العقد
                                </label>
                                <input type="date" class="form-control" id="contract_date" name="contract_date" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">عدد ساعات العمل</label>
                                <input type="number" class="form-control" id="working_hours" name="working_hours" step="0.5">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">أجرة الساعة</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">الراتب الشهري</label>
                                <input type="number" class="form-control" id="monthly_salary" name="monthly_salary" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مبلغ نقل الكفالة</label>
                                <input type="number" class="form-control" id="sponsorship_transfer" name="sponsorship_transfer" step="0.01">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    التكلفة الشهرية
                                </label>
                                <input type="number" class="form-control" id="monthly_cost" name="monthly_cost" step="0.01" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مدة العقد (بالشهور)</label>
                                <input type="number" class="form-control" id="contract_duration_months" name="contract_duration_months" step="0.1">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">مدة العقد (بالأيام)</label>
                                <input type="number" class="form-control" id="contract_duration_days" name="contract_duration_days">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">قيمة العقد الإجمالية</label>
                                <input type="number" class="form-control" id="contract_value" name="contract_value" step="0.01" readonly>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">تاريخ بداية الإنجاز</label>
                                <input type="date" class="form-control" id="ingaz_start_date" name="ingaz_start_date">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">تاريخ نهاية الإنجاز</label>
                                <input type="date" class="form-control" id="ingaz_end_date" name="ingaz_end_date">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <span class="required-star">*</span>
                                    المسوق
                                </label>
                                <select class="form-select select2" id="marketer_id" name="marketer_id" required>
                                    <option value="">اختر المسوق</option>
                                    @foreach($marketers as $marketer)
                                        <option value="{{$marketer->id}}" {{ auth()->id() == $marketer->id ? 'selected' : '' }}>
                                            {{$marketer->name}}
                                        </option>
                                    @endforeach
                                </select>
                                                         </div>
                         </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-section">
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-center gap-3 pt-4 border-top">
                                    <a href="{{ route('RentContract.index') }}" class="btn btn-outline-secondary btn-lg px-5">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        العودة للقائمة
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submit-btn">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ عقد التأجير
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // تهيئة Select2
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

    // معالج تغيير العميل
    $('#new_existing_client_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');

        if (selectedOption.val()) {
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

            var nationalityId = selectedOption.data('nationality');
            if (nationalityId) {
                $('#client_nationality_id').val(nationalityId).trigger('change');
            }
        } else {
            // مسح البيانات
            $('#client_name, #client_phone, #client_email, #client_national_num, #client_birthdate, #client_address, #client_city, #family_num, #children, #profession').val('');
            $('#client_nationality_id').val('').trigger('change');
        }
    });

    // حساب قيمة العقد
    function calculateContractValue() {
        var monthlyCost = parseFloat($('#monthly_cost').val()) || 0;
        var durationMonths = parseFloat($('#contract_duration_months').val()) || 0;
        var totalValue = monthlyCost * durationMonths;
        $('#contract_value').val(totalValue.toFixed(2));
    }

    $('#monthly_cost, #contract_duration_months').on('input', calculateContractValue);

    // معالج إرسال النموذج
    $('#rentalContractForm').on('submit', function(e) {
        e.preventDefault();

        // التحقق من البيانات الأساسية
        if (!$('#new_existing_client_id').val() && !$('#client_name').val()) {
            Swal.fire('خطأ', 'يرجى اختيار عميل', 'error');
            return;
        }

        if (!$('#maid_id').val()) {
            Swal.fire('خطأ', 'يرجى اختيار عاملة', 'error');
            return;
        }

        // إرسال النموذج
        const submitBtn = $('#submit-btn');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: 'تم إنشاء عقد التأجير بنجاح'
                }).then(() => {
                    window.location.href = "{{ route('RentContract.index') }}";
                });
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء حفظ العقد';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors)[0][0];
                }

                Swal.fire('خطأ', errorMessage, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endsection
