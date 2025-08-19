@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
عقود الاستقدام
@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">عقود الاستقدام</h4>
                    <p class="text-muted">إدارة عقود الاستقدام في المنصة الحكومية</p>
                </div>
            </div>
        </div>

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-contract text-primary me-2"></i>
                عقود الاستقدام
            </h3>
            <div>
                <a href="{{ route('istiqdam.index') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة للرئيسية
                </a>
                <a href="{{ route('istiqdam.contracts.create') }}" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>
                    إنشاء عقد جديد
                </a>
            </div>
        </div>

        <!-- Contract Management Tools -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>البحث عن عقد</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="contractIdSearch"
                                   placeholder="أدخل رقم العقد للبحث">
                            <button class="btn btn-primary" type="button" id="searchContractBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>أدوات سريعة</h6>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info" id="refreshBtn">
                                <i class="fas fa-sync-alt me-1"></i>
                                تحديث
                            </button>
                            <button type="button" class="btn btn-warning" id="testConnectionBtn">
                                <i class="fas fa-wifi me-1"></i>
                                اختبار الاتصال
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contracts List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة العقود المنشأة
                </h5>
            </div>
            <div class="card-body">
                <div id="contractsContainer">
                    <div class="text-center">
                        <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                        <h5>لا توجد عقود محفوظة محلياً</h5>
                        <p class="text-muted">العقود المنشأة في منصة الاستقدام ستظهر هنا</p>
                        <a href="{{ route('istiqdam.contracts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            إنشاء عقد جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contract Details Modal -->
    <div class="modal fade" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contractModalLabel">تفاصيل العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="contractModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-info" id="downloadContractBtn" style="display: none;">
                        <i class="fas fa-download me-1"></i>
                        تحميل العقد
                    </button>
                    <button type="button" class="btn btn-warning" id="addWorkPermitBtn" style="display: none;">
                        <i class="fas fa-user-plus me-1"></i>
                        إضافة عاملة
                    </button>
                    <button type="button" class="btn btn-danger" id="cancelContractBtn" style="display: none;">
                        <i class="fas fa-times me-1"></i>
                        إلغاء العقد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Work Permit Assignment Modal -->
    <div class="modal fade" id="workPermitModal" tabindex="-1" aria-labelledby="workPermitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="workPermitModalLabel">إضافة عاملة للعقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="workPermitForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="work_permit_number" class="form-label">رقم تصريح العمل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="work_permit_number" name="work_permit_number" required>
                            </div>
                            <div class="col-md-6">
                                <label for="maid_birthdate" class="form-label">تاريخ ميلاد العاملة <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="maid_birthdate" name="maid_birthdate" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="maid_id" class="form-label">اختيار العاملة <span class="text-danger">*</span></label>
                                <select class="form-select" id="maid_id" name="maid_id" required>
                                    <option value="">اختر العاملة</option>
                                </select>
                                <div class="form-text">سيتم عرض العاملات المتاحة فقط</div>
                            </div>
                        </div>
                        <div class="row mt-3" id="selectedMaidInfo" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6>معلومات العاملة المختارة:</h6>
                                    <div id="maidDetails"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            إضافة العاملة
                        </button>
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
    let currentContractId = null;

    // Test connection button
    $('#testConnectionBtn').on('click', function() {
        testConnection();
    });

    // Search contract button
    $('#searchContractBtn').on('click', function() {
        const contractId = $('#contractIdSearch').val().trim();
        if (contractId) {
            searchContract(contractId);
        } else {
            Swal.fire('تنبيه!', 'يرجى إدخال رقم العقد', 'warning');
        }
    });

    // Allow Enter key for search
    $('#contractIdSearch').on('keypress', function(e) {
        if (e.which === 13) {
            $('#searchContractBtn').click();
        }
    });

    // Download contract button
    $('#downloadContractBtn').on('click', function() {
        if (currentContractId) {
            downloadContract(currentContractId);
        }
    });

    // Add work permit button
    $('#addWorkPermitBtn').on('click', function() {
        if (currentContractId) {
            showWorkPermitModal(currentContractId);
        }
    });

    // Cancel contract button
    $('#cancelContractBtn').on('click', function() {
        if (currentContractId) {
            cancelContract(currentContractId);
        }
    });

    // Work permit form submission
    $('#workPermitForm').on('submit', function(e) {
        e.preventDefault();
        if (currentContractId) {
            assignWorkPermit(currentContractId);
        }
    });

    // Maid selection change
    $('#maid_id').on('change', function() {
        const maidId = $(this).val();
        if (maidId) {
            showMaidInfo(maidId);
        } else {
            $('#selectedMaidInfo').hide();
        }
    });

    function testConnection() {
        $.ajax({
            url: '{{ route("istiqdam.test.connection") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    Swal.fire('نجح!', response.message, 'success');
                } else {
                    Swal.fire('فشل!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'حدث خطأ أثناء اختبار الاتصال', 'error');
            }
        });
    }

    function searchContract(contractId) {
        $('#contractModalBody').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري البحث...</span>
                </div>
                <p class="mt-2">جاري البحث عن العقد رقم ${contractId}...</p>
            </div>
        `);

        $('#contractModal').modal('show');
        currentContractId = contractId;

        $.ajax({
            url: '{{ route("istiqdam.contracts.show", ":id") }}'.replace(':id', contractId),
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    displayContractDetails(response.data);
                    $('#downloadContractBtn, #addWorkPermitBtn, #cancelContractBtn').show();
                } else {
                    $('#contractModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.error || 'لم يتم العثور على العقد'}
                        </div>
                    `);
                    $('#downloadContractBtn, #addWorkPermitBtn, #cancelContractBtn').hide();
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء البحث عن العقد';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }

                $('#contractModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${errorMessage}
                    </div>
                `);
                $('#downloadContractBtn, #addWorkPermitBtn, #cancelContractBtn').hide();
            }
        });
    }

    function displayContractDetails(contract) {
        const html = `
            <div class="contract-details">
                <div class="row">
                    <div class="col-md-6">
                        <h6>معلومات العقد الأساسية</h6>
                        <table class="table table-sm">
                            <tr><td><strong>رقم العقد:</strong></td><td>${contract.id || 'غير محدد'}</td></tr>
                            <tr><td><strong>حالة العقد:</strong></td><td><span class="badge bg-primary">${contract.status || 'غير محدد'}</span></td></tr>
                            <tr><td><strong>تاريخ البداية:</strong></td><td>${contract.startDate ? new Date(contract.startDate).toLocaleDateString('ar-SA') : 'غير محدد'}</td></tr>
                            <tr><td><strong>رقم الباقة:</strong></td><td>${contract.packageId || 'غير محدد'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>معلومات المستفيد</h6>
                        <table class="table table-sm">
                            <tr><td><strong>رقم الهوية:</strong></td><td>${contract.beneficiary?.id || 'غير محدد'}</td></tr>
                            <tr><td><strong>تاريخ الميلاد:</strong></td><td>${contract.beneficiary?.birthdate ? new Date(contract.beneficiary.birthdate).toLocaleDateString('ar-SA') : 'غير محدد'}</td></tr>
                            <tr><td><strong>المدينة:</strong></td><td>${contract.details?.beneficiary?.address?.city || 'غير محدد'}</td></tr>
                            <tr><td><strong>البريد الإلكتروني:</strong></td><td>${contract.details?.beneficiary?.contact?.email || 'غير محدد'}</td></tr>
                        </table>
                    </div>
                </div>

                ${contract.details?.beneficiary?.contact ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>معلومات الاتصال</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>الجوال:</strong><br>
                                    ${contract.details.beneficiary.contact.phoneNumber?.mobile || 'غير محدد'}
                                </div>
                                <div class="col-md-3">
                                    <strong>هاتف المنزل:</strong><br>
                                    ${contract.details.beneficiary.contact.phoneNumber?.home || 'غير محدد'}
                                </div>
                                <div class="col-md-3">
                                    <strong>هاتف العمل:</strong><br>
                                    ${contract.details.beneficiary.contact.phoneNumber?.work || 'غير محدد'}
                                </div>
                                <div class="col-md-3">
                                    <strong>الشخص المسؤول:</strong><br>
                                    ${contract.details.beneficiary.contact.person?.name || 'غير محدد'}
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${contract.details?.payment ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>معلومات الدفع</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>طريقة الدفع:</strong><br>
                                    ${contract.details.payment.paymentMethod || 'غير محدد'}
                                </div>
                                <div class="col-md-4">
                                    <strong>نسبة الخصم:</strong><br>
                                    ${contract.details.payment.discountPercentage || 0}%
                                </div>
                                <div class="col-md-4">
                                    <strong>تفاصيل الدفع:</strong><br>
                                    ${contract.details.payment.paymentDetails?.length || 0} دفعة
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}

                ${contract.details?.commitments ? `
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>التزامات الطرف الأول</h6>
                            <ul>
                                <li>فترة الإشعار: ${contract.details.commitments.firstParty?.noticePeriod || 'غير محدد'} ساعة</li>
                                <li>مرات الاستبدال: ${contract.details.commitments.firstParty?.replacementTimes || 'غير محدد'}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>التزامات الطرف الثاني</h6>
                            <ul>
                                <li>تكاليف النقل: ${contract.details.commitments.secondParty?.laborerTransportationCosts || 'غير محدد'}</li>
                                <li>نسبة الرسوم الإدارية: ${contract.details.commitments.secondParty?.administrativeFeePercent || 'غير محدد'}%</li>
                                <li>رسوم الإنهاء: ${contract.details.commitments.secondParty?.terminationFee || 'غير محدد'}</li>
                            </ul>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        $('#contractModalBody').html(html);
    }

    function downloadContract(contractId) {
        const downloadUrl = '{{ route("istiqdam.contracts.download", [":id", "pdf"]) }}'.replace(':id', contractId);
        window.open(downloadUrl, '_blank');
    }

    function showWorkPermitModal(contractId) {
        $('#workPermitModal').modal('show');
        loadAvailableMaids();
    }

    function loadAvailableMaids() {
        // Load available maids - you might need to create an endpoint for this
        // For now, we'll use a placeholder
        $('#maid_id').html('<option value="">جاري تحميل العاملات...</option>');

        // This would typically load from your maids endpoint
        // $.ajax({...});

        // Placeholder options
        setTimeout(() => {
            $('#maid_id').html(`
                <option value="">اختر العاملة</option>
                <option value="1">فاطمة أحمد - فلبين</option>
                <option value="2">سارة محمد - إندونيسيا</option>
                <option value="3">مريم علي - بنجلاديش</option>
            `);
        }, 1000);
    }

    function showMaidInfo(maidId) {
        // Show selected maid information
        const maidInfo = {
            '1': { name: 'فاطمة أحمد', nationality: 'فلبين', passport: 'P1234567' },
            '2': { name: 'سارة محمد', nationality: 'إندونيسيا', passport: 'I7654321' },
            '3': { name: 'مريم علي', nationality: 'بنجلاديش', passport: 'B9876543' }
        };

        const maid = maidInfo[maidId];
        if (maid) {
            $('#maidDetails').html(`
                <strong>الاسم:</strong> ${maid.name}<br>
                <strong>الجنسية:</strong> ${maid.nationality}<br>
                <strong>رقم الجواز:</strong> ${maid.passport}
            `);
            $('#selectedMaidInfo').show();
        }
    }

    function assignWorkPermit(contractId) {
        const formData = new FormData($('#workPermitForm')[0]);

        $.ajax({
            url: '{{ route("istiqdam.work-permit.assign", ":id") }}'.replace(':id', contractId),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire('نجح!', response.message, 'success');
                    $('#workPermitModal').modal('hide');
                    $('#workPermitForm')[0].reset();
                    $('#selectedMaidInfo').hide();
                } else {
                    Swal.fire('خطأ!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء إضافة العاملة';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire('خطأ!', errorMessage, 'error');
            }
        });
    }

    function cancelContract(contractId) {
        Swal.fire({
            title: 'تأكيد إلغاء العقد',
            text: 'هل أنت متأكد من إلغاء هذا العقد؟ هذا الإجراء لا يمكن التراجع عنه.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، إلغاء العقد',
            cancelButtonText: 'تراجع'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("istiqdam.contracts.cancel", ":id") }}'.replace(':id', contractId),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('تم الإلغاء!', response.message, 'success');
                            $('#contractModal').modal('hide');
                        } else {
                            Swal.fire('خطأ!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء إلغاء العقد';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('خطأ!', errorMessage, 'error');
                    }
                });
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

.contract-details .table td {
    border: none;
    padding: 0.25rem 0.5rem;
}

.contract-details .table td:first-child {
    width: 40%;
}

.badge {
    font-size: 0.875em;
}
</style>
@endsection
