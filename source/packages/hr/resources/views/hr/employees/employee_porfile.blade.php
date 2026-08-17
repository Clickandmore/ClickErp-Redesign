@extends('click.layout.indexs.index')
@section('page-title')
    {{$bladeTitle}}
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{$bladeTitle}}</li>
@endsection
@section('content')
    <div class="page-body px-xl-4 px-sm-2 px-0 py-lg-2 py-1 mt-0 mt-lg-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body border-bottom">
                            <div class="d-flex align-items-md-start align-items-center flex-column flex-md-row">
                                <img src="{{ showUserImage($employee->image) }}" width="170"
                                     height="170" alt="" class="rounded-4">
                                <div class="media-body ms-md-5 m-0 mt-4 mt-md-0 text-md-start text-center">
                                    <h4 class="mb-1 fw-light">{{$employee->name}}
                                        {{--                                        <a href="#" class="fa fa-pencil-square-o fs-6 ms-2" data-bs-toggle="offcanvas" data-bs-target="#edit_profile" title="Edit Profile"></a>--}}
                                    </h4>
                                    <p><a href="mailto:{{$employee->email}}" class="__cf_email__"
                                          data-cfemail="1f7e7373767a786d7e6b7a6d5f736a7170317c7072">[{{ $employee->email }}] </a></p>
                                    <span class="text-muted">{{ __("hr.phone") }}<br> {{$employee->phone}}</span>
                                    <div
                                        class="d-flex flex-row flex-wrap align-items-center justify-content-center justify-content-md-start">
                                        <div class="card py-2 px-3 me-2 mt-2">
                                            <small class="text-muted">{{ __("hr.salary") }}</small>
                                            <div class="fs-5">{{$employee->salary}}</div>
                                        </div>
                                        <div class="card py-2 px-3 me-2 mt-2">
                                            <small class="text-muted">{{ __("hr.allowances") }}</small>
                                            <div
                                                class="fs-5">{{$employee->total_allowances?? 0 }}</div>
                                        </div>
                                        <div class="card py-2 px-3 me-2 mt-2">
                                            <small class="text-muted">{{ __("hr.sanctions") }}</small>
                                            <div
                                                class="fs-5">{{$employee->sanctions()->sum('value')}}</div>
                                        </div>
                                        <div class="card py-2 px-3 me-2 mt-2">
                                            <small class="text-muted">{{ __("hr.nationality") }}</small>
                                            <div
                                                class="fs-5">{{ $employee->nationality->name }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class=" col-lg-4 col-md-4">
                                    <div class="card mb-3">

                                        <ul class="list-group list-group-flush list-group-custom">
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.advances') }}
                                                    <span>{{ $advances }}</span></a></li>
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.vacations') }}
                                                    <span>{{ $vacations }}</span></a></li>
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.permissions') }}
                                                    <span>{{ $permissions }}</span></a></li>
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.daily-work-reports') }}
                                                    <span>{{ $daily_work_reports }}</span></a></li>
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.tasks') }}
                                                    <span>{{ $tasks }}</span></a></li>
                                            <li class="list-group-item px-4"><a class="d-flex justify-content-between"
                                                                                href="#">{{ __('hr.rewards') }}
                                                    <span>{{ $rewards->sum('net_value') ?? 0 }}</span></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs tab-card border-bottom-0 pt-2 fs-6 justify-content-center justify-content-md-start"
                            role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#profile_post"
                                                    role="tab"><span> {{ __("hr.basic information") }}</span></a></li>

                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#job_info" role="tab"><i
                                            class="fa fa-info"></i>
                                    <span class="d-none d-sm-inline-block ms-2">{{ __('hr.job_info') }}</span></a></li>

                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#Employee_papers" role="tab"><i
                                        class="fa fa-paperclip"></i>
                                <span class="d-none d-sm-inline-block ms-2">{{ __('hr.Employee papers') }}</span></a></li>

                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#advances" role="tab"><i
                                        class="fa fa-address-card-o"></i><span
                                        class="d-none d-sm-inline-block ms-2">{{ __('hr.advances') }}</span></a></li>

                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vacations"
                                                    role="tab"><i class="fa fa-address-card-o"></i><span
                                        class="d-none d-sm-inline-block ms-2">{{ __('hr.vacations') }}</span></a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#permissions"
                                                    role="tab"><i class="fa fa-list-alt"></i><span
                                        class="d-none d-sm-inline-block ms-2">{{ __('hr.permissions_sec') }}</span></a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#daily_work_reports"
                                                    role="tab" id="tab_profile_campaigns"><i
                                        class="fa fa-area-chart"></i><span
                                        class="d-none d-md-inline-block ms-2">{{ __('hr.daily-work-reports') }}</span></a>
                            </li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tasks" role="tab"><i
                                        class="fa fa-font"></i><span
                                        class="d-none d-md-inline-block ms-2">{{ __('hr.tasks') }}</span></a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rewards" role="tab">
                                        <i class="fa-solid fa-money-bill"></i>
                                        <span class="d-none d-md-inline-block ms-2">{{ __('hr.rewards') }}</span></a></li>
                        </ul>
                    </div>
                    <div class="tab-content mt-5">

                        <div class="tab-pane fade show active" id="profile_post" role="tabpanel">
                            <div class="row-title mb-2">
                            </div>
                            <div class="row ">
                                <div class=" col-lg-12 col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.employee_name") }}:</span>{{ $employee->name }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.Employee Code") }}:</span>{{ $employee->code }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.the address") }}:</span>{{ $employee->address }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.date of birth") }}:</span>{{ $employee->birthday }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.ID Number") }}:</span>{{ $employee->national_num }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.department") }}:</span>{{ $employee->departments->title }}
                                                        </li>
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.phone") }}:</span>{{ $employee->phone }}

                                                        </li>

                                                    </ul>

                                                </div>
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.Email") }}:</span>{{ $employee->email }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.job") }}:</span>{{ $employee->jobs->title }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.shifts") }}:</span>{{ $employee->shift->shift->name }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w90 d-inline-block">{{ __("hr.Date of hiring") }}:</span>{{ $employee->hired_date }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w100 d-inline-block">{{ __("hr.Contract expiration date") }}:</span>{{ $employee->contract_end_date }}
                                                        </li>

                                                    </ul>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="tab-pane fade" id="job_info" role="tabpanel">
                            <div class="row ">
                                <div class=" col-lg-12 col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.department") }}:</span>{{ $employee->departments->title }}
                                                        </li>
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.job") }}:</span>{{ $employee->jobs->title }}
                                                        </li>
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.shifts") }}:</span>{{ $employee->shift->shift->name }}
                                                        </li>
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w90 d-inline-block">{{ __("hr.Date of hiring") }}:</span>{{ $employee->hired_date }}
                                                        </li>
                                                        <li class="py-2"><span
                                                                class="text-muted me-2 w100 d-inline-block">{{ __("hr.Contract expiration date") }}:</span>{{ $employee->contract_end_date }}
                                                        </li>
                                                    </ul>

                                                </div>
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w100 d-inline-block">{{ __("hr.Responsible Employee Report") }}:</span>
                                                            <ul>
                                                                @foreach ($employee->responsible as $responsible)
                                                                    <li>{{ $responsible->name }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </li>
                                                        <li class="py-2"><span
                                                            class="text-muted me-2 w100 d-inline-block">{{ __("hr.vacation_count") }}:</span>{{ $employee->vacations->count() }}
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="tab-pane fade" id="Employee_papers" role="tabpanel">
                            <div class="row ">
                                <div class=" col-lg-12 col-md-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <table class='table'>
                                                    <thead class="thead-inverse">
                                                        <tr>
                                                            <th>{{ __("hr.name") }}</th>
                                                            <th>{{ __("hr.file") }} </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($employee->files as $file)
                                                            <tr>
                                                                <td>{{$file->title}}</td>
                                                                <td><a href="{{ showFile($file->file) }}" download title="{{ __("hr.download") }}"><i class="fas fa-download"></i></a></td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="12" class="text-center">لا يوجد بيانات</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="advances" role="tabpanel">
                            <table id="dataTableObject_advances" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('finance.SL') }}</th>
                                    <th>{{ __("hr.payment method") }}</th>
                                    <th>{{ __("hr.value") }} </th>
                                    <th> {{ __("hr.date") }}</th>
                                    <th> {{ __("hr.file") }}</th>
                                    <th class="notPrintable"> {{ __("hr.Installments and payments") }}</th>
                                    <th class="notPrintable">{{__("finance.actions")}}</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>

                        <div class="tab-pane fade" id="vacations" role="tabpanel">
                                <table id="dataTableObject_vactions" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>{{ __('hr.SL') }}</th>
                                        <th>{{ __("hr.Employee") }}  </th>
                                        <th>{{ __("hr.the kind of holiday") }}</th>
                                        <th>{{ __("hr.Balance") }} </th>
                                        <th>{{ __("hr.From the date of") }}</th>
                                        <th>{{ __("hr.To date") }}</th>
                                        <th>{{ __("hr.Date difference") }}</th>
                                        <th>{{ __("hr.comments") }} </th>
                                        <th>{{ __("hr.status") }}</th>
                                        <th>{{__("hr.actions")}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                        </div>

                        <div class="tab-pane fade" id="permissions" role="tabpanel">
                            <table id="dataTableObject_permissions" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('hr.SL') }}</th>
                                    <th>{{ __("hr.Employee") }} </th>
                                    <th>{{ __("hr.Permission") }} </th>
                                    <th>{{ __("hr.date") }} </th>
                                    <th>{{ __("hr.Permission time") }} </th>
                                    <th>{{ __("hr.reason") }} </th>
                                    <th>{{ __("hr.Status") }}</th>
                                    <th>{{__("hr.actions")}}</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="daily_work_reports" role="tabpanel">
                            <table id="dataTableObject_daily_work_reports" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('hr.SL') }}</th>
                                    <th>{{ __("hr.title") }}</th>
                                    <th>{{ __("hr.date") }}</th>
                                    <th>{{ __("hr.file") }}</th>
                                    <th>{{ __('hr.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="tasks" role="tabpanel">
                            <table id="dataTableObject_tasks" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('crm.SL') }}</th>
                                    <th>{{ __("crm.title") }} </th>
                                    <th>{{ __("crm.end_date") }} </th>
                                    <th>{{ __("crm.attach") }} </th>
                                    <th class="notPrintable">{{ __("hr.replies_actions") }} </th>
                                    <th class="notPrintable">{{ __('crm.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="rewards" role="tabpanel">
                            <table id="dataTableObject_rewards" class="table display dataTable table-hover dataTableObject" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('hr.SL') }}</th>
                                        <th>{{ __("hr.rewards") }}</th>
                                        <th>{{ __("hr.salary") }}</th>
                                        <th>{{ __("hr.net_value") }}</th>
                                        <th>{{ __("hr.exchange_timing") }}</th>
                                        <th>{{ __("hr.date") }}</th>
                                        <th>{{ __('hr.actions') }}</th>
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
    @include('hr.employees._js_employee_profile', ['employee' => $employee])
@endsection
