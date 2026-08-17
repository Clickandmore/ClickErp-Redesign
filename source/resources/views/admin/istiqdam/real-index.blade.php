@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
منصة الاستقدام - الربط الفعلي
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-success">
                    <h4><i class="fas fa-link me-2"></i>منصة الاستقدام - الربط الفعلي</h4>
                    <p class="mb-0">هذه الصفحة تستخدم الربط الفعلي مع منصة الاستقدام الحكومية السعودية</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="{{ route('istiqdam.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-flask me-1"></i>
                                النسخة التجريبية
                            </a>
                            <button class="btn btn-success active">
                                <i class="fas fa-link me-1"></i>
                                الربط الفعلي
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Connection Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-signal me-2"></i>
                            حالة الاتصال مع منصة الاستقدام الحكومية
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="connectionStatus">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">جاري التحقق...</span>
                                </div>
                                <span>جاري اختبار الاتصال مع المنصة الحكومية...</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary me-2" id="testConnectionBtn">
                                <i class="fas fa-wifi me-2"></i>
                                اختبار الاتصال
                            </button>
                            <button type="button" class="btn btn-info me-2" onclick="showApiInfo()">
                                <i class="fas fa-info-circle me-2"></i>
                                معلومات الـ API
                            </button>
                            <button type="button" class="btn btn-warning" id="diagnoseBtn">
                                <i class="fas fa-stethoscope me-2"></i>
                                تشخيص الاتصال
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Environment Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-server me-2"></i>
                            معلومات البيئة
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td><strong>البيئة الحالية:</strong></td>
                                <td><span class="badge bg-warning">{{ config('istiqdam.current_env') }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>رابط الـ API:</strong></td>
                                <td><code class="small">{{ config('istiqdam.environments.' . config('istiqdam.current_env') . '.base_url') }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>معرف العميل:</strong></td>
                                <td><code>{{ config('istiqdam.environments.' . config('istiqdam.current_env') . '.client_id') }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>مهلة الانتظار:</strong></td>
                                <td>{{ config('istiqdam.timeout') }} ثانية</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            الوظائف المتاحة
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                جلب الباقات من المنصة
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                إنشاء العقود في المنصة
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                عرض تفاصيل العقود
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                إلغاء العقود
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                تحميل ملفات العقود
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                إضافة العاملات للعقود
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions with Real API -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card action-card h-100 border-primary">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-box fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">الباقات الحقيقية</h5>
                        <p class="card-text text-muted">جلب الباقات مباشرة من المنصة</p>
                        <button class="btn btn-primary" onclick="testRealPackages()">
                            <i class="fas fa-download me-1"></i>
                            جلب الباقات
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card action-card h-100 border-success">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-file-contract fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">إنشاء عقد حقيقي</h5>
                        <p class="card-text text-muted">إنشاء عقد في المنصة الحكومية</p>
                        <button class="btn btn-success" onclick="showContractForm()">
                            <i class="fas fa-plus me-1"></i>
                            إنشاء عقد
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card action-card h-100 border-info">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-search fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">البحث عن عقد</h5>
                        <p class="card-text text-muted">البحث في المنصة الحكومية</p>
                        <button class="btn btn-info" onclick="showSearchForm()">
                            <i class="fas fa-search me-1"></i>
                            بحث
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            نتائج الاختبارات
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults">
                            <div class="text-center text-muted">
                                <i class="fas fa-flask fa-3x mb-3"></i>
                                <p>لم يتم تشغيل أي اختبارات بعد</p>
                                <small>استخدم الأزرار أعلاه لاختبار الوظائف المختلفة</small>
                            </div>
                        </div>
                    </div>
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
    // Test connection on page load
    testConnection();

    // Test connection button
    $('#testConnectionBtn').on('click', function() {
        testConnection();
    });

    function testConnection() {
        $('#connectionStatus').html(`
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">جاري الاختبار...</span>
                </div>
                <span>جاري اختبار الاتصال مع المنصة الحكومية...</span>
            </div>
        `);

        $.ajax({
            url: '{{ route("istiqdam.test.connection") }}',
            type: 'GET',
            timeout: 30000, // 30 seconds timeout
            success: function(response) {
                if (response.success) {
                    $('#connectionStatus').html(`
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>نجح الاتصال مع المنصة الحكومية!</strong>
                            <small class="d-block mt-1">${response.message}</small>
                            <small class="d-block mt-1">البيئة: ${response.data.environment} | الوقت: ${new Date(response.data.timestamp).toLocaleString('ar-SA')}</small>
                        </div>
                    `);

                    // Log successful test
                    logTestResult('اختبار الاتصال', 'نجح', response.message);
                } else {
                    $('#connectionStatus').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>فشل الاتصال مع المنصة!</strong>
                            <small class="d-block mt-1">${response.message}</small>
                        </div>
                    `);

                    // Log failed test
                    logTestResult('اختبار الاتصال', 'فشل', response.message);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'حدث خطأ أثناء الاتصال';
                if (status === 'timeout') {
                    errorMessage = 'انتهت مهلة الانتظار - قد تكون المنصة غير متاحة';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                $('#connectionStatus').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>خطأ في الاتصال!</strong>
                        <small class="d-block mt-1">${errorMessage}</small>
                    </div>
                `);

                // Log error
                logTestResult('اختبار الاتصال', 'خطأ', errorMessage);
            }
        });
    }

    function logTestResult(testName, status, message) {
        const timestamp = new Date().toLocaleString('ar-SA');
        const statusClass = status === 'نجح' ? 'success' : status === 'فشل' ? 'danger' : 'warning';

        const resultHtml = `
            <div class="alert alert-${statusClass} alert-dismissible fade show">
                <strong>${testName}:</strong> ${message}
                <small class="d-block mt-1">${timestamp}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        if ($('#testResults .text-muted').length) {
            $('#testResults').html(resultHtml);
        } else {
            $('#testResults').prepend(resultHtml);
        }
    }
});

function showApiInfo() {
    Swal.fire({
        title: 'معلومات الـ API',
        html: `
            <div class="text-start">
                <h6>تفاصيل الربط مع منصة الاستقدام:</h6>
                <ul>
                    <li><strong>البيئة:</strong> {{ config('istiqdam.current_env') }}</li>
                    <li><strong>رابط الـ API:</strong><br><code class="small">{{ config('istiqdam.environments.' . config('istiqdam.current_env') . '.base_url') }}</code></li>
                    <li><strong>معرف العميل:</strong> {{ config('istiqdam.environments.' . config('istiqdam.current_env') . '.client_id') }}</li>
                    <li><strong>مهلة الانتظار:</strong> {{ config('istiqdam.timeout') }} ثانية</li>
                </ul>
                <hr>
                <h6>ملاحظات مهمة:</h6>
                <ul>
                    <li>هذا ربط حقيقي مع المنصة الحكومية</li>
                    <li>جميع العمليات ستؤثر على البيانات الفعلية</li>
                    <li>يرجى الحذر عند الاختبار</li>
                </ul>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'حسناً',
        width: '600px'
    });
}

function testRealPackages() {
    Swal.fire({
        title: 'جاري جلب الباقات...',
        html: 'يرجى الانتظار أثناء جلب الباقات من المنصة الحكومية',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();

            $.ajax({
                url: '{{ route("istiqdam.packages.data") }}',
                type: 'GET',
                timeout: 30000,
                success: function(response) {
                    Swal.close();
                    if (response.data && response.data.length > 0) {
                        let packagesHtml = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID</th><th>الجنسية</th><th>المهنة</th><th>المدة</th><th>السعر</th></tr></thead><tbody>';
                        response.data.forEach(function(pkg) {
                            packagesHtml += `<tr><td>${pkg.packageId || 'N/A'}</td><td>${pkg.nationality || 'N/A'}</td><td>${pkg.occupation || 'N/A'}</td><td>${pkg.duration || 'N/A'}</td><td>${pkg.priceGross || 'N/A'}</td></tr>`;
                        });
                        packagesHtml += '</tbody></table></div>';

                        Swal.fire({
                            title: 'الباقات من المنصة الحكومية',
                            html: packagesHtml,
                            width: '800px',
                            confirmButtonText: 'حسناً'
                        });

                        logTestResult('جلب الباقات', 'نجح', `تم جلب ${response.data.length} باقة من المنصة`);
                    } else {
                        Swal.fire('تنبيه', 'لا توجد باقات متاحة', 'info');
                        logTestResult('جلب الباقات', 'فشل', 'لا توجد باقات متاحة');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    let errorMsg = status === 'timeout' ? 'انتهت مهلة الانتظار' : 'حدث خطأ في الاتصال';
                    Swal.fire('خطأ!', errorMsg, 'error');
                    logTestResult('جلب الباقات', 'خطأ', errorMsg);
                }
            });
        }
    });
}

function showContractForm() {
    Swal.fire({
        title: 'إنشاء عقد في المنصة الحكومية',
        html: `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>تحذير:</strong> هذا سيقوم بإنشاء عقد حقيقي في المنصة الحكومية!
            </div>
            <p>هل تريد المتابعة لصفحة إنشاء العقد؟</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، المتابعة',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form for real contract creation
            Swal.fire('قريباً', 'سيتم إضافة نموذج إنشاء العقد الحقيقي', 'info');
        }
    });
}

function showSearchForm() {
    Swal.fire({
        title: 'البحث عن عقد في المنصة',
        html: `
            <input type="text" id="contractSearchId" class="form-control" placeholder="أدخل رقم العقد">
        `,
        showCancelButton: true,
        confirmButtonText: 'بحث',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const contractId = document.getElementById('contractSearchId').value;
            if (!contractId) {
                Swal.showValidationMessage('يرجى إدخال رقم العقد');
                return false;
            }
            return contractId;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            searchRealContract(result.value);
        }
    });
}

function searchRealContract(contractId) {
    Swal.fire({
        title: 'جاري البحث...',
        html: `جاري البحث عن العقد ${contractId} في المنصة الحكومية`,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();

            $.ajax({
                url: '{{ route("istiqdam.contracts.show", ":id") }}'.replace(':id', contractId),
                type: 'GET',
                timeout: 30000,
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            title: 'تم العثور على العقد!',
                            html: `<pre class="text-start">${JSON.stringify(response.data, null, 2)}</pre>`,
                            width: '800px',
                            confirmButtonText: 'حسناً'
                        });
                        logTestResult('البحث عن عقد', 'نجح', `تم العثور على العقد ${contractId}`);
                    } else {
                        Swal.fire('لم يتم العثور على العقد', response.message, 'info');
                        logTestResult('البحث عن عقد', 'فشل', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    let errorMsg = status === 'timeout' ? 'انتهت مهلة الانتظار' : 'حدث خطأ في البحث';
                    Swal.fire('خطأ!', errorMsg, 'error');
                    logTestResult('البحث عن عقد', 'خطأ', errorMsg);
                }
            });
        }
    });
}

// تشخيص الاتصال
$(document).on('click', '#diagnoseBtn', function() {
    Swal.fire({
        title: 'جاري التشخيص...',
        html: 'جاري تشخيص مشاكل الاتصال مع المنصة الحكومية',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();

            $.ajax({
                url: '{{ route("istiqdam.diagnose") }}',
                type: 'GET',
                timeout: 30000,
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        let diagnosisHtml = `
                            <div class="text-start">
                                <h6>نتائج التشخيص:</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>البيئة:</strong></td><td>${response.diagnosis.environment}</td></tr>
                                    <tr><td><strong>رابط API:</strong></td><td><code>${response.diagnosis.base_url}</code></td></tr>
                                    <tr><td><strong>معرف العميل:</strong></td><td>${response.diagnosis.client_id}</td></tr>
                                    <tr><td><strong>طول مفتاح API:</strong></td><td>${response.diagnosis.api_key_length}</td></tr>
                                    <tr><td><strong>مهلة الانتظار:</strong></td><td>${response.diagnosis.timeout} ثانية</td></tr>
                                </table>

                                <h6 class="mt-3">اختبار الاتصال الأساسي:</h6>
                                <p>الحالة: <span class="badge ${response.diagnosis.health_check.success ? 'bg-success' : 'bg-danger'}">${response.diagnosis.health_check.success ? 'نجح' : 'فشل'}</span></p>
                                <p>رمز الاستجابة: ${response.diagnosis.health_check.status}</p>

                                <h6 class="mt-3">اختبار API الرئيسي:</h6>
                                <p>الحالة: <span class="badge ${response.diagnosis.api_test.success ? 'bg-success' : 'bg-danger'}">${response.diagnosis.api_test.success ? 'نجح' : 'فشل'}</span></p>
                                <p>رمز الاستجابة: ${response.diagnosis.api_test.status}</p>

                                ${response.diagnosis.api_test.response ? `<p>الاستجابة: <code>${JSON.stringify(response.diagnosis.api_test.response, null, 2)}</code></p>` : ''}
                            </div>
                        `;

                        Swal.fire({
                            title: 'نتائج التشخيص',
                            html: diagnosisHtml,
                            width: '800px',
                            confirmButtonText: 'حسناً'
                        });

                        logTestResult('تشخيص الاتصال', 'نجح', 'تم إكمال التشخيص');
                    } else {
                        Swal.fire('خطأ في التشخيص', response.message, 'error');
                        logTestResult('تشخيص الاتصال', 'فشل', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    let errorMsg = status === 'timeout' ? 'انتهت مهلة الانتظار' : 'حدث خطأ في التشخيص';
                    Swal.fire('خطأ!', errorMsg, 'error');
                    logTestResult('تشخيص الاتصال', 'خطأ', errorMsg);
                }
            });
        }
    });
});
</script>

<style>
.action-card {
    transition: transform 0.2s;
    border: 2px solid #e9ecef;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.action-icon {
    opacity: 0.8;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.border-primary {
    border-color: #007bff !important;
}

.border-success {
    border-color: #28a745 !important;
}

.border-info {
    border-color: #17a2b8 !important;
}

code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.875em;
}
</style>
@endsection
