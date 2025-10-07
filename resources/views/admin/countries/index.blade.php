@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
    .ui-sortable-helper {
        display: table;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .ui-sortable-placeholder {
        visibility: visible !important;
        background: #f8f9fa;
        height: 50px;
    }
    .sort-handle {
        cursor: move;
        color: #999;
        padding: 0 10px;
    }
</style>
@endsection

@section('page-title')
إدارة الدول
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::USERS_VIEW))
    <div class="page-body">
        <div class="container-fluid">
           <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-globe text-primary me-2"></i>
                إدارة الدول
            </h3>
            <div class="export-buttons text-end">
                <a href="{{ route('countries.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> إضافة دولة جديدة
                </a>
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
                               value="{{ request('search') }}" placeholder="اسم الدولة...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">رمز الدولة</label>
                        <input type="text" class="form-control" name="code" id="code"
                               value="{{ request('code') }}" placeholder="SA">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">العملة</label>
                        <input type="text" class="form-control" name="currency" id="currency"
                               value="{{ request('currency') }}" placeholder="SAR">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">جميع الحالات</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="{{ route('countries.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> إعادة تعيين
                            </a>
                            <button type="button" class="btn btn-outline-info" id="exportBtn">
                                <i class="fas fa-download"></i> تصدير
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="card shadow">
            <div class="card-body">

                    <table class="table table-bordered table-striped table-hover" id="countries-table">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px"><i class="fas fa-arrows-alt"></i></th>
                                <th>الاسم</th>
                                <th>الرمز</th>
                                <th>رمز الهاتف</th>
                                <th>العملة</th>
                                <th>الحالة</th>
                                <th>الترتيب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($allCountries as $country)
                            <tr data-id="{{ $country->id }}">
                                <td class="text-center"><i class="fa fa-arrows-alt sort-handle"></i></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <strong>{{ $country->name_ar }}</strong>
                                            <br><small class="text-muted">{{ $country->name_en }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $country->code }}</span></td>
                                <td>+{{ $country->phone_code }}</td>
                                <td>{{ $country->currency_symbol }} ({{ $country->currency }})</td>
                                <td>
                                    @if($country->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>{{ $country->sort_order }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('countries.edit', $country) }}" class="btn btn-outline-primary" title="تعديل">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-{{ $country->is_active ? 'warning' : 'success' }}"
                                                onclick="toggleStatus({{ $country->id }})" title="تغيير الحالة">
                                            <i class="fa fa-{{ $country->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                        @if (@AdminGuardHasPerm(\App\PermKeys::USERS_DELETE))
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="deleteCountry({{ $country->id }})" title="حذف">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center my-3">
                        {{ $allCountries->links('pagination::bootstrap-4') }}
                    </div>

            </div>
        </div>
    </div>
    </div>
@endif
@endsection

@section('js')
<script src="{{asset("admin")}}/assets/jsbundle/dataTables.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    // $('#countries-table').DataTable({
    //     "language": {
    //         "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json"
    //     },
    //     "pageLength": 25,
    //     "order": [[6, 'asc']],
    //     "columnDefs": [
    //         {"orderable": false, "targets": [0, 7]}
    //     ]
    // });

    // Sortable functionality
    $("#countries-table tbody").sortable({
        handle: '.sort-handle',
        update: function(event, ui) {
            let order = [];
            $('tr[data-id]').each(function(index) {
                order.push({
                    id: $(this).data('id'),
                    sort_order: index + 1
                });
            });

            $.ajax({
                url: '{{ route("countries.update-order") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order: order
                },
                success: function(response) {
                    if (response.success) {
                        $('tr[data-id]').each(function(index) {
                            $(this).find('td:eq(6)').text(index + 1);
                        });
                        showAlert('success', 'تم تحديث ترتيب الدول بنجاح');
                    }
                }
            });
        }
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
});

// Toggle Status Function
function toggleStatus(countryId) {
    if (confirm('هل أنت متأكد من تغيير حالة هذه الدولة؟')) {
        $.ajax({
            url: `/admin/ingaz/countries/${countryId}/toggle-status`,
            method: 'Post',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                location.reload();
            },
            error: function() {
                showAlert('error', 'حدث خطأ في تغيير الحالة');
            }
        });
    }
}

// Delete Country Function
function deleteCountry(countryId) {
    if (confirm('هل أنت متأكد من حذف هذه الدولة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
        $.ajax({
            url: `/admin/ingaz/countries/${countryId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                showAlert('success', 'تم حذف الدولة بنجاح');
                location.reload();
            },
            error: function() {
                showAlert('error', 'حدث خطأ في حذف الدولة');
            }
        });
    }
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
