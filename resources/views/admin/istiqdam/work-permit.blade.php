@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
إضافة عاملة للعقد
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">إضافة عاملة للعقد رقم {{ $contractId }}</h4>
                    <p class="text-muted">تعيين عاملة لعقد الاستقدام في المنصة الحكومية</p>
                </div>
            </div>
        </div>

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-plus text-primary me-2"></i>
                إضافة عاملة للعقد
            </h3>
            <div>
                <a href="{{ route('istiqdam.contracts') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة للعقود
                </a>
            </div>
        </div>

        <!-- Contract Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-contract me-2"></i>
                    معلومات العقد
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>رقم العقد:</strong>
                        <p class="text-primary">{{ $contractId }}</p>
                    </div>
                    <div class="col-md-3">
                        <strong>حالة العقد:</strong>
                        <p><span class="badge bg-info">نشط</span></p>
                    </div>
                    <div class="col-md-3">
                        <strong>تاريخ الإنشاء:</strong>
                        <p>{{ date('Y-m-d') }}</p>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="loadContractDetails">
                            <i class="fas fa-eye me-1"></i>
                            عرض تفاصيل العقد
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Permit Assignment Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-id-card me-2"></i>
                    معلومات تصريح العمل والعاملة
                </h5>
            </div>
            <div class="card-body">
                <form id="workPermitForm">
                    @csrf

                    <!-- Work Permit Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary">
                                <i class="fas fa-certificate me-2"></i>
                                معلومات تصريح العمل
                            </h6>
                            <hr>
                        </div>
                        <div class="col-md-6">
                            <label for="work_permit_number" class="form-label">رقم تصريح العمل <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="work_permit_number" name="work_permit_number" required
                                   placeholder="أدخل رقم تصريح العمل">
                            <div class="form-text">رقم تصريح العمل الصادر من وزارة الموارد البشرية</div>
                        </div>
                        <div class="col-md-6">
                            <label for="maid_birthdate" class="form-label">تاريخ ميلاد العاملة <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="maid_birthdate" name="maid_birthdate" required>
                            <div class="form-text">يجب أن يطابق تاريخ الميلاد في جواز السفر</div>
                        </div>
                    </div>

                    <!-- Maid Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary">
                                <i class="fas fa-user me-2"></i>
                                اختيار العاملة
                            </h6>
                            <hr>
                        </div>
                        <div class="col-md-8">
                            <label for="maid_id" class="form-label">العاملة <span class="text-danger">*</span></label>
                            <select class="form-select" id="maid_id" name="maid_id" required>
                                <option value="">جاري تحميل العاملات المتاحة...</option>
                            </select>
                            <div class="form-text">سيتم عرض العاملات المتاحة فقط</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-info d-block" id="refreshMaidsBtn">
                                <i class="fas fa-sync-alt me-1"></i>
                                تحديث القائمة
                            </button>
                        </div>
                    </div>

                    <!-- Selected Maid Information -->
                    <div class="row mb-4" id="selectedMaidInfo" style="display: none;">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 text-primary">
                                        <i class="fas fa-user-check me-2"></i>
                                        معلومات العاملة المختارة
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="maidDetails">
                                        <div class="text-center">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">جاري التحميل...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary">
                                <i class="fas fa-sticky-note me-2"></i>
                                ملاحظات إضافية
                            </h6>
                            <hr>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="أدخل أي ملاحظات إضافية حول تعيين العاملة (اختياري)"></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-user-plus me-2"></i>
                                إضافة العاملة للعقد
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section("js")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    const contractId = '{{ $contractId }}';

    // Load available maids on page load
    loadAvailableMaids();

    // Handle maid selection change
    $('#maid_id').on('change', function() {
        const maidId = $(this).val();
        if (maidId) {
            showMaidInfo(maidId);
        } else {
            $('#selectedMaidInfo').hide();
        }
    });

    // Refresh maids button
    $('#refreshMaidsBtn').on('click', function() {
        loadAvailableMaids();
    });

    // Load contract details button
    $('#loadContractDetails').on('click', function() {
        loadContractDetails(contractId);
    });

    // Handle form submission
    $('#workPermitForm').on('submit', function(e) {
        e.preventDefault();
        assignWorkPermit();
    });

    function loadAvailableMaids() {
        $('#maid_id').html('<option value="">جاري تحميل العاملات المتاحة...</option>');

        // In a real implementation, this would fetch from your maids API
        // For now, we'll simulate with available maids data
        setTimeout(() => {
            const maids = @json($maids ?? []);
            let options = '<option value="">اختر العاملة</option>';

            if (maids.length > 0) {
                maids.forEach(function(maid) {
                    const nationalityName = maid.nationality ? maid.nationality.name : 'غير محدد';
                    options += `<option value="${maid.id}">${maid.name} - ${nationalityName} - ${maid.passport_num || 'لا يوجد جواز'}</option>`;
                });
            } else {
                options += '<option value="" disabled>لا توجد عاملات متاحة</option>';
            }

            $('#maid_id').html(options);
        }, 1000);
    }

    function showMaidInfo(maidId) {
        $('#selectedMaidInfo').show();
        $('#maidDetails').html(`
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحميل معلومات العاملة...</p>
            </div>
        `);

        // In a real implementation, you would fetch maid details from API
        // For now, we'll use the available data
        const maids = @json($maids ?? []);
        const selectedMaid = maids.find(maid => maid.id == maidId);

        if (selectedMaid) {
            setTimeout(() => {
                const html = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <img src="${selectedMaid.image || '/admin/assets/images/default-avatar.png'}"
                                     class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;"
                                     alt="صورة العاملة">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>الاسم:</strong></td>
                                    <td>${selectedMaid.name}</td>
                                </tr>
                                <tr>
                                    <td><strong>الجنسية:</strong></td>
                                    <td>${selectedMaid.nationality ? selectedMaid.nationality.name : 'غير محدد'}</td>
                                </tr>
                                <tr>
                                    <td><strong>رقم الجواز:</strong></td>
                                    <td>${selectedMaid.passport_num || 'غير محدد'}</td>
                                </tr>
                                <tr>
                                    <td><strong>العمر:</strong></td>
                                    <td>${selectedMaid.age || 'غير محدد'} سنة</td>
                                </tr>
                                <tr>
                                    <td><strong>الحالة:</strong></td>
                                    <td><span class="badge bg-success">متاحة</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                $('#maidDetails').html(html);
            }, 500);
        } else {
            $('#maidDetails').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    لم يتم العثور على معلومات العاملة
                </div>
            `);
        }
    }

    function loadContractDetails(contractId) {
        Swal.fire({
            title: 'تفاصيل العقد',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل تفاصيل العقد...</p>
                </div>
            `,
            showConfirmButton: false,
            allowOutsideClick: false
        });

        $.ajax({
            url: '{{ route("istiqdam.contracts.show", ":id") }}'.replace(':id', contractId),
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const contract = response.data;
                    Swal.fire({
                        title: `تفاصيل العقد رقم ${contractId}`,
                        html: `
                            <div class="text-start">
                                <div class="row">
                                    <div class="col-6"><strong>حالة العقد:</strong></div>
                                    <div class="col-6">${contract.status || 'غير محدد'}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>رقم الباقة:</strong></div>
                                    <div class="col-6">${contract.packageId || 'غير محدد'}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>تاريخ البداية:</strong></div>
                                    <div class="col-6">${contract.startDate ? new Date(contract.startDate).toLocaleDateString('ar-SA') : 'غير محدد'}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>المستفيد:</strong></div>
                                    <div class="col-6">${contract.beneficiary?.id || 'غير محدد'}</div>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'حسناً'
                    });
                } else {
                    Swal.fire('خطأ!', response.error || 'فشل في تحميل تفاصيل العقد', 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'حدث خطأ أثناء تحميل تفاصيل العقد', 'error');
            }
        });
    }

    function assignWorkPermit() {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();

        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html(`
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">جاري الإرسال...</span>
            </div>
            جاري إضافة العاملة...
        `);

        const formData = new FormData($('#workPermitForm')[0]);

        $.ajax({
            url: '{{ route("istiqdam.work-permit.assign", ":id") }}'.replace(':id', contractId),
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
                        confirmButtonText: 'عرض العقود',
                        cancelButtonText: 'إضافة عاملة أخرى',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route("istiqdam.contracts") }}';
                        } else {
                            // Reset form for new assignment
                            $('#workPermitForm')[0].reset();
                            $('#selectedMaidInfo').hide();
                            loadAvailableMaids();
                        }
                    });
                } else {
                    Swal.fire('خطأ!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إضافة العاملة';

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
    }
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

.table td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.table td:first-child {
    width: 30%;
}
</style>
@endsection
