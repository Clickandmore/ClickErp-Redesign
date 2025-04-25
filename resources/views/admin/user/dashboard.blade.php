@extends('admin.layout.indexs.index')
@section("style")
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/dataTables.min.css">
<link rel="stylesheet" href="{{asset("admin")}}/assets/cssbundle/fancybox.min.css">
<style>
    .quote-imgs-thumbs {
        background: #eee;
        border: 1px solid #ccc;
        border-radius: 0.25rem;
        margin: 1.5rem 0;
        padding: 0.75rem;
    }

    .quote-imgs-thumbs--hidden {
        display: none;
    }

    .img-preview-thumb {
        background: #fff;
        border: 1px solid #777;
        border-radius: 0.25rem;
        box-shadow: 0.125rem 0.125rem 0.0625rem rgba(0, 0, 0, 0.12);
        margin-right: 1rem;
        max-width: 140px;
        padding: 0.25rem;
    }
</style>

<style>
    .contentPreviewImg {
        position: relative;
    }

    .contentPreviewImg .quote-imgs-thumbs {
        background: #fff;
        border-radius: 0.25rem;
        margin: 1.5rem 0;
        padding: 0.75rem;
        -webkit-box-shadow: 0px 0px 10px 0px #c2c2c2bf;
        box-shadow: 0px 0px 10px 0px #c2c2c2bf;
        border-radius: 15px;
        min-height: 108px;
        padding-top: 60px;
    }

    .contentPreviewImg .quote-imgs-thumbs--hidden {
        display: none;
    }

    .contentPreviewImg .xSpan {
        color: #fff;
        position: absolute;
        top: -57px;
        right: 22px;
        cursor: pointer;
        background: #000;
        width: 25px;
        height: 25px;
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
        font-size: 14px;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        margin: 0;
        border-radius: 50%;
    }

    .contentPreviewImg .xSpan:hover {
        -webkit-transition: .2s ease-in;
        transition: .2s ease-in;
        -webkit-transform: scale(1.2, 1.2);
        transform: scale(1.2, 1.2);
        color: red;
    }

    .contentPreviewImg .inputfile+label {
        font-size: 1.25em;
        font-weight: 700;
        color: white;
        background-color: black;
        display: inline-block;
        -webkit-transition: .3s ease;
        transition: .3s ease;
    }

    .contentPreviewImg .inputfile:focus+label,
    .contentPreviewImg .inputfile+label:hover {
        background-color: #00a0c4;
    }

    .contentPreviewImg .img-preview-thumb {
        background: #fff;
        border-radius: 0.25rem;
        margin-right: 1rem;
        max-width: 140px;
    }

    .contentPreviewImg [type="file"] {
        border: 0;
        clip: rect(0, 0, 0, 0);
        height: 1px;
        overflow: hidden;
        padding: 0;
        position: absolute !important;
        white-space: nowrap;
        width: 1px;
    }

    .contentPreviewImg [type="file"]+label {
        background-color: #00ae40;
        border-radius: 4rem;
        color: #fff;
        cursor: pointer;
        display: inline-block;
        padding-left: 2rem 4rem;
        -webkit-transition: .3s ease;
        transition: .3s ease;
        position: absolute;
        top: 50px;
        left: 30px;
    }

    .contentPreviewImg [type="file"]:focus+label,
    .contentPreviewImg [type="file"]+label:hover {
        background-color: #0093c9;
    }

    .contentPreviewImg [type="file"]:focus+label {
        outline: none;
    }

    .contentPreviewImg img {
        width: 150px;
        height: 150px;
        margin: 8px;
        -webkit-box-shadow: 0px 0px 10px 0px #c2c2c2bf;
        box-shadow: 0px 0px 10px 0px #c2c2c2bf;
        border-radius: 8px;
    }

    .contentPreviewImg label {
        display: -webkit-box !important;
        display: -ms-flexbox !important;
        display: flex !important;
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
        -webkit-box-pack: center;
        -ms-flex-pack: center;
        justify-content: center;
    }

    .contentPreviewImg label span {
        padding: 10px 15px 10px 10px;
    }

    .contentPreviewImg label i {
        font-size: 30px !important;
        padding: 5px 10px;
    }

    .remove_and_update {
        display: none !important;
    }

    .remove_and_update2 {
        display: none !important;
    }


    /*# sourceMappingURL=style.css.map */
