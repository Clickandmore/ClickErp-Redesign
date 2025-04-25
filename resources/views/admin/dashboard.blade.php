@extends('admin.layout.indexs.index')
@section("style")
    <link rel="stylesheet" href="{{asset(" admin")}}/assets/cssbundle/dataTables.min.css">
@endsection
@section('page-title')
    الرئيسية
@endsection
@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> الرئيسية</li>
@endsection
@section('content')

@if (@AdminGuardHasPerm(\App\PermKeys::DASHBOARD_VIEW))

    <div class="row  g-3 row-deck">
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <svg class="position-absolute top-0 end-0 mt-4 me-3" xmlns="http://www.w3.org/2000/svg"
                         width="26" fill="currentColor" viewBox="0 0 16 16">
                        <path class="fill-muted"
                              d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5zM.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5z"/>
                        <path class="fill-primary"
                              d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    </svg>
                    <div class="mb-2 text-uppercase">
                        {{ helperTrans('ingaz.clients') }}
                        <span class="h4">{{ optional($data['clients_count'])['allCount'] ?? 0 }} </span>
                        @isset($data['clients_count']['thisMonthCount'])
                            <span class="small text-muted"><i class="fa fa-level-up text-success"></i>
                            {{ optional($data['clients_count'])['thisMonthCount'] ?? 0 }}
                        </span>
                        @endisset
                    </div>
                    <div>
                    </div>
                    <small class="text-success">{{helperTrans('ingaz.new this month')}}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <svg class="position-absolute top-0 end-0 mt-4 me-3" xmlns="http://www.w3.org/2000/svg"
                         width="26" fill="currentColor" viewBox="0 0 16 16">
                        <path class="fill-muted"
                              d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v1.384l7.614 2.03a1.5 1.5 0 0 0 .772 0L16 5.884V4.5A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5z"/>
                        <path class="fill-primary"
                              d="M0 12.5A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5V6.85L8.129 8.947a.5.5 0 0 1-.258 0L0 6.85v5.65z"/>
                    </svg>
                    <a href="{{route('contact.index')}}">
                        <div class="mb-2 text-uppercase">{{ helperTrans('ingaz.contacts us count') }}</div>
                        <div>
                            <span class="h4">{{ $data['contact_count'] ?? 0 }} </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <svg class="position-absolute top-0 end-0 mt-4 me-3" xmlns="http://www.w3.org/2000/svg"
                         width="26" fill="currentColor" viewBox="0 0 16 16">
                        <path class="fill-primary" fill-rule="evenodd"
                              d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/>
                        <path class="fill-primary"
                              d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1h-.003zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195l.054.012z"/>
                        <path class="fill-muted"
                              d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083c.058-.344.145-.678.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1H1z"/>
                        <path class="fill-muted"
                              d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 5.982 5.982 0 0 1 3.13-1.567z"/>
                    </svg>
                    <a href="{{route('orders.index',['status'=>'current'])}}">
                        <div class="mb-2 text-uppercase">{{ helperTrans('ingaz.current orders') }}</div>
                        <div>
                            <span class="h4">{{ $data['current_orders_count'] ?? 0 }} </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <i class="fa fa-female fs-2 text-primary"></i>
                    <a href="{{route('maids.index')}}">
                        <div class="mb-2 text-uppercase">{{ helperTrans('ingaz.cv count') }}</div>
                        <div>
                            <span class="h4">{{ $data['cv_count'] ?? 0 }} </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <i class="fa fa-headphones fs-2 text-primary"></i>
                    <a href="{{route('admins.index')}}">
                        <div class="mb-2 text-uppercase">{{ helperTrans('ingaz.employees count') }}</div>
                        <div>
                            <span class="h4">{{ $data['call_center_count'] ?? 0 }} </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <i class="fa fa-thumbs-up fs-2 text-primary"></i>
                    <a href="{{route('orders.index')}}">
                        <div class="mb-2 text-uppercase">{{ helperTrans('ingaz.completed cv count') }}</div>
                        <div>
                            <span class="h4">{{ $data['ordered_cv'] ?? 0 }} </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>



        <div class="col-xxl-12 col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title m-0">{{helperTrans('ingaz.orders statistics')}}</h6>

                    <div class="dropdown morphing scale-left">
                        <a href="#" class="card-fullscreen" data-bs-toggle="tooltip" title="Card Full-Screen"><i
                                    class="icon-size-fullscreen"></i></a>
                        <a href="#" class="more-icon dropdown-toggle" data-bs-toggle="dropdown"
                           aria-expanded="false"><i class="fa fa-ellipsis-h"></i></a>
                        <ul class="dropdown-menu shadow border-0 p-2">
                            <li><a class="dropdown-item"
                                   href="{{ route('orders.index', ['status' => 'current']) }}">
                                    {{helperTrans('ingaz.current orders')}}</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index', ['status' => 'ended']) }}">
                                    {{ helperTrans('ingaz.ended orders') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="apex-AudienceOverview"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body d-flex flex-column">
                    <div>
                        <h6 class="mb-0">{{ helperTrans('ingaz.top 3 customer services') }} 🏆</h6>
                    </div>
                    <div class="pt-2">
                        @isset($data['top_call_center'])
                            @foreach($data['top_call_center'] as $row)
                                @php
                                    $imagePath = ($row->sex_type == 0) ? getFile('images/male.png') : getFile('images/female.avif');
                                @endphp
                                <img class="avatar rounded-circle m-1 lift" src="{{ $imagePath }}"
                                     data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $row->name }}" alt="">
                                <span class="fw-bold">{{ $row->name }}</span>
                                <span class="h6 mb-0 text-success ">({{$row->order_count}} {{helperTrans('ingaz.order')}})</span>
                                <br>
                            @endforeach
                        @endisset
                    </div>
                </div>
            </div>

        </div>

        <div class="col-6">
            <div class="card-body">
                <div class="card-header">
                    <h6 class="card-title m-0">{{helperTrans('ingaz.orders types')}}</h6>
                </div>
                <div class="card-body">
                    <div id="apex-optionsForType"></div>
                </div>
            </div>
        </div>
    </div>

