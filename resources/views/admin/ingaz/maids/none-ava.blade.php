@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/daterangepicker.min.css">
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
    }

    .badge {
        font-size: 0.75rem;
    }

    /* تحسين مظهر الصور في الجدول */
    .table img {
        max-width: 100%;
        height: auto;
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

    .btn-light-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border: none;
        color: white;
    }

    .btn-light-info:hover {
        background: linear-gradient(135deg, #138496, #117a8b);
        color: white;
    }

    .cancel_request_add {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: none;
        color: white;
    }

    .cancel_request_add:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
        color: white;
    }

    /* تحسين أزرار الجدول */
    .table .btn-sm {
        border-radius: 6px;
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        margin: 0 0.1rem;
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
    }
</style>
@endsection
@section('page-title')
{{helperTrans("ingaz.maids")}}
@endsection
{{--@section('page-links')--}}
{{-- <li class="breadcrumb-item active" aria-current="page"> {{__("finance.clients")}}</li>--}}
{{--@endsection--}}
@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW))

    <div class="card">
        <div class=" card-body show">
            <div class="row align-items-end">
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="passport_num" name="passport_num"
                            value="{{$request->passport_num}}" placeholder="رقم الجواز">
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <select class="form-control" name="nationality_id" id="nationality_id">
                            <option value="" selected="">الجنسية</option>
                            @foreach($filter['nationalities'] as $nationality)
                            <option value="{{ $nationality->id }}" {{ ($request->nationality_id == $nationality->id) ?
                                'selected' : '' }}>
                                {{ $nationality->sub_title }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <select class="form-control " name="office_id" id="office_id">
                            <option value="" selected="">مكتب الاستقدام</option>
                            @foreach($filter['offices'] as $office)
                            <option value="{{$office->id}}" {{ ($request->office_id == $office->id) ? 'selected' : ''
                                }}>{{$office->title}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <select class="form-control " name="job_type" id="type">
                        <option value="" selected="">نوع السيرة الذاتية</option>
                        @foreach($filter['job_types'] as $jobtype)
                        <option value="{{$jobtype->id}}" {{ ($request->job_type == $jobtype->id) ? 'selected' : ''
                            }}>{{$jobtype->title}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <input type="text" name="experience_years" value="{{$request->experience_years}}"
                            class="form-control" placeholder="سنوات الخبرة">
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="daterange" placeholder="مدى التاريخ"
                            data-separator=" - " autocomplete="off" data-advanced-range="true">
                        <span class="input-group-text">
                            <i class="feather-sm fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <select name="order_by" class="form-control">
                            <option value="">ترتيب حسب</option>
                            <option value="low_price" {{ ($request->order_by ?? '') == 'low_price' ? 'selected' : '' }}>
                                الاقل سعرا
                            </option>
                            <option value="low_salary" {{ ($request->order_by ?? '') == 'low_salary' ? 'selected' : '' }}>
                                الاقل راتب
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                    <div class="input-group mb-3">
                        <select class="form-control" name="cv_status" id="cv_status">
                            <option value="" selected>{{helperTrans("ingaz.cv_status")}}</option>
                            <option value="available" {{ ($request->cv_status == 'available') ? 'selected' : '' }}>{{helperTrans("ingaz.cv_available")}}</option>
                            <option value="reserved" {{ ($request->cv_status == 'reserved') ? 'selected' : '' }}>{{helperTrans("ingaz.cv_reserved")}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-2 text-end">
                    <button id="btnSubmit" class="btn btn-light-info btn-search me-2">
                        <i class="fa fa-search me-1"></i> بحث
                    </button>
                    <a href="{{route('noneAvailableMaids')}}" class="btn btn-danger cancel_request_add">
                        <i class="fa fa-times me-1"></i> إلغاء البحث
                    </a>
                </div>
                {{-- @if(Auth::guard('admin')->user()->hasPermission('العاملات-انشاء')) --}}
                <div class="col-12 mt-2">
                    <a href="{{ route('maids.create') }}" class="btn btn-success btn-add">
                        <i class="fa fa-plus me-1"></i> {{helperTrans("ingaz.maid")}}
                    </a>
                </div>
                {{-- @endif --}}
            </div>
        </div>
    </div>


    <div class="table-responsive">
        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
                <tr>
                    <th class="d-none d-md-table-cell">ID</th>
                    <th>{{helperTrans("ingaz.cv_file")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.nationality_id")}}</th>
                    <th>{{helperTrans("ingaz.passport_num")}}</th>
                    <th class="d-none d-md-table-cell">{{helperTrans("ingaz.price")}}</th>
                    <th class="d-none d-md-table-cell">{{helperTrans("ingaz.salary")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.religion_id")}}</th>
                    <th class="d-none d-md-table-cell">{{helperTrans("ingaz.age")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.height")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.weight")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.maidContractRemaining")}}</th>
                    <th class="d-none d-md-table-cell">{{helperTrans("ingaz.available")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.available_at")}}</th>
                    <th class="d-none d-lg-table-cell">{{helperTrans("ingaz.is_show")}}</th>
                    <th>{{helperTrans("ingaz.cv_status")}}</th>
                    <th class="d-none d-md-table-cell">{{helperTrans("ingaz.created_at")}}</th>
                    <th>{{helperTrans("ingaz.actions")}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>

@else
    @include('components.no_permissions')
@endif

@include('web.ingaz.maids.sub.maid_modal_view')

@endsection
@section('js')
<script src="{{asset(" admin")}}/assets/js/bundle/dataTables.bundle.js"></script>
<script src="{{asset(" admin")}}/assets/js/bundle/daterangepicker.bundle.js"></script>
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
                {"data": 'id', name: 'DT_RowIndex', orderable: true, searchable: false, className: 'd-none d-md-table-cell'},
                {"data": "cv_file", orderable: false, searchable: false},
                {"data": "nationality_id", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "passport_num", orderable: true, searchable: true},
                {"data": "price", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "salary", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "religion_id", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "age", orderable: true, searchable: true, className: 'd-none d-md-table-cell'},
                {"data": "height", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "weight", orderable: true, searchable: true, className: 'd-none d-lg-table-cell'},
                {"data": "maidContractRemaining", orderable: true, searchable: false, className: 'd-none d-lg-table-cell'},
                {"data": "available", orderable: true, searchable: false, className: 'd-none d-md-table-cell'},
                {"data": "available_at", orderable: true, searchable: false, className: 'd-none d-lg-table-cell'},
                {"data": "is_show", orderable: false, searchable: false, className: 'd-none d-lg-table-cell'},
                {"data": "cv_status", orderable: false, searchable: false},
                {"data": "created_at", orderable: false, searchable: false, className: 'd-none d-md-table-cell'},
                {"data": "actions", orderable: false, searchable: false}
            ];

            function updateDataTable(filters) {
                let url = "{{ route('noneAvailableMaids') }}";
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
                    order_by: $('select[name="order_by"]').val(),
                    cv_status: $('#cv_status').val()
                };
                updateDataTable(filters);
            });

            function showDataTable(url, columns) {
                $('#dataTableObject').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: url,
                        type: 'GET'
                    },
                    columns: columns,
                    pageLength: 10,
                    order: [[15, 'desc']], // Order by created_at column by default in descending order
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>'
                    },
                    columnDefs: [
                        {
                            targets: '_all',
                            className: 'align-middle'
                        }
                    ]
                });
            }

            showDataTable("{{ route("noneAvailableMaids") }}", columns);
        });

        // ِChange Status Using Ajax
        $(document).on('click', '.update-status', function (event) {
            var rowId = $(this).data('id');
            var url = "{{ route('maids.show', ':id') }}";
            url = url.replace(':id', rowId);
            $.ajax({
                type: 'Get',
                url: url,
                success: function (data) {
                    if (data.success) {
                        reloadDataTable();
                        $.toast({
                            heading: 'نجاح',
                            text: 'تم تغيير الحالة بنجاح!',
                            showHideTransition: 'slide',
                            icon: 'success',
                            hideAfter: 2000,
                            position: 'top-right',
                            stack: 1
                        });
                    } else {
                        alert('فشل في تحديث الحالة.');
                    }
                }
            });
        });


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
                        // window.location.href = '{{route('noneAvailableMaids')}}';
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

</script>
@endsection
