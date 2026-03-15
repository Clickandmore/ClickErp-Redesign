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
    </style>
@endsection

@section('page-title')
    {{ helperTrans('ingaz.All Shelter contracts') }}
@endsection

@section('content')
    <div class="page-body p-4 text-dark admin-tables">
        @if (@AdminGuardHasPerm(\App\PermKeys::SHELTER_CONTRACT_VIEW))
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h3 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-home text-primary me-2"></i>
                        {{ helperTrans('ingaz.All Shelter contracts') }}
                    </h3>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="exportExcel" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>تصدير Excel
                        </button>
                        <button type="button" id="exportPdf" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>تصدير PDF
                        </button>
                        <a href="{{ route('ShelterContract.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            {{ helperTrans('ingaz.add_shelter_contract') }}
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card total-card shadow h-100 stats-filter-card"
                            data-clear-filters="1">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            إجمالي العقود
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $totalContracts ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-contract fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card shadow h-100 border-start border-primary border-4 stats-filter-card"
                            data-service-category="recruitment">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            عاملات الاستقدام
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $serviceCategoryStats['recruitment'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-briefcase fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card shadow h-100 border-start border-info border-4 stats-filter-card"
                            data-service-category="rental">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            عاملات التأجير
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $serviceCategoryStats['rental'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-house-user fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card shadow h-100 border-start border-success border-4 stats-filter-card"
                            data-service-category="transfer">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            نقل الخدمات
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $serviceCategoryStats['transfer'] ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-people-arrows fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card within-guarantee-card shadow h-100 stats-filter-card"
                            data-status="within_guarantee">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            ضمن الضمان
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $withinGuarantee ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shield-alt fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card expired-guarantee-card shadow h-100 stats-filter-card"
                            data-status="expired_guarantee">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            منتهي الضمان
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $expiredGuarantee ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card wants-work-card shadow h-100 stats-filter-card"
                            data-work-willingness="yes">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            جاهزات للنقل
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $wantsToWork ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card shadow h-100 border-start border-warning border-4 stats-filter-card"
                            data-work-willingness="no">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            مرحلة الترحيل
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $inDeportation ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-plane-departure fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card statistics-card instant-receipt-card shadow h-100 stats-filter-card"
                            data-instant-receipt="yes">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">
                                            متاحة للاستلام الفوري
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            {{ $availableForInstantReceipt ?? 0 }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bolt fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h6 class="mb-1">التصنيفات السريعة</h6>
                                <small class="text-muted">مثل: جاهزة للنقل، جاهزة للإيجار، تحت المتابعة. يمكن استعمالها
                                    كإحصائية وفلتر مباشر.</small>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-warning text-dark">بدون تاريخ وصول واضح:
                                    {{ $guaranteeUnknown ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-dark quick-filter-chip active"
                                data-tag="">
                                كل العقود
                            </button>
                            @forelse ($quickTagStats as $tagStat)
                                <button type="button" class="btn btn-sm btn-outline-primary quick-filter-chip"
                                    data-tag="{{ $tagStat['name'] }}">
                                    {{ $tagStat['name'] }}
                                    <span class="badge bg-light text-dark ms-1">{{ $tagStat['count'] }}</span>
                                </button>
                            @empty
                                <span class="text-muted">لا توجد تصنيفات سريعة مضافة حتى الآن.</span>
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
                                <div class="col-md-3">
                                    <label for="filterContractNumber" class="form-label">رقم العقد</label>
                                    <input type="text" id="filterContractNumber" class="form-control"
                                        placeholder="ابحث برقم العقد">
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
                                        <option value="no">غير متاحة للاستلام الفوري</option>
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
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end">
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
                <div class="card shadow">
                    <div class="card-body border-bottom">
                        <div class="alert column-explanation mb-0">
                            <strong class="d-block mb-2">توضيح الأعمدة</strong>
                            <div>مدة التسكين داخل الإيواء: من تاريخ إدخال العاملة إلى الإيواء وحتى اليوم.</div>
                            <div>مدة بقائها داخل المملكة: من تاريخ وصولها للمملكة وحتى اليوم.</div>
                            <div>تاريخ إدخال الإيواء: هو تاريخ مراجعتها وإيداعها داخل الإيواء.</div>
                        </div>
                    </div>
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
                                        <th>مصدر الاستلام</th>
                                        <th>حالة الضمان</th>
                                        <th>رغبة العمل</th>
                                        <th>التصنيفات السريعة</th>
                                        <th>متاحة للاستلام الفوري</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
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
                responsive: false,
                scrollX: true,
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
                        data: 'receipt_source',
                        name: 'receipt_source',
                        orderable: false
                    },
                    {
                        data: 'guarantee_status',
                        name: 'guarantee_status',
                        orderable: false
                    },
                    {
                        data: 'work_willingness',
                        name: 'work_willingness',
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

            // Apply filters
            $('#applyFilters').on('click', function() {
                dataTable.ajax.reload();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
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
                    nationality: $('#filterNationality').val(),
                    shelter: $('#filterShelter').val(),
                    receipt_source: $('#filterReceiptSource').val(),
                    status: $('#filterStatus').val(),
                    work_willingness: $('#filterWorkWillingness').val(),
                    arrival_date: $('#filterArrivalDate').val(),
                    return_date: $('#filterReturnDate').val(),
                    instant_receipt: $('#filterInstantReceipt').val(),
                    quick_tag: $('#filterQuickTag').val(),
                    service_category: $('#filterServiceCategory').val()
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
                $('.quick-filter-chip').removeClass('active');
                $('.quick-filter-chip[data-tag=""]').addClass('active');

                $('#filterServiceCategory').val($(this).data('service-category') || '');
                $('#filterStatus').val($(this).data('status') || '');
                $('#filterWorkWillingness').val($(this).data('work-willingness') || '');
                $('#filterInstantReceipt').val($(this).data('instant-receipt') || '');
                dataTable.ajax.reload();
            });

            // Handle action buttons
            $(document).on('click', '.confirm-action', function(e) {
                // console.log("Amer");

                e.preventDefault();
                const action = $(this).data('action');
                const id = $(this).data('id');
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
                        performAction(action, id);
                    }
                });
            });

            function performAction(action, id) {
                let url = '';
                let method = 'POST';

                switch (action) {
                    case 'back':
                        url = '{{ route('ShelterContract.back', ':id') }}'.replace(':id', id);
                        break;
                    case 'transfer':
                        // فتح صفحة إضافة طلب نقل كفالة
                        window.location.href = '{{ route('SponsorshipTransfer.create') }}?contract_id=' + id;
                        return;
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
        }); // نهاية window.addEventListener
    </script>
@endsection
