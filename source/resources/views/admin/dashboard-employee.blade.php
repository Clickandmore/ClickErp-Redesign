@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin/assets/css/dashboard-new.css') }}?v=13">
@endsection

@section('page-title')
    ملف الموظف
@endsection

@section('content')
    @php
        $employeeStatusText = ($employee->status ?? 'active') === 'suspended' ? 'موقوف مؤقتاً' : 'نشط';
        $employeeStatusClass = ($employee->status ?? 'active') === 'suspended' ? 'is-danger' : 'is-success';
        $employeeInitial = trim((string) mb_substr($employee->name ?? 'M', 0, 1));
        $overviewCards = [
            [
                'label' => 'إجمالي المتابعات',
                'value' => $employeeStats['handled_total'] ?? 0,
                'meta' => 'طلبات وعقود ضمن الفترة الحالية',
                'color' => 'var(--dash-chart-1)',
            ],
            [
                'label' => 'إجمالي الطلبات',
                'value' => $employeeStats['orders_total'] ?? 0,
                'meta' => 'جديد ' . number_format($employeeStats['orders_new'] ?? 0) . ' | تحت الإجراء ' . number_format($employeeStats['orders_under_process'] ?? 0),
                'color' => 'var(--dash-chart-2)',
            ],
            [
                'label' => 'إجمالي العقود',
                'value' => $employeeStats['contracts_total'] ?? 0,
                'meta' => 'جارية ' . number_format($employeeStats['contracts_current'] ?? 0) . ' | مكتملة ' . number_format($employeeStats['contracts_completed'] ?? 0),
                'color' => 'var(--dash-chart-3)',
            ],
            [
                'label' => 'نسبة التحويل',
                'value' => ($employeeStats['conversion_rate'] ?? 0) . '%',
                'meta' => 'من الطلبات إلى العقود داخل الفترة',
                'color' => 'var(--dash-chart-4)',
            ],
            [
                'label' => 'العقود تحت الضمان',
                'value' => $employeeStats['contracts_warranty_active'] ?? 0,
                'meta' => 'عقود تحتاج متابعة ما بعد الوصول',
                'color' => 'var(--warning)',
            ],
            [
                'label' => 'متوسط يومي',
                'value' => number_format((float) ($employeeStats['avg_daily_handled'] ?? 0), 1),
                'meta' => 'متوسط المعاملات اليومية ضمن الفترة',
                'color' => 'var(--success)',
            ],
        ];

        $ordersSortedStatuses = collect($ordersStatusBreakdown ?? [])->sortDesc();
        $contractsSortedStatuses = collect($contractsStatusBreakdown ?? [])->sortDesc();

        $orderStatusLabels = [
            \App\Enums\ingaz\OrderEnum::New->value => 'جديد',
            \App\Enums\ingaz\OrderEnum::UnderProcess->value => 'تحت الإجراء',
            \App\Enums\ingaz\OrderEnum::Contracted->value => 'تم التعاقد',
            \App\Enums\ingaz\OrderEnum::ContractedWithoutContract->value => 'تم التعاقد بدون عقد',
            \App\Enums\ingaz\OrderEnum::Completed->value => 'مكتملة',
            \App\Enums\ingaz\OrderEnum::Done->value => 'مكتملة',
            \App\Enums\ingaz\OrderEnum::Refused->value => 'مرفوضة',
            \App\Enums\ingaz\OrderEnum::Return->value => 'استرجاع',
            \App\Enums\ingaz\OrderEnum::CanceledBySystem->value => 'ملغاة آلياً',
        ];
    @endphp

    <div class="employee-profile-hero surface-card">
        <div class="employee-profile-main">
            <div class="employee-profile-breadcrumbs">
                <a href="{{ route('dashboard.index') }}">لوحة التحكم</a>
                <span>/</span>
                <span>ملف الموظف</span>
            </div>

            <div class="employee-profile-head">
                <div>
                    <p class="hero-eyebrow">لوحة أداء الموظف</p>
                    <h1>{{ $employee->name }}</h1>
                    <p>عرض تفصيلي للطلبات والعقود المنسوبة إلى هذا الموظف مع فلترة زمنية ورسوم بيانية وجداول تشغيلية.</p>
                </div>
                <div class="employee-avatar-badge">{{ $employeeInitial !== '' ? $employeeInitial : '#' . $employee->id }}</div>
            </div>

            <div class="employee-profile-meta">
                <span class="employee-profile-pill {{ $employeeStatusClass }}">{{ $employeeStatusText }}</span>
                <span class="employee-profile-pill">{{ $employeePrimaryRole ?: 'بدون دور' }}</span>
                @if ($employee->email)
                    <span class="employee-profile-pill">{{ $employee->email }}</span>
                @endif
                @if ($employee->formatted_contact_phone)
                    <span class="employee-profile-pill">{{ $employee->formatted_contact_phone }}</span>
                @endif
            </div>

            <div class="employee-profile-stats-row">
                <span class="employee-summary-pill">الإجمالي العام: {{ number_format($allTimeSummary['handled_total'] ?? 0) }}</span>
                <span class="employee-summary-pill">طلبات عامة: {{ number_format($allTimeSummary['orders_total'] ?? 0) }}</span>
                <span class="employee-summary-pill">عقود عامة: {{ number_format($allTimeSummary['contracts_total'] ?? 0) }}</span>
                <span class="employee-summary-pill">تحويل عام: {{ number_format($allTimeSummary['conversion_rate'] ?? 0) }}%</span>
                @if ($lastActivityAt)
                    <span class="employee-summary-pill">آخر نشاط: {{ $lastActivityAt->diffForHumans() }}</span>
                @endif
            </div>

            <div class="hero-quick-actions">
                <a href="{{ route('dashboard.index') }}"><i class="fas fa-chart-line ms-1"></i> العودة للوحة التحكم</a>
                <a href="{{ route('orders.index', ['admin_id' => $employee->id]) }}"><i class="fas fa-clipboard-list ms-1"></i> الطلبات</a>
                <a href="{{ route('RecruitmentContract.index', ['marketer_id' => $employee->id]) }}"><i class="fas fa-file-signature ms-1"></i> العقود</a>
            </div>
        </div>

        <div class="employee-filter-card">
            <div class="employee-filter-head">
                <div>
                    <small>الفترة الحالية</small>
                    <strong>{{ $dateRangeLabel }}</strong>
                </div>
                @if ($firstActivityAt)
                    <span class="employee-filter-note">أول نشاط {{ $firstActivityAt->translatedFormat('d F Y') }}</span>
                @endif
            </div>

            <div class="employee-range-pills">
                @foreach ($rangePresets as $rangeKey => $rangeLabel)
                    <a href="{{ route('dashboard.employees.show', ['employee' => $employee->id, 'range' => $rangeKey]) }}"
                        class="employee-range-pill {{ $selectedRangeKey === $rangeKey ? 'active' : '' }}">
                        {{ $rangeLabel }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('dashboard.employees.show', $employee->id) }}" class="employee-date-form">
                <input type="hidden" name="range" value="custom">
                <label class="contract-filter-field">
                    <span>من تاريخ</span>
                    <input type="date" name="from_date" value="{{ $startDate->format('Y-m-d') }}">
                </label>
                <label class="contract-filter-field">
                    <span>إلى تاريخ</span>
                    <input type="date" name="to_date" value="{{ $endDate->format('Y-m-d') }}">
                </label>
                <div class="contract-filter-actions">
                    <button type="submit" class="btn btn-primary">تطبيق الفلترة</button>
                    <a href="{{ route('dashboard.employees.show', $employee->id) }}" class="btn btn-outline-secondary">إعادة الضبط</a>
                </div>
            </form>
        </div>
    </div>

    <div class="employee-kpi-grid mt-4">
        @foreach ($overviewCards as $card)
            <div class="employee-kpi-card" style="--employee-kpi-accent: {{ $card['color'] }};">
                <small>{{ $card['label'] }}</small>
                <strong>{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</strong>
                <span>{{ $card['meta'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xxl-8">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">حركة الأداء</p>
                        <h4 class="mb-0">تدفق الطلبات والعقود</h4>
                    </div>
                    <small>
                        {{ $activityChart['mode'] === 'daily' ? 'عرض يومي' : ($activityChart['mode'] === 'monthly' ? 'عرض شهري' : 'عرض سنوي') }}
                    </small>
                </div>
                <div class="chart-holder employee-chart-holder">
                    <canvas id="employeeActivityChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xxl-4">
            <div class="panel-card h-100 employee-side-panel">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">ملخص سريع</p>
                        <h4 class="mb-0">نقاط التركيز</h4>
                    </div>
                    <small>{{ $employeePrimaryRole ?: 'بدون دور' }}</small>
                </div>

                <div class="employee-focus-list">
                    <div class="employee-focus-card">
                        <div class="employee-focus-icon is-orders">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="employee-focus-copy">
                            <strong>الطلبات المفتوحة</strong>
                            <small>جديد + تحت الإجراء</small>
                        </div>
                        <span>{{ number_format(($employeeStats['orders_new'] ?? 0) + ($employeeStats['orders_under_process'] ?? 0)) }}</span>
                    </div>

                    <div class="employee-focus-card">
                        <div class="employee-focus-icon is-contracts">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="employee-focus-copy">
                            <strong>العقود الجارية</strong>
                            <small>العقود التي ما زالت في المسار التشغيلي</small>
                        </div>
                        <span>{{ number_format($employeeStats['contracts_current'] ?? 0) }}</span>
                    </div>

                    <div class="employee-focus-card">
                        <div class="employee-focus-icon is-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="employee-focus-copy">
                            <strong>الإغلاقات</strong>
                            <small>طلبات وعقود مكتملة ضمن الفترة</small>
                        </div>
                        <span>{{ number_format(($employeeStats['orders_completed'] ?? 0) + ($employeeStats['contracts_completed'] ?? 0)) }}</span>
                    </div>

                    <div class="employee-focus-card">
                        <div class="employee-focus-icon is-warning">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="employee-focus-copy">
                            <strong>ضمانات نشطة</strong>
                            <small>عقود تحتاج متابعة ما بعد الوصول</small>
                        </div>
                        <span>{{ number_format($employeeStats['contracts_warranty_active'] ?? 0) }}</span>
                    </div>
                </div>

                <div class="employee-mini-summary">
                    <div>
                        <small>الإجمالي العام</small>
                        <strong>{{ number_format($allTimeSummary['handled_total'] ?? 0) }}</strong>
                    </div>
                    <div>
                        <small>طلبات عامة</small>
                        <strong>{{ number_format($allTimeSummary['orders_total'] ?? 0) }}</strong>
                    </div>
                    <div>
                        <small>عقود عامة</small>
                        <strong>{{ number_format($allTimeSummary['contracts_total'] ?? 0) }}</strong>
                    </div>
                    <div>
                        <small>تحويل عام</small>
                        <strong>{{ number_format($allTimeSummary['conversion_rate'] ?? 0) }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">حالات الطلبات</p>
                        <h4 class="mb-0">توزيع الطلبات</h4>
                    </div>
                    <small>{{ number_format($employeeStats['orders_total'] ?? 0) }} طلب</small>
                </div>

                @if (array_sum($ordersStatusChart['data'] ?? []) > 0)
                    <div class="chart-holder employee-status-chart">
                        <canvas id="employeeOrdersStatusChart"></canvas>
                    </div>
                @else
                    <div class="empty-state py-3">لا توجد طلبات ضمن الفترة المحددة.</div>
                @endif

                @if ($ordersSortedStatuses->count())
                    <div class="employee-status-chip-list">
                        @foreach ($ordersSortedStatuses as $status => $total)
                            <span class="employee-metric-chip chip-orders">{{ $orderStatusLabels[$status] ?? $status }}:
                                {{ number_format((int) $total) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-head">
                    <div>
                        <p class="section-eyebrow">حالات العقود</p>
                        <h4 class="mb-0">توزيع العقود</h4>
                    </div>
                    <small>{{ number_format($employeeStats['contracts_total'] ?? 0) }} عقد</small>
                </div>

                @if (array_sum($contractsStatusChart['data'] ?? []) > 0)
                    <div class="chart-holder employee-status-chart">
                        <canvas id="employeeContractsStatusChart"></canvas>
                    </div>
                @else
                    <div class="empty-state py-3">لا توجد عقود ضمن الفترة المحددة.</div>
                @endif

                @if ($contractsSortedStatuses->count())
                    <div class="employee-status-chip-list">
                        @foreach ($contractsSortedStatuses as $status => $total)
                            <span class="employee-metric-chip chip-contracts">{{ getContractStatus($status) }}:
                                {{ number_format((int) $total) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="panel-card mt-4">
        <div class="employee-table-head">
            <div>
                <p class="section-eyebrow">قائمة الطلبات</p>
                <h4 class="mb-0">الطلبات المرتبطة بالموظف</h4>
            </div>
            <a href="{{ route('orders.index', ['admin_id' => $employee->id]) }}" class="btn btn-outline-primary btn-sm">فتح صفحة الطلبات</a>
        </div>

        <div class="table-responsive employee-table-wrap">
            <table class="table align-middle mb-0 employee-activity-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>الخدمة</th>
                        <th>المسؤول</th>
                        <th>تاريخ الإنشاء</th>
                        <th>العقود المرتبطة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order->id) }}" class="employee-row-link">#{{ $order->id }}</a>
                            </td>
                            <td>{{ $order->user->name ?? 'غير محدد' }}</td>
                            <td>
                                <span class="employee-inline-status">
                                    {{ $orderStatusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td>{{ $order->jobType->title ?? 'استقدام' }}</td>
                            <td>{{ $order->resolveResponsibleAdmin()?->name ?? 'غير محدد' }}</td>
                            <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
                            <td>{{ number_format($order->contracts->count()) }}</td>
                            <td class="text-end">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-light text-dark">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state py-4">لا توجد طلبات ضمن الفترة المحددة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="employee-pagination">
                {{ $orders->appends(request()->except('orders_page'))->links('vendor.pagination.bootstrap-4') }}
            </div>
        @endif
    </div>

    <div class="panel-card mt-4">
        <div class="employee-table-head">
            <div>
                <p class="section-eyebrow">قائمة العقود</p>
                <h4 class="mb-0">العقود المرتبطة بالموظف</h4>
            </div>
            <a href="{{ route('RecruitmentContract.index', ['marketer_id' => $employee->id]) }}"
                class="btn btn-outline-success btn-sm">فتح صفحة العقود</a>
        </div>

        <div class="table-responsive employee-table-wrap">
            <table class="table align-middle mb-0 employee-activity-table">
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>الحالة</th>
                        <th>العاملة</th>
                        <th>الضمان</th>
                        <th>تاريخ الإنشاء</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contracts as $contract)
                        <tr>
                            <td>
                                <a href="{{ route('RecruitmentContract.show', $contract->id) }}" class="employee-row-link">#{{ $contract->id }}</a>
                            </td>
                            <td>{{ $contract->user->name ?? 'غير محدد' }}</td>
                            <td>
                                <span class="employee-inline-status">{{ getContractStatus($contract->status) }}</span>
                            </td>
                            <td>{{ optional($contract->maid)->name ?: optional($contract->maid)->passport_num ?: 'غير محدد' }}</td>
                            <td>{{ $contract->warranty_status_text ?? 'غير محدد' }}</td>
                            <td>{{ optional($contract->created_at)->format('Y-m-d') }}</td>
                            <td class="text-end">
                                <a href="{{ route('RecruitmentContract.show', $contract->id) }}"
                                    class="btn btn-sm btn-light text-dark">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state py-4">لا توجد عقود ضمن الفترة المحددة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($contracts->hasPages())
            <div class="employee-pagination">
                {{ $contracts->appends(request()->except('contracts_page'))->links('vendor.pagination.bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            const rootStyles = getComputedStyle(document.documentElement);
            const getCssVar = (name, fallback) => {
                const value = rootStyles.getPropertyValue(name).trim();
                return value || fallback;
            };

            const palette = [
                getCssVar('--dash-chart-1', '#222940'),
                getCssVar('--dash-chart-2', '#5c6273'),
                getCssVar('--dash-chart-3', '#97bf5a'),
                getCssVar('--dash-chart-4', '#a4bf7a'),
                getCssVar('--dash-chart-5', '#7e8c5a'),
                getCssVar('--success', '#97bf5a'),
                getCssVar('--warning', '#a4bf7a'),
                getCssVar('--danger', '#5c6273')
            ];

            const toRgba = (hex, alpha) => {
                const normalized = hex.replace('#', '').trim();
                if (normalized.length !== 6) return hex;
                const value = parseInt(normalized, 16);
                const r = (value >> 16) & 255;
                const g = (value >> 8) & 255;
                const b = value & 255;
                return `rgba(${r}, ${g}, ${b}, ${alpha})`;
            };

            const activityCanvas = document.getElementById('employeeActivityChart');
            if (activityCanvas) {
                new Chart(activityCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($activityChart['labels'] ?? []),
                        datasets: [{
                                type: 'bar',
                                label: 'الطلبات',
                                data: @json($activityChart['orders'] ?? []),
                                borderRadius: 12,
                                backgroundColor: toRgba(palette[0], 0.72),
                                borderColor: palette[0],
                                borderWidth: 1
                            },
                            {
                                type: 'line',
                                label: 'العقود',
                                data: @json($activityChart['contracts'] ?? []),
                                tension: 0.35,
                                fill: true,
                                backgroundColor: toRgba(palette[2], 0.16),
                                borderColor: palette[2],
                                pointBackgroundColor: palette[2],
                                pointBorderColor: '#fff',
                                pointRadius: 4
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
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
                                }
                            }
                        }
                    }
                });
            }

            const createDoughnut = (canvasId, labels, values) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas || !values.length) return;

                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: labels.map((_, index) => palette[index % palette.length]),
                            borderColor: '#ffffff',
                            borderWidth: 4,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '64%',
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            };

            createDoughnut(
                'employeeOrdersStatusChart',
                @json($ordersStatusChart['labels'] ?? []),
                @json($ordersStatusChart['data'] ?? [])
            );

            createDoughnut(
                'employeeContractsStatusChart',
                @json($contractsStatusChart['labels'] ?? []),
                @json($contractsStatusChart['data'] ?? [])
            );
        });
    </script>
@endsection
