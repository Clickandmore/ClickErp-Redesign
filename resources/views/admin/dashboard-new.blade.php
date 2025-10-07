@extends('admin.layout.indexs.index')

@section("style")

<style>
    /* تصميم احترافي للوحة التحكم */
    .dashboard-header {
        background: linear-gradient(135deg, #6699ea 0%, #0c5460 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }

    .dashboard-header h1 {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .dashboard-header p {
        font-size: 16px;
        opacity: 0.9;
    }

    /* بطاقات الإحصائيات */
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    }

    .stat-card.primary::before { background: linear-gradient(180deg, #4e73df 0%, #224abe 100%); }
    .stat-card.success::before { background: linear-gradient(180deg, #1cc88a 0%, #13855c 100%); }
    .stat-card.info::before { background: linear-gradient(180deg, #36b9cc 0%, #258391 100%); }
    .stat-card.warning::before { background: linear-gradient(180deg, #f6c23e 0%, #dda20a 100%); }
    .stat-card.danger::before { background: linear-gradient(180deg, #e74a3b 0%, #be2617 100%); }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }

    .stat-icon.primary { background: rgba(78, 115, 223, 0.1); color: #4e73df; }
    .stat-icon.success { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .stat-icon.info { background: rgba(54, 185, 204, 0.1); color: #36b9cc; }
    .stat-icon.warning { background: rgba(246, 194, 62, 0.1); color: #f6c23e; }
    .stat-icon.danger { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #858796;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-change {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 20px;
    }

    .stat-change.positive {
        background: rgba(28, 200, 138, 0.1);
        color: #1cc88a;
    }

    .stat-change.negative {
        background: rgba(231, 74, 59, 0.1);
        color: #e74a3b;
    }

    /* قسم الرسوم البيانية */
    .chart-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .chart-title {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
    }

    /* جدول الأنشطة الأخيرة */
    .activity-table {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .activity-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 15px;
    }

    /* الأزرار السريعة */
    .quick-action {
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        text-decoration: none;
        color: inherit;
    }

    .quick-action i {
        font-size: 32px;
        margin-bottom: 10px;
        color: #667eea;
    }

    .quick-action-title {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
    }
</style>
@endsection

@section('page-title')
لوحة التحكم
@endsection

@section('content')

<!-- Header -->
<div class="dashboard-header">
    <h1>مرحباً بك، {{ auth()->guard('admin')->user()->name }}</h1>
    <p>لوحة التحكم الرئيسية - نظرة عامة على جميع الأنشطة</p>
</div>

<!-- إحصائيات العقود والمراحل -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-file-contract me-2"></i>العقود والمراحل</h4>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="stat-value">{{ $contractsStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي العقود</div>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ $contractsStats['thisMonth'] ?? 0 }} هذا الشهر
            </span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">{{ $contractsStats['completed'] ?? 0 }}</div>
            <div class="stat-label">عقود مكتملة</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value">{{ $contractsStats['pending'] ?? 0 }}</div>
            <div class="stat-label">عقود قيد التنفيذ</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="stat-value">{{ $contractsStats['underWarranty'] ?? 0 }}</div>
            <div class="stat-label">تحت الضمان</div>
        </div>
    </div>
</div>

<!-- إحصائيات السير الذاتية والطلبات -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-users me-2"></i>السير الذاتية والطلبات</h4>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-value">{{ $cvStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي السير الذاتية</div>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ $cvStats['available'] ?? 0 }} متاح
            </span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-value">{{ $ordersStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي الطلبات</div>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ $ordersStats['today'] ?? 0 }} اليوم
            </span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="stat-value">{{ $ordersStats['completed'] ?? 0 }}</div>
            <div class="stat-label">طلبات مكتملة</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card danger">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value">{{ $ordersStats['pending'] ?? 0 }}</div>
            <div class="stat-label">طلبات معلقة</div>
        </div>
    </div>
</div>

<!-- إحصائيات العملاء والإيواء -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-user-friends me-2"></i>العملاء والإيواء</h4>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value">{{ $clientsStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي العملاء</div>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ $clientsStats['newThisMonth'] ?? 0 }} جديد
            </span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-value">{{ $shelterStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي الإيواء</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-bed"></i>
            </div>
            <div class="stat-value">{{ $shelterStats['occupied'] ?? 0 }}</div>
            <div class="stat-label">غرف مشغولة</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-door-open"></i>
            </div>
            <div class="stat-value">{{ $shelterStats['available'] ?? 0 }}</div>
            <div class="stat-label">غرف متاحة</div>
        </div>
    </div>
</div>

<!-- إحصائيات المكاتب الخارجية والمبيعات -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-building me-2"></i>المكاتب الخارجية والمبيعات</h4>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-globe"></i>
            </div>
            <div class="stat-value">{{ $officesStats['total'] ?? 0 }}</div>
            <div class="stat-label">المكاتب الخارجية</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">{{ number_format($salesStats['totalRevenue'] ?? 0) }}</div>
            <div class="stat-label">إجمالي المبيعات</div>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> {{ $salesStats['growthPercent'] ?? 0 }}%
            </span>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value">{{ number_format($salesStats['thisMonth'] ?? 0) }}</div>
            <div class="stat-label">مبيعات الشهر</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-value">{{ $salesStats['conversionRate'] ?? 0 }}%</div>
            <div class="stat-label">معدل التحويل</div>
        </div>
    </div>
</div>

<!-- إحصائيات الموظفين -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-3"><i class="fas fa-user-tie me-2"></i>الموظفين</h4>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card primary">
            <div class="stat-icon primary">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-value">{{ $employeesStats['total'] ?? 0 }}</div>
            <div class="stat-label">إجمالي الموظفين</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card success">
            <div class="stat-icon success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-value">{{ $employeesStats['active'] ?? 0 }}</div>
            <div class="stat-label">موظفين نشطين</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card info">
            <div class="stat-icon info">
                <i class="fas fa-circle text-success"></i>
            </div>
            <div class="stat-value">{{ $employeesStats['online'] ?? 0 }}</div>
            <div class="stat-label">متصلين الآن</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card warning">
            <div class="stat-icon warning">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="stat-value">
                <small>مبيعات: {{ $employeesStats['departments']['sales'] ?? 0 }}</small><br>
                <small>دعم: {{ $employeesStats['departments']['support'] ?? 0 }}</small>
            </div>
            <div class="stat-label">الأقسام</div>
        </div>
    </div>
</div>

<!-- الرسوم البيانية -->
<div class="row mb-4">
    <!-- رسم بياني للعقود -->
    <div class="col-xl-8 col-lg-7">
        <div class="chart-card">
            <div class="chart-header">
                <h5 class="chart-title">إحصائيات العقود الشهرية</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary">عقود جديدة</span>
                    <span class="badge bg-success">عقود مكتملة</span>
                </div>
            </div>
            <div style="height: 250px; position: relative;">
                <canvas id="contractsChart"></canvas>
            </div>
            <!-- إحصائيات رقمية -->
            <div class="p-3 border-top bg-light">
                <div class="row text-center">
                    <div class="col-4">
                        <small class="text-muted d-block">إجمالي العقود</small>
                        <h5 class="mb-0 text-primary">{{ array_sum($newContracts ?? []) }}</h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">متوسط شهري</small>
                        <h5 class="mb-0 text-info">{{ $newContracts ? round(array_sum($newContracts) / count($newContracts)) : 0 }}</h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">نسبة الإنجاز</small>
                        <h5 class="mb-0 text-success">{{ ($newContracts && array_sum($newContracts) > 0) ? round((array_sum($completedContracts ?? []) / array_sum($newContracts)) * 100) : 0 }}%</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- توزيع الجنسيات -->
    <div class="col-xl-4 col-lg-5">
        <div class="chart-card">
            <div class="chart-header">
                <h5 class="chart-title">توزيع الجنسيات</h5>
                <small class="text-muted">إجمالي: {{ array_sum($nationalityData ?? []) }} عاملة</small>
            </div>
            <div style="height: 200px; position: relative;">
                <canvas id="nationalitiesChart"></canvas>
            </div>
            <!-- قائمة الجنسيات -->
            <div class="p-3 border-top bg-light" style="max-height: 150px; overflow-y: auto;">
                @if(isset($nationalityLabels) && isset($nationalityData))
                    @foreach($nationalityLabels as $index => $label)
                    @if(isset($nationalityData[$index]))
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">{{ $label }}</span>
                        <div>
                            <span class="badge bg-secondary">{{ $nationalityData[$index] }}</span>
                            <small class="text-muted">({{ array_sum($nationalityData) > 0 ? round(($nationalityData[$index] / array_sum($nationalityData)) * 100) : 0 }}%)</small>
                        </div>
                    </div>
                    @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<!-- تحليل الأداء الشهري -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>تحليل الأداء</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- الشهر الحالي -->
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                            <h6 class="text-muted">الشهر الحالي</h6>
                            <h3 class="text-primary mb-0">{{ $contractsStats['thisMonth'] ?? 0 }}</h3>
                            <small class="text-muted">عقد جديد</small>
                        </div>
                    </div>

                    <!-- هذا الأسبوع -->
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-calendar-week fa-2x text-success mb-2"></i>
                            <h6 class="text-muted">هذا الأسبوع</h6>
                            <h3 class="text-success mb-0">{{ $contractsStats['thisWeek'] ?? 0 }}</h3>
                            <small class="text-muted">عقد جديد</small>
                        </div>
                    </div>

                    <!-- اليوم -->
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                            <h6 class="text-muted">اليوم</h6>
                            <h3 class="text-warning mb-0">{{ $contractsStats['today'] ?? 0 }}</h3>
                            <small class="text-muted">عقد جديد</small>
                        </div>
                    </div>

                    <!-- تحت الضمان -->
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <i class="fas fa-shield-alt fa-2x text-info mb-2"></i>
                            <h6 class="text-muted">تحت الضمان</h6>
                            <h3 class="text-info mb-0">{{ $contractsStats['underWarranty'] ?? 0 }}</h3>
                            <small class="text-muted">عقد نشط</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- الأنشطة الأخيرة والإجراءات السريعة -->
<div class="row">
    <!-- الأنشطة الأخيرة -->
    <div class="col-xl-8 col-lg-7">
        <div class="activity-table">
            <h5 class="mb-4">الأنشطة الأخيرة</h5>

            @foreach($recentActivities ?? [] as $activity)
            <div class="activity-item">
                <div class="activity-icon bg-primary bg-opacity-10">
                    <i class="fas fa-{{ $activity['icon'] ?? 'circle' }} text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $activity['title'] }}</div>
                    <small class="text-muted">{{ $activity['time'] }}</small>
                </div>
                <div>
                    <span class="badge bg-{{ $activity['type'] ?? 'primary' }} bg-opacity-10 text-{{ $activity['type'] ?? 'primary' }}">
                        {{ $activity['status'] }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- الإجراءات السريعة -->
    <div class="col-xl-4 col-lg-5">
        <div class="mb-4">
            <h5 class="mb-4">إجراءات سريعة</h5>
            <div class="row g-3">
                <div class="col-6">
                    <a href="{{ route('RecruitmentContract.create') }}" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <div class="quick-action-title">عقد جديد</div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('orders.index') }}" class="quick-action">
                        <i class="fas fa-shopping-basket"></i>
                        <div class="quick-action-title">الطلبات</div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('clients.create') }}" class="quick-action">
                        <i class="fas fa-user-plus"></i>
                        <div class="quick-action-title">عميل جديد</div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('maids.create') }}" class="quick-action">
                        <i class="fas fa-id-card-alt"></i>
                        <div class="quick-action-title">سيرة ذاتية</div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('complaints.create') }}" class="quick-action">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="quick-action-title">شكوى جديدة</div>
                    </a>
                </div>
                <div class="col-6">
                    <a href="#" class="quick-action" onclick="alert('التقارير قيد التطوير'); return false;">
                        <i class="fas fa-chart-bar"></i>
                        <div class="quick-action-title">التقارير</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- رسم بياني للإيرادات -->
<div class="row mb-4">
    <div class="col-12">
        <div class="chart-card">
            <div class="chart-header">
                <h5 class="chart-title">الإيرادات الشهرية</h5>
            </div>
            <div class="chart-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- التقويم -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>التقويم والمواعيد</h4>
            </div>
            <div class="card-body">
                <div id="calendar"></div>
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
    // رسم بياني للعقود - محسن
    const contractsCtx = document.getElementById('contractsChart').getContext('2d');
    new Chart(contractsCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels ?? ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو']) !!},
            datasets: [{
                label: 'عقود جديدة',
                data: {!! json_encode($newContracts ?? [65, 59, 80, 81, 56, 55]) !!},
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: '#4e73df',
                borderWidth: 2,
                borderRadius: 8,
                barPercentage: 0.7
            }, {
                label: 'عقود مكتملة',
                data: {!! json_encode($completedContracts ?? [28, 48, 40, 19, 86, 27]) !!},
                backgroundColor: 'rgba(28, 200, 138, 0.8)',
                borderColor: '#1cc88a',
                borderWidth: 2,
                borderRadius: 8,
                barPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.9)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    rtl: true,
                    textDirection: 'rtl',
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' عقد';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    max: Math.max(...{!! json_encode($newContracts ?? [100]) !!}, ...{!! json_encode($completedContracts ?? [100]) !!}) * 1.2,
                    grid: {
                        borderDash: [3, 3],
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        },
                        stepSize: 10,
                        callback: function(value) {
                            return value;
                        }
                    }
                }
            }
        }
    });

    // رسم بياني للجنسيات - محسن
    const nationalitiesCtx = document.getElementById('nationalitiesChart').getContext('2d');
    new Chart(nationalitiesCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($nationalityLabels ?? ['الفلبين', 'إندونيسيا', 'بنغلاديش', 'الهند', 'أخرى']) !!},
            datasets: [{
                data: {!! json_encode($nationalityData ?? [35, 25, 20, 15, 5]) !!},
                backgroundColor: [
                    'rgba(78, 115, 223, 0.9)',
                    'rgba(28, 200, 138, 0.9)',
                    'rgba(54, 185, 204, 0.9)',
                    'rgba(246, 194, 62, 0.9)',
                    'rgba(231, 74, 59, 0.9)'
                ],
                borderColor: [
                    '#4e73df',
                    '#1cc88a',
                    '#36b9cc',
                    '#f6c23e',
                    '#e74a3b'
                ],
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.9)',
                    padding: 12,
                    rtl: true,
                    textDirection: 'rtl',
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed;
                            let sum = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / sum) * 100).toFixed(1);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // التقويم
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'ar',
        direction: 'rtl',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: {!! json_encode($calendarEvents ?? []) !!},
        eventClick: function(info) {
            if (info.event.extendedProps.url) {
                window.location.href = info.event.extendedProps.url;
            }
        },
        eventColor: function(event) {
            switch(event.extendedProps.type) {
                case 'success': return '#1cc88a';
                case 'warning': return '#f6c23e';
                case 'danger': return '#e74a3b';
                default: return '#4e73df';
            }
        },
        height: 600
    });
    calendar.render();

    // رسم بياني للإيرادات
    if (document.getElementById('revenueChart')) {
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartLabels ?? []) !!},
                datasets: [{
                    label: 'الإيرادات (ريال)',
                    data: {!! json_encode($revenue ?? []) !!},
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('ar-SA') + ' ريال';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('ar-SA') + ' ريال';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
