@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات الفروع
@endsection

@section('content')
    @php
        $branchLimit = $branchLimit ?? null;
        $canCreateBranch = $branchLimit === null || ($branchesCount ?? 0) < $branchLimit;
        $sharingSettings = $sharingSettings ?? [];
        $activeSettingRows = [
            'share_clients' => [
                'title' => 'مشاركة العملاء بين الفروع',
                'hint' => 'عند التفعيل يرى موظفو الفروع قائمة العملاء المشتركة. عند الإيقاف تُعزل حسب الفرع، مع استثناء clients_view_all_branches.',
            ],
            'share_maids' => [
                'title' => 'مشاركة السير الذاتية غير المخصصة',
                'hint' => 'عند التفعيل تظهر السيرة التي بلا فرع لكل الفروع. السير المخصصة تبقى معزولة، مع استثناء maids_view_all_branches.',
            ],
            'share_external_offices' => [
                'title' => 'مشاركة المكاتب الخارجية',
                'hint' => 'عند التفعيل تظهر كل المكاتب لكل الفروع. عند الإيقاف تُعزل حسب الفرع، مع استثناء offices_view_all_branches.',
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
                    لا يوجد اختيار فرع هنا لأن الإعدادات أدناه سياسة عامة وليست صلاحيات فرع محدد.
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
                                <h6>سياسة على مستوى الشركة</h6>
                                <p class="text-muted small mb-0">مثل العميل والسيرة والمكتب الخارجي وشجرة الحسابات. لا ترتبط بفرع واحد من هذه الشاشة.</p>
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
                                <h6>صلاحية للدور داخل فرع</h6>
                                <p class="text-muted small mb-0">مثل مدير المنطقة أو المدقق. يُمنح الاستثناء من صلاحيات الأدوار مع تحديد الفرع المقصود.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">السياسات العامة الفعّالة</h5>
                        <div class="text-muted small">
                            المفاتيح الخمسة موصولة بالتنفيذ وتعمل على مستوى الشركة وتؤثر في جميع الفروع.
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

                        @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CONTROL]))
                            <div class="mt-3 d-flex justify-content-end">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save"></i> حفظ سياسة جميع الفروع
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
