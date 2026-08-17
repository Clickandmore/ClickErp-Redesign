@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
إنشاء عقد استقدام جديد
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">إنشاء عقد استقدام جديد</h4>
                    <p class="text-muted">إنشاء عقد جديد في منصة الاستقدام الحكومية</p>
                </div>
            </div>
        </div>

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-contract text-primary me-2"></i>
                إنشاء عقد جديد
            </h3>
            <div>
                <a href="{{ route('istiqdam.contracts') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة للعقود
                </a>
            </div>
        </div>

        <!-- Contract Form -->
        <form id="contractForm" enctype="multipart/form-data">
            @csrf

            <!-- Package Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-box me-2"></i>
                        اختيار الباقة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="package_id" class="form-label">رقم الباقة <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="package_id" name="package_id" required
                                   value="{{ request('package_id') }}" placeholder="أدخل رقم الباقة">
                            <div class="form-text">يمكنك العثور على رقم الباقة من صفحة الباقات</div>
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">تاريخ بداية العقد <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="special_specification" class="form-label">مواصفات خاصة</label>
                            <textarea class="form-control" id="special_specification" name="special_specification"
                                      rows="3" placeholder="أدخل أي مواصفات خاصة للعقد (اختياري)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Beneficiary Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المستفيد
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="beneficiary_id" class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="beneficiary_id" name="beneficiary_id" required
                                   placeholder="أدخل رقم الهوية">
                        </div>
                        <div class="col-md-6">
                            <label for="beneficiary_birthdate" class="form-label">تاريخ الميلاد <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="beneficiary_birthdate" name="beneficiary_birthdate" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        معلومات العنوان
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="city" class="form-label">المدينة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required
                                   placeholder="أدخل اسم المدينة">
                        </div>
                        <div class="col-md-4">
                            <label for="longitude" class="form-label">خط الطول <span class="text-danger">*</span></label>
                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" required
                                   placeholder="مثال: 46.6753">
                        </div>
                        <div class="col-md-4">
                            <label for="latitude" class="form-label">خط العرض <span class="text-danger">*</span></label>
                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" required
                                   placeholder="مثال: 24.7136">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-phone me-2"></i>
                        معلومات الاتصال
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="contact_person_name" class="form-label">اسم الشخص المسؤول <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_person_name" name="contact_person_name" required
                                   placeholder="أدخل اسم الشخص المسؤول">
                        </div>
                        <div class="col-md-6">
                            <label for="contact_person_phone" class="form-label">رقم هاتف الشخص المسؤول <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_person_phone" name="contact_person_phone" required
                                   placeholder="+966534201202">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="mobile_phone" class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="mobile_phone" name="mobile_phone" required
                                   placeholder="+966538459011">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="example@example.com">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="home_phone" class="form-label">هاتف المنزل</label>
                            <input type="tel" class="form-control" id="home_phone" name="home_phone"
                                   placeholder="+966136504803">
                        </div>
                        <div class="col-md-3">
                            <label for="second_phone" class="form-label">هاتف ثانوي</label>
                            <input type="tel" class="form-control" id="second_phone" name="second_phone"
                                   placeholder="+966598884847">
                        </div>
                        <div class="col-md-3">
                            <label for="work_phone" class="form-label">هاتف العمل</label>
                            <input type="tel" class="form-control" id="work_phone" name="work_phone"
                                   placeholder="+966166254176">
                        </div>
                        <div class="col-md-3">
                            <label for="redirect_phone" class="form-label">رقم التحويل</label>
                            <input type="text" class="form-control" id="redirect_phone" name="redirect_phone"
                                   placeholder="840">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        معلومات الدفع
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">اختر طريقة الدفع</option>
                                <option value="تحويل بنكي">تحويل بنكي</option>
                                <option value="بطاقة ائتمانية">بطاقة ائتمانية</option>
                                <option value="نقدي">نقدي</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="discount_percentage" class="form-label">نسبة الخصم (%)</label>
                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage"
                                   min="0" max="100" placeholder="50">
                        </div>
                        <div class="col-md-4">
                            <label for="down_payment_amount" class="form-label">مبلغ الدفعة المقدمة <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="down_payment_amount" name="down_payment_amount" required
                                   placeholder="3075">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="down_payment_due_date" class="form-label">تاريخ استحقاق الدفعة المقدمة <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="down_payment_due_date" name="down_payment_due_date" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commitments -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-handshake me-2"></i>
                        الالتزامات والشروط
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>التزامات الطرف الأول</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="notice_period" class="form-label">فترة الإشعار (ساعة) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="notice_period" name="notice_period" required
                                           placeholder="24">
                                </div>
                                <div class="col-md-6">
                                    <label for="replacement_times" class="form-label">عدد مرات الاستبدال <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="replacement_times" name="replacement_times" required
                                           placeholder="1">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>التزامات الطرف الثاني</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="travel_days" class="form-label">أيام السفر <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="travel_days" name="travel_days" required
                                           placeholder="1">
                                </div>
                                <div class="col-md-6">
                                    <label for="travel_hours" class="form-label">ساعات السفر <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="travel_hours" name="travel_hours" required
                                           placeholder="1">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="transportation_costs" class="form-label">تكاليف النقل <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="transportation_costs" name="transportation_costs" required
                                   placeholder="1">
                        </div>
                        <div class="col-md-4">
                            <label for="admin_fee_percent" class="form-label">نسبة الرسوم الإدارية (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" class="form-control" id="admin_fee_percent" name="admin_fee_percent" required
                                   placeholder="2.5">
                        </div>
                        <div class="col-md-4">
                            <label for="termination_fee" class="form-label">رسوم الإنهاء <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" class="form-control" id="termination_fee" name="termination_fee" required
                                   placeholder="2.5">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="card">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-save me-2"></i>
                        إنشاء العقد في منصة الاستقدام
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section("js")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('#start_date').val(today);
    $('#down_payment_due_date').val(today);

    // Handle form submission
    $('#contractForm').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();

        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html(`
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">جاري الإرسال...</span>
            </div>
            جاري إنشاء العقد...
        `);

        const formData = new FormData(this);

        $.ajax({
            url: '{{ route("istiqdam.contracts.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'نجح!',
                        text: response.message,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'عرض تفاصيل العقد',
                        cancelButtonText: 'إنشاء عقد آخر',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to contract details
                            window.location.href = '{{ route("istiqdam.contracts") }}';
                        } else {
                            // Reset form for new contract
                            $('#contractForm')[0].reset();
                            $('#start_date').val(today);
                            $('#down_payment_due_date').val(today);
                        }
                    });
                } else {
                    Swal.fire('خطأ!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إنشاء العقد';

                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    let errorList = '<ul>';
                    for (const field in errors) {
                        errors[field].forEach(function(error) {
                            errorList += `<li>${error}</li>`;
                        });
                    }
                    errorList += '</ul>';

                    Swal.fire({
                        title: 'بيانات غير صحيحة',
                        html: errorList,
                        icon: 'error'
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                    Swal.fire('خطأ!', errorMessage, 'error');
                } else {
                    Swal.fire('خطأ!', errorMessage, 'error');
                }
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Auto-fill coordinates for Riyadh as default
    $('#city').on('blur', function() {
        const city = $(this).val().toLowerCase();
        if (city === 'الرياض' || city === 'riyadh') {
            $('#longitude').val(46.6753);
            $('#latitude').val(24.7136);
        }
    });
});
</script>

<style>
.page-title-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.text-danger {
    color: #dc3545 !important;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}
</style>
@endsection
