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

.actions-btn.show {
    background: #f8f9fa;
    border-color: #adb5bd;
}

/* تحسين dropdown menu */
.actions-dropdown .dropdown-menu {
    position: absolute;
    z-index: 1050;
    display: none;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: right;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 0.25rem;
}

.actions-dropdown .dropdown-menu.show {
    display: block;
}

.actions-dropdown .dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
}

.actions-dropdown .dropdown-item:hover {
    color: #1e2125;
    background-color: #f8f9fa;
}

.actions-dropdown .dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid #e9ecef;
}
</style>
@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
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
                                    <th>تفاصيل الرحلة</th>
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
</div>
@endsection

@include('components.sweetalert')

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css">

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
        searching: false,
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
                data: 'user_data',
                name: 'user_data',
                orderable: false
            },
            {
                data: 'maid_data',
                name: 'maid_data',
                orderable: false
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
                data: 'travel_details',
                name: 'travel_details',
                orderable: false
            },
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
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        drawCallback: function(settings) {
            // إخفاء الـ loader بعد اكتمال التحميل
            $('.dataTables_processing').hide();

            // تهيئة dropdown buttons
            initializeDropdowns();
        }
    });
}

// تهيئة dropdown
function initializeDropdowns() {
    // إزالة أي event listeners سابقة
    $(document).off('click', '.actions-btn');
    $(document).off('click', '.dropdown-item');

    // عند النقر على زر الإجراءات
    $(document).on('click', '.actions-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        var $dropdown = $button.next('.dropdown-menu');
        var isOpen = $dropdown.hasClass('show');

        // إغلاق جميع القوائم المفتوحة
        $('.dropdown-menu').removeClass('show');
        $('.actions-btn').attr('aria-expanded', 'false');

        // فتح/إغلاق القائمة الحالية
        if (!isOpen) {
            $dropdown.addClass('show');
            $button.attr('aria-expanded', 'true');

            // تحديد موضع القائمة
            var buttonOffset = $button.offset();
            var buttonHeight = $button.outerHeight();
            var dropdownHeight = $dropdown.outerHeight();
            var windowHeight = $(window).height();
            var scrollTop = $(window).scrollTop();

            // التحقق من وجود مساحة كافية أسفل الزر
            if (buttonOffset.top + buttonHeight + dropdownHeight > windowHeight + scrollTop) {
                $dropdown.css({
                    'position': 'absolute',
                    'top': 'auto',
                    'bottom': '100%',
                    'margin-bottom': '0.125rem'
                });
            } else {
                $dropdown.css({
                    'position': 'absolute',
                    'top': '100%',
                    'bottom': 'auto',
                    'margin-top': '0.125rem'
                });
            }
        }
    });

    // إغلاق القائمة عند النقر خارجها
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.actions-dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('.actions-btn').attr('aria-expanded', 'false');
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
