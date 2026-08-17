@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
باقات الاستقدام
@endsection

@section('content')
<div class="page-body p-4 text-dark">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-box me-2"></i>باقات الاستقدام التجريبية</h4>
                    <p class="mb-0">عرض الباقات التجريبية - جاري تطوير التكامل مع منصة الاستقدام الفعلية</p>
                </div>
            </div>
        </div>

        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-box text-primary me-2"></i>
                الباقات المتاحة
            </h3>
            <div>
                <a href="{{ route('istiqdam.index') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-2"></i>
                    العودة للرئيسية
                </a>
                <button type="button" class="btn btn-primary" id="loadPackagesBtn">
                    <i class="fas fa-sync-alt me-2"></i>
                    تحميل الباقات
                </button>
            </div>
        </div>

        <!-- Packages Container -->
        <div id="packagesContainer">
            <div class="text-center">
                <button class="btn btn-success btn-lg" id="loadPackagesBtn2">
                    <i class="fas fa-download me-2"></i>
                    تحميل الباقات التجريبية
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section("js")
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Load packages buttons
    $('#loadPackagesBtn, #loadPackagesBtn2').on('click', function() {
        loadPackages();
    });

    function loadPackages() {
        $('#packagesContainer').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحميل الباقات التجريبية...</p>
            </div>
        `);

        $.ajax({
            url: '{{ route("istiqdam.packages.data") }}',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    displayPackages(response.data);
                } else {
                    showError('فشل في تحميل الباقات');
                }
            },
            error: function(xhr) {
                showError('حدث خطأ أثناء تحميل الباقات');
            }
        });
    }

    function displayPackages(data) {
        if (!data.content || data.content.length === 0) {
            $('#packagesContainer').html(`
                <div class="text-center">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5>لا توجد باقات متاحة</h5>
                </div>
            `);
            return;
        }

        let html = '<div class="row">';

        data.content.forEach(function(package) {
            html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card package-card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-box me-2"></i>
                                باقة رقم ${package.id}
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="package-info">
                                <div class="row mb-2">
                                    <div class="col-6"><strong>المدة:</strong></div>
                                    <div class="col-6">${package.duration || 'غير محدد'} شهر</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6"><strong>الجنسية:</strong></div>
                                    <div class="col-6">${package.nationality || 'غير محدد'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6"><strong>المهنة:</strong></div>
                                    <div class="col-6">${package.occupation || 'غير محدد'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6"><strong>الجنس:</strong></div>
                                    <div class="col-6">${package.gender === 'male' ? 'ذكر' : package.gender === 'female' ? 'أنثى' : 'غير محدد'}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6"><strong>السعر:</strong></div>
                                    <div class="col-6">
                                        <span class="badge bg-success">${package.priceGross || 0} ريال</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-primary btn-sm view-package" data-id="${package.id}">
                                    <i class="fas fa-eye me-1"></i>
                                    عرض التفاصيل
                                </button>
                                <button class="btn btn-primary btn-sm create-contract" data-id="${package.id}">
                                    <i class="fas fa-plus me-1"></i>
                                    إنشاء عقد
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';

        $('#packagesContainer').html(html);
    }

    // Handle view package details
    $(document).on('click', '.view-package', function() {
        const packageId = $(this).data('id');
        showPackageDetails(packageId);
    });

    // Handle create contract
    $(document).on('click', '.create-contract', function() {
        const packageId = $(this).data('id');
        window.location.href = '{{ route("istiqdam.contracts.create") }}?package_id=' + packageId;
    });

    function showPackageDetails(packageId) {
        Swal.fire({
            title: 'تفاصيل الباقة',
            html: '<div class="spinner-border text-primary" role="status"></div>',
            showConfirmButton: false,
            allowOutsideClick: false
        });

        $.ajax({
            url: '{{ route("istiqdam.packages.show", ":id") }}'.replace(':id', packageId),
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const package = response.data;
                    Swal.fire({
                        title: `تفاصيل الباقة رقم ${packageId}`,
                        html: `
                            <div class="text-start">
                                <div class="row">
                                    <div class="col-6"><strong>الجنسية:</strong></div>
                                    <div class="col-6">${package.nationality || 'غير محدد'}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>المهنة:</strong></div>
                                    <div class="col-6">${package.occupation || 'غير محدد'}</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>المدة:</strong></div>
                                    <div class="col-6">${package.duration || 'غير محدد'} شهر</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>السعر:</strong></div>
                                    <div class="col-6">${package.priceGross || 0} ريال</div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>الوصف:</strong></div>
                                    <div class="col-6">${package.description || 'غير متوفر'}</div>
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'حسناً',
                        showCancelButton: true,
                        cancelButtonText: 'إنشاء عقد من هذه الباقة'
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            window.location.href = '{{ route("istiqdam.contracts.create") }}?package_id=' + packageId;
                        }
                    });
                } else {
                    Swal.fire('خطأ!', 'فشل في تحميل تفاصيل الباقة', 'error');
                }
            },
            error: function() {
                Swal.fire('خطأ!', 'حدث خطأ أثناء تحميل تفاصيل الباقة', 'error');
            }
        });
    }

    function showError(message) {
        $('#packagesContainer').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `);
    }
});
</script>

<style>
.package-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endsection