@endif
@endsection
@section('js')
    <script src="{{asset("admin")}}/assets/js/bundle/apexcharts.bundle.js"></script>
    <script>
        // Order Chart
        var options = {
            series: [
                    @foreach($data['types'] as $type)
                {
                    name: '{{$type["name"]}}',
                    data: [
                        @for($month = 1; $month <= 12; $month++)
                                {{$type['data'][$month] ?? 0}},
                        @endfor
                    ]
                },
                @endforeach
            ],
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
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],
            xaxis: {
                categories: [
                    '{{helperTrans('ingaz.Jan')}}',
                    '{{helperTrans('ingaz.Feb')}}',
                    '{{helperTrans('ingaz.March')}}',
                    '{{helperTrans('ingaz.Apr')}}',
                    '{{helperTrans('ingaz.May')}}',
                    '{{helperTrans('ingaz.Jun')}}',
                    '{{helperTrans('ingaz.July')}}',
                    '{{helperTrans('ingaz.Aug')}}',
                    '{{helperTrans('ingaz.Sept')}}',
                    '{{helperTrans('ingaz.Oct')}}',
                    '{{helperTrans('ingaz.Nov')}}',
                    '{{helperTrans('ingaz.Dec')}}'
                ],
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
        var chart = new ApexCharts(document.querySelector("#apex-AudienceOverview"), options);
        chart.render();
        // Sales by Category
        var options = {
            chart: {
                height: 280,
                type: 'donut',
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                showAlways: true,
                                show: true
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                show: true,
            },
            colors: ['var(--chart-color1)', 'var(--chart-color2)', 'var(--chart-color3)'],
            series: [55, 35, 10],
        }


    </script>

    <script>
        var optionsForType = {
            chart: {
                height: 280,
                type: 'donut',
            },
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                showAlways: true,
                                show: true
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                show: true,
            },
            colors: ['var(--chart-color1)', 'var(--chart-color2)', 'var(--chart-color3)'],
            labels: [
                @foreach($data['order_types'] as $order_type)
                    '{{$order_type["title"]}} ({{$order_type["count"]}})',
                @endforeach
            ],
            series: [
                @foreach($data['order_types'] as $order_type)
                        {{$order_type['count']}},
                @endforeach
            ],
        }
        var chart = new ApexCharts(document.querySelector("#apex-optionsForType"), optionsForType);
        chart.render();
    </script>

@endsection
