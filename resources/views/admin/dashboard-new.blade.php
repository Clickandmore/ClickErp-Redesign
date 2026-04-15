@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/css/dashboard-new.css') }}?v=12">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
@endsection

@section('page-title')
    لوحة التحكم
@endsection

@section('content')
    <div class="dashboard-hero surface-card">
        <div class="hero-main">
            <p class="hero-eyebrow">مؤشرات فورية</p>
            <h1>مرحباً {{ auth()->guard('admin')->user()->name ?? '' }} 👋</h1>
            <p>تابع أداء العقود والطلبات والإيرادات لحظة بلحظة مع إمكانية التحكم في الأقسام التي تهمك.</p>
            <div class="hero-meta">
                <span class="live-pill">محدث الآن</span>
                <span>آخر تحديث {{ now()->translatedFormat('d F Y - H:i') }}</span>
            </div>
            <div class="hero-quick-actions">
                <a href="{{ route('RecruitmentContract.create') }}"><i class="fas fa-plus-circle ms-1"></i> عقد جديد</a>
                <a href="{{ route('orders.index') }}"><i class="fas fa-shopping-basket ms-1"></i> الطلبات المباشرة</a>
                <a href="{{ route('admins.index') }}"><i class="fas fa-users-cog ms-1"></i> إدارة الفرق</a>
            </div>
        </div>
        <div class="hero-controls">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>اختر الفترة</small>
                    <h5 class="mb-0">تحديث القيم فوراً</h5>
                </div>
                <i class="fas fa-sliders-h"></i>
            </div>
            <div class="range-switch" role="group" aria-label="تصفية الإحصائيات حسب الفترة">
                <button class="range-btn active" data-range="today">اليوم</button>
                <button class="range-btn" data-range="week">هذا الأسبوع</button>
                <button class="range-btn" data-range="month">هذا الشهر</button>
                <button class="range-btn" data-range="total">الإجمالي</button>
            </div>
            <div class="hero-micro-grid">
                <div>
                    <small data-range-label data-today="طلبات اليوم" data-week="طلبات هذا الأسبوع"
                        data-month="طلبات هذا الشهر" data-total="إجمالي الطلبات">طلبات اليوم</small>
                    <strong data-range-value data-range-format="number" data-today="{{ $ordersStats['today'] ?? 0 }}"
                        data-week="{{ $ordersStats['thisWeek'] ?? 0 }}" data-month="{{ $ordersStats['thisMonth'] ?? 0 }}"
                        data-total="{{ $ordersStats['total'] ?? 0 }}">{{ number_format($ordersStats['today'] ?? 0) }}</strong>
                </div>
                <div>
                    <small data-range-label data-today="عقود اليوم" data-week="عقود هذا الأسبوع"
                        data-month="عقود هذا الشهر" data-total="إجمالي العقود">عقود اليوم</small>
                    <strong data-range-value data-range-format="number" data-today="{{ $contractsStats['today'] ?? 0 }}"
                        data-week="{{ $contractsStats['thisWeek'] ?? 0 }}"
                        data-month="{{ $contractsStats['thisMonth'] ?? 0 }}"
                        data-total="{{ $contractsStats['total'] ?? 0 }}">{{ number_format($contractsStats['today'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-row">
        <div class="stat-card is-1">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">طلبات الاستقدام</small>
                    <h3 class="mb-0">{{ number_format($ordersStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">جديدة: {{ number_format($ordersStats['new'] ?? 0) }} | تحت الإجراء:
                {{ number_format($ordersStats['under_process'] ?? 0) }} | تم التعاقد:
                {{ number_format($ordersStats['contracted'] ?? 0) }}</div>
            <div class="progress mt-2" style="height:6px;">
                @php $orderTotal = max($ordersStats['total'] ?? 1, 1); @endphp
                <div class="progress-bar bg-success"
                    style="width: {{ round((($ordersStats['contracted'] ?? 0) / $orderTotal) * 100) }}%"></div>
                <div class="progress-bar bg-warning"
                    style="width: {{ round((($ordersStats['under_process'] ?? 0) / $orderTotal) * 100) }}%"></div>
            </div>
        </div>

        <div class="stat-card is-2">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">عقود الاستقدام</small>
                    <h3 class="mb-0">{{ number_format($contractsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('RecruitmentContract.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">قبل الوصول: {{ number_format($contractsPipeline['pre_arrival'] ?? 0) }} |
                مرحلة الوصول: {{ number_format($contractsPipeline['arrival_stage'] ?? 0) }} | تحت الضمان:
                {{ number_format($contractsPipeline['under_warranty'] ?? 0) }}</div>
            <div class="progress mt-2" style="height:6px;">
                @php $ctTotal = max($contractsStats['total'] ?? 1, 1); @endphp
                <div class="progress-bar bg-success"
                    style="width: {{ round((($contractsStats['completed'] ?? 0) / $ctTotal) * 100) }}%"></div>
                <div class="progress-bar bg-info"
                    style="width: {{ round((($contractsPipeline['pre_arrival'] ?? 0) / $ctTotal) * 100) }}%"></div>
            </div>
        </div>

        <div class="stat-card is-3">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">السير الذاتية</small>
                    <h3 class="mb-0">{{ number_format($cvStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/maids') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">متاحة: {{ number_format($cvStats['available'] ?? 0) }} | محجوزة:
                {{ number_format($cvStats['booked'] ?? 0) }}</div>
            <div class="progress mt-2" style="height:6px;">
                @php $cvTotal = max($cvStats['total'] ?? 1, 1); @endphp
                <div class="progress-bar bg-success"
                    style="width: {{ round((($cvStats['available'] ?? 0) / $cvTotal) * 100) }}%"></div>
                <div class="progress-bar bg-warning"
                    style="width: {{ round((($cvStats['booked'] ?? 0) / $cvTotal) * 100) }}%"></div>
            </div>
        </div>

        <div class="stat-card is-4">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">العملاء</small>
                    <h3 class="mb-0">{{ number_format($clientsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/clients') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">لديهم طلبات: {{ number_format($clientsStats['with_orders'] ?? 0) }} | بدون
                طلبات: {{ number_format($clientsStats['no_orders'] ?? 0) }}</div>
            <div class="progress mt-2" style="height:6px;">
                @php $clTotal = max($clientsStats['total'] ?? 1, 1); @endphp
                <div class="progress-bar bg-success"
                    style="width: {{ round((($clientsStats['with_orders'] ?? 0) / $clTotal) * 100) }}%"></div>
                <div class="progress-bar bg-secondary"
                    style="width: {{ round((($clientsStats['no_orders'] ?? 0) / $clTotal) * 100) }}%"></div>
            </div>
        </div>
    </div>

    @php

        $statusCounts = $contractStatusBreakdown ?? [];
        $insights = $operationsInsights ?? [];

        $arrivalTodayCount = $insights['arrival_today'] ?? ($contractsPipeline['arrival_stage'] ?? 0);
        $delayedWithoutTickets = $insights['delayed_no_tickets'] ?? ($contractsPipeline['delayed_arrival'] ?? 0);
        $expiredLongTerm = $insights['expired_over_2y'] ?? ($contractsPipeline['expired_over_2y'] ?? 0);
        $warrantyActive = $insights['warranty_active'] ?? ($contractsStats['underWarranty'] ?? 0);
        $ticketsReady = $insights['tickets_ready'] ?? ($statusCounts[\App\Enums\ingaz\ContractStatusEnum::Tazker->value] ?? 0);
        $returnedContracts = $insights['returned'] ?? ($statusCounts[\App\Enums\ingaz\ContractStatusEnum::Back->value] ?? 0);
        $completedContractsCount = $insights['completed'] ?? ($contractsStats['completed'] ?? 0);

        $operationsHighlights = [
            [
                'title' => 'اعتماد العقود',
                'subtitle' => 'بانتظار الاعتماد',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                'meta' => 'اليوم: ' . number_format($contractsStats['today'] ?? 0),
                'video' => asset('admin/assets/img/new-dashboard/contracted.webm'),
                'icon' => 'fas fa-file-signature',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::New->value]),
            ],
            [
                'title' => 'حركة وصول اليوم',
                'subtitle' => 'تذاكر مؤكدة وموعودة بالوصول',
                'value' => $arrivalTodayCount,
                'meta' => 'تذاكر جاهزة: ' . number_format($ticketsReady),
                'video' => asset('admin/assets/img/new-dashboard/daily-arraivel.webm'),
                'icon' => 'fas fa-plane-arrival',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Arrival->value]),
            ],
            [
                'title' => 'المتأخرة بالوصول',
                'subtitle' => 'تجاوزت 60 يوماً بدون تذاكر',
                'value' => $delayedWithoutTickets,
                'meta' => 'مطلوب متابعة عاجلة',
                'video' => asset('admin/assets/img/new-dashboard/ezgif.com-resize.webm'),
                'icon' => 'fas fa-triangle-exclamation',
                'link' => route('RecruitmentContract.index', ['status' => 'current']),
            ],
            [
                'title' => 'عقود منتهية',
                'subtitle' => 'أقدم من عامين',
                'value' => $expiredLongTerm,
                'meta' => 'ضمان ' . number_format($warrantyActive) . ' | مكتملة ' . number_format($completedContractsCount) . ' | مرتجع ' . number_format($returnedContracts),
                'video' => asset('admin/assets/img/new-dashboard/ended-contracted.webm'),
                'icon' => 'fas fa-hourglass-end',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Completed->value]),
            ]
        ];

        $operationsLeft = [
            [
                'title' => 'عقود جديدة',
                'subtitle' => __('ingaz.new'),
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                'icon' => 'fas fa-file-signature',
                'color' => 'var(--dash-chart-1)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::New->value]),
            ],
            [
                'title' => __('ingaz.msaned'),
                'subtitle' => 'بعد الاعتماد',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Msaned->value] ?? 0,
                'icon' => 'fas fa-folder-open',
                'color' => 'var(--dash-chart-2)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Msaned->value]),
            ],
            [
                'title' => __('ingaz.tafwed'),
                'subtitle' => 'إجراءات التفويض',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Tafwed->value] ?? 0,
                'icon' => 'fas fa-key',
                'color' => 'var(--dash-chart-3)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Tafwed->value]),
            ],
            [
                'title' => __('ingaz.taffez'),
                'subtitle' => 'التفييز والتجهيز',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Taffez->value] ?? 0,
                'icon' => 'fas fa-check-double',
                'color' => 'var(--dash-chart-4)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Taffez->value]),
            ],
        ];

        $operationsRight = [
            [
                'title' => __('ingaz.tazker'),
                'subtitle' => 'تذاكر معتمدة',
                'value' => $ticketsReady,
                'icon' => 'fas fa-ticket-alt',
                'color' => 'var(--dash-chart-5)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Tazker->value]),
            ],
            [
                'title' => 'تحت الضمان',
                'subtitle' => 'آخر 90 يوماً',
                'value' => $warrantyActive,
                'icon' => 'fas fa-shield-alt',
                'color' => 'var(--warning)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Completed->value, 'warranty_status' => 'active']),
            ],
            [
                'title' => 'عقود مكتملة',
                'subtitle' => 'محصلة نهائية',
                'value' => $completedContractsCount,
                'icon' => 'fas fa-check-circle',
                'color' => 'var(--success)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Completed->value]),
            ],
            [
                'title' => 'عقود مرتجعة',
                'subtitle' => 'تحت إعادة المعالجة',
                'value' => $returnedContracts,
                'icon' => 'fas fa-undo-alt',
                'color' => 'var(--danger)',
                'link' => route('RecruitmentContract.index', ['status' => \App\Enums\ingaz\ContractStatusEnum::Back->value]),
            ],
        ];

        $operationsTotal = max(
            1,
            array_reduce(array_merge($operationsLeft, $operationsRight), function ($carry, $row) {
                return $carry + ($row['value'] ?? 0);
            }, 0)
        );
    @endphp

    <div class="data-section" id="operations">
        <div class="section-head">
            <div>
                <p class="section-eyebrow">العمليات</p>
                <h3 class="mb-0">مؤشرات التشغيل السريعة</h3>
            </div>
            <button class="section-toggle" data-toggle-section="operations" aria-expanded="true">
                <i class="fas fa-eye"></i> طي / عرض
            </button>
        </div>
        <div class="section-body operations-showcase" data-section-body="operations">
            <div class="operations-highlight-grid">
                @foreach ($operationsHighlights as $index => $highlight)
                    <a href="{{ $highlight['link'] ?? route('RecruitmentContract.index') }}"
                        class="operation-tile op-tile-{{ $index }} operation-link">
                        <div class="operation-icon">
                            {{-- <video src="{{ $highlight['video'] }}" autoplay muted loop playsinline></video> --}}
                            <i class="{{ $highlight['icon'] ?? 'fas fa-layer-group' }}"></i>
                        </div>
                        <p class="operation-subtitle">{{ $highlight['subtitle'] }}</p>
                        <p class="operation-value">{{ number_format($highlight['value']) }}</p>
                        <div class="operation-foot">
                            <span>{{ $highlight['title'] }}</span>
                            <span>{{ $highlight['meta'] }}</span>
                        </div>
                        <span class="operation-cta">عرض التفاصيل <i class="fas fa-arrow-left ms-1"></i></span>
                    </a>
                @endforeach
            </div>

            <div class="operations-detail-grid">
                <div class="operations-column">
                    <div class="operations-column-head">
                        <strong>مراحل ما قبل الوصول</strong>
                        <small>سير التنفيذ من الاعتماد حتى التذاكر</small>
                    </div>
                    @foreach ($operationsLeft as $card)
                        @php
                            $cardShare = $operationsTotal ? round((($card['value'] ?? 0) / $operationsTotal) * 100) : 0;
                        @endphp
                        <a href="{{ $card['link'] ?? route('RecruitmentContract.index') }}"
                            class="mini-operation-card mini-operation-link">
                            <div class="mini-operation-main">
                                <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>
                                <div class="mini-operation-text">
                                    <strong>{{ $card['title'] }}</strong>
                                    <small>{{ $card['subtitle'] }}</small>
                                </div>
                                <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                            </div>
                            <div class="mini-operation-meter">
                                <small>{{ $cardShare }}% من مؤشرات العمليات</small>
                                <div class="stage-progress">
                                    <span style="width: {{ $cardShare }}%; background: {{ $card['color'] }};"></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="operations-column">
                    <div class="operations-column-head">
                        <strong>مراحل ما بعد الوصول</strong>
                        <small>الضمان والإغلاق وإعادة المعالجة</small>
                    </div>
                    @foreach ($operationsRight as $card)
                        @php
                            $cardShare = $operationsTotal ? round((($card['value'] ?? 0) / $operationsTotal) * 100) : 0;
                        @endphp
                        <a href="{{ $card['link'] ?? route('RecruitmentContract.index') }}"
                            class="mini-operation-card mini-operation-link">
                            <div class="mini-operation-main">
                                <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                    <i class="{{ $card['icon'] }}"></i>
                                </span>
                                <div class="mini-operation-text">
                                    <strong>{{ $card['title'] }}</strong>
                                    <small>{{ $card['subtitle'] }}</small>
                                </div>
                                <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                            </div>
                            <div class="mini-operation-meter">
                                <small>{{ $cardShare }}% من مؤشرات العمليات</small>
                                <div class="stage-progress">
                                    <span style="width: {{ $cardShare }}%; background: {{ $card['color'] }};"></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="stat-row mt-3">
        <div class="stat-card is-5">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">عقود الإيجار</small>
                    <h3 class="mb-0">{{ number_format($rentalStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('RentContract.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">نشطة: {{ number_format($rentalStats['active'] ?? 0) }} | تحت الإجراء:
                {{ number_format($rentalStats['in_workflow'] ?? 0) }} | مكتملة:
                {{ number_format($rentalStats['completed'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-6">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">طلبات الإيجار من الموقع</small>
                    <h3 class="mb-0">{{ number_format($rentalOrderStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('rental-orders.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">جديدة: {{ number_format($rentalOrderStats['new'] ?? 0) }} | تحت الإجراء:
                {{ number_format($rentalOrderStats['under_process'] ?? 0) }} | مكتملة:
                {{ number_format($rentalOrderStats['completed'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-2">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">معاملات نقل الكفالة</small>
                    <h3 class="mb-0">{{ number_format($transferStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">قيد المراجعة: {{ number_format($transferStats['pending'] ?? 0) }} | معتمدة:
                {{ number_format($transferStats['approved'] ?? 0) }} | منقولة:
                {{ number_format($transferStats['transferred'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-1">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">طلبات نقل الكفالة من الموقع</small>
                    <h3 class="mb-0">{{ number_format($transferWebRequestStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('SponsorshipTransfer.webRequests') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">جديدة: {{ number_format($transferWebRequestStats['new'] ?? 0) }} | مدفوعة:
                {{ number_format($transferWebRequestStats['paid'] ?? 0) }} | مكتملة:
                {{ number_format($transferWebRequestStats['completed'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-7">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="stat-label">الإيواء</small>
                    <h3 class="mb-0">{{ number_format($shelterStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ route('dashboard.index') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 stat-meta">مشغول: {{ number_format($shelterStats['occupied'] ?? 0) }} | متاح:
                {{ number_format($shelterStats['available'] ?? 0) }}</div>
        </div>
    </div>

    @php
        $analyticsStats = $contractAnalyticsStats ?? [];
        $analyticsPipeline = $contractAnalyticsPipeline ?? [];
        $analyticsStatusCounts = $contractAnalyticsStatusBreakdown ?? [];
        $threeYearSeries = collect($contractsYearSeries ?? []);
        $currentYearSeries = $threeYearSeries->last();
        $contractsThreeYearsDisplayedTotal = $contractsThreeYearsTotal ?? (int) $threeYearSeries->sum('total');
        $missingContractDateCount = (int) ($analyticsStats['missingContractDate'] ?? 0);
        $professionStats = collect($contractProfessionStats ?? []);
        $professionSummary = $contractProfessionSummary ?? [];
        $contractOverviewCards = [
            [
                'label' => 'إجمالي العقود',
                'value' => $analyticsStats['total'] ?? 0,
                'meta' => ($contractFiltersApplied ?? false) ? 'العقود المطابقة للفلاتر الحالية' : 'جميع العقود المسجلة',
                'color' => 'var(--dash-chart-1)',
            ],
            [
                'label' => 'عقود سنة ' . ($currentYearSeries['year'] ?? now()->year),
                'value' => $currentYearSeries['total'] ?? 0,
                'meta' => 'بحسب تاريخ العقد فقط',
                'color' => 'var(--dash-chart-2)',
            ],
            [
                'label' => 'عقود هذا الشهر',
                'value' => $analyticsStats['thisMonth'] ?? 0,
                'meta' => 'بحسب تاريخ العقد فقط',
                'color' => 'var(--success)',
            ],
            [
                'label' => 'عقود مكتملة',
                'value' => $analyticsStats['completed'] ?? 0,
                'meta' => 'العقود التي أغلقت بنجاح',
                'color' => 'var(--warning)',
            ],
        ];
        $contractStageRows = [
            [
                'label' => 'بانتظار الاعتماد',
                'value' => $analyticsStatusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? ($analyticsPipeline['pre_arrival'] ?? 0),
                'meta' => 'قبل الوصول',
                'color' => 'var(--dash-chart-1)',
            ],
            [
                'label' => 'مرحلة الوصول اليوم',
                'value' => $analyticsPipeline['arrival_stage'] ?? 0,
                'meta' => 'تذاكر مؤكدة في الطريق',
                'color' => 'var(--dash-chart-2)',
            ],
            [
                'label' => 'تحت الضمان',
                'value' => $analyticsPipeline['under_warranty'] ?? 0,
                'meta' => 'آخر 90 يوماً',
                'color' => 'var(--warning)',
            ],
            [
                'label' => 'مكتمل',
                'value' => $analyticsPipeline['completed'] ?? 0,
                'meta' => 'تم إغلاقه',
                'color' => 'var(--success)',
            ],
            [
                'label' => 'متأخر بالوصول',
                'value' => $analyticsPipeline['delayed_arrival'] ?? 0,
                'meta' => 'تجاوز 60 يوماً بلا تذكرة',
                'color' => 'var(--dash-chart-5)',
            ],
            [
                'label' => 'عقود منتهية (> سنتين)',
                'value' => $analyticsPipeline['expired_over_2y'] ?? 0,
                'meta' => 'مراجعة أرشيفية',
                'color' => 'var(--danger)',
            ],
        ];
        $contractStagesRawTotal = array_reduce($contractStageRows, function ($carry, $row) {
            return $carry + ($row['value'] ?? 0);
        }, 0);
        $contractStagesTotal = max(1, $contractStagesRawTotal);
        $professionPalette = [
            'var(--dash-chart-1)',
            'var(--dash-chart-2)',
            'var(--dash-chart-3)',
            'var(--dash-chart-4)',
            'var(--dash-chart-5)',
            'var(--success)',
            'var(--warning)',
        ];
    @endphp

    <div id="contracts-overview" class="surface-card mt-3 contracts-overview-section">
        <div class="contract-section-header">
            <div class="contract-section-heading">
                <p class="section-eyebrow">العقود</p>
                <h4 class="mb-0">إجمالي العقود</h4>
                <small>عرض تحليلي لآخر 3 سنوات مع فلترة مباشرة حسب الجنسية والمهنة.</small>
            </div>
            <div class="contract-section-summary">
                <span class="contract-summary-pill">آخر 3 سنوات: {{ number_format($contractsThreeYearsDisplayedTotal) }} عقد</span>
                <span class="contract-summary-pill">عقود اليوم: {{ number_format($analyticsStats['today'] ?? 0) }}</span>
                @if ($missingContractDateCount > 0)
                    <span class="contract-summary-pill">بدون تاريخ عقد: {{ number_format($missingContractDateCount) }}</span>
                @endif
            </div>
        </div>
        <div class="contract-section-body">
            <form class="contract-filter-form" method="GET" action="{{ route('dashboard.index') }}#contracts-overview">
                <div class="contract-filter-grid">
                    <label class="contract-filter-field">
                        <span>الجنسية</span>
                        <select name="contract_nationality_id">
                            <option value="">كل الجنسيات</option>
                            @foreach ($contractNationalityOptions ?? [] as $nationalityOption)
                                <option value="{{ $nationalityOption['id'] }}"
                                    {{ (int) ($selectedContractNationalityId ?? 0) === (int) $nationalityOption['id'] ? 'selected' : '' }}>
                                    {{ $nationalityOption['title'] }} ({{ number_format($nationalityOption['total']) }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="contract-filter-field">
                        <span>المهنة</span>
                        <select name="contract_profession_id">
                            <option value="">كل المهن</option>
                            @foreach ($contractProfessionOptions ?? [] as $professionOption)
                                <option value="{{ $professionOption['id'] }}"
                                    {{ (int) ($selectedContractProfessionId ?? 0) === (int) $professionOption['id'] ? 'selected' : '' }}>
                                    {{ $professionOption['title'] }} ({{ number_format($professionOption['total']) }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="contract-filter-actions">
                        <button type="submit" class="btn btn-primary">تطبيق الفلترة</button>
                        <a href="{{ route('dashboard.index') }}#contracts-overview" class="btn btn-outline-secondary">إعادة الضبط</a>
                    </div>
                </div>

                @if ($contractFiltersApplied ?? false)
                    <div class="contract-active-filters">
                        <span class="contract-summary-pill">الفلاتر النشطة</span>
                        @if (!empty($selectedContractNationalityTitle))
                            <span class="contract-summary-pill">الجنسية: {{ $selectedContractNationalityTitle }}</span>
                        @endif
                        @if (!empty($selectedContractProfessionTitle))
                            <span class="contract-summary-pill">المهنة: {{ $selectedContractProfessionTitle }}</span>
                        @endif
                        <span class="contract-summary-pill">النتائج: {{ number_format($analyticsStats['total'] ?? 0) }} عقد</span>
                    </div>
                @endif
            </form>

            <div class="contracts-kpi-grid">
                @foreach ($contractOverviewCards as $overviewCard)
                    <div class="contract-kpi-card" style="--contract-kpi-accent: {{ $overviewCard['color'] }};">
                        <small>{{ $overviewCard['label'] }}</small>
                        <strong>{{ number_format($overviewCard['value']) }}</strong>
                        <span>{{ $overviewCard['meta'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="contracts-layout-grid">
                <div class="contracts-chart-panel">
                    <div class="contracts-chart-head">
                        <div>
                            <strong>حركة العقود خلال 3 سنوات</strong>
                            <small>كل سنة تظهر بلون مختلف لتسهيل المقارنة الشهرية.</small>
                        </div>
                        <span class="contract-summary-pill">هذا الأسبوع: {{ number_format($analyticsStats['thisWeek'] ?? 0) }}</span>
                    </div>
                    <div class="contracts-chart-wrap">
                        <canvas id="contractsChart"></canvas>
                    </div>
                </div>

                <div class="contract-stage-panel">
                    <div class="contract-stage-head">
                        <div class="contract-stage-heading">
                            <strong>مراحل العقود</strong>
                            <small>متابعة مباشرة لتدفق العقود حسب المرحلة</small>
                        </div>
                        <div class="contract-stage-total">
                            <small>الإجمالي</small>
                            <strong>{{ number_format($contractStagesRawTotal) }}</strong>
                        </div>
                    </div>
                    <a href="{{ route('RecruitmentContract.index') }}" class="contract-stage-link">عرض جميع العقود</a>
                    <div class="contract-stages mt-3 flex-grow-1">
                        @foreach ($contractStageRows as $stage)
                            @php
                                $stageWidth = $contractStagesTotal ? round(($stage['value'] / $contractStagesTotal) * 100) : 0;
                            @endphp
                            <div class="contract-stage-row">
                                <header>
                                    <div class="contract-stage-title">
                                        <span class="stage-dot" style="background: {{ $stage['color'] }};"></span>
                                        <strong>{{ $stage['label'] }}</strong>
                                    </div>
                                    <span class="contract-stage-value">{{ number_format($stage['value']) }}</span>
                                </header>
                                <div class="contract-stage-meta">
                                    @if (!empty($stage['meta']))
                                        <small>{{ $stage['meta'] }}</small>
                                    @else
                                        <small>بدون وصف إضافي</small>
                                    @endif
                                    <small>{{ $stageWidth }}%</small>
                                </div>
                                <div class="stage-progress">
                                    <span style="width: {{ $stageWidth }}%; background: {{ $stage['color'] }};"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="contract-profession-panel">
                <div class="contract-profession-head">
                    <div>
                        <strong>العقود حسب المهنة</strong>
                        <small>تفصيل العقود المرتبطة بكل مهنة أسفل إجمالي العقود مباشرة.</small>
                    </div>
                    <div class="contract-profession-pills">
                        <span class="contract-profession-pill">المهن المرصودة:
                            {{ number_format($professionSummary['tracked_professions'] ?? 0) }}</span>
                        @if (!empty($professionSummary['top_profession']))
                            <span class="contract-profession-pill">الأعلى:
                                {{ $professionSummary['top_profession'] }}</span>
                        @endif
                        <span class="contract-profession-pill">المعروف:
                            {{ number_format($professionSummary['known_contracts'] ?? 0) }}</span>
                        <span class="contract-profession-pill">غير محدد:
                            {{ number_format($professionSummary['unknown_contracts'] ?? 0) }}</span>
                    </div>
                </div>

                @if ($professionStats->count())
                    <div class="contract-profession-grid">
                        @foreach ($professionStats as $profession)
                            @php
                                $isUnknownProfession = $profession['is_unknown'] ?? false;
                                $accent = $isUnknownProfession
                                    ? 'var(--danger)'
                                    : $professionPalette[$loop->index % count($professionPalette)];
                                $shareWidth = max(
                                    min((int) ($profession['share'] ?? 0), 100),
                                    ((int) ($profession['total'] ?? 0) > 0 ? 6 : 0)
                                );
                            @endphp
                            <div class="contract-profession-card {{ $isUnknownProfession ? 'is-unknown' : '' }}"
                                style="--profession-accent: {{ $accent }};">
                                <div class="contract-profession-card-head">
                                    <span class="contract-profession-icon">
                                        <i class="fas fa-briefcase"></i>
                                    </span>
                                    <span class="contract-profession-share">{{ number_format((int) ($profession['share'] ?? 0)) }}%</span>
                                </div>
                                <strong class="contract-profession-name">{{ $profession['profession'] ?? 'غير محدد' }}</strong>
                                <div class="contract-profession-total">{{ number_format((int) ($profession['total'] ?? 0)) }}</div>
                                <div class="contract-profession-meta">
                                    <span>{{ number_format((int) ($profession['total'] ?? 0)) }} عقد</span>
                                    <span>{{ $isUnknownProfession ? 'تحتاج ربط مهنة' : 'من إجمالي العقود' }}</span>
                                </div>
                                <div class="stage-progress contract-profession-progress">
                                    <span style="width: {{ $shareWidth }}%; background: var(--profession-accent);"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state py-3">لا توجد بيانات متاحة لعرض العقود حسب المهنة.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 mt-3">
        <div class="col-xxl-4 col-lg-5">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">التوزيع الجنسي</p>
                        <h4 class="mb-0">أبرز الجنسيات المتاحة</h4>
                    </div>
                    <small>إجمالي {{ array_sum($nationalityData ?? []) }} عاملة</small>
                </div>
                <div class="chart-holder" style="height: 260px;">
                    <canvas id="nationalitiesChart"></canvas>
                </div>
                <ul class="legend-list">
                    @if (isset($nationalityLabels) && isset($nationalityData))
                        @foreach ($nationalityLabels as $index => $label)
                            <li>
                                <span>{{ $label }}</span>
                                <strong>{{ number_format($nationalityData[$index] ?? 0) }}</strong>
                            </li>
                        @endforeach
                    @else
                        <li class="empty-state">لا توجد بيانات متاحة</li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="col-xxl-4 col-lg-4">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">المكاتب الخارجية</p>
                        <h4 class="mb-0">المخزون لكل مكتب</h4>
                    </div>
                    <small>{{ count($officeStats ?? []) }} مكتب</small>
                </div>
                <div class="office-stock-list">
                    @forelse($officeStats ?? [] as $office)
                        @php
                            $available = (int) ($office->maids_available ?? 0);
                            $returned = (int) ($office->maids_returned ?? 0);
                            $backout = (int) ($office->maids_backout ?? 0);
                            $total = (int) ($office->maids_total ?? 0);
                            $other = (int) ($office->maids_other ?? max($total - $available - $returned - $backout, 0));
                            $avgRecruitmentDays = isset($office->avg_recruitment_days) ? (int) $office->avg_recruitment_days : null;
                            $arrivalsTotal = (int) ($office->arrivals_total ?? 0);
                            $nationalityFlagUrl = $office->nationality_flag_url ?? null;
                        @endphp
                        <a href="{{ route('maids.index', ['office_id' => $office->id]) }}" class="office-stock-card office-stock-link">
                            <div class="office-stock-head">
                                <div class="office-stock-heading">
                                    <strong>{{ $office->title ?? 'مكتب' }}</strong>
                                    <div class="office-stock-meta">
                                        <span class="office-stock-pill">إجمالي {{ number_format($total) }}</span>
                                        @if ($avgRecruitmentDays !== null)
                                            <span class="office-stock-pill">متوسط الوصول {{ number_format($avgRecruitmentDays) }}
                                                يوم</span>
                                        @else
                                            <span class="office-stock-pill is-muted">متوسط الوصول غير متاح</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="office-stock-total">سير {{ number_format($total) }}</span>
                            </div>

                            <div class="office-stock-nationality">
                                @if ($nationalityFlagUrl)
                                    <img src="{{ $nationalityFlagUrl }}"
                                        alt="{{ $office->nationality->title ?? 'الجنسية' }}">
                                @else
                                    <span class="office-stock-flag-fallback">
                                        <i class="fas fa-flag"></i>
                                    </span>
                                @endif
                                <span>{{ $office->nationality->title ?? 'غير محدد' }}</span>
                            </div>

                            <div class="office-stock-stats">
                                <span class="office-status-pill is-available">متاح {{ number_format($available) }}</span>
                                <span class="office-status-pill is-returned">مرتجع {{ number_format($returned) }}</span>
                                <span class="office-status-pill is-backout">باك أوت {{ number_format($backout) }}</span>
                                @if ($other > 0)
                                    <span class="office-status-pill is-other">أخرى {{ number_format($other) }}</span>
                                @endif
                            </div>

                            <small class="office-stock-foot">
                                @if ($arrivalsTotal > 0)
                                    متوسط المدة محسوب من {{ number_format($arrivalsTotal) }} حالة وصول
                                @else
                                    لا توجد حالات وصول مكتملة لاحتساب المتوسط
                                @endif
                            </small>
                        </a>
                    @empty
                        <div class="empty-state py-3">لا توجد بيانات مكاتب</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-lg-3">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">التحديثات الفورية</p>
                        <h4 class="mb-0">آخر الأنشطة</h4>
                    </div>
                    <small>{{ count($recentActivities ?? []) }} عنصر</small>
                </div>
                <div class="activity-feed">
                    @forelse($recentActivities ?? [] as $activity)
                        <div class="activity-line">
                            <div class="activity-icon">
                                <i class="fas fa-{{ $activity['icon'] ?? 'circle' }} text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong class="d-block">{{ $activity['title'] }}</strong>
                                <small>{{ $activity['time'] }}</small>
                            </div>
                            <span class="status-chip {{ $activity['type'] ?? 'info' }}">{{ $activity['status'] }}</span>
                        </div>
                    @empty
                        <div class="empty-state">لا يوجد نشاط حديث</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-3">
        <div class="col-xxl-8">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">الجدول الزمني</p>
                        <h4 class="mb-0">مواعيد وتذكيرات حرجة</h4>
                    </div>
                    <small id="calendarCount">{{ count($calendarEvents ?? []) }} موعداً</small>
                </div>
                <div id="calendar" class="calendar-shell"></div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">طاقم العمل</p>
                        <h4 class="mb-0">حجوزات وعقود الموظفين</h4>
                    </div>
                    <a href="{{ route('admins.index') }}" class="btn btn-outline-primary btn-sm">إدارة</a>
                </div>
                <div class="employee-summary-row">
                    <span class="employee-summary-pill">نشطون:
                        {{ number_format($employeeBookingSummary['employees_with_activity'] ?? 0) }}</span>
                    <span class="employee-summary-pill">الحجوزات:
                        {{ number_format($employeeBookingSummary['orders_total'] ?? 0) }}</span>
                    <span class="employee-summary-pill">العقود:
                        {{ number_format($employeeBookingSummary['contracts_total'] ?? 0) }}</span>
                </div>
                <div class="employee-performance-list">
                    @forelse($employeeBookings ?? [] as $emp)
                        @php
                            $handledTotal = (int) ($emp->handled_total ?? 0);
                        @endphp
                        <div class="employee-performance-card">
                            <div class="employee-performance-head">
                                <a href="{{ route('dashboard.employees.show', ['employee' => $emp->id, 'range' => 'all']) }}"
                                    class="employee-profile-link">{{ $emp->name }}</a>
                                <span class="employee-total-badge">{{ number_format($handledTotal) }} متابعة</span>
                            </div>
                            <div class="employee-performance-metrics">
                                <span class="employee-metric-chip chip-orders">حجوزات: {{ number_format((int) $emp->orders_count) }}</span>
                                <span class="employee-metric-chip">جديد: {{ number_format((int) $emp->orders_new) }}</span>
                                <span class="employee-metric-chip">تحت الإجراء: {{ number_format((int) $emp->orders_under_process) }}</span>
                                <span class="employee-metric-chip">تم التعاقد: {{ number_format((int) $emp->orders_contracted) }}</span>
                                <span class="employee-metric-chip">مغلقة: {{ number_format((int) $emp->orders_completed) }}</span>
                                <span class="employee-metric-chip chip-contracts">عقود: {{ number_format((int) $emp->contracts_count) }}</span>
                                <span class="employee-metric-chip">جارية: {{ number_format((int) $emp->contracts_current) }}</span>
                                <span class="employee-metric-chip">مكتملة: {{ number_format((int) $emp->contracts_completed) }}</span>
                                <span class="employee-metric-chip">مرتجعة: {{ number_format((int) $emp->contracts_returned) }}</span>
                            </div>
                            <div class="employee-performance-footer">
                                <small>نسبة التعاقد من الحجوزات:
                                    {{ number_format((int) ($emp->conversion_rate ?? 0)) }}%</small>
                                <div class="employee-performance-actions">
                                    <a href="{{ route('dashboard.employees.show', ['employee' => $emp->id, 'range' => 'all']) }}"
                                        class="btn btn-primary btn-sm">الملف التحليلي</a>
                                    <a href="{{ route('orders.index', ['admin_id' => $emp->id]) }}"
                                        class="btn btn-outline-primary btn-sm">الحجوزات</a>
                                    <a href="{{ route('RecruitmentContract.index', ['marketer_id' => $emp->id]) }}"
                                        class="btn btn-outline-success btn-sm">العقود</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-3">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script>
        $(document).ready(function() {
            const rootStyles = getComputedStyle(document.documentElement);
            const getCssVar = (name, fallback) => {
                const value = rootStyles.getPropertyValue(name).trim();
                return value || fallback;
            };
            const hexToRgba = (hex, alpha) => {
                let value = hex.replace('#', '').trim();
                if (value.length === 3) {
                    value = value.split('').map((c) => c + c).join('');
                }
                if (value.length !== 6) return hex;
                const bigint = parseInt(value, 16);
                const r = (bigint >> 16) & 255;
                const g = (bigint >> 8) & 255;
                const b = bigint & 255;
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            };
            const toRgba = (color, alpha) => {
                if (!color) return `rgba(95, 91, 255, ${alpha})`;
                const trimmed = color.trim();
                if (trimmed.startsWith('rgba(')) {
                    return trimmed.replace(/rgba\(([^)]+)\)/, (match, values) => {
                        const parts = values.split(',').map((v) => v.trim());
                        return `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, ${alpha})`;
                    });
                }
                if (trimmed.startsWith('rgb(')) {
                    return trimmed.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
                }
                if (trimmed.startsWith('#')) {
                    return hexToRgba(trimmed, alpha);
                }
                return trimmed;
            };

            const chartColor1 = getCssVar('--dash-chart-1', '#5f5bff');
            const chartColor2 = getCssVar('--dash-chart-2', '#28c76f');
            const chartColor3 = getCssVar('--dash-chart-3', '#36b9cc');
            const chartColor4 = getCssVar('--dash-chart-4', '#f4b347');
            const chartColor5 = getCssVar('--dash-chart-5', '#ef5350');
            const successColor = getCssVar('--success', '#28c76f');

            const rangeButtons = document.querySelectorAll('.range-btn');
            const rangeValues = document.querySelectorAll('[data-range-value]');
            const rangeLabels = document.querySelectorAll('[data-range-label]');

            const numberFormatter = new Intl.NumberFormat('ar-SA');

            function formatValue(value, format) {
                if (format === 'currency') {
                    return new Intl.NumberFormat('ar-SA', {
                        style: 'currency',
                        currency: 'SAR',
                        maximumFractionDigits: 0
                    }).format(value);
                }
                return numberFormatter.format(value);
            }

            rangeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const targetRange = button.dataset.range;
                    rangeButtons.forEach((btn) => btn.classList.toggle('active', btn === button));

                    rangeValues.forEach((element) => {
                        const format = element.dataset.rangeFormat || 'number';
                        const datasetValue = element.dataset[targetRange];
                        if (datasetValue === undefined) return;
                        element.textContent = formatValue(Number(datasetValue) || 0,
                            format);
                    });

                    rangeLabels.forEach((element) => {
                        const labelText = element.dataset[targetRange];
                        if (labelText) {
                            element.textContent = labelText;
                        }
                    });
                });
            });

            if (rangeButtons.length) {
                rangeButtons[0].click();
            }

            document.querySelectorAll('[data-toggle-section]').forEach((button) => {
                button.addEventListener('click', () => {
                    const sectionName = button.dataset.toggleSection;
                    const target = document.querySelector(`[data-section-body="${sectionName}"]`);
                    if (!target) return;
                    target.classList.toggle('collapsed');
                    button.setAttribute('aria-expanded', !target.classList.contains('collapsed'));
                    button.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });

            // عقود
            const contractsCanvas = document.getElementById('contractsChart');
            if (contractsCanvas) {
                const contractsCtx = contractsCanvas.getContext('2d');
                const contractsYearSeries = {!! json_encode($contractsYearSeries ?? []) !!};
                const contractsYearColors = [chartColor1, chartColor2, successColor];

                new Chart(contractsCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($contractsChartLabels ?? []) !!},
                        datasets: contractsYearSeries.map((series, index) => {
                            const color = contractsYearColors[index % contractsYearColors.length];

                            return {
                                label: series.label,
                                data: series.data || [],
                                borderColor: color,
                                backgroundColor: toRgba(color, 0.82),
                                borderWidth: 2,
                                borderRadius: 10,
                                borderSkipped: false,
                                maxBarThickness: 26,
                                categoryPercentage: 0.72,
                                barPercentage: 0.9
                            };
                        })
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                                padding: 12,
                                rtl: true,
                                textDirection: 'rtl'
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                },
                                grid: {
                                    borderDash: [4, 4],
                                    color: 'rgba(15, 23, 42, 0.08)'
                                }
                            }
                        }
                    }
                });
            }

            // الجنسيات
            const nationalitiesCtx = document.getElementById('nationalitiesChart').getContext('2d');
            new Chart(nationalitiesCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($nationalityLabels ?? ['الفلبين', 'إندونيسيا', 'بنغلاديش', 'الهند', 'أخرى']) !!},
                    datasets: [{
                        data: {!! json_encode($nationalityData ?? [35, 25, 20, 15, 5]) !!},
                        backgroundColor: [chartColor1, successColor, chartColor2, chartColor4, chartColor5],
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverOffset: 8,
                        cutout: '68%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            rtl: true,
                            textDirection: 'rtl'
                        }
                    }
                }
            });

            // التقويم
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                const calendarEventsUrl = "{{ route('travel.calendar.events') }}";
                const calendarCountEl = document.getElementById('calendarCount');

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'ar',
                    direction: 'rtl',
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    height: 500,
                    events: function(fetchInfo, successCallback, failureCallback) {
                        $.ajax({
                            url: calendarEventsUrl,
                            type: 'GET',
                            data: {
                                start: fetchInfo.startStr,
                                end: fetchInfo.endStr
                            },
                            success: function(response) {
                                if (response && response.success && Array.isArray(response.events)) {
                                    successCallback(response.events);
                                    if (calendarCountEl) {
                                        calendarCountEl.textContent = `${response.events.length} موعداً`;
                                    }
                                } else {
                                    successCallback([]);
                                    if (calendarCountEl) {
                                        calendarCountEl.textContent = '0 موعداً';
                                    }
                                }
                            },
                            error: function(xhr) {
                                console.error('Calendar events load failed', xhr);
                                failureCallback(xhr);
                                if (calendarCountEl) {
                                    calendarCountEl.textContent = '0 موعداً';
                                }
                            }
                        });
                    },
                    eventClick: function(info) {
                        const eventUrl = info.event.extendedProps.url;
                        if (eventUrl) {
                            window.location.href = eventUrl;
                            return;
                        }

                        const tripId = info.event.extendedProps.trip_id || info.event.id;
                        if (tripId) {
                            const editUrl = "{{ route('travel.edit', ':id') }}".replace(':id', tripId);
                            window.location.href = editUrl;
                        }
                    }
                });
                calendar.render();
            }
        });
    </script>
@endsection
