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
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('admin') }}/custom/admin-tables.css"> --}}
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

        #travelHistoryTable {
            border-collapse: separate !important;
            border-spacing: 0 8px;
        }

        #travelHistoryTable thead th {
            white-space: nowrap;
            font-size: 13px;
        }

        #travelHistoryTable tbody tr td {
            background: #fff;
            border-top: 1px solid #e8edf5;
            border-bottom: 1px solid #e8edf5;
            vertical-align: top;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        #travelHistoryTable tbody tr td:first-child {
            border-right: 1px solid #e8edf5;
            border-radius: 0 10px 10px 0;
        }

        #travelHistoryTable tbody tr td:last-child {
            border-left: 1px solid #e8edf5;
            border-radius: 10px 0 0 10px;
        }

        #travelHistoryTable tbody tr.grouped-trip-row td {
            background: #f5fbff;
            border-top-color: #bfe2ff;
            border-bottom-color: #bfe2ff;
        }

        .trip-index-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            min-width: 62px;
        }

        .trip-kind-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            line-height: 1.4;
        }

        .trip-kind-badge.grouped {
            background: #0d6efd1a;
            color: #0b5ed7;
        }

        .trip-kind-badge.single {
            background: #6c757d1a;
            color: #495057;
        }

        .contracts-cell {
            line-height: 1.9;
            min-width: 180px;
        }

        .calendar-trip-list {
            border: 1px solid #e4e9f2;
            border-radius: 10px;
            background: #fff;
            max-height: 280px;
            overflow-y: auto;
            padding: 8px;
        }

        .calendar-trip-item {
            display: block;
            width: 100%;
            text-align: right;
            border: 1px solid #edf1f7;
            border-radius: 8px;
            background: #f9fbff;
            padding: 8px 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all .2s ease;
        }

        .calendar-trip-item:hover {
            border-color: #bfd7ff;
            background: #f2f7ff;
        }

        .calendar-trip-item:last-child {
            margin-bottom: 0;
        }

        .fc .fc-daygrid-event {
            white-space: normal;
        }

        .calendar-event-title {
            font-size: 12px;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .calendar-event-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 4px;
            margin-top: 3px;
            font-size: 11px;
            opacity: .95;
        }

        .calendar-event-count {
            background: rgba(255, 255, 255, .22);
            border: 1px solid rgba(255, 255, 255, .35);
            padding: 0 6px;
            border-radius: 999px;
            line-height: 1.5;
        }

        .contract-pill-wrap {
            margin-top: 6px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .contract-pill {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eef4ff;
            color: #0b5ed7;
            font-size: 12px;
            border: 1px solid #d8e6ff;
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
                            @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CREATE))
                                <a href="{{ route('travel.control') }}" class="btn btn-success">
                                    <i class="fa fa-plus"></i> @lang('admin.add')
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            إجمالي رحلات الاستقدام</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalTrips">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            الرحلات القادمة</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeTrips">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane-departure fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">الرحلات المكتملة
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedTrips">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane-arrival fa-2x text-gray-300"></i>
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
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('admin.search_by_client_maid_order') }}</label>
                                        <input type="text" class="form-control" id="search_text"
                                            placeholder="{{ __('admin.search_by_client_maid_order') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('admin.travel_type') }}</label>
                                        <select id="travel_type" class="form-control">
                                            <option value="">{{ __('admin.travel_type') }}</option>
                                            @forelse (__('travel_types.travel_types') ?? [] as $k => $name)
                                                <option value="{{ $k }}">{{ $name }}</option>

                                            @empty
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('admin.status') }}</label>
                                        <select id="status" class="form-control">
                                            <option value="">الرحلات النشطة (افتراضي)</option>
                                            <option value="completed">الرحلات المكتملة</option>
                                            <option value="all">جميع الرحلات</option>
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

                <div class="card shadow mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fa fa-calendar-alt"></i> تقويم رحلات الوصول
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
                                    @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))
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
                                    @else
                                        <div class="alert alert-light border small mb-3">
                                            لديك صلاحية عرض الرحلات فقط. إسناد الرحلة متاح لمن يملك صلاحية الإدارة.
                                        </div>
                                    @endif
                                    <hr>
                                    <h6 class="mb-2">جميع رحلات الوصول</h6>
                                    <div id="calendarTripList" class="calendar-trip-list small text-muted">
                                        جاري تحميل الرحلات...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fa fa-list"></i> {{ __('admin.travel_history') }}
                        </h5>
                    </div>
                    <div class="card-body">

                        <table id="travelHistoryTable" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('admin.client_id') }}</th>
                                    <th>{{ __('admin.maid_id') }}</th>
                                    <th>العقود</th>
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
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>


    <script>
        let logisticsCalendar = null;
        let selectedCalendarTripId = null;
        const calendarScope = 'arrival';
        const travelAssignRouteTemplate = @json(route('travel.assign', ['id' => '__ID__']));

        $(document).ready(function() {
            loadTable();
            loadStatistics();
            initLogisticsCalendar();
            @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))
                loadAssignableStaff();
                $('#assignStaffSelect').on('change', toggleAssignButtonState);
                $('#assignTripBtn').on('click', assignSelectedTrip);
            @endif
            $(document).on('click', '.calendar-trip-item', function() {
                const eventId = String($(this).data('event-id'));
                if (!logisticsCalendar) {
                    return;
                }

                const event = logisticsCalendar.getEventById(eventId);
                if (!event) {
                    return;
                }

                logisticsCalendar.gotoDate(event.start);
                selectedCalendarTripId = event.id;
                renderSelectedTripInfo(event);
                toggleAssignButtonState();
            });
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
                        data: {
                            travel_scope: calendarScope
                        },
                        success: function(response) {
                            const events = response.events || [];
                            successCallback(events);
                            renderCalendarTripList(events);
                        },
                        error: function() {
                            renderCalendarTripList([]);
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
                    const p = arg.event.extendedProps || {};
                    const wrapper = document.createElement('div');
                    const title = document.createElement('div');
                    title.className = 'calendar-event-title';
                    title.textContent = arg.event.title;

                    const meta = document.createElement('div');
                    meta.className = 'calendar-event-meta';

                    const remaining = document.createElement('span');
                    remaining.textContent = p.remaining_label || '';

                    meta.appendChild(remaining);

                    if ((p.contracts_count || 0) > 1) {
                        const groupedCount = document.createElement('span');
                        groupedCount.className = 'calendar-event-count';
                        groupedCount.textContent = `${p.contracts_count} عقود`;
                        meta.appendChild(groupedCount);
                    }

                    wrapper.appendChild(title);
                    wrapper.appendChild(meta);

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

        function renderCalendarTripList(events) {
            const container = $('#calendarTripList');
            if (!events || !events.length) {
                container.html('<div class="text-muted">لا توجد رحلات في هذا النطاق.</div>');
                return;
            }

            const sortedEvents = [...events].sort((a, b) => new Date(a.start) - new Date(b.start));
            const html = sortedEvents.map(function(event) {
                const p = event.extendedProps || {};
                const dateLabel = p.arrival_date || '-';
                const timeLabel = p.arrival_time || '';
                const statusLabel = p.status_label || '-';
                const contractsCount = p.contracts_count || 1;
                const ordersText = p.order_ids_text || '';
                return `
                    <button type="button" class="calendar-trip-item" data-event-id="${event.id}">
                        <div class="fw-bold mb-1">${event.title}</div>
                        <div class="text-muted">${dateLabel} ${timeLabel}</div>
                        <div class="text-muted">العقود: ${contractsCount}${ordersText ? ` - ${ordersText}` : ''}</div>
                        <div class="text-muted">الحالة: ${statusLabel}</div>
                    </button>
                `;
            }).join('');

            container.html(html);
        }

        function renderSelectedTripInfo(event) {
            const p = event.extendedProps || {};
            const orderIds = Array.isArray(p.order_ids) ? p.order_ids : [];
            const normalizedOrderIds = orderIds.length ? orderIds : (p.order_id ? [p.order_id] : []);
            const orderPills = normalizedOrderIds.length
                ? normalizedOrderIds.map((id) => `<span class="contract-pill">#${id}</span>`).join('')
                : '<span class="text-muted">-</span>';
            const html = `
                <div class="mb-1"><strong>نوع الرحلة:</strong> ${p.travel_type_label || '-'}</div>
                <div class="mb-1"><strong>الحالة:</strong> ${p.status_label || '-'}</div>
                <div class="mb-1"><strong>عدد العقود:</strong> ${p.contracts_count || (normalizedOrderIds.length || 1)}</div>
                <div class="mb-1"><strong>العقود:</strong><div class="contract-pill-wrap">${orderPills}</div></div>
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
                    if ($.fn.DataTable.isDataTable('#travelHistoryTable')) {
                        $('#travelHistoryTable').DataTable().ajax.reload();
                    }
                    loadStatistics();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'حدث خطأ أثناء إسناد الرحلة';
                    Swal.fire('خطأ', message, 'error');
                }
            });
        }

        function loadStatistics() {
            $.ajax({
                url: "{{ route('travel.statistics') }}",
                method: 'GET',
                success: function(data) {
                    $('#totalTrips').text(data.total || 0);
                    $('#activeTrips').text(data.active || 0);
                    $('#completedTrips').text(data.completed || 0);
                    $('#upcomingTrips').text(data.upcoming || 0);
                },
                error: function() {
                    console.log('Error loading statistics');
                }
            });
        }

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
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            const isGrouped = (row.contracts_count || 0) > 1;
                            const badgeClass = isGrouped ? 'grouped' : 'single';
                            const badgeText = isGrouped ? 'مجمعة' : 'منفردة';
                            return `
                                <div class="trip-index-cell">
                                    <strong>${data}</strong>
                                    <span class="trip-kind-badge ${badgeClass}">${badgeText}</span>
                                </div>
                            `;
                        }
                    },
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
                    {
                        data: 'order_id',
                        name: 'order_id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `<div class="contracts-cell">${data || '-'}</div>`;
                        }
                    },
                    {
                        data: 'travel_type',
                        name: 'travel_type',
                        render: function(data) {
                            const types = @json(__('travel_types.travel_types'));
                            return types[data] || data;
                        }
                    },
                    {
                        data: 'travel_with',
                        name: 'travel_with',
                        render: function(data) {
                            const travelWithMap = {
                                airport: 'مطار',
                                group_transport: 'نقل جماعي',
                                train: 'قطار'
                            };
                            return travelWithMap[data] || data || '-';
                        }
                    },
                    {
                        data: 'travel_details',
                        name: 'travel_details',
                        orderable: false
                    },
                    {
                        data: 'flight_company',
                        name: 'flight_company',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'flight_number',
                        name: 'flight_number',
                        render: function(data) {
                            return data || '-';
                        }
                    },
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
                order: [
                    [0, 'desc']
                ],
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                createdRow: function(row, data) {
                    if ((data.contracts_count || 0) > 1) {
                        $(row).addClass('grouped-trip-row');
                    }
                },
                drawCallback: function() {
                    // Update statistics after table is loaded
                    loadStatistics();
                }
            });
        }



        @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_DELETE))
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
                            url: "{{ route('travel.delete', ':id') }}".replace(':id', id),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                _method: 'DELETE'
                            },
                            success: function(response) {
                                Swal.fire('تم الحذف!', 'تم حذف الرحلة بنجاح.', 'success');
                                $('#travelHistoryTable').DataTable().ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف',
                                    'error');
                            }
                        });
                    }
                });
            }
        @endif

        @if (@AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL))
            // إكتمال الرحلة
            function completeTrip(id) {
                Swal.fire({
                    title: 'إكتمال الرحلة',
                    text: 'هل أنت متأكد من إكتمال هذه الرحلة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إكتملت الرحلة',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('travel.complete', ':id') }}".replace(':id', id),
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message
                                    }).then(() => {
                                        loadTable(); // إعادة تحميل الجدول
                                        loadStatistics(); // إعادة تحميل الإحصائيات
                                    });
                                } else {
                                    Swal.fire('خطأ!', response.message, 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message ||
                                    'حدث خطأ أثناء إكتمال الرحلة', 'error');
                            }
                        });
                    }
                });
            }

            const completeTripFromLink = new URLSearchParams(window.location.search).get('complete_trip');
            if (completeTripFromLink) {
                setTimeout(function() {
                    completeTrip(completeTripFromLink);
                }, 700);
            }
        @endif
    </script>
@endsection
