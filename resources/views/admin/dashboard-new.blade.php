@extends('admin.layout.indexs.index')

@section('style')
    <style>
        :root {
            --surface: #ffffff;
            --surface-muted: #f7f7fb;
            --stroke: rgba(15, 23, 42, 0.08);
            --primary: #5f5bff;
            --primary-dark: #3936d4;
            --success: #28c76f;
            --warning: #ffa726;
            --danger: #ff4d4f;
            --text: #0f172a;
            --muted: #6b7280;
        }

        .surface-card {
            border-radius: 28px;
            background: var(--surface);
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
        }

        .dashboard-hero {
            display: flex;
            gap: 32px;
            padding: 32px;
            background: linear-gradient(135deg, #1f1f39, #353086);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .dashboard-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.2), transparent 55%);
            pointer-events: none;
        }

        .dashboard-hero>* {
            position: relative;
            z-index: 1;
        }

        .hero-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .hero-eyebrow {
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            opacity: 0.8;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.85);
        }

        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(40, 199, 111, 0.2);
            color: #4ef9a5;
            font-size: 12px;
        }

        .live-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4ef9a5;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(78, 249, 165, 0.6);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(78, 249, 165, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(78, 249, 165, 0);
            }
        }

        .hero-quick-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-quick-actions a {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 14px;
            padding: 10px 18px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s ease;
        }

        .hero-quick-actions a:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .hero-controls {
            width: min(360px, 100%);
            border-radius: 24px;
            background: rgba(7, 10, 28, 0.35);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            backdrop-filter: blur(8px);
        }

        .range-switch {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .range-btn {
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: transparent;
            color: #fff;
            border-radius: 12px;
            padding: 8px 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .range-btn.active {
            background: #fff;
            color: #1f1f39;
            border-color: #fff;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        .hero-micro-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            font-size: 14px;
        }

        .hero-micro-grid div {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 10px;
            text-align: center;
        }

        .hero-micro-grid strong {
            display: block;
            font-size: 20px;
            margin-top: 4px;
        }

        .insight-grid {
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .stat-card {
            border-radius: 18px;
            padding: 18px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        .insight-card {
            padding: 20px;
            border-radius: 22px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            position: relative;
            overflow: hidden;
        }

        .insight-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(95, 91, 255, 0.08), transparent 40%);
            pointer-events: none;
        }

        .insight-card h4 {
            font-size: 14px;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .insight-value {
            font-size: 36px;
            font-weight: 600;
            color: var(--text);
        }

        .insight-foot {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .trend-up {
            color: var(--success);
            font-weight: 600;
        }

        .trend-down {
            color: var(--danger);
            font-weight: 600;
        }

        .data-section {
            margin-top: 32px;
            padding: 24px;
            border-radius: 24px;
            background: var(--surface);
            border: 1px solid var(--stroke);
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .section-eyebrow {
            font-size: 13px;
            color: var(--muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }

        .section-toggle {
            border: 1px solid var(--stroke);
            background: transparent;
            border-radius: 12px;
            padding: 6px 14px;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .section-body {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .section-body.collapsed {
            display: none;
        }

        .metric-card {
            border: 1px solid var(--stroke);
            border-radius: 18px;
            padding: 18px;
            background: var(--surface-muted);
        }

        .metric-card h5 {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .metric-card strong {
            font-size: 24px;
            color: var(--text);
        }

        .progress-line {
            margin-top: 12px;
            height: 8px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.08);
            position: relative;
            overflow: hidden;
        }

        .progress-line span {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: var(--primary);
        }

        .progress-line.success span {
            background: var(--success);
        }

        .progress-line.warning span {
            background: var(--warning);
        }

        .progress-line.danger span {
            background: var(--danger);
        }

        .panel-card {
            border-radius: 24px;
            background: var(--surface);
            border: 1px solid var(--stroke);
            padding: 24px;
            height: 100%;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .panel-head small {
            color: var(--muted);
        }

        .chart-holder {
            position: relative;
            height: 320px;
        }

        .chart-holder canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .legend-list {
            list-style: none;
            padding: 0;
            margin: 20px 0 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 180px;
            overflow-y: auto;
        }

        .legend-list li {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--muted);
        }

        .legend-list strong {
            color: var(--text);
        }

        .activity-feed {
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: 360px;
            overflow-y: auto;
        }

        .activity-line {
            display: flex;
            gap: 12px;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--stroke);
        }

        .activity-line:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--surface-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .activity-line small {
            color: var(--muted);
        }

        .status-chip {
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            text-transform: capitalize;
        }

        .status-chip.success {
            background: rgba(40, 199, 111, 0.12);
            color: var(--success);
        }

        .status-chip.warning {
            background: rgba(255, 167, 38, 0.12);
            color: var(--warning);
        }

        .status-chip.danger {
            background: rgba(255, 77, 79, 0.12);
            color: var(--danger);
        }

        .status-chip.info {
            background: rgba(95, 91, 255, 0.12);
            color: var(--primary-dark);
        }

        .calendar-shell {
            min-height: 400px;
        }

        .empty-state {
            text-align: center;
            padding: 32px 0;
            color: var(--muted);
        }

        @media (max-width: 992px) {
            .dashboard-hero {
                flex-direction: column;
            }

            .hero-controls {
                width: 100%;
            }
        }
    </style>
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
                    <small>طلبات اليوم</small>
                    <strong>{{ number_format($ordersStats['today'] ?? 0) }}</strong>
                </div>
                <div>
                    <small>عقود قيد المتابعة</small>
                    <strong>{{ number_format($contractsStats['pending'] ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-row">
        <div class="stat-card" style="background: linear-gradient(135deg,#0d5c63,#0a3f44);">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">طلبات الاستقدام</small>
                    <h3 class="text-white mb-0">{{ number_format($ordersStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/orders') }}" class="btn btn-sm btn-light text-dark">عرض</a>
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

        <div class="stat-card" style="background: linear-gradient(135deg,#0a4d8c,#082f51);">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">عقود الاستقدام</small>
                    <h3 class="text-white mb-0">{{ number_format($contractsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/contracts') }}" class="btn btn-sm btn-light text-dark">عرض</a>
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

        <div class="stat-card" style="background: linear-gradient(135deg,#1f1f39,#0c0c1c);">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">السير الذاتية</small>
                    <h3 class="text-white mb-0">{{ number_format($cvStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/maids') }}" class="btn btn-sm btn-light text-dark">عرض</a>
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

        <div class="stat-card" style="background: linear-gradient(135deg,#b87900,#6e4c00);">
            <div class="stat-head d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-light opacity-75">العملاء</small>
                    <h3 class="text-white mb-0">{{ number_format($clientsStats['total'] ?? 0) }}</h3>
                </div>
                <a href="{{ url('dashboard/clients') }}" class="btn btn-sm btn-light text-dark">عرض</a>
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
        $ordersTotal = max($ordersStats['total'] ?? 0, 1);
        $completedPercent = round((($ordersStats['completed'] ?? 0) / $ordersTotal) * 100);
        $pendingPercent = round((($ordersStats['pending'] ?? 0) / $ordersTotal) * 100);
        $processingPercent = round((($ordersStats['processing'] ?? 0) / $ordersTotal) * 100);

        $cvTotal = max($cvStats['total'] ?? 0, 1);
        $cvAvailablePercent = round((($cvStats['available'] ?? 0) / $cvTotal) * 100);

        $employeesTotal = max($employeesStats['total'] ?? 0, 1);
        $employeesActivePercent = round((($employeesStats['active'] ?? 0) / $employeesTotal) * 100);

        $shelterTotal = max($shelterStats['total'] ?? 0, 1);
        $shelterOccupiedPercent = round((($shelterStats['occupied'] ?? 0) / $shelterTotal) * 100);
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
        <div class="section-body" data-section-body="operations">
            <div class="metric-card">
                <h5>مسار الطلبات</h5>
                <strong>{{ number_format($ordersStats['total'] ?? 0) }}</strong>
                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>مكتمل</span>
                    <span>{{ $completedPercent }}%</span>
                </div>
                <div class="progress-line success"><span style="width: {{ $completedPercent }}%"></span></div>
                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>قيد التنفيذ</span>
                    <span>{{ $processingPercent }}%</span>
                </div>
                <div class="progress-line warning"><span style="width: {{ $processingPercent }}%"></span></div>
                <div class="d-flex justify-content-between mt-2 small text-muted">
                    <span>معلق</span>
                    <span>{{ $pendingPercent }}%</span>
                </div>
                <div class="progress-line danger"><span style="width: {{ $pendingPercent }}%"></span></div>
            </div>

            <div class="metric-card">
                <h5>السير الذاتية</h5>
                <strong>{{ number_format($cvStats['available'] ?? 0) }}</strong>
                <small class="d-block text-muted">متاح من أصل {{ number_format($cvStats['total'] ?? 0) }}</small>
                <div class="progress-line success"><span style="width: {{ $cvAvailablePercent }}%"></span></div>
                <div class="mt-2 text-muted small">{{ number_format($cvStats['newThisWeek'] ?? 0) }} سيرة جديدة هذا
                    الأسبوع</div>
            </div>

            <div class="metric-card">
                <h5>فريق العمل</h5>
                <strong>{{ number_format($employeesStats['active'] ?? 0) }}</strong>
                <small class="d-block text-muted">من أصل {{ number_format($employeesStats['total'] ?? 0) }}</small>
                <div class="progress-line"><span style="width: {{ $employeesActivePercent }}%"></span></div>
                <div class="mt-2 text-muted small">مبيعات: {{ $employeesStats['departments']['sales'] ?? 0 }} | دعم:
                    {{ $employeesStats['departments']['support'] ?? 0 }}</div>
            </div>

            <div class="metric-card">
                <h5>الإيواء</h5>
                <strong>{{ number_format($shelterStats['occupied'] ?? 0) }}</strong>
                <small class="d-block text-muted">مشغول من أصل {{ number_format($shelterStats['total'] ?? 0) }}</small>
                <div class="progress-line warning"><span style="width: {{ $shelterOccupiedPercent }}%"></span></div>
                <div class="mt-2 text-muted small">المتاح: {{ number_format($shelterStats['available'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="stat-row mt-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0c6f75,#0a4a4e);">
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

        <div class="stat-card" style="background: linear-gradient(135deg,#7b2cbf,#4b1f73);">
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

        <div class="stat-card" style="background: linear-gradient(135deg,#a86b00,#6d4500);">
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

    @php $contractsLast6Total = array_sum($newContracts ?? []); @endphp

    <div class="surface-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">اجمالي العقود</h5>
            <small class="text-muted">إجمالي الفترة: {{ number_format($contractsLast6Total) }} عقد</small>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-8">
                    <div style="height: 260px;">
                        <canvas id="contractsChart"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="p-3 border rounded-3 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>مراحل العقود</strong>
                            <a href="{{ url('dashboard/contracts') }}" class="small text-primary">عرض</a>
                        </div>
                        <div class="mt-3 d-grid gap-2 flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <span>قبل
                                    الوصول</span><strong>{{ number_format($contractsPipeline['pre_arrival'] ?? 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>مرحلة
                                    الوصول</span><strong>{{ number_format($contractsPipeline['arrival_stage'] ?? 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>تحت
                                    الضمان</span><strong>{{ number_format($contractsPipeline['under_warranty'] ?? 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>مكتمل</span><strong>{{ number_format($contractsPipeline['completed'] ?? 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-warning">
                                <span>متأخر (>60
                                    يوم)</span><strong>{{ number_format($contractsPipeline['delayed_arrival'] ?? 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between text-danger">
                                <span>منتهي
                                    (>سنتين)</span><strong>{{ number_format($contractsPipeline['expired_over_2y'] ?? 0) }}</strong>
                            </div>
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
                            backgroundColor: 'rgba(95, 91, 255, 0.85)',
                            borderColor: '#5f5bff',
                            borderWidth: 2,
                            borderRadius: 10,
                            barPercentage: 0.65
                        }, {
                            label: 'عقود مكتملة',
                            data: {!! json_encode($completedContracts ?? []) !!},
                            backgroundColor: 'rgba(40, 199, 111, 0.85)',
                            borderColor: '#28c76f',
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
                        backgroundColor: ['#5f5bff', '#28c76f', '#36b9cc', '#ffa726', '#ff4d4f'],
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
