@extends('admin.layout.indexs.index')

@section('style')
    <style>
        .reports-hero,
        .report-card,
        .report-filter-card {
            border: 1px solid #e8edf5;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
        }

        .reports-hero {
            background: linear-gradient(135deg, #f8fbff 0%, #fdfefe 100%);
        }

        .report-card {
            width: 100%;
            padding: 18px;
            text-align: right;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        }

        .report-card.active {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
        }

        .report-card__icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef4ff;
            color: #0d6efd;
            font-size: 1.2rem;
        }

        .report-card__title {
            font-size: 1.02rem;
            font-weight: 700;
            color: #111827;
        }

        .report-card__desc {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.7;
            margin: 0;
        }

        .report-filter-card .card-header {
            background: transparent;
            border-bottom: 1px solid #edf2f7;
        }

        .report-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 14px;
        }

        .report-note {
            border-radius: 14px;
            border: 1px dashed #d8e3f5;
            background: #f8fbff;
            color: #475569;
            padding: 12px 14px;
        }

        .report-note strong {
            color: #0f172a;
        }

        .report-filter-section .form-label {
            font-weight: 600;
            color: #334155;
        }

        @media (max-width: 767.98px) {
            .report-card {
                padding: 14px;
            }
        }
    </style>
    @if (\App\Support\SystemModuleManager::usesRedesign())
        <link rel="stylesheet" href="{{ asset('admin/custom/clickerp-reports.css') }}?v=1">
    @endif
@endsection

@section('page-title')
    إدارة التقارير
@endsection

