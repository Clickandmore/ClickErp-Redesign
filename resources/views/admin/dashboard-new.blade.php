@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/css/dashboard-new.css') }}">
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
                    <small class="text-light opacity-75">طلبات الاستقدام</small>
                    <h3 class="text-white mb-0">{{ number_format($ordersStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/orders') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">جديدة: {{ number_format($ordersStats['new'] ?? 0) }} | تحت الإجراء:
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
                    <small class="text-light opacity-75">عقود الاستقدام</small>
                    <h3 class="text-white mb-0">{{ number_format($contractsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/contracts') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">قبل الوصول: {{ number_format($contractsPipeline['pre_arrival'] ?? 0) }} |
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
                    <small class="text-light opacity-75">السير الذاتية</small>
                    <h3 class="text-white mb-0">{{ number_format($cvStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/maids') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">متاحة: {{ number_format($cvStats['available'] ?? 0) }} | محجوزة:
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
                    <small class="text-light opacity-75">العملاء</small>
                    <h3 class="text-white mb-0">{{ number_format($clientsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('admin/clients') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">لديهم طلبات: {{ number_format($clientsStats['with_orders'] ?? 0) }} | بدون
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
        $completedContracts = $insights['completed'] ?? ($contractsStats['completed'] ?? 0);

        $operationsHighlights = [
            [
                'title' => 'اعتماد العقود',
                'subtitle' => 'بانتظار الاعتماد',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                'meta' => 'اليوم: ' . number_format($contractsStats['today'] ?? 0),
                'video' => asset('admin/assets/img/new-dashboard/contracted.webm'),
            ],
            [
                'title' => 'حركة وصول اليوم',
                'subtitle' => 'تذاكر مؤكدة وموعودة بالوصول',
                'value' => $arrivalTodayCount,
                'meta' => 'تذاكر جاهزة: ' . number_format($ticketsReady),
                'video' => asset('admin/assets/img/new-dashboard/daily-arraivel.webm'),
            ],
            [
                'title' => 'المتأخرة بالوصول',
                'subtitle' => 'تجاوزت 60 يوماً بدون تذاكر',
                'value' => $delayedWithoutTickets,
                'meta' => 'مطلوب متابعة عاجلة',
                'video' => asset('admin/assets/img/new-dashboard/ezgif.com-resize.webm'),
            ],
            [
                'title' => 'عقود منتهية',
                'subtitle' => 'أقدم من عامين',
                'value' => $expiredLongTerm,
                'meta' => 'ضمان ' . number_format($warrantyActive) . ' | مكتملة ' . number_format($completedContracts) . ' | مرتجع ' . number_format($returnedContracts),
                'video' => asset('admin/assets/img/new-dashboard/ended-contracted.webm'),
            ]
        ];

        $operationsLeft = [
            [
                'title' => 'عقود جديدة',
                'subtitle' => __('ingaz.new'),
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? 0,
                'icon' => 'fas fa-file-signature',
                'color' => 'var(--dash-chart-1)'
            ],
            [
                'title' => __('ingaz.msaned'),
                'subtitle' => 'بعد الاعتماد',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Msaned->value] ?? 0,
                'icon' => 'fas fa-folder-open',
                'color' => 'var(--dash-chart-2)'
            ],
            [
                'title' => __('ingaz.tafwed'),
                'subtitle' => 'إجراءات التفويض',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Tafwed->value] ?? 0,
                'icon' => 'fas fa-key',
                'color' => 'var(--dash-chart-3)'
            ],
            [
                'title' => __('ingaz.taffez'),
                'subtitle' => 'التفييز والتجهيز',
                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::Taffez->value] ?? 0,
                'icon' => 'fas fa-check-double',
                'color' => 'var(--dash-chart-4)'
            ],
        ];

        $operationsRight = [
            [
                'title' => __('ingaz.tazker'),
                'subtitle' => 'تذاكر معتمدة',
                'value' => $ticketsReady,
                'icon' => 'fas fa-ticket-alt',
                'color' => 'var(--dash-chart-5)'
            ],
            [
                'title' => 'تحت الضمان',
                'subtitle' => 'آخر 90 يوماً',
                'value' => $warrantyActive,
                'icon' => 'fas fa-shield-alt',
                'color' => 'var(--warning)'
            ],
            [
                'title' => 'عقود مكتملة',
                'subtitle' => 'محصلة نهائية',
                'value' => $completedContracts,
                'icon' => 'fas fa-check-circle',
                'color' => 'var(--success)'
            ],
            [
                'title' => 'عقود مرتجعة',
                'subtitle' => 'تحت إعادة المعالجة',
                'value' => $returnedContracts,
                'icon' => 'fas fa-undo-alt',
                'color' => 'var(--danger)'
            ],
        ];
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
                    <div class="operation-tile op-tile-{{ $index }}">
                        <div class="operation-icon">
                            <video src="{{ $highlight['video'] }}" autoplay muted loop playsinline></video>
                        </div>
                        <p class="operation-subtitle">{{ $highlight['subtitle'] }}</p>
                        <p class="operation-value">{{ number_format($highlight['value']) }}</p>
                        <div class="operation-foot">
                            <span>{{ $highlight['title'] }}</span>
                            <span>{{ $highlight['meta'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="operations-detail-grid">
                <div class="operations-column">
                    @foreach ($operationsLeft as $card)
                        <div class="mini-operation-card">
                            <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                <i class="{{ $card['icon'] }}"></i>
                            </span>
                            <div class="mini-operation-text">
                                <strong>{{ $card['title'] }}</strong>
                                <small>{{ $card['subtitle'] }}</small>
                            </div>
                            <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="operations-column">
                    @foreach ($operationsRight as $card)
                        <div class="mini-operation-card">
                            <span class="mini-operation-icon" style="background: {{ $card['color'] }};">
                                <i class="{{ $card['icon'] }}"></i>
                            </span>
                            <div class="mini-operation-text">
                                <strong>{{ $card['title'] }}</strong>
                                <small>{{ $card['subtitle'] }}</small>
                            </div>
                            <span class="mini-operation-value">{{ number_format($card['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="stat-row mt-3">
        <div class="stat-card is-5">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">عقود الإيجار</small>
                    <h3 class="text-white mb-0">{{ number_format($rentalStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/rental-orders') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">نشطة: {{ number_format($rentalStats['active'] ?? 0) }} | تحت الإجراء:
                {{ number_format($rentalStats['in_workflow'] ?? 0) }} | مكتملة:
                {{ number_format($rentalStats['completed'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-6">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">نقل الكفالة</small>
                    <h3 class="text-white mb-0">{{ number_format($transferStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/transfer-service-contract') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">قيد المراجعة: {{ number_format($transferStats['pending'] ?? 0) }} | معتمدة:
                {{ number_format($transferStats['approved'] ?? 0) }} | منقولة:
                {{ number_format($transferStats['transferred'] ?? 0) }}</div>
        </div>

        <div class="stat-card is-7">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">الإيواء</small>
                    <h3 class="text-white mb-0">{{ number_format($shelterStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard') }}" class="btn btn-sm btn-light text-dark">عرض</a>
            </div>
            <div class="mt-3 text-white small">مشغول: {{ number_format($shelterStats['occupied'] ?? 0) }} | متاح:
                {{ number_format($shelterStats['available'] ?? 0) }}</div>
        </div>
    </div>

    @php $contractsLast12Total = array_sum($newContracts ?? []); @endphp

    <div class="surface-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">اجمالي العقود</h5>
            <small class="text-muted">إجمالي آخر 12 شهر: {{ number_format($contractsLast12Total) }} عقد</small>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-8">
                    <div style="height: 260px;">
                        <canvas id="contractsChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    @php
                        $contractStageRows = [
                            [
                                'label' => 'بانتظار الاعتماد',
                                'value' => $statusCounts[\App\Enums\ingaz\ContractStatusEnum::New->value] ?? ($contractsPipeline['pre_arrival'] ?? 0),
                                'meta' => 'قبل الوصول',
                                'color' => 'var(--dash-chart-1)'
                            ],
                            [
                                'label' => 'مرحلة الوصول اليوم',
                                'value' => $contractsPipeline['arrival_stage'] ?? 0,
                                'meta' => 'تذاكر مؤكدة في الطريق',
                                'color' => 'var(--dash-chart-2)'
                            ],
                            [
                                'label' => 'تحت الضمان',
                                'value' => $contractsPipeline['under_warranty'] ?? 0,
                                'meta' => 'آخر 90 يوماً',
                                'color' => 'var(--warning)'
                            ],
                            [
                                'label' => 'مكتمل',
                                'value' => $contractsPipeline['completed'] ?? 0,
                                'meta' => 'تم إغلاقه',
                                'color' => 'var(--success)'
                            ],
                            [
                                'label' => 'متأخر بالوصول',
                                'value' => $contractsPipeline['delayed_arrival'] ?? 0,
                                'meta' => 'تجاوز 60 يوماً بلا تذكرة',
                                'color' => 'var(--dash-chart-5)'
                            ],
                            [
                                'label' => 'عقود منتهية (> سنتين)',
                                'value' => $contractsPipeline['expired_over_2y'] ?? 0,
                                'meta' => 'مراجعة أرشيفية',
                                'color' => 'var(--danger)'
                            ],
                        ];
                        $contractStagesTotal = max(
                            1,
                            array_reduce($contractStageRows, function ($carry, $row) {
                                return $carry + ($row['value'] ?? 0);
                            }, 0)
                        );
                    @endphp
                    <div class="p-3 border rounded-3 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>مراحل العقود</strong>
                            <a href="{{ url('dashboard/contracts') }}" class="small text-primary">عرض</a>
                        </div>
                        <div class="contract-stages mt-3 flex-grow-1">
                            @foreach ($contractStageRows as $stage)
                                @php
                                    $stageWidth = $contractStagesTotal ? round(($stage['value'] / $contractStagesTotal) * 100) : 0;
                                @endphp
                                <div class="contract-stage-row">
                                    <header>
                                        <strong>{{ $stage['label'] }}</strong>
                                        <span>{{ number_format($stage['value']) }}</span>
                                    </header>
                                    @if (!empty($stage['meta']))
                                        <small>{{ $stage['meta'] }} • {{ $stageWidth }}%</small>
                                    @else
                                        <small>{{ $stageWidth }}%</small>
                                    @endif
                                    <div class="stage-progress">
                                        <span style="width: {{ $stageWidth }}%; background: {{ $stage['color'] }};"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
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
                <div class="d-flex flex-column gap-2" style="max-height: 320px; overflow: auto;">
                    @forelse($officeStats ?? [] as $office)
                        @php
                            $available = $office->maids_available ?? 0;
                            $total = $office->maids_total ?? 0;
                            $booked = max($total - $available, 0);
                        @endphp
                        <div class="p-3 rounded-3"
                            style="background: var(--surface-muted); border: 1px solid var(--stroke);">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ $office->title ?? 'مكتب' }}</strong>
                                <span class="badge bg-light text-dark">متاح {{ $available }} /
                                    {{ $total }}</span>
                            </div>
                            <small class="text-muted d-block mt-1">جنسية: {{ $office->nationality->title ?? 'غير محدد' }}
                                | حجز: {{ $booked }}</small>
                        </div>
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
                    <small>{{ count($calendarEvents ?? []) }} موعداً</small>
                </div>
                <div id="calendar" class="calendar-shell"></div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">طاقم العمل</p>
                        <h4 class="mb-0">حجوزات الموظفين</h4>
                    </div>
                    <a href="{{ url('dashboard/admins') }}" class="btn btn-outline-primary btn-sm">إدارة</a>
                </div>
                <div class="d-flex flex-column gap-2" style="max-height: 320px; overflow: auto;">
                    @forelse($employeeBookings ?? [] as $emp)
                        <div class="p-3 rounded-3"
                            style="background: var(--surface-muted); border: 1px solid var(--stroke);">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $emp->name }}</strong>
                                <span class="badge bg-primary-subtle text-primary">{{ $emp->orders_count }}</span>
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
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css' rel='stylesheet' />
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
                new Chart(contractsCtx, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($chartLabels ?? []) !!},
                        datasets: [{
                            label: 'عقود جديدة',
                            data: {!! json_encode($newContracts ?? []) !!},
                            backgroundColor: toRgba(chartColor1, 0.85),
                            borderColor: chartColor1,
                            borderWidth: 2,
                            borderRadius: 10,
                            barPercentage: 0.65
                        }, {
                            label: 'عقود مكتملة',
                            data: {!! json_encode($completedContracts ?? []) !!},
                            backgroundColor: toRgba(successColor, 0.85),
                            borderColor: successColor,
                            borderWidth: 2,
                            borderRadius: 10,
                            barPercentage: 0.65
                        }]
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
                    events: {!! json_encode($calendarEvents ?? []) !!},
                    eventClick: function(info) {
                        if (info.event.extendedProps.url) {
                            window.location.href = info.event.extendedProps.url;
                        }
                    }
                });
                calendar.render();
            }
        });
    </script>
@endsection
