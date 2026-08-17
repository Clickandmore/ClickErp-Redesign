@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
نظام الاستقدام - الإصدار التجريبي
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle me-2"></i>نظام التكامل مع منصة الاستقدام</h4>
                    <p class="mb-0">هذا إصدار تجريبي من نظام التكامل مع منصة الاستقدام الحكومية. النظام جاهز ويحتاج لرفع الملفات للخادم الحي.</p>
                </div>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>النظام جاهز</h5>
                        <p class="text-muted">تم تطوير جميع المكونات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-cogs fa-3x text-primary mb-3"></i>
                        <h5>API متكامل</h5>
                        <p class="text-muted">جميع endpoints جاهزة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-database fa-3x text-info mb-3"></i>
                        <h5>قاعدة البيانات</h5>
                        <p class="text-muted">جداول العقود منشأة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-desktop fa-3x text-warning mb-3"></i>
                        <h5>واجهات المستخدم</h5>
                        <p class="text-muted">جميع الصفحات جاهزة</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Functions -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-flask me-2"></i>اختبار الوظائف</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" id="testConnectionBtn">
                                <i class="fas fa-wifi me-2"></i>اختبار الاتصال
                            </button>
                            <button class="btn btn-info" id="testPackagesBtn">
                                <i class="fas fa-box me-2"></i>اختبار الباقات
                            </button>
                            <button class="btn btn-success" id="testContractBtn">
                                <i class="fas fa-file-contract me-2"></i>اختبار العقود
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>المكونات المطورة</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>IstiqdasmService - خدمة API</li>
                            <li><i class="fas fa-check text-success me-2"></i>IstiqdasmController - تحكم العمليات</li>
                            <li><i class="fas fa-check text-success me-2"></i>IstiqdasmContract - نموذج العقود</li>
                            <li><i class="fas fa-check text-success me-2"></i>واجهات المستخدم</li>
                            <li><i class="fas fa-check text-success me-2"></i>قاعدة البيانات</li>
                            <li><i class="fas fa-check text-success me-2"></i>Routes والإعدادات</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>نتائج الاختبار</h5>
                    </div>
                    <div class="card-body">
                        <div id="testResults">
                            <p class="text-muted text-center">اضغط على أحد أزرار الاختبار أعلاه لبدء الاختبار</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>الخطوات التالية:</h5>
                    <ol>
                        <li>رفع جميع الملفات المطورة للخادم الحي</li>
                        <li>تشغيل migrations قاعدة البيانات</li>
                        <li>تحديث إعدادات API للبيئة الحية</li>
                        <li>اختبار التكامل مع منصة الاستقدام الفعلية</li>
                        <li>تدريب المستخدمين على النظام الجديد</li>
                    </ol>
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
    // Test connection
    $('#testConnectionBtn').on('click', function() {
        testFunction('/admin/istiqdam/test-connection', 'اختبار الاتصال');
    });

    // Test packages
    $('#testPackagesBtn').on('click', function() {
        testFunction('/admin/istiqdam/test-packages', 'اختبار الباقات');
    });

    // Test contracts
    $('#testContractBtn').on('click', function() {
        testFunction('/admin/istiqdam/test-contract/12345', 'اختبار العقود');
    });

    function testFunction(url, testName) {
        $('#testResults').html(`
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin me-2"></i>
                جاري تشغيل ${testName}...
            </div>
        `);

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
            success: function(response) {
                if (response.success) {
                    $('#testResults').html(`
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check-circle me-2"></i>${testName} - نجح</h6>
                            <p><strong>الرسالة:</strong> ${response.message}</p>
                            ${response.data ? `<p><strong>البيانات:</strong> <code>${JSON.stringify(response.data, null, 2)}</code></p>` : ''}
                        </div>
                    `);
                } else {
                    $('#testResults').html(`
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>${testName} - تحذير</h6>
                            <p>${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                let errorMsg = 'حدث خطأ غير متوقع';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.statusText) {
                    errorMsg = xhr.statusText;
                }

                $('#testResults').html(`
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle me-2"></i>${testName} - فشل</h6>
                        <p><strong>خطأ:</strong> ${errorMsg}</p>
                        <p><strong>كود الخطأ:</strong> ${xhr.status}</p>
                    </div>
                `);
            }
        });
    }
});
</script>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 20px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.btn {
    border-radius: 8px;
}

.alert {
    border-radius: 8px;
}

pre {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
}
</style>
@endsection
