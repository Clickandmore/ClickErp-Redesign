@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@endsection

@section('page-title')
تفاصيل الدولة - {{ $country->name_ar }}
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
    <div class="page-body">
        <div class="container-fluid">
           <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-globe text-primary me-2"></i>
                تفاصيل الدولة - {{ $country->name_ar }}
            </h3>
            <div class="export-buttons text-end">
                <a href="{{ route('countries.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> العودة للدول
                </a>
                <a href="{{ route('countries.edit', $country) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> تعديل الدولة
                </a>
            </div>
        </div>

        <!-- Country Details Card -->
        <div class="card mb-4 shadow">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    معلومات الدولة
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">الاسم بالعربية:</label>
                            <p class="form-control-plaintext">{{ $country->name_ar }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">الاسم بالإنجليزية:</label>
                            <p class="form-control-plaintext">{{ $country->name_en }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">رمز الدولة:</label>
                            <span class="badge bg-light text-dark fs-6">{{ $country->code }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">رمز الهاتف:</label>
                            <p class="form-control-plaintext">+{{ $country->phone_code }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">العملة:</label>
                            <p class="form-control-plaintext">{{ $country->currency_symbol }} ({{ $country->currency }})</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">الحالة:</label>
                            @if($country->is_active)
                                <span class="badge bg-success fs-6">نشط</span>
                            @else
                                <span class="badge bg-danger fs-6">غير نشط</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ترتيب العرض:</label>
                            <p class="form-control-plaintext">{{ $country->sort_order }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">تاريخ الإنشاء:</label>
                            <p class="form-control-plaintext">{{ $country->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cities Section -->
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-city me-2"></i>
                    المدن التابعة لهذه الدولة ({{ $cities->count() }})
                </h5>
                <div>
                    <span class="badge bg-warning me-2">
                        <i class="fas fa-star"></i>
                        العواصم: {{ $cities->where('is_capital', true)->count() }}
                    </span>
                    <span class="badge bg-info">
                        <i class="fas fa-city"></i>
                        المدن العادية: {{ $cities->where('is_capital', false)->count() }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                @if($cities->count() > 0)

                        <table class="table table-bordered table-striped table-hover" id="cities-table">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="40%">اسم المدينة</th>
                                    <th width="15%">النوع</th>
                                    <th width="15%">الحالة</th>
                                    <th width="25%">الإحداثيات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cities as $city)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $city->name_ar }}</strong>
                                            <br><small class="text-muted">{{ $city->name_en }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($city->is_capital)
                                            <span class="badge bg-warning">
                                                <i class="fas fa-star"></i> عاصمة
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">مدينة</span>
                                        @endif
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
                                                <i class="fas fa-map-marker-alt"></i>
                                                {{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                @else
                    <div class="text-center py-5">
                        <i class="fas fa-city fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد مدن مسجلة لهذه الدولة</h5>
                        <p class="text-muted">يمكنك إضافة المدن من خلال قسم إدارة المدن</p>
                        <a href="{{ route('cities.index') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إدارة المدن
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            إجراءات سريعة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <a href="{{ route('countries.edit', $country) }}" class="btn btn-outline-primary btn-lg w-100 mb-2">
                                    <i class="fas fa-edit fa-2x mb-2"></i>
                                    <br>تعديل الدولة
                                </a>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-{{ $country->is_active ? 'warning' : 'success' }} btn-lg w-100 mb-2"
                                        onclick="toggleStatus({{ $country->id }})">
                                    <i class="fas fa-{{ $country->is_active ? 'pause' : 'play' }} fa-2x mb-2"></i>
                                    <br>{{ $country->is_active ? 'إيقاف' : 'تفعيل' }} الدولة
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('cities.index', ['country_id' => $country->id]) }}" class="btn btn-outline-info btn-lg w-100 mb-2">
                                    <i class="fas fa-city fa-2x mb-2"></i>
                                    <br>عرض المدن
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('countries.index') }}" class="btn btn-outline-secondary btn-lg w-100 mb-2">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <br>جميع الدول
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endif
@endsection

@section('js')
<script src="{{asset("admin")}}/assets/jsbundle/dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable for cities
    $('#cities-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
        },
        "pageLength": 10,
        "order": [[2, 'desc'], [1, 'asc']], // Sort by type (capitals first), then by name
        "columnDefs": [
            {"orderable": false, "targets": [0]}
        ]
    });
});

// Toggle Status Function
function toggleStatus(countryId) {
    if (confirm('هل أنت متأكد من تغيير حالة هذه الدولة؟')) {
        $.ajax({
            url: `/admin/ingaz/countries/${countryId}/toggle-status`,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload();
            },
            error: function() {
                alert('حدث خطأ في تغيير الحالة');
            }
        });
    }
}
</script>
@endsection
