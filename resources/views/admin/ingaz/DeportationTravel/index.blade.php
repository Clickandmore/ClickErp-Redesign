@extends('admin.layout.indexs.index')

@section('title')
رحلات الترحيل
@endsection

@section('style')
{{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> --}}
{{-- <link rel="stylesheet" href="{{asset("admin")}}/custom/admin-tables.css"> --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet" />
<style>
    #logisticsCalendar {
        min-height: 540px;
    }

    .calendar-side-card {
        border: 1px dashed #dce1ec;
        border-radius: 10px;
        padding: 12px;
        background: #fafcff;
    }
</style>

@endsection

@section('content')
<div class="page-body p-4 text-dark admin-tables">
    <div class="container-fluid rtl-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="page-title">
                        <i class="fa fa-plane-departure"></i> رحلات الترحيل
                    </h4>
                    <a href="{{ route('DeportationTravel.create') }}" class="btn btn-success">
                        <i class="fa fa-plus"></i> جدولة رحلة جديدة
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    إجمالي رحلات الترحيل</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalTrips">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-plane-departure fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    الرحلات النشطة</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeTrips">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-4 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    الرحلات المكتملة</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedTrips">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="contract_number" class="form-label">رقم العقد</label>
                                <input type="text" class="form-control" id="contract_number" placeholder="ابحث برقم العقد">
                            </div>
                            <div class="col-md-2">
                                <label for="maid_name" class="form-label">اسم العاملة</label>
                                <input type="text" class="form-control" id="maid_name" placeholder="ابحث باسم العاملة">
                            </div>
                            <div class="col-md-2">
                                <label for="travel_date" class="form-label">تاريخ السفر</label>
                                <input type="date" class="form-control" id="travel_date">
                            </div>
                            <div class="col-md-3">
                                <label for="status_filter" class="form-label">حالة الرحلة</label>
                                <select class="form-select" id="status_filter">
                                    <option value="">الرحلات النشطة والمجدولة</option>
                                    <option value="all">جميع الرحلات</option>
                                    <option value="new">جديدة</option>
                                    <option value="active">نشطة</option>
                                    <option value="scheduled">مجدولة</option>
                                    <option value="in_progress">قيد التنفيذ</option>
                                    <option value="pending">في الانتظار</option>
                                    <option value="confirmed">مؤكدة</option>
                                    <option value="completed">مكتملة</option>
                                    <option value="cancelled">ملغية</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button class="btn btn-primary flex-fill" onclick="loadTable()">
                                    <i class="fa fa-search"></i> بحث
                                </button>
                                <button class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fa fa-calendar-alt"></i> تقويم الخدمات اللوجستية (الاستقدام + الترحيل)
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div id="logisticsCalendar"></div>
                    </div>
                    <div class="col-lg-4">
                        <div class="calendar-side-card">
                            <h6 class="mb-3">إسناد الرحلة</h6>
                            <div id="calendarTripInfo" class="small text-muted mb-3">
                                اختر رحلة من التقويم لعرض التفاصيل وإسنادها.
                            </div>
                            <div class="mb-2">
                                <label class="form-label">الموظف / السائق</label>
                                <select id="assignStaffSelect" class="form-select">
                                    <option value="">-- اختر --</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظة الإسناد</label>
                                <textarea id="assignmentNote" class="form-control" rows="3"
                                    placeholder="ملاحظة اختيارية"></textarea>
                            </div>
                            <button id="assignTripBtn" type="button" class="btn btn-primary w-100" disabled>
                                <i class="fa fa-paper-plane"></i> إسناد وإرسال تنبيه
                            </button>
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
                            <i class="fa fa-list"></i> قائمة رحلات الترحيل
                        </h5>
                    </div>
                    <div class="card-body">

                            <table id="deportationTravelTable" class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم العقد</th>
                                        <th>بيانات العاملة</th>
                                        <th>بيانات العميل</th>
                                        <th>تفاصيل الرحلة</th>
                                        <th>الحالة</th>
                                        <th width="100">الإجراءات</th>
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
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="{{asset("admin")}}/custom/admin-tables.js"></script>
<script>
let logisticsCalendar = null;
let selectedCalendarTripId = null;
const travelAssignRouteTemplate = @json(route('travel.assign', ['id' => '__ID__']));

$(document).ready(function() {
    loadTable();
    updateStatistics();
    initLogisticsCalendar();
    loadAssignableStaff();

    $('#assignStaffSelect').on('change', toggleAssignButtonState);
    $('#assignTripBtn').on('click', assignSelectedTrip);
});

function initLogisticsCalendar() {
    const calendarEl = document.getElementById('logisticsCalendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') {
        return;
    }

    logisticsCalendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'ar',
        direction: 'rtl',
        initialView: 'dayGridMonth',
        height: 540,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            $.ajax({
                url: "{{ route('travel.calendar.events') }}",
                method: 'GET',
                success: function(response) {
                    successCallback(response.events || []);
                },
                error: function() {
                    failureCallback();
                }
            });
        },
        eventClick: function(info) {
            selectedCalendarTripId = info.event.id;
            renderSelectedTripInfo(info.event);
            toggleAssignButtonState();
        },
        eventContent: function(arg) {
            const wrapper = document.createElement('div');
            const title = document.createElement('div');
            title.className = 'fw-bold';
            title.textContent = arg.event.title;

            const remaining = document.createElement('small');
            remaining.textContent = arg.event.extendedProps.remaining_label || '';

            wrapper.appendChild(title);
            wrapper.appendChild(remaining);

            return {
                domNodes: [wrapper]
            };
        },
        eventDidMount: function(info) {
            info.el.title = `${info.event.title}\n${info.event.extendedProps.remaining_label || ''}`;
        }
    });

    logisticsCalendar.render();
}

