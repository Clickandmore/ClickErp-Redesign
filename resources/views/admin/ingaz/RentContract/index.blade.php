@extends('admin.layout.indexs.index')
@section('style')
    <!-- DataTables CSS من CDN -->
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- الملفات المحلية -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> --}}
    <style>
        #dataTableObject {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        #dataTableObject_wrapper {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }

        #dataTableObject th:last-child,
        #dataTableObject td:last-child {
            position: -webkit-sticky !important;
            position: sticky !important;
            right: 0;
            z-index: 6;
            background: #fff;
            min-width: 155px;
            box-shadow: -8px 0 14px rgba(0, 0, 0, 0.06);
        }

        #dataTableObject thead th:last-child {
            z-index: 8;
            background: #f8f9fa;
        }

        html[dir="rtl"] #dataTableObject th:last-child,
        html[dir="rtl"] #dataTableObject td:last-child {
            right: auto;
            left: 0;
            box-shadow: 8px 0 14px rgba(0, 0, 0, 0.06);
        }

        #dataTableObject .actions-dropdown {
            position: static !important;
        }

        #dataTableObject .actions-dropdown .dropdown-menu.dropdown-menu-end {
            position: fixed !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 2147483647 !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
            will-change: top, left;
        }

        .rent-contract-actions-menu.dropdown-menu,
        #dataTableObject .actions-dropdown .dropdown-menu.dropdown-menu-end.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            z-index: 2147483647 !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
        }

        .extend-contract-modal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .extend-contract-modal .modal-header {
            background: #0f766e;
            border-bottom: 0;
        }

        .extend-summary-band {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }

        .extend-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .extend-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            min-height: 74px;
        }

        .extend-stat-label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .extend-stat-value {
            color: #111827;
            font-weight: 700;
            font-size: 15px;
            word-break: break-word;
        }

        .extend-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .extend-status-pill.is-ok {
            background: #dcfce7;
            color: #166534;
        }

        .extend-status-pill.is-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .extend-status-pill.is-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .extend-preview-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 10px;
            padding: 14px;
        }

        .rent-stats-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 1.5rem;
        }

        .rent-stat-card {
            border: 1px solid #e5e7eb;
            border-right: 4px solid var(--rent-stat-color, #2563eb);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            padding: 16px;
            min-height: 116px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .rent-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .rent-stat-value {
            color: #111827;
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
        }

        .rent-stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #eff6ff;
            background: color-mix(in srgb, var(--rent-stat-color, #2563eb) 12%, #fff);
            color: var(--rent-stat-color, #2563eb);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .rent-stat-primary { --rent-stat-color: #2563eb; }
        .rent-stat-success { --rent-stat-color: #16a34a; }
        .rent-stat-info { --rent-stat-color: #0891b2; }
        .rent-stat-warning { --rent-stat-color: #d97706; }
        .rent-stat-danger { --rent-stat-color: #dc2626; }
        .rent-stat-muted { --rent-stat-color: #475569; }

        .filter-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .filter-section-title {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            margin: 14px 0 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .rent-period-box {
            min-width: 210px;
            display: grid;
            gap: 6px;
        }

        .rent-period-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: #334155;
        }

        .rent-period-row strong {
            color: #64748b;
            font-weight: 700;
            white-space: nowrap;
        }

        .rent-period-row span {
            color: #111827;
            font-weight: 700;
            direction: ltr;
            text-align: left;
        }

        .rent-period-money {
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px dashed #e5e7eb;
        }

        #dataTableObject th,
        #dataTableObject td {
            vertical-align: middle;
        }

        #dataTableObject thead th {
            white-space: nowrap;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            background: #f8fafc;
        }

        .rent-person-cell {
            min-width: 220px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rent-person-avatar {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            overflow: hidden;
        }

        .rent-person-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rent-person-main {
            min-width: 0;
        }

        .rent-person-name {
            color: #111827;
            font-weight: 800;
            margin-bottom: 3px;
        }

        .rent-person-meta {
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        .rent-person-meta a {
            color: #2563eb;
            text-decoration: none;
        }

        #dataTableObject th:first-child,
        #dataTableObject td:first-child {
            width: 54px;
            text-align: center;
            color: #64748b;
            font-weight: 800;
        }

        #dataTableObject td:nth-last-child(2),
        #dataTableObject td:nth-last-child(3) {
            white-space: nowrap;
        }

        .close-contract-modal .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .close-contract-modal .modal-header {
            background: #155e75;
            border-bottom: 0;
        }

        .close-section-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .close-section-card .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
            font-weight: 700;
        }

        .close-section-card .section-body {
            padding: 14px;
        }

        .close-option-card {
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .close-option-card:hover {
            border-color: #94a3b8;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .close-option-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .close-option-list {
            padding-right: 18px;
            margin-bottom: 16px;
            color: #475569;
            font-size: 13px;
            line-height: 1.8;
            text-align: right;
        }

        .transfer-summary-box {
            border: 1px solid #fde68a;
            background: #fffbeb;
            border-radius: 10px;
            padding: 12px 14px;
            color: #78350f;
        }

        @media (max-width: 991.98px) {
            .rent-stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .extend-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .rent-stats-grid {
                grid-template-columns: 1fr;
            }

            .extend-stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                    <a href="{{ route('form-settings.index') }}#editor-rental-contract" class="btn btn-secondary me-2">
                        <i class="fas fa-file-signature me-2"></i>
                        إعدادات نصوص العقد
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
        <div class="rent-stats-grid">
            <div class="rent-stat-card rent-stat-primary">
                <div>
                    <div class="rent-stat-label">إجمالي عقود التأجير</div>
                    <div class="rent-stat-value" id="totalContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
            </div>

            <div class="rent-stat-card rent-stat-success">
                <div>
                    <div class="rent-stat-label">العقود النشطة</div>
                    <div class="rent-stat-value" id="activeContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

            <div class="rent-stat-card rent-stat-info">
                <div>
                    <div class="rent-stat-label">العقود المكتملة</div>
                    <div class="rent-stat-value" id="completedContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
            </div>

            <div class="rent-stat-card rent-stat-muted">
                <div>
                    <div class="rent-stat-label">مغلقة - متاحة</div>
                    <div class="rent-stat-value" id="availableContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>

            <div class="rent-stat-card rent-stat-warning">
                <div>
                    <div class="rent-stat-label">تم النقل</div>
                    <div class="rent-stat-value" id="transferredContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>

            <div class="rent-stat-card rent-stat-danger">
                <div>
                    <div class="rent-stat-label">ملغية قبل اكتمال المدة</div>
                    <div class="rent-stat-value" id="cancelledContracts">0</div>
                </div>
                <div class="rent-stat-icon">
                    <i class="fas fa-times-circle"></i>
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
                <div class="filter-section-title">حالة العقد وتاريخ الإنشاء</div>
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
                            <option value="frozen" {{ request('status') === 'frozen' ? 'selected' : '' }}>مجمد</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">تاريخ إنشاء العقد من:</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="dateToFilter" class="form-label">تاريخ إنشاء العقد إلى:</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="expiryFilter" class="form-label">حالة انتهاء العقد:</label>
                        <select id="expiryFilter" class="form-select">
                            <option value="" {{ request('expiry_filter') ? '' : 'selected' }}>جميع العقود</option>
                            <option value="expired" {{ request('expiry_filter') === 'expired' ? 'selected' : '' }}>
                                العقود المنتهية
                            </option>
                            <option value="not_expired" {{ request('expiry_filter') === 'not_expired' ? 'selected' : '' }}>
                                العقود غير المنتهية
                            </option>
                        </select>
                    </div>
                </div>

                <div class="filter-section-title">تواريخ مدة العقد</div>
                <div class="row">
                    <div class="col-md-3">
                        <label for="startDateFromFilter" class="form-label">بداية العقد من:</label>
                        <input type="date" id="startDateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="startDateToFilter" class="form-label">بداية العقد إلى:</label>
                        <input type="date" id="startDateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDateFromFilter" class="form-label">نهاية العقد من:</label>
                        <input type="date" id="endDateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDateToFilter" class="form-label">نهاية العقد إلى:</label>
                        <input type="date" id="endDateToFilter" class="form-control">
                    </div>
                </div>

                <div class="filter-section-title">بيانات العميل والعاملة</div>
                <div class="row mt-3">
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
                <div class="filter-section-title">التصنيف والمتابعة</div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="marketerFilter" class="form-label">المسوق:</label>
                        <select id="marketerFilter" class="form-select">
                            <option value="">جميع المسوقين</option>
                            @foreach(($marketers ?? collect()) as $marketer)
                                <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($branchesEnabled ?? false)
                        <div class="col-md-3">
                            <label for="branchFilter" class="form-label">{{ __('admin.branches') }}:</label>
                            <select id="branchFilter" class="form-select">
                                <option value="">{{ helperTrans('ingaz.all') }}</option>
                                @foreach(($branches ?? collect()) as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
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
                            <th>بيانات العميل</th>
                            <th>العاملة</th>
                            <th>معلومات العقد</th>
                            <th>المسوق</th>
                            @if($branchesEnabled ?? false)
                                <th>{{ __('admin.branches') }}</th>
                            @endif
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
        <div class="modal-dialog modal-xl close-contract-modal">
            <div class="modal-content">
                <div class="modal-header text-white">
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
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-info-circle"></i>معلومات العقد</div>
                                <div class="section-body">
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
                                            <label for="closureDate" class="fw-bold mb-1">تاريخ الإنهاء:</label>
                                            <input type="date" class="form-control form-control-sm" id="closureDate" required>
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
                                    <div class="alert alert-danger mb-0 d-none" id="earlyClosureNotice">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        سيتم تسجيل هذا العقد كعقد ملغي لأن تاريخ الإنهاء قبل تاريخ نهاية العقد، وسيظهر ذلك في التفاصيل والإحصائيات.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4" id="closeContractAttachmentsRow" style="display: none;">
                        <div class="col-12">
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-paperclip"></i>مرفقات العقد</div>
                                <div class="section-body">
                                    <div id="contractAttachmentsList" class="d-flex flex-wrap gap-2">
                                        <span class="text-muted">لا توجد مرفقات مسجلة على هذا العقد.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4" id="closeFinancialSettlementRow" style="display: none;">
                        <div class="col-12">
                            <div class="close-section-card">
                                <div class="section-title"><i class="fas fa-file-invoice-dollar"></i>البيان المالي قبل الإنهاء</div>
                                <div class="section-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <strong>التكلفة الشهرية:</strong>
                                            <p id="financialMonthlyCost">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>مدة العقد:</strong>
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
                                        <div class="col-md-3">
                                            <strong>أيام بعد انتهاء العقد:</strong>
                                            <p id="financialOverdueDays">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>تكلفة اليوم:</strong>
                                            <p id="financialDailyRate">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>تكلفة الأيام المستخدمة:</strong>
                                            <p id="financialUsedCost">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>الاسترجاع المقترح:</strong>
                                            <p id="financialSuggestedRefund">-</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>مستحق الشركة:</strong>
                                            <p id="financialCompanyDue">-</p>
                                        </div>
                                    </div>
                                    <div class="row g-3 mt-2 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label">نوع التسوية</label>
                                            <select class="form-select" id="closeAdjustmentType">
                                                <option value="refund">استرجاع للعميل</option>
                                                <option value="company_due">مستحق للشركة</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" id="closeAdjustmentAmountLabel">مبلغ الاسترجاع</label>
                                            <input type="number" class="form-control" id="closeAdjustmentAmount"
                                                min="0" step="0.01" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">المستحق النهائي</label>
                                            <div class="fw-bold">
                                                <span id="finalDueAmount">0.00 ريال</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">سبب الإرجاع / الإغلاق المبكر</label>
                                            <textarea class="form-control" id="closeReturnReason" rows="3"
                                                placeholder="اكتب سبب إرجاع العاملة أو الإغلاق المبكر..."></textarea>
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
                                            <label class="form-label">آيبان العميل <small class="text-muted">(اختياري)</small></label>
                                            <input type="text" class="form-control" id="closeClientIban" dir="ltr"
                                                maxlength="34" placeholder="SAxxxxxxxxxxxxxxxxxxxxxx">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">مرفق آيبان العميل <small class="text-muted">(اختياري)</small></label>
                                            <input type="file" class="form-control" id="closeClientIbanAttachment"
                                                accept=".jpg,.jpeg,.png,.pdf">
                                            <div class="form-text d-none" id="closeClientIbanAttachmentCurrentWrapper">
                                                <a href="#" target="_blank"
                                                    id="closeClientIbanAttachmentCurrent">عرض المرفق الحالي</a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox"
                                                    id="sendClosureMessageToggle">
                                                <label class="form-check-label" for="sendClosureMessageToggle">
                                                    إرسال رسالة إنهاء العقد للعميل
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        <button type="button" class="btn btn-secondary" id="backToCloseOptionsBtn">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            رجوع للخيارات
                                        </button>
                                        <button type="button" class="btn btn-success" id="confirmCloseAndMakeAvailableBtn">
                                            <i class="fas fa-check me-2"></i>
                                            تأكيد الإغلاق والإتاحة
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- خيارات الإغلاق -->
                    <div class="row g-3" id="closeContractOptionsRow">
                        <div class="col-md-6">
                            <div class="close-option-card">
                                <div class="close-option-icon bg-success text-white">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <h6 class="fw-bold mb-2">إغلاق وإرجاع العاملة متاحة</h6>
                                <ul class="close-option-list">
                                    <li>يتم إنهاء عقد الإيجار الحالي.</li>
                                    <li>تعود العاملة إلى حالة متاحة للتأجير.</li>
                                    <li>يمكن تسجيل الاسترجاع أو تصفير المتبقي من البيان المالي أعلاه.</li>
                                </ul>
                                <button type="button" class="btn btn-success w-100" id="closeAndMakeAvailableBtn">
                                    <i class="fas fa-check me-2"></i>
                                    اختيار الإغلاق والإتاحة
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="close-option-card">
                                <div class="close-option-icon bg-warning text-dark">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <h6 class="fw-bold mb-2">إغلاق ونقل الكفالة</h6>
                                <ul class="close-option-list">
                                    <li>يتم إنهاء عقد الإيجار الحالي بحالة تم النقل.</li>
                                    <li>تبقى العاملة غير متاحة لأنها خرجت بنقل خدمات.</li>
                                    <li>لا يتم إنشاء عقد جديد، وتسجل مبالغ النقل فقط.</li>
                                </ul>
                                <button type="button" class="btn btn-warning w-100" id="transferSponsorshipBtn">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    اختيار نقل الكفالة
                                </button>
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
                                                <h6 class="mb-1">{{ documentSetting('document_company_name', documentSetting('website_name', config('app.name'))) }}</h6>
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

                                <form id="sponsorshipTransferForm" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="transfer-summary-box mb-3">
                                                <i class="fas fa-info-circle me-1"></i>
                                                نقل الكفالة لا ينشئ عقداً جديداً. أدخل المبلغ الكامل للنقل، وسيتم استخدامه في التوثيق والبنود المالية.
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
                                            <div class="border rounded p-3 mb-3 bg-light">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-credit-card text-primary me-2"></i>
                                                        بيانات الدفع
                                                    </h6>
                                                    <small class="text-muted">طريقة الدفع وحالة السداد والمرفقات</small>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentType" class="form-label">طريقة الدفع <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" id="transferPaymentType" name="payment_type" required>
                                                            <option value="">اختر الطريقة</option>
                                                            <option value="cash">نقداً</option>
                                                            <option value="bank_transfer">تحويل بنكي</option>
                                                            <option value="mada">مدى</option>
                                                            <option value="tabby">تابي</option>
                                                            <option value="tamara">تمارا</option>
                                                            <option value="pay_later">الدفع لاحقاً</option>
                                                            <option value="other">أخرى</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentStatus" class="form-label">حالة الدفع <span
                                                                class="text-danger">*</span></label>
                                                        <select class="form-select" id="transferPaymentStatus" name="payment_status" required>
                                                            <option value="">اختر الحالة</option>
                                                            <option value="paid">تم الدفع</option>
                                                            <option value="partial">مدفوع جزئياً</option>
                                                            <option value="pending">معلق</option>
                                                            <option value="later">الدفع لاحقاً</option>
                                                            <option value="bank_pending">بانتظار التحويل</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label for="transferPaymentReceipt" class="form-label">
                                                            إيصال الدفع <span class="text-danger d-none" id="transferReceiptRequiredMark">*</span>
                                                        </label>
                                                        <input type="file" class="form-control" id="transferPaymentReceipt"
                                                            name="payment_receipt" accept=".jpg,.jpeg,.png,.pdf">
                                                        <div class="form-text d-none" id="transferReceiptRequiredNote">
                                                            الإيصال مطلوب عند اختيار تم الدفع أو مدفوع جزئياً.
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="transferAttachments" class="form-label">مرفقات إضافية</label>
                                                        <input type="file" class="form-control" id="transferAttachments"
                                                            name="attachments[]" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" multiple>
                                                    </div>
                                                </div>
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
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox"
                                                    id="sendTransferClosureMessageToggle">
                                                <label class="form-check-label" for="sendTransferClosureMessageToggle">
                                                    إرسال رسالة إغلاق ونقل كفالة للعميل
                                                </label>
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
        <div class="modal-dialog modal-xl extend-contract-modal">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title" id="extendContractModalLabel">
                        <i class="fas fa-calendar-plus me-2"></i>
                        تمديد عقد الإيجار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="extendContractErrors" class="alert alert-danger d-none"></div>

                    <div class="extend-summary-band mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <div class="text-muted small">رقم العقد</div>
                                <div class="h5 mb-0" id="extend_contract_number">-</div>
                            </div>
                            <div class="extend-status-pill is-ok" id="extend_contract_status_badge">
                                <i class="fas fa-circle-check"></i>
                                <span>ضمن المدة</span>
                            </div>
                        </div>
                        <div class="extend-stat-grid">
                            <div class="extend-stat">
                                <div class="extend-stat-label">بداية العقد</div>
                                <div class="extend-stat-value" id="extend_current_start_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">نهاية العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_current_end_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">مدة العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_current_duration_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">الأيام المستخدمة</div>
                                <div class="extend-stat-value" id="extend_used_days_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">الأيام المتبقية / التأخير</div>
                                <div class="extend-stat-value" id="extend_remaining_days_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">قيمة العقد الحالية</div>
                                <div class="extend-stat-value" id="extend_contract_value_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">تكلفة اليوم التقريبية</div>
                                <div class="extend-stat-value" id="extend_daily_rate_text">-</div>
                            </div>
                            <div class="extend-stat">
                                <div class="extend-stat-label">عدد مرات التمديد</div>
                                <div class="extend-stat-value" id="extend_extensions_count_text">-</div>
                            </div>
                        </div>
                    </div>

                    <form id="extendContractForm" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="extend_current_end_value">
                        <input type="hidden" id="extend_monthly_cost_value">
                        <input type="hidden" id="extend_contract_value_value">
                        <input type="hidden" id="extend_duration_days_value">
                        <input type="hidden" id="extend_daily_rate_value">

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="extend_extension_months" class="form-label">شهور التمديد</label>
                                <input type="number" class="form-control" id="extend_extension_months"
                                    name="extension_months" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_extra_days" class="form-label">أيام إضافية</label>
                                <input type="number" class="form-control" id="extend_extension_extra_days"
                                    name="extension_days" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_days" class="form-label">إجمالي أيام التمديد</label>
                                <input type="text" class="form-control bg-light" id="extend_extension_days" readonly>
                            </div>
                            <div class="col-md-3">
                                <label for="extend_extension_end_date" class="form-label">تاريخ النهاية الجديد</label>
                                <input type="text" class="form-control bg-light" id="extend_extension_end_date" readonly>
                            </div>
                            <div class="col-12">
                                <div class="extend-preview-box">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-muted small">قيمة التمديد المتوقعة</div>
                                            <div class="fw-bold" id="extend_extension_value">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small">مدة التمديد المحفوظة</div>
                                            <div class="fw-bold" id="extend_new_duration_text">-</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted small">قيمة التمديد المحفوظة</div>
                                            <div class="fw-bold" id="extend_new_contract_value_text">-</div>
                                        </div>
                                    </div>
                                </div>
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
                    <button type="submit" class="btn btn-success" id="extendContractSubmit" form="extendContractForm">
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

            let openRentContractActionMenu = null;

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
                        d.start_date_from = $('#startDateFromFilter').val();
                        d.start_date_to = $('#startDateToFilter').val();
                        d.end_date_from = $('#endDateFromFilter').val();
                        d.end_date_to = $('#endDateToFilter').val();
                        d.client_phone = $('#clientPhoneFilter').val();
                        d.client_name = $('#clientNameFilter').val();
                        d.maid_passport = $('#maidPassportFilter').val();
                        d.maid_name = $('#maidNameFilter').val();
                        d.contract_number = $('#contractNumberFilter').val();
                        d.marketer_id = $('#marketerFilter').val();
                        d.branch_id = $('#branchFilter').length ? $('#branchFilter').val() : '';
                        d.extension_filter = $('#extensionFilter').val();
                        d.expiry_filter = $('#expiryFilter').val();
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
                    @if($branchesEnabled ?? false)
                    {
                        data: 'branch',
                        name: 'branch'
                    },
                    @endif
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
                preDrawCallback: function() {
                    closeRentContractActionMenu();
                },
                drawCallback: function() {
                    console.log('Table drawn successfully');
                    $('#loadingOverlay').hide();
                    initializeRentContractActionDropdowns();
                }
            });

            function setImportantStyle(element, property, value) {
                element.style.setProperty(property, value, 'important');
            }

            function positionRentContractActionMenu(menu, toggle) {
                if (!toggle) {
                    toggle = menu.data('rentContractToggle');
                }

                if (!toggle) {
                    return;
                }

                const buttonRect = toggle.getBoundingClientRect();
                const menuElement = menu instanceof jQuery ? menu[0] : menu;
                const menuRect = menuElement.getBoundingClientRect();
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const spacing = 8;
                const menuWidth = menuRect.width || 220;
                const menuHeight = menuRect.height || 260;

                let left = buttonRect.right - menuWidth;
                let top = buttonRect.bottom + spacing;

                if (top + menuHeight > viewportHeight - spacing) {
                    top = buttonRect.top - menuHeight - spacing;
                }

                left = Math.max(spacing, Math.min(left, viewportWidth - menuWidth - spacing));
                top = Math.max(spacing, Math.min(top, viewportHeight - menuHeight - spacing));

                setImportantStyle(menuElement, 'position', 'fixed');
                setImportantStyle(menuElement, 'left', `${Math.round(left)}px`);
                setImportantStyle(menuElement, 'top', `${Math.round(top)}px`);
                setImportantStyle(menuElement, 'right', 'auto');
                setImportantStyle(menuElement, 'bottom', 'auto');
                setImportantStyle(menuElement, 'transform', 'none');
                setImportantStyle(menuElement, 'z-index', '2147483647');
            }

            function closeRentContractActionMenu() {
                if (!openRentContractActionMenu) {
                    return;
                }

                const $menu = openRentContractActionMenu;
                const $parent = $menu.data('rentContractParent');
                const $toggle = $($menu.data('rentContractToggle'));

                $menu.removeClass('show rent-contract-actions-menu').hide().removeAttr('style');

                if ($parent && $parent.length) {
                    $menu.appendTo($parent);
                }

                $toggle.removeClass('show').attr('aria-expanded', 'false');
                openRentContractActionMenu = null;
            }

            function initializeRentContractActionDropdowns() {
                $('#dataTableObject .actions-dropdown .actions-btn').each(function() {
                    $(this)
                        .removeAttr('data-bs-toggle data-bs-display data-bs-popper')
                        .attr('aria-expanded', 'false');
                });
            }

            initializeRentContractActionDropdowns();

            $(document).on('click', '#dataTableObject .actions-dropdown .actions-btn', function(event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                const toggle = this;
                const $dropdown = $(toggle).closest('.actions-dropdown');
                const $menu = $dropdown.find('.dropdown-menu').first();

                if (!$menu.length) {
                    return false;
                }

                if (openRentContractActionMenu && openRentContractActionMenu[0] === $menu[0]) {
                    closeRentContractActionMenu();
                    return false;
                }

                closeRentContractActionMenu();

                $menu
                    .data('rentContractParent', $dropdown)
                    .data('rentContractToggle', toggle)
                    .appendTo(document.body)
                    .addClass('show rent-contract-actions-menu')
                    .show();

                $(toggle).addClass('show').attr('aria-expanded', 'true');
                openRentContractActionMenu = $menu;
                positionRentContractActionMenu($menu, toggle);

                return false;
            });

            $(document).on('click', '.rent-contract-actions-menu', function(event) {
                event.stopPropagation();
            });

            $(document).on('click', '.rent-contract-actions-menu .dropdown-item', function() {
                setTimeout(closeRentContractActionMenu, 150);
            });

            $(document).on('click', function() {
                closeRentContractActionMenu();
            });

            $(window).on('scroll resize', function() {
                if (openRentContractActionMenu) {
                    positionRentContractActionMenu(openRentContractActionMenu);
                }
            });

            const filterFields = [
                '#statusFilter',
                '#dateFromFilter',
                '#dateToFilter',
                '#startDateFromFilter',
                '#startDateToFilter',
                '#endDateFromFilter',
                '#endDateToFilter',
                '#clientPhoneFilter',
                '#clientNameFilter',
                '#maidPassportFilter',
                '#maidNameFilter',
                '#contractNumberFilter',
                '#marketerFilter',
                '#branchFilter',
                '#extensionFilter',
                '#expiryFilter'
            ].filter((selector) => $(selector).length).join(', ');

            function applyRentContractFilters() {
                updateStatistics();
                dataTable.ajax.reload();
            }

            // Apply Filters
            $('#applyFilters').click(function() {
                applyRentContractFilters();
            });

            $(document).on('keydown', filterFields, function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyRentContractFilters();
                }
            });

            // Clear Filters
            $('#clearFilters').click(function() {
                $('#statusFilter, #marketerFilter, #branchFilter, #extensionFilter, #expiryFilter')
                    .val('');
                $('#dateFromFilter, #dateToFilter, #startDateFromFilter, #startDateToFilter, #endDateFromFilter, #endDateToFilter, #clientPhoneFilter, #clientNameFilter, #maidPassportFilter, #maidNameFilter, #contractNumberFilter')
                    .val('');
                updateStatistics();
                dataTable.ajax.reload();
            });

            // Export Excel
            $('#exportExcel').click(function() {
                window.location.href = "{{ route('RentContract.export.excel') }}?" + $.param({
                    status: $('#statusFilter').val(),
                    date_from: $('#dateFromFilter').val(),
                    date_to: $('#dateToFilter').val(),
                    start_date_from: $('#startDateFromFilter').val(),
                    start_date_to: $('#startDateToFilter').val(),
                    end_date_from: $('#endDateFromFilter').val(),
                    end_date_to: $('#endDateToFilter').val(),
                    client_phone: $('#clientPhoneFilter').val(),
                    client_name: $('#clientNameFilter').val(),
                    maid_passport: $('#maidPassportFilter').val(),
                    maid_name: $('#maidNameFilter').val(),
                    contract_number: $('#contractNumberFilter').val(),
                    marketer_id: $('#marketerFilter').val(),
                    branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                    extension_filter: $('#extensionFilter').val(),
                    expiry_filter: $('#expiryFilter').val()
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

            function toNumber(value, fallback = 0) {
                const number = Number(value);
                return Number.isFinite(number) ? number : fallback;
            }

            function setExtendStatusBadge(remainingDays) {
                const badge = $('#extend_contract_status_badge');
                const icon = badge.find('i');
                const text = badge.find('span');

                badge.removeClass('is-ok is-warning is-danger');
                icon.removeClass('fa-circle-check fa-triangle-exclamation fa-circle-xmark');

                if (remainingDays === null || typeof remainingDays === 'undefined' || remainingDays === '') {
                    badge.addClass('is-warning');
                    icon.addClass('fa-triangle-exclamation');
                    text.text('المدة غير محددة');
                    return;
                }

                const days = parseInt(remainingDays, 10);
                if (days < 0) {
                    badge.addClass('is-danger');
                    icon.addClass('fa-circle-xmark');
                    text.text('متأخر ' + Math.abs(days) + ' يوم');
                } else if (days === 0) {
                    badge.addClass('is-warning');
                    icon.addClass('fa-triangle-exclamation');
                    text.text('ينتهي اليوم');
                } else {
                    badge.addClass('is-ok');
                    icon.addClass('fa-circle-check');
                    text.text('ضمن المدة');
                }
            }

            function renderRemainingDays(remainingDays) {
                if (remainingDays === null || typeof remainingDays === 'undefined' || remainingDays === '') {
                    return '-';
                }

                const days = parseInt(remainingDays, 10);
                if (days < 0) {
                    return 'متأخر ' + Math.abs(days) + ' يوم';
                }

                if (days === 0) {
                    return 'ينتهي اليوم';
                }

                return days + ' يوم متبقي';
            }

            function updateExtensionPreview() {
                const months = parseInt($('#extend_extension_months').val(), 10) || 0;
                const extraDays = parseInt($('#extend_extension_extra_days').val(), 10) || 0;
                const endDateStr = $('#extend_current_end_value').val();
                const contractValue = toNumber($('#extend_contract_value_value').val());
                const currentDurationDays = toNumber($('#extend_duration_days_value').val());
                const storedDailyRate = toNumber($('#extend_daily_rate_value').val());
                const dailyRate = storedDailyRate || (currentDurationDays > 0 && contractValue > 0 ? contractValue / currentDurationDays : 0);
                const extensionDays = (months * 30) + extraDays;

                if (!extensionDays || !endDateStr) {
                    $('#extend_extension_days').val('');
                    $('#extend_extension_end_date').val('');
                    $('#extend_extension_value').text('-');
                    $('#extend_new_duration_text').text('-');
                    $('#extend_new_contract_value_text').text('-');
                    return;
                }

                const baseDate = new Date(endDateStr);
                if (Number.isNaN(baseDate.getTime())) {
                    $('#extend_extension_days').val('');
                    $('#extend_extension_end_date').val('');
                    $('#extend_extension_value').text('-');
                    $('#extend_new_duration_text').text('-');
                    $('#extend_new_contract_value_text').text('-');
                    return;
                }

                const newEndDate = new Date(baseDate.getTime() + (extensionDays * 24 * 60 * 60 * 1000));
                const extensionValue = dailyRate * extensionDays;

                $('#extend_extension_days').val(extensionDays + ' يوم');
                $('#extend_extension_end_date').val(formatDate(newEndDate));
                $('#extend_extension_value').text(formatCurrency(extensionValue) + ' ريال');
                $('#extend_new_duration_text').text(extensionDays + ' يوم');
                $('#extend_new_contract_value_text').text(formatCurrency(extensionValue) + ' ريال');
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
                $('#extend_extension_value').text('-');
                $('#extend_new_duration_text').text('-');
                $('#extend_new_contract_value_text').text('-');
                $('#extend_current_end_value').val('');
                $('#extend_monthly_cost_value').val('');
                $('#extend_contract_value_value').val('');
                $('#extend_duration_days_value').val('');
                $('#extend_daily_rate_value').val('');
                $('#extend_contract_number').text('-');
                $('#extend_current_start_text').text('-');
                $('#extend_current_end_text').text('-');
                $('#extend_current_duration_text').text('-');
                $('#extend_used_days_text').text('-');
                $('#extend_remaining_days_text').text('-');
                $('#extend_contract_value_text').text('-');
                $('#extend_daily_rate_text').text('-');
                $('#extend_extensions_count_text').text('-');
                $('#extend_extension_months').val('0');
                $('#extend_extension_extra_days').val('0');
                $('#extend_extension_payment_receipt').prop('required', false);
                $('#extendReceiptRequiredMark, #extendReceiptRequiredNote').addClass('d-none');
                setExtendStatusBadge(null);
            }

            $(document).on('click', '.open-extend-modal', function() {
                const button = $(this);
                const extendUrl = button.data('extend-url');
                const contractNumber = button.data('contract-number') || '-';
                const ingazFrom = button.data('ingaz-from') || '';
                const ingazTo = button.data('ingaz-to') || '';
                const monthlyCost = toNumber(button.data('monthly-cost'));
                const contractValue = toNumber(button.data('contract-value'));
                const durationMonths = toNumber(button.data('duration-months'));
                const durationDays = toNumber(button.data('duration-days'));
                const remainingDays = button.data('remaining-days');
                const usedDays = button.data('used-days');
                const extensionsCount = toNumber(button.data('extensions-count'));
                const dailyRate = durationDays > 0 && contractValue > 0 ? contractValue / durationDays : monthlyCost / 30;

                resetExtendModal();
                $('#extendContractForm').attr('action', extendUrl);
                $('#extend_contract_number').text(contractNumber);
                $('#extend_current_start_text').text(ingazFrom || 'غير محدد');
                $('#extend_current_end_text').text(ingazTo || 'غير محدد');
                const durationParts = [];
                if (durationMonths) {
                    durationParts.push(durationMonths + ' شهر');
                }
                if (durationDays) {
                    durationParts.push(durationDays + ' يوم');
                }
                $('#extend_current_duration_text').text(durationParts.length ? durationParts.join(' / ') : '-');
                $('#extend_used_days_text').text(usedDays !== '' && typeof usedDays !== 'undefined' ? (usedDays + ' يوم') : '-');
                $('#extend_remaining_days_text').text(renderRemainingDays(remainingDays));
                $('#extend_contract_value_text').text(formatCurrency(contractValue) + ' ريال');
                $('#extend_daily_rate_text').text(formatCurrency(dailyRate) + ' ريال');
                $('#extend_extensions_count_text').text(extensionsCount + ' مرة');
                $('#extend_current_end_value').val(ingazTo || '');
                $('#extend_monthly_cost_value').val(monthlyCost || 0);
                $('#extend_contract_value_value').val(contractValue || 0);
                $('#extend_duration_days_value').val(durationDays || 0);
                $('#extend_daily_rate_value').val(dailyRate || 0);
                setExtendStatusBadge(remainingDays);

                updateExtensionPreview();
                $('#extendContractModal').modal('show');
            });

            $('#extend_extension_months, #extend_extension_extra_days').on('input change', function() {
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
                const months = parseInt($('#extend_extension_months').val(), 10) || 0;
                const extraDays = parseInt($('#extend_extension_extra_days').val(), 10) || 0;
                const receiptInput = $('#extend_extension_payment_receipt')[0];
                const hasReceipt = !!(receiptInput && receiptInput.files && receiptInput.files.length > 0);

                $('#extendContractErrors').addClass('d-none').empty();
                submitBtn.prop('disabled', true);

                if ((months * 30) + extraDays <= 0) {
                    const message = 'يرجى تحديد مدة التمديد بالشهور أو الأيام.';
                    $('#extendContractErrors').removeClass('d-none').html(message);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: message
                    });
                    submitBtn.prop('disabled', false);
                    return;
                }

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
                const adjustmentType = $('#closeAdjustmentType').val() || 'refund';
                const clearRemainingEnabled = $('#closeClearRemainingToggle').is(':checked');
                let clearRemainingAmount = clearRemainingEnabled ? parseFloat($('#closeClearRemainingAmount').val() || 0) : 0;

                $('#closeAdjustmentAmountLabel').text(adjustmentType === 'company_due' ? 'مبلغ مستحق الشركة' : 'مبلغ الاسترجاع');

                if (clearRemainingAmount > remainingAmount) {
                    clearRemainingAmount = remainingAmount;
                    $('#closeClearRemainingAmount').val(clearRemainingAmount.toFixed(2));
                }

                const finalRemaining = Math.max(0, remainingAmount - clearRemainingAmount);

                $('#finalDueAmount').text(formatCurrency(adjustmentAmount) + ' ريال');
                $('#finalRemainingAmount').text(formatCurrency(finalRemaining) + ' ريال');
            }

            function formatCloseRemainingDaysText(contract) {
                const overdueDays = parseInt(contract.overdue_days || 0, 10);

                if (overdueDays > 0) {
                    return 'منتهي منذ ' + overdueDays + ' يوم';
                }

                return contract.remaining_days || '-';
            }

            function formatContractDuration(contract) {
                if (contract.duration_label) {
                    return contract.duration_label;
                }

                const durationDays = parseInt(contract.duration_days || 0, 10);
                const durationMonths = parseInt(contract.duration_months || 0, 10);

                if (durationDays && (!durationMonths || durationDays !== durationMonths * 30)) {
                    if (durationDays === 7) {
                        return 'أسبوع';
                    }
                    if (durationDays === 14) {
                        return 'أسبوعين';
                    }
                    if (durationDays % 7 === 0) {
                        return (durationDays / 7) + ' أسابيع';
                    }
                    return durationDays + ' يوم';
                }

                return durationMonths ? (durationMonths + ' شهر') : (durationDays ? durationDays + ' يوم' : '-');
            }

            function fillCloseFinancialFields(contract) {
                $('#remainingDays').text(formatCloseRemainingDaysText(contract));
                $('#financialMonthlyCost').text(formatCurrency(contract.monthly_cost || 0) + ' ريال');
                $('#financialDurationMonths').text(formatContractDuration(contract));
                $('#financialDurationDays').text((contract.duration_days || 0) ? (contract.duration_days + ' يوم') : '-');
                $('#financialContractValue').text(formatCurrency(contract.contract_value || 0) + ' ريال');
                $('#financialPaidAmount').text(formatCurrency(contract.paid_amount || 0) + ' ريال');
                $('#financialRemainingAmount')
                    .text(formatCurrency(contract.remaining_amount || 0) + ' ريال')
                    .data('raw', contract.remaining_amount || 0);
                $('#financialUsedDays').text(contract.used_days !== null && typeof contract.used_days !== 'undefined' ? (contract.used_days + ' يوم') : '-');
                $('#financialRemainingDays').text(contract.remaining_days_value !== null && typeof contract.remaining_days_value !== 'undefined' ? (contract.remaining_days_value + ' يوم') : '-');
                $('#financialOverdueDays').text(contract.overdue_days !== null && typeof contract.overdue_days !== 'undefined' ? (contract.overdue_days + ' يوم') : '-');
                $('#financialDailyRate').text(formatCurrency(contract.daily_rate || 0) + ' ريال');
                $('#financialUsedCost').text(formatCurrency(contract.used_cost || 0) + ' ريال');
                $('#financialSuggestedRefund').text(formatCurrency(contract.calculated_refund_value || 0) + ' ريال');
                $('#financialCompanyDue').text(formatCurrency(contract.calculated_company_due_value || 0) + ' ريال');
                $('#earlyClosureNotice').toggleClass('d-none', !contract.is_early_closure);
                if (contract.closure_status_text) {
                    $('#currentStatus').text((contract.status_text || '-') + ' / بعد الإنهاء: ' + contract.closure_status_text);
                }
                $('#closeAdjustmentType')
                    .data('refund-value', contract.refund_value || 0)
                    .data('company-due-value', contract.company_due_value || 0)
                    .val(contract.settlement_type || 'refund');
                $('#closeAdjustmentAmount').val(parseFloat(contract.settlement_value || 0).toFixed(2));
                updateCloseFinancialPreview();
            }

            function reloadCloseFinancialSummary() {
                if (!selectedContractId) {
                    return;
                }

                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/details`,
                    type: 'GET',
                    data: {
                        closure_date: $('#closureDate').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            fillCloseFinancialFields(response.contract);
                        }
                    }
                });
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
                $('#sendClosureMessageToggle').prop('checked', false);
                $('#closeReturnReason').val('');
                $('#closureDate').val('').removeAttr('min');
                $('#closeClientIbanAttachmentCurrentWrapper').addClass('d-none');
                $('#closeClientIbanAttachmentCurrent').attr('href', '#');
                $('#closeAdjustmentType').data('refund-value', 0).data('company-due-value', 0);
                $('#financialRemainingAmount').data('raw', 0);
                $('#financialDailyRate').text('-');
                $('#financialUsedCost').text('-');
                $('#financialSuggestedRefund').text('-');
                $('#financialOverdueDays').text('-');
                $('#financialCompanyDue').text('-');
                updateCloseFinancialPreview();
            }

            $('#closureDate').on('change', function() {
                reloadCloseFinancialSummary();
            });

            $('#closeAdjustmentType').on('change', function() {
                const selectedType = $(this).val() || 'refund';
                const defaultAmount = selectedType === 'company_due'
                    ? $(this).data('company-due-value')
                    : $(this).data('refund-value');

                $('#closeAdjustmentAmount').val(parseFloat(defaultAmount || 0).toFixed(2));
                updateCloseFinancialPreview();
            });

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

            function toggleTransferReceiptRequirement() {
                const paymentStatus = $('#transferPaymentStatus').val();
                const receiptRequired = paymentStatus === 'paid' || paymentStatus === 'partial';

                $('#transferPaymentReceipt').prop('required', receiptRequired);
                $('#transferReceiptRequiredMark, #transferReceiptRequiredNote').toggleClass('d-none', !receiptRequired);
            }

            function resetTransferPaymentFields() {
                $('#transferPaymentType').val('');
                $('#transferPaymentStatus').val('');
                $('#transferPaymentReceipt').val('');
                $('#transferAttachments').val('');
                $('#sendTransferClosureMessageToggle').prop('checked', false);
                toggleTransferReceiptRequirement();
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

            $('#transferPaymentStatus').on('change', function() {
                toggleTransferReceiptRequirement();
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
                    data: {
                        status_filter: $('#statusFilter').val(),
                        date_from: $('#dateFromFilter').val(),
                        date_to: $('#dateToFilter').val(),
                        start_date_from: $('#startDateFromFilter').val(),
                        start_date_to: $('#startDateToFilter').val(),
                        end_date_from: $('#endDateFromFilter').val(),
                        end_date_to: $('#endDateToFilter').val(),
                        client_phone: $('#clientPhoneFilter').val(),
                        client_name: $('#clientNameFilter').val(),
                        maid_passport: $('#maidPassportFilter').val(),
                        maid_name: $('#maidNameFilter').val(),
                        contract_number: $('#contractNumberFilter').val(),
                        marketer_id: $('#marketerFilter').val(),
                        branch_id: $('#branchFilter').length ? $('#branchFilter').val() : '',
                        extension_filter: $('#extensionFilter').val(),
                        expiry_filter: $('#expiryFilter').val()
                    },
                    success: function(data) {
                        $('#totalContracts').text(data.total || 0);
                        $('#activeContracts').text(data.active || 0);
                        $('#completedContracts').text(data.completed || 0);
                        $('#cancelledContracts').text(data.cancelled || 0);
                        $('#availableContracts').text(data.available || 0);
                        $('#transferredContracts').text(data.transferred || 0);
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
                            resetTransferPaymentFields();
                            $('#transferSponsorshipForm').hide();
                            $('#closeContractAttachmentsRow').hide();
                            $('#closeFinancialSettlementRow').hide();
                            $('#closeContractOptionsRow').show();
                            $('#sponsorshipTransferForm')[0].reset();
                            resetTransferPaymentFields();

                            // تعبئة بيانات العقد في الـ modal
                            $('#contractId').text(contract.id);
                            $('#clientName').text(contract.client_name || '-');
                            $('#maidName').text(contract.maid_name || '-');
                            $('#currentStatus').text(contract.status_text || '-');
                            $('#startDate').text(contract.start_date || '-');
                            $('#endDate').text(contract.end_date || '-');
                            $('#closureDate')
                                .attr('min', contract.start_date || null)
                                .val(contract.closure_date || new Date().toISOString().slice(0, 10));
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
                            $('#financialDailyRate').text(formatCurrency(contract.daily_rate || 0) + ' ريال');
                            $('#financialUsedCost').text(formatCurrency(contract.used_cost || 0) + ' ريال');
                            $('#financialSuggestedRefund').text(formatCurrency(contract.calculated_refund_value || 0) + ' ريال');
                            $('#closeAdjustmentAmount').val(parseFloat(contract.refund_value || 0).toFixed(2));
                            fillCloseFinancialFields(contract);
                            $('#closeReturnReason').val(contract.closure_return_reason || '');
                            $('#closePaymentStatus').val(contract.payment_status || '');
                            $('#closeClientIban').val(contract.closure_client_iban || '');
                            renderContractAttachments(contract.attachments || []);

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

            function executeCloseAndMakeAvailable() {
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
                        formData.append('closure_date', $('#closureDate').val());
                        formData.append('closure_return_reason', $('#closeReturnReason').val());
                        formData.append('close_payment_status', $('#closePaymentStatus').val());
                        formData.append('close_client_iban', $('#closeClientIban').val());
                        formData.append('send_closure_message', $('#sendClosureMessageToggle').is(':checked') ? 1 : 0);

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
            }

            // إظهار بيان الإغلاق بعد اختيار إرجاع العاملة متاحة
            $('#closeAndMakeAvailableBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('#closeContractOptionsRow').hide();
                $('#closeContractAttachmentsRow').show();
                $('#closeFinancialSettlementRow').show();
            });

            $('#confirmCloseAndMakeAvailableBtn').click(function() {
                executeCloseAndMakeAvailable();
            });

            $('#backToCloseOptionsBtn').click(function() {
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#transferSponsorshipForm').hide();
                $('#closeContractOptionsRow').show();
            });

            function renderContractAttachments(attachments) {
                const container = $('#contractAttachmentsList');
                container.empty();

                if (!attachments.length) {
                    container.append($('<span class="text-muted">لا توجد مرفقات مسجلة على هذا العقد.</span>'));
                    return;
                }

                attachments.forEach(function(attachment) {
                    const link = $('<a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"></a>');
                    link.attr('href', attachment.url);
                    link.append($('<i class="fas fa-paperclip me-1"></i>'));
                    link.append(document.createTextNode(attachment.label || 'مرفق'));
                    container.append(link);
                });
            }

            // إظهار نموذج نقل الكفالة
            $('#transferSponsorshipBtn').click(function() {
                $('#transferSponsorshipForm').show();
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#closeContractOptionsRow').hide();
                $('#closeContractModal .modal-body').animate({
                    scrollTop: $('#transferSponsorshipForm').position().top
                }, 200);
                if (!$('#transfer-adjustment-rows tr').length) {
                    addTransferAdjustmentRow({
                        type: 'income',
                        label: 'نقل خدمات',
                        amount: $('#totalAmount').val() || '0.00'
                    });
                }
            });

            // إلغاء نقل الكفالة
            $('#cancelTransferBtn').click(function() {
                $('#transferSponsorshipForm').hide();
                $('#closeContractAttachmentsRow').hide();
                $('#closeFinancialSettlementRow').hide();
                $('#closeContractOptionsRow').show();
                $('#sponsorshipTransferForm')[0].reset();
                resetTransferAdjustments();
                resetTransferPaymentFields();
            });

            // حساب المبلغ المتبقي تلقائياً
            $('#totalAmount, #paidAmount').on('input', function() {
                const total = parseFloat($('#totalAmount').val()) || 0;
                const paid = parseFloat($('#paidAmount').val()) || 0;
                const remaining = total - paid;
                $('#remainingAmount').val(remaining.toFixed(2));

                const firstRow = $('#transfer-adjustment-rows tr:first');
                const firstRowAmount = firstRow.find('.transfer-adjustment-amount');
                const firstRowLabel = (firstRow.find('.transfer-adjustment-label').val() || '').trim();
                const currentRowAmount = parseFloat(firstRowAmount.val() || 0);
                if (firstRowAmount.length && firstRowLabel === 'نقل خدمات' && currentRowAmount <= 0) {
                    firstRowAmount.val(total.toFixed(2));
                }
            });

            // تأكيد نقل الكفالة
            $('#sponsorshipTransferForm').submit(function(e) {
                e.preventDefault();

                const paymentStatus = $('#transferPaymentStatus').val();
                const receiptInput = $('#transferPaymentReceipt')[0];
                const hasReceipt = !!(receiptInput && receiptInput.files && receiptInput.files.length > 0);

                if ((paymentStatus === 'paid' || paymentStatus === 'partial') && !hasReceipt) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع "تم الدفع" أو "مدفوع جزئياً".'
                    });
                    return;
                }

                const formData = new FormData(this);
                formData.append('contract_id', selectedContractId);
                formData.append('total_amount', $('#totalAmount').val());
                formData.append('paid_amount', $('#paidAmount').val());
                formData.append('remaining_amount', $('#remainingAmount').val());
                formData.append('notes', $('#transferNotes').val());
                formData.append('closure_date', $('#closureDate').val());
                formData.append('transfer_finance_items_present', '1');
                formData.append('send_closure_message', $('#sendTransferClosureMessageToggle').is(':checked') ? 1 : 0);
                formData.append('_token', '{{ csrf_token() }}');

                getTransferAdjustments().forEach(function(item, index) {
                    formData.append(`transfer_finance_items[${index}][type]`, item.type);
                    formData.append(`transfer_finance_items[${index}][label]`, item.label);
                    formData.append(`transfer_finance_items[${index}][amount]`, item.amount);
                });

                $.ajax({
                    url: `/admin/RentContract/${selectedContractId}/transfer-sponsorship`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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

            $(document).on('click', '.freeze-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'تجميد عقد الإيجار',
                    input: 'textarea',
                    inputLabel: 'سبب التجميد',
                    inputPlaceholder: 'اكتب سبب تجميد العقد',
                    inputAttributes: {
                        maxlength: 1000
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#64748b',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'تجميد العقد',
                    cancelButtonText: 'إلغاء',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'يرجى كتابة سبب التجميد';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'freeze', 'تم تجميد عقد الإيجار بنجاح', {
                            freeze_reason: result.value
                        });
                    }
                });
            });

            $(document).on('click', '.edit-freeze-date', function() {
                const contractId = $(this).data('id');
                const currentFrozenAt = $(this).data('frozen-at') || '';

                Swal.fire({
                    title: 'تغيير تاريخ التجميد',
                    input: 'datetime-local',
                    inputLabel: 'تاريخ التجميد',
                    inputValue: currentFrozenAt,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'حفظ التاريخ',
                    cancelButtonText: 'إلغاء',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'يرجى تحديد تاريخ التجميد';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'update-freeze-date', 'تم تحديث تاريخ التجميد بنجاح', {
                            frozen_at: result.value
                        });
                    }
                });
            });

            $(document).on('click', '.resume-contract', function() {
                const contractId = $(this).data('id');

                Swal.fire({
                    title: 'استئناف عقد الإيجار',
                    text: 'هل تريد استئناف هذا العقد؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'نعم، استئناف العقد',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeWorkflowAction(contractId, 'resume', 'تم استئناف عقد الإيجار بنجاح');
                    }
                });
            });

            function executeWorkflowAction(contractId, action, successMessage, extraData = {}) {
                $.ajax({
                    url: `{{ url('/admin') }}/RentContract/${contractId}/${action}`,
                    type: 'POST',
                    data: Object.assign({
                        _token: '{{ csrf_token() }}'
                    }, extraData),
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
