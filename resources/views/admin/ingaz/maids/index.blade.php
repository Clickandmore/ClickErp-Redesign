@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
    <link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/daterangepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                    <div class="col-md-2 mb-2">
                        <input type="text" class="form-control" id="passport_num" name="passport_num" value="{{$request->passport_num}}" placeholder="رقم الجواز">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="nationality_id" id="nationality_id">
                            <option value="" selected>الجنسية</option>
                            @foreach($filter['nationalities'] as $nationality)
                                <option value="{{ $nationality->id }}" {{ ($request->nationality_id == $nationality->id) ? 'selected' : '' }}>
                                    {{ $nationality->sub_title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="office_id" id="office_id">
                            <option value="" selected>مكتب الاستقدام</option>
                            @foreach($filter['offices'] as $office)
                                <option value="{{$office->id}}" {{ ($request->office_id == $office->id) ? 'selected' : '' }}>{{$office->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="job_type" id="type">
                            <option value="" selected>نوع السيرة الذاتية</option>
                            @foreach($filter['job_types'] as $jobtype)
                                <option value="{{$jobtype->id}}" {{ ($request->job_type == $jobtype->id) ? 'selected' : '' }}>{{$jobtype->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" name="experience_years" value="{{$request->experience_years}}" class="form-control" placeholder="سنوات الخبرة">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" class="form-control" name="daterange" placeholder="مدى التاريخ" data-separator=" - " autocomplete="off" data-advanced-range="true">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="order_by" class="form-control">
                            <option value="">ترتيب حسب</option>
                            <option value="low_price" {{ ($request->order_by ?? '') == 'low_price' ? 'selected' : '' }}>الاقل سعرا</option>
                            <option value="low_salary" {{ ($request->order_by ?? '') == 'low_salary' ? 'selected' : '' }}>الاقل راتب</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2 text-end">
                        <button id="btnSubmit" type="button" class="btn btn-info">بحث</button>
                        <a href="{{route('maids.index')}}" class="btn btn-danger">إلغاء البحث</a>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-8 d-flex gap-2">
                        @if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL))
                            <a href="{{ route('maids.create') }}" class="btn btn-success me-2">+ أضف عاملة</a>
                        @endif
                        <a href="{{ route('maids.export.pdf') }}" class="btn btn-danger me-2" target="_blank">
                            <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
                        </a>
                        <a href="{{ route('maids.export.excel') }}" class="btn btn-success">
                            <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>{{helperTrans("ingaz.cv_file")}}</th>
            <th>{{helperTrans("ingaz.nationality_id")}}</th>
            <th>{{helperTrans("ingaz.job_type")}}</th>
            <th>{{helperTrans("ingaz.passport_num")}}</th>
            <th>{{helperTrans("ingaz.price")}}</th>
            <th>{{helperTrans("ingaz.salary")}}</th>
            <th>{{helperTrans("ingaz.religion_id")}}</th>
            <th>{{helperTrans("ingaz.age")}}</th>
            <th>{{helperTrans("ingaz.maidContractRemaining")}}</th>
            <th>{{helperTrans("ingaz.is_show")}}</th>
            <th>مثبتة</th>
            <th>{{helperTrans("ingaz.created_at")}}</th>
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
    <script src="{{asset("admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
    <script src="{{asset("admin")}}/assets/js/bundle/daterangepicker.bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
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
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "cv_file", orderable: false, searchable: false},
                {"data": "nationality_id", orderable: true, searchable: true},
                {"data": "job_type", orderable: true, searchable: true},
                {"data": "passport_num", orderable: true, searchable: true},
                {"data": "price", orderable: true, searchable: true},
                {"data": "salary", orderable: true, searchable: true},
                {"data": "religion_id", orderable: true, searchable: true},
                {"data": "age", orderable: true, searchable: true},
                {"data": "maidContractRemaining", orderable: false, searchable: false},
                {"data": "is_show", orderable: false, searchable: false},
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
                    searchable: false
                },
                {"data": "created_at", orderable: true, searchable: false},
                {
                    "data": "actions",
                    "render": function(data, type, row) {
                        let actions = data;
                        if (row.available == 1) {
                            let pinButton = `<button class="btn btn-sm toggle-pin ${row.is_pinned ? 'btn-warning' : 'btn-secondary'}"
                                data-id="${row.id}"
                                title="${row.is_pinned ? 'إلغاء التثبيت' : 'تثبيت'}">
                                <i class="fa fa-thumb-tack"></i>
                            </button>`;
                            actions = pinButton + actions;
                        }
                        return actions;
                    },
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
                    datefilter: $('input[name="daterange"]').val(),
                    order_by: $('select[name="order_by"]').val()
                };
                updateDataTable(filters);
            });

            function showDataTable(url, columns) {
                $('#dataTableObject').DataTable({
                    processing: true,
                    serverSide: true,
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
                    order: [[11, 'desc']], // Order by created_at column by default in descending order
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
                    },
                    drawCallback: function() {
                        // Reinitialize any event handlers after table redraw
                        initializeEventHandlers();
                    }
                });
            }

            function initializeEventHandlers() {
                // Add click handler for pin button
                $('.toggle-pin').on('click', function() {
                    let button = $(this);
                    let id = button.data('id');
                    let isPinned = button.hasClass('btn-warning');
                    let confirmMsg = isPinned ? 'هل أنت متأكد من إلغاء التثبيت؟' : 'هل أنت متأكد من تثبيت هذه العاملة؟';

                    Swal.fire({
                        title: 'تأكيد',
                        text: confirmMsg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'نعم',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `/admin/maids/${id}/toggle-pin`,
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $('#dataTableObject').DataTable().ajax.reload();
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'تم بنجاح',
                                            text: isPinned ? 'تم إلغاء التثبيت بنجاح' : 'تم تثبيت العاملة بنجاح',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: 'حدث خطأ أثناء تحديث حالة التثبيت'
                                    });
                                }
                            });
                        }
                    });
                });
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
