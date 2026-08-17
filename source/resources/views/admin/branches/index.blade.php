@extends('admin.layout.indexs.index')

@section('page-title')
    إدارة الفروع
@endsection

@push('style')
<style>
    .branch-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        transition: box-shadow .2s;
    }
    .branch-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,.12);
    }
    .branch-status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-left: 5px;
    }
    .manager-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: .82rem;
        border: none;
        cursor: pointer;
        transition: background .15s;
    }
    .manager-btn.has-manager {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .manager-btn.has-manager:hover {
        background: #c8e6c9;
    }
    .manager-btn.no-manager {
        background: #f8f9fa;
        color: #868e96;
        border: 1px dashed #ced4da;
    }
    .manager-btn.no-manager:hover {
        background: #e9ecef;
        color: #495057;
    }
    .branches-table thead th {
        background: #f8f9fc;
        color: #495057;
        font-weight: 600;
        border-bottom: 2px solid #e3e6f0;
        white-space: nowrap;
    }
    .branches-table tbody td {
        vertical-align: middle;
    }
    .branch-name-ar { font-weight: 600; color: #1a1a2e; }
    .branch-code-badge {
        background: #edf2ff;
        color: #3b5bdb;
        font-size: .75rem;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .admins-count-badge {
        background: #f1f3f5;
        color: #495057;
        font-size: .78rem;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .sticky-actions {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 2;
    }
    .table-shell { overflow-x: auto; }
</style>
@endpush

@section('content')
    @php
        $branchesCount = $branchesCount ?? 0;
        $branchLimit   = $branchLimit ?? null;
        $canCreateBranch = $canCreateBranch ?? true;
    @endphp

    <div class="page-body">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="h3 mb-1 text-gray-800">
                        <i class="fas fa-code-branch text-primary me-2"></i>
                        إدارة الفروع
                    </h3>
                    <small class="text-muted">
                        {{ $branchesCount }} فرع
                        / {{ $branchLimit === null ? 'غير محدود' : $branchLimit }}
                        @if(!$canCreateBranch)
                            <span class="badge bg-warning text-dark ms-1">تم الوصول للحد الأقصى</span>
                        @endif
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('branches.report') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chart-bar"></i> تقرير
                    </a>
                    @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_CREATE, \App\PermKeys::BRANCHES_CONTROL]) && $canCreateBranch)
                        <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> فرع جديد
                        </a>
                    @endif
                </div>
            </div>

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card branch-card mb-4">
                <div class="card-body py-3">
                    <form class="row g-2 align-items-end" method="GET">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">بحث</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                       value="{{ request('search') }}" placeholder="اسم الفرع، الكود، المدينة، الجوال">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">الحالة</label>
                            <select class="form-select" name="is_active">
                                <option value="">جميع الحالات</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-4">
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

            {{-- Table --}}
            <div class="card branch-card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-shell">
                        <table class="table branches-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>الفرع</th>
                                    <th>الموقع</th>
                                    <th>التواصل</th>
                                    <th>مدير الفرع</th>
                                    <th>الموظفون</th>
                                    <th>الحالة</th>
                                    <th class="sticky-actions" style="width:100px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    @php $manager = $branch->manager; @endphp
                                    <tr>
                                        <td class="text-muted">{{ $branches->firstItem() + $loop->index }}</td>

                                        <td>
                                            <span class="branch-name-ar">{{ $branch->name_ar }}</span>
                                            @if($branch->name_en)
                                                <br><small class="text-muted">{{ $branch->name_en }}</small>
                                            @endif
                                            @if($branch->code)
                                                <br><span class="branch-code-badge">{{ $branch->code }}</span>
                                            @endif
                                            @if($branch->is_main)
                                                <span class="badge bg-primary ms-1" style="font-size:.7rem">رئيسي</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($branch->city)
                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $branch->city }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                            @if($branch->district)
                                                <br><small class="text-muted">{{ $branch->district }}</small>
                                            @endif
                                        </td>

                                        <td>
                                            @if($branch->phone)
                                                <i class="fas fa-phone text-success me-1"></i>{{ $branch->phone }}<br>
                                            @endif
                                            @if($branch->whatsapp)
                                                <small><i class="fab fa-whatsapp text-success me-1"></i>{{ $branch->whatsapp }}</small><br>
                                            @endif
                                            @if($branch->email)
                                                <small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $branch->email }}</small>
                                            @endif
                                            @if(!$branch->phone && !$branch->whatsapp && !$branch->email)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Manager column --}}
                                        <td>
                                            @php $canEditBranch = @AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_EDIT, \App\PermKeys::BRANCHES_CONTROL]); @endphp
                                            @if($manager)
                                                <button type="button"
                                                    class="manager-btn has-manager"
                                                    data-branch-id="{{ $branch->id }}"
                                                    data-branch-name="{{ e($branch->name_ar) }}"
                                                    data-manager-name="{{ e($manager->name) }}"
                                                    data-admins-url="{{ route('branches.admins', $branch) }}"
                                                    data-assign-url="{{ route('branches.assign-manager', $branch) }}"
                                                    onclick="handleManagerClick(this)">
                                                    <i class="fas fa-user-check"></i>
                                                    {{ $manager->name }}
                                                </button>
                                            @elseif($canEditBranch)
                                                <button type="button"
                                                    class="manager-btn no-manager"
                                                    data-branch-id="{{ $branch->id }}"
                                                    data-branch-name="{{ e($branch->name_ar) }}"
                                                    data-admins-url="{{ route('branches.admins', $branch) }}"
                                                    data-assign-url="{{ route('branches.assign-manager', $branch) }}"
                                                    onclick="handleManagerClick(this)">
                                                    <i class="fas fa-user-slash"></i>
                                                    غير مسند
                                                </button>
                                            @else
                                                <span class="text-muted small">غير مسند</span>
                                            @endif
                                        </td>

                                        <td>
                                            <button type="button" class="admins-count-badge border-0"
                                                style="cursor:pointer"
                                                data-branch-id="{{ $branch->id }}"
                                                data-branch-name="{{ e($branch->name_ar) }}"
                                                data-admins-url="{{ route('branches.admins', $branch) }}"
                                                data-available-url="{{ route('branches.available-admins', $branch) }}"
                                                data-assign-url="{{ route('branches.assign-admin', $branch) }}"
                                                data-remove-url="{{ route('branches.remove-admin', $branch) }}"
                                                onclick="openEmployeesPopup(this)">
                                                <i class="fas fa-users me-1"></i>
                                                {{ $branch->admins->count() }}
                                            </button>
                                        </td>

                                        <td>
                                            @if($branch->is_active)
                                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1" style="border-radius:20px">
                                                    <span class="branch-status-dot" style="background:#2e7d32"></span>نشط
                                                </span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1" style="border-radius:20px">
                                                    <span class="branch-status-dot" style="background:#c62828"></span>غير نشط
                                                </span>
                                            @endif
                                        </td>

                                        <td class="sticky-actions">
                                            <div class="btn-group btn-group-sm">
                                                @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_EDIT, \App\PermKeys::BRANCHES_CONTROL]))
                                                    <a href="{{ route('branches.edit', $branch) }}"
                                                       class="btn btn-outline-primary" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                @if (@AdminGuardHasAnyPerm([\App\PermKeys::BRANCHES_DELETE, \App\PermKeys::BRANCHES_CONTROL]))
                                                    <button type="button" class="btn btn-outline-danger"
                                                        title="حذف"
                                                        onclick="confirmDeleteBranch({{ $branch->id }}, '{{ e($branch->name_ar) }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <form id="delete-branch-{{ $branch->id }}"
                                                          action="{{ route('branches.destroy', $branch) }}"
                                                          method="POST" style="display:none">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="fas fa-code-branch fa-3x text-muted mb-3 d-block"></i>
                                            <p class="text-muted mb-0">لا توجد فروع</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($branches->hasPages())
                        <div class="d-flex justify-content-center p-3">
                            {{ $branches->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@push('js')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ---- Entry point: called when clicking manager button (assigned or not) ---- */
function handleManagerClick(btn) {
    const adminsUrl  = btn.dataset.adminsUrl;
    const assignUrl  = btn.dataset.assignUrl;
    const branchName = btn.dataset.branchName;
    const managerName = btn.dataset.managerName ?? null;

    if (managerName) {
        // Has manager — show details first, offer change
        Swal.fire({
            title: 'المسؤول عن الفرع',
            html: `<p class="mb-1"><i class="fas fa-user-check text-success me-1"></i><strong>${managerName}</strong></p>
                   <p class="text-muted small mb-0">الفرع: ${branchName}</p>`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-exchange-alt me-1"></i>تغيير',
            cancelButtonText: 'إغلاق',
            confirmButtonColor: '#3085d6',
        }).then(r => {
            if (r.isConfirmed) openAssignPopup(adminsUrl, assignUrl, branchName);
        });
    } else {
        openAssignPopup(adminsUrl, assignUrl, branchName);
    }
}

/* ---- Load admins and show assign popup ---- */
function openAssignPopup(adminsUrl, assignUrl, branchName) {
    fetch(adminsUrl, {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(admins => {
        if (!admins.length) {
            Swal.fire({ icon: 'info', title: 'لا يوجد موظفون', text: 'لا يوجد موظفون مسجلون في هذا الفرع.' });
            return;
        }

        let options = `<option value="">-- غير مسند --</option>`;
        admins.forEach(a => {
            options += `<option value="${a.id}" ${a.is_selected ? 'selected' : ''}>${a.name}</option>`;
        });

        Swal.fire({
            title: 'تعيين المسؤول',
            html: `<p class="text-muted mb-2">الفرع: <strong>${branchName}</strong></p>
                   <select id="swal-manager-select" class="form-select">${options}</select>`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check me-1"></i>حفظ',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#198754',
            preConfirm: () => document.getElementById('swal-manager-select').value,
        }).then(result => {
            if (result.isConfirmed) saveManager(assignUrl, result.value);
        });
    })
    .catch(err => Swal.fire('خطأ', 'تعذر تحميل قائمة الموظفين: ' + err.message, 'error'));
}

function saveManager(assignUrl, adminId) {
    const body = new FormData();
    body.append('_token', CSRF);
    if (adminId) body.append('admin_id', adminId);

    fetch(assignUrl, { method: 'POST', body, credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
        Swal.fire({ icon: 'success', title: data.message ?? 'تم الحفظ', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
    })
    .catch(() => Swal.fire('خطأ', 'حدث خطأ أثناء الحفظ', 'error'));
}

/* ---- Employees popup ---- */
function openEmployeesPopup(btn) {
    const adminsUrl    = btn.dataset.adminsUrl;
    const availableUrl = btn.dataset.availableUrl;
    const assignUrl    = btn.dataset.assignUrl;
    const removeUrl    = btn.dataset.removeUrl;
    const branchName   = btn.dataset.branchName;

    Promise.all([
        fetch(adminsUrl,    { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }).then(r => r.json()),
        fetch(availableUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }).then(r => r.json()),
    ])
    .then(([current, available]) => {
        renderEmployeesPopup(current, available, branchName, assignUrl, removeUrl, adminsUrl, availableUrl);
    })
    .catch(err => Swal.fire('خطأ', 'تعذر تحميل البيانات: ' + err.message, 'error'));
}

function renderEmployeesPopup(current, available, branchName, assignUrl, removeUrl, adminsUrl, availableUrl) {
    let currentList = current.length
        ? current.map(a => `
            <div class="d-flex align-items-center justify-content-between mb-2 p-2 rounded" style="background:#f8f9fa">
                <span><i class="fas fa-user me-2 text-muted"></i>${a.name}
                    ${a.is_branch_manager ? '<span class="badge bg-success ms-1" style="font-size:.7rem">مسؤول</span>' : ''}
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                    onclick="doRemoveAdmin('${removeUrl}', ${a.id}, '${branchName}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>`).join('')
        : '<p class="text-muted small text-center mb-2">لا يوجد موظفون في هذا الفرع</p>';

    let addOptions = available.length
        ? available.map(a => `<option value="${a.id}">${a.name}${a.branch_name ? ' (' + a.branch_name + ')' : ''}</option>`).join('')
        : '';

    let addSection = available.length ? `
        <hr class="my-2">
        <label class="form-label fw-semibold small mb-1">إضافة موظف للفرع</label>
        <div class="d-flex gap-2">
            <select id="swal-add-admin-select" class="form-select form-select-sm">
                <option value="">-- اختر موظف --</option>
                ${addOptions}
            </select>
            <button type="button" class="btn btn-sm btn-success px-3"
                onclick="doAssignAdmin('${assignUrl}', '${branchName}', '${adminsUrl}', '${availableUrl}', '${removeUrl}')">
                <i class="fas fa-plus"></i>
            </button>
        </div>` : '<p class="text-muted small text-center mt-2 mb-0">لا يوجد موظفون آخرون متاحون</p>';

    Swal.fire({
        title: `موظفو الفرع — ${branchName}`,
        html: `<div class="text-start">${currentList}${addSection}</div>`,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'إغلاق',
        width: 480,
    });
}

function doAssignAdmin(assignUrl, branchName, adminsUrl, availableUrl, removeUrl) {
    const adminId = document.getElementById('swal-add-admin-select')?.value;
    if (!adminId) return;

    fetch(assignUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: (() => { const f = new FormData(); f.append('_token', CSRF); f.append('admin_id', adminId); return f; })(),
    })
    .then(r => r.json())
    .then(() => {
        // Reload popup with fresh data
        Promise.all([
            fetch(adminsUrl,    { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }).then(r => r.json()),
            fetch(availableUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } }).then(r => r.json()),
        ]).then(([c, av]) => renderEmployeesPopup(c, av, branchName, assignUrl, removeUrl, adminsUrl, availableUrl));
    })
    .catch(() => Swal.fire('خطأ', 'حدث خطأ', 'error'));
}

function doRemoveAdmin(removeUrl, adminId, branchName) {
    Swal.fire({
        title: 'إزالة من الفرع',
        text: 'هل تريد إزالة هذا الموظف من الفرع؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(removeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: (() => { const f = new FormData(); f.append('_token', CSRF); f.append('admin_id', adminId); return f; })(),
        })
        .then(res => res.json())
        .then(() => location.reload())
        .catch(() => Swal.fire('خطأ', 'حدث خطأ', 'error'));
    });
}

/* ---- Delete confirmation ---- */
function confirmDeleteBranch(id, name) {
    Swal.fire({
        title: 'حذف الفرع',
        text: `هل أنت متأكد من حذف الفرع "${name}"؟ لا يمكن التراجع.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById(`delete-branch-${id}`).submit();
        }
    });
}
</script>
@endpush
