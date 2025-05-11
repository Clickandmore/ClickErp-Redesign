@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/daterangepicker.min.css">
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
            <div class="row">
                <div class="col-md-3 ">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="passport_num" name="passport_num"
                            value="{{$request->passport_num}}" placeholder="رقم الجواز">
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3 ">
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
                <div class="col-lg-3 ml-auto">
                    <select class="form-control " name="job_type" id="type">
                        <option value="" selected="">نوع السيرة الذاتية</option>
                        @foreach($filter['job_types'] as $jobtype)
                        <option value="{{$jobtype->id}}" {{ ($request->job_type == $jobtype->id) ? 'selected' : ''
                            }}>{{$jobtype->title}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 ">
                    <div class="input-group mb-3">
                        <input type="text" name="experience_years" value="{{$request->experience_years}}"
                            class="form-control" placeholder="سنوات الخبرة">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="daterange" placeholder="مدى التاريخ"
                            data-separator=" - " autocomplete="off" data-advanced-range="true">
                        <span class="input-group-text">
                            <i class="feather-sm fa fa-calendar"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3 text-end">
                    <button id="btnSubmit" class="btn btn-light-info">بحث</button>
                    <a href="{{route('noneAvailableMaids')}}" class="btn btn-danger cancel_request_add "> إلغاء البحث </a>
                </div>
                {{-- @if(Auth::guard('admin')->user()->hasPermission('العاملات-انشاء')) --}}
                <div class="col-sm-4">
                    {!! addButton(route("maids.create") ,helperTrans("ingaz.maid")) !!}
                </div>
                {{-- @endif --}}
            </div>
        </div>
    </div>


    <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>{{helperTrans("ingaz.cv_file")}}</th>
                <th>{{helperTrans("ingaz.nationality_id")}}</th>
                <th>{{helperTrans("ingaz.passport_num")}}</th>
                <th>{{helperTrans("ingaz.price")}}</th>
                <th>{{helperTrans("ingaz.salary")}}</th>
                <th>{{helperTrans("ingaz.religion_id")}}</th>
                <th>{{helperTrans("ingaz.age")}}</th>
                <th>{{helperTrans("ingaz.height")}}</th>
                <th>{{helperTrans("ingaz.weight")}}</th>
                <th>{{helperTrans("ingaz.maidContractRemaining")}}</th>
                <th>{{helperTrans("ingaz.available")}}</th>
                <th>{{helperTrans("ingaz.available_at")}}</th>
                <th>{{helperTrans("ingaz.is_show")}}</th>
                <th>{{helperTrans("ingaz.created_at")}}</th>
                <th>{{helperTrans("ingaz.actions")}}</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>

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
                {"data": 'id', name: 'DT_RowIndex', orderable: true, searchable: false},
                {"data": "cv_file", orderable: false, searchable: false},
                {"data": "nationality_id", orderable: true, searchable: true},
                {"data": "passport_num", orderable: true, searchable: true},
                {"data": "price", orderable: true, searchable: true},
                {"data": "salary", orderable: true, searchable: true},
                {"data": "religion_id", orderable: true, searchable: true},
                {"data": "age", orderable: true, searchable: true},
                {"data": "height", orderable: true, searchable: true},
                {"data": "weight", orderable: true, searchable: true},
                {"data": "maidContractRemaining", orderable: true, searchable: false},
                {"data": "available", orderable: true, searchable: false},
                {"data": "available_at", orderable: true, searchable: false},
                {"data": "is_show", orderable: false, searchable: false},
                {"data": "created_at", orderable: false, searchable: false},
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
                    order_by: $('select[name="order_by"]').val()
                };
                updateDataTable(filters);
            });

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
