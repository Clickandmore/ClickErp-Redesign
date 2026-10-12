@extends('admin.layout.indexs.index')

@section('page-title')
    استيراد البيانات
@endsection

@php
    $canUpload = AdminGuardHasAnyPerm(['data_import_upload']);
    $canGenerateBonds = AdminGuardHasAnyPerm(['data_import_generate_bonds']);
    $canReview = AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']);

    // Temporarily hide Arabia / Alrafedin import cards; keep their import handlers and history available.
    $hiddenTemplateKeys = [
        'arabia_clients',
        'arabia_cvs',
        'arabia_recruitment_orders',
        'legacy_rental_orders',
        'alrafedin_transfer_services',
        'finance_cost_centers',
    ];
    $visibleTemplates = collect($templates)->except($hiddenTemplateKeys)->all();

    // تصنيف القوالب في مجموعات لتسهيل الوصول إليها؛ أي نوع غير مصنف يظهر ضمن "أخرى".
    $groupDefinitions = [
        'people' => ['label' => 'العملاء والعمالة', 'icon' => 'fas fa-users', 'keys' => ['clients', 'arabia_clients', 'maids', 'arabia_cvs', 'rental_maids', 'recruitment_offices']],
        'staff' => ['label' => 'الموظفون والمستخدمون', 'icon' => 'fas fa-user-tie', 'keys' => ['admins', 'employees']],
        'contracts' => ['label' => 'العقود والطلبات', 'icon' => 'fas fa-file-contract', 'keys' => ['recruitment_contracts', 'shelter_contracts', 'rent_contracts', 'sponsorship_transfers', 'ingaz_delegations', 'arabia_recruitment_orders', 'recruitment_contracts_clients_maids', 'rent_contracts_clients_rental_maids', 'legacy_rental_orders', 'alrafedin_transfer_services', 'complaints']],
        'finance' => ['label' => 'المالية', 'icon' => 'fas fa-coins', 'keys' => ['finance_cost_centers', 'finance_accounts', 'finance_constraints', 'receipt_bonds', 'disbursement_bonds', 'invoices', 'legacy_invoices']],
    ];

    $groupDefinitions['contracts']['keys'][] = 'external_office_dispatches';
    $groupDefinitions['finance']['keys'][] = 'musaned_transfers';
    $groupedTemplates = [];
    foreach ($groupDefinitions as $groupKey => $group) {
        foreach ($group['keys'] as $key) {
            if (isset($visibleTemplates[$key])) {
                $groupedTemplates[$groupKey][$key] = $visibleTemplates[$key];
            }
        }
    }
    $groupedKeys = collect($groupDefinitions)->pluck('keys')->flatten()->all();
    $otherTemplates = collect($visibleTemplates)->except($groupedKeys)->all();
    if (!empty($otherTemplates)) {
        $groupDefinitions['other'] = ['label' => 'أخرى', 'icon' => 'fas fa-layer-group', 'keys' => array_keys($otherTemplates)];
        $groupedTemplates['other'] = $otherTemplates;
    }

    $selectedType = old('type', request('type'));
    $selectedType = isset($visibleTemplates[$selectedType ?? '']) ? $selectedType : null;
    $pendingCount = $reviewBatches->where('status', '!=', 'approved')->count();

    $defaultTab = $canUpload ? 'upload' : ($canReview || $reviewBatches->isNotEmpty() ? 'reviews' : ($canGenerateBonds ? 'bonds' : 'reviews'));
@endphp

