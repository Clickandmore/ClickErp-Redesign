@extends('admin.layout.indexs.index')

@php
    $airports = [
        'RUH' => 'مطار الملك خالد الدولي - الرياض (RUH)',
        'JED' => 'مطار الملك عبدالعزيز الدولي - جدة (JED)',
        'DMM' => 'مطار الملك فهد الدولي - الدمام (DMM)',
        'MED' => 'مطار الأمير محمد بن عبدالعزيز الدولي - المدينة المنورة (MED)',
        'TUU' => 'مطار تبوك الدولي - تبوك (TUU)',
        'AHB' => 'مطار أبها الإقليمي - أبها (AHB)',
        'TIF' => 'مطار الطائف الإقليمي - الطائف (TIF)',
        'ELQ' => 'مطار القصيم الإقليمي - بريدة (ELQ)',
        'URY' => 'مطار الجوف الإقليمي - سكاكا (URY)',
        'HAS' => 'مطار حائل الإقليمي - حائل (HAS)',
        'BHH' => 'مطار الباحة الإقليمي - الباحة (BHH)',
        'GIZ' => 'مطار جازان الإقليمي - جازان (GIZ)',
    ];
@endphp

@section('title')
{{ __('admin.travel') }}
@endsection

@section('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">
<style>
/* إزالة الظلال من الجدول */
.table * {
    text-shadow: none !important;
    box-shadow: none !important;
}

/* تحسين وضوح النص */
.table td, .table th {
    color: #212529 !important;
    background-color: transparent;
}

.table tbody tr:hover {
    background-color: #f8f9fa !important;
}

/* تحسين الشارات */
.badge {
    text-shadow: none !important;
    box-shadow: none !important;
}

/* التأكد من وضوح البيانات */
.text-muted {
    color: #6c757d !important;
    text-shadow: none !important;
}

.table strong {
    color: #212529 !important;
    font-weight: 600;
}

.table small {
    color: #6c757d !important;
}

/* إزالة أي تأثيرات من admin-tables.css */
#travelHistoryTable td,
#travelHistoryTable th,
#travelHistoryTable * {
    text-shadow: none !important;
    box-shadow: none !important;
    filter: none !important;
    opacity: 1 !important;
}

.maid-info, .user-info {
    line-height: 1.6;
}

.maid-info .fw-bold, .user-info .fw-bold {
    display: block;
    margin-bottom: 2px;
}

.travel-details {
    line-height: 1.8;
}

.travel-details i {
    width: 20px;
    color: #5a5c69;
    margin-left: 5px;
}

/* تنسيق زر الإجراءات */
.actions-dropdown {
    position: relative;
}

.actions-btn {
    background: #fff;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    transition: all 0.15s ease-in-out;
}

.actions-btn:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #495057;
}

.actions-btn i {
    margin-left: 0.25rem;
}

/* القائمة المنسدلة */
.dropdown-menu {
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0;
    font-size: 0.875rem;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.25rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    color: #212529;
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    color: #16181b;
}

.dropdown-item i {
    width: 20px;
    margin-left: 0.5rem;
    color: inherit;
}
</style>
@endsection

@section('content')
@if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_VIEW))

