@extends('admin.layout.indexs.index')

@section('page-title')
    {{ $pageConfig['title'] }}
@endsection

@section("style")
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .status-overview-page .page-title-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #eef4ff;
            color: #2563eb;
        }

        .status-overview-page .statistics-card {
            border: 1px solid transparent;
            border-radius: 16px;
            box-shadow: 0 0.45rem 1.25rem rgba(15, 23, 42, 0.08) !important;
            border-inline-start: 4px solid transparent;
        }

        .status-overview-page .stats-card-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .status-overview-page .stats-card-link .statistics-card {
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            cursor: pointer;
        }

        .status-overview-page .stats-card-link:hover .statistics-card,
        .status-overview-page .stats-card-link:focus .statistics-card {
            transform: translateY(-3px);
            box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.12) !important;
        }

        .status-overview-page .statistics-card .text-xs {
            color: #475569 !important;
            letter-spacing: 0;
        }

        .status-overview-page .statistics-card .h5 {
            color: #111827;
        }

        .status-overview-page .statistics-card i {
            opacity: 0.95;
        }

        .status-overview-page .statistics-card.pending-card {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            border-color: #dbe4ee;
            border-inline-start-color: #94a3b8;
        }

        .status-overview-page .statistics-card.pending-card i {
            color: #64748b;
        }

        .status-overview-page .statistics-card.trial-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #bfdbfe;
            border-inline-start-color: #3b82f6;
        }

        .status-overview-page .statistics-card.trial-card i {
            color: #2563eb;
        }

        .status-overview-page .statistics-card.transferred-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-color: #a7f3d0;
            border-inline-start-color: #10b981;
        }

        .status-overview-page .statistics-card.transferred-card i {
            color: #059669;
        }

        .status-overview-page .statistics-card.failed-card {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #fecaca;
            border-inline-start-color: #ef4444;
        }

        .status-overview-page .statistics-card.failed-card i {
            color: #dc2626;
        }

        .status-overview-page .filter-card,
        .status-overview-page .card.shadow {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        .status-overview-page .filter-card .card-header,
        .status-overview-page .card.shadow .card-header.bg-primary {
            background: #f8fafc !important;
            color: #111827 !important;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-overview-page .filter-card .form-label {
            color: #475569;
            font-weight: 600;
            font-size: 0.86rem;
        }

        .status-overview-page .filter-card .form-control,
        .status-overview-page .filter-card .form-select {
            border-color: #d7dee7;
            min-height: 42px;
            box-shadow: none;
        }

        .status-overview-page .filter-card .form-control:focus,
        .status-overview-page .filter-card .form-select:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 0.18rem rgba(148, 163, 184, 0.16);
        }

        .status-overview-page .active-filters-box {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border: 1px dashed #d6dde6;
            border-radius: 12px;
            background: #f8fafc;
        }

        .status-overview-page .active-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            margin: 0.2rem;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #d6dde6;
            color: #334155;
            font-size: 0.8rem;
        }

        .status-overview-table thead th {
            white-space: nowrap;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.84rem;
            font-weight: 700;
            border-bottom-width: 1px;
        }

        .status-overview-table td {
            vertical-align: middle;
        }

        .status-overview-page .table-hover tbody tr:hover {
            background: #fafcff;
        }

        .reason-box {
            max-width: 280px;
            white-space: normal;
            color: #475569;
        }

        .status-overview-page .status-pill,
        .status-overview-page .meta-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .status-overview-page .status-pill.status-primary,
        .status-overview-page .meta-pill.meta-primary {
            background: #dbeafe;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .status-overview-page .status-pill.status-success {
            background: #d1fae5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .status-overview-page .status-pill.status-danger,
        .status-overview-page .meta-pill.meta-danger {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .status-overview-page .meta-pill.meta-warning {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
        }

        .status-overview-page .actions-btn {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #d6dde6;
        }

        .status-overview-page .actions-btn:hover,
        .status-overview-page .actions-btn:focus {
            background: #f8fafc;
            color: #111827;
            border-color: #c1ccd8;
        }
    </style>
@endsection

@section('content')
@if(auth()->guard('admin')->user()->can('sponsor_transfer_view'))
    @php
        $headerIcons = [
            'trial' => 'fas fa-user-clock',
            'transferred' => 'fas fa-check-circle',
            'failed' => 'fas fa-times-circle',
        ];

        $statCardClasses = [
            'primary' => 'trial-card',
            'success' => 'transferred-card',
            'warning' => 'pending-card',
            'danger' => 'failed-card',
            'info' => 'pending-card',
        ];

        $headerIcon = $headerIcons[$pageConfig['page']] ?? 'fas fa-exchange-alt';

        $statusPillClasses = [
            'primary' => 'status-primary',
            'success' => 'status-success',
            'danger' => 'status-danger',
            'warning' => 'status-primary',
            'info' => 'status-primary',
        ];

        $activeFilters = collect([
            helperTrans("ingaz.contract_number") => request('contract_number'),
            helperTrans("ingaz.old_sponsor") => request('old_sponsor'),
            helperTrans("ingaz.new_sponsor") => request('new_sponsor'),
            'العاملة' => request('maid_keyword'),
            helperTrans("ingaz.date_from") => request('date_from'),
            helperTrans("ingaz.date_to") => request('date_to'),
            helperTrans("ingaz.trial_days") => request('trial_days') ? request('trial_days') . ' يوم' : null,
            helperTrans("ingaz.contract_value") . ' من' => request('amount_from'),
            helperTrans("ingaz.contract_value") . ' إلى' => request('amount_to'),
            'العودة للإيواء' => request('returned_to_shelter') ? 'نعم' : null,
            'سبب موثق' => request('documented_reason') ? 'نعم' : null,
            'حالة التجربة' => request('trial_state') ? [
                'active' => 'داخل المدة',
                'today' => 'تنتهي اليوم',
                'soon' => 'تنتهي خلال 7 أيام',
                'expired' => 'منتهية',
            ][request('trial_state')] ?? request('trial_state') : null,
            'سبب الفشل' => request('failure_reason'),
            'الترتيب حسب' => request('sort') ? ($pageConfig['sort_options'][request('sort')] ?? request('sort')) : null,
            'الاتجاه' => request('direction') ? (request('direction') === 'asc' ? 'تصاعدي' : 'تنازلي') : null,
        ])->filter(fn ($value) => filled($value));
    @endphp

    <div class="container-fluid status-overview-page">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <span class="page-title-icon me-2">
                        <i class="{{ $headerIcon }}"></i>
                    </span>
                    {{ $pageConfig['title'] }}
                </h1>
                <div class="text-muted mt-2">
                    عدد النتائج الحالية: {{ number_format($transfers->total()) }}
                    @if($filtersActive)
                        <span class="badge bg-secondary ms-2">تم تطبيق فلترة</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 mt-3 mt-sm-0">
                <a href="{{ route('SponsorshipTransfer.index') }}" class="btn btn-dark">
                    <i class="fas fa-arrow-right me-2"></i>
                    العودة للإدارة الرئيسية
                </a>
            </div>
        </div>

        <div class="row mb-4">
            @foreach($stats as $card)
                <div class="col-xl-3 col-md-6 mb-4">
                    <a href="{{ $card['href'] ?? $pageConfig['route'] }}" class="stats-card-link">
                        <div class="card statistics-card {{ $statCardClasses[$card['theme']] ?? 'trial-card' }} shadow h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            {{ $card['label'] }}
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $card['value'] }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="{{ $card['icon'] }} fa-2x"></i>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2">
                                    اضغط للتصفية
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter me-2"></i>
                    {{ helperTrans("ingaz.filters") }}
                </h6>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="{{ $filtersActive ? 'true' : 'false' }}" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="collapse {{ $filtersActive ? 'show' : '' }}" id="filtersCollapse">
                <div class="card-body">
                    <form method="GET" action="{{ $pageConfig['route'] }}">
                        @if($activeFilters->isNotEmpty())
                            <div class="active-filters-box">
                                <div class="small text-muted mb-2">الفلاتر المطبقة حالياً</div>
                                <div>
                                    @foreach($activeFilters as $label => $value)
                                        <span class="active-filter-chip">
                                            <strong>{{ $label }}:</strong>
                                            <span>{{ $value }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_number") }}</label>
                                <input type="text" class="form-control" name="contract_number" value="{{ request('contract_number') }}" placeholder="ابحث برقم العقد">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.old_sponsor") }}</label>
                                <input type="text" class="form-control" name="old_sponsor" value="{{ request('old_sponsor') }}" placeholder="اسم أو جوال أو هوية">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.new_sponsor") }}</label>
                                <input type="text" class="form-control" name="new_sponsor" value="{{ request('new_sponsor') }}" placeholder="اسم أو جوال أو هوية">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">العاملة</label>
                                <input type="text" class="form-control" name="maid_keyword" value="{{ request('maid_keyword') }}" placeholder="اسم أو رقم جواز">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.date_from") }} {{ $pageConfig['date_label'] }}</label>
                                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.date_to") }} {{ $pageConfig['date_label'] }}</label>
                                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.trial_days") }}</label>
                                <select class="form-select" name="trial_days">
                                    <option value="">{{ helperTrans("ingaz.all_trial_days") }}</option>
                                    @foreach([7, 14, 15, 30, 45, 60, 90] as $days)
                                        <option value="{{ $days }}" {{ (string) request('trial_days') === (string) $days ? 'selected' : '' }}>{{ $days }} يوم</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_value") }} من</label>
                                <input type="number" step="0.01" class="form-control" name="amount_from" value="{{ request('amount_from') }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ helperTrans("ingaz.contract_value") }} إلى</label>
                                <input type="number" step="0.01" class="form-control" name="amount_to" value="{{ request('amount_to') }}" placeholder="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الترتيب حسب</label>
                                <select class="form-select" name="sort">
                                    @foreach($pageConfig['sort_options'] as $value => $label)
                                        <option value="{{ $value }}" {{ request('sort', $pageConfig['default_sort']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الاتجاه</label>
                                <select class="form-select" name="direction">
                                    <option value="asc" {{ request('direction', $pageConfig['default_direction']) === 'asc' ? 'selected' : '' }}>تصاعدي</option>
                                    <option value="desc" {{ request('direction', $pageConfig['default_direction']) === 'desc' ? 'selected' : '' }}>تنازلي</option>
                                </select>
                            </div>

                            @if($pageConfig['page'] === 'trial')
                                <div class="col-md-3">
                                    <label class="form-label">حالة التجربة</label>
                                    <select class="form-select" name="trial_state">
                                        <option value="">الكل</option>
                                        <option value="active" {{ request('trial_state') === 'active' ? 'selected' : '' }}>داخل المدة</option>
                                        <option value="today" {{ request('trial_state') === 'today' ? 'selected' : '' }}>تنتهي اليوم</option>
                                        <option value="soon" {{ request('trial_state') === 'soon' ? 'selected' : '' }}>تنتهي خلال 7 أيام</option>
                                        <option value="expired" {{ request('trial_state') === 'expired' ? 'selected' : '' }}>منتهية</option>
                                    </select>
                                </div>
                            @endif

                            @if($pageConfig['page'] === 'failed')
                                <div class="col-md-3">
                                    <label class="form-label">سبب الفشل</label>
                                    <input type="text" class="form-control" name="failure_reason" value="{{ request('failure_reason') }}" placeholder="ابحث بسبب الفشل">
                                </div>
                            @endif
                        </div>

                        <div class="row mt-3">
                            <div class="col-12 d-flex justify-content-between">
                                <a href="{{ $pageConfig['route'] }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>{{ helperTrans("ingaz.clear_filters") }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>{{ helperTrans("ingaz.search") }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    قائمة {{ $pageConfig['title'] }}
                </h6>
            </div>
            <div class="card-body">
                @if($transfers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 status-overview-table">
                            <thead>
                                <tr>
                                    <th>{{ helperTrans("ingaz.contract_number") }}</th>
                                    <th>{{ helperTrans("ingaz.old_sponsor") }}</th>
                                    <th>{{ helperTrans("ingaz.new_sponsor") }}</th>
                                    <th>بيانات العاملة</th>
                                    @if($pageConfig['page'] === 'trial')
                                        <th>بداية التجربة</th>
                                        <th>{{ helperTrans("ingaz.trial_days") }}</th>
                                        <th>نهاية التجربة</th>
                                        <th>{{ helperTrans("ingaz.remaining_amount") }}</th>
                                    @elseif($pageConfig['page'] === 'transferred')
                                        <th>تاريخ النقل</th>
                                        <th>{{ helperTrans("ingaz.contract_value") }}</th>
                                        <th>{{ helperTrans("ingaz.paid_amount") }}</th>
                                        <th>نفذ بواسطة</th>
                                    @else
                                        <th>تاريخ الفشل</th>
                                        <th>سبب الفشل</th>
                                        <th>المستحق للعميل</th>
                                        <th>سجل بواسطة</th>
                                    @endif
                                    <th>{{ helperTrans("ingaz.status") }}</th>
                                    <th>{{ helperTrans("ingaz.actions") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers as $transfer)
                                    @php
                                        $contract = $transfer->contract;
                                        $maid = $contract?->maid;
                                        $maidNationality = optional($maid?->nationality)->title ?: optional($maid?->nationality)->name ?: optional($maid?->nationality)->sub_title ?: 'غير محدد';
                                        $trialEndDate = $transfer->trial_end_date;
                                        $daysRemaining = $transfer->trial_days_remaining;
                                    @endphp
                                    <tr>
                                        <td><strong>عقد رقم: {{ $contract?->id ?? 'غير محدد' }}</strong></td>
                                        <td>
                                            <div>
                                                <strong>{{ optional($transfer->oldSponsor)->name ?? 'غير محدد' }}</strong><br>
                                                <small class="text-muted">{{ optional($transfer->oldSponsor)->documentNumber() ?? optional($transfer->oldSponsor)->phone ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ optional($transfer->newSponsor)->name ?? 'غير محدد' }}</strong><br>
                                                <small class="text-muted">{{ optional($transfer->newSponsor)->documentNumber() ?? optional($transfer->newSponsor)->phone ?? '' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($maid)
                                                <div>
                                                    <strong>{{ $maid->name ?? 'غير محدد' }}</strong><br>
                                                    <small class="text-muted">{{ $maid->passport_num ?? 'بدون جواز' }}</small><br>
                                                    <small class="text-muted">{{ $maidNationality }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">لا توجد بيانات</span>
                                            @endif
                                        </td>

                                        @if($pageConfig['page'] === 'trial')
                                            <td>
                                                @if($transfer->effective_trial_start_date)
                                                    <div>
                                                        <strong>{{ $transfer->effective_trial_start_date->format('Y-m-d') }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->effective_trial_start_date->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>{{ $transfer->trial_days }} يوم</td>
                                            <td>
                                                @if($trialEndDate)
                                                    <div>
                                                        <strong>{{ $trialEndDate->format('Y-m-d') }}</strong><br>
                                                        @if($daysRemaining > 0)
                                                            <span class="meta-pill meta-primary">باقي {{ $daysRemaining }} يوم</span>
                                                        @elseif($daysRemaining === 0)
                                                            <span class="meta-pill meta-warning">تنتهي اليوم</span>
                                                        @else
                                                            <span class="meta-pill meta-danger">انتهت منذ {{ abs($daysRemaining) }} يوم</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format((float) $transfer->remaining_amount, 2) }} ريال</td>
                                        @elseif($pageConfig['page'] === 'transferred')
                                            <td>
                                                @if($transfer->transferred_at)
                                                    <div>
                                                        <strong>{{ $transfer->transferred_at->format('Y-m-d') }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->transferred_at->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format((float) $transfer->contract_value, 2) }} ريال</td>
                                            <td>{{ number_format((float) $transfer->paid_amount, 2) }} ريال</td>
                                            <td>
                                                @if($transfer->transferredBy)
                                                    <div>
                                                        <strong>{{ $transfer->transferredBy->name }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->transferred_at?->diffForHumans() }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        @else
                                            <td>
                                                @if($transfer->failed_at)
                                                    <div>
                                                        <strong>{{ $transfer->failed_at->format('Y-m-d') }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->failed_at->format('H:i') }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="reason-box">
                                                    {{ $transfer->failure_reason ?: 'لم يتم تحديد السبب' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-danger">
                                                    {{ number_format((float) $transfer->failure_settlement_amount, 2) }} ريال
                                                </div>
                                                <small class="text-muted">
                                                    تصفير المتبقي: {{ number_format((float) $transfer->failure_clear_remaining_amount, 2) }} ريال
                                                </small>
                                            </td>
                                            <td>
                                                @if($transfer->failedBy)
                                                    <div>
                                                        <strong>{{ $transfer->failedBy->name }}</strong><br>
                                                        <small class="text-muted">{{ $transfer->failed_at?->diffForHumans() }}</small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">غير محدد</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td>
                                            <span class="status-pill {{ $statusPillClasses[$pageConfig['theme']] ?? 'status-primary' }}">{{ $transfer->status_text }}</span>
                                        </td>
                                        <td>
                                            <div class="dropdown actions-dropdown">
                                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="الإجراءات المتاحة">
                                                    <i class="fas fa-cogs me-1"></i> الإجراءات
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('SponsorshipTransfer.show', $transfer->id) }}">
                                                            <i class="fas fa-eye"></i> عرض التفاصيل
                                                        </a>
                                                    </li>

                                                    @if($pageConfig['page'] === 'trial')
                                                        <li>
                                                            <a class="dropdown-item confirm-action" href="#" data-action="confirm-transfer" data-id="{{ $transfer->id }}" data-text="تأكيد نقل الكفالة وإنهاء فترة التجربة">
                                                                <i class="fas fa-check-circle"></i> تأكيد النقل
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item cancel-transfer-action" href="#"
                                                                data-id="{{ $transfer->id }}"
                                                                data-contract-value="{{ (float) $transfer->contract_value }}"
                                                                data-paid-amount="{{ (float) $transfer->paid_amount }}"
                                                                data-remaining-amount="{{ (float) $transfer->remaining_amount }}">
                                                                <i class="fas fa-times-circle"></i> إلغاء النقل
                                                            </a>
                                                        </li>
                                                    @else
                                                        @if($contract)
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('contracts.details', $contract->id) }}">
                                                                    <i class="fas fa-file-contract"></i> عرض العقد
                                                                </a>
                                                            </li>
                                                        @endif

                                                        @if($pageConfig['page'] === 'failed')
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('ShelterContract.availableForTransfer') }}">
                                                                    <i class="fas fa-home"></i> عرض في الإيواء
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $transfers->links('pagination::bootstrap-4') }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="{{ $pageConfig['empty_icon'] }} fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">{{ $pageConfig['empty_title'] }}</h5>
                        <p class="text-muted">{{ $pageConfig['empty_message'] }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if($pageConfig['page'] === 'failed' && $topFailureReasons->count() > 0)
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-chart-bar me-2"></i>
                        أكثر أسباب الفشل تكراراً
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($topFailureReasons as $reason)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $reason->failure_reason }}</span>
                                <span class="status-pill status-danger">{{ $reason->total }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@else
    @include('components.no_permissions')
@endif
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{asset("admin")}}/custom/admin-tables.js"></script>
    <script>
        $(document).ready(function () {
            const filtersCollapse = document.getElementById('filtersCollapse');
            const filtersIcon = document.getElementById('filtersIcon');

            if (filtersCollapse && filtersIcon) {
                filtersCollapse.addEventListener('show.bs.collapse', function () {
                    filtersIcon.classList.remove('fa-chevron-down');
                    filtersIcon.classList.add('fa-chevron-up');
                });

                filtersCollapse.addEventListener('hide.bs.collapse', function () {
                    filtersIcon.classList.remove('fa-chevron-up');
                    filtersIcon.classList.add('fa-chevron-down');
                });

                if (filtersCollapse.classList.contains('show')) {
                    filtersIcon.classList.remove('fa-chevron-down');
                    filtersIcon.classList.add('fa-chevron-up');
                }
            }

            @if($pageConfig['page'] === 'trial')
                $(document).on('click', '.confirm-action', function (event) {
                    event.preventDefault();

                    const action = $(this).data('action');
                    const id = $(this).data('id');
                    const text = $(this).data('text');

                    Swal.fire({
                        title: 'تأكيد العملية',
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'تأكيد',
                        cancelButtonText: 'إلغاء'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performAction(action, id);
                        }
                    });
                });

                $(document).on('click', '.cancel-transfer-action', function (event) {
                    event.preventDefault();
                    const button = $(this);
                    const id = button.data('id');
                    const contractValue = Number(button.data('contract-value')) || 0;
                    const paidAmount = Number(button.data('paid-amount')) || 0;
                    const remainingAmount = Number(button.data('remaining-amount')) || 0;
                    const money = (value) => Number(value || 0).toLocaleString('ar-SA', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    Swal.fire({
                        title: 'تأكيد فشل التجربة',
                        html: `
                            <div class="text-start" dir="rtl">
                                <div class="alert alert-light border mb-3">
                                    <div>قيمة العقد: <strong>${money(contractValue)} ريال</strong></div>
                                    <div>الدائن (المدفوع): <strong>${money(paidAmount)} ريال</strong></div>
                                    <div>المدين (المتبقي): <strong>${money(remainingAmount)} ريال</strong></div>
                                    <div>إجمالي الخصومات: <strong>0.00 ريال</strong></div>
                                </div>
                                <label class="form-label">نوع التسوية</label>
                                <select id="failureSettlementType" class="form-select mb-2">
                                    <option value="refund" selected>استرجاع للعميل (يُسجل كمستحق للعميل)</option>
                                </select>
                                <label class="form-label">مبلغ التسوية</label>
                                <input type="number" id="failureSettlementAmount" class="form-control mb-1" min="0" step="0.01" value="${paidAmount.toFixed(2)}">
                                <div class="form-text mb-2">يتم احتساب الاسترجاع تلقائيا من المبلغ المدفوع ويمكن تعديله</div>
                                <label class="form-label">آيبان العميل <span class="text-danger">*</span></label>
                                <input type="text" id="failureClientIban" class="form-control mb-2" dir="ltr" maxlength="34" placeholder="SA0000000000000000000000">
                                <label class="form-label">مرفق الآيبان <span class="text-danger">*</span></label>
                                <input type="file" id="failureClientIbanAttachment" class="form-control mb-2" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="alert alert-warning py-2 mb-2">سيتم تصفير المبلغ المتبقي عند إلغاء النقل حسب المبلغ المحدد أدناه.</div>
                                <label class="form-label">مبلغ تصفير المتبقي</label>
                                <input type="number" id="failureClearRemainingAmount" class="form-control mb-1" min="0" step="0.01" value="${remainingAmount.toFixed(2)}">
                                <div class="form-text mb-2">يمكنك تعديل مبلغ التصفير حسب الحاجة</div>
                                <div class="alert alert-info mb-2" id="failureSettlementPreview"></div>
                                <label class="form-label">سبب الفشل</label>
                                <textarea id="failureReason" class="form-control" rows="3" placeholder="اكتب سبب الفشل أو الإلغاء"></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'حفظ وإلغاء النقل',
                        cancelButtonText: 'إلغاء',
                        didOpen: () => {
                            const updatePreview = () => {
                                const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                                const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                                $('#failureSettlementPreview').html(`
                                    الدائن بعد التسوية: <strong>${money(paidAmount)} ريال</strong><br>
                                    المدين بعد التسوية: <strong>${money(Math.max(0, remainingAmount - clearRemainingAmount))} ريال</strong><br>
                                    المستحق للعميل بعد التسوية: <strong>${money(settlementAmount)} ريال</strong>
                                `);
                            };

                            $('#failureSettlementAmount, #failureClearRemainingAmount').on('input', updatePreview);
                            updatePreview();
                        },
                        preConfirm: () => {
                            const settlementAmount = Number($('#failureSettlementAmount').val()) || 0;
                            const clearRemainingAmount = Number($('#failureClearRemainingAmount').val()) || 0;
                            const iban = ($('#failureClientIban').val() || '').trim();
                            const attachment = $('#failureClientIbanAttachment')[0]?.files?.[0] || null;

                            if (settlementAmount > paidAmount) {
                                Swal.showValidationMessage('مبلغ الاسترجاع لا يمكن أن يكون أكبر من المبلغ المدفوع.');
                                return false;
                            }

                            if (clearRemainingAmount > remainingAmount) {
                                Swal.showValidationMessage('مبلغ تصفير المتبقي لا يمكن أن يكون أكبر من المبلغ المتبقي.');
                                return false;
                            }

                            if (settlementAmount > 0 && !iban && !attachment) {
                                Swal.showValidationMessage('يرجى إدخال آيبان العميل أو إرفاق مرفق الآيبان.');
                                return false;
                            }

                            return {
                                failure_reason: ($('#failureReason').val() || '').trim(),
                                failure_settlement_type: $('#failureSettlementType').val(),
                                failure_settlement_amount: settlementAmount,
                                failure_clear_remaining_amount: clearRemainingAmount,
                                failure_client_iban: iban,
                                failure_client_iban_attachment: attachment
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performCancelTransfer(id, result.value);
                        }
                    });
                });

                function performCancelTransfer(id, payload) {
                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('failure_reason', payload.failure_reason || '');
                    formData.append('failure_settlement_type', payload.failure_settlement_type || 'refund');
                    formData.append('failure_settlement_amount', payload.failure_settlement_amount || 0);
                    formData.append('failure_clear_remaining_amount', payload.failure_clear_remaining_amount || 0);
                    formData.append('failure_client_iban', payload.failure_client_iban || '');

                    if (payload.failure_client_iban_attachment) {
                        formData.append('failure_client_iban_attachment', payload.failure_client_iban_attachment);
                    }

                    $.ajax({
                        url: `/admin/SponsorshipTransfer/${id}/cancel-transfer`,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('نجح!', response.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || 'حدث خطأ غير متوقع';
                            Swal.fire('خطأ!', message, 'error');
                        }
                    });
                }

                function performAction(action, id, payload = {}) {
                    $.ajax({
                        url: `/admin/SponsorshipTransfer/${id}/${action}`,
                        type: 'POST',
                        data: Object.assign({
                            _token: '{{ csrf_token() }}'
                        }, payload),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('نجح!', response.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('خطأ!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            const message = xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
                            Swal.fire('خطأ!', message, 'error');
                        }
                    });
                }
            @endif
        });
    </script>
@endsection
