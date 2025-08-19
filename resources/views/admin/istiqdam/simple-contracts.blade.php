@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
عقود الاستقدام
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-file-contract me-2"></i>عقود الاستقدام التجريبية</h4>
                    <p class="mb-0">إدارة العقود التجريبية - جاري تطوير التكامل مع منصة الاستقدام الفعلية</p>
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
                                   placeholder="أدخل رقم العقد للبحث (مثال: IST-20250816-123456)">
                            <button class="btn btn-primary" type="button" id="searchContractBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <small class="text-muted">جرب البحث عن: IST-20250816-123456</small>
                    </div>
                    <div class="col-md-6">
                        <h6>أدوات سريعة</h6>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-info" onclick="showDemoContracts()">
                                <i class="fas fa-list me-1"></i>
                                عقود تجريبية
                            </button>
                            <button type="button" class="btn btn-warning" id="testConnectionBtn">
                                <i class="fas fa-wifi me-1"></i>
                                اختبار النظام
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
                    قائمة العقود
                </h5>
            </div>
            <div class="card-body">
                <div id="contractsContainer">
                    <div class="text-center">
                        <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                        <h5>لا توجد عقود محفوظة</h5>
                        <p class="text-muted">العقود المنشأة ستظهر هنا</p>
                        <div class="mt-3">
                            <a href="{{ route('istiqdam.contracts.create') }}" class="btn btn-primary me-2">
                                <i class="fas fa-plus me-1"></i>
                                إنشاء عقد جديد
                            </a>
                            <button class="btn btn-outline-info" onclick="showDemoContracts()">
                                <i class="fas fa-eye me-1"></i>
                                عرض عقود تجريبية
                            </button>
                        </div>
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
            window.location.href = '{{ route("istiqdam.work-permit.form", ":id") }}'.replace(':id', currentContractId);
        }
    });

    // Cancel contract button
    $('#cancelContractBtn').on('click', function() {
        if (currentContractId) {
            cancelContract(currentContractId);
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
                Swal.fire('خطأ!', 'حدث خطأ أثناء اختبار النظام', 'error');
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
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            العقد رقم ${contractId} غير موجود أو هذا عقد تجريبي.
                            <hr>
                            <small>جرب البحث عن: IST-20250816-123456</small>
                        </div>
                    `);
                    $('#downloadContractBtn, #addWorkPermitBtn, #cancelContractBtn').hide();
                }
            },
            error: function(xhr) {
                $('#contractModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء البحث عن العقد
                    </div>
                `);
                $('#downloadContractBtn, #addWorkPermitBtn, #cancelContractBtn').hide();
            }
        });
    }

    function displayContractDetails(contract) {
        const html = `
            <div class="contract-details">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>تم العثور على العقد!</strong> هذا عقد تجريبي للاختبار.
                </div>
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
                                    <strong>الشخص المسؤول:</strong><br>
                                    ${contract.details.beneficiary.contact.person?.name || 'غير محدد'}
                                </div>
                                <div class="col-md-3">
                                    <strong>طريقة الدفع:</strong><br>
                                    ${contract.details?.payment?.paymentMethod || 'غير محدد'}
                                </div>
                            </div>
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

    function cancelContract(contractId) {
        Swal.fire({
            title: 'تأكيد إلغاء العقد',
            text: 'هل أنت متأكد من إلغاء هذا العقد التجريبي؟',
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
                        Swal.fire('خطأ!', 'حدث خطأ أثناء إلغاء العقد', 'error');
                    }
                });
            }
        });
    }
});

function showDemoContracts() {
    const demoContracts = [
        { id: 'IST-20250816-123456', status: 'نشط', beneficiary: '1234567890', package: '1001' },
        { id: 'IST-20250816-789012', status: 'مكتمل', beneficiary: '9876543210', package: '1002' },
        { id: 'IST-20250816-345678', status: 'مسودة', beneficiary: '5555555555', package: '1003' }
    ];

    let html = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><strong>عقود تجريبية للاختبار</strong></div>';
    html += '<div class="table-responsive"><table class="table table-striped">';
    html += '<thead><tr><th>رقم العقد</th><th>الحالة</th><th>رقم المستفيد</th><th>رقم الباقة</th><th>الإجراءات</th></tr></thead><tbody>';

    demoContracts.forEach(function(contract) {
        const statusClass = contract.status === 'نشط' ? 'success' : contract.status === 'مكتمل' ? 'primary' : 'secondary';
        html += `
            <tr>
                <td><code>${contract.id}</code></td>
                <td><span class="badge bg-${statusClass}">${contract.status}</span></td>
                <td>${contract.beneficiary}</td>
                <td>${contract.package}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="searchDemoContract('${contract.id}')">
                        <i class="fas fa-eye me-1"></i>عرض
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';

    $('#contractsContainer').html(html);
}

function searchDemoContract(contractId) {
    $('#contractIdSearch').val(contractId);
    $('#searchContractBtn').click();
}
</script>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