function renderSelectedTripInfo(event) {
    const p = event.extendedProps || {};
    const html = `
        <div class="mb-1"><strong>نوع الرحلة:</strong> ${p.travel_type_label || '-'}</div>
        <div class="mb-1"><strong>الحالة:</strong> ${p.status_label || '-'}</div>
        <div class="mb-1"><strong>العميلة:</strong> ${p.client_name || 'غير محدد'}</div>
        <div class="mb-1"><strong>العاملة:</strong> ${p.maid_name || 'غير محدد'}</div>
        <div class="mb-1"><strong>موعد الوصول:</strong> ${p.arrival_date || '-'} ${p.arrival_time || ''}</div>
        <div class="mb-1"><strong>المتبقي:</strong> ${p.remaining_label || '-'}</div>
        <div class="mb-1"><strong>المكلّف الحالي:</strong> ${p.assigned_to || 'غير مسند'}</div>
    `;

    $('#calendarTripInfo').html(html);
    if (p.assigned_to_id) {
        $('#assignStaffSelect').val(String(p.assigned_to_id));
    } else {
        $('#assignStaffSelect').val('');
    }
    $('#assignmentNote').val(p.assignment_note || '');
}

function loadAssignableStaff() {
    $.ajax({
        url: "{{ route('travel.assignable-staff') }}",
        method: 'GET',
        success: function(response) {
            const select = $('#assignStaffSelect');
            select.empty().append('<option value="">-- اختر --</option>');

            (response.data || []).forEach(function(staff) {
                select.append(
                    `<option value="${staff.id}">${staff.label || staff.name}</option>`
                );
            });
        },
        error: function() {
            console.log('Failed to load assignable staff');
        }
    });
}

function toggleAssignButtonState() {
    const canAssign = !!selectedCalendarTripId && !!$('#assignStaffSelect').val();
    $('#assignTripBtn').prop('disabled', !canAssign);
}