</style>

@endsection
@section('page-title')
الرئيسية
@endsection
@section('page-links')
<li class="breadcrumb-item active" aria-current="page"> الرئيسية</li>
@endsection
@section('content')

<div class="page-toolbar px-xl-4 px-sm-2 px-0 py-3">
    <div class="container-fluid">
        {{-- <div class="row g-3 mb-3 align-items-center">
            <div class="col">
                <ol class="breadcrumb bg-transparent mb-0">
                    <li class="breadcrumb-item"><a class="text-secondary" href="index.html">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-secondary" href="crafted-page.html">Crafted pages</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">My Profile</li>
                </ol>
            </div>
        </div> --}}
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="fs-5 color-900 mt-1 mb-0">مرحبا بك, {{$employee->name}}!</h1>
            </div>
        </div>
    </div>
</div>

<div class="page-body px-xl-4 px-sm-2 px-0 py-lg-2 py-1 mt-0 mt-lg-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body border-bottom">
                        <div class="d-flex align-items-md-start align-items-center flex-column flex-md-row">
                            <img src="{{showFile($employee->image)}}" style="width: 160px;height:160px" alt="" class="rounded-4">
                            <div class="media-body ms-md-5 m-0 mt-4 mt-md-0 text-md-start text-center">
                                <h4 class="mb-1 fw-light">{{$employee->name}}<a href="#"
                                        class="fa fa-pencil-square-o fs-6 ms-2" data-bs-toggle="offcanvas"
                                        data-bs-target="#edit_profile" title="Edit Profile"></a></h4>
                                <p><a href="/cdn-cgi/l/email-protection" class="__cf_email__"
                                        data-cfemail="a0c1ccccc9c5c7d2c1d4c5d2e0ccd5cecf8ec3cfcd">[{{$employee->email}}]</a>
                                </p>
                                <div
                                    class="d-flex flex-row flex-wrap align-items-center justify-content-center justify-content-md-start">
                                    <div class="card py-2 px-3 me-2 mt-2">
                                        <small class="text-muted">المرتب</small>
                                        <div class="fs-5">{{$employee->salary}}</div>
                                    </div>
                                    <div class="card py-2 px-3 me-2 mt-2">
                                        <small class="text-muted">البدلات</small>
                                        <div class="fs-5">{{ $allowanceSum }}</div>
                                    </div>
                                    <div class="card py-2 px-3 me-2 mt-2">
                                        <small class="text-muted">التأمينات</small>
                                        <div class="fs-5">{{ $employee->insurances()->sum('emp_value') }}</div>
                                    </div>
                                    <div class="card py-2 px-3 me-2 mt-2">
                                        <small class="text-muted">الضرايب</small>
                                        <div class="fs-5">{{ $taxs }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="nav nav-tabs tab-card border-bottom-0 pt-2 fs-6 justify-content-center justify-content-md-start"
                        role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#profile_post"
                                role="tab"><span>Overview</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#profile_groups"
                                role="tab"><i class="fa fa-address-card-o"></i><span
                                    class="d-none d-sm-inline-block ms-2">الأجازات</span></a>
                        </li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#profile_project"
                                role="tab"><i class="fa fa-list-alt"></i><span
                                    class="d-none d-sm-inline-block ms-2">الجزائات</span></a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#profile_campaigns"
                                role="tab" id="tab_profile_campaigns"><i class="fa fa-area-chart"></i><span
                                    class="d-none d-md-inline-block ms-2">الأذونات</span></a></li>
                    </ul>
                </div>
                <div class="tab-content mt-5">

                    <div class="tab-pane fade show active" id="profile_post" role="tabpanel">
                        <div class="row-title mb-2">
                            <h5>Profile Overview</h5>
                        </div>
                        <div class="row g-3">
                            <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-12">
                                <div class="dropdown morphing scale-left">
                                <ul class="dropdown-menu shadow border-0 p-2">
                                    <li><a class="dropdown-item" href="#">File Info</a></li>
                                    <li><a class="dropdown-item" href="#">Copy to</a></li>
                                    <li><a class="dropdown-item" href="#">Move to</a></li>
                                    <li><a class="dropdown-item" href="#">Rename</a></li>
                                    <li><a class="dropdown-item" href="#">Block</a></li>
                                    <li><a class="dropdown-item" href="#">Delete</a></li>
                                </ul>
                                </div>

                                <div class="card-body">
                                    <div id="apex-Attendance"></div>
                                </div>
                            </div>
                            <div class="col-xxl-6 col-xl-4 col-lg-4 col-md-12">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">البيانات الشخصية</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li class="py-2"><span class="text-muted me-2 w90 d-inline-block">الاسم:</span>{{$employee->name}}</li>
                                            <li class="py-2"><span
                                                    class="text-muted me-2 w90 d-inline-block">الايميل:</span><a
                                                    href="/cdn-cgi/l/email-protection" class="__cf_email__"
                                                    data-cfemail="fe9f9292979b998c9f8a9b8cbe928b9091d09d9193">[{{$employee->email}}]</a>
                                            </li>
                                            <li class="py-2"><span
                                                    class="text-muted me-2 w90 d-inline-block">الجوال:</span>{{$employee->phone}}</li>
                                            <li class="py-2"><span
                                                    class="text-muted me-2 w90 d-inline-block">العنوان:</span>{{$employee->address}}</li>
                                            <li class="py-2"><span
                                                    class="text-muted me-2 w90 d-inline-block">الكود:</span>{{$employee->code}}
                                            </li>
                                            <li class="py-2"><span
                                                class="text-muted me-2 w90 d-inline-block">ؤقم الهوية:</span>{{$employee->national_num}}
                                            </li>
                                            <li class="py-2"><span
                                                class="text-muted me-2 w90 d-inline-block">تاريخ الميلاد:</span>{{$employee->birthday}}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xxl-6 col-xl-12 col-lg-12 col-md-12">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title mb-0">البيانات الوظيفية</h6>
                                    </div>
                                    <ul class="list-group list-group-flush list-group-custom">
                                        <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                href="#">القسم
                                                <span>{{$employee->departments->title}}</span></a></li>
                                        <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                href="#">الوظيفة
                                                <span>{{$employee->jobs->title}}</span></a></li>
                                        <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                href="#">الشفت
                                                <span>{{$employee->shift->shift->name}}</span></a></li>
                                        <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                href="#">تاريخ بدالة التعاقد
                                                <span>{{$employee->hired_date}}</span></a></li>
                                        <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                href="#">تاريخ انتهاء التعاقد
                                                <span>{{$employee->contract_end_date}}</span></a></li>
                                    </ul>
                                </div>

                                <script data-cfasync="false"
                                    src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js">
                                </script>
                                <script src="{{asset("admin")}}/assets/js/bundle/fancybox.bundle.js"></script>

                                <script>
                                    /*	end gallery */
                        $(document).ready(function () {
                        $(".fancybox").fancybox({
                            openEffect: "none",
                            closeEffect: "none"
                        });
                        });
                                </script>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="profile_groups" role="tabpanel">
                        <table class="table">
                            <thead>
                              <tr>
                                <th scope="col">#</th>
                                <th scope="col">اسم الموظف</th>
                                <th scope="col">اسم الاجازة</th>
                                <th scope="col">القيمة</th>
                                <th scope="col">من تاريخ</th>
                                <th scope="col">الى تاريخ</th>
                                <th scope="col">العمليات</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach ($vacations as $item)
                                    <tr>
                                        <th scope="row">{{$item->id}}</th>
                                        <td>{{$item->employee->name}}</td>
                                        <td>{{$item->vacation->title}}</td>
                                        <td>{{$item->value}}</td>
                                        <td>{{$item->from_date}}</td>
                                        <td>{{$item->to_date}}</td>
                                        <td>
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_vacations-update')) --}}
                                                <button type="button" class="btn btn-success float-start addButton mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                                                    model-title="تعديل"  model-route="{{route("employeeVacations.edit", $item->id)}}" title="تعديل" >
                                                    <i class="fa fa-plus-circle"></i> تعديل
                                                </button>
                                            {{-- @endif --}}
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_vacations-delete')) --}}
                                                <button type="button" class="btn btn-danger deleteButton" delete-route="{{route("employeeVacations.destroy", $item->id)}}">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            {{-- @endif --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="profile_project" role="tabpanel">
                        <table class="table">
                            <thead>
                              <tr>
                                <th scope="col">#</th>
                                <th scope="col">اسم الموظف</th>
                                <th scope="col">اسم الاجازة</th>
                                <th scope="col"> تاريخ</th>
                                <th scope="col">وقت</th>
                                <th scope="col">العمليات</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $item)
                                    <tr>
                                        <th scope="row">{{$item->id}}</th>
                                        <td>{{$item->employee->name}}</td>
                                        <td>{{$item->permission->title}}</td>
                                        <td>{{$item->date}}</td>
                                        <td>{{$item->time}}</td>
                                        <td>
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_permissions-update')) --}}
                                                <button type="button" class="btn btn-success float-start addButton mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                                                    model-title="تعديل"  model-route="{{route("employeePermissions.edit", $item->id)}}" title="تعديل" >
                                                    <i class="fa fa-plus-circle"></i> تعديل
                                                </button>
                                            {{-- @endif --}}
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_permissions-delete')) --}}
                                                <button type="button" class="btn btn-danger deleteButton" delete-route="{{route("employeePermissions.destroy", $item->id)}}">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            {{-- @endif --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="profile_campaigns" role="tabpanel">
                        <table class="table">
                            <thead>
                              <tr>
                                <th scope="col">#</th>
                                <th scope="col">اسم الموظف</th>
                                <th scope="col">اسم الاجازة</th>
                                <th scope="col">القيمة</th>
                                <th scope="col"> تاريخ</th>
                                <th scope="col">العمليات</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach ($sanctions as $item)
                                    <tr>
                                        <th scope="row">{{$item->id}}</th>
                                        <td>{{$item->employee->name}}</td>
                                        <td>{{$item->sanction->title}}</td>
                                        <td>{{$item->value}}</td>
                                        <td>{{$item->date}}</td>
                                        <td>
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_sanctions-update')) --}}
                                                <button type="button" class="btn btn-success float-start addButton mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                                                    model-title="تعديل"  model-route="{{route("employeeSanctions.edit", $item->id)}}" title="تعديل" >
                                                    <i class="fa fa-plus-circle"></i> تعديل
                                                </button>
                                            {{-- @endif --}}
                                            {{-- @if (Auth::guard('admin')->user()->hasPermission('hr_employee_sanctions-delete')) --}}
                                                <button type="button" class="btn btn-danger deleteButton" delete-route="{{route("employeeSanctions.destroy", $item->id)}}">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>
                                            {{-- @endif --}}
                                        </td>
                                    </tr>
                                @endforeach
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
<script src="{{asset("admin")}}/assets/js/bundle/fancybox.bundle.js"></script>
<script src="{{asset("admin")}}/assets/js/bundle/apexcharts.bundle.js"></script>
<script src="{{asset("admin")}}/assets/js/bundle/apexcharts.bundle.js"></script>

<script>
    var options = {
      chart: {
        height: 500,
        type: 'heatmap',
        toolbar: {
          show: false,
        },
      },
      plotOptions: {
        heatmap: {
          shadeIntensity: 0.7,
          radius: 0,
          useFillColorAsStroke: false,
          colorScale: {
            ranges: [{
              from: 0,
              to: 0,
              name: 'غياب',
              color: '#E73957'
            }, {
              from: 1,
              to: 1,
              name: 'حضور',
              color: '#79B989'
            }, {
              from: 2,
              to: 2,
              name: 'لم يتواجد',
              color: '#808080'
            }]
          }
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        width: 2,
      },
      series:{!! json_encode($series) !!},
      xaxis: {
            categories: [1, 2, 3, 4, 5, 6, 7, 8, 9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30],
        }
    };
    var chart = new ApexCharts(document.querySelector("#apex-Attendance"), options);
    chart.render();
</script>

@endsection
