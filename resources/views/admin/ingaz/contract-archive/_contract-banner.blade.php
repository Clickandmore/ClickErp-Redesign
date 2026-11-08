{{--
    شريط الأرشفة داخل صفحة العقد.
    المتغيرات: $archiveType ('recruitment' | 'rental')، $contract
--}}
@php
    $archiveService = app(\App\Services\ContractArchiveService::class);
    $archiveColumnReady = $contract::archiveColumnExists();
    $canViewArchive = \App\Http\Controllers\ingaz\ContractArchiveController::canView($archiveType);
    $canManageArchive = \App\Http\Controllers\ingaz\ContractArchiveController::canManage($archiveType);
    $finalStatuses = $archiveType === 'rental'
        ? \App\Services\ContractArchiveService::RENTAL_FINAL_STATUSES
        : \App\Services\ContractArchiveService::RECRUITMENT_FINAL_STATUSES;
    $isArchivedContract = $archiveColumnReady && $contract->isArchived();
    $showArchiveAction = $archiveColumnReady && !$isArchivedContract && $canManageArchive
        && in_array((string) $contract->status, $finalStatuses, true);
    $archiveBlockerText = $showArchiveAction ? $archiveService->archiveBlocker($contract) : null;
    $archiveRejected = session('archive_rejected', []);
    $archiveRoutes = \App\Http\Controllers\ingaz\ContractArchiveController::routeNames($archiveType);
@endphp

@if ($archiveColumnReady && ($isArchivedContract || $showArchiveAction || !empty($archiveRejected)))
    <div class="contract-archive-banner no-print mb-3">
        @if (!empty($archiveRejected))
            <div class="alert alert-warning mb-2">
                @foreach ($archiveRejected as $item)
                    <div>العقد #{{ $item['number'] }}: {{ $item['reason'] }}</div>
                @endforeach
            </div>
        @endif

        @if ($isArchivedContract)
            <div class="alert alert-secondary d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0">
                <div>
                    <div class="fw-bold"><i class="fas fa-box-archive me-1"></i> هذا العقد مؤرشف — للقراءة فقط</div>
                    <div class="small">
                        منذ {{ optional($contract->archived_at)->format('Y-m-d H:i') }}
                        @if ($contract->archivedByAdmin)
                            بواسطة {{ $contract->archivedByAdmin->name }}
                        @endif
                        @if ($contract->archive_reason)
                            — السبب: {{ $contract->archive_reason }}
                        @endif
                    </div>
                    <div class="small text-muted">الفواتير والسندات والقيود المرتبطة به تبقى ظاهرة في المالية كما هي.</div>
                </div>
                <div class="d-flex gap-2">
                    @if ($canViewArchive)
                        <a href="{{ route($archiveRoutes['index']) }}" class="btn btn-sm btn-outline-secondary">الأرشيف</a>
                    @endif
                    @if ($canManageArchive)
                        <form method="POST" action="{{ route($archiveRoutes['restore']) }}"
                            onsubmit="return confirm('استرجاع العقد من الأرشيف؟ سيعود للظهور في القوائم ويمكن تعديله.');">
                            @csrf
                            <input type="hidden" name="ids[]" value="{{ $contract->id }}">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-rotate-left me-1"></i> استرجاع من الأرشيف
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @elseif ($showArchiveAction)
            <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0">
                @if ($archiveBlockerText)
                    <div class="small text-muted">
                        <i class="fas fa-box-archive me-1"></i> لا يمكن أرشفة هذا العقد الآن: {{ $archiveBlockerText }}
                    </div>
                @else
                    <div class="small">
                        <i class="fas fa-box-archive me-1"></i> العقد منتهٍ ويمكن أرشفته. الأرشفة تخفيه من القوائم ولا تحذف أي بيانات.
                    </div>
                    <form method="POST" action="{{ route($archiveRoutes['archive']) }}"
                        class="d-flex flex-wrap gap-2 align-items-center"
                        onsubmit="return confirm('أرشفة هذا العقد؟ سيصبح للقراءة فقط حتى يُسترجع.');">
                        @csrf
                        <input type="hidden" name="ids[]" value="{{ $contract->id }}">
                        <input type="text" name="reason" class="form-control form-control-sm" style="min-width: 240px"
                            placeholder="سبب الأرشفة" required minlength="3" maxlength="500">
                        <button type="submit" class="btn btn-sm btn-outline-dark">أرشفة العقد</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endif