@section('style')
    <style>
        .import-panel {
            border: 1px solid #e8edf5;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }

        .import-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .import-step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #e8edf5;
            border-radius: 10px;
            background: #fff;
        }

        .import-step-number {
            flex: 0 0 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: #eef4ff;
            color: #0d6efd;
        }

        .import-step-title {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .import-step-text {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .import-tabs {
            border-bottom: 1px solid #e8edf5;
            gap: 4px;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .import-tabs .nav-link {
            white-space: nowrap;
            border: 0;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            color: #64748b;
            font-weight: 600;
            padding: 12px 16px;
            background: transparent;
        }

        .import-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        .form-step-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-step-label .import-step-number {
            flex-basis: 26px;
            height: 26px;
            font-size: 0.8rem;
        }

        .upload-zone {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
        }

        .template-details {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            height: 100%;
        }

        .template-details-empty {
            color: #94a3b8;
            text-align: center;
            padding: 40px 16px;
        }

        .template-icon {
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef4ff;
            color: #0d6efd;
        }

        .columns-preview {
            max-height: 180px;
            overflow: auto;
        }

        .template-group-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #334155;
            margin: 8px 0 12px;
        }

        .template-item {
            border: 1px solid #edf2f7;
            border-radius: 10px;
            padding: 14px;
            height: 100%;
            background: #fff;
            display: flex;
            flex-direction: column;
            transition: border-color .15s, box-shadow .15s;
        }

        .template-item:hover {
            border-color: #bfd4fb;
            box-shadow: 0 6px 18px rgba(13, 110, 253, 0.08);
        }

        .template-item.is-selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
        }

        .template-item .template-icon {
            flex-basis: 36px;
            width: 36px;
            height: 36px;
        }

        .template-description {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .template-filter .btn {
            border-radius: 20px;
        }

        .import-progress-overlay {
            position: fixed;
            inset: 0;
            z-index: 11000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(2px);
        }

        .import-progress-overlay.is-visible {
            display: flex;
        }

        .import-progress-dialog {
            width: min(520px, 100%);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.3);
        }

        .import-progress-dialog .progress {
            height: 18px;
        }

        @media (max-width: 767.98px) {
            .import-steps {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            {{-- رأس الصفحة --}}
            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-file-import text-primary me-2"></i>
                        استيراد البيانات
                    </h3>
                    <p class="text-muted mb-0">استيراد بيانات الأقسام من ملفات Excel مع مراجعتها قبل حفظها في النظام.</p>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary mt-3 mt-sm-0">
                    <i class="fas fa-chart-pie me-1"></i>
                    إدارة التقارير
                </a>
            </div>

            {{-- خطوات العمل --}}
            <div class="import-steps mb-4">
                <div class="import-step">
                    <span class="import-step-number">1</span>
                    <div>
                        <div class="import-step-title">حمّل القالب</div>
                        <div class="import-step-text">اختر نوع البيانات وحمّل قالب Excel الخاص به.</div>
                    </div>
                </div>
                <div class="import-step">
                    <span class="import-step-number">2</span>
                    <div>
                        <div class="import-step-title">عبّئ الملف وارفعه</div>
                        <div class="import-step-text">الأعمدة المعلّمة بـ <span class="text-danger">*</span> إلزامية.</div>
                    </div>
                </div>
                <div class="import-step">
                    <span class="import-step-number">3</span>
                    <div>
                        <div class="import-step-title">راجع واعتمد</div>
                        <div class="import-step-text">لا يُحفظ أي شيء قبل مراجعة الملف واعتماده.</div>
                    </div>
                </div>
            </div>

            {{-- التنبيهات --}}
            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}</div>
            @endif

            @if (session('import_errors'))
                <div class="alert alert-warning">
                    <div class="fw-bold mb-2">ملاحظات على الصفوف التي لم يتم استيرادها:</div>
                    <ul class="mb-0 ps-3">
                        @foreach (session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    @if (count(session('import_errors')) >= 10)
                        <div class="mt-2 text-muted">تم عرض أول 10 ملاحظات فقط.</div>
                    @endif
                </div>
            @endif

            @if (session('bond_generation_errors'))
                <div class="alert alert-warning">
                    <div class="fw-bold mb-2">ملاحظات توليد سندات القبض والصرف:</div>
                    <ul class="mb-0 ps-3">
                        @foreach (session('bond_generation_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="import-panel mb-4">
                {{-- التبويبات --}}
                <ul class="nav import-tabs px-2" id="import-tabs" role="tablist">
                    @if ($canUpload)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $defaultTab === 'upload' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-upload" data-tab="upload" type="button" role="tab">
                                <i class="fas fa-upload me-1"></i> رفع ملف جديد
                            </button>
                        </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $defaultTab === 'reviews' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-reviews" data-tab="reviews" type="button" role="tab">
                            <i class="fas fa-clipboard-check me-1"></i> المراجعة والاعتماد
                            @if ($pendingCount)
                                <span class="badge rounded-pill bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                            @endif
                        </button>
                    </li>
                    @if ($canGenerateBonds)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $defaultTab === 'bonds' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-bonds" data-tab="bonds" type="button" role="tab">
                                <i class="fas fa-receipt me-1"></i> توليد السندات
                            </button>
                        </li>
                    @endif
                    @if ($canReview)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" data-tab="history" type="button" role="tab">
                                <i class="fas fa-history me-1"></i> سجل الملفات المرفوعة
                            </button>
                        </li>
                    @endif
                </ul>

                <div class="tab-content p-3 p-lg-4">
                    {{-- تبويب: رفع ملف --}}
                    @if ($canUpload)
                        <div class="tab-pane fade {{ $defaultTab === 'upload' ? 'show active' : '' }}" id="tab-upload" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <form action="{{ route('reports.import.store') }}" method="POST" enctype="multipart/form-data" id="data-import-form">
                                        @csrf
                                        <div class="upload-zone p-3 p-lg-4">
                                            <div class="mb-3">
                                                <label for="import-type" class="form-step-label">
                                                    <span class="import-step-number">1</span> نوع البيانات
                                                </label>
                                                <select name="type" id="import-type" class="form-select" required>
                                                    <option value="">اختر نوع البيانات المراد استيرادها</option>
                                                    @foreach ($groupedTemplates as $groupKey => $groupTemplates)
                                                        <optgroup label="{{ $groupDefinitions[$groupKey]['label'] }}">
                                                            @foreach ($groupTemplates as $key => $template)
                                                                <option value="{{ $key }}" {{ $selectedType === $key ? 'selected' : '' }}>{{ $template['label'] }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                                <div class="form-text">أو اختر النوع من دليل القوالب بالأسفل.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="import-file" class="form-step-label">
                                                    <span class="import-step-number">2</span> ملف البيانات
                                                </label>
                                                <input type="file" name="file" id="import-file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                                <div class="form-text">الصيغ المقبولة: ‎.xlsx‎ ، ‎.xls‎ ، ‎.csv</div>
                                            </div>

                                            <div class="mb-3 {{ $selectedType === 'finance_constraints' ? '' : 'd-none' }}" id="finance-constraints-option">
                                                <div class="form-check p-3 ps-5 border rounded bg-white">
                                                    <input type="hidden" name="finance_constraints_missing_only" value="0">
                                                    <input class="form-check-input" type="checkbox" name="finance_constraints_missing_only"
                                                           value="1" id="finance-constraints-missing-only"
                                                           {{ old('finance_constraints_missing_only', '1') ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="finance-constraints-missing-only">
                                                        إدخال أرقام القيود المفقودة فقط وعدم تعديل أي قيد موجود
                                                        <span class="badge bg-success-subtle text-success border ms-1">موصى به</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary btn-lg" id="data-import-submit">
                                                    <i class="fas fa-upload me-1"></i>
                                                    رفع الملف للمراجعة
                                                </button>
                                            </div>
                                            <div class="small text-muted text-center mt-2">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                يُفحص الملف فقط عند الرفع، ولا يُحفظ شيء قبل الاعتماد.
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- تفاصيل القالب المختار --}}
                                <div class="col-lg-6">
                                    <div class="template-details p-3 p-lg-4">
                                        <div class="template-details-empty {{ $selectedType && isset($visibleTemplates[$selectedType]) ? 'd-none' : '' }}" data-template-detail-empty>
                                            <i class="fas fa-hand-pointer fa-2x mb-3 d-block"></i>
                                            اختر نوع البيانات لعرض أعمدة القالب والقيم المسموح بها ورابط تحميله.
                                        </div>

                                        @foreach ($visibleTemplates as $key => $template)
                                            <div class="{{ $selectedType === $key ? '' : 'd-none' }}" data-template-detail="{{ $key }}">
                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                    <span class="template-icon"><i class="{{ $template['icon'] }}"></i></span>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1">{{ $template['label'] }}</h5>
                                                        <div class="text-muted small" dir="ltr">{{ $template['route_hint'] }}</div>
                                                    </div>
                                                </div>

                                                <p class="text-muted mb-3" style="line-height: 1.7">{{ $template['description'] }}</p>
                                                <div class="alert alert-info">{{ $template['matching_hint'] }}</div>

                                                <div class="d-flex flex-wrap gap-2 mb-3">
                                                    @if (($template['downloadable'] ?? true) !== false)
                                                        <a href="{{ route('reports.import.template', $key) }}" class="btn btn-success">
                                                            <i class="fas fa-file-excel me-1"></i>
                                                            تحميل القالب
                                                        </a>
                                                    @else
                                                        <span class="badge bg-info text-dark align-self-center p-2">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            ارفع تقرير النظام القديم مباشرة دون قالب
                                                        </span>
                                                    @endif
                                                    @if (in_array($key, ['maids', 'arabia_cvs'], true))
                                                        <a href="{{ route('maids.bulk-import.create') }}" class="btn btn-outline-primary">
                                                            <i class="fas fa-check-double me-1"></i>
                                                            مراجعة استيراد السير
                                                        </a>
                                                    @endif
                                                </div>

                                                @if (!empty($template['columns']))
                                                    @php
                                                        $requiredCount = collect($template['columns'])->where('required', true)->count();
                                                        $columnsWithValues = collect($template['columns'])->filter(fn ($column) => !empty($column['valid_values']));
                                                    @endphp
                                                    <div class="fw-semibold small mb-2">
                                                        أعمدة القالب ({{ count($template['columns']) }} عموداً، منها {{ $requiredCount }} إلزامي)
                                                    </div>
                                                    <div class="columns-preview mb-3">
                                                        @foreach ($template['columns'] as $column)
                                                            <span class="badge bg-white text-dark border mb-1 fw-normal">
                                                                {{ $column['label'] }}
                                                                @if ($column['required'])
                                                                    <span class="text-danger">*</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>

                                                    @if ($columnsWithValues->isNotEmpty())
                                                        <details class="small">
                                                            <summary class="fw-semibold mb-2" style="cursor: pointer">القيم المسموح بها لبعض الأعمدة</summary>
                                                            @foreach ($columnsWithValues as $column)
                                                                <div class="fw-semibold mb-1">{{ $column['label'] }}:</div>
                                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                                    @foreach ($column['valid_values'] as $value)
                                                                        <span class="badge bg-white text-dark border fw-normal">{{ $value }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endforeach
                                                        </details>
                                                    @endif
                                                @else
                                                    <div class="alert alert-info py-2 small mb-0">
                                                        يستخدم قالب السير الذاتية التفصيلي الموجود في صفحة الاستيراد الجماعي.
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- دليل القوالب --}}
                            <hr class="my-4">
                            <div class="d-lg-flex align-items-center justify-content-between gap-3 mb-3">
                                <h5 class="mb-2 mb-lg-0">
                                    <i class="fas fa-th-large text-primary me-1"></i>
                                    دليل القوالب
                                </h5>
                                <div class="d-flex flex-column flex-md-row gap-2">
                                    <div class="template-filter btn-group flex-wrap" role="group">
                                        <button type="button" class="btn btn-sm btn-primary" data-group-filter="all">الكل</button>
                                        @foreach ($groupedTemplates as $groupKey => $groupTemplates)
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-group-filter="{{ $groupKey }}">
                                                {{ $groupDefinitions[$groupKey]['label'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <input type="search" class="form-control form-control-sm" id="template-search" placeholder="ابحث عن قالب..." style="min-width: 200px">
                                </div>
                            </div>

                            @foreach ($groupedTemplates as $groupKey => $groupTemplates)
                                <div data-template-group="{{ $groupKey }}">
                                    <div class="template-group-title">
                                        <i class="{{ $groupDefinitions[$groupKey]['icon'] }} text-muted me-1"></i>
                                        {{ $groupDefinitions[$groupKey]['label'] }}
                                        <span class="text-muted fw-normal small">({{ count($groupTemplates) }})</span>
                                    </div>
                                    <div class="row g-3 mb-3">
                                        @foreach ($groupTemplates as $key => $template)
                                            <div class="col-xxl-3 col-xl-4 col-md-6" data-template-card data-search="{{ $template['label'] }} {{ $template['description'] }}">
                                                <div class="template-item {{ $selectedType === $key ? 'is-selected' : '' }}" data-template-item="{{ $key }}">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <span class="template-icon"><i class="{{ $template['icon'] }}"></i></span>
                                                        <h6 class="mb-0">{{ $template['label'] }}</h6>
                                                    </div>
                                                    <p class="template-description mb-3" title="{{ $template['description'] }}">{{ $template['description'] }}</p>
                                                    <p class="small text-primary">{{ $template['matching_hint'] }}</p>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button type="button" class="btn btn-primary btn-sm" data-use-template="{{ $key }}">
                                                            <i class="fas fa-mouse-pointer me-1"></i>
                                                            اختيار
                                                        </button>
                                                        @if (($template['downloadable'] ?? true) !== false)
                                                            <a href="{{ route('reports.import.template', $key) }}" class="btn btn-outline-success btn-sm">
                                                                <i class="fas fa-file-excel me-1"></i>
                                                                تحميل القالب
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <div class="text-center text-muted py-4 d-none" id="template-search-empty">لا توجد قوالب مطابقة للبحث.</div>
                        </div>
                    @endif

                    {{-- تبويب: المراجعة والاعتماد --}}
                    <div class="tab-pane fade {{ $defaultTab === 'reviews' ? 'show active' : '' }}" id="tab-reviews" role="tabpanel">
                        <p class="text-muted mb-3">آخر 20 ملفاً رُفعت للمراجعة. افتح الملف لمراجعة الصفوف وأخطائها، ثم اعتماده.</p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الملف</th>
                                        <th>النوع</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الرفع</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reviewBatches as $batch)
                                        <tr>
                                            <td dir="auto" class="text-break">{{ $batch->original_name }}</td>
                                            <td>{{ $templates[$batch->type]['label'] ?? $batch->type }}</td>
                                            <td>
                                                @if ($batch->status === 'approved')
                                                    <span class="badge bg-success-subtle text-success border"><i class="fas fa-check me-1"></i>معتمد</span>
                                                @elseif ($batch->status === 'partial')
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border">معتمد جزئياً — صفوف تحتاج تصحيحاً</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="fas fa-hourglass-half me-1"></i>بانتظار المراجعة</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap small">{{ optional($batch->created_at)->format('Y-m-d h:i A') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('reports.import.show', $batch) }}" class="btn btn-sm {{ $batch->status === 'approved' ? 'btn-outline-secondary' : 'btn-primary' }}">
                                                    {{ $batch->status === 'approved' ? 'عرض النتائج' : 'مراجعة واعتماد' }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">لا توجد ملفات مراجعة بعد.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- تبويب: توليد السندات --}}
                    @if ($canGenerateBonds)
                        <div class="tab-pane fade {{ $defaultTab === 'bonds' ? 'show active' : '' }}" id="tab-bonds" role="tabpanel">
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-8">
                                    <h5 class="mb-2">توليد سندات القبض والصرف من القيود المحاسبية</h5>
                                    <p class="text-muted mb-3">
                                        يتم توليد السندات من القيود اليومية غير المرتبطة بسند، بشرط أن يحتوي القيد على طرف مدين واحد وطرف دائن واحد وأن يكون أحد الطرفين حساب صندوق أو بنك.
                                    </p>
                                    @if (!empty($bondGenerationStats['ready']))
                                        <div class="row g-2">
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="small text-muted">قيود مرشحة</div>
                                                    <div class="fs-5 fw-bold">{{ number_format($bondGenerationStats['candidate_constraints'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="small text-muted">سندات مرتبطة بقيود</div>
                                                    <div class="fs-5 fw-bold">{{ number_format($bondGenerationStats['existing_bonds'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="small text-muted">سندات القبض المستوردة</div>
                                                    <div class="fs-5 fw-bold text-success">{{ number_format($bondGenerationStats['imported_receipt_bonds'] ?? 0) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-xl-3">
                                                <div class="border rounded p-2 text-center">
                                                    <div class="small text-muted">إجمالي حركة القبض</div>
                                                    <div class="fs-5 fw-bold text-success">{{ number_format($bondGenerationStats['imported_receipt_movement'] ?? 0, 2) }} <small>ريال</small></div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning py-2 mb-0">
                                            جداول المالية المطلوبة غير جاهزة لتوليد السندات.
                                        </div>
                                    @endif
                                </div>
                                <div class="col-lg-4">
                                    <form action="{{ route('reports.import.generate-bonds') }}" method="POST"
                                          onsubmit="return confirm('سيتم إنشاء سندات قبض وصرف من القيود اليومية المؤهلة وغير المرتبطة بسند. هل تريد المتابعة؟')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-lg w-100" {{ empty($bondGenerationStats['ready']) ? 'disabled' : '' }}>
                                            <i class="fas fa-receipt me-1"></i>
                                            توليد سندات القبض والصرف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- تبويب: سجل الملفات المرفوعة --}}
                    @if ($canReview)
                        <div class="tab-pane fade" id="tab-history" role="tabpanel">
                            <p class="text-muted mb-3">الملفات الخام المرفوعة من هذه الصفحة.</p>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>نوع البيانات</th>
                                            <th>اسم الملف</th>
                                            <th>الحجم</th>
                                            <th>تاريخ الرفع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentUploads as $upload)
                                            <tr>
                                                <td>{{ $templates[$upload['type']]['label'] ?? $upload['type'] }}</td>
                                                <td dir="ltr" class="text-break">{{ $upload['name'] }}</td>
                                                <td class="text-nowrap">{{ number_format($upload['size'] / 1024, 1) }} KB</td>
                                                <td class="text-nowrap small">{{ \Carbon\Carbon::createFromTimestamp($upload['date'])->format('Y-m-d h:i A') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">لا توجد ملفات مرفوعة من هذه الصفحة بعد.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="import-progress-overlay" id="import-progress-overlay" role="dialog" aria-modal="true"
         aria-labelledby="import-progress-title" aria-describedby="import-progress-status">
        <div class="import-progress-dialog p-4 p-lg-5 text-center">
            <div class="mb-3 text-primary"><i class="fas fa-cloud-upload-alt fa-3x"></i></div>
            <h4 id="import-progress-title" class="mb-2">جارٍ رفع ملف الاستيراد</h4>
            <p id="import-progress-status" class="text-muted mb-3" aria-live="polite">يتم تجهيز الملف للرفع...</p>
            <div class="progress mb-2" dir="ltr">
                <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">0%</div>
            </div>
            <div class="small text-muted">يرجى عدم إغلاق الصفحة أو الانتقال إلى شاشة أخرى حتى اكتمال العملية.</div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // التبويبات واختيار القالب والبحث في دليل القوالب
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('#import-tabs [data-tab]');
            const tabStorageKey = 'data-import-active-tab';

            const activateTab = function (name) {
                tabButtons.forEach(function (button) {
                    const active = button.dataset.tab === name;
                    button.classList.toggle('active', active);
                    const pane = document.querySelector(button.dataset.bsTarget);
                    if (!pane) return;
                    pane.classList.toggle('show', active);
                    pane.classList.toggle('active', active);
                });
            };

            tabButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    activateTab(button.dataset.tab);
                    try { sessionStorage.setItem(tabStorageKey, button.dataset.tab); } catch (error) {}
                });
            });

            // لا نستعيد التبويب المحفوظ إذا كانت هناك أخطاء في نموذج الرفع أو نوع محدد مسبقاً.
            const keepDefaultTab = {{ ($errors->any() || $selectedType) ? 'true' : 'false' }};
            if (!keepDefaultTab) {
                try {
                    const saved = sessionStorage.getItem(tabStorageKey);
                    if (saved && document.querySelector('#import-tabs [data-tab="' + saved + '"]')) activateTab(saved);
                } catch (error) {}
            }

            const typeSelect = document.getElementById('import-type');
            if (!typeSelect) return;

            const financeOption = document.getElementById('finance-constraints-option');
            const emptyDetail = document.querySelector('[data-template-detail-empty]');
            const details = document.querySelectorAll('[data-template-detail]');
            const items = document.querySelectorAll('[data-template-item]');

            const showTemplate = function (key) {
                details.forEach(function (detail) {
                    detail.classList.toggle('d-none', detail.dataset.templateDetail !== key);
                });
                items.forEach(function (item) {
                    item.classList.toggle('is-selected', item.dataset.templateItem === key);
                });
                if (emptyDetail) emptyDetail.classList.toggle('d-none', !!key);
                if (financeOption) financeOption.classList.toggle('d-none', key !== 'finance_constraints');
            };

            typeSelect.addEventListener('change', function () {
                showTemplate(typeSelect.value);
            });

            document.querySelectorAll('[data-use-template]').forEach(function (button) {
                button.addEventListener('click', function () {
                    typeSelect.value = button.dataset.useTemplate;
                    showTemplate(typeSelect.value);
                    typeSelect.closest('form').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.getElementById('import-file').focus({ preventScroll: true });
                });
            });

            // البحث والتصفية حسب المجموعة
            const searchInput = document.getElementById('template-search');
            const filterButtons = document.querySelectorAll('[data-group-filter]');
            const groups = document.querySelectorAll('[data-template-group]');
            const emptySearch = document.getElementById('template-search-empty');
            let activeGroup = 'all';

            const applyFilter = function () {
                const term = (searchInput.value || '').trim().toLowerCase();
                let visibleTotal = 0;

                groups.forEach(function (group) {
                    const groupMatches = activeGroup === 'all' || group.dataset.templateGroup === activeGroup;
                    let visibleInGroup = 0;

                    group.querySelectorAll('[data-template-card]').forEach(function (card) {
                        const visible = groupMatches && (!term || card.dataset.search.toLowerCase().includes(term));
                        card.classList.toggle('d-none', !visible);
                        if (visible) visibleInGroup++;
                    });

                    group.classList.toggle('d-none', visibleInGroup === 0);
                    visibleTotal += visibleInGroup;
                });

                emptySearch.classList.toggle('d-none', visibleTotal > 0);
            };

            searchInput.addEventListener('input', applyFilter);
            filterButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    activeGroup = button.dataset.groupFilter;
                    filterButtons.forEach(function (other) {
                        other.classList.toggle('btn-primary', other === button);
                        other.classList.toggle('btn-outline-primary', other !== button);
                    });
                    applyFilter();
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('data-import-form');
            const submitButton = document.getElementById('data-import-submit');
            const overlay = document.getElementById('import-progress-overlay');
            const progressBar = document.getElementById('import-progress-bar');
            const progressTitle = document.getElementById('import-progress-title');
            const progressStatus = document.getElementById('import-progress-status');
            let importInProgress = false;

            if (!form || !window.XMLHttpRequest || !(new XMLHttpRequest()).upload) {
                return;
            }

            const preventLeaving = function (event) {
                if (!importInProgress) return;
                event.preventDefault();
                event.returnValue = '';
            };

            const setProgress = function (percent) {
                const value = Math.max(0, Math.min(100, Math.round(percent)));
                progressBar.style.width = value + '%';
                progressBar.setAttribute('aria-valuenow', String(value));
                progressBar.textContent = value + '%';
            };

            const unlockPage = function () {
                importInProgress = false;
                submitButton.disabled = false;
                overlay.classList.remove('is-visible');
                document.body.style.overflow = '';
            };

            const showError = function (message) {
                unlockPage();
                const oldAlert = document.getElementById('import-upload-error');
                if (oldAlert) oldAlert.remove();

                const alert = document.createElement('div');
                alert.id = 'import-upload-error';
                alert.className = 'alert alert-danger';
                alert.setAttribute('role', 'alert');
                alert.textContent = message || 'تعذر رفع الملف. يرجى المحاولة مرة أخرى.';
                form.closest('.import-panel').before(alert);
                alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            window.addEventListener('beforeunload', preventLeaving);

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity() || importInProgress) return;

                event.preventDefault();
                importInProgress = true;
                submitButton.disabled = true;
                overlay.classList.add('is-visible');
                document.body.style.overflow = 'hidden';
                progressTitle.textContent = 'جارٍ رفع ملف الاستيراد';
                progressStatus.textContent = 'بدأ رفع الملف...';
                setProgress(0);

                const request = new XMLHttpRequest();
                request.open('POST', form.action, true);
                request.setRequestHeader('Accept', 'application/json');
                request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                request.upload.addEventListener('progress', function (uploadEvent) {
                    if (!uploadEvent.lengthComputable) {
                        progressStatus.textContent = 'جارٍ رفع الملف...';
                        return;
                    }

                    const percent = (uploadEvent.loaded / uploadEvent.total) * 100;
                    setProgress(percent);
                    progressStatus.textContent = 'تم رفع ' + Math.round(percent) + '% من الملف.';
                });

                request.upload.addEventListener('load', function () {
                    setProgress(100);
                    progressTitle.textContent = 'جارٍ معالجة البيانات';
                    progressStatus.textContent = 'اكتمل نقل الملف، ويتم الآن فحص البيانات دون حفظها...';
                });

                request.addEventListener('load', function () {
                    let response = {};
                    try { response = JSON.parse(request.responseText || '{}'); } catch (error) {}

                    if (request.status >= 200 && request.status < 300 && response.redirect) {
                        progressTitle.textContent = 'اكتملت العملية بنجاح';
                        progressStatus.textContent = 'جارٍ فتح نتيجة الاستيراد...';
                        importInProgress = false;
                        window.location.assign(response.redirect);
                        return;
                    }

                    const validationMessage = response.errors
                        ? Object.values(response.errors).flat().join(' ')
                        : response.message;
                    const statusSuffix = request.status ? ' (HTTP ' + request.status + ')' : '';
                    showError(validationMessage || ('انقطعت معالجة الملف على الخادم' + statusSuffix + '. راجع ملفات المراجعة الأخيرة قبل إعادة الرفع.'));
                });

                request.addEventListener('error', function () {
                    showError('انقطع الاتصال بالخادم أثناء الفحص. راجع ملفات المراجعة الأخيرة قبل إعادة الرفع.');
                });

                request.addEventListener('abort', function () {
                    showError('تم إلغاء رفع الملف قبل اكتماله.');
                });

                request.send(new FormData(form));
            });
        });
    </script>
@endsection
