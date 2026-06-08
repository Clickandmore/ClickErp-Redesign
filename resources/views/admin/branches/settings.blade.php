@extends('admin.layout.indexs.index')

@section('page-title')
    إعدادات الفروع
@endsection

@section('content')
    @php
        $branchLimit = $branchLimit ?? null;
        $canCreateBranch = $branchLimit === null || ($branchesCount ?? 0) < $branchLimit;
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
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
