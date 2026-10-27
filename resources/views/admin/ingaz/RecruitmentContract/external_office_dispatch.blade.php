@extends('admin.layout.indexs.index')

@section('page-title')
    {{ \App\Support\InvoiceSupplierWorkflow::enabled() ? 'فاتورة المورد' : 'إرسالية المكتب الخارجي' }} - العقد #{{ $contract->id }}
@endsection

@php
    $statusClass = match ($dispatchState['code']) {
        'completed' => 'success',
        'outdated' => 'warning text-dark',
        'incomplete' => 'danger',
        default => 'secondary',
    };
    $previewUrl = $dispatch ? route('RecruitmentContract.externalOfficeDispatch.print', $contract->id) : null;
    $pdfUrl = $dispatch
        ? route('RecruitmentContract.externalOfficeDispatch.print', ['contract' => $contract->id, 'driver' => 'dompdf', 'download' => 1])
        : null;
    $currentContractIsBulkCompatible = $bulkCompatibleContracts->contains(
        fn ($bulkContract) => (int) $bulkContract->id === (int) $contract->id
    );
    $showBulkCreateForm = $isEditable && $bulkCompatibleContracts->isNotEmpty();
    $useUnifiedCreateForm = !$dispatch && $showBulkCreateForm && $currentContractIsBulkCompatible;
    // Creation eligibility no longer applies after a dispatch has been recorded.
    // In particular, reaching arrival/completion must not make a completed dispatch
    // look invalid merely because the contract is no longer in a current status.
    $availabilityReasons = $dispatch ? [] : $selectionUnavailableReasons;
@endphp