function assignSelectedTrip() {
    if (!selectedCalendarTripId) {
        Swal.fire('تنبيه', 'يرجى اختيار رحلة من التقويم أولاً', 'warning');
        return;
    }

    const assignedTo = $('#assignStaffSelect').val();
    if (!assignedTo) {
        Swal.fire('تنبيه', 'يرجى اختيار الموظف/السائق', 'warning');
        return;
    }

    const assignUrl = travelAssignRouteTemplate.replace('__ID__', selectedCalendarTripId);
    $.ajax({
        url: assignUrl,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            assigned_to: assignedTo,
            assignment_note: $('#assignmentNote').val()
        },
        success: function(response) {
            Swal.fire('تم', response.message || 'تم إسناد الرحلة بنجاح', 'success');
            if (logisticsCalendar) {
                logisticsCalendar.refetchEvents();
            }
            if ($.fn.DataTable.isDataTable('#deportationTravelTable')) {
                $('#deportationTravelTable').DataTable().ajax.reload();
            }
            updateStatistics();
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء إسناد الرحلة';
            Swal.fire('خطأ', message, 'error');
        }
    });
}

function resetFilters() {
    $('#contract_number').val('');
    $('#maid_name').val('');
    $('#travel_date').val('');
    $('#status_filter').val('');
    loadTable();
}

function loadTable() {
    // تحديث الإحصائيات عند تغيير الفلاتر
    updateStatistics();

    if ($.fn.DataTable.isDataTable('#deportationTravelTable')) {
        $('#deportationTravelTable').DataTable().destroy();
    }

    $('#deportationTravelTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json',
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
        },
        ajax: {
            url: "{{ route('DeportationTravel.index') }}",
            type: 'GET',
            data: function(d) {
                d.contract_number = $('#contract_number').val();
                d.maid_name = $('#maid_name').val();
                d.travel_date = $('#travel_date').val();
                d.status = $('#status_filter').val();
            },
            dataSrc: function(json) {
                console.log('DataTables Response:', json);
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'contract_number', name: 'contract_number' ,searchable: true },
            { data: 'maid_data', name: 'maid_data', orderable: false  ,searchable: true },
            { data: 'client', name: 'client', orderable: false  ,searchable: true },
            { data: 'travel_details', name: 'travel_details', orderable: false ,searchable: true  },
            { data: 'status', name: 'status' },
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
        }
    });
}

// دالة حذف الرحلة
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
                url: `{{ route('DeportationTravel.index') }}/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم الحذف!', 'تم حذف الرحلة بنجاح.', 'success');
                    $('#deportationTravelTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف', 'error');
                }
            });
        }
    });
}

// دالة إكمال الرحلة
function completeTrip(id) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "هل تريد تأكيد إكمال هذه الرحلة؟",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم، أكمل الرحلة!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('DeportationTravel') }}/${id}/complete`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire('تم!', response.message || 'تم إكمال الرحلة بنجاح', 'success');
                    $('#deportationTravelTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ', 'error');
                }
            });
        }
    });
}

// دالة تحديث الإحصائيات
function updateStatistics() {
    console.log('Updating statistics...');
    $.ajax({
        url: '{{ route("DeportationTravel.statistics") }}',
        method: 'GET',
        data: {
            status: $('#status_filter').val(),
            date_from: $('#travel_date').val(),
            date_to: $('#travel_date').val()
        },
        success: function(response) {
            console.log('Statistics response:', response);
            if (response.success) {
                $('#totalTrips').text(response.data.total || 0);
                $('#activeTrips').text(response.data.active || 0);
                $('#completedTrips').text(response.data.completed || 0);
                console.log('Statistics updated successfully');
            } else {
                console.error('Statistics response not successful:', response);
            }
        },
        error: function(xhr) {
            console.error('Error loading statistics:', xhr);
        }
    });
}

// الاحتفاظ بالدالة القديمة للتوافق
function completeTravel(id) {
    completeTrip(id);
}
</script>
@endsection
