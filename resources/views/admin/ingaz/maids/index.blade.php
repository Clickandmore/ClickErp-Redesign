@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/daterangepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* تحسينات للـ responsive */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .form-control {
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.8rem;
            }

            .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .form-control {
                font-size: 0.8rem;
            }
        }

        /* تحسين مظهر الجدول */
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
            padding: 0.75rem;
        }

        .badge {
            font-size: 0.75rem;
        }

        /* تحسين مظهر الصور في الجدول */
        .table img {
            max-width: 100%;
            height: auto;
        }

        /* إظهار اسم المكتب بالكامل مع التفاف مناسب */
        .table td.office-column,
        .table th.office-column-header {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            width: 200px;
        }

        /* تحسين شكل الأزرار */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-search {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
        }

        .btn-clear {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
        }

        .btn-clear:hover {
            background: #6c757d;
            color: white;
            border-color: #6c757d;
        }

        .btn-add {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border: none;
            color: white;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
            color: white;
        }

        .btn-export {
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-export.btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
        }

        .btn-export.btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
        }

        .btn-export.btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border: none;
        }

        .btn-export.btn-success:hover {
            background: linear-gradient(135deg, #1e7e34, #155724);
        }

        /* تحسين أزرار الجدول */
        .table .btn-sm {
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem 0.1rem;
            transition: all 0.2s ease;
        }

        .table .btn-sm:hover {
            transform: scale(1.05);
        }

        /* أزرار العمليات المخصصة */
        .btn-view {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            color: white;
        }

        .btn-edit {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            color: white;
        }

        .toggle-pin {
            border-radius: 50%;
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-pin.btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            border: none;
            color: #212529;
        }

        .toggle-pin.btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        .toggle-pin.btn-secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
            border: none;
            color: white;
        }

        .toggle-pin.btn-secondary:hover {
            background: linear-gradient(135deg, #545b62, #4e555b);
        }

        /* تحسين الأزرار في الشاشات الصغيرة */
        @media (max-width: 768px) {
            .btn {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
            }

            .btn-sm {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }

            .toggle-pin {
                width: 28px;
                height: 28px;
            }
        }

        @media (max-width: 576px) {
            .btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }

            .btn-sm {
                font-size: 0.65rem;
                padding: 0.15rem 0.3rem;
            }

            .toggle-pin {
                width: 24px;
                height: 24px;
            }
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("ingaz.maids")}}
@endsection
{{--@section('page-links')--}}
{{--    <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))

    <div class="card">
        <div class="card-body show">
            <form id="filterForm">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <input type="text" class="form-control" id="passport_num" name="passport_num" value="{{$request->passport_num}}" placeholder="رقم الجواز">
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <select class="form-control" name="nationality_id" id="nationality_id">
                            <option value="" selected>الجنسية</option>
                            @foreach($filter['nationalities'] as $nationality)
                                <option value="{{ $nationality->id }}" {{ ($request->nationality_id == $nationality->id) ? 'selected' : '' }}>
                                    {{ $nationality->sub_title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <select class="form-control" name="office_id" id="office_id">
                            <option value="" selected>مكتب الاستقدام</option>
                            @foreach($filter['offices'] as $office)
                                <option value="{{$office->id}}" {{ ($request->office_id == $office->id) ? 'selected' : '' }}>{{$office->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <select class="form-control" name="job_type" id="type">
                            <option value="" selected>نوع السيرة الذاتية</option>
                            @foreach($filter['job_types'] as $jobtype)
                                <option value="{{$jobtype->id}}" {{ ($request->job_type == $jobtype->id) ? 'selected' : '' }}>{{$jobtype->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <input type="text" name="experience_years" value="{{$request->experience_years}}" class="form-control" placeholder="سنوات الخبرة">
                    </div>

                    {{-- <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                        <select name="order_by" class="form-control">
                            <option value="">ترتيب حسب</option>
                            <option value="low_price" {{ ($request->order_by ?? '') == 'low_price' ? 'selected' : '' }}>الاقل سعرا</option>
                            <option value="low_salary" {{ ($request->order_by ?? '') == 'low_salary' ? 'selected' : '' }}>الاقل راتب</option>
                        </select>
                    </div> --}}
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                        <select class="form-control" name="cv_status" id="cv_status">
                            <option value="" selected>{{helperTrans("ingaz.cv_status")}}</option>
                            <option value="available" {{ ($request->cv_status == 'available') ? 'selected' : '' }}>{{helperTrans("ingaz.cv_available")}}</option>
                            <option value="reserved" {{ ($request->cv_status == 'reserved') ? 'selected' : '' }}>{{helperTrans("ingaz.cv_reserved")}}</option>
                            <option value="backout" {{ ($request->cv_status == 'backout') ? 'selected' : '' }}>باك اوت</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-3">
                        <select name="experience_status" class="form-control">
                            <option value="">جميع حالات الخبرة</option>
                            <option value="has_experience" {{ ($request->experience_status == "has_experience") ? "selected" : "" }}>سبق لها العمل</option>
                            <option value="no_experience" {{ ($request->experience_status == "no_experience") ? "selected" : "" }}>لم يسبق لها العمل</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                        <input type="text" class="form-control" name="daterange" placeholder="مدى التاريخ" data-separator=" - " autocomplete="off" data-advanced-range="true">
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-12 mb-3 text-end">
                        <button id="btnSubmit" type="button" class="btn btn-primary btn-search me-2">
                            <i class="fa fa-search me-1"></i> بحث
                        </button>
                        <a href="{{route('maids.index')}}" class="btn btn-outline-secondary btn-clear">
                            <i class="fa fa-times me-1"></i> إلغاء البحث
                        </a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 d-flex flex-wrap gap-2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CREATE))
                            <a href="{{ route('maids.create') }}" class="btn btn-success btn-add">
                                <i class="fa fa-plus me-1"></i> أضف عاملة
                            </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_PDF))
                        <a href="{{ route('maids.export.pdf') }}" class="btn btn-danger btn-export" target="_blank">
                            <i class="fa fa-file-pdf-o me-1"></i> {{helperTrans("ingaz.export_pdf")}}
                        </a>
                        @endif
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EXPORT_EXCEL))
                        <a href="{{ route('maids.export.excel') }}" class="btn btn-success btn-export">
                            <i class="fa fa-file-excel-o me-1"></i> {{helperTrans("ingaz.export_excel")}}
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
        
        <table id="dataTableObject" class="table table-striped table-hover align-middle text-center display dataTable" style="width:100%">
            <thead>
            <tr>
                <th class="d-none d-md-table-cell">ID</th>
                <th>{{helperTrans("ingaz.cv_file")}}</th>
                <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.nationality_id")}}</th>
                <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.job_type")}}</th>
                <th class="office-column-header">{{helperTrans("ingaz.office_id")}}</th>
                <th>{{helperTrans("ingaz.passport_num")}}</th>
                <th class="d-none d-md-table-cell">{{helperTrans("ingaz.price")}}</th>
                <th class="d-none d-md-table-cell">{{helperTrans("ingaz.salary")}}</th>
                <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.religion_id")}}</th>
                <th class="d-none d-md-table-cell">{{helperTrans("ingaz.age")}}</th>
                <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.maidContractRemaining")}}</th>
                <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.is_show")}}</th>
                <th class="d-none d-lg-table-cell">مثبتة</th>
                <th>{{helperTrans("ingaz.cv_status")}}</th>
                <th class="d-none d-md-table-cell">{{helperTrans("ingaz.created_at")}}</th>
                <th>{{helperTrans("ingaz.actions")}}</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

    @include('web.ingaz.maids.sub.maid_modal_view')

