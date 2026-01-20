@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('page-title')
إدارة المدن
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
    <div class="page-body">
        <div class="container-fluid">
           <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-city text-primary me-2"></i>
                إدارة المدن
            </h3>
            <div class="export-buttons text-end">
                <a href="{{ route('countries.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> العودة للدول
                </a>
                <button type="button" class="btn btn-outline-info btn-sm" id="exportBtn">
                    <i class="fas fa-download"></i> تصدير
                </button>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4 filter-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter me-2"></i>
                    الفلاتر
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="card-body collapse" id="filtersCollapse">
                <form id="filterForm" class="row g-3" method="GET">
                    <div class="col-md-3">
                        <label class="form-label">البحث في الاسم</label>
                        <input type="text" class="form-control" name="search" id="search"
                               value="{{ request('search') }}" placeholder="اسم المدينة...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الدولة</label>
                        <select class="form-select" name="country_id" id="country_id">
                            <option value="">جميع الدول</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ request('country_id') == $country->id ? 'selected' : '' }}>
                                    {{ $country->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" name="is_active" id="is_active">
                            <option value="">جميع الحالات</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="{{ route('cities.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> إعادة تعيين
                            </a>
                            <button type="button" class="btn btn-success" onclick="refreshData()">
                                <i class="fas fa-sync"></i> تحديث
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="card shadow">
            <div class="card-body">

                    <table class="table table-bordered table-striped table-hover" id="cities-table">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%" style="color: #fff">#</th>
                                <th width="30%" style="color: #fff">اسم المدينة</th>
                                <th width="25%" style="color: #fff">الدولة</th>
                                <th width="15%" style="color: #fff">الحالة</th>
                                <th width="15%" style="color: #fff">الإحداثيات</th>
                                <th width="10%" style="color: #fff">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cities as $city)
                            <tr>
                                <td>{{ $cities->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong>{{ $city->name_ar }}</strong>
                                            <br><small class="text-muted">{{ $city->name_en }}</small>
                                            @if($city->is_capital)
                                                <br><small class="badge bg-warning text-dark mt-1">
                                                    <i class="fas fa-star"></i> عاصمة
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $city->country->name_ar }}
                                    </span>
                                    <br><small class="text-muted">{{ $city->country->name_en }}</small>
                                </td>
                                <td>
                                    @if($city->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    @if($city->latitude && $city->longitude)
                                        <small class="text-muted">
                                            {{ number_format($city->latitude, 4) }}<br>
                                            {{ number_format($city->longitude, 4) }}
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('countries.show', $city->country_id) }}"
                                           class="btn btn-outline-primary" title="عرض الدولة">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info"
                                                onclick="showCityDetails({{ $city->id }})" title="تفاصيل المدينة">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-city fa-2x mb-2"></i>
                                        <p>لا توجد مدن تطابق معايير البحث</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>


                <!-- Pagination -->
                @if($cities->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        <nav>
                            {{ $cities->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </nav>
                    </div>
                @endif

            </div>
        </div>
    </div>
    </div>
@endif

<!-- Modal لتفاصيل المدينة -->
<div class="modal fade" id="cityDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل المدينة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cityDetailsContent">
                <!-- سيتم تحميل التفاصيل هنا -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{asset("admin")}}/assets/jsbundle/dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#cities-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        },
        "pageLength": 25,
        "order": [[1, 'asc']],
        "columnDefs": [
            {"orderable": false, "targets": [5]}
        ]
    });

    // Filter collapse toggle
    $('#filtersCollapse').on('show.bs.collapse', function () {
        $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    $('#filtersCollapse').on('hide.bs.collapse', function () {
        $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    // Auto search on input
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 500);
    });

    // Auto filter on country change
    $('#country_id').on('change', function() {
        $('#filterForm').submit();
    });

    // Auto filter on status change
    $('#is_active').on('change', function() {
        $('#filterForm').submit();
    });
});

// Refresh Data Function
function refreshData() {
    location.reload();
}

// Show City Details Function
function showCityDetails(cityId) {
    $.ajax({
        url: '/admin/ingaz/api/cities/' + cityId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                showCityModal(response.city);
            } else {
                showAlert('error', 'حدث خطأ في جلب بيانات المدينة');
            }
        },
        error: function() {
            showAlert('error', 'حدث خطأ في الاتصال');
        }
    });
}

// Show City Modal Function
function showCityModal(city) {
    const modalContent = `
        <div class="row">
            <div class="col-md-12">
                <h5 class="text-center mb-3">
                    <i class="fas fa-city text-primary"></i>
                    ${city.name_ar}
                    <small class="text-muted">(${city.name_en})</small>
                </h5>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-flag"></i> معلومات الدولة
                        </h6>
                        <p class="mb-1"><strong>الدولة:</strong> ${city.country.name_ar}</p>
                        <p class="mb-1"><strong>رمز الدولة:</strong> ${city.country.code}</p>
                        <p class="mb-1"><strong>العملة:</strong> ${city.country.currency_symbol} ${city.country.currency}</p>
                        <p class="mb-0"><strong>رمز الهاتف:</strong> +${city.country.phone_code}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle"></i> معلومات المدينة
                        </h6>
                        <p class="mb-1">
                            <strong>الحالة:</strong>
                            ${city.is_active ?
                                '<span class="badge bg-success">نشط</span>' :
                                '<span class="badge bg-danger">غير نشط</span>'
                            }
                        </p>
                        ${city.latitude && city.longitude ?
                            `<p class="mb-1"><strong>الإحداثيات:</strong> ${parseFloat(city.latitude).toFixed(4)}, ${parseFloat(city.longitude).toFixed(4)}</p>` :
                            '<p class="mb-1"><strong>الإحداثيات:</strong> غير محددة</p>'
                        }
                        <p class="mb-0"><strong>تاريخ الإنشاء:</strong> ${new Date(city.created_at).toLocaleDateString('ar-SA')}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="/admin/ingaz/countries/${city.country_id}" class="btn btn-primary">
                    <i class="fas fa-eye"></i> عرض تفاصيل الدولة
                </a>
            </div>
        </div>
    `;

    $('#cityDetailsContent').html(modalContent);
    $('#cityDetailsModal').modal('show');
}

// Show Alert Function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('.container-fluid').prepend(alertHtml);

    setTimeout(() => {
        $('.alert').fadeOut();
    }, 3000);
}
</script>
@endsection
