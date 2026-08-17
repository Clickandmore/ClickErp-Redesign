@extends('click.layout.indexs.index_empty')
@php
    @endphp
@section('page-title')
    التنبيهات
@endsection
@section('content')
    <style>
        /*@media print {*/
        /*  */
        /*    body * {*/
        /*        visibility: hidden;*/
        /*    }*/
        /*    #printableArea, #printableArea * {*/
        /*        visibility: visible;*/
        /*    }*/
        /*    #printableArea {*/
        /*        position: absolute;*/
        /*        left: 0;*/
        /*        top: 0;*/
        /*        width: 100%;*/
        /*    }*/
        /*}*/
    </style>
    <div class="container-fluid py-3">
        <div class="row">
            <div class="row  row-deck">
                <div class="col-3">
                    <div class="card p-3 border-0 alert-danger alert mb-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar rounded-circle no-thumbnail bg-light"><i
                                    class="fa-solid fa-network-wired fa-lg"></i>
                            </div>
                            <div class="flex-fill ms-3 text-truncate">
                                <div class="small">@lang('hr.departments')</div>
                                <span class="h5 mb-0">{{ $departments_counts }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-3">
                    <div class="card p-3 border-0 alert-info alert mb-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar rounded-circle no-thumbnail bg-light"><i class="fa fa-users fa-lg"></i>
                            </div>
                            <div class="flex-fill ms-3 text-truncate">
                                <div class="small">@lang('hr.employees_counts')</div>
                                <span class="h5 mb-0">{{ $employees_counts }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-3 col">
                    <div class="card p-3 border-0 alert-success alert mb-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar rounded-circle no-thumbnail bg-light"><i class="fa fa-dollar fa-lg"></i>
                            </div>
                            <div class="flex-fill ms-3 text-truncate">
                                <div class="small">@lang('hr.total_salaries')</div>
                                <span class="h5 mb-0">{{ $total_salaries }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card p-3">
                        <div class="d-flex align-items-center">
                            <div class="avatar rounded-circle no-thumbnail bg-light"><i class="fa fa-eye fa-lg"></i>
                            </div>
                            <div class="flex-fill ms-3 text-truncate">
                                <div class="small">@lang('hr.Attendance')</div>
                                <span class="h5 mb-0">{{ $attendances_today }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{--            <div class="col-lg-3 col-md-6 col-sm-6">--}}
            {{--                <div class="card overflow-hidden">--}}
            {{--                    <div class="card-body">--}}
            {{--                        <i class="fa fa-group fa-lg position-absolute top-0 end-0 p-3"></i>--}}
            {{--                        <div class="mb-2 text-uppercase">{{ __('hr.vacations') }}</div>--}}
            {{--                        <div><span class="h4">{{ $vacationData->current_month_days ?? 0 }}</span> <span--}}
            {{--                                class="small text-muted"><i--}}
            {{--                                    class="fa fa-level-{{ $vacationData->previous_month_days > $vacationData->current_month_days ? 'down text-danger' : 'up text-success' }}"></i>--}}
            {{--                                {{ (int) $vacationData->percentage ?? 0 }}%</span>--}}
            {{--                        </div>--}}
            {{--                        --}}{{--                    <small class="text-muted">Analytics for last Year</small> --}}
            {{--                    </div>--}}
            {{--                    <div class="progress" style="height: 2px;">--}}
            {{--                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 45%"--}}
            {{--                            aria-valuenow="{{ (int) $vacationData->percentage ?? 0 }}" aria-valuemin="0" aria-valuemax="100">--}}
            {{--                        </div>--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--            </div>--}}
            <div class="row row-cols-xl-4 row-cols-lg-3 row-cols-md-2 row-cols-sm-2 row-cols-1 g-2 mb-3">
                <div class="col">
                    <div class="card lift">
                        <div class="card-body">
                            <span class="text-uppercase">{{ __('hr.employeePermissions') }}</span>
                            <h4 class="mb-0 mt-2">{{ array_sum($permissionCounts) }}
                            </h4>
                            <span
                                class="small text-muted"><i
                                    class="fa fa-level-{{ $permissionsData->previous_month_days > $permissionsData->current_month_days ? 'down text-danger' : 'up text-success' }}"></i>
                                {{ (int) $permissionsData->percentage ?? 0 }}%
                                </span>
                        </div>
                        <div id="apex-wc-1"></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card lift">
                        <div class="card-body">
                            <span class="text-uppercase">{{ __('hr.sanctions') }}</span>
                            <h4 class="mb-0 mt-2">{{ array_sum($sanctionCounts) }}
                            </h4>
                            <span
                                class="small text-muted"><i
                                    class="fa fa-level-{{ $permissionsData->previous_month_days > $permissionsData->current_month_days ? 'down text-danger' : 'up text-success' }}"></i>
                                {{ (int) $permissionsData->percentage ?? 0 }}%
                                </span>
                        </div>
                        <div id="apex-wc-2"></div>
                    </div>
                </div>

                <div class="col">
                    <div class="card lift">
                        <div class="card-body">
                            <span class="text-uppercase">{{ __('hr.Delays') }}</span>
                            <h4 class="mb-0 mt-2">{{$delaysThisMonth5}} </h4>
                            <small class="text-muted">{{$delaysToday5}} @lang('hr.delays_today')</small>
                        </div>
                        <div id="apex-wc-5"></div>
                    </div>
                </div>
                <div class="col">
                    <div class="card lift">
                        <div class="card-body">
                            <span class="text-uppercase">{{ __('hr.absence') }}</span>
                            <h4 class="mb-0 mt-2">{{$delaysThisMonth3}} </h4>
                            <small class="text-muted">{{$delaysToday3}} @lang('hr.absence_today')</small>
                        </div>
                        <div id="apex-wc-3"></div>
                    </div>
                </div>

            </div>

        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row ">

                    <div class="col">
                        <a href="{{route('employees.index')}}"
                           class="card text-center py-0 py-lg-2 bg-primary text-light">
                            <div class="card-body">
                                <i class="fa fa-users fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.employees') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{route('vacations.index')}}"
                           class="card text-center py-0 py-lg-2 chart-color1 text-light">
                            <div class="card-body">
                                <i class="fa fa-calendar fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.vacations') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{route('allowances.index')}}"
                           class="card text-center py-0 py-lg-2 chart-color2 text-light">
                            <div class="card-body">
                                <i class="fa fa-dollar fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.allowances') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{route('sanctions.index')}}"
                           class="card text-center py-0 py-lg-2 chart-color3 text-light">
                            <div class="card-body">
                                <i class="fa fa-ban fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.sanctions') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{route('departments.index')}}"
                           class="card text-center py-0 py-lg-2 chart-color4 text-light">
                            <div class="card-body">
                                <i class="fa fa-building fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.departments') }}</div>
                            </div>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{route('daily-work-reports.index')}}"
                           class="card text-center py-0 py-lg-2 bg-success text-light">
                            <div class="card-body">
                                <i class="fa fa-clipboard fa-2x"></i>
                                <div class="fs-6 mt-3">{{ __('hr.report') }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div id="apex-Attendance"></div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title m-0">{{__('hr.department_salaries')}}</h6>
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
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-row align-items-center">

                            <div class="ms-lg-5 ms-md-4 ms-lg-5 ms-3">
                                <h6 class="mb-0 fw-bold">{{$totalSalaries}}</h6>
                                <small class="text-muted">{{__('hr.Last Month')}}</small>
                            </div>

                        </div>
                        <div id="apex-SalaryStatistics"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title m-0">{{__('hr.departments')}}</h6>
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
                    </div>
                    <div class="card-body">
                        <div id="apex-DeviceusebyStudent"></div>
                    </div>
                </div>
            </div>
        </div>

        {{--    @if ($employees_contract_end->count() > 0)--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-12">--}}
        {{--                    <div class="card cardNew">--}}
        {{--                        <div class="card-header">--}}
        {{--                            --}}{{--        <h3 class="card-title">@lang("fleet.monthly_chart") {{date("F")}}</h3> --}}
        {{--                        </div>--}}
        {{--                        <div class="card-body">--}}
        {{--                            <div class="row">--}}
        {{--                                <div class="col-12">--}}
        {{--                                    <div class="card card-success">--}}
        {{--                                        <div class="card-header">--}}
        {{--                                            <h5> @lang('hr.employees_contract_end_this_month') </h5>--}}
        {{--                                        </div>--}}
        {{--                                        <div class="card-body">--}}
        {{--                                            @if ($employees_contract_end->count() > 0)--}}
        {{--                                                <table class="table table-responsive table-bordered">--}}
        {{--                                                    <thead class="">--}}
        {{--                                                        <tr>--}}
        {{--                                                            <th>@lang('hr.name')</th>--}}
        {{--                                                            <th>@lang('hr.department')</th>--}}
        {{--                                                            <th>@lang('hr.salary')</th>--}}
        {{--                                                            <th>@lang('hr.contract_end_date')</th>--}}
        {{--                                                        </tr>--}}
        {{--                                                    </thead>--}}
        {{--                                                    <tbody>--}}
        {{--                                                        @foreach ($employees_contract_end as $employee_contract_end)--}}
        {{--                                                            <tr>--}}
        {{--                                                                <td>{{ $employee_contract_end->name }}</td>--}}
        {{--                                                                <td>{{ $employee_contract_end->departments->title ?? '' }}--}}
        {{--                                                                </td>--}}
        {{--                                                                <td>{{ @$employee_contract_end->salary }}</td>--}}
        {{--                                                                <td>{{ $employee_contract_end->contract_end_date }}</td>--}}
        {{--                                                            </tr>--}}
        {{--                                                        @endforeach--}}
        {{--                                                    </tbody>--}}
        {{--                                                </table>--}}
        {{--                                            @endif--}}
        {{--                                        </div>--}}
        {{--                                    </div>--}}
        {{--                                </div>--}}
        {{--                            </div>--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        @endif--}}
        {{--        @if ($employee_vacations_back->count() > 0)--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-12">--}}
        {{--                    <div class="card cardNew">--}}
        {{--                        <div class="card-header">--}}
        {{--                            --}}{{--        <h3 class="card-title">@lang("fleet.monthly_chart") {{date("F")}}</h3> --}}
        {{--                        </div>--}}
        {{--                        <div class="card-body">--}}
        {{--                            <div class="row">--}}
        {{--                                <div class="col-md-12">--}}
        {{--                                    <div class="card card-success">--}}
        {{--                                        <div class="card-header">--}}
        {{--                                            <h5> @lang('hr.employee_vacations_back') </h5>--}}
        {{--                                        </div>--}}
        {{--                                        <div class="card-body">--}}
        {{--                                            @if ($employee_vacations_back->count() > 0)--}}
        {{--                                                <table class="table table-responsive table-bordered ">--}}
        {{--                                                    <thead class="thead-inverse">--}}
        {{--                                                        <tr>--}}
        {{--                                                            <th>@lang('hr.name')</th>--}}
        {{--                                                            <th>@lang('hr.department')</th>--}}
        {{--                                                            <th>@lang('hr.Total days')</th>--}}
        {{--                                                            <th>@lang('hr.from_date')</th>--}}
        {{--                                                            <th>@lang('hr.to_date')</th>--}}
        {{--                                                            <th>@lang('hr.vacation_type')</th>--}}
        {{--                                                        </tr>--}}
        {{--                                                    </thead>--}}
        {{--                                                    <tbody>--}}
        {{--                                                        @foreach ($employee_vacations_back as $employee_vacation)--}}
        {{--                                                            <tr>--}}
        {{--                                                                <td>{{ $employee_vacation->employee->name }}</td>--}}
        {{--                                                                <td>{{ @$employee_vacation->employee->departments->title }}--}}
        {{--                                                                </td>--}}
        {{--                                                                <td>{{ $employee_vacation->days }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->from_date }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->to_date }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->vacation->title }}</td>--}}
        {{--                                                            </tr>--}}
        {{--                                                        @endforeach--}}
        {{--                                                    </tbody>--}}
        {{--                                                </table>--}}
        {{--                                            @endif--}}
        {{--                                        </div>--}}
        {{--                                    </div>--}}
        {{--                                </div>--}}
        {{--                            </div>--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        @endif--}}

        {{--        @if ($employees_vacations_today->count() > 0)--}}
        {{--            <div class="row">--}}
        {{--                <div class="col-md-12">--}}
        {{--                    <div class="card cardNew">--}}
        {{--                        <div class="card-header">--}}
        {{--                            --}}{{--        <h3 class="card-title">@lang("fleet.monthly_chart") {{date("F")}}</h3> --}}
        {{--                        </div>--}}
        {{--                        <div class="card-body">--}}
        {{--                            <div class="row">--}}
        {{--                                <div class="col-md-12">--}}
        {{--                                    <div class="card card-success">--}}
        {{--                                        <div class="card-header">--}}
        {{--                                            <h5> @lang('hr.employees_vacations_today') </h5>--}}
        {{--                                        </div>--}}
        {{--                                        <div class="card-body">--}}
        {{--                                            @if ($employees_vacations_today->count() > 0)--}}
        {{--                                                <table class="table table-responsive table-bordered">--}}
        {{--                                                    <thead class="thead-inverse">--}}
        {{--                                                        <tr>--}}
        {{--                                                            <th>@lang('hr.name')</th>--}}
        {{--                                                            <th>@lang('hr.department')</th>--}}
        {{--                                                            <th>@lang('hr.Total days')</th>--}}
        {{--                                                            <th>@lang('hr.from_date')</th>--}}
        {{--                                                            <th>@lang('hr.to_date')</th>--}}
        {{--                                                            <th>@lang('hr.vacation_type')</th>--}}
        {{--                                                        </tr>--}}
        {{--                                                    </thead>--}}
        {{--                                                    <tbody>--}}
        {{--                                                        @foreach ($employees_vacations_today as $employee_vacation)--}}
        {{--                                                            <tr>--}}
        {{--                                                                <td>{{ $employee_vacation->employee->name }}</td>--}}
        {{--                                                                <td>{{ @$employee_vacation->employee->departments->title }}--}}
        {{--                                                                </td>--}}
        {{--                                                                <td>{{ $employee_vacation->days }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->from_date }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->to_date }}</td>--}}
        {{--                                                                <td>{{ $employee_vacation->vacation->title }}</td>--}}
        {{--                                                            </tr>--}}
        {{--                                                        @endforeach--}}
        {{--                                                    </tbody>--}}
        {{--                                                </table>--}}
        {{--                                            @endif--}}
        {{--                                        </div>--}}
        {{--                                    </div>--}}
        {{--                                </div>--}}
        {{--                            </div>--}}
        {{--                        </div>--}}
        {{--                    </div>--}}
        {{--                </div>--}}
        {{--            </div>--}}
        {{--        @endif--}}

    </div>
@endsection
@section('js')
    <script src="{{ asset('admin/assets/js/Chart.bundle.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/canvasjs.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/theme.js') }}"></script>
    <script src="{{ asset('admin/assets/js/bundle/apexcharts.bundle.js') }}"></script>
    <script>
        var ctx = document.getElementById('canvas').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($array_department_labels),
                datasets: [{
                    label: '@lang('hr.department_salaries')',
                    data: @json($array_department_salaries),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

    </script>


    <script>
        window.chartColors = {
            red: 'rgb(255, 99, 132)',
            orange: 'rgb(255, 159, 64)',
            yellow: 'rgb(255, 205, 86)',
            green: 'rgb(75, 192, 192)',
            blue: 'rgb(54, 162, 235)',
            purple: 'rgb(153, 102, 255)',
            grey: 'rgb(201, 203, 207)',
            black: 'rgb(0,0,0)'
        };

        function random_color(i) {
            var color1, color2, color3;
            var col_arr = [];
            for (x = 0; x <= i; x++) {
                var c1 = [176, 255, 84, 220, 134, 66, 238];
                var c2 = [254, 61, 147, 114, 51, 26, 137];
                var c3 = [27, 111, 153, 93, 157, 216, 187, 44, 243];
                color1 = c1[Math.floor(Math.random() * c1.length)];
                color2 = c2[Math.floor(Math.random() * c2.length)];
                color3 = c3[Math.floor(Math.random() * c3.length)];
                col_arr.push("rgba(" + color1 + "," + color2 + "," + color3 + ",0.5)");
            }
            return col_arr;
        }

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
                        ranges: [
                            {
                                from: 0,
                                to: 0,
                                name: '{{__("hr.absence")}}',
                                color: '#FF5733' // Updated color for Absence
                            },
                            {
                                from: 1,
                                to: 1,
                                name: '{{__("hr.Attendance")}}',
                                color: '#28A745' // Updated color for Attendance
                            },
                            {
                                from: 2,
                                to: 2,
                                name: 'لم يتواجد',
                                color: '#BDBDBD' // Updated color for Not Present
                            },
                            {
                                from: 3,
                                to: 3,
                                name: '{{__("hr.Attendance and departure")}}',
                                color: '#0000FF' // Updated color for Attendance and Departure
                            },
                            {
                                from: 4,
                                to: 4,
                                name: '{{__("hr.Weekend")}}',
                                color: '#000000' // Updated color for Weekend
                            },
                            {
                                from: 5,
                                to: 5,
                                name: '{{__("hr.Late")}}',
                                color: '#FFD700' // Updated color for Late
                            },
                            {
                                from: 6,
                                to: 6,
                                name: '{{__("hr.Vacation")}}',
                                color: '#17A2B8' // Updated color for Vacation
                            },
                            {
                                from: 7,
                                to: 7,
                                name: '{{__("hr.Official Vacation")}}',
                                color: '#FF69B4' // Updated color for Official Vacation
                            }
                        ]
                    }

                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 2,
            },
            series: {!! json_encode($series) !!},
            xaxis: {
                categories: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30],
            }
        };
        var chart = new ApexCharts(document.querySelector("#apex-Attendance"), options);
        chart.render();
        var apexwc3 = {
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true
                },
            },
            series: [{
                data: @json($delayCounts3)
            }],
            stroke: {
                width: 1
            },
            fill: {
                type: "gradient",
                gradient: {
                    gradientToColors: ['var(--chart-color2)'],
                    shadeIntensity: 2,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                },
            },
            colors: ['var(--chart-color2)'],
        }
        new ApexCharts(document.querySelector("#apex-wc-3"), apexwc3).render();
        var apexwc5 = {
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true
                },
            },
            series: [{
                data: @json($delayCounts5)
            }],
            stroke: {
                width: 1
            },
            fill: {
                type: "gradient",
                gradient: {
                    gradientToColors: ['var(--chart-color2)'],
                    shadeIntensity: 2,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                },
            },
            colors: ['var(--chart-color2)'],
        }
        new ApexCharts(document.querySelector("#apex-wc-5"), apexwc5).render();
        var daysPInMonth = @json($daysPInMonth);
        var permissionCounts = @json($permissionCounts);
        var apexwc1 = {
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true
                },
            },
            series: [{
                data: @json($permissionCounts)
            }],
            stroke: {
                width: 1
            },
            fill: {
                type: "gradient",
                gradient: {
                    gradientToColors: ['var(--chart-color2)'],
                    shadeIntensity: 2,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                },
            },
            colors: ['var(--chart-color2)'],
        }
        new ApexCharts(document.querySelector("#apex-wc-1"), apexwc3).render();

        var apexwc2 = {
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true
                },
            },
            series: [{
                data: @json($sanctionCounts) // Dynamic data passed from the controller
            }],

            stroke: {
                width: 1
            },
            fill: {
                type: "gradient",
                gradient: {
                    gradientToColors: ['var(--chart-color1)'],
                    shadeIntensity: 2,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 100]
                },
            },
            colors: ['var(--chart-color1)'],
        }

        new ApexCharts(document.querySelector("#apex-wc-2"), apexwc2).render();

        // Salary Statistics
        const departmentLabels = @json($array_department_labels);
        const departmentSalaries = @json($array_department_salaries);
        const departmentEmployees = @json($array_department_employees);
        const totalEmployees = departmentEmployees.reduce((acc, val) => acc + val, 0);
        const totalSalaries = departmentSalaries.reduce((acc, val) => acc + val, 0);
        const departmentSeries = @json($department_series);
        const monthLabels = @json($department_labels);


        var salaryOptions = {
            series: departmentSeries,
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                stackType: '100%',
                toolbar: {
                    show: false,
                },
            },
            colors: ['var(--chart-color1)', 'var(--chart-color2)', 'var(--chart-color3)'],
            xaxis: {
                categories: monthLabels, // استخدام الأشهر فقط
            },
            fill: {
                opacity: 1
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
            },
        };

        // إنشاء الرسم البياني في الصفحة
        var salaryChart = new ApexCharts(document.querySelector("#apex-SalaryStatistics"), salaryOptions);
        salaryChart.render();
        // Income Analysis
        // إعداد الرسم البياني لدونات الأقسام
        var donutOptions = {
            chart: {
                type: 'donut',
                height: 270,
            },
            series: departmentEmployees,
            labels: departmentLabels,
            dataLabels: {
                enabled: false,
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: '{{__('finance.Total')}}', // النص الذي يظهر فوق الإجمالي
                                formatter: function (w) {
                                    // عرض الإجمالي في المركز
                                    return `${totalEmployees}`;
                                },
                                style: {
                                    fontSize: '16px',
                                    fontWeight: 'bold',
                                    color: '#000', // اللون الأسود للنص
                                },
                            }
                        }
                    }
                }
            },
            tooltip: {
                enabled: true,
                y: {
                    formatter: function (val) {
                        // تنسيق النص بحيث يظهر عند تمرير الماوس
                        return `{{__('hr.number_employees')}}: ${val}`;
                    },
                },
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
            },
            colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0'],
        };

        // إنشاء الرسم البياني الدونات
        var donutChart = new ApexCharts(document.querySelector("#apex-DeviceusebyStudent"), donutOptions);
        donutChart.render();
        {{-- var chartData = { --}}
        {{--    labels: @json($array_department_labels), --}}
        {{--    datasets: [{ --}}
        {{--        type: 'pie', --}}
        {{--        label: '', --}}
        {{--        backgroundColor: ['#21bc6c', '#ff5462'], --}}
        {{--        borderColor: window.chartColors.red, --}}
        {{--        borderWidth: 1, --}}
        {{--        data: @json($array_department_salaries) --}}
        {{--    }] --}}
        {{-- }; --}}
        {{--        var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July']; --}}
        {{--        var config = { --}}
        {{--            type: 'line', --}}
        {{--            data: { --}}
        {{--                labels: MONTHS, --}}
        {{--                datasets: [{ --}}
        {{--                    label: "@lang('fleet.expense')", --}}
        {{--                    backgroundColor: '#ff5462', --}}
        {{--                    borderColor: '#ff5462', --}}
        {{--                    data: [{{$yearly_expense}}], --}}
        {{--                    fill: false, --}}
        {{--                }, { --}}
        {{--                    label: "@lang('fleet.income')", --}}
        {{--                    fill: false, --}}
        {{--                    backgroundColor: '#21bc6c', --}}
        {{--                    borderColor: '#21bc6c', --}}
        {{--                    data: [{{$yearly_income}}], --}}
        {{--                }] --}}
        {{--            }, --}}
        {{--            options: { --}}
        {{--                responsive: true, --}}
        {{--                title:{ --}}
        {{--                    display:false, --}}
        {{--                }, --}}
        {{--                tooltips: { --}}
        {{--                    mode: 'index', --}}
        {{--                    intersect: false, --}}
        {{--                }, --}}
        {{--                hover: { --}}
        {{--                    mode: 'nearest', --}}
        {{--                    intersect: true --}}
        {{--                }, --}}
        {{--                scales: { --}}
        {{--                    xAxes: [{ --}}
        {{--                        display: true, --}}
        {{--                        scaleLabel: { --}}
        {{--                            display: true, --}}
        {{--                            labelString: "@lang('fleet.month')" --}}
        {{--                        } --}}
        {{--                    }], --}}
        {{--                    yAxes: [{ --}}
        {{--                        display: true, --}}
        {{--                        scaleLabel: { --}}
        {{--                            display: true, --}}
        {{--                            labelString: "@lang('fleet.amount')" --}}
        {{--                        } --}}
        {{--                    }] --}}
        {{--                } --}}
        {{--            } --}}
        {{--        }; --}}
        {{--        var datewise_config = { --}}
        {{--            type: 'line', --}}
        {{--            data: { --}}
        {{--                labels: [ --}}
        {{--                      @foreach ($dates as $k => $v) --}}
        {{--                        CanvasJS.formatDate( new Date("{{date('Y-m-d H:i:s',strtotime($k))}}"), "DD/MM/YY"), --}}
        {{--                      @endforeach], --}}
        {{--                datasets: [{ --}}
        {{--                    label: "@lang('fleet.expense')", --}}
        {{--                    backgroundColor: '#ff5462', --}}
        {{--                    borderColor: '#ff5462', --}}
        {{--                    data: [{{$expenses1}}], --}}
        {{--                    fill: false, --}}
        {{--                }, { --}}
        {{--                    label: "@lang('fleet.income')", --}}
        {{--                    fill: false, --}}
        {{--                    backgroundColor: '#21bc6c', --}}
        {{--                    borderColor: '#21bc6c', --}}
        {{--                    data: [{{$incomes}}], --}}
        {{--                }] --}}
        {{--            }, --}}
        {{--            options: { --}}
        {{--                responsive: true, --}}
        {{--                title:{ --}}
        {{--                    display:false, --}}
        {{--                }, --}}
        {{--                tooltips: { --}}
        {{--                    mode: 'index', --}}
        {{--                    intersect: false, --}}
        {{--                }, --}}
        {{--                hover: { --}}
        {{--                    mode: 'nearest', --}}
        {{--                    intersect: true --}}
        {{--                }, --}}
        {{--                scales: { --}}
        {{--                    xAxes: [{ --}}
        {{--                        display: true, --}}
        {{--                        scaleLabel: { --}}
        {{--                            display: true, --}}
        {{--                            labelString: "@lang('fleet.date')" --}}
        {{--                        } --}}
        {{--                    }], --}}
        {{--                    yAxes: [{ --}}
        {{--                        display: true, --}}
        {{--                        scaleLabel: { --}}
        {{--                            display: true, --}}
        {{--                            labelString: "@lang('fleet.amount')" --}}
        {{--                        } --}}
        {{--                    }] --}}
        {{--                } --}}
        {{--            } --}}
        {{--        }; --}}
        //     window.onload = function () {
        //     // console.log(chartData);
        //     var ctx = document.getElementById("yearly").getContext("2d");
        //     window.myLine = new Chart(ctx, config);
        //     var ctx = document.getElementById("canvas").getContext("2d");
        //     var datewise = document.getElementById("datewise").getContext("2d");
        //     window.myLine = new Chart(datewise, datewise_config);
        //     window.myMixedChart = new Chart(ctx, {
        //         type: 'bar',
        //         data: chartData,
        //         options: {
        //             legend: {display: false},
        //             responsive: true,
        //
        //             tooltips: {
        //                 mode: 'index',
        //                 intersect: false
        //             }
        //         }
        //     });
        // }
        {{--            var ctx = document.getElementById("canvas2").getContext("2d"); --}}
        {{--            window.myMixedChart = new Chart(ctx, { --}}
        {{--                type: 'pie', --}}
        {{--                data: chartData3, --}}
        {{--                options: { --}}
        {{--                    responsive: true, --}}
        {{--                    title: { --}}
        {{--                        display: false, --}}
        {{--                        text: "@lang('fleet.chart')" --}}
        {{--                    }, --}}
        {{--                    tooltips: { --}}
        {{--                        mode: 'index', --}}
        {{--                        intersect: true --}}
        {{--                    } --}}
        {{--                } --}}
        {{--            }); --}}
        {{--        }; --}}
        {{--            var chartData3 = { --}}
        {{--            labels: [@foreach ($expenses as $exp) "{{$vehicle_name[$exp->vehicle_id]}}", @endforeach], --}}
        {{--            datasets: [{ --}}
        {{--                type: 'pie', --}}
        {{--                label: '', --}}
        {{--                backgroundColor: random_color({{count($expenses)}}), --}}
        {{--                borderColor: window.chartColors.black, --}}
        {{--                borderWidth: 1, --}}
        {{--                data: [@foreach ($expenses as $exp) {{$exp->expense}}, @endforeach] --}}
        {{--            }] --}}
        {{--        }; --}}
    </script>
@endsection