@else
    @include('components.no_permissions')
@endif


@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/daterangepicker.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            @if ($request)
            setTimeout(function () {
                $('#btnSubmit').trigger('click');
            }, 100);
            @endif

            $(function () {
                $('input[name="daterange"]').daterangepicker({
                    opens: 'left',
                });
                $('input[name="daterange"]').val('');
            })
            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'd-none d-md-table-cell'},
                {"data": "cv_file", orderable: false, searchable: false},
                {"data": "nationality_id", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "job_type", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "office", orderable: false, searchable: false, className: 'office-column'},
                {"data": "passport_num", orderable: true, searchable: true},
                {"data": "price", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "salary", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "religion_id", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "age", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "maidContractRemaining", orderable: false, searchable: false, className: 'd-none d-lg-table-cell'},
                {"data": "is_show", orderable: false, searchable: false, className: 'd-none d-lg-table-cell'},
                {
                    "data": "is_pinned",
                    "render": function(data, type, row) {
                        if (row.is_pinned) {
                            return '<span title="مثبتة"><i class="fa fa-thumb-tack text-warning"></i> مثبتة</span>';
                        } else {
                            return '<span title="غير مثبتة"><i class="fa fa-thumb-tack text-secondary"></i> غير مثبتة</span>';
                        }
                    },
                    orderable: false,
                    searchable: false,
                    className: 'd-none d-lg-table-cell'
                },
                {"data": "cv_status", orderable: true, searchable: true},
                {"data": "created_at", orderable: true, searchable: false, className: 'd-none d-md-table-cell'},
                {
                    "data": "actions",
                    orderable: false,
                    searchable: false
                }
            ];

            function updateDataTable(filters) {
                let url = "{{ route('maids.index') }}";
                let queryParams = buildQueryParams(filters);

                // Check if the URL already has parameters
                if (url.indexOf('?') === -1) {
                    url += '?' + queryParams;
                } else {
                    url += '&' + queryParams;
                }

                // Update the browser history with the new URL
                window.history.pushState({path: url}, '', url);

                // Clear existing data and then reload DataTable with the updated URL
                $('#dataTableObject').DataTable().clear().destroy();
                showDataTable(url, columns);
            }

            function buildQueryParams(filters) {
                let queryParams = "";
                for (let key in filters) {
                    if (filters.hasOwnProperty(key) && filters[key]) {
                        if (queryParams !== "") {
                            queryParams += "&";
                        }
                        queryParams += `${key}=${encodeURIComponent(filters[key])}`;
                    }
                }
                return queryParams;
            }

            $('#btnSubmit').on('click', function () {
                let filters = {
                    passport_num: $('#passport_num').val(),
                    nationality_id: $('#nationality_id').val(),
                    office_id: $('#office_id').val(),
                    job_type: $('#type').val(),
                    experience_years: $('input[name="experience_years"]').val(),
                    experience_status: $('select[name="experience_status"]').val(),
                    datefilter: $('input[name="daterange"]').val(),
                    order_by: $('select[name="order_by"]').val(),
                    cv_status: $('#cv_status').val()
                };
                updateDataTable(filters);
            });

            function showDataTable(url, columns) {
                console.log('Initializing DataTable with URL:', url);
                $('#dataTableObject').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    searching: false,
                    ajax: {
                        url: url,
                        type: 'GET',
                        error: function (xhr, error, thrown) {
                            console.error('DataTables error:', error);
                            $.toast({
                                heading: 'خطأ',
                                text: 'حدث خطأ أثناء تحميل البيانات',
                                showHideTransition: 'slide',
                                icon: 'error',
                                hideAfter: 3000,
                                position: 'top-right',
                                stack: 1
                            });
                        }
                    },
                    columns: columns,
                    pageLength: 10,
                    order: [], // حافظ على ترتيب الاستعلام (مثبتات أولاً ثم الأحدث)
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
                    },
                    drawCallback: function() {
                        // Reinitialize any event handlers after table redraw
                        console.log('DataTable drawCallback - reinitializing event handlers');
                        initializeEventHandlers();
                    },
                    columnDefs: [
                        {
                            targets: '_all',
                            className: 'align-middle'
                        }
                    ]
                });
            }

            function initializeEventHandlers() {
                // Remove existing event handlers to prevent duplicates
                $(document).off('click', '.toggle-pin');
                $(document).off('click', '.update-status');

                // Add click handler for pin button using event delegation
                $(document).on('click', '.toggle-pin', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    console.log('Toggle pin button clicked');
                    let button = $(this);
                    let id = button.data('id');
                    let isPinned = button.hasClass('btn-warning');
                    let confirmMsg = isPinned ? 'هل أنت متأكد من إلغاء التثبيت؟' : 'هل أنت متأكد من تثبيت هذه العاملة؟';

                    console.log('Maid ID:', id, 'Is Pinned:', isPinned);
                    console.log('Button element:', button);
                    console.log('Button data-id:', button.data('id'));
                    console.log('Button classes:', button.attr('class'));
                    console.log('Button data-pinned:', button.data('pinned'));

                    Swal.fire({
                        title: 'تأكيد',
                        text: confirmMsg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log('Making AJAX request to:', `/admin/maids/${id}/toggle-pin`);
                            console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
                            console.log('Request method: POST');
                            console.log('Request headers:', {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            });

                            $.ajax({
                                url: `/admin/maids/${id}/toggle-pin`,
                                type: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                beforeSend: function() {
                                    console.log('AJAX request started');
                                    button.prop('disabled', true);
                                },
                                success: function(response) {
                                    console.log('Toggle pin response:', response);
                                    if (response.success) {
                                        // Update the button appearance immediately
                                        if (isPinned) {
                                            button.removeClass('btn-warning').addClass('btn-secondary');
                                            button.attr('title', 'تثبيت');
                                            button.attr('data-pinned', 'false');
                                        } else {
                                            button.removeClass('btn-secondary').addClass('btn-warning');
                                            button.attr('title', 'إلغاء التثبيت');
                                            button.attr('data-pinned', 'true');
                                        }

                                        // Reload the table
                                        $('#dataTableObject').DataTable().ajax.reload();

                                        Swal.fire({
                                            icon: 'success',
                                            title: 'تم بنجاح',
                                            text: isPinned ? 'تم إلغاء التثبيت بنجاح' : 'تم تثبيت العاملة بنجاح',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'خطأ',
                                            text: response.message || 'حدث خطأ أثناء تحديث حالة التثبيت'
                                        });
                                    }
                                },
                                complete: function(xhr, status) {
                                    console.log('AJAX request completed');
                                    console.log('Response status:', xhr.status);
                                    console.log('Response status text:', xhr.statusText);
                                    button.prop('disabled', false);
                                },
                                error: function(xhr, status, error) {
                                    console.error('Toggle pin error:', xhr.responseJSON);
                                    console.error('Status:', status);
                                    console.error('Error:', error);
                                    let errorMessage = 'حدث خطأ أثناء تحديث حالة التثبيت';

                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }

                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: errorMessage
                                    });
                                }
                            });
                        }
                    });
                });

                // Add click handler for update-status button (show/hide maid)
                $(document).on('click', '.update-status', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    console.log('Update status button clicked');
                    let button = $(this);
                    let id = button.data('id');
                    let currentStatus = button.hasClass('btn-success') ? 'ظاهر' : 'مخفي';
                    let newStatus = button.hasClass('btn-success') ? 'مخفي' : 'ظاهر';
                    let confirmMsg = `هل أنت متأكد من تغيير حالة الظهور من "${currentStatus}" إلى "${newStatus}"؟`;

                    console.log('Maid ID:', id, 'Current Status:', currentStatus);

                    Swal.fire({
                        title: 'تأكيد تغيير الحالة',
                        text: confirmMsg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            console.log('Making AJAX request to:', `/admin/maids/${id}`);
                            console.log('Request method: GET');

                            $.ajax({
                                url: `/admin/maids/${id}`,
                                type: 'GET',
                                beforeSend: function() {
                                    console.log('AJAX request started');
                                    button.prop('disabled', true);
                                    button.html('<i class="fa fa-spinner fa-spin"></i>');
                                },
                                success: function(response) {
                                    console.log('Update status response:', response);
                                    if (response.success) {
                                        // Reload the table to reflect changes
                                        $('#dataTableObject').DataTable().ajax.reload();

                                        Swal.fire({
                                            icon: 'success',
                                            title: 'تم بنجاح',
                                            text: response.message || `تم تغيير حالة الظهور إلى "${newStatus}" بنجاح`,
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'خطأ',
                                            text: response.message || 'حدث خطأ أثناء تحديث حالة الظهور'
                                        });
                                    }
                                },
                                complete: function(xhr, status) {
                                    console.log('AJAX request completed');
                                    console.log('Response status:', xhr.status);
                                    console.log('Response status text:', xhr.statusText);
                                    button.prop('disabled', false);
                                    // Restore original button text
                                    if (button.hasClass('btn-success')) {
                                        button.html('ظاهر');
                                    } else {
                                        button.html('مخفي');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Update status error:', xhr.responseJSON);
                                    console.error('Status:', status);
                                    console.error('Error:', error);
                                    let errorMessage = 'حدث خطأ أثناء تحديث حالة الظهور';

                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }

                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: errorMessage
                                    });
                                }
                            });
                        }
                    });
                });
            }

            // Function to reload DataTable
            function reloadDataTable() {
                $('#dataTableObject').DataTable().ajax.reload();
            }

            $(document).on('submit', '.from-submit-global-maid', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let url = $(this).attr('action');
                let submitButton = $(e.originalEvent.submitter);
                let buttonText = submitButton.text();
                let modalResult = $("#createModal");
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function () {
                        $('#loading-wrapper').fadeIn();
                        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' + buttonText);
                    },
                    complete: function () {
                        $('#loading-wrapper').fadeOut();
                    },
                    success: function (result) {

                        console.log(200)

                        reloadDataTable();
                        submitButton.html(buttonText)
                        if (modalResult.is(":visible")) {
                            modalResult.modal('toggle');
                        }
                        window.setTimeout(function () {

                            handleSuccessResponce(result);
                            // window.location.href = '{{route('maids.index')}}';
                            var id = result.data.id;
                            // console.log(id);
                            window.location.href = "{{ route('convert_page', ':id') }}".replace(':id', id);
                            $('.loader-ajax').hide()
                        }, 20);
                        // modalResult.modal('toggle');
                        console.log(modalResult.is(':visible'));
                    },
                    error: function (errorObj, errorText, errorThrown) {
                        console.log(500)
                        console.log(errorObj)
                        handleErrorResponce(errorObj);
                    }
                });

            });

        });

    </script>
@endsection
