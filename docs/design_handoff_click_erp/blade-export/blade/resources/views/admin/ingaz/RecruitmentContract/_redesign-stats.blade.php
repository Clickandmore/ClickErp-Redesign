{{-- كتلة إحصائيات مراحل العقود — بعد إعادة التصميم
     المسار: resources/views/admin/ingaz/RecruitmentContract/_redesign-stats.blade.php
     الاستخدام: استبدل بها كتلة #contractStatsSection في index.blade.php، أو:
     @include('admin.ingaz.RecruitmentContract._redesign-stats')
     تُحافظ على: data-status و data-stat-key و data-warranty و ids الطي — فتبقى JS الحالية تعمل بلا تغيير. --}}
@php
    $ckStageLabels = \App\Support\RecruitmentContractStageLabels::labels();
    $ckStage = fn($status) => $ckStageLabels[$status][app()->getLocale() === 'en' ? 'en' : 'ar'] ?? $status;
    $ckGroups = [
        [
            'title' => 'سارية — قيد التنفيذ',
            'icon' => 'fa-play-circle',
            'id' => 'currentStatsSection',
            'cards' => [
                ['pending_maid_selection', 'stage_pending_maid_selection', 'fa-user-clock', 'pending'],
                ['new', 'stage_new', 'fa-file-circle-plus', 'new'],
                ['msaned', 'stage_msaned', 'fa-handshake', 'msaned'],
                ['tafwed', 'stage_tafwed', 'fa-file-signature', 'tafwed'],
                ['taffez', 'stage_taffez', 'fa-passport', 'taffez'],
                ['tazker', 'stage_tazker', 'fa-plane-departure', 'tazker'],
            ],
        ],
        [
            'title' => 'سارية — بعد الوصول',
            'icon' => 'fa-plane-arrival',
            'id' => 'completedStatsSection',
            'cards' => [
                ['arrival', 'stage_arrival', 'fa-plane-arrival', 'arrival'],
                ['shelter', 'stage_shelter', 'fa-house-user', 'shelter'],
                ['sari', 'stage_sari', 'fa-circle-check', 'current'],
            ],
        ],
        [
            'title' => 'مغلقة — مكتملة ومرتجعة',
            'icon' => 'fa-box-archive',
            'id' => 'closedStatsSection',
            'cards' => [
                ['completed', 'stage_completed', 'fa-circle-check', 'completed'],
                ['back', 'stage_back', 'fa-arrow-rotate-left', 'back'],
                ['refunded', 'stage_refunded', 'fa-money-bill-transfer', 'refunded'],
            ],
        ],
    ];
@endphp

<div class="row mb-3" id="contractStatsSection">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center contract-stats-header">
                <div class="contract-stats-heading">
                    <h6 class="mb-1">
                        <i class="fas fa-chart-bar me-2"></i>
                        إحصائيات مراحل العقود
                    </h6>
                    <small class="text-muted">الأرقام تتبع فلاتر البحث — اضغط أي بطاقة لتصفية الجدول عليها</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary stats-collapse-toggle"
                    id="contractStatsToggle" data-bs-toggle="collapse" data-bs-target="#contractStatsCollapse"
                    aria-expanded="true" aria-controls="contractStatsCollapse">
                    <span id="contractStatsToggleLabel">طي الإحصائيات</span>
                    <i class="fas fa-chevron-up" id="contractStatsToggleIcon"></i>
                </button>
            </div>
            <div class="collapse show" id="contractStatsCollapse">
                <div class="card-body">
                    {{-- الصف الأول: الإجماليات --}}
                    <div class="row g-3 stats-overview mb-4">
                        <div class="col-12 col-sm-6 col-xl-3">
                            <button type="button" class="stat-card stat-filter stat-card-all" data-status="">
                                <div class="stat-icon"><i class="fas fa-list-check"></i></div>
                                <div class="stat-body">
                                    <div class="stat-title">جميع العقود والطلبات</div>
                                    <div class="stat-value" data-stat-key="total">0</div>
                                </div>
                            </button>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <button type="button" class="stat-card stat-filter stat-card-current" data-status="current">
                                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                                <div class="stat-body">
                                    <div class="stat-title">العقود السارية</div>
                                    <div class="stat-value" data-stat-key="current_total">0</div>
                                </div>
                            </button>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <button type="button" class="stat-card stat-filter stat-card-completed" data-status="completed_stage">
                                <div class="stat-icon"><i class="fas fa-circle-check"></i></div>
                                <div class="stat-body">
                                    <div class="stat-title">العقود المكتملة</div>
                                    <div class="stat-value" data-stat-key="completed_total">0</div>
                                </div>
                            </button>
                        </div>
                        <div class="col-12 col-sm-6 col-xl-3">
                            <button type="button" class="stat-card stat-filter stat-card-back" data-status="back">
                                <div class="stat-icon"><i class="fas fa-arrow-rotate-left"></i></div>
                                <div class="stat-body">
                                    <div class="stat-title">العقود المرتجعة</div>
                                    <div class="stat-value" data-stat-key="stage_back">0</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- المراحل مجمّعة: تنفيذ / بعد الوصول / مغلقة --}}
                    <div class="row g-3">
                        @foreach ($ckGroups as $group)
                            <div class="col-12" id="{{ $group['id'] }}">
                                <div class="stats-group">
                                    <div class="stats-group-header">
                                        <span class="stats-group-icon"><i class="fas {{ $group['icon'] }}"></i></span>
                                        <span>{{ $group['title'] }}</span>
                                    </div>
                                    <div class="row g-2">
                                        @foreach ($group['cards'] as [$status, $key, $icon, $tone])
                                            <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                                                <button type="button" class="stat-card stat-filter stat-card-{{ $tone }}"
                                                    data-status="{{ $status }}">
                                                    <div class="stat-icon"><i class="fas {{ $icon }}"></i></div>
                                                    <div class="stat-body">
                                                        <div class="stat-title">{{ $ckStage($status) }}</div>
                                                        <div class="stat-value" data-stat-key="{{ $key }}">0</div>
                                                    </div>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- حالة الضمان --}}
                        <div class="col-12" id="warrantyStatsSection">
                            <div class="stats-group">
                                <div class="stats-group-header">
                                    <span class="stats-group-icon"><i class="fas fa-shield-halved"></i></span>
                                    <span>حالة الضمان</span>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12 col-sm-4">
                                        <button type="button" class="stat-card stat-filter stat-card-warranty-active"
                                            data-status="arrival" data-warranty="active">
                                            <div class="stat-icon"><i class="fas fa-shield"></i></div>
                                            <div class="stat-body">
                                                <div class="stat-title">داخل الضمان</div>
                                                <div class="stat-value" data-stat-key="warranty_active">0</div>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <button type="button" class="stat-card stat-filter stat-card-warranty-expired"
                                            data-status="completed" data-warranty="expired">
                                            <div class="stat-icon"><i class="fas fa-shield-circle-xmark"></i></div>
                                            <div class="stat-body">
                                                <div class="stat-title">منتهي الضمان</div>
                                                <div class="stat-value" data-stat-key="warranty_expired">0</div>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <button type="button" class="stat-card stat-filter stat-card-warranty-na"
                                            data-status="completed" data-warranty="not_applicable">
                                            <div class="stat-icon"><i class="fas fa-shield-slash"></i></div>
                                            <div class="stat-body">
                                                <div class="stat-title">بدون ضمان</div>
                                                <div class="stat-value" data-stat-key="warranty_not_applicable">0</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-none" id="backStatsSection"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