@section('content')
    @php
        $canViewDataImports = AdminGuardHasAnyPerm([
            \App\PermKeys::DATA_IMPORT_UPLOAD,
            \App\PermKeys::DATA_IMPORT_REVIEW,
            \App\PermKeys::DATA_IMPORT_APPROVE,
        ]);
    @endphp
    <div class="page-body ck-reports-page">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4 reports-page-head">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        إدارة التقارير
                    </h3>
                    <p class="text-muted mb-0">فلترة أي قائمة وتصديرها من مكان واحد بصيغة PDF موحدة أو Excel.</p>
                </div>
                @if (\App\Support\SystemModuleManager::usesRedesign())
                    <div class="reports-head-actions">
                        <label class="reports-search">
                            <i class="fas fa-search"></i>
                            <input type="search" id="reportsCatalogSearch" placeholder="ابحث في {{ count($reports) }} تقريرًا..." autocomplete="off">
                        </label>
                        @if ($canViewDataImports)
                            <a href="{{ route('reports.import.index') }}" class="btn btn-primary mt-3 mt-sm-0">
                                <i class="fas fa-file-import me-1"></i>
                                استيراد البيانات
                            </a>
                        @endif
                    </div>
                @elseif ($canViewDataImports)
                    <a href="{{ route('reports.import.index') }}" class="btn btn-primary mt-3 mt-sm-0">
                        <i class="fas fa-file-import me-1"></i>
                        استيراد البيانات
                    </a>
                @endif
            </div>

            @if(isset($reports['recruitment_contracts']))
                <a href="{{ route('reports.arrivals') }}" class="btn btn-primary mb-3"><i class="fas fa-plane-arrival me-1"></i> تقرير وصول العاملات والإحصائيات الشهرية</a>
            @endif
            <div class="card reports-hero mb-4">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="text-muted small mb-1">التقرير المحدد حالياً</div>
                        <h4 class="mb-2" id="selectedReportLabel">{{ $reports[$selectedReport]['label'] }}</h4>
                        <p class="text-muted mb-0" id="selectedReportDescription">{{ $reports[$selectedReport]['description'] }}</p>
                    </div>
                    <div class="report-note">
                        <strong>PDF</strong> للطباعة الموحدة داخل النظام.
                        <br>
                        <strong>Excel</strong> مناسب أكثر للقوائم الكبيرة وسرعة التصدير.
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 reports-catalog" id="reportsCatalog">
                @foreach ($reports as $key => $report)
                    <div class="col-xl-4 col-md-6" data-report-catalog-item data-report-search="{{ $report['label'] }} {{ $report['description'] }}">
                        <button type="button"
                            class="report-card d-flex align-items-start gap-3 {{ $selectedReport === $key ? 'active' : '' }}"
                            data-report-card="{{ $key }}">
                            <span class="report-card__icon">
                                <i class="{{ $report['icon'] }}"></i>
                            </span>
                            <span class="text-start">
                                <span class="report-card__title d-block">{{ $report['label'] }}</span>
                                <span class="report-card__desc d-block mt-2">{{ $report['description'] }}</span>
                            </span>
                        </button>
                    </div>
                @endforeach
                @if (\App\Support\SystemModuleManager::usesRedesign())
                    <div class="col-12 d-none" id="reportsCatalogEmpty">
                        <div class="reports-empty"><i class="fas fa-search"></i><strong>لا توجد تقارير مطابقة</strong><span>جرّب كلمة بحث أخرى.</span></div>
                    </div>
                @endif
            </div>

            <div class="card report-filter-card">
                <div class="card-header py-3">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        إعدادات الفلترة والتصدير
                    </h6>
                </div>
                <div class="card-body">
                    <form id="reportExportForm" method="GET"
                        action="{{ route('reports.export', ['report' => $selectedReport]) }}">
                        <input type="hidden" name="format" id="reportFormat" value="pdf">

                        <div class="row g-3 mb-4">
                            <div class="col-lg-4">
                                <label for="reportSelector" class="form-label">نوع التقرير</label>
                                <select id="reportSelector" class="form-select">
                                    @foreach ($reports as $key => $report)
                                        <option value="{{ $key }}" {{ $selectedReport === $key ? 'selected' : '' }}>
                                            {{ $report['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'maids' ? '' : 'd-none' }}"
                            data-report-section="maids">
                            <div class="report-section-title">فلاتر السير الذاتية</div>
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label for="maids-keyword" class="form-label">كلمة البحث</label>
                                    <input type="text" class="form-control" id="maids-keyword" name="keyword"
                                        placeholder="الاسم أو رقم الجواز أو رقم السيرة">
                                </div>
                                <div class="col-lg-4">
                                    <label for="maids-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="maids-status" name="status">
                                        <option value="">جميع الحالات</option>
                                        @foreach ($maidStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label for="maids-job-type" class="form-label">نوع العمل</label>
                                    <select class="form-select" id="maids-job-type" name="job_type_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['job_types'] as $jobType)
                                            <option value="{{ $jobType->id }}">{{ $jobType->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label for="maids-request-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="maids-request-type" name="request_type">
                                        <option value="">الكل</option>
                                        @foreach ($requestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label for="maids-nationality" class="form-label">الجنسية</label>
                                    <select class="form-select" id="maids-nationality" name="nationality_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['nationalities'] as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->sub_title ?? ($nationality->title ?? $nationality->name ?? '-') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <label for="maids-office" class="form-label">المكتب الخارجي</label>
                                    <select class="form-select" id="maids-office" name="office_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['offices'] as $office)
                                            <option value="{{ $office->id }}">{{ $office->title ?? $office->name ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="maids-visibility" class="form-label">الظهور في الموقع</label>
                                    <select class="form-select" id="maids-visibility" name="visibility">
                                        <option value="">الكل</option>
                                        <option value="visible">ظاهرة</option>
                                        <option value="hidden">مخفية</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="maids-pinned" class="form-label">التثبيت</label>
                                    <select class="form-select" id="maids-pinned" name="pinned">
                                        <option value="">الكل</option>
                                        <option value="only">مثبتة فقط</option>
                                        <option value="unpinned">غير مثبتة</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="maids-date-from" class="form-label">تاريخ الإضافة من</label>
                                    <input type="date" class="form-control" id="maids-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="maids-date-to" class="form-label">تاريخ الإضافة إلى</label>
                                    <input type="date" class="form-control" id="maids-date-to" name="date_to">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'orders' ? '' : 'd-none' }}"
                            data-report-section="orders">
                            <div class="report-section-title">فلاتر طلبات الاستقدام</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="orders-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="orders-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($orderStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-from-date" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="orders-from-date" name="from_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-to-date" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="orders-to-date" name="to_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-admin" class="form-label">الموظف المسؤول</label>
                                    <select class="form-select" id="orders-admin" name="admin_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-nationality" class="form-label">الجنسية</label>
                                    <select class="form-select" id="orders-nationality" name="nationality_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['nationalities'] as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->sub_title ?? ($nationality->title ?? $nationality->name ?? '-') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-request-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="orders-request-type" name="request_type">
                                        <option value="">الكل</option>
                                        @foreach ($requestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-client-name" class="form-label">اسم العميل</label>
                                    <input type="text" class="form-control" id="orders-client-name" name="client_name"
                                        placeholder="ابحث باسم العميل">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-phone" class="form-label">رقم الجوال</label>
                                    <input type="text" class="form-control" id="orders-phone" name="phone"
                                        placeholder="05xxxxxxxx">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-passport" class="form-label">رقم جواز العاملة</label>
                                    <input type="text" class="form-control" id="orders-passport" name="passport_num"
                                        placeholder="رقم الجواز">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-office-name" class="form-label">اسم المكتب الخارجي</label>
                                    <input type="text" class="form-control" id="orders-office-name" name="office_name"
                                        placeholder="اسم المكتب">
                                </div>
                                <div class="col-lg-3">
                                    <label for="orders-timer-status" class="form-label">حالة المؤقت</label>
                                    <select class="form-select" id="orders-timer-status" name="timer_status">
                                        <option value="">الكل</option>
                                        <option value="normal">عادي</option>
                                        <option value="urgent">عاجل</option>
                                        <option value="overdue">متأخر</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'clients' ? '' : 'd-none' }}"
                            data-report-section="clients">
                            <div class="report-section-title">فلاتر العملاء</div>
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label for="clients-keyword" class="form-label">كلمة البحث</label>
                                    <input type="text" class="form-control" id="clients-keyword" name="keyword"
                                        placeholder="الاسم أو الجوال أو البريد أو الهوية">
                                </div>
                                <div class="col-lg-2">
                                    <label for="clients-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="clients-status" name="status">
                                        <option value="">الكل</option>
                                        @foreach ($clientStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="clients-nationality" class="form-label">الجنسية</label>
                                    <select class="form-select" id="clients-nationality" name="nationality_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['countries'] as $country)
                                            <option value="{{ $country->id }}">
                                                {{ $country->title ?? $country->name_ar ?? $country->name_en ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="clients-city" class="form-label">المدينة</label>
                                    <select class="form-select" id="clients-city" name="city">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['cities'] as $city)
                                            <option value="{{ $city->name ?? $city->name_ar ?? $city->name_en ?? '' }}">
                                                {{ $city->name ?? $city->name_ar ?? $city->name_en ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="clients-has-orders" class="form-label">وجود طلبات</label>
                                    <select class="form-select" id="clients-has-orders" name="has_orders">
                                        <option value="">الكل</option>
                                        <option value="with">لديهم طلبات</option>
                                        <option value="without">بدون طلبات</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="clients-date-from" class="form-label">من تاريخ التسجيل</label>
                                    <input type="date" class="form-control" id="clients-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="clients-date-to" class="form-label">إلى تاريخ التسجيل</label>
                                    <input type="date" class="form-control" id="clients-date-to" name="date_to">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'recruitment_contracts' ? '' : 'd-none' }}"
                            data-report-section="recruitment_contracts">
                            <div class="report-section-title">فلاتر عقود الاستقدام</div>
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <label for="rec-global-search" class="form-label">بحث شامل</label>
                                    <input type="text" class="form-control" id="rec-global-search" name="global_search"
                                        placeholder="هوية العميل أو الجوال أو رقم العقد أو رقم التأشيرة">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-status" class="form-label">حالة العقد</label>
                                    <select class="form-select" id="rec-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($recruitmentStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-warranty-status" class="form-label">حالة الضمان</label>
                                    <select class="form-select" id="rec-warranty-status" name="warranty_status">
                                        <option value="">الكل</option>
                                        @foreach ($warrantyStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-date-from" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="rec-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-date-to" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="rec-date-to" name="date_to">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-client-name" class="form-label">اسم العميل</label>
                                    <input type="text" class="form-control" id="rec-client-name" name="client_name">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-client-phone" class="form-label">رقم الجوال</label>
                                    <input type="text" class="form-control" id="rec-client-phone" name="client_phone">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-maid-name" class="form-label">اسم العاملة</label>
                                    <input type="text" class="form-control" id="rec-maid-name" name="maid_name">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-maid-passport" class="form-label">رقم الجواز</label>
                                    <input type="text" class="form-control" id="rec-maid-passport" name="maid_passport">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-contract-number" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="rec-contract-number" name="contract_number">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-nationality" class="form-label">جنسية العاملة</label>
                                    <select class="form-select" id="rec-nationality" name="nationality">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['nationalities'] as $nationality)
                                            @php
                                                $nationalityLabel = $nationality->sub_title ?? ($nationality->title ?? $nationality->name ?? '-');
                                            @endphp
                                            <option value="{{ $nationalityLabel }}">{{ $nationalityLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-religion" class="form-label">ديانة العاملة</label>
                                    <select class="form-select" id="rec-religion" name="religion">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['religions'] as $religion)
                                            @php
                                                $religionLabel = $religion->title ?? $religion->name ?? '-';
                                            @endphp
                                            <option value="{{ $religionLabel }}">{{ $religionLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-office" class="form-label">المكتب الخارجي</label>
                                    <select class="form-select" id="rec-office" name="office_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['offices'] as $office)
                                            <option value="{{ $office->id }}">{{ $office->title ?? $office->name ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-request-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="rec-request-type" name="request_type">
                                        <option value="">الكل</option>
                                        @foreach ($requestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-marketer" class="form-label">المسوق</label>
                                    <select class="form-select" id="rec-marketer" name="marketer_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rec-city" class="form-label">مدينة العميل</label>
                                    <input type="text" class="form-control" id="rec-city" name="city"
                                        placeholder="المدينة">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'rent_contracts' ? '' : 'd-none' }}"
                            data-report-section="rent_contracts">
                            <div class="report-section-title">فلاتر عقود الإيجار</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="rent-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="rent-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($rentStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-date-from" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="rent-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-date-to" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="rent-date-to" name="date_to">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-marketer" class="form-label">المسوق</label>
                                    <select class="form-select" id="rent-marketer" name="marketer_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-client-name" class="form-label">اسم العميل</label>
                                    <input type="text" class="form-control" id="rent-client-name" name="client_name">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-client-phone" class="form-label">رقم الجوال</label>
                                    <input type="text" class="form-control" id="rent-client-phone" name="client_phone">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-maid-name" class="form-label">اسم العاملة</label>
                                    <input type="text" class="form-control" id="rent-maid-name" name="maid_name">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-maid-passport" class="form-label">رقم الجواز</label>
                                    <input type="text" class="form-control" id="rent-maid-passport" name="maid_passport">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-contract-number" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="rent-contract-number" name="contract_number">
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-request-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="rent-request-type" name="request_type">
                                        <option value="">الكل</option>
                                        @foreach ($requestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-warranty-status" class="form-label">حالة الضمان</label>
                                    <select class="form-select" id="rent-warranty-status" name="warranty_status">
                                        <option value="">الكل</option>
                                        @foreach ($warrantyStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="rent-extension-filter" class="form-label">التمديد</label>
                                    <select class="form-select" id="rent-extension-filter" name="extension_filter">
                                        <option value="">الكل</option>
                                        @foreach ($rentExtensionOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'shelter_contracts' ? '' : 'd-none' }}"
                            data-report-section="shelter_contracts">
                            <div class="report-section-title">فلاتر عقود الإيواء</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="shelter-contract-number" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="shelter-contract-number"
                                        name="contract_number">
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-nationality" class="form-label">الجنسية</label>
                                    <select class="form-select" id="shelter-nationality" name="nationality">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['nationalities'] as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->sub_title ?? ($nationality->title ?? $nationality->name ?? '-') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-place" class="form-label">مكان الإيواء</label>
                                    <select class="form-select" id="shelter-place" name="shelter">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['shelters'] as $shelter)
                                            <option value="{{ $shelter->id }}">{{ $shelter->title ?? $shelter->name ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-receipt-source" class="form-label">مصدر الاستلام</label>
                                    <select class="form-select" id="shelter-receipt-source" name="receipt_source">
                                        <option value="">الكل</option>
                                        @foreach ($shelterReceiptSourceOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-guarantee-status" class="form-label">حالة الضمان</label>
                                    <select class="form-select" id="shelter-guarantee-status" name="status">
                                        <option value="">الكل</option>
                                        @foreach ($shelterGuaranteeStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-work" class="form-label">رغبة العمل</label>
                                    <select class="form-select" id="shelter-work" name="work_willingness">
                                        <option value="">الكل</option>
                                        @foreach ($shelterWorkWillingnessOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-instant" class="form-label">الاستلام الفوري</label>
                                    <select class="form-select" id="shelter-instant" name="instant_receipt">
                                        <option value="">الكل</option>
                                        @foreach ($shelterInstantReceiptOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-service-category" class="form-label">تصنيف الخدمة</label>
                                    <select class="form-select" id="shelter-service-category" name="service_category">
                                        <option value="">الكل</option>
                                        @foreach ($shelterServiceCategoryOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-request-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="shelter-request-type" name="request_type">
                                        <option value="">الكل</option>
                                        @foreach ($requestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-arrival-date" class="form-label">تاريخ الوصول</label>
                                    <input type="date" class="form-control" id="shelter-arrival-date" name="arrival_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-return-date" class="form-label">تاريخ دخول الإيواء</label>
                                    <input type="date" class="form-control" id="shelter-return-date" name="return_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="shelter-quick-tag" class="form-label">تصنيف سريع</label>
                                    <input type="text" class="form-control" id="shelter-quick-tag" name="quick_tag"
                                        placeholder="مثال: ترغب بالعمل">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'sponsorship_transfers' ? '' : 'd-none' }}"
                            data-report-section="sponsorship_transfers">
                            <div class="report-section-title">فلاتر نقل الخدمات</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="sponsor-transfer-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($sponsorshipTransferStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-date-from" class="form-label">تاريخ الإنشاء من</label>
                                    <input type="date" class="form-control" id="sponsor-transfer-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-date-to" class="form-label">تاريخ الإنشاء إلى</label>
                                    <input type="date" class="form-control" id="sponsor-transfer-date-to" name="date_to">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-transferred-from" class="form-label">تاريخ النقل من</label>
                                    <input type="date" class="form-control" id="sponsor-transfer-transferred-from"
                                        name="transfer_date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-transferred-to" class="form-label">تاريخ النقل إلى</label>
                                    <input type="date" class="form-control" id="sponsor-transfer-transferred-to"
                                        name="transfer_date_to">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-contract-number" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="sponsor-transfer-contract-number"
                                        name="contract_number" placeholder="رقم العقد">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-old-sponsor" class="form-label">الكفيل السابق</label>
                                    <input type="text" class="form-control" id="sponsor-transfer-old-sponsor"
                                        name="old_sponsor" placeholder="الاسم أو الجوال">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-new-sponsor" class="form-label">الكفيل الجديد</label>
                                    <input type="text" class="form-control" id="sponsor-transfer-new-sponsor"
                                        name="new_sponsor" placeholder="الاسم أو الجوال">
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-trial-days" class="form-label">أيام التجربة</label>
                                    <select class="form-select" id="sponsor-transfer-trial-days" name="trial_days">
                                        <option value="">كل المدد</option>
                                        <option value="7">7 أيام</option>
                                        <option value="14">14 يوم</option>
                                        <option value="21">21 يوم</option>
                                        <option value="30">30 يوم</option>
                                        <option value="60">60 يوم</option>
                                        <option value="90">90 يوم</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="sponsor-transfer-amount" class="form-label">قيمة العقد</label>
                                    <select class="form-select" id="sponsor-transfer-amount" name="amount">
                                        <option value="">كل المبالغ</option>
                                        <option value="0-5000">0 - 5,000 ريال</option>
                                        <option value="5000-10000">5,000 - 10,000 ريال</option>
                                        <option value="10000-15000">10,000 - 15,000 ريال</option>
                                        <option value="15000+">15,000 ريال فأكثر</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'deportation_travel' ? '' : 'd-none' }}"
                            data-report-section="deportation_travel">
                            <div class="report-section-title">فلاتر رحلات الترحيل</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="deportation-contract-number" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="deportation-contract-number"
                                        name="contract_number" placeholder="رقم العقد">
                                </div>
                                <div class="col-lg-3">
                                    <label for="deportation-maid-name" class="form-label">اسم العاملة</label>
                                    <input type="text" class="form-control" id="deportation-maid-name"
                                        name="maid_name" placeholder="اسم العاملة">
                                </div>
                                <div class="col-lg-3">
                                    <label for="deportation-travel-date" class="form-label">تاريخ السفر</label>
                                    <input type="date" class="form-control" id="deportation-travel-date" name="travel_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="deportation-status" class="form-label">حالة الرحلة</label>
                                    <select class="form-select" id="deportation-status" name="status">
                                        <option value="">الرحلات النشطة والمجدولة</option>
                                        <option value="all">جميع الرحلات</option>
                                        @foreach ($travelStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'travel_trips' ? '' : 'd-none' }}"
                            data-report-section="travel_trips">
                            <div class="report-section-title">فلاتر رحلات الاستقدام</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="travel-search" class="form-label">بحث</label>
                                    <input type="text" class="form-control" id="travel-search" name="search"
                                        placeholder="عميل، عاملة، عقد، رحلة">
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-order-id" class="form-label">رقم العقد</label>
                                    <input type="text" class="form-control" id="travel-order-id" name="order_id"
                                        placeholder="#123">
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-type" class="form-label">{{ __('admin.travel_type') }}</label>
                                    <select class="form-select" id="travel-type" name="travel_type">
                                        <option value="">الكل</option>
                                        @foreach ($travelTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-with" class="form-label">{{ __('admin.travel_with') }}</label>
                                    <select class="form-select" id="travel-with" name="travel_with">
                                        <option value="">الكل</option>
                                        @foreach ($travelWithOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-status" class="form-label">{{ __('admin.status') }}</label>
                                    <select class="form-select" id="travel-status" name="status">
                                        <option value="">الرحلات النشطة والمجدولة</option>
                                        <option value="all">جميع الرحلات</option>
                                        @foreach ($travelStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-date" class="form-label">تاريخ الرحلة</label>
                                    <input type="date" class="form-control" id="travel-date" name="travel_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-created-date" class="form-label">تاريخ الإنشاء</label>
                                    <input type="date" class="form-control" id="travel-created-date" name="created_date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-created-by" class="form-label">المنشئ</label>
                                    <select class="form-select" id="travel-created-by" name="created_by">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-arrival-airport" class="form-label">المطار</label>
                                    <select class="form-select" id="travel-arrival-airport" name="arrival_airport">
                                        <option value="">الكل</option>
                                        @foreach ($travelAirportOptions as $airport)
                                            <option value="{{ $airport['value'] }}">{{ $airport['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-flight-company" class="form-label">{{ __('admin.flight_company') }}</label>
                                    <input type="text" class="form-control" id="travel-flight-company" name="flight_company">
                                </div>
                                <div class="col-lg-3">
                                    <label for="travel-flight-number" class="form-label">{{ __('admin.flight_number') }}</label>
                                    <input type="text" class="form-control" id="travel-flight-number" name="flight_number">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'complaints' ? '' : 'd-none' }}"
                            data-report-section="complaints">
                            <div class="report-section-title">فلاتر الشكاوى</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="complaints-keyword" class="form-label">بحث</label>
                                    <input type="text" class="form-control" id="complaints-keyword" name="keyword"
                                        placeholder="العميل، الموضوع، الجوال، جواز العاملة">
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-ticket-number" class="form-label">رقم التذكرة</label>
                                    <input type="text" class="form-control" id="complaints-ticket-number"
                                        name="ticket_number" placeholder="#000001">
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="complaints-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($complaintStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-priority" class="form-label">الأولوية</label>
                                    <select class="form-select" id="complaints-priority" name="priority">
                                        <option value="">كل الأولويات</option>
                                        @foreach ($complaintPriorityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-type" class="form-label">نوع الشكوى</label>
                                    <select class="form-select" id="complaints-type" name="type">
                                        <option value="">كل الأنواع</option>
                                        @foreach ($filterOptions['complaint_types'] as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-assigned-to" class="form-label">الموظف المسؤول</label>
                                    <select class="form-select" id="complaints-assigned-to" name="assigned_to">
                                        <option value="">كل الموظفين</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-period" class="form-label">الفترة الزمنية</label>
                                    <select class="form-select" id="complaints-period" name="period">
                                        <option value="">كل الأوقات</option>
                                        <option value="today">اليوم</option>
                                        <option value="yesterday">أمس</option>
                                        <option value="week">هذا الأسبوع</option>
                                        <option value="month">هذا الشهر</option>
                                        <option value="custom">تاريخ مخصص</option>
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-date-from" class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control" id="complaints-date-from" name="date_from">
                                </div>
                                <div class="col-lg-3">
                                    <label for="complaints-date-to" class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control" id="complaints-date-to" name="date_to">
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'offices' ? '' : 'd-none' }}"
                            data-report-section="offices">
                            <div class="report-section-title">فلاتر المكاتب</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="offices-name" class="form-label">اسم المكتب</label>
                                    <input type="text" class="form-control" id="offices-name" name="office_name"
                                        placeholder="اسم المكتب">
                                </div>
                                <div class="col-lg-3">
                                    <label for="offices-nationality" class="form-label">الجنسية</label>
                                    <select class="form-select" id="offices-nationality" name="nationality_id">
                                        <option value="">كل الجنسيات</option>
                                        @foreach ($filterOptions['nationalities'] as $nationality)
                                            <option value="{{ $nationality->id }}">
                                                {{ $nationality->sub_title ?? ($nationality->title ?? $nationality->name ?? '-') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="offices-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="offices-status" name="status">
                                        <option value="">كل الحالات</option>
                                        @foreach ($officeStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="offices-linked-state" class="form-label">حالة السير المرتبطة</label>
                                    <select class="form-select" id="offices-linked-state" name="linked_state">
                                        <option value="">كل الحالات</option>
                                        @foreach ($officeLinkedStateOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="offices-cost-state" class="form-label">حالة قيمة الدولار</label>
                                    <select class="form-select" id="offices-cost-state" name="cost_state">
                                        <option value="">كل الحالات</option>
                                        @foreach ($officeCostStateOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="report-filter-section {{ $selectedReport === 'financial_requests' ? '' : 'd-none' }}"
                            data-report-section="financial_requests">
                            <div class="report-section-title">فلاتر الطلبات المالية</div>
                            <div class="row g-3">
                                <div class="col-lg-3">
                                    <label for="financial-scope" class="form-label">النطاق</label>
                                    <select class="form-select" id="financial-scope" name="scope">
                                        <option value="">كل الطلبات</option>
                                        @foreach ($financialRequestScopeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-search" class="form-label">بحث</label>
                                    <input type="text" class="form-control" id="financial-search" name="q"
                                        placeholder="رقم طلب، عقد، عميل، هوية">
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-type" class="form-label">نوع الطلب</label>
                                    <select class="form-select" id="financial-type" name="type">
                                        <option value="">الكل</option>
                                        @foreach ($financialRequestTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-status" class="form-label">الحالة</label>
                                    <select class="form-select" id="financial-status" name="status">
                                        <option value="">الكل</option>
                                        @foreach ($financialRequestStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-priority" class="form-label">التصعيد</label>
                                    <select class="form-select" id="financial-priority" name="priority">
                                        <option value="">الكل</option>
                                        @foreach ($financialRequestPriorityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-date" class="form-label">التاريخ</label>
                                    <input type="date" class="form-control" id="financial-date" name="date">
                                </div>
                                <div class="col-lg-3">
                                    <label for="financial-created-by" class="form-label">مقدم الطلب</label>
                                    <select class="form-select" id="financial-created-by" name="created_by_id">
                                        <option value="">الكل</option>
                                        @foreach ($filterOptions['admins'] as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-4 mt-4 border-top">
                            <button type="button" class="btn btn-outline-danger export-trigger" data-format="pdf">
                                <i class="fas fa-file-pdf me-1"></i>
                                تصدير PDF موحد
                            </button>
                            <button type="button" class="btn btn-outline-success export-trigger" data-format="excel">
                                <i class="fas fa-file-excel me-1"></i>
                                تصدير Excel سريع
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resetReportFilters">
                                <i class="fas fa-rotate-left me-1"></i>
                                مسح الفلاتر
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportMeta = @json($reports);
            const reportFormatPermissions = @json($reportFormatPermissions);
            const exportRoutes = @json(collect($reports)->mapWithKeys(fn ($report, $key) => [$key => route('reports.export', ['report' => $key])])->all());

            const form = document.getElementById('reportExportForm');
            const reportSelector = document.getElementById('reportSelector');
            const reportFormat = document.getElementById('reportFormat');
            const summaryLabel = document.getElementById('selectedReportLabel');
            const summaryDescription = document.getElementById('selectedReportDescription');
            const reportCards = document.querySelectorAll('[data-report-card]');
            const reportSections = document.querySelectorAll('[data-report-section]');
            const resetButton = document.getElementById('resetReportFilters');
            const exportButtons = document.querySelectorAll('.export-trigger');
            const catalogSearch = document.getElementById('reportsCatalogSearch');
            const catalogItems = document.querySelectorAll('[data-report-catalog-item]');
            const catalogEmpty = document.getElementById('reportsCatalogEmpty');

            if (catalogSearch) {
                catalogSearch.addEventListener('input', function() {
                    const query = this.value.trim().toLocaleLowerCase();
                    let visible = 0;

                    catalogItems.forEach((item) => {
                        const matches = !query || (item.dataset.reportSearch || '').toLocaleLowerCase().includes(query);
                        item.classList.toggle('d-none', !matches);
                        if (matches) visible += 1;
                    });

                    if (catalogEmpty) catalogEmpty.classList.toggle('d-none', visible !== 0);
                });
            }

            function updateExportButtons(reportKey) {
                exportButtons.forEach((button) => {
                    const format = button.dataset.format;
                    const isAllowed = Boolean(reportFormatPermissions[reportKey] && reportFormatPermissions[reportKey][format]);

                    button.disabled = !isAllowed;
                    button.classList.toggle('disabled', !isAllowed);
                    button.title = isAllowed ? '' : 'ليس لديك صلاحية لهذا النوع من التصدير';
                });
            }

            function setActiveReport(reportKey) {
                if (!exportRoutes[reportKey]) {
                    return;
                }

                reportSelector.value = reportKey;
                form.action = exportRoutes[reportKey];

                reportCards.forEach((card) => {
                    card.classList.toggle('active', card.dataset.reportCard === reportKey);
                });

                reportSections.forEach((section) => {
                    const isActive = section.dataset.reportSection === reportKey;
                    section.classList.toggle('d-none', !isActive);

                    section.querySelectorAll('input, select, textarea').forEach((field) => {
                        field.disabled = !isActive;
                    });
                });

                if (reportMeta[reportKey]) {
                    summaryLabel.textContent = reportMeta[reportKey].label;
                    summaryDescription.textContent = reportMeta[reportKey].description;
                }

                updateExportButtons(reportKey);

                const nextUrl = new URL(window.location.href);
                nextUrl.searchParams.set('report', reportKey);
                window.history.replaceState({}, '', nextUrl);
            }

            reportCards.forEach((card) => {
                card.addEventListener('click', function() {
                    setActiveReport(this.dataset.reportCard);
                });
            });

            reportSelector.addEventListener('change', function() {
                setActiveReport(this.value);
            });

            exportButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    if (this.disabled) {
                        return;
                    }

                    const format = this.dataset.format;
                    reportFormat.value = format;
                    form.target = format === 'pdf' ? '_blank' : '_self';
                    form.submit();
                });
            });

            resetButton.addEventListener('click', function() {
                const activeReport = reportSelector.value;
                form.reset();
                reportFormat.value = 'pdf';
                setActiveReport(activeReport);
            });

            setActiveReport(reportSelector.value);
        });
    </script>
@endsection
