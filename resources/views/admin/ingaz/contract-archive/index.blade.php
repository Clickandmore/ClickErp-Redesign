@extends('admin.layout.indexs.index')

@section('page-title')
    أرشيف {{ $typeLabel }}
@endsection

@section('style')
    <style>
        .contract-archive-page .ca-hero {
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: #fff;
        }
        .contract-archive-page .ca-hero h1 { font-size: 1.35rem; font-weight: 800; margin: 0; color: #fff; }
        .contract-archive-page .ca-hero p { margin: .35rem 0 0; color: rgba(255,255,255,.78); }
        .contract-archive-page .ca-card { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
        .contract-archive-page .ca-blocker { color: #b45309; font-size: .82rem; }
        .contract-archive-page table td, .contract-archive-page table th { vertical-align: middle; }
    </style>
@endsection

@section('content')
    @php
        $isRental = $type === 'rental';
        $otherType = $isRental ? 'recruitment' : 'rental';
        $canViewOther = \App\Http\Controllers\ingaz\ContractArchiveController::canView($otherType);
        $rejected = session('archive_rejected', []);
        $archiveRoutes = \App\Http\Controllers\ingaz\ContractArchiveController::routeNames($type);
    @endphp

    <div class="container-fluid contract-archive-page py-3">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if (!empty($rejected))
            <div class="alert alert-warning">
                <div class="fw-bold mb-1">عقود لم تُنفّذ عليها العملية:</div>
                @foreach ($rejected as $item)
                    <div>العقد #{{ $item['number'] }}: {{ $item['reason'] }}</div>
                @endforeach
            </div>
        @endif

        <div class="ca-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1><i class="fas fa-box-archive me-2"></i>أرشيف {{ $typeLabel }}</h1>
                <p>الأرشفة تخفي العقد المنتهي من القوائم ولوحة التحكم دون حذف أي بيانات. الفواتير والسندات والقيود تبقى في المالية كما هي.</p>
            </div>
            <div class="d-flex gap-2">
                @if ($canViewOther)
                    <a href="{{ route(\App\Http\Controllers\ingaz\ContractArchiveController::routeNames($otherType)['index']) }}" class="btn btn-outline-light btn-sm">
                        أرشيف {{ \App\Services\ContractArchiveService::typeLabel($otherType) }}
                    </a>
                @endif
                <a href="{{ route($listRoute) }}" class="btn btn-light btn-sm">العودة إلى {{ $typeLabel }}</a>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'archived' ? 'active' : '' }}"
                    href="{{ route($archiveRoutes['index'], ['tab' => 'archived']) }}">
                    العقود المؤرشفة <span class="badge bg-secondary">{{ $archivedCount }}</span>
                </a>
            </li>
            @if ($canManage)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'eligible' ? 'active' : '' }}"
                        href="{{ route($archiveRoutes['index'], ['tab' => 'eligible']) }}">
                        عقود منتهية (للأرشفة)
                    </a>
                </li>
            @endif
        </ul>

        <form method="GET" class="ca-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="flex-grow-1">
                <label class="form-label mb-1">بحث</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control"
                    placeholder="{{ $isRental ? 'رقم العقد، اسم العميل، الجوال، العاملة' : 'رقم العقد، اسم العميل، الجوال، العاملة أو الجواز' }}">
            </div>
            <button type="submit" class="btn btn-primary">بحث</button>
            @if ($search !== '')
                <a href="{{ route($archiveRoutes['index'], ['tab' => $tab]) }}" class="btn btn-outline-secondary">مسح</a>
            @endif
        </form>

        <form method="POST" id="archiveBulkForm" class="ca-card"
            action="{{ route($archiveRoutes[$tab === 'archived' ? 'restore' : 'archive']) }}">
            @csrf

            @if ($canManage && $contracts->count() > 0)
                <div class="p-3 border-bottom d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted">المحدد: <strong id="archiveSelectedCount">0</strong></span>
                    @if ($tab === 'eligible')
                        <input type="text" name="reason" class="form-control" style="max-width: 360px"
                            placeholder="سبب الأرشفة (مطلوب)" minlength="3" maxlength="500" required>
                        <button type="submit" class="btn btn-dark" id="archiveBulkSubmit" disabled
                            data-confirm="أرشفة العقود المحددة؟ ستُخفى من القوائم وتصبح للقراءة فقط حتى تُسترجع.">
                            <i class="fas fa-box-archive me-1"></i> أرشفة المحدد
                        </button>
                    @else
                        <input type="text" name="reason" class="form-control" style="max-width: 360px"
                            placeholder="سبب الاسترجاع (اختياري)" maxlength="500">
                        <button type="submit" class="btn btn-primary" id="archiveBulkSubmit" disabled
                            data-confirm="استرجاع العقود المحددة من الأرشيف؟">
                            <i class="fas fa-rotate-left me-1"></i> استرجاع المحدد
                        </button>
                    @endif
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @if ($canManage)
                                <th style="width: 42px"><input type="checkbox" class="form-check-input" id="archiveSelectAll"></th>
                            @endif
                            <th>رقم العقد</th>
                            <th>العميل</th>
                            <th>العاملة</th>
                            <th>الحالة</th>
                            @if ($tab === 'archived')
                                <th>تاريخ الأرشفة</th>
                                <th>بواسطة</th>
                                <th>السبب</th>
                            @else
                                <th>إمكانية الأرشفة</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contracts as $contract)
                            @php
                                $blocker = $blockers[$contract->id] ?? null;
                                $selectable = $canManage && ($tab === 'archived' || $blocker === null);
                            @endphp
                            <tr>
                                @if ($canManage)
                                    <td>
                                        @if ($selectable)
                                            <input type="checkbox" class="form-check-input archive-row-checkbox" name="ids[]" value="{{ $contract->id }}">
                                        @else
                                            <i class="fas fa-ban text-muted" title="{{ $blocker }}"></i>
                                        @endif
                                    </td>
                                @endif
                                <td class="fw-bold">#{{ \App\Services\ContractArchiveService::contractNumber($contract) }}</td>
                                <td>
                                    {{ $contract->user->name ?? '—' }}
                                    @if (!empty($contract->user?->phone))
                                        <div class="small text-muted">{{ $contract->user->phone }}</div>
                                    @endif
                                </td>
                                <td>{{ $contract->maid->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $isRental ? ($contract->status_text ?? $contract->status) : getContractStatus($contract->status) }}
                                    </span>
                                </td>
                                @if ($tab === 'archived')
                                    <td class="text-nowrap">{{ optional($contract->archived_at)->format('Y-m-d H:i') }}</td>
                                    <td>{{ $contract->archivedByAdmin->name ?? '—' }}</td>
                                    <td class="small">{{ $contract->archive_reason }}</td>
                                @else
                                    <td>
                                        @if ($blocker === null)
                                            <span class="badge bg-success">قابل للأرشفة</span>
                                        @else
                                            <span class="ca-blocker">{{ $blocker }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ route($showRoute, $contract->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    {{ $tab === 'archived' ? 'لا توجد عقود مؤرشفة.' : 'لا توجد عقود منتهية غير مؤرشفة.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="mt-3">
            {{ $contracts->links() }}
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            const form = document.getElementById('archiveBulkForm');
            const submit = document.getElementById('archiveBulkSubmit');
            const selectAll = document.getElementById('archiveSelectAll');
            const counter = document.getElementById('archiveSelectedCount');
            const boxes = () => Array.from(document.querySelectorAll('.archive-row-checkbox'));

            function refresh() {
                const selected = boxes().filter((box) => box.checked).length;
                if (counter) counter.textContent = selected;
                if (submit) submit.disabled = selected === 0;
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    boxes().forEach((box) => { box.checked = selectAll.checked; });
                    refresh();
                });
            }
            document.addEventListener('change', (event) => {
                if (event.target.classList.contains('archive-row-checkbox')) refresh();
            });
            if (form && submit) {
                form.addEventListener('submit', (event) => {
                    if (!confirm(submit.dataset.confirm)) event.preventDefault();
                });
            }
        })();
    </script>
@endsection
