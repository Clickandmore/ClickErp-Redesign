@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
منصة الاستقدام الحكومية
@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">منصة الاستقدام الحكومية</h4>
                    <p class="text-muted">التكامل مع منصة الاستقدام الحكومية لإدارة العقود والباقات</p>
                </div>
            </div>
        </div>

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-building text-primary me-2"></i>
                لوحة تحكم الاستقدام
            </h3>
            <div>
                <a href="{{ route('istiqdam.test') }}" class="btn btn-warning me-2">
                    <i class="fas fa-flask me-2"></i>
                    صفحة الاختبار
                </a>
                <button type="button" class="btn btn-info me-2" id="testConnectionBtn">
                    <i class="fas fa-wifi me-2"></i>
                    اختبار الاتصال
                </button>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-signal me-2"></i>
                            حالة الاتصال بمنصة الاستقدام
                        </h5>
                        <div id="connectionStatus">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">جاري التحقق...</span>
                                </div>
                                <span>جاري التحقق من الاتصال...</span>
                            </div>
                        </div>
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
                            <i class="fas fa-cog fa-3x text-secondary"></i>
                        </div>
                        <h5 class="card-title">الإعدادات</h5>
                        <p class="card-text text-muted">إعدادات التكامل</p>
                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-tools me-1"></i>
                            قريباً
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            معلومات API
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-4"><strong>البيئة:</strong></div>
                            <div class="col-sm-8">
                                <span class="badge bg-info">{{ config('istiqdam.current_env') }}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-4"><strong>URL الأساسي:</strong></div>
                            <div class="col-sm-8">
                                <small class="text-muted">{{ config('istiqdam.environments.'.config('istiqdam.current_env').'.base_url') }}</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-4"><strong>Client ID:</strong></div>
                            <div class="col-sm-8">
                                <code>{{ config('istiqdam.environments.'.config('istiqdam.current_env').'.client_id') }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            العمليات المتاحة
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                إنشاء العقود
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
                                إدارة الباقات
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                إضافة العاملات للعقود
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                تحميل العقود
                            </li>
                        </ul>
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
                <span>جاري التحقق من الاتصال...</span>
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
                            <strong>متصل بنجاح!</strong>
                            <small class="d-block mt-1">${response.message}</small>
                        </div>
                    `);
                } else {
                    $('#connectionStatus').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            <strong>فشل الاتصال!</strong>
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
                        <small class="d-block mt-1">حدث خطأ أثناء اختبار الاتصال</small>
                    </div>
                `);
            }
        });
    }
});
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

.page-title-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
</style>
@endsection
