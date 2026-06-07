@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة الفروع
@endsection

@section('content')
    @php
        $branchesCount = $branchesCount ?? 0;
        $branchLimit = $branchLimit ?? null;
        $canCreateBranch = $canCreateBranch ?? true;
    @endphp

    <div class="page-body">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h3 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-code-branch text-primary me-2"></i>
                    إدارة الفروع
                </h3>
                @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CREATE, \App\PermKeys::BRANCHES_CONTROL]) && $canCreateBranch)
                    <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> إضافة فرع
                    </a>
                @endif
            </div>

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="alert {{ $canCreateBranch ? 'alert-info' : 'alert-warning' }}">
                عدد الفروع الحالي: {{ $branchesCount }}
                /
                {{ $branchLimit === null ? 'غير محدود' : $branchLimit }}
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form class="row g-3" method="GET">
                        <div class="col-md-5">
                            <label class="form-label">بحث</label>
                            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="اسم الفرع، الكود، الجوال">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الحالة</label>
                            <select class="form-select" name="is_active">
                                <option value="">جميع الحالات</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> إعادة تعيين
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th style="color:#fff">#</th>
                                <th style="color:#fff">الفرع</th>
                                <th style="color:#fff">الموقع</th>
                                <th style="color:#fff">التواصل</th>
                                <th style="color:#fff">مدير الفرع</th>
                                <th style="color:#fff">الحالة</th>
                                <th style="color:#fff">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                                <tr>
                                    <td>{{ $branches->firstItem() + $loop->index }}</td>
                                    <td>
                                        <strong>{{ $branch->name_ar }}</strong>
                                        @if($branch->name_en)
                                            <br><small class="text-muted">{{ $branch->name_en }}</small>
                                        @endif
                                        @if($branch->code)
                                            <br><span class="badge bg-light text-dark">{{ $branch->code }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $branch->city ?: '-' }}
                                        @if($branch->district)
                                            <br><small class="text-muted">{{ $branch->district }}</small>
                                        @endif
                                        @if($branch->latitude && $branch->longitude)
                                            <br><small class="text-muted">{{ $branch->latitude }}, {{ $branch->longitude }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $branch->phone ?: '-' }}
                                        @if($branch->whatsapp)
                                            <br><small class="text-muted">واتساب: {{ $branch->whatsapp }}</small>
                                        @endif
                                        @if($branch->email)
                                            <br><small class="text-muted">{{ $branch->email }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $branch->manager_name ?: '-' }}</td>
                                    <td>
                                        @if($branch->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">غير نشط</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_EDIT, \App\PermKeys::BRANCHES_CONTROL]))
                                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-warning" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_DELETE, \App\PermKeys::BRANCHES_CONTROL]))
                                                <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('هل أنت متأكد من حذف الفرع {{ $branch->name_ar }}؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-code-branch fa-2x mb-2"></i>
                                            <p>لا توجد فروع حتى الآن</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($branches->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $branches->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
