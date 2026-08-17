@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
إنشاء عقد استقدام تجريبي
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-file-contract me-2"></i>إنشاء عقد استقدام تجريبي</h4>
                    <p class="mb-0">إنشاء عقد تجريبي للاختبار - جاري تطوير التكامل مع منصة الاستقدام الفعلية</p>
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
                            <select class="form-select" id="package_id" name="package_id" required>
                                <option value="">اختر الباقة</option>
                                <option value="1001" {{ request('package_id') == '1001' ? 'selected' : '' }}>1001 - خادمة منزلية من الفلبين</option>
                                <option value="1002" {{ request('package_id') == '1002' ? 'selected' : '' }}>1002 - طباخة من إندونيسيا</option>
                                <option value="1003" {{ request('package_id') == '1003' ? 'selected' : '' }}>1003 - عاملة نظافة من بنجلاديش</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">تاريخ بداية العقد <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
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
                                   placeholder="مثال: 1234567890" value="1234567890">
                        </div>
                        <div class="col-md-6">
                            <label for="beneficiary_birthdate" class="form-label">تاريخ الميلاد <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="beneficiary_birthdate" name="beneficiary_birthdate" required>
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
                                   placeholder="أحمد محمد" value="أحمد محمد">
                        </div>
                        <div class="col-md-6">
                            <label for="mobile_phone" class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="mobile_phone" name="mobile_phone" required
                                   placeholder="+966501234567" value="+966501234567">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="example@example.com" value="test@example.com">
                        </div>
                        <div class="col-md-6">
                            <label for="city" class="form-label">المدينة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" name="city" required
                                   placeholder="الرياض" value="الرياض">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات إضافية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="أدخل أي ملاحظات إضافية (اختياري)">عقد تجريبي للاختبار</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="card">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-save me-2"></i>
                        إنشاء العقد التجريبي
                    </button>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            هذا عقد تجريبي للاختبار ولن يتم إرساله لمنصة الاستقدام الفعلية
                        </small>
                    </div>
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
    $('#beneficiary_birthdate').val('1985-05-15');

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
            جاري إنشاء العقد التجريبي...
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
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>${response.message}</h5>
                                <p><strong>رقم العقد:</strong> <code>${response.contract_id}</code></p>
                                <small class="text-muted">يمكنك البحث عن هذا العقد في صفحة العقود</small>
                            </div>
                        `,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'الذهاب للعقود',
                        cancelButtonText: 'إنشاء عقد آخر',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("istiqdam.contracts") }}';
                        } else {
                            // Reset form for new contract
                            $('#contractForm')[0].reset();
                            $('#start_date').val(today);
                            $('#beneficiary_birthdate').val('1985-05-15');
                            $('#beneficiary_id').val('1234567890');
                            $('#contact_person_name').val('أحمد محمد');
                            $('#mobile_phone').val('+966501234567');
                            $('#email').val('test@example.com');
                            $('#city').val('الرياض');
                            $('#notes').val('عقد تجريبي للاختبار');
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
                    let errorList = '<ul class="text-start">';
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

    // Package selection change
    $('#package_id').on('change', function() {
        const packageId = $(this).val();
        if (packageId) {
            showPackageInfo(packageId);
        }
    });

    function showPackageInfo(packageId) {
        const packages = {
            '1001': { name: 'خادمة منزلية من الفلبين', duration: '24 شهر', price: '15,000 ريال' },
            '1002': { name: 'طباخة من إندونيسيا', duration: '24 شهر', price: '18,000 ريال' },
            '1003': { name: 'عاملة نظافة من بنجلاديش', duration: '12 شهر', price: '12,000 ريال' }
        };

        const package = packages[packageId];
        if (package) {
            Swal.fire({
                title: 'تفاصيل الباقة المختارة',
                html: `
                    <div class="text-start">
                        <p><strong>النوع:</strong> ${package.name}</p>
                        <p><strong>المدة:</strong> ${package.duration}</p>
                        <p><strong>السعر:</strong> ${package.price}</p>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'حسناً'
            });
        }
    }

    // Show package info if pre-selected
    if ($('#package_id').val()) {
        setTimeout(() => {
            showPackageInfo($('#package_id').val());
        }, 1000);
    }
});
</script>

<style>
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
