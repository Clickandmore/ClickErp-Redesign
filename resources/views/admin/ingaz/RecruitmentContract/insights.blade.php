@extends('admin.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .insights-page {
            --series-1: #2a78d6;
            --series-2: #eb6834;
            --series-3: #1baf7a;
            --ink: #0f172a;
            --ink-2: #475569;
            --ink-3: #64748b;
            --line: #e5e7eb;
            --panel: #fff;
        }

        .insights-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 20px 22px;
            margin-bottom: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #312e81, #1d4ed8 55%, #0ea5e9);
            color: #fff;
        }

        .insights-header h3 { color: #fff; font-weight: 800; margin: 0 0 4px; }
        .insights-header small { color: rgba(255, 255, 255, 0.8); }

        .insights-header .btn-back {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            border-radius: 999px;
            padding: 7px 16px;
            font-weight: 600;
        }

        .insights-header .btn-back:hover { background: rgba(255, 255, 255, 0.25); color: #fff; }

        .insights-panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
            height: 100%;
        }

        .insights-panel-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 12px;
        }

        .insights-panel-title h6 { margin: 0; font-weight: 700; color: var(--ink); }
        .insights-panel-title small { color: var(--ink-3); }

        .insights-filters { box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06); }
        .insights-filters .form-label { font-size: 0.8rem; color: var(--ink-2); margin-bottom: 4px; }

        .period-shortcuts { display: flex; flex-wrap: wrap; gap: 6px; }

        .period-shortcuts button,
        .date-type-switch button {
            border: 1px solid var(--line);
            background: #f8fafc;
            color: var(--ink-2);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.8rem;
        }

        .period-shortcuts button.active,
        .date-type-switch button.active {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }

        .date-type-switch { display: inline-flex; flex-wrap: wrap; gap: 6px; }

        .extra-filters-note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 999px;
            padding: 3px 4px 3px 12px;
        }

        .kpi-card {
            display: flex;
            flex-direction: column;
            gap: 6px;
            height: 100%;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            color: inherit;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        a.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08); color: inherit; }

        .kpi-card-top { display: flex; align-items: center; justify-content: space-between; }

        .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--tone-soft, #f1f5f9);
            color: var(--tone, #334155);
        }

        .kpi-label { font-size: 0.85rem; color: var(--ink-2); }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--ink); line-height: 1.1; }
        .kpi-hint { font-size: 0.75rem; color: var(--ink-3); }

        .tone-slate { --tone: #334155; --tone-soft: #e2e8f0; }
        .tone-indigo { --tone: #4f46e5; --tone-soft: #e0e7ff; }
        .tone-green { --tone: #16a34a; --tone-soft: #dcfce7; }
        .tone-red { --tone: #dc2626; --tone-soft: #fee2e2; }
        .tone-orange { --tone: #c2410c; --tone-soft: #ffedd5; }
        .tone-sky { --tone: #0284c7; --tone-soft: #e0f2fe; }
        .tone-teal { --tone: #0d9488; --tone-soft: #ccfbf1; }

        .arrival-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
        }

        .arrival-card {
            --tone: #334155;
            --tone-soft: #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 14px 16px;
            border: 1px solid var(--line);
            border-top: 4px solid var(--tone);
            border-radius: 14px;
            background: #fff;
            color: inherit;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .arrival-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0, 0, 0, 0.07); color: inherit; }

        .arrival-card-success { --tone: #16a34a; --tone-soft: #dcfce7; }
        .arrival-card-info { --tone: #2563eb; --tone-soft: #dbeafe; }
        .arrival-card-danger { --tone: #dc2626; --tone-soft: #fee2e2; }
        .arrival-card-warning { --tone: #d97706; --tone-soft: #fef3c7; }
        .arrival-card-slate { --tone: #475569; --tone-soft: #e2e8f0; }

        .arrival-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }

        .arrival-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--tone-soft);
            color: var(--tone);
        }

        .arrival-card-value { font-size: 1.75rem; font-weight: 800; color: var(--ink); line-height: 1; }
        .arrival-card-title { font-weight: 700; color: #1f2937; }
        .arrival-card-hint { font-size: 0.78rem; color: #6b7280; }

        .arrival-card-trend {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed var(--line);
            font-size: 0.78rem;
            color: #4b5563;
        }

        .arrival-card-trend.is-up { color: #15803d; }
        .arrival-card-trend.is-down { color: #b91c1c; }

        .arrival-footer { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }

        .arrival-footer a {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 0.8rem;
            color: #374151;
            text-decoration: none;
        }

        .arrival-footer a:hover { border-color: rgba(13, 110, 253, 0.6); color: #0d6efd; }
        .arrival-footer a.warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }

        .chart-box { min-height: 300px; }
        .chart-empty { display: flex; align-items: center; justify-content: center; min-height: 260px; color: var(--ink-3); }

        .insights-loading .kpi-value,
        .insights-loading .arrival-card-value { opacity: 0.35; }
    </style>
@endsection

@section('page-title')
    إحصائيات عقود الاستقدام
@endsection

@section('content')
    @php
        $indexRoute = route($companyContractsMode ? 'RecruitmentContract.company.index' : 'RecruitmentContract.index');
        $monthName = now()->locale('ar')->translatedFormat('F');
        $stageLabels = \App\Support\RecruitmentContractStageLabels::labels();
        $stageLabel = fn ($status) => $stageLabels[$status][app()->getLocale() === 'en' ? 'en' : 'ar'] ?? $status;
        $stageKeys = ['pending_maid_selection', 'new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival', 'completed', 'refunded', 'back'];
        $arrivalCards = [
            'arrived_month' => ['tone' => 'success', 'icon' => 'fa-plane-arrival', 'title' => 'وصلت هذا الشهر', 'hint' => 'وصول فعلي منذ 1 ' . $monthName],
            'expected_month' => ['tone' => 'info', 'icon' => 'fa-calendar-check', 'title' => 'ستصل هذا الشهر', 'hint' => 'رحلات مجدولة من اليوم حتى نهاية ' . $monthName],
            'delayed' => ['tone' => 'danger', 'icon' => 'fa-triangle-exclamation', 'title' => 'متأخرة عن موعد الرحلة', 'hint' => 'مضى تاريخ الرحلة ولم يُسجَّل الوصول'],
            'waiting' => ['tone' => 'warning', 'icon' => 'fa-hourglass-half', 'title' => 'بانتظار الوصول', 'hint' => 'كل العقود التي لم تصل بعد'],
            'undelivered' => ['tone' => 'slate', 'icon' => 'fa-handshake-slash', 'title' => 'وصلت دون تسليم موثق', 'hint' => 'تراكمي — غياب التوثيق لا يعني عدم التسليم'],
        ];
    @endphp

    <div class="insights-page">
        <div class="insights-header">
            <div>
                <h3 class="h4"><i class="fas fa-chart-pie me-2"></i>إحصائيات عقود الاستقدام</h3>
                <small>مراحل العقود، الوصول والتسليم، والاتجاه الشهري — كل الأرقام تتبع الفلاتر أدناه</small>
            </div>
            <a href="{{ $indexRoute }}" class="btn btn-back" id="backToContracts">
                <i class="fas fa-arrow-right me-1"></i> العودة إلى العقود
            </a>
        </div>

        {{-- الفلاتر --}}
        <div class="insights-panel insights-filters mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-6">
                    <label class="form-label d-block">نوع التاريخ</label>
                    <div class="date-type-switch" role="radiogroup" aria-label="نوع التاريخ">
                        @foreach (['contract' => 'تاريخ العقد', 'created' => 'تاريخ إنشاء السجل', 'arrival' => 'الوصول الفعلي', 'delivery' => 'التسليم الموثق'] as $key => $label)
                            <button type="button" role="radio" data-value="{{ $key }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 col-xl-6">
                    <label class="form-label d-block">فترات سريعة</label>
                    <div class="period-shortcuts">
                        <button type="button" data-period="month">هذا الشهر</button>
                        <button type="button" data-period="last_month">الشهر السابق</button>
                        <button type="button" data-period="3m">آخر 3 أشهر</button>
                        <button type="button" data-period="year">هذه السنة</button>
                        <button type="button" data-period="all">كل الفترات</button>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fDateFrom" class="form-label">من</label>
                    <input type="date" id="fDateFrom" class="form-control form-control-sm" data-param="date_from">
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fDateTo" class="form-label">إلى</label>
                    <input type="date" id="fDateTo" class="form-control form-control-sm" data-param="date_to">
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fNationality" class="form-label">جنسية العاملة</label>
                    <select id="fNationality" class="form-select form-select-sm" data-param="nationality">
                        <option value="">الكل</option>
                        @foreach ($filterOptions['nationalities'] ?? [] as $nationality)
                            <option value="{{ $nationality->sub_title }}">{{ $nationality->sub_title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fOffice" class="form-label">المكتب الخارجي</label>
                    <select id="fOffice" class="form-select form-select-sm" data-param="office_id">
                        <option value="">الكل</option>
                        @foreach ($filterOptions['offices'] ?? [] as $office)
                            <option value="{{ $office->id }}">{{ $office->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fMarketer" class="form-label">المسوق</label>
                    <select id="fMarketer" class="form-select form-select-sm" data-param="marketer_id">
                        <option value="">الكل</option>
                        @foreach ($filterOptions['marketers'] ?? [] as $marketer)
                            <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <label for="fRequestType" class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                    <select id="fRequestType" class="form-select form-select-sm" data-param="request_type">
                        <option value="">الكل</option>
                        @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @include('admin.partials.branch-filter', [
                    'id' => 'fBranch',
                    'containerClass' => 'col-6 col-md-4 col-xl',
                    'selectClass' => 'form-select form-select-sm',
                ])
                <div class="w-100 m-0"></div>
                <div class="col-12 d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="extra-filters-note d-none" id="extraFiltersNote">
                        <i class="fas fa-filter"></i>
                        <span id="extraFiltersText"></span>
                        <button type="button" class="btn btn-sm btn-link p-0 px-2" id="clearExtraFilters">إزالتها</button>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="resetFilters">
                        <i class="fas fa-rotate-left me-1"></i> إعادة تعيين الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <div id="insightsBody">
            {{-- مؤشرات رئيسية --}}
            <div class="row g-3 mb-4">
                @foreach ([
                    ['key' => 'total', 'label' => 'جميع العقود', 'icon' => 'fa-list-check', 'tone' => 'slate', 'status' => ''],
                    ['key' => 'current_total', 'label' => 'العقود السارية', 'icon' => 'fa-spinner', 'tone' => 'indigo', 'status' => 'current'],
                    ['key' => 'completed_total', 'label' => 'العقود المكتملة', 'icon' => 'fa-circle-check', 'tone' => 'green', 'status' => 'completed_stage'],
                    ['key' => 'stage_back', 'label' => 'العقود المرتجعة', 'icon' => 'fa-arrow-rotate-left', 'tone' => 'red', 'status' => 'back'],
                ] as $kpi)
                    <div class="col-6 col-xl-2">
                        <a class="kpi-card tone-{{ $kpi['tone'] }} index-link" href="#" data-status="{{ $kpi['status'] }}">
                            <span class="kpi-card-top">
                                <span class="kpi-label">{{ $kpi['label'] }}</span>
                                <span class="kpi-icon"><i class="fas {{ $kpi['icon'] }}"></i></span>
                            </span>
                            <span class="kpi-value" data-stat-key="{{ $kpi['key'] }}">—</span>
                        </a>
                    </div>
                @endforeach
                <div class="col-6 col-xl-2">
                    <div class="kpi-card tone-sky">
                        <span class="kpi-card-top">
                            <span class="kpi-label">متوسط مدة الوصول</span>
                            <span class="kpi-icon"><i class="fas fa-stopwatch"></i></span>
                        </span>
                        <span class="kpi-value" id="kpiContractToArrival">—</span>
                        <span class="kpi-hint">يوم من تاريخ العقد حتى الوصول</span>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="kpi-card tone-teal">
                        <span class="kpi-card-top">
                            <span class="kpi-label">متوسط مدة التسليم</span>
                            <span class="kpi-icon"><i class="fas fa-handshake"></i></span>
                        </span>
                        <span class="kpi-value" id="kpiArrivalToDelivery">—</span>
                        <span class="kpi-hint">يوم من الوصول حتى التسليم الموثق</span>
                    </div>
                </div>
            </div>

            {{-- متابعة الوصول والتسليم --}}
            <section class="insights-panel mb-4" aria-labelledby="arrivalPanelTitle">
                <div class="insights-panel-title">
                    <div>
                        <h6 id="arrivalPanelTitle"><i class="fas fa-plane-arrival me-2"></i>متابعة الوصول والتسليم — {{ $monthName }} {{ now()->year }}</h6>
                        <small>كل بطاقة تعدّ العقود ضمن الفلاتر الحالية، والضغط عليها يفتح عقودها في الجدول.</small>
                    </div>
                </div>
                <div class="arrival-cards">
                    @foreach ($arrivalCards as $key => $card)
                        <a href="#" class="arrival-card arrival-card-{{ $card['tone'] }} index-link" data-arrival-scope="{{ $key }}">
                            <span class="arrival-card-top">
                                <span class="arrival-card-icon"><i class="fas {{ $card['icon'] }}"></i></span>
                                <span class="arrival-card-value" data-stat-key="operations_{{ $key }}">—</span>
                            </span>
                            <span class="arrival-card-title">{{ $card['title'] }}</span>
                            <span class="arrival-card-hint">{{ $card['hint'] }}</span>
                            @if ($key === 'arrived_month')
                                <span class="arrival-card-trend" id="arrivalPeriodComparison" role="status">جارٍ المقارنة بالشهر السابق…</span>
                            @endif
                        </a>
                    @endforeach
                </div>
                <div class="arrival-footer">
                    <a href="#" class="index-link" data-arrival-scope="arrived">
                        <i class="fas fa-layer-group"></i> إجمالي الواصلين (كل الفترات): <strong data-stat-key="operations_arrived">—</strong>
                    </a>
                    <a href="#" class="index-link" data-arrival-scope="unscheduled">
                        <i class="fas fa-calendar-xmark"></i> بانتظار الوصول بلا موعد رحلة: <strong data-stat-key="operations_unscheduled">—</strong>
                    </a>
                    <a href="#" class="index-link warn" data-arrival-scope="missing">
                        <i class="fas fa-circle-exclamation"></i> وصلت دون تاريخ وصول صالح (تحتاج تصحيح): <strong data-stat-key="operations_missing">—</strong>
                    </a>
                </div>
            </section>

            {{-- الرسوم البيانية --}}
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="insights-panel">
                        <div class="insights-panel-title">
                            <div>
                                <h6><i class="fas fa-chart-line me-2"></i>الاتجاه الشهري</h6>
                                <small id="trendHint">عقود جديدة (بتاريخ العقد) مقابل الوصول الفعلي والتسليم الموثق لكل شهر</small>
                            </div>
                        </div>
                        <div id="trendChart" class="chart-box"></div>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="insights-panel">
                        <div class="insights-panel-title">
                            <div>
                                <h6><i class="fas fa-route me-2"></i>توزيع العقود على المراحل</h6>
                                <small>اضغط على أي عمود لعرض عقود المرحلة</small>
                            </div>
                        </div>
                        <div id="stagesChart" class="chart-box"></div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="insights-panel">
                        <div class="insights-panel-title">
                            <div>
                                <h6><i class="fas fa-shield-halved me-2"></i>حالة الضمان</h6>
                                <small>للعقود الواصلة والمكتملة</small>
                            </div>
                        </div>
                        <div id="warrantyChart" class="chart-box"></div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="insights-panel">
                        <div class="insights-panel-title"><h6><i class="fas fa-flag me-2"></i>أعلى الجنسيات</h6></div>
                        <div id="nationalitiesChart" class="chart-box"></div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="insights-panel">
                        <div class="insights-panel-title"><h6><i class="fas fa-building me-2"></i>أعلى المكاتب الخارجية</h6></div>
                        <div id="officesChart" class="chart-box"></div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="insights-panel">
                        <div class="insights-panel-title"><h6><i class="fas fa-user-tie me-2"></i>أعلى المسوقين</h6></div>
                        <div id="marketersChart" class="chart-box"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
    <script>
        $(function () {
            const indexUrl = @json($indexRoute);
            const statisticsUrl = @json(route('RecruitmentContract.statistics'));
            const insightsDataUrl = @json(route('RecruitmentContract.insights.data'));
            const stageKeys = @json($stageKeys);
            const stageNames = @json(collect($stageKeys)->mapWithKeys(fn ($key) => [$key => $stageLabel($key)]));
            const COLORS = ['#2a78d6', '#eb6834', '#1baf7a'];
            const FONT = getComputedStyle(document.body).fontFamily;

            // فلاتر لها حقول في هذه الشاشة؛ أي فلتر آخر قادم من شاشة العقود يُمرَّر كما هو ويُعرض كملاحظة.
            const formParams = ['date_type', 'date_from', 'date_to', 'nationality', 'office_id', 'marketer_id', 'request_type', 'branch_id'];
            const ignoredParams = ['all_contracts', 'arrival_scope'];
            const url = new URL(window.location.href);
            let dateType = url.searchParams.get('date_type') || 'contract';
            $('[data-param]').each(function () {
                const value = url.searchParams.get($(this).data('param'));
                if (value !== null) $(this).val(value);
            });
            if ($('#fBranch').length && url.searchParams.has('branch_id')) $('#fBranch').val(url.searchParams.get('branch_id'));

            function extraParams() {
                const extra = {};
                new URL(window.location.href).searchParams.forEach(function (value, key) {
                    if (value !== '' && !formParams.includes(key) && !ignoredParams.includes(key)) extra[key] = value;
                });
                return extra;
            }

            function currentParams() {
                const params = Object.assign({}, extraParams(), {date_type: dateType});
                $('[data-param]').each(function () {
                    const value = $(this).val();
                    if (value) params[$(this).data('param')] = value;
                });
                if ($('#fBranch').length && $('#fBranch').val()) params.branch_id = $('#fBranch').val();
                return params;
            }

            function syncUrl(params) {
                const next = new URL(window.location.pathname, window.location.origin);
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== '' && value !== null && !(key === 'date_type' && value === 'contract')) next.searchParams.set(key, value);
                });
                window.history.replaceState({}, '', next.toString());

                const extra = Object.keys(extraParams()).filter(key => key !== 'company_contracts');
                $('#extraFiltersNote').toggleClass('d-none', extra.length === 0);
                $('#extraFiltersText').text('مطبّق أيضاً ' + extra.length + ' فلتر من شاشة العقود (' + extra.join('، ') + ')');
                $('.date-type-switch button').each(function () {
                    const active = $(this).data('value') === dateType;
                    $(this).toggleClass('active', active).attr('aria-checked', active ? 'true' : 'false');
                });
                $('.period-shortcuts button').removeClass('active');
                const shortcut = Object.entries(periods()).find(([, range]) => range[0] === ($('#fDateFrom').val() || '') && range[1] === ($('#fDateTo').val() || ''));
                if (shortcut) $('.period-shortcuts button[data-period="' + shortcut[0] + '"]').addClass('active');
            }

            function linkTo(extra) {
                const target = new URL(indexUrl, window.location.origin);
                Object.entries(Object.assign(currentParams(), extra)).forEach(([key, value]) => {
                    if (value !== '' && value !== null && value !== undefined) target.searchParams.set(key, value);
                });
                return target.toString();
            }

            function refreshLinks() {
                $('#backToContracts').attr('href', linkTo({}));
                $('.index-link').each(function () {
                    const extra = {};
                    if ($(this).data('arrivalScope')) extra.arrival_scope = $(this).data('arrivalScope');
                    if ($(this).is('[data-status]')) extra.status = $(this).data('status');
                    $(this).attr('href', linkTo(extra));
                });
            }

            function ymd(date) {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            }

            function periods() {
                const now = new Date();
                return {
                    month: [ymd(new Date(now.getFullYear(), now.getMonth(), 1)), ymd(now)],
                    last_month: [ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1)), ymd(new Date(now.getFullYear(), now.getMonth(), 0))],
                    '3m': [ymd(new Date(now.getFullYear(), now.getMonth() - 2, 1)), ymd(now)],
                    year: [ymd(new Date(now.getFullYear(), 0, 1)), ymd(now)],
                    all: ['', ''],
                };
            }

            const charts = {};
            function render(id, options) {
                if (charts[id]) charts[id].destroy();
                const base = {
                    chart: {fontFamily: FONT, toolbar: {show: false}, animations: {speed: 300}},
                    dataLabels: {enabled: false},
                    grid: {borderColor: '#eef2f7', strokeDashArray: 3},
                    tooltip: {theme: 'light'},
                    noData: {text: 'لا توجد بيانات ضمن الفلاتر الحالية'},
                };
                charts[id] = new ApexCharts(document.getElementById(id), $.extend(true, base, options));
                charts[id].render();
            }

            function barChart(id, rows, color) {
                render(id, {
                    chart: {type: 'bar', height: 300},
                    series: [{name: 'عدد العقود', data: rows.map(row => row.total)}],
                    xaxis: {categories: rows.map(row => row.label)},
                    plotOptions: {bar: {horizontal: true, borderRadius: 4, borderRadiusApplication: 'end', barHeight: '60%'}},
                    colors: [color],
                    dataLabels: {enabled: true, style: {colors: ['#0f172a'], fontWeight: 600}, offsetX: 18},
                });
            }

            let pending = null;
            function load() {
                const params = currentParams();
                syncUrl(params);
                refreshLinks();
                if (pending) pending.forEach(request => request.abort());
                $('#insightsBody').addClass('insights-loading');

                const stats = $.get(statisticsUrl, params);
                const insights = $.get(insightsDataUrl, params);
                pending = [stats, insights];

                stats.done(function (response) {
                    const data = response.data || {};
                    $('[data-stat-key]').each(function () {
                        const value = data[$(this).data('statKey')];
                        $(this).text(value === undefined ? 0 : Number(value).toLocaleString('en'));
                    });

                    const comparison = data.arrival_comparison;
                    if (comparison) {
                        const diff = comparison.difference;
                        const change = comparison.percentage === null ? (diff > 0 ? '+' : '') + diff : (diff > 0 ? '+' : '') + comparison.percentage + '%';
                        $('#arrivalPeriodComparison')
                            .removeClass('is-up is-down').addClass(diff > 0 ? 'is-up' : (diff < 0 ? 'is-down' : ''))
                            .attr('title', 'من ' + comparison.previous_from + ' إلى ' + comparison.previous_to)
                            .html('<i class="fas ' + (diff > 0 ? 'fa-arrow-trend-up' : (diff < 0 ? 'fa-arrow-trend-down' : 'fa-equals')) + '"></i> '
                                + change + ' عن نفس الفترة من الشهر السابق (' + comparison.previous + ')');
                    }

                    render('stagesChart', {
                        chart: {
                            type: 'bar', height: 320,
                            events: {
                                dataPointSelection: function (event, context, config) {
                                    window.location.href = linkTo({status: stageKeys[config.dataPointIndex]});
                                },
                            },
                        },
                        series: [{name: 'عدد العقود', data: stageKeys.map(key => data['stage_' + key] || 0)}],
                        xaxis: {categories: stageKeys.map(key => stageNames[key])},
                        plotOptions: {bar: {borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '55%'}},
                        colors: [COLORS[0]],
                        dataLabels: {enabled: true, offsetY: -18, style: {colors: ['#0f172a'], fontWeight: 600}},
                        states: {hover: {filter: {type: 'darken', value: 0.85}}},
                    });

                    const warranty = [data.warranty_active || 0, data.warranty_expired || 0, data.warranty_not_applicable || 0];
                    render('warrantyChart', {
                        chart: {type: 'donut', height: 320},
                        series: warranty.some(Boolean) ? warranty : [],
                        labels: ['داخل الضمان', 'منتهي الضمان', 'بدون ضمان'],
                        colors: COLORS,
                        legend: {position: 'bottom'},
                        stroke: {width: 2, colors: ['#fff']},
                        plotOptions: {pie: {donut: {size: '62%', labels: {show: true, total: {show: true, label: 'الإجمالي'}}}}},
                    });
                }).fail(function (xhr) {
                    if (xhr.statusText === 'abort') return;
                    $('[data-stat-key]').text('—');
                    $('#arrivalPeriodComparison').text((xhr.responseJSON && xhr.responseJSON.message) || 'تعذّر تحديث الإحصائيات. راجع الفلاتر.');
                });

                insights.done(function (response) {
                    const data = response.data || {};
                    const durations = data.durations || {};
                    $('#kpiContractToArrival').text(durations.contract_to_arrival ?? '—');
                    $('#kpiArrivalToDelivery').text(durations.arrival_to_delivery ?? '—');

                    const trend = data.trend || {labels: []};
                    render('trendChart', {
                        chart: {type: 'area', height: 320},
                        series: [
                            {name: 'عقود جديدة', data: trend.contracts},
                            {name: 'وصول فعلي', data: trend.arrivals},
                            {name: 'تسليم موثق', data: trend.deliveries},
                        ],
                        xaxis: {categories: trend.labels},
                        yaxis: {labels: {formatter: value => Math.round(value)}},
                        colors: COLORS,
                        stroke: {curve: 'smooth', width: 2},
                        fill: {type: 'gradient', gradient: {opacityFrom: 0.25, opacityTo: 0.02}},
                        markers: {size: 4, strokeWidth: 2, strokeColors: '#fff'},
                        legend: {position: 'top', horizontalAlign: 'right'},
                        tooltip: {shared: true, intersect: false},
                    });

                    barChart('nationalitiesChart', data.nationalities || [], COLORS[0]);
                    barChart('officesChart', data.offices || [], COLORS[0]);
                    barChart('marketersChart', data.marketers || [], COLORS[0]);
                });

                $.when(stats, insights).always(function () {
                    $('#insightsBody').removeClass('insights-loading');
                });
            }

            $('[data-param], #fBranch').on('change', load);
            $('.date-type-switch button').on('click', function () {
                dateType = $(this).data('value');
                load();
            });
            $('.period-shortcuts button').on('click', function () {
                const range = periods()[$(this).data('period')];
                $('#fDateFrom').val(range[0]);
                $('#fDateTo').val(range[1]);
                load();
            });
            $('#clearExtraFilters').on('click', function () {
                const next = new URL(window.location.href);
                Object.keys(extraParams()).filter(key => key !== 'company_contracts').forEach(key => next.searchParams.delete(key));
                window.history.replaceState({}, '', next.toString());
                load();
            });
            $('#resetFilters').on('click', function () {
                $('[data-param]').val('');
                $('#fBranch').val('');
                dateType = 'contract';
                const next = new URL(window.location.pathname, window.location.origin);
                if (url.searchParams.get('company_contracts')) next.searchParams.set('company_contracts', 1);
                window.history.replaceState({}, '', next.toString());
                load();
            });

            load();
        });
    </script>
@endsection