@section('content')
    <div class="alert alert-info">
        <strong>إثبات تكلفة المورد الخارجي:</strong>
        @if (\App\Support\RecruitmentSupplierCostRecognition::mode() === 'on_dispatch')
            عند حفظ فاتورة المورد بتاريخها، بشرط وجود فاتورة للعقد وتفعيل القيود التلقائية. لا يتكرر القيد عند الوصول.
        @else
            عند وصول العاملة؛ حفظ فاتورة المورد لا ينشئ قيد التكلفة.
        @endif
    </div>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">{{ \App\Support\InvoiceSupplierWorkflow::enabled() ? 'فاتورة المورد' : 'إرسالية المكتب الخارجي' }}</h3>
                <div class="text-muted">مرتبطة بالعقد رقم #{{ $contract->id }}</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($showBulkCreateForm)
                <a href="#bulk-dispatch-card" id="show-bulk-dispatch-form" class="btn btn-primary">
                    <i class="fas fa-layer-group me-1"></i>
                    {{ $dispatch ? 'إنشاء فاتورة مورد لعقود أخرى' : 'اختيار عقود وعمل فاتورة مورد' }}
                </a>
                @endif
                <a href="{{ route('RecruitmentContract.index', ['status' => 'current']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-1"></i>
                    العقود السارية
                </a>
                <a href="{{ route('contracts.details', $contract->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    الرجوع لتفاصيل العقد
                </a>
                @if ($previewUrl)
                    <a href="{{ $previewUrl }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                        <i class="fas fa-eye me-1"></i>
                        معاينة
                    </a>
                @endif
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" class="btn btn-primary" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf me-1"></i>
                        PDF
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('bulk_dispatch_errors') && count(session('bulk_dispatch_errors')))
            <div class="alert alert-warning">
                <div class="fw-semibold mb-2">ملاحظات على الإرسال المجمع:</div>
                <ul class="mb-0 ps-3">
                    @foreach (session('bulk_dispatch_errors') as $bulkDispatchError)
                        <li>{{ $bulkDispatchError }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$dispatch)
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">
                            <i class="fas fa-clipboard-check text-primary me-1"></i>
                            شروط إتاحة العقد للفاتورة مورد
                        </h5>
                        <div class="small text-muted">حالة العقد الحالي #{{ $contract->id }} بحسب البيانات المسجلة الآن.</div>
                    </div>
                    @if (!empty($availabilityReasons))
                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i>العقد غير متاح</span>
                    @elseif ($dispatch)
                        <span class="badge bg-info text-dark"><i class="fas fa-pen me-1"></i>فاتورة المورد مسجلة</span>
                    @else
                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>العقد متاح</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if ($dispatch)
                    <div class="alert alert-info mb-3">
                        فاتورة المورد مسجلة لهذا العقد بتاريخ
                        {{ optional($dispatch->dispatch_date)->format('Y-m-d') ?: 'غير محدد' }}،
                        ويمكن تعديلها من النموذج بالأسفل بدل إنشاء فاتورة مورد جديدة.
                    </div>
                @endif

                @if (!empty($availabilityReasons))
                    <div class="alert alert-danger mb-3">
                        <div class="fw-semibold mb-2">سبب عدم إتاحة العقد:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($availabilityReasons as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-2 mb-3">
                    @foreach ($selectionEligibilityChecks as $check)
                        @php
                            // Only meaningful while creating a dispatch; in edit mode it is
                            // an informational note, not a failed condition.
                            $isEditModeNote = ($check['key'] ?? null) === 'no_existing_dispatch' && $dispatch;
                            $checkLabel = $isEditModeNote ? 'فاتورة المورد مسجلة ويمكن تعديلها' : $check['label'];
                            $checkBorder = $isEditModeNote ? 'border-info' : ($check['passed'] ? 'border-success' : 'border-danger');
                            $checkIcon = $isEditModeNote
                                ? 'fa-info-circle text-info'
                                : ($check['passed'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger');
                        @endphp
                        <div class="col-md-6 col-xl-4">
                            <div class="d-flex align-items-start gap-2 border rounded-3 p-2 h-100 bg-light {{ $checkBorder }}">
                                <i class="fas {{ $checkIcon }} mt-1"></i>
                                <span class="small fw-semibold">{{ $checkLabel }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="alert alert-info mb-0">
                    <div class="fw-semibold mb-2">شروط حفظ فاتورة المورد:</div>
                    <ul class="mb-0 ps-3 small">
                        <li>إدخال تاريخ فاتورة مورد صحيح وتكلفة العقد بالدولار بقيمة أكبر من صفر.</li>
                        <li>تحديد محطة وصول موحدة، أو توفر محطة وصول مستقلة لكل عقد محدد.</li>
                        <li>عند اختيار أكثر من عقد يجب أن تكون جميع العقود من نفس المكتب الخارجي ونفس الجنسية.</li>
                        <li>يجب ألا تكون هناك فاتورة مورد مسجلة مسبقًا لأي عقد مختار.</li>
                        <li>رقم التأشيرة وعمر العاملة بيانات عرض وليسا شرطًا لمنع إنشاء فاتورة المورد.</li>
                    </ul>
                </div>
            </div>
        </div>
        @endif

        @if ($showBulkCreateForm)
            <div id="bulk-dispatch-panel" {{ $dispatch && !session('bulk_dispatch_errors') && !old('return_contract_id') ? 'hidden' : '' }}>
            <form method="POST" enctype="multipart/form-data" action="{{ route('RecruitmentContract.externalOfficeDispatch.bulkStore') }}"
                id="bulk-dispatch-card" class="card shadow-sm border-0 mb-4 overflow-hidden">
                @csrf
                <input type="hidden" name="return_contract_id" value="{{ $contract->id }}">

                <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                    <div>
                        <h5 class="mb-1">إنشاء فاتورة مورد</h5>
                        <div class="small opacity-75">
                            {{ $bulkGroup['office_name'] }} / {{ $bulkGroup['nationality_name'] }}
                        </div>
                    </div>
                    <span class="badge bg-white text-primary" id="inline-bulk-selected-count">0 عقد</span>
                </div>

                <div class="card-body">
                    <div class="alert alert-light border d-flex align-items-start gap-2">
                        <i class="fas fa-info-circle text-primary mt-1"></i>
                        <div>
                            <div class="fw-semibold">نموذج موحد للفاتورة مورد الفردية والمجمعة</div>
                            <div class="small text-muted">
                                اختيار عقد واحد ينشئ فاتورة مورد فردية، واختيار أكثر من عقد ينشئ فواتير الموردين مجمعة بنفس التاريخ والتكلفة ومحطة الوصول.
                            </div>
                            @if (!$currentContractIsBulkCompatible)
                                <div class="small text-danger mt-1">
                                    العقد الحالي #{{ $contract->id }} غير متاح للاختيار:
                                    {{ !empty($selectionUnavailableReasons) ? implode(' ', $selectionUnavailableReasons) : 'العقد ليس ضمن مجموعة المكتب والجنسية المعروضة.' }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">تاريخ فاتورة المورد <span class="text-danger">*</span></label>
                            <input type="date" name="dispatch_date" class="form-control"
                                value="{{ $initialDispatchDate }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">التكلفة بالدولار لكل عقد <span class="text-danger">*</span></label>
                            <input type="number" name="office_contract_cost_usd" id="inline_bulk_cost_usd"
                                class="form-control" value="{{ $initialOfficeContractCostUsd }}"
                                step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">المقابل بالريال</label>
                            <div class="input-group">
                                <input type="number" id="inline_bulk_cost_sar" class="form-control"
                                    value="{{ $initialOfficeContractCostSar }}" step="0.01" min="0.01">
                                <span class="input-group-text">ريال</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">محطة الوصول الموحدة</label>
                            <input type="text" name="arrival_station" class="form-control"
                                value="{{ $initialArrivalStation }}"
                                placeholder="اتركها فارغة لاستخدام محطة كل عقد">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h6 class="mb-0">العقود المتاحة من نفس المكتب والجنسية</h6>
                        <small class="text-muted">حدد عقدًا واحدًا أو أكثر</small>
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;">
                                        <input type="checkbox" class="form-check-input" id="inline-bulk-select-all">
                                    </th>
                                    <th>العقد</th>
                                    <th>العميل</th>
                                    <th>العاملة</th>
                                    <th>رقم التأشيرة</th>
                                    <th>محطة الوصول</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bulkCompatibleContracts as $bulkContract)
                                    @php
                                        $bulkSource = $bulkContract->buildExternalOfficeDispatchSourceData('1.00');
                                        $bulkStation = $bulkContract->resolveExternalOfficeDispatchArrivalStation();
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="contract_ids[]"
                                                class="form-check-input inline-bulk-contract"
                                                value="{{ $bulkContract->id }}"
                                                {{ in_array($bulkContract->id, $oldBulkContractIds, true) ? 'checked' : '' }}>
                                        </td>
                                        <td>
                                            <a href="{{ route('contracts.details', $bulkContract->id) }}" target="_blank" rel="noopener">
                                                #{{ $bulkContract->id }}
                                            </a>
                                            @if ($bulkContract->id === $contract->id)
                                                <span class="badge bg-primary ms-1">العقد الحالي</span>
                                            @endif
                                        </td>
                                        <td>{{ $bulkContract->user?->name ?: 'غير محدد' }}</td>
                                        <td>
                                            <div>{{ $bulkSource['worker_name'] ?: 'غير محدد' }}</div>
                                            <small class="text-muted">{{ $bulkSource['passport_number'] ?: '-' }}</small>
                                        </td>
                                        <td>{{ $bulkSource['visa_number'] ?: '-' }}</td>
                                        <td>{{ $bulkStation ?: 'سيتم استخدام المحطة الموحدة' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-body border-top">
                    @include('admin.ingaz.RecruitmentContract.partials.dispatch-attachments-input')
                    <div class="form-text">تُرفق الملفات بكل إرسالية ضمن العقود المحددة.</div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" id="inline-bulk-submit">
                        <i class="fas fa-paper-plane me-1"></i>
                        <span id="inline-bulk-submit-label">إنشاء فاتورة المورد</span>
                    </button>
                </div>
            </form>
            </div>
        @endif

        @include('admin.ingaz.RecruitmentContract.partials.dispatch-attachments')
        @if (!$useUnifiedCreateForm)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">حالة فاتورة المورد</h5>
                        <div class="text-muted small">لا يمكن وصول العقد أو إغلاقه قبل اكتمال فاتورة المورد هذه.</div>
                    </div>
                    <span class="badge bg-{{ $statusClass }}">{{ $dispatchState['label'] }}</span>
                </div>
                <div class="alert alert-light border mb-0">{{ $dispatchState['message'] }}</div>
            </div>
        </div>

        @if (!$contract->canManageExternalOfficeDispatch())
            <div class="alert alert-warning">
                هذا العقد ليس ضمن العقود الجارية حاليًا، لذلك يمكن عرض فاتورة المورد فقط بدون تعديل.
            </div>
        @endif

        @if (!empty($sourceErrors))
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">لا يمكن اعتماد فاتورة المورد قبل استكمال البيانات التالية:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($sourceErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('RecruitmentContract.externalOfficeDispatch.store', $contract->id) }}">
            @csrf

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات فاتورة المورد</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">رقم العقد</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['contract_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم التأشيرة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['visa_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ $contract->company_profile_id ? 'اسم الشركة' : 'اسم الكفيل / العميل' }}</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['sponsor_name'] }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ $contract->company_profile_id ? 'السجل التجاري' : 'رقم الهوية' }}</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['sponsor_identity_number'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">اسم العاملة / المستقدم</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_name'] }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم الجواز</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['passport_number'] }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">العمر</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_age'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الخبرة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_experience'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">الديانة</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['worker_religion'] }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">المكتب الخارجي</label>
                            <input type="text" class="form-control" value="{{ $sourceSnapshot['office_name'] }}" readonly>
                        </div>

                        <div class="col-md-4">
                            @if(\App\Support\InvoiceSupplierWorkflow::enabled() || $dispatch?->supplier_total_cost_usd !== null)
                            <label class="form-label">إثبات تكلفة المورد</label>
                            <select name="supplier_recognition_mode" class="form-control mb-2" {{ $isEditable ? '' : 'disabled' }}>
                                <option value="full" @selected(old('supplier_recognition_mode', $dispatch?->supplier_recognition_mode ?? 'full') === 'full')>التكلفة كاملة</option>
                                <option value="partial" @selected(old('supplier_recognition_mode', $dispatch?->supplier_recognition_mode) === 'partial')>جزء من التكلفة</option>
                            </select>
                            <label class="form-label">إجمالي تكلفة المورد (دولار)</label>
                            <input type="number" name="supplier_total_cost_usd" class="form-control mb-2" step="0.01" min="0.01" max="99999999.99"
                                value="{{ old('supplier_total_cost_usd', $dispatch?->supplier_total_cost_usd ?? $sourceSnapshot['office_contract_cost_usd']) }}" {{ $isEditable ? 'required' : 'disabled' }}>
                            <label class="form-label">المبلغ المراد إثباته (دولار) <span class="text-danger">*</span></label>
                            <small class="text-muted d-block">في الخيار الكامل يُستخدم الإجمالي، وفي الخيار الجزئي يُستخدم المبلغ أدناه.</small>
                            @else
                            <label class="form-label">تكلفة العقد بالدولار <span class="text-danger">*</span></label>
                            @endif
                            <input type="number" name="office_contract_cost_usd" id="office_contract_cost_usd"
                                class="form-control @error('office_contract_cost_usd') is-invalid @enderror"
                                value="{{ $initialOfficeContractCostUsd }}"
                                step="0.01" min="0.01"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('office_contract_cost_usd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المقابل بالريال</label>
                            <div class="input-group">
                                <input type="number" id="office_contract_cost_sar" class="form-control"
                                    value="{{ $initialOfficeContractCostSar }}"
                                    step="0.01" min="0.01"
                                    {{ $isEditable ? '' : 'disabled' }}>
                                <span class="input-group-text">ريال</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">محطة الوصول <span class="text-danger">*</span></label>
                            <input type="text" name="arrival_station"
                                class="form-control @error('arrival_station') is-invalid @enderror"
                                value="{{ $initialArrivalStation }}"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('arrival_station')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ فاتورة المورد <span class="text-danger">*</span></label>
                            <input type="date" name="dispatch_date"
                                class="form-control @error('dispatch_date') is-invalid @enderror"
                                value="{{ $initialDispatchDate }}"
                                {{ $isEditable ? '' : 'disabled' }}>
                            @error('dispatch_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                @if ($isEditable)
                    <div class="card-body border-top">
                        @include('admin.ingaz.RecruitmentContract.partials.dispatch-attachments-input')
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary" {{ !empty($sourceErrors) ? 'disabled' : '' }}>
                            <i class="fas fa-save me-1"></i>
                            حفظ فاتورة المورد
                        </button>
                    </div>
                @endif
            </div>
        </form>
        @endif
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usdInput = document.getElementById('office_contract_cost_usd');
            document.getElementById('show-bulk-dispatch-form')?.addEventListener('click', function(event) {
                event.preventDefault();
                const panel = document.getElementById('bulk-dispatch-panel');
                if (!panel) return;
                panel.hidden = false;
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            const sarInput = document.getElementById('office_contract_cost_sar');
            const rate = parseFloat({{ json_encode($usdToSarRate) }});
            let updatingCurrency = false;

            function parseMoney(value) {
                return parseFloat(String(value || '').replace(',', '.'));
            }

            function setConvertedValue(targetInput, value) {
                if (!targetInput) {
                    return;
                }

                targetInput.value = Number.isFinite(value) && value > 0
                    ? value.toFixed(2)
                    : '';
            }

            function updateSarCost() {
                if (updatingCurrency || !Number.isFinite(rate) || rate <= 0) {
                    return;
                }

                updatingCurrency = true;
                setConvertedValue(sarInput, parseMoney(usdInput?.value) * rate);
                updatingCurrency = false;
            }

            function updateUsdCost() {
                if (updatingCurrency || !Number.isFinite(rate) || rate <= 0) {
                    return;
                }

                updatingCurrency = true;
                setConvertedValue(usdInput, parseMoney(sarInput?.value) / rate);
                updatingCurrency = false;
            }

            usdInput?.addEventListener('input', updateSarCost);
            sarInput?.addEventListener('input', updateUsdCost);

            const bulkUsdInput = document.getElementById('inline_bulk_cost_usd');
            const bulkSarInput = document.getElementById('inline_bulk_cost_sar');
            const bulkCheckboxes = Array.from(document.querySelectorAll('.inline-bulk-contract'));
            const bulkSelectAll = document.getElementById('inline-bulk-select-all');
            const bulkSubmit = document.getElementById('inline-bulk-submit');
            const bulkSelectedCount = document.getElementById('inline-bulk-selected-count');
            const bulkSubmitLabel = document.getElementById('inline-bulk-submit-label');

            function syncInlineBulkSelection() {
                const selectedCount = bulkCheckboxes.filter(checkbox => checkbox.checked).length;
                if (bulkSelectedCount) {
                    bulkSelectedCount.textContent = selectedCount + ' عقد';
                }
                if (bulkSubmit) {
                    bulkSubmit.disabled = selectedCount === 0;
                }
                if (bulkSubmitLabel) {
                    bulkSubmitLabel.textContent = selectedCount > 1
                        ? `إنشاء ${selectedCount} فواتير الموردين مجمعة`
                        : 'إنشاء فاتورة مورد فردية';
                }
                if (bulkSelectAll) {
                    bulkSelectAll.checked = bulkCheckboxes.length > 0 && selectedCount === bulkCheckboxes.length;
                    bulkSelectAll.indeterminate = selectedCount > 0 && selectedCount < bulkCheckboxes.length;
                }
            }

            bulkCheckboxes.forEach(checkbox => checkbox.addEventListener('change', syncInlineBulkSelection));
            bulkSelectAll?.addEventListener('change', function () {
                bulkCheckboxes.forEach(checkbox => {
                    checkbox.checked = bulkSelectAll.checked;
                });
                syncInlineBulkSelection();
            });

            bulkUsdInput?.addEventListener('input', function () {
                setConvertedValue(bulkSarInput, parseMoney(bulkUsdInput.value) * rate);
            });
            bulkSarInput?.addEventListener('input', function () {
                setConvertedValue(bulkUsdInput, parseMoney(bulkSarInput.value) / rate);
            });
            syncInlineBulkSelection();
        });
    </script>
@endpush
