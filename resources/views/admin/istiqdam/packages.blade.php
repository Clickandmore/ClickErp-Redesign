@extends('admin.layout.indexs.index')

@section('style')
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('page-title')
باقات الاستقدام
@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
    <div class="container-fluid">
        <!-- Page Title Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">باقات الاستقدام المتاحة</h4>
                    <p class="text-muted">تصفح الباقات المتاحة في منصة الاستقدام الحكومية</p>
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
                <button type="button" class="btn btn-primary" id="refreshPackagesBtn">
                    <i class="fas fa-sync-alt me-2"></i>
                    تحديث
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter me-2"></i>فلاتر البحث
                </h6>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="collapse" id="filterCollapse">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="filterPackageId" class="form-label">رقم الباقة</label>
                            <input type="text" id="filterPackageId" class="form-control" placeholder="ابحث برقم الباقة">
                        </div>
                        <div class="col-md-3">
                            <label for="filterDuration" class="form-label">المدة</label>
                            <select id="filterDuration" class="form-select">
                                <option value="">الكل</option>
                                <option value="12">12 شهر</option>
                                <option value="24">24 شهر</option>
                                <option value="36">36 شهر</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterNationality" class="form-label">الجنسية</label>
                            <input type="text" id="filterNationality" class="form-control" placeholder="الجنسية">
                        </div>
                        <div class="col-md-3">
                            <label for="filterOccupation" class="form-label">المهنة</label>
                            <input type="text" id="filterOccupation" class="form-control" placeholder="المهنة">
                        </div>
                        <div class="col-md-3">
                            <label for="filterGender" class="form-label">الجنس</label>
                            <select id="filterGender" class="form-select">
                                <option value="">الكل</option>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterPriceMin" class="form-label">السعر الأدنى</label>
                            <input type="number" id="filterPriceMin" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label for="filterPriceMax" class="form-label">السعر الأعلى</label>
                            <input type="number" id="filterPriceMax" class="form-control" placeholder="100000">
                        </div>
                        <div class="col-md-3">
                            <label for="filterSize" class="form-label">عدد النتائج</label>
                            <select id="filterSize" class="form-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" id="clearFilters" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> مسح الفلاتر
                            </button>
                            <button type="button" id="applyFilters" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Packages Grid -->
        <div id="packagesContainer">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحميل الباقات...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-4" style="display: none !important;">
            <div id="paginationInfo"></div>
            <div id="paginationControls"></div>
        </div>
    </div>

    <!-- Package Details Modal -->
    <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalLabel">تفاصيل الباقة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="packageModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" id="createContractFromPackage" style="display: none;">
                        <i class="fas fa-plus me-1"></i>
                        إنشاء عقد من هذه الباقة
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
    let currentPage = 0;
    let currentPackageId = null;

    // Load packages on page load
    loadPackages();

    // Refresh button
    $('#refreshPackagesBtn').on('click', function() {
        currentPage = 0;
        loadPackages();
    });

    // Apply filters
    $('#applyFilters').on('click', function() {
        currentPage = 0;
        loadPackages();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#filterPackageId').val('');
        $('#filterDuration').val('');
        $('#filterNationality').val('');
        $('#filterOccupation').val('');
        $('#filterGender').val('');
        $('#filterPriceMin').val('');
        $('#filterPriceMax').val('');
        $('#filterSize').val('10');
        currentPage = 0;
        loadPackages();
    });

    function loadPackages() {
        const filters = {
            page: currentPage,
            size: $('#filterSize').val() || 10,
            packageId: $('#filterPackageId').val(),
            duration: $('#filterDuration').val(),
            nationality: $('#filterNationality').val(),
            occupation: $('#filterOccupation').val(),
            gender: $('#filterGender').val(),
            priceGrossMin: $('#filterPriceMin').val(),
            priceGrossMax: $('#filterPriceMax').val(),
        };

        $('#packagesContainer').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحميل الباقات...</p>
            </div>
        `);

        $.ajax({
            url: '{{ route("istiqdam.packages.data") }}',
            type: 'GET',
            data: filters,
            success: function(response) {
                if (response.success && response.data) {
                    displayPackages(response.data);
                } else {
                    showError(response.error || 'فشل في تحميل الباقات');
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
                    <p class="text-muted">جرب تغيير معايير البحث</p>
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

        // Update pagination
        updatePagination(data);
    }

    function updatePagination(data) {
        if (data.totalElements <= data.size) {
            $('#paginationContainer').hide();
            return;
        }

        $('#paginationContainer').show();

        const totalPages = data.totalPages;
        const currentPageNum = data.number;

        $('#paginationInfo').html(`
            عرض ${data.numberOfElements} من ${data.totalElements} باقة
        `);

        let paginationHtml = '<nav><ul class="pagination">';

        // Previous button
        if (currentPageNum > 0) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPageNum - 1}">السابق</a></li>`;
        }

        // Page numbers
        for (let i = 0; i < totalPages; i++) {
            if (i === currentPageNum) {
                paginationHtml += `<li class="page-item active"><span class="page-link">${i + 1}</span></li>`;
            } else {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i + 1}</a></li>`;
            }
        }

        // Next button
        if (currentPageNum < totalPages - 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPageNum + 1}">التالي</a></li>`;
        }

        paginationHtml += '</ul></nav>';

        $('#paginationControls').html(paginationHtml);
    }

    // Handle pagination clicks
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page !== undefined) {
            currentPage = page;
            loadPackages();
        }
    });

    // Handle view package details
    $(document).on('click', '.view-package', function() {
        const packageId = $(this).data('id');
        currentPackageId = packageId;
        showPackageDetails(packageId);
    });

    // Handle create contract
    $(document).on('click', '.create-contract', function() {
        const packageId = $(this).data('id');
        window.location.href = '{{ route("istiqdam.contracts.create") }}?package_id=' + packageId;
    });

    function showPackageDetails(packageId) {
        $('#packageModalBody').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            </div>
        `);

        $('#packageModal').modal('show');

        $.ajax({
            url: '{{ route("istiqdam.packages.show", ":id") }}'.replace(':id', packageId),
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    displayPackageDetails(response.data);
                    $('#createContractFromPackage').show().data('package-id', packageId);
                } else {
                    $('#packageModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.error || 'فشل في تحميل تفاصيل الباقة'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#packageModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        حدث خطأ أثناء تحميل تفاصيل الباقة
                    </div>
                `);
            }
        });
    }

    function displayPackageDetails(package) {
        const html = `
            <div class="package-details">
                <div class="row">
                    <div class="col-md-6">
                        <h6>المعلومات الأساسية</h6>
                        <table class="table table-sm">
                            <tr><td><strong>رقم الباقة:</strong></td><td>${package.id}</td></tr>
                            <tr><td><strong>المدة:</strong></td><td>${package.duration || 'غير محدد'} شهر</td></tr>
                            <tr><td><strong>الجنسية:</strong></td><td>${package.nationality || 'غير محدد'}</td></tr>
                            <tr><td><strong>المهنة:</strong></td><td>${package.occupation || 'غير محدد'}</td></tr>
                            <tr><td><strong>الجنس:</strong></td><td>${package.gender === 'male' ? 'ذكر' : package.gender === 'female' ? 'أنثى' : 'غير محدد'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>المعلومات المالية</h6>
                        <table class="table table-sm">
                            <tr><td><strong>السعر الإجمالي:</strong></td><td><span class="badge bg-success">${package.priceGross || 0} ريال</span></td></tr>
                            <tr><td><strong>تاريخ الإنشاء:</strong></td><td>${package.createdAt ? new Date(package.createdAt).toLocaleDateString('ar-SA') : 'غير محدد'}</td></tr>
                            <tr><td><strong>آخر تحديث:</strong></td><td>${package.updatedAt ? new Date(package.updatedAt).toLocaleDateString('ar-SA') : 'غير محدد'}</td></tr>
                        </table>
                    </div>
                </div>
                ${package.description ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>الوصف</h6>
                            <p>${package.description}</p>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;

        $('#packageModalBody').html(html);
    }

    // Handle create contract from modal
    $('#createContractFromPackage').on('click', function() {
        const packageId = $(this).data('package-id');
        window.location.href = '{{ route("istiqdam.contracts.create") }}?package_id=' + packageId;
    });

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

.page-title-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.filter-card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endsection