<div class="container-fluid rtl-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="page-title">
                    <i class="fa fa-plane-arrival"></i> {{ __('admin.travel') }}
                </h4>
                <a href="{{ route('travel.control') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> @lang('admin.add')
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('admin.search_by_client_maid_order') }}</label>
                            <input type="text" class="form-control" id="search_text" placeholder="{{ __('admin.search_by_client_maid_order') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('admin.travel_type') }}</label>
                            <select id="travel_type" class="form-control">
                                <option value="">{{ __('admin.travel_type') }}</option>
                                @forelse (__('travel_types.travel_types') ?? [] as $k => $name)
                                    <option value="{{$k}}">{{ $name }}</option>
                                @empty
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('admin.status') }}</label>
                            <select id="status" class="form-control">
                                <option value="">{{ __('admin.status') }}</option>
                                @forelse (__('travel_types.status') ?? [] as $k => $name)
                                    <option value="{{$k}}">{{ $name }}</option>
                                @empty
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="loadTable()">
                                <i class="fa fa-search"></i> {{ __('admin.search') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-list"></i> {{ __('admin.travel_history') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="travelHistoryTable" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('admin.client_id') }}</th>
                                    <th>{{ __('admin.maid_id') }}</th>
                                    <th>رقم العقد</th>
                                    <th>{{ __('admin.travel_type') }}</th>
                                    <th>{{ __('admin.travel_with') }}</th>
                                    <th>{{ __('admin.arrival_airport') }}</th>
                                    <th>{{ __('admin.arrival_date') }}</th>
                                    <th>{{ __('admin.arrival_time') }}</th>
                                    <th>{{ __('admin.flight_company') }}</th>
                                    <th>{{ __('admin.flight_number') }}</th>
                                    <th>{{ __('admin.status') }}</th>
                                    <th width="100">{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@else
    @include('components.no_permissions')
@endif
@endsection

@include('components.sweetalert')

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    loadTable();
});

function loadTable() {
    if ($.fn.DataTable.isDataTable('#travelHistoryTable')) {
        $('#travelHistoryTable').DataTable().destroy();
    }

    $('#travelHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
        },
        ajax: {
            url: "{{ route('travel') }}",
            type: 'GET',
            data: function(d) {
                d.search = $('#search_text').val();
                d.travel_type = $('#travel_type').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            {
                data: 'user_id',
                name: 'user_id',
                render: function(data, type, row) {
                    if (row.user) {
                        return `<div class="user-info">
                            <span class="fw-bold">${row.user.name || 'غير محدد'}</span><br>
                            <small class="text-muted">الهاتف: ${row.user.mobile || 'غير محدد'}</small>
                        </div>`;
                    }
                    return '<span class="text-muted">غير محدد</span>';
                }
            },
            {
                data: 'maid_id',
                name: 'maid_id',
                render: function(data, type, row) {
                    if (row.maid) {
                        let nationality = 'غير محدد';
                        if (row.maid.nationality && row.maid.nationality.translations && row.maid.nationality.translations.length > 0) {
                            nationality = row.maid.nationality.translations[0].sub_title || row.maid.nationality.title || 'غير محدد';
                        }
                        return `<div class="maid-info">
                            <span class="fw-bold">${row.maid.name || 'غير محدد'}</span><br>
                            <small class="text-muted">الجواز: ${row.maid.passport_num || 'غير محدد'}</small><br>
                            <small class="text-muted">الجنسية: ${nationality}</small>
                        </div>`;
                    }
                    return '<span class="text-muted">غير محدد</span>';
                }
            },
            { data: 'order_id', name: 'order_id' },
            {
                data: 'travel_type',
                name: 'travel_type',
                render: function(data) {
                    const types = @json(__('travel_types.travel_types'));
                    return types[data] || data;
                }
            },
            { data: 'travel_with', name: 'travel_with' },
            {
                data: 'arrival_airport',
                name: 'arrival_airport',
                render: function(data) {
                    const airports = @json($airports);
                    return airports[data] || data;
                }
            },
            { data: 'arrival_date', name: 'arrival_date' },
            { data: 'arrival_time', name: 'arrival_time' },
            { data: 'flight_company', name: 'flight_company' },
            { data: 'flight_number', name: 'flight_number' },
            {
                data: 'status',
                name: 'status',
                render: function(data) {
                    const statuses = @json(__('travel_types.status'));
                    const statusClass = data === 'completed' ? 'success' : 'warning';
                    return `<span class="badge bg-${statusClass}">${statuses[data] || data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let html = '<div class="dropdown actions-dropdown">';
                    html += '<button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">';
                    html += '<i class="fas fa-cogs me-1"></i> الإجراءات';
                    html += '</button>';
                    html += '<ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // تعديل
                    html += `<li><a class="dropdown-item" href="{{ route('travel.control') }}?id=${row.id}&userId=${row.user_id}&maidId=${row.maid_id}&orderId=${row.order_id}">
                        <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                    </a></li>`;

                    @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))
                    html += '<li><hr class="dropdown-divider"></li>';

                    // حذف
                    html += `<li><a class="dropdown-item" href="javascript:void(0)" onclick="deleteTravel(${row.id})">
                        <i class="fas fa-trash"></i> {{ __('admin.delete') }}
                    </a></li>`;
                    @endif

                    html += '</ul>';
                    html += '</div>';

                    return html;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            // إخفاء الـ loader بعد اكتمال التحميل
            $('.dataTables_processing').hide();
        }
    });
}

@if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))
function deleteTravel(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "لن تتمكن من استرجاع هذه الرحلة بعد الحذف!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('travel.delete', '') }}/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم الحذف!', 'تم حذف الرحلة بنجاح.', 'success');
                    $('#travelHistoryTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            });
        }
    });
}
@endif
</script>
@endsection
