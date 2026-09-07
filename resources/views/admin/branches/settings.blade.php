@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات الفروع
@endsection

@section('content')
    @php
        $branchLimit = $branchLimit ?? null;
        $canCreateBranch = $branchLimit === null || ($branchesCount ?? 0) < $branchLimit;
        $sharingSettings = $sharingSettings ?? [];
        $sharingBranches = $sharingBranches ?? collect();
        $branchSharingPolicies = $branchSharingPolicies ?? [];
        $activeSettingRows = [
            'share_maids' => [
                'title' => 'مشاركة السير الذاتية غير المسندة لفرع',
                'hint' => 'عند التفعيل تظهر السيرة التي بلا فرع لكل الفروع. السير المخصصة تبقى معزولة، مع استثناء maids_view_all_branches.',
            ],
            'shared_cost_center_root' => [
                'title' => 'جذر مشترك لمراكز تكلفة الفروع',
                'hint' => 'سياسة مالية عامة: تنظم مراكز تكلفة جميع الفروع تحت أصل واحد.',
            ],
            'accounts_per_branch' => [
                'title' => 'تخصيص الحسابات على مستوى الفروع',
                'hint' => 'سياسة مالية عامة: الحساب غير المخصص متاح للجميع، والمخصص يظهر لفروعه فقط.',
            ],
        ];
        $branchSharingEntities = [
            'clients' => [
                'title' => 'العملاء',
                'hint' => 'السماح لجميع الفروع بمشاهدة عملاء الفرع المحدد.',
            ],
            'maids' => [
                'title' => 'السير الذاتية المخصصة',
                'hint' => 'السماح لجميع الفروع بمشاهدة السير المسندة إلى الفرع المحدد.',
            ],
            'external_offices' => [
                'title' => 'المكاتب الخارجية',
                'hint' => 'السماح لجميع الفروع بمشاهدة المكاتب التابعة للفرع المحدد.',
            ],
        ];
    @endphp

    <div class="page-body">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h3 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-sliders-h text-primary me-2"></i>
                    إعدادات الفروع
                </h3>
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CREATE, \App\PermKeys::BRANCHES_CONTROL]) && $canCreateBranch)
                    <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> إضافة فرع
                    </a>
                @endif
            </div>

            <div class="alert alert-primary d-flex align-items-start gap-2" role="alert">
                <i class="fas fa-building mt-1"></i>
                <div>
                    <strong>نطاق هذه الشاشة: الشركة بالكامل (جميع الفروع).</strong>
                    سياسات النظام المالية عامة، أما مشاركة البيانات المرجعية فتُحدد لكل فرع من القائمة أدناه.
                    الصلاحيات التفصيلية تُضبط من شاشة الأدوار، وهناك يظهر اسم الفرع والدور بوضوح قبل الحفظ.
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-2">إجمالي الفروع</div>
                            <div class="h3 mb-0">{{ $branchesCount }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-2">الفروع النشطة</div>
                            <div class="h3 mb-0">{{ $activeBranchesCount }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-2">العدد المسموح به</div>
                            <div class="h3 mb-0">{{ $branchLimit === null ? 'غير محدود' : $branchLimit }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small mb-2">حالة إدارة الفروع</div>
                            @if (@SystemModuleEnabled('branches'))
                                <span class="badge bg-success">مفعلة</span>
                            @else
                                <span class="badge bg-secondary">غير مفعلة</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">روابط إدارة الفروع</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('branches.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-code-branch"></i> إدارة الفروع
                        </a>
                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CREATE, \App\PermKeys::BRANCHES_CONTROL]) && $canCreateBranch)
                            <a href="{{ route('branches.create') }}" class="btn btn-outline-success">
                                <i class="fas fa-plus"></i> إضافة فرع
                            </a>
                        @endif
                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::PERMISSIONS_VIEW, \App\PermKeys::PERMISSIONS_ASSIGN]))
                            <a href="{{ route('dash.roles.index') }}" class="btn btn-outline-dark">
                                <i class="fas fa-user-shield"></i> صلاحيات الأدوار حسب الفرع
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0">خريطة التحكم في بيانات الفروع</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100">
                                <span class="badge bg-info mb-2">بيانات مرجعية</span>
                                <h6>مشاركة يحددها الفرع</h6>
                                <p class="text-muted small mb-0">يختار كل فرع مشاركة عملائه أو سيره المخصصة أو مكاتبه مع بقية الفروع.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100">
                                <span class="badge bg-success mb-2">بيانات تشغيلية ومالية</span>
                                <h6>عزل دائم حسب الفرع</h6>
                                <p class="text-muted small mb-0">العقود والفواتير والقيود والطلبات تُعزل بفرع السجل، ولا يوجد مفتاح مشاركة يلغي هذا العزل.</p>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 h-100">
                                <span class="badge bg-warning text-dark mb-2">استثناء وظيفي</span>
                                <h6>صلاحية عامة للدور</h6>
                                <p class="text-muted small mb-0">مثل مدير المنطقة أو المدقق؛ تسمح صلاحية view_all بعرض الكيان في كل الفروع بغض النظر عن سياسات المشاركة.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">سياسات الشركة العامة</h5>
                        <div class="text-muted small">
                            هذه السياسات الثلاث تخص الشركة بالكامل ولا تتكرر لكل فرع.
                        </div>
                    </div>
                    <span class="badge bg-primary">جميع الفروع</span>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('branches.settings.update') }}">
                        @csrf

                        <div class="row g-3">
                            @foreach ($activeSettingRows as $key => $row)
                                <div class="col-lg-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="{{ $key }}" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                id="branch-sharing-{{ $key }}" name="{{ $key }}" value="1"
                                                @checked((bool) ($sharingSettings[$key] ?? false))>
                                            <label class="form-check-label fw-bold" for="branch-sharing-{{ $key }}">
                                                {{ $row['title'] }}
                                            </label>
                                        </div>
                                        <p class="text-muted small mb-0 mt-2">{{ $row['hint'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>تنبيه:</strong>
                            تفعيل تخصيص الحسابات لا يغيّر أكواد الحسابات ولا يولّد أكوادًا داخل نطاق الفرع.
                            الفلترة تكون فقط في الشجرة، قوائم اختيار الحساب، والتحقق عند حفظ القيد.
                        </div>

                        <div class="card border-primary mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-1">مشاركة بيانات كل فرع</h5>
                                <div class="text-muted small">
                                    اختر الفرع مالك البيانات، ثم حدد البيانات التي يشاركها مع جميع الفروع.
                                    صلاحيات الدور تظل مطبقة على الشاشات والإجراءات.
                                </div>
                            </div>
                            <div class="card-body">
                                @if ($sharingBranches->isEmpty())
                                    <div class="alert alert-info mb-0">
                                        لا توجد فروع نشطة يمكن إعداد المشاركة لها.
                                    </div>
                                @else
                                    <div class="mb-3">
                                        <label class="form-label fw-bold" for="branch-sharing-policy-selector">الفرع</label>
                                        <select class="form-select" id="branch-sharing-policy-selector">
                                            @foreach ($sharingBranches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @foreach ($sharingBranches as $branch)
                                        <div class="branch-sharing-policy-panel" data-branch-policy="{{ $branch->id }}" @if (! $loop->first) hidden @endif>
                                            <div class="alert alert-light border">
                                                إعداد مشاركة بيانات <strong>{{ $branch->name }}</strong>. عند التفعيل تصبح بيانات هذا الفرع مرئية لبقية الفروع المصرح لها بالشاشة.
                                            </div>

                                            <div class="row g-3">
                                                @foreach ($branchSharingEntities as $entityKey => $entity)
                                                    @php
                                                        $policyEnabled = isset($branchSharingPolicies[$branch->id][$entityKey]);
                                                    @endphp
                                                    <div class="col-lg-4">
                                                        <div class="border rounded-3 p-3 h-100">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="sharing_policies[{{ $branch->id }}][{{ $entityKey }}]" value="0">
                                                                <input class="form-check-input" type="checkbox" role="switch" value="1"
                                                                    id="branch-policy-{{ $branch->id }}-{{ $entityKey }}"
                                                                    name="sharing_policies[{{ $branch->id }}][{{ $entityKey }}]"
                                                                    @checked($policyEnabled)>
                                                                <label class="form-check-label fw-bold" for="branch-policy-{{ $branch->id }}-{{ $entityKey }}">
                                                                    مشاركة {{ $entity['title'] }}
                                                                </label>
                                                            </div>
                                                            <p class="text-muted small mb-0 mt-2">{{ $entity['hint'] }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CONTROL]))
                            <div class="mt-3 d-flex justify-content-end">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save"></i> حفظ سياسات الفروع
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('branch-sharing-policy-selector');
            if (!selector) return;

            const panels = document.querySelectorAll('[data-branch-policy]');
            const showSelectedBranch = function () {
                panels.forEach(function (panel) {
                    panel.hidden = panel.dataset.branchPolicy !== selector.value;
                });
            };

            selector.addEventListener('change', showSelectedBranch);
            showSelectedBranch();
        });
    </script>
@endsection
