@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
منصة الاستقدام الحكومية
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-building me-2"></i>منصة الاستقدام الحكومية</h4>
                    <p class="mb-0">النظام جاهز للاختبار - جاري تطوير التكامل مع منصة الاستقدام الحكومية السعودية</p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <button class="btn btn-success active">
                                <i class="fas fa-flask me-1"></i>
                                النسخة التجريبية
                            </button>
                            <a href="{{ route('istiqdam.real.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-link me-1"></i>
                                الربط الفعلي
                            </a>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                النسخة التجريبية تستخدم بيانات وهمية، بينما الربط الفعلي يتصل بالمنصة الحكومية
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-signal me-2"></i>
                            حالة النظام
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="connectionStatus">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">جاري التحقق...</span>
                                </div>
                                <span>جاري التحقق من حالة النظام...</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary mt-3" id="testConnectionBtn">
                            <i class="fas fa-wifi me-2"></i>
                            اختبار النظام
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-file-contract fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">العقود</h5>
                        <p class="card-text text-muted">إدارة عقود الاستقدام</p>
                        <a href="{{ route('istiqdam.contracts') }}" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i>
                            عرض العقود
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-plus-circle fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">إنشاء عقد جديد</h5>
                        <p class="card-text text-muted">إضافة عقد جديد في المنصة</p>
                        <a href="{{ route('istiqdam.contracts.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>
                            إنشاء عقد
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-box fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title">الباقات</h5>
                        <p class="card-text text-muted">عرض الباقات المتاحة</p>
                        <a href="{{ route('istiqdam.packages') }}" class="btn btn-warning">
                            <i class="fas fa-search me-1"></i>
                            تصفح الباقات
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card action-card h-100">
                    <div class="card-body text-center">
                        <div class="action-icon mb-3">
                            <i class="fas fa-info-circle fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">معلومات النظام</h5>
                        <p class="card-text text-muted">تفاصيل التكامل</p>
                        <button class="btn btn-info" onclick="showSystemInfo()">
                            <i class="fas fa-info me-1"></i>
                            عرض المعلومات
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            العمليات المتاحة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        إنشاء العقود (تجريبي)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        عرض تفاصيل العقود (تجريبي)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        إلغاء العقود (تجريبي)
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        إدارة الباقات (تجريبي)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        إضافة العاملات للعقود (تجريبي)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        تحميل العقود (تجريبي)
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>ملاحظة:</strong> النظام حالياً في مرحلة التطوير والاختبار. جميع العمليات تجريبية ولا تتصل بمنصة الاستقدام الفعلية.
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
                    <span class="visually-hidden">جاري التحقق...</span>
                </div>
                <span>جاري التحقق من حالة النظام...</span>
            </div>
        `);

        $.ajax({
            url: '{{ route("istiqdam.test.connection") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#connectionStatus').html(`
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>النظام يعمل بشكل صحيح!</strong>
                            <small class="d-block mt-1">${response.message}</small>
                            <small class="d-block mt-1">البيئة: ${response.data.environment} | الوقت: ${new Date(response.data.timestamp).toLocaleString('ar-SA')}</small>
                        </div>
                    `);
                } else {
                    $('#connectionStatus').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>خطأ في النظام!</strong>
                            <small class="d-block mt-1">${response.message}</small>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#connectionStatus').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>خطأ في الاتصال!</strong>
                        <small class="d-block mt-1">حدث خطأ أثناء اختبار النظام</small>
                    </div>
                `);
            }
        });
    }
});

function showSystemInfo() {
    Swal.fire({
        title: 'معلومات النظام',
        html: `
            <div class="text-start">
                <h6>تفاصيل التكامل مع منصة الاستقدام:</h6>
                <ul>
                    <li><strong>الحالة:</strong> قيد التطوير</li>
                    <li><strong>البيئة:</strong> اختبار</li>
                    <li><strong>الإصدار:</strong> 1.0.0</li>
                    <li><strong>التحديث الأخير:</strong> ${new Date().toLocaleDateString('ar-SA')}</li>
                </ul>
                <hr>
                <h6>الوظائف المطورة:</h6>
                <ul>
                    <li>إدارة العقود</li>
                    <li>تصفح الباقات</li>
                    <li>إضافة العاملات</li>
                    <li>تحميل الملفات</li>
                </ul>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'حسناً'
    });
}
</script>

<style>
.action-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
</style>
@endsection
