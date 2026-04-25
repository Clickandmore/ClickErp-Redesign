@extends('admin.layout.indexs.index')
@section('style')
    <!-- DataTables CSS من CDN -->
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- الملفات المحلية -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> --}}
@endsection

@section('page-title')
    {{ helperTrans('ingaz.All Rent contracts') }}
@endsection

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_VIEW))
        @php
            $canManageRentContractTerms = @AdminGuardHasAnyPerm([
                \App\PermKeys::RENT_CONTRACT_TERMS_VIEW,
                \App\PermKeys::RENT_CONTRACT_TERMS_CREATE,
                \App\PermKeys::RENT_CONTRACT_TERMS_EDIT,
                \App\PermKeys::RENT_CONTRACT_TERMS_DELETE,
                \App\PermKeys::RENT_CONTRACT_VIEW,
                \App\PermKeys::RENT_CONTRACT_CREATE,
                \App\PermKeys::RENT_CONTRACT_EDIT,
                \App\PermKeys::RENT_CONTRACT_DELETE,
                \App\PermKeys::RENT_CONTRACT_CONTROL,
                \App\PermKeys::CONTRACT_SETTINGS,
            ]);
        @endphp
        <!-- Header Section -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h3 class="h3 mb-0 text-gray-800">
                <i class="fas fa-handshake text-primary me-2"></i>
                {{ helperTrans('ingaz.All Rent contracts') }}
            </h3>
            <div>
                @if (@AdminGuardHasPerm(\App\PermKeys::CONTRACT_SETTINGS))
                    <a href="{{ route('rent-contract.settings') }}" class="btn btn-info me-2">
                        <i class="fas fa-sliders-h me-2"></i>
                        إعدادات العقد
                    </a>
                @endif
                @if ($canManageRentContractTerms)
                    <a href="{{ route('rent-contract-terms.index') }}" class="btn btn-warning me-2">
                        <i class="fas fa-list-alt me-2"></i>
                        إدارة بنود العقد
                    </a>
                @endif
                @if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE))
                    <a href="{{ route('RentContract.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        {{ helperTrans('ingaz.add Rent contract') }}
                    </a>
                @endif
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    إجمالي عقود التأجير</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalContracts">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    العقود النشطة</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeContracts">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">العقود المكتملة</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedContracts">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-flag-checkered fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    مغلقة - متاحة</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="availableContracts">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    تم النقل</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="transferredContracts">0</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    قيمة العقود الإجمالية</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalValue">0 ريال</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card filter-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلاتر البحث
                    </h6>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
                    <i class="fas fa-chevron-down" id="filtersIcon"></i>
                </button>
            </div>
            <div class="card-body collapse" id="filtersCollapse">
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">حالة العقد:</label>
                        <select id="statusFilter" class="form-select">
                            <option value="" {{ request('status') ? '' : 'selected' }}>جميع الحالات</option>
                            <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>جديد</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="contract_sent" {{ in_array(request('status'), ['contract_sent', 'waiting_contract_send'], true) ? 'selected' : '' }}>
                                بانتظار التوقيع
                            </option>
                            <option value="contract_signed" {{ request('status') === 'contract_signed' ? 'selected' : '' }}>
                                تم توقيع العقد
                            </option>
                            <option value="maid_delivered" {{ request('status') === 'maid_delivered' ? 'selected' : '' }}>
                               تم تسليم العاملة
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                            {{-- <option value="available">مغلق - متاحة</option> --}}
                            <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>تم النقل</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>معلق</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">تاريخ من:</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="dateToFilter" class="form-label">تاريخ إلى:</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="clientPhoneFilter" class="form-label">رقم جوال العميل:</label>
                        <input type="text" id="clientPhoneFilter" class="form-control"
                            placeholder="ابحث برقم الجوال (5********)">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="clientNameFilter" class="form-label">اسم العميل:</label>
                        <input type="text" id="clientNameFilter" class="form-control" placeholder="ابحث باسم العميل">
                    </div>
                    <div class="col-md-3">
                        <label for="maidPassportFilter" class="form-label">رقم جواز العاملة:</label>
                        <input type="text" id="maidPassportFilter" class="form-control"
                            placeholder="ابحث برقم الجواز">
                    </div>
                    <div class="col-md-3">
                        <label for="maidNameFilter" class="form-label">اسم العاملة:</label>
                        <input type="text" id="maidNameFilter" class="form-control" placeholder="ابحث باسم العاملة">
                    </div>
                    <div class="col-md-3">
                        <label for="contractNumberFilter" class="form-label">رقم العقد:</label>
                        <input type="text" id="contractNumberFilter" class="form-control"
                            placeholder="ابحث برقم العقد">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="marketerFilter" class="form-label">المسوق:</label>
                        <select id="marketerFilter" class="form-select">
                            <option value="">جميع المسوقين</option>
                            <!-- سيتم تعبئتها من الـ Controller -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="requestTypeFilter" class="form-label">{{ helperTrans('ingaz.request_type') }}:</label>
                        <select id="requestTypeFilter" class="form-select">
                            <option value="">{{ helperTrans('ingaz.all') }}</option>
                            @foreach (\App\Models\ingaz\Maid::getRequestTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="warrantyStatusFilter" class="form-label">حالة الضمان:</label>
                        <select id="warrantyStatusFilter" class="form-select">
                            <option value="">جميع حالات الضمان</option>
                            <option value="not_applicable">غير قابل للتطبيق</option>
                            <option value="active">نشط</option>
                            <option value="expired">منتهي</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="extensionFilter" class="form-label">حالة التمديد:</label>
                        <select id="extensionFilter" class="form-select">
                            <option value="">جميع العقود</option>
                            <option value="extended">عقود ممددة</option>
                            <option value="not_extended">غير ممددة</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-search me-2"></i>تطبيق الفلاتر
                            </button>
                            <button type="button" class="btn btn-secondary" id="clearFilters">
                                <i class="fas fa-times me-2"></i>مسح الفلاتر
                            </button>
                            <button type="button" class="btn btn-success" id="exportExcel">
                                <i class="fas fa-file-excel me-2"></i>تصدير Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="clean-loading-overlay" style="display: none;">
            <div class="clean-loading-content">
                <div class="clean-loader-main">
                    <div class="clean-loader-dot"></div>
                    <div class="clean-loader-dot"></div>
                    <div class="clean-loader-dot"></div>
                </div>
                <div class="clean-loading-text">جاري تحميل عقود التأجير...</div>
                <div class="clean-loading-subtitle">يرجى الانتظار</div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>
                    قائمة عقود التأجير
                </h6>
            </div>
            <div class="card-body">

                <table class="table table-bordered table-style" id="dataTableObject" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>رقم الجوال</th>
                            <th>العاملة</th>
                            <th>معلومات العقد</th>
                            <th>المسوق</th>
                            <th>الحالة</th>
                            <th>الأيام المتبقية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- سيتم تعبئتها بواسطة DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @include('components.no_permissions')
    @endif

    <!-- Modal لإغلاق العقد -->
    <div class="modal fade" id="closeContractModal" tabindex="-1" aria-labelledby="closeContractModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="closeContractModalLabel">
                        <i class="fas fa-times-circle me-2"></i>
                        إنهاء عقد الإيجار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- معلومات العقد -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات العقد</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="contractDetails">
                                        <div class="col-md-3">
                                            <strong>رقم العقد:</strong>
                                            <p id="contractId">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>العميل:</strong>
                                            <p id="clientName">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>العاملة:</strong>
                                            <p id="maidName">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>الحالة الحالية:</strong>
                                            <p id="currentStatus">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>تاريخ البداية:</strong>
                                            <p id="startDate">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>تاريخ النهاية:</strong>
                                            <p id="endDate">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>المدة المتبقية:</strong>
                                            <p id="remainingDays">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>قيمة العقد:</strong>
                                            <p id="contractValue">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>البيان المالي</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <strong>التكلفة الشهرية:</strong>
                                            <p id="financialMonthlyCost">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>مدة العقد (شهر):</strong>
                                            <p id="financialDurationMonths">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>مدة العقد (يوم):</strong>
                                            <p id="financialDurationDays">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>قيمة العقد:</strong>
                                            <p id="financialContractValue">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>المبلغ المدفوع:</strong>
                                            <p id="financialPaidAmount">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>المبلغ المتبقي:</strong>
                                            <p id="financialRemainingAmount">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>الأيام المستخدمة:</strong>
                                            <p id="financialUsedDays">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>الأيام المتبقية:</strong>
                                            <p id="financialRemainingDays">-</p>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">نوع الاسترجاع</label>
                                            <select class="form-select" id="closeAdjustmentType">
                                                <option value="refund">استرجاع للعميل</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">مبلغ الاسترجاع</label>
                                            <input type="number" class="form-control" id="closeAdjustmentAmount"
                                                min="0" step="0.01" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">المستحق النهائي</label>
                                            <div class="fw-bold">
                                                <span id="finalDueAmount">0.00 ريال</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2 align-items-end">
                                        <div class="col-md-4">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox"
                                                    id="closeClearRemainingToggle">
                                                <label class="form-check-label" for="closeClearRemainingToggle">
                                                    تصفير المبلغ المتبقي
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">مبلغ التصفير</label>
                                            <input type="number" class="form-control" id="closeClearRemainingAmount"
                                                min="0" step="0.01" value="0" disabled>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">المتبقي بعد التصفير</label>
                                            <div class="fw-bold">
                                                <span id="finalRemainingAmount">0.00 ريال</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-3">
                                        <div class="col-md-4">
                                            <label class="form-label">تحديث حالة الدفع بعد الإغلاق</label>
                                            <select class="form-select" id="closePaymentStatus">
                                                <option value="">بدون تغيير</option>
                                                <option value="paid">تم الدفع</option>
                                                <option value="partial">مدفوع جزئياً</option>
                                                <option value="pending">معلق</option>
                                                <option value="later">الدفع لاحقاً</option>
                                                <option value="bank_pending">بانتظار التحويل</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">إيصال التحويل</label>
                                            <input type="file" class="form-control" id="closePaymentReceipt"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">آيبان العميل</label>
                                            <input type="text" class="form-control" id="closeClientIban" dir="ltr"
                                                maxlength="34" placeholder="SAxxxxxxxxxxxxxxxxxxxxxx">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">مرفق آيبان العميل</label>
                                            <input type="file" class="form-control" id="closeClientIbanAttachment"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                            <div class="form-text d-none" id="closeClientIbanAttachmentCurrentWrapper">
                                                <a href="#" target="_blank"
                                                    id="closeClientIbanAttachmentCurrent">عرض المرفق الحالي</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- خيارات الإغلاق -->
                    <div class="row" id="closeContractOptionsRow">
                        <div class="col-md-6">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-check me-2"></i>
                                        إغلاق العقد وإرجاع العاملة متاحة
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="mb-3">سيتم إغلاق العقد وإرجاع العاملة لتصبح متاحة للإيجار مرة أخرى</p>
                                    <button type="button" class="btn btn-success btn-lg" id="closeAndMakeAvailableBtn">
                                        <i class="fas fa-check me-2"></i>
                                        إغلاق وإرجاع العاملة
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning h-100">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        إغلاق العقد ونقل الكفالة
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <i class="fas fa-handshake fa-3x text-warning mb-3"></i>
                                    <p class="mb-3">سيتم إغلاق العقد ونقل كفالة العاملة لعميل آخر</p>
                                    <button type="button" class="btn btn-warning btn-lg" id="transferSponsorshipBtn">
                                        <i class="fas fa-exchange-alt me-2"></i>
                                        إغلاق ونقل الكفالة
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نموذج نقل الكفالة (مخفي في البداية) -->
                    <div id="transferSponsorshipForm" style="display: none;" class="mt-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    تأكيد نقل الكفالة
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- معلومات النقل -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <small class="text-muted">الكفيل القديم</small>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="mb-1">{{ documentSetting('document_company_name', 'شركة بلاتنيوم الشرقية للاستقدام') }}</h6>
                                                <small class="text-muted">عقد إيجار</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header">
                                                <small class="text-muted">الكفيل الجديد</small>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="mb-1" id="transferClientName">-</h6>
                                                <small class="text-muted">عقد استقدام</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form id="sponsorshipTransferForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="transferAmount" class="form-label">قيمة العقد الجديد <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="transferAmount"
                                                    name="transferAmount" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="totalAmount" class="form-label">المبلغ الكامل <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="totalAmount"
                                                    name="totalAmount" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="paidAmount" class="form-label">المبلغ المدفوع <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="paidAmount"
                                                    name="paidAmount" step="0.01" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="remainingAmount" class="form-label">المبلغ المتبقي</label>
                                                <input type="number" class="form-control" id="remainingAmount"
                                                    name="remainingAmount" step="0.01" readonly>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="transferNotes" class="form-label">ملاحظات</label>
                                                <textarea class="form-control" id="transferNotes" name="transferNotes" rows="3"
                                                    placeholder="ملاحظات إضافية حول عملية نقل الكفالة..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0">بنود نقل الخدمات</h6>
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                        id="add-transfer-adjustment">
                                                        <i class="fas fa-plus me-1"></i>إضافة بند
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 18%;">النوع</th>
                                                                <th>البند</th>
                                                                <th style="width: 20%;">المبلغ</th>
                                                                <th style="width: 12%;">إجراء</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="transfer-adjustment-rows"></tbody>
                                                    </table>
                                                </div>
                                                <template id="transfer-adjustment-row-template">
                                                    <tr>
                                                        <td>
                                                            <select
                                                                class="form-select form-select-sm transfer-adjustment-type">
                                                                <option value="income">إيراد</option>
                                                                <option value="expense">مصروف</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control form-control-sm transfer-adjustment-label"
                                                                placeholder="اسم البند">
                                                        </td>
                                                        <td>
                                                            <input type="number"
                                                                class="form-control form-control-sm text-end transfer-adjustment-amount"
                                                                step="0.01" min="0" value="0.00">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-outline-danger btn-sm remove-transfer-adjustment">
                                                                حذف
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" id="cancelTransferBtn">
                                            <i class="fas fa-times me-2"></i>
                                            إلغاء
                                        </button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-check me-2"></i>
                                            تأكيد نقل الكفالة
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="extendContractModal" tabindex="-1" aria-labelledby="extendContractModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="extendContractModalLabel">
                        <i class="fas fa-calendar-plus me-2"></i>
                        تمديد عقد الإيجار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="extendContractErrors" class="alert alert-danger d-none"></div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">رقم العقد</label>
                            <div class="form-control bg-light" id="extend_contract_number">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">نهاية العقد الحالية</label>
                            <div class="form-control bg-light" id="extend_current_end_text">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">التكلفة الشهرية</label>
                            <div class="form-control bg-light" id="extend_monthly_cost_text">-</div>
                        </div>
                    </div>

                    <form id="extendContractForm" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="extend_current_end_value">
                        <input type="hidden" id="extend_monthly_cost_value">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="extend_extension_months" class="form-label">مدة التمديد بالشهور <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="extend_extension_months"
                                    name="extension_months" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_days" class="form-label">عدد أيام التمديد</label>
                                <input type="text" class="form-control" id="extend_extension_days" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_end_date" class="form-label">تاريخ النهاية الجديد</label>
                                <input type="text" class="form-control" id="extend_extension_end_date" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_value" class="form-label">قيمة التمديد</label>
                                <input type="text" class="form-control" id="extend_extension_value" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_payment_status" class="form-label">حالة دفع التمديد <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="extend_extension_payment_status"
                                    name="extension_payment_status" required>
                                    <option value="">اختر الحالة</option>
                                    <option value="paid">تم الدفع</option>
                                    <option value="partial">مدفوع جزئياً</option>
                                    <option value="pending">معلق</option>
                                    <option value="later">الدفع لاحقاً</option>
                                    <option value="bank_pending">بانتظار التحويل</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="extend_extension_payment_receipt" class="form-label">
                                    إيصال الدفع
                                    <span class="text-danger d-none" id="extendReceiptRequiredMark">*</span>
                                </label>
                                <input type="file" class="form-control" id="extend_extension_payment_receipt"
                                    name="extension_payment_receipt" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-danger d-none" id="extendReceiptRequiredNote">
                                    يجب إرفاق الإيصال عند تحديد تم الدفع أو مدفوع جزئياً.
                                </small>
                            </div>
                            <div class="col-12">
                                <label for="extend_extension_notes" class="form-label">ملاحظات</label>
                                <textarea class="form-control" id="extend_extension_notes" name="extension_notes" rows="3"
                                    placeholder="أي ملاحظات إضافية حول التمديد"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info" id="extendContractSubmit" form="extendContractForm">
                        <i class="fas fa-save me-2"></i>
                        حفظ التمديد
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <!-- DataTables JS من CDN كبديل آمن -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- الملفات المحلية -->
    <!-- <script src="{{ asset('admin') }}/assets/jsbundle/dataTables.bundle.js"></script> -->
    <script src="{{ asset('admin') }}/custom/admin-tables.js"></script>
    <script src="{{ asset('admin') }}/custom/orders-dropdown-fix.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        console.log('Script loaded, jQuery available:', typeof $);
        console.log('DataTable available:', typeof $.fn.DataTable);

        $(document).ready(function() {
            console.log('Document ready, initializing DataTable...');
            console.log('Table element found:', $('#dataTableObject').length);

            // التحقق من تحميل DataTables
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables library is not loaded!');
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ في تحميل المكتبات',
                    text: 'لم يتم تحميل مكتبة DataTables بشكل صحيح'
                });
                return;
            }

            // Initialize DataTable with simplified settings
            let dataTable = $('#dataTableObject').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('RentContract.index') }}",
                    data: function(d) {
                        d.status_filter = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
                        d.date_to = $('#dateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.marketer_id = $('#marketerFilter').val();
                        d.request_type = $('#requestTypeFilter').val();
                        d.warranty_status = $('#warrantyStatusFilter').val();
                        d.extension_filter = $('#extensionFilter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'user_id',
                        name: 'user_id'
                    },
                    {
                        data: 'phone',
                        name: 'phone'
                    },
                    {
                        data: 'maid_id',
                        name: 'maid_id'
                    },
                    {
                        data: 'contract_info',
                        name: 'contract_info'
                    },
                    {
                        data: 'marketer',
                        name: 'marketer'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'remaining_days',
                        name: 'remaining_days'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                language: {
                    "sProcessing": "جارٍ التحميل...",
                    "sLengthMenu": "أظهر _MENU_ مدخلات",
                    "sZeroRecords": "لم يعثر على أية سجلات",
                    "sInfo": "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                    "sInfoEmpty": "يعرض 0 إلى 0 من أصل 0 سجل",
                    "sInfoFiltered": "(منتقاة من مجموع _MAX_ مُدخل)",
                    "sInfoPostFix": "",
                    "sSearch": "ابحث:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "الأول",
                        "sPrevious": "السابق",
                        "sNext": "التالي",
                        "sLast": "الأخير"
                    }
                },
                drawCallback: function() {
                    console.log('Table drawn successfully');
                    $('#loadingOverlay').hide();
                }
            });

            // Apply Filters
            $('#applyFilters').click(function() {
                dataTable.ajax.reload();
            });

            // Clear Filters
            $('#clearFilters').click(function() {
                $('#statusFilter, #marketerFilter, #requestTypeFilter, #warrantyStatusFilter, #extensionFilter')
                    .val('');
                $('#dateFromFilter, #dateToFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter')
                    .val('');
                dataTable.ajax.reload();
            });

            // Export Excel
            $('#exportExcel').click(function() {
                window.location.href = "{{ route('RentContract.export.excel') }}?" + $.param({
                    status: $('#statusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    client_phone: $('#clientPhoneFilter').val(),
                    client_name: $('#clientNameFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_name: $('#maidNameFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    request_type: $('#requestTypeFilter').val(),
                    warranty_status: $('#warrantyStatusFilter').val(),
                    extension_filter: $('#extensionFilter').val()
                });
            });

            function formatDate(date) {
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            function formatCurrency(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function updateExtensionPreview() {
                const months = parseInt($('#extend_extension_months').val(), 10) || 0;
                const endDateStr = $('#extend_current_end_value').val();
                const monthlyCost = parseFloat($('#extend_monthly_cost_value').val()) || 0;

                if (!months || !endDateStr) {
                    $('#extend_extension_days').val('');
                    $('#extend_extension_end_date').val('');
                    $('#extend_extension_value').val('');
                    return;
                }

                const baseDate = new Date(endDateStr);
                if (Number.isNaN(baseDate.getTime())) {
                    $('#extend_extension_days').val('');
                    $('#extend_extension_end_date').val('');
                    $('#extend_extension_value').val('');
                    return;
                }

                const extensionDays = months * 30;
                const newEndDate = new Date(baseDate.getTime() + (extensionDays * 24 * 60 * 60 * 1000));

                $('#extend_extension_days').val(extensionDays);
                $('#extend_extension_end_date').val(formatDate(newEndDate));
                $('#extend_extension_value').val(formatCurrency(monthlyCost * months) + ' ريال');
            }

            function toggleExtendReceiptRequirement() {
                const paymentStatus = $('#extend_extension_payment_status').val();
                const receiptRequired = paymentStatus === 'paid' || paymentStatus === 'partial';

                $('#extend_extension_payment_receipt').prop('required', receiptRequired);
                $('#extendReceiptRequiredMark').toggleClass('d-none', !receiptRequired);
                $('#extendReceiptRequiredNote').toggleClass('d-none', !receiptRequired);
            }

            function resetExtendModal() {
                $('#extendContractErrors').addClass('d-none').empty();
                $('#extendContractForm')[0].reset();
                $('#extend_extension_days').val('');
                $('#extend_extension_end_date').val('');
                $('#extend_extension_value').val('');
                $('#extend_current_end_value').val('');
                $('#extend_monthly_cost_value').val('');
                $('#extend_contract_number').text('-');
                $('#extend_current_end_text').text('-');
                $('#extend_monthly_cost_text').text('-');
                $('#extend_extension_payment_receipt').prop('required', false);
                $('#extendReceiptRequiredMark, #extendReceiptRequiredNote').addClass('d-none');
            }

            $(document).on('click', '.open-extend-modal', function() {
                const button = $(this);
                const extendUrl = button.data('extend-url');
                const contractNumber = button.data('contract-number') || '-';
                const ingazTo = button.data('ingaz-to') || '';
                const monthlyCost = button.data('monthly-cost') || 0;

                resetExtendModal();
                $('#extendContractForm').attr('action', extendUrl);
                $('#extend_contract_number').text(contractNumber);
                $('#extend_current_end_text').text(ingazTo || 'غير محدد');
                $('#extend_monthly_cost_text').text(formatCurrency(monthlyCost) + ' ريال');
                $('#extend_current_end_value').val(ingazTo || '');
                $('#extend_monthly_cost_value').val(monthlyCost || 0);

                updateExtensionPreview();
                $('#extendContractModal').modal('show');
            });

            $('#extend_extension_months').on('input change', function() {
                updateExtensionPreview();
            });

            $('#extend_extension_payment_status').on('change', function() {
                toggleExtendReceiptRequirement();
            });

            $('#extendContractForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#extendContractSubmit');
                const paymentStatus = $('#extend_extension_payment_status').val();
                const receiptInput = $('#extend_extension_payment_receipt')[0];
                const hasReceipt = !!(receiptInput && receiptInput.files && receiptInput.files.length > 0);

                $('#extendContractErrors').addClass('d-none').empty();
                submitBtn.prop('disabled', true);

                if ((paymentStatus === 'paid' || paymentStatus === 'partial') && !hasReceipt) {
                    const message =
                        'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع "تم الدفع" أو "مدفوع جزئياً".';
                    $('#extendContractErrors').removeClass('d-none').html(message);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: message
                    });
                    submitBtn.prop('disabled', false);
                    return;
                }

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: new FormData(form[0]),
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        $('#extendContractModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: response.message || 'تم تمديد عقد التأجير بنجاح'
                        });
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء تمديد العقد';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const messages = [];
                            Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                                messages.push(xhr.responseJSON.errors[key][0]);
                            });

                            if (messages.length) {
                                $('#extendContractErrors').removeClass('d-none').html(messages.join('<br>'));
                                errorMessage = messages[0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: errorMessage
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            function updateCloseFinancialPreview() {
                const remainingAmount = parseFloat($('#financialRemainingAmount').data('raw') || 0);
                const adjustmentAmount = parseFloat($('#closeAdjustmentAmount').val() || 0);
                const clearRemainingEnabled = $('#closeClearRemainingToggle').is(':checked');
                let clearRemainingAmount = clearRemainingEnabled ? parseFloat($('#closeClearRemainingAmount').val() || 0) : 0;

                if (clearRemainingAmount > remainingAmount) {
                    clearRemainingAmount = remainingAmount;
                    $('#closeClearRemainingAmount').val(clearRemainingAmount.toFixed(2));
                }

                const finalRemaining = Math.max(0, remainingAmount - clearRemainingAmount);

                $('#finalDueAmount').text(formatCurrency(adjustmentAmount) + ' ريال');
                $('#finalRemainingAmount').text(formatCurrency(finalRemaining) + ' ريال');
            }

            function resetCloseFinancialFields() {
                $('#closeAdjustmentType').val('refund');
                $('#closeAdjustmentAmount').val('0');
                $('#closeClearRemainingToggle').prop('checked', false);
                $('#closeClearRemainingAmount').val('0').prop('disabled', true);
                $('#closePaymentStatus').val('');
                $('#closePaymentReceipt').val('');
                $('#closeClientIban').val('');
                $('#closeClientIbanAttachment').val('');
                $('#closeClientIbanAttachmentCurrentWrapper').addClass('d-none');
                $('#closeClientIbanAttachmentCurrent').attr('href', '#');
                $('#financialRemainingAmount').data('raw', 0);
                updateCloseFinancialPreview();
            }

            $('#closeAdjustmentAmount, #closeClearRemainingAmount').on('input change', function() {
                updateCloseFinancialPreview();
            });

            $('#closeClearRemainingToggle').on('change', function() {
                const enabled = $(this).is(':checked');
                $('#closeClearRemainingAmount').prop('disabled', !enabled);
                if (!enabled) {
                    $('#closeClearRemainingAmount').val('0');
                }
                updateCloseFinancialPreview();
            });

            function addTransferAdjustmentRow(item = null) {
                const template = document.querySelector('#transfer-adjustment-row-template');
                if (!template) {
                    return;
                }

                const fragment = template.content.cloneNode(true);
                const row = fragment.querySelector('tr');
                if (item) {
                    row.querySelector('.transfer-adjustment-type').value = item.type || 'income';
                    row.querySelector('.transfer-adjustment-label').value = item.label || '';
                    row.querySelector('.transfer-adjustment-amount').value = item.amount ?? '0.00';
                }
                document.querySelector('#transfer-adjustment-rows').appendChild(fragment);
            }

            function resetTransferAdjustments() {
                $('#transfer-adjustment-rows').empty();
            }

            function getTransferAdjustments() {
                const items = [];

                $('#transfer-adjustment-rows tr').each(function() {
                    const row = $(this);
                    const label = (row.find('.transfer-adjustment-label').val() || '').trim();
                    const amount = parseFloat(row.find('.transfer-adjustment-amount').val() || 0);
                    const type = row.find('.transfer-adjustment-type').val() || 'income';

                    if (!label) {
                        return;
                    }

                    items.push({
                        type: type,
                        label: label,
                        amount: Number.isFinite(amount) ? amount : 0
                    });
                });

                return items;
            }

            $(document).on('click', '#add-transfer-adjustment', function() {
                addTransferAdjustmentRow();
            });

            $(document).on('click', '.remove-transfer-adjustment', function() {
                $(this).closest('tr').remove();
            });



            // Delete Contract Handler
            $(document).on('click', '.delete-contract', function() {
                let contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد الحذف',
                    text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع عن هذا الإجراء.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، احذف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = $('<form>', {
                            'method': 'POST',
                            'action': "{{ route('RentContract.destroy', ':id') }}"
                                .replace(':id', contractId)
                        });
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            // Update Statistics
            function updateStatistics() {
                $.ajax({
                    url: "{{ route('RentContract.statistics') }}",
                    method: 'GET',
                    success: function(data) {
                        $('#totalContracts').text(data.total || 0);
                        $('#activeContracts').text(data.active || 0);
                        $('#completedContracts').text(data.completed || 0);
                        $('#availableContracts').text(data.available || 0);
                        $('#transferredContracts').text(data.transferred || 0);
                        $('#totalValue').text((data.totalValue || 0).toLocaleString() + ' ريال');

                        dataTable.ajax.reload();
                    },
                    error: function() {
                        console.log('Error loading statistics');
                    }
                });
            }

            // Toggle filters collapse icon
            $('#filtersCollapse').on('show.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            });
            $('#filtersCollapse').on('hide.bs.collapse', function() {
                $('#filtersIcon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            });

            // Load initial statistics
            updateStatistics();

            // إضافة متغير لحفظ معرف العقد المحدد
            let selectedContractId = null;

            // التعامل مع زر إنهاء العقد
            $(document).on('click', '.complete-contract', function() {
                selectedContractId = $(this).data('id');

                // جلب بيانات العقد
                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/details`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const contract = response.contract;
                            resetCloseFinancialFields();
                            resetTransferAdjustments();
                            $('#transferSponsorshipForm').hide();
                            $('#closeContractOptionsRow').show();
                            $('#sponsorshipTransferForm')[0].reset();

                            // تعبئة بيانات العقد في الـ modal
                            $('#contractId').text(contract.id);
                            $('#clientName').text(contract.client_name || '-');
                            $('#maidName').text(contract.maid_name || '-');
                            $('#currentStatus').text(contract.status_text || '-');
                            $('#startDate').text(contract.start_date || '-');
                            $('#endDate').text(contract.end_date || '-');
                            $('#remainingDays').text(contract.remaining_days || '-');
                            $('#contractValue').text((contract.amount || 0) + ' ريال');
                            $('#financialMonthlyCost').text(formatCurrency(contract.monthly_cost || 0) + ' ريال');
                            $('#financialDurationMonths').text((contract.duration_months || 0) ? (contract.duration_months + ' شهر') : '-');
                            $('#financialDurationDays').text((contract.duration_days || 0) ? (contract.duration_days + ' يوم') : '-');
                            $('#financialContractValue').text(formatCurrency(contract.contract_value || 0) + ' ريال');
                            $('#financialPaidAmount').text(formatCurrency(contract.paid_amount || 0) + ' ريال');
                            $('#financialRemainingAmount')
                                .text(formatCurrency(contract.remaining_amount || 0) + ' ريال')
                                .data('raw', contract.remaining_amount || 0);
                            $('#financialUsedDays').text(contract.used_days !== null && typeof contract.used_days !== 'undefined' ? (contract.used_days + ' يوم') : '-');
                            $('#financialRemainingDays').text(contract.remaining_days_value !== null && typeof contract.remaining_days_value !== 'undefined' ? (contract.remaining_days_value + ' يوم') : '-');
                            $('#closePaymentStatus').val(contract.payment_status || '');
                            $('#closeClientIban').val(contract.closure_client_iban || '');

                            if (contract.closure_client_iban_attachment_url) {
                                $('#closeClientIbanAttachmentCurrent').attr('href', contract.closure_client_iban_attachment_url);
                                $('#closeClientIbanAttachmentCurrentWrapper').removeClass('d-none');
                            }

                            updateCloseFinancialPreview();

                            // تعبئة اسم العميل في نموذج نقل الكفالة
                            $('#transferClientName').text(contract.client_name || '-');

                            // إظهار الـ modal
                            $('#closeContractModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message ||
                                    'حدث خطأ أثناء جلب بيانات العقد'
                            });
                        }

                        dataTable.ajax.reload();
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء جلب بيانات العقد'
                        });
                    }
                });
            });

            // إغلاق العقد وإرجاع العاملة متاحة
            $('#closeAndMakeAvailableBtn').click(function() {
                Swal.fire({
                    title: 'تأكيد الإغلاق',
                    text: 'هل أنت متأكد من إغلاق العقد وإرجاع العاملة متاحة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، إغلاق العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('adjustment_type', $('#closeAdjustmentType').val());
                        formData.append('adjustment_amount', $('#closeAdjustmentAmount').val() || 0);
                        formData.append('clear_remaining_amount', $('#closeClearRemainingToggle').is(':checked') ? ($('#closeClearRemainingAmount').val() || 0) : 0);
                        formData.append('close_payment_status', $('#closePaymentStatus').val());
                        formData.append('close_client_iban', $('#closeClientIban').val());

                        const closePaymentReceipt = $('#closePaymentReceipt')[0];
                        if (closePaymentReceipt && closePaymentReceipt.files && closePaymentReceipt.files[0]) {
                            formData.append('close_payment_receipt', closePaymentReceipt.files[0]);
                        }

                        const closeClientIbanAttachment = $('#closeClientIbanAttachment')[0];
                        if (closeClientIbanAttachment && closeClientIbanAttachment.files && closeClientIbanAttachment.files[0]) {
                            formData.append('close_client_iban_attachment', closeClientIbanAttachment.files[0]);
                        }

                        $.ajax({
                            url: `/admin/RentContract/${selectedContractId}/close-and-make-available`,
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
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'تم بنجاح',
                                        text: response.message ||
                                            'تم إغلاق العقد وإرجاع العاملة متاحة بنجاح'
                                    }).then(() => {
                                        $('#closeContractModal').modal('hide');
                                        dataTable.ajax.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'خطأ',
                                        text: response.message ||
                                            'حدث خطأ أثناء إغلاق العقد'
                                    });
                                }

                                dataTable.ajax.reload();
                            },
                            error: function(xhr) {
                                let message = 'حدث خطأ أثناء إغلاق العقد';
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                                    if (firstKey) {
                                        message = xhr.responseJSON.errors[firstKey][0];
                                    }
                                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'خطأ',
                                    text: message
                                });
                            }
                        });
                    }
                });
            });

            // إظهار نموذج نقل الكفالة
            $('#transferSponsorshipBtn').click(function() {
                $('#transferSponsorshipForm').show();
                $('#closeContractOptionsRow').hide();
                if (!$('#transfer-adjustment-rows tr').length) {
                    addTransferAdjustmentRow({
                        type: 'income',
                        label: 'نقل خدمات',
                        amount: $('#transferAmount').val() || '0.00'
                    });
                }
            });

            // إلغاء نقل الكفالة
            $('#cancelTransferBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('#closeContractOptionsRow').show();
                $('#sponsorshipTransferForm')[0].reset();
                resetTransferAdjustments();
            });

            // حساب المبلغ المتبقي تلقائياً
            $('#totalAmount, #paidAmount').on('input', function() {
                const total = parseFloat($('#totalAmount').val()) || 0;
                const paid = parseFloat($('#paidAmount').val()) || 0;
                const remaining = total - paid;
                $('#remainingAmount').val(remaining.toFixed(2));
            });

            $('#transferAmount').on('input', function() {
                const firstRowAmount = $('#transfer-adjustment-rows tr:first .transfer-adjustment-amount');
                if (firstRowAmount.length && !firstRowAmount.val()) {
                    firstRowAmount.val($(this).val() || '0.00');
                }
            });

            // تأكيد نقل الكفالة
            $('#sponsorshipTransferForm').submit(function(e) {
                e.preventDefault();

                const formData = {
                    contract_id: selectedContractId,
                    transfer_amount: $('#transferAmount').val(),
                    total_amount: $('#totalAmount').val(),
                    paid_amount: $('#paidAmount').val(),
                    remaining_amount: $('#remainingAmount').val(),
                    notes: $('#transferNotes').val(),
                    transfer_finance_items_present: 1,
                    transfer_finance_items: getTransferAdjustments(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/transfer-sponsorship`,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح',
                                text: response.message ||
                                    'تم إغلاق العقد ونقل الكفالة بنجاح'
                            }).then(() => {
                                $('#closeContractModal').modal('hide');
                                dataTable.ajax.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'حدث خطأ أثناء نقل الكفالة'
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'حدث خطأ أثناء نقل الكفالة';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            if (firstKey) {
                                message = xhr.responseJSON.errors[firstKey][0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: message
                        });
                    }
                });
            });

            $(document).on('click', '.sign-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد توقيع العقد',
                    text: 'هل تم توقيع العقد من العميل فعلاً؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تم التوقيع',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'sign-contract',
                            'تم تأكيد توقيع العقد بنجاح');
                    }
                });
            });

            $(document).on('click', '.deliver-maid', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد تسليم العاملة',
                    text: 'هل تم تسليم العاملة للعميل فعلاً؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، تم التسليم',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'deliver-maid',
                            'تم تأكيد تسليم العاملة بنجاح');
                    }
                });
            });

            $(document).on('click', '.activate-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تأكيد تفعيل العقد',
                    text: 'هل تريد تفعيل العقد وبدء الخدمة؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، فعّل العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'activate-contract',
                            'تم تفعيل العقد بنجاح');
                    }
                });
            });

            function executeWorkflowAction(contractId, action, successMessage) {
                $.ajax({
                    url: `{{ url('/admin') }}/RentContract/${contractId}/${action}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        dataTable.ajax.reload();

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم بنجاح!',
                                text: response.message || successMessage,
                                confirmButtonText: 'موافق'
                            })
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ!',
                                text: response.message || 'حدث خطأ أثناء تنفيذ العملية'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'حدث خطأ أثناء تنفيذ العملية';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ!',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    </script>
@endsection
