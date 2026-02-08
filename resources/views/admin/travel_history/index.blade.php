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
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>


    <script>
        $(document).ready(function() {
            loadTable();
            loadStatistics();
        });

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
                        searchable: false
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
                        name: 'order_id'
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
                        name: 'travel_with'
                    },
                    {
                        data: 'travel_details',
                        name: 'travel_details',
                        orderable: false
                    },
                    {
                        data: 'flight_company',
                        name: 'flight_company'
                    },
                    {
                        data: 'flight_number',
                        name: 'flight_number'
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
                drawCallback: function() {
                    // Update statistics after table is loaded
                    loadStatistics();
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
        @endif
    </script>
@endsection
