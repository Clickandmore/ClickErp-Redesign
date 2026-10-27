@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات الفروع
@endsection

@section('content')
    @php
        $branchLimit = $branchLimit ?? null;
        $canCreateBranch = $branchLimit === null || ($branchesCount ?? 0) < $branchLimit;
        $canManageBranches = @AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CREATE, \App\PermKeys::BRANCHES_CONTROL]);
        $canControl = @AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CONTROL]);
        $canViewRoles = @AdminGuardHasAnyPerm([\App\PermKeys::PERMISSIONS_VIEW, \App\PermKeys::PERMISSIONS_ASSIGN]);
        $branchesModuleEnabled = @SystemModuleEnabled('branches');
        $sharingSettings = $sharingSettings ?? [];
        $sharingBranches = $sharingBranches ?? collect();
        $branchSharingPolicies = $branchSharingPolicies ?? [];
        $transferSharingPolicies = $transferSharingPolicies ?? [];
        $activeSettingRows = [
            'share_maids' => [
                'icon' => 'fa-id-card',
                'title' => 'مشاركة السير الذاتية غير المسندة لفرع',
                'hint' => 'عند التفعيل تظهر السيرة التي بلا فرع لكل الفروع. السير المخصصة تبقى معزولة، مع استثناء maids_view_all_branches.',
            ],
            'shared_cost_center_root' => [
                'icon' => 'fa-sitemap',
                'title' => 'جذر مشترك لمراكز تكلفة الفروع',
                'hint' => 'سياسة مالية عامة: تنظم مراكز تكلفة جميع الفروع تحت أصل واحد.',
            ],
            'accounts_per_branch' => [
                'icon' => 'fa-book',
                'title' => 'تخصيص الحسابات على مستوى الفروع',
                'hint' => 'سياسة مالية عامة: الحساب غير المخصص متاح للجميع، والمخصص يظهر لفروعه فقط.',
            ],
        ];
        $branchSharingEntities = [
            'clients' => [
                'icon' => 'fa-users',
                'title' => 'العملاء',
                'hint' => 'السماح لجميع الفروع بمشاهدة عملاء الفرع المحدد.',
            ],
            'maids' => [
                'icon' => 'fa-id-badge',
                'title' => 'السير الذاتية المخصصة',
                'hint' => 'السماح لجميع الفروع بمشاهدة السير المسندة إلى الفرع المحدد.',
            ],
            'external_offices' => [
                'icon' => 'fa-globe',
                'title' => 'المكاتب الخارجية',
                'hint' => 'السماح لجميع الفروع بمشاهدة المكاتب التابعة للفرع المحدد.',
            ],
        ];
        $statCards = [
            ['icon' => 'fa-code-branch', 'tone' => 'primary', 'label' => 'إجمالي الفروع', 'value' => $branchesCount],
            ['icon' => 'fa-check-circle', 'tone' => 'success', 'label' => 'الفروع النشطة', 'value' => $activeBranchesCount],
            ['icon' => 'fa-layer-group', 'tone' => 'info', 'label' => 'العدد المسموح به', 'value' => $branchLimit === null ? 'غير محدود' : $branchLimit],
        ];
    @endphp

    <style>
        .branch-settings .bs-stat { display: flex; align-items: center; gap: .9rem; }
        .branch-settings .bs-stat-icon {
            width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem;
        }
        .branch-settings .bs-section { scroll-margin-top: 90px; }
        .branch-settings .bs-section > .card-header { background: transparent; border-bottom: 1px solid rgba(0, 0, 0, .06); padding: 1rem 1.25rem; }
        .branch-settings .bs-section-icon {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .branch-settings .bs-policy {
            border: 1px solid rgba(0, 0, 0, .08); border-radius: 12px; padding: 1rem;
            height: 100%; transition: border-color .15s, background-color .15s;
        }
        .branch-settings .bs-policy.is-on { border-color: rgba(var(--bs-primary-rgb), .45); background: rgba(var(--bs-primary-rgb), .04); }
        .branch-settings .bs-policy-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .branch-settings .bs-toggle {
            -webkit-appearance: none; appearance: none; position: relative; flex-shrink: 0;
            width: 2.6rem; height: 1.4rem; margin: 0; border: 0; border-radius: 999px;
            background: #cfd4dc; cursor: pointer; transition: background-color .15s; vertical-align: middle;
        }
        .branch-settings .bs-toggle::before {
            content: ""; position: absolute; top: .2rem; inset-inline-start: .2rem;
            width: 1rem; height: 1rem; border-radius: 50%; background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .25); transition: inset-inline-start .15s;
        }
        .branch-settings .bs-toggle:checked { background: var(--bs-primary, #0d6efd); }
        .branch-settings .bs-toggle:checked::before { inset-inline-start: 1.4rem; }
        .branch-settings .bs-check { width: 1.15rem; height: 1.15rem; margin: 0; cursor: pointer; vertical-align: middle; accent-color: var(--bs-primary, #0d6efd); }
        .branch-settings .bs-toggle:focus-visible, .branch-settings .bs-check:focus-visible { outline: 2px solid rgba(var(--bs-primary-rgb), .5); outline-offset: 2px; }
        .branch-settings :disabled.bs-toggle, .branch-settings :disabled.bs-check { opacity: .55; cursor: not-allowed; }
        .branch-settings .bs-matrix tbody tr:hover > td { background: #f8f9fb; }
        .branch-settings .bs-matrix th, .branch-settings .bs-matrix td { vertical-align: middle; white-space: nowrap; }
        .branch-settings .bs-matrix thead th { font-size: .8rem; font-weight: 600; color: #6c757d; background: #f8f9fb; }
        .branch-settings .bs-matrix .bs-sticky-col { position: sticky; inset-inline-start: 0; background: #fff; z-index: 1; min-width: 180px; }
        .branch-settings .bs-matrix thead .bs-sticky-col { background: #f8f9fb; }
        .branch-settings .bs-matrix .bs-self { color: #ced4da; }
        .branch-settings .bs-col-toggle { font-size: .72rem; padding: 0 .4rem; }
        .branch-settings .bs-side { position: sticky; top: 90px; }
        .branch-settings .bs-nav .nav-link { color: #495057; border-radius: 8px; padding: .5rem .75rem; display: flex; align-items: center; gap: .5rem; }
        .branch-settings .bs-nav .nav-link:hover { background: #f1f3f7; }
        .branch-settings .bs-legend-item { display: flex; gap: .65rem; padding: .65rem 0; border-top: 1px dashed rgba(0, 0, 0, .08); }
        .branch-settings .bs-legend-item:first-child { border-top: 0; padding-top: 0; }
        .branch-settings .bs-savebar {
            position: sticky; bottom: 0; z-index: 10; background: #fff;
            border: 1px solid rgba(0, 0, 0, .08); border-radius: 12px;
            box-shadow: 0 -6px 18px rgba(0, 0, 0, .06); padding: .75rem 1rem;
        }
        .branch-settings .bs-savebar.is-dirty { border-color: rgba(var(--bs-warning-rgb), .7); }
    </style>

    <div class="page-body branch-settings">
        <div class="container-fluid">

            {{-- رأس الصفحة --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-sliders-h text-primary me-2"></i>
                        إعدادات الفروع
                    </h3>
                    <div class="text-muted small">سياسات الشركة العامة، مشاركة البيانات المرجعية، وإتاحة نقل الخدمات بين الفروع.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-code-branch"></i> إدارة الفروع
                    </a>
                    @if ($canViewRoles)
                        <a href="{{ route('dash.roles.index') }}" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-user-shield"></i> صلاحيات الأدوار حسب الفرع
                        </a>
                    @endif
                    @if ($canManageBranches && $canCreateBranch)
                        <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> إضافة فرع
                        </a>
                    @endif
                </div>
            </div>

            {{-- مؤشرات --}}
            <div class="row g-3 mb-4">
                @foreach ($statCards as $stat)
                    <div class="col-6 col-xl-3">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body bs-stat">
                                <span class="bs-stat-icon bg-{{ $stat['tone'] }} bg-opacity-10 text-{{ $stat['tone'] }}">
                                    <i class="fas {{ $stat['icon'] }}"></i>
                                </span>
                                <div>
                                    <div class="text-muted small">{{ $stat['label'] }}</div>
                                    <div class="h4 mb-0 fw-bold">{{ $stat['value'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-6 col-xl-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body bs-stat">
                            <span class="bs-stat-icon {{ $branchesModuleEnabled ? 'bg-success text-success' : 'bg-secondary text-secondary' }} bg-opacity-10">
                                <i class="fas fa-power-off"></i>
                            </span>
                            <div>
                                <div class="text-muted small">حالة إدارة الفروع</div>
                                @if ($branchesModuleEnabled)
                                    <span class="badge bg-success mt-1">مفعلة</span>
                                @else
                                    <span class="badge bg-secondary mt-1">غير مفعلة</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (! $branchesModuleEnabled)
                <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <div>وحدة الفروع غير مفعلة حاليًا؛ يمكن حفظ السياسات الآن لكنها لن تؤثر على العزل حتى يتم تفعيل الوحدة.</div>
                </div>
            @endif

            <div class="row g-4">
                {{-- المحتوى الرئيسي --}}
                <div class="col-xl-9 order-2 order-xl-1">
                    <div class="alert alert-primary d-flex align-items-start gap-2 mb-4" role="alert">
                        <i class="fas fa-building mt-1"></i>
                        <div>
                            <strong>نطاق هذه الشاشة: الشركة بالكامل (جميع الفروع).</strong>
                            سياسات النظام المالية عامة، أما مشاركة البيانات المرجعية فتُحدد لكل فرع من الجدول أدناه.
                            الصلاحيات التفصيلية تُضبط من شاشة الأدوار، وهناك يظهر اسم الفرع والدور بوضوح قبل الحفظ.
                        </div>
                    </div>

                    <form method="post" action="{{ route('branches.settings.update') }}" id="branch-settings-form">
                        @csrf
                        <fieldset @disabled(! $canControl)>

                            {{-- 1. سياسات الشركة العامة --}}
                            <div class="card shadow-sm border-0 mb-4 bs-section" id="company-policies">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bs-section-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-building"></i></span>
                                        <div>
                                            <h5 class="mb-0">سياسات الشركة العامة</h5>
                                            <div class="text-muted small">هذه السياسات الثلاث تخص الشركة بالكامل ولا تتكرر لكل فرع.</div>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary">جميع الفروع</span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        @foreach ($activeSettingRows as $key => $row)
                                            @php $isOn = (bool) ($sharingSettings[$key] ?? false); @endphp
                                            <div class="col-md-6 col-xxl-4">
                                                <label class="bs-policy d-block mb-0 {{ $isOn ? 'is-on' : '' }}" for="branch-sharing-{{ $key }}">
                                                    <div class="bs-policy-head">
                                                        <span class="fw-bold">
                                                            <i class="fas {{ $row['icon'] }} text-primary me-1"></i>
                                                            {{ $row['title'] }}
                                                        </span>
                                                        <input type="hidden" name="{{ $key }}" value="0">
                                                        <input class="bs-toggle" type="checkbox" role="switch"
                                                            id="branch-sharing-{{ $key }}" name="{{ $key }}" value="1"
                                                            @checked($isOn)>
                                                    </div>
                                                    <p class="text-muted small mb-0 mt-2">{{ $row['hint'] }}</p>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="alert alert-warning d-flex align-items-start gap-2 mt-3 mb-0 small">
                                        <i class="fas fa-info-circle mt-1"></i>
                                        <div>
                                            <strong>تنبيه:</strong>
                                            تفعيل تخصيص الحسابات لا يغيّر أكواد الحسابات ولا يولّد أكوادًا داخل نطاق الفرع.
                                            الفلترة تكون فقط في الشجرة، قوائم اختيار الحساب، والتحقق عند حفظ القيد.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. مشاركة بيانات كل فرع --}}
                            <div class="card shadow-sm border-0 mb-4 bs-section" id="branch-sharing">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bs-section-icon bg-info bg-opacity-10 text-info"><i class="fas fa-share-alt"></i></span>
                                        <div>
                                            <h5 class="mb-0">مشاركة بيانات كل فرع</h5>
                                            <div class="text-muted small">
                                                فعّل لكل فرع مالك البيانات ما يشاركه مع جميع الفروع. صلاحيات الدور تظل مطبقة على الشاشات والإجراءات.
                                            </div>
                                        </div>
                                    </div>
                                    @if ($sharingBranches->count() > 6)
                                        <input type="search" class="form-control form-control-sm" style="max-width: 220px"
                                            placeholder="بحث عن فرع..." data-branch-filter="#branch-sharing-table">
                                    @endif
                                </div>
                                <div class="card-body p-0">
                                    @if ($sharingBranches->isEmpty())
                                        <div class="alert alert-info m-3 mb-3">
                                            لا توجد فروع نشطة يمكن إعداد المشاركة لها.
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table mb-0 bs-matrix" id="branch-sharing-table">
                                                <thead>
                                                    <tr>
                                                        <th class="bs-sticky-col ps-3">الفرع مالك البيانات</th>
                                                        @foreach ($branchSharingEntities as $entityKey => $entity)
                                                            <th class="text-center" title="{{ $entity['hint'] }}" data-bs-toggle="tooltip">
                                                                <div><i class="fas {{ $entity['icon'] }} me-1"></i> مشاركة {{ $entity['title'] }}</div>
                                                                <button type="button" class="btn btn-link bs-col-toggle"
                                                                    data-branch-toggle="branch-policy-col-{{ $entityKey }}">تحديد الكل</button>
                                                            </th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sharingBranches as $branch)
                                                        <tr data-branch-label="{{ $branch->name }} {{ $branch->code }}">
                                                            <td class="bs-sticky-col ps-3">
                                                                <div class="fw-semibold">{{ $branch->name }}</div>
                                                                <div class="text-muted small">{{ $branch->code }}</div>
                                                            </td>
                                                            @foreach ($branchSharingEntities as $entityKey => $entity)
                                                                <td class="text-center">
                                                                    <div class="d-inline-flex">
                                                                        <input type="hidden" name="sharing_policies[{{ $branch->id }}][{{ $entityKey }}]" value="0">
                                                                        <input class="bs-toggle branch-policy-col-{{ $entityKey }}" type="checkbox" role="switch" value="1"
                                                                            id="branch-policy-{{ $branch->id }}-{{ $entityKey }}"
                                                                            name="sharing_policies[{{ $branch->id }}][{{ $entityKey }}]"
                                                                            aria-label="مشاركة {{ $entity['title'] }} — {{ $branch->name }}"
                                                                            @checked(isset($branchSharingPolicies[$branch->id][$entityKey]))>
                                                                    </div>
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 3. إتاحة عاملات الإيواء للنقل --}}
                            <div class="card shadow-sm border-0 mb-4 bs-section" id="transfer-sharing">
                                <div class="card-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bs-section-icon bg-success bg-opacity-10 text-success"><i class="fas fa-exchange-alt"></i></span>
                                        <div>
                                            <h5 class="mb-0">إتاحة عاملات الإيواء لنقل الخدمات بين الفروع</h5>
                                            <div class="text-muted small">
                                                كل صف هو فرع العقد المصدر، وكل عمود هو الفرع الذي يُسمح له بإنشاء طلب نقل لعاملاته المؤهلات.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="alert alert-light border small m-3">
                                        <i class="fas fa-key text-warning me-1"></i>
                                        يلزم أيضًا منح الموظف صلاحية «إنشاء نقل خدمات لعاملات الفروع المتاحة». يُسجل الطلب الجديد لفرع الموظف، ويظل عقد المصدر في فرعه.
                                    </div>
                                    @if ($sharingBranches->count() < 2)
                                        <div class="alert alert-info mx-3 mb-3">يلزم فرعان نشطان على الأقل لإعداد إتاحة النقل.</div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0 bs-matrix text-center">
                                                <thead>
                                                    <tr>
                                                        <th class="bs-sticky-col text-start ps-3">
                                                            فرع المصدر <i class="fas fa-arrow-left mx-1"></i> متاحة لفرع
                                                        </th>
                                                        @foreach ($sharingBranches as $destination)
                                                            <th>{{ $destination->name }}</th>
                                                        @endforeach
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sharingBranches as $source)
                                                        <tr>
                                                            <td class="bs-sticky-col text-start ps-3 fw-semibold">{{ $source->name }}</td>
                                                            @foreach ($sharingBranches as $destination)
                                                                @if ($source->id === $destination->id)
                                                                    <td class="bs-self bg-light"><i class="fas fa-minus"></i></td>
                                                                @else
                                                                    <td>
                                                                        <input type="hidden" name="transfer_sharing[{{ $source->id }}][{{ $destination->id }}]" value="0">
                                                                        <input type="checkbox" class="bs-check transfer-row-{{ $source->id }}" value="1"
                                                                            id="transfer-sharing-{{ $source->id }}-{{ $destination->id }}"
                                                                            name="transfer_sharing[{{ $source->id }}][{{ $destination->id }}]"
                                                                            title="عاملات {{ $source->name }} متاحة لفرع {{ $destination->name }}"
                                                                            aria-label="عاملات {{ $source->name }} متاحة لفرع {{ $destination->name }}"
                                                                            @checked(!empty($transferSharingPolicies[$source->id][$destination->id]))>
                                                                    </td>
                                                                @endif
                                                            @endforeach
                                                            <td>
                                                                <button type="button" class="btn btn-link bs-col-toggle"
                                                                    data-branch-toggle="transfer-row-{{ $source->id }}">الكل</button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </fieldset>

                        @if ($canControl)
                            <div class="bs-savebar d-flex flex-wrap align-items-center justify-content-between gap-2" id="branch-settings-savebar">
                                <div class="small text-muted" data-branch-dirty>
                                    <i class="fas fa-check-circle text-success me-1"></i> لا توجد تغييرات غير محفوظة
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-light" type="reset">
                                        <i class="fas fa-undo"></i> تراجع
                                    </button>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-save"></i> حفظ سياسات الفروع
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0">
                                <i class="fas fa-lock"></i>
                                عرض فقط — تعديل السياسات يتطلب صلاحية التحكم في الفروع.
                            </div>
                        @endif
                    </form>
                </div>

                {{-- الشريط الجانبي --}}
                <div class="col-xl-3 order-1 order-xl-2">
                    <div class="bs-side d-flex flex-column gap-3">
                        <div class="card shadow-sm border-0 d-none d-xl-block">
                            <div class="card-body p-2">
                                <div class="text-muted small fw-bold px-2 pt-1 pb-2">أقسام الصفحة</div>
                                <nav class="nav flex-column bs-nav">
                                    <a class="nav-link" href="#company-policies"><i class="fas fa-building text-primary"></i> سياسات الشركة العامة</a>
                                    <a class="nav-link" href="#branch-sharing"><i class="fas fa-share-alt text-info"></i> مشاركة بيانات كل فرع</a>
                                    <a class="nav-link" href="#transfer-sharing"><i class="fas fa-exchange-alt text-success"></i> إتاحة عاملات الإيواء للنقل</a>
                                </nav>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-transparent">
                                <h6 class="mb-0"><i class="fas fa-map-signs text-primary me-1"></i> خريطة التحكم في بيانات الفروع</h6>
                            </div>
                            <div class="card-body">
                                <div class="bs-legend-item">
                                    <span class="badge bg-info align-self-start">مرجعية</span>
                                    <div>
                                        <div class="fw-semibold small">مشاركة يحددها الفرع</div>
                                        <div class="text-muted small">يختار كل فرع مشاركة عملائه أو سيره المخصصة أو مكاتبه مع بقية الفروع.</div>
                                    </div>
                                </div>
                                <div class="bs-legend-item">
                                    <span class="badge bg-success align-self-start">تشغيلية</span>
                                    <div>
                                        <div class="fw-semibold small">عزل دائم حسب الفرع</div>
                                        <div class="text-muted small">المعاملات تُعزل بفرع السجل. إتاحة عاملات الإيواء تسمح بإنشاء طلب نقل مرتبط بعقد المصدر مع بقاء تبعيته لفرعه.</div>
                                    </div>
                                </div>
                                <div class="bs-legend-item">
                                    <span class="badge bg-warning text-dark align-self-start">استثناء</span>
                                    <div>
                                        <div class="fw-semibold small">صلاحية عامة للدور</div>
                                        <div class="text-muted small">مثل مدير المنطقة أو المدقق؛ تسمح صلاحية view_all بعرض الكيان في كل الفروع بغض النظر عن سياسات المشاركة.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('branch-settings-form');
            if (!form) return;

            const switches = Array.from(form.querySelectorAll('input[type="checkbox"]'));
            const savebar = document.getElementById('branch-settings-savebar');
            const dirtyLabel = savebar ? savebar.querySelector('[data-branch-dirty]') : null;

            const refresh = function () {
                form.querySelectorAll('.bs-policy').forEach(function (card) {
                    const input = card.querySelector('input[type="checkbox"]');
                    card.classList.toggle('is-on', !!(input && input.checked));
                });

                if (!dirtyLabel) return;
                const changed = switches.filter(function (input) { return input.checked !== input.defaultChecked; }).length;
                savebar.classList.toggle('is-dirty', changed > 0);
                dirtyLabel.innerHTML = changed > 0
                    ? '<i class="fas fa-circle text-warning me-1"></i> ' + changed + ' تغيير غير محفوظ'
                    : '<i class="fas fa-check-circle text-success me-1"></i> لا توجد تغييرات غير محفوظة';
            };

            form.addEventListener('change', refresh);
            form.addEventListener('reset', function () { setTimeout(refresh, 0); });

            // تحديد/إلغاء تحديد عمود أو صف كامل
            form.querySelectorAll('[data-branch-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const group = Array.from(form.querySelectorAll('input.' + button.dataset.branchToggle))
                        .filter(function (input) { return !input.closest('tr')?.hidden; });
                    const allOn = group.every(function (input) { return input.checked; });
                    group.forEach(function (input) { input.checked = !allOn; });
                    refresh();
                });
            });

            // بحث الفروع في جدول المشاركة
            document.querySelectorAll('[data-branch-filter]').forEach(function (search) {
                const table = document.querySelector(search.dataset.branchFilter);
                if (!table) return;
                search.addEventListener('input', function () {
                    const term = search.value.trim().toLowerCase();
                    table.querySelectorAll('tbody tr').forEach(function (row) {
                        row.hidden = term !== '' && !row.dataset.branchLabel.toLowerCase().includes(term);
                    });
                });
            });

            window.addEventListener('beforeunload', function (event) {
                if (savebar && savebar.classList.contains('is-dirty') && !form.dataset.submitting) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
            form.addEventListener('submit', function () { form.dataset.submitting = '1'; });

            if (window.bootstrap && bootstrap.Tooltip) {
                document.querySelectorAll('.branch-settings [data-bs-toggle="tooltip"]').forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });
            }

            refresh();
        });
    </script>
@endsection
