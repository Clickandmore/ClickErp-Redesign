@extends('admin.layout.indexs.index')

@section('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .instant-receipt-card {
            border-left: 4px solid #ffc107;
        }

        .instant-receipt-card .card-body {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }

        .instant-receipt-card .text-xs {
            color: #856404;
        }

        .instant-receipt-card .h5 {
            color: #856404;
        }

        .quick-filter-chip.active {
            color: #fff !important;
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .column-explanation {
            border-right: 4px solid #0d6efd;
            background: #f8fbff;
        }

        .stats-filter-card {
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .stats-filter-card:hover {
            transform: translateY(-2px);
        }

        .compact-stats-panel {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 .125rem .35rem rgba(0, 0, 0, .04);
        }

        .compact-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(138px, 1fr));
            gap: .65rem;
        }

        .compact-stat {
            width: 100%;
            min-height: 76px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            color: inherit;
            text-align: start;
            padding: .75rem;
        }

        .compact-stat:focus {
            outline: 2px solid rgba(13, 110, 253, .25);
            outline-offset: 2px;
        }

        .compact-stat-label {
            display: flex;
            align-items: center;
            gap: .4rem;
            min-height: 22px;
            color: #6c757d;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .compact-stat-value {
            margin-top: .35rem;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1;
        }

        .compact-stat-meta {
            display: block;
            margin-top: .3rem;
            color: #6c757d;
            font-size: .74rem;
            line-height: 1.3;
        }

        .compact-stat-primary {
            border-right: 4px solid #0d6efd;
        }

        .compact-stat-rental {
            border-right: 4px solid #0dcaf0;
            background: #f7fdff;
        }

        .quick-tags-panel {
            border-top: 1px solid #f1f3f5;
        }

        .quick-tags-strip {
            display: flex;
            gap: .5rem;
            overflow-x: auto;
            padding-bottom: .15rem;
            scrollbar-width: thin;
        }

        .quick-tags-strip .quick-filter-chip {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .shelter-contracts-page {
            overflow-x: hidden;
        }

        .shelter-contracts-page .page-actions {
            row-gap: .5rem;
        }

        .shelter-contracts-page .quick-filter-chip {
            max-width: 100%;
        }

        .shelter-contracts-page .table-card .card-body {
            min-width: 0;
        }

        .shelter-contracts-page .table-responsive {
            overflow-x: auto;
        }

        #shelterContractsTable {
            width: 100% !important;
        }

        #shelterContractsTable th,
        #shelterContractsTable td {
            vertical-align: middle;
        }

        #shelterContractsTable td {
            white-space: normal;
            word-break: break-word;
        }

        #shelterContractsTable th:nth-child(3),
        #shelterContractsTable td:nth-child(3) {
            min-width: 220px;
        }

        #shelterContractsTable th:nth-child(4),
        #shelterContractsTable td:nth-child(4) {
            min-width: 180px;
        }

        #shelterContractsTable th:nth-child(11),
        #shelterContractsTable td:nth-child(11) {
            min-width: 240px;
        }

        #shelterContractsTable td.child {
            background: #f8f9fa;
        }

        #shelterContractsTable td.child ul.dtr-details {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        #shelterContractsTable td.child ul.dtr-details li {
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            align-items: flex-start;
            padding: .5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        #shelterContractsTable td.child .dtr-title {
            flex: 0 0 42%;
            font-weight: 700;
        }

        #shelterContractsTable td.child .dtr-data {
            flex: 1;
            min-width: 0;
            text-align: start;
        }

        .table-person-cell,
        .shelter-status-cell {
            display: flex;
            flex-direction: column;
            gap: .42rem;
            min-width: 0;
        }

        .table-person-title {
            color: #212529;
            font-size: .92rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .table-person-meta,
        .status-line {
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            color: #6c757d;
            font-size: .78rem;
            line-height: 1.4;
        }

        .table-person-meta i,
        .status-line i {
            flex: 0 0 14px;
            width: 14px;
            margin-top: .12rem;
            color: #8a94a6;
            text-align: center;
        }

        .status-top {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 100%;
            border-radius: 999px;
            padding: .25rem .55rem;
            font-size: .74rem;
            font-weight: 800;
            line-height: 1.25;
            white-space: normal;
        }

        .status-pill-service {
            color: #084298;
            background: #e7f1ff;
            border: 1px solid #bfd7ff;
        }

        .status-pill-work {
            color: #0f5132;
            background: #d1e7dd;
            border: 1px solid #badbcc;
        }

        .status-pill-work-warning {
            color: #664d03;
            background: #fff3cd;
            border: 1px solid #ffecb5;
        }

        .status-pill-work-muted {
            color: #41464b;
            background: #e2e3e5;
            border: 1px solid #d3d6d8;
        }

        .status-line strong {
            flex: 0 0 auto;
            color: #495057;
            font-weight: 800;
        }

        .status-line span {
            min-width: 0;
        }

        .shelter-contracts-page .dataTables_wrapper .row {
            row-gap: .75rem;
        }

        .shelter-contracts-page .dataTables_wrapper .dataTables_info,
        .shelter-contracts-page .dataTables_wrapper .dataTables_paginate {
            white-space: normal;
        }

        @media (max-width: 991.98px) {
            .shelter-contracts-page {
                padding: 1rem !important;
            }

            .shelter-contracts-page .container-fluid {
                padding-right: 0;
                padding-left: 0;
            }

            .shelter-contracts-page .page-header {
                align-items: stretch !important;
                gap: 1rem;
            }

            .shelter-contracts-page .page-header .h3 {
                font-size: 1.25rem;
                line-height: 1.5;
            }

            .shelter-contracts-page .page-actions {
                width: 100%;
            }

            .shelter-contracts-page .page-actions .btn,
            .shelter-contracts-page .filter-actions .btn {
                flex: 1 1 150px;
            }

            .shelter-contracts-page .card-body {
                padding: 1rem;
            }

            .compact-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .shelter-contracts-page {
                padding: .75rem !important;
            }

            .shelter-contracts-page .page-title-box .page-title {
                font-size: 1rem;
            }

            .shelter-contracts-page .page-actions .btn,
            .shelter-contracts-page .filter-actions .btn {
                flex-basis: 100%;
                width: 100%;
            }

            .shelter-contracts-page .statistics-card .card-body .row {
                flex-wrap: nowrap;
            }

            .shelter-contracts-page .statistics-card .fa-2x {
                font-size: 1.4rem;
            }

            .quick-tags-strip .quick-filter-chip {
                max-width: 82vw;
            }

            #shelterContractsTable {
                font-size: .875rem;
            }

            #shelterContractsTable td.child ul.dtr-details li {
                display: block;
            }

            #shelterContractsTable td.child .dtr-title {
                display: block;
                margin-bottom: .25rem;
            }

            .shelter-contracts-page .dataTables_wrapper .pagination {
                flex-wrap: wrap;
                gap: .25rem;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.All Shelter contracts') }}
@endsection

@section('content')
    <div class="page-body p-4 text-dark admin-tables shelter-contracts-page">
        @if (
            @AdminGuardHasAnyPerm([
                \App\PermKeys::SHELTER_CONTRACT_INSIDE_VIEW,
                \App\PermKeys::SHELTER_CONTRACT_VIEW,
                \App\PermKeys::SHELTER_CONTRACT_CONTROL,
            ]))
            <div class="container-fluid ">
                <!-- Page Title Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">قائمة العقود داخل الإيواء</h4>
                        </div>
                    </div>
                </div>

                <!-- Header Section -->
                <div class="page-header d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-home text-primary me-2"></i>
                        {{ helperTrans('ingaz.All Shelter contracts') }}
                    </h3>
                    <div class="page-actions d-flex flex-wrap gap-2">
                        <button type="button" id="exportExcel" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>تصدير Excel
                        </button>
                        <button type="button" id="exportPdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>تصدير PDF
                        </button>
                        @if (
                            @AdminGuardHasAnyPerm([
                                \App\PermKeys::SHELTER_CONTRACT_EDIT,
                                \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                            ]))
                            <button type="button" id="openLivingExpenseModal" class="btn btn-warning">
                                <i class="fas fa-utensils me-2"></i>إضافة إعاشة
                            </button>
                        @endif
                        @if (
                            @AdminGuardHasAnyPerm([
                                \App\PermKeys::SHELTER_CONTRACT_ADDITION_CREATE,
                                \App\PermKeys::SHELTER_CONTRACT_CREATE,
                                \App\PermKeys::SHELTER_CONTRACT_CONTROL,
                            ]))
                            <a href="{{ route('ShelterContract.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                {{ helperTrans('ingaz.add_shelter_contract') }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Statistics And Quick Categories -->
                <div class="compact-stats-panel mb-4">
                    <div class="p-3">
                        <div class="compact-stats-grid">
                            <button type="button" class="compact-stat compact-stat-primary stats-filter-card"
                                data-clear-filters="1">
                                <span class="compact-stat-label">
                                    <i class="fas fa-file-contract text-primary"></i> إجمالي العقود
                                </span>
                                <span class="compact-stat-value">{{ $totalContracts ?? 0 }}</span>
                                <span class="compact-stat-meta">كل العاملات داخل الإيواء</span>
                            </button>

                            <button type="button" class="compact-stat stats-filter-card"
                                data-service-category="recruitment">
                                <span class="compact-stat-label">
                                    <i class="fas fa-briefcase text-primary"></i> استقدام
                                </span>
                                <span class="compact-stat-value">{{ $serviceCategoryStats['recruitment'] ?? 0 }}</span>
                                <span class="compact-stat-meta">فلترة عاملات الاستقدام</span>
                            </button>

                            <button type="button" class="compact-stat compact-stat-rental stats-filter-card"
                                data-service-category="rental">
                                <span class="compact-stat-label">
                                    <i class="fas fa-house-user text-info"></i> عاملات الإيجار
                                </span>
                                <span class="compact-stat-value">{{ $rentalWorkerStats['total'] ?? 0 }}</span>
                                <span class="compact-stat-meta">جاهزات: {{ $rentalWorkerStats['ready'] ?? 0 }} | فوري:
                                    {{ $rentalWorkerStats['instant'] ?? 0 }}</span>
                            </button>

                            <button type="button" class="compact-stat stats-filter-card" data-service-category="transfer">
                                <span class="compact-stat-label">
                                    <i class="fas fa-people-arrows text-success"></i> نقل خدمات
                                </span>
                                <span class="compact-stat-value">{{ $serviceCategoryStats['transfer'] ?? 0 }}</span>
                                <span class="compact-stat-meta">فلترة نقل الخدمات</span>
                            </button>

                            <button type="button" class="compact-stat stats-filter-card" data-status="within_guarantee">
                                <span class="compact-stat-label">
                                    <i class="fas fa-shield-alt text-success"></i> ضمن الضمان
                                </span>
                                <span class="compact-stat-value">{{ $withinGuarantee ?? 0 }}</span>
                                <span class="compact-stat-meta">إيجار:
                                    {{ $rentalWorkerStats['within_guarantee'] ?? 0 }}</span>
                            </button>

                            <button type="button" class="compact-stat stats-filter-card" data-status="expired_guarantee">
                                <span class="compact-stat-label">
                                    <i class="fas fa-exclamation-triangle text-danger"></i> منتهي الضمان
                                </span>
                                <span class="compact-stat-value">{{ $expiredGuarantee ?? 0 }}</span>
                                <span class="compact-stat-meta">إيجار:
                                    {{ $rentalWorkerStats['expired_guarantee'] ?? 0 }}</span>
                            </button>

                            <button type="button" class="compact-stat compact-stat-rental stats-filter-card"
                                data-service-category="rental" data-work-willingness="yes">
                                <span class="compact-stat-label">
                                    <i class="fas fa-user-check text-success"></i> إيجار جاهزات
                                </span>
                                <span class="compact-stat-value">{{ $rentalWorkerStats['ready'] ?? 0 }}</span>
                                <span class="compact-stat-meta">ترغب بالعمل من عاملات الإيجار</span>
                            </button>

                            <button type="button" class="compact-stat compact-stat-rental stats-filter-card"
                                data-service-category="rental" data-instant-receipt="yes">
                                <span class="compact-stat-label">
                                    <i class="fas fa-bolt text-warning"></i> إيجار فوري
                                </span>
                                <span class="compact-stat-value">{{ $rentalWorkerStats['instant'] ?? 0 }}</span>
                                <span class="compact-stat-meta">متاحة للاستلام الفوري</span>
                            </button>

                            <button type="button" class="compact-stat compact-stat-rental stats-filter-card"
                                data-service-category="rental" data-work-willingness="no">
                                <span class="compact-stat-label">
                                    <i class="fas fa-plane-departure text-warning"></i> إيجار ترحيل
                                </span>
                                <span class="compact-stat-value">{{ $rentalWorkerStats['deportation'] ?? 0 }}</span>
                                <span class="compact-stat-meta">لا ترغب بالعمل</span>
                            </button>
                        </div>
                    </div>

                    <div class="quick-tags-panel px-3 py-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h6 class="mb-0 small fw-bold">التصنيفات السريعة</h6>
                            <span class="badge bg-warning text-dark">بدون تاريخ وصول: {{ $guaranteeUnknown ?? 0 }}</span>
                        </div>

                        <div class="quick-tags-strip">
                            <button type="button" class="btn btn-sm btn-outline-dark quick-filter-chip active"
                                data-tag="">
                                الكل
                            </button>
                            @forelse ($quickTagStats as $tagStat)
                                <button type="button" class="btn btn-sm btn-outline-primary quick-filter-chip"
                                    data-tag="{{ $tagStat['name'] }}">
                                    {{ $tagStat['name'] }}
                                    <span class="badge bg-light text-dark ms-1">{{ $tagStat['count'] }}</span>
                                </button>
                            @empty
                                <span class="text-muted small">لا توجد تصنيفات سريعة مضافة حتى الآن.</span>
                            @endforelse
                        </div>
                    </div>
                </div>


                <!-- Filter Section -->
                <div class="card filter-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;"
                        data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <h6 class="m-0 font-weight-bold ">
                            <i class="fas fa-filter me-2"></i>{{ helperTrans('admin.filters') }}
                        </h6>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="collapse" id="filterCollapse">
                        <div class="card-body">
                            <div class="row g-3">
                                @if (($branchSelector['enabled'] ?? false) && ($branchSelector['can_view_all_branches'] ?? false))
                                    <div class="col-md-3">
                                        <label for="filterBranch" class="form-label">الفرع</label>
                                        <select id="filterBranch" class="form-select">
                                            <option value="">جميع الفروع</option>
                                            @foreach ($branchSelector['branches'] as $branch)
                                                <option value="{{ $branch->id }}"
                                                    @selected((int) ($branchSelector['selected_branch_id'] ?? 0) === (int) $branch->id)>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-3">
                                    <label for="filterContractNumber" class="form-label">رقم العقد</label>
                                    <input type="text" id="filterContractNumber" class="form-control"
                                        placeholder="ابحث برقم العقد">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterMaidName" class="form-label">اسم / رقم العاملة</label>
                                    <input type="text" id="filterMaidName" class="form-control"
                                        placeholder="ابحث باسم أو رقم العاملة">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterPassport" class="form-label">رقم الجواز</label>
                                    <input type="text" id="filterPassport" class="form-control"
                                        placeholder="ابحث برقم الجواز">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterMaidType" class="form-label">نوع العاملة</label>
                                    <select id="filterMaidType" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach ($jobTypes as $type)
                                            @php
                                                $locale = app()->getLocale();
                                                $translation = $type->translations
                                                    ->where('locale', $locale)
                                                    ->first();
                                                $title = $translation ? $translation->title : $type->title;
                                            @endphp
                                            <option value="{{ $type->id }}">{{ $title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterNationality" class="form-label">الجنسية</label>
                                    <select id="filterNationality" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach ($nationalities as $nationality)
                                            @php
                                                $locale = app()->getLocale();
                                                $translation = $nationality->translations
                                                    ->where('locale', $locale)
                                                    ->first();
                                                $name = $translation
                                                    ? $translation->sub_title
                                                    : $nationality->title ?? 'غير محدد';
                                            @endphp
                                            <option value="{{ $nationality->id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterShelter" class="form-label">مكان الإيواء</label>
                                    <select id="filterShelter" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach ($shelters as $shelter)
                                            @php
                                                $locale = app()->getLocale();
                                                $translation = $shelter->translations
                                                    ->where('locale', $locale)
                                                    ->first();
                                                $name = $translation
                                                    ? $translation->title
                                                    : $shelter->title ?? 'غير محدد';
                                            @endphp
                                            <option value="{{ $shelter->id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterReceiptSource" class="form-label">مصدر الاستلام</label>
                                    <select id="filterReceiptSource" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach (__('shelter_worker_statuses.received_by') ?? [] as $k => $name)
                                            <option value="{{ $k }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterStatus" class="form-label">حالة الضمان</label>
                                    <select id="filterStatus" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="within_guarantee">ضمن الضمان</option>
                                        <option value="expired_guarantee">منتهي الضمان</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterWorkWillingness" class="form-label">رغبة العمل</label>
                                    <select id="filterWorkWillingness" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="yes">ترغب بالعمل</option>
                                        <option value="no">لا ترغب بالعمل</option>
                                        <option value="unknown">غير محدد</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterArrivalDate" class="form-label">تاريخ الوصول</label>
                                    <input type="date" id="filterArrivalDate" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterReturnDate" class="form-label">تاريخ إدخال الإيواء</label>
                                    <input type="date" id="filterReturnDate" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label for="filterInstantReceipt" class="form-label">الاستلام الفوري</label>
                                    <select id="filterInstantReceipt" class="form-select">
                                        <option value="">جميع العاملات</option>
                                        <option value="yes">متاحة للاستلام الفوري</option>
                                        <option value="no">غير متاحة</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterQuickTag" class="form-label">التصنيف السريع</label>
                                    <select id="filterQuickTag" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach ($quickTagSuggestions as $tag)
                                            <option value="{{ $tag }}">{{ $tag }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterServiceCategory" class="form-label">تصنيف الخدمة</label>
                                    <select id="filterServiceCategory" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="recruitment">استقدام</option>
                                        <option value="rental">تأجير</option>
                                        <option value="transfer">نقل خدمات</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterRequestType"
                                        class="form-label">{{ helperTrans('ingaz.request_type') }}</label>
                                    <select id="filterRequestType" class="form-select">
                                        <option value="">{{ helperTrans('ingaz.all') }}</option>
                                        @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end filter-actions d-flex flex-wrap justify-content-end gap-2">
                                    <button type="button" id="clearFilters" class="btn btn-secondary me-2">
                                        <i class="fas fa-times me-1"></i> مسح الفلاتر
                                    </button>
                                    <button type="button" id="applyFilters" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> بحث
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول العقود -->
                <div class="card shadow table-card">
                    {{-- <div class="card-body border-bottom">
                        {{-- <div class="alert column-explanation mb-0">
                            <strong class="d-block mb-2">توضيح الأعمدة</strong>
                            <div>مدة التسكين داخل الإيواء: من تاريخ إدخال العاملة إلى الإيواء وحتى اليوم.</div>
                            <div>مدة بقائها داخل المملكة: من تاريخ وصولها للمملكة وحتى اليوم.</div>
                            <div>تاريخ إدخال الإيواء: هو تاريخ مراجعتها وإيداعها داخل الإيواء.</div>
                        </div> --}}
                    {{-- </div> --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="shelterContractsTable" class="table table-hover align-middle mb-0 table-style"
                                style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم العقد</th>
                                        <th>بيانات العاملة</th>
                                        <th>العميل</th>
                                        <th>الجنسية</th>
                                        <th>مكان الإيواء</th>
                                        <th>تاريخ الوصول للمملكة</th>
                                        <th>تاريخ إدخال الإيواء</th>
                                        <th>مدة داخل المملكة</th>
                                        <th>مدة داخل الإيواء</th>
                                        <th>الحالة</th>
                                        <th>التصنيفات السريعة</th>
                                        <th>الاستلام الفوري</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>



            </div>

            <div class="modal fade" id="livingExpenseModal" tabindex="-1" aria-labelledby="livingExpenseModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="livingExpenseForm">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="livingExpenseModalLabel">تسجيل مصروف إعاشة للإيواء</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingExpenseDate">التاريخ</label>
                                        <input type="date" id="livingExpenseDate" name="expense_date"
                                            class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingExpenseShelter">مكان الإيواء</label>
                                        <select id="livingExpenseShelter" name="shelter_id" class="form-select">
                                            <option value="">كل الإيواء</option>
                                            @foreach ($shelters as $shelter)
                                                @php
                                                    $translation = $shelter->translations
                                                        ->where('locale', app()->getLocale())
                                                        ->first();
                                                    $name = $translation ? $translation->title : $shelter->title ?? 'غير محدد';
                                                @endphp
                                                <option value="{{ $shelter->id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingExpenseTitle">البند</label>
                                        <input type="text" id="livingExpenseTitle" name="title" class="form-control"
                                            value="إعاشة الإيواء">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingExpenseAmount">إجمالي المصروف</label>
                                        <input type="number" step="0.01" min="0.01" id="livingExpenseAmount"
                                            name="total_amount" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="livingExpenseContract">العاملة</label>
                                        <select id="livingExpenseContract" name="contract_id" class="form-select">
                                            <option value="">كل العاملات المطابقات</option>
                                            @foreach ($livingExpenseWorkerOptions as $worker)
                                                <option value="{{ $worker['contract_id'] }}"
                                                    data-shelter-id="{{ $worker['shelter_id'] ?? '' }}">
                                                    {{ $worker['label'] }} - {{ $worker['shelter'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">اتركها فارغة لتوزيع المبلغ على كل العاملات داخل الإيواء المختار.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="livingExpenseAccount">حساب مصروف الإعاشة</label>
                                        <select id="livingExpenseAccount" name="expense_account_id"
                                            class="form-select living-expense-searchable" required>
                                            <option value="">اختر الحساب</option>
                                            @foreach ($livingExpenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ (string) ($defaultLivingExpenseAccountId ?? '') === (string) $account->id ? 'selected' : '' }}>
                                                    {{ $account->code }} / {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">يمكن البحث باسم الحساب أو رقمه، وضبط الحساب الافتراضي من الإعدادات المحاسبية.</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingPaymentSourceType">مصدر الصرف</label>
                                        <select id="livingPaymentSourceType" name="payment_source_type"
                                            class="form-select" required>
                                            @if ($paymentAccounts->isNotEmpty())
                                                <option value="bank">حساب بنكي أو صندوق</option>
                                            @endif
                                            @if ($custodyAccounts->isNotEmpty())
                                                <option value="custody" {{ $paymentAccounts->isEmpty() ? 'selected' : '' }}>
                                                    عهدة على موظف
                                                </option>
                                            @endif
                                        </select>
                                        @if ($paymentAccounts->isEmpty() && $custodyAccounts->isEmpty())
                                            <small class="text-danger">لا يوجد حساب صرف أو عهدة موظف نشطة.</small>
                                        @endif
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="livingPaymentAccount">حساب الصرف</label>
                                        <select id="livingPaymentAccount" name="payment_account_id"
                                            class="form-select living-expense-searchable" required>
                                            <option value="">اختر الحساب</option>
                                            @foreach ($paymentAccounts as $account)
                                                <option value="{{ $account->id }}" data-source-type="bank"
                                                    {{ (string) ($defaultPaymentAccountId ?? '') === (string) $account->id ? 'selected' : '' }}>
                                                    {{ $account->code }} / {{ $account->name }}
                                                </option>
                                            @endforeach
                                            @foreach ($custodyAccounts as $account)
                                                <option value="{{ $account->id }}" data-source-type="custody">
                                                    {{ $account->code }} / {{ $account->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">يمكن البحث باسم الحساب أو رقمه.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="livingExpenseNotes">ملاحظات</label>
                                        <textarea id="livingExpenseNotes" name="notes" rows="2" class="form-control"></textarea>
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="mb-0">آخر مصروفات الإعاشة</h6>
                                    <button type="button" id="refreshLivingExpenses" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>التاريخ</th>
                                                <th>الإيواء</th>
                                                <th>التخصيص</th>
                                                <th>الإجمالي</th>
                                                <th>عدد العاملات</th>
                                                <th>نصيب العاملة</th>
                                                <th>مصدر الصرف</th>
                                                <th>القيد</th>
                                            </tr>
                                        </thead>
                                        <tbody id="livingExpensesRows">
                                            <tr>
                                                <td colspan="8" class="text-muted text-center">لم يتم التحميل بعد</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i> حفظ وتوزيع الإعاشة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog"
                aria-labelledby="updateStatusModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateStatusModalLabel">تحديث الحالة</h5>
                            <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="rowId" name="rowId">
                            <input type="hidden" id="status" name="status">
                            <div class="form-group">
                                <label for="statusDate">تاريخ الحالة:</label>
                                <input type="date" id="statusDate" required name="statusDate" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                            <button type="button" class="btn btn-primary" id="saveStatusBtn">حفظ</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="confirmBackModal" tabindex="-1" role="dialog"
                aria-labelledby="updateStatusModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateStatusModalLabel">ارجاع العقد</h5>
                            <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="contract_id" name="id">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                هل انت متأكد من ارجاع العقد ؟
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                            <button type="button" class="btn btn-danger" id="confirmBackBtn">تأكيد الإرجاع</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Work Desire Modal -->
            <div class="modal fade" id="changeWorkDesireModal" tabindex="-1" role="dialog"
                aria-labelledby="changeWorkDesireModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeWorkDesireModalLabel">تغيير رغبة العمل</h5>
                            <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="changeWorkDesireForm">
                                <input type="hidden" id="workDesireContractId" name="contract_id">

                                <div class="mb-3">
                                    <label class="form-label"><strong>الرغبة الحالية:</strong></label>
                                    <div id="currentWorkDesire" class="alert alert-info"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="newWorkDesire" class="form-label">الرغبة الجديدة <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="newWorkDesire" name="wants_to_work" required>
                                        <option value="">اختر رغبة العمل</option>
                                        <option value="yes">ترغب بالعمل</option>
                                        <option value="no">لا ترغب بالعمل</option>
                                        <option value="unknown">غير محدد</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="changeReason" class="form-label">سبب التغيير (اختياري)</label>
                                    <textarea class="form-control" id="changeReason" name="reason" rows="3"
                                        placeholder="اكتب سبب تغيير رغبة العمل..."></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" id="saveWorkDesireBtn">حفظ التغيير</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="quickTagsModal" tabindex="-1" role="dialog"
                aria-labelledby="quickTagsModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="quickTagsModalLabel">التصنيفات السريعة</h5>
                            <button type="button" class="close btn" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="quickTagsForm">
                                <input type="hidden" id="quickTagsContractId" name="contract_id">
                                <div class="mb-3">
                                    <label for="quickTagsInput" class="form-label">أدخل التصنيفات مفصولة بفاصلة</label>
                                    <input type="text" class="form-control" id="quickTagsInput"
                                        name="shelter_quick_tags" list="quickTagsSuggestions"
                                        placeholder="مثال: جاهزة للنقل, جاهزة للإيجار">
                                    <datalist id="quickTagsSuggestions">
                                        @foreach ($quickTagSuggestions as $tag)
                                            <option value="{{ $tag }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted d-block mt-2">يمكن إدخال أكثر من تصنيف باستخدام الفاصلة
                                        العربية أو الإنجليزية.</small>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" id="saveQuickTagsBtn">حفظ التصنيفات</button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            @include('components.no_permissions')
        @endif
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>

    <script>
        // التحقق من وجود jQuery
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
        } else {
            console.log('jQuery version:', jQuery.fn.jquery);
        }

        // الانتظار حتى يتم تحميل الصفحة بالكامل
        window.addEventListener('load', function() {
            console.log('Window loaded - checking for DataTable');

            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTable plugin is not loaded!');
                return;
            }

            console.log('DataTable plugin is loaded');

            // طباعة URL للتحقق
            const dataUrl = '{{ route('ShelterContract.data') }}';
            console.log('Data URL:', dataUrl);

            // Initialize DataTable
            const dataTable = $('#shelterContractsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        type: 'inline',
                        target: 0
                    }
                },
                autoWidth: false,
                scrollX: false,
                searching: false,
                ajax: {
                    url: '{{ route('ShelterContract.data') }}',
                    type: 'GET',
                    cache: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: function(d) {
                        console.log('Sending request with data:', d);
                        // إضافة الفلاتر
                        d.contract_number = $('#filterContractNumber').val();
                        d.maid_name = $('#filterMaidName').val();
                        d.passport_num = $('#filterPassport').val();
                        d.maid_type = $('#filterMaidType').val();
                        d.nationality = $('#filterNationality').val();
                        d.shelter = $('#filterShelter').val();
                        d.receipt_source = $('#filterReceiptSource').val();
                        d.status = $('#filterStatus').val();
                        d.work_willingness = $('#filterWorkWillingness').val();
                        d.arrival_date = $('#filterArrivalDate').val();
                        d.return_date = $('#filterReturnDate').val();
                        d.instant_receipt = $('#filterInstantReceipt').val();
                        d.quick_tag = $('#filterQuickTag').val();
                        d.service_category = $('#filterServiceCategory').val();
                        d.request_type = $('#filterRequestType').val();
                        d.branch_id = $('#filterBranch').length ? $('#filterBranch').val() : '';
                        d._timestamp = new Date().getTime(); // منع cache
                    },
                    dataSrc: function(json) {
                        console.log('Received data:', json);
                        console.log('Data array:', json.data);
                        if (json.data && json.data.length > 0) {
                            console.log('First row columns:', Object.keys(json.data[0]));
                        }
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error);
                        console.error('XHR status:', xhr.status);
                        console.error('XHR response:', xhr.responseText);
                        console.error('Thrown error:', thrown);

                        // تحليل الخطأ
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.error('Parsed error response:', response);
                            Swal.fire('خطأ!', response.message || 'حدث خطأ في تحميل البيانات', 'error');
                        } catch (e) {
                            console.error('Could not parse error response');
                            Swal.fire('خطأ!', 'حدث خطأ في تحميل البيانات', 'error');
                        }
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'contract_number',
                        name: 'id'
                    },
                    {
                        data: 'maid_data',
                        name: 'maid_data',
                        orderable: false
                    },
                    {
                        data: 'client',
                        name: 'client',
                        orderable: false
                    },
                    {
                        data: 'nationality',
                        name: 'nationality',
                        orderable: false
                    },
                    {
                        data: 'shelter',
                        name: 'shelter',
                        orderable: false
                    },
                    {
                        data: 'arrival_date',
                        name: 'arrival_date'
                    },
                    {
                        data: 'shelter_entry_date',
                        name: 'shelter_entry_date',
                        orderable: false
                    },
                    {
                        data: 'kingdom_duration',
                        name: 'kingdom_duration',
                        orderable: false
                    },
                    {
                        data: 'shelter_duration',
                        name: 'shelter_duration',
                        orderable: false
                    },
                    {
                        data: 'status_summary',
                        name: 'status_summary',
                        orderable: false
                    },
                    {
                        data: 'quick_tags',
                        name: 'quick_tags',
                        orderable: false
                    },
                    {
                        data: 'instant_receipt_availability',
                        name: 'instant_receipt_availability',
                        orderable: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [{
                        targets: 0,
                        responsivePriority: 1
                    },
                    {
                        targets: 1,
                        responsivePriority: 2
                    },
                    {
                        targets: 2,
                        responsivePriority: 3
                    },
                    {
                        targets: 10,
                        responsivePriority: 4
                    },
                    {
                        targets: 13,
                        responsivePriority: 5
                    },
                    {
                        targets: 3,
                        responsivePriority: 6
                    },
                    {
                        targets: 11,
                        responsivePriority: 7
                    },
                    {
                        targets: [4, 5],
                        responsivePriority: 30
                    },
                    {
                        targets: [6, 7, 8, 9, 12],
                        responsivePriority: 100
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    console.log('Row created with data:', data);
                },
                drawCallback: function(settings) {
                    var api = this.api();
                    console.log('Table drawn. Total rows:', api.rows().count());
                    console.log('Displayed rows:', api.rows({
                        page: 'current'
                    }).count());
                },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
                },
                order: [
                    [1, 'desc']
                ],
                pageLength: 25,
                dom: 'lrtip'
            });

            $('#filterCollapse').on('shown.bs.collapse hidden.bs.collapse', function() {
                dataTable.columns.adjust().responsive.recalc();
            });

            $(window).on('resize orientationchange', function() {
                dataTable.columns.adjust().responsive.recalc();
            });

            // Apply filters
            $('#applyFilters').on('click', function() {
                dataTable.ajax.reload();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#filterContractNumber').val('');
                $('#filterMaidName').val('');
                $('#filterPassport').val('');
                $('#filterMaidType').val('');
                $('#filterNationality').val('');
                $('#filterShelter').val('');
                $('#filterReceiptSource').val('');
                $('#filterStatus').val('');
                $('#filterWorkWillingness').val('');
                $('#filterArrivalDate').val('');
                $('#filterReturnDate').val('');
                $('#filterInstantReceipt').val('');
                $('#filterQuickTag').val('');
                $('#filterServiceCategory').val('');
                $('#filterRequestType').val('');
                $('.quick-filter-chip').removeClass('active');
                $('.quick-filter-chip[data-tag=""]').addClass('active');
                dataTable.ajax.reload();
            });

            $('.quick-filter-chip').on('click', function() {
                const tag = $(this).data('tag') || '';
                $('.quick-filter-chip').removeClass('active');
                $(this).addClass('active');
                $('#filterQuickTag').val(tag);
                dataTable.ajax.reload();
            });

            $('#filterQuickTag').on('change', function() {
                const tag = $(this).val() || '';
                $('.quick-filter-chip').removeClass('active');
                $('.quick-filter-chip').filter(function() {
                    return ($(this).data('tag') || '') === tag;
                }).addClass('active');
            });

            function buildExportUrl(format) {
                const filters = {
                    contract_number: $('#filterContractNumber').val(),
                    maid_name: $('#filterMaidName').val(),
                    passport_num: $('#filterPassport').val(),
                    maid_type: $('#filterMaidType').val(),
                    nationality: $('#filterNationality').val(),
                    shelter: $('#filterShelter').val(),
                    receipt_source: $('#filterReceiptSource').val(),
                    status: $('#filterStatus').val(),
                    work_willingness: $('#filterWorkWillingness').val(),
                    arrival_date: $('#filterArrivalDate').val(),
                    return_date: $('#filterReturnDate').val(),
                    instant_receipt: $('#filterInstantReceipt').val(),
                    quick_tag: $('#filterQuickTag').val(),
                    service_category: $('#filterServiceCategory').val(),
                    request_type: $('#filterRequestType').val(),
                    branch_id: $('#filterBranch').length ? $('#filterBranch').val() : ''
                };

                return '{{ route('ShelterContract.export', ['format' => '__FORMAT__']) }}'
                    .replace('__FORMAT__', format) + '?' + $.param(filters);
            }

            $('#exportExcel').on('click', function() {
                window.open(buildExportUrl('excel'), '_blank');
            });

            $('#exportPdf').on('click', function() {
                window.open(buildExportUrl('pdf'), '_blank');
            });

            $('.stats-filter-card').on('click', function() {
                if ($(this).data('clear-filters') == 1) {
                    $('#clearFilters').trigger('click');
                    return;
                }

                $('#filterContractNumber').val('');
                $('#filterNationality').val('');
                $('#filterShelter').val('');
                $('#filterReceiptSource').val('');
                $('#filterStatus').val('');
                $('#filterWorkWillingness').val('');
                $('#filterArrivalDate').val('');
                $('#filterReturnDate').val('');
                $('#filterInstantReceipt').val('');
                $('#filterQuickTag').val('');
                $('#filterServiceCategory').val('');
                $('#filterRequestType').val('');
                $('.quick-filter-chip').removeClass('active');
                $('.quick-filter-chip[data-tag=""]').addClass('active');

                $('#filterServiceCategory').val($(this).data('service-category') || '');
                $('#filterStatus').val($(this).data('status') || '');
                $('#filterWorkWillingness').val($(this).data('work-willingness') || '');
                $('#filterInstantReceipt').val($(this).data('instant-receipt') || '');
                dataTable.ajax.reload();
            });

            $('#filterBranch').on('change', function() {
                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set('branch_id', this.value);
                } else {
                    url.searchParams.delete('branch_id');
                }
                window.location.href = url.toString();
            });

            $(document).on('click', '.report-escape', function(e) {
                e.preventDefault();
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'إضافة بلاغ هروب',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-warning small">
                                سيخرج العقد من الإيواء ولن يكون متاحاً لنقل الكفالة أو الترحيل.
                            </div>
                            <label for="escapeReportNote" class="form-label">الملاحظة</label>
                            <textarea id="escapeReportNote" class="form-control mb-3" rows="4" maxlength="3000"
                                placeholder="اكتب تفاصيل البلاغ"></textarea>
                            <label for="escapeReportAttachment" class="form-label">المرفق</label>
                            <input id="escapeReportAttachment" type="file" class="form-control"
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            <small class="text-muted">يجب إدخال ملاحظة أو اختيار ملف واحد على الأقل.</small>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'تسجيل بلاغ الهروب',
                    cancelButtonText: 'إلغاء',
                    focusConfirm: false,
                    preConfirm: () => {
                        const note = document.getElementById('escapeReportNote').value.trim();
                        const fileInput = document.getElementById('escapeReportAttachment');

                        if (!note && !fileInput.files.length) {
                            Swal.showValidationMessage('يجب كتابة ملاحظة أو إرفاق ملف.');
                            return false;
                        }

                        return { note, file: fileInput.files[0] || null };
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('_token', '{{ csrf_token() }}');
                    formData.append('note', result.value.note);
                    if (result.value.file) {
                        formData.append('attachment', result.value.file);
                    }

                    Swal.fire({
                        title: 'جارٍ تسجيل البلاغ...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    $.ajax({
                        url: '{{ route('ShelterContract.escapeReport', ':id') }}'.replace(':id', contractId),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            Swal.fire('تم!', response.message, 'success').then(() => {
                                window.location.href = response.redirect || '{{ route('ShelterContract.outside') }}';
                            });
                        },
                        error: function(xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const firstError = errors ? Object.values(errors).flat()[0] : null;
                            Swal.fire('خطأ!', firstError || xhr.responseJSON?.message ||
                                'تعذر تسجيل بلاغ الهروب.', 'error');
                        }
                    });
                });
            });

            // Handle action buttons
            $(document).on('click', '.confirm-action', function(e) {
                // console.log("Amer");

                e.preventDefault();
                const action = $(this).data('action');
                const id = $(this).data('id');
                const workflow = $(this).data('workflow') || 'standard';
                const text = $(this).data('text') || 'هل أنت متأكد من هذا الإجراء؟';

                Swal.fire({
                    title: 'تأكيد',
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'نعم، تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // تنفيذ الإجراء
                        performAction(action, id, workflow);
                    }
                });
            });

            function performAction(action, id, workflow) {
                let url = '';
                let method = 'POST';

                switch (action) {
                    case 'back':
                        url = '{{ route('ShelterContract.back', ':id') }}'.replace(':id', id);
                        break;
                    case 'transfer':
                        // فتح صفحة إضافة طلب نقل خدمات
                        window.location.href = '{{ route('SponsorshipTransfer.create') }}?contract_id=' + id + '&workflow_source=' + encodeURIComponent(workflow);
                        return;
                    case 'vacation':
                        url = '{{ route('ShelterContract.vacation', ':id') }}'.replace(':id', id);
                        break;
                    case 'start-deportation':
                        $.ajax({
                            url: '{{ route('ShelterContract.deportation.start', ':id') }}'
                                .replace(':id', id),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'نجح!',
                                    text: response.message ||
                                        'تم بدء إجراءات الترحيل بنجاح',
                                    icon: 'success',
                                    confirmButtonText: 'حسناً'
                                }).then(() => {
                                    window.location.href =
                                        '{{ route('ShelterContract.deportation.procedures', ':id') }}'
                                        .replace(':id', id);
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message ||
                                    'حدث خطأ أثناء بدء إجراءات الترحيل', 'error'
                                );
                            }
                        });
                        return;
                    case 'confirm-procedures':
                        $.ajax({
                            url: '{{ route('ShelterContract.deportation.update', ':id') }}'
                                .replace(':id', id),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                confirm_procedures: true
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'نجح!',
                                    text: response.message || 'تم تأكيد اكتمال الإجراءات بنجاح',
                                    icon: 'success',
                                    confirmButtonText: 'حسناً'
                                }).then(() => {
                                    dataTable.ajax.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message ||
                                    'حدث خطأ أثناء تأكيد الإجراءات', 'error');
                            }
                        });
                        return;
                    case 'complete-deportation':
                        $.ajax({
                            url: '{{ route('ShelterContract.deportation.update', ':id') }}'
                                .replace(':id', id),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                complete_deportation: true
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: 'نجح!',
                                    text: response.message || 'تم إكمال إجراءات الترحيل بنجاح',
                                    icon: 'success',
                                    confirmButtonText: 'حسناً'
                                }).then(() => {
                                    if (response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        dataTable.ajax.reload();
                                    }
                                });
                            },
                            error: function(xhr) {
                                Swal.fire('خطأ!', xhr.responseJSON?.message ||
                                    'حدث خطأ أثناء إكمال الترحيل', 'error');
                            }
                        });
                        return;
                    default:
                        return;
                }

                $.ajax({
                    url: url,
                    type: method,
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('نجح!', response.message || 'تم تنفيذ العملية بنجاح', 'success');
                        dataTable.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء تنفيذ العملية',
                            'error');
                    }
                });
            }

            // Handle change work desire action
            $(document).on('click', '.change-work-desire', function(e) {
                e.preventDefault();
                const contractId = $(this).data('id');
                const currentDesire = $(this).data('current') || 'unknown';

                // Set values in modal
                $('#workDesireContractId').val(contractId);

                // Display current desire
                let currentDesireText = '';
                let badgeClass = '';
                switch (currentDesire) {
                    case 'yes':
                        currentDesireText = 'ترغب بالعمل';
                        badgeClass = 'bg-success';
                        break;
                    case 'no':
                        currentDesireText = 'لا ترغب بالعمل';
                        badgeClass = 'bg-warning';
                        break;
                    default:
                        currentDesireText = 'غير محدد';
                        badgeClass = 'bg-secondary';
                }

                $('#currentWorkDesire').html('<span class="badge ' + badgeClass + '">' + currentDesireText +
                    '</span>');

                // Reset form
                $('#newWorkDesire').val('');
                $('#changeReason').val('');

                // Show modal
                $('#changeWorkDesireModal').modal('show');
            });

            $(document).on('click', '.edit-quick-tags', function(e) {
                e.preventDefault();
                $('#quickTagsContractId').val($(this).data('id'));
                $('#quickTagsInput').val($(this).data('tags') || '');
                $('#quickTagsModal').modal('show');
            });

            $('#saveQuickTagsBtn').on('click', function() {
                const contractId = $('#quickTagsContractId').val();
                $.ajax({
                    url: '{{ route('ShelterContract.quickTags', ':id') }}'.replace(':id',
                        contractId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        shelter_quick_tags: $('#quickTagsInput').val()
                    },
                    success: function(response) {
                        $('#quickTagsModal').modal('hide');
                        Swal.fire('نجح!', response.message || 'تم حفظ التصنيفات السريعة',
                            'success');
                        dataTable.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', xhr.responseJSON?.message ||
                            'حدث خطأ أثناء حفظ التصنيفات السريعة', 'error');
                    }
                });
            });

            // Handle save work desire
            $('#saveWorkDesireBtn').on('click', function() {
                const contractId = $('#workDesireContractId').val();
                const newDesire = $('#newWorkDesire').val();
                const reason = $('#changeReason').val();

                // Validation
                if (!newDesire) {
                    Swal.fire('خطأ!', 'يرجى اختيار رغبة العمل الجديدة', 'error');
                    return;
                }

                // Show confirmation
                Swal.fire({
                    title: 'تأكيد تغيير رغبة العمل',
                    text: 'هل أنت متأكد من تغيير رغبة العمل؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تغيير',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Perform the change
                        $.ajax({
                            url: '{{ route('ShelterContract.changeWorkDesire', ':id') }}'
                                .replace(':id', contractId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                wants_to_work: newDesire,
                                reason: reason
                            },
                            success: function(response) {
                                $('#changeWorkDesireModal').modal('hide');
                                Swal.fire('نجح!', response.message ||
                                    'تم تغيير رغبة العمل بنجاح', 'success');
                                dataTable.ajax.reload();
                            },
                            error: function(xhr) {
                                let errorMessage = 'حدث خطأ أثناء تغيير رغبة العمل';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('خطأ!', errorMessage, 'error');
                            }
                        });
                    }
                });
            });

            // Handle toggle instant receipt action
            $(document).on('click', '.toggle-instant-receipt', function(e) {
                e.preventDefault();
                const contractId = $(this).data('id');
                const currentStatus = $(this).data('current');
                const actionText = $(this).data('text');

                // Show confirmation
                Swal.fire({
                    title: 'تأكيد تغيير حالة الاستلام الفوري',
                    text: actionText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تأكيد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Perform the toggle
                        $.ajax({
                            url: '{{ route('ShelterContract.toggleInstantReceipt', ':id') }}'
                                .replace(':id', contractId),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('نجح!', response.message ||
                                    'تم تغيير حالة الاستلام الفوري بنجاح', 'success'
                                );
                                dataTable.ajax.reload();
                            },
                            error: function(xhr) {
                                let errorMessage =
                                    'حدث خطأ أثناء تغيير حالة الاستلام الفوري';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire('خطأ!', errorMessage, 'error');
                            }
                        });
                    }
                });
            });

            const livingExpenseModal = $('#livingExpenseModal');

            $('.living-expense-searchable').select2({
                dropdownParent: livingExpenseModal,
                width: '100%',
                language: {
                    noResults: function() {
                        return 'لا توجد نتائج مطابقة';
                    },
                    searching: function() {
                        return 'جاري البحث...';
                    }
                }
            });

            function filterLivingPaymentAccounts() {
                const sourceType = String($('#livingPaymentSourceType').val() || '');
                const paymentAccountSelect = $('#livingPaymentAccount');
                const selectedOption = paymentAccountSelect.find('option:selected');

                paymentAccountSelect.find('option').each(function() {
                    const option = $(this);
                    const optionSourceType = String(option.data('source-type') || '');
                    const isPlaceholder = option.val() === '';
                    const shouldShow = isPlaceholder || optionSourceType === sourceType;

                    option.prop('disabled', !shouldShow).prop('hidden', !shouldShow);
                });

                if (selectedOption.val() && String(selectedOption.data('source-type') || '') !== sourceType) {
                    paymentAccountSelect.val('');
                }

                paymentAccountSelect.trigger('change.select2');
            }

            function loadLivingExpenses() {
                $.get('{{ route('ShelterContract.livingExpenses') }}')
                    .done(function(response) {
                        const rows = response.data || [];
                        if (!rows.length) {
                            $('#livingExpensesRows').html(
                                '<tr><td colspan="8" class="text-muted text-center">لا توجد مصروفات إعاشة مسجلة</td></tr>'
                            );
                            return;
                        }

                        $('#livingExpensesRows').html(rows.map(function(row) {
                            const constraint = row.constraint_url ?
                                '<a href="' + row.constraint_url + '" target="_blank" class="btn btn-sm btn-outline-primary">عرض القيد</a>' :
                                '<span class="text-muted">بدون قيد</span>';
                            const paymentSource = (row.payment_source_type === 'custody' ?
                                    'عهدة موظف' : 'حساب بنكي أو صندوق') +
                                (row.payment_account ? '<br><small>' + row.payment_account + '</small>' : '');

                            return '<tr>' +
                                '<td>' + row.expense_date + '</td>' +
                                '<td>' + row.shelter + '</td>' +
                                '<td>' + row.target + '</td>' +
                                '<td>' + row.total_amount + '</td>' +
                                '<td>' + row.worker_count + '</td>' +
                                '<td>' + row.amount_per_worker + '</td>' +
                                '<td>' + paymentSource + '</td>' +
                                '<td>' + constraint + '</td>' +
                                '</tr>';
                        }).join(''));
                    })
                    .fail(function(xhr) {
                        $('#livingExpensesRows').html(
                            '<tr><td colspan="8" class="text-danger text-center">' +
                            (xhr.responseJSON?.message || 'تعذر تحميل مصروفات الإعاشة') +
                            '</td></tr>'
                        );
                    });
            }

            function filterLivingExpenseWorkers() {
                const shelterId = String($('#livingExpenseShelter').val() || '');
                const workerSelect = $('#livingExpenseContract');
                const currentValue = workerSelect.val();
                let currentStillVisible = !currentValue;

                workerSelect.find('option').each(function() {
                    const option = $(this);
                    const optionShelterId = String(option.data('shelter-id') || '');
                    const isEmptyOption = option.val() === '';
                    const shouldShow = isEmptyOption || !shelterId || optionShelterId === shelterId;

                    option.prop('hidden', !shouldShow).prop('disabled', !shouldShow);
                    if (shouldShow && option.val() === currentValue) {
                        currentStillVisible = true;
                    }
                });

                if (!currentStillVisible) {
                    workerSelect.val('');
                }
            }

            $('#openLivingExpenseModal').on('click', function() {
                livingExpenseModal.modal('show');
                filterLivingExpenseWorkers();
                loadLivingExpenses();
            });

            $('#refreshLivingExpenses').on('click', loadLivingExpenses);
            $('#livingExpenseShelter').on('change', filterLivingExpenseWorkers);
            $('#livingPaymentSourceType').on('change', filterLivingPaymentAccounts);
            filterLivingPaymentAccounts();

            $('#livingExpenseForm').on('submit', function(e) {
                e.preventDefault();

                const submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true);

                $.ajax({
                    url: '{{ route('ShelterContract.livingExpenses.store') }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        Swal.fire('نجح!', response.message || 'تم حفظ مصروف الإعاشة', 'success');
                        $('#livingExpenseAmount').val('');
                        $('#livingExpenseNotes').val('');
                        loadLivingExpenses();

                        if (response.constraint_url) {
                            window.open(response.constraint_url, '_blank');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('خطأ!', xhr.responseJSON?.message || 'حدث خطأ أثناء حفظ مصروف الإعاشة', 'error');
                    },
                    complete: function() {
                        submitButton.prop('disabled', false);
                    }
                });
            });
        }); // نهاية window.addEventListener
    </script>
@endsection
