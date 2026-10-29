@extends('admin.layout.indexs.index')

@section('page-title')
    {{ __('admin.recruitment_contract_stages') }}
@endsection

@push('css')
    <style>
        .rcs-page .rcs-tabs { border-bottom: 1px solid #e3e6ea; gap: .25rem; padding: 0 1rem; }
        .rcs-page .rcs-tabs .nav-link { border: 0; border-bottom: 2px solid transparent; border-radius: 0; color: #5f6b7a; padding: .85rem .9rem; font-weight: 500; background: none; }
        .rcs-page .rcs-tabs .nav-link:hover { color: #1f2937; }
        .rcs-page .rcs-tabs .nav-link.active { color: var(--bs-primary); border-bottom-color: var(--bs-primary); }
        .rcs-page .rcs-tabs .rcs-count { font-size: .72rem; background: #eef0f3; color: #5f6b7a; border-radius: 10px; padding: 1px 7px; margin-inline-start: .35rem; font-weight: 500; }
        .rcs-page .rcs-tabs .nav-link.active .rcs-count { background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); }
        .rcs-page .rcs-tabs .rcs-error-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--bs-danger); margin-inline-start: .35rem; vertical-align: middle; }
        .rcs-page .rcs-pane { display: none; }
        .rcs-page .rcs-pane.active { display: block; }
        .rcs-page .rcs-intro { color: #6b7280; font-size: .875rem; margin-bottom: 1rem; max-width: 760px; }
        .rcs-page .rcs-table { margin-bottom: 0; }
        .rcs-page .rcs-table thead th { background: #f7f8fa; color: #5f6b7a; font-size: .8rem; font-weight: 600; border-bottom-width: 1px; white-space: nowrap; }
        .rcs-page .rcs-table td { vertical-align: middle; }
        .rcs-page .rcs-code { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; color: #4b5563; background: #f3f4f6; border-radius: 4px; padding: 2px 6px; direction: ltr; display: inline-block; }
        .rcs-page .rcs-default { color: #8a94a3; font-size: .82rem; }
        .rcs-page .rcs-table .form-control, .rcs-page .rcs-table .form-select { font-size: .875rem; }
        .rcs-page .rcs-step-en { margin-top: .35rem; direction: ltr; text-align: left; }
        .rcs-page .rcs-checks { display: flex; flex-wrap: wrap; gap: .25rem 1rem; }
        .rcs-page .rcs-checks .form-check { margin: 0; font-size: .85rem; }
        .rcs-page .rcs-note { border-inline-start: 3px solid #d0d5dc; background: #f9fafb; padding: .6rem .85rem; font-size: .85rem; color: #4b5563; border-radius: 0 4px 4px 0; }
        .rcs-page .rcs-master { border: 1px solid #e3e6ea; border-radius: 6px; padding: .85rem 1rem; margin-bottom: 1rem; }
        .rcs-page .rcs-vars code { font-size: .78rem; margin-inline-end: .35rem; direction: ltr; display: inline-block; }
        .rcs-page .rcs-muted-table { opacity: .55; }
        .rcs-page .rcs-footer { position: sticky; bottom: 0; z-index: 5; background: #fff; border-top: 1px solid #e3e6ea; }
        .rcs-page tr.d-none + tr td { border-top-width: 1px; }
    </style>
@endpush

@section('content')
    @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW))
        @php
            $canControl = @AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL);

            $savedStepRows = $stageSteps->map(fn ($step) => [
                'id' => $step->id,
                'stage' => $step->stage,
                'name_ar' => $step->name_ar,
                'name_en' => $step->name_en,
                'nationality_id' => $step->nationality_id,
                'sort_order' => $step->sort_order,
                'is_active' => $step->is_active ? 1 : 0,
                'is_required' => $step->is_required ? 1 : 0,
                'notify_sms' => $step->notify_sms ? 1 : 0,
                'notify_email' => $step->notify_email ? 1 : 0,
                'notification_template' => $step->notification_template,
            ])->all();
            $stepRows = old('steps', $savedStepRows);

            $tabErrors = [
                'stages' => $errors->has('labels.*'),
                'steps' => $errors->has('steps') || $errors->has('steps.*')
                    || collect($stepRows)->contains(fn ($row) => empty($row['_delete']) && filled($row['stage'] ?? null)
                        && !\App\Support\RecruitmentStagePolicy::allowsSteps((string) $row['stage'])),
                'notifications' => $errors->has('notifications') || $errors->has('notifications.*'),
                'orders' => $errors->has('order_labels.*'),
            ];
            $initialTab = collect($tabErrors)->filter()->keys()->first() ?? 'stages';
        @endphp

        <div class="container-fluid rcs-page">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h3 class="h4 mb-1">{{ __('admin.recruitment_contract_stages') }}</h3>
                    <div class="text-muted small">{{ __('admin.recruitment_contract_stages_hint') }}</div>
                </div>
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    {{ __('admin.back_to_contracts') }}
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <div class="fw-semibold mb-1">{{ __('admin.recruitment_contract_stages_validation_error') }}</div>
                    <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('recruitment-contract-stages.update') }}" class="card" id="stagesForm">
                @csrf
                @method('PUT')

                <ul class="nav rcs-tabs" role="tablist">
                    @foreach ([
                        'stages' => ['أسماء المراحل', count($labels)],
                        'steps' => ['المراحل الفرعية', count($stepRows)],
                        'notifications' => ['إشعارات العميل', null],
                        'orders' => [__('admin.recruitment_order_statuses'), count($orderLabels)],
                    ] as $tab => [$title, $count])
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link {{ $initialTab === $tab ? 'active' : '' }}" role="tab"
                                data-rcs-tab="{{ $tab }}" aria-selected="{{ $initialTab === $tab ? 'true' : 'false' }}">
                                {{ $title }}
                                @if ($count !== null)<span class="rcs-count" @if ($tab === 'steps') id="stepsCount" @endif>{{ $count }}</span>@endif
                                @if ($tabErrors[$tab])<span class="rcs-error-dot" title="يوجد أخطاء في هذا القسم"></span>@endif
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="card-body">
                    {{-- أسماء المراحل --}}
                    <div class="rcs-pane {{ $initialTab === 'stages' ? 'active' : '' }}" data-rcs-pane="stages" role="tabpanel">
                        <p class="rcs-intro">الاسم الذي يظهر للموظفين والعملاء لكل مرحلة من مراحل عقد الاستقدام. اترك الحقل فارغًا لاستخدام القيمة الافتراضية.</p>
                        <div class="table-responsive border rounded">
                            <table class="table rcs-table">
                                <thead>
                                    <tr>
                                        <th style="width: 170px;">{{ __('admin.stage_code') }}</th>
                                        <th>{{ __('admin.stage_name_ar') }}</th>
                                        <th>{{ __('admin.stage_name_en') }}</th>
                                        <th style="width: 200px;">{{ __('admin.default_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labels as $status => $locales)
                                        <tr>
                                            <td><span class="rcs-code">{{ $status }}</span></td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][ar]"
                                                    class="form-control form-control-sm @error("labels.$status.ar") is-invalid @enderror"
                                                    value="{{ old("labels.$status.ar", $locales['ar'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td>
                                                <input type="text" name="labels[{{ $status }}][en]" dir="ltr"
                                                    class="form-control form-control-sm @error("labels.$status.en") is-invalid @enderror"
                                                    value="{{ old("labels.$status.en", $locales['en'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td class="rcs-default">{{ $defaults[$status]['ar'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- المراحل الفرعية --}}
                    <div class="rcs-pane {{ $initialTab === 'steps' ? 'active' : '' }}" data-rcs-pane="steps" role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <p class="rcs-intro mb-0">إجراءات تفصيلية داخل كل مرحلة، يمكن تخصيصها لجنسية معينة أو تطبيقها على الجميع.</p>
                            @if ($canControl)
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addStageStep">
                                    <i class="fas fa-plus me-1"></i> إضافة مرحلة فرعية
                                </button>
                            @endif
                        </div>

                        <div class="rcs-note mb-3">
                            مرحلة الوصول والحالات النهائية لها إجراءات مستقلة ولا تقبل مراحل فرعية.
                            العقود الجارية تحتفظ بمراحلها الفرعية وإعدادات إشعاراتها كما هي، والتعديلات هنا تُطبَّق على العقود الجديدة فقط.
                        </div>

                        <div class="table-responsive border rounded {{ count($stepRows) ? '' : 'd-none' }}" id="stageStepsWrap">
                            <table class="table rcs-table" id="stageStepsTable">
                                <thead>
                                    <tr>
                                        <th style="min-width: 160px;">المرحلة</th>
                                        <th style="min-width: 220px;">اسم المرحلة الفرعية</th>
                                        <th style="min-width: 160px;">الجنسية</th>
                                        <th style="width: 80px;">الترتيب</th>
                                        <th style="min-width: 120px;">الحالة</th>
                                        <th style="min-width: 240px;">إشعار العميل عند الإنجاز</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="stageStepsBody">
                                    @foreach ($stepRows as $index => $step)
                                        @include('admin.ingaz.recruitment_contract_stage_labels._step_row', ['index' => $index, 'step' => $step])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="noStageSteps" class="border rounded text-center text-muted py-4 small {{ count($stepRows) ? 'd-none' : '' }}">
                            لا توجد مراحل فرعية حتى الآن.
                            @if ($canControl)<br>استخدم زر «إضافة مرحلة فرعية» لإضافة أول مرحلة.@endif
                        </div>

                        <template id="stageStepTemplate">
                            @include('admin.ingaz.recruitment_contract_stage_labels._step_row', [
                                'index' => '__INDEX__',
                                'step' => ['id' => null, 'stage' => '', 'name_ar' => '', 'name_en' => '', 'nationality_id' => null, 'sort_order' => 0, 'is_active' => 1],
                            ])
                        </template>
                    </div>

                    {{-- الإشعارات --}}
                    <div class="rcs-pane {{ $initialTab === 'notifications' ? 'active' : '' }}" data-rcs-pane="notifications" role="tabpanel">
                        @include('admin.ingaz.recruitment_contract_stage_labels._notifications')
                    </div>

                    {{-- حالات الطلب --}}
                    <div class="rcs-pane {{ $initialTab === 'orders' ? 'active' : '' }}" data-rcs-pane="orders" role="tabpanel">
                        <p class="rcs-intro">{{ __('admin.recruitment_order_statuses_hint') }}</p>
                        <div class="table-responsive border rounded">
                            <table class="table rcs-table">
                                <thead>
                                    <tr>
                                        <th style="width: 170px;">{{ __('admin.status_code') }}</th>
                                        <th>{{ __('admin.status_name_ar') }}</th>
                                        <th>{{ __('admin.status_name_en') }}</th>
                                        <th style="width: 200px;">{{ __('admin.default_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orderLabels as $status => $locales)
                                        <tr>
                                            <td><span class="rcs-code">{{ $status }}</span></td>
                                            <td>
                                                <input type="text" name="order_labels[{{ $status }}][ar]"
                                                    class="form-control form-control-sm @error("order_labels.$status.ar") is-invalid @enderror"
                                                    value="{{ old("order_labels.$status.ar", $locales['ar'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td>
                                                <input type="text" name="order_labels[{{ $status }}][en]" dir="ltr"
                                                    class="form-control form-control-sm @error("order_labels.$status.en") is-invalid @enderror"
                                                    value="{{ old("order_labels.$status.en", $locales['en'] ?? '') }}"
                                                    maxlength="100">
                                            </td>
                                            <td class="rcs-default">{{ $orderDefaults[$status]['ar'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($canControl)
                    <div class="card-footer rcs-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span class="text-muted small">يحفظ الزر جميع الأقسام معًا.</span>
                        <div class="d-flex gap-2">
                            <button type="submit" name="reset_defaults" value="1" class="btn btn-sm btn-light border text-danger" id="resetDefaults">
                                {{ __('admin.restore_defaults') }}
                            </button>
                            <button type="submit" class="btn btn-sm btn-primary px-4">
                                <i class="fas fa-save me-1"></i>
                                {{ __('admin.save_contract_stages') }}
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    @endif
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const page = document.querySelector('.rcs-page');
            if (!page) return;

            // التبويبات
            const tabs = page.querySelectorAll('[data-rcs-tab]');
            const showTab = (name) => {
                tabs.forEach(t => {
                    const on = t.dataset.rcsTab === name;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                page.querySelectorAll('[data-rcs-pane]').forEach(p => p.classList.toggle('active', p.dataset.rcsPane === name));
            };
            tabs.forEach(t => t.addEventListener('click', () => {
                showTab(t.dataset.rcsTab);
                history.replaceState(null, '', '#' + t.dataset.rcsTab);
            }));
            @if (!$errors->any())
                const fromHash = location.hash.slice(1);
                if (fromHash && page.querySelector('[data-rcs-pane="' + CSS.escape(fromHash) + '"]')) showTab(fromHash);
            @endif

            // الحقول المطلوبة داخل تبويب مخفي: افتح التبويب قبل أن يعرض المتصفح رسالة التحقق
            document.getElementById('stagesForm').addEventListener('invalid', function (event) {
                const pane = event.target.closest('[data-rcs-pane]');
                if (pane && !pane.classList.contains('active')) showTab(pane.dataset.rcsPane);
            }, true);

            document.getElementById('resetDefaults')?.addEventListener('click', function (event) {
                if (!confirm('سيتم استرجاع الأسماء الافتراضية لجميع المراحل والحالات. هل تريد المتابعة؟')) event.preventDefault();
            });

            // المراحل الفرعية
            const body = document.getElementById('stageStepsBody');
            const wrap = document.getElementById('stageStepsWrap');
            const emptyState = document.getElementById('noStageSteps');
            const counter = document.getElementById('stepsCount');
            const template = document.getElementById('stageStepTemplate');
            let nextIndex = {{ count($stepRows) ? max(array_map('intval', array_keys($stepRows))) + 1 : 0 }};

            const refreshEmptyState = () => {
                const visible = body.querySelectorAll('tr:not(.d-none)').length;
                emptyState.classList.toggle('d-none', visible > 0);
                wrap.classList.toggle('d-none', visible === 0);
                if (counter) counter.textContent = visible;
            };

            document.getElementById('addStageStep')?.addEventListener('click', function () {
                body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                refreshEmptyState();
                body.lastElementChild?.querySelector('select')?.focus();
            });

            body.addEventListener('click', function (event) {
                const button = event.target.closest('[data-remove-step]');
                if (!button) return;

                const row = button.closest('tr');
                const id = row.querySelector('input[data-step-id]')?.value;
                if (id) {
                    if (!confirm('حذف هذه المرحلة الفرعية؟ لن يتأثر العقد الجاري الذي يستخدمها.')) return;
                    row.querySelector('input[data-delete-step]').value = '1';
                    row.classList.add('d-none');
                    // الصف المخفي يبقى في النموذج؛ أزل required حتى لا يمنع الحفظ
                    row.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
                } else {
                    row.remove();
                }
                refreshEmptyState();
            });

            // إبراز جدول الإشعارات عند إيقاف التفعيل
            const master = document.getElementById('notificationsEnabled');
            const stageTable = document.getElementById('stageNotificationsTable');
            const syncMaster = () => stageTable?.classList.toggle('rcs-muted-table', master && !master.checked);
            master?.addEventListener('change', syncMaster);
            syncMaster();

            // «تعديل عليه»: انسخ الرسالة الافتراضية إلى الحقل ليبدأ التعديل منها
            stageTable?.addEventListener('click', function (event) {
                const button = event.target.closest('[data-use-default]');
                if (!button) return;
                const field = button.closest('td').querySelector('textarea');
                if (!field.value.trim() || confirm('استبدال النص الحالي بالرسالة الافتراضية؟')) field.value = button.dataset.useDefault;
                field.focus();
            });
        });
    </script>
@endpush
